<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 6 janv. 2011
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : processBuilder.php
 * GLE-1.2
 */
require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . '/Synopsis_Process/class/process.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php');
if (!$user->rights->process->configurer) {
    accessforbidden();
}
global $langs;
$langs->load('process@Synopsis_Process');
$langs->load("process@Synopsis_Process");

//    Interface Creation
//    Interface Modification
//    Interface Suppression
//    PDF => Associe un modele de Pdf à un process
//    Process bloquant
//    Droit sur le process
//    Post traitement du formulaire et pretraitement => implique de coller du code php dans l'appli avec une abstraction des variables.

$action = $_REQUEST['action'];
$id = $_REQUEST['id'];

$js = ' <script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.min.js" type="text/javascript"/>';
$js .="<script>";
$js .= <<<EOF
 jQuery(document).ready(function(){
            jQuery.validator.addMethod(
            'required',
            function(value, element) {
                return value.match(/^[\w\W\d]+$/);
            },
            '<br>Ce champ est requis'
        );
            jQuery.validator.addMethod(
            'requiredRadio',
            function(value1, element) {
                var name = jQuery(element).attr('name');
                var value = jQuery('input[type=radio][name='+name+']:checked').val();
                return (value && value.match(/^[\w\W\d]+$/));
            },
            '<br>Ce champ est requis'
        );

 });

EOF;
$js .= "</script>";
$msg = "";

switch ($action) {
    case 'unsetRevisionModel': {
            $process = new process($db);
            $process->id = $id;
            $res = $process->setRevisionModel(false);
//            if ($res)
//                header('Location: processBuilder.php?id=' . $id);
//            else
                header('Location: processBuilder.php?action=Modify&id=' . $id);
        }
        break;
    case 'setRevisionModel': {
            $process = new process($db);
            $process->id = $id;
            $res = $process->setRevisionModel($_REQUEST['revision_model']);
//            if ($res)
//                header('Location: processBuilder.php?id=' . $id);
//            else
                header('Location: processBuilder.php?action=Modify&id=' . $id);
        }
        break;
    case 'activer': {
            if ($id > 0) {
                $process = new process($db);
                $process->fetch($id);
                $res = $process->activate();
                header('Location: processBuilder.php?id=' . $id);
            }
        }
        break;
    case 'desactiver': {
            if ($id > 0) {
                $process = new process($db);
                $process->fetch($id);
                $res = $process->unactivate();
                header('Location: processBuilder.php?id=' . $id);
            }
        }
        break;
    case 'add': {
            $process = new process($db);
            $process->label = $_REQUEST['label'];
            $process->description = $_REQUEST['description'];
            $res = $process->add();
            if ($res > 0)
                header('Location: processBuilder.php?id=' . $res);
            else if ($res == -1)
                $msg = "Erreur SQL : " . $process->error;
            else if ($res == -2)
                $msg = "Erreur : " . $process->error;
        }
    case 'Create':
        $js .= "<script>jQuery(document).ready(function(){
                            jQuery('#addForm').validate();
                            jQuery('#addElem').click(function(){ if(jQuery('#addForm').validate().form()){ jQuery('#addForm').submit(); } }); });</script>";
        llxHeader($js, 'Ajouter un process');
        print "<div class='titre'>Ajouter un process</div><br/>";
        if ($msg . "x" != "x") {
            print "<div class='ui-error error'>" . $msg . "</div><br/>";
        }
        print "<br/><form id='addForm' action='processBuilder.php?action=add' method='post'> ";
        print "<table cellpadding=15 width=100%>";
        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("processName");
        print "    <td class='ui-widget-content'><input class='required" . ($res == -2 ? " error" : "") . "' type='text' value='" . $_REQUEST['label'] . "' name='label'>";
        print "<tr><th class='ui-widget-header ui-state-default'>" . $langs->trans("processDescription");
        print "    <td class='ui-widget-content'><textarea class='required' type='text' name='description'>" . $_REQUEST['description'] . "</textarea>";
        print "</table>";
        print "<button class='butAction' id='addElem'>Ajouter</button>";
        print "</form>";
        break;
    case 'Clone': {
            if ($id > 0) {
                $process = new process($db);
                $process->fetch($id);
                $res = $process->cloneProcess();
                if ($res > 0)
                    header('location: processBuilder.php?id=' . $res);
                else
                    header('location: processBuilder.php?id=' . $id);
            }
        }
        break;
    case 'update':
        if ($id > 0) {
            $process = new process($db);
            $process->fetch($id);
            $process->label = $_REQUEST['label'];
            $process->description = $_REQUEST['description'];
            $process->pretraitement = $_REQUEST['pretraitement'];
            $process->posttraitement = $_REQUEST['posttraitement'];
            $process->validAction = $_REQUEST['validAction'];
            $process->askValidAction = $_REQUEST['askValidAction'];
            $process->reviseAction = $_REQUEST['reviseAction'];
            $process->bloquant = $_REQUEST['bloquant'];
            $process->PROCESS_ADDON = $_REQUEST['numref'];
            $process->PROCESS_MASK = $_REQUEST['PROCESS_' . strtoupper($process->PROCESS_ADDON) . '_MASK'];
            $process->ADDON_PDF = $_REQUEST['pdf'];
            $process->formulaire_refid = $_REQUEST['formulaire_refid'];
            $process->typeElement_refid = $_REQUEST['typeElement_refid'];
            $process->trigger_refid = $_REQUEST['typeElement_refid'];//$_REQUEST['trigger_refid'];

            //Droits
            $db->begin();
            $commit = true;
            //1 reset tous les droits
            $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights WHERE process_refid = " . $id;
            $sql = $db->query($requete);
            $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process_group_rights WHERE process_refid = " . $id;
            $sql1 = $db->query($requete);
            $commit = ($sql && $commit ? true : false);
            if ($sql1)
            //2 mets les droits
                foreach ($_REQUEST as $key => $val) {
                    if (preg_match('/([u|g]{1})([0-9]+)-([0-9]+)/', $key, $arr)) {
                        $type = $arr[1];
                        $userid = $arr[2];
                        $rightId = $arr[3];
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_rights (process_refid,user_refid,right_refid,valeur)
                                     VALUES (" . $id . "," . $userid . "," . $rightId . ",1)";
                        if ($type == 'g') {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_group_rights (process_refid,group_refid,right_refid,valeur)
                                         VALUES (" . $id . "," . $userid . "," . $rightId . ",1)";
                        }
                        $sql = $db->query($requete);
                        $commit = ($sql && $commit ? true : false);
                    }
                }
            if ($commit) {
                $db->commit();
            } else {
                $db->rollback();
            }
            $res = $process->update();
            if ($res > 0)
                header('Location: processBuilder.php?id=' . $res);
            else if ($res == -1)
                $msg = "Erreur SQL : " . $process->error;
            else if ($res == -2)
                $msg = "Erreur : " . $process->error;
        }

    case 'Modify':
        if ($id > 0) {
            $process = new process($db);
            $process->fetch($id);

            $js .= "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.jDoubleSelect.js' type='text/javascript'></script>";
            $js .= "<script>var validator=false;";
            $js .= "jQuery(document).ready(function(){ jQuery('#tabs').tabs({spinner: 'Chargement', cache: true, fx: { opacity:'toggle'}});
                        jQuery('.tb').each(function(){ jQuery(this).rotate('45deg'); });
                        jQuery('#accordion').accordion({animated: 'bounceslide',  navigation: true,autoHeight: false, fillSpace: false});
                        jQuery('#modForm').validate({ invalidHandler: handleError() });
                        jQuery('#Modifier').click(function(){ 
                            if (jQuery('#modForm').validate({ invalidHandler: handleError() }).form()){
                                jQuery('#modForm').submit(); 
                            }
                        });
                        jQuery('SELECT.double').each(function(){ 
                            jQuery(this).jDoubleSelect({text:'', finish: function(){ 
                                jQuery('SELECT.double').each(function(){ 
//                                    jQuery(this).selectmenu({style:'dropdown',maxHeight: 300});
                                }); 
                            }, el1_change: function(){ 
                                jQuery('SELECT.double').each(function(){ 
//                                    jQuery(this).selectmenu({
//                                        style:'dropdown',maxHeight: 300
//                                    });
                                }); 
                            }, destName:'trigger_refid', el2_dest:jQuery('#dest2el') 
                        });
                    });

                    });
                        ;
                   ";
            if ($_REQUEST['tabs'] . "x" != "x") {
                $js .= "jQuery(document).ready(function(){
                    jQuery('#tabs').tabs('select','" . $_REQUEST['tabs'] . "');
                });";
            }
            $js .= "
function handleError()
{
     if(jQuery('#jsMsg').length >0) { jQuery('#jsMsg').remove();  }
     if (!jQuery('#modForm').validate().form()){
         jQuery('#tabs').prepend('<div id=\"jsMsg\" class=\"ui-error error\">'+jQuery('#modForm').validate().numberOfInvalids() + ' champ(s) est(sont) incomplet(s)</div>');
                    setTimeout(function()
                    {
                        jQuery('#jsMsg').fadeOut(
                            'slow',
                            function ()
                            {
                                jQuery('#jsMsg').remove();
                             }
                        );
                     }, 3500);
     }
}

function activatePdf(str){
    jQuery.ajax({
        url:'ajax/activatePdf-xml_response.php' ,
        cache:false,
        datatype:'xml',
        type:'POST',
        data: 'pdf='+str,
        success: function(msg){
            if (jQuery(msg).find('OK') && jQuery(msg).find('OK').text()=='OK')
            {
                location.href='processBuilder.php?id=" . $process->id . "&action=Modify&tabs=pdf';
            } else {
                console.log(msg);
            }
          }
     });
}";
            $js .= "</script>";
            $js .= '<style>#typeElement_refid-button{ display: none;}</style>';
//            $js .= "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery-css-transform.js' type='text/javascript'></script>";
            $js .= "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery-animate-css-rotate-scale.js' type='text/javascript'></script>";
            llxHeader($js, 'Modifier un process');
            print "<div class='titre'>Modifier un process</div><br/>";
            if ($msg . "x" != "x") {
                print "<div class='ui-error error'>" . $msg . "</div><br/>";
            }
            print "<form id='modForm' method='POST' action='processBuilder.php?id=" . $process->id . "&action=update'>";
            print "<table cellpadding=15 width=100%>";
            print "<tr><th class='ui-widget-header ui-state-default'>Nom";
            print "    <td class='ui-widget-content'><input name='label' class='required" . ($res == -2 ? " error" : "") . "' value='" . addslashes($process->label) . "'>";
            print "    <th class='ui-widget-header ui-state-default'>Statut";
            print "    <td class='ui-widget-content'>" . $process->getLibStatut(5);
            print "<tr><th class='ui-widget-header ui-state-default'>Description";
            print "    <td colspan=3 class='ui-widget-content'><textarea name='description' class='required'>" . $process->description . "</textarea>";
            print "<tr><td colspan=4 class='ui-widget-content'>";
            print "<div id='tabs'>";
            print "<ul>";
            print "<li><a href='#global'>Configuration globale</a></li>";
            print "<li><a href='#refnum'>Num&eacute;rotation</a></li>";
            print "<li><a href='#pdf'>Editions PDF</a></li>";
            print "<li><a href='#droits'>Droits</a></li>";
            print "<li><a href='#rev'>R&eacute;visions</a></li>";
            print "</ul>";
            print "<div id='global'>";
            print "<table cellpadding=10 width=100%>";
            print "<tr><th class='ui-widget-header ui-state-default'>Process Bloquant";
            print "    <td class='ui-widget-content'><input type='checkbox' name='bloquant' " . ($process->bloquant == 1 ? "CHECKED >" : ">");
            print "<tr><th class='ui-widget-header ui-state-default'>Formulaire du process";
            print "    <td class='ui-widget-content'><select name='formulaire_refid' class='required'>";
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form ORDER BY label";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                if ($res->id == $process->formulaire_refid) {
                    print "<option SELECTED value='" . $res->id . "'>" . $res->label . "</option>";
                } else {
                    print "<option value='" . $res->id . "'>" . $res->label . "</option>";
                }
            }
            print "</select>";
            print "<tr><th class='ui-widget-header ui-state-default'>Type d'&eacute;l&eacute;ment / D&eacute;clancheur";
            print "    <td class='ui-widget-content'><table width=100%><tr><td><select name='typeElement_refid' id='typeElement_refid' class='required double noSelDeco'>";
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_type_element ORDER BY rang";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                print "<optgroup label='" . str_replace(" ", "_", $res->label) . "'>";
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_trigger as t, " . MAIN_DB_PREFIX . "Synopsis_Process_type_element_trigger as te WHERE te.trigger_refid = t.id AND te.element_refid = " . $res->id . "  ORDER BY code";
                $sql1 = $db->query($requete);
                while ($res1 = $db->fetch_object($sql1)) {
                    if ($res->id == $process->typeElement_refid && $res1->id == $process->trigger->id) {
                        print "<option SELECTED value='" . $res1->id . "'>" . $res1->code . "</option>";
                    } else {
                        print "<option value='" . $res1->id . "'>" . $res1->code . "</option>";
                    }
                }
                print "</optgroup>";
            }
            print "</select>";
            print "<td><div id='dest2el'></div></table>";

            print "<tr><th class='ui-widget-header ui-state-default'>Pr&eacute;-traitement";
            print "    <td class='ui-widget-content' align=center><textarea style='width:100%' name='pretraitement'>" . $process->pretraitement . "</textarea>";
            print "<tr><th class='ui-widget-header ui-state-default'>Post-traitement";
            print "    <td class='ui-widget-content' align=center><textarea style='width:100%' name='posttraitement'>" . $process->posttraitement . "</textarea>";
            print "<tr><th class='ui-widget-header ui-state-default'>Event revision";
            print "    <td class='ui-widget-content' align=center><textarea style='width:100%' name='reviseAction'>" . $process->reviseAction . "</textarea>";
            print "<tr><th class='ui-widget-header ui-state-default'>Event demande validation";
            print "    <td class='ui-widget-content' align=center><textarea style='width:100%' name='askValidAction'>" . $process->askValidAction . "</textarea>";
            print "<tr><th class='ui-widget-header ui-state-default'>Event validation";
            print "    <td class='ui-widget-content' align=center><textarea style='width:100%' name='validAction'>" . $process->validAction . "</textarea>";

            print "</table>";
            print "</div>";

            /*
             *  Module numerotation
             */
            print "<div id='refnum'>";

            $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsis_process/";
            $html = new Form($db);
            print "<br>";
            print_titre($langs->trans("Mod&egrave;le de num&eacute;rotation de ce process"));

            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Name") . "</td>\n";
            print '<td>' . $langs->trans("Description") . "</td>\n";
            print '<td nowrap>' . $langs->trans("Example") . "</td>\n";
            print '<td align="center" width="60">' . $langs->trans("Activated") . '</td>';
            print '</tr>' . "\n";

            clearstatcache();

            $handle = opendir($dir);
            if ($handle) {
                $var = true;
                while (($file = readdir($handle)) !== false) {
                    if (substr($file, 0, 12) == 'mod_process_' && substr($file, strlen($file) - 3, 3) == 'php') {
                        $file = substr($file, 0, strlen($file) - 4);

                        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsis_process/" . $file . ".php");

                        $module = new $file;

                        // Show modules according to features level
                        if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
                            continue;
                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
                            continue;

                        $var = !$var;
                        print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
                        print $module->info(false, $process);
                        print '</td>';

                        // Examples
                        print '<td nowrap="nowrap">' . $module->getExample($process) . "</td>\n";

                        print '<td align="center">';

                        if ('mod_process_' . $process->PROCESS_ADDON == $file) {
                            print "<input class='requiredRadio' CHECKED type='radio' name='numref' value='" . substr($file, 12) . "'>";
                        } else {
                            print "<input class='requiredRadio' type='radio' name='numref' value='" . substr($file, 12) . "'>";
                        }
                        print '</td>';
                        print "</tr>\n";
                    }
                }
                closedir($handle);
            }
            print "</table><br>\n";

            print "</div>";
            print "<div id='pdf'>";
            /*
             * Modeles de documents
             */

            print_titre($langs->trans("PDFModules"));

            // Defini tableau def de modele propal
            $def = array();
            $sql = "SELECT nom";
            $sql.= " FROM " . MAIN_DB_PREFIX . "document_model";
            $sql.= " WHERE type = 'process'";
            $resql = $db->query($sql);
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    array_push($def, $obj->nom);
                }
            } else {
                dol_print_error($db);
            }

            $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsis_process/";

            print "<table class=\"noborder\" width=\"100%\">\n";
            print "<tr class=\"liste_titre\">\n";
            print "  <td width=\"140\">" . $langs->trans("Name") . "</td>\n";
            print "  <td>" . $langs->trans("Description") . "</td>\n";
            print '<td align="center" width="60">' . $langs->trans("Activated") . "</td>\n";
            print "</tr>\n";

            clearstatcache();

            $handle = opendir($dir);

            $var = true;
            while (($file = readdir($handle)) !== false) {
                if (substr($file, strlen($file) - 12) == '.modules.php' && substr($file, 0, 12) == 'pdf_process_') {
                    $name = substr($file, 12, strlen($file) - 24);
                    $classname = substr($file, 0, strlen($file) - 12);

                    $var = !$var;
                    print "<tr " . $bc[$var] . ">\n  <td>";
                    print "$name";
                    print "</td>\n  <td>\n";
                    require_once($dir . $file);
                    $module = new $classname($db);
                    print $module->description;
                    print '</td>';
                    // Active
                    if (in_array($name, $def)) {
                        print "<td align=\"center\">\n";
                        if ($process->ADDON_PDF != "$name") {
                            print '<input class="requiredRadio" type="radio" name="pdf" value="' . $name . '">';
                        } else {
                            print '<input class="requiredRadio" CHECKED type="radio" name="pdf" value="' . $name . '" >';
                        }
                        print "</td>";
                    } else {
                        print "<td align=\"center\">\n";
                        print '<a href="#" onClick="activatePdf(\'' . $name . '\');">' . $langs->trans("Activate") . '</a>';
                        print "</td>";
                    }



                    print "</tr>\n";
                }
            }
            closedir($handle);

            print '</table>';
            print '<br>';

            print "</div>";

            print "<div id='rev'>";
            //Modele de revision
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_revision_model ORDER BY nom";
            $sql = $db->query($requete);
            print "<table width=100% cellpadding=10>";
            print "<tr><th class='ui-widget-header ui-state-default'>Nom";
            print "    <th class='ui-widget-header ui-state-default'>Description";
            print "    <th class='ui-widget-header ui-state-default'>Exemple";
            print "    <th class='ui-widget-header ui-state-default'>Action";
            while ($res = $db->fetch_object($sql)) {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/modele/" . $res->phpClass . ".class.php");
                $tmp = $res->phpClass;
                $obj = new $tmp($db);
                print "<tr><th class='ui-widget-header ui-state-hover'>" . $obj->nom;
                print "    <td class='ui-widget-content'>" . $obj->description;
                print "    <td class='ui-widget-content'>[ref]-" . $obj->convert_revision(rand(10, 100));
                print "    <td class='ui-widget-content'>";
                if ($res->id == $process->revision_model_refid) {
                    print "       <a href='processBuilder.php?action=unsetRevisionModel&id=" . $_REQUEST['id'] . "&revision_model=" . $res->id . "'>".img_picto($langs->trans("Activate"), 'switch_on')."</a>";
                }
                else
                    print "       <a href='processBuilder.php?action=setRevisionModel&id=" . $_REQUEST['id'] . "&revision_model=" . $res->id . "'>".img_picto($langs->trans("Activate"), 'switch_off')."</a>";
            }
            print "</table>";

            print "</div>";


            print "<div id='droits'>";
            print '<div id="accordion">';
            print '<h3><a href="#">Utilisateurs</a></h3>';
            print '<div>';
            print "<table cellpadding=10 width=100%>";
            print "<tr><th class='ui-widget-header ui-state-default' title='Nom de l\'utilisateur'>Utilisateur";
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE active=1 ORDER BY rang ";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                print "    <th style='height: 7em' class='ui-widget-header ui-state-default' title='" . $res->description . "'><span class='tb'>" . $res->label . "</span>";
            }

            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                $tmpuser = new User($db);
                $tmpuser->id = $res->rowid;
                $tmpuser->fetch($tmpuser->id);
                $process->getRights($tmpuser);

                print "<tr><td class='ui-widget-content'>" . $tmpuser->getNomUrl(1);
                $process_right_ref = "process" . $process->id;

                $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE active=1 ORDER BY rang ";
                $sql1 = $db->query($requete1);
                while ($res1 = $db->fetch_object($sql1)) {
                    $type = $res1->code;
                    print "    <td class='ui-widget-content' align=center><input type='checkbox' name='u" . $tmpuser->id . "-" . $res1->id . "' " . ($tmpuser->rights->process_user->$process_right_ref && $tmpuser->rights->process_user->$process_right_ref->$type ? "Checked >" : ">");
                }
            }
            print "</table>";
            print '</div>';
            print '<h3><a href="#">Groupes</a></h3>';
            print '<div>';
            print "<table cellpadding=10 width=100%>";
            print "<tr><th class='ui-widget-header ui-state-default' title='Groupe d\'utilisateur'>Groupe";
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE active=1 ORDER BY rang ";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                print "    <th style='height: 7em' class='ui-widget-header ui-state-default' title='" . $res->description . "'><span class='tb'>" . $res->label . "</span>";
            }

            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "usergroup ";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                $tmpgrp = new UserGroup($db);
                $tmpgrp->id = $res->rowid;
                $tmpgrp->fetch($res->rowid);
                $tmpgrp = $process->getGrpRights($tmpgrp);
//var_dump($tmpgrp->rights);
                print "<tr><td class='ui-widget-content'>" . $tmpgrp->nom;
                $process_right_ref = "process" . $process->id;

                $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE active=1 ORDER BY rang ";
                $sql1 = $db->query($requete1);
                while ($res1 = $db->fetch_object($sql1)) {
                    $type = $res1->code;
//                    print "    <td class='ui-widget-content' align=center>".($tmpgrp->rights->process->$process_right_ref && $tmpgrp->rights->process->$process_right_ref->$type?"Oui":"Non");
                    print "    <td class='ui-widget-content' align=center><input type='checkbox' name='g" . $tmpgrp->id . "-" . $res1->id . "' " . ($tmpgrp->rights->process_group->$process_right_ref && $tmpgrp->rights->process_group->$process_right_ref->$type ? "Checked >" : ">");
                }
            }
            print "</table>";
            print '</div>';
            print '</div>';
            //1 liste des utilisateur
            //2 liste des groupes GLE
            //2 droits de voir, de modifier, de valider, de supprimer, de générer un pdf

            print "</div>";

            print "</div>";
            print "<tr><td colspan=4 class='ui-widget-content' align='center'><button id='Modifier' class='butAction'>Modifier</button><button onClick='location.href=\"processBuilder.php?id=" . $process->id . "\"; return false;' class='butAction'>Annuler</button></td>";
            print "</table>";
            print "</div>";
        } else {
            header('location:listProcess.php');
        }
        break;
    case "Delete": {
            if ($id > 0) {
                $process = new process($db);
                $ret = $process->delete($id);
                if ($ret == 1)
                    header('location:listProcess.php');
                else
                    $msg = $process->error;
            }
        }
    default:
        if ($id > 0) {
            $process = new process($db);
            $process->fetch($id);
            $js .= "<script>";
            $js .= "jQuery(document).ready(function(){
                        jQuery('#tabs').tabs({spinner: 'Chargement', cache: true, fx: { opacity:'toggle'}});
                        jQuery('.tb').each(function(){ jQuery(this).rotate('45deg');});
                        jQuery('#accordion').accordion({animated: 'bounceslide',  navigation: true,autoHeight: false, fillSpace: false});
                        jQuery('#delDialog').dialog({
                            autoOpen:false,
                            title:'Supprimer un process',
                            modal: true,
                            buttons:{
                                OK: function(){ location.href='processBuilder.php?action=Delete&id=" . $process->id . "'},
                                Annuler:function(){ jQuery('#delDialog').dialog('close'); }
                            },
                        });
                    });";
            $js .= "</script>";
            
//            $js .= "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery-css-transform.js' type='text/javascript'></script>";
            $js .= "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery-animate-css-rotate-scale.js' type='text/javascript'></script>";
            llxHeader($js, 'Visualiser un process');
            print "<div class='titre'>Visualiser un process</div><br/>";
            if ($msg . "x" != "x") {
                print "<div class='ui-error error'>" . $msg . "</div><br/>";
            }
            print "<table cellpadding=15 width=100%>";
            print "<tr><th class='ui-widget-header ui-state-default'>Nom";
            print "    <td class='ui-widget-content'>" . $process->getNomUrl(1);
            print "    <th class='ui-widget-header ui-state-default'>Statut";
            print "    <td class='ui-widget-content'>" . $process->getLibStatut(5);
            print "<tr><th class='ui-widget-header ui-state-default'>Description";
            print "    <td colspan=3 class='ui-widget-content'>" . $process->description;
            print "<tr><td colspan=4 class='ui-widget-content'>";
            print "<div id='tabs'>";
            print "<ul>";
            print "<li><a href='#global'>Configuration globale</a></li>";
            print "<li><a href='#refnum'>Num&eacute;rotation</a></li>";
            print "<li><a href='#pdf'>Editions PDF</a></li>";
            print "<li><a href='#droits'>Droits</a></li>";
            print "<li><a href='#rev'>R&eacute;visions</a></li>";
            print "</ul>";
            print "<div id='global'>";
            print "<table cellpadding=10 width=100%>";
            print "<tr><th width=150 class='ui-widget-header ui-state-default'>Process Bloquant";
            print "    <td class='ui-widget-content'>" . ($process->bloquant == 1 ? "Oui" : "Non");
            print "<tr><th class='ui-widget-header ui-state-default'>Formulaire du process";
            print "    <td class='ui-widget-content'>" . ($process->formulaire ? $process->formulaire->getNomUrl(1) : "<div class='error'>Pas de formulaire associ&eacute;</div>");
            print "<tr><th class='ui-widget-header ui-state-default'>Type d'&eacute;l&eacute;ment";
            print "    <td class='ui-widget-content'>" . ($process->typeElement ? $process->typeElement->label : "<div class='error'>Pas de type d'&eacute;l&eacute;ment associ&eacute;</div>");
            print "<tr><th class='ui-widget-header ui-state-default'>D&eacute;clancheur";
            print "    <td class='ui-widget-content'>" . ($process->trigger ? $process->trigger->code : "<div class='error'>Pas de type d'&eacute;l&eacute;ment associ&eacute;</div>");

//trigger type

            print "<tr><th class='ui-widget-header ui-state-default'>Pr&eacute;-traitement";
            print "    <td class='ui-widget-content'>" . $process->pretraitement;
            print "<tr><th class='ui-widget-header ui-state-default'>Post-traitement";
            print "    <td class='ui-widget-content'>" . $process->posttraitement;
            print "<tr><th class='ui-widget-header ui-state-default'>Event demande validation";
            print "    <td class='ui-widget-content'>" . $process->askValidAction;
            print "<tr><th class='ui-widget-header ui-state-default'>Event validation";
            print "    <td class='ui-widget-content'>" . $process->validAction;
            print "<tr><th class='ui-widget-header ui-state-default'>Event revision";
            print "    <td class='ui-widget-content'>" . $process->reviseAction;
            print "</table>";
            print "</div>";

            /*
             *  Module numerotation
             */
            print "<div id='refnum'>";

            $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsis_process/";
            $html = new Form($db);
            print "<br>";
            print_titre($langs->trans("Mod&egrave;le de num&eacute;rotation de ce process"));

            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Name") . "</td>\n";
            print '<td>' . $langs->trans("Description") . "</td>\n";
            print '<td nowrap>' . $langs->trans("Example") . "</td>\n";
            print '<td align="center" width="60">' . $langs->trans("Activated") . '</td>';
            print '</tr>' . "\n";

            clearstatcache();

            $handle = opendir($dir);
            if ($handle) {
                $var = true;
                while (($file = readdir($handle)) !== false) {
                    if (substr($file, 0, 12) == 'mod_process_' && substr($file, strlen($file) - 3, 3) == 'php') {
                        $file = substr($file, 0, strlen($file) - 4);

                        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsis_process/" . $file . ".php");

                        $module = new $file;

                        // Show modules according to features level
                        if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
                            continue;
                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
                            continue;

                        $var = !$var;
                        print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
                        print $module->info(1, $process);
                        print '</td>';

                        // Examples
                        print '<td nowrap="nowrap">' . $module->getExample($process) . "</td>\n";

                        print '<td align="center">';
                        if ('mod_process_' . $process->PROCESS_ADDON == "$file") {
                            print img_picto($langs->trans("Activated"), 'switch_on');
                        } else {
                            print $langs->trans("Non");
                        }
                        print '</td>';


                        // Info
//                        $facture->type = 0;
                        $nextval = $module->getNextValue($mysoc, $process);
                        if ("$nextval" != $langs->trans("NotAvailable")) {    // Keep " on nextval
                            $htmltooltip.='<b>' . $langs->trans("NextValue") . '</b>: ';
                            if ($nextval) {
                                $htmltooltip.=$nextval . '<br>';
                            } else {
                                $htmltooltip.=$langs->trans($module->error) . '<br>';
                            }
                        }


                        print "</tr>\n";
                    }
                }
                closedir($handle);
            }
            print "</table><br>\n";

            print "</div>";
            print "<div id='pdf'>";
            /*
             * Modeles de documents
             */

            print_titre($langs->trans("PDFModules"));

            // Defini tableau def de modele propal
            $def = array();
            $sql = "SELECT nom";
            $sql.= " FROM " . MAIN_DB_PREFIX . "document_model";
            $sql.= " WHERE type = 'process'";
            $resql = $db->query($sql);
            if ($resql) {
                while ($res = $db->fetch_object($resql)) {
                    array_push($def, $res->nom);
                }
            } else {
                dol_print_error($db);
            }

            $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsis_process/";

            print "<table class=\"noborder\" width=\"100%\">\n";
            print "<tr class=\"liste_titre\">\n";
            print "  <td width=\"140\">" . $langs->trans("Name") . "</td>\n";
            print "  <td>" . $langs->trans("Description") . "</td>\n";
            print '<td align="center" width="60">' . $langs->trans("Activated") . "</td>\n";
//            print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
            print "</tr>\n";

            clearstatcache();

            $handle = opendir($dir);

            $var = true;
            if ($handle)
                while (($file = readdir($handle)) !== false) {
                    if (substr($file, strlen($file) - 12) == '.modules.php' && substr($file, 0, 12) == 'pdf_process_') {
                        $name = substr($file, 12, strlen($file) - 24);
                        $classname = substr($file, 0, strlen($file) - 12);

                        $var = !$var;
                        print "<tr " . $bc[$var] . ">\n  <td>";
                        print "$name";
                        print "</td>\n  <td>\n";
                        require_once($dir . $file);
                        $module = new $classname($db);
                        print $module->description;
                        print '</td>';

//                    // Active
//                    if (in_array($name, $def))
//                    {
//                        print "<td align=\"center\">\n";
//                        if ($process->ADDON_PDF != "$name")
//                        {
//                            print img_tick($langs->trans("Disable"));
//                        } else {
//                            print img_tick($langs->trans("Enabled"));
//                        }
//                        print "</td>";
//                    } else {
//                        print "<td align=\"center\">\n";
//                        print ''.$langs->trans("Non").'';
//                        print "</td>";
//                    }
                        // Defaut
                        print "<td align=\"center\">";
                        if ($process->ADDON_PDF == "$name") {
                            print img_picto($langs->trans("Activate"), 'switch_on');
                        } else {
                            print img_picto($langs->trans("Default"), 'switch_off');
                        }
                        print '</td>';

                        print "</tr>\n";
                    }
                }
            closedir($handle);

            print '</table>';
            print '<br>';

            print "</div>";

            print "<div id='rev'>";
            //Modele de revision
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_revision_model ORDER BY nom";
            $sql = $db->query($requete);
            print "<table width=100% cellpadding=10>";
            print "<tr><th class='ui-widget-header ui-state-default'>Nom";
            print "    <th class='ui-widget-header ui-state-default'>Description";
            print "    <th class='ui-widget-header ui-state-default'>Exemple";
            print "    <th class='ui-widget-header ui-state-default'>Action";
            while ($res = $db->fetch_object($sql)) {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/modele/" . $res->phpClass . ".class.php");
                $tmp = $res->phpClass;
                $obj = new $tmp($db);
                print "<tr><th class='ui-widget-header ui-state-hover'>" . $obj->nom;
                print "    <td class='ui-widget-content'>" . $obj->description;
                print "    <td class='ui-widget-content'>[ref]-" . $obj->convert_revision(rand(10, 100));
                print "    <td class='ui-widget-content'>";
                if ($res->id == $process->revision_model_refid)
                    print img_picto($langs->trans("Activate"), 'switch_on');
                else
                    print "       Disponible";
            }
            print "</table>";

            print "</div>";

            print "<div id='droits'>";
            print '<div id="accordion">';
            print '<h3><a href="#">Utilisateurs</a></h3>';
            print '<div>';
            print "<table cellpadding=10 width=100%>";
            print "<tr><th class='ui-widget-header ui-state-default' title='Nom de l\'utilisateur'>Utilisateur";
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE active=1 ORDER BY rang ";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                print "    <th style='height: 7em' class='ui-widget-header ui-state-default' title='" . $res->description . "'><span class='tb'>" . $res->label . "</span>";
            }

            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                $tmpuser = new User($db);
                $tmpuser->id = $res->rowid;
                $tmpuser->fetch($tmpuser->id);
                $process->getRights($tmpuser);
                //Group de l'utilisateur
                $objGr = new UserGroup($db);
                $groups = $objGr->listGroupsForUser($tmpuser->id);
                //  var_dump($groups);
                foreach ($groups as $group) {
                    $group = $process->getGrpRights($group);
                    foreach ($tmpuser->rights->process_user as $key => $val) {
                        foreach ($val as $key1 => $val1) {
                            if ($group->rights->process_group->$key->$key1 && !$val1) {
                                $tmpuser->rights->process_user->$key->$key1 = "g";
                            }
                        }
                    }
                }

                print "<tr><td class='ui-widget-content'>" . $tmpuser->getNomUrl(1);
                $process_right_ref = "process" . $process->id;
                $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE active=1 ORDER BY rang ";
                $sql1 = $db->query($requete1);
                while ($res1 = $db->fetch_object($sql1)) {
                    $type = $res1->code;
                    print "    <td class='ui-widget-content' align=center>" . ($tmpuser->rights->process_user->$process_right_ref && $tmpuser->rights->process_user->$process_right_ref->$type ? ($tmpuser->rights->process_user->$process_right_ref->$type == 'g' ? img_picto('H&eacute;rit&eacute;', "tick") : img_picto('Oui', "tick")) : "-");
                }
            }
            print "</table>";
            print '</div>';

            print '<h3><a href="#">Groupes</a></h3>';
            print '<div>';
            print "<table cellpadding=10 width=100%>";
            print "<tr><th class='ui-widget-header ui-state-default' title='Groupe d\'utilisateur'>Groupe";
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE active=1 ORDER BY rang ";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                print "    <th style='height: 7em' class='ui-widget-header ui-state-default' title='" . $res->description . "'><span class='tb'>" . $res->label . "</span>";
            }

            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "usergroup ";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                $tmpgrp = new UserGroup($db);
                $tmpgrp->id = $res->rowid;
                $tmpgrp->fetch($res->rowid);
                $tmpgrp = $process->getGrpRights($tmpgrp);
//var_dump($tmpgrp->rights);
                print "<tr><td class='ui-widget-content'>" . $tmpgrp->nom;
                $process_right_ref = "process" . $process->id;
                $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE active=1 ORDER BY rang ";
                $sql1 = $db->query($requete1);
                while ($res1 = $db->fetch_object($sql1)) {
                    $type = $res1->code;
                    print "    <td class='ui-widget-content' align=center>" . ($tmpgrp->rights->process_group->$process_right_ref && $tmpgrp->rights->process_group->$process_right_ref->$type ? img_picto("Oui", "tick") : "-");
                }
            }
            print "</table>";
            print '</div>';

            print '</div>';
            print "</div>";

            print "</div>";
            print "<tr><td colspan=4 class='ui-widget-content' align='center'><button onClick='location.href=\"processBuilder.php?id=" . $process->id . "&action=Modify\"'  class='butAction'>Modifier</button><button onClick='location.href=\"processBuilder.php?id=" . $process->id . "&action=Clone\"'  class='butAction'>Cloner</button>" . ($process->fk_statut == 0 ? "<button  onClick='location.href=\"processBuilder.php?id=" . $process->id . "&action=activer\"'  class='butAction'>Activer</button>" : "<button  onClick='location.href=\"processBuilder.php?id=" . $process->id . "&action=desactiver\"'  class='butAction'>Desactiver</button>") . "<button onClick='jQuery(\"#delDialog\").dialog(\"open\");' class='butActionDelete'>Supprimer</button></td>";
            print "</table>";
            print "<div id='delDialog'>";
            print "&Ecirc;tes vous sur de vouloir supprimer ce process ?";
            print "</div>";

            llxFooter('$Date: 2011/01/06 19:20:02 $ - $Revision: GLE 1.2 $');
        } else {
            header('location:listProcess.php');
        }
        break;
}

llxFooter('$Date: 2011/01/06 19:20:02 $ - $Revision: GLE 1.2 $');
?>

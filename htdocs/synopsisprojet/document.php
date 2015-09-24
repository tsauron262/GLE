<?php

/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Create on : 4-1-2009
  * Version 1.1
  *
  * Infos on http://www.finapro.fr
  *
  */
/*
 */
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/**
        \file       htdocs/comm/synopsisprojet/document.php
        \ingroup    projete
        \brief      Page de gestion des documents rattache a un projet
        \version    $Id: document.php,v 1.43 2008/07/10 17:11:05 eldy Exp $
*/



//TODO: ajouter un lien vers une tache
//ajouter un modal:>  lien du document à ue tache, à un % de completion de la tache, à une liste d'adresse mail(recherhe zimbra+doli contact) + acces direct pour les membre du projet
//                    + configuration :> envoie des doc en PJ ou lien interne / externe (zimbra) + template du mail


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/core/lib/synopsis_project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
$projetid='';


if ($_GET["id"]) { $projetid=$_GET["id"]; }

// If socid provided by ajax company selector


if ($projetid == '' && ($_GET['action'] != "create" && $_POST['action'] != "add" && $_POST["action"] != "update" && !$_POST["cancel"])) accessforbidden();

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'synopsisprojet', $projetid, 'Synopsis_projet_view');

// Get parameters
$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


$projetConf = $conf->synopsisprojet;




if ($_REQUEST['SynAction'] == 'dlZip')
{
    require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
    $zipFilename =  tempnam("/tmp", "zipping-dolibarr-prop-");
    unlink($zipFilename);
    $zipFilename .= ".zip";
    $societe = new Societe($db);
    $projet = new SynopsisProject($db);

    if ($projet->fetch($projetid))
    {
        $upload_dir = $projetConf->dir_output.'/'.sanitize_string($projet->ref);
        if ($societe->fetch($projet->socid))
        {
            $finalFileName = "doc_projet_".sanitize_string($projet->ref)."_".sanitize_string($societe->nom) ."-". date("Ymd-Hi", time()).".zip";
            $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
            $zip = new ZipArchive();
            if ( $zip->open($zipFilename,ZIPARCHIVE::CREATE) === TRUE)
            {
                $zip->setArchiveComment('Generate by GLE - Synopsis et DRSI');
                foreach($filearray as $key=>$val)
                {
                    //Add files
                     $zip->addFile($val['fullname'], "".sanitize_string($societe->nom).'/'.$val['name']);
                }
            $zip->close();
            }
        }
        if (is_file($zipFilename) &&filesize($zipFilename))
        {
              header("content-type: application/zip");
              header("Content-Disposition: attachment; filename=".$finalFileName);
              print file_get_contents($zipFilename);

              unlink ($zipFilename);
        } else {
            print "Erreur dans la cr&eacute;tion du fichier Zip";
        }
    }
}

if ($_REQUEST['SynAction'] == 'dlZipByGrp')
{
    require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
    $zipFilename =  tempnam("/tmp", "zipping-dolibarr-proj-");
    unlink($zipFilename);
    $zipFilename .= ".zip";
    $societe = new Societe($db);
    $projet = new SynopsisProject($db);

    if ($projet->fetch($projetid))
    {
        $upload_dir = $projetConf->dir_output.'/'.sanitize_string($proj->ref);
        if ($societe->fetch($projet->socid))
        {
            $finalFileName = "doc_projet_".sanitize_string($projet->ref)."_".sanitize_string($societe->nom) ."-". date("Ymd-Hi", time()).".zip";
            //$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc, ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group WHERE fk_document = ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.rowid AND fk_group = ".$_REQUEST['groupId'];
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql))
            {
                $filearray[] = array('fullname' => $dolibarr_main_data_root."/".$res->fullpath_dol , 'name' => $res->filename);
            }
            $zip = new ZipArchive();
            if ( $zip->open($zipFilename,ZIPARCHIVE::CREATE) === TRUE)
            {
                $zip->setArchiveComment('Generate by GLE - Synopsis et DRSI');
                foreach($filearray as $key=>$val)
                {
                    //Add files
                     $zip->addFile($val['fullname'], "".sanitize_string($societe->nom).'/'.$val['name']);
                }
            $zip->close();
            }
        }
        if (is_file($zipFilename) &&filesize($zipFilename))
        {
              header("content-type: application/zip");
              header("Content-Disposition: attachment; filename=".$finalFileName);
              print file_get_contents($zipFilename);

              unlink ($zipFilename);
              exit();
        } else {
            print "Erreur dans la cr&eacute;tion du fichier Zip";
            exit();
        }
    }
}



/*
 * Actions
 */
// Envoi fichier
if ($_POST["sendit"] && isset($projetConf->dir_output))
{
    $projet = new SynopsisProject($db);
    $projet->fetch($projetid);
    $upload_dir = $projetConf->dir_output . "/" . sanitize_string($projet->ref);

    
    
    if (dol_mkdir($upload_dir) >= 0) {
        $resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . dol_unescapefile($_FILES['userfile']['name']), 0, 0, $_FILES['userfile']['error']);
        if (is_numeric($resupload) && $resupload > 0) {
            if (image_format_supported($upload_dir . "/" . $_FILES['userfile']['name']) == 1) {
                // Create small thumbs for image (Ratio is near 16/9)
                // Used on logon for example
                $imgThumbSmall = vignette($upload_dir . "/" . $_FILES['userfile']['name'], $maxwidthsmall, $maxheightsmall, '_small', $quality, "thumbs");
                // Create mini thumbs for image (Ratio is near 16/9)
                // Used on menu or for setup page for example
                $imgThumbMini = vignette($upload_dir . "/" . $_FILES['userfile']['name'], $maxwidthmini, $maxheightmini, '_mini', $quality, "thumbs");
            }
            $mesg = '<div class="ok">' . $langs->trans("FileTransferComplete") . '</div>';
        } else {
            $langs->load("errors");
            if ($resupload < 0) { // Unknown error
                $mesg = '<div class="error">' . $langs->trans("ErrorFileNotUploaded") . '</div>';
            } else if (preg_match('/ErrorFileIsInfectedWithAVirus/', $resupload)) { // Files infected by a virus
                $mesg = '<div class="error">' . $langs->trans("ErrorFileIsInfectedWithAVirus") . '</div>';
            } else { // Known error
                $mesg = '<div class="error">' . $langs->trans($resupload) . '</div>';
            }
        }
    }
    /*if ($projet->fetch($projetid))
    {
        $upload_dir = $projetConf->dir_output . "/" . sanitize_string($projet->ref);
        if (! is_dir($upload_dir)) 
            dol_mkdir($upload_dir);
        if (is_dir($upload_dir))
        {
            $tmpName = $_FILES['userfile']['name'];
            //decode decimal HTML entities added by web browser
            $tmpName = dol_unescapefile($tmpName );

            if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $tmpName,0) > 0)
            {
                $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';

                //ajoute dans ".MAIN_DB_PREFIX."Synopsis_projet_document


                //add file to ecm
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($db);
                $interface->texte=$tmpName;
                $result=$interface->run_triggers('ECM_UL_PROJET',$projet,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
//                require_once(DOL_DOCUMENT_ROOT . "/ecm/ecmdirectory.class.php" );
//                $ecm = new EcmDirectory($db);
//                $ecm->create_assoc("projet",$projet, $_FILES['userfile']['name'],$user,$conf);
                //print_r($_FILES);
            }
            else
            {
                // Echec transfert (fichier d�passant la limite ?)
                $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
                // print_r($_FILES);
            }
        }
    }*/
}

// Delete
if ($_REQUEST['action'] =='delete')
{
    $projet = new SynopsisProject($db);

    $projetid=$_GET["id"];
    if ($projet->fetch($projetid))
    {
        $upload_dir = $projetConf->dir_output . "/" . sanitize_string($projet->ref);
        $file = $upload_dir . '/' . urldecode($_GET['urlfile']);
        dol_delete_file($file);
        $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $result=$interface->run_triggers('ECM_UL_DEL_PROJET',$projet,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
    }
}


/*
 * Affichage
 */

$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = "<script type='text/javascript' src='".$jspath."/jquery.treeview.min.js'></script>";

$header .= "<style>";
$header .= <<<EOF

    .ui-accordion-contentDoc { max-height: 270px; min-height: 250px; height: 270px; overflow-x: none; z-index: 2; }
    .ui-accordion .ui-accordion-content { max-height: 270px; min-height: 250px; height: 270px;}
</style>
EOF;

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";
$header .= '<link rel="stylesheet" href="'.$csspath.'jquery.treeview.css" type="text/css" />'."\n";
$header .= '<link rel="stylesheet" href="'.$csspath.'jquery.autocomplete.css" type="text/css" />'."\n";

$header .= "<style>";
$header .= <<<EOF

    .notdraggable{ font-style: italic; color: #515151; cursor: no-drop; }
    input#userfile { color: #000000 !important; }

    .ac_results { z-Index: 10000000}

    #projetTree li,#projetTree span,#projetTree a { white-space: nowrap; font-size: 11px; }

    .ui-accordion-contentDoc { max-height: 250px; min-height: 250px; height: 250px;}
    .ui-accordion .ui-accordion-content { max-height: 250px; min-height: 250px; height: 250px;}
    .draggable.custom-state-active { background: #eee; }
    .draggable li { border: 0;  width: 96px; padding: 0; margin: 0; padding-left: 13pt; text-align: center; padding-top: 3px;padding-bottom: 0px;  margin-top: -5px; white-space: nowrap;  }
    .draggable { cursor: all-scroll; }
    .draggable div {white-space: nowrap;}
    .draggable li h5 { margin: 0 0 0.4em; cursor: move; text-transform: capitalize; -moz-border-radius-topright: 8px; -webkit-border-top-right-radius: 8px; }
    .draggable li a { float: right; }
    .draggable li span { float: right; }
    .draggable li a.ui-icon-zoomin { float: left; }
    .draggable li img { width: 100%; cursor: move; }
    .leftpart { top:0; left: 0; padding: 5pt; height: 100%;  width: 190px;  z-Index: 6000; background-color: #FFFFFF; }
    .droppableupper { left: 280px; }

    .ui-draggable-dragging { z-index: 400; position : absolute;  }

    .droppable { z-index: 2; float: left; width: 280px; min-height: 350px; background-color: #CCCCCC; background-image: none; overflow-y: auto; pointer: all-scroll;}
    .droppable h4 { line-height: 16px; margin: 0 0 0.4em;  }
    .droppable .ui-icon { float: left; }
    .droppable div { max-width: 298px; overflow-x: auto;}
    .droppable ul { margin-left: 10px;  background: transparent; }
    .droppable li h5 { margin: 0 0 0.4em; cursor: move; text-transform: capitalize; -moz-border-radius-topright: 8px; -webkit-border-top-right-radius: 8px; }
    .droppable .draggable h5 { display: none; }
    .droppable li { border: 0;  width: 96px; padding: 0; margin: 0; padding-left: 13pt; text-align: center; padding-top: 3px;padding-bottom: 0px;  margin-top: -5px;  }

    .ui-icon { cursor: pointer; }

       .droppabledraggable{ cursor: all-scroll; }

    #treeDoc { vertical-align: top; margin-top:0; padding-top:0; max-height: 100%; overflow-y: auto;}
    .head { width:100%; }
    .ui-pg-button { height: 22px;}

    .dlGrpZip { cursor: pointer; }



EOF;
$header .= "</style>";


$header .= '<script language="javascript"  src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.validate.min.js"></script>'."\n";
$header .= "<script type='text/javascript'>";
$header .= "var projId = $projetid;";
$header .= "var userId = ".$user->id.";";
$header .= <<<EOF
var currentId = "";
var currentIdAct = "";
jQuery(document).ready(function(){
    jQuery('#tabs').tabs({
        spinner: 'Chargement ...',
        cache: true,
        fx: { opacity: 'toggle' }
    });
    jQuery("#treeDocs").treeview({
        collapsed: true,
        animated: "slow",
        prerendered: true,
    });

    jQuery(".actionaccordion").accordion({
        animated: 'slide',
        clearStyle: true  ,
        height: 400,
        navigation: true,
        collapsible: true,
        change: function(event,ui){
            if (ui && ui.newHeader &&  ui.newHeader.attr('id'))
            {
                var id = ui.newHeader.attr('id').replace(/^[a-zA-Z]*/,"");
                currentIdAct = id; //global
                jQuery("#treeAction").parent().css({display: "block"});
                    jQuery('.slider').slider({ min: 0, animate: true , range: "min", step: 5, change: function(e,u){
                        jQuery(this).parent().parent().find('td:last').find('#percTaskStr').text(u.value);
                        jQuery(this).parent().parent().find('input').val(u.value);
                    }});
                    jQuery('.projecttree').treeview({
                        prerendered: true,
                        animated: "slow",
                    });

                //reset draggable
            } else {
                currentIdAct = ''; //global
                jQuery("#treeAction").parent().css({display: "none"});
            }
        },
        active: "false" ,
    });




    jQuery(".docaccordion").accordion({
        animated: 'slide',
        clearStyle: true  ,
        height: 400,
        navigation: true,
        collapsible: true,
        change: function(event,ui){
            if (ui && ui.newHeader &&  ui.newHeader.attr('id'))
            {
                var id = ui.newHeader.attr('id').replace(/^[a-zA-Z]*/,"");
                currentId = id; //global
                jQuery("#treeDocs").parent().css({display: "block"});
                    getDocs();
                    initDetail();

                //reset draggable
            } else {
                currentId = ''; //global
                jQuery("#treeDocs").parent().css({display: "none"});
            }
        },
        active: "false" ,
    });
    jQuery('#add_cat').click(function(){
        jQuery('#addDiag').dialog('open');
    });
    jQuery('#mod_cat').click(function(){
        jQuery('#modDiag').dialog('open');
    });
    jQuery('#del_cat').click(function(){
        jQuery('#delDiag').dialog('open');
    });
    jQuery('#addDiag').dialog({
        autoOpen: false,
        buttons: {
            Ok: function(){
                post = "projId="+projId;
                var name = jQuery("#addDocName").val();
                post += "&name="+name;
                post += "&oper=add";
                if (jQuery("#addDialogForm").validate({
                                rules: { addDocName: {
                                            required: true,
                                            minlength: 5,
                                        },
                                      },
                            messages: {
                                        addDocName: {
                                            required : "<br>Champs requis",
                                            minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                        },
                                    }
                            }).form())
                            {
                                jQuery.ajax({
                                        async: false,
                                        url: "ajax/doc_group-ajax.php",
                                        type: "POST",
                                        data: post,
                                        success: function(msg){
                                            if (jQuery(msg).find('ok').text()+"x" != "x")
                                            {
                                                location.reload();
                                            } else {
                                                alert (jQuery(msg).find('ko').text());
                                            }
                                        }
                                    });
                            }            }
        },
        modal: true,
        zIndex: 1000006,
        title: 'Ajouter un groupe',
    });
    jQuery('#modDiag').dialog({
        autoOpen: false,
    });
    jQuery('#delDiag').dialog({
        autoOpen: false,
    });

    var ZimId="";
    var GLEId="";

    jQuery('#mailDialog').dialog({
            autoOpen: false,
            minHeight: 400,
            minWidth: 530,
            width: '80%',
            open:function(e,u){
                var tmp = "<div id='MailTabs'>";
                    tmp += "<ul>";
                    tmp += "  <li><a href='#MailFragment-1'><span>Projet</span></a></li>";
                    tmp += "  <li><a href='#MailFragment-2'><span>Global</span></a></li>";
                    tmp += "  <li><a href='#MailFragment-3'><span>Mon Zimbra</span></a></li>";
                    tmp += "  <li><a href='#MailFragment-4'><span>Autres</span></a></li>";
                    tmp += "</ul>";
                    tmp += "<div id='MailFragment-1'>";
EOF;
print "\n";
$requete = "SELECT DISTINCT ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user
                       FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                            ".MAIN_DB_PREFIX."projet_task,
                            ".MAIN_DB_PREFIX."user
                      WHERE ".MAIN_DB_PREFIX."projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
                        AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user = ".MAIN_DB_PREFIX."user.rowid
                        AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.type = 'user'
                        AND fk_projet = ".$projetid."
                   ORDER BY ".MAIN_DB_PREFIX."user.lastname, ".MAIN_DB_PREFIX."user.firstname";
$sql = $db->query($requete);
$tableInProj = "<div style=\'max-height: 300px; overflow-y: auto;\'><table width=100%><tbody>";
while ($res = $db->fetch_object($sql))
{
    $tmpUser = new User($db);
    $tmpUser->fetch($res->fk_user);
    $tableInProj .= "<tr><td>".$tmpUser->getNomUrl(1)."</td><td>".$tmpUser->email."</td><td>"./*img_GLE('Ajouter',"plus.gif").*/"</td></tr>";
}
$tableInProj .= "</tbody></table></div>";
$header .= " tmp += '".$tableInProj."'; "."\n";

$header .= <<< EOF
                    tmp += "</div>";
                    tmp += "<div id='MailFragment-2'>";
//".MAIN_DB_PREFIX."contact ou ".MAIN_DB_PREFIX."user
                    tmp += "<FieldSet>";
                    tmp += "<legend style='padding: 0 10px 0 10px; margin: 0 10px 0 10px; '>Recherche dans GLE</legend><br/>";
                    tmp += "   <center><p> Veuillez saisir les premi&egrave;res lettres du nom/pr&eacute;nom/email de la personne recherch&eacute;e  </p><br/><br/>";
                    tmp += "   <input type='text' class='gleAutoComplete'><span class='butAction'>D&eacute;tails</span><span class='butAction'>Ajouter</span></center><br/><br/>";
                    tmp += "</FieldSet>";

                    tmp += "</div>";
                    tmp += "<div id='MailFragment-3'>";
                    tmp += "<FieldSet>";
                    tmp += "<legend style='padding: 0 10px 0 10px; margin: 0 10px 0 10px; '>Recherche dans mon Zimbra</legend><br/>";
                    tmp += "   <center><p> Veuillez saisir les premi&egrave;res lettres du nom/pr&eacute;nom/email de la personne recherch&eacute;e  </p><br/><br/>";
                    tmp += "   <input type='text' class='zimAutoComplete'><span class='butAction'>D&eacute;tails</span><span class='butAction'>Ajouter</span></center><br/><br/>";
                    tmp += "</FieldSet>";

EOF;

	// Files list constructor
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


$header .= <<< EOF
                    tmp += "</div>";
                    tmp += "<div id='MailFragment-4'>";
//Autre
                    tmp += "<FieldSet>";
                    tmp += "<legend style='padding: 0 10px 0 10px; margin: 0 10px 0 10px; '>Autres contacts</legend><br/>";
                    tmp += "   <center><br/><br/>";
                    tmp += "   <table>";
                    tmp += "     <tr><td>Destinataire :</td><td><input id='' type='text'></td></tr>";
                    tmp += "     <tr><td>Email : </td><td><input id='' type='text'></td></tr>";
                    tmp += "     <tr><td rowspan=2><span class='butAction'>Ajouter</span></center><br/><br/></td></tr>";
                    tmp += "   </table>";
                    tmp += "</FieldSet>";

                    tmp += "</div>";


                    tmp += " </div>";
                var longHtml = "<div>"+tmp+"<table width=100%><tr><td>User:</td><td><table width=100%><tr><td>Toto</td></tr><tr><td>Tutu</td></tr></table></td></tr></table></div>";
                jQuery('#mailDialogForm').append(longHtml);
                jQuery('#mailDialogForm').find('#MailTabs').tabs({ cache: true, spinner: 'Chargement ...', fx: { opacity: 'toggle' }});
                jQuery('.zimAutoComplete').autocomplete("ajax/doc_zimbra_contact.php?userid="+userId,{
                    minChars: 3,
                    width: 260,
                    selectFirst: false,
                    formatItem: function(row) { return row[1]; },
                    formatResult: function(data, value) {
                        var id=data[2];
                       return data[1];
                    },
                    modifAutocompleteSynopsisReturnSelId: function(selected)
                    {
                        ZimId = selected.data[2];
                    }
                });
                jQuery('.gleAutoComplete').autocomplete("ajax/doc_gle_contact.php?userid="+userId,{
                    minChars: 3,
                    width: 260,
                    selectFirst: false,
                    formatItem: function(row) { return row[1]; },
                    formatResult: function(data, value) {
                        var id=data[2];
                       return data[1];
                    },
                    modifAutocompleteSynopsisReturnSelId: function(selected)
                    {
                        GLEId = selected.data[2];
                    }
                });
            },
            buttons: {
                Ok: function(){
                }
            },
            modal: true,
            zIndex: 1000006,
            title: 'Gestion des destinataires',

    });
    jQuery('.dlGrpZip').click(function(){
EOF;
        $header .= 'jQuery.download("document.php?id='.$projetid.'", "groupId="+currentId+"&SynAction=dlZipByGrp","POST" );';
$header .= <<< EOF
    });

    initDnD();
    initDetail();
});
jQuery.download = function(url, data, method){
    //url and data options required
    if( url && data ){
        //data can be string of parameters or array/object
        data = typeof data == 'string' ? data : jQuery.param(data);
        //split params into form inputs
        var inputs = '';
        jQuery.each(data.split('&'), function(){
            var pair = this.split('=');
            inputs+='<input type="hidden" name="'+ pair[0] +'" value="'+ pair[1] +'" />';
        });
        //send request
        jQuery('<form action="'+ url +'" method="'+ (method||'post') +'">'+inputs+'</form>')
        .appendTo('body').submit().remove();
    };
};


    var recycle_icon = '<span style="margin-right: 15px; margin-top: -3px; margin-left: 5px;" title="Supprimer du groupe" class="ui-icon ui-icon-trash">Supprimer du groupe</span>';
    function addToDocGroup(item,droppableobj,ev,ui) {
        var dropI= droppableobj;
//        var $list = jQuery('ul',$dropI).length ? jQuery('ul',$dropI) : jQuery('<ul class="gallery ui-helper-reset"/>').appendTo($dropI);
        var list = jQuery('#droppable'+currentId);
        var text = item.text();
        var id = item.attr('id');
//send to ajax

//redraw
        initDnD();
        //count ++
        //send to ajax

        var post = "oper=addToGroup";
            post += "&docId="+id;
            post += "&groupId="+currentId;
        jQuery.ajax({
            async: false,
            url: "ajax/doc_group-ajax.php",
            type: "POST",
            data: post,
            success: function(msg){
                if (jQuery(msg).find('ok').text()+"x" != "x")
                {
                    item = jQuery('<div style=" width: 100px; height: 36px;  " class="droppabledraggable ui-widget-header ui-state-default" id='+id+'><h5 style="margin-top: 4px; margin-bottom:0px;">'+recycle_icon+text+'</h5></div>');
                    item.appendTo(list).fadeIn(function() {
                        item.find('h5').animate({ height: '12px', marginTop: '2px' }).parent().animate({ width: "296px", marginTop: "-1px" }).animate({ height: '20px'});
                    });
                    initDetail();
                    //reset List Display ?
                    jQuery('#treeDocs').find('#'+id).removeClass('draggable').removeClass('ui-draggable').removeClass('ui-droppable');
                    jQuery('#treeDocs').find('#'+id).addClass('notdraggable');
                    initDnD();
                    //alert (jQuery(msg).find('ok').text());
                } else {
                    //alert (jQuery(msg).find('ko').text());
                }
            }
        });
    }
function selTask(pId,obj)
{
    //TODO liaison doc group , task et liste user
}
function getDocs()
{
    var post = "oper=getDocs";
            post += "&groupId="+currentId;
        jQuery.ajax({
            async: false,
            url: "ajax/doc_group-ajax.php",
            type: "POST",
            data: post,
            success: function(msg){
                var list = jQuery('#droppable'+currentId);

                    list.find('.droppabledraggable').remove();
                    jQuery('#treeDocs').find('li').each(function(){
                        if(jQuery(this).attr('id') && jQuery(this).attr('id').length > 0 && jQuery(this).attr('id').match(/[0-9]*/))
                        {
                            jQuery(this).removeClass('notdraggable');
                            jQuery(this).addClass('draggable ui-draggable ui-droppable');
                        }
                    });

	// Company
//	print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
//	if (! empty($object->societe->id)) print $object->societe->getNomUrl(1);
//	else print '&nbsp;';
//	print '</td></tr>';

                    var item = jQuery('<div style=" width: 100px; height: 36px;  " class="droppabledraggable ui-widget-header ui-state-default" id='+id+'><h5 style="margin-bottom:0px; margin-top: 4px;">'+recycle_icon+text+'</h5></div>');
                        item.appendTo(list).fadeIn(function() {
                            item.find('h5').animate({ height: '12px', marginTop: '2px' }).parent().animate({ width: "296px", marginTop: "-1px" }).animate({ height: '20px'});
                        });
                    jQuery('#treeDocs').find('#'+id).removeClass('draggable').removeClass('ui-draggable').removeClass('ui-droppable');
                    jQuery('#treeDocs').find('#'+id).addClass('notdraggable');
//                });
                initDnD();
            }
        });
}

function initDetail ()
{
    var post = "oper=getDetail";
        post += "&groupId="+currentId;
        jQuery.ajax({
            async: false,
            url: "ajax/doc_group-ajax.php",
            type: "POST",
            data: post,
            success: function(msg){
                var count = jQuery(msg).find('count').text();
                var tailleTot = jQuery(msg).find('totSize').text();
                jQuery('#count'+currentId).text(count);
                jQuery('#totSize'+currentId).text(tailleTot);
            }
        });
}
function remFromDocGroup(draggable,helper,ui)
{
    //reset la liste dns la box
    //reset la list de details a droite
        var id = draggable.attr('id');

    var post = "oper=remFromGroup";
        post += "&groupId="+currentId;
        post += "&docId="+id;

    jQuery.ajax({
        async: false,
        url: "ajax/doc_group-ajax.php",
        type: "POST",
        data: post,
        success: function(msg){
            if (jQuery(msg).find('ok').text()+"x" != "x")
            {
                draggable.fadeOut(function() {
                    draggable.remove();
                });
                initDetail();
                //
                jQuery('#treeDocs').find('#'+id).addClass('draggable').addClass('ui-draggable').addClass('ui-droppable');
                jQuery('#treeDocs').find('#'+id).removeClass('notdraggable');
                initDnD();
            } else {

            }
        }
    });

}
    // let the gallery items be draggable
    function initDnD()
    {
       jQuery('.ui-icon-trash').click(function(){
            remFromDocGroup(jQuery(this).parent().parent());
       });
       jQuery(".notdraggable").draggable( 'disable' );
EOF;

//TODO
//fichier a virer  dans detail :> nbr de fichier
//Pour les droits , si droit de modifier les groupes de document => cacher Doc du projet
// deco filetree + vert +fiedset

        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_document_group
                     WHERE fk_projet = ".$projetid;
        $sql1 = $db->query($requete);
        if ($db->num_rows($sql1)> 0)
        {
            while ($res=$db->fetch_object($sql1))
            {
                // let the trash be droppable, accepting the gallery items
                $header .= 'jQuery("#droppable'.$res->id.'").droppable({';
                    $header .= "accept: '.draggable',";
                    $header .= "activeClass: 'ui-state-highlight',";
                    $header .= "drop: function(ev, ui) {";
                    $header .= '             addToDocGroup(ui.draggable,this,ev,ui); ';
               $header .= " }";
               $header .= "});";
            }
        }

$header .= <<<EOF

       jQuery('.draggable').draggable({
                    cancel: 'a.ui-icon',// clicking an icon won't initiate dragging
                    revert: 'invalid', // when not dropped, the item will revert back to its initial position
                    containment: 'document', // stick to demo-frame if present
                    helper: 'clone',
                    cursor: 'move',
                    iframefix: true,
                    zIndex: 100000,
                    refreshPositions: true,
                    distance: 10,
                });
       jQuery(".draggable").draggable( 'enable' );

        // let the trash items be draggable
//       jQuery(".droppabledraggable").draggable({
//                    cancel: 'a.ui-icon',// clicking an icon won't initiate dragging
//                    revert: 'invalid', // when not dropped, the item will revert back to its initial position
//                    containment: 'document',
//                    helper: 'clone',
//                    cursor: 'move',
//                    iframefix: true,
//                    zIndex: 100010,
//                    refreshPositions: true,
//                    distance: 10,
//                });

        // let the gallery be droppable as well, accepting items from the trash
        jQuery('#treeDocs').droppable({
            accept: '.droppable div',
            activeClass: 'custom-state-active',
            drop: function(ev, ui) {
                remFromDocGroup(ui.draggable,ui.helper,ui);
            }
        });
    }


//Refaireenutilisant l'espace à droite comme dans doc Group
function displayCondAction(obj)
{
    //console.log(jQuery(obj).parent().parent().find('.condDisplay'));
    if (obj.checked)
    {
        jQuery(obj).parent().parent().parent().find('.condDisplay').slideDown("slow");
         jQuery(obj).parent().parent().parent().parent().parent().parent().find('.condDisplayVert').find('#projetTreeDiv').animate({ width: "226px", opacity: 1,display: "block"});
        //console.log(jQuery(obj).parent().parent().parent().parent().parent().parent().find('.condDisplayVert').find('#projetTreeDiv').text());

    } else {
        jQuery(obj).parent().parent().parent().find('.condDisplay').slideUp("slow");
        jQuery(obj).parent().parent().parent().parent().parent().parent().parent().find('.condDisplayVert').find('#projetTreeDiv').animate({ width: 0, opacity: 0, display: "none"});
        //console.log(jQuery(obj).parent().parent().parent().parent().parent().parent().find('.condDisplayVert').find('#projetTreeDiv').text());
    }



}

function editMailTo(obj)
{
    jQuery('#mailDialog').dialog('open');
}

</script>
EOF;

llxHeader($header,"Projet - Document","",1);

if ($projetid > 0)
{
    $projet = new SynopsisProject($db);
    if ($projet->fetch($projetid))
    {

        $projet = new SynopsisProject($db);
        $projet->fetch($_GET["id"]);
        $projet->societe->fetch($projet->societe->id);

        $head=synopsis_project_prepare_head($projet);
        dol_fiche_head($head, 'Document', $langs->trans("Project"));


        //2 tabs
        print '<div id="tabs">';
        print '    <ul>';
        print '        <li><a href="#fragment-1"><span>Documents</span></a></li>';
        print '        <li><a href="#fragment-2"><span>Actions</span></a></li>';
        print '        <li><a href="#fragment-3"><span>Groupes</span></a></li>';
        print '    </ul>';
        print '    <div id="fragment-3">';
        print '    <table width=100% style="border-collapse: collapse;"><tr>';
        print '<td  valign=top  width=650>';
        //print '      TODO col gauche : par fichier col centre groupes + ajouter en bas par drag and drop<br/>';
        print '    </div>';
        print '     <div style="float : left; width: 630px; height: 475px;" class="ui-corner-top ui-widget ui-widget-content " >';
        print '     <div class="ui-widget-header ui-state-default ui-corner-top" style="padding: 2px 0px 4px 4px;">Groupe</div>';
        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_document_group
                     WHERE fk_projet = ".$projetid;
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0)
        {
            print '       <div class="docaccordion">';
            while ($res = $db->fetch_object($requete))
            {
                print '        <div  id="grpdID'.$res->id.'" class="accordion-header ui-widget ui-widget-header" style="min-width: 630px;">';
                print '          <a href="#">';
                print '           <span  id="grpdSID'.$res->id.'"  class="head accordion-header" style="text-transform: capitalize;" >' . html_entity_decode($res->nom) . '</span>';
                print           '</a>';
                print '           </div>';
                print '           <div style="width:560px; padding-bottom: 5pt; padding-top: 5pt; " class="ui-accordion-contentDoc" >';
                print '             <h4 class="ui-widget-header ui-corner-top ui-widget-header" style="width: 286px; margin-bottom: 0px; padding: 5pt;"><span style="height: 16px; float: left; " class="ui-icon ui-icon-transferthick-e-w"></span><span style="margin-left: 10pt; text-transform: capitalize">' . html_entity_decode($res->nom). '</span></h4>';
                print '             <div  class="ui-state-default ui-widget-content droppable" id="droppable'.$res->id.'" style=" float: left; margin-right: 40px;  min-width: 120px; width: 298px;  min-height: 100px;height: 190px; margin-top: 0px; padding-top: 0px; overflow-y: auto; ">';
                print '             </div>'; // fin droppable
                print '             <div style="float: left"><fieldset style="padding: 10px;"><legend>D&eacute;tail</legend><table><tr><td>Nb. de fichier</td><td align=right><span id="count'.$res->id.'">0</span></td></tr><tr><td>Volume</td><td  align=right><span id="totSize'.$res->id.'">0</span></td></tr></table></fieldset><br><br>
                                        <button class="dlGrpZip ui-widget ui-state-default ui-corner-all" style="padding: 5px 10px;" id="dlGrpZip'.$res->id.'">
                                            <span style="float: left; margin-right: 5px;" class="ui-icon ui-icon-copy"></span>
                                            <span style="float: left;">T&eacute;l&eacute;charger tous</span></button></div>';
                print '           </div>';
            }


            print '       </div>'; // fin accordion

        } else {
            print '        <div style="margin-left: 20%; margin-top: 30%;  padding: 20px; width: 150px; " class="ui-state-default ui-error">Pas de groupe d&eacute;fini</div>';

        }
        print '     </div>'; // fin de float div

        print '</td>';

            print '</div>';
        print '</div>';
        print '<td valign=top>';
        print '    <div style="display: none;width: 275px; max-width: 275px; overflow: auto; min-height: 450px;  margin: 3px;  border: 1px Solid #DDDDDD; margin-top: 0;  " class="ui-corner-all">';
//        print '      TODO Grouper les documents dans des groupes 1 documents pouvant être dans plusieurs groupe<br/>';
        //Treeview par lettre de debut de fichier
        $requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.rowid as id , ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.filename
                      FROM ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc,
                           ".MAIN_DB_PREFIX."Synopsis_ecm_document_auto_categorie,
                           ".MAIN_DB_PREFIX."Synopsis_li_ecm_element_assoc
                     WHERE ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.categorie_refid = ".MAIN_DB_PREFIX."Synopsis_ecm_document_auto_categorie.id
                       AND ".MAIN_DB_PREFIX."Synopsis_ecm_document_auto_categorie.idStr = 'projet'
                       AND ".MAIN_DB_PREFIX."Synopsis_li_ecm_element_assoc.category_refid = ".MAIN_DB_PREFIX."Synopsis_ecm_document_auto_categorie.id
                       AND ".MAIN_DB_PREFIX."Synopsis_li_ecm_element_assoc.ecm_assoc_refid = ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.rowid
                       AND ".MAIN_DB_PREFIX."Synopsis_li_ecm_element_assoc.element_refid = ".$projet->id."
                  ORDER BY ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.filename";
        $sql = $db->query($requete);
        print "<div class='ui-widget-header ui-state-default ui-corner-top' style='padding: 2px 0px 4px 4px; font-size: 12pt;'>Doc. du projet</div>";
        print '<ul id="treeDocs" class="treeview ui-corner-all" >';


        $remChar = "";
        $arrList = array();
        $arrList['Autres'] = array();
        
        while ($res = $db->fetch_object($sql))
        {
            $firstChar = strtoupper($res->filename[0]);
            if (!preg_match('/[a-zA-Z]/',$firstChar))
            {
                $firstChar="Autres";
            }
            $arrList[$firstChar][]=array('id' => $res->id, 'nom' => $res->filename);
        }
        //require_once('Var_Dump.php');
        //Var_Dump::Display($arrList);
        $arrAlpha = array('ABC','DEF','GHI','JKL','MNO','PQRS','TUV','WXYZ');
        $bool=false;
        foreach($arrAlpha as $key)
        {
            if ($bool) print '</ul></li>'; $bool=true;
            print '<li class="expandable"><div class="hitarea expandable-hitarea"></div><strong>'.$key.'</strong>';
            print '<ul style="display: none;">';
            for($i=0;$i<4;$i++)
            {
                if (count($arrList[$key[$i]]) > 0)
                {
                    foreach($arrList[$key[$i]] as $key1=>$val2)
                    {
                            print '<li id="'.$val2['id'].'" class="draggable"><div style="background-color: #FFFFFF;">'.$val2['nom'].'</div></li>';
                    }
                }

            }
        }

        print '</ul></li>';
        print '<li class="lastExpandable"><div class="hitarea lastExpandable-hitarea"></div><strong>Autres</strong>';
        print '<ul style="display: none;">';
        foreach($arrList['Autres'] as $key1=>$val2)
        {
                print '<li id="'.$val2['id'].'" class="draggable"><div style="background-color: #FFFFFF;">'.$val2['nom'].'</div></li>';
        }


        print '</li>';
        print '</ul>';
        print '</td>';

        print '</tr>';
        print '<tr><td>';

            print '<div style="position: relative; clear: right; float: left;   bottom: 0px; " >';
            print '<div  class="scroll ui-widget ui-widget-content ui-state-default ui-jqgrid-pager ui-corner-bottom" style="text-align: center; margin: 0;  width: 630px; height: 24px;">';
            print '<div  class="ui-pager-control" role="group" style="font-size: 1.1em; border: 1px none,font-weight: bold; outline: none ;">';
            print '</div>';
            print '<table><tbody><tr>';
            print '<td id="add_cat" class="ui-pg-button ui-corner-all" title="Ajouter une cat&eacute;gorie">'."\n";
            print '<div class="ui-pg-div">';
            print '<span class="ui-icon ui-icon-plus"></span>';
            print '</div>';
            print '</td>';
            print '<td id="mod_cat" class="ui-pg-button  ui-corner-all" title="Modifier une cat&eacute;gorie">';
            print '<div class="ui-pg-div" style="font-size: 1.1em;font-weight: bold; outline: none ;">';
            print '<span class="ui-icon ui-icon-pencil"></span>';
            print '</div>';
            print '</td>';
            print '<td id="del_cat" class="ui-pg-button ui-corner-all" title="Effacer une cat&eacute;gorie">';
            print '<div class="ui-pg-div" style=" font-size: 1.1em;font-weight: bold; outline: none ">';
            print '<span class="ui-icon ui-icon-trash"></span>';
            print '</div>';
            print '</td><td></td>';
            print '</tr></tbody></table>';

        print '</table>';

        print '    </div>'; // fin de fragment
        print '    <div id="fragment-2" style="min-height: 475px;">';

//1 choisit les doc à gauche par groupe ?

        print '     <div style="float : left; width: 630px; height: 475px;" class="ui-corner-top ui-widget ui-widget-content " >';
        print '     <div class="ui-widget-header ui-state-default ui-corner-top" style="padding: 2px 0px 4px 4px;">Groupe</div>';

        $requete = "SELECT rowid, label as title FROM ".MAIN_DB_PREFIX."projet_task WHERE fk_projet = ".$projet->id;
            $sql = $db->query($requete);
            $taskSel = "";
            while ($res=$db->fetch_object($sql))
            {
                $taskSel .= "<option value='".$res->rowid."'>".$res->title."</option>";

            }


        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_document_group
                     WHERE fk_projet = ".$projetid;
        $sql = $db->query($requete);

        if ($db->num_rows($sql) > 0)
        {
            //print $db->num_rows($sql);
            print '       <div class="actionaccordion">';
            while ($res = $db->fetch_object($sql))
            {
            //    var_dump($requete);
                print '        <div  id="grpdID'.$res->id.'" class="accordion-header ui-widget ui-widget-header" style="min-width: 630px;">'."\n";
                print '          <a href="#">'."\n";
                print '           <span  id="grpdSID'.$res->id.'"  class="head accordion-header" style="text-transform: capitalize;" >' . html_entity_decode($res->nom) . '</span>'."\n";
                print           '</a>'."\n";
                print '           </div>'."\n";
                print '           <div style="width:560px; padding-bottom: 5pt; padding-top: 5pt; " class="ui-accordion-contentDoc" >'."\n";
 //TODO templacer par des div
                print "             <div style='width:100%'>"."\n";
                print "                 <div  class='condDisplayVert' style='height: 100%; width:226px;'>"."\n";
                                        $projet->showTreeTask('selTask',"218",240,'0','none')."\n";
                print "                 </div>"."\n";
                print "                 <div style='width:325px; text-align: center; float: left'>";
                print "                 <table><tr><td width=175>Envoi conditionnel </td>"."\n";
                print "                      <td colspan='2' width=145><input type='hidden' name='CurSelTask' id='CurSelTask' value=''><input type='checkbox' onClick='displayCondAction(this)'></td>"."\n";
                print "                 </tr>"."\n";
                print "                 <tr>"."\n";
                print "                      <td colspan='3'>&nbsp;</td>"."\n";
                print "                 </tr>"."\n";
                print "                 <tr>"."\n";
                print "                   <td colspan=3><div  style='display: none;' class='condDisplay ui-widget ui-widget-content' ><table width=320><tr>"."\n";
                print "                      <td width=175 style='font-size: 11pt;'>Envoyer si la t&acirc;che est effectu&eacute; &agrave; </td>"."\n";
                print "                      <td width=100><input type='hidden' name='percTask'><div class='slider'></div></td>"."\n";
                print "                      <td width=45><span><span id='percTaskStr'>0</span>%</span></td>"."\n";
                print "                    </tr></table></div>"."\n";
                print "                 </tr>"."\n";
                print "                 <tr>"."\n";
                print "                      <td colspan='3'>&nbsp;</td>"."\n";
                print "                 </tr>"."\n";
                print "                 <tr><td>Envoyer &agrave; :</td>"."\n";
                print "                     <td colspan=1>"."\n";
                print "                         <table title='Cliquer pour modifier' width=100% onClick='editMailTo(this);'>"."\n";
                print "                             <tr><td>toto</td></tr>"."\n";
                print "                             <tr><td>titi</td></tr>"."\n";
                print "                             <tr><td>toto@toto.com</td>"."\n";
                print "                             </tr></table>"."\n";
                print "                     </td></tr>"."\n"; //TODO onclick show modal , cf contact
                print "                 <tr>"."\n";
                print "                      <td colspan='3'>&nbsp;</td>"."\n";
                print "                 </tr>"."\n";
                print "                 <tr>"."\n";
                print "                      <td colspan='3'>&nbsp;</td>"."\n";
                print "                 </tr>"."\n";

                print "                 <tr><td colspan=3  align=center><button class='ui-corner-all ui-widget ui-state-default ui-widget-button' style='padding: 5px 10px;'><span style='float: left; margin-right: 5px' class='ui-icon ui-icon-mail-closed'></span><span style='float: left'>Envoyer maintenant</span></button></td>"."\n";
                print "                 </table></div>";
                print "             </div>"."\n";

//                print '             <div style="float: left"><fieldset><legend>D&eacute;tail</legend><table><tr><td>Nb. de fichier</td><td align=right><span id="count'.$res->id.'">0</span></td></tr><tr><td>Volume</td><td  align=right><span id="totSize'.$res->id.'">0</span></td></tr></table></fieldset></div>';
                print '           </div>'."\n";
            }
            print '       </div>'."\n"; // fin accordion
        } else {
            print '        <div style="margin-left: 20%; margin-top: 30%;  padding: 20px; width: 150px; " class="ui-state-default ui-error">Pas de groupe d&eacute;fini</div>';
        }
        print '     </div>'; // fin de float div

//2 choisit l'acction, la tache

        print '    </div>';
        print '    <div id="fragment-1">';
        $upload_dir = $projetConf->dir_output.'/'.sanitize_string($projet->ref);
        // Construit liste des fichiers
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        $totalsize=0;
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }
        print '<table cellpadding=15 class="border"width="100%">';
        // Ref
        print '<tr><th width="30%" class="ui-widget-header ui-state-default">'.$langs->trans('Ref').'</th>
                   <td  class="ui-widget-content" colspan="3">'.$projet->ref.'</td></tr>';
        // Projet
        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans('Project').'</th>
                   <td  class="ui-widget-content" colspan="5">'.$projet->title.'</td></tr>';

        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("NbOfAttachedFiles").'</td>
                   <td class="ui-widget-content" colspan="3">'.sizeof($filearray).'</td></tr>';
        if ($totalsize > 1024 * 1024)
        {
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("TotalSizeOfAttachedFiles").'</th>
                       <td  class="ui-widget-content" colspan="3">'.round($totalsize * 100 / (1024*1024))/100 .' M'.$langs->trans("bytes").'</td></tr>';
        } else if ($totalsize > 1024){
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("TotalSizeOfAttachedFiles").'</th>
                       <td  class="ui-widget-content" colspan="3">'.round($totalsize * 100/1024)/100 .' K'.$langs->trans("bytes").'</td></tr>';
        } else {
            print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("TotalSizeOfAttachedFiles").'</th>
                       <td  class="ui-widget-content" colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
        }

        print '</table><br><br>';


        if ($mesg) { print "$mesg<br>"; }

        // Affiche formulaire upload
        $formfile=new FormFile($db);
        $formfile->form_attach_new_file(DOL_URL_ROOT.'/synopsisprojet/document.php?id='.$projet->id,"Documents du projet","1","1",$projet);


        // List of document
        $param='&projetid='.$projet->id;
        $formfile->list_of_documents($filearray,$projet,'synopsisprojet',$param);

        //Download all docs via zip
        print "<br>";
        print '<form action="?id='.$projetid.'" method="POST">';
        print '<input type="hidden" name="projet_id" value="'.$projetid.'"/>';
        print '<input type="hidden" name="SynAction" value="dlZip"/>';
        print '<button class="ui-widget-header ui-state-default ui-corner-all" style="padding: 5px 10px;" value="'.$langs->trans('DownloadZip').'">';
        print "<span style='float: left; margin-right: 5px;' class='ui-icon ui-icon-suitcase'></span>";
        print "<span style='float: left;'>".$langs->trans('DownloadZip')."</span>";
        print "</button>";
        print '</form>';
        print '    </div>'; //fin de fragment
        print '</div>';// fin de tabs


        print '<div id="addDiag">';
        print '<form id="addDialogForm">';
        print '<table>';
        print '<tbody>';
        print '<tr>';
        print '<td>Nom :';
        print '</td>';
        print '<td><input type="text" id="addDocName" name="addDocName">';
        print '</td>';
        print '</tr>';
        print '</tbody>';
        print '</table>';
        print '</form>';
        print '</div>';

        print '<div id="modDiag">';
        print '<form id="modDialogForm">';
        print '<table>';
        print '<tbody>';
        print '<tr>';
        print '<td>Nom :';
        print '</td>';
        print '<td><input type="text" id="modDocName" name="modDocName">';
        print '</td>';
        print '</tr>';
        print '</tbody>';
        print '</table>';
        print '</form>';
        print '</div>';

        print '<div id="delDiag">';
        print '<p>Etes vous sur de vouloir effacer ce groupe ?</p>';
        print '</div>';

        print '<div id="mailDialog">';
        print '<form id="mailDialogForm">';
        print '</form>';
        print '</div>';


    }
    else
    {
        dol_print_error($db);
    }
}
else
{
    print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date: 2008/07/10 17:11:05 $ - $Revision: 1.43 $');
?>

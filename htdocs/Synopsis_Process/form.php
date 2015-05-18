<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 30 dec. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : formPreview.php
 * GLE-1.2
 *
 */
//$a = memory_get_usage(1);
//echo "<pre>".print_r($_POST, true)."</pre>";
require_once('pre.inc.php');
//  $b = memory_get_usage(1);
$process_id = $_REQUEST['process_id'];
$element_id = $_REQUEST['id'];
$element_obj = false;
$processDetId = $_REQUEST['processDetId'];
if ($processDetId > 0 && (!$element_id > 0 || !$process_id)) {
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_active WHERE processdet_refid = " . $processDetId;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $element_id = $res->element_refid;
    $process_id = $res->process_refid;
}
//  require_once('Var_Dump.php');
//print Var_Dump::Display($_REQUEST);


if (!$user->rights->process->lire) {
    if ($process_id > 0) {
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
        $process = new process($db);
        $res1 = $process->fetch($process_id);
        $process->getGlobalRights();
        $tmp = "process" . $process_id;
        if (!$user->rights->process_user->$tmp->voir)
            accessforbidden("Un process bloque l'acc&egrave;s &agrave; cet &eacute;l&eacute;ment. 1");
    } else {
        accessforbidden("Un process bloque l'acc&egrave;s &agrave; cet &eacute;l&eacute;ment. id inc");
    }
}

if ($_REQUEST['action'] == 'saveDatas') {
    saveDatas($db, $_REQUEST, $process_id, $element_id, $processDetId);
}
if ($_REQUEST['action'] == 'askValid') {
    saveDatas($db, $_REQUEST, $process_id, $element_id, $processDetId, false);
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
    $processDet = new processDet($db);
    $res1 = $processDet->fetch($processDetId);
    $res = $processDet->ask_valid();
    if ($res) {
        $processDet->fetch_process();
        $process = $processDet->process;
        $eval = $process->askValidAction;
        if ($eval . "x" != "x")
            eval($eval);
        header('location: form.php?processDetId=' . $processDetId);
    }
}
if ($_REQUEST['action'] == 'validAction') {
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
    $process = new process($db);
    $res1 = $process->fetch($process_id);
    $processDet = new processDet($db);
    $processDet->fetch($processDetId);
//    if($process->fk_statut ); die;
//    $eval = $process->validAction;
//    if ($eval . "x" != "x")
//        eval($eval);
}

if ($_REQUEST['action'] == 'validationForm') {
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
    $process = new process($db);
    $res1 = $process->fetch($process_id);
    $process->validateDet($element_id, $type);
    $processDet = new processDet($db);
    $processDet->fetch($processDetId);
//    $eval = $process->validAction;
//    if ($eval . "x" != "x")
//        eval($eval);
}
if ($_REQUEST['action'] == 'reviser' && $processDetId > 0) {
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
    $processDet = new processDet($db);
    $processDet->fetch($processDetId);
    $res = $processDet->set_revised();
    if ($res > 0) {
        $processDet->fetch_process();
        $process = $processDet->process;
        $eval = $processDet->process->reviseAction;
        if ($eval . "x" != "x")
            eval($eval);
        header('location: form.php?processDetId=' . $res);
    }
}
if ($_REQUEST['action'] == 'modAfterValid' && $processDetId > 0) {
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
    $processDet = new processDet($db);
    $processDet->fetch($processDetId);
    $res = $processDet->unvalidate();
    if ($res > 0) {
        $processDet->fetch_process();
        $process = $processDet->process;
        $eval = $processDet->process->reviseAction;
        if ($eval . "x" != "x")
            eval($eval);
        header('location: form.php?processDetId=' . $processDetId);
    }
}
$displayHead = 1;
displayForm($db, $displayHead, $process_id, $element_id, $processDetId);

/* js to disable form
 */

function saveDatas($db, $req, $process_id, $element_id, $processDetId, $go = true) {
//      require_once('Var_Dump.php');
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
    $process = new process($db);
    $res1 = $process->fetch($process_id);
    $process->getGlobalRights();
    $processDet = new processDet($db);
    if ($processDetId > 0) {
        $processDet->fetch($processDetId);
        $processDet->process_refid = $process_id;
        $processDet->element_refid = $element_id;
        $processDet->update();
    } else {
        $processDet->ref = $process->getNextRef();
        $processDet->process_refid = $process_id;
        $processDet->element_refid = $element_id;
        $processDetId = $processDet->add();
        //UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet_active
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Processdet_active
                         SET processdet_refid =" . $processDetId . "
                       WHERE element_refid = " . $processDet->element_refid . "
                         AND process_refid= " . $processDet->process_refid . "
                         AND type_refid  = " . $_REQUEST['type_element_GLE'];
        $sqlGLE = $db->query($requete);
    }
    //TODO Save Datas
    //Liste les parametres
    $requete = "SELECT v.valeur, m.id
                    FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model as m,
                         " . MAIN_DB_PREFIX . "Synopsis_Process_form_type as t,
                         " . MAIN_DB_PREFIX . "Synopsis_Process_form as f,
                         " . MAIN_DB_PREFIX . "Synopsis_Process as pr,
                         " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop as p
               LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value as v ON p.id = v.prop_refid
                   WHERE m.type_refid = t.id
                     AND v.model_refid = m.id
                     AND m.form_refid = f.id
                     AND f.id = pr.formulaire_refid
                     AND pr.id = " . $process_id . "
                     AND p.element_name = 'name'
                     AND v.valeur is not NULL
                     AND v.valeur <> ''
                     AND t.isInput = 1";
    $res1 = $process->fetch($process_id);
    //recupere les valeurs
    $sql = $db->query($requete);
    $arrVal = array();
    $arrSkip = array();
    while ($res = $db->fetch_object($sql)) {
//        $arrVal[$res->valeur] = "";
        foreach ($_REQUEST as $key => $val) {
//            print $val."<br/>";
            if ($res->valeur == $key) {
                $arrVal[$res->valeur] = array('val' => $_REQUEST[$res->valeur], 'model' => $res->id);
                $arrSkip[$res->id] = $res->id;
                continue;
            }
        }
    }
    
    
    //Valeur avec le label
    $requete = "SELECT m.label as valeur, m.id 
        FROM `".MAIN_DB_PREFIX."Synopsis_Process_form_model` m, `".MAIN_DB_PREFIX."Synopsis_Process` p 
        WHERE m.form_refid = p.`formulaire_refid` AND p.`id` = ".$process_id;
        
    if (count($arrSkip) > 0)
        $requete .= "    AND m.id NOT IN (" . join(',', $arrSkip) . ")";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql)) {
        $res->valeur = SynSanitize($res->valeur);
//        $arrVal[$res->valeur] = "";
        foreach ($_REQUEST as $key => $val) {
//            print $key.$val."<br/>".$res->valeur;
            if ($res->valeur == $key) {
                $arrVal[$res->valeur] = array('val' => $_REQUEST[$res->valeur], 'model' => $res->id);
                continue;
            }
        }
    }
    
        //Valeur si pas de name
    $requete = "  SELECT CONCAT('inpt',m.id) as valeur,
                         m.id
                    FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model as m,
                         " . MAIN_DB_PREFIX . "Synopsis_Process_form_type as t,
                         " . MAIN_DB_PREFIX . "Synopsis_Process_form as f,
                         " . MAIN_DB_PREFIX . "Synopsis_Process as pr
                   WHERE m.type_refid = t.id
                     AND m.form_refid = f.id
                     AND f.id = pr.formulaire_refid
                     AND pr.id = " . $process_id . "
                     AND t.isInput = 1";
    if (count($arrSkip) > 0)
        $requete .= "    AND m.id NOT IN (" . join(',', $arrSkip) . ")";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql)) {
//        $arrVal[$res->valeur] = "";
        foreach ($_REQUEST as $key => $val) {
//            print $key.$val."<br/>".$res->valeur;
            if ($res->valeur == $key) {
                $arrVal[$res->valeur] = array('val' => $_REQUEST[$res->valeur], 'model' => $res->id);
                continue;
            }
        }
    }
//            die;

    //Valeur des fonctions
    foreach ($process->formulaire->lignes as $key => $ligne) {
//        if($ligne->label == "test987"){
//        echo "<pre>";
//        print_r ($ligne->src);
//        echo "</pre>";
//        }
        if ($ligne->src->type == 'f' && $ligne->src->fct->paramsForHtmlName . "x" != "x") {
            //on cherche la valeur de se parametre
            $requete = "SELECT *
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value
                             WHERE fct_refid = " . $ligne->src->fct->id . "
                               AND model_refid = " . $ligne->id . "
                               AND label = '" . $ligne->src->fct->paramsForHtmlName . "'";
            $sql = $db->query($requete);
            die($requete);
            while ($res = $db->fetch_object($sql)) {
                $arrVal[$res->valeur] = array('val' => $_REQUEST[$res->valeur], 'model' => $res->model_refid);
            }
        }
    }

    foreach ($arrVal as $key => $val) {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_value WHERE processDet_refid = " . $processDetId . " AND nom='" . $key . "'";
        $sql = $db->query($requete);
        if ($sql) {
            $requete1 = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Processdet_value (processDet_refid,nom, valeur, model_refid) VALUES (" . $processDetId . ",'" . $key . "','" . addslashes($val['val']) . "'," . $val['model'] . ") ";
            $sql = $db->query($requete1);
        }
    }
    //mets à jour / ajoute
    if ($go) {
        if ($processDet->fk_statut == 2) {
            header('Location:' . DOL_URL_ROOT . "/" . $process->typeElement->ficheUrl . "?" . $process->typeElement->_GET_id . "=" . $element_id);
            exit;
        } else {
            header('Location:' . DOL_URL_ROOT . "/Synopsis_Process/form.php?id=" . $element_id . "&process_id=" . $process_id . "&processDetId=" . $processDetId);
            exit;
        }
    }
}

function displayForm($db, $displayHead = true, $process_id, $element_id = false, $processDetId = false) {
    global $langs, $conf, $user, $element_obj;
    $xmpMode = 0;

    $element_obj = false;

    $langs->load("process@Synopsis_Process");

    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
    $process = new process($db);
    $processDet = new processDet($db);
    $res1 = $process->fetch($process_id, false);
    $process->getGlobalRights();
    if ($processDetId > 0)
        $processDet->fetch($processDetId);
    else
        $processDet = false;

    if ($element_id && $process->typeElement)
        $element_obj = $process->typeElement->fetch_element($element_id);

    $form = new formulaire($db);
    $res = $form->fetch($process->formulaire_refid);
    $js = "";
    $js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.validate.min.js'></script>";
    $arrTabsTitle = array();
    $hasModRight = false;
    if ($element_id) {
        $tmp = 'process' . $process->id;
        if ($user->rights->process->modifier || $user->rights->process_user->$tmp->modifier) {
            $hasModRight = true;
        }
    }

    if ($res > 0) {
        $arr = array();
        $arrCss = array();
        $arr1 = array();
        $curTabId = false;

        $hasDoubleSel = false;
        $arrRem = array();
        $arrCssRem = array();
        foreach ($form->lignes as $key => $lignes) {
            if ($lignes->type->jsScript . 'x' != 'x' && !in_array("<script type='text/javascript' src='" . DOL_URL_ROOT . "/" . $lignes->type->jsScript . "'></script>", $arrRem)) {
                $arr[$lignes->type->code] = "<script type='text/javascript' src='" . DOL_URL_ROOT . "/" . $lignes->type->jsScript . "'></script>";
                $arrRem[] = "<script type='text/javascript' src='" . DOL_URL_ROOT . "/" . $lignes->type->jsScript . "'></script>";
            }
            if ($lignes->type->cssScript . 'x' != 'x' && !in_array("<link type='text/css' rel='stylesheet' href='" . DOL_URL_ROOT . "/" . $lignes->type->cssScript . "'/>", $arrCssRem)) {
                $arrCss[$lignes->type->code] = "<link type='text/css' rel='stylesheet' href='" . DOL_URL_ROOT . "/" . $lignes->type->cssScript . "'/>";
                $arrCssRem[] = "<link type='text/css' rel='stylesheet' href='" . DOL_URL_ROOT . "/" . $lignes->type->cssScript . "'/>";
            }
            if ($lignes->type->jsCode . 'x' != 'x') {
                $arr1[$lignes->type->code] = $lignes->type->jsCode;
            }

            if ($lignes->type->isBegEndTab == 1)
                $curTabId = $lignes->id;
            if ($lignes->type->isBegEndTab == 2)
                $curTabId = false;
            if ($curTabId && $lignes->type->isTabTitle > 0) {
                $arrTabsTitle[$curTabId][] = $lignes->label;
            }
            if ($lignes->src->uniqElem->OptGroup . "x" != "x") {
                $hasDoubleSel = true;
            }
        }


        if (0 && $hasDoubleSel) {
            $js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.jDoubleSelect.js'></script>";
            $js .= <<<EOF
                <script>
                jQuery(document).ready(function(){
                    jQuery('select.double').each(function(){
                        var self=jQuery(this);

                        var widthSelect = parseInt(jQuery("#"+self.attr('id')+"").parent()[0].offsetWidth * 0.9);
                        jQuery(this).jDoubleSelect({
                                        text:'',
                                        finish: function(){
                                            /*jQuery("#"+self.attr('id')+"_jDS").selectmenu({
                                                style:'dropdown',
                                                maxHeight: 300,
                                                width: widthSelect,
                                            });*/

                                        },
                                        el1_change: function(){
                                            /*jQuery("#"+self.attr('id')+"_jDS_2").selectmenu({
                                                style:'dropdown',
                                                maxHeight: 300,
                                                width: widthSelect,
                                            });*/

                                        },
                                        el2_dest: jQuery("#"+self.attr('id')+"_jDS_Dest_2"),

                        });

                    });
                });

                      </script>
EOF;
        }
        if (!$hasModRight || $processDet->statut != 0) {
            $js .= <<<EOF
        <script>
        jQuery(document).ready(function(){
            jQuery('#formProcess').find('input').each(function(){
                jQuery(this).attr('disabled','true');
                if(jQuery(this).attr('type')=='text'){
                    var val = jQuery(this).val();
                    var classElem = jQuery(this).attr("class");
                    jQuery(this).replaceWith("<div style='display: inline-block; min-height: 1em; min-width: 100px; padding: 3px 7px;opacity: 0.85' class='ui-widget-header ui-corner-all ui-state-hover "+classElem+"'>"+val+"</div>");
                }
            });
            jQuery('#formProcess').find('textarea').each(function(){
                jQuery(this).attr('disabled','true');
                var val = jQuery(this).val();
                var classElem = jQuery(this).attr("class");
                jQuery(this).replaceWith("<div style='display: inline-block; min-height: 5em; min-width: 200px; padding: 3px 7px;opacity: 0.85' class='ui-widget-header ui-corner-all ui-state-hover "+classElem+"'>"+val+"</div>");
            });
            jQuery('#formProcess').find('SELECT').each(function(){
                jQuery(this).attr("disabled", "disabled");
            });
            jQuery('#formProcess').find('input.star-rating-applied').each(function(){
                jQuery(this).rating('disable');
            });
EOF;
            if ($_REQUEST['fromIframe'] == 1) {
                $js .= <<<EOF
            jQuery('a.ui-selectmenu-disabled').css('opacity','0.85');
EOF;
            }

            $js .= <<<EOF
        });
        </script>
EOF;
        }

        $js .= join('', $arr);
        $js .= join('', $arrCss);
        $js .= "<script>" . join('', $arr1) . " ; </script>";

        if ($element_id) {
            $js.="<script> jQuery(document).ready(function(){
                                 jQuery('#saveButton').click(function(){
                                     if (jQuery('#formProcess').validate().form()) {
                                         jQuery('#formProcess').submit();
                                     }
                                 });
                                jQuery('#revButton').click(function(){
                                    location.href='form.php?action=reviser&processDetId='+processDetId;
                                });
                                jQuery('#modAfterValidButton').click(function(){
                                    location.href='form.php?action=modAfterValid&processDetId='+processDetId;
                                });
                                 jQuery('#formProcess').validate();
                             });";
            if ($processDetId > 0) {
                $js .= "var processDetId = " . $processDetId . ";";
                $js .= "var processId = " . $process->id . ";";
                $js .= "var validation_number = " . ($processDet->validation_number > 0 ? $processDet->validation_number : 1) . ";";
                if ($element_id > 0)
                    $js .= "var element_refid = " . $element_id . ";";
            }
            $js .=<<<EOF
            function validation_Form(obj)
            {
                jQuery(obj).attr('disabled', 'disabled');
                var typeValid = jQuery(obj).attr("id");
                var valeur = jQuery(obj).parents('tr').find('select :selected').val();
                var nota = jQuery(obj).parents('tr').find('textarea').val();

/*                console.log(typeValid);
                console.log(obj);
                console.log(jQuery(obj).parents('tr').find('select :selected').val());
                console.log(jQuery(obj).parents('tr').find('textarea').val());
*/
                var data = "code="+typeValid+"&valeur="+valeur+"&note="+nota+"&processDetId="+processDetId+"&element_refid="+element_refid+"&processId="+processId+'&validation_number='+validation_number;

                jQuery.ajax({
                    url:'ajax/validatationProcess-xml_response.php',
                    data:data,
                    datatype:'xml',
                    type:'POST',
                    success:function(msg){
                        if(jQuery(msg).find('OK').length>0)
                        {
                            location.href='form.php?processDetId='+processDetId+"&action=validAction";
                        } else {
                            alert('KO');
                        }
                    }
                })

EOF;
            //TODO Dialog
//                $js .= "location.href='form.php?id=".$element_id."&process=".$process_id.($processDetId>0?"&processDetId=".$processDetId:"")."&action=validationForm&type='+validation_Form;";
            $js .= <<<EOF
                return(false);
            }
</script>

EOF;
        }
        if ($processDetId)
            $js.="<script> jQuery(document).ready(function(){ 
                jQuery('#askValid').click(function(){ 
                    if (jQuery('#formProcess').validate().form()) { 
                                jQuery('#action').val('askValid'); 
                                jQuery('#saveButton').click();
                    } else { 
                        alert('Erreur dans le formulaire')} 
                    }
                ); 
                jQuery('#formProcess').validate(); 
                });</script>";
    }
//    print "<xmp>".$js."</xmp>";
    if (!$_REQUEST['fromIframe'] == 1) {
        printHead($process->typeElement->type, $process->typeElement->element->id, $js);
    } else {
        top_htmlhead($js);
        $displayHead = false;
    }


    print "<div class='titre'>" . $process->label . ' - ' . $form->label . ($processDetId > 0 ? ' - ' . $processDet->ref : "") . "</div>";
//$eval = preg_replace('/\$/','\\\$',$process->pretraitement);
    $eval = $process->pretraitement;

    if ($eval . "x" != "x") {
//        print "<xmp>";
//        var_dump($eval);
//        print "</xmp>";
        eval($eval);
    }



    if ($displayHead) {
        print "<table cellpadding=15 width=100%>";
        print "<tr><th class='ui-widget-header ui-state-default'>Nom du process</th>";
        print "    <td class='ui-widget-content'>" . $process->getNomUrl(1) . "</td>";
        print "    <th class='ui-widget-header ui-state-default'>Nom du formulaire</th>";
        print "    <td class='ui-widget-content'>" . ($process->formulaire ? $process->formulaire->getNomUrl(1) : '') . "</td>";
        print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;f&eacute;rence</th>";
        print "    <td class='ui-widget-content' colspan=1>" . ($processDet ? $processDet->getNomUrl(1) : "") . "</td>";
        print "    <th class='ui-widget-header ui-state-default'>Statut</th>";
        print "    <td class='ui-widget-content' colspan=1>" . ($processDet ? $processDet->getLibStatut(4) : "") . "</td>";
        if ($processDet->isRevised) {
            $arrNextPrev = $processDet->getPrevNextRev();
            $prev = $arrNextPrev['prev'];
            $next = $arrNextPrev['next'];
            $procNext = new processDet($db);
            $procPrev = new processDet($db);
            if ($next && $prev) {
                $procNext->fetch($next);
                $procPrev->fetch($prev);
                print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;vision pr&eacute;c&eacute;dente</th>";
                print "    <td class='ui-widget-content'>" . $procPrev->getNomUrl(1) . "</td>";
                print "    <th class='ui-widget-header ui-state-default'>R&eacute;vision suivante</th>";
                print "    <td class='ui-widget-content'>" . $procNext->getNomUrl(1) . "</td>";
            } else if ($prev) {
                $procPrev->fetch($prev);
                print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;vision pr&eacute;c&eacute;dente</th>";
                print "    <td class='ui-widget-content' colspan=3>" . $procPrev->getNomUrl(1) . "</td>";
            } else if ($next) {
                $procNext->fetch($next);
                print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;vision suivante</th>";
                print "    <td class='ui-widget-content' colspan=3>" . $procNext->getNomUrl(1) . "</td>";
            }
        }

        if ($process->typeElement && $element_id > 0) {
            print "<tr><th class='ui-widget-header ui-state-default'>El&eacute;ment</th>";
            print "    <td class='ui-widget-content' colspan=1>" . str_replace(".php?", ".php?vueValid=true&", $process->typeElement->getNomUrl_byProcessDet($element_id, 1)) . "</td>";
            if ($processDet->validation_number > 1 || $processDet->statut > 0) {
                print "    <th class='ui-widget-header ui-state-default'>Suivi validation</th>";
                print "    <td class='ui-widget-content' colspan=1><a href='" . DOL_URL_ROOT . "/Synopsis_Process/historyValidation.php?filterProcess=" . $processDetId . "'><table><tr><td><span class='ui-icon ui-icon-extlink'></span></td><td>Suivi</td></table></a></td>";
            }
        }
        if ($processDet->fk_statut > 0) {
            print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;sum&eacute;</th>";
            print "    <td class='ui-widget-content' colspan=3><a href='formDatas.php?processDetId=" . $processDetId . "'><table><tr><td><span class='ui-icon ui-icon-extlink'></span></td><td>Afficher</table></a>";
        }

        print "</table>";
    }
//      var_dump::display($process);
    if ($element_id) {
        print "<form id='formProcess' action='form.php?id=" . $element_id . ($processDetId > 0 ? "&processDetId=" . $processDetId : "") . "&process_id=" . $process_id . "' method='POST'>";
        print "<input type='hidden' name='action' id='action' value='saveDatas'>";
        print "<input type='hidden' name='action2' id='action2' value='none'>";
    }
    if (!$processDetId > 0 && $_REQUEST['type'] > 0) {
        print '<input type="hidden" name="type_element_GLE" value="' . $_REQUEST['type'] . '"></input>';
    }
    if ($res > 0) {
        print "<div>";
        $isFirstTabsElement = false;
        foreach ($form->lignes as $key => $lignes) {
            //Droits sur ligne
            $rights = $lignes->rights;
            if ($lignes->rights . "x" != "x") {
                $rights = $lignes->rights;
                $str = 'if(!(' . $rights . ')) { $rights = false; } else { $rights = true; }';
                eval($str);
            } else {
                $rights = true;
            }
            if (!$rights)
                continue;

            if ($xmpMode == 1)
                print "<xmp>";
            //var_dump(($lignes->src->uniqElem->OptGroup."x" != "x"?" double noSelDeco ":""));
            if ($lignes->type->isBegEndTab == 1) {
                //Remove dernier char de htmlTag
                if ($lignes->type->htmlTag . "x" != "x") {
                    print preg_replace('/>$/', ' ', $lignes->type->htmlTag);
                    if (count($lignes->prop) > 0) {
                        foreach ($lignes->prop as $key => $val) {
                            if ($val->valeur . "x" != "x")
                                print $val->element_name . "='" . $val->valeur . "' ";
                        }
                    }
                    print " class='" . $lignes->type->cssClass . " " . $lignes->cssClass->valeur . " " . ($lignes->src->uniqElem->OptGroup . "x" != "x" ? " double noSelDeco " : "") . "' ";
                    if (count($lignes->style) > 0) {
                        print " style='";
                        foreach ($lignes->style as $key => $val) {
                            if ($val->valeur . "x" != "x")
                                print $val->element_name . ":" . $val->valeur . " ;";
                        }
                        print "' ";
                    }
                    print " >";
                }
                print "<ul>";
                foreach ($arrTabsTitle[$lignes->id] as $key1 => $val1) {
                    print "  <li><a href='#tabs-" . SynSanitize($val1) . "'>" . $val1 . "</a></li>";
                }
                print "</ul>";
                $isFirstTabsElement = true;
                continue;
            } else if ($lignes->type->isTabTitle == 1) {
                if (!$isFirstTabsElement) {
                    print "</div>";
                }
                print '<div id="tabs-' . SynSanitize($lignes->label) . '">';
                $isFirstTabsElement = false;
                continue;
            } else if ($lignes->type->isBegEndTab == 2) {
                if (!$isFirstTabsElement) {
                    print "</div>";
                }
                print "</div>";
                $isFirstTabsElement = false;
                continue;
            }


            if ($lignes->label . "x" != "x" && $lignes->type->titleInLegend <> 1 && $lignes->type->titleInsideTag <> 1) {
                $label = $lignes->label;
                eval("\$label = \"$label\";");
                print $label . "<br>";
            }
            if ($lignes->description . "x" != "x" && $lignes->type->descriptionInsideTag <> 1 && $lignes->type->hasDescription == 1) {
                $description = $lignes->description;
                eval("\$description = \"$description\";");
                print "<em>" . $description . "</em><br>";
            }
            //Remove dernier char de htmlTag
            if ($lignes->src->type == 'f') {
                $ret = $lignes->src->uniqElem->call_function($lignes->id, $processDetId);
                if ($ret)
                    print $ret;
            } else {
                $iter = 1;
                $htmlName = "";
                $htmlId = "";
                if ($lignes->type->repeatTag > 0) {
                    $iter = $lignes->type->repeatTag;
                    print "<div class='starrating'>";
                }
                for ($itmp = 0; $itmp < $iter; $itmp++) {
                    $jtmp = $itmp + 1;
                    if ($lignes->type->htmlTag . "x" != "x") {
                        print ($lignes->src->uniqElem->OptGroup . "x" != "x" ? "<table width=100%><tr><td width=50%>" : "");

                        print preg_replace('/>$/', ' ', $lignes->type->htmlTag);
                        if (count($lignes->prop) > 0) {
                            foreach ($lignes->prop as $key => $val) {
                                if ($val->element_name == 'name' && $val->valeur . "x" != "x")
                                    $htmlName = $val->valeur;
                                if ($val->element_name == 'id' && $val->valeur . "x" != "x")
                                    $htmlId = $val->valeur;
                                if ($val->valeur . "x" != "x")
                                    print $val->element_name . "='" . $val->valeur . "' ";
                            }
                        }
                        if ($htmlName . 'x' == 'x' && $lignes->type->isInput == 1) {
                            $htmlName = SynSanitize(($lignes->label . "x" == "x" ? "inpt" . $lignes->id : $lignes->label));
                            print " name='" . $htmlName . "' ";
                        }
                        if ($htmlId . 'x' == 'x' && $lignes->src->uniqElem->OptGroup . "x" != "x") {
                            $htmlId = SynSanitize(($lignes->label . "x" == "x" ? "JDS" . $lignes->id : $lignes->label));
                            print " id='" . $htmlId . "' ";
                        }
                        //Si value inside value
                        if ($lignes->type->valueInValueField == 1) {
                            if ($processDetId) {
                                $tmp = false;
                                if ($processDet->valeur->valeur[$htmlName]->valeur . "x" != "x") {
                                    print " value='" . $processDet->valeur->valeur[$htmlName]->valeur . "' ";
                                }
                            } else {
                                if ($lignes->dflt . "x" != "x") {
                                    if (preg_match('/\[GLOBVAR\]([0-9]*)/', $lignes->dflt, $arr)) {
                                        $dflt = getGlobalVar($arr[1]);
                                        print " value='" . $dflt . "' ";
                                    } else {
                                        print " value='" . $lignes->dflt . "' ";
                                    }
                                }
                            }
                        }
                        //Si value checked
                        if ($lignes->type->valueIsChecked == 1 && $iter == 1) {
                            //TODO si valeur du formulaire
                            if ($processDetId) {
                                $tmp = false;
                                if ($processDet->valeur->valeur[$htmlName]->valeur > 0) {
                                    print " checked ";
                                }
                            } else {
                                if ($lignes->dflt . "x" != "x") {
                                    if (preg_match('/\[GLOBVAR\]([0-9]*)/', $lignes->dflt, $arr)) {
                                        $dflt = getGlobalVar($arr[1]);
                                        if ($dflt > 0)
                                            print " CHECKED ";
                                    } else if ($lignes->dflt > 0)
                                        print " CHECKED ";
                                }
                            }
                        }
                        //Si value checked et est un type etoile
                        if ($lignes->type->valueIsChecked == 1 && $iter > 1) {
                            //TODO si valeur du formulaire
                            if ($processDetId) {
                                $tmp = false;
                                if ($processDet->valeur->valeur[$htmlName]->valeur == $jtmp) {
                                    print " checked ";
                                }
                            } else {
                                if ($lignes->dflt . "x" != "x") {
                                    if (preg_match('/\[GLOBVAR\]([0-9]*)/', $lignes->dflt, $arr)) {
                                        $dflt = getGlobalVar($arr[1]);
                                        if ($dflt == $jtmp)
                                            print " CHECKED ";
                                    } else if ($lignes->dflt == $jtmp)
                                        print " CHECKED ";
                                }
                            }
                        }

                        print " class='" . $lignes->type->cssClass . " " . $lignes->cssClass->valeur . " " . ($lignes->src->uniqElem->OptGroup . "x" != "x" ? " double noSelDeco " : "") . "' ";
                        if (count($lignes->style) > 0) {
                            print " style='";
                            foreach ($lignes->style as $key => $val) {
                                if ($val->valeur . "x" != "x")
                                    print $val->element_name . ":" . $val->valeur . " ;";
                            }
                            print "' ";
                        }
                        if ($iter > 1) {
                            print " value='" . $jtmp . "' ";
                        }
                        print " >";
                        if ($lignes->type->code == "autocomplete") {
                            print "<input name='" . $htmlName . "-autocomplete' id='" . $htmlId . "-autocomplete' type='hidden'>";
                        }
                        if ($lignes->type->descriptionInsideTag == 1 && $lignes->type->hasDescription == 1) {
                            $description = $lignes->description;
                            eval("\$description = \"$description\";");
                            print "<em>" . $description . "</em><br>";
                        }
                        if ($lignes->type->titleInsideTag == 1 && $lignes->type->hasTitle == 1) {
                            $label = $lignes->label;
                            eval("\$label = \"$label\";");
                            print $label;
                        }
                        if ($lignes->label . "x" != "x" && $lignes->type->titleInLegend == 1) {
                            $label = $lignes->label;
                            eval("\$label = \"$label\";");
                            print "<legend>" . $label . "</legend>";
                        }

                        if ($lignes->type->sourceIsOption == 1 && $lignes->type->hasSource > 0 && $lignes->src) {
                            //Get Source
                            switch ($lignes->src->type) {
                                case "r": {
                                        if ($lignes->src->uniqElem->OptGroup . "x" != "x") {
                                            $lignes->src->requete->getValues();
                                            //var_dump($lignes->src->requete);
                                            foreach ($lignes->src->requete->valuesGroupArr as $keyOptGrp => $valOptGrp) {
                                                print "<OPTGROUP label='" . $valOptGrp['label'] . "'>";
                                                foreach ($valOptGrp['data'] as $key1OptGrp => $val1OptGrp) {
                                                    $valCompare = $lignes->dflt;
                                                    if ($lignes->dflt . "x" != "x") {
                                                        if (preg_match('/\[GLOBVAR\]([0-9]*)/', $lignes->dflt, $arr)) {
                                                            $dflt = getGlobalVar($arr[1]);
                                                            $valCompare = $dflt;
                                                        }
                                                    }

                                                    if ($processDetId > 0) {
                                                        $valCompare = $processDet->valeur->valeur[$htmlName]->valeur;
                                                    }
                                                    if ($valCompare == $key1OptGrp) {
                                                        print "<OPTION SELECTED value='" . $key1OptGrp . "'>" . $val1OptGrp . "</OPTION>";
                                                    } else {
                                                        print "<OPTION value='" . $key1OptGrp . "'>" . $val1OptGrp . "</OPTION>";
                                                    }
                                                }
                                                print "</OPTGROUP>";
                                            }
                                        } else {
                                            foreach ($lignes->src->uniqElem->getValues() as $key => $val) {
                                                //Si key == dfltValue && value is Selected
                                                //TODO si valeur autre que default
                                                $valCompare = $lignes->dflt;
                                                if ($lignes->dflt . "x" != "x") {
                                                    if (preg_match('/\[GLOBVAR\]([0-9]*)/', $lignes->dflt, $arr)) {
                                                        $valCompare = getGlobalVar($arr[1]);
                                                    }
                                                }
                                                if ($processDetId > 0 && $processDet->valeur->valeur[$htmlName]->valeur . "x" != "x") {
                                                    $valCompare = $processDet->valeur->valeur[$htmlName]->valeur;
                                                }

                                                if ($valCompare == $key) {
                                                    print "<option SELECTED value='" . $key . "'>" . $val . "</option>";
                                                } else {
                                                    print "<option value='" . $key . "'>" . $val . "</option>";
                                                }
                                            }
                                        }
                                    }
                                    break;
                                case "g": {
                                        //Si key == dfltValue && value is Selected
                                        foreach ($lignes->src->uniqElem->getValues() as $key => $val) {
                                            //TODO si valeur autre que default
                                            $valCompare = $lignes->dflt;
                                            if ($lignes->dflt . "x" != "x") {
                                                if (preg_match('/\[GLOBVAR\]([0-9]*)/', $lignes->dflt, $arr)) {
                                                    $dflt = getGlobalVar($arr[1]);
                                                    $valCompare = $dflt;
                                                }
                                            }

                                            if ($processDetId > 0) {
                                                $valCompare = $processDet->valeur->valeur[$htmlName]->valeur;
                                            }
                                            if ($valCompare == $key) {
                                                print "<option SELECTED value='" . $key . "'>" . $val . "</option>";
                                            } else {
                                                print "<option value='" . $key . "'>" . $val . "</option>";
                                            }
                                        }
                                    }
                                    break;
                                case "l": {
                                        //Si key == dfltValue && value is Selected
                                        foreach ($lignes->src->uniqElem->getValues() as $key => $val) {
                                            //TODO si valeur autre que default
                                            $valCompare = $lignes->dflt;
                                            if ($lignes->dflt . "x" != "x") {
                                                if (preg_match('/\[GLOBVAR\]([0-9]*)/', $lignes->dflt, $arr)) {
                                                    $dflt = getGlobalVar($arr[1]);
                                                    $valCompare = $dflt;
                                                }
                                            }

                                            if ($processDetId > 0) {
                                                $valCompare = $processDet->valeur->valeur[$htmlName]->valeur;
                                            }
                                            if ($valCompare == $key) {
                                                print "<option SELECTED value='" . $key . "'>" . $val . "</option>";
                                            } else {
                                                print "<option value='" . $key . "'>" . $val . "</option>";
                                            }
                                        }
                                    }
                                    break;
                            }
                            //Print
                        }
                    }
                    //Si value is in Tag
                    if ($lignes->type->valueInTag == 1) {
                        $valDisplay = "";

                        if ($lignes->dflt . "x" != "x") {
                            if (preg_match('/\[GLOBVAR\]([0-9]*)/', $lignes->dflt, $arr)) {
                                $dflt = getGlobalVar($arr[1]);
                                $valDisplay = $dflt;
                            } else {
                                $valDisplay = $lignes->dflt;
                            }
                        }
                        if ($processDetId > 0 && $processDet->valeur->valeur[$htmlName]->valeur . "x" != "x") {
                            $valDisplay = $processDet->valeur->valeur[$htmlName]->valeur;
                        }
                        print $valDisplay;
                    }
                    if ($lignes->type->endNedded == 1) {
                        print $lignes->type->htmlEndTag;
                    }
                    print ($lignes->src->uniqElem->OptGroup . "x" != "x" ? "<td><div id='" . $htmlId . "_jDS_Dest_2'></div></table>" : "");
                    if ($lignes->type->code == 'autocomplete') {
                        if ($htmlId . 'x' == 'x' && $htmlName . 'x' == 'x')
                            $htmlId = SynSanitize(($lignes->label . "x" == "x" ? "JDS" . $lignes->id : $lignes->label));
                        else if ($htmlId . 'x' == 'x')
                            $htmlId = $htmlName;
                        $tmpHtml = "<script>";
                        $tmpHtml .= "jQuery(document).ready(function(){";
                        $tmpHtml .= 'jQuery("input#' . $htmlId . '").autocomplete("' . DOL_URL_ROOT . '/Synopsis_Process/ajax/autocomplete-json.php?type=' . $lignes->id . '",';
                        $tmpHtml .= <<<EOF
                                           {minChar: 2,
                                            delay: 400,
                                            width: 260,
                                            dataType: "json",
                                            selectFirst: false,
                                            formatItem: function(data, i, max, value, term) {
                                                        return value;
                                                    },
                                             parse: function(data) {
                                                    var mytab = new Array();
                                                    for (var i = 0; i < data.length; i++) {
                                                        var myres = data[i].label;
                                                        var myvalue = data[i].label;
                                                        mytab[mytab.length] = { data: data[i], value: myvalue, result: myres };
                                                    }
                                                    return mytab;
                                             },
                                            modifAutocompleteSynopsisReturnSelId: function(selected)
                                            {
                                                var selId = selected.data['id'];
EOF;
                        $tmpHtml .= "jQuery('#" . $htmlId . "-autocomplete').val(selId) ;";
                        $tmpHtml .= <<<EOF
                                            }
                                        });
EOF;
                        $tmpHtml .= '});';
                        $tmpHtml .= "</script>";
                        $arrJs2[$lignes->type->code] = $tmpHtml;
                    }
                }
                if ($lignes->type->repeatTag > 0) {
                    print "</div>";
                }
            }
            if (is_array($arrJs2) && count($arrJs2) > 0)
                print join(' ', $arrJs2);
            $arrJs2 = array();
            if ($xmpMode == 1)
                print "</xmp>";
        }
        print "</div>";
    } else {
        print "<div class='error ui-state-error'>Probleme technique</div>";
    }

    if ($element_id) {
        print "</form>";
    }

    if ($element_id && !$_REQUEST['fromIframe'] == 1) {
        $process->getGlobalRights();
        $hasModRight = false;
        $tmp = 'process' . $process->id;
        if ($user->rights->process->modifier || $user->rights->process_user->$tmp->modifier) {
            $hasModRight = true;
        }
        print "<table width=100%>";
        if (!($processDetId > 0 && $processDet->fk_statut == 999))
            print "<tr><td align=center class='ui-state-default'>";
        if ($processDet->fk_statut == 0 && $hasModRight) {
            print "<button id='saveButton' class='butAction' >Sauvegarder</button><button class='butActionDelete' onClick='jQuery(\"#formProcess\")[0].reset();return (false);'>Reset</button>";
        }
        if ($processDet->fk_statut == 0)
            print "<button id='askValid' class='butAction'>Demande de validation</button>";
        if ($processDet->fk_statut == 3 && $process->revision_model_refid > 0)
            print "<button id='revButton' class='butAction' >R&eacute;viser</button>";
        else if ($processDet->fk_statut == 3 && $hasModRight)
            print "<button id='modAfterValidButton' class='butAction' >Modifier</button>";
//Bouton de validation
        if ($processDetId > 0 && $processDet->fk_statut == 999) {
            //Cas 1/ droit de valider tous :> valid tout
            //Cas 2/ droit de valider par role :> valid 1
            //Cas 3/ droit de valider tous + valider par role
            //Cas 4/ pas de droit :> affiche rien?
            $requete = " SELECT *
                           FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def
                          WHERE active = 1
                            AND isValidationRight = 1
                            AND isValidationForAll = 1";
            $sql = $db->query($requete);
            $multipleValidation = true;
            $tmp = 'process' . $process->id;
            if ($db->num_rows($sql) > 0) {

                require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
                $formHtml = new Form($db);
                $value = '';
                $option = 1;
                $runOnce = true;
                while ($res = $db->fetch_object($sql)) {
                    $code = $res->code;
                    if ($user->rights->process->valider || $user->rights->process_user->$tmp->$code) {
                        if ($runOnce) {
                            print "<tr><th style='padding:15px;' colspan=4 class='ui-widget-header ui-state-hover'>Validation";
                            $runOnce = false;
                            $resValue = false;
                            $requeteValue = "SELECT *
                                               FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation,
                                                    " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def as d
                                              WHERE processdet_refid = " . $processDetId . "
                                                AND validation_number=" . $processDet->validation_number . "
                                                AND validation_type_refid = d.id
                                                AND d.isValidationForAll <> 1
                                                AND d.isValidationRight = 1";
                            $sqlValue = $db->query($requeteValue);

                            $formHtml = new Form($db);
                            $code = $res->code;
                            if ($db->num_rows($sqlValue) > 0)
                            while ($resValue = $db->fetch_object($sqlValue)) {
                                print "<tr style='line-height:40px;'><th class='ui-widget-header ui-state-default'>" . $resValue->label;
                                $tmpUser = new User($db);
                                $tmpUser->fetch($resValue->user_refid);
                                print "<td align=right class='ui-widget-content'>" . $resValue->note;
                                print "<td align=center class='ui-widget-content' colspan=2>";
                                if ($resValue->valeur == 1) {
                                    print img_picto($langs->trans("Active"), 'tick') . " Valider le " . date('d/m/Y', strtotime($resValue->dateValid)) . " par " . $tmpUser->getNomUrl(1);
                                } else {
                                    print img_error() . " Refuser le " . date('d/m/Y', strtotime($resValue->dateValid)) . " par " . $tmpUser->getNomUrl(1);
                                }
                            }
                        }
                        $multipleValidation = false;
                        print "<tr><th class='ui-widget-header ui-state-default'>Note</th>
                                   <th class='ui-widget-header ui-state-default'>Validation</th>
                                   <th class='ui-widget-header ui-state-default'>Action</th>
                               <tr><td colspan=1 align=right class='ui-widget-content'><textarea style='width:100%;'></textarea>
                                   <td colspan=1 align=center class='ui-widget-content'>";
                        print $formHtml->selectyesno("validation-" . $code, $value, $option, '');
                        print "    <td colspan=1 align=center class='ui-widget-content'>";
                        print "        <button onClick='validation_Form(this); return(false);' class='butAction' id='" . $res->code . "'>" . $res->label . "</button>";
                    }
                }
            }
            if ($multipleValidation) {

                $requete = " SELECT *
                               FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def
                              WHERE active = 1
                                AND isValidationRight = 1
                                AND isValidationForAll <> 1";
                $sql = $db->query($requete);

                if ($db->num_rows($sql) > 0) {
                    print "<br/>";
                    print "<table width=100%>";
                    print "<tr><th style='padding:15px;' colspan=4 class='ui-widget-header ui-state-hover'>Validation";
                    require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
                    while ($res = $db->fetch_object($sql)) {
                        $resValue = false;
                        $requeteValue = "SELECT *
                                           FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation
                                          WHERE processdet_refid = " . $processDetId . "
                                            AND validation_number=" . $processDet->validation_number . "
                                            AND validation_type_refid = " . $res->id;
                        $sqlValue = $db->query($requeteValue);
                        if ($db->num_rows($sqlValue) > 0)
                            $resValue = $db->fetch_object($sqlValue);

                        $formHtml = new Form($db);
                        $code = $res->code;
                        if ($resValue) {
                            print "<tr style='line-height:40px;'><th class='ui-widget-header ui-state-default'>" . $res->label;
                            $tmpUser = new User($db);
                            $tmpUser->fetch($resValue->user_refid);
                            print "<td align=right class='ui-widget-content'>" . $resValue->note;
                            print "<td align=center class='ui-widget-content' colspan=2>";
                            if ($resValue->valeur == 1) {
                                print img_picto($langs->trans("Active"), 'tick') . " Valider le " . date('d/m/Y', strtotime($resValue->dateValid)) . " par " . $tmpUser->getNomUrl(1);
                            } else {
                                print img_error() . " Refuser le " . date('d/m/Y', strtotime($resValue->dateValid)) . " par " . $tmpUser->getNomUrl(1);
                            }
                        } else {
                            if ($user->rights->process_user->$tmp->$code) {
                                print "<tr><th class='ui-widget-header ui-state-default'>" . $res->label;
                                $value = '';
                                $option = 1;
                                print "<td align=right class='ui-widget-content'><textarea style='width:100%;'></textarea>";
                                print "<td align=right class='ui-widget-content'>" . $formHtml->selectyesno("validation-" . $res->code, $value, $option, '');
                                print "<td align=center class='ui-widget-content'><button onClick='validation_Form(this); return(false);' class='butAction' id='" . $res->code . "'>OK</button>";
                            } else {
                                print "<tr><th class='ui-widget-header ui-state-default'>" . $res->label;
                                $value = '';
                                $option = 1;
                                print "<td colspan=3 style='line-height:40px;' align=right class='ui-widget-content'>En attente de validation";
                            }
                        }
                    }
                }
            }
        }
        print "</table>";
    }

    $eval = $process->posttraitement;
    if ($eval . "x" != "x")
        eval($eval);
}

function getGlobalVar($globId) {
    global $db;
    $globalvar = new globalvar($db);
    $globalvar->fetch($globId);
    return($globalvar->glabalVarEval);
}

//$c = memory_get_usage(1);
//print "<br/>";
//print $a ." ". round($a*100/1024)/100 ."ko ".round($a*100/(1024*1024))/100 ."Mo <br/>";
//print $b ." ". round($b*100/1024)/100 ."ko ".round($b*100/(1024*1024))/100 ."Mo <br/>";
//print $c ." ". round($c*100/1024)/100 ."ko ".round($c*100/(1024*1024))/100 ."Mo <br/>";
llxFooter('$Date: 2007/05/28 11:51:00 $ - $Revision: 1.6 $');
?>

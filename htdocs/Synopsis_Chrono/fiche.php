<?php

/*

 * Name : fiche.php
 * GLE-1.2
 */

require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Chrono/Chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/synopsis_chrono.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
//  require_once('Var_Dump.php');
//Var_Dump::Display($_REQUEST);
$id = $_REQUEST['id'];
$action = $_REQUEST['action'];
$upload_dir = $conf->synopsischrono->dir_output . "/" . $id;

$js = "";

$langs->load("chrono@Synopsis_Chrono");
$msg = "";

if ($action == 'setprojet') {
    $db->query("UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono SET projetid = '" . $_REQUEST['projet'] . "' WHERE id = " . $id);
}

if ($action == 'setprop') {
    $db->query("UPDATE " . MAIN_DB_PREFIX . "Synopsis_Chrono SET propalid = '" . $_REQUEST['prop'] . "' WHERE id = " . $id);
}

if ($action == 'supprimer') {
    $chr = new Chrono($db);
    $chr->fetch($id);
    $tmpChr = 'chrono' . $chr->model_refid;
    $rightChrono = $user->rights->chrono_user->$tmpChr;

    if ($user->rights->synopsischrono->Supprimer || $rightChrono->supprimer) {
        $res = $chr->supprimer($id);
        if ($res > 0) {
            header('Location: liste.php');
        } else {
            header('Location: fiche.php?id=' . $id);
        }
    }
}


if ($action == 'multiValider') {
    $chr = new Chrono($db);
    $chr->fetch($_REQUEST['id']);
    $def = $_REQUEST['def'];
    $note = addslashes($_REQUEST['note']);
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def WHERE id = " . $def;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $val = $_REQUEST[$res->code];
    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Chrono_Multivalidation
                            (user_refid, chrono_refid,validation,right_refid,validation_number,note)
                     VALUES (" . $user->id . "," . $id . "," . $val . "," . $def . "," . ($chr->validation_number > 0 ? $chr->validation_number : "NULL") . ",'" . $note . "')";
//print $requete;
    $sql = $db->query($requete);
    $res = $chr->multivalidate();
}
if ($action == 'Valider') {
    $chr = new Chrono($db);
    $chr->fetch($_REQUEST['id']);
    $res = false;
    if ($chr->statut == 999) {
        //On multivalide les manquants
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def WHERE isValidationRight = 1 AND isValidationForAll <> 1";
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql)) {
            $requete1 = "SELECT *
                               FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_Multivalidation
                              WHERE validation_number " . ($chr->validation_number > 0 ? ' = ' . $chr->validation_number : " IS NULL ") . "
                                AND right_refid = " . $res->id . "
                                AND chrono_refid = " . $_REQUEST['id'] . "
                            ";
            $sql1 = $db->query($requete1);
            if ($db->num_rows($sql1) > 0)
                continue;
            else {
                $requete2 = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Chrono_Multivalidation
                                           (user_refid, chrono_refid,validation,right_refid,validation_number)
                                    VALUES (" . $user->id . "," . $_REQUEST['id'] . "," . $_REQUEST['value'] . "," . $res->id . "," . ($chr->validation_number > 0 ? $chr->validation_number : "NULL") . ")";
                $sql2 = $db->query($requete2);
            }
        }
        $res = $chr->multivalidate();
    } else {
        $res = $chr->validate();
    }
    if ($res > 0) {
        header('location: fiche.php?id=' . $chr->id);
    } else {
        $msg = "Erreur de mise &agrave; jour";
    }
}
if ($action == 'AskValider') {
    $chr = new Chrono($db);
    $chr->fetch($_REQUEST['id']);
    $res = $chr->attenteValidate();
    if ($res > 0) {
        header('location: fiche.php?id=' . $chr->id);
    } else {
        $msg = "Erreur de mise &agrave; jour";
    }
}

if ($action == 'ModifyAfterValid') {
    //Si chrono revisable
    $chr = new Chrono($db);
    $chr->fetch($_REQUEST['id']);
    if ($chr->model->hasRevision) {
        if ($chr->model->revision_model_refid > 0) {
            $res = $chr->revised();
            if ($res > 0) {
                header("Location: fiche.php?id=" . $res);
            } else {
                header("Location: fiche.php?id=" . $_REQUEST['id']);
            }
        }
    } else {
        //Sinon mode normal
        $res = $chr->unvalidate();
        $action = 'Modify';
        $_REQUEST['action'] = 'Modifier';
    }
}

if ($action == 'modifier') {
    $chr = new Chrono($db);
    $chr->id = $_REQUEST['id'];
    $chr->description = addslashes($_REQUEST['description']);
    $chr->socid = addslashes($_REQUEST['socid']);
    $chr->contactid = addslashes($_REQUEST['contactid']);
    $chr->propalid = addslashes($_REQUEST['Proposition comm.']);
    $chr->projetid = addslashes($_REQUEST['Projet']);

//Extra Value

    $res = $chr->update($chr->id);
    $dataArr = array();
    foreach ($_REQUEST as $key => $val) {
        if (preg_match('/^Chrono-([0-9]*)$/', $key, $arrTmp)) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key, " . MAIN_DB_PREFIX . "Synopsis_Chrono_key_type_valeur WHERE " . MAIN_DB_PREFIX . "Synopsis_Chrono_key_type_valeur.id = " . MAIN_DB_PREFIX . "Synopsis_Chrono_key.type_valeur AND " . MAIN_DB_PREFIX . "Synopsis_Chrono_key.id = " . $arrTmp[1];
            $sql = $db->query($requete);
            if ($sql)
                $res = $db->fetch_object($sql);
            if ($sql && $res->valueIsChecked == 1 && ($val == 'on' || $val == 'On' || $val == 'oN' || $val == 'ON'))
                $dataArr[$arrTmp[1]] = 1;
            else if ($sql && $res->valueIsChecked == 1)
                $dataArr[$arrTmp[1]] = 0;
            else
                $dataArr[$arrTmp[1]] = addslashes($val);
        }
    }
    $res1 = $chr->setDatas($chr->id, $dataArr);

    if ($res > 0) {
        header('location:fiche.php?id=' . $id);
    } else {
        $msg = "Erreur dans la mise &agrave; jour";
    }
}
// Suppression fichier
if ($action == 'confirm_deletefile' && $_REQUEST['confirm'] == 'yes') {
    $file = $upload_dir . "/" . urldecode($_GET["urlfile"]);
    dol_delete_file($file);
    //TODO
    $tmpName = $_FILES['userfile']['name'];
    //decode decimal HTML entities added by web browser
    $tmpName = dol_unescapefile($tmpName);
    // Appel des triggers
    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
    $interface = new Interfaces($db);
    $interface->texte = $tmpName;
    $result = $interface->run_triggers('ECM_UL_DEL_CHRONO', $chrono, $user, $langs, $conf);
    if ($result < 0) {
        $error++;
        $errors = $interface->errors;
    }
    // Fin appel triggers

    $mesg = '<div class="ok">' . $langs->trans("FileWasRemoved") . '</div>';
}
if ($action == "Modify" || $action == "ModifyAfterValid") {
    $js = "<script>";
    if ($conf->global->COMPANY_USE_SEARCH_TO_SELECT) {
        $js .= <<< EOF
          function ajax_updater_postFct(socid)
          {
              if (socid > 0)
              {
                    jQuery.ajax({
                        url:"ajax/contactSoc-xml_response.php",
                      type:"POST",
                      datatype:"xml",
                      data:"socid="+socid,
                      success: function(msg){
                            jQuery('#contactSociete').replaceWith("<div id='contactSociete'>"+jQuery(msg).find('contactsList').text()+"</div>");
                            jQuery('#contactSociete').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });
                      }
                    });
              } else {
                jQuery('#contactSociete').replaceWith("<div id='contactSociete'></div>")
              }
          }
EOF;
    } else {
        $js .= <<< EOF
          jQuery(document).ready(function(){
            jQuery('#socid').change(function(){
              var socid = jQuery(this).find(':selected').val();
              if (socid > 0)
              {
                    jQuery.ajax({
                      url:"ajax/contactSoc-xml_response.php",
                      type:"POST",
                      datatype:"xml",
                      data:"socid="+socid,
                      success: function(msg){
                          jQuery('#contactSociete').replaceWith("<div id='contactSociete'>"+jQuery(msg).find('contactsList').text()+"</div>");
                          jQuery('#contactSociete').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });
                      }
                    });
              } else {
                jQuery('#contactSociete').replaceWith("<div id='contactSociete'></div>")
              }
            });
          });
EOF;
    }

    $js .= <<< EOF
      jQuery(document).ready(function(){
        jQuery.validator.addMethod(
            'required',
            function(value, element) {
                return (value+"x"!="x");
            },
            '<br/>Ce champs est requis'
        );
        jQuery('.datepicker').datepicker({ showTime : false});
        jQuery('.datetimepicker').datepicker({ showTime : false});

        jQuery('#form').validate();
      });
EOF;
    $js .= "</script>";
}
$js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.jDoubleSelect.js'></script>";
$js .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.js"></script>' . "\n";

//launchRunningProcess($db,'Chrono',$_GET['id']);

llxHeader($js, 'Fiche chrono');
print "<div class='titre'>Fiche chrono</div><br/>";

require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/modele/revision_merlot.class.php");
$conv = new revision_merlot($db);

if ($msg . "x" != 'x') {
    print "<div style='padding: 3px;'><span class='ui-icon ui-icon-info' style='float: left;'></span>" . $msg . "</div>";
}
$chr = new Chrono($db);
if ($id > 0) {
    $chr->fetch($id);
    $tmpChr = 'chrono' . $chr->model_refid;
    $rightChrono = $user->rights->chrono_user->$tmpChr;

    if (!($rightChrono->voir == 1 || $user->rights->synopsischrono->read == 1)) {

        accessforbidden("Ce type de chrono ne vous est pas accessible", 0);
        exit;
    }


    //saveHistoUser($chr->id, "chrono",$chr->ref);

    $head = chrono_prepare_head($chr);
    $html = new Form($db);
    dol_fiche_head($head, 'chrono', $langs->trans("Chrono"));

    if ($_GET['action'] == 'delete') {
        $html->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $_GET["id"] . '&amp;urlfile=' . urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
        print '<br>';
    }

    if ($action == "Modify" && $user->rights->synopsischrono->Modifier) {
        print "<form id='form' action='fiche.php?id=" . $chr->id . "' method=post>";
        print "<input type='hidden' name='action' value='modifier'>";
        print "<input type='hidden' name='id' value='" . $chr->id . "'>";
        print "<table id='chronoTable' width=100%; class='ui-state-default' style='border-collapse: collapse;' cellpadding=15>";
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Ref') . '</th>
                     <td colspan=1 class=" ui-widget-content" >' . $chr->getNomUrl(1) . '</td>
                     <th colspan=1 class=" ui-widget-header ui-state-default" >Type</th>
                     <td colspan=1 class=" ui-widget-content" >' . $chr->model->titre . '</td>';
        if ($chr->model->hasSociete == 1) {
            print '<tr><th colspan=1 class="ui-state-default ui-widget-header" >' . $langs->trans('Company') . '</th>';
            if ($chr->model->hasContact == 1)
                print '    <td  class="ui-widget-content" colspan="1">' . $html->select_company($chr->socid, 'socid', 1, false, "") . '</td>';
            else
                print '    <td  class="ui-widget-content" colspan="3">' . $html->select_company($chr->socid, 'socid', 1, false, "") . '</td>';
        }
        if ($chr->model->hasContact == 1) {
            if (!$chr->model->hasSociete == 1)
                print '<tr>';
            print '    <th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">';
            print $langs->trans('Contact') . '</th>';
            $tmpContact = "";
            if ($chr->socid > 0) {
                if ($chr->contactid > 0) {
                    $html->select_contacts($chr->socid, $chr->contactid, 'contactid', 1, '', false);
                    $tmpContact = $html->tmpReturn;
                } else {
                    $html->select_contacts($chr->socid, '', 'contactid', 1, '', false);
                    $tmpContact = $html->tmpReturn;
                }
            } else if ($chr->contactid > 0) {
                $html->select_contacts(-1, $chr->contactid, 'contactid', 1, '', false);
                $tmpContact = $html->tmpReturn;
            }
            if ($chr->model->hasSociete == 1)
                print '    <td  class="ui-widget-content" colspan="1"><div id="contactSociete">' . $tmpContact . '</div></td>';
            else
                print '    <td  class="ui-widget-content" colspan="3"><div id="contactSociete">' . $tmpContact . '</div></td>';
        }

        print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Description';
        print '    <td  class="ui-widget-content" colspan="3"><textarea style="width: 98%; min-height: 8em;" class="required" name="description">' . $chr->description . '</textarea></td>';

//Ajoute les extra key/Values
        $requete = "SELECT k.nom,
                           k.id,
                           v.`value`,
                           t.nom as typeNom,
                           t.hasSubValeur,
                           t.subValeur_table,
                           t.subValeur_idx,
                           t.subValeur_text,
                           t.htmlTag,
                           t.htmlEndTag,
                           t.endNeeded,
                           t.cssClass,
                           t.cssScript,
                           t.jsCode,
                           t.valueIsChecked,
                           t.valueIsSelected,
                           t.valueInTag,
                           t.valueInValueField,
                           t.sourceIsOption,
                           k.type_subvaleur,
                           k.extraCss,
                           t.phpClass
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key_type_valeur AS t,
                           " . MAIN_DB_PREFIX . "Synopsis_Chrono_key AS k
                      LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Chrono_value AS v ON v.key_id = k.id AND v.chrono_refid = " . $chr->id . "
                     WHERE t.id = k.type_valeur
                       AND k.model_refid = " . $chr->model_refid;
        //print $requete;
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql)) {
            print '<tr><th class="ui-state-default ui-widget-header" nowrap class="ui-state-default">' . $res->nom;
            print '    <td  class="ui-widget-content" colspan="3">';
            if ($res->hasSubValeur == 1) {
                if ($res->sourceIsOption) {
                    $tag = preg_replace('/>$/', "", $res->htmlTag);
                    $html = "";
                    $html .= $tag;
                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
                    $tmp = $res->phpClass;
                    $obj = new $tmp($db);
                    $obj->fetch($res->type_subvaleur);
                    $obj->getValues();
                    $extra_extraClass = "";
                    if ($obj->OptGroup . "x" != "x") {
                        $extra_extraClass = " double noSelDeco ";
                        print <<<EOF
                      <script>
jQuery(document).ready(function(){
EOF;
                        print "jQuery('#Chrono-" . $res->id . "').jDoubleSelect({\n";
                        print <<<EOF
        text:'',
        finish: function(){
EOF;
                        print " /*jQuery('#Chrono-" . $res->id . "_jDS').selectmenu({\n";
                        print <<<EOF
                style:'dropdown',
                maxHeight: 300
            });*/
        },
        el1_change: function(){
EOF;
                        print " /*jQuery('#Chrono-" . $res->id . "_jDS_2').selectmenu({\n";
                        print <<<EOF
                style:'dropdown',
                maxHeight: 300
            });*/
        },
EOF;
                        print "el2_dest: jQuery('#destChrono-" . $res->id . "'),\n";
                        print <<<EOF
    });
});

                      </script>
EOF;
                    }
                    if ($res->extraCss . $res->cssClass . $extra_extraClass . "x" != "x") {
                        $html .= " class='" . $res->cssClass . " " . $res->extraCss . " " . $extra_extraClass . "' ";
                    }
                    if ($res->valueInValueField) {
                        $html .= " value='" . $res->value . "' ";
                    }
                    if ($res->valueIsChecked) {
                        $html .= ($res->value == 1 ? " CHECKED " : "");
                    }
                    $html .= " name='Chrono-" . $res->id . "' ";
                    $html .= " id='Chrono-" . $res->id . "' ";
                    $html.=">";
                    if ($res->valueInTag) {
                        $html .= $res->value;
                    }
                    $remOpt = false;
                    if ($obj->OptGroup . "x" != "x") {
                        $html = "<table><tr><td width=50%>" . $html;
                        foreach ($obj->valuesGroupArr as $key => $val) {
                            $html .= "<OPTGROUP label='" . $val['label'] . "'>";
                            foreach ($val['data'] as $key1 => $val1) {
                                $html .= "<OPTION " . ($res->valueIsSelected && $res->value == $key1 ? "SELECTED" : "") . " value='" . $key1 . "'>" . $val1 . "</OPTION>";
                            }
                            $html .= "</OPTGROUP>";
                        }
                        $html .= "<td><div id='destChrono-" . $res->id . "'></div>";
                        $html .= "</table>";
                    } else {
                        foreach ($obj->valuesArr as $key => $val) {
                            $html .= "<OPTION " . ($res->valueIsSelected && $res->value == $key ? "SELECTED" : "") . " value='" . $key . "'>" . $val . "</OPTION>";
                        }
                    }
                    if ($res->endNeeded == 1)
                        $html .= $res->htmlEndTag;
                    print $html;
                } else {
                    //Beta
                    if ($res->phpClass == 'fct')
                        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
                    $tmp = $res->phpClass;
                    $obj = new $tmp($db);
                    $obj->fetch($res->type_subvaleur);
                    $obj->call_function_chronoModule($chr->model_refid, $chr->id);
                }
            } else {
                //Construct Form
                $tag = preg_replace('/>$/', "", $res->htmlTag);
                $html = "";
                $html .= $tag;
                if ($res->extraCss . $res->cssClass . "x" != "x") {
                    $html .= " class='" . $res->cssClass . " " . $res->extraCss . "' ";
                }
                if ($res->valueInValueField) {
                    $html .= " value='" . $res->value . "' ";
                }
                if ($res->valueIsChecked) {
                    $html .= ($res->value == 1 ? " CHECKED " : "");
                }
                $html .= " name='Chrono-" . $res->id . "' ";
                $html .= " id='Chrono-" . $res->id . "' ";
                $html.=">";
                if ($res->valueInTag) {
                    $html .= $res->value;
                }
                if ($res->endNeeded == 1)
                    $html .= $res->htmlEndTag;
                print $html;
            }
            print '</td>';
        }


        print '<tr><th align=right class="ui-state-default ui-widget-header" nowrap colspan=4  class="ui-state-default">';
        print "<button onClick='location.href=\"fiche.php?id=" . $chr->id . "\"; return(false);' class='butAction'>Annuler</button>";
        print "<button class='butAction'>Modifier</button>";
        print '</table>';
        print '</form>';
    } else {
        print "<table id='chronoTable' width=100%; class='ui-state-default' style='border-collapse: collapse;' cellpadding=15>";
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Ref') . '</th>
                     <td colspan=1 class=" ui-widget-content" >' . $chr->getNomUrl(1) . '</td>
                     <th colspan=1 class=" ui-widget-header ui-state-default" >Type</th>
                     <td colspan=1 class=" ui-widget-content" >' . $chr->model->titre . '</td>';
        if ($chr->societe && $chr->model->hasSociete == 1) {
            if ($chr->contact && $chr->model->hasContact == 1) {
                // Societe
                print '<tr><th colspan=1 class="ui-state-default ui-widget-header" >' . $langs->trans('Company') . '</th>';
                print '    <td  class="ui-widget-content" colspan="1">' . $chr->societe->getNomUrl(1) . '</td>';
            } else {
                // Societe
                print '<tr><th colspan=1 class="ui-state-default ui-widget-header" >' . $langs->trans('Company') . '</th>';
                print '    <td  class="ui-widget-content" colspan="3">' . $chr->societe->getNomUrl(1) . '</td>';
            }
        }

        if ($chr->contact && $chr->model->hasContact == 1) {
            if ($chr->societe && $chr->model->hasSociete == 1) {
                // Contact
                print '<th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">';
                print $langs->trans('Contact') . '</th>';
                print '    <td  class="ui-widget-content" colspan="1">' . $chr->contact->getNomUrl(1) . '</td>';
            } else {
                // Contact
                print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">';
                print $langs->trans('Contact') . '</th>';
                print '    <td  class="ui-widget-content" colspan="3">' . $chr->contact->getNomUrl(1) . '</td>';
            }
        }
        $chr->user_author->fetch($chr->user_author->id);

        print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Cr&eacute;er le';
        print '    <td  class="ui-widget-content" colspan="1">' . date('d/m/Y \&\a\g\r\a\v\e\; H:i', $chr->date) . '</td>';
        print '    <th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Par';
        print '    <td  class="ui-widget-content" colspan="1">' . $chr->user_author->getNomUrl(1) . '</td>';
        if ($chr->user_modif_id > 0) {
            $chr->user_modif->fetch($chr->user_modif->id);
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Derni&egrave;re modification le';
            print '    <td  class="ui-widget-content" colspan="1">' . date('d/m/Y \&\a\g\r\a\v\e\; H:i', $chr->date_modif) . '</td>';
            print '    <th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Par';
            print '    <td  class="ui-widget-content" colspan="1">' . $chr->user_modif->getNomUrl(1) . '</td>';
        }

        if ($chr->validation_number > 0 && $chr->statut != 2 && $chr->statut != 3) {
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Derni&egrave;re demande de validation :';
            print '    <td  class="ui-widget-content" colspan="3">';
            $requete = "SELECT d.label, m.user_refid, m.validation,m.tms,m.note
                            FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_Multivalidation as m,
                                 " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def as d
                           WHERE chrono_refid = " . $chr->id . "
                             AND d.id = m.right_refid
                             AND validation_number = " . ($chr->validation_number - 1);
            if ($chr->validation_number == 1) {
                $requete = "SELECT d.label, m.user_refid, m.validation,m.tms,m.note
                            FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_Multivalidation as m,
                                 " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def as d
                           WHERE chrono_refid = " . $chr->id . "
                             AND d.id = m.right_refid
                             AND validation_number IS NULL ";
            }
            $sql3 = $db->query($requete);
            while ($res3 = $db->fetch_object($sql3)) {
                print "<table width=100%>";
                print "<tr><td align=left  width=20%>" . $res3->label;
                print "    <td align=left>";
                $tmpUser = new User($db);
                $tmpUser->id = $res3->user_refid;
                $tmpUser->fetch();
                if ($res3->validation == 1) {
                    print img_tick("Valider");
                    print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res3->tms));
                } else {
                    print img_error("Non valider");
                    print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res3->tms));
                }
                print "<tr><td>&nbsp;<td colspan=1 align=leftt class='black' style='font-weight: normal;'>" . nl2br($res3->note);

                print "</table>";
            }
            //Derniere validation
            print '</td>';
        }

        print '<tr><th class="ui-widget-header ui-state-default">Proposition comm.
		<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=editprop">' . img_edit("Editer proposition comm.", 1) . '</a>';
        // print '<td colspan=1 class="ui-widget-content">';
        $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono,
                       " . MAIN_DB_PREFIX . "propal
                 WHERE " . MAIN_DB_PREFIX . "Synopsis_Chrono.propalid = " . MAIN_DB_PREFIX . "propal.rowid
                   AND " . MAIN_DB_PREFIX . "Synopsis_Chrono.id = " . $chr->id;
        // print "<table class='nobordernopadding' width=100%>";
        if ($_REQUEST['action'] == 'editprop') {
            print '<form name="editprop" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post">';
            print '<input type="hidden" name="action" value="setprop">';
            print "     <td class='ui-widget-content'><select name='prop'>";
            print "<OPTION value=''>S&eacute;lectionner-></OPTION>";
            $idT = '';
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    $idT = $res->rowid;
                }
            }
            $requete2 = "SELECT * FROM " . MAIN_DB_PREFIX . "propal ORDER BY `rowid` DESC";
            $sql2 = $db->query($requete2);
            while ($res = $db->fetch_object($sql2)) {
                print "<option value='" . $res->rowid . "'" . (($res->rowid == $idT) ? " selected=\"selected\"" : "") . ">" . $res->ref . "</option>";
            }
            print '<input type="submit" value="Modifier"/>';
            print "</form>";
        } else {
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    print "<td class='ui-widget-content'><a href='" . DOL_URL_ROOT . "/comm/propal.php?propalid=" . $res->rowid . "'>" . $res->ref . "</a></td>";
                }
            }
        }

        /* 	print '<tr><th class="ui-widget-header ui-state-default">Projet';
          $requete = "SELECT *
          FROM ".MAIN_DB_PREFIX."Synopsis_Chrono,
          ".MAIN_DB_PREFIX."propal, ".MAIN_DB_PREFIX."Synopsis_projet
          WHERE ".MAIN_DB_PREFIX."Synopsis_Chrono.propalid = ".MAIN_DB_PREFIX."propal.rowid
          AND ".MAIN_DB_PREFIX."propal.fk_projet = ".MAIN_DB_PREFIX."Synopsis_projet.rowid
          AND ".MAIN_DB_PREFIX."Synopsis_Chrono.id = ".$chr->id;
          if ($resql = $db->query($requete))
          {
          while ($res = $db->fetch_object($resql))
          {
          print "<td class='ui-widget-content'><a href='".DOL_URL_ROOT."/projet/fiche.php?id=".$res->rowid."'>".$res->title."</a></td>";
          //print "<td class='ui-widget-content'>azerttyy</a></td>";
          }
          } */





        print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Statut';
        print '    <td  class="ui-widget-content" colspan="3">' . $chr->getLibStatut(4) . '</td>';


        print '<tr><th class="ui-widget-header ui-state-default">Projet
		<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=editprojet">' . img_edit("Editer projet", 1) . '</a>
		<td class=\'ui-widget-content\'>';
        $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono,
                       " . MAIN_DB_PREFIX . "Synopsis_projet
                 WHERE " . MAIN_DB_PREFIX . "Synopsis_Chrono.projetid = " . MAIN_DB_PREFIX . "Synopsis_projet.rowid
                   AND " . MAIN_DB_PREFIX . "Synopsis_Chrono.id = " . $chr->id;
        // print "<table class='nobordernopadding' width=100%>";
        if ($_REQUEST['action'] == 'editprojet') {
            $requete3 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_projet ORDER BY `rowid` DESC";
            $sql3 = $db->query($requete3);
            print '<form name="editprojet" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post">';
            print '<input type="hidden" name="action" value="setprojet">';
            print "     <select name='projet'>";
            print "<OPTION value=''>S&eacute;lectionner-></OPTION>";
            $idT = '';
            if ($resql = $db->query($requete)) {
                while ($res2 = $db->fetch_object($resql)) {
                    $idT = $res2->rowid;
                }
            }
            while ($res = $db->fetch_object($sql3)) {
                print "<option value='" . $res->rowid . "'" . (($res->rowid == $idT) ? " selected=\"selected\"" : "") . ">" . $res->ref . " : " . $res->title . "</option>";
            }
            print '<input type="submit" value="Modifier"/>';
            print "</form>";
        } else {
            if ($resql = $db->query($requete)) {
                while ($res = $db->fetch_object($resql)) {
                    print "<a href='" . DOL_URL_ROOT . "/projet/fiche.php?id=" . $res->rowid . "'>" . $res->title . "</a>";
                }
            }
        }
        echo "</td>";

//print '    <td  class="ui-widget-content" colspan="3"><textarea style="width: 98%; min-height: 8em;" class="required" name="description">'.$chr->description.'</textarea></td>';
        print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Description';
        print '    <td  class="ui-widget-content" colspan="3">' . $chr->description . '</td>';

//Ajoute les extra key/Values
        $requete = "SELECT k.nom,
                           k.id,
                           v.`value`,
                           t.nom as typeNom,
                           t.hasSubValeur,
                           t.subValeur_table,
                           t.subValeur_idx,
                           t.subValeur_text,
                           t.htmlTag,
                           t.htmlEndTag,
                           t.endNeeded,
                           t.cssClass,
                           t.cssScript,
                           t.jsCode,
                           t.valueIsChecked,
                           t.valueIsSelected,
                           t.valueInTag,
                           t.valueInValueField,
                           t.sourceIsOption,
                           k.type_subvaleur,
                           t.phpClass
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key_type_valeur AS t,
                           " . MAIN_DB_PREFIX . "Synopsis_Chrono_key AS k
                      LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Chrono_value AS v ON v.key_id = k.id AND v.chrono_refid = " . $chr->id . "
                     WHERE t.id = k.type_valeur
                       AND k.model_refid = " . $chr->model_refid;
        //print $requete;
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql)) {
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">' . $res->nom;
            print '    <td  class="ui-widget-content" colspan="3">';
            if ($res->hasSubValeur == 1) {
                if ($res->sourceIsOption) {
                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
                    $tmp = $res->phpClass;
                    $obj = new $tmp($db);
                    $obj->fetch($res->type_subvaleur);
                    $obj->getValues();
                    $html = "";
                    foreach ($obj->valuesArr as $key => $val) {
                        if ($res->valueIsSelected && $res->value == $key) {
                            if ($obj->OptGroup . "x" != "x") {
                                $html .= $obj->valuesGroupArrDisplay[$key]['label'] . " - " . $val;
                                break;
                            } else {
                                $html = $val;
                                break;
                            }
                        }
                    }

                    print $html;
                } else {
                    //Beta
                    if ($res->phpClass == 'fct' || $res->phpClass == 'globalvar')
                        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
                    $tmp = $res->phpClass;
                    $obj = new $tmp($db);
                    $obj->fetch($res->type_subvaleur);
                    $obj->call_function_chronoModule($chr->model_refid, $chr->id);
                }
            } else {
                //Construct Form
                $html = "";
                if ($res->valueIsChecked && $res->value == 1) {
                    $html .= "OUI";
                } else if ($res->valueIsChecked && $res->value != 1) {
                    $html .= "NON";
                } else {
                    $html .= $res->value;
                }
                print $html;
            }
            print '</td>';
        }

        if (($user->rights->synopsischrono->Modifier || $rightChrono->modifier ) && $chr->statut == 0) {
            print '<tr><th align=right nowrap colspan=4  class="ui-state-default">';
            print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $chr->id . "&action=Modify\"'>Modifier</button>";
        } else if (($user->rights->synopsischrono->ModifierApresValide ) && $chr->statut > 0 && $chr->statut != 999) {
            print '<tr><th align=right nowrap colspan=4  class="ui-state-default">';
            if ($chr->model->hasRevision == 1 && $chr->model->revision_model_refid > 0 && $chr->statut != 3)
                print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $chr->id . "&action=ModifyAfterValid\"'>R&eacute;viser</button>";
            else if ($chr->model->hasRevision == 1 && $chr->statut != 3)
                print "<div class='ui-error error'>Pas de mod&egrave;le de r&eacute;visions !</div>";
            else if ($chr->model->hasRevision == 1 && $chr->statut == 3) {
//Affiche le dernier et le suivant
                $requete = "SELECT *
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono
                               WHERE orig_ref = '" . $chr->orig_ref . "'
                                 AND revision = " . ($chr->revision > 0 ? $chr->revision + 1 : 1);
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                if ($res->id > 0) {
                    print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $res->id . "\"'>R&eacute;vision suivante: " . $res->ref . "</button>";
                }
                $requete = "SELECT *
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono
                               WHERE orig_ref = '" . $chr->orig_ref . "'
                                 AND revision = (SELECT max(revision) FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono WHERE orig_ref='" . $chr->orig_ref . "')";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                if ($res->id > 0) {
                    print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $res->id . "\"'>Derni&egrave;re r&eacute;vision: " . $res->ref . "</button>";
                }
            } else if ($chr->statut != 3)
                print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $chr->id . "&action=ModifyAfterValid\"'>Modifier</button>";
        }
        $requete2 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def WHERE active=1 AND isValidationForAll = 1";
        $sql2 = $db->query($requete2);
        $hasRight = false;
        while ($res2 = $db->fetch_object($sql2)) {
            $tmp = $res2->code;
            if ($rightChrono->$tmp)
                $hasRight = true;
            if ($hasRight)
                break;
        }
        if (($user->rights->synopsischrono->Valider || $hasRight) && $chr->statut == 0) {

            //Validation totale
            if (!($user->rights->synopsischrono->Modifier || $rightChrono->modifier))
                print '<tr><th align=right nowrap colspan=4  class="ui-state-default">';
            print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $chr->id . "&action=Valider\"'>Valider</button>";
        } else {
            //Si droit de validation partiel
            $requete2 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def WHERE active=1 AND isValidationForAll <> 1 AND isValidationRight=1";
            $sql2 = $db->query($requete2);
            $hasRight = false;
            while ($res2 = $db->fetch_object($sql2)) {
                $tmp = $res2->code;
                if ($rightChrono->$tmp)
                    $hasRight = true;
                if ($hasRight)
                    break;
            }

            if ($hasRight && $chr->statut == 0) {

                if (!($user->rights->synopsischrono->Modifier || $rightChrono->modifier))
                    print '<tr><th align=right nowrap colspan=4  class="ui-state-default">';
                print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $chr->id . "&action=AskValider\"'>Demande de validation</button>";
            }
        }
        if ($chr->statut == 999) {
            print '<tr><th align=right class="ui-state-default" nowrap colspan=4 >';
            $requete3 = "SELECT *
                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def
                                WHERE isValidationForAll = 1
                                  AND active=1
                                  AND isValidationRight=1 ";
            $sql3 = $db->query($requete3);
            $hasAllRight = false;
            while ($res3 = $db->fetch_object($sql3)) {
                $tmp = $res3->code;
                if ($rightChrono->$tmp) {
                    $requete4 = "SELECT d.label, d.id, M.user_refid,M.tms, d.code, M.validation
                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def as d
                                    LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Chrono_Multivalidation as M ON M.right_refid = d.id AND M.validation_number " . ($chr->validation_number > 0 ? " = " . $chr->validation_number : "IS NULL") . " AND M.chrono_refid = " . $id . "
                                        WHERE isValidationForAll <> 1
                                          AND isValidationRight=1";
                    $sql4 = $db->query($requete4);
                    $html = new Form($db);

                    while ($res4 = $db->fetch_object($sql4)) {
                        $tmp = $res4->code;
                        if ($res4->validation . "x" != "x") {
                            print "<table width=80%>";
                            print "<tr><td align=left  width=20%>" . $res4->label;
                            print "<td align=right>";
                            $tmpUser = new User($db);
                            $tmpUser->id = $res4->user_refid;
                            $tmpUser->fetch();
                            if ($res4->validation == 1) {
                                print img_tick("Valider");
                                print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res4->tms));
                            } else {
                                print img_error("Non valider");
                                print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res4->tms));
                            }
                            print "</table>";
                        }
                    }

                    print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $chr->id . "&action=Valider&value=1&def=" . $res3->id . "\"'>" . $res3->label . "</button>";
                    print "<button class='butAction' onClick='location.href=\"fiche.php?id=" . $chr->id . "&action=Valider&value=0\"'>Invalider</button>";

                    $hasAllRight = true;
                }
            }
            if (!$hasAllRight) {
                $requete3 = "SELECT d.label, d.id, M.user_refid,M.tms, d.code, M.validation, M.note
                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_rights_def as d
                                LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Chrono_Multivalidation as M ON M.right_refid = d.id AND M.validation_number " . ($chr->validation_number > 0 ? " = " . $chr->validation_number : "IS NULL") . " AND M.chrono_refid = " . $id . "
                                    WHERE isValidationForAll <> 1
                                      AND isValidationRight=1";
                $sql3 = $db->query($requete3);
                $html = new Form($db);

                while ($res3 = $db->fetch_object($sql3)) {
                    $tmp = $res3->code;
                    if ($rightChrono->$tmp) {
                        $value = '';
                        if ($res3->validation . "x" != "x") {
                            print "<table width=80%>";
                            print "<tr><td align=left  width=20%>" . $res3->label;
                            print "<td align=right class='black' style='font-weight: normal;'>" . nl2br($res3->note);
                            print "<td align=right>";
                            $tmpUser = new User($db);
                            $tmpUser->id = $res3->user_refid;
                            $tmpUser->fetch();
                            if ($res3->validation == 1) {
                                print img_tick("Valider");
                                print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res3->tms));
                            } else {
                                print img_error("Non valider");
                                print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res3->tms));
                            }
                            print "</table>";
                        } else {
                            print "<form method='POST' action='fiche.php?id=" . $_REQUEST['id'] . "&def=" . $res3->id . "&action=multiValider'>";
                            print "<table width=80%>";
                            print "<tr><td align=left  width=20%>" . $res3->label;
                            print "<td align=right><textarea name='note'></textarea>";
                            print "<td align=right>";
                            print $html->selectyesno($res3->code, $value, 1, 'required');
                            print "<td align=right width=100>";
                            print "<button class='butAction'>OK</button>";
                            print "</table>";
                            print "</form>";
                        }
//                              print "<button class='butAction' onClick='location.href=\"fiche.php?id=".$chr->id."&action=valider&def=".$res3->id."\"'>".$res3->label."</button>";
                    } else {
                        if ($res3->validation . "x" != "x") {
                            print "<table width=80%>";
                            print "<tr><td align=left  width=20%>" . $res3->label;
                            print "<td align=right>" . nl2br($res3->note);
                            print "<td align=right>";
                            $tmpUser = new User($db);
                            $tmpUser->id = $res3->user_refid;
                            $tmpUser->fetch();
                            if ($res3->validation == 1) {
                                print img_tick("Valider");
                                print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res3->tms));
                            } else {
                                print img_error("Non valider");
                                print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res3->tms));
                            }
                            print "</table>";
                        } else {
                            print "<table width=80%>";
                            print "<tr><td align=left  width=20%>" . $res3->label;
                            print "    <td align=right>En attente de validation";
                            print "</table>";
                        }
                    }
                }
            }
        }
        if ($chr->statut == 0 && ($user->rights->synopsischrono->Supprimer || $rightChrono->supprimer) && !$chr->revision > 0) {
            if (!($user->rights->synopsischrono->Modifier || $rightChrono->modifier))
                print '<tr><th align=right nowrap colspan=4  class="ui-state-default">';
            print "<button class='butActionDelete' onClick='jQuery(\"#delDialog\").dialog(\"open\");return(false);'>Supprimer</button>";
        }


        print '</table>';

        print "<div id='delDialog'>" . img_error('') . " &Ecirc;tes vous sur de vouloir supprimer ce chrono ?</div>";
        print "<script>";
        print "var chronoId = " . $chr->id . ";";
        print <<<EOF
          jQuery(document).ready(function(){
                jQuery('#delDialog').dialog({
                    autoOpen: false,
                    width: 520,
                    minWidth: 520,
                    modal: true,
                    title: "Suppression de chrono",
                    buttons: {
                        OK: function(){
                            location.href='fiche.php?action=supprimer&id='+chronoId
                            jQuery('#delDialog').dialog('close');
                        },
                        Annuler: function(){
                            jQuery('#delDialog').dialog('close');
                        }
                    }
                });
          });
          </script>
EOF;

        if ($chr->model->hasFile == 1) {
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
            print "<br/><br/>";
            $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_ASC : SORT_DESC), 1);
            $formfile = new FormFile($db);
            // List of document
            $param = '&id=' . $chr->id;
            $formfile->list_of_documents($filearray, $chr, 'synopsischrono', $param, 1, $chr->id . "/");
        }
    }
} else {
    accessforbidden("Pas d'id de chrono", 0);
    exit;
}

llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');
?>
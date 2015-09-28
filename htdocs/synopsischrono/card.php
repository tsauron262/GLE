<?php

/*

 * Name : card.php
 * GLE-1.2
 */

require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/core/lib/synopsischrono.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
//  require_once('Var_Dump.php');
//Var_Dump::Display($_REQUEST);
$id = $_REQUEST['id'];
$action = $_REQUEST['action'];
$action2 = (isset($_REQUEST['action2']) ? $_REQUEST['action2'] : "");
$upload_dir = $conf->synopsischrono->dir_output . "/" . $id;

$js = "";

$langs->load("chrono@synopsischrono");
$msg = "";

if (!$id > 0 && isset($_REQUEST['ref'])) {
    $result = $db->query("SELECT id FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE ref LIKE '" . $_REQUEST['ref'] . "'");
    if ($db->num_rows($result) > 0) {
        $ligne = $db->fetch_object($result);
        $id = $ligne->id;
    }
}


if ($id > 0) {
    $chr = new Chrono($db);
    $chr->fetch($id);
    if (!$chr->id > 0){
        header('Location: ' . DOL_URL_ROOT . '/synopsischrono/listByObjet.php');
        die;
    }
    global $typeChrono;
    $typeChrono = $chr->model->id;
}

if ($action == 'addLnProp' && $chr->propalid && isset($_REQUEST['idprod']) && $_REQUEST['idprod'] > 0) {
    $prod = new Product($db);
    $prod->fetch($_REQUEST['idprod']);
    $prod->tva_tx = ($prod->tva_tx > 0) ? $prod->tva_tx : 0;
    require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
    $prodF = new ProductFournisseur($db);
    $prodF->find_min_price_product_fournisseur($prod->id, 1);
    $chr->propal->addline($prod->description, $prod->price, 1, $prod->tva_tx, 0, 0, $prod->id, $chr->societe->remise_percent, 'HT', null, null, null, null, null, null, $prodF->product_fourn_price_id, $prodF->fourn_price);
    $chr->propal->fetch($chr->propal->id);
    require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
    propale_pdf_create($db, $chr->propal, null, $langs);
}

if ($action == 'setprojet') {
    $db->query("UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET projetid = '" . $_REQUEST['projet'] . "' WHERE id = " . $id);
}

if ($action == 'setprop') {
    $db->query("UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET propalid = '" . $_REQUEST['prop'] . "' WHERE id = " . $id);
}

if ($action == 'createPC' && $chr->propal->id == 0) {
    $chr->createPropal();
}

if ($action == "cancel") {
    if ($chr->socid == 0 && $chr->description == '') {
//        $result = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_value WHERE `value` is not null AND `chrono_refid` =" . $chr->id);
//        if ($db->num_rows($result) == 0)
        if($chr->fk_user_modif < 1 && $chr->socid < 1)
            $action = 'supprimer';
    }
}


$para = "id=" . $_REQUEST['id'];
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'generatePdf' || $_REQUEST['action'] == 'builddoc')) {
    require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/core/modules/synopsischrono/modules_synopsischrono.php");
    $model = (isset($_REQUEST['model']) ? $_REQUEST['model'] : '');
    synopsischrono_pdf_create($db, $chr, $model);
    header('location: ?' . $para . "#documentAnchor");
}

if ($action == 'supprimer') {
    $tmpChr = 'chrono' . $chr->model_refid;
    $rightChrono = $user->rights->chrono_user->$tmpChr;

    if ($user->rights->synopsischrono->Supprimer || $rightChrono->supprimer) {
        $res = $chr->supprimer($id);
        if ($res > 0) {
            if ($chr->propalid)
                header('Location: ' . DOL_URL_ROOT . "/synopsischrono/listByObjet.php?obj=propal&id=" . $chr->propalid);
            elseif ($chr->projetid)
                header('Location: ' . DOL_URL_ROOT . "/synopsischrono/listByObjet.php?obj=project&id=" . $chr->projetid);
            elseif ($chr->socid)
                header('Location: ' . DOL_URL_ROOT . "/synopsischrono/listByObjet.php?obj=soc&id=" . $chr->socid);
            else
                header('Location: ' . DOL_URL_ROOT . '/synopsischrono/listByObjet.php');
        } else {
            header('Location: ?id=' . $id);
        }
    }
}


if ($action == 'multiValider') {
    $def = $_REQUEST['def'];
    $note = addslashes($_REQUEST['note']);
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def WHERE id = " . $def;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $val = $_REQUEST[$res->code];
    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsischrono_Multivalidation
                            (user_refid, chrono_refid,validation,right_refid,validation_number,note)
                     VALUES (" . $user->id . "," . $id . "," . $val . "," . $def . "," . ($chr->validation_number > 0 ? $chr->validation_number : "NULL") . ",'" . $note . "')";
//print $requete;
    $sql = $db->query($requete);
    $res = $chr->multivalidate();
}
if ($action == 'Valider') {
    $res = false;
    if ($chr->statut == 999) {
        //On multivalide les manquants
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def WHERE isValidationRight = 1 AND isValidationForAll <> 1";
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql)) {
            $requete1 = "SELECT *
                               FROM " . MAIN_DB_PREFIX . "synopsischrono_Multivalidation
                              WHERE validation_number " . ($chr->validation_number > 0 ? ' = ' . $chr->validation_number : " IS NULL ") . "
                                AND right_refid = " . $res->id . "
                                AND chrono_refid = " . $_REQUEST['id'] . "
                            ";
            $sql1 = $db->query($requete1);
            if ($db->num_rows($sql1) > 0)
                continue;
            else {
                $requete2 = "INSERT INTO " . MAIN_DB_PREFIX . "synopsischrono_Multivalidation
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
        header('location: ?id=' . $chr->id);
    } else {
        $msg = "Erreur de mise &agrave; jour";
    }
}
if ($action == 'AskValider') {
    $res = $chr->attenteValidate();
    if ($res > 0) {
        header('location: ?id=' . $chr->id);
    } else {
        $msg = "Erreur de mise &agrave; jour";
    }
}

if ($action == 'ModifyAfterValid' || $action == 'ModifyAfterValid2') {
    //Si chrono revisable
    if ($chr->model->hasRevision && $action == 'ModifyAfterValid') {
        if ($chr->model->revision_model_refid > 0) {
            $res = $chr->revised();
            if ($res > 0) {
                header("Location: ?id=" . $res);
            } else {
                header("Location: ?id=" . $_REQUEST['id']);
            }
        }
    } else {
        //Sinon mode normal
        $res = $chr->unvalidate();
        $chr->fetch($chr->id);
//        $action = 'Modify';
//        $_REQUEST['action'] = 'Modifier';
    }
}

if ($action == 'modifier') {
    $chr->note = (($chr->note != "") ? $chr->note . "\n\n" : "");
    $chr->note .= "Modifié le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
    $chr->description = addslashes($_REQUEST['description']);
    $chr->socid = addslashes($_REQUEST['socid']);
    $chr->contactid = addslashes($_REQUEST['contactid']);
    $chr->propalid = addslashes($_REQUEST['Proposition comm.']);
    $chr->projetid = addslashes($_REQUEST['Projet']);

    if (isset($_REQUEST['socid']) && $_REQUEST['socid'] == "max") {
        $sql = $db->query("SELECT MAX(rowid) as max FROM " . MAIN_DB_PREFIX . "societe");
        if ($db->num_rows($sql) > 0) {
            $result = $db->fetch_object($sql);
            $chr->socid = $result->max;
        }
    }

//Extra Value

    $res = $chr->update($chr->id);
    $dataArr = $tabChronoValue = array();
    foreach ($_REQUEST as $key => $val) {
        if (preg_match('/^Chrono([0-9]*)$/', $key, $arrTmp)) {
            $requete = "SELECT * FROM "
                    . "" . MAIN_DB_PREFIX . "synopsischrono_key k, "
                    . "" . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur tk "
                    . "WHERE tk.id = k.type_valeur AND k.id = " . $arrTmp[1]
                    . " ORDER BY k.rang";

            $sql = $db->query($requete);
            if ($sql)
                $res = $db->fetch_object($sql);
            if ($sql && $res->valueIsChecked == 1 && ($val == 'on' || $val == 'On' || $val == 'oN' || $val == 'ON'))
                $dataArr[$arrTmp[1]] = 1;
            else if ($sql && $res->valueIsChecked == 1 && isset($_REQUEST[$key . "_check"]) && ($_REQUEST[$key . "_check"] == 'on' || $_REQUEST[$key . "_check"] == 'On' || $_REQUEST[$key . "_check"] == 'oN' || $_REQUEST[$key . "_check"] == 'ON'))
                $dataArr[$arrTmp[1]] = 1;
            else if ($sql && $res->valueIsChecked == 1)
                $dataArr[$arrTmp[1]] = 0;
            else
                $dataArr[$arrTmp[1]] = addslashes($val);
        }
        if (preg_match('/^ChronoLien-([0-9]*)-([0-9a-zA-Z]*)-([0-9]*)$/', $key, $arrTmp)) {
            $tabChronoValue[$arrTmp[1]][$arrTmp[2]][$val] = $val;
        }
        if (preg_match('/^ChronoLien-([0-9]*)-([0-9a-zA-Z]*)-([0-9a-zA-Z]*)$/', $key, $arrTmp)) {
            $tabChronoValue[$arrTmp[1]][$arrTmp[2]][0] = 0;
        }
    }
    $res1 = $chr->setDatas($chr->id, $dataArr);

//    print_r($tabChronoValue);die;
    foreach ($tabChronoValue as $idLien => $tmpArray) {
        foreach ($tmpArray as $nomElement => $tabVal) {
            if ($nomElement != "") {
                $objLien = new Lien($db);
                $objLien->cssClassM = "type:" . $nomElement;
                $objLien->fetch($idLien);
                $objLien->setValue($id, $tabVal);
            }
        }
    }

    /* special bimp appel */
    if (isset($_REQUEST['mailTrans']) && $_REQUEST['mailTrans'] == "on") {
        $chr->fetch($chr->id);
        if(is_object($chr->societe))
        $socStr = $chr->societe->getNomUrl(1);
        else
            $socStr = "n/c";
        if (isset($_REQUEST["Chrono1071"]) && $_REQUEST["Chrono1071"] > 0) {
            $group = new UserGroup($db);
            $group->fetch($_REQUEST["Chrono1071"]);
            foreach ($group->members as $tech) {
                mailSyn2("Transfert Appel " . $chr->societe->nom, $tech->email, null, "Bonjour " . $tech->getFullName($langs) . " l'appel " . $chr->getNomUrl(1) . " de " . $socStr . " été transmis a votre groupe.");
            }
        } elseif (isset($_REQUEST["Chrono1070"]) && $_REQUEST["Chrono1070"] > 0) {
            $tech = new User($db);
            $tech->fetch($_REQUEST["Chrono1070"]);
            mailSyn2("Transfert Appel " . $chr->societe->nom, $tech->email, null, "Bonjour " . $tech->getFullName($langs) . " l'appel " . $chr->getNomUrl(1) . " de " . $socStr . " vous a été transmis.");
        }
    }
    /* fin special bimp appel */


    if ($res > 0) {
        header('location:?id=' . $id . ($action2 != "" ? "&action=$action2" : ""));
    } else {
        $msg = "Erreur dans la mise &agrave; jour";
    }
}
// Suppression fichier
if ($action == 'confirm_deletefile' && $_REQUEST['confirm'] == 'yes') {
    $file = $conf->synopsischrono->dir_output . "/" . urldecode($_GET["urlfile"]);
    dol_delete_file($file);
    //TODO
    $tmpName = $_FILES['userfile']['name'];
    //decode decimal HTML entities added by web browser
    $tmpName = dol_unescapefile($tmpName);
    // Appel des triggers
    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
    $interface = new Interfaces($db);
    $interface->texte = $tmpName;

    $result = $interface->run_triggers('ECM_UL_DEL_CHRONO', $chr, $user, $langs, $conf);
    if ($result < 0) {
        $error++;
        $errors = $interface->errors;
    }
    // Fin appel triggers

    $mesg = '<div class="ok">' . $langs->trans("FileWasRemoved") . '</div>';
}
//if ($action == "Modify" || $action == "ModifyAfterValid") {
$js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/synopsischrono/fiche.js'></script>";
//}
$js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.jDoubleSelect.js'></script>";
$js .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.js"></script>' . "\n";
//$js .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/ui.selectmenu.js"></script>' . "\n";
//$js .= '<script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/ui.datetimepicker.js" type="text/javascript"></script>';
//launchRunningProcess($db,'Chrono',$_GET['id']);
define('REQUIRE_JQUERY_TIMEPICKER', true);
//define('REQUIRE_JQUERY_MULTISELECT', true);



if ($chr->id > 0) {
    if (isset($_REQUEST['nomenu'])) {
        top_htmlhead($js, 'Fiche ' . $chr->model->titre);
    } else
        llxHeader($js, 'Fiche ' . $chr->model->titre);
    
    
    
    if(isset($_SESSION['error'])){
        foreach($_SESSION['error'] as $error => $type){
            dol_htmloutput_mesg($error, array(), ($type == 1)? "error" : "ok");
        }
        $_SESSION['error'] = array();
    }
    

//print "<div class='titre'>Fiche chrono</div><br/>";

    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/modele/revision_merlot.class.php");
    $conv = new revision_merlot($db);

    if ($msg . "x" != 'x') {
        print "<div style='padding: 3px;'><span class='ui-icon ui-icon-info' style='float: left;'></span>" . $msg . "</div>";
    }
    $tmpChr = 'chrono' . $chr->model_refid;
    $rightChrono = $user->rights->chrono_user->$tmpChr;

    if (!($rightChrono->voir == 1 || $user->rights->synopsischrono->read == 1)) {

        accessforbidden("Ce type de chrono ne vous est pas accessible", 0);
        exit;
    }


    //saveHistoUser($chr->id, "chrono",$chr->ref);

    $html = new Form($db);

    if (!isset($_REQUEST['nomenu'])) {
        $head = chrono_prepare_head($chr);
        dol_fiche_head($head, 'chrono', $chr->model->titre);
    }

    if ($_GET['action'] == 'delete' || $_GET['action'] == 'remove_file') {
        $html->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $_GET["id"] . '&amp;urlfile=' . urldecode((isset($_GET["urlfile"]) && $_GET["urlfile"] != "") ? $_GET["urlfile"] : $_GET["file"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
        print '<br>';
    }

    if (($action == "Modify" || $action2 == "Modify") && $user->rights->synopsischrono->Modifier) {
        print "<form id='form' action='?id=" . $chr->id . "' method=post>";
        print "<table id='chronoTable' class='border' width=100%; class='ui-state-default' style='border-collapse: collapse;' cellpadding=15>";
        print "<input type='hidden' name='action' value='modifier'>";
        print "<input type='hidden' name='id' value='" . $chr->id . "'>";
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Ref') . '</th>
                     <td colspan=1 class=" ui-widget-content" >' . $chr->getNomUrl(1) . '</td>
                     <th colspan=1 class=" ui-widget-header ui-state-default" >Type</th>
                     <td colspan=1 class=" ui-widget-content" >' . $chr->model->titre . '</td>';
        if ($chr->model->hasSociete == 1) {
            print '<tr><th colspan=1 class="ui-state-default ui-widget-header" >' . $langs->trans('Company') . '</th>';

            print '    <td  class="ui-widget-content" colspan="' . (($chr->model->hasContact == 1) ? '1' : '3') . '"><span class="addSoc editable" style="float: left; padding : 3px 15px 0 0;">' . img_picto($langs->trans("Create"), 'filenew') . '</span>' . $html->select_company($chr->socid, 'socid', 1, 1, 0, 0, array(array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php', 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled')))) . '</td>';
//            else
//                print '    <td  class="ui-widget-content" colspan="3">' . $html->select_company($chr->socid, 'socid', 1, 1, 0, 0, array(array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php', 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled')))) . '</td>';
        }
        if ($chr->model->hasContact == 1) {
            if (!$chr->model->hasSociete == 1)
                print '<tr>';
            print '    <th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">';
            print $langs->trans('Contact') . '</th>';
            $tmpContact = "";
            ob_start();
            if ($chr->socid > 0) {
                if ($chr->contactid > 0) {
                    $html->select_contacts($chr->socid, $chr->contactid, 'contactid', 1, '', false, '', 0, 'dolibarrcombobox');
                    $tmpContact = $html->tmpReturn;
                } else {
                    $html->select_contacts($chr->socid, '', 'contactid', 1, '', false);
                    $tmpContact = $html->tmpReturn;
                }
            } else if ($chr->contactid > 0) {
                $html->select_contacts(-1, $chr->contactid, 'contactid', 1, '', false);
                $tmpContact = $html->tmpReturn;
            }
            $tmpContact = ob_get_clean();
            print '    <td  class="ui-widget-content" colspan="' . (($chr->model->hasSociete == 1) ? 1 : 3) . '"><span class="addContact editable" style="float: left; padding : 3px 15px 0 0;">' . img_picto($langs->trans("Create"), 'filenew') . '</span><div id="contactSociete">' . $tmpContact . '</div></td>';
        }

        if ($chr->model->hasDescription) {
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">' . $chr->model->nomDescription;
            print '<td  class="ui-widget-content" colspan="3">';
            if ($chr->model->typeDescription == 2)
                print '<textarea style="width: 98%; min-height: 8em;" class="" name="description">' . stripslashes($chr->description) . '</textarea>';
            else
                print '<input type="text" name="description" class="required" value="' . stripslashes($chr->description) . '"/>';
            print '</td>';
        }


        require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/chronoFiche.lib.php");
//        $requete = "SELECT k.id FROM
//                           " . MAIN_DB_PREFIX . "synopsischrono_key AS k
//                      WHERE k.model_refid = " . $chr->model_refid
//                . " ORDER BY k.rang";
//        $sql = $db->query($requete);
//        while ($result = $db->fetch_object($sql)) {
//            echo getValueForm($chr->id, $result->id, $chr->socid);
//        }

        $chr->getValuesPlus();
        foreach ($chr->valuesPlus as $res)
            echo getValueForm2($res, $chr);

//
//        print '<tr><th align=right class="ui-state-default ui-widget-header" nowrap colspan=4  class="ui-state-default">';
        print '</table></div><div class="divButAction">';
        print '<input type="hidden" id="forAction2" name="action2" value="nc"/>';
        
        print "<button onClick='location.href=\"?action=cancel&id=" . $chr->id . "\"; return(false);' class='butAction'>Annuler</button>";
        print "<button id='forValid' class='butAction'>Modifier</button>";
        print '</div></form>';

        echo '<script>'
        . '$( document ).ready(function() {'
               . 'autoSave(function(){
                   $("#forAction2").attr("value", "Modify");
                   $("#forValid").click();
                });'
        . '});'
        . '</script>';
    } else if ($chr->id > 0) {
        print "<table id='chronoTable' class='border' width=100%; class='ui-state-default' style='border-collapse: collapse;' cellpadding=15>";
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Ref') . '</th>
                     <td colspan=1 class=" ui-widget-content" >' . $chr->getNomUrl(1) . '</td>
                     <th colspan=1 class=" ui-widget-header ui-state-default" >Type</th>
                     <td colspan=1 class=" ui-widget-content" >' . $chr->model->titre . '</td>';
        $hasSoc = $chr->socid && $chr->model->hasSociete == 1;
        $hasCont = $chr->contact && $chr->model->hasContact == 1;

        if ($hasSoc || $hasCont)
            echo "<tr/>";

        if ($hasSoc) {
            $societe = new Societe($db);
            $societe->fetch($chr->socid);
            // Societe
            print '<th colspan=1 class="ui-state-default ui-widget-header" >' . $langs->trans('Company') . '</th>';
            print '    <td  class="ui-widget-content" colspan="' . ($hasCont ? 1 : 3) . '">' . $societe->getNomUrl(1) . '</td>';
        }

        if ($hasCont) {
            // Contact
            print '<th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">';
            print $langs->trans('Contact') . '</th>';
            print '    <td  class="ui-widget-content" colspan="' . ($hasSoc ? 1 : 3) . '">' . $chr->contact->getNomUrl(1) . '</td>';
        }
//        $chr->user_author->fetch($chr->user_author->id);

        if ($chr->model->hasSuivie) {
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Crée le';
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
        }

        if ($chr->validation_number > 0 && $chr->statut != 2 && $chr->statut != 3) {
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Derni&egrave;re demande de validation :';
            print '    <td  class="ui-widget-content" colspan="1">';
            $requete = "SELECT d.label, m.user_refid, m.validation,m.tms,m.note
                            FROM " . MAIN_DB_PREFIX . "synopsischrono_Multivalidation as m,
                                 " . MAIN_DB_PREFIX . "synopsischrono_rights_def as d
                           WHERE chrono_refid = " . $chr->id . "
                             AND d.id = m.right_refid
                             AND validation_number = " . ($chr->validation_number - 1);
            if ($chr->validation_number == 1) {
                $requete = "SELECT d.label, m.user_refid, m.validation,m.tms,m.note
                            FROM " . MAIN_DB_PREFIX . "synopsischrono_Multivalidation as m,
                                 " . MAIN_DB_PREFIX . "synopsischrono_rights_def as d
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
                $tmpUser->fetch($res3->user_refid);
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

        if ($chr->model->hasPropal) {
            print '<tr><th class="ui-widget-header ui-state-default">Proposition comm';
            // print '<td colspan=1 class="ui-widget-content">';
            $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "synopsischrono,
                       " . MAIN_DB_PREFIX . "propal
                 WHERE " . MAIN_DB_PREFIX . "synopsischrono.propalid = " . MAIN_DB_PREFIX . "propal.rowid
                   AND " . MAIN_DB_PREFIX . "synopsischrono.id = " . $chr->id;
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
                $requete2 = "SELECT * FROM " . MAIN_DB_PREFIX . "propal ";
                if ($hasSoc)
                    $requete2 .= " WHERE fk_soc = " . $chr->socid;
                $requete2 .= " ORDER BY `rowid` DESC";
                $sql2 = $db->query($requete2);
                while ($res = $db->fetch_object($sql2)) {
                    print "<option value='" . $res->rowid . "'" . (($res->rowid == $idT) ? " selected=\"selected\"" : "") . ">" . $res->ref . "</option>";
                }
                print '<input type="submit" value="Modifier"/>';
                print "</form>";
            } else {
                echo '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=editprop">' . img_edit("Editer proposition comm.", 1) . '</a>';
                $resql = $db->query($requete);
                if ($db->num_rows($resql) > 0) {
                    while ($res = $db->fetch_object($resql)) {
                        require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                        require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                        $propal = new Propal($db);
                        $propal->fetch($res->rowid);
                        $tabT = getElementElement("propal", "facture", $propal->id);
                        print "<td class='ui-widget-content'>" . $propal->getNomUrl(1) . " " . $propal->getLibStatut() . "<br/>Total : " . price($res->total_ht, 1, '', 1, -1, -1, $conf->currency) . " HT " . price($res->total, 1, '', 1, -1, -1, $conf->currency) . " TTC<br/>";
                        if ($propal->statut == 0) {
                            $form = new Form($db);
                            echo "<form method='POST'><input type='hidden' name='idProp' value='" . $propal->id . "'/><input name='action' type='hidden' value='addLnProp'/>";
                            $form->select_produits('', "idprod", '', $conf->product->limit_size);
                            echo "<input class='butAction' type='submit' value='Ajouter'/></form></td>";
                        }
                        foreach ($tabT as $val) {
                            $propal = new Facture($db);
                            $propal->fetch($val['d']);
                            print '<tr><th class="ui-widget-header ui-state-default">Facture';
                            print "<td class='ui-widget-content'>" . $propal->getNomUrl(1) . " " . $propal->getLibStatut() . "<br/>Total : " . price($propal->total_ht, 1, '', 1, -1, -1, $conf->currency) . " HT " . price($propal->total_ttc, 1, '', 1, -1, -1, $conf->currency) . " TTC</td>";
                        }
                    }
                } else {
                    echo "<td class='ui-widget-content'>";
                    if ($hasSoc)
//                        echo '<a href="' . DOL_URL_ROOT . '/comm/propal.php?action=create' . ($hasSoc?"&socid=".$chr->socid:"") . '">Créer</a>';
                        echo '<a href="?id=' . $id . '&action=createPC">Créer</a>';
                    else
                        echo 'Pas de client définit';
                }
            }
        }
        /* 	print '<tr><th class="ui-widget-header ui-state-default">Projet';
          $requete = "SELECT *
          FROM ".MAIN_DB_PREFIX."synopsischrono,
          ".MAIN_DB_PREFIX."propal, ".MAIN_DB_PREFIX."Synopsis_projet_view
          WHERE ".MAIN_DB_PREFIX."synopsischrono.propalid = ".MAIN_DB_PREFIX."propal.rowid
          AND ".MAIN_DB_PREFIX."propal.fk_projet = ".MAIN_DB_PREFIX."Synopsis_projet_view.rowid
          AND ".MAIN_DB_PREFIX."synopsischrono.id = ".$chr->id;
          if ($resql = $db->query($requete))
          {
          while ($res = $db->fetch_object($resql))
          {
          print "<td class='ui-widget-content'><a href='".DOL_URL_ROOT."/projet/card.php?id=".$res->rowid."'>".$res->title."</a></td>";
          //print "<td class='ui-widget-content'>azerttyy</a></td>";
          }
          } */





        if ($chr->model->hasStatut) {
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">Statut';
            print '    <td  class="ui-widget-content" colspan="3">' . $chr->getLibStatut(4) . '</td>';
        }

        if ($chr->model->hasProjet) {
            print '<tr><th class="ui-widget-header ui-state-default">Projet
		<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=editprojet">' . img_edit("Editer projet", 1) . '</a>
		<td class=\'ui-widget-content\'>';
            $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "synopsischrono,
                       " . MAIN_DB_PREFIX . "Synopsis_projet_view
                 WHERE " . MAIN_DB_PREFIX . "synopsischrono.projetid = " . MAIN_DB_PREFIX . "Synopsis_projet_view.rowid
                   AND " . MAIN_DB_PREFIX . "synopsischrono.id = " . $chr->id;
            // print "<table class='nobordernopadding' width=100%>";
            if ($_REQUEST['action'] == 'editprojet') {
                $requete3 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view ORDER BY `rowid` DESC";
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
                        print "<a href='" . DOL_URL_ROOT . "/projet/card.php?id=" . $res->rowid . "'>" . $res->ref . " : " . $res->title . "</a>";
                    }
                }
            }
            echo "</td>";
        }
        if ($chr->model->hasDescription) {
//print '    <td  class="ui-widget-content" colspan="3"><textarea style="width: 98%; min-height: 8em;" class="required" name="description">'.$chr->description.'</textarea></td>';
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">' . $chr->model->nomDescription;
            print '    <td  class="ui-widget-content" colspan="1">' . str_replace("\n", "<br/>", stripslashes($chr->description)) . '</td>';
        }



//Ajoute les extra key/Values
        $chr->getValuesPlus();
        print '<td colspan="2" rowspan="100" class="zonePlus">';
        foreach ($chr->valuesPlus as $res) {
            print '<tr><th class="ui-state-default ui-widget-header" nowrap  class="ui-state-default">' . $res->nom;
            print '    <td  class="ui-widget-content ' . $res->extraCss . '" colspan="1">';
            print str_replace("\n", "<br/>", $res->valueHtml);
            print '</td>';
        }


        print '</table></div><div class="divButAction">';
        print '<tr><th align=right nowrap colspan=4  class="ui-state-default">';
        if (($user->rights->synopsischrono->Modifier || $rightChrono->modifier ) && $chr->statut == 0) {
            print "<button class='butAction' onClick='location.href=\"?id=" . $chr->id . "&action=Modify\"'>Modifier</button>";
        }
        if (($user->rights->synopsischrono->ModifierApresValide ) && $chr->statut != 999) {

            $requete = "SELECT *
                                FROM " . MAIN_DB_PREFIX . "synopsischrono
                               WHERE ";
            if ($chr->revision >= 1) {
                if ($chr->revision > 1)
                    $requete .= "orig_ref = '" . $chr->orig_ref . "'
                                 AND revision = " . ($chr->revision > 0 ? $chr->revision - 1 : 1);
                else
                    $requete .= "ref = '" . $chr->orig_ref . "'";

                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                if ($res->id > 0) {
                    print "<button class='butAction' onClick='location.href=\"?id=" . $res->id . "\"'>R&eacute;vision précédente: " . $res->ref . "</button>";
                }
            }

            if ($chr->model->hasRevision == 1 && !$chr->model->revision_model_refid > 0)
                print "<div class='ui-error error'>Pas de mod&egrave;le de r&eacute;visions !</div>";
            else if ($chr->model->hasRevision == 1 && $chr->model->revision_model_refid > 0 && $chr->statut != 3 && ($chr->statut > 0 || !$chr->model->hasStatut))
                print "<button class='butAction' onClick='location.href=\"?id=" . $chr->id . "&action=ModifyAfterValid\"'>R&eacute;viser</button>";
            else if ($chr->model->hasRevision == 1 && $chr->statut == 3) {//deja Réviser afficher suivante derniere
//Affiche le dernier et le suivant
                $requete = "SELECT *
                                FROM " . MAIN_DB_PREFIX . "synopsischrono
                               WHERE orig_ref = '" . $chr->orig_ref . "'
                                 AND revision = " . ($chr->revision > 0 ? $chr->revision + 1 : 1);
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                if ($res->id > 0) {
                    print "<button class='butAction' onClick='location.href=\"?id=" . $res->id . "\"'>R&eacute;vision suivante: " . $res->ref . "</button>";
                }


                $requete = "SELECT *
                                FROM " . MAIN_DB_PREFIX . "synopsischrono
                               WHERE orig_ref = '" . $chr->orig_ref . "'
                                 AND revision = (SELECT max(revision) FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE orig_ref='" . $chr->orig_ref . "')";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                if ($res->id > 0) {
                    print "<button class='butAction' onClick='location.href=\"?id=" . $res->id . "\"'>Derni&egrave;re r&eacute;vision: " . $res->ref . "</button>";
                }
            }
            if ($chr->statut != 3 && $chr->statut > 0)//Dévalider
                print "<button class='butAction' onClick='location.href=\"?id=" . $chr->id . "&action=ModifyAfterValid2\"'>Dévalider</button>";
        }
        $requete2 = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def WHERE active=1 AND isValidationForAll = 1";
        $sql2 = $db->query($requete2);
        $hasRight = false;
        while ($res2 = $db->fetch_object($sql2)) {
            $tmp = $res2->code;
            if ($rightChrono->$tmp)
                $hasRight = true;
            if ($hasRight)
                break;
        }
        if ($chr->model->hasStatut) {
            if (($user->rights->synopsischrono->Valider || $hasRight) && $chr->statut == 0) {

                //Validation totale
                if (!($user->rights->synopsischrono->Modifier || $rightChrono->modifier))
                    print '<tr><th align=right nowrap colspan=4  class="ui-state-default">';
                print "<button class='butAction' onClick='location.href=\"?id=" . $chr->id . "&action=Valider\"'>Valider</button>";
            } else {
                //Si droit de validation partiel
                $requete2 = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def WHERE active=1 AND isValidationForAll <> 1 AND isValidationRight=1";
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
                    print "<button class='butAction' onClick='location.href=\"?id=" . $chr->id . "&action=AskValider\"'>Demande de validation</button>";
                }
            }
            if ($chr->statut == 999) {
                print '<tr><th align=right class="ui-state-default" nowrap colspan=4 >';
                $requete3 = "SELECT *
                                 FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def
                                WHERE isValidationForAll = 1
                                  AND active=1
                                  AND isValidationRight=1 ";
                $sql3 = $db->query($requete3);
                $hasAllRight = false;
                while ($res3 = $db->fetch_object($sql3)) {
                    $tmp = $res3->code;
                    if ($rightChrono->$tmp) {
                        $requete4 = "SELECT d.label, d.id, M.user_refid,M.tms, d.code, M.validation
                                         FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def as d
                                    LEFT JOIN " . MAIN_DB_PREFIX . "synopsischrono_Multivalidation as M ON M.right_refid = d.id AND M.validation_number " . ($chr->validation_number > 0 ? " = " . $chr->validation_number : "IS NULL") . " AND M.chrono_refid = " . $id . "
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
                                $tmpUser->fetch($res4->user_refid);
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

                        print "<button class='butAction' onClick='location.href=\"?id=" . $chr->id . "&action=Valider&value=1&def=" . $res3->id . "\"'>" . $res3->label . "</button>";
                        print "<button class='butAction' onClick='location.href=\"?id=" . $chr->id . "&action=Valider&value=0\"'>Invalider</button>";

                        $hasAllRight = true;
                    }
                }
                if (!$hasAllRight) {
                    $requete3 = "SELECT d.label, d.id, M.user_refid,M.tms, d.code, M.validation, M.note
                                     FROM " . MAIN_DB_PREFIX . "synopsischrono_rights_def as d
                                LEFT JOIN " . MAIN_DB_PREFIX . "synopsischrono_Multivalidation as M ON M.right_refid = d.id AND M.validation_number " . ($chr->validation_number > 0 ? " = " . $chr->validation_number : "IS NULL") . " AND M.chrono_refid = " . $id . "
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
                                $tmpUser->fetch($res3->user_refid);
                                if ($res3->validation == 1) {
                                    print img_picto("Valider", "tick");
                                    print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res3->tms));
                                } else {
                                    print img_error("Non valider");
                                    print " par " . $tmpUser->getNomUrl(1) . " le " . date('d/m/Y', strtotime($res3->tms));
                                }
                                print "</table>";
                            } else {
                                print "<form method='POST' action='?id=" . $_REQUEST['id'] . "&def=" . $res3->id . "&action=multiValider'>";
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
//                              print "<button class='butAction' onClick='location.href=\"?id=".$chr->id."&action=valider&def=".$res3->id."\"'>".$res3->label."</button>";
                        } else {
                            if ($res3->validation . "x" != "x") {
                                print "<table width=80%>";
                                print "<tr><td align=left  width=20%>" . $res3->label;
                                print "<td align=right>" . nl2br($res3->note);
                                print "<td align=right>";
                                $tmpUser = new User($db);
                                $tmpUser->fetch($res3->user_refid);
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
        }
        if ($chr->statut == 0 && ($user->rights->synopsischrono->Supprimer || $rightChrono->supprimer) && !$chr->revision > 0) {
            if (!($user->rights->synopsischrono->Modifier || $rightChrono->modifier))
                print '<tr><th align=right nowrap colspan=4  class="ui-state-default">';
            print "<button class='butActionDelete' onClick='jQuery(\"#delDialog\").dialog(\"open\");return(false);'>Supprimer</button>";
        }


        print '</div>';

        print "<div id='delDialog'>" . img_error('') . " &Ecirc;tes vous sur de vouloir supprimer ce chrono ?</div>";
        print "<script>";
        print "var chronoId = " . $chr->id . ";";
        print <<<EOF
          jQuery(document).ready(function(){
EOF;
        if (isset($_REQUEST['msg'])) {
            print("alert('" . urldecode($_REQUEST['msg']) . "');");
        }

        print <<<EOF
                jQuery('#delDialog').dialog({
                    autoOpen: false,
                    width: 520,
                    minWidth: 520,
                    modal: true,
                    title: "Suppression de chrono",
                    buttons: {
                        OK: function(){
                            location.href='?action=supprimer&id='+chronoId
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
//            $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_ASC : SORT_DESC), 1);
//            $formfile = new FormFile($db);
//            // List of document
//            $param = '&id=' . $chr->id;
//            $formfile->list_of_documents($filearray, $chr, 'synopsischrono', $param, 1, $chr->id . "/");

            $object = $chr;
            $filename = sanitize_string($object->id);
            $urlsource = $_SERVER["PHP_SELF"] . "?" . $para;
            $genallowed = 1; //$user->rights->synopsischrono->Global->read;

            require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
            $html = new Form($db);
            $formfile = new FormFile($db);
            $somethingshown = $formfile->show_documents('synopsischrono', $filename, $upload_dir, $urlsource, $genallowed, $genallowed, "Chrono", 0); //, $object->modelPdf);
        }
    }
} else {
    accessforbidden("Pas d'id de chrono", 0);
    exit;
}

llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');
?>
<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/partsCart.class.php';
require_once DOL_DOCUMENT_ROOT . '/Synopsis_Process/class/process.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsischrono/class/chrono.class.php';
$js = "<link rel='stylesheet' type='text/css' href='" . DOL_URL_ROOT . "/synopsistools/css/global.css' />";
$js.= "<link rel='stylesheet' type='text/css' href='" . DOL_URL_ROOT . "/synopsistools/css/BIMP.css' />";
$js.= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.js"></script>' . "\n";
$js.= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/synopsischrono/fiche.js' ></script>";
$js.= '<script type="text/javascript" >$(window).load(function() { $(".addContact2").click(function() {
        socid = $("#socid").val();
        dispatchePopObject(socid, "newContact", function() {
        $("#form").append(\'<input type="hidden" name="action2" value="Modify"/><input type="hidden" name="contactSociete" value="max"/>\');
        $(".required").removeClass("required");
        $("#form").submit();
        }, "Contact", 1)
    });
$("#inputautocompletesocid").focus();

$("form#form").submit(function(){
});

});</script>';

$echo = "";

if (isset($_REQUEST['socid']) && $_REQUEST['socid'] == "max") {
    $sql = $db->query("SELECT MAX(rowid) as max FROM " . MAIN_DB_PREFIX . "societe WHERE fk_user_creat = ".$user->id);
    if ($db->num_rows($sql) > 0) {
        $result = $db->fetch_object($sql);
        $_REQUEST['socid'] = $result->max;
    }
}

if (isset($_REQUEST['socid']) && $_REQUEST['socid'] > 0 && isset($_REQUEST['contactSociete']) && $_REQUEST['contactSociete'] == "max") {
    $sql = $db->query("SELECT MAX(rowid) as max FROM " . MAIN_DB_PREFIX . "socpeople WHERE fk_soc=" . $_REQUEST["socid"]." AND fk_user_creat = ".$user->id);
    if ($db->num_rows($sql) > 0) {
        $result = $db->fetch_object($sql);
        $_REQUEST['contactSociete'] = $result->max;
    }
}

$form = new form($db);
$echo .= "<h1 font size='20' align='center' ><B> Fiche Rapide </B></h1>";
$socid = (isset($_REQUEST['socid']) ? $_REQUEST['socid'] : "");
$NoMachine = (isset($_POST['NoMachine']) ? $_POST['NoMachine'] : "");
$machine = (isset($_POST['Machine']) ? $_POST['Machine'] : "");
$numExt = (isset($_POST['NumExt']) ? $_POST['NumExt'] : "");
$systeme = (isset($_POST['systeme']) ? $_POST['systeme'] : "");
$garantie = (isset($_POST['Garantie']) ? $_POST['Garantie'] : "");
$preuve = (isset($_POST['Preuve']) && ($_POST['Preuve'] == 1 || $_POST['Preuve'] == "on") ? 'checked' : "");
$prio = (isset($_POST['Prio']) && ($_POST['Prio'] == 1 || $_POST['Prio'] == 'on') ? 'checked' : "");
$DateAchat = (isset($_POST['DateAchat']) ? $_POST['DateAchat'] : "");
$etat1 = (isset($_POST['Etat']) && $_POST['Etat'] == 1 ? 'selected' : "");
$etat2 = (isset($_POST['Etat']) && $_POST['Etat'] == 2 ? 'selected' : "");
$etat3 = (isset($_POST['Etat']) && $_POST['Etat'] == 3 ? 'selected' : "");
$etat4 = (isset($_POST['Etat']) && $_POST['Etat'] == 4 ? 'selected' : "");
$etat5 = (isset($_POST['Etat']) && $_POST['Etat'] == 5 ? 'selected' : "");
$accessoire = (isset($_POST['Chrono-1041']) ? $_POST['Chrono-1041'] : "");
$sauv0 = (isset($_POST['Sauv']) && $_POST['Sauv'] == 0 ? 'selected' : "");
$sauv1 = (isset($_POST['Sauv']) && $_POST['Sauv'] == 1 ? 'selected' : "");
$sauv2 = (isset($_POST['Sauv']) && $_POST['Sauv'] == 2 ? 'selected' : "");
$sauv3 = (isset($_POST['Sauv']) && $_POST['Sauv'] == 3 ? 'selected' : "");
$pass = (isset($_POST['pass']) ? $_POST['pass'] : "");
$loginA = (isset($_POST['loginA']) ? $_POST['loginA'] : "");
$devis1 = (isset($_POST['Devis']) && $_POST['Devis'] == 1 ? 'selected' : "");
$devis2 = (isset($_POST['Devis']) && $_POST['Devis'] == 2 ? 'selected' : "");
$retour1 = (isset($_POST['Retour']) && $_POST['Retour'] == 1 ? 'selected' : "");
$retour2 = (isset($_POST['Retour']) && $_POST['Retour'] == 2 ? 'selected' : "");
$retour3 = (isset($_POST['Retour']) && $_POST['Retour'] == 3 ? 'selected' : "");
$symptomes = (isset($_POST['Symptomes']) ? $_POST['Symptomes'] : "");
$modeP = (isset($_REQUEST['paiementtype']) ? $_REQUEST['paiementtype'] : "");
$descr = (isset($_POST['Descr']) ? $_POST['Descr'] : "");
$acompte = (isset($_POST['acompte']) ? $_POST['acompte'] : "");
$centre = (isset($_POST['centre']) ? $_POST['centre'] : null);
$typeGarantie = (isset($_POST["typeGarantie"]) ? $_POST["typeGarantie"] : "");

//die($_REQUEST['socid']);

if (isset($_POST["Descr"]) && !isset($_REQUEST['action2'])) {
    if (!isset($_POST["Symptomes"]) || $_POST["Symptomes"] == "")
        $echo .= "Renseignez les Sympomes";
    elseif (!isset($_REQUEST['socid']) || $_REQUEST['socid'] < 1)
        $echo .= "Renseignez le Client";
//    elseif (!isset($_REQUEST['contactid']) || $_REQUEST['contactid'] == "")
//        $echo .= "Renseignez le Contact";
    elseif (!isset($_POST['Machine']) || $_POST['Machine'] == "")
        $echo .= "Renseignez la Machine";
    elseif (!isset($_POST['NoMachine']) || $_POST['NoMachine'] == "")
        $echo .= "Renseignez le numéro de série";
    elseif (!isset($_POST['Retour']) || $_POST['Retour'] == "")
        $echo .= "Renseignez le mode de contact";
    elseif (!isset($_POST['pass']) || $_POST['pass'] == "")
        $echo .= "Renseignez le mot de passe";
    elseif (!isset($_POST['Sauv']) || $_POST['Sauv'] == "")
        $echo .= "Renseignez l'état de la sauvegarde";
    elseif (!isset($_POST['Etat']) || $_POST['Etat'] == "")
        $echo .= "Renseignez l'état de la machine";
    else {
        $chronoProd = new Chrono($db);

        $chronoProdid = existProd($NoMachine);
        if ($chronoProdid < 0) {
            $chronoProd->model_refid = 101;
            $chronoProd->socid = $socid;
            $chronoProd->description = $machine;
            $dataArrProd = array(1011 => $NoMachine, 1057 => $pass, 1063 => $loginA, 1014 => $DateAchat, 1015 => $garantie, 1064 => $typeGarantie, 1067 => $systeme);
            $chronoProdNewid = $chronoProd->create();
            $testProd = $chronoProd->setDatas($chronoProdNewid, $dataArrProd);
        } else {
            $chronoProd->fetch($chronoProdid);
            $chronoProd->socid = $socid;
            $chronoProd->description = $machine;
            $chronoProd->update($chronoProdid);
            $dataArrProd = array(1057 => $pass, 1063 => $loginA, 1014 => $DateAchat, 1015 => $garantie, 1064 => $typeGarantie, 1067 => $systeme);
            $testProd = $chronoProd->setDatas($chronoProdid, $dataArrProd);
        }
        if (isset($chronoProdid) && $chronoProdid < 0 && isset($chronoProdNewid) && $chronoProdNewid > 0 || isset($chronoProdid) && $chronoProdid > 0) {

            $chrono = new Chrono($db);
            $chrono->model_refid = 105;
            $chrono->description = ($descr != "" ? addslashes($descr) : "");
            $chrono->socid = $socid;
            $chrono->contactid = $_REQUEST["contactSociete"];
            $chronoid = $chrono->create();
            if ($chronoid > 0) {
                $dataArr = array(1045 => date("Y/m/d H:i"), 1055 => $_POST["Sauv"], 1040 => $_POST["Etat"], 1041 => $accessoire, 1047 => $symptomes, /* 1058 => $_POST['Devis'], */ 1059 => $_POST['Retour'], 1056 => 0, 1060 => $centre, 1066 => $numExt, 1068 => ($prio == "" ? 0 : 1));
                $test = $chrono->setDatas($chronoid, $dataArr);
                if ($test) {
                    $socid = "";
                    $lien = new lien($db);
                    $lien->cssClassM = "type:SAV";
                    $lien->fetch(3);
                    $lien->setValue($chrono->id, array($chronoProd->id));
                    $chrono->fetch($chrono->id);
                    $chronoProd->fetch($chronoProd->id);

                    //Propal facture acompte
                    $chrono->createPropal();
                    $propal = new Propal($db);
                    $propal = $chrono->propal;


                    $propal->addline("Prise en charge :  : " . $chrono->ref .
                            "\n" . "S/N : " . $NoMachine .
                            "\n" . "Garantie :
Pour du matériel couvert par Apple, la garantie initiale s'applique.
Pour du matériel non couvert par Apple, la garantie est de 3 mois pour les pièces et la main d'oeuvre.
Les pannes logicielles ne sont pas couvertes par la garantie du fabricant.
Une garantie de 30 jours est appliquée pour les réparations logicielles.
", 0, 1, 0, 0, 0, 0, $chrono->societe->remise_percent, 'HT', 0, 0, 3);

                    $acompte = intval($acompte);
                    if ($acompte > 0) {
                        require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                        $factureA = new Facture($db);
                        $factureA->type = 3;
                        $factureA->date = dol_now();
                        $factureA->socid = $chrono->socid;
                        $factureA->modelpdf = "crabeSav";
                        $factureA->create($user);
                        $factureA->addline("Acompte", $acompte / 1.2, 1, 20, null, null, null, 0, null, null, null, null, null, 'HT', null, 1, null, null, null, null, null, null, $acompte / 1.2);

//                                ("Acompte", $acompte, 1, 0, 0, 0, $prod->id, 0, 'HT', null, null,null, null, null, null, null, null, null, null, null, null, null, $acompte);
                        $factureA->validate($user);

                        addElementElement("propal", "facture", $chrono->propalid, $factureA->id);

//                $factureA->add
                        require_once(DOL_DOCUMENT_ROOT . "/compta/paiement/class/paiement.class.php");
                        $payement = new Paiement($db);
                        $payement->amounts = array($factureA->id => $acompte);
                        $payement->datepaye = dol_now();
                        $payement->paiementid = $modeP;
                        $payement->create($user);

                        $factureA->set_paid($user);

                        include_once(DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php');
                        $factureA->generateDocument("crabeSav", $langs);
//                        facture_pdf_create($db, $factureA, "crabeSav", $langs);

                        require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
                        $discount = new DiscountAbsolute($db);
                        $discount->description = "Acompte";
                        $discount->fk_soc = $factureA->socid;
                        $discount->fk_facture_source = $factureA->id;
                        $discount->amount_ht = $acompte / 1.2;
                        $discount->amount_ttc = $acompte;
                        $discount->amount_tva = $acompte - ($acompte / 1.2);
                        $discount->tva_tx = 20;
                        $discount->create($user);
//                $propal->addline("Acompte", -$acompte, 1, 0, 0, 0, 0, 0, 0, -$acompte);
                        $propal->insert_discount($discount->id);
                    }

                    if ($prio) {
                        require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
                        $prodF = new ProductFournisseur($db);
                        $prodF->fetch(3422);
                        $prodF->tva_tx = ($prodF->tva_tx > 0) ? $prodF->tva_tx : 0;
                        $prodF->find_min_price_product_fournisseur($prodF->id, 1);
                        $propal->addline($prodF->description, $prodF->price, 1, $prodF->tva_tx, 0, 0, $prodF->id, $chrono->societe->remise_percent, 'HT', null, null, null, null, null, null, $prodF->product_fourn_price_id, $prodF->fourn_price);
                    }

                    $propal->fetch($propal->id);

                    require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
                    $propal->generateDocument("azurSAV", $langs);
//                    propale_pdf_create($db, $propal, "azurSAV", $langs);

                    require_once DOL_DOCUMENT_ROOT . "/synopsischrono/core/modules/synopsischrono/modules_synopsischrono.php";

                    synopsischrono_pdf_create($db, $chrono, "pc");
                    $repDest = DOL_DATA_ROOT . "/synopsischrono/" . $chrono->id . "/";
                    if(!is_dir($repDest))
                    mkdir($repDest);
                    link(DOL_DATA_ROOT . "/propale/" . $propal->ref . "/" . $propal->ref . ".pdf", $repDest . $propal->ref . ".pdf");
                    if ($acompte > 0)
                        link(DOL_DATA_ROOT . "/facture/" . $factureA->ref . "/" . $factureA->ref . ".pdf", $repDest . $factureA->ref . ".pdf");
//                $echo .= DOL_DATA_ROOT."/facture/".$factureA->ref."/".$factureA->ref.".pdf", $repDest.$factureA->ref.".pdf";die;


                    
                    unset($_REQUEST['action2']);
                    header('Status: 301 Moved Permanently', false, 301);
                    header("Location: ./FicheRapide.php?idChrono=" . $chrono->id);
                    die;
                } else {
                    $echo .= "Echec de l'Enregistrement 1";
                }
            } else {
                $echo .= "Echec de l'Enregistrement 2";
            }
        } else {
            $echo .= "Echec de l'Enregistrement 3";
        }
    }
}

llxHeader($js);
echo $echo;


if (isset($_REQUEST['idChrono'])) {
    $chrono = new Chrono($db);
    $chrono->fetch($_REQUEST['idChrono']);
    echo "<h3>Enregistrement effecué avec succés. </h3>"
    . "SAV : " . $chrono->getNomUrl(1) . " <br/>";
//                    . "Produit : " . $chronoProd->getNomUrl(1);
    // List of document
    echo "<br/><br/>";
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
    require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
    $formfile = new FormFile($db);
    $filearray = dol_dir_list(DOL_DATA_ROOT . "/synopsischrono/" . $chrono->id);
    $formfile->list_of_documents($filearray, $chrono, 'synopsischrono', $param, 1, $chrono->id . "/");
    echo "<h3>Nouvelle prise en charge</h3>";
}







if ($socid != "") {
    echo "<div id='reponse' >";
    echo "</div>";
    echo "<form id='form' method='post' action ='" . DOL_URL_ROOT . "/synopsisapple/FicheRapide.php?socid=" . $socid . "&action=semitotal'>";
    echo "<div style='float:left' >";
    echo "<table id='chronoTable' class='border' width='100%;' style='border-collapse: collapse;' cellpadding='15'>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Client.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo $form->select_thirdparty($socid, 'socid');
    echo "<br />";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<th class='ui-state-default ui-widget-header'>Contact.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo '<span class="addContact2 editable" style="float: left; padding : 3px 15px 0 0;"><img src="' . DOL_URL_ROOT . '/theme/eldy/img/filenew.png" border="0" alt="Create" title="Create"></span>';
    echo  $form->selectcontacts($socid, $_REQUEST['contactSociete'], 'contactSociete', 1, null,null,null,null,null,null,1);
    echo "<br />";
    echo "</td>";
    echo "</tr>";
    echo "</p>";


    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Centre</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo "<select name='centre'>";
    $result = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 11 ");
//    $centres = array("G" => "Grenoble", "L" => "Lyon", "M" => "Meythet");
//    foreach ($centres as $val => $centre) {
    while ($ligne = $db->fetch_object($result)) {
        $val = $ligne->valeur;
        $centre = $ligne->label;
        $tabT = explode(" ", trim($user->array_options['options_apple_centre']));
        $myCentre = (isset($tabT[0]) ? $tabT[0] : 'false');
        echo "<option value='" . $val . "' " . ($val == $myCentre ? "selected='selected'" : "") . ">" . $centre . "</option>";
    }
    echo "</select>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";


    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Prise en charge prioritaire.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='checkbox' name='Prio' id='Prio' " . $prio . "/>";
    echo "</td>";
    echo "</tr>";


    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>N° de série de la machine.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='NoMachine' value='" . $NoMachine . "' id='NoMachine' class='required'/>";
    echo "<span id='patientez' style='display:none; margin-left:15px;'>";
    echo "<img src='" . DOL_URL_ROOT . "/synopsistools/img/load.gif' title='Chargement des informations GSX en cours' alt='Chargement des informations GSX en cours'/>";
    echo "</span>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";

    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>N° de dossier prestataire.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='NumExt' value='" . $numExt . "' id='NumExt' class=''/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";

    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Machine.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='Machine' value='" . $machine . "' id='Machine' class='required'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";


    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Type garantie.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='typeGarantie' value='" . $typeGarantie . "' id='typeGarantie' class=''/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";


    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Date fin de garantie.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='Garantie' value='" . $garantie . "' id='Garantie' class='datepicker'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Preuve d'achat.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='checkbox' name='Preuve' value='1' id='Preuve'" . $preuve . "/>";
    echo " <label for='preuveAchat'/>(Cochez si une preuve d'achat est fournie)</label>";
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Date d'achat.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='DateAchat' value='" . $DateAchat . "' id='DateAchat' class='datepicker'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Etat de la machine.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <select name='Etat' id='Etat' class='required'>";
    echo "<option value=''></option> ";
    echo "<option value='1'" . $etat1 . ">Neuf</option> ";
    echo "<option value='2'" . $etat2 . ">Très bon état </option>";
    echo "<option value='3'" . $etat3 . ">Usagé</option> ";
//    echo "<option value='4'" . $etat4 . ">Rayures</option> ";
//    echo "<option value='5'" . $etat5 . ">Ecran cassé</option> ";
    echo " </select>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Description état machine.</th>";
    echo "<td class='ui-widget-content'>";
    echo " <textarea class='grand' type='text' name='Descr' id='Descr' >$descr</textarea>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Accessoires.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo "<textarea class=' grand choixAccess ' name='Chrono-1041' id='Chrono-1041'>$accessoire</textarea>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Sauvegarde.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <select name='Sauv' id='Sauv' class='required'>";
    echo "<option value=''></option> ";
    echo "<option value='3'" . $sauv3 . ">Dispose d'une sauvegarde Time machine</option> ";
    echo "<option value='2'" . $sauv2 . ">Désire une sauvegarde si necessaire</option> ";
    echo "<option value='1'" . $sauv1 . ">Dispose d'une sauvegarde </option>";
    echo "<option value='0'" . $sauv0 . ">Non Applicable</option> ";
    echo " </select>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";



    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Système</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo "<select name='systeme' class='required'>";
    $result = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 12 ");
    while ($ligne = $db->fetch_object($result))
        echo "<option value='" . $ligne->valeur . "' " . ($ligne->valeur == $systeme ? "selected='selected'" : "") . ">" . $ligne->label . "</option>";
    echo "</select>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";

    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Login admin.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='loginA' value='" . $loginA . "' id='loginA' class='required'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Mot de passe admin.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='pass' value='" . $pass . "' id='pass' class='required'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
//    echo "<tr>";
//    echo "<th class='ui-state-default ui-widget-header'>Préférence de contact pour le devis.</th>";
//    echo "<td class='ui-widget-content'>";
//    echo " <select name='Devis' id='Devis' class='required'>";
//    echo "<option value=''></option> ";
//    echo "<option value='1'" . $devis1 . ">Par Mail</option> ";
//    echo "<option value='2'" . $devis2 . ">Par Téléphone </option>";
//    echo " </select>";
//    echo "</td>";
//    echo "</tr>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Préférence de contact.</th>";
    echo "<td class='ui-widget-content'>"; /* <span class='addSoc editable' style='float: left; padding : 3px 15px 0 0;'> */
    echo " <select name='Retour' id='Retour' class='required'>";
    echo "<option value=''></option> ";
    echo "<option value='1'" . $retour1 . ">Par Mail</option> ";
    echo "<option value='2'" . $retour2 . ">Par Téléphone </option>";
    echo "<option value='3'" . $retour3 . ">Par Messages (SMS) </option>";
    echo " </select>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Symptomes.</th>";
    echo "<td class='ui-widget-content'>";
    echo " <textarea type='text' class='grand required' name='Symptomes' id='Symptomes'>$symptomes</textarea>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Acompte.</th>";
    echo "<td class='ui-widget-content'>";
    echo " <input type='text' name='acompte' id='acompte' value='$acompte'/>";
    echo $form->select_types_paiements($modeP);
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "</div>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'></th>";
    echo "<td class='ui-widget-content'>";
    echo "<input type='submit' class='butAction' name='Envoyer' value='Valider' id ='Envoyer'>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "</table>";
    echo "</div>";
    echo "</form>";
} else {
    echo "<form method='post' id='form' action ='" . DOL_URL_ROOT . "/synopsisapple/FicheRapide.php?socid=" . $socid . "'>";
    echo "<p>";
    echo "<label for='text'>Rentrez le client avant de passer a la suite : </label>";
    echo "</p>";
    echo "<p>";
    echo "<label for='client'>Client : </label>";
    echo $form->select_thirdparty('', 'socid');
    echo "<span class='addSoc editable' style='float: left; padding : 3px 15px 0 0;'><img src='" . DOL_URL_ROOT . "/theme/eldy/img/filenew.png' border='0' alt='Create' title='Create'></span>";
    echo "<br />";
    echo "</p>";
    echo "<p>";
    echo "<input type='submit' value='Valider' name='Envoyer' class='butAction' id ='Envoyer'>";
    echo "</p>";
    echo "</form>";
}






function existProd($nomachine) {
    global $db;
    $requete = "SELECT id FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_101 WHERE N__Serie = '" . $nomachine . "';";
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0) {
        $obj = $db->fetch_object($sql);
        $return = $obj->id;
        return $return;
    } else {
        return -1;
    }
}

?>

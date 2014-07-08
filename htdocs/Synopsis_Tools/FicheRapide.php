<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';
require_once DOL_DOCUMENT_ROOT . '/apple/gsxDatas.class.php';
require_once DOL_DOCUMENT_ROOT . '/apple/partsCart.class.php';
require_once DOL_DOCUMENT_ROOT . '/Synopsis_Process/process.class.php';
$js = "<link rel='stylesheet' type='text/css' href='".DOL_URL_ROOT."/Synopsis_Tools/css/global.css' />";
$js.= "<link rel='stylesheet' type='text/css' href='".DOL_URL_ROOT."/Synopsis_Tools/css/BIMP.css' />";
$js.= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.js"></script>' . "\n";
$js.= "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Chrono/fiche.js' ></script>";
$js.= '<script type="text/javascript" >$(window).load(function() { $(".addContact2").click(function() {
        socid = $("select#socid").val();
        dispatchePopObject(socid, "newContact", function() {
        $("#form").append(\'<input type="hidden" name="action2" value="Modify"/><input type="hidden" name="contactid" value="max"/>\');
        $(".required").removeClass("required");
        $("#form").submit();
        }, "Contact", 1)
    });});</script>';
llxHeader($js);

if (isset($_REQUEST['socid']) && $_REQUEST['socid'] == "max") {
    $sql = $db->query("SELECT MAX(rowid) as max FROM " . MAIN_DB_PREFIX . "societe");
    if ($db->num_rows($sql) > 0) {
        $result = $db->fetch_object($sql);
        $_REQUEST['socid'] = $result->max;
    }
}

if (isset($_REQUEST['socid']) && $_REQUEST['socid'] > 0 && isset($_REQUEST['contactid']) && $_REQUEST['contactid'] == "max") {
    $sql = $db->query("SELECT MAX(rowid) as max FROM " . MAIN_DB_PREFIX . "socpeople WHERE fk_soc=".$_REQUEST["socid"] );
    if ($db->num_rows($sql) > 0) {
        $result = $db->fetch_object($sql);
        $_REQUEST['contactid'] = $result->max;
    }
}

$form = new form($db);
echo "<h1 font size='20' align='center' ><B> Fiche Rapide </B></h1>";
$socid = (isset($_REQUEST['socid']) ? $_REQUEST['socid'] : "");
$NoMachine = (isset($_POST['NoMachine']) ? $_POST['NoMachine'] : "");
$machine = (isset($_POST['Machine']) ? $_POST['Machine'] : "");
$garantie = (isset($_POST['Garantie']) ? $_POST['Garantie'] : "");
$preuve = (isset($_POST['Preuve']) && $_POST['Preuve'] == 1 ? 'checked' : "");
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
$pass = (isset($_POST['pass']) ? $_POST['pass'] : "");
$devis1 = (isset($_POST['Devis']) && $_POST['Devis'] == 1 ? 'selected' : "");
$devis2 = (isset($_POST['Devis']) && $_POST['Devis'] == 2 ? 'selected' : "");
$retour1 = (isset($_POST['Retour']) && $_POST['Retour'] == 1 ? 'selected' : "");
$retour2 = (isset($_POST['Retour']) && $_POST['Retour'] == 2 ? 'selected' : "");
$retour3 = (isset($_POST['Retour']) && $_POST['Retour'] == 3 ? 'selected' : "");
$symptomes = (isset($_POST['Symptomes']) ? $_POST['Symptomes'] : "");
$descr = (isset($_POST['Descr']) ? $_POST['Descr'] : "");

if (isset($_POST["Descr"]) && $_POST["Descr"] != "" && isset($_REQUEST['socid']) && $_REQUEST['socid'] !== "" && isset($_REQUEST['contactid']) && $_REQUEST['contactid'] !== "" && isset($_POST['Machine']) && $_POST['Machine'] !== "" && isset($_POST['NoMachine']) && $_POST['NoMachine'] !== "") {
    $chronoProd = new Chrono($db);

    $chronoProdid = existProd($NoMachine);
    if (chronoProdid < 0) {
        $chronoProd->model_refid = 101;
        $chronoProd->socid = $socid;
        $chronoProd->description = $machine;
        $dataArrProd = array(1011 => $NoMachine, 1057 => $pass, 1014 => $DateAchat);
        $chronoProdNewid = $chronoProd->create();
        $testProd = $chronoProd->setDatas($chronoProdNewid, $dataArrProd);
    } else {
        $chronoProd->fetch($chronoProdid);
        $chronoProd->socid = $socid;
        $chronoProd->description = $machine;
        $chronoProd->update($chronoProdid);
        $dataArrProd = array(1057 => $pass, 1014 => $DateAchat);
        $testProd = $chronoProd->setDatas($chronoProdid, $dataArrProd);
    }
    if (isset($chronoProdid) && $chronoProdid < 0 && isset($chronoProdNewid) && $chronoProdNewid > 0 || isset($chronoProdid) && $chronoProdid > 0) {

        $chrono = new Chrono($db);
        $chrono->model_refid = 105;
        $chrono->description = addslashes($descr);
        $chrono->socid = $socid;
        $chrono->contactid = $_REQUEST["contactid"];
        $chronoid = $chrono->create();
        if ($chronoid > 0) {
            $dataArr = array(1055 => $_POST["Sauv"], 1040 => $_POST["Etat"], 1041 => $accessoire, 1047 => $symptomes, 1058 => $_POST['Devis'], 1059 => $_POST['Retour'], 1056 => 0);
            $test = $chrono->setDatas($chronoid, $dataArr);
            if ($test) {
                $socid = "";
                $lien = new lien($db);
                $lien->cssClassM = "type:SAV";
                $lien->fetch(3);
                $lien->setValue($chrono->id, array($chronoProd->id));
                echo "Enregistrement effecué avec succés";
            } else {
                echo "Echec de l'Enregistrement";
            }
        } else {
            echo "Echec de l'Enregistrement";
        }
    } else {
        echo "Echec de l'Enregistrement";
    }
} elseif (isset($_POST["Descr"]) && !isset($_REQUEST['action2'])) {
    echo "Renseignez tous les champs";
}





if ($socid != "") {
    echo "<div id='reponse' >";
    echo "</div>";
    echo "<form id='form' method='post' action ='" . DOL_URL_ROOT . "/Synopsis_Tools/FicheRapide.php?socid=" . $socid . "&action=semitotal'>";
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
    echo '<span class="addContact2 editable" style="float: left; padding : 3px 15px 0 0;"><img src="'.DOL_URL_ROOT.'/theme/eldy/img/filenew.png" border="0" alt="Create" title="Create"></span>';
    $form->select_contacts($socid, $_REQUEST['contactid']);
    echo "<br />";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>N° de série de la machine.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='NoMachine' value='" . $NoMachine . "' id='NoMachine' class='required'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
//        $userId = 'Corinne@actitec.fr';
//        $password = 'cocomart01';
//        $serviceAccountNo = '0000100635';
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
    echo "<th class='ui-state-default ui-widget-header'>Etat de la garantie.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='Garantie' value='" . $garantie . "' id='Garantie'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Preuve d'achat.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='checkbox' name='Preuve' value='1' id='Preuve'" . $preuve . "/>";
    echo " <label for='peuvreAchat'/>(Cochez si une preuve d'achat est fournie)</label>";
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Date d'achat.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='Date' class = 'datePicker' name='DateAchat' value='" . $DateAchat . "' id='DateAchat'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Etat de la machine.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <select name='Etat' id='Etat'>";
    echo "<option value=''></option> ";
    echo "<option value='1'" . $etat1 . ">Neuf</option> ";
    echo "<option value='2'" . $etat2 . ">Très bon état </option>";
    echo "<option value='3'" . $etat3 . ">Choc Visible</option> ";
    echo "<option value='4'" . $etat4 . ">Rayures</option> ";
    echo "<option value='5'" . $etat5 . ">Ecran cassé</option> ";
    echo " </select>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Accesoires.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo "<textarea class=' grand choixAccess' name='Chrono-1041' id='Chrono-1041'>$accessoire</textarea>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Sauvegarde.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <select name='Sauv' id='Sauv'>";
    echo "<option value=''></option> ";
    echo "<option value='2'" . $sauv2 . ">Désire une sauvegarde</option> ";
    echo "<option value='1'" . $sauv1 . ">Dispose d'une sauvegarde </option>";
    echo "<option value='0'" . $sauv0 . ">Non Applicable</option> ";
    echo " </select>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Mot de passe admin.</th>";
    echo "<td class='ui-widget-content' colspan='1'>";
    echo " <input type='text' name='pass' value='" . $pass . "' id='pass'/>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Préférence de contact pour le devis.</th>";
    echo "<td class='ui-widget-content'>";
    echo " <select name='Devis' id='Devis'>";
    echo "<option value=''></option> ";
    echo "<option value='1'" . $devis1 . ">Par Mail</option> ";
    echo "<option value='2'" . $devis2 . ">Par Téléphone </option>";
    echo " </select>";
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Préférence de contact pour le retour.</th>";
    echo "<td class='ui-widget-content'>"; /* <span class='addSoc editable' style='float: left; padding : 3px 15px 0 0;'> */
    echo " <select name='Retour' id='Retour'>";
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
    echo " <textarea type='text' class='grand' name='Symptomes' id='Symptomes'>$symptomes</textarea>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'>Déscription.</th>";
    echo "<td class='ui-widget-content'>";
    echo " <textarea class='grand required' type='text' name='Descr' id='Descr'>$descr</textarea>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "</div>";
    echo "<p>";
    echo "<tr>";
    echo "<th class='ui-state-default ui-widget-header'></th>";
    echo "<td class='ui-widget-content'>";
    echo "<input type='submit' class='butAction' name='Envoyer' id ='Envoyer'>";
    echo "</td>";
    echo "</tr>";
    echo "</p>";
    echo "</table>";
    echo "</div>";
    echo "</form>";
} else {
    echo "<form method='post' id='form' action ='" . DOL_URL_ROOT . "/Synopsis_Tools/FicheRapide.php?socid=" . $socid . "'>";
    echo "<p>";
    echo "<label for='text'>Rentrez le client avant de passer a la suite : </label>";
    echo "</p>";
    echo "<p>";
    echo "<label for='client'>Client : </label>";
    echo $form->select_thirdparty('', 'socid');
    echo "<span class='addSoc editable' style='float: left; padding : 3px 15px 0 0;'><img src='".DOL_URL_ROOT."/theme/eldy/img/filenew.png' border='0' alt='Create' title='Create'></span>";
    echo "<br />";
    echo "</p>";
    echo "<p>";
    echo "<input type='submit' name='Envoyer' class='butAction' id ='Envoyer'>";
    echo "</p>";
    echo "</form>";
}
function existProd($nomachine) {
        global $db;
        $requete = "SELECT chrono_refid FROM "  . MAIN_DB_PREFIX . "Synopsis_Chrono_value WHERE key_id = 1011 and value = '".$nomachine."';";
        $sql = $db->query($requete);
        if ($sql) {
            $obj = $db->fetch_object($sql);
            $return = $obj->chrono_refid;
            return $return;
        } else {
            return -1;
        }
        
}
?>

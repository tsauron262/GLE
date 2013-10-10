<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 5 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : devalLogistique-xml_response.php
 *
 * GLE-1.2
 *
 *
 */
require_once('../../../main.inc.php');
$id = $_REQUEST['comId'];
$xmlStr = "<ajax-response>";
$requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_commande SET logistique_statut=0 WHERE rowid = " . $id;
$sql = $db->query($requete);

require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
$commande = new Synopsis_Commande($db);
$commande->fetch($id);
$arrGrpTmp = $commande->listGroupMember();
foreach ($arrGrpTmp as $key => $val) {
    $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_commande SET logistique_statut=0 WHERE rowid = " . $val->id;
    $sql = $db->query($requete);
}


$sql = $db->query($requete);
if ($sql) {
    $xmlStr .= "<OK>OK</OK>";
    require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
    $commande = new Synopsis_Commande($db);
    $commande->fetch($_REQUEST['comId']);
    $tmpUser = new User($db);
    $tmpUser->fetch($commande->user_author_id);

    //Notification
    //TO commercial author
    //CC Resp Tech et Resp logistique et financier
    $to = $tmpUser->email;



    if ($commande->logistique_ok == 1) {
        $statusLog = 'OK';
    } else if ($commande->logistique_ok == 0) {
        $statusLog = 'KO';
    } else if ($commande->logistique_ok == 2) {
        $statusLog = 'Partiel';
    }

    $dateDispo = "";
    $weekDispo = "";
    if ($commande->logistique_ok != 1) {
        $dateDispo = ($commande->logistique_date_dispo . 'x' == 'x' ? '' : date('d/m/Y', strtotime($commande->logistique_date_dispo)));
        $weekDispo = ($commande->logistique_date_dispo . 'x' == 'x' ? '' : date('W', strtotime($commande->logistique_date_dispo)));
    }
    $subject = "[Dispo Produit] Nouveau message concernant la logistique de la commande " . $commande->ref;
    if ($commande->logistique_ok != 1) {
        $subject = "[Non Dispo Produit] Nouveau message concernant la logistique de la commande " . $commande->ref;
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($db);
        $result = $interface->run_triggers('PREPACOM_MOD_INDISPO_PROD', $commande, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
            $errors = $interface->errors;
        }
        // Fin appel triggers
    } else {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($db);
        $result = $interface->run_triggers('PREPACOM_MOD_DISPO_PROD', $commande, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
            $errors = $interface->errors;
        }
        // Fin appel triggers
    }

    if (isset($conf->global->BIMP_MAIL_FROM) && isset($conf->global->BIMP_MAIL_GESTLOGISTIQUE)) {
        $msg = "Bonjour,<br/><br/>";
        $msg .= "La commande " . $commande->getNomUrl(1, 6) . " a &eacute;t&eacute; modifié après la valididation logistique.";
        if ($commande->logistique_ok != 1)
            $msg .= "<br/><b><em><font style='color: red'> Attention, certains produits ne sont pas disponibles !!!</font></em></b>";
        else
            $msg .= "<br/><b>Tous les produits sont disponibles !!!</b>";

        $msg .= "<br/><br/>Cordialement,<br/>\nGLE\n";
        $from = $conf->global->BIMP_MAIL_FROM;
        $addr_cc = $conf->global->BIMP_MAIL_GESTLOGISTIQUE . ", " . $conf->global->BIMP_MAIL_GESTPROD;


        require_once(DOL_DOCUMENT_ROOT . '/Synopsis_Tools/class/CMailFile.class.php');
        sendMail($subject, $to, $from, $msg, array(), array(), array(), $addr_cc, '', 0, 1, $from);
    }
} else {
    $xmlStr .= "<KO>KO</KO>";
}
if (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
    header("Content-type: application/xhtml+xml;charset=utf-8");
} else {
    header("Content-type: text/xml;charset=utf-8");
}
$et = ">";
print "<?xml version='1.0' encoding='utf-8'?$et\n";
print $xmlStr;
print "</ajax-response>";

function sendMail($subject, $to, $from, $msg, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = '', $addr_bcc = '', $deliveryreceipt = 0, $msgishtml = 1, $errors_to = '') {
    global $mysoc;
    global $langs;
    $mail = new CMailFile($subject, $to, $from, $msg,
                    $filename_list, $mimetype_list, $mimefilename_list,
                    $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, $errors_to);
    $res = $mail->sendfile();
    if ($res) {
        return (1);
    } else {
        return -1;
    }
}

?>

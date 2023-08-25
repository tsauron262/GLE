<?php

require_once "../../master.inc.php";

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . "/synopsistools/SynDiversFunction.php";

$fi = BimpObject::getInstance('bimptechnique', "BT_ficheInter");

if ($fi->find(['public_signature_url' => $_POST['key']], 1)) {
    $fi->updateField('signed', 1);
    $fi->updateField('base_64_signature', $_POST['signature']);
    $fi->updateField('fk_statut', 1);
    $fi->updateField('date_signed', date('Y-m-d H:i:s'));

    $commercial = $fi->getCommercialClient();

    $email_comm = '';
    $email_tech = '';
    
    $cc = '';
    $ref = $fi->getRef();
    $client = BimpObject::getInstance('bimpcore', "Bimp_Societe", $fi->getData('fk_soc'));
    
    $tech = $fi->getChildObject('user_tech');
    if (BimpObject::objectLoaded($tech)) {
        $email_tech = BimpTools::cleanEmailsStr($tech->getData('email'));
    }
    
    if (BimpObject::objectLoaded($commercial)) {
        $email_comm = BimpTools::cleanEmailsStr($commercial->getData('email'));
        $reply_to = ($email_comm ? $email_comm : ($email_tech ? $email_tech : ''));
        $bmCommercial = new BimpMail($fi,
                $ref . " signée à distance par le client",
                $email_comm, 
                '',
                "Bonjour, le client ".$client->getName()." (" . $client->getNomUrl() . ") a signé à distance la FI N°" . $fi->getNomUrl(), $reply_to, $cc
                );
        $mail_errors = array();
        $bmCommercial->send($mail_errors);
    }


    $email_cli = BimpTools::cleanEmailsStr($fi->getData('email_signature'));
    $reply_to = ($email_comm ? $email_comm : ($email_tech ? $email_tech : ''));

    $file = $conf->ficheinter->dir_output . '/' . $ref . '/' . $ref . '.pdf';
    $fi->actionGeneratePdf([]);

    $subject = "Fiche d'intervention " . $ref;
    $message = "Bonjour,<br />Veuillez trouver ci-joint notre Fiche d'Intervention<br />";
    $message .= "Vous souhaitant bonne réception de ces éléments, nous restons à votre disposition pour tout complément d'information.<br />";
    $message .= '<br/>Très courtoisement.';
    $message .= "<br /><br /><b>Le Service Technique</b><br/>";
    
    $cc = $email_comm;
    
    if ($email_tech) {
        $cc .= ($cc ? ', '  : '') . $email_tech;
    }
    
//    $cc .= ($cc ? ', '  : '') . 'f.martinez@bimp.fr';

    $bm = new BimpMail($fi, $subject, $email_cli, '', $message, $reply_to, $cc);

    if (file_exists($file)) {
        $bm->addFile(array($file, 'application/pdf', $ref . '.pdf'));
    }

    $mail_errors = array();
    $bm->send($mail_errors);

    if (!count($mail_errors)) {
        $fi->addLog("FI envoyée au client avec succès");
    }
}
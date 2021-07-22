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

    if (BimpObject::objectLoaded($commercial)) {
        $email_comm = BimpTools::cleanEmailsStr($commercial->getData('email'));
    } else {
        $tech = $fi->getChildObject('user_tech');
        if (BimpObject::objectLoaded($tech)) {
            $email_comm = BimpTools::cleanEmailsStr($tech->getData('email'));
        }
    }

    $email_cli = BimpTools::cleanEmailsStr($fi->getData('email_signature'));

    $ref = $fi->getRef();

    $file = $conf->ficheinter->dir_output . '/' . $ref . '/' . $ref . '.pdf';
    $fi->actionGeneratePdf([]);

    $subject = "Fiche d'intervention " . $ref;
    $message = "Bonjour,<br />Veuillez trouver ci-joint notre Fiche d'Intervention<br />";
    $message .= "Vous souhaitant bonne réception de ces éléments, nous restons à votre disposition pour tout complément d'information.<br />";
    $message .= '<br/>Très courtoisement.';
    $message .= "<br /><br /><b>Le Service Technique</b><br />OLYS - 2 rue des Erables - CS21055 - 69760 LIMONEST<br />";

    $bm = new BimpMail($subject, $email_cli, '', $message, $email_comm, $email_comm);

    if (file_exists($file)) {
        $bm->addFile(array($file, 'application/pdf', $ref . '.pdf'));
    }
    $bm->send();
}
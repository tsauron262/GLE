<?php

require_once "../../master.inc.php";
require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT."/synopsistools/SynDiversFunction.php";
$object = BimpObject::getInstance('bimptechnique', "BT_ficheInter");
if($object->find(['public_signature_url' => $_POST['key']], 1)) {
    
    $object->updateField('signed', 1);
    $object->updateField('base_64_signature', $_POST['signature']);
    
    
    $object->updateField('fk_statut', 1);
    $commercial = $object->getCommercialClient();
    $email_comm = $commercial->getData('email');
    $email_cli = $object->getData('email_signature');
    $file = $conf->ficheinter->dir_output . '/' . $object->dol_object->ref . '/' . $object->dol_object->ref . '.pdf';
    $object->actionGeneratePdf([]);
    $message = "Bonjour,<br />Veuillez trouver ci-joint notre Fiche d'Intervention<br />";
    $message .= "Vous souhaitant bonne réception de ces éléments, nous restons à votre disposition pour tout complément d'information.<br />";
    $message .= '<br/>Très courtoisement.';
    $message .= "<br /><br /><b>Le Service Technique</b><br />OLYS - 2 rue des Erables - CS21055 - 69760 LIMONEST<br />";
    
    mailSyn2("Fiche d'intervention N°" . $object->getRef(), "$email_cli,$email_comm", null, $message, array($file), array('application/pdf'), array($instance->dol_object->ref . '.pdf'), "");
    
}

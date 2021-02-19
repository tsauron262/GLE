<?php

class fiController extends BimpController {
    
    protected function ajaxProcessSignFi() {
        global $user, $db, $conf;
        $controlle = BimpTools::getPostFieldValue('controlle');
        $base64 = BimpTools::getPostFieldValue('base64');
        $nom = BimpTools::getPostFieldValue('nom');
        $isChecked = BimpTools::getPostFieldValue('isChecked');
        $itsOkForSign = (is_null($controlle)) ? false : true;
        $email = BimpTools::getPostFieldValue('email');
        $success = "";
        $errors = array();
        if(!$itsOkForSign && $isChecked == 'false') { $errors[] = "Il doit y avoir une signature"; }
        if(empty($nom)) { $errors[] = "Il doit y avoir un nom de signataire"; }
        if(empty($email)) { $errors[] = "Il doit y avoir un email"; }
        
        if(!empty($email)) {
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email invalide";
            }
        }
        
        
        $instance = BimpObject::getInstance('bimptechnique', 'BT_ficheInter', $_REQUEST['id']);
        
        if(!count($instance->getChildrenList("inters"))) {
            $errors[] = "Vous ne pouvez pas faire signer une fiche d'intervention sans intervention";
        }
                
        $auto_terminer = in_array($instance->getData('fk_soc'), explode(',', BimpCore::getConf('bimptechnique_id_societe_auto_terminer'))) ? true : false;
        
        if(!count($errors)) {
            if($instance->isLoaded()) {
                $instance->updateField('signed', 1);
                $instance->updateField('email_signature', $email);
                if($isChecked == 'false') {
                    $instance->updateField('base_64_signature', $base64);
                }
                $errors = $instance->updateField('signataire', $nom);
                if(!count($errors)) {
                    $instance->dol_object->setValid($user);
                    
                    $today = date('Y-m-d');
                    
                    $sql_where = 'code = "RC_RDV" AND fk_user_author = ' . $user->id . ' AND datep LIKE "'.$today.'%"';
                    
                    $bdd = new BimpDb($db);
                    
                    $id_actionComm = $bdd->getValue('actioncomm', 'id', $sql_where);
                    
//                    if(count(json_decode($instance->getData('tickets'))) > 0) {
//                        foreach(json_decode($instance->getData('tickets')) as $id_ticket) {
//                            $ticket = BimpObject::getInstance('bimpsupport', "BS_Ticket", $id_ticket);
//                            if($ticket->getData('')) {
//                                
//                            }
//                        }
//                    }
                    
                    if($id_actionComm > 0) {
                        $actionComm = $this->getInstance("bimpcore", "Bimp_ActionComm", $id_actionComm);
                        $actionComm->updateField("percent", 100);
                    }
                    
                    $id_tech = $instance->getData('fk_user_author');
                    $teck = BimpObject::getInstance('bimpcore', 'Bimp_User', $id_tech);
                    $email_tech = $teck->getdata('email');
                    $mail_client = $instance->getData('email_signature');
                    $commercial = $instance->getCommercialClient();
                    $email_commercial = $commercial->getData('email');
                    $instance->actionGeneratePdf([]);
                    $file = $conf->ficheinter->dir_output . '/' . $instance->dol_object->ref . '/' . $instance->dol_object->ref . '.pdf';
                    
                    $message = "Bonjour, voici votre fiche d'intervention " . $instance->getLink();
                    $instance->fetch($instance->id);
                    if(!$instance->getData('base_64_signature')) {
                        $message .= ", merci de la signée et l'envoyer à votre commercial.";
                    }
                    else
                        $message .= ' signée';
                    $success = "Rapport signé avec succès";
                    if($auto_terminer) {
                        $message .= "<br />";
                        $message.= "L'intervention en interne à été signée par le technicien et terminée. La FI à été marquée comme terminée automatiquement.";
                        $success = "Rapport signé et terminé avec succès";
                        $instance->updateField('fk_statut', 2);
                    } else {
                        if($isChecked == 'false') {
                            $instance->updateField('fk_statut', 1);
                        } else {
                            $instance->updateField('fk_statut', 4);
                        }
                    }
                    
                    mailSyn2("Fiche d'intervention N°" . $instance->dol_object->ref, "$email, $email_commercial", "admin@bimp.fr", $message, array($file), array('application/pdf'), array($instance->dol_object->ref . '.pdf'), "", $email_tech);
                    
                    
                }
            } else {
                $errors[] = "Erreur lors du load de la fiche d'intervention";
            }
        }
        
        die(json_encode(array(
            'success_callback'    => "window.location.href = '".DOL_URL_ROOT."/bimptechnique/?fc=fi&id=".$instance->id."'",
            'request_id' => BimpTools::getValue('request_id', 0), // Imporant
            'errors' => $errors
        )));
    }
    
    
    
}
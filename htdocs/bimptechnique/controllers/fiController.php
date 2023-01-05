<?php

class fiController extends BimpController
{

    protected function ajaxProcessSignFi()
    {
        // Utiliser de préférence une action objet pour ce genre de processus 
        // (Tout les traitements propres à un objet doivent être placés dans la classe de cet objet => ça permet de s'y retrouver facilement). 

//        global $user, $db, $conf, $mysoc;
//
//        $success = "";
//        $errors = array();
//
//        $controlle = BimpTools::getPostFieldValue('controlle');
//        $base64 = BimpTools::getPostFieldValue('base64');
//        $nom = BimpTools::getPostFieldValue('nom');
//        $isChecked = BimpTools::getPostFieldValue('isChecked'); // Papier
//        $farSign = BimpTools::getPostFieldValue("farSign"); // Dist
//        $sign = BimpTools::getPostFieldValue("sign"); // Elec
//        $itsOkForSign = (is_null($controlle)) ? false : true;
//        $email = BimpTools::getPostFieldValue('email');
//        $preco = BimpTools::getPostFieldValue('preco');
//        $attente_client = BimpTools::getPostFieldValue('attente');
//        $noFinish = BimpTools::getPostFieldValue("noFinish");
//        $contact_commercial = BimpTools::getPostFieldValue("contactCommercial");
//
//        if (!$itsOkForSign && $isChecked == 'false' && $farSign == 'false') {
//            $errors[] = "Il doit y avoir une signature";
//        }
//        if (empty($nom) && $farSign == 'false') {
//            $errors[] = "Il doit y avoir un nom de signataire";
//        }
//        if (empty($email)) {
//            $errors[] = "Il doit y avoir un email";
//        }
//
//        if (!empty($email)) {
//            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//                $errors[] = "Email invalide";
//            }
//        }
//
//        $instance = BimpObject::getInstance('bimptechnique', 'BT_ficheInter', $_REQUEST['id']);
//        $services_executed = $instance->getServicesExecutedArray();
//        $inter_non_vendu = $instance->getServicesByTypeArray(4);
//        $dep_non_vendu = $instance->getServicesByTypeArray(3);
//        $imponderable = $instance->getServicesByTypeArray(1);
//
////        $errors[] = "<pre>" . print_r($services_executed, 1) . "</pre>";
////        $errors[] = "<pre>" . print_r($inter_non_vendu, 1) . "</pre>";
////        $errors[] = "<pre>" . print_r($dep_non_vendu, 1) . "</pre>";
////        $errors[] = "<pre>" . print_r($imponderable, 1) . "</pre>";
//
//        if (!count($instance->getChildrenList("inters"))) {
//            $errors[] = "Vous ne pouvez pas faire signer une fiche d'intervention sans intervention";
//        }
//
//        $auto_terminer = in_array($instance->getData('fk_soc'), explode(',', BimpCore::getConf('id_societe_auto_terminer', null, 'bimptechnique'))) ? true : false;
//
//        if (!count($errors)) {
//            
////            $errors[] = "Nouvelle fonctionnalité -> Update agenda tech";
////            // Get current planning
////            $children = $instance->getChildrenList("inters");
////            $planningArray = [];
////            $start_dateTime = 0;
////            $plus_grand_dateTime = 0;
////            foreach($children as $id_child) {
////                $child = $instance->getChildObject('inters', $id_child);
//////            $planningArray[$child->getData('date')] = "";
////            }
////            $errors[] = "<pre>".print_r($planningArray, 1)."</pre>";
//
//            $mailOk = false;
//            if ($instance->isLoaded($errors) && !count($errors)) {
//                $ref = $instance->getData('ref');
//                $instance->updateField('signed', 1);
//                if ($farSign == 'true') {
//                    $instance->updateField('type_signature', 1);
//                    //$instance->updateField('fk_statut', 4);
//                    // Envois mail + Générate code
//                    $instance->dol_object->setValid($user);
//                    $ref = $instance->dol_object->ref;
//                    $instance->updateField('signed', 0);
//                    $instance->farSign($email);
//                    $mailOk = true;
//                    $isChecked = 'true';
//                } elseif ($isChecked == 'true') {
//                    $instance->updateField('type_signature', 2);
//                } elseif ($sign == 'true') {
//                    $instance->updateField('type_signature', 3);
//                }
//                $instance->updateField('date_signed', date('Y-m-d H:i:s'));
//                $instance->updateField('email_signature', $email);
//                if ($isChecked == 'false' && $farSign == 'false') {
//                    $instance->updateField('base_64_signature', $base64);
//                }
//                if ($farSign == 'false') {
//                    $errors = $instance->updateField('signataire', $nom);
//                }
//
//                if (!count($errors)) {
//                    if (!empty($attente_client)) {
//                        $instance->updateField("attente_client", $attente_client);
//                    }
//                    if (!empty($preco)) {
//                        $instance->updateField('note_public', $preco);
//                    }
//                    if (!empty($noFinish)) {
//                        $instance->updateField("no_finish_reason", $noFinish);
//                    }
//                    if ($contact_commercial == "true") {
//                        $instance->updateField('client_want_contact', 1);
//                    }
//
//                    $instance->dol_object->setValid($user);
//                    $ref = $instance->dol_object->ref;
//
//                    $today = date('Y-m-d');
//
//                    $sql_where = 'code = "RC_RDV" AND userownerid = ' . $user->id . ' AND datep LIKE "' . $today . '%"';
//                    $bdd = new BimpDb($db);
//
//                    $id_actionComm = $bdd->getValue('actioncomm', 'id', $sql_where);
//
//                    if ($id_actionComm > 0) {
//                        $actionComm = $this->getInstance("bimpcore", "Bimp_ActionComm", $id_actionComm);
//                        $actionComm->updateField("percent", 100);
//                    }
//
//                    $id_tech = $instance->getData('fk_user_author');
//                    $teck = BimpObject::getInstance('bimpcore', 'Bimp_User', $id_tech);
//                    $email_tech = $teck->getdata('email');
//                    $mail_client = $instance->getData('email_signature');
//                    $commercial = $instance->getCommercialClient();
//                    $email_commercial = $commercial->getData('email');
//                    $instance->actionGeneratePdf([]);
//
//                    $message = "Bonjour,<br />Veuillez trouver ci-joint notre Fiche d'Intervention<br />";
//                    $instance->fetch($instance->id);
//                    if (!$instance->getData('base_64_signature')) {
//                        $message .= "Merci de bien vouloir l'envoyer par email à votre interlocuteur commercial, dûment complétée et signée.<br />";
//                    }
//
//                    $message .= "Vous souhaitant bonne réception de ces éléments, nous restons à votre disposition pour tout complément d'information.<br />";
//
//                    if (!$instance->getData('base_64_signature')) {
//                        $message .= "Dans l'attente de votre retour.<br />";
//                    }
//
//                    $success = "Rapport signé avec succès";
//                    if ($auto_terminer) {
//                        $message = "Bonjour, pour informations : <br />";
//                        $message .= "L'intervention " . $instance->getLink() . " en interne à été signée par le technicien et terminée. La FI à été marquée comme terminée automatiquement.";
//                        $success = "Rapport signé et terminé avec succès";
//                        $instance->updateField('fk_statut', 2);
//                    } else {
//                        if ($isChecked == 'false') {
//                            $instance->updateField('fk_statut', 1);
//                        } else {
//                            $instance->updateField('fk_statut', 4);
//                        }
//                    }
//                    
//                    $message .= '<br/>Très courtoisement.';
//                    $message .= "<br /><br /><b>Le Service Technique</b><br />OLYS - 2 rue des Erables - CS21055 - 69760 LIMONEST<br />";
//
//                    $new_facturation_line = BimpCache::getBimpObjectInstance("bimptechnique", "BT_ficheInter_facturation");
//
//                    if (count($services_executed)) {
//                        foreach ($services_executed as $id_line_commande => $informations) {
//                            $new_facturation_line->set('fk_fichinter', $instance->id);
//                            $new_facturation_line->set('id_commande', $informations["commande"]);
//                            $new_facturation_line->set('id_commande_line', $id_line_commande);
//                            $new_facturation_line->set('fi_lines', json_encode($informations['lines']));
//                            $new_facturation_line->set('is_vendu', 1);
//                            $new_facturation_line->set('total_ht_vendu', $informations['ht_vendu']);
//                            $new_facturation_line->set('tva_tx', 20);
//                            $new_facturation_line->set('total_ht_depacement', ($informations['ht_executed'] - $informations['ht_vendu']));
//                            $new_facturation_line->set('remise', 0);
//                            $errors = $new_facturation_line->create($warnings);
//                        }
//                    }
//
//                    if (count($inter_non_vendu)) {
//                        foreach ($inter_non_vendu as $index => $informations) {
//                            $new_facturation_line->set('fk_fichinter', $instance->id);
//                            $new_facturation_line->set('id_commande', 0);
//                            $new_facturation_line->set('id_commande_line', 0);
//                            $new_facturation_line->set('fi_lines', json_encode(array($informations['line'])));
//                            $new_facturation_line->set('is_vendu', 0);
//                            $new_facturation_line->set('total_ht_vendu', 0);
//                            $new_facturation_line->set('tva_tx', 20);
//                            $new_facturation_line->set('total_ht_depacement', $informations['tarif']);
//                            $new_facturation_line->set('remise', 0);
//                            $errors = $new_facturation_line->create($warnings);
//                        }
//                    }
//
//                    if (count($dep_non_vendu)) {
//                        foreach ($dep_non_vendu as $index => $informations) {
//                            $new_facturation_line->set('fk_fichinter', $instance->id);
//                            $new_facturation_line->set('id_commande', 0);
//                            $new_facturation_line->set('id_commande_line', 0);
//                            $new_facturation_line->set('fi_lines', json_encode(array($informations['line'])));
//                            $new_facturation_line->set('is_vendu', 0);
//                            $new_facturation_line->set('total_ht_vendu', 0);
//                            $new_facturation_line->set('tva_tx', 20);
//                            $new_facturation_line->set('total_ht_depacement', $informations['tarif']);
//                            $new_facturation_line->set('remise', 0);
//                            $errors = $new_facturation_line->create($warnings);
//                        }
//                    }
//
//                    if (count($imponderable)) {
//                        foreach ($imponderable as $index => $informations) {
//                            $new_facturation_line->set('fk_fichinter', $instance->id);
//                            $new_facturation_line->set('id_commande', 0);
//                            $new_facturation_line->set('id_commande_line', 0);
//                            $new_facturation_line->set('fi_lines', json_encode(array($informations['line'])));
//                            $new_facturation_line->set('is_vendu', 0);
//                            $new_facturation_line->set('total_ht_vendu', 0);
//                            $new_facturation_line->set('tva_tx', 20);
//                            $new_facturation_line->set('total_ht_depacement', $informations['tarif']);
//                            $new_facturation_line->set('remise', 0);
//                            $errors = $new_facturation_line->create($warnings);
//                        }
//                    }
//
//                    if (!$mailOk) {
//                        $logo = $mysoc->logo;
//                        $testFile = str_replace(array(".jpg", ".png"), "_PRO.png", $logo);
//                        if ($testFile != $logo)
//                            $logo = $testFile;
//                        $message .= '<img width="25%" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=' . $logo . '">';
//
//                        $file = $conf->ficheinter->dir_output . '/' . $ref . '/' . $ref . '.pdf';
//
//                        if (is_file($file)) {
//                            //envois au commecial
//                            mailSyn2("Fiche d'intervention N°" . $ref . " - [COMMERCIAL UNIQUEMENT]", "$email_commercial", "gle@bimp.fr", $message . "<br />Lien vers la FI: " . $instance->getNomUrl(), array($file), array('application/pdf'), array($ref . '.pdf'), "", /* temporaire pour controle */ 'at.bernard@bimp.fr, t.sauron@bimp.fr');
//                            //envois au client
//                            mailSyn2("Fiche d'intervention N°" . $ref, "$email", "gle@bimp.fr", $message, array($file), array('application/pdf'), array($ref . '.pdf'), "", $email_tech);
//                        } else {
//                            BimpCore::addlog('Probléme de fichier ' . $file, 1, 'bimptechnique', null, array('dol_object' => print_r($instance->dol_object, 1)));
//                            $errors[] = 'Mail non envoyé PDF non trouvé';
//                        }
//                    }
//                }
//            }
//        }
//
//        die(json_encode(array(
//            'success_callback' => "window.location.href = '" . DOL_URL_ROOT . "/bimptechnique/?fc=fi&id=" . $instance->id . "'",
//            'request_id'       => BimpTools::getValue('request_id', 0), // Important
//            'errors'           => $errors
//        )));
    }
}

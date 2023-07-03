<?php

// Entité: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/classes/BWSApi.php';

BWSApi::$requests['setDemandeFinancementStatus'] = array(
    'desc'   => 'Enregistrer le nouveau statut d\'une demande de location',
    'params' => array(
        'demande_target' => array('label' => 'Destinataire de la demande de location', 'required' => 1),
        'id_demande'     => array('label' => 'ID demande de location', 'data_type' => 'id', 'required' => 1),
        'type_origine'   => array('label' => 'Type de pièce d\'origine', 'required' => 1),
        'id_origine'     => array('label' => 'ID pièce d\'origine', 'data_type' => 'id', 'required' => 1),
        'status'         => array('label' => 'Nouveau statut', 'data_type' => 'int', 'required' => 1),
        'note'           => array('label' => 'Note', 'data_type' => 'text', 'default' => '')
    )
);

BWSApi::$requests['newDemandeFinancementNote'] = array(
    'desc'           => 'Notifier d\'une nouvelle note pour une demande de location',
    'demande_target' => array('label' => 'Destinataire de la demande de location', 'required' => 1),
    'id_demande'     => array('label' => 'ID demande de location', 'data_type' => 'id', 'required' => 1),
    'type_origine'   => array('label' => 'Type de pièce d\'origine', 'required' => 1),
    'id_origine'     => array('label' => 'ID pièce d\'origine', 'data_type' => 'id', 'required' => 1),
    'note'           => array('label' => 'Note', 'data_type' => 'text', 'default' => '')
);

BWSApi::$requests['sendDocFinancement'] = array(
    'desc'   => 'Envoi d\'un document PDF de location',
    'params' => array(
        'demande_target'   => array('label' => 'Destinataire de la demande de location', 'required' => 1),
        'id_demande'       => array('label' => 'ID demande de location', 'data_type' => 'id', 'required' => 1),
        'type_origine'     => array('label' => 'Type de pièce d\'origine', 'required' => 1),
        'id_origine'       => array('label' => 'ID pièce d\'origine', 'data_type' => 'id', 'required' => 1),
        'doc_type'         => array('label' => 'Type de document'),
        'docs_content'     => array('label' => 'Fichier(s)', 'data_type' => 'json', 'required' => 1),
        'signature_params' => array('label' => 'Paramètres signature', 'data_type' => 'json', 'required' => 1),
        'signataires_data' => array('label' => 'Données signataires', 'data_type' => 'json')
    )
);

BWSApi::$requests['setDocFinancementStatus'] = array(
    'desc'   => 'Définir le statut du document d\'une demande de location',
    'params' => array(
        'demande_target' => array('label' => 'Destinataire de la demande de location', 'required' => 1),
        'id_demande'     => array('label' => 'ID demande de location', 'data_type' => 'id', 'required' => 1),
        'type_origine'   => array('label' => 'Type de pièce d\'origine', 'required' => 1),
        'id_origine'     => array('label' => 'ID pièce d\'origine', 'data_type' => 'id', 'required' => 1),
        'doc_type'       => array('label' => 'Type de document'),
        'status'         => array('label' => 'Nouveau statut')
    )
);

BWSApi::$requests['reopenDemandeFinancement'] = array(
    'demande_target' => array('label' => 'Destinataire de la demande de location', 'required' => 1),
    'id_demande'     => array('label' => 'ID demande de location', 'data_type' => 'id', 'required' => 1),
    'type_origine'   => array('label' => 'Type de pièce d\'origine', 'required' => 1),
    'id_origine'     => array('label' => 'ID pièce d\'origine', 'data_type' => 'id', 'required' => 1),
    'status'         => array('label' => 'Statut de la demande de financement', 'data_type' => 'int', 'required' => 1)
);

BWSApi::$requests['getContractInfo'] = array(
);

class BWSApi_ExtEntity extends BWSApi
{

    // Requêtes: 

    protected function wsRequest_setDemandeFinancementStatus()
    {
        $response = array();

        if (!count($this->errors)) {
            $bcdf_class = '';
            BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin', $bcdf_class);
            $origine = $bcdf_class::getOrigineFromType($this->getParam('type_origine', ''), (int) $this->getParam('id_origine', 0));

            if (!BimpObject::objectLoaded($origine)) {
                $this->addError('UNFOUND', 'Pièce d\'origine non trouvée (Type: "' . $this->getParam('type_origine', '') . '" - ID: ' . $this->getParam('id_origine', 0) . ')');
            } else {
                $bcdf = BimpCache::findBimpObjectInstance('bimpcommercial', 'BimpCommDemandeFin', array(
                            'obj_module' => $origine->module,
                            'obj_name'   => $origine->object_name,
                            'id_obj'     => $origine->id,
                            'target'     => $this->getParam('demande_target', ''),
                            'id_ext_df'  => (int) $this->getParam('id_demande', 0)
                ));

                if (!BimpObject::objectLoaded($bcdf)) {
                    $this->addError('UNFOUND', 'Aucune demande de location trouvée pour l\'ID externe ' . $this->getParam('id_demande', 0));
                } else {
                    $errors = $bcdf->setNewStatusFromTarget($this->getParam('status', ''), $this->getParam('note', ''));

                    if (count($errors)) {
                        $this->addError('FAIL', BimpTools::getMsgFromArray($errors, '', true));
                    }
                }
            }
        }

        return $response;
    }

    protected function wsRequest_newDemandeFinancementNote()
    {
        $response = array();

        if (!count($this->errors)) {
            $bcdf_class = '';
            BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin', $bcdf_class);
            $origine = $bcdf_class::getOrigineFromType($this->getParam('type_origine', ''), (int) $this->getParam('id_origine', 0));

            if (!BimpObject::objectLoaded($origine)) {
                $this->addError('UNFOUND', 'Pièce d\'origine non trouvée (Type: "' . $this->getParam('type_origine', '') . '" - ID: ' . $this->getParam('id_origine', 0) . ')');
            } else {
                $bcdf = BimpCache::findBimpObjectInstance('bimpcommercial', 'BimpCommDemandeFin', array(
                            'obj_module' => $origine->module,
                            'obj_name'   => $origine->object_name,
                            'id_obj'     => $origine->id,
                            'target'     => $this->getParam('demande_target', ''),
                            'id_ext_df'  => (int) $this->getParam('id_demande', 0)
                ));

                if (!BimpObject::objectLoaded($bcdf)) {
                    $this->addError('UNFOUND', 'Aucune demande de location trouvée pour l\'ID externe ' . $this->getParam('id_demande', 0));
                } else {
                    $errors = $bcdf->onNewNoteFromTarget($this->getParam('note', ''));

                    if (count($errors)) {
                        $this->addError('FAIL', BimpTools::getMsgFromArray($errors, '', true));
                    }
                }
            }
        }

        return $response;
    }

    protected function wsRequest_sendDocFinancement()
    {
        $response = array();

        if (!count($this->errors)) {
            $bcdf_class = '';
            BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin', $bcdf_class);
            $origine = $bcdf_class::getOrigineFromType($this->getParam('type_origine', ''), (int) $this->getParam('id_origine', 0));

            if (!BimpObject::objectLoaded($origine)) {
                $this->addError('UNFOUND', 'Pièce d\'origine non trouvée (Type: "' . $this->getParam('type_origine', '') . '" - ID: ' . $this->getParam('id_origine', 0) . ')');
            } else {
                $bcdf = BimpCache::findBimpObjectInstance('bimpcommercial', 'BimpCommDemandeFin', array(
                            'obj_module' => $origine->module,
                            'obj_name'   => $origine->object_name,
                            'id_obj'     => $origine->id,
                            'target'     => $this->getParam('demande_target', ''),
                            'id_ext_df'  => (int) $this->getParam('id_demande', 0)
                ));

                if (!BimpObject::objectLoaded($bcdf)) {
                    $this->addError('UNFOUND', 'Aucune demande de location trouvée pour l\'ID externe ' . $this->getParam('id_demande', 0));
                } else {
                    $errors = $bcdf->onDocFinReceived($this->getParam('doc_type', '') . '_fin', $this->getParam('docs_content', ''), $this->getParam('signature_params', array()), $this->getParam('signataires_data', array()));

                    if (count($errors)) {
                        $this->addError('FAIL', BimpTools::getMsgFromArray($errors, '', true));
                    }
                }
            }
        }

        return $response;
    }

    protected function wsRequest_setDocFinancementStatus()
    {
        $response = array();

        if (!count($this->errors)) {
            $bcdf_class = '';
            BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin', $bcdf_class);
            $origine = $bcdf_class::getOrigineFromType($this->getParam('type_origine', ''), (int) $this->getParam('id_origine', 0));

            if (!BimpObject::objectLoaded($origine)) {
                $this->addError('UNFOUND', 'Pièce d\'origine non trouvée (Type: "' . $this->getParam('type_origine', '') . '" - ID: ' . $this->getParam('id_origine', 0) . ')');
            } else {
                $bcdf = BimpCache::findBimpObjectInstance('bimpcommercial', 'BimpCommDemandeFin', array(
                            'obj_module' => $origine->module,
                            'obj_name'   => $origine->object_name,
                            'id_obj'     => $origine->id,
                            'target'     => $this->getParam('demande_target', ''),
                            'id_ext_df'  => (int) $this->getParam('id_demande', 0)
                ));

                if (!BimpObject::objectLoaded($bcdf)) {
                    $this->addError('UNFOUND', 'Aucune demande de location trouvée pour l\'ID externe ' . $this->getParam('id_demande', 0));
                } else {
                    $errors = $bcdf->setDocFinStatus($this->getParam('doc_type', '') . '_fin', (int) $this->getParam('status', ''));

                    if (count($errors)) {
                        $this->addError('FAIL', BimpTools::getMsgFromArray($errors, '', true));
                    }
                }
            }
        }

        return $response;
    }

    protected function wsRequest_getDemandeFinancementInfos()
    {
        $response = array();

        if (!count($this->errors)) {
            $id_demande = (int) $this->getParam('id_demande', 0);
            $demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande);

            if (!BimpObject::objectLoaded($demande)) {
                $this->addError('UNFOUND', 'La demande de location #' . $id_demande . ' n\'existe pas');
            } else {
                $response = array(
                    'ref'       => $demande->getRef(),
                    'status'    => $demande->displayData('status', 'default', false),
                    'user_resp' => array(
                        'name'  => '',
                        'email' => '',
                        'phone' => ''
                    ),
                    'logs'      => array(),
                );

                $user_resp = $demande->getChildObject('user_resp');
                if (BimpObject::objectLoaded($user_resp)) {
                    $response['user_resp']['name'] = $user_resp->getName();
                    $response['user_resp']['email'] = $user_resp->getData('email');
                    $response['user_resp']['phone'] = $user_resp->getData('office_phone');
                }

                $logs = $demande->getObjectLogs();

                foreach ($logs as $log) {
                    $user = $log->getChildObject('user');
                    $date = $log->getData('date');

                    $str = '<b>Le ' . date('d / m / Y à H:i', strtotime($date));

                    if (BimpObject::objectLoaded($user)) {
                        $str .= ' par ' . $user->getName();
                    }

                    $str .= ' : </b>' . strip_tags($log->getData('msg'));

                    $response['logs'][] = $str;
                }
            }
        }

        return $response;
    }

    protected function wsRequest_reopenDemandeFinancement()
    {
        $response = array();

        if (!count($this->errors)) {
            $bcdf_class = '';
            BimpObject::loadClass('bimpcommercial', 'BimpCommDemandeFin', $bcdf_class);
            $origine = $bcdf_class::getOrigineFromType($this->getParam('type_origine', ''), (int) $this->getParam('id_origine', 0));

            if (!BimpObject::objectLoaded($origine)) {
                $this->addError('UNFOUND', 'Pièce d\'origine non trouvée (Type: "' . $this->getParam('type_origine', '') . '" - ID: ' . $this->getParam('id_origine', 0) . ')');
            } else {
                $bcdf = BimpCache::findBimpObjectInstance('bimpcommercial', 'BimpCommDemandeFin', array(
                            'obj_module' => $origine->module,
                            'obj_name'   => $origine->object_name,
                            'id_obj'     => $origine->id,
                            'target'     => $this->getParam('demande_target', ''),
                            'id_ext_df'  => (int) $this->getParam('id_demande', 0)
                ));

                if (!BimpObject::objectLoaded($bcdf)) {
                    $this->addError('UNFOUND', 'Aucune demande de location trouvée pour l\'ID externe ' . $this->getParam('id_demande', 0));
                } else {
                    $devis_status = 0;
                    $contrat_status = 0;

                    $errors = $bcdf->reopenFromTarget($this->getParam('status', 0), $devis_status, $contrat_status);

                    if (count($errors)) {
                        $this->addError('FAIL', BimpTools::getMsgFromArray($errors, '', true));
                    } else {
                        $response = array(
                            'success'        => 1,
                            'devis_status'   => $devis_status,
                            'contrat_status' => $contrat_status
                        );
                    }
                }
            }
        }

        return $response;
    }
    
    protected function wsRequest_getContractInfo(){
        return array(
                            'success'        => 1,
                            'contract_infos' => array('ref'=>'hjhjkhkjkh', 'cli'=> 'hjkhkjhj')
        );
    }
}

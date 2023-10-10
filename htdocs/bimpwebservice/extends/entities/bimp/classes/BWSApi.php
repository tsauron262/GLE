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
    'date_valid'   => array('label' => 'Date ou le contrat doit être actif', 'required' => 0), 
    'ref_cli'      => array('label' => 'Code client', 'required' => 0),
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

    protected function wsRequest_getContractInfo()
    {
        $return = array();
        $dateV = $this->getParam('date_valid', '');
        $refCli = $this->getParam('ref_cli', '');
        $filters = array();
        if ($dateV) {
            if ($dateV == 'today')
                $dateV = date('Y-m-d');;
            $filters['and_date'] = array('and_fields' => array(
                    'date_start'       => array(
                        'operator' => '<',
                        'value'    => $dateV
                    ),
                    'end_date_contrat' => array(
                        'operator' => '>',
                        'value'    => $dateV
            )));
        }
        if ($refCli) {
            $filters['client:code_client'] = $refCli;
        }

        if (!count($filters)) {
            $this->addError('FAIL', 'Merci de filtrer les résultat');
        } else {
            $list = BimpCache::getBimpObjectObjects('bimpcontract', 'BContract_contrat', $filters);

            foreach ($list as $contract) {
                $ln = array("ref" => $contract->getData("ref"), "status" => $contract->getData('statut'), "date_contrat" => $contract->getData("date_contrat"), "date_start" => $contract->getData("date_start"), "end_date_contrat" => $contract->getData("end_date_contrat"));
                $cli = $contract->getChildObject("client");
                $ln['client'] = array('ref' => $cli->getData('code_client'), 'nom' => $cli->getData('nom'));
                $contacts = $cli->getChildrenObjects('contacts');
                foreach ($contacts as $contact) {
                    $ln['client']['contacts'][] = array("nom" => $contact->displayNomComplet(), 'mail' => $contact->getData('email'));
                }
                $commerciaux = $cli->getCommercials();
                foreach ($commerciaux as $commercial) {
                    $ln['client']['commerciaux'][] = array("nom" => $commercial->getFullName(), 'mail' => $commercial->getData('email'));
                }

                $return[$contract->id] = $ln;
            }
            return array(
                'success'        => 1,
                'contract_infos' => $return
            );
        }
    }
}

<?php

// Entité: prolease

require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/classes/BWSApi.php';

BWSApi::$requests['addDemandeFinancement'] = array(
    'desc'   => 'Ajout d\'une demande de location',
    'params' => array(
        'data' => array('label'      => 'Données', 'data_type'  => 'json', 'sub_params' => array(
                'type_source' => array('label' => 'Source', 'required' => 1),
                'type_orgine' => array('label' => 'Type de pièce d\'origine', 'default' => 'propale'),
                'demande'     => array('label'      => 'Options demande', 'data_type'  => 'array', 'sub_params' => array(
                        'duration'    => array('label' => 'Durée', 'data_type' => 'int', 'required' => 1),
                        'periodicity' => array('label' => 'Périodicité', 'data_type' => 'int', 'required' => 1),
                        'mode_calcul' => array('label' => 'Mode de calcul', 'data_type' => 'int', 'required' => 1)
                    )),
                'origine'     => array('label'      => 'Pièce d\'origine', 'data_type'  => 'array', 'sub_params' => array(
                        'id'         => array('label' => 'ID', 'data_type' => 'id', 'required' => 1),
                        'ref'        => array('label' => 'Reférence', 'required' => 1),
                        'extra_data' => array('label' => 'Données supplémentaires', 'data_type' => 'array')
                    )),
                'lines'       => array('label' => 'Lignes', 'data_type' => 'array'),
                'client'      => array('label'      => 'Données client', 'data_type'  => 'json', 'sub_params' => array(
                        'id'              => array('label' => 'ID', 'data_type' => 'id', 'required' => 1),
                        'ref'             => array('label' => 'Reférence', 'required' => 1),
                        'nom'             => array('label' => 'Nom', 'required' => 1),
                        'is_company'      => array('label' => 'Entreprise', 'data_type' => 'bool', 'default' => 0),
                        'siret'           => array('label' => 'N° SIRET', 'required_if' => 'data/client/is_company'),
                        'siren'           => array('label' => 'N° SIREN', 'required_if' => 'data/client/is_company'),
                        'forme_juridique' => array('label' => 'Forme juridique', 'required_if' => 'data/client/is_company'),
                        'capital'         => array('label' => 'Capital social'),
                        'address'         => array('label'      => 'Adresse client (siège si pro)', 'data_type'  => 'array', 'sub_params' => array(
                                'address' => array('label' => 'Lignes adresse', 'required' => 1),
                                'zip'     => array('label' => 'Code postal', 'required' => 1),
                                'town'    => array('label' => 'Ville', 'required' => 1),
                                'pays'    => array('label' => 'Pays', 'default' => 'France')
                            )),
                        'contact'         => array('label'      => 'Infos contact', 'data_type'  => 'array', 'sub_params' => array(
                                'nom'    => array('label' => 'Nom'),
                                'prenom' => array('label' => 'Prénom'),
                                'email'  => array('label' => 'Adresse e-mail'),
                                'tel'    => array('label' => 'Tel.'),
                                'mobile' => array('label' => 'Tel. (mobile)')
                            )),
//                        'signataire'      => array('label'      => 'Signataire', 'data_type'  => array(), 'sub_params' => array(
//                                'nom'      => array('label' => 'Nom', 'required' => 1),
//                                'prenom'   => array('label' => 'Nom', 'required' => 1),
//                                'fonction' => array('label' => 'Fonction', 'required_if' => 'data/client/is_company'),
//                            )),
                        'livraisons'      => array('label' => 'Adresses de livraison', 'data_type' => 'array')
                    )),
                'commercial'  => array('label'      => 'Données commercial', 'data_type'  => 'array', 'sub_params' => array(
                        'id'    => array('label' => 'ID', 'data_type' => 'id'),
                        'nom'   => array('label' => 'Nom'),
                        'email' => array('label' => 'Adresse e-mail'),
                        'tel'   => array('label' => 'Tel.'),
                    )),
                'demande'     => array('label' => 'Données supplémentaires', 'data_type' => 'json')
            )),
    )
);
BWSApi::$requests['cancelDemandeFinancement'] = array(
    'desc'   => 'Annulation d\'une demande de location',
    'params' => array(
        'id_demande' => array('label' => 'ID Demande', 'data_type' => 'id', 'required' => 1),
        'note'       => array('label' => 'Note', 'data_type' => 'text', 'default' => '')
    )
);
BWSApi::$requests['getDemandeFinancementInfos'] = array(
    'desc'   => 'Retourne les infos d\'une demande de location',
    'params' => array(
        'id_demande' => array('label' => 'ID Demande', 'data_type' => 'id', 'required' => 1)
    )
);
BWSApi::$requests['setDemandeFinancementDocSigned'] = array(
    'desc'   => 'Notifier la signature d\'un document d\'une demande de location',
    'params' => array(
        'id_demande'  => array('label' => 'ID Demande', 'data_type' => 'id', 'required' => 1),
        'doc_type'    => array('label' => 'Type de document', 'required' => 1),
        'doc_content' => array('label' => 'Fichier', 'required' => 1)
    )
);
BWSApi::$requests['setDemandeFinancementDocRefused'] = array(
    'desc'   => 'Refus d\'un document d\'une demande de location',
    'params' => array(
        'id_demande' => array('label' => 'ID Demande', 'data_type' => 'id', 'required' => 1),
        'doc_type'   => array('label' => 'Type de document', 'required' => 1),
        'note'       => array('label' => 'Note', 'data_type' => 'text', 'default' => '')
    )
);

class BWSApi_ExtEntity extends BWSApi
{

    // Requêtes: 

    protected function wsRequest_addDemandeFinancement()
    {
        $response = array();

        if (!count($this->errors)) {
            $BF_Demande_class = '';
            BimpObject::loadClass('bimpfinancement', 'BF_Demande', $BF_Demande_class);

            $errors = array();
            $warnings = array();

            $demande = $BF_Demande_class::createFromSource($this->getParam('data'), $errors, $warnings);

            if (!count($errors) && BimpObject::objectLoaded($demande)) {
                $demande->addObjectLog('Créé via webservice - compte utilisateur: ' . $this->ws_user->getLink());
                $response = array(
                    'success'     => 1,
                    'id_demande'  => $demande->id,
                    'ref_demande' => $demande->getRef()
                );

                if (!empty($warnings)) {
                    $response['warnings'] = $warnings;
                }
            } else {
                $this->addError('CREATION_FAIL', BimpTools::getMsgFromArray($errors, 'Echec de la création de la demande de location', true));
            }
        }

        return $response;
    }

    protected function wsRequest_cancelDemandeFinancement()
    {
        $response = array();

        if (!count($this->errors)) {
            $id_demande = (int) $this->getParam('id_demande', 0);
            $demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande);

            if (!BimpObject::objectLoaded($demande)) {
                $this->addError('UNFOUND', 'La demande de location #' . $id_demande . ' n\'existe pas');
            } else {
                $errors = $demande->onDemandeCancelledBySource($this->getParam('note', ''));

                if (count($errors)) {
                    $this->addError('FAIL', BimpTools::getMsgFromArray($errors, '', true));
                } else {
                    $response['success'] = 1;
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
                $status = (int) $demande->getData('status');
                $response = array(
                    'ref'               => $demande->getRef(),
                    'status'            => $status,
                    'status_label'      => $demande->displayData('status', 'default', false),
                    'duration'          => (int) $demande->getData('duration'),
                    'periodicity'       => (int) $demande->getData('periodicity'),
                    'periodicity_label' => $demande->displayData('periodicity', 'default', false),
                    'nb_loyers'         => $demande->getNbLoyers(),
                    'montants'          => array(),
                    'user_resp'         => array(
                        'name'  => '',
                        'email' => '',
                        'phone' => ''
                    ),
                    'logs'              => array(),
                    'notes'             => array()
                );

                if ($status >= 10 && $status < 20) {
                    $response['montants']['loyer_mensuel_evo_ht'] = $demande->getData('loyer_mensuel_evo_ht');
                    $response['montants']['loyer_mensuel_dyn_ht'] = $demande->getData('loyer_mensuel_dyn_ht');
                    $response['montants']['loyer_mensuel_suppl_ht'] = $demande->getData('loyer_mensuel_suppl_ht');
                }

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

                    $str .= ' : </b>' . str_replace('[BR]', '<br/>', strip_tags(BimpTools::replaceBr($log->getData('msg'), '[BR]')));

                    $response['logs'][] = $str;
                }

                foreach ($demande->getNotes() as $note) {
                    $response['notes'][] = array(
                        'author'  => $note->displayAuthor(false, true),
                        'date'    => date('d / m / à H:i', strtotime($note->getData('date_create'))),
                        'content' => $note->getData('content')
                    );
                }
            }
        }

        return $response;
    }

    protected function wsRequest_setDemandeFinancementDocSigned()
    {
        $response = array();

        if (!count($this->errors)) {
            $id_demande = (int) $this->getParam('id_demande', 0);
            $demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande);

            if (!BimpObject::objectLoaded($demande)) {
                $this->addError('UNFOUND', 'La demande de location #' . $id_demande . ' n\'existe pas');
            } else {
                $errors = $demande->onDocSignedFromSource($this->getParam('doc_type', ''), $this->getParam('doc_content', ''));

                if (count($errors)) {
                    $this->addError('FAIL', BimpTools::getMsgFromArray($errors, '', true));
                } else {
                    $response['success'] = 1;
                }
            }
        }

        return $response;
    }

    protected function wsRequest_setDemandeFinancementDocRefused()
    {
        $response = array();

        if (!count($this->errors)) {
            $id_demande = (int) $this->getParam('id_demande', 0);
            $demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande);

            if (!BimpObject::objectLoaded($demande)) {
                $this->addError('UNFOUND', 'La demande de location #' . $id_demande . ' n\'existe pas');
            } else {
                $errors = $demande->onDocRefused($this->getParam('doc_type', ''), $this->getParam('note', ''));

                if (count($errors)) {
                    $this->addError('FAIL', BimpTools::getMsgFromArray($errors, '', true));
                } else {
                    $response['success'] = 1;
                }
            }
        }

        return $response;
    }
}

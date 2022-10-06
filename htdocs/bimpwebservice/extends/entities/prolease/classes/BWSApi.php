<?php

// Entité: prolease

require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/classes/BWSApi.php';

BWSApi::$requests['addDemandeFinancement'] = array(
    'desc'   => 'Ajout d\'une demande de financement',
    'params' => array(
        'propale'       => array('label' => 'Données devis', 'data_type' => 'json'),
        'propale_lines' => array('label' => 'Lignes devis', 'data_type' => 'json'),
        'client'        => array('label' => 'Données client', 'data_type' => 'json'),
        'contact'       => array('label' => 'Données contact', 'data_type' => 'json'),
        'commercial'    => array('label' => 'Données commercial', 'data_type' => 'json'),
        'extra_data'    => array('label' => 'Données supplémentaires', 'data_type' => 'json')
    )
);
BWSApi::$requests['getDemandeFinancementInfos'] = array(
    'desc'   => 'Retourne les infos d\'une demande de financement',
    'params' => array(
        'id_demande' => array('label' => 'ID Demande', 'data_type' => 'id', 'required' => 1)
    )
);
BWSApi::$requests['setDemandeFinancementDocSigned'] = array(
    'desc'   => 'Notifier la signature d\'un document d\'une demande de financement',
    'params' => array(
        'id_demande'  => array('label' => 'ID Demande', 'data_type' => 'id', 'required' => 1),
        'doc_type'    => array('label' => 'Type de document', 'required' => 1),
        'doc_content' => array('label' => 'Fichier', 'required' => 1)
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

            $demande = $BF_Demande_class::createFromExternalPropale($this->params, $errors, $warnings);

            if (BimpObject::objectLoaded($demande)) {
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
                $this->addError('CREATION_FAIL', BimpTools::getMsgFromArray($errors, 'Echec de la création de la demande de financement', true));
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
                $this->addError('UNFOUND', 'La demande de financement #' . $id_demande . ' n\'existe pas');
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
                );

                if ($status >= 10 && $status < 20) {
                    $response['montants']['loyer_ht'] = $demande->getLoyerAmountHT();
                    $response['montants']['total_loyers_ht'] = $demande->getTotalLoyersHT();
                    $response['montants']['total_loyers_tva'] = $demande->getTotalLoyersTVA();
                    $response['montants']['total_loyers_ttc'] = $demande->getTotalLoyersTTC();
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
                $this->addError('UNFOUND', 'La demande de financement #' . $id_demande . ' n\'existe pas');
            } else {
                $errors = $demande->onExternalDocSigned($this->getParam('doc_type', ''), $this->getParam('doc_content', ''));

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

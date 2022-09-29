<?php

// Entité: prolease

require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/classes/BWSApi.php';

BWSApi::$requests['addDemandeFinancement'] = array(
    'desc'   => 'Ajout d\'une demande de fincancement',
    'params' => array(
        'propale'       => array('label' => 'Données devis', 'data_type' => 'json'),
        'propale_lines' => array('label' => 'Lignes devis', 'data_type' => 'json'),
        'client'        => array('label' => 'Données client', 'data_type' => 'json'),
        'contact'       => array('label' => 'Données contact', 'data_type' => 'json'),
        'commercial'    => array('label' => 'Données commercial', 'data_type' => 'json'),
        'extra_data'    => array('label' => 'Données supplémentaires', 'data_type' => 'json')
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

            $demande = $BF_Demande_class::createFromBimpPropale($this->params, $errors, $warnings);

            if (BimpObject::objectLoaded($demande)) {
                $demande->addObjectLog('Créé via webservice - compte utilisateur: ' . $this->ws_user->getLink());
                $response = array(
                    'success'    => 1,
                    'id_demande' => $demande->id
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
}

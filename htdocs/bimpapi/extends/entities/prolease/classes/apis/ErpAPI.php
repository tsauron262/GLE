<?php

// Entité: proplease

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/ErpAPI.php';

ErpApi::$requests['setDemandeFinancementStatus'] = array(
    'label' => 'Soumettre nouveau statut de la demande de financement'
);
ErpApi::$requests['sendContratFinancement'] = array(
    'label' => 'Envoyer contrat de financement'
);

class ErpAPI_ExtEntity extends ErpAPI
{

    public function __construct($api_idx = 0, $id_user_account = 0, $debug_mode = false)
    {
        parent::__construct($api_idx, $id_user_account, $debug_mode);
    }

    public function setDemandeFinancementStatus($id_demande, $id_propal, $status, $note, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('setDemandeFinancementStatus', array(
            'fields' => array(
                'status'     => $status,
                'id_propal'  => $id_propal,
                'id_demande' => $id_demande,
                'note'       => $note
            )
                ), $errors);

        if (isset($response['warnings'])) {
            $warnings = BimpTools::merge_array($warnings, $response['warnings']);
            unset($response['warnings']);
        }

        if (!count($errors)) {
            return $response;
        }

        return null;
    }

    public function sendContratFinancement($id_demande, $id_propal, $doc_content, $signature_params, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('sendContratFinancement', array(
            'fields' => array(
                'id_propal'        => $id_propal,
                'id_demande'       => $id_demande,
                'doc_content'      => $doc_content,
                'signature_params' => $signature_params
            )), $errors);

        if (isset($response['warnings'])) {
            $warnings = BimpTools::merge_array($warnings, $response['warnings']);
            unset($response['warnings']);
        }

        if (!count($errors)) {
            return $response;
        }

        return null;
    }
}

<?php

// Entité: proplease

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/ErpAPI.php';

ErpApi::$requests['setDemandeFinancementStatus'] = array(
    'label' => 'Soumettre nouveau statut de la demande de location'
);
ErpApi::$requests['newDemandeFinancementNote'] = array(
    'label' => 'Nouvelle note d\'une demande de location'
);
ErpApi::$requests['sendDocFinancement'] = array(
    'label' => 'Envoyer document de location'
);

class ErpAPI_ExtEntity extends ErpAPI
{

    public function __construct($api_idx = 0, $id_user_account = 0, $debug_mode = false)
    {
        parent::__construct($api_idx, $id_user_account, $debug_mode);
    }

    public function setDemandeFinancementStatus($id_demande, $type_origine, $id_origine, $status, $note, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('setDemandeFinancementStatus', array(
            'fields' => array(
                'demande_target' => 'prolease',
                'id_demande'     => $id_demande,
                'status'         => $status,
                'type_origine'   => $type_origine,
                'id_origine'     => $id_origine,
                'note'           => $note
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

    public function newDemandeFinancementNote($id_demande, $type_origine, $id_origine, $note, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('newDemandeFinancementNote', array(
            'fields' => array(
                'demande_target' => 'prolease',
                'id_demande'     => $id_demande,
                'type_origine'   => $type_origine,
                'id_origine'     => $id_origine,
                'note'           => $note
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

    public function sendDocFinancement($id_demande, $type_origine, $id_origine, $doc_type, $doc_content, $signature_params, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('sendDocFinancement', array(
            'fields' => array(
                'demande_target'   => 'prolease',
                'id_demande'       => $id_demande,
                'type_origine'     => $type_origine,
                'id_origine'       => $id_origine,
                'doc_type'         => $doc_type,
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

<?php

// Entité: proplease

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/ErpAPI.php';

ErpApi::$requests['setDemandeFinancementStatus'] = array(
    'label' => 'Soumettre nouveau statut de la demande de location'
);
ErpApi::$requests['reopenDemandeFinancement'] = array(
    'label' => 'Réouvrir une demande de location'
);
ErpApi::$requests['newDemandeFinancementNote'] = array(
    'label' => 'Nouvelle note d\'une demande de location'
);
ErpApi::$requests['sendDocFinancement'] = array(
    'label' => 'Envoyer document de location'
);
ErpApi::$requests['setDocFinancementStatus'] = array(
    'label' => 'Définir le statut du document d\'une demande de location'
);
ErpApi::$requests['reviewDocFinancement'] = array(
    'label' => 'Mise en révision d\'un document de location'
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

    public function reopenDemandeFinancement($id_demande, $type_origine, $id_origine, $status, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('reopenDemandeFinancement', array(
            'fields' => array(
                'demande_target' => 'prolease',
                'id_demande'     => $id_demande,
                'status'         => $status,
                'type_origine'   => $type_origine,
                'id_origine'     => $id_origine
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

    public function sendDocFinancement($id_demande, $type_origine, $id_origine, $doc_type, $docs_content, $signature_params, $signataires_data, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('sendDocFinancement', array(
            'fields' => array(
                'demande_target'   => 'prolease',
                'id_demande'       => $id_demande,
                'type_origine'     => $type_origine,
                'id_origine'       => $id_origine,
                'doc_type'         => $doc_type,
                'docs_content'     => $docs_content,
                'signature_params' => $signature_params,
                'signataires_data' => $signataires_data
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

    public function setDocFinancementStatus($id_demande, $type_origine, $id_origine, $doc_type, $new_status, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('setDocFinancementStatus', array(
            'fields' => array(
                'demande_target' => 'prolease',
                'id_demande'     => $id_demande,
                'type_origine'   => $type_origine,
                'id_origine'     => $id_origine,
                'doc_type'       => $doc_type,
                'status'         => $new_status
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

    public function reviewDocFin($id_demande, $type_origine, $id_origine, $doc_type, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('reviewDocFinancement', array(
            'fields' => array(
                'demande_target' => 'prolease',
                'id_demande'     => $id_demande,
                'type_origine'   => $type_origine,
                'id_origine'     => $id_origine,
                'doc_type'       => $doc_type,
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

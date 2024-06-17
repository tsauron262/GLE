<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/ErpAPI.php';

ErpApi::$requests['addDemandeFinancement'] = array(
    'label' => 'Ajouter une demande de location'
);
ErpApi::$requests['editDemandeFinancementClientData'] = array(
    'label' => 'Mise à jour des données du client d\'une demande de location'
);
ErpApi::$requests['cancelDemandeFinancement'] = array(
    'label' => 'Annuler une demande de location'
);
ErpApi::$requests['getDemandeFinancementInfos'] = array(
    'label' => 'Obtenir les infos d\'une demande de location'
);
ErpApi::$requests['setDemandeFinancementDocSigned'] = array(
    'label' => 'Document d\'une demande de location signé'
);
ErpApi::$requests['setDemandeFinancementDocRefused'] = array(
    'label' => 'Document d\'une demande de location refusé'
);
ErpApi::$requests['addDemandeFinancementNote'] = array(
    'label' => 'Ajout d\'une note pour une demande de location'
);
ErpApi::$requests['getPropositionLocation'] = array(
    'label' => 'Obtenir une proposition de location'
);
ErpAPI::$requests['setDemandeFinancementSerialNumbers'] = array(
    'label' => 'Transmettre les numéros de série pour une demande de location'
);

class DemandeLocOutAPI extends ErpAPI
{

    public static $type_source = '';
    
    public function addDemandeFinancement($type_origine, $data = array(), &$errors = array(), &$warnings = array())
    {
        if (!$type_origine) {
            $errors[] = 'Type de pièce d\'origine non spécifiée';
        }

        if (!count($errors)) {
            $data['type_source'] = static::$type_source;
            $data['type_origine'] = $type_origine;
            $params = array(
                'fields' => array(
                    'data' => json_encode($data)
                )
            );

            if (!count($errors)) {
                $response = $this->execCurl('addDemandeFinancement', $params, $errors);

                if (isset($response['warnings'])) {
                    $warnings = BimpTools::merge_array($warnings, $response['warnings']);
                    unset($response['warnings']);
                }

                if (!count($errors)) {
                    return $response;
                }
            }
        }

        return null;
    }

    public function editDemandeFinancementClientData($id_df, $cient_data = array(), &$errors = array(), &$warnings = array())
    {
        $params = array(
            'fields' => array(
                'id_demande'  => $id_df,
                'client_data' => json_encode($cient_data)
            )
        );

        if (!count($errors)) {
            $response = $this->execCurl('editDemandeFinancementClientData', $params, $errors);

            if (isset($response['warnings'])) {
                $warnings = BimpTools::merge_array($warnings, $response['warnings']);
                unset($response['warnings']);
            }

            if (!count($errors)) {
                return $response;
            }
        }

        return null;
    }

    public function cancelDemandeFinancement($id_df, $note = '', &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('cancelDemandeFinancement', array(
            'fields' => array(
                'id_demande' => $id_df,
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

    public function getDemandeFinancementInfos($id_df, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('getDemandeFinancementInfos', array(
            'fields' => array(
                'id_demande' => $id_df
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

    public function setDemandeFinancementDocSigned($id_df, $doc_type, $doc_content, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('setDemandeFinancementDocSigned', array(
            'fields' => array(
                'id_demande'  => $id_df,
                'doc_type'    => $doc_type,
                'doc_content' => base64_encode($doc_content)
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

    public function setDemandeFinancementDocRefused($id_df, $doc_type, $note = '', &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('setDemandeFinancementDocRefused', array(
            'fields' => array(
                'id_demande' => $id_df,
                'doc_type'   => $doc_type,
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

    public function addDemandeFinancementNote($id_df, $note, &$errors = array(), &$warnings = array())
    {
        $user = BimpCore::getBimpUser();

        if (!BimpObject::objectLoaded($user)) {
            $errors[] = 'Aucun utilisateur connecté';
        } else {
            $response = $this->execCurl('addDemandeFinancementNote', array(
                'fields' => array(
                    'id_demande'   => $id_df,
                    'author_email' => $user->getData('email'),
                    'author_name'  => $user->getName(),
                    'note'         => $note
                )
                    ), $errors);
            if (isset($response['warnings'])) {
                $warnings = BimpTools::merge_array($warnings, $response['warnings']);
                unset($response['warnings']);
            }

            if (!count($errors)) {
                return $response;
            }
        }

        return null;
    }

    public function getPropositionLocation($data, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('getPropositionLocation', array(
            'fields' => $data
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

    public function setDemandeFinancementSerialNumbers($id_df, $serials, &$errors = array())
    {
        $response = $this->execCurl('setDemandeFinancementSerialNumbers', array(
            'fields' => array(
                'id_demande' => $id_df,
                'serials'    => json_encode($serials)
            )
                ), $errors);

        if (!count($errors)) {
            return $response;
        }

        return null;
    }
}

<?php

//Entity: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Propal.class.php';

class Bimp_Propal_ExtEntity extends Bimp_Propal
{

    public static $df_status_list = array(
        0  => '',
        1  => array('label' => 'En attente d\'acceptation', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        10 => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        20 => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        21 => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );

    // Droits users: 

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'createDemandeFinancement':
                if ($user->rights->bimpcommerical->demande_financement) {
                    return 1;
                }
                return 1;
        }
        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'close':
            case 'modify':
            case 'review':
            case 'reopen':
            case 'createOrder':
            case 'createInvoice':
            case 'classifyBilled':
            case 'createContrat':
                $df_status = (int) $this->getData('df_status');
                if ($df_status > 0 && $df_status < 10) {
                    $errors[] = 'Une demande de financement est en attente d\'acceptation';
                    return 0;
                }
                break;

            case 'createDemandeFinancement':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (!(int) BimpCore::getConf('allow_df_from_propal', null, 'bimpcommercial')) {
                    $errors[] = 'Demandes de financement désactivées';
                    return 0;
                }
                if ((int) $this->getData('df_status') > 0) {
                    $errors[] = 'Une demande de financement a déjà été faite';
                    return 0;
                }

                if ((int) $this->getData('fk_statut') !== 2) {
                    $errors[] = 'Ce devis n\'est pas au statut "signé / accepté"';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('createDemandeFinancement') && $this->canSetAction('createDemandeFinancement')) {
            $buttons[] = array(
                'label'   => 'Demande de financement',
                'icon'    => 'fas_hand-holding-usd',
                'onclick' => $this->getJsActionOnclick('createDemandeFinancement', array(), array(
                    'form_name' => 'demande_financement'
//                    'confirm_msg' => 'Veuillez confirmer la création d\\\'une demande de financement auprès de LDLC Pro Lease pour ce devis'
                ))
            );
        }

        $buttons = BimpTools::merge_array($buttons, parent::getActionsButtons());

        return $buttons;
    }

    // Getters Données: 

    public function getDefaultIdContactForDF()
    {
        foreach (array('CUSTOMER'/* , 'SHIPPING', 'BILLING2', 'BILLING' */) as $type_contact) {
            $contacts = $this->dol_object->getIdContact('external', $type_contact);
            if (isset($contacts[0]) && $contacts[0]) {
                return (int) $contacts[0];
            }
        }

        return 0;
    }

    // Rendus HTML

    public function renderHeaderStatusExtra()
    {
        $html = parent::renderHeaderStatusExtra();

        if ((int) $this->getData('df_status') > 0) {
            $html .= '<br/>Demande de financememt: ' . $this->displayData('df_status', 'default', false);
        }

        return $html;
    }

    // Actions: 

    public function actionCreateDemandeFinancement($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demande de financement effectuée avec succès';

        $api = null;
        $client = null;
        $contact = null;

        $id_contact = BimpTools::getArrayValueFromPath($data, 'id_contact', 0);
        if (!$id_contact) {
            $errors[] = 'Veuillez sélectionner un contact';
        } else {
            $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
            if (!BimpObject::objectLoaded($contact)) {
                $errors[] = 'Le contact #' . $id_contact . ' n\'existe pas';
            }
        }
        $id_api = (int) BimpCore::getConf('id_api_webservice_ldlc_pro_lease', null, 'bimpcommercial');

        if (!$id_api) {
            $errors[] = 'ID API non configuré';
        } else {
            $api = BimpCache::getBimpObjectInstance('bimpapi', 'API_Api', $id_api);
            if (!BimpObject::objectLoaded($api)) {
                $errors[] = 'ID de l\'API invalide';
            }
        }

        $client = $this->getChildObject('client');
        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Client absent ou invalide';
        }

        if (!count($errors)) {
            // Vérification de l'existence du client dans la table de synchro
            $sync = BimpCache::findBimpObjectInstance('bimpdatasync', 'BDS_SyncObject', array(
                        'obj_module' => 'bimpcore',
                        'obj_name'   => 'Bimp_Client',
                        'id_loc'     => $client->id
            ));
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}

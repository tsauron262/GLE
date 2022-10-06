<?php

//Entity: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Propal.class.php';

class Bimp_Propal_ExtEntity extends Bimp_Propal
{

    const DOC_STATUS_NONE = 0;
    const DOC_STATUS_ATTENTE = 1;
    const DOC_STATUS_SEND = 2;
    const DOC_STATUS_ACCEPTED = 10;
    const DOC_STATUS_REFUSED = 20;
    const DOC_STATUS_CANCELED = 21;

    public static $df_status_list = array(
        self::DOC_STATUS_NONE     => '',
        self::DOC_STATUS_ATTENTE  => array('label' => 'En attente d\'acceptation', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::DOC_STATUS_ACCEPTED => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        self::DOC_STATUS_REFUSED  => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::DOC_STATUS_CANCELED => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
    public static $doc_status_list = array(
        self::DOC_STATUS_NONE     => '',
        self::DOC_STATUS_ATTENTE  => array('label' => 'En attente d\'envoi', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::DOC_STATUS_SEND     => array('label' => 'En attente de signature', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::DOC_STATUS_ACCEPTED => array('label' => 'Signé', 'icon' => 'fas_check', 'classes' => array('success')),
        self::DOC_STATUS_REFUSED  => array('label' => 'Refusé', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::DOC_STATUS_CANCELED => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
    public static $signature_doc_types = array(
        'devis_fin'   => 'Devis de financement',
        'contrat_fin' => 'Contrat de financement'
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
                $df_status = (int) $this->getData('df_status');
                if ($df_status > 0 && $df_status < 10) {
                    $errors[] = 'Une demande de financement est en attente d\'acceptation';
                    return 0;
                }

                if ($df_status === self::DOC_STATUS_ACCEPTED) {
                    if ((int) $this->getData('devis_fin_status') < 20) {
                        $errors[] = 'Devis de financement non refusé ou annulé';
                        return 0;
                    }
                    if ((int) $this->getData('contrat_fin_status') < 20) {
                        $errors[] = 'Contrat de financement non refusé ou annulé';
                        return 0;
                    }
                }
                return 1;

            case 'createOrder':
            case 'createInvoice':
            case 'classifyBilled':
            case 'createContrat':
            case 'createSignature':
            case 'addAcompte':
                $df_status = (int) $this->getData('df_status');
                if ($df_status > 0 && $df_status < 10) {
                    $errors[] = 'Une demande de financement est en attente d\'acceptation';
                    return 0;
                }

                if ($df_status === self::DOC_STATUS_ACCEPTED) {
                    if ((int) $this->getData('devis_fin_status') !== self::DOC_STATUS_ACCEPTED) {
                        $errors[] = 'Devis de financement non signé';
                        return 0;
                    }
                    if ((int) $this->getData('contrat_fin_status') !== self::DOC_STATUS_ACCEPTED) {
                        $errors[] = 'Contrat de financement non signé';
                        return 0;
                    }

                    return 1;
                }

                $errors[] = 'Devis de financement non accepté par le client';
                return 0;

            case 'createDemandeFinancement':
            case 'generateDevisFinancement':
            case 'createDevisFinSignature':
            case 'createContratFinSignature':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if (!(int) BimpCore::getConf('allow_df_from_propal', null, 'bimpcommercial')) {
                    $errors[] = 'Demandes de financement désactivées';
                    return 0;
                }

                if (!in_array((int) $this->getData('fk_statut'), array(1, 2))) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé' . $this->e() . '" ou "accepté' . $this->e() . '"';
                    return 0;
                }

                switch ($action) {
                    case 'createDemandeFinancement':
                        if ((int) $this->getData('df_status') > 0) {
                            $errors[] = 'Une demande de financement a déjà été faite';
                            return 0;
                        }
                        return 1;

                    case 'generateDevisFinancement':
                        if ((int) $this->getData('df_status') !== self::DOC_STATUS_ACCEPTED) {
                            $errors[] = 'La demande de financement n\'est pas au statut "Acceptée"';
                            return 0;
                        }

                        $file = $this->getFilesDir() . $this->getDocFinancementFileName('devis_fin');
                        if (file_exists($file)) {
                            $errors[] = 'Le devis de financement a déjà été généré';
                            return 0;
                        }
                        return 1;

                    case 'createDevisFinSignature':
                        $file = $this->getFilesDir() . $this->getDocFinancementFileName('devis_fin');
                        if (!file_exists($file)) {
                            $errors[] = 'Le devis de financement n\'a pas été généré';
                            return 0;
                        }

                        if ((int) $this->getData('id_signature_devis_fin')) {
                            $errors[] = 'La fiche signature du devis de financement existe déjà';
                            return 0;
                        }
                        return 1;

                    case 'createContratFinSignature':
                        $file_name = $this->getDocFinancementFileName('contrat_fin');
                        if (!$file_name || !file_exists($this->getFilesDir() . $file_name)) {
                            $errors[] = 'Le contrat de financement n\'a pas été généré';
                            return 0;
                        }
                        if ((int) $this->getData('id_signature_contrat_fin')) {
                            $errors[] = 'La fiche signature du contrat de financement existe déjà';
                            return 0;
                        }
                        return 1;
                }
                break;
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
                'icon'    => 'fas_comment-dollar',
                'onclick' => $this->getJsActionOnclick('createDemandeFinancement', array(), array(
                    'form_name' => 'demande_financement'
                ))
            );
        }

        if ($this->isActionAllowed('generateDevisFinancement') && $this->canSetAction('generateDevisFinancement')) {
            $buttons[] = array(
                'label'   => 'Générer le devis de financement',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('generateDevisFinancement', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        if ($this->isActionAllowed('createDevisFinSignature') && $this->canSetAction('createDevisFinSignature')) {
            $buttons[] = array(
                'label'   => 'Envoyer le devis fin. pour signature',
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('createDevisFinSignature', array(), array(
                    'form_name' => 'signature_devis_fin'
                ))
            );
        }

        if ($this->isActionAllowed('createContratFinSignature') && $this->canSetAction('createContratFinSignature')) {
            $buttons[] = array(
                'label'   => 'Envoyer le contrat fin. pour signature',
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('createContratFinSignature', array(), array(
                    'form_name' => 'signature_contrat_fin'
                ))
            );
        }

        $parent_buttons = parent::getActionsButtons();

        if (!empty($parent_buttons)) {
            return array(
                'buttons_groups' => array(
                    array(
                        'label'   => 'Financement',
                        'icon'    => 'fas_hand-holding-usd',
                        'buttons' => $buttons
                    ),
                    array(
                        'label'   => 'Actions',
                        'icon'    => 'fas_cogs',
                        'buttons' => $parent_buttons
                    )
                )
            );
        }

        return $buttons;
    }

    public function getDefaultSignDistEmailContent($doc_type = 'devis')
    {
        if (!isset(self::$signature_doc_types[$doc_type])) {
            return parent::getDefaultSignDistEmailContent($doc_type);
        }

        // Todo : à modif si nécessaire
        return parent::getDefaultSignDistEmailContent($doc_type);
    }

    // Getters Données: 

    public function getDocFinancementFileName($doc_type, $signed = false)
    {
        if ((int) $this->getData('fk_statut') > 0) {
            switch ($doc_type) {
                case 'devis_fin':
                    return $this->getRef() . '-FIN' . ($signed ? '_signe' : '') . '.pdf';

                case 'contrat_fin':
                    $ref = $this->getData('ref_df_prolease');
                    if ($ref) {
                        return str_replace('DF', 'CTF', $ref) . ($signed ? '_signe' : '') . '.pdf';
                    }
                    break;
            }
        }

        return '';
    }

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

    public function getLdlcProLeaseAPI(&$errors = array(), $check_validity = true)
    {
        $id_api = (int) BimpCore::getConf('id_api_webservice_ldlc_pro_lease', null, 'bimpcommercial');

        if (!$id_api) {
            $errors[] = 'ID API non configuré';
        } else {
            BimpObject::loadClass('bimpapi', 'API_Api');
            return API_Api::getApiInstanceByID($id_api, $errors, $check_validity);
        }

        return null;
    }

    // Getters array: 

    public function getDFDurationsArray()
    {
        BimpObject::loadClass('bimpfinancement', 'BF_Demande');
        return BF_Demande::$durations;
    }

    public function getDFPeriodicitiesArray()
    {
        BimpObject::loadClass('bimpfinancement', 'BF_Demande');
        return BF_Demande::$periodicities;
    }

    public function getDFCalcModesArray()
    {
        BimpObject::loadClass('bimpfinancement', 'BF_Demande');
        return BF_Demande::$calc_modes;
    }

    // Rendus HTML

    public function renderHeaderStatusExtra()
    {
        $html = parent::renderHeaderStatusExtra();

        if ((int) $this->getData('df_status') > 0) {
            $html .= '<br/>Demande de financememt: ' . $this->displayData('df_status', 'default', false);
        }

        if ((int) $this->getData('devis_fin_status') > 0) {
            $html .= '<br/>Devis de financememt: ' . $this->displayData('devis_fin_status', 'default', false);
        }

        if ((int) $this->getData('contrat_fin_status') > 0) {
            $html .= '<br/>Contrat de financememt: ' . $this->displayData('contrat_fin_status', 'default', false);
        }

        return $html;
    }

    public function renderHeaderExtraLeft()
    {
        $html = parent::renderHeaderExtraLeft();

        foreach (array('devis_fin', 'contrat_fin') as $doc_type) {
            $signature = $this->getChildObject('signature_' . $doc_type);
            if (BimpObject::objectLoaded($signature)) {
                if ((int) $this->getData($doc_type . '_status') === self::DOC_STATUS_SEND && !$signature->getData('signed')) {
                    $html .= '<div style="margin-top: 10px">';
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= '<a href="' . $signature->getUrl() . '" target="_blank">';
                    $msg .= 'Signature du ' . str_replace('_fin', '', $doc_type) . ' de financement en attente';
                    $msg .= BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

                    $btn_html = $signature->renderSignButtonsGroup();
                    if ($btn_html) {
                        $msg .= '<div style="margin-top: 8px; text-align: right">';
                        $msg .= $btn_html;
                        $msg .= '</div>';
                    }

                    $html .= BimpRender::renderAlerts($msg, 'warning');
                    $html .= '</div>';
                } elseif ((int) $this->getData($doc_type . '_status') !== self::DOC_STATUS_ACCEPTED && $signature->getData('signed')) {
                    $html .= '<div style="margin-top: 10px">';
                    $msg = BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft');
                    $msg .= 'Le document signé "' . $this->getSignatureDocRef($doc_type) . '" semble ne pas avoir été envoyé à LDLC PRO LEASE';
                    $msg .= '<div style="text-align: center">';
                    $onclick = $this->getJsActionOnclick('onDocFinancementSigned', array(
                        'doc_type' => $doc_type
                    ));
                    $msg .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                    $msg .= BimpRender::renderIcon('fas_arrow-circle-right', 'iconLeft') . 'Envoyer le document';
                    $msg .= '</span>';
                    $msg .= '</div>';
                    $html .= BimpRender::renderAlerts($msg, 'danger');
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight()
    {
        $html = '<div class="buttonsContainer">';

        $dir = $this->getFilesDir();
        foreach (array('devis_fin', 'contrat_fin') as $doc_type) {
            foreach (array(1, 0) as $signed) {
                $file = $dir . $this->getSignatureDocFileName($doc_type, $signed);
                if (file_exists($file)) {
                    $url = $this->getSignatureDocFileUrl($doc_type, '', $signed);

                    if ($url) {
                        $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
                        $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . $this->getSignatureDocRef($doc_type) . ($signed ? ' (signé)' : '');
                        $html .= '</span>';
                    }
                    break;
                }
            }
        }

        $html .= parent::renderHeaderExtraRight(true);
        $html .= '</div>';

        return $html;
    }

    public function renderDemandeFinancementView()
    {
        $html = '';

        $html .= '<div class="buttonsContainer align-right" style="margin: 0">';
        $onclick = $this->getJsLoadCustomContent('renderDemandeFinancementView', '$(this).findParentByClass(\'nav_tab_ajax_result\')');
        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<div class="row">';
        $html .= '<div class="col-xs-12 col-sm-6">';

        $errors = array();
        $content = '';

        $id_df = (int) $this->getData('id_df_prolease');

        if ($id_df) {
            $api = $this->getLdlcProLeaseAPI($errors);

            if (!count($errors)) {
                $req_warnings = array();
                $req_errors = array();

                $data = $api->getDemandeFinancementInfos($id_df, $req_errors, $req_warnings);

                if (count($req_warnings)) {
                    $content .= BimpRender::renderAlerts($req_warnings, 'warning');
                }

                if (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'obtention des données de la demande de financement');
                } else {
                    if (isset($data['ref']) && $data['ref']) {
                        $content .= '<b>Référence :</b> ' . $data['ref'] . '<br/>';
                    }
                    if (isset($data['status']) && $data['status']) {
                        $content .= '<b>Statut actuel :</b> ' . $data['status_label'] . '<br/>';
                    }
                    if (isset($data['user_resp']['name']) && $data['user_resp']['name']) {
                        $content .= '<b>Pris en charge par : </b> ' . $data['user_resp']['name'];

                        if (isset($data['user_resp']['email']) && $data['user_resp']['email']) {
                            $content .= ' - <a href="mailto: ' . $data['user_resp']['email'] . '">' . $data['user_resp']['email'] . '</a>';
                        }

                        if (isset($data['user_resp']['phone']) && $data['user_resp']['phone']) {
                            $content .= ' - ' . BimpRender::renderIcon('fas_phone', 'iconLeft') . $data['user_resp']['phone'];
                        }
                        $content .= '<br/>';
                    } else {
                        $content .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non encore pris en charge</span><br/>';
                    }
                    $content .= '<br/>';

                    $content .= '<h4>Options: </h4>';
                    if (isset($data['duration'])) {
                        $content .= '<b>Durée totale :</b> ' . $data['duration'] . ' mois<br/>';
                    }
                    if (isset($data['periodicity_label']) && $data['periodicity_label']) {
                        $content .= '<b>Périodicité :</b> ' . $data['periodicity_label'] . ' mois<br/>';
                    }
                    if (isset($data['nb_loyers']) && $data['nb_loyers']) {
                        $content .= '<b>Nombre de loyers :</b> ' . $data['nb_loyers'] . '<br/>';
                    }
                    $content .= '<br/>';

                    if (isset($data['montants']) && !empty($data['montants'])) {
                        $content .= '<h4>Montants: </h4>';
                        if (isset($data['montants']['loyer_ht'])) {
                            $content .= '<b>Montant HT d\'un loyer :</b> ' . BimpTools::displayMoneyValue($data['montants']['loyer_ht'], 'EUR', false, false, false, 2, true) . '<br/>';
                        }
                        if (isset($data['montants']['total_loyers_ht'])) {
                            $content .= '<b>Total loyers HT : </b> ' . BimpTools::displayMoneyValue($data['montants']['total_loyers_ht'], 'EUR', false, false, false, 2, true) . '<br/>';
                        }
                        if (isset($data['montants']['total_loyers_tva'])) {
                            $content .= '<b>Total TVA loyers : </b> ' . BimpTools::displayMoneyValue($data['montants']['total_loyers_tva'], 'EUR', false, false, false, 2, true) . '<br/>';
                        }
                        if (isset($data['montants']['total_loyers_ttc'])) {
                            $content .= '<b>Total loyers TTC : </b> ' . BimpTools::displayMoneyValue($data['montants']['total_loyers_ttc'], 'EUR', false, false, false, 2, true) . '<br/>';
                        }
                        $content .= '<br/>';
                    }

                    if (isset($data['logs']) && !empty($data['logs'])) {
                        $content .= '<h4>' . BimpRender::renderIcon('fas_history', 'iconLeft') . 'Historique de la demande</h4>';

                        $content .= '<ul>';
                        foreach ($data['logs'] as $log) {
                            $content .= '<li>' . $log . '</li>';
                        }
                        $content .= '</ul>';
                    }
                }
            }
        } else {
            $errors[] = 'Aucune demande de financement liée à ' . $this->getLabel('this');
        }

        if (count($errors)) {
            $content .= BimpRender::renderAlerts($errors);
        }

        $html .= BimpRender::renderPanel(BimpRender::renderIcon('fas_comment-dollar', 'iconLeft') . 'Infos demande de financement LDLC Pro Lease', $content, '', array(
                    'type' => 'secondary'
        ));
        $html .= '</div>';
        $html .= '<div class="col-xs-12 col-sm-6">';

        $content = '';

        if ((int) $this->getData('id_signature_devis_fin') || (int) $this->getData('id_signature_contrat_fin')) {
            $fields_table = new BC_FieldsTable($this, 'signatures_fin');
            $content .= $fields_table->renderHtml();
        }

        if ($content) {
            $html .= BimpRender::renderPanel(BimpRender::renderIcon('fas_comment-dollar', 'iconLeft') . 'Infos demande de financement LDLC Pro Lease', $content, '', array(
                        'type' => 'secondary'
            ));
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // Traitements: 

    public function setDemandeFinancementStatus($status, $note = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!isset(self::$df_status_list[$status])) {
                $errors[] = 'Nouveau statut de la demande de financement invalide: ' . $status;
            } else {
                $errors = $this->updateField('df_status', $status);

                if (!count($errors)) {
                    $msg = 'Demande de financement ' . lcfirst(self::$df_status_list[$status]['label']);
                    if ($note) {
                        $msg .= '<br/><b>Note : </b>' . $note;
                    }

                    $this->addObjectLog($msg, 'NEW_DMD_FIN_STATUS_' . $status);

                    // Todo: mail commercial
                }
            }
        }

        return $errors;
    }

    public function onContratFinReceived($doc_content, $signature_params)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $dir = $this->getFilesDir();

            if (!is_dir($dir)) {
                $dir_err = BimpTools::makeDirectories($dir);
                if ($dir_err) {
                    $errors[] = 'Echec de la création du dossier de destination: ' . $dir_err;
                }
            }

            if (!count($errors)) {
                $file = $dir . $this->getSignatureDocFileName('contrat_fin');
                if (!file_put_contents($file, base64_decode($doc_content))) {
                    $errors[] = 'Echec de l\'enregistrement du fichier';
                } else {
                    if (!empty($signature_params)) {
                        $this->updateField('signature_cf_params', $signature_params);
                    }

                    $this->updateField('contrat_fin_status', self::DOC_STATUS_ATTENTE);
                    $this->addObjectLog('Contrat de financement reçu', 'CONTRAT_FIN_RECEIVED');
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionCreateDemandeFinancement($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demande de financement effectuée avec succès';
        $sc = 'bimp_reloadPage();';

        if ((int) $this->getData('id_signature')) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                if (!(int) $signature->getData('type')) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est en attente de signature.<br/>Vous devez attendre la signature du client (ou annuler la demande de signature) pour émettre une demande de financement';
                }
            }
        }

        $api = $this->getLdlcProLeaseAPI($errors);

        $id_contact = BimpTools::getArrayValueFromPath($data, 'id_contact', 0);
        if (!$id_contact) {
            $errors[] = 'Veuillez sélectionner un contact client';
        }

        if (!count($errors)) {
            $extra_data = array(
                'duration'    => BimpTools::getArrayValueFromPath($data, 'duration'),
                'periodicity' => BimpTools::getArrayValueFromPath($data, 'periodicity'),
                'mode_calcul' => BimpTools::getArrayValueFromPath($data, 'calc_mode')
            );

            $req_errors = array();
            $result = $api->addDemandeFinancement($this, (int) $this->getData('fk_soc'), $id_contact, $extra_data, $req_errors, $warnings);

            if (isset($result['id_demande']) && (int) $result['id_demande']) {
                $this->addObjectLog('Création de la demande de financement sur LDLC PRO LEASE effectuée avec succès');
                $this->set('id_df_prolease', (int) $result['id_demande']);
                $this->set('ref_df_prolease', $result['ref_demande']);
                $this->set('df_status', 1);
                $up_errors = $this->update($warnings, true);

                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Création de la demande de financement ok mais échec de la mise à jour du devis');
                }
            } elseif (count($req_errors)) {
                $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la création de la demande de financement sur LDLC PRO LEASE');
                $this->addObjectLog(BimpTools::getMsgFromArray($req_errors, 'Echec de la création de la demande de financement sur LDLC PRO LEASE'));
            } else {
                $errors[] = 'Echec de la requête (Aucune réponse reçue)';
                $this->addObjectLog('Echec de la création de la demande de financement sur LDLC PRO LEASE (Aucune réponse reçue suite à la requête)');
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionGenerateDevisFinancement($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Devis de financement généré avec succès';
        $sc = '';

        $id_df = (int) $this->getData('id_df_prolease');

        if ($id_df) {
            $api = $this->getLdlcProLeaseAPI($errors);

            if (!count($errors)) {
                $req_errors = array();

                $demande_data = $api->getDemandeFinancementInfos($id_df, $req_errors, $warnings);

                if (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'obtention des données de la demande de financement');
                } else {
                    global $db;
                    require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/pdf/DevisFinancementPDF.php';

                    $pdf = new DevisFinancementPDF($db, $this, $demande_data);

                    $file_name = $this->getDocFinancementFileName('devis_fin');
                    $file_path = $this->getFilesDir() . $file_name;

                    if (!$pdf->render($file_path, 'F')) {
                        $errors[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la création du fichier');
                    } else {
                        $this->addObjectLog('Devis de financement généré', 'DEVIS_FIN_GENERE');

                        $up_errors = $this->updateField('devis_fin_status', self::DOC_STATUS_ATTENTE);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut du devis de financement');
                        }

                        if (!count($errors) && file_exists($file_path)) {
                            $url = $this->getFileUrl($file_name);
                            if ($url) {
                                $sc = 'window.open(\'' . $url . '\')';
                            }
                        }
                    }
                }
            }
        } else {
            $errors[] = 'ID de la demande de financement LDLC Pro Lease absent';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionCreateDevisFinSignature($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_client = (int) $this->getData('fk_soc');

        if (!$id_client) {
            $errors[] = 'Client absent';
        }

        if ((int) $this->getData('id_signature_devis_fin')) {
            $errors[] = 'La fiche signature du devis de financement a déjà été créée pour ' . $this->getLabel('this');
        }

        $id_contact_signature = BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
        $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getDefaultSignDistEmailContent('devis_fin'));

        if (!count($errors)) {
            $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                        'obj_module' => 'bimpcommercial',
                        'obj_name'   => 'Bimp_Propal',
                        'id_obj'     => $this->id,
                        'doc_type'   => 'devis_fin',
                        'id_client'  => $id_client,
                        'id_contact' => $id_contact_signature
                            ), true, $errors, $warnings);

            if (!count($errors) && BimpObject::objectLoaded($signature)) {
                $success = 'Création de la fiche signature effectuée avec succès';
                $this->addObjectLog('Fiche signature  du devis de financement créée', 'SIGNATURE_DEVIS_FIN_CREEE');
                $up_errors = $this->updateField('id_signature_devis_fin', (int) $signature->id);

                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregstrement de l\'ID de la fiche signature');
                }

                $open_warnings = array();
                $open_errors = $signature->openSignDistAccess($email_content, true, array(), '', $open_warnings, $success);

                if (count($open_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                } else {
                    $this->updateField('devis_fin_status', self::DOC_STATUS_SEND);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateContratFinSignature($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_client = (int) $this->getData('fk_soc');

        if (!$id_client) {
            $errors[] = 'Client absent';
        }

        if ((int) $this->getData('id_signature_contrat_fin')) {
            $errors[] = 'La fiche signature du contrat de financement a déjà été créée pour ' . $this->getLabel('this');
        }

        $id_contact_signature = BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
        $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getDefaultSignDistEmailContent('devis_fin'));

        if (!count($errors)) {
            $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                        'obj_module' => 'bimpcommercial',
                        'obj_name'   => 'Bimp_Propal',
                        'id_obj'     => $this->id,
                        'doc_type'   => 'contrat_fin',
                        'id_client'  => $id_client,
                        'id_contact' => $id_contact_signature
                            ), true, $errors, $warnings);

            if (!count($errors) && BimpObject::objectLoaded($signature)) {
                $success = 'Création de la fiche signature effectuée avec succès';
                $this->addObjectLog('Fiche signature  du contrat de financement créée', 'SIGNATURE_DEVIS_FIN_CREEE');
                $up_errors = $this->updateField('id_signature_contrat_fin', (int) $signature->id);

                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregstrement de l\'ID de la fiche signature');
                }

                $open_warnings = array();
                $open_errors = $signature->openSignDistAccess($email_content, true, array(), '', $open_warnings, $success);

                if (count($open_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                } else {
                    $this->updateField('contrat_fin_status', self::DOC_STATUS_SEND);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionOnDocFinancementSigned($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $doc_type = BimpTools::getArrayValueFromPath($data, 'doc_type', '');

        if (!$doc_type) {
            $errors[] = 'Type de docuement non spécifié';
        } else {
            $doc_label = str_replace('_fin', '', $doc_type);

            $signature = $this->getChildObject('signature_' . $doc_type);
            if (!BimpObject::objectLoaded($signature)) {
                $errors[] = 'La fiche signature du ' . $doc_label . ' de financement n\'existe pas';
            } elseif (!$signature->getData('signed')) {
                $errors[] = 'Le' . $doc_label . ' de financement n\'a pas encore été signé par le client';
            } elseif ((int) $this->getData($doc_type . '_status') === self::DOC_STATUS_ACCEPTED) {
                $errors[] = 'Le' . $doc_label . ' de financement signé a déjà été envoyé à LDLC PRO LEASE avec succès';
            } else {
                $errors = $this->onSigned($signature, array());

                if (!count($errors)) {
                    $success = ucfirst($doc_label) . ' envoyé avec succès à LDLC PRO LEASE';
                }
            }
        }



        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function duplicate($new_data = [], &$warnings = [], $force_create = false)
    {
        $new_data['df_status'] = 0;
        $new_data['id_df_prolease'] = 0;
        $new_data['ref_df_prolease'] = '';

        $new_data['devis_fin_status'] = 0;
        $new_data['contrat_fin_status'] = 0;
        $new_data['id_signature_devis_fin'] = 0;
        $new_data['id_signature_contrat_fin'] = 0;
        $new_data['signature_df_params'] = '';
        $new_data['signature_cf_params'] = '';

        return parent::duplicate($new_data, $warnings, $force_create);
    }

    // Gestion Signature:

    public function getSignatureDocFileName($doc_type, $signed = false)
    {
        if (!isset(self::$signature_doc_types[$doc_type])) {
            return parent::getSignatureDocFileName($doc_type, $signed);
        }

        return $this->getDocFinancementFileName($doc_type, $signed);
    }

    public function getSignatureDocRef($doc_type)
    {
        if (!isset(self::$signature_doc_types[$doc_type])) {
            return parent::getSignatureDocRef($doc_type);
        }

        switch ($doc_type) {
            case 'devis_fin':
                return $this->getRef() . '-FIN';

            case 'contrat_fin':
                $ref = $this->getData('ref_df_prolease');
                if ($ref) {
                    return str_replace('DF', 'CTF', $ref);
                }
                break;
        }

        return '';
    }

    public function getSignatureParams($doc_type)
    {
        if (!isset(self::$signature_doc_types[$doc_type])) {
            return parent::getSignatureParams($doc_type);
        }

        switch ($doc_type) {
            case 'devis_fin':
                return $this->getData('signature_df_params');

            case 'contrat_fin':
                return $this->getData('signature_cf_params');
        }

        return array();
    }

    public function onSigned($bimpSignature, $data)
    {
        $doc_type = $bimpSignature->getData('doc_type');

        if (!isset(self::$signature_doc_types[$doc_type])) {
            return parent::onSigned($bimpSignature, $data);
        }

        $errors = array();

        if ($this->isLoaded($errors)) {
            $api = $this->getLdlcProLeaseAPI($errors);
            $id_df = (int) $this->getData('id_df_prolease');
            $file_content = '';

            if (!$id_df) {
                $errors[] = 'ID de la demande de financement absent';
            }

            $file_name = $this->getSignatureDocFileName($doc_type, true);
            if (!$file_name) {
                $errors[] = 'Nom du fichier signé absent';
            } else {
                $file = $this->getFilesDir() . $file_name;

                if (!file_exists($file)) {
                    $errors[] = 'Fichier signé non trouvé';
                } else {
                    $file_content = file_get_contents($file);
                    if (!$file_content) {
                        $errors[] = 'Echec de la récupération du contenu du fichier signé';
                    }
                }
            }

            if (!count($errors)) {
                $req_errors = array();
                $api->setDemandeFinancementDocSigned($id_df, str_replace('_fin', '', $doc_type), $file_content, $req_errors);

                if (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'envoi du fichier à LDLC PRO LEASE');
                } else {
                    $this->updateField($doc_type . '_status', self::DOC_STATUS_ACCEPTED);
                    $this->addObjectLog(self::$signature_doc_types[$doc_type] . ' signé par le client', 'DEVIS_FIN_SIGNE');
                }
            }
        }

        return $errors;
    }

    public function isSignatureCancellable()
    {
        if (!isset(self::$signature_doc_types[$doc_type])) {
            return parent::getSignatureDocFileName($doc_type, $signed);
        }
        return 0;
    }
}

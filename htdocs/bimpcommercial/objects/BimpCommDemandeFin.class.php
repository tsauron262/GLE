<?php

class BimpCommDemandeFin extends BimpObject
{

    const DOC_STATUS_NONE = 0;
    const DOC_STATUS_ATTENTE = 1;
    const DOC_STATUS_SEND = 2;
    const DOC_STATUS_ACCEPTED = 10;
    const DOC_STATUS_REFUSED = 20;
    const DOC_STATUS_CANCELED = 21;

    public static $status_list = array(
        self::DOC_STATUS_NONE     => '',
        self::DOC_STATUS_ATTENTE  => array('label' => 'En attente d\'acceptation', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::DOC_STATUS_ACCEPTED => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        self::DOC_STATUS_REFUSED  => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::DOC_STATUS_CANCELED => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
    public static $doc_status_list = array(
        self::DOC_STATUS_NONE     => '',
        self::DOC_STATUS_ATTENTE  => array('label' => 'En attente d\'envoi au client', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::DOC_STATUS_SEND     => array('label' => 'En attente de signature', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::DOC_STATUS_ACCEPTED => array('label' => 'Signé', 'icon' => 'fas_check', 'classes' => array('success')),
        self::DOC_STATUS_REFUSED  => array('label' => 'Refusé', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::DOC_STATUS_CANCELED => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
    public static $signature_doc_types = array(
        'devis_fin'   => 'Devis de location',
        'contrat_fin' => 'Contrat de location',
        'pvr_fin'     => 'PV de réception'
    );
    public static $doc_types = array(
        'devis_fin'   => 'Devis de location',
        'contrat_fin' => 'Contrat de location',
        'pvr_fin'     => 'PV de réception'
    );
    public static $targets = array();
    public static $targets_defaults = array();
    public static $def_target = '';
    public static $def_duration = 12;
    public static $def_periodicity = 1;
    public static $def_mode_calcul = 2;
    protected $df_data = null;

    // Droits users: 

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'createDemandeFinancement':
            case 'createDevisFinSignature':
            case 'createContratFinSignature':
            case 'createPvrFinSignature':
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
        if (!in_array($action, array('createDemandeFinancement')) && !$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'createDemandeFinancement':
                if ($this->isLoaded()) {
                    $errors[] = 'Création d\'une demande de location non possible depuis une instance existante';
                    return 0;
                }
                return 1;

            case 'cancelDemandeFinancement':
            case 'editClientData':
                if ((int) $this->getData('status') >= 20) {
                    $errors[] = 'Cette demande de location est déjà au statut annulée ou refusée';
                    return 0;
                }

                if ((int) $this->getData('contrat_fin_status') === self::DOC_STATUS_ACCEPTED) {
                    $errors[] = 'Le contrat de location a déjà été accepté et signé';
                    return 0;
                }
                return 1;

            case 'createDevisFinSignature':
                $file = $this->getFilesDir() . $this->getSignatureDocFileName('devis_fin');
                if (!file_exists($file)) {
                    $errors[] = 'Le devis de location n\'a pas été reçu';
                    return 0;
                }

                if ((int) $this->getData('id_signature_devis_fin')) {
                    $errors[] = 'La fiche signature du devis de location existe déjà';
                    return 0;
                }
                return 1;

            case 'createContratFinSignature':
                $file_name = $this->getSignatureDocFileName('contrat_fin');
                if (!$file_name || !file_exists($this->getFilesDir() . $file_name)) {
                    $errors[] = 'Le contrat de location n\'a pas été reçu';
                    return 0;
                }
                if ((int) $this->getData('id_signature_contrat_fin')) {
                    $errors[] = 'La fiche signature du contrat de location existe déjà';
                    return 0;
                }
                return 1;

            case 'createPvrFinSignature':
                $file_name = $this->getSignatureDocFileName('pvr_fin');
                if (!$file_name || !file_exists($this->getFilesDir() . $file_name)) {
                    $errors[] = 'Le PV de réception n\'a pas été reçu';
                    return 0;
                }
                if ((int) $this->getData('id_signature_pvr_fin')) {
                    $errors[] = 'La fiche signature du PV de réception existe déjà';
                    return 0;
                }
                return 1;

            case 'submitDevisFinRefused':
                if ((int) $this->getData('devis_fin_status') !== self::DOC_STATUS_SEND) {
                    $errors[] = 'Le devis de location n\'est pas en attente de signature par le client';
                    return 0;
                }
                return 1;

            case 'submitContratFinRefused':
                if ((int) $this->getData('contrat_fin_status') !== self::DOC_STATUS_SEND) {
                    $errors[] = 'Le contrat de location n\'est pas en attente de signature par le client';
                    return 0;
                }
                return 1;

            case 'submitPvrFinRefused':
                if ((int) $this->getData('pvr_fin_status') !== self::DOC_STATUS_SEND) {
                    $errors[] = 'Le PV de réception n\'est pas en attente de signature par le client';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isTargetOk(&$errors = array())
    {
        $target = $this->getData('target');

        if (!$target) {
            $errors[] = 'Destinataire de la demande de location non spécifié';
            return 0;
        } elseif (!isset(static::$targets[$target])) {
            $errors[] = 'Destinataire de la demande de location invalide';
            return 0;
        }

        return 1;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('sendNote') && $this->canSetAction('sendNote')) {
            $buttons[] = array(
                'label'   => 'Envoyer une note à ' . $this->displayTarget(),
                'icon'    => 'fas_paper-plane',
                'onclick' => $this->getJsActionOnclick('sendNote', array(), array(
                    'form_name' => 'note'
                ))
            );
        }

        if ($this->isActionAllowed('cancelDemandeFinancement') && $this->canSetAction('cancelDemandeFinancement')) {
            $buttons[] = array(
                'label'   => 'Annuler la demande de location',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('cancelDemandeFinancement', array(), array(
                    'form_name' => 'cancel'
                ))
            );
        }

        if ($this->isActionAllowed('createDevisFinSignature') && $this->canSetAction('createDevisFinSignature')) {
            $buttons[] = array(
                'label'   => 'Envoyer le devis de location pour signature',
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('createDevisFinSignature', array(), array(
                    'form_name' => 'signature_devis_fin'
                ))
            );
        }

        if ($this->isActionAllowed('createContratFinSignature') && $this->canSetAction('createContratFinSignature')) {
            $buttons[] = array(
                'label'   => 'Envoyer le contrat de location pour signature',
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('createContratFinSignature', array(), array(
                    'form_name' => 'signature_contrat_fin'
                ))
            );
        }

        if ($this->isActionAllowed('createPvrFinSignature') && $this->canSetAction('createPvrFinSignature')) {
            $buttons[] = array(
                'label'   => 'Envoyer le PV de réception pour signature',
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsActionOnclick('createPvrFinSignature', array(), array(
                    'form_name' => 'signature_pvr_fin'
                ))
            );
        }

        if ($this->isActionAllowed('submitDevisFinRefused') && $this->canSetAction('submitDevisFinRefused')) {
            $buttons[] = array(
                'label'   => 'Devis de location refusé',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('submitDevisFinRefused', array(), array(
                    'form_name' => 'refuse'
                ))
            );
        }

        if ($this->isActionAllowed('submitContratFinRefused') && $this->canSetAction('submitContratFinRefused')) {
            $buttons[] = array(
                'label'   => 'Contrat de location refusé',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('submitContratFinRefused', array(), array(
                    'form_name' => 'refuse'
                ))
            );
        }

        if ($this->isActionAllowed('submitPvrFinRefused') && $this->canSetAction('submitPvrFinRefused')) {
            $buttons[] = array(
                'label'   => 'PV de récpeption refusé',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('submitPvrFinRefused', array(), array(
                    'form_name' => 'refuse'
                ))
            );
        }

        return $buttons;
    }

    public function getFilesDir()
    {
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent) && method_exists($parent, 'getFilesDir')) {
            return $parent->getFilesDir();
        }

        return '';
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent) && method_exists($parent, 'getFileUrl')) {
            return $parent->getFileUrl($file_name, $page);
        }

        return '';
    }

    public function getExternalApi(&$errors = array(), $check_validity = true)
    {
        if ($this->isTargetOk($errors)) {
            $id_api = (int) BimpCore::getConf('id_api_webservice_' . $this->getData('target'), 0, 'bimpcommercial');

            if (!$id_api) {
                $errors[] = 'ID API non configuré pour ' . $this->displayTarget();
            } else {
                BimpObject::loadClass('bimpapi', 'API_Api');
                return API_Api::getApiInstanceByID($id_api, $errors, $check_validity);
            }
        }

        return null;
    }

    public function getSignatureEmailContent($signature_type = '')
    {
        if (!$signature_type) {
            if (BimpTools::isPostFieldSubmit('init_docusign')) {
                if ((int) BimpTools::getPostFieldValue('init_docusign')) {
                    $signature_type = 'docusign';
                }
            }
            if (!$signature_type) {
                if (BimpTools::isPostFieldSubmit('open_public_access')) {
                    if ((int) BimpTools::getPostFieldValue('open_public_access')) {
                        $signature_type = 'elec';
                    }
                }
            }
        }

        if ($signature_type) {
            BimpObject::loadClass('bimpcore', 'BimpSignature');
            return BimpSignature::getDefaultSignDistEmailContent($signature_type);
        }

        return '';
    }

    public function getTargetIdClient()
    {
        $target = $this->getData('target');

        $id_client = (int) BimpCore::getConf('demande_fin_target_' . $target . '_id_client', 0, 'bimpcommercial');
        if (!$id_client) {
            BimpCore::addlog('ID Client non configuré pour le destinataire des demandes de location "' . $this->displayTarget() . '"', 4, 'bimpcomm', $this, array(
                'Info' => 'Ajouter conf "demande_fin_target_' . $target . '_id_client" dans module bimpcommercial'
                    ), true);
        }

        return $id_client;
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

    public function getContactsArray($include_empty = true, $empty_label = '', $active_only = true)
    {
        $origine = null;
        if (!$this->isLoaded()) {
            $type_origine = BimpTools::getPostFieldValue('type_origine', '');
            $id_origine = (int) BimpTools::getPostFieldValue('id_origine', 0);

            if ($type_origine && $id_origine) {
                $origine = $this->getOrigineFromType($type_origine, $id_origine);
            }
        } else {
            $origine = $this->getParentInstance();
        }

        if (BimpObject::objectLoaded($origine)) {
            $id_client = (int) $origine->getData('fk_soc');

            if ($id_client) {
                return BimpCache::getSocieteContactsArray($id_client, $include_empty, $empty_label, $active_only);
            }
        }

        return array();
    }

    // Getters données: 

    public function getInputValue($input_name)
    {
        if ($this->field_exists($input_name) && $this->isLoaded()) {
            return $this->getData($input_name);
        }

        switch ($input_name) {
            case 'target':
                return static::$def_target;

            case 'id_contact_suivi':
                return $this->getDefaultIdContact('suivi');

            case 'id_contact_signature':
                return $this->getDefaultIdContact('signature');

            case 'fonction_signataire':
                $id_contact = (int) BimpTools::getPostFieldValue('id_contact_signature', 0);
                if ($id_contact) {
                    return $this->db->getValue('socpeople', 'poste', 'rowid = ' . (int) $id_contact);
                }
                return '';

            case 'contacts_livraisons':
                return $this->getDefaultIdContact('livraison', true);

            case 'duration':
            case 'periodicity':
            case 'mode_calcul':
                $target = BimpTools::getPostFieldValue('target', '');
                if (!$target) {
                    $target = static::$def_target;
                }
                if (isset(static::$targets_defaults[$target][$input_name])) {
                    return static::$targets_defaults[$target][$input_name];
                }
                return static::${'def_' . $input_name};
        }

        return null;
    }

    public function getDefaultIdContact($type = 'suivi', $all = false)
    {
        $origine = null;

        if (!$this->isLoaded()) {
            $type_origine = BimpTools::getPostFieldValue('type_origine', '');
            $id_origine = (int) BimpTools::getPostFieldValue('id_origine', 0);

            if ($type_origine && $id_origine) {
                $origine = $this->getOrigineFromType($type_origine, $id_origine);
            }
        } else {
            $origine = $this->getParentInstance();
        }

        if (BimpObject::objectLoaded($origine)) {
            return $origine->getDefaultIdContactForDF($type, $all);
        }

        if ($all) {
            return array();
        }

        return 0;
    }

    // Getters statics: 

    public static function getDocTypeLabel($doc_type)
    {
        if (isset(static::$doc_types[$doc_type])) {
            return static::$doc_types[$doc_type];
        }

        return $doc_type;
    }

    public static function getOrigineFromType($type_origine, $id_origine)
    {
        switch ($type_origine) {
            case 'propale':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_origine);

            case 'commande':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_origine);
        }

        return null;
    }

    // Affichages: 

    public function displayTarget()
    {
        $target = $this->getData('target');
        if ($target && isset(static::$targets[$target])) {
            return static::$targets[$target];
        }

        return '';
    }

    public function displayStatus()
    {
        $html = '';

        if ((int) $this->getData('status') > 0) {
            $html .= '<br/>Demande de location: ' . $this->displayData('status', 'default', false);
        }

        if ((int) $this->getData('devis_fin_status') > 0) {
            $html .= '<br/>Devis de location: ' . $this->displayData('devis_fin_status', 'default', false);
        }

        if ((int) $this->getData('contrat_fin_status') > 0) {
            $html .= '<br/>Contrat de location: ' . $this->displayData('contrat_fin_status', 'default', false);
        }

        if ((int) $this->getData('pvr_fin_status') > 0) {
            $html .= '<br/>PV de réception: ' . $this->displayData('pvr_fin_status', 'default', false);
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderSignaturesAlertes()
    {
        $html = '';

        foreach (array('devis_fin', 'contrat_fin', 'pvr_fin') as $doc_type) {
            if (!(int) $this->getData('id_signature_' . $doc_type)) {
                continue;
            }

            $signature = $this->getChildObject('signature_' . $doc_type);
            if (BimpObject::objectLoaded($signature)) {
                if (in_array((int) $this->getData($doc_type . '_status'), array(self::DOC_STATUS_ATTENTE, self::DOC_STATUS_SEND))) {
                    if (!$signature->isSigned()) {
                        $html .= '<div style="margin-top: 10px">';
                        $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                        $msg .= '<a href="' . $signature->getUrl() . '" target="_blank">';
                        $msg .= 'Signature du ' . str_replace('_fin', '', $doc_type) . ' de location en attente';
                        $msg .= BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

                        $btn_html = $signature->renderSignButtonsGroup();
                        if ($btn_html) {
                            $msg .= '<div style="margin-top: 8px; text-align: right">';
                            $msg .= $btn_html;
                            $msg .= '</div>';
                        }

                        $html .= BimpRender::renderAlerts($msg, 'warning');
                        $html .= '</div>';
                    } elseif ((int) $this->getData($doc_type . '_status') !== self::DOC_STATUS_ACCEPTED) {
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
        }

        return $html;
    }

    public function renderDocsButtons()
    {
        $html = '';

        $dir = $this->getFilesDir();
        foreach (array('devis_fin', 'contrat_fin', 'pvr_fin') as $doc_type) {
            foreach (array(1, 0) as $signed) {
                $file = $dir . $this->getSignatureDocFileName($doc_type, $signed);
                if (file_exists($file)) {
                    $url = $this->getSignatureDocFileUrl($doc_type, '', $signed);

                    if ($url) {
                        $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
                        $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . $this->getDocTypeLabel($doc_type) . ($signed ? ' (signé)' : '');
                        $html .= '</span>';
                    }
                    break;
                }
            }
        }

        return $html;
    }

    public function renderDemandeInfos()
    {
        $html = '';
        $errors = array();

        if (!count($errors)) {
            $req_warnings = array();
            $req_errors = array();

            $data = $this->fetchDemandeFinData(false, $req_errors, $req_warnings);

            if (count($req_warnings)) {
                $content .= BimpRender::renderAlerts($req_warnings, 'warning');
            }

            if (count($req_errors)) {
                $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'obtention des données de la demande de location');
            } else {
                if (isset($data['missing_serials']['total']) && (int) $data['missing_serials']['total'] > 0) {
                    $commande = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Commande', array(
                                'id_demande_fin' => $this->id
                    ));

                    if (BimpObject::objectLoaded($commande) && (int) $commande->getData('shipment_status') === 2) {
                        if ((int) $data['missing_serials']['total'] > 1) {
                            $msg = $data['missing_serials']['total'] . ' numéros de série sont manquants sur ' . $this->displayTarget() . '<br/>';
                        } else {
                            $msg = $data['missing_serials']['total'] . ' numéro de série est manquant sur ' . $this->displayTarget() . '<br/>';
                        }

                        $onclick = $commande->getJsActionOnclick('setDemandeFinSerials');
                        $msg .= '<div style="text-align: right">';
                        $msg .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $msg .= 'Transmettre les n° de série' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                        $msg .= '</span>';
                        $msg .= '</div>';
                        $content .= BimpRender::renderAlerts($msg, 'warning');
                    }
                }

                if (isset($data['source_warning']) && $data['source_warning']) {
                    $content .= BimpRender::renderAlerts($data['source_warning'], 'warning');
                }

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
                    $content .= '<b>Périodicité des loyers :</b> ' . $data['periodicity_label'] . '<br/>';
                }
                if (isset($data['nb_loyers']) && $data['nb_loyers']) {
                    $content .= '<b>Nombre de loyers :</b> ' . $data['nb_loyers'] . '<br/>';
                }
                $content .= '<br/>';

                if (isset($data['montants']) && !empty($data['montants'])) {
                    $periodicity = (int) BimpTools::getArrayValueFromPath($data, 'periodicity', 1);
                    require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/BF_Lib.php';
                    $content .= '<h4>Loyers proposés: </h4>';
                    $content .= '<table class="bimp_list_table" style="text-align: center">';
                    $content .= '<thead>';
                    $content .= '<tr>';
                    $content .= '<td></td>';
                    $content .= '<th style="text-align: center">Loyer mensuel HT</th>';
                    if ($periodicity > 1) {
                        $content .= '<th style="text-align: center">Loyer ' . BFTools::$periodicities_masc[$periodicity] . ' HT</th>';
                    }
                    $content .= '</tr>';
                    $content .= '</thead>';
                    $content .= '<tbody class="headers_col">';

                    if (isset($data['montants']['loyer_mensuel_evo_ht'])) {
                        $content .= '<tr>';
                        $content .= '<th>Formule évolutive</th>';
                        $content .= '<td><b>' . BimpTools::displayMoneyValue($data['montants']['loyer_mensuel_evo_ht']) . '*</b></td>';
                        if ($periodicity > 1) {
                            $content .= '<td><b>' . BimpTools::displayMoneyValue($data['montants']['loyer_mensuel_evo_ht'] * $periodicity) . '*</b></td>';
                        }
                        $content .= '</tr>';
                    }

                    if (isset($data['montants']['loyer_mensuel_dyn_ht'])) {
                        $content .= '<tr>';
                        $content .= '<th>Formule dynamique</th>';
                        $content .= '<td><b>' . BimpTools::displayMoneyValue($data['montants']['loyer_mensuel_dyn_ht']) . '*</b>';
                        if (isset($data['montants']['loyer_mensuel_suppl_ht'])) {
                            $content .= '<br/>Puis : <b>' . BimpTools::displayMoneyValue($data['montants']['loyer_mensuel_suppl_ht']) . '*</b>';
                        }
                        $content .= '</td>';
                        if ($periodicity > 1) {
                            $content .= '<td><b>' . BimpTools::displayMoneyValue($data['montants']['loyer_mensuel_dyn_ht'] * $periodicity) . '*</b>';
                            if (isset($data['montants']['loyer_mensuel_suppl_ht'])) {
                                $content .= '<br/>Puis ' . (12 / $periodicity) . ' x <b>' . BimpTools::displayMoneyValue($data['montants']['loyer_mensuel_suppl_ht'] * $periodicity) . '*</b>';
                            }
                            $content .= '</td>';
                        }
                        $content .= '</tr>';
                    }

                    $content .= '</tbody>';
                    $content .= '</table>';
                    $content .= '<p style="font-style: italic">* Loyers bruts en € HT, hors assurance.</p>';
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

                if (isset($data['notes']) && !empty($data['notes'])) {
                    $content .= '<br/><h4>' . BimpRender::renderIcon('fas_sticky-note', 'iconLeft') . 'Notes synchronisées</h4>';

                    if ($this->isActionAllowed('sendNote') && $this->canSetAction('sendNote')) {
                        $content .= '<div class="buttonsContainer align-right">';
                        $onclick = $this->getJsActionOnclick('sendNote', array(), array(
                            'form_name' => 'note'
                        ));
                        $content .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $content .= BimpRender::renderIcon('fas_paper-plane', 'iconLeft') . 'Nouveau message à ' . $this->displayTarget();
                        $content .= '</span>';
                        $content .= '</div>';
                    }

                    foreach ($data['notes'] as $note) {
                        $content .= '<div style="margin: 12px 0;">';
                        $content .= '<b>Le ' . $note['date'] . ' par ' . $note['author'] . ' : </b><br/>';
                        $content .= '<div class="note_content">';
                        $content .= $note['content'];
                        $content .= '</div>';
                        $content .= '</div>';
                    }
                }
                $html .= BimpRender::renderPanel(BimpRender::renderIcon('fas_comment-dollar', 'iconLeft') . 'Infos demande de location ' . $this->displayTarget(), $content, '', array(
                            'type' => 'secondary'
                ));
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    public function renderDocFinSignatairesInfos($doc_type)
    {
        $html = '';
        $data = array();

        switch ($doc_type) {
            case 'contrat_fin':
                $data = $this->getData('signataires_cf_data');
                break;

            case 'pvr_fin':
                $data = $this->getData('signataires_pvr_data');
                break;

            default:
                return BimpRender::renderAlerts('Type de document invalide');
        }


        $contact = $this->getChildObject('contact_signature');

        $html .= '<h4>Locataire</h4>';
        $html .= '<div style="padding-left: 15px">';
        if (BimpObject::objectLoaded($contact)) {
            $locataire_nom = BimpTools::getArrayValueFromPath($data, 'locataire/nom', '<span class="danger">' . $contact->getName() . '</span>');
            $locataire_fonction = BimpTools::getArrayValueFromPath($data, 'locataire/fonction', $contact->getData('poste'));
            $locataire_email = $contact->getData('email');

            if (!$locataire_nom) {
                $locataire_nom = '<span class="danger">Non spécifié</span>';
            }

            if (!$locataire_fonction) {
                $locataire_fonction = '<span class="danger">Non spécifié</span>';
            }

            if (!$locataire_email) {
                $locataire_email = '<span class="danger">Non spécifié</span>';
            }

            $html .= '<b>Nom : </b>' . $locataire_nom . '<br/>';
            $html .= '<b>Adresse e-mail : </b>' . $locataire_email . '<br/>';
            $html .= '<b>Fonction : </b>' . $locataire_fonction . '<br/>';
        } else {
            $html .= BimpRender::renderAlerts('Contact Signataire non sélectionné');
        }
        $html .= '</div>';

        if (isset($data['loueur'])) {
            $html .= '<h4>Loueur</h4>';
            $html .= '<div style="padding-left: 15px">';
            $html .= '<b>Nom : </b>' . BimpTools::getArrayValueFromPath($data, 'loueur/nom', '<span class="danger">Non spécifié</span>') . '<br/>';
            $html .= '<b>Adresse e-mail : </b>' . BimpTools::getArrayValueFromPath($data, 'loueur/email', '<span class="danger">Non spécifié</span>') . '<br/>';
            $html .= '<b>Fonction : </b>' . BimpTools::getArrayValueFromPath($data, 'loueur/fonction', '<span class="danger">Non spécifié</span>') . '<br/>';
            $html .= '</div>';
        }

        if (isset($data['cessionnaire'])) {
            $societe_cessionnaire = BimpTools::getArrayValueFromPath($data, 'cessionnaire/raison_social', '');
            $nom_cessionnaire = BimpTools::getArrayValueFromPath($data, 'cessionnaire/nom', '');
            $fonction_cessionnaire = BimpTools::getArrayValueFromPath($data, 'cessionnaire/fonction', '');
            
            if (!$societe_cessionnaire) {
                $societe_cessionnaire = '<span class="danger">Non spécifiée</span>';
            }
            if (!$nom_cessionnaire) {
                $nom_cessionnaire = '<span class="danger">A saisir par le cessionnaire</span>';
            }
            if (!$fonction_cessionnaire) {
                $fonction_cessionnaire = '<span class="danger">A saisir par le cessionnaire</span>';
            }
            
            $html .= '<h4>Cessionnaire</h4>';
            $html .= '<div style="padding-left: 15px">';
            $html .= '<b>Raison sociale : </b>' . $societe_cessionnaire .'<br/>';
            $html .= '<b>Nom : </b>' . $nom_cessionnaire . '<br/>';
            $html .= '<b>Adresse e-mail : </b>' . BimpTools::getArrayValueFromPath($data, 'cessionnaire/email', '<span class="danger">Non spécifié</span>') . '<br/>';
            $html .= '<b>Fonction : </b>' . $fonction_cessionnaire . '<br/>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderDocFinSignatureInitDocuSignInput($doc_type)
    {
        if (!array_key_exists($doc_type, self::$signature_doc_types)) {
            return BimpRender::renderAlerts('Type de document invalide');
        }

        $html = '';

        if (!(int) BimpCore::getConf($doc_type . '_signature_allow_docusign', null, 'bimpcommercial')) {
            $html .= '<div class="danger">Signature via DocuSign non autorisée pour ce ' . self::$signature_doc_types[$doc_type] . '</div>';
            $html .= '<input type="hidden" value="0" name="init_docusign"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'init_docusign', 1);
        }

        return $html;
    }

    public function renderDocFinSignatureOpenDistAccessInput($doc_type)
    {
        $html = '';

        if (!(int) BimpCore::getConf($doc_type . '_signature_allow_dist', null, 'bimpcommercial')) {
            $html .= '<div class="danger">Signature éléctronique à distance non autorisée pour ce ' . self::$signature_doc_types[$doc_type] . '</div>';
            $html .= '<input type="hidden" value="0" name="open_public_access"/>';
        } else {
            $html .= BimpInput::renderInput('toggle', 'open_public_access', 1);
        }

        return $html;
    }

    // Traitements: 

    public function fetchDemandeFinData($force_request = false, &$errors = array(), &$warnings = array())
    {
        if ($force_request) {
            $this->df_data = null;
        }

        if (is_null($this->df_data)) {
            $this->df_data = array();

            $api = $this->getExternalApi($errors);

            if (!count($errors)) {
                $this->df_data = $api->getDemandeFinancementInfos((int) $this->getData('id_ext_df'), $errors, $warnings);

                $serials_ok = (int) (!isset($this->df_data['missing_serials']['total']) || !(int) $this->df_data['missing_serials']['total']);

                if ($serials_ok !== (int) $this->getData('serials_ok')) {
                    $this->updateField('serials_ok', $serials_ok);
                }
            }
        }

        return $this->df_data;
    }

    public function onDocFinReceived($doc_type, $doc_content, $signature_params, $signataires_data = array())
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
                $file = $dir . $this->getSignatureDocFileName($doc_type);
                if (!$file) {
                    $errors[] = 'Type de document invalide: ' . $doc_type;
                } else {
                    if (!file_put_contents($file, base64_decode($doc_content))) {
                        $errors[] = 'Echec de l\'enregistrement du fichier';
                    } else {
                        if (!empty($signature_params)) {
                            switch ($doc_type) {
                                case 'devis_fin':
                                    $this->updateField('status', static::DOC_STATUS_ACCEPTED);
                                    $this->updateField('signature_df_params', $signature_params);
                                    break;

                                case 'contrat_fin':
                                    $this->updateField('signature_cf_params', $signature_params);
                                    $this->updateField('signataires_cf_data', $signataires_data);
                                    break;

                                case 'pvr_fin':
                                    $this->updateField('signature_pvr_params', $signature_params);
                                    $this->updateField('signataires_pvr_data', $signataires_data);
                                    break;
                            }
                        }

                        $this->updateField($doc_type . '_status', self::DOC_STATUS_ATTENTE);

                        $parent = $this->getParentInstance();
                        if (BimpObject::objectLoaded($parent)) {
                            $parent->addObjectLog(static::getDocTypeLabel($doc_type) . ' reçu', strtoupper($doc_type) . '_RECEIVED');
                            $this->addParentNoteForCommercial('Document reçu : ' . $this->getDocTypeLabel($doc_type));
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function setDocFinStatus($doc_type, $new_status)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!array_key_exists($new_status, self::$doc_status_list)) {
                $errors[] = 'Nouveau statut invalide: ' . $new_status;
            } else {
                $errors = $this->updateField($doc_type . '_status', $new_status);

                if ($new_status >= 10 && $new_status < 20) {
                    $this->updateField('status', self::DOC_STATUS_ACCEPTED);
                }

                if (!count($errors)) {
                    $parent = $this->getParentInstance();
                    if (BimpObject::objectLoaded($parent)) {
                        $parent->addObjectLog(static::getDocTypeLabel($doc_type) . ' mis au statut : ' . self::$doc_status_list[$new_status]['label'], strtoupper($doc_type) . '_STATUS_' . $new_status);

                        if (is_a($parent, 'Bimp_Propal') && $new_status == self::DOC_STATUS_ACCEPTED) {
                            if ((int) $parent->getData('fk_statut') === 1) {
                                $parent->updateField('fk_statut', 2);
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function submitDocFinRefused($doc_type, $note = '')
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->isTargetOk($errors)) {
            $api = $this->getExternalApi($errors);
            $id_df = (int) $this->getData('id_ext_df');

            if (!$id_df) {
                $errors[] = 'ID de la demande de location ' . $this->displayTarget() . ' absent';
            }

            if (!count($errors)) {
                $req_errors = array();
                $api->setDemandeFinancementDocRefused($id_df, str_replace('_fin', '', $doc_type), $note, $req_errors);

                if (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'envoi du fichier à ' . $this->displayTarget());
                } else {
                    $this->updateField($doc_type . '_status', self::DOC_STATUS_REFUSED);

                    $parent = $this->getParentInstance();

                    if (BimpObject::objectLoaded($parent)) {
                        $msg = ucfirst($this->getDocTypeLabel($doc_type)) . ' annulé' . ($note ? '.<br/><b>Raisons : </b>' . $note : '');
                        $parent->addObjectLog($msg, strtoupper($doc_type) . '_REFUSED');
                    }

                    if ((int) $this->getData('id_signature_' . $doc_type)) {
                        $signature = $this->getChildObject('signature_' . $doc_type);
                        if (BimpObject::objectLoaded($signature)) {
                            $signature->cancelAllSignatures();
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function setNewStatusFromTarget($new_status, $note = '')
    {
        $errors = array();

        if (!array_key_exists($new_status, static::$status_list)) {
            $errors[] = 'Nouveau statut invalide: ' . $new_status;
        } else {
            $up_errors = $this->updateField('status', $new_status);

            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du nouveau statut');
            } else {
                $parent = $this->getParentInstance();
                if (BimpObject::objectLoaded($parent)) {
                    $msg = 'Demande de location mise au statut "' . static::$status_list[$new_status]['label'] . '"';
                    if ($note) {
                        $msg .= '.<br/><b>Note : </b>' . $note;
                    }
                    $parent->addObjectLog($msg, 'DF_NEW_STATUS_' . $new_status);
                    $this->addParentNoteForCommercial($msg);
                }

                if (in_array($new_status, array(static::DOC_STATUS_CANCELED, static::DOC_STATUS_REFUSED))) {
                    $devis_status = (int) $this->getData('devis_fin_status');
                    if ($devis_status > 0 && $devis_status < 10) {
                        $this->updateField('devis_fin_status', $new_status);

                        if ((int) $this->getData('id_signature_devis_fin')) {
                            $signature = $this->getChildObject('signature_devis_fin');
                            if (BimpObject::objectLoaded($signature)) {
                                if (!(int) $signature->isSigned()) {
                                    $signature->cancelAllSignatures();
                                }
                            }
                        }
                    }

                    $contrat_status = (int) $this->getData('contrat_fin_status');
                    if ($contrat_status > 0 && $contrat_status < 10) {
                        $this->updateField('contrat_fin_status', $new_status);

                        if ((int) $this->getData('id_signature_contrat_fin')) {
                            $signature = $this->getChildObject('signature_contrat_fin');
                            if (BimpObject::objectLoaded($signature)) {
                                if (!(int) $signature->isSigned()) {
                                    $signature->cancelAllSignatures();
                                }
                            }
                        }
                    }

                    $pvr_status = (int) $this->getData('pvr_fin_status');
                    if ($pvr_status > 0 && $pvr_status < 10) {
                        $this->updateField('pvr_fin_status', $new_status);

                        if ((int) $this->getData('id_signature_pvr_fin')) {
                            $signature = $this->getChildObject('signature_pvr_fin');
                            if (BimpObject::objectLoaded($signature)) {
                                if (!(int) $signature->isSigned()) {
                                    $signature->cancelAllSignatures();
                                }
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function reopenFromTarget($df_status, &$devis_fin_status, &$contrats_fin_status)
    {
        $errors = array();

        if (!array_key_exists($df_status, static::$status_list)) {
            $errors[] = 'Nouveau statut invalide: ' . $df_status;
        } else {
            $this->set('status', $df_status);

            $parent = $this->getParentInstance();
            if (BimpObject::objectLoaded($parent)) {
                $msg = 'Demande de location réouverte par ' . $this->displayTarget();
                $parent->addObjectLog($msg, 'DF_REOPEN');
                $this->addParentNoteForCommercial($msg);
            }

            // Détermination des statuts du devis et du contrat: 
            $devis_fin_status = 0;
            $contrat_fin_status = 0;
            $pvr_fin_status = 0;

            $dir = $this->getSignatureDocFileDir();

            if ((int) $this->getData('id_signature_contrat_fin')) {
                $signature = $this->getChildObject('signature_contrat_fin');
                if (BimpObject::objectLoaded($signature)) {
                    if (!(int) $signature->isSigned()) {
                        $contrat_fin_status = self::DOC_STATUS_SEND;
                        $signature->reopenAllSignatures();
                    } else {
                        $contrat_fin_status = self::DOC_STATUS_ACCEPTED;
                    }
                }
            }

            if (!$contrats_fin_status) {
                $file = $this->getSignatureDocFileName('contrat_fin');

                if (file_exists($dir . $file)) {
                    $contrats_fin_status = self::DOC_STATUS_ATTENTE;
                }
            }

            if ((int) $this->getData('id_signature_devis_fin')) {
                $signature = $this->getChildObject('signature_devis_fin');
                if (BimpObject::objectLoaded($signature)) {
                    if (!(int) $signature->isSigned()) {
                        $devis_fin_status = self::DOC_STATUS_SEND;
                        $signature->reopenAllSignatures();
                    } else {
                        $devis_fin_status = self::DOC_STATUS_ACCEPTED;
                    }
                }
            }

            if (!$devis_fin_status) {
                $file = $this->getSignatureDocFileName('devis_fin');

                if (file_exists($dir . $file)) {
                    $devis_fin_status = self::DOC_STATUS_ATTENTE;
                }
            }

            if ($contrats_fin_status > 0) {
                $devis_fin_status = self::DOC_STATUS_ACCEPTED;
            }

            if ((int) $this->getData('id_signature_pvr_fin')) {
                $signature = $this->getChildObject('signature_pvr_fin');
                if (BimpObject::objectLoaded($signature)) {
                    if (!(int) $signature->isSigned()) {
                        $pvr_fin_status = self::DOC_STATUS_SEND;
                        $signature->reopenAllSignatures();
                    } else {
                        $pvr_fin_status = self::DOC_STATUS_ACCEPTED;
                    }
                }
            }

            if (!$pvr_fin_status) {
                $file = $this->getSignatureDocFileName('pvr_fin');

                if (file_exists($dir . $file)) {
                    $pvr_fin_status = self::DOC_STATUS_ATTENTE;
                }
            }

            $this->set('devis_fin_status', $devis_fin_status);
            $this->set('contrat_fin_status', $contrat_fin_status);
            $this->set('pvr_fin_status', $pvr_fin_status);

            $warnings = array();
            $up_errors = $this->update($warnings, true);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la demande de location');
            }
        }

        return $errors;
    }

    public function addParentNoteForCommercial($msg, $delete_on_view = 1)
    {
        $errors = array();

        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpComm')) {
            $id_commercial = (int) $parent->getCommercialId();
            if ($id_commercial) {
                $errors = $parent->addNote($msg, null, 0, 0, '', BimpNote::BN_AUTHOR_USER, BimpNote::BN_DEST_USER, 0, $id_commercial, $delete_on_view);
            } else {
                $errors[] = 'Aucun commercial';
            }
        } else {
            $errors[] = 'Pièce d\'origine absente ou invalide';
        }

        return $errors;
    }

    public function onNewNoteFromTarget($note)
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
            $msg = 'Nouvelle note reçue de la part de ' . $this->displayTarget() . ' pour la demande de location ' . $this->getData('ref_ext_df');
            $msg .= '<br/><b>Message : ' . $note . '</b>';
            $msg .= '<br/><br/>Rendez-vous dans l\'onglet "Demande de location" ' . $parent->getLabel('of_the') . ' pour voir toutes les notes synchronisées';
            return $this->addParentNoteForCommercial($msg, 0);
        }

        return array('Pièce d\'origine absente');
    }

    public function setSerialsToTarget($id_commande)
    {
        $errors = array();

        if (!(int) $id_commande) {
            $errors[] = 'ID de la commande absent';
        }

        $api = $this->getExternalApi($errors);
        $id_df = (int) $this->getData('id_ext_df');

        if (!$id_df) {
            $errors[] = 'ID de la demande de location ' . $this->displayTarget() . ' absent';
        }

        if (!count($errors) && $this->isLoaded($errors) && $this->isTargetOk($errors)) {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);
            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'La commande #' . $id_commande . ' n\'existe plus';
            } else {
                $equipments = array();
                $lines = $commande->getLines('not_text');
                foreach ($lines as $line) {
                    if (!$line->isProductSerialisable()) {
                        continue;
                    }

                    $prod = $line->getProduct();
                    if (!BimpObject::objectLoaded($prod)) {
                        continue;
                    }

                    $ref = $prod->getRef();
                    if (!$ref) {
                        $errors[] = 'Ligne n° ' . $line->getData('position') . ' : référence produit absente';
                        continue;
                    }

                    $shipments = $line->getData('shipments');
                    foreach ($shipments as $id_shipment => $shipment_data) {
                        if (!(int) $shipment_data['shipped'] || empty($shipment_data['equipments'])) {
                            continue;
                        }

                        if (!isset($equipments[$ref])) {
                            $equipments[$ref] = array();
                        }

                        foreach ($shipment_data['equipments'] as $id_eq) {
                            $equipments[$ref][] = $id_eq;
                        }
                    }
                }

                if (empty($equipments)) {
                    $errors[] = 'Aucun numéro de série à transmettre';
                } else {
                    $serials = array();
                    foreach ($equipments as $prod_ref => $eq_ids) {
                        $rows = $this->db->getValues('be_equipment', 'serial', 'id IN (' . implode(',', $eq_ids) . ')');
                        if (is_array($rows)) {
                            $serials[$prod_ref] = array();
                            foreach ($rows as $serial) {
                                if ($serial) {
                                    $serials[$prod_ref][] = $serial;
                                }
                            }
                        }
                    }

                    if (empty($serials)) {
                        $errors[] = 'Aucun numéro de série à transmettre';
                    } else {
                        $req_errors = array();
                        $result = $api->setDemandeFinancementSerialNumbers($id_df, $serials, $req_errors);

                        if (count($req_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la requête');
                        } else {
                            if (isset($result['serials_ok']) && (int) $result['serials_ok']) {
                                $this->updateField('serials_ok', 1);
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionSendNote($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Note envoyée avec succès';

        if ($this->isLoaded($errors) && $this->isTargetOk($errors)) {
            $api = $this->getExternalApi($errors);
            $id_df = (int) $this->getData('id_ext_df');

            if (!$id_df) {
                $errors[] = 'ID de la demande de location ' . $this->displayTarget() . ' absent';
            }

            $note = BimpTools::getArrayValueFromPath($data, 'note', '');
            if (!$note) {
                $errors[] = 'Veuillez saisir la note';
            }

            if (!count($errors)) {
                $api->addDemandeFinancementNote($id_df, $note, $errors, $warnings);
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionCreateDemandeFinancement($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demande de location effectuée avec succès';

        $target = BimpTools::getArrayValueFromPath($data, 'target', '');
        if (!$target) {
            $errors[] = 'Veuillez sélectionner le destinataire de la demande de location';
        }

        $type_origine = BimpTools::getArrayValueFromPath($data, 'type_origine', '');
        if (!$type_origine) {
            $errors[] = 'Type de pièce d\'origine absent';
        }

        $id_origine = (int) BimpTools::getArrayValueFromPath($data, 'id_origine', 0);
        if (!$id_origine) {
            $errors[] = 'ID de la pièce d\'origine absent';
        }

        $origine = null;

        if (!count($errors)) {
            switch ($type_origine) {
                case 'propale':
                    $origine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_origine);
                    if (!BimpObject::objectLoaded($origine)) {
                        $errors[] = ucfirst($origine->getLabel('the')) . ' #' . $id_origine . ' n\'existe plus';
                    }
                    break;

                case 'commande':
                    $origine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_origine);
                    if (!BimpObject::objectLoaded($origine)) {
                        $errors[] = ucfirst($origine->getLabel('the')) . ' #' . $id_origine . ' n\'existe plus';
                    }
                    break;

                default:
                    $errors[] = 'Type de pièce d\'origine invalide';
                    break;
            }
        }

        if (BimpObject::objectLoaded($origine)) {
            $origine->isDemandeFinCreatable($errors);
        }

        if (!count($errors)) {
            $this->set('target', $target);

            $api = $this->getExternalApi($errors);

            if (!count($errors)) {
                $is_company = 0;

                $client = $origine->getChildObject('client');
                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Client absent';
                } else {
                    $is_company = (int) $client->isCompany();
                }

                $contact_suivi = null;
                $id_contact_suivi = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_suivi', 0);
                if ($id_contact_suivi) {
                    $contact_suivi = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_suivi);

                    if (!BimpObject::objectLoaded($contact_suivi)) {
                        $errors[] = 'Le contact de suivi sélectionné n\'existe plus';
                    }
                }

                $contact_signature = null;
                $id_contact_signature = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
                if ($id_contact_signature) {
                    $contact_signature = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_signature);

                    if (!BimpObject::objectLoaded($contact_signature)) {
                        $errors[] = 'Le contact signataire sélectionné n\'existe plus';
                    }
                }

                $commercial = $origine->getCommercial();
                if (!BimpObject::objectLoaded($commercial)) {
                    $errors[] = 'Commercial absent';
                }

                $fonction_signataire = BimpTools::getPostFieldValue('fonction_signataire', (BimpObject::objectLoaded($contact_signature) ? $contact_signature->getData('poste') : ''));

                $contacts_livraisons = array();
                foreach (BimpTools::getArrayValueFromPath($data, 'contacts_livraisons', array()) as $id_contact_liv) {
                    $contact_liv = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_liv);
                    if (BimpObject::objectLoaded($contact_liv)) {
                        $contacts_livraisons[] = $id_contact_liv;
                    } else {
                        $errors[] = 'Le contact de livraison #' . $id_contact_liv . ' n\'existe plus';
                    }
                }

                if (!count($errors)) {
                    $demande_data = array(
                        'demande'    => array(
                            'duration'    => BimpTools::getArrayValueFromPath($data, 'duration'),
                            'periodicity' => BimpTools::getArrayValueFromPath($data, 'periodicity'),
                            'mode_calcul' => BimpTools::getArrayValueFromPath($data, 'mode_calcul'),
                        ),
                        'origine'    => array(
                            'id'         => $origine->id,
                            'ref'        => $origine->getRef(),
                            'extra_data' => array(
                                'libelle'   => array('label' => 'Libellé', 'value' => $origine->getData('libelle')),
                                'total_ht'  => array('label' => 'Total HT', 'value' => $origine->getTotalHt(), 'type' => 'money'),
                                'total_ttc' => array('label' => 'Total TTC', 'value' => $origine->getTotalTtc(), 'type' => 'money')
                            )
                        ),
                        'lines'      => array(),
                        'client'     => array(
                            'id'              => $client->id,
                            'ref'             => $client->getRef(),
                            'nom'             => $client->getName(),
                            'is_company'      => (int) $client->isCompany(),
                            'siret'           => $client->getData('siret'),
                            'siren'           => $client->getData('siren'),
                            'forme_juridique' => $client->displayData('fk_forme_juridique', 'default', 0, 1),
                            'capital'         => $client->displayData('capital', 'default', 0, 1),
                            'extra_data'      => array(),
                            'address'         => array(),
                            'contact'         => array(),
                            'signataire'      => array(
                                'nom'      => (BimpObject::objectLoaded($contact_signature) ? $contact_signature->getData('lastname') : ''),
                                'prenom'   => (BimpObject::objectLoaded($contact_signature) ? $contact_signature->getData('firstname') : ''),
                                'fonction' => $fonction_signataire
                            ),
                            'livraisons'      => array()
                        ),
                        'commercial' => array(
                            'id'    => $commercial->id,
                            'nom'   => $commercial->getName(),
                            'tel'   => $commercial->getData('office_phone'),
                            'email' => $commercial->getData('email')
                        )
                    );

                    if ($is_company) {
                        $demande_data['client']['extra_data'] = array(
                            'alias'     => array('label' => 'Alias', 'value' => $client->getData('name_alias')),
                            'type_ent'  => array('label' => 'Type entreprise', 'value' => $client->displayData('fk_typent', 'default', false, true)),
                            'tva_assuj' => array('label' => 'Assujetti à la TVA', 'value' => $client->displayData('tva_assuj', 'default', false, true)),
                            'tva_intra' => array('label' => 'N° TVA', 'value' => $client->getData('tva_intra'))
                        );
                    }

                    if ($client->getData('address') && $client->getData('zip') && $client->getData('town')) {
                        $demande_data['client']['address'] = array(
                            'address' => $client->getData('address'),
                            'zip'     => $client->getData('zip'),
                            'town'    => $client->getData('town'),
                            'pays'    => $client->displayData('fk_pays', 'default', 0, 1)
                        );
                    } elseif (BimpObject::objectLoaded($contact_suivi)) {
                        $demande_data['client']['address'] = array(
                            'address' => $contact_suivi->getData('address'),
                            'zip'     => $contact_suivi->getData('zip'),
                            'town'    => $contact_suivi->getData('town'),
                            'pays'    => $contact_suivi->displayData('fk_pays', 'default', 0, 1)
                        );
                    }

                    if (BimpObject::objectLoaded($contact_suivi)) {
                        $demande_data['client']['contact'] = array(
                            'nom'    => $contact_suivi->getData('lastname'),
                            'prenom' => $contact_suivi->getData('firstname'),
                            'email'  => $contact_suivi->getData('email'),
                            'tel'    => $contact_suivi->getData('phone'),
                            'mobile' => $contact_suivi->getData('phone_mobile')
                        );
                    } else {
                        $demande_data['client']['contact'] = array(
                            'email' => $client->getData('email'),
                            'tel'   => $client->getData('phone'),
                        );
                    }

                    foreach ($contacts_livraisons as $id_contact_liv) {
                        $contact_liv = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_liv);
                        if (BimpObject::objectLoaded($contact_liv)) {
                            $demande_data['client']['livraisons'][] = array(
                                'address' => $contact_liv->getData('address'),
                                'zip'     => $contact_liv->getData('zip'),
                                'town'    => $contact_liv->getData('town'),
                                'pays'    => $contact_liv->displayData('fk_pays', 'default', 0, 1),
                                'email'   => $contact_liv->getData('email'),
                                'tel'     => $contact_liv->getData('phone'),
                                'mobile'  => $contact_liv->getData('phone_mobile')
                            );
                        }
                    }

                    foreach ($origine->getLines('all') as $line) {
                        if ((int) $line->id_remise_except) {
                            continue; // On exclut les événtuels acomptes / avoirs. 
                        }

                        switch ($line->getData('type')) {
                            case ObjectLine::LINE_PRODUCT:
                                $product = $line->getProduct();
                                if (BimpObject::objectLoaded($product)) {
                                    $serialisable = (BimpObject::objectLoaded($product) ? (int) $product->isSerialisable() : 0);
                                    $product_type = 1; // Produit
                                    if ($product->field_exists('type2') && (int) $product->getData('type2') === 5) {
                                        $product_type = 3; // Logiciel
                                    } elseif ($product->isTypeService()) {
                                        $product_type = 2; // Service
                                    }
                                    $demande_data['lines'][] = array(
                                        'id'           => $line->id,
                                        'type'         => 2,
                                        'ref'          => $product->getRef(),
                                        'label'        => $product->getName(),
                                        'product_type' => $product_type,
                                        'qty'          => $line->getFullQty(),
                                        'pu_ht'        => $line->pu_ht,
                                        'tva_tx'       => $line->tva_tx,
                                        'pa_ht'        => $line->pa_ht,
                                        'remise'       => $line->remise,
                                        'serialisable' => $serialisable,
                                        'serials'      => ($serialisable ? implode(',', $line->getSerials()) : '')
                                    );
                                }
                                break;

                            case ObjectLine::LINE_FREE:
                                $demande_data['lines'][] = array(
                                    'id'           => $line->id,
                                    'type'         => 2,
                                    'label'        => $line->description,
                                    'qty'          => $line->getFullQty(),
                                    'pu_ht'        => $line->pu_ht,
                                    'tva_tx'       => $line->tva_tx,
                                    'pa_ht'        => $line->pa_ht,
                                    'remise'       => $line->remise,
                                    'serialisable' => 0
                                );
                                break;

                            case ObjectLine::LINE_TEXT:
                                $demande_data['lines'][] = array(
                                    'id'          => $line->id,
                                    'type'        => 3,
                                    'description' => $line->desc,
                                );
                                break;
                        }
                    }

                    if (!count($errors)) {
                        $req_errors = array();
                        $result = $api->addDemandeFinancement($type_origine, $demande_data, $req_errors, $warnings);

                        if (isset($result['id_demande']) && (int) $result['id_demande']) {
                            $origine->addObjectLog('Création de la demande de location sur ' . $this->displayTarget() . ' effectuée avec succès');

                            $df_data = array(
                                'obj_module'           => $origine->module,
                                'obj_name'             => $origine->object_name,
                                'id_obj'               => $origine->id,
                                'id_ext_df'            => (int) $result['id_demande'],
                                'ref_ext_df'           => $result['ref_demande'],
                                'status'               => self::DOC_STATUS_ATTENTE,
                                'id_contact_suivi'     => $id_contact_suivi,
                                'id_contact_signature' => $id_contact_signature,
                                'contacts_livraisons'  => $contacts_livraisons
                            );

                            $this->validateArray($df_data);

                            $create_errors = $this->create($warnings, true);

                            if (count($create_errors)) {
                                $msg = 'Création de la demande de location sur ' . $this->displayTarget() . ' ok';
                                $msg .= ' mais échec de l\'enregistrement des données au niveau local.<br/>';
                                $msg .= 'L\'équipe de développement est prévenue et va procéder à une correction manuelle';
                                $warnings[] = BimpTools::getMsgFromArray($create_errors, $msg);

                                BimpCore::addlog('Echec création DF locale suite à DF ' . $this->displayTarget() . ' - CORRECTION MANUELLE NECESSAIRE', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', $origine, array(
                                    'Données' => $df_data,
                                    'Erreurs' => $errors
                                        ), true);
                            } else {
                                $up_errors = $origine->updateField('id_demande_fin', $this->id);
                                if (count($up_errors)) {
                                    BimpCore::addlog('Echec enregistrement ID DF locale suite à DF ' . $this->displayTarget() . ' - CORRECTION MANUELLE NECESSAIRE', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', $origine, array(
                                        'ID'      => $this->id,
                                        'Erreurs' => $errors
                                            ), true);
                                }
                            }
                        } elseif (count($req_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la création de la demande de location sur ' . $this->displayTarget());
                            $origine->addObjectLog(BimpTools::getMsgFromArray($req_errors, 'Echec de la création de la demande de location sur LDLC PRO LEASE'));
                        } else {
                            $errors[] = 'Echec de la requête (Aucune réponse reçue)';
                            $origine->addObjectLog('Echec de la création de la demande de location sur ' . $this->displayTarget() . ' (Aucune réponse reçue suite à la requête)');
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionEditClientData($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mise à jour des données du client effectuée avec succès';

        $api = $this->getExternalApi($errors);
        $parent = $this->getParentInstance();
        $client = null;
        $is_company = 0;

        if (!BimpObject::objectLoaded($parent) || !is_a($parent, 'BimpComm')) {
            $errors[] = 'Pièce commerciale liée invalide';
        } else {
            $client = $parent->getChildObject('client');

            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Client absent';
            } else {
                $is_company = (int) $client->isCompany();
            }
        }

        $contact_suivi = null;
        $id_contact_suivi = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_suivi', (int) $this->getData('id_contact_suivi'));
        if ($id_contact_suivi) {
            $contact_suivi = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_suivi);

            if (!BimpObject::objectLoaded($contact_suivi)) {
                $errors[] = 'Le contact de suivi sélectionné n\'existe plus';
            }
        }

        $contact_signature = null;
        $id_contact_signature = (int) BimpTools::getArrayValueFromPath($data, 'id_contact_signature', (int) $this->getData('id_contact_signature'));
        if ($id_contact_signature) {
            $contact_signature = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_signature);

            if (!BimpObject::objectLoaded($contact_signature)) {
                $errors[] = 'Le contact signataire sélectionné n\'existe plus';
            }
        }

        $fonction_signataire = BimpTools::getPostFieldValue('fonction_signataire', (BimpObject::objectLoaded($contact_signature) ? $contact_signature->getData('poste') : ''));

        $contacts_livraisons = array();
        foreach (BimpTools::getArrayValueFromPath($data, 'contacts_livraisons', $this->getData('contacts_livraisons')) as $id_contact_liv) {
            $contact_liv = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_liv);
            if (BimpObject::objectLoaded($contact_liv)) {
                $contacts_livraisons[] = $id_contact_liv;
            } else {
                $errors[] = 'Le contact de livraison #' . $id_contact_liv . ' n\'existe plus';
            }
        }

        if (!count($errors)) {
            $client_data = array(
                'id'              => $client->id,
                'ref'             => $client->getRef(),
                'nom'             => $client->getName(),
                'is_company'      => (int) $client->isCompany(),
                'siret'           => $client->getData('siret'),
                'siren'           => $client->getData('siren'),
                'forme_juridique' => $client->displayData('fk_forme_juridique', 'default', 0, 1),
                'capital'         => $client->displayData('capital', 'default', 0, 1),
                'extra_data'      => array(),
                'address'         => array(),
                'contact'         => array(),
                'signataire'      => array(
                    'nom'      => (BimpObject::objectLoaded($contact_signature) ? $contact_signature->getData('lastname') : ''),
                    'prenom'   => (BimpObject::objectLoaded($contact_signature) ? $contact_signature->getData('firstname') : ''),
                    'fonction' => $fonction_signataire
                ),
                'livraisons'      => array()
            );

            if ($is_company) {
                $client_data['extra_data'] = array(
                    'alias'     => array('label' => 'Alias', 'value' => $client->getData('name_alias')),
                    'type_ent'  => array('label' => 'Type entreprise', 'value' => $client->displayData('fk_typent', 'default', false, true)),
                    'tva_assuj' => array('label' => 'Assujetti à la TVA', 'value' => $client->displayData('tva_assuj', 'default', false, true)),
                    'tva_intra' => array('label' => 'N° TVA', 'value' => $client->getData('tva_intra'))
                );
            }

            if ($client->getData('address') && $client->getData('zip') && $client->getData('town')) {
                $client_data['address'] = array(
                    'address' => $client->getData('address'),
                    'zip'     => $client->getData('zip'),
                    'town'    => $client->getData('town'),
                    'pays'    => $client->displayData('fk_pays', 'default', 0, 1)
                );
            } elseif (BimpObject::objectLoaded($contact_suivi)) {
                $client_data['address'] = array(
                    'address' => $contact_suivi->getData('address'),
                    'zip'     => $contact_suivi->getData('zip'),
                    'town'    => $contact_suivi->getData('town'),
                    'pays'    => $contact_suivi->displayData('fk_pays', 'default', 0, 1)
                );
            }

            if (BimpObject::objectLoaded($contact_suivi)) {
                $client_data['contact'] = array(
                    'nom'    => $contact_suivi->getData('lastname'),
                    'prenom' => $contact_suivi->getData('firstname'),
                    'email'  => $contact_suivi->getData('email'),
                    'tel'    => $contact_suivi->getData('phone'),
                    'mobile' => $contact_suivi->getData('phone_mobile')
                );
            } else {
                $client_data['contact'] = array(
                    'email' => $client->getData('email'),
                    'tel'   => $client->getData('phone'),
                );
            }

            foreach ($contacts_livraisons as $id_contact_liv) {
                $contact_liv = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact_liv);
                if (BimpObject::objectLoaded($contact_liv)) {
                    $client_data['livraisons'][] = array(
                        'address' => $contact_liv->getData('address'),
                        'zip'     => $contact_liv->getData('zip'),
                        'town'    => $contact_liv->getData('town'),
                        'pays'    => $contact_liv->displayData('fk_pays', 'default', 0, 1),
                        'email'   => $contact_liv->getData('email'),
                        'tel'     => $contact_liv->getData('phone'),
                        'mobile'  => $contact_liv->getData('phone_mobile')
                    );
                }
            }

            if (!count($errors)) {
                $req_errors = array();
                $result = $api->editDemandeFinancementClientData((int) $this->getData('id_ext_df'), $client_data, $req_errors, $warnings);

                if (isset($result['success']) && (int) $result['success']) {
                    $this->validateArray(array(
                        'id_contact_suivi'     => $id_contact_suivi,
                        'id_contact_signature' => $id_contact_signature,
                        'contacts_livraisons'  => $contacts_livraisons
                    ));

                    $up_errors = $this->update($warnings, true);

                    if (count($up_errors)) {
                        $msg = 'Mise à jour des données du client sur' . $this->displayTarget() . ' ok';
                        $msg .= ' mais échec de l\'enregistrement des données au niveau local';
                        $warnings[] = BimpTools::getMsgFromArray($up_errors, $msg);
                    }
                } elseif (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de la mise à jour des données client sur ' . $this->displayTarget());
                } else {
                    $errors[] = 'Echec de la requête (Aucune réponse reçue)';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => (empty($warnings) ? 'bimp_reloadPage();' : '')
        );
    }

    public function actionCancelDemandeFinancement($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demande de location annulée avec succès';

        if ($this->isTargetOk($errors)) {
            $api = $this->getExternalApi($errors);
            $id_df = (int) $this->getData('id_ext_df');

            if (!$id_df) {
                $errors[] = 'ID de la demande de location ' . $this->displayTarget() . ' absent';
            }

            if (!count($errors)) {
                $note = BimpTools::getArrayValueFromPath($data, 'note', '');
                $req_errors = array();
                $api->cancelDemandeFinancement($id_df, $note, $req_errors);

                if (count($req_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'enregistrement de l\'annulation sur ' . $this->displayTarget());
                } else {
                    $this->set('status', self::DOC_STATUS_CANCELED);

                    $df_status = (int) $this->getData('devis_fin_status');
                    if ($df_status > 0 && $df_status < 10) {
                        $this->set('devis_fin_status', self::DOC_STATUS_CANCELED);
                    }

                    $cf_status = (int) $this->getData('contrat_fin_status');
                    if ($cf_status > 0 && $cf_status < 10) {
                        $this->set('contrat_fin_status', self::DOC_STATUS_CANCELED);
                    }

                    $up_errors = $this->update($warnings, true);
                    if (count($up_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut de la demande de location');
                    } else {
                        $parent = $this->getParentInstance();

                        if (BimpObject::objectLoaded($parent)) {
                            $msg = 'Demande de location annulée' . ($note ? '.<br/><b>Motif : </b>' . $note : '');
                            $parent->addObjectLog($msg, 'DF_CANCELLED');
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionCreateDevisFinSignature($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $parent = $this->getParentInstance();

        if (!BimpObject::objectLoaded($parent)) {
            $errors[] = 'Pièce parente absente';
        } elseif (!is_a($parent, 'BimpComm')) {
            $errors[] = 'Pièce parente invalide';
        } else {
            $id_client = (int) $parent->getData('fk_soc');

            if (!$id_client) {
                $errors[] = 'Client absent';
            }

            if ((int) $this->getData('id_signature_devis_fin')) {
                $errors[] = 'La fiche signature du devis de location a déjà été créée pour ' . $this->getLabel('this');
            }

            $id_contact_signature = BimpTools::getArrayValueFromPath($data, 'id_contact_signature', 0);
            $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getSignatureEmailContent('devis_fin'));

            if (!count($errors)) {
                $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                            'obj_module'       => $this->module,
                            'obj_name'         => $this->object_name,
                            'id_obj'           => $this->id,
                            'doc_type'         => 'devis_fin',
                            'obj_params_field' => 'signature_df_params'
                                ), true, $errors, $warnings);

                if (!count($errors) && BimpObject::objectLoaded($signature)) {
                    $success = 'Création de la fiche signature effectuée avec succès';
                    $parent->addObjectLog('Fiche signature du devis de location créée', 'SIGNATURE_DEVIS_FIN_CREEE');
                    $up_errors = $this->updateField('id_signature_devis_fin', (int) $signature->id);

                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregstrement de l\'ID de la fiche signature');
                    } else {
                        $signataire_errors = array();
                        $signataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                    'id_signature' => $signature->id,
                                    'id_client'    => $id_client,
                                    'id_contact'   => $id_contact_signature,
                                    'allow_dist'   => 1,
                                    'allow_refuse' => 0
                                        ), true, $signataire_errors, $warnings);

                        if (!BimpObject::objectLoaded($signataire)) {
                            $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du contact signataire à la fiche signature');
                        } else {
                            $open_warnings = array();
                            $open_errors = $signataire->openSignDistAccess(true, $email_content, true, array(), '', $open_warnings, $success);

                            if (count($open_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                            } else {
                                $this->updateField('devis_fin_status', self::DOC_STATUS_SEND);
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionCreateContratFinSignature($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $parent = $this->getParentInstance();

        if (!BimpObject::objectLoaded($parent)) {
            $errors[] = 'Pièce parente absente';
        } elseif (!is_a($parent, 'BimpComm')) {
            $errors[] = 'Pièce parente invalide';
        } else {
            $id_client = (int) $parent->getData('fk_soc');

            if (!$id_client) {
                $errors[] = 'Client absent';
            }

            if ((int) $this->getData('id_signature_contrat_fin')) {
                $errors[] = 'La fiche signature du contrat de location a déjà été créée pour ' . $this->getLabel('this');
            }

            $signataires_data = $this->getData('signataires_cf_data');

            $loueur_nom = BimpTools::getArrayValueFromPath($signataires_data, 'loueur/nom', '', $errors, true, 'Nom du loueur absent');
            $loueur_email = BimpTools::getArrayValueFromPath($signataires_data, 'loueur/email', '', $errors, true, 'Adresse e-mail du loueur absente');
            $loueur_fonction = BimpTools::getArrayValueFromPath($signataires_data, 'loueur/fonction', '', $errors, true, 'Qualité du loueur absente');
            $cessionnaire_nom = BimpTools::getArrayValueFromPath($signataires_data, 'cessionnaire/nom');
            $cessionnaire_email = BimpTools::getArrayValueFromPath($signataires_data, 'cessionnaire/email', '', $errors, true, 'Adresse e-mail du cessionnaire absente');
            $cessionnaire_fonction = BimpTools::getArrayValueFromPath($signataires_data, 'cessionnaire/fonction');

            $allow_dist = (int) BimpCore::getConf('contrat_fin_signature_allow_dist', null, 'bimpcommercial');
            $allow_docusign = (int) BimpCore::getConf('contrat_fin_signature_allow_docusign', null, 'bimpcommercial');
            $allow_refuse = (int) BimpCore::getConf('contrat_fin_signature_allow_refuse', null, 'bimpcommercial');

            $sign_dist = (int) BimpTools::getArrayValueFromPath($data, 'sign_dist', 0);
            $init_docusign = (int) BimpTools::getArrayValueFromPath($data, 'init_docusign', 0);
            $open_dist_access = (int) BimpTools::getArrayValueFromPath($data, 'open_dist_access', 0);
            $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getSignatureEmailContent($init_docusign && $allow_docusign ? 'docusign' : 'elec'));

            if (!$cessionnaire_nom && $init_docusign && $allow_docusign) {
                $cessionnaire_nom = BimpTools::getArrayValueFromPath($signataires_data, 'cessionnaire/raison_social', '');
            }
            
            if (!count($errors)) {
                $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                            'obj_module'       => $this->module,
                            'obj_name'         => $this->object_name,
                            'id_obj'           => $this->id,
                            'doc_type'         => 'contrat_fin',
                            'obj_params_field' => 'signature_cf_params'
                                ), true, $errors, $warnings);

                if (!count($errors) && BimpObject::objectLoaded($signature)) {
                    $success = 'Création de la fiche signature effectuée avec succès';
                    $parent->addObjectLog('Fiche signature du contrat de location créée', 'SIGNATURE_CONTRAT_FIN_CREEE');
                    $up_errors = $this->updateField('id_signature_contrat_fin', (int) $signature->id);

                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de l\'ID de la fiche signature');
                    } else {
                        BimpObject::loadClass('bimpcore', 'BimpSignataire');
                        
                        $signataire_errors = array();
                        $signataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                    'id_signature'   => $signature->id,
                                    'type'           => BimpSignataire::TYPE_CLIENT,
                                    'code'           => 'locataire',
                                    'label'          => 'Locataire',
                                    'id_signature'   => $signature->id,
                                    'id_client'      => $id_client,
                                    'id_contact'     => (int) $this->getData('id_contact_signature'),
                                    'allow_dist'     => $allow_dist,
                                    'allow_docusign' => $allow_docusign,
                                    'allow_refuse'   => $allow_refuse
                                        ), true, $signataire_errors, $warnings);

                        if (!BimpObject::objectLoaded($signataire)) {
                            $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du signataire "Locataire" à la fiche signature');
                        }

                        $signataire_errors = array();
                        $signataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                    'id_signature'   => $signature->id,
                                    'type'           => BimpSignataire::TYPE_CUSTOM,
                                    'code'           => 'loueur',
                                    'label'          => 'Loueur',
                                    'nom'            => $loueur_nom,
                                    'email'          => $loueur_email,
                                    'fonction'       => $loueur_fonction,
                                    'allow_dist'     => $allow_dist,
                                    'allow_docusign' => $allow_docusign,
                                    'allow_refuse'   => $allow_refuse
                                        ), true, $signataire_errors, $warnings);

                        if (!BimpObject::objectLoaded($signataire)) {
                            $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du signataire "Loueur" à la fiche signature');
                        }

                        $signataire_errors = array();
                        $signataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                    'id_signature'   => $signature->id,
                                    'type'           => BimpSignataire::TYPE_CUSTOM,
                                    'code'           => 'cessionnaire',
                                    'label'          => 'Cessionnaire',
                                    'nom'            => $cessionnaire_nom,
                                    'email'          => $cessionnaire_email,
                                    'fonction'       => $cessionnaire_fonction,
                                    'allow_dist'     => $allow_dist,
                                    'allow_docusign' => $allow_docusign,
                                    'allow_refuse'   => $allow_refuse
                                        ), true, $signataire_errors, $warnings);

                        if (!BimpObject::objectLoaded($signataire)) {
                            $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du signataire "Cessionnaire" à la fiche signature');
                        }

                        if (!count($errors)) {
                            if ($sign_dist) {
                                if ($init_docusign && $allow_docusign) {
                                    $docusign_success = '';
                                    $docusign_result = $signature->setObjectAction('initDocuSign', 0, array(
                                        'email_content' => $email_content
                                            ), $docusign_success, true);

                                    if (count($docusign_result['errors'])) {
                                        $warnings[] = BimpTools::getMsgFromArray($docusign_result['errors'], 'Echec de l\'envoi de la demande de signature via DocuSign');
                                    } else {
                                        $success .= '<br/>' . $docusign_success;
                                    }
                                    if (!empty($docusign_result['warnings'])) {
                                        $warnings[] = BimpTools::getMsgFromArray($docusign_result['warnings'], 'Envoi de la demande de signature via DocuSign');
                                    }
                                } elseif ($open_dist_access && $allow_dist) {
                                    $open_errors = $signature->openAllSignDistAccess($email_content, $warnings, $success);

                                    if (count($open_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                                    } else {
                                        $this->updateField('contrat_fin_status', self::DOC_STATUS_SEND);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionCreatePvrFinSignature($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $parent = $this->getParentInstance();

        if (!BimpObject::objectLoaded($parent)) {
            $errors[] = 'Pièce parente absente';
        } elseif (!is_a($parent, 'BimpComm')) {
            $errors[] = 'Pièce parente invalide';
        } else {
            $id_client = (int) $parent->getData('fk_soc');

            if (!$id_client) {
                $errors[] = 'Client absent';
            }

            if ((int) $this->getData('id_signature_pvr_fin')) {
                $errors[] = 'La fiche signature du PV de réception a déjà été créée pour ' . $this->getLabel('this');
            }

            $signataires_data = $this->getData('signataires_pvr_data');

            $loueur_nom = BimpTools::getArrayValueFromPath($signataires_data, 'loueur/nom', '', $errors, true, 'Nom du loueur absent');
            $loueur_email = BimpTools::getArrayValueFromPath($signataires_data, 'loueur/email', '', $errors, true, 'Adresse e-mail du loueur absente');
            $loueur_fonction = BimpTools::getArrayValueFromPath($signataires_data, 'loueur/fonction', '', $errors, true, 'Qualité du loueur absente');

            $allow_dist = (int) BimpCore::getConf('pvr_fin_signature_allow_dist', null, 'bimpcommercial');
            $allow_docusign = (int) BimpCore::getConf('pvr_fin_signature_allow_docusign', null, 'bimpcommercial');
            $allow_refuse = (int) BimpCore::getConf('pvr_fin_signature_allow_refuse', null, 'bimpcommercial');

            $sign_dist = (int) BimpTools::getArrayValueFromPath($data, 'sign_dist', 0);
            $init_docusign = (int) BimpTools::getArrayValueFromPath($data, 'init_docusign', 0);
            $open_dist_access = (int) BimpTools::getArrayValueFromPath($data, 'open_dist_access', 0);
            $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getSignatureEmailContent($init_docusign && $allow_docusign ? 'docusign' : 'elec'));

            if (!count($errors)) {
                $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                            'obj_module'       => $this->module,
                            'obj_name'         => $this->object_name,
                            'id_obj'           => $this->id,
                            'doc_type'         => 'pvr_fin',
                            'obj_params_field' => 'signature_pvr_params'
                                ), true, $errors, $warnings);

                if (!count($errors) && BimpObject::objectLoaded($signature)) {
                    $success = 'Création de la fiche signature effectuée avec succès';
                    $parent->addObjectLog('Fiche signature du PV de réception créée', 'SIGNATURE_PVR_FIN_CREEE');
                    $up_errors = $this->updateField('id_signature_pvr_fin', (int) $signature->id);

                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de l\'ID de la fiche signature');
                    } else {
                        $signataire_errors = array();
                        BimpObject::loadClass('bimpcore', 'BimpSignataire');
                        $signataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                    'id_signature'   => $signature->id,
                                    'type'           => BimpSignataire::TYPE_CLIENT,
                                    'code'           => 'locataire',
                                    'label'          => 'Locataire',
                                    'id_signature'   => $signature->id,
                                    'id_client'      => $id_client,
                                    'id_contact'     => (int) $this->getData('id_contact_signature'),
                                    'allow_dist'     => $allow_dist,
                                    'allow_docusign' => $allow_docusign,
                                    'allow_refuse'   => $allow_refuse
                                        ), true, $signataire_errors, $warnings);

                        if (!BimpObject::objectLoaded($signataire)) {
                            $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du signataire "Locataire" à la fiche signature');
                        }

                        $signataire_errors = array();
                        $signataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                                    'id_signature'   => $signature->id,
                                    'type'           => BimpSignataire::TYPE_CUSTOM,
                                    'code'           => 'loueur',
                                    'label'          => 'Loueur',
                                    'nom'            => $loueur_nom,
                                    'email'          => $loueur_email,
                                    'fonction'       => $loueur_fonction,
                                    'allow_dist'     => $allow_dist,
                                    'allow_docusign' => $allow_docusign,
                                    'allow_refuse'   => $allow_refuse
                                        ), true, $signataire_errors, $warnings);

                        if (!BimpObject::objectLoaded($signataire)) {
                            $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du signataire "Loueur" à la fiche signature');
                        }

                        if (!count($errors)) {
                            if ($sign_dist) {
                                if ($init_docusign && $allow_docusign) {
                                    $docusign_success = '';
                                    $docusign_result = $signature->setObjectAction('initDocuSign', 0, array(
                                        'email_content' => $email_content
                                            ), $docusign_success, true);

                                    if (count($docusign_result['errors'])) {
                                        $warnings[] = BimpTools::getMsgFromArray($docusign_result['errors'], 'Echec de l\'envoi de la demande de signature via DocuSign');
                                    } else {
                                        $success .= '<br/>' . $docusign_success;
                                    }
                                    if (!empty($docusign_result['warnings'])) {
                                        $warnings[] = BimpTools::getMsgFromArray($docusign_result['warnings'], 'Envoi de la demande de signature via DocuSign');
                                    }
                                } elseif ($open_dist_access && $allow_dist) {
                                    $open_errors = $signature->openAllSignDistAccess($email_content, $warnings, $success);

                                    if (count($open_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                                    } else {
                                        $this->updateField('pvr_fin_status', self::DOC_STATUS_SEND);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
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
            $doc_label = $this->getDocTypeLabel($doc_type);

            $signature = $this->getChildObject('signature_' . $doc_type);
            if (!BimpObject::objectLoaded($signature)) {
                $errors[] = 'La fiche signature du ' . $doc_label . ' n\'existe pas';
            } elseif (!$signature->isSigned()) {
                $errors[] = 'Le' . $doc_label . ' n\'a pas encore été signé par le client';
            } elseif ((int) $this->getData($doc_type . '_status') === self::DOC_STATUS_ACCEPTED) {
                $errors[] = 'Le' . $doc_label . ' signé a déjà été envoyé à LDLC PRO LEASE avec succès';
            } else {
                $errors = $this->onSigned($signature);

                if (!count($errors)) {
                    $success = ucfirst($doc_label) . ' envoyé avec succès à ' . $this->displayTarget();
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionSubmitDevisFinRefused($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Refus du devis de location enregistré avec succès';

        $errors = $this->submitDocFinRefused('devis_fin', BimpTools::getArrayValueFromPath($data, 'note', ''));

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionSubmitContratFinRefused($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Refus du contrat de location enregistré avec succès';

        $errors = $this->submitDocFinRefused('contrat_fin', BimpTools::getArrayValueFromPath($data, 'note', ''));

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionSubmitPvrFinRefused($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Refus du PV de réception enregistré avec succès';

        $errors = $this->submitDocFinRefused('pvr_fin', BimpTools::getArrayValueFromPath($data, 'note', ''));

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    // Gestion signatures: 

    public function getSignatureDocRef($doc_type)
    {
        $ref = $this->getData('ref_ext_df');

        if ($ref) {
            switch ($doc_type) {
                case 'devis_fin':
                    return $ref;

                case 'contrat_fin':
                    return str_replace('DF', 'CTF', $ref);

                case 'pvr_fin':
                    return 'PVR_' . $ref;
            }
        }

        return '';
    }

    public function getSignatureDocFileName($doc_type, $signed = false)
    {
        $ext = $this->getSignatureDocFileExt($doc_type, $signed);
        $ref = $this->getSignatureDocRef($doc_type);

        if ($ref) {
            return $ref . ($signed ? '_signe' : '') . '.' . $ext;
        }

        return '';
    }

    public function getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false)
    {
        if (!$this->isLoaded()) {
            return '';
        }
        $context = BimpCore::getContext();

        if ($forced_context) {
            $context = $forced_context;
        }

        $fileName = $this->getSignatureDocFileName($doc_type, $signed);

        if ($fileName) {
            if ($context === 'public') {
                return self::getPublicBaseUrl() . 'fc=doc&doc=' . $doc_type . ($signed ? '_signed' : '') . '&docid=' . $this->id . '&docref=' . $this->getRef();
            } else {
                return $this->getFileUrl($fileName);
            }
        }

        return '';
    }

    public function getSignatureParams($doc_type)
    {
        switch ($doc_type) {
            case 'devis_fin':
                return $this->getData('signature_df_params');

            case 'contrat_fin':
                return $this->getData('signature_cf_params');

            case 'pvr_fin':
                return $this->getData('signature_pvr_params');
        }

        return array();
    }

    public function onSigned($bimpSignature)
    {
        $errors = array();
        $doc_type = $bimpSignature->getData('doc_type');

        if ($this->isLoaded($errors) && $this->isTargetOk($errors)) {
            $api = $this->getExternalApi($errors);
            $id_df = (int) $this->getData('id_ext_df');
            $file_content = '';

            if (!$id_df) {
                $errors[] = 'ID de la demande de location ' . $this->displayTarget() . ' absent';
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
                    $errors[] = BimpTools::getMsgFromArray($req_errors, 'Echec de l\'envoi du fichier à ' . $this->displayTarget());
                } else {
                    $this->updateField($doc_type . '_status', self::DOC_STATUS_ACCEPTED);

                    $parent = $this->getParentInstance();
                    if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
                        $parent->addObjectLog(self::$signature_doc_types[$doc_type] . ' signé par le client', strtoupper($doc_type) . '_SIGNE');

                        if (method_exists($parent, 'onDocFinancementSigned')) {
                            $parent->onDocFinancementSigned($doc_type);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function isSignatureCancellable()
    {
        return 0;
    }
}

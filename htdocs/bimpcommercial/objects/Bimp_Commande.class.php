<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

class Bimp_Commande extends BimpComm
{

    public $acomptes_allowed = true;
    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public static $dol_module = 'commande';
    public static $email_type = 'order_send';
    public static $status_list = array(
        -3 => array('label' => 'Stock insuffisant', 'icon' => 'exclamation-triangle', 'classes' => array('warning')),
        -1 => array('label' => 'Abandonnée', 'icon' => 'times-circle', 'classes' => array('danger')),
        0  => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1  => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2  => array('label' => 'Acceptée', 'icon' => 'check-circle', 'classes' => array('success')),
        3  => array('label' => 'Fermée', 'icon' => 'times', 'classes' => array('danger')),
    );
    public static $logistique_status = array(
        0 => array('label' => 'A traiter', 'icon' => 'fas_exclamation-circle', 'classes' => array('important')),
        1 => array('label' => 'En cours de traitement', 'icon' => 'fas_cogs', 'classes' => array('info')),
        2 => array('label' => 'Traitée', 'icon' => 'fas_check', 'classes' => array('success')),
        3 => array('label' => 'Compléte', 'icon' => 'fas_check', 'classes' => array('success')),
        4 => array('label' => 'En attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        5 => array('label' => 'A supprimer', 'icon' => 'fas_exclamation-triangle', 'classes' => array('danger')),
    );
    public static $shipment_status = array(
        0 => array('label' => 'Non expédiée', 'icon' => 'fas_shipping-fast', 'classes' => array('danger')),
        1 => array('label' => 'Expédiée partiellement', 'icon' => 'fas_shipping-fast', 'classes' => array('warning')),
        2 => array('label' => 'Expédiée', 'icon' => 'fas_shipping-fast', 'classes' => array('success'))
    );
    public static $invoice_status = array(
        0 => array('label' => 'Non facturée', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('danger')),
        1 => array('label' => 'Facturée partiellement', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('warning')),
        2 => array('label' => 'Facturée', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('success'))
    );
    public static $revalorisations = array(
        0 => array('label' => 'NON', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'OUI', 'icon' => 'fas_exclamation', 'classes' => array('warning')),
        2 => array('label' => 'Traité', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $extra_satus = array(
        0 => array('label' => 'Aucune', 'classes' => array('info')),
        1 => array('label' => 'A supprimer', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger')),
        2 => array('label' => 'Non facturable', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger'))
    );
    public static $logistique_active_status = array(1, 2, 3);

    // Gestion des droits et autorisations: 

    public function canCreate()
    {
        if (defined('NOLOGIN')) {
            return 1;
        }

        global $user;
        if (isset($user->rights->commande->creer)) {
            return (int) $user->rights->commande->creer;
        }

        return 0;
    }

    protected function canEdit()
    {
        return $this->can("create");
    }

    public function canSetAction($action)
    {
        global $conf, $user;

        switch ($action) {
            case 'validate':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->commande->creer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->commande->order_advance->validate))) {
                    return 1;
                }
                return 0;

            case 'cancel':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->commande->cloturer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->commande->order_advance->annuler))) {
                    return 1;
                }
                return 0;

            case 'sendMail':
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->commande->order_advance->send) {
                    return 1;
                }
                return 0;

            case 'reopen':
            case 'duplicate':
                return (int) $this->can("create");

            case 'processLogitique':
                return 1;

            case 'forceStatus':
                return 1;
        }
        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande absent';
            return 0;
        }

        global $conf;
        $status = (int) $this->getData('fk_statut');
        $invalide_error = 'Le statut actuel de la commande ne permet pas cette opération';

        switch ($action) {
            case 'sendMail':
                if ($status <= Commande::STATUS_DRAFT) {
                    $errors[] = $invalide_error;
                    return 0;
                }
                return 1;

            case 'validate':
                if ($status !== Commande::STATUS_DRAFT) {
                    $errors[] = $invalide_error;
                    return 0;
                } else {
                    $lines = $this->getChildrenObjects('lines');
                    if (!count($lines)) {
                        $errors[] = 'Aucune ligne enregistrée pour cette commande';
                        return 0;
                    }
                }
                return 1;

            case 'reopen':
                if (!in_array($status, array(Commande::STATUS_CLOSED, Commande::STATUS_CANCELED))) {
                    $errors[] = $invalide_error;
                    return 0;
                }
                return 1;

            case 'cancel':
                if ($status !== Commande::STATUS_VALIDATED) {
                    $errors[] = $invalide_error;
                    return 0;
                }
                return 1;

            case 'processLogitique':
                if (!in_array($status, self::$logistique_active_status)) {
                    $errors[] = 'La logistique n\'est pas active pour cette commande';
                    return 0;
                }
                if ((int) $this->getData('logistique_status') > 0) {
                    $errors[] = 'La logistique est déjà prise en charge pour cette commande';
                    return 0;
                }
                return 1;

            case 'forceStatus':
                if (!$this->isLogistiqueActive()) {
                    $errors[] = 'La logistique n\'est pas active';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters booléens:

    public function isLogistiqueActive()
    {
        if (in_array((int) $this->getData('fk_statut'), self::$logistique_active_status) && (int) $this->getData('logistique_status') > 0) {
            return 1;
        }

        return 0;
    }

    // Getters: 

    public function getDefaultListExtraButtons()
    {
        $buttons = parent::getDefaultListExtraButtons();

        if ($this->isLoaded() && $this->isLogistiqueActive()) {
            $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $this->id;
            $buttons[] = array(
                'label'   => 'Page logistique',
                'icon'    => 'fas_truck-loading',
                'onclick' => 'window.open(\'' . $url . '\')'
            );
        }

        return $buttons;
    }

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFCommandes')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
        }

        return ModelePDFCommandes::liste_modeles($this->db->db);
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->commande->dir_output;
    }

    public function getActionsButtons()
    {
        global $conf, $langs, $user;

        $buttons = parent::getActionsButtons();

        if ($this->isLoaded()) {
            $status = (int) $this->getData('fk_statut');
            $ref = $this->getRef();
            $client = $this->getChildObject('client');

            // Envoyer par e-mail
            if ($this->isActionAllowed('sendMail')) {
                if ($this->canSetAction('sendMail')) {
                    $buttons[] = array(
                        'label'   => 'Envoyer par e-mail',
                        'icon'    => 'envelope',
                        'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
                            'form_name' => 'email'
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Envoyer par e-mail',
                        'icon'     => 'envelope',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission'
                    );
                }
            }

//            // Valider
            if ($this->isActionAllowed('validate')) {
                if ($this->canSetAction('validate')) {
                    $buttons[] = array(
                        'label'   => 'Valider',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('validate', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la validation de cette commande'
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Valider',
                        'icon'     => 'fas_check',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission'
                    );
                }
            }

            // Edit (désactivé)
//            if ($status == Commande::STATUS_VALIDATED && $this->can("create")) {
//                $buttons[] = array(
//                    'label'   => 'Modifier',
//                    'icon'    => 'undo',
//                    'onclick' => $this->getJsActionOnclick('modify', array(), array(
//                        'confirm_msg' => strip_tags($langs->trans('ConfirmUnvalidateOrder', $ref))
//                    ))
//                );
//            }
//            
            // Créer intervention
            if ($conf->ficheinter->enabled) {
                $langs->load("interventions");

                if ($status > Commande::STATUS_DRAFT && $status < Commande::STATUS_CLOSED && $this->dol_object->getNbOfServicesLines() > 0) {
                    if ($user->rights->ficheinter->creer) {
                        $url = DOL_URL_ROOT . '/fichinter/card.php?action=create&amp;origin=' . $this->dol_object->element . '&amp;originid=' . $this->id . '&amp;socid=' . $client->id;
                        $buttons[] = array(
                            'label'   => $langs->trans('AddIntervention'),
                            'icon'    => 'plus-circle',
                            'onclick' => 'window.location = \'' . $url . '\''
                        );
                    } else {
                        $buttons[] = array(
                            'label'    => $langs->trans('AddIntervention'),
                            'icon'     => 'plus-circle',
                            'onclick'  => '',
                            'disabled' => 1,
                            'popover'  => 'Vous n\'avez pas la permission'
                        );
                    }
                }
            }
//
//            // Créer contrat
//            if ($conf->contrat->enabled && ($status == Commande::STATUS_VALIDATED || $status == Commande::STATUS_ACCEPTED || $status == Commande::STATUS_CLOSED)) {
//                $langs->load("contracts");
//
//                if ($user->rights->contrat->creer) {
//                    print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/contrat/card.php?action=create&amp;origin=' . $this->dol_object->element . '&amp;originid=' . $this->dol_object->id . '&amp;socid=' . $this->dol_object->socid . '">' . $langs->trans('AddContract') . '</a></div>';
//                }
//            }
//
//            // Expédier
//            $numshipping = 0;
//            if (!empty($conf->expedition->enabled)) {
//                $numshipping = $this->dol_object->nb_expedition();
//
//                if ($status > Commande::STATUS_DRAFT && $status < Commande::STATUS_CLOSED && ($this->dol_object->getNbOfProductsLines() > 0 || !empty($conf->global->STOCK_SUPPORTS_SERVICES))) {
//                    if (($conf->expedition_bon->enabled && $user->rights->expedition->creer) || ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer)) {
//                        if ($user->rights->expedition->creer) {
//                            print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/expedition/shipment.php?id=' . $this->dol_object->id . '">' . $langs->trans('CreateShipment') . '</a></div>';
//                        } else {
//                            print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('CreateShipment') . '</a></div>';
//                        }
//                    } else {
//                        $langs->load("errors");
//                        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")) . '">' . $langs->trans('CreateShipment') . '</a></div>';
//                    }
//                }
//            }
//
            // Réouvrir
            if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
                $buttons[] = array(
                    'label'   => 'Réouvrir',
                    'icon'    => 'undo',
                    'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                        'confirm_msg' => 'Veuillez confirmer la réouverture de ' . $this->getLabel('this')
                    ))
                );
            }
//
//            // Marquer comme expédier
//            if (($status == Commande::STATUS_VALIDATED || $status == Commande::STATUS_ACCEPTED) && $user->rights->commande->cloturer) {
//                print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->dol_object->id . '&amp;action=shipped">' . $langs->trans('ClassifyShipped') . '</a></div>';
//            }
//
            // Prendre en charge logistique:
            if ($this->isActionAllowed('processLogitique')) {
                if ($this->canSetAction('processLogitique')) {
                    $buttons[] = array(
                        'label'   => 'Prendre en charge logistique',
                        'icon'    => 'fas_truck-loading',
                        'onclick' => $this->getJsActionOnclick('processLogitique', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la prise en charge de la logistique pour cette commande'
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => $langs->trans('AddIntervention'),
                        'icon'     => 'plus-circle',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission'
                    );
                }
            }

            // Cloner
            if ($this->canSetAction('duplicate')) {
                $buttons[] = array(
                    'label'   => 'Cloner',
                    'icon'    => 'copy',
                    'onclick' => $this->getJsActionOnclick('duplicate', array(
                        'date_commande' => date('Y-m-d')
                            ), array(
                        'form_name' => 'duplicate'
                    ))
                );
            }

            // Annuler
            if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
                $buttons[] = array(
                    'label'   => 'Annuler',
                    'icon'    => 'times',
                    'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                        'confirm_msg' => $langs->trans('ConfirmCancelOrder', $ref)
                    ))
                );
            }

            // Forcer statut: 
            if ($this->isActionAllowed('forceStatus')) {
                if ($this->canSetAction('forceStatus')) {
                    $buttons[] = array(
                        'label'   => 'Forcer un statut',
                        'icon'    => 'far_check-square',
                        'onclick' => $this->getJsActionOnclick('forceStatus', array(), array(
                            'form_name' => 'force_status'
                        )),
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Forcer un statut',
                        'icon'     => 'far_check-square',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission'
                    );
                }
            }

            if ($user->admin) {
                $buttons[] = array(
                    'label'   => 'Ancienne version',
                    'icon'    => 'fas_file',
                    'onclick' => 'window.open(\'' . BimpObject::getInstanceUrl($this->dol_object) . '\')'
                );
            }
        }

        return $buttons;
    }

    public function getProductFournisseursPricesArray()
    {
        if (BimpTools::isSubmit('id_product')) {
            $id_product = (int) BimpTools::getValue('id_product', 0);
        } elseif (BimpTools::isSubmit('fields')) {
            $fields = BimpTools::getValue('fields', array());
            if (isset($fields['id_product'])) {
                $id_product = (int) $fields['id_product'];
            }
        }
        if ($id_product) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            return Bimp_Product::getFournisseursPriceArray($id_product);
        }

        return array(
            0 => ''
        );
    }

    public function getShipmentsArray()
    {
        $shipments = array();

        if ($this->isLoaded()) {
            $cs = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
            foreach ($cs->getList(array(
                'id_commande_client' => (int) $this->id,
                'status'             => 1
            )) as $row) {
                $shipments[(int) $row['id']] = 'Expédition n°' . $row['num_livraison'];
            }
        }

        return $shipments;
    }

    public function getInvoicesArray($editable_only = false, $include_empty = false, $empty_label = '')
    {
        if ($this->isLoaded()) {
            $cache_key = 'commande_' . $this->id . '_factures';

            if ($editable_only) {
                $cache_key .= '_editable';
            }

            if (!isset(self::$cache[$cache_key])) {
                $asso = new BimpAssociation($this, 'factures');

                foreach ($asso->getAssociatesList() as $id_facture) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                    if ($facture->isLoaded()) {
                        if ($editable_only) {
                            if ((int) $facture->getData('fk_statut') !== Facture::STATUS_DRAFT) {
                                continue;
                            }

                            self::$cache[$cache_key][(int) $id_facture] = $facture->getRef();
                        }
                    }
                }
            }

            return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
        }

        return array();
    }

    public function getClientFacture()
    {
        if ((int) $this->getData('id_client_facture')) {
            $client = $this->getChildObject('client_facture');
            if (BimpObject::objectLoaded($client)) {
                return $client;
            }
        }

        if ((int) $this->getData('fk_soc')) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client;
            }
        }

        return null;
    }

    public function getClientFactureContactsArray()
    {
        $id_client_facture = BimpTools::getValue('id_client_facture');

        if (is_null($id_client_facture)) {
            $client = $this->getClientFacture();
            if (BimpObject::objectLoaded($client)) {
                $id_client_facture = $client->id;
            }
        }

        if (!(int) $id_client_facture) {
            return array();
        }

        return self::getSocieteContactsArray($id_client_facture);
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $user = new User($this->db->db);

            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le ' . $this->displayData('date_creation');

            $user->fetch((int) $this->dol_object->user_author_id);
            $html .= ' par ' . $user->getNomUrl(1);
            $html .= '</div>';

            $status = (int) $this->getData('fk_statut');
            if ($status >= 1 && (int) $this->getData('fk_user_valid')) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée le ' . $this->displayData('date_valid');
                $user->fetch((int) $this->getData('fk_user_valid'));
                $html .= ' par ' . $user->getNomUrl(1);
                $html .= '</div>';
            }

            if ($status >= 3) {
                $id_user_cloture = (int) $this->db->getValue($this->getTable(), 'fk_user_cloture', '`rowid` = ' . (int) $this->id);
                if ($id_user_cloture) {
                    $user->fetch($id_user_cloture);
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Fermée le ' . $this->displayData('date_cloture');
                    $html .= ' par ' . $user->getNomUrl(1);
                    $html .= '</div>';
                }
            }

            if ((int) $this->getData('id_user_resp')) {
                $user_resp = $this->getChildObject('user_resp');
                if (BimpObject::objectLoaded($user_resp)) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Responsable logistique: ';
                    $html .= $user_resp->dol_object->getNomUrl(1);
                    $html .= '</div>';
                }
            }

            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $html .= '<div style="margin-top: 10px">';
                $html .= '<strong>Client: </strong>';
                $html .= BimpObject::getInstanceNomUrlWithIcons($client);
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ($this->isLoaded()) {
            if ((int) $this->getData('extra_status') > 0) {
                $html .= '<br/>';
                $html .= $this->displayData('extra_status');
            }
            if (in_array((int) $this->getData('fk_statut'), self::$logistique_active_status)) {
                $html .= '<br/>Logistique:';
                $html .= $this->displayData('logistique_status');
            }
            if ((int) $this->getData('shipment_status') > 0) {
                $html .= '<br/>';
                $html .= $this->displayData('shipment_status');
            }
            if ((int) $this->getData('invoice_status') > 0) {
                $html .= '<br/>';
                $html .= $this->displayData('invoice_status');
            }
        }

        return $html;
    }

    public function renderShipmentsInput()
    {
        $shipments = $this->getShipmentsArray();

        $id_shipment = (int) BimpTools::getPostFieldValue('id_shipment', 0);

        if (!$id_shipment) {
            foreach ($shipments as $id_s => $shipment) {
                $id_shipment = $id_s;
                break;
            }
        }

        return BimpInput::renderInput('select', 'id_shipment', $id_shipment, array(
                    'options' => $shipments
        ));
    }

    public function renderShipmentLinesListInput()
    {
        $lines = BimpTools::getPostFieldValue('shipment_lines_list', array());

        if (empty($lines)) {
            $lines = array();
            foreach ($this->getLines('not_text') as $line) {
                if (!$line->isShippable()) {
                    continue;
                }
                $lines[] = $line->id;
            }
        }

        if (is_array($lines)) {
            $lines = implode(',', $lines);
        }

        return '<input type="hidden" value="' . $lines . '" name="shipment_lines_list"/>';
    }

    public function renderShipmentLinesInputs()
    {
        $html = '';
        $id_shipment = (int) BimpTools::getPostFieldValue('id_shipment', 0);

        $lines = BimpTools::getPostFieldValue('shipment_lines_list', array());

        if (is_string($lines)) {
            $lines = explode(',', $lines);
        }

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>N° ligne</th>';
        $html .= '<th>Libellé</th>';
        $html .= '<th>Qté expédition</th>';
        $html .= '<th>Options</th>';
        $html .= '</tr>';

        $html .= '<tbody>';

        foreach ($lines as $id_line) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);
            $full_qty = (float) $line->getFullQty();

            if (!$line->isShippable()) {
                continue;
            }
            if ($line->isLoaded()) {
                $available_qty = (float) $line->getShipmentsQty() - (float) $line->getShippedQty();

                if ($id_shipment) {
                    $shipment_data = $line->getShipmentData($id_shipment);
                    if (isset($shipment_data['qty'])) {
                        $available_qty += (float) $shipment_data['qty'];
                    }
                }

                if ($full_qty >= 0) {
                    if ($available_qty <= 0) {
                        continue;
                    }
                } else {
                    if ($available_qty >= 0) {
                        continue;
                    }
                }

                $product = null;

                if ((int) $line->getData('type') === ObjectLine::LINE_PRODUCT) {
                    $product = $line->getProduct();
                }

                $html .= '<tr>';
                $html .= '<td>';
                $html .= $line->getData('position');
                $html .= '</td>';
                $html .= '<td>';
                $html .= $line->displayLineData('desc');
                $html .= '</td>';
                $html .= '<td>';
                $html .= $line->renderShipmentQtyInput($id_shipment);
                $html .= '</td>';
                $html .= '<td>';

                if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                    if (!$product->isSerialisable()) {
                        if ($full_qty > 0) {
                            $shipment_data = $line->getShipmentData($id_shipment);
                            if (isset($shipment_data['group'])) {
                                $value = (int) $shipment_data['group'];
                            } else {
                                $value = 0;
                            }
                            $html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_group_articles', $value);
                        }
                    } else {
                        $html .= '<div id="shipment_line_' . $line->id . '_equipments" class="shipment_line_equipments">';
                        $html .= $line->renderShipmentEquipmentsInput($id_shipment, null, 'line_' . $line->id . '_shipment_' . $id_shipment . '_qty');
                        $html .= '</div>';
                    }
                }

                $html .= '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</thead>';
        $html .= '</table>';

        return $html;
    }

    public function renderFacturesInput()
    {
        if ((int) BimpTools::getPostFieldValue('new_facture', 0)) {
            $id_facture = 0;
            $factures = array(
                0 => 'Nouvelle facture'
            );
        } else {
            $factures = $this->getInvoicesArray(true, true, 'Nouvelle facture');
            $id_facture = (int) BimpTools::getPostFieldValue('id_facture', 0);
        }

        return BimpInput::renderInput('select', 'id_facture', $id_facture, array(
                    'options' => $factures
        ));
    }

    public function renderFactureLinesListInput()
    {
        BimpObject::loadClass('bimpcommercial', 'ObjectLine');

        $lines = BimpTools::getPostFieldValue('facture_lines_list', array());

        if (empty($lines)) {
            $lines = array();
            foreach ($this->getChildrenObjects('lines', array(
                'type' => array(
                    'operator' => '<>',
                    'value'    => ObjectLine::LINE_TEXT
                )
            )) as $line) {
                $lines[] = $line->id;
            }
        }

        if (is_array($lines)) {
            $lines = implode(',', $lines);
        }

        return '<input type="hidden" value="' . $lines . '" name="facture_lines_list"/>';
    }

    public function renderFactureLinesInputs()
    {
        $html = '';
        $id_facture = (int) BimpTools::getPostFieldValue('id_facture', 0);

        $lines = BimpTools::getPostFieldValue('facture_lines_list', array());

        if (is_string($lines)) {
            $lines = explode(',', $lines);
        }

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>N° ligne</th>';
        $html .= '<th>Libellé</th>';
        $html .= '<th>PU HT</th>';
        $html .= '<th>Tx TVA</th>';
        $html .= '<th>Qté</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $has_lines = false;

        foreach ($lines as $id_line) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);

            if ($line->isLoaded()) {
                $max_qty = (float) $line->getFullQty() - (float) $line->getBilledQty();
                if ($id_facture) {
                    $facture_data = $line->getFactureData($id_facture);
                    if (isset($facture_data['qty'])) {
                        $max_qty += (float) $facture_data['qty'];
                    }
                }

                if (!$max_qty) {
                    continue;
                }

                $product = null;

                if ((int) $line->getData('type') === ObjectLine::LINE_PRODUCT) {
                    $product = $line->getProduct();
                }

                $has_lines = true;

                $html .= '<tr>';
                $html .= '<td>';
                $html .= $line->getData('position');
                $html .= '</td>';
                $html .= '<td>';
                $html .= $line->displayLineData('desc_light');
                $html .= '</td>';
                $html .= '<td>';
                $html .= $line->displayLineData('pu_ht');
                $html .= '</td>';
                $html .= '<td>';
                $html .= $line->displayLineData('tva_tx');
                $html .= '</td>';
                $html .= '<td>';
                $html .= $line->renderFactureQtyInput($id_facture);
                $html .= '</td>';
                $html .= '</tr>';

                if (BimpObject::objectLoaded($product)) {
                    if ($product->isSerialisable()) {
                        $html .= '<tr id="facture_line_' . $line->id . '_equipments" class="facture_line_equipments">';
                        $html .= '<td colspan="5">';
                        $html .= '<div style="padding-left: 45px;">';
                        $html .= $line->renderFactureEquipmentsInput($id_facture, null, 'line_' . $line->id . '_facture_' . $id_facture . '_qty');
                        $html .= '</div>';
                        $html .= '</td>';
                        $html .= '</tr>';
                    }
                }
            }
        }

        if (!$has_lines) {
            $html .= '<tr>';
            $html .= '<td colspan="5">';
            $html .= BimpRender::renderAlerts('Aucune ligne de commande disponible pour l\'ajout à une facture', 'warning');
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderCommandeFournisseursList()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la commande client absent');
        }

        $html = '';

        $line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
        $lines_list = $line_instance->getList(array(
            'id_obj' => (int) $this->id
                ), null, null, 'id', 'asc', 'array', array('id'));
        $lines = array();

        foreach ($lines_list as $item) {
            $lines[] = (int) $item['id'];
        }

        $fourn_line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
        $fourn_lines_list = $fourn_line_instance->getList(array(
            'linked_object_name' => 'commande_line',
            'linked_id_object'   => array(
                'in' => $lines
            )
                ), null, null, 'id', 'asc', 'array', array('id'));

        $fourn_lines = array();

        if (!is_null($fourn_lines_list)) {
            foreach ($fourn_lines_list as $item) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $item['id']);
                if (BimpObject::objectLoaded($line)) {
                    $commande_fourn = $line->getParentInstance();
                    if (BimpObject::objectLoaded($commande_fourn)) {
                        $id_fourn = (int) $commande_fourn->getData('fk_soc');
                        if ($id_fourn) {
                            if (!isset($fourn_lines[$id_fourn])) {
                                $fourn_lines[$id_fourn] = array();
                            }

                            if (!isset($fourn_lines[$id_fourn][$commande_fourn->id])) {
                                $fourn_lines[$id_fourn][$commande_fourn->id] = array();
                            }

                            $fourn_lines[$id_fourn][$commande_fourn->id][$line->id] = $line;
                        }
                    }
                }
            }
        }

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Commande fournisseur</th>';
        $html .= '<th>Désignation</th>';
        $html .= '<th>Prix d\'achat HT</th>';
        $html .= '<th>Tx TVA</th>';
        $html .= '<th>Qté</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        if (count($fourn_lines)) {
            foreach ($fourn_lines as $id_fourn => $commandes) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fourn);
                $html .= '<tr>';
                $html .= '<td colspan="6" style="padding: 20px 8px 8px 8px; border-bottom: 1px solid #787878">Fournisseur: ' . $soc->getNomUrl(1, false, true, 'default') . '</td>';
                $html .= '</tr>';

                foreach ($commandes as $id_commande_fourn => $comm_lines) {
                    $fl = true;
                    $comm_status = 0;
                    foreach ($comm_lines as $id_line => $line) {
                        $html .= '<tr>';

                        if ($fl) {
                            $commande = $line->getParentInstance();
                            $comm_status = (int) $commande->getData('fk_statut');

                            $html .= '<td rowspan="' . count($comm_lines) . '">';
                            $html .= $commande->getNomUrl(1, false, true, 'full') . '&nbsp;&nbsp;&nbsp;';
                            if ((int) $commande->isLogistiqueActive()) {
                                $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commandeFourn&id=' . $commande->id;
                                $html .= '<a href="' . $url . '" target="_blank">';
                                $html .= 'Logistique' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                                $html .= '</a>';
                            }

                            $html .= '<br/>' . $commande->displayData('fk_statut') . '  -  ' . $commande->displayData('invoice_status');
                            $html .= '</td>';
                            $fl = false;
                        }

                        $html .= '<td>' . $line->displayLineData('desc') . '</td>';
                        $html .= '<td>' . $line->displayLineData('pu_ht') . '</td>';
                        $html .= '<td>' . $line->displayLineData('tva_tx') . '</td>';
                        $html .= '<td>' . $line->displayQties() . '</td>';

                        $html .= '<td style="text-align: right">';

                        if ($comm_status > 0) {
                            $html .= BimpRender::renderRowButton('Réceptionner', 'fas_arrow-circle-down', $line->getJsLoadModalView('reception', 'Réceptionner'));
                        } else {
                            $comm_cli_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));
                            if (BimpObject::objectLoaded($comm_cli_line)) {
                                $html .= BimpRender::renderRowButton('Retirer de la commande fournisseur', 'fas_times-circle', $comm_cli_line->getJsActionOnclick('cancelCommandeFourn', array(
                                                    'id_commande_fourn_line' => $line->id
                                                        ), array(
                                                    'confirm_msg' => 'Veuillez confirmer le retrait de cet élément de la commande fournisseur'
                                )));
//                            $html .= BimpRender::renderRowButton('Editer', 'fas_times-circle', $comm_cli_line->getJsActionOnclick('editCommandeFourn', array(
//                                'id_commande_fourn' => $id_commande_fourn,
//                                'id_commande_fourn_line' => $line->id
//                            ), array(
//                                'form_name' => 'commande_fourn'
//                            )));
                            }
                        }

                        $html .= '</td>';

                        $html .= '</tr>';
                    }
                }
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="6" style="text-align: center">';
            $html .= BimpRender::renderAlerts('Aucune commande fournisseur associée à cette commande client', 'info');
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderEquipmentsToAddToShipmentCheckList()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la commande client absent');
        }

        $id_shipment = (int) BimpTools::getPostFieldValue('id_shipment', 0);

        if (!$id_shipment) {
            return BimpRender::renderAlerts('Aucune expédition sélectionnée', 'warning');
        }

        $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
        if (!BimpObject::objectLoaded($shipment)) {
            return BimpRender::renderAlerts('L\'expédition d\'ID ' . $id_shipment . ' n\'existe pas');
        }

        self::loadClass('bimpcommercial', 'ObjectLine');

        $selected_reservations = explode(',', BimpTools::getPostFieldValue('reservations', ''));

        $lines = $this->getChildrenObjects('lines', array(
            'type' => ObjectLine::LINE_PRODUCT
        ));

        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

        $lines_equipments = array();

        foreach ($lines as $line) {
            $product = $line->getProduct();
            $full_qty = (float) $line->getFullQty();
            if (BimpObject::objectLoaded($product)) {
                if ($product->isSerialisable()) {
                    $line_equipments = array(
                        'id_line'        => (int) $line->id,
                        'label'          => 'Ligne n°' . $line->getData('position') . ' - ' . $line->displayLineData('desc'),
                        'qty'            => 0,
                        'min'            => 0,
                        'max'            => 0,
                        'equipments_max' => 0,
                        'equipments_min' => 0,
                        'equipments'     => array(),
                        'selected'       => array()
                    );
                    $line_shipments = $line->getData('shipments');
                    $line_total_qty = (int) $line->getShipmentsQty();
                    $remain_qty = $line_total_qty;
                    foreach ($line_shipments as $id_s => $shipment_data) {
                        if ((int) $id_s === $id_shipment) {
                            $line_equipments['qty'] = (int) $shipment_data['qty'];
                            if (isset($shipment_data['equipments'])) {
                                if ($full_qty >= 0) {
                                    $line_equipments['min'] = count($shipment_data['equipments']);
                                } else {
                                    $line_equipments['max'] = (count($shipment_data['equipments']) * -1);
                                }
                            }
                        }
                        $remain_qty -= (int) $shipment_data['qty'];
                    }

                    if ($full_qty >= 0) {
                        $line_equipments['max'] = $line_equipments['qty'] + $remain_qty;
                    } else {
                        $line_equipments['min'] = $line_equipments['qty'] + $remain_qty;
                    }
                    $line_equipments['equipments_max'] = $line_equipments['qty'] - $line_equipments['min'];

                    if ($full_qty >= 0) {
                        // Equipements à expédier: 
                        $list = $reservation->getList(array(
                            'id_commande_client'      => (int) $this->id,
                            'id_commande_client_line' => (int) $line->id,
                            'status'                  => 200,
                            'id_equipment'            => array(
                                'operator' => '>',
                                'value'    => 0
                            )
                                ), null, null, 'id', 'asc', 'array', array('id', 'id_equipment'));
                        if (!is_null($list)) {
                            foreach ($list as $item) {
                                $id_shipment = (int) $line->getEquipmentIdShipment((int) $item['id_equipment']);
                                if (!$id_shipment) {
                                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $item['id_equipment']);
                                    if (BimpObject::objectLoaded($equipment)) {
                                        $line_equipments['equipments'][(int) $equipment->id] = $equipment->getData('serial');
                                        if (in_array($item['id'], $selected_reservations)) {
                                            $line_equipments['selected'][] = (int) $equipment->id;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Equipements retournés: 
                        $equipments_returned = $line->getData('equipments_returned');
                        foreach ($equipments_returned as $id_equipment => $id_entrepot) {
                            $id_shipment = (int) $line->getEquipmentIdShipment((int) $id_equipment);
                            if (!$id_shipment) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (BimpObject::objectLoaded($equipment)) {
                                    $line_equipments['equipments'][(int) $equipment->id] = $equipment->getData('serial');
                                }
                            }
                        }
                    }

                    if (count($line_equipments['equipments'])) {
                        $lines_equipments[] = $line_equipments;
                    }
                }
            }
        }

        $html = '';

        if (empty($lines_equipments)) {
            $html .= BimpRender::renderAlerts('Il n\'y a aucun équipement à attribuer à une expédition pour cette commande', 'warning');
        } else {
            foreach ($lines_equipments as $line_data) {
                $html .= '<div class="line_equipments_container" style="margin-bottom: 30px;" data-id_line="' . $line_data['id_line'] . '">';
                $html .= '<div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #282828; color: #282828">';
                $html .= $line_data['label'];
                $html .= '</div>';

                $html .= '<div style="margin: 15px 0"><span>Qté: assignées à l\'expédition:&nbsp;&nbsp;&nbsp;</span>';
                $html .= BimpInput::renderInput('qty', 'line_' . $line_data['id_line'] . '_qty', $line_data['qty'], array(
                            'data'      => array(
                                'data_type' => 'number',
                                'min'       => $line_data['min'],
                                'max'       => $line_data['max'],
                                'decimals'  => 0,
                                'unsigned'  => 1
                            ),
                            'min_label' => ((float) $line_data['min'] < 0 ? 1 : 0),
                            'max_label' => ((float) $line_data['max'] > 0 ? 1 : 0)
                ));
                $html .= '</div>';

                $html .= BimpInput::renderInput('check_list', 'equipments', $line_data['selected'], array(
                            'items'          => $line_data['equipments'],
                            'max'            => $line_data['equipments_max'],
                            'max_input_name' => 'line_' . $line_data['id_line'] . '_qty',
                            'max_input_abs'  => 1
                ));

                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderLogistiqueEquipmentsView()
    {
        $html = '';

        $html .= BimpRender::renderAlerts('En développement', 'warning');

        return $html;
    }

    public function renderLogistiqueButtons()
    {
        $html = '';

        // Nouvelle expédition:
        $expedition = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');

        $onclick = $expedition->getJsLoadModalForm('default', 'Nouvelle expédition', array(
            'fields' => array(
                'id_commande_client' => (int) $this->id,
                'id_entrepot'        => (int) $this->getData('entrepot')
            )
        ));

        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Nouvelle expédition';
        $html .= '</button>';

        // Nouvelle facture: 
        $client_facture = $this->getClientFacture();
        $onclick = $this->getJsActionOnclick('linesFactureQties', array(
            'new_facture'       => 1,
            'id_client_facture' => (int) (!is_null($client_facture) ? $client_facture->id : 0),
            'id_contact'        => (int) ($client_facture->id === (int) $this->getData('fk_soc') ? $this->dol_object->contactid : 0),
            'id_cond_reglement' => (int) $this->getData('fk_cond_reglement')
                ), array(
            'form_name'      => 'invoice',
            'on_form_submit' => 'function ($form, extra_data) { return onFactureFormSubmit($form, extra_data); }'
        ));

        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Nouvelle facture anticipée';
        $html .= '</button>';

        // Ajout ligne: 
        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');

        $onclick = $line->getJsLoadModalForm('line_forced', 'Ajout d\\\'une ligne de commande supplémentaire', array(
            'fields' => array(
                'id_obj' => (int) $this->id
            )
        ));
        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter une ligne de commande';
        $html .= '</button>';

        // Attribuer équipements:   
//        $onclick = $this->getJsLoadModalView('logistique_equipments', 'Attribuer des équipements');
//
//        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
//        $html .= BimpRender::renderIcon('fas_desktop', 'iconLeft') . 'Attribuer des équipements';
//        $html .= '</button>';
        // Statuts sélectionnés: 
        $items = array();

        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $this->id . ', 2);">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'A réserver</button>';
        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $this->id . ', 200);">' . BimpRender::renderIcon('fas_lock', 'iconLeft') . 'Réserver</button>';
        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $this->id . ', 0);">' . BimpRender::renderIcon('fas_undo', 'iconLeft') . 'Réinitialiser</button>';
        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsEquipmentsToShipment($(this), ' . $this->id . ');">' . BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Attribuer les équipements</button>';

        $html .= BimpRender::renderDropDownButton('Status sélectionnés', $items, array(
                    'icon'       => 'far_check-square',
                    'menu_right' => true
        ));

        return $html;
    }

    // Traitements divers:

    public function createReservations()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $lines = $this->getChildrenObjects('lines');

            foreach ($lines as $line) {
                $errors = array_merge($errors, $line->checkReservations());
            }
        } else {
            $errors[] = 'ID de la commande absent';
        }

        return $errors;
    }

    // Traitements factures: 

    public function createFacture(&$errors = array(), $id_client = null, $id_contact = null, $cond_reglement = null, $id_account = null, $public_note = '', $private_note = '', $remises = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande client absent ou invalide';
            return 0;
        }

        if (is_null($id_client)) {
            $id_client = (int) $this->getData('fk_soc');
        }

        if (!$id_client) {
            $errors[] = 'Aucun client enregistré pour cette commande';
            return 0;
        }

        if (is_null($cond_reglement) || !$cond_reglement) {
            $cond_reglement = (int) $this->dol_object->cond_reglement_id;
        }

        if ((is_null($id_client)) || !(int) $id_client) {
            $id_client = (int) $this->dol_object->socid;
        }

        if (is_null($id_contact) || !(int) $id_contact) {
            $id_contact = $this->dol_object->contactid;
        }

        // Création de la facture: 
        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

        $facture->dol_object->date = dol_now();
        $facture->dol_object->source = 0;
        $facture->dol_object->socid = $id_client;
        $facture->dol_object->fk_project = $this->dol_object->fk_project;
        $facture->dol_object->cond_reglement_id = $cond_reglement;
        $facture->dol_object->mode_reglement_id = $this->dol_object->mode_reglement_id;
        $facture->dol_object->availability_id = $this->dol_object->availability_id;
        $facture->dol_object->demand_reason_id = $this->dol_object->demand_reason_id;
        $facture->dol_object->date_livraison = $this->dol_object->date_livraison;
        $facture->dol_object->fk_delivery_address = $this->dol_object->fk_delivery_address;
        $facture->dol_object->contact_id = $id_contact;
        $facture->dol_object->ref_client = $this->dol_object->ref_client;
        $facture->dol_object->note_private = $private_note;
        $facture->dol_object->note_public = $public_note;

        $facture->dol_object->origin = $this->dol_object->element;
        $facture->dol_object->origin_id = $this->dol_object->id;

        $facture->dol_object->fk_account = (int) $id_account;

        // get extrafields from original line
//        $this->dol_object->fetch_optionals($this->id);

        foreach ($this->dol_object->array_options as $options_key => $value)
            $facture->dol_object->array_options[$options_key] = $value;

        // Possibility to add external linked objects with hooks
        $facture->dol_object->linked_objects[$facture->dol_object->origin] = $facture->dol_object->origin_id;
        if (!empty($this->dol_object->other_linked_objects) && is_array($this->dol_object->other_linked_objects)) {
            $facture->dol_object->linked_objects = array_merge($facture->dol_object->linked_objects, $this->dol_object->other_linked_objects);
        }

        $facture->dol_object->source = 0;

        global $user;

        $id_facture = $facture->dol_object->create($user);
        if ($id_facture <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de la création de la facture');
            return 0;
        }

        $asso = new BimpAssociation($this, 'factures');
        $asso->addObjectAssociation($id_facture);

        // Insertion des accomptes:
        if (count($remises)) {
            foreach ($remises as $id_remise) {
                $facture->dol_object->error = '';
                $facture->dol_object->errors = array();

                if ($facture->dol_object->insert_discount((int) $id_remise) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de l\'insertion de la remise client d\'ID ' . $id_remise);
                }
            }
        }

        return $id_facture;
    }

    public function checkFactureLinesData($lines_qties, $id_facture = null, $lines_equipments = array())
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande client absent';
            return $errors;
        }

        foreach ($lines_qties as $id_line => $qty) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);
            if (!BimpObject::objectLoaded($line)) {
                $errors[] = 'La ligne d\'ID ' . $id_line . ' n\'existe pas';
            } elseif ((int) $line->getData('id_obj') !== (int) $this->id) {
                $errors[] = 'La ligne d\'ID ' . $id_line . ' n\'appartient pas à cette commande';
            } else {
                $line_equipments = isset($lines_equipments[(int) $id_line]) ? $lines_equipments[(int) $id_line] : array();
                $line_errors = $line->checkFactureData($qty, $line_equipments, $id_facture);
                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                }
            }
        }

        return $errors;
    }

    public function addLinesToFacture($id_facture, $lines_qties = null, $lines_equipments = array(), $check_data = true)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande client absent ou invalide';
            return $errors;
        }

        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
        $facture->checkLines();

        if (!BimpObject::objectLoaded($facture)) {
            $errors[] = 'La facture d\'ID ' . $id_facture . ' n\'existe pas';
            return $errors;
        }

        if ($check_data) {
            $errors = $this->checkFactureLinesData($lines_qties, $id_facture, $lines_equipments);
            if (count($errors)) {
                return $errors;
            }
        }

        foreach ($lines_qties as $id_line => $line_qty) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);

            if (BimpObject::objectLoaded($line)) {
                $product = $line->getProduct();
                $fac_line_errors = array();

                $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                            'id_obj'             => (int) $facture->id,
                            'linked_object_name' => 'commande_line',
                            'linked_id_object'   => (int) $line->id
                                ), true);

                if (!BimpObject::objectLoaded($fac_line)) {
                    if (!(float) $line_qty) {
                        continue;
                    }

                    // Création de la ligne de facture: 
                    $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                    $fac_line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => $line->getData('type'),
                        'remisable'          => $line->getData('remisable'),
                        'force_qty_1'        => $line->getData('force_qty_1'),
                        'linked_id_object'   => (int) $line->id,
                        'linked_object_name' => 'commande_line',
                    ));

                    $fac_line->qty = (float) $line_qty;
                    $fac_line->desc = $line->desc;
                    $fac_line->id_product = $line->id_product;
                    $fac_line->pu_ht = $line->pu_ht;
                    $fac_line->tva_tx = $line->tva_tx;
                    $fac_line->pa_ht = $line->pa_ht;
                    $fac_line->id_fourn_price = $line->id_fourn_price;
                    $fac_line->date_from = $line->date_from;
                    $fac_line->date_to = $line->date_to;
                    $fac_line->id_remise_except = $line->id_remise_except;

                    $fac_line_warnings = array();
                    $fac_line_errors = $fac_line->create($fac_line_warnings);
                    $fac_line_errors = array_merge($fac_line_errors, $fac_line_warnings);

                    if (count($fac_line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la création de la ligne de facture depuis la ligne de commande n°' . $line->getData('position') . ' (ID ' . $line->id . ')');
                    } else {
                        // Création des remises: 
                        $remises = $line->getRemises();
                        foreach ($remises as $remise) {
                            $new_remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                            $new_remise->validateArray(array(
                                'id_object_line' => (int) $fac_line->id,
                                'object_type'    => 'facture',
                                'label'          => $remise->getData('label'),
                                'type'           => $remise->getData('type'),
                                'percent'        => $remise->getData('percent'),
                                'montant'        => $remise->getData('montant'),
                                'per_unit'       => $remise->getData('per_unit'),
                            ));

                            $remise_warnings = array();
                            $remise_errors = $new_remise->create($remise_warnings);

                            $remise_errors = array_merge($remise_errors, $remise_warnings);

                            if (count($remise_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création d\'une remise pour la ligne de facture d\'ID ' . $fac_line->id);
                            }
                        }

                        $fac_line->set('editable', 0);
                        $fac_line->set('deletable', 0);
                        $fac_line_warnings = array();
                        $fac_line->update($fac_line_warnings, true);
                    }
                } else {
                    $fac_line->qty = (float) $line_qty;
                    $fac_line_errors = $fac_line->setEquipments(array());

                    if (count($fac_line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la mise à jour de la liste des équipements pour la ligne de facture n°' . $fac_line->getData('position'));
                    } else {
                        $fac_line_warnings = array();
                        $fac_line_errors = $fac_line->update($fac_line_warnings, true);
                        $fac_line_errors = array_merge($fac_line_errors, $fac_line_warnings);

                        if (count($fac_line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la mise à jour de la ligne de facture depuis la ligne de commande n°' . $line->getData('position') . ' (ID ' . $line->id . ')');
                        }
                    }
                }

                if (!count($fac_line_errors)) {
                    // Assignation des équipements à la ligne de facture: 
                    $equipments_set = array();
                    if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                        $line_equipments = array();

                        if (isset($lines_equipments[(int) $id_line])) {
                            foreach ($lines_equipments[(int) $id_line] as $id_equipment) {
                                $line_equipments[] = array(
                                    'id_equipment' => (int) $id_equipment
                                );
                            }
                        }

                        $eq_errors = $fac_line->setEquipments($line_equipments, $equipments_set);
                        if (count($eq_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Ligne n°' . $line->getData('position'));
                        }
                    }

                    // Enregistrement des quantités facturées pour la ligne de commande: 
                    $line_warnings = array();

                    $line_errors = $line->setFactureData((int) $facture->id, $line_qty, $equipments_set, $line_warnings, false);
                    $line_errors = array_merge($line_errors, $line_warnings);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'enregistrement des quantités facturées pour la ligne n°' . $line->getData('position') . ' (ID: ' . $line->id . ')');
                    }
                }
            } else {
                $errors[] = 'La ligne de commande client d\'ID ' . $id_line . ' n\'existe pas';
            }
        }

        return $errors;
    }

    // Checks status: 

    public function checkLogistiqueStatus()
    {
        if ($this->isLoaded()) {
            $status_forced = $this->getData('status_forced');

            if (isset($status_forced['logistique']) && (int) $status_forced['logistique']) {
                return;
            }

            if (!in_array((int) $this->getData('logistique_status'), array(0, 4, 5))) {
                $lines = $this->getLines('not_text');

                $hasToProcess = false;
                $isCompleted = true;
                foreach ($lines as $line) {
                    $qties = $line->getReservedQties();

                    if (isset($qties['status'][0]) && (float) $qties['status'][0] > 0) {
                        $isCompleted = false;
                        $hasToProcess = true;
                        break;
                    }

                    if (isset($qties['not_reserved']) && (float) $qties['not_reserved'] > 0) {
                        $isCompleted = false;
                    }
                }

                if ($hasToProcess) {
                    $new_status = 1;
                } elseif (!$isCompleted) {
                    $new_status = 2;
                } else {
                    $new_status = 3;
                }

                if ($new_status !== (int) $this->getInitData('logistique_status')) {
                    $this->updateField('logistique_status', $new_status);
                }
            }
        }
    }

    public function checkShipmentStatus()
    {
        if ($this->isLoaded()) {
            $status_forced = $this->getData('status_forced');

            if (isset($status_forced['shipment']) && (int) $status_forced['shipment']) {
                return;
            }

            $lines = $this->getLines('not_text');

            $hasShipment = 0;
            $isFullyShipped = 1;

            $current_status = (int) $this->getInitData('shipment_status');

            foreach ($lines as $line) {
                $shipped_qty = (float) $line->getShippedQty(null, true);
                if ($shipped_qty) {
                    $hasShipment = 1;
                }

                if (abs($shipped_qty) < abs((float) $line->getShipmentsQty())) {
                    $isFullyShipped = 0;
                }
            }

            if ($isFullyShipped) {
                $new_status = 2;
            } elseif ($hasShipment) {
                $new_status = 1;
            } else {
                $new_status = 0;
            }

            if ($new_status !== $current_status) {
                $this->updateField('shipment_status', $new_status);
            }
        }
    }

    public function checkInvoiceStatus()
    {
        if ($this->isLoaded()) {
            $status_forced = $this->getData('status_forced');

            if (isset($status_forced['invoice']) && (int) $status_forced['invoice']) {
                return;
            }

            $lines = $this->getLines('not_text');

            $hasInvoice = 0;
            $isFullyInvoiced = 1;

            $current_status = (int) $this->getInitData('invoice_status');

            foreach ($lines as $line) {
                $billed_qty = (float) $line->getBilledQty(null, false);
                if ($billed_qty) {
                    $hasInvoice = 1;
                }

                if (abs($billed_qty) < abs((float) $line->getFullQty())) {
                    $isFullyInvoiced = 0;
                }
            }

            if ($isFullyInvoiced) {
                $new_status = 2;
            } elseif ($hasInvoice) {
                $new_status = 1;
            } else {
                $new_status = 0;
            }

            if ($new_status !== $current_status) {
                $this->updateField('invoice_status', $new_status);
            }
        }
    }

    // Gestion des lignes:

    public function setRevalorisation()
    {
        if (!(int) $this->getData('revalorisation')) {
            $this->updateField('revalorisation', 1);
        }
    }

    // Actions:

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel('')) . ' validé';
        if ($this->isLabelFemale()) {
            $success .= 'e';
        }
        $success .= ' avec succès';
        $success_callback = 'bimp_reloadPage();';

        global $conf, $langs, $user;

        $result = $this->dol_object->valid($user, (int) $this->getData('entrepot'));

        if ($result > 0) {
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $this->fetch($this->id);
                $this->dol_object->generateDocument($this->getModelPdf(), $langs);
            }
        } else {
            $comm_errors = BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings);

            if (!count($comm_errors)) {
                if (!(int) $this->getData('validComm')) {
                    $comm_errors[] = 'Commande en attente de validation commerciale';
                }
                if (!(int) $this->getData('validFin')) {
                    $comm_errors[] = 'Commande en attente de validation financière';
                }
            }

            if (!count($comm_errors)) {
                $errors[] = 'Echec de la validation pour une raison inconnue';
            } else {
                $errors[] = BimpTools::getMsgFromArray($comm_errors, 'Des erreurs sont survenues lors de la validation ' . $this->getLabel('of_the'));
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commande annulée avec succès';
        $success_callback = 'bimp_reloadPage();';

        if ($this->dol_object->cancel() < 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Echec de l\'annulation de la commande');
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture ' . $this->getLabel('of_the') . ' effectuée avec succès';

        global $user;

        if ($this->dol_object->set_reopen($user) < 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la réouverture ' . $this->getLabel('of_the'));
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionLinesShipmentQties($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_shipment = (isset($data['id_shipment']) ? (int) $data['id_shipment'] : 0);
        $lines = (isset($data['lines']) ? $data['lines'] : array());

        if (!$id_shipment) {
            $errors[] = 'ID de l\'expédition absent';
        }

        if (!is_array($lines) || empty($lines)) {
            $errors[] = 'Aucune ligne de commande spécifiée';
        }

        if (!count($errors)) {
            $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
            if (!BimpObject::objectLoaded($shipment)) {
                $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' n\'existe pas';
            } else {
                if ((int) $shipment->getData('status') !== BL_CommandeShipment::BLCS_BROUILLON) {
                    $errors[] = 'L\'expédition sélectionnée ne peut pas être modifiée car elle n\'a plus le statut "brouillon"';
                } elseif ((int) $shipment->getData('id_commande_client') !== (int) $this->id) {
                    $errors[] = 'L\'expédition sélectionnée n\'appartient pas à cette commande';
                } else {
                    $success = 'Ajouts à l\'expédition n°' . $shipment->getData('num_livraison') . ' effectués avec succès';
                    foreach ($lines as $line_data) {
                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line_data['id_line']);
                        if (!BimpObject::objectLoaded($line)) {
                            $errors[] = 'La ligne de commande d\'ID ' . $line_data['id_line'] . ' n\'existe pas';
                        } elseif ($line->isShippable()) {
                            $line_warnings = array();
                            $line_errors = $line->setShipmentData($shipment, $line_data, $line_warnings);

                            if (count($line_warnings)) {
                                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n° ' . $line->getData('position') . ' (ID ' . $line->id . ')');
                            }

                            if (count($line_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n° ' . $line->getData('position') . ' (ID ' . $line->id . ')');
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionLinesFactureQties($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if (!isset($data['id_facture'])) {
            $errors[] = 'Aucune facture spécifiée';
        } elseif (!isset($data['lines']) || empty($data['lines'])) {
            $errors[] = 'Aucune quantité spécifiée';
        } else {
            $lines_qties = array();
            $lines_equipments = array();

            foreach ($data['lines']as $line_data) {
                $lines_qties[(int) $line_data['id_line']] = (float) $line_data['qty'];

                if (isset($line_data['equipments'])) {
                    $lines_equipments[(int) $line_data['id_line']] = $line_data['equipments'];
                }
            }

            // Vérification des quantités: 
            $id_facture = (int) $data['id_facture'] ? (int) $data['id_facture'] : null;
            $errors = $this->checkFactureLinesData($lines_qties, $id_facture, $lines_equipments);

            if (!count($errors)) {
                if ($id_facture) {
                    $success = 'Ajout des unités à la facture effectué avec succès';
                    $errors = $this->addLinesToFacture($id_facture, $lines_qties, $lines_equipments, false);
                } else {
                    $success = 'Création de la facture effectuée avec succès';
                    $id_client = isset($data['id_client_facture']) ? $data['id_client_facture'] : null;
                    $id_contact = isset($data['id_contact']) ? $data['id_contact'] : null;
                    $id_cond_reglement = isset($data['id_cond_reglement']) ? $data['id_cond_reglement'] : null;
                    $id_account = isset($data['id_account']) ? (int) $data['id_account'] : null;
                    $remises = isset($data['id_remises_list']) ? $data['id_remises_list'] : array();
                    $note_public = isset($data['note_public']) ? $data['note_public'] : '';
                    $note_private = isset($data['note_private']) ? $data['note_private'] : '';

                    $id_facture = $this->createFacture($errors, $id_client, $id_contact, $id_cond_reglement, $id_account, $note_public, $note_private, $remises);

                    // Ajout des lignes à la facture: 
                    if ($id_facture && !count($errors)) {
                        $lines_errors = $this->addLinesToFacture($id_facture, $lines_qties, $lines_equipments, false);

                        if (count($lines_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($lines_errors, 'Erreurs lors de l\'ajout des lignes à la facture');
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetLinesReservationsStatus($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $reservations = isset($data['reservations']) ? $data['reservations'] : array();
        $status = isset($data['status']) ? (int) $data['status'] : null;

        if (!is_array($reservations) || empty($reservations)) {
            $errors[] = 'Aucun élément sélectionné';
        } elseif (is_null($status)) {
            $errors[] = 'Nouveau statut non spécifié';
        } else {
            $n_success = 0;
            foreach ($reservations as $id_reservation) {
                $reservation = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', (int) $id_reservation);
                if (!BimpObject::objectLoaded($reservation)) {
                    $warnings[] = 'La réservation d\'ID ' . $id_reservation . ' n\'existe pas';
                } else {
                    $line = $reservation->getChildObject('commande_client_line');

                    if (!BimpObject::objectLoaded($line)) {
                        $warnings[] = 'La réservation d\'ID ' . $id_reservation . ' n\'est pas associée à une ligne de commande valide';
                    } else {
                        if ((int) $reservation->getData('status') >= 300) {
                            $title = 'Ligne n° ' . $line->getData('position') . ': ';
                            $title .= 'statut "' . BR_Reservation::$status_list[(int) $reservation->getData('status')]['label'] . '"';
                            $warnings[] = $title . ': ce statut n\'est plus modifiable';
                        } else {
                            $res_errors = array();

                            if (!count($res_errors)) {
                                $res_errors = $reservation->setNewStatus($status);
                            }

                            if (count($res_errors)) {
                                $title = 'Ligne n° ' . $line->getData('position') . ': ';
                                $title .= 'statut "' . BR_Reservation::$status_list[(int) $reservation->getData('status')]['label'] . '"';
                                $warnings[] = BimpTools::getMsgFromArray($res_errors, $title);
                            } else {
                                $n_success++;
                            }
                        }
                    }
                }
            }
            if ($n_success > 0) {
                if ($n_success === count($reservations)) {
                    $success = 'Tous les nouveaux statuts ont été enregistrés avec succès';
                } else {
                    if ($n_success > 1) {
                        $success = $n_success . ' statuts ont été mis à jour avec succès';
                    } else {
                        $success = '1 statut a été mis à jour avec succès';
                    }
                }
            } else {
                $errors = $warnings;
                $warnings = array();
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddEquipmentsToShipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_shipment = isset($data['id_shipment']) ? (int) $data['id_shipment'] : 0;

        if (!$id_shipment) {
            $errors[] = 'Aucune expédition sélectionnée';
        } else {
            $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
            if (!$shipment->isLoaded()) {
                $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' n\'existe pas';
            } else {
                if ((int) $shipment->getData('status') !== BL_CommandeShipment::BLCS_BROUILLON) {
                    $errors[] = 'L\'expédition n°' . $shipment->getDatat('num_livraison') . ' n\'a pas le statut "brouillon"';
                } elseif (!isset($data['lines']) || !is_array($data['lines']) || empty($data['lines'])) {
                    $errors[] = 'Aucun équipement sélectionné';
                } else {
                    $check = false;
                    foreach ($data['lines'] as $line_data) {
                        if (isset($line_data['equipments']) && is_array($line_data['equipments']) && !empty($line_data['equipments'])) {
                            $check = true;
                            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line_data['id_line']);
                            if (!$line->isLoaded()) {
                                $warnings[] = 'La ligne de commande d\'ID ' . $line_data['id_line'] . ' n\'existe pas';
                            } else {
                                $line_errors = $line->addEquipmentsToShipment($id_shipment, $line_data['equipments'], (int) $line_data['qty']);
                                if (count($line_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs pour la ligne n°' . $line->getData('position'));
                                } else {
                                    $success .= ($success ? '<br/>' : '') . 'Ligne n°' . $line->getData('position') . ': équipements assignés avec succès';
                                }
                            }
                        }
                    }
                    if (!$check) {
                        $errors[] = 'Aucun équipement sélectionné';
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionProcessLogitique($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Prise en charge de la logistique effectuée avec succès';

        global $user;

        if (!BimpObject::objectLoaded($user)) {
            $errors[] = 'Aucun utilisateur connecté';
        } else {
            $this->set('id_user_resp', (int) $user->id);
            $this->set('logistique_status', 1);

            $errors = $this->update($warnings);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionForceStatus($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Statut forcé enregistré avec succès';

        if (!isset($data['status_type']) || !(string) $data['status_type']) {
            $errors[] = 'Type de statut absent';
        }

        if (!count($errors)) {
            $status_forced = $this->getData('status_forced');

            switch ($data['status_type']) {
                case 'logistique':
                    if (!isset($data['logistique_status'])) {
                        $errors[] = 'Statut logistique absent';
                    } elseif (!in_array((int) $data['logistique_status'], array(-1, 0, 1, 2, 3, 4, 5))) {
                        $errors[] = 'Statut logistique invalide';
                    } else {
                        if ((int) $data['logistique_status'] === -1) {
                            if (isset($status_forced['logistique'])) {
                                unset($status_forced['logistique']);
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                            if (!count($errors)) {
                                $this->checkLogistiqueStatus();
                            }
                        } else {
                            $status_forced['logistique'] = 1;
                            $sub_errors = $this->updateField('logistique_status', (int) $data['logistique_status']);
                            if (count($sub_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut logistique de la commande');
                            } else {
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                        }
                    }
                    break;

                case 'shipment':
                    if (!isset($data['shipment_status'])) {
                        $errors[] = 'Statut expédition absent';
                    } elseif (!in_array((int) $data['shipment_status'], array(-1, 0, 1, 2))) {
                        $errors[] = 'Statut expédition invalide';
                    } else {
                        if ((int) $data['shipment_status'] === -1) {
                            if (isset($status_forced['shipment'])) {
                                unset($status_forced['shipment']);
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                            if (!count($errors)) {
                                $this->checkShipmentStatus();
                            }
                        } else {
                            $status_forced['shipment'] = 1;
                            $sub_errors = $this->updateField('shipment_status', (int) $data['shipment_status']);
                            if (count($sub_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut expédition de la commande');
                            } else {
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                        }
                    }
                    break;

                case 'invoice':
                    if (!isset($data['invoice_status'])) {
                        $errors[] = 'Statut facturation absent';
                    } elseif (!in_array((int) $data['invoice_status'], array(-1, 0, 1, 2))) {
                        $errors[] = 'Statut facturation invalide';
                    } else {
                        if ((int) $data['invoice_status'] === -1) {
                            if (isset($status_forced['invoice'])) {
                                unset($status_forced['invoice']);
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                            if (!count($errors)) {
                                $this->checkInvoiceStatus();
                            }
                        } else {
                            $status_forced['invoice'] = 1;
                            $sub_errors = $this->updateField('invoice_status', (int) $data['invoice_status']);
                            if (count($sub_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut facturation de la commande');
                            } else {
                                $errors = $this->updateField('status_forced', $status_forced);
                            }
                        }
                    }
                    break;
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides BimpComm:

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $new_data['id_facture'] = 0;
        $new_data['validFin'] = 0;
        $new_data['validComm'] = 0;
        $new_data['date_creation'] = date('Y-m-d H:i:s');
        $new_data['date_valid'] = null;
        $new_data['date_cloture'] = null;
        $new_data['fk_user_author'] = 0;
        $new_data['fk_user_modif'] = 0;
        $new_data['fk_user_valid'] = 0;
        $new_data['fk_user_cloture'] = 0;
        $new_data['shipment_status'] = 0;
        $new_data['invoice_status'] = 0;
        $new_data['logistique_status'] = 0;
        $new_data['id_user_resp'] = 0;

        return parent::duplicate($new_data, $warnings, $force_create);
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        // Fermeture de la propale si nécessaire
        if ((int) BimpTools::getValue('close_propal', 0)) {
            $origin = BimpTools::getValue('origin', '');
            $origin_id = (int) BimpTools::getValue('origin_id', 0);

            if ($origin === 'propal') {
                if (!$origin_id) {
                    $errors[] = 'ID de la proposition commerciale d\'origine absent';
                } else {
                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $origin_id);

                    if (!BimpObject::objectLoaded($propal)) {
                        $errors[] = 'La proposition commeciale d\'origine d\'ID ' . $origin_id . ' n\'existe pas';
                    } else {
                        $close_errors = array();
                        if (!$propal->isActionAllowed('close', $close_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($close_errors, 'La proposition commerciale ne peut pas être signée');
                        } elseif (!$propal->canSetAction('close')) {
                            $errors[] = 'Vous n\'avez pas la permission de signer la proposition commerciale';
                        } else {
                            $success = '';
                            $result = $propal->actionClose(array(
                                'new_status' => 2
                                    ), $success);
                            if (count($result['errors'])) {
                                $errors = $result['errors'];
                            }
                        }
                    }
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $this->set('date_creation', date('Y-m-d H:i:s'));

        return parent::create($warnings, $force_create);
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id_commande = (int) $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            // Suppression des réservations: 
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

            $reservations = $reservation->getListObjects(array(
                'id_commande_client' => $id_commande
            ));

            foreach ($reservations as $res) {
                $res_warnings = array();
                $res_errors = $res->delete($res_warnings, true);
                $res_errors = array_merge($res_errors, $res_warnings);

                if (count($res_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Erreur lors de la suppression d\'une réservation');
                }
            }
        }

        return $errors;
    }
}

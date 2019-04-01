<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

class Bimp_Commande extends BimpComm
{

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

    public function canEdit()
    {
        return $this->canCreate();
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
                return (int) $this->canCreate();
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
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters booléens:

    public function isFullyShipped()
    {
        if ($this->isLoaded()) {
            $total_qty = 0;

            $orderLines = $this->getChildrenObjects('order_lines');

            foreach ($orderLines as $line) {
                $total_qty += (int) $line->getData('qty');
            }

            $shipment_instance = BimpObject::getInstance('bimpreservation', 'BR_CommandeShipment');
            $rs = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');
            $ss = BimpObject::getInstance('bimpreservation', 'BR_ServiceShipment');
            foreach ($shipment_instance->getList(array(
                'id_commande_client' => (int) $this->id,
                'status'             => 2
                    ), null, null, 'id', 'asc', 'object', array('id')) as $shipment) {
                foreach ($rs->getList(array(
                    'id_commande_client' => (int) $this->id,
                    'id_shipment'        => (int) $shipment->id
                        ), null, null, 'id', 'asc', 'object', array('qty')) as $item) {
                    $total_qty -= $item->qty;
                }
                foreach ($ss->getList(array(
                    'id_commande_client' => (int) $this->id,
                    'id_shipment'        => (int) $shipment->id
                        ), null, null, 'id', 'asc', 'object', array('qty')) as $item) {
                    $total_qty -= $item->qty;
                }
            }

            if ($total_qty <= 0) {
                return 1;
            }
        }

        return 0;
    }

    public function isFullyInvoiced()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_facture')) {
                return 1;
            }

            $total_qty = 0;

            $orderLines = $this->getChildrenObjects('order_lines');

            foreach ($orderLines as $line) {
                $total_qty += (int) $line->getData('qty');
            }

            $shipment_instance = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
            $rs = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');
            $ss = BimpObject::getInstance('bimpreservation', 'BR_ServiceShipment');

            foreach ($shipment_instance->getList(array(
                'id_commande_client' => (int) $this->id,
                'id_facture'         => array(
                    'operator' => '>',
                    'value'    => 0
                )
                    ), null, null, 'id', 'asc', 'object', array('id')) as $shipment) {
                foreach ($rs->getList(array(
                    'id_commande_client' => (int) $this->id,
                    'id_shipment'        => (int) $shipment->id
                        ), null, null, 'id', 'asc', 'object', array('qty')) as $item) {
                    $total_qty -= $item->qty;
                }
                foreach ($ss->getList(array(
                    'id_commande_client' => (int) $this->id,
                    'id_shipment'        => (int) $shipment->id
                        ), null, null, 'id', 'asc', 'object', array('qty')) as $item) {
                    $total_qty -= $item->qty;
                }
            }

            if ($total_qty <= 0) {
                return 1;
            }
        }

        return 0;
    }

    // Getters: 

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFPropales')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
        }

        return ModelePDFCommandes::liste_modeles($this->db->db);
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->commande->dir_output;
    }

    public function getListFilters()
    {
        return array();
    }

    public function getActionsButtons()
    {
        global $conf, $langs, $user;

        $buttons = array();

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
//            if ($status == Commande::STATUS_VALIDATED && $this->canCreate()) {
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
            // Cloner
            if ($this->canSetAction('duplicate')) {
                $buttons[] = array(
                    'label'   => 'Cloner',
                    'icon'    => 'copy',
                    'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
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
                            if (!$facture->isEditable()) {
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

            if ($status >= 3 && (int) $this->getData('fk_user_cloture')) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Fermée le ' . $this->displayData('date_cloture');
                $user->fetch((int) $this->getData('fk_user_cloture'));
                $html .= ' par ' . $user->getNomUrl(1);
                $html .= '</div>';
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
            foreach ($this->getChildrenObjects('lines', array(
                'type' => ObjectLine::LINE_TEXT
            )) as $line) {
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
        $html .= '<th>Grouper les articles</th>';
        $html .= '</tr>';

        $html .= '<tbody>';

        foreach ($lines as $id_line) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);

            if ($line->isLoaded()) {
                $available_qty = (float) $line->qty - (float) $line->getShippedQty();

                if ($id_shipment) {
                    $shipment_data = $line->getShipmentData($id_shipment);
                    if (isset($shipment_data['qty'])) {
                        $available_qty += (float) $shipment_data['qty'];
                    }
                }

                if ($available_qty <= 0) {
                    continue;
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

                if (BimpObject::objectLoaded($product)) {
                    if ((int) $product->getData('fk_product_type') === 0 && !$product->isSerialisable()) {
                        $line_shipments = $line->getData('shipments');
                        if (isset($line_shipments[$id_shipment]['group_articles'])) {
                            $value = (int) $line_shipments[$id_shipment]['group_articles'];
                        } else {
                            $value = 0;
                        }
                        $html .= BimpInput::renderInput('toggle', 'group_articles', $value);
                    }
                }

                $html .= '</td>';
                $html .= '</tr>';

                if (BimpObject::objectLoaded($product)) {
                    if ($product->isSerialisable()) {
                        $html .= '<tr id="shipment_line_' . $line->id . '_equipments" class="shipment_line_equipments">';
                        $html .= '<td colspan="4">';
                        $html .= '<div style="padding-left: 45px">';
                        $html .= $line->renderShipmentEquipmentsInput($id_shipment, null, 'line_' . $line->id . '_shipment_' . $id_shipment . '_qty');
                        $html .= '</div>';
                        $html .= '</td>';
                        $html .= '</tr>';
                    }
                }
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
                $html .= $line->displayLineData('desc');
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
                            $html .= $commande->getNomUrl(1, false, true, 'full') . '&nbsp;&nbsp;&nbsp;' . $commande->displayData('fk_statut');
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
                                $html .= BimpRender::renderRowButton('Annuler', 'fas_times-circle', $comm_cli_line->getJsActionOnclick('cancelCommandeFourn', array(
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
            if (BimpObject::objectLoaded($product)) {
                if ($product->isSerialisable()) {
                    $line_equipments = array(
                        'id_line'        => (int) $line->id,
                        'label'          => 'Ligne n°' . $line->getData('position') . ' - ' . $line->displayLineData('desc'),
                        'qty'            => 0,
                        'min'            => 0,
                        'max'            => 0,
                        'equipments_max' => 0,
                        'equipments'     => array(),
                        'selected'       => array()
                    );
                    $line_shipments = $line->getData('shipments');
                    $line_total_qty = (int) $line->qty;
                    $remain_qty = $line_total_qty;
                    foreach ($line_shipments as $id_s => $shipment_data) {
                        if ((int) $id_s === $id_shipment) {
                            $line_equipments['qty'] = (int) $shipment_data['qty'];
                            if (isset($shipment_data['equipments'])) {
                                $line_equipments['min'] = count($shipment_data['equipments']);
                            }
                        }

                        $remain_qty -= (int) $shipment_data['qty'];
                    }

                    $line_equipments['max'] = $line_equipments['qty'] + $remain_qty;
                    $line_equipments['equipments_max'] = $line_equipments['qty'] - $line_equipments['min'];

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
                                    $line_equipments['equipments'][(int) $equipment->id] = 'Equipement ' . $equipment->id . ' - NS: ' . $equipment->getData('serial');
                                    if (in_array($item['id'], $selected_reservations)) {
                                        $line_equipments['selected'][] = (int) $equipment->id;
                                    }
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
                            'max_label' => 1
                ));
                $html .= '</div>';

                $html .= BimpRender::renderAlerts('Vous pouvez sélectionner jusqu\'à <span class="max_nb_equipments">' . $line_data['equipments_max'] . '</span> équipement(s)', 'info');

                $remain_qty = (int) $line_data['equipments_max'] - (int) count($line_data['selected']);

                $html .= '<div class="equipments_selection_infos">';
                if ($remain_qty > 0) {
                    $html .= BimpRender::renderAlerts('Il reste ' . $remain_qty . ' équipement(s) à sélectionner', 'warning');
                } elseif ($remain_qty < 0) {
                    $html .= BimpRender::renderAlerts('Vous devez désélectionner ' . (-$remain_qty) . ' équipement(s)', 'danger');
                } else {
                    $html .= BimpRender::renderAlerts('Il ne reste plus aucun équipement à sélectionner', 'success');
                }
                $html .= '</div>';

                $html .= BimpInput::renderInput('check_list', 'equipments', $line_data['selected'], array(
                            'items' => $line_data['equipments']
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

        $onclick = $this->getJsActionOnclick('linesFactureQties', array(
            'new_facture'       => 1,
            'id_client'         => (int) $this->getData('fk_soc'),
            'id_contact'        => (int) $this->dol_object->contactid,
            'id_cond_reglement' => (int) $this->getData('fk_cond_reglement')
                ), array(
            'form_name'      => 'invoice',
            'on_form_submit' => 'function ($form, extra_data) { return onFactureFormSubmit($form, extra_data); }'
        ));

        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Nouvelle facture';
        $html .= '</button>';

        $onclick = $this->getJsLoadModalView('logistique_equipments', 'Attribuer des équipements');

        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_desktop', 'iconLeft') . 'Attribuer des équipements';
        $html .= '</button>';

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
                $errors = array_merge($errors, $line->createReservation());
            }
        } else {
            $errors[] = 'ID de la commande absent';
        }

        return $errors;
    }

    public function addOrderLine($id_product, $qty = 1, $desc = '', $id_fournisseur_price = 0, $remise_percent = 0, $date_start = '', $date_end = '')
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande client absent';
        }

        if (!(int) $id_product) {
            $errors[] = 'Produit absent';
        }

        if (count($errors)) {
            return $errors;
        } else {
            global $db;
            $product = new Product($db);
            if ($product->fetch((int) $id_product) <= 0) {
                $errors[] = 'ID du produit invalide';
            } else {
                $pu_ht = $product->price;
                $txtva = (float) $product->tva_tx;
                $txlocaltax1 = 0;
                $txlocaltax2 = 0;
                $fk_product = (int) $id_product;
                $info_bits = 0;
                $fk_remise_except = 0;
                $price_base_type = 'HT';
                $pu_ttc = $product->price_ttc;
                $pa_ht = 0;
                if ($id_fournisseur_price) {
                    $fournPrice = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $id_fournisseur_price);
                    if (BimpObject::objectLoaded($fournPrice)) {
                        $pa_ht = (float) $fournPrice->getData('price');
                    } else {
                        $errors[] = 'Prix fournisseur d\'ID ' . $id_fournisseur_price . ' inexistant';
                        return $errors;
                    }
                }

                $current_status = $this->dol_object->statut;
                $this->dol_object->statut = Commande::STATUS_DRAFT;

                $id_line = $this->dol_object->addline($desc, $pu_ht, (int) $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, (float) $remise_percent, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $date_start, $date_end, 0, -1, 0, 0, null, $pa_ht);

                $this->dol_object->statut = $current_status;
                $this->update();

                if ($id_line <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de l\'ajout de la ligne de commande');
                } else {
                    global $db;
                    $line = new OrderLine($db);
                    $line->fetch((int) $id_line);
                    $line->id = $line->rowid;

                    $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                    $br_order_line = BimpObject::getInstance('bimpreservation', 'BR_OrderLine');
                    $id_entrepot = (int) $this->dol_object->array_options['options_entrepot'];

                    $res_errors = $reservation->createFromCommandeClientLine($id_entrepot, $this->dol_object, $line, $br_order_line);
                    if (count($res_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la création des éléments de la logistique');
                    }
                }

                $this->checkIsFullyShipped();
                $this->checkIsFullyInvoiced();
            }
        }

        return $errors;
    }

    public function removeOrderLine($id_line, $qty, $id_avoir = 0, $id_equipment = 0)
    {
        $errors = array();

        if ($this->isLoaded()) {
            $orderLine = BimpObject::getInstance('bimpreservation', 'BR_OrderLine');
            if ($orderLine->find(array('id_order_line' => (int) $id_line))) {
                $current_qty = (int) $orderLine->getData('qty');
                $new_qty = $current_qty - $qty;
                if ($new_qty < 0) {
                    $errors[] = 'Quantité à retirer invalide (nouvelles quantités négatives)';
                    return $errors;
                }

                if ((int) $this->getData('id_facture')) {
                    // Ajout à l'avoir:
                    $avoir_errors = $this->addLineToCreditNote($id_line, $qty, $id_avoir, null, $id_equipment);
                    if (count($avoir_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($avoir_errors, 'Echec de l\'ajout à l\'avoir');
                    }
                }
                global $user;
                $current_status = $this->dol_object->statut;
                $this->dol_object->statut = Commande::STATUS_DRAFT;
                $this->dol_object->update($user);
                $this->dol_object->fetch($this->id);

                if ($new_qty > 0) {
                    // Mise à jour des quantités de la ligne de commande: 

                    global $db;
                    $line = new OrderLine($db);
                    if ($line->fetch((int) $id_line) <= 0) {
                        $errors[] = 'Ligne de commande d\'ID ' . $id_line . ' non trouvée';
                    } else {
                        if ($this->dol_object->updateline((int) $id_line, $line->desc, (float) $line->subprice, $new_qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT') <= 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour des quantités pour la ligne de commande d\'ID ' . $id_line);
                        } else {
                            $up_errors = $orderLine->updateField('qty', $new_qty);
                            if (count($up_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour des quantités pour la ligne de commande d\'ID ' . $id_line);
                            }
                        }
                    }
                } else {
                    // Suppression de la ligne de commande (quantités = 0) 
                    if ($this->dol_object->deleteline($user, $id_line) <= 0) {
                        $errors = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression de la ligne de commande');
                    } else {
                        $del_warnings = array();
                        $del_errors = $orderLine->delete($del_warnings, true);
                        if (count($del_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression de la ligne de commande');
                        }
                    }
                }

                $this->dol_object->statut = $current_status;
                $this->dol_object->update($user);
                $this->dol_object->fetch($this->id);
            } else {
                $errors[] = 'ID de la ligne de commande absent ou invalide';
            }
        } else {
            $errors[] = 'ID de ma commande absent';
        }

        return $errors;
    }

    public function checkIsFullyShipped()
    {
        global $user;
        $errors = array();
        if ($this->isFullyShipped()) {
            if ((int) $this->dol_object->statut !== Commande::STATUS_CLOSED) {
                if ($this->dol_object->cloture($user) <= 0) {
                    $errors[] = 'Echec de la fermeture de la commande';
                }
            }
        } else {
            if ((int) $this->dol_object->statut === Commande::STATUS_CLOSED) {
                if ($this->dol_object->set_reopen($user) <= 0) {
                    $errors[] = 'Echec de la réouverture de la commande';
                }
            }
        }
        return $errors;
    }

    public function checkIntegrity()
    {
        return array();

        $errors = array();
        if ($this->isLoaded() && $this->dol_object->statut > 0) {
            $nCommandeProducts = 0;
            $nCommandeServices = 0;
            $nBrOrderProducts = 0;
            $nBrOrderServices = 0;
            $nToShipProducts = 0;
            $nToShipServices = 0;
            $nShippedProducts = 0;
            $nShippedServices = 0;
            $nOrderLineProductsShipped = 0;
            $nOrderLineServicesShipped = 0;

            $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');

            foreach ($this->dol_object->lines as $line) {
                if (isset($line->fk_product) && (int) $line->fk_product) {
                    $type = (int) $product->getSavedData('fk_product_type', (int) $line->fk_product);
                    if ((int) $line->qty > 0) {
                        if ($type === Product::TYPE_PRODUCT) {
                            $nCommandeProducts += (int) $line->qty;
                        } else {
                            $nCommandeServices += (int) $line->qty;
                        }
                    }
                }
            }

            foreach ($this->getChildrenObjects('order_lines') as $brOrderLine) {
                $qty = (int) $brOrderLine->getData('qty');
                $qtyShipped = (int) $brOrderLine->getData('qty_shipped');
                switch ((int) $brOrderLine->getData('type')) {
                    case BR_OrderLine::PRODUIT:
                        $nBrOrderProducts += $qty;
                        $nOrderLineProductsShipped += $qtyShipped;
                        break;

                    case BR_OrderLine::SERVICE:
                        $nBrOrderServices += $qty;
                        $nOrderLineServicesShipped += $qtyShipped;
                        break;
                }
            }

            foreach ($this->getChildrenObjects('shipments') as $shipment) {
                if ((int) $shipment->getData('status') === 2) {
                    $nShippedProducts += (int) $shipment->getNbArticles();
                    $nShippedServices += (int) $shipment->getNbServices();
                } else {
                    $nToShipProducts += (int) $shipment->getNbArticles();
                    $nToShipServices += (int) $shipment->getNbServices();
                }
            }

            if ((int) $nCommandeProducts !== (int) $nBrOrderProducts) {
                $errors[] = 'Le nombre de produits enregistrés pour la commande (' . $nCommandeProducts . ') ne correspond pas au nombre de produits enregistrés pour la logistique (' . $nBrOrderProducts . ')';
            }

            if ((int) $nCommandeServices !== (int) $nBrOrderServices) {
                $errors[] = 'Le nombre de services enregistrés pour la commande ne correspond pas au nombre de services enregistrés pour la logistique';
            }
            if (((int) $nToShipProducts + (int) $nShippedProducts) !== $nOrderLineProductsShipped) {
                $errors[] = 'Le nombre de produits expédiés ne correspond pas à la quantité enregistrée';
            }
            if (((int) $nToShipServices + (int) $nShippedServices) !== $nOrderLineServicesShipped) {
                $errors[] = 'Le nombre de services expédiés ne correspond pas à la quantité enregistrée';
            }

            BimpObject::loadClass('bimpreservation', 'BR_Reservation');

            $sql = 'SELECT SUM(`qty`) as qty FROM ' . MAIN_DB_PREFIX . 'br_reservation WHERE `id_commande_client` = ' . (int) $this->id;
            $result = $this->db->executeS($sql . ' AND `status` = 250');
            if ((int) $result[0]->qty !== (int) $nToShipProducts) {
                $errors[] = 'Le nombre de réservations au statut "' . BR_Reservation::$status_list[250]['label'] . '" est incorrect';
            }
            $result = $this->db->executeS($sql . ' AND `status` = 300');
            if ((int) $result[0]->qty !== (int) $nShippedProducts) {
                $errors[] = 'Le nombre de réservations au statut "' . BR_Reservation::$status_list[300]['label'] . '" est incorrect';
            }
            $result = $this->db->executeS($sql . ' AND `status` < 250');
            if ((int) $result[0]->qty !== (int) ($nCommandeProducts - $nToShipProducts - $nShippedProducts)) {
                $errors[] = 'Le nombre de réservations non expédiées ou en attente d\'expédition est incorrect: ' . $result[0]->qty . ' => ' . $nCommandeProducts . ', ' . $nToShipProducts . ', ' . $nShippedProducts;
            }
        }

        return $errors;
    }

    // Traitements factures old: 

    public function createFactureOld($shipments_ids = null, $cond_reglement = null, $id_account = null, $remises = array(), $public_note = '', $private_note = '')
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande client absent ou invalide';
            return $errors;
        }

        if ((int) $this->getData('id_facture')) {
            $errors[] = 'Tous les éléments de cette commande ont déjà été facturés';
            return $errors;
        }

        $id_client = (int) $this->dol_object->socid;

        if (!$id_client) {
            $errors[] = 'Aucun client enregistré pour cette commande';
        }

        $shipments_objects = array();

        if (!is_null($shipments_ids)) {
            if (!is_array($shipments_ids)) {
                $shipments_ids = array($shipments_ids);
            }
            foreach ($shipments_ids as $id_shipment) {
                $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $id_shipment);
                if (!BimpObject::objectLoaded($shipment)) {
                    $errors[] = 'Expédition d\'ID ' . $id_shipment . ' non trouvée';
                } elseif ((int) $shipment->getData('id_facture')) {
                    $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' a déjà été facturée';
                } else {
                    $shipments_objects[] = $shipment;
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        global $user, $langs;

        $commande = $this->dol_object;

        $langs->load('errors');
        $langs->load('bills');
        $langs->load('companies');
        $langs->load('compta');
        $langs->load('products');
        $langs->load('banks');
        $langs->load('main');

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

        $rs = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');
        $ss = BimpObject::getInstance('bimpreservation', 'BR_ServiceShipment');

        if (!is_null($shipments_objects) && count($shipments_objects)) {
            $shipments_list = implode(',', $shipments_ids);

            foreach ($commande->lines as $i => $line) {
                if (!isset($line->fk_product) || !$line->fk_product) {
                    unset($commande->lines[$i]);
                    continue;
                }

                $qty = 0;

                $list = $rs->getList(array(
                    'id_shipment'             => array('in' => $shipments_list),
                    'id_commande_client'      => (int) $this->id,
                    'id_commande_client_line' => $line->id
                        ), null, null, 'id', 'asc', 'array', array('qty'));
                if (count($list)) {
                    foreach ($list as $item) {
                        $qty += (int) $item['qty'];
                    }
                } else {
                    foreach ($ss->getList(array(
                        'id_shipment'             => array('in' => $shipments_list),
                        'id_commande_client'      => (int) $this->id,
                        'id_commande_client_line' => $line->id
                            ), null, null, 'id', 'asc', 'array', array('qty')) as $item) {
                        $qty += (int) $item['qty'];
                    }
                }

                if ($qty === 0) {
                    unset($commande->lines[$i]);
                } else {
                    $commande->lines[$i]->qty = $qty;
                }
            }

            if (!count($commande->lines)) {
                $errors[] = 'Aucun produit ou service à facturer trouvé';
            }
            if (count($errors)) {
                return $errors;
            }
        } else {
            $lines_billed_qties = array();
            $shipments = $this->getChildrenObjects('shipments', array(
                'id_facture' => array(
                    'operator' => '>',
                    'value'    => 0
                )
            ));
            $filters = array(
                'id_commande_client' => (int) $this->id
            );
            foreach ($shipments as $shipment) {
                $filters['id_shipment'] = (int) $shipment->id;
                foreach ($rs->getList($filters, null, null, 'id', 'asc', 'array', array('id_commande_client_line', 'qty')) as $item) {
                    if (!isset($lines_billed_qties[(int) $item['id_commande_client_line']])) {
                        $lines_billed_qties[(int) $item['id_commande_client_line']] = 0;
                    }
                    $lines_billed_qties[(int) $item['id_commande_client_line']] += (int) $item['qty'];
                }
                foreach ($ss->getList($filters, null, null, 'id', 'asc', 'array', array('id_commande_client_line', 'qty')) as $item) {
                    if (!isset($lines_billed_qties[(int) $item['id_commande_client_line']])) {
                        $lines_billed_qties[(int) $item['id_commande_client_line']] = 0;
                    }
                    $lines_billed_qties[(int) $item['id_commande_client_line']] += (int) $item['qty'];
                }
            }

            foreach ($commande->lines as $i => $line) {
                if (isset($lines_billed_qties[(int) $line->id])) {
                    $new_qties = (int) $line->qty - $lines_billed_qties[(int) $line->id];

                    if ($new_qties === 0) {
                        unset($commande->lines[$i]);
                    } else {
                        $commande->lines[$i]->qty = $new_qties;
                    }
                }
            }
        }

        if (!is_null($cond_reglement) && $cond_reglement) {
            $commande->cond_reglement_id = (int) $cond_reglement;
        }

        $commande->array_options['options_type'] = 'C';
        if ($facture->createFromCommande($commande, (int) $id_account, $public_note, $private_note) <= 0) {
            $msg = 'Echec de la création de la facture';
            if ($facture->dol_object->error) {
                $msg .= ' - "' . $langs->trans($facture->dol_object->error) . '"';
            }
            $errors[] = $msg;
            return $errors;
        }

        unset($commande);
        $commande = null;

        if (count($remises)) {
            foreach ($remises as $id_remise) {
                $facture->dol_object->error = '';
                $facture->dol_object->errors = array();

                if ($facture->dol_object->insert_discount((int) $id_remise) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de l\'insertion de la remise client d\'ID ' . $id_remise);
                }
            }
        }

        // Validation de la facture: 
//        if ($facture->dol_object->validate($user) <= 0) {
//            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de la validation de la facture');
//        }
//
//        $facture->dol_object->generateDocument('bimpfact', $langs);

        $this->fetch($this->id);

        if (count($shipments_objects)) {
            foreach ($shipments_objects as $shipment) {
                $shipment->set('id_facture', (int) $facture->id);
                if ((int) $shipment->getData('status') !== 2) {
                    $shipment->set('status', 4);
                }
                $up_errors = $shipment->update();
                if (count($up_errors)) {
                    $label = 'Expédition n° ' . $shipment->getData('num_livraison');
                    $errors[] = BimpTools::getMsgFromArray($up_errors, $label . ': facture créée avec succès mais échec de l\'enregistrement de l\'ID facture (' . $facture->id . ')');
                }
            }
        } else {
            $up_errors = $this->updateField('id_facture', (int) $facture->id);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Des erreurs sont survenues durant l\'enregistrement de l\'ID de la facture');
            }
        }

        $this->checkIsFullyInvoiced();

        return $errors;
    }

    public function checkIsFullyInvoiced()
    {
        global $user;
        $errors = array();
        if ($this->isFullyInvoiced()) {
            if (!(int) $this->dol_object->billed) {
                if ($this->dol_object->classifyBilled($user) <= 0) {
                    $errors[] = 'Echec de la mise à jour du statut de la commande à "Facturée"';
                }
            }
        } else {
            if ((int) $this->dol_object->billed) {
                if ($this->dol_object->classifyUnBilled() <= 0) {
                    $errors[] = 'Echec de la mise à jour du statut de la commande à "Non Facturée"';
                }
            }
        }
        return $errors;
    }

    public function addLineToCreditNote($id_line, $qty, $id_avoir = null, $id_facture_source = null, $id_equipment = null)
    {
        $errors = array();

        $avoir = null;

        global $db, $user, $langs;

        $langs->load('errors');
        $langs->load('bills');
        $langs->load('companies');
        $langs->load('compta');
        $langs->load('products');
        $langs->load('banks');
        $langs->load('main');

        if (is_null($id_avoir) || !(int) $id_avoir) {
            // Création d'un nouvel avoir: 
            BimpTools::loadDolClass('compta/facture', 'facture');
            $avoir = new Facture($db);
            $avoir->date = dol_now();
            $avoir->socid = $this->dol_object->socid;
            $avoir->type = Facture::TYPE_CREDIT_NOTE;
            $avoir->origin = $this->dol_object->element;
            $avoir->origin_id = $this->dol_object->id;
            $avoir->array_options['options_type'] = 'R';
            $avoir->array_options['options_entrepot'] = $this->dol_object->array_options['options_entrepot'];

            if (!is_null($id_facture_source)) {
                $avoir->fk_facture_source = $id_facture_source;
            }

            $avoir->linked_objects[$avoir->origin] = $avoir->origin_id;

            if ($avoir->create($user) <= 0) {
                $avoir_errors = BimpTools::getErrorsFromDolObject($avoir, null, $langs);
                $errors[] = BimpTools::getMsgFromArray($avoir_errors, 'Des erreurs sont survenues lors de la création de l\'avoir');
            } else {
                $asso = new BimpAssociation($this, 'avoirs');
                $asso->addObjectAssociation($avoir->id);
            }
        } else {
            $bimp_avoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_avoir);
            if (!$bimp_avoir->isLoaded()) {
                $errors[] = 'Avoir d\'ID ' . $id_avoir . ' inexistant';
            } else {
                $avoir = $bimp_avoir->dol_object;
            }
        }

        if (!count($errors) && BimpObject::objectLoaded($avoir)) {
            $order_line = new OrderLine($db);
            if ($order_line->fetch((int) $id_line) <= 0) {
                $errors[] = 'Ligne de commande d\'ID ' . $id_line . ' inexistante';
            } else {
                $serial = '';
                if (!is_null($id_equipment) && (int) $id_equipment) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if ($equipment->isLoaded()) {
                        $serial = $equipment->getData('serial');
                    }
                }

                $fk_product = $order_line->fk_product;
                $desc = $order_line->desc . ($serial ? ' - N° de série: ' . $serial : '');
                $qty = (int) $qty;
                $pu_ht = $order_line->subprice;
                $txtva = $order_line->tva_tx;
                $remise_percent = $order_line->remise_percent;

                $txlocaltax1 = $order_line->localtax1_tx;
                $txlocaltax2 = $order_line->localtax2_tx;
                $price_base_type = 'HT';
                $date_start = '';
                $date_end = '';
                $ventil = 0;
                $info_bits = 0;
                $fk_remise_except = $order_line->fk_remise_except;

                if ($avoir->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $ventil, $info_bits, $fk_remise_except, $price_base_type) <= 0) {
                    $msg = 'Des erreurs sont survenues lors de l\'ajout à l\'avoir';
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir, null, $langs), $msg);
                }
            }
        }

        return $errors;
    }

    // Traitements factures new: 

    public function createFacture($lines_qties = null, $lines_equipments = array(), $id_client = null, $id_contact = null, $cond_reglement = null, $id_account = null, $public_note = '', $private_note = '')
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande client absent ou invalide';
            return $errors;
        }

        if (is_null($id_client)) {
            $id_client = (int) $this->getData('fk_soc');
        }

        if (!$id_client) {
            $errors[] = 'Aucun client enregistré pour cette commande';
        }

        if (count($errors)) {
            return $errors;
        }

        if (is_null($lines_qties)) {
            // Récupération de toutes les lignes de la commande: 
            foreach ($this->getChildrenObjects('lines', array(
                'type' => array(
                    'operator' => '<>',
                    'value'    => ObjectLine::LINE_TEXT
                )
            )) as $line) {
                $qty = (float) $line->qty - (float) $line->getBilledQty();
                if ($qty) {
                    $lines_qties[(int) $line->id] = $qty;
                }
            }
        }

        if (empty($lines_qties)) {
            $errors[] = 'Aucune ligne de commande disponible pour la création d\'une nouvelle facture';
            return $errors;
        }

        // Vérification des quantités: 
        $errors = $this->checkFactureLinesQties($lines_qties, null, $lines_equipments);

        if (count($errors)) {
            return $errors;
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
            return $errors;
        }

        $asso = new BimpAssociation($this, 'factures');
        $asso->addObjectAssociation($id_facture);

        // Ajout des lignes à la facture: 
        $errors = $this->addLinesToFacture($id_facture, $lines_qties, false, $lines_equipments);

        return $errors;
    }

    public function checkFactureLinesQties($lines_qties, $id_facture = null, $lines_equipments = array())
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
                $billed_qty = (float) $line->getBilledQty();
                $available_qty = (float) $line->qty - $billed_qty;

                if (!is_null($id_facture) && (int) $id_facture) {
                    $facture_data = $line->getFactureData($id_facture);
                    if (isset($facture_data['qty'])) {
                        $available_qty += (float) $facture_data['qty'];
                    }
                }
                if ($qty > $available_qty) {
                    if ($available_qty > 0) {
                        $msg = 'Toutes les unités ont déjà été facturées';
                    } else {
                        $msg = 'Il ne reste que ' . $available_qty . ' unité(s) à facturer.<br/>';
                        $msg .= 'Veuillez retirer ' . ($qty - $available_qty) . ' unité(s)';
                    }
                    $errors[] = BimpTools::getMsgFromArray($msg, 'Ligne n°' . $line->getData('position'));
                } elseif (isset($lines_equipments[(int) $id_line])) {
                    if (count($lines_equipments) > (int) $qty) {
                        $errors[] = BimpTools::getMsgFromArray('Veuillez désélectionner ' . (count($lines_equipments[(int) $id_line]) - (int) $qty) . ' équipement(s)', 'Ligne n°' . $line->getData('position'));
                    }
                }
            }
        }

        return $errors;
    }

    public function addLinesToFacture($id_facture, $lines_qties = null, $check_qties = true, $lines_equipments = array())
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande client absent ou invalide';
            return $errors;
        }

        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

        if (!BimpObject::objectLoaded($facture)) {
            $errors[] = 'La facture d\'ID ' . $id_facture . ' n\'existe pas';
            return $errors;
        }

        if ($check_qties) {
            $errors = $this->checkFactureLinesQties($lines_qties, $id_facture, $lines_equipments);
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

                if (BimpObject::objectLoaded($fac_line)) {
                    $fac_line->qty = (float) $line_qty;

                    $fac_line_warnings = array();
                    $fac_line_errors = $fac_line->update($fac_line_warnings);
                    $fac_line_errors = array_merge($fac_line_errors, $fac_line_warnings);

                    if (count($fac_line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la mise à jour de la ligne de facture depuis la ligne de commande n°' . $line->getData('position') . ' (ID ' . $line->id . ')');
                    }
                } else {
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
                    }
                }
                if (!count($fac_line_errors)) {                    
                    // Assignation des équipements ) la ligne de facture: 
                    if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                        $line_equipments = array();
                        
                        if (isset($lines_equipments[(int) $id_line])) {
                            foreach ($lines_equipments[(int) $id_line] as $id_equipment) {
                                $line_equipments[] = array(
                                    'id_equipment' => (int) $id_equipment
                                );
                            }
                        }

                        $eq_errors = $fac_line->setEquipments($line_equipments);
                        if (count($eq_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Ligne n°' . $line->getData('position'));
                        }
                    }

                    // Enregistrement des quantités facturées pour la ligne de commande: 
                    $line_warnings = array();

                    $line_errors = $line->setFactureData((int) $facture->id, $line_qty, isset($lines_equipments[(int) $id_line]) ? $lines_equipments[(int) $id_line] : array(), $line_warnings);

                    $line_errors = array_merge($line_errors, $line_warnings);

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'enregistrement des quantités facturées pour la ligne n°' . $line->getData('position') . ' (ID: ' . $line->id . ')');
                    }
                }
            } else {
                $errors[] = 'La ligne de commande client d\'ID ' . $id_line . ' n\'existe pas';
            }
        }

        $this->checkInvoiceStatus();

        return $errors;
    }

    public function checkInvoiceStatus()
    {
        // todo
    }

    // Actions:

    public function actionRemoveOrderLines($data, &$success)
    {
        $success = 'Produits retirés de la commande avec succès';
        $errors = array();
        $warnings = array();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateFacture($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Création de la facture effectuée avec succès';
        $success_callback = '';

        $id_shipment = (isset($data['id_shipment']) ? (int) $data['id_shipment'] : null);
        $cond_reglement = (isset($data['cond_reglement']) ? (int) $data['cond_reglement'] : 0);
        $id_account = (isset($data['id_account']) ? (int) $data['id_account'] : 0);
        $remises = (isset($data['id_remises_list']) ? $data['id_remises_list'] : array());
        $public_note = (isset($data['note_public']) ? $data['note_public'] : '');
        $private_note = (isset($data['note_private']) ? $data['note_private'] : '');

        if ((is_null($id_account) || !$id_account)) {
            $errors[] = 'Compte financier absent';
        }

        if ((is_null($id_shipment) || !$id_shipment) && !(int) $this->getData('id_facture')) {
            $success_callback = 'bimp_reloadPage();';
        }

        $errors = $this->createFactureOld($id_shipment, $cond_reglement, $id_account, $remises, $public_note, $private_note);

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionAddLine($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = 'bimp_reloadPage();';
        $success = 'Produit / service ajouté à la commande avec succès - ' . $data['qty'];

        if (!isset($data['id_product']) || !$data['id_product']) {
            $errors[] = 'Produit absent';
        } else {
            $id_product = (int) $data['id_product'];
        }

        if (!isset($data['id_fournisseur_price']) || !$data['id_fournisseur_price']) {
            $errors[] = 'Prix fournisseur absent';
        } else {
            $id_fournisseur_price = (int) $data['id_fournisseur_price'];
        }

        if (isset($data['qty'])) {
            $qty = (int) $data['qty'];
        } else {
            $qty = 1;
        }

        if (isset($data['desc'])) {
            $desc = $data['desc'];
        } else {
            $desc = '';
        }

        if (isset($data['reduc'])) {
            $remise_percent = $data['reduc'];
        } else {
            $remise_percent = 0;
        }

        if (!isset($data['limited']) || !(int) $data['limited']) {
            $data['date_start'] = '';
            $data['date_end'] = '';
        }

        $errors = $this->addOrderLine($id_product, $qty, $desc, $id_fournisseur_price, $remise_percent, $data['date_start'], $data['date_end']);

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionValidateFacture($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Facture validée avec succès';
        $success_callback = 'bimp_reloadPage();';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande absent';
        } elseif (!(int) $this->getData('id_facture')) {
            $errors[] = 'Aucune facture enregistrée pour cette commande';
        } else {
            $facture = $this->getChildObject('facture');
            if (!BimpObject::objectLoaded($facture)) {
                $errors[] = 'Facture d\'ID ' . $this->getData('id_facture') . ' non trouvée';
            } elseif ((int) $facture->getData('fk_statut') > 0) {
                $errors[] = 'Cette facture a déjà été validée';
            } else {
                global $user, $langs;

                if ($facture->dol_object->validate($user) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de la validation de la facture');
                }

                $facture->dol_object->generateDocument('bimpfact', $langs);
            }
        }
        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

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
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Des erreurs sont survenues lors de la validation ' . $this->getLabel('of_the'));
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
                        } else {
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

            if ((int) $data['id_facture']) {
                $success = 'Ajout des unités à la facture effectué avec succès';
                $errors = $this->addLinesToFacture((int) $data['id_facture'], $lines_qties, true, $lines_equipments);
            } else {
                $success = 'Création de la facture effectuée avec succès';
                $id_client = isset($data['id_client']) ? $data['id_client'] : null;
                $id_contact = isset($data['id_contact']) ? $data['id_contact'] : null;
                $id_cond_reglement = isset($data['id_cond_reglement']) ? $data['id_cond_reglement'] : null;
                $errors = $this->createFacture($lines_qties, $lines_equipments, $id_client, $id_contact, $id_cond_reglement);
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
        $success = 'Tous les nouveaux statuts ont été enregistrés avec succès';

        $reservations = isset($data['reservations']) ? $data['reservations'] : array();
        $status = isset($data['status']) ? (int) $data['status'] : null;

        if (!is_array($reservations) || empty($reservations)) {
            $errors[] = 'Aucun élément sélectionné';
        } elseif (is_null($status)) {
            $errors[] = 'Nouveau statut non spécifié';
        } else {
            foreach ($reservations as $id_reservation) {
                $reservation = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', (int) $id_reservation);
                if (!BimpObject::objectLoaded($reservation)) {
                    $warnings[] = 'La réservation d\'ID ' . $id_reservation . ' n\'existe pas';
                } else {
                    $line = $reservation->getChildObject('commande_client_line');

                    if (!BimpObject::objectLoaded($line)) {
                        $warnings[] = 'La réservation d\'ID ' . $id_reservation . ' n\'est pas associée à une ligne de commande valide';
                    } else {
                        $res_errors = $reservation->setNewStatus($status);

                        if (count($res_errors)) {
                            $title = 'Ligne n° ' . $line->getData('position') . ': ';
                            $title .= 'statut "' . BR_Reservation::$status_list[(int) $reservation->getData('status')]['label'] . '"';
                            $warnings[] = BimpTools::getMsgFromArray($res_errors, $title);
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

    // Overrides BimpComm:

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $new_data['id_facture'] = 0;
        $new_data['validFin'] = 0;
        $new_data['validComm'] = 0;
        $new_data['date_creation'] = date('Y-m-d H:i:s');
        $new_data['date_valid'] = null;
        $new_data['date_cloture'] = null;
        $new_data['fk_user_modif'] = 0;
        $new_data['fk_user_valid'] = 0;
        $new_data['fk_user_cloture'] = 0;

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
}

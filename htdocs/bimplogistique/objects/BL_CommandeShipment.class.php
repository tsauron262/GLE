<?php

class BL_CommandeShipment extends BimpObject
{

    const BLCS_BROUILLON = 1;
    const BLCS_EXPEDIEE = 2;
    const BLCS_ANNULEE = 3;
    const BLCS_VEROUILLEE = 4;

    public static $status_list = array(
        self::BLCS_BROUILLON  => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        self::BLCS_EXPEDIEE   => array('label' => 'Expédiée', 'icon' => 'check', 'classes' => array('success')),
        self::BLCS_ANNULEE    => array('label' => 'Annulée', 'icon' => 'times', 'classes' => array('danger')),
        self::BLCS_VEROUILLEE => array('label' => 'Vérouillée', 'icon' => 'lock', 'classes' => array('important'))
    );
    public static $signed_values = array(
        0 => array('label' => 'NON', 'classes' => array('danger')),
        1 => array('label' => 'OUI', 'classes' => array('success')),
        2 => array('label' => 'Non applicable', 'classes' => array('info')),
    );

    // Gestion des droits et autorisations: 

    public function isActionAllowed($action, $errors = array())
    {

        switch ($action) {
            case 'validateShipment':
                if (!in_array((int) $this->getData('status'), array(1, 4))) {
                    $errors[] = 'Cette expédition doit avoir le statut "' . self::$status_list[1]['label'] . '" ou "' . self::$status_list[4]['label'] . '" pour pouvoire être expédiée';
                    return 0;
                }
                return 1;

            case 'cancelShipment':
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    // Getters: 

    public function getContactsArray()
    {
        $commande = $this->getChildObject('commande_client');

        if (!BimpObject::objectLoaded($commande)) {
            return array();
        }

        $commande = $commande->dol_object;

        $contacts = array(
            0 => 'Addresse de livraison de la commande'
        );

        if (!is_null($commande->socid) && $commande->socid) {
            $where = '`fk_soc` = ' . (int) $commande->socid;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        BimpTools::loadDolClass('contact');

        $bill_contacts = $commande->getIdContact('external', 'BILLING');
        if (!is_null($bill_contacts) && count($bill_contacts)) {
            foreach ($bill_contacts as $id_contact) {
                if (!array_key_exists((int) $id_contact, $contacts)) {
                    $contact = new Contact($this->db->db);
                    if ($contact->fetch((int) $id_contact) > 0) {
                        $contacts[(int) $id_contact] = $contact->firstname . ' ' . $contact->lastname;
                    }
                    unset($contact);
                }
            }
        }

        $ship_contacts = $commande->getIdContact('external', 'SHIPPING');
        if (!is_null($ship_contacts) && count($ship_contacts)) {
            foreach ($ship_contacts as $id_contact) {
                if (!array_key_exists((int) $id_contact, $contacts)) {
                    $contact = new Contact($this->db->db);
                    if ($contact->fetch((int) $id_contact) > 0) {
                        $contacts[(int) $id_contact] = $contact->firstname . ' ' . $contact->lastname;
                    }
                    unset($contact);
                }
            }
        }

        return $contacts;
    }

    public function getIdClient()
    {
        if (BimpTools::isSubmit('extra_data/id_commande_client')) {
            $this->set('id_commande_client', (int) BimpTools::getValue('extra_data/id_commande_client', 0));
        }

        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            return (int) $commande->dol_object->socid;
        }

        return 0;
    }

    public function getExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {

            $buttons[] = array(
                'label'   => 'Produits / services inclus',
                'icon'    => 'fas_list',
                'onclick' => $this->getJsLoadModalView('lines', 'Expédition n°' . $this->getData('num_livraison') . ': produits / services inclus')
            );

            if (in_array((int) $this->getData('status'), array(1, 4))) {
                $buttons[] = array(
                    'label'   => 'Expédier',
                    'icon'    => 'fas_sign-out-alt',
                    'onclick' => $this->getJsActionOnclick('validateShipment', array(), array(
                        'form_name' => 'validation'
                    ))
                );
            }

            if (!(int) $this->getData('id_facture')) {
                $buttons[] = array(
                    'label'   => 'Créer une facture',
                    'icon'    => 'far_file-alt',
                    'onclick' => $this->getJsActionOnclick('createFacture', array(), array(
                        'form_name' => 'facture',
                        'on_form_submit' => 'function($form, extra_data) { return onShipmentFactureFormSubmit($form, extra_data); } '
                    ))
                );
            }
        }

        return $buttons;
    }

    public function getCommandesListbulkActions()
    {
//        $id_commande = (int) $this->getData('id_commande_client');
//
//        return array(
//            array(
//                'label'   => 'Créer une facture unique',
//                'icon'    => 'far_file-alt',
//                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'createBulkFacture\', {id_commande_client: ' . $id_commande . '}, \'facture\', null, true)'
//            )
//        );

        return array();
    }

    public function getName()
    {
        return 'Expédition n°' . $this->getData('num_livraison');
    }

    public function getcontact()
    {
        $id_contact = (int) $this->getData('id_contact');
        if (!$id_contact) {
            $commande = $this->getParentInstance();
            $contacts = $commande->dol_object->getIdContact('external', 'SHIPPING');
            if (isset($contacts[0]) && $contacts[0]) {
                $id_contact = $contacts[0];
            } else {
                $contacts = $commande->dol_object->getIdContact('external', 'CUSTOMER');
                if (isset($contacts[0]) && $contacts[0]) {
                    $id_contact = $contacts[0];
                }
            }
        }

        return $id_contact;
    }

    public function getPDFQtiesAndSerials()
    {
        $qties = array();

        $commande = $this->getParentInstance();
        $equipment_instance = BimpObject::getInstance('bimpequipment', 'Equipment');

        if (BimpObject::objectLoaded($commande)) {
            $lines = $commande->getChildrenObjects('lines');
            $prev_shipments = array();
            $list = $this->getList(array(
                'id'           => array(
                    'operator' => '<>',
                    'value'    => $this->id
                ),
                'date_shipped' => array(
                    'operator' => '<',
                    'value'    => $this->getData('date_shipped')
                )
                    ), null, null, 'num_livraison', 'asc', 'array', array('id'));

            foreach ($list as $item) {
                $prev_shipments[] = $item['id'];
            }

            foreach ($lines as $line) {
                $line_qties = array(
                    'qty'         => 0,
                    'shipped_qty' => 0,
                    'to_ship_qty' => 0,
                    'serials'     => array()
                );
                $line_shipments = $line->getData('shipments');
                foreach ($line_shipments as $id_shipment => $shipment_data) {
                    if ((int) $id_shipment === $this->id) {
                        $line_qties['qty'] = (float) $shipment_data['qty'];
                        if (isset($shipment_data['equipments'])) {
                            $equipments = $equipment_instance->getList(array(
                                'id' => array(
                                    'in' => $shipment_data['equipments']
                                )
                                    ), null, null, 'id', 'asc', 'array', array('serial'));

                            foreach ($equipments as $eq) {
                                $line_qties['serials'][] = $eq['serial'];
                            }
                        }
                    } elseif (in_array($id_shipment, $prev_shipments)) {
                        $line_qties['shipped_qty'] += (float) $shipment_data['qty'];
                    } else {
                        $line_qties['to_ship_qty'] += (float) $shipment_data['qty'];
                    }
                }
                $qties[(int) $line->getData('id_line')] = $line_qties;
            }
        }

        return $qties;
    }

    // Affichages: 

    public function displayContact()
    {
        $id_contact = (int) $this->getData('id_contact');

        if ($id_contact) {
            return $this->displayData('id_contact', 'nom_url');
        }

        $commande = $this->getChildObject('commande_client');
        if (BimpObject::objectLoaded($commande)) {
            $commande = $commande->dol_object;
            $contacts = $commande->getIdContact('external', 'SHIPPING');
            if (isset($contacts[0]) && $contacts[0]) {
                BimpTools::loadDolClass('contact');
                $contact = new Contact($this->db->db);
                if ($contact->fetch($contacts[0]) > 0) {
                    return $contact->getNomUrl(1) . BimpRender::renderObjectIcons($contact, true);
                }
            }

            $contacts = $commande->getIdContact('external', 'CUSTOMER');
            if (isset($contacts[0]) && $contacts[0]) {
                BimpTools::loadDolClass('contact');
                $contact = new Contact($this->db->db);
                if ($contact->fetch($contacts[0]) > 0) {
                    return $contact->getNomUrl(1) . BimpRender::renderObjectIcons($contact, true);
                }
            }

            $commande->fetch_thirdparty();
            if (is_object($commande->thirdparty)) {
                return $commande->thirdparty->getNomUrl(1) . BimpRender::renderObjectIcons($commande->thirdparty, true);
            }

            return 'Adresse de livraison de la commande';
        }

        return '';
    }

    public function displayPdfButtons($display_global_invoice = false)
    {
        $html = '';

        if ($this->isLoaded()) {
            if ((int) $this->getData('status') === 2) {
                $url = DOL_URL_ROOT . '/bimplogistique/bl.php?id_shipment=' . $this->id;
                $onclick = 'window.open(\'' . $url . '\')';
                $html .= '<button type="button" class="btn btn-default" onclick="' . htmlentities($onclick) . '">';
                $html .= '<i class="' . BimpRender::renderIconClass('fas_file-pdf') . ' iconLeft"></i>';
                $html .= 'Bon de livraison';
                $html .= '</button>';
            }

            $facture = null;
            $label = 'Facture';
            if ((int) $this->getData('id_facture')) {
                $facture = $this->getChildObject('facture');
            } elseif ($display_global_invoice) {
                $commande = $this->getParentInstance();
                if (BimpObject::objectLoaded($commande)) {
                    if ((int) $commande->getData('id_facture')) {
                        $facture = $commande->getChildObject('facture');
                        $label .= ' (globale)';
                    }
                }
            }

            if (BimpObject::objectLoaded($facture)) {
                $ref = dol_sanitizeFileName($facture->dol_object->ref);
                if (file_exists(DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf')) {
                    $url = DOL_URL_ROOT . '/document.php?modulepart=facture&attachment=0';
                    $url .= '&file=' . htmlentities($ref . '/' . $ref) . '.pdf';
                    $onclick = 'window.open(\'' . $url . '\')';
                    $html .= '<button type="button" class="btn btn-default" onclick="' . htmlentities($onclick) . '">';
                    $html .= '<i class="' . BimpRender::renderIconClass('fas_file-pdf') . ' iconLeft"></i>';
                    $html .= $label;
                    $html .= '</button>';
                }
            }
        }

        return $html;
    }

    public function displayCommercial()
    {
        $commande = $this->getParentInstance();
        if (BimpObject::objectLoaded($commande)) {
            $users = $commande->dol_object->getIdContact('internal', 'SALESREPFOLL');
            if (isset($users[0]) && $users[0]) {
                $comm_user = new User($this->db->db);
                if ($comm_user->fetch((int) $users[0]) > 0) {
                    return self::getInstanceNomUrlWithIcons($comm_user);
                }
            }

            $users = $commande->dol_object->getIdContact('internal', 'SALESREPFOLL');
            if (isset($users[0]) && $users[0]) {
                $comm_user = new User($this->db->db);
                if ($comm_user->fetch((int) $users[0]) > 0) {
                    return self::getInstanceNomUrlWithIcons($comm_user);
                }
            }
        }

        return '';
    }

    // Rendus: 

    public function renderLinesQties()
    {
        $html = '';

        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de l\'expédition absent');
        }

        $commande = $this->getChildObject('commande_client');

        if (!BimpObject::objectLoaded($commande)) {
            if ((int) $this->getData('id_commande_client')) {
                $html .= BimpRender::renderAlerts($this->renderChildUnfoundMsg('commande_client'));
            } else {
                $html .= BimpRender::renderAlerts('ID de la commande absent');
            }
        } else {
            $lines = array();
            foreach ($commande->getChildrenObjects('lines') as $line) {
                $data = $line->getShipmentData($this->id);
                if ((float) $data['qty'] > 0) {
                    $ready_qty = $line->getReadyToShipQty($this->id);
                    $lines[] = array(
                        'line'      => $line,
                        'data'      => $data,
                        'ready_qty' => $ready_qty
                    );
                }
            }

            if (count($lines)) {
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Description</th>';
                $html .= '<th>Qté</th>';
                $html .= '<th>PU HT</th>';
                $html .= '<th>Statut</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                foreach ($lines as $line) {
                    $html .= '<tr>';
                    $html .= '<td>' . $line['line']->displayLineData('desc') . '</td>';
                    $html .= '<td>' . $line['data']['qty'] . '</td>';
                    $html .= '<td>' . $line['line']->displayLineData('pu_ht') . '</td>';

                    $html .= '<td>';
                    if ($line['ready_qty'] >= $line['data']['qty']) {
                        $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Prêt</span>';
                    } else {
                        $diff = $line['data']['qty'] - $line['ready_qty'];
                        $html .= '<span>' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . $diff . ' unité' . ($diff > 1 ? 's' : '') . ' en attente</span>';
                    }
                    $html .= '</td>';

                    $html .= '</tr>';

                    $product = $line['line']->getProduct();
                    if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                        $shipment_data = $line['line']->getShipmentData($this->id);

                        if (isset($shipment_data['equipments']) && !empty($shipment_data['equipments'])) {
                            $html .= '<tr>';
                            $html .= '<td colspan="4">';
                            $html .= '<div style="padding-left: 45px;">';
                            $html .= '<div style="font-weight: bold; font-size: 13px; margin-bottom: 6px">Equipements: </div>';

                            foreach ($shipment_data['equipments'] as $id_equipment) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if ($equipment->isLoaded()) {
                                    $html .= ' - ' . $equipment->getNomUrl(1, 1, 1, 'default');
                                } else {
                                    $html .= ' - <span class="danger">L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas</span>';
                                }
                                $html .= '<br/>';
                            }

                            $html .= '</div>';
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                $html .= '</tbody>';
                $html .= '</table>';
            } else {
                $html .= BimpRender::renderAlerts('Aucune unité ajoutée à cette expédition');
            }
        }

        return $html;
    }

    public function renderServicesQtiesInputs()
    {
        $id_commande = (int) $this->getData('id_commande_client');
        if (!$id_commande) {
            return '';
        }

        $commande = $this->getChildObject('commande_client');
        if (BimpObject::objectLoaded($commande)) {
            $commande = $commande->dol_object;
            $lines = $commande->lines;
            $html = '';
            $title_row = '';
            $service = BimpObject::getInstance($this->module, 'BR_OrderLine');

            $html = '<table class="objectlistTable">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="width: 40%">Service</th>';
            $html .= '<th>PU HT</th>';
            $html .= '<th>Déjà livrés</th>';
            $html .= '<th>Qté restante</th>';
            $html .= '<th>Qté à inclure</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            foreach ($lines as $i => $line) {
                if (is_null($line->fk_product) || !$line->fk_product) {
                    if (!is_null($line->desc) && $line->desc) {
                        $title_row = '<tr style="font-weight: bold; background-color: #DCDCDC;"><td colspan="5">' . str_replace("\n", '<br/>', $line->desc) . '</td></tr>';
                    } else {
                        continue;
                    }
                } else {
                    if ($line->total_ht == 0) {
                        continue;
                    }
                    $product = new Product($this->db->db);
                    if ($product->fetch((int) $line->fk_product) <= 0) {
                        unset($product);
                        $product = null;
                    } elseif ($product->type === 0) {
                        continue;
                    }
                    $desc = '';
                    if (is_null($line->desc) || !$line->desc) {
                        if (!is_null($product)) {
                            $desc = $product->ref;
                            $desc .= ($desc ? ' - ' : '') . $product->label;
                        }
                    }
                    if (!$desc) {
                        $desc = $line->desc;
                    }
                    $desc = str_replace("\n", '<br/>', $desc);
                    if ($desc) {
                        if ($service->find(array(
                                    'id_commande'   => (int) $commande->id,
                                    'id_order_line' => (int) $line->id,
                                    'type'          => BR_OrderLine::SERVICE
                                ))) {
                            $qty = (int) $service->getData('qty');
                            $qty_shipped = (int) $service->getData('qty_shipped');
                            $qty_available = $qty - $qty_shipped;
                            if ($qty_available < 0) {
                                $qty_available = 0;
                            }

                            if ($title_row) {
                                $html .= $title_row;
                                $title_row = '';
                            }

                            $html .= '<tr>';
                            $html .= '<td>' . $desc . '</td>';
                            $html .= '<td>' . BimpTools::displayMoneyValue($line->subprice, 'EUR') . '</td>';
                            $html .= '<td>' . $qty_shipped . '</td>';
                            $html .= '<td>' . $qty_available . '</td>';
                            if ($qty_available > 0) {
                                $html .= '<td>';
                                $field_name = 'service_' . $line->id;
                                $options = array(
                                    'step'  => 1,
                                    'data'  => array(
                                        'data_type' => 'number',
                                        'decimals'  => 0,
                                        'min'       => 0,
                                        'max'       => $qty_available,
                                        'unsigned'  => 1
                                    ),
                                    'style' => 'width: auto;'
                                );

                                $html .= '<div class="inputContainer ' . $field_name . '_inputContainer"';
                                $html .= ' data-field_name="' . $field_name . '"';
                                $html .= ' data-initial_value="' . $qty_available . '"';
                                $html .= ' data-multiple="0"';
                                $html .= '>';
                                $html .= BimpInput::renderInput('qty', $field_name, $qty_available, $options);
                                $html .= '</div>';
                                $html .= '</td>';
                            } else {
                                $html .= '<td></td>';
                            }
                            $html .= '</tr>';
                        }
                    }
                }
            }
            $html .= '</tbody></table>';
            return $html;
        }

        return BimpRender::renderAlerts('Commande invalide');
    }

    public function renderCommandeLinesForm()
    {
        $html = '';

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $html .= BimpRender::renderAlerts('ID de la commande client absent');
        } else {
            BimpObject::loadClass('bimpcommercial', 'ObjectLine');
            $lines = array();

            foreach ($commande->getChildrenObjects('lines', array(
                'type' => array(
                    'in' => array(ObjectLine::LINE_FREE, ObjectLine::LINE_PRODUCT)
                )
            )) as $line) {
                if ((float) $line->qty > (float) $line->getShippedQty()) {
                    $lines[] = $line;
                }
            }

            if (!count($lines)) {
                $html .= BimpRender::renderAlerts('Il ne reste aucune unité à expédier pour cette commande client', 'warning');
            } else {
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>N° ligne</th>';
                $html .= '<th>Désignation</th>';
                $html .= '<th>Qté</th>';
                $html .= '<th>Grouper les articles</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody class="receptions_rows">';
                foreach ($lines as $line) {
                    $max = (float) $line->qty - (float) $line->getShippedQty();
                    $decimals = 3;
                    $equipments = array();
                    $product = $line->getProduct();

                    if (BimpObject::objectLoaded($product)) {
                        if ((int) $product->getData('fk_product_type') === 0) {
                            $decimals = 0;
                        }

                        if ($product->isSerialisable()) {
                            $equipments = $line->getEquipementsToAttributeToShipment();
                        }
                    }

                    $html .= '<tr class="line_shipment_row" data-id_line="' . $line->id . '">';
                    $html .= '<td>' . $line->getData('position') . '</td>';
                    $html .= '<td>' . $line->displayLineData('desc') . '</td>';
                    $html .= '<td>';
                    $html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty', $max, array(
                                'data'      => array(
                                    'data_type' => 'number',
                                    'decimals'  => $decimals,
                                    'min'       => 0,
                                    'max'       => $max
                                ),
                                'max_label' => 1
                    ));
                    $html .= '</td>';
                    $html .= '<td>';
                    $html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_group_articles', 0);
                    $html .= '</td>';
                    $html .= '</tr>';

                    if (count($equipments)) {
                        $items = array();

                        foreach ($equipments as $id_equipment) {
                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                            if (BimpObject::objectLoaded($equipment)) {
                                $items[$id_equipment] = $equipment->getData('serial');
                            }
                        }
                        $html .= '<tr class="line_equipments_row">';
                        $html .= '<td colspan="4">';
                        $html .= 'Equipements: <br/>';
                        $html .= BimpInput::renderInput('check_list', 'line_' . $line->id . '_equipments', array(), array(
                                    'items' => $items
                        ));
                        $html .= '</td>';
                        $html .= '</tr>';
                    }
                }
                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function renderShipmentLines()
    {
        $html = '';

        if (!$this->isLoaded()) {
            $html .= BimpRender::renderAlerts('ID de l\'expédition absent');
        } else {
            $commande = $this->getParentInstance();
            if (!BimpObject::objectLoaded($commande)) {
                $html .= BimpRender::renderAlerts('ID de la commande client absent');
            } else {
                BimpObject::loadClass('bimpcommercial', 'ObjectLine');

                $lines = array();

                // Trie des lignes de commandes à afficher: 
                foreach ($commande->getChildrenObjects('lines', array(
                    'type' => array(
                        'in' => array(ObjectLine::LINE_PRODUCT, ObjectLine::LINE_FREE)
                    )
                )) as $line) {
                    $shipment_data = $line->getShipmentData($this->id);
                    if ((float) $shipment_data['qty'] > 0 || ((float) $line->qty > (float) $line->getShippedQty())) {
                        $lines[] = $line;
                    }
                }

                if (!count($lines)) {
                    $html .= BimpRender::renderAlerts('Il n\'y a aucun produit ou service disponible pour cette expédition', 'warning');
                } else {
                    $edit = ((int) $this->getData('status') === self::BLCS_BROUILLON);

                    $html .= '<div class="shipment_lines" data-id_shipment="' . $this->id . ' data-edit="' . $edit . '">';

                    $html .= '<table class="bimp_list_table">';
                    $html .= '<thead>';
                    $html .= '<th style="width: 30px;text-align: center">N°</th>';
                    $html .= '<th>Désignation</th>';
                    $html .= '<th>Qté</th>';
                    $html .= '<th>' . ($edit ? 'Groupes les articles' : 'Articles groupés' ) . '</th>';
                    if ($edit) {
                        $html .= '<th>Statut</th>';
                    }
                    $html .= '</thead>';
                    $html .= '<tbody>';

                    $i = 0;
                    foreach ($lines as $line) {
                        $i++;
                        $shipment_data = $line->getShipmentData((int) $this->id);
                        $product = $line->getProduct();

                        $html .= '<tr class="shipment_line_row" data-id_line="' . $line->id . '" data-num_line="' . $i . '">';
                        $html .= '<td style="width: 40px;text-align: center">' . $i . '</td>';
                        $html .= '<td>' . $line->displayLineData('desc') . '</td>';
                        $html .= '<td>';
                        if ($edit) {
                            $max = ((float) $line->qty - (float) $line->getShippedQty()) + (float) $shipment_data['qty'];
                            $decimals = 3;
                            if (BimpObject::objectLoaded($product)) {
                                if ((int) $product->getData('fk_product_type') === 0) {
                                    $decimals = 0;
                                }
                            }

                            $html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty', (float) $shipment_data['qty'], array(
                                        'data'      => array(
                                            'data_type' => 'number',
                                            'min'       => 0,
                                            'max'       => $max,
                                            'decimals'  => $decimals
                                        ),
                                        'max_label' => 1
                            ));
                        } else {
                            $html .= $shipment_data['qty'];
                        }
                        $html .= '</td>';

                        $html .= '<td>';
                        if (BimpObject::objectLoaded($product) && ((int) $product->getData('fk_product_type') === 0) && !$product->isSerialisable()) {
                            if ($edit) {
                                $html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_group_article', (int) $shipment_data['group']);
                            } else {
                                if ((int) $shipment_data['group']) {
                                    $html .= '<span class="success">OUI</span>';
                                } else {
                                    $html .= '<span class="danger">NON</span>';
                                }
                            }
                        }
                        $html .= '</td>';
                        if ($edit) {
                            $html .= '<td>';
                            $ready_qty = (float) $line->getReadyToShipQty((int) $this->id);
                            if ($ready_qty >= (float) $shipment_data['qty']) {
                                $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Prêt</span>';
                            } else {
                                $diff = (float) $shipment_data['qty'] - $ready_qty;
                                $html .= '<span style="color: #636363">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . $diff . ' unité' . ($diff > 1 ? 's' : '') . ' en attente</span>';
                            }
                            $html .= '</td>';
                        }

                        $html .= '</tr>';

                        if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                            $html .= '<tr id="shipment_line_' . $line->id . '_equipments_row" class="shipment_line_equipments_row">';
                            $html .= '<td colspan="' . ($edit ? '5' : '4') . '" style="padding-left: 60px">';

                            $html .= 'Equipements: <br/>';

                            if ($edit) {
                                $items = array();

                                if (isset($shipment_data['equipments'])) {
                                    foreach ($shipment_data['equipments'] as $id_equipment) {
                                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                        if ($equipment->isLoaded()) {
                                            $items[(int) $id_equipment] = $equipment->getData('serial');
                                        }
                                    }
                                }

                                $equipments = $line->getEquipementsToAttributeToShipment();

                                foreach ($equipments as $id_equipment) {
                                    if (!array_key_exists((int) $id_equipment, $items)) {
                                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                        if ($equipment->isLoaded()) {
                                            $items[(int) $id_equipment] = $equipment->getData('serial');
                                        }
                                    }
                                }

                                $html .= BimpInput::renderInput('check_list', 'line_' . $line->id . '_equipments', $shipment_data['equipments'], array(
                                            'items' => $items
                                ));
                            } else {
                                $equipments = array();
                                if (isset($shipment_data['equipments'])) {
                                    foreach ($shipment_data['equipments'] as $id_equipment) {
                                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                        if ($equipment->isLoaded()) {
                                            $equipments[(int) $id_equipment] = $equipment;
                                        }
                                    }
                                }

                                if (!count($equipments)) {
                                    $html .= BimpRender::renderAlerts('Aucun équipement attribué à cette expédition', 'warning');
                                } else {
                                    foreach ($equipments as $equipment) {
                                        $html .= ' - ' . $equipment->getNomUrl(1, 0, 1) . '<br/>';
                                    }
                                }
                            }

                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';

                    $html .= '<div class="ajaxResultContainer"></div>';
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    public function renderFactureFormLinesInputs()
    {
        $html = '';

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            return BimpRender::renderAlerts('ID de la commande client absent');
        }

        $id_facture = (int) $this->getData('id_facture');

        BimpObject::loadClass('bimpcommercial', 'ObjectLine');
        $lines = $commande->getChildrenObjects('lines', array(
            'type' => array(
                'in' => array(ObjectLine::LINE_PRODUCT, ObjectLine::LINE_FREE)
            )
        ));

        $qties = array();
        $equipments = array();

        foreach ($lines as $line) {
            $facture_data = null;
            if ($id_facture) {
                $facture_data = $line->getFactureData($id_facture);
            }
            $shipment_data = $line->getShipmentData((int) $this->id);
            $qty = isset($shipment_data['qty']) ? (float) $shipment_data['qty'] : 0;
            $max = (float) $line->qty - (float) $line->getBilledQty();
            if ($id_facture) {
                $qty += (float) $facture_data['qty'];
            }

            if ($qty > $max) {
                $qty = $max;
            }

            if ($qty) {
                $qties[(int) $line->id] = $qty;
            }
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

        foreach ($lines as $line) {
            if (!isset($qties[(int) $line->id]) || !(float) $qties[(int) $line->id]) {
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
            $html .= $line->displayLineData('desc');
            $html .= '</td>';
            $html .= '<td>';
            $html .= $line->displayLineData('pu_ht');
            $html .= '</td>';
            $html .= '<td>';
            $html .= $line->displayLineData('tva_tx');
            $html .= '</td>';
            $html .= '<td>';
            $html .= $line->renderFactureQtyInput($id_facture, false, (float) $qties[(int) $line->id]);
            $html .= '</td>';
            $html .= '</tr>';

            if (BimpObject::objectLoaded($product)) {
                if ($product->isSerialisable()) {
                    $shipment_data = $line->getShipmentData((int) $this->id);
                    $items = array();
                    $values = array();
                    
                    if (isset($shipment_data['equipments'])) {
                        foreach ($shipment_data['equipments'] as $id_equipment) {
                            $id_eq_fac = (int) $line->getEquipmentIdFacture((int) $id_equipment);
                            if (!$id_eq_fac || ($id_facture && $id_eq_fac === (int) $id_facture)) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (BimpObject::objectLoaded($equipment)) {
                                    $items[(int) $id_equipment] = $equipment->getData('serial');
                                    $values[] = (int) $id_equipment;
                                }
                            }
                        }
                    }

                    foreach ($line->getEquipementsToAttributeToFacture() as $id_equipment) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                        if (BimpObject::objectLoaded($equipment)) {
                            $items[(int) $id_equipment] = $equipment->getData('serial');
                        }
                    }

                    $html .= '<tr id="facture_line_' . $line->id . '_equipments" class="facture_line_equipments">';
                    $html .= '<td colspan="5">';
                    $html .= '<div style="padding-left: 45px;">';
                    $html .= '<div style="font-weight: bold; font-size: 13px; margin-bottom: 6px">Equipements: </div>';
                    $html .= BimpInput::renderInput('check_list', 'line_' . $line->id . '_facture_' . (int) $id_facture . '_equipments', $values, array(
                                'items'          => $items,
                                'max_input_name' => 'line_' . $line->id . '_facture_' . $id_facture . '_qty'
                    ));
                    $html .= '</div>';
                    $html .= '</td>';
                    $html .= '</tr>';
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

    // Traitements: 

    public function validateShipment(&$warnings = array())
    {
        $errors = array();

        if (!$this->isActionAllowed('validateShipment', $errors)) {
            return $errors;
        }

        $commande = $this->getParentInstance();
        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande client absent ou commande inexistante';
            return $errors;
        }

        if (!(int) $this->getData('id_entrepot')) {
            $errors[] = 'ID de l\'entrepôt absent';
            return $errors;
        }

        // Vérifications des quantités prêtes: 
        $lines = array();

        foreach ($commande->getChildrenObjects('lines') as $line) {
            $data = $line->getShipmentData((int) $this->id);

            if ((float) $data['qty'] > 0) {
                $line_errors = array();

                if (!$line->isReadyToShip($this->id, $line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne de commande n°' . $line->getData('position') . ' (ID ' . $line->id . ')');
                } else {
                    $lines[] = $line;
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $lines_done = array();
        foreach ($lines as $line) {
            $line_errors = $line->setShipmentShipped($this);

            if (count($line_errors)) {
                $lines_done[] = $line;
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors du traitement de la ligne de commande n°' . $line->getData('position') . ' (ID ' . $line->id . ')');
                break;
            }

            $lines_done[] = $line;
        }

        if (count($errors)) {
            foreach ($lines_done as $line) {
                $line->cancelShipmentShipped($this);
            }
            return $errors;
        }

        $this->set('status', self::BLCS_EXPEDIEE);
        $this->set('date_shipped', date('Y-m-d H:i:s'));

        $update_errors = $this->update($warnings);
        if (count($update_errors)) {
            $errors[] = BimpTools::getMsgFromArray($update_errors, 'Echec de la mise à jour de l\'expédition');
        }

//        $commande->checkIsFullyShipped();

        return $errors;
    }

    public function rebuildFacture()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $facture = $this->getChildObject('facture');
            if (!BimpObject::objectLoaded($facture)) {
                $errors[] = 'Aucune facture enregistrée pour cette expédition';
            } else {
                $commande = $this->getParentInstance();
                if (!BimpObject::objectLoaded($commande)) {
                    $errors[] = 'ID de la commande client absent ou invalide';
                } else {
                    $remises = array();
                    foreach ($facture->dol_object->lines as $line) {
                        $id_remise = (int) $this->db->getValue('societe_remise_except', 'rowid', '`fk_facture_line` = ' . (int) $line->rowid);
                        if ($id_remise) {
                            $remises[] = $id_remise;
                            $this->db->update('societe_remise_except', array(
                                'fk_facture_line' => 0
                                    ), '`rowid` = ' . (int) $id_remise);
                        }
                    }
                    $this->updateField('id_facture', 0);
                    $shipments_list = $this->getList(array(
                        'id_facture' => (int) $facture->id
                            ), null, null, 'id', 'asc', 'array', array('id'));
                    $shipments = array($this->id);
                    foreach ($shipments_list as $item) {
                        $shipments[] = (int) $item['id'];
                        $this->db->update('br_commande_shipment', array(
                            'id_facture' => 0
                                ), '`id` = ' . (int) $item['id']);
                    }
                    $cond_reglement = $facture->dol_object->cond_reglement_id;
                    $id_account = $facture->dol_object->fk_account;
                    $errors = $commande->createFacture($shipments, $cond_reglement, $id_account, $remises);

                    if (!count($errors)) {
                        $fac_warnings = array();
                        $facture->delete($fac_warnings, true);
                    }
                }
            }
        } else {
            $errors[] = 'ID de l\'expédition absent';
        }

        return $errors;
    }

    // Actions: 

    public function actionSaveLines($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Produits / services  de l\'expédition enregistrés avec succès';

        if (!isset($data['lines']) || !is_array($data['lines']) || !count($data['lines'])) {
            $errors[] = 'Aucune ligne à enregistrer';
        } else {
            $i = 0;
            foreach ($data['lines'] as $line_data) {
                $i++;

                $id_line = isset($line_data['id_line']) ? $line_data['id_line'] : 0;
                if (!$id_line) {
                    $errors[] = BimpTools::getMsgFromArray('ID de la ligne de commande client absent', 'Ligne n°' . $i);
                } else {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                    if (!BimpObject::objectLoaded($line)) {
                        $errors[] = BimpTools::getMsgFromArray('La ligne de commande client d\'ID ' . $id_line . ' n\'existe pas', 'Ligne n°' . $i);
                    } else {
                        $shipment_data = $line->getShipmentData($this->id);

                        $qty = (float) isset($line_data['qty']) ? $line_data['qty'] : 0;
                        if ($qty >= 0) {
                            $available_qty = (float) $line->qty - (float) $line->getShippedQty() + (float) $shipment_data['qty'];
                            if ($qty > $available_qty) {
                                $errors[] = 'Seules ' . $available_qty . ' unité(s) sont disponibles.<br/>Veuillez retirer ' . ($qty - $available_qty) . ' unité(s)';
                            } else {
                                $shipment_data['qty'] = $qty;
                                if (isset($line_data['group'])) {
                                    $shipment_data['group'] = (int) $line_data['group'];
                                }

                                if (isset($line_data['equipments'])) {
                                    $currents = isset($shipment_data['equipments']) ? $shipment_data['equipments'] : array();
                                    $shipment_data['equipments'] = array();
                                    $availables = $line->getEquipementsToAttributeToShipment();

                                    foreach ($line_data['equipments'] as $id_equipment) {
                                        if (!in_array((int) $id_equipment, $currents) && !in_array((int) $id_equipment, $availables)) {
                                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                            if ($equipment->isLoaded()) {
                                                $warnings[] = BimpTools::getMsgFromArray('L\'équipement ' . $id_equipment . ' - NS: ' . $equipment->getData('serial') . ' n\'est plus disponible pour cette expédition', 'Ligne n°' . $i);
                                            } else {
                                                $warnings[] = BimpTools::getMsgFromArray('L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas', 'LIgne n°' . $i);
                                            }
                                        } else {
                                            $shipment_data['equipments'][] = (int) $id_equipment;
                                        }
                                    }

                                    if (count($shipment_data['equipments']) > $qty) {
                                        $msg = 'Vous ne pouvez sélectionner que ' . $qty . ' équipement(s).<br/>Veuillez désélectionner ' . (count($shipment_data['equipments']) - $qty) . ' équipement(s)';
                                        $errors[] = BimpTools::getMsgFromArray($msg, 'Ligne n°' . $i);
                                    }
                                }

                                if (!count($errors)) {
                                    $shipments = $line->getData('shipments');
                                    $shipments[(int) $this->id] = $shipment_data;
                                    $line->set('shipments', $shipments);
                                    $line_errors = $line->update();

                                    if (count($line_errors)) {
                                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $i . ': Echec de l\'enregistrement des données de l\'expédition');
                                    }
                                }
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

    public function actionValidateShipment($data, &$success)
    {
        $warnings = array();
        $success = 'Expédition validée avec succès';

        $errors = $this->validateShipment($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelShipment($data, &$success)
    {
//        if ($this->isLoaded() && $this->getData('status') === 2) {
//            $serviceShipment = BimpObject::getInstance($this->module, 'BR_ServiceShipment');
//
//            $list = $serviceShipment->getList(array(
//                'id_commande' => (int) $this->getData('id_commande_client'),
//                'id_shipment' => (int) $this->id
//                    ), null, null, 'id', 'asc', 'array', array('id', 'id_service'));
//
//            if (!is_null($list) && count($list)) {
//                foreach ($list as $item) {
//                    if ($serviceShipment->fetch((int) $item['id'])) {
//                        $qty = (int) $serviceShipment->getData('qty');
//                        $serviceShipment->delete(true);
//                        if ($qty > 0 && $serviceShipment->fetch((int) $item['id_service'])) {
//                            $shipped = (int) $serviceShipment->getData('shipped');
//                            $service->set('shipped', $shipped - $qty);
//                            $service->update();
//                        }
//                    }
//                }
//            }
//
//            $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
//            $reservationShipment = BimpObject::getInstance($this->module, 'BR_ReservationShipment');
//
//            $list = $reservationShipment->getList(array(
//                'id_commande_client' => (int) $this->getData('id_commande_client'),
//                'id_shipment'        => (int) $this->id
//                    ), null, null, 'id', 'asc', 'array', array('id', 'ref_reservation', 'qty'));
//
//            if (!is_null($list) && count($list)) {
//                foreach ($list as $item) {
//                    if ($reservation->find(array(
//                                'id_commande_client' => (int) $this->getData('id_commande_client'),
//                                'ref'                => $item['ref_reservation'],
//                                'status'             => 300
//                            ))) {
//                        $reservation->setNewStatus(250, (int) $item['qty']);
//                        $reservation->update();
//                    }
//                }
//            }
//
//            $this->set('status', 1);
//            $this->update();
//        }
    }

    public function actionCreateFacture($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $errors[] = 'Non opérationnel';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides:

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $commande = $this->getChildObject('commande_client');

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande absent';
        } else {
            $id_entrepot = (int) $commande->getData('entrepot');
            if (!$id_entrepot) {
                $errors[] = 'ID de l\'entrepot absent';
            }

            $sql = 'SELECT MAX(num_livraison) as num FROM ' . MAIN_DB_PREFIX . 'br_commande_shipment ';
            $sql .= 'WHERE `id_commande_client` = ' . (int) $commande->id;

            $result = $this->db->execute($sql);
            $result = $this->db->db->fetch_object($result);

            if (is_null($result) || !isset($result->num)) {
                $num = 0;
            } else {
                $num = (int) $result->num;
            }

            $num++;

            $this->set('id_commande_client', $commande->id);
            $this->set('id_entrepot', $id_entrepot);
            $this->set('status', 1);
            $this->set('num_livraison', $num);
        }

        if (count($errors)) {
            return $errors;
        }

        // Vérification du non-dépassement des quantités max: 
        BimpObject::loadClass('bimpcommercial', 'ObjectLine');
        $lines = $commande->getChildrenObjects('lines', array(
            'type' => array(
                'in' => array(ObjectLine::LINE_FREE, ObjectLine::LINE_PRODUCT)
            )
        ));

        foreach ($lines as $line) {
            $qty = BimpTools::getValue('line_' . $line->id . '_qty', 0);
            $available_qty = (float) $line->qty - (float) $line->getShippedQty();
            if ($qty > $available_qty) {
                $errors[] = 'Ligne n°' . $line->getData('position') . ': il ne reste que ' . $available_qty . ' unité(s) à expédiier.<br/>Veuillez retirer ' . ($qty - $available_qty) . ' unité(s)';
            } else {
                $product = $line->getProduct();
                if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                    $equipments = BimpTools::getValue('line_' . $line->id . '_equipments', array());

                    if (count($equipments) > $qty) {
                        $errors[] = 'Ligne n°' . $line->getData('position') . ': vous ne pouvez sélectionner que ' . $qty . ' équipement(s).<br/>Veuillez désélectionner ' . (count($equipments) - $qty) . ' équipement(s).';
                    }
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            foreach ($lines as $line) {
                $line_warnings = array();

                $qty = (float) BimpTools::getValue('line_' . $line->id . '_qty', 0);

                if ($qty > 0) {
                    $line_errors = $line->setShipmentData($this, array(
                        'qty'            => $qty,
                        'group_articles' => (int) BimpTools::getValue('line_' . $line->id . '_group_articles', 0)
                            ), $line_warnings);

                    $line_errors = array_merge($line_errors, $line_warnings);

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position') . ': erreurs lors de l\'enregistrement des quantités');
                    } else {
                        $product = $line->getProduct();
                        if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                            $equipments = BimpTools::getValue('line_' . $line->id . '_equipments', array());
                            if (count($equipments)) {
                                $line_errors = $line->addEquipmentsToShipment($this->id, $equipments);
                                if (count($line_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position') . ': erreurs lors de l\'attribution des équipements');
                                }
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }
}

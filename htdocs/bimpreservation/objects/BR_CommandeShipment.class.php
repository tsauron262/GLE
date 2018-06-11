<?php

class BR_CommandeShipment extends BimpObject
{

    public static $status_list = array(
        1 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        2 => array('label' => 'Expédiée', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Annulée', 'icon' => 'times', 'classes' => array('danger')),
        4 => array('label' => 'Vérouillée', 'icon' => 'lock', 'classes' => array('important'))
    );
    public static $signed_values = array(
        0 => array('label' => 'NON', 'classes' => array('danger')),
        1 => array('label' => 'OUI', 'classes' => array('success')),
        2 => array('label' => 'Non applicable', 'classes' => array('info')),
    );

    // Getters: 

    public function getNbArticles()
    {
        $qty = 0;

        if ($this->isLoaded()) {
            foreach ($this->getChildrenObjects('reservation_shipments') as $rs) {
                $qty += (int) $rs->getData('qty');
            }
        }

        return $qty;
    }

    public function getNbServices()
    {
        $qty = 0;

        if ($this->isLoaded()) {
            foreach ($this->getChildrenObjects('service_shipments') as $ss) {
                $qty += (int) $ss->getData('qty');
            }
        }

        return $qty;
    }

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

    public function getExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $onclick = 'loadModalView(\'' . $this->module . '\', \'' . $this->object_name . '\', ' . $this->id . ', \'lines\', $(this))';
            $buttons[] = array(
                'label'   => 'Produits / services inclus',
                'icon'    => 'bars',
                'onclick' => $onclick
            );

            if (in_array((int) $this->getData('status'), array(1, 4))) {
                $buttons[] = array(
                    'label'   => 'Expédier',
                    'icon'    => 'sign-out',
                    'onclick' => $this->getJsActionOnclick('validateShipment', array(), array(
                        'form_name' => 'validation'
                    ))
                );
            }

            if (!(int) $this->getData('id_facture') && ($this->getNbArticles() > 0 || $this->getNbServices() > 0)) {
                $commande = $this->getParentInstance();
                if (BimpObject::objectLoaded($commande)) {
                    if (!(int) $commande->getData('id_facture')) {
                        $buttons[] = array(
                            'label'   => 'Créer une facture',
                            'icon'    => 'file-text-o',
                            'onclick' => $this->getJsActionOnclick('createFacture', array(), array(
                                'form_name' => 'facture'
                            ))
                        );
                    }
                }
            }
        }

        return $buttons;
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
                $url = DOL_URL_ROOT . '/bimpreservation/bl.php?id_commande=' . $this->getData('id_commande_client') . '&num_bl=' . $this->getData('num_livraison') . '&id_contact_shipment=' . (int) $this->getData('id_contact');
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

    public function getCommandeCondReglement()
    {
        if ($this->isLoaded()) {
            $commande = $this->getChildObject('commande_client');
            if (BimpObject::objectLoaded($commande)) {
                return $commande->dol_object->cond_reglement_id;
            }
        }

        return 0;
    }

    // Rendus: 

    public function renderProductsQties()
    {
        $html = '';

        if ($this->isLoaded()) {
            $resShipment = BimpObject::getInstance($this->module, 'BR_ReservationShipment');

            $rows = $resShipment->getList(array(
                'id_commande_client' => (int) $this->getData('id_commande_client'),
                'id_shipment'        => (int) $this->id
            ));

            $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

            if (count($rows)) {
                $html .= '<table class="objectlistTable">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Ref. réservation</th>';
                $html .= '<th>Produit</th>';
                $html .= '<th>N° de série</th>';
                $html .= '<th>Qté</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                foreach ($rows as $r) {
                    $product->fetch((int) $r['id_product']);
                    if ((int) $r['id_equipment']) {
                        $equipment->fetch((int) $r['id_equipment']);
                    } else {
                        $equipment->reset();
                    }
                    $html .= '<tr>';
                    $html .= '<td>' . $r['ref_reservation'] . '</td>';
                    $html .= '<td>';
                    if ($product->isLoaded()) {
                        $html .= $product->getData('ref') . ' - ' . $product->getData('label');
                    } else {
                        $html .= '<span class="danger">Erreur: produit invalide' . ((int) $r['id_product'] ? ' (ID ' . $r['id_product'] . ')' : '') . '</span>';
                    }
                    $html .= '</td>';
                    $html .= '<td>';
                    if ((int) $r['id_equipment']) {
                        if ($equipment->isLoaded()) {
                            $html .= $equipment->getData('serial');
                        } else {
                            $html .= '<span class="danger">Erreur: équipement invalide (ID ' . $r['id_equipment'] . ')</span>';
                        }
                    }
                    $html .= '</td>';
                    $html .= '<td>' . $r['qty'] . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';

                $html .= '</table>';
            } else {
                $html .= BimpRender::renderAlerts('Aucun produit inclus dans cette expédition', 'info');
            }
        }

        return $html;
    }

    public function renderServicesQties()
    {
        $html = '';

        if ($this->isLoaded()) {
            $shipments = $this->getChildrenObjects('service_shipments');

            foreach ($shipments as $key => $shipment) {
                if ((int) $shipment->getData('qty') <= 0) {
                    unset($shipments[$key]);
                }
            }

            if (count($shipments)) {
                $html .= '<table class="objectlistTable">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Service</th>';
                $html .= '<th>Qté</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                foreach ($shipments as $shipment) {
                    $html .= '<tr>';
                    $html .= '<td>';
                    $html .= $shipment->displayService('nom_url');
                    $html .= '</td>';
                    $html .= '<td>' . $shipment->getData('qty') . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';

                $html .= '</table>';
            } else {
                $html .= BimpRender::renderAlerts('Aucun service inclus dans cette expédition', 'info');
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
                            $desc.= ($desc ? ' - ' : '') . $product->label;
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

    // Actions: 

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

    public function actionValidateShipment($data, &$success)
    {
        $success = 'Expédition validée avec succès';

        $errors = array();
        $warnings = array();

        if (!in_array((int) $this->getData('status'), array(1, 4))) {
            return array('Cette expédition doit avoir le statut "' . self::$status_list[1]['label'] . '" pour pouvoire être expédiée');
        }

        $id_entrepot = (int) $this->getData('id_entrepot');
        $commande = $this->getParentInstance();

        if (!$id_entrepot) {
            $errors[] = 'Entrepot absent';
        }

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'Commande client absente ou invalide';
        } else {
            $commande = $commande->dol_object;
        }

        if (count($errors)) {
            return array(
                'errors'   => $errors,
                'warnings' => $warnings
            );
        }

        // Traitement des réservations: 
        $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
        $reservationShipment = BimpObject::getInstance($this->module, 'BR_ReservationShipment');
        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
        $id_client = $commande->socid;
        $id_contact = (int) $this->getData('id_contact');
        if (!$id_contact) {
            $contacts = $commande->getIdContact('external', 'SHIPPING');
            if (isset($contacts[0]) && $contacts[0]) {
                $id_contact = $contacts[0];
            } else {
                $contacts = $commande->getIdContact('external', 'CUSTOMER');
                if (isset($contacts[0]) && $contacts[0]) {
                    $id_contact = $contacts[0];
                }
            }
        }

        $list = $reservationShipment->getList(array(
            'id_commande_client' => (int) $this->getData('id_commande_client'),
            'id_shipment'        => (int) $this->id
                ), null, null, 'id', 'asc', 'array', array('id', 'ref_reservation', 'qty', 'id_equipment'));

        if (!is_null($list) && count($list)) {
            global $user;
            $stock_label = 'Expédition n°' . $this->getData('num_livraison') . ' pour la commande client "' . $commande->ref . '"';
            $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');
            foreach ($list as $item) {
                // Mise à jour du statut de la réservation correspondante: 
                if ($reservationShipment->fetch((int) $item['id'])) {
                    $item_qty_shipped = 0;

                    if ($reservation->find(array(
                                'id_commande_client' => (int) $this->getData('id_commande_client'),
                                'ref'                => $item['ref_reservation'],
                                'status'             => 250,
                                'id_equipment'       => (int) $item['id_equipment']
                            ))) {
                        $res_errors = $reservation->setNewStatus(300, (int) $item['qty']);
                        if (!count($res_errors)) {
                            $res_errors = $reservation->update();
                        }
                        if (count($res_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Echec de la mise à jour du statut pour la réservation de référence "' . $item['ref_reservation'] . '"');
                        }
                    } else {
                        $warnings[] = 'Réservation de référence "' . $item['ref_reservation'] . '" non trouvée pour la ligne d\'expédition d\'ID ' . $item['id'];
                    }

                    // Mise à jour des stocks et emplacement: 
                    $id_equipment = (int) $reservationShipment->getData('id_equipment');
                    if ($id_equipment) {
                        $item_qty_shipped = 1;
                        $place->reset();
                        $place_errors = $place->validateArray(array(
                            'id_equipment' => $id_equipment,
                            'type'         => BE_Place::BE_PLACE_CLIENT,
                            'id_client'    => (int) $id_client,
                            'id_contact'   => (int) $id_contact,
                            'infos'        => $stock_label,
                            'date'         => date('Y-m-d H:i:s'),
                            'code_mvt'     => $codemove
                        ));

                        if (!count($place_errors)) {
                            $place_errors = $place->create();
                        }

                        if (count($place_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement d\'ID ' . $id_equipment . ' (Réf. réservation: "' . $item['ref_reservation'] . '")');
                        }
                    } else {
                        $product = $reservationShipment->getChildObject('product');
                        if (is_null($product) || !$product->isLoaded()) {
                            $warnings[] = 'Aucun produit trouvé pour la ligne d\'expédition d\'ID ' . $reservationShipment->id . ' (Réf. réservation: "' . $item['ref_reservation'] . '"';
                        } else {
                            if ($product->isSerialisable()) {
                                $warnings[] = 'Numéro de série obligatoire pour le produit "' . $product->dol_object->label . '" (ID ' . $product->id . ')';
                            } else {
                                $item_qty_shipped = (int) $reservationShipment->getData('qty');
                                if ($product->dol_object->correct_stock($user, $id_entrepot, $item_qty_shipped, 1, $stock_label, 0, $codemove, 'commande', $commande->id) <= 0) {
                                    $warnings[] = 'Echec de la mise à jour des stocks pour le produit "' . $product->dol_object->label . '" (ID ' . $product->id . ', quantités à retirer: ' . $item_qty_shipped . ')';
                                }
                            }
                        }
                    }
                } else {
                    $warnings[] = 'Ligne d\'expédition non trouvée pour la réservation de référence "' . $item['ref_reservation'] . '"';
                }
            }
        }

        $this->set('status', 2);
        $this->set('date_shipped', date('Y-m-d H:i:s'));

        $update_errors = $this->update($warnings);
        if (count($update_errors)) {
            $warnings[] = BimpTools::getMsgFromArray($update_errors, 'Echec de la mise à jour de l\'expédition');
        }

        $commande = $this->getParentInstance();
        $commande->checkIsFullyShipped();

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

        $label = 'Expédition n° ' . $this->getData('num_livraison');

        if (!$this->isLoaded()) {
            $errors[] = 'ID de l\'expédition absent';
        } elseif (!in_array($this->getData('status'), array(1, 2, 4))) {
            $errors[] = $label . ': statut actuel invalide';
        } elseif (!isset($data['cond_reglement']) || !(int) $data['cond_reglement']) {
            $errors[] = $label . ': conditions de réglement non spécifiées';
        } elseif ((int) $this->getData('id_facture')) {
            $errors[] = $label . ': une facture a déjà été créée pour cette expédition';
        }

        $id_account = (int) (isset($data['id_account']) ? (int) $data['id_account'] : 0);

        if (!$id_account) {
            $errors[] = 'Compte financier absent';
        }
        
        $remises = (isset($data['id_remises_list']) ? $data['id_remises_list'] : array());

        if (!count($errors)) {
            $commande = BimpObject::getInstance('bimpcore', 'Bimp_Commande', (int) $this->getData('id_commande_client'));
            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = $label . ': ID de la commande client absent ou invalide';
            } else {
                $create_errors = $commande->createFacture((int) $this->id, (int) $data['cond_reglement'], $id_account, $remises);
                if (count($create_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($create_errors, $label . ': des erreurs sont survenues lors de la création de la facture');
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides:

    public function create(&$warnings = array())
    {
        $errors = array();

        $commande = $this->getChildObject('commande_client');

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande absent';
        } else {
            $id_entrepot = (int) (isset($commande->dol_object->array_options['options_entrepot']) ? $commande->dol_object->array_options['options_entrepot'] : 0);
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

        $errors = parent::create($warnings);

        if (!count($errors) && $this->isLoaded()) {
            // Création de la liste des services: 
            $services = $commande->getChildrenObjects('services');
            $serviceShipment = BimpObject::getInstance($this->module, 'BR_ServiceShipment');
            foreach ($services as $service) {
                $serviceShipment->reset();
                $service_errors = $serviceShipment->validateArray(array(
                    'id_shipment'             => (int) $this->id,
                    'id_commande_client'      => (int) $commande->id,
                    'id_commande_client_line' => (int) $service->getData('id_order_line'),
                    'id_br_order_line'        => (int) $service->id,
                    'qty'                     => 0
                ));
                if (!count($service_errors)) {
                    $service_errors = $serviceShipment->create();
                }
                if (count($service_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($service_errors, 'Echec de l\'enregistrement du service ' . $service->id . ' pour cette expédition');
                }
            }
        }
        return $errors;
    }
}

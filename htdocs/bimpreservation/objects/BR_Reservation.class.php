<?php

class BR_Reservation extends BimpObject
{

    public static $use_a_commander = false;

    const BR_RESERVATION_COMMANDE = 1;
    const BR_RESERVATION_TRANSFERT = 2;
    const BR_RESERVATION_TEMPORAIRE = 3;
    const BR_RESERVATION_SAV = 4;

    public static $status_list = array(
        0   => array('label' => 'A traiter', 'icon' => 'exclamation-circle', 'classes' => array('info')),
//        1   => array('label' => 'A commander', 'icon' => 'exclamation-circle', 'classes' => array('important')),
        2   => array('label' => 'A réserver', 'icon' => 'exclamation-circle', 'classes' => array('important')),
        3   => array('label' => 'Cmde fourn. à finaliser', 'icon' => 'cart-arrow-down', 'classes' => array('important')),
        100 => array('label' => 'En attente de réception', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        101 => array('label' => 'Reçu - Attente attribution', 'icon' => 'hourglass-start', 'classes' => array('important')),
        200 => array('label' => 'Réservé / Attribué', 'icon' => 'lock', 'classes' => array('danger')),
        201 => array('label' => 'Transfert en cours', 'icon' => 'lock', 'classes' => array('danger')),
        202 => array('label' => 'Réservé', 'icon' => 'lock', 'classes' => array('danger')),
        203 => array('label' => 'Réservé SAV', 'icon' => 'lock', 'classes' => array('danger')),
        250 => array('label' => 'Prêt pour expédition', 'icon' => 'sign-out', 'styles' => array('font-weight' => 'bold', 'color' => '#009B9B')),
        300 => array('label' => 'Expédié au client', 'icon' => 'sign-out', 'classes' => array('success')),
        301 => array('label' => 'Transféré', 'icon' => 'sign-out', 'classes' => array('success')),
        302 => array('label' => 'Reservation terminée', 'icon' => 'sign-out', 'classes' => array('success')),
        303 => array('label' => 'Reservation annulée', 'icon' => 'times', 'classes' => array('success')),
        304 => array('label' => 'Livré au client', 'icon' => 'sign-out', 'classes' => array('success')),
    );
    public static $commande_status = array(0, 2, 3, 100, 101, 200, 250, 300, 303);
    public static $transfert_status = array(201, 301, 303);
    public static $temp_status = array(202, 302, 303);
    public static $sav_status = array(203, 303, 304);
    public static $need_equipment_status = array(200, 201, 202, 250, 300, 301, 303, 304);
    public static $unavailable_status = array(200, 201, 202, 203, 250);
    public static $types = array(
        1 => array('label' => 'Commande', 'icon' => 'fas_dolly'),
        2 => array('label' => 'Transfert', 'icon' => 'far_arrow-alt-circle-right'),
        3 => array('label' => 'Réservation temporaire', 'icon' => 'far_calendar-alt'),
        4 => array('label' => 'SAV', 'icon' => 'fas_wrench')
    );
    protected $brOrderLine = null;

    // Gestion des droits users: 

    public function canDelete()
    {
        global $user;
        return (int) $user->admin;
//        return 0;
    }

    public function canEdit()
    {
        global $user;
        return (int) 1; //$user->admin;
//        return 0;
    }

    // Getters booléens: 

    public function isCommandeClient()
    {
        return (int) ((int) $this->getData('type') === self::BR_RESERVATION_COMMANDE);
    }

    public function isTransfert()
    {
        return (int) ((int) $this->getData('type') === self::BR_RESERVATION_TRANSFERT);
    }

    public function isTemporaire()
    {
        return (int) ((int) $this->getData('type') === self::BR_RESERVATION_TEMPORAIRE);
    }

    public function isSav()
    {
        return (int) ((int) $this->getData('type') === self::BR_RESERVATION_SAV);
    }

    public function isEquipment()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_equipment')) {
                return 1;
            }
        }

        return 0;
    }

    public function isProduct()
    {
        return ($this->isEquipment() ? 0 : 1);
    }

    public function isProductSerialisable()
    {
        $status = (int) $this->getData('status');

        if (!in_array($status, self::$need_equipment_status)) {
            return 0;
        }

        $product = $this->getChildObject('product');
        if (is_null($product) || !$product->isLoaded()) {
            return 0;
        }

        return $product->isSerialisable();
    }

    public function isProductNotSerialisable()
    {
        $status = (int) $this->getData('status');

        if ($status <= 100 || $status === 303) {
            return 1;
        }
        return ($this->isProductSerialisable() ? 0 : 1);
    }

    public function isOrderInvoiced()
    {
        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_commande_client'));
        if (BimpObject::objectLoaded($commande)) {
            if ((int) $commande->getData('id_facture')) {
                return 1;
            }
        }

        return 0;
    }

    public function isNewStatusAllowed($new_status, &$errors = array())
    {
        $new_status = (int) $new_status;

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la réservation absent';
            return 0;
        }

        if (!array_key_exists($new_status, self::$status_list)) {
            $errors[] = 'Le satut d\'ID ' . $new_status . ' n\'existe pas';
            return 0;
        }

        $type = (int) $this->getData('type');

        $error_label = 'Impossible de passer la réservation au statut "' . self::$status_list[$new_status]['label'] . '"';

        switch ($type) {
            case self::BR_RESERVATION_COMMANDE:
                if (!in_array($new_status, self::$commande_status)) {
                    $errors[] = $error_label . '. La réservation doit être liée à une commande client';
                    return 0;
                }
                break;

            case self::BR_RESERVATION_TRANSFERT:
                if (!in_array($new_status, self::$transfert_status)) {
                    $errors[] = $error_label . '. La réservation doit être liée à un transfert';
                    return 0;
                }
                break;

            case self::BR_RESERVATION_TEMPORAIRE:
                if (!in_array($new_status, self::$transfert_status)) {
                    $errors[] = $error_label . '. La réservation doit être de type temporaire';
                    return 0;
                }
                break;

            case self::BR_RESERVATION_SAV:
                if (!in_array($new_status, self::$sav_status)) {
                    $errors[] = $error_label . '. La réservation doit être liée à un SAV';
                    return 0;
                }
                break;
        }

        if ((int) $new_status === 0) {
            return (int) $this->isReinitialisable($errors);
        }

        return 1;
    }

    public function isReinitialisable(&$errors = array())
    {
        $status = (int) $this->getData('status');

        if ($status === 0) {
            $errors[] = 'Cette réservation est déjà au statut "' . self::$status_list[0]['label'] . '"';
            return 0;
        }

        if ($status >= 250) {
            $errors[] = 'Le statut actuel de la réservation (' . self::$status_list[$status]['label'] . ') ne permet pas sa réinitialisation';
            return 0;
        }

        switch ((int) $this->getData('type')) {
            case self::BR_RESERVATION_COMMANDE:
                if ($status === 200 && (int) $this->getData('id_equipment')) {
                    $id_equipment = (int) $this->getData('id_equipment');
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('id_commande_client_line'));
                    if (BimpObject::objectLoaded($line)) {
                        $id_shipment = (int) $line->getEquipmentIdShipment($id_equipment);
                        if ($id_shipment) {
                            $errors[] = 'L\'équipement associé a été attribué à une expédition';
                            return 0;
                        }

                        $id_facture = (int) $line->getEquipmentIdFacture($id_equipment);
                        if ($id_facture) {
                            $errors[] = 'L\'équipement associé a été attribué à une facture';
                            return 0;
                        }
                    }
                }
                break;
        }

        return 1;
    }

    // getters array: 

    public function getAvoirsArray()
    {
        $avoirs = array(
            0 => 'Créer un nouvel avoir'
        );

        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_commande_client'));

        if (BimpObject::objectLoaded($commande)) {
            $asso = new BimpAssociation($commande, 'avoirs');
            foreach ($asso->getAssociatesList() as $id_avoir) {
                $avoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_avoir);
                if ($avoir->isLoaded()) {
                    if ((int) $avoir->dol_object->statut === (int) Facture::STATUS_DRAFT) {
                        $DT = new DateTime($this->db->db->iDate($avoir->dol_object->date_creation));
                        $avoirs[(int) $id_avoir] = $avoir->dol_object->ref . ' (créé le ' . $DT->format('d / m / Y à H:i') . ')';
                    }
                }
            }
        }

        krsort($avoirs);

        return $avoirs;
    }

    public function getCommandeClientLinesArray()
    {
        $commande = $this->getChildObject('commande_client');
        if (!BimpObject::objectLoaded($commande)) {
            return array();
        }

        $id_product = (int) $this->getData('id_product');

        $lines = array();

        $n = 0;
        foreach ($commande->getLines() as $line) {
            $n++;
            if ($id_product) {
                if ((int) $line->id_product !== (int) $id_product) {
                    continue;
                }
            }

            $lines[(int) $line->id] = 'Ligne ' . $n . ' - Produit: ' . $line->displayLineData('id_product', 0, 'ref_nom', true);
        }

        return $lines;
    }

    public function getCommandeFournisseurLinesArray()
    {
        $commande = $this->getChildObject('commande_fournisseur');
        if (is_null($commande) || !isset($commande->id) || !$commande->id) {
            return array();
        }

        $lines = array();

        foreach ($commande->lines as $n => $line) {
            $label = 'Ligne ' . ($n + 1);
            if (isset($line->product_label) && $line->product_label) {
                $label .= '- produit: "' . $line->product_label . '"';
                if (isset($line->product_ref) && $line->product_ref) {
                    $label .= ' (ref: "' . $line->product_ref . '")';
                }
            } elseif (isset($line->label) && $line->label) {
                $label .= ' - ' . $line->label;
            } elseif (isset($line->description) && $line->description) {
                $label .= ' - ' . $line->description;
            }

            $lines[(int) $line->id] = $label;
        }

        return $lines;
    }

    public function getStatusListArray()
    {
        $type = (int) $this->getData('type');
        $status = array();

        switch ($type) {
            case self::BR_RESERVATION_COMMANDE:
                foreach (self::$commande_status as $key) {
                    $status[$key] = self::$status_list[$key];
                }
                break;

            case self::BR_RESERVATION_TRANSFERT:
                foreach (self::$transfert_status as $key) {
                    $status[$key] = self::$status_list[$key];
                }
                break;

            case self::BR_RESERVATION_TEMPORAIRE:
                foreach (self::$temp_status as $key) {
                    $status[$key] = self::$status_list[$key];
                }
                break;

            case self::BR_RESERVATION_SAV:
                foreach (self::$sav_status as $key) {
                    $status[$key] = self::$status_list[$key];
                }
                break;

            default:
                $status = self::$status_list;
                break;
        }

        return $status;
    }

    public function getShipmentsArray()
    {
        $shipments = array();

        $id_commande_client = $this->getIdCommandeforShipment();

        if (!$id_commande_client) {
            if (BimpTools::isSubmit('fields')) {
                $fields = BimpTools::getValue('fields', array());
                if (isset($fields['id_shipment'])) {
                    $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', (int) $fields['id_shipment']);
                    if (BimpObject::objectLoaded($shipment)) {
                        $id_commande_client = (int) $shipment->getData('id_commande_client');
                    }
                }
            }
        }
        if ($id_commande_client) {
            $cs = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
            foreach ($cs->getList(array(
                'id_commande_client' => $id_commande_client,
                'status'             => 1
            )) as $row) {
                $shipments[(int) $row['id']] = 'Expédition n°' . $row['num_livraison'];
            }
        }

        return $shipments;
    }

    // Getters params: 

    public function getEquipmentFormTitle()
    {
        $equipment = $this->getChildObject('equipment');
        if (!is_null($equipment) && $equipment->isLoaded()) {
            if ($this->isLoaded()) {
                return 'Edition de la réservation ' . $this->id . ' pour l\'équipement ' . $equipment->id . ' (serial: ' . $equipment->getData('serial') . ')';
            } else {
                return 'Ajout d\'une réservation pour l\'équipement ' . $equipment->id . ' (serial: ' . $equipment->getData('serial') . ')';
            }
        }

        return 'Erreur: aucun équipement spécifié';
    }

    public function getListExtraBtn()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            $status = (int) $this->getData('status');
            $qty = (int) $this->getData('qty');
            $product = $this->getChildObject('product');
            $type = (int) $this->getData('type');

            if ($status < 300) {
                switch ($status) {
                    // Si à traiter
                    case 0: // OK
//                        $title = 'Produits à commander';
//                        $values = htmlentities('\'{"fields": {"status": 1}}\'');
//                        $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
//                        $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
//                        $buttons[] = array(
//                            'label'   => 'A Commander',
//                            'icon'    => 'sign-in',
//                            'onclick' => $onclick,
//                            'class'   => 'newStatusButton',
//                            'attrs'   => array(
//                                'data' => array(
//                                    'new_status' => 1
//                                )
//                            )
//                        );
                        // A Reserver: 
                        $params = array();
                        if ($qty > 1) {
                            $params['form_name'] = 'new_status';
                        }

                        $buttons[] = array(
                            'label'   => 'A réserver',
                            'icon'    => 'fas_exclamation-circle',
                            'onclick' => $this->getJsActionOnclick('setNewStatus', array(
                                'status' => 2
                                    ), $params),
                            'class'   => 'newStatusButton',
                            'attrs'   => array(
                                'data' => array(
                                    'new_status' => 2
                                )
                            )
                        );


                    // Ajouter à une commande fournisseur: 
//                        if ($type === self::BR_RESERVATION_COMMANDE) {
//                            $id_commande_client = (int) $this->getData('id_commande_client');
//                            $id_entrepot = (int) $this->getData('id_entrepot');
//                            $id_product = (int) $this->getData('id_product');
//                            $ref = $this->getData('ref');
//
//                            if (!is_null($ref) && $ref && $id_entrepot && $id_product && $id_commande_client) {
//                                $title = 'Ajouter à une commande fournisseur';
//                                $values = array(
//                                    'fields' => array(
//                                        'id_reservation'     => (int) $this->id,
//                                        'ref_reservation'    => $ref,
//                                        'id_entrepot'        => (int) $id_entrepot,
//                                        'id_commande_client' => (int) $id_commande_client,
//                                        'id_product'         => (int) $id_product,
//                                        'qty'                => (int) $qty
//                                    )
//                                );
//
//                                $reservation_cmd_fourn = BimpObject::getInstance('bimpreservation', 'BR_ReservationCmdFourn');
//                                $onclick = $reservation_cmd_fourn->getJsLoadModalForm('default', $title, $values);
//
//                                $buttons[] = array(
//                                    'label'   => 'Commander',
//                                    'icon'    => 'cart-plus',
//                                    'onclick' => $onclick,
//                                );
//                            }
//                        }
//                        break;
                    // Si à réserver: 
                    case 2: // OK
                    case 101:
                        // Réserver: 
                        $params = array();
                        if ($product->isSerialisable()) {
                            $params['form_name'] = 'reserve_equipments';
                        } elseif ($qty > 1) {
                            $params['form_name'] = 'new_status';
                        }

                        $buttons[] = array(
                            'label'   => 'Réserver',
                            'icon'    => 'fas_lock',
                            'onclick' => $this->getJsActionOnclick('setNewStatus', array(
                                'status' => 200
                                    ), $params),
                            'class'   => 'newStatusButton',
                            'attrs'   => array(
                                'data' => array(
                                    'new_status' => 200
                                )
                            )
                        );
                        break;

                    // Si en attente de réception
                    case 100: // A Tester
                        // Réceptionner une commande fournisseur: 
//                        $params = array();
//                        if ($product->isSerialisable() || $qty > 1) {
//                            $params['form_name'] = 'new_status';
//                        }
//
//                        $buttons[] = array(
//                            'label'   => 'Réceptionner',
//                            'icon'    => 'fas_arrow-circle-down',
//                            'onclick' => $this->getJsActionOnclick('setNewStatus', array(
//                                'status' => 200
//                                    ), $params),
//                            'class'   => 'newStatusButton',
//                            'attrs'   => array(
//                                'data' => array(
//                                    'new_status' => 200
//                                )
//                            )
//                        );
                        break;

//                    // Si réservé / attribué
//                    case 200: // A Tester
//                        // Ajouter à une expédition: 
//                        if ($type === self::BR_RESERVATION_COMMANDE) {
//                            $id_commande_client = (int) $this->getData('id_commande_client');
//                            $ref = $this->getData('ref');
//
//                            if (!is_null($ref) && $ref && $id_commande_client) {
//                                $id_equipment = (int) $this->getData('id_equipment');
//
//                                $title = 'Ajouter à une expédition';
////                                $values = htmlentities('\'{"fields": {"id_commande_client": ' . $id_commande_client . ', "ref_reservation": "' . $ref . '", "qty": ' . $qty . ', "id_equipment": ' . $id_equipment . '}}\'');
////                                $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_ReservationShipment\', id_object: 0, ';
////                                $onclick .= 'form_name: \'default\', param_values: ' . $values . '}, \'' . $title . '\');';
//
//                                $values = array(
//                                    'fields' => array(
//                                        'id_commande_client' => (int) $id_commande_client,
//                                        'ref_reservation'    => $ref,
//                                        'qty'                => $qty,
//                                        'id_equipment'       => (int) $id_equipment,
//                                    )
//                                );
//
//                                $reservation_shipment = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');
//                                $buttons[] = array(
//                                    'label'   => 'Ajouter à une expédition',
//                                    'icon'    => 'sign-out',
//                                    'onclick' => $reservation_shipment->getJsLoadModalForm('default', $title, $values)
//                                );
//                            }
//                        }
//                        break;
                    // Si transfert en cours: 
                    case 201: // Non modifié
                        if ($type === self::BR_RESERVATION_TRANSFERT) {
                            // Transféré: 
                            if ($qty === 1) {
                                $onclick = 'setReservationStatus($(this), ' . $this->id . ', 301)';
                            } else {
                                $title = 'Produits transférés';
                                $values = htmlentities('\'{"fields": {"status": 301}}\'');
                                $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
                                $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
                            }

                            $buttons[] = array(
                                'label'   => 'Transféré',
                                'icon'    => 'sign-out',
                                'onclick' => $onclick,
                                'class'   => 'newStatusButton',
                                'attrs'   => array(
                                    'data' => array(
                                        'new_status' => 301
                                    )
                                )
                            );
                        }
                        break;

                    // Si réservé (Rés. temporaire): 
                    case 202: // Non modifié
                        // Réservation terminée
                        if ($type === self::BR_RESERVATION_TEMPORAIRE) {
                            if ($qty === 1) {
                                $onclick = 'setReservationStatus($(this), ' . $this->id . ', 302)';
                            } else {
                                $title = 'Réservations terminées';
                                $values = htmlentities('\'{"fields": {"status": 302}}\'');
                                $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
                                $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
                            }

                            $buttons[] = array(
                                'label'   => 'Réservation terminée',
                                'icon'    => 'unlock',
                                'onclick' => $onclick,
                                'class'   => 'newStatusButton',
                                'attrs'   => array(
                                    'data' => array(
                                        'new_status' => 302
                                    )
                                )
                            );
                        }
                        break;

                    // Si Réservé SAV: 
                    case 203: // Non modifié
                        if ($type === self::BR_RESERVATION_SAV) {
                            if ($qty === 1) {
                                $onclick = 'setReservationStatus($(this), ' . $this->id . ', 300)';
                            }

                            $buttons[] = array(
                                'label'   => 'Livré au client',
                                'icon'    => 'sign-out',
                                'onclick' => $onclick,
                                'class'   => 'newStatusButton',
                                'attrs'   => array(
                                    'data' => array(
                                        'new_status' => 300
                                    )
                                )
                            );
                        }
                        break;
                }

                switch ($type) {
                    case self::BR_RESERVATION_COMMANDE: // A Tester
                        // Réinitialisation de la réservation: 
                        if ($this->isReinitialisable()) {
                            $params = array();
                            if ($qty > 1) {
                                $params['form_name'] = 'new_status';
                            }
                            $onclick = 'setReservationStatus($(this), ' . $this->id . ', 0)';
                            $buttons[] = array(
                                'label'   => 'Réinitialiser',
                                'icon'    => 'undo',
                                'onclick' => $this->getJsActionOnclick('setNewStatus', array(
                                    'status' => 0
                                        ), $params),
                                'class'   => 'newStatusButton',
                                'attrs'   => array(
                                    'data' => array(
                                        'new_status' => 0
                                    )
                                )
                            );
                        }
                        break;

                    case self::BR_RESERVATION_TEMPORAIRE:
                    case self::BR_RESERVATION_TRANSFERT:
                    case self::BR_RESERVATION_SAV: // Non modifié
                        // Annulation de la réservation: 
                        $onclick = 'setReservationStatus($(this), ' . $this->id . ', 303)';
                        $buttons[] = array(
                            'label'   => 'Annuler la réservation',
                            'icon'    => 'times-circle',
                            'onclick' => $onclick,
                            'class'   => 'newStatusButton',
                            'attrs'   => array(
                                'data' => array(
                                    'new_status' => 303
                                )
                            )
                        );
                        break;
                }
            }

            // Retrait de la commande client: 
//            if (in_array($status, array(0, 2, 200, 303))) {
//                $buttons[] = array(
//                    'label'   => 'Retirer de la commande client',
//                    'icon'    => 'times-circle',
//                    'onclick' => $this->getJsActionOnclick('removeFromOrder', array(), array(
//                        'form_name' => 'remove'
//                    )),
//                );
//            }
        }

        return $buttons;
    }

    public function getCommandesListBulkActions()
    {
        return array(
            array(
                'label'   => 'Réinitialiser',
                'icon'    => 'undo',
                'onclick' => 'setSelectedReservationStatus($(this), \'list_id\', 0);'
            ),
            array(
                'label'   => 'Ajouter à une expédition',
                'icon'    => 'sign-out',
                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'addToShipment\', {id_commande_client: list_filter_id_commande_client}, \'shipment\', null)'
            )
        );
    }

    public function getEquipmentCreateFormValues()
    {
        if (!$this->isLoaded() || !(int) $this->getData('id_product')) {
            return array();
        }

        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $this->getData('id_product'));

        if (!BimpObject::objectLoaded($product) || !$product->isSerialisable()) {
            return array();
        }

        BimpObject::loadClass('bimpequipment', 'BE_Place');

        return array(
            'fields'  => array(
                'id_product' => (int) $product->id
            ),
            'objects' => array(
                'places' => array(
                    'fields' => array(
                        'type'        => BE_Place::BE_PLACE_ENTREPOT,
                        'id_entrepot' => (int) $this->getData('id_entrepot')
                    )
                )
            )
        );
    }

    // Getters valeurs: 

    public function getIdCommandeforShipment()
    {
        $id_commande_client = (int) $this->getData('id_commande_client');

        if (!$id_commande_client) {
            if (BimpTools::isSubmit('extra_data')) {
                $extra_data = BimpTools::getValue('extra_data', array());
                if (isset($extra_data['id_commande_client'])) {
                    $id_commande_client = (int) $extra_data['id_commande_client'];
                }
            }
        }

        return (int) $id_commande_client;
    }

    public function getBrOrderLine()
    {
        if (is_null($this->brOrderLine)) {
            if ((int) $this->getData('id_commande_client_line')) {
                $orderLine = BimpObject::getInstance($this->module, 'BR_OrderLine');
                if ($orderLine->find(array('id_order_line' => (int) $this->getData('id_commande_client_line')))) {
                    $this->brOrderLine = $orderLine;
                }
            }
        }

        return $this->brOrderLine;
    }

    public function getRemovableQty()
    {
        if ($this->isLoaded()) {
            $orderLine = $this->getBrOrderLine();
            if (BimpObject::objectLoaded($orderLine)) {
                $qty = (int) $orderLine->getRemovableQty();
                if ((int) $this->getData('qty') < $qty) {
                    return (int) $this->getData('qty');
                } else {
                    return $qty;
                }
            }
        }

        return 0;
    }

    // Affichage:

    public function displayEquipment()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_equipment')) {
                return $this->displayData('id_equipment', 'nom_url');
            } else {
                if (in_array((int) $this->getData('status'), self::$need_equipment_status)) {
                    $product = $this->getChildObject('product');
                    if (!is_null($product) && $product->isLoaded()) {
                        if ($product->isSerialisable()) {
                            return '<span class="danger">Attribution nécessaire</span>';
                        } else {
                            return '<span class="warning">Non sérialisable</span>';
                        }
                    }
                }
            }
        }

        return '';
    }

    public function displayProductAvailableQty()
    {
        ini_set('display_errors', 1);

        $product = $this->getChildObject('product');
        if (BimpObject::objectLoaded($product)) {
            $stocks = $product->getStocksForEntrepot((int) $this->getData('id_entrepot'));
            if (isset($stocks['dispo'])) {
                return $stocks['dispo'];
            }
            return '<span class="danger">inconnu</span>';
        }
        return '';
    }

    public function displayLinkedObject()
    {
        switch ((int) $this->getData('type')) {
            case self::BR_RESERVATION_COMMANDE:
                $obj = $this->getChildObject('commande_client');
                if (BimpObject::objectLoaded($obj)) {
                    return $obj->getNomUrl(1, 1, 1, 'full');
                }
                break;

            case self::BR_RESERVATION_TRANSFERT:
                $obj = $this->getChildObject('transfer');
                if (BimpObject::objectLoaded($obj)) {
                    return $obj->getNomUrl(1, 0, 1, 'default');
                }
                break;

            case self::BR_RESERVATION_SAV:
                $obj = $this->getChildObject('sav');
                if (BimpObject::objectLoaded($obj)) {
                    return $obj->getNomUrl(1, 1, 1, 'default');
                }
                break;

//            case self::BR_RESERVATION_TEMPORAIRE:
//                break;
        }
    }

    // Rendus: 

    public function renderNewStatusQtyInput()
    {
        return BimpInput::renderInput('qty', 'qty', (int) $this->getData('qty'), array(
                    'data' => array(
                        'data_type' => 'number',
                        'decimals'  => 0,
                        'unsigned'  => 1,
                        'min'       => 1,
                        'max'       => (int) $this->getData('qty')
                    )
        ));
    }

    public function renderRemoveQtyInput()
    {
        $removableQty = (int) $this->getRemovableQty();
        return BimpInput::renderInput('qty', 'qty', (int) $removableQty, array(
                    'data' => array(
                        'data_type' => 'number',
                        'decimals'  => 0,
                        'unsigned'  => 1,
                        'min'       => 1,
                        'max'       => (int) $removableQty
                    )
        ));
    }

    public function renderReserveEquipmentsInput()
    {
        $html = '';

        $errors = array();
        if (!$this->isLoaded()) {
            $errors[] = 'ID de la réservation absent';
        } else {
            $product = $this->getChildObject('product');
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Produit absent';
            } elseif (!$product->isSerialisable()) {
                $errors[] = 'Produit non sérialisable';
            } else {
                $entrepot = $this->getChildObject('entrepot');
                if (!BimpObject::objectLoaded($entrepot)) {
                    if ((int) $this->getData('id_entrepot')) {
                        $errors[] = 'L\'entrepôt d\'ID ' . $this->getData('id_entrepot') . ' n\'existe pas';
                    } else {
                        $errors[] = 'Entrepôt absent';
                    }
                } else {
                    BimpObject::loadClass('bimpequipment', 'Equipment');
                    $equipments = Equipment::getAvailableEquipmentsArray((int) $entrepot->id, (int) $product->id);
                    $values = array();
                    $hidden = false;
                    ;

                    if (!count($equipments)) {
                        $html .= BimpRender::renderAlerts('Aucun équipement disponible dans l\'entrepôt ' . $entrepot->getNomUrl(1), 'warning');
                        $hidden = true;
                    } else {
                        $selected_equipments = BimpTools::getPostFieldValue('equipments', array());

                        if (is_array($selected_equipments) && !empty($selected_equipments)) {
                            foreach ($selected_equipments as $id_equipment) {
                                if (array_key_exists((int) $id_equipment, $equipments) && !array_key_exists((int) $id_equipment, $values)) {
                                    $values[(int) $id_equipment] = $equipments[(int) $id_equipment];
                                }
                            }
                        }
                    }
                    $input_name = 'equipments';
                    $max_values = (int) $this->getData('qty');

                    $content = '<div style="display: ' . ($hidden ? 'none' : 'inline-block') . '; margin-right: 15px;">';
                    $content .= '<span class="small">Numéro de série: </span><br/>';
                    $content .= BimpInput::renderInput('text', 'search_serial', '', array(
                                'style'       => 'border: 1px solid #DCDCDC',
//                                'extra_class' => 'auto_focus'
                    ));
                    $content .= '</div>';

                    $content .= BimpInput::renderInput('select', $input_name . '_add_value', '', array(
                                'options' => $equipments
                    ));

                    $html .= '<h4>Equipements disponibles dans l\'entrepôt: ' . $entrepot->getNomUrl(1) . '<br/><br/>';
                    $html .= 'Pour le produit ' . $product->getNomUrl(1) . '</h4><br/>';

                    $html .= BimpInput::renderMultipleValuesInput($this, $input_name, $content, $values, '', false, false, false, $max_values);
                }
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    // Gestion des réservations:

    public function createReservationsFromCommandeClient($id_entrepot, $id_commande_client)
    {
        if (is_null($id_commande_client) || !$id_commande_client) {
            return array('ID de la commande client absent');
        }

        if (!class_exists('Commande')) {
            require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        }

        $errors = array();

        global $db;

        $commande = new Commande($db);

        if ($commande->fetch($id_commande_client) <= 0) {
            $errors[] = 'Echec du chargement de la commande d\'ID ' . $id_commande_client . ' - ' . $commande->error;
        } else {
            foreach ($commande->lines as $i => $line) {
                $br_orderLine = BimpObject::getInstance($this->module, 'BR_OrderLine');
                $add_errors = $this->createFromCommandeClientLine($id_entrepot, $commande, $line, $br_orderLine);
                if (count($add_errors)) {
                    $errors[] = 'Echec de l\'ajout de la ligne n°' . $i;
                    $errors = array_merge($errors, $add_errors);
                }
            }
        }
        return $errors;
    }

    public function createFromCommandeClientLine($id_entrepot, Commande $commande, OrderLine $line, BR_OrderLine $br_orderLine)
    {
        $errors = array();
//        $this->reset();
//
//        if (is_null($line) || !isset($line->id) || !$line->id) {
//            return array('Ligne de Commande client invalide');
//        }
//
//        if (!isset($line->fk_product) || !$line->fk_product) {
//            return array();
//        }
//
//        $this->set('id_product', $line->fk_product);
//        $product = $this->getChildObject('product');
//        if (is_null($product) || !$product->isLoaded()) {
//            return array('Produit d\'ID ' . $line->fk_product . ' invalide');
//        } else {
////            $service = BimpObject::getInstance($this->module, 'BR_Service');
////            $service_errors = $service->validateArray(array(
////                'id_commande_client'      => (int) $commande->id,
////                'id_commande_client_line' => (int) $line->id,
////                'qty'                     => (int) $line->qty,
////                'shipped'                 => 0
////            ));
////            if (!count($service_errors)) {
////                $service_errors = $service->create();
////            }
////            if (count($service_errors)) {
////                $errors[] = 'Echec de l\'ajout du service pour la ligne d\'ID ' . $line->id;
////                $errors = array_merge($errors, $service_errors);
////            }
//            if (is_null($commande) || !isset($commande->id) || !$commande->id) {
//                return array('Commande client invalide');
//            }
//
//            // Création de la ligne de commande: 
//            $errors = $br_orderLine->createFromOrderLine($commande, $line, $product);
//
//            if (count($errors)) {
//                return $errors;
//            }
//
//            if ((int) $product->getData('fk_product_type') === 0) {
//                // Création de la réservation
//                $errors = $this->set('id_commande_client_line', $line->id);
//                $this->set('id_commande_client', $line->fk_commande);
//
//                $errors = array_merge($errors, $this->set('id_entrepot', $id_entrepot));
//
//                if (count($errors)) {
//                    return $errors;
//                }
//
//                $this->set('id_client', $commande->socid);
//                $this->set('date_from', date('Y-m-d H:i:s'));
//                if (isset($commande->user_author_id)) {
//                    $this->set('id_commercial', $commande->user_author_id);
//                }
//
//                $this->set('type', self::BR_RESERVATION_COMMANDE);
//
//                $qty = (int) $line->qty;
//                $this->set('qty', $qty);
//                $this->set('status', 0);
//                $this->set('id_equipment', 0);
//
//                $errors = array_merge($errors, $this->create($warnings, true));
//            }
//        }
//
        return $errors;
    }

    public function setEquipment($id_equipment, &$errors = array())
    {
        if (is_null($id_equipment) || !$id_equipment) {
            $errors[] = 'ID de l\'équipement absent';
            return false;
        }

        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
        if (!$this->checkEquipment($equipment, $errors)) {
            return false;
        }

        $this->set('id_equipment', (int) $id_equipment);

        return true;
    }

    public function checkEquipment($equipment = null, &$errors = array())
    {
        if (is_null($equipment)) {
            $equipment = $this->getChildObject('equipment');
        }

        if (!$equipment->isLoaded()) {
            $errors[] = 'Equipement d\'ID ' . (int) $this->getData('id_equipment') . ' non trouvé';
            return 0;
        }

        $id_product = (int) $this->getData('id_product');
        if (!$id_product) {
            $this->set('id_product', (int) $equipment->getData('id_product'));
        }

        if ($id_product !== (int) $equipment->getData('id_product')) {
            $errors[] = 'L\'équipement spécifié ne correspond pas au produit associé à cette réservation';
            return 0;
        }

        $id_entrepot = (int) $this->getData('id_entrepot');
        if (!$equipment->isAvailable($id_entrepot, $errors, array(
                    'id_reservation' => ($this->isLoaded() ? (int) $this->id : 0)
                ))) {
            return 0;
        }

        return 1;
    }

    public function findEquipmentToReceive($id_commande_client, $serial, &$errors = array())
    {
        if (is_null($id_commande_client) || !$id_commande_client) {
            $errors[] = 'ID de la commande absent';
            return 0;
        }

        if (is_null($serial) || !$serial) {
            $errors[] = 'Numéro de série absent';
            return 0;
        }

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        $filters = array(
            'serial' => array(
                'in' => array('\'' . $serial . '\'', '\'S' . $serial . '\'')
            )
        );
        $list = $equipment->getList($filters, null, null, 'id', 'desc', 'array', array('id'));
        if (!is_null($list) && count($list)) {
            if (count($list) > 1) {
                $msg = 'Plusieurs équipements ont été trouvés pour ce numéro de série.<br/>';
                $msg .= 'Veuillez utiliser le bouton "Réceptionner" à la réservation correspondante.';
                $errors[] = $msg;
            } else {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $list[0]['id']);
                if (!$equipment->isLoaded()) {
                    $errors[] = 'Equipement trouvé non valide';
                }
            }
        } else {
            $errors[] = 'Aucun équipement trouvé pour ce numéro de série';
        }

        if (!count($errors) && $equipment->isLoaded()) {
            $id_product = (int) $equipment->getData('id_product');

            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
            if ($reservation->find(array(
                        'id_commande_client' => (int) $id_commande_client,
                        'status'             => array(
                            'operator' => '<',
                            'value'    => 200
                        ),
                        'id_product'         => $id_product
                    ))) {
                $errors = $reservation->setNewStatus(200, null, $equipment->id);
                if (!count($errors)) {
                    $errors = $reservation->update();
                    return $reservation->id;
                }
            } else {
                $errors[] = 'Aucune réservation en lien avec cette commande trouvée pour l\'équipement ' . $equipment->id . ' (serial: ' . $equipment->getData('serial') . ')' . ' ' . $id_product . ', ' . $id_commande_client;
            }
        }

        return 0;
    }

    public function setNewStatus($status, $qty = null, $id_equipment = null)
    {
        $status = (int) $status;
        if (!$this->isLoaded()) {
            return array('ID de la réservation absent');
        }

        if (is_null($status)) {
            return array('Nouveau statut absent');
        }

        if (!array_key_exists($status, $this->getStatusListArray())) {
            return array('Nouveau statut invalide pour ce type de réservation');
        }

        $errors = array();

        if (!$this->isNewStatusAllowed($status, $errors)) {
            return $errors;
        }

        $ref = $this->getData('ref');

        if (!$ref) {
            return array('Référence de la réservation absente');
        }

        if ((int) $this->getData('id_equipment') && (int) $this->getData('type') === self::BR_RESERVATION_COMMANDE && $status < 200) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('id_commande_client_line'));
            if (BimpObject::objectLoaded($line)) {
                $line_errors = $line->removeEquipmentFromShipment((int) $this->getData('id_equipment'));

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position') . ', statut "' . self::$status_list[(int) $this->getData('status')]['label'] . '"');
                    return $errors;
                }
            }
        }

        $this->set('status', $status);

        $current_status = (int) $this->getInitData('status');

        if ($status === $current_status) {
            return array('Cette réservation a déjà ce statut');
        }

        if (in_array($status, self::$need_equipment_status) && ($current_status < 200 || ($this->getData('type') === self::BR_RESERVATION_SAV)) && $this->isProductSerialisable()) {
            if (is_null($id_equipment) || !$id_equipment) {
                return array('Produit sérialisable: équipement obligatoire');
            }

            if (!$this->setEquipment($id_equipment, $errors)) {
                return $errors;
            }
        }

        $current_qty = (int) $this->getSavedData('qty');

        if (is_null($qty) || !$qty) {
            $qty = $current_qty;
        }

        if (!in_array($status, self::$need_equipment_status) && (int) $this->getData('id_equipment')) {
            $this->updateField('id_equipment', 0);
        }

//        if (!$status && (in_array($current_status, array(3, 100))) && ((int) $this->getData('type') === self::BR_RESERVATION_COMMANDE)) {
//            $rcf = BimpObject::getInstance($this->module, 'BR_ReservationCmdFourn');
//            $list = $rcf->getList(array(
//                'ref_reservation' => $ref
//            ));
//
//            if (!is_null($list) && count($list)) {
//                foreach ($list as $item) {
//                    $rcf = BimpCache::getBimpObjectInstance($this->module, 'BR_ReservationCmdFourn', (int) $item['id']);
//                    if ($rcf->isLoaded()) {
//                        $delete = true;
//                        if ((int) $rcf->getData('id_commande_fournisseur')) {
//                            $remove_errors = array();
//                            if (!$rcf->removeFromCommandeFournisseur($remove_errors)) {
//                                $errors = array_merge($errors, $remove_errors);
//                                $delete = false;
//                            }
//                        }
//
//                        if ($delete) {
//                            $del_warnings = array();
//                            $delete_errors = $rcf->delete($del_warnings, true);
//                            if (count($delete_errors)) {
//                                $errors[] = 'Echec de la suppression de la ligne de commande fournisseur d\'ID ' . $item['id'];
//                                $errors = array_merge($errors, $delete_errors);
//                                $qty -= (int) $rcf->getData('qty');
//                            }
//                        } else {
//                            $qty -= (int) $rcf->getData('qty');
//                        }
//                    }
//                }
//            }
//
//            if ($qty <= 0) {
//                return $errors;
//            }
//        }

        if ($this->isProductSerialisable()) {
            $equipment = $this->getChildObject('equipment');
            if (is_null($equipment) || !$equipment->isLoaded()) {
                $errors[] = 'Equipement invalide - ' . $this->getData('id_equipment') . ' - ' . $id_equipment;
            } else {
                if ($current_qty > 1) {
                    $new_reservation = BimpObject::getInstance($this->module, $this->object_name, $this->id);
                    $new_reservation->id = null;
                    $new_reservation->set('qty', 1);
                    $new_reservation->set('id_equipment', $equipment->id);
                    $new_reservation->set('status', $status);
                    $new_reservation->set('ref', $ref);
                    $new_errors = $new_reservation->create();

                    if (count($new_errors)) {
                        $errors[] = 'Echec de la création d\'une nouvelle réservation pour le numéro de série "' . $equipment->getData('serial') . '"';
                        $errors = array_merge($errors, $new_errors);
                    } else {
                        $this->set('status', $current_status);
                        $this->set('qty', $current_qty - 1);
                        $this->set('id_equipment', 0);
                        $this->update();
                    }
                } else {
                    $this->set('qty', 1);
                    $this->set('id_equipment', $equipment->id);
                    $this->update();
                }
            }
        } else {
            $product = $this->getChildObject('product');
            $id_equipment = (int) $this->getData('id_equipment');
            if (!$id_equipment && (is_null($product) || !$product->isLoaded())) {
                $errors[] = 'Produit invalide';
            }

            if ($qty > $current_qty) {
                $qty = $current_qty;
            }

            $old_reservation = BimpObject::getInstance($this->module, $this->object_name);

            if (!$id_equipment && $old_reservation->find(array(
                        'ref'          => $ref,
                        'status'       => (int) $status,
                        'id_equipment' => 0
                    ))) {
                if ($qty < $current_qty) {
                    $old_reservation->set('qty', ((int) $old_reservation->getData('qty') + $qty));
                    $update_errors = $old_reservation->update();

                    if (count($update_errors)) {
                        $errors[] = 'Echec de la mise à jour de la réservation ' . $old_reservation->id;
                        $errors = array_merge($errors, $update_errors);
                    } else {
                        $this->set('qty', (int) ($current_qty - $qty));
                        $this->set('status', $current_status);
                        $this->update();
                    }
                } else {
                    $new_qty = (int) ($qty + (int) $old_reservation->getData('qty'));
                    $id_old_reservation = $old_reservation->id;
                    $delete_warnings = array();
                    $delete_errors = $old_reservation->delete($delete_warnings, true);
                    if (count($delete_errors)) {
                        $errors[] = 'Echec de la suppression de la réservation ' . $id_old_reservation;
                        $errors = array_merge($errors, $delete_errors);
                    } else {
                        $this->set('qty', $new_qty);
                        $this->set('id_equipment', 0);
                        $this->update();
                    }
                }
            } elseif ($qty < $current_qty) {
                $new_reservation = BimpObject::getInstance($this->module, $this->object_name, $this->id);
                $new_reservation->id = null;
                $new_reservation->set('id', null);
                $new_reservation->set('qty', $qty);
                $new_reservation->set('status', $status);
                $new_reservation->set('ref', $ref);
                $new_errors = $new_reservation->create();

                if (count($new_errors)) {
                    $errors[] = 'Echec de la création d\'une nouvelle réservation pour ' . $qty . ' produit' . ($qty > 1 ? 's' : '') . ' "' . $product->getData('label') . '"';
                    $errors = array_merge($errors, $new_errors);
                } else {
                    $this->set('id_equipment', 0);
                    $this->set('status', $current_status);
                    $this->set('qty', ($current_qty - $qty));
                    $up_errors = $this->update();
                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Erreurs lors de la mise à jour de la réservation actuelle');
                    }
                }
            } else {
                $this->set('qty', $qty);
                $this->set('status', $status);
                $up_errors = $this->update();
                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Erreurs lors de la mise à jour de la réservation actuelle');
                }
            }
        }

        return $errors;
    }

    public function removeFromOrder($qty, $defective, $id_avoir = null)
    {
        $qty = (int) $qty;

        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_commande_client'));
        if (BimpObject::objectLoaded($commande)) {
            $errors = $commande->removeOrderLine((int) $this->getData('id_commande_client_line'), (int) $qty, $id_avoir);
            if (!count($errors)) {
                if (isset($defective) && (int) $defective) {
                    if ((int) $this->getData('id_equipment')) {
                        $id_entrepot = (int) BimpCore::getConf('defective_id_entrepot');
                        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                        $place_errors = $place->validateArray(array(
                            'id_equipment' => (int) $this->getData('id_equipment'),
                            'type'         => BE_Place::BE_PLACE_ENTREPOT,
                            'id_entrepot'  => (int) $id_entrepot,
                            'infos'        => 'Retrait de la commande "' . $commande->dol_object->ref . '" - Equipement défectueux',
                            'date'         => date('Y-m-d H:i:s'),
                            'code_mvt'     => dol_print_date(dol_now(), '%y%m%d%H%M%S')
                        ));

                        if (!count($place_errors)) {
                            $place_errors = $place->create();
                        }

                        if (count($place_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement (Produits défectueux) pour l\'équipement d\'ID ' . $this->getData('id_equipment'));
                        }
                    } else {
                        $product = $this->getChildObject('product');
                        if (!BimpObject::objectLoaded($product)) {
                            $errors[] = 'Aucun produit trouvé pour réservation d\'ID ' . $this->id;
                        } else {
                            global $user;
                            $stock_label = 'Retrait de  la commande "' . $commande->dol_object->ref . '" (Produit défectueux)';
                            if ($product->dol_object->correct_stock($user, (int) $this->getData('id_entrepot'), $qty, 1, $stock_label, 0, dol_print_date(dol_now(), '%y%m%d%H%M%S'), 'commande', (int) $this->getData('id_commande_client')) <= 0) {
                                $errors[] = 'Echec de la mise à jour des stocks entrepots pour le produit "' . $product->dol_object->label . '" (ID ' . $product->id . ', quantités: ' . $qty . ')';
                            } elseif ($product->dol_object->correct_stock($user, (int) BimpCore::getConf('defective_id_entrepot'), $qty, 0, $stock_label, 0, dol_print_date(dol_now(), '%y%m%d%H%M%S'), 'commande', (int) $this->getData('id_commande_client')) <= 0) {
                                $errors[] = 'Echec de la mise à jour des stocks entrepots pour le produit "' . $product->dol_object->label . '" (ID ' . $product->id . ', quantités: ' . $qty . ')';
                            }
                        }
                    }
                }

                if ((int) $this->getData('qty') > (int) $qty) {
                    $new_qty = (int) $this->getData('qty') - (int) $qty;
                    $this->set('qty', (int) $new_qty);
                    $up_errors = $this->update();
                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la réservation correspondante');
                    }
                } else {
                    $del_warnings = array();
                    $del_errors = $this->delete($del_warnings, true);
                    if (count($del_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression de la réservation correspondante');
                    }
                }
            }

            $commande->checkIsFullyShipped();
            $commande->checkIsFullyInvoiced();
        } else {
            $errors[] = 'ID de la commande client absent ou invalide';
        }

        return $errors;
    }

    // Actions: 

    public function actionSetNewStatus($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Statut mis à jour avec succès';

        if (!isset($data['status'])) {
            $errors[] = 'Nouveau statut absent';
        } elseif ($this->isNewStatusAllowed((int) $data['status'], $errors)) {
            if (isset($data['equipments'])) {
                if (!count($data['equipments'])) {
                    $errors[] = 'Aucun équipement sélectionné';
                } else {
                    foreach ($data['equipments'] as $id_equipment) {
                        $res_errors = $this->setNewStatus((int) $data['status'], 1, $id_equipment);
                        if (count($res_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($res_errors, 'Equipement ' . $id_equipment);
                        }
                    }
                }
            } else {
                $qty = isset($data['qty']) ? (int) $data['qty'] : null;
                $id_equipment = isset($data['id_equipment']) ? (int) $data['id_equipment'] : null;

                $errors = $this->setNewStatus((int) $data['status'], $qty, $id_equipment);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddToShipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réservation ' . $this->id . ': ajout à l\'expédition effectué avec succès';

        if (!isset($data['id_shipment']) || !$data['id_shipment']) {
            $errors[] = 'Réservation ' . $this->id . ': ajout à une expédition impossible - ID de l\'expédition absent';
        } elseif ((int) $this->getData('status') !== 200) {
            $errors[] = 'Réservation ' . $this->id . ': ajout à une expédition impossible - statut invalide';
        } else {
            if (!isset($data['qty'])) {
                $data['qty'] = (int) $this->getData('qty');
            }

            if (!isset($data['group_articles'])) {
                $data['group_articles'] = 0;
            }

            if ((int) $data['qty'] <= 0) {
                $errors[] = 'Réservation ' . $this->id . ': ajout à une expédition impossible - quantité invalide';
            } else {
                $rs = BimpObject::getInstance($this->module, 'BR_ReservationShipment');
                $rs_errors = $rs->validateArray(array(
                    'ref_reservation' => $this->getData('ref'),
                    'id_equipment'    => (int) $this->getData('id_equipment'),
                    'id_shipment'     => (int) $data['id_shipment'],
                    'qty'             => (int) $data['qty'],
                    'group_articles'  => (int) $data['group_articles']
                ));
                if (!count($rs_errors)) {
                    $rs_errors = $rs->create($warnings);
                }
                if (count($rs_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($rs_errors, 'Réservation ' . $this->id . ': échec de la création de la ligne d\'expédition');
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveFromOrder($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Produit(s) retirés de la commande avec succès';

        if (!isset($data['id_avoir'])) {
            $data['id_avoir'] = 0;
        }

        if (!isset($data['qty']) || (int) $data['qty'] <= 0) {
            $errors[] = 'Quantités à retirer absentes ou invalides';
        } else {
            $errors = $this->removeFromOrder((int) $data['qty'], isset($data['defective']) ? (int) $data['defective'] : 0, $data['id_avoir']);
        }

        if (!count($errors)) {
            
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Validation des données: 

    protected function validateCommande()
    {
        $errors = array();

        $status = (int) $this->getData('status');
        if (!in_array($status, self::$commande_status)) {
            $errors[] = 'Statut invalide pour ce type de réservation';
        }

        $id_commande_client = (int) $this->getData('id_commande_client');
        if (!$id_commande_client) {
            $errors[] = 'Aucune commande client spécifiée';
        }

        $id_line = (int) $this->getData('id_commande_client_line');
        if (!$id_line) {
            $errors[] = 'Aucune ligne de commande client spécifiée';
        }

        if (count($errors)) {
            return $errors;
        }

        $commande = $this->getChildObject('commande_client');
        if (BimpObject::objectLoaded($commande)) {
            $this->set('id_client', (int) $commande->getData('fk_soc'));
        } else {
            return array('Commande invalide');
        }

        $equipment = $this->getChildObject('equipment');
        if (BimpObject::objectLoaded($equipment)) {
            $this->set('id_product', (int) $equipment->getData('id_product'));
        }

        $lines = $this->getCommandeClientLinesArray();
        if (!isset($lines[$id_line])) {
            $errors[] = 'ID de la ligne de commande client invalide';
            return $errors;
        }

        $id_product = (int) $this->getData('id_product');

        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
        if ($line->isLoaded()) {
            if ((int) $line->id_product) {
                if (!$id_product) {
                    $id_product = (int) $line->id_product;
                    $this->set('id_product', (int) $line->id_product);
                } elseif ($id_product !== (int) $line->id_product) {
                    $errors[] = 'Le produit sélectionné ne correspond pas à la ligne de commande sélectionnée';
                }
            }
        }

        $product = $this->getChildObject('product');
        if (!BimpObject::objectLoaded($product)) {
            $errors[] = 'Aucun produit ' . ($status !== 100 ? 'ou équipement ' : '') . 'sélectionné';
        } elseif ($status !== 303 && $status >= 200 && $product->isSerialisable() && (is_null($equipment) || !$equipment->isLoaded())) {
            $errors[] = 'Produit sérialisable: sélection d\'un équipement obligatoire';
        }

        if ($status === 100) {
            $this->set('id_equipment', 0);
        }

        return $errors;
    }

    protected function validateTransfert()
    {
        $errors = array();

        $status = (int) $this->getData('status');
        if (!in_array($status, self::$transfert_status)) {
            $errors[] = 'Statut invalide pour ce type de réservation';
        }

        $equipment = $this->getChildObject('equipment');
        $id_product = 0;
        if ($equipment->isLoaded()) {
            $id_product = (int) $equipment->getData('id_product');
            $this->set('id_product', $id_product);
        } else {
            $id_product = (int) $this->getData('id_product');
        }

        $equipementOblige = true;
        if ($id_product) {
            $product = $this->getChildObject('product');
            if (is_null($product) || !$product->isLoaded()) {
                $errors[] = 'Produit introuvable';
            } else
                $equipementOblige = $product->isSerialisable();
        }

        if ($this->getData('id_equipment')) {
            if ($this->getData("status") < 300)
                $this->checkEquipment($equipment, $errors);
            $this->set('qty', 1);
        } else {
            if ($equipementOblige)
                $errors[] = 'Produit sérialisable: équipement obligatoire';
            $this->set('id_equipment', 0);
        }

        $id_transfert = (int) $this->getData('id_transfert');
        if (!$id_transfert) {
            $errors[] = 'ID du transfert correspondant absent';
        }

        return $errors;
    }

    protected function validateTemporaire()
    {
        $errors = array();

        $status = (int) $this->getData('status');
        if (!in_array($status, self::$temp_status)) {
            $errors[] = 'Statut invalide pour ce type de réservation';
        }

        return $errors;
    }

    protected function validateSAV()
    {
        $errors = array();

        $status = (int) $this->getData('status');
        if (!in_array($status, self::$sav_status)) {
            $errors[] = 'Statut invalide pour ce type de réservation';
        }

        if (!(int) $this->getData('id_sav')) {
            $errors[] = 'ID du SAV absent';
        }

        if (!(int) $this->getData('id_entrepot')) {
            $errors[] = 'Entrepot absent';
        }

        if (!(int) $this->getData('id_product')) {
            $errors[] = 'Aucun produit sélectionné';
        }

        $product = $this->getChildObject('product');
        if (!BimpObject::objectLoaded($product)) {
            $errors[] = 'ID du produit invalide';
        }

        if (count($errors)) {
            return $errors;
        }

        if ((int) $this->getData('id_equipment')) {
            $equipment = $this->getChildObject('equipment');
            $this->checkEquipment($equipment, $errors);
            $this->set('qty', 1);
        } elseif ($this->isProductSerialisable()) {
            $errors[] = 'Produit sérialisable: équipement obligatoire au statut "' . self::$status_list[$status]['label'] . '"';
        }

        $line = $this->getChildObject('sav_propal_line');
        if (!BimpObject::objectLoaded($line)) {
            $errors[] = 'ID de la ligne de devis du SAV absent ou invalide';
        }

        return $errors;
    }

    public function validatePost()
    {
        if ((int) BimpTools::getValue('new_status', 0)) {
            return $this->setNewStatus(BimpTools::getValue('status'), BimpTools::getValue('qty'), BimpTools::getValue('id_equipment'));
        }

        return parent::validatePost();
    }

    public function validate()
    {
        $errors = parent::validate();
        if (!count($errors)) {
            $type = (int) $this->getData('type');

            if (!array_key_exists($type, self::$types)) {
                $errors[] = 'Type invalide';
                return $errors;
            }

            $status = (int) $this->getData('status');

            if (!array_key_exists($status, self::$status_list)) {
                $errors[] = 'Statut invalide';
                return $errors;
            }

            switch ($type) {
                case self::BR_RESERVATION_COMMANDE:
                    $errors = $this->validateCommande();
                    break;

                case self::BR_RESERVATION_TRANSFERT:
                    $errors = $this->validateTransfert();
                    break;

                case self::BR_RESERVATION_TEMPORAIRE:
                    $errors = $this->validateTemporaire();
                    break;

                case self::BR_RESERVATION_SAV:
                    $errors = $this->validateSAV();
                    break;
            }
        }

        return $errors;
    }

    // Overrides: 

    public function reset()
    {
        parent::reset();

        if (!is_null($this->brOrderLine)) {
            unset($this->brOrderLine);
            $this->brOrderLine = null;
        }
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if ($this->isLoaded()) {
            $ref = $this->getData('ref');
            if (is_null($ref) || !$ref) {
                switch ((int) $this->getData('type')) {
                    case self::BR_RESERVATION_COMMANDE:
                        $ref = 'RES-CMD-' . $this->id;
                        break;

                    case self::BR_RESERVATION_TRANSFERT:
                        $ref = 'RES-TRANS-' . $this->id;
                        break;

                    case self::BR_RESERVATION_TEMPORAIRE:
                        $ref = 'RES-TEMP-' . $this->id;
                        break;

                    case self::BR_RESERVATION_SAV:
                        $ref = 'RES-SAV-' . $this->id;
                }
                if ($ref) {
                    $up_errors = $this->updateField('ref', $ref);
                    if (count($up_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de la référence pour cette réservation');
                    }
                }
            }
        }

        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        if ((int) $this->getData('id_commande_client')) {
            $commande = $this->getChildObject('commande_client');

            if (BimpObject::objectLoaded($commande)) {
                $commande->checkLogistiqueStatus();
            }
        }
    }

    // Méthodes statiques: 

    public static function getProductCounts($id_product, $id_entrepot = null)
    {
        $counts = array(
            'total' => 0,
            'reel'  => 0
        );

        $filters = array(
            'id_product' => (int) $id_product,
            'status'     => array(
                'operator' => '<',
                'value'    => 300
            )
        );

        if ((int) $id_entrepot) {
            $filters['id_entrepot'] = (int) $id_entrepot;
        }

        $sql = 'SELECT qty, status';
        $sql .= BimpTools::getSqlFrom('br_reservation');
        $sql .= BimpTools::getSqlWhere($filters);

        global $db;
        $bdb = new BimpDb($db);

        $rows = $bdb->executeS($sql, 'array');

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $counts['total'] += (int) $r['qty'];

                if ((int) $r['status'] >= 200 || (int) $r['status'] === 2) {
                    $counts['reel'] += (int) $r['qty'];
                }
            }
        }

        return $counts;
    }

    public static function getShippedQuantities($id_commande, $num_bl = null, $num_bl_max = null)
    {
        $data = array();

        $date_max = '';
        if (!is_null($num_bl_max)) {
            $commandeShipment = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
            if ($commandeShipment->find(array(
                        'id_commande_client' => (int) $id_commande,
                        'num_livraison'      => (int) $num_bl_max
                    ))) {
                $date_max = $commandeShipment->getData('date_shipped');
            }
            if (is_null($date_max) || !$date_max) {
                return $data;
            }
        }

        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        $resShipment = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');

        $rows = $reservation->getList(array(
            'type'               => self::BR_RESERVATION_COMMANDE,
            'id_commande_client' => (int) $id_commande
        ));

        foreach ($rows as $r) {
            if ((int) $r['id_product']) {
                if (!isset($data[(int) $r['id_commande_client_line']])) {
                    $data[(int) $r['id_commande_client_line']] = 0;

                    $filters = array(
                        'a.ref_reservation' => $r['ref']
                    );

                    $joins = array();

                    if (!is_null($num_bl)) {
                        $filters['cs.num_livraison'] = (int) $num_bl;
                        $joins[] = array(
                            'table' => 'bl_commande_shipment',
                            'alias' => 'cs',
                            'on'    => 'cs.id = a.id_shipment'
                        );
                    } elseif (!is_null($num_bl_max)) {
                        $filters['cs.date_shipped'] = array(
                            'and' => array(
                                'IS_NOT_NULL',
                                array(
                                    'operator' => '<=',
                                    'value'    => $date_max
                                )
                            )
                        );
                        $joins[] = array(
                            'table' => 'bl_commande_shipment',
                            'alias' => 'cs',
                            'on'    => 'cs.id = a.id_shipment'
                        );
                    }

                    $shipments = $resShipment->getList($filters, null, null, 'id', 'asc', 'object', array('qty'), $joins);

                    foreach ($shipments as $shipment) {
                        $data[(int) $r['id_commande_client_line']] += (int) $shipment->qty;
                    }
                }
            }
        }

        $orderLine = BimpObject::getInstance('bimpreservation', 'BR_OrderLine');
        $serviceShipment = BimpObject::getInstance('bimpreservation', 'BR_ServiceShipment');

        $list = $orderLine->getList(array(
            'a.id_commande' => (int) $id_commande
                ), null, null, 'id', 'asc', 'object', array('id', 'id_order_line'));

        if (!is_null($list) && count($list)) {
            foreach ($list as $item) {
                if (!isset($data[(int) $item->id_order_line])) {
                    $data[(int) $item->id_order_line] = 0;
                }

                $filters = array(
                    'a.id_br_order_line' => (int) $item->id
                );
                $joins = array();

                if (!is_null($num_bl)) {
                    $filters['cs.num_livraison'] = (int) $num_bl;
                    $joins[] = array(
                        'table' => 'bl_commande_shipment',
                        'alias' => 'cs',
                        'on'    => 'cs.id = a.id_shipment'
                    );
                } elseif (!is_null($num_bl_max)) {
                    $filters['cs.date_shipped'] = array(
                        'and' => array(
                            'IS_NOT_NULL',
                            array(
                                'operator' => '<=',
                                'value'    => $date_max
                            )
                        )
                    );
                    $joins[] = array(
                        'table' => 'bl_commande_shipment',
                        'alias' => 'cs',
                        'on'    => 'cs.id = a.id_shipment'
                    );
                }

                $shipments = $serviceShipment->getList($filters, null, null, 'id', 'asc', 'object', array('qty'), $joins);

                foreach ($shipments as $shipment) {
                    $data[(int) $item->id_order_line] += (int) $shipment->qty;
                }
            }
        }

        return $data;
    }

    public static function getShippedSerials($id_commande, $id_commande_line, $num_bl)
    {
        $resShipment = BimpObject::getInstance('bimpreservation', 'BR_ReservationShipment');

        $filters = array(
            'a.id_commande_client'      => (int) $id_commande,
            'a.id_commande_client_line' => $id_commande_line,
            'a.id_equipment'            => array(
                'operator' => '>',
                'value'    => 0
            ),
            'cs.num_livraison'          => (int) $num_bl
        );

        $joins = array(
            array(
                'table' => 'bl_commande_shipment',
                'alias' => 'cs',
                'on'    => 'cs.id = a.id_shipment'
            ),
            array(
                'table' => 'be_equipment',
                'alias' => 'e',
                'on'    => 'e.id = a.id_equipment'
            )
        );

        $shipments = $resShipment->getList($filters, null, null, 'id', 'asc', 'array', array('e.serial'), $joins);

        $serials = array();
        foreach ($shipments as $shipment) {
            $serials[] = $shipment['serial'];
        }

        return $serials;
    }
}

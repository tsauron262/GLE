<?php

class BR_Reservation extends BimpObject
{

    public static $use_a_commander = false;

    const BR_RESERVATION_COMMANDE = 1;
    const BR_RESERVATION_TRANSFERT = 2;
    const BR_RESERVATION_TEMPORAIRE = 3;

    public static $status_list = array(
        0   => array('label' => 'A traiter', 'icon' => 'exclamation-circle', 'classes' => array('info')),
//        1   => array('label' => 'A commander', 'icon' => 'exclamation-circle', 'classes' => array('important')),
        2   => array('label' => 'A réserver', 'icon' => 'exclamation-circle', 'classes' => array('important')),
        3   => array('label' => 'Commande à finaliser', 'icon' => 'cart-arrow-down', 'classes' => array('important')),
        100 => array('label' => 'En attente de réception', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        200 => array('label' => 'Attribué', 'icon' => 'lock', 'classes' => array('danger')),
        201 => array('label' => 'Transfert en cours', 'icon' => 'lock', 'classes' => array('danger')),
        202 => array('label' => 'Réservé', 'icon' => 'lock', 'classes' => array('danger')),
        250 => array('label' => 'Prêt pour expédition', 'icon' => 'sign-out', 'styles' => array('font-weight' => 'bold', 'color' => '#009B9B')),
        300 => array('label' => 'Expédié au client', 'icon' => 'sign-out', 'classes' => array('success')),
        301 => array('label' => 'Transféré', 'icon' => 'sign-out', 'classes' => array('success')),
        302 => array('label' => 'Reservation terminée', 'icon' => 'sign-out', 'classes' => array('success')),
        303 => array('label' => 'Reservation annulée', 'icon' => 'times', 'classes' => array('success'))
    );
    public static $commande_status = array(0, 2, 3, 100, 200, 250, 300, 303);
    public static $transfert_status = array(201, 301, 303);
    public static $temp_status = array(202, 302, 303);
    public static $need_equipment_status = array(200, 201, 202, 250, 300);
    public static $types = array(
        1 => 'Commande',
        2 => 'Transfert',
        3 => 'Réservation temporaire'
    );

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

    public function getCommandeClientLinesArray()
    {
        $commande = $this->getChildObject('commande_client');
        if (is_null($commande) || !isset($commande->id) || !$commande->id) {
            return array();
        }

        $id_product = (int) $this->getData('id_product');

        $commande->fetch_lines();
        $lines = array();

        foreach ($commande->lines as $n => $line) {
            if ($id_product) {
                if ((int) $line->fk_product !== $id_product) {
                    continue;
                }
            }
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

    public function getListExtraBtn()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            $status = (int) $this->getData('status');
            $qty = (int) $this->getData('qty');
            $product = $this->getChildObject('product');

            if ($status < 300) {
                switch ($status) {
                    case 0:
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
                        if ($qty === 1) {
                            $onclick = 'setReservationStatus($(this), ' . $this->id . ', 2)';
                        } else {
                            $title = 'Produits à réserver';
                            $values = htmlentities('\'{"fields": {"status": 2}}\'');
                            $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
                            $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
                        }

                        $buttons[] = array(
                            'label'   => 'A réserver',
                            'icon'    => 'lock',
                            'onclick' => $onclick,
                            'class'   => 'newStatusButton',
                            'attrs'   => array(
                                'data' => array(
                                    'new_status' => 2
                                )
                            )
                        );

                        $id_commande_client = (int) $this->getData('id_commande_client');
                        $id_entrepot = (int) $this->getData('id_entrepot');
                        $id_product = (int) $this->getData('id_product');
                        $ref = $this->getData('ref');

                        if (!is_null($ref) && $ref && $id_entrepot && $id_product && $id_commande_client) {
                            $title = 'Ajouter à une commande fournisseur';
                            $values = htmlentities('\'{"fields": {"id_reservation": ' . $this->id . ', "ref_reservation": "' . $ref . '", "id_entrepot": ' . $id_entrepot . ', "id_commande_client": ' . $id_commande_client . ', "id_product": ' . $id_product . ', "qty": ' . $qty . '}}\'');
                            $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_ReservationCmdFourn\', id_object: 0, ';
                            $onclick .= 'form_name: \'default\', param_values: ' . $values . '}, \'' . $title . '\');';
                            $buttons[] = array(
                                'label'   => 'Commander',
                                'icon'    => 'cart-plus',
                                'onclick' => $onclick,
                            );
                        }
                        break;

                    case 2:
                        if ($product->isSerialisable() || $qty > 1) {
                            $title = 'Réserver des produits';
                            $values = htmlentities('\'{"fields": {"status": 200}}\'');
                            $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
                            $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
                        } else {
                            $onclick = 'setReservationStatus($(this), ' . $this->id . ', 200)';
                        }

                        $buttons[] = array(
                            'label'   => 'Réserver',
                            'icon'    => 'lock',
                            'onclick' => $onclick,
                            'class'   => 'newStatusButton',
                            'attrs'   => array(
                                'data' => array(
                                    'new_status' => 200
                                )
                            )
                        );
                        break;

                    case 100:
                        if ($product->isSerialisable() || $qty > 1) {
                            $title = 'Attribuer des produits / équipements';
                            $values = htmlentities('\'{"fields": {"status": 200}}\'');
                            $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
                            $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
                        } else {
                            $onclick = 'setReservationStatus($(this), ' . $this->id . ', 200)';
                        }

                        $buttons[] = array(
                            'label'   => 'Attribuer des équipements',
                            'icon'    => 'arrow-circle-down',
                            'onclick' => $onclick,
                            'class'   => 'newStatusButton',
                            'attrs'   => array(
                                'data' => array(
                                    'new_status' => 200
                                )
                            )
                        );
                        break;

                    case 200:
                        $id_commande_client = (int) $this->getData('id_commande_client');
                        $ref = $this->getData('ref');

                        if (!is_null($ref) && $ref && $id_commande_client) {
                            $id_equipment = (int) $this->getData('id_equipment');

                            $title = 'Ajouter à une expédition';
                            $values = htmlentities('\'{"fields": {"id_commande_client": ' . $id_commande_client . ', "ref_reservation": "' . $ref . '", "qty": ' . $qty . ', "id_equipment": ' . $id_equipment . '}}\'');
                            $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_ReservationShipment\', id_object: 0, ';
                            $onclick .= 'form_name: \'default\', param_values: ' . $values . '}, \'' . $title . '\');';
                            $buttons[] = array(
                                'label'   => 'Ajouter à une expédition',
                                'icon'    => 'sign-out',
                                'onclick' => $onclick,
                            );
                        }
                        break;

                    case 201:
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
                        break;

                    case 202:
                        if ($qty === 1) {
                            $onclick = 'setReservationStatus($(this), ' . $this->id . ', 302)';
                        } else {
                            $title = 'Réservations transférés';
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
                        break;
                }

                switch ((int) $this->getData('type')) {
                    case self::BR_RESERVATION_COMMANDE:
                        if ((int) $status) {
                            $onclick = 'setReservationStatus($(this), ' . $this->id . ', 0)';
                            $buttons[] = array(
                                'label'   => 'Réinitialiser la réservation',
                                'icon'    => 'undo',
                                'onclick' => $onclick,
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
        }

        return $buttons;
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

            default:
                $status = self::$status_list;
                break;
        }

        return $status;
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
                $add_errors = $this->createFromCommandeClientLine($id_entrepot, $commande, $line);
                if (count($add_errors)) {
                    $errors[] = 'Echec de l\'ajout de la ligne n°' . $i;
                    $errors = array_merge($errors, $add_errors);
                }
            }
        }
        return $errors;
    }

    public function createFromCommandeClientLine($id_entrepot, Commande $commande, OrderLine $line)
    {
        $this->reset();

        if (is_null($line) || !isset($line->id) || !$line->id) {
            return array('Ligne de Commande client invalide');
        }

        if (!isset($line->fk_product) || !$line->fk_product) {
            return array();
        }

        $this->set('id_product', $line->fk_product);
        $product = $this->getChildObject('product');
        if (is_null($product) || !$product->isLoaded()) {
            return array('Produit d\'ID ' . $line->fk_product . ' invalide');
        } elseif ((int) $product->getData('fk_product_type') !== 0) {
            $service = BimpObject::getInstance($this->module, 'BR_Service');
            $service_errors = $service->validateArray(array(
                'id_commande_client'      => (int) $commande->id,
                'id_commande_client_line' => (int) $line->id,
                'qty'                     => (int) $line->qty,
                'shipped'                 => 0
            ));
            if (!count($service_errors)) {
                $service_errors = $service->create();
            }
            if (count($service_errors)) {
                $errors[] = 'Echec de l\'ajout du service pour la ligne d\'ID ' . $line->id;
                $errors = array_merge($errors, $service_errors);
            }
        } else {
            if (is_null($commande) || !isset($commande->id) || !$commande->id) {
                return array('Commande client invalide');
            }

            $errors = $this->set('id_commande_client_line', $line->id);
            $this->set('id_commande_client', $line->fk_commande);

            $errors = array_merge($errors, $this->set('id_entrepot', $id_entrepot));

            if (count($errors)) {
                return $errors;
            }

            $this->set('id_client', $commande->socid);
            $this->set('date_from', date('Y-m-d H:i:s'));
            if (isset($commande->user_author_id)) {
                $this->set('id_commercial', $commande->user_author_id);
            }

            $this->set('type', self::BR_RESERVATION_COMMANDE);

            $qty = (int) $line->qty;
            $this->set('qty', $qty);
            $this->set('status', 0);
            $this->set('id_equipment', 0);

            $errors = array_merge($errors, $this->create());
        }

        return $errors;
    }

    public function setEquipment($id_equipment, &$errors = array())
    {
        if (is_null($id_equipment) || !$id_equipment) {
            $errors[] = 'ID de l\'équipement absent';
            return false;
        }

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment', (int) $id_equipment);
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
            return false;
        }

        $id_product = (int) $this->getData('id_product');
        if (!$id_product) {
            $this->set('id_product', (int) $equipment->getData('id_product'));
        }

        if ($id_product !== (int) $equipment->getData('id_product')) {
            $errors[] = 'L\'équipement spécifié ne correspond pas au produit associé à cette réservation';
            return false;
        }

        $current_reservations = $equipment->getReservationsList();
        if (count($current_reservations)) {
            if (!$this->isLoaded() || ($this->isLoaded() && !in_array($this->id, $current_reservations))) {
                $errors[] = 'Cet équipement est déjà réservé';
                return false;
            }
        }

        $id_entrepot = (int) $this->getData('id_entrepot');
        if ($id_entrepot) {
            $place = $equipment->getCurrentPlace();
            if (is_null($place) || (int) $place->getData('type') !== BE_Place::BE_PLACE_ENTREPOT || (int) $place->getData('id_entrepot') !== $id_entrepot) {
                $errors[] = 'L\'équipement ' . $equipment->id . ' n\'est pas disponible dans l\'entrepôt sélectionné';
                return false;
            }
        }

        return true;
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

        $errors = array();
        $ref = $this->getData('ref');

        if (!$ref) {
            return array('Référence de la réservation absente');
        }

        $this->set('status', $status);

        $current_status = (int) $this->getSavedData('status');

        if ($status === $current_status) {
            return array('Cette réservation a déjà ce statut');
        }

        if (in_array($status, self::$need_equipment_status) && ($current_status <= 100) && $this->isProductSerialisable()) {
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

        if (!$status && ($current_status === 100) && ((int) $this->getData('type') === self::BR_RESERVATION_COMMANDE)) {
            $rcf = BimpObject::getInstance($this->module, 'BR_ReservationCmdFourn');
            $list = $rcf->getList(array(
                'ref_reservation' => $ref
            ));

            if (!is_null($list) && count($list)) {
                foreach ($list as $item) {
                    if ($rcf->fetch((int) $item['id'])) {
                        $delete = true;
                        if ((int) $rcf->getData('id_commande_fournisseur')) {
                            $remove_errors = array();
                            if (!$rcf->removeFromCommandeFournisseur($remove_errors)) {
                                $errors = array_merge($errors, $remove_errors);
                                $delete = false;
                            }
                        }

                        if ($delete) {
                            $delete_errors = $rcf->delete();
                            if (count($delete_errors)) {
                                $errors[] = 'Echec de la suppression de la ligne de réservation en commande fournisseur d\'ID ' . $item['id'];
                                $errors = array_merge($errors, $delete_errors);
                                $qty -= (int) $rcf->getData('qty');
                            }
                        } else {
                            $qty -= (int) $rcf->getData('qty');
                        }
                    }
                }
            }

            if ($qty <= 0) {
                return $errors;
            }
        }

        if (!$status && ($current_status === 250) && ((int) $this->getData('type') === self::BR_RESERVATION_COMMANDE)) {
            $rs = BimpObject::getInstance($this->module, 'BR_ReservationShipment');
            $list = $rs->getList(array(
                'ref_reservation' => $ref
            ));

            if (!is_null($list) && count($list)) {
                foreach ($list as $item) {
                    if ($rs->fetch((int) $item['id'])) {
                        $delete = true;
                        $shipment = $rs->getParentInstance();
                        if ($shipment->isLoaded()) {
                            if ((int) $shipment->getData('status') === 2) {
                                $errors[] = $rs->getData('qty') . ' unités ont déjà été expédiées pour la référence "' . $ref . '"';
                                $delete = false;
                            }
                        }

                        if ($delete) {
                            $delete_errors = $rs->delete();
                            if (count($delete_errors)) {
                                $errors[] = 'Echec de la suppression de la ligne d\'expédition d\'ID ' . $item['id'];
                                $errors = array_merge($errors, $delete_errors);
                                $qty -= (int) $rs->getData('qty');
                            }
                        } else {
                            $qty -= (int) $rs->getData('qty');
                        }
                    }
                }
            }

            if ($qty <= 0) {
                return $errors;
            }
        }


        if ($this->isProductSerialisable()) {
            $this->config->resetObjects();
            $equipment = $this->getChildObject('equipment');
            if (is_null($equipment) || !$equipment->isLoaded()) {
                $errors[] = 'Equipement invalide';
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
                    }
                } else {
                    $this->set('qty', 1);
                    $this->set('id_equipment', $equipment->id);
                }
            }
        } else {
            $product = $this->getChildObject('product');
            $id_equipment = (int) $this->getData('id_equipment');
            if (is_null($product) || !$product->isLoaded()) {
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
                    $delete_errors = $old_reservation->delete();
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
                    $this->update();
                }
            } else {
                $this->set('qty', $qty);
                $this->set('status', $status);
                $this->update();
            }
        }

        return $errors;
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
                if (!$equipment->fetch((int) $list[0]['id'])) {
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

//    public function addShipment($ref, $qty)
//    {
//        if ((int) $this->getData('type') !== self::BR_RESERVATION_COMMANDE) {
//            return array('Impossible de créer une expédition pour la réservation ' . $this->id . ' - type invalide');
//        }
//
//        $errors = array();
//        global $user;
//
//        $resShipment = BimpObject::getInstance($this->module, 'BR_ReservationShipment');
//        $errors = $resShipment->validateArray(array(
//            'id_commande_client' => (int) $this->getData('id_commande_client'),
//            'ref_reservation'    => $ref,
//            'date'               => date('Y-m-d'),
//            'qty'                => (int) $qty,
//            'id_user'            => (int) $user->id
//        ));
//        if (!count($errors)) {
//            $errors = $resShipment->create();
//        }
//        return $errors;
//    }
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
        if (!is_null($commande) && isset($commande->id) && $commande->id) {
            $this->set('id_client', (int) $commande->socid);
        } else {
            return array('Commande invalide');
        }

        $equipment = $this->getChildObject('equipment');
        if (!is_null($equipment) && $equipment->isLoaded()) {
            $this->set('id_product', (int) $equipment->getData('id_product'));
        }

        $lines = $this->getCommandeClientLinesArray();
        if (!isset($lines[$id_line])) {
            $errors[] = 'ID de la ligne de commande client invalide';
            return $errors;
        }

        $id_product = (int) $this->getData('id_product');

        global $db;
        $line = new OrderLine($db);
        if ($line->fetch($id_line) > 0) {
            if (isset($line->fk_product) && $line->fk_product) {
                if (!$id_product) {
                    $id_product = (int) $line->fk_product;
                    $this->set('id_product', (int) $line->fk_product);
                } elseif ($id_product !== (int) $line->fk_product) {
                    $errors[] = 'Le produit sélectionné ne correspond pas à la ligne de commande sélectionnée';
                }
            }
        }

        $product = $this->getChildObject('product');
        if (is_null($product) || !$product->isLoaded()) {
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

        if (!$id_product) {
            $errors[] = 'Aucun produit sélectionné';
        } else {
            $product = $this->getChildObject('product');
            if (is_null($product) || !$product->isLoaded()) {
                $errors[] = 'Aucun produit spécifié';
            } elseif ($product->isSerialisable()) {
                if (!(int) $this->getData('id_equipment')) {
                    $errors[] = 'Produit sérialisable: équipement obligatoire';
                } else {
                    $this->checkEquipment($equipment, $errors);
                    $this->set('qty', 1);
                }
            } else {
                $this->set('id_equipment', 0);
            }
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
        if (!in_array($status, self::$commande_status)) {
            $errors[] = 'Statut invalide pour ce type de réservation';
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
            }
        }

        return $errors;
    }

    // Overrides: 

    public function create()
    {
        $errors = parent::create();

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
                }
                if ($ref) {
                    if (!$this->db->update($this->getTable(), array(
                                'ref' => $ref
                                    ), '`id` = ' . (int) $this->id)) {
                        $msg = 'Echec de l\'enregistrement de la référence pour cette réservation';
                        $sqlError = $this->db->db->lasterror();
                        if ($sqlError) {
                            $msg .= ' - ' . $sqlError;
                        }
                        $errors[] = $msg;
                    }
                }
            }
        }

        return $errors;
    }

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

        if (!is_null($id_entrepot)) {
            $filters['id_entrepot'] = (int) $id_entrepot;
        }

        $sql = 'SELECT qty, status';
        $sql .= BimpTools::getSqlFrom($this->getTable());
        $sql .= BimpTools::getSqlWhere(array(
                    'id_entrepot' => (int) $id_entrepot
        ));

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $counts['total'] += (int) $r['qty'];

                if ((int) $r['status'] >= 200) {
                    $counts['reel'] += (int) $r['qty'];
                }
            }
        }
    }

    public static function getShippedQuantities($id_commande, $num_bl = null, $num_bl_max = null)
    {
        $data = array();

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
                            'table' => 'br_commande_shipment',
                            'alias' => 'cs',
                            'on'    => 'cs.id = a.id_shipment'
                        );
                    } elseif (!is_null($num_bl_max)) {
                        $filters['cs.num_livraison'] = array(
                            'operator' => '<=',
                            'value'    => (int) $num_bl_max
                        );
                        $joins[] = array(
                            'table' => 'br_commande_shipment',
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

        $service = BimpObject::getInstance('bimpreservation', 'BR_Service');
        $serviceShipment = BimpObject::getInstance('bimpreservation', 'BR_ServiceShipment');

        $list = $service->getList(array(
            'a.id_commande_client' => (int) $id_commande
                ), null, null, 'id', 'asc', 'object', array('id', 'id_commande_client_line'));

        if (!is_null($list) && count($list)) {
            foreach ($list as $item) {
                if (!isset($data[(int) $item->id_commande_client_line])) {
                    $data[(int) $item->id_commande_client_line] = 0;
                }

                $filters = array(
                    'a.id_service' => (int) $item->id
                );
                $joins = array();

                if (!is_null($num_bl)) {
                    $filters['cs.num_livraison'] = (int) $num_bl;
                    $joins[] = array(
                        'table' => 'br_commande_shipment',
                        'alias' => 'cs',
                        'on'    => 'cs.id = a.id_shipment'
                    );
                } elseif (!is_null($num_bl_max)) {
                    $filters['cs.num_livraison'] = array(
                        'operator' => '<=',
                        'value'    => $num_bl_max
                    );
                    $joins[] = array(
                        'table' => 'br_commande_shipment',
                        'alias' => 'cs',
                        'on'    => 'cs.id = a.id_shipment'
                    );
                }

                $shipments = $serviceShipment->getList($filters, null, null, 'id', 'asc', 'object', array('qty'), $joins);

                foreach ($shipments as $shipment) {
                    $data[(int) $item->id_commande_client_line] += (int) $shipment->qty;
                }
            }
        }

        return $data;
    }
}

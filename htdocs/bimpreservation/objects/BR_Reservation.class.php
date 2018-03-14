<?php

class BR_Reservation extends BimpObject
{

    const BR_RESERVATION_COMMANDE = 1;
    const BR_RESERVATION_TRANSFERT = 2;
    const BR_RESERVATION_TEMPORAIRE = 3;

    public static $status_list = array(
        0   => array('label' => 'A réserver', 'icon' => 'exclamation-circle', 'classes' => array('info')),
        1   => array('label' => 'A commander', 'icon' => 'exclamation-circle', 'classes' => array('info')),
        100 => array('label' => 'En attente de réception', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        200 => array('label' => 'Attribué', 'icon' => 'lock', 'classes' => array('danger')),
        201 => array('label' => 'Transfert en cours', 'icon' => 'lock', 'classes' => array('danger')),
        202 => array('label' => 'Réservé', 'icon' => 'lock', 'classes' => array('danger')),
        300 => array('label' => 'Livré au client', 'icon' => 'sign-out', 'classes' => array('success')),
        301 => array('label' => 'Transféré', 'icon' => 'sign-out', 'classes' => array('success')),
        302 => array('label' => 'Reservation terminée', 'icon' => 'sign-out', 'classes' => array('success')),
        303 => array('label' => 'Reservation annulée', 'icon' => 'times', 'classes' => array('success'))
    );
    public static $commande_status = array(0, 1, 100, 200, 300, 303);
    public static $transfert_status = array(201, 301, 303);
    public static $temp_status = array(202, 302, 303);
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

        if ($status <= 100 || $status === 303) {
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
            if ($status < 300) {
                switch ($status) {
                    case 0:
                        $title = 'Produits à mettre en attente de réception';
                        $values = htmlentities('\'{"fields": {"status": 100}}\'');
                        $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
                        $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
                        $buttons[] = array(
                            'label'   => 'Mettre en attente de réception',
                            'icon'    => 'hourglass-start',
                            'onclick' => $onclick
                        );
                        $title = 'Produits à réserver';
                        $values = htmlentities('\'{"fields": {"status": 200}}\'');
                        $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
                        $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
                        $buttons[] = array(
                            'label'   => 'Réserver',
                            'icon'    => 'lock',
                            'onclick' => $onclick
                        );
                        break;

                    case 100:
                        $title = 'Réceptionner des produits';
                        $values = htmlentities('\'{"fields": {"status": 200}}\'');
                        $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_Reservation\', id_object: ' . $this->id . ', ';
                        $onclick .= 'form_name: \'new_status\', param_values: ' . $values . '}, \'' . $title . '\');';
                        $buttons[] = array(
                            'label'   => 'Réceptionner',
                            'icon'    => 'arrow-circle-down',
                            'onclick' => $onclick
                        );
                        break;

                    case 200:
                        $onclick = 'setReservationStatus($(this), ' . $this->id . ', 300)';
                        $buttons[] = array(
                            'label'   => 'Livré',
                            'icon'    => 'sign-out',
                            'onclick' => $onclick
                        );
                        break;

                    case 201:
                        $onclick = 'setReservationStatus($(this), ' . $this->id . ', 301)';
                        $buttons[] = array(
                            'label'   => 'Transféré',
                            'icon'    => 'sign-out',
                            'onclick' => $onclick
                        );
                        break;

                    case 202:
                        $onclick = 'setReservationStatus($(this), ' . $this->id . ', 302)';
                        $buttons[] = array(
                            'label'   => 'Livré',
                            'icon'    => 'sign-out',
                            'onclick' => $onclick
                        );
                        break;
                }
                $onclick = 'setReservationStatus($(this), ' . $this->id . ', 303)';
                $buttons[] = array(
                    'label'   => 'Annuler la réservation',
                    'icon'    => 'times-circle',
                    'onclick' => $onclick
                );
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
                $status[0] = self::$status_list[0];
                $status[100] = self::$status_list[100];
                $status[200] = self::$status_list[200];
                $status[300] = self::$status_list[300];
                break;

            case self::BR_RESERVATION_TRANSFERT:
                $status[201] = self::$status_list[201];
                $status[301] = self::$status_list[301];
                break;

            case self::BR_RESERVATION_TEMPORAIRE:
                $status[202] = self::$status_list[202];
                $status[302] = self::$status_list[302];
                $status[303] = self::$status_list[303];
                break;
        }

        return $status;
    }

    // Gestion des réservations: 

    public function createFromCommandeClientLine($id_entrepot, $id_commande_client_line)
    {
        $this->reset();

        if (is_null($id_commande_client_line) || !$id_commande_client_line) {
            return array('Aucune ligne de commande client spécifiée');
        }

        $errors = $this->set('id_commande_client_line', $id_commande_client_line);
        $errors = array_merge($errors, $this->set('id_entrepot', $id_entrepot));

        if (count($errors)) {
            return $errors;
        }

        global $db;

        if (!class_exists('Commande')) {
            require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        }

        $line = new OrderLine($db);
        if ($line->fetch($id_commande_client_line) <= 0) {
            return array('Ligne de commande d\'ID ' . $id_commande_client_line . ' invalide - ' . $line->error);
        }

        if (!isset($line->fk_product) || !$line->fk_product) {
            return array();
        }

        $this->set('id_product', $line->fk_product);
        $product = $this->getChildObject('id_product');

        if (!$product->isLoaded()) {
            return array('Produit d\'ID ' . $line->fk_product . ' invalide');
        }

        $this->set('id_commande_client', $line->fk_commande);
        $commande = $this->getChildObject('commande_client');

        if (is_null($commande) || !isset($commande->id) || !$commande->id) {
            return array('Commande d\'ID ' . $line->fk_commande . ' invalide');
        }

        $this->set('id_client', $commande->socid);
        $this->set('date_from', date('Y-m-d H:i:s'));
        if (isset($commande->user_author_id)) {
            $this->set('id_commercial', $commande->user_author_id);
        }

        $this->set('type', self::BR_RESERVATION_COMMANDE);

        $qty = $line->qty;

        $stocks = $product->getStocksForEntrepot((int) $id_entrepot);

        $qty_dispo = 0;
        $qty_cmd = 0;

        if ($stocks['dispo'] > 0) {
            if ($stocks['dispo'] >= $qty) {
                $qty_dispo = $qty;
            } else {
                $qty_dispo = $stocks['dispo'];
                $qty_cmd = $qty - $qty_dispo;
            }
        } else {
            $qty_cmd = $qty;
        }

        if ($qty_cmd > 0) {
            $this->set('qty', $qty_cmd);
            $this->set('status', 100);
            $create_errors = $this->create();
            if (count($create_errors)) {
                $errors[] = 'Echec de la création de la réservation pour ' . $qty_dispo . ' unités au status "' . self::$status_list[100]['label'] . '"';
                $errors = array_merge($errors, $create_errors);
            }
        }

        if ($qty_dispo > 0) {
            $this->id = null;
            $this->set('qty', $qty_dispo);
            $this->set('status', 200);
            $create_errors = $this->create();
            if (count($create_errors)) {
                $errors[] = 'Echec de la création de la réservation pour ' . $qty_dispo . ' unités au status "' . self::$status_list[200]['label'] . '"';
                $errors = array_merge($errors, $create_errors);
            }
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

        // todo: checker entrepot => Faire correction auto ??? 

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

        if (!$this->isLoaded() && $equipment->isReserved()) {
            $errors[] = 'Cet équipement est déjà réservé';
            return false;
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
        if (!$this->isLoaded()) {
            return array('ID de la réservation absent');
        }

        if (is_null($status)) {
            return array('Nouveau statut absent');
        }

        $errors = array();
        $this->set('status', $status);
        $current_status = (int) $this->getSavedData('status');

        if (($current_status <= 100) && $this->isProductSerialisable()) {
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
                    $new_errors = $new_reservation->create();

                    if (count($new_errors)) {
                        $errors[] = 'Echec de la création d\'une nouvelle réservation pour le numéro de série "' . $equipment->getData('serial') . '"';
                        $errors = array_merge($errors, $new_errors);
                    } else {
                        $this->set('status', $current_status);
                        $this->set('qty', $current_qty - 1);
                    }
                } else {
                    $this->set('qty', 1);
                    $this->set('id_equipment', $equipment->id);
                }
            }
        } else {
            $product = $this->getChildObject('product');
            if (is_null($product) || !$product->isLoaded()) {
                $errors[] = 'Produit invalide';
            }

            if ($qty > $current_qty) {
                $qty = $current_qty;
            }

            $old_reservation = BimpObject::getInstance($this->module, $this->object_name);

            if ((int) $this->getData('type') === self::BR_RESERVATION_COMMANDE) {
                $id_commande = (int) $this->getData('id_commande_client');
                $id_commande_client_line = (int) $this->getData('id_commande_client_line');
                if ($old_reservation->find(array(
                            'id_commande_client'      => $id_commande,
                            'id_commande_client_line' => $id_commande_client_line,
                            'status'                  => (int) $status
                        ))) {
                    if ($qty < $current_qty) {
                        $old_reservation->set('qty', ((int) $old_reservation->getData('qty') + $qty));
                        $update_errors = $old_reservation->update();

                        if (count($update_errors)) {
                            $errors[] = 'Echec de la mise à jour de la réservation ' . $old_reservation->id;
                            $errors = array_merge($errors, $update_errors);
                        } else {
                            $this->set('qty', ($current_qty - $qty));
                            $this->set('status', $current_status);
                        }
                    } else {
                        $qty += (int) $old_reservation->getData('qty');
                        $id_old_reservation = $old_reservation->id;
                        $delete_errors = $old_reservation->delete();
                        if (count($delete_errors)) {
                            $errors[] = 'Echec de la suppression de la réservation ' . $id_old_reservation;
                            $errors = array_merge($errors, $delete_errors);
                        } else {
                            $this->set('qty', $qty);
                        }
                    }
                    return $errors;
                }
            }

            if ($qty < $current_qty) {
                $new_reservation = BimpObject::getInstance($this->module, $this->object_name, $this->id);
                $new_reservation->id = null;
                $new_reservation->set('qty', $qty);
                $new_reservation->set('status', $status);
                $new_errors = $new_reservation->create();

                if (count($new_errors)) {
                    $errors[] = 'Echec de la création d\'une nouvelle réservation pour ' . $qty . ' produit' . ($qty > 1 ? 's' : '') . ' "' . $product->getData('label') . '"';
                    $errors = array_merge($errors, $new_errors);
                } else {
                    $this->set('status', $current_status);
                    $this->set('qty', ($current_qty - $qty));
                }
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
            'or' => array(
                array('serial' => $serial),
                array('serial' => 'S' . $serial)
            )
        );
        $list = $equipment->getList($filters, null, null, 'id', 'desc', 'array', array('id'));
        if (!is_null($list) && count($list)) {
            if (count($list) > 1) {
                $msg = 'Plusieurs équipements ont été trouvés pour ce numéro de série.<br/>';
                $msg .= 'Veuillez utiliser le bouton "Réceptionner" pour la réservation correspondante.';
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
                        'status'             => 100,
                        'id_product'         => $id_product
                    ))) {
                $errors = $reservation->setNewStatus(200, null, $equipment->id);
                if (!count($errors)) {
                    return $reservation->id;
                }
            }
        }

        return 0;
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

//    Statiques

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
}

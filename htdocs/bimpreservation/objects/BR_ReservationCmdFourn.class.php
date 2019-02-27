<?php

class BR_ReservationCmdFourn extends BimpObject
{

    public function getProdFournisseursArray()
    {
        $fournisseurs = array();

        $product = $this->getChildObject('product');

        if (!is_null($product) && $product->isLoaded()) {
            $list = $product->dol_object->list_suppliers();
            foreach ($list as $id_fourn) {
                if (!array_key_exists($id_fourn, $fournisseurs)) {
                    $result = $this->db->getRow('societe', '`rowid` = ' . (int) $id_fourn, array('nom', 'code_fournisseur'));
                    if (!is_null($result)) {
                        $fournisseurs[(int) $id_fourn] = $result->code_fournisseur . ' - ' . $result->nom;
                    } else {
                        echo $this->db->db->error();
                    }
                }
            }
        }

        return $fournisseurs;
    }

    public function getProdFournisseursPricesArray()
    {
        $id_product = (int) $this->getData('id_product');
        $id_price = (int) $this->getData('id_price');

        BimpObject::loadClass('bimpcore', 'Bimp_Product');
        return Bimp_Product::getFournisseursPriceArray($id_product, 0, $id_price);
    }

    public function getCommandesFournisseurArray()
    {
        $commandes = array(
            0     => '',
            'new' => 'Nouvelle commande'
        );

        $id_entrepot = (int) $this->getData('id_entrepot');
        if (!$id_entrepot) {
            return array();
        }

        $id_fournisseur = 0;
        switch ((int) $this->getData('type')) {
            case 1:
                $price = $this->getChildObject('fournisseur_price');
                if (!is_null($price) && $price->isLoaded()) {
                    $id_fournisseur = (int) $price->getData('fk_soc');
                }
                break;

            case 2:
                $id_fournisseur = (int) $this->getData('id_fournisseur');
                break;
        }

        if ($id_fournisseur) {
            $sql = 'SELECT cf.rowid as id, cf.ref, cf.date_creation as date, s.nom FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur cf';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields cfe ON cf.rowid = cfe.fk_object';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON s.rowid = cf.fk_soc';
            $sql .= ' WHERE cf.fk_soc = ' . (int) $id_fournisseur . ' AND cf.fk_statut = 0 AND cfe.entrepot = ' . (int) $id_entrepot;

            $rows = $this->db->executeS($sql);
            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $obj) {
                    $DT = new DateTime($obj->date);
                    $commandes[(int) $obj->id] = $obj->nom . ' ' . $obj->ref . ' - Créée le ' . $DT->format('d / m / Y à H:i');
                }
            }
        }

        return $commandes;
    }

    public function displayFournisseur()
    {
        $type = (int) $this->getData('type');

        switch ($type) {
            case 1:
                $fp = $this->getChildObject('fournisseur_price');
                if (!is_null($fp) && $fp->isLoaded()) {
                    return $fp->displayData('fk_soc', 'nom_url');
                }
                break;

            case 2:
                return $this->displayData('id_fournisseur', 'nom_url');
        }

        return '';
    }

    public function displayPrice()
    {
        $type = (int) $this->getData('type');

        switch ($type) {
            case 1:
                $fp = $this->getChildObject('fournisseur_price');
                if (!is_null($fp) && $fp->isLoaded()) {
                    return BimpTools::displayMoneyValue($fp->getData('unitprice'), 'EUR');
                }
                break;

            case 2:
                return $this->displayData('special_price');
        }

        return '';
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        if (!(int) $this->getData('id_commande_fournisseur')) {
            $title = 'Ajout à une commande fournisseur';
            $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_ReservationCmdFourn\', id_object: ' . $this->id . ', ';
            $onclick .= 'form_name: \'supplier\'}, \'' . $title . '\');';
            $buttons[] = array(
                'label'   => 'Ajouter à une commande fournisseur',
                'icon'    => 'plus',
                'onclick' => $onclick
            );
        } else {
            $commande = $this->getChildObject('commande_fournisseur');
            if (!is_null($commande) && isset($commande->id) && $commande->id) {
                if ((int) $commande->statut === 0) {
                    $buttons[] = array(
                        'label'   => 'Retirer de la commande fournisseur',
                        'icon'    => 'times-circle',
                        'onclick' => 'removeFromCommandeFournisseur($(this), ' . $this->id . ', 0);'
                    );
                } else {
                    $buttons[] = array(
                        'label'   => 'Forcer le retrait de la commande fournisseur',
                        'icon'    => 'times-circle',
                        'onclick' => 'removeFromCommandeFournisseur($(this), ' . $this->id . ', 1);'
                    );
                }
            }
        }

        return $buttons;
    }

    // Gestion de la commande fournisseur: 

    public function addToCommandeFournisseur(&$errors)
    {
        if ((int) $this->getSavedData('id_commande_fournisseur')) {
            return false;
        }

        $id_commande_fournisseur = (int) $this->getData('id_commande_fournisseur');
        if ($id_commande_fournisseur) {
            $commande = $this->getChildObject('commande_fournisseur');

            if (is_null($commande) || !isset($commande->id) || !$commande->id) {
                $errors[] = 'Commande fournisseur absente ou invalide';
                return false;
            }

            $product = $this->getChildObject('product');
            if (is_null($product) || !$product->isLoaded()) {
                $errors[] = 'Produit absent ou invalide';
                return false;
            }

            $type = (int) $this->getData('type');
            if (!$type) {
                $errors[] = 'Type de prix absent ou invalide';
                return false;
            }

            if ($type === 1) {
                $fk_prod_fourn_price = (int) $this->getData('id_price');
                if (!$fk_prod_fourn_price) {
                    $errors[] = 'Prix fournisseur absent ou invalide';
                    return false;
                }
                $price = $this->getChildObject('fournisseur_price');
                if (is_null($price) || !$price->isLoaded()) {
                    $errors[] = 'Prix fournisseur absent ou invalide';
                    return false;
                }
                $pu_ht = (float) $price->getData('price');
                $txtva = (float) $price->getData('tva_tx');
                $fourn_ref = $price->getData('ref_fourn');
            } else {
                $fk_prod_fourn_price = 0;
                $pu_ht = (float) $this->getData('special_price');
                $txtva = (float) $this->getData('special_price_tva_tx');
                $fourn_ref = '';
            }

            $desc = $product->getData('ref') . ' - ' . $product->getData('label');
            $qty = (int) $this->getData('qty');
            $fk_product = $product->id;

            $remise_percent = 0.0;
            $price_base_type = 'HT';
            $txlocaltax1 = 0.0;
            $txlocaltax2 = 0.0;

            $id_line = (int) $commande->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $fk_prod_fourn_price, $fourn_ref, $remise_percent, $price_base_type);
            if ($id_line <= 0) {
                $errors[] = 'Echec de l\'ajout du produit "' . $desc . '" à la commande fournisseur';
                BimpTools::getErrorsFromDolObject($commande, $errors);
                return false;
            }

            if ($type === 2) {
                if ($commande->updateline(
                                $id_line, $desc, $pu_ht, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type
                        ) < 0) {
                    $errors[] = 'Echec de l\'enregistrement du prix spécial pour le produit "' . $desc . '"';
                    $commande->deleteline($id_line);
                    return false;
                }
            }

            $this->set('id_commande_fournisseur_line', $id_line);
            $this->set('id_commande_fournisseur', (int) $commande->id);

            return true;
        }

        return false;
    }

    public function removeFromCommandeFournisseur(&$errors, $force_remove = false)
    {
        if ($this->isLoaded()) {
            $commande = $this->getChildObject('commande_fournisseur');
            if (is_null($commande) || !isset($commande->id) || !$commande->id) {
                $errors[] = 'ID de la commande fournisseur absent';
            }

            if ((int) $commande->statut !== 0) {
                if ($force_remove) {
                    $this->set('id_commande_fournisseur', 0);
                    $this->set('id_commande_fournisseur_line', 0);
                    $errors = $this->update();
                    return true;
                } else {
                    $errors[] = 'La commander fournisseur "' . $commande->ref . '" ne peut plus être modifiée car elle n\'a plus le statut "brouillon"';
                }
            }

            $id_line = (int) $this->getData('id_commande_fournisseur_line');
            if (!$id_line) {
                $errors[] = 'ID de la ligne de commande fournisseur absent';
            }

            if (!count($errors)) {
                if ($commande->deleteline($id_line) <= 0) {
                    $errors[] = 'Echec de la suppression de la ligne de commande fournisseur d\'ID ' . $id_line;
                    BimpTools::getErrorsFromDolObject($commande, $errors);
                } else {
                    $this->set('id_commande_fournisseur', 0);
                    $this->set('id_commande_fournisseur_line', 0);
                    $errors = $this->update();
                    return true;
                }
            }
        } else {
            $errors[] = 'ID de la réservation absent';
        }

        return false;
    }

    // Overrides:

    public function validatePost()
    {
        $create_commande = false;
        if (BimpTools::getValue('id_commande_fournisseur', 0) === 'new') {
            $_POST['id_commande_fournisseur'] = 0;
            $create_commande = true;
        }

        $errors = parent::validatePost();

        if (!count($errors) && $create_commande) {
            BimpTools::loadDolClass('fourn', 'fournisseur.commande', 'CommandeFournisseur');
            $commande = new CommandeFournisseur($this->db->db);

            $id_fournisseur = 0;
            switch ((int) $this->getData('type')) {
                case 1:
                    $price = $this->getChildObject('fournisseur_price');
                    if (!is_null($price) && $price->isLoaded()) {
                        $id_fournisseur = (int) $price->getData('fk_soc');
                    }
                    break;

                case 2:
                    $id_fournisseur = (int) $this->getData('id_fournisseur');
                    break;
            }

            if (!$id_fournisseur) {
                $errors[] = 'Echec de la création de la commande fournisseur - fournisseur absent';
            } else {
                $id_entrepot = (int) $this->getData('id_entrepot');
                if (!$id_entrepot) {
                    $errors[] = 'Echec de la création de la commande fournisseur - entrepôt absent';
                } else {
                    $commande->socid = $id_fournisseur;
                    $commande->array_options['options_entrepot'] = $id_entrepot;

                    global $user;
                    $id_commande = $commande->create($user);
                    if ($id_commande <= 0) {
                        $errors[] = 'Echec de la création de la commande fournisseur';
                        BimpTools::getErrorsFromDolObject($commande, $errors);
                    } else {
                        $this->set('id_commande_fournisseur', $id_commande);
                    }
                }
            }
        }

        return $errors;
    }

    public function create()
    {
        $reservation = $this->getChildObject('reservation');

        if (is_null($reservation) || !$reservation->isLoaded()) {
            return array('ID de la réservation absent ou invalide');
        }

        $errors = array();

        $this->addToCommandeFournisseur($errors);
        if (count($errors)) {
            return $errors;
        }

        $errors = parent::create();

        if (!count($errors)) {
            $reservation = $this->getChildObject('reservation');

            $qty = (int) $this->getData('qty');
            $id_commande_fournisseur = (int) $this->getData('id_commande_fournisseur');
            if ($id_commande_fournisseur) {
                $res_errors = $reservation->setNewStatus(100, $qty);
            } else {
                $res_errors = $reservation->setNewStatus(3, $qty);
            }

            if (count($res_errors)) {
                $errors[] = 'Echec de la mise à jour du statut de la réservation';
                $errors = array_merge($errors, $res_errors);
            } else {
                $reservation->update();
            }
        }

        return $errors;
    }

    public function update()
    {
        $errors = array();

        $prev_id_commande_fournisseur = (int) $this->getSavedData('id_commande_fournisseur');

        $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
        $ref_reservation = $this->getData('ref_reservation');
        $qty = (int) $this->getData('qty');

        if ((int) $this->getData('id_commande_fournisseur')) {
            if (!$prev_id_commande_fournisseur) {
                $add_errors = array();
                $this->addToCommandeFournisseur($add_errors);
                if (count($add_errors)) {
                    $errors[] = 'Echec de l\'ajout à la commande fournisseur';
                    $errors = array_merge($errors, $add_errors);
                    return $errors;
                }
                if ($reservation->find(array(
                            'ref'          => $ref_reservation,
                            'status'       => 3,
                            'id_equipment' => 0
                        ))) {
                    $res_errors = $reservation->setNewStatus(100, $qty);
                    if (count($res_errors)) {
                        $errors[] = 'Echec de la mise à jour du statut de la réservation';
                        $errors = array_merge($errors, $res_errors);
                    } else {
                        $reservation->update();
                    }
                }
            }
        } elseif ($prev_id_commande_fournisseur) {
            if ($reservation->find(array(
                        'ref'          => $ref_reservation,
                        'status'       => 100,
                        'id_equipment' => 0
                    ))) {
                $res_errors = $reservation->setNewStatus(3, $qty);
                if (count($res_errors)) {
                    $errors[] = 'Echec de la mise à jour du statut de la réservation';
                    $errors = array_merge($errors, $res_errors);
                } else {
                    $reservation->update();
                }
            }
        }

        $errors = parent::update();

        return $errors;
    }
}

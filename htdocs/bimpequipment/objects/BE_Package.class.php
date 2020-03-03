<?php

class BE_Package extends BimpObject
{

    public $current_place = null;

    // Droits Users: 

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'addEquipment':
            case 'addProduct':
            case 'removeEquipment':
            case 'removeProduct':
            case 'moveEquipment':
            case 'moveProduct':
            case 'saveProductQty':
                return (int) $this->can('edit');
        }
        return (int) parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($this->hasEquipments() || $this->hasProducts()) {
            $errors[] = 'Ce package ne peut pas être supprimé car il contient encore des produits ou des équipements';
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'addProduct':
            case 'addEquipment':
                $curPlace = $this->getCurrentPlace();
                if (!BimpObject::objectLoaded($curPlace)) {
                    $errors[] = 'Aucun emplacement sélectionné pour ce package';
                    return 0;
                }
                break;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function hasEquipments()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT COUNT(id) as num FROM ' . MAIN_DB_PREFIX . 'be_equipment ';
            $sql .= ' WHERE `id_package` = ' . (int) $this->id;

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['num']) && (int) $result[0]['num'] > 0) {
                return 1;
            }
        }

        return 0;
    }

    public function hasProducts()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT COUNT(id) as num FROM ' . MAIN_DB_PREFIX . 'be_package_product ';
            $sql .= ' WHERE `id_package` = ' . (int) $this->id;

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['num']) && (int) $result[0]['num'] > 0) {
                return 1;
            }
        }

        return 0;
    }

    // Getters Array: 

    public static function getTypesPlaceArray()
    {
        BimpObject::loadClass('bimpequipment', 'BE_Place');

        return BE_Place::$types;
    }

    public function getAllPackagesArray()
    {
        $options = array();
        $filter = array(
            'id' => array(
                'operator' => '!=',
                'value'    => $this->getData('id')
        ));
        $packages = $this->getList($filter, null, null, 'id', 'desc', 'array', array('id', 'label', 'ref'));
        foreach ($packages as $package) {
            $options[$package['id']] = $package['ref'] . ' ' . $package['label'];
        }

        return $options;
    }

    // Getters: 

    public function getCurrentPlace()
    {
        if ($this->isLoaded()) {
            if (is_null($this->current_place)) {
                $place = BimpObject::getInstance($this->module, 'BE_PackagePlace');
                $items = $place->getList(array(
                    'id_package' => $this->id,
                    'position'   => 1
                        ), 1, 1, 'id', 'desc', 'array', array(
                    'id'
                ));

                if (isset($items[0])) {
                    $place = BimpCache::getBimpObjectInstance($this->module, 'BE_PackagePlace', (int) $items[0]['id']);
                    if ($place->isLoaded()) {
                        $this->current_place = $place;
                    } else {
                        $this->current_place = null;
                    }
                }
            }
        }

        return $this->current_place;
    }

    public function getEquipments()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        return BimpCache::getBimpObjectObjects('bimpequipment', 'Equipment', array(
                    'id_package' => (int) $this->id
        ));
    }

    public function getPackageProducts()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        return $this->getChildrenObjects('products');
    }

    public static function getQuantityMaxProduct()
    {
        $id_package_product = BimpTools::getPostFieldValue('id_package_product', 0);
        $package_product = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_PackageProduct', $id_package_product);
        return $package_product->getData('qty');
    }

    // Getters filtres: 

    public function getPlace_typeSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((string) $value) {
            $joins['placeType'] = array(
                'table' => 'be_package_place',
                'alias' => 'placeType',
                'on'    => 'placeType.id_package = ' . $main_alias . '.id'
            );

            $filters['placeType.position'] = 1;
            $filters['or_placeType'] = array(
                'or' => array(
                    'placeType.type' => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    )
                )
            );
        }
    }

    public function getPlaceSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((string) $value) {
            $joins['place'] = array(
                'table' => 'be_package_place',
                'alias' => 'place',
                'on'    => 'place.id_package = ' . $main_alias . '.id'
            );
            $joins['place_entrepot'] = array(
                'table' => 'entrepot',
                'alias' => 'place_entrepot',
                'on'    => 'place_entrepot.rowid = place.id_entrepot'
            );
            $joins['place_user'] = array(
                'table' => 'user',
                'alias' => 'place_user',
                'on'    => 'place_user.rowid = place.id_user'
            );
            $joins['place_client'] = array(
                'table' => 'societe',
                'alias' => 'place_client',
                'on'    => 'place_client.rowid = place.id_client'
            );

            $filters['place.position'] = 1;
            $filters['or_place'] = array(
                'or' => array(
                    'place.place_name'         => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_entrepot.lieu'      => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_entrepot.ref'       => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_user.firstname'     => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_user.lastname'      => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_client.nom'         => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_client.code_client' => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                )
            );
        }
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'place_date_end':
                // Bouton "Exclure" désactivé pour ce filtre: ne pas tenir compte de $excluded
                $joins['place'] = array(
                    'table' => 'be_package_place',
                    'on'    => 'place.id_package = a.id',
                    'alias' => 'place'
                );
                $joins['next_place'] = array(
                    'table' => 'be_package_place',
                    'on'    => 'next_place.id_package = a.id',
                    'alias' => 'next_place'
                );
                $filters['next_place_position'] = array('custom' => 'next_place.position = (place.position - 1)');

                $or_field = array();
                foreach ($values as $value) {
                    $or_field[] = BC_Filter::getRangeSqlFilter($value, $errors);
                }

                if (!empty($or_field)) {
                    $filters['next_place.date'] = array(
                        'or_field' => $or_field
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    // Affichages:

    public function displayEquipmentsToRemove()
    {
        $equipments = array();

        $id_equipment = (int) BimpTools::getPostFieldValue('id_equipment', 0);
        if ($id_equipment) {
            $equipments[] = $id_equipment;
        } else {
            $equipments = BimpTools::getPostFieldValue('equipments', array());
        }

        $html = '<input type="hidden" name="equipments" value="' . implode(',', $equipments) . '"/>';

        if (!empty($equipments)) {
            foreach ($equipments as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $html .= $equipment->getNomUrl(1, 1, 1, 'default');
                } else {
                    $html .= '<span class="danger">Equipement #' . $id_equipment . ' inexistant</span>';
                }
                $html .= '<br/>';
            }
        } else {
            $html .= '<span class="danger">Aucun équipement spécifié</span>';
        }

        return $html;
    }

    public function displayProductsToRemove()
    {
        $packageProducts = array();

        $id_pp = BimpTools::getPostFieldValue('id_package_product', 0);
        if ($id_pp) {
            $packageProducts[] = $id_pp;
        } else {
            $packageProducts = BimpTools::getPostFieldValue('packageProducts', array());
        }

        $html = '<input type="hidden" name="packageProducts" value="' . implode(',', $packageProducts) . '"/>';

        if (!empty($packageProducts)) {
            foreach ($packageProducts as $id_pp) {
                $pp = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_PackageProduct', (int) $id_pp);

                if (BimpObject::objectLoaded($pp)) {
                    $id_product = (int) $pp->getData('id_product');
                    if ($id_product) {
                        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
                        if (BimpObject::objectLoaded($product)) {
                            $html .= $product->dol_object->getNomUrl(1);
                        } else {
                            $html .= '<span class="danger">Produit #' . $id_product . ' inexistant</span>';
                        }
                    } else {
                        $html .= '<span class="danger">Aucun produit enregistré pour la ligne #' . $id_pp . '</span>';
                    }
                } else {
                    $html .= '<span class="danger">La ligne produit d\'ID ' . $id_pp . ' n\'existe pas</span>';
                }
                $html .= '<br/>';
            }
        } else {
            $html .= '<span class="danger">Aucun produit spécifié</span>';
        }

        return $html;
    }

    public function displayCurrentPlace($no_html = false)
    {
        if ($this->isLoaded()) {
            $place = $this->getCurrentPlace();

            if (BimpObject::objectLoaded($place)) {
                if ($no_html) {
                    return $place->getPlaceName();
                } else {
                    return $place->displayPlace();
                }
            }
        }

        return '';
    }

    public function displayCurrentPlaceType()
    {
        $place = $this->getCurrentPlace();
        if (!is_null($place) && $place->isLoaded()) {
            return $place->displayData("type");
        }

        return '';
    }

    // Traitements:

    public function addEquipment($id_equipment, $code_mouv, $label_mouv, $date_mouv = '', &$warnings = array(), $force = 0)
    {
        $errors = array();

        if ($date_mouv == '') {
            $date_mouv = date('Y-m-d H:i:s');
        }

        if (!$code_mouv) {
            $code_mouv = 'PACKAGE' . $this->id . '_ADD';
        }

        if (!$label_mouv) {
            $label_mouv = 'Ajout au package ' . $this->getRef();
        }

        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
        if (!BimpObject::objectLoaded($equipment)) {
            $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
        } else {
            $package = null;
            if ((int) $equipment->getData('id_package')) {
                $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $equipment->getData('id_package'));
            }
            if (BimpObject::objectLoaded($package) and ! $force) {
                $errors[] = 'L\'équipement ' . $equipment->getNomUrl(0, 1, 1, 'default') . ' est déjà attribué au package ' . $package->getNomUrl(0, 1, 1, 'default');
            } else {
                if (!$equipment->isAvailable(0, $errors) and ! $force) {
                    return $errors;
                }

                $errors = $equipment->updateField('id_package', (int) $this->id);

                if (!count($errors)) {
                    $warnings = $this->setEquipmentPlace($equipment, $code_mouv, $label_mouv, $date_mouv, 'package', (int) $this->id);
                }
            }
        }

        return $errors;
    }

    public function addProduct($id_product, $qty, $id_entrepot = 0, &$warnings = array())
    {
        // $qty can be < 0 => A éviter, utiliser plutôt saveProductQty(). 

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if (!$id_entrepot and $id_entrepot != -1) {
            $errors[] = 'Aucun entrepôt d\'origine spécifié';
            return $errors;
        }

        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);

        if (!BimpObject::objectLoaded($product)) {
            $errors[] = 'Le produit d\'ID ' . $id_product . ' n\'existe pas';
            return $errors;
        }

        if ($product->isTypeService()) {
            $errors[] = 'Le produit "' . $product->getRef() . '" est de type "service"';
            return $errors;
        }

        if ($product->isSerialisable()) {
            $errors[] = 'Le produit "' . $product->getRef() . '" est sérialisé. Veuillez ajouter les équipements via leurs numéros de série';
            return $errors;
        }

        $pp = BimpCache::findBimpObjectInstance('bimpequipment', 'BE_PackageProduct', array(
                    'id_package' => (int) $this->id,
                    'id_product' => (int) $id_product
        ));

        if (!BimpObject::objectLoaded($pp)) {
            $pp = BimpObject::getInstance('bimpequipment', 'BE_PackageProduct');
            $errors = $pp->validateArray(array(
                'id_package' => (int) $this->id,
                'id_product' => (int) $id_product,
                'qty'        => $qty
            ));

            if (!count($errors)) {
                $errors = $pp->create($warnings, true);
            }
        } else {
            $errors = $pp->updateField('qty', $pp->getData('qty') + $qty);
        }

        if (!count($errors)) {
            if ($qty > 0) {
                $stock_errors = $this->onProductIn($id_product, $qty, $id_entrepot);
            } elseif ($qty < 0) {
                $stock_errors = $this->onProductOut($id_product, abs($qty), $id_entrepot);
            }

            if (count($stock_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks');
            }
        }

        if ((int) $pp->getData('qty') == 0) {
            $pp->delete();
        }

        return $errors;
    }

    public function removePackageProduct($id_packageProduct, $id_entrepot_dest = 0, &$warnings = array(), $mvt_infos = '')
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if (!(int) $id_packageProduct) {
            $errors[] = 'ID de la ligne produit absent';
            return $errors;
        }

        $pp = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_PackageProduct', (int) $id_packageProduct);

        if (!BimpObject::objectLoaded($pp)) {
            $errors[] = 'La ligne produit d\'ID ' . $id_packageProduct . ' n\'existe pas';
            return $errors;
        }

        $id_product = (int) $pp->getData('id_product');
        $qty = (int) $pp->getData('qty');

        $pp_warnings = array();
        $pp_errors = $pp->delete($warnings, true);

        if (count($pp_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($pp_warnings, 'Erreurs suite à la suppression de la ligne produit');
        }

        if (count($pp_errors)) {
            $errors[] = BimpTools::getMsgFromArray($pp_errors, 'Echec de la suppression de la ligne produit');
        } else {
            $stock_errors = $this->onProductOut($id_product, $qty, $id_entrepot_dest, '', 0, $mvt_infos);

            if (count($stock_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks');
            }
        }

        return $errors;
    }

    public function saveProductQty($id_packageProduct, $new_qty, $id_entrepot = 0, &$warnings = array(), $mvt_infos = '')
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if ((int) $new_qty == 0) {
            return $this->removePackageProduct($id_packageProduct, $id_entrepot, $warnings, $mvt_infos);
        }

        if (!(int) $id_packageProduct) {
            $errors[] = 'ID de la ligne produit absent';
            return $errors;
        }

        $pp = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_PackageProduct', (int) $id_packageProduct);

        if (!BimpObject::objectLoaded($pp)) {
            $errors[] = 'La ligne produit d\'ID ' . $id_packageProduct . ' n\'existe pas';
            return $errors;
        }

        $init_qty = (int) $pp->getData('qty');

        $diff = (int) $new_qty - $init_qty;

        if (!$diff) {
            $errors[] = 'La quantité indiquée est la même que celle déjà enregistrée';
            return $errors;
        }

        $up_errors = $pp->updateField('qty', (int) $new_qty);

        if (count($up_errors)) {
            $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour des quantités');
            return $errors;
        }

        if ($diff > 0) {
            $stock_errors = $this->onProductIn((int) $pp->getData('id_product'), $diff, $id_entrepot, '', 0, $mvt_infos);
        } else {
            $stock_errors = $this->onProductOut((int) $pp->getData('id_product'), abs($diff), $id_entrepot, '', 0, $mvt_infos);
        }

        if (count($stock_errors)) {
            $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la mise à jour des stocks');
        }

        return $errors;
    }

    public function onNewPlace()
    {
        if ($this->isLoaded()) {
            $prev_place = $this->getCurrentPlace();
            $equipments = $this->getEquipments();
            $packageProducts = $this->getPackageProducts();
            $ref = $this->getRef();

            $place = BimpObject::getInstance($this->module, 'BE_PackagePlace');
            $items = $place->getList(array(
                'id_package' => $this->id
                    ), 2, 1, 'id', 'desc', 'array', array(
                'id'
            ));

            $new_place = BimpCache::getBimpObjectInstance($this->module, 'BE_PackagePlace', (int) $items[0]['id']);

            if (BimpObject::objectLoaded($new_place)) {
                $this->current_place = $new_place;

                $codemove = $new_place->getData('code_mvt');
                if (is_null($codemove) || !$codemove) {
                    $codemove = 'PACKAGE' . (int) $this->id . '_PLACE' . (int) $new_place->id;
                }

                $new_place_infos = $new_place->getData('infos');
                $label = ($new_place_infos ? $new_place_infos . ' - ' : '') . 'Package #' . $this->id . ($ref ? ' - ' . $ref : '');
                $origin = $new_place->getData('origin');
                $id_origin = (int) $new_place->getData('id_origin');

                if (!$origin || !$id_origin) {
                    global $user;
                    $origin = 'user';
                    $id_origin = (int) $user->id;
                }

                // Maj de l'emplacement des équipements: 
                foreach ($equipments as $equipment) {
                    $this->setEquipmentPlace($equipment, $codemove, $label, '');
                }

                // Maj des stocks des produits non sérialisés: 
                if (!empty($packageProducts)) {
                    $prev_place = null;
                    if (isset($items[1])) {
                        $prev_place = BimpCache::getBimpObjectInstance($this->module, 'BE_PackagePlace', $items[1]['id']);
                        if (BimpObject::objectLoaded($prev_place)) {
                            if ((int) $prev_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT && (int) $prev_place->getData('id_entrepot')) {
                                foreach ($packageProducts as $pp) {
                                    $product = $pp->getChildObject('product');
                                    if (BimpObject::objectLoaded($product)) {
                                        $product->correctStocks((int) $prev_place->getData('id_entrepot'), (int) $pp->getData('qty'), Bimp_Product::STOCK_OUT, $codemove, $label . ' - Emplacement de destination: ' . $new_place->getPlaceName(), $origin, $id_origin);
                                    }
                                }
                            }
                        }
                    }

                    if ((int) $new_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT && (int) $new_place->getData('id_entrepot')) {
                        if (BimpObject::objectLoaded($prev_place)) {
                            $label .= ' - Emplacement d\'origine: ' . $prev_place->getPlaceName();
                        }
                        foreach ($packageProducts as $pp) {
                            $product = $pp->getChildObject('product');
                            if (BimpObject::objectLoaded($product)) {
                                $product->correctStocks((int) $new_place->getData('id_entrepot'), (int) $pp->getData('qty'), Bimp_Product::STOCK_IN, $codemove, $label, $origin, $id_origin);
                            }
                        }
                    }
                }
            }
        }
    }

    public function onProductIn($id_product, $qty, $id_entrepot_src = 0, $origin = '', $id_origin = 0, $mvt_infos = '')
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);

        if (!BimpObject::objectLoaded($product)) {
            $errors[] = 'Le produit d\'ID ' . $id_product . ' n\'existe pas';
            return $errors;
        }

        $place = $this->getCurrentPlace();

        $id_entrepot_dest = 0;
        $code_move = 'PACKAGE' . $this->id . '_ADD';
        $label = 'Ajout au package #' . $this->id;

        if (BimpObject::objectLoaded($place)) {
            if ((int) $place->getData('type') === BE_Place::BE_PLACE_ENTREPOT) {
                $id_entrepot_dest = (int) $place->getData('id_entrepot');
                if (!$id_entrepot_dest) {
                    $errors[] = 'ID de l\'entrepôt absent pour l\'emplacement actuel du package';
                    return $errors;
                }
            }

            if (!$mvt_infos) {
                $label .= ' - Nouvel emplacement: ' . $place->getPlaceName();
            }
        }

        if ($mvt_infos) {
            $label .= ' - ' . $mvt_infos;
        }

        if ((int) $id_entrepot_src === (int) $id_entrepot_dest) {
            return array();
        }

        if (!$origin || !$id_origin) {
            $origin = 'package';
            $id_origin = (int) $this->id;
        }

        if ($id_entrepot_src) {
            $stock_errors = $product->correctStocks((int) $id_entrepot_src, $qty, Bimp_Product::STOCK_OUT, $code_move, $label, $origin, $id_origin);
            if (count($stock_errors)) {
                $errors[] = BimpTools::getMsgFromArray($stock_errors);
            }
        }

        if ($id_entrepot_dest) {
            $stock_errors = $product->correctStocks((int) $id_entrepot_dest, $qty, Bimp_Product::STOCK_IN, $code_move, $label, $origin, $id_origin);
            if (count($stock_errors)) {
                $errors[] = BimpTools::getMsgFromArray($stock_errors);
            }
        }
        return $errors;
    }

    public function onProductOut($id_product, $qty, $id_entrepot_dest = 0, $origin = '', $id_origin = 0, $mvt_infos = '')
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);

        if (!BimpObject::objectLoaded($product)) {
            $errors[] = 'Le produit d\'ID ' . $id_product . ' n\'existe pas';
            return $errors;
        }

        $place = $this->getCurrentPlace();
        $id_entrepot_src = 0;
        $code_move = 'PACKAGE' . $this->id . '_REMOVE';
        $label = 'Retrait du package #' . $this->id;

        if (!$mvt_infos && $id_entrepot_dest) {
            $entrepot = BimpCache::getDolObjectInstance((int) $id_entrepot_dest, 'product/stock', 'entrepot');
            if (BimpObject::objectLoaded($entrepot)) {
                $label .= ' - Entrepôt de destination: ' . $entrepot->ref . ' - ' . $entrepot->lieu;
            } else {
                $label .= ' - Destination: entrepôt #' . $id_entrepot_dest;
            }
        }

        if ($mvt_infos) {
            $label .= ' - ' . $mvt_infos;
        }

        if (!$origin || !$id_origin) {
            $origin = 'package';
            $id_origin = (int) $this->id;
        }

        if (BimpObject::objectLoaded($place)) {
            if ((int) $place->getData('type') === BE_Place::BE_PLACE_ENTREPOT) {
                $id_entrepot_src = (int) $place->getData('id_entrepot');
                if (!$id_entrepot_src) {
                    $errors[] = 'ID de l\'entrepôt absent pour l\'emplacement actuel du package';
                    return $errors;
                }
            }
        }

        if ((int) $id_entrepot_src === (int) $id_entrepot_dest) {
            return array();
        }

        if ($id_entrepot_src) {
            $stock_errors = $product->correctStocks((int) $id_entrepot_src, $qty, Bimp_Product::STOCK_OUT, $code_move, $label, $origin, $id_origin);
            if (count($stock_errors)) {
                $errors[] = BimpTools::getMsgFromArray($stock_errors);
            }
        }

        if ($id_entrepot_dest) {
            $stock_errors = $product->correctStocks((int) $id_entrepot_dest, $qty, Bimp_Product::STOCK_IN, $code_move, $label, $origin, $id_origin);
            if (count($stock_errors)) {
                $errors[] = BimpTools::getMsgFromArray($stock_errors);
            }
        }

        return $errors;
    }

    public function setEquipmentPlace(Equipment $equipment, $code_mouv, $label_mouv, $date_mouv = '', $origin = '', $id_origin = 0)
    {
        $errors = array();

        if ($date_mouv == '') {
            $date_mouv = date('Y-m-d H:i:s');
        }

        if ($this->isLoaded($errors)) {
            if (!BimpObject::objectLoaded($equipment)) {
                $errors[] = 'ID de l\'équipement absent';
                return $errors;
            }

            $place = $this->getCurrentPlace();

            if (BimpObject::objectLoaded($place)) {
                $eq_place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                $data = $place->getDataArray();

                unset($data['id_package']);
                $data['id_equipment'] = $equipment->id;
                $data['infos'] = $label_mouv;
                $data['code_mvt'] = $code_mouv;
                $data['date'] = $date_mouv;

                if ($origin && (int) $id_origin) {
                    $data['origin'] = $origin;
                    $data['id_origin'] = (int) $id_origin;
                }

                $eq_errors = $eq_place->validateArray($data);

                if (!count($eq_errors)) {
                    $eq_warnings = array();
                    $eq_errors = $eq_place->create($eq_warnings, true);

                    if (count($eq_warnings)) {
                        $errors[] = BimpTools::getMsgFromArray($eq_warnings, 'Erreurs suite à la création de l\'émplacement de l\'équipement "' . $equipment->getData('serial') . '"');
                    }
                }

                if (count($eq_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Echec de la création de l\'émplacement de l\'équipement "' . $equipment->getData('serial') . '"');
                }
            }
        }

        return $errors;
    }

    /**
     * 
     * @param int $id_package_src
     * @param int $id_package_dest
     * @param array $products   array(id_product => qty);
     *  si qty < 0       src => dest et dest => src
     * @param array $equipments array(inutile    => id_equipment);
     * @return array(errors)
     */
    public static function moveElements($id_package_src, $id_package_dest, $products = array(), $equipments = array())
    {
        $errors = array();

        if ($id_package_src < 1) {
            $errors[] = 'Le package source n\'est pas défini';
        }

        if ($id_package_dest < 1) {
            $errors[] = 'Le package de destination n\'est pas défini';
        }

        $package_src = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $id_package_src);
        $package_dest = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $id_package_dest);

        if (!BimpObject::objectLoaded($package_src)) {
            $errors[] = 'Le package source d\'ID ' . $id_package_src . ' n\'existe pas';
        }

        if (!BimpObject::objectLoaded($package_dest)) {
            $errors[] = 'Le package de destination d\'ID ' . $id_package_dest . ' n\'existe pas';
        }

        if (!count($errors)) {
            $p_products = $package_src->getPackageProducts();

            // Vérification des produits et de leurs quantité
            foreach ($products as $id_product => $qty) {
                foreach ($p_products as $p_product) {
                    if ((int) $id_product == (int) $p_product->getData('id_product')) {
                        if($qty > 0)
                            $errors = array_merge($errors, self::moveProduct($p_product->id, $id_package_dest, $qty, -1));
                        else
                            $errors = array_merge($errors, self::moveProduct($p_product->id, $id_package_src, $qty, -1));
                    }
                }
            }
            
            // Vérification des équipements
            $code_mvt = $package_src->getData('ref') . '-' . $id_package_dest;
            $stock_label = 'Déplacement de ' . $package_src->getData('ref') . ' au package n°' . $id_package_dest;

            foreach ($equipments as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                $errors = BimpTools::merge_array($errors, $equipment->moveToPackage($id_package_dest, $code_mvt, $stock_label, 1));
            }
        }

        return $errors;
    }

    // Si qty < 0 => inversion du sens du mouvement
    public static function moveProduct($id_package_product_src, $id_package_dest, $qty, $id_entrepot)
    {
        $errors = array();

        if ($id_package_product_src < 1)
            $errors[] = 'Produit non renseigné';

        if ($id_package_dest < 1)
            $errors[] = 'Package de destination non renseigné';

        if ($qty == 0)
            $errors[] = 'Quantité nulle';
        
        if((float) $qty < 0) {
            $tmp = $id_package_product_src;
            $id_package_product_src = $id_package_dest;
            $id_package_dest = $tmp;
            $qty *= -1;
        }

        if (count($errors))
            return $errors;

        $package_product_src = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_PackageProduct', $id_package_product_src);
        $package_dest = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package_dest);

        if (!BimpObject::objectLoaded($package_product_src)) {
            $errors[] = 'Le produit en package source d\'ID ' . $id_package_product_src . ' n\'existe pas';
        } else {
            $package_src = $package_product_src->getParentInstance();

            if (!BimpObject::objectLoaded($package_src)) {
                $errors[] = 'Le package source d\'ID ' . $package_product_src->getData('id_package') . ' n\'existe pas';
            }
        }

        if (!BimpObject::objectLoaded($package_dest)) {
            $errors[] = 'Le package de destination d\'ID ' . $package_product_src->getData('id_package') . ' n\'existe pas';
        }

        if (!count($errors)) {
            $id_product = $package_product_src->getData('id_product');

            // Ajout dans $package_dest
            $errors = BimpTools::merge_array($errors, $package_dest->addProduct($id_product, $qty, $id_entrepot));

            if (!count($errors)) {
                // Retrait dans $package_product_src
                $new_qty = (int) $package_product_src->getData('qty') - (int) $qty;
                $warnings = array();
                $errors = BimpTools::merge_array($errors, $package_src->saveProductQty($id_package_product_src, $new_qty, 0, $warnings, 'Destination: package ' . $package_dest->getRef() . ' (Nouvel emplacement: ' . $package_dest->displayCurrentPlace(true) . ')'));
            }
        }

        return $errors;
    }

    public function addPlace($entrepot, $type, $date, $infos, $code_mvt, $origin = '', $id_origin = 0)
    {

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $place = BimpObject::getInstance('bimpequipment', 'BE_PackagePlace');
        $errors = BimpTools::merge_array($errors, $place->validateArray(array(
                    'id_package'  => (int) $this->id,
                    'id_entrepot' => (int) $entrepot,
                    'type'        => (int) $type,
                    'date'        => $date,
                    'infos'       => $infos,
                    'code_mvt'    => $code_mvt,
                    'origin'      => $origin,
                    'id_origin'   => $id_origin
        )));

        $errors = BimpTools::merge_array($errors, $place->create($w, true));

        return $errors;
    }

    // Rendus HTML: 

    public function renderEquipmentsQuickForm()
    {
        $html = '';

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $curPlace = $this->getCurrentPlace();

        if (!BimpObject::objectLoaded($curPlace)) {
            return '';
        }

        $html .= '<div class="packageAddEquipmentForm singleLineForm" style="margin-bottom: 15px; width: 100%" data-id_package="' . $this->id . '">';
        $html .= '<div class="singleLineFormCaption">';
        $html .= '<h4>' . BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajout d\'équipements</h4>';
        $html .= '</div>';

        $html .= '<div class="singleLineFormContent">';
        $content .= BimpInput::renderInput('text', 'search_serial', '', array(
                    'extra_class' => 'large',
                    'style'       => 'width: 300px;'
        ));
        $content .= '<br/><span class="small">N° de série</span>';
        $html .= BimpInput::renderInputContainer('search_serial', '', $content, '');

        $html .= '<button id="addPackageEquipmentButton" type="button" class="btn btn-primary" onclick="addPackageEquipment($(this));">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
        $html .= '</button>';
        $html .= '<div class="quickAddForm_ajax_result"></div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderEquipmentsList()
    {
        $html .= '';

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

        $list = new BC_ListTable($equipment, 'package', 1, null, 'Equipements inclus', $equipment->params['icon']);
        $list->addFieldFilterValue('id_package', (int) $this->id);

        $html .= $list->renderHtml();
        return $html;
    }

    public function renderProductsQuickForm()
    {
        $html = '';

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $curPlace = $this->getCurrentPlace();

        if (!BimpObject::objectLoaded($curPlace)) {
            return BimpRender::renderAlerts('Vous devez ajouter un emplacement pour pouvoir ajouter des élements au package', 'warning');
        }

        $html .= '<div class="packageAddProductForm singleLineForm" style="margin-bottom: 15px; width: 100%" data-id_package="' . $this->id . '">';
        $html .= '<div class="singleLineFormCaption">';
        $html .= '<h4>' . BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajout de produits non sérialisés</h4>';
        $html .= '</div>';

        $html .= '<div class="singleLineFormContent">';
        $content = BimpInput::renderInput('search_product', 'id_product', 0, array(
                    'filter_type' => 'product'
        ));
        $content .= '<br/><span class="small">Rechercher un produit</span>';
        $html .= BimpInput::renderInputContainer('id_product', '', $content, '');

        $content = BimpInput::renderInput('qty', 'qty_product', 1, array(
                    'data' => array(
                        'min'      => 0,
                        'max'      => 'none',
                        'decimals' => 0
                    )
        ));
        $content .= '<br/><span class="small">Quantité</span>';
        $html .= BimpInput::renderInputContainer('qty_product', 1, $content);

        $content = BimpInput::renderInput('search_entrepot', 'id_entrepot_src', 0, array(
                    'include_empty' => 1
        ));
        $content .= '<br/><span class="small">Entrepôt d\'origine</span>';
        $html .= BimpInput::renderInputContainer('qty_product', 1, $content);

        $html .= '<button type="button" class="btn btn-primary" onclick="addPackageProduct($(this));">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
        $html .= '</button>';
        $html .= '<div class="quickAddForm_ajax_result"></div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderProductsList()
    {
        if ($this->isLoaded()) {
            $pp = BimpObject::getInstance('bimpequipment', 'BE_PackageProduct');

            $list = new BC_ListTable($pp, 'default', 1, $this->id, 'Produits non sérialisés inclus', 'fas_box');
            return $list->renderHtml();
        }

        return '';
    }

    public function renderEquipmentPlaceForm()
    {
        $html = '';

        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
        $form = new BC_Form($place, null, 'default', 1, true);

        $html .= '<div class="equipment_place_form">';
        $html .= $form->renderHtml();
        $html .= '</div>';

        return $html;
    }

    // Actions:

    public function actionAddProduct($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Produit(s) non sérialisé(s) ajouté(s) avec succès';

        if (!isset($data['id_product']) || !(int) $data['id_product']) {
            $errors[] = 'Aucun produit sélectionné';
        } elseif (!isset($data['qty']) || (int) $data['qty'] <= 0) {
            $errors[] = 'Veillez indiquer une quantité supérieure à 0';
        } elseif (!isset($data['id_entrepot']) || !(int) $data['id_entrepot']) {
            $errors[] = 'Veuillez sélectionner un entrepôt d\'origine';
        } else {
            $errors = $this->addProduct((int) $data['id_product'], (int) $data['qty'], (isset($data['id_entrepot']) ? (int) $data['id_entrepot'] : 0), $warnings);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Equipement ajouté avec succès';
        $html = '';

        $id_equipment = 0;
        if (isset($data['id_equipment']) && (int) $data['id_equipment']) {
            $id_equipment = (int) $data['id_equipment'];
        } else {
            if (!isset($data['serial']) || !(string) $data['serial']) {
                $errors[] = 'Veuillez saisir un numéro de série';
            } else {
                BimpObject::loadClass('bimpequipment', 'Equipment');
                $equipments = Equipment::findEquipments((string) $data['serial']);

                if (empty($equipments)) {
                    $errors[] = 'Aucun équipement trouvé pour le numéro de série "' . $data['serial'] . '"';
                } elseif (count($equipments) > 1) {
                    $html = BimpRender::renderAlerts(count($equipments) . ' équipements trouvés pour le numéro de série "' . $data['serial'] . '"', 'info');
                    $html .= '<table class="bimp_list_table">';
                    $html .= '<tbody>';
                    foreach ($equipments as $id_eq) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_eq);
                        if (BimpObject::objectLoaded($equipment)) {
                            $html .= '<tr>';
                            $html .= '<td>' . $equipment->displayProduct('nom_url', false, true) . '</td>';
                            $html .= '<td>';
                            $html .= '<span class="btn btn-default" onclick="' . $this->getJsActionOnclick('addEquipment', array('id_equipment' => $id_eq), array()) . '">';
                            $html .= 'Sélectionner' . BimpRender::renderIcon('fas_chevron-right', 'iconRight');
                            $html .= '</span>';
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                    $html .= '</tbody>';
                    $html .= '</table>';
                } else {
                    $id_equipment = (int) $equipments[0];
                }
            }
        }

        if ($id_equipment) {
            $errors = $this->addEquipment($id_equipment, '', '', null, $warnings);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'html'     => $html
        );
    }

    public function actionRemoveProduct($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Produit retiré avec succès';

        $pps = array();

        $id_pp = (isset($data['id_package_product']) ? (int) $data['id_package_product'] : 0);
        $id_entrepot_dest = (isset($data['id_entrepot_dest']) ? (int) $data['id_entrepot_dest'] : 0);

        if ($id_pp) {
            $pps[] = $id_pp;
        } elseif (isset($data['packageProducts'])) {
            if (is_string($data['packageProducts'])) {
                $data['packageProducts'] = explode(',', $data['packageProducts']);
            }
            $pps = $data['packageProducts'];
        }

        if (empty($pps)) {
            $errors[] = 'Aucun produit à retirer spécifié';
        }

        if (!count($errors)) {
            $nDone = 0;
            foreach ($pps as $id_pp) {
                $pp = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_PackageProduct', (int) $id_pp);

                if (!BimpObject::objectLoaded($pp)) {
                    $errors[] = 'La ligne produit #' . $id_pp . ' n\'existe pas';
                    continue;
                }

                $pp_warning = array();
                $pp_errors = $this->removePackageProduct($id_pp, $id_entrepot_dest, $warnings);

                if (count($pp_warning) || count($pp_errors)) {
                    $product = $pp->getChildObject('product');

                    if (count($pp_warning)) {
                        $warnings[] = BimpTools::getMsgFromArray($pp_warning, 'Erreurs lors du retrait du produit "' . (BimpObject::objectLoaded($product) ? $product->getRef() : '#' . (int) $pp->getData('id_product')) . '"');
                    }

                    if (count($pp_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($pp_errors, 'Echec du retrait du produit "' . (BimpObject::objectLoaded($product) ? $product->getRef() : '#' . (int) $pp->getData('id_product')) . '"');
                    } else {
                        $nDone++;
                    }
                }
            }

            if ($nDone > 1) {
                $success = $nDone . ' produits retirés avec succès';
            } elseif ($nDone > 0) {
                $success = 'Produit retiré avec succès';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpequipment\', \'BE_PackageProduct\', 0)'
        );
    }

    public function actionRemoveEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $equipments = array();

        $id_equipment = (isset($data['id_equipment']) ? (int) $data['id_equipment'] : 0);

        if (isset($data['id_equipment']) && (int) $data['id_equipment']) {
            $equipments[] = (int) $data['id_equipment'];
        } elseif (isset($data['equipments'])) {
            $equipments = explode(',', $data['equipments']);
        }

        if (empty($equipments)) {
            $errors[] = 'Aucun équipement à retirer spécifié';
        } else {
            $set_place = false;

            if ((!isset($data['keep_place']) || !(int) $data['keep_place']) &&
                    isset($data['type'])) {
                $set_place = true;
                $post_temp = $_POST;
                $_POST = $data;

                // Vérif de l'emplacement: 
                $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                $place_errors = $place->validatePost();
                if (!count($place_errors)) {
                    foreach ($equipments as $id_equipment) {
                        $place->set('id_equipment', (int) $id_equipment);
                        break;
                    }

                    $place_errors = $place->validate();
                }

                if (count($place_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($place_errors, 'Erreurs concernant le nouvel emlacement');
                } else {
                    unset($_POST['keep_place']);
                    unset($_POST['equipments']);
                }

                unset($place);
            }

            if (!count($errors)) {
                $nDone = 0;
                $code_move = 'PACKAGE' . $this->id . '_REMOVE';
                $label_move = 'Retrait du package ' . $this->getRef();

                foreach ($equipments as $id_equipment) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);

                    if (!BimpObject::objectLoaded($equipment)) {
                        $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                    } else {
                        if ((int) $equipment->getData('id_package') !== (int) $this->id) {
                            $errors[] = 'L\'équipement ' . $equipment->getNomUrl(0, 1, 1, 'default') . ' n\'est pas inclus dans ce package';
                        } else {
                            $up_errors = $equipment->updateField('id_package', 0);

                            if (count($up_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec du retrait de l\'équipement ' . $equipment->getRef());
                            } else {
                                $nDone++;

                                if ($set_place) {
                                    $_POST['id_equipment'] = $equipment->id;

                                    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');

                                    $place_errors = $place->validatePost();
                                    if (!count($place_errors)) {
                                        $place->set('code_mvt', $code_move);
                                        $infos = $place->getData('infos');
                                        $place->set('infos', $label_move . ($infos ? ' - ' . $infos : ''));
                                        $place->set('origin', 'package');
                                        $place->set('id_origin', (int) $this->id);

                                        $place_warnings = array();
                                        $place_errors = $place->create($place_warnings, true);

                                        if (count($place_warnings)) {
                                            $warnings[] = BimpTools::getMsgFromArray($place_warnings, 'Erreurs lors de la création du nouvel emplacement pour l\'équipement ' . $equipment->getRef());
                                        }
                                    }

                                    if (count($place_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création du nouvel emplacement pour l\'équipement ' . $equipment->getRef());
                                    }
                                }
                            }
                        }
                    }
                }

                if ($nDone > 1) {
                    $success = $nDone . ' équipements retirés avec succès';
                } elseif ($nDone > 0) {
                    $success = 'Equipement retiré avec succès';
                }
            }

            if ($set_place) {
                $_POST = $post_temp;
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpequipment\', \'Equipment\', 0)'
        );
    }

    public function actionMoveEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $ids_equipment = $data['equipments'];
        if (!is_array($ids_equipment))
            $ids_equipment = array($ids_equipment);

        $id_package_dest = (int) isset($data['id_package_dest']) ? $data['id_package_dest'] : 0;

        if (!$id_package_dest) {
            $errors[] = 'Package de destination non spécifié';
        } else {
            $package_dest = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package_dest);
            if (!BimpObject::objectLoaded($package_dest)) {
                $errors[] = 'Le package de destination d\'ID ' . $id_package_dest . ' n\'existe pas';
            }
        }

        if (!count($errors)) {
            $code_mvt = 'PACKAGE' . $id_package_dest . '_ADD';
            $stock_label = 'Déplacement du package ' . $this->getRef() . ' au package ' . $package_dest->getRef();

            foreach ($ids_equipment as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                $errors = BimpTools::merge_array($errors, $equipment->moveToPackage($id_package_dest, $code_mvt, $stock_label, 1));
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpequipment\', \'BE_Package\', 0)'
        );
    }

    public function actionSaveProductQty($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Quantités mises à jour avec succès';

        $id_pp = (isset($data['id_package_product']) ? (int) $data['id_package_product'] : 0);
        $qty = (isset($data['qty']) ? (int) $data['qty'] : 0);
        $id_entrepot = (isset($data['id_entrepot']) ? (int) $data['id_entrepot'] : 0);

        if (!$id_pp) {
            $errors[] = 'ID de la ligne produit absent';
        }

        if ($qty < 0) {
            $errors[] = 'Veuillez indiquer une quantité supérieur à 0';
        }

        if (!count($errors)) {
            $errors = $this->saveProductQty($id_pp, $qty, $id_entrepot, $warnings);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpequipment\', \'BE_PackageProduct\', 0)'
        );
    }

    public function actionMoveProduct($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Produit déplacé avec succès';

        $id_package_product = (isset($data['id_package_product']) ? (int) $data['id_package_product'] : 0);
        $id_package_dest = (isset($data['id_package_product']) ? (int) $data['id_package_dest'] : 0);
        $qty = (isset($data['id_package_product']) ? $data['qty'] : 0);

        if (!count($errors)) {
            $errors = BimpTools::merge_array($errors, self::moveProduct($id_package_product, $id_package_dest, $qty, -1));
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpequipment\', \'BE_PackageProduct\', 0)'
        );
    }

    // Overrides :

    public function create(&$warnings = array(), $force_create = false)
    {
        $ref = BimpTools::getNextRef($this->getTable(), 'ref', 'PKG{AA}{MM}-');

        if (!$ref) {
            return array('Echec attribution d\'une nouvelle référence');
        }

        $this->set('ref', $ref);

        return parent::create($warnings, $force_create);
    }
    
    
    public function getActionsButtons(){
        $filters = $joins = array();
        $filters['bimp_origin'] = 'package';
        $filters['bimp_id_origin'] = $this->id;
        $pp = BimpObject::getInstance('bimpcore', 'BimpProductMouvement');
        $onclick = $pp->getJsLoadModalList('default', array(
                'title'         => 'Détail mouvements package '.$this->getNomUrl(),
                'extra_filters' => $filters,
                'extra_joins'   => $joins
            ));

        $buttons[] = array(
            'label'   => 'Détail mouvements',
            'icon'    => 'fas_bars',
            'onclick' => $onclick
        );
        
        return $buttons;
    }
}

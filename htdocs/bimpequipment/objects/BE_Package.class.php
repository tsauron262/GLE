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
            case 'saveProductQty':
                return (int) $this->can('edit');
        }
        return (int) parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isDeletable($force_delete = false, &$errors = array())
    {
        // todo: checker si produits / équipements présents 
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        return parent::isActionAllowed($action, $errors);
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

    // Traitements:

    public function checkEquipments()
    {
        $equipments = $this->getData('equipments');
        $update = false;

        foreach ($equipments as $key => $id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            if (!BimpObject::objectLoaded($equipment)) {
                unset($equipments[$key]);
                $update = true;
            }
        }

        if ($update) {
            $this->updateField('equipments', $equipments);
        }
    }

    public function checkProducts()
    {
        $products = $this->getData('products');
        $update = false;

        foreach ($products as $id_product => $qty) {
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);
            if (!BimpObject::objectLoaded($product)) {
                unset($products[(int) $id_product]);
                $update = true;
            }
        }

        if ($update) {
            $this->updateField('products', $products);
        }
    }

    public function addEquipment($id_equipment, &$warnings = array())
    {
        $errors = array();

        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
        if (!BimpObject::objectLoaded($equipment)) {
            $errors[] = 'L\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
        } else {
            $package = null;
            if ((int) $equipment->getData('id_package')) {
                $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $equipment->getData('id_package'));
            }
            if (BimpObject::objectLoaded($package)) {
                $errors[] = 'L\'équipement ' . $equipment->getNomUrl(0, 1, 1, 'default') . ' est déjà attribué au package ' . $package->getNomUrl(0, 1, 1, 'default');
            } else {
                $errors = $equipment->updateField('id_package', (int) $this->id);

                if (!count($errors)) {
                    $warnings = $this->setEquipmentPlace($equipment);
                }
            }
        }

        return $errors;
    }

    public function removeEquipment($id_equipment, $id_entrepot)
    {
        
    }

    public function addProduct($id_product, $qty, $id_entrepot = 0, &$warnings = array())
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

        $pp = BimpCache::findBimpObjectInstance('bimpequipment', 'BE_PackageProduct', array(
                    'id_package' => (int) $this->id,
                    'id_product' => (int) $id_product
        ));

        if (!BimpObject::objectLoaded($pp)) {
            $pp = BimpObject::getInstance('bimpequipment', 'BE_PackageProduct');
            $errors = $pp->validateArray(array(
                'id_package' => (int) $this->id,
                'id_product' => (int) $id_product,
                'qty'        => (int) $qty
            ));

            if (!count($errors)) {
                $errors = $pp->create($warnings, true);
            }
        } else {
            $errors = $pp->updateField('qty', (int) $pp->getData('qty') + (int) $qty);
        }

        if (!count($errors)) {
            $stock_errors = $this->onProductIn($id_product, $qty, $id_entrepot);
            if (count($stock_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks');
            }
        }

        return $errors;
    }

    public function removePackageProduct($id_packageProduct, $id_entrepot_dest = 0, &$warnings = array())
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
            $stock_errors = $this->onProductOut($id_product, $qty, $id_entrepot_dest);

            if (count($stock_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks');
            }
        }

        return $errors;
    }

    public function saveProductQty($id_packageProduct, $new_qty, $id_entrepot = 0, &$warnings = array())
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
            $stock_errors = $this->onProductIn((int) $pp->getData('id_product'), $diff, $id_entrepot);

            if (count($stock_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la mise à jour des stocks');
            }
        } else {
            $stock_errors = $this->onProductOut((int) $pp->getData('id_product'), abs($diff), $id_entrepot);

            if (count($stock_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la mise à jour des stocks');
            }
        }

        return $errors;
    }

    public function onNewPlace()
    {
        if ($this->isLoaded()) {
            global $user;
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

            $prev_place_element = '';
            $prev_place_id_element = null;

            $new_place_element = '';
            $new_place_id_element = null;

            $new_place = BimpCache::getBimpObjectInstance($this->module, 'BE_PackagePlace', (int) $items[0]['id']);

            if (BimpObject::objectLoaded($new_place)) {
                $this->current_place = $new_place;

                // Maj de l'emplacement des équipements: 
                foreach ($equipments as $equipment) {
                    $this->setEquipmentPlace($equipment);
                }

                // Maj des stocks des produits non sérialisés: 

                if (!empty($packageProducts)) {
                    $codemove = $new_place->getData('code_mvt');
                    if (is_null($codemove) || !$codemove) {
                        $codemove = 'PACKAGE' . (int) $this->id . '_PLACE' . (int) $new_place->id;
                    }

                    $new_place_infos = $new_place->getData('infos');
                    $label = ($new_place_infos ? $new_place_infos . ' - ' : '') . 'Package #' . $this->id . ($ref ? ' - ' . $ref : '');

                    switch ((int) $new_place->getData('type')) {
                        case BE_Place::BE_PLACE_CLIENT:
                            $new_place_element = 'societe';
                            $new_place_id_element = (int) $new_place->getData('id_client');
                            break;

                        case BE_Place::BE_PLACE_ENTREPOT:
                        case BE_Place::BE_PLACE_PRESENTATION:
                        case BE_Place::BE_PLACE_VOL:
                        case BE_Place::BE_PLACE_PRET:
                        case BE_Place::BE_PLACE_SAV:
                            $new_place_element = 'entrepot';
                            $new_place_id_element = (int) $new_place->getData('id_entrepot');
                            break;

                        case BE_Place::BE_PLACE_USER:
                            $new_place_element = 'user';
                            $new_place_id_element = (int) $new_place->getData('id_user');
                            break;
                    }

                    if (isset($items[1])) {
                        $prev_place = BimpCache::getBimpObjectInstance($this->module, 'BE_PackagePlace', $items[1]['id']);
                        switch ((int) $prev_place->getData('type')) {
                            case BE_Place::BE_PLACE_CLIENT:
                                $prev_place_element = 'societe';
                                $prev_place_id_element = (int) $prev_place->getData('id_client');
                                break;

                            case BE_Place::BE_PLACE_ENTREPOT:
                            case BE_Place::BE_PLACE_PRESENTATION:
                            case BE_Place::BE_PLACE_VOL:
                            case BE_Place::BE_PLACE_PRET:
                            case BE_Place::BE_PLACE_SAV:
                                $prev_place_element = 'entrepot';
                                $prev_place_id_element = (int) $prev_place->getData('id_entrepot');
                                if ((int) $prev_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT) {
                                    foreach ($packageProducts as $pp) {
                                        $product = $pp->getChildObject('product');
                                        if (BimpObject::objectLoaded($product)) {
                                            if ($product->dol_object->correct_stock($user, $prev_place_id_element, (int) $pp->getData('qty'), 1, $label, 0, $codemove, $new_place_element, $new_place_id_element) <= 0) {
                                                $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getRef() . ' - ' . $product->getName() . '" (ID: ' . $product->id . ')';
                                                dol_syslog('[ERREUR STOCK] ' . $msg . ' - Nouvel emplacement package #' . $this->id . ' - Produit #' . $product->id . ' - Qté à retirer: ' . (int) $pp->getData('qty') . ' - Entrepôt #' . $prev_place_id_element, LOG_ERR);
                                            }
                                        }
                                    }
                                }
                                break;

                            case BE_Place::BE_PLACE_USER:
                                $prev_place_element = 'user';
                                $prev_place_id_element = (int) $prev_place->getData('id_user');
                                break;
                        }
                    } else {
                        $prev_place_element = $this->getData('origin_element');
                        if (in_array($prev_place_element, array(1, 2))) {
                            $prev_place_element = 'societe';
                        }
                        $prev_place_id_element = $this->getData('origin_id_element');
                        if (!$prev_place_id_element) {
                            $prev_place_id_element = null;
                        }
                    }

                    if ((int) $new_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT) {
                        foreach ($packageProducts as $pp) {
                            $product = $pp->getChildObject('product');
                            if (BimpObject::objectLoaded($product)) {
                                if ($product->dol_object->correct_stock($user, $new_place_id_element, (int) $pp->getData('qty'), 0, $label, 0, $codemove, $prev_place_element, $prev_place_id_element) <= 0) {
                                    $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getRef() . ' - ' . $product->getName() . '" (ID: ' . $product->id . ')';
                                    dol_syslog('[ERREUR STOCK] ' . $msg . ' - Nouvel emplacement package #' . $this->id . ' - Produit #' . $product->id . ' - Qté à ajouter: ' . (int) $pp->getData('qty') . ' - Entrepôt #' . $new_place_id_element, LOG_ERR);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onProductIn($id_product, $qty, $id_entrepot_src = 0)
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

        global $user;

        $place = $this->getCurrentPlace();

        $id_entrepot_dest = 0;

        if (BimpObject::objectLoaded($place)) {
            if (in_array((int) $place->getData('type'), BE_Place::$entrepot_types)) {
                $id_entrepot_dest = (int) $place->getData('id_entrepot');
                if (!$id_entrepot_dest) {
                    $errors[] = 'ID de l\'entrepôt absent pour l\'emplacement actuel du package';
                    return $errors;
                }
            }
        }

        if ((int) $id_entrepot_src === (int) $id_entrepot_dest) {
            return array();
        }

        $code_move = 'PACKAGE' . $this->id . '_ADD';

        if ($id_entrepot_src) {
            BimpTools::resetDolObjectErrors($product->dol_object);
            if ($product->dol_object->correct_stock($user, (int) $id_entrepot_src, $qty, 1, 'Ajout au package #' . $this->id, 0, $code_move) <= 0) {
                $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getRef() . ' - ' . $product->getName() . '" (ID: ' . $product->id . ')';
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), $msg);
                dol_syslog('[ERREUR STOCK] ' . $msg . ' - Ajout au Package #' . $this->id . ' - Produit #' . $product->id . ' - Qté à retirer: ' . (int) $qty . ' - Entrepôt #' . $id_entrepot_src, LOG_ERR);
            }
        }

        if ($id_entrepot_dest) {
            BimpTools::resetDolObjectErrors($product->dol_object);
            if ($product->dol_object->correct_stock($user, (int) $id_entrepot_dest, $qty, 0, 'Ajout au package #' . $this->id, 0, $code_move) <= 0) {
                $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getRef() . ' - ' . $product->getName() . '" (ID: ' . $product->id . ')';
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), $msg);
                dol_syslog('[ERREUR STOCK] ' . $msg . ' - Ajout au Package #' . $this->id . ' - Produit #' . $product->id . ' - Qté à ajouter: ' . (int) $qty . ' - Entrepôt #' . $id_entrepot_dest, LOG_ERR);
            }
        }

        return $errors;
    }

    public function onProductOut($id_product, $qty, $id_entrepot_dest = 0)
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

        global $user;

        $place = $this->getCurrentPlace();

        $id_entrepot_src = 0;

        if (BimpObject::objectLoaded($place)) {
            if (in_array((int) $place->getData('type'), BE_Place::$entrepot_types)) {
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

        $code_move = 'PACKAGE' . $this->id . '_REMOVE';

        if ($id_entrepot_src) {
            BimpTools::resetDolObjectErrors($product->dol_object);
            if ($product->dol_object->correct_stock($user, (int) $id_entrepot_src, $qty, 1, 'Retrait du package #' . $this->id, 0, $code_move) <= 0) {
                $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getRef() . ' - ' . $product->getName() . '" (ID: ' . $product->id . ')';
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), $msg);
                dol_syslog('[ERREUR STOCK] ' . $msg . ' - Retrait du Package #' . $this->id . ' - Produit #' . $product->id . ' - Qté à retirer: ' . (int) $qty . ' - Entrepôt #' . $id_entrepot_src, LOG_ERR);
            }
        }

        if ($id_entrepot_dest) {
            BimpTools::resetDolObjectErrors($product->dol_object);
            if ($product->dol_object->correct_stock($user, (int) $id_entrepot_dest, $qty, 0, 'Retrait du package #' . $this->id, 0, $code_move) <= 0) {
                $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getRef() . ' - ' . $product->getName() . '" (ID: ' . $product->id . ')';
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), $msg);
                dol_syslog('[ERREUR STOCK] ' . $msg . ' - Retrait du Package #' . $this->id . ' - Produit #' . $product->id . ' - Qté à ajouter: ' . (int) $qty . ' - Entrepôt #' . $id_entrepot_dest, LOG_ERR);
            }
        }

        return $errors;
    }

    public function setEquipmentPlace(Equipment $equipment)
    {
        $errors = array();

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

    // Rendus HTML: 

    public function renderEquipmentsQuickForm()
    {
        $html = '';

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
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
            $errors = $this->addEquipment($id_equipment, $warnings);
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
        } else {
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

                unset($_POST['keep_place']);
                unset($_POST['equipments']);
            }

            $nDone = 0;
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
                $success = $nDone . ' équipement retirés avec succès';
            } elseif ($nDone > 0) {
                $success = 'Equipement retiré avec succès';
            }

            if ($set_place) {
                $_POST = $post_temp;
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpequipment\', \'Eequipment\', 0)'
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

        if ($qty <= 0) {
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
}

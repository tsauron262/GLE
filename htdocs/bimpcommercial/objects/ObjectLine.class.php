<?php

class ObjectLine extends BimpObject
{

    public static $parent_comm_type = '';
    public static $dol_line_table = '';
    public static $dol_line_primary = 'rowid';
    public static $dol_line_parent_field = '';
    public static $check_on_update = false;
    public $equipment_required = false;
    public static $equipment_required_in_entrepot = true;
    public static $product_search_name = 'tosell';
    public static $tva_free = false;

    const LINE_PRODUCT = 1;
    const LINE_TEXT = 2;
    const LINE_FREE = 3;
    const LINE_SUB_TOTAL = 4;

    public $desc = null;
    public $id_product = null;
    public $product_type = 0;
    public $qty = 1;
    public $pu_ht = null;
    public $tva_tx = null;
    public $pa_ht = null;
    public $id_fourn_price = null;
    public $remise = null;
    public $date_from = null;
    public $date_to = null;
    public $nbCalcremise = 0;
    public $id_remise_except = null;
    public static $product_line_data = array(
        'id_product'     => array('label' => 'Produit / Service', 'type' => 'int', 'required' => 1),
        'id_fourn_price' => array('label' => 'Prix d\'achat fournisseur', 'type' => 'int', 'default' => null),
        'desc'           => array('label' => 'Description', 'type' => 'html', 'required' => 0, 'default' => null),
        'qty'            => array('label' => 'Quantité', 'type' => 'float', 'required' => 1, 'default' => 1),
        'pu_ht'          => array('label' => 'PU HT', 'type' => 'float', 'required' => 0, 'default' => null),
        'tva_tx'         => array('label' => 'Taux TVA', 'type' => 'float', 'required' => 0, 'default' => null),
        'pa_ht'          => array('label' => 'Prix d\'achat HT', 'type' => 'float', 'required' => 0, 'default' => null),
        'remise'         => array('label' => 'Remise', 'type' => 'float', 'required' => 0, 'default' => 0),
        'date_from'      => array('label' => 'Date début', 'type' => 'date', 'required' => 0, 'default' => null),
        'date_to'        => array('label' => 'Date fin', 'type' => 'date', 'required' => 0, 'default' => null),
        'product_type'   => array('label' => 'Service', 'type' => 'bool', 'required' => 0, 'default' => 0)
    );
    public static $text_line_data = array(
        'desc'           => array('label' => 'Description', 'type' => 'html', 'required' => 0, 'default' => ''),
        'id_parent_line' => array('label' => 'Ligne parente', 'type' => 'int', 'required' => 0, 'default' => null)
    );
    protected $product = null;
    protected $post_id_product = null;
    protected $post_equipment = null;
    public $no_equipment_post = false;
    public $remises = null;
    public $bimp_line_only = false;
    protected $remises_total_infos = null;
    public $no_html = false;

    // Gestion des droits utilisateurs:

    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'pu_ht':
                return $this->canEditPrixVente();

            case 'pa_ht':
                return $this->canEditPrixAchat();
        }

        return parent::canEditField($field_name);
    }

    public function canEditPrixAchat()
    {
        global $user;
        if (isset($user->rights->bimpcommercial->priceAchat) && (int) $user->rights->bimpcommercial->priceAchat) {
            return 1;
        }
        $product = $this->getProduct();
        if (BimpObject::objectLoaded($product)) {
            if ($product->getData("price") == 1 || $product->getData("price") == 0 || !$product->hasFixePa())
                return 1;
        }
        return 0;
    }

    public function canEditPrixVente()
    {
        global $user;

        if ((float) $this->qty < 0) {
            return 1;
        }

        if (isset($user->rights->bimpcommercial->priceVente) && (int) $user->rights->bimpcommercial->priceVente == 1) {
            return 1;
        }

        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            if ($product->getData("price") == 1 || $product->getData("price") == 0 || !$product->hasFixePu())
                return 1;
        }

        return 0;
    }

    public function canEditRemisePa()
    {
        return 0;
    }

    // Getters booléens:

    public function isCreatable($force_create = false, &$errors = array())
    {
        if ($force_create) {
            return 1;
        }

        return $this->isParentEditable();
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        if ($force_edit) {
            return 1;
        } elseif ((int) $this->id_remise_except) {
            return 0;
        }

        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return 0;
        }

        if ($this->isLineText()) {
            return 1;
        }

        return (int) ((int) $parent->isEditable());
    }

    public function isParentDraft()
    {
        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return 0;
        }

        if ($parent->field_exists('fk_statut') && (int) $parent->getData('fk_statut') === 0) {
            return 1;
        } elseif (isset($parent->dol_object->statut) && (int) $parent->dol_object->statut === 0) {
            return 1;
        }

        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($this->isLoaded()) {
            if (!$force_delete && !(int) $this->getData('deletable')) {
                return 0;
            }

            if ($force_delete) {
                return 1;
            }

            $parent = $this->getParentInstance();
            if (!BimpObject::objectLoaded($parent)) {
                return 0;
            }

            if ($parent->field_exists('fk_statut') && $parent->getData('fk_statut') === 0) {
                return 1;
            } elseif (isset($parent->dol_object->statut) && (int) $parent->dol_object->statut === 0) {
                return 1;
            }
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (!(int) $this->isEditable($force_edit)) {
            return 0;
        }

        if ((int) $this->getData('type') === self::LINE_SUB_TOTAL) {
            if (in_array($field, array('desc', 'hide_in_pdf'))) {
                if (!$this->isParentDraft() || !(int) $this->getData('editable')) {
                    return 0;
                }

                return 1;
            }
            return 0;
        } else {
            if (!in_array($field, array('remise_crt', 'force_qty_1', 'hide_product_label', 'date_from', 'date_to', 'desc', 'ref_supplier', 'hide_in_pdf'))) {
                if (!$force_edit) {
                    $parent = $this->getParentInstance();
                    if (!BimpObject::objectLoaded($parent)) {
                        return 0;
                    }

                    if (!(int) $this->getData('editable') || !$parent->areLinesEditable()) {
                        return 0;
                    }
                }

                switch ($field) {
                    case 'remisable':
                        $product = $this->getProduct();
                        if (BimpObject::objectLoaded($product)) {
                            if (!(int) $product->getData('remisable')) {
                                return 0;
                            }
                        }

//                        if ((float) $this->getTotalHT() < 0) {
//                            return 0;
//                        }
                        return 1;
                }
            }
        }

        return (int) parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('attributeEquipment')) && !$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
            return 0;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function isProductRemisable()
    {
        $product = $this->getProduct();
        if (BimpObject::objectLoaded($product)) {
            if (!(int) $product->getData('remisable')) {
                return 0;
            }
        }

        return 1;
    }

    public function isRemisable()
    {
        return $this->isProductRemisable() && (int) $this->getData('remisable');
    }

    public function isParentEditable()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent) and $parent->isEditable() && $parent->areLinesEditable()) {
            if ($parent->field_exists('fk_statut') && $parent->getData('fk_statut') === 0) {
                return 1;
            } elseif (isset($parent->dol_object->statut) && (int) $parent->dol_object->statut === 0) {
                return 1;
            }
        }

        return 0;
    }

    public function isRemiseEditable()
    {
        return (int) $this->isParentEditable();
    }

    public function isLineProduct()
    {
        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            return 1;
        }

        return 0;
    }

    public function isLineText()
    {
        return (int) ((int) $this->getData('type') === self::LINE_TEXT);
    }

    public function isNotTypeText()
    {
        if ((int) $this->getData('type') === self::LINE_TEXT) {
            return 0;
        }

        return 1;
    }

    public function isArticleLine()
    {
        return (int) in_array((int) $this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE));
    }

    public function isNotArticleLine()
    {
        return (int) in_array((int) $this->getData('type'), array(self::LINE_TEXT, self::LINE_SUB_TOTAL));
    }

    public function isProductEditable()
    {
        if ($this->isLoaded()) {
            return 0;
        }

        return 1;
    }

    public function isProductSerialisable()
    {
        $product = $this->getProduct();
        if (BimpObject::objectLoaded($product)) {
            return (int) $product->isSerialisable();
        }

        return 0;
    }

    public function isEquipmentAvailable(Equipment $equipment = null)
    {
        $errors = array();

        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            $errors[] = 'Objet parent absent';
        } else {
            $id_entrepot = 0;

            if (static::$equipment_required_in_entrepot) {
                if ($parent->field_exists('entrepot')) {
                    $id_entrepot = (int) $parent->getData('entrepot');
                    if (!$id_entrepot) {
                        $errors[] = 'Aucun entrepôt défini pour ' . $parent->getLabel('the') . ' ' . $parent->getNomUrl(0, 1, 1, 'full');
                    }
                }
            }

            if (!count($errors)) {
                $allowed = array();

                if ($this->getData('linked_object_name') === 'commande_line') {
                    $id_commande_line = (int) $this->getData('linked_id_object');
                    if ($id_commande_line) {
                        $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                    'type'                    => BR_Reservation::BR_RESERVATION_COMMANDE,
                                    'id_commande_client_line' => (int) $id_commande_line,
                                    'id_equipment'            => (int) $equipment->id
                                        ), true);

                        if (BimpObject::objectLoaded($reservation)) {
                            $allowed['id_reservation'] = (int) $reservation->id;
                        }
                    }
                } elseif ($this->getData('linked_object_name') === 'commande_fourn_line') {
                    $allowed['id_commande_fourn_line'] = (int) $this->getData('linked_id_object');
                }

                $equipment->isAvailable($id_entrepot, $errors, $allowed);
            }
        }

        return $errors;
    }

    public function isLimited()
    {
        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            if ((int) $product->getData('duree') > 0) {
                return 1;
            }
        }

        if (!$this->isLoaded()) {
            return 0;
        }

        if (empty($this->date_from) && empty($this->date_to)) {
            return 0;
        }

        return 1;
    }

    public function isChild($instance)
    {
        if (is_a($instance, 'ObjectLineRemise')) {
            $obj_type = $instance->getParentObjectType();
            if (!$obj_type) {
                if (isset($_POST['remises_sub_object_idx_object_type'])) {
                    $instance->set('object_type', $_POST['remises_sub_object_idx_object_type']);
                }
            }
        }

        return parent::isChild($instance);
    }

    public function isValid(&$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the');
            return 0;
        }

        if ((int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();

            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Produit absent';
                return 0;
            }

            if (is_object($product) && $product->id > 0) {
                if (!$product->isVendable($errors))
                    return 0;
            }
        }

        return 1;
    }

    public function hasEquipmentToAttribute()
    {
        if ($this->isLoaded()) {
            $product = $this->getProduct();

            if (is_a($product, 'Bimp_Product') && $product->isSerialisable()) {
                $lines = $this->getEquipmentLines();
                if (count($lines)) {
                    foreach ($lines as $line) {
                        if (!(int) $line->getData('id_equipment')) {
                            return 1;
                        }
                    }
                }
            }
        }

        return 0;
    }

    public function showMarginsInForms()
    {
        return 0;
    }

    public function isService()
    {
        if ($this->getData('type') == static::LINE_PRODUCT) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                if ((int) $product->getData('fk_product_type') == 1)
                    return 1;
            }
        } else {
            $line = $this->getChildObject('line');
            $type = $line->product_type ? $line->product_type : $line->fk_product_type;

            if ($type == 1) {
                return 1;
            }
        }
        return 0;
    }

    // Getters array: 

    public function getProductFournisseursPricesArray($include_empty = true, $empty_label = '')
    {
        $id_product = (int) $this->getIdProductFromPost();
        if ($id_product) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            return Bimp_Product::getFournisseursPriceArray($id_product, 0, 0, $include_empty, $empty_label);
        }

        if ($include_empty) {
            return array(
                0 => $empty_label
            );
        }

        return array();
    }

    public function getProdFournisseursArray()
    {
        $id_product = (int) $this->getIdProductFromPost();
        if ($id_product) {
            return BimpCache::getProductFournisseursArray($id_product, true);
        }

        return array();
    }

    public function getProductPricesValuesArray()
    {
        $this->getIdProductFromPost();

        if (!(int) $this->id_product) {
            return array();
        }

        $product = $this->getProduct();

        if (!BimpObject::objectLoaded($product) || !$product->hasFixePu()) {
            return array();
        }

        $values = array(
            0 => ''
        );

        if (BimpObject::objectLoaded($this->post_equipment)) {
            if ((float) $this->post_equipment->getData('prix_vente_except') > 0) {
                $pu_ht = BimpTools::calculatePriceTaxEx((float) $this->post_equipment->getData('prix_vente_except'), (float) $product->getData('tva_tx'));
                $values['' . $pu_ht] = 'Prix de vente exceptionnel équipement: ' . BimpTools::displayMoneyValue($pu_ht, 'EUR');
            }
        }

        $values['' . $product->getData('price')] = 'Prix de vente produit: ' . BimpTools::displayMoneyValue((float) $product->getData('price'), 'EUR');
        return $values;
    }

    public function getTypesArray()
    {
        $types = array(
            self::LINE_PRODUCT   => 'Produit / Service',
            self::LINE_TEXT      => 'Texte libre',
            self::LINE_SUB_TOTAL => 'Sous-total'
        );

        if ((int) BimpCore::getConf('use_free_objectline', null, 'bimpcommercial')) {
            $types[self::LINE_FREE] = 'Ligne libre';
        }

        return $types;
    }

    public function getProductAvailableRemisesArrieresArray()
    {
        $remises = array();

        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            $product_remises = $product->getChildrenObjects('remises_arrieres', array(
                'active' => 1
            ));

            if (!empty($product_remises)) {
                $cur_remises = $this->getRemisesArrieres();

                foreach ($product_remises as $prod_remise) {
                    if ($prod_remise->getData('type') !== 'oth') {
                        if (!empty($cur_remises)) {
                            foreach ($cur_remises as $cur_remise) {
                                if ($cur_remise->getData('type') == $prod_remise->getData('type')) {
                                    continue 2;
                                }
                            }
                        }
                    }

                    $remises[$prod_remise->id] = $prod_remise->getData('nom') . ' (' . $prod_remise->displayData('value', 'default', false) . ')';
                }
            }
        }

        return $remises;
    }

    // Getters params:

    public function getParentCommType()
    {
        return static::$parent_comm_type;
    }

    public function getLineDataDefs()
    {
        switch ((int) $this->getData('type')) {
            case self::LINE_PRODUCT:
            case self::LINE_FREE:
                return self::$product_line_data;

            case self::LINE_TEXT:
                return self::$text_line_data;
        }

        return array();
    }

    public function getFournisseurPriceCreateForm()
    {
        if ($this->canEditPrixAchat()) {
            return 'default';
        }

        return '';
    }

    public function getLineEquipmentInstance()
    {
        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $this->module . '/objects/' . $this->object_name . 'Equipment.yml')) {
            $module = $this->module;
            $object_name = $this->object_name . 'Equipment';
        } else {
            $module = 'bimpcommercial';
            $object_name = 'ObjectLineEquipment';
        }

        return BimpObject::getInstance($module, $object_name);
    }

    public function getListExtraBtn()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            if ((int) $this->id_product) {
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if ($product->isSerialisable() && $this->equipment_required) {
                        if ($this->isActionAllowed('attributeEquipment') && $this->hasEquipmentToAttribute()) {
                            $data = array();
                            if (BimpObject::objectLoaded($this->post_equipment)) {
                                $data['id_equipment'] = (int) $this->post_equipment->id;
                            }
                            $onclick = $this->getJsActionOnclick('attributeEquipment', $data, array(
                                'form_name' => 'equipment'
                            ));
                            $buttons[] = array(
                                'label'   => 'Attribuer un équipement',
                                'icon'    => 'arrow-circle-down',
                                'onclick' => $onclick
                            );
                        }

                        $instance = $this->getLineEquipmentInstance();

                        $buttons[] = array(
                            'label'   => 'Détails équipements',
                            'icon'    => 'bars',
                            'onclick' => 'loadModalList(\'' . $instance->module . '\', \'' . $instance->object_name . '\', \'default\', ' . $this->id . ', $(this), \'Equipements assignés à la ligne n°' . $this->getData('position') . '\', {}, ' . htmlentities(json_encode(array('object_type' => static::$parent_comm_type))) . ')'
                        );
                    }
                }
            }
            if (in_array((int) $this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE))) {
                $buttons[] = array(
                    'label'   => 'Remises ligne + remises arrières',
                    'icon'    => 'fas_percent',
                    'onclick' => $this->getJsLoadModalCustomContent('renderRemisesLists', 'Remises PV et remises arrières')
                );
            }

            if ($this->isParentEditable() && in_array((int) $this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE)) && !(int) $this->getData('id_parent_line')) {
                $line_instance = BimpObject::getInstance($this->module, $this->object_name);
                $onclick = $line_instance->getJsLoadModalForm((is_a($this, 'FournObjectLine')) ? 'fournline' : 'default', 'Ajout d\\\'une sous-ligne à la ligne n°' . $this->getData('position'), array(
                    'objects' => array(
                        'remises' => $this->getClientDefaultRemiseFormValues()
                    ),
                    'fields'  => array(
                        'id_obj'         => (int) $this->getData('id_obj'),
                        'id_parent_line' => (int) $this->id,
                        'type'           => self::LINE_TEXT
                    )
                ));
                $buttons[] = array(
                    'label'   => 'Ajout d\'une sous-ligne',
                    'icon'    => 'fas_plus-circle',
                    'onclick' => $onclick
                );
            }
        }

        return $buttons;
    }

    public function getDescSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {

        $alias = static::$dol_line_table;

        if (!isset($joins[$alias])) {
            $joins[$alias] = array(
                'alias' => $alias,
                'table' => $alias,
                'on'    => $alias . '.rowid = ' . $main_alias . '.id_line'
            );
        }

        if (!isset($joins['prod'])) {
            $joins['prod'] = array(
                'alias' => 'prod',
                'table' => 'product',
                'on'    => $alias . '.fk_product = prod.rowid'
            );
        }

        $where = 'prod.ref LIKE \'%' . (string) $value . '%\' OR prod.label LIKE \'%' . (string) $value . '%\' OR prod.barcode = \'' . (string) $value . '\'';

        if (preg_match('/^\d+$/', (string) $value)) {
            $where .= ' OR prod.rowid = ' . $value;
        }

        $filters['or_product'] = array(
            'or' => array(
                $alias . '.description' => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                $alias . '.label'       => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                $alias . '.fk_product'  => array(
                    'in' => 'SELECT prod.rowid FROM ' . MAIN_DB_PREFIX . 'product prod WHERE ' . $where
                )
            )
        );
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'categ1':
            case 'categ2':
            case 'categ3':
                $line_alias = $main_alias . '___dol_line';
                $joins[$line_alias] = array(
                    'alias' => $line_alias,
                    'table' => static::$dol_line_table,
                    'on'    => $line_alias . '.rowid = ' . $main_alias . '.id_line'
                );

                $alias = $main_alias . '___cat_prod_' . $field_name;
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'categorie_product',
                    'on'    => $alias . '.fk_product = ' . $line_alias . '.fk_product'
                );
                $filters[$alias . '.fk_categorie'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                return;

            case 'id_commercial':
                $ids = array();
                $empty = false;

                foreach ($values as $value) {
                    if ((int) $value) {
                        $ids[] = (int) $value;
                    } else {
                        $empty = true;
                    }
                }

                $elem_alias = $main_alias . '___elemcont';
                $joins[$elem_alias] = array(
                    'table' => 'element_contact',
                    'on'    => $elem_alias . '.element_id = ' . $main_alias . '.id_obj',
                    'alias' => $elem_alias
                );

                $type_alias = $main_alias . '___typecont';
                $joins[$type_alias] = array(
                    'table' => 'c_type_contact',
                    'on'    => $elem_alias . '.fk_c_type_contact = ' . $type_alias . '.rowid',
                    'alias' => $type_alias
                );

                $sql = '';

                if (!empty($ids)) {
                    $sql = '(' . $type_alias . '.element = \'' . static::$parent_comm_type . '\' AND ' . $type_alias . '.source = \'internal\'';
                    $sql .= ' AND ' . $type_alias . '.code = \'SALESREPFOLL\' AND ' . $elem_alias . '.fk_socpeople ' . ($excluded ? 'NOT ' : '') . 'IN (' . implode(',', $ids) . '))';

                    if (!$empty && $excluded) {
                        $sql .= ' OR (SELECT COUNT(ec2.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc2 ON tc2.rowid = ec2.fk_c_type_contact';
                        $sql .= ' WHERE tc2.element = \'' . static::$parent_comm_type . '\'';
                        $sql .= ' AND tc2.source = \'internal\'';
                        $sql .= ' AND tc2.code = \'SALESREPFOLL\'';
                        $sql .= ' AND ec2.element_id = ' . $main_alias . '.id_obj) = 0';
                    }
                }

                if ($empty) {
                    $sql .= ($sql ? ($excluded ? ' AND ' : ' OR ') : '');
                    $sql .= '(SELECT COUNT(ec2.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc2 ON tc2.rowid = ec2.fk_c_type_contact';
                    $sql .= ' WHERE tc2.element = \'' . static::$parent_comm_type . '\'';
                    $sql .= ' AND tc2.source = \'internal\'';
                    $sql .= ' AND tc2.code = \'SALESREPFOLL\'';
                    $sql .= ' AND ec2.element_id = ' . $main_alias . '.id_obj) ' . ($excluded ? '>' : '=') . ' 0';
                }

                if ($sql) {
                    $filters[$main_alias . '___commercial_custom'] = array(
                        'custom' => $sql
                    );
                }
                break;

            case 'ref-prod':
                $line_alias = $main_alias . '___dol_line';
                $joins[$line_alias] = array(
                    'alias' => $line_alias,
                    'table' => static::$dol_line_table,
                    'on'    => $line_alias . '.rowid = ' . $main_alias . '.id_line'
                );

                $prod_alias = $main_alias . '___product';
                $joins[$prod_alias] = array(
                    'alias' => $prod_alias,
                    'table' => 'product',
                    'on'    => $prod_alias . '.rowid = ' . $line_alias . '.fk_product'
                );

                $ref_filters = array();
                foreach ($values as $value) {

                    $filter = BC_Filter::getValuePartSqlFilter($value['value'], $value['part_type'], $excluded);
                    if (!empty($filter)) {
                        $ref_filters[] = $filter;
                    }
                }

                if (!empty($ref_filters)) {
                    $filters[$prod_alias . '.ref'] = array(
                        ($excluded ? 'and' : 'or_field') => $ref_filters
                    );
                }
                break;

            case 'fk_product_type':
                $line_alias = 'dol_line';
                $joins[$line_alias] = array(
                    'alias' => $line_alias,
                    'table' => static::$dol_line_table,
                    'on'    => $line_alias . '.rowid = ' . $main_alias . '.id_line'
                );

                $prod_alias = $main_alias . '___product';
                $joins[$prod_alias] = array(
                    'alias' => $prod_alias,
                    'table' => 'product',
                    'on'    => $prod_alias . '.rowid = ' . $line_alias . '.fk_product'
                );

                $filters[$prod_alias . '.fk_product_type'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );

                break;

            case 'categorie':
            case 'collection':
            case 'nature':
            case 'famille':
            case 'gamme':
                $line_alias = 'dol_line';
                $joins[$line_alias] = array(
                    'alias' => $line_alias,
                    'table' => static::$dol_line_table,
                    'on'    => $line_alias . '.rowid = ' . $main_alias . '.id_line'
                );

                $prod_ef_alias = $main_alias . '___product_ef';
                $joins[$prod_ef_alias] = array(
                    'alias' => $prod_ef_alias,
                    'table' => 'product_extrafields',
                    'on'    => $prod_ef_alias . '.fk_object = ' . $line_alias . '.fk_product'
                );

                $filters[$prod_ef_alias . '.' . $field_name] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;

            case 'acomptes':
                if (count($values) === 1) {
                    $line_alias = $main_alias . '___dol_line';
                    $joins[$line_alias] = array(
                        'alias' => $line_alias,
                        'table' => static::$dol_line_table,
                        'on'    => $line_alias . '.rowid = ' . $main_alias . '.id_line'
                    );

                    if ((int) $values[0]) {
                        $filters[$line_alias . '.fk_remise_except'] = array(
                            'operator' => '>',
                            'value'    => 0
                        );
                    } else {
                        $filters[$line_alias . '.fk_remise_except'] = array(
                            'or_field' => array(
                                0,
                                'IS_NULL',
                            )
                        );
                    }
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function getCustomFilterValueLabel($field_name, $value)
    {
        switch ($field_name) {
            case 'id_product':
                $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $value);
                if (BimpObject::ObjectLoaded($product)) {
                    return $product->getRef();
                }
                break;

            case 'id_commercial':
                if ((int) $value) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $value);
                    if (BimpObject::ObjectLoaded($user)) {
                        global $langs;
                        return $user->dol_object->getFullName($langs);
                    }
                } else {
                    return 'Aucun';
                }
                break;

            default:
                return $value;
        }

        return parent::getCustomFilterValueLabel($field_name, $value);
    }

    public function getRowStyle()
    {
        if ((int) $this->getData('type') === self::LINE_SUB_TOTAL) {
            return 'border-top: 2px solid #888;border-bottom: 1px solid #888;font-weight: bold;';
        }

        return '';
    }

    // Getters valeurs:

    public function getFullQty()
    {
        return (float) $this->qty;
    }

    public function getUnitPriceTTC()
    {
        if (!is_null($this->pu_ht)) {
            $pu_ht = $this->pu_ht;

            if (!is_null($this->remise) && $this->remise != 0) {
                $pu_ht -= (float) ($pu_ht * ($this->remise / 100));
            }
            if (is_null($this->tva_tx)) {
                return $pu_ht;
            }
            return BimpTools::calculatePriceTaxIn((float) $pu_ht, (float) $this->tva_tx);
        }

        return 0;
    }

    public function getUnitPriceHTWithRemises()
    {
        $value = $this->pu_ht;

        if (!is_null($this->remise) && (float) $this->remise != 0) {
            $value -= ($value * ((float) $this->remise / 100));
        }

        return $value;
    }

    public function getTotalHT($full_qty = false)
    {
        if ($full_qty) {
            $qty = (float) $this->getFullQty();
        } else {
            $qty = (float) $this->qty;
        }
        return (float) ((float) $this->pu_ht * $qty);
    }

    public function getTotalHTWithRemises($full_qty = false)
    {
        if (!is_null($this->pu_ht)) {
            if ($full_qty) {
                $qty = (float) $this->getFullQty();
            } else {
                $qty = (float) $this->qty;
            }

            $pu_ht = $this->pu_ht;

            if (!is_null($this->remise) && $this->remise != 0) {
                $pu_ht -= (float) ($pu_ht * ($this->remise / 100));
            }

            return (float) ($pu_ht * $qty);
        }

        return 0;
    }

    public function getTotalTTC($full_qty = false)
    {
        $pu_ttc = (float) $this->getUnitPriceTTC();

        if ($full_qty) {
            $qty = (float) $this->getFullQty();
        } else {
            $qty = (float) $this->qty;
        }

        return ($pu_ttc * $qty);
    }

    public function getTotalTtcWithoutRemises($full_qty = false)
    {
        if ($full_qty) {
            $qty = (float) $this->getFullQty();
        } else {
            $qty = (float) $this->qty;
        }
        return round(BimpTools::calculatePriceTaxIn((float) $this->pu_ht, (float) $this->tva_tx) * $qty, 8);
    }

    public function getTotalPA($full_qty = false)
    {
        if ($full_qty) {
            $qty = (float) $this->getFullQty();
        } else {
            $qty = (float) $this->qty;
        }
        return (float) $this->pa_ht * $qty;
    }

    public function getMargin($full_qty = false)
    {
        if ($full_qty) {
            $qty = (float) $this->getFullQty();
        } else {
            $qty = (float) $this->qty;
        }

        $pu = (float) $this->pu_ht;
        if (!is_null($this->remise) && $this->remise != 0) {
            $pu -= ($pu * ((float) $this->remise / 100));
        }

        $margin = ($pu * $qty) - ((float) $this->pa_ht * $qty);
        return $margin;
    }

    public function getMargePrevue($full_qty = false)
    {
        $margin = (float) $this->getMargin($full_qty);

        $done = false;
        if ($this->object_name === 'Bimp_FactureLine') {
            $facture = $this->getParentInstance();

            if (BimpObject::objectLoaded($facture)) {
                if ((int) $facture->getData('fk_statut')) {
                    $done = true;
                    $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
                                'id_facture_line' => (int) $this->id
                    ));

                    foreach ($revals as $reval) {
                        if (in_array((int) $reval->getData('status'), array(0, 1))) {
                            $reval_amount = $reval->getTotal();
                            $margin += $reval_amount;
                        }
                    }
                }
            }
        }

        if (!$done) {
            $margin += (float) $this->getTotalRemisesArrieres($full_qty);
        }

        return $margin;
    }

    public function getMarginRate()
    {
        $pu = (float) $this->pu_ht;
        if (!is_null($this->remise) && (float) $this->remise != 0) {
            $pu -= ($pu * ((float) $this->remise / 100));
        }

        $margin = $pu - (float) $this->pa_ht;

        if (!$margin) {
            return 0;
        }

        if ((int) BimpCore::getConf('use_tx_marque', 1, 'bimpcommercial')) {
            if (!$pu) {
                return 0;
            }

            return ($margin / $pu) * 100;
        } else {
            if (!(float) $this->pa_ht) {
                return 0;
            }

            return ($margin / (float) $this->pa_ht) * 100;
        }
    }

    public function getMarginLabel()
    {
        if ((int) BimpCore::getConf('use_tx_marque', 1, 'bimpcommercial')) {
            return 'Marge (Tx marque)';
        }
        return 'Marge (Tx marge)';
    }

    public function getIdFournPriceFromPost()
    {
        $id = BimpTools::getPostFieldValue('id_fourn_price', null);

        if (!is_null($id)) {
            $this->id_fourn_price = (int) $id;
        }

        return (int) $this->id_fourn_price;
    }

    public function getIdProductFromPost()
    {
        if (is_null($this->post_id_product) || !(int) $this->post_id_product) {
            $id_product = 0;
            $id_equipment = null;

            if (!$this->no_equipment_post) {
                $id_equipment = BimpTools::getPostFieldValue('id_equipment', null);
            }

            if (is_null($id_equipment) && BimpObject::objectLoaded($this->post_equipment)) {
                $id_equipment = (int) $this->post_equipment->id;
            }

            if (!is_null($id_equipment)) {
                $id_equipment = (int) $id_equipment;
                if (!BimpObject::objectLoaded($this->post_equipment) || ($this->post_equipment->id !== $id_equipment)) {
                    if (BimpObject::objectLoaded($this->post_equipment)) {
                        unset($this->post_equipment);
                        $this->post_equipment = null;
                    }
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if ($equipment->isLoaded()) {
                        $this->post_equipment = $equipment;
                    }
                }

                if (BimpObject::objectLoaded($this->post_equipment)) {
                    $id_product = (int) $this->post_equipment->getData('id_product');
                }
            }

            if (!$id_product) {
                $id_product = BimpTools::getPostFieldValue('id_product', null);

                if (is_null($id_product)) {
                    $id_product = (int) $this->id_product;
                } else {
                    $id_product = (int) $id_product;
                }
            }

            if ($id_product !== (int) $this->id_product) {
                $this->id_product = $id_product;

                if (!is_null($this->product)) {
                    unset($this->product);
                    $this->product = null;
                }
            }

            $this->post_id_product = $id_product;
        }

        return (int) $this->post_id_product;
    }

    public function getValueByProduct($field)
    {
        if ($field === 'tva_tx') {
            $parent = $this->getParentInstance();

            if (BimpObject::objectLoaded($parent) && !$parent->isTvaActive()) {
                return 0;
            }
        }

        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            $id_product = (int) $product->id;
            switch ($field) {
                case 'pu_ht':
                    if (BimpObject::objectLoaded($this->post_equipment)) {
                        if ((float) $this->post_equipment->getData('prix_vente_except') > 0) {
                            return BimpTools::calculatePriceTaxEx((float) $this->post_equipment->getData('prix_vente_except'), (float) $product->getData('tva_tx'));
                        }
                    }
                    $pu_ht = $this->pu_ht;
//                    if ($this->isLoaded() && $this->field_exists('def_pu_ht')) {
//                        $pu_ht = $this->getData('def_pu_ht');
//                    }
                    if ($id_product && (is_null($pu_ht) || (int) $this->id_product !== $id_product) && $product->hasFixePu()) {
                        return $product->getData('price');
                    }
                    return (float) $pu_ht;

                case 'tva_tx':
                    $tva_tx = $this->tva_tx;
//                    if ($this->isLoaded() && $this->field_exists('def_tva_tx')) {
//                        $tva_tx = $this->getData('def_tva_tx');
//                    }
                    if ($id_product && (is_null($tva_tx) || (int) $this->id_product !== $id_product)) {
                        return (float) $product->getData('tva_tx');
                    }
                    if (is_null($tva_tx)) {
                        $tva_tx = BimpTools::getDefaultTva();
                    }
                    return (float) $tva_tx;

                case 'id_fourn_price':
                    $id_fourn_price = $this->id_fourn_price;
//                    if ($this->isLoaded() && $this->field_exists('def_id_fourn_price')) {
//                        $id_fourn_price = $this->getData('def_id_fourn_price');
//                    }
                    if ($id_product && (is_null($id_fourn_price) || (int) $this->id_product !== $id_product)) {
                        if (!$product->hasFixePa()) {
                            $id_fourn_price = 0;
                        } else {
                            $id_fourn_price = (int) $product->getCurrentFournPriceId();
                        }
                    }

                    return (int) $id_fourn_price;

                case 'pa_ht':
                    $pa_ht = $this->pa_ht;

                    if ($id_product && (is_null($pa_ht) || (int) $this->id_product !== $id_product) && $product->hasFixePa()) {
                        $pa_ht = (float) $product->getCurrentPaHt();
                    }

                    return (float) $pa_ht;

                case 'remisable':
                    if ($id_product && (int) $this->id_product !== $id_product) {
                        if (BimpObject::objectLoaded($product)) {
                            return (int) $product->getData('remisable');
                        }
                    }
                    return (int) $this->isRemisable();

                case 'desc':
                    $desc = $this->desc;
                    if ($id_product && ((is_null($desc) || (int) $this->id_product !== $id_product))) {
                        $desc = (string) $product->dol_object->description;
                        $product_label = (string) $product->getData('label');

                        if (preg_match('/^' . preg_quote($product_label, '/') . '(.*)$/', $desc, $matches)) {
                            $desc = $matches[1];
                        }
                    }

                    if (is_null($desc)) {
                        $desc = '';
                    }

                    return $desc;

//                case 'label':
//                    $label = (string) $this->getData('label');
//                    if ($id_product && ((is_null($label) || !(string) $label || (int) $this->id_product !== $id_product))) {
//                        return (string) $product->getData('label');
//                    }
//                    return $label;
            }
        }

        if (isset($this->{$field}) && !is_null($this->{$field})) {
            return $this->{$field};
        }

        switch ($field) {
            case 'remisable':
                return (int) $this->getData('remisable');

            case 'desc':
                return (string) $this->getData('desc');

//            case 'label':
//                return $this->getData('label');
        }

        return self::$product_line_data[$field]['default'];
    }

    public function getProduct()
    {
        if (!$this->isLoaded() && $this->id_product < 1) {
            $this->getIdProductFromPost();
        }

        if ((int) $this->id_product) {
            if (is_null($this->product)) {
                $this->product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $this->id_product);
                if (!BimpObject::objectLoaded($this->product)) {
                    unset($this->product);
                    $this->product = null;
                }
            }
        }

        return $this->product;
    }

    public function getEquipmentLine($id_equipment)
    {
        if ($this->isLoaded() && static::$parent_comm_type && (int) $id_equipment) {
            return BimpCache::findBimpObjectInstance('bimpcommercial', 'ObjectLineEquipment', array(
                        'id_object_line' => (int) $this->id,
                        'object_type'    => static::$parent_comm_type,
                        'id_equipment'   => (int) $id_equipment
                            ), true);
        }

        return null;
    }

    public function getEquipmentLines()
    {
        if ($this->isLoaded() && static::$parent_comm_type) {
            return BimpCache::getBimpObjectObjects('bimpcommercial', 'ObjectLineEquipment', array(
                        'id_object_line' => (int) $this->id,
                        'object_type'    => static::$parent_comm_type
            ));
        }

        return array();
    }

    public function getCurrentEquipmentsLinesData()
    {
        $data = array();

        if ($this->isLoaded()) {
            $where = 'id_object_line = ' . (int) $this->id . ' AND object_type = \'' . static::$parent_comm_type . '\'';
            $rows = $this->db->getRows('object_line_equipment', $where, null, 'array', array('id', 'id_equipment'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $data[(int) $r['id']] = (int) $r['id_equipment'];
                }
                ksort($data, SORT_NUMERIC);
            }
        }

        return $data;
    }

    public function getRemises()
    {
        if ($this->isLoaded() && static::$parent_comm_type) {
            if (is_null($this->remises)) {
                $this->remises = BimpCache::getBimpObjectObjects('bimpcommercial', 'ObjectLineRemise', array(
                            'id_object_line' => (int) $this->id,
                            'object_type'    => static::$parent_comm_type
                ));
            }
            if (!$this->isRemisable() && count($this->remises)) {
                foreach ($this->remises as $remise) {
                    $del_warnings = array();
                    $remise->delete($del_warnings, true);
                }
                unset($this->remises);
                $this->remises = array();
            }

            return $this->remises;
        }

        return array();
    }

    public function getRemisesArrieres()
    {
        if (!$this->isLoaded() || !static::$parent_comm_type) {
            return array();
        }

        return BimpCache::getBimpObjectObjects('bimpcommercial', 'ObjectLineRemiseArriere', array(
                    'id_object_line' => (int) $this->id,
                    'object_type'    => static::$parent_comm_type
        ));
    }

    public function getRemiseArriere($type = '')
    {
        if ($type) {
            $ra = BimpCache::findBimpObjectInstance('', '', array(
                        'id_object_line' => (int) $this->id,
                        'object_type'    => static::$parent_comm_type,
                        'type'           => $type
                            ), true);

            if (BimpObject::objectLoaded($ra)) {
                return $ra;
            }
        }

        return null;
    }

    public function getRemiseTotalInfos($recalculate = false, $force_qty_mode = -1)
    {
//        $force_qty_mode : -1 aucun forçage / 0 : forcer qtés initiales / 1 : forcer qtés finales (qty + qty_modif). 

        $qty_modif_exists = $this->field_exists('qty_modif');

        if (($qty_modif_exists && $force_qty_mode >= 0) || $recalculate || is_null($this->remises_total_infos)) {
            $this->remises_total_infos = array(
                'line_percent'              => 0,
                'line_amount_ht'            => 0,
                'line_amount_ttc'           => 0,
                'global_percent'            => 0,
                'global_amount_ht'          => 0,
                'global_amount_ttc'         => 0,
                'ext_global_percent'        => 0,
                'ext_global_amount_ht'      => 0,
                'ext_global_amount_ttc'     => 0,
                'total_percent'             => 0,
                'total_amount_ht'           => 0,
                'total_amount_ttc'          => 0,
                'total_ht_without_remises'  => 0,
                'total_ttc_without_remises' => 0,
                'remises_globales'          => array()
            );

            if (!$this->isLoaded()) {
                return $this->remises_total_infos;
            }

            $full_qty = 0;

            if ($force_qty_mode >= 0) {
                $full_qty = $force_qty_mode;
            } elseif ((float) $this->qty == 0 && $qty_modif_exists) {
                $full_qty = 1;
            }

            if ($full_qty) {
                $qty = (float) $this->getFullQty();
            } else {
                $qty = (float) $this->qty;
            }

            $total_ht = $this->getTotalHT($full_qty);
            $total_ttc = $this->getTotalTtcWithoutRemises($full_qty);
            $this->remises_total_infos['total_ht_without_remises'] = $total_ht;
            $this->remises_total_infos['total_ttc_without_remises'] = $total_ttc;

            if (!$this->isRemisable()) {
                return $this->remises_total_infos;
            }

            $remises = $this->getRemises();

            $total_line_amounts = 0;

            foreach ($remises as $remise) {
                if ((int) $remise->getData('id_remise_globale') || (int) $remise->getData('linked_id_remise_globale')) {
                    $id_remise_globale = (int) ((int) $remise->getData('id_remise_globale') ? $remise->getData('id_remise_globale') : $remise->getData('linked_id_remise_globale'));

                    $percent = (float) $remise->getData('percent');
                    $amount_ht = $total_ht * ($percent / 100);
                    $amount_ttc = $total_ttc * ($percent / 100);

                    if ((int) $remise->getData('id_remise_globale')) {
                        $this->remises_total_infos['global_percent'] += $percent;
                        $this->remises_total_infos['global_amount_ht'] += $amount_ht;
                        $this->remises_total_infos['global_amount_ttc'] += $amount_ttc;
                    } else {
                        $this->remises_total_infos['ext_global_percent'] += $percent;
                        $this->remises_total_infos['ext_global_amount_ht'] += $amount_ht;
                        $this->remises_total_infos['ext_global_amount_ttc'] += $amount_ttc;
                    }
                    $this->remises_total_infos['remises_globales'][$id_remise_globale] = array(
                        'percent'    => $percent,
                        'amount_ht'  => $amount_ht,
                        'amount_ttc' => $amount_ttc
                    );
                } else {
                    switch ((int) $remise->getData('type')) {
                        case ObjectLineRemise::OL_REMISE_PERCENT:
                            $this->remises_total_infos['line_percent'] += (float) $remise->getData('percent');
                            break;

                        case ObjectLineRemise::OL_REMISE_AMOUNT:
                            if ((int) $remise->getData('per_unit')) {
                                $total_line_amounts += ((float) $remise->getData('montant') * abs($qty));
                            } else {
                                $total_line_amounts += (float) $remise->getData('montant');
                            }
                            break;
                    }
                }
            }

            if ($total_line_amounts && $total_ttc) {
                $this->remises_total_infos['line_percent'] += (float) (($total_line_amounts / $total_ttc) * 100);
            }

            if ($this->remises_total_infos['line_percent']) {
                $this->remises_total_infos['line_amount_ht'] = (float) ($total_ht * ($this->remises_total_infos['line_percent'] / 100));
                $this->remises_total_infos['line_amount_ttc'] = (float) ($total_ttc * ($this->remises_total_infos['line_percent'] / 100));
            }

            $this->remises_total_infos['total_percent'] = round($this->remises_total_infos['line_percent'] + $this->remises_total_infos['global_percent'] + $this->remises_total_infos['ext_global_percent'], 8);
            $this->remises_total_infos['total_amount_ht'] = $this->remises_total_infos['line_amount_ht'] + $this->remises_total_infos['global_amount_ht'] + $this->remises_total_infos['ext_global_amount_ht'];
            $this->remises_total_infos['total_amount_ttc'] = $this->remises_total_infos['line_amount_ttc'] + $this->remises_total_infos['global_amount_ttc'] + $this->remises_total_infos['ext_global_amount_ttc'];
        }

        return $this->remises_total_infos;
    }

    public function getRemiseTotalInfosFullQty()
    {
        $infos = array(
            'percent'                   => 0,
            'amount_ht'                 => 0,
            'amount_ttc'                => 0,
            'total_ht_without_remises'  => 0,
            'total_ttc_without_remises' => 0
        );

        if (!$this->isLoaded()) {
            return $infos;
        }

        $qty = (float) $this->getFullQty();

        $total_ht = $this->getTotalHT(true);
        $total_ttc = $this->getTotalTtcWithoutRemises(true);
        $infos['total_ht_without_remises'] = $total_ht;
        $infos['total_ttc_without_remises'] = $total_ttc;

        if (!$this->isRemisable()) {
            return $infos;
        }

        $remises = $this->getRemises();

        $total_line_amounts = 0;

        foreach ($remises as $remise) {
            switch ((int) $remise->getData('type')) {
                case ObjectLineRemise::OL_REMISE_PERCENT:
                    $infos['percent'] += (float) $remise->getData('percent');
                    break;

                case ObjectLineRemise::OL_REMISE_AMOUNT:
                    if ((int) $remise->getData('per_unit')) {
                        $total_line_amounts += ((float) $remise->getData('montant') * $qty);
                    } else {
                        $total_line_amounts += (float) $remise->getData('montant');
                    }
                    break;
            }
        }

        if ($total_line_amounts && (float) $total_ttc) {
            $infos['percent'] += (float) (($total_line_amounts / $total_ttc) * 100);
        }

        if ($infos['percent']) {
            $infos['amount_ht'] = (float) ($total_ht * ($infos['percent'] / 100));
            $infos['amount_ttc'] = (float) ($total_ttc * ($infos['percent'] / 100));
        }

        return $infos;
    }

    public function getClientDefaultRemiseFormValues()
    {
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent)) {
            $client = $parent->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                BimpObject::loadClass('bimpcommercial', 'ObjectLineRemise');
                if ((float) $client->dol_object->remise_percent > 0) {
                    return array(
                        array(
                            'fields' => array(
                                'label'   => 'Remise client par défaut',
                                'type'    => ObjectLineRemise::OL_REMISE_PERCENT,
                                'percent' => (float) $client->dol_object->remise_percent
                            )
                        )
                    );
                }
            }
        }

        return array();
    }

    public function getNewEquipmentDefaultPlaceValues()
    {
        $values = array();
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent)) {
            $entrepot = $parent->getChildObject('entrepot');

            if ($parent->object_name == 'Bimp_Facture' && in_array($parent->getData('fk_statut'), array(1, 2))) {
                BimpObject::loadClass('bimpequipment', 'BE_Place');
                $values = array(
                    'fields' => array(
                        'type'      => BE_Place::BE_PLACE_CLIENT,
                        'id_client' => (int) $parent->getData('fk_soc')
                    )
                );
            } elseif (BimpObject::objectLoaded($entrepot)) {
                BimpObject::loadClass('bimpequipment', 'BE_Place');
                $values = array(
                    'fields' => array(
                        'type'        => BE_Place::BE_PLACE_ENTREPOT,
                        'id_entrepot' => (int) $entrepot->id
                    )
                );
            }
        }

        return $values;
    }

    public function getQtyDecimals()
    {
        $product = $this->getProduct();

        if (BimpObject::objectLoaded($product)) {
            if ($product->getData('fk_product_type') === 0) {
                return 1;
            }
        }

        return 6;
    }

    public function getTotalRemisesArrieres($full_qty = false)
    {
        $total = 0;
        $qty = ($full_qty ? $this->getFullQty() : (float) $this->qty);

        $remises = $this->getRemisesArrieres();

        foreach ($remises as $remise) {
            $total += $remise->getRemiseAmount() * $qty;
        }

        return $total;
    }

    public function getSerials($include_imei = false)
    {
        $serials = array();

        if ($this->isLoaded() && static::$parent_comm_type) {
            $fields = array('a.serial');

            if ($include_imei) {
                $fields[] = 'a.imei';
            }

            $sql = BimpTools::getSqlSelect($fields);
            $sql .= BimpTools::getSqlFrom('be_equipment', array(
                        'le' => array(
                            'alias' => 'le',
                            'table' => 'object_line_equipment',
                            'on'    => 'le.id_equipment = a.id'
                        )
            ));
            $sql .= BimpTools::getSqlWhere(array(
                        'le.id_object_line' => (int) $this->id,
                        'le.object_type'    => static::$parent_comm_type
            ));

            $rows = $this->db->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $serial = $r['serial'];

                    if ($include_imei) {
                        if ($r['imei'] != '' && $r['imei'] != 'n/a') {
                            $serial .= ' (' . $r['imei'] . ')';
                        }
                    }
                    $serials[] = $serial;
                }
            }
        }

        return $serials;
    }

    // Affichages: 

    public function displayLineData($field, $edit = 0, $display_name = 'default', $no_html = false)
    {
        global $modeCSV;

        if ($modeCSV) {
            $no_html = true;
            $edit = 0;
        }

        $html = '';

        $type = (int) $this->getData('type');

        if ($type === self::LINE_TEXT) {
            if (!array_key_exists($field, self::$text_line_data)) {
                return '';
            }
        } elseif ($type === self::LINE_SUB_TOTAL) {
            if (!array_key_exists($field, self::$text_line_data)) {
                return $this->displaySubTotalLineData($field, $no_html);
            }
        }

        if ($edit && $this->isFieldEditable($field) && $this->canEditField($field)) {
            $dataDefs = $this->getLineDataDefs();
            if (!array_key_exists($field, $dataDefs)) {
                return '';
            }
            $required = (int) (isset($dataDefs[$field]['required']) ? $dataDefs[$field]['required'] : 0);
            $html .= '<div class="inputContainer ' . $field . '_inputContainer"';
            $html .= ' data-field_name="' . $field . '"';
            $html .= ' data-initial_value="' . (isset($this->{$field}) ? $this->{$field} : '') . '"';
            $html .= ' data-multiple="0"';
            $html .= ' data-required="' . $required . '"';
            $html .= ' data-data_type="' . $dataDefs[$field]['type'] . '"';
            $html .= ' data-field_prefix=""';
            $html .= '>';
            $html .= $this->renderLineInput($field);
            $html .= '</div>';
        } else {
            $format = "";
            switch ($field) {
                case 'id_product':
                    $product = $this->getProduct();
                    if (!BimpObject::objectLoaded($product)) {
                        $msg = 'Erreur: le produit d\'ID ' . $this->id_product . ' n\'existe pas';
                        if ($no_html) {
                            $html .= $msg;
                        } else {
                            $html .= BimpRender::renderAlerts($msg);
                        }
                    } else {
                        switch ($display_name) {
                            case 'ref':
                                $html = $product->getRef();
                                break;

                            case 'nom_url':
                                if ($this->no_html) {
                                    $html .= $product->getRef();
                                } else {
                                    $html .= $product->getLink();
                                }

                                break;

                            case 'card':
                                if ($this->no_html) {
                                    $html = BimpObject::getInstanceNom($product);
                                } else {
                                    $card = new BC_Card($product);
                                    $html = $card->renderHtml();
                                }
                                break;

                            case 'default':
                            case 'nom':
                            default:
                                $html = BimpObject::getInstanceNom($product);
                                break;
                        }
                    }
                    break;

                case 'desc':
                case 'desc_light':
                    if ((int) $this->getData('id_parent_line')) {
                        if (!$no_html) {
                            $html .= '<span style="display: inline-block; margin: 0 0 5px 15px; height: 100%; border-left: 3px solid #787878;"></span>';
                            $html .= '<span style="margin-right: 15px; color: #787878;font-size: 18px;">' . BimpRender::renderIcon('fas_long-arrow-alt-right') . '</span>';
                            $html .= '<div style="display: inline-block">';
                        }
                    }


                    $text = '';
                    $desc = '';

                    if ((int) $this->id_remise_except) {
                        BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                        $discount = new DiscountAbsolute($this->db->db);
                        $discount->fetch((int) $this->id_remise_except);

                        if (!BimpObject::objectLoaded($discount)) {
                            $html .= BimpRender::renderAlerts('La remise d\'ID ' . $this->id_remise_except) . ' n\'existe plus';
                        } else {
                            $desc = BimpTools::getRemiseExceptLabel($discount->description);

                            if (!$desc) {
                                $desc = 'Remise client';
                            }

                            $text = $desc;

                            if (!$no_html) {
                                $text .= ' ' . $discount->getNomUrl(1);
                            }
                        }
                    } else {
                        $desc = BimpTools::cleanString($this->desc);

                        $product = $this->getProduct();
                        if (BimpObject::objectLoaded($product)) {
                            global $modeCSV;
                            if ($modeCSV)
                                $text .= $this->displayLineData('id_product', 0, 'ref', $no_html);
                            else
                                $text .= $this->displayLineData('id_product', 0, 'nom_url', $no_html);

                            $product_label = BimpTools::cleanString($product->getData('label'));

                            $desc = str_replace("  ", " ", $desc);
                            $product_label = str_replace("  ", " ", $product_label);

                            if ($product_label) {
                                if (preg_match('/^' . preg_quote($product_label, '/') . '(.*)$/', $desc, $matches)) {
                                    $desc = $matches[1];
                                }

                                if (!(int) $this->getData('hide_product_label')) {
                                    $text .= ($text ? '<br/>' : '') . $product_label;
                                }
                            }

                            if ($this->date_from) {
                                $dt_from = new DateTime($this->date_from);
                                if ($text) {
                                    $text .= '<br/>';
                                }
                                if ($this->date_to) {
                                    $text .= 'Du ';
                                } else {
                                    $text .= 'A partir du ';
                                }
                                $text .= $dt_from->format('d/m/Y');
                            }

                            if ($this->date_to) {
                                $dt_to = new DateTime($this->date_to);
                                if (!$this->date_from) {
                                    $text .= ($text ? '<br/>' : '') . 'Jusqu\'au ';
                                } else {
                                    $text .= ' au ';
                                }
                                $text .= $dt_to->format('d/m/Y');
                            }
                        }
                        if ((!$text || $field !== 'desc_light') && $desc) {
                            $text .= ($text ? '<br/>' : '') . (string) $desc;
                        }
                    }

                    if ($no_html) {
                        $text = BimpTools::replaceBr($text);
                        $text = (string) strip_tags($text);
                    }

                    $html .= $text;

                    if (!$no_html && (int) $this->getData('id_parent_line')) {
                        $html .= '</div>';
                    }
                    break;

                case 'qty':
                    $html .= (float) $this->qty;
                    if ($this->field_exists('force_qty_1') && (int) $this->getData('force_qty_1')) {
                        $html .= '<br/>';
                        $msg = 'L\'option "Forcer qté à 1" est activée. Une seule unité sera inscrite dans le PDF et le total de la ligne sera utilisé comme prix unitaire';
                        $html .= '<span class="warning bs-popover"' . BimpRender::renderPopoverData($msg) . '>(Forcée à 1)</span>';
                    }
                    if ($this->field_exists('qty_modif')) {
                        $qty_modif = (float) $this->getData('qty_modif');
                        if ($qty_modif) {
                            if (!$no_html) {
                                $html .= '<span class="important">';
                            }
                            $html .= ' (' . ($qty_modif > 0 ? '+' : '' ) . $qty_modif . ')';
                            if (!$no_html) {
                                $html .= '</span>';
                            }
                        }
                    }
                    break;

                case 'pu_ht':
                    $format = 'price';
                    $value = (float) $this->pu_ht;
                    if ($no_html) {
                        $html = price((float) $this->pu_ht) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->pu_ht, 'EUR', 0, 0, 0, 2, 1);
                    }
                    break;

                case 'pu_ttc':
                    $format = 'price';
                    $value = (float) $this->getUnitPriceTTC();
                    if ($no_html) {
                        $html = price((float) $this->getUnitPriceTTC()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getUnitPriceTTC(), 'EUR', 0, 0, 0, 2, 1);
                    }
                    break;

                case 'tva_tx':
                    $html .= str_replace('.', ',', (string) $this->tva_tx) . ' %';
                    break;

                case 'pa_ht':
                    $format = 'price';
                    $value = (float) $this->pa_ht;
                    $pa_ht = (float) $this->pa_ht;

                    if ($no_html) {
                        $html .= price((float) $pa_ht) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $pa_ht, 'EUR', 0, 0, 0, 2, 1);
                    }

                    $remises_arrieres = $this->getRemisesArrieres();
                    if (!empty($remises_arrieres)) {
                        foreach ($remises_arrieres as $remise_arriere) {
                            $remise_amount = (float) $remise_arriere->getRemiseAmount();
                            if ($remise_amount) {
                                $pa_ht -= $remise_amount;
                                if ($no_html) {
                                    $html .= "\n" . '- Remise ' . $remise_arriere->getData('label') . ' : ' . price($remise_amount) . ' €';
                                } else {
                                    $html .= '<br/>- Remise ' . $remise_arriere->getData('label') . ' : ' . BimpTools::displayMoneyValue($remise_amount);
                                }
                            }
                        }

                        if ($pa_ht != $value) {
                            if ($no_html) {
                                $html .= "\n" . 'PA Final prévu : ' . price($pa_ht) . ' €';
                            } else {
                                $html .= '<br/><b>PA Final prévu : ' . BimpTools::displayMoneyValue((float) $pa_ht, 'EUR', 0, 0, 0, 2, 1) . '</b>';
                            }
                        }
                    }
                    break;

                case 'remise':
                    $html .= BimpTools::displayFloatValue($this->remise, 2, ',', 0, 0, 0, 1, 1) . ' %';
                    break;

                case 'date_from':
                    if ((string) $this->date_from && $this->date_from !== '0000-00-00') {
                        $date = new DateTime($this->date_from);
                        if ($no_html) {
                            $html .= $date->format('d / m / Y');
                        } else {
                            $html .= '<span class="date">' . $date->format('d / m / Y') . '</span>';
                        }
                    }
                    break;

                case 'date_to':
                    if ((string) $this->date_to && $this->date_to !== '0000-00-00') {
                        $date = new DateTime($this->date_to);
                        if ($no_html) {
                            $html .= $date->format('d / m / Y');
                        } else {
                            $html .= '<span class="date">' . $date->format('d / m / Y') . '</span>';
                        }
                    }
                    break;

                case 'total_ht':
                    $format = 'price';
                    $value = (float) $this->getTotalHT();
                    if ($no_html) {
                        $html = price((float) $this->getTotalHT()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getTotalHT(), 'EUR', 0, 0, 0, 2, 1);
                    }
                    if ($this->field_exists('qty_modif')) {
                        $qty_modif = (float) $this->getData('qty_modif');
                        if ($qty_modif) {
                            if ($no_html) {
                                $html .= "\n(" . price((float) $this->getTotalHT(true)) . ' €)';
                            } else {
                                $html .= '<br/><span class="important">';
                                $html .= BimpTools::displayMoneyValue((float) $this->getTotalHT(true), 'EUR', 0, 0, 0, 2, 1);
                                $html .= '</span>';
                            }
                        }
                    }
                    break;

                case 'total_ht_w_remises':
                    $format = 'price';
                    $value = (float) $this->getTotalHTWithRemises();
                    if ($no_html) {
                        $html = price((float) $this->getTotalHTWithRemises()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getTotalHTWithRemises(), 'EUR', 0, 0, 0, 2, 1);
                    }
                    if ($this->field_exists('qty_modif')) {
                        $qty_modif = (float) $this->getData('qty_modif');
                        if ($qty_modif) {
                            if ($no_html) {
                                $html .= "\n(" . price((float) $this->getTotalHTWithRemises(true)) . ' €)';
                            } else {
                                $html .= '<br/><span class="important">';
                                $html .= '(' . BimpTools::displayMoneyValue((float) $this->getTotalHTWithRemises(true), 'EUR', 0, 0, 0, 2, 1) . ')';
                                $html .= '</span>';
                            }
                        }
                    }
                    break;

                case 'total_ttc':
                    $format = 'price';
                    $value = (float) $this->getTotalTTC();
                    if ($no_html) {
                        $html .= price((float) $this->getTotalTTC()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getTotalTTC(), 'EUR', 0, 0, 0, 2, 1);
                    }
                    if ($this->field_exists('qty_modif')) {
                        $qty_modif = (float) $this->getData('qty_modif');
                        if ($qty_modif) {
                            if ($no_html) {
                                $html .= "\n(" . price((float) $this->getTotalTTC(true)) . ' €)';
                            } else {
                                $html .= '<br/><span class="important">';
                                $html .= '(' . BimpTools::displayMoneyValue((float) $this->getTotalTTC(true), 0, 0, 0, 2, 1) . ')';
                                $html .= '</span>';
                            }
                        }
                    }
                    break;

                case 'margin':
                    $margin = (float) $this->getMargin();
                    $margin_full_qty = (float) $this->getMargin(true);

                    $format = 'price';
                    $value = (float) $margin;
                    $margin_rate = 0;
                    if ($margin !== 0.0) {
                        $margin_rate = round($this->getMarginRate(), 4);
                    }

                    if ($no_html) {
                        $html = price($margin) . ' € ';
                        if ($margin !== $margin_full_qty) {
                            $html .= ' (' . $margin_full_qty . ' €)';
                        }
                        if (!$margin_rate && !(float) $this->pa_ht) {
                            $html .= '(∞)';
                        } else {
                            $html .= '(' . $margin_rate . ' %)';
                        }
                    } else {
                        if ($margin <= 0) {
                            $html = '<span class="danger">';
                            $html .= BimpTools::displayMoneyValue($margin, 'EUR');
                            if ($margin_rate) {
                                $html .= ' (' . $margin_rate . ' %)';
                            } elseif (!(float) $this->pa_ht) {
                                $html .= ' (&infin;)';
                            }
                            $html .= '</span>';
                        } else {
                            $html = '<span class="bold">';
                            $html .= BimpTools::displayMoneyValue($margin, 'EUR');
                            if ($margin_rate) {
                                $html .= ' (' . $margin_rate . ' %)';
                            } elseif (!(float) $this->pa_ht) {
                                $html .= ' (&infin;)';
                            }
                            $html .= '</span>';
                        }

                        if ($margin !== $margin_full_qty) {
                            $html .= '<br/><span class="important">';
                            $html .= BimpTools::displayMoneyValue($margin_full_qty);
                            $html .= '</span>';
                        }
                    }
                    break;

                case 'marge_prevue':
                    $margin = (float) $this->getMargin();
                    $margin_full_qty = (float) $this->getMargin(true);
                    $total_reval = 0;

                    $done = false;
                    if ($this->object_name === 'Bimp_FactureLine') {
                        $facture = $this->getParentInstance();

                        if (BimpObject::objectLoaded($facture)) {
                            if ((int) $facture->getData('fk_statut')) {
                                $done = true;
                                $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
                                            'id_facture_line' => (int) $this->id
                                ));

                                foreach ($revals as $reval) {
                                    if (in_array((int) $reval->getData('status'), array(0, 1))) {
                                        $reval_amount = $reval->getTotal();
                                        $total_reval += $reval_amount;
                                        $margin += $reval_amount;
                                        $margin_full_qty += $reval_amount;
                                    }
                                }
                            }
                        }
                    }

                    if (!$done) {
                        $remises_arrieres = $this->getTotalRemisesArrieres(false);
                        $remises_arrieres_full_qty = $this->getTotalRemisesArrieres(true);

                        $total_reval += $remises_arrieres;
                        $margin += $remises_arrieres;
                        $margin_full_qty += $remises_arrieres_full_qty;
                    }

                    $format = 'price';
                    $value = (float) $margin;
                    $margin_rate = 0;
                    if ($margin !== 0.0) {
                        $price = 0;
                        if ((int) BimpCore::getConf('use_tx_marque', 1, 'bimpcommercial')) {
                            $price = (float) $this->pu_ht;
                            if (!is_null($this->remise) && (float) $this->remise != 0) {
                                $price -= ($price * ((float) $this->remise / 100));
                            }
                        } else {
                            $price = $this->pa_ht + $total_reval;
                        }
                        if ($price) {
                            $margin_rate = round(($margin / ((float) $price * $this->qty)) * 100, 4);
                        }
                    }

                    if ($no_html) {
                        $html = price($margin) . ' € ';
                        if ($margin !== $margin_full_qty) {
                            $html .= ' (' . $margin_full_qty . ' €)';
                        }
                        if (!$margin_rate && !(float) $this->pa_ht) {
                            $html .= '(∞)';
                        } else {
                            $html .= '(' . $margin_rate . ' %)';
                        }
                    } else {
                        if ($margin <= 0) {
                            $html = '<span class="danger">';
                            $html .= BimpTools::displayMoneyValue($margin, 'EUR');
                            if ($margin_rate) {
                                $html .= ' (' . $margin_rate . ' %)';
                            } elseif (!(float) $this->pa_ht) {
                                $html .= ' (&infin;)';
                            }
                            $html .= '</span>';
                        } else {
                            $html = '<span class="bold">';
                            $html .= BimpTools::displayMoneyValue($margin, 'EUR');
                            if ($margin_rate) {
                                $html .= ' (' . $margin_rate . ' %)';
                            } elseif (!(float) $this->pa_ht) {
                                $html .= ' (&infin;)';
                            }
                            $html .= '</span>';
                        }

                        if ($margin !== $margin_full_qty) {
                            $html .= '<br/><span class="important">';
                            $html .= BimpTools::displayMoneyValue($margin_full_qty);
                            $html .= '</span>';
                        }
                    }
                    break;

                case 'remisable':
                    if ((int) $this->isRemisable()) {
                        $html .= '<span class="success">OUI</span>';
                    } else {
                        $html .= '<span class="danger">NON</span>';
                    }
                    break;

                case 'remise_crt':
                    return $this->displayData($field);
            }

            if ($format == 'price' && $modeCSV) {
                $html = str_replace(".", ",", $value);
            }
        }

        return $html;
    }

    public function displaySubTotalLineData($field, $no_html = false)
    {
        $html = '';

        if (in_array($field, array('total_ht', 'total_ht_w_remises', 'total_ttc', 'margin', 'marge_prevue', 'remise', 'remise_full'))) {
            $parent = $this->getParentInstance();

            if (BimpObject::objectLoaded($parent)) {
                $where = 'id_obj = ' . $parent->id . ' AND position < ' . (int) $this->getData('position');
                $where .= ' AND type = ' . self::LINE_SUB_TOTAL;

                $prev_subtotal_line = null;
                $id_prev_subtotal_line = (int) $this->db->getValue($this->getTable(), $this->getPrimary(), $where, 'position', 'DESC');

                if ($id_prev_subtotal_line) {
                    $prev_subtotal_line = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_prev_subtotal_line);
                }

                $filters = array(
                    'type'     => array(
                        'in' => array(self::LINE_PRODUCT, self::LINE_FREE)
                    ),
                    'position' => array(
                        'min' => (BimpObject::objectLoaded($prev_subtotal_line) ? (int) $prev_subtotal_line->getData('position') + 1 : 0),
                        'max' => ((int) $this->getData('position') - 1)
                    )
                );

                $lines = $parent->getChildrenObjects('lines', $filters);

                if (!empty($lines)) {
                    if (in_array($field, array('remise', 'remise_full'))) {
                        $total_ttc = 0;
                        $total_remise_ht = 0;
                        $total_remise_ttc = 0;

                        foreach ($lines as $line) {
                            $line_ttc = (float) $line->getTotalTTC(true);
                            $total_ttc += $line_ttc;

                            if ((float) $line->remise) {
                                $total_remise_ttc += ($line_ttc * ($line->remise / 100));
                                $total_remise_ht += ((float) $line->getTotalHT(true) * ($line->remise / 100));
                            }
                        }

                        $remise_percent = 0;

                        if ($total_remise_ttc && $total_ttc) {
                            $remise_percent = ($total_remise_ttc / $total_ttc) * 100;
                        }

                        switch ($field) {
                            case 'remise':
                                $html .= BimpTools::displayFloatValue($remise_percent, 2, ',', 0, 0, 0, 1, 1) . ' %';
                                break;

                            case 'remise_full':
                                $html .= '<div style="display: inline-block">' . BimpTools::displayMoneyValue($total_remise_ht, 'EUR', true, true, false, 2, 1, ',', 1) . '</div>';
                                $html .= ' / ';
                                $html .= '<div style="display: inline-block">' . BimpTools::displayMoneyValue($total_remise_ttc, 'EUR', true, true, false, 2, 1, ',', 1) . '</div>';
                                $html .= '<br/>(' . BimpTools::displayFloatValue($remise_percent, 2, ',', 0, 0, 0, 1, 1) . ' %)';
                                break;
                        }
                    } else {
                        $amount = 0;
                        foreach ($lines as $line) {
                            switch ($field) {
                                case 'total_ht':
                                    $amount += (float) $line->getTotalHT(true);
                                    break;

                                case 'total_ht_w_remises':
                                    $amount += (float) $line->getTotalHTWithRemises(true);
                                    break;

                                case 'total_ttc':
                                    $amount += (float) $line->getTotalTTC(true);
                                    break;

                                case 'margin':
                                    $amount += (float) $line->getMargin(true);
                                    break;

                                case 'marge_prevue':
                                    $amount += (float) $line->getMargePrevue(true);
                                    break;
                            }
                        }
                        $html .= BimpTools::displayMoneyValue($amount, 'EUR', true, true, false, 2, 1, ',', 1);
                    }
                }
            }
        }

        return $html;
    }

    public function displayEquipment()
    {
//        if ($this->isLoaded()) {
//            if ((int) $this->getData('id_equipment')) {
//                return $this->displayData('id_equipment', 'nom_url');
//            } elseif ((int) $this->id_product) {
//                $product = $this->getProduct();
//                if (BimpObject::objectLoaded($product)) {
//                    if ($product->isSerialisable()) {
//                        return '<span class="danger">Attribution nécéssaire</span>';
//                    } else {
//                        return '<span class="warning">Non sérialisable</span>';
//                    }
//                } else {
//                    return '<span class="danger">Produit invalide</span>';
//                }
//            }
//        }

        return BimpRender::renderAlerts('ERREUR');
    }

    public function displaySerials()
    {
        $serials = array();

        $equipment_lines = $this->getEquipmentLines();
        if (count($equipment_lines)) {
            $equipments = array();

            foreach ($equipment_lines as $equipment_line) {
                if ((int) $equipment_line->getData('id_equipment')) {
                    $equipments[] = (int) $equipment_line->getData('id_equipment');
                }
            }

            if (count($equipments)) {
                foreach ($equipments as $id_equipment) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    $serials[] = $equipment->displaySerialImei();
                }
            }
        }


        return implode("<br/>", $serials);
    }

    public function displayRemise()
    {
        $html = '';
        if ($this->isLoaded()) {
            if ((int) $this->getData('type') === self::LINE_SUB_TOTAL) {
                $html .= $this->displaySubTotalLineData('remise_full');
            } elseif ($this->isRemisable()) {
                $remises = $this->getRemiseTotalInfos();
                if ((float) $remises['total_percent']) {
                    $html .= '<div style="display: inline-block">' . BimpTools::displayMoneyValue($remises['total_amount_ht'], 'EUR') . '</div>';
                    $html .= ' / <div style="display: inline-block">' . BimpTools::displayMoneyValue($remises['total_amount_ttc'], 'EUR') . '</div>';
                    $html .= '<br/>(' . round($remises['total_percent'], 4) . '%)';

                    if ($this->field_exists('qty_modif')) {
                        $qty_modif = (float) $this->getData('qty_modif');
                        if ($qty_modif) {
                            $html .= '<br/>';
                            $html .= '<div class="important">';
                            $remises = $this->getRemiseTotalInfosFullQty();
                            $html .= '<div style="display: inline-block">' . BimpTools::displayMoneyValue($remises['amount_ht'], 'EUR') . '</div>';
                            $html .= ' / <div style="display: inline-block">' . BimpTools::displayMoneyValue($remises['amount_ttc'], 'EUR') . '</div>';
                            $html .= '<br/>(' . round($remises['percent'], 4) . '%)';
                            $html .= '</div>';
                        }
                    }
                }
            } else {
                $html = '<span class="warning">Non remisable</span>';
            }
        }

        return $html;
    }

    public function displayUnitPriceHTWithRemises()
    {
        global $modeCSV;
        if ($modeCSV)
            return $this->priceToCsv($this->getUnitPriceHTWithRemises());
        else
            return BimpTools::displayMoneyValue($this->getUnitPriceHTWithRemises(), 'EUR');
    }

    public function displayMargePrevue()
    {
        
    }

    public function displayLinkedObject()
    {
        if ($this->getData('linked_object_name') === 'commande_line') {
            $id_commande_line = (int) $this->getData('linked_id_object');
            $commandeLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_commande_line);
            $commande = $commandeLine->getParentInstance();
            global $modeCSV;
            if ($modeCSV) {
                return $commande->getRef() . ' ln ' . $commandeLine->getData('position');
            } else
                return $commande->getLink() . "<br/>" . $commandeLine->getLink();
        }
        return '';
    }

    public function displayDureeReliquat($type = 'TTC')
    {
        if (!empty($this->date_from) && !empty($this->date_to)) {
            $now = date('Y-m-d');
            if ($this->date_from > $now) {
                $rate = 1;
            } elseif ($this->date_to < $now) {
                $rate = 0;
            } else {
                $from_tms = BimpTools::getDateForDolDate($this->date_from);
                $to_tms = BimpTools::getDateForDolDate($this->date_to);
                $now_tms = time();

                $total = $to_tms - $from_tms;
                $current = $now_tms - $from_tms;

                if ((int) $total) {
                    $rate = ($current / $total);
                } else {
                    $rate = 1;
                }
            }

            switch ($type) {
                case 'ttc':
                default:
                    $ttc = $this->getTotalTTC(true);
                    $amount = $ttc * $rate;
                    break;

                case 'ht':
                    $ht = $this->getTotalHT(true);
                    $amount = $ht * $rate;
                    break;
            }

            return BimpTools::displayMoneyValue($amount, 'EUR', 0, 0, 0, 2, 1);
        }

        return '';
    }

    // Gestion ligne dolibarr:

    public function createFromDolLine($id_obj, $line)
    {
        $errors = array();
        $warnings = array();

        if (BimpObject::objectLoaded($line)) {
            if ($this->isLoaded()) {
                BimpCache::unsetBimpObjectInstance($this->module, $this->object_name, $this->id);
            }

            $parent = $this->parent;
            $this->reset();
            $this->parent = $parent;

            $id = (int) $this->db->getValue($this->getTable(), 'id', 'id_line = ' . $line->id);

            if (!$id) {
                $remisable = 1;

                if (isset($line->fk_product) && (int) $line->fk_product) {
                    $type = 1;
//                if ($this->dol_field_exists('remisable')) {
                    $remisable = $this->db->getValue('product_extrafields', 'remisable', '`fk_object` = ' . (int) $line->fk_product);
//                }
                    if (is_null($remisable)) {
                        $remisable = 1;
                    }
                } elseif ((float) $line->subprice) {
                    $type = 3;
                } else {
                    $type = 2;
                }

                $errors = $this->validateArray(array(
                    'id_obj'             => (int) $id_obj,
                    'id_line'            => (int) $line->id,
                    'type'               => $type,
                    'deletable'          => 1,
                    'editable'           => 1,
                    'linked_id_object'   => 0,
                    'linked_object_name' => 0,
                    'position'           => (int) $line->rang,
                    'remisable'          => $remisable
                ));

                if (!count($errors)) {
                    $id = (int) $this->db->insert($this->getTable(), $this->getDbData(), true);
                    if ($id <= 0) {
                        $msg = 'Echec de l\'insertion des données';
                        $sqlError = $this->db->db->lasterror();
                        if ($sqlError) {
                            $msg .= ' - ' . $sqlError;
                        }
                        $errors[] = $msg;
                    } else {
                        $parent_status = (int) $parent->getData('fk_statut');
                        $this->parent->set("fk_statut", 0);
                        if (!$this->fetch($id, $this->parent)) {
                            $errors[] = BimpTools::ucfirst($this->getLabel('the')) . ' d\'ID ' . $id . ' semble avoir bien était enregistrée mais n\'a pas été trouvée';
                            return $errors;
                        }

                        if ($remisable && isset($line->remise_percent) && (float) $line->remise_percent) {
                            if (static::$parent_comm_type) {
                                $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise', null, $this);
                                $remise->validateArray(array(
                                    'id_object_line' => (int) $id,
                                    'object_type'    => static::$parent_comm_type,
                                    'label'          => '',
                                    'type'           => ObjectLineRemise::OL_REMISE_PERCENT,
                                    'percent'        => (float) $line->remise_percent
                                ));
                                $remise_errors = $remise->create($warnings, true);
                                if (count($remise_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise de ' . $line->remise_percent . ' % pour la ligne n° ' . $line->rang);
                                }
                            }
                        }

                        if ($this->equipment_required) {
                            $this->createEquipmentsLines();
                        }

                        $this->parent->set("fk_statut", $parent_status);
                    }
                }
            } else {
                $this->fetch($id);
                BimpCore::addlog('Tentative de création ' . $this->getLabel('of_a') . ' existant déjà', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', $this, array(
                    'Context'                  => 'createFromDolLine()',
                    'ID ' . $this->object_name => $id,
                    'ID ligne dolibarr'        => (int) $line->id
                ));
            }
        }

        return $errors;
    }

    protected function createLine($check_data = true, $force_create = false)
    {
        $errors = array();

        $instance = $this->getParentInstance();
        if (!BimpObject::objectLoaded($instance)) {
            $errors[] = BimpTools::ucfirst(BimpObject::getInstanceLabel($instance, '')) . ' invalide';
        } else {
            if ($check_data) {
                $errors = $this->validate();
                if (count($errors)) {
                    return $errors;
                }
            }

            $object = $instance->dol_object;
            $object->error = '';
            $object->errors = array();

            $result = null;

            if ($force_create) {
                $initial_brouillon = isset($object->brouillon) ? $object->brouillon : null;
                $object->brouillon = 1;
            }

            switch ((int) $this->getData('type')) {
                case self::LINE_PRODUCT:
                case self::LINE_FREE:
                    if (!is_null($this->date_from)) {
                        $date_from = BimpTools::getDateForDolDate($this->date_from);
                    } else {
                        $date_from = '';
                    }

                    if (!is_null($this->date_to)) {
                        $date_to = BimpTools::getDateForDolDate($this->date_to);
                    } else {
                        $date_to = '';
                    }

                    $type = 0;
                    if ((int) $this->id_product) {
                        $product = $this->getProduct();
                        if (BimpObject::objectLoaded($product)) {
                            $type = (int) $product->getData('fk_product_type');
                        }
                    } else {
                        $type = (int) $this->product_type;
                    }

                    $class_name = get_class($object);
                    switch ($class_name) {
                        case 'Propal':
//                            addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0.0, $txlocaltax2=0.0, $fk_product=0, $remise_percent=0.0, $price_base_type='HT', $pu_ttc=0.0, $info_bits=0, $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=0, $pa_ht=0, $label='',$date_start='', $date_end='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0, $pu_ht_devise=0, $fk_remise_except=0)
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (float) $this->remise, 'HT', 0, 0, $type, (int) $this->getData('position'), 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht, '', $date_from, $date_to, 0, null, '', 0, 0, (int) $this->id_remise_except);
                            break;

                        case 'Facture':
//                            addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=self::TYPE_STANDARD, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='', $array_options=0, $situation_percent=100, $fk_prev_id=0, $fk_unit = null, $pu_ht_devise = 0)
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (float) $this->remise, $date_from, $date_to, 0, 0, $this->id_remise_except, 'HT', 0, $type, (int) $this->getData('position'), 0, '', 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht);
                            break;

                        case 'Commande':
//                            addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $info_bits=0, $fk_remise_except=0, $price_base_type='HT', $pu_ttc=0, $date_start='', $date_end='', $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0, $pu_ht_devise = 0)
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (float) $this->remise, 0, (int) $this->id_remise_except, 'HT', 0, $date_from, $date_to, $type, (int) $this->getData('position'), 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht);
                            break;

                        case 'CommandeFournisseur':
                            if (isset($this->ref_supplier)) {
                                $ref_supplier = $this->ref_supplier;
                            } else {
                                $ref_supplier = '';
                            }
//                            addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0.0, $txlocaltax2=0.0, $fk_product=0, $fk_prod_fourn_price=0, $ref_supplier='', $remise_percent=0.0, $price_base_type='HT', $pu_ttc=0.0, $type=0, $info_bits=0, $notrigger=false, $date_start=null, $date_end=null, $array_options=0, $fk_unit=null, $pu_ht_devise=0, $origin='', $origin_id=0)
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (int) $this->id_fourn_price, $ref_supplier, (float) $this->remise, 'HT', 0.0, $type, 0, false, $date_from, $date_to);
                            break;

                        case 'FactureFournisseur':
                            if (isset($this->ref_supplier)) {
                                $ref_supplier = $this->ref_supplier;
                            } else {
                                $ref_supplier = '';
                            }
//                            addline($desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT', $type=0, $rang=-1, $notrigger=false, $array_options=0, $fk_unit=null, $origin_id=0, $pu_ht_devise=0, $ref_supplier='')
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, (float) $this->tva_tx, 0, 0, $this->qty, (int) $this->id_product, (float) $this->remise, $date_from, $date_to, 0, '', 'HT', $type, (int) $this->getData('position'), false, 0, null, 0, 0, $ref_supplier);
                            break;

                        default:
                            $errors[] = 'Objet parent non défini';
                            break;
                    }


                    break;

                case self::LINE_TEXT:
                case self::LINE_SUB_TOTAL:
                    $class_name = get_class($object);
                    switch ($class_name) {
                        case 'Propal':
                        case 'Commande':
                        case 'Facture':
                        case 'CommandeFournisseur':
                            $result = $object->addLine((string) $this->desc, 0, (float) $this->qty, 0);
                            break;

                        case 'FactureFournisseur':
                            $result = $object->addLine((string) $this->desc, 0, 0, 0, 0, 1, 0, 0, '', '', '', 0, '');
                            break;

                        default:
                            $errors[] = 'Objet parent non défini';
                            break;
                    }
                    break;

                default:
                    $errors[] = 'Type invalide';
                    break;
            }
            if (is_null($result) || $result <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object), 'Des erreurs sont survenues lors de l\'ajout de la ligne ' . BimpObject::getInstanceLabel($instance, 'to'));
            } else {
                if ($this->isLoaded()) {
                    $this->updateField('id_line', (int) $result);
                    $this->resetPositions();
                } else {
                    $this->set('id_line', (int) $result);
                }

                $object->fetch_lines();
                $object->update_price();
                $this->hydrateFromDolObject();
            }

            if ($force_create) {
                if (is_null($initial_brouillon)) {
                    unset($object->brouillon);
                } else {
                    $object->brouillon = $initial_brouillon;
                }
            }
        }

        return $errors;
    }

    protected function updateLine($check_data = true, $force_update = false)
    {
        $errors = array();

        $instance = $this->getParentInstance();

        if (!BimpObject::objectLoaded($instance)) {
            $errors[] = BimpTools::ucfirst(BimpObject::getInstanceLabel($instance, '')) . ' invalide';
        } else {
            $id_line = (int) $this->getData('id_line');
            if (!$id_line) {
                return array('ID de la ligne ' . BimpObject::getInstanceLabel($instance, 'of_the') . ' absent');
            }

            if ($check_data) {
                $errors = $this->validate();
                if (count($errors)) {
                    return $errors;
                }
            }

            $object = $instance->dol_object;

            $object->error = '';
            $object->errors = array();

            $initial_brouillon = null;

            if ($force_update) {
                $initial_brouillon = isset($object->brouillon) ? $object->brouillon : null;
                $object->brouillon = 1;
            }

            $result = null;
            $class_name = get_class($object);

            switch ((int) $this->getData('type')) {
                case self::LINE_PRODUCT:
                case self::LINE_FREE:
                    if (!is_null($this->date_from)) {
                        $date_from = BimpTools::getDateForDolDate($this->date_from);
                    } else {
                        $date_from = '';
                    }

                    if (!is_null($this->date_to)) {
                        $date_to = BimpTools::getDateForDolDate($this->date_to);
                    } else {
                        $date_to = '';
                    }
                    switch ($class_name) {
                        case 'Propal':
                            BimpCache::unsetDolObjectInstance((int) $id_line, 'comm/propal', 'propal', 'PropaleLigne');
                            $result = $object->updateline($id_line, (float) $this->pu_ht, $this->qty, (float) $this->remise, (float) $this->tva_tx, 0, 0, (string) $this->desc, 'HT', 0, 0, 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht, '', 0, $date_from, $date_to);
                            break;

                        case 'Facture':
                            $result = $object->updateline($id_line, (string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->remise, $date_from, $date_to, (float) $this->tva_tx, 0, 0, 'HT', 0, 0, 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht);
                            break;

                        case 'Commande':
                            $result = $object->updateLine($id_line, (string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->remise, (float) $this->tva_tx, 0.0, 0.0, 'HT', 0, $date_from, $date_to, 0, 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht);
                            break;

                        case 'CommandeFournisseur':
                            if (isset($this->ref_supplier)) {
                                $ref_supplier = $this->ref_supplier;
                            } else {
                                $ref_supplier = '';
                            }
                            $result = $object->updateLine($id_line, (string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->remise, (float) $this->tva_tx, 0.0, 0.0, 'HT', 0, 0, 0, $date_from, $date_to, 0, null, 0, $ref_supplier);
                            break;

                        case 'FactureFournisseur':
                            $type = 0;
                            if ((int) $this->id_product) {
                                $product = $this->getProduct();
                                if (BimpObject::objectLoaded($product)) {
                                    $type = (int) $product->getData('fk_product_type');
                                }
                            }
                            if (isset($this->ref_supplier)) {
                                $ref_supplier = $this->ref_supplier;
                            } else {
                                $ref_supplier = '';
                            }
                            $result = $object->updateLine($id_line, (string) $this->desc, (float) $this->pu_ht, (float) $this->tva_tx, 0, 0, $this->qty, (int) $this->id_product, 'HT', 0, $type, (float) $this->remise, false, $date_from, $date_to, 0, null, 0, $ref_supplier);
                            break;

                        default:
                            $errors[] = 'Objet parent non défini';
                            break;
                    }
                    break;

                case self::LINE_TEXT:
                case self::LINE_SUB_TOTAL:
                    switch ($class_name) {
                        case 'Propal':
                            BimpCache::unsetDolObjectInstance((int) $id_line, 'comm/propal', 'propal', 'PropaleLigne');
                            $result = $object->updateline($id_line, 0, 0, 0, 0, 0, 0, (string) $this->desc);
                            break;

                        case 'Facture':
                            $result = $object->updateline($id_line, $this->desc, 0, 0, 0, '', '', 0);
                            break;

                        case 'Commande':
                        case 'CommandeFournisseur':
                        case 'FactureFournisseur':
                            $result = $object->updateline($id_line, $this->desc, 0, 0, 0, 0);
                            break;

                        default:
                            $result = 0;
                            $errors[] = 'Objet parent non défini';
                            break;
                    }
                    break;

                default:
                    $errors[] = 'Type de ligne invalide';
                    break;
            }

            if (!is_null($result) && $result <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object), 'Des erreurs sont survenues lors de la mise à jour de la ligne ' . BimpObject::getInstanceLabel($instance, 'of_the'));
            } else {
                if (!$instance->procededOperationMasseLine) {
                    $object->fetch_lines();
                    $object->update_price();
                    $this->hydrateFromDolObject();
                }
            }

            if ($force_update) {
                if (is_null($initial_brouillon)) {
                    unset($object->brouillon);
                } else {
                    $object->brouillon = $initial_brouillon;
                }
            }
        }

        return $errors;
    }

    protected function fetchLine()
    {
        $id_line = (int) $this->getData('id_line');

        if (!$id_line) {
            return false;
        }

        BimpCache::unsetDolObjectInstance((int) $id_line, 'comm/propal', 'propal', 'PropaleLigne');

        $line = $this->getChildObject('line');

        if (!BimpObject::objectLoaded($line)) {
            return false;
        }

        switch ($this->getData('type')) {
            case self::LINE_PRODUCT:
            case self::LINE_FREE:
                $this->id_product = (int) $line->fk_product;
                $this->id_fourn_price = (int) $line->fk_fournprice;
                $this->desc = (isset($line->desc) ? (string) $line->desc : (isset($line->description) ? (string) $line->description : ''));
                $this->pu_ht = (float) $line->subprice;
                $this->qty = isset($line->qty) ? $line->qty : 1;
                $this->tva_tx = (float) $line->tva_tx;
                $this->pa_ht = (float) $line->pa_ht;
                $this->remise = (float) $line->remise_percent;
                $this->date_from = BimpTools::getDateFromDolDate($line->date_start);
                $this->date_to = BimpTools::getDateFromDolDate($line->date_end);
                $this->id_remise_except = (int) $line->fk_remise_except;
                break;

            case self::LINE_TEXT:
            case self::LINE_SUB_TOTAL:
                $this->desc = (isset($line->desc) ? (string) $line->desc : (isset($line->description) ? (string) $line->description : ''));
                $this->qty = 0;
                break;

            default:
                return false;
        }

        return true;
    }

    protected function deleteLine()
    {
        $errors = array();

        $instance = $this->getParentInstance();

        if (!BimpObject::objectLoaded($instance)) {
            $errors[] = BimpTools::ucfirst(BimpObject::getInstanceLabel($instance, '')) . ' invalide';
        } else {
            $id_line = (int) $this->getData('id_line');
            if (!$id_line) {
                return array('ID de la ligne ' . BimpObject::getInstanceLabel($instance, 'of_the') . ' absent');
            }

            $instance->dol_object->error = '';
            $instance->dol_object->errors = array();

            if (in_array(get_class($instance->dol_object), array('Commande'))) {
                global $user;
                $result = $instance->dol_object->deleteline($user, $id_line);
            } else {
                $result = $instance->dol_object->deleteline($id_line);
            }
            if ($result <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($instance->dol_object), 'Des erreurs sont survenues lors de la suppression de la ligne ' . BimpObject::getInstanceLabel($instance, 'of_the'));
            } else {
                BimpCache::unsetDolObjectInstance((int) $id_line, 'comm/propal', 'propal', 'PropaleLigne');
            }
        }

        return $errors;
    }

    public function forceUpdateLine()
    {
        $errors = array();

        $line = $this->getChildObject('line');
        if (BimpObject::objectLoaded($line)) {
            $parent = $this->getParentInstance();
            if (BimpObject::objectLoaded($parent)) {
                $prev_status = (int) $parent->dol_object->statut;
                $parent->set('fk_statut', 0);
                $parent->dol_object->statut = 0;
                $errors = $this->updateLine(true, true);
                $parent->dol_object->statut = $prev_status;
                $parent->set('fk_statut', $prev_status);
            }
        } else {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent. Mise à jour forcée impossible';
        }

        return $errors;
    }

    // Gestion équipements: 

    public function checkEquipment($equipment)
    {
        $errors = array();

        if (!BimpObject::objectLoaded($equipment)) {
            $errors[] = 'Equipement invalide';
        } else {
            if ((int) $equipment->getData('id_product')) {
                $product = $equipment->getChildObject('product');
                if (!BimpObject::objectLoaded($product)) {
                    $errors[] = 'Le produit associé à l\'équipement ' . $equipment->id . ' - n° série "' . $equipment->getData('serial') . '" n\'existe plus';
                } elseif ((int) $this->id_product) {
                    if ((int) $product->id !== (int) $this->id_product) {
                        $errors[] = 'Cet équipement ne correspond pas au produit sélectionné';
                    }
                } else {
                    $this->id_product = $product->id;
                }
            } else {
                $errors[] = 'L\'équipement sélectionné n\'est associé à aucun produit';
                $this->id_product = 0;
            }

            if (!count($errors)) {
                $errors = $this->isEquipmentAvailable($equipment);
            }
        }

        return $errors;
    }

    public function createEquipmentsLines($qty = null)
    {
        $warnings = array();

        if (!static::$parent_comm_type) {
            $warnings[] = 'Erreur technique: tentative de création d\'une ligne équipement depuis une instance "ObjectLine"';
            return $warnings;
        }

        if (!$this->equipment_required) {
            return $warnings;
        }

        if ($this->isArticleLine() && (int) $this->id_product) {
            if (is_null($qty)) {
                $qty = abs((int) $this->qty);
                $qty -= count($this->getEquipmentLines());
            }

            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                if ($this->isProductSerialisable()) {
                    $i = 1;
                    while ($qty > 0) {
                        $instance = BimpObject::getInstance('bimpcommercial', 'ObjectLineEquipment');
                        $instance->validateArray(array(
                            'id_object_line' => (int) $this->id,
                            'object_type'    => static::$parent_comm_type,
                            'id_equipment'   => 0,
                        ));
                        $line_errors = $instance->create($warnings, true);
                        if (count($line_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne équipement n°' . $i);
                        }
                        $qty--;
                        $i++;
                    }
                }
            }
        }

        return $warnings;
    }

    public function attributeEquipment($id_equipment, $id_equipment_line = 0, $recalc_line_pa = true, $check_equipment = true)
    {
        // Si $pa_ht défini, il est prioritaire sur id_fourn_price et $equipment->getData('prix_achat')
        // Sinon, si $equipment->getData('prix_achat') est défini (pas 0), il est prioritaire sur $id_fourn_price. 
        // Si ni $id_fourn_price, ni $equipment->getData('prix_achat'), ni $pa_ht n'est défini, c'est le pa_ht de la ligne qui s'applique. 

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if (!(int) $id_equipment && !(int) $id_equipment_line) {
            $errors[] = 'Aucun équipement spécifié';
        } else {
            if ($check_equipment) {
                // Méthode via objet (interface utilisateur) 
                $line = null;
                if ((int) $id_equipment_line) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'ObjectLineEquipment', (int) $id_equipment_line);
                    if (!BimpObject::objectLoaded($line)) {
                        $errors[] = 'La ligne d\'équipement d\'ID ' . $id_equipment_line . ' n\'existe pas';
                        return $errors;
                    }
                }
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (!BimpObject::objectLoaded($equipment)) {
                    $errors[] = 'L\'équipement #' . $id_equipment . ' n\'existe pas';
                    return $errors;
                }

                if (is_null($line) || ((int) $line->getData('id_equipment') !== (int) $id_equipment)) {
                    $errors = $this->checkEquipment($equipment);

                    if (count($errors)) {
                        return $errors;
                    }
                }

                $lines = $this->getEquipmentLines();

                if (!count($lines)) {
                    $errors[] = 'Aucune ligne d\'équipement n\'est enregistrée pour ' . $this->getLabel('this');
                    return $errors;
                }

                foreach ($lines as $l) {
                    if (!(int) $l->getData('id_equipment')) {
                        $line = $l;
                        break;
                    }
                }

                if (is_null($line)) {
                    $errors[] = 'Il n\'y a aucune unité en attente d\'attribution d\'un équipement';
                } else {
                    if ((int) $line->getData('id_equipment') !== (int) $id_equipment) {
                        $errors = $line->setEquipment((int) $id_equipment, false);
                    }
                }
            } else {
                // Méthode simplifiée via requêtes SQL (attribution en série via PHP - on fait confiance à la validité des équipements) 

                if (!$id_equipment_line) {
                    $where = 'id_object_line = ' . (int) $this->id . ' AND object_type = \'' . static::$parent_comm_type . '\'';
                    $where .= ' AND (id_equipment IS NULL OR id_equipment = 0)';
                    $id_equipment_line = (int) $this->db->getValue('object_line_equipment', 'id', $where, 'id', 'asc');

                    if (!$id_equipment_line) {
                        $errors[] = 'Il n\'y a aucune unité en attente d\'attribution d\'un équipement';
                    }
                }

                if ($id_equipment_line) {
                    if ($this->db->update('object_line_equipment', array(
                                'id_equipment' => $id_equipment
                                    ), 'id = ' . (int) $id_equipment_line) <= 0) {
                        $errors[] = 'Echec de l\'attribution de l\'équipement - ' . $this->db->err();
                    }
                }
            }

            if (!count($errors)) {
                if ($recalc_line_pa) {
                    $this->calcPaByEquipments();
                }

                if (method_exists($this, 'onEquipmentAttributed')) {
                    $this->onEquipmentAttributed((int) $id_equipment);
                }
            }
        }

        return $errors;
    }

    public function calcPaByEquipments($update = true, $date_cur_pa = null, &$new_pa = null, &$default_cur_pa = null, &$details = array())
    {
        $errors = array();

        if ($this->isLoaded()) {
            $qty = abs((float) $this->getFullQty());

            if ($qty > 0) {
                $lines = $this->getEquipmentLines();

                $total_achats = 0;
                $nDone = 0;

                if (count($lines)) {
                    foreach ($lines as $line) {
                        $equipment = $line->getChildObject('equipment');
                        if (BimpObject::objectLoaded($equipment)) {
                            $eq_pa = (float) $equipment->getData('prix_achat');

                            if ($eq_pa) {
                                $total_achats += $eq_pa;
                                $nDone++;
                            }
                        }
                    }
                }

                $diff = $qty - $nDone;

                if ($nDone > 0) {
                    $details[] = 'PA réel équipements pour ' . $nDone . ' unité(s) - Moyenne: ' . BimpTools::displayMoneyValue($total_achats / $nDone);
                }

                if ($diff > 0) {
                    $cur_pa = null;
                    $cur_pa_amount = 0;
                    $prod = $this->getProduct();
                    if (BimpObject::objectLoaded($prod)) {
//                        $cur_pa = $prod->getCurrentPaHt(null, true, $date_cur_pa);
                        $cur_pa = $prod->getCurrentPaObject(true, $date_cur_pa);
                        if (BimpObject::objectLoaded($cur_pa)) {
                            $cur_pa_amount = (float) $cur_pa->getData('amount');
                            $default_cur_pa = $cur_pa;
                        }
                    }

                    if ((string) $date_cur_pa) {
                        $dt = new DateTime($date_cur_pa);
                        $details[] = 'PA courant du produit au ' . $dt->format('d / m / Y') . ': pour ' . $diff . ' unité(s) - ' . BimpTools::displayMoneyValue($cur_pa_amount);
                    } else {
                        $details[] = 'PA courant du produit pour ' . $diff . ' unité(s) : ' . BimpTools::displayMoneyValue($cur_pa_amount);
                    }

                    $total_achats += ($cur_pa_amount * $diff);
                }

                $new_pa = $total_achats / $qty;

                if ((float) $this->pu_ht < 0) {
                    $new_pa *= -1;
                }

                if ($update && $new_pa != (float) $this->pa_ht) {
                    $errors = $this->updatePrixAchat($new_pa);
                }
            }
        }

        return $errors;
    }

    public function updatePrixAchat($new_pa_ht)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if ((float) $new_pa_ht !== (float) $this->pa_ht) {
                $id_line = (int) $this->getData('id_line');
                if ($id_line) {
                    // Màj directe en base: 
                    if ($this->db->update(static::$dol_line_table, array(
                                'buy_price_ht' => (float) $new_pa_ht
                                    ), '`rowid` = ' . (int) $this->getData('id_line')) <= 0) {
                        $errors[] = 'Echec de la mise à jour du prix d\'achat - ' . $this->db->db->lasterror();
                    } else {
                        $this->pa_ht = $new_pa_ht;
                    }
                } else {
                    $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                }
            }
        }

        return $errors;
    }

    public function setEquipments($equipments, &$equipments_set = array(), $check_equipments = true)
    {
        $errors = array();
        $equipments_set = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
            return $errors;
        }

        $current_equipments = array();
        $new_equipments = array();

//        $line_equipments = $this->getEquipmentLines();
        $line_equipments = $this->getCurrentEquipmentsLinesData();

//        foreach ($line_equipments as $line_equipment) {
//            $id_equipment = (int) $line_equipment->getData('id_equipment');
//            if ($id_equipment) {
//                $current_equipments[] = $id_equipment;
//            }
//        }

        foreach ($line_equipments as $id_line_eq => $id_equipment) {
            if ((int) $id_equipment) {
                $current_equipments[(int) $id_line_eq] = (int) $id_equipment;
            }
        }

        foreach ($equipments as $equipment_data) {
            if (isset($equipment_data['id_equipment']) && (int) $equipment_data['id_equipment']) {
                $new_equipments[] = (int) $equipment_data['id_equipment'];
            }
        }

        $qty = abs($this->qty);

        if (count($new_equipments) > (int) $qty) {
            $errors[] = 'Le nombre d\'équipements (' . count($new_equipments) . ') dépasse le nombre d\'unités asssignées à cette facture (' . $qty . ')';
            return $errors;
        }

        // Equipements à supprimer:         
//        foreach ($line_equipments as $line_equipment) {
//            $id_equipment = (int) $line_equipment->getData('id_equipment');
//            if ($id_equipment && !in_array($id_equipment, $new_equipments)) {
//                $line_errors = $line_equipment->removeEquipment();
//                if (count($line_errors)) {
//                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
//                    if (BimpObject::objectLoaded($equipment)) {
//                        $eq_label = '"' . $equipment->getData('serial') . '"';
//                    } else {
//                        $eq_label = ' d\'ID ' . $id_equipment;
//                    }
//                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs lors de la désattribution de l\'équipement ' . $eq_label);
//                }
//            }
//        }

        $eq_lines_to_remove = array();
        foreach ($line_equipments as $id_line_eq => $id_equipment) {
            if ((int) $id_line_eq && (int) $id_equipment && !in_array($id_equipment, $new_equipments)) {
                $eq_lines_to_remove[] = (int) $id_line_eq;
                $line_equipments[(int) $id_line_eq] = 0;
                unset($current_equipments[(int) $id_line_eq]);
            }
        }

        if (count($eq_lines_to_remove)) {
            $this->db->update('object_line_equipment', array(
                'id_equipment' => 0
                    ), 'id IN (' . implode(',', $eq_lines_to_remove) . ')');
        }

        // Equipments à ajouter: 
        foreach ($equipments as $equipment_data) {
            if (isset($equipment_data['id_equipment'])) {
                if (!in_array((int) $equipment_data['id_equipment'], $current_equipments)) {
                    $id_line_eq_to_attribute = 0;
                    foreach ($line_equipments as $id_line_eq => $id_eq) {
                        if (!(int) $id_eq) {
                            $id_line_eq_to_attribute = (int) $id_line_eq;
                            break;
                        }
                    }

                    $eq_errors = $this->attributeEquipment($equipment_data['id_equipment'], $id_line_eq_to_attribute, false, $check_equipments);

                    if (count($eq_errors)) {
                        if ($check_equipments) {
                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $equipment_data['id_equipment']);
                            if (BimpObject::objectLoaded($equipment)) {
                                $label = '"' . $equipment->getData('serial') . '" (ID: ' . $equipment_data['id_equipment'] . ')';
                            } else {
                                $label = 'd\'ID ' . $equipment_data['id_equipment'];
                            }
                            $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Erreurs lors de l\'attribution de l\'équipement ' . $label);
                        } else {
                            $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Echec attribution équipement #' . $id_equipment);
                        }
                    } else {
                        if ($id_line_eq_to_attribute) {
                            $line_equipments[$id_line_eq_to_attribute] = (int) $equipment_data['id_equipment'];
                        }
                    }
                }
            }
        }

//        $line_equipments = $this->getEquipmentLines();
//        foreach ($line_equipments as $line_equipment) {
//            $equipments_set[] = (int) $line_equipment->getData('id_equipment');
//        }
        $line_equipments = $this->getCurrentEquipmentsLinesData();
        foreach ($line_equipments as $id_line_eq => $id_equipment) {
            $equipments_set[] = (int) $id_equipment;
        }

        $this->calcPaByEquipments();

        return $errors;
    }

    public function checkEquipmentsAttribution()
    {
        $errors = array();

        if ($this->equipment_required) {
            if ($this->isProductSerialisable()) {
                $equipment_lines = $this->getEquipmentLines();

                $qty_set = 0;
                foreach ($equipment_lines as $equipment_line) {
                    if ((int) $equipment_line->getData('id_equipment')) {
                        $qty_set++;
                    }
                }

                if ($qty_set < (int) $this->qty) {
                    $diff = (int) $this->qty - $qty_set;

                    if ($diff > 1) {
                        $errors[] = $diff . ' équipements n\'ont pas été attribués';
                    } else {
                        $errors[] = $diff . ' équipement n\'a pas été attribué';
                    }
                }
            }
        }

        return $errors;
    }

    public function removeEquipment($id_equipment)
    {
        $errors = array();

        $eq_line = $this->getEquipmentLine($id_equipment);

        if (BimpObject::objectLoaded($eq_line)) {
            $errors = $eq_line->removeEquipment();
        }

        return $errors;
    }

    // Gestion remises: 

    public function addRemise($value, $label = '', $type = 1, $per_unit = 0, &$warnings = array(), $force_create = false)
    {
        $errors = array();

        // Vérifications: 
        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
            return $errors;
        }

        if (!$this->isRemisable()) {
            $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas remisable';
        } elseif (!$this->isEditable($force_create)) {
            $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas éditable';
        } else {
            $parent = $this->getParentInstance();
            if (!BimpObject::ObjectLoaded($parent)) {
                if (is_a($parent, 'BimpComm')) {
                    $errors[] = 'ID ' . $parent->getLabel('of_the') . ' absent';
                } else {
                    $errors[] = 'objet parent absent';
                }
            } else {
                if (!$parent->areLinesEditable()) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est plus éditable';
                }
            }
        }

        if (!in_array((int) $type, array(1, 2))) {
            $errors[] = 'Type de la remise invalide';
        }

        if (count($errors)) {
            return $errors;
        }

        // Création de la remise: 
        $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');

        $remise->validateArray(array(
            'id_object_line' => (int) $this->id,
            'object_type'    => static::$parent_comm_type,
            'label'          => $label,
            'type'           => (int) $type,
            'percent'        => ((int) $type === 1 ? (float) $value : 0),
            'montant'        => ((int) $type === 2 ? (float) $value : 0),
            'per_unit'       => (int) $per_unit
        ));

        $errors = $remise->create($warnings, true);

        return $errors;
    }

    public function calcRemise()
    {
        $warnings = array();
        if ($this->isLoaded()) {
            $remises_infos = $this->getRemiseTotalInfos(true);

            if (is_null($this->remise) || (float) $this->remise !== (float) $remises_infos['total_percent'] ||
                    $remises_infos['total_percent'] !== (float) $this->getData('remise') ||
                    $remises_infos['total_percent'] !== (float) $this->getInitData('remise')) {

                $this->remise = (float) $remises_infos['total_percent'];
                $this->set('remise', (float) $remises_infos['total_percent']);

//                if($this->nbCalcremise < 90){
                $this->nbCalcremise++;
                $this->update($warnings, true);
//                }
            }
        }
    }

    public function checkRemises()
    {
        $errors = array();
        $remises = $this->getRemises();

        if (!$this->isRemisable()) {
            if (count($remises)) {
                foreach ($remises as $remise) {
                    $del_warnings = array();
                    $errors = BimpTools::merge_array($errors, $remise->delete($del_warnings, true));
                }
                unset($this->remises);
                $this->remises = null;
            }
            if ((float) $this->remise || (float) $this->getData('remise')) {
                $this->set('remise', 0);
                $this->remise = 0;
                $errors = BimpTools::merge_array($errors, $this->update());
            }
        } else {
//            $remise_infos = $this->getRemiseTotalInfos();
//            if ((float) $this->remise !== (float) $remise_infos['total_percent']) {
//                $this->remise = (float) $remise_infos['total_percent'];
//                $this->update();
//            }
            // Reprise de la remise depuis l'ancienne interface. *** désactivé => sinon conflit avec les remises globales *** 
//
//            if ((float) $this->remise !== (float) $remise_infos['total_percent']) {
//                $remise_percent = (float) $this->remise;
//
//                $remises = $this->getRemises();
//                foreach ($remises as $remise) {
//                    $del_warnings = array();
//                    $remise->delete($del_warnings, true);
//                }
//                unset($this->remises);
//                $this->remises = null;
//
//                $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise', null, $this);
//                $remise->validateArray(array(
//                    'id_object_line' => (int) $this->id,
//                    'object_type'    => static::$parent_comm_type,
//                    'type'           => ObjectLineRemise::OL_REMISE_PERCENT,
//                    'percent'        => (float) $remise_percent
//                ));
//                $remise_errors = $remise->create($warnings, true);
//                if (count($remise_errors)) {
//                    $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise de ' . $remise_percent . ' %');
//                }
//                $this->calcRemise();
//            }
        }
        return $errors;
    }

    public function setRemiseGlobalePart(RemiseGlobale $rg, $rate)
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->isRemisable()) {
            if (!BimpObject::objectLoaded($rg)) {
                $errors[] = 'ID de la remise globale absent';
            } else {
                $remise = BimpCache::findBimpObjectInstance('bimpcommercial', 'ObjectLineRemise', array(
                            'id_object_line'    => (int) $this->id,
                            'object_type'       => static::$parent_comm_type,
                            'id_remise_globale' => (int) $rg->id
                                ), true, true);

                $label = 'Part de la remise globale "' . $rg->getData('label') . '"';

                if (BimpObject::objectLoaded($remise)) {
                    if ($remise->getData('label') === $label &&
                            (int) $remise->getData('type') === ObjectLineRemise::OL_REMISE_PERCENT &&
                            (float) $remise->getData('percent') === (float) $rate) {
                        return array();
                    }

                    $err_label = 'mise à jour';
                } else {
                    $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                    $err_label = 'création';
                }

                $rem_errors = $remise->validateArray(array(
                    'id_object_line'    => (int) $this->id,
                    'object_type'       => static::$parent_comm_type,
                    'id_remise_globale' => (int) $rg->id,
                    'label'             => $label,
                    'type'              => ObjectLineRemise::OL_REMISE_PERCENT,
                    'percent'           => (float) $rate
                ));

                if (!count($rem_errors)) {
                    $rem_warnings = array();
                    if (BimpObject::objectLoaded($remise)) {
                        $rem_errors = $remise->update($rem_warnings, true);
                    } else {
                        $rem_errors = $remise->create($rem_warnings, true);
                    }
                }

                if (count($rem_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($rem_errors, 'Ligne n° ' . $this->getData('position') . ': échec de la ' . $err_label . ' de la remise');
                }
            }
        }

        return $errors;
    }

    public function checkRemisesGlobales($rgs = null)
    {
        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();

            if (BimpObject::objectLoaded($parent)) {
                if (is_null($rgs)) {
                    $remises_globales = $parent->getRemisesGlobales();

                    foreach ($remises_globales as $rg) {
                        $rgs[] = (int) $rg->id;
                    }
                }
            }

            $filters = array(
                'id_object_line'    => (int) $this->id,
                'object_type'       => static::$parent_comm_type,
                'id_remise_globale' => array(
                    'and' => array(
                        array(
                            'operator' => '>',
                            'value'    => 0
                        )
                    )
                )
            );

            if (!empty($rgs)) {
                $filters['id_remise_globale']['and'][] = array(
                    'not_in' => $rgs
                );
            }

            $remises_to_delete = BimpCache::getBimpObjectObjects('bimpcommercial', 'ObjectLineRemise', $filters);

            foreach ($remises_to_delete as $remise) {
                $remise->delete($warnings, true);
            }
        }
    }

    public function copyRemisesFromOrigin($origin, $inverse_prices = false, $copy_remises_globales = false)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (BimpObject::objectLoaded($origin) && is_a($origin, 'ObjectLine')) {
                $remises = $origin->getRemises();

                if (!empty($remises)) {
                    foreach ($remises as $remise) {
                        if (!$copy_remises_globales && ((int) $remise->getData('id_remise_globale') || (int) $remise->getData('linked_id_remise_globale'))) {
                            continue;
                        }

                        $data = $remise->getDataArray();
                        $data['id_object_line'] = (int) $this->id;
                        $data['object_type'] = static::$parent_comm_type;

//                        if ((int) $data['linked_id_remise_globale']) {
//                            $data['linked_id_remise_globale'] = 0;
//
//                            // On converti la remise en montant fixe.
//                            if ((float) $data['percent']) {
//                                $total_ttc = (float) $origin->getTotalTtcWithoutRemises(true);
//                                $data['montant'] = $total_ttc * ((float) $data['percent'] / 100);
//                                $data['percent'] = 0;
//                                $data['type'] = ObjectLineRemise::OL_REMISE_AMOUNT;
//                                $data['per_unit'] = 0;
//                            }
//                        }


                        if ((float) $data['montant'] != 0 && $inverse_prices) {
                            $data['montant'] = ((float) $data['montant'] * -1);
                        }

                        BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemise', $data, true, $errors, $errors);
                    }
                }
            }
        }

        return $errors;
    }

    public function copyRemisesArrieresFromOrigine($origin)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (BimpObject::objectLoaded($origin) && is_a($origin, 'ObjectLine')) {
                $remises = $origin->getRemisesArrieres();

                if (!empty($remises)) {
                    foreach ($remises as $remise) {
                        BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemiseArriere', array(
                            'id_object_line' => $this->id,
                            'object_type'    => static::$parent_comm_type,
                            'type'           => $remise->getData('type'),
                            'label'          => $remise->getData('label'),
                            'value'          => $remise->getData('value')
                                ), true, $errors);
                    }
                }
            }
        }

        return $errors;
    }

    // Traitements divers: 

    public function onSave(&$errors = [], &$warnings = [])
    {
        $crt_errors = $this->checkRemiseCRT();
        if (count($crt_errors)) {
            $errors[] = BimpTools::getMsgFromArray($crt_errors, 'Erreurs sur la remise CRT');
        }

        parent::onSave($errors, $warnings);
    }

    public function onChildSave($child)
    {
        if ($this->isDeleting) {
            return array();
        }

        if (!$this->isLoaded()) {
            $instance = self::getInstanceByParentType($child->getData('object_type'), (int) $child->getData('id_object_line'));
            if ($instance->isLoaded()) {
                return $instance->onChildSave($child);
            }
        }

        if (is_a($child, 'ObjectLineRemise')) {
            unset($this->remises);
            $this->remises = null;
            $this->calcRemise();
        } elseif (is_a($child, 'ObjectLineRemiseArriere')) {
            $remises = $this->getRemisesArrieres();
            $remise_pa = 0;

            foreach ($remises as $remise) {
                $remise_pa += $remise->getRemiseAmount();
            }

            $this->updateField('remise_pa', $remise_pa);

            $parent = $this->getParentInstance();
            if (BimpObject::objectLoaded($parent) && method_exists($parent, 'setRevalorisation')) {
                $parent->setRevalorisation();
            }
        }
        return array();
    }

    public function onChildDelete($child, $id_child)
    {
        if ($this->isDeleting) {
            return array();
        }

        if (!$this->isLoaded()) {
            $instance = self::getInstanceByParentType($child->getData('object_type'), (int) $child->getData('id_object_line'));
            if ($instance->isLoaded()) {
                return $instance->onChildDelete($child, $id_child);
            }
        }

        if (is_a($child, 'ObjectLineRemise')) {
            unset($this->remises);
            $this->remises = null;
            $this->calcRemise();
        } elseif (is_a($child, 'ObjectLineRemiseArriere')) {
            if ($child->getData('type') == 'crt' && $this->field_exists('remise_crt')) {
                $this->updateField('remise_crt', 0);
            }

            $remises = $this->getRemisesArrieres();
            $remise_pa = 0;

            foreach ($remises as $remise) {
                $remise_pa += $remise->getRemiseAmount();
            }

            $this->updateField('remise_pa', $remise_pa);
        }
        return array();
    }

    protected function setLinesPositions()
    {
        $errors = array();

        $parent = $this->getParentInstance();
        if (is_a($parent, 'BimpObject') && $parent->isDeleting) {
            return;
        }

        $table = $this::$dol_line_table;
        $primary = $this::$dol_line_primary;

        if (!$table) {
            $errors[] = 'table non définie';
        }

        if (!$primary) {
            $errors[] = 'Clé primaire non définie';
        }

        $parent = $this->getParentInstance();

        if (is_null($parent)) {
            $errors[] = 'Objet parent non défini';
        } elseif (!$parent->isLoaded()) {
            $errors[] = 'ID ' . $parent->getLabel('of_the') . ' absent';
        }

        if (!count($errors)) {
            $lines = $this->getList(array(
                'id_obj' => (int) $parent->id
                    ), null, null, 'position', 'asc', 'array', array(
                'id_line', 'position'
            ));

            $tabRang = array();
            $tabTemp = $this->db->executeS('SELECT ' . $primary . ' as id, rang FROM llx_' . $table . ' WHERE ' . $this::$dol_line_parent_field . ' = ' . $parent->id, 'array');

            if (is_array($tabTemp)) {
                foreach ($tabTemp as $lnTemp) {
                    $tabRang[$lnTemp['id']] = $lnTemp['rang'];
                }
            }

            if (!is_null($lines) && count($lines)) {
                foreach ($lines as $line) {
                    if (!isset($tabRang[$line['id_line']]) || $line['position'] != $tabRang[$line['id_line']]) {
                        if ($this->db->update($table, array(
                                    'rang' => (int) $line['position']
                                        ), '`' . $primary . '` = ' . (int) $line['id_line']) <= 0) {
                            $msg = 'Echec de la mise à jour de la position de la ligne d\'ID ' . $line['id_line'];
                            $sqlError = $this->db->db->lasterror();
                            if ($sqlError) {
                                $msg .= ' - ' . $sqlError;
                            }
                            $errors[] = $msg;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function checkPosition($position)
    {
        $parent = $this->getParentInstance();
        if (is_a($parent, 'BimpObject') && $parent->isDeleting) {
            return;
        }

        if ((int) $this->getData('id_parent_line')) {
            // Vérification de la nouvelle position de la ligne si elle est enfant d'une autre ligne.
            $parent_line = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $this->getData('id_parent_line'));
            if (BimpObject::objectLoaded($parent_line)) {
                $parent_position = (int) $parent_line->getData('position');
                if ($position <= $parent_position) {
                    $position = $parent_position + 1;
                } elseif ($position > ($parent_position + 1)) {
                    // on vérifie l'existance d'autres lignes enfants pour la même ligne parente: 
                    $rows = $this->getList(array(
                        'id_obj'         => (int) $this->getData('id_obj'),
                        'id_parent_line' => (int) $parent_line->id
                            ), null, null, 'position', 'asc', 'array', array('id', 'position'));

                    if (!is_null($rows)) {
                        $max_pos = $parent_position + count($rows);
                        if ($position > $max_pos) {
                            $position = $max_pos;
                        }
                    }
                }
            }
        } else {
            // Vérification que la nouvelle position ne sépare pas des lignes enfants de leur parent.
            $rows = $this->getList(array(
                'id_obj' => (int) $this->getData('id_obj')
                    ), null, null, 'position', 'asc', 'array', array('id', 'id_parent_line', 'position'));

            $init_pos = (int) $this->getInitData('position');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    if ((int) $r['id'] === (int) $this->id) {
                        continue;
                    }

                    $r_pos = (int) $r['position'];

                    if ($init_pos < $r_pos) {
                        $r_pos--;
                    }

                    if ((int) $r_pos === (int) $position) {
                        if ((int) $r['id_parent_line']) {
                            $position++;
                        }
                    }
                }
            }
        }
        return $position;
    }

    public function findValidPrixAchat($date = '')
    {
        if ($this->isLoaded() && (int) $this->getData('type') === self::LINE_PRODUCT) {
            $product = $this->getProduct();

            if (BimpObject::objectLoaded($product)) {
                $mult = ((float) $this->pu_ht < 0 ? -1 : 1);
                $pa_ht = 0;
                $origin = '';

                if ($product->isSerialisable()) {
                    $default_cur_pa = null;
                    $this->calcPaByEquipments(false, $date, $pa_ht, $default_cur_pa);
                    $origin = 'Prix d\'achat moyen des équipements';
                    if (BimpObject::objectLoaded($default_cur_pa)) {
                        $origin .= '<br/>Prix d\'achat par défaut: ' . $default_cur_pa->getNomUrl(0, 1, 0, 'default') . ' (' . BimpTools::displayMoneyValue($default_cur_pa->getData('amount')) . ')';
                    }
                } elseif ((int) $product->getData('no_fixe_prices')) {
                    $pa_ht = (float) $this->pa_ht;
                    $origin = 'Prix d\'achat enregistré dans la ligne';
                    $origin .= '<br/><span class="warning">(Le produit n\'a pas de prix d\'achat fixe)</span>';
                } else {
                    $cur_pa = $product->getCurrentPaObject(true, $date);
                    if (BimpObject::objectLoaded($cur_pa)) {
                        $pa_ht = (float) $cur_pa->getData('amount');
                        $origin = 'Prix d\'achat ' . $cur_pa->getNomUrl(0, 1, 0, 'default');
                    }
                    $pa_ht *= $mult;
                }

                return array(
                    'pa_ht'  => $pa_ht,
                    'origin' => $origin
                );
            }
        }

        return array(
            'pa_ht'  => (float) $this->pa_ht,
            'origin' => 'Prix d\'achat enregistré dans la ligne'
        );
    }

    public function checkRemiseCRT()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $remise_crt = (int) $this->getData('remise_crt');
            $ra = BimpCache::findBimpObjectInstance('bimpcommercial', 'ObjectLineRemiseArriere', array(
                        'id_object_line' => $this->id,
                        'object_type'    => static::$parent_comm_type,
                        'type'           => 'crt'
                            ), true);

            if ($remise_crt) {
                if (isset($this->no_remises_arrieres_auto_create) && $this->no_remises_arrieres_auto_create) {
                    return;
                }

                if (!BimpObject::objectLoaded($ra)) {
                    $product = $this->getProduct();
                    $prod_ra = null;
                    if (BimpObject::objectLoaded($product)) {
                        $prod_ra = $product->getRemiseArriere('crt');
                    }

                    if (BimpObject::objectLoaded($prod_ra)) {
                        BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemiseArriere', array(
                            'id_object_line' => $this->id,
                            'object_type'    => static::$parent_comm_type,
                            'type'           => 'crt',
                            'label'          => $prod_ra->getData('nom'),
                            'value'          => $prod_ra->getData('value')
                                ), true, $errors);
                    } else {
                        $errors[] = 'Il n\'y a pas de remise CRT pour ce produit - Remise CRT désactivée';
                        $this->updateField('remise_crt', 0);
                    }
                }
            } else {
                if (BimpObject::objectLoaded($ra)) {
                    $warnings = array();
                    $errors = $ra->delete($warnings, true);
                }
            }
        }

        return $errors;
    }

    // Rendus HTML: 

    public function renderLineInput($field, $attribute_equipment = false, $prefixe = '', $force_edit = false)
    {
        if ($this->getData('type') == self::LINE_SUB_TOTAL && !in_array($field, array('desc', 'hide_in_pdf'))) {
            return $this->displayLineData($field);
        }

        if (!$this->isFieldEditable($field, $force_edit)) {
            return $this->displayLineData($field);
        }

        $html = '';

        $value = null;

        $this->getIdProductFromPost();

        if (BimpTools::isSubmit('new_values/' . $this->id . '/' . $field))
            $value = BimpTools::getValue('new_values/' . $this->id . '/' . $field);
        elseif ($field === 'id_product') {
            $value = (int) $this->id_product;
        } elseif (in_array($field, array('pu_ht', 'tva_tx', 'id_fourn_price', 'pa_ht', 'remisable', 'desc'))) {
            $value = $this->getValueByProduct($field);
        } else {
            if (BimpTools::isSubmit($field)) {
                $value = BimpTools::getValue($field);
            } elseif (BimpTools::isSubmit('fields/' . $field)) {
                $value = BimpTools::getValue('fields/' . $field);
            } else {
                if (isset($this->{$field})) {
                    $value = $this->{$field};
                } elseif ($this->field_exists($field)) {
                    $value = $this->getData($field);
                }
            }
        }



        switch ($field) {
            case 'id_product':
                $html = BimpInput::renderInput('search_object', $prefixe . 'id_product', (int) $value, array(
                            'object'      => BimpObject::getInstance('bimpcore', 'Bimp_Product'),
                            'search_name' => static::$product_search_name,
//                            'card'        => 'default',
                            'help'        => 'Entrez la référence, le nom, ou le code-barre d\'un produit',
                            'max_results' => 500
                ));
                break;

            case 'id_fourn_price':
                $html .= '<span class="warning">Non sélectionnable</span>';
                break;

            case 'pa_ht':
                if (BimpObject::objectLoaded($this->post_equipment)) {
                    if ((float) $this->post_equipment->getData('prix_achat') > 0) {
                        $html .= 'Prix d\'achat équipement: ';
                        $html .= BimpTools::displayMoneyValue((float) $this->post_equipment->getData('prix_achat'), 'EUR') . '</strong>';
                        $html .= '<input type="hidden" name="' . $prefixe . 'pa_ht" value="' . (float) $this->post_equipment->getData('prix_achat') . '"/>';

                        break;
                    }
                }

                $is_pa_prevu = false;

                if (!(float) $value) {
                    $product = $this->getProduct();
                    if (BimpObject::objectLoaded($product) && !(int) $product->getData('validate')) {
                        $pa_prevu = (float) $product->getData('pa_prevu');
                        if ($pa_prevu) {
                            $value = $pa_prevu;
                            $is_pa_prevu = true;
                        }
                    }
                }

                if (!$attribute_equipment && $this->canEditPrixAchat() && $this->isEditable($force_edit)) {
                    $html .= BimpInput::renderInput('text', $prefixe . 'pa_ht', (float) $value, array(
                                'data'        => array(
                                    'data_type' => 'number',
                                    'decimals'  => 4,
                                    'min'       => 'none',
                                    'max'       => 'none'
                                ),
                                'addon_right' => '<i class="fa fa-' . BimpTools::getCurrencyIcon('EUR') . '"></i>'
                    ));
                } else {
                    $html .= 'Prix d\'achat actuel: ';
                    $html .= BimpTools::displayMoneyValue((float) $value);
                    if ($is_pa_prevu) {
                        $html .= ' (prévisionnel)';
                    }
                    $html .= '<input type="hidden" name="' . $prefixe . 'pa_ht" value="' . (float) $value . '"/>';
                }
                break;

            case 'desc':
                global $user;
                if ($user->rights->bimpcommercial->editHtml)
                    $html = BimpInput::renderInput('html', 'desc', (string) $value);
                else
                    $html = BimpInput::renderInput('textarea', 'desc', (string) $value);
                break;

            case 'qty':
//                if (!$force_edit && !$this->isFieldEditable('qty')) {
//                    return $value;
//                }

                $product_type = null;
                if ((int) $this->id_product) {
                    $product_type = (int) $this->db->getValue('product', 'fk_product_type', '`rowid` = ' . (int) $this->id_product);
                }

                if (is_null($value)) {
                    $value = 1;
                }

                if (BimpObject::objectLoaded($this->post_equipment)) {
                    $html .= '<input type="hidden" value="1" name="' . $prefixe . 'qty"/>';
                    $html .= '1';
                } else {
                    if ($product_type == Product::TYPE_SERVICE) {
                        $html = BimpInput::renderInput('qty', $prefixe . 'qty', (float) $value, array(
                                    'step' => 1,
                                    'data' => array(
                                        'data_type' => 'number',
                                        'min'       => 'none',
                                        'unsigned'  => 0,
                                        'decimals'  => 6
                                    )
                        ));
                    } else {
                        $min = $max = 'none';
                        $decimals = $this->getQtyDecimals();

                        if ($this->isLoaded()) {
                            if (method_exists($this, 'getMinQty')) {
                                $min = $this->getMinQty();
                            } else {
                                $equipment_lines = $this->getEquipmentLines();
                                if (count($equipment_lines)) {
                                    $min = 0;
                                    foreach ($equipment_lines as $line) {
                                        if ((int) $line->getData('id_equipment')) {
                                            $min++;
                                        }
                                    }
                                    if (!$min) {
                                        $min = 'none';
                                    }
                                }
                            }
                        }
                        $parent = $this->getParentInstance();
                        if (is_a($parent, 'Bimp_Facture') && $parent->getData('type') == 2 && $min != 'none') {
                            $max = -$min;
                            $min = 'none';
                        }

                        $html = BimpInput::renderInput('qty', $prefixe . 'qty', (int) $value, array(
                                    'data' => array(
                                        'data_type' => 'number',
                                        'min'       => $min,
                                        'max'       => $max,
                                        'unsigned'  => 0,
                                        'decimals'  => $decimals
                                    )
                        ));
                    }

                    if ($this->field_exists('force_qty_1') && (int) $this->getData('force_qty_1')) {
                        $html .= '<br/>';
                        $msg = 'L\'option "Forcer qté à 1" est activée. Une seule unité sera inscrite dans le PDF et le total de la ligne sera utilisé comme prix unitaire';
                        $html .= '<span class="warning bs-popover"' . BimpRender::renderPopoverData($msg) . '>(Forcée à 1)</span>';
                    }
                }

                break;

            case 'pu_ht':
                if (BimpObject::objectLoaded($this->post_equipment)) {
                    if ((float) $this->post_equipment->getData('prix_vente_except')) {
                        $product = $this->getProduct();
                        if (BimpObject::objectLoaded($product)) {
                            $value = '' . BimpTools::calculatePriceTaxEx((float) $this->post_equipment->getData('prix_vente_except'), (float) $product->getData('tva_tx'));
                        }
                    }
                }
                if (!$this->isEditable($force_edit) || $attribute_equipment || !$this->canEditPrixVente()) {
                    $html = '<input type="hidden" value="' . $value . '" name="' . $prefixe . 'pu_ht"/>';
                    $html .= BimpTools::displayMoneyValue($value, 'EUR');
                    if (!$this->isEditable()) {
                        $html .= ' <span class="inputInfo warning">(non modifiable)</span>';
                    }
                } else {
                    $options = array(
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 7
                        ),
                        'addon_right' => '<i class="fa fa-' . BimpTools::getCurrencyIcon('EUR') . '"></i>'
                    );

                    if (!is_a($this, 'FournObjectLine')) {
                        $options['values'] = $this->getProductPricesValuesArray();
                    }

                    $html = BimpInput::renderInput('text', $prefixe . 'pu_ht', (float) $value, $options);
                }
                break;

            case 'tva_tx':
                $parent = $this->getParentInstance();

                if (BimpObject::objectLoaded($parent) && !$parent->isTvaActive()) {
                    $html = '<input type="hidden" value="' . $value . '" name="' . $prefixe . 'tva_tx"/>';
                    $html .= ' <span class="inputInfo warning">Non applicable</span>';
                } elseif (!$this->isEditable($force_edit) || $attribute_equipment || !$this->canEditPrixVente()) {
                    $html = '<input type="hidden" value="' . $value . '" name="' . $prefixe . 'tva_tx"/>';
                    $html .= $value . ' %';
                    if (!$this->isEditable()) {
                        $html .= ' <span class="inputInfo warning">(non modifiable)</span>';
                    }
                } else {
                    if (static::$tva_free) {
                        $html = BimpInput::renderInput('text', $prefixe . 'tva_tx', (float) $value, array(
                                    'data'        => array(
                                        'data_type' => 'number',
                                        'decimals'  => 2,
                                        'min'       => 0,
                                        'max'       => 100
                                    ),
                                    'addon_right' => '<i class="fa fa-percent"></i>'
                        ));
                    } else {
                        $tva_rates = BimpCache::getTaxesByRates(1);
                        $html = BimpInput::renderInput('select', $prefixe . 'tva_tx', (float) $value, array(
                                    'options' => $tva_rates
                        ));
                    }
                }
                break;

            case 'remise':
                if ($this->isEditable($force_edit)) {
                    $html = BimpInput::renderInput('text', $prefixe . 'remise', (float) $value, array(
                                'data'        => array(
                                    'data_type' => 'number',
                                    'decimals'  => 2,
                                    'min'       => 0,
                                    'max'       => 100
                                ),
                                'addon_right' => '<i class="fa fa-percent"></i>'
                    ));
                } else {
                    $html = $value . ' % <span class="inputInfo warning">(non modifiable)</span>';
                }

                break;

            case 'date_from':
                $html = BimpInput::renderInput('date', $prefixe . 'date_from', (string) $value);
                break;

            case 'date_to':
                $html = BimpInput::renderInput('date', $prefixe . 'date_to', (string) $value);
                break;

            case 'force_qty_1':
                $value = $this->getData("force_qty_1");
                $html .= BimpInput::renderInput('toggle', 'force_qty_1', (int) $value);
                break;

            case 'remisable':
                if (!$this->isProductRemisable()) {
                    $html .= '<input type="hidden" value="0" name="' . $prefixe . 'remisable"/>';
                    $html .= '<span class="danger">NON</span>';
                    break;
                }

                $html .= BimpInput::renderInput('toggle', $prefixe . 'remisable', (int) $value);
                break;

            case 'remise_crt':
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    $remise_crt = (float) $product->getRemiseCrt();
                    if ($remise_crt) {
                        $html .= BimpInput::renderInput('toggle', $prefixe . 'remise_crt', (int) $value);
                        $html .= '<p class="inputHelp">';
                        $html .= 'Le montant de la remise CRT pourra être modifiée depuis la liste des remises arrières de cette ligne.<br/>Attention: toute Remise CRT erronée pourra donner lieu à un refus.';
                        $html .= '</p>';
                    } else {
                        $html .= '<input type="hidden" name="' . $prefixe . 'remise_crt" value="0"/>';
                        $html .= '<span class="warning">Non applicable</span>';
                    }
                } else {
                    $html .= '<input type="hidden" name="' . $prefixe . 'remise_crt" value="0"/>';
                    $html .= '<span class="warning">Attente sélection d\'un produit</span>';
                }
                break;

            case 'hide_in_pdf':
                $type = (int) $this->getData('type');
                $pu_ht = (float) BimpTools::getPostFieldValue('pu_ht', $this->pu_ht);
                $qty = (float) BimpTools::getPostFieldValue('qty', $this->qty);
                if ($this->field_exists('qty_modif')) {
                    $qty += (float) $this->getData('qty_modif');
                }
                if (in_array($type, array(self::LINE_TEXT, self::LINE_SUB_TOTAL)) || ($pu_ht * $qty) == 0) {
                    $html .= BimpInput::renderInput('toggle', $prefixe . 'hide_in_pdf', (int) $this->getData('hide_in_pdf'));
                } else {
                    $html .= '<span class="danger">NON</span>';
                    $html .= '<input type="hidden" value="0" name="' . $prefixe . 'hide_in_pdf' . '"/>';
                }
                break;
        }

        return $html;
    }

    public function renderLimitedInput()
    {
        $edit = 1;
        $value = (int) $this->isLimited();

        $product = $this->getProduct();

        $info = '';

        if (BimpObject::objectLoaded($product)) {
            if ((int) $product->getData('duree') > 0) {
                $edit = 0;
                $value = 1;

                $info = 'Ce ';
                if ($product->isTypeService()) {
                    $info .= 'service';
                } else {
                    $info .= 'produit';
                }
                $info .= ' a une durée limité de ' . $product->getData('duree') . ' mois.';
                $info .= '<br/>Laisser les champs "Du" / "Au" vides pour définir ces dates automatiquement à partir de la date de 1ère expédition';
            }
        }

        $html = '';

        if ($edit) {
            $html .= BimpInput::renderInput('toggle', 'limited', $value);
        } else {
            $html .= '<input type="hidden" name="limited" value="' . $value . '"/>';
            if ($value) {
                $html .= '<span class="success">OUI</span>';
            } else {
                $html .= '<span class="danger">NON</span>';
            }
        }

        if ($info) {
            $html .= BimpRender::renderAlerts($info, 'info');
        }

        return $html;
    }

    public function renderFormMargins()
    {
        $html = '';

        $parent = $this->getParentInstance();

        $id_line = 0;

        if ($this->isLoaded()) {
            $id_line = $this->id;
        } else {
            $id_line = BimpTools::getValue('id_object_line', 0);
        }

        $lines = array();
        if (BimpObject::objectLoaded($parent)) {
            $lines = $parent->getChildrenObjects('lines');
        }

        $line_pu = 0;
        $line_pa = 0;
        $line_remise = 0;
        $line_tva_tx = 0;
        $line_qty = 0;
        $is_line_remisable = 1;

        $total_vente = 0;
        $total_achat = 0;

        foreach ($lines as $line) {
            if ($id_line && (int) $line->id === (int) $id_line) {
                $line_pu = (float) $line->pu_ht;
                $line_pa = (float) $line->pa_ht;
                $line_remise = (float) $line->remise;
                $line_tva_tx = (float) $line->tva_tx;
                $line_qty = (float) $line->qty;
                $is_line_remisable = (int) $line->getData('remisable');
                continue;
            }
            $pu = (float) $line->pu_ht;
            if (!is_null($line->remise) && $line->remise > 0) {
                $pu -= ($pu * ((float) $line->remise / 100));
            }
            $total_vente += ($pu * (float) $line->qty);
            $total_achat += $line->pa_ht * (float) $line->qty;
        }

        if (BimpTools::isSubmit('line_pu_ht')) {
            $line_pu = (float) BimpTools::getValue('line_pu_ht');
        }

        if (BimpTools::isSubmit('line_pa_ht')) {
            $line_pa = BimpTools::getValue('line_pa_ht');
        } elseif (BimpTools::isSubmit('line_id_fourn_price')) {
            $fournPrice = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) BimpTools::getValue('line_id_fourn_price'));
            if (BimpObject::objectLoaded($fournPrice)) {
                $line_pa = (float) $fournPrice->getData('price');
            }
        }

        if (BimpTools::getValue('line_tva_tx')) {
            $line_tva_tx = (float) BimpTools::getValue('line_tva_tx');
        }

        if (BimpTools::isSubmit('line_qty')) {
            $line_qty = (float) BimpTools::getValue('line_qty');
        }

        if (BimpTools::isSubmit('line_remisable')) {
            $is_line_remisable = (int) BimpTools::getValue('line_remisable');
        }

        if ($is_line_remisable) {
            if (BimpTools::isSubmit('line_remise')) {
                $line_remise = (float) BimpTools::getValue('line_remise');
            } elseif (BimpTools::isSubmit('line_remises')) {
                $line_remise = 0;
                $remises = array();

                if ($id_line) {
                    $remise_instance = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                    $list_remises = $remise_instance->getList(array(
                        'id_object_line' => $id_line,
                        'object_type'    => static::$parent_comm_type
                            ), null, null, 'id', 'desc', 'array');

                    if (!is_null($list_remises)) {
                        foreach ($list_remises as $item) {
                            $remises[(int) $item['id']] = $item;
                        }
                    }
                }

                $new_remises = BimpTools::getValue('line_remises');

                foreach ($new_remises as $new_remise) {
                    if (isset($new_remise['id']) && (int) $new_remise['id']) {
                        $remises[(int) $new_remise['id']] = $new_remise;
                    } else {
                        $remises[] = $new_remise;
                    }
                }

                $total_remises = 0;
                BimpObject::loadClass('bimpcommercial', 'ObjectLineRemise');

                foreach ($remises as $remise) {
                    switch ((int) $remise['type']) {
                        case ObjectLineRemise::OL_REMISE_PERCENT:
                            $line_remise += (float) $remise['percent'];
                            break;

                        case ObjectLineRemise::OL_REMISE_AMOUNT:
                            if (isset($remise['remise_ht']) && (int) $remise['remise_ht']) {
                                $remise['montant'] = (float) BimpTools::calculatePriceTaxIn((float) $remise['montant'], $line_tva_tx);
                            }
                            if ((int) $remise['per_unit']) {
                                $total_remises += ((float) $remise['montant'] * (float) $line_qty);
                            } else {
                                $total_remises += (float) $remise['montant'];
                            }
                            break;
                    }
                }

                if ((float) $total_remises) {
                    $line_total_ttc = (float) BimpTools::calculatePriceTaxIn($line_pu, (float) $line_tva_tx) * (float) $line_qty;

                    if ($line_total_ttc) {
                        $line_remise += (float) (($total_remises / $line_total_ttc) * 100);
                    }
                }
            }
        } else {
            $line_remise = 0;
        }

        if ((float) $line_remise > 0) {
            $line_pu -= ($line_pu * ((float) $line_remise / 100));
        }

        $total_vente += ($line_pu * $line_qty);
        $total_achat += ($line_pa * $line_qty);

        $lineMargin = ($line_pu * $line_qty) - ($line_pa * $line_qty);
        $totalMargin = $total_vente - $total_achat;

        $lineMarginRate = '&infin;';
        $totalMarginRate = '&infin;';
        $tx_label = '';

        if ((int) BimpCore::getConf('use_tx_marque', 1, 'bimpcommercial')) {
            $tx_label = 'Taux de marque';
            if ($lineMargin && $line_pu && $line_qty) {
                $lineMarginRate = round(($lineMargin / ($line_pu * $line_qty)) * 100, 4) . ' %';
            }
            if ($total_vente) {
                $totalMarginRate = round(($totalMargin / $total_vente) * 100, 4) . ' %';
            }
        } else {
            $tx_label = 'Taux de marge';
            if ($lineMargin && $line_pa && $line_qty) {
                $lineMarginRate = round(($lineMargin / ($line_pa * $line_qty)) * 100, 4) . ' %';
            }
            if ($total_achat) {
                $totalMarginRate = round(($totalMargin / $total_achat) * 100, 4) . ' %';
            }
        }

        $html .= '<div class="formMarginsContainer">';
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th></th>';
        $html .= '<th>Marge</th>';
        $html .= '<th>' . $tx_label . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<th>Ligne</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($lineMargin, 'EUR') . '</td>';
        $html .= '<td>' . $lineMarginRate . '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';

        $html .= '<tfoot>';
        $html .= '<tr>';
        $html .= '<th>Total</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($totalMargin, 'EUR') . '</td>';
        $html .= '<td>' . $totalMarginRate . '</td>';
        $html .= '</tr>';
        $html .= '</tfoot>';

        $html .= '</table>';
        $html .= '</div>';

        if ($this->isLoaded() && $this->equipment_required) {
            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                $msg = 'Attention: produit sérialisable. Ce calcul de marges est basé sur les montants par défaut de la ligne ';
                $msg .= 'et ne tient pas compte des éventuels prix de vente et prix d\'achat exceptionnels des équipements attribués';
                $html .= BimpRender::renderAlerts($msg, 'warning');
            }
        }
        return $html;
    }

    public function renderQuickAddForm($bc_list)
    {
        if (!$this->isParentEditable()) {
            return '';
        }

        $parent = $this->getParentInstance();

        $html = '';

        $html .= '<div class="objectLineQuickAddForm singleLineForm" style="margin-top: 10px"';
        $html .= ' data-module="' . $this->module . '"';
        $html .= ' data-object_name="' . $this->object_name . '"';
        $html .= ' data-id_obj="' . $parent->id . '"';
        $html .= '>';

        $html .= '<div class="singleLineFormCaption">';
        $html .= '<h4>' . BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajout rapide</h4>';
        $html .= '</div>';

        $html .= '<div class="singleLineFormContent">';

        $content = '<label>Produit: </label>';
        $content .= $this->renderLineInput('id_product', false, 'quick_add_');
        $html .= BimpInput::renderInputContainer('quick_add_id_product', 0, $content, '', 1);

        $content = '<label>Qté: </label>';
        $content .= $this->renderLineInput('qty', false, 'quick_add_');
        $html .= BimpInput::renderInputContainer('quick_add_qty', 1, $content, '', 1);

        $remise = 0;

        if (BimpObject::objectLoaded($parent)) {
            $client = $parent->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                if ((float) $client->dol_object->remise_percent > 0) {
                    $remise = (float) $client->dol_object->remise_percent;
                }
            }
        }

        $content = '<label>Remise:&nbsp;</label>';
        $content .= BimpInput::renderInput('text', 'quick_add_default_remise', $remise, array(
                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                    'data'        => array(
                        'data_type' => 'number',
                        'min'       => 0,
                        'max'       => 100,
                        'decimals'  => 8
                    ),
                    'style'       => 'width: 80px'
        ));
        if ($remise > 0) {
            $content .= '<br/><span class="small">Remise client par défaut: ' . $remise . '%</span>';
        }
        $html .= BimpInput::renderInputContainer('quick_add_default_remise', $remise, $content, '', 0);

        $html .= '<button type="button" class="btn btn-primary" onclick="quickAddObjectLine($(this));">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
        $html .= '</button>';
        $html .= '<div class="quickAddForm_ajax_result"></div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderRemisesLists()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (!$this->isRemisable()) {
                $html .= '<h3>Remises sur le prix de vente</h3>';
                $html .= BimpRender::renderAlerts('Cette ligne n\'est pas remisable', 'warning');
            } else {
                $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                $remise->set('object_type', static::$parent_comm_type);
                $list = new BC_ListTable($remise, 'default', 1, $this->id, 'Remises sur le prix de vente', 'fas_percent');
                $list->addFieldFilterValue('object_type', static::$parent_comm_type);
                $html .= $list->renderHtml();
            }

            $remise_arr = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemiseArriere');
            $remise_arr->set('object_type', static::$parent_comm_type);
            $list = new BC_ListTable($remise_arr, 'default', 1, $this->id, 'Remises arrières sur le prix d\'achat', 'fas_percent');
            $list->addFieldFilterValue('object_type', static::$parent_comm_type);
            $html .= $list->renderHtml();
        }

        return $html;
    }

    // Actions: 

    public function actionAttributeEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Equipement attribué avec succès';

        if (!isset($data['id_equipment'])) {
            $errors[] = 'Veuillez sélectionner un équipement';
        } else {
            if (!count($errors)) {
                $id_line_equipment = (int) (isset($data['id_line_equipment']) ? $data['id_line_equipment'] : 0);
                if ($id_line_equipment) {
                    $success = 'Mise à jour de la ligne d\'équipement effectuée avec succès';
                }

                $errors = $this->attributeEquipment((int) $data['id_equipment'], $id_line_equipment);

                if (!count($errors)) {
                    if (method_exists($this, 'onEquipmentAttributed')) {
                        $this->onEquipmentAttributed((int) $data['id_equipment']);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddProductRA($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise arrière ajoutée avec succès';

        if ($this->isLoaded($errors)) {
            $id_prod_ra = (int) BimpTools::getArrayValueFromPath($data, 'id_product_ra', 0);
            if (!$id_prod_ra) {
                $errors[] = 'Aucune remise arrière sélectionnée';
            } else {
                $prod_ra = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductRA', $id_prod_ra);
                if (!BimpObject::objectLoaded($prod_ra)) {
                    $errors[] = 'La remise produit #' . $id_prod_ra . ' n\'existe pas';
                } else {
                    if ($prod_ra->getData('type') !== 'oth') {
                        $where = 'id_object_line = ' . (int) $this->id . ' AND object_type = \'' . static::$parent_comm_type . '\' AND type = \'' . $prod_ra->getData('type') . '\'';
                        $id = (int) $this->db->getValue('object_line_remise_arriere', 'id', $where);

                        if ($id) {
                            $errors[] = 'Une remise arrière de ce type (' . $prod_ra->displayData('type', 'default', 0) . ') a déjà été ajoutée pour cette ligne';
                        }
                    }

                    if (!count($errors)) {
                        if ($prod_ra->getData('type') == 'crt' && $this->field_exists('remise_crt')) {
                            $this->set('remise_crt', 1);
                        }

                        BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemiseArriere', array(
                            'id_object_line' => $this->id,
                            'object_type'    => static::$parent_comm_type,
                            'type'           => $prod_ra->getData('type'),
                            'label'          => $prod_ra->getData('nom'),
                            'value'          => $prod_ra->getData('value')
                                ), true, $errors, $warnings);

                        if (!count($errors)) {
                            if ($prod_ra->getData('type') == 'crt' && $this->field_exists('remise_crt')) {
                                $this->updateField('remise_crt', 1);
                            }
                        }
                    }
                }
            }
        }


        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpcommercial\', \'ObjectLineRemiseArriere\')'
        );
    }

    // Overrides: 

    public function resetPositions()
    {
        if ($this->getConf('positions', false, false, 'bool') && !BimpComm::$dont_check_parent_on_update) {
            $filters = array();
            $parent_id_property = $this->getParentIdProperty();
            if (is_null($parent_id_property)) {
                return;
            }
            $id_parent = $this->getData($parent_id_property);
            if (is_null($id_parent) || !$id_parent) {
                return;
            }
            $filters[$parent_id_property] = $id_parent;

            $table = $this->getTable();
            $primary = $this->getPrimary();

            $items = $this->getList($filters, null, null, 'position', 'asc', 'array', array($primary, 'position'));
            $i = 1;
            $done = array();
            foreach ($items as $item) {
                if (in_array((int) $item[$primary], $done)) {
                    continue;
                }
                if ((int) $item['position'] !== (int) $i) {
                    $this->db->update($table, array(
                        'position' => (int) $i
                            ), '`' . $primary . '` = ' . (int) $item[$primary]);
                }
                $done[] = (int) $item[$primary];
                $i++;

                $children = $this->getList(array(
                    'id_obj'         => (int) $id_parent,
                    'id_parent_line' => (int) $item[$primary]
                        ), null, null, 'position', 'asc', 'array', array('id', 'position'));
                if (!is_null($children)) {
                    foreach ($children as $child) {
                        if ((int) $child['position'] !== $i) {
                            $this->db->update($table, array(
                                'position' => (int) $i
                                    ), '`' . $primary . '` = ' . (int) $child['id']);
                        }
                        $done[] = (int) $child['id'];
                        $i++;
                    }
                }
            }
            $this->setLinesPositions();
        }
    }

    public function setPosition($position, &$errors = array())
    {
        $check = true;

        $position = (int) $this->checkPosition($position);

        if (!isset($this->id) || !(int) $this->id) {
            $check = false;
        } elseif ($this->getConf('positions', false, false, 'bool') && !BimpComm::$dont_check_parent_on_update) {
            $filters = array();
            $parent_id_property = $this->getParentIdProperty();
            if (is_null($parent_id_property)) {
                $check = false;
            } else {
                $id_parent = $this->getData($parent_id_property);
                if (is_null($id_parent) || !$id_parent) {
                    $check = false;
                } else {
                    $filters[$parent_id_property] = $id_parent;
                    $table = $this->getTable();
                    $primary = $this->getPrimary();

                    $items = $this->getList($filters, null, null, 'position', 'asc', 'array', array($primary, 'position', 'id_parent_line'));

                    $i = 1;
                    $done = array();

                    foreach ($items as $item) {
                        if ($i === $position) {
                            // Attribution de la nouvelle position à la ligne en cours: 
                            if (!in_array($this->id, $done)) {
                                $this->db->update($table, array(
                                    'position' => (int) $position
                                        ), '`' . $primary . '` = ' . (int) $this->id);
                                $this->set('position', $position);
                                $i++;
                                $done[] = $this->id;

                                if (!(int) $this->getData('id_parent_line')) {
                                    // Attribution des positions suivantes aux enfants de cette ligne:
                                    $children = $this->getList(array(
                                        'id_obj'         => (int) $id_parent,
                                        'id_parent_line' => (int) $this->id
                                            ), null, null, 'position', 'asc', 'array', array('id', 'position'));
                                    if (!is_null($children)) {
                                        foreach ($children as $child) {
                                            if ((int) $child['position'] !== $i) {
                                                $this->db->update($table, array(
                                                    'position' => (int) $i
                                                        ), '`' . $primary . '` = ' . (int) $child['id']);
                                            }
                                            $done[] = (int) $child['id'];
                                            $i++;
                                        }
                                    }
                                }
                            }
                        }

                        if ((int) $item[$primary] === (int) $this->id) {
                            continue;
                        }

                        if (in_array($item[$primary], $done)) {
                            continue;
                        }

                        if ((int) $item['id_parent_line']) {
                            $position--;
                            continue;
                        }

                        // Attribution de la position courante à la ligne courante: 
                        if ((int) $item['position'] !== (int) $i) {
                            $this->db->update($table, array(
                                'position' => (int) $i
                                    ), '`' . $primary . '` = ' . (int) $item[$primary]);
                        }
                        $done[] = $item[$primary];
                        $i++;

                        // Attribution des positions suivantes aux enfants de cette ligne:
                        $children = $this->getList(array(
                            'id_obj'         => (int) $id_parent,
                            'id_parent_line' => (int) $item[$primary]
                                ), null, null, 'position', 'asc', 'array', array('id', 'position'));
                        if (!is_null($children)) {
                            foreach ($children as $child) {
                                if ($i === $position) {
                                    if ((int) $this->getData('id_parent_line') === (int) $item[$primary]) {
                                        $this->db->update($table, array(
                                            'position' => (int) $position
                                                ), '`' . $primary . '` = ' . (int) $this->id);
                                        $i++;
                                        $done[] = $this->id;
                                    } else {
                                        $position++;
                                    }
                                }
                                if (!in_array((int) $child['id'], $done) && (int) $child['id'] !== $this->id) {
                                    if ((int) $child['position'] !== $i) {
                                        $this->db->update($table, array(
                                            'position' => (int) $i
                                                ), '`' . $primary . '` = ' . (int) $child['id']);
                                    }
                                    $done[] = (int) $child['id'];
                                    $i++;
                                }
                            }
                        }
                    }

                    if (!in_array($this->id, $done)) {
                        $this->db->update($table, array(
                            'position' => (int) $position
                                ), '`' . $primary . '` = ' . (int) $this->id);
                        $this->set('position', $position);
                    }
                }
            }
        } else {
            $check = false;
        }

        $this->setLinesPositions();

        return $check;
    }

    public function getNextPosition()
    {
        if ($this->getConf('positions', false, false, 'bool') && !BimpComm::$dont_check_parent_on_update) {
            $filters = array();

            $parent_id_property = $this->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                $id_parent = $this->getData($parent_id_property);
                if (is_null($id_parent) || !$id_parent) {
                    return 0;
                }
                $filters[$parent_id_property] = $id_parent;
            }

            $sql = 'SELECT MAX(`position`) as max_pos';
            $sql .= BimpTools::getSqlFrom($this->getTable());
            $sql .= BimpTools::getSqlWhere($filters);

            $result = $this->db->executeS($sql, 'array');

            if (!is_null($result)) {
                return (int) ((int) $result[0]['max_pos'] + 1);
            }
        }
        return 1;
    }

    public function reset()
    {
        BimpCache::unsetDolObjectInstance((int) $this->getData('id_line'), 'comm/propal', 'propal', 'PropaleLigne');

        $this->id_product = null;
        $this->id_fourn_price = null;
        $this->desc = null;
        $this->qty = 1;
        $this->pu_ht = null;
        $this->pa_ht = null;
        $this->tva_tx = null;
        $this->date_from = null;
        $this->date_to = null;
        $this->id_remise_except = null;

        if (!is_null($this->product)) {
            unset($this->product);
            $this->product = null;
        }

        $this->remise = null;

        if (!is_null($this->remises)) {
            unset($this->remises);
            $this->remises = null;
        }

        parent::reset();
    }

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (!(int) $this->getData('type') && BimpTools::isSubmit('id_product')) {
            $this->set('type', 1);
        }

        if (!count($errors)) {
            $data = null;

            switch ($this->getData('type')) {
                case self::LINE_PRODUCT:
                case self::LINE_FREE:
                    $data = static::$product_line_data;
                    break;

                case self::LINE_TEXT:
                case self::LINE_SUB_TOTAL:
                    $data = static::$text_line_data;
                    break;
            }

            if (is_array($data)) {
                foreach ($data as $field => $params) {
                    if (BimpTools::isSubmit($field)) {
                        $this->{$field} = BimpTools::getValue($field);
                    } elseif (is_null($this->{$field}) && isset($params['default'])) {
                        $this->{$field} = $params['default'];
                    }

                    if (!is_null($this->{$field})) {
                        if (!BimpTools::checkValueByType($params['type'], $this->{$field})) {
                            $errors[] = 'Valeur invalide: "' . $field . '"';
                        }
                    }
                }
            } else {
                $errors[] = 'Type invalide';
            }
        }

        return $errors;
    }

    public function validate()
    {
        if (!(int) $this->getData('type') && (int) $this->id_product) {
            $this->set('type', 1);
        }

        $errors = parent::validate();

        if (!count($errors)) {
            switch ($this->getData('type')) {
                case self::LINE_SUB_TOTAL:
                    if (empty($this->desc)) {
                        $this->desc = 'Sous-total';
                    }

                case self::LINE_TEXT:
                    $this->id_product = null;
                    $this->id_fourn_price = null;
                    $this->tva_tx = null;
                    $this->qty = null;
                    $this->pa_ht = null;
                    $this->remise = null;
                    $this->date_to = null;
                    $this->date_from = null;
                    if (BimpObject::objectLoaded($this->post_equipment)) {
                        unset($this->post_equipment);
                        $this->post_equipment = null;
                    }
                    $this->set('remise_crt', 0);
                    $this->set('remise_pa', 0);
                    $this->set('remisable', 0);
                    break;

                case self::LINE_PRODUCT:
                    if (is_null($this->id_product) || !$this->id_product) {
                        $errors[] = 'Produit ou service obligatoire';
                    } else {
                        $product = $this->getProduct();

                        if (!BimpObject::objectLoaded($product)) {
                            $errors[] = 'Le produit d\'ID ' . $this->id_product . ' n\'existe pas';
                        } else {
                            // Décimales autorisées dans les factures pour permettre les facturations périodiques
                            if ((int) $product->getData('fk_product_type') === 0 && $this->object_name !== 'Bimp_FactureLine') {
                                $qty_str = (string) $this->qty;

                                if (preg_match('/.*\..*/', $qty_str)) {
                                    $errors[] = 'Les quantités décimales ne sont autorisées que pour les produits de type "Service".';
                                }
                            }
                        }

                        if (!count($errors)) {
                            if (is_null($this->pu_ht)) {
                                $this->pu_ht = (float) $this->getValueByProduct('pu_ht');
                            }
                            if (is_null($this->tva_tx)) {
                                $this->tva_tx = (float) $this->getValueByProduct('tva_tx');
                            }

                            // On ne se base plus sur le id_fourn_price pour le pa_ht
                            $this->id_fourn_price = 0;

                            if (is_null($this->pa_ht)) {
                                $this->pa_ht = (float) $this->getValueByProduct('pa_ht');

                                if (!$this->pa_ht && !(int) $product->getData('validate') && (float) $product->getData('pa_prevu')) {
                                    $this->pa_ht = (float) $product->getData('pa_prevu');
                                }
                            }

                            if (is_null($this->desc) || !(string) $this->desc) {
                                $this->desc = $this->getValueByProduct('desc');
                            }

                            $product = $this->getProduct();

                            if ((int) $this->getData('remisable')) {
                                if (!(int) $product->getData('remisable')) {
                                    $this->set('remisable', 0);
                                }
                            }
                        }
                    }

                case self::LINE_FREE:
                    if (is_null($this->pu_ht)) {
                        $this->pu_ht = 0;
                    }
                    if (is_null($this->tva_tx)) {
                        $this->tva_tx = 0;
                    }
                    if (is_null($this->id_fourn_price)) {
                        $this->id_fourn_price = 0;
                    }
                    if (is_null($this->pa_ht)) {
                        $this->pa_ht = 0;
                    }

                    if (BimpObject::objectLoaded($this->post_equipment)) {
                        $errors = $this->checkEquipment($this->post_equipment);
                        if (count($errors)) {
                            return $errors;
                        }
                    }

                    if (!is_null($this->id_fourn_price) && (int) $this->id_fourn_price) {
                        $fournPrice = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $this->id_fourn_price);
                        if (BimpObject::objectLoaded($fournPrice)) {
                            $this->pa_ht = (float) $fournPrice->getData('price');
                        } else {
                            $this->id_fourn_price = 0;
                        }
                    }

                    if (BimpObject::objectLoaded($this->post_equipment)) {
                        if ((float) $this->post_equipment->getData('prix_achat') > 0) {
                            $this->pa_ht = (float) $this->post_equipment->getData('prix_achat');
                            $this->id_fourn_price = 0;
                        }
                    }

                    // Pas de TVA si vente hors UE: 
                    $parent = $this->getParentInstance();
                    if (BimpObject::objectLoaded($parent) && !$parent->isTvaActive()) {
                        $this->tva_tx = 0;
                    }

                    if ((!is_null($this->date_from) && $this->date_from) || (!is_null($this->date_to) && $this->date_to)) {
                        $date_check = true;
                        if (is_null($this->date_from) || !(string) $this->date_from) {
                            $errors[] = 'Date de début non spécifiée';
                            $date_check = false;
                        } elseif (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string) $this->date_from)) {
                            $errors[] = 'Date de début invalide';
                            $date_check = false;
                        }

                        if (is_null($this->date_to) || !(string) $this->date_to) {
                            $errors[] = 'Date de fin non spécifiée';
                            $date_check = false;
                        } elseif (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string) $this->date_to)) {
                            $errors[] = 'Date de fin invalide';
                            $date_check = false;
                        }

                        if ($date_check) {
                            if ($this->date_from > $this->date_to) {
                                $errors[] = 'La date de début doit être inférieure à la date de fin';
                            }
                        }
                    }

                    if ((float) $this->pu_ht < 0 && $this->pa_ht > 0) {
                        $this->pa_ht *= -1;
                    }

                    foreach (static::$product_line_data as $field => $params) {
                        if ($this->field_exists('def_' . $field)) {
                            $this->set('def_' . $field, $this->{$field});
                        }
                    }

                    if ($this->field_exists('hide_in_pdf')) {
                        if ($this->pu_ht * $this->getFullQty() != 0) {
                            $this->set('hide_in_pdf', 0);
                        }
                    }
                    break;
            }

            if ($this->force_pa_ht > 0)
                $this->pa_ht = $this->force_pa_ht;
        }
        return $errors;
    }

    public function checkObject($context = '', $field = '')
    {
        if (in_array($context, array('create', 'fetch')) && (int) $this->id_remise_except && (float) $this->qty) {
            $parent = $this->getParentInstance();
            if (BimpObject::objectLoaded($parent) && (int) $parent->getData('fk_statut') === 0) {
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch((int) $this->id_remise_except);

                $id_facture = 0;
                if ((int) $discount->discount_type === 0) {
                    // discount client:
                    if ((int) $discount->fk_facture) {
                        $id_facture = (int) $discount->fk_facture;
                    } elseif ((int) $discount->fk_facture_line) {
                        $id_facture = (int) $this->db->getValue('facturedet', 'fk_facture', '`rowid` = ' . (int) $discount->fk_facture_line);
                    }
                    if ($id_facture > 0) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            if ($parent->object_name !== 'Bimp_Facture' || ($parent->object_name === 'Bimp_Facture' && (int) $id_facture !== (int) $parent->id)) {
                                $this->qty = 0;
                                $this->desc .= ($this->desc ? '<br/>' : '') . '<span class="danger">Remise déjà consommée dans la facture ' . $facture->getNomUrl(1, 1, 1, 'full') . '</span>';
                                $warnings = array();
                                $this->update($warnings, true);
                            }
                        }
                    }
                } else {
                    // discount fournisseur: 
                    if ((int) $discount->fk_invoice_supplier) {
                        $id_facture = (int) $discount->fk_invoice_supplier;
                    } elseif ((int) $discount->fk_invoice_supplier_line) {
                        $id_facture = (int) $this->db->getValue('facture_fourn_det', 'fk_facture_fourn', '`rowid` = ' . (int) $discount->fk_invoice_supplier_line);
                    }
                    if ($id_facture > 0) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            if ($parent->object_name !== 'Bimp_FactureFourn' || ($parent->object_name === 'Bimp_FactureFourn' && (int) $id_facture !== (int) $parent->id)) {
                                $this->qty = 0;
                                $this->desc .= ($this->desc ? '<br/>' : '') . '<span class="danger">Remise déjà consommée dans la facture fournisseur ' . $facture->getNomUrl(1, 1, 1, 'full') . '</span>';
                                $warnings = array();
                                $this->update($warnings, true);
                            }
                        }
                    }
                }
            }
        }
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();
        if (!static::$parent_comm_type) {
            $errors[] = 'Impossible de créer une ligne depuis une instance de la classe de base "ObjectLine"';
            return $errors;
        }

        if (!$this->isCreatable($force_create)) {
            return array('Création de la ligne impossible');
        }


        if ((int) $this->getData('id_line')) {
            $id_bimp_line = (int) $this->db->getValue($this->getTable(), 'id', 'id_line = ' . (int) $this->getData('id_line'));

            if ($id_bimp_line) {
                $this->fetch($id_bimp_line);
                BimpCore::addlog('Tentative de création ' . $this->getLabel('of_a') . ' existant déjà', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', $this, array(
                    'context'                  => 'create()',
                    'ID ' . $this->object_name => $id_bimp_line,
                    'ID ligne dolibarr'        => (int) $this->getData('id_line'),
                ));
                return array();
            }
        }

        $equipment = null;
        $id_equipment = 0;

        if (!$this->no_equipment_post) {
            $id_equipment = (int) BimpTools::getValue('id_equipment', 0);
            if ($id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (!BimpObject::objectLoaded($equipment)) {
                    $errors[] = 'Equipement invalide';
                    unset($equipment);
                    $equipment = null;
                } else {
                    $errors = $this->checkEquipment($equipment);
                }
            }
        }

        $parent = $this->getParentInstance();

        // Forçage de la création: 
        $prev_parent_status = null;
        if ($force_create) {
            if (BimpObject::objectLoaded($parent)) {
                if ((int) $parent->getData('fk_statut') !== 0) {
                    $prev_parent_status = (int) $parent->getData('fk_statut');
                    $parent->dol_object->statut = 0;
                    $parent->dol_object->brouillon = 1;
                }
            }
        }


        if (!count($errors)) {
//            $this->db->db->begin();
            $errors = parent::create($warnings, $force_create);
        }

        if (!count($errors)) {
            $errors = $this->createLine(false);
            if (count($errors)) {
                $del_warnings = array();
                $this->delete($del_warnings, true);
//                $this->db->db->rollback();
            } else {
//                $this->db->db->commit();
                if ($this->equipment_required) {
                    $warnings = BimpTools::merge_array($warnings, $this->createEquipmentsLines());

                    if (!is_null($equipment)) {
                        $equipment_errors = $this->attributeEquipment((int) $equipment->id);
                        if (count($equipment_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($equipment_errors, 'Echec de l\'attribution de l\'équipement');
                        }
                    }
                }

                // Ajout remise: 
                if (BimpTools::isSubmit('default_remise')) {
                    $remise_value = (float) BimpTools::getValue('default_remise', 0);
                    if ($remise_value) {
                        if ($this->isRemisable()) {
                            $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                            $remise->validateArray(array(
                                'id_object_line' => (int) $this->id,
                                'object_type'    => $this->getParentCommType(),
                                'label'          => '',
                                'type'           => 1,
                                'percent'        => $remise_value
                            ));
                            $remise_errors = $remise->create();
                            if (count($remise_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise');
                            }
                        } else {
                            $warnings[] = 'ATTENTION: ce produit n\'étant pas remisable, la remise de ' . $remise_value . '% n\'a pas été prise en compte';
                        }
                    }
                }
//                if (BimpObject::objectLoaded($parent)) {
//                    $parent->resetLines();
//                }
                // Ajout Remises arrières: 
                if (!isset($this->no_remises_arrieres_auto_create) || !$this->no_remises_arrieres_auto_create) {
                    $product = $this->getProduct();
                    if (BimpObject::objectLoaded($product)) {
                        $prod_ras = $product->getChildrenObjects('remises_arrieres', array(
                            'active' => 1
                        ));

                        foreach ($prod_ras as $prod_ra) {
                            $ra_type = $prod_ra->getData('type');
                            if ($ra_type == 'crt' && ($this->field_exists('remise_crt') && !(int) $this->getData('remise_crt'))) {
                                continue;
                            }

                            if (!in_array($ra_type, Bimp_ProductRA::$auto_add_types)) {
                                continue;
                            }

                            // Vérif de l'exsitance de la remise arrière: 
                            if ($ra_type != 'oth') {
                                $line_ra = BimpCache::findBimpObjectInstance('bimpcommercial', 'ObjectLineRemiseArriere', array(
                                            'id_object_line' => $this->id,
                                            'object_type'    => static::$parent_comm_type,
                                            'type'           => $ra_type
                                                ), true);

                                if (BimpObject::objectLoaded($line_ra)) {
                                    continue;
                                }
                            }

                            $ra_errors = array();
                            BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemiseArriere', array(
                                'id_object_line' => $this->id,
                                'object_type'    => static::$parent_comm_type,
                                'type'           => $ra_type,
                                'label'          => $prod_ra->getData('nom'),
                                'value'          => $prod_ra->getData('value')
                                    ), true, $ra_errors);

                            if (count($ra_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($ra_errors, 'Echec de l\'ajout de la remise arrière "' . $prod_ra->getData('nom') . '"');
                            }
                        }
                    }
                }
            }
        }

        if (!is_null($prev_parent_status)) {
            $parent->dol_object->statut = $prev_parent_status;
            $parent->dol_object->brouillon = 0;
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();
        if (!static::$parent_comm_type) {
            $errors[] = 'Impossible de mettre à jour une ligne depuis une instance de la classe de base "ObjectLine"';
            return $errors;
        }

        $parent = $this->getParentInstance();

        if (!BimpObject::objectLoaded($parent)) {
            return array('Mise à jour de la ligne impossible (objet parent invalide)');
        }

        if (!$this->isEditable(true)) {
            return array(BimpTools::ucfirst($parent->getLabel('the')) . ' n\'est pas éditable');
        }

        // (Mise à jour possible pour certains champs sans màj de la dolLine) 
        $isParentEditable = ($force_update || $this->isParentEditable());

        $line = $this->getChildObject('line');

        if ($isParentEditable) {
            if ((int) $this->id_product) {
                $product = $this->getProduct();
                if ($this->equipment_required && BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                    if (!BimpObject::objectLoaded($line)) {
                        return array('ID de la ligne correspondante absent');
                    }

                    $sql = 'SELECT COUNT(`id`) as nRows FROM ' . MAIN_DB_PREFIX . 'object_line_equipment ';
                    $sql .= 'WHERE `id_object_line` = ' . (int) $this->id . ' AND `object_type` = \'' . static::$parent_comm_type . '\'';

                    $result = $this->db->executeS($sql);

                    $prev_qty = 0;

                    if (!is_null($result) && isset($result[0]->nRows)) {
                        $prev_qty = (int) $result[0]->nRows;
                    }

                    // Vérification de la quantité des lignes d'équipement et création/suppression si nécessaire: 
                    $eq_lines = $this->getEquipmentLines();
                    $eq_qty = abs($this->qty);
                    if ((int) $eq_qty !== $prev_qty) {
                        $diff = $eq_qty - $prev_qty;

                        if ($diff > 0) {
                            $this->createEquipmentsLines($diff);
                        } else {
                            $deletable = 0;
                            $diff = -$diff;
                            foreach ($eq_lines as $eq_line) {
                                if (!(int) $eq_line->getData('id_equipment')) {
                                    $deletable++;
                                }
                            }

                            if ($deletable < $diff) {
                                $errors[] = 'Quantité minimum: ' . ($line->qty - $deletable);
                            } else {
                                foreach ($eq_lines as $eq_line) {
                                    if ($diff <= 0) {
                                        break;
                                    }
                                    if (!(int) $eq_line->getData('id_equipment')) {
                                        $del_warnings = array();
                                        $eq_line->delete($del_warnings, true);
                                        $diff--;
                                    }
                                }
                            }
                        }
                    }

                    if (count($errors)) {
                        return $errors;
                    }
                }
            }

            if (!$this->isRemisable()) {
                $remises = $this->getRemises();

                if (is_array($remises)) {
                    foreach ($remises as $remise) {
                        $del_warnings = array();
                        $remise->delete($del_warnings, true);
                    }
                }

                unset($this->remises);
                $this->remises = null;
            }

            $initial_remise = (float) $this->getData('remise');
            $this->remise = $initial_remise;

            // Forçage de la mise à jour: 
            $prev_parent_status = null;
            if ($force_update) {
                if (BimpObject::objectLoaded($parent)) {
                    if ((int) $parent->getData('fk_statut') !== 0) {
                        $prev_parent_status = (int) $parent->getData('fk_statut');
                        $parent->dol_object->statut = 0;
                        $parent->dol_object->brouillon = 1;
                    }
                }
            }
        }

        $errors = BimpTools::merge_array($errors, parent::update($warnings, $force_update));

        if (!$isParentEditable) {
            if ((int) $this->getData('id_line')) {
                if ($this->db->update(static::$dol_line_table, array(
                            'description' => (string) $this->desc,
                            'date_start'  => (string) $this->date_from,
                            'date_end'    => (string) $this->date_to
                                ), '`' . static::$dol_line_primary . '` = ' . (int) $this->getData('id_line')) <= 0) {
                    echo $this->db->db->lasterror() . '<br/>';
                }
            }
        } else {
            $errors = BimpTools::merge_array($errors, $this->updateLine(false));

            if (!is_null($prev_parent_status)) {
                $parent->dol_object->statut = $prev_parent_status;
                $parent->dol_object->brouillon = 0;
            }

            if (!count($errors)) {
                $remises = $this->getRemiseTotalInfos(true);
                if ($initial_remise !== (float) $remises['total_percent']) {
                    $this->remise = (float) $remises['total_percent'];
                    $this->set('remise', (float) $remises['total_percent']);
                    $new_warnings = array();
                    $errors = $this->update($new_warnings, $force_update);
                }
            }
        }

        return $errors;
    }

    public function updateField($field, $value, $id_object = null, $force_update = true, $do_not_validate = false)
    {
        $errors = parent::updateField($field, $value, $id_object, $force_update, $do_not_validate);

        if (!count($errors)) {
            if ($field === 'remisable' && !(int) $value) {
                $instance = $this;
                if (!$this->isLoaded()) {
                    $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_object);
                }

                if ($instance->isLoaded()) {
                    $remises = $instance->getRemises();
                    foreach ($remises as $remise) {
                        $del_warnings = array();
                        $remise->delete($del_warnings, true);
                    }
                    unset($instance->remises);
                    $instance->remises = null;
                }
            }
        }

        return $errors;
    }

    public function fetch($id, $parent = null, $bimp_line_only = false)
    {
        $this->bimp_line_only = $bimp_line_only;

        if (parent::fetch($id, $parent)) {
            if (!$this->bimp_line_only) {
                if (!$this->fetchLine()) {
                    $this->reset();
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();
        if (!static::$parent_comm_type) {
            $errors[] = 'Impossible de supprimer une ligne depuis une instance de la classe de base "ObjectLine"';
            return $errors;
        }

        if (!$this->isDeletable($force_delete)) {
            return array('Suppression de la ligne impossible');
        }

        $remises = $this->getRemises();
        $lines = $this->getEquipmentLines();

        if (!$this->bimp_line_only) {
            $errors = $this->deleteLine();
        }

        if (!count($errors)) {
            $errors = parent::delete($warnings, $force_delete);
            if (!count($errors)) {
                $prevDeleting = $this->isDeleting;
                $this->isDeleting = true;

                if (count($lines)) {
                    foreach ($lines as $line) {
                        $del_warnings = array();
                        $line->delete($del_warnings, true);
                    }
                }
                if (count($remises)) {
                    foreach ($remises as $remise) {
                        $del_warnings = array();
                        $remise->delete($del_warnings, true);
                    }
                    unset($this->remises);
                    $this->remise = null;
                }

                $this->isDeleting = $prevDeleting;
            }
        }

        return $errors;
    }

    // Gestion ExtraFields: 

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '', &$filters = array())
    {
        // Retourner la clé de filtre SQl sous la forme alias_table.nom_champ_db 
        // Implémenter la jointure dans $joins en utilisant l'alias comme clé du tableau (pour éviter que la même jointure soit ajouté plusieurs fois à $joins). 
        // Si $main_alias est défini, l'utiliser comme préfixe de alias_table. Ex: $main_alias .'_'.$alias_table (Bien utiliser l'underscore).  
        // ET: utiliser $main_alias à la place de "a" dans la clause ON. 
//        Ex: 
        if ($field == 'duree_tot') {
            $join_alias = ($main_alias ? $main_alias . '___' : '') . 'dol_line';
            $joins[$join_alias] = array(
                'alias' => $join_alias,
                'table' => $this->parent->dol_object->table_element_line,
                'on'    => $join_alias . '.rowid = ' . ($main_alias ? $main_alias : 'a') . '.id_line'
            );
            $join_alias2 = ($join_alias ? $join_alias . '___' : '') . 'product';
            /* Pas necessaire de faire la jointure sur cette table mais gardé l'alias pour compatibilit avec le reste */
//            $joins[$join_alias2] = array(
//                'alias' => $join_alias2,
//                'table' => 'product',
//                'on'    => $join_alias2 . '.rowid = ' . ($join_alias ? $join_alias : 'a') . '.fk_product'
//            );
            $join_alias3 = ($join_alias2 ? $join_alias2 . '__' : '') . 'ef';
            $joins[$join_alias3] = array(
                'alias' => $join_alias3,
                'table' => 'product_extrafields',
                'on'    => $join_alias3 . '.fk_object = ' . ($join_alias ? $join_alias : 'a') . '.fk_product'
            );

//        die('('.$join_alias.'.duree * '.$main_alias.'.qty) as duree_tot');
            return '(' . $join_alias3 . '.duree_i * ' . $join_alias . '.qty)';
        }

        return '';
    }

    public function fetchExtraFields()
    {
        $extra = array();
        if (isset($this->parent) && $this->parent->dol_object->table_element_line != '') {
            $sql = 'SELECT (a___dol_line___product___product.duree_i * a___dol_line.qty) as tot
                        FROM ' . MAIN_DB_PREFIX . $this->parent->dol_object->table_element_line . ' a___dol_line
                        LEFT JOIN ' . MAIN_DB_PREFIX . 'product_extrafields a___dol_line___product___product ON a___dol_line___product___product.fk_object = a___dol_line.fk_product
                        WHERE a___dol_line.rowid = ' . $this->getData('id_line');

            $result = $this->db->executeS($sql, 'array');

            if (!is_null($result)) {
                $extra['duree_tot'] = $result[0]['tot'];
            }
        }
        return $extra;
    }

    // Méthodes statiques: 

    public static function getModuleByType($type)
    {
        switch ($type) {
            case 'propal':
            case 'facture':
            case 'commande':
            case 'commande_fournisseur':
            case 'facture_fournisseur':
            default:
                return 'bimpcommercial';

            case 'sav_propal':
                return 'bimpsupport';
        }
    }

    public static function getObjectNameByType($type)
    {
        switch ($type) {
            case 'propal':
                return 'Bimp_PropalLine';

            case 'facture':
                return 'Bimp_FactureLine';

            case 'commande':
                return 'Bimp_CommandeLine';

            case 'commande_fournisseur':
                return 'Bimp_CommandeFournLine';

            case 'facture_fournisseur':
                return 'Bimp_FactureFournLine';

            case 'sav_propal':
                return 'BS_SavPropalLine';

            default:
                return 'ObjectLine';
        }
    }

    public static function getInstanceByParentType($type, $id = null)
    {
        switch ($type) {
            case 'propal':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_PropalLine', $id);

            case 'sav_propal':
                return BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SavPropalLine', $id);

            case 'commande':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id);

            case 'facture':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', $id);

            case 'commande_fournisseur':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', $id);

            case 'facture_fournisseur':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFournLine', $id);
        }

        return BimpObject::getInstance('bimpcommercial', 'ObjectLine');
    }

    public static function traiteSerialApple($serial)
    {
        if (stripos($serial, 'S') === 0) {
            return substr($serial, 1);
        }
        return $serial;
    }
}

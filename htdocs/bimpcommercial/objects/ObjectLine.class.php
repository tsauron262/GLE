<?php

class ObjectLine extends BimpObject
{

    public static $parent_comm_type = '';
    public static $dol_line_primary = 'rowid';
    public static $check_on_update = false;
    public $equipment_required = false;

    const LINE_PRODUCT = 1;
    const LINE_TEXT = 2;
    const LINE_FREE = 3;

    public $desc = null;
    public $id_product = null;
    public $qty = 1;
    public $pu_ht = null;
    public $tva_tx = null;
    public $pa_ht = null;
    public $id_fourn_price = null;
    public $remise = null;
    public $date_from = null;
    public $date_to = null;
    public $id_remise_except = null;
    public static $product_line_data = array(
        'id_product'     => array('label' => 'Produit / Service', 'type' => 'int', 'required' => 1),
        'id_fourn_price' => array('label' => 'Prix d\'achat fournisseur', 'type' => 'int'),
        'desc'           => array('label' => 'Description', 'type' => 'html', 'required' => 0, 'default' => null),
        'qty'            => array('label' => 'Quantité', 'type' => 'float', 'required' => 1, 'default' => 1),
        'pu_ht'          => array('label' => 'PU HT', 'type' => 'float', 'required' => 0, 'default' => null),
        'tva_tx'         => array('label' => 'Taux TVA', 'type' => 'float', 'required' => 0, 'default' => null),
        'pa_ht'          => array('label' => 'Prix d\'achat HT', 'type' => 'float', 'required' => 0, 'default' => null),
        'remise'         => array('label' => 'Remise', 'type' => 'float', 'required' => 0, 'default' => 0),
        'date_from'      => array('label' => 'Date début', 'type' => 'date', 'required' => 0, 'default' => null),
        'date_to'        => array('label' => 'Date fin', 'type' => 'date', 'required' => 0, 'default' => null)
    );
    public static $text_line_data = array(
        'desc'           => array('label' => 'Description', 'type' => 'html', 'required' => 0, 'default' => ''),
        'id_parent_line' => array('label' => 'Ligne parente', 'type' => 'int', 'required' => 0, 'default' => null)
    );
    public static $types = array(
        self::LINE_PRODUCT => 'Produit / Service',
        self::LINE_TEXT    => 'Texte libre'
    );
    protected $product = null;
    protected $post_id_product = null;
    protected $post_equipment = null;
    public $no_equipment_post = false;
    public $remises = null;
    protected $bimp_line_only = false;
    protected $remises_total_infos = null;

    
    public function __construct($module, $object_name) {
        if(BimpCore::getConf("use_freeline"))
            self::$types[self::LINE_FREE]    = 'Ligne libre';
        return parent::__construct($module, $object_name);
    }
    
    
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
        if ($this->getChildObject("product") && $this->getChildObject("product")->id > 0)
            if ($this->getChildObject("product")->getData("price") == 1 || $this->getChildObject("product")->getData("price") == 0)
                return 1;
        return 0;
    }

    public function canEditRemisePa()
    {
        return 0;
    }

    // Getters booléens: 

    public function isCreatable($force_create = false)
    {
        if ($force_create) {
            return 1;
        }

        return $this->isParentEditable();
    }

    public function isEditable($force_edit = false)
    {
        if (!$force_edit && !(int) $this->getData('editable')) {
            return 0;
        }

        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return 0;
        }

        if ($force_edit) {
            return 1;
        }

        if ($parent->field_exists('fk_statut') && (int) $parent->getData('fk_statut') === 0) {
            return 1;
        } elseif (isset($parent->dol_object->statut) && (int) $parent->dol_object->statut === 0) {
            return 1;
        }

        return 0;
    }

    public function isDeletable($force_delete = false)
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
        switch ($field) {
            case 'remisable':
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if (!(int) $product->getData('remisable')) {
                        return 0;
                    }
                }

                if ((float) $this->getTotalHT() < 0) {
                    return 0;
                }

                return 1;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
            return 0;
        }

        switch ($action) {
            case 'attributeEquipment':
                if (!$this->isEditable()) {
                    $errors[] = $this->getLabel('the') . ' n\'est pas modifiable';
                    return 0;
                }
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function isRemisable()
    {
        $product = $this->getProduct();
        if (BimpObject::objectLoaded($product)) {
            if (!(int) $product->getData('remisable')) {
                return 0;
            }
        }

        if ((float) $this->getTotalHT() < 0) {
            return 0;
        }

        return (int) $this->getData('remisable');
    }

    public function isParentEditable()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent)) {
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
        return (int) $this->isEditable();
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
        return array();
    }

    public function isLimited()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if ((int) $this->id_product) {
            if (!(int) $this->db->getValue('product', 'fk_product_type', '`rowid` = ' . (int) $this->id_product)) {
                return 0;
            }
        }

        if (is_null($this->date_from) && is_null($this->date_to)) {
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

            if(is_object($product) && $product->id > 0){
                if ($product->dol_field_exists('validate')) {
                    if (!(int) $product->getData('validate')) {
                        global $user;
                        $errors[] = 'Le produit "' . $product->getRef() . ' - ' . $product->getData('label') . '" n\'est pas validé';
                        if (mailSyn2("Validation produit", "XX_Achats@bimp.fr", null, "Bonjour " . $user->getNomUrl(1) . "souhaite que vous validiez " . $product->getNomUrl(1) . "<br/>Cordialement"))
                            $errors[] = "Un mai a été envoyé pour validation du produit.";
                        return 0;
                    }
                }
            }
        }

        return 1;
    }

    public function hasEquipmentToAttribute()
    {
        if ($this->isLoaded()) {
            $lines = $this->getEquipmentLines();
            if (count($lines)) {
                foreach ($lines as $line) {
                    if (!(int) $line->getData('id_equipment')) {
                        return 1;
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

    // Getters array: 

    public function getProductFournisseursPricesArray()
    {
        $id_product = (int) $this->getIdProductFromPost();
        if ($id_product) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            return Bimp_Product::getFournisseursPriceArray($id_product);
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

        if (!BimpObject::objectLoaded($product)) {
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
                            'onclick' => 'loadModalList(\'' . $instance->module . '\', \'' . $instance->object_name . '\', \'default\', ' . $this->id . ', $(this), \'Equipements assignés à la ligne n°' . $this->getData('position') . '\')'
                        );
                    }
                }
            }
            if ($this->isRemisable() && in_array((int) $this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE))) {
                $onclick = 'loadModalList(\'bimpcommercial\', \'ObjectLineRemise\', \'default\', ' . $this->id . ', $(this), \'Remises\', {parent_object_type: \'' . static::$parent_comm_type . '\'})';
                $buttons[] = array(
                    'label'   => 'Remises ligne',
                    'icon'    => 'percent',
                    'onclick' => $onclick
                );
            }
            if ($this->isParentEditable() && in_array((int) $this->getData('type'), array(self::LINE_PRODUCT, self::LINE_FREE)) && !(int) $this->getData('id_parent_line')) {
                $line_instance = BimpObject::getInstance($this->module, $this->object_name);
                $onclick = $line_instance->getJsLoadModalForm('default', 'Ajout d\\\'une sous-ligne à la ligne n°' . $this->getData('position'), array(
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

    // Getters valeurs:

    public function getUnitPriceTTC()
    {
        if (!is_null($this->pu_ht)) {
            $pu_ht = $this->pu_ht;

            if (!is_null($this->remise) && $this->remise > 0) {
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

        if (!is_null($this->remise) && (float) $this->remise > 0) {
            $value -= ($value * ((float) $this->remise / 100));
        }

        return $value;
    }

    public function getTotalHT()
    {
        if (!is_null($this->pu_ht) && !is_null($this->qty)) {
            return (float) ((float) $this->pu_ht * (float) $this->qty);
        }

        return 0;
    }

    public function getTotalHTWithRemises()
    {
        if (!is_null($this->pu_ht)) {
            $pu_ht = $this->pu_ht;

            if (!is_null($this->remise) && $this->remise > 0) {
                $pu_ht -= (float) ($pu_ht * ($this->remise / 100));
            }

            return (float) ($pu_ht * (float) $this->qty);
        }

        return 0;
    }

    public function getTotalTTC()
    {
        $pu_ttc = (float) $this->getUnitPriceTTC();

        if ($pu_ttc && !is_null($this->qty)) {
            return (float) ($pu_ttc * (float) $this->qty);
        }
        return 0;
    }

    public function getTotalTtcWithoutRemises()
    {
        return round(BimpTools::calculatePriceTaxIn((float) $this->pu_ht, (float) $this->tva_tx) * (float) $this->qty, 8);
    }

    public function getMargin()
    {
        $pu = (float) $this->pu_ht;
        if (!is_null($this->remise) && $this->remise > 0) {
            $pu -= ($pu * ((float) $this->remise / 100));
        }

        $margin = ($pu * (float) $this->qty) - ((float) $this->pa_ht * (float) $this->qty);
        return $margin;
    }

    public function getMarginRate()
    {
        $pu = (float) $this->pu_ht;
        if (!is_null($this->remise) && (float) $this->remise > 0) {
            $pu -= ($pu * ((float) $this->remise / 100));
        }

        $margin = $pu - (float) $this->pa_ht;

        if (!$margin) {
            return 0;
        }

        if ((int) BimpCore::getConf('bimpcomm_tx_marque')) {
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
        if ((int) BimpCore::getConf('bimpcomm_tx_marque')) {
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
                    if ($this->isLoaded() && $this->field_exists('def_pu_ht')) {
                        $pu_ht = $this->getData('def_pu_ht');
                    }
                    if ($id_product && (is_null($pu_ht) || (int) $this->id_product !== $id_product)) {
                        return $product->getData('price');
                    }
                    return $pu_ht;

                case 'tva_tx':
                    $tva_tx = $this->tva_tx;
                    if ($this->isLoaded() && $this->field_exists('def_tva_tx')) {
                        $tva_tx = $this->getData('def_tva_tx');
                    }
                    if ($id_product && (is_null($tva_tx) || (int) $this->id_product !== $id_product)) {
                        return (float) $product->getData('tva_tx');
                    }
                    if (is_null($tva_tx)) {
                        $tva_tx = BimpTools::getDefaultTva();
                    }
                    return (float) $tva_tx;

                case 'id_fourn_price':
                    $id_fourn_price = $this->id_fourn_price;
                    if ($this->isLoaded() && $this->field_exists('def_id_fourn_price')) {
                        $id_fourn_price = $this->getData('def_id_fourn_price');
                    }
                    if ($id_product && (is_null($id_fourn_price) || (int) $this->id_product !== $id_product)) {
                        if ((int) $this->id_fourn_price) {
                            $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $this->id_fourn_price);
                            if (BimpObject::objectLoaded($pfp)) {
                                if ((int) $pfp->getData('fk_product') === $id_product) {
                                    return $this->id_fourn_price;
                                }
                            }
                        }

                        // Récupération du dernier prix d'achat enregistré: 
                        return (int) $product->getCurrentFournPriceId();
                    }
                    return (int) $id_fourn_price;

                case 'remisable':
                    if ($id_product && (int) $this->id_product !== $id_product) {
                        return (int) $product->getData('remisable');
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
        $this->getIdProductFromPost();
        if ((int) $this->id_product) {
            if (is_null($this->product)) {
                $this->product = BimpObject::getInstance('bimpcore', 'Bimp_Product', (int) $this->id_product);
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
            $lines = $this->getChildrenObjects('equipment_lines', array(
                'id_object_line' => (int) $this->id,
                'object_type'    => static::$parent_comm_type
            ));
            return $lines;
        }

        return array();
    }

    public function getRemises()
    {
        if ($this->isLoaded() && static::$parent_comm_type) {
            if (is_null($this->remises)) {
                $this->remises = $this->getChildrenObjects('remises', array(
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
                $this->remises = null;
            }

            return $this->remises;
        }

        return array();
    }

    public function getRemiseTotalInfos($recalculate = false, $remise_globale_rate = null)
    {
        if ($recalculate || is_null($this->remises_total_infos)) {
            $this->remises_total_infos = array(
                'line_percent'              => 0,
                'line_amount_ht'            => 0,
                'line_amount_ttc'           => 0,
                'remise_globale_percent'    => 0,
                'remise_globale_amount_ht'  => 0,
                'remise_globale_amount_ttc' => 0,
                'total_percent'             => 0,
                'total_amount_ht'           => 0,
                'total_amount_ttc'          => 0,
                'total_ht_without_remises'  => 0,
                'total_ttc_without_remises' => 0
            );

            if (!$this->isLoaded()) {
                return $this->remises_total_infos;
            }

            $total_ht = $this->getTotalHT();
            $total_ttc = $this->getTotalTtcWithoutRemises();
            $this->remises_total_infos['total_ht_without_remises'] = $total_ht;
            $this->remises_total_infos['total_ttc_without_remises'] = $total_ttc;

            if (!$this->isRemisable()) {
                return $this->remises_total_infos;
            }

            $remises = $this->getRemises();

            $total_line_amounts = 0;

            foreach ($remises as $remise) {
                switch ((int) $remise->getData('type')) {
                    case ObjectLineRemise::OL_REMISE_PERCENT:
                        $this->remises_total_infos['line_percent'] += (float) $remise->getData('percent');
                        break;

                    case ObjectLineRemise::OL_REMISE_AMOUNT:
                        if ((int) $remise->getData('per_unit')) {
                            $total_line_amounts += ((float) $remise->getData('montant') * (float) $this->qty);
                        } else {
                            $total_line_amounts += (float) $remise->getData('montant');
                        }
                        break;
                }
            }

            if ($total_line_amounts) {
                $this->remises_total_infos['line_percent'] += (float) (($total_line_amounts / $total_ttc) * 100);
            }

            if ($this->remises_total_infos['line_percent']) {
                $this->remises_total_infos['line_amount_ht'] = (float) ($total_ht * ($this->remises_total_infos['line_percent'] / 100));
                $this->remises_total_infos['line_amount_ttc'] = (float) ($total_ttc * ($this->remises_total_infos['line_percent'] / 100));
            }

            $parent = $this->getParentInstance();

            if ($this->isRemisable()) {
                if ($parent->isLoaded()) {
                    if ($parent->field_exists('remise_globale')) {
                        if (is_null($remise_globale_rate)) {
                            $remise_globale_rate = (float) $parent->getRemiseGlobaleLineRate($recalculate);
                        } else {
                            $remise_globale_rate = (float) $remise_globale_rate;
                        }
                        if ($remise_globale_rate > 0) {
                            $this->remises_total_infos['remise_globale_percent'] = $remise_globale_rate;
                            $this->remises_total_infos['remise_globale_amount_ht'] = $total_ht * ($remise_globale_rate / 100);
                            $this->remises_total_infos['remise_globale_amount_ttc'] = $total_ttc * ($remise_globale_rate / 100);
                        }
                    }
                }
            }

            $this->remises_total_infos['total_percent'] = round($this->remises_total_infos['line_percent'] + $this->remises_total_infos['remise_globale_percent'], 8);
            $this->remises_total_infos['total_amount_ht'] = $this->remises_total_infos['line_amount_ht'] + $this->remises_total_infos['remise_globale_amount_ht'];
            $this->remises_total_infos['total_amount_ttc'] = $this->remises_total_infos['line_amount_ttc'] + $this->remises_total_infos['remise_globale_amount_ttc'];
        }

        return $this->remises_total_infos;
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
            if (BimpObject::objectLoaded($entrepot)) {
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

        return 3;
    }

    // Affichages: 

    public function displayLineData($field, $edit = 0, $display_name = 'default', $no_html = false)
    {
        $html = '';

        if ((int) $this->getData('type') === self::LINE_TEXT) {
            if (!array_key_exists($field, self::$text_line_data)) {
                return '';
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
                                    $html = BimpObject::getInstanceNom($product);
                                } else {
                                    $html .= BimpObject::getInstanceNomUrl($product);
                                    $html .= BimpRender::renderObjectIcons($product, true);
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

                    $product = $this->getProduct();
                    $desc = BimpTools::cleanString($this->desc);
                    $text = '';


                    if (BimpObject::objectLoaded($product)) {
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

                        if ((int) $product->getData('fk_product_type') == 1) {
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
                    }

                    if ((!$text || $field !== 'desc_light') && $desc) {
                        $text .= ($text ? '<br/>' : '') . (string) $desc;
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
                    break;

                case 'pu_ht':
                    if ($no_html) {
                        $html = price((float) $this->pu_ht) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->pu_ht, 'EUR');
                    }
                    break;

                case 'pu_ttc':
                    if ($no_html) {
                        $html = price((float) $this->getUnitPriceTTC()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getUnitPriceTTC(), 'EUR');
                    }
                    break;

                case 'tva_tx':
                    $html .= str_replace('.', ',', (string) $this->tva_tx) . ' %';
                    break;

                case 'pa_ht':
                    $pa_ht = (float) $this->pa_ht;
                    $remise_pa = 0;

                    if ($this->field_exists('remise_pa')) {
                        $remise_pa = (float) $this->getData('remise_pa');
                        if ($remise_pa && $pa_ht) {
                            $pa_ht -= ($pa_ht * ($remise_pa / 100));
                        }
                    }
                    if ($no_html) {
                        $html = price((float) $pa_ht) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $pa_ht, 'EUR');
                    }

                    if ($remise_pa) {
                        $html .= ' (-' . BimpTools::displayFloatValue($remise_pa, 8) . '%)';
                    }
                    break;

                case 'remise':
                    $html .= str_replace('.', ',', (string) $this->remise) . ' %';
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
                    if ($no_html) {
                        $html = price((float) $this->getTotalHT()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getTotalHT(), 'EUR');
                    }
                    break;

                case 'total_ht_w_remises':
                    if ($no_html) {
                        $html = price((float) $this->getTotalHTWithRemises()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getTotalHTWithRemises(), 'EUR');
                    }
                    break;

                case 'total_ttc':
                    if ($no_html) {
                        $html = price((float) $this->getTotalTTC()) . ' €';
                    } else {
                        $html .= BimpTools::displayMoneyValue((float) $this->getTotalTTC(), 'EUR');
                    }
                    break;

                case 'margin':
                    $margin = (float) $this->getMargin();
                    $margin_rate = 0;
                    if ($margin !== 0.0) {
                        $margin_rate = round($this->getMarginRate(), 4);
                    }

                    if ($no_html) {
                        if (!$margin_rate && !(float) $this->pa_ht) {
                            $html = price($margin) . ' € (' . $margin_rate . ' %)';
                        } else {
                            $html = price($margin) . ' € (∞)';
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
                    }
                    break;
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

    public function displayRemise()
    {
        $html = '';
        if ($this->isLoaded()) {
            if ($this->isRemisable()) {
                $remises = $this->getRemiseTotalInfos();
                if ((float) $remises['total_amount_ttc']) {
                    $html .= BimpTools::displayMoneyValue($remises['total_amount_ht'], 'EUR');
                    $html .= ' / ' . BimpTools::displayMoneyValue($remises['total_amount_ttc'], 'EUR');
                    $html .= ' (' . round($remises['total_percent'], 4) . '%)';
                }
            } else {
                $html = '<span class="warning">Non remisable</span>';
            }
        }

        return $html;
    }

    public function displayUnitPriceHTWithRemises()
    {
        return BimpTools::displayMoneyValue($this->getUnitPriceHTWithRemises(), 'EUR');
    }

    // Gestion ligne dolibarr:

    public function createFromDolLine($id_obj, $line)
    {
        $errors = array();

        if (BimpObject::objectLoaded($line)) {
            if ($this->isLoaded()) {
                BimpCache::unsetBimpObjectInstance($this->module, $this->object_name, $this->id);
            }

            $parent = $this->parent;
            $this->reset();
            $this->parent = $parent;

            $remisable = 1;

            if (isset($line->fk_product) && (int) $line->fk_product) {
                $type = 1;
                if ($this->dol_field_exists('remisable')) {
                    $remisable = $this->db->getValue('product_extrafields', 'remisable', '`fk_object` = ' . (int) $line->fk_product);
                }
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
                $id = (int) $this->db->insert($this->getTable(), $this->data, true);
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
                    $this->fetch($id, $this->parent);

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
                            $remise_errors = $remise->create($remise_warnings, true);
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
        }

        return $errors;
    }

    protected function createLine($check_data = true)
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

                    $class_name = get_class($object);
                    switch ($class_name) {
                        case 'Propal':
//                            addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0.0, $txlocaltax2=0.0, $fk_product=0, $remise_percent=0.0, $price_base_type='HT', $pu_ttc=0.0, $info_bits=0, $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=0, $pa_ht=0, $label='',$date_start='', $date_end='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0, $pu_ht_devise=0, $fk_remise_except=0)
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (float) $this->remise, 'HT', 0, 0, 0, (int) $this->getData('position'), 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht, '', $date_from, $date_to, 0, null, '', 0, 0, (int) $this->id_remise_except);
                            break;

                        case 'Facture':
//                            addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=self::TYPE_STANDARD, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='', $array_options=0, $situation_percent=100, $fk_prev_id=0, $fk_unit = null, $pu_ht_devise = 0)
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (float) $this->remise, $date_from, $date_to, 0, 0, $this->id_remise_except, 'HT', 0, Facture::TYPE_STANDARD, (int) $this->getData('position'), 0, '', 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht);
                            break;

                        case 'Commande':
//                            addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $info_bits=0, $fk_remise_except=0, $price_base_type='HT', $pu_ttc=0, $date_start='', $date_end='', $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0, $pu_ht_devise = 0)
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (float) $this->remise, 0, (int) $this->id_remise_except, 'HT', 0, $date_from, $date_to, 0, (int) $this->getData('position'), 0, 0, (int) $this->id_fourn_price, (float) $this->pa_ht);
                            break;

                        case 'CommandeFournisseur':
                            if (isset($this->ref_supplier)) {
                                $ref_supplier = $this->ref_supplier;
                            } else {
                                $ref_supplier = '';
                            }
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, $this->qty, (float) $this->tva_tx, 0, 0, (int) $this->id_product, (int) $this->id_fourn_price, $ref_supplier, (float) $this->remise, 'HT', 0.0, 0, 0, false, $date_from, $date_to);
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
                            $result = $object->addLine((string) $this->desc, (float) $this->pu_ht, (float) $this->tva_tx, 0, 0, $this->qty, (int) $this->id_product, (float) $this->remise, $date_from, $date_to, 0, '', 'HT', $type, (int) $this->getData('position'), false, 0, null, 0, 0, $ref_supplier);
                            break;

                        default:
                            $errors[] = 'Objet parent non défini';
                            break;
                    }


                    break;

                case self::LINE_TEXT:
                    $result = $object->addLine((string) $this->desc, 0, (float) $this->qty, 0);
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
                    switch ($class_name) {
                        case 'Propal':
                            BimpCache::unsetDolObjectInstance((int) $id_line, 'comm/propal', 'propal', 'PropaleLigne');
                            $result = $object->updateline($id_line, 0, 0, 0, 0, 0, 0, (string) $this->desc);
                            break;

                        case 'Facture':
                        case 'Commande':
                        case 'CommandeFournisseur':
                            $result = $object->updateline($id_line, $this->desc);
                            break;

                        default:
                            $result = 0;
                    }
                    break;

                default:
                    $errors[] = 'Type de ligne invalide';
                    break;
            }

            if (!is_null($result) && $result <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object), 'Des erreurs sont survenues lors de la mise à jour de la ligne ' . BimpObject::getInstanceLabel($instance, 'of_the'));
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
                $this->desc = (string) $line->desc;
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
                $this->desc = (string) $line->desc;
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

    protected function createEquipmentsLines($qty = null)
    {
        $warnings = array();

        if (!static::$parent_comm_type) {
            $warnings[] = 'Erreur technique: tentative de création d\'une ligne équipement depuis une instance "ObjectLine"';
            return $warnings;
        }

        if (!$this->equipment_required) {
            return $warnings;
        }

        if ((int) $this->getData('type') !== self::LINE_TEXT && (int) $this->id_product) {
            if (is_null($qty)) {
                $qty = abs((int) $this->qty);
            }

            $product = $this->getProduct();
            if (BimpObject::objectLoaded($product)) {
                if ((int) $product->getData('fk_product_type') === Product::TYPE_PRODUCT) {
                    if ($product->isSerialisable()) {

                        $instance = BimpObject::getInstance('bimpcommercial', 'ObjectLineEquipment');
                        $i = 1;
                        while ($qty > 0) {
                            $instance->reset();
                            $pu_ht = (float) $this->pu_ht;
                            if ($this->field_exists('def_pu_ht')) {
                                $pu_ht = (float) $this->getData('def_pu_ht');
                            }
                            $tva_tx = (float) $this->tva_tx;
                            if ($this->field_exists('def_tva_tx')) {
                                $tva_tx = (float) $this->getData('def_tva_tx');
                            }
                            $pa_ht = (float) $this->pa_ht;
                            if ($this->field_exists('def_pa_ht')) {
                                $pa_ht = (float) $this->getData('def_pa_ht');
                            }
                            $id_fourn_price = (int) $this->id_fourn_price;
                            if ($this->field_exists('def_id_fourn_price')) {
                                $id_fourn_price = (float) $this->getData('def_id_fourn_price');
                            }

                            $instance->validateArray(array(
                                'id_object_line' => (int) $this->id,
                                'object_type'    => static::$parent_comm_type,
                                'id_equipment'   => 0,
                                'pu_ht'          => (float) $pu_ht,
                                'tva_tx'         => (float) $tva_tx,
                                'pa_ht'          => (float) $pa_ht,
                                'id_fourn_price' => (int) $id_fourn_price
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
        }

        return $warnings;
    }

    public function attributeEquipment($id_equipment, $pu_ht = null, $tva_tx = null, $id_fourn_price = null, $id_equipment_line = 0)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $errors = array();

        if (!(int) $id_equipment && !$id_equipment_line) {
            $errors[] = 'Aucun équipement spécifié';
        } else {
            $line = null;

            if ($id_equipment_line) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'ObjectLineEquipment', (int) $id_equipment_line);
                if (!BimpObject::objectLoaded($line)) {
                    $errors[] = 'La ligne d\'équipement d\'ID ' . $id_equipment_line . ' n\'existe pas';
                    return $errors;
                }
            }

            if ($id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (is_null($line) || ((int) $line->getData('id_equipment') !== (int) $id_equipment)) {
                    $errors = $this->checkEquipment($equipment);
                }
            }

            if (!count($errors)) {
                if (!$id_equipment_line) {
                    $lines = $this->getEquipmentLines();

                    if (!count($lines)) {
                        $errors[] = 'Aucune ligne d\'équipement n\'est enregistré pour ' . $this->getLabel('this');
                    } else {
                        foreach ($lines as $l) {
                            if (!(int) $l->getData('id_equipment')) {
                                $line = $l;
                                break;
                            }
                        }
                    }
                }

                if (is_null($line)) {
                    $errors[] = 'Il n\'y a aucune unité en attente d\'attribution d\'un équipement';
                } else {
                    if ((int) $line->getData('id_equipment') !== (int) $id_equipment) {
                        $errors = $line->setEquipment((int) $id_equipment, false);
                    }

                    if (!count($errors)) {
                        if ($this->isParentEditable()) {
                            if (is_null($tva_tx)) {
                                if ($this->field_exists('def_tva_tx')) {
                                    $tva_tx = (float) $this->getData('def_tva_tx');
                                } else {
                                    $tva_tx = (float) $this->tva_tx;
                                }
                            }
                            if (!is_null($equipment) && (float) $equipment->getData('prix_vente_except')) {
                                $pu_ht = BimpTools::calculatePriceTaxEx((float) $equipment->getData('prix_vente_except'), $tva_tx);
                            } elseif (is_null($pu_ht)) {
                                if ($this->field_exists('def_pu_ht')) {
                                    $pu_ht = (float) $this->getData('def_pu_ht');
                                } else {
                                    $pu_ht = (float) $this->pu_ht;
                                }
                            }
                            $line->set('pu_ht', (float) $pu_ht);
                            $line->set('tva_tx', $tva_tx);
                        }
                        if (!is_null($equipment) && (float) $equipment->getData('prix_achat')) {
                            $line->set('pa_ht', (float) $equipment->getData('prix_achat'));
                            $line->set('id_fourn_price', 0);
                        } else {
                            if (is_null($id_fourn_price)) {
                                if ($this->field_exists('def_id_fourn_price')) {
                                    $id_fourn_price = $this->getData('def_id_fourn_price');
                                } else {
                                    $id_fourn_price = $this->id_fourn_price;
                                }
                            }
                            if ((int) $id_fourn_price) {
                                $pa_ht = (float) $this->db->getValue('product_fournisseur_price', 'price', '`rowid` = ' . (int) $id_fourn_price);
                            } else {
                                $pa_ht = (float) $this->pa_ht;
                            }
                            $line->set('pa_ht', $pa_ht);
                            $line->set('id_fourn_price', (int) $id_fourn_price);
                        }

                        $warnings = array();
                        $update_errors = $line->update($warnings, true);
                        if (count($update_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($update_errors, 'Des erreurs sont survenues lors de la mise à jour des données de la ligne d\'équipement');
                        } else {
                            if ($this->isEditable()) {
                                $this->update();
                            } else {
                                $this->calcValuesByEquipments();
                                $this->forceUpdateLine();
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function calcValuesByEquipments()
    {
        if ($this->isLoaded() && (int) $this->qty > 0) {
            $lines = $this->getEquipmentLines();

            if (count($lines)) {
                $total_ht = 0;
                $total_achat = 0;

                foreach ($lines as $line) {
                    $total_ht += (float) $line->getData('pu_ht');
                    $total_achat += (float) $line->getData('pa_ht');
                }

                if ($this->isEditable()) {
                    $this->pu_ht = (float) $total_ht / $this->qty;
                }
                $this->pa_ht = (float) $total_achat / $this->qty;
                $this->id_fourn_price = 0;
            }
        }
    }

    public function setEquipments($equipments, &$equipments_set = array())
    {
        $errors = array();
        $equipments_set = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
            return $errors;
        }

        $current_equipments = array();
        $new_equipments = array();

        $line_equipments = $this->getEquipmentLines();

        foreach ($line_equipments as $line_equipment) {
            $id_equipment = (int) $line_equipment->getData('id_equipment');
            if ($id_equipment) {
                $current_equipments[] = $id_equipment;
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

        foreach ($equipments as $equipment_data) {
            if (isset($equipment_data['id_equipment'])) {
                if (!in_array((int) $equipment_data['id_equipment'], $current_equipments)) {
                    $pu_ht = isset($equipment_data['pu_ht']) ? $equipment_data['pu_ht'] : null;
                    $tva_tx = isset($equipment_data['tva_tx']) ? $equipment_data['tva_tx'] : null;
                    $eq_errors = $this->attributeEquipment($equipment_data['id_equipment'], $pu_ht, $tva_tx);

                    if (count($eq_errors)) {
                        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $equipment_data['id_equipment']);
                        if (BimpObject::objectLoaded($equipment)) {
                            $label = '"' . $equipment->getData('serial') . '" (ID: ' . $equipment_data['id_equipment'] . ')';
                        } else {
                            $label = 'd\'ID ' . $equipment_data['id_equipment'];
                        }
                        $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Erreurs lors de l\'attribution de l\'équipement ' . $label);
                    }
                }
            }
        }

        // Equipements à supprimer:         
        foreach ($line_equipments as $line_equipment) {
            $id_equipment = (int) $line_equipment->getData('id_equipment');
            if ($id_equipment && !in_array($id_equipment, $new_equipments)) {
                $line_errors = $line_equipment->removeEquipment();
                if (count($line_errors)) {
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if (BimpObject::objectLoaded($equipment)) {
                        $eq_label = '"' . $equipment->getData('serial') . '"';
                    } else {
                        $eq_label = ' d\'ID ' . $id_equipment;
                    }
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs lors de la désattribution de l\'équipement ' . $eq_label);
                }
            }
        }

        $line_equipments = $this->getEquipmentLines();
        foreach ($line_equipments as $line_equipment) {
            $equipments_set[] = (int) $line_equipment->getData('id_equipment');
        }

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

    public function calcRemise($remise_globale_rate = null)
    {
        if ($this->isLoaded()) {
            $remises_infos = $this->getRemiseTotalInfos(true, $remise_globale_rate);

            if (is_null($this->remise) || (float) $this->remise !== (float) $remises_infos['total_percent'] ||
                    $remises_infos['total_percent'] !== (float) $this->getData('remise') ||
                    $remises_infos['total_percent'] !== (float) $this->getInitData('remise')) {
                $this->update($warnings, true);
            }
        }
    }

    public function checkRemises()
    {
        $errors = array();
        $remises = $this->getRemises();

        // On suppose que "$this->remise" a pu être modifié via l'ancienne interface Dolibarr: 

        if (!$this->isRemisable()) {
            if (count($remises)) {
                foreach ($remises as $remise) {
                    $del_warnings = array();
                    $remise->delete($del_warnings, true);
                }
                unset($this->remises);
                $this->remises = null;
            }
            if ((float) $this->remise || (float) $this->getData('remise')) {
                $this->remise = 0;
                $this->update();
            }
        } else {
            $remise_infos = $this->getRemiseTotalInfos();

            if ((float) $this->remise !== (float) $remise_infos['total_percent']) {
                $remise_percent = (float) $this->remise;

                $remises = $this->getRemises();
                foreach ($remises as $remise) {
                    $del_warnings = array();
                    $remise->delete($del_warnings, true);
                }
                unset($this->remises);
                $this->remises = null;

                $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise', null, $this);
                $remise->validateArray(array(
                    'id_object_line' => (int) $this->id,
                    'object_type'    => static::$parent_comm_type,
                    'type'           => ObjectLineRemise::OL_REMISE_PERCENT,
                    'percent'        => (float) $remise_percent
                ));
                $remise_errors = $remise->create($warnings, true);
                if (count($remise_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise de ' . $remise_percent . ' %');
                }
                $this->calcRemise();
            }
        }
    }

    // Traitements divers: 

    public function onChildSave($child)
    {
        if (is_a($child, 'ObjectLineRemise')) {
            if (!$this->isLoaded()) {
                $instance = self::getInstanceByParentType($child->getData('object_type'), (int) $child->getData('id_object_line'));
                if ($instance->isLoaded()) {
                    $instance->onChildSave($child);
                }
            } else {
                unset($this->remises);
                $this->remises = null;
                $this->calcRemise();
            }
        }
    }

    protected function setLinesPositions()
    {
        $errors = array();

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

            if (!is_null($lines) && count($lines)) {
                foreach ($lines as $line) {
                    if ($this->db->update($table, array(
                                'rang' => (int) $line['position']
                                    ), '`' . $primary . '` = ' . (int) $line['id_line']) <= 0) {
                        $msg = 'Echec de la mise à jour de la position de la ligne d\'ID ' . $line['id_line'];
                        $sqlError = $this->db->lasterror();
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

    public function checkPosition($position)
    {
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

    // Rendus HTML: 

    public function renderLineInput($field, $attribute_equipment = false, $prefixe = '', $force_edit = false)
    {
        $html = '';

        $value = null;

        $this->getIdProductFromPost();

        if ($field === 'id_product') {
            $value = (int) $this->id_product;
        } elseif (in_array($field, array('pu_ht', 'tva_tx', 'id_fourn_price', 'remisable', 'desc'))) {
            $value = $this->getValueByProduct($field);
        } else {
            if (BimpTools::isSubmit($field)) {
                $value = BimpTools::getValue($field);
            } elseif (BimpTools::isSubmit('fields/' . $field)) {
                $value = BimpTools::getValue('fields/' . $field);
            } else {
                if ($this->isLoaded() && $this->field_exists('def_' . $field)) {
                    $value = $this->getData('def_' . $field);
                } elseif (isset($this->{$field})) {
                    $value = $this->{$field};
                } elseif ($this->field_exists($field)) {
                    $value = $this->getData($field);
                }
            }
        }

        switch ($field) {
            case 'id_product':
                $html = BimpInput::renderInput('search_product', $prefixe . 'id_product', (int) $value, array(
                            'filter_type' => 'both'
                ));
                $html .= '<p class="inputHelp">Entrez la référence ou le code-barre d\'un produit.<br/>Laissez vide si vous sélectionnez un équipement.</p>';
                break;

            case 'id_fourn_price':
                if (BimpObject::objectLoaded($this->post_equipment)) {
                    if ((float) $this->post_equipment->getData('prix_achat') > 0) {
                        $html .= '<input type="hidden" name="' . $prefixe . 'id_fourn_price" value="0"/>';
                        $html .= '<input type="hidden" name="' . $prefixe . 'pa_ht" value="' . (float) $this->post_equipment->getData('prix_achat') . '"/>';
                        $html .= 'Prix d\'achat équipement: <strong>';
                        $html .= BimpTools::displayMoneyValue((float) $this->post_equipment->getData('prix_achat'), 'EUR') . '</strong>';
                        break;
                    }
                }

                $values = $this->getProductFournisseursPricesArray();

                $has_values = false;
                foreach ($values as $value => $label) {
                    if ((int) $value) {
                        $has_values = true;
                        break;
                    }
                }

                if ($has_values && !$attribute_equipment && $this->canEditPrixAchat() && $this->isEditable($force_edit)) {
                    $html = BimpInput::renderInput('select', $prefixe . 'id_fourn_price', (int) $value, array(
                                'options' => $values
                    ));
                } else {
                    if ($attribute_equipment) {
                        if ($this->field_exists('def_id_fourn_price')) {
                            $value = (int) $this->getData('def_id_fourn_price');
                        }
                    }
                    $html .= '<input type="hidden" name="' . $prefixe . 'id_fourn_price" value="' . $value . '"/>';
                    if ((int) $value) {
                        if (isset($values[$value])) {
                            $html .= $values[$value];
                        } else {
                            $html .= BimpRender::renderAlerts('Le prix fournisseur d\'ID ' . $value . ' n\'est pas enregistré pour ce produit');
                        }
                    } else {
                        $pa_ht = $this->pa_ht;
                        $is_pa_prevu = false;

                        $product = $this->getProduct();
                        if (BimpObject::objectLoaded($product)) {
                            $pa_prevu = (float) $product->getData('pa_prevu');

                            if (is_null($pa_ht && $pa_prevu)) {
                                $pa_ht = $pa_prevu;
                            }

                            if ($pa_ht === $pa_prevu) {
                                $is_pa_prevu = true;
                            }
                        }

                        if (!is_null($pa_ht)) {
                            $html .= '<input type="hidden" name="' . $prefixe . 'pa_ht" value="' . $pa_ht . '"/>';
                            $html .= BimpTools::displayMoneyValue($pa_ht);
                            if ($is_pa_prevu) {
                                $html .= ' (prévisionnel)';
                            }
                        }
                    }
                }
                break;

            case 'desc':
                $html = BimpInput::renderInput('html', 'desc', (string) $value);
                break;

            case 'qty':
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
                                        'decimals'  => 3
                                    )
                        ));
                    } else {
                        $min = 'none';
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

                        $html = BimpInput::renderInput('qty', $prefixe . 'qty', (int) $value, array(
                                    'data' => array(
                                        'data_type' => 'number',
                                        'min'       => $min,
                                        'unsigned'  => 0,
                                        'decimals'  => $decimals
                                    )
                        ));
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
                            'decimals'  => 2
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
                    $html = BimpInput::renderInput('text', $prefixe . 'tva_tx', (float) $value, array(
                                'data'        => array(
                                    'data_type' => 'number',
                                    'decimals'  => 2,
                                    'min'       => 0,
                                    'max'       => 100
                                ),
                                'addon_right' => '<i class="fa fa-percent"></i>'
                    ));
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
                if (!$this->isFieldEditable('remisable')) {
                    $html .= '<input type="hidden" value="0" name="' . $prefixe . 'remisable"/>';
                    $html .= '<span class="danger">NON</span>';
                    break;
                }

                $html .= BimpInput::renderInput('toggle', $prefixe . 'remisable', (int) $value);
                break;

            case 'remise_crt':
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    $remise_pa = (float) $product->getRemiseCrt();
                    if ($remise_pa) {
                        $html .= BimpInput::renderInput('toggle', $prefixe . 'remise_crt', (int) $value);
                    } else {
                        $html .= '<input type="hidden" name="' . $prefixe . 'remise_crt" value="0"/>';
                        $html .= '<span class="warning">Non applicable</span>';
                    }
                } else {
                    $html .= '<input type="hidden" name="' . $prefixe . 'remise_crt" value="0"/>';
                    $html .= '<span class="warning">Attente sélection d\'un produit</span>';
                }
                break;

            case 'remise_pa':
                $product = $this->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if ((int) BimpTools::getPostFieldValue('remise_crt', 0)) {
                        $remise_pa = (float) $product->getRemiseCrt();
                        $html .= '<input type="hidden" name="' . $prefixe . 'remise_pa" value="' . $remise_pa . '"/>';
                        $html .= BimpTools::displayFloatValue($remise_pa, 8) . '%';
                    } elseif ($this->canEditRemisePa()) {
                        $html .= BimpInput::renderInput('text', $prefixe . 'remise_pa', (float) $value, array(
                                    'addon_right' => BimpRender::renderIcon('fas_percent'),
                                    'data'        => array(
                                        'data_type' => 'number',
                                        'decimals'  => 8,
                                        'min'       => 0,
                                        'max'       => 100,
                                        'unsigned'  => 1
                                    )
                        ));
                    } else {
                        $html .= '<input type="hidden" name="' . $prefixe . 'remise_pa" value="0"/>';
                        $html .= '0%';
                    }
                } else {
                    $html .= '<input type="hidden" name="' . $prefixe . 'remise_pa" value="0"/>';
                    $html .= '<span class="warning">Attente sélection d\'un produit</span>';
                }
                break;
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

                if (BimpObject::objectLoaded($parent)) {
                    if ($parent->field_exists('remise_globale')) {
                        $line_remise += (float) $parent->getRemiseGlobaleLineRate();
                    }
                }

                if ($total_remises) {
                    $line_total_ttc = BimpTools::calculatePriceTaxIn($line_pu, (float) $line_tva_tx) * (float) $line_qty;
                    $line_remise += (float) (($total_remises / $line_total_ttc) * 100);
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

        if ((int) BimpCore::getConf('bimpcomm_tx_marque')) {
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

    public function renderQuickAddForm()
    {
        if (!$this->isParentEditable()) {
            return '';
        }

        $parent = $this->getParentInstance();

        $html = '';

        $html .= '<div class="objectLineQuickAddForm singleLineForm"';
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
                $pu_ht = null;
                $tva_tx = null;
                $id_fourn_price = null;
                $id_line_equipment = (int) (isset($data['id_line_equipment']) ? $data['id_line_equipment'] : 0);

                if ($id_line_equipment) {
                    $success = 'Mise à jour de la ligne d\'équipement effectuée avec succès';
                }

                if ($this->isParentEditable()) {
                    if (isset($data['pu_ht'])) {
                        $pu_ht = (float) $data['pu_ht'];
                    } elseif ($this->field_exists('def_pu_ht')) {
                        $pu_ht = (float) $this->getData('def_pu_ht');
                    } else {
                        $pu_ht = (float) $this->pu_ht;
                    }

                    if (isset($data['tva_tx'])) {
                        $tva_tx = (float) $data['tva_tx'];
                    } elseif ($this->field_exists('def_tva_tx')) {
                        $tva_tx = (float) $this->getData('def_tva_tx');
                    } else {
                        $tva_tx = (float) $this->tva_tx;
                    }
                }

                if (isset($data['id_fourn_price']) && (int) $data['id_fourn_price']) {
                    $id_fourn_price = (float) $data['id_fourn_price'];
                } elseif ($this->field_exists('def_id_fourn_price')) {
                    $id_fourn_price = (float) $this->getData('def_id_fourn_price');
                } else {
                    $id_fourn_price = (float) $this->id_fourn_price;
                }

                $errors = $this->attributeEquipment((int) $data['id_equipment'], $pu_ht, $tva_tx, $id_fourn_price, $id_line_equipment);

                if (!count($errors)) {
                    if (method_exists($this, 'onEquipmentAttributed')) {
                        $this->onEquipmentAttributed();
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function resetPositions()
    {
        if ($this->getConf('positions', false, false, 'bool')) {
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
        }

        $this->setLinesPositions();
    }

    public function setPosition($position)
    {
        $check = true;

        $position = (int) $this->checkPosition($position);

        if (!isset($this->id) || !(int) $this->id) {
            $check = false;
        } elseif ($this->getConf('positions', false, false, 'bool')) {
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
        if ($this->getConf('positions', false, false, 'bool')) {
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
                case self::LINE_TEXT:
//                    if (is_null($this->desc) || !$this->desc) {
//                        $errors[] = 'Description obligatoire';
//                    }
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
                            if ((int) $product->getData('fk_product_type') === 0) {
                                $qty_str = (string) $this->qty;

                                if (preg_match('/.*\..*/', $qty_str)) {
                                    $errors[] = 'Les quantités décimales ne sont autorisées que pour les produits de type "Service". Veuillez corriger';
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
                            if (is_null($this->id_fourn_price) && is_null($this->pa_ht)) {
                                $this->id_fourn_price = (int) $this->getValueByProduct('id_fourn_price');
                            }
                            if (is_null($this->desc) || !(string) $this->desc) {
                                $this->desc = $this->getValueByProduct('desc');
                            }

                            $product = $this->getProduct();

                            if (!(int) $this->id_fourn_price && !(float) $this->pa_ht) {
                                if (!(int) $product->getData('validate') && (float) $product->getData('pa_prevu')) {
                                    $this->pa_ht = (float) $product->getData('pa_prevu');
                                }
                            }

                            if ((int) $this->getData('remisable')) {
                                if (!(int) $product->getData('remisable') || (float) $this->getTotalHT() < 0) {
                                    $this->set('remisable', 0);
                                }
                            }

                            $remise_pa = 0;
                            if (BimpObject::objectLoaded($product)) {
                                $remise_pa = (float) $product->getRemiseCrt();
                            }

                            if ((int) $this->getData('remise_crt')) {
                                if ($remise_pa) {
                                    $this->set('remise_pa', $remise_pa);
                                } else {
                                    $this->set('remise_crt', 0);
                                    $this->set('remise_pa', 0);
                                }
                            } elseif ((int) $this->getInitData('remise_crt')) {
                                if ($remise_pa === (float) $this->getData('remise_pa')) {
                                    $this->set('remise_pa', 0);
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
                        } elseif (preg_match('^\d{4}\-\d{2}\-\d{2}$', (string) $this->date_from)) {
                            $errors[] = 'Date de début invalide';
                            $date_check = false;
                        }

                        if (is_null($this->date_to) || !(string) $this->date_to) {
                            $errors[] = 'Date de fin non spécifiée';
                            $date_check = false;
                        } elseif (preg_match('^\d{4}\-\d{2}\-\d{2}$', (string) $this->date_to)) {
                            $errors[] = 'Date de fin invalide';
                            $date_check = false;
                        }

                        if ($date_check) {
                            if ($this->date_from > $this->date_to) {
                                $errors[] = 'La date de début doit être inférieure à la date de fin';
                            }
                        }
                    }

                    foreach (static::$product_line_data as $field => $params) {
                        if ($this->field_exists('def_' . $field)) {
                            $this->set('def_' . $field, $this->{$field});
                        }
                    }
                    break;
            }
        }
        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        if (!static::$parent_comm_type) {
            $errors[] = 'Impossible de créer une ligne depuis une instance de la classe de base "ObjectLine"';
            return $errors;
        }

        if (!$this->isCreatable($force_create)) {
            return array('Création de la ligne impossible');
        }

        $errors = array();

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

        if (!count($errors)) {
            $errors = parent::create($warnings, $force_create);
        }

        if (!count($errors)) {
            $errors = $this->createLine(false);
            if (count($errors)) {
                $del_warnings = array();
                $this->delete($del_warnings, true);
            } else {
                if ($this->equipment_required) {
                    $warnings = array_merge($warnings, $this->createEquipmentsLines());

                    if (!is_null($equipment)) {
                        $equipment_errors = $this->attributeEquipment((int) $equipment->id);
                        if (count($equipment_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($equipment_errors, 'Echec de l\'attribution de l\'équipement');
                        }
                    }
                }

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
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if (!static::$parent_comm_type) {
            $errors[] = 'Impossible de mettre à jour une ligne depuis une instance de la classe de base "ObjectLine"';
            return $errors;
        }
        $parent = $this->getParentInstance();

        if (!BimpObject::objectLoaded($parent)) {
            return array('Mise à jour de la ligne impossible (objet parent invalide)');
        }

        if (!$this->isEditable(true)) {
            return array(BimpTools::ucfirst($parent->getLabel('the')) . ' n\'a pas le statut "brouillon". Mise à jour de la ligne impossible');
        }

        $line = $this->getChildObject('line');

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

                // Vérification de la quantité de lignes d'équipement et création/suppression si nécessaire: 
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

                // Valeurs par défaut pour les lignes d'équipement: 
                $pu_ht = (float) $this->pu_ht;
                if ($this->field_exists('def_pu_ht')) {
                    $pu_ht = (float) $this->getData('def_pu_ht');
                }
                $tva_tx = (float) $this->tva_tx;
                if ($this->field_exists('def_tva_tx')) {
                    $tva_tx = (float) $this->getData('def_tva_tx');
                }
                $id_fourn_price = (int) $this->id_fourn_price;
                if ($this->field_exists('def_id_fourn_price')) {
                    $id_fourn_price = (float) $this->getData('def_id_fourn_price');
                }
                $pa_ht = (float) $this->db->getValue('product_fournisseur_price', 'price', '`rowid` = ' . (int) $id_fourn_price);

                // Vérification des valeurs des lignes d'équipement et mise à jour si nécessaire: 
                $eq_lines = $this->getEquipmentLines();
                foreach ($eq_lines as $eq_line) {
                    $update_line = false;
                    $equipment = null;

                    if ((int) $eq_line->getData('id_equipment')) {
                        $equipment = $eq_line->getChildObject('equipment');
                        if (!BimpObject::objectLoaded($equipment)) {
                            $eq_line->set('id_equipment', 0);
                            $update_line = true;
                            unset($equipment);
                            $equipment = null;
                        }
                    }

                    if (is_null($equipment) || (!is_null($equipment) && !(float) $equipment->getData('prix_vente_except'))) {
                        if ($pu_ht !== (float) $eq_line->getData('pu_ht')) {
                            $eq_line->set('pu_ht', $pu_ht);
                            $update_line = true;
                        }
                    }

                    if ($tva_tx !== (float) $eq_line->getData('tva_tx')) {
                        $eq_line->set('tva_tx', $tva_tx);
                        $update_line = true;
                    }

                    if (is_null($equipment) || (!is_null($equipment) && !(float) $equipment->getData('prix_achat'))) {
                        if ($pa_ht !== (float) $eq_line->getData('pa_ht')) {
                            $eq_line->set('pa_ht', $pa_ht);
                            $update_line = true;
                        }
                        if ($id_fourn_price !== (int) $eq_line->getData('id_fourn_price')) {
                            $eq_line->set('id_fourn_price', $id_fourn_price);
                            $update_line = true;
                        }
                    }

                    if ($update_line) {
                        $update_warnings = array();
                        $update_errors = $eq_line->update($update_warnings, true);
                        $update_errors = array_merge($update_errors, $update_warnings);
                        if (count($update_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($update_errors, 'Des erreurs sont survenues lors de la mise à jour de la ligne d\'équipement d\'ID ' . $eq_line->id);
                        }
                    }
                }

                if (count($errors)) {
                    return $errors;
                }

                // Calcul des valeurs poyennes selon les équipements: 
                $this->calcValuesByEquipments();
            }
        }

        if (!$this->isRemisable()) {
            $remises = $this->getRemises();
            foreach ($remises as $remise) {
                $del_warnings = array();
                $remise->delete($del_warnings, true);
            }
            unset($this->remises);
            $this->remises = null;
        }

        $initial_remise = (float) $this->getData('remise');
        $this->remise = $initial_remise;

        $errors = parent::update($warnings, $force_update);
        $errors = array_merge($errors, $this->updateLine(false));

        if (!count($errors)) {
            $remises = $this->getRemiseTotalInfos(true);
            if ($initial_remise !== (float) $remises['total_percent']) {
                $this->remise = (float) $remises['total_percent'];
                $this->set('remise', (float) $remises['total_percent']);
                $new_warnings = array();
                $errors = $this->update($new_warnings, $force_update);
            }
            if (!$force_update && BimpObject::objectLoaded($parent)) {
                if ($parent->field_exists('remise_globale') &&
                        (float) $parent->getData('remise_globale')) {
                    $warnings[] = 'Attention: le montant de la remise globale a pu être modifié. Veuillez vérifier.';
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
            }
        }

        return $errors;
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
}

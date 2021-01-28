<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/objects/BE_Place.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

ini_set('max_execution_time', 600);
ini_set('memory_limit', '2048M');


/**
 * Requete test
 * SELECT * FROM `llx_bl_inventory_expected` where `qty_scanned` != IFNULL((SELECT SUM(`qty`) FROM `llx_bl_inventory_det_2` WHERE `fk_warehouse_type` = `id_wt` AND `fk_product` = `id_product`), 0) 

ORDER BY `llx_bl_inventory_expected`.`id_inventory`  DESC
 * 
 */

class Inventory2 extends BimpObject
{

    CONST STATUS_DRAFT = 0;
    CONST STATUS_OPEN = 1;
    CONST STATUS_CLOSED = 2;

    public $isOkForValid = true;

    
    public static $status_list = Array(
        self::STATUS_DRAFT            => Array('label' => 'Brouillon', 'classes'           => Array('success'), 'icon' => 'fas_cogs'),
        self::STATUS_OPEN             => Array('label' => 'Ouvert', 'classes'              => Array('warning'), 'icon' => 'fas_arrow-alt-circle-down'),
        self::STATUS_CLOSED           => Array('label' => 'Fermé', 'classes'               => Array('danger'),  'icon' => 'fas_times')
    );
    
    public $filters = array('incl_categorie',  'excl_categorie',
                            'incl_collection', 'excl_collection', 
                            'incl_nature',     'excl_nature', 
                            'incl_famille',    'excl_famille',
                            'incl_gamme',      'excl_gamme',
                            'incl_product',    'excl_product');
    
    public $filters_radical = array('categorie', 
                                    'collection',
                                    'nature',    
                                    'famille',   
                                    'gamme',
                                    'product'); 
    
    public static $types;
    
    public function __construct($module, $object_name) {
        self::$types = BE_Place::$types;
        parent::__construct($module, $object_name);
    }
    
    public function fetch($id, $parent = null) {
        $return = parent::fetch($id, $parent);
        
        if (!defined('MOD_DEV')) {
            
            $requete = "SELECT MIN(e.id) as id, (SUM(`qty_scanned`)/IF(count(DISTINCT(d.id)) >= 1,count(DISTINCT(d.id)),1)) as scan_exp, IFNULL((SUM(d.`qty`)/count(DISTINCT(e.id))), 0) as scan_det, id_product 
FROM `llx_bl_inventory_expected` e 
LEFT JOIN llx_bl_inventory_det_2 d ON `fk_warehouse_type` = `id_wt` AND `fk_product` = `id_product` 
WHERE id_inventory = " . $this->id . " 
GROUP BY id_wt, id_product 
HAVING scan_exp != scan_det";
            

            $sql1 = $this->db->db->query($requete);
            
            
            if($this->db->db->num_rows($sql1) > 0){
                $this->isOkForValid = false;
                $text = "Inchoérence detecté dans inventaire : ".$this->getData('id');
                while ($ln = $this->db->db->fetch_object($sql1))
                        $text .= "<br/>Ln expected ".$ln->id. ' : ' . $ln->scan_det . " det / ".$ln->scan_exp." exp id_prod = ".$ln->id_product;
                mailSyn2 ('Incohérence inventaire', 'dev@bimp.fr', null, $text);
                echo 'attention ' . $text;
            }

            $sql2 = $this->db->db->query(
                     "SELECT COUNT(*), min(id) as minId, max(id) as maxId "
                    . "FROM `llx_bl_inventory_det_2` "
                    . "WHERE `fk_inventory` = ".$this->getData('id')." AND `fk_equipment` > 0 "
                    . "GROUP BY `fk_equipment` HAVING COUNT(*) > 1");
            if($this->db->db->num_rows($sql2) > 0){
                $this->isOkForValid = false;
                $text = "Inchoérence detecté dans les scann de l'inventaire : ".$this->getData('id');
                while ($ln = $this->db->db->fetch_object($sql2))
                        $text .= "<br/>Ln de scanne ".$ln->minId." et ln de scann ".$ln->maxId." identique";
                mailSyn2 ('Incohérence inventaire', 'dev@bimp.fr', null, $text);
            }
        }
        return $return;
    }
    
    public function displayCompletion(){
        $percent = 0;
        $return = '';
        $info = "";
        if($this->getData('status') == self::STATUS_OPEN){
            $sql = $this->db->db->query('SELECT SUM(`qty_scanned`) as scan, SUM(`qty`) as att FROM `llx_bl_inventory_expected` WHERE `id_inventory` = '.$this->id);
            if($this->db->db->num_rows($sql)){
                $ln = $this->db->db->fetch_object($sql);
                if($ln->scan > 0 && $ln->att > 0)
                    $percent = $ln->scan / $ln->att * 100;
                $info = $ln->scan.' / '.$ln->att;
            }
        }
        elseif($this->getData('status') == self::STATUS_CLOSED)
            $percent = 100;
        
        $return = price($percent). ' %';
        if($info != "")
            $return .= ' ('.$info.')';
        return $return;
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        
        $errors = array();
        $ids_prod = array();
                
        $has_filter = (int) BimpTools::getValue('filter_inventory');
        
        $errors = BimpTools::merge_array($errors, $this->checkDuplicateIncludeExclude());
        
        if(!empty($errors))
            return $errors;
        
        $this->setFilters($has_filter);
        
        if($has_filter and !$this->hasPostedFilter()) {
            $errors[] = "Vous n'avez rentré aucun filtre alors que l'inventaire est partiel.";
            return $errors;
        }
        

        
        $warehouse_and_type = BimpTools::getValue('warehouse_and_type');
        unset($warehouse_and_type[0]);
        
        // Définition de l'entrepot par défault
        list($w_main, $t_main) = explode('_', $warehouse_and_type[1]);
        $this->data['fk_warehouse'] = (int) $w_main;
        $this->data['type'] = (int) $t_main;
        
        
//        $this->data['id_package_vol'] = 0;
//        $this->data['id_package_nouveau'] = 0;
        
        if(!empty($errors))
            return $errors;
                
        // Création de l'inventaire
        $errors = BimpTools::merge_array($errors, parent::create($warnings, $force_create));
        
        // Création des packages
        $errors = BimpTools::merge_array($errors, $this->createPackageVol());
        $errors = BimpTools::merge_array($errors, $this->createPackageNouveau());

        // MAJ des champ id_package_vol et id_package_nouveau
        $errors = BimpTools::merge_array($errors, $this->updateField('id_package_vol', $this->temp_package_vol));
        $errors = BimpTools::merge_array($errors, $this->updateField('id_package_nouveau', $this->temp_package_nouveau));
        
        $errors = BimpTools::merge_array($errors, $this->createWarehouseType($warehouse_and_type, $w_main, $t_main));
                
        return $errors;
    }
    
    
    /**
     * Obtention du prochain id qui sera inséré pour cette table
     */
    public function getNextInsertId() {
        global $conf;
        
        $sql  = 'SELECT AUTO_INCREMENT as ai FROM information_schema.tables';
        $sql .= ' WHERE table_name = "' . MAIN_DB_PREFIX. $this->getTable() .'"';
        $sql .= ' AND table_schema = "' . $conf->db->name . '"';
        
        $rows = $this->db->executeS($sql);


        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $row) {
                return $row->ai;
            }
        }
        
        return -1;
    }
    
    private function createWarehouseType($warehouse_and_type, $w_main, $t_main) {
        $errors = array();
        // Création des couple entrepôt/type
        foreach($warehouse_and_type as $wt) {
            list($w, $t) = explode('_', $wt);
            $wt_obj = BimpObject::getInstance($this->module, 'InventoryWarehouse');
            $errors = BimpTools::merge_array($errors, $wt_obj->validateArray(array(
                'fk_inventory' => (int) $this->getData('id'),
                'fk_warehouse' => (int) $w,
                'type'         => (int) $t,
                'is_main'      => ((int) $w_main == (int) $w and (int) $t_main == (int) $t)  ? 1 : 0
            )));
            $errors = BimpTools::merge_array($errors, $wt_obj->create());
        }
        
        return $errors;
    }

    /**
     * Attention, vérifier qu'il n'y ai pas un expected qui existe pour ce prod
     */
    private function createExpected($allowed = false, $in_or_not_in = 'in', $has_filter = 0) {
        
        $errors = array();

        if(!is_array($allowed))
            $allowed = $this->getAllowedProduct(0);
        
        
        foreach($this->getWarehouseType() as $wt) {
            
            // Products
            if((int) $this->getData('filter_inventory') == 1 or $has_filter)
                $prods = $wt->getProductStock($allowed, $in_or_not_in);
            else
                $prods = $wt->getProductStock(0, $in_or_not_in);
                        
            foreach($prods as $id_prod => $prod) {
                foreach($prod as $datas) {
                    global $db;
                    $sql = $db->query("SELECT * FROM llx_bl_inventory_expected WHERE id_inventory = ".$this->getData('id')." AND id_wt = ".$wt->getData('id')." AND id_package = ".$datas['id_package']. " AND id_product = ".$id_prod);
                    if($db->num_rows($sql) == 0){
                        $expected = BimpObject::getInstance($this->module, 'InventoryExpected');
                        $errors = BimpTools::merge_array($errors, $expected->validateArray(array(
                            'id_inventory'   => (int)   $this->getData('id'),
                            'id_wt'          => (int)   $wt->getData('id'),
                            'id_package'     => (int)   $datas['id_package'],
                            'id_product'     => (int)   $id_prod,
                            'qty'            => (int)   $datas['qty'],
                            'ids_equipments' => (array) array(),
                            'serialisable'   => 0
                        )));
                        $errors = BimpTools::merge_array($errors, $expected->create());
                    }
                }
            }
            
            // Equipments
            if((int) $this->getData('filter_inventory') == 1 or $has_filter)
                $pack_prod_eq = $wt->getEquipmentStock(2, $allowed, $in_or_not_in);
            else
                $pack_prod_eq = $wt->getEquipmentStock(2, 0, $in_or_not_in);
            
//            echo 'fezfefe<pre>';
//            print_r($pack_prod_eq);
//            die();
            
                  
            foreach($pack_prod_eq as $id_package => $prod_eq) {
                
                foreach($prod_eq as $id_prod => $ids_equipments) {
                    global $db;
                    $sql = $db->query("SELECT * FROM llx_bl_inventory_expected WHERE id_inventory = ".$this->getData('id')." AND id_wt = ".$wt->getData('id')." AND id_package = ".$id_package. " AND id_product = ".$id_prod);
                    if($db->num_rows($sql) == 0){
                    
                        $expected = BimpObject::getInstance($this->module, 'InventoryExpected');
                        $errors = BimpTools::merge_array($errors, $expected->validateArray(array(
                            'id_inventory'   => (int)   $this->getData('id'),
                            'id_wt'          => (int)   $wt->getData('id'),
                            'id_package'     => (int)   $id_package,
                            'id_product'     => (int)   $id_prod,
                            'qty'            => (int)   sizeof($ids_equipments),
                            'ids_equipments' => (array) $ids_equipments,
                            'serialisable'   => 1
                        )));
                        $errors = BimpTools::merge_array($errors, $expected->create());
                    }
                    
                }
            }
        }
        
        return $errors;
        
    }
    
    public function createScanNegative() {

        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine2');
        $expected = BimpObject::getInstance($this->module, 'InventoryExpected');
        $l_e = $expected->getList(array(
            'id_inventory' => (int) $this->id
        ), null, null, 'id', 'DESC', 'object', array('id_wt', 'id_package', 'id_product', 'qty', 'ids_equipments'));
        
        foreach($l_e as $e) {
                        
            if(empty(json_decode($e->ids_equipments)) and $e->qty < 0) {
                $errors = BimpTools::merge_array($errors, $inventory_line->validateArray(array(
                    'fk_inventory'      => (int) $this->getData('id'),
                    'fk_product'        => (int) $e->id_product,
                    'fk_equipment'      => (int) 0,
                    'fk_warehouse_type' => (int) $e->id_wt,
                    'fk_package'        => (int) $e->id_package,
                    'qty'               => (int) $e->qty,
                )));

                if (!count($errors)) {
                    $errors = BimpTools::merge_array($errors, $inventory_line->create());            
                }
            }
            
        }
        
    }


    public function getActionsButtons() {
        global $user;
        $buttons = array();
        if (!$this->isLoaded())
            return $buttons;

        if ($this->getData('status') == self::STATUS_DRAFT) {
            $buttons[] = array(
                'label'   => 'Commencer l\'inventaire',
                'icon'    => 'fas_box',
                'onclick' => $this->getJsActionOnclick('setSatus', array("status" => self::STATUS_OPEN), array(
                    'success_callback' => 'function(result) {bimp_reloadPage();}')
                )
            );
        }
        
        if ($this->getData('status') == self::STATUS_OPEN){
            $buttons[] = array(
                'label'   => 'Recalculer attendu',
                'icon'    => 'fas_box',
                'onclick' => $this->getJsActionOnclick('createExpected', array(), array(
                    'success_callback' => 'function(result) {bimp_reloadPage();}')
                )
            );
        }

        
        if ($this->getData('status') == self::STATUS_OPEN && $this->isOkForValid) {
            if ($user->rights->bimpequipment->inventory->close) {
                $buttons[] = array(
                    'label'   => 'Fermer l\'inventaire',
                    'icon'    => 'fas_window-close',
                    'onclick' => $this->getJsActionOnclick('setSatus', array("status" => self::STATUS_CLOSED), array(
                        'form_name'        => 'confirm_close',
                        'success_callback' => 'function(result) {bimp_reloadPage();}'
                    ))
                );
            }
        }
        
//        if ($user->rights->bimpequipment->inventory->close) {
//            $onclick = $this->getJsActionOnclick('test22');
//            $buttons[] = array(
//                'label'   => 'Editer les filtre',
////                'icon'    => 'fas_check',
////                'type'    => 'danger',
//                'onclick' => $this->getJsActionOnclick('change_filter', array(), array(
//                        'form_name'        => 'confirm_close',
//                        'success_callback' => 'function(result) {bimp_reloadPage();}'
//                ))
//            );
//        }

        return $buttons;
    }
    
    public function isEditable($force_edit = false, &$errors = array()) {
        
        if((int) $this->getData('status') < self::STATUS_CLOSED)
            return 1;
        
        return 0;
    }
    
    /*
     * Requiere de faire
     * $init_filters = $this->getFiltersValue();
     * avant de modifier les filtres
     */
    
    public function resetFilters($init_filters, $init_has_filter) {
        foreach($init_filters as $field => $value)
            $this->setSerializedValue($field, $value);
        
        $this->updateField('filter_inventory', $init_has_filter);
    }
    
    public function update(&$warnings = array(), $force_update = false) {
        

        $errors = array();
        
        $init_filters = $this->getFiltersValue();
        
        $init_has_filter = $this->getInitData('filter_inventory');
        
        $errors = BimpTools::merge_array($errors, $this->checkDuplicateIncludeExclude());
        
        if(!empty($errors))
            return $errors;
                
        
        // TODO update déclenché depuis une autre classe ?
        
        $has_filter = (int) BimpTools::getPostFieldValue('filter_inventory');
        $delete_scan_and_exp =  (int) BimpTools::getPostFieldValue('delete_scan_and_exp');
        
        
        if((int) $this->getData('status') == self::STATUS_OPEN) {
            
            $old_cache = $this->getAllowedProduct(0); // sans raffraichir le cache de prod

            $errors = BimpTools::merge_array($errors, $this->setFilters($has_filter, 'update'));
            
            $new_cache = $this->getAllowedProduct(1); // en raffraichissant le cache de prod
           
            // Ajout de filtre
            if(isset($old_cache['all']) and !isset($new_cache['all'])) {
                
                if($delete_scan_and_exp) {
                    $this->cleanScanAndExpected($new_cache);

                } else {
                    $errors = BimpTools::merge_array($errors, $this->getScanAndExpectedToClean($new_cache, 'not_in'));
                    if(empty($errors)) {
                        $this->cleanScanAndExpected($new_cache);
                    } else
                        $this->resetFilters($init_filters, $init_has_filter);
                }
                
            // Suppression de filtre
            } elseif(isset($new_cache['all']) and !isset($old_cache['all'])) {

                $add = array_diff($new_cache, $old_cache);
                $this->createExpected($old_cache, 'not_in', 1);
               
            // Modification du filtre
            } elseif(!isset($old_cache['all']) and !isset($new_cache['all'])) {
                
                if($delete_scan_and_exp) {
                    $add = array_diff($new_cache, $old_cache);
                    $this->cleanScanAndExpected($new_cache);
                    $this->createExpected($add);

                } else {
                    $errors = BimpTools::merge_array($errors, $this->getScanAndExpectedToClean($new_cache, 'not_in'));
                    if(empty($errors)) {
                        $this->cleanScanAndExpected($new_cache);
                        $add = array_diff($new_cache, $old_cache);
                        $this->createExpected($add);
                    } else
                        $this->resetFilters($init_filters, $init_has_filter);
                }
                
            }
            
        } elseif((int) $this->getData('status') == self::STATUS_DRAFT) {
            $errors = BimpTools::merge_array($errors, $this->setFilters($has_filter, 'update'));
        }

        $errors = BimpTools::merge_array($errors, parent::update($warnings, $force_update));
        
        return $errors;

    }
    
    public function checkDuplicateIncludeExclude() {
        
        $errors = array();
        
        $posted_filters = $this->getPostedFilterData();
        
        foreach($posted_filters as $name => $data)
            $$name = $data;
            
        foreach($this->filters_radical as $radical) {
            
            $inter = array_intersect(${'incl_' . $radical},${'excl_' . $radical});

            if(!empty($inter)) {
                
                if($radical == 'product') {

                    foreach($inter as $id_prod) {
                        
                        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);
                        
                        $errors[] = "La ref " . $prod->getRef()
                            . " du champ produit est présente dans les inclusions et les exlusions"
                            . ", merci de corriger.";
                        
                    }
                    
                } else {
                    
                    self::loadClass('bimpcore', 'Bimp_Product');
                    $select_options = Bimp_Product::getValues8sens($radical);

                    foreach($inter as $id_option)
                        $errors[] = "L'entrée " . $select_options[$id_option]
                            ." du champ " . $radical
                            . " est présente dans les inclusions et les exlusions"
                            . ", merci de corriger.";
                    
                }
            }
            
        }
        
        return $errors;
    }
    
    public function getPostedFilterData() {
                
        $incl_categorie = BimpTools::getPostFieldValue('incl_categorie');
        $excl_categorie = BimpTools::getPostFieldValue('excl_categorie');
                
        $incl_collection = BimpTools::getPostFieldValue('incl_collection');
        $excl_collection = BimpTools::getPostFieldValue('excl_collection');
        
        $incl_nature = BimpTools::getPostFieldValue('incl_nature');
        $excl_nature = BimpTools::getPostFieldValue('excl_nature');
        
        $incl_famille = BimpTools::getPostFieldValue('incl_famille');
        $excl_famille = BimpTools::getPostFieldValue('excl_famille');

        $incl_gamme = BimpTools::getPostFieldValue('incl_gamme');
        $excl_gamme = BimpTools::getPostFieldValue('excl_gamme');
        
        $incl_product = $this->getPostedIdsProducts('incl_product');
        $excl_product = $this->getPostedIdsProducts('excl_product');
                
        $return = array();
        
        foreach($this->filters as $f) {
            
            if(is_array($$f)) {
                foreach($$f as $k => $v) {
                    if(!(int) $v > 0)
                        unset($$f[$k]);
                }
            } else
                $return[$f] = array();
                
                
            $return[$f] = $$f;

        }
        
        return $return;
    }
    
    public function getPostedIdsProducts($input_name) {
        
        // Filtre par produit directement
        $ids_prod = array();

        foreach($_POST as $key => $inut) {
            
            if(preg_match('/^prod_' . $input_name . '_[0-9]+/', $key)) {
                $new_id = BimpTools::getPostFieldValue($key);
                if(!isset($ids_prod[$new_id]) and 0 < $new_id)
                    $ids_prod[$new_id] = $new_id;
            }
            
        }
        
        return array_values($ids_prod);
    }
    
    public function getScanAndExpectedToClean($allowed_products, $in_not_in = 'not_in') {
        $errors = array();
        
        if(empty($allowed_products)) {
            $errors = "Aucun produits autorisé (méthode : getScanAndExpectedToClean";
            return $errors;
        }
        
        $sql = 'SELECT SUM(qty) as sum_expected, SUM(qty_scanned) as sum_scanned, id_product';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory_expected';
        $sql .= ' WHERE id_inventory=' . (int) $this->id;
        $sql .= ' AND id_product ';
        $sql .= ($in_not_in == 'in') ? 'IN' : 'NOT IN';
        $sql .= '(' . implode(',', array_keys($allowed_products)) . ')';
        $sql .= ' GROUP BY id_product ';
        $sql .= ' ORDER BY sum_scanned DESC ';
        $list_exp = $this->db->executeS($sql);

        if (!is_null($list_exp) and sizeof($list_exp) != 0) {
            if(sizeof($list_exp) > 1)
                $error = sizeof($list_exp) . ' lignes vont être supprimées si vous <strong>forcer l\'édition</strong>:<br/>';
            else
                $error = sizeof($list_exp) . ' ligne va être supprimée si vous <strong>forcer l\'édition</strong>:<br/>';
            foreach ($list_exp as $line) {
                $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $line->id_product);
                $error .= $prod->getRef() .  " scanné ";
                $error .= (((int)$line->sum_scanned > 0) ? '<strong>' : '');
                $error .= $line->sum_scanned . '/' . $line->sum_expected;
                $error .= (((int)$line->sum_scanned > 0) ? '</strong>' : '') . ' ';
                $error .= $prod->getData('label') . '<br/>';
            }
            $errors[] = $error;

        }
        
        return $errors;
    }
    
    /**
     * Supprime toutes les ligne de scan et les lignes
     */
    private function cleanScanAndExpected($allowed_products, $in_not_in = 'not_in') {
        $errors = array();
                
        // Delete Scan
        $filters_scan =  array(
                'fk_product' => array(
                    $in_not_in => array_keys($allowed_products)
                    ),
                'fk_inventory' => (int) $this->id
        );
        
        $line_scan = BimpCache::getBimpObjectInstance($this->module, 'InventoryLine2');
        $line_scan->deleteBy($filters_scan, $errors);
        
        
        // Delete Expected
        $filters_exp =  array(
                'id_product' => array(
                    $in_not_in => array_keys($allowed_products)
                    ),
                'id_inventory' => (int) $this->id
        );
        
        $line_exp = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');
        $line_exp->deleteBy($filters_exp, $errors);
        
        return $errors;
    }
    
    public static function renderWarehouseAndType() {
        
        if(self::loadClass('bimplogistique', 'InventoryWarehouse'))
            $options = InventoryWarehouse::getAllWarehouseAndType();
        
        $input_name = 'warehouse_and_type';
        $html = '';

        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array('options'     => $options));

        $html .= '<p class="inputHelp">';
        $html .= 'Sélectionnez un entrepôt assoscié à son type puis cliquez sur "Ajouter"<br/>';
        $html .= '<strong>Attention ! </strong> Le premier couple entrepôt/type sera celui ';
        $html .= 'dans lequel on placera les produits en trop lors des fermeture d\'inventaire.<br/>';
        $html .= 'C\'est également celui qui sera rempli en dernier lors des mouvements de stock.<br/>';
        $html .= 'Si <strong>il manque une entrée </strong> c\'est parce qu\'un inventaire (brouillon ou ouvert) ';
        $html .= 'utilise déjà cette association entrepôt-type.';
        $html .= '</p>';

        $html .= '<div style="display: none; margin-top: 10px">';
        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
        $html .= '</div>';

        return $html;
        
    }
    
    /**
     * 
     * @param string $input_name
     * @param type $type (gamme, collection, famille etc)
     */
    public static function renderCats($input_name, $type) {

        self::loadClass('bimpcore', 'Bimp_Product');
        $options = Bimp_Product::getValues8sens($type);
        
        $html = '';

        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array('options'     => $options));

        $html .= '<div style="display: none; margin-top: 10px">';
        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
        $html .= '</div>';

        return $html;
        
    }
    
    public function retrieveFilters() {
        if((int) $this->id > 0)
            return array(2851 => 'DEPLACEMENT PRO');
    }
    
    public function filterssssss() {
        return array(2851 => 'DEPLACEMENT PRO');
    }
    
    
    public function renderInputs() {
        $html = '';

        if ($this->isLoaded()) {
            if ((int) $this->getData('status') != (int) self::STATUS_OPEN) {
                $html = BimpRender::renderAlerts('Le statut de l\'inventaire ne permet pas d\'ajouter des lignes', 'info');
            } else {
                $header_table = '<span style="margin-left: 100px">Ajouter</span>';
                $header_table .= BimpInput::renderInput('search_product', 'insert_line', '', array('filter_type' => 'both'));
                $header_table .= "<script>initEvents2();</script>";
                $header_table .= '<span style="margin-left: 100px">Quantité</span>';
                $header_table .= '<input name="insert_quantity" type="number" style="width: 80px; margin-left: 10px;" value="1" >';

                $html = BimpRender::renderPanel($header_table, $html, '', array(
                            'foldable' => false,
                            'type'     => 'secondary',
                            'icon'     => 'fas_plus-circle',
                ));
            }
        }
        
        $html .= '<div id="allow_sound"></div>';

        return $html;
    }
    
    public function actionCreateExpected(){
        return $this->createExpected();
    }
    
    public function actionSetSatus($data = array(), &$success = '') {
        $errors = array();
        $status = (int) $data['status'];

        $init_status = $this->getInitData('status');

        $this->updateField("status", $status);

        // Open
        if ($status == self::STATUS_OPEN) {
            $errors = BimpTools::merge_array($errors, $this->createExpected());
//            $errors = BimpTools::merge_array($errors, $this->createScanNegative());
            if(empty($errors))
                $this->updateField("date_opening", date("Y-m-d H:i:s"));
            
        // Close
        } elseif($status == self::STATUS_CLOSED) {
            $errors = BimpTools::merge_array($errors, $this->close());
            $date_mouvement = BimpTools::getPostFieldValue('date_mouvement');
            if (!$this->setDateMouvement($date_mouvement))
                $errors[] = "Erreur lors de la définition de la date du mouvement";
            
            $this->updateField("date_closing", date("Y-m-d H:i:s"));
        } else {
            $errors[] = "Statut non reconnu, valeur = " . $status;
        }
        
        if(!empty($errors)) 
            $this->updateField("status", $init_status);


        return $errors;
    }
    
    public function canCreate() {
        return $this->isAdmin();
    }
    

    public function canDelete() {
        return $this->isAdmin();
    }
    
    
    public function isDeletable($force_delete = false, &$errors = array()) {
        if ($this->getData('status') == self::STATUS_DRAFT) {
            return 1;
        }
        return 0;
    }
    
    public function delete(&$warnings = array(), $force_delete = false) {
        
        $pack_nouv = BimpObject::getInstance('bimpequipment', 'BE_Package', (int) $this->getData('id_package_nouveau'));
        if(!$pack_nouv->hasEquipments() and !$pack_nouv->hasProducts())
            $pack_nouv->delete();
        else
            $warnings[] = "Le package nouveau " . $pack_nouv->getNomUrl() . " n'a pas été supprimé " .
                "car il contient un(des) produit(s).";

        
        $pack_vol = BimpObject::getInstance('bimpequipment', 'BE_Package', (int) $this->getData('id_package_vol'));
        if(!$pack_vol->hasEquipments() and !$pack_vol->hasProducts())
            $pack_vol->delete();
        else
            $warnings[] = "Le package nouveau " . $pack_vol->getNomUrl() . " n'a pas été supprimé " .
                "car il contient un(des) produit(s).";        
        
        return parent::delete($warnings, $force_delete);
        
    }
    
    public function isFieldEditable($field, $force_edit = false) {
        if($field == 'id_filter_product')
            return 1;
        return parent::isFieldEditable($field, $force_edit);
    }
        
    public function isAdmin() {
        global $user;
        
        if($user->rights->bimpequipment->inventory->create)
            return 1;
        return 0;
    }

    public function getWarehouseType() {
        $warehouse_type = array();
        
        $inventory_warehouse = BimpObject::getInstance($this->module, 'InventoryWarehouse');
        $tab_tab_ids = $inventory_warehouse->getList(array('fk_inventory' => $this->getData('id')), null, null, 'is_main', 'asc', 'array', array('id'));
        foreach($tab_tab_ids as $tab_id) {
            $warehouse_type[] = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', $tab_id['id']);
        }

        return $warehouse_type;
    }
    
    /**
     * Search the $fk_warehouse_type befor inserting it 
     */
    public function insertLineProduct($id_product, $qty_input, &$errors) {
        
        $return = array();
        
        $diff = $this->getDiffProduct();
//        
        end($diff);
        $fk_main_wt = key($diff);
        
        $id_package_nouveau = (int) $this->getPackageNouveau();
        
        foreach ($diff as $fk_wt => $package_prod_qty) { // Itération sur warehouse_type
            
            foreach($package_prod_qty as $id_package => $prod_qty) { // Itération sur les packages
                
                if((int) $id_package == $id_package_nouveau)
                    continue;

                $values = $prod_qty[$id_product];
                if(!is_array($values))
                    continue;
                
                if($values['stock'] < 0)
                    continue;
                
                // Qty positive
                if(0 < $qty_input) {
                    
                    // On en attends
                    if($values['diff'] < 0) {
                        
                        // On en attends cette quantité ou +
                        if ($qty_input <= -$values['diff']) {
                            $return[] = $this->createLine($id_product, 0, $qty_input, $fk_wt, $id_package, $errors);
                            return $return;
                            
                         // On en attends pas autant
                        } elseif (-$values['diff'] < $qty_input) {
                            $qty_input += $values['diff'];
                            $return[] = $this->createLine($id_product, 0, -$values['diff'], $fk_wt, $id_package, $errors);
                        }
                        
                    }
                    
                // Qty négative
                } else {
                    
                    // On ne peut pas enlever de quantité ici
                    if($values['nb_scan'] <= 0)
                        continue;
                    
                    $max_removable = -$values['nb_scan'];
                    
                    // On peut enlever cette quantité de scan
                    if($max_removable <= $qty_input) {
                        $return[] = $this->createLine($id_product, 0, $qty_input, $fk_wt, $id_package, $errors);
                        return $return;
                        
                    // On veut en enlever plus qu'on en a scanné
                    } else {
                        $qty_input -= $max_removable;
                        $return[] = $this->createLine($id_product, 0, $max_removable, $fk_wt, $id_package, $errors);                        
                    }
                    
                }
                
            } // fin package

            // On atteint le dernier wt, on ne l'a trouver dans aucun package
            // insertion directe et fin de boucle
            if((int) $fk_main_wt == (int) $fk_wt and $qty_input != 0) {
                $return[] = $this->createLine($id_product, 0, $qty_input, $fk_wt, $id_package_nouveau, $errors);
                break;
            }
            
        } // fin warehouse_type
        
        return $return;
    }
    
    /**
     * Search the $fk_warehouse_type befor inserting it 
     */
    public function insertLineEquipment($id_product, $id_equipment, &$errors) {
        
        $diff = $this->getDiffEquipment();
        
        $data = $diff[$id_equipment];
        
        
        return $this->createLine($id_product, $id_equipment, 1, $data['id_wt'], $data['id_package'], $errors);
    }
    
    
    public function createLine($id_product, $id_equipment, $qty, $fk_warehouse_type, $fk_package, &$errors) {
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine2');
        
        // Si le wt n'est pas renseigné, on le met dans celui par défault
        if((int) $fk_warehouse_type == 0) {
            $wt = $this->getMainWT();
            $fk_warehouse_type = (int) $wt->id;
            if(0 == (int) $fk_package)
                $fk_package = $this->getPackageNouveau();
        }

        $errors = BimpTools::merge_array($errors, $inventory_line->validateArray(array(
            'fk_inventory'      => (int) $this->getData('id'),
            'fk_product'        => (int) $id_product,
            'fk_equipment'      => (int) $id_equipment,
            'fk_warehouse_type' => (int) $fk_warehouse_type,
            'fk_package'        => (int) $fk_package,
            'qty'               => (int) $qty,
        )));

        if (!count($errors)) {
            $errors = BimpTools::merge_array($errors, $inventory_line->create());
        } else
            $errors[] = "Erreur lors de la validation des données renseignées";


        return (int) $inventory_line->id;
    }
    
    public function getDiffEquipment($key_wt = false) {
        
        $diff = array();
        
        if(!$key_wt) {
            foreach ($this->getWarehouseType() as $wt)
                $diff = array_replace($diff, $wt->getScanExpectedEquipment());
        } else {
            foreach ($this->getWarehouseType() as $wt)
                $diff[$wt->id] = $wt->getScanExpectedEquipment();
        }
        
        return $diff;
    }
    
    /**
    * Attention si $fk_product est renseigné, $fk_package et $fk_warehouse_type doivent l'être aussi
    * Attention si $fk_package est renseigné, $fk_warehouse_type doit l'être aussi
     */
    public function getDiffProduct($fk_warehouse_type = false, $fk_package = false, $fk_product = false) {
        
        $diff = array();
        
        foreach ($this->getWarehouseType() as $wt) // Pour chaque couple entrepôt/type
            $diff[(int) $wt->id] = $wt->getScanExpectedProduct();
        
        
        
        return $diff;
    }
    
    public function renderStock() {
        $html = '';
        
        
        foreach($this->getWarehouseType() as $key => $wt) {
            $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $wt->id);
            $html .= '<h3>' . $inventory_warehouse->renderName() . '</h3>';
            
            $expected = BimpObject::getInstance($this->module, 'InventoryExpected');
            $list = new BC_ListTable($expected);
            $list->addFieldFilterValue('id_wt', $wt->id);
            $list->identifier .= '_' . $key;
            $html .= $list->renderHtml();
        }

        return $html;
    }
    
    public function renderDifferenceTab() {
        $html = '';
        
        $expected = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');
        
        foreach($this->getWarehouseType() as $key => $wt) {
            $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $wt->id);
            $titre = $inventory_warehouse->renderName();
            
            $list = new BC_ListTable($expected, 'default', 1,  null, $titre);
            $list->addFieldFilterValue('qty != qty_scanned AND 1', '1');
            $list->addFieldFilterValue('id_wt', $wt->getData('id'));
            $list->addIdentifierSuffix($key . '_');
            $html .= $list->renderHtml();
        }

        return $html;
    }
    
    public function renderDifferenceProduct() {
        $html = '';

        $diff = $this->getDiffProduct();
        
        foreach ($diff as $id_wt => $package_prod_qty) {
            
            $wt_obj = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', $id_wt);
            
            $errors = '<h2>' . $wt_obj->renderName(). '</h2>' ;
            $has_diff = false;

            foreach($package_prod_qty as $id_package => $prod_qty) {
                
                if((int) $id_package > 0) {
                    $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);
                    $package_ref = 'package ' . $package->getData('ref');
                } else
                    $package_ref = 'stock';

                foreach($prod_qty as $id_product => $data) {
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
                    if($data['diff'] != 0) {
                        $has_diff = true;
                        $errors .= 'Le produit ' . $product->getData('ref') . ' a été scanné <strong>' . (float) $data['nb_scan'] .
                                '</strong> fois et il est présent <strong>' . (float) $data['stock'] . 
                                '</strong> fois dans le ' . $package_ref .
                                ' il va être modifié de <strong>' . ((0 < (float) $data['diff']) ? '+' : '') . (float) $data['diff'] . '</strong>.<br/>';
                    }
                }
            }
            
            if(!$has_diff) {
                $msg = $errors . "Aucun mouvements prévu.";
                $html .= BimpRender::renderAlerts($msg, 'success');
            } else 
                $html .= BimpRender::renderAlerts($errors, 'warning');
            
        }

        return $html;
    }
    
    public function renderDifferenceEquipments() {
        
        $diff = $this->getDiffEquipment(true);
        $html = '';
        
        foreach ($diff as $id_wt => $equip_data) {
            
            $wt_obj = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', $id_wt);
            
            $errors = '<h2>' . $wt_obj->renderName(). '</h2>' ;
            $has_diff = false;

            foreach($equip_data as $id_equipment => $data) {
                
                if((int) $data['code_scan'] == 1)
                    continue;
                
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $equipment->getData('id_product'));
                
                if((int) $data['code_scan'] == 0) {
                    $has_diff = true;
                    
                    $placeReel = $equipment->getCurrentPlace();
                    $msg = '';
                    
                    if($wt_obj->getData('fk_warehouse') != $placeReel->getData('id_entrepot'))
                        $msg .= 'L\'équipement '.$equipment->getNomUrl().' n\'est plus dans le dépot <strong>'.$wt_obj->displayData('fk_warehouse'). '</strong> mais dans le dépot <strong>'.$placeReel->displayData('id_entrepot') . '</strong>';
                    if((int) $wt_obj->getData('type') != (int) $placeReel->getData('type')) {
                        if($msg == '')
                            $msg .= 'L\'équipement '.$equipment->getNomUrl().' n\'est plus en emplacement de type <strong>'.$wt_obj->displayData('type'). '</strong> mais de type <strong>'.$placeReel->displayData('type') . '</strong>';
                        else
                            $msg .= ' et n\'est plus en emplacement de type <strong>'.$wt_obj->displayData('type'). '</strong> mais de type <strong>'.$placeReel->displayData('type') . '</strong>';
                    }
                    
                    if((int) $data['id_package'] != (int) $equipment->getData('id_package')
                        and (int) $data['id_package'] != (int) $this->getPackageNouveau()) {
                        
                        // Où il était sensé être
                        if(0 < (int) $data['id_package']) {
                        
                            $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $data['id_package']);

                            if($msg == '')
                                $msg .= 'L\'équipement '.$equipment->getNomUrl().' n\'est plus dans le package '. $package->getNomUrl() . (((int) $equipment->getData('id_package') > 0) ? ' mais dans '.$equipment->displayData('id_package', 'nom_url') : '');
                            else
                                $msg .= ' et n\'est plus dans le package '. $package->getNomUrl() . (((int) $equipment->getData('id_package') > 0) ? ' mais dans '.$equipment->displayData('id_package', 'nom_url') : '');
                            
                        } else {
                            
                            if($msg == '')
                                $msg .= 'L\'équipement '.$equipment->getNomUrl().' est maintenant ' . (((int) $equipment->getData('id_package') > 0) ? ' dans '.$equipment->displayData('id_package', 'nom_url') : ' sans package');
                            else
                                $msg .= ' et maintenant ' . (((int) $equipment->getData('id_package') > 0) ? ' dans '.$equipment->displayData('id_package', 'nom_url') : ' sans package');
                        
                        }
                        
                    }
                            
                    
                    if($msg == '')
                        $errors .= "Le produit sérialisé " . $product->getData('ref') .
                             " " . $equipment->getNomUrl() . " n'a pas été scanné.<br/>";
                    else 
                        $errors .= $msg . '<br/>';
                    
                } elseif ((int) $data['code_scan'] == 2) {
                    $has_diff = true;
                    $errors .= "Le produit sérialisé " . $product->getData('ref') .
                         " " . $equipment->getNomUrl() . " a été scanné en excès.<br/>";
                }
            }
            
            if(!$has_diff) {
                $msg = $errors . "Aucun mouvements prévu.";
                $html .= BimpRender::renderAlerts($msg, 'success');
            } else
                $html .= BimpRender::renderAlerts($errors, 'warning');
            
        }
        
        return $html;
        
    }
    
    
    
    public function setDateMouvement($date_mouvement) {

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'stock_mouvement';
        $sql .= ' SET datem="' . $date_mouvement . '"';
        $sql .= ' WHERE inventorycode="inventory2-' . $this->getData('id') . '"';

        $result = $this->db->db->query($sql);
        if ($result) {
            $this->db->db->commit();
            return true;
        } else {
            dol_print_error($this->db->db);
            $this->db->db->rollback();
            return false;
        }
    }
    
    public function close() {
        $errors = array();
        
        $errors = $this->testHasService();
        if(empty($errors)) {
            $errors = BimpTools::merge_array($errors, $this->moveProducts());
            $errors = BimpTools::merge_array($errors, $this->moveEquipments());
        }
       
        return $errors;
    }
    
    private function testHasService() {
        
        $errors = array();
        
        $sql = 'SELECT d.id as id_det, p.ref as p_ref, i.id as id_inv
FROM llx_bl_inventory_det_2 as d
LEFT JOIN llx_product as p ON d.fk_product = p.rowid
LEFT JOIN llx_bl_inventory_2 as i ON d.fk_inventory = i.id
WHERE p.fk_product_type=1
AND i.id=' . (int) $this->id;
        
        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $errors[] = 'Le service ' . $obj->p_ref . ' empèche la fermeture de l\'inventaire';
            }
        }
        
        return $errors;
    }
    

    public function moveProducts() {
        $errors = array();
        $warnings = array();
            
        $id_package_vol = $this->getPackageVol();
        $package_vol = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package_vol);

        $id_package_nouveau = $this->getPackageNouveau();
        $package_nouveau = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package_nouveau);

        $diff = $this->getDiffProduct();
        
        $mvt_label = 'Correction inventaire #' . $this->id;
        $code_mvt = 'inventory2-' . $this->id;
        
        foreach ($diff as $id_wt => $package_prod_qty) {
            
            $wt = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $id_wt);
            $id_entrepot = (int) $wt->getData('fk_warehouse');
            
            foreach($package_prod_qty as $id_package => $prod_qty) {
                
                foreach($prod_qty as $id_product => $data) {
                    
                    if((int) $data['diff'] == 0)
                        continue;

                    if(0 < (int) $id_package) {
                        
                        // Package nouveau
                        if((int) $id_package == (int) $id_package_nouveau)  {
                            
                            // Manquant
                            if($data['diff'] < 0) {
                                
                                $id_package_src = $id_package_nouveau;
                                $id_package_dest = $id_package_vol;
                                $diff = -$data['diff'];
                                
                            // En trop
                            } else {

                                $id_package_src = $id_package_vol;
                                $id_package_dest = $id_package_nouveau;
                                $diff = $data['diff'];
                                
                            }
                        
                        // Stock initialement négatif
                        } elseif($data['stock'] < 0) {
                            
                            // Égalisation à zéro
                            if(0 < $data['diff']) {
                                
                                $id_package_src = $id_package_nouveau;
                                $id_package_dest = $id_package;
                                $diff = $data['diff'];

                            }                          
                            
                        // Stock initialement positif et pas dans package nouveau
                        } else {
                            
                            // Manquant
                            if($data['diff'] < 0) {
                                
                                $id_package_src = $id_package;
                                $id_package_dest = $id_package_vol;
                                $diff = -$data['diff'];

                            }
                        
                        }

                        
                        $errors = BimpTools::merge_array($errors,
                                BE_Package::moveElements($id_package_src, $id_package_dest,
                                        array($id_product => $diff), array(),
                                        $code_mvt, $mvt_label, 'inventory2', $this->id));                        
                        
                    // Stock
                    } else {
                        
                        if($data['diff'] < 0) {
                            
                            $diff = -$data['diff'];
                            $errors = BimpTools::merge_array($errors, $package_vol->addProduct($id_product, $diff, $id_entrepot, $warnings, $code_mvt, $mvt_label, 'inventory2', $this->id));
                            
                        // Égalisation à zéro
                        } elseif(0 < $data['diff'] and $data['stock'] < 0) {
                            
                            $diff = -$data['diff'];
                            $errors = BimpTools::merge_array($errors, $package_vol->addProduct($id_product, $diff, $id_entrepot, $warnings, $code_mvt, $mvt_label, 'inventory2', $this->id));
                   
                        }
                        
                    }
                    
                } // loop prod
            } // loop package
        } // loop wt

        return $errors;
    }
        
    public function moveEquipments() {
        
        $errors = array();
        $id_package_vol = $this->getPackageVol();
        $id_package_nouveau = $this->getPackageNouveau();
        
        $date_opening = $this->getData('date_opening');
        
        
        $diff = $this->getDiffEquipment();
        
        $code_move = 'inventory2-'.$this->id;
        
        foreach ($diff as $id_equip => $data) {
            if($data['code_scan'] == 0) {                
                
                $equip = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equip);
                BimpObject::loadClass('bimpcore', 'BimpNote');
                // Cet équipement a été déplacé entre temps
                if((int) $equip->getPlaceByDate($date_opening, $errors) != (int) $equip->getCurrentPlace()->getData('id')) {
                    $this->addNote("L'équipement " . $equip->getData('serial') . 
                            " a été déplacé après la date d'ouverture de l'inventaire.", BimpNote::BIMP_NOTE_AUTHOR);
                    
                // Cet équipement a été volé
                } else
                    $errors = BimpTools::merge_array($errors, $equip->moveToPackage($id_package_vol, $code_move, "Manquant lors de l'inventaire #" . $this->id, 1, null, 'inventory2', $this->id));

                
            } elseif($data['code_scan'] == 2) {
                $equip = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equip);
                $errors = BimpTools::merge_array($errors, $equip->moveToPackage($id_package_nouveau, $code_move, "Présent lors de l'inventaire #" . $this->id, 1, null, 'inventory2', $this->id));
            }
        }
        
        return $errors;
    }
    
    
    public static function getWarehouseInventories() {
        global $db;
        
        $sql  = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';
        $sql .= ' WHERE ref="INV"';

        $result = $db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $db->fetch_object($result)) {
                return $obj->rowid;
            }
        }
        
        return -1;
    }
    
    public function getMainWT() {
        
        $wt = BimpObject::getInstance($this->module, 'InventoryWarehouse');
        $id = $wt->getList(array('fk_inventory' => (int) $this->getData('id'), 'is_main' => 1), null, null, 'is_main', 'asc', 'array', array('id'))[0]['id'];
        $wt->fetch((int) $id);
        return $wt;
    }
    
    /**
     * Utilisé si scan only = 0 lors des correct stock
     * @return tableau d'id product
     */
    
    public function getProductScanned() {
        $products = array();
        
        $sql  = 'SELECT fk_product';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'bl_inventory_det_2';
        $sql .= ' WHERE fk_inventory=' . $this->getData('id');
        $sql .= ' AND (fk_equipment=0 OR fk_equipment IS NULL)';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $products[(int) $obj->fk_product] = (int) (int) $obj->fk_product;
            }
        }
        
        return $products;
    }
    
    public function getEntrepotRef() {
        $sql = 'SELECT ref';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';
        $sql .= ' WHERE rowid=' . $this->getData('fk_warehouse');

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            $obj = $this->db->db->fetch_object($result);
            return $obj->ref;
        }
        
        return "Entrepôt " . $this->getData('fk_warehouse') . " non définit";
    }
    
    public function renderMouvementTrace() {
        
        if (self::STATUS_CLOSED == $this->getData('status')) {
            $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=products&search=1&object=BimpProductMouvement&sall=inventory2-' . $this->getData('id') . '';
            return '<a target="_blank" href="' . $url . '">Voir</a>';
        }

        return "Disponible à la fermeture de l'inventaire";
    }
    
    public function renderPackageVol() {
        
        $id_package = $this->getPackageVol();
        $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);
        if(!is_null($package))
            return $package->getLink();
        
        return "Package introuvable";
    }
    
    public function renderPackageNouveau() {
        $id_package = $this->getPackageNouveau();
        $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);
        if(!is_null($package))
            return $package->getLink();
        
        return "Package introuvable";
    }
    
    public function getPackageVol() {
        return $this->getData('id_package_vol');
    }
    
    public function getPackageNouveau() {
        return $this->getData('id_package_nouveau');
    }
    
    public function createPackageVol() {
        $errors = array();
        
        if(!$this->id > 0)
            $id_inv = $this->getNextInsertId();
        else
            $id_inv = $this->id;

        
        $package = BimpObject::getInstance('bimpequipment', 'BE_Package');
        $errors = BimpTools::merge_array($errors, $package->validateArray(array(
                    'label'      => 'Vol-Inventaire#' . $id_inv,
                    'products'   => array(),
                    'equipments' => array()
        )));
        $errors = BimpTools::merge_array($errors, $package->create());
        
        $errors = BimpTools::merge_array($errors, $package->addPlace($this->getData('fk_warehouse'), BE_Place::BE_PLACE_VOL,
                date('Y-m-d H:i:s'), 'Vol inventaire ' . $this->id, 'VolInv#' . $this->id . '.'));
        
        $this->temp_package_vol = $package->getData('id');
        
        return $errors;
    }
    
    public function createPackageNouveau() {
        $errors = array();
        
        if(!$this->id > 0)
            $id_inv = $this->getNextInsertId();
        else
            $id_inv = $this->id;
        
        $package = BimpObject::getInstance('bimpequipment', 'BE_Package');
        $errors = BimpTools::merge_array($errors, $package->validateArray(array(
                    'label'      => ('Nouveau-Inventaire#' . $id_inv),
                    'products'   => array(),
                    'equipments' => array()
        )));
        

        $errors = BimpTools::merge_array($errors, $package->create());
        
        $errors = BimpTools::merge_array($errors, $package->addPlace($this->getData('fk_warehouse'), $this->getData('type'),
                date('Y-m-d H:i:s'), 'Nouveau inventaire ' . $this->id, 'NouvInv#' . $this->id . '.'));
        
        $this->temp_package_nouveau = $package->getData('id');
        
        return $errors;
    }
    public function lineIsDeletable($id_line) {
        
        if(!is_array($this->lines_status))
            $this->setLinesStatus();

        return $this->lines_status[$id_line];
    }
    
    
    /**
     * Used to delete lines
     */
    public function setLinesStatus() {
        $this->lines_status = array();
        $prods = array();

        // Création du tableau $prods[id_prod][id_package][] = id_line
        $sql =  'SELECT id, fk_product, fk_package';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory_det_2';
        $sql .= ' WHERE fk_inventory=' . (int) $this->id;

        $rows = $this->db->executeS($sql);

        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $row) {

                if(!isset($prods[(int) $row->fk_product]))
                    $prods[(int) $row->fk_product] = array();
                
                if(!isset($prods[(int) $row->fk_product][(int) $row->fk_package]))
                    $prods[(int) $row->fk_product][(int) $row->fk_package] = array();

                $prods[(int) $row->fk_product][(int) $row->fk_package][] = (int) $row->id;
            }
        }
        
        $id_package_nouveau = $this->getPackageNouveau();

        // Création du tableau lines_status[id_line] = is_deletable (boolean)
        foreach ($prods as $pack_lines) {

            // Il y a des lignes dans package nouveau, uniquement celle-ci 
            // seront supprimable
            if(isset($pack_lines[$id_package_nouveau])) {
                
                foreach($pack_lines as $id_package => $lines) {
                    if((int) $id_package == $id_package_nouveau) {
                        foreach($lines as $id_line)
                            $this->lines_status[(int) $id_line] = 1;
                    } else {
                        foreach($lines as $id_line)
                            $this->lines_status[(int) $id_line] = 0;
                    }
                }
                
            // Il n'y a pas de ligne dans package nouveau, on peut toutes
            //  les supprimer
            } else {
                foreach($pack_lines as $id_package => $lines) {
                    foreach($lines as $id_line)
                        $this->lines_status[(int) $id_line] = 1;
                }
            }
        }
    }
    
    
    /**
     * Utilisé le yml
     */
    public function getProductFiltersArray() {
        global $user;

        $return = array(0 => '');
        
        $instance = BimpObject::getInstance('bimpcore', 'ListFilters');
        $rows = $instance->getList(array(
            'id_owner' => (int) $user->id,
            'obj_name' => 'Bimp_Product'
        ), null, null, 'id', 'DESC', 'array', array('id', 'name'));

        foreach ($rows as $r) {
            $return[(int) $r['id']] = $r['name'];
        }

        return $return;
        
    }

    public function getFiltersValue() {
        
        $return = array();
        foreach($this->filters as $f) {
            $return[$f] = $this->getSerializeValue($f);
        }
 
        return $return;
    }
    
    public function setCacheProduct() {
        
        $this->cache_prod = array();
        
        $incl_categorie = $incl_collection = $incl_nature = $incl_famille 
                = $incl_gamme = $incl_product = $excl_categorie
                = $excl_collection = $excl_nature = $excl_famille
                = $excl_gamme = $excl_product = array();
        
        if((int) $this->getInitData('filter_inventory') == 0 /*and !isset($this->refresh_cache)*/) {
            $this->cache_prod['all'] = 'all';
            return $this->cache_prod;
        }
        
        foreach($this->getFiltersValue() as $name => $data) {
            $$name = $data;
        }
        
        $sql =  'SELECT fk_object';
        $where = '';
        $include = '';
        $exclude = '';
        
        $has_include = (!empty($incl_categorie)  or !empty($incl_collection) or
                       !empty($incl_nature)     or !empty($incl_famille)    or
                       !empty($incl_gamme)      or !empty($incl_product));
        
        $has_exclude = (!empty($excl_categorie)  or !empty($excl_collection) or
                       !empty($excl_nature)     or !empty($excl_famille)    or
                       !empty($excl_gamme)      or !empty($excl_product));

        
        
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_extrafields';
        if($has_include or $has_exclude)
            $where .= ' WHERE ';
                
        // A des include
        if($has_include) {
            
            $include .= '(';
            
            if(!empty($incl_categorie))
                $include .= (($include != '(') ? ' OR' : '') . ' categorie IN('      . implode(',', $incl_categorie) . ')';
            
            if(!empty($incl_collection))
                $include .= (($include != '(') ? ' OR' : '') . ' collection IN('      . implode(',', $incl_collection) . ')';
            
            if(!empty($incl_nature))
                $include .= (($include != '(') ? ' OR' : '') . ' nature IN('      . implode(',', $incl_nature) . ')';
            
            if(!empty($incl_famille))
                $include .= (($include != '(') ? ' OR' : '') . ' famille IN('      . implode(',', $incl_famille) . ')';
            
            if(!empty($incl_gamme))
                $include .= (($include != '(') ? ' OR' : '') . ' gamme IN('      . implode(',', $incl_gamme) . ')';
            
            if(!empty($incl_product))
                $include .= (($include != '(') ? ' OR' : '') . ' fk_object IN('      . implode(',', $incl_product) . ')';
            
            $include .= ')';
        }
        
        if($has_exclude) {
            
            $exclude .= '(';
            
            if(!empty($excl_categorie))
                $exclude .= (($exclude != '(') ? ' OR' : '') . ' categorie NOT IN('      . implode(',', $excl_categorie) . ')';
            
            if(!empty($excl_collection))
                $exclude .= (($exclude != '(') ? ' OR' : '') . ' collection NOT IN('      . implode(',', $excl_collection) . ')';
            
            if(!empty($excl_nature))
                $exclude .= (($exclude != '(') ? ' OR' : '') . ' nature NOT IN('      . implode(',', $excl_nature) . ')';
            
            if(!empty($excl_famille))
                $exclude .= (($exclude != '(') ? ' OR' : '') . ' famille NOT IN('      . implode(',', $excl_famille) . ')';
            
            if(!empty($excl_gamme))
                $exclude .= (($exclude != '(') ? ' OR' : '') . ' gamme NOT IN('      . implode(',', $excl_gamme) . ')';
            
            if(!empty($excl_product))
                $exclude .= (($exclude != '(') ? ' OR' : '') . ' fk_object NOT IN('      . implode(',', $excl_product) . ')';
            
            $exclude .= ')';
        }
        
        if($has_include and $has_exclude)
            $include .= ' AND ';
        
        $sql .= $where . $include . $exclude;

        $rows = $this->db->executeS($sql);

        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $row) {
                $this->cache_prod[(int) $row->fk_object] = (int) $row->fk_object;
            }
        }
        

    }
        
            
    public function getAllowedProduct($refresh_cache = 0) {
        
        if(isset($this->cache_prod) and !$refresh_cache)
            return $this->cache_prod;
        
        $this->setCacheProduct();
                        
        return $this->cache_prod;
    }
    
    public function isAllowedProduct($id_product) {
        
        if((int) $this->getData('filter_inventory') == 0)
            return 1;
        
        $allowed_products = $this->getAllowedProduct(0);
                
        if(isset($allowed_products['all']))
            return 1;
        
        if(isset($allowed_products[$id_product]))
            return 1;
        
        return 0;
    }
    
    

    
    /**
     * 
     * @param type $has_filter
     * @param type $called_from 'create' ou 'update'
     * @return type
     */
    public function setFilters($has_filter, $called_from = 'create') {
        
        $errors = array();
                
        if(!$has_filter) {
            
            if($called_from == 'create') {
                
                foreach($this->filters as $f)
                    $this->data[$f] = serialize(array());
                
                $this->data['filter_inventory'] = 0;
                
            } elseif($called_from == 'update') {
                
                foreach($this->filters as $f)
                    $this->setSerializedValue($f, array());
                
                $this->updateField('filter_inventory', 0);
                
            } else {
                $errors = "Paramètre 'called_from' mal renseigné";
                return $errors;
            }
            
            return $errors;
        }

        // Contient un filtre
        
        $posted_filters = $this->getPostedFilterData();
        
        foreach($filters as $name => $data)
            $$name = $data;

        if($called_from == 'create') {
            
            foreach($posted_filters as $name => $data)
                $this->data[$name] = serialize($data);
            
            $this->data['filter_inventory'] = 1;
            
        } elseif($called_from == 'update') {

            foreach($posted_filters as $name => $data)
                $this->setSerializedValue($name, $data);
            
            $this->updateField('filter_inventory', 1);
            
        } else {
            $errors = "Paramètre 'called_from' mal renseigné";
            return $errors;
        }
        
        return $errors;
    }
    
    
    public function setSerializedValue($field, $value) {
        return $this->updateField($field, serialize($value));
    }
        
    public function getSerializeValue($field) {
        if(isset($this->getInitData($field)[0]))
            return array_values(unserialize($this->getInitData($field)[0]));
        
        return array_values($this->getData($field));
    }
    
    public function displayCats($field, $type) {
        $html = '';
        
        self::loadClass('bimpcore', 'Bimp_Product');

        $id_label = Bimp_Product::getValues8sens($type);
        $list = $this->getSerializeValue($field);
        
        if (is_array($list)) {
            foreach($list as $id) {
                if(isset($id_label[$id]))
                    $html .= $id_label[$id] . '<br/>';
                else
                    $html .= 'Id non définit' . $id . '<br/>';
            }
        }
        
        if($html == '')
            $html = 'Aucune';

        return $html;
    }
    
    public function displayProduct($field) {
        $html = '';
        $list = $this->getSerializeValue($field);
        
        if (is_array($list)) {
            foreach($list as $id) {
                if((int) $id > 0) {

                    $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id);
                    $html .= $prod->getNomUrl() . '<br/>';
                }

            }
        }
        
        if($html == '')
            $html = 'Aucun';

        return $html;
    }
    
    public function renderAddProduct($input_name = 'excl_product') {
                
        $html .= '<div input_name="' . $input_name . '">';
        
        $html .= '<button type="button" class="addValueBtn btn btn-primary" '
                . 'onclick="getProduct(\'' . $input_name .'\')">'
                . '<i class="fa fa-plus-circle iconLeft"></i>Ajouter</button>';
        
        $html .= '<button type="button" class="addValueBtn btn btn-danger" '
                . 'onclick="deleteProduct(\'' . $input_name . '\')">'
                . '<i class="fas fa5-trash-alt iconLeft"></i>Tout supprimer</button>';
        
        $html .= '<div name="div_products"></div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function hasPostedFilter() {
                
        $filters = $this->getPostedFilterData();  
        
        foreach ($filters as $f) {
            if(!empty($f))
                return 1;
        }

        return 0;
    }
    
}

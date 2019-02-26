<?php


require_once DOL_DOCUMENT_ROOT.'/bimpcore/objects/BimpDolObject.class.php';

abstract class extraFI extends BimpDolObject{
    public $moduleRightsName = "synopsisficheinter";
    public static $typeinter_list = array();
    
    // gestion des extra
    
    public function __construct($module, $object_name) {
        $return = parent::__construct($module, $object_name);
        
        if(count(self::$typeinter_list) < 1){
            $sql = $this->db->db->query("SELECT * FROM `".MAIN_DB_PREFIX."synopsisfichinter_c_typeInterv` ORDER BY rang ASC");
            while($ln = $this->db->db->fetch_object($sql))
                    self::$typeinter_list[$ln->id] = array('label'=>$ln->label);
        }
        return $return;
    }
    
    
    public function canView() {
        return $this->getDolRights("lire");
    }
    public function canEdit() {
        $parent = $this->getParentInstance();
        if(is_object($parent) && $parent->isLoaded())
            return $parent->canEdit();
        
        if($this->canEditAll())
            return 1;
        if($this->getInitData("fk_statut") > 0)
            return 0;
        
        return $this->getDolRights("creer");
    }
    public function canEditAll() {
        return ($this->getDolRights("modifAfterValid") || $this->getDolRights("edit_after_validation"))? 1: 0;
    }
    public function canDelete() {
        return $this->getDolRights("supprimer");
    }
    public function canCreate() {
        return ($this->canEdit() &&$this->getDolRights("creer"));
    }
    public function canViewPrice() {
        return ($this->getDolRights("voirPrix") || $this->getDolRights("config"))? 1 : 0;
    }
    
    public function getDolRights($nom){
        global $user;
        $module = $this->moduleRightsName;
        return (isset($user->rights->$module->$nom))? 1 : 0;
    }
    
    
    
    public function getProducts_contrat_listArray(){
        $this->products_contrat_list = array(0=>array('label'=>''));
        $parent = $this->getParentInstance(); 
        if (is_object($parent) && $parent->isLoaded() && $parent->getData('fk_contrat') > 0) {
            $requete = "SELECT fk_product, rowid FROM " . MAIN_DB_PREFIX . "contratdet WHERE fk_contrat = " . $parent->getData('fk_contrat');
//            die($requete);
            $sql4 = $this->db->db->query($requete);
            while($res4 = $this->db->db->fetch_object($sql4)){
                if ($res4->fk_product > 0) {
                    $tmpProd = new Product($this->db->db);
                    $tmpProd->fetch($res4->fk_product);
                    $this->products_contrat_list[$res4->rowid] = array('label' => $tmpProd->getNomUrl(1), 'icon' => '', 'classes' => array('info'));
                } 
            }
        }
//        print_r($this->products_contrat_list.$requete);
        return $this->products_contrat_list;
    }
    
    public function getProducts_commande_listArray(){
        $this->products_commande_list = array(0=>array('label'=>''));
        $parent = $this->getParentInstance(); 
        if (is_object($parent) && $parent->isLoaded() && $parent->getData('fk_commande') > 0) {
            $requete = "SELECT fk_product, rowid FROM " . MAIN_DB_PREFIX . "commandedet WHERE fk_commande = " . $parent->getData('fk_commande');
//            die($requete);
            $sql4 = $this->db->db->query($requete);
            while($res4 = $this->db->db->fetch_object($sql4)){
                if ($res4->fk_product > 0) {
                    $tmpProd = new Product($this->db->db);
                    $tmpProd->fetch($res4->fk_product);
                    $this->products_commande_list[$res4->rowid] = array('label' => $tmpProd->getNomUrl(1), 'icon' => '', 'classes' => array('info'));
                } 
            }
        }
        
        return $this->products_commande_list;
    }
    
    public function getList_commandeArray(){
        $this->list_commande = array();
        if($this->isLoaded() && $this->getData("fk_soc") > 0){
            $clef = 'getList_commandeArray_user_'.$this->getData("fk_soc");
            if(!isset(self::$cache[$clef])){
                self::$cache[$clef] = array(0=>array('label'=>''));
                $sql = $this->db->db->query("SELECT ref, rowid FROM llx_commande WHERE fk_soc = ".$this->getData("fk_soc"));
                while($ln = $this->db->db->fetch_object($sql))
                        self::$cache[$clef][$ln->rowid] = array('label' => $ln->ref, 'icon' => '', 'classes' => array('info'));
            }
             $this->list_commande = self::$cache[$clef];
        }
        return $this->list_commande;
    }
    
    public function getList_contratArray(){
        if($this->isLoaded() && $this->getData("fk_soc") > 0){
            $clef = 'getList_contratArray_user_'.$this->getData("fk_soc");
            if(!isset(self::$cache[$clef])){
                self::$cache[$clef] = array(0=>array('label'=>''));
                $sql = $this->db->db->query("SELECT ref, rowid FROM llx_contrat WHERE fk_soc = ".$this->getData("fk_soc"));
                while($ln = $this->db->db->fetch_object($sql))
                        self::$cache[$clef][$ln->rowid] = array('label' => $ln->ref, 'icon' => '', 'classes' => array('info'));
            }
             $this->list_contrat = self::$cache[$clef];
        }
        return $this->list_contrat;
    }
    
    
    public function getListExtra($key){
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice WHERE key_refid = " . $key;
        $sql1 = $this->db->db->query($requete);
        $return = array();
        while ($res1 = $this->db->db->fetch_object($sql1)) {
            $return[$res1->value] = array('label' => $res1->label, 'icon' => 'fas_file-alt', 'classes' => array('warning'));
        }
        return $return;
    }
    
    
    public function getExtra($field){
        if($field == "di"){
            if($this->isLoaded() && is_a($this->dol_object, 'Synopsisfichinter')){
                $return = array();
                $dis = $this->dol_object->getDI();
                require_once DOL_DOCUMENT_ROOT.'/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php';
                $di = new Synopsisdemandeinterv($this->db->db);
                foreach($dis as $diI){
                    $di->fetch($diI);
                    $return[] = $di->getNomUrl(1);
                }
                return implode("<br/>", $return);
            }
        }
        if($field == "fi"){
            if($this->isLoaded() && is_a($this->dol_object, 'Synopsisdemandeinterv')){
                $return = array();
                $dis = $this->dol_object->getFI();
                require_once DOL_DOCUMENT_ROOT.'/synopsisfichinter/class/synopsisfichinter.class.php';
                $di = new Synopsisfichinter($this->db->db);
                foreach($dis as $diI){
                    $di->fetch($diI);
                    $return[] = $di->getNomUrl(1);
                }
                return implode("<br/>", $return);
            }
        }
        
        
        $field = str_replace("extra", "", $field);
        if ($this->isLoaded()){
            if(!$this->extraFetch){
                $this->dol_object->fetch_extra();
                $this->extraFetch = true;
            }
            return $this->dol_object->extraArr[$field];
        }
    }
    
    
     public function insertExtraFields()
    {
         if(!is_object($this->dol_object)){
             $this->dol_object = new Synopsisfichinter($this->db->db);
             $this->dol_object->id = $this->id;
         }
         $this->updateExtraFields();

        return array();
    }

    public function updateExtraFields()
    {
        $list = $this->getExtraFields();
        foreach($list as $extra)
            if($this->getData($extra) != $this->getInitData($extra))
                $this->updateExtraField ($extra, $this->getData($extra),0);

        return array();
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        if($id_object == $this->dol_object->id || $id_object == 0){
            $field = str_replace("extra", "", $field_name);
            $this->dol_object->setExtra($field, $value);
        }

        return array();
    }

    public function fetchExtraFields()
    {
        $return = array();
        $list = $this->getExtraFields();
        foreach($list as $extra){
            $return[$extra] = $this->getExtra ($extra);
            if(in_array($extra, array("extra37"))){
                $listName = $extra."_list";
                $this->$listName = $this->getListExtra (str_replace("extra", "", $extra));
            }
        }

        return $return;
    }

    public function deleteExtraFields()
    {
        // Supprimer les extrafields
        // Retourner un tableau d'erreurs

        if (count($this->getExtraFields())) {
            return array('Fonction de suppression des champs supplémentaires non implémentée');
        }

        return array();
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        return $this->getInitData($field);
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '')
    {
        // Retourner la clé de filtre SQl sous la forme alias_table.nom_champ_db 
        // Implémenter la jointure dans $joins en utilisant l'alias comme clé du tableau (pour éviter que la même jointure soit ajouté plusieurs fois à $joins). 
        // Si $main_alias est défini, l'utiliser comme préfixe de alias_table. Ex: $main_alias .'_'.$alias_table (Bien utiliser l'underscore).  
        // ET: utiliser $main_alias à la place de "a" dans la clause ON. 
//        Ex: 
//        $join_alias = ($main_alias ? $main_alias . '_' : '') . 'xxx';
//        $joins[$join_alias] = array(
//            'alias' => $join_alias,
//            'table' => 'nom_table',
//            'on'    => $join_alias . '.xxx = ' . ($main_alias ? $main_alias : 'a') . '.xxx'
//        );
//        
//        return $join_alias.'.nom_champ_db';

        return '';
    }
    
    
   
    
    public function asParentCommande(){
        $parent = $this->getParentInstance();
        if($parent->isLoaded())
            return ($parent->getData("fk_commande") > 0)? 1 : 0;
        return 0;
    }
    
    public function asParentContrat(){
        $parent = $this->getParentInstance();
        if($parent->isLoaded())
            return ($parent->getData("fk_contrat") > 0)? 1 : 0;
        return 0;
    }
    
    public function traitePriceProd(){
        if($this->getData("fk_commandedet") > 0){//on est en mode commande
//            die('cic'.$this->getData("fk_commandedet"));
            if($this->getData("fk_commandedet") != $this->getInitData("fk_commandedet")){//on a changé de ligne commande
                $sql = $this->db->db->query("SELECT subprice FROM `llx_commandedet` WHERE `rowid` = ".$this->getData("fk_commandedet"));
//                die("SELECT subprice FROM `llx_commandedet` WHERE `rowid` = ".$this->getData("fk_commandedet"));
                while($ln = $this->db->db->fetch_object($sql)){
                    $this->updateField("pu_ht", $ln->subprice);
                    $warnings[] = "Prix de la ligne maj avec prix commande";
                }
            }
        }
        elseif($this->getData("fk_contratdet") > 0){//on est en mode contrat
            if($this->getData("fk_contratdet") != $this->getInitData("fk_contratdet") || $this->getData("fk_commandedet") != $this->getInitData("fk_commandedet")){//on a changé de ligne commande
                $sql = $this->db->db->query("SELECT subprice FROM `llx_contratdet` WHERE `rowid` = ".$this->getData("fk_contratdet"));
//                die("SELECT subprice FROM `llx_commandedet` WHERE `rowid` = ".$this->getData("fk_commandedet"));
                while($ln = $this->db->db->fetch_object($sql)){
                    $this->updateField("pu_ht", $ln->subprice);
                    $warnings[] = "Prix de la ligne maj avec prix contrat";
                }
            }
        }
    }
    
    public function update(&$warnings = array(), $force_update = false) {
        $this->traitePriceProd();
        
        return parent::update($warnings, $force_update);
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        $this->traitePriceProd();
        return parent::create($warnings, $force_create);
    }
}
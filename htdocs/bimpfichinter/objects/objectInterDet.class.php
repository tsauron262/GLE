<?php


require_once DOL_DOCUMENT_ROOT.'/bimpfichinter/objects/extraFI.class.php';

class ObjectInterDet extends extraFI{
    
    public function getTypeinter_listArray(){
        if(!isset(self::$cache['typeinter_list']) || count(self::$cache['typeinter_list']) < 1){
            self::$cache['typeinter_list'] = array();
            $sql = $this->db->db->query("SELECT * FROM `".MAIN_DB_PREFIX."synopsisfichinter_c_typeInterv` ORDER BY rang ASC");
            while($ln = $this->db->db->fetch_object($sql))
                    self::$cache['typeinter_list'][$ln->id] = array('label'=>$ln->label);
        }
        return self::$cache['typeinter_list'];
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
    
    public function traitePriceProd(&$warnings){
        if($this->getData("fk_commandedet") > 0){//on est en mode commande
            if($this->getData("fk_commandedet") != $this->getInitData("fk_commandedet")){//on a changé de ligne commande
                $sql = $this->db->db->query("SELECT subprice FROM `".MAIN_DB_PREFIX."commandedet` WHERE `rowid` = ".$this->getData("fk_commandedet"));
//                die("SELECT subprice FROM `".MAIN_DB_PREFIX."commandedet` WHERE `rowid` = ".$this->getData("fk_commandedet"));
                while($ln = $this->db->db->fetch_object($sql)){
                    $this->set("pu_ht", $ln->subprice); 
                    $warnings[] = "Prix de la ligne maj avec prix commande";
                }
            }
        }
        elseif($this->getData("fk_contratdet") > 0){//on est en mode contrat
            if($this->getData("fk_contratdet") != $this->getInitData("fk_contratdet") || $this->getData("fk_commandedet") != $this->getInitData("fk_commandedet")){//on a changé de ligne commande
                $sql = $this->db->db->query("SELECT subprice FROM `".MAIN_DB_PREFIX."contratdet` WHERE `rowid` = ".$this->getData("fk_contratdet"));
//                die("SELECT subprice FROM `".MAIN_DB_PREFIX."commandedet` WHERE `rowid` = ".$this->getData("fk_commandedet"));
                while($ln = $this->db->db->fetch_object($sql)){
                    $this->set("pu_ht", $ln->subprice);
                    $warnings[] = "Prix de la ligne maj avec prix contrat";
                }
            }
        }
    }
    
    
        

    
    public function update(&$warnings = array(), $force_update = false) {
        $this->traitePriceProd($warnings);
        
        return parent::update($warnings, $force_update);
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        $this->traitePriceProd($warnings);
        return parent::create($warnings, $force_create);
    }
}
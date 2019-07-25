<?php


require_once DOL_DOCUMENT_ROOT.'/bimpfichinter/objects/extraFI.class.php';

class ObjectInter extends extraFI{
    public static $dirDol = "synopsisfichinter";
    public static $controller_name;
    public $redirectMode = 5;//5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    
    public $extra_left = '';




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
    
    
    public function create(&$warnings = array(), $force_create = false) {
        global $user;
        $this->set("fk_user_author", $user->id);
        return parent::create($warnings, $force_create);
    }
    
    
    
    public function renderHeaderExtraLeft()
    {
        $soc = $this->getChildObject("client");
        return $soc->dol_object->getNomUrl(1).$this->extra_left;
    }
    
    
    
    
    

        
        
    
    public function getList_contratArray(){
        if($this->isLoaded() && $this->getData("fk_soc") > 0){
            $clef = 'getList_contratArray_user_'.$this->getData("fk_soc");
            if(!isset(self::$cache[$clef])){
                self::$cache[$clef] = array(0=>array('label'=>''));
                $sql = $this->db->db->query("SELECT ref, rowid FROM llx_contrat WHERE fk_soc = ".$this->getData("fk_soc")." AND (statut = 1 || rowid = '".$this->getData("fk_contrat")."')");
                while($ln = $this->db->db->fetch_object($sql))
                        self::$cache[$clef][$ln->rowid] = array('label' => $ln->ref, 'icon' => '', 'classes' => array('info'));
            }
             $this->list_contrat = self::$cache[$clef];
        }
        return $this->list_contrat;
    }
    
    public function getList_factureArray(){
        if($this->isLoaded() && $this->getData("fk_soc") > 0){
            $clef = 'getList_factureArray_user_'.$this->getData("fk_soc");
            if(!isset(self::$cache[$clef])){
                self::$cache[$clef] = array(0=>array('label'=>''));
                $sql = $this->db->db->query("SELECT facnumber, rowid FROM llx_facture WHERE fk_soc = ".$this->getData("fk_soc"));
                while($ln = $this->db->db->fetch_object($sql))
                        self::$cache[$clef][$ln->rowid] = array('label' => $ln->facnumber, 'icon' => '', 'classes' => array('info'));
            }
             $this->list_facture = self::$cache[$clef];
        }
        return $this->list_facture;
    }
    
    
    
//    public static function redirect($newVersion = true, $id = 0){
//        
//        $redirectModeOldNew = 1;//0 pas de redirect 1 redirect button   2 redirect direct
//        $redirectModeNewOld = 2;//0 pas de redirect 1 redirect button   2 redirect direct
//        
//        global $user;
//        if(in_array($user->id, array(1, 375, 35, 446, 277, 242, 42)))
//                $redirectModeNewOld = 1;
//        
//        if($redirectModeOldNew == 2)//pur incohérence
//            $redirectModeNewOld = 0;
//        elseif($redirectModeNewOld == 2)
//            $redirectModeOldNew = 0;
//        
//        if($id == "" || $id == 0)
//            $id = "list";
//        $html = "";
//        $location = "";
//        if($newVersion && $redirectModeNewOld > 0){
//            if($redirectModeNewOld == 1)
//                $idR = $_REQUEST["idR"];
//            elseif($redirectModeNewOld == 2)
//                $idR = $id;
//            if($idR == "list")
//                $location = "/".self::$dirDol."/list.php";
//            elseif($idR > 0){
//                $location = "/".self::$dirDol."/card.php?id=".$idR;
//            }
//            else{
//                $html .= "<form method='POST'><input type='submit' class='btn btn-primary saveButton' name='redirige' value='Ancienne version'/><input type='hidden' name='idR' value='".$id."'/></form>";
//            }
//        }
//        elseif(!$newVersion && $redirectModeOldNew > 0){
//            if($redirectModeOldNew == 1)
//                $idR = $_REQUEST["idR"];
//            elseif($redirectModeOldNew == 2)
//                $idR = $id;
//            
//            
//            if($idR == "list"){
//                    $location = "/bimpfichinter/";
//            }elseif($idR > 0){
//                $location = "/bimpfichinter/?fc=".self::$controller_name."&id=".$idR;
//            }
//            else{
//                $html .= "<form method='POST'><input type='submit' class='btn btn-primary saveButton' name='redirige' value='Nouvelle version'/><input type='hidden' name='idR' value='".$id."'/></form>";
//            }
//        }
//        if($location != ""){
//            header("Location: ".DOL_URL_ROOT.$location);
//            exit;
//        }
//        
//        return $html;
//    }
    
    
    
    function getFieldFiltre($field, $mode){//show filtre form_value
        
        $tabT = array("fk_soc" => BimpTools::getPostFieldValue("fk_soc"));
        
        if(isset($tabT[$field]) && $tabT[$field] > 0){
            $value = $tabT[$field];
            if($mode == "show")
                return 0;
            elseif($mode == "filtre"){
                return array(array("name"=>$field, "filter"=>$value));
            }
            elseif($mode == "form_value"){
                return array("fields"=>array($field=>$value));
            }
        }
        
        else{
            if($mode == "show")
                return 1;
            else
                return array();
            
        }
            
    }
    
    public function iAmAdminRedirect() {
        global $user;
        if(in_array($user->id, array(1, 375, 35, 446, 277, 242, 42, 330, 62)))
            return true;
        parent::iAmAdminRedirect();
    }
}

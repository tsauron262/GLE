<?php


require_once DOL_DOCUMENT_ROOT.'/bimpfichinter/objects/extraFI.class.php';

class ObjectInter extends extraFI{
    public static $controller_name = "";
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
    
    
    public static function redirectOldToNew($id = null){
        $mode = 0; //0 rien 1 boutons 2 force redirect
        
        if($mode > 0){
            if($mode == 1)
                $idR = $_REQUEST["idR"];
            elseif($mode == 2)
                $idR = $id;
            if($idR > 0){
                header("Location: ".DOL_URL_ROOT."/bimpfichinter/?fc=".self::$controller_name."&id=".$idR);
                exit;
            }
            elseif($idR == "list"){
                    header("Location: ".DOL_URL_ROOT."/bimpfichinter/");
                    exit;
            }
            else{
                echo "<form><input type='submit' class='btn btn-primary saveButton' name='redirige' value='Nouvelle verssion'/><input type='hidden' name='idR' value='".$id."'<form>";
            }
        }
        
            
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
}
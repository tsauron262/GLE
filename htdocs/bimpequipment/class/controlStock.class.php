<?php
set_time_limit(300);


if(isset($_REQUEST['action'])){
    require_once '../../main.inc.php';
    llxHeader();
    $c = new controlStock($db);
    $c->go();
    llxFooter();
}


class controlStock{
    private $entrepot = array();
    private $prodS = array();
    private $db;
    
    
    function __construct($db){
        $this->db = $db;
    }
    
    function go(){
        global $user;
        $this->getEntrepot();
        $this->getProductSerialisable();
        
        foreach($this->entrepot as $idEn => $labEl){
            $debutText = "Entrepot : ".$labEl."<br/>";
            foreach($this->prodS as $idPr => $labPr){
                $nbE = $this->getNbEquip($idPr, $idEn);
                $nbS = $this->getStockProd($idPr, $idEn);
                
                if($nbE != $nbS || $nbE != 0){
                    $millieuText = $debutText. "  -  Produit : ".$labPr;
                    if($nbE == $nbS){
                        if($_REQUEST['action'] == "detail")
                            echo $millieuText." OK  : ".$nbE."<br/>";
                    }
                    elseif($nbE > $nbS)
                        echo $millieuText." ATTENTION PLUS d'equipement (".$nbE.") que de prod (".$nbS.")<br/>";
                    elseif($nbE < $nbS)
                        echo $millieuText." ATTENTION MOINS d'equipement (".$nbE.") que de prod (".$nbS.")<br/>";
                    else
                        echo $millieuText."ATTENTION BIZZARRE<br/>";
                    $nbCorrection = $nbE - $nbS;
                    if($nbCorrection != 0 && $_REQUEST['action'] == "corriger"){
                        echo "  correction de  ".$nbCorrection."<br/>";
                        $product = new Product($this->db);
                        $product->fetch($idPr);
                        $now = dol_now();
                        $codemove = dol_print_date($now, '%y%m%d%H%M%S');
                        $product->correct_stock($user, $idEn, $nbCorrection, 0, "correction Auto Stock en fonction des equipments", 0, $codemove);
                    }
                    
                }
            }
        }
        
        $this->getEquipmentNonSerialisable();
        if(count($this->equipNonS) == 0)
            echo "<br/>AUCUN Equipment NON Serialisable.... OK";
        else{
            echo "<br/><br/>1 ou plusieurs equipment correspondant a des produits non serialisable";
            foreach($this->equipNonS as $sn)
                echo "<br/>Equipment non Serilisé....".$sn;
        }
        
        echo "<br/><br/>Fin du test";
    }
    
    
    private function getEntrepot(){
        $sql = $this->db->query("SELECT `rowid`, `label` FROM `llx_entrepot`");
        while($ligne = $this->db->fetch_object($sql))
                $this->entrepot[$ligne->rowid] = $ligne->label;
    }
    
    private function getProductSerialisable(){
        $sql = $this->db->query("SELECT p.rowid, p.label FROM `llx_product` p, llx_product_extrafields pe WHERE p.rowid = pe.fk_object AND pe.serialisable = 1");
        while($ligne = $this->db->fetch_object($sql))
                $this->prodS[$ligne->rowid] = $ligne->label;
    }
    
    
    private function getEquipmentNonSerialisable(){
        $sql = $this->db->query("SELECT serial FROM llx_product_extrafields pe, `llx_be_equipment` be WHERE be.id_product = pe.fk_object AND pe.serialisable = 0");
        while($ligne = $this->db->fetch_object($sql))
                $this->equipNonS = $ligne->serial;
    }
    
    
    
    private function getNbEquip($prod, $entrepot){
        $sql = $this->db->query("SELECT COUNT(*) as nb FROM `llx_be_equipment` be, `llx_be_equipment_place` bep WHERE be.id = bep.`id_equipment` AND position = 1 AND `id_entrepot` = ".$entrepot." AND id_product = ".$prod);
        while($ligne = $this->db->fetch_object($sql))
                return $ligne->nb;
        return 0;
    }
    
    
    
    private function getStockProd($prod, $entrepot){
        $sql = $this->db->query("SELECT reel as nb FROM `llx_product_stock` WHERE `fk_entrepot` = ".$entrepot." AND fk_product = ".$prod);
        while($ligne = $this->db->fetch_object($sql))
                return $ligne->nb;
        return 0;
    }
}
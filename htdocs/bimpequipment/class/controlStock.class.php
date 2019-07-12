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
        
        echo "Debut : <br/>";
        
        $i = 0;
        
        
        $stocks = $this->getStocksProds();
        $stocksEqui = $this->getNbEquips();
//        echo "<pre>";print_r($stocks);die;
        foreach($this->entrepot as $idEn => $labEl){
            $debutText = "Entrepot : ".$labEl."<br/>";
            foreach($this->prodS as $idPr => $labPr){
                $i++;
//                $nbE = $this->getNbEquip($idPr, $idEn);
//                $nbS = $this->getStockProd($idPr, $idEn);
                
                $nbE = isset($stocksEqui[$idEn][$idPr])? $stocksEqui[$idEn][$idPr] : 0;
                $nbS = isset($stocks[$idEn][$idPr])? $stocks[$idEn][$idPr] : 0;
                
                
                
                if($nbE != $nbS || $nbE != 0){
                    $millieuText = $debutText. "  -  Produit : ".$labPr;
                    if($nbE == $nbS){
                        if($_REQUEST['action'] == "detail")
                            echo $millieuText." OK  : ".$nbE."<br/>";
                    }
                    else{
                        $text = "";
                        $tabSerials = array();
                        if($nbE > 0){
                            $tabSerials = $this->getTabSerials($idEn, $idPr);
                        }
                        
                        
                        if($nbE > $nbS)
                            $text =  $millieuText." ATTENTION PLUS d'equipement (".$nbE." | ".implode(" ", $tabSerials).") que de prod (".$nbS.")<br/>";
                        elseif($nbE < $nbS)
                            $text =  $millieuText." ATTENTION MOINS d'equipement (".$nbE." | ".implode(" ", $tabSerials).") que de prod (".$nbS.")<br/>";
                        else
                            $text =  $millieuText."ATTENTION BIZZARRE<br/>";
                        $nbCorrection = $nbE - $nbS;
                        if($nbCorrection != 0 && $_REQUEST['action'] == "corriger"){
                            echo "  correction de  ".$nbCorrection."<br/>";
                            $product = new Product($this->db);
                            $product->fetch($idPr);
                            $now = dol_now();
                            $codemove = dol_print_date($now, '%y%m%d%H%M%S');
                            $product->correct_stock($user, $idEn, $nbCorrection, 0, "correction Auto Stock en fonction des equipments", 0, $codemove);
                        }
                        elseif(isset($_REQUEST['mail']))
                            mailSyn2("Probléme stock", "tommy@bimp.fr", '', $text);
                        echo $text;
                    }
                }
            }
        }
        
        $this->getEquipmentNonSerialisable();
        if(count($this->equipNonS) == 0)
            echo "<br/>AUCUN Equipment NON Serialisable.... OK";
        else{
            echo "<br/><br/>".count($this->equipNonS)." equipment(s) correspondant a des produits non serialisable";
            foreach($this->equipNonS as $ref => $tabSn){
                $prod = new Product($this->db);
                $prod->fetch($ref);
                echo "<br/>Equipment non Serilisé ref : ".$prod->getNomUrl(). " SN : ". implode(" - ", $tabSn);
            }
        }
        
        echo "<br/><br/>Fin du test";
    }
    
    
    private function getEntrepot(){
        $sql = $this->db->query("SELECT `rowid`, `ref` FROM `llx_entrepot`");// WHERE ref LIKE 'SAV%'");
        while($ligne = $this->db->fetch_object($sql))
                $this->entrepot[$ligne->rowid] = $ligne->ref;
    }
    
    private function getProductSerialisable(){
        $sql = $this->db->query("SELECT p.rowid, p.label as label, ref FROM `llx_product` p, llx_product_extrafields pe WHERE p.rowid = pe.fk_object AND pe.serialisable = 1");
        while($ligne = $this->db->fetch_object($sql))
                $this->prodS[$ligne->rowid] = $ligne->ref." ".$ligne->label;
    }
    
    
    private function getEquipmentNonSerialisable(){
        $this->equipNonS = array();
        $sql = $this->db->query("SELECT serial, id_product FROM `llx_be_equipment` be, `llx_be_equipment_place` bep WHERE bep.id_equipment = be.id AND bep.`type` = 2 AND bep.`position` = 1 AND be.id_product > 0 AND be.id_product NOT IN (SELECT pe.fk_object FROM llx_product_extrafields pe WHERE pe.serialisable = 1)");
//        $sql = $this->db->query("SELECT serial FROM llx_product_extrafields pe, `llx_be_equipment` be WHERE be.id_product = pe.fk_object AND pe.serialisable = 0");
        while($ligne = $this->db->fetch_object($sql))
                $this->equipNonS[$ligne->id_product][] = $ligne->serial;
    }
    
    
    
//    private function getNbEquip($prod, $entrepot){
//        $sql = $this->db->query("SELECT COUNT(*) as nb FROM `llx_be_equipment` be, `llx_be_equipment_place` bep WHERE be.id = bep.`id_equipment` AND bep.type = 2 AND position = 1 AND `id_entrepot` = ".$entrepot." AND id_product = ".$prod);
//        while($ligne = $this->db->fetch_object($sql))
//                return $ligne->nb;
//        return 0;
//    }
//    
//    
//    
//    private function getStockProd($prod, $entrepot){
//        $sql = $this->db->query("SELECT reel as nb FROM `llx_product_stock` WHERE `fk_entrepot` = ".$entrepot." AND fk_product = ".$prod);
//        while($ligne = $this->db->fetch_object($sql))
//                return $ligne->nb;
//        return 0;
//    }
    
    
    private function getStocksProds(){
        $result = array();
        $sql = $this->db->query("SELECT reel as nb, fk_entrepot, fk_product FROM `llx_product_stock`");
        while($ligne = $this->db->fetch_object($sql))
                $result[$ligne->fk_entrepot][$ligne->fk_product] = $ligne->nb;
        return $result;
    }
    
    
    
    private function getNbEquips(){
        $result = array();
        $sql = $this->db->query("SELECT COUNT(*) as nb, `id_entrepot`, id_product FROM `llx_be_equipment` be, `llx_be_equipment_place` bep WHERE be.id = bep.`id_equipment` AND bep.type = 2 AND position = 1 GROUP BY `id_entrepot`, id_product ");
        
        while($ligne = $this->db->fetch_object($sql))
                $result[$ligne->id_entrepot][$ligne->id_product] = $ligne->nb;
        return $result;
    }
    
    private function getTabSerials($idEn, $idPr){
        $return = array();
        $sql = $this->db->query("SELECT `serial` FROM `llx_be_equipment` be, `llx_be_equipment_place` bp WHERE bp.`id_equipment` = be.id AND `position` = 1 AND id_product = ".$idPr." AND bp.`type` = 2 AND `id_entrepot` = ".$idEn);
        while($ln = $this->db->fetch_object($sql))
            $return[] = $ln->serial;
        return $return;
    }
}
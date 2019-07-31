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
                        
                        
                        if($nbE > $nbS)
                            $ope = "+";
                        else
                            $ope = "-";
    
                        if($nbE > 0){
                            $tabSerials = $this->getTabSerials($idEn, $idPr, $ope);
                        }
                        
                        
                        $sql2 = $this->db->query("SELECT count(*) as nb, sum(value) as value FROM `llx_stock_mouvement` WHERE fk_entrepot = ".$idEn." AND fk_product = ".$idPr);
                        $ln2 = $this->db->fetch_object($sql2);
                        
                        $text =  $millieuText." ATTENTION ".$ope." d'equipement (".$nbE." | ".implode(" ", $tabSerials).") que de prod (".$nbS.") total des mouvement (".$ln2->value.")<br/>";
                        
                        $corigable = ($nbE == $ln2->value);
                            
                        $nbCorrection = $nbE - $nbS;
                        if($nbCorrection != 0 && $_REQUEST['action'] == "corriger" && $corigable){
                            echo "  correction de  ".$nbCorrection."<br/>";
                            $product = new Product($this->db);
                            $product->fetch($idPr);
                            $now = dol_now();
                            $codemove = dol_print_date($now, '%y%m%d%H%M%S');
                            $product->correct_stock($user, $idEn, $nbCorrection, 0, "correction Auto Stock en fonction des equipments", 0, $codemove);
                        }
                        elseif(isset($_REQUEST['mail']))
                            mailSyn2("Probléme stock", "tommy@bimp.fr", '', $text);
                        elseif($corigable)
                            $text .= "<span style='color:green'>Corigeable</span><br/><br/>";
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
    
    private function getTabSerials($idEn, $idPr, $ope){
        $return = array();
        if($ope == "+"){
            $sql = $this->db->query("SELECT `serial` FROM `llx_be_equipment` be, `llx_be_equipment_place` bp WHERE bp.`id_equipment` = be.id AND `position` = 1 AND id_product = ".$idPr." AND bp.`type` = 2 AND `id_entrepot` = ".$idEn);
            while($ln = $this->db->fetch_object($sql)){
                $html = "<span style='color:";

                //Toute les sortie
                    $sql2 = $this->db->query("SELECT count(*) as nb, sum(value) as value FROM `llx_stock_mouvement` WHERE `label` LIKE '%".$ln->serial."%' AND fk_entrepot = ".$idEn." AND fk_product = ".$idPr."");
                    $ln2 = $this->db->fetch_object($sql2);
    //            if($ope == "+"){
                    if($ln2->value == 1)
                        $html .= 'green';
                    else
                        $html .= 'red';
                $html .= "'>".$ln->serial."</span>";
                $return[] = $html;
            }
        }
//        else{
            $sql2 = $this->db->query("SELECT count(*) as nb, sum(value) as value, serial 

FROM llx_be_equipment_place ep, `llx_be_equipment` e 

LEFT JOIN `llx_stock_mouvement` sm 
ON sm.`label` LIKE concat('%', concat(serial, '%')) AND sm.`fk_product` = e.`id_product`

WHERE e.id = `id_equipment` AND `position` > 1 AND ep.`type` = 2 AND `id_entrepot` = ".$idEn." AND id_product = ".$idPr." AND sm.`fk_entrepot` = ep.`id_entrepot`

GROUP BY serial



");
            if($this->db->num_rows($sql2) > 0){
                while($ln2 = $this->db->fetch_object($sql2))
                    if($ln2->value != 0)
                        $return[] = "<span style='color:red'>".$ln2->serial."</span>";
            }
            
            
//        }
        return $return;
    }
}
<?php

require_once("../../main.inc.php");






$go = (isset($_REQUEST['action']) && $_REQUEST['action'] == 'go')? 1 : 0;

$cl = new majCodeConfigurationnProd($db);
$cl->exec($go);






class majCodeConfigurationnProd{
    private $whereTaille = '( LENGTH(serial) = 13 || LENGTH(serial) = 12)';
    private $totCorrection = 0;
    private $erreurs = array();
    private $info = array();
    private $ok = array();

    function getSN($fin, $id_prod = null, $max = null){
        $return = array();
        $req = "SELECT `serial` FROM `llx_be_equipment` WHERE ".$this->whereTaille." AND `serial` LIKE '%".$fin."'";
        if($id_prod > 0)
            $req .= " AND `id_product` = ".$id_prod;
        if($max > 0)
            $req .= " LIMIT 0,".$max;
        $sql = $this->db->query($req);
        while($ln = $this->db->fetch_object($sql)){
            $return[] = $ln->serial;
        }
        return implode(" | ", $return);
    }
    
    function __construct($db) {
        $this->db = $db;
    }
    
    function updateEquipmentOrfellin(){
        $sql4 = $this->db->query("SELECT code_config, fk_object FROM llx_product_extrafields WHERE code_config IS NOT NULL");
        while($ln4 = $this->db->fetch_object($sql4)){
            $this->db->query("UPDATE llx_be_equipment SET id_product = ".$ln4->fk_object." WHERE serial LIKE '%".$ln4->code_config."' and ".$this->whereTaille);
        }
    }
    
    function testSerialDouble(){
        $sql3 = $this->db->query("SELECT COUNT(*) as nbIdentique, serial FROM `llx_be_equipment` WHERE `id_product` > 0 AND ".$this->whereTaille." GROUP BY `serial`, id_product HAVING nbIdentique > 1  
        ORDER BY COUNT(*)  DESC");
        while($ln3 = $this->db->fetch_object($sql3)){
            $this->erreurs[] = $ln3->serial." plusieurs foix ... grave";
        }
    }
    
    function updateCodeConfigProd($go){
        $sql = $this->db->query("SELECT COUNT(*) as nbSerial, SUBSTRING(`serial`, LENGTH(`serial`)-3, 4) as fin, MIN(id) as minEquipment, MIN(id_product) as minProd, MAX(id_product) as maxProd, COUnT(DISTINCT(id_product)) as nbProd FROM `llx_be_equipment` "
                . "WHERE ".$this->whereTaille." AND  id_product > 0 "
                . "AND SUBSTRING(`serial`, LENGTH(`serial`)-3, 4) NOT IN (SELECT code_config FROM llx_product_extrafields WHERE code_config IS NOT NULL) "
                . "AND id_product IN (SELECT rowid FROM llx_product WHERE ref LIKE 'APP-%') "
                . "GROUP BY fin ORDER BY COUNT(*) DESC");
        while($ln = $this->db->fetch_object($sql)){
            if($ln->nbSerial > 30){
                if($ln->nbProd > 1){
                    $this->erreurs[] = $ln->fin." plusieurs prod (".$ln->nbProd.")  <br/>".$ln->minProd."(".$this->getSN($ln->fin, $ln->minProd, 4).") <br/>".$ln->maxProd."(".$this->getSN($ln->fin, $ln->maxProd, 4).")".($ln->nbProd > 2? "<br/> ..." :"")."<br/>";
                }
                else{
                    if(!isset($this->ok[$ln->fin])){
                        $this->ok[$ln->fin]= 0; 
                        $sql2 = $this->db->query("SELECT COUNT(*) as nbSerial  FROM `llx_be_equipment` WHERE ".$this->whereTaille." AND  id_product = 0 AND serial LIKE '%".$ln->fin."'");
                        $ln2 = $this->db->fetch_object($sql2);

                        $this->totCorrection += $ln2->nbSerial;

                        $this->info[] = "OK prod ".$ln->minProd.' code config '.$ln->fin. '   corrigera '.$ln2->nbSerial. ' equipement SAV';

                        if($go)
                            $this->db->query("UPDATE llx_product_extrafields SET code_config = '".$ln->fin."' WHERE fk_object = '".$ln->minProd."'"); 
                    }
                    else
                        $this->erreurs[] = $ln->fin.' plusieurs ('.($this->ok[$ln->fin]+1).') fois ATTENTION......';
                    $this->ok[$ln->fin]++;


                }
            }
            elseif($ln->nbSerial > 5){
                $this->erreurs[] = $ln->fin." pas assé d'equipment (".$ln->nbSerial.")";
            }
        }

    }
    
    function exec($go = false){
        
        $this->updateCodeConfigProd($go);
        
        if($go){
            $this->updateEquipmentOrfellin();
        }
        
        $this->testSerialDouble();
        
        
        $this->bilan();
    }
    
    
    function bilan(){
        echo '<pre>';

        print_r($this->info);

        print_r($this->erreurs);


        echo '<br/><br/>Fin : corrigerais : '.$this->totCorrection.' equipment sans produit';
    }

}


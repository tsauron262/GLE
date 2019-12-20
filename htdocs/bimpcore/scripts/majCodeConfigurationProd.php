<?php

require_once("../../main.inc.php");


require_once __DIR__ . '/../Bimp_Lib.php';




$go = (isset($_REQUEST['action']) && $_REQUEST['action'] == 'go')? 1 : 0;

$cl = new majCodeConfigurationnProd($db);
$cl->exec($go);






class majCodeConfigurationnProd{
    private $whereTaille = '( LENGTH(serial) = 13 || LENGTH(serial) = 12)';
    private $totCorrection = 0;
    private $totFusion =  0;
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
    
    function testSerialDouble($go = false){
        $sql3 = $this->db->query("SELECT COUNT(*) as nbIdentique, serial, id_product FROM `llx_be_equipment` WHERE `id_product` > 0 AND ".$this->whereTaille." GROUP BY `serial`, id_product HAVING nbIdentique > 1  
        ORDER BY COUNT(*)  DESC");
        while($ln3 = $this->db->fetch_object($sql3)){
            $this->erreurs[] = $ln3->serial." plusieurs foix (".$ln3->nbIdentique.") ... grave";
            $this->fusionSav($ln3->serial, $ln3->id_product, $go);
        }
    }
    
    
    function fusionSav($serial, $idProd, $go = false){
        $serial = $this->traiteSerialApple($serial);
        $sql = $this->db->query("SELECT * FROM llx_be_equipment WHERE (serial LIKE '".$serial."' || serial LIKE 'S".$serial."') AND id_product=".$idProd." ORDER BY id DESC");
        $pasChezCleint = 0;
        $tabEquipment = array();
        $equipmentAGarde = null;
        while($ln = $this->db->fetch_object($sql)){
            $ex = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $ln->id);
            $place = $ex->getCurrentPlace();
            $tabEquipment[] = $ex;
            if(is_object($place) && $place->getData('type') != 1){
                $pasChezCleint++;
                $equipmentAGarde = $ex;
            }
        }
        if($pasChezCleint < 2){
            $this->erreurs[] = "Fustion OK";
            if(!is_object($equipmentAGarde))
                $equipmentAGarde = $tabEquipment[0];
            foreach($tabEquipment as $ex){
                if($ex->id != $equipmentAGarde->id){
                    if($equipmentAGarde->id < 1)
                        die("grosse erreur");
                    $this->totFusion++;
                    if($go){
                        $this->changeIdSav($ex->id, $equipmentAGarde->id);
                        $ex->delete();
                    }
                }
            }
        }
        else
            $this->erreurs[] = "Fustion BAD";
        
    }
    
    public function changeIdSav($oldId, $newId){
        $sql = $this->db->query("SELECT MAX(`position`) as max FROM `llx_be_equipment_place` WHERE `id_equipment` = ".$newId);
        $ln = $this->db->fetch_object($sql);
        $sql2 = $this->db->query("UPDATE `llx_be_equipment_place` SET id_equipment = ".$newId.", position = (position + ".$ln->max.")  WHERE `id_equipment`=".$oldId);
        
        $tabConversion = array("bs_sav" =>'', 'br_reservation' => '', 'bc_vente_article'=>'', 'bc_vente_return'=>'', 'object_line_equipment'=>'', 'bcontract_serials'=>'', 'bs_sav_product' => '', 'bt_transfer_det' => '');
        foreach($tabConversion as $table => $champ){
            if($champ == '')
                $champ = 'id_equipment';
            $this->db->query("UPDATE `llx_".$table."` SET `".$champ."` = '".$newId."' WHERE ".$champ." = ".$oldId.";");
        }
        
    }
    
    public function traiteSerialApple($serial){
        if(stripos($serial, 'S') === 0){
            return substr($serial,1);
        }
        return $serial;
    }
    
    function updateCodeConfigProd($go){
        $sql = $this->db->query("SELECT COUNT(*) as nbSerial, SUBSTRING(`serial`, LENGTH(`serial`)-3, 4) as fin, MIN(id) as minEquipment, MIN(id_product) as minProd, MAX(id_product) as maxProd, COUnT(DISTINCT(id_product)) as nbProd "
                . "FROM `llx_be_equipment`, llx_product p "
                . "WHERE ".$this->whereTaille." AND  id_product > 0 "
                . "AND SUBSTRING(`serial`, LENGTH(`serial`)-3, 4) NOT IN (SELECT code_config FROM llx_product_extrafields WHERE code_config IS NOT NULL) "
                . "AND id_product =p.rowid AND ref LIKE 'APP-%' "
                . "AND label  NOT LIKE '%(Demo)%' "
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
        
        $this->testSerialDouble($go);
        
        
        $this->bilan();
    }
    
    
    function bilan(){
        echo '<pre>';

        print_r($this->info);

        print_r($this->erreurs);


        echo '<br/><br/>Fin : corrigerais : '.$this->totCorrection.' equipment sans produit';
        echo '<br/><br/>Fin : corrigerais : '.$this->totFusion.' equipment fusionné';
    }

}


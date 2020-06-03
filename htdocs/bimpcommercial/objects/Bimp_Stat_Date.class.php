<?php

class Bimp_Stat_Date extends BimpObject
{
    public $isOk = false;
    public $datas = array();
    public $datasPropal = array();
    public $datasCommande = array();
    public $datasFacture = array();
    public $signatureFilter = "";
    public function getListCount($filters = array(), $joins = array())
    {
        $this->signatureFilter = json_encode($filters);
        
       $filters["a.filter"] = $this->signatureFilter;
       
       if(isset($filters["a.date"]) && isset($filters["a.date"]["or_field"][0]) && !isset($filters["a.date"]["or_field"][1])){
           $filter = $filters["a.date"]["or_field"][0];
           if(isset($filter['min']) && isset($filter['max'])){
                $this->isOk = true;
                $date = strtotime($filter['min'])+3600*2;
                $dateFin = strtotime($filter['max']);
                $this->majTable($date, $dateFin);
           }
       }
       
       return parent::getListCount($filters, $joins);
    }
    
    public function majTable($date, $dateFin){
        $i= 0;
        $this->cacheTables();
        while($date < $dateFin){
            if($i > 100)
                die('trop de boucles');
            $i++;
            $dateFinJ = $date + 3600*24;
            
            $dateStr = gmdate("Y-m-d", $date);
            
            if(!isset($this->datas[$dateStr.$this->signatureFilter])){
                $nbProp = (isset($this->datasPropal[$dateStr.$this->signatureFilter]))? $this->datasPropal[$dateStr.$this->signatureFilter]->nb : 0;
                $totProp = (isset($this->datasPropal[$dateStr.$this->signatureFilter]))? $this->datasPropal[$dateStr.$this->signatureFilter]->tot : 0;
                
                $nbComm = (isset($this->datasCommande[$dateStr.$this->signatureFilter]))? $this->datasCommande[$dateStr.$this->signatureFilter]->nb : 0;
                $totComm = (isset($this->datasCommande[$dateStr.$this->signatureFilter]))? $this->datasCommande[$dateStr.$this->signatureFilter]->tot : 0;
                
                $nbFact = (isset($this->datasFacture[$dateStr.$this->signatureFilter]))? $this->datasFacture[$dateStr.$this->signatureFilter]->nb : 0;
                $totFact = (isset($this->datasFacture[$dateStr.$this->signatureFilter]))? $this->datasFacture[$dateStr.$this->signatureFilter]->tot : 0;

                 
                $this->db->db->query("INSERT INTO `llx_bimp_stat_date`(`date`, `devis_qty`, `devis_total`, `commande_qty`, `commande_total`, `facture_qty`, `facture_total`,`filter`) "
                        . "VALUES ('".$dateStr."', ".$nbProp.", ".$totProp.", ".$nbComm.", ".$totComm.", ".$nbFact.", ".$totFact.", '".$this->signatureFilter."')");
            }
            
            
            $date = $dateFinJ;
            
            
        }
    }
    
    public function cacheTables(){
        $this->datas = array();
        
        $sql = $this->db->db->query("SELECT * FROM `llx_bimp_stat_date` WHERE filter = '".$this->signatureFilter."' GROUP BY date ASC");
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datas[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $sql = $this->db->db->query("SELECT DATE(`date_valid`) as date, count(*) as nb, SUM(total_ht) as tot FROM `llx_propal` WHERE 1 group by DATE(`date_valid`)");
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datasPropal[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $sql = $this->db->db->query("SELECT DATE(`date_valid`) as date, count(*) as nb, SUM(total_ht) as tot FROM `llx_commande` WHERE 1 group by DATE(`date_valid`)");
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datasCommande[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $sql = $this->db->db->query("SELECT DATE(`date_valid`) as date, count(*) as nb, SUM(total) as tot FROM `llx_facture` WHERE 1 group by DATE(`date_valid`)");
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datasFacture[$ln->date.$this->signatureFilter] = $ln;
        }
    }
    
    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = array(), $extra_order_by = null, $extra_order_way = 'ASC') {
       $filters["a.filter"] = $this->signatureFilter;
        if($this->isOk)
            return parent::getList($filters, $n, $p, $order_by, $order_way, $return, $return_fields, $joins, $extra_order_by, $extra_order_way);
        else
            return array();
    }
    
    public function getLabel($type = ""){
        $return = parent::getLabel($type);
        if($type == 'name' && !$this->isOk)
            return $return." (Choisir une plage de date)";
        return $return;
    }
    
    
}

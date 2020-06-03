<?php

class Bimp_Stat_Date extends BimpObject
{
    public $isOk = false;
    public $datas = array();
    public $datasPropal = array();
    public $datasCommande = array();
    public $datasFacture = array();
    public $signatureFilter = "";
    public $filterCusom = array();
    public $filterCusomExclud = array();
    public static $factTypes = array(
        0 => 'Facture standard',
        1 => 'Facture de remplacement',
        2 => 'Avoir',
        3 => 'Facture d\'acompte',
        4 => 'Facture proforma',
        5 => 'Facture de situation'
    );
    public function getListCount($filters = array(), $joins = array())
    {
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
    
    public function traiteFilters(&$filters){
        global $memoireFilter;
        if(!isset($memoireFilter)){
            $memoireFilter = $filters;
        }
        $this->signatureFilter = json_encode($this->filterCusom);
        $this->signatureFilter .= json_encode($this->filterCusomExclud);
        $this->signatureFilter .= json_encode($memoireFilter);
       $filters["a.filter"] = $this->signatureFilter;
//       print_r($filters);die;
    }
    
    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        if($excluded)
            $this->filterCusomExclud[$field_name] = $values;
        else
            $this->filterCusom[$field_name] = $values;
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
        
        $and = $andFact = "";
        $extrafield = false;
        foreach(array("IN" => $this->filterCusom, "NOT IN" => $this->filterCusomExclud) as $typeF => $filters){
            foreach($filters as $filter => $values){
                if(stripos($filter, "ef_") !== false){
                        $filter = str_replace("ef_", "f.", $filter);
                        $extrafield = true;
                }
                else {
                    $filter = "a.".$filter;
                }
                if(stripos($filter, "facture_") !== false){
                    $andFact .= " AND ".str_replace("facture_", "", $filter). " ".$typeF." ('".implode("','", $values)."')";
                }
                else
                    $and .= " AND ".$filter. " ".$typeF." ('".implode("','", $values)."')";
            }
        }
        $sql = $this->db->db->query("SELECT * FROM `llx_bimp_stat_date` WHERE filter = '".$this->signatureFilter."' GROUP BY date ASC");
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datas[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $req = "SELECT DATE(`date_valid`) as date, count(*) as nb, SUM(total_ht) as tot FROM `llx_propal` a";
        if($extrafield)
            $req .= " LEFT JOIN llx_propal_extrafields f ON  a.rowid = f.fk_object ";
        $req .= " WHERE 1 ".$and." group by DATE(`date_valid`)";
        $sql = $this->db->db->query($req);
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datasPropal[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $req = "SELECT DATE(`date_valid`) as date, count(*) as nb, SUM(total_ht) as tot FROM `llx_commande` a";
        if($extrafield)
            $req .= " LEFT JOIN llx_commande_extrafields f ON a.rowid = f.fk_object ";
        $req .= " WHERE 1 ".$and." group by DATE(`date_valid`)";
        $sql = $this->db->db->query($req);
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datasCommande[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $req = "SELECT DATE(`date_valid`) as date, count(*) as nb, SUM(total) as tot FROM `llx_facture` a";
        if($extrafield)
            $req .= " LEFT JOIN llx_facture_extrafields f ON a.rowid = f.fk_object ";
        $req .= " WHERE 1 ".$and.$andFact." group by DATE(`date_valid`)";
        $sql = $this->db->db->query($req);
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

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
    public $mode = 'month';
    
    public $asGraph = true;
    public $filterCusomExclud = array();
    public static $factTypes = array(
        0 => 'Facture standard',
        1 => 'Facture de remplacement',
        2 => 'Avoir',
        3 => 'Facture d\'acompte',
        4 => 'Facture proforma',
        5 => 'Facture de situation'
    );
    
    public function displayOldValue($field, $nb_month){
        global $modeCSV, $modeGraph;
        if($this->isLoaded()){
            $date = new DateTime($this->getData('date'));
            $date->sub(new DateInterval('P'.$nb_month.'M'));
            
            $sql = $this->db->db->query("SELECT * FROM `llx_bimp_stat_date` WHERE `date` = '".$date->format('Y-m-d')."' AND `filter` = '".$this->getData('filter')."'");
            if($this->db->db->num_rows($sql) > 0){
                $ln= $this->db->db->fetch_object($sql);
                if(stripos($field, 'total') && !$modeCSV)
                    return price($ln->$field)." €";
                elseif($modeCSV && !$modeGraph)
                    return str_replace (".", ",", $ln->$field);
                else
                    return $ln->$field;
                
            }
            elseif(!$modeGraph)
                return BimpRender::renderAlerts("Pas de calcul pour le ".$date->format('Y-m-d'));
            else
                return 0;
        }
    }
    
    
    public function addConfigExtraParams()
    {
        $cols = array();

        foreach (array("qty"=>'Nb', "total"=>'Total') as $type => $labelType) {
            foreach (array("devis"=>'Devis', "commande"=>'Commande', "facture"=>'Facture') as $elem => $label) {
                foreach (array(1, 3, 6, 12, 24) as $nb_month) {
                    $cols[$type."_".$elem.'_' . $nb_month . '_mois'] = array(
                        'label' => $labelType.' '.$label.' à ' . $nb_month . ' mois',
                        'value' => array(
                            'callback' => array(
                                'method' => 'displayOldValue',
                                'params' => array(
                                    $elem."_".$type,
                                    $nb_month
                                )
                            )
                        )
                    );
                }
            }
        }

        

        $this->config->addParams('lists_cols', $cols);
    }
    
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
    
    
    public function getInfoGraph(){
        $data = parent::getInfoGraph();
        $data["data1"] = 'Facture HT';
        $data["data2"] = 'Commande HT';
        $data["data3"] = 'Devis HT';
        $data["data11"] = 'Facture HT a 1an';
        $data["axeX"] = '';
        $data["axeY"] = 'K €';
        $data["title"] = 'Facture Commande et Devis par Jour';
        
        return $data;
    }
    
    public function getGraphDataPoint($numero_data = 1){
        $tabDate = explode("-", $this->getData('date'));
        if($this->mode == 'day')
            $tabDate[1]--;
        $x = "new Date(".implode(", ", $tabDate).")";
        if($numero_data == 1)
            $y = $this->getData('facture_total');
        elseif($numero_data == 2)
            $y = $this->getData('commande_total');
        elseif($numero_data == 3)
            $y = $this->getData('devis_total');
        elseif($numero_data == 11)
            $y = str_replace(",", ".", $this->displayOldValue('facture_total', 12));
            
        return '{ x: '.$x.', y: '.$y.' },';
    }
    
    public function traiteFilters(&$filters){
        global $memoireFilter;
        if(!isset($memoireFilter)){
            $memoireFilter = $filters;
            unset($memoireFilter['a.date']);
        }
//        print_r($filters);die('tttt');
        if(strtotime($filters["a.date"]["or_field"][0]['max']) > ( time() - 86400))
            $filters["a.date"]["or_field"][0]['max'] = date('Y-m-d', ( time() - 86400) );
       if(isset($filters['a.fk_soc'])){
            if(!is_array($filters['a.fk_soc']))
                $filters['a.fk_soc'] = array($filters['a.fk_soc']);
            $this->filterCusom['a.fk_soc'] = $filters['a.fk_soc'];
            unset($filters['a.fk_soc']);
       }
        
        
        $this->signatureFilter = json_encode($this->filterCusom);
        $this->signatureFilter .= json_encode($this->filterCusomExclud);
        $this->signatureFilter .= json_encode($memoireFilter);
        $this->signatureFilter .= json_encode($this->mode);
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
            if($i > 1000)
                die('trop de boucles');
            $i++;
            $dateFinJ = $date + 3600*24;
            
            $dateStr = gmdate("Y-m-d", $date);
            if(!isset($this->datas[$dateStr.$this->signatureFilter]) && ($this->mode != 'month' || (int) gmdate("d", $date) == 1)){
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
        
        if($this->mode == 'month'){
            $selectDate = 'CONCAT(DATE_FORMAT(date_valid, "%Y-%m"),"-01") ';
            $groupBy = 'DATE_FORMAT(date_valid, "%m%Y")';
        }
        else{
            $selectDate = 'DATE(`date_valid`)';
            $groupBy = 'DATE(`date_valid`)';
        }
        
        $and = $andFact = "";
        $extrafield = $contact = false;
        foreach(array("IN" => $this->filterCusom, "NOT IN" => $this->filterCusomExclud) as $typeF => $filters){
            foreach($filters as $filter => $values){
                if(stripos($filter, "ef_") !== false){
                        $filter = str_replace("ef_", "f.", $filter);
                        $extrafield = true;
                }
                elseif(stripos($filter, "ec_") !== false){
                        $filter = str_replace("ec_", "ec.", $filter);
                        $contact = true;
                }
                elseif(stripos($filter, 'a.') === false) {
                    $filter = "a.".$filter;
                }
                if(stripos($filter, "facture_") !== false){
                    $andFact .= " AND ".str_replace("facture_", "", $filter). " ".$typeF." ('".implode("','", $values)."')";
                }
                else{
//                    echo '<br/>'.$filter.' : '.print_r($values,1);
                    $and .= " AND ".$filter. " ".$typeF." ('".implode("','", $values)."')";
                }
            }
        }
        $sql = $this->db->db->query("SELECT * FROM `llx_bimp_stat_date` WHERE filter = '".$this->signatureFilter."' GROUP BY date ASC");
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datas[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $req = "SELECT ".$selectDate." as date, count(*) as nb, SUM(total_ht) as tot FROM `llx_propal` a";
        if($extrafield)
            $req .= " LEFT JOIN llx_propal_extrafields f ON  a.rowid = f.fk_object ";
        if($contact)
            $req .= " LEFT JOIN `llx_element_contact` ec ON ec.element_id = a.rowid LEFT JOIN `llx_c_type_contact` c ON `code` LIKE 'SALESREPFOLL' AND `fk_c_type_contact` = c.rowid AND c.element = 'propal' ";
        $req .= " WHERE 1 ".$and." group by ".$groupBy;
        $sql = $this->db->db->query($req);
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datasPropal[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $req = "SELECT ".$selectDate." as date, count(*) as nb, SUM(total_ht) as tot FROM `llx_commande` a";
        if($extrafield)
            $req .= " LEFT JOIN llx_commande_extrafields f ON a.rowid = f.fk_object ";
        if($contact)
            $req .= " LEFT JOIN `llx_element_contact` ec ON ec.element_id = a.rowid LEFT JOIN `llx_c_type_contact` c ON `code` LIKE 'SALESREPFOLL' AND `fk_c_type_contact` = c.rowid AND c.element = 'commande' ";
        $req .= " WHERE 1 ".$and." group by ".$groupBy;
        $sql = $this->db->db->query($req);
        while($ln = $this->db->db->fetch_object($sql)){
            $this->datasCommande[$ln->date.$this->signatureFilter] = $ln;
        }
        
        $req = "SELECT ".$selectDate." as date, count(*) as nb, SUM(total) as tot FROM `llx_facture` a";
        if($extrafield)
            $req .= " LEFT JOIN llx_facture_extrafields f ON a.rowid = f.fk_object ";
        if($contact)
            $req .= " LEFT JOIN `llx_element_contact` ec ON ec.element_id = a.rowid LEFT JOIN `llx_c_type_contact` c ON `code` LIKE 'SALESREPFOLL' AND `fk_c_type_contact` = c.rowid AND c.element = 'facture' ";
        $req .= " WHERE 1 ".$and.$andFact." group by ".$groupBy;
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

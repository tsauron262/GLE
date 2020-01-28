<?php

class BimpCube{
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function reqPlusLog($sql){
        $result = $this->db->query($sql);
        dol_syslog("req ".$sql." terminer result : ".$result, 3, 0, "_cube");
        return $result;
    }
    
    public function callTrigerRefresh(){
        $result = 1;
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_societe();");
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_categorie();");
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_product_cat();");
        $result = $result && $this->reqPlusLog("CALL set_product_cat_flat();");
        $result = $result && $this->reqPlusLog("CALL set_types_product_cat_flat();");
        
//        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_facturedet_year_m(2017);");
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_facturedet_year_m(2018);");
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_facturedet_year_m(2019);");
//        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_commandedet_year_m(2017);");
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_commandedet_year_m(2018);");
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_commandedet_year_m(2019);");
//        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_propaldet_year_m(2017);");
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_propaldet_year_m(2018);");
        $result = $result && $this->reqPlusLog("CALL refresh_".MAIN_DB_PREFIX."mat_view_propaldet_year_m(2019);");
        if($result){
            $this->output .= "OK";
            return 'success';
        }
        return 'failure';
    }
}
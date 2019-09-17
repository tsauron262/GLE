<?php

class BimpCube{
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function callTrigerRefresh(){
        $result = 1;
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_societe();");
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_categorie();");
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_product_cat();");
        $result = $result && $this->db->query("CALL set_product_cat_flat();");
//        $result = $result && $this->db->query("CALL refresh_llx_mat_view_facturedet_year_m(2017);");
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_facturedet_year_m(2018);");
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_facturedet_year_m(2019);");
//        $result = $result && $this->db->query("CALL refresh_llx_mat_view_commandedet_year_m(2017);");
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_commandedet_year_m(2018);");
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_commandedet_year_m(2019);");
//        $result = $result && $this->db->query("CALL refresh_llx_mat_view_propaldet_year_m(2017);");
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_propaldet_year_m(2018);");
        $result = $result && $this->db->query("CALL refresh_llx_mat_view_propaldet_year_m(2019);");
        if($result){
            $this->output .= "OK";
            return 'success';
        }
        return 'failure';
    }
}
<?php

class BimpCube{
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function callTrigerRefresh(){
//        $result1 = $this->db->query("CALL refresh_llx_mat_view_propaldet();");
//        $result2 = $this->db->query("CALL refresh_llx_mat_view_facturedet();");
        $result3 = $this->db->query("CALL refresh_llx_mat_view_commandedet();");
        $result4 = $this->db->query("CALL refresh_llx_mat_view_categorie();");
        $result5 = $this->db->query("CALL refresh_llx_mat_view_product_cat();");
        if($result1 && $resul2 && $resul3 && $result4 && $result5)
            $this->output .= "OK";
    }
}
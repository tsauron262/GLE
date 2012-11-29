<?php

class user_fct {
    public $db;
    public $tmpReturn;
    public $fct = array();
    public function user_fct($DB) {
        $this->db = $DB;
        $this->detailFct['fct_select_users']='Utilisateurs';
        $this->detailFct['fct_select_societes']='Soci&eacute;t&eacute;';
        $this->detailFct['fct_select_produits']='Produits';
        $this->detailFct['fct_selectyesno']='Oui/Non';
    }
    public function listAvailable(){
        $class_methods = get_class_methods($this);
        $this->fct=array();
        foreach ($class_methods as $method_name) {
            $name = $this->detailFct[$method_name];
            if (preg_match("/^fct_/",$method_name))
                $this->fct[$method_name]=$name;
        }

    }
    public function fct_select_users($selected='',$htmlname='userid',$show_empty=1,$exclude='',$disabled=0,$display=false){
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
        $html = new form($this->db);
        $ret = $html->select_users($selected,$htmlname,$show_empty,$exclude,$disabled,$display);
        $this->tmpReturn = $thml->tmpReturn;
        return($ret);
    }
    public function fct_select_company($selected='',$htmlname='socid',$filter='',$showempty=1,$display=false,$extra=""){
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
        return($html->select_company($selected,$htmlname,$filter,$showempty,$display,$extra));
    }
    public function fct_select_produits($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$status=1,$useJquery=true,$returnField=true,$display=true,$displayLabel=true){
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
        return($html->fct_select_produits($selected,$htmlname,$filtertype,$limit,$price_level,$status,$useJquery,$returnField,$display,$displayLabel));
    }
    public function fct_selectyesno($name='yesno',$value='',$option=0,$class=''){
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
        return($html->selectyesno($name,$value,$option,$class));
    }
}
?>
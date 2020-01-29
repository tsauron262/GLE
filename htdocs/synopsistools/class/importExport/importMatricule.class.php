<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importMatricule extends import8sens {

    public function __construct($db) {
        parent::__construct($db);
        $this->path = $this->path . "matricule/";
    }

    public function go() {
        parent::go();
    }

    function traiteLn($ln) {
        global $user;
        if (isset($ln['Matricule']) && $ln['Matricule'] != "") {
            echo $ln['Matricule']."<br/>";
            
            $sql = $this->db->query("SELECT * FROM llx_user WHERE lastname LIKE '".addslashes($ln['Nom'])."' AND  firstname LIKE '".addslashes($ln['Prenom'])."'");
            if($this->db->num_rows($sql) == 1){
            	while ($lnBdd = $this->db->fetch_object($sql)){
            		$this->db->query("UPDATE llx_user SET matricule = '".$ln['Matricule']."' WHERE rowid = ".$lnBdd->rowid);
            	}
            }
            elseif($this->db->num_rows($sql) > 1){
            	echo "trop de retour : ".print_r($ln,1)."<br/>";
            }else{
            	echo "Pas de retour : ".print_r($ln,1)."<br/>";
            }
        }
    }


}

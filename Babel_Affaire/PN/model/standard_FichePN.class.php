<?php

class standard_FichePN {
    public $db;
    public function standard_FichePN($DB) {
        $this->db = $DB;
        $this->name = "standard";
        $this->description = "Fiche PN standard";
    }
    public function info(){
        return array('name' => $this->name, "description" => $this->description);
    }
    public function write(){
        //6 tables
        //Matériel
        //Achat matéiel
        //Service
        //Achat services
        //RH en option
        //Total

    }
}


?>


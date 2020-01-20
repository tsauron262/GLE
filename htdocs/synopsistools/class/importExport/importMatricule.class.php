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
            echo $ln['Matricule'];
        }
    }


}

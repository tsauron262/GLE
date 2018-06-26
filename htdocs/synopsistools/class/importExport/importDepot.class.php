<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importDepot extends import8sens {

    public function __construct($db) {
        parent::__construct($db);
        $this->path = $this->path . "depot/";
    }

    public function go() {
        parent::go();
    }

    function traiteLn($ln) {
        global $user;
        if (isset($ln['DepCode']) && $ln['DepCode'] != "" && $ln['DepIsSleep'] == 0 && $ln['DepIsSupp'] == 0) {
            require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
            $entrepot = new Entrepot($this->db);
            $entrepot->fetch('', $ln['DepCode']);
            
            
            if ($entrepot->id > 0)
                echo "<br/>depot connue";// . print_r($entrepot, 1);
            else
                $entrepot->id = $this->createEntrepot($ln);
            
            $entrepot->address = $ln['DepGAdrRue1'];
            $entrepot->zip = $ln['DepGAdrZip'];
            $entrepot->town = $ln['DepGAdrCity'];
            $entrepot->lieu = $ln['DepLib'];
            $entrepot->description = $ln['DepLib'];
            $entrepot->statut = 1;
            $entrepot->update($entrepot->id, $user);
        }
    }

    function createEntrepot($ln) {
        global $user;
        require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
        $entrepot = new Entrepot($this->db);
        $entrepot->label = $ln['DepCode'];
        return $entrepot->create($user);
    }

}

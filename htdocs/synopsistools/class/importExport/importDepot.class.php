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
            
            
            
		$sql = "UPDATE ".MAIN_DB_PREFIX."entrepot ";
		$sql .= " SET label = '" . $this->db->escape($this->libelle) ."'";
		$sql .= ", fk_parent = " . (($this->fk_parent > 0) ? $this->fk_parent : "NULL");
		$sql .= ", description = '" . $this->db->escape($this->description) ."'";
		$sql .= ", statut = " . $this->statut;
		$sql .= ", lieu = '" . $this->db->escape($this->lieu) ."'";
		$sql .= ", address = '" . $this->db->escape($this->address) ."'";
		$sql .= ", zip = '" . $this->db->escape($this->zip) ."'";
		$sql .= ", town = '" . $this->db->escape($this->town) ."'";
		$sql .= ", fk_pays = " . $this->country_id;
		$sql .= " WHERE rowid = " . $id;
            
            if ($entrepot->id > 0)
                echo "<br/>depot connue";// . print_r($entrepot, 1);
            else
                $entrepot->id = $this->createDepot($ln);
            
            $entrepot->address = $ln['DepGAdrRue1'];
            $entrepot->zip = $ln['DepGAdrZip'];
            $entrepot->town = $ln['DepGAdrCity'];
            $entrepot->lieu = $ln['DepLib'];
            $entrepot->description = $ln['DepLib'];
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

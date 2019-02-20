<?php

class annexeManip {

    var $tabExiste = array();

    function __construct($db) {
        $this->db = $db;
    }

    function fetchContrat($contratId) {
        $this->objectId = $contratId;
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe WHERE contrat_refid = " . $contratId;
        $sql1 = $this->db->query($requete);
        while ($ligne = $this->db->fetch_object($sql1))
            $this->tabExiste[$ligne->annexe_refid] = $ligne;
    }
    
    function getIdAnnexeByCode($string){
        $sql = $this->db->query("SELECT id FROM `llx_Synopsis_contrat_annexePdf` WHERE `ref` LIKE '".$string."'");
        if($this->db->num_rows($sql) < 1)
            return 0;
        $ln = $this->db->fetch_object($sql);
        return $ln->id;
    }

    function addAnnexe($val, $rang = 0) {
        if(is_string($val)){
            $val = $this->getIdAnnexeByCode($val);
        }
        
        if($val < 1)
            return 0;
        
        if (isset($this->tabExiste[$val])) {
            if ($this->tabExiste[$val]->rang != $rang) {
                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe SET rang = " . $rang . " WHERE
                                annexe_refid = " . $val . " AND  contrat_refid = " . $this->objectId;
                $sql = $this->db->query($requete);
            } else
                $sql = true;
        } else {
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe
                                (annexe_refid, contrat_refid, rang )
                         VALUES (" . $val . "," . $this->objectId . "," . $rang . ")";
            $sql = $this->db->query($requete);
        }
        return $sql;
    }

}

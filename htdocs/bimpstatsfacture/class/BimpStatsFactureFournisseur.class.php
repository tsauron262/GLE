<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class BimpStatsFactureFournisseur {

    /**
     * 'd' => HTML détail
     * 'r' => HTML réduit
     * 'c' => CSV
     */
    private $db;
    private $mode;
    public $errors;

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    function __construct($db) {
        $this->db = $db;
    }

    public function getFactures($dateStart, $dateEnd, $types, $centres, $statut, $sortBy, $taxes, $etats, $format, $nomFichier) {
        // TODO MAJ BDD
        $this->mode = $format;
        $facids = $this->getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $etats);    // apply filter
//        $hash = $this->getFields($facids, $taxes);      // get all information about filtered factures
//        $hash = $this->addMargin($hash);
//        if ($this->mode == 'd') {
//            $hash = $this->addSocieteURL($hash);
//            $hash = $this->addFactureURL($hash);
//            $hash = $this->addPaiementURL($hash);
//            $hash = $this->addSavURL($hash);
//        }
//        $hash = $this->addStatut($hash);
//        $t_to_types = $this->getExtrafieldArray('facture', 'type');
//        $c_to_centres = $this->getExtrafieldArray('facture', 'centre');
//        $hash = $this->convertType($hash, $t_to_types);
//        $hash = $this->convertCenter($hash, $c_to_centres);
//        $out = $this->sortHash($hash, $sortBy);
//        if ($this->mode == 'c') {
//            $this->putCsv($out, $nomFichier);
//            $out['urlCsv'] = "<a href='" . DOL_URL_ROOT . "/document.php?modulepart=synopsischrono&attachment=1&file=/export/exportGle/" . $nomFichier . ".csv' class='butAction'>Fichier</a>";
//        }
        return $facids;
    }
    
        /* Filter facture */

    private function getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $etats) {
        $ids = array();
        $sql = 'SELECT f.rowid as facid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields as e ON f.rowid = e.fk_object';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_factSAV as fs ON f.rowid = fs.idFact';
        $sql .= ' WHERE f.datef >= ' . $this->db->idate($dateStart);
        $sql .= ' AND   f.datef <= ' . $this->db->idate($dateEnd);
        
        

        if (!empty($types) and in_array('NRS', $types)) {   // Non renseigné inclut selected
            $sql .= ' AND (e.type IN (\'' . implode("','", $types) . '\', "0", "1")';
            $sql .= ' OR e.type IS NULL)';
        } else if (!empty($types)) {     // Non renseigné NOT selected
            $sql .= ' AND e.type IN (\'' . implode("','", $types) . '\')';
        }

        $sql .= " AND (";
        if (!empty($centres)) {
            $sql .= ' (e.centre IN (\'' . implode("','", $centres) . '\')';
            $sql .= ' OR fs.centre IN (\'' . implode("','", $centres) . '\'))';
            if (in_array('NRS', $centres)) {
                $sql .= " OR ((e.centre IS NULL OR e.centre = '1')";
                $sql .= " AND (fs.centre IS NULL OR fs.centre = '1'))";
            }
        } else {
            $sql .= "1";
        }
        $sql .= ")";

//        if (!empty($centres) and in_array('NRS', $centres)) {   // Non renseigné selected
//            $sql .= ' AND (e.centre IN (\'' . implode("','", $centres) . '\', "0")';
//            $sql .= ' OR fs.centre IN (\'' . implode("','", $centres) . '\', "0")';
//            $sql .= ' OR e.centre IS NULL';
//            $sql .= ' OR fs.centre IS NULL)';
//        } else if (!empty($centres)) {
//            $sql .= ' AND (e.centre IN (\'' . implode("','", $centres) . '\')';
//            $sql .= ' OR fs.centre IN (\'' . implode("','", $centres) . '\'))';
//        }

        if (!empty($etats)) {
            $sql .= ' AND f.fk_statut IN (\'' . implode("','", $etats) . '\')';
        }

        if ($statut == 'p') // payed
            $sql .= ' AND f.paye = 1';
        elseif ($statut == 'u') //unpayed
            $sql .= ' AND f.paye = 0';

//        echo $sql . "\n";
        dol_syslog(get_class($this) . "::getFactureIds sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $ids[] = $obj->facid;
            }
        }
        return $ids;
    }

}

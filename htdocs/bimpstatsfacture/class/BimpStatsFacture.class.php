<?php

/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file       /htdocs/bimpstatsfacture/class/BimpStatsFacture.class.php
 * 	\ingroup    bimpstatsfacture
 * 	\brief      Class
 */
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/margin/lib/margins.lib.php';

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsischrono/class/chrono.class.php';

class BimpStatsFacture {

    /**
     * 'd' => HTML détail
     * 'r' => HTML réduit
     * 'c' => CSV
     */
    private $mode;

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    function __construct($db) {
        $this->db = $db;
    }

    function create() {
        dol_syslog(get_class($this) . '::create', LOG_DEBUG);
    }

    /* Main function, triggered when the user click on "Valider" button */

    public function getFactures($dateStart, $dateEnd, $types, $centres, $statut, $sortBy, $taxes, $etats, $format, $nomFichier) {
        // TODO MAJ BDD
        $this->mode = $format;
        $facids = $this->getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $etats);    // apply filter
        $hash = $this->getFields($facids, $taxes);      // get all information about filtered factures
        $hash = $this->addMargin($hash);
        if ($this->mode == 'd') {
            $hash = $this->addSocieteURL($hash);
            $hash = $this->addFactureURL($hash);
            $hash = $this->addPaiementURL($hash);
            $hash = $this->addSavURL($hash);
        }
        $hash = $this->addStatut($hash);
        $t_to_types = $this->getExtrafieldArray('facture', 'type');
        $c_to_centres = $this->getExtrafieldArray('facture', 'centre');
        $hash = $this->convertType($hash, $t_to_types);
        $hash = $this->convertCenter($hash, $c_to_centres);
        $out = $this->sortHash($hash, $sortBy);
        if ($this->mode == 'c') {
            $this->putCsv($out, $nomFichier);
            $out['urlCsv'] = "<a href='" . DOL_URL_ROOT . "/document.php?modulepart=synopsischrono&attachment=1&file=/export/exportGle/" . $nomFichier . ".csv' class='butAction'>Fichier</a>";
        }
        return $out;
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

    private function getFields($facids, $taxes) {

        $hash = array();

        $sql = 'SELECT f.rowid as fac_id, f.facnumber as fac_number, f.fk_statut as fac_statut,';
        $sql .= ' s.rowid as soc_id, s.nom as soc_nom,';
        $sql .= ' p.rowid as pai_id, p.ref as pai_ref,';
        $sql .= ' e.centre as centre, e.type as type,';
        $sql .= ' fs.centre as centre, fs.idSav as sav_id, fs.refSav as sav_ref,';
        $sql .= ' pf.amount as pai_paye_ttc,';
        $sql .= ' sy.description as description, sy.model_refid as saf_refid,';
        $sql .= ' sy_101.N__Serie as numero_serie, sy_101.Type_garantie as type_garantie,';

        if ($taxes == 'ttc')
            $sql.= ' f.total_ttc as fac_total';
        else    // ht
            $sql.= ' f.total as fac_total';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'facture as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe             as s  ON f.fk_soc        = s.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement_facture    as pf ON f.rowid         = pf.fk_facture';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement            as p  ON pf.fk_paiement  = p.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields as e  ON f.rowid         = e.fk_object';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_factSAV as fs ON f.rowid = fs.idFact';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'synopsischrono     as sy     ON fs.equipmentId = sy.id';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'synopsischrono_chrono_101 as sy_101 ON fs.equipmentId = sy_101.id';

        $sql .= ' WHERE f.rowid IN (\'' . implode("','", $facids) . '\')';
        $sql .= ' ORDER BY f.rowid';

        dol_syslog(get_class($this) . "::getFields sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);

        $ind = 0;
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $hash[$ind]['fac_id'] = $obj->fac_id;
                $hash[$ind]['nom_facture'] = $obj->fac_number;
                $hash[$ind]['fac_statut'] = $obj->fac_statut;
                $hash[$ind]['factotal'] = $obj->fac_total;
                $hash[$ind]['soc_id'] = $obj->soc_id;
                $hash[$ind]['nom_societe'] = $obj->soc_nom;
                $hash[$ind]['pai_id'] = $obj->pai_id;
                $hash[$ind]['ref_paiement'] = $obj->pai_ref;
                $hash[$ind]['paipaye_ttc'] = $obj->pai_paye_ttc;
                $hash[$ind]['ct'] = ($obj->centre != "0" and $obj->centre != '' and $obj->centre != false) ? $obj->centre : 0;
                $hash[$ind]['ty'] = ($obj->type != "0" and $obj->type != '' and $obj->type != false) ? $obj->type : 0;
                $hash[$ind]['equip_ref'] = $obj->description;
                $hash[$ind]['numero_serie'] = $obj->numero_serie;
                $hash[$ind]['type_garantie'] = $obj->type_garantie;
                $hash[$ind]['sav_id'] = $obj->sav_id;
                $hash[$ind]['sav_ref'] = $obj->sav_ref;
                $hash[$ind]['saf_refid'] = $obj->saf_refid;
                $ind++;
            }
        }
        return $hash;
    }

    private function addMargin($hash) {

        foreach ($hash as $id => $h) {
            $sql = 'SELECT buy_price_ht, total_ht, qty';
            $sql.= ' FROM ' . MAIN_DB_PREFIX . 'facturedet';
            $sql .= ' WHERE  fk_facture =' . $h['fac_id'];
            dol_syslog(get_class($this) . "::addMargin sql=" . $sql, LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->fetch_object($result)) {
                    $pa = $obj->buy_price_ht * $obj->qty;
                    $pv = $obj->total_ht;
                    if ($pa < 0)
                        $pa = -$pa;
                    if ($pv < 0) {
                        $pa = -$pa;
                    }
                    $hash[$id]['marge'] += $pv - $pa;
                }
            }
        }
        return $hash;
    }

    function formatPrice($in) {
        $out = str_replace(',', '.', $in);
        $out = price($out);
        if ($this->mode != 'c')
            $out .= " €";
        return $out;
    }

    private function addSocieteURL($hash) {
        foreach ($hash as $ind => $h) {
            $soc = new Societe($this->db);
            $soc->id = $h['soc_id'];
            $soc->name = $h['nom_societe'];
            $hash[$ind]['nom_societe'] = $soc->getNomUrl(1);
            unset($hash[$ind]['soc_id']);
        }
        return $hash;
    }

    private function addStatut($hash) {
        $facture = new Facture($this->db);
        foreach ($hash as $ind => $h) {
            switch ($h['fac_statut']) {
                case $facture::STATUS_DRAFT: {
                        $hash[$ind]['facstatut'] = "Brouillon";
                        break;
                    }
                case $facture::STATUS_VALIDATED: {
                        $hash[$ind]['facstatut'] = "Validée";
                        break;
                    }
                case $facture::STATUS_CLOSED: {
                        $hash[$ind]['facstatut'] = "Fermée";
                        break;
                    }
                case $facture::STATUS_ABANDONED: {
                        $hash[$ind]['facstatut'] = "Abandonnée";
                        break;
                    }
                default: break;
            }
            unset($hash[$ind]['fac_statut']);
        }
        return $hash;
    }

    private function addFactureURL($hash) {
        foreach ($hash as $ind => $h) {
            $facture = new Facture($this->db);
            $facture->id = $h['fac_id'];
            $facture->ref = $h['nom_facture'];
            $hash[$ind]['nom_facture'] = $facture->getNomUrl(1);
        }
        return $hash;
    }

    private function addSavURL($hash) {
        foreach ($hash as $ind => $h) {
            if (isset($h['sav_id'])/* and $h['saf_refid'] == 105 */) {
                $chrono = new Chrono($this->db);
                $chrono->id = $h['sav_id'];
                $chrono->ref = $h['sav_ref'];
//                $chrono->model_refid = 105;
                $hash[$ind]['sav_ref'] = $chrono->getNomUrl(1, '', '');
            }
        }
        return $hash;
    }

    private function addPaiementURL($hash) {
        foreach ($hash as $ind => $h) {
            if (isset($h['pai_id'])) {
                $pai = new Paiement($this->db);
                $pai->id = $h['pai_id'];
                $pai->ref = $h['ref_paiement'];
                $hash[$ind]['ref_paiement'] = $pai->getNomUrl(1, '', '');
            }
        }
        return $hash;
    }

    public function getExtrafieldArray($elementtype, $name) { // $elementtype = "facture"
        $out = array();

        $sql = 'SELECT param ';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'extrafields';
        $sql .= ' WHERE  elementtype = "' . $elementtype . '"';
        $sql .= ' AND name = "' . $name . '"';

        dol_syslog(get_class($this) . "::getExtrafieldArray sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $row_line = $obj->param;
            }
        }
        preg_match_all('/"(.*?)"/', $row_line, $in);

        for ($i = 1; $i < sizeof($in[1]) - 1; $i+=2) {
            $out[$in[1][$i]] = $in[1][$i + 1];
        }

        if ($name == 'centre') {
            $sql = 'SELECT valeur, label ';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'Synopsis_Process_form_list_members';
            $sql .= ' WHERE  list_refid = 11';

            dol_syslog(get_class($this) . "::getExtrafieldArray sql=" . $sql, LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->fetch_object($result)) {
                    $out[$obj->valeur] = $obj->label;
                }
            }
        }
        return $out;
    }

    private function convertCenter($hash, $centres) {
        foreach ($hash as $ind => $h) {
            $hash[$ind]['centre'] = $centres[$h['ct']];
        }
        return $hash;
    }

    private function convertType($hash, $types) {
        foreach ($hash as $ind => $h) {
            $hash[$ind]['type'] = $types[$h['ty']];
        }
        return $hash;
    }

    function putCsv($factures, $nomFichier) {
        $sautLn = "\n";
        $sep = ";";
        $sortie = "";

        foreach ($factures as $factureTab) {
            $tabHeadIsOk = false;
            $sortie .= "Tableau : " . $factureTab["title"];
            $sortie .= $sautLn;
            foreach ($factureTab["factures"] as $facture) {
                $sortie .= $sautLn;
                if (!$tabHeadIsOk) {
                    foreach ($facture as $nomChamp => $champ) {
                        $sortie .= $nomChamp;
                        $sortie .= $sep;
                    }
                    $sortie .= $sautLn;
                    $tabHeadIsOk = true;
                }

                foreach ($facture as $champ) {
                    if(!is_numeric(str_replace(",", ".", str_replace(" ", "", $champ)))){
                        $champ = str_replace('"', '', $champ);
                        $champ = '"' . $champ . '"';
                    }
                    else {
                        $champ = 666;
                    }
                    $sortie .= $champ;
                    $sortie .= $sep;
                }
            }
            $sortie .= $sautLn;
            $sortie .= $sautLn;
            $sortie .= $sautLn;
        }

        file_put_contents(DOL_DATA_ROOT . "/synopsischrono/export/exportGle/" . $nomFichier . ".csv", $sortie);
    }

    private function sortHash($hash, $sortBy) {
        $out = array();

        (in_array('c', $sortBy)) ? $sortCenter = true : $sortCenter = false;
        (in_array('t', $sortBy)) ? $sortType = true : $sortType = false;
        (in_array('g', $sortBy)) ? $sortTypeGarantie = true : $sortTypeGarantie = false;
        (in_array('e', $sortBy)) ? $sortEquipement = true : $sortEquipement = false;

        if ($sortTypeGarantie) {
            $allTypeGarantie = $this->getTypeGaranties();
        }
        if ($sortEquipement) {
            $allEquipement = $this->getEquipements();
        }

        foreach ($hash as $row) {
            if (empty($sortBy)) {
                $filtre = 'all';
                $title = 'Toutes les factures';
            } else {
                $title = '';
                $filtre = '';
                if ($sortCenter) {
                    $filtre .= $row['ct'];
                    $title .= $row['centre'] . ' - ';
                }
                if ($sortType) {
                    $filtre .= $row['ty'];
                    $title .= $row['type'] . ' - ';
                }
                if ($sortTypeGarantie) {
                    $ind = array_search($row['type_garantie'], $allTypeGarantie);
                    $filtre .= $ind . '_';
                    $title .= $row['type_garantie'] . ' - ';
                }
                if ($sortEquipement) {
                    $ind = array_search($row['equip_ref'], $allEquipement);
                    $filtre .= $ind . '_';
                    $title .= $row['equip_ref'] . ' - ';
                }
                $title = substr($title, 0, -2);
            }


            if (!isset($out[$filtre])) {
                $out[$filtre] = array('title' => $title, 'total_total' => 0, 'total_total_marge' => 0, 'total_payer' => 0, 'factures' => array());
            }
            $out[$filtre]['total_payer'] += $row['paipaye_ttc'];
            
            if(!isset($out[$filtre]['nb_facture'][$row['fac_id']])){//La facture n'est pas encore traité sinon deuxieme paiement
                $out[$filtre]['total_total'] += $row['factotal'];
                $out[$filtre]['total_total_marge'] += $row['marge'];
                $out[$filtre]['nb_facture'][$row['fac_id']] = 1;
            }
            else{//deuxieme paiement on vire les montant
                $row['factotal'] = 0;
                $row['marge'] = 0;
            }
            
            unset($row['fac_statut']);
            unset($row['soc_id']);
//            unset($row['pai_id']);
            unset($row['ct']);
            unset($row['ty']);
            unset($row['sav_id']);
            unset($row['saf_refid']);
            
            if ($this->mode != 'r'){
                //Formatae des données
                $row['factotal'] = $this->formatPrice($row['factotal']);
                $row['marge'] = $this->formatPrice($row['marge']);
                $row['paipaye_ttc'] = $this->formatPrice($row['paipaye_ttc']);
                
                $out[$filtre]['factures'][] = $row;
            }
        }

        foreach ($out as $key => $inut) {
            $out[$key]['total_total'] = $this->formatPrice($out[$key]['total_total']);
            $out[$key]['total_total_marge'] = $this->formatPrice($out[$key]['total_total_marge']);
            $out[$key]['total_payer'] = $this->formatPrice($out[$key]['total_payer']);
            $out[$key]['nb_facture'] = count($out[$key]['nb_facture']);
        }
        sort($out);
        return $out;
    }

    function getTypeGaranties() {

        $typesGarantie = array();
        $sql = 'SELECT DISTINCT Type_garantie';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'synopsischrono_chrono_101';

        dol_syslog(get_class($this) . "::getTypeGarantie sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $typesGarantie[] = $obj->Type_garantie;
            }
        }
        return $typesGarantie;
    }

    function getEquipements() {
        $equipements = array();
        $sql = 'SELECT DISTINCT description';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'synopsischrono';

        dol_syslog(get_class($this) . "::getEquipement sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $equipements[] = $obj->description;
            }
        }
        return $equipements;
    }

}

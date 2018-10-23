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
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsischrono/class/chrono.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

class BimpStatsFactureFournisseur {

    /**
     * 'd' => HTML détail
     * 'r' => HTML réduit
     * 'c' => CSV
     */
    private $mode;
    private $db;

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

    public function getFactures($dateStart, $dateEnd, $centres, $statut, $sortBy, $taxes, $etats, $format, $nomFichier) {
        $this->mode = $format;
        $facids = $this->getFactureIds($dateStart, $dateEnd, $centres, $statut, $etats);    // apply filter
        $hash = $this->getFields($facids, $taxes);      // get all information about filtered factures
//        $hash = $this->addMargin($hash);
        if ($this->mode == 'd') {
            $hash = $this->addSocieteURL($hash);
            $hash = $this->addFactureURL($hash);
            $hash = $this->addPaiementURL($hash);
        }
        $hash = $this->addStatut($hash);
        $hash = $this->addEntrepotURL($hash);
        $out = $this->sortHash($hash, $sortBy);
        if ($this->mode == 'c') {
            $this->putCsv($out, $nomFichier);
            $out['urlCsv'] = "<a href='" . DOL_URL_ROOT . "/document.php?modulepart=synopsischrono&attachment=1&file=/export/exportGle/" . $nomFichier . ".csv' class='butAction'>Fichier</a>";
        }
        return $out;
    }

    /* Filter facture */

    private function getFactureIds($dateStart, $dateEnd, $centres, $statut, $etats) {
        $ids = array();
        $sql = 'SELECT f.rowid as facid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture_fourn as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_extrafields as e ON f.rowid = e.fk_object';
        $sql .= ' WHERE f.datef >= "' . $this->db->idate($dateStart).'"';
        $sql .= ' AND   f.datef <= "' . $this->db->idate($dateEnd).'"';

        $sql .= " AND (";
        if (!empty($centres)) {
            $sql .= 'e.entrepot IN (\'' . implode("','", $centres) . '\')';
            if (in_array('NRS', $centres)) {
                $sql .= " OR e.entrepot IS NULL OR e.entrepot = '1'";
            }
        } else {
            $sql .= "1";
        }
        $sql .= ")";

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

        $sql = 'SELECT f.rowid as fac_id, f.ref as fac_ref, f.fk_statut as fac_statut,';
        $sql .= ' s.rowid as soc_id, s.nom as soc_nom,';
        $sql .= ' p.rowid as pai_id, p.ref as pai_ref,';
        $sql .= ' e.entrepot as fk_entrepot,';
        $sql .= ' pf.amount as pai_paye_ttc,';

        if ($taxes == 'ttc')
            $sql.= ' f.total_ttc as fac_total';
        else    // ht
            $sql.= ' f.total_ht as fac_total';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'facture_fourn as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe                    as s  ON f.fk_soc             = s.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiementfourn_facturefourn as pf ON f.rowid              = pf.fk_facturefourn';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiementfourn              as p  ON pf.fk_paiementfourn  = p.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_extrafields  as e  ON f.rowid              = e.fk_object';

        $sql .= ' WHERE f.rowid IN (\'' . implode("','", $facids) . '\')';
        $sql .= ' ORDER BY f.rowid';

        dol_syslog(get_class($this) . "::getFields sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);

//        echo $sql . "\n";
        $ind = 0;
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $hash[$ind]['fac_id'] = $obj->fac_id;
                $hash[$ind]['nom_facture'] = $obj->fac_ref;
                $hash[$ind]['fac_statut'] = $obj->fac_statut;
                $hash[$ind]['factotal'] = $obj->fac_total;
                $hash[$ind]['soc_id'] = $obj->soc_id;
                $hash[$ind]['nom_societe'] = $obj->soc_nom;
                $hash[$ind]['pai_id'] = (isset($obj->pai_id)) ? $obj->pai_id : '';
                $hash[$ind]['ref_paiement'] = (isset($obj->pai_ref)) ? $obj->pai_ref : '';
                $hash[$ind]['paipaye_ttc'] = $obj->pai_paye_ttc;
//                if ($obj->centre1 != "0" and $obj->centre1 != '' and $obj->centre1 != false)
//                    $hash[$ind]['ct'] = $obj->centre1;
//                elseif ($obj->centre2 != "0" and $obj->centre2 != '' and $obj->centre2 != false)
//                    $hash[$ind]['ct'] = $obj->centre2;
//                else
                $hash[$ind]['ct'] = 0;
                $hash[$ind]['fk_entrepot'] = (isset($obj->fk_entrepot)) ? $obj->fk_entrepot : '';
                $ind++;
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
            $facture = new FactureFournisseur($this->db);
            $facture->id = $h['fac_id'];
            $facture->ref = $h['nom_facture'];
            $hash[$ind]['nom_facture'] = $facture->getNomUrl(1);
        }
        return $hash;
    }

    private function addSavURL($hash) {
        foreach ($hash as $ind => $h) {
            if (isset($h['sav_id'])) {
                $chrono = new Chrono($this->db);
                $chrono->id = $h['sav_id'];
                $chrono->ref = $h['sav_ref'];
                $hash[$ind]['sav_ref'] = $chrono->getNomUrl(1, '', '');
            }
        }
        return $hash;
    }

    private function addPaiementURL($hash) {
        foreach ($hash as $ind => $h) {
            if (isset($h['pai_id']) && $h['pai_id'] != '') {
                $pai = new PaiementFourn($this->db);
                $pai->id = $h['pai_id'];
                $pai->ref = $h['ref_paiement'];
                $hash[$ind]['ref_paiement'] = $pai->getNomUrl(1);
            }
        }
        return $hash;
    }

    private function addEntrepotURL($hash) {
        $allEntrepots = $this->getAllEntrepots();

        foreach ($hash as $ind => $h) {
            if (isset($h['fk_entrepot']) && $h['fk_entrepot'] != '') {
                $entrepot = new Entrepot($this->db);
                $entrepot->id = $h['fk_entrepot'];
                $entrepot->libelle = $allEntrepots[$h['fk_entrepot']];
                if ($this->mode == 'd')
                    $hash[$ind]['centre_url'] = $entrepot->getNomUrl(1);
                $hash[$ind]['centre'] = $allEntrepots[$h['fk_entrepot']];
                $hash[$ind]['ct'] = $h['fk_entrepot'];
            } else {
                unset($hash[$ind]['ct']);
            }
        }

        return $hash;
    }

    public function getAllEntrepots() {

        $entrepots = array();

        $sql = 'SELECT rowid, ref';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $entrepots[$obj->rowid] = $obj->ref;
            }
        }
        return $entrepots;
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
                $row_line = (isset($obj->param)) ? $obj->param : '';
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

            $out['NRS'] = 'Non renseigné';
            dol_syslog(get_class($this) . "::getExtrafieldArray sql=" . $sql, LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->fetch_object($result)) {
                    $out[$obj->valeur] = (isset($obj->label)) ? $obj->label : '';
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
            $hash[$ind]['type'] = isset($types[$h['ty']]) ? $types[$h['ty']] : '';
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
                    if (!is_numeric(str_replace(",", ".", str_replace(" ", "", $champ)))) {
                        $champ = str_replace('"', '', $champ);
                    } else {
                        $champ = str_replace(' ', '', $champ);
                    }
                    $champ = '"' . $champ . '"';
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
        $place = 'Entrepôt';

        (in_array('c', $sortBy)) ? $sortCenter = true : $sortCenter = false;

        foreach ($hash as $row) {
            if (empty($sortBy)) {
                $filtre = 'all';
                $title = 'Toutes les factures';
            } else {
                $title = '';
                $filtre = '';
                if ($sortCenter != '') {
                    $filtre .= $row['ct'];
                    $title .= ($row['centre'] != '' ? $row['centre'] . ' - ' : 'Sans ' . $place . ' - ');
                }
                $title = substr($title, 0, -2);
            }


            if (!isset($out[$filtre])) {
                $out[$filtre] = array('title' => $title, 'total_total' => 0, /* 'total_total_marge' => 0, */ 'total_payer' => 0, 'factures' => array());
            }
            $out[$filtre]['total_payer'] += $row['paipaye_ttc'];

            if (!isset($out[$filtre]['nb_facture'][$row['fac_id']])) {//La facture n'est pas encore traité sinon deuxieme paiement
                $out[$filtre]['total_total'] += $row['factotal'];
//                $out[$filtre]['total_total_marge'] += $row['marge'];
                $out[$filtre]['nb_facture'][$row['fac_id']] = 1;
            } else {//deuxieme paiement on vire les montant
                $row['factotal'] = 0;
//                $row['marge'] = 0;
            }

            unset($row['fac_statut']);
            unset($row['soc_id']);
            if ($this->mode != 'd')
                unset($row['pai_id']);
            unset($row['ct']);
            unset($row['ty']);
            unset($row['sav_id']);
            unset($row['saf_refid']);
            unset($row['fk_entrepot']);

            if ($this->mode != 'r') {
                //Formatage des données
                $row['factotal'] = $this->formatPrice($row['factotal']);
//                $row['marge'] = $this->formatPrice($row['marge']);
                $row['paipaye_ttc'] = $this->formatPrice($row['paipaye_ttc']);
                $out[$filtre]['factures'][] = $row;
            }
        }

        foreach ($out as $key => $inut) {
            $out[$key]['total_total'] = $this->formatPrice($out[$key]['total_total']);
//            $out[$key]['total_total_marge'] = $this->formatPrice($out[$key]['total_total_marge']);
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

    public function parseCenter($user, $centers) {

        $sql = 'SELECT apple_centre';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'user_extrafields';
        $sql .= ' WHERE fk_object=' . $user->id;

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $ct = explode(' ', $obj->apple_centre);
            }
        }

        foreach ($centers as $letter => $inut) {
            if (!in_array($letter, $ct))
                unset($centers[$letter]);
        }
        return $centers;
    }

}
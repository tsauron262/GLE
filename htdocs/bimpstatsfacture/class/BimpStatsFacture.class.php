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
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsischrono/class/chrono.class.php';

class BimpStatsFacture {

    /**
     * 'd' => HTML détail
     * 'r' => HTML réduit
     * 'c' => CSV
     */
    private $mode;
    private $db;

//    private $is_common;

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

    public function getFactures($user, $dateStart, $dateEnd, $types, $centres, $statut, $sortBy, $taxes, $etats, $type, $format, $nomFichier, $placeType) {
        // TODO MAJ BDD
        $this->mode = $format;
        $facids = $this->getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $etats, $type, $user, $placeType);    // apply filter
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
        $hash = $this->convertType($hash, $t_to_types);
        if ($placeType == 'c') {    // centre
            $c_to_centres = $this->getExtrafieldArray('facture', 'centre');
            $hash = $this->convertCenter($hash, $c_to_centres);
        } else { // entrepot
            $hash = $this->addEntrepotURL($hash);
        }
        $out = $this->sortHash($hash, $sortBy, $placeType);
        if ($this->mode == 'c') {
            $this->putCsv($out, $nomFichier);
            $out['urlCsv'] = "<a href='" . DOL_URL_ROOT . "/document.php?modulepart=bimpstatsfacture&attachment=1&file=/export_fact/" . $nomFichier . ".csv' class='butAction'>Fichier</a>";
        }
        return $out;
    }

    /* Filter facture */

    private function getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $etats, $type, $user, $placeType) {
        $ids = array();
        $sql = 'SELECT f.rowid as facid, fs.id as idSav1';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields as e ON f.rowid = e.fk_object';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bs_sav as fs ON f.rowid = fs.id_facture || f.rowid = fs.id_facture_avoir || f.rowid = fs.id_facture_acompte';
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bs_sav as fs2 ON f.rowid = fs2.id_facture_acompte';
        $sql .= ' WHERE f.date_valid >= "' . $this->db->idate($dateStart).'"';
        $sql .= ' AND   f.date_valid <= "' . $this->db->idate($dateEnd).'"';

        if (!empty($types) and in_array('NRS', $types)) {   // Non renseigné inclut selected
            $sql .= ' AND (e.type IN (\'' . implode("','", $types) . '\', "0", "1")';
            $sql .= ' OR e.type IS NULL)';
        } else if (!empty($types)) {     // Non renseigné NOT selected
            $sql .= ' AND e.type IN (\'' . implode("','", $types) . '\')';
        }

        $sql .= " AND (";
        if (!empty($centres) and $placeType == 'c') {
            $sql .= ' (e.centre IN (\'' . implode("','", $centres) . '\')';
            $sql .= ' OR fs.code_centre IN (\'' . implode("','", $centres) . '\')';
            if (in_array('NRS', $centres)) {
                $sql .= " OR ((e.centre IS NULL OR e.centre = '1')";
                $sql .= " AND (fs.code_centre IS NULL OR fs.code_centre = '1')";
            }
        } elseif (!empty($centres) and $placeType == 'e') {
            $sql .= '(e.entrepot IN (\'' . implode("','", $centres) . '\')';
            $sql .= ' OR fs.id_entrepot IN (\'' . implode("','", $centres) . '\')';
            if (in_array('NRS', $centres)) {
                $sql .= " OR e.entrepot IS NULL OR e.entrepot = '1'";
            }
            $sql .= ")";
        } else {
            $sql .= "1";
        }
        $sql .= ")";

        if ($user->rights->bimpstatsfacture->factureCentre->read and ! $user->rights->bimpstatsfacture->facture->read) {
            $tab_center = explode(' ', $user->array_options['options_apple_centre']);
            $sql .= ' AND (fs.code_centre IN ("' . implode('","', $tab_center) . '")';
            $sql .= ' OR e.centre IN ("' . implode('","', $tab_center) . '"))';
        }

        if (!empty($etats)) {
            $sql .= ' AND f.fk_statut IN (\'' . implode("','", $etats) . '\')';
        }
        if (!empty($type)) {
            $sql .= ' AND f.type IN (\'' . implode("','", $type) . '\')';
        }

        if ($statut == 'p') // payed
            $sql .= ' AND f.paye = 1';
        elseif ($statut == 'u') //unpayed
            $sql .= ' AND f.paye = 0';

//        echo $sql . "\n";die;
        dol_syslog(get_class($this) . "::getFactureIds sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $idSav = ($obj->idSav1 ? $obj->idSav1 : $obj->idSav2);
                $ids[] = array($obj->facid, $idSav);
            }
        }
        return $ids;
    }

    private function getFields($facids, $taxes) {

        $hash = array();
        $ind = 0;

        foreach ($facids as $fact) {
            $sql = 'SELECT f.rowid as fac_id, prop.rowid as prop_id, f.facnumber as fac_number, f.fk_statut as fac_statut,';
            $sql .= ' s.rowid as soc_id, s.nom as soc_nom,';
            $sql .= ' p.rowid as pai_id, p.ref as pai_ref,';
            $sql .= ' e.centre as centre2, e.type as type, e.entrepot as fk_entrepot2,';
            $sql .= ' pf.amount as pai_paye_ttc,';
            $sql .= ' f.date_valid as fact_date, ';

            if ($taxes == 'ttc')
                $sql .= ' f.total_ttc as fac_total,  SUM(prop.total) as prop_total';
            else    // ht
                $sql .= ' f.total as fac_total,  SUM(prop.total_ht) as prop_total';
            $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'facture as f';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe             as s  ON f.fk_soc        = s.rowid';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement_facture    as pf ON f.rowid         = pf.fk_facture';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement            as p  ON pf.fk_paiement  = p.rowid';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields as e  ON f.rowid         = e.fk_object';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as elel ON `sourcetype` LIKE "propal" AND `fk_target` = f.rowid AND `targettype` LIKE "facture"';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'propal as prop ON `fk_source` = prop.rowid AND prop.fk_statut != 3';

            $sql .= ' WHERE f.rowid  = ' . $fact[0];
            dol_syslog(get_class($this) . "::getFields sql=" . $sql, LOG_DEBUG);
            $result = $this->db->query($sql);



            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->fetch_object($result)) {
                    $obj2 = false;
                    if ($fact[1] > 0 || $obj->prop_id > 0) {//SAV
                        $sql = "SELECT";
                        $sql .= ' fs.code_centre as centre1, fs.id_entrepot as fk_entrepot1, fs.id as sav_id, fs.ref as sav_ref,';
                        $sql .= ' eq.product_label as description, ';
                        $sql .= ' eq.serial as numero_serie, eq.warranty_type as type_garantie,';
                        $sql .= ' re.repair_confirm_number as ggsx ';

                        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bs_sav as fs';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment as eq ON eq.id = fs.id_equipment';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_gsx_repair as re ON re.id_sav = fs.id';
                        if($fact[1] > 0)
                            $sql .= " WHERE fs.id = " . $fact[1];
                        else
                            $sql .= " WHERE fs.id_propal = " . $obj->prop_id;
                        $result2 = $this->db->query($sql);
                        if ($result2 and mysqli_num_rows($result2) > 0)
                            $obj2 = $this->db->fetch_object($result2);
                    }





                    $hash[$ind]['fac_id'] = $obj->fac_id;
                    $hash[$ind]['nom_facture'] = $obj->fac_number;
                    $hash[$ind]['fac_statut'] = $obj->fac_statut;
                    $hash[$ind]['factotal'] = $obj->fac_total;
                    $hash[$ind]['soc_id'] = $obj->soc_id;
                    $hash[$ind]['nom_societe'] = $obj->soc_nom;
                    $hash[$ind]['pai_id'] = (isset($obj->pai_id)) ? $obj->pai_id : '';
                    $hash[$ind]['ref_paiement'] = (isset($obj->pai_ref)) ? $obj->pai_ref : '';
                    $hash[$ind]['paipaye_ttc'] = $obj->pai_paye_ttc;
                    if ($obj2 AND $obj2->centre1 != "0" and $obj2->centre1 != '' and $obj2->centre1 != false)
                        $hash[$ind]['ct'] = $obj2->centre1;
                    elseif ($obj->centre2 != "0" and $obj->centre2 != '' and $obj->centre2 != false)
                        $hash[$ind]['ct'] = $obj->centre2;
                    else
                        $hash[$ind]['ct'] = 0;
                    $hash[$ind]['ty'] = ($obj->type != "0" and $obj->type != '' and $obj->type != false) ? $obj->type : 0;
                    $hash[$ind]['equip_ref'] = (isset($obj2->description)) ? $obj2->description : '';
                    if ($obj2 AND $obj2->fk_entrepot1 != "0" and $obj2->fk_entrepot1 != '' and $obj2->fk_entrepot1 != false)
                        $hash[$ind]['fk_entrepot'] = $obj2->fk_entrepot1;
                    elseif ($obj->fk_entrepot2 != "0" and $obj->fk_entrepot2 != '' and $obj->fk_entrepot2 != false)
                        $hash[$ind]['fk_entrepot'] = $obj->fk_entrepot2;
                    else
                        $hash[$ind]['fk_entrepot'] = 0;
                    $hash[$ind]['numero_serie'] = (isset($obj2->numero_serie)) ? $obj2->numero_serie : '';
                    $hash[$ind]['type_garantie'] = (isset($obj2->type_garantie)) ? $obj2->type_garantie : '';
                    $hash[$ind]['sav_id'] = ($obj2 AND isset($obj2->sav_id)) ? $obj2->sav_id : '';
                    $hash[$ind]['sav_ref'] = ($obj2 AND isset($obj2->sav_ref)) ? $obj2->sav_ref : '';
                    $hash[$ind]['ggsx'] = ($obj2 AND isset($obj2->ggsx)) ? $obj2->ggsx : '';
                    $hash[$ind]['prop_total'] = (isset($obj->prop_total)) ? $obj->prop_total : '';
                    $hash[$ind]['fact_date'] = (isset($obj->fact_date)) ? dol_print_date($this->db->jdate($obj->fact_date)) : '';
                    $ind++;
                }
            }
            else{
                die("introuvable ".$sql);
            }
        }
        return $hash;
    }

    private function addMargin($hash) {
        foreach ($hash as $id => $h) {
            $sql = 'SELECT buy_price_ht, total_ht, qty';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'facturedet';
            $sql .= ' WHERE  fk_facture =' . $h['fac_id'];
            dol_syslog(get_class($this) . "::addMargin sql=" . $sql, LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->fetch_object($result)) {
                    $pa = $obj->buy_price_ht * $obj->qty;
                    $pv = $obj->total_ht;
                    
                    //todo pas compros pourquoi
//                    if ($pa < 0)
//                        $pa = -$pa;
//                    if ($pv < 0) {
//                        $pa = -$pa;
//                    }
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
        $soc = new Societe($this->db);
        foreach ($hash as $ind => $h) {
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
        $facture = new Facture($this->db);
        foreach ($hash as $ind => $h) {
            $facture->id = $h['fac_id'];
            $facture->ref = $h['nom_facture'];
            $hash[$ind]['nom_facture'] = $facture->getNomUrl(1);
        }
        return $hash;
    }

    private function addSavURL($hash) {
        require_once DOL_DOCUMENT_ROOT . "/bimpsupport/objects/BS_SAV.class.php";
        $chrono = new BS_SAV($this->db);
        foreach ($hash as $ind => $h) {
            if (isset($h['sav_id'])) {
                $chrono->id = $h['sav_id'];
                $chrono->ref = $h['sav_ref'];
                $hash[$ind]['sav_ref'] = $chrono->getNomUrl(1, '', '');
            }
        }
        return $hash;
    }

    private function addPaiementURL($hash) {
        $pai = new Paiement($this->db);
        foreach ($hash as $ind => $h) {
            if (isset($h['pai_id']) && $h['pai_id'] != '') {
                $pai->id = $h['pai_id'];
                $pai->ref = $h['ref_paiement'];
                $hash[$ind]['ref_paiement'] = $pai->getNomUrl(1, '', '');
            }
        }
        return $hash;
    }

    private function addEntrepotURL($hash) {
        $allEntrepots = $this->getAllEntrepots();
        $entrepot = new Entrepot($this->db);
        foreach ($hash as $ind => $h) {
            if (isset($h['fk_entrepot']) && $h['fk_entrepot'] != '') {
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

        for ($i = 1; $i < sizeof($in[1]) - 1; $i += 2) {
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
        
        if($name == 'type' && count($out) < 2){
            $out = array();
            $sql = $this->db->query("SELECT * FROM `llx_bimp_c_secteur` WHERE 1");
            while($ln = $this->db->fetch_object($sql)){
                $out[$ln->clef] = $ln->valeur;
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
        
        $folder = DOL_DATA_ROOT . "/bimpstatsfacture/";
        if(!is_dir($folder))
            mkdir ($folder);
        $folder .= "/export_fact/";
        if(!is_dir($folder))
            mkdir ($folder);
        file_put_contents($folder . $nomFichier . ".csv", $sortie);
    }

    private function sortHash($hash, $sortBy, $placeType) {
        $out = array();
        if ($placeType == 'e')
            $place = 'Entrepôt';
        else
            $place = 'Centre';

        (in_array('c', $sortBy)) ? $sortCenter = true : $sortCenter = false;
        (in_array('t', $sortBy)) ? $sortType = true : $sortType = false;
        (in_array('g', $sortBy)) ? $sortTypeGarantie = true : $sortTypeGarantie = false;
        (in_array('e', $sortBy)) ? $sortEquipement = true : $sortEquipement = false;

        if ($sortTypeGarantie) {
            $allTypeGarantie = $this->getTypeGaranties();
        }
        if ($sortEquipement) {
            //$allEquipement = $this->getEquipements();
            $allEquipement = array("fdsfdsfdsfd");//pour eviter l'indice 0
        }

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
                if ($sortType != '') {
                    $filtre .= $row['ty'];
                    $title .= ($row['type'] != '' ? $row['type'] . ' - ' : 'Sans type - ');
                }
                if ($sortTypeGarantie != '') {
                    $ind = array_search($row['type_garantie'], $allTypeGarantie);
                    $filtre .= $ind . '_';
                    $title .= ($row['type_garantie'] != '' ? $row['type_garantie'] . ' - ' : 'Sans type de garantie - ');
                }
                if ($sortEquipement != '') {
                    $filtreStr = $row['equip_ref'];
                    $filtreStr = str_replace("~", "", $filtreStr);
                    $filtreStr = str_replace("VIN,", "", $filtreStr);
                    $filtreStr = lcfirst($filtreStr);
                    $ind = array_search($filtreStr, $allEquipement);
                    if($ind < 1){
                        $allEquipement[] = $filtreStr;
                        $ind = count($allEquipement) - 1;
                    }
                    $filtre .= $ind . '_';
                    $title .= ($filtreStr != '' ? $filtreStr . ' - ' : 'Sans équipement - ');
                }
                $title = substr($title, 0, -2);
            }


            if (!isset($out[$filtre])) {
                $out[$filtre] = array('title' => $title, 'total_total' => 0, 'total_total_marge' => 0, 'total_payer' => 0, 'factures' => array());
            }
            $out[$filtre]['total_payer'] += $row['paipaye_ttc'];

            if (!isset($out[$filtre]['nb_facture'][$row['fac_id']])) {//La facture n'est pas encore traité sinon deuxieme paiement
                $out[$filtre]['total_total'] += $row['factotal'];
                $out[$filtre]['total_total_prop'] += $row['prop_total'];
                $out[$filtre]['total_total_marge'] += $row['marge'];
                $out[$filtre]['nb_facture'][$row['fac_id']] = 1;
            } else {//deuxieme paiement on vire les montant
                $row['factotal'] = 0;
                $row['marge'] = 0;
            }

            unset($row['fac_statut']);
            unset($row['soc_id']);
            if ($this->mode != 'd')
                unset($row['pai_id']);
            unset($row['ct']);
            unset($row['ty']);
            unset($row['sav_id']);
            unset($row['fk_entrepot']);

            if ($this->mode != 'r') {
                //Formatage des données
                $row['factotal'] = $this->formatPrice($row['factotal']);
                $row['prop_total'] = $this->formatPrice($row['prop_total']);
                $row['marge'] = $this->formatPrice($row['marge']);
                $row['paipaye_ttc'] = $this->formatPrice($row['paipaye_ttc']);
                $out[$filtre]['factures'][] = $row;
            }
        }

        foreach ($out as $key => $inut) {
            $out[$key]['total_total'] = $this->formatPrice($out[$key]['total_total']);
            $out[$key]['total_total_prop'] = $this->formatPrice($out[$key]['total_total_prop']);
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

//    function getEquipements() {
//        $equipements = array();
//        $sql = 'SELECT DISTINCT description';
//        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'synopsischrono';
//
//        dol_syslog(get_class($this) . "::getEquipement sql=" . $sql, LOG_DEBUG);
//        $result = $this->db->query($sql);
//        if ($result and mysqli_num_rows($result) > 0) {
//            while ($obj = $this->db->fetch_object($result)) {
//                $equipements[] = $obj->description;
//            }
//        }
//        return $equipements;
//    }

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

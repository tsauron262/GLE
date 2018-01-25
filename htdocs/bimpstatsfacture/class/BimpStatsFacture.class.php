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

class BimpStatsFacture {

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

    public function getFactures($dateStart, $dateEnd, $types, $centres, $statut, $sortBy, $taxes, $etats, $format) {
        // TODO MAJ BDD
        $facids = $this->getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $etats);
        $hash = $this->getFields($facids, $taxes);
        $hash = $this->addMargin($hash);
        $hash = $this->addSocieteURL($hash);
        $hash = $this->addFactureURL($hash);
        $hash = $this->addPaiementURL($hash);
        $t_to_types = $this->getExtrafieldArray('facture', 'type');
        $c_to_centres = $this->getExtrafieldArray('facture', 'centre');
        $hash = $this->convertType($hash, $t_to_types);
        $hash = $this->convertCenter($hash, $c_to_centres);
        $out = $this->sortHash($hash, $sortBy);
        return $out;
    }

    private function getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $etats) {
//print_r($etats);echo"\n\n\n";
        $ids = array();
        $sql = 'SELECT f.rowid as facid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields as e ON f.rowid = e.fk_object';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_factSAV as fs ON f.rowid = fs.idFact';
        $sql .= ' WHERE f.datef >= ' . $this->db->idate($dateStart);
        $sql .= ' AND   f.datef <= ' . $this->db->idate($dateEnd);

        if (!empty($types) and in_array('NRS', $types)) {   // Non renseigné inclut selected TODO
            $sql .= ' AND (e.type IN (\'' . implode("','", $types) . '\', "0")';
            $sql .= ' OR e.type IS NULL)';
        } else if (!empty($types)) {     // Non renseigné NOT selected
            $sql .= ' AND e.type IN (\'' . implode("','", $types) . '\')';
        }

        if (!empty($centres) and in_array('NRS', $centres)) {   // Non renseigné selected TODO
            $sql .= ' AND (e.centre IN (\'' . implode("','", $centres) . '\', "0")';
            $sql .= ' OR fs.centre IN (\'' . implode("','", $centres) . '\', "0")';
            $sql .= ' OR e.centre IS NULL';
            $sql .= ' OR fs.centre IS NULL)';
        } else if (!empty($centres)) {
            $sql .= ' AND (e.centre IN (\'' . implode("','", $centres) . '\')';
            $sql .= ' OR fs.centre IN (\'' . implode("','", $centres) . '\'))';
        }
        
        if (!empty($etats)) {
            $sql .= ' AND f.fk_statut IN (\'' . implode("','", $etats) . '\')';
        }
//echo  'AND (f.fk_statut IN (\'' . implode("','", $etats) . '\')';
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
//        print_r($ids);
        return $ids;
    }

    private function getFields($facids, $taxes) {

        $hash = array();

        $sql = 'SELECT f.rowid as fac_id, f.facnumber as fac_number, f.fk_statut as fac_statut,';
        $sql .= ' s.rowid as soc_id, s.nom as soc_nom,';
        $sql .= ' p.rowid as pai_id, p.ref as pai_ref,';
        $sql .= ' e.centre as centre, e.type as type,';
        $sql .= ' fs.centre as centre,';
        $sql .= ' pf.amount as pai_paye_ttc, ';
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
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'Synopsis_Process_form_list_members as sp ON f.rowid = e.fk_object';

        $sql .= ' WHERE f.rowid IN (\'' . implode("','", $facids) . '\')';
        $sql .= ' ORDER BY f.rowid';

        dol_syslog(get_class($this) . "::getFields sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);

        $ind = 0;
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $hash[$ind]['fac_id'] = $obj->fac_id;
                $hash[$ind]['fac_nom'] = $obj->fac_number;
                $hash[$ind]['fac_statut'] = $obj->fac_statut;
                $hash[$ind]['factotal'] = $obj->fac_total;
                $hash[$ind]['soc_id'] = $obj->soc_id;
                $hash[$ind]['soc_nom'] = $obj->soc_nom;
                $hash[$ind]['pai_id'] = $obj->pai_id;
                $hash[$ind]['pai_ref'] = $obj->pai_ref;
                $hash[$ind]['paipaye_ttc'] = $obj->pai_paye_ttc;
                $hash[$ind]['ct'] = ($obj->centre != "0" and $obj->centre != '') ? $obj->centre : 0;
                $hash[$ind]['ty'] = ($obj->type != "0" and $obj->type != '') ? $obj->type : 0;
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

//                        

    function formatPrice($in) {
        $out = str_replace(',', '.', $in);
        return price($out) . " €";
    }

    private function addSocieteURL($hash) {
        foreach ($hash as $ind => $h) {
            $soc = new Societe($this->db);
            $soc->id = $h['soc_id'];
            $soc->name = $h['soc_nom'];
            $hash[$ind]['socurl'] = $soc->getNomUrl(1);
            unset($hash[$ind]['soc_id']);
            unset($hash[$ind]['soc_nom']);
        }
        return $hash;
    }

    private function addFactureURL($hash) {
        foreach ($hash as $ind => $h) {
            $facture = new Facture($this->db);
            $facture->id = $h['fac_id'];
            $facture->ref = $h['fac_nom'];
            $hash[$ind]['facurl'] = $facture->getNomUrl(1);
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
            unset($hash[$ind]['fac_nom']);
            unset($hash[$ind]['fac_statut']);
        }
        return $hash;
    }

    private function addPaiementURL($hash) {
        foreach ($hash as $ind => $h) {
            if (isset($h['pai_id'])) {
                $pai = new Paiement($this->db);
                $pai->id = $h['pai_id'];
                $pai->ref = $h['pai_ref'];
                $hash[$ind]['paiurl'] = $pai->getNomUrl(1);
            }
            unset($hash[$ind]['pai_ref']);
        }
        return $hash;
    }

    public function getExtrafieldArray($elementtype, $name) { // $elementtype = "faccture"
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

    private function sortHash($hash, $sortBy) {
        $out = array();

        foreach ($hash as $row) {
            if (sizeof($sortBy) == 2) {
                $filtre = $row['ty'] . '_' . $row['ct'];
                $title = $row['centre'] . " - " . $row['type'];
            } elseif (sizeof($sortBy) == 0){
                $filtre = 'all';
                $title = 'Tous les centres et tous les types';
            } elseif ($sortBy[0] == 't') {
                $filtre = $row['ty'];
                $title =  $row['type'];
            } elseif ($sortBy[0] == 'c') {
                $filtre = $row['ct'];
                $title = $row['centre'];
            }
            
            if (!isset($out[$filtre])) {
                $out[$filtre] = array('title' => $title, 'total_total' => 0, 'total_total_marge' => 0, 'total_payer' => 0, 'factures' => array());
            }
            $out[$filtre]['total_total'] += $row['factotal'];
            $out[$filtre]['total_total_marge'] += $row['marge'];
            $out[$filtre]['total_payer'] += $row['paipaye_ttc'];
            $row['factotal'] = $this->formatPrice($row['factotal']);
            $row['marge'] = $this->formatPrice($row['marge']);
            $row['paipaye_ttc'] = $this->formatPrice($row['paipaye_ttc']);
            $out[$filtre]['factures'][] = $row;
        }
        
        foreach ($out as $key => $inut) {
            $out[$key]['total_total'] = $this->formatPrice($out[$key]['total_total']);
            $out[$key]['total_total_marge'] = $this->formatPrice($out[$key]['total_total_marge']);
            $out[$key]['total_payer'] = $this->formatPrice($out[$key]['total_payer']);
        }
        return $out;
    }

}

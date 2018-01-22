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

    function getFactures($dateStart, $dateEnd, $types, $centres, $statut, $sortBy, $taxes) {

        $facids = $this->getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $sortBy);
        $hash = $this->getFields($facids, $taxes);
        $hash = $this->addMargin($hash);
        $hash = $this->addSocieteURL($hash);
        $hash = $this->addFactureURL($hash);
        $hash = $this->addPaiementURL($hash);
        $types = $this->getExtrafieldArray('facture', 'type');
        $centres = $this->getExtrafieldArray('facture', 'centre');
        $hash = $this->convertType($hash, $types);
        $hash = $this->convertCenter($hash, $centres);
        return $hash;
    }

    function getFactureIds($dateStart, $dateEnd, $types, $centres, $statut, $sortBy) {

        $ids = array();

        $sql = 'SELECT f.rowid as facid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields as e ON f.rowid = e.fk_object';
        $sql .= ' WHERE f.datec >= ' . $this->db->idate($dateStart);
        $sql .= ' AND   f.datef <= ' . $this->db->idate($dateEnd);
        $sql .= ' AND e.type IN (\'' . implode("','", $types) . '\')';
        $sql .= ' AND e.centre IN (\'' . implode("','", $centres) . '\')';

        if ($statut == 'p') // payed
            $sql .= ' AND f.paye = 1';
        elseif ($statut == 'u') //unpayed
            $sql .= ' AND f.paye = 0';

//        if (!empty($sortBy)) {
//            $sql .= ' ORDER BY ';
//            if (in_array('c', $sortBy)) {       // tri par centre
//                $sql .= ' e.centre ASC';
//                if (in_array('t', $sortBy))     // tri par centre et par type
//                    $sql .= ', e.type ASC';
//            } else
//                $sql .= ' e.type ASC';          // tri uniquement par type
//        }

//        $sql .="ORDER BY f.total";
        dol_syslog(get_class($this) . "::getFactureIds sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);

        if ($result and mysqli_num_rows($result) > 0) {
            $i = 0;
            while ($obj = $this->db->fetch_object($result)) {
                $ids[] = $obj->facid;
            }
        }
        return $ids;
    }

    function getFields($facids, $taxes) {

        $hash = array();

        $sql = 'SELECT f.rowid as fac_id, f.facnumber as fac_number, f.fk_statut as fac_statut,';
        $sql .= ' s.rowid as soc_id, s.nom as soc_nom,';
        $sql .= ' p.rowid as pai_id, p.ref as pai_ref,';
        $sql .= ' e.centre as centre, e.type as type,';
        if ($taxes == 'ttc')
            $sql.= ' f.total_ttc as fac_total, p.amount as pai_paye_ttc';
        else    // ht
            $sql.= ' f.total as fac_total, p.amount as pai_paye_ttc';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'facture as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON f.fk_soc = s.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement_facture as pf ON f.rowid = pf.fk_facture';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement as p ON p.rowid = pf.fk_paiement';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields as e ON f.rowid = e.fk_object';
        $sql .= ' WHERE f.rowid IN (\'' . implode("','", $facids) . '\')';

        dol_syslog(get_class($this) . "::getFields sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);

        $ind =0;
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
//                $ind = $obj->fac_id;
                $hash[$ind]['fac_id'] = $obj->fac_id;
                $hash[$ind]['fac_nom'] = $obj->fac_number;
                $hash[$ind]['fac_statut'] = $obj->fac_statut;
                $hash[$ind]['factotal'] = $obj->fac_total;
                $hash[$ind]['soc_id'] = $obj->soc_id;
                $hash[$ind]['soc_nom'] = $obj->soc_nom;
                $hash[$ind]['pai_id'] = $obj->pai_id;
                $hash[$ind]['pai_ref'] = $obj->pai_ref;
                $hash[$ind]['paipaye_ttc'] = $obj->pai_paye_ttc;
                $hash[$ind]['centre'] = $obj->centre;
                $hash[$ind]['type'] = $obj->type;
                $ind++;
            }
        }
        return $hash;
    }

    function addMargin($hash) {

        foreach ($hash as $id => $h) {
//            $sql = 'SELECT subprice, remise_percent, tva_tx, localtax1_tx, localtax2_tx, buy_price_ht, fk_product_fournisseur_price';
            $sql = 'SELECT buy_price_ht, total_ht';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet';
            $sql .= ' WHERE  fk_facture =' . $h['fac_id'];
            dol_syslog(get_class($this) . "::addMargin sql=" . $sql, LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->fetch_object($result)) {
                    $hash[$id]['marge'] += $obj->total_ht - $obj->buy_price_ht;
//                    $marge = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $objp->fk_product_fournisseur_price, $objp->buy_price_ht);
//                    if(isset($hash[$id]['marge'])) {
//                       $hash[$id]['marge'] += $marge[2];
//                    } else {
//                       $hash[$id]['marge'] = $marge[2];
//                    }
                }
            }
        }
        return $hash;
    }
// o prix de reviens
    function addSocieteURL($hash) {
        foreach ($hash as $ind => $h) {
            $soc = new Societe($this->db);
            $soc->id = $h['soc_id'];
            $soc->nom = $h['soc_nom'];
            $hash[$ind]['socurl'] = $soc->getNomUrl(1);
            unset($hash[$ind]['soc_id']);
            unset($hash[$ind]['soc_nom']);
        }
        return $hash;
    }

    function addFactureURL($hash) {
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

    function addPaiementURL($hash) {
        foreach ($hash as $ind => $h) {
            if (isset($h['pai_id'])) {
                $pai = new Paiement($this->db);
                $pai->id = $h['pai_id'];
                $pai->ref = $h['pai_ref'];
                $hash[$ind]['paiurl'] = $pai->getNomUrl(1);
            } else {
                unset($hash[$ind]['paipaye_ttc']);
            }
            unset($hash[$ind]['pai_id']);
            unset($hash[$ind]['pai_ref']);
        }
        return $hash;
    }

    function getExtrafieldArray($elementtype, $name) { // $elementtype = "faccture"
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
        return $out;
    }

    function convertCenter($hash, $centres) {
        foreach ($hash as $ind => $h) {
            $hash[$ind]['centre'] = $centres[$h['centre']];
        }
        return $hash;
    }

    function convertType($hash, $types) {
        foreach ($hash as $ind => $h) {
            $hash[$ind]['type'] = $types[$h['type']];
        }
        return $hash;
    }

}

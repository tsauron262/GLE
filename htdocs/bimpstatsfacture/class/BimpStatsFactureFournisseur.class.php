<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

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

    public function getFactures($dateStart, $dateEnd, $centres, $statut, $sortBy, $taxes, $etats, $format, $nomFichier) {
        // TODO MAJ BDD
        $this->mode = $format;
        $facids = $this->getFactureIds($dateStart, $dateEnd, $centres, $statut, $etats);    // apply filter
        $hash = $this->getFields($facids, $taxes);      // get all information about filtered factures
//        $hash = $this->addMargin($hash);
        if ($this->mode == 'd') {
            $hash = $this->addSocieteURL($hash);
            $hash = $this->addFactureURL($hash);
            $hash = $this->addPaiementURL($hash);
//            $hash = $this->addSavURL($hash);
        }
        $hash = $this->addStatut($hash);
//        $t_to_types = $this->getExtrafieldArray('facture', 'type');
//        $c_to_centres = $this->getExtrafieldArray('facture', 'centre');
//        $hash = $this->convertType($hash, $t_to_types);
//        $hash = $this->convertCenter($hash, $c_to_centres);
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
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_factSAV as fs ON f.rowid = fs.idFact';
        $sql .= ' WHERE f.datef >= ' . $this->db->idate($dateStart);
        $sql .= ' AND   f.datef <= ' . $this->db->idate($dateEnd);



//        if (!empty($types) and in_array('NRS', $types)) {   // Non renseigné inclut selected
//            $sql .= ' AND (e.type IN (\'' . implode("','", $types) . '\', "0", "1")';
//            $sql .= ' OR e.type IS NULL)';
//        } else if (!empty($types)) {     // Non renseigné NOT selected
//            $sql .= ' AND e.type IN (\'' . implode("','", $types) . '\')';
//        }
//
        $sql .= " AND (";
        if (!empty($centres)) {
            $sql .= ' e.entrepot IN (\'' . implode("','", $centres) . '\')';
            if (in_array('NRS', $centres)) {
                $sql .= " OR (e.entrepot IS NULL OR e.entrepot = '1')";
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

        $sql = 'SELECT f.rowid as fac_id, f.ref as ref, f.fk_statut as fac_statut,';
        $sql .= ' s.rowid as soc_id, s.nom as soc_nom,';
        $sql .= ' p.rowid as pai_id, p.ref as pai_ref,';
        $sql .= ' e.entrepot as entrepot_id,';
        $sql .= ' en.label as nom_entrepot,';
//        $sql .= ' fs.centre as centre1, fs.idSav as sav_id, fs.refSav as sav_ref,';
        $sql .= ' pf.amount as pai_paye_ttc,';
//        $sql .= ' sy.description as description, sy.model_refid as saf_refid,';
//        $sql .= ' sy_101.N__Serie as numero_serie, sy_101.Type_garantie as type_garantie,';

        if ($taxes == 'ttc')
            $sql.= ' f.total_ttc as fac_total';
        else    // ht
            $sql.= ' f.total_ht as fac_total';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'facture_fourn as f';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe                    as s  ON f.fk_soc             = s.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiementfourn_facturefourn as pf ON f.rowid              = pf.fk_facturefourn';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiementfourn              as p  ON pf.fk_paiementfourn  = p.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_extrafields  as e  ON f.rowid              = e.fk_object';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'entrepot                   as en ON e.entrepot           = en.rowid';
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_factSAV as fs ON f.rowid = fs.idFact';
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'synopsischrono     as sy     ON fs.equipmentId = sy.id';
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'synopsischrono_chrono_101 as sy_101 ON fs.equipmentId = sy_101.id';

        $sql .= ' WHERE f.rowid IN (\'' . implode("','", $facids) . '\')';
        $sql .= ' ORDER BY f.rowid';

//        echo $sql;
        dol_syslog(get_class($this) . "::getFields sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);

        $ind = 0;
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $hash[$ind]['fac_id'] = $obj->fac_id;
                $hash[$ind]['nom_facture'] = $obj->ref;
                $hash[$ind]['fac_statut'] = $obj->fac_statut;
                $hash[$ind]['factotal'] = $obj->fac_total;
                $hash[$ind]['soc_id'] = $obj->soc_id;
                $hash[$ind]['nom_societe'] = $obj->soc_nom;
                $hash[$ind]['pai_id'] = (isset($obj->pai_id)) ? $obj->pai_id : '';
                $hash[$ind]['ref_paiement'] = (isset($obj->pai_ref)) ? $obj->pai_ref : '';
                $hash[$ind]['paipaye_ttc'] = $obj->pai_paye_ttc;
                $hash[$ind]['entrepot_id'] = $obj->entrepot_id;
                $hash[$ind]['centre'] = (isset($obj->nom_entrepot)) ? $obj->nom_entrepot : '';
//                if ($obj->centre1 != "0" and $obj->centre1 != '' and $obj->centre1 != false)
//                    $hash[$ind]['ct'] = $obj->centre1;
//                elseif ($obj->centre2 != "0" and $obj->centre2 != '' and $obj->centre2 != false)
//                    $hash[$ind]['ct'] = $obj->centre2;
//                else
//                    $hash[$ind]['ct'] = 0;
//                $hash[$ind]['ty'] = ($obj->type != "0" and $obj->type != '' and $obj->type != false) ? $obj->type : 0;
//                $hash[$ind]['equip_ref'] = $obj->description;
//                $hash[$ind]['numero_serie'] = $obj->numero_serie;
//                $hash[$ind]['type_garantie'] = $obj->type_garantie;
//                $hash[$ind]['sav_id'] = $obj->sav_id;
//                $hash[$ind]['sav_ref'] = $obj->sav_ref;
//                $hash[$ind]['saf_refid'] = $obj->saf_refid;
                $ind++;
            }
        }
        return $hash;
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

    private function addFactureURL($hash) {
        foreach ($hash as $ind => $h) {
            $facture = new FactureFournisseur($this->db);
            $facture->id = $h['fac_id'];
            $facture->ref = $h['nom_facture'];
            $hash[$ind]['nom_facture'] = $facture->getNomUrl(1);
        }
        return $hash;
    }

    private function addPaiementURL($hash) {
        foreach ($hash as $ind => $h) {
            if (isset($h['pai_id']) && $h['pai_id'] != '') {
                $pai = new Paiement($this->db);
                $pai->id = $h['pai_id'];
                $pai->ref = $h['ref_paiement'];
                $hash[$ind]['ref_paiement'] = $pai->getNomUrl(1, '', '');
            }
        }
        return $hash;
    }

    private function addStatut($hash) {
        $facture = new FactureFournisseur($this->db);
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

    private function sortHash($hash, $sortBy) {
        $out = array();

        $sortCenter = (in_array('c', $sortBy)) ? true : false;

        foreach ($hash as $grp) {
            if (empty($sortBy)) {
                $title = 'Toutes les factures';
                $tag_id = 'all';
            } else {
                $title = ($grp['centre'] != '') ? $grp['centre'] : 'Non définit';
                $tag_id = $grp['entrepot_id'];
            }


            if (!isset($out[$tag_id])) {
                $out[$tag_id] = array('title' => $title, 'total_total' => 0, 'total_payer' => 0, 'factures' => array());
            }
            $out[$tag_id]['total_payer'] += $grp['paipaye_ttc'];

            if (!isset($out[$tag_id]['nb_facture'][$grp['fac_id']])) {//La facture n'est pas encore traité sinon deuxieme paiement
                $out[$tag_id]['total_total'] += $grp['factotal'];
                $out[$tag_id]['nb_facture'][$grp['fac_id']] = 1;
            } else {//deuxieme paiement on vire les montant
                $grp['factotal'] = 0;
            }

            unset($grp['fac_statut']);
            unset($grp['soc_id']);
//            unset($grp['pai_id']);
            unset($grp['ty']);
            unset($grp['sav_id']);
            unset($grp['saf_refid']);

            if ($this->mode != 'r') {
                //Formatage des données
                $grp['factotal'] = $this->formatPrice($grp['factotal']);
                $grp['paipaye_ttc'] = $this->formatPrice($grp['paipaye_ttc']);

                $out[$tag_id]['factures'][] = $grp;
            }
        }

        foreach ($out as $key => $inut) {
            $out[$key]['total_total'] = $this->formatPrice($out[$key]['total_total']);
            $out[$key]['total_payer'] = $this->formatPrice($out[$key]['total_payer']);
            $out[$key]['nb_facture'] = count($out[$key]['nb_facture']);
        }
        sort($out);
        return $out;
    }

    private function formatPrice($in) {
        $out = str_replace(',', '.', $in);
        $out = price($out);
        if ($this->mode != 'c')
            $out .= " €";
        return $out;
    }

    public function getAllEntrepots() {

        $entrepots = array();

        $sql = 'SELECT rowid, label';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $entrepots[$obj->rowid] = $obj->label;
            }
        }
        return $entrepots;
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

}

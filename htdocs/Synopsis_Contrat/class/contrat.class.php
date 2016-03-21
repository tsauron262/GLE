<?php

require_once DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php";

class Synopsis_Contrat extends Contrat {

    public $db;
    public $product;
    public $societe;
    public $user_service;
    public $user_cloture;
    public $client_signataire;
    //Special maintenance

    public $sumDInterByStatut = array();
    public $sumDInterByUser = array();
    public $sumDInterCal = array();
    public $totalDInter = 0;
    public $totalFInter = 0;

    public function _construct($db) {
        $this->db = $db;
        $this->product = new Product($this->db);
        $this->societe = new Societe($this->db);
        $this->user_service = new User($this->db);
        $this->user_cloture = new User($this->db);
        $this->client_signataire = new User($this->db);
    }

    public function getTypeContrat_noLoad($id) {
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "contrat WHERE rowid = " . $id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return($res->extraparams);
    }

    function addlineSyn($qty2, $reconductionAuto, $isSav, $sla, $durValid, $hotline, $telemaintenance, $telemaintenanceCur, $maintenance, $type, $qteTempsPerDuree, $qteTktPerDuree, $nbVisite, $nbVisiteCur, $desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type = 'HT', $pu_ttc = 0, $info_bits = 0, $fk_fournprice = null, $pa_ht = 0) {
        $id = parent::addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type = 'HT', $pu_ttc = 0, $info_bits = 0, $fk_fournprice = null, $pa_ht = 0);

        $requete2 = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO
                            (contratdet_refid,qte,tms,DateDeb,reconductionAuto,
                            isSAV, SLA, durValid,
                            hotline, telemaintenance, telemaintenanceCur, maintenance,
                            type, qteTempsPerDuree,  qteTktPerDuree, nbVisite, nbVisiteCur)
                     VALUES (" . $id . "," . $qty2 . ",now(),now(),'" . $reconductionAuto . "',
                            " . ($isSAV > 0 ? 1 : 0) . ",'" . addslashes($sla) . "'," . $durValid . ",
                            " . ($hotline <> 0 ? $hotline : 0) . "," . ($telemaintenance <> 0 ? $telemaintenance : 0) . "," . ($telemaintenanceCur <> 0 ? $telemaintenanceCur : 0) . "," . ($maintenance > 0 ? 1 : 0) . ",
                            " . $type . ", '" . $qteTempsPerDuree . "','" . $qteTktPerDuree . "','" . $nbVisite . "','" . $nbVisiteCur . "')";
        $sql1 = $this->db->query($requete2);
    }

    public function reconduction($user) {
        $this->fetch_lines();
        $dateM = 0;
        foreach ($this->lines as $ligne)
            if ($ligne->date_fin_validite > $dateM)
                $dateM = $ligne->date_fin_validite;

        $this->setDate(date("Y/m/d", $this->date_contrat), date("Y/m/d", $dateM));
    }

    public function renouvellementSimple($user) {
        $newContrat = new Synopsis_Contrat($this->db);
        $newContrat->fetch($this->id);
        $newContrat->renouvellementPart1($user, 0);
//        die($newContrat->id);
        $this->fetch_lines();
        foreach ($this->lines as $lignes) {
            $dateFin = $lignes->date_fin_validite - $lignes->date_ouverture_prevue + $newContrat->date_contrat;
            $newContrat->addlineSyn($lignes->qty2, $lignes->GMAO_Mixte['reconductionAuto'], $lignes->GMAO_Mixte['isSAV'], $lignes->GMAO_Mixte['SLA'], $lignes->GMAO_Mixte['durVal'], $lignes->GMAO_Mixte['hotline'], $lignes->GMAO_Mixte['telemaintenance'], $lignes->GMAO_Mixte['telemaintenanceCur'], $lignes->GMAO_Mixte['maintenance'], $lignes->type, $lignes->GMAO_Mixte['qteTempsPerDuree'], $lignes->GMAO_Mixte['qteTktPerDuree'], $lignes->GMAO_Mixte['nbVisiteAn'], $lignes->GMAO_Mixte['nbVisiteAnCur'], $lignes->desc, $lignes->price_ht, $lignes->qty, $lignes->tva_tx, $lignes->localtax1_tx, $lignes->localtax2_tx, $lignes->fk_product, $lignes->remise_percent, $newContrat->date_contrat, $dateFin);
        }
        $newContrat->renouvellementPart2();
        return $newContrat->id;
    }

    public function renouvellementPart1($user, $commId) {
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/revision.class.php");

        if ($commId > 0) {
            $this->commId = $commId;

            $commande = new Commande($this->db);
            $commande->fetch($commId);
            $this->date_contrat = $commande->date;
        } else
            $this->date_contrat = time();
        $this->cloture($user);

        $oldRef = $this->ref;
        $this->oldId = $this->id;
        $this->ref .= "Temp";
        $this->create($user);
        $this->ref = SynopsisRevision::convertRef($oldRef, "contrat");
        $this->setExtraParametersSimple(7);
        $this->majRef();

//        $this->validate($user);
    }

    public function renouvellementPart2() {
        global $user;
        $tabOldIdOk = array();
        $oldContrat = new Synopsis_Contrat($this->db);
        $oldContrat->fetch($this->oldId);
        $oldContrat->fetch_lines();
        $this->fetch_lines();
        foreach ($oldContrat->lines as $oldLigne) {//enreg info old ligne
            $idS = $oldLigne->id;
            foreach ($this->lines as $newLigne) {
                if (!isset($tabOldIdOk[$newLigne->id]) && $oldLigne->fk_product == $newLigne->fk_product) {
                    $idD = $newLigne->id;
                    $tabOldIdOk[$idD] = $idD;
                    $tab = getElementElement("contratdet", null, $idS);
                    foreach ($tab as $lien)
                        if ($lien['td'] != "commandedet")
                            addElementElement($lien['ts'], $lien['td'], $idD, $lien['d']);
                    $tab = getElementElement("contratdet", null, $idS, null, 0);
                    foreach ($tab as $lien)
                        if ($lien['td'] != "commandedet")
                            addElementElement($lien['ts'], $lien['td'], $idD, $lien['d'], 0);
                    $newLigne->description = $oldLigne->description;
                    $newLigne->update($user);
                    continue;
                }
            }
        }
//        $this->activeAllLigne();
        if ($this->commId > 0)
            addElementElement("commande", "contrat", $this->commId, $this->id);
        
        
        //gestion des contact
        $this->delete_linked_contact();
        $this->copy_linked_contact($oldContrat);
        $this->copy_linked_contact($oldContrat, "external");
        
        $this->db->query("INSERT INTO ".MAIN_DB_PREFIX . "Synopsis_contrat_GMAO (condReg_refid , modeReg_refid, contrat_refid)  VALUES ('".$oldContrat->condReg_refid."','".$oldContrat->modeReg_refid."'," . $this->id.")");
    }

    public function setExtraParametersSimple($extra = "") {
        if ($extra)
            $this->extraparams = $extra;
        $this->db->query("UPDATE " . MAIN_DB_PREFIX . "contrat SET extraparams = '" . $this->extraparams . "' WHERE rowid = " . $this->id);
    }

    public function activeAllLigne() {
        global $user;
        foreach ($this->lines as $ligne)
            $this->active_line($user, $ligne->id, $ligne->date_ouverture_prevue, $ligne->date_fin_validite);
    }

    public function majRef() {
        $this->db->query("UPDATE " . MAIN_DB_PREFIX . "contrat set ref ='" . $this->ref . "' WHERE rowid=" . $this->id);
//        die("UPDATE " . MAIN_DB_PREFIX . "contrat set ref ='" . $this->ref . "' WHERE rowid=" . $this->id);
    }

    function getNextNumRef($soc) {
        global $db, $langs, $conf;
        $langs->load("contract");

        $dir = DOL_DOCUMENT_ROOT . "/core/modules/contract";

        if (empty($conf->global->CONTRACT_ADDON)) {
            $conf->global->CONTRACT_ADDON = 'mod_contract_serpis';
        }

        $file = $conf->global->CONTRACT_ADDON . ".php";

        // Chargement de la classe de numerotation
        $classname = $conf->global->CONTRACT_ADDON;

        $result = include_once $dir . '/' . $file;
        if ($result) {
            $obj = new $classname();
            $numref = "";
            if (isset($this->prefRef))
                $obj->prefix = $this->prefRef;
            $numref = $obj->getNextValue($soc, $this);

            if ($numref != "") {
                return $numref;
            } else {
                dol_print_error($db, get_class($this) . "::getNextValue " . $obj->error);
                return "";
            }
        } else {
            print $langs->trans("Error") . " " . $langs->trans("Error_CONTRACT_ADDON_NotDefined");
            return "";
        }
    }

    public function initRefPlus() {
        global $conf;
        $pref = "CHM";
        $oldPref = "CT";
        $isSav = $isMaint = $isTeleMaint = $isHotline = $is8h = $isMed = $fpr = $isMed8 = $suivie = $isEph = $isMedEPg = $iscas = false;
        $this->fetch_lines();
        foreach ($this->lines as $ligne) {
            if (stripos($ligne->description, "suivi") !== false)
                $suivie = true; 
            if ($ligne->GMAO_Mixte['isSAV'])
                $isSav = true;
            if ($ligne->GMAO_Mixte['maintenance'])
                $isMaint = true;
            if ($ligne->GMAO_Mixte['telemaintenance'])
                $isTeleMaint = true;
            if ($ligne->GMAO_Mixte['hotline'] == -1 || $ligne->GMAO_Mixte['hotline'] > 0)
                $isHotline = true;

            $prod = new Product($this->db);
            $prod->fetch($ligne->fk_product);
            if (stripos($prod->ref, "YO1sante") !== false)
                $isMed = true;
            if (stripos($prod->ref, "SERV-CMB-MED") !== false)
                $isMedEph = true;
            if (stripos($prod->ref, "SERV-CMB") !== false)
                $isEph = true;
            if (stripos($prod->ref, "SERV-CMS") !== false)
                $isEph = true;
            if (stripos($prod->ref, "SERV-CMA-MAC-SANTE") !== false)
                $isMed = true;
            if (stripos($prod->ref, "FPR77") !== false)
                $isMed = true;
            if (stripos($prod->ref, "FCR04") !== false)
                $isMed8 = true;
            if (stripos($prod->ref, "fpr") !== false)
                $fpr = true;
            
            
            if (stripos($prod->ref, "SERV-CMT") !== false)
                $suivie = true;
            if (stripos($prod->ref, "SERV-CMV") !== false)
                $suivie = true;
            if (stripos($prod->ref, "cmb-zen") !== false)
                $suivie = true;
            if (stripos($prod->ref, "serv-cmzen") !== false)
                $suivie = true;
            
            if (stripos($prod->ref, "serv-cmserv") !== false)
                $iscas = true;
            
            if (stripos($ligne->GMAO_Mixte['SLA'], "8") !== false)
                $is8h = true;
//            else echo $ligne->GMAO_Mixte['SLA']."|";
            
            
            
            if (stripos($prod->ref, "serv-cmn-") !== false)
                $isCsserv8 = true;
            if (stripos($prod->ref, "serv-cm-serv") !== false)
                $isCsserv8 = true;
            
        }
//        if ($isSav)
//            $pref = "SAV";
//        if ($isMaint)
//            $pref = "MAI";
//        if ($isTeleMaint)
//            $pref = "TEL";
//        if ($fpr)
//            $pref = "CHM";
        if ($isHotline)
            $pref = "HL";
        if ($isHotline && $isTeleMaint)
            $pref = "CT";
        if ($isHotline && $is8h)
            $pref = "CD8";
        if ($suivie)
            $pref = "CS4";
        if($iscas)
            $pref = "CAS";
        if ($isMed)
            $pref = "CMED";
        if ($isMed8)
            $pref = "CMED8";
        if ($isEph)
            $pref = "C8-EPH";
        if ($isMedEph)
            $pref = "CMED-EPH";
        if ($isMedEph)
            $pref = "CMED-EPH";
        if($isCsserv8)
            $pref = "CSSERV8";
        $this->prefRef = substr($pref, 0, 2);
        if (isset($conf->global->CONTRACT_MAGRE_MASK))
            $conf->global->CONTRACT_MAGRE_MASK = str_replace($oldPref, $pref, $conf->global->CONTRACT_MAGRE_MASK);
        $soc = new Societe($this->db);
        $soc->fetch($this->socid);
        $this->ref = $this->getNextNumRef($soc);
//        $this->ref = str_replace($oldPref, $pref ."-". $oldPref, $this->ref);
        $this->majRef();
        
    }

    public function fetch($id, $ref = '') {
        $ret = parent::fetch($id, $ref);
        $id = $this->id;
        if(!$id > 0)
            return 0;
        $this->societe = new Societe($this->db);
        $this->societe->fetch($this->socid);
        $requete = "SELECT durValid,
                           unix_timestamp(DateDeb) as DateDebU,
                           fk_prod,
                           reconductionAuto,
                           qte,
                           hotline,
                           telemaintenance,
                           maintenance,
                           SLA,unix_timestamp(dateAnniv) as dateAnnivU,
                           isSAV,
                           condReg_refid, 
                           modeReg_refid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_GMAO
                     WHERE contrat_refid =" . $id;
        $sql = $this->db->query($requete);
        if (!$this->db->num_rows($sql) > 0) {
            $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_contrat_GMAO (contrat_refid) VALUES (" . $id . ")");
            $sql = $this->db->query($requete);
        }
        $res = $this->db->fetch_object($sql);

        $this->condReg_refid = (isset($res->condReg_refid) ? $res->condReg_refid : 0);
        $this->modeReg_refid = (isset($res->modeReg_refid) ? $res->modeReg_refid : 0);
        $this->durValid = $res->durValid;
        $this->DateDeb = $res->DateDebU;
        $this->dateAnniv = $res->dateAnnivU;
        $this->dateAnnivFR = date('d/m/Y', $res->dateAnnivU);
        $this->DateDebFR = date('d/m/Y', $res->DateDebU);
        $this->fk_prod = $res->fk_prod;
        if ($this->fk_prod > 0) {
            require_once(DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
            $prodTmp = new Product($this->db);
            $prodTmp->fetch($this->fk_prod);
            $this->prod = $prodTmp;
        }
        $this->reconductionAuto = $res->reconductionAuto;
        $this->qte = $res->qte;
        $this->hotline = $res->hotline;
        $this->telemaintenance = $res->telemaintenance;
        $this->maintenance = $res->maintenance;
        $this->SLA = $res->SLA;
        $this->isSAV = $res->isSAV;

        $this->type = $this->extraparams;
        $this->typeContrat = $this->type;

        $requete = "SELECT unix_timestamp(date_add(date_add(" . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.DateDeb, INTERVAL " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.durValid month), INTERVAL ifnull(" . MAIN_DB_PREFIX . "product_extrafields.2dureeSav,0) MONTH)) as dfinprev,
                               unix_timestamp(date_add(date_add(" . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.DateDeb, INTERVAL " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.durValid month), INTERVAL ifnull(" . MAIN_DB_PREFIX . "product_extrafields.2dureeSav,0) MONTH)) as dfin,
                               unix_timestamp(" . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.DateDeb) as ddeb,
                               unix_timestamp(" . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.DateDeb) as ddebprev,
                               " . MAIN_DB_PREFIX . "contratdet.qty,
                               " . MAIN_DB_PREFIX . "contratdet.rowid,
                               " . MAIN_DB_PREFIX . "contratdet.subprice as pu,
                               " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.durValid as durVal,
                               " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.fk_contrat_prod,
                               " . MAIN_DB_PREFIX . "product_extrafields.2dureeSav,
                               " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont.serial_number
                          FROM " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO, " . MAIN_DB_PREFIX . "contratdet
                     LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields ON fk_object = " . MAIN_DB_PREFIX . "contratdet.fk_product
                     LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont ON " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont.element_id = " . MAIN_DB_PREFIX . "contratdet.rowid AND " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont.element_type LIKE 'contrat%'
                         WHERE " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO.contratdet_refid = " . MAIN_DB_PREFIX . "contratdet.rowid
                           AND fk_contrat =" . $id;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $this->lineTkt[$res->rowid] = array(
                'serial_number' => $res->serial_number,
                'fk_contrat_prod' => ($res->fk_contrat_prod > 0 ? $res->fk_contrat_prod : false),
                'durVal' => $res->durVal, /*
                  'durSav' => $res->durSav, */
                'qty' => $res->qty,
                'pu' => $res->pu,
                'dfinprev' => $res->dfinprev,
                'dfin' => $res->dfin,
                'ddeb' => $res->ddeb,
                'ddebprev' => $res->ddebprev);
        }
        return($ret);
    }

    public function fetch_lines($byid = false) {
        if ($this->id > 0) {
            parent::fetch_lines();
//            $this->lines = array();
            $id = $this->id;
            $requete = "SELECT rowid FROM " . MAIN_DB_PREFIX . "contratdet WHERE fk_contrat =" . $id;
            $result = $this->db->query($requete);
            $i = 100;
            $tabLigne = array();
            while ($ligneDb = $this->db->fetch_object($result)) {
                $ligne = new Synopsis_ContratLigne($this->db);
                $ligne->fetch($ligneDb->rowid);
                $ligne->rang = ($ligne->rang > 0) ? $ligne->rang : 100;
                if ($byid)
                    $ligne->rang = $ligne->rowid;;
                if (!isset($tabLigne[$ligne->rang]))
                    $tabLigne[$ligne->rang] = $ligne;
                else {
                    $i++;
                    $tabLigne[$i] = $ligne;
                }
            }
            $this->lines = array();
            ksort($tabLigne);
            foreach ($tabLigne as $obj) {
                $this->lines[] = $obj;
            }
        } else
            echo("Pas d'id");
    }

//    
//    public function fetch_lines($byid = false) {
//        $this->nbofserviceswait = 0;
//        $this->nbofservicesopened = 0;
//        $this->nbofservicesclosed = 0;
//        $this->lignes = array();
//        // Selectionne les lignes contrats liees a un produit
//
//        $sql = "SELECT p.label,
//                       p.description as product_desc,
//                       p.ref,
//                       d.rowid,
//                       d.statut,
//                       d.description,
//                       d.price_ht,
//                       d.tva_tx,
//                       d.line_order,
//                       g.type,
//                       unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(p.durSav,0) MONTH)) as GMAO_dfinprev,
//                       unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(p.durSav,0) MONTH)) as GMAO_dfin,
//                       unix_timestamp(g.DateDeb) as GMAO_ddeb,
//                       unix_timestamp(g.DateDeb) as GMAO_ddebprev,
//                       g.durValid as GMAO_durVal,
//                       g.hotline as GMAO_hotline,
//                       g.telemaintenance as GMAO_telemaintenance,
//                       g.maintenance as GMAO_maintenance,
//                       g.SLA as GMAO_sla,
//                       g.clause as GMAO_clause,
//                       g.isSAV as GMAO_isSAV,
//                       g.qte as GMAO_qte,
//                       g.nbVisite as GMAO_nbVisite,
//                       g.fk_prod as GMAO_fk_prod,
//                       g.reconductionAuto as GMAO_reconductionAuto,
//                       g.maintenance as GMAO_maintenance,
//                       g.prorataTemporis as GMAO_prorata,
//                       g.prixAn1 as GMAO_prixAn1,
//                       g.prixAnDernier as GMAO_prixAnDernier,
//                       g.fk_contrat_prod as GMAO_fk_contrat_prod,
//                       g.qteTempsPerDuree as GMAO_qteTempsPerDuree,
//                       g.qteTktPerDuree as GMAO_qteTktPerDuree,
//                       sc.serial_number as GMAO_serial_number,
//                       d.total_ht,
//                       d.qty,
//                       d.remise_percent,
//                       d.subprice,
//                       d.info_bits,
//                       d.fk_product,
//                       date_format(d.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
//                       date_format(d.date_ouverture ,'%d/%m/%Y') as date_ouverture,
//                       date_format(d.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
//                       date_format(d.date_cloture,'%d/%m/%Y') as date_cloture,
//                       UNIX_TIMESTAMP(d.date_valid) as dateCompare,
//                       ifnull(d.avenant,9999999999) as avenant
//                  FROM " . MAIN_DB_PREFIX . "contratdet as d
//             LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON  d.fk_product = p.rowid
//             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO as g ON g.contratdet_refid = d.rowid
//             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont as sc ON sc.element_id = d.rowid AND sc.element_type LIKE 'contrat%'
//                 WHERE d.fk_contrat = " . $this->id . "
//              ORDER BY avenant, line_order";
//        $sql = "SELECT d.rowid, g.type,
//                       unix_timestamp(date_add(g.DateDeb, INTERVAL g.durValid month)) as GMAO_dfinprev,
//                       unix_timestamp(date_add(g.DateDeb, INTERVAL g.durValid month)) as GMAO_dfin,
//                       unix_timestamp(g.DateDeb) as GMAO_ddeb,
//                       unix_timestamp(g.DateDeb) as GMAO_ddebprev,
//                       g.durValid as GMAO_durVal,
//                       g.hotline as GMAO_hotline,
//                       g.telemaintenance as GMAO_telemaintenance,
//                       g.maintenance as GMAO_maintenance,
//                       g.SLA as GMAO_sla,
//                       g.clause as GMAO_clause,
//                       g.isSAV as GMAO_isSAV,
//                       g.qte as GMAO_qte,
//                       g.nbVisite as GMAO_nbVisite,
//                       g.fk_prod as GMAO_fk_prod,
//                       g.reconductionAuto as GMAO_reconductionAuto,
//                       g.maintenance as GMAO_maintenance,
//                       g.prorataTemporis as GMAO_prorata,
//                       g.prixAn1 as GMAO_prixAn1,
//                       g.prixAnDernier as GMAO_prixAnDernier,
//                       g.fk_contrat_prod as GMAO_fk_contrat_prod,
//                       g.qteTempsPerDuree as GMAO_qteTempsPerDuree,
//                       g.qteTktPerDuree as GMAO_qteTktPerDuree
//                  FROM " . MAIN_DB_PREFIX . "contratdet as d
//             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO as g ON g.contratdet_refid = d.rowid
//                 WHERE d.fk_contrat = " . $this->id;
////date_debut_prevue = $objp->date_ouverture_prevue;
//print $sql;
////                //$ligne->date_debut_reel   = $objp->date_ouverture;
//        dol_syslog("Contrat::fetch_lignes sql=" . $sql);
//        $result = $this->db->query($sql);
//        if ($result) {
//            $this->lignes = array();
//            $num = $this->db->num_rows($result);
//            $i = 0;
//
//            while ($objp = $this->db->fetch_object($result)) {
//
//                $ligne = new ContratLigne($this->db);
//                $ligne->fetch($objp->rowid);
////                $ligne->description = $objp->description;  // Description ligne
////                $ligne->description = $objp->description;  // Description ligne
////                $ligne->qty = $objp->qty;
////                $ligne->fk_contrat = $this->id;
////                $ligne->tva_tx = $objp->tva_tx;
////                $ligne->subprice = $objp->subprice;
////                $ligne->statut = $objp->statut;
////                $ligne->remise_percent = $objp->remise_percent;
////                $ligne->price = $objp->total_ht;
////                $ligne->total_ht = $objp->total_ht;
////                $ligne->fk_product = $objp->fk_product;
////                $ligne->type = $objp->type;           //contrat Mixte
////                $ligne->avenant = $objp->avenant;
////                require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
////                $tmpProd = new Product($this->db);
////                $tmpProd1 = new Product($this->db);
////                ($objp->fk_product > 0 ? $tmpProd1->fetch($objp->fk_product) : $tmpProd1 = false);
////                ($objp->GMAO_fk_contrat_prod > 0 ? $tmpProd->fetch($objp->GMAO_fk_contrat_prod) : $tmpProd = false);
////                $ligne->product = $tmpProd1;
//////LineTkt
//////                    'serial_number'=>$res->serial_number ,
//                $ligne->GMAO_Mixte = array();
//                $ligne->GMAO_Mixte = array(
//                    'fk_contrat_prod' => ($objp->GMAO_fk_contrat_prod > 0 ? $objp->GMAO_fk_contrat_prod : false),
//                    'contrat_prod' => $tmpProd,
//                    'durVal' => $objp->GMAO_durVal,
//                    'tickets' => $objp->GMAO_qte,
//                    'qty' => $objp->qty,
//                    'pu' => $objp->subprice,
//                    'dfinprev' => $objp->GMAO_dfinprev,
//                    'dfin' => $objp->GMAO_dfin,
//                    'ddeb' => $objp->GMAO_ddeb,
//                    'hotline' => $objp->GMAO_hotline,
//                    'telemaintenance' => $objp->GMAO_telemaintenance,
//                    'maintenance' => $objp->GMAO_maintenance,
//                    'SLA' => $objp->GMAO_sla,
//                    'nbVisiteAn' => $objp->GMAO_nbVisite * intval(($objp->qty > 0 ? $objp->qty : 1)),
//                    'isSAV' => $objp->GMAO_isSAV,
//                    'fk_prod' => $objp->GMAO_fk_prod,
//                    'reconductionAuto' => $objp->GMAO_reconductionAuto,
//                    'maintenance' => $objp->GMAO_maintenance,
//                    'serial_number' => $objp->GMAO_serial_number,
//                    'ddebprev' => $objp->GMAO_ddebprev,
//                    "clause" => $objp->GMAO_clause,
//                    "prorata" => $objp->GMAO_prorata,
//                    "prixAn1" => $objp->GMAO_prixAn1,
//                    "prixAnDernier" => $objp->GMAO_prixAnDernier,
//                    "qteTempsPerDuree" => $objp->GMAO_qteTempsPerDuree,
//                    "qteTktPerDuree" => $objp->GMAO_qteTktPerDuree,
//                );
//                echo "<pre>".$objp->rowid;
//                print_r( $ligne->GMAO_Mixte);
//
//
//
////
////                if ($objp->fk_product > 0) {
////                    $product = new Product($this->db);
////                    $product->id = $objp->fk_product;
////                    $product->fetch($objp->fk_product);
////                    $ligne->product = $product;
////                } else {
////                    $ligne->product = false;
////                }
////
////                $ligne->info_bits = $objp->info_bits;
////
////                $ligne->ref = $objp->ref;
////                $ligne->libelle = $objp->label;        // Label produit
////                $ligne->product_desc = $objp->product_desc; // Description produit
////
////                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
////                $ligne->date_debut_reel = $objp->date_ouverture;
////                $ligne->date_fin_prevue = $objp->date_fin_validite;
////                $ligne->date_fin_reel = $objp->date_cloture;
////                $ligne->dateCompare = $objp->dateCompare;
////                if ($byid) {
////                    $this->lignes[$objp->rowid] = $ligne;
////                } else {
////                    if ($objp->line_order != 0) {
////                        $this->lignes[$objp->line_order] = $ligne;
////                    } else {
////                        $this->lignes[] = $ligne;
////                    }
////                }
////                //dol_syslog("1 ".$ligne->description);
////                //dol_syslog("2 ".$ligne->product_desc);
////
////                if ($ligne->statut == 0)
////                    $this->nbofserviceswait++;
////                if ($ligne->statut == 4)
////                    $this->nbofservicesopened++;
////                if ($ligne->statut == 5)
////                    $this->nbofservicesclosed++;
//
//                $i++;
//            }
//            $this->db->free($result);
////            require_once('Var_Dump.php');
////            Var_Dump::Display($this->lignes);
//        } else {
//            dol_syslog("Contrat::Fetch Erreur lecture des lignes de contrats liees aux produits");
//            return -3;
//        }
////        // Selectionne les lignes contrat liees a aucun produit
////        $sql = "SELECT p.label,
////                       p.description as product_desc,
////                       p.ref,
////                       d.rowid,
////                       d.statut,
////                       d.description,
////                       d.price_ht,
////                       d.tva_tx,
////                       d.line_order,
////                       g.type,
////                       unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(p.durSav,0) MONTH)) as GMAO_dfinprev,
////                       unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(p.durSav,0) MONTH)) as GMAO_dfin,
////                       unix_timestamp(g.DateDeb) as GMAO_ddeb,
////                       unix_timestamp(g.DateDeb) as GMAO_ddebprev,
////                       g.durValid as GMAO_durVal,
////                       g.hotline as GMAO_hotline,
////                       g.telemaintenance as GMAO_telemaintenance,
////                       g.maintenance as GMAO_maintenance,
////                       g.SLA as GMAO_sla,
////                       g.clause as GMAO_clause,
////                       g.isSAV as GMAO_isSAV,
////                       g.fk_prod as GMAO_fk_prod,
////                       g.reconductionAuto as GMAO_reconductionAuto,
////                       g.maintenance as GMAO_maintenance,
////                       g.prorataTemporis as GMAO_prorata,
////                       g.prixAn1 as GMAO_prixAn1,
////                       g.prixAnDernier as GMAO_prixAnDernier,
////                       g.fk_contrat_prod as GMAO_fk_contrat_prod,
////                       sc.serial_number as GMAO_serial_number,
////                       d.total_ht,
////                       d.qty,
////                       d.remise_percent,
////                       d.subprice,
////                       d.info_bits,
////                       d.fk_product,
////                       date_format(d.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
////                       date_format(d.date_ouverture ,'%d/%m/%Y') as date_ouverture,
////                       date_format(d.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
////                       date_format(d.date_cloture,'%d/%m/%Y') as date_cloture,
////                       UNIX_TIMESTAMP(d.date_valid) as dateCompare,
////                       ifnull(d.avenant,9999999999) as avenant
////                  FROM " . MAIN_DB_PREFIX . "contratdet as d
////             LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON  d.fk_product = p.rowid
////             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO as g ON g.contratdet_refid = d.rowid
////             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont as sc ON sc.element_id = d.rowid AND sc.element_type LIKE 'contrat%'
////                 WHERE d.fk_contrat = " . $this->id . "
////                   AND (d.fk_product IS NULL OR d.fk_product = 0)";   // fk_product = 0 garde pour compatibilite
////
////        $result = $this->db->query($sql);
////        if ($result) {
////            $num = $this->db->num_rows($result);
////            $i = 0;
////
////            while ($i < $num) {
////                $objp = $this->db->fetch_object($result);
////                $ligne = new ContratLigne($this->db);
////                $ligne->id = $objp->rowid;
////                $ligne->libelle = stripslashes($objp->description);
////                $ligne->description = stripslashes($objp->description);
////                $ligne->qty = $objp->qty;
////                $ligne->statut = $objp->statut;
////                $ligne->ref = $objp->ref;
////                $ligne->tva_tx = $objp->tva_tx;
////                $ligne->subprice = $objp->subprice;
////                $ligne->type = $objp->type;
////                $ligne->remise_percent = $objp->remise_percent;
////                $ligne->price = $objp->total_ht;
////                $ligne->total_ht = $objp->total_ht;
////                $ligne->fk_product = 0;
////
////                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
////                $ligne->date_debut_reel = $objp->date_ouverture;
////                $ligne->date_fin_prevue = $objp->date_fin_validite;
////                $ligne->date_fin_reel = $objp->date_cloture;
////
////                if ($ligne->statut == 0)
////                    $this->nbofserviceswait++;
////                if ($ligne->statut == 4)
////                    $this->nbofservicesopened++;
////                if ($ligne->statut == 5)
////                    $this->nbofservicesclosed++;
////
////                require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
////                $tmpProd = new Product($this->db);
////                $tmpProd1 = new Product($this->db);
////                ($objp->fk_product > 0 ? $tmpProd1->fetch($objp->fk_product) : $tmpProd1 = false);
////                ($objp->GMAO_fk_contrat_prod > 0 ? $tmpProd->fetch($objp->GMAO_fk_contrat_prod) : $tmpProd = false);
////                $ligne->product = $tmpProd1;
//////LineTkt
//////                    'serial_number'=>$res->serial_number ,
////                $ligne->GMAO_Mixte = array();
////                $ligne->GMAO_Mixte = array(
////                    'fk_contrat_prod' => ($objp->GMAO_fk_contrat_prod > 0 ? $objp->GMAO_fk_contrat_prod : false),
////                    'contrat_prod' => $tmpProd,
////                    'durVal' => $objp->GMAO_durVal,
////                    'qty' => $objp->qty,
////                    'pu' => $objp->subprice,
////                    'dfinprev' => $objp->GMAO_dfinprev,
////                    'dfin' => $objp->GMAO_dfin,
////                    'ddeb' => $objp->GMAO_ddeb,
////                    'hotline' => $objp->GMAO_hotline,
////                    'telemaintenance' => $objp->GMAO_telemaintenance,
////                    'maintenance' => $objp->GMAO_maintenance,
////                    'SLA' => $objp->GMAO_sla,
////                    'isSAV' => $objp->GMAO_isSAV,
////                    'fk_prod' => $objp->GMAO_fk_prod,
////                    'reconductionAuto' => $objp->GMAO_reconductionAuto,
////                    'maintenance' => $objp->GMAO_maintenance,
////                    'serial_number' => $objp->GMAO_serial_number,
////                    'ddebprev' => $objp->GMAO_ddebprev,
////                    "clause" => $objp->GMAO_clause,
////                    "prorata" => $objp->GMAO_prorata,
////                    "prixAn1" => $objp->GMAO_prixAn1,
////                    "prixAnDernier" => $objp->GMAO_prixAnDernier
////                );
////
////
////                if ($byid) {
////                    $this->lignes[$objp->rowid] = $ligne;
////                } else {
////                    if ($objp->line_order != 0) {
////                        $this->lignes[$objp->line_order] = $ligne;
////                    } else {
////                        $this->lignes[] = $ligne;
////                    }
////                }
////
////                $i++;
////            }
////
////            $this->db->free($result);
////        } else {
////            dol_syslog("Contrat::Fetch Erreur lecture des lignes de contrat non liees aux produits");
////            $this->error = $this->db->error();
////            return -2;
////        }
//
//        $this->nbofservices = sizeof($this->lignes);
//
//        ksort($this->lignes);
//        return $this->lignes;
//    }


    public function displayExtraInfoCartouche() {
        return "";
    }

//
//    public function displayDialog($type = 'add', $mysoc, $objp) {
//        global $conf, $form, $db;
//        $html .= '<div id="' . $type . 'Line" class="ui-state-default ui-corner-all" style="">';
//        $html .= "<form id='" . $type . "Form' method='POST' onsubmit='return(false);'>";
//
////        $html .=  '<span class="ui-state-default ui-widget-header ui-corner-all" style="margin-top: -4px; padding: 5px 35px 5px 35px;">Ajout d\'une ligne</span>';
//        $html .= '<table style="width: 900px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
//        $html .= '<tr style="border-bottom: 1px Solid #0073EA !important">';
//        $html .= '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Recherche de produits & financement</th></tr>';
//        $html .= '<tr style="border-top: 1px Solid #0073EA !important">';
//        $html .= '<td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">Produits</td>
//                   <td style=" padding-top: 5px; padding-bottom: 3px;">';
//        // multiprix
//        $filter = "";
//        switch ($this->type) {
//            case 1:
//                //SAV
//                $filter = "1";
//                break;
//        }
//        if ($conf->global->PRODUIT_MULTIPRICES == 1)
//            $html .= $this->returnSelect_produits('', 'p_idprod_' . $type, $filter, $conf->produit->limit_size, $this->societe->price_level, 1, true, false, false);
//        else
//            $html .= $this->returnSelect_produits('', 'p_idprod_' . $type, $filter, $conf->produit->limit_size, false, 1, true, true, false);
//        if (!$conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
//            $html .= '<br>';
//
//
//
//        $html .= '</td><td  style=" padding-top: 5px; padding-bottom: 3px;border-right: 1px Solid #0073EA;">&nbsp;</td>';
//        $html .= '<tr>';
//        $html .= ' <td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">Financement ? ';
//        $html .= ' </td>
//                    <td style="width: 30px;">
//                        <input type="checkbox" id="addFinancement' . $type . '"  name="addFinancement' . $type . '" /></td>
//                    <td style="border-right: 1px Solid #0073EA; padding-top: 5px; padding-bottom: 3px;">&nbsp;</td>';
//        $html .= '</tr>';
//        $html .= "</table>";
//        $html .= '<table style="width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
//        $html .= '<tr>';
//        $html .= '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Description ligne / produit</th></tr>';
//        $html .= '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
//        $html .= '<td style="border-right: 1px Solid #0073EA;">';
//        $html .= 'Description libre<br/>';
//        $html .= '<div class="nocellnopadd" id="ajdynfieldp_idprod_' . $type . '"></div>';
//        $html .= "<textarea style='width: 600px; height: 3em' name='" . $type . "Desc' id='" . $type . "Desc'></textarea>";
//        $html .= '</td>';
//        $html .= '</tr>';
//        $html .= "</table>";
//
//        $html .= '<table style=" width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
//        $html .= '<tr>';
//        $html .= '<th style="border-bottom: 1px Solid #0073EA !important; " colspan="8"  class="ui-widget-header">Prix & Quantit&eacute;</th></tr><tr style="padding: 10px; ">';
//        $html .= '<td align=right>Prix (&euro;)</td><td align=left>';
//        $html .= "<input id='" . $type . "Price' name='" . $type . "Price' style='width: 100px; text-align: center;'/>";
//        $html .= '</td>';
//        $html .= '<td align=right>TVA<td align=left width=180>';
//        $html .= $form->load_tva($type . "Linetva_tx", "20", $mysoc, $this->societe, "", 0, false);
//
//        $html .= '</td>';
//        $html .= '<td align=right>Qt&eacute;</td><td align=left>';
//        $html .= "<input id='" . $type . "Qty' value=1 name='" . $type . "Qty' style='width: 20px;  text-align: center;'/>";
//        $html .= '</td>';
//        $html .= '<td align=right>Remise (%)</td><td align=left>';
//        $html .= "<input id='" . $type . "Remise' value=0 name='" . $type . "Remise' style='width: 20px; text-align: center;'/>";
//        $html .= '</td>';
//        $html .= '</tr>';
//
//        $html .= '</table>';
//
//        $html .= '<table style="width: 900px;  border-collapse: collapse; margin-top: 5px;"  cellpadding=10>';
//        $html .= '<tr style="border-bottom: 1px Solid #0073EA; ">';
//        $html .= '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Chronologie</th>';
//        $html .= '</tr>';
//        $html .= "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; '>";
//        $html .= '<td>Date de d&eacute;but pr&eacute;vue</td>';
//        $html .= '<td>
//                        <input value="' . date('d') . '/' . date('m') . '/' . date('Y') . '" style="text-align: center;" type="text" name="dateDeb' . $type . '" id="dateDeb' . $type . '"/>' . img_picto('calendrier', 'calendar.png', 'style="float: left;margin-right: 3px; margin-top: 1px;"') . '</td>';
//        $html .= '<td>Date de fin pr&eacute;vue</td>';
////        calendar.png
//        $html .= '<td style="border-right: 1px Solid #0073EA;">
//                        <input style="text-align: center;" type="text" name="dateFin' . $type . '" id="dateFin' . $type . '"/>' . img_picto('calendrier', 'calendar.png', 'style="float: left; margin-right: 3px; margin-top: 1px;"') . '</td>';
//        $html .= '</tr>';
//        $html .= "</table>";
//
//        $html .= '<div id="financementLigne' . $type . '" style="display: none; margin-top: 5px;">';
//        $html .= '<table style="width: 900px;  border-collapse: collapse; "  cellpadding=10>';
//        $html .= '<tr style="border-bottom: 1px Solid #0073EA; ">';
//        $html .= '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Financement</th>';
//        $html .= '</tr>';
//        $html .= "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; border-top: 1px Solid #0073EA;'>";
//        $html .= '<td align=right>Nombre de p&eacute;riode</td>';
//        //TODO ds conf
//        $html .= '<td align=left><input style="text-align: center;width: 35px;" type="text" name="nbPeriode' . $type . '" id="nbPeriode' . $type . '"/></td>';
//        $html .= '<td align=right>Type de p&eacute;riode</td>';
//        $html .= '<td align=left><select id="typePeriod' . $type . '">';
//        $requete = "SELECT * FROM Babel_financement_period ORDER BY id";
//        $sqlPeriod = $db->query($requete);
//        while ($res = $db->fetch_object($sqlPeriod)) {
//            $html .= "<option value='" . $res->id . "'>" . $res->Description . "</option>";
//        }
//        $html .= '</select>';
//        $html .= '</td>';
//        //TODO dans conf taux par défaut configurable selon droit ++ droit de choisir le taux
//        $html .= '<td align=right>Taux achat</td>';
//        $html .= '<td align=left><input style="text-align: center; width: 35px;" name="' . $type . 'TauxAchat" id="' . $type . 'TauxAchat"/></td>';
//        //TODO dans conf taux par défaut configurable selon droit + droit de choisir le taux
//        $html .= '<td align=right>Taux vente</td>';
//        $html .= '<td align=left style="border-right: 1px Solid #0073EA;">
//                        <input style="text-align: center;width: 35px;" name="' . $type . 'TauxVente" id="' . $type . 'TauxVente"/></td>';
//        $html .= '</tr>';
//        $html .= "</table>";
//        $html .= '</div>';
//
//        $html .= '</form>';
//        $html .= '</div>';
//        return ($html);
//    }

    public function getHtmlLinked($tabLiked) {
        global $langs, $conf;
        $db = $this->db;
        foreach ($tabLiked as $elem) {
            if (1) {
                print '<tr><th class="ui-widget-header ui-state-default"><table class="nobordernopadding" style="width:100%;">';
                print '<tr><th style="border:0" class="ui-widget-header ui-state-default">Contrat associ&eacute; &agrave; ';
                $val1 = $elem['s'];
                switch ($elem['ts']) {
                    case 'commande':
                        print 'la commande<td>';
                        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $this->id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
                        $comm = new Commande($db);
                        $comm->fetch($val1);
                        if ($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE == 1) {
                            $comm2 = new Synopsis_Commande($db);
                            $comm2->fetch($val1);
                            print "</table><td colspan=1 class='ui-widget-content'>" . $comm->getNomUrl(1);
                            print "<th class='ui-widget-header ui-state-default'>Prepa. commande";
                            print "<td colspan=1 class='ui-widget-content'>" . $comm2->getNomUrl(1);
                        } else {
                            print "</table><td colspan=3 class='ui-widget-content'>" . $comm->getNomUrl(1);
                        }

                        break;
                    case 'propal':
                        print 'la proposition<td>';
                        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                        require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                        $prop = new Propal($db);
                        $prop->fetch($val1);
                        print "</table><td colspan=3 class='ui-widget-content'>" . $prop->getNomUrl(1);
                        break;
                }
                $val1 = $elem['d'];
                switch ($elem['td']) {
                    case 'facture':
                        print 'la facture<td>';
                        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                        require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                        $fact = new Facture($db);
                        $fact->fetch($val1);
                        print "</table><td colspan=3 class='ui-widget-content'>" . $fact->getNomUrl(1);
                        break;
                }
            }
        }
    }

//    public function contratCheck_link() {
//        $this->linkedArray['co'] = array();
//        $this->linkedArray['pr'] = array();
//        $this->linkedArray['fa'] = array();
//        $db = $this->db;
//        //check si commande ou propale ou facture
//        if (preg_match('/^([c|p|f]{1})([0-9]*)/', $this->linkedTo, $arr)) {
//            //si commande check si propal lie a la commande / facture etc ...
//            switch ($arr[1]) {
//                case "p":
//                    //test si commande facture
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_pr WHERE fk_propale = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['co'], $res->fk_commande);
//                        }
//                    }
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "fa_pr WHERE fk_propale = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['fa'], $res->fk_facture);
//                        }
//                    }
//                    break;
//                case "c":
//                    //test si commande propal ...
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_pr WHERE fk_commande = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['pr'], $res->fk_propale);
//                        }
//                    }
//                    $requete = "SELECT fk_target as fk_facture FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'commande' AND targettype = 'facture' AND fk_source = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['fa'], $res->fk_facture);
//                        }
//                    }
//                    break;
//                case "f":
//                    //test si propal facture ...
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_fa WHERE fk_facture = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['co'], $res->fk_commande);
//                        }
//                    }
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "fa_pr WHERE fk_facture = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['pr'], $res->fk_propal);
//                        }
//                    }
//                    break;
//            }
//        }
//        //ajoute donnees dans les tables
////        var_dump($this->linkedArray);
//    }

    public function getTypeContrat() {
        $arrayT[0]['type'] = "Simple";
        $arrayT[0]['Nom'] = "Simple";
        $arrayT[1]['type'] = "Service";
        $arrayT[1]['Nom'] = "Service";
        $arrayT[2]['type'] = "Ticket";
        $arrayT[2]['Nom'] = "Au ticket";
        $arrayT[3]['type'] = "Maintenance";
        $arrayT[3]['Nom'] = "Maintenance";
        $arrayT[4]['type'] = "SAV";
        $arrayT[4]['Nom'] = "SAV";
        $arrayT[5]['type'] = "Location";
        $arrayT[5]['Nom'] = "Location de produits";
        $arrayT[6]['type'] = "LocationFinanciere";
        $arrayT[6]['Nom'] = "Location Financi&egrave;re";
        $arrayT[7]['type'] = "Mixte";
        $arrayT[7]['Nom'] = "Mixte";
        return $arrayT[intval($this->typeContrat)];
    }

    public function getExtraHeadTab($head) {
        return $head;
    }

    public function list_all_valid_contacts() {
        include_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
        $arr = array();
        $arr1 = array();
        $arr = $this->liste_contact(4, 'external');
        $arr1 = $this->liste_contact(4, 'internal');
        $arr2 = array_merge($arr, $arr1);
        $newArr = array();
        foreach ($arr2 as $id => $elem) {
            $obj = new Contact($this->db);
            $obj->fetch($elem['id']);

            $newArr[$id]['cp'] = $obj->cp;
            $newArr[$id]['ville'] = $obj->ville;
            $newArr[$id]['email'] = $obj->email;
            $newArr[$id]['tel'] = $obj->phone_pro;
            $newArr[$id]['fax'] = $obj->fax;
            foreach (array('fullname' => 'fullname', 'civility' => 'civility', 'nom' => 'lastname', 'prenom' => 'firstname') as $val0 => $val1) {
                $result = $elem[$val1];
                if ($val0 == 'fullname')
                    $result = $elem['civility'] . " " . $elem['lastname'] . " " . $elem['firstname'];
                $newArr[$id][$val0] = $result;
            }
            foreach ($elem as $nom => $val)
                $newArr[$id][$nom] = $val;
        }
        $this->element_contact_arr = $newArr;
        return($this->element_contact_arr);
    }

    public function intervRestant($val) {
        $tickets = $val->GMAO_Mixte['tickets'];

        $qteTempsPerDuree = $val->GMAO_Mixte['qteTempsPerDuree'];
        $qteTktPerDuree = $val->GMAO_Mixte['qteTktPerDuree'];


        $requete = "SELECT fd.rowid, fd.duree
                      FROM " . MAIN_DB_PREFIX . "fichinter as f,
                           " . MAIN_DB_PREFIX . "Synopsis_fichinterdet as fd,
                           " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as b
                     WHERE b.id = fd.fk_typeinterv
                       AND fd.fk_fichinter = f.rowid
                       AND b.decountTkt = 1
                       AND fd.fk_contratdet = " . $val->id;
        $consomme = 0;
        $sqlCnt = $this->db->query($requete);
        while ($resCnt = $this->db->fetch_object($sqlCnt)) {
            if ($qteTempsPerDuree == 0) {
                $consomme += $qteTktPerDuree;
            } else {
                for ($i = 0; $i < $resCnt->duree; $i+=$qteTempsPerDuree) {
                    $consomme += $qteTktPerDuree;
                }
            }
        }
        $restant = $tickets - $consomme;
        return(array('restant' => $restant, 'consomme' => $consomme));
    }

    function lignePlus($object) {
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
        $ligne = new Synopsis_ContratLigne($this->db);
        $ligne->fetch($object->rowid);
        echo "<table width='100%'><tr class='impair'><td>";
        echo "<span>Materiel : </span>";
        $_REQUEST['chrono_id'] = $object->rowid;
        $lien = new lien($this->db);
        $lien->socid = $this->socid;
        $lien->cssClassM = "type:contratdet";
        $lien->fetch(3);
        echo $lien->displayForm();
        if ($ligne->GMAO_Mixte['nbVisiteAn'] <> 0) {
            echo "</td><td>";
            echo "Nb Visite préventive : ";
            if ($ligne->GMAO_Mixte['nbVisiteAn'] == -1)
                echo "illimité";
            else
            echo $ligne->GMAO_Mixte['nbVisiteAn'] * $ligne->qty;
        }
        if ($ligne->GMAO_Mixte['nbVisiteAnCur'] <> 0) {
            echo "</td><td>";
            echo "Nb Visite curative : ";
            if ($ligne->GMAO_Mixte['nbVisiteAnCur'] == -1)
                echo "illimité";
            else
            echo $ligne->GMAO_Mixte['nbVisiteAnCur'] * $ligne->qty;
        }
        if ($ligne->GMAO_Mixte['telemaintenance'] <> 0) {
            echo "</td><td>";
            echo "Nb Télémaintenance préventive : ";
            if ($ligne->GMAO_Mixte['telemaintenance'] == -1)
                echo "illimité";
            else
            echo $ligne->GMAO_Mixte['telemaintenance'] * $ligne->qty;
        }
        if ($ligne->GMAO_Mixte['telemaintenanceCur'] <> 0) {
            echo "</td><td>";
            echo "Nb Télémaintenance curative : ";
            if ($ligne->GMAO_Mixte['telemaintenanceCur'] == -1)
                echo "illimité";
            else
            echo $ligne->GMAO_Mixte['telemaintenanceCur'] * $ligne->qty;
        }
        if ($ligne->GMAO_Mixte['hotline'] <> 0) {
            echo "</td><td>";
            echo "Nb Appels : ";
            if ($ligne->GMAO_Mixte['hotline'] == -1)
                echo "illimité";
            else
                echo $ligne->GMAO_Mixte['hotline'] * $ligne->qty;
        }
        if ($ligne->GMAO_Mixte['SLA'] != "") {

            echo "</td><td>";
            echo "SLA : ";
            echo $this->transSla($ligne->GMAO_Mixte['SLA']);
        }
        echo "</td></tr>";
        echo "</table>";
    }

    private function transSla($sla) {
        $logoSla = "";
        $intSla = 0;
        if (stripos($sla, "4") !== false)
            $intSla = 4;
        elseif (stripos($sla, "2") !== false)
            $intSla = 2;
        elseif (stripos($sla, "6") !== false)
            $intSla = 6;
        elseif (stripos($sla, "8") !== false)
            $intSla = 8;
        if ($intSla > 0) {
            $sla = $intSla . "h";
            $logoSla = '<img title="' . $sla . '" alt="SLA" style="vertical-align: middle;" src="' . DOL_URL_ROOT . '/Synopsis_Contrat/img/logoSLA' . $intSla . '.jpg"/>';
        } else
            $logoSla = $sla;
        return $logoSla;
    }

    function display1Line($object, $objL) {
        global $langs;
        $langs->load("contracts");
        $langs->load("bills");
        $idLigne = $objL->id;
        $db = $this->db;
        $productstatic = new Product($db);
        $return = '';
//        $return .= '<tr height="16" ' . $bc[false] . '>';
//        $return .= '<td class="liste_titre" width="90" style="border-left: 1px solid #' . $colorb . '; border-top: 1px solid #' . $colorb . '; border-bottom: 1px solid #' . $colorb . ';">';
//            $return .= $langs->trans("ServiceNb",$cursorline).'</td>';
//        $return .= '<td class="tab" style="border-right: 1px solid #' . $colorb . '; border-top: 1px solid #' . $colorb . '; border-bottom: 1px solid #' . $colorb . ';" rowspan="2">';
        // Area with common detail of line
        $return .= '<table class="notopnoleft" width="100%">';

        $sql = "SELECT cd.rowid, cd.statut, cd.label as label_det, cd.fk_product, cd.description, cd.price_ht, cd.qty,";
        $sql.= " cd.tva_tx, cd.remise_percent, cd.info_bits, cd.subprice,";
        $sql.= " cd.date_ouverture_prevue as date_debut, cd.date_ouverture as date_debut_reelle,";
        $sql.= " cd.date_fin_validite as date_fin, cd.date_cloture as date_fin_reelle,";
        $sql.= " cd.commentaire as comment,";
        $sql.= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype";
        $sql.= " FROM " . MAIN_DB_PREFIX . "contratdet as cd";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON cd.fk_product = p.rowid";
        $sql.= " WHERE cd.rowid = " . $idLigne;

        $result = $db->query($sql);
        if ($result) {
            $total = 0;

            $return .= '<tr class="liste_titre">';
            $return .= '<td>' . $langs->trans("Service") . '</td>';
            $return .= '<td width="50" align="center">' . $langs->trans("VAT") . '</td>';
            $return .= '<td width="50" align="right">' . $langs->trans("PriceUHT") . '</td>';
            $return .= '<td width="30" align="center">' . $langs->trans("Qty") . '</td>';
            $return .= '<td width="50" align="right">' . $langs->trans("ReductionShort") . '</td>';
            $return .= '<td width="30">&nbsp;</td>';
            $return .= "</tr>\n";

            $var = true;

            $objp = $db->fetch_object($result);

            $var = !$var;

            if ($action != 'editline' || GETPOST('rowid') != $objp->rowid) {
                $return .= '<tr ' . $bc[$var] . ' valign="top">';
                // Libelle
                if ($objp->fk_product > 0) {
                    $return .= '<td>';
                    $productstatic->id = $objp->fk_product;
                    $productstatic->type = $objp->ptype;
                    $productstatic->ref = $objp->pref;
                    $return .= $productstatic->getNomUrl(1, '', 20);
                    $return .= $objp->label ? ' - ' . dol_trunc($objp->label, 56) : '';
                    if ($objp->description)
                        $return .= '<br>' . dol_nl2br($objp->description);
                    $return .= '</td>';
                }
                else {
                    $return .= "<td>" . nl2br($objp->description) . "</td>\n";
                }
                // TVA
                $return .= '<td align="center">' . vatrate($objp->tva_tx, '%', $objp->info_bits) . '</td>';
                // Prix
                $return .= '<td align="right">' . price($objp->subprice) . "</td>\n";
                // Quantite
                $return .= '<td align="center">' . $objp->qty . '</td>';
                // Remise
                if ($objp->remise_percent > 0) {
                    $return .= '<td align="right">' . $objp->remise_percent . "%</td>\n";
                } else {
                    $return .= '<td>&nbsp;</td>';
                }
                // Icon move, update et delete (statut contrat 0=brouillon,1=valide,2=ferme)
                $return .= '<td align="right" nowrap="nowrap">';
                if ($user->rights->contrat->creer && count($arrayothercontracts) && ($object->statut >= 0)) {
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=move&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_picto($langs->trans("MoveToAnotherContract"), 'uparrow');
                    $return .= '</a>';
                } else {
                    $return .= '&nbsp;';
                }
                if ($user->rights->contrat->creer && ($object->statut >= 0)) {
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=editline&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_edit();
                    $return .= '</a>';
                } else {
                    $return .= '&nbsp;';
                }
                if ($user->rights->contrat->creer && ($object->statut >= 0)) {
                    $return .= '&nbsp;';
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=deleteline&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_delete();
                    $return .= '</a>';
                }
                $return .= '</td>';

                $return .= "</tr>\n";

                // Dates de en service prevues et effectives
                if ($objp->subprice >= 0) {
                    $return .= '<tr ' . $bc[$var] . '>';
                    $return .= '<td colspan="6">';

                    // Date planned
                    $return .= $langs->trans("DateStartPlanned") . ': ';
                    if ($objp->date_debut) {
                        $return .= dol_print_date($db->jdate($objp->date_debut));
                        // Warning si date prevu passee et pas en service
                        if ($objp->statut == 0 && $db->jdate($objp->date_debut) < ($now - $conf->contrat->services->inactifs->warning_delay)) {
                            $return .= " " . img_warning($langs->trans("Late"));
                        }
                    } else
                        $return .= $langs->trans("Unknown");
                    $return .= ' &nbsp;-&nbsp; ';
                    $return .= $langs->trans("DateEndPlanned") . ': ';
                    if ($objp->date_fin) {
                        $return .= dol_print_date($db->jdate($objp->date_fin));
                        if ($objp->statut == 4 && $db->jdate($objp->date_fin) < ($now - $conf->contrat->services->expires->warning_delay)) {
                            $return .= " " . img_warning($langs->trans("Late"));
                        }
                    } else
                        $return .= $langs->trans("Unknown");

                    $return .= '</td>';
                    $return .= '</tr>';
                }
            }
            // Ligne en mode update
            else {
                $return .= '<form name="update" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="post">';
                $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                $return .= '<input type="hidden" name="action" value="updateligne">';
                $return .= '<input type="hidden" name="elrowid" value="' . GETPOST('rowid') . '">';
                // Ligne carac
                $return .= "<tr $bc[$var]>";
                $return .= '<td>';
                if ($objp->fk_product) {
                    $productstatic->id = $objp->fk_product;
                    $productstatic->type = $objp->ptype;
                    $productstatic->ref = $objp->pref;
                    $return .= $productstatic->getNomUrl(1, '', 20);
                    $return .= $objp->label ? ' - ' . dol_trunc($objp->label, 56) : '';
                    $return .= '<br>';
                } else {
                    $return .= $objp->label ? $objp->label . '<br>' : '';
                }
                $return .= '<textarea name="eldesc" cols="70" rows="1">' . $objp->description . '</textarea></td>';
                $return .= '<td align="right">';
                $return .= $form->load_tva("eltva_tx", $objp->tva_tx, $mysoc, $object->thirdparty);
                $return .= '</td>';
                $return .= '<td align="right"><input size="5" type="text" name="elprice" value="' . price($objp->subprice) . '"></td>';
                $return .= '<td align="center"><input size="2" type="text" name="elqty" value="' . $objp->qty . '"></td>';
                $return .= '<td align="right" nowrap="nowrap"><input size="1" type="text" name="elremise_percent" value="' . $objp->remise_percent . '">%</td>';
                $return .= '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" name="save" value="' . $langs->trans("Modify") . '">';
                $return .= '<br><input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
                $return .= '</td>';
                // Ligne dates prevues
                $return .= "<tr $bc[$var]>";
                $return .= '<td colspan="5">';
                $return .= $langs->trans("DateStartPlanned") . ' ';
                $form->select_date($db->jdate($objp->date_debut), "date_start_update", $usehm, $usehm, ($db->jdate($objp->date_debut) > 0 ? 0 : 1), "update");
                $return .= '<br>' . $langs->trans("DateEndPlanned") . ' ';
                $form->select_date($db->jdate($objp->date_fin), "date_end_update", $usehm, $usehm, ($db->jdate($objp->date_fin) > 0 ? 0 : 1), "update");
                $return .= '</td>';
                $return .= '</tr>';

                $return .= "</form>\n";
            }

            $db->free($result);
        } else {
            dol_print_error($db, "sql");
        }

        if ($object->statut > 0) {
            $return .= '<tr ' . $bc[false] . '>';
            $return .= '<td colspan="6"><hr></td>';
            $return .= "</tr>\n";
        }

        $return .= "</table>";


        /*
         * Confirmation to delete service line of contract
         */
        if ($action == 'deleteline' && !$_REQUEST["cancel"] && $user->rights->contrat->creer && $idLigne == GETPOST('rowid')) {
            $ret = $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&lineid=" . GETPOST('rowid'), $langs->trans("DeleteContractLine"), $langs->trans("ConfirmDeleteContractLine"), "confirm_deleteline", '', 0, 1);
            if ($ret == 'html')
                $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation to move service toward another contract
         */
        if ($action == 'move' && !$_REQUEST["cancel"] && $user->rights->contrat->creer && $idLigne == GETPOST('rowid')) {
            $arraycontractid = array();
            foreach ($arrayothercontracts as $contractcursor) {
                $arraycontractid[$contractcursor->id] = $contractcursor->ref;
            }
            //var_dump($arraycontractid);
            // Cree un tableau formulaire
            $formquestion = array(
                'text' => $langs->trans("ConfirmMoveToAnotherContractQuestion"),
                array('type' => 'select', 'name' => 'newcid', 'values' => $arraycontractid));

            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&lineid=" . GETPOST('rowid'), $langs->trans("MoveToAnotherContract"), $langs->trans("ConfirmMoveToAnotherContract"), "confirm_move", $formquestion);
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation de la validation activation
         */
        if ($action == 'active' && !$_REQUEST["cancel"] && $user->rights->contrat->activer && $idLigne == GETPOST('ligne')) {
            $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            $comment = GETPOST('comment');
            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&ligne=" . GETPOST('ligne') . "&date=" . $dateactstart . "&dateend=" . $dateactend . "&comment=" . urlencode($comment), $langs->trans("ActivateService"), $langs->trans("ConfirmActivateService", dol_print_date($dateactstart, "%A %d %B %Y")), "confirm_active", '', 0, 1);
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation de la validation fermeture
         */
        if ($action == 'closeline' && !$_REQUEST["cancel"] && $user->rights->contrat->activer && $idLigne == GETPOST('ligne')) {
            $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            $comment = GETPOST('comment');
            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&ligne=" . GETPOST('ligne') . "&date=" . $dateactstart . "&dateend=" . $dateactend . "&comment=" . urlencode($comment), $langs->trans("CloseService"), $langs->trans("ConfirmCloseService", dol_print_date($dateactend, "%A %d %B %Y")), "confirm_closeline", '', 0, 1);
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }


        // Area with status and activation info of line
        if ($object->statut > 0) {
            $return .= '<table class="notopnoleft" width="100%">';

            $return .= '<tr ' . $bc[false] . '>';
            $return .= '<td>' . $langs->trans("ServiceStatus") . ': ' . $objL->getLibStatut(4) . '</td>';
            $return .= '<td width="30" align="right">';
            if ($user->societe_id == 0) {
                if ($object->statut > 0 && $action != 'activateline' && $action != 'unactivateline') {
                    $tmpaction = 'activateline';
                    if ($objp->statut == 4)
                        $tmpaction = 'unactivateline';
                    $return .= '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . $idLigne . '&amp;action=' . $tmpaction . '">';
                    $return .= img_edit();
                    $return .= '</a>';
                }
            }
            $return .= '</td>';
            $return .= "</tr>\n";

            $return .= '<tr ' . $bc[false] . '>';

            $return .= '<td>';
            // Si pas encore active
            if (!$objp->date_debut_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                if ($objp->date_debut_reelle)
                    $return .= dol_print_date($objp->date_debut_reelle);
                else
                    $return .= $langs->trans("ContractStatusNotRunning");
            }
            // Si active et en cours
            if ($objp->date_debut_reelle && !$objp->date_fin_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                $return .= dol_print_date($objp->date_debut_reelle);
            }
            // Si desactive
            if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                $return .= dol_print_date($objp->date_debut_reelle);
                $return .= ' &nbsp;-&nbsp; ';
                $return .= $langs->trans("DateEndReal") . ': ';
                $return .= dol_print_date($objp->date_fin_reelle);
            }
            if (!empty($objp->comment))
                $return .= "<br>" . $objp->comment;
            $return .= '</td>';

            $return .= '<td align="center">&nbsp;</td>';

            $return .= '</tr>';
            $return .= '</table>';
        }

        if ($user->rights->contrat->activer && $action == 'activateline' && $idLigne == GETPOST('ligne')) {
            /**
             * Activer la ligne de contrat
             */
            $return .= '<form name="active" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . GETPOST('ligne') . '&amp;action=active" method="post">';
            $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

            $return .= '<table class="noborder" width="100%">';
            //$return .= '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Status").'</td></tr>';
            // Definie date debut et fin par defaut
            $dateactstart = $objp->date_debut;
            if (GETPOST('remonth'))
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            elseif (!$dateactstart)
                $dateactstart = time();

            $dateactend = $objp->date_fin;
            if (GETPOST('endmonth'))
                $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            elseif (!$dateactend) {
                if ($objp->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                }
            }

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("DateServiceActivate") . '</td><td>';
            $return .= $form->select_date($dateactstart, '', $usehm, $usehm, '', "active");
            $return .= '</td>';

            $return .= '<td>' . $langs->trans("DateEndPlanned") . '</td><td>';
            $return .= $form->select_date($dateactend, "end", $usehm, $usehm, '', "active");
            $return .= '</td>';

            $return .= '<td align="center" rowspan="2" valign="middle">';
            $return .= '<input type="submit" class="button" name="activate" value="' . $langs->trans("Activate") . '"><br>';
            $return .= '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
            $return .= '</td>';

            $return .= '</tr>';

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("Comment") . '</td><td colspan="3"><input size="80" type="text" name="comment" value="' . GETPOST('comment') . '"></td></tr>';

            $return .= '</table>';

            $return .= '</form>';
        }

        if ($user->rights->contrat->activer && $action == 'unactivateline' && $idLigne == GETPOST('ligne')) {
            /**
             * Desactiver la ligne de contrat
             */
            $return .= '<form name="closeline" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . $idLigne . '&amp;action=closeline" method="post">';
            $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

            $return .= '<table class="noborder" width="100%">';

            // Definie date debut et fin par defaut
            $dateactstart = $objp->date_debut_reelle;
            if (GETPOST('remonth'))
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            elseif (!$dateactstart)
                $dateactstart = time();

            $dateactend = $objp->date_fin_reelle;
            if (GETPOST('endmonth'))
                $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            elseif (!$dateactend) {
                if ($objp->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                }
            }
            $now = dol_now();
            if ($dateactend > $now)
                $dateactend = $now;

            $return .= '<tr ' . $bc[$var] . '><td colspan="2">';
            if ($objp->statut >= 4) {
                if ($objp->statut == 4) {
                    $return .= $langs->trans("DateEndReal") . ' ';
                    $form->select_date($dateactend, "end", $usehm, $usehm, ($objp->date_fin_reelle > 0 ? 0 : 1), "closeline");
                }
            }
            $return .= '</td>';

            $return .= '<td align="right" rowspan="2"><input type="submit" class="button" name="close" value="' . $langs->trans("Close") . '"><br>';
            $return .= '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
            $return .= '</td></tr>';

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("Comment") . '</td><td><input size="70" type="text" class="flat" name="comment" value="' . GETPOST('comment') . '"></td></tr>';
            $return .= '</table>';

            $return .= '</form>';
        }

//        $return .= '</td>'; // End td if line is 1
//        $return .= '</tr>';
//        $return .= '<tr><td style="border-right: 1px solid #' . $colorb . '">&nbsp;';
        return $return;
    }

    public function setDate($dateDeb, $dateProlongation) {
        $this->fetch_lines();
        $dureeMax = 0;
        $tmpProd = new Product($this->db);
        foreach ($this->lines as $ligne) {
            $duree = 12;
            if (isset($ligne->fk_product) && $ligne->fk_product > 0) {
                $tmpProd->fetch($ligne->fk_product);
                $tmpProd->fetch_optionals($ligne->fk_product);
                if ($isSAV && $tmpProd->array_options['options_2dureeSav'] > 0)
                    $duree = $tmpProd->array_options['options_2dureeSav'];
                elseif ($tmpProd->array_options['options_2dureeVal'] > 0)
                    $duree = $tmpProd->array_options['options_2dureeVal'];
            }
            if ($duree > $dureeMax)
                $dureeMax = $duree;
            $query = "UPDATE " . MAIN_DB_PREFIX . "contratdet SET date_ouverture_prevue = '" . $dateDeb . "'";
            if ($ligne->date_ouverture)
                $query .= ", date_ouverture = '" . $dateDeb . "', date_fin_validite = date_add(date_add('" . $dateProlongation . "',INTERVAL " . $duree . " MONTH), INTERVAL -1 DAY)";
//die($query . " WHERE rowid = " . $ligne->id);
            $sql = $this->db->query($query . " WHERE rowid = " . $ligne->id);
            $query = "UPDATE " . MAIN_DB_PREFIX . "contrat SET date_contrat = '" . $dateDeb . "'";
            if ($this->mise_en_service)
                $query .= ", mise_en_service = '" . $dateDeb . "', fin_validite = date_add(date_add('" . $dateProlongation . "',INTERVAL " . $dureeMax . " MONTH), INTERVAL -1 DAY)";
            $sql = $this->db->query($query . " WHERE rowid = " . $this->id);
        }
    }

    public function setDateContrat($date) {
        $this->setDate($date, $date);
    }

    public function addLigneCommande($commId, $comLigneId) {
        global $user, $langs, $conf;
        $contratId = $this->id;
        $db = $this->db;
        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
        require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");

        $com = new Synopsis_Commande($db);
        $com->fetch($commId);
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "commandedet WHERE rowid = " . $comLigneId;

        $sql = $db->query($requete);
        if (!$sql)
            die("Erreur SQL : " . $requete);
        $res = $db->fetch_object($sql);
        $total_ht = preg_replace('/,/', '.', $res->subprice * $res->qty);
        $total_tva = preg_replace('/,/', '.', 0.20 * $total_ht);
        $total_ttc = preg_replace('/,/', '.', 1.20 * $total_ht);
        $sql = false;
//for ($i = 0; $i < $qty; $i++) {
        /* $line0 = 0;
          $requete = "SELECT max(line_order) + 1 as mx
          FROM ".MAIN_DB_PREFIX."contratdet
          WHERE fk_contrat = ".$contratId;
          $sql1 = $db->query($requete);
          $res1 = $db->fetch_object($sql1);
          $lineO = ($res1->mx>0?$res1->mx:1);

          $tmpProd = new Product($db);
          $tmpProd->fetch($res->fk_product);

          $avenant = 'NULL';
          //SI contrat statut > 0 => avenant pas NULL
          $requete = "SELECT *
          FROM ".MAIN_DB_PREFIX."contrat
          WHERE rowid = ".$contratId;
          $sql2 = $db->query($requete);
          $res2 = $db->fetch_object($sql2);
          if ($res2->statut > 0)
          {
          $avenant=0;
          //avenant en cours ou pas ?
          $requete = "SELECT max(avenant) + 1 as mx
          FROM ".MAIN_DB_PREFIX."contratdet
          WHERE fk_contrat=".$contratId."
          AND statut > 0";
          $sql3 = $db->query($requete);
          $res3 = $db->fetch_object($sql3);
          $avenant= $res3->mx;
          } */


        $tmpProd = new Product($db);
        $tmpProd->fetch($res->fk_product);
        $tmpProd->fetch_optionals($res->fk_product);

        if ($tmpProd->array_options['options_2annexe'] > 0)
            $this->addAnnexe($tmpProd->array_options['options_2annexe']);



        $isMnt = false;
        $isSAV = false;
        $isTkt = false;
        $qte1 = $res->qty;
        $qte2 = ($tmpProd->array_options['options_2qte'] > 0) ? $tmpProd->array_options['options_2qte'] : 0;
        if ($tmpProd->array_options['options_2hotline'] > 0 || $tmpProd->array_options['options_2teleMaintenance'] > 0 || $tmpProd->array_options['options_2maintenance'] > 0) {
            $isMnt = true;
//            $qte = $tmpProd->array_options['options_2visiteSurSite'];
        } else if ($tmpProd->array_options['options_2isSav'] > 0) {
            $isSAV = true;
        } else if ($tmpProd->array_options['options_2timePerDuree'] > 0) {
            $isTkt = true;
        }

        $duree = 12;
        if ($isSAV && $tmpProd->array_options['options_2dureeSav'] > 0)
            $duree = $tmpProd->array_options['options_2dureeSav'];
        elseif ($tmpProd->array_options['options_2dureeVal'] > 0)
            $duree = $tmpProd->array_options['options_2dureeVal'];

        $date = date("Y-m-d", $this->date_contrat);
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "contratdet
                            (fk_contrat,fk_product,statut,description,
                             tva_tx,qty,subprice,price_ht,
                             total_ht, total_tva, total_ttc,fk_user_author,
                             "/* line_order,fk_commande_ligne,avenant, */ . "date_ouverture_prevue,date_ouverture, date_fin_validite)
                     VALUES (" . $contratId . ",'" . $res->fk_product . "',0,'" . addslashes($res->description) . "',
                             20," . $qte1 . "," . $res->subprice . "," . $res->subprice . ",
                             " . $total_ht . "," . $total_tva . "," . $total_ttc . "," . $user->id . "
                             " . /* $lineO.",".$comLigneId.",".$avenant. */",'" . $date . "','" . $date . "', date_add(date_add('" . $date . "',INTERVAL " . $duree . " MONTH), INTERVAL - 1 DAY))";
        $sql = $db->query($requete);
//    die($requete);
        $cdid = $db->last_insert_id(MAIN_DB_PREFIX . "contratdet");

        addElementElement("commandedet", "contratdet", $comLigneId, $cdid);

//Mode de reglement et condition de reglement
        if ($this->condReg_refid != $com->cond_reglement_id || $this->modeReg_refid != $com->mode_reglement_id) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($db);
            $result = $interface->run_triggers('NOTIFY_ORDER_CHANGE_CONTRAT_MODE_REG', $com, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $errors = $interface->errors;
            }
            // Fin appel triggers
        }




//Lier a contratdetprop
//".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO


        $requete2 = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO
                            (contratdet_refid,fk_contrat_prod,qte,tms,DateDeb,reconductionAuto,
                            isSAV, SLA, durValid,
                            hotline, telemaintenance, telemaintenanceCur, maintenance,
                            type, qteTempsPerDuree,  qteTktPerDuree, nbVisite, nbVisiteCur)
                     VALUES (" . $cdid . "," . $res->fk_product . "," . $qte2 . ",now(),now(),'" . $tmpProd->array_options['options_2reconductionAuto'] . "',
                            " . ($isSAV > 0 ? 1 : 0) . ",'" . addslashes($tmpProd->array_options['options_2SLA']) . "'," . $duree . ",
                            " . ($tmpProd->array_options['options_2hotline'] <> 0 ? $tmpProd->array_options['options_2hotline'] : 0) . "," . ($tmpProd->array_options['options_2teleMaintenance'] <> 0 ? $tmpProd->array_options['options_2teleMaintenance'] : 0) . "," . ($tmpProd->array_options['options_2teleMaintenanceCur'] <> 0 ? $tmpProd->array_options['options_2teleMaintenanceCur'] : 0) . "," . ($tmpProd->array_options['options_2maintenance'] > 0 ? 1 : 0) . ",
                            " . ($isMnt ? 3 : ($isSAV ? 4 : ($isTkt ? 2 : 5))) . ", '" . $tmpProd->array_options['options_2timePerDuree'] . "','" . $tmpProd->array_options['options_2qtePerDuree'] . "','" . $tmpProd->array_options['options_2visiteSurSite'] . "','" . $tmpProd->array_options['options_2visiteCur'] . "')";
        $sql1 = $db->query($requete2);

        if ($sql && $sql1)
            return true;
        echo "\n\n" . $requete;
        echo "\n\n" . $requete2;
        return false;
    }

    public function addAnnexe($annexeId) {
        $maxRang = 0;
        $tabEl = array();
        $req1 = "DELETE FROM `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexe` WHERE `contrat_refid` NOT IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "contrat)";
        $result = $this->db->query($req1);
        $req1 = "SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexe` WHERE `contrat_refid` = " . $this->id . " GROUP BY annexe_refid ORDER BY rang";
        $result = $this->db->query($req1);
        while ($res = $this->db->fetch_object($result)) {
            $idAnn = $res->annexe_refid;
            if ($idAnn != 36 && $idAnn != 25 && $idAnn != 2)
                $tabEl[] = $idAnn;
        }
//        echo "<pre>"; print_r($tabEl);echo "ici".$req1;
        if (!in_array($annexeId, $tabEl))
                $tabEl[] = $annexeId;
        if(stripos($this->ref, "CMED") === false)
                $tabEl[] = 36;
        $tabEl[] = 25;
        $tabEl[] = 2;
        $tabInsert = array();
        foreach($tabEl as $id => $elem){
            $tabInsert[] = "(" . $elem . ", " . $this->id . ", " . ($id+1) . ", '')";
        }
        $req2 = "DELETE FROM `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexe` WHERE contrat_refid = " . $this->id;
        if (!$this->db->query($req2))
            die($req2);
        $req2 = "INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexe` (`annexe_refid`, `contrat_refid`, `rang`, `annexe`) VALUES ".implode(",", $tabInsert);
        if (!$this->db->query($req2))
            die($req2);
//        $req2 = "INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexe` (`annexe_refid`, `contrat_refid`, `rang`, `annexe`) VALUES (36, " . $this->id . ", " . ($maxRang + 2) . ", ''), (25, " . $this->id . ", " . ($maxRang + 3) . ", ''), (2, " . $this->id . ", " . ($maxRang + 4) . ", '')";
//        if (!$this->db->query($req2))
//            die($req2);
    }

//    
//    
//    public function initDialog($mysoc,$objp)
//    {
//        global $user;
//        $html = "";
//        if ($user->rights->contrat->creer && $this->statut ==0)
//        {
//            $html .= '<div id="addDialog" class="ui-state-default ui-corner-all" style="">';
//            $html .= $this->displayDialog('add',$mysoc,$objp);
//            $html .= '</div>';
//        }
//        if ($user->rights->contrat->supprimer)
//        {
//            $html .=  '<div id="delDialog"><span id="delDialog-content"></span></div>';
//        }
//        if ($user->rights->contrat->creer && ($this->statut ==0   || ($this->statut == 1 && isset(isset($conf->global->CONTRAT_EDITWHENVALIDATED) && $conf->global->CONTRAT_EDITWHENVALIDATED) && isset($conf->global->CONTRAT_EDITWHENVALIDATED) && $conf->global->CONTRAT_EDITWHENVALIDATED) ) )
//        {
//            $html .=  '<div id="modDialog"><span id="modDialog-content">';
//            $html .=  $this->displayDialog('mod',$mysoc,$objp);
//            $html .=  '</span></div>';
//        }
//
//        if ($user->rights->contrat->activer && $this->statut !=0)
//        {
//            $html .=  '<div id="activateDialog" class="ui-state-default ui-corner-all" style="">';
//            $html .=  "<table width=450><tr><td>Date de d&eacute;but effective du service<td>";
//            $html .=  "<input type='text' name='dateDebEff' id='dateDebEff'>";
//            $html .=  "<tr><td>Date de fin effective du service<td>";
//            $html .=  "<input type='text' name='dateFinEff' id='dateFinEff'>";
//            $html .=  "</table>";
//            $html .=  '</div>';
//        }
//        if ($user->rights->contrat->desactiver && $this->statut !=0)
//        {
//            $html .=  '<div id="unactivateDialog" class="ui-state-default ui-corner-all" style="">';
//            $html .=  "<p>&Ecirc;tes vous sur de vouloir d&eacute;sactiver cette ligne&nbsp;?</p>";
//            $html .=  '</div>';
//        }
//        //var_dump($user->rights->contrat);
//        if ($user->rights->contrat->activer && $this->statut != 0)
//        {
//            $html .=  '<div id="closeLineDialog" class="ui-state-default ui-corner-all" style="">';
//            $html .=  "<p>&Ecirc;tes vous sur de vouloir cl&ocirc;turer cette ligne&nbsp;?</p>";
//            $html .=  '</div>';
//        }
//
//
//        return($html);
//    }
//    
//    public function displayDialog($type='add',$mysoc,$objp)
//    {
//        global $conf, $form, $db;
//        $html .=  '<div id="'.$type.'Line" class="ui-state-default ui-corner-all" style="">';
//        $html .= "<form id='".$type."Form' method='POST' onsubmit='return(false);'>";
//
////        $html .=  '<span class="ui-state-default ui-widget-header ui-corner-all" style="margin-top: -4px; padding: 5px 35px 5px 35px;">Ajout d\'une ligne</span>';
//        $html .=  '<table style="width: 900px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
//        $html .=  '<tr style="border-bottom: 1px Solid #0073EA !important">';
//        $html .=  '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Recherche de produits & financement</th></tr>';
//        $html .=  '<tr style="border-top: 1px Solid #0073EA !important">';
//        $html .=  '<td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">Produits</td>
//                   <td style=" padding-top: 5px; padding-bottom: 3px;">';
//            // multiprix
//            $filter = "";
//            switch ($this->type)
//            {
//                case 1:
//                    //SAV
//                    $filter="1";
//                break;
//            }
//            if($conf->global->PRODUIT_MULTIPRICES == 1)
//                $html .= $this->returnSelect_produits('','p_idprod_'.$type,$filter,$conf->produit->limit_size,$this->societe->price_level,1,true,false,false);
//            else
//                $html .= $this->returnSelect_produits('','p_idprod_'.$type,$filter,$conf->produit->limit_size,false,1,true,true,false);
//            if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) $html .=  '<br>';
//
//
//
//        $html .=  '</td><td  style=" padding-top: 5px; padding-bottom: 3px;border-right: 1px Solid #0073EA;">&nbsp;</td>';
//        $html .=  '<tr>';
//        $html .=  ' <td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">Financement ? ';
//        $html .=  ' </td>
//                    <td style="width: 30px;">
//                        <input type="checkbox" id="addFinancement'.$type.'"  name="addFinancement'.$type.'" /></td>
//                    <td style="border-right: 1px Solid #0073EA; padding-top: 5px; padding-bottom: 3px;">&nbsp;</td>';
//        $html .=  '</tr>';
//        $html .=  "</table>";
//        $html .=  '<table style="width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
//        $html .=  '<tr>';
//        $html .=  '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Description ligne / produit</th></tr>';
//        $html .=  '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
//        $html .=  '<td style="border-right: 1px Solid #0073EA;">';
//        $html .=  'Description libre<br/>';
//        $html .=  '<div class="nocellnopadd" id="ajdynfieldp_idprod_'.$type.'"></div>';
//        $html .=  "<textarea style='width: 600px; height: 3em' name='".$type."Desc' id='".$type."Desc'></textarea>";
//        $html .=  '</td>';
//        $html .=  '</tr>';
//        $html .=  "</table>";
//
//        $html .=  '<table style=" width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
//        $html .=  '<tr>';
//        $html .=  '<th style="border-bottom: 1px Solid #0073EA !important; " colspan="8"  class="ui-widget-header">Prix & Quantit&eacute;</th></tr><tr style="padding: 10px; ">';
//        $html .=  '<td align=right>Prix (&euro;)</td><td align=left>';
//        $html .=  "<input id='".$type."Price' name='".$type."Price' style='width: 100px; text-align: center;'/>";
//        $html .=  '</td>';
//        $html .=  '<td align=right>TVA<td align=left width=180>';
//        $html .= $form->load_tva($type."Linetva_tx","20",$mysoc,$this->societe,"",0,false);
//
//        $html .=  '</td>';
//        $html .=  '<td align=right>Qt&eacute;</td><td align=left>';
//        $html .=  "<input id='".$type."Qty' value=1 name='".$type."Qty' style='width: 20px;  text-align: center;'/>";
//        $html .=  '</td>';
//        $html .=  '<td align=right>Remise (%)</td><td align=left>';
//        $html .=  "<input id='".$type."Remise' value=0 name='".$type."Remise' style='width: 20px; text-align: center;'/>";
//        $html .=  '</td>';
//        $html .=  '</tr>';
//
//        $html .=  '</table>';
//
//        $html .=  '<table style="width: 900px;  border-collapse: collapse; margin-top: 5px;"  cellpadding=10>';
//        $html .=  '<tr style="border-bottom: 1px Solid #0073EA; ">';
//        $html .=  '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Chronologie</th>';
//        $html .=  '</tr>';
//        $html .=  "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; '>";
//        $html .=  '<td>Date de d&eacute;but pr&eacute;vue</td>';
//        $html .=  '<td>
//                        <input value="'. date('d').'/'.date('m').'/'.date('Y') .'" style="text-align: center;" type="text" name="dateDeb'.$type.'" id="dateDeb'.$type.'"/>'.img_picto('calendrier','calendar.png','style="float: left;margin-right: 3px; margin-top: 1px;"').'</td>';
//        $html .=  '<td>Date de fin pr&eacute;vue</td>';
////        calendar.png
//        $html .=  '<td style="border-right: 1px Solid #0073EA;">
//                        <input style="text-align: center;" type="text" name="dateFin'.$type.'" id="dateFin'.$type.'"/>'.img_picto('calendrier','calendar.png','style="float: left; margin-right: 3px; margin-top: 1px;"').'</td>';
//        $html .=  '</tr>';
//        $html .=  "</table>";
//
//        $html .= '<div id="financementLigne'.$type.'" style="display: none; margin-top: 5px;">';
//        $html .=  '<table style="width: 900px;  border-collapse: collapse; "  cellpadding=10>';
//        $html .=  '<tr style="border-bottom: 1px Solid #0073EA; ">';
//        $html .=  '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Financement</th>';
//        $html .=  '</tr>';
//        $html .=  "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; border-top: 1px Solid #0073EA;'>";
//        if ($conf->global->MAIN_MODULE_BABELGA == 1){
//        $html .=  '<td align=right>Nombre de p&eacute;riode</td>';
//        //TODO ds conf
//        $html .=  '<td align=left><input style="text-align: center;width: 35px;" type="text" name="nbPeriode'.$type.'" id="nbPeriode'.$type.'"/></td>';
//        $html .=  '<td align=right>Type de p&eacute;riode</td>';
//        $html .=  '<td align=left><select id="typePeriod'.$type.'">';
//        $requete = "SELECT * FROM Babel_financement_period ORDER BY id";
//        $sqlPeriod = $db->query($requete);
//        while ($res = $db->fetch_object($sqlPeriod))
//        {
//            $html .=  "<option value='".$res->id."'>".$res->Description."</option>";
//        }
//        $html .=  '</select>';
//        $html .=  '</td>';
//        }
//        //TODO dans conf taux par défaut configurable selon droit ++ droit de choisir le taux
//        $html .=  '<td align=right>Taux achat</td>';
//        $html .=  '<td align=left><input style="text-align: center; width: 35px;" name="'.$type.'TauxAchat" id="'.$type.'TauxAchat"/></td>';
//        //TODO dans conf taux par défaut configurable selon droit + droit de choisir le taux
//        $html .=  '<td align=right>Taux vente</td>';
//        $html .=  '<td align=left style="border-right: 1px Solid #0073EA;">
//                        <input style="text-align: center;width: 35px;" name="'.$type.'TauxVente" id="'.$type.'TauxVente"/></td>';
//        $html .=  '</tr>';
//        $html .=  "</table>";
//        $html .=  '</div>';
//
//        $html .=  '</form>';
//        $html .=  '</div>';
//        return ($html);
//
//    }













    public function initDialog($mysoc, $objp = null) {
        global $user, $conf;
        $html = "";
        $js = "";
        if ($user->rights->contrat->creer || ($this->statut == 0 || ($this->statut == 1 && isset($conf->global->CONTRAT_EDITWHENVALIDATED) && $conf->global->CONTRAT_EDITWHENVALIDATED) )) {
            $html .= '<div id="addDialog" class="hide ui-state-default ui-corner-all" style="">';
            $tab = $this->displayDialog('add', $mysoc, $objp);
            $html .= $tab[0];
            $js .= $tab[1];
            $html .= '</div>';
        }

        if ($user->rights->contrat->supprimer && ($this->statut == 0 || ($this->statut == 1 && isset($conf->global->CONTRAT_EDITWHENVALIDATED) && $conf->global->CONTRAT_EDITWHENVALIDATED))) {
            $html .= '<div id="delDialog" class="hide"><span id="delDialog-content"></span></div>';
        }
        if ($user->rights->contrat->creer || ($this->statut == 0 || ($this->statut == 1 && isset($conf->global->CONTRAT_EDITWHENVALIDATED) && $conf->global->CONTRAT_EDITWHENVALIDATED) )) {
            $html .= '<div id="modDialog" class="hide"><span id="modDialog-content">';
            $tab = $this->displayDialog('mod', $mysoc, $objp);
            $html .= $tab[0];
            $js .= $tab[1];
            $html .= '</span></div>';
        }

        if ($user->rights->contrat->activer && $this->statut != 0) {
            $html .= '<div id="activateDialog" class="ui-state-default ui-corner-all" style="">';
            $html .= "<table width=450><tr><td>Date de d&eacute;but effective du service<td>";
            $html .= "<input type='text' name='dateDebEff' id='dateDebEff'>";
            $html .= "<tr><td>Date de fin effective du service<td>";
            $html .= "<input type='text' name='dateFinEff' id='dateFinEff'>";
            $html .= "</table>";
            $html .= '</div>';
        }
        if ($user->rights->contrat->desactiver && $this->statut != 0) {
            $html .= '<div id="unactivateDialog" class="ui-state-default ui-corner-all" style="">';
            $html .= "<p>&Ecirc;tes vous sur de vouloir d&eacute;sactiver cette ligne&nbsp;?</p>";
            $html .= '</div>';
        }
        //var_dump($user->rights->contrat);
        if ($user->rights->contrat->activer && $this->statut != 0) {
            $html .= '<div id="closeLineDialog" class="ui-state-default ui-corner-all" style="">';
            $html .= "<p>&Ecirc;tes vous sur de vouloir cl&ocirc;turer cette ligne&nbsp;?</p>";
            $html .= '</div>';
        }


        return array($html, $js);
    }

    public function displayDialog($type = 'add', $mysoc = null, $objp = null) {
        global $conf, $form;

        $form = new Form($this->db);


        $html = '<div id="' . $type . 'Line" class="ui-state-default ui-corner-all" style="">';
        $html .= "<form id='" . $type . "Form' method='POST' onsubmit='return(false);'>";
        $html .= "<div id='" . $type . "dialogTab'>";
        $html .= "<ul>";
        $html .= "    <li><a href='#" . $type . "general'><span>G&eacute;n&eacute;ral</span></a></li>";
        $html .= "    <li><a href='#" . $type . "price'><span>Prix</span></a></li>";
        $html .= "    <li><a href='#" . $type . "produit'><span>Produit</span></a></li>";
        $html .= "    <li><a href='#" . $type . "detail'><span>D&eacute;tail</span></a></li>";
        $html .= "    <li><a href='#" . $type . "clause'><span>Condition</span></a></li>";
        $html .= "</ul>";

        $html .= "<div id='" . $type . "clause'>";
        $html .= '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .= '<tr style="border-bottom: 1px Solid #0073EA !important">';
        $html .= '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .= '<td style="border-right: 1px Solid #0073EA;" colspan=3>';
        $html .= 'Clauses juridiques<br/>';
        $html .= "<textarea style='width: 600px; height: 6em' name='" . $type . "Clause' id='" . $type . "Clause'></textarea>";
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .= '<td style="border-right: 1px Solid #0073EA;" colspan=3>';
        $html .= 'Clauses produit (rappel)<br/>';
        $html .= "<div style='width: 600px; height: 6em border:1px Solid;padding: 5px;'  class='ui-widget-content'   name='" . $type . "ClauseProd' id='" . $type . "ClauseProd'></div>";
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .= '<td style="border-right: 1px Solid #0073EA;" colspan=3>';
        $html .= 'Clauses contrat (rappel)<br/>';
        $html .= "<div style='width: 600px; height: 6em border:1px Solid;padding: 5px;' class='ui-widget-content' name='" . $type . "ClauseProdCont' id='" . $type . "ClauseProdCont'></div>";
        $html .= '</td>';
        $html .= '</tr>';

        $html .= "</table>";
        $html .= "</div>";


        $html .= "<div id='" . $type . "general'>" . "\n";
//        $html .=  '<span class="ui-state-default ui-widget-header ui-corner-all" style="margin-top: -4px; padding: 5px 35px 5px 35px;">Ajout d\'une ligne</span>';
        $html .= '<table style="width: 870px;" cellpadding=10 >' . "\n";
        $html .= '<tr>' . "\n";
        $html .= '<th class="ui-widget-header ui-state-default" colspan=1>Description</th>' . "\n";
        $html .= "<td class='ui-widget-content' colspan=2><textarea style='width: 600px; height: 3em' name='" . $type . "Desc' id='" . $type . "Desc'></textarea>" . "\n";
        $html .= '</td>' . "\n";
        $html .= '</tr>' . "\n";
        $html .= '<tr>' . "\n";
        $html .= '<th class="ui-widget-header ui-state-default" width=150 colspan=1>Date de d&eacute;but' . "\n";

        $html .= '<td class="ui-widget-content" colspan=2>';

        ob_start();
        $html .= $form->select_date('', 'dateDeb' . $type);
        $html .= ob_get_clean();
//        $html .= '<input type="text" class="datepicker" style="width: 100px" name="dateDeb' . $type . '" id="dateDeb' . $type . '">';
        $html .= "\n" . '</td>' . "\n";
        $html .= '</tr>' . "\n";
        $html .= '<tr>' . "\n";
        $html .= '<th class="ui-widget-header ui-state-default" colspan=1>SLA' . "\n";
        $html .= '<td class="ui-widget-content" colspan=2><input type="text" style="width: 100px" name="' . $type . 'SLA" id="' . $type . 'SLA">' . "\n";
        $html .= '</td>' . "\n";
        $html .= '</tr>' . "\n";
        $html .= '<tr>' . "\n";
        $html .= '<th class="ui-widget-header ui-state-default" colspan=1>Reconduction automatique' . "\n";
        $html .= '<td class="ui-widget-content" colspan=2><input type="checkbox" name="' . $type . 'recondAuto" id="' . $type . 'recondAuto">' . "\n";
        $html .= '</td>' . "\n";
        $html .= '</tr>' . "\n";
        $html .= '<tr>' . "\n";
        $html .= '<th class="ui-widget-header ui-state-default" colspan=1>Commande' . "\n";

        $html .= '<td class="ui-widget-content" width=200>' . "\n";
        $html .= "\n" . '<SELECT name="' . $type . 'Commande" id="' . $type . 'Commande"> ' . "\n";
        $html .= "<OPTION value='-1'>S&eacute;lectionner -></OPTION>" . "\n";

        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "commande WHERE fk_soc = " . $this->socid;
        $sql = $this->db->query($requete);

        while ($res = $this->db->fetch_object($sql)) {
            $html .= "<OPTION value='" . $res->rowid . "'>" . $res->ref . " " . $res->date_commande . "</OPTION>\n";
        }
        $html .= "</SELECT>\n";

        $js = "<script>\n";
        $js .= "       var fk_soc = " . $this->societe->id . ";";
        $js .= "   jQuery(document).ready(function(){\n";
        $js .= "       jQuery('#" . $type . "Commande').change(function(){\n";
        $js .= "       var type = '" . $type . "';\n";
        $js .= <<<EOF
          var seekId = jQuery(this).find(':selected').val();
          if (seekId > 0){

              jQuery.ajax({
                  url: DOL_URL_ROOT+"/Synopsis_Contrat/ajax/listCommandeDet-xml_response.php",
                  data: "id="+seekId,
                  datatype:"xml",
                  type: "POST",
                  cache: true,
                  success: function(msg){
                    var longHtml = '<span id="'+type+'commandeDet">';
                        longHtml += "<SELECT name='"+type+"LigneCom'  name='"+type+"LigneCom'><OPTION value='-1'>S&eacute;lectionner-></OPTION>";
                    jQuery(msg).find('commandeDet').each(function(){
                        var idLigne = jQuery(this).attr('id');
                        var valLigne = jQuery(this).text();
                        longHtml += "<OPTION value='"+idLigne+"'>"+valLigne+"</OPTION>";
                    });
                    longHtml += "</SELECT></span>";
                    jQuery('#'+type+'commandeDet').replaceWith(longHtml);
                    jQuery('#'+type+'commandeDet').find('SELECT').selectmenu({style: 'dropdown', maxHeight: 300 });
                  }
              });
          } else {
              jQuery('#'+type+'commandeDet').replaceWith('<span id="'+type+'commandeDet">&nbsp;</span>');
          }
        });
EOF;

        $js .= 'jQuery("#MnTtype' . $type . '").click(function(){ ' . $type . 'showGMAO("MnT"); });' . "\n";
        $js .= 'jQuery("#TkTtype' . $type . '").click(function(){ ' . $type . 'showGMAO("TkT"); });' . "\n";
        $js .= 'jQuery("#SaVtype' . $type . '").click(function(){ ' . $type . 'showGMAO("SaV"); });' . "\n";
        $js .= '});' . "\n";
        $js .= ' var typeContratRem=false;' . "\n";
        $js .= 'function ' . $type . 'showGMAO(typeContrat){' . "\n";
        $js .= "    typeContratRem=typeContrat" . "\n";
        $js .= "    if (jQuery('#ticket" . $type . "').css('display')=='block') { jQuery('#ticket" . $type . "').slideUp('fast',function(){ " . $type . "showGMAO2(); })}" . "\n";
        $js .= "    else if (jQuery('#maintenance" . $type . "').css('display')=='block') { jQuery('#maintenance" . $type . "').slideUp('fast',function(){ " . $type . "showGMAO2(); })}" . "\n";
        $js .= "    else if (jQuery('#savgmao" . $type . "').css('display')=='block') { jQuery('#savgmao" . $type . "').slideUp('fast',function(){ " . $type . "showGMAO2(); })}" . "\n";
        $js .= "    else { " . $type . "showGMAO2(); }" . "\n";
        $js .= "}" . "\n";

        $js .= 'function ' . $type . 'showGMAO2(){' . "\n";
        $js .= "    if (typeContratRem == 'MnT'){" . "\n";
        $js .= "      jQuery('#maintenance" . $type . "').slideDown();" . "\n";
        $js .= "    }" . "\n";
        $js .= "    if (typeContratRem == 'TkT'){" . "\n";
        $js .= "      jQuery('#ticket" . $type . "').slideDown();" . "\n";
        $js .= "    }" . "\n";
        $js .= "    if (typeContratRem == 'SaV'){" . "\n";
        $js .= "      jQuery('#savgmao" . $type . "').slideDown();" . "\n";
        $js .= "    }" . "\n";
        $js .= "}" . "\n";

        $js .=<<<EOF
    function publish_selvalue_callBack(obj,value){
        if (jQuery(obj).attr('id') == "p_idprod_add")
        {
            jQuery.ajax({
                url:DOL_URL_ROOT+"/Babel_GMAO/ajax/getProdClause-xml_response.php",
                data:"prod="+value+"&fk_soc="+fk_soc,
                datatype:"xml",
                type: "POST",
                success: function(msg){
                    var clause = jQuery(msg).find('clause').text();
                    jQuery('#addClauseProd').replaceWith('<div id="addClauseProd" class="ui-widget-content" style="padding: 5px;">'+clause+'</div>');
                }
            });
        }
        if (jQuery(obj).attr('id') == "p_idprod_mod")
        {
            jQuery.ajax({
                url:DOL_URL_ROOT+"/Babel_GMAO/ajax/getProdClause-xml_response.php",
                data:"prod="+value+"&fk_soc="+fk_soc,
                datatype:"xml",
                type: "POST",
                success: function(msg){
                    var clause = jQuery(msg).find('clause').text();
                    jQuery('#modClauseProd').replaceWith('<div id="modClauseProd" class="ui-widget-content" style="padding: 5px;">'+clause+'</div>');
                }
            });
        }
        if (jQuery(obj).attr('id') == "p_idContratprod_add")
        {
            jQuery.ajax({
                url:DOL_URL_ROOT+"/Babel_GMAO/ajax/getProdContratProd-xml_response.php",
                data:"prod="+value+"&fk_soc="+fk_soc,
                datatype:"xml",
                type: "POST",
                success: function(msg){
                    var price = jQuery(msg).find('price').text();
                    var tva = jQuery(msg).find('tva').text();
                    var qte = jQuery(msg).find('qte').text();
                    var qteMNT = jQuery(msg).find('qteMNT').text();
                    var clause = jQuery(msg).find('clause').text();
                    var Hotline = jQuery(msg).find('Hotline').text();
                    var TeleMaintenance = jQuery(msg).find('TeleMaintenance').text();
                    var Maintenance = jQuery(msg).find('Maintenance').text();
                    var SLA = jQuery(msg).find('SLA').text();
                    var VisiteSurSite = jQuery(msg).find('VisiteSurSite').text();
                    var reconductionAuto = jQuery(msg).find('reconductionAuto').text();
                    var durValid = jQuery(msg).find('durValid').text();
                    var isSAV = jQuery(msg).find('isSAV').text();

                    var qteTktPerDuree = jQuery(msg).find('qteTktPerDuree').text();
                    var qteTempsPerDuree = jQuery(msg).find('qteTempsPerDuree').text();
                    var qteTempsPerDureeH = jQuery(msg).find('qteTempsPerDureeH').text();
                    var qteTempsPerDureeM = jQuery(msg).find('qteTempsPerDureeM').text();

                    jQuery('#qteTktPerDureeadd').val(qteTktPerDuree);
                    jQuery('#qteTempsPerDureeHadd').val(qteTempsPerDureeH);
                    jQuery('#qteTempsPerDureeMadd').val(qteTempsPerDureeM);

                    if (tva + "x" != "x")
                    {
                        var i =0;
                        var remi=0;
                        jQuery('#addtauxtva').find('option').each(function(){
                            if (tva.replace(/,/,'.') == jQuery(this).val())
                            {
                                remi=i;
                            }
                            i++;
                        });
                        jQuery('#addtauxtva').selectmenu('value',remi);
                    }

                    jQuery('#nbTicketadd').val(qte);
                    jQuery('#nbTicketMNTadd').val(qteMNT);
                    jQuery('#hotlineadd').val(Hotline);
                    jQuery('#telemaintenanceadd').val(TeleMaintenance);
//                    if (Hotline == 1){
//                        jQuery('#hotlineadd').attr('checked',true);
//                    } else {
//                        jQuery('#hotlineadd').attr('checked',false);
//                    }
//                    if (TeleMaintenance == 1){
//                        jQuery('#telemaintenanceadd').attr('checked',true);
//                    } else {
//                        jQuery('#telemaintenanceadd').attr('checked',false);
//                    }
                    if (reconductionAuto == 1)
                    {
                        jQuery('#addrecondAuto').attr('checked',true);
                    } else {
                        jQuery('#addrecondAuto').attr('checked',false);
                    }
                    jQuery('#nbVisiteadd').val(VisiteSurSite);
                    jQuery('#addSLA').val(SLA);
                    jQuery('#DurSAVadd').val(durValid);
                    var clause = jQuery(msg).find('clause').text();
                    jQuery('#addClauseProdCont').replaceWith('<div id="addClauseProdCont" class="ui-widget-content" style="padding: 5px;">'+clause+'</div>');

                    jQuery('input[name=typeadd]').attr('checked',false);
                    //reinit durValid Mnt et Tkt
                    jQuery('#DurValMntadd').val("");
                    jQuery('#DurValTktadd').val("");

                    if (Maintenance == 1)
                    {
                        jQuery('#MnTtypeadd').attr('checked',true);
                        jQuery('#ticketadd').hide();
                        jQuery('#savgmaoadd').hide();
                        addshowGMAO("MnT");
                        jQuery('#DurSAVadd').val("");
                        jQuery('#nbTicketadd').val("");
//                        jQuery('#nbTicketMNTadd').val("");
                        jQuery('#DurValMntadd').val(durValid);
                    } else if (isSAV == 1){
                        jQuery('#SaVtypeadd').attr('checked',true);
                        jQuery('#ticketadd').hide();
                        jQuery('#maintenanceadd').hide();
                        addshowGMAO("SaV");
                        jQuery('#nbTicketadd').val("");
                        jQuery('#nbTicketMNTadd').val("");
                        jQuery('#telemaintenanceadd').attr('checked',false);
                        jQuery('#hotlineadd').attr('checked',false);
                    } else if (qte+"x"!= "x"){
                        jQuery('#TkTtypeadd').attr('checked',true);
                        jQuery('#savgmaoadd').hide();
                        jQuery('#maintenanceadd').hide();
                        addshowGMAO("TkT");
                        jQuery('#DurSAVadd').val("");
                        jQuery('#telemaintenanceadd').attr('checked',false);
                        jQuery('#hotlineadd').attr('checked',false);
                        jQuery('#DurValTktadd').val(durValid);
                        jQuery('#nbTicketMNTadd').val("");
                    }

                    jQuery('#addPuHT').val(price);
                }
            });
        }
        if (jQuery(obj).attr('id') == "p_idContratprod_mod")
        {
            jQuery.ajax({
                url:DOL_URL_ROOT+"/Babel_GMAO/ajax/getProdContratProd-xml_response.php",
                data:"prod="+value+"&fk_soc="+fk_soc,
                datatype:"xml",
                type: "POST",
                success: function(msg){
                    var price = jQuery(msg).find('price').text();
                    var tva = jQuery(msg).find('tva').text();
                    var qte = jQuery(msg).find('qte').text();
                    var qteMNT = jQuery(msg).find('qteMNT').text();
                    var clause = jQuery(msg).find('clause').text();
                    var Hotline = jQuery(msg).find('Hotline').text();
                    var TeleMaintenance = jQuery(msg).find('TeleMaintenance').text();
                    var Maintenance = jQuery(msg).find('Maintenance').text();
                    var SLA = jQuery(msg).find('SLA').text();
                    var VisiteSurSite = jQuery(msg).find('VisiteSurSite').text();
                    var reconductionAuto = jQuery(msg).find('reconductionAuto').text();
                    var durValid = jQuery(msg).find('durValid').text();
                    var isSAV = jQuery(msg).find('isSAV').text();
                    var qteTktPerDuree = jQuery(msg).find('qteTktPerDuree').text();
                    var qteTempsPerDuree = jQuery(msg).find('qteTempsPerDuree').text();
                    var qteTempsPerDureeH = jQuery(msg).find('qteTempsPerDureeH').text();
                    var qteTempsPerDureeM = jQuery(msg).find('qteTempsPerDureeM').text();

                    jQuery('#qteTktPerDureemod').val(qteTktPerDuree);
                    jQuery('#qteTempsPerDureeHmod').val(qteTempsPerDureeH);
                    jQuery('#qteTempsPerDureeMmod').val(qteTempsPerDureeM);

                    if (tva + "x" != "x")
                    {
                        var i =0;
                        var remi=0;
                        jQuery('#modtauxtva').find('option').each(function(){
                            if (tva.replace(/,/,'.') == jQuery(this).val())
                            {
                                remi=i;
                            }
                            i++;
                        });
                        jQuery('#modtauxtva').selectmenu('value',remi);
                    }

                    jQuery('#nbTicketmod').val(qte);
                    jQuery('#nbTicketMNTmod').val(qteMNT);
                    jQuery('#hotlinemod').val(Hotline);
                    jQuery('#telemaintenancemod').val(TeleMaintenance);
//                    if (Hotline == 1){
//                        jQuery('#hotlinemod').attr('checked',true);
//                    } else {
//                        jQuery('#hotlinemod').attr('checked',false);
//                    }
//                    if (TeleMaintenance == 1){
//                        jQuery('#telemaintenancemod').attr('checked',true);
//                    } else {
//                        jQuery('#telemaintenancemod').attr('checked',false);
//                    }
                    if (reconductionAuto == 1)
                    {
                        jQuery('#modrecondAuto').attr('checked',true);
                    } else {
                        jQuery('#modrecondAuto').attr('checked',false);
                    }
                    jQuery('#nbVisitemod').val(VisiteSurSite);
                    jQuery('#modSLA').val(SLA);
                    jQuery('#DurSAVmod').val(durValid);
                    var clause = jQuery(msg).find('clause').text();
                    jQuery('#modClauseProdCont').replaceWith('<div id="modClauseProdCont" class="ui-widget-content" style="padding: 5px;">'+clause+'</div>');

                    jQuery('input[name=typemod]').attr('checked',false);
                    jQuery('#DurValMntmod').val("");
                    jQuery('#DurValTktmod').val("");
                    if (Maintenance == 1)
                    {
                        jQuery('#MnTtypemod').attr('checked',true);
                        jQuery('#ticketmod').hide();
                        jQuery('#savgmaomod').hide();
                        modshowGMAO("MnT");
                        jQuery('#DurSAVmod').val("");
                        jQuery('#nbTicketmod').val("");
                        jQuery('#DurValMntmod').val(durValid);
                    } else if (isSAV == 1){
                        jQuery('#SaVtypemod').attr('checked',true);
                        jQuery('#ticketmod').hide();
                        jQuery('#maintenancemod').hide();
                        modshowGMAO("SaV");
                        jQuery('#nbTicketmod').val("");
                        jQuery('#nbTicketMNTmod').val("");
                        jQuery('#telemaintenancemod').attr('checked',false);
                        jQuery('#hotlinemod').attr('checked',false);
                    } else if (qte+"x"!= "x"){
                        jQuery('#TkTtypemod').attr('checked',true);
                        jQuery('#savgmaomod').hide();
                        jQuery('#maintenancemod').hide();
                        modshowGMAO("TkT");
                        jQuery('#DurSAVmod').val("");
                        jQuery('#nbTicketMNTmod').val("");
                        jQuery('#telemaintenancemod').attr('checked',false);
                        jQuery('#hotlinemod').attr('checked',false);
                        jQuery('#DurValTktmod').val(durValid);
                    }

                    jQuery('#modPuHT').val(price);
                }
            });
        }
    }

EOF;
        $js .= "</script>" . "\n";
        $html .= '<td class="ui-widget-content"><span id="' . $type . 'commandeDet">&nbsp;</span>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= "</table>";
        $html .= "</div>";


        $html .= "<div id='" . $type . "produit'>";

        $html .= "<div id='productCli'></div>";
        $html .= "</div>";
//        $html .= '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
//        $html .= '<tr>';
//        $html .= '<th colspan="4" class="ui-widget-header">Recherche de produits</th></tr>';
//        $html .= '<tr>';
//        $html .= '<th colspan=1 class="ui-state-default ui-widget-header">Produit</th>';
//        $html .= '<td class="ui-widget-content" width=175>';
//        // multiprix
//        $filter = "0";
//        if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES == 1)
//            $html .= $this->returnSelect_produits('', 'p_idprod_' . $type, $filter, $conf->produit->limit_size, $this->societe->price_level, 1, true, false, false);
//        else
//            $html .= $this->returnSelect_produits('', 'p_idprod_' . $type, $filter, $conf->produit->limit_size, false, 1, true, true, false);
//        if (!$conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
//            $html .= '<br>';
//        $html .= '<td class="ui-widget-content">';
//        $html .= '<div class="nocellnopadd" id="ajdynfieldp_idprod_' . $type . '"></div>';
//
//
//        $html .= '</td>';
//        $html .= '<tr>';
//        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Num&eacute;ro de s&eacute;rie';
//        $html .= '<td class="ui-widget-content" colspan=2><input type="text" style="width: 300px" name="' . $type . 'serial" id="' . $type . 'serial">';
//        $html .= "</table>";
//        $html .= "</div>";

        $html .= "<div id='" . $type . "price'>";
        $html .= '<table style="width: 870px; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" width=150 colspan=1>Produit contrat' . "\n";
        $html .= '<td class="ui-widget-content" colspan=1 width=175>' . "\n";
        $filter = "2";

//        $html .= '<input type="text" name="p_idContratprod_mod" value="p_idContratprod_mod"/>';
        if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES == 1)
            $html .= $this->returnSelect_produits('', 'p_idContratprod_' . $type, $filter, $conf->produit->limit_size, $this->societe->price_level, 1, true, false, false);
        else
            $html .= $this->returnSelect_produits('', 'p_idContratprod_' . $type, $filter, $conf->produit->limit_size, false, 1, true, true, false);
        if (!$conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
            $html .= '<br>';
        $html .= ' <td class="ui-widget-content" colspan=2>';
        $html .= '<div class="nocellnopadd" id="ajdynfieldp_idContratprod_' . $type . '"></div>';

        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Prix HT' . "\n";
        $html .= '<td class="ui-widget-content" colspan=1><input type="text" style="width: 100px" name="' . $type . 'PuHT" id="' . $type . 'PuHT">' . "\n";
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Quantit&eacute;' . "\n";
        $html .= '<td class="ui-widget-content" colspan=1><input type="text" style="width: 100px" value="1" name="' . $type . 'Qte" id="' . $type . 'Qte">' . "\n";
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>TVA' . "\n";
        $html .= '<td class="ui-widget-content" colspan=3>' . "\n";

        $form = new Form($this->db);
        $html .= $form->load_tva($type . 'tauxtva', '20', $mysoc, $this->societe->id, "", 0, false);

        $html .= '<tr style="border: 1px Solid #0073EA;">' . "\n";
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Ajustement <em>Prorata temporis</em>' . "\n";
        $html .= '<td class="ui-widget-content"  colspan=3><input type="checkbox" name="' . $type . 'prorata" id="' . $type . 'prorata">' . "\n";
        $html .= '</td>' . "\n";
        $html .= '</tr>' . "\n";

        $html .= "</table>";
        $html .= "</div>";
        $html .= "<div id='" . $type . "detail'>";
        $html .= '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header">Maintenance';
        $html .= '</th><td class="ui-widget-content" colspan=2><input id="MnTtype' . $type . '" name="type' . $type . '" value="MnT" type="radio"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header">Ticket';
        $html .= '</th><td colspan=2 class="ui-widget-content"><input id="TkTtype' . $type . '" name="type' . $type . '" value="TkT" type="radio"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-content">SAV';
        $html .= '</th><td colspan=2 class="ui-widget-content"><input id="SaVtype' . $type . '" name="type' . $type . '" value="SaV" type="radio"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-content">Autre';
        $html .= '</th><td colspan=2 class="ui-widget-content"><input id="Othertype' . $type . '" name="type' . $type . '" value="Other" type="radio"></td>';

        $html .= "</table>";
        $html .= "<div>";
        $html .= "<div id='maintenance" . $type . "' style='display: none;'>";
        $html .= '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=3>Maintenance';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Nb visite annuelle';
        $html .= '</th><td class="ui-widget-content" colspan=2><input id="nbVisite' . $type . '" name="nbVisite' . $type . '"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Nb visite curative';
        $html .= '</th><td class="ui-widget-content" colspan=2><input id="nbVisite' . $type . 'Cur" name="nbVisite' . $type . 'Cur"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>T&eacute;l&eacute;maintenance';
        $html .= '</th><td class="ui-widget-content" colspan=2><input type=text id="telemaintenance' . $type . '" name="telemaintenance' . $type . '"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>T&eacute;l&eacute;maintenance curative';
        $html .= '</th><td class="ui-widget-content" colspan=2><input type=text id="telemaintenance' . $type . 'Cur" name="telemaintenance' . $type . 'Cur"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Hotline';
        $html .= '</th><td class="ui-widget-content" colspan=2><input type=text name="hotline' . $type . '" id="hotline' . $type . '"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Dur&eacute;e validit&eacute;<br/>(en mois)';
        $html .= '</th><td class="ui-widget-content" colspan=2><input id="DurValMnt' . $type . '" name="DurValMnt' . $type . '"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Duree appel max avant interv. <br/><em><small>(en min)</small></em>';
        $html .= '</th><td class="ui-widget-content" colspan=2><input id="nbTicketMNT' . $type . '" name="nbTicketMNT' . $type . '"></td>';
        $html .= '<tr><th class="ui-widget-header ui-state-default" >Dur&eacute;e par ticket<br/><em><small>(<b>0 h 0 min</b> sans d&eacute;compte de temps)</small></em></th>
                   <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" value="1" id="qteTktPerDuree' . $type . '" name="qteTktPerDuree' . $type . '"> ticket(s) pour <input style="text-align:center;" type="text" size=4 value="0" id="qteTempsPerDureeH' . $type . '" name="qteTempsPerDureeH' . $type . '"> h <input style="text-align:center;" type="text" size=4 value="0" id="qteTempsPerDureeM' . $type . '" name="qteTempsPerDureeM' . $type . '"> min';
//                $qteTempsPerDureeM = 0;
//                $qteTempsPerDureeH = 0;
//                if ($product->qteTempsPerDuree > 0 ){
//                    $arrDur = convDur($product->qteTempsPerDuree);
//                    $qteTempsPerDureeH=$arrDur['hours']['abs'];
//                    $qteTempsPerDureeM=$arrDur['minutes']['rel'];
//                }
//
//
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Dur&eacute;e par ticket<br/><em><small>(<b>0 h 0 min</b>sans d&eacute;compte de temps)</small></em>").'</th>
//                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" value="'.$product->qteTktPerDuree.'" name="qteTktPerDuree"> ticket(s) pour <input style="text-align:center;" type="text" size=4 value="'.$qteTempsPerDureeH.'" name="qteTempsPerDureeH"> h <input style="text-align:center;" type="text" size=4 value="'.$qteTempsPerDureeM.'" name="qteTempsPerDureeM"> min';



        $html .= "</table>";
        $html .= "</div>";

        $html .= "<div id='ticket" . $type . "' style='display: none;'>";
        $html .= '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=3>Tickets';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Nb Ticket<br/><em><small>(<b>0</b> Sans ticket, <b>-1</b> Illimit&eacute;)</small></em>';
        $html .= '</th><td class="ui-widget-content" colspan=2><input id="nbTicket' . $type . '" name="nbTicket' . $type . '"></td>';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Dur&eacute;e validit&eacute;<br/>(en mois)';
        $html .= '</th><td class="ui-widget-content" colspan=2><input id="DurValTkt' . $type . '" name="DurValTkt' . $type . '"></td>';

        $html .= "</table>";
        $html .= "</div>";

        $html .= "<div id='savgmao" . $type . "' style='display: none;'>";
        $html .= '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=3>SAV';
        $html .= '<tr>';
        $html .= '<th class="ui-state-default ui-widget-header" colspan=1>Dur&eacute;e extension<br/>(en mois)';
        $html .= '</th><td class="ui-widget-content" colspan=2><input id="DurSAV' . $type . '" name="DurSAV' . $type . '"></td>';
        $html .= "</table>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        $html .= "</div>";

        $html .= '</form>';
        $html .= '</div>';
        return array($html, $js);
    }

    private function returnSelect_produits($selected = '', $htmlname = 'productid', $filtertype = '', $limit = 20, $price_level = 0, $status = 1, $finished = 2, $selected_input_value = '', $hidelabel = 0, $ajaxoptions = array()) {
        $form = new Form($this->db);

        ob_start();
        $form->select_produits($selected, $htmlname, $filtertype, $limit, $price_level, $status, $finished, $selected_input_value, $hidelabel, $ajaxoptions);
        $return = ob_get_contents();
        ob_clean();
        return $return;
    }

}

class Synopsis_ContratLigne extends ContratLigne {

    public function fetch($id, $ref = '') {
        parent::fetch($id, $ref);
        $sql = "SELECT d.rowid, g.type,
                       unix_timestamp(date_add(g.DateDeb, INTERVAL g.durValid month)) as GMAO_dfinprev,
                       unix_timestamp(date_add(g.DateDeb, INTERVAL g.durValid month)) as GMAO_dfin,
                       unix_timestamp(g.DateDeb) as GMAO_ddeb,
                       unix_timestamp(g.DateDeb) as GMAO_ddebprev,
                       g.durValid as GMAO_durVal,
                       g.hotline as GMAO_hotline,
                       g.telemaintenance as GMAO_telemaintenance,
                       g.telemaintenanceCur as GMAO_telemaintenanceCur,
                       g.maintenance as GMAO_maintenance,
                       g.SLA as GMAO_sla,
                       g.clause as GMAO_clause,
                       g.isSAV as GMAO_isSAV,
                       g.qte as GMAO_qte,
                       g.nbVisite as GMAO_nbVisite,
                       g.nbVisiteCur as GMAO_nbVisiteCur,
                       g.fk_prod as GMAO_fk_prod,
                       g.reconductionAuto as GMAO_reconductionAuto,
                       g.maintenance as GMAO_maintenance,
                       g.prorataTemporis as GMAO_prorata,
                       g.prixAn1 as GMAO_prixAn1,
                       g.prixAnDernier as GMAO_prixAnDernier,
                       g.fk_contrat_prod as GMAO_fk_contrat_prod,
                       g.qteTempsPerDuree as GMAO_qteTempsPerDuree,
                       g.qteTktPerDuree as GMAO_qteTktPerDuree,
                       sc.serial_number as GMAO_serial_number,
                       g.rang
                  FROM " . MAIN_DB_PREFIX . "contratdet as d
             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO as g ON g.contratdet_refid = d.rowid
             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_product_serial_cont as sc ON sc.element_id = d.rowid
                 WHERE d.rowid = " . $this->id;
//date_debut_prevue = $objp->date_ouverture_prevue;
//print $sql;
//                //$ligne->date_debut_reel   = $objp->date_ouverture;
        dol_syslog("Contrat::fetch_lignes sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
//            $this->lignes = array();
            $num = $this->db->num_rows($result);
            $i = 0;

            $objp = $this->db->fetch_object($result);

//                $ligne = new ContratLigne($this->db);
//            $ligne = $this;
//                $ligne->description = $objp->description;  // Description ligne
//                $ligne->description = $objp->description;  // Description ligne
//                $ligne->qty = $objp->qty;
//                $ligne->fk_contrat = $this->id;
//                $ligne->tva_tx = $objp->tva_tx;
//                $ligne->subprice = $objp->subprice;
//                $ligne->statut = $objp->statut;
//                $ligne->remise_percent = $objp->remise_percent;
//                $ligne->price = $objp->total_ht;
//                $ligne->total_ht = $objp->total_ht;
//                $ligne->fk_product = $objp->fk_product;
//                $ligne->type = $objp->type;           //contrat Mixte
//                $ligne->avenant = $objp->avenant;
//                require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
//                $tmpProd = new Product($this->db);
            $tmpProd1 = new Product($this->db);
            if ($this->fk_product > 0) {
                $tmpProd1->fetch($this->fk_product);
                $tmpProd1->fetch_optionals($this->fk_product);
            }
            $tmpProd2 = new Product($this->db);
            if ($objp->GMAO_fk_prod > 0) {
                $tmpProd2->fetch($objp->GMAO_fk_prod);
                $tmpProd2->fetch_optionals($objp->GMAO_fk_prod);
            }
            $this->rang = $objp->rang;
            $this->prodContrat = $tmpProd1;
            $this->prod2 = $tmpProd2;
            $this->type = $objp->type;
            $this->serial_number = $objp->GMAO_serial_number;
            $this->SLA = $objp->GMAO_sla;
            $this->qty2 = $objp->GMAO_qte;
//                ($objp->GMAO_fk_contrat_prod > 0 ? $tmpProd->fetch($objp->GMAO_fk_contrat_prod) : $tmpProd = false);
//                $ligne->product = $tmpProd1;
////LineTkt
////                    'serial_number'=>$res->serial_number ,
//                @$ligne->GMAO_Mixte = array();
            $this->GMAO_Mixte = array(
                'contrat_prod' => $tmpProd1,
                'durVal' => $objp->GMAO_durVal,
                'tickets' => $objp->GMAO_qte,
                'dfinprev' => $objp->GMAO_dfinprev,
                'dfin' => $objp->GMAO_dfin,
                'ddeb' => $objp->GMAO_ddeb,
                'hotline' => $objp->GMAO_hotline,
                'telemaintenance' => $objp->GMAO_telemaintenance,
                'telemaintenanceCur' => $objp->GMAO_telemaintenanceCur,
                'maintenance' => $objp->GMAO_maintenance,
                'SLA' => $objp->GMAO_sla,
                'nbVisiteAn' => $objp->GMAO_nbVisite,
                'nbVisiteAnCur' => $objp->GMAO_nbVisiteCur,
                'isSAV' => $objp->GMAO_isSAV,
                'fk_prod' => $objp->GMAO_fk_prod,
                'reconductionAuto' => $objp->GMAO_reconductionAuto,
                'serial_number' => $objp->GMAO_serial_number,
                'ddebprev' => $objp->GMAO_ddebprev,
                "clause" => $objp->GMAO_clause,
                "prorata" => $objp->GMAO_prorata,
                "prixAn1" => $objp->GMAO_prixAn1,
                "prixAnDernier" => $objp->GMAO_prixAnDernier,
                "qteTempsPerDuree" => $objp->GMAO_qteTempsPerDuree,
                "qteTktPerDuree" => $objp->GMAO_qteTktPerDuree,
            );
        }



        $this->tabProdCli = array();
        $tabR = getElementElement("contratdet", "productCli", $this->id);
        foreach ($tabR as $elem)
            $this->tabProdCli[] = $elem['d'];
    }

    public function getTitreInter() {
        $dsc = '';
        $requete = "SELECT nbVisite as nb, ref, qte, qty, GMAO.* FROM `" . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO` GMAO, " . MAIN_DB_PREFIX . "contrat c, " . MAIN_DB_PREFIX . "contratdet cdet WHERE c.rowid = cdet.fk_contrat AND cdet.rowid = contratdet_refid AND contratdet_refid = " . $_REQUEST['fk_contratdet'];
        $sql = $this->db->query($requete);
        $tabExiste = getElementElement("contratdet", "synopsisdemandeinterv", $_REQUEST['fk_contratdet']);
        $nbExiste = count($tabExiste);
        $tabExiste = getElementElement("contratdet", "fichinter", $_REQUEST['fk_contratdet']);
        $nbExiste += count($tabExiste);
        while ($result = $this->db->fetch_object($sql)) {
//            print_r($result);
            $qte = $result->qte * $result->qty;
            if ($result->nb)
                $dsc = "Visite sur site " . ($nbExiste + 1) . " / " . $result->nb * $result->qty . " Contrat : " . $result->ref;
            elseif ($result->telemaintenance)
                $dsc = "Télémaintenance " . ($nbExiste + 1) . " / " . $result->telemaintenance * $result->qty . " Contrat : " . $result->ref;
            elseif ($result->hotline)
                $dsc = "Hotline " . ($nbExiste + 1) . " / " . $qte . " Contrat : " . $result->ref;
        }
        return $dsc;
    }

    function getInfoOneProductCli($idProdCli, $opt = "", $size = 200) {
        $html = '';
        if($opt != "SN")
        $html = "\n";
        $sql = $this->db->query("SELECT description FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id =" . $idProdCli);
        if ($this->db->num_rows($sql) > 0 && $opt != "SN") {
            $result = $this->db->fetch_object($sql);
            if ($result->description != "") {
                $html .= $result->description . " : ";
            }
        }
        
        
        $sql = $this->db->query("SELECT  Produit as value FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_101 WHERE id =" . $idProdCli . "");
        if ($this->db->num_rows($sql) > 0 && $opt != "SN") {
            $result = $this->db->fetch_object($sql);
            if ($result->value > 0) {
                $prod = new Product($this->db);
                $prod->fetch($result->value);
                $html .= $prod->libelle . " ";
            }
        }

//        $sql = $this->db->query("SELECT value FROM " . MAIN_DB_PREFIX . "synopsischrono_value WHERE chrono_refid =" . $idProdCli . " AND key_id = 1012");
//        if ($this->db->num_rows($sql) > 0 && $opt != "SN") {
//            $result = $this->db->fetch_object($sql);
//            $html .= "(" . $result->value . ") ";
//        }

        $sql = $this->db->query("SELECT N__Serie as value FROM " . MAIN_DB_PREFIX . "synopsischrono_chrono_101 WHERE id =" . $idProdCli . "");
        if ($this->db->num_rows($sql) > 0) {
            $result = $this->db->fetch_object($sql);
            $html .= ($result->value != "" && $opt != "SN") ? " [SN : " : "";
            $html .= ($result->value != "") ? $result->value : "";
            $html .= ($result->value != "" && $opt != "SN") ? "]" : "";
        }
        return dol_trunc($html, $size);
    }

    function getInfoProductCli($opt = "", $size = 200) {
        $elems = getElementElement("contratdet", "productCli", $this->id);
        $htmlT = array();
        foreach ($elems as $elem) {
            $infoT = $this->getInfoOneProductCli($elem['d'], $opt, $size);
            if($infoT != '')
            $htmlT[] = $infoT;
        }
        return dol_trunc(implode("\n", $htmlT), $size);
    }

}

?>

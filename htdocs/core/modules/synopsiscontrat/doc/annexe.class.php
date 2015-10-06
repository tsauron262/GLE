<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of annexe
 *
 * @author minijean
 */
class annexe {

    function annexe($pdf, $model, $outputlangs) {
        $this->pdf = $pdf;
        $this->model = $model;
        $this->outputlangs = $outputlangs;
        $this->db = $model->db;
        $this->rang = 0;
        $this->i = 0;
    }

    function getAnnexeCGV($object) {
        $this->element = $object;
        $requete = "SELECT *, annexe as annexeP
                              FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf
                             WHERE ref LIKE  '%C%'";
//                die($requete);
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $this->getOneAnnexe($res);
        }
        $this->model->_pagefoot($this->pdf, $this->element, $this->outputlangs);
    }

    function getAnnexeContrat($contrat) {
        $this->element = $contrat;
        $requete = "SELECT *, IF(a.annexe != '', a.annexe, p.annexe) as annexeP
                              FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf as p,
                                   " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe as a
                             WHERE p.id = a.annexe_refid AND type = 1
                               AND a.contrat_refid = " . $contrat->id . "
                          ORDER BY a.rang";
//                die($requete);
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            $this->getOneAnnexe($res);
        }

        $this->model->_pagefoot($this->pdf, $this->element, $this->outputlangs);
    }

    function getOneAnnexe($res) {
//        if (!$this->i == 0)
        $this->model->_pagefoot($this->pdf, $this->element, $this->outputlangs);
        $this->pdf->AddPage();
        $this->model->_pagehead($this->pdf, $this->element, 0, $this->outputlangs);
        $this->i++;
        if ($arrAnnexe[$res->ref]['lnk'] > 0) {
            $this->pdf->SetLink($arrAnnexe[$res->ref]['lnk'], $this->model->marge_haute);
        }
        $this->pdf->SetFont('', '', 8);
        $this->pdf->SetXY($this->model->marge_gauche, $this->model->marge_haute + $this->model->hauteurHeader);
        $this->pdf->SetFont('', 'B', 12);
        if ($res->afficheTitre == 1) {
            $this->rang++;
            $this->pdf->multicell(155, 7, utf8_encodeRien(utf8_encodeRien("Annexe " . $this->rang . " : " . $res->modeleName)), 0, 'L');
        } else {
            $this->pdf->multicell(155, 7, utf8_encodeRien(utf8_encodeRien($res->modeleName)), 0, 'L');
        }
        $this->pdf->SetFont('', '', 8);
        $this->pdf->SetXY($this->model->marge_gauche, $this->pdf->GetY() + 5);

        //Traitement annexe :>
        $annexe = $res->annexeP;
        $annexe = $this->replaceWithAnnexe($res->annexeP, $this->element, $res->annexe_refid);
//
//                    $this->pdf->multicell(155, 5, utf8_encodeRien(utf8_encodeRien($annexe)));
        $tabLigneAnnexe = explode("\n", $annexe);
        foreach ($tabLigneAnnexe as $idL => $ligne) {
            $style = '';
            $titre = false;
            if (stripos($ligne, "<g>") > -1) {
                $ligne = str_replace("<g>", "", $ligne);
                $titre = true;
                $style .= 'B';
            }
            if (stripos($ligne, "<i>") > -1) {
                $ligne = str_replace("<i>", "", $ligne);
                $style .= 'I';
            }
            if (stripos($ligne, "<s>") > -1) {
                $ligne = str_replace("<s>", "", $ligne);
                $style .= 'U';
            }


            $nbCarac = strlen($ligne);
            $nbLn = 0;
            $maxCarac = 105;
            while ($nbCarac > $maxCarac) {
                $nbCarac = $nbCarac - $maxCarac;
                $nbLn++;
            }
            $yAfter = $this->pdf->getY() + (5 * $nbLn);
            if ($yAfter > 270 || ($titre && $yAfter > 250 && (count($tabLigneAnnexe) - 3) > $idL)) {
                $this->model->_pagefoot($this->pdf, $this->element, $this->outputlangs);
                $this->pdf->AddPage();
                $this->model->_pagehead($this->pdf, $this->element, 0, $this->outputlangs);
                $this->pdf->SetY($this->model->marge_haute + $this->model->hauteurHeader);
            }

            $this->pdf->SetFont('', $style, 8);
            if($ligne != "")
                $this->pdf->multicell(155, 5, utf8_encodeRien(utf8_encodeRien($ligne)), 0, 'L');
        }
    }

    function replaceWithAnnexe($annexe, $contrat, $idAnnexe) {
        global $mysoc, $user, $langs;

        if (stripos(get_class($contrat), "contrat") !== false) {
            //Tritement des contact
            $contacts = array();
            foreach ($contrat->list_all_valid_contacts() as $key => $val) {
                foreach (array('fullname', 'civility', 'nom', 'prenom', 'cp', 'ville', 'email', 'tel', 'fax') as $val0) {
                    $code = "Contact-" . $val['source'] . "-" . $val['code'] . "-" . $val0;
                    $annexe = preg_replace('/' . $code . "/", $val[$val0], $annexe);
                }
                $tempStr = utf8_encodeRien('Contact-external-CUSTOMER-fullname
Mail : Contact-external-CUSTOMER-email
Tél. : Contact-external-CUSTOMER-tel
');
                foreach (array('fullname', 'civility', 'nom', 'prenom', 'cp', 'ville', 'email', 'tel', 'fax') as $val0) {
                    $code = "Contact-" . $val['source'] . "-" . $val['code'] . "-" . $val0;
                    $result = $val[$val0];
                    $tempStr = preg_replace('/' . $code . "/", $result, $tempStr);
                }
                $contacts[$val['code']][] = $tempStr;
            }
            foreach ($contacts as $typeContact => $val) {
                $annexe = preg_replace('/Contacts-' . $typeContact . '/', implode("

", $val), $annexe);
            }

            $sql2 = "SELECT lnCon.rowid FROM `" . MAIN_DB_PREFIX . "product_extrafields` prod, `" . MAIN_DB_PREFIX . "contratdet` lnCon
				WHERE lnCon.`fk_product` = prod.fk_object 
				    AND lnCon.`fk_contrat` = '" . $contrat->id . "' AND
                                    prod.`2annexe` = '" . $idAnnexe . "'";
            $res = $this->db->query($sql2);
            //$result = $this->db->fetch_object($res);
            $desc = "";
            $dateFin = "";
            $qte = 0;
            $qte2 = 0;
            $qteT = 0;
            $phraseDelai = "";
            while ($result = $this->db->fetch_object($res)) {
                $ligneContrat = new Synopsis_ContratLigne($this->db);
                $ligneContrat->fetch($result->rowid);
                //if (isset($result->date_fin_validite)) {
                $sla = ($ligneContrat->SLA != '') ? " (" . $ligneContrat->SLA . ")" : "";


//                $serialNum = ($ligneContrat->serial_number != '') ? " \n SN : " . $ligneContrat->serial_number . "" : "";
                $desc .= /*$ligneContrat->description . $sla .*/ $serialNum . str_replace("\n\n", "\n", $ligneContrat->getInfoProductCli("", 1000)) . "";
                $dateFin = date('d/m/Y', $ligneContrat->date_fin_validite);
                $qte += $ligneContrat->qty;
                $qte2 += $ligneContrat->qte2;
                $qteTt = $ligneContrat->qty + $ligneContrat->qte2;
                $qteT += $qteTt;
                if ($qteTt == "8")
                    $phraseDelai = "Couplé au contrat de télémaintenance, ce contrat comprend 8 visites par an.";
                elseif ($qteTt > 0)
                    $phraseDelai = "Couplé au contrat de télémaintenance, ce contrat comprend 1 visite de suivi tous les " . (12 / $qteTt) . " mois sur site (soit " . $qteTt . " visite(s) par an).";
                
                
                if($ligneContrat->GMAO_Mixte['nbVisiteAnCur'] > 0)
                    $phraseDelai .= "\nCe contrat comprend ".$ligneContrat->GMAO_Mixte['nbVisiteAnCur']." visites curative par an.";
                
                if($ligneContrat->GMAO_Mixte['telemaintenanceCur'] > 0)
                    $phraseDelai .= "\nCe contrat comprend ".$ligneContrat->GMAO_Mixte['telemaintenanceCur']." télémaintenance curative par an.";
            }


            $annexe = preg_replace('/Ligne-date_fin/', $dateFin, $annexe);
            $annexe = preg_replace('/Ligne-description/', html_entity_decode($desc), $annexe);
            $annexe = preg_replace('/Ligne-phrase_delai/', utf8_encodeRien($phraseDelai), $annexe);
            $annexe = preg_replace('/Ligne-qte/', $qteT, $annexe);
            $annexe = preg_replace('/Ligne-qte2/', $qte2, $annexe);


            $annexe = preg_replace('/Contrat-date_contrat/', $contrat->date_contrat, $annexe);
            $annexe = preg_replace('/Contrat-date_fin/', dol_print_date($val->date_fin_validite), $annexe);
            $annexe = preg_replace('/Contrat-ref/', $contrat->ref, $annexe);
            $annexe = preg_replace('/Contrat-note_public/', $contrat->note_public, $annexe);
        }
        $socid = $contrat->socid;


        $sql = $this->db->query("SELECT di.*, fi.fk_user_author FROM ".MAIN_DB_PREFIX."synopsisdemandeinterv di LEFT JOIN `".MAIN_DB_PREFIX."element_element` el ON
 el.`fk_source` = di.rowid
AND  el.`sourcetype` LIKE  'di'
AND  el.`targettype` LIKE  'fi' LEFT JOIN ".MAIN_DB_PREFIX."fichinter fi ON fi.rowid = el.fk_target  WHERE di.fk_contrat = ".$contrat->id. " GROUP BY di.rowid ORDER BY di.datei ASC");
        $strDi = "";
        $tabTemp = array();
        while($result = $this->db->fetch_object($sql)){
            $motClef = "";
            if(stripos($result->description, "Visite") !== false)
                    $motClef = "Visite(s)";
            if(stripos($result->description, "Télémaintenance") !== false)
                    $motClef = "Télémaintenance(s)";
            $tabTemp[$motClef] .= "       - ".$result->description . " - ".dol_print_date($this->db->jdate($result->datei));
            if(isset($result->fk_user_author)){
                $userT = new User($this->db);
                $userT->fetch($result->fk_user_author);
                $tabTemp[$motClef] .= " Fait par ".$userT->getFullName($langs);
            }
            $tabTemp[$motClef] .= "\n";
        }
        ksort($tabTemp);
        foreach($tabTemp as $motClef => $val){
            if($motClef != "")
            $strDi .= "\n<g>".$motClef." : \n".$val;
            else
            $strDi .= $val;
        }
        $annexe = preg_replace('/Date-Di/', $strDi, $annexe);




        $annexe = preg_replace('/User-fullname/', $user->getFullName($langs), $annexe);
        $annexe = preg_replace('/User-nom/', $user->lastname, $annexe);
        $annexe = preg_replace('/User-prenom/', $user->firstname, $annexe);
        $annexe = preg_replace('/User-email/', $user->email, $annexe);
        $annexe = preg_replace('/User-office_phone/', $user->office_phone, $annexe);
        $annexe = preg_replace('/User-user_mobile/', $user->user_mobile, $annexe);
        $annexe = preg_replace('/User-office_fax/', $user->office_fax, $annexe);

        $annexe = preg_replace('/Mysoc-nom/', $mysoc->nom, $annexe);
        $annexe = preg_replace('/Mysoc-adresse_full/', $mysoc->address."\n".$mysoc->zip." ".$mysoc->town, $annexe);
        $annexe = preg_replace('/Mysoc-adresse/', $mysoc->address, $annexe);
        $annexe = preg_replace('/Mysoc-cp/', $mysoc->zip, $annexe);
        $annexe = preg_replace('/Mysoc-ville/', $mysoc->town, $annexe);
        $annexe = preg_replace('/Mysoc-tel/', $mysoc->phone, $annexe);
        $annexe = preg_replace('/Mysoc-fax/', $mysoc->fax, $annexe);
        $annexe = preg_replace('/Mysoc-email/', $mysoc->email, $annexe);
        $annexe = preg_replace('/Mysoc-url/', $mysoc->url, $annexe);
        $annexe = preg_replace('/Mysoc-rcs/', $mysoc->rcs, $annexe);
        $annexe = preg_replace('/Mysoc-siren/', $mysoc->siren, $annexe);
        $annexe = preg_replace('/Mysoc-siret/', $mysoc->siret, $annexe);
        $annexe = preg_replace('/Mysoc-ape/', $mysoc->ape, $annexe);
        $annexe = preg_replace('/Mysoc-tva_intra/', $mysoc->tva_intra, $annexe);
        $annexe = preg_replace('/Mysoc-capital/', $mysoc->capital, $annexe);

        $societe = new Societe($this->db);
        $societe->fetch($socid);

        $annexe = preg_replace('/Soc-titre/', $societe->titre, $annexe);
        $annexe = preg_replace('/Soc-nom/', $societe->nom, $annexe);
        $annexe = preg_replace('/Soc-adresse_full/', $societe->adresse_full, $annexe);
        $annexe = preg_replace('/Soc-adresse/', $societe->adresse, $annexe);
        $annexe = preg_replace('/Soc-cp/', $societe->cp, $annexe);
        $annexe = preg_replace('/Soc-ville/', $societe->ville, $annexe);
        $annexe = preg_replace('/Soc-tel/', $societe->tel, $annexe);
        $annexe = preg_replace('/Soc-fax/', $societe->fax, $annexe);
        $annexe = preg_replace('/Soc-email/', $societe->email, $annexe);
        $annexe = preg_replace('/Soc-url/', $societe->url, $annexe);
        $annexe = preg_replace('/Soc-siren/', $societe->siren, $annexe);
        $annexe = preg_replace('/Soc-siret/', $societe->siret, $annexe);
        $annexe = preg_replace('/Soc-code_client/', $societe->code_client, $annexe);
        $annexe = preg_replace('/Soc-note/', $societe->note, $annexe);
        $annexe = preg_replace('/Soc-ref/', $societe->ref, $annexe);

        $annexe = preg_replace('/DateDuJour/', date('d/m/Y'), $annexe);




        $arr['fullname'] = 'Nom complet';
        $arr['cp'] = 'Code postal';
        $arr['ville'] = 'Ville';
        $arr['email'] = 'Email';
        $arr['fax'] = utf8_encodeRien('N° fax');
        $arr['tel'] = utf8_encodeRien('N° tel');
        $arr['civility'] = 'Civilit&eacute;';
        $arr['nom'] = 'Nom';
        $arr['prenom'] = 'Pr&eacute;om';

        return $annexe;
    }
    
    public static function getListAnnexe($type){
        
    }
}

?>

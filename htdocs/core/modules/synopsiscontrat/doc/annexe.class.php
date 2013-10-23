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
    function annexe(){
        
    }
    
    function getAnnexe($contrat, $pdf, $model, $outputlangs){
            $this->db = $model->db;
                $requete = "SELECT *, IF(a.annexe != '', a.annexe, p.annexe) as annexeP
                              FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf as p,
                                   " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe as a
                             WHERE p.id = a.annexe_refid
                               AND a.contrat_refid = " . $contrat->id . "
                          ORDER BY a.rang";
//                die($requete);
                $sql = $model->db->query($requete);
                $i = 0;
                $rang = 1;
                while ($res = $model->db->fetch_object($sql)) {
                    if (!$i == 0)
                        $model->_pagefoot($pdf, $outputlangs);
                    $pdf->AddPage();
                    $model->_pagehead($pdf, $contrat, 1, $outputlangs);
                    $i++;
                    if ($arrAnnexe[$res->ref]['lnk'] > 0) {
                        $pdf->SetLink($arrAnnexe[$res->ref]['lnk'], $model->marge_haute);
                    }
                    $pdf->SetFont('', '', 8);
                    $pdf->SetXY($model->marge_gauche, $model->marge_haute);
                    $pdf->SetFont('', 'B', 12);
                    if ($res->afficheTitre == 1) {
                        $pdf->multicell(155, 7, utf8_encodeRien(utf8_encodeRien("Annexe " . $rang . " : " . $res->modeleName)), 0, 'L');
                        $rang++;
                    } else {
                        $pdf->multicell(155, 7, utf8_encodeRien(utf8_encodeRien($res->modeleName)), 0, 'L');
                    }
                    $pdf->SetFont('', '', 8);
                    $pdf->SetXY($model->marge_gauche, $pdf->GetY() + 5);

                    //Traitement annexe :>
                    $annexe = $this->replaceWithAnnexe($res->annexeP, $contrat, $res->annexe_refid);



//
//                    $pdf->multicell(155, 5, utf8_encodeRien(utf8_encodeRien($annexe)));
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
                        $yAfter = $pdf->getY() + (5 * $nbLn);
                        if ($yAfter > 270 || ($titre && $yAfter > 250 && (count($tabLigneAnnexe)-3) > $idL)) {
                            $model->_pagefoot($pdf, $outputlangs);
                            $pdf->AddPage();
                            $model->_pagehead($pdf, $contrat, 1, $outputlangs);
                            $pdf->SetY($model->marge_haute);
                        }

                        $pdf->SetFont('', $style, 8);
                        $pdf->multicell(155, 5, utf8_encodeRien(utf8_encodeRien($ligne)), 0, 'L');
                    }
                }


                $model->_pagefoot($pdf, $outputlangs);

    }
    
        function replaceWithAnnexe($annexe, $contrat, $idAnnexe) {
        global $mysoc, $user;
        //Tritement des contact
        $contacts = array();
        foreach ($contrat->list_all_valid_contacts() as $key => $val) {
            foreach (array('fullname', 'civilite', 'nom', 'prenom', 'cp', 'ville', 'email', 'tel', 'fax') as $val0) {
                $code = "Contact-" . $val['source'] . "-" . $val['code'] . "-" . $val0;
                $annexe = preg_replace('/' . $code . "/", $val[$val0], $annexe);
            }
            $tempStr = utf8_encodeRien('Contact-external-CUSTOMER-fullname
Mail : Contact-external-CUSTOMER-email
Tél. : Soc-tel
');
            foreach (array('fullname', 'civilite', 'nom', 'prenom', 'cp', 'ville', 'email', 'tel', 'fax') as $val0) {
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








        $annexe = preg_replace('/User-fullname/', $user->getFullName($langs), $annexe);
        $annexe = preg_replace('/User-nom/', $user->lastname, $annexe);
        $annexe = preg_replace('/User-prenom/', $user->firstname, $annexe);
        $annexe = preg_replace('/User-email/', $user->email, $annexe);
        $annexe = preg_replace('/User-office_phone/', $user->office_phone, $annexe);
        $annexe = preg_replace('/User-user_mobile/', $user->user_mobile, $annexe);
        $annexe = preg_replace('/User-office_fax/', $user->office_fax, $annexe);

        $annexe = preg_replace('/Mysoc-nom/', $mysoc->nom, $annexe);
        $annexe = preg_replace('/Mysoc-adresse_full/', $mysoc->address_full, $annexe);
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

        $annexe = preg_replace('/Soc-titre/', $contrat->societe->titre, $annexe);
        $annexe = preg_replace('/Soc-nom/', $contrat->societe->nom, $annexe);
        $annexe = preg_replace('/Soc-adresse_full/', $contrat->societe->adresse_full, $annexe);
        $annexe = preg_replace('/Soc-adresse/', $contrat->societe->adresse, $annexe);
        $annexe = preg_replace('/Soc-cp/', $contrat->societe->cp, $annexe);
        $annexe = preg_replace('/Soc-ville/', $contrat->societe->ville, $annexe);
        $annexe = preg_replace('/Soc-tel/', $contrat->societe->tel, $annexe);
        $annexe = preg_replace('/Soc-fax/', $contrat->societe->fax, $annexe);
        $annexe = preg_replace('/Soc-email/', $contrat->societe->email, $annexe);
        $annexe = preg_replace('/Soc-url/', $contrat->societe->url, $annexe);
        $annexe = preg_replace('/Soc-siren/', $contrat->societe->siren, $annexe);
        $annexe = preg_replace('/Soc-siret/', $contrat->societe->siret, $annexe);
        $annexe = preg_replace('/Soc-code_client/', $contrat->societe->code_client, $annexe);
        $annexe = preg_replace('/Soc-note/', $contrat->societe->note, $annexe);
        $annexe = preg_replace('/Soc-ref/', $contrat->societe->ref, $annexe);

        $annexe = preg_replace('/Contrat-date_contrat/', $contrat->date_contrat, $annexe);
        $annexe = preg_replace('/Contrat-date_fin/', dol_print_date($val->date_fin_validite), $annexe);
        $annexe = preg_replace('/Contrat-ref/', $contrat->ref, $annexe);
        $annexe = preg_replace('/Contrat-note_public/', $contrat->note_public, $annexe);

        $annexe = preg_replace('/DateDuJour/', date('d/m/Y'), $annexe);


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
        $phraseDelai = "";
        while ($result = $this->db->fetch_object($res)) {
            $ligneContrat = new Synopsis_ContratLigne($this->db);
            $ligneContrat->fetch($result->rowid);
            //if (isset($result->date_fin_validite)) {
            $sla = ($ligneContrat->SLA != '') ? " (" . $ligneContrat->SLA . ")" : "";
            $serialNum = ($ligneContrat->serial_number != '') ? " \n SN : " . $ligneContrat->serial_number . "" : "";
            $desc .= $ligneContrat->description . $sla . $serialNum . "\n\n";
            $dateFin = date('d/m/Y', $ligneContrat->date_fin_validite);
            $qte += $ligneContrat->qte;
            $qte2 += $ligneContrat->qte2;
            if ($result->qty2 == "8")
                $phraseDelai = "Couplé au contrat de télémaintenance, ce contrat comprend 8 visites par an.";
            elseif ($result->qty2 > 0)
                $phraseDelai = "Couplé au contrat de télémaintenance, ce contrat comprend 1 visite de suivi tous les " . (12 / $result->qty) . " mois sur site (soit " . $result->qty . " visites par an).";
        }


        $annexe = preg_replace('/Ligne-date_fin/', $dateFin, $annexe);
        $annexe = preg_replace('/Ligne-description/', html_entity_decode($desc), $annexe);
        $annexe = preg_replace('/Ligne-phrase_delai/', utf8_encodeRien($phraseDelai), $annexe);
        $annexe = preg_replace('/Ligne-qte/', $qte, $annexe);
        $annexe = preg_replace('/Ligne-qte2/', $qte2, $annexe);



        $arr['fullname'] = 'Nom complet';
        $arr['cp'] = 'Code postal';
        $arr['ville'] = 'Ville';
        $arr['email'] = 'Email';
        $arr['fax'] = utf8_encodeRien('N° fax');
        $arr['tel'] = utf8_encodeRien('N° tel');
        $arr['civilite'] = 'Civilit&eacute;';
        $arr['nom'] = 'Nom';
        $arr['prenom'] = 'Pr&eacute;om';





        /*


          Contact-external-SALESREPSIGN-fullname  Nom complet     M. PIROCHE
          Contact-external-SALESREPSIGN-civilite  Civilité
          Contact-external-SALESREPSIGN-nom   Nom     PIROCHE
          Contact-external-SALESREPSIGN-prenom    Préom
          Contact-external-SALESREPSIGN-cp    Code postal
          Contact-external-SALESREPSIGN-ville     Ville
          Contact-external-SALESREPSIGN-email     Email
          Contact-external-SALESREPSIGN-tel   N° tel
          Contact-external-SALESREPSIGN-fax   N° fax

          Contact-internal-SALESREPFOLL-fullname  Nom complet     Jean-Marcéé LE FEVRE
          Contact-internal-SALESREPFOLL-civilite  Civilité
          Contact-internal-SALESREPFOLL-nom   Nom     LE FEVRE
          Contact-internal-SALESREPFOLL-prenom    Préom   Jean-Marcéé
          Contact-internal-SALESREPFOLL-cp    Code postal
          Contact-internal-SALESREPFOLL-ville     Ville
          Contact-internal-SALESREPFOLL-email     Email   tommy@drsi.fr
          Contact-internal-SALESREPFOLL-tel   N° tel
          Contact-internal-SALESREPFOLL-fax   N° fax

          Contact-internal-TECHRESP-fullname  Nom complet     Jean-Marcéé LE FEVRE
          Contact-internal-TECHRESP-civilite  Civilité
          Contact-internal-TECHRESP-nom   Nom     LE FEVRE
          Contact-internal-TECHRESP-prenom    Préom   Jean-Marcéé
          Contact-internal-TECHRESP-cp    Code postal
          Contact-internal-TECHRESP-ville     Ville
          Contact-internal-TECHRESP-email     Email   tommy@drsi.fr
          Contact-internal-TECHRESP-tel   N° tel
          Contact-internal-TECHRESP-fax   N° fax

          Contact-internal-SALESREPSIGN-fullname  Nom complet     Jean-Marcéé LE FEVRE
          Contact-internal-SALESREPSIGN-civilite  Civilité
          Contact-internal-SALESREPSIGN-nom   Nom     LE FEVRE
          Contact-internal-SALESREPSIGN-prenom    Préom   Jean-Marcéé
          Contact-internal-SALESREPSIGN-cp    Code postal
          Contact-internal-SALESREPSIGN-ville     Ville
          Contact-internal-SALESREPSIGN-email     Email   tommy@drsi.fr
          Contact-internal-SALESREPSIGN-tel   N° tel
          Contact-internal-SALESREPSIGN-fax   N° fax


         */
        return $annexe;
    }

}



?>

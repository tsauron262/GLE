<?php

/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/*
 * or see http://www.gnu.org/
 */

/**
  \file       htdocs/core/modules/synopsisficheinter/pdf_pluton.modules.php
  \ingroup    ficheinter
  \brief      Fichier de la classe permettant de generer les fiches d'intervention au modele pluton
  \version    $Id: pdf_pluton.modules.php,v 1.46 2008/07/29 19:20:34 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/modules_synopsisfichinter.php");
require_once(DOL_DOCUMENT_ROOT . "/lib/company.lib.php");

/**
  \class      pdf_pluton
  \brief      Classe permettant de generer les fiches d'intervention au modele pluton
 */
class pdf_pluton extends ModelePDFFicheinter {

    /**
      \brief      Constructeur
      \param        db        Handler acces base de donnees
     */
    function pdf_pluton($db=0) {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = 'pluton';
        $this->description = "Modele de fiche d'intervention standard";

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 5;
        $this->marge_droite = 5;
        $this->marge_haute = 10;
        $this->marge_basse = 10;

        $this->option_logo = 1;                    // Affiche logo
        $this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 0;                 // Affiche mode reglement
        $this->option_condreg = 0;                 // Affiche conditions reglement
        $this->option_codeproduitservice = 0;      // Affiche code produit-service
        $this->option_multilang = 0;               // Dispo en plusieurs langues
        $this->option_draft_watermark = 1;           //Support add of a watermark on drafts


        $this->totalBonIdx = 13;
        $this->bonRemisIdx = 14;
        $this->totalPieceIdx = 15;

        $this->dateFinAMIdx = 24;
        $this->dateDebPMIdx = 27;
        $this->dateFinPMIdx = 25;
        $this->dateDebAMIdx = 26;

        $this->isInstallationIdx = 21;
        $this->isInterventionIdx = 23;
        $this->isForfaitIdx = 35;

        $this->recupDataIdx = 16;
        $this->isIntervTermineIdx = 17;
        $this->dateNextRdvIdx = 18;
        $this->attenteClientIdx = 19;
        $this->miseEnRelationIdx = 20;
        $this->precoIdx = 28;
        $this->remarqueCliIdx = 29;
        $this->techALheureIdx = 30;
        $this->infoTacheDurIdx = 31;
        $this->satisfactionIdx = 32;
        $this->recontactComIdx = 33;

        $this->propContratIdx = 34;

        // Recupere code pays de l'emmetteur
        $this->emetteur = $mysoc;
        if (!$this->emetteur->code_pays)
            $this->emetteur->code_pays = substr($langs->defaultlang, -2);    // Par defaut, si n'etait pas defini








    }

    /**
      \brief      Fonction generant la fiche d'intervention sur le disque
      \param        fichinter        Object fichinter
      \return        int             1=ok, 0=ko
     */
    function write_file($fichinter, $outputlangs='') {
        $affZoneGauche = false;
        global $user, $langs, $conf, $mysoc;



        if (!is_object($outputlangs))
            $outputlangs = $langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("interventions");

        $outputlangs->setPhpLang();

        if ($conf->fichinter->dir_output) {
            // If $fichinter is id instead of object
            if (!is_object($fichinter)) {
                $id = $fichinter;
                $fichinter = new Fichinter($this->db);
                $result = $fichinter->fetch($id);
                if ($result < 0) {
                    dol_print_error($this->db, $fichinter->error);
                }
            }
            $fichinter->info($fichinter->id);
            $fichinter->fetch_extra();
            $fichinter->fetch_lines();

            $fichref = sanitize_string($fichinter->ref);
            $dir = $conf->fichinter->dir_output;
            if (!preg_match('/specimen/i', $fichref))
                $dir.= "/" . $fichref;
            $file = $dir . "/" . $fichref . ".pdf";

            if (!file_exists($dir)) {
                if (create_exdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Protection et encryption du pdf
                if ($conf->global->PDF_SECURITY_ENCRYPTION) {
                    $pdf = new FPDI_Protection('P', 'mm', $this->format);
                    $pdfrights = array('print'); // Ne permet que l'impression du document
                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
                    $pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass);
                } else {
                    $pdf = new FPDI('P', 'mm', $this->format);
                }

                $pdf->Open();
                $pdf->AddPage();
                $pdf->SetDrawColor(255, 255, 255);

                $intervHasFPR30 = false;
                $FPR30arr = array();
                $intervHasFPR40 = false;
                $FPR40arr = array();

                $isAvantVente = false;
                $isTempsPasse = false;
                $isSousGarantie = false;
                $isFormation = false;
                $totDeplacementHT = 0;
                $totFPR30 = 0;
                $totFPR40 = 0;
                $totTempsFPR30 = 0;
                $totTempsFPR40 = 0;
                $pu_fpr40 = 0;
                $pu_fpr30 = 0;
                $total_ht = 0;
                $total_tva = 0;
                $total_ttc = 0;
                $total_duree = 0;
                foreach ($fichinter->lignes as $key => $val) {
                    $total_ht += $val->total_ht;
                    $total_tva += $val->total_tva;
                    $total_ttc += $val->total_ttc;
                    $total_duree += $val->duration;
                    if ($val->fk_commandedet > 0) {
                        $requete = "SELECT ".MAIN_DB_PREFIX."product.ref
                                      FROM ".MAIN_DB_PREFIX."commandedet,
                                           ".MAIN_DB_PREFIX."product
                                     WHERE ".MAIN_DB_PREFIX."product.rowid = ".MAIN_DB_PREFIX."commandedet.fk_product
                                       AND ".MAIN_DB_PREFIX."commandedet.rowid =" . $val->fk_commandedet;
                        $sql1 = $this->db->query($requete);
                        $res1 = $this->db->fetch_object($sql1);
                        if ($res1->ref == 'FPR40') {
                            $intervHasFPR40 = true;
                            $FPR40arr[$key] = $val;
                            $totFPR40+=$val->total_ht;
                            $totTempsFPR40+=$val->duration;
                            $pu_fpr40 = $val->pu_ht;
                        } else if ($res1->ref == 'FPR30') {
                            $totTempsFPR30+=$val->duration;
                            $totFPR30+=$val->total_ht;
                            $intervHasFPR30 = true;
                            $FPR30arr[$key] = $val;
                            $isTempsPasse = true;
                            $pu_fpr30 = $val->pu_ht;
                        }
                    }
                    if ($val->fk_typeinterv == 8) {
                        $isSousGarantie = true;
                    }
                    if ($val->fk_typeinterv == 7) {
                        $isFormation = true;
                    }
                    if ($val->fk_typeinterv == 3) {
                        $isAvantVente = true;
                    }
                    if ($val->fk_typeinterv == 4) {
                        $totDeplacementHT+= $val->total_ht;
                    }
                }


                $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/core/modules/synopsisficheinter/RapportIntervBIMP2.pdf');
//
                $tplidx = $pdf->importPage(1, "/MediaBox");
                $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
//                $pdf->SetDrawColor(0,0,255);
//                for($i=0;$i<210;$i+=10){
//                    if ($i%50==0){
//                        $pdf->SetDrawColor(0,255,255);
//                    }
//                    $pdf->Line($i,0,$i,297);
//                    $pdf->SetDrawColor(0,0,255);
//                }
//                for($i=0;$i<297;$i+=10){
//                    if ($i%50==0){
//                        $pdf->SetDrawColor(0,255,255);
//                    }
//                    $pdf->Line(0,$i,210,$i);
//                    $pdf->SetDrawColor(0,0,255);
//                }
//
//                $pdf->SetDrawColor(255,255,255);
//                $pdf->SetLineWidth(0.1);
//
//                $pdf->SetDrawColor(0,255,0);
//                for($i=0;$i<210;$i+=1){
//                    if ($i%5==0){
//                        $pdf->SetDrawColor(255,0,255);
//                    }
//                    if ($i%10 != 0)
//                        $pdf->Line($i,0,$i,297);
//                    $pdf->SetDrawColor(0,255,0);
//                }
//                for($i=0;$i<297;$i+=1){
//                    if ($i%5==0){
//                        $pdf->SetDrawColor(255,0,255);
//                    }
//                    if ($i%10 != 0)
//                        $pdf->Line(0,$i,210,$i);
//                    $pdf->SetDrawColor(0,255,0);
//                }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(0, 0);
                $pdf->SetDrawColor(255, 255, 255);
                $pdf->SetFillColor(255, 255, 255);

                $pdf->SetDrawColor(0, 0, 0);
                $pdf->SetFillColor(0, 0, 0);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);

                $pdf->SetXY($this->marge_gauche + 3 + 0.7, 39);
                $pdf->MultiCell(32.8, 3.75, $fichinter->societe->nom, 0, 'L', 0);

//CUSTOMER / BILLING sinon
//Contact de la commande
                $requete = "SELECT *
                              FROM ".MAIN_DB_PREFIX."element_contact
                             WHERE fk_c_type_contact IN (130,131) AND element_id =  " . $fichinter->id;
                $sql1 = $this->db->query($requete);
                $arrContact = array();
                while ($res1 = $this->db->fetch_object($sql1)) {
                    $arrContact[$res1->fk_c_type_contact][] = $res1->fk_socpeople;
                }
                $requete = "SELECT *
                              FROM ".MAIN_DB_PREFIX."element_contact
                             WHERE fk_c_type_contact IN (101) AND element_id =  " . $fichinter->fk_commande;
                $sql1 = $this->db->query($requete);
                while ($res1 = $this->db->fetch_object($sql1)) {
                    $arrContact[$res1->fk_c_type_contact][] = $res1->fk_socpeople;
                }


                $contactFound = false;
                if (count($arrContact[130]) > 0) {
                    $contactFound = $arrContact[130][0];
                } else if (count($arrContact[131])) {
                    $contactFound = $arrContact[131][0];
                } else if (count($arrContact[101])) {
                    $contactFound = $arrContact[101][0];
                }
                $contactStr = "";
                if ($contactFound) {
                    require_once(DOL_DOCUMENT_ROOT . "/contact.class.php");
                    $contact = new Contact($this->db);
                    $contact->fetch($contactFound);
                    $contactStr = $contact->nom . " " . $contact->prenom;
                }
                $pdf->SetXY($this->marge_gauche + 3 + 0.9, 62.5);
                $pdf->MultiCell(32.6, 3.75, $contactStr, 0, 'L', 0);

                $pdf->SetXY($this->marge_gauche + 2 + 17, 70.9);
                $pdf->MultiCell(17.5, 4.4, $fichinter->societe->code_client, 1, 'C', 0);

                $pdf->SetXY($this->marge_gauche + 2 + 18.1, 85.6);
                $pu_ht = ($pu_fpr30 > 0 ? $pu_fpr30 : "");
                if ($pu_ht > 0)
                    $pu_ht = price($pu_ht);
                $pdf->MultiCell(16.6, 4.4, $pu_ht, 1, 'C', 0);
                $pdf->SetXY($this->marge_gauche + 2 + 18.1, 91.6);
                $totTpsPasse = ($totTempsFPR30 > 0 ? $totTempsFPR30 : "");
                if ($totTpsPasse > 0)
                    $totTpsPasse = $this->sec2time($totTpsPasse);
                elseif($affZoneGauche)
                    $totTpsPasse = $this->sec2time($total_duree);
                $totTpsPasse = str_replace("min", "m", $totTpsPasse);
                $pdf->MultiCell(16.6, 4.4, $totTpsPasse, 1, 'C', 0);
                $pdf->SetXY($this->marge_gauche + 2 + 18.1, 97.5);
                $totalMO = $total_ht - $totDeplacementHT;
                $totalMO = ($pu_fpr30 > 0 ? price($totalMO) : "");
                $pdf->MultiCell(16.6, 4.4, $totalMO, 1, 'C', 0);
                $pdf->SetXY($this->marge_gauche + 2 + 18.1, 103.5);
                $totDeplacementHT = ($pu_fpr30 > 0 ? price($totDeplacementHT) : "");

                $pdf->MultiCell(16.6, 4.4, $totDeplacementHT, 1, 'C', 0);
                $pdf->SetXY($this->marge_gauche + 2 + 18.1, 109.4);
                $totalPiece = "";
                if ($fichinter->extraArr[$this->totalPieceIdx] > 0 && $pu_fpr30 > 0) {
                    $totalPiece = price($fichinter->extraArr[$this->totalPieceIdx]);
                }

                $pdf->MultiCell(16.6, 4.4, $totalPiece, 1, 'C', 0);

                $puUrgent = ($pu_fpr40 > 0 ? $pu_fpr40 : "");
                if ($puUrgent > 0

                    )$puUrgent = price($puUrgent);
                $pdf->SetXY($this->marge_gauche + 2 + 18.1, 115.4);
                $pdf->MultiCell(16.6, 4.4, $puUrgent, 1, 'C', 0);


                $pdf->SetXY($this->marge_gauche + 3.2, 126);
                $pdf->MultiCell(33.6, 4.7, $fichinter->extraArr[$this->recupDataIdx], 1, 'C', 0);


                if ($pu_fpr40 > 0 || $pu_fpr30 > 0 || $affZoneGauche) {
                    $pdf->SetXY($this->marge_gauche + 2 + 18, 133);
                    $pdf->MultiCell(16.9, 4.5, price($total_ht + $fichinter->extraArr[$this->totalPieceIdx]), 1, 'C', 0);
                    $pdf->SetXY($this->marge_gauche + 2 + 18, 139);
                    $pdf->MultiCell(16.9, 4.5, price($total_tva + ($fichinter->extraArr[$this->totalPieceIdx] * 0.196)), 1, 'C', 0);
                    $pdf->SetXY($this->marge_gauche + 2 + 18, 145);
                    $pdf->MultiCell(16.9, 4.5, price($total_ttc + ($fichinter->extraArr[$this->totalPieceIdx] * 1.196)), 1, 'C', 0);

                    $pdf->SetXY($this->marge_gauche + 2 + 18, 153);
                    $pdf->MultiCell(16.9, 4.5, $fichinter->extraArr[$this->totalBonIdx], 1, 'C', 0);

                    $pdf->SetXY($this->marge_gauche + 2 + 18, 158.8);
                    $pdf->MultiCell(16.9, 6.1, $fichinter->extraArr[$this->bonRemisIdx], 0, 'C', 0);
                }


                $pdf->SetXY($this->marge_gauche + 2 + 2.1, 192);
                $pdf->MultiCell(31.9, 4.1, $fichinter->extraArr[$this->dateNextRdvIdx], 1, 'C', 0);

                $pdf->SetXY($this->marge_gauche + 2 + 2.45, 208);
                $pdf->MultiCell(31.50, 4, $fichinter->extraArr[$this->attenteClientIdx], 0, 'C', 0);

                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
                $pdf->SetXY(69.5, 15.8);
                $tmpName = $fichinter->user_creation->prenom . " " . $fichinter->user_creation->nom;
                $pdf->MultiCell(90, 4, $tmpName, 0, 'L', 0);


                $pdf->SetXY(166, 10.95);
                $pdf->MultiCell(35.5, 5.8, $fichinter->ref, 1, 'C', 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);

                $req = "SELECT * FROM ".MAIN_DB_PREFIX."commande where ROWID = " . $fichinter->fk_commande;
                $sql = $this->db->query($req);
                $res99 = $this->db->fetch_object($sql);

                $pdf->SetXY(166, 21.2);
                $pdf->MultiCell(35.5, 6.4, $res99->ref, 1, 'C', 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);

                $pdf->SetXY(61.5, 21.2);
                $pdf->MultiCell(25.3, 4.2, date('d/m/Y', $fichinter->date), 0, 'C', 0);

                $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 24);
                $pdf->SetTextColor(245, 93, 0);
                $pdf->SetXY(99.3, 41);
                $pdf->MultiCell(102, 6, strtoupper($fichinter->ref), 0, 'L', 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 6.5);
                $pdf->SetTextColor(0, 0, 0);

                $pdf->SetXY(9, 260);
                $pdf->MultiCell(33, 4, $fichinter->extraArr[$this->propContratIdx], 0, "L", 0);

                $heureAMdep = $fichinter->extraArr[$this->dateDebAMIdx];
                $heurePMdep = $fichinter->extraArr[$this->dateDebPMIdx];
                $heureAMarr = $fichinter->extraArr[$this->dateFinAMIdx];
                $heurePMarr = $fichinter->extraArr[$this->dateFinPMIdx];


                $pdf->SetXY(63, 54.57);
                $pdf->MultiCell(8.5, 2.5, $heureAMarr, 0, 'C', 0);

                $pdf->SetXY(82.8, 54.57);
                $pdf->MultiCell(8.5, 2.5, $heureAMdep, 0, 'C', 0);

                $pdf->SetXY(63, 64.12);
                $pdf->MultiCell(8.5, 2.5, $heurePMarr, 0, 'C', 0);

                $pdf->SetXY(82.8, 64.12);
                $pdf->MultiCell(8.5, 2.5, $heurePMdep, 0, 'C', 0);

                $longtext = "";
                $tmpArr = array();
                $atLeastOneDescError = false;
                $atLeastOneTimeError = false;
                foreach ($fichinter->lignes as $key => $val) {
//                     $tmpArr[]=$this->sec2time($val->duration)." - ".$val->desc;

                    if ($val->fk_contratdet > 0) {
                        $requete = "SELECT description FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = " . $val->fk_contratdet;
                        $sql = $this->db->query($requete);
                        $res = $this->db->fetch_object($sql);
                        $tmpdesc = ($res->description . "x" != "x" ? $res->description . " :\n" . $val->desc : $val->desc);
                        $tmpArr[] = utf8_decode(utf8_encode($tmpdesc));
                    } else {
                        $tmpArr[] = utf8_decode(utf8_encode($val->desc));
                    }
                    if (!$val->duration > 0) {
                        $atLeastOneTimeError = true;
                    }
                    if ("x" . $val->desc == "x") {
                        $atLeastOneTimeError = true;
                    }
                }
                if (count($fichinter->lignes) == 0) {
                    $atLeastOneDescError = true;
                }
                if ($atLeastOneDescError || $atLeastOneTimeError) {
                    $pdf->SetXY(30, 3);
                    $pdf->SetFont(pdf_getPDFFont($outputlangs), 'b', 12);
                    $pdf->SetTextColor(255, 0, 0);
                    $pdf->MultiCell(187, 4, utf8_decode("Merci de saisir le champs descriptifs de l'intervention et durée de l'intervention"), 0, 'L', 0);
                }

                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);





//x 35.9 y 174.9
//x1 42 y1 180.8

                if ($fichinter->extraArr[$this->isIntervTermineIdx] == 1) {
                    //termine
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 35.8, 174.7, 6.2, 6.2);
                } else {
                    //en cours
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 35.8, 181.7, 6.2, 6.2);
                }

                //Attente client
                if ($fichinter->extraArr[$this->attenteClientIdx] . "x" != "x") {
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 36.2, 197.6, 6.1, 6);
                }
                if ($fichinter->extraArr[$this->miseEnRelationIdx] == 'Direction Technique') {
                    //Dir Tech
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 37.5, 241.7, 4.8, 4.3);
                }

                if ($fichinter->extraArr[$this->miseEnRelationIdx] == 'Service Commercial') {
                    //Service Commercial
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 37.5, 246.6, 4.8, 4.3);
                }


                if ($fichinter->extraArr[$this->isInstallationIdx] == 1) {
                    //Installation
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 92, 20.7, 5, 5.1);
                }

                if ($fichinter->extraArr[$this->isInterventionIdx] == 1) {
                    //Instervention
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 122.3, 20.7, 5, 5.1);
                }

                //Temps passé
                if ($isTempsPasse)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 50.2, 31.6, 4.9, 4.9);

                //Forfait
                $isForfait = false;
                if ($fichinter->extraArr[$this->isForfaitIdx] == 1) {
                    $isForfait = true;
                }

                if ($isForfait)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 82.8, 31.6, 4.9, 4.9);

                //ss garantie
                if ($isSousGarantie)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 105.8, 31.6, 4.9, 4.9);

                //Formation
                if ($isFormation)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 139.5, 31.6, 4.9, 4.9);

                //Avant vente
                if ($isAvantVente)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 169.4, 31.6, 4.9, 4.9);


                if ($fichinter->extraArr[$this->recontactComIdx] == 1) {
                    //Contact client
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 118, 254, 4.5, 4.5);
                }

                //non oui moyen
                //Non
                if ($fichinter->extraArr[$this->techALheureIdx] == "Non")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 135.25, 267.5, 4, 4);
                if ($fichinter->extraArr[$this->infoTacheDurIdx] == "Non")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 135.25, 273.7, 4, 4);
                if ($fichinter->extraArr[$this->satisfactionIdx] == "Non")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 135.25, 279.5, 4, 4);

                //Moyen
                if ($fichinter->extraArr[$this->techALheureIdx] == "Moyen")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 143.75, 267.5, 4, 4);
                if ($fichinter->extraArr[$this->infoTacheDurIdx] == "Moyen")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 143.75, 273.7, 4, 4);
                if ($fichinter->extraArr[$this->satisfactionIdx] == "Moyen")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 143.75, 279.5, 4, 4);

                //Oui
                if ($fichinter->extraArr[$this->techALheureIdx] == "Oui")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 151.9, 267.5, 4, 4);
                if ($fichinter->extraArr[$this->infoTacheDurIdx] == "Oui")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 151.9, 273.7, 4, 4);
                if ($fichinter->extraArr[$this->satisfactionIdx] == "Oui")
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/cocherSansCase.png", 151.9, 279.5, 4, 4);



//                $this->_pagefoot($pdf,$outputlangs);







                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);

                /* a virer */
                /* $tmp = $tmpArr;
                  foreach ($tmp as $cle => $val)
                  $tmpArr[] = $val;
                  foreach ($tmp as $cle => $val)
                  $tmpArr[] = $val;
                  foreach ($tmp as $cle => $val)
                  $tmpArr[] = $val; */
                /**/

                $tmp2 = $tmpArr;
                $tmpArr = array();
                foreach ($tmp2 as $val) {
                    $tab = explode("\n", $val);
                    foreach ($tab as $val2)
                        $tmpArr[] = $val2;
                }

                $i = 0;
                $newArr = array();
                $diffTaillePage = 27;
                $tailleMax = 50;
                foreach ($tmpArr as $cle => $ligneStr) {
                    $i++;
                    if ($i > $tailleMax || ($i > ($tailleMax - $diffTaillePage) && count($tmpArr) <= ($cle - $i + $diffTaillePage))) {
                        $tplidx = $pdf->importPage(3, "/MediaBox");
                        $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                        $longText = join("\n", $newArr);
                        $pdf->SetXY(50, 76);
                        $pdf->MultiCell(347, 4, $longText, 0, 'L', 0);
                        $pdf->AddPage();
                        $tplidx = $pdf->importPage(1, "/MediaBox");
                        $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                        $newArr = array();
                        $i = 0;
                    }
                    $newArr[] = $tmpArr[$cle];
                }

//                $tplidx = $pdf->importPage(1, "/MediaBox");
//                $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                $tplidx = $pdf->importPage(2, "/MediaBox");
                $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                $longText = join("\n", $newArr);
                $pdf->SetXY(50, 76);
                $pdf->MultiCell(147, 4, $longText, 0, 'L', 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);



                $precoText = $fichinter->extraArr[$this->precoIdx];
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect(47, 230, 154, 21, "F");
                $pdf->SetFillColor(0, 0, 0);

                $pdf->SetXY(50, 200);
                $pdf->MultiCell(147, 4, $precoText, 0, 'L', 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);

                $precoText = $fichinter->extraArr[$this->remarqueCliIdx];
                $pdf->SetXY(50, 229);
                $pdf->MultiCell(147, 4, $precoText, 0, 'L', 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);



                $pdf->AliasNbPages();

                $pdf->Close();

                $pdf->Output($file);

                $langs->setPhpLang();    // On restaure langue session
                return 1;
            } else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "FICHEINTER_OUTPUTDIR");
            return 0;
        }
        $this->error = $langs->trans("ErrorUnknown");
        return 0;   // Erreur par defaut
    }

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */

    function _pagefoot(&$pdf, $outputlangs) {
        return pdf_pagefoot($pdf, $outputlangs, 'FICHEINTER_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur);
    }

    function sec2time($sec) {
        $returnstring = " ";
        $days = intval($sec / 86400);
        $hours = intval(($sec / 3600) - ($days * 24));
        $minutes = intval(($sec - (($days * 86400) + ($hours * 3600))) / 60);
        $seconds = $sec - ( ($days * 86400) + ($hours * 3600) + ($minutes * 60));

        $returnstring .= ( $days) ? (($days == 1) ? "1 j" : $days . "j") : "";
        $returnstring .= ( $days && $hours && !$minutes && !$seconds) ? "" : "";
        $returnstring .= ( $hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
        $returnstring .= ( ($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
        $returnstring .= ( $minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . " min") : "";
        //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
        //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
        return ($returnstring);
    }

}

?>
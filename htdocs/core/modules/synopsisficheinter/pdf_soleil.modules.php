<?php

require_once(DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');

class pdf_soleil extends ModeleSynopsisficheinter {

    /**
      \brief      Constructeur
      \param        db        Handler acces base de donnees
     */
    function pdf_soleil($db = 0) {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = 'pluton';
        $this->description = "Modele de fiche d'intervention standard";

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 3;
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
    function addPage($pdf) {
        //Numéro de page
        $this->footer($pdf);
        $pdf->AddPage();
    }

    function footer($pdf) {
        if ($pdf->PageNo() > 0) {
            $pdf->SetXY(-5, -10);
            $pdf->Cell(0, 10, $pdf->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }

    function write_file($fichinter, $outputlangs = '') {
        $affZoneGauche = false;
        global $user, $langs, $conf, $mysoc;



        if (!is_object($outputlangs))
            $outputlangs = $langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("interventions");

//        $outputlangs->setPhpLang();
        if ($conf->synopsisficheinter->dir_output) {
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
            $dir = $conf->synopsisficheinter->dir_output;
            if (!preg_match('/specimen/i', $fichref))
                $dir.= "/" . $fichref;
            $file = $dir . "/" . $fichref . ".pdf";

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                $pdf = pdf_getInstance($this->format);
                // Protection et encryption du pdf
//                if ($conf->global->PDF_SECURITY_ENCRYPTION) {
//                    $pdf = new FPDI_Protection('P', 'mm', $this->format);
//                    $pdfrights = array('print'); // Ne permet que l'impression du document
//                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
//                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
//                    $pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass);
//                } else {
//                    $pdf = new FPDI('P', 'mm', $this->format);
//                }

                $pdf->Open();
                $this->AddPage($pdf);
                $pdf->SetDrawColor(255, 255, 255);

                $intervHasFPR30 = false;
                $FPR30arr = array();
                $intervHasFPR40 = false;
                $FPR40arr = array();

                $isAvantVente = false;
                $isTempsPasse = false;
                $isSousGarantie = false;
                $isFormation = false;
                $isTelemaint = false;
                $isContrat = false;
                $totDeplacementHT = 0;
                $totDeplacementTva = 0;
                $totFPR30 = 0;
                $totFPR40 = 0;
                $totFPR30Tva = 0;
                $totFPR40Tva = 0;
                $totTempsFPR30 = 0;
                $totTempsFPR40 = 0;
                $pu_fpr40 = 0;
                $pu_fpr30 = 0;
                $total_ht = 0;
                $total_tva = 0;
                $total_ttc = 0;
                $total_duree = 0;


                if ($fichinter->fk_contrat) {
                    $isContrat = true;
                }
                foreach ($fichinter->lignes as $key => $val) {
                    $total_ht += $val->total_ht;
                    $total_tva += $val->total_tva;
                    $total_ttc += $val->total_ttc;
                    $total_duree += $val->duration;
                    if ($val->fk_commandedet > 0) {
                        $requete = "SELECT llx_product.ref
                                      FROM llx_commandedet,
                                           llx_product
                                     WHERE llx_product.rowid = llx_commandedet.fk_product
                                       AND llx_commandedet.rowid =" . $val->fk_commandedet;
                        $sql1 = $this->db->query($requete);
                        $res1 = $this->db->fetch_object($sql1);
                        if ($res1->ref == 'FPR40') {
                            $intervHasFPR40 = true;
                            $FPR40arr[$key] = $val;
                            $totFPR40+=$val->total_ht;
                            $totFPR40Tva+=$val->total_ht * $val->total_tva;
                            $totTempsFPR40+=$val->duration;
                            $pu_fpr40 = $val->pu_ht;
                        } else if ($res1->ref == 'FPR30') {
                            $totTempsFPR30+=$val->duration;
                            $totFPR30+=$val->total_ht;
                            $totFPR30Tva+=$val->total_tva;
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
                    if ($val->fk_typeinterv == 13) {
                        $isTelemaint = true;
                    }
                    if ($val->fk_typeinterv == 4) {
                        $totDeplacementHT+= $val->total_ht;
                        $totDeplacementTva+= $val->total_tva;
                    }
                }


                $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/core/modules/synopsisficheinter/RapportIntervBIMP11.pdf');
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

                $pdf->SetXY($this->marge_gauche + 3.4 + 0.7, 27.5);
                $pdf->MultiCell(32.8, 3.75, $fichinter->societe->nom, 0, 'L', 0);

//CUSTOMER / BILLING sinon
//Contact de la commande
                $requete = "SELECT *
                              FROM llx_element_contact
                             WHERE fk_c_type_contact IN (130,131) AND element_id =  " . $fichinter->id;
                $sql1 = $this->db->query($requete);
                $arrContact = array();
                while ($res1 = $this->db->fetch_object($sql1)) {
                    $arrContact[$res1->fk_c_type_contact][] = $res1->fk_socpeople;
                }
                if (isset($fichinter->fk_commande) && $fichinter->fk_commande > 0) {
                    $requete = "SELECT *
                              FROM llx_element_contact
                             WHERE fk_c_type_contact IN (101) AND element_id =  " . $fichinter->fk_commande;
                    $sql1 = $this->db->query($requete);
                    while ($res1 = $this->db->fetch_object($sql1)) {
                        $arrContact[$res1->fk_c_type_contact][] = $res1->fk_socpeople;
                    }
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
                    require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
                    $contact = new Contact($this->db);
                    $contact->fetch($contactFound);
                    $contactStr = $contact->nom . " " . $contact->prenom;
                }
                $pdf->SetXY($this->marge_gauche + 3.4 + 0.9, 47);
                $pdf->MultiCell(32.6, 3.75, $contactStr, 0, 'L', 0);

                $pdf->SetXY($this->marge_gauche + 1 + 17, 53.5);
                $pdf->MultiCell(17.5, 4.4, $fichinter->societe->code_client, 0, 'C', 0);

                $pdf->SetXY($this->marge_gauche + 2 + 17.2, 83);
                $pu_ht = ($pu_fpr30 > 0 ? $pu_fpr30 : "");
                if ($pu_ht > 0)
                    $pu_ht = price($pu_ht);
                $pdf->MultiCell(16.6, 4.4, $pu_ht, 0, 'C', 0);
                $pdf->SetXY($this->marge_gauche + 2 + 17.2, 89);
                $totTpsPasse = ($totTempsFPR30 > 0 ? $totTempsFPR30 : "");
                if ($totTpsPasse > 0)
                    $totTpsPasse = $this->sec2time($totTpsPasse);
                elseif ($affZoneGauche)
                    $totTpsPasse = $this->sec2time($total_duree);
                $totTpsPasse = str_replace("min", "m", $totTpsPasse);
                $pdf->MultiCell(16.6, 4.4, $totTpsPasse, 0, 'C', 0);
                $pdf->SetXY($this->marge_gauche + 2 + 17.2, 94);
                $totalMO = $total_ht - $totDeplacementHT;
                $totalMO = ($pu_fpr30 > 0 ? price($totalMO) : "");
                //Mod tysauron
                $totalMO = $totFPR30;
                if ($totalMO == "0")
                    $totalMO = '';
                $totalMOTva = $totFPR30Tva;
                //F mod
                if ($totalMO > 0)
                    $pdf->MultiCell(16.6, 4.4, price($totalMO), 0, 'C', 0);
                $pdf->SetXY($this->marge_gauche + 2 + 17.2, 99);
                $totDeplacementHT = ($pu_fpr30 > 0 ? price($totDeplacementHT) : "");

                $pdf->MultiCell(16.6, 4.4, $totDeplacementHT, 0, 'C', 0);
                $pdf->SetXY($this->marge_gauche + 2 + 17.2, 104);
                $totalPiece = "";
                if ($fichinter->extraArr[$this->totalPieceIdx] > 0 && $pu_fpr30 > 0) {
                    $totalPiece = price($fichinter->extraArr[$this->totalPieceIdx]);
                }

                $pdf->MultiCell(16.6, 4.4, $totalPiece, 0, 'C', 0);

                $puUrgent = ($pu_fpr40 > 0 ? $pu_fpr40 : "");
                if ($puUrgent > 0)
                    $puUrgent = price($puUrgent);
                $pdf->SetXY($this->marge_gauche + 2 + 17.2, 109);
                $pdf->MultiCell(16.6, 4.4, $puUrgent, 0, 'C', 0);


                $pdf->SetXY($this->marge_gauche + 3.2, 117.5);
                $pdf->MultiCell(33.6, 4.7, $fichinter->extraArr[$this->recupDataIdx], 0, 'C', 0);


                if ($pu_fpr40 > 0 || $pu_fpr30 > 0 || $affZoneGauche) {
                    //Deb mod tysauron
//                    $pdf->MultiCell(16.9, 4.5, price($total_ht + $fichinter->extraArr[$this->totalPieceIdx]), 1, 'C', 0);
//                    $pdf->SetXY($this->marge_gauche + 2 + 17.2, 139);
//                    $pdf->MultiCell(16.9, 4.5, price($total_tva + ($fichinter->extraArr[$this->totalPieceIdx] * 0.196)), 1, 'C', 0);
//                    $pdf->SetXY($this->marge_gauche + 2 + 17.2, 145);
//                    $pdf->MultiCell(16.9, 4.5, price($total_ttc + ($fichinter->extraArr[$this->totalPieceIdx] * 1.196)), 1, 'C', 0);
                    $totHt = $totalMO + $totDeplacementHT + $puUrgent + $totalPiece;
                    $totTva = $totalMOTva + $totDeplacementTva + $totFPR40Tva + ($fichinter->extraArr[$this->totalPieceIdx] * 0.196);
                    $totTtc = $totHt + $totTva;
                    $pdf->SetXY($this->marge_gauche + 2 + 17.2, 121.5);
                    $pdf->MultiCell(16.9, 4.5, price($totHt), 0, 'C', 0);
                    $pdf->SetXY($this->marge_gauche + 2 + 17.2, 126.1);
                    $pdf->MultiCell(16.9, 4.5, price($totTva), 0, 'C', 0);
                    $pdf->SetXY($this->marge_gauche + 2 + 17.2, 130.5);
                    $pdf->MultiCell(16.9, 4.5, price($totTtc), 0, 'C', 0);
                    //f mod

                    $pdf->SetXY($this->marge_gauche + 2 + 17.2, 136.8);
                    $pdf->MultiCell(16.9, 4.5, price($fichinter->extraArr[$this->totalBonIdx]), 0, 'C', 0);

                    $pdf->SetXY($this->marge_gauche + 2 + 17.2, 140.7);
                    $pdf->MultiCell(16.9, 6.1, price($fichinter->extraArr[$this->bonRemisIdx]), 0, 'C', 0);
                }

                if ($fichinter->extraArr[$this->isIntervTermineIdx] != 1) {
                    $pdf->SetXY($this->marge_gauche + 3, 168);
                    $pdf->MultiCell(31.9, 4.1, $fichinter->extraArr[$this->dateNextRdvIdx], 0, 'C', 0);
                }

                $pdf->SetXY($this->marge_gauche + 2.5 + 2, 179.7);
                $pdf->MultiCell(31.50, 4, $fichinter->extraArr[$this->attenteClientIdx], 0, 'C', 0);

                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
                $pdf->SetXY(67.5, 11.5);
                $tmpName = $fichinter->user_creation->prenom . " " . $fichinter->user_creation->nom;
                $pdf->MultiCell(90, 4, $tmpName, 0, 'L', 0);


//                $pdf->SetXY(166, 10.95);
//                $pdf->MultiCell(35.5, 5.8, $fichinter->ref, 1, 'C', 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 10);

                if (isset($fichinter->fk_commande) && $fichinter->fk_commande > 0) {
                    $req = "SELECT * FROM llx_commande where rowid = " . $fichinter->fk_commande;
                    $sql = $this->db->query($req);
                    $res99 = $this->db->fetch_object($sql);

                    $pdf->SetXY(146, 14.1);
                    $pdf->MultiCell(35.5, 4, $res99->ref, 0, 'C', 0);
                    $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
                }

                if (isset($fichinter->fk_contrat) && $fichinter->fk_contrat > 0) {
                    $req = "SELECT * FROM llx_contrat where rowid = " . $fichinter->fk_contrat;
                    $sql = $this->db->query($req);
                    $res99 = $this->db->fetch_object($sql);
                    $pdf->SetXY(174.3, 14.1);
                    $pdf->MultiCell(32, 4, str_replace("CTR-", "", $res99->ref), 0, 'C', 0);
                    $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
                }

//               $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 10);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 20);
                $pdf->SetTextColor(245, 93, 0);
                $pdf->SetXY(159, 27.8);
                $pdf->MultiCell(50, 5, date('d/m/Y', $fichinter->date), 0, 'C', 0);

                $pdf->SetXY(92, 27.5);
                $pdf->MultiCell(102, 5, strtoupper($fichinter->ref), 0, 'L', 0);

                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 6.5);
                $pdf->SetTextColor(0, 0, 0);

//                $pdf->SetXY(9, 260);
//                $pdf->MultiCell(33, 4, $fichinter->extraArr[$this->propContratIdx], 1, "L", 0);

                $heureAMdep = $fichinter->extraArr[$this->dateDebAMIdx];
                $heurePMdep = $fichinter->extraArr[$this->dateDebPMIdx];
                $heureAMarr = $fichinter->extraArr[$this->dateFinAMIdx];
                $heurePMarr = $fichinter->extraArr[$this->dateFinPMIdx];


                $pdf->SetXY(19, 67);
                $pdf->MultiCell(20.5, 2.5, utf8_decode($heureAMarr . " à " . $heureAMdep), 0, 'C', 0);

//                $pdf->SetXY(82.8, 54.57);
//                $pdf->MultiCell(8.5, 2.5, $heureAMdep, 0, 'C', 0);

                $pdf->SetXY(19, 72);
                $pdf->MultiCell(20.5, 2.5, utf8_decode($heurePMarr . " à " . $heurePMdep), 0, 'C', 0);

//                $pdf->SetXY(82.8, 64.12);
//                $pdf->MultiCell(8.5, 2.5, , 0, 'C', 0);

                $longtext = "";
                $tmpArr = array();
                $atLeastOneDescError = false;
                $atLeastOneTimeError = false;
                foreach ($fichinter->lignes as $key => $val) {
//                     $tmpArr[]=$this->sec2time($val->duration)." - ".$val->desc;

                    if ($val->fk_contratdet > 0) {
                        $requete = "SELECT description FROM llx_contratdet WHERE rowid = " . $val->fk_contratdet;
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
                if (($atLeastOneDescError || $atLeastOneTimeError) && $fichinter->extraArr[$this->attenteClientIdx] == '') {
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
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 21.3, 155.4, 3.6, 3.6);
                } else {
                    //en cours
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 21.3, 160.3, 3.6, 3.6);
                }

                //Attente client
                if ($fichinter->extraArr[$this->attenteClientIdx] . "x" != "x") {
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 26.7, 173, 3.6, 3.6);
                }
                /*  if ($fichinter->extraArr[$this->miseEnRelationIdx] == 'Direction Technique') {
                  //Dir Tech
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 37.5, 241.7, 4.8, 4.3);
                  }

                  if ($fichinter->extraArr[$this->miseEnRelationIdx] == 'Service Commercial') {
                  //Service Commercial
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 37.5, 246.6, 4.8, 4.3);
                  }
                 */
                /*
                  //Installation
                  if ($fichinter->extraArr[$this->isInstallationIdx] == 1)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 83.3, 35.5, 3.6, 3.6);

                  //Instervention
                  if ($fichinter->extraArr[$this->isInterventionIdx] == 1)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 107.8, 35.5, 3.6, 3.6);

                  //Temps passé
                  if ($isTempsPasse)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 92.1, 19.6, 4, 4);

                  //Forfait
                  $isForfait = false;
                  if ($fichinter->extraArr[$this->isForfaitIdx] == 1) {
                  $isForfait = true;
                  }

                  if ($isForfait)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 128.7, 19.6, 4, 4);

                  //ss garantie
                  if ($isSousGarantie)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 150.7, 19.6, 4, 4);

                  //Formation
                  if ($isFormation)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 168, 35.5, 3.6, 3.6);

                  //Avant vente
                  if ($isAvantVente)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 191.3, 35.5, 3.6, 3.6);

                  //Contrat
                  if ($isContrat)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 177.3, 19.6, 4, 4);

                  //Telemaintenance
                  if ($isTelemaint)
                  $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 133.1, 35.5, 3.6, 3.6);
                 */

                $type = $fichinter->extraArr[35];
                if ($type == 3)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 92, 20.6, 4, 4);
                if ($type == 1)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 117.4, 20.6, 4, 4);
                if ($type == 2)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 139.3, 20.6, 4, 4);
                if ($type == 4)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 165.2, 20.6, 4, 4);





                $natureI = $fichinter->natureInter;
                //Contrat
                if ($natureI == 1)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 42.1, 35.5, 3.6, 3.6);

                elseif ($natureI == 2)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 71.15, 35.5, 3.6, 3.6);

                elseif ($natureI == 3)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 101.75, 35.5, 3.6, 3.6);

                elseif ($natureI == 3)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 141.85, 35.5, 3.6, 3.6);

                elseif ($natureI == 6)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 171.9, 35.5, 3.6, 3.6);
                elseif ($natureI == 5)
                    $pdf->Image(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/caseCocher.png", 191.4, 35.5, 3.6, 3.6);



                /*
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
                 */


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

                $i = $j = 0;
                $newArr = array();
                $diffTaillePage = 26; //23
                $tailleMax = 58;
                $pageVide = false;
                foreach ($tmpArr as $cle => $ligneStr) {
                    $i++;
//                    $j = 0;
                    $nbCarac = strlen($ligneStr);
                    $caracMac = 100;
                    for (; $nbCarac > $caracMac; $nbCarac = $nbCarac - $caracMac)
                        $j++;
                    $ligneApr = $i + $j;
//                    if ($j > $tailleMax)
//                        die("Merci de sauter des lignes dans les descriptions.");
                    if ($ligneApr > $tailleMax) {
//                        $tplidx = $pdf->importPage(3, "/MediaBox");
//                        $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);

                        $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/core/modules/synopsisficheinter/RapportIntervBIMP12.pdf');
                        $tplidx = $pdf->importPage(1, "/MediaBox");
                        $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);

                        $this->affDesc($pdf, $newArr,$outputlangs);
                        $this->AddPage($pdf);
//                        $tplidx = $pdf->importPage(1, "/MediaBox");
//                        $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                        $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/core/modules/synopsisficheinter/RapportIntervBIMP11.pdf');
                        $tplidx = $pdf->importPage(1, "/MediaBox");
                        $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                        $newArr = array();
                        $i = $j = 0;
                    }
                    if ($ligneApr > ($tailleMax - $diffTaillePage) && !isset($tmpArr[$cle - $ligneApr + $tailleMax + 1]))
                        $pageVide = true;
                    $newArr[] = $ligneStr;
                }

                //Pour la derniére page
                if ($pageVide) {
//                    $tplidx = $pdf->importPage(3, "/MediaBox");
//                    $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                    $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/core/modules/synopsisficheinter/RapportIntervBIMP12.pdf');
                    
                    $tplidx = $pdf->importPage(1, "/MediaBox");
                    $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                    $this->affDesc($pdf, $newArr,$outputlangs);
                    $this->AddPage($pdf);
//                    $tplidx = $pdf->importPage(1, "/MediaBox");
//                    $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
//                    $tplidx = $pdf->importPage(2, "/MediaBox");
//                    $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                    $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/core/modules/synopsisficheinter/RapportIntervBIMP11.pdf');
                    $tplidx = $pdf->importPage(1, "/MediaBox");
                    $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
                } else {
                    /* $tplidx = $pdf->importPage(2, "/MediaBox");
                      $pdf->useTemplate($tplidx, 0, 0, 0, 0, true); */
                    $this->affDesc($pdf, $newArr, $outputlangs);
                }

//                $tplidx = $pdf->importPage(1, "/MediaBox");
//                $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);



                $precoText = $fichinter->extraArr[$this->precoIdx];
//                $pdf->SetFillColor(255, 255, 255);
//                $pdf->Rect(47, 230, 154, 21, "F");
                $pdf->SetFillColor(0, 0, 0);

                $pdf->SetXY(47, 204);
                $pdf->MultiCell(147, 4, $precoText, 0, 'L', 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);

                /* $precoText = $fichinter->extraArr[$this->remarqueCliIdx];
                  $pdf->SetXY(50, 229);
                  $pdf->MultiCell(147, 4, $precoText, 0, 'L', 0);
                  $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8); */


                $this->footer($pdf);
                $pdf->AliasNbPages();

                $pdf->Close();

                $pdf->Output($file, 'f');

//                $langs->setPhpLang();    // On restaure langue session
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

    function affDesc($pdf, $newArr, $outputlangs) {
        $longText = join("\n", $newArr);
        $pdf->SetXY(45, 53);
        $pdf->MultiCell(147, 4, stripslashes($longText), 0, 'L', 0);
        $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
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

<?php

/*

  /*
 * or see http://www.gnu.org/
 */

/**
  \file       htdocs/includes/modules/contrat/pdf_contrat_babel.modules.php
  \ingroup    contrat
  \brief      Fichier de la classe permettant de generer les contrats au modele BIMP
  \author     Christian CONSTANTIN-BERTIN
  \version    $Id: pdf_contrat_bimp.modules.php,v 1.121 2011/08/07  $
 */
require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';


/**
  \class      pdf_contrat_babel
  \brief      Classe permettant de generer les contrats au modele babel
 */
if (!defined('EURO'))
    define('EURO', chr(128));

ini_set('max_execution_time', 600);

class pdf_contrat_BIMP extends ModeleSynopsiscontrat {

    public $emetteur;    // Objet societe qui emet

    /**
      \brief      Constructeur
      \param        db        Handler acces base de donnee
     */

    function pdf_contrat_BIMP($db) {

        global $conf, $langs, $mysoc;

        $langs->load("main");
        $langs->load("bills");
        $this->debug = "";
        $this->db = $db;
        $this->name = "babel";
        $this->description = $langs->trans('PDFContratbabelDescription');

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 49;
        $this->marge_droite = 7;
        $this->marge_haute = 39;
        $this->marge_basse = 22;

        $this->option_logo = 1;                    // Affiche logo
        // Recupere emmetteur
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);    // Par defaut, si n'etait pas defini









            
// Defini position des colonnes
        $this->posxdesc = $this->marge_gauche + 1;
        $this->posxtva = 113;
        $this->posxup = 126;
        $this->posxqty = 145;
        $this->posxdiscount = 162;
        $this->postotalht = 174;
    }

    /**
      \brief      Fonction generant la contrat sur le disque
      \param        contrat            Objet contrat a generer (ou id si ancienne methode)
      \param        outputlangs        Lang object for output language
      \return        int             1=ok, 0=ko
     */
    function write_file($contrat, $outputlangs = '') {
        global $user, $langs, $conf;

        if (!is_object($outputlangs))
            $outputlangs = $langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("contrat");
        $outputlangs->load("products");
        //$outputlangs->setPhpLang();
        if ($conf->synopsiscontrat->dir_output) {
            // Definition de l'objet $contrat (pour compatibilite ascendante)
            if (!is_object($contrat)) {
                $id = $contrat;
                require_once(DOL_DOCUMENT_ROOT . "/Babel_GMAO/contratMixte.class.php");
                $contrat = getContratObj($id);
                $contrat->fetch($id);
                $contrat->fetch_lines(true);
//                $contrat = new ContratMixte($this->db);
//                $ret=$contrat->fetch($id);
            } else {
                $contrat->fetch_lines(true);
            }

            // Definition de $dir et $file
            if ($contrat->specimen) {
                $dir = $conf->synopsiscontrat->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contrat->ref);
                $dir = $conf->synopsiscontrat->dir_output . "/" . $propref;
                $file = $dir . "/" . $propref . ".pdf";
            }
            $this->contrat = $contrat;

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                $pdf = "";
                $nblignes = sizeof($contrat->lines);
//                // Protection et encryption du pdf
//                if ($conf->global->PDF_SECURITY_ENCRYPTION) {
//                    $pdf = new FPDI_Protection('P', 'mm', $this->format);
//                    $pdfrights = array('print'); // Ne permet que l'impression du document
//                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
//                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
//                    $pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass);
//                } else {
//
//                    $pdf = new FPDI('P', 'mm', $this->format);
//                }
                $pdf = pdf_getInstance($this->format);

                $pdf1 = pdf_getInstance($this->format);
//                $pdf1 = new FPDI('P', 'mm', $this->format);

                $requete = "SELECT *
                              FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf as p,
                                   " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe as a
                             WHERE p.id = a.annexe_refid
                               AND a.contrat_refid = " . $contrat->id . "
                          ORDER BY a.rang";
                $sql = $this->db->query($requete);
                $rang = 1;
                $arrAnnexe = array();
                while ($res = $this->db->fetch_object($sql)) {
                    if ($res->afficheTitre == 1) {
                        $arrAnnexe[$res->ref]['rang'] = $rang;
                        $rang++;
                    }
                }


                $pdf->Open();
                $pdf1->Open();
                $pdf->AddPage();
                $pdf1->AddPage();
                $pdf1->SetFont('', '', 8);

                $pdf->SetDrawColor(128, 128, 128);


                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("GLE " . GLE_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(0, 0);

                $pdf->AddFont('VeraMoBI', 'BI', DOL_DOCUMENT_ROOT . '/Synopsis_Tools/font/VeraMoBI.php');
                $pdf->AddFont('fq-logo', 'Roman', DOL_DOCUMENT_ROOT . '/Synopsis_Tools/font/fq-logo.php');

                // Tete de page
                $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                $pdf->SetFont('Helvetica', 'B', 12);

                //Titre Page 1
                $pdf->SetXY(49, 42);
                $pdf->MultiCell(157, 6, 'Contrat ' . $contrat->ref, 0, 'C');

                $pdf->SetFont('', '', 9);
                $this->clauseDefault($pdf, 0, 20);
                $pdf->SetFont('', '', 8);

                $this->_pagefoot($pdf, $outputlangs);
                //Page 3 ligne par 3 lignes
                $i = 3;
                $init = 39;
                $pdf->SetXY($this->marge_gauche, $init);
                $nextY = $init;
                $nextY = $pdf->getY() + 4;

                $avenant = 0;
                $hauteur_ligne = 9.8;
                $col1 = 51;
                $col2 = 106;
                $page = 1;

                foreach ($contrat->lines as $key => $val) {
//                    if ($val->statut == 0 || $val->statut == 5)
//                        continue;
                    if ($avenant != $val->avenant && $val->avenant != 0) {
                        if ($i == 3) {
//                            $pdf->Line($this->marge_gauche - 1 ,$nextY,$this->page_largeur - $this->marge_droite + 2,$nextY);
//                            $pdf->Line($this->marge_gauche +$col1, $this->marge_haute , $this->marge_gauche +$col1 ,$pdf->getY());
                        } else {
                            $this->_pagefoot($pdf, $outputlangs);
                            $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);
                            $pdf->Line($this->marge_gauche + $col1, $this->marge_haute, $this->marge_gauche + $col1, $nextY);
                        }

                        $pdf->AddPage();
                        $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                        $i = 1;
                        //Titre Page 1

                        if ($val->avenant != 9999999999) {
                            $requete = "SELECT unix_timestamp(date_avenant)  as da
                                          FROM Babel_contrat_avenant
                                         WHERE id = " . $val->avenant;
                            $sql = $this->db->query($requete);
                            $res = $this->db->fetch_object($sql);
                            $avenantTxt = " du " . date('d/m/Y', $res->da);
                        }

                        $pdf->SetXY(59, 32);
                        $pdf->SetFont('Helvetica', 'B', 12);
                        $pdf->MultiCell(157, 6, utf8_encodeRien('Avenant ' . $avenantTxt), 0, 'C');
                        $pdf->SetFont('', '', 8);
                        $pdf->SetXY($this->marge_gauche - 1, $init);
                        $nextY = $init;
                    } else if ($i == 3 || $pdf->GetY() > 500) {
                        $pdf->AddPage();
                        $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                        $i = 1;
                        if ($page == 1) {
                            $pdf->SetXY(59, 32);
                            $pdf->SetFont('Helvetica', 'B', 12);
                            $pdf->MultiCell(157, 6, utf8_encodeRien('Contenu du contrat'), 0, 'C');
                            $pdf->SetFont('', '', 8);
                            $pdf->SetXY($this->marge_gauche - 1, $init);
                            $nextY = $pdf->GetY();
                            $page++;
                        } else {
                            $nextY = $init;
                        }
                    } else {
//                        $pdf->SetDrawColor(0,0,0);
                        $pdf->Line($this->marge_gauche - 1, $nextY - 0.5, $this->page_largeur - $this->marge_droite + 2, $nextY - 0.5);
                        $i++;
                    }
                    $avenant = $val->avenant;
                    $pdf->SetTextColor(0, 0, 60);
                    $pdf->SetFont('', '', 8);
//$this->debug .= print_r($val->prodContrat,1);
//                    file_put_contents('/tmp/debugBIMPpdf.txt',print_r($val->prodContrat,1));
                    //Table
                    $type = "Libre";
                    $pdf->setfillcolor(250, 250, 250);
                    $extraDataType = "";
                    $libelleContrat = $val->prodContrat->libelle;
                    if ($val->type == 3) {
                        $pdf->setfillcolor(255, 231, 227);
                        $type = "Maintenance";
                    } else if ($val->type == 4) {
                        $pdf->setfillcolor(223, 255, 232);
                        $type = "SAV";
                    } else if ($val->type == 2) {
                        $pdf->setfillcolor(209, 221, 255);
                        $type = "Ticket";
                    }
                    if ($val->prodContrat->extra_Type_PDF) {
                        $type = $val->prodContrat->extra_Type_PDF;
                    }
                    if ($val->prodContrat->extra_Designation_PDF) {
                        $libelleContrat = $val->prodContrat->extra_Designation_PDF;
                    }
                    if ($val->prodContrat->extra_Couleur_PDF) {
                        $color = $this->hex2RGB($val->prodContrat->extra_Couleur_PDF, false);
                        $pdf->setfillcolor($color['red'], $color['green'], $color['blue']);
                    }


                    if ($val->type == 3) {
                        if ($val->GMAO_Mixte['telemaintenance'] > 0) {
                            if ($val->GMAO_Mixte['nbVisiteAn'] > 0)
                                $extraDataType = $val->GMAO_Mixte['nbVisiteAn'] . " intervention(s) de télémaintenance";
                            else if ($val->GMAO_Mixte['nbVisiteAn'] < 0)
                                $extraDataType = "Nb interventions de télémaintenance illimités";
                            $type = "Télémaintenance";
                        }
                        elseif ($val->GMAO_Mixte['hotline'] > 0) {
                            if ($val->GMAO_Mixte['nbVisiteAn'] > 0)
                                $extraDataType = $val->GMAO_Mixte['nbVisiteAn'] . " appel(s)";
                            else if ($val->GMAO_Mixte['nbVisiteAn'] < 0)
                                $extraDataType = "Nb appels illimités";
                            $type = "Hot-line";
                        } elseif ($val->GMAO_Mixte['hotline'] > 0) {
                            if ($val->GMAO_Mixte['nbVisiteAn'] > 0)
                                $extraDataType = $val->GMAO_Mixte['nbVisiteAn'] . " visite(s) par an";
                            else if ($val->GMAO_Mixte['nbVisiteAn'] < 0)
                                $extraDataType = "Nb visite(s) illimités";
                        }
                    } else if ($val->type == 4) {
                        $extraDataType = "Extension de " . $val->GMAO_Mixte['durVal'] . " mois";
                    } else if ($val->type == 2) {
                        if ($val->GMAO_Mixte['tickets'] > 0)
                            $extraDataType = " Lot de  " . $val->GMAO_Mixte['tickets'] . " tickets";
                        else
                            $extraDataType = " Nb tickets illimités";
                    }



                    $pdf->SetXY($this->marge_gauche - 1, $nextY);
                    $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);
                    $pdf->SetDrawColor(128, 128, 128);
                    //Type de contrat

                    $pdf->SetFont('', 'B', 8);
                    $pdf->MultiCell($col1, $hauteur_ligne, utf8_encodeRien($type), 0, 'C', 1);
                    $pdf->SetFont('', '', 8);
                    $pdf->setXY($this->marge_gauche + $col1 - 1, $nextY);
                    //Data Type de contrat
                    $pdf->MultiCell($col2, $hauteur_ligne, "  " . utf8_encodeRien($extraDataType), 0, 'L', 1);
                    $nextY = $pdf->getY();
                    $pdf->SetXY($this->marge_gauche - 1, $nextY);
                    $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);
                    //Désignation
                    $pdf->MultiCell($col1, $hauteur_ligne, utf8_encodeRien("Désignation"), 0, 'C', 1);
                    $pdf->setXY($this->marge_gauche + $col1 - 1, $nextY);
                    //Data Désignation
                    $pdf->SetFont('Helvetica', '', 7);
                    $designation = couperChaine("  " . traiteStr($val->prodContrat->ref . " - ") . traiteStr($libelleContrat), 70);
                    $pdf->MultiCell($col2, $hauteur_ligne, $designation, 0, 'L', 1);
                    $pdf->SetFont('', '', 8);
                    $nextY = $pdf->getY();
                    $pdf->SetXY($this->marge_gauche - 1, $nextY);
                    $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);
                    //Matériel
                    $pdf->MultiCell($col1, $hauteur_ligne * 2, utf8_encodeRien("Matériel"), 0, 'C', 1);
                    $pdf->setXY($this->marge_gauche + $col1 - 1, $nextY);
                    //Data Matériel
                    //$pdf->SetFont('Helvetica', '', 7);

                    $pdf->MultiCell($col2, $hauteur_ligne * 2, "", 0, 'L', 1);
                    $pdf->setXY($this->marge_gauche + $col1, $nextY);

                    $pdf->MultiCell($col2 - 1, $hauteur_ligne, max_size(traiteStr($val->description . ($val->serial_number . "x" == "x" ? "" : " (SN: " . $val->serial_number . ")")), 150), 0, 'L', 1);
                    $nextY = $nextY + $hauteur_ligne * 2;
                    $pdf->SetFont('', '', 8);
                    $pdf->SetXY($this->marge_gauche - 1, $nextY);
                    $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);

                    //Prix
                    $pdf->MultiCell($col1, $hauteur_ligne, utf8_encodeRien("Tarif"), 0, 'C', 1);
                    $pdf->setXY($this->marge_gauche + $col1 - 1, $nextY);
                    //Data Prix
                    $pdf->MultiCell($col2, $hauteur_ligne, "  " . utf8_encodeRien(utf8_encodeRien(price($val->total_ht) . EURO . "  pour " . $val->GMAO_Mixte['durVal'] . " mois")), 0, 'L', 1);
                    $nextY = $pdf->getY();
                    $pdf->SetXY($this->marge_gauche - 1, $nextY);
                    $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);


//                    //Date
                    $pdf->MultiCell($col1, $hauteur_ligne, utf8_encodeRien("Date"), 0, 'C', 1);
                    $pdf->setXY($this->marge_gauche + $col1 - 1, $nextY);
//                    //Data Date
                    $pdf->MultiCell($col2, $hauteur_ligne, "  " . utf8_encodeRien("Du " . dol_print_date($val->date_ouverture) . " au " . dol_print_date($val->date_fin_validite) . ($val->GMAO_Mixte['reconductionAuto'] > 0 ? " avec reconduction automatique" : "")), 0, 'L', 1);
                    $nextY = $pdf->getY();
                    $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);

                    $pdf->SetXY($this->marge_gauche - 1, $nextY);

                    //Conditions
                    $pdf->MultiCell($col1, $hauteur_ligne * 2, utf8_encodeRien("Conditions"), 0, 'C', 1);
                    $remY = $pdf->getY();
                    $pdf->setXY($this->marge_gauche + $col1 - 1, $nextY);
                    //Data Conditions
                    $condition = "";
                    if ($val->SLA . "x" != "x")
                        $condition.= "Délai d'intervention : " . utf8_encodeRien(($val->SLA == 0 || $val->SLA . "x" == "x" ? "Aucun" : $val->SLA) . "\n");
                    if (preg_match("/\[\[Annexe:([\w]*)\]\]/", $val->GMAO_Mixte['clause'], $arr)) {
                        $numAnnexe = $arrAnnexe[$arr[1]]['rang'];

                        $arrAnnexe[$arr[1]]['lnk'] = $pdf->AddLink();
                        $condition .= utf8_encodeRien(preg_replace("/\[\[Annexe:([\w]*)\]\]/", "Annexe " . $numAnnexe, $val->GMAO_Mixte['clause']));
                        $pdf->Link($this->marge_gauche + $col1, $nextY, $this->page_largeur - ($this->marge_droite + $this->marge_gauche), 2 * $hauteur_ligne, $arrAnnexe[$arr[1]]['lnk']);
                    } else {
                        $sql = "SELECT annexe.rang FROM `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexe` annexe, Babel_product prod WHERE `annexe_refid` = prod.`annexe`
                                    AND prod.`rowid` = '" . $val->fk_product . "' 
                                    AND `contrat_refid` = '" . $contrat->id . "'";
                        $res = $this->db->query($sql);
                        $result = $this->db->fetch_object($res);
                        if (isset($result->rang)) {
                            if ($val->GMAO_Mixte['clause'] != '')
                                $condition .= utf8_encodeRien($val->GMAO_Mixte['clause']) . "\n";
                            $condition .= "Cf Annexe " . $result->rang;
                        }
                        else
                            $condition .= utf8_encodeRien($val->GMAO_Mixte['clause']);
                    }
                    $pdf1->SetX(0);
                    $pdf1->SetY(0);
                    $pdf1->MultiCell($col2, $hauteur_ligne, "  " . utf8_encodeRien($condition), 0, 'L', 1);
                    $sizeY = $pdf1->GetY();
                    $sizeX = $pdf1->GetX();
                    $hauteur_ligne2 = $hauteur_ligne;
                    if ($sizeY < $hauteur_ligne * 2 && $condition . "x" == "x") {
                        $condition .= "
  ";
                    } else if ($sizeY < $hauteur_ligne * 2 && $condition . "x" != "x") {
                        //$hauteur_ligne2 = $hauteur_ligne * 2;
                    }
                    //$condition .= "sizeY:".$sizeY." hauteur ligne:".$hauteur_ligne;
                    $pdf->MultiCell($col2, $hauteur_ligne2 * 2, '', 0, 'L', 1);
                    $pdf->setXY($this->marge_gauche + $col1, $nextY);
                    $pdf->MultiCell($col2, $hauteur_ligne2, utf8_encodeRien($condition), 0, 'L', 0);
                    $pdf->setXY($this->marge_gauche + $col1 - 1, $nextY);
                    $nextY = $remY;
                    $pdf->setY($nextY);
                    /*     $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);

                      $pdf->SetXY($this->marge_gauche - 1, $nextY);


                      //Info
                      $pdf->MultiCell($col1, $hauteur_ligne, utf8_encodeRien("Infos"), 0, 'C', 1);
                      $requete = "SELECT durRenew,
                      unix_timestamp(date_renouvellement) as date_renouvellement
                      FROM Babel_contrat_renouvellement
                      WHERE contratdet_refid = " . $val->id
                      . " ORDER BY date_renouvellement DESC
                      LIMIT 1 ";
                      $sql = $this->db->query($requete);
                      $res = $this->db->fetch_object($sql);
                      $info = "";
                      if ($res->date_renouvellement > 0) {
                      $info .= 'Renouvelé le ' . date('d/m/Y', $res->date_renouvellement) . " pour " . $res->durRenew . " mois";
                      }
                      //Data Info
                      $pdf->setXY($this->marge_gauche + $col1 - 1, $nextY);
                      $pdf->MultiCell($col2, $hauteur_ligne, "  " . utf8_encodeRien($info), 0, 'L', 1);
                      //                    //Data Date
                      $nextY = $pdf->getY();
                      $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY); */

                    if ($i == 3) {
                        $pdf->Line($this->marge_gauche - 1, $this->page_hauteur - $this->marge_basse - 1, $this->page_largeur - $this->marge_droite + 2, $this->page_hauteur - $this->marge_basse - 1);
                        $pdf->Line($this->marge_gauche + $col1, $this->marge_haute, $this->marge_gauche + $col1, $this->page_hauteur - $this->marge_basse - 1);
                        $this->_pagefoot($pdf, $outputlangs);
                    }
                    $nextY = $pdf->getY();
                }


                if ($i != 3) {
                    $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);
                    $pdf->Line($this->marge_gauche + $col1, $this->marge_haute, $this->marge_gauche + $col1, $pdf->getY());
                    $this->_pagefoot($pdf, $outputlangs);
                }

                $needExtGarPage = false;
                foreach ($contrat->lines as $key => $val) {
                    if ($val->statut == 0 || $val->statut == 5)
                        continue;
                    if ($val->type == 4) {
                        $needExtGarPage = true;
                        break;
                    }
                }
                if ($needExtGarPage) {
                    $init = $this->marge_gauche - 1;
                    getHeadExtensionsGarenties($pdf, $outputlangs, $contrat, $hauteur_ligne);

                    $nextY = $pdf->getY();
                    foreach ($contrat->lines as $key => $val) {
                        if ($val->statut == 0 || $val->statut == 5)
                            continue;

                        if ($nextY > 274) {
                            getHeadExtensionsGarenties($pdf, $outputlangs, $contrat, $hauteur_ligne, true);
                        }

                        $nextY = $pdf->getY();
                        $type = "Libre";
                        $libelleContrat = $val->prodContrat->libelle;

                        $pdf->setfillcolor(255, 255, 255);
                        if ($val->type == 3) {
                            $pdf->setfillcolor(255, 231, 227);
                            $type = "Maintenance";
                            continue;
                        } else if ($val->type == 4) {
                            $pdf->setfillcolor(223, 255, 232);
                            $type = "SAV";
                        } else if ($val->type == 2) {
                            $pdf->setfillcolor(209, 221, 255);
                            $type = "Tickets";
                            continue;
                        } else {
                            continue;
                        }
                        if ($val->prodContrat->extra_Type_PDF) {
                            $type = $val->prodContrat->extra_Type_PDF;
                        }
                        if ($val->prodContrat->extra_Designation_PDF) {
                            $libelleContrat = $val->prodContrat->extra_Designation_PDF;
                        }
                        if ($val->prodContrat->extra_Couleur_PDF) {
                            $color = $this->hex2RGB($val->prodContrat->extra_Couleur_PDF, false);
                            $pdf->setfillcolor($color['red'], $color['green'], $color['blue']);
                        }


                        $pdf->SetXY($init, $nextY);
                        $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);
                        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien($val->desc), 0, 'L', 1);
                        $pdf->SetXY($init + $col, $nextY);
                        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien($val->serial_number), 0, 'C', 1);
                        $pdf->SetXY($init + $col + $col, $nextY);
                        $pdf->MultiCell($col - $decal_type, $hauteur_ligne, utf8_encodeRien($type), 0, 'C', 1);
                        $pdf->SetXY($init + $col + $col + $col - $decal_type, $nextY);
                        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("Du " . dol_print_date($val->date_ouverture) . " au " . dol_print_date($val->date_fin_validite)), 0, 'C', 1);
                        $nextY = $pdf->getY();
                    }
                    $this->_pagefoot($pdf, $outputlangs);
                }

                $init = $this->marge_gauche - 1;
                $nextY = 50;
                $pdf->AddPage();
                $this->_pagehead($pdf, $contrat, 1, $outputlangs);


                //Titre Page 1
                $pdf->SetXY(59, 32);
                $pdf->SetFont('Helvetica', 'B', 12);

                $pdf->MultiCell(157, 6, utf8_encodeRien('Résumé des services'), 0, 'C');


                $pdf->SetFont('', 'B', 8);

                $nextY = $this->marge_haute;
                $pdf->SetXY($init, $nextY);
                $col = 32;
                $pdf->setfillcolor(220, 130, 40);
                $pdf->SetDrawColor(220, 130, 40);
                $pdf->SetTextColor(255, 255, 255);
                $decal_type = 3;
                $avenant = 0;

                $pdf->MultiCell($col + 15, $hauteur_ligne, utf8_encodeRien("Produits"), 0, 'C', 1);
                $pdf->SetXY($init + $col + 15, $nextY);
                $pdf->MultiCell($col - 5, $hauteur_ligne, utf8_encodeRien("SN"), 0, 'C', 1);
                $pdf->SetXY($init + $col + $col + 10, $nextY);
                $pdf->MultiCell($col - 5 - $decal_type, $hauteur_ligne, utf8_encodeRien("Type"), 0, 'C', 1);
                $pdf->SetXY($init + $col + $col + 5 + $col - $decal_type, $nextY);
                $pdf->MultiCell($col - 5, $hauteur_ligne, utf8_encodeRien("Dates"), 0, 'C', 1);

                $pdf->SetXY($init + $col + $col + $col - $decal_type + $col, $nextY);
                $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("Délais d'intervention"), 0, 'C', 1);
                $pdf->SetTextColor(0, 0, 60);

                $pdf->SetFont('', '', 7);

                $nextY = $pdf->getY();
                foreach ($contrat->lines as $key => $val) {
                    $libelleContrat = $val->prodContrat->libelle;
//                    if ($val->statut == 0 || $val->statut == 5)
//                        continue;
                    $pdf->SetDrawColor(255, 255, 255);
                    $pdf->setfillcolor(255, 255, 255);
                    $type = "Libre";


                    if ($val->type == 3) {
                        $pdf->SetDrawColor(255, 231, 227);
                        $pdf->setfillcolor(255, 231, 227);
                        $type = "Maintenance";
                    } else if ($val->type == 4) {
                        $pdf->SetDrawColor(223, 255, 232);
                        $pdf->setfillcolor(223, 255, 232);
                        $type = "SAV";
                        continue;
                    } else if ($val->type == 2) {
                        $pdf->setfillcolor(209, 221, 255);
                        $pdf->SetDrawColor(209, 221, 255);
                        $type = "Tickets";
                    }
                    if ($val->prodContrat->extra_Type_PDF) {
                        $type = $val->prodContrat->extra_Type_PDF;
                    }
                    if ($val->prodContrat->extra_Designation_PDF) {
                        $libelleContrat = $val->prodContrat->extra_Designation_PDF;
                    }
                    if ($val->prodContrat->extra_Couleur_PDF) {
                        $color = $this->hex2RGB($val->prodContrat->extra_Couleur_PDF, false);
                        $pdf->setfillcolor($color['red'], $color['green'], $color['blue']);
                        $pdf->SetDrawColor($color['red'], $color['green'], $color['blue']);
                    }


                    if ($nextY > 274) {
                        $this->_pagefoot($pdf, $outputlangs);

                        $pdf->AddPage();
                        $this->_pagehead($pdf, $contrat, 1, $outputlangs);

                        $pdf->SetFont('Helvetica', 'B', 12);

                        //Titre Page 1
                        $pdf->SetXY(49, 42);
                        $pdf->MultiCell(157, 6, utf8_encodeRien('Résumé des services(Suite)'), 0, 'C');

                        $pdf->SetFont('', 'B', 8);


                        $pdf->SetXY($init, $nextY);
                        $col = 32;
                        $pdf->setfillcolor(220, 130, 40);
                        $pdf->SetDrawColor(220, 130, 40);
                        $pdf->SetTextColor(255, 255, 255);
                        $decal_type = 3;

                        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("Produits"), 0, 'C', 1);
                        $pdf->SetXY($init + $col, $nextY);
                        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("SN"), 0, 'C', 1);
                        $pdf->SetXY($init + $col + $col, $nextY);
                        $pdf->MultiCell($col - $decal_type, $hauteur_ligne, utf8_encodeRien("Type"), 0, 'C', 1);
                        $pdf->SetXY($init + $col + $col + $col - $decal_type, $nextY);
                        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("Dates"), 0, 'C', 1);
                        $pdf->SetXY($init + $col + $col + $col - $decal_type + $col, $nextY);
                        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("Tarif"), 0, 'C', 1);
                        $pdf->SetFont('', '', 8);

                        $pdf->SetTextColor(0, 0, 60);
                        $pdf->SetDrawColor(255, 255, 255);
                        $pdf->setfillcolor(255, 255, 255);
                        $type = "Libre";
                        if ($val->type == 3) {
                            $pdf->SetDrawColor(255, 231, 227);
                            $pdf->setfillcolor(255, 231, 227);
                            $type = "Maintenance";
                        } else if ($val->type == 4) {
                            $pdf->SetDrawColor(223, 255, 232);
                            $pdf->setfillcolor(223, 255, 232);
                            $type = "SAV";
                            continue;
                        } else if ($val->type == 2) {
                            $pdf->setfillcolor(209, 221, 255);
                            $pdf->SetDrawColor(209, 221, 255);
                            $type = "Tickets";
                        }
                        if ($val->prodContrat->extra_Type_PDF) {
                            $type = $val->prodContrat->extra_Type_PDF;
                        }
                        if ($val->prodContrat->extra_Designation_PDF) {
                            $libelleContrat = $val->prodContrat->extra_Designation_PDF;
                        }
                        if ($val->prodContrat->extra_Couleur_PDF) {
                            $color = $this->hex2RGB($val->prodContrat->extra_Couleur_PDF, false);
                            $pdf->setfillcolor($color['red'], $color['green'], $color['blue']);
                            $pdf->SetDrawColor($color['red'], $color['green'], $color['blue']);
                        }

                        $nextY = $pdf->getY();
                    }
                    $pdf->SetXY($init, $nextY);
                    $pdf->Line($this->marge_gauche - 1, $nextY, $this->page_largeur - $this->marge_droite + 2, $nextY);

                    if ($avenant != $val->avenant) {
                        $avenantTxt = "";
                        $pdf->SetTextColor(0, 0, 60);
                        $pdf->SetDrawColor(0, 0, 60);
                        $pdf->setfillcolor(255, 255, 255);

                        if ($val->avenant != 9999999999) {
                            $requete = "SELECT unix_timestamp(date_avenant)  as da
                                          FROM Babel_contrat_avenant
                                         WHERE id = " . $val->avenant;
                            $sql = $this->db->query($requete);
                            $res = $this->db->fetch_object($sql);
                            $avenantTxt = " du " . date('d/m/Y', $res->da);
                        }
                        $pdf->SetFont('Helvetica', 'B', 8);
                        $pdf->MultiCell(157, 6, utf8_encodeRien('Avenant ' . $avenantTxt), 1, 'C', 0);
                        $pdf->SetFont('Helvetica', '', 6.5);
                        $avenant = $val->avenant;

                        $pdf->SetTextColor(0, 0, 60);
                        $pdf->SetDrawColor(255, 255, 255);
                        $pdf->setfillcolor(255, 255, 255);
                        $type = "Libre";
                        if ($val->type == 3) {
                            $pdf->SetDrawColor(255, 231, 227);
                            $pdf->setfillcolor(255, 231, 227);
                            $type = "Maintenance";
                        } else if ($val->type == 4) {
                            $pdf->SetDrawColor(223, 255, 232);
                            $pdf->setfillcolor(223, 255, 232);
                            $type = "SAV";
                            continue;
                        } else if ($val->type == 2) {
                            $pdf->setfillcolor(209, 221, 255);
                            $pdf->SetDrawColor(209, 221, 255);
                            $type = "Tickets";
                        }
                        if ($val->prodContrat->extra_Type_PDF) {
                            $type = $val->prodContrat->extra_Type_PDF;
                        }
                        if ($val->prodContrat->extra_Designation_PDF) {
                            $libelleContrat = $val->prodContrat->extra_Designation_PDF;
                        }
                        if ($val->prodContrat->extra_Couleur_PDF) {
                            $color = $this->hex2RGB($val->prodContrat->extra_Couleur_PDF, false);
                            $pdf->setfillcolor($color['red'], $color['green'], $color['blue']);
                            $pdf->SetDrawColor($color['red'], $color['green'], $color['blue']);
                        }

                        $nextY = $pdf->GetY() + 0.1;
                        $pdf->SetXY($this->marge_gauche - 1, $nextY);
                    }

                    $desc = couperChaine(utf8_encodeRien($libelleContrat . " " . $val->desc), 60);

                    $pdf->SetFont('Helvetica', '', 6.5);
                    $pdf1->SetFont('Helvetica', '', 6.5);
                    $pdf1->SetXY(0, 0);
                    $pdf1->MultiCell($col, $hauteur_ligne, utf8_encodeRien($desc), 0, 'L', 1);
                    $newY = $pdf1->GetY();
                    $hauteur_ligne2 = $hauteur_ligne;
//                    die($newY . "|" . $hauteur_ligne);
//                    if ($newY > 2 * $hauteur_ligne) {
//                        $hauteur_ligne2 = $hauteur_ligne / 2;
//                        $bool = true;
//                        $iteartorTest = 0;
//                        while ($bool) {
//                            $iteartorTest++;
//                            $desc = substr($desc, 0, -1);
//                            $pdf1->SetXY(0, 0);
//                            $pdf1->MultiCell($col + 15, $hauteur_ligne, utf8_encodeRien($desc . "..."), 0, 'L', 1);
//                            $newY = $pdf1->GetY();
//                            if ($newY <= 2 * $hauteur_ligne) {
//                                $desc .= "...";
//                                $bool = false;
//                            }
//                            if ($iteartorTest > 10000)
//                                die("La boucle est infinie");
//                        }
//                    } else if ($newY > $hauteur_ligne) {
//                        $hauteur_ligne2 = $hauteur_ligne / 2;
//                    }

                    $pdf->MultiCell($col + 15, $hauteur_ligne2, utf8_encodeRien($desc), 0, 'L', 1);
                    $pdf->SetXY($init + $col + 15, $nextY);
                    $pdf->MultiCell($col - 5, $hauteur_ligne, traiteStr($val->serial_number), 0, 'C', 1);
                    $pdf->SetXY($init + $col + $col + 10, $nextY);
                    $pdf->MultiCell($col - $decal_type - 5, $hauteur_ligne, utf8_encodeRien($type), 0, 'C', 1);
                    $pdf->SetXY($init + $col + $col + $col + 5 - $decal_type, $nextY);
                    $pdf->MultiCell($col - 5, $hauteur_ligne, utf8_encodeRien("Du " . dol_print_date($val->date_ouverture) . "
Au " . dol_print_date($val->date_fin_validite)), 0, 'C', 1);
                    $pdf->SetXY($init + $col + $col + $col - $decal_type + $col, $nextY);

//                    $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien(price($val->total_ht) . EURO . " pour " . $val->GMAO_Mixte['durVal'] . " mois"), 0, 'C', 1);                    $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien(price($val->total_ht) . EURO . " pour " . $val->GMAO_Mixte['durVal'] . " mois"), 0, 'C', 1);
                    $slaT = utf8_encodeRien(utf8_encodeRien($val->SLA));
                    $hauteurSla = (strlen($slaT) < 25) ? $hauteur_ligne : $hauteur_ligne / 2;
                    $pdf->MultiCell($col, $hauteurSla, $slaT, 0, 'C', 1);
                    $pdf1->SetFont('Helvetica', '', 6.5);


                    $nextY = $pdf->getY();
                }
                $this->_pagefoot($pdf, $outputlangs);

                //Annexes
//                $pdf->SetAutoPageBreak(0,1);

                $requete = "SELECT *, IF(a.annexe != '', a.annexe, p.annexe) as annexeP
                              FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf as p,
                                   " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe as a
                             WHERE p.id = a.annexe_refid
                               AND a.contrat_refid = " . $contrat->id . "
                          ORDER BY a.rang";
//                die($requete);
                $sql = $this->db->query($requete);
                $i = 0;
                $rang = 1;
                while ($res = $this->db->fetch_object($sql)) {
                    if (!$i == 0)
                        $this->_pagefoot($pdf, $outputlangs);
                    $pdf->AddPage();
                    $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                    $i++;
                    if ($arrAnnexe[$res->ref]['lnk'] > 0) {
                        $pdf->SetLink($arrAnnexe[$res->ref]['lnk'], $this->marge_haute);
                    }
                    $pdf->SetFont('', '', 8);
                    $pdf->SetXY($this->marge_gauche, $this->marge_haute);
                    $pdf->SetFont('Helvetica', 'B', 12);
                    if ($res->afficheTitre == 1) {
                        $pdf->multicell(155, 7, utf8_encodeRien(utf8_encodeRien("Annexe " . $rang . " : " . $res->modeleName)), 0, 'L');
                        $rang++;
                    } else {
                        $pdf->multicell(155, 7, utf8_encodeRien(utf8_encodeRien($res->modeleName)), 0, 'L');
                    }
                    $pdf->SetFont('', '', 8);
                    $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 5);

                    //Traitement annexe :>
                    $annexe = $this->replaceWithAnnexe($res->annexeP, $contrat, $res->annexe_refid);



//
//                    $pdf->multicell(155, 5, utf8_encodeRien(utf8_encodeRien($annexe)));
                    $tabLigneAnnexe = explode("\n", $annexe);
                    foreach ($tabLigneAnnexe as $ligne) {
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
                        if ($yAfter > 270 || ($titre && $yAfter > 250)) {
                            $this->_pagefoot($pdf, $outputlangs);
                            $pdf->AddPage();
                            $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                            $pdf->SetY($this->marge_haute);
                        }

                        $pdf->SetFont('', $style, 8);
                        $pdf->multicell(155, 5, utf8_encodeRien(utf8_encodeRien($ligne)), 0, 'L');
                    }
                }


                $this->_pagefoot($pdf, $outputlangs);



                $pdf->AliasNbPages();
                $pdf->Close();

                $this->file = $file;
                $this->file = $file;
                $pdf->Output($file, 'F');

//                //$langs->setPhpLang();    // On restaure langue session


                return 1;   // Pas d'erreur
            } else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                //$langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "CONTRACT_OUTPUTDIR");
            //$langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error = $langs->trans("ErrorUnknown");
        //$langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

    function clauseDefault(&$pdf, $decal_x = 0, $decal_y = 0) {
        global $langs;
        $langs->load('dict');
        $requete = "
        SELECT *
          FROM llx_c_type_contact,
               llx_element_contact,
               llx_socpeople
         WHERE `code` = 'SALESREPSIGN'
           AND source = 'external'
           AND element = 'contrat'
           AND llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
           AND llx_socpeople.rowid = llx_element_contact.fk_socpeople
           AND element_id = " . $this->contrat->id;
        $sql = $this->db->query($requete);
        $to = "";
        $poste = "";
        $tel = "";
        $to_signature = "";
        if ($this->db->num_rows($sql) > 0) {
            $tmpSoc = new Societe($this->db);
            $tmpSoc->fetch($res->fk_soc);
            $res = $this->db->fetch_object($sql);
            $tel = "\nTel: " . ($res->phone . "x" != "x" ? $res->phone : $tmpSoc->tel) . "        email : " . $res->email;
            $civilite = $res->civilite;
            if ($langs->trans("CivilityShort" . $res->civilite) != "CivilityShort" . $civilite)
                $civilite = $langs->trans("CivilityShort" . $res->civilite);
            $to = $civilite . " " . $res->name . " " . $res->firstname;
            $to_signature = $civilite . " " . $res->name . " " . $res->firstname;
            if ($res->address . "x" != "x") {
                $to .= "\n" . $res->address . " " . $res->cp . " " . $res->ville;
            }
            $poste = utf8_encodeRien($res->poste);
        } else {
            $poste = "";
            $to = "Pas de client signataire désigné";
        }
        $modeReg = "Indéterminé";
        $requete = "SELECT * FROM llx_c_paiement WHERE id = " . $this->contrat->modeReg_refid;
        $sql = $this->db->query($requete);
        if ($sql) {
            $res = $this->db->fetch_object($sql);
            $modeReg = utf8_encodeRien($res->libelle);
        }
        $condReg = "Indéterminé";
        $requete = "SELECT * FROM llx_cond_reglement WHERE rowid = " . $this->contrat->condReg_refid;
        $sql = $this->db->query($requete);
        if ($sql) {
            $res = $this->db->fetch_object($sql);
            $condReg = utf8_encodeRien($res->libelle);
        }

        $clause = "Entre les soussignés";
        $clause1 = "BIMP INFORMATIQUE";
        $clause2 = "Société Anonyme OLYS au capital de 85 372 Euros, dont le siège social sis 4 rue du Cdt. Dubois
69003 LYON, représentée par Monsieur Roland CHASSING, Président Directeur Général.

d'une part
Et,";

        $clause3 = utf8_encodeRien($this->contrat->societe->titre) . " " . utf8_encodeRien($this->contrat->societe->nom);
        $clause4 = "Sis " . utf8_encodeRien($this->contrat->societe->adresse_full) . "
Représenté(e) légalement par
" . utf8_encodeRien($to) . "        Fonction : " . $poste . $tel . "

";

        $clause5 = "1 OBJET";
        $clause6 = "Le présent contrat a pour objet de définir les modalités d'intervention de la Société BIMP INFORMATIQUE auprès de " . utf8_encodeRien($this->contrat->societe->titre) . " " . utf8_encodeRien($this->contrat->societe->nom) . "

";

        $clause7 = "2 LIMITES GENERIQUES DU CONTRAT";
        $clause8 = "Ce contrat ne se substitue en aucun cas aux prestations de formation.
Toute nouvelle installation de logiciels, de nouveaux postes ou de nouveaux périphériques fera l'objet d'un devis indépendant de ce présent contrat. Dans le cas de l'adjonction de nouveaux postes ou serveurs, le présent contrat fera l'objet d'une nouvelle proposition en rapport avec les évolutions du réseau. L'audit matériel fournit par l'outil de gestion de parc faisant foi.

";

        $clause9 = "3 CONTRAT CADRE";
        $clause10 = "Ce contrat se compose de une ou plusieurs sous parties spécifiques à chacune de vos demandes contractualisées. Chaque demande peut présenter des clauses spécifiques qui prévalent devant les clauses du contrat cadre.

";

        $clause11 = "4 CLAUSE ARBITRALE";
        $clause12 = "En cas de désaccord dans l'exécution du présent contrat seul le tribunal de commerce de Lyon sera compétent.

";

        $clause13 = "5 DATE D'EFFET";
        $clause14 = "Le présent contrat prendra effet le " . date('d/m/Y', $this->contrat->date_contrat) . "

";

        $clause15 = "6 REGLEMENTS";
        $clause16 = "Nos tarifs s'entendent hors taxe et nos prestations sont payables telles que :

    Mode de paiement : " . $modeReg . "
    Condition de paiement :  " . $condReg . ".

Fait en deux exemplaires
A LYON
Le ...................,
";

//TODO import automatique
//TODO Notification sur changement de cond / mod de reglement(email)
//TODO avenant KO
//TODO AnnexPDF => système de variable avec Interlocuteur Tech, Civilité, Nom Mail, tel + Numéro de contrat + durée de validité
//transfert des conditions de reglements de la commande au contrat
        //Si c'est pas un nouveau contrat :> pas de changement && notification si condition de reglement change si c'est un avenant => trigger new Event change + notify by mail system
//extra params dans contrat PDF
//validation de contrat ne rend pas actif la ligne + correction sur gle.bimp.fr :> Pas reproductible corrigé ?
//contrat changer condition de reglement
//import titre
//titre societe
//résumé 2) Tous sauf SAV, Ajouter séparation par avenant + colonne prix( prix mensuel  + durVal + total HT sur la période totale)
//Résumé 1) Extensions de garantie :> Liste résumée => pas de renouvellemnt possible
//ajouter catégorie +=> 3 champs templates Type de contrat + Désignation du contrat + couleur => dans PDF => Type contrat à la place de tickets, couleur pour lignes et Désignation => Désignation
//encart signature
//Infos => Renouvellement précédent + 1 ligne
//Ajouter prix period, period, et prix total
//verifier que le bouton cloturer par ligne est actif
//Verifier le bouton avenant pour statut > 0
//SLA dans les conditions
//Filtre => cloturer => ne s'affiche, tout ce qui est brouillon ne s'affiche pas

        $pdf->SetXY($this->marge_gauche + $decal_x, $this->marge_haute + $decal_y);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause), 0, 'L');
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause1), 0, 'L');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause2), 0, 'L');
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause3), 0, 'L');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause4), 0, 'L');
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause5), 0, 'L');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause6), 0, 'L');
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause7), 0, 'L');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause8), 0, 'L');
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause9), 0, 'L');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause10), 0, 'L');
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause11), 0, 'L');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause12), 0, 'L');
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause13), 0, 'L');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause14), 0, 'L');
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause15), 0, 'L');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(155, 4, utf8_encodeRien($clause16), 0, 'L');
        $remY = $pdf->GetY();
        $signature2 = "Pour BIMP Informatique :
M. Roland CHASSING
Signature et cachet";

        $signature1 = "Pour  " . utf8_encodeRien($this->contrat->societe->nom) . ":
" . utf8_encodeRien($to_signature) . "
Signature et cachet
";

        $pdf->SetY($remY + 5);
        $pdf->MultiCell(155 / 2, 4, utf8_encodeRien($signature1), 0, 'L');
        $pdf->SetXY($this->marge_gauche + $decal_x + 155 / 2, $remY + 5);
        $pdf->MultiCell(155 / 2, 4, utf8_encodeRien($signature2), 0, 'L');
    }

    function _pagehead(& $pdf, $object, $showadress = 1, $outputlangs, $currentPage = 0) {
        global $conf, $langs;
        if ($currentPage > 1) {
            $showadress = 0;
        }

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("companies");

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', 13);

        $posy = $this->marge_haute;

        $pdf->SetXY(5, 13);

        // Logo
        $logo = false;
        if (is_file($conf->societe->dir_logos . '/' . $this->emetteur->logo . "noalpha.png")) {
            $logo = $conf->societe->dir_logos . '/' . $this->emetteur->logo . "noalpha.png";
        } else {
            $logo = $conf->societe->dir_logos . '/' . $this->emetteur->logo;
        }

//        $logo = $conf->societe->dir_logos . '/' . $this->emetteur->logo;
        if ($this->emetteur->logo) {
            if (is_readable($logo)) {
                $pdf->Image($logo, 5, 13, 0, 24);
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', 8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
                $pdf->SetTextColor(0, 0, 0);
            }
        } else if (defined("FAC_PDF_INTITULE")) {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }
        $pdf->SetFont('', 'B', 10);
        //Société
        $pdf->SetXY(3.5, 54.5);
        $pdf->MultiCell(39, 4, "Code Client : " . $object->societe->code_client, 0, "L");
        $pdf->SetXY(17, 63);
        $pdf->MultiCell(39, 4, "Ref : ", 0, "L");
        $pdf->SetXY(4.5, 67);
        $pdf->MultiCell(39, 4, $object->ref, 0, "L");
        $pdf->Rect(48, 39, 157, 235);
        $pdf->SetFont('', 'B', 7);
    }

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */

    function _pagefoot(&$pdf, $outputlangs) {


        $pdf->SetFont('', 'B', 9);
        $pdf->SetTextColor(255, 63, 50);
        //Société
        global $mysoc;

        $pdf->SetXY(3.5, 269);
        $pdf->MultiCell(39, 4, utf8_encodeRien($mysoc->adresse), 0, "L");
        $pdf->SetXY(3.5, 273);
        $pdf->MultiCell(39, 4, utf8_encodeRien($mysoc->cp . " " . $mysoc->ville), 0, "L");
        $pdf->SetXY(3.5, 278);
        $pdf->MultiCell(39, 4, utf8_encodeRien("Tél. : " . $mysoc->tel), 0, "L");
        $pdf->SetXY(3.5, 282);
        $pdf->MultiCell(39, 4, "Fax  : " . $mysoc->fax, 0, "L");

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', 7);
        $ligne = "SA OLYS au capital de 85 372" . EURO . "    -   320 387 483 R.C.S. Lyon   -   APE 4741Z   -   TVA/CEE FR 34 320387483";
        $ligne .= "\n" . "RIB : BPLL  -  13907. 00000.00202704667.45  -  CCP 11 158 41U Lyon";

        $pdf->SetXY(48, 285);
        $pdf->MultiCell(157, 3, $ligne, 0, "C");
        $pdf->line(48, 282, 205, 282);

        $pdf->SetFont('', 'B', 8);
        $pdf->SetTextColor(255, 63, 50);
        $pdf->SetXY(192, 292);
        $pdf->MultiCell(19, 3, '' . $pdf->PageNo() . '/{nb}', 0, 'R', 0);

        //return pdf_pagefoot($pdf,$outputlangs,'CONTRAT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche + 40,$this->page_hauteur);
    }

    function hex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
        $rgbArray = array();
        if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
            $colorVal = hexdec($hexStr);
            $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue'] = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
            $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            return false; //Invalid hex color code
        }
        return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
    }

    function getHeadExtensionsGarenties(&$pdf, $outputlangs, $contrat, $hauteur_ligne, $suite = false) {
        if ($suite)
            $this->_pagefoot($pdf, $outputlangs);
        $pdf->AddPage();
        $this->_pagehead($pdf, $contrat, 1, $outputlangs);
        $nextY = $this->marge_haute;

        $pdf->SetFont('Helvetica', 'B', 12);

        //Titre Page 1
        $pdf->SetXY(49, 42);
        $pdf->MultiCell(157, 6, utf8_encodeRien('Résumé des extensions de garanties' . ($suite ? ' (Suite)' : '')), 0, 'C');

        $pdf->SetFont('', 'B', 8);


        $pdf->SetXY($init, $nextY);
        $col = 40;
        $pdf->setfillcolor(220, 130, 40);
        $pdf->SetTextColor(255, 255, 255);
        $decal_type = 3;

        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("Produits"), 0, 'C', 1);
        $pdf->SetXY($init + $col, $nextY);
        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("SN"), 0, 'C', 1);
        $pdf->SetXY($init + $col + $col, $nextY);
        $pdf->MultiCell($col - $decal_type, $hauteur_ligne, utf8_encodeRien("Type"), 0, 'C', 1);
        $pdf->SetXY($init + $col + $col + $col - $decal_type, $nextY);
        $pdf->MultiCell($col, $hauteur_ligne, utf8_encodeRien("Dates"), 0, 'C', 1);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('Helvetica', '', 6.5);
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








        $annexe = preg_replace('/User-fullname/', $user->fullname, $annexe);
        $annexe = preg_replace('/User-nom/', $user->nom, $annexe);
        $annexe = preg_replace('/User-prenom/', $user->prenom, $annexe);
        $annexe = preg_replace('/User-email/', $user->email, $annexe);
        $annexe = preg_replace('/User-office_phone/', $user->office_phone, $annexe);
        $annexe = preg_replace('/User-user_mobile/', $user->user_mobile, $annexe);
        $annexe = preg_replace('/User-office_fax/', $user->office_fax, $annexe);

        $annexe = preg_replace('/Mysoc-nom/', $mysoc->nom, $annexe);
        $annexe = preg_replace('/Mysoc-adresse_full/', $mysoc->adresse_full, $annexe);
        $annexe = preg_replace('/Mysoc-adresse/', $mysoc->adresse, $annexe);
        $annexe = preg_replace('/Mysoc-cp/', $mysoc->cp, $annexe);
        $annexe = preg_replace('/Mysoc-ville/', $mysoc->ville, $annexe);
        $annexe = preg_replace('/Mysoc-tel/', $mysoc->tel, $annexe);
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
          Contact-internal-SALESREPFOLL-email     Email   jm.lefevre@babel-services.com
          Contact-internal-SALESREPFOLL-tel   N° tel
          Contact-internal-SALESREPFOLL-fax   N° fax

          Contact-internal-TECHRESP-fullname  Nom complet     Jean-Marcéé LE FEVRE
          Contact-internal-TECHRESP-civilite  Civilité
          Contact-internal-TECHRESP-nom   Nom     LE FEVRE
          Contact-internal-TECHRESP-prenom    Préom   Jean-Marcéé
          Contact-internal-TECHRESP-cp    Code postal
          Contact-internal-TECHRESP-ville     Ville
          Contact-internal-TECHRESP-email     Email   jm.lefevre@babel-services.com
          Contact-internal-TECHRESP-tel   N° tel
          Contact-internal-TECHRESP-fax   N° fax

          Contact-internal-SALESREPSIGN-fullname  Nom complet     Jean-Marcéé LE FEVRE
          Contact-internal-SALESREPSIGN-civilite  Civilité
          Contact-internal-SALESREPSIGN-nom   Nom     LE FEVRE
          Contact-internal-SALESREPSIGN-prenom    Préom   Jean-Marcéé
          Contact-internal-SALESREPSIGN-cp    Code postal
          Contact-internal-SALESREPSIGN-ville     Ville
          Contact-internal-SALESREPSIGN-email     Email   jm.lefevre@babel-services.com
          Contact-internal-SALESREPSIGN-tel   N° tel
          Contact-internal-SALESREPSIGN-fax   N° fax


         */
        return $annexe;
    }

}

function couperChaine($chaine, $nb) {
    if (strlen($chaine) > $nb)
        $chaine = substr($chaine, 0, $nb) . "...";
    return $chaine;
}

function traiteStr($str) {
    return utf8_encodeRien(utf8_encodeRien(htmlspecialchars($str)));
}

function max_size($chaine, $lg_max) {
    if (strlen($chaine) > $lg_max) {
        $chaine = substr($chaine, 0, $lg_max);
        $last_space = strrpos($chaine, " ");
        $chaine = substr($chaine, 0, $last_space) . "...";
    }

    return $chaine;
}

?>

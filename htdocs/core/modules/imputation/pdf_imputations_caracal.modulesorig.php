<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008 Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
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

 * along with this program; if not, write to the Free Software

 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
  */
/*
 * or see http://www.gnu.org/
 */

/**
 \file       htdocs/core/modules/imputation/pdf_imputations_caracal.modules.php
 \ingroup    projet
 \brief      Fichier de la classe permettant de generer les projets au modele Azur
 \author        Laurent Destailleur
 \version    $Id: pdf_imputations_caracal.modules.php,v 1.121 2008/08/07 07:47:38 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT."/core/modules/imputation/modules_imputations.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");


/**
 \class      pdf_imputations_caracal
 \brief      Classe permettant de generer les projets au modele Azur
 */

class pdf_imputations_caracal extends ModelePDFImputations
{
    public $emetteur;    // Objet societe qui emet


    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function pdf_imputations_caracal($db)
    {
        global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
        $this->name = "Caracal";
        $this->description = $langs->trans('XlsxCaracalDescription');

        // Dimension page pour format A4
        $this->type = 'xlsl';

        set_include_path(get_include_path() . PATH_SEPARATOR . DOL_DOCUMENT_ROOT.'/lib/PHPExcel-1.7.6/Classes');

        require_once("PHPExcel.php");
        require_once("PHPExcel/Writer/Excel2007.php");

    }

    /**
    \brief      Fonction generant la projet sur le disque
    \param        projet            Objet propal a generer (ou id si ancienne methode)
        \param        outputlangs        Lang object for output language
        \return        int             1=ok, 0=ko
        */
    function write_file($projet,$outputlangs='',$fuser =false)
    {
        global $user,$langs,$conf;
        if (!$fuser) $fuser = $user;
        if (! is_object($outputlangs)) $outputlangs=$langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("projects");

        //$outputlangs->setPhpLang();
        if ($conf->imputations->dir_output)
        {
            // Definition de l'objet $projet (pour compatibilite ascendante)
                $ref = sanitize_string("Imputations-".date('Y')."-".$fuser->login);
                $dir = $conf->imputations->dir_output . "/" . $ref;
                $file = $dir . "/" . $ref . ".xlsx";

                if (! file_exists($dir))
                {
                    if (dol_mkdir($dir) < 0)
                    {
                        $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                        return 0;
                    }
                }

                $docxl = new PHPExcel();

                $docxl->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                $docxl->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

                $feuilxl = $docxl->getActiveSheet();

                $docxl->getActiveSheet()->getProtection()->setSheet(false);

                $feuilxl->getDefaultStyle()->getFont()->setName('Arial');
                $feuilxl->getDefaultStyle()->getFont()->setSize(12);


                $docxl->getProperties()->setCreator("GLE ++");
                $docxl->getProperties()->setLastModifiedBy("GLE ++");
                $docxl->getProperties()->setTitle("Imputations");
                $docxl->getProperties()->setSubject("Imputations de ".utf8_encode($fuser->getFullName($langs)));
                $docxl->getProperties()->setDescription("Imputations de ".utf8_encode($fuser->getFullName($langs)));
                $docxl->getProperties()->setKeywords("Imputations ".utf8_encode($fuser->getFullName($langs)." "));
                $docxl->getProperties()->setCategory("Imputations");




                $docxl->setActiveSheetIndex(0);
                //$feuilxl->setOffice2003Compatibility(true);
                $docxl->getActiveSheet(1)->setTitle('Résumé');


                //$feuilxl->mergeCells('A1:B3');

                $objDrawing = new PHPExcel_Worksheet_Drawing();
                $objDrawing->setName('Logo');
                $objDrawing->setDescription('logo');
                $Pathlogo = $conf->mycompany->dir_output .'/logos'."/thumbs/".MAIN_INFO_SOCIETE_LOGO_SMALL;
                if(!is_file ($Pathlogo))
                {
                    $Pathlogo = MAIN_INFO_SOCIETE_LOGO_SMALL;
                    if(!is_file($Pathlogo))
                    {
                        $conf->mycompany->dir_output .'/logos'."/thumbs/".$mysoc->nom."_small.png";
                        if(!is_file($Pathlogo))
                        {
                            $Pathlogo = false;
                        }
                    }
                }
                if($Pathlogo){
                    $objDrawing->setPath($Pathlogo);
                    $objDrawing->setHeight(36);
                    $objDrawing->setCoordinates('A1');
                    $objDrawing->setOffsetX(1);
                    $objDrawing->setWorksheet($feuilxl);
                }
                //$feuilxl->setCellValueByColumnAndRow(0, 2, 'Logo Babel');
                $feuilxl->setCellValueByColumnAndRow(2, 1, 'Généré le ');
                $feuilxl->setCellValueByColumnAndRow(3, 1, date('d/m/Y H:i'));
                $feuilxl->setCellValueByColumnAndRow(2, 2, 'Intervenant');
                $feuilxl->setCellValueByColumnAndRow(3, 2, utf8_encode($fuser->getFullName($langs)));


                $ligne = 4;



                $feuilxl->setCellValueByColumnAndRow(2, $ligne, 'Projet');
                $feuilxl->setCellValueByColumnAndRow(3, $ligne, 'Tâches');
                $feuilxl->setCellValueByColumnAndRow(4, $ligne, "Durée prévue (h)");
                $feuilxl->setCellValueByColumnAndRow(5, $ligne, "Durée effectuée (h)");

                $feuilxl->getColumnDimension('C')->setAutoSize(true);
                $feuilxl->getColumnDimension('D')->setWidth('80');
                $feuilxl->getColumnDimension('E')->setWidth('18');
                $feuilxl->getColumnDimension('F')->setWidth('18');


                $feuilxl->getRowDimension($ligne)->setRowHeight("28");
                $feuilxl->getStyle('C'.$ligne)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
                $feuilxl->getStyle('D'.$ligne.':F'.$ligne.'')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFont()->setBold(true);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);

                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFill()->getStartColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFont()->getColor()->setARGB('FFFFFFFF');

                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $feuilxl->getStyle('C'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $feuilxl->getStyle('D'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $feuilxl->getStyle('F'.$ligne.'')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                $feuilxl->getStyle('C'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('D'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('F'.$ligne)->getBorders()->getRight()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getTop()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getBottom()->getColor()->setARGB('AA0073EA');


                $requete = "SELECT p.title, p.ref, t.title as ttitle,
                                   (SELECT SUM(tt.task_duration) FROM ".MAIN_DB_PREFIX."projet_task_time as tt WHERE tt.fk_task = t.rowid AND tt.fk_user = a.fk_user ) as dur,
                                   (SELECT SUM(te.task_duration_effective) FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective as te WHERE te.fk_task = t.rowid AND te.fk_user = a.fk_user ) as durEff
                              FROM ".MAIN_DB_PREFIX."projet_task as t,
                                   ".MAIN_DB_PREFIX."Synopsis_projet_task_actors as a,
                                   ".MAIN_DB_PREFIX."Synopsis_projet_view as p
                             WHERE a.fk_projet_task = t.rowid
                               AND t.fk_projet = p.rowid
                               AND a.type='user'
                               AND a.fk_user = ".$fuser->id."
                          ORDER BY p.ref  ";

                $sql = $this->db->query($requete);
                $ligne++;
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getBottom()->getColor()->setARGB('FF000000');
                $ligne++;
                $remProj = false;
                $tot = 0;
                $totEff = 0;
                $num=0;
                $subtotal=0;
                $firstLigne = $ligne;
                $subtotalEff=0;
                $remLigne = false;


                while($res = $this->db->fetch_object($sql)){
                    if (!$remProj)
                    {
                        $remLigne = $ligne;
                        $num = 0;
                        $subtotal=0;
                        $subtotalEff=0;
                        $feuilxl->setCellValueByColumnAndRow(2, $ligne, utf8_encode($res->ref." - ".$res->title));
                        $feuilxl->getStyle('C'.$ligne)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
                        $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);

                        $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFill()->getStartColor()->setARGB('FFFFFFFF');
                        $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFont()->getColor()->setARGB('FF000000');

                        $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

                        $feuilxl->getStyle('C'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                        $feuilxl->getStyle('D'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('F'.$ligne.'')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                    } else if ($remProj != $res->ref){
                        if ($num > 1){
                            $feuilxl->mergeCells('C'.$remLigne.':C'.intval($ligne).'');
                            $feuilxl->getStyle('D'.$ligne.":F".$ligne)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_DOUBLE);
                            $feuilxl->getStyle('D'.$ligne.":F".$ligne)->getBorders()->getTop()->getColor()->setARGB('FF000000');
                            $feuilxl->getStyle('E'.$ligne.':F'.$ligne.'')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

                            $feuilxl->getStyle('D'.$ligne.':F'.$ligne.'')->getFont()->setBold(true);
                            $feuilxl->setCellValueByColumnAndRow(3, $ligne, utf8_encode("Total"));
                            $feuilxl->setCellValueByColumnAndRow(4, $ligne, utf8_encode(round($subtotal/36)/100));
                            $feuilxl->setCellValueByColumnAndRow(5, $ligne, utf8_encode(round($subtotalEff/36)/100));
                            $feuilxl->getRowDimension($ligne)->setRowHeight("18");
                            $ligne ++;
                        }
                        $feuilxl->getStyle('D'.$ligne.':F'.$ligne.'')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $feuilxl->getStyle('C'.$ligne.":F".$ligne)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                        $feuilxl->setCellValueByColumnAndRow(2, $ligne, utf8_encode($res->ref." - ".$res->title));
                        $remLigne = $ligne;
                        $num = 0;
                        $subtotal=0;
                        $subtotalEff=0;
                    }
                    $remProj = $res->ref;
                    $feuilxl->setCellValueByColumnAndRow(3, $ligne, utf8_encode($res->ttitle));
                    $feuilxl->setCellValueByColumnAndRow(4, $ligne, round($res->dur/36)/100);
                    $feuilxl->setCellValueByColumnAndRow(5, $ligne, round($res->durEff/36)/100);
                    $ligne ++;
                    $tot += $res->dur;
                    $totEff += $res->durEff;
                    $subtotal += $res->dur;
                    $subtotalEff += $res->durEff;
                    $num++;
                    //border
                    $feuilxl->getRowDimension($ligne)->setRowHeight("16");
                    $feuilxl->getStyle('C'.$ligne)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

                    $feuilxl->getStyle('C'.$ligne)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('C'.$ligne)->getBorders()->getRight()->getColor()->setARGB('FF000000');
                    $feuilxl->getStyle('D'.$ligne)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('D'.$ligne)->getBorders()->getRight()->getColor()->setARGB('FF000000');
                    $feuilxl->getStyle('E'.$ligne)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('E'.$ligne)->getBorders()->getRight()->getColor()->setARGB('FF000000');

                    $feuilxl->getStyle('C'.$ligne.":F".$ligne)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('C'.$ligne.":F".$ligne)->getBorders()->getTop()->getColor()->setARGB('FF000000');

                }
                if($num> 1)
                {
                    $feuilxl->getRowDimension($ligne)->setRowHeight("14");
                    $feuilxl->mergeCells('C'.$remLigne.':C'.intval($ligne).'');
                    $feuilxl->getStyle('D'.$ligne.":F".$ligne)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_DOUBLE);
                    $feuilxl->getStyle('D'.$ligne.":F".$ligne)->getBorders()->getTop()->getColor()->setARGB('FF000000');
                    $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFont()->setBold(true);
                    $feuilxl->setCellValueByColumnAndRow(3, $ligne, utf8_encode("Total"));
                    $feuilxl->setCellValueByColumnAndRow(4, $ligne, utf8_encode(round($subtotal/36)/100));
                    $feuilxl->setCellValueByColumnAndRow(5, $ligne, utf8_encode(round($subtotalEff/36)/100));
                    $ligne ++;
                }
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getTop()->getColor()->setARGB('FF000000');

                $feuilxl->getStyle('C'.$firstLigne.':C'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $feuilxl->getStyle('C'.$firstLigne.':C'.$ligne.'')->getBorders()->getLeft()->getColor()->setARGB('FF000000');
                $feuilxl->getStyle('F'.$firstLigne.':F'.$ligne)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $feuilxl->getStyle('F'.$firstLigne.':F'.$ligne.'')->getBorders()->getRight()->getColor()->setARGB('FF000000');


                $ligne++;
                $feuilxl->mergeCells('C'.$ligne.':D'.$ligne.'');


                $feuilxl->getRowDimension($ligne)->setRowHeight("28");
                $feuilxl->setCellValueByColumnAndRow(2, $ligne, 'Total');
                $feuilxl->setCellValueByColumnAndRow(4, $ligne, round($tot/36)/100);
                $feuilxl->setCellValueByColumnAndRow(5, $ligne, round($totEff/36)/100);

                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFont()->setBold(true);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);

                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFill()->getStartColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getFont()->getColor()->setARGB('FFFFFFFF');

                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

                $feuilxl->getStyle('C'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $feuilxl->getStyle('D'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $feuilxl->getStyle('F'.$ligne.'')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                $feuilxl->getStyle('C'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('D'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('F'.$ligne)->getBorders()->getRight()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getTop()->getColor()->setARGB('AA0073EA');
                $feuilxl->getStyle('C'.$ligne.':F'.$ligne.'')->getBorders()->getBottom()->getColor()->setARGB('AA0073EA');


                $iter = 1;
                $requete = "SELECT DISTINCT p.rowid, p.title, p.ref
                              FROM ".MAIN_DB_PREFIX."projet_task as t,
                                   ".MAIN_DB_PREFIX."Synopsis_projet_task_actors as a,
                                   ".MAIN_DB_PREFIX."Synopsis_projet_view as p
                             WHERE a.fk_projet_task = t.rowid
                               AND t.fk_projet = p.rowid
                               AND a.type='user'
                               AND a.fk_user = ".$fuser->id."
                          ORDER BY p.ref";
                $sql = $this->db->query($requete);
                while($res=$this->db->fetch_object($sql))
                {
                    $docxl->createSheet();
                    $docxl->setActiveSheetIndex($iter);
                    $docxl->getActiveSheet($iter)->setTitle(utf8_encode(dol_trunc($res->ref." ".$res->title,30,'right',false)));

                    $feuilxl = $docxl->getActiveSheet();

                    $docxl->getActiveSheet()->getProtection()->setSheet(false);

                    $feuilxl->getDefaultStyle()->getFont()->setName('Arial');
                    $feuilxl->getDefaultStyle()->getFont()->setSize(12);



                    $objDrawing = new PHPExcel_Worksheet_Drawing();
                    $objDrawing->setName('Logo');
                    $objDrawing->setDescription('logo');
                    $Pathlogo = $conf->mycompany->dir_output .'/logos'."/thumbs/".MAIN_INFO_SOCIETE_LOGO_SMALL;
                    if(!is_file ($Pathlogo))
                    {
                        $Pathlogo = MAIN_INFO_SOCIETE_LOGO_SMALL;
                        if(!is_file($Pathlogo))
                        {
                            $conf->mycompany->dir_output .'/logos'."/thumbs/".$mysoc->nom."_small.png";
                            if(!is_file($Pathlogo))
                            {
                                $Pathlogo = false;
                            }
                        }
                    }
                    if($Pathlogo){
                        $objDrawing->setPath($Pathlogo);
                        $objDrawing->setHeight(36);
                        $objDrawing->setCoordinates('A1');
                        $objDrawing->setOffsetX(1);
                        $objDrawing->setWorksheet($feuilxl);
                    }



                    //$feuilxl->setCellValueByColumnAndRow(0, 2, 'Logo Babel');
                    $feuilxl->setCellValueByColumnAndRow(2, 1, 'Généré le ');
                    $feuilxl->setCellValueByColumnAndRow(3, 1, date('d/m/Y H:i'));
                    $feuilxl->setCellValueByColumnAndRow(2, 2, 'Intervenant');
                    $feuilxl->setCellValueByColumnAndRow(3, 2, utf8_encode($fuser->getFullName($langs)));
                    $ligne = 6;
//Cartouche Projet

                    $proj = new Project($this->db);
                    $proj->fetch($res->rowid);
                    $titre = $res->title;
                    $ref = $res->ref;
                    $societe = $proj->societe->nom;
                    $statut = $proj->getLibStatut('1');
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($proj->user_resp_id);
                    $resp = $tmpUser->getFullName($langs) ." <".$tmpUser->email.">";

                    $ligne ++;

                    $feuilxl->setCellValueByColumnAndRow(3, $ligne, utf8_encode("Ref : ".$ref));
                    $feuilxl->setCellValueByColumnAndRow(4, $ligne, utf8_encode("Nom du projet :"));
                    $feuilxl->setCellValueByColumnAndRow(5, $ligne, utf8_encode($titre));
                    $feuilxl->setCellValueByColumnAndRow(6, $ligne, utf8_encode("Client : "));
                    $feuilxl->setCellValueByColumnAndRow(7, $ligne, utf8_encode($societe));
                    $feuilxl->setCellValueByColumnAndRow(8, $ligne, utf8_encode("Resp. du projet : "));
                    $feuilxl->setCellValueByColumnAndRow(9, $ligne, utf8_encode($resp));

                    $feuilxl->getRowDimension($ligne)->setRowHeight("28");
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFont()->setBold(true);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);

                    $feuilxl->getStyle('D'.$ligne)->getFill()->getStartColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('D'.$ligne)->getFont()->getColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('E'.$ligne)->getFill()->getStartColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('E'.$ligne)->getFont()->getColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('G'.$ligne)->getFill()->getStartColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('G'.$ligne)->getFont()->getColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('I'.$ligne)->getFill()->getStartColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('I'.$ligne)->getFont()->getColor()->setARGB('FFFFFFFF');

                    $feuilxl->getStyle('F'.$ligne)->getFill()->getStartColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('F'.$ligne)->getFont()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('H'.$ligne)->getFill()->getStartColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('H'.$ligne)->getFont()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('J'.$ligne)->getFill()->getStartColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('J'.$ligne)->getFont()->getColor()->setARGB('AA0073EA');


//                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFill()->getStartColor()->setARGB('FFFFFFFF');
//                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFont()->getColor()->setARGB('AA0073EA');

                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('D'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('G'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('H'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('I'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('J'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('J'.$ligne.'')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                    $feuilxl->getStyle('D'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('G'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('H'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('I'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('J'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('J'.$ligne)->getBorders()->getRight()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getTop()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getBottom()->getColor()->setARGB('AA0073EA');


                    $ligne ++;
                    $ligne ++;

                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFont()->setBold(true);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFill()->getStartColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFont()->getColor()->setARGB('AA0073EA');

                    $feuilxl->getRowDimension($ligne)->setRowHeight("28");


                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('D'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('G'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('H'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('I'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('J'.$ligne)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $feuilxl->getStyle('J'.$ligne.'')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                    $feuilxl->getStyle('D'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('E'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('F'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('G'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('H'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('I'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('J'.$ligne)->getBorders()->getLeft()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('J'.$ligne)->getBorders()->getRight()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getTop()->getColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getBottom()->getColor()->setARGB('AA0073EA');

                    $feuilxl->setCellValueByColumnAndRow(3, $ligne, "Tâche");
                    $feuilxl->setCellValueByColumnAndRow(4, $ligne, "Rôle");
                    $feuilxl->setCellValueByColumnAndRow(5, $ligne, "Début tâche");
                    $feuilxl->setCellValueByColumnAndRow(6, $ligne, "Prévue");
                    $feuilxl->setCellValueByColumnAndRow(7, $ligne, "Effectuée");
                    $feuilxl->setCellValueByColumnAndRow(8, $ligne, "Statut");
                    $feuilxl->setCellValueByColumnAndRow(9, $ligne, "Note");
                    $ligne ++;

                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                    $ligne ++;


//Col, duree prev, effectué, tache, nom tache, role, note, date deb
                    $requete1 = "SELECT t.title,
                                        a.role,
                                        t.dateDeb,
                                        t.statut,
                                        t.note,
                                        (SELECT SUM(task_duration) FROM ".MAIN_DB_PREFIX."projet_task_time as tt WHERE tt.fk_task=t.rowid AND tt.fk_user = a.fk_user ) as task_duration,
                                        (SELECT SUM(task_duration_effective) FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective as te WHERE te.fk_task=t.rowid AND te.fk_user = a.fk_user ) as task_duration_effective
                                   FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors as a,
                                        ".MAIN_DB_PREFIX."projet_task as t
                                  WHERE t.fk_projet = ".$res->rowid."
                                    AND t.rowid = a.fk_projet_task
                                    ANd a.fk_user = ".$fuser->id;
                    $sql1 = $this->db->query($requete1);
                    $tot = 0;
                    $totEff = 0;
                    $feuilxl->getColumnDimension('D')->setWidth('36');
                    $feuilxl->getColumnDimension('E')->setWidth('18');
                    $feuilxl->getColumnDimension('F')->setWidth('18');
                    $feuilxl->getStyle('F'.$ligne)->getNumberFormat()->setFormatCode('d/m/yyyy h:mm');
                    $feuilxl->getColumnDimension('G')->setWidth('18');
                    $feuilxl->getColumnDimension('H')->setWidth('18');
                    $feuilxl->getColumnDimension('I')->setWidth('14');
                    $feuilxl->getColumnDimension('J')->setAutoSize(true);

                    while($res1=$this->db->fetch_object($sql1))
                    {
                        $feuilxl->getRowDimension($ligne)->setRowHeight("15");

                        $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('D'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                        $feuilxl->getStyle('E'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('F'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('G'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('H'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('I'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('J'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                        $feuilxl->getStyle('J'.$ligne.'')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                        $feuilxl->getStyle('E'.$ligne.':J'.$ligne.'')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                        $feuilxl->setCellValueByColumnAndRow(3, $ligne, utf8_encode($res1->title));
                        $feuilxl->setCellValueByColumnAndRow(4, $ligne, utf8_encode($langs->trans($res1->role)));
                        $feuilxl->setCellValueByColumnAndRow(5, $ligne, date('d/m/Y H:i',strtotime($res1->dateDeb)));
                        $feuilxl->setCellValueByColumnAndRow(6, $ligne, round($res1->task_duration/36)/100);
                        $feuilxl->setCellValueByColumnAndRow(7, $ligne, round($res1->task_duration_effective/36)/100);
                        $feuilxl->setCellValueByColumnAndRow(8, $ligne, $langs->trans($res1->statut));
                        $feuilxl->setCellValueByColumnAndRow(9, $ligne, utf8_encode($res1->note));
                        $ligne ++;
                        $tot += $res1->task_duration;
                        $totEff += $res1->task_duration_effective;
                    }
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                    $ligne ++;

                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFont()->setBold(true);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFill()->getStartColor()->setARGB('AA0073EA');
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getFont()->getColor()->setARGB('FFFFFFFF');

                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('D'.$ligne.'')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getStyle('J'.$ligne.'')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $feuilxl->getRowDimension($ligne)->setRowHeight("22");

                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getTop()->getColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('D'.$ligne.':J'.$ligne.'')->getBorders()->getBottom()->getColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('D'.$ligne.'')->getBorders()->getLeft()->getColor()->setARGB('FFFFFFFF');
                    $feuilxl->getStyle('J'.$ligne.'')->getBorders()->getRight()->getColor()->setARGB('FFFFFFFF');


                    $feuilxl->mergeCells('D'.$ligne.':F'.$ligne.'');

                    $feuilxl->setCellValueByColumnAndRow(3, $ligne, "Total");
                    $feuilxl->setCellValueByColumnAndRow(6, $ligne, round($tot/36)/100);
                    $feuilxl->setCellValueByColumnAndRow(7, $ligne, round($totEff/36)/100);





                    $iter ++;
                }

//                $feuilxl = $docxl->getActiveSheet();
//
//                $docxl->getActiveSheet()->getProtection()->setSheet(false);
//
//                $feuilxl->getDefaultStyle()->getFont()->setName('Arial');
//                $feuilxl->getDefaultStyle()->getFont()->setSize(12);


                $writer = new PHPExcel_Writer_Excel2007($docxl);

                $ref = sanitize_string("Imputations-".date('Y').'-'.$fuser->login);
                $dir = $conf->imputations->dir_output . "/" . $ref;
                $file = $dir . "/" . $ref . ".xlsx";


                $writer->setOffice2003Compatibility(true);
                $writer->save($file);
                return 1;


        } else {
            $this->error=$langs->trans("ErrorConstantNotDefined","IMPUT_OUTPUTDIR");
            $langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error=$langs->trans("ErrorUnknown");
        $langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }
}

?>

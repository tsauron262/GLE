<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 5 avr. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : test.php
  * dolibarr-24dev
  */

//include les fichiers dolibarr pour la databases etc ...

require_once('../../../master.inc.php');





//charge les données
require_once('../deplacement.class.php');
$deplacement = new Ndf($db);
$deplacement->id = 3;
$deplacement->fetch(3);

$requetea = "SELECT ".MAIN_DB_PREFIX."deplacement.fk_soc as soc_refid," .
        "          ".MAIN_DB_PREFIX."societe.nom as sname, " .
        "          ".MAIN_DB_PREFIX."c_deplacement.libelle as typename, " .
        "          ".MAIN_DB_PREFIX."deplacement.note," .
        "          ".MAIN_DB_PREFIX."c_deplacement.id as catid, " .
        "          ".MAIN_DB_PREFIX."deplacement.prix_HT," .
        "          ".MAIN_DB_PREFIX."deplacement.rowid as id," .
        "          ".MAIN_DB_PREFIX."deplacement.note as note," .
        "          ".MAIN_DB_PREFIX."deplacement.Km, " .
        "          date(".MAIN_DB_PREFIX."deplacement.dated) as sqldate," .
        "          month(".MAIN_DB_PREFIX."deplacement.dated) as month, " .
        "          year(".MAIN_DB_PREFIX."deplacement.dated) as year," .
        "          day(".MAIN_DB_PREFIX."deplacement.dated) as day," .
        "          ".MAIN_DB_PREFIX."c_deplacement.isKm as isKm," .
        "          ".MAIN_DB_PREFIX."c_deplacement.pdfCat as catName, " .
        "          ".MAIN_DB_PREFIX."c_deplacement.id as pdfCatId, " .
        "          ".MAIN_DB_PREFIX."c_deplacement.code as pdfCatCode, " .
        "          ".MAIN_DB_PREFIX."c_deplacement.pdfCat as pdfCat," .
        "          ".MAIN_DB_PREFIX."c_deplacement.pdfKmIsOther as isOther," .
        "          ".MAIN_DB_PREFIX."deplacement.tva_taux as TVAstr," .
        "          ".MAIN_DB_PREFIX."deplacement.tva_taux as TVA " .
        "     FROM ".MAIN_DB_PREFIX."deplacement," .
        "          ".MAIN_DB_PREFIX."c_deplacement," .
        "          ".MAIN_DB_PREFIX."societe," .
        "          Babel_ndf " .
        "    WHERE ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode " .
        "      AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)" .
        "      AND Babel_ndf.id = 3" .
        "      AND ".MAIN_DB_PREFIX."c_deplacement.id = ".MAIN_DB_PREFIX."deplacement.type_refid " .
        "      AND ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."deplacement.fk_soc" .
        "       " .
        " ORDER BY ".MAIN_DB_PREFIX."deplacement.dated";
        $sql=$db->query($requetea);

                //Titres des colonnes
                $header1=array('Date','Client');
                $header2 = array();
                $header2 = array('Trajet','Repas',"Hotel","Autre");
                $colarray=array();
                $colarray2[1]="TF_LUNCH"; // Repas
                $colarray2[0]="TF_TRIP"; // Trajet
                $colarray2[3]="TF_OTHER"; // Autre
                $colarray2[2]="TF_HOTEL"; // Hotel

                $header3 = array('Total HT','TVA','Total TVA','Total TTC');
                $header = array_merge($header1, $header2,$header3);


//        var_dump($this->db);
        $data= array();
    if ($sql)
    {
        $iter=0;
        $KmCol = 0;
        $totKmMois = 0;

        while ($res=$db->fetch_object($sql))
        {
            $socid = $res->soc_refid;
            $socname = '';
            if ($socid < 1)
            {
                $socname = MAIN_INFO_SOCIETE_NOM;
            } else {
                $socname = $res->sname;
            }
            $sqlday = $res->day;
            if (strlen($sqlday) == 1){ $sqlday = "0".$sqlday;}
            $sqlmonth = $res->month;
            if (strlen($sqlmonth) == 1){ $sqlmonth = "0".$sqlmonth;}
            $array[0]=$sqlday.'/'.$sqlmonth.'/'.$res->year;
            $array[1]=$socname;
            $isKM = $res->isKm;
            $pdfcatid = $res->pdfCat;
            $isother = $res->isOther;
//            $pdfkey = array_search($res->pdfCatId, $colarray2);
            $pdfkey="";
            foreach($colarray2 as $key=>$val)
            {
                if ($val == $res->pdfCatCode)
                {
                    $pdfkey = $key;
                }
            }
            $array[999]=0;
            $array[998]=0;
            for ($i=0;$i<5 ; $i++)
            {
                if (($i == $pdfkey) && ($isother != 1) && $isKM == 0)
                {
                    $array[$i+2] = $res->prix_HT;
                } else if (($i == $pdfkey) && ($isother == 1)&& $isKM == 0)
                {
                    $array[$i+2] = $res->note;
                    $array[999] = $res->prix_HT;
                } else if (($i == $pdfkey) && ($isother != 1)&& $isKM == 1)
                {
                    $array[$i+2] = $res->Km ;
                    $KmCol = $i +2;
                } else if (($i == $pdfkey) && ($isother == 1)&& $isKM == 1)
                {
                    $array[$i+2] = $res->note;
                    $array[999] = $res->prix_HT;
                } else  {
                    $array[$i+2]=' - ';
                }
            }
            $new_idx  = 7;
            //Prob avec les Km:
            $montantHT = 0;
            if ($isKM == 1)
            {
                //on place un tag pour calculer plus tard
                $array[$new_idx]=0;
                $array[998] = "[X]*".$array[$KmCol];
                $totKmMois += $array[$KmCol];
            } else {
                $array[$new_idx]=$res->prix_HT;
                $montantHT=$res->prix_HT;
            }
            $new_idx++;
            $array[$new_idx]=round($res->TVAstr,2)."%"; // get TVA string
            $new_idx++;
            $tvatx = $res->TVA;
            $array[$new_idx]= round(floatval($montantHT) * floatval($tvatx/100),2) ; // get TVA tx
            $new_idx++;
            $array[$new_idx]= round($montantHT * (1 + $tvatx/100),2); // * 1 + get TVA tx
            $new_idx++;
            $data[$iter]=$array;
            $iter++;
        }

    }
    set_include_path(get_include_path() . PATH_SEPARATOR . DOL_DOCUMENT_ROOT.'/Babel_TechPeople/deplacements/philou/PHPExcel-1.6.6');



//Charge l'utilisateur de la ndf

$fuser = new User($db);
$fuser->id = $deplacement->fk_user_author;
$fuser->fetch();

require_once("PHPExcel.php");
require_once("PHPExcel/Writer/Excel2007.php");

$docxl = new PHPExcel;

$feuilxl = $docxl->getActiveSheet();

//var_dump($data); // tous le tableau
//var_dump($data[1]); // la ligne 1
//var_dump($data[1][3]); //la 3eme col de la 1ere ligne

$feuilxl->mergeCells('C1:E1');
$feuilxl->setCellValueByColumnAndRow(0, 2, 'Logo Babel');
$feuilxl->setCellValueByColumnAndRow(2, 1, 'Note de frais pour le periode:');
$feuilxl->setCellValueByColumnAndRow(5, 1, date('m-Y',$deplacement->tsperiode));
$feuilxl->setCellValueByColumnAndRow(2, 2, 'De:');
$feuilxl->setCellValueByColumnAndRow(3, 2, $fuser->fullname);
$feuilxl->setCellValueByColumnAndRow(3, 3, 'Seuil:');

$tmpSeuil = $deplacement->getSeuil($tmpNbrKm);
if (!$tmpSeuil) { $tmpSeuil = ' - '; }
$feuilxl->setCellValueByColumnAndRow(4, 3, $tmpSeuil);
$feuilxl->setCellValueByColumnAndRow(5, 3, 'Total Km:');

$tmpNbrKm = $deplacement->getKm(1) + $deplacement->getKm(3);
if (!$tmpNbrKm) { $tmpNbrKm = ' - '; }
$feuilxl->setCellValueByColumnAndRow(6, 3, $tmpNbrKm);
$feuilxl->setCellValueByColumnAndRow(7, 3, 'km');
$feuilxl->setCellValueByColumnAndRow(8, 3, 'CV:');
$feuilxl->setCellValueByColumnAndRow(9, 3, $fuser->CV_ndf);

foreach($header as $numColHeader => $ContenuHeader)
{
     $feuilxl->setCellValueByColumnAndRow($numColHeader, 5, $ContenuHeader);
}

foreach($data as $numLigne => $ContenuLigne)
{
    //ContenuLigne est un array; il contient toute la ligne
    //numLigne est le num de Ligne en commencant a zero

    foreach($ContenuLigne as $numCol => $ContenuCol)
    {
        //numCol est le num de Ligne en commencant à 0
        //ContenuCol est le num de Ligne
        if($ContenuCol == "&nbsp;"){$ContenuCol = ' - ';}
        if($numCol<998)
        {
            if($numCol == 0 )  { $feuilxl->setCellValueByColumnAndRow(0, $numLigne+6, "=\"".$ContenuCol."\""); }
            if($numCol == 1 )  { $feuilxl->setCellValueByColumnAndRow(1, $numLigne+6, $ContenuCol); }
            if($numCol == 2 )
            {
                settype($ContenuCol, 'int');
                if ($ContenuCol == 0) { $ContenuCol =' - ';}
                $feuilxl->setCellValueByColumnAndRow(2, $numLigne+6, $ContenuCol);
            }
            if($numCol == 3 )
            {
                settype($ContenuCol, 'int');
                if ($ContenuCol == 0) { $ContenuCol =' - ';}
                $feuilxl->setCellValueByColumnAndRow(3, $numLigne+6, $ContenuCol);
            }
            if($numCol == 4 )
            {
                settype($ContenuCol, 'int');
                if ($ContenuCol == 0) { $ContenuCol =' - ';}
                $feuilxl->setCellValueByColumnAndRow(4, $numLigne+6, $ContenuCol);
            }
            if($numCol == 5 )
            {
                $feuilxl->setCellValueByColumnAndRow(5, $numLigne+6, $ContenuCol);
            }
            if($numCol == 6 )
            {
                settype($ContenuCol, 'int');
                if ($ContenuCol == 0) { $ContenuCol =' - ';}
                $feuilxl->setCellValueByColumnAndRow(6, $numLigne+6, $ContenuCol);
            }
            if($numCol == 8 )
            {
                $ContenuCol = preg_replace('/%$/','' ,$ContenuCol);
                settype($ContenuCol ,'string');
                $ContenuCol = preg_replace('/\,/','.',$ContenuCol);
                $ContenuCol /= 100;
                $ContenuCol = preg_replace('/\,/','.',$ContenuCol);
                $feuilxl->setCellValueExplicitByColumnAndRow(7, $numLigne + 6, $ContenuCol." ",PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $feuilxl->getStyleByColumnAndRow(7,$numLigne+6)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00);
            }
            if($numCol == 9 )  { $feuilxl->setCellValueByColumnAndRow(8, $numLigne+6, $ContenuCol); }
            if($numCol == 10 ) { $feuilxl->setCellValueByColumnAndRow(9, $numLigne+6, $ContenuCol); }
        }
        // Style de colonne
        // Date
        $feuilxl->getStyleByColumnAndRow(0,$numLigne+6)->getNumberFormat()->applyFromArray(
                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY ));
        $feuilxl->getStyleByColumnAndRow(2,$numLigne+6)->getNumberFormat()->applyFromArray(
                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER ));
        $feuilxl->getStyleByColumnAndRow(3,$numLigne+6)->getNumberFormat()->applyFromArray(
                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00 ));
        $feuilxl->getStyleByColumnAndRow(4,$numLigne+6)->getNumberFormat()->applyFromArray(
                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00 ));
        $feuilxl->getStyleByColumnAndRow(5,$numLigne+6)->getNumberFormat()->applyFromArray(
                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00 ));
        $feuilxl->getStyleByColumnAndRow(6,$numLigne+6)->getNumberFormat()->applyFromArray(
                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00 ));
//        $feuilxl->getStyleByColumnAndRow(7,$numLigne+6)->getNumberFormat()->applyFromArray(
//                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00 ));
        //         array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00 ));
        $feuilxl->getStyleByColumnAndRow(8,$numLigne+6)->getNumberFormat()->applyFromArray(
                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00 ));
        $feuilxl->getStyleByColumnAndRow(9,$numLigne+6)->getNumberFormat()->applyFromArray(
                 array( 'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00 ));
    }
}
$feuilxl->setCellValueByColumnAndRow(2, $numLigne+7, $deplacement->getKm(1));
$feuilxl->setCellValueByColumnAndRow($numCol-2, $numLigne+7, 'Total HT:');
$feuilxl->setCellValueByColumnAndRow($numCol-2, $numLigne+8, 'Total TVA:');
$feuilxl->setCellValueByColumnAndRow($numCol-2, $numLigne+9, 'Total TTC:');

$feuilxl->setCellValueByColumnAndRow(1, $numLigne+12, 'Le:');
$feuilxl->setCellValueByColumnAndRow(2, $numLigne+12, date('d/m/Y'));
$feuilxl->setCellValueByColumnAndRow(4, $numLigne+12, 'Signature:');
$feuilxl->setCellValueByColumnAndRow(6, $numLigne+12, 'Validation:');

// Formulas
$tmpLigneFormules = $numLigne+6;
$feuilxl->setCellValueByColumnAndRow(3, $numLigne+7, '=SUM(D6:D'.$tmpLigneFormules.')');
$feuilxl->setCellValueByColumnAndRow(4, $numLigne+7, '=SUM(E6:E'.$tmpLigneFormules.')');
$feuilxl->setCellValueByColumnAndRow($numCol-1, $numLigne+7, '=SUM(G6:G'.$tmpLigneFormules.')');
$feuilxl->setCellValueByColumnAndRow($numCol-1, $numLigne+8, '=SUM(I6:I'.$tmpLigneFormules.')');
$feuilxl->setCellValueByColumnAndRow($numCol-1, $numLigne+9, '=SUM(J6:J'.$tmpLigneFormules.')');

$writer = new PHPExcel_Writer_Excel2007($docxl);
$records = './ResultXLSeos.xlsx';
$writer->save($records);
?>

<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
//$Date="Mai 2007";
$userid=$_COOKIE['rowid'];
if ($userid . "x" == "x") { $userid = 2;}
//$year=$_POST['year'];
$year = date('Y');
$month=date('m');
//$month=$_POST['month'];
$debug = 0;
if ($debug == 1 )
{
    $userid=2;
    $year=2007;
    $month = 5;
}
if ('x'.$userid == 'x') { exit(0); }

require_once('../fpdf/fpdf.php');
require_once('../Class/pdftableau.class.php');
//require_once('../class/log.class.php');
require_once("../Class/BD.class.php");
//require_once("class/ndf.class.php");
require_once('../Config/parametres.php');

//$log=new log();
//$log->addlog('generate pdf:'.$month.'-'.$year,3,$userid);

$Date = $fr[$month]." ".$year;

$db=new BD();
//$ndf=new ndf();
$pdf=new PDF('L','mm','A4');
//utilisateur
//$ndf->detail_user($userid,"2007");
$requete = "SELECT user.name as uname," .
        "          user.firstname as ufirstname," .
        "          user.email as uemail," .
        "          distance.seuil as dseuil," .
        "          distance.name as dname," .
        "          kilometrage.cv as kcv," .
        "          kilometrage.math as kmath" .
        "           " .
        "     FROM user," .
        "          li_user_dist," .
        "          kilometrage, " .
        "          distance " .
        "    WHERE user_refid = ".$userid .
        "      AND user.id = li_user_dist.user_refid" .
        "      AND li_user_dist.kilometrage_refid = kilometrage.id" .
        "      AND kilometrage.distance_refid = distance.id ";
$sql = $db->execRequete($requete);
$res=$db->objetSuivant($sql);
$prenom = $res->ufirstname;
$nom = $res->uname;
$email = "<".$res->uemail .">";
$cv = $res->kcv;
$seuil = $res->dseuil;
$math = $res->kmath;
$math = preg_replace('/,/',".",$math);
$seuilname = $res->dname;

$totPartielKm = 0;

$requete1= "SELECT * " .
        "     FROM frais, " .
        "          category " .
        "    WHERE user_refid = ".$_COOKIE['rowid'] . " " .
        "      AND category.id = frais.category_refid " .
        //"      AND frais.was_validate = 0" .
        "     ";
$sql1 = $db->execRequete($requete1);
$res1 = $db->objetSuivant($sql1);
$totalKm = 0;
$totann = 0;
if ($res1->isKM == 1 && $res1->was_validate == 0)
{
    $totalKm += $res1->Km;
    $totann += $res1->Km;
} else if ($res1->isKM == 0 && $res1->was_validate == 0){
    $totalFrais += $res1->montantHT;
}else if ($res1->isKM == 1 && $res1->was_validate == 1 ){
    //Le totParteil est initialiser a ce qui a �t� parcouru
    $totPartielKm += $res1->Km;
    $totann += $res1->Km;
}



//$prenom=$ndf->;
//$nom=$ndf->nom;
//$email='<'.$ndf->email.'>';
//$cv=$ndf->cv;
//$seuil=$ndf->dname;
//$totalKm = $ndf->totalKm;


//Titres des colonnes
$header1=array('Date','D�signation','Client');
$requetecath = "SELECT * FROM pdf_gen_category ORDER BY is_other ASC ";
$sqlcath=$db->execRequete($requetecath);
$header2 = array();
$colarray=array();
$iter=0;

while ($rescath=$db->objetSuivant($sqlcath))
{
    $headertmp = array($rescath->name);
    $header2 = array_merge($header2,$headertmp);
    $colarray2[$iter]=$rescath->id;
    $iter ++;
}

$header3 = array('Total HT','TVA','Total TVA','Total TTC');
$header = array_merge($header1, $header2,$header3);
//$header = array_combine($header, $header3);

//Chargement des donnees
$requetea = "SELECT frais.societe_refid as soc_refid," .
        "          societe.name as sname, " .
        "          type.name as typename, " .
        "          frais.designation," .
        "          frais.contact_refid," .
        "          frais.category_refid as catid, " .
        "          frais.montantHT," .
        "          frais.id," .
        "          frais.Km, " .
        "          date(frais.Date) as sqldate," .
        "          month(frais.date) as month, " .
        "          year(frais.date) as year," .
        "          day(frais.date) as day," .
        "          category.isKm as isKm," .
        "          category.name as catName, " .
        "          category.pdfcategory_refid as pdfCat," .
        "          pdf_gen_category.is_other as isOther," .
        "          tva.TVAstr as TVAstr," .
        "          tva.TVA as TVA " .
        "     FROM frais," .
        "          type," .
        "          category," .
        "          societe," .
        "          pdf_gen_category," .
        "           tva  " .
        "    WHERE frais.user_refid = ".$userid."" .
        "      AND was_validate=0 " .
        "      AND frais.type_refid = type.id" .
        "      AND societe.id = frais.societe_refid" .
        "      AND category.id = frais.category_refid " .
        "      AND category.tva_refid = tva.id" .
        "      AND pdf_gen_category.id = category.pdfcategory_refid" .
        "       " .
        " ORDER BY frais.date";
$sql=$db->execRequete($requetea);
$data= array();
$iter=0;
while ($res=$db->objetSuivant($sql))
{
    $socid = $res->soc_refid;
    $socname = '';
    if ($socid < 1)
    {
        $socname = "Babel Services";
    } else {
        $socname = $res->sname;
    }
    $sqlday = $res->day;
    if (strlen($sqlday) == 1){ $sqlday = "0".$sqlday;}
    $sqlmonth = $res->month;
    if (strlen($sqlmonth) == 1){ $sqlmonth = "0".$sqlmonth;}
    $array[0]=$sqlday.'/'.$sqlmonth.'/'.$res->year;
    $array[1]=$res->designation;
    $array[2]=$socname;
    $isKM = $res->isKm;
    $pdfcatid = $res->pdfCat;
    $isother = $res->isOther;
    $KmCol = 0;
    $pdfkey = array_search($pdfcatid, $colarray2);
    for ($i=0;$i<5 ; $i++)
    {
        if (($i == $pdfkey) && ($isother != 1) && $isKM == 0)
        {
            $array[$i+3] = $res->montantHT;
        } else if (($i == $pdfkey) && ($isother == 1)&& $isKM == 0)
        {
            $array[7] = $res->catName;
        } else if (($i == $pdfkey) && ($isother != 1)&& $isKM == 1)
        {
            $array[$i+3] = $res->Km ;
            $KmCol = $i + 3;
        } else if (($i == $pdfkey) && ($isother == 1)&& $isKM == 1)
        {
            $array[7] = $res->catName;
        } else  {
            $array[$i+3]=' - ';
        }
    }
    $new_idx  = 8;
    //Prob avec les Km:
    $montantHT = 0;
    if ($isKM == 1)
    {
        $mathexpr =  preg_replace('/\[X\]/i',$totPartielKm,$math);
        $mathexpr = preg_replace('`([^+\-*=/\(\)\d\^<>&|\.]*)`','',$mathexpr);
        eval("\$mathexpr = $mathexpr;");

        $totPartielKm += $res->Km;
        $mathexpr1 =  preg_replace('/\[X\]/i',$totPartielKm,$math);
        $mathexpr1 = preg_replace('`([^+\-*=/\(\)\d\^<>&|\.]*)`','',$mathexpr1);
        eval("\$mathexpr1 = $mathexpr1;");

        $array[$new_idx]=$mathexpr1 - $mathexpr;
        $montantHT=$mathexpr1 - $mathexpr;
    } else {
        $array[$new_idx]=$res->montantHT;
        $montantHT=$res->montantHT;
    }
    $new_idx++;
    $array[$new_idx]=$res->TVAstr; // get TVA string
    $new_idx++;
    $tvatx = $res->TVA;
    $array[$new_idx]=$montantHT * $tvatx; // get TVA tx
    $new_idx++;
    $array[$new_idx]=$montantHT * (1 + $tvatx); // * 1 + get TVA tx
    $new_idx++;
    $data[$iter]=$array;
    $iter++;
}
$pdf->SetFont('Arial','',14);
$pdf->AddPage();
$pdf->Image('../img/Synopsis et DRSI-logo.png',275 ,0, 20,35);
$pdf->SetFont('Arial','B',16);
$pdf->SetXY(10,10);
$pdf->Cell(40,10,'Note de frais du mois de '.$Date);
$pdf->SetFont('Arial','',12);
$pdf->SetXY(15,5);
$pdf->Cell(200,30,'De '.$prenom.' '.$nom.' '.$email);
$pdf->SetXY(15,10);
$pdf->Cell(20,30,'Seuil Km: '.$seuil);
$pdf->SetXY(75,10);
$pdf->Cell(20,30,'Total Km: '.$totann." Km");

$pdf->SetXY(15,15);
$pdf->Cell(20,30,'CV: '.$cv);
$pdf->SetXY(10,35);
$pdf->SetFont('Arial','',10);
//$newY=$pdf->FancyTable($header,$data,$KmCol,$ndf->avance, $ndf->solde);
$newY=$pdf->FancyTable($header,$data,$KmCol,0, 0);
$newY -= 5;
$pdf->SetFont('Arial','B',12);
$pdf->SetXY(15,$newY);
$pdf->Cell(20,6,'Le: '.date("d-m-Y"),'',0,'C',0);
$pdf->SetXY(55,$newY);
$pdf->Cell(20,6,'Signature:','',0,'C',0);
$pdf->SetXY(95,$newY);
$pdf->Cell(20,6,'Validation:','',0,'C',0);

//echo $KmCol;
$pdf->AliasNbPages();


//Verif ecrasement

    $requetesel = "SELECT Pdfid FROM pdf_files WHERE Pdfname = \"". 'ndf-'.$userid.'-'.$month.'-'.$year.'.pdf' . "\" ";
    $sqlsel=$db->execRequete($requetesel);
    $ressel=$db->objetSuivant($sqlsel);
    if ($ressel->pdfwas_validate == 1)
    {
        if ($month != 12)
        {
            $month++;
            if (strlen($month == 1))
            {
                $month = "0".$month;
            }
        } else {
            $month = "01";
            $year ++;
        }
    }

if (!is_dir("../pdf/"))
{
    mkdir ("../pdf",0777);
}
if (!is_dir("../pdf/".$userid))
{
    mkdir ("../pdf/".$userid,0777);
}
$pdf->Output('../pdf/'.$userid.'/ndf-'.$userid.'-'.$month.'-'.$year.'.pdf','F');

if (is_file('../pdf/'.$userid.'/ndf-'.$userid.'-'.$month.'-'.$year.'.pdf'))
{
    //pdf requete

    $reqInsUpdt;
    if ("x".$ressel->Pdfid == "x")
    {
        $reqInsUpdt = "INSERT INTO pdf_files (Pdfname,pdfwasCreatedOn,user_refid) VALUES (".'"ndf-'.$userid.'-'.$month.'-'.$year.'.pdf"' .", now(), ". $_COOKIE['rowid'].")";
    } else {
        $reqInsUpdt = "UPDATE pdf_files set pdfwasCreatedOn = now() WHERE Pdfid =". $ressel->Pdfid;
    }
    $db->execRequete($reqInsUpdt);
}


   header("Content-Type: text/xml");
    $xml = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xml .= '<ajax-response>';
    $xml .='</ajax-response>';



?>
<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 18 juin 09
  *
  * Infos on http://www.finapro.fr
  *
  */
/**
 *
 * Name : overview_grid_init.php
 * nagios_db
 */

require_once('./pre.inc.php');
$contratId = $_REQUEST['contratId'];

$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows'];
 // get how many rows we want to have into the grid
 $sidx = $_REQUEST['sidx'];
 // get index row - i.e. user click to sort
 $sord = $_REQUEST['sord'];
 $id =$_REQUEST['id'];
 // get the direction
 if(!$sidx) $sidx =1;

  $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = ".$contratId);
   $row = $db->fetch_array($result,MYSQL_ASSOC);
   $count = $row['count'];
   if( $count >0 ) {
        $total_pages = ceil($count/$limit);
   } else {
        $total_pages = 0;
   }
   if ($page > $total_pages)
        $page=$total_pages;
   $start = $limit*$page - $limit; // do not put $limit*($page - 1)
   if ($start < 0) $start = 0;
	
	$isFinancement=0;
	$requete = "SELECT isFinancement FROM ".MAIN_DB_PREFIX."propal WHERE rowid =". $contratId;
	$sql = $db->query($requete);
	$res = $db->fetch_object($sql);
	$isFinancement = $res->isFinancement;
	
  $requete = "SELECT ".MAIN_DB_PREFIX."propaldet.rowid as id,
                     ".MAIN_DB_PREFIX."propaldet.fk_product,
                     ifnull(".MAIN_DB_PREFIX."product.description,".MAIN_DB_PREFIX."propaldet.description) as description,
                     ".MAIN_DB_PREFIX."propaldet.qty,
                     ".MAIN_DB_PREFIX."propaldet.total_ht
                FROM ".MAIN_DB_PREFIX."propaldet
           LEFT JOIN ".MAIN_DB_PREFIX."product on ".MAIN_DB_PREFIX."product.rowid = ".MAIN_DB_PREFIX."propaldet.fk_product
               WHERE ".MAIN_DB_PREFIX."propaldet.fk_propal = $contratId
            ORDER BY $sidx $sord
               LIMIT $start , $limit";
// print $requete;
    $result = $db->query( $requete ) or die("Couldnt execute query.".mysql_error());
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo "<rows>";
    echo "<page>".$page."</page>";
    echo "<total>".$total_pages."</total>";
    echo "<records>".$count."</records>"; // be sure to put text data in CDATA
    while($row = $db->fetch_array($result,MYSQL_ASSOC))
    {

        if ($isFinancement == 1)
        {
            require_once(DOL_DOCUMENT_ROOT."/Babel_GA/ContratGA.class.php");
            $contratGA = new ContratGA($db);
            $requete1 = "SELECT Babel_GA_propale.tauxFinancement,
                                Babel_financement_period.NbIterAn,
                                Babel_GA_propale.duree,
                                Babel_GA_propale.echu,
                                Babel_GA_propale.tauxMarge,
                                Babel_GA_propale.duree
                           FROM Babel_financement_period, Babel_GA_propale
                          WHERE Babel_financement_period.id =  Babel_GA_propale.financement_period_refid
                            AND propaldet_refid = ".$row['id'];
            $sql1 = $db->query($requete1);
//			print $requete1;
            $res1 = $db->fetch_object($sql1);
            $interest = $res1->tauxFinancement;
            $type = $res1->echu;
            $amortperiod = $res1->duree;
            $principal = ($res1-tauxMarge / 100) * $row[total_ht];
            $payperyear = $res1->NbIterAn;
//			print $interest."\n  ".$payperyear."  \n".$amortperiod."  \n".$principal."  \n".$type;
            $monthlyCost = price(round(100 * $contratGA->getLoyer($interest, $payperyear,$amortperiod,$principal,$type))/100);
            $total = $monthlyCost * 12 * $amortperiod / $payperyear;

            echo "<row id='".$row[id]."'>";
            echo "<cell>". $row[id]."</cell>";
            echo "<cell><![CDATA[". $row[fk_product]."]]></cell>";
//            echo "<cell><![CDATA[". $row[statut]."]]></cell>";
//            echo "<cell><![CDATA[". utf8_encode($row[label])."]]></cell>";
            echo "<cell><![CDATA[". utf8_encode($row[description])."]]></cell>";
//            echo "<cell><![CDATA[". $row[date_ouverture]."]]></cell>";
//            echo "<cell><![CDATA[". $row[date_fin_validite]."]]></cell>";
            echo "<cell><![CDATA[". $row[qty]."]]></cell>";
            echo "<cell><![CDATA[". $monthlyCost."&euro;]]></cell>";
            echo "<cell><![CDATA[-]]></cell>";
            echo "</row>";
        } else {
            $monthlyCost = calculateMonthlyAmortizingCost($row[total_ht], "3", "10");
            $total = calculateTotalAmortizingCost($row[total_ht], "3", "10");
            echo "<row id='".$row[id]."'>";
            echo "<cell>". $row[id]."</cell>";
            echo "<cell><![CDATA[". $row[fk_product]."]]></cell>";
//            echo "<cell><![CDATA[". $row[statut]."]]></cell>";
//            echo "<cell><![CDATA[". utf8_encode($row[label])."]]></cell>";
            echo "<cell><![CDATA[". utf8_encode($row[description])."]]></cell>";
//            echo "<cell><![CDATA[". $row[date_ouverture]."]]></cell>";
//            echo "<cell><![CDATA[". $row[date_fin_validite]."]]></cell>";
            echo "<cell><![CDATA[". $row[qty]."]]></cell>";
            echo "<cell><![CDATA[-]]></cell>";
            echo "<cell><![CDATA[". $total."&euro;]]></cell>";
            echo "</row>";
        }

    }
        echo "</rows>";
function calculateMonthlyAmortizingCost($totalLoan, $years, $interest )

{

$tmp = pow((1 + ($interest / 1200)), ($years * 12));

return round(($totalLoan * $tmp) * ($interest / 1200) / ($tmp - 1),2);

}
function calculateTotalAmortizingCost($totalLoan, $years, $interest )

{

$tmp = pow((1 + ($interest / 1200)), ($years * 12));

return round(($years*12*(($totalLoan * $tmp) * ($interest / 1200) / ($tmp - 1))-$totalLoan),2);

}

?>

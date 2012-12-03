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

  $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$contratId);
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

  $requete = "SELECT isFinancement FROM ".MAIN_DB_PREFIX."contrat WHERE rowid = ".$contratId;
  $result = $db->query($requete);
  $sql = $db->fetch_object($result);
  $isFinancement = $sql->isFinancement;

  $requete = "SELECT ".MAIN_DB_PREFIX."contratdet.rowid as id,
                     ".MAIN_DB_PREFIX."contratdet.fk_product,
                     ".MAIN_DB_PREFIX."contratdet.statut,
                     ifnull(llx_product.label,".MAIN_DB_PREFIX."contratdet.label) as label,
                     ifnull(llx_product.description,".MAIN_DB_PREFIX."contratdet.description) as description,
                     ".MAIN_DB_PREFIX."contratdet.date_ouverture,
                     ".MAIN_DB_PREFIX."contratdet.date_fin_validite,
                     ".MAIN_DB_PREFIX."contratdet.qty,
                     ".MAIN_DB_PREFIX."contratdet.total_ht
                FROM ".MAIN_DB_PREFIX."contratdet
           LEFT JOIN llx_product on llx_product.rowid = ".MAIN_DB_PREFIX."contratdet.fk_product
               WHERE ".MAIN_DB_PREFIX."contratdet.fk_contrat = $contratId
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
            $requete1 = "SELECT Babel_GA_contrat.tauxFinancement,
                                Babel_financement_period.NbIterAn,
                                Babel_GA_contrat.duree,
                                Babel_GA_contrat.echu,
                                Babel_GA_contrat.tauxMarge,
                                Babel_GA_contrat.duree
                           FROM Babel_financement_period, Babel_GA_contrat
                          WHERE Babel_financement_period.id =  Babel_GA_contrat.financement_period_refid
                            AND contratdet_refid = ".$id;
                            print $requete1;
            $sql1 = $db->query($requete1);
            $res1 = $db->fetch_object($sql1);
            $interest = $res1->tauxFinancement;
            $type = $res1->echu;
            $amortperiod = $res1->duree;
            $principal = ($res1-tauxMarge / 100) * $row[total_ht];
            $payperyear = $res1->NbIterAn;
            $monthlyCost = $contratGA->getLoyer($interest, $payperyear,$amortperiod,$principal,$type);
            $total = $monthlyCost * 12 * $amortperiod / $payperyear;

            echo "<row id='".$row[id]."'>";
            echo "<cell>". $row[id]."</cell>";
            echo "<cell><![CDATA[". $row[fk_product]."]]></cell>";
            echo "<cell><![CDATA[". $row[statut]."]]></cell>";
            echo "<cell><![CDATA[". utf8_encode($row[label])."]]></cell>";
            echo "<cell><![CDATA[". utf8_encode($row[description])."]]></cell>";
            echo "<cell><![CDATA[". $row[date_ouverture]."]]></cell>";
            echo "<cell><![CDATA[". $row[date_fin_validite]."]]></cell>";
            echo "<cell><![CDATA[". $row[qty]."]]></cell>";
            echo "<cell><![CDATA[". $monthlyCost."]]></cell>";
            echo "<cell><![CDATA[". $total."]]></cell>";
            echo "</row>";
        } else {
            $monthlyCost = calculateMonthlyAmortizingCost($row[total_ht], "3", "10");
            $total = calculateTotalAmortizingCost($row[total_ht], "3", "10");
            echo "<row id='".$row[id]."'>";
            echo "<cell>". $row[id]."</cell>";
            echo "<cell><![CDATA[". $row[fk_product]."]]></cell>";
            echo "<cell><![CDATA[". $row[statut]."]]></cell>";
            echo "<cell><![CDATA[". utf8_encode($row[label])."]]></cell>";
            echo "<cell><![CDATA[". utf8_encode($row[description])."]]></cell>";
            echo "<cell><![CDATA[". $row[date_ouverture]."]]></cell>";
            echo "<cell><![CDATA[". $row[date_fin_validite]."]]></cell>";
            echo "<cell><![CDATA[". $row[qty]."]]></cell>";
            echo "<cell><![CDATA[". $monthlyCost."]]></cell>";
            echo "<cell><![CDATA[". $total."]]></cell>";
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

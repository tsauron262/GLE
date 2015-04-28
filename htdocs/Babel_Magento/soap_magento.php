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
require_once('magento_soap.class.php');

$mag = new magento_soap($conf);

require_once('Var_Dump.php');
Var_Dump::displayInit(array('display_mode' => 'HTML4_Table'), array('mode' => 'normal','offset' => 4));

//$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
//$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";


//Produit en hypertree
print <<<HTML
  <html>
    <head>
      <link type="text/css" rel="stylesheet" href="example.css" />
  <!--[if IE]>
  <script type="text/javascript" src="jit/Extras/excanvas.js"></script>
  <![endif]-->
      <script type="text/javascript" src="Jit/jit-yc.js" ></script>
      <script type="text/javascript" src="example.js" ></script>
      <script type="text/javascript" src="jquery/jquery-1.3.2.js" ></script>
    </head>

    <body>
      <iframe src="hypertree.php" height="1600" width="1600"></iframe><div style="float: right;" id="resInfoProd"></div>
HTML;

//liste des produit par categorie dans magento
$mag->connect();
$res = $mag->prod_prod_list();

$arrNameProd=array();
foreach($res as $key=>$val)
{
    $arrNameProd[$val["product_id"]]=$val['name'];
}


//$rs1 = $mag->prod_cat_list();

//
//$catId = array();
//foreach($rs as $key=>$val)
//{
//    if (is_array($val['category_ids']) && count($val['category_ids']) >0)
//    {
//        foreach($val['category_ids'] as $tmpid => $catArr)
//        {
//            $catId[$catArr][$val['product_id']]['name']=$val['name'];
//            $catId[$catArr][$val['product_id']]['sku']=$val['sku'];
//            $catId[$catArr][$val['product_id']]['id']=$val['product_id'];
//            $catId[$catArr][$val['product_id']]['type']=$val['type'];
//        }
//    } else {
//        //Product has no category
//        $catId[0][$val['product_id']]['name']=$val['name'];
//        $catId[0][$val['product_id']]['sku']=$val['sku'];
//        $catId[0][$val['product_id']]['id']=$val['product_id'];
//        $catId[0][$val['product_id']]['type']=$val['type'];
//    }
//}

//Parse category

//$mag->parseCat($rs1);

//$res= $mag->prod_cat_updt_stock(6,"50",1);
//
//$res= $mag->prod_cat_get_stock(6);

$res=$mag->sales_list();
// Stats
//        -> best seller OK
//        -> somme des ventes OK a faire : 24H/1semaine/1mois/an/total
//        -> vente moyenne OK a faire : 24H/1semaine/1mois/an/total
//        -> 5 dernieres commandes OK
//        -> most viewed product KO
//        -> nouveau client => depuis 24H/1semain/1mois / an / total OK
//        -> 5 best client OK a faire sur 24H/1semaine/1mois/an/total
//        -> 5 plus grosses commandes OK a faire sur 24H/1semaine/1mois/an/total

$sumVente=0;
$sumCA=0;
$nbVente=0;
$arrAmountCommandByCustomer=array();
$arrCustomer=array();
$arrAmountByCommand=array();
$arrAmountByDate=array();

foreach($res as $key=>$val)
{
    $sumVente += $val["subtotal"];
    $sumCA += $val["base_grand_total"];
    $nbVente ++;
    //commande par client
    if (!is_numeric($arrAmountCommandByCustomer[$val['customer_id']]['val']))
    {
        $arrAmountCommandByCustomer[$val['customer_id']]['val']=0;
        $arrAmountCommandByCustomer[$val['customer_id']]['custId']=$val['customer_id'];
    }
    $arrAmountCommandByCustomer[$val['customer_id']]['val'] += $val['subtotal'];
    //Client

    $arrCustomer[$val['customer_id']] = $val['customer_firstname']. " ".$val['customer_lastname']. " &lt;".$val['customer_email']."&gt;";

    if (!is_numeric($arrAmountByCommand[$val['customer_id']]['val']))
    {
        $arrAmountByCommand[$val['increment_id']]['val']=0;
        $arrAmountByCommand[$val['increment_id']]['orderId']=$val['increment_id'];
    }
    $arrAmountByCommand[$val['increment_id']]['val'] += $val['subtotal'];

    //Date commande
    $date = $val["created_at"];
    //parse date
    $dateEpoch = strtotime($date);
    //$arrAmountByDate[$dateEpoch] += $val['subtotal'];
    if (!is_numeric($arrAmountByDate[$dateEpoch]['val']))
    {
        $arrAmountByDate[$dateEpoch]['val']=0;
        $arrAmountByDate[$dateEpoch]['orderId']=$val['increment_id'];
        $arrAmountByDate[$dateEpoch]['ts']=$dateEpoch;
    }
    $arrAmountByDate[$dateEpoch]['val'] += $val['subtotal'];



}

usort($arrAmountCommandByCustomer, "sortByValDesc");
usort($arrAmountByCommand, "sortByValDesc");
usort($arrAmountByDate, "sortByDateDesc");

function sortByDateDesc($a, $b)
{
    $aa = $a["ts"];
    $bb = $b["ts"];
    return ($aa < $bb) ? +1 : -1;
}

function sortByValDesc($a, $b)
{
    $aa = $a["val"];
    $bb = $b["val"];
    return ($aa < $bb) ? +1 : -1;
}
function sortByValAsc($a, $b)
{
    $aa = $a["val"];
    $bb = $b["val"];
    return ($aa > $bb) ? +1 : -1;
}
print "<div style='clear:both;'>";
print "<table><tbody>";
print "<tr><th>CA</th><td>".$sumCA." &euro;</td></tr>";
print "<tr><th>Somme vente</th><td>".$sumVente." &euro;</td></tr>";
print "<tr><th>Montant moyen</th><td>".round($sumVente/$nbVente,2)." &euro;</td></tr>";
print "<tr><th>Nb de vente</th><td>".$nbVente."</td></tr>";
print "<tr><th>5 Best Customers</th><td>";
print "<table><tbody>\n";
$cnt = 0;
foreach ($arrAmountCommandByCustomer as $key=>$val)
{
    print "<tr><td>".$arrCustomer[$val['custId']]."</td><td>".$val['val']."</td></tr>";
    $cnt++;
    if ($cnt == 5)
    {
        continue;
    }
}
print "</tbody></table>";
print "</td></tr>";
print "<tr><th>5 Best Orders</th><td>";
print "<table><tbody>";
$cnt = 0;
foreach ($arrAmountByCommand as $key=>$val)
{
    print "<tr><td>".$val['orderId']."</td><td>".$val['val']."</td></tr>\n";
    $cnt++;
    if ($cnt == 5)
    {
        continue;
    }
}
print "</tbody></table>";
print "</td></tr>";

print "<tr><th>5 Last Orders</th><td>";
print "<table><tbody>";
$cnt = 0;
foreach ($arrAmountByDate as $key=>$val)
{
    print "<tr><td>".date('d/m/Y H:i:s',$val['ts'])."</td><td>".$val['orderId']."</td><td>".$val['val']."</td></tr>\n";
    $cnt++;
    if ($cnt == 5)
    {
        continue;
    }
}
print "</tbody></table>";
print "</td></tr>";

print "<tr><th>Nouveaux Clients</th><td>";
print "<table><tbody>";

$res = $mag->customer_list();
$cnt=0;
foreach ($res as $key=>$val)
{
    $ts = strtotime($val["created_at"]);
    $ts24 = strtotime('-1 day',time());
    $ts1W = strtotime('-1 week',time());
    $ts1M = strtotime('-1 month',time());
    $ts1Y = strtotime('-1 year',time());
    $custname = $val['firstname'] . " ".$val['lastname']. " &lt;".$val['email'].">";
    if ($ts > $ts24)
    {
        print "<tr><td>".date("d/m/Y H:i:s",$ts)."</td><td>".$custname."</td><td>".$arrAmountCommandByCustomer[$val["customer_id"]]['val']."</td></tr>";
    }

    $cnt++;
    if ($cnt == 5)
    {
        continue;
    }

}
print "</tbody></table>";
print "</td></tr>";

print "<tr><th>Best Sellers</th><td>";
print "<table><tbody>";

//best seller product
$res=$mag->sales_list();
$countSaleByProduct = array();
foreach($res as $key=>$val)
{
    if (!is_numeric($arrAmountByDate[$dateEpoch]['val']))
    {
        $arrAmountByDate[$dateEpoch]['val']=0;
        $arrAmountByDate[$dateEpoch]['orderId']=$val['increment_id'];
        $arrAmountByDate[$dateEpoch]['ts']=$dateEpoch;
    }
    $res1 = $mag->sales_info($val['increment_id']);
    foreach($res1['items'] as $key=>$val)
    {
        if (!is_numeric($countSaleByProduct[$val["product_id"]]['val']))
        {
            $countSaleByProduct[$val["product_id"]]['val']=0;
            $countSaleByProduct[$val["product_id"]]['product_id']="";
        }
        $countSaleByProduct[$val["product_id"]]['val'] += $val["qty_ordered"];
        $countSaleByProduct[$val["product_id"]]['product_id']=$val["product_id"];
    }
}
//var_dump($countSaleByProduct);

usort($countSaleByProduct, "sortByValDesc");

foreach($countSaleByProduct as $idx => $qtySell)
{
    print "<tr><td>".$arrNameProd[$qtySell['product_id']]."</td><td>".$qtySell['val']."</td></tr>";
}
print "</tbody></table>";
print "</td></tr>";


print "</tbody></table>";
print "<iframe src='magento_stats_ofc.php' width='821' height='650' ></iframe>";
print "</div>";
//Var_Dump::display($mag->jsonArr);
//Var_Dump::display($res);

//var_dump($rs);
?>
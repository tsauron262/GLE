<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 12 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : dashboard.php
  * magentoGLE
  */
$disablejs=true;
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_sales.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_customer.class.php");

$langs->load("synopsisGene@Synopsis_Tools");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'demandeInterv', $demandeIntervid,'');

$header .= '<link href="css/magento.css"  type="text/css" rel="stylesheet" ></link>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";

$js = <<<EOF
<script type="text/javascript" >
jQuery(document).ready(function()
{
    $("#accordion").tabs({cache: true,fx: { opacity: 'toggle' },
        spinner:"Chargement ...",});

}
);
</script>
EOF;

$header .= $js;
/*
*    View
*/
//print $header;
llxHeader($header);

//Display stasts
$mag = new magento_sales($conf);
$mag->connect();
$res=$mag->sales_list();
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

print "<div style='float: left; padding: 30px; '>";
print "<table class='magento_stats'><tbody>";
print "<tr><td colspan=2>";
//.$sumCA.

print '<div class="entry-edit">';
print '<div class="entry-edit-head">';
print '<h4>CA</h4>';
print '</div>';
print '<fieldset class="a-center bold">';
print '<span class="nowrap" style="font-size: 18px;">';
print '<span class="price">'.$sumCA.' &euro;</span>';
print '<span style="font-size: 14px; color: rgb(104, 104, 104);"/>';
print '</span>';
print '</fieldset>';
print '</div>';

print "<tr><td colspan=2>";
print '<div class="entry-edit">';
print '<div class="entry-edit-head">';
print '<h4>Ventes</h4>';
print '</div>';
print '<fieldset class="a-center bold">';
print '<span class="nowrap" style="font-size: 18px;">';
print '<span class="price">'.$sumVente.' &euro;</span>';
print '<span style="font-size: 14px; color: rgb(104, 104, 104);"/>';
print '</span>';
print '</fieldset>';
print '</div>';

print "<tr><td colspan=2>";
print '<div class="entry-edit">';
print '<div class="entry-edit-head">';
print '<h4>Montant moyen</h4>';
print '</div>';
print '<fieldset class="a-center bold">';
print '<span class="nowrap" style="font-size: 18px;">';
print '<span class="price">'.round($sumVente/$nbVente,2).' &euro;</span>';
print '<span style="font-size: 14px; color: rgb(104, 104, 104);"/>';
print '</span>';
print '</fieldset>';
print '</div>';

print "<tr><td colspan=2>";
print '<div class="entry-edit">';
print '<div class="entry-edit-head">';
print '<h4>Nb de vente</h4>';
print '</div>';
print '<fieldset class="a-center bold">';
print '<span class="nowrap" style="font-size: 18px;">';
print '<span class="price">'.$nbVente.' </span>';
print '<span style="font-size: 14px; color: rgb(104, 104, 104);"/>';
print '</span>';
print '</fieldset>';
print '</div>';


print "</tbody></table>";
print "</div>";


print "<div>";
print "<iframe src='magento_stats_ofc.php' width='850px' height='655px' style='border: 0;' ></iframe>";

print "</div>";






print "<div id='accordion' style='width:1010px'>";
print <<<EOF
 <ul>
        <li><a href="#fragment-1"><span>5 Best Customers</span></a></li>
        <li><a href="#fragment-2"><span>5 Best Orders</span></a></li>
        <li><a href="#fragment-3"><span>5 Last Orders</span></a></li>
        <li><a href="#fragment-4"><span>Nouveaux clients</span></a></li>
        <li><a href="#fragment-5"><span>Best Sellers</span></a></li>
    </ul>
EOF;
print "<div  id='fragment-1' class='grid np'>";




print "<table class='even pointer' cellspacing='0' style='border: 0pt none;'>";
print ' <thead>
<tr class="headings">
<th class="no-link" style="background-color: transparent;">
<span class="nobr">Client</span>
</th>
<th class="no-link"  style="background-color: transparent;">
<span class="nobr">Achat</span>
</th>
</tr>
</thead>';
print "<tbody>";
//print "<table  class='even pointer' cellspacing='0' style='border: 0pt none ;' ><tbody>\n";
$cnt = 0;
foreach ($arrAmountCommandByCustomer as $key=>$val)
{
    $css = "even";
    if ($cnt %2 == 1)
    {
        $css = "pointer";
    }
    print "<tr class='".$css."'><td>".$arrCustomer[$val['custId']]."</td><td>".$val['val']." &euro;</td></tr>";
    $cnt++;
    if ($cnt == 5)
    {
        continue;
    }
}
print "</tbody></table>";
print "</div>";
print "<div  id='fragment-2'  class='grid np'>";


print "<table class='even pointer' cellspacing='0' style='border: 0pt none ;'>";
print ' <thead>
<tr class="headings">
<th class="no-link" style="background-color: transparent;">
<span class="nobr">Ref commande</span>
</th>
<th class="no-link"  style="background-color: transparent;">
<span class="nobr">Valeur</span>
</th>
</tr>
</thead>';
print "<tbody>";
$cnt = 0;
foreach ($arrAmountByCommand as $key=>$val)
{
    $css = "even";
    print "<tr class='".$css."'><td>".$val['orderId']."</td><td>".$val['val']." &euro;</td></tr>";
    $cnt++;
    if ($cnt == 5)
    {
        continue;
    }
}
print "</tbody></table>";

print "</div>";


print "<div  id='fragment-3'  class='grid np'>";
print "<table class='even pointer' cellspacing='0' style='border: 0pt none ;'>";
print ' <thead>
<tr class="headings">
    <th class="no-link" style="background-color: transparent;">
        <span class="nobr">Date commande</span>
    </th>
    <th class="no-link"  style="background-color: transparent;">
        <span class="nobr">R&eacute;f&eacute;rence commande</span>
    </th>
    <th class="no-link"  style="background-color: transparent;">
        <span class="nobr">Valeur</span>
    </th>

</tr>
</thead>';
print "<tbody>";
$cnt = 0;
foreach ($arrAmountByDate as $key=>$val)
{
    $css = "even";
    print "<tr class='".$css."'><td>".date('d/m/Y H:i:s',$val['ts'])."</td><td>".$val['orderId']." </td><td>".$val['val']." &euro;</td></tr>";
    $cnt++;
    if ($cnt == 5)
    {
        continue;
    }
}
print "</tbody></table>";

print "</div>";

print "<div  id='fragment-4'  class='grid np'>";
//print "<table><tbody>";
//
//print "<tr><th>Nouveaux Clients</th><td>";
//print "<table><tbody>";
//
//$res = $mag->customer_list();
//$cnt=0;
//foreach ($res as $key=>$val)
//{
//    $ts = strtotime($val["created_at"]);
//    $ts24 = strtotime('-1 day',time());
//    $ts1W = strtotime('-1 week',time());
//    $ts1M = strtotime('-1 month',time());
//    $ts1Y = strtotime('-1 year',time());
//    $custname = $val['firstname'] . " ".$val['lastname']. " &lt;".$val['email'].">";
//    if ($ts > $ts24)
//    {
//        print "<tr><td>".date("d/m/Y H:i:s",$ts)."</td><td>".$custname."</td><td>".$arrAmountCommandByCustomer[$val["customer_id"]]['val']."</td></tr>";
//    }
//
//    $cnt++;
//    if ($cnt == 5)
//    {
//        continue;
//    }
//
//}
//print "</tbody></table>";
//print "</td></tr>";
//print "</tbody></table>";

print "<table class='even pointer' cellspacing='0' style='border: 0pt none ;'>";
print ' <thead>
<tr class="headings">
    <th class="no-link" style="background-color: transparent;">
        <span class="nobr">Date inscription</span>
    </th>
    <th class="no-link"  style="background-color: transparent;">
        <span class="nobr">Nom client</span>
    </th>
    <th class="no-link"  style="background-color: transparent;">
        <span class="nobr">Valeur</span>
    </th>

</tr>
</thead>';
print "<tbody>";
$cnt = 0;
//    $css = "even";
//    print "<tr class='".$css."'><td>".date('d/m/Y H:i:s',$val['ts'])."</td><td>".$val['orderId']." </td><td>".$val['val']." &euro;</td></tr>";
$mag1=new magento_customer($conf);
$res = $mag1->customer_list();
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
        $value = 0;
        foreach ($arrAmountCommandByCustomer as $key1=>$val1)
        {
            if ($val1['custId'] == $val["customer_id"])
            {
                $value = $val1['val'];
                break;
            }
        }
        print "<tr class='".$css."'><td>".date("d/m/Y H:i:s",$ts)."</td><td>".$custname."</td><td>".$value." &euro;</td></tr>";
    }



    $cnt++;
    if ($cnt == 5)
    {
        continue;
    }
}
print "</tbody></table>";

print "</div>";

print "<div  id='fragment-5'  class='grid np'>";
print "<table><tbody>";

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
print "</div>";
print "</div>";

$db->close();

llxFooter('$Date: 2008/04/09 18:13:50 $ - $Revision: 1.40 $');


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
?>

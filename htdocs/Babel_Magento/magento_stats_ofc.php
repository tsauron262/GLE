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
//displaythe openflash chart graph + the ui
//    -> graph par montant
//    -> graph par nombre de client
//    -> graph par nombre de commande
//    -> graph par produit (qty)

$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";

print '  <html>';
print '    <head>';
print '      <link type="text/css" rel="stylesheet" href="css/magento.css" />';
print '      <script type="text/javascript" src="jquery/jquery-1.3.2.js" ></script>';
print '      <script type="text/javascript" src="jquery/ui/jquery-ui.js" ></script>';
print '      <script type="text/javascript" src="js/swfobject.js"></script>';
print '        <script type="text/javascript">';
print '        swfobject.embedSWF(';
print '        "open-flash-chart/open-flash-chart.swf", "my_chart", "100%", "100%",';
print '        "9.0.0", "expressInstall.swf",';
print '        {"data-file":"json_data.php?action=customer"} );';

print <<<EOF
 $(document).ready(function() {
    $('#typeGraph').change(function()
    {
        var action = $('#typeGraph :selected').val();
        if (action == "product")
        {
            $("#productType").css('display','block');
        } else {
            var dateSel = $('#dateGraph :selected').val();

            $("#productType").css('display','none');
            //remove chart
            $("#my_chart").replaceWith('<div id="my_chart"></div>');

           swfobject.embedSWF(
            "open-flash-chart/open-flash-chart.swf", "my_chart", "100%", "100%",
            "9.0.0", "expressInstall.swf",
            {"data-file":"json_data.php?action="+action+"%26date="+dateSel} );

        }

    });

    $('#dateGraph').change(function()
    {
        var action = $('#typeGraph :selected').val();
        if (action == "product")
        {
            $("#productType").css('display','block');
        } else {
            var dateSel = $('#dateGraph :selected').val();

            $("#productType").css('display','none');
            //remove chart
            $("#my_chart").replaceWith('<div id="my_chart"></div>');

           swfobject.embedSWF(
            "open-flash-chart/open-flash-chart.swf", "my_chart", "100%", "100%",
            "9.0.0", "expressInstall.swf",
            {"data-file":"json_data.php?action="+action+"%26date="+dateSel} );

        }

    });
    $('#productType').change(function()
    {
        var action = $('#typeGraph :selected').val();
        var dateSel = $('#dateGraph :selected').val();
        var prodSel = $('#productType :selected').val();

        //remove chart
        $("#my_chart").replaceWith('<div id="my_chart"></div>');

       swfobject.embedSWF(
        "open-flash-chart/open-flash-chart.swf", "my_chart", "100%", "100%",
        "9.0.0", "expressInstall.swf",
        {"data-file":"json_data.php?action="+action+"%26date="+dateSel+"%26prod="+prodSel} );


    });

 });


EOF;
print '        </script>';
print '    </head>';
print '    <body style="padding: 0px; margin: 0;max-width:830px;">';


$action=$_REQUEST['action'];
print'<div class="grid" style="padding: 0px; background-image: url(images/bg-ofc.png);  background-repeat:no-repeat; background-attachment:fixed; max-width:830px; border: 1px solid; border-right: 1px solid #000000; ">';
print '<table class="even pointer" cellspacing="0" style="border: 0pt none ;width:830px; border-right: 1px solid #000000; ">';
print '<thead><tr class="headings"><th>';
print "<select id='typeGraph'>";
print "<option SELECTED value='ventes'>Ventes</option>";
print "<option value='customer'>Inscription Client</option>";
print "<option value='product'>Produit</option>";
print "</select>";
print "</th><th>";
print "<select id='dateGraph'>";
print "<option value='day'>Jour</option>";
print "<option SELECTED value='week'  >Semaine</option>";
print "<option value='month'>Mois</option>";
print "<option value='year'>Ann&eacute;e</option>";
print "<option value='all'>5 ans</option>";
print "</select>";
print "</th><th id='productType' style='display:none;'>";


print "<select >";
require_once('magento_product.class.php');
$mag = new magento_product($conf);
$mag->connect();
$res=$mag->prod_prod_list();
foreach($res as $key=>$val)
{
    print "<option value='".$val['product_id']."'>".$val['name']."</option>";
}
print "</select>";
print "</th><th style='width:100%;'>&nbsp;</th></tr></thead>";
print '</table><table  class="even pointer" cellspacing="0 style="border: 0pt none ;width:830px;>';
print "<tbody style='background-color: transparent;'><tr><td colspan='1'>";

switch($action)
{
    case ('somecase'):
    {

    }
    break;
    case ('byamount'):
    default:
    {
//
//print <<<CHART
//
//        <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
//                codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0"
//                width="500"
//                height="250" id="graph-2" align="middle">
//
//            <param name="allowScriptAccess" value="sameDomain" />
//            <param name="movie" value="open-flash-chart.swf" />
//            <param name="quality" value="high" />
//            <embed src="open-flash-chart/open-flash-chart.swf"
//                   quality="high"
//                   bgcolor="#FFFFFF"
//                   width="500"
//                   height="250"
//                   name="open-flash-chart"
//                   align="middle"
//                   allowScriptAccess="sameDomain"
//                   type="application/x-shockwave-flash"
//                   pluginspage="http://www.macromedia.com/go/getflashplayer" />
//        </object>
//
//CHART;

    }
    break;
}

        print '<div id="resize" style="width:800px; height:600px ; padding: 10px">';
        print '<div id="my_chart"></div>';
        print '</div>';

print '</td></tr></table></div>';
$mag->disconnect();
?>
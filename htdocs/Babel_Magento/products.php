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
  * Name : products.php
  * magentoGLE
  */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_soap.class.php");

//$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
//$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";


$header = <<<EOF
      <link type="text/css" rel="stylesheet" href="css/example.css" />
  <!--[if IE]>
  <script type="text/javascript" src="jit/Extras/excanvas.js"></script>
  <![endif]-->
      <script type="text/javascript" src="Jit/jit.js" ></script>
      <script type="text/javascript" src="example.js" ></script>
      <script type="text/javascript" src="js/json.js" ></script>
      <script type="text/javascript" src="jquery/jquery-1.3.2.js" ></script>
      <script type="text/javascript" src="js/jquery.bgiframe.js" ></script>
      <script type="text/javascript" src="js/jquery.dimensions.js" ></script>
      <script type="text/javascript" src="js/jquery.tooltip.js" ></script>
      <script type="text/javascript">
      var updown=0;
      $(document).ready(function(){
          $("#hideUp").click(function()
          {
                $("#foldingDiv").slideToggle();
                if (updown==1)
                {
                    updown=0;
                    $("#hideUp").attr("src", "images/up.gif");
                } else {
                    updown = 1;
                    $("#hideUp").attr("src", "images/down.gif");
                }
          });

      });
      </script>
EOF;
llxHeader($header);
print <<<EOF
    <body onload="init();">
<div style="width:852px; background-color: #EAFFFA; opacity: 0.9;">
<table style="width:852px; background-image: url(images/headerbg.gif); border-collapse: collapse;"><tr>
<td style="background-color: rgb(224, 224, 224);color: white;
                  display: table-cell;
                  font-size: 12px;
                  font-weight: bold;
                  height: 14px;
                  padding: 2px;
                  text-align: left;
                  vertical-align: middle;
                  white-space: nowrap;
                  width: 4px;
                  background-image: url(images/headerleft.gif); "></td>
<td style="background-color: rgb(224, 224, 224);
                  color: white;
                  display: table-cell;
                  font-size: 12px;
                  font-weight: bold;
                  height: 14px;
                  padding: 0px;
                  text-align: left;
                  vertical-align: middle;
                  white-space: nowrap;
                  width: auto;
                  background-image: url(images/headerbg.gif);" class="ui_widget_all">Carte des produits</span>
</td>
<td style ="background-color: rgb(224, 224, 224);color: white;
                  display: table-cell;
                  font-size: 12px;
                  font-weight: bold;
                  height: 14px;
                  padding: 0px;
                  text-align: left;
                  vertical-align: middle;
                  white-space: nowrap;
                  width: 21px;
                  background-image: url(images/headerbg.gif); "><img id="hideUp" src="images/up.gif"></img>
</td>

<td style ="background-color: rgb(224, 224, 224);color: white;
                  display: table-cell;
                  font-size: 12px;
                  font-weight: bold;
                  height: 14px;
                  padding: 3px;
                  text-align: left;
                  vertical-align: middle;
                  white-space: nowrap;
                  width: 4px;
                  background-image: url(images/headerright.gif); ">
</td>
</tr></table>
<div id="foldingDiv">
      <div id="log"></div>
      <div id="infovis"></div>
</div>
</div>
<div style="float:left;"><input type="button" id="testBut" value="Cherche"></input></div>
<div id="prodDet">

</div>
    </body>
  </html>
EOF;
?>

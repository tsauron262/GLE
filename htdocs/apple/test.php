<?php

require_once('../main.inc.php');
llxHeader();

echo '<link type="text/css" rel="stylesheet" href="appleGSX.css"/>' . "\n";
echo '<script type="text/javascript" src="./appleGsxScripts.js"></script>' . "\n";

echo '<div style="background-color: #E6E6E6; width: 100%; padding: 10px; margin-bottom: 30px;">' . "\n";
echo '<label for="serialInput">Entrez un numéro de série: </label>' . "\n";
echo '<input type="text" name="serialInput" id="serialInput" value="C02H21L8DHJQ"';
echo 'onfocus="this.value = \'\';"';
echo '/>' . "\n";
echo '<button id="serialSubmit">&nbsp;&nbsp;OK&nbsp;&nbsp;</button>' . "\n";
echo '</div>';
echo '<div id="serialResult"></div>' . "\n";
echo '<div id="productInfos"></div>' . "\n";

//echo "<pre>"; print_r($gsx->part ( array ( 'partNumber' =>'Z661-6061')));
//echo "fin";
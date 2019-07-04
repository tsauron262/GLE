<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'BIMPCORE TEST', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

echo BimpRender::renderCompteurCaisse();

echo '</body></html>';

//llxFooter();

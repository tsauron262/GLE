<?php

require_once('../main.inc.php');

llxHeader();

$lien = '.bimp.fr/bimp8/synopsistools/test.php?no_menu=1&nolog=ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef';

if(isset($_GET['file']))
    $lien .= '&file='.$_GET['file'];

$array = array("erp1", "erp2", "erp3", "erp4", "erp5");


foreach($array as $erp){
    echo '<h1>Serveur '.$erp.'</h1>';
    echo 'https://'.$erp.$lien;
    echo '<iframe style="width: 100%; height: 400px;" src="https://'.$erp.$lien.'"></iframe>';
}


llxFooter();
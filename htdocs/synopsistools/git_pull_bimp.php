<?php

require_once('../main.inc.php');

llxHeader();

$lien = '.bimp.fr/bimp8/synopsistools/git_pull.php?no_menu=1&nolog=ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef';

$array = array("erp1", "erp2", "erp3", "erp4", "erp5");


foreach($array as $erp){
    echo '<h1>Serveur '.$erp.'</h1>';
    echo 'https://'.$erp.$lien;
    echo '<iframe style="width: 100%; height: 400px;" src="https://'.$erp.$lien.'"></iframe>';
}
ob_end_flush();


sleep(3);

BimpCore::setConf('git_version', BimpCore::getConf('git_version', 1)+1);


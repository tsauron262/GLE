<?php

require_once('../main.inc.php');


$log1 = "noreplay@bimp.fr";
$log2 = "autoauto";
$command = 'git pull https://'. urlencode($log1).":".urlencode($log2).'@git2.bimp.fr/BIMP/bimp-erp.git';
$result = array();
exec($command, $result);
foreach ($result as $line) {
    print($line . "\n");
}
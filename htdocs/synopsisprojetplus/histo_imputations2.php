<?php

ini_set('max_execution_time', 40000);
ini_set("memory_limit", "1200M");


require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/synopsisprojetplus/class/imputations.class.php');

$statImputations = new statImputations($db);

echo $statImputations->getStat();die;
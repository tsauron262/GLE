<?php

if(isset($_REQUEST['nolog']) && $_REQUEST['nolog'] == 'ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef'){
    define("NOLOGIN", 1);
    header('x-frame-options: ALLOWALL',true);
}

require_once('../main.inc.php');

if(!isset($_REQUEST['no_menu']))
    llxHeader();



require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';


$userd = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', 1);
$userd = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', 242);
//$user->fetch(242);
$userd->fetch(1);
echo 'millieu<br/>';
$userd->fetch(242);
$userd->fetch(1);
echo 'apr<br/>';


die('fin');


$cache = BimpCache::$cache_server;

echo $cache->printAll();
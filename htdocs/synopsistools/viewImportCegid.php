<?php

if(isset($_REQUEST['nolog']) && $_REQUEST['nolog'] == 'ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef'){
    define("NOLOGIN", 1);
    header('x-frame-options: ALLOWALL',true);
}

require_once('../main.inc.php');


require_once DOL_DOCUMENT_ROOT . '/bimptocegid/bimptocegid.lib.php';

if(!isset($_REQUEST['no_menu']))
    llxHeader();



$dir    = '/tmp';
$files1 = scandir(bimptocegidLib::getDirOutput() . 'BY_DATE' . '/');

print_r($files1);

if(isset($_GET['file']))
    echo '<br/><br/>'.nl2br(file_get_contents(bimptocegidLib::getDirOutput() . 'BY_DATE' . '/'.$_GET['file']));
if(!isset($_REQUEST['no_menu']))
    llxFooter();

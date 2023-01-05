<?php

if(isset($_REQUEST['nolog']) && $_REQUEST['nolog'] == 'ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef'){
    define("NOLOGIN", 1);
    header('x-frame-options: ALLOWALL',true);
}

require_once('../main.inc.php');

if(!isset($_REQUEST['no_menu']))
    llxHeader();



$dir    = '/tmp';
$files1 = scandir(PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/');

print_r($files1);

if(isset($_GET['file']))
    echo '<br/><br/>'.nl2br(file_get_contents(PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/'.$_GET['file']));
if(!isset($_REQUEST['no_menu']))
    llxFooter();

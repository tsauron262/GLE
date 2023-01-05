<?php

require_once("../../main.inc.php");
global $user;

$id = $_GET['id'];
$dir = DOL_DATA_ROOT.'/bimpcore/tmpFile/';
if(!is_dir($dir))
    mkdir($dir);
foreach($_FILES as $file){
    move_uploaded_file($file['tmp_name'], $dir.$user->id."_".$id."_".$file['name']);
}

//mailSyn2("test", "tommy@bimp.fr", null, 'test upload'.print_r($_REQUEST,1).print_r($_FILES,1));
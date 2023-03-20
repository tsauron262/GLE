<?php

require_once("../../main.inc.php");
global $user;

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpTools.php';
if($_REQUEST['type'] == 'media' || $_REQUEST['type'] == 'mediaImg'){
    $dir = '/medias/';
    $url = DOL_URL_ROOT.'/viewimage.php?modulepart=medias&entity=1&file=';
    if($_REQUEST['type'] == 'mediaImg'){
        $dir .= 'image/';
        $url .= 'image/';
    }
}
else{
    $dir = BimpTools::getTmpFilesDir();
    $url = '';
}

if (!is_dir($dir)) {
    BimpTools::makeDirectories($dir);
}
mailSyn2('envoie pj', 'tommy@bimp.fr', null, 'test '.DOL_DATA_ROOT . '/' . $dir . '/'.print_r($_FILES,1));

$files = array();
foreach ($_FILES as $file) {
    $file_name = /*$user->id . '_' .*/ $file['name'];
    if(move_uploaded_file($file['tmp_name'], DOL_DATA_ROOT . '/' . $dir . '/' . $file_name)){
        $files[] = array('fileName' => $file_name, 'uploaded'=>1, 'error'=> array(), 'url' => $url.$file_name);
    }
    
}

if(count($files) == 1)
    echo json_encode($files[0]);
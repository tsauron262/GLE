<?php

require_once("../../main.inc.php");
global $user;

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpTools.php';
$dir = BimpTools::getTmpFilesDir();

if (!is_dir($dir)) {
    BimpTools::makeDirectories($dir);
}

foreach ($_FILES as $file) {
    $file_name = $user->id . '_' . $file['name'];
    move_uploaded_file($file['tmp_name'], DOL_DATA_ROOT . '/' . $dir . '/' . $file_name);

    die(json_encode(array(
        'files' => $files
    )));
}

//mailSyn2("test", "tommy@bimp.fr", null, 'test upload'.print_r($_REQUEST,1).print_r($_FILES,1));
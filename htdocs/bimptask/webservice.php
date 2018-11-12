<?php

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != "synchro" || $_SERVER['PHP_AUTH_PW'] != "9DDrvuNcWRdKClhTe2LGh0mbKVIV33I3" ) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'NON autorisé';
    exit;
} else {

//header("Content-type: text/xml");
}

$errors = array();

require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';

$controller = BimpController::getInstance('bimptask');

$newTask = false;
$dst = "task0001@bimp.fr";
$src = "tommy@bimp.fr";
$subj = "new task mail";
$txt = "corp du mail";
$idTask = 28;

if($newTask){
    $task = BimpObject::getInstance("bimptask", "BIMP_Task");
    $tab = array("src"=>$src, "dst"=>$dst, "subj"=>$subj, "txt"=>$txt, "test_ferme"=>"");
    $errors = array_merge($errors, $task->validateArray($tab));
    $errors = array_merge($errors, $task->create());
}
else{
    $note = BimpObject::getInstance("bimpcore", "BimpNote");
    $tab = array("obj_type"=>"bimp_object", "obj_module"=>"bimptask", "obj_name"=>"BIMP_Task", "id_obj"=>$idTask, "type_author"=>"3", "email"=>$src, "visibility"=>4, "content"=>$txt);
    $errors = array_merge($errors, $note->validateArray($tab));
    $errors = array_merge($errors, $note->create());
}

print_r($errors);
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

$dst = $_REQUEST['dst'];//"task0001@bimp.fr";
$src = $_REQUEST['src'];//"tommy@bimp.fr";
$subj = $_REQUEST['subj'];//"new task mail";
$txt = $_REQUEST['txt'];/*"corp du mail
fsfdfs
dfdsfdsf
-------------
IDTASK:546785645628";*/





if($dst != "" && $src != "" && $subj != "" && $txt != ""){
    $idTask = 0;



    $const = "IDTASK:5467856456";
    preg_match("/".$const."[0-9]*/", $txt, $matches);
    if(isset($matches[0])){
        $idTask = str_replace($const, "", $matches[0]);
    }

    $tabTxt = explode("-------------", $txt);
    $txt = rtrim($tabTxt[0]);




    if($idTask < 1){
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
}
else{
    echo "Pas de données <pre>".print_r($_REQUEST,1);
}
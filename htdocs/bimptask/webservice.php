<?php
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != "synchro" || $_SERVER['PHP_AUTH_PW'] != "9DDrvuNcWRdKClhTe2LGh0mbKVIV33I3") {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'NON autorisé';
    exit;
} else {

//header("Content-type: text/xml");
}
define("NOLOGIN", 1); 
$errors = array();

require_once '../bimpcore/main.php';


require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

//dol_syslog(print_r($_REQUEST,1),3);
//dol_syslog(print_r($_FILES,1),3);

$controller = BimpController::getInstance('bimptask');

define("ID_USER_DEF", 215);

$dst = urldecode($_REQUEST['dst']); 
$src = urldecode($_REQUEST['src']); 
$subj = urldecode($_REQUEST['subj']); 
$txt = urldecode($_REQUEST['txt']); 


if($_REQUEST["old"]){
    $commande = new Commande($db);
    $sql = $db->query("SELECT rowid FROM `llx_commande` WHERE `validComm` > 0 AND (`validFin` < 1 || validFin is NULL) AND fk_statut = 0 ORDER BY `llx_commande`.`tms` DESC");
    while($ln=$db->fetch_object($sql)){
        $commande->fetch($ln->rowid);
        $commande->valid($user);
        echo "Validation ".$commande->getNomUrl(1);
    }
}






if (!($dst != "" && $src != "" && $subj != "" && $txt != "")) {
//    echo "Pas de données <pre>".print_r($_REQUEST,1);
    $sql = $db->query("SELECT * FROM llx_bimp_task2");
    while ($ln = $db->fetch_object($sql)) {
        if (traiteTask($ln->dst, $ln->src, $ln->subj, $ln->txt)) {
            $db->query("DELETE FROM llx_bimp_task2 WHERE id =" . $ln->id);
            echo "<br/>Tache 2 id : " . $ln->id;
        }
    }
} else {
    traiteTask($dst, $src, $subj, $txt);
}

function traiteTask($dst, $src, $subj, $txt) {
    global $db, $user;
    echo "traite" . $subj;
    $idTask = 0;
    $task = BimpObject::getInstance("bimptask", "BIMP_Task");

    $dst = str_replace("console@bimp.fr", "consoles@bimp.fr", $dst);
    $dst = str_replace("vol@bimp.fr", "vols@bimp.fr", $dst);
    //verif destinataire
    foreach(BIMP_Task::$valSrc as $destCorrect => $nom){
        if($destCorrect != "other" && stripos($dst, $destCorrect) !== false){
            $dst = $destCorrect;
        }
    }


    $const = "IDTASK:5467856456";
    preg_match("/" . $const . "[0-9]*/", $txt, $matches);
    if (isset($matches[0])) {
        $idTask = str_replace($const, "", $matches[0]);
    }

    $tabTxt = explode("-------------", $txt);
    $tabTxt = explode("\n> ", $tabTxt[0]);
    
    $txt = rtrim($tabTxt[0]);
    
    
    $user = new User($db);
    $sql = $db->query("SELECT u.rowid FROM `llx_user` u, llx_user_extrafields ue WHERE ue.fk_object = u.rowid AND (email LIKE '".$src."' || ue.alias LIKE '%".$src."%')");
    if($db->num_rows($sql) > 0){
        $ln = $db->fetch_object($sql);
        $user->fetch($ln->rowid);
    }
    else
        $user->fetch(ID_USER_DEF);
    
    $user->rights->bimptask->$dst->write = 1;
    $user->rights->bimptask->other->write = 1;


    if ($idTask > 0) {
        if(!$task->fetch($idTask) || $task->getData("status") > 3)
            $idTask = 0;
    }
    
    if ($idTask < 1) {
        
        echo "<br/>Création task";
        $tab = array("src" => $src, "dst" => $dst, "subj" => $subj, "txt" => $txt, "test_ferme" => "");
        $errors = array_merge($errors, $task->validateArray($tab));
        $errors = array_merge($errors, $task->create());
    } else {
        echo "<br/>Création note, task : ".$idTask;
        $note = BimpObject::getInstance("bimpcore", "BimpNote");
        $tab = array("obj_type" => "bimp_object", "obj_module" => "bimptask", "obj_name" => "BIMP_Task", "id_obj" => $idTask, "type_author" => "3", "email" => $src, "visibility" => 4, "content" => $txt);
        $errors = array_merge($errors, $note->validateArray($tab));
        $errors = array_merge($errors, $note->create());
    }

    if (count($errors) > 0) {
        echo "errors";
        dol_syslog("erreur task".print_r($errors,1),3);
        return 0;
    }else{
        $dir = $task->getFilesDir()."/";
        if(!is_dir($dir))
            mkdir($dir);
        foreach($_FILES as $fileT){
//            $dir = "/data/DOCUMENTS/bimp/societe/154049/";
            $file = $fileT['name'];
            
            
            move_uploaded_file($fileT['tmp_name'], $dir.$file);
        }
    }
    return 1;
}

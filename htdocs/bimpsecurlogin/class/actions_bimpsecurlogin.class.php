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

    var $max_tentative = 3;
    
    var $debug = 0;//0 pas de auth mail sur ip //1 pas de sms code ecran //2 normal

    var $message = array();
    public function __construct($db) {
        $this->db = $db;
    }

    public function setSecure($statut = false, $codeR = null) {//statut = 0 pas secure = 1 secure add cokkie secure = 2 session secure mais pas de cookie
        global $conf;
        $_SESSION['sucur'] = ($statut? "secur" : "no");
        if ($statut == 1) {
            if (is_null($codeR)) {
                $codeR = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
                $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "bimp_secure_log (id_user, crypt, ip) VALUES (" . $this->user->id . ",'" . $codeR . "', '" . $_SERVER['REMOTE_ADDR'] . "')");
            }

            $this->setCookie($codeR);
        }
        else
            $this->setCookie ();
    }
    
    public function setCookie($codeR = ""){
        global $conf;
        $int = 60 * 60 * 24 * 7;
        $_COOKIE[$this->nomCookie] = $codeR;
        setcookie($this->nomCookie, $codeR, time() + $int, $conf->file->dol_url_root['main']);
        
    }

    public function secur() {
        if (!$this->isSecur()) {
            $code = GETPOST("sms_code_1") . GETPOST("sms_code_2") . GETPOST("sms_code_3") . GETPOST("sms_code_4");
            if ($this->user->array_options['options_echec_auth'] < $this->max_tentative) {
                if (!empty($code))
                    $this->testCode($code);
                else
                    $this->createSendCode();
            }

            if (!$this->isSecur()) {
                if ($this->user->array_options['options_echec_auth'] >= $this->max_tentative)
                    $this->message[] = "<span class='red'>Compte bloqué</span>";
                    $message = implode("<br/>", $this->message);
                include(DOL_DOCUMENT_ROOT . '/bimpsecurlogin/views/formCode.php');
                die;
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

        $this->nomCookie = "secure_bimp_erp234" . $this->user->id;


//                $this->setSecure();
    }

    function isSecur() {
        $this->traiteMessageUser();
        
        $filename = DOL_DATA_ROOT . "/white-ip.txt";
        if (is_file($filename)) {//ip white liste
            $tmp = file_get_contents($filename);
            $tab = explode("\n", $tmp);
            foreach($tab as $ip){
                $tabT = explode("//", $ip);
                $ip = $tabT[0];
                if(stripos($_SERVER['REMOTE_ADDR'],$ip) !== false)
                    return 1;
            }
        }

        
        

        if (isset($_SESSION['sucur']) && $_SESSION['sucur'] == "secur")//session deja securise
            return 1;

        if (isset($_COOKIE[$this->nomCookie])) {//cokkie secur en place
            $crypt = $_COOKIE[$this->nomCookie];
            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "bimp_secure_log WHERE id_user = " . $this->user->id . " AND crypt = '" . $crypt . "'");
            if ($this->db->num_rows($sql) > 0) {
                $this->setSecure(1, $crypt);
                return 1;
            }
        }


        if(!$this->debug){//provisoir a viré
            $to = $this->traitePhone();
            $toM = $this->traiteMail();
            if (!$this->isPhoneMobile($to) && !$this->isMAil($toM))
                mailSyn2("ATTENTION Ip Inconnue phone KO MAIL ko ATTENTION", "tommy@bimp.fr, j.belhocine@bimp.fr, peter@bimp.fr, g.faure@bimp-pro.fr", "admin@bimp.fr", "Ip inconnue : " . $_SERVER['REMOTE_ADDR'] . " user " . $this->user->login . " phone : " . $to." mail :".$toM);
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
        $this->message[] = "Code incorrecte ".$this->user->array_options['options_echec_auth'] ." / ". $this->max_tentative;
        return false;
    }

    function createSendCode() {
        global $user;
        $okSms = $okMail = false;
        $code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
        require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");
        $to = $this->traitePhone();
        if ($this->isPhoneMobile($to)) {
            if($this->debug != 1){
                $text = "Votre code est : " . $code;
                $smsfile = new CSMSFile($to, "BIMP ERP", $text);
                if ($smsfile->sendfile()){
                    $this->message[] = 'Code envoyé au 0' . substr($to, 3, 5) . "****<br/><br/>";
                    $okSms = true;
                }
                $toM = $this->traiteMail();
                if($this->isMAil($toM) && mailSyn2("Code BIMP", $toM, "no-replay@bimp.fr", $text)){
                    $this->message[] = 'Code envoyé à ' . substr($toM, 0, 4) . "*******" . substr($toM, -7) . "<br/><br/>";
                    $okMail = true;
                }
            }
            else
                $this->message[] = "Vottre code est ".$code." il ne sera pas envoyé au ".$to;
            
            if($okSms || $okMail){
                $this->user->array_options['options_code_sms'] = $code;
                $this->user->update($user);
            }
        } else
            $this->message[] = "Pas de numéro pour l'envoie du code... ";
    }
    
    public function traiteMessageUser(){
            $to = $this->traitePhone();
            $toM = $this->traiteMail();
            if (!$this->isPhoneMobile($to) && !$this->isMAil($toM)){
                $message = "<a href='" . DOL_URL_ROOT . "/bimpcore/tabs/user.php'>Vos numéros de mobile (pro et perso) sont invalide ainsi que l'adresse mail de secours : dans quelques jours vous ne pourrez plus accéder à l'application, inscrire 'NO' si vous n'avez pas de téléphone pro et que vous refusez d'inscrire votre tel perso (qui ne serait utilisé que pour l'envoi de code par SMS et non communiqué aux équipes) et merci d'indiquer un mail de secours en cliquant sur ce message</a>";
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
                setEventMessages($message, null, 'warnings');
            }
    }

    public function traitePhone() {
        $phone = str_replace(array(" ", "-", ":"), "", $this->user->user_mobile);
        if (stripos($phone, "+") === false) {
            if (stripos($phone, "0") === 0)
                $phone = "+33" . substr($phone, 1);
        }
        if (!$this->isPhoneMobile($phone) && $this->user->array_options['options_phone_perso'] != ""){//Si pas trouver 
            $phone = $this->user->array_options['options_phone_perso'];
            if (stripos($phone, "+") === false) {
                if (stripos($phone, "0") === 0)
                    $phone = "+33" . substr($phone, 1);
            }
        }
                
                
//        if (!$this->isPhoneMobile($phone) && strtolower($phone) != "no"){
//            setEventMessages("<a href='" . DOL_URL_ROOT . "/bimpcore/tabs/user.php'>Vos numéros de mobile (pro et perso) sont invalide : dans quelques jours vous ne pourrez plus accéder à l'application, inscrire 'NO' si vous n'avez pas de téléphone pro et que vous refusez d'inscrire votre tel perso (qui ne serait utilisé que pour l'envoi de code par SMS et non communiqué aux équipes)</a>", null, 'warnings');
//            setEventMessages("<a href='" . DOL_URL_ROOT . "/bimpcore/tabs/user.php'>Vos numéros de mobile (pro et perso) sont invalide : dans quelques jours vous ne pourrez plus accéder à l'application, inscrire 'NO' si vous n'avez pas de téléphone pro et que vous refusez d'inscrire votre tel perso (qui ne serait utilisé que pour l'envoi de code par SMS et non communiqué aux équipes)</a>", null, 'warnings');
//        }
        return $phone;
    }
    
    public function traiteMail(){
        $mail = "";
        if(isset($this->user->array_options['options_mail_sec']))
            $mail = $this->user->array_options['options_mail_sec'];
        return $mail;
    }
    
    public function isPhoneMobile($phone){
        return (stripos($phone, "+336") === 0 || stripos($phone, "+337") === 0);
    }
    
    public function isMAil($mail){
        if(stripos($mail, "@") > 0)
                return 1;
        return 0;
    }

}
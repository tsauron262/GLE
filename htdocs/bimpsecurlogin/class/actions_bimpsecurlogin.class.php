<?php

class Actionsbimpsecurlogin
{

    function doActions($parameters, &$object, &$action, $hookmanager)
    {
        
    }

    function setContentSecurityPolicy($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $db, $conf;
        if ($user->id > 0) {//l'adresse n'est pas wite liste et le module est activé
            if (!class_exists('BimpTools') || BimpTools::getContext() != "public") {
                $secur = new securLogSms($db);
                $secur->fetch($user);
            }
        }
    }

    function printLeftBlock()
    {
        global $user;
//        if(isset($user->array_options['options_date_val_mdp']) && $user->array_options['options_date_val_mdp'] < (time()+(3600*24*2)))
//            setEventMessages("<a href='".DOL_URL_ROOT."/user/card.php?id=".$user->id."'>Merci de changer obligatoirement votre mdp</a>", null, 'errors');
//        if(isset($user->array_options['options_date_val_mdp']) && $user->array_options['options_date_val_mdp'] < (time()))
//            if(stripos($_SERVER['REQUEST_URI'], "/user/card.php") === false)
//                header("Location: ".DOL_URL_ROOT."/user/card.php?id=".$user->id);

        return 0;
    }
}

class securLogSms
{

    var $max_tentative = 3;
    var $debug = 2; //0 pas de verif //1 pas de sms code ecran //2 normal
    var $message = array();
    var $ip = '';

    public function __construct($db)
    {
        $this->db = $db;
        $this->filename = PATH_TMP . "/bimpcore/white-ip.txt";

        if (defined('MOD_DEV') && MOD_DEV == 1 && $this->debug > 1) {
            $this->debug = 1;
        }

        if (class_exists("BimpCore") && BimpCore::getConf('mode_securlogin', "") != "")
            $this->debug = BimpCore::getConf('mode_securlogin');

//        $this->debug = 0;
    }

    public function testSecur()
    {
        if (!$this->isSecur()) {
            $code = GETPOST("sms_code_1") . GETPOST("sms_code_2") . GETPOST("sms_code_3") . GETPOST("sms_code_4");
            if ($this->user->array_options['options_echec_auth'] < $this->max_tentative) {
                $dateFinBloquage = time() - (60 * 5);

                $secondeRestante = (int) $this->user->array_options['options_heure_sms'] - $dateFinBloquage;
                
                
                if ((empty($code) && $secondeRestante < 0) || $this->user->array_options['options_code_sms'] == '')
                    $this->createSendCode();
                elseif (!empty($code))
                    $this->testCode($code);
                else
                    $this->message[] = "Vous devez attendre " . date("i", $secondeRestante) . " minutes " . date("s", $secondeRestante) . " secondes pour avoir un nouveau code !";
            }

            if (!$this->isSecur()) {
                if ($this->user->array_options['options_echec_auth'] >= $this->max_tentative)
                    $this->message[] = "<span class='red'>Compte bloqué</span><br/>Seul votre supérieur peut vous débloquer<br/>Contactez-le";
                $message = implode("<br/>", $this->message);
                include(DOL_DOCUMENT_ROOT . '/bimpsecurlogin/views/formCode.php');
                die;
            }
        }
    }

    public function fetch($id_user)
    {
        global $user, $conf;
        if ($id_user == $user->id)
            $this->user = $user;
        elseif (is_int($id_user)) {
            $this->user = new User($this->db);
            $this->user->fetch($id_user);
        } elseif (is_object($id_user))
            $this->user = $id_user;
        $this->user->oldcopy = clone $this->user;
        $this->ip = synopsisHook::getUserIp();

        $this->nomCookie = "secu_erp".$conf->file->dol_url_root['main']."_" . $this->user->id . "_" . str_replace(".", "_", $this->ip);

        $this->testSecur();
    }

    public function setSecure($statut = false, $codeR = null)
    {//statut = 0 pas secure = 1 secure add cokkie secure = 2 session secure mais pas de cookie
        global $conf;
        $_SESSION['sucur'] = ($statut ? $this->nomCookie : "no");
        if ($statut == 1) {
            if (is_null($codeR)) {
                $codeR = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
                $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "bimp_secure_log (id_user, crypt, IP) VALUES (" . $this->user->id . ",'" . $codeR . "', '" . $this->ip . "')");
            }

            $this->setCookie($codeR);

            $this->createWhiteList();
        } else
            $this->setCookie();
    }

    public function setCookie($codeR = "")
    {
        global $conf;
        $int = 60 * 60 * 24 * 7;
        $_COOKIE[$this->nomCookie] = $codeR;
        $arr_cookie_options = array (
            'expires' =>  time() + $int, 
            'path' => '',//$conf->file->dol_url_root['main'], 
            'domain' => '', // leading dot for compatibility or use subdomain
            'secure' => true,     // or false
            'httponly' => true,    // or false
            'samesite' => 'Lax' // None || Lax  || Strict
        );
        setcookie($this->nomCookie, $codeR, $arr_cookie_options);
    }

    function asSecureCokie()
    {
        if (isset($_COOKIE[$this->nomCookie])) {//cokkie secur en place
            $crypt = $_COOKIE[$this->nomCookie];
            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "bimp_secure_log WHERE id_user = " . $this->user->id . " AND crypt = '" . $crypt . "' AND IP = '" . $this->ip . "' AND DATEDIFF(now(), tms ) <= 31");
//            echo "<pre>"; print_r($_COOKIE);die("SELECT * FROM " . MAIN_DB_PREFIX . "bimp_secure_log WHERE id_user = " . $this->user->id . " AND crypt = '" . $crypt . "' AND IP = '" . $this->ip . "'");
            if ($this->db->num_rows($sql) > 0) {
                $this->setSecure(1, $crypt);
                return 1;
            }
        }
    }

    function isSecur()
    {
        if ($this->debug == 0)
            return 1;

        $this->traiteMessageUser();

//        if (isset($_SESSION['sucur']) && $_SESSION['sucur'] == $this->nomCookie)//session deja securise
//            return 1;

        if ($this->asSecureCokie())
            return 1;
        
        if ($this->isIpWhite($this->ip))
            return 1;



//        if (!$this->debug) {//provisoir a viré
//            $to = $this->traitePhone();
//            $toM = $this->traiteMail();
//            if (!$this->isPhoneMobile($to) && !$this->isMAil($toM))
//                mailSyn2("ATTENTION Ip Inconnue phone KO MAIL ko ATTENTION", "tommy@bimp.fr, j.belhocine@bimp.fr, peter@bimp.fr, g.faure@bimp-pro.fr", "admin@bimp.fr", "Ip inconnue : " . $_SERVER['REMOTE_ADDR'] . " user " . $this->user->login . " phone : " . $to . " mail :" . $toM);
//            //                else
//            //                    mailSyn2("Ip Inconnue phone OK", "tommy@bimp.fr", "admin@bimp.fr", "Ip inconnue : ".$_SERVER['REMOTE_ADDR']." user ".$this->user->login. " phone : ".$to);
//            $this->setSecure(2);
//            return 1;
//        }


        return 0;
    }

    function isIpWhite($ipTest)
    {

        if (stripos($this->ip, '10.20.') !== false)//interne
            return 1;
        
        if (stripos($this->ip, '10.212.13') === 0)//vpn
            return 1;
        
        if (stripos($this->ip, '10.8.12.') === 0)//vpn
            return 1;
        
        if (is_file($this->filename)) {//ip white liste
            $tmp = file_get_contents($this->filename);
            $tab = explode("\n", $tmp);
            foreach ($tab as $ip) {
                $tabT = explode("//", $ip);
                $ip = $tabT[0];
                if (stripos($ipTest, $ip) !== false) {//Whte listé, on securise
                    $this->setSecure(1);
                    return 1;
                }
            }
        }
    }

    function testCode($code)
    {
        global $user;
        $this->user->oldcopy = clone($this->user);
        if ($this->user->array_options['options_code_sms'] == $code && $code != '') {
            $this->user->array_options['options_echec_auth'] = 0;
            $this->user->array_options['options_code_sms'] = '';
            $this->user->update($user);
            $this->setSecure(1);
            return true;
        }
        $this->user->array_options['options_echec_auth']++;
        $this->user->update($user);
        $this->message[] = "Code incorrecte " . $this->user->array_options['options_echec_auth'] . " / " . $this->max_tentative;
        return false;
    }

    function createSendCode()
    {
        global $user, $langs;
        if (!is_object($langs)) { // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
            include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
            $langs = new Translate("", $conf);
            $langcode = (GETPOST('lang', 'aZ09', 1) ? GETPOST('lang', 'aZ09', 1) : (empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT));
            if (defined('MAIN_LANG_DEFAULT'))
                $langcode = constant('MAIN_LANG_DEFAULT');
            $langs->setDefaultLang($langcode);
        }
        $okSms = $okMail = false;
        $code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
        $this->user->array_options['options_code_sms'] = $code;
        $this->user->array_options['options_heure_sms'] = dol_now();
        $this->user->update($user);
        $text = "Votre code est : " . $code;

        if ($this->debug != 1) {
            if (!empty($conf->global->MAIN_DISABLE_ALL_SMS)) {
                $this->message[] = 'Envoi des SMS désactivé pour le moment';
            } else {
                $to = $this->traitePhone();
                if ($this->isPhoneMobile($to)) {
                    require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");

                    $smsfile = new CSMSFile($to, "BIMP ERP", $text);
                    if ($smsfile->sendfile()) {
                        $this->message[] = 'Code envoyé au 0' . substr($to, 3, 5) . "****<br/><br/>";
                        $okSms = true;
                    }
                }
            }

            $toM = $this->traiteMail();
            if ($this->isMAil($toM) && mailSyn2("Code BIMP", $toM, null, $text . ' IP : ' . $this->ip)) {
                $this->message[] = 'Code envoyé à ' . substr($toM, 0, 4) . "*******" . substr($toM, -7) . "<br/><br/>";
                $okMail = true;
            }
            //            }
            //            mailSyn2("Code envoyé", "admin@bimp.fr", "admin@bimp.fr", "Bonjour un code a été envoyé " . ($okSms ? "par sms " : "") . ($okMail ? "par mail " : "") . " pour l'utilisateur " . $this->user->getNomUrl(1) . " ip " . $_SERVER['REMOTE_ADDR']);

            if (!$okSms && !$okMail) {
                $this->message[] = "Vous n’avez fourni ni votre téléphone, ni votre mail.<br/>Seul votre supérieur peut vous communiquer votre code<br/>Contactez-le, puis complétez ci-dessous";
            }
        } else {
            $this->message[] = "Votre code est " . $code . " il ne sera pas envoyé au " . $to;
        }
    }

    public function traiteMessageUser()
    {
        if (defined('BIMP_LIB') && BimpCore::isContextPublic()) {
            return;
        }

        $to = $this->traitePhone();
        $toM = $this->traiteMail();
        if (!$this->isPhoneMobile($to) && !$this->isMAil($toM)) {
            $message = "<a href='" . DOL_URL_ROOT . "/bimpcore/tabs/user.php'>Vos numéros de mobile (pro et perso) sont invalide ainsi que l'adresse mail de secours : dans quelques jours vous ne pourrez plus accéder à l'application, inscrire 'NO' si vous n'avez pas de téléphone pro et que vous refusez d'inscrire votre tel perso (qui ne serait utilisé que pour l'envoi de code par SMS et non communiqué aux équipes) et merci d'indiquer un mail de secours en cliquant sur ce message</a>";
            setEventMessages($message, null, 'errors');
            setEventMessages($message, null, 'errors');
            setEventMessages($message, null, 'errors');
            setEventMessages($message, null, 'errors');
            setEventMessages($message, null, 'errors');
            setEventMessages($message, null, 'mesgs');
            setEventMessages($message, null, 'mesgs');
            setEventMessages($message, null, 'mesgs');
            setEventMessages($message, null, 'mesgs');
            setEventMessages($message, null, 'mesgs');
            setEventMessages($message, null, 'warnings');
            setEventMessages($message, null, 'warnings');
            setEventMessages($message, null, 'warnings');
            setEventMessages($message, null, 'warnings');
            setEventMessages($message, null, 'warnings');
            setEventMessages($message, null, 'warnings');
        }

        $tabMsg = array();
//        $tabMsg['newVersion'] = "Nouvelle version, si vous rencontrez des problèmes, les signaler au plus vite.<br/>debugerp@bimp.fr - 06 28 33 50 81";

        foreach ($tabMsg as $name => $detailMsg) {
            if (!is_array($detailMsg))
                $detailMsg = array("msg" => $detailMsg);
            if (!isset($detailMsg['mode']))
                $detailMsg['mode'] = 'warnings';
            if (!isset($detailMsg['nb']))
                $detailMsg['nb'] = 3;

            if (!isset($_SESSION['msgsPerso'][$name]))
                $_SESSION['msgsPerso'][$name] = 0;
            if ($_SESSION['msgsPerso'][$name] < $detailMsg['nb']) {
                setEventMessages($detailMsg['msg'], null, $detailMsg['mode']);
                $_SESSION['msgsPerso'][$name]++;
            }
        }
    }

    public function traitePhone()
    {
//        $phone = str_replace(array(" ", "-", ":"), "", $this->user->user_mobile);
//        if (stripos($phone, "+") === false) {
//            if (stripos($phone, "0") === 0)
//                $phone = "+33" . substr($phone, 1);
//        }
//        if (!$this->isPhoneMobile($phone) && $this->user->array_options['options_phone_perso'] != "") {//Si pas trouver 
//            $phone = str_replace(array(" ", "-", ":"), "", $this->user->array_options['options_phone_perso']);
//            if (stripos($phone, "+") === false) {
//                if (stripos($phone, "0") === 0)
//                    $phone = "+33" . substr($phone, 1);
//            }
//        }
        $nums = array($this->user->user_mobile, $this->user->office_phone, $this->user->array_options['options_phone_perso']);
        foreach ($nums as $phone) {
            $phone = str_replace(array(" ", "-", ":"), "", $phone);
            if (stripos($phone, "+") === false) {
                if (stripos($phone, "0") === 0)
                    $phone = "+33" . substr($phone, 1);
            }
            if ($this->isPhoneMobile($phone))
                return $phone;
        }

        return '';
    }

    public function traiteMail()
    {
        $mail = "";
        if (isset($this->user->array_options['options_mail_sec']))
            $mail = $this->user->array_options['options_mail_sec'];
        return $mail;
    }

    public function isPhoneMobile($phone)
    {
        return (stripos($phone, "+336") === 0 || stripos($phone, "+337") === 0);
    }

    public function isMAil($mail)
    {
        if (stripos($mail, "@") > 0)
            return 1;
        return 0;
    }

    public function createWhiteList()
    {
//        $sql = $this->db->query("SELECT count(DISTINCT(fk_user)) as nb, `ip` FROM `".MAIN_DB_PREFIX."events` WHERE `type` = 'USER_LOGIN' GROUP BY `ip` ORDER BY `nb` DESC");
        $sql = $this->db->query("SELECT COUNT(DISTINCT(id_user)) as nb, IP as ip FROM `" . MAIN_DB_PREFIX . "bimp_secure_log` WHERE DATEDIFF(now(), tms ) <= 31 GROUP BY IP ORDER BY `nb` DESC");
        $tabIp = array("78.195.193.207//flo", '91.164.189.142//tommy');
        while ($ln = $this->db->fetch_object($sql))
            if ($ln->nb > 2)
                $tabIp[] = $ln->ip;
        file_put_contents($this->filename, implode("\n", $tabIp));
        //Viré les 1 mois
    }
}

// 
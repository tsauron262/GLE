<?php
class Session {
    // Variable interne contenant la BDD
    private $_Connexion_BDD;
    private $table = "llx_bimp_php_session";
    private static $sessionBase = array();
    private $sessionId = '';
    private $db = null;
    private $timeDeb = 0;
    // Initialisation de la session lors de l'appel de la classe
    public function __construct($db){
        $this->timeDeb = hrtime(true);
//         Ouverture de la connexion à la BDD et association de cette connexion à la variable $_Connexion_BDD
//        $this->_Connexion_BDD = $this->general_connexion_bdd($dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_port);
        // Paramétrage des sessions
        $this->db = $db;
        session_set_save_handler(
            array($this, "session_ouverture"),
            array($this, "session_fermeture"),
            array($this, "session_lecture"),
            array($this, "session_ecriture"),
            array($this, "session_destruction"),
            array($this, "session_nettoyage")
        );
        // Démarrage des sessions
        if(!defined('NOREQUIREDB')){
            session_start();

            $this->alimentSession();
        }
//        echo '<pre>';print_r($_SESSION);
    }
    
    
//    function general_connexion_bdd($dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_port) {
//        $bdd_dns='mysql:host='.$dolibarr_main_db_host.';port='.$dolibarr_main_db_port.';dbname='.$dolibarr_main_db_name.';charset=utf8';
//
//        $bdd_option['PDO::ATTR_EMULATE_PREPARES']='FALSE';
//        $bdd_option['PDO::ATTR_ERRMODE']='PDO::ERRMODE_EXCEPTION';
//        $bdd_option['PDO::ATTR_DEFAULT_FETCH_MODE']='PDO::FETCH_ASSOC';
//
//        // Instantiation de la BDD
//        try {
//            $Connexion_BDD = new PDO($bdd_dns, $dolibarr_main_db_user, $dolibarr_main_db_pass, $bdd_option);
//        }
//        catch (PDOException $e) {
//            print "Erreur de connexion à la BDD ! Message : " . $e->getMessage() . "<br/>";
//            die();
//        }
//
//        return $Connexion_BDD;
//    }
    // Définition des fonctions de gestion des sessions
    ///////////////////////////////////////////////////
    // Ouverture des sessions
    public function session_ouverture($savePath, $sessionID) {
        $this->info('ouverture');
        if (is_object($this->db)) {
            // Si la connexion existe, on renvoie "true".
            return true;
        }
        // En cas d'erreur, on force php à annuler l'utilisation des sessions.
        return false;
    }
    // Fermeture des sessions
    public function session_fermeture() {
        // Nettoyage de la BDD lors de la fermeture pour ne pas attendre le nettoyage automatique
        $this->session_nettoyage(ini_get("session.gc_maxlifetime"));
        // Destruction de la connexion
//        $this->_Connexion_BDD = null;
        // Renvoie de "true" pour valider la fermeture.
        return true;
    }
    // Lecture des sessions
    public function session_lecture($sessionID) {
        $this->sessionId = $sessionID;
        
//        $sql = $this->db->query("SELECT `data` FROM ".$this->table." WHERE `id_session` = '".$sessionID."' LIMIT 1");
//        if($this->db->num_rows($sql) > 0){
//            $ln = $this->db->fetch_object($sql);
//            return $ln->data;
//        }
        
        return '';
    }
    
    public function alimentSession(){
        $sql = $this->db->query("SELECT * FROM ".$this->table." WHERE `id_session` = '".$this->sessionId."' LIMIT 1");
        if($this->db->num_rows($sql) > 0){
            $ln = $this->db->fetch_object($sql);
            
            $_SESSION = json_decode($ln->data, true);
            if($ln->login != '')
            $_SESSION['dol_login'] = $ln->login;
        }
        
        self::$sessionBase = $_SESSION;
    }
    // Ecriture des sessions
    public function session_ecriture($sessionID, $sessionData) {
        $datetime_actuel = new DateTime("now", new DateTimeZone('Europe/Paris'));
        $time = (hrtime(true)-$this->timeDeb) / 1000000000;
        $_SESSION['time']['erp'.ID_ERP] += $time;
        $timeTot = 0;
        for($i=0;$i<10;$i++){
            if(isset($_SESSION['time']['erp'.$i]))
                $timeTot +=$_SESSION['time']['erp'.$i];
        }
//        $sessionData = addslashes($sessionData);
//        $this->db->query("INSERT INTO ".$this->table." (`id_session`, `data`, `update`) VALUES ('".$sessionID."', '".$sessionData."', '".$datetime_actuel->format('Y-m-d H:i:s')."') ON DUPLICATE KEY UPDATE `data` = '".$sessionData."'");
        
        $diff1 = $this->arrayRecursiveDiff($_SESSION, self::$sessionBase);
        $diff2 = $this->arrayRecursiveDiff(self::$sessionBase, $_SESSION);
        unset($diff1['newtoken']);
        unset($diff2['newtoken']);
        unset($diff1['token']);
        unset($diff2['token']);
        if($time < 1){
            unset($diff1['time']);
            unset($diff2['time']);
        }
        
        if(count($diff1) > 0 || count($diff2) > 0){

            $data = $_SESSION;
            $login = $_SESSION['dol_login'];
            unset($data['dol_login']);
            $data = addslashes(json_encode($data));
            if((isset($login) && $login != '') || (isset($_SESSION['userClient']) && $_SESSION['userClient'] != '')){
                $req = "INSERT INTO ".$this->table." (`id_session`, `data`, login, `update`, data_time) VALUES ('".$sessionID."', '".$data."', '".$login."', '".$datetime_actuel->format('Y-m-d H:i:s')."', '".$timeTot."') ON DUPLICATE KEY UPDATE login = '".$login."', `data` = '".$data."', data_time = '".$timeTot."'";
                $this->db->query($req);
            }
    //        else{
    //            echo '<pre>ecriture';print_r($_SESSION);
    //        }
        }
        return true;
    }
    
    function arrayRecursiveDiff($aArray1, $aArray2) {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
          if (array_key_exists($mKey, $aArray2)) {
            if (is_array($mValue)) {
              $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
              if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
            } else {
              if ($mValue != $aArray2[$mKey]) {
                $aReturn[$mKey] = $mValue;
              }
            }
          } else {
            $aReturn[$mKey] = $mValue;
          }
        }
        return $aReturn;
      } 
    
    // Destruction des sessions
    public function session_destruction($sessionID) {
        
        $resultat = $this->db->query("DELETE FROM ".$this->table." WHERE `id_session` = '".$sessionID."'");
        return true;
        
        
        
        $this->info('destruction<br/>');
        // Préparation de la requête
//        $requete = $this->_Connexion_BDD->prepare("DELETE FROM ".$this->table." WHERE `id_session` = ?");
//        // Execution de la requête
//        $requete->execute([$sessionID]);
//        // Récupération des résultats
//        $resultat = $requete->rowCount();
//        if ( $resultat >= 1 ) {
            // Si la suppression a réussi, on renvoie "true".
//            return true;
//        };
//        // Si quelque chose ne fonctionne pas, on retourne "false".
//        return false;
    }
    // Nettoyage de la BDD
    public function session_nettoyage($sessionMaxLifetime) {
        if(!defined('NO_SESSION_NETTOYAGE') && class_exists('BimpCore')){
            $date = BimpCore::getConf('date_nettoyage_session');
            if(!$date || $date < time() - 600){
                if(is_object($this->db)){
                    if($sessionMaxLifetime < 43200)
                        $sessionMaxLifetime = 43200;


                    $timestamp_expiration = time() - $sessionMaxLifetime;
                    $date_expiration = new DateTime("@".$timestamp_expiration);
                    $date_expiration->setTimezone(new DateTimeZone('Europe/Paris'));

                    $this->db->query("DELETE FROM ".$this->table." WHERE `update` <= '".$date_expiration->format('Y-m-d H:i:s')."'");
                    BimpCore::setConf('date_nettoyage_session', time());
                }
            }
        }
        return true;
    }
    
    public function info($str){
//        echo $str."<br/>";
    }
}



<?php
class Session {
    // Variable interne contenant la BDD
    private $_Connexion_BDD;
    private $table = "llx_bimp_php_session";
    private $sessionId = '';
    // Initialisation de la session lors de l'appel de la classe
    public function __construct($db){
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
        session_start();
        
        $this->alimentSession();
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
//        print_r($_SESSION);die;
    }
    // Ecriture des sessions
    public function session_ecriture($sessionID, $sessionData) {
        $datetime_actuel = new DateTime("now", new DateTimeZone('Europe/Paris'));
//        $sessionData = addslashes($sessionData);
//        $this->db->query("INSERT INTO ".$this->table." (`id_session`, `data`, `update`) VALUES ('".$sessionID."', '".$sessionData."', '".$datetime_actuel->format('Y-m-d H:i:s')."') ON DUPLICATE KEY UPDATE `data` = '".$sessionData."'");
        
        $data = $_SESSION;
        $login = $_SESSION['dol_login'];
        unset($data['dol_login']);
        $data = addslashes(json_encode($data));
        if((isset($login) && $login != '') || (isset($_SESSION['userClient']) && $_SESSION['userClient'] != ''))
            $this->db->query("INSERT INTO ".$this->table." (`id_session`, `data`, login, `update`) VALUES ('".$sessionID."', '".$data."', '".$login."', '".$datetime_actuel->format('Y-m-d H:i:s')."') ON DUPLICATE KEY UPDATE `data` = '".$data."'");
//        else{
//            echo '<pre>ecriture';print_r($_SESSION);
//        }
        return true;
    }
    // Destruction des sessions
    public function session_destruction($sessionID) {
        
//        $resultat = $this->db->query("DELETE FROM ".$this->table." WHERE `id_session` = '".$sessionID."'");
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
        if(is_object($this->db)){
            $timestamp_expiration = time() - $sessionMaxLifetime;
            $date_expiration = new DateTime("@".$timestamp_expiration);
            $date_expiration->setTimezone(new DateTimeZone('Europe/Paris'));

            $this->db->query("DELETE FROM ".$this->table." WHERE `update` <= '".$date_expiration->format('Y-m-d H:i:s')."'");
        }
        return true;
    }
    
    public function info($str){
//        echo $str."<br/>";
    }
}



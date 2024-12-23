<?php

//ini_set('memory_limit', '512M');

/* Copyright (C) 2001		Fabien Seisen			<seisen@linuxfr.org>
 * Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2020       Peter TKATCHENKO      <peter@bimp.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/core/db/mysqlic.class.php
 * 	\brief      Class file to manage Dolibarr database access for a dynamically discovered MySQL database
 */
require_once DOL_DOCUMENT_ROOT . '/core/db/mysqli.class.php';

/**
 * 	Class to manage Dolibarr database access for a MySQL database using the MySQLi extension
 *      Database server addresses will be discovered using Consul servers, the results can be cached using Redis server
 */
/*
 *      Example of configuration (conf/config.php):
 * 
  define('CONSUL_SERVERS', serialize (array("http://10.192.20.115:8300", "http://10.192.20.116:8300", "http://10.192.20.117:8300")));
  define('CONSUL_SERVICE_DATABASE', "bderpdev");
  define('CONSUL_SERVICES_PRIORITY_WRITE', serialize (array(2,1,3)));
  define('CONSUL_SERVICES_USE_FOR_WRITE', 1);
  define('CONSUL_SERVICES_PRIORITY_READ', serialize (array(1,3,2)));
  define('CONSUL_SERVICES_USE_FOR_READ', 2);
  define('CONSUL_SET_MAIN_DB_HOST', true);
  define('CONSUL_READ FROM_WRITE_DB_HOST', true);
  define('CONSUL_READ FROM_WRITE_DB_HOST_TIME', 120);    // Seconds
  define('CONSUL_USE_REDIS_CACHE', true);
  define('CONSUL_REDIS_CACHE TTL', 120);  // Seconds

  define('REDIS_USE_LOCALHOST', true);
  define('REDIS_LOCALHOST_SOCKET', "/var/run/redis/redis.sock");
  define('REDIS_USE_CONSUL_SEARCH', false);
  define('CONSUL_SERVICE_REDIS', "rediserpdev");
  define('REDIS_USE_HOST', false);
  define('REDIS_HOST', "10.192.20.92:6379");
 *
 */
class DoliDBMysqliC extends DoliDBMysqli
{

    //! Path to Consul endpoint

    const CONSUL_PATH = '/v1/health/service/';

    private $CONSUL_SERVERS = array();
    private $CONSUL_SERVICE_DATABASE;
    private $CONSUL_SERVICES_USE_FOR_WRITE;
    private $CONSUL_SERVICES_USE_FOR_READ;
    private $CONSUL_USE_REDIS_CACHE;
    private $REDIS_USE_LOCALHOST;
    private $REDIS_LOCALHOST_SOCKET;
    private $CONSUL_REDIS_CACHE_TTL;
    private $CONSUL_READ_FROM_WRITE_DB_HOST;
    private $CONSUL_READ_FROM_WRITE_DB_HOST_TIME;
    private $_svc_read = array();
    private $_svc_write = array();
    private $_last_discover_time;
    public $database_pass;
    public $thread_id = 0;
    public $timeReconnect = 0;
    
    public $useMysqlic = false;

    /**
     * 	Constructor.
     * 	This create an opened connexion to a database server and eventually to a database
     *
     * 	@param      string	$type		Type of database (mysql, pgsql...)
     * 	@param	    string	$host		Address of database server
     * 	@param	    string	$user		Nom de l'utilisateur autorise
     * 	@param	    string	$pass		Mot de passe
     * 	@param	    string	$name		Nom de la database
     * 	@param	    int		$port		Port of database server
     */
    function __construct($type, $host, $user, $pass, $name = '', $port = 0)
    {
//        ini_set('display_errors', 1);

        global $conf, $langs;
        
        
        
        if (empty($conf->db->dolibarr_main_db_collation)) {//old install 
                $conf->db->dolibarr_main_db_collation = 'utf8_unicode_ci'; // Old installation
        }
        if (empty($conf->db->dolibarr_main_db_character_set)) {
                $conf->db->dolibarr_main_db_character_set = 'utf8'; // Old installation
        }

        // Note that having "static" property for "$forcecharset" and "$forcecollate" will make error here in strict mode, so they are not static
        if (!empty($conf->db->character_set))
            $this->forcecharset = $conf->db->character_set;
        if (!empty($conf->db->dolibarr_main_db_collation))
            $this->forcecollate = $conf->db->dolibarr_main_db_collation;

        $this->database_user = $user;
        $this->database_host = "";
        $this->database_port = 0;
        $this->database_pass = $pass;
        $this->database_name = $name;

        $this->transaction_opened = 0;

        //print "Name DB: $host,$user,$pass,$name<br>";

        if (!class_exists('mysqli')) {
            $this->connected = false;
            $this->ok = false;
            $this->error = "Mysqli PHP functions for using Mysqli driver are not available in this version of PHP. Try to use another driver.";
            dol_syslog(get_class($this) . "::DoliDBMysqliC : Mysqli PHP functions for using Mysqli driver are not available in this version of PHP. Try to use another driver.", LOG_ERR);
        }
        /*
          if (! $host)
          {
          $this->connected = false;
          $this->ok = false;
          $this->error=$langs->trans("ErrorWrongHostParameter");
          dol_syslog(get_class($this)."::DoliDBMysqliC : Connect error, wrong host parameters",LOG_ERR);
          }
         */

        $this->connected = false;
        $this->ok = false;
        $this->database_selected = false;

        //      define('CONSUL_SERVERS', serialize (array("http://10.192.20.115:8300", "http://10.192.20.116:8300", "http://10.192.20.117:8300")));
        if (!defined('CONSUL_SERVERS')) {
            dol_syslog("Constante CONSUL_SERVERS non definie", LOG_ERR);
            return FALSE;
        } else
            $this->CONSUL_SERVERS = unserialize(CONSUL_SERVERS);

        //      define('CONSUL_SERVICE_DATABASE', "bderpdev");
        if (!defined('CONSUL_SERVICE_DATABASE')) {
            dol_syslog("Constante CONSUL_SERVICE_DATABASE non definie", LOG_ERR);
            return FALSE;
        } else
            $this->CONSUL_SERVICE_DATABASE = CONSUL_SERVICE_DATABASE;

        //  define('CONSUL_SERVICES_USE_FOR_WRITE', 1);
        if (!defined('CONSUL_SERVICES_USE_FOR_WRITE')) {
            dol_syslog("Constante CONSUL_SERVICES_USE_FOR_WRITE non definie", LOG_WARNING);
            $this->CONSUL_SERVICES_USE_FOR_WRITE = 1; // Default to 1
        } else
            $this->CONSUL_SERVICES_USE_FOR_WRITE = CONSUL_SERVICES_USE_FOR_WRITE;

        //  define('CONSUL_SERVICES_USE_FOR_READ', 2);
        if (!defined('CONSUL_SERVICES_USE_FOR_READ')) {
            dol_syslog("Constante CONSUL_SERVICES_USE_FOR_READ non definie", LOG_WARNING);
            $this->CONSUL_SERVICES_USE_FOR_READ = 1; // Default to 1
        } else
            $this->CONSUL_SERVICES_USE_FOR_READ = CONSUL_SERVICES_USE_FOR_READ;

        //      define('CONSUL_USE_REDIS_CACHE', true);
        if (!defined('CONSUL_USE_REDIS_CACHE')) {
            dol_syslog("Constante CONSUL_USE_REDIS_CACHE non definie", LOG_WARNING);
            $this->CONSUL_USE_REDIS_CACHE = FALSE;
        } else
            $this->CONSUL_USE_REDIS_CACHE = CONSUL_USE_REDIS_CACHE;

        //      define('REDIS_USE_LOCALHOST', true);
        if (!defined('REDIS_USE_LOCALHOST')) {
            dol_syslog("Constante REDIS_USE_LOCALHOST non definie", LOG_WARNING);
            $this->REDIS_USE_LOCALHOST = true;
        } else
            $this->REDIS_USE_LOCALHOST = REDIS_USE_LOCALHOST;

        //      define('REDIS_LOCALHOST_SOCKET', "/var/run/redis/redis.sock");
        if (!defined('REDIS_LOCALHOST_SOCKET')) {
            dol_syslog("Constante REDIS_LOCALHOST_SOCKET non definie", LOG_WARNING);
            $this->REDIS_LOCALHOST_SOCKET = "/var/run/redis/redis.sock";
        } else
            $this->REDIS_LOCALHOST_SOCKET = REDIS_LOCALHOST_SOCKET;

        //      define('CONSUL_REDIS_CACHE_TTL', 120);  // Seconds
        if (!defined('CONSUL_REDIS_CACHE_TTL')) {
            dol_syslog("Constante CONSUL_REDIS_CACHE_TTL non definie", LOG_WARNING);
            $this->CONSUL_REDIS_CACHE_TTL = 120;
        } else
            $this->CONSUL_REDIS_CACHE_TTL = CONSUL_REDIS_CACHE_TTL;

        //      define('CONSUL_READ_FROM_WRITE_DB_HOST', true);
        if (!defined('CONSUL_READ_FROM_WRITE_DB_HOST')) {
            dol_syslog("Constante CONSUL_READ_FROM_WRITE_DB_HOST non definie", LOG_WARNING);
            $this->CONSUL_READ_FROM_WRITE_DB_HOST = TRUE; // Default to TRUE
        } else
            $this->CONSUL_READ_FROM_WRITE_DB_HOST = CONSUL_READ_FROM_WRITE_DB_HOST;

        //      define('CONSUL_READ_FROM_WRITE_DB_HOST_TIME', 120);    // Seconds
        if (!defined('CONSUL_READ_FROM_WRITE_DB_HOST_TIME')) {
            dol_syslog("Constante CONSUL_READ_FROM_WRITE_DB_HOST_TIME non definie", LOG_WARNING);
            $this->CONSUL_READ_FROM_WRITE_DB_HOST_TIME = 120; // Default to 120
        } else
            $this->CONSUL_READ_FROM_WRITE_DB_HOST_TIME = CONSUL_READ_FROM_WRITE_DB_HOST_TIME;

        if(!$this->CONSUL_USE_REDIS_CACHE){
//            $this->error = "Cannot use Mysqlic with !CONSUL_USE_REDIS_CACHE";
            dol_syslog(get_class($this) . "::DoliDBMysqliC Connect error: Cannot use Mysqlic with !CONSUL_USE_REDIS_CACHE", LOG_ERR);
            return parent::__construct('mysqli', $host, $user, $pass, $name, $port);
        }
        elseif (!$this->discover_svc()) {
//            $this->error = "Cannot discover database servers";
            dol_syslog(get_class($this) . "::DoliDBMysqliC Connect error: Cannot discover database servers", LOG_ERR);
            if(class_exists('BimpCore'))
                BimpCore::addlog('consul ne repond pas utilisation de mysqli', Bimp_Log::BIMP_LOG_ERREUR);
            return parent::__construct('mysqli', $host, $user, $pass, $name, $port);
        }
        else{
            $this->useMysqlic = true;
        }
    }
    /*
     * Set database character set and collation
     */

    function set_charset_and_collation()
    {
        global $conf;

        // If client is old latin, we force utf8
        $clientmustbe = empty($conf->db->dolibarr_main_db_character_set) ? 'utf8' : $conf->db->dolibarr_main_db_character_set;
        if (preg_match('/latin1/', $clientmustbe))
            $clientmustbe = 'utf8';

        if ($this->db->character_set_name() != $clientmustbe) {
            $this->db->set_charset($clientmustbe); // This set utf8_general_ci

            $collation = empty($conf->db->dolibarr_main_db_collation) ? 'utf8_unicode_ci' : $conf->db->dolibarr_main_db_collation;
            if (preg_match('/latin1/', $collation))
                $collation = 'utf8_unicode_ci';
            if (!preg_match('/general/', $collation)) {
                $query = "SET collation_connection = " . $collation;
                try {
                    $sql = $this->db->query($query);
                } catch (Exception $e) {
                    $this->catch($query, $sql, $e);
                    return 0;
                }
                if (!$sql)
                    $this->catch($query, $sql);
            }
        }
    }

    /**
     * Discover SQL servers to use and put them into _svc_write and _svc_read arrays
     */
    function discover_svc($force = FALSE)
    {
        $req_filter = "(not (Checks.Status==critical) and (Checks.CheckID!=serfHealth))";
        $id_separator = "_";
        $index = 0;
        $ind_sep = 0;
        $ind_max_pri = 0;
        $ind_svc_all = array();
        $svc_all = array();
        $this->_svc_read = array(); // Clean arrays
        $this->_svc_write = array();

        if (!$force && (time() - $this->_last_discover_time) < ($this->CONSUL_REDIS_CACHE_TTL)) {
            if ($this->read_svc_from_redis())
                return TRUE;
        }

        $this->_svc_read = array(); // Clean arrays 
        $this->_svc_write = array();

        foreach ($this->CONSUL_SERVERS as $consul_server) {
            $full_url = $consul_server . self::CONSUL_PATH . $this->CONSUL_SERVICE_DATABASE . "?filter=" . urlencode($req_filter);
            $json_string = file_get_contents($full_url);
            if ($json_string === FALSE)
                continue;
            $json_obj = json_decode($json_string);
            if ($json_obj === NULL)
                continue;
            foreach ($json_obj as $service) {
                $ind_sep = strrpos($service->Service->ID, $id_separator) + 1;
                if (strlen($service->Service->ID) > $ind_sep) {
                    $index = intval(substr($service->Service->ID, $ind_sep)); // Service ID should be something like "bderpdev_2" so 2 will be the $index
                    $svc_all[$index] = $service->Service->Address . ":" . $service->Service->Port;
                    $ind_svc_all[] = $index;
                } else
                    continue;
            }
            break;
        }
        $num_svc_all = count($svc_all);
        if ($num_svc_all === 0)
            return FALSE;

        //  define('CONSUL_SERVICES_PRIORITY_WRITE', serialize (array(2,1,3)));
        if (!defined('CONSUL_SERVICES_PRIORITY_WRITE')) {
            dol_syslog("Constante CONSUL_SERVICES_PRIORITY_WRITE non definie", LOG_WARNING);
            // Default to the original index
            $ind_svc_all_bkp = $ind_svc_all;
            for ($i = 0; $i < $this->CONSUL_SERVICES_USE_FOR_WRITE; $i++) {
                $min_ind = min($ind_svc_all);
                foreach ($svc_all as $id => $address) {
                    if ($id === $min_ind) {
                        $this->_svc_write[] = $address;
                        break;
                    }
                }
                if (($id = array_search($min_ind, $ind_svc_all)) !== false)
                    array_splice($ind_svc_all, $id, 1);
//                    unset($ind_svc_all[$id]);   // TODO: replace with another function to remove completely from array
                else
                    break;  // If we cannot remove the value already used - the same server will be choosen during the next loop iteration
            }
            $ind_svc_all = $ind_svc_all_bkp;
        } else {
            $CONSUL_SERVERS = unserialize(CONSUL_SERVICES_PRIORITY_WRITE);
            $ind_max_pri = count($CONSUL_SERVERS);
            for ($i = 0; $i < $ind_max_pri; $i++) {
                foreach ($svc_all as $id => $address) {
                    if ($id === $CONSUL_SERVERS[$i]) {
                        $this->_svc_write[] = $address;
                        break;
                    }
                }
                if (count($this->_svc_write) >= $this->CONSUL_SERVICES_USE_FOR_WRITE)
                    break;
            }
        }

        //  define('CONSUL_SERVICES_PRIORITY_READ', serialize (array(1,3,2)));
        if (!defined('CONSUL_SERVICES_PRIORITY_READ')) {
            dol_syslog("Constante CONSUL_SERVICES_PRIORITY_READ non definie", LOG_WARNING);
            // Default to the original index
            $ind_svc_all_bkp = $ind_svc_all;
            for ($i = 0; $i < $this->CONSUL_SERVICES_USE_FOR_READ; $i++) {
                $min_ind = min($ind_svc_all);
                foreach ($svc_all as $id => $address) {
                    if ($id === $min_ind) {
                        $this->_svc_read[] = $address;
                        break;
                    }
                }
                if (($id = array_search($min_ind, $ind_svc_all)) !== false)
                    array_splice($ind_svc_all, $id, 1);
//                    unset($ind_svc_all[$id]);
                else
                    break;  // If we cannot remove the value already used - the same server will be choosen during the next loop iteration
            }
            $ind_svc_all = $ind_svc_all_bkp;
        } else {
            $CONSUL_SERVERS = unserialize(CONSUL_SERVICES_PRIORITY_READ);
            $ind_max_pri = count($CONSUL_SERVERS);
            for ($i = 0; $i < $ind_max_pri; $i++) {
                foreach ($svc_all as $id => $address) {
                    if ($id === $CONSUL_SERVERS[$i]) {
                        $this->_svc_read[] = $address;
                        break;
                    }
                }
                if (count($this->_svc_read) >= $this->CONSUL_SERVICES_USE_FOR_READ)
                    break;
            }
        }

        $this->write_svc_to_redis();
        $this->_last_discover_time = time();

        return TRUE;
    }
    /*
     * Write discovered services to Redis database
     */

    function write_svc_to_redis()
    {

        if (!$this->CONSUL_USE_REDIS_CACHE)
            return FALSE;

        $key_write = $this->CONSUL_SERVICE_DATABASE . "_write";
        $key_read = $this->CONSUL_SERVICE_DATABASE . "_read";
        $hash_write = $key_write . "_hash";
        $hash_read = $key_read . "_hash";

        if (!$this->REDIS_USE_LOCALHOST) {
            dol_syslog("Serveurs distants REDIS ne sont pas (encore) supportés", LOG_ERR);
            return FALSE;
        }

        $size_write = count($this->_svc_write);
        $size_read = count($this->_svc_read);

        try {
            $redisClient = new Redis();
            $redisClient->connect($this->REDIS_LOCALHOST_SOCKET);
            $redisClient->setex($key_write, $this->CONSUL_REDIS_CACHE_TTL, $size_write);
            $redisClient->setex($key_read, $this->CONSUL_REDIS_CACHE_TTL, $size_read);
            $redisClient->del($hash_write);
            $redisClient->del($hash_read);
            for ($i = 0; $i < $size_write; $i++) {
                $redisClient->hSet($hash_write, $i, $this->_svc_write[$i]);
            }
            for ($i = 0; $i < $size_read; $i++) {
                $redisClient->hSet($hash_read, $i, $this->_svc_read[$i]);
            }
            $redisClient->close();
            return TRUE;
        } catch (Exception $e) {
            dol_syslog("Redis operation error: " . $e->getMessage(), LOG_ERR);
            return FALSE;
        }
    }
    /*
     * Read discovered services from Redis database
     */

    function read_svc_from_redis()
    {

        if (!$this->CONSUL_USE_REDIS_CACHE)
            return FALSE;

        $key_write = $this->CONSUL_SERVICE_DATABASE . "_write";
        $key_read = $this->CONSUL_SERVICE_DATABASE . "_read";
        $hash_write = $key_write . "_hash";
        $hash_read = $key_read . "_hash";

        if (!$this->REDIS_USE_LOCALHOST) {
            dol_syslog("Serveurs distants REDIS ne sont pas (encore) supportés", LOG_ERR);
            return FALSE;
        }

        try {
            $redisClient = new Redis();
            $redisClient->connect($this->REDIS_LOCALHOST_SOCKET);
            $size_write = $redisClient->get($key_write);
            if ($size_write === FALSE or $size_write === "")
                return FALSE;
            $size_read = $redisClient->get($key_read);
            if ($size_read === FALSE or $size_read === "")
                return FALSE;
            $this->_svc_write = $redisClient->hGetAll($hash_write);
            if (count($this->_svc_write) < 1)
                return FALSE;
            if (empty($this->_svc_write[0]))
                return FALSE;
            $this->_svc_read = $redisClient->hGetAll($hash_read);
            if (count($this->_svc_read) < 1)
                return FALSE;
            if (empty($this->_svc_read[0]))
                return FALSE;
            return TRUE;
        } catch (Exception $e) {
            dol_syslog("Redis operation error: " . $e->getMessage(), LOG_ERR);
            return FALSE;
        }
    }

    /**
     * Connect to server - SHOULD NOT BE USED
     *
     * @param   string $host database server host
     * @param   string $login login
     * @param   string $passwd password
     * @param   string $name name of database (not used for mysql, used for pgsql)
     * @param   integer $port Port of database server
     * @return  mysqli  Database access object
     * @see close
     */
    function connect($host, $login, $passwd, $name, $port = 0)
    {
        dol_syslog(get_class($this) . "::connect host=$host, port=$port, login=$login, passwd=--hidden--, name=$name", LOG_DEBUG);

        // Can also be
        // mysqli::init(); mysql::options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 0'); mysqli::options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        // return mysqli::real_connect($host, $user, $pass, $db, $port);
        return new mysqli($host, $login, $passwd, $name, $port);
    }

    /**
     * 	Return version of database server
     *
     * 	@return	        string      Version string
     */
    function getVersion()
    {
        if (!$this->connected) {
            dol_syslog("Call to getVersion when server is disconnected", LOG_ERR);
            return "";
        }
        return $this->db->server_info;
    }

    /**
     *  Close database connexion
     *
     *  @return     bool     True if disconnect successfull, false otherwise
     *  @see        connect
     */
    function close()
    {
        if ($this->db && $this->connected) {
            if ($this->transaction_opened > 0)
                dol_syslog(get_class($this) . "::close Closing a connection with an opened transaction depth=" . $this->transaction_opened, LOG_ERR);
            if ($this->transaction_opened > 0 && class_exists('BimpCore'))
                BimpCore::addlog(get_class($this) . "::close Closing a connection with an opened transaction depth=" . $this->transaction_opened, Bimp_Log::BIMP_LOG_ERREUR);
            $this->connected = false;
            $this->database_host = "";
            $this->database_port = 0;

            return $this->db->close();
        }
        return false;
    }
    /*
     * Returns actually valid server of cluster
     * using local array, redis or consul
     * 
     * @param   int     $query_type   SQL query type: 0 - unknown, 1 - read, 2 - write
     * @return  bool    FALSE if no servers available, TRUE if a server is connected
     * 
     * If success - IP address and port of the currently connected server will be set in $this->database_host and $this->database_port
     */

    function connect_server($query_type = 0, $tentative = 0)
    {
        $timestamp_debut = 0.0;
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        if (!$this->CONSUL_USE_REDIS_CACHE) {
            // TODO: work without Redis server
            // Try to get server and last write timestamp from session
            // If the server is not valid anymore - clear session vars
            dol_syslog("get_server: work without Redis server is not (still) supported", LOG_ERR);
            return FALSE;
        }

        if ($query_type === 0)
            $query_type = 2;  // Par safety we consider unknown query as 'write'

        $count_read = count($this->_svc_read);
        $count_write = count($this->_svc_write);
        $rnd_count = 0;
        $rnd_index = 0;

        switch ($query_type) {
            case 1: // read
                if ($count_read < 1) {
                    dol_syslog("get_server: no servers available for read query", LOG_ERR);
                    return FALSE;
                }
                break;
            case 2: // write
                if ($count_write < 1) {
                    dol_syslog("get_server: no servers available for write query", LOG_ERR);
                    return FALSE;
                }
                break;
            default: {
                    dol_syslog("get_server: Unknown query type: " . $query_type, LOG_ERR);
                    return FALSE;
                }
        }

        $sessid = "";
        $login = "";
        if (isset($_SESSION["dol_login"]))
            $login = $_SESSION["dol_login"];
        if (session_id() != "")
            $sessid = session_id();

        if (!$this->REDIS_USE_LOCALHOST) {
            dol_syslog("Serveurs distants REDIS ne sont pas (encore) supportés", LOG_ERR);
            return FALSE;
        }

        $server = FALSE;
        $key = "";

        // Use the last server used for write, cached in Redis
        if ($this->CONSUL_READ_FROM_WRITE_DB_HOST && ( ($login != "") || ($sessid != "") )) {
            try {
                $redisClient = new Redis();
                $redisClient->connect($this->REDIS_LOCALHOST_SOCKET);

                if ($login != "") {  // Search for previously used server, saved for login (normal case)
                    $server = $redisClient->get($login . "_server");
                    if (!($server === FALSE) && !($server === ""))
                        $key = $login . "_server";
                }

                if (($key === "") && ($sessid != "")) {  // Server not found - search it saved for session (case of 'fresh' login)
                    $server = $redisClient->get($sessid . "_server");
                    if (!($server === FALSE) && !($server === ""))
                        $key = $sessid . "_server";
                }

                $redisClient->close();
            } catch (Exception $e) {
                dol_syslog("Redis operation error: " . $e->getMessage(), LOG_ERR);
                dol_syslog("Redis server cannot be used, falling back to Consul only mode - CONSUL_READ_FROM_WRITE_DB_HOST mode cannot be used", LOG_ERR);
//                return FALSE;
            }

            if (!($server === FALSE) && !($server === "")) {  // Server, previously used for write, is found - it will be used if still available
                $arr_server = explode(":", $server);
                if (count($arr_server) > 1) {
                    $port = intval($arr_server[1]);
                    if ($port == 0)
                        $port = 3306;    // Should never happens
                    if ($this->connected) {
                        if (($this->database_host === $arr_server[0]) && ($this->database_port === $port) && $this->db->ping())
                            return TRUE;   // Already connected to this server, nothing to do
                        if ($this->transaction_opened && $this->db->ping())
                            return TRUE;   // No reconnect inside a transaction
                        else {
                            $timestamp_debut = microtime(true);
                            $this->close();
                            unset($this->db);
                            $this->countReq2++;
                        }
                    }

                    $this->db = mysqli_init();
//                    $this->db->options(MYSQL_OPT_RECONNECT,false);

                    $this->db->real_connect($arr_server[0], $this->database_user, $this->database_pass, $this->database_name, $port);
                    if (($this->db != FALSE) && (!$this->db->connect_error)) {
                        $this->database_host = $arr_server[0];
                        $this->database_port = $port;
                        $this->connected = TRUE;
                        $this->set_charset_and_collation();
                        if ($timestamp_debut > 0.0)
                            $this->timeReconnect += (microtime(true) - $timestamp_debut);
                        return TRUE;
                    }
                    // The last used server is not available. We need to clean his address in Redis and retry the search.
                    try {
                        $redisClient = new Redis();
                        $redisClient->connect($this->REDIS_LOCALHOST_SOCKET);
                        $redisClient->del($key);
                        $redisClient->close();
                        return $this->connect_server($query_type);
                    } catch (Exception $e) {
                        dol_syslog("Redis operation error: " . $e->getMessage(), LOG_ERR);
                    }
                }
            }
        }

        $cur_timestamp = time();
        if (($cur_timestamp - $this->_last_discover_time) > ($this->CONSUL_REDIS_CACHE_TTL / 2)) {
            $this->discover_svc();   // On TTL/2 we rediscover services from Consul (or read cached values from Redis)
            $count_read = count($this->_svc_read);
            $count_write = count($this->_svc_write);
        }
        // Search for a server in array
        switch ($query_type) {
            case 1: // read
                if (($this->CONSUL_SERVICES_USE_FOR_READ === 1) || ($count_read === 1)) {
                    $server = $this->_svc_read[0];
                } else {    // random server
                    $rnd_count = $count_read * 10 - 1;
                    $rnd_index = intdiv(rand(0, $rnd_count), 10);
                    $server = $this->_svc_read[$rnd_index];
                }
                break;
            case 2: // write
                if (($this->CONSUL_SERVICES_USE_FOR_WRITE === 1) || ($count_write === 1)) {
                    $server = $this->_svc_write[0];
                } else {    // random server
                    $rnd_count = $count_write * 10 - 1;
                    $rnd_index = intdiv(rand(0, $rnd_count), 10);
                    $server = $this->_svc_write[$rnd_index];
                }
                break;
        }

        if (!empty($server)) {
            // Try to connect to the server
            $arr_server = explode(":", $server);
            $port = intval($arr_server[1]);
            if ($this->connected) {
                if (($this->database_host === $arr_server[0]) && ($this->database_port === $port) && $this->db->ping())
                    return TRUE;
                else {
                    $timestamp_debut = microtime(true);
                    $this->close();
                    unset($this->db);
                    $this->countReq2++;
                }
            }
            $this->db = mysqli_init();
//            $this->db->options(MYSQL_OPT_RECONNECT,false);

            $this->db->real_connect($arr_server[0], $this->database_user, $this->database_pass, $this->database_name, $port);
//            $this->db = new mysqli($arr_server[0], $this->database_user, $this->database_pass, $this->database_name, $port);
            if (($this->db != FALSE) && (!$this->db->connect_error)) {
                $this->database_host = $arr_server[0];
                $this->database_port = $port;
                $this->connected = TRUE;
                $this->set_charset_and_collation();
                if ($timestamp_debut > 0.0)
                    $this->timeReconnect += (microtime(true) - $timestamp_debut);

                // Write the server used for write to Redis if needed
                if ($query_type == 2 && $this->CONSUL_READ_FROM_WRITE_DB_HOST && ( ($login != "") || ($sessid != "") )) {
                    if ($login != "")
                        $key = $login . "_server";
                    else
                        $key = $sessid . "_server";
                    try {
                        $redisClient = new Redis();
                        $redisClient->connect($this->REDIS_LOCALHOST_SOCKET);
                        $redisClient->setex($key, $this->CONSUL_READ_FROM_WRITE_DB_HOST_TIME, $server);
                        $redisClient->close();
                    } catch (Exception $e) {
                        dol_syslog("Redis operation error: " . $e->getMessage(), LOG_ERR);
                        dol_syslog("Cannot write the address of last used for write server", LOG_ERR);
                    }
                }
                return TRUE;
            }
            // If we cannot connect to the server - we need to remove it from the array and retry the search
            if ($query_type == 2) {
                if (($ind_srv = array_search($server, $this->_svc_write)) !== false)
                    array_splice($this->_svc_write, $ind_srv, 1);
                //                unset($this->_svc_write[$ind_srv]);     // Should always be true            
            } else {
                if (($ind_srv = array_search($server, $this->_svc_read)) !== false)
                    array_splice($this->_svc_read, $ind_srv, 1);
                //                unset($this->_svc_read[$ind_srv]);     // Should always be true                        
            }

            dol_syslog('Impossible de se connectée a ' . $server, 3);
            if ($tentative < 20)
                return $this->connect_server($query_type, $tentative + 1);
            else
                die('impossible de se connecté au serveur');
        }

        dol_syslog('Aucun serveur pour connexion BDD ' . $count_read . '/' . count($this->_svc_read) . ' ' . $count_write . '/' . count($this->_svc_write) . ' ' . $rnd_index, 3);
        return FALSE;
    }

    /**
     * 	Execute a SQL request and return the resultset
     *  SELECT, SHOW and DESC queries are considered "read", all others - "write"
     *  Server to use for the query will be taken from arrays or newly discovered  
     *
     * 	@param	string	$query			SQL query string
     * 	@param	int		$usesavepoint	0=Default mode, 1=Run a savepoint before and a rollbock to savepoint if error (this allow to have some request with errors inside global transactions).
     * 									Note that with Mysql, this parameter is not used as Myssql can already commit a transaction even if one request is in error, without using savepoints.
     *  @param  string	$type           Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
     * 	@return	bool|mysqli_result		Resultset of answer
     */
    function query($query, $usesavepoint = 0, $type = 'auto', $result_mode = 0)
    {
        if(!$this->useMysqlic)
            return parent::query($query, $usesavepoint, $type, $result_mode);
        $this->timeDebReq = microtime(true);
//    	global $conf, $user;
        global $user;

        $qtype = 2; // 0 - unknown, 1 - read, 2 - write

        if (stripos($type, "dml") == 0) {
            $trim_query = trim($query);
            if (stripos($trim_query, "SELECT") === 0)
                $qtype = 1;
            if (stripos($trim_query, "SHOW") === 0)
                $qtype = 1;
            if (stripos($trim_query, "SET") === 0)
                $qtype = 1;
        }
        dol_syslog('Query: ' . $query, LOG_DEBUG);
        dol_syslog('Query type: ' . $qtype, LOG_DEBUG);

        if (is_object($user) && $user->admin && defined('BIMP_PRINT_ADMIN_SQL')) {
            $debugTime = true;
        } else
            $debugTime = false;
        /*
          $debugTime = false;
          if (class_exists("BimpDebug") && BimpDebug::isActive('bimpcore/objects/print_admin_sql')) {
          global $user;
          if ($user->admin)
          $debugTime = true;
          }
         */
        /* moddrsi (20.2) */
        $this->countReq++;
        $timestamp_debut = microtime(true);
        if ($debugTime) {
            if (!isset($this->timestamp_debut)) {
                $this->timestamp_debut = $timestamp_debut;
                $this->timestamp_derfin = $timestamp_debut;
            }
        }
        /* fmoddrsi */

        if ($this->transaction_opened == 0) {//On est pas dans une transaction.
            if (!$this->connect_server($qtype)) {
                $extra = array('svc_read' => $this->_svc_read, 'svc_write' => $this->_svc_write);
                $this->discover_svc();
                $extra['new_svc_read'] = $this->_svc_read;
                $extra['new_svc_write'] = $this->_svc_write;
                dol_syslog(get_class($this) . "::query: Fatal error - cannot connect to database server for request type: " . $qtype . ' ' . print_r($extra, 1) . ' ' . $this->database_user . ' ' . $this->database_pass . ' ' . $this->database_name, LOG_ERR);

                if (class_exists('BimpCore')) {
                    $this->discover_svc();
                    BimpCore::addlog(get_class($this) . "::query: Fatal error - cannot connect to database server for request type: " . $qtype, 4, 'sql', null, $extra);
                }
                return FALSE;
            }
        } else {
            if (stripos($query, 'SELECT') !== 0) {
                $thread_id = $this->getThreadId();
                if ($thread_id != $this->thread_id) {//gros probléme id transaction changée
                    if (class_exists('BimpCore')) {
                        BimpCore::addlog('Gros probléme changement de thread Id', 3, 'sql', null, array('query' => $query, 'oldId' => $this->thread_id, 'newId' => $thread_id));
                    } else {
                        die('ThreadId probléme, est pas de BimpCore');
                    }
                    $this->transaction_opened = 0;
                    static::stopAll('ThreadId');
                }
            }
        }

        /* fmoddrsi */


        // Starting from this point we consider that the database is connected
        $query = trim($query);
//        $this->set_charset_and_collation();   Moved to connect_server
//	    if (! in_array($query,array('BEGIN','COMMIT','ROLLBACK'))) dol_syslog('sql='.$query, LOG_DEBUG);
        /*
          if (! $this->database_name)
          {
          // Ordre SQL ne necessitant pas de connexion a une base (exemple: CREATE DATABASE)
          $ret = $this->db->query($query);
          }
          else
          {
          $ret = $this->db->query($query);
          }
         */
        $this->timeDebReq = microtime(true);
        try {
            $ret = $this->db->query($query);
        } catch (Exception $e) {
            $this->catch($query, $ret, $e);
            $this->timeDebReq = 0;
            return 0;
        }
        if (!$ret)
            $this->catch($query, $ret);
        $this->timeDebReq = 0;

        if (!preg_match("/^COMMIT/i", $query) && !preg_match("/^ROLLBACK/i", $query)) {
            // Si requete utilisateur, on la sauvegarde ainsi que son resultset
            $this->lastquery = $query;
            $this->_results = $ret;
        }
        /*
          if (! preg_match("/^BEGIN/i",$query) && ($this->transaction_opened==0) )
          {
          $this->db->close();
          $this->database_host = "";
          $this->database_port = 0;
          $this->connected = FALSE;
          }
         */
        /* moddrsi (20.2) */
        $timestamp_fin = microtime(true);
        $difference_ms = $timestamp_fin - $timestamp_debut;
        if ($debugTime) {
            global $tabReq;

            if (!isset($tabReq[$query]))
                $tabReq[$query] = 0;
            $tabReq[$query]++;

            $difference_ms2 = $timestamp_fin - $this->timestamp_debut;
            $difference_ms3 = $timestamp_debut - $this->timestamp_derfin;

            if ($tabReq[$query] > 2)
                echo 'attention req identique ' . $tabReq[$query] . " fois.";

            if ($difference_ms > 0.00 || $difference_ms3 > 0.1) {
                echo $this->countReq . " ";
                echo $query . " <br/>";
                echo "||" . $this->num_rows($ret) . " en " . $difference_ms . "s depuis deb " . $difference_ms2 . " <br/><br/>";
            }

            $this->timestamp_derfin = $timestamp_fin;
        }

        if (defined('BIMP_LIB') && BimpDebug::isActive() && !in_array($query, array('BEGIN', 'COMMIT', 'ROLLBACK'))) {
            BimpDebug::addSqlDebug($query, $this->countReq, ($this->noTransaction ? -1 : $this->transaction_opened), $difference_ms);

            if (!$ret) {
                $content = BimpRender::renderAlerts('Erreur SQL - ' . $this->lasterror());
                BimpDebug::addDebug('sql', '', $content, array(
                    'foldable' => false
                ));
            }
        }
        /* fmoddrsi */

        return $ret;
    }

    /**
     * 	Escape a string to insert data
     *
     * 	@param	string	$stringtoencode		String to escape
     * 	@return	string						String escaped
     *  @deprecated
     */
    public function escapeunderscore($stringtoencode)
    {
        return str_replace('_', '\_', (string) $stringtoencode);
    }

    function catch($query, $ret, $e = null)
    {
        $deadLock = false;
        $classLog = 'sql';
        $this->lastqueryerror = $query;
        $this->lasterror = $this->error();
        $this->lasterrno = $this->errno();

        if (stripos($this->lasterror, 'Deadlock') !== false || stripos($this->lasterrno, '1213') !== false) {
            $deadLock = true;
            $classLog = 'deadLock';
        } elseif ($e && (stripos($e->getMessage(), 'Deadlock') !== false || stripos($e->getMessage(), '1213') !== false)) {
            $deadLock = true;
            $classLog = 'deadLock';
        } elseif (stripos($this->lasterrno, 'DB_ERROR_RECORD_ALREADY_EXISTS') !== false) {
            $classLog = 'sql_duplicate';
        }

        if (class_exists('synopsisHook'))
            $timer = synopsisHook::getTime();

        $msg = get_class($this) . "::query SQL Error message: ";
        $msg .= '<br/>Lasterrno : ' . $this->lasterrno;
        $msg .= '<br/>Lasterror : ' . $this->lasterror;
        if ($e)
            $msg .= '<br/>Exception msg : ' . $e->getMessage();
        $msg .= '<br/>Serveur : ' . $this->database_host;
        $msg .= '<br/>Query : ' . $query;
        if ($this->timeDebReq > 0)
            $msg .= '<br/>Time Req : ' . (microtime(true) - $this->timeDebReq);
        if ($this->timeDebReq2 > 0)
            $msg .= '<br/>Time Req2 : ' . (microtime(true) - $this->timeDebReq2);
        if (class_exists('synopsisHook'))
            $msg .= '<br/>Time Depuis déb : ' . $timer;

        dol_syslog($msg, LOG_ERR);

        if (class_exists('BimpCore')) {
            $log = true;
            if (in_array($this->lasterrno, array('DB_ERROR_1205'))) {
                $log = false;
            } elseif ($classLog == 'deadLock' && !(int) BimpCore::getConf('log_sql_dealocks')) {
                $log = false;
            } /* elseif ($classLog == 'sql_duplicate' && !(int) BimpCore::getConf('log_sql_duplicate')) {
              $log = false;
              } */
            if ($log) {
                $extra_data = array(
                    'Code erreur' => $this->lasterrno,
                    'Erreur SQL'  => $this->lasterror,
                    'Serveur'     => $this->database_host,
                    'Timer'       => $timer
                );

                if ($this->timeDebReq > 0) {
                    $extra_data['Durée req 1'] = (microtime(true) - $this->timeDebReq);
                }

                if ($this->timeDebReq2 > 0) {
                    $extra_data['Durée req 2'] = (microtime(true) - $this->timeDebReq2);
                }

                $extra_data['Requête'] = '<br/><br/>' . BimpRender::renderSql($query) . '<br/><br/>';

                BimpCore::addlog('ERREUR SQL - ' . $this->lasterror, Bimp_Log::BIMP_LOG_ERREUR, $classLog, null, $extra_data);
            }
        } else {
            dol_syslog('Erreur sql BimpCore non loadé', LOG_ERR);
        }
        if ($deadLock) {
            $this->transaction_opened = 0;
            static::stopAll('deadLock');
        }
    }

    function getThreadId()
    {
        $query = 'SELECT CONNECTION_ID() as id;';
        try {
            $sql = $this->db->query($query);
        } catch (Exception $e) {
            $this->catch($query, $sql, $e);
            return 0;
        }
        if (!$sql)
            $this->catch($query, $sql);
        if ($sql) {
            $res = $this->fetch_object($sql);
            return $res->id;
        }
        return 0;
    }

    /**
     * 	Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
     *
     * 	@param	mysqli_result	$resultset	Curseur de la requete voulue
     * 	@return int							Nombre de lignes
     * 	@see    num_rows
     */
    function affected_rows($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (!is_object($resultset)) {
            $resultset = $this->_results;
        }
        // mysql necessite un link de base pour cette fonction contrairement
        // a pqsql qui prend un resultset
        if (!$this->connected) {
            dol_syslog("Call to affected_rows when server is disconnected", LOG_ERR);
            return 0;
        }
        return $this->db->affected_rows;
    }

    /**
     * 	Libere le dernier resultset utilise sur cette connexion
     *
     * 	@param  mysqli_result	$resultset	Curseur de la requete voulue
     * 	@return	void
     */
    function free($resultset = null)
    {
        // If resultset not provided, we take the last used by connexion
        if (!is_object($resultset)) {
            $resultset = $this->_results;
        }
        // Si resultset en est un, on libere la memoire
        if (is_object($resultset))
            $resultset->free_result();
    }

    /**
     * 	Escape a string to insert data
     *
     * 	@param	string	$stringtoencode		String to escape
     * 	@return	string						String escaped
     */
    function escape($stringtoencode)
    {
        if (!$this->connected && !$this->transaction_opened)
            $this->connect_server(0);


        if (!$this->connected) {
            dol_syslog("Call to escape when server is disconnected", LOG_WARNING);
            dol_syslog("Using replacement function - valid for utf8 only!!", LOG_WARNING);
            return $this->real_escape($stringtoencode);
        }
        return $this->db->real_escape_string($stringtoencode);
    }

    /**
     * * Returns a string with backslashes before characters that need to be escaped.
     * * As required by MySQL and suitable for multi-byte character sets
     * * Characters encoded are NUL (ASCII 0), \n, \r, \, ', ", and ctrl-Z.
     * * In addition, the special control characters % and _ are also escaped,
     * * suitable for all statements, but especially suitable for `LIKE`.
     * * @param string $stringtoencode String to add slashes to
     * * @return $string with `\` prepended to reserved characters
     * *
     * * @author Trevor Herselman
     * */
    function real_escape($stringtoencode)
    {
        if (function_exists('mb_ereg_replace'))
            return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C]', '\\\0', $stringtoencode);
        else
            return preg_replace('~[\x00\x0A\x0D\x1A\x22\x25\x27\x5C]~u', '\\\$0', $stringtoencode);
    }

    /**
     * Get last ID after an insert INSERT
     *
     * @param   string	$tab    	Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
     * @param	string	$fieldid	Field name
     * @return  int|string			Id of row
     */
    function last_insert_id($tab, $fieldid = 'rowid')
    {
        if (!$this->connected) {
            dol_syslog("Call to last_insert_id when server is disconnected", LOG_ERR);
            return 0;
        }
        return $this->db->insert_id;
    }

    public function begin($textinlog = '')
    {
        if (!$this->connected && !$this->transaction_opened)
            $this->connect_server(2);

        if (!$this->transaction_opened)
            $firstBegin = true;
        else
            $firstBegin = false;
        $res = parent::begin();

        if (defined('BIMP_LIB') && BimpDebug::isActive()) {
            if ($res <= 0) {
                $content = BimpRender::renderAlerts('Echec BEGIN - ' . $this->lasterror());
                BimpDebug::addDebug('sql', '', $content, array(
                    'foldable' => false
                ));
            } else {
                $content = '<span class="info">BEGIN #' . $this->transaction_opened . '</span><br/><br/>';
                BimpDebug::addDebug('sql', '', $content, array(
                    'foldable' => false
                ));
            }
        }

        if ($firstBegin)
            $this->thread_id = $this->getThreadId();
    }
}

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
 *	\file       htdocs/core/db/mysqlic.class.php
 *	\brief      Class file to manage Dolibarr database access for a dynamically discovered MySQL database
 */

require_once DOL_DOCUMENT_ROOT .'/core/db/DoliDB.class.php';

/**
 *	Class to manage Dolibarr database access for a MySQL database using the MySQLi extension
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
class DoliDBMysqliC extends DoliDB
{
    /** @var mysqli Database object */
    public $db;
    //! Database type
    public $type='mysqli';
    //! Database label
    const LABEL='MySQL or MariaDB';
    //! Version min database
    const VERSIONMIN='5.0.3';
    //! Path to Consul endpoint
    const CONSUL_PATH='/v1/health/service/';
    /** @var mysqli_result Resultset of last query */
    private $_results;
    
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

    public $database_user;
    public $database_host;
    public $database_port;
    public $database_pass;
    public $database_name;

    public $transaction_opened;

    /* moddrsi */
    public $countReq = 0;
    public $countReq2 = 0;
    public $timeReconnect = 0;

    /* fmoddrsi */

    /**
	 *	Constructor.
	 *	This create an opened connexion to a database server and eventually to a database
	 *
	 *	@param      string	$type		Type of database (mysql, pgsql...)
	 *	@param	    string	$host		Address of database server
	 *	@param	    string	$user		Nom de l'utilisateur autorise
	 *	@param	    string	$pass		Mot de passe
	 *	@param	    string	$name		Nom de la database
	 *	@param	    int		$port		Port of database server
     */
    function __construct($type, $host, $user, $pass, $name='', $port=0)
    {
        global $conf,$langs;

        // Note that having "static" property for "$forcecharset" and "$forcecollate" will make error here in strict mode, so they are not static
        if (! empty($conf->db->character_set)) $this->forcecharset=$conf->db->character_set;
        if (! empty($conf->db->dolibarr_main_db_collation)) $this->forcecollate=$conf->db->dolibarr_main_db_collation;

        $this->database_user = $user;
        $this->database_host="";
        $this->database_port=0;
        $this->database_pass = $pass;
        $this->database_name = $name;

        $this->transaction_opened=0;

        //print "Name DB: $host,$user,$pass,$name<br>";

        if (! class_exists('mysqli'))
        {
            $this->connected = false;
            $this->ok = false;
            $this->error="Mysqli PHP functions for using Mysqli driver are not available in this version of PHP. Try to use another driver.";
            dol_syslog(get_class($this)."::DoliDBMysqliC : Mysqli PHP functions for using Mysqli driver are not available in this version of PHP. Try to use another driver.",LOG_ERR);
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
        if(!defined('CONSUL_SERVERS'))
        {
            dol_syslog("Constante CONSUL_SERVERS non definie", LOG_ERR);
            return FALSE;
        }
        else        
            $this->CONSUL_SERVERS = unserialize(CONSUL_SERVERS);
        
//      define('CONSUL_SERVICE_DATABASE', "bderpdev");
        if(!defined('CONSUL_SERVICE_DATABASE'))
        {
            dol_syslog("Constante CONSUL_SERVICE_DATABASE non definie", LOG_ERR);
            return FALSE;
        }
        else        
            $this->CONSUL_SERVICE_DATABASE = CONSUL_SERVICE_DATABASE;

//  define('CONSUL_SERVICES_USE_FOR_WRITE', 1);
        if(!defined('CONSUL_SERVICES_USE_FOR_WRITE'))
        {
            dol_syslog("Constante CONSUL_SERVICES_USE_FOR_WRITE non definie", LOG_WARNING);
            $this->CONSUL_SERVICES_USE_FOR_WRITE = 1; // Default to 1
        }
        else        
            $this->CONSUL_SERVICES_USE_FOR_WRITE = CONSUL_SERVICES_USE_FOR_WRITE;

//  define('CONSUL_SERVICES_USE_FOR_READ', 2);
        if(!defined('CONSUL_SERVICES_USE_FOR_READ'))
        {
            dol_syslog("Constante CONSUL_SERVICES_USE_FOR_READ non definie", LOG_WARNING);
            $this->CONSUL_SERVICES_USE_FOR_READ = 1; // Default to 1
        }
        else        
            $this->CONSUL_SERVICES_USE_FOR_READ = CONSUL_SERVICES_USE_FOR_READ;

//      define('CONSUL_USE_REDIS_CACHE', true);
        if(!defined('CONSUL_USE_REDIS_CACHE'))
        {
            dol_syslog("Constante CONSUL_USE_REDIS_CACHE non definie", LOG_WARNING);
            $this->CONSUL_USE_REDIS_CACHE = FALSE;
        }
        else        
            $this->CONSUL_USE_REDIS_CACHE = CONSUL_USE_REDIS_CACHE;

//      define('REDIS_USE_LOCALHOST', true);
        if(!defined('REDIS_USE_LOCALHOST'))
        {
            dol_syslog("Constante REDIS_USE_LOCALHOST non definie", LOG_WARNING);
            $this->REDIS_USE_LOCALHOST = true;
        }
        else        
            $this->REDIS_USE_LOCALHOST = REDIS_USE_LOCALHOST;

//      define('REDIS_LOCALHOST_SOCKET', "/var/run/redis/redis.sock");
        if(!defined('REDIS_LOCALHOST_SOCKET'))
        {
            dol_syslog("Constante REDIS_LOCALHOST_SOCKET non definie", LOG_WARNING);
            $this->REDIS_LOCALHOST_SOCKET = "/var/run/redis/redis.sock";
        }
        else        
            $this->REDIS_LOCALHOST_SOCKET = REDIS_LOCALHOST_SOCKET;

//      define('CONSUL_REDIS_CACHE_TTL', 120);  // Seconds
        if(!defined('CONSUL_REDIS_CACHE_TTL'))
        {
            dol_syslog("Constante CONSUL_REDIS_CACHE_TTL non definie", LOG_WARNING);
            $this->CONSUL_REDIS_CACHE_TTL = 120;
        }
        else        
            $this->CONSUL_REDIS_CACHE_TTL = CONSUL_REDIS_CACHE_TTL;
        
//      define('CONSUL_READ_FROM_WRITE_DB_HOST', true);
        if(!defined('CONSUL_READ_FROM_WRITE_DB_HOST'))
        {
            dol_syslog("Constante CONSUL_READ_FROM_WRITE_DB_HOST non definie", LOG_WARNING);
            $this->CONSUL_READ_FROM_WRITE_DB_HOST = TRUE; // Default to TRUE
        }
        else        
            $this->CONSUL_READ_FROM_WRITE_DB_HOST = CONSUL_READ_FROM_WRITE_DB_HOST;

//      define('CONSUL_READ_FROM_WRITE_DB_HOST_TIME', 120);    // Seconds
        if(!defined('CONSUL_READ_FROM_WRITE_DB_HOST_TIME'))
        {
            dol_syslog("Constante CONSUL_READ_FROM_WRITE_DB_HOST_TIME non definie", LOG_WARNING);
            $this->CONSUL_READ_FROM_WRITE_DB_HOST_TIME = 120; // Default to 120
        }
        else        
            $this->CONSUL_READ_FROM_WRITE_DB_HOST_TIME = CONSUL_READ_FROM_WRITE_DB_HOST_TIME;
        
        if(! $this->discover_svc())
        {
            $this->error = "Cannot discover database servers";
            dol_syslog(get_class($this) . "::DoliDBMysqliC Connect error: " . $this->error, LOG_ERR);
        }

                // Try server connection
		// We do not try to connect to database, only to server. Connect to database is done later in constrcutor
/*
                $this->db = $this->connect($host, $user, $pass, '', $port);

		if ($this->db->connect_errno) {
			$this->connected = false;
			$this->ok = false;
			$this->error = $this->db->connect_error;
			dol_syslog(get_class($this) . "::DoliDBMysqliC Connect error: " . $this->error, LOG_ERR);
		} else {
			$this->connected = true;
			$this->ok = true;
		}
*/
		// If server connection is ok, we try to connect to the database
/*        
        if ($this->connected && $name)
        {
            if ($this->select_db($name))
            {
                $this->database_selected = true;
                $this->database_name = $name;
                $this->ok = true;

                $this->set_charset_and_collation();
            }
            else
            {
                $this->database_selected = false;
                $this->database_name = '';
                $this->ok = false;
                $this->error=$this->error();
                dol_syslog(get_class($this)."::DoliDBMysqliC : Select_db error ".$this->error,LOG_ERR);
            }
        }
        else
        {
            // Pas de selection de base demandee, ok ou ko
            $this->database_selected = false;

            if ($this->connected) $this->set_charset_and_collation();
        }
*/        
    }

    /*
     * Set database character set and collation
     */
    function set_charset_and_collation()
    {
        global $conf;
        
        // If client is old latin, we force utf8
        $clientmustbe = empty($conf->db->dolibarr_main_db_character_set) ? 'utf8' : $conf->db->dolibarr_main_db_character_set;
        if (preg_match('/latin1/', $clientmustbe)) $clientmustbe='utf8';

	if ($this->db->character_set_name() != $clientmustbe) {
            $this->db->set_charset($clientmustbe);	// This set utf8_general_ci

            $collation = empty($conf->db->dolibarr_main_db_collation) ? 'utf8_unicode_ci' : $conf->db->dolibarr_main_db_collation;
            if (preg_match('/latin1/', $collation)) $collation='utf8_unicode_ci';
            if (! preg_match('/general/', $collation)) $this->db->query("SET collation_connection = ".$collation);
	}
    }
    
    /**
     * Discover SQL servers to use and put them into _svc_write and _svc_read arrays
     */
    function discover_svc($force=FALSE)
    {
        $req_filter = "(not (Checks.Status==critical) and (Checks.CheckID!=serfHealth))";
        $id_separator = "_";
        $index=0;
        $ind_sep=0;
        $ind_max_pri=0;
        $ind_svc_all = array();
        $svc_all = array();
        $this->_svc_read = array(); // Clean arrays
        $this->_svc_write = array();

        if(!$force)
        {
            if($this->read_svc_from_redis())
                return TRUE;
        }

        $this->_svc_read = array(); // Clean arrays 
        $this->_svc_write = array();
        
        foreach($this->CONSUL_SERVERS as $consul_server)
        {
            $full_url = $consul_server.self::CONSUL_PATH.$this->CONSUL_SERVICE_DATABASE."?filter=".urlencode($req_filter);
            $json_string = file_get_contents($full_url);
            if($json_string === FALSE) continue;
            $json_obj = json_decode($json_string);
            if($json_obj === NULL) continue;
            foreach($json_obj as $service)
            {
                $ind_sep = strrpos($service->Service->ID, $id_separator)+1;
                if(strlen($service->Service->ID)>$ind_sep)
                {
                    $index = intval(substr($service->Service->ID, $ind_sep)); // Service ID should be something like "bderpdev_2" so 2 will be the $index
                    $svc_all[$index] = $service->Service->Address.":".$service->Service->Port;
                    $ind_svc_all[] = $index;
                }
                else
                    continue;
            }
            break;
        }
        $num_svc_all = count($svc_all);
        if($num_svc_all===0) return FALSE;

//  define('CONSUL_SERVICES_PRIORITY_WRITE', serialize (array(2,1,3)));
        if(!defined('CONSUL_SERVICES_PRIORITY_WRITE'))
        {
            dol_syslog("Constante CONSUL_SERVICES_PRIORITY_WRITE non definie", LOG_WARNING);
            // Default to the original index
            $ind_svc_all_bkp = $ind_svc_all;
            for($i=0; $i<$this->CONSUL_SERVICES_USE_FOR_WRITE; $i++)
            {
                $min_ind = min($ind_svc_all);
                foreach ($svc_all as $id => $address)
                {
                    if($id === $min_ind)
                    {
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
        }
        else
        {
            $CONSUL_SERVERS = unserialize(CONSUL_SERVICES_PRIORITY_WRITE);
            $ind_max_pri = count($CONSUL_SERVERS);
            for($i=0; $i<$ind_max_pri; $i++)
            {
                foreach ($svc_all as $id => $address)
                {
                    if($id === $CONSUL_SERVERS[$i])
                    {
                        $this->_svc_write[] = $address;
                        break;
                    }
                }
                if(count($this->_svc_write)>=$this->CONSUL_SERVICES_USE_FOR_WRITE)
                    break;
            }
        }

//  define('CONSUL_SERVICES_PRIORITY_READ', serialize (array(1,3,2)));
        if(!defined('CONSUL_SERVICES_PRIORITY_READ'))
        {
            dol_syslog("Constante CONSUL_SERVICES_PRIORITY_READ non definie", LOG_WARNING);
            // Default to the original index
            $ind_svc_all_bkp = $ind_svc_all;
            for($i=0; $i<$this->CONSUL_SERVICES_USE_FOR_READ; $i++)
            {
                $min_ind = min($ind_svc_all);
                foreach ($svc_all as $id => $address)
                {
                    if($id === $min_ind)
                    {
                        $this->_svc_read[] = $address;
                        break;
                    }
                }
                if (($id = array_search($min_ind, $ind_svc_all)) !== false) 
                    unset($ind_svc_all[$id]);
                else
                    break;  // If we cannot remove the value already used - the same server will be choosen during the next loop iteration
            }
            $ind_svc_all = $ind_svc_all_bkp;
        }
        else  
        {
            $CONSUL_SERVERS = unserialize(CONSUL_SERVICES_PRIORITY_READ);
            $ind_max_pri = count($CONSUL_SERVERS);
            for($i=0; $i<$ind_max_pri; $i++)
            {
                foreach ($svc_all as $id => $address)
                {
                    if($id === $CONSUL_SERVERS[$i])
                    {
                        $this->_svc_read[] = $address;
                        break;
                    }
                }
                if(count($this->_svc_read)>=$this->CONSUL_SERVICES_USE_FOR_READ)
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
        
        if(! $this->CONSUL_USE_REDIS_CACHE)
            return FALSE;
        
        $key_write = $this->CONSUL_SERVICE_DATABASE . "_write";
        $key_read = $this->CONSUL_SERVICE_DATABASE . "_read";
        $hash_write = $key_write . "_hash";
        $hash_read = $key_read . "_hash";

        if(! $this->REDIS_USE_LOCALHOST)
        {
            dol_syslog("Serveurs distants REDIS ne sont pas (encore) supportés", LOG_ERR);
            return FALSE;            
        }
        
        $size_write = count($this->_svc_write);
        $size_read = count($this->_svc_read);

        try {
            $redisClient = new Redis();
            $redisClient -> connect($this->REDIS_LOCALHOST_SOCKET);
            $redisClient -> setex($key_write, $this->CONSUL_REDIS_CACHE_TTL, $size_write);
            $redisClient -> setex($key_read, $this->CONSUL_REDIS_CACHE_TTL, $size_read);
            $redisClient -> del($hash_write);
            $redisClient -> del($hash_read);
            for($i=0; $i<$size_write; $i++)
            {
                $redisClient->hSet($hash_write, $i, $this->_svc_write[$i]);
            }
            for($i=0; $i<$size_read; $i++)
            {
                $redisClient->hSet($hash_read, $i, $this->_svc_read[$i]);
            }
            $redisClient->close();
            return TRUE;
        }
        catch( Exception $e ) { 
            dol_syslog("Redis operation error: ".$e->getMessage(), LOG_ERR);
            return FALSE;
        }
        
    }

    /*
     * Read discovered services from Redis database
     */
    function read_svc_from_redis()
    {

        if(! $this->CONSUL_USE_REDIS_CACHE)
            return FALSE;

        $key_write = $this->CONSUL_SERVICE_DATABASE . "_write";
        $key_read = $this->CONSUL_SERVICE_DATABASE . "_read";
        $hash_write = $key_write . "_hash";
        $hash_read = $key_read . "_hash";

        if(! $this->REDIS_USE_LOCALHOST)
        {
            dol_syslog("Serveurs distants REDIS ne sont pas (encore) supportés", LOG_ERR);
            return FALSE;            
        }
        
        try {
            $redisClient = new Redis();
            $redisClient -> connect($this->REDIS_LOCALHOST_SOCKET);
            $size_write = $redisClient -> get($key_write);
            if($size_write===FALSE or $size_write==="")
                return FALSE;
            $size_read = $redisClient -> get($key_read);
            if($size_read===FALSE or $size_read==="")
                return FALSE;
            $this->_svc_write = $redisClient->hGetAll($hash_write);
            if(count($this->_svc_write) < 1)
                return FALSE;
            if(empty($this->_svc_write[0]))
                return FALSE;
            $this->_svc_read = $redisClient->hGetAll($hash_read);
            if(count($this->_svc_read) < 1)
                return FALSE;
            if(empty($this->_svc_read[0]))
                return FALSE;
            return TRUE;
        }        
        catch( Exception $e ) { 
            dol_syslog("Redis operation error: ".$e->getMessage(), LOG_ERR);
            return FALSE;
        }
        
    }

    /**
     *  Convert a SQL request in Mysql syntax to native syntax
     *
     *  @param     string	$line   SQL request line to convert
     *  @param     string	$type	Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
     *  @return    string   		SQL request line converted
     */
    static function convertSQLFromMysql($line,$type='ddl')
    {
        return $line;
    }

	/**
	 *	Select a database
	 *
	 *	@param	    string	$database	Name of database
	 *	@return	    boolean  		    true if OK, false if KO
	 */
    function select_db($database)
    {
        dol_syslog(get_class($this)."::select_db database=".$database, LOG_DEBUG);
        if(!$this->connected)
        {
            dol_syslog("Call to select_db when server is disconnected", LOG_ERR);
            return FALSE;
        }
        return $this->db->select_db($database);
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
	 *	Return version of database server
	 *
	 *	@return	        string      Version string
     */
    function getVersion()
    {
        if(!$this->connected)
        {
            dol_syslog("Call to getVersion when server is disconnected", LOG_ERR);
            return "";
        }
        return $this->db->server_info;
    }

    /**
     *	Return version of database client driver
     *
     *	@return	        string      Version string
     */
	function getDriverInfo()
	{
		return $this->db->client_info;
	}


    /**
     *  Close database connexion
     *
     *  @return     bool     True if disconnect successfull, false otherwise
     *  @see        connect
     */
    function close()
    {
        if ( $this->db && $this->connected )
        {
	    if ($this->transaction_opened > 0) 
                dol_syslog(get_class($this)."::close Closing a connection with an opened transaction depth=".$this->transaction_opened,LOG_ERR);
            $this->connected=false;
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
    function connect_server($query_type=0)
    {        
        $timestamp_debut = 0.0;
        
        if(! $this->CONSUL_USE_REDIS_CACHE)
        {
            // TODO: work without Redis server
            // Try to get server and last write timestamp from session
            // If the server is not valid anymore - clear session vars
            dol_syslog("get_server: work without Redis server is not (still) supported", LOG_ERR);
            return FALSE;
        }
        
        if($query_type===0)
            $query_type = 2;  // Par safety we consider unknown query as 'write'
        
        $count_read = count($this->_svc_read);
        $count_write = count($this->_svc_write);
        $rnd_count = 0;
        $rnd_index = 0;
        
        switch ($query_type)
        {
            case 1: // read
                if($count_read<1)
                {
                    dol_syslog("get_server: no servers available for read query", LOG_ERR);
                    return FALSE;
                }
                break;
            case 2: // write
                if($count_write<1)
                {
                    dol_syslog("get_server: no servers available for write query", LOG_ERR);
                    return FALSE;
                }
                break;
            default:
                {
                    dol_syslog("get_server: Unknown query type: ".$query_type, LOG_ERR);
                    return FALSE;
                }
        }

        $sessid = "";
        $login = "";
        if (isset($_SESSION["dol_login"]))        
            $login = $_SESSION["dol_login"];
        if(session_id()!="")
            $sessid = session_id();
        
        if(! $this->REDIS_USE_LOCALHOST)
        {
            dol_syslog("Serveurs distants REDIS ne sont pas (encore) supportés", LOG_ERR);
            return FALSE;            
        }
        
        $server=FALSE;
        $key = "";

        // Use the last server used for write, cached in Redis
        if($this->CONSUL_READ_FROM_WRITE_DB_HOST && ( ($login!="") || ($sessid!="") ) )
        {
            try {
                $redisClient = new Redis();
                $redisClient -> connect($this->REDIS_LOCALHOST_SOCKET);

                if($login!="")  // Search for previously used server, saved for login (normal case)
                {
                    $server = $redisClient -> get($login."_server");
                    if( !($server===FALSE) && !($server==="") )
                        $key = $login."_server";
                }
                
                if( ($key==="") && ($sessid!="") )  // Server not found - search it saved for session (case of 'fresh' login)
                {
                    $server = $redisClient -> get($sessid."_server");
                    if( !($server===FALSE) && !($server==="") )
                        $key = $sessid."_server";
                }
                
                $redisClient -> close();
            }
            catch( Exception $e ) { 
                dol_syslog("Redis operation error: ".$e->getMessage(), LOG_ERR);
                dol_syslog("Redis server cannot be used, falling back to Consul only mode - CONSUL_READ_FROM_WRITE_DB_HOST mode cannot be used", LOG_ERR);
//                return FALSE;
            }

            if ( !($server===FALSE) && !($server==="") )  // Server, previously used for write, is found - it will be used if still available
            {
                $arr_server = explode(":", $server);
                if(count($arr_server)>1)
                {
                    $port = intval($arr_server[1]);
                    if($port==0) $port=3306;    // Should never happens
                    if($this->connected)
                    {
                        if( ($this->database_host === $arr_server[0]) && ($this->database_port === $port) && $this->db->ping() )
                            return TRUE;   // Already connected to this server, nothing to do
                        else
                        {
                            $timestamp_debut = microtime(true);
                            $this->close();
                            unset($this->db);
                            $this->countReq2 ++;
                        }
                    }
                    $this->db = new mysqli($arr_server[0], $this->database_user, $this->database_pass, $this->database_name, $port);
                    if( ($this->db!=FALSE) && (!$this->db->connect_error) )
                    {
                        $this->database_host = $arr_server[0];
                        $this->database_port = $port;
                        $this->connected = TRUE;
                        $this->set_charset_and_collation();
                        if ($timestamp_debut>0.0)
                            $this->timeReconnect += (microtime(true)-$timestamp_debut);                    
                        return TRUE;
                    }
                    // The last used server is not available. We need to clean his address in Redis and retry the search.
                    try {
                        $redisClient = new Redis();
                        $redisClient -> connect($this->REDIS_LOCALHOST_SOCKET);
                        $redisClient -> del($key);
                        $redisClient -> close();
                        return $this->connect_server($query_type);
                    }
                    catch( Exception $e ) { 
                        dol_syslog("Redis operation error: ".$e->getMessage(), LOG_ERR);
                    }
                }
            }
        }
        
        $cur_timestamp = time();
        if( ($cur_timestamp - $this->_last_discover_time) > ($this->CONSUL_REDIS_CACHE_TTL / 2) )
            $this->discover_svc();   // On TTL/2 we rediscover services from Consul (or read cached values from Redis)
        // Search for a server in array
        switch ($query_type)
        {
            case 1: // read
                if( ($this->CONSUL_SERVICES_USE_FOR_READ===1) || ($count_read===1) )
                {
                    $server = $this->_svc_read[0];
                }
                else    // random server
                {
                    $rnd_count = $count_read * 10 - 1;
                    $rnd_index = intdiv(rand(0,$rnd_count), 10);
                    $server =  $this->_svc_read[$rnd_index];
                }
                break;
            case 2: // write
                if( ($this->CONSUL_SERVICES_USE_FOR_WRITE===1) || ($count_write===1) )
                {
                    $server = $this->_svc_write[0];
                }
                else    // random server
                {
                    $rnd_count = $count_write * 10 -1;
                    $rnd_index = intdiv(rand(0,$rnd_count), 10);
                    $server = $this->_svc_write[$rnd_index];
                }
                break;
        }

        if(!empty($server))
        {
            // Try to connect to the server
            $arr_server = explode(":", $server);
            $port = intval($arr_server[1]);
            if($this->connected)
            {
                if( ($this->database_host === $arr_server[0]) && ($this->database_port === $port) && $this->db->ping() )
                    return TRUE;
                else
                {
                    $timestamp_debut = microtime(true);
                    $this->close();
                    unset($this->db);
                    $this->countReq2 ++;
                }
            }
            $this->db = new mysqli($arr_server[0], $this->database_user, $this->database_pass, $this->database_name, $port);
            if( ($this->db!=FALSE) && (!$this->db->connect_error) )
            {
                $this->database_host = $arr_server[0];
                $this->database_port = $port;
                $this->connected = TRUE;
                $this->set_charset_and_collation();
                if ($timestamp_debut>0.0)
                    $this->timeReconnect += (microtime(true)-$timestamp_debut);                    

                // Write the server used for write to Redis if needed
                if( $query_type==2 && $this->CONSUL_READ_FROM_WRITE_DB_HOST && ( ($login!="") || ($sessid!="") ) )
                {
                    if($login!="")
                        $key = $login."_server";
                    else
                        $key = $sessid."_server";
                    try {
                        $redisClient = new Redis();
                        $redisClient -> connect($this->REDIS_LOCALHOST_SOCKET);
                        $redisClient -> setex($key, $this->CONSUL_READ_FROM_WRITE_DB_HOST_TIME, $server);
                        $redisClient -> close();
                    }
                    catch( Exception $e ) { 
                        dol_syslog("Redis operation error: ".$e->getMessage(), LOG_ERR);
                        dol_syslog("Cannot write the address of last used for write server", LOG_ERR);
                    }
                }
                return TRUE;
            }
            // If we cannot connect to the server - we need to remove it from the array and retry the search
            if($query_type==2)
            {
                if (($ind_srv = array_search($server, $this->_svc_write)) !== false) 
                    array_splice($this->_svc_write, $ind_srv, 1);
    //                unset($this->_svc_write[$ind_srv]);     // Should always be true            
            }
            else
            {
                if (($ind_srv = array_search($server, $this->_svc_read)) !== false) 
                    array_splice($this->_svc_read, $ind_srv, 1);
    //                unset($this->_svc_read[$ind_srv]);     // Should always be true                        
            }

            return $this->connect_server($query_type);
        }
        
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
     *	@return	bool|mysqli_result		Resultset of answer
     */
    function query($query,$usesavepoint=0,$type='auto')
    {
//    	global $conf, $user;
        global $user;
        
        $qtype = 2; // 0 - unknown, 1 - read, 2 - write
        
        if(stripos($type,"dml")==0)
        {
            $trim_query = trim($query);
            if(stripos($trim_query, "SELECT") === 0) $qtype = 1;
            if(stripos($trim_query, "SHOW") === 0) $qtype = 1;
            if(stripos($trim_query, "SET") === 0) $qtype = 1;
        }
        dol_syslog('Query: '.$query, LOG_DEBUG);
        dol_syslog('Query type: '.$qtype, LOG_DEBUG);
        
        if ($user->admin && defined('BIMP_PRINT_ADMIN_SQL')) {
            $debugTime = true;
        }
        else
            $debugTime = false;
/*
        $debugTime = false;
        if (class_exists("BimpDebug") && BimpDebug::isActive('bimpcore/objects/print_admin_sql')) {
            global $user;
            if ($user->admin)
                $debugTime = true;
        }
*/
        /* moddrsi */
        $tabRemplacement = array(
//            "SELECT COUNT(DISTINCT a.rowid) as nb_rows FROM llx_propal a LEFT JOIN llx_element_contact ec ON ec.element_id = a.rowid LEFT JOIN llx_c_type_contact tc ON ec.fk_c_type_contact = tc.rowid" =>
//                "SELECT COUNT(DISTINCT a.rowid) as nb_rows FROM llx_propal a ",
//            "SELECT DISTINCT (a.rowid) FROM llx_propal a LEFT JOIN llx_element_contact ec ON ec.element_id = a.rowid LEFT JOIN llx_c_type_contact tc ON ec.fk_c_type_contact = tc.rowid ORDER BY a." =>
//                "SELECT DISTINCT (a.rowid) FROM llx_propal a ORDER BY a."
        );
        foreach ($tabRemplacement as $old => $new) {
            $query = str_replace($old, $new, $query);
        }

        $this->countReq ++;
        $timestamp_debut = microtime(true);
        if ($debugTime) {
            if (!isset($this->timestamp_debut)) {
                $this->timestamp_debut = $timestamp_debut;
                $this->timestamp_derfin = $timestamp_debut;
            }
        }
        /* fmoddrsi */

        if($this->transaction_opened == 0 && !$this->connect_server($qtype))
        {
            dol_syslog(get_class($this)."::query: Fatal error - cannot connect to database server for request type: ".$qtype, LOG_ERR);
            return FALSE;
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
        $ret = $this->db->query($query);
        
        if (! preg_match("/^COMMIT/i",$query) && ! preg_match("/^ROLLBACK/i",$query))
        {
            // Si requete utilisateur, on la sauvegarde ainsi que son resultset
            if (! $ret)
            {
                $this->lastqueryerror = $query;
                $this->lasterror = $this->error();
                $this->lasterrno = $this->errno();

                $debug = "";
                if (function_exists("synGetDebug"))
                    $debug = synGetDebug();

//				if ($conf->global->SYSLOG_LEVEL < LOG_DEBUG) dol_syslog(get_class($this)."::query SQL Error query: ".$query, LOG_ERR);	// Log of request was not yet done previously
                dol_syslog(get_class($this)."::query SQL Error message: ".$this->lasterrno." ".$this->lasterror, LOG_ERR);
            }
            $this->lastquery=$query;
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
        /* moddrsi */
        $timestamp_fin = microtime(true);
        $difference_ms = $timestamp_fin - $timestamp_debut;
        if ($debugTime) {
            global $tabReq;

            if (!isset($tabReq[$query]))
                $tabReq[$query] = 0;
            $tabReq[$query] ++;

            $difference_ms2 = $timestamp_fin - $this->timestamp_debut;
            $difference_ms3 = $timestamp_debut - $this->timestamp_derfin;


            if ($tabReq[$query] > 2)
                echo 'attention req identique ' . $tabReq[$query] . " foix.";

            if ($difference_ms > 0.00 || $difference_ms3 > 0.1) {
                echo $this->countReq . " ";
                echo $query . " <br/>";
                echo "||" . $this->num_rows($ret) . " en " . $difference_ms . "s depuis deb " . $difference_ms2 . " <br/><br/>";
            }

            $this->timestamp_derfin = $timestamp_fin;
        }

        if (defined('BIMP_LIB') && BimpDebug::isActive() && !in_array($query, array('BEGIN', 'COMMIT', 'ROLLBACK'))) {
            BimpDebug::addSqlDebug($query);

            $content = BimpRender::renderDebugInfo($query);
            BimpDebug::addDebug('sql', 'Requête #' . $this->countReq . ' - ' . $difference_ms . ' s', $content, array(
                'open' => false
            ));

            if ($ret <= 0) {
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
     *	Renvoie la ligne courante (comme un objet) pour le curseur resultset
     *
     *	@param	mysqli_result	$resultset	Curseur de la requete voulue
     *	@return	object|null					Object result line or null if KO or end of cursor
     */
    function fetch_object($resultset)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
		return $resultset->fetch_object();
    }


    /**
     *	Return datas as an array
     *
     *	@param	mysqli_result	$resultset	Resultset of request
     *	@return	array|null					Array or null if KO or end of cursor
     */
    function fetch_array($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
        return $resultset->fetch_array();
    }

    /**
     *	Return datas as an array
     *
     *	@param	mysqli_result	$resultset	Resultset of request
     *	@return	array|null|0				Array or null if KO or end of cursor or 0 if resultset is bool
     */
    function fetch_row($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_bool($resultset))
        {
            if (! is_object($resultset)) { $resultset=$this->_results; }
            return $resultset->fetch_row();
        }
        else
        {
            // si le curseur est un booleen on retourne la valeur 0
            return 0;
        }
    }

    /**
     *	Return number of lines for result of a SELECT
     *
     *	@param	mysqli_result	$resultset  Resulset of requests
     *	@return	int				Nb of lines
     *	@see    affected_rows
     */
    function num_rows($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
        return $resultset->num_rows;
    }

    /**
     *	Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
     *
     *	@param	mysqli_result	$resultset	Curseur de la requete voulue
     *	@return int							Nombre de lignes
     *	@see    num_rows
     */
    function affected_rows($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
        // mysql necessite un link de base pour cette fonction contrairement
        // a pqsql qui prend un resultset
        if(!$this->connected)
        {
            dol_syslog("Call to affected_rows when server is disconnected", LOG_ERR);
            return 0;
        }
        return $this->db->affected_rows;
    }


    /**
     *	Libere le dernier resultset utilise sur cette connexion
     *
     *	@param  mysqli_result	$resultset	Curseur de la requete voulue
     *	@return	void
     */
    function free($resultset=null)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
        // Si resultset en est un, on libere la memoire
        if (is_object($resultset)) $resultset->free_result();
    }

    /**
     *	Escape a string to insert data
     *
     *	@param	string	$stringtoencode		String to escape
     *	@return	string						String escaped
     */
    function escape($stringtoencode)
    {
        if(!$this->connected)
        {
            dol_syslog("Call to escape when server is disconnected", LOG_WARNING);
            dol_syslog("Using replacement function - valid for utf8 only!!", LOG_WARNING);
            return $this->real_escape($stringtoencode);
        }
        return $this->db->real_escape_string($stringtoencode);
    }

/**
** Returns a string with backslashes before characters that need to be escaped.
** As required by MySQL and suitable for multi-byte character sets
** Characters encoded are NUL (ASCII 0), \n, \r, \, ', ", and ctrl-Z.
** In addition, the special control characters % and _ are also escaped,
** suitable for all statements, but especially suitable for `LIKE`.
** @param string $stringtoencode String to add slashes to
** @return $string with `\` prepended to reserved characters
**
** @author Trevor Herselman
**/  
    function real_escape($stringtoencode)
    {
        if (function_exists('mb_ereg_replace'))
            return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]', '\\\0', $stringtoencode);
        else
            return preg_replace('~[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]~u', '\\\$0', $stringtoencode);
    }
    
    /**
     *	Return generic error code of last operation.
     *
     *	@return	string		Error code (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
     */
    function errno()
    {
        if (! $this->connected) {
            // Si il y a eu echec de connexion, $this->db n'est pas valide.
            return 'DB_ERROR_FAILED_TO_CONNECT';
        } else {
            // Constants to convert a MySql error code to a generic Dolibarr error code
            $errorcode_map = array(
            1004 => 'DB_ERROR_CANNOT_CREATE',
            1005 => 'DB_ERROR_CANNOT_CREATE',
            1006 => 'DB_ERROR_CANNOT_CREATE',
            1007 => 'DB_ERROR_ALREADY_EXISTS',
            1008 => 'DB_ERROR_CANNOT_DROP',
            1022 => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
            1025 => 'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
            1044 => 'DB_ERROR_ACCESSDENIED',
            1046 => 'DB_ERROR_NODBSELECTED',
            1048 => 'DB_ERROR_CONSTRAINT',
            1050 => 'DB_ERROR_TABLE_ALREADY_EXISTS',
            1051 => 'DB_ERROR_NOSUCHTABLE',
            1054 => 'DB_ERROR_NOSUCHFIELD',
            1060 => 'DB_ERROR_COLUMN_ALREADY_EXISTS',
            1061 => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
            1062 => 'DB_ERROR_RECORD_ALREADY_EXISTS',
            1064 => 'DB_ERROR_SYNTAX',
            1068 => 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS',
            1075 => 'DB_ERROR_CANT_DROP_PRIMARY_KEY',
            1091 => 'DB_ERROR_NOSUCHFIELD',
            1100 => 'DB_ERROR_NOT_LOCKED',
            1136 => 'DB_ERROR_VALUE_COUNT_ON_ROW',
            1146 => 'DB_ERROR_NOSUCHTABLE',
            1215 => 'DB_ERROR_CANNOT_ADD_FOREIGN_KEY_CONSTRAINT',
            1216 => 'DB_ERROR_NO_PARENT',
            1217 => 'DB_ERROR_CHILD_EXISTS',
            1396 => 'DB_ERROR_USER_ALREADY_EXISTS',    // When creating user already existing
            1451 => 'DB_ERROR_CHILD_EXISTS'
            );

            if (isset($errorcode_map[$this->db->errno])) {
                return $errorcode_map[$this->db->errno];
            }
            $errno=$this->db->errno;
            return ($errno?'DB_ERROR_'.$errno:'0');
        }
    }

    /**
	 *	Return description of last error
	 *
	 *	@return	string		Error text
     */
    function error()
    {
        if (! $this->connected) {
            // Si il y a eu echec de connexion, $this->db n'est pas valide pour mysqli_error.
            return 'Not connected. Check setup parameters in conf/conf.php file and your mysql client and server versions';
        }
        else {
            return $this->db->error;
        }
    }

    /**
	 * Get last ID after an insert INSERT
	 *
	 * @param   string	$tab    	Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
	 * @param	string	$fieldid	Field name
	 * @return  int|string			Id of row
     */
    function last_insert_id($tab,$fieldid='rowid')
    {
        if(!$this->connected)
        {
            dol_syslog("Call to last_insert_id when server is disconnected", LOG_ERR);
            return 0;
        }
        return $this->db->insert_id;
    }

    /**
     *	Encrypt sensitive data in database
     *  Warning: This function includes the escape, so it must use direct value
     *
     *	@param	string	$fieldorvalue	Field name or value to encrypt
     * 	@param	int		$withQuotes		Return string with quotes
     * 	@return	string					XXX(field) or XXX('value') or field or 'value'
     *
     */
    function encrypt($fieldorvalue, $withQuotes=0)
    {
        global $conf;

        // Type of encryption (2: AES (recommended), 1: DES , 0: no encryption)
        $cryptType = (!empty($conf->db->dolibarr_main_db_encryption)?$conf->db->dolibarr_main_db_encryption:0);

        //Encryption key
        $cryptKey = (!empty($conf->db->dolibarr_main_db_cryptkey)?$conf->db->dolibarr_main_db_cryptkey:'');

        $return = ($withQuotes?"'":"").$this->escape($fieldorvalue).($withQuotes?"'":"");

        if ($cryptType && !empty($cryptKey))
        {
            if ($cryptType == 2)
            {
                $return = 'AES_ENCRYPT('.$return.',\''.$cryptKey.'\')';
            }
            else if ($cryptType == 1)
            {
                $return = 'DES_ENCRYPT('.$return.',\''.$cryptKey.'\')';
            }
        }

        return $return;
    }

    /**
     *	Decrypt sensitive data in database
     *
     *	@param	string	$value			Value to decrypt
     * 	@return	string					Decrypted value if used
     */
    function decrypt($value)
    {
        global $conf;

        // Type of encryption (2: AES (recommended), 1: DES , 0: no encryption)
        $cryptType = (!empty($conf->db->dolibarr_main_db_encryption)?$conf->db->dolibarr_main_db_encryption:0);

        //Encryption key
        $cryptKey = (!empty($conf->db->dolibarr_main_db_cryptkey)?$conf->db->dolibarr_main_db_cryptkey:'');

        $return = $value;

        if ($cryptType && !empty($cryptKey))
        {
            if ($cryptType == 2)
            {
                $return = 'AES_DECRYPT('.$value.',\''.$cryptKey.'\')';
            }
            else if ($cryptType == 1)
            {
                $return = 'DES_DECRYPT('.$value.',\''.$cryptKey.'\')';
            }
        }

        return $return;
    }


    /**
	 * Return connexion ID
	 *
	 * @return	        string      Id connexion
     */
    function DDLGetConnectId()
    {
        $resql=$this->query('SELECT CONNECTION_ID()');
        if ($resql)
        {
            $row=$this->fetch_row($resql);
            return $row[0];
        }
        else return '?';
    }

    /**
	 *	Create a new database
	 *	Do not use function xxx_create_db (xxx=mysql, ...) as they are deprecated
	 *	We force to create database with charset this->forcecharset and collate this->forcecollate
	 *
	 *	@param	string	$database		Database name to create
	 * 	@param	string	$charset		Charset used to store data
	 * 	@param	string	$collation		Charset used to sort data
	 * 	@param	string	$owner			Username of database owner
	 * 	@return	bool|mysqli_result		resource defined if OK, null if KO
     */
    function DDLCreateDb($database,$charset='',$collation='',$owner='')
    {
        if (empty($charset))   $charset=$this->forcecharset;
        if (empty($collation)) $collation=$this->forcecollate;

        // ALTER DATABASE dolibarr_db DEFAULT CHARACTER SET latin DEFAULT COLLATE latin1_swedish_ci
		$sql = "CREATE DATABASE `".$this->escape($database)."`";
		$sql.= " DEFAULT CHARACTER SET `".$this->escape($charset)."` DEFAULT COLLATE `".$this->escape($collation)."`";

        dol_syslog($sql,LOG_DEBUG);
        $ret=$this->query($sql);
        if (! $ret)
        {
            // We try again for compatibility with Mysql < 4.1.1
            $sql = "CREATE DATABASE `".$this->escape($database)."`";
            dol_syslog($sql,LOG_DEBUG);
            $ret=$this->query($sql);
        }
        return $ret;
    }

    /**
	 *  List tables into a database
	 *
	 *  @param	string		$database	Name of database
	 *  @param	string		$table		Nmae of table filter ('xxx%')
	 *  @return	array					List of tables in an array
     */
    function DDLListTables($database, $table='')
    {
        $listtables=array();

        $like = '';
        if ($table) $like = "LIKE '".$table."'";
        $sql="SHOW TABLES FROM ".$database." ".$like.";";
        //print $sql;
        $result = $this->query($sql);
        if ($result)
        {
            while($row = $this->fetch_row($result))
            {
                $listtables[] = $row[0];
            }
        }
        return $listtables;
    }

    /**
	 *	List information of columns into a table.
	 *
	 *	@param	string	$table		Name of table
	 *	@return	array				Tableau des informations des champs de la table
     */
    function DDLInfoTable($table)
    {
        $infotables=array();

        $sql="SHOW FULL COLUMNS FROM ".$table.";";

        dol_syslog($sql,LOG_DEBUG);
        $result = $this->query($sql);
        if ($result)
        {
            while($row = $this->fetch_row($result))
            {
                $infotables[] = $row;
            }
        }
        return $infotables;
    }

    /**
	 *	Create a table into database
	 *
	 *	@param	    string	$table 			Name of table
	 *	@param	    array	$fields 		Tableau associatif [nom champ][tableau des descriptions]
	 *	@param	    string	$primary_key 	Nom du champ qui sera la clef primaire
	 *	@param	    string	$type 			Type de la table
	 *	@param	    array	$unique_keys 	Tableau associatifs Nom de champs qui seront clef unique => valeur
	 *	@param	    array	$fulltext_keys	Tableau des Nom de champs qui seront indexes en fulltext
	 *	@param	    array	$keys 			Tableau des champs cles noms => valeur
	 *	@return	    int						<0 if KO, >=0 if OK
     */
    function DDLCreateTable($table,$fields,$primary_key,$type,$unique_keys=null,$fulltext_keys=null,$keys=null)
    {
	    // FIXME: $fulltext_keys parameter is unused

        // cles recherchees dans le tableau des descriptions (fields) : type,value,attribute,null,default,extra
        // ex. : $fields['rowid'] = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
        $sql = "CREATE TABLE ".$table."(";
        $i=0;
        foreach($fields as $field_name => $field_desc)
        {
        	$sqlfields[$i] = $field_name." ";
			$sqlfields[$i]  .= $field_desc['type'];
			if( preg_match("/^[^\s]/i",$field_desc['value'])) {
				$sqlfields[$i]  .= "(".$field_desc['value'].")";
			}
			if( preg_match("/^[^\s]/i",$field_desc['attribute'])) {
				$sqlfields[$i]  .= " ".$field_desc['attribute'];
			}
			if( preg_match("/^[^\s]/i",$field_desc['default']))
			{
				if ((preg_match("/null/i",$field_desc['default'])) || (preg_match("/CURRENT_TIMESTAMP/i",$field_desc['default']))) {
					$sqlfields[$i]  .= " default ".$field_desc['default'];
				}
				else {
					$sqlfields[$i]  .= " default '".$field_desc['default']."'";
				}
			}
			if( preg_match("/^[^\s]/i",$field_desc['null'])) {
				$sqlfields[$i]  .= " ".$field_desc['null'];
			}
			if( preg_match("/^[^\s]/i",$field_desc['extra'])) {
				$sqlfields[$i]  .= " ".$field_desc['extra'];
			}
            $i++;
        }
        if($primary_key != "")
        $pk = "primary key(".$primary_key.")";

        if(is_array($unique_keys)) {
            $i = 0;
            foreach($unique_keys as $key => $value)
            {
                $sqluq[$i] = "UNIQUE KEY '".$key."' ('".$value."')";
                $i++;
            }
        }
        if(is_array($keys))
        {
            $i = 0;
            foreach($keys as $key => $value)
            {
                $sqlk[$i] = "KEY ".$key." (".$value.")";
                $i++;
            }
        }
        $sql .= implode(',',$sqlfields);
        if($primary_key != "")
        $sql .= ",".$pk;
        if($unique_keys != "")
        $sql .= ",".implode(',',$sqluq);
        if(is_array($keys))
        $sql .= ",".implode(',',$sqlk);
        $sql .=") engine=".$type;

        if(! $this->query($sql))
        return -1;
        else
        return 1;
    }

    /**
     *	Drop a table into database
     *
     *	@param	    string	$table 			Name of table
     *	@return	    int						<0 if KO, >=0 if OK
     */
    function DDLDropTable($table)
    {
    	$sql = "DROP TABLE ".$table;

		if (! $this->query($sql))
 			return -1;
    	else
    		return 1;
    }

    /**
	 *	Return a pointer of line with description of a table or field
	 *
	 *	@param	string		$table	Name of table
	 *	@param	string		$field	Optionnel : Name of field if we want description of field
	 *	@return	bool|mysqli_result	Resultset x (x->Field, x->Type, ...)
     */
    function DDLDescTable($table,$field="")
    {
        $sql="DESC ".$table." ".$field;

        dol_syslog(get_class($this)."::DDLDescTable ".$sql,LOG_DEBUG);
        $this->_results = $this->query($sql);
        return $this->_results;
    }

    /**
	 *	Create a new field into table
	 *
	 *	@param	string	$table 				Name of table
	 *	@param	string	$field_name 		Name of field to add
	 *	@param	string	$field_desc 		Tableau associatif de description du champ a inserer[nom du parametre][valeur du parametre]
	 *	@param	string	$field_position 	Optionnel ex.: "after champtruc"
	 *	@return	int							<0 if KO, >0 if OK
     */
    function DDLAddField($table,$field_name,$field_desc,$field_position="")
    {
        // cles recherchees dans le tableau des descriptions (field_desc) : type,value,attribute,null,default,extra
        // ex. : $field_desc = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
        $sql= "ALTER TABLE ".$table." ADD ".$field_name." ";
        $sql.= $field_desc['type'];
        if (preg_match("/^[^\s]/i",$field_desc['value']))
        {
            if (! in_array($field_desc['type'],array('date','datetime')))
            {
                $sql.= "(".$field_desc['value'].")";
            }
        }
        if (isset($field_desc['attribute']) && preg_match("/^[^\s]/i",$field_desc['attribute']))
        {
        	$sql.= " ".$field_desc['attribute'];
        }
        if (isset($field_desc['null']) && preg_match("/^[^\s]/i",$field_desc['null']))
        {
        	$sql.= " ".$field_desc['null'];
        }
        if (isset($field_desc['default']) && preg_match("/^[^\s]/i",$field_desc['default']))
        {
            if(preg_match("/null/i",$field_desc['default']))
            $sql.= " default ".$field_desc['default'];
            else
            $sql.= " default '".$field_desc['default']."'";
        }
        if (isset($field_desc['extra']) && preg_match("/^[^\s]/i",$field_desc['extra']))
        {
        	$sql.= " ".$field_desc['extra'];
        }
        $sql.= " ".$field_position;

        dol_syslog(get_class($this)."::DDLAddField ".$sql,LOG_DEBUG);
        if ($this->query($sql)) {
            return 1;
        }
        return -1;
    }

    /**
	 *	Update format of a field into a table
	 *
	 *	@param	string	$table 				Name of table
	 *	@param	string	$field_name 		Name of field to modify
	 *	@param	string	$field_desc 		Array with description of field format
	 *	@return	int							<0 if KO, >0 if OK
     */
    function DDLUpdateField($table,$field_name,$field_desc)
    {
        $sql = "ALTER TABLE ".$table;
        $sql .= " MODIFY COLUMN ".$field_name." ".$field_desc['type'];
        if ($field_desc['type'] == 'double' || $field_desc['type'] == 'tinyint' || $field_desc['type'] == 'int' || $field_desc['type'] == 'varchar') {
        	$sql.="(".$field_desc['value'].")";
        }
        if ($field_desc['null'] == 'not null' || $field_desc['null'] == 'NOT NULL')
        {
        	// We will try to change format of column to NOT NULL. To be sure the ALTER works, we try to update fields that are NULL
        	if ($field_desc['type'] == 'varchar' || $field_desc['type'] == 'text')
        	{
        		$sqlbis="UPDATE ".$table." SET ".$field_name." = '".$this->escape($field_desc['default'] ? $field_desc['default'] : '')."' WHERE ".$field_name." IS NULL";
        		$this->query($sqlbis);
        	}
        	elseif ($field_desc['type'] == 'tinyint' || $field_desc['type'] == 'int')
        	{
        		$sqlbis="UPDATE ".$table." SET ".$field_name." = ".((int) $this->escape($field_desc['default'] ? $field_desc['default'] : 0))." WHERE ".$field_name." IS NULL";
        		$this->query($sqlbis);
        	}

        	$sql.=" NOT NULL";
        }

        if ($field_desc['default'] != '')
        {
			if ($field_desc['type'] == 'double' || $field_desc['type'] == 'tinyint' || $field_desc['type'] == 'int') $sql.=" DEFAULT ".$this->escape($field_desc['default']);
			elseif ($field_desc['type'] != 'text') $sql.=" DEFAULT '".$this->escape($field_desc['default'])."'";							// Default not supported on text fields
        }

        dol_syslog(get_class($this)."::DDLUpdateField ".$sql,LOG_DEBUG);
        if (! $this->query($sql))
        return -1;
        else
        return 1;
    }

    /**
	 *	Drop a field from table
	 *
	 *	@param	string	$table 			Name of table
	 *	@param	string	$field_name 	Name of field to drop
	 *	@return	int						<0 if KO, >0 if OK
     */
    function DDLDropField($table,$field_name)
    {
        $sql= "ALTER TABLE ".$table." DROP COLUMN `".$field_name."`";
        dol_syslog(get_class($this)."::DDLDropField ".$sql,LOG_DEBUG);
        if ($this->query($sql)) {
            return 1;
        }
	    $this->error=$this->lasterror();
	    return -1;
    }


    /**
	 * 	Create a user and privileges to connect to database (even if database does not exists yet)
	 *
	 *	@param	string	$dolibarr_main_db_host 		Ip server or '%'
	 *	@param	string	$dolibarr_main_db_user 		Nom user a creer
	 *	@param	string	$dolibarr_main_db_pass 		Mot de passe user a creer
	 *	@param	string	$dolibarr_main_db_name		Database name where user must be granted
	 *	@return	int									<0 if KO, >=0 if OK
     */
    function DDLCreateUser($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name)
    {
        $sql = "CREATE USER '".$this->escape($dolibarr_main_db_user)."'";
        dol_syslog(get_class($this)."::DDLCreateUser", LOG_DEBUG);	// No sql to avoid password in log
        $resql=$this->query($sql);
        if (! $resql)
        {
            if ($this->lasterrno != 'DB_ERROR_USER_ALREADY_EXISTS')
            {
            	return -1;
            }
            else
			{
            	// If user already exists, we continue to set permissions
            	dol_syslog(get_class($this)."::DDLCreateUser sql=".$sql, LOG_WARNING);
            }
        }
        $sql = "GRANT ALL PRIVILEGES ON ".$this->escape($dolibarr_main_db_name).".* TO '".$this->escape($dolibarr_main_db_user)."'@'".$this->escape($dolibarr_main_db_host)."' IDENTIFIED BY '".$this->escape($dolibarr_main_db_pass)."'";
        dol_syslog(get_class($this)."::DDLCreateUser", LOG_DEBUG);	// No sql to avoid password in log
        $resql=$this->query($sql);
        if (! $resql)
        {
            return -1;
        }

        $sql="FLUSH Privileges";

        dol_syslog(get_class($this)."::DDLCreateUser", LOG_DEBUG);
        $resql=$this->query($sql);
        if (! $resql)
        {
            return -1;
        }

        return 1;
    }

    /**
     *	Return charset used to store data in current database
     *  Note: if we are connected to databasename, it is same result than using SELECT default_character_set_name FROM information_schema.SCHEMATA WHERE schema_name = "databasename";)
     *
     *	@return		string		Charset
     *  @see getDefaultCollationDatabase
     */
    function getDefaultCharacterSetDatabase()
    {
        $resql=$this->query('SHOW VARIABLES LIKE \'character_set_database\'');
        if (!$resql)
        {
            // version Mysql < 4.1.1
            return $this->forcecharset;
        }
        $liste=$this->fetch_array($resql);
        $tmpval = $liste['Value'];

        return $tmpval;
    }

    /**
     *	Return list of available charset that can be used to store data in database
     *
     *	@return		array|null		List of Charset
     */
    function getListOfCharacterSet()
    {
        $resql=$this->query('SHOW CHARSET');
        $liste = array();
        if ($resql)
        {
            $i = 0;
            while ($obj = $this->fetch_object($resql) )
            {
                $liste[$i]['charset'] = $obj->Charset;
                $liste[$i]['description'] = $obj->Description;
                $i++;
            }
            $this->free($resql);
        } else {
            // version Mysql < 4.1.1
            return null;
        }
        return $liste;
    }

    /**
     *	Return collation used in current database
     *
     *	@return		string		Collation value
     *  @see getDefaultCharacterSetDatabase
     */
    function getDefaultCollationDatabase()
    {
        $resql=$this->query('SHOW VARIABLES LIKE \'collation_database\'');
        if (!$resql)
        {
            // version Mysql < 4.1.1
            return $this->forcecollate;
        }
        $liste=$this->fetch_array($resql);
        $tmpval = $liste['Value'];

        return $tmpval;
    }

    /**
     *	Return list of available collation that can be used for database
     *
     *	@return		array|null		Liste of Collation
     */
    function getListOfCollation()
    {
        $resql=$this->query('SHOW COLLATION');
        $liste = array();
        if ($resql)
        {
            $i = 0;
            while ($obj = $this->fetch_object($resql) )
            {
                $liste[$i]['collation'] = $obj->Collation;
                $i++;
            }
            $this->free($resql);
        } else {
            // version Mysql < 4.1.1
            return null;
        }
        return $liste;
    }

    /**
	 *	Return full path of dump program
	 *
	 *	@return		string		Full path of dump program
     */
    function getPathOfDump()
    {
        $fullpathofdump='/pathtomysqldump/mysqldump';

        $resql=$this->query('SHOW VARIABLES LIKE \'basedir\'');
        if ($resql)
        {
            $liste=$this->fetch_array($resql);
            $basedir=$liste['Value'];
            $fullpathofdump=$basedir.(preg_match('/\/$/',$basedir)?'':'/').'bin/mysqldump';
        }
        return $fullpathofdump;
    }

    /**
     *	Return full path of restore program
     *
     *	@return		string		Full path of restore program
     */
    function getPathOfRestore()
    {
        $fullpathofimport='/pathtomysql/mysql';

        $resql=$this->query('SHOW VARIABLES LIKE \'basedir\'');
        if ($resql)
        {
            $liste=$this->fetch_array($resql);
            $basedir=$liste['Value'];
            $fullpathofimport=$basedir.(preg_match('/\/$/',$basedir)?'':'/').'bin/mysql';
        }
        return $fullpathofimport;
    }

    /**
     * Return value of server parameters
     *
     * @param	string	$filter		Filter list on a particular value
	 * @return	array				Array of key-values (key=>value)
     */
    function getServerParametersValues($filter='')
    {
        $result=array();

        $sql='SHOW VARIABLES';
        if ($filter) $sql.=" LIKE '".$this->escape($filter)."'";
        $resql=$this->query($sql);
        if ($resql)
        {
        	while($obj=$this->fetch_object($resql)) $result[$obj->Variable_name]=$obj->Value;
        }

        return $result;
    }

    /**
     * Return value of server status (current indicators on memory, cache...)
     *
     * @param	string	$filter		Filter list on a particular value
	 * @return  array				Array of key-values (key=>value)
     */
    function getServerStatusValues($filter='')
    {
        $result=array();

        $sql='SHOW STATUS';
        if ($filter) $sql.=" LIKE '".$this->escape($filter)."'";
        $resql=$this->query($sql);
        if ($resql)
        {
            while($obj=$this->fetch_object($resql)) $result[$obj->Variable_name]=$obj->Value;
        }

        return $result;
    }
    
    /* moddrsi */

    public function begin()
    {
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
    }
    
    public function commit($log = '')
    {
        $id_trans = $this->transaction_opened;
        
        $res = parent::commit($log);

        if (defined('BIMP_LIB') && BimpDebug::isActive()) {
            if ($res <= 0) {
                $content = BimpRender::renderAlerts('Echec COMMIT #' . $id_trans . ' - ' . $this->lasterror());
                BimpDebug::addDebug('sql', '', $content, array(
                    'foldable' => false
                ));
            } else {
                $content = '<span class="success">COMMIT #' . $id_trans . '</span><br/><br/>';
                BimpDebug::addDebug('sql', '', $content, array(
                    'foldable' => false
                ));
            }
        }
    }
    
    public function rollback($log = '')
    {
        $id_trans = $this->transaction_opened;
        
        $res = parent::rollback($log);

        if (defined('BIMP_LIB') && BimpDebug::isActive()) {
            if ($res <= 0) {
                $content = BimpRender::renderAlerts('Echec ROLLBACK #' . $id_trans . ' - ' . $this->lasterror());
                BimpDebug::addDebug('sql', '', $content, array(
                    'foldable' => false
                ));
            } else {
                $content = '<span class="danger">ROLLBACK #' . $id_trans . '</span><br/><br/>';
                BimpDebug::addDebug('sql', '', $content, array(
                    'foldable' => false
                ));
            }
        }
    }
    /* fmoddrsi */
    
}


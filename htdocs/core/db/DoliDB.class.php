<?php
/*
 * Copyright (C) 2013-2015 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2015 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file 		htdocs/core/db/DoliDB.class.php
 * \brief 		Class file to manage Dolibarr database access
 */

require_once DOL_DOCUMENT_ROOT.'/core/db/Database.interface.php';

/**
 * Class to manage Dolibarr database access
 */
abstract class DoliDB implements Database
{
	/** @var bool|resource|mysqli|SQLite3|PgSql\connection Database handler */
	public $db;
	/** @var string Database type */
	public $type;
	/** @var string Charset used to force charset when creating database */
	public $forcecharset = 'utf8';
	/** @var string Collate used to force collate when creating database */
	public $forcecollate = 'utf8_unicode_ci';

	/** @var resource Resultset of last query */
	private $_results;

	/** @var bool true if connected, else false */
	public $connected;
	/** @var bool true if database selected, else false */
	public $database_selected;
	/** @var string Selected database name */
	public $database_name;
	/** @var string Database username */
	public $database_user;
	/** @var string Database host */
	public $database_host;
	/** @var int Database port */
	public $database_port;
	/** @var int >=1 if a transaction is opened, 0 otherwise */
	public $transaction_opened;
	/** @var string Last successful query */
	public $lastquery;
	/** @var string Last failed query */
	public $lastqueryerror;
	/** @var string Last error message */
	public $lasterror;
	/** @var string Last error number. For example: 'DB_ERROR_RECORD_ALREADY_EXISTS', '12345', ... */
	public $lasterrno;

	/** @var string If we need to set a prefix specific to the database so it can be reused (when defined instead of MAIN_DB_PREFIX) to forge requests */
	public $prefix_db;

	/** @var bool Status */
	public $ok;
	/** @var string */
	public $error;

        /* moddrsi */
        public $has_rollback = false;
        public $noTransaction = false;
        /* fmoddrsi */

	/**
	 *	Return the DB prefix found into prefix_db (if it was set manually by doing $dbhandler->prefix_db=...).
	 *  Otherwise return MAIN_DB_PREFIX (common use).
	 *
	 *	@return string		The DB prefix
	 */
	public function prefix()
	{
		return (empty($this->prefix_db) ? MAIN_DB_PREFIX : $this->prefix_db);
	}

	/**
	 *	Format a SQL IF
	 *
	 *	@param	string	$test           Test string (example: 'cd.statut=0', 'field IS NULL')
	 *	@param	string	$resok          resultat si test egal
	 *	@param	string	$resko          resultat si test non egal
	 *	@return	string          		SQL string
	 */
	public function ifsql($test, $resok, $resko)
	{
		//return 'IF('.$test.','.$resok.','.$resko.')';		// Not sql standard
		return '(CASE WHEN '.$test.' THEN '.$resok.' ELSE '.$resko.' END)';
	}

	/**
	 * Return SQL string to force an index
	 *
	 * @param	string	$nameofindex	Name of index
	 * @return	string					SQL string
	 */
	public function hintindex($nameofindex)
	{
		return '';
	}


	/**
	 *	Format a SQL REGEXP
	 *
	 *	@param	string	$subject        string tested
	 *	@param	string  $pattern        SQL pattern to match
	 *	@param	string	$sqlstring      whether or not the string being tested is an SQL expression
	 *	@return	string          		SQL string
	 */
	public function regexpsql($subject, $pattern, $sqlstring = false)
	{
		if ($sqlstring) {
			return "(". $subject ." REGEXP '" . $pattern . "')";
		}

		return "('". $subject ."' REGEXP '" . $pattern . "')";
	}


	/**
	 *   Convert (by PHP) a GM Timestamp date into a string date with PHP server TZ to insert into a date field.
	 *   Function to use to build INSERT, UPDATE or WHERE predica
	 *
	 *   @param	    int		$param      Date TMS to convert
	 *	 @param		mixed	$gm			'gmt'=Input informations are GMT values, 'tzserver'=Local to server TZ
	 *   @return	string      		Date in a string YYYY-MM-DD HH:MM:SS
	 */
	public function idate($param, $gm = 'tzserver')
	{
		// TODO $param should be gmt, so we should have default $gm to 'gmt' instead of default 'tzserver'
		return dol_print_date($param, "%Y-%m-%d %H:%M:%S", $gm);
	}

	/**
	 *	Return last error code
	 *
	 *	@return	    string	lasterrno
	 */
	public function lasterrno()
	{
		return $this->lasterrno;
	}

	/**
	 * Sanitize a string for SQL forging
	 *
	 * @param   string 	$stringtosanitize 	String to escape
	 * @param   int		$allowsimplequote 	1=Allow simple quotes in string. When string is used as a list of SQL string ('aa', 'bb', ...)
	 * @param	string	$allowsequals		1=Allow equals sign
	 * @return  string                      String escaped
	 */
	public function sanitize($stringtosanitize, $allowsimplequote = 0, $allowsequals = 0)
	{
		return preg_replace('/[^a-z0-9_\-\.,'.($allowsequals ? '=' : '').($allowsimplequote ? "\'" : '').']/i', '', $stringtosanitize);
	}

	/**
	 * Start transaction
	 *
	 * @param	string	$textinlog		Add a small text into log. '' by default.
	 * @return	int         			1 if transaction successfuly opened or already opened, 0 if error
	 */
	public function begin($textinlog = '')
	{
                /*moddrsi*/
                if (!$this->noTransaction && defined('BIMP_LIB') && BimpDebug::isActive()) {
                    $content = '<span class="info">BEGIN #' . ($this->transaction_opened + 1). '</span><br/><br/>';
                    BimpDebug::addDebug('sql', '', $content, array(
                        'foldable' => false
                    ));
                }
                /*fmoddrsi*/
                
		if (!$this->transaction_opened && !$this->noTransaction)
		{
			$ret = $this->query("BEGIN");
			if ($ret) {
				$this->transaction_opened++;
				dol_syslog("BEGIN Transaction".($textinlog ? ' '.$textinlog : ''), LOG_DEBUG);
				dol_syslog('', 0, 1);
			} else {
                            /*moddrsi*/
                            if (defined('BIMP_LIB') && BimpDebug::isActive()) {
                                $content = BimpRender::renderAlerts('Echec BEGIN - ' . $this->lasterror());
                                BimpDebug::addDebug('sql', '', $content, array(
                                    'foldable' => false
                                ));
                            }
                            /*fmoddrsi*/
                        }
			return $ret;
		} else {
			$this->transaction_opened++;
			dol_syslog('', 0, 1);
			return 1;
		}
	}

	/**
	 * Validate a database transaction
	 *
	 * @param	string	$log		Add more log to default log line
	 * @return	int         		1 if validation is OK or transaction level no started, 0 if ERROR
	 */
	public function commit($log = '')
	{
            dol_syslog('', 0, -1);
            /*moddrsi*/ 
//                
//		if ($this->transaction_opened <= 1) {
//			$ret = $this->query("COMMIT");
//			if ($ret) {
//				$this->transaction_opened = 0;
//				dol_syslog("COMMIT Transaction".($log ? ' '.$log : ''), LOG_DEBUG);
//				return 1;
//			} else {
//				return 0;
//			}
//		} else {
//			$this->transaction_opened--;
//			return 1;
//		}
                
            /*moddrsi*/
            if (!$this->noTransaction && defined('BIMP_LIB') && BimpDebug::isActive()) {
                $content = '<span class="success">COMMIT #' . ($this->transaction_opened). '</span><br/><br/>';
                BimpDebug::addDebug('sql', '', $content, array(
                    'foldable' => false
                ));
            }
            /*fmoddrsi*/
                
            if ($this->transaction_opened==1 && !$this->noTransaction) {
                if ($this->has_rollback) {
                    if (!defined('BIMP_LIB')) {
                        require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
                    }
                    BimpCore::addlog('Tentative de COMMIT SQL à la suite d\'un ROLLBACK', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore');
                    $this->rollback();
                    return 0;
                }
                        
                $ret=$this->query("COMMIT");
                        
                if (class_exists('BimpTools')) {
                    BimpTools::deloqueAll ();
                }
        
                if ($ret) {
                        $this->transaction_opened=0;
                        dol_syslog("COMMIT Transaction".($log?' '.$log:''),LOG_DEBUG);
                        return 1;
                } else {
                    BimpCore::addlog('COMMIT ERREUR', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', null, array(
                        'Dernière Erreur SQL' => $this->lasterror(),
                        'lasterror'           => $this->error(),
                        'lasterrno'           => $this->errno()
                    ));

                    if (defined('BIMP_LIB') && BimpDebug::isActive()) {
                        $content = BimpRender::renderAlerts('Echec COMMIT  - ' . $this->lasterror());
                        BimpDebug::addDebug('sql', '', $content, array(
                            'foldable' => false
                        ));
                    }
                    return 0;
                }
            } elseif($this->transaction_opened > 1) {
                $this->transaction_opened--;
                return 1;
            } else {
                // On doit considérer ça comme ok
                return 1;
            }
                /*fmoddrsi*/
	}

	/**
	 *	Cancel a transaction and go back to initial data values
	 *
	 * 	@param	string			$log		Add more log to default log line
	 * 	@return	resource|int         		1 if cancelation is ok or transaction not open, 0 if error
	 */
	public function rollback($log = '')
	{
		dol_syslog('', 0, -1);
                
                /*moddrsi*/ 
                
//		if ($this->transaction_opened <= 1) {
//			$ret = $this->query("ROLLBACK");
//			$this->transaction_opened = 0;
//			dol_syslog("ROLLBACK Transaction".($log ? ' '.$log : ''), LOG_DEBUG);
//			return $ret;
//		} else {
//			$this->transaction_opened--;
//			return 1;
//		}
                
                if (defined('BIMP_LIB') && BimpDebug::isActive()) {
                    $id_trans = $this->transaction_opened;
                    $content = '<span class="danger">ROLLBACK #' . $id_trans . '</span><br/><br/>';
                    BimpDebug::addDebug('sql', '', $content, array(
                        'foldable' => false
                    ));
                }


                if ($this->transaction_opened <= 1 && !$this->noTransaction) {
                    $ret = $this->query("ROLLBACK");
                    $this->transaction_opened = 0;
                    
                    if (class_exists('BimpTools')){
                        BimpTools::deloqueAll();
                    }
                    
                    $this->has_rollback = false;

                    if (defined('BIMP_LIB') && BimpDebug::isActive()) {
                        if ($ret <= 0) {
                            $content = BimpRender::renderAlerts('Echec ROLLBACK  - ' . $this->lasterror());
                            BimpDebug::addDebug('sql', '', $content, array(
                                'foldable' => false
                            ));
                        }
                    }
                    
                    dol_syslog("ROLLBACK Transaction" . ($log ? ' ' . $log : ''), LOG_DEBUG);
                    return $ret;
                } elseif ($this->noTransaction) {
                    // Pas besoin de loguer, de nombreux cas où c'est normal d'arriver là (Quand on veut contourner un comportement par défaut). 
//                    BimpCore::addlog('Tentative de ROLLBACK sur instance sans transactions', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore');
                    return 1;
                } else {
                    $this->transaction_opened--;
                    $this->has_rollback = true;
                    // Idem
//                    BimpCore::addLogs_debug_trace('Tentative de ROLLBACK sur transaction d\'id ' . ($this->transaction_opened + 1));
                    return 1;
                }
                
                /*fmoddrsi*/
	}

	/**
	 *	Define limits and offset of request
	 *
	 *	@param	int		$limit      Maximum number of lines returned (-1=conf->liste_limit, 0=no limit)
	 *	@param	int		$offset     Numero of line from where starting fetch
	 *	@return	string      		String with SQL syntax to add a limit and offset
	 */
	public function plimit($limit = 0, $offset = 0)
	{
		global $conf;
		if (empty($limit)) {
			return "";
		}
		if ($limit < 0) {
			$limit = $conf->liste_limit;
		}
		if ($offset > 0) {
			return " LIMIT ".((int) $offset).",".((int) $limit)." ";
		} else {
			return " LIMIT ".((int) $limit)." ";
		}
	}

	/**
	 *	Return version of database server into an array
	 *
	 *	@return	        array  		Version array
	 */
	public function getVersionArray()
	{
		return preg_split("/[\.,-]/", $this->getVersion());
	}

	/**
	 *	Return last request executed with query()
	 *
	 *	@return	string					Last query
	 */
	public function lastquery()
	{
		return $this->lastquery;
	}

	/**
	 * Define sort criteria of request
	 *
	 * @param	string		$sortfield		List of sort fields, separated by comma. Example: 't1.fielda,t2.fieldb'
	 * @param	string		$sortorder		Sort order, separated by comma. Example: 'ASC,DESC'. Note: If the quantity fo sortorder values is lower than sortfield, we used the last value for missing values.
	 * @return	string						String to provide syntax of a sort sql string
	 */
	public function order($sortfield = null, $sortorder = null)
	{
		if (!empty($sortfield)) {
			$oldsortorder = '';
			$return = '';
			$fields = explode(',', $sortfield);
			$orders = explode(',', $sortorder);
			$i = 0;
			foreach ($fields as $val) {
				if (!$return) {
					$return .= ' ORDER BY ';
				} else {
					$return .= ', ';
				}

				$return .= preg_replace('/[^0-9a-z_\.]/i', '', $val); // Add field

				$tmpsortorder = (empty($orders[$i]) ? '' : trim($orders[$i]));

				// Only ASC and DESC values are valid SQL
				if (strtoupper($tmpsortorder) === 'ASC') {
					$oldsortorder = 'ASC';
					$return .= ' ASC';
				} elseif (strtoupper($tmpsortorder) === 'DESC') {
					$oldsortorder = 'DESC';
					$return .= ' DESC';
				} else {
					$return .= ' '.($oldsortorder ? $oldsortorder : 'ASC');
				}

				$i++;
			}
			return $return;
		} else {
			return '';
		}
	}

	/**
	 *	Return last error label
	 *
	 *	@return	    string		Last error
	 */
	public function lasterror()
	{
		return $this->lasterror;
	}

	/**
	 *	Convert (by PHP) a PHP server TZ string date into a Timestamps date (GMT if gm=true)
	 * 	19700101020000 -> 3600 with server TZ = +1 and $gm='tzserver'
	 * 	19700101020000 -> 7200 whaterver is server TZ if $gm='gmt'
	 *
	 * 	@param	string				$string		Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 *	@param	mixed				$gm			'gmt'=Input informations are GMT values, 'tzserver'=Local to server TZ
	 *	@return	int|string						Date TMS or ''
	 */
	public function jdate($string, $gm = 'tzserver')
	{
		// TODO $string should be converted into a GMT timestamp, so param gm should be set to true by default instead of false
		if ($string == 0 || $string == "0000-00-00 00:00:00") {
			return '';
		}
		$string = preg_replace('/([^0-9])/i', '', $string);
		$tmp = $string.'000000';
		$date = dol_mktime((int) substr($tmp, 8, 2), (int) substr($tmp, 10, 2), (int) substr($tmp, 12, 2), (int) substr($tmp, 4, 2), (int) substr($tmp, 6, 2), (int) substr($tmp, 0, 4), $gm);
		return $date;
	}

	/**
	 *	Return last query in error
	 *
	 *	@return	    string	lastqueryerror
	 */
	public function lastqueryerror()
	{
		return $this->lastqueryerror;
	}

	/**
	 * Return first result from query as object
	 * Note : This method executes a given SQL query and retrieves the first row of results as an object. It should only be used with SELECT queries
	 * Dont add LIMIT to your query, it will be added by this method
	 *
	 * @param 	string 				$sql 	The sql query string
	 * @return 	bool|int|object    			False on failure, 0 on empty, object on success
	 */
	public function getRow($sql)
	{
		$sql .= ' LIMIT 1';

		$res = $this->query($sql);
		if ($res) {
			$obj = $this->fetch_object($res);
			if ($obj) {
				return $obj;
			} else {
				return 0;
			}
		}

		return false;
	}

	/**
	 * Return all results from query as an array of objects
	 * Note : This method executes a given SQL query and retrieves all row of results as an array of objects. It should only be used with SELECT queries
	 * be carefull with this method use it only with some limit of results to avoid performences loss.
	 *
	 * @param 	string 		$sql 		The sql query string
	 * @return 	bool|array				Result
	 * @deprecated
	 */
	public function getRows($sql)
	{
		$res = $this->query($sql);
		if ($res) {
			$results = array();
			if ($this->num_rows($res) > 0) {
				while ($obj = $this->fetch_object($res)) {
					$results[] = $obj;
				}
			}
			return $results;
		}

		return false;
	}
        
        
    // moddrsi : Ajouts
        
    public function commitAll()
    {
        if (!$this->noTransaction) {
            if ($this->transaction_opened > 0) {
                while ($this->transaction_opened > 1) {
                    if (defined('BIMP_LIB') && BimpDebug::isActive()) {
                        $content = '<span class="success">COMMIT #' . ($this->transaction_opened). '</span><br/><br/>';
                        BimpDebug::addDebug('sql', '', $content, array(
                            'foldable' => false
                        ));
                    }        
                    $this->transaction_opened--;
                }
                
                $this->commit();
            }
        }
        
        $this->transaction_opened = 0;
    }
    
    static function stopAll($msg = ''){
        $errors = array('Problème réseau, merci de relancer l\'opération ('.$msg.')');
        if (BimpTools::isSubmit('ajax')) {
            echo json_encode(array(
                'errors'           => $errors,
                'request_id'       => BimpTools::getValue('request_id', 0)
            ));
        }
        else{
            echo 'Oupppps   '.print_r($errors,1);
        }
        die();
        exit;
    }
}
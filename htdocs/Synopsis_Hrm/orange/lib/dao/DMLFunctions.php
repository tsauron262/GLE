<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

require_once ROOT_PATH . '/lib/dao/MySQLClass.php';
require_once ROOT_PATH . '/lib/confs/Conf.php';

class DMLFunctions {

	var $dbObject; // var to connection
	var $conf;

	/**
	 * Constructor for the DMLFunctions Class
	 * which takes the configuration variables
	 * from the conf.php Class and return the
	 * reference of conf object
	 */
	function DMLFunctions() {
		$this-> conf = new HrmConf();
		$this-> dbObject = new MySQLClass($this->conf);
	}

	/**
	 * Function ExecuteQuery will take in a SQL Query
	 * String as the Input Parameter and execute the
	 * SQLQuery Function
	 */
	function executeQuery($SQL) {

		$SQL = $this->_formatQuery($SQL);

		if ( $this -> dbObject -> dbConnect()) {
			$result = $this->dbObject->sqlQuery($SQL);
			return $result;
		}

		return false;
	}
	
	function _formatQuery($query) {
		
		if (preg_match('/\'AES_[ED][NE]CRYPT\(/', $query)) {
			
			$query = preg_replace(array("/^'AES_ENCRYPT\(/", "/\)'/"), array('AES_ENCRYPT(', ')'), $query);
			
		}
		
		return $query;
		
	}

}
?>

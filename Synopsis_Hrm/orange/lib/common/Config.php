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

require_once ROOT_PATH.'/lib/dao/DMLFunctions.php';
require_once ROOT_PATH.'/lib/dao/SQLQBuilder.php';

/**
 * This calss is to set and get values of 'hs_hr_config table'.
 * This table contains only two fileds `key` and `value`, both are in varchar(100).
 * You may add new keys to the class with the prefix 'KEY_NAME' and write
 * corresponding 'set' and 'get' methods. Both 'set' and 'get' methods have been
 * defined as 'static'.
 */

class Config {

    const DB_TABLE_CONFIG = "hs_hr_config";
    const DB_FIELD_KEY = "key";
    const DB_FIELD_VALUE = "value";
    const KEY_NAME_HSP_ACCRUED_LAST_UPDATED = "hsp_accrued_last_updated";
    const KEY_NAME_HSP_USED_LAST_UPDATED = "hsp_used_last_updated";
    const KEY_NAME_HSP_CURRENT_PLAN = "hsp_current_plan";
    const KEY_NAME_TIMESHEET_PERIOD_SET = "timesheet_period_set";
    const KEY_NAME_LEAVE_BROUGHT_FORWARD = "LeaveBroughtForward";

    /**
     * Sets the 'value' corresponding to 'key'
     * If the 'key' is already availabe, correponding 'value' would be updated.
     * If not, a new 'key', 'value' pair would be inserted.
     * @param string $key 'key' field corresponding to the value to be set
     * @param string $value 'value' that should be set
     */

    private static function _setValue($key, $value) {

		$updateFields[0] = "`".self::DB_FIELD_KEY."`";
		$updateFields[1] = "`".self::DB_FIELD_VALUE."`";
		$updateValues[0] = "'".$key."'";
		$updateValues[1] = "'".$value."'";

		$sqlBuilder = new SQLQBuilder();

		$sqlBuilder->table_name = self::DB_TABLE_CONFIG;
		$sqlBuilder->flg_insert = 'true';
		$sqlBuilder->arr_insert = $updateValues;
		$sqlBuilder->arr_insertfield = $updateFields;

		$query = $sqlBuilder->addNewRecordFeature2(true, true);

		$dbConnection = new DMLFunctions();

		$result = $dbConnection->executeQuery($query);

		if (!$result) {
		    throw new Exception("Value corresponding to $key was not updated");
		}

    }

    /**
     * Outputs the 'value' corresponding to 'key'
     * @param string $key 'key' field where corresponding 'value' is needed
     */

    private static function _selectValue($key) {

		$selectTable = "`".self::DB_TABLE_CONFIG."`";
		$selectFields[0] = "`".self::DB_FIELD_VALUE."`";
		$selectConditions[0] = "`".self::DB_FIELD_KEY."` = '". $key ."'";

		$sqlBuilder = new SQLQBuilder();

		$query = $sqlBuilder->simpleSelect($selectTable, $selectFields, $selectConditions);

		$dbConnection = new DMLFunctions();

		$result = $dbConnection->executeQuery($query);

		if ($dbConnection->dbObject->numberOfRows($result) != 1) {
		    throw new Exception("Value corresponding to $key could not be selected");
		}

		$resultArray = $dbConnection->dbObject->getArray($result);

		return $resultArray[0];

    }

    /**
     * Sets the 'value' field correponding to 'hsp_accrued_last_updated'
     * 'key' argument is optional.
     * If the 'key' is not already set, it can be set via the method.
     * @param string $value New date. Should be in yyyy-mm-dd format
     */

    public static function setHspAccruedLastUpdated($value, $key=null) {

    	if (!preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/i',$value)) {
    	    throw new Exception("Given date is not valid. Should be in yyyy-mm-dd format.");
    	}

    	if (isset($key)) {
    	    self::_setValue($key, $value);
    	} else {
    	    self::_setValue(self::KEY_NAME_HSP_ACCRUED_LAST_UPDATED, $value);
    	}

    }

    /**
     * Returns the 'value' field correponding to 'hsp_accrued_last_updated'
     * @return string 'HSP Accrued' last updated date
     */

    public static function getHspAccruedLastUpdated() {

    	return self::_selectValue(self::KEY_NAME_HSP_ACCRUED_LAST_UPDATED);

    }

    /**
     * Sets the 'value' field correponding to 'hsp_used_last_updated'
     * 'key' argument is optional.
     * If the 'key' is not already set, it can be set via the method.
     * @param string $value New date. Should be in yyyy-mm-dd format
     */

    public static function setHspUsedLastUpdated($value, $key=null) {

    	if (!preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/i',$value)) {
    	    throw new Exception("Given date is not valid. Should be in yyyy-mm-dd format.");
    	}

    	if (isset($key)) {
    	    self::_setValue($key, $value);
    	} else {
    	    self::_setValue(self::KEY_NAME_HSP_USED_LAST_UPDATED, $value);
    	}

    }

    /**
     * Returns the 'value' field correponding to 'hsp_used_last_updated'
     * @return string 'HSP Used' last updated date
     */

    public static function getHspUsedLastUpdated() {

    	return self::_selectValue(self::KEY_NAME_HSP_USED_LAST_UPDATED);

    }

    /**
     * Sets the 'value' field correponding to 'hsp_current_plan'
     * 'key' argument is optional.
     * If the 'key' is not already set, it can be set via the method.
     * @param string $value HSP value.
     */

    public static function setHspCurrentPlan($value, $key=null) {

    	if (isset($key)) {
    	    self::_setValue($key, $value);
    	} else {
    	    self::_setValue(self::KEY_NAME_HSP_CURRENT_PLAN, $value);
    	}

    }

    /**
     * Returns the 'value' field correponding to 'hsp_current_plan'
     * @return string 'HSP Selected' current HSP value
     */

    public static function getHspCurrentPlan() {
    	return self::_selectValue(self::KEY_NAME_HSP_CURRENT_PLAN);
    }

    public static function setHspBroughtForwadYear($value, $key) {
    	self::_setValue($key, $value);
    }

    public static function getHspBroughtForwadYear($key) {
    	try {
    		self::_selectValue($key);
    		return true;
    	} catch (Exception $e) {
    		return false;
    	}
    }

	/**
	 * Method to set Week Starting Day of Timesheets
	 */
	 public static function setTimePeriodSet($value) {
	 	if ($value != 'Yes' && $value != 'No') {
			throw new Exception("Given value for TimeSheetPeriodSet should be 'Yes' or 'No'");
	 	}

    	self::_setValue(self::KEY_NAME_TIMESHEET_PERIOD_SET, $value);
	 }

	 /**
	 * Method to get Week Starting Day of Timesheets
	 */
	 public static function getTimePeriodSet() {
	 	try {
			$value = self::_selectValue(self::KEY_NAME_TIMESHEET_PERIOD_SET);
			return ($value == 'Yes');
	 	} catch (Exception $e) {
	 	    return false;
	 	}
	 }

	 /**
	  * Sets LeaveBroughtForward for given year
	  * @param year $year
	  */
	 public static function setLeaveBroughtForward($year) {

	 	try {
	 	    self::_selectValue(self::KEY_NAME_LEAVE_BROUGHT_FORWARD.$year);
	 	    throw new Exception("LeaveBroughtForward has been already set");
	 	} catch (Exception $e) {}

		self::_setValue(self::KEY_NAME_LEAVE_BROUGHT_FORWARD.$year, "set");

	 }

	 /**
	  * Check whether LeaveBroughtForward has been set for given year
	  * @param year $year
	  * @return boolean Returns true if LeaveBroughtForward is set, false other wise
	  */
	 public static function getLeaveBroughtForward($year) {

	 	try {
			self::_selectValue(self::KEY_NAME_LEAVE_BROUGHT_FORWARD.$year);
	 	    return true;
	 	} catch (Exception $e) {
	 	    return false;
	 	}

	 }
}

?>

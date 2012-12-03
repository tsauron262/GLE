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
 *
 */

require_once ROOT_PATH . '/lib/models/leave/LeaveQuota.php';

class EXTRACTOR_LeaveQuota {

	private $parent_Leave;

	function __construct() {
		$this->parent_Leave = new LeaveQuota();
	}

	public function parseAddData($postArr) {

		$this->parent_Leave->setYear($postArr['year']);
		$this->parent_Leave->setEmployeeId($_SESSION['empID']);
		$this->parent_Leave->setLeaveTypeId($postArr['sltLeaveType']);
		$this->parent_Leave->setLeaveDate($postArr['txtLeaveDate']);
		$this->parent_Leave->setLeaveLength($postArr['sltLeaveLength']);
		$this->parent_Leave->setLeaveComments($postArr['txtComments']);

		return $this->parent_Leave;
	}


	/**
	 * Pares edit data in the UI form
	 *
	 * @param mixed $postArr
	 * @return Leave[]
	 */
	public function parseEditData($postArr) {

		$objLeave = null;

		for ($i=0; $i < count($postArr['txtLeaveTypeId']); $i++) {
			$tmpObj = new LeaveQuota();
			$tmpObj->setYear($postArr['year']);
			$tmpObj->setEmployeeId($postArr['txtEmployeeId'][$i]);
			$tmpObj->setLeaveTypeId($postArr['txtLeaveTypeId'][$i]);
			$tmpObj->setNoOfDaysAllotted($postArr['txtLeaveEntitled'][$i]);
			$objLeave[] = $tmpObj;
		}

		return $objLeave;
	}

	/**
	 * Pares delete data in the UI form
	 *
	 * @param mixed $postArr
	 * @return Leave[]
	 */
	public function parseDeleteData($postArr) {
		$objLeave = null;

		for ($i=0; $i < count($postArr['cmbStatus']); $i++) {
			if ($postArr['cmbStatus'][$i] == 0) {
				$tmpObj = new Leave();
				$tmpObj->setLeaveId($postArr['id'][$i]);

				$objLeave[] = $tmpObj;
			}
		}

		return $objLeave;
	}

}
?>
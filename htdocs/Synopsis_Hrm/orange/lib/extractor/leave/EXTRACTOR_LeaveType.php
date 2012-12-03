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
require_once ROOT_PATH . '/lib/models/leave/LeaveType.php';

class EXTRACTOR_LeaveType {

	private $parent_LeaveType;

	function __construct() {
		$this->parent_LeaveType = new LeaveType();
	}


	public function parseLeaveType($postArr) {

		$this->parent_LeaveType->setLeaveTypeName(CommonFunctions::escapeHtml($postArr['txtLeaveTypeName']));

		return $this->parent_LeaveType;
	}

	public function parseEditData($postArr) {

		if (!isset($postArr['txtLeaveTypeName'])) {
			return null;
		}

		$objLeave = null;

		for ($i=0; $i < count($postArr['txtLeaveTypeName']); $i++) {
			$tmpObj = new LeaveType();
			$tmpObj->setLeaveTypeId($postArr['id'][$i]);
			$tmpObj->setLeaveTypeName($postArr['txtLeaveTypeName'][$i]);

			$objLeave[] = $tmpObj;
		}

		return $objLeave;

		/*$this->parent_LeaveType->setLeaveTypeId($postArr['id']);
		$this->parent_LeaveType->setLeaveTypeName($postArr['txtLeaveTypeName']);
		return $this->parent_LeaveType;*/
	}

	public function parseDeleteData($postArr) {

		$objLeave = null;

		for ($i=0; $i < count($postArr['chkLeaveTypeID']); $i++) {
			$tmpObj = new LeaveType();
			$tmpObj->setLeaveTypeId($postArr['chkLeaveTypeID'][$i]);

			$objLeave[] = $tmpObj;
		}
		return $objLeave;


	}
}
?>
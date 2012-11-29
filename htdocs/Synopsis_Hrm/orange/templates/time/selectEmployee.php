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
$role = $records[0];
$employees = $records[1];
$pendingTimesheets = $records[2];
$pending = $records[3];

?>
<script type="text/javascript">
<!--
var initialAction = "<?php echo $_SERVER['PHP_SELF']; ?>?timecode=Time&action=";

function returnEmpDetail(){
		var popup=window.open('../../templates/hrfunct/emppop.php?reqcode=REP','Employees','height=450,width=400');
        if(!popup.opener) popup.opener=self;
		popup.focus();
}

function view() {
	empIdObj = document.getElementById("txtRepEmpID");
	if ((empIdObj.value == 0) || (empIdObj.value == '')) {
		alert('<?php echo $lang_Error_PleaseSelectAnEmployee; ?>');
		return false;
	}
	frmObj = document.getElementById("frmTimesheet");

	frmObj.action= initialAction+'View_Timesheet';
	frmObj.submit();
}

function viewTimesheet(id) {
	frmObj = document.getElementById("frmTimesheet");

	frmObj.action= initialAction+'View_Timesheet&id='+id;
	frmObj.submit();
}
-->
</script>
<h2><?php echo $lang_Time_Select_Employee_Title; ?>
	<hr>
</h2>
<?php if (isset($_GET['message'])) {

		$expString  = $_GET['message'];
		$col_def = CommonFunctions::getCssClassForMessage($expString);
		$expString = 'lang_Time_Errors_' . $expString;
?>
		<font class="<?php echo $col_def?>" size="-1" face="Verdana, Arial, Helvetica, sans-serif">
<?php echo $$expString; ?>
		</font>
<?php }	?>
<form name="frmEmp" id="frmTimesheet" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?timecode=Time&action=">
<table border="0" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th class="tableTopLeft"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
			<th class="tableTopRight"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="tableMiddleLeft"></td>
			<td ><?php echo $lang_Leave_Common_EmployeeName; ?></td>
			<td></td>
		<?php if ($role == authorize::AUTHORIZE_ROLE_ADMIN) { ?>
			<td ><input type="text" name="cmbRepEmpID" id="cmbRepEmpID" disabled />
				<input type="hidden" name="txtRepEmpID" id="txtRepEmpID" />
				<input type="button" value="..." onclick="returnEmpDetail();" />
			</td>
		<?php } else if ($role == authorize::AUTHORIZE_ROLE_SUPERVISOR) { ?>
			<td >
				<select name="txtRepEmpID" id="txtRepEmpID">
					<option value="-1">-<?php echo $lang_Leave_Common_Select;?>-</option>
					<?php if (is_array($employees)) {
		   					foreach ($employees as $employee) {
		  			?>
		 		  	<option value="<?php echo $employee[0] ?>"><?php echo $employee[1]; ?></option>
		  			<?php 	}
		   				} ?>
				</select>
			</td>
		<?php } ?>
			<td></td>
			<td><input type="image" name="btnView" onclick="view(); return false;" src="../../themes/beyondT/icons/view.gif" onmouseover="this.src='../../themes/beyondT/icons/view_o.gif';" onmouseout="this.src='../../themes/beyondT/icons/view.gif';" /></td>
			<td></td>
			<td class="tableMiddleRight"></td>
		</tr>
	</tbody>
	<tfoot>
	  	<tr>
			<td class="tableBottomLeft"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomRight"></td>
		</tr>
  	</tfoot>
</table>
<?php
	if ($pending) {
?>
<h3><?php echo $lang_Time_Select_Employee_SubmittedTimesheetsPendingSupervisorApproval; ?></h3>
<table border="0" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th class="tableTopLeft"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
			<th class="tableTopRight"></th>
		</tr>
		<tr>
			<th class="tableMiddleLeft"></th>
			<th width="100px" class="tableMiddleMiddle"><?php echo $lang_Leave_Common_EmployeeName; ?></th>
			<th class="tableMiddleMiddle"></th>
			<th width="150px" class="tableMiddleMiddle"><?php echo $lang_Time_Select_Employee_TimesheetPeriod; ?></th>
			<th class="tableMiddleMiddle"></th>
			<th class="tableMiddleMiddle"></th>
			<th class="tableMiddleRight"></th>
		</tr>
	</thead>
	<tbody>
		<?php if (is_array($employees)) {
		   		foreach ($employees as $employee) {
		   			if (is_array($pendingTimesheets[$employee[0]])) {
		   				foreach ($pendingTimesheets[$employee[0]] as $timesheet) {
		?>
		<tr>
			<td class="tableMiddleLeft"></td>
			<td><?php echo $employee[1];?></td>
			<td>&nbsp;</td>
			<td><?php echo preg_replace(array('/#date/'), array(LocaleUtil::getInstance()->formatDate($timesheet->getStartDate())), $lang_Time_Select_Employee_WeekStartingDate); ?></td>
			<td>
				<input type="image" name="btnView" alt="View"
					   onclick="viewTimesheet(<?php echo $timesheet->getTimesheetId(); ?>); return false;"
					   src="../../themes/beyondT/icons/view.gif"
					   onmouseover="this.src='../../themes/beyondT/icons/view_o.gif';"
					   onmouseout="this.src='../../themes/beyondT/icons/view.gif';" />
			</td>
			<td>&nbsp;</td>
			<td class="tableMiddleRight"></td>
		</tr>
		<?php
		   				}
		   			}
		   		}
			}
		?>
	</tbody>
	<tfoot>
	  	<tr>
			<td class="tableBottomLeft"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomRight"></td>
		</tr>
  	</tfoot>
</table>
<?php
}
?>
</form>
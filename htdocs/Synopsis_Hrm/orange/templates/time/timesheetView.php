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


require_once ROOT_PATH . '/lib/models/eimadmin/Customer.php';
require_once ROOT_PATH . '/lib/models/eimadmin/Projects.php';


$timeExpenses=$records[0];
$timesheet=$records[1];
$timesheetSubmissionPeriod=$records[2];
$dailySum=$records[3];
$employee=$records[4];
$self=$records[5];
$next=$records[6];
$prev=$records[7];
$role=$records[8];
$activitySum=$records[9];
$totalTime=$records[10];

if ($self) {
	$next=true;
	$prev=true;
}

$status=$timesheet->getStatus();

switch ($status) {
	case Timesheet::TIMESHEET_STATUS_NOT_SUBMITTED : $statusStr = $lang_Time_Timesheet_Status_NotSubmitted;
												break;
	case Timesheet::TIMESHEET_STATUS_SUBMITTED : $statusStr = $lang_Time_Timesheet_Status_Submitted;
												break;
	case Timesheet::TIMESHEET_STATUS_APPROVED : $statusStr = $lang_Time_Timesheet_Status_Approved;
												break;
	case Timesheet::TIMESHEET_STATUS_REJECTED : $statusStr = $lang_Time_Timesheet_Status_Rejected;
												break;
}

$startDate = strtotime($timesheet->getStartDate());
$endDate = strtotime($timesheet->getEndDate());

?>
<script type="text/javascript">
<!--
var prev = new Array();
var next = new Array();

var initialAction = "<?php echo $_SERVER['PHP_SELF']; ?>?timecode=Time&action=";

next.startDate = '<?php echo LocaleUtil::getInstance()->formatDate(date('Y-m-d', strtotime("+7 day", $startDate))); ?>';
next.endDate = '<?php echo LocaleUtil::getInstance()->formatDate(date('Y-m-d',strtotime("+7 day", $endDate))); ?>';

prev.startDate = '<?php echo LocaleUtil::getInstance()->formatDate(date('Y-m-d', strtotime("-7 day", $startDate))); ?>';
prev.endDate = '<?php echo LocaleUtil::getInstance()->formatDate(date('Y-m-d', strtotime("-7 day", $endDate))); ?>';

function $(id) {
	return document.getElementById(id);
}

function actionNav(nav) {

<?php if ($self) { ?>
	switch (nav) {
		case 1  : $("txtStartDate").value = prev.startDate;

				  $("txtEndDate").value = prev.endDate;
				  break;
		case -1 : $("txtStartDate").value = next.startDate;
				  $("txtEndDate").value = next.endDate;
				  break;
	}
	$("frmTimesheet").action= initialAction+"View_Timesheet";
	$("txtTimesheetId").disabled= true;
<?php } else { ?>
	switch (nav) {
		case 1  : $("frmTimesheet").action= initialAction+"Fetch_Prev_Timesheet";
				  break;
		case -1 : $("frmTimesheet").action= initialAction+"Fetch_Next_Timesheet";
				  break;
	}
<?php } ?>

	$("frmTimesheet").submit();

}

function actionSubmit() {
	$("frmTimesheet").action= initialAction+"Submit_Timesheet";
	$("frmTimesheet").submit();
}

function actionCancel() {
	$("frmTimesheet").action= initialAction+"Cancel_Timesheet";
	$("frmTimesheet").submit();
}

function actionEdit() {
	window.location=initialAction+"View_Edit_Timesheet&id=<?php echo $timesheet->getTimesheetId(); ?>&return=View_Timesheet";
}

function actionReject() {
	if ($('txtComment').value == '') {
		alert('<?php echo $lang_Time_Errors_PleaseAddAComment; ?>');
		$('txtComment').focus();
		return false;
	}
	$("frmTimesheet").action= initialAction+"Reject_Timesheet";
	$("frmTimesheet").submit();
}

function actionApprove() {
	if ($('txtComment').value == '') {
		alert('<?php echo $lang_Time_Errors_PleaseAddAComment; ?>');
		$('txtComment').focus();
		return false;
	}
	$("frmTimesheet").action= initialAction+"Approve_Timesheet";
	$("frmTimesheet").submit();
}

function actionDetails() {
	window.location=initialAction+"View_Detail_Timesheet&id=<?php echo $timesheet->getTimesheetId(); ?>";
}
-->
</script>

<style type="text/css">
td {
	vertical-align: top;
	padding: 5px;
	text-align:center;
}
</style>

<h2>
	<?php if ($prev) { ?>
	<input src="../../themes/beyondT/icons/resultset_previous.png"
			onclick="actionNav(1); return false;"
			name="btnPrev" id="btnPrev" type="image"/>
		<?php
			}
				$headingStr = $lang_Time_Timesheet_TimesheetNameForViewTitle;
				if ($self) {
					$headingStr = $lang_Time_Timesheet_TimesheetForViewTitle;
				}
				echo preg_replace(array('/#periodName/', '/#startDate/', '/#name/'),
							array($timesheetSubmissionPeriod->getName(), LocaleUtil::getInstance()->formatDate($timesheet->getStartDate()), "{$employee[2]} {$employee[1]}"),
							$headingStr);
		if (($next)  && !($timesheet->getEndDate() >= date('Y-m-d'))) {
	?>
	<input src="../../themes/beyondT/icons/resultset_next.png"
			onclick="actionNav(-1); return false;"
			name="btnNext" id="btnNext" type="image"/>
	<?php } ?>
	<hr/>
</h2>

<h3><?php echo preg_replace(array('/#status/'),
							array($statusStr),
							$lang_Time_Timesheet_Status);
		if (($timesheet->getComment() != null) &&
		   (($status == Timesheet::TIMESHEET_STATUS_APPROVED) || ($status == Timesheet::TIMESHEET_STATUS_REJECTED))) {
			echo " - {$timesheet->getComment()}";
		}?></h3>

<?php if (isset($_GET['message'])) {

		$expString  = $_GET['message'];
		$col_def = CommonFunctions::getCssClassForMessage($expString);
		$expString = 'lang_Time_Errors_' . $expString;
?>
		<font class="<?php echo $col_def?>" size="-1" face="Verdana, Arial, Helvetica, sans-serif">
<?php echo $$expString; ?>
		</font>
<?php }	?>
<table border="0" cellpadding="5" cellspacing="0">
	<thead>
		<tr>
			<th class="tableTopLeft"></th>
	    	<th class="tableTopMiddle"></th>
	    	<th class="tableTopMiddle"></th>
	    <?php for ($i=$startDate; $i<=$endDate; $i=strtotime("+1 day", $i)) { ?>
	    	<th class="tableTopMiddle"></th>
	    <?php } ?>
	    	<th class="tableTopMiddle"></th>
			<th class="tableTopRight"></th>
		</tr>
		<tr>
			<th class="tableMiddleLeft"></th>
			<th width="100px" class="tableMiddleMiddle"><?php echo $lang_Time_Timesheet_Project; ?></th>
			<th width="100px" class="tableMiddleMiddle"><?php echo $lang_Time_Timesheet_Activity; ?></th>
		<?php for ($i=$startDate; $i<=$endDate; $i=strtotime("+1 day", $i)) { ?>
	    	<th width="80px" class="tableMiddleMiddle"><?php echo date('l ' . LocaleUtil::getInstance()->getDateFormat(), $i); ?></th>
	    <?php } ?>
	    	<th width="80px" class="tableMiddleMiddle"><?php echo $lang_Time_Timesheet_Total; ?></th>
			<th class="tableMiddleRight"></th>
		</tr>
	</thead>
	<tbody >
		<?php
		if (isset($timeExpenses) && is_array($timeExpenses)) {
			$customerObj = new Customer();
			$projectObj = new Projects();
			$projectActivityObj = new ProjectActivity();

			foreach ($timeExpenses as $project=>$timeExpense) {
				$projectDet = $projectObj->fetchProject($project);
				$customer = $customerObj->fetchCustomer($projectDet->getCustomerId(), true);

				foreach ($timeExpense as $activityId=>$activityExpense) {
					$projectActivity = $projectActivityObj->getActivity($activityId);
			?>
			<tr>
				<td class="tableMiddleLeft"></td>
				<td ><?php echo "{$customer->getCustomerName()} - {$projectDet->getProjectName()}"; ?></td>
				<td ><?php echo $projectActivity->getName(); ?></td>
			<?php 	for ($i=$startDate; $i<=$endDate; $i=strtotime("+1 day", $i)) {
						if (!isset($activityExpense[$i])) {
							$activityExpense[$i]=0;
						}
			?>
	    		<td ><?php echo round($activityExpense[$i]/36)/100; ?></td>
	    	<?php } ?>
	    		<th ><?php echo round($activitySum[$project][$activityId]/36)/100; ?></th>
				<td class="tableMiddleRight"></td>
			</tr>
		<?php	}
			} ?>
			<tr>
				<th class="tableMiddleLeft"></th>
				<th ><?php echo $lang_Time_Timesheet_Total; ?></th>
				<th ></th>
			<?php for ($i=$startDate; $i<=$endDate; $i=strtotime("+1 day", $i)) {
					if (!isset($dailySum[$i])) {
						$dailySum[$i]=0;
					}
			?>
		    	<th ><?php echo round($dailySum[$i]/36)/100; ?></th>
		    <?php } ?>
		    	<th ><?php echo round($totalTime/36)/100; ?></th>
				<th class="tableMiddleRight"></th>
			</tr>
		<?php } else { ?>
			<tr>
				<td class="tableMiddleLeft"></td>
				<td ><?php echo $lang_Error_NoRecordsFound; ?></td>
				<td ></td>
			<?php for ($i=$startDate; $i<=$endDate; $i=strtotime("+1 day", $i)) { ?>
	    		<td ></td>
	    	<?php } ?>
	    		<td ></td>
				<td class="tableMiddleRight"></td>
			</tr>
		<?php }?>
	</tbody>
	<tfoot>
	  	<tr>
			<td class="tableBottomLeft"></td>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomMiddle"></td>
		<?php for ($i=$startDate; $i<=$endDate; $i=strtotime("+1 day", $i)) { ?>
			<td class="tableBottomMiddle"></td>
		<?php } ?>
			<td class="tableBottomMiddle"></td>
			<td class="tableBottomRight"></td>
		</tr>
  	</tfoot>
</table>
<p id="controls">
<form id="frmTimesheet" name="frmTimesheet" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?timecode=Time&action=">

<input type="hidden" id="txtTimesheetId" name="txtTimesheetId" value="<?php echo $timesheet->getTimesheetId(); ?>" />
<input type="hidden" name="txtEmployeeId" value="<?php echo $timesheet->getEmployeeId(); ?>" />

<input type="hidden" id="txtTimesheetPeriodId" name="txtTimesheetPeriodId" value="<?php echo $timesheet->getTimesheetPeriodId(); ?>" />
<input type="hidden" id="txtStartDate" name="txtStartDate" value="<?php echo $timesheet->getStartDate(); ?>" />
<input type="hidden" id="txtEndDate" name="txtEndDate" value="<?php echo $timesheet->getEndDate(); ?>" />
<div>
<?php if ($timesheet->getStatus() != Timesheet::TIMESHEET_STATUS_APPROVED) { ?>
	<input src="../../themes/beyondT/pictures/btn_edit.gif"
			onclick="actionEdit(); return false;"
			onmouseover="this.src='../../themes/beyondT/pictures/btn_edit_02.gif';"
			onmouseout="this.src='../../themes/beyondT/pictures/btn_edit.gif';"
			name="btnEdit" id="btnEdit" height="20" type="image" width="65"/>
	<?php if (($timesheet->getStatus() == Timesheet::TIMESHEET_STATUS_NOT_SUBMITTED) || ($timesheet->getStatus() == Timesheet::TIMESHEET_STATUS_REJECTED)) { ?>
	<input src="../../themes/beyondT/icons/submit.gif"
			onclick="actionSubmit(); return false;"
			onmouseover="this.src='../../themes/beyondT/icons/submit_o.gif';"
			onmouseout="this.src='../../themes/beyondT/icons/submit.gif';"
			name="btnSubmit" id="btnSubmit" height="20" type="image" width="65"/>
	<?php } ?>
	<?php if ($self && ($timesheet->getStatus() == Timesheet::TIMESHEET_STATUS_SUBMITTED)) { ?>
	<input src="../../themes/beyondT/icons/cancel.gif"
			onclick="actionCancel(); return false;"
			onmouseover="this.src='../../themes/beyondT/icons/cancel_o.gif';"
			onmouseout="this.src='../../themes/beyondT/icons/cancel.gif';"
			name="btnCancel" id="btnCancel" height="20" type="image" width="65"/>
	<?php }
	}
	?>
	<input src="../../themes/beyondT/icons/details.gif"
			onclick="actionDetails(); return false;"
			onmouseover="this.src='../../themes/beyondT/icons/details_o.gif';"
			onmouseout="this.src='../../themes/beyondT/icons/details.gif';"
			name="btnEdit" id="btnEdit" type="image" alt="Details"
			height="20" width="65"/>

	<?php if ($role && (($timesheet->getStatus() == Timesheet::TIMESHEET_STATUS_APPROVED) || ($timesheet->getStatus() == Timesheet::TIMESHEET_STATUS_REJECTED))) { ?>
	<input src="../../themes/beyondT/icons/reset.gif"
			onclick="actionSubmit(); return false;"
			onmouseover="this.src='../../themes/beyondT/icons/reset_o.gif';"
			onmouseout="this.src='../../themes/beyondT/icons/reset.gif';"
			name="btnReset" id="btnReset" height="20" type="image" width="65"/>
	<?php } ?>

</div>
<?php if ($timesheet->getStatus() != Timesheet::TIMESHEET_STATUS_APPROVED) { ?>
	<?php if ($role && ($timesheet->getStatus() == Timesheet::TIMESHEET_STATUS_SUBMITTED)) { ?>
<div>
	<label><?php echo $lang_Leave_Common_Comment; ?> <input name="txtComment" id="txtComment" size="75" /></label>
	<br/>
	<input src="../../themes/beyondT/icons/approve.gif"
			onmouseover="this.src='../../themes/beyondT/icons/approve_o.gif';"
			onmouseout="this.src='../../themes/beyondT/icons/approve.gif';"
			onclick="actionApprove(); return false;"
			name="btnApprove" id="btnApprove"
			height="20" width="65" type="image"/>
	<input src="../../themes/beyondT/icons/reject.gif"
			onmouseover="this.src='../../themes/beyondT/icons/reject_o.gif';"
			onmouseout="this.src='../../themes/beyondT/icons/reject.gif';"
			onclick="actionReject(); return false;"
			name="btnReject" id="btnReject"
			height="20" width="65" type="image"/>
</div>
	<?php } ?>
<?php } ?>
</form>
</p>

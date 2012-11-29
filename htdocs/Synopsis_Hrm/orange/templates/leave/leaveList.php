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

 if (isset($modifier[1])) {
    $dispYear = $modifier[1];
 }

 $modifier = $modifier[0];

 if (isset($modifier) && ($modifier == "Taken")) {

    $empInfo = $records[count($records)-1][0];
    $employeeName = $empInfo[2].' '.$empInfo[1];

    array_pop($records);

    $records = $records[0];
 }

if ($modifier === "SUP") {
    $employeeName = $records[0]->getEmployeeName();
    $lang_Title = preg_replace('/#employeeName/', $employeeName, $lang_Leave_Leave_Requestlist_Title1);
} else if ($modifier === "ADMIN") {
    $employeeName = $records[0]->getEmployeeName();
    $lang_Title = preg_replace('/#employeeName/', $employeeName, $lang_Leave_Leave_Requestlist_Title2);
} else if ($modifier === "Taken") {
 $lang_Title = preg_replace(array('/#employeeName/', '/#dispYear/'), array($employeeName, $dispYear) , $lang_Leave_Leave_list_Title2);
} else if ($modifier === "MY") {
    $lang_Title = $lang_Leave_Leave_list_TitleMyLeaveList;
} else {
 $lang_Title = $lang_Leave_Leave_list_Title3;
}

 if ($modifier === "SUP") {
    $action = "Leave_ChangeStatus";
    $backLink = "Leave_FetchLeaveSupervisor";
 } else if ($modifier === "ADMIN") {
    $action = "Leave_ChangeStatus";
    $backLink = "Leave_FetchLeaveAdmin";
 } else {
    $action = "Leave_CancelLeave";
    $backLink = "Leave_FetchLeaveEmployee";
 }

?>
<h2><?php echo $lang_Title?><hr/></h2>
<?php if (isset($_GET['message']) && $_GET['message'] != 'xx') {

    $expString  = $_GET['message'];
    $expString = explode ("_",$expString);
    $length = count($expString);

    $col_def=strtolower($expString[$length-1]);

    $expString='lang_Leave_'.$_GET['message'];
    if (isset($$expString)) {
?>
    <font class="<?php echo $col_def?>" size="-1" face="Verdana, Arial, Helvetica, sans-serif">
<?php echo $$expString; ?>
    </font>
<?php
    }
}
?>
<script language="javascript">
    function goBack () {
        <?php if ($modifier == "ADMIN") { ?>
            window.location = "?leavecode=Leave&action=Leave_FetchLeaveAdmin";
        <?php
        } else if ($modifier == "Taken") {
        ?>
            history.back();
        <?php } else { ?>
            window.location = "?leavecode=Leave&action=<?php echo $backLink; ?>";
        <?php } ?>
    }
</script>
<!--
<p class="navigation">
        <input type="image" title="Back" onMouseOut="this.src='../../themes/beyondT/pictures/btn_back.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_back_02.gif';"  src="../../themes/beyondT/pictures/btn_back.gif" onClick="goBack(); return false;">
</p>
-->
<?php
    if (!is_array($records)) {
?>
    <h5><?php echo $lang_Error_NoRecordsFound; ?></h5>
<?php
    } else {
?>
<form id="frmCancelLeave" name="frmCancelLeave" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?leavecode=Leave&action=<?php echo $action; ?>">

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
        <th width="155px" class="tableMiddleMiddle"><?php echo $lang_Leave_Common_Date;?></th>
        <th width="90px" class="tableMiddleMiddle"><?php echo $lang_Leave_Common_LeaveType;?></th>
        <th width="110px" class="tableMiddleMiddle"><?php echo $lang_Leave_Common_Status;?></th>
        <th width="130px" class="tableMiddleMiddle"><?php echo $lang_Leave_Duration;?></th>
        <th width="150px" class="tableMiddleMiddle"><?php echo $lang_Leave_Common_Comments;?></th>
        <th class="tableMiddleRight"></th>
    </tr>
  </thead>
  <tbody>
<?php
    $j = 0;
    if (is_array($records)) {

        foreach ($records as $record) {
            if(!($j%2)) {
                $cssClass = 'odd';
            } else {
                $cssClass = 'even';
            }
            $j++;

?>
    <input type="hidden" name="txtLeaveRequestId[]" id="txtLeaveRequestId[]" value="<?php echo $record->getLeaveRequestId(); ?>" />
    <input type="hidden" name="txtEmployeeName[]" id="txtEmployeeName[]" value="<?php echo $record->getEmployeeName(); ?>" />
    <input type="hidden" name="txtLeaveDate[]" id="txtLeaveDate[]" value="<?php echo $record->getLeaveDate();; ?>" />
    <input type="hidden" name="txtLeaveTypeName[]" id="txtLeaveTypeName[]" value="<?php echo $record->getLeaveTypeName(); ?>" />
    <input type="hidden" name="sltLeaveLength[]" id="sltLeaveLength[]" value="<?php echo $record->getLeaveLengthHours(); ?>" />
  <tr>
    <td class="tableMiddleLeft"></td>
    <td class="<?php echo $cssClass; ?>"><?php echo LocaleUtil::getInstance()->formatDate($record->getLeaveDate()); ?></td>
    <td class="<?php echo $cssClass; ?>"><?php echo $record->getLeaveTypeName(); ?></td>
    <td class="<?php echo $cssClass; ?>"><?php
            $statusArr = array($record->statusLeaveRejected => $lang_Leave_Common_Rejected, $record->statusLeaveCancelled => $lang_Leave_Common_Cancelled, $record->statusLeavePendingApproval => $lang_Leave_Common_PendingApproval, $record->statusLeaveApproved => $lang_Leave_Common_Approved, $record->statusLeaveTaken=> $lang_Leave_Common_Taken);
            $suprevisorRespArr = array($record->statusLeaveRejected => $lang_Leave_Common_Rejected, $record->statusLeaveApproved => $lang_Leave_Common_Approved, $record->statusLeaveCancelled => $lang_Leave_Common_Cancelled);
            $employeeRespArr = array($record->statusLeaveCancelled => $lang_Leave_Common_Cancelled);

            if ($modifier === "MY") {
                $possibleStatusesArr = $employeeRespArr;
            } else if ($modifier == "SUP" || $modifier == "ADMIN") {
                $possibleStatusesArr = $suprevisorRespArr;

                if ($record->getLeaveStatus() == Leave::LEAVE_STATUS_LEAVE_TAKEN) {
                    $possibleStatusesArr = array(Leave::LEAVE_STATUS_LEAVE_CANCELLED => $lang_Leave_Common_Cancelled);
                }
            }

            if (($record->getLeaveStatus() == $record->statusLeavePendingApproval) || ($record->getLeaveStatus() ==  $record->statusLeaveApproved) || (($record->getLeaveStatus() ==  $record->statusLeaveRejected) && ($modifier == "SUP" || $modifier == "ADMIN")) ||
                (($record->getLeaveStatus() ==  Leave::LEAVE_STATUS_LEAVE_TAKEN) && ($modifier == "ADMIN"))) {
        ?>
                <input type="hidden" name="id[]" value="<?php echo $record->getLeaveId(); ?>" />
            <?php if (($record->getLeaveLengthHours() != null) && ($record->getLeaveLengthHours() != 0)) { ?>
                <select name="cmbStatus[]">
                    <option value="<?php echo $record->getLeaveStatus();?>" selected="selected" ><?php echo $statusArr[$record->getLeaveStatus()]; ?></option>
                    <?php foreach($possibleStatusesArr as $key => $value) {
                                if ($key != $record->getLeaveStatus()) {
                    ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php       }
                          }
                    ?>
                </select>
            <?php } else {
                        $holiday = Holidays::getHolidayForDate($record->getLeaveDate());
                        if (!empty($holiday) && is_a($holiday, 'Holidays')) {
                            echo $holiday->getDescription();
                        } else {
                            echo $lang_Leave_Closed;
                        }
            ?>
                <input type="hidden" name="cmbStatus[]" value="<?php echo $record->getLeaveStatus(); ?>" />
            <?php }?>
        <?php
            } else {
                if (Weekends::isWeekend($record->getLeaveDate())) {
                    echo $lang_Leave_Closed;
                } else {
                    echo $statusArr[$record->getLeaveStatus()];
                }
                ?>
                <input type="hidden" name="cmbStatus[]" value="<?php echo $record->getLeaveStatus(); ?>" />
                <input type="hidden" name="id[]" value="<?php echo $record->getLeaveId(); ?>" />
        <?php } ?>
    </td>
    <td class="<?php echo $cssClass; ?>"><?php
            echo (($record->getLeaveLengthHours() == null) || ($record->getLeaveLengthHours() == 0))?"----":$record->getLeaveLengthHours();
    ?></td>
    <td class="<?php echo $cssClass; ?>">
        <?php if (($record->getLeaveStatus() == Leave::LEAVE_STATUS_LEAVE_PENDING_APPROVAL) || ($record->getLeaveStatus() ==  Leave::LEAVE_STATUS_LEAVE_APPROVED) ||
        (($record->getLeaveStatus() ==  Leave::LEAVE_STATUS_LEAVE_REJECTED) && ($modifier == "SUP" || $modifier == "ADMIN")) ||
        (($record->getLeaveStatus() ==  Leave::LEAVE_STATUS_LEAVE_TAKEN) && ($modifier == "ADMIN"))) { ?>


        <input type="text" name="txtComment[]" value="<?php echo $record->getLeaveComments(); ?>" />
        <input type="hidden" name="txtEmployeeId[]" value="<?php echo $record->getEmployeeId(); ?>" />
        <?php } else if (($modifier == "MY") || ($modifier == "Taken")) {
            echo $record->getLeaveComments(); ?>
        <input type="hidden" name="txtEmployeeId[]" value="<?php echo $record->getEmployeeId(); ?>" />
        <input type="hidden" name="txtComment[]" value="<?php echo $record->getLeaveComments(); ?>" />
        <?php } else {
            echo $record->getLeaveComments();
        ?>
            <input type="hidden" name="txtEmployeeId[]" value="<?php echo $record->getEmployeeId(); ?>" />
            <input type="hidden" name="txtComment[]" value="<?php echo $record->getLeaveComments(); ?>" />
        <?php } ?>
    </td>
    <td class="tableMiddleRight"></td>
  </tr>

<?php
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
<?php     if ($modifier !== "Taken") { ?>
<p id="controls">
<input type="image" name="Save" class="save" src="../../themes/beyondT/pictures/btn_save.gif"/>
</p>
</form>
<?php   }
    } ?>

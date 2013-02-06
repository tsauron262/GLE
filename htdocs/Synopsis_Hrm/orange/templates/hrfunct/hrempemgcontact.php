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
?>
<script language="JavaScript">
function delEContact() {

	var check = false;
	with (document.frmEmp) {
		for (var i=0; i < elements.length; i++) {
			if ((elements[i].name == 'chkecontactdel[]') && (elements[i].checked == true)) {
				check = true;
			}
		}
	}

	if(!check) {
		alert('<?php echo $lang_Error_SelectAtLeastOneRecordToDelete?>')
		return;
	}

	document.frmEmp.econtactSTAT.value="DEL";
	qCombo(5);
}

function validateEContact() {

	if(document.frmEmp.txtEConName.value == '') {
		alert('<?php echo $lang_Common_FieldEmpty?>');
		document.frmEmp.txtEConName.focus();
		return false;
	}

	if(document.frmEmp.txtEConRel.value == '') {
		alert('<?php echo $lang_Common_FieldEmpty?>');
		document.frmEmp.txtEConRel.focus();
		return false;
	}

	if ((document.frmEmp.txtEConHmTel.value == '') &&
		(document.frmEmp.txtEConMobile.value == '') &&
		(document.frmEmp.txtEConWorkTel.value == '')) {
		alert('<?php echo $lang_hremp_ie_PleaseSpecifyAtLeastOnePhoneNo; ?>');
		document.frmEmp.txtEConHmTel.focus();
		return false;
	}

	var cntrl = document.frmEmp.txtEConHmTel;
	if(cntrl.value != '' && !checkPhone(cntrl)) {
		alert('<?php echo "$lang_hremp_hmtele : $lang_hremp_InvalidPhone"; ?>');
		cntrl.focus();
		return;
	}

	var cntrl = document.frmEmp.txtEConMobile;
	if(cntrl.value != '' && !checkPhone(cntrl)) {
		alert('<?php echo "$lang_hremp_mobile : $lang_hremp_InvalidPhone"; ?>');
		cntrl.focus();
		return;
	}

	var cntrl = document.frmEmp.txtEConWorkTel;
	if(cntrl.value != '' && !checkPhone(cntrl)) {
		alert('<?php echo "$lang_hremp_worktele : $lang_hremp_InvalidPhone"; ?>');
		cntrl.focus();
		return;
	}

	return true;
}

function addEContact() {
	if(validateEContact()) {
		document.frmEmp.econtactSTAT.value="ADD";
		qCombo(5);
	}
}

function viewEContact(ecSeq) {
	document.frmEmp.action=document.frmEmp.action + "&ECSEQ=" + ecSeq ;
	document.frmEmp.pane.value=5;
	document.frmEmp.submit();
}

function editEContact() {
	if(validateEContact()) {
		document.frmEmp.econtactSTAT.value="EDIT";
		qCombo(5);
	}
}

</script>
<span id="parentPaneEmgContact" >
<?php if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') { ?>
    <input type="hidden" name="econtactSTAT" value="">
<?php if(isset($this->getArr['ECSEQ'])) {
		$edit = $this->popArr['editECForm'];
?>
	<div id="editPaneEmgContact">
		<table id="editPaneEmgContact" height="120" border="0" cellpadding="0" cellspacing="0">
          <tr>
			 <td><font color=#ff0000>*</font><?php echo $lang_hremp_name; ?><input type="hidden" name="txtECSeqNo" value="<?php echo $edit[0][1]?>"></td>
			 <td><input type="text" name="txtEConName" value="<?php echo $edit[0][2]?>"></td>
			 <td width="50">&nbsp;</td>
			<td><font color=#ff0000>*</font><?php echo $lang_hremp_relationship; ?></td>
			 <td><input type="text" name="txtEConRel" value="<?php echo $edit[0][3]?>"></td>
			 </tr>
			 <tr>
			 <td><?php echo $lang_hremp_hmtele; ?></td>
			 <td><input type="text"  name="txtEConHmTel" value="<?php echo $edit[0][4]?>"></td>
			 <td width="50">&nbsp;</td>
			 <td><?php echo $lang_hremp_mobile; ?></td>
			 <td><input type="text" name="txtEConMobile" value="<?php echo $edit[0][5]?>"></td>
			 </tr>
			 <tr>
			 <td><?php echo $lang_hremp_worktele; ?></td>
			 <td><input type="text" name="txtEConWorkTel" value="<?php echo $edit[0][6]?>"></td>
			 </tr>
				<td>
					<?php	if (($locRights['edit']) || ($_GET['reqcode'] === "ESS")){ ?>
					<img border="0" title="Save" onClick="editEContact();" onmouseout="this.src='../../themes/beyondT/pictures/btn_save.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_save_02.gif';" src="../../themes/beyondT/pictures/btn_save.gif">
					<?php	} ?>
				</td>
			</tr>

		</table>
	</div>
<?php  } else { ?>
	<div id="addPaneEmgContact" class="<?php echo ($this->popArr['empECAss'] != null)?"addPane":""; ?>" >
		<table height="120" border="0" cellpadding="0" cellspacing="0">
			 <tr>
			 <td><font color=#ff0000>*</font><?php echo $lang_hremp_name; ?>
			 	<input type="hidden" name="txtECSeqNo" value="<?php echo $this->popArr['newECID']?>" /></td>
			  <td><input name="txtEConName" <?php echo $locRights['add'] ? '':''?> type="text"></td>
			 <td width="50">&nbsp;</td>
			<td><font color=#ff0000>*</font><?php echo $lang_hremp_relationship; ?>&nbsp;&nbsp;</td>
			 <td><input name="txtEConRel" <?php echo $locRights['add'] ? '':''?> type="text"></td>
			 </tr>
			 <tr>
			 <td><?php echo $lang_hremp_hmtele; ?>&nbsp;&nbsp;</td>
			 <td><input name="txtEConHmTel" <?php echo $locRights['add'] ? '':''?> type="text"></td>
			 <td width="50">&nbsp;</td>
			 <td><?php echo $lang_hremp_mobile; ?>&nbsp;&nbsp;</td>
			 <td><input name="txtEConMobile" <?php echo $locRights['add'] ? '':''?> type="text"></td>
			 </tr>
			 <tr>
			 <td><?php echo $lang_hremp_worktele; ?>&nbsp;&nbsp;</td>
			 <td><input name="txtEConWorkTel" <?php echo $locRights['add'] ? '':''?> type="text"></td>
			 </tr>
				<td>
<?php	if (($locRights['add']) || ($_GET['reqcode'] === "ESS")) { ?>
        <img border="0" title="Save" onClick="addEContact();" onmouseout="this.src='../../themes/beyondT/pictures/btn_save.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_save_02.gif';" src="../../themes/beyondT/pictures/btn_save.gif">
<?php	} ?>
				</td>
			</tr>
		</table>
	</div>
<?php } ?>
<?php
$rset = $this->popArr['empECAss'];
		if ($rset != null){ //checking for a records if exsist view the the table and delete btn else no ?>
		<?php if($locRights['add']) { ?>
		<img border="0" title="Add" onClick="showAddPane('EmgContact');" onMouseOut="this.src='../../themes/beyondT/pictures/btn_add.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_add_02.gif';" src="../../themes/beyondT/pictures/btn_add.gif" />
		<?php } ?>
		<?php	if (($locRights['delete']) || ($_GET['reqcode'] === "ESS"))  { //checking for the privilege?>
		<img title="Delete" onclick="delEContact();" onmouseout="this.src='../../themes/beyondT/pictures/btn_delete.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_delete_02.gif';" src="../../themes/beyondT/pictures/btn_delete.gif">
		<?php 	} //view the delete btn?>
		<table width="550" align="center" border="0" class="tabForm">
			<tr>
                <td width="50">&nbsp;</td>
				<td><strong><?php echo $lang_hremp_name; ?></strong></td>
				<td><strong><?php echo $lang_hremp_relationship; ?></strong></td>
				<td><strong><?php echo $lang_hremp_hmtele; ?></strong></td>
				<td><strong><?php echo $lang_hremp_mobile; ?></strong></td>
				<td><strong><?php echo $lang_hremp_worktele; ?></strong></td>
			</td>
		</tr>
		</tr>
<?php
		for($c=0;$rset && $c < count($rset); $c++) {
        echo '<tr>';
            echo "<td><input type='checkbox' class='checkbox' name='chkecontactdel[]' value='" . $rset[$c][1] ."'></td>";

            ?> <td><a href="javascript:viewEContact('<?php echo $rset[$c][1]?>')"><?php echo $rset[$c][2]?></a></td> <?php
            echo '<td>' . $rset[$c][3] .'</td>';
            echo '<td>' . $rset[$c][4] .'</td>';
            echo '<td>' . $rset[$c][5] .'</td>';
            echo '<td>' . $rset[$c][6] .'</td>';

        echo '</tr>';
   		} ?>

   	</table>
<?php } ?>
<?php } ?>
</span>

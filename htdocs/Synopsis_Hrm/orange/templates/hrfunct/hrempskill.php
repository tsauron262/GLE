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
?>
<script language="JavaScript">

function editSkill() {

	if(document.EditSkill.title=='Save') {
		editEXTSkill();
		return;
	}

	var frm=document.frmEmp;
	for (var i=0; i < frm.elements.length; i++)
		frm.elements[i].disabled = false;

	document.EditSkill.src="../../themes/beyondT/pictures/btn_save.gif";
	document.EditSkill.title="Save";
}

function moutSkill() {
	if(document.EditSkill.title=='Save')
		document.EditSkill.src='../../themes/beyondT/pictures/btn_save.gif';
	else
		document.EditSkill.src='../../themes/beyondT/pictures/btn_edit.gif';
}

function moverSkill() {
	if(document.EditSkill.title=='Save')
		document.EditSkill.src='../../themes/beyondT/pictures/btn_save_02.gif';
	else
		document.EditSkill.src='../../themes/beyondT/pictures/btn_edit_02.gif';
}

function addEXTSkill() {

	if(document.frmEmp.cmbSkilCode.value=='0') {
		alert("<?php echo $lang_Error_FieldShouldBeSelected; ?>");
		document.frmEmp.cmbSkilCode.focus();
		return;
	}

	if (document.frmEmp.txtEmpYears.value == '') {
		alert ("<?php echo $lang_hrEmpMain_YearsOfExperiencCannotBeBlank; ?>!");
		document.frmEmp.txtEmpYears.focus();
		return;
	}

	var txt = document.frmEmp.txtEmpYears;
		if (!decimal(txt.value)) {
			alert ("<?php echo $lang_hrEmpMain_YearsOfExperiencWrongFormat; ?>");
			txt.focus();
			return;
	}

	wrkExp = eval(txt.value);
	if(wrkExp < 0 || wrkExp > 99) {
		<?php
			$promptText = preg_replace('/#range/', '0-9', $lang_hrEmpMain_YearsOfExperiencBetween);
		?>
			alert ("<?php echo $promptText; ?>!");
			txt.focus();
			return;
	}

	document.frmEmp.skillSTAT.value="ADD";
	qCombo(16);
}

function decimal(txt) {
	regExp = /^[0-9]+(\.[0-9]+){0,1}$/;

	if (regExp.test(txt)) {
		return true;
	}

	return false;
}

function editEXTSkill() {

	var txt = document.getElementById('etxtEmpYears');
	if (txt.value == '') {
		alert ("<?php echo $lang_hrEmpMain_YearsOfExperiencCannotBeBlank; ?>!");
		txt.focus();
		return;
	}

	if (!decimal(txt.value)) {
			alert ("<?php echo $lang_hrEmpMain_YearsOfExperiencWrongFormat; ?>");
			txt.focus();
			return;
	}

  document.frmEmp.skillSTAT.value="EDIT";
  qCombo(16);
}

function delEXTSkill() {

	var check = false;
	with (document.frmEmp) {
		for (var i=0; i < elements.length; i++) {
			if ((elements[i].name == 'chkskilldel[]') && (elements[i].checked == true)) {
				check = true;
			}
		}
	}

	if(!check) {
		alert('<?php echo $lang_Error_SelectAtLeastOneRecordToDelete; ?>')
		return;
	}

    document.frmEmp.skillSTAT.value="DEL";
    qCombo(16);
}

function viewSkill(skill) {
	document.frmEmp.action = document.frmEmp.action + "&SKILL=" + skill;
	document.frmEmp.pane.value = 16;
	document.frmEmp.submit();
}
</script>
<span id="parentPaneSkills" >
<?php if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') { ?>
    <input type="hidden" name="skillSTAT" value="">
<?php
if(isset($this->popArr['editSkillArr'])) {
    $edit = $this->popArr['editSkillArr'];
?>
	<div id="editPaneSkills" >
	<table width="100%" border="0" cellpadding="5" cellspacing="0" class="">
                    <tr>
                      <td width="200"><?php echo $lang_hrEmpMain_Skill?></td>
    				  <td><input type="hidden" name="cmbSkilCode" value="<?php echo $edit[0][1]?>"><strong>
<?php						$allSkilllist = $this->popArr['allSkilllist'];
						for($c=0;count($allSkilllist)>$c;$c++)
							if($this->getArr['SKILL']==$allSkilllist[$c][0])
							     break;

					  	echo $allSkilllist[$c][1];
?>
					  </strong></td>
					</tr>
					  <tr>
                      <td><?php echo $lang_hrEmpMain_yearofex?></td>
    				  <td><input type="text" name="txtEmpYears" id="etxtEmpYears" <?php echo isset($this->popArr['txtEmpYears']) ? '':'disabled'?> value="<?php echo isset($this->popArr['txtEmpYears']) ? $this->popArr['txtEmpYears'] : $edit[0][2]?>"></td>
    				  <td width="50">&nbsp;</td>
					  </tr>

					  <tr>
						<td><?php echo $lang_Leave_Common_Comments?></td>
						<td> <textarea <?php echo isset($this->popArr['txtEmpComments']) ? '':'disabled'?>  name="txtEmpComments"><?php echo isset($this->popArr['txtEmpComments']) ? $this->popArr['txtEmpComments'] : $edit[0][3]?></textarea></td>
    				  <td width="50">&nbsp;</td>
					 </tr>

					  <tr>
						<td valign="top"></td>
						<td align="left" valign="top">
		<?php			if($locRights['edit']) { ?>
						        <img src="../../themes/beyondT/pictures/btn_edit.gif" title="Edit" onmouseout="moutSkill();" onmouseover="moverSkill();" name="EditSkill" onClick="editSkill();">
		<?php			}  ?>
						</td>
					  </tr>
                  </table>
		</div>
<?php } else { ?>
<div id="addPaneSkills" class="<?php echo ($this->popArr['rsetSkill'] != null)?"addPane":""; ?>" >
	<table width="100%" border="0" cellpadding="5" cellspacing="0" class="">
                    <tr>
                      <td width="200"><?php echo $lang_hrEmpMain_Skill?></td>
    				  <td><select name="cmbSkilCode" <?php echo $locRights['add'] ? '':'disabled'?>>
    				  		<option selected value="0">-----------<?php echo $lang_rep_SelectSkill;?>-------------</option>
<?php
						$skilllist= $this->popArr['uskilllist'];
						for($c=0;$skilllist && count($skilllist)>$c;$c++)
							   echo "<option value=" . $skilllist[$c][0] . ">" . $skilllist[$c][1] . "</option>";
?>
					  </select></td>
					</tr>
                    <tr>
                      <td><?php echo $lang_hrEmpMain_yearofex?></td>
    				  <td><input type="text" name="txtEmpYears" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo isset($this->popArr['txtEmpYears']) ? $this->popArr['txtEmpYears'] :''?>"></td>
    				  <td width="50">&nbsp;</td>
					</tr>
					 <tr>
					<td><?php echo $lang_Leave_Common_Comments?></td>
						<td> <textarea <?php echo $locRights['add'] ? '':'disabled'?> name="txtEmpComments"><?php echo isset($this->popArr['txtEmpComments']) ? $this->popArr['txtEmpComments'] :''?></textarea></td>
    				  <td width="50">&nbsp;</td>
						 </tr>
					  <tr>
						<td valign="top"></td>
						<td align="left" valign="top">
					<?php	if($locRights['add']) { ?>
					        <img border="0" title="Save" onClick="addEXTSkill();" onmouseout="this.src='../../themes/beyondT/pictures/btn_save.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_save_02.gif';" src="../../themes/beyondT/pictures/btn_save.gif">
					<?php	} ?>
								</td>
					  </tr>
                  </table>
</div>
<?php } ?>
<?php
$rset = $this->popArr['rsetSkill'] ;
$allSkilllist = $this->popArr['allSkilllist'];

if ($rset != null){ ?>
<h3><?php echo $lang_hrEmpMain_assignskills?></h3>
<?php	if($locRights['add']) { ?>
		<img border="0" title="Add" onClick="showAddPane('Skills');" onmouseout="this.src='../../themes/beyondT/pictures/btn_add.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_add_02.gif';" src="../../themes/beyondT/pictures/btn_add.gif">
<?php	} ?>
<?php	if($locRights['delete']) { ?>
        <img title="Delete" onclick="delEXTSkill();" onmouseout="this.src='../../themes/beyondT/pictures/btn_delete.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_delete_02.gif';" src="../../themes/beyondT/pictures/btn_delete.gif">
<?php 	} ?>
<table width="100%" border="0" cellpadding="5" cellspacing="0" class="tabForm">
       				<tr>
                      	<td></td>
						 <td><strong><?php echo $lang_hrEmpMain_Skill?></strong></td>
						 <td><strong><?php echo $lang_hrEmpMain_yearofex?></strong></td>

					</tr>
<?php

    for($c=0; $rset && $c < count($rset); $c++)
        {
        echo '<tr>';
            echo "<td><input type='checkbox' class='checkbox' name='chkskilldel[]' value='" . $rset[$c][1] ."'>";

			for($a=0;count($allSkilllist)>$a;$a++)
				if($rset[$c][1] == $allSkilllist[$a][0])
				   $lname=$allSkilllist[$a][1];
			?><td><a href="javascript:viewSkill('<?php echo $rset[$c][1]?>')"><?php echo $lname?></td><?php
			echo '<td>'. $rset[$c][2] .'</a></td>';

        echo '</tr>';
        }

?>
     </table>
<?php } ?>
<?php } ?>
</span>
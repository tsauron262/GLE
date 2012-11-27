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

	$lantype  = array ($lang_hrEmpMain_Writing=> 1 , $lang_hrEmpMain_Speaking=>2 , $lang_hrEmpMain_Reading=>3 );
	$grdcodes = array($lang_hrEmpMain_Poor=> 1 ,$lang_hrEmpMain_Basic=>2 , $lang_hrEmpMain_Good=>3 ,$lang_hrEmpMain_MotherTongue=>4);

?>
<script language="JavaScript">
function editLang()
{
	if(document.EditLang.title=='Save') {
		editEXTLang();
		return;
	}

	var frm=document.frmEmp;
	for (var i=0; i < frm.elements.length; i++)
		frm.elements[i].disabled = false;
	document.EditLang.src="../../themes/beyondT/pictures/btn_save.gif";
	document.EditLang.title="Save";
}

function moutLang() {
	if(document.EditLang.title=='Save')
		document.EditLang.src='../../themes/beyondT/pictures/btn_save.gif';
	else
		document.EditLang.src='../../themes/beyondT/pictures/btn_edit.gif';
}

function moverLang() {
	if(document.EditLang.title=='Save')
		document.EditLang.src='../../themes/beyondT/pictures/btn_save_02.gif';
	else
		document.EditLang.src='../../themes/beyondT/pictures/btn_edit_02.gif';
}


function addEXTLang()
{
	if(document.frmEmp.cmbLanCode.value=='0') {
		alert("<?php echo $lang_Error_FieldShouldBeSelected; ?>");
		document.frmEmp.cmbLanCode.focus();
		return;
	}

	if(document.frmEmp.cmbLanType.value=='0') {
		alert("<?php echo $lang_Error_FieldShouldBeSelected; ?>");
		document.frmEmp.cmbLanType.focus();
		return;
	}

	if(document.frmEmp.cmbRatGrd.value=='0') {
		alert("<?php echo $lang_Error_FieldShouldBeSelected; ?>");
		document.frmEmp.cmbRatGrd.focus();
		return;
	}

  document.frmEmp.langSTAT.value="ADD";
  qCombo(11);
}

function editEXTLang() {
  document.frmEmp.langSTAT.value="EDIT";
  qCombo(11);
}

function viewLang(lanSeq,lanFlu) {

	document.frmEmp.action=document.frmEmp.action + "&lanSEQ=" + lanSeq + "&lanFLU=" + lanFlu;
	document.frmEmp.pane.value=11;
	document.frmEmp.submit();
}

function delEXTLang() {

	var check = false;
	with (document.frmEmp) {
		for (var i=0; i < elements.length; i++) {
			if ((elements[i].name == 'chklangdel[]') && (elements[i].checked == true)) {
				check = true;
			}
		}
	}

	if(!check) {
		alert('<?php echo $lang_Common_SelectDelete; ?>')
		return;
	}

    document.frmEmp.langSTAT.value="DEL";
   qCombo(11);
}

</script>
<span id="parentPaneLanguages" >
<?php  if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') { ?>
<input type="hidden" name="langSTAT" value="">
<?php
if(isset($this->getArr['lanSEQ'])) {
    $edit = $this->popArr['editLanArr'];
?>
<div id="editPaneLanguages" >
	<table width="100%" border="0" cellpadding="5" cellspacing="0">
                    <tr>
                      <td width="200"><?php echo $lang_hremp_Language?></td>
    				  <td><input type="hidden" name="cmbLanCode" value="<?php echo $edit[0][1]?>"><strong>
<?php						$lanlist = $this->popArr['lanlist'];
						for($c=0;count($lanlist)>$c;$c++)
							if($edit[0][1]==$lanlist[$c][0])
							     break;

					  			echo $lanlist[$c][1];
?>
					  </strong></td>
					</tr>
					  <tr>
						<td valign="top"><?php echo $lang_hremplan_fluency?></td>
						<td align="left" valign="top"><input type="hidden" name="cmbLanType" value="<?php echo $this->getArr['lanFLU']?>"><strong>
<?php
						$index=array_values($lantype);
						$value=array_keys($lantype);
						for($a=0;count($lantype)>$a;$a++)
							if($this->getArr['lanFLU']==$index[$a])
					  			echo $value[$a];
?>
						</td>
					  </tr>

					  <tr>
						<td valign="top"><?php echo $lang_hrEmpMain_ratinggarde?></td>
						<td align="left" valign="top"><select disabled name='cmbRatGrd'>
<?php
						$code=array_values($grdcodes);
						 $name=array_keys($grdcodes);
						for($c=0;count($grdcodes)>$c;$c++)
							if($code[$c]==$edit[0][3])
								echo "<option selected value='" . $code[$c] . "'>" . $name[$c] ."</option>";
							else
								echo "<option value='" . $code[$c] . "'>" . $name[$c] ."</option>";
?>
						</select></td>
					  </tr>

					  <tr>
						<td valign="top"></td>
						<td align="left" valign="top">
						        <img src="../../themes/beyondT/pictures/btn_edit.gif" title="Edit" onmouseout="moutLang();" onmouseover="moverLang();" name="EditLang" onClick="editLang();">
						</td>
					  </tr>
                  </table>
</div>
<?php } else { ?>
<div id="addPaneLanguages" class="<?php echo ($this->popArr['rsetLang'] != null)?"addPane":""; ?>" >
	<table width="100%" border="0" cellpadding="5" cellspacing="0" class="">
                    <tr>
                      <td width="200"><?php echo $lang_hremp_Language?></td>
    				  <td><select name="cmbLanCode">
    				  		<option selected value="0">--<?php echo $lang_hremplan_SelectLanguage; ?>--</option>
<?php
						$lanlist= $this->popArr['lanlist'];
						for($c=0;$lanlist && count($lanlist)>$c;$c++)
							if(isset($this->popArr['cmbLanCode']) && $this->popArr['cmbLanCode']==$lanlist[$c][0])
							   echo "<option  value=" . $lanlist[$c][0] . ">" . $lanlist[$c][1] . "</option>";
							 else
							   echo "<option value=" . $lanlist[$c][0] . ">" . $lanlist[$c][1] . "</option>";
?>
					  </select></td>
					</tr>
                    <tr>
                      <td width="200"><?php echo $lang_hremplan_fluency?></td>
    				  <td><select name="cmbLanType">
    				  		<option value="0">---<?php echo $lang_hremplan_SelectFluency; ?>---</option>
<?php
						$index=array_values($lantype);
						$value=array_keys($lantype);
						for($c=0;$lantype && count($lantype)>$c;$c++)
							   echo "<option value=" . $index[$c] . ">" . $value[$c] . "</option>";
?>
					  </select></td>
					</tr>
					  <tr>
						<td valign="top"><?php echo $lang_hrEmpMain_ratinggarde?></td>
						<td align="left" valign="top"><select name='cmbRatGrd'>
    				  		<option value="0">----<?php echo $lang_hremplan_SelectRating; ?>----</option>
<?php
				        $code=array_values($grdcodes);
						$name=array_keys($grdcodes);
						for($c=0;$grdcodes && count($grdcodes)>$c;$c++)
							   echo "<option value=" . $code[$c] . ">" . $name[$c] . "</option>";
?>

					</select>
						</td>
					  </tr>
					  <tr>
						<td valign="top"></td>
						<td align="left" valign="top">
					        <img border="0" title="Save" onClick="addEXTLang();" onmouseout="this.src='../../themes/beyondT/pictures/btn_save.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_save_02.gif';" src="../../themes/beyondT/pictures/btn_save.gif">
								</td>
					  </tr>
                  </table>
</div>
<?php } ?>
<?php
    $rset = $this->popArr['rsetLang'];

    if( $rset && count($rset) > 0 ){
        $assignedLanguages = true;
    } else {
        $assignedLanguages = false;
    }
?>
<?php if($assignedLanguages) { ?>
<h3><?php echo $lang_hremplan_assignlanguage; ?></h3>
<img border="0" title="Add" onClick="showAddPane('Languages');" onmouseout="this.src='../../themes/beyondT/pictures/btn_add.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_add_02.gif';" src="../../themes/beyondT/pictures/btn_add.gif">
<img title="Delete" onclick="delEXTLang();" onmouseout="this.src='../../themes/beyondT/pictures/btn_delete.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_delete_02.gif';" src="../../themes/beyondT/pictures/btn_delete.gif">
<table width="100%" border="0" cellpadding="5" cellspacing="0" class="tabForm">

                    <tr>
                      	<td></td>
						 <td><strong><?php echo $lang_hremp_Language?></strong></td>
						 <td><strong><?php echo $lang_hremplan_fluency?></strong></td>
						 <td><strong><?php echo $lang_hrEmpMain_ratinggarde?></strong></td>
					</tr>
<?php
    for($c=0; $rset && $c < count($rset); $c++) {
        echo '<tr>';
            echo "<td><input type='checkbox' class='checkbox' name='chklangdel[]' value='" . $rset[$c][1] ."|". $rset[$c][2] ."'>";

			for($a=0;count($lanlist)>$a;$a++)
				if($rset[$c][1] == $lanlist[$a][0])
				   $lname=$lanlist[$a][1];
            ?> <td><a href="javascript:viewLang('<?php echo $rset[$c][1]?>','<?php echo $rset[$c][2]?>')"><?php echo $lname?></a></td> <?php

            for($a=0;count($lantype)>$a;$a++)
				if($rset[$c][2] == $index[$a])
				   $flu=$value[$a];
            echo '<td>' . $flu .'</a></td>';
            for($a=0;count($grdcodes)>$a;$a++)
				if($rset[$c][3] == $code[$a])
				   $rate=$name[$a];
            echo '<td>' . $rate.'</a></td>';

        echo '</tr>';
        }
?>
	</table>
<?php } ?>
<?php } ?>
</span>

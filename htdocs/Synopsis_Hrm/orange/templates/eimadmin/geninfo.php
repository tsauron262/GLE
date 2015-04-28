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

require_once ROOT_PATH . '/lib/confs/sysConf.php';
require_once($lan->getLangPath("full.php"));

	$sysConst = new sysConf();
	$locRights=$_SESSION['localRights'];

	$GLOBALS['lang_Common_Select'] = $lang_Common_Select;

function populateStates($value, $oldState) {

	$view_controller = new ViewController();
	$provlist = $view_controller->xajaxObjCall($value,'LOC','province');

	$objResponse = new xajaxResponse();
	$xajaxFiller = new xajaxElementFiller();
	$xajaxFiller->setDefaultOptionName($GLOBALS['lang_Common_Select']);
	if ($provlist) {
		$objResponse->addAssign('lrState','innerHTML','<select name="txtState" id="txtState"><option value="0">--- '.$GLOBALS['lang_Common_Select'].' ---</option></select>');
		$objResponse = $xajaxFiller->cmbFillerById($objResponse,$provlist,1,'frmGenInfo.lrState','txtState');

	} else {
		$objResponse->addAssign('lrState','innerHTML','<input type="text" name="txtState" id="txtState" value="'. $oldState .'">');
	}

	$objResponse->addAssign('status','innerHTML','');
	return $objResponse->getXML();
}

$objAjax = new xajax();
$objAjax->registerFunction('populateStates');
$objAjax->processRequests();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php $objAjax->printJavascript(); ?>
<script type="text/javascript" src="../../scripts/archive.js"></script>
<script>

function mout() {
	var Edit = document.getElementById("btnEdit");

	if(Edit.title=='Save')
		Edit.src='../../themes/<?php echo $styleSheet; ?>/pictures/btn_save.gif';
	else
		Edit.src='../../themes/<?php echo $styleSheet; ?>/pictures/btn_edit.gif';
}

function mover() {
	var Edit = document.getElementById("btnEdit");

	if(Edit.title=='Save')
		Edit.src='../../themes/<?php echo $styleSheet; ?>/pictures/btn_save_02.gif';
	else
		Edit.src='../../themes/<?php echo $styleSheet; ?>/pictures/btn_edit_02.gif';
}

function edit()
{
	var Edit = document.getElementById("btnEdit");

	if(Edit.title=='Save') {
		addUpdate();
		return;
	}

	var frm=document.frmGenInfo;
	for (var i=0; i < frm.elements.length; i++) {
		frm.elements[i].disabled = false;
	}
	document.getElementById("btnClear").disabled = false;
	Edit.src="../../themes/<?php echo $styleSheet; ?>/pictures/btn_save.gif";
	Edit.title="Save";
}

	function addUpdate() {

		if (document.frmGenInfo.txtCompanyName.value == '') {
			alert ("<?php echo $lang_geninfo_err_CompanyName; ?>");
			document.frmGenInfo.txtCompanyName.focus();
			return;
		}

		var cntrl = document.frmGenInfo.txtPhone;
		if(cntrl.value != '' && !checkPhone(cntrl)) {
			alert('<?php echo $lang_geninfo_err_Phone; ?>');
			cntrl.focus();
			return;
		}

		var cntrl = document.frmGenInfo.txtFax;
		if(cntrl.value != '' && !checkPhone(cntrl)) {
			alert('<?php echo $lang_geninfo_err_Phone; ?>');
			cntrl.focus();
			return;
		}

		document.getElementById("cmbState").value=document.getElementById("txtState").value;
		document.frmGenInfo.STAT.value = "EDIT";
		document.frmGenInfo.submit();
	}

	function clearAll() {

		window.location.reload();
	}

	function validate() {

	return 'return false;';
	}

	function MM_preloadImages() { //v3.0
  		var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    	var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    		if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
	}

	function OnCountryChange(newValue) {
		document.getElementById('status').innerHTML = '<?php echo $lang_Commn_PleaseWait;?>....';

		// keep the old value only if state is a text input
		var oldVal = "";
		var state =  document.getElementById('txtState');
		if (state.type == 'text') {
			oldVal = state.value;
		}

		xajax_populateStates(newValue, oldVal);
	}
	
	function showCommentLengthExceedWarning() {
		totalFieldLength = 800;
		marginOffset = 35;
		usedLength = 0;
	
		with (document.forms['frmGenInfo']) {
			usedLength += txtCompanyName.value.length; 
			usedLength += txtTaxID.value.length; 
			usedLength += txtNAICS.value.length; 
			usedLength += txtPhone.value.length; 
			usedLength += txtFax.value.length; 
			usedLength += cmbCountry.options[cmbCountry.selectedIndex].value.length; 
			usedLength += txtStreet1.value.length; 
			usedLength += txtStreet2.value.length; 
			usedLength += cmbCity.value.length; 
			usedLength += (txtState.type == 'text') ? txtState.value.length : txtState.options[txtState.selectedIndex].value.length; 
			usedLength += txtZIP.value.length;
			
			availableLength = totalFieldLength - (usedLength + marginOffset);
			commentLengthWarning = document.getElementById('commentLengthWarningLabel');
			
			if (txtComments.value.length > availableLength) {
				commentLengthWarning.style.display = 'block';
			} else {
				commentLengthWarning.style.display = 'none';
			}
		}
	}

</script>
<link href="../../themes/<?php echo $styleSheet;?>/css/style.css" rel="stylesheet" type="text/css">
<style type="text/css">
@import url("../../themes/<?php echo $styleSheet;?>/css/style.css"); .style1 {color: #FF0000}
</style>
</head>
<body>
<table width='100%' cellpadding='0' cellspacing='0' border='0' class='moduleTitle'>
  <tr>
    <td valign='top'></td>
    <td width='100%'><h2><?php echo $lang_geninfo_heading; ?></h2></td>
    <td valign='top' align='right' nowrap style='padding-top:3px; padding-left: 5px;'><div id="status"></div></td>
  </tr>
</table>
<p>
<p>
<?php $editArr = $this->popArr['editArr']; ?>
<form name="frmGenInfo" id="frmGenInfo" method="post" action="<?php echo $_SERVER['PHP_SELF']?>?uniqcode=<?php echo $this->getArr['uniqcode']?>">
<table width="431" border="0" cellspacing="0" cellpadding="0" ><td width="177">

  <tr>
    <td height="27" valign='top'> <p>
       <input type="hidden" name="STAT" value="">
      </p></td>
    <td width="254" align='left' valign='bottom'> <font color="red" face="Verdana, Arial, Helvetica, sans-serif">&nbsp;
      <?php
		if (isset($this->getArr['msg'])) {
			$expString  = $this->getArr['msg'];
			$expString = explode ("%",$expString);
			$length = sizeof($expString);
			for ($x=0; $x < $length; $x++) {
				echo " " . $expString[$x];
			}
		}
		?>
      </font> </td>
  </tr><td width="177">
</table>

<?php $editArr = $this->popArr['editArr']; ?>

              <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
                  <td width="339" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/<?php echo $styleSheet; ?>/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
                  <td width="11"><img src="../../themes/<?php echo $styleSheet; ?>/pictures/spacer.gif" width="1" height="12" border="0" alt=""></td>
                </tr>
                <tr>
                  <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td><table width="100%" border="0" cellpadding="5" cellspacing="0" class="">
                  			  <tr>
							    <td><span class="error">*</span> <?php echo $lang_geninfo_compname; ?></td>
							    <td><input name="txtCompanyName" type="text" disabled value="<?php echo isset($editArr['COMPANY']) ? $editArr['COMPANY'] : ''?>" maxlength="250"></td>
							    <td><?php echo $lang_geninfo_numEmployees. " :"; ?></td>
								<td><?php echo $this->popArr['empcount'];?></td>
				   			  </tr>
				   			  <tr>
				   			  	<td><?php echo $lang_geninfo_taxID; ?></td>
							    <td><input name='txtTaxID' type="text" disabled value="<?php echo isset($editArr['TAX']) ? $editArr['TAX'] : ''?>" maxlength="25"></td>
				   			  	<td><?php echo $lang_geninfo_naics; ?></td>
							    <td><input name='txtNAICS' type="text" disabled value="<?php echo isset($editArr['NAICS']) ? $editArr['NAICS'] : ''?>" maxlength="15"></td>
							  </tr>
							  <tr>
							    <td><?php echo $lang_compstruct_Phone; ?></td>
							    <td><input name='txtPhone' type="text" disabled value="<?php echo isset($editArr['PHONE']) ? $editArr['PHONE'] : ''?>" maxlength="20"></td>
							  	<td><?php echo $lang_comphire_fax; ?></td>
							    <td><input name="txtFax" type="text" disabled value="<?php echo isset($editArr['FAX']) ? $editArr['FAX'] : ''?>" maxlength="20"></td>
							  </tr>
							  <tr>
							    <td><?php echo $lang_compstruct_country; ?></td>
							    <td><select name='cmbCountry' disabled onChange="OnCountryChange(this.value);">
							    		<option value="0">--- <?php echo $lang_Common_Select;?> ---</option>
							    <?php		$cntlist = $this->popArr['cntlist'];
							    		for($c=0; $cntlist && count($cntlist)>$c ;$c++)
							    			if(isset($editArr['COUNTRY']) && ($editArr['COUNTRY'] == $cntlist[$c][0]))
							    				echo "<option selected value='" . $cntlist[$c][0] . "'>" . $cntlist[$c][1] . "</option>";
							    			else
							    				echo "<option value='" . $cntlist[$c][0] . "'>" . $cntlist[$c][1] . "</option>";
							    ?>
							    </select></td>
							  </tr>
							  <tr>
							    <td><?php echo $lang_compstruct_Address; ?>1</td>
							    <td><input name='txtStreet1' type="text" disabled value="<?php echo isset($editArr['STREET1']) ? $editArr['STREET1'] : ''?>" maxlength="40"></td>
							    <td><?php echo $lang_compstruct_Address; ?>2</td>
							    <td><input name='txtStreet2' type="text" disabled value="<?php echo isset($editArr['STREET2']) ? $editArr['STREET2'] : ''?>" maxlength="40"></td>
							  </tr>
							  <tr valign="top">
							  	<td><?php echo $lang_compstruct_city; ?></td>
							    <td><input name="cmbCity" type="text" disabled value="<?php echo isset($editArr['CITY']) ? $editArr['CITY'] : ''?>" maxlength="30"></td>
							    <td><?php echo $lang_compstruct_state?></td>
							    <td><div id="lrState" name="lrState">
							    <?php if (isset($editArr['COUNTRY']) && ($editArr['COUNTRY'] == 'US')) { ?>
							    	<select name="txtState" id="txtState" disabled>
							    		<option value="0">--- <?php echo $lang_Common_Select;?>---</option>
							     	<?php	$statlist = $this->popArr['provlist'];
							    		for($c=0; $statlist && count($statlist)>$c ;$c++)
							    			if($editArr['STATE'] == $statlist[$c][1])
							    				echo "<option selected value='" . $statlist[$c][1] . "'>" . $statlist[$c][2] . "</option>";
							    			else
							    				echo "<option value='" . $statlist[$c][1] . "'>" . $statlist[$c][2] . "</option>";
							    	?>
							    	</select>
							    	<?php } else { ?>
							    	<input name="txtState" type="text" disabled id="txtState" value="<?php echo isset($editArr['STATE']) ? $editArr['STATE'] : ''?>" maxlength="30">
							    	<?php } ?>
							    	</div>
							    	<input type="hidden" name="cmbState" id="cmbState" value="<?php echo isset($editArr['STATE']) ? $editArr['STATE'] : ''?>">
						    	</td>
							  </tr>
							  <tr valign="top">
							  	<td><?php echo $lang_compstruct_ZIP_Code; ?></td>
							    <td><input name='txtZIP' type="text" disabled value="<?php echo isset($editArr['ZIP']) ? $editArr['ZIP'] : ''?>" maxlength="20"></td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							  </tr>
							  <tr valign="top">
							    <td><?php echo $lang_Leave_Common_Comments; ?></td>
							    <td colspan="3">
									<span id="commentLengthWarningLabel" style="display: none" class="style1"><?php echo $lang_geninfo_err_CommentLengthWarning; ?></span>
									<textarea disabled name='txtComments' onKeyUp="showCommentLengthExceedWarning()"><?php echo isset($editArr['COMMENTS']) ? $editArr['COMMENTS'] : ''?></textarea>
									</td>
							  </tr>
							  <tr><td></td><td></td><td></td><td align="right">
<?php			if($locRights['edit']) { ?>
			        <input type="image" class="button1" id="btnEdit" src="../../themes/<?php echo $styleSheet; ?>/pictures/btn_edit.gif" title="Edit" onMouseOut="mout();" onMouseOver="mover();" name="Edit" onClick="edit(); return false;">
<?php			} else { ?>
			        <input type="image" class="button1" id="btnEdit" src="../../themes/<?php echo $styleSheet; ?>/pictures/btn_edit.gif" onClick="alert('<?php echo $lang_Common_AccessDenied;?>'); return false;">
<?php			}  ?>
					  <input type="image" class="button1" id="btnClear" disabled src="../../themes/<?php echo $styleSheet; ?>/icons/reset.gif" onMouseOut="this.src='../../themes/<?php echo $styleSheet; ?>/icons/reset.gif';" onMouseOver="this.src='../../themes/<?php echo $styleSheet; ?>/icons/reset_o.gif';" onClick="clearAll(); return false;" />
							</td> </tr>
                  </table></td>
                  <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td><img src="../../themes/<?php echo $styleSheet; ?>/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                </tr>
                <tr>
                  <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
                  <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/<?php echo $styleSheet; ?>/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
                  <td><img src="../../themes/<?php echo $styleSheet; ?>/pictures/spacer.gif" width="1" height="16" border="0" alt=""></td>
                </tr>
              </table>
<span id="notice"><?php echo preg_replace('/#star/', '<span class="error">*</span>', $lang_Commn_RequiredFieldMark); ?>.</span>
</form>
</body>
</html>

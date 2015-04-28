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

require_once ROOT_PATH . '/lib/controllers/ViewController.php';
require_once ROOT_PATH . '/lib/confs/sysConf.php';
require_once($lan->getLangPath("full.php"));

$GLOBALS['lang_Common_Select'] = $lang_Common_Select;
$GLOBALS['lang_Common_Save'] = $lang_Common_Save;

function assignEmploymentStatus($valArr) {

	$view_controller = new ViewController();
	$ext_jobtitempstat = new EXTRACTOR_JobTitEmpStat();
	$filledObj = $ext_jobtitempstat->parseAddData($valArr);
	$view_controller->addData('JEM',$filledObj);

	$assList = $view_controller->xajaxObjCall($valArr['txtJobTitleID'],'JOB','assigned');
	$unAssList = $view_controller->xajaxObjCall($valArr['txtJobTitleID'],'JOB','unAssigned');

	$objResponse = new xajaxResponse();
	$xajaxFiller = new xajaxElementFiller();
	$xajaxFiller->setDefaultOptionName($GLOBALS['lang_Common_Select']);
	$objResponse = $xajaxFiller->cmbFiller($objResponse,$assList,0,'frmJobTitle','cmbAssEmploymentStatus',0);
	$objResponse = $xajaxFiller->cmbFiller($objResponse,$unAssList,0,'frmJobTitle','cmbUnAssEmploymentStatus',0);
	$objResponse->addAssign('status','innerHTML','');

return $objResponse->getXML();
}



function unAssignEmploymentStatus($jobtit,$empstat) {

	$delArr[0][0] = $jobtit;
	$delArr[1][0] = $empstat;

	$view_controller = new ViewController();
	$view_controller ->delParser('JEM',$delArr);

	$view_controller = new ViewController();
	$assList = $view_controller->xajaxObjCall($jobtit,'JOB','assigned');
	$unAssList = $view_controller->xajaxObjCall($jobtit,'JOB','unAssigned');

	$objResponse = new xajaxResponse();
	$xajaxFiller = new xajaxElementFiller();
	$xajaxFiller->setDefaultOptionName($GLOBALS['lang_Common_Select']);
	$objResponse = $xajaxFiller->cmbFiller($objResponse,$assList,0,'frmJobTitle','cmbAssEmploymentStatus',0);
	$objResponse = $xajaxFiller->cmbFiller($objResponse,$unAssList,0,'frmJobTitle','cmbUnAssEmploymentStatus',0);

	$objResponse->addAssign('status','innerHTML','');

return $objResponse->getXML();
}



function showAddEmpStatForm() {

    $objResponse = new xajaxResponse();
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.disabled = false;");
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.focus();");
	$objResponse->addScript("document.getElementById('layerEmpStat').style.visibility='visible';");
	//$parent::
	$objResponse->addAssign('layerEmpStat','style','visibility:hidden;');
	$objResponse->addAssign('buttonLayer','innerHTML',"<input type='button' value='".$GLOBALS['lang_Common_Save']."' onclick='addFormData();'>");
	$objResponse->addAssign('status','innerHTML','');

return $objResponse->getXML();
}

function showEditEmpStatForm($estatCode) {

	$view_controller = new ViewController();
	$editArr = $view_controller->xajaxObjCall($estatCode,'JOB','editEmpStat');

	$objResponse = new xajaxResponse();
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.disabled = false;");
	$objResponse->addScript("document.frmJobTitle.txtEmpStatID.value = '" .$editArr[0][0]."';");
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.value = '" .$editArr[0][1]."';");
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.focus();");
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.selectAll();");
	$objResponse->addScript("document.getElementById('layerEmpStat').style.visibility='visible';");

	$objResponse->addAssign('buttonLayer','innerHTML',"<input type='button' value='".$GLOBALS['lang_Common_Save']."' onclick='editFormData();'>");
	$objResponse->addAssign('status','innerHTML','');

return $objResponse->getXML();
}



function addExt($arrElements) {

	$view_controller = new ViewController();
	$ext_empstat = new EXTRACTOR_EmployStat();

	$objEmpStat = $ext_empstat->parseAddData($arrElements);
	$view_controller -> addData('EST',$objEmpStat,true);

	$view_controller = new ViewController();
	$unAssEmpStat = $view_controller->xajaxObjCall($arrElements['txtJobTitleID'],'JOB','unAssigned');

	$objResponse = new xajaxResponse();
	$xajaxFiller = new xajaxElementFiller();
	$xajaxFiller->setDefaultOptionName($GLOBALS['lang_Common_Select']);
	$objResponse = $xajaxFiller->cmbFiller($objResponse,$unAssEmpStat,0,'frmJobTitle','cmbUnAssEmploymentStatus',0);
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.value = '';");
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.disabled = true;");
	$objResponse->addScript("document.getElementById('layerEmpStat').style.visibility='hidden';");

	$objResponse->addAssign('buttonLayer','innerHTML','');
	$objResponse->addAssign('status','innerHTML','');

return $objResponse->getXML();
}

function editExt($arrElements) {

	$view_controller = new ViewController();
	$ext_empstat = new EXTRACTOR_EmployStat();

	$objEmpStat = $ext_empstat -> parseEditData($arrElements);
	$view_controller->updateData('EST',$arrElements['txtEmpStatID'],$objEmpStat,true);

	$view_controller = new ViewController();
	$unAssEmpStat = $view_controller->xajaxObjCall($arrElements['txtJobTitleID'],'JOB','unAssigned');

	$objResponse = new xajaxResponse();
	$xajaxFiller = new xajaxElementFiller();
	$xajaxFiller->setDefaultOptionName($GLOBALS['lang_Common_Select']);
	$objResponse = $xajaxFiller->cmbFiller($objResponse,$unAssEmpStat,0,'frmJobTitle','cmbUnAssEmploymentStatus',0);
	$objResponse->addScript("document.frmJobTitle.txtEmpStatID.value = '';");
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.value = '';");
	$objResponse->addScript("document.frmJobTitle.txtEmpStatDesc.disabled = true;");
	$objResponse->addScript("document.getElementById('layerEmpStat').style.visibility='hidden';");

	$objResponse->addAssign('buttonLayer','innerHTML','');
	$objResponse->addAssign('status','innerHTML','');

return $objResponse->getXML();
}

	$objAjax = new xajax();
	$objAjax->registerFunction('assignEmploymentStatus');
	$objAjax->registerFunction('unAssignEmploymentStatus');
	$objAjax->registerFunction('showAddEmpStatForm');
	$objAjax->registerFunction('showEditEmpStatForm');
	$objAjax->registerFunction('addExt');
	$objAjax->registerFunction('editExt');
	$objAjax->processRequests();

	$sysConst = new sysConf();

	$locRights=$_SESSION['localRights'];
	$cookie = $_COOKIE;

  if (isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') {

	$editArr = $this->popArr['editArr'];

	if (!isset($_COOKIE['txtJobTitleID']) || (isset($_COOKIE['txtJobTitleID']) && ($_COOKIE['txtJobTitleID'] != $editArr[0][0]))) {
		unset($cookie);
	}

  }

  if (isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'addmode') {

	if (!isset($_COOKIE['txtJobTitleID']) || (isset($_COOKIE['txtJobTitleID']) && ($_COOKIE['txtJobTitleID'] != ''))) {
		unset($cookie);
	}

  }

  setcookie('txtJobTitleName', 'null', time()-3600, '/');
  setcookie('txtJobTitleDesc', 'null', time()-3600, '/');
  setcookie('txtJobTitleComments', 'null', time()-3600, '/');
  setcookie('cmbJobSpecId', 'null', time()-3600, '/');
  setcookie('cmbPayGrade', 'null', time()-3600, '/');
  setcookie('txtJobTitleID', 'null', time()-3600, '/');
  
  $themeDir = '../../themes/' . $styleSheet;

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title></title>
<?php $objAjax->printJavascript(); ?>
<script type="text/javascript" src="../../scripts/octopus.js"></script>
<script type="text/javascript">

	function addSave() {
		if(document.frmJobTitle.txtJobTitleName.value == '') {
			alert ('<?php echo $lang_jobtitle_NameShouldBeSpecified; ?>');
			document.frmJobTitle.txtJobTitleName.focus();
			return;
		}

		if(document.frmJobTitle.txtJobTitleDesc.value == '') {
			alert ('<?php echo $lang_jobtitle_DescriptionShouldBeSpecified; ?>');
			document.frmJobTitle.txtJobTitleDesc.focus();
			return;
		}

		if(document.frmJobTitle.cmbPayGrade.value == '0') {
			alert ('<?php echo $lang_jobtitle_PayGradeNotSelected; ?>');
			document.frmJobTitle.cmbPayGrade.focus();
			return;
		}

		document.frmJobTitle.sqlState.value = "NewRecord";

		document.frmJobTitle.submit();
	}

	function goBack() {
		location.href = "./CentralController.php?uniqcode=<?php echo $this->getArr['uniqcode']?>&VIEW=MAIN";
	}

	function mout() {
		if(document.Edit.title=='Save') {
			document.Edit.src='<?php echo $themeDir; ?>/pictures/btn_save.gif';
		} else {
			document.Edit.src='<?php echo $themeDir; ?>/pictures/btn_edit.gif';
		}
	}

	function mover() {
		if(document.Edit.title=='Save') {
			document.Edit.src='<?php echo $themeDir; ?>/pictures/btn_save_02.gif';
		} else {
			document.Edit.src='<?php echo $themeDir; ?>/pictures/btn_edit_02.gif';
		}
	}

	function edit() {
		if(document.Edit.title=='Save') {
			addUpdate();
			return;
		}
	
		var frm=document.frmJobTitle;
	
		for (var i=0; i < frm.elements.length; i++) {
			frm.elements[i].disabled = false;
		}
	
		frm.txtEmpStatDesc.disabled=true;
	
		document.Edit.src="<?php echo $themeDir; ?>/pictures/btn_save.gif";
		document.Edit.title="Save";
	}

	function addUpdate() {
		if(document.frmJobTitle.txtJobTitleName.value == '') {
			alert ('<?php echo $lang_jobtitle_NameShouldBeSpecified; ?>');
			document.frmJobTitle.txtJobTitleName.focus();

			return;
		}

		if(document.frmJobTitle.txtJobTitleDesc.value == '') {
			alert ('<?php echo $lang_jobtitle_DescriptionShouldBeSpecified; ?>');
			document.frmJobTitle.txtJobTitleDesc.focus();

			return;
		}

		if(document.frmJobTitle.cmbPayGrade.value == '0') {
			alert ('<?php echo $lang_jobtitle_PayGradeNotSelected; ?>');
			document.frmJobTitle.cmbPayGrade.focus();

			return;
		}

		document.frmJobTitle.sqlState.value = "UpdateRecord";

		document.frmJobTitle.submit();
	}



	function assignEmploymentStatus() {	
		if(document.frmJobTitle.cmbUnAssEmploymentStatus.selectedIndex == -1) {
			alert('<?php echo $lang_jobtitle_NoSelection; ?>');
			return;
		}
	
		document.getElementById('status').innerHTML = '<?php echo $lang_Commn_PleaseWait;?>....';
	
		xajax_assignEmploymentStatus(xajax.getFormValues('frmJobTitle'));
	}

	function unAssignEmploymentStatus() {	
		if(document.frmJobTitle.cmbAssEmploymentStatus.selectedIndex == -1) {
			alert('<?php echo $lang_jobtitle_NoSelection; ?>');
			return;
		}

		document.getElementById('status').innerHTML = '<?php echo $lang_Commn_PleaseWait;?>....';

		xajax_unAssignEmploymentStatus(document.frmJobTitle.txtJobTitleID.value, document.frmJobTitle.cmbAssEmploymentStatus.value);
	}

	function numeric(txt) {
		var flag = true;
		var i, code;
		
		if (txt.value=="") {
		   return false;
		 }
		
		for (i=0;txt.value.length>i;i++) {
			code=txt.value.charCodeAt(i);
			
			if(code>=48 && code<=57 || code==46) {
			   flag=true;
			} else {
			   flag=false;
			   break;
			}
		}
		
		return flag;	
	}



	function editPayGrade() {	
		paygrade = document.frmJobTitle.cmbPayGrade.value;
	
		if(paygrade == '0') {
			alert('<?php echo $lang_jobtitle_PayGradeNotSelected; ?>');
			document.frmJobTitle.cmbPayGrade.focus();
			return;
		}
	
		document.gotoPayGrade.action = '../../lib/controllers/CentralController.php?uniqcode=SGR&id=' + paygrade + '&capturemode=updatemode';
	
		document.gotoPayGrade.submit();
	
	}

	function showEditForm() {	
		empstat = document.frmJobTitle.cmbUnAssEmploymentStatus.value;
	
		if(document.frmJobTitle.cmbUnAssEmploymentStatus.selectedIndex == -1) {
			alert('<?php echo $lang_jobtitle_PleaseSelectEmploymentStatus; ?>');
			document.frmJobTitle.cmbUnAssEmploymentStatus.focus();
	
			return;
		}
	
		xajax_showEditEmpStatForm(document.frmJobTitle.cmbUnAssEmploymentStatus.value);
	}

	function addFormData() {
		if(document.frmJobTitle.txtEmpStatDesc.value == '') {
			alert('<?php echo $lang_jobtitle_EnterEmploymentStatus; ?>');
			document.frmJobTitle.txtEmpStatDesc.focus();
	
			return;
		}
	
		document.getElementById('status').innerHTML = '<?php echo $lang_Commn_PleaseWait;?>....';
	
		xajax_addExt(xajax.getFormValues('frmJobTitle'));
	}

	function editFormData() {
		if(document.frmJobTitle.txtEmpStatDesc.value == '') {
			alert('<?php echo $lang_jobtitle_EnterEmploymentStatus; ?>');
			document.frmJobTitle.txtEmpStatDesc.focus();
	
			return;	
		}
	
		document.getElementById('status').innerHTML = '<?php echo $lang_Commn_PleaseWait;?>....';
	
		xajax_editExt(xajax.getFormValues('frmJobTitle'));
	}

	function preserveData() {
		if (!(document.getElementById('txtJobTitleName').disabled)) {
			id="txtJobTitleID";
			writeCookie(id,document.getElementById('txtJobTitleID').value);
	
			id="txtJobTitleName";
			writeCookie(id,document.getElementById('txtJobTitleName').value);
	
			id="txtJobTitleDesc";
			writeCookie(id,document.getElementById('txtJobTitleDesc').value);
	
			id="txtJobTitleComments";
			writeCookie(id,document.getElementById('txtJobTitleComments').value);
	
			id="cmbJobSpecId";
			writeCookie(id, document.getElementById(id).value);
	
			id="cmbPayGrade";
			writeCookie(id,document.getElementById('cmbPayGrade').value);
		}	
	}

	function writeCookie(name, value, expire) {
		if (!expire) {
			expire = 3600000;
		}
	
		var date = new Date();
		date.setTime(date.getTime()+expire);
		var expires = date.toGMTString();
	
		document.cookie = name+"="+value+"; expires="+expires+"; path=/";
	}

	function promptUseCookieValues() {
		if (!confirm('<?php echo $lang_jobtitle_ShowingSavedValues . "\\n" . $lang_Error_DoYouWantToContinue; ?>')) {
			history.go();
		}
	}

	function addSalaryGrade() {
		document.gotoPayGrade.action =  '../../lib/controllers/CentralController.php?uniqcode=SGR&capturemode=addmode';
	
		document.gotoPayGrade.submit();
	}

	function editSalaryGrade() {
		editPayGrade(document.frmJobTitle.cmbPayGrade.value);
	}

	function clearAll() {
		document.frmJobTitle.txtJobTitleName.value = '';
		document.frmJobTitle.txtJobTitleDesc.value = '';
		document.frmJobTitle.txtJobTitleComments.value = '';
		document.frmJobTitle.cmbJobSpecId.value = -1;
		document.frmJobTitle.cmbPayGrade.value = 0;
	}
</script>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<link href="../../themes/<?php echo $styleSheet;?>/css/style.css" rel="stylesheet" type="text/css">

<style type="text/css">@import url("../../themes/<?php echo $styleSheet;?>/css/style.css"); </style>
<style type="text/css">

    .roundbox {
        margin-top: 10px;
        margin-left: 0px;
        width: 625px;
    }

    .roundbox_content {
        padding:15px;
    }
	
	.controlLabel {
		width: 135px; 
		float: left; 
		padding-right: 10px;
		padding-left: 15px;
	}

	.controlContainer {
		padding-top: 4px;
		padding-bottom: 4px;
		vertical-align: top;
	}

</style>

</head>

<body onload="<?php echo (isset($cookie) && isset($this->getArr['capturemode']) && ($this->getArr['capturemode'] == 'updatemode'))? 'edit();' : '' ?><?php echo isset($cookie) ? 'promptUseCookieValues();' : '' ?>">

<form id="gotoPayGrade" name="gotoPayGrade" action="../../lib/controllers/CentralController.php?uniqcode=SGR&capturemode=addmode" method="post">
	<input type="hidden" name="referer" value="<?php echo $_SERVER['REQUEST_URI'];?>" />
</form>

<div class="moduleTitle" style="padding: 6px; ">	<h2><?php echo $lang_jobtitle_heading; ?></h2></div>
<div id="status" style="width: 20%; text-align: right; position: absolute; right: 0px; top: 5px;"></div>

<div style="padding: 6px">
	<img 
		title="Back" 
		alt="Back"
		src="<?php echo $themeDir; ?>/pictures/btn_back.gif"
		style="border: none;" 
		onmouseout="this.src='<?php echo $themeDir; ?>/pictures/btn_back.gif';" 
		onmouseover="this.src='<?php echo $themeDir; ?>/pictures/btn_back_02.gif';"  
		onclick="goBack();" />
</div>

<?php if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'addmode') { ?>

<div class="roundbox">
		<form id="frmJobTitle" name="frmJobTitle" method="POST" action="<?php echo $_SERVER['PHP_SELF']?>?uniqcode=<?php echo $this->getArr['uniqcode']?>">

		<input type="hidden" name="sqlState" />
		<input type="hidden" name="txtJobTitleID" id="txtJobTitleID" value="" />
		<div class="controlContainer">
			<label for="txtJobTitleName" class="controlLabel"><span class="error">*</span>&nbsp;<?php echo $lang_jobtitle_jobtitname;?></label>
			<input type="text" name="txtJobTitleName" id="txtJobTitleName" value="<?php echo isset($cookie['txtJobTitleName'])? $cookie['txtJobTitleName'] : ''?>" />
		</div>
		<div class="controlContainer">
			<label for="txtJobTitleDesc" class="controlLabel"><span class="error">*</span>&nbsp;<?php echo $lang_jobtitle_jobtitdesc;?></label>
			<textarea name="txtJobTitleDesc" id="txtJobTitleDesc"><?php echo isset($cookie['txtJobTitleDesc']) ? $cookie['txtJobTitleDesc'] : ''?></textarea>
		</div>
		<div class="controlContainer">
			<label for="txtJobTitleComments" class="controlLabel"><?php echo $lang_jobtitle_jobtitcomments; ?></label>
			<textarea name="txtJobTitleComments" id="txtJobTitleComments"><?php echo isset($cookie['txtJobTitleComments']) ? $cookie['txtJobTitleComments'] : ''?></textarea>
		</div>
		<div class="controlContainer">
			<label for="cmbJobSpecId" class="controlLabel"><?php echo $lang_jobtitle_jobspec; ?></label>
			<select name="cmbJobSpecId" id="cmbJobSpecId" style="width: 150px;">
				<option value='-1'>--<?php echo $lang_Leave_Common_Select; ?>--</option>
				<?php 
					$jobSpecs = $this->popArr['jobSpecList'];
					$selectedSpecId = isset($cookie['cmbJobSpecId']) ? $cookie['cmbJobSpecId'] : null;                                       
                                                
					foreach($jobSpecs as $jobSpec) {                                                    
						$selected = ($selectedSpecId == $jobSpec->getId()) ? 'selected' : '';                                                    
				?>
						<option <?php echo $selected; ?> value="<?php echo $jobSpec->getId();?>"> <?php echo $jobSpec->getName();?></option>
				<?php   } ?>
			</select>
		</div>
		<div class="controlContainer">
			<label for="cmbPayGrade" class="controlLabel"><span class="error">*</span> <?php echo $lang_hrEmpMain_paygrade; ?></label>
			<select name="cmbPayGrade" id="cmbPayGrade" style="width: 150px;">
				<option value='0'>--<?php echo $lang_Leave_Common_Select; ?>--</option>
				<?php 
					$paygrade = $this->popArr['paygrade'];

					for($c=0;$paygrade && count($paygrade)>$c;$c++) { ?>
						<option <?php echo (isset($cookie['cmbPayGrade']) && ($cookie['cmbPayGrade'] == $paygrade[$c][0])) ? 'selected' : '' ?> value="<?php echo $paygrade[$c][0]?>">
							<?php echo $paygrade[$c][1]?>
						</option>
				<?php	} ?>
			</select>
			<span style=" padding-left: 10px;">
				<input type="button" onclick="preserveData(); addSalaryGrade();" value="<?php echo $lang_jobtitle_addpaygrade; ?>" style="display: inline;" />
			</span>
			<span>
				<input type="button" onclick="preserveData(); editSalaryGrade();" value="<?php echo $lang_jobtitle_editpaygrade; ?>"  style="display: inline;" />
			</span>
		</div>
		<div class="controlContainer" style="padding-top: 25px;">
			<img 
				src="<?php echo $themeDir; ?>/pictures/btn_save.gif" 
				alt="Save" onclick="addSave();" 
				onmouseover="this.src='<?php echo $themeDir; ?>/pictures/btn_save_02.gif';" 
				onmouseout="this.src='<?php echo $themeDir; ?>/pictures/btn_save.gif';" /> 
				
			<img 
				src="<?php echo $themeDir; ?>/pictures/btn_clear.gif" 
				alt="Clear" onclick="clearAll();" 
				onmouseover="this.src='<?php echo $themeDir; ?>/pictures/btn_clear_02.gif';" 
				onmouseout="this.src='<?php echo $themeDir; ?>/pictures/btn_clear.gif';" />
		</div>
	</form>
</div>

<div  style="padding-top: 10px;">
	<span id="notice"><?php echo preg_replace('/#star/', '<span class="error">*</span>', $lang_Commn_RequiredFieldMark); ?>.</span>
</div>

<?php } elseif (isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') { ?>

<div class="roundbox">
	<form id="frmJobTitle" name="frmJobTitle" method="POST" action="<?php echo $_SERVER['PHP_SELF']?>?id=<?php echo $this->getArr['id']?>&uniqcode=<?php echo $this->getArr['uniqcode']?>&capturemode=updatemode">
		<input type="hidden" name="sqlState" />
		<input type="hidden" name="txtJobTitleID" id="txtJobTitleID" value="<?php echo $editArr[0][0]?>" />
		<div class="controlContainer">
			<span class="controlLabel"><?php echo $lang_jobtitle_jobtitid; ?></span>
			<span style="font-weight: bold"><?php echo $editArr[0][0]?></span>
		</div>
		<div class="controlContainer">
			<label for="txtJobTitleName" class="controlLabel"><span class="error">*</span>&nbsp;<?php echo $lang_jobtitle_jobtitname;?></label>
			<input type="text" disabled="disabled" name="txtJobTitleName" id="txtJobTitleName" value="<?php echo isset($cookie['txtJobTitleName']) ? $cookie['txtJobTitleName'] : $editArr[0][1]?>" />
		</div>
		<div class="controlContainer">
			<label for="txtJobTitleDesc" class="controlLabel"><span class="error">*</span>&nbsp;<?php echo $lang_jobtitle_jobtitdesc;?></label>
			<textarea disabled="disabled" name="txtJobTitleDesc" id="txtJobTitleDesc"><?php echo isset($cookie['txtJobTitleDesc']) ? $cookie['txtJobTitleDesc'] : $editArr[0][2]?></textarea>
		</div>
		<div class="controlContainer">
			<label for="txtJobTitleComments" class="controlLabel"><?php echo $lang_jobtitle_jobtitcomments; ?></label>
			<textarea disabled="disabled" name="txtJobTitleComments" id="txtJobTitleComments"><?php echo isset($cookie['txtJobTitleComments']) ? $cookie['txtJobTitleComments'] : $editArr[0][3]?></textarea>
		</div>
		<div class="controlContainer">
			<label for="cmbJobSpecId" class="controlLabel"><?php echo $lang_jobtitle_jobspec; ?></label>
			<select disabled="disabled" name="cmbJobSpecId" id="cmbJobSpecId" style="width: 150px;">
            	<option value='-1'>--<?php echo $lang_Leave_Common_Select; ?>--</option>
                <?php 
					$jobSpecs = $this->popArr['jobSpecList'];
					$selectedSpecId = isset($cookie['cmbJobSpecId']) ? $cookie['cmbJobSpecId'] : $editArr[0][5];                                       

					foreach($jobSpecs as $jobSpec) {
						$selected = ($selectedSpecId == $jobSpec->getId()) ? 'selected' : '';                                                    
				?>
						<option <?php echo $selected; ?> value="<?php echo $jobSpec->getId();?>"> <?php echo $jobSpec->getName();?></option>
				<?php   } ?>
			</select>
		</div>
		<div class="controlContainer" style="padding-top: 20px;">
			<label for="cmbPayGrade" class="controlLabel"><span class="error">*</span>&nbsp;<?php echo $lang_hrEmpMain_paygrade; ?></label>
			<select disabled="disabled" name="cmbPayGrade" id="cmbPayGrade" style="width: 150px;">
				<option value='0'>--<?php echo $lang_Leave_Common_Select; ?>--</option>
				<?php 
					$paygrade = $this->popArr['paygrade'];

				    for($c=0;$paygrade && count($paygrade)>$c;$c++)
				    	if ((isset($cookie['cmbPayGrade']) && ($cookie['cmbPayGrade'] == $paygrade[$c][0])) || ((!isset($cookie['cmbPayGrade'])) && ($paygrade[$c][0] == $editArr[0][4]))) {
							echo "<option selected value='" .$paygrade[$c][0]. "'>" .$paygrade[$c][1]. "</option>";
						} else { 
							echo "<option value='" .$paygrade[$c][0]. "'>" .$paygrade[$c][1]. "</option>";
						} 
					?>
			</select>
			<span style=" padding-left: 10px;">
				<input type="button" onclick="preserveData(); addSalaryGrade();" value="<?php echo $lang_jobtitle_addpaygrade; ?>" disabled="disabled" style="display: inline;" />
			</span>
			<span style=" padding-left: 10px;">
				<input type="button" onclick="preserveData(); editSalaryGrade();" value="<?php echo $lang_jobtitle_editpaygrade; ?>"  disabled="disabled" style="display: inline;" />
			</span>
		</div>
		<div class="controlContainer" style="padding-top: 20px;">
			<div style="float: left;">
				<label for="cmbAssEmploymentStatus" class="controlLabel"><span class="success">#</span>&nbsp;<?php echo $lang_jobtitle_empstat; ?></label>
				<select disabled="disabled" size="3" name="cmbAssEmploymentStatus" id="cmbAssEmploymentStatus" style="width:150px; height: 50px;">
				<?php 
					$assEmploymentStat = $this->popArr['assEmploymentStat'];
					
					for($c=0;$assEmploymentStat && count($assEmploymentStat)>$c;$c++) {
						echo "<option value='" .$assEmploymentStat[$c][0]. "'>" .$assEmploymentStat[$c][1]. "</option>";
					}
				?>
				</select>
			</div>
			<div style="padding-left: 10px; padding-right: 10px; float: left;">
				<input type="button" disabled="disabled" name="butAssEmploymentStatus" onclick="assignEmploymentStatus();" value="< <?php echo $lang_compstruct_add; ?>" style="width: 90px;" />
				<br /><br />
				<input type="button" disabled="disabled" name="butUnAssEmploymentStatus" onclick="unAssignEmploymentStatus();" value="<?php echo $lang_Leave_Common_Remove; ?> >" style="width: 90px;" />
			</div>
			<div style="float: none;">
				<select disabled="disabled" size="3" name="cmbUnAssEmploymentStatus" id="cmbUnAssEmploymentStatus" style="width:150px; height: 50px;">
				<?php 
					$unAssEmploymentStat = $this->popArr['unAssEmploymentStat'];

				    for($c=0;$unAssEmploymentStat && count($unAssEmploymentStat)>$c;$c++) {
						echo "<option value='" .$unAssEmploymentStat[$c][0]. "'>" .$unAssEmploymentStat[$c][1]. "</option>";
					}
				?>
				</select>
			</div>
		</div>  
		<div class="controlContainer" style="padding-top: 20px; padding-left: 10px;">
			<input type="button" disabled="disabled" value="<?php echo $lang_jobtitle_addempstat; ?>" onclick="xajax_showAddEmpStatForm();" />
			<br /><br />
			<input type="button" disabled="disabled" value="<?php echo $lang_jobtitle_editempstat; ?>" onclick="showEditForm();" />
		</div>
		<div id="layerEmpStat" class="roundbox" style="visibility: hidden; width: 400px; height: 80px;">
			<input type="hidden" name="txtEmpStatID" />
			<div>
				<label for="txtEmpStatDesc" class="controlLabel" style="padding-left: 0px"><?php echo $lang_jobtitle_empstat; ?></label>
				<input type="text" name="txtEmpStatDesc" id="txtEmpStatDesc" disabled="disabled" style="width: 200px" />
			</div>
			<div id="buttonLayer" style="text-align: right; padding-right: 10px; padding-top: 10px;"></div>
		</div>
		<div class="controlContainer" style="padding-top: 20px;">
			<img 
				src="<?php echo $themeDir; ?>/pictures/btn_edit.gif" 
				title="Edit" 
				onmouseout="mout();" 
				onmouseover="mover();" 
				name="Edit" 
				onclick="<?php	if($locRights['edit']) { ?>edit();<?php	} else { ?>alert('<?php echo $lang_Common_AccessDenied;?>');<?php	}  ?>" />
		
			<img 
				src="<?php echo $themeDir; ?>/pictures/btn_clear.gif" 
				onmouseout="this.src='<?php echo $themeDir; ?>/pictures/btn_clear.gif';" 
				onmouseover="this.src='<?php echo $themeDir; ?>/pictures/btn_clear_02.gif';" 
				onclick="" />
		</div>
	</form>
</div>
<div style="padding-top: 10px;">
	<span id="notice"><?php echo preg_replace('/#star/', '<span class="error">*</span>', $lang_Commn_RequiredFieldMark); ?>.</span>
	<br />
	<span id="notice"><span class="success">#</span> = <?php echo $lang_jobtitle_emstatExpl; ?></span>
</div>
</form>
<?php } ?>

<script type="text/javascript">
<!--
   	if (document.getElementById && document.createElement) {
		initOctopus();
	}
-->
</script>

</body>
</html>

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

//xajax headers
require_once ROOT_PATH . '/lib/confs/sysConf.php';
require_once ROOT_PATH . '/lib/controllers/EmpViewController.php';

    $sysConst = new sysConf();
    $locRights=$_SESSION['localRights'];

    $arrMStat = $this->popArr['arrMStat'];

function populateStates($value) {

    $view_controller = new ViewController();
    $provlist = $view_controller->xajaxObjCall($value,'LOC','province');

    $objResponse = new xajaxResponse();
    $xajaxFiller = new xajaxElementFiller();
    if ($provlist) {
        $objResponse->addAssign('lrState','innerHTML','<select name="txtState" id="txtState"><option value="0">--- Select ---</option></select>');
        $objResponse = $xajaxFiller->cmbFillerById($objResponse,$provlist,1,'frmGenInfo.lrState','txtState');

    } else {
        $objResponse->addAssign('lrState','innerHTML','<input type="text" name="txtState" id="txtState" value="">');
    }
    $objResponse->addAssign('status','innerHTML','');

return $objResponse->getXML();
}

function populateDistrict($value) {

    $emp_view_controller = new EmpViewController();
    $dislist = $emp_view_controller->xajaxObjCall($value,'EMP','district');

    $objResponse = new xajaxResponse();
    $xajaxFiller = new xajaxElementFiller();
    $response = $xajaxFiller->cmbFiller($objResponse,$dislist,1,'frmEmp','cmbCity');
    $response->addAssign('status','innerHTML','');

return $response->getXML();
}

//function assEmpStat($value) {
//
//    $view_controller = new ViewController();
//    $empstatlist = $view_controller->xajaxObjCall($value,'JOB','assigned');
//
//    $objResponse = new xajaxResponse();
//    $xajaxFiller = new xajaxElementFiller();
//    $response = $xajaxFiller->cmbFiller($objResponse,$empstatlist,0,'frmEmp','cmbType',3);
//    $response->addAssign('status','innerHTML','');
//
//return $response->getXML();
//}

function fetchJobSpecInfo($value) {
    $view_controller = new ViewController();
    $response = new xajaxResponse();
    $jobSpec = $view_controller->getJobSpecForJob($value);
    if (empty($jobSpec)) {
        $jobSpecName = '';
        $jobSpecDuties = '';
    } else {
        $jobSpecName = CommonFunctions::escapeHtml($jobSpec->getName());
        $jobSpecDuties = nl2br(CommonFunctions::escapeHtml($jobSpec->getDuties()));
    }

    $response->addAssign('jobSpecName','innerHTML', $jobSpecName);
    $response->addAssign('jobSpecDuties','innerHTML', $jobSpecDuties);

    $response->addAssign('status','innerHTML','');
return $response->getXML();
}

function getUnAssMemberships($mtype) {

    $emp_view_controller = new EmpViewController();

    $value[0] = $_GET['id'];
    $value[1] = $mtype;

    $unAssMembership = $emp_view_controller->xajaxObjCall($value,'MEM','unAssMembership');

    $response = new xajaxResponse();
    $xajaxFiller = new xajaxElementFiller();
    $response = $xajaxFiller->cmbFiller($response,$unAssMembership,0,'frmEmp','cmbMemCode',3);
    $response->addAssign('status','innerHTML','');

return $response->getXML();
}

function getMinMaxCurrency($value, $salGrd) {

    $emp_view_controller = new EmpViewController();
    $common_func = new CommonFunctions();

    $temp[0] = $salGrd;
    $temp[1] = $_GET['id'];

    $currlist = $emp_view_controller->xajaxObjCall($temp,'BAS','currency');

    for($c=0; $c < count($currlist);$c++)
        if(isset($currlist[$c][2]) && $currlist[$c][2] == $value)
            break;

    $response = new xajaxResponse();

    if ($value === '0') {
        $response->addAssign('txtMinCurrency','value', '');
        $response->addAssign('divMinCurrency','innerHTML', '-N/A-');
        $response->addAssign('txtMaxCurrency','value', '');
        $response->addAssign('divMaxCurrency','innerHTML', '-N/A-');

    } else {
        $response->addAssign('txtMinCurrency','value',$currlist[$c][3]);
        $response->addAssign('divMinCurrency','innerHTML', $common_func->formatSciNO($currlist[$c][3]));
        $response->addAssign('txtMaxCurrency','value', $currlist[$c][5]);
        $response->addAssign('divMaxCurrency','innerHTML', $common_func->formatSciNO($currlist[$c][5]));
    }
return $response->getXML();
}
$GLOBALS['lang_hremp_SelectCurrency'] = $lang_hremp_SelectCurrency;
function getUnAssignedCurrencyList($payGrade) {
    $emp_view_controller = new EmpViewController();
    $empId = $_GET['id'];

    $temp[] = $payGrade;
    $temp[] = $empId;
    if($payGrade){
        $currlist = $emp_view_controller->xajaxObjCall($temp,'BAS','currency');
    }else{
        $currlist[0][0]  = $GLOBALS['lang_hremp_SelectCurrency'] ;
        $currlist[0][2]  = "0";
    }

    $response = new xajaxResponse();
    $xajaxFiller = new xajaxElementFiller();
    $xajaxFiller->setDefaultOptionName('select_currency');
    $response = $xajaxFiller->cmbFiller2($response, $currlist, 0, 2, 'frmEmp', 'cmbCurrCode', 0);
    $response->addAssign('status','innerHTML','');

    return $response->getXML();
}


$GLOBALS['lang_Common_Select'] = $lang_Common_Select;
$GLOBALS['lang_hremp_ErrorAssigningLocation'] = $lang_hremp_ErrorAssigningLocation;

/**
 * Assign location to employee
 * @param string $locCode Location code
 */
function assignLocation($locCode) {

    $empViewController = new EmpViewController();
    $result = $empViewController->assignLocation($_GET['id'], $locCode);

    $response = new xajaxResponse();
    if ($result) {
        $response->addScript('onLocationAssign("' . $locCode. '");');
    } else {
        $response->addScript('alert("' . $GLOBALS['lang_hremp_ErrorAssigningLocation'] .'");');
    }

    $xajaxFiller = new xajaxElementFiller();
    $response->addAssign('status','style','display:none;');
    $response->addScript('enableLocationLinks();');

    return $response->getXML();
}

/**
 * Remove location from employee
 * @param string $locCode Location code
 */
function removeLocation($locCode) {

    $empViewController = new EmpViewController();
    $result = $empViewController->removeLocation($_GET['id'], $locCode);

    $response = new xajaxResponse();
    if ($result) {
       $response->addScript('onLocationRemove("' . $locCode. '");');
    } else {
        $response->addScript('alert("' . $GLOBALS['lang_hremp_ErrorAssigningLocation'] .'");');
    }

    $xajaxFiller = new xajaxElementFiller();
    $response->addAssign('status','style','display:none;');
    $response->addScript('enableLocationLinks();');

    return $response->getXML();
}

$objAjax = new xajax();
$objAjax->registerFunction('populateStates');
$objAjax->registerFunction('populateDistrict');
//$objAjax->registerFunction('assEmpStat');
$objAjax->registerFunction('fetchJobSpecInfo');
$objAjax->registerFunction('getUnAssMemberships');
$objAjax->registerFunction('getMinMaxCurrency');
$objAjax->registerFunction('getUnAssignedCurrencyList');
$objAjax->registerFunction('assignLocation');
$objAjax->registerFunction('removeLocation');

$objAjax->processRequests();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>OrangeHRM - Employee Details</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="../../scripts/archive.js"></script>
<?php
$objAjax->printJavascript();
include ROOT_PATH."/lib/common/calendar.php"; ?>
<script language="JavaScript">
function MM_reloadPage(init) {  //reloads the window if Nav4 resized
  if (init==true) with (navigator) {if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {
    document.MM_pgW=innerWidth; document.MM_pgH=innerHeight; onresize=MM_reloadPage; }}
  else if (innerWidth!=document.MM_pgW || innerHeight!=document.MM_pgH) location.reload();
}

MM_reloadPage(true);

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_showHideLayers() { //v6.0
  var z,i,p,v,obj,args=MM_showHideLayers.arguments;
  for (i=0; i<(args.length-2); i+=3) if ((obj=MM_findObj(args[i]))!=null) { v=args[i+2];
    if (obj.style) { obj=obj.style; z=(v=='show')?3:(v=='hide')?2:2; v=(v=='show')?'visible':(v=='hide')?'hidden':v; }
    obj.visibility=v; obj.zIndex=z; }
}

function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function alpha(txt)
{
    var flag=true;
    var i,code;

    if(txt.value=='')
        return false;

    for(i=0;txt.value.length>i;i++)
    {
        code=txt.value.charCodeAt(i);

        if (code>=48 && code<=57) {
            flag=false;
            break;
        } else {
        flag=true;
        }

    }

  return flag;
}

function numeric(txt) {
var flag=true;
var i,code;

if(txt.value=="")
   return false;

for(i=0;txt.value.length>i;i++)    {
    code=txt.value.charCodeAt(i);
    if(code>=48 && code<=57)
    flag=true;
    else
    {
    flag=false;
    break;
    }
    }
return flag;
}

function addEmpMain() {

    var cnt = document.frmEmp.txtEmpLastName;
    if(!(cnt.value == '') && !alpha(cnt) && !confirm('<?php echo $lang_Error_LastNameNumbers?>')) {
        cnt.focus();
        return;
    }  if (cnt.value == '') {
        alert('<?php echo $lang_Error_LastNameEmpty?>');
        cnt.focus();
        return;
    }

    var cnt = document.frmEmp.txtEmpFirstName;
    if(!(cnt.value == '') && !alpha(cnt) && !confirm('<?php echo $lang_Error_FirstNameNumbers?>')) {
        cnt.focus();
        return;
    } else if (cnt.value == '') {
        alert('<?php echo $lang_Error_FirstNameEmpty?>');
        cnt.focus();
        return;
    }

    var cnt = document.frmEmp.txtEmpMiddleName;
    if(!(cnt.value == '') && !alpha(cnt) && !confirm('<?php echo $lang_Error_MiddleNameNumbers?>')) {
        cnt.focus();
        return;
    } else if ((cnt.value == '') && !confirm('<?php echo $lang_Error_MiddleNameEmpty?>')) {
        cnt.focus();
        return;
    }

    document.frmEmp.sqlState.value = "NewRecord";
    document.frmEmp.submit();
}

    function goBack() {

        location.href ="./CentralController.php?reqcode=<?php echo $this->getArr['reqcode']?>&VIEW=MAIN";
    }

function mout() {
    var Edit = document.getElementById("btnEdit");
    if(document.frmEmp.EditMode.value=='1')
        Edit.src='../../themes/beyondT/pictures/btn_save.gif';
    else
        Edit.src='../../themes/beyondT/pictures/btn_edit.gif';
}

function mover() {
    var Edit = document.getElementById("btnEdit");
    if(document.frmEmp.EditMode.value=='1')
        Edit.src='../../themes/beyondT/pictures/btn_save_02.gif';
    else
        Edit.src='../../themes/beyondT/pictures/btn_edit_02.gif';
}

function editEmpMain() {

    var lockedEl = Array(100);
    var lockEmpCont = false;

    var Edit = document.getElementById("btnEdit");

    if(document.frmEmp.EditMode.value=='1') {
        updateEmpMain();
        return;
    }

    var frm=document.frmEmp;

    for (var i=0; i < frm.elements.length; i++) {
        if (frm.elements[i].type == "hidden")
            frm.elements[i].disabled=false;

        <?php

            $supervisorEMPMode = false;
            if ((isset($_SESSION['isSupervisor']) && $_SESSION['isSupervisor']) && (isset($_GET['reqcode']) && ($_GET['reqcode'] === "EMP")) ) {
                $supervisorEMPMode = true;
            }

            /* If admin or supervisor in EMP page */
            if ((isset($_SESSION['isAdmin']) && ($_SESSION['isAdmin'] == 'Yes')) || $supervisorEMPMode ) { ?>

        if (frm.elements[i].className.indexOf('noDefaultEdit') == -1) {
        frm.elements[i].disabled=false;
        }

        <?php } ?>
    }

        <?php

          $allowLocationDelete = false;
          $allowLocationEdit = false;
          if ($supervisorEMPMode) {
              $allowLocationDelete = true;
              $allowLocationEdit = true;
          } else if (isset($_SESSION['isAdmin']) && ($_SESSION['isAdmin'] == 'Yes')) {
              $allowLocationDelete = $locRights['delete'];
              $allowLocationEdit = $locRights['edit'];
          }

          // display location modifying link
          if ($allowLocationEdit) {
         ?>
            var addLocationLayer = document.getElementById("addLocationLayer");
            if (addLocationLayer) {
                addLocationLayer.style.display = 'block';
            }
        <?php } ?>
        <?php
          // Show deletion check boxes
          if ($allowLocationDelete) {
         ?>
            var elms = YAHOO.util.Dom.getElementsByClassName('locationDeleteChkBox');
            // loop over all the elements
            for(var i=0,j=elms.length;i<j;i++){
                elms[i].style.display = 'block';
            }

        <?php } ?>

        <?php
        /* form elements disabled only for supervisor mode */
        if ($supervisorEMPMode) { ?>

            disableArr = new Array(    );

            for (j=0; j<disableArr.length; j++) {
                if (frm[disableArr[j]]) {
                    frm[disableArr[j]].disabled = true;
                }
            }

        <?php } ?>


        <?php if (isset($_GET['reqcode']) && ($_GET['reqcode'] === "ESS")) { ?>
        enableArr = new Array(    'cmbRepEmpID',
                                    'cmbRepMethod',
                                    'cmbRepType',
                                    'txtBasSal',
                                    'cmbCurrCode',
                                    'txtEmpFirstName',
                                'txtEmpMiddleName',
                                'txtEmpLastName',
                                'txtEmpNickName',
                                "txtOtherID",
                                'cmbCountry',
                                'txtEConName',
                                "btnBrowser",
                                "chkSmokeFlag",
                                "txtMilitarySer",
                                "cmbNation",
                                "cmbMarital",
                                "cmbEthnicRace",
                                "optGender",
                                "btnLicExpDate",
                                "txtLicExpDate",
                                "btnDOB",
                                "DOB",
                                "txtState",
                                "cmbCity",
                                "txtHmTelep",
                                "txtWorkTelep",
                                "txtOtherEmail",
                                "txtStreet1",
                                "txtStreet2",
                                "txtzipCode",
                                "txtMobile",
                                "txtWorkEmail",
                                "txtEConRel",
                                "txtEConHmTel",
                                "txtEConMobile",
                                "txtEConWorkTel",
                                "txtEConName");

        for (j=0; j<enableArr.length; j++) {
            if (frm[enableArr[j]]) {
                if (frm[enableArr[j]].length) {
                    for (i=0; i<frm[enableArr[j]].length; i++) {
                        frm[enableArr[j]][i].disabled = false;
                    }
                }
                frm[enableArr[j]].disabled = false;
            }
        }

        <?php } ?>

    document.getElementById("btnClear").disabled = false;
    Edit.src="../../themes/beyondT/pictures/btn_save_02.gif";
    Edit.title="Save";
    document.frmEmp.EditMode.value='1';
}

function updateEmpMain() {
    var cnt = document.frmEmp.txtEmpLastName;
    if(!(cnt.value == '') && !alpha(cnt) && !confirm('<?php echo $lang_Error_LastNameNumbers?>')) {
        cnt.focus();
        return;
    }  if (cnt.value == '') {
        alert('<?php echo $lang_Error_LastNameEmpty?>');
        cnt.focus();
        return;
    }

    var cnt = document.frmEmp.txtEmpFirstName;
    if(!(cnt.value == '') && !alpha(cnt) && !confirm('<?php echo $lang_Error_FirstNameNumbers?>')) {
        cnt.focus();
        return;
    } else if (cnt.value == '') {
        alert('<?php echo $lang_Error_FirstNameEmpty?>');
        cnt.focus();
        return;
    }

    var cnt = document.frmEmp.txtEmpMiddleName;

    if((document.frmEmp.main.value == 1) && !(cnt.value == '') && !alpha(cnt) && !confirm('<?php echo $lang_Error_MiddleNameNumbers?>')) {
        cnt.focus();
        return;
    } else if ((document.frmEmp.main.value == 1) && (cnt.value == '') && !confirm('<?php echo $lang_Error_MiddleNameEmpty?>')) {
        cnt.focus();
        return;
    }

    // contact details validation
    if( document.frmEmp.contactFlag.value == '1' ){

        // check work email
        var workEmail = document.frmEmp.txtWorkEmail.value;
        if (workEmail != '') {
            if( !checkEmail(workEmail) ){
                alert ('<?php echo $lang_Errro_WorkEmailIsNotValid; ?>');
                return false;
            }
        }

        // txtOtherEmail
        var otherEmail = document.frmEmp.txtOtherEmail.value;
        if (otherEmail != '') {
            if( !checkEmail(otherEmail) ){
                alert ('<?php echo $lang_Errro_OtherEmailIsNotValid; ?>');
                return false;
            }
        }
    }

    if ( (document.frmEmp.txtzipCode.value != '') && (!numbers(document.frmEmp.txtzipCode)) ){
        if (!confirm ("<?php echo $lang_Error_CompStruct_ZipInvalid; ?>".replace(/#characterList/, nonNumbers(document.frmEmp.txtzipCode))+". <?php echo $lang_Error_DoYouWantToContinue; ?>") ) {
            document.frmEmp.txtzipCode.focus();
            return;
        }
    }

    var cntrl = document.frmEmp.txtHmTelep;
    if(cntrl.value != '' && !checkPhone(cntrl)) {
        alert('<?php echo "$lang_hremp_hmtele : $lang_hremp_InvalidPhone"; ?>');
        cntrl.focus();
        return;
    }

    var cntrl = document.frmEmp.txtMobile;
    if(cntrl.value != '' && !checkPhone(cntrl)) {
        alert('<?php echo "$lang_hremp_mobile : $lang_hremp_InvalidPhone"; ?>');
        cntrl.focus();
        return;
    }

    var cntrl = document.frmEmp.txtWorkTelep;
    if(cntrl.value != '' && !checkPhone(cntrl)) {
        alert('<?php echo "$lang_hremp_worktele : $lang_hremp_InvalidPhone"; ?>');
        cntrl.focus();
        return;
    }

    var cntrl = document.frmEmp.taxFederalExceptions;
    if(cntrl.value != '' && !numbers(cntrl)) {
        alert('<?php echo "$lang_hrEmpMain_FederalIncomeTax $lang_hrEmpMain_TaxExemptions : $lang_Error_FieldShouldBeNumeric"; ?>');
        cntrl.focus();
        return;
    }

    var cntrl = document.frmEmp.taxStateExceptions;
    if(cntrl.value != '' && !numbers(cntrl)) {
        alert('<?php echo "$lang_hrEmpMain_StateIncomeTax $lang_hrEmpMain_TaxExemptions : $lang_Error_FieldShouldBeNumeric"; ?>');
        cntrl.focus();
        return;
    }

    document.getElementById("cmbProvince").value=document.getElementById("txtState").value;
    document.frmEmp.sqlState.value = "UpdateRecord";
    document.frmEmp.submit();
}

function hideLoad() {
    document.getElementById("status").innerHTML = '';
}

<?php if ((isset($this->getArr['capturemode'])) && ($this->getArr['capturemode'] == 'updatemode')) {     ?>
        function reLoad() {
            location.href ="<?php echo $_SERVER['PHP_SELF']?>?id=<?php echo $this->getArr['id']?>&capturemode=updatemode&reqcode=<?php echo $this->getArr['reqcode']?>";
        }
<?php } ?>

 function qCombo(lblPane) {

    document.frmEmp.pane.value=lblPane;
    document.frmEmp.submit();
}

function chgPane(lblPane) {

    document.frmEmp.pane.value=lblPane;
}

function qshowpane() {

    var opt=eval(document.frmEmp.pane.value);
    displayLayer(opt);
}

function displayLayer(panelNo) {

            if((panelNo != 1 && document.frmEmp.personalFlag.value == '1') || (panelNo != 2 && document.frmEmp.jobFlag.value == '1') || (panelNo != 4 && document.frmEmp.contactFlag.value == '1') || (panelNo != 18 && document.frmEmp.taxFlag.value == '1') || (panelNo != 20 && document.frmEmp.customFlag.value == '1')) {

                if(confirm("<?php echo $lang_Error_ChangePane?>")) {
                    editEmpMain();
                    if( !updateEmpMain() ){
                        return;
                    }
                } else {
                    document.frmEmp.personalFlag.value=0;
                    document.frmEmp.jobFlag.value=0;
                    document.frmEmp.contactFlag.value=0;
                    document.frmEmp.taxFlag.value=0;
                    document.frmEmp.customFlag.value=0;
                }
            }

        // styles of sub menu items
              var IconStyles = new Array("personalLink", "jobLink", "dependantsLink", "contactLink", "emergency_contactLink", "attachmentsLink", "cash_benefitsLink", "non_cash_benefitsLink", "educationLink", "immigrationLink", "languagesLink", "licenseLink", "membershipLink", "paymentLink", "report-toLink", "skillsLink", "work_experienceLink", "taxLink", "directDebitLink", "customLink");


        // highlight the current selected item
          for (i=0; i<IconStyles.length; i++){
              var Style = IconStyles[i];
              obj=MM_findObj(Style);
              if (obj && obj.style ){
                  if (i == panelNo - 1){
                      obj.style.fontWeight="bold";
                  } else {
                      obj.style.fontWeight="normal";
                  }
              }
          }

    switch(panelNo) {
            case 1 : MM_showHideLayers('hidebg','','hide','personal','','show','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //personal
            case 2 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','show','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //job
            case 3 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','show','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //dependents
            case 4 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','show','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //contacts
            case 5 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','show','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //emg-contacts
            case 6 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','show','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //attachements
            case 7 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','show','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //cash-benefits
            case 8 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','show','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //noncash-benefits
            case 9 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','show','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //education
            case 10 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','show','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //immigration
            case 11 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','show','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //languages
            case 12 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','show','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //licenses
            case 13 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','show','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //memberships
            case 14 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','show','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //payments
            case 15 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','show','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //report-to
            case 16 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','show','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //skills
            case 17 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','show', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //work-experiance
            case 18 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'show', 'direct-debit', '', 'hide', 'custom', '', 'hide'); break; //tax
            case 19 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'show', 'custom', '', 'hide'); break; //direct-debit
            case 20 : MM_showHideLayers('hidebg','','hide','personal','','hide','job','','hide','dependents','','hide','contacts','','hide','emgcontacts','','hide','attachments','','hide','cash-benefits','','hide','noncash-benefits','','hide','education','','hide','immigration','','hide','languages','','hide','licenses','','hide','memberships','','hide','payments','','hide','report-to','','hide','skills','','hide','work-experiance','','hide', 'tax', '', 'hide', 'direct-debit', '', 'hide', 'custom', '', 'show'); break; //custom
    }

    document.frmEmp.pane.value = panelNo;
}

function setUpdate(opt) {

        switch(eval(opt)) {
            case 0 : document.frmEmp.main.value=1; break;
            case 1 : document.frmEmp.personalFlag.value=1; break;
            case 2 : document.frmEmp.jobFlag.value=1; break;
            case 4 : document.frmEmp.contactFlag.value=1; break;
            case 18: document.frmEmp.taxFlag.value=1; break;
            case 20: document.frmEmp.customFlag.value=1; break;
        }
        document.frmEmp.pane.value = opt;
}


function popPhotoHandler() {
    alert('<?php echo DOL_URL_ROOT ?>/hrm/orange/templates/hrfunct/photohandler.php?id=<?php echo isset($this->getArr['id']) ? $this->getArr['id'] : ''?>');
    var popup=window.open('<?php echo DOL_URL_ROOT ?>/hrm/orange/templates/hrfunct/photohandler.php?id=<?php echo isset($this->getArr['id']) ? $this->getArr['id'] : ''?>','Photo','height=275,width=250');
    if(!popup.opener) popup.opener=self;
    popup.focus()
}

function resetAdd(panel, add) {
    document.frmEmp.action = document.frmEmp.action;
    document.frmEmp.pane.value = panel;
    document.frmEmp.txtShowAddPane.value = add;
    document.frmEmp.submit();
}

function showAddPane(paneName) {
    YAHOO.OrangeHRM.container.wait.show();

    addPane = document.getElementById('addPane'+paneName);
    editPane = document.getElementById('editPane'+paneName);
    parentPane = document.getElementById('parentPane'+paneName);

    if (addPane && addPane.style) {
        addPane.style.display = tableDisplayStyle;
    } else {
        resetAdd(document.frmEmp.pane.value, paneName);
        return;
    }

    if (editPane && parentPane) {
        parentPane.removeChild(editPane);
    }

    YAHOO.OrangeHRM.container.wait.hide();
}

tableDisplayStyle = "table";
</script>
<!--[if IE]>
<script type="text/javascript">
    tableDisplayStyle = "block";
</script>
<![endif]-->

<link href="../../themes/<?php echo $styleSheet;?>/css/style.css" rel="stylesheet" type="text/css">
<style type="text/css">@import url("../../themes/<?php echo $styleSheet;?>/css/hrEmpMain.css"); </style>
<style type="text/css">@import url("../../themes/<?php echo $styleSheet;?>/css/essMenu.css"); </style>
<style type="text/css">
    <!--

    table.historyTable th {
        border-width: 0px;
        padding: 3px 3px 3px 5px;
        text-align: left;
    }
    table.historyTable td {
        border-width: 0px;
        padding: 3px 3px 3px 5px;
        text-align: left;
    }

    .locationDeleteChkBox {
        padding:2px 4px 2px 4px;
        border-style: solid;
        border-width: thin;
        display:block;
    }

    -->
</style>

</head>
<body onLoad="hideLoad();">
<script type="text/javascript">
  YAHOO.OrangeHRM.container.init();
</script>
<?php
 if (!isset($this->getArr['pane'])) {
    $this->getArr['pane'] = 1;
 };
 if (!isset($this->postArr['pane'])) {
    $this->postArr['pane'] = $this->getArr['pane'];
 };
 ?>
<div id="cal1Container"></div>
<table width='100%' cellpadding='0' cellspacing='0' border='0'>
  <tr>
    <td valign='top'>&nbsp; </td>
    <td width='100%'><h2 align="center"><?php echo $lang_empview_EmployeeInformation; ?></h2></td>
    <td valign='top' align='right' nowrap style='padding-top:3px; padding-left: 5px;'>
    <b><div align="right" id="status" style="display: none;"><img src="../../themes/beyondT/icons/loading.gif" width="20" height="20" style="vertical-align:bottom;"/> <span style="vertical-align:text-top"><?php echo $lang_Common_LoadingPage; ?>...</span></div></b></td>
  </tr>
</table>

<?php    if ((isset($this->getArr['capturemode'])) && ($this->getArr['capturemode'] == 'addmode')) { ?>
<form name="frmEmp" id="frmEmp" method="post" action="<?php echo $_SERVER['PHP_SELF']?>?reqcode=<?php echo $this->getArr['reqcode']?>&capturemode=<?php echo $this->getArr['capturemode']?>" enctype="multipart/form-data">
<?php
    } elseif ((isset($this->getArr['capturemode'])) && ($this->getArr['capturemode'] == 'updatemode')) {
    $edit = $this->popArr['editMainArr'];
?>
<form name="frmEmp" id="frmEmp" method="post" action="<?php echo $_SERVER['PHP_SELF']?>?id=<?php echo $this->getArr['id']?>&reqcode=<?php echo $this->getArr['reqcode']?>&capturemode=<?php echo $this->getArr['capturemode']?>" enctype="multipart/form-data">
<?php } ?>

<input type="hidden" name="sqlState">
<input type="hidden" name="pane" value="<?php echo (isset($this->postArr['pane']) && $this->postArr['pane']!='')?$this->postArr['pane']:''?>">
<input type="hidden" name="txtShowAddPane" >

<input type="hidden" name="main" value="<?php echo isset($this->postArr['main'])? $this->postArr['main'] : '0'?>">
<input type="hidden" name="personalFlag" value="<?php echo isset($this->postArr['personalFlag'])? $this->postArr['personalFlag'] : '0'?>">
<input type="hidden" name="jobFlag" value="<?php echo isset($this->postArr['jobFlag'])? $this->postArr['jobFlag'] : '0'?>">

<input type="hidden" name="dependentFlag" value="<?php echo isset($this->postArr['dependentFlag'])? $this->postArr['dependentFlag'] : '0'?>">
<input type="hidden" name="childrenFlag" value="<?php echo isset($this->postArr['childrenFlag'])? $this->postArr['childrenFlag'] : '0'?>">
<input type="hidden" name="contactFlag" value="<?php echo isset($this->postArr['contactFlag'])? $this->postArr['contactFlag'] : '0'?>">
<input type="hidden" name="econtactFlag" value="<?php echo isset($this->postArr['econtactFlag'])? $this->postArr['econtactFlag'] : '0'?>">
<input type="hidden" name="cash-benefitsFlag" value="<?php echo isset($this->postArr['cash-benefitsFlag'])? $this->postArr['cash-benefitsFlag'] : '0'?>">
<input type="hidden" name="noncash-benefitsFlag" value="<?php echo isset($this->postArr['noncash-benefitsFlag'])? $this->postArr['noncash-benefitsFlag'] : '0'?>">
<input type="hidden" name="educationFlag" value="<?php echo isset($this->postArr['educationFlag'])? $this->postArr['educationFlag'] : '0'?>">
<input type="hidden" name="immigrationFlag" value="<?php echo isset($this->postArr['immigrationFlag'])? $this->postArr['immigrationFlag'] : '0'?>">
<input type="hidden" name="languageFlag" value="<?php echo isset($this->postArr['languageFlag'])? $this->postArr['languageFlag'] : '0'?>">
<input type="hidden" name="licenseFlag" value="<?php echo isset($this->postArr['licenseFlag'])? $this->postArr['licenseFlag'] : '0'?>">
<input type="hidden" name="membershipFlag" value="<?php echo isset($this->postArr['membershipFlag'])? $this->postArr['membershipFlag'] : '0'?>">
<input type="hidden" name="paymentFlag" value="<?php echo isset($this->postArr['paymentFlag'])? $this->postArr['paymentFlag'] : '0'?>">
<input type="hidden" name="report-toFlag" value="<?php echo isset($this->postArr['report-toFlag'])? $this->postArr['report-toFlag'] : '0'?>">
<input type="hidden" name="skillsFlag" value="<?php echo isset($this->postArr['skillsFlag'])? $this->postArr['skillsFlag'] : '0'?>">
<input type="hidden" name="work-experianceFlag" value="<?php echo isset($this->postArr['work-experianceFlag'])? $this->postArr['work-experianceFlag'] : '0'?>">
<input type="hidden" name="taxFlag" value="<?php echo isset($this->postArr['taxFlag'])? $this->postArr['taxFlag'] : '0'?>">
<input type="hidden" name="direct-debitFlag" value="<?php echo isset($this->postArr['direct-debitFlag'])? $this->postArr['direct-debitFlag'] : '0'?>">
<input type="hidden" name="customFlag" value="<?php echo isset($this->postArr['customFlag'])? $this->postArr['customFlag'] : '0'?>">
<input type="hidden" name="attSTAT" value="">
<input type="hidden" name="EditMode" value="<?php echo isset($this->postArr['EditMode'])? $this->postArr['EditMode'] : '0'?>">

<?php
    if (isset($this->getArr['message'])) {

        $expString  = $this->getArr['message'];
        $col_def = CommonFunctions::getCssClassForMessage($expString);
?>
    <p align="right">
        <font class="<?php echo $col_def?>" size="-1" face="Verdana, Arial, Helvetica, sans-serif;" style="margin-right:10px">
            <?php echo eval('return $lang_empview_'.$expString.';'); ?>
        </font>
    </p>
<?php } ?>

<?php if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'addmode') { ?>

<table width="550" align="center" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td class="tableTopLeft"></td>
                  <td class="tableTopMiddle"></td>
                  <td class="tableTopRight"></td>
                </tr>
                <tr>
                  <td class="tableMiddleLeft"></td>
                  <td><table width="100%" border="0" cellpadding="5" cellspacing="0" class="">
            <tr>

                <td><?php echo $lang_Commn_code; ?></td>
                <td>
                    <input name="txtEmployeeId" type="text" value="<?php echo $this->popArr['newID']?>" maxlength="50">
                    </td>
            </tr>
            <tr>
                <td><font color=#ff0000>*</font> <?php echo $lang_hremp_EmpLastName?></td>
                <td> <input type="text" name="txtEmpLastName" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtEmpLastName']))?$this->postArr['txtEmpLastName']:''?>"></td>
                <td>&nbsp;</td>
                <td><font color=#ff0000>*</font> <?php echo $lang_hremp_EmpFirstName?></td>
                <td> <input type="text" name="txtEmpFirstName" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtEmpFirstName']))?$this->postArr['txtEmpFirstName']:''?>"></td>
            </tr>
            <tr>
                <td><?php echo $lang_hremp_EmpMiddleName?></td>
                <td> <input type="text" name="txtEmpMiddleName" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtEmpMiddleName']))?$this->postArr['txtEmpMiddleName']:''?>"></td>
                <td>&nbsp;</td>
            <td><?php echo $lang_hremp_nickname?></td>
                <td> <input type="text" name="txtEmpNickName" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtEmpNickName']))?$this->postArr['txtEmpNickName']:''?>"></td>
            </tr>
            <tr>
                <td><?php echo $lang_hremp_photo?></td>
                <td>
                    <input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
                    <input type="file" name='photofile' <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['photofile']))?$this->postArr['photofile']:''?>" />
                </td>
            </tr>
                  </table></td>
                  <td class="tableMiddleRight"></td>
                </tr>
                <tr>
                  <td class="tableBottomLeft"></td>
                  <td class="tableBottomMiddle"></td>
                  <td class="tableBottomRight"></td>
                </tr>
              </table>
                <p align="center"><?php echo preg_replace('/#star/', '<span class="error">*</span>', $lang_Commn_RequiredFieldMark); ?>.</p>

    <table border="0" align="center" >
                <tr>
                </tr>
    <tr>
    <td><?php if($_GET['reqcode'] !== "ESS") {?><img title="Back" onMouseOut="this.src='../../themes/beyondT/pictures/btn_back.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_back_02.gif';"  src="../../themes/beyondT/pictures/btn_back.gif" onClick="goBack();"><?php }?></td>
    <td>
                    <?php    if (($locRights['add']) || ($_GET['reqcode'] === "ESS")) { ?>
                            <input type="image" class="button1" id="btnEdit" border="0" title="Save" onClick="addEmpMain(); return false;" onMouseOut="this.src='../../themes/beyondT/pictures/btn_save.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_save_02.gif';" src="../../themes/beyondT/pictures/btn_save.gif">

                    <?php     } else { ?>
                            <input type="image" class="button1" id="btnEdit" onClick="alert('<?php echo $lang_Common_AccessDenied;?>'); return false;" src="../../themes/beyondT/pictures/btn_save.gif">

                    <?php    } ?>
    </td>
    <td>&nbsp;</td>
    <td><input type="image" class="button1" id="btnClear" onClick="document.frmEmp.reset(); return false;" onMouseOut="this.src='../../themes/beyondT/icons/reset.gif';" onMouseOver="this.src='../../themes/beyondT/icons/reset_o.gif';" src="../../themes/beyondT/icons/reset.gif"></td>
    </tr>
    </table>

<?php } elseif(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') { ?>

<table width="100%">
<tr>
<td>
            <table width="550" align="center" border="0" cellpadding="0" cellspacing="0"><tr><td><br>&nbsp;</td></tr>
                <tr>
                  <td class="tableTopLeft"></td>
                  <td class="tableTopMiddle"></td>
                  <td class="tableTopRight"></td>
                </tr>
                <tr>
                  <td class="tableMiddleLeft"></td>
                  <td><table onClick="setUpdate(0)" onKeyPress="setUpdate(0)" width="100%" border="0" cellpadding="5" cellspacing="0" class="">
            <tr>
                <td><?php echo $lang_Commn_code?></td>
                <td><input type="hidden" name="txtEmpID" value="<?php echo $this->getArr['id']?>">
                    <input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtEmployeeId" value="<?php echo (isset($this->postArr['txtEmployeeId']))?$this->postArr['txtEmployeeId']:$edit[0][5]?>" maxlength="50">
                </td>
            </tr>
            <tr>
                <td><font color=#ff0000>*</font> <?php echo $lang_hremp_EmpLastName?></td>
                <td> <input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtEmpLastName" value="<?php echo (isset($this->postArr['txtEmpLastName']))?$this->postArr['txtEmpLastName']:$edit[0][1]?>"></td>
                <td>&nbsp;</td>
                <td><font color=#ff0000>*</font> <?php echo $lang_hremp_EmpFirstName?></td>
                <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtEmpFirstName" value="<?php echo (isset($this->postArr['txtEmpFirstName']))?$this->postArr['txtEmpFirstName']:$edit[0][2]?>"></td>
            </tr>
            <tr>
                <td><?php echo $lang_hremp_EmpMiddleName?></td>
                <td> <input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtEmpMiddleName" value="<?php echo (isset($this->postArr['txtEmpMiddleName']))?$this->postArr['txtEmpMiddleName']:$edit[0][3]?>"></td>
                <td>&nbsp;</td>
            <td><?php echo $lang_hremp_nickname?></td>
                <td> <input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtEmpNickName" value="<?php echo (isset($this->postArr['txtEmpNickName']))?$this->postArr['txtEmpNickName']:$edit[0][4]?>"></td>
            </tr><tr><td><br>&nbsp;</td></tr>
                </table></td>
                  <td class="tableMiddleRight"></td>
                </tr>
                <tr>
                  <td class="tableBottomLeft"></td>
                  <td class="tableBottomMiddle"></td>
                  <td class="tableBottomRight"></td>
                </tr>
              </table>
</td>
<td>
      <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
                  <td width="200" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
                  <td width="11"><img src="../../themes/beyondT/pictures/spacer.gif" width="1" height="12" border="0" alt=""></td>
                </tr>
                <tr>
                  <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td><table width="100%" border="0" cellpadding="5" cellspacing="0" class="">
                    <tr>
                    <td width="100%" align="center"><img width="100" height="120" src="../../templates/hrfunct/photohandler.php?id=<?php echo $this->getArr['id']?>&action=VIEW"></td>
                    </tr>
                    <tr>
                    <td width="100%" align="center"><input type="button" value="<?php echo $lang_hremp_browse; ?>" name="btnBrowser" onClick="popPhotoHandler()"></td>
                    </tr>
                  </table></td>
                  <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td><img src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                </tr>
                <tr>
                  <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
                  <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
                  <td><img src="../../themes/beyondT/pictures/spacer.gif" width="1" height="16" border="0" alt=""></td>
                </tr>
              </table>
</td>
</tr>
</table>
    <table border="0" align="center" >
    <tr>
    <td><?php if($_GET['reqcode'] !== "ESS") {?>      <img title="Back" onMouseOut="this.src='../../themes/beyondT/pictures/btn_back.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_back_02.gif';"  src="../../themes/beyondT/pictures/btn_back.gif" onClick="goBack();">
      <?php }?></td>
    <td>
<?php            if (($locRights['edit']) || ($_GET['reqcode'] === "ESS")) { ?>
                    <input type="image" class="button1" id="btnEdit" src="<?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '../../themes/beyondT/pictures/btn_save.gif' : '../../themes/beyondT/pictures/btn_edit.gif'?>" title="Edit" onMouseOut="mout();" onMouseOver="mover();" name="EditMain" onClick="editEmpMain(); return false;">
<?php            } else { ?>
                    <input type="image" class="button1" id="btnEdit" src="../../themes/beyondT/pictures/btn_edit.gif" onClick="alert('<?php echo $lang_Common_AccessDenied;?>');  return false;">
<?php            }  ?>
    </td>
    <td><input type="image" class="button1" id="btnClear" disabled src="../../themes/beyondT/icons/reset.gif" onMouseOut="this.src='../../themes/beyondT/icons/reset.gif';" onMouseOver="this.src='../../themes/beyondT/icons/reset_o.gif';" onClick="reLoad();  return false;" ></td>
    </tr>
    </table>
<br>



    <table border="0">
        <tr>
            <td>
            <table border="0" align="center" cellpadding="1" cellspacing="1">
                <tr class="mnuPIM">

          <td id="personalLink"><a href="javascript:displayLayer(1)">
            <?php echo $lang_pim_tabs_Personal; ?>
            </a></td>

          <td id="contactLink"><a href="javascript:displayLayer(4)">
            <?php echo $lang_pim_tabs_Contact; ?>
            </a></td>

          <td id="emergency_contactLink"><a href="javascript:displayLayer(5)">
            <?php echo $lang_pim_tabs_EmergencyContacts; ?>
            </a></td>

          <td id="dependantsLink"><a href="javascript:displayLayer(3)">
            <?php echo $lang_pim_tabs_Dependents; ?>
            </a></td>

          <td id="immigrationLink"><a href="javascript:displayLayer(10)">
            <?php echo $lang_pim_tabs_Immigration; ?>
            </a></td>


          <td id="jobLink"><a href="javascript:displayLayer(2)">
            <?php echo $lang_pim_tabs_Job; ?>
            </a></td>

          <td id="paymentLink"><a href="javascript:displayLayer(14)">
            <?php echo $lang_pim_tabs_Payments; ?>
            </a></td>

          <td id="taxLink"><a href="javascript:displayLayer(18)">
            <?php echo $lang_pim_tabs_Tax; ?>
            </a></td>

          <td id="directDebitLink"><a href="javascript:displayLayer(19)">
            <?php echo $lang_pim_tabs_DirectDebit; ?>
            </a></td>

          <td id="customLink" style="min-width: 80px;" align="center"><a href="javascript:displayLayer(20)">
            <?php echo $lang_pim_tabs_Custom; ?>
            </a></td>

          <td id="report-toLink"><a href="javascript:displayLayer(15)">
            <?php echo $lang_pim_tabs_ReportTo; ?>
            </a></td>


          <td id="work_experienceLink"><a href="javascript:displayLayer(17)">
            <?php echo $lang_pim_tabs_WorkExperience; ?>
            </a></td>

          <td id="educationLink"><a href="javascript:displayLayer(9)">
            <?php echo $lang_pim_tabs_Education; ?>
            </a></td>

          <td id="skillsLink" style="min-width: 80px;"><a href="javascript:displayLayer(16)">
            <?php echo $lang_pim_tabs_Skills; ?>
            </a></td>

          <td id="languagesLink"><a href="javascript:displayLayer(11)">
            <?php echo $lang_pim_tabs_Languages; ?>
            </a></td>

          <td id="licenseLink"><a href="javascript:displayLayer(12)">
            <?php echo $lang_pim_tabs_License; ?>
            </a></td>


          <td id="membershipLink"><a href="javascript:displayLayer(13)">
            <?php echo $lang_pim_tabs_Membership; ?>
            </a></td>

          <td id="attachmentsLink"><a href="javascript:displayLayer(6)">
            <?php echo $lang_pim_tabs_Attachments; ?>
            </a></td>
                    <!--<td id="cash_benefitsLink"><a href="javascript:displayLayer(7)">Cash Benefits</a></td>
                    <td id="non_cash_benefitsLink"><a href="javascript:displayLayer(8)">Non cash benefits</a></td>    -->
                </tr>
            </table>
            </td>
        </tr>
        <tr>
            <td align="center">

    <div id="personal" style="position:absolute; z-index:3; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] != '1') ? 'hidden' : 'visible'?>; left: 200px; top: 360px;">
    <table  border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hremppers.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>

      <table border="0" align="center" >
    <tr>
    <td><?php echo preg_replace('/#star/', '<span class="error">*</span>', $lang_Commn_RequiredFieldMark); ?>.</td>
    </tr>
    </table>
    </div>

    <div id="job" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '2') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempjob.php"); ?>
          <?php require(ROOT_PATH . "/templates/hrfunct/hrempconext.php"); ?>
          <?php require(ROOT_PATH . "/templates/hrfunct/hrempjobhistory.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="dependents" style="position:absolute; z-index:2; width: 590px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '3') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" align="center">
     <tr><td valign="top">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempdependent.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
      </td>
     <td valign="top">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempchildren.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
      </td></tr>
      </table>
    </div>
    <div id="contacts" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '4') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempcontact.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="emgcontacts" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '5') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempemgcontact.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="attachments" style="position:absolute; z-index:3; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '6') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempattachment.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="cash-benefits" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '7') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>
            Cash Benefits
          <?php /*require(ROOT_PATH . "/templates/hrfunct/EmpCashBenefits.php");*/ ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="noncash-benefits" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '8') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>
          Non-cash benefits
          <?php /*require(ROOT_PATH . "/templates/hrfunct/EmpNonCashBenefits.php");*/ ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="education" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '9') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempeducation.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="immigration" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '10') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempimmigration.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="languages" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '11') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hremplanguage.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="licenses" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '12') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hremplicenses.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="memberships" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '13') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempmembership.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="payments" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '14') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hremppayment.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="report-to" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '15') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempreportto.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="skills" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '16') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempskill.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="work-experiance" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '17') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempwrkexp.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="tax" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '18') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hremptax.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="direct-debit" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '19') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempdirectdebit.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>
    <div id="custom" style="position:absolute; z-index:2; width: 540px; visibility: <?php echo (isset($this->postArr['pane']) && $this->postArr['pane'] == '20') ? 'visible' : 'hidden'?>; left: 200px; top: 360px;">
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
          <td width="514" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
        </tr>
        <tr>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td>

          <?php require(ROOT_PATH . "/templates/hrfunct/hrempcustom.php"); ?>

            </td><td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c3.gif"><img name="table_r2_c3" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
        </tr>
        <tr>
          <td><img name="table_r3_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c1.gif" width="13" height="16" border="0" alt=""></td>
          <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c2.gif"><img name="table_r3_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
          <td><img name="table_r3_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r3_c3.gif" width="13" height="16" border="0" alt=""></td>
        </tr>
      </table>
    </div>

            </td>
        </tr>
    <table>

<?php } ?>

        </form>
    </body>
    <script language="JavaScript" type="text/javascript">
        displayLayer(<?php echo $this->postArr['pane']; ?>);
        <?php if (isset($this->postArr['txtShowAddPane']) && !empty($this->postArr['txtShowAddPane'])) { ?>
        showAddPane('<?php echo $this->postArr['txtShowAddPane']; ?>');
        <?php } ?>
    </script>
</html>

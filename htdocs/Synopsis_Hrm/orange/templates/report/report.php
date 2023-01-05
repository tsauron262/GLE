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

require_once ROOT_PATH . '/lib/common/CommonFunctions.php';
$lan = new Language();

//require_once ROOT_PATH . '/language/default/lang_default_full.php';
//require_once($lan->getLangPath("full.php"));
require_once ROOT_PATH . '/language/fr/lang_fr_full.php';
$lang_Template_rep_headName = array('Employee No'=>$lang_rep_EmployeeNo,
                                    'Employee First Name'=>$lang_rep_EmployeeFirstName,
                                    'Employee Last Name'=>$lang_rep_EmployeeLastName,
                                    'Address'=>$lang_rep_Address,
                                    'Telephone'=>$lang_rep_Telephone,
                                    'Report to'=>$lang_rep_ReportTo,
                                    'Reporting method'=>$lang_rep_ReportingMethod,
                                    'Date of Birth'=>$lang_rep_DateOfBirth,
                                    'Salary Grade'=>$lang_rep_SalaryGrade,
                                    'Employee Status'=>$lang_rep_EmployeeStatus,
                                    'Joined Date'=>$lang_rep_JoinedDate,
                                    'Job Title'=>$lang_rep_JobTitle,
                                    'Qualifications'=>$lang_rep_Qualification,
                                    'Year of passing'=>$lang_rep_YearOfPassing,
                                    'Sub division'=>$lang_rep_SubDivision,
                                    'Languages'=>$lang_rep_Languages,
                                    'Skills'=>$lang_rep_Skills,
                                    'Contract'=>$lang_rep_Contract,
                                    'Work experience'=>$lang_rep_WorkExperience);

$styleSheet = CommonFunctions::getTheme();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title></title>
<script language="JavaScript">
function goBack() {
    location.href = "./CentralController.php?repcode=EMPVIEW&VIEW=MAIN";
    }
</script>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="../../themes/<?php echo $styleSheet;?>/css/style.css" rel="stylesheet" type="text/css">
<style type="text/css">
@import url("../../themes/<?php echo $styleSheet;?>/css/style1.css");
ul {
    margin: 0px;
    left: -6px;
    position: relative;
    top: 0px;
    padding-top: 2px;
    padding-left: 0px;
}

li{
    list-style-type: none;
    vertical-align: middle;
    margin-top:8px;
}

</style>
</head>
<body>
<table border="0">
<tr><td></td>
<td height="35"><img title="Back" onMouseOut="this.src='../../themes/beyondT/pictures/btn_back.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_back_02.gif';"  src="../../themes/beyondT/pictures/btn_back.gif" onClick="goBack();"></td>
</tr>
<tr><td></td><td>
    <h2><center><?php echo $lang_rep_Report; ?>: <?php echo $this->repName; ?></center></h2></td>
</tr>
<tr><td></td><td>
        <table border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                  <td width="13"><img name="table_r1_c1" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c1.gif" width="13" height="12" border="0" alt=""></td>
                  <td width="339" background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c2.gif"><img name="table_r1_c2" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td width="13"><img name="table_r1_c3" src="../../themes/<?php echo $styleSheet; ?>/pictures/table_r1_c3.gif" width="13" height="12" border="0" alt=""></td>
                  <td width="11"><img src="../../themes/beyondT/pictures/spacer.gif" width="1" height="12" border="0" alt=""></td>
                </tr>
                <tr>
                  <td background="../../themes/<?php echo $styleSheet; ?>/pictures/table_r2_c1.gif"><img name="table_r2_c1" src="../../themes/beyondT/pictures/spacer.gif" width="1" height="1" border="0" alt=""></td>
                  <td><table width="100%" border="0" cellpadding="5" cellspacing="0" class="">
                    <tr>
<?php
                $startColumn=0;
                if (!$empNoField) {
                    $startColumn=1;
                }
                $reportingMethod = false;
                $subDivision = false;
                $contractDate = false;
                $jobTitle = false;
                $qualifications = false;
                $skills = false;
                $workExperience = false;
                $dateFields = array_fill(0, count($this->headName), false);

                $lang_Template_rep_ReportingMethod = array (1 => $lang_hrEmpMain_arrRepMethod_Direct, 2 => $lang_hrEmpMain_arrRepMethod_Indirect);
                for($i=$startColumn;$i<count($this->headName); $i++){
                    if (isset($lang_Template_rep_headName) && ($lang_Template_rep_headName[$this->headName[$i]])) {
                        $colHead = $lang_Template_rep_headName[$this->headName[$i]];
                    } else {
                        $colHead = $this->headName[$i];
                    }
                    echo "<td valign='top'>" . '<strong>' . $colHead . '</strong>' . '</td>';

                    switch ($this->headName[$i]) {
                        case 'Reporting method' : $reportingMethod = $i;
                                                break;
                        case 'Contract' : $contractDate = $i;
                                        break;
                        case 'Qualifications' : $qualifications = $i;
                                                break;
                        case 'Sub division' : $subDivision = $i;
                                            $compStructObj = new CompStruct();
                                            $compStructObj->buildAllWorkStations();
                                            break;
                        case 'Job Title' : $jobTitle = $i;
                                        break;
                        case 'Skills' : $skills = $i;
                                        break;
                        case 'Work experience' : $workExperience = $i;
                                                break;
                        case 'Date of Birth' : $dateFields[$i] = true;
                                                break;
                        case 'Joined Date' : $dateFields[$i] = true;
                                                break;

                    }
}?>

                    </tr>

<?php            $l=0;
                if (is_array($repDetails )) {
                    foreach ($repDetails as $i=>$employee){
                        $className="odd";
                        if (($l%2) == 0) {
                            $className="even";
                        }
                        $l++;
                ?>
                    <tr valign="top" class="<?php echo $className; ?>">
<?php                    for($j=$startColumn;$j<$columns; $j++) {
                            $tdWidth='auto';
                            switch ($j) {
                                case $contractDate : $tdWidth='130px';
                                                    break;
                                case $subDivision : $tdWidth='130px';
                                                    break;
                                case $jobTitle : $tdWidth='130px';
                                                break;
                                case $qualifications : $tdWidth='130px';
                                                    break;
                                case $skills : $tdWidth='180px';
                                            break;
                                case $workExperience : $tdWidth='180px';
                                                        break;
                            }
                    ?>
                        <td>
                    <?php     if (isset($repDetails[$i][$j]) && ($repDetails[$i][$j] != '')) {
                                $last=null; ?>
                                <ul style="height: 90%; width:<?php echo $tdWidth; ?>; clear:both;">
                        <?php
                                $rowHeight=floor(80/count($repDetails[$i][$j]));
                                $colWidth=substr($tdWidth, 0, -2);
                                $charWidth=$colWidth/5;

                                foreach ($repDetails[$i][$j] as $k=>$dataItem) {
                                    echo "<li style='height: $rowHeight%' >";
                                    if (($repDetails[$i][$j][$k] != '')) {

                                        // Convert date fields
                                        if($dateFields[$j]) {
                                            $repDetails[$i][$j][$k] = LocaleUtil::getInstance()->formatDate($repDetails[$i][$j][$k]);
                                        }

                                        if ($contractDate && $contractDate == $j) {
                                            $dates = explode(" - ", $repDetails[$i][$j][$k]);
                                            if (count($dates) == 2) {
                                                $startDate = LocaleUtil::getInstance()->formatDate($dates[0]);
                                                $endDate = LocaleUtil::getInstance()->formatDate($dates[1]);
                                                $repDetails[$i][$j][$k] = $startDate . " - " . $endDate;
                                            }
                                        }


                                        if ($subDivision && ($subDivision == $j)) {
                                            echo $compStructObj->fetchHierarchString($repDetails[$i][$j][$k]);
                                        } else if ($reportingMethod && ($reportingMethod == $j)) {
                                            echo $lang_Template_rep_ReportingMethod[$repDetails[$i][$j][$k]];
                                        } else if ($charWidth > 0){
                                            echo wordwrap($repDetails[$i][$j][$k], $charWidth, "<br>", 1);
                                        } else {
                                            echo $repDetails[$i][$j][$k];
                                        }
                                        $last = $repDetails[$i][$j][$k];
                                    } else {
                                        echo '―';
                                    }
                                    echo "</li>";
                                } ?>
                                </ul>
                    <?php    } else {
                                echo "&nbsp;";
                            }?>

                        </td>
                <?php    }
?>
                    </tr>
<?php             }
            }
?>


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
              </td></tr></table>
</body>
</html>

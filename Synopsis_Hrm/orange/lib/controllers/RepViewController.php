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


require_once ROOT_PATH . '/lib/confs/Conf.php';
require_once ROOT_PATH . '/lib/dao/DMLFunctions.php';
require_once ROOT_PATH . '/lib/dao/SQLQBuilder.php';
require_once ROOT_PATH . '/lib/common/CommonFunctions.php';
require_once ROOT_PATH . '/lib/exception/ExceptionHandler.php';

require_once ROOT_PATH . '/lib/models/report/EmpReport.php';
require_once ROOT_PATH . '/lib/models/report/EmpRepUserGroups.php';
require_once ROOT_PATH . '/lib/models/report/ReportGenerator.php';


class RepViewController {

    function RepViewController() {

    }

    function viewList($getArr,$postArr) {

        $form_creator = new FormCreator($getArr,$postArr);
        $form_creator ->formPath ='/repview.php';

        if ((isset($getArr['repcode'])) && ($getArr['repcode'] != '')) {
            $form_creator ->popArr['headinginfo'] = $this ->getHeadingInfo(trim($getArr['repcode']));
        }

        $form_creator ->popArr['currentPage'] = $currentPage =(isset($postArr['pageNO'])) ? (int)$postArr['pageNO'] : 1;

        if (isset($postArr['captureState'])&& ($postArr['captureState']=="SearchMode")) {
            $choice=$postArr['loc_code'];
            $strName=trim($postArr['loc_name']);
            $form_creator ->popArr['message'] = $this ->  getInfo(trim($getArr['repcode']),$currentPage,$strName,$choice);
        } else
            $form_creator ->popArr['message'] = $this ->  getInfo(trim($getArr['repcode']),$currentPage);

        if (isset($postArr['captureState'])&& ($postArr['captureState']=="SearchMode"))
            $form_creator ->popArr['temp'] = $this ->  countList(trim($getArr['repcode']),$strName,$choice);
        else
            $form_creator ->popArr['temp'] = $this ->  countList(trim($getArr['repcode']));

        $form_creator->display();
    }

    function delParser($indexCode,$arrList) {
        $this->indexCode=$indexCode;

        switch ($this->indexCode)  {

            case 'EMPDEF' :

                $this->report = new EmpReport();
                $this->report->delReports($arrList);
                break;
        }
    }

    function assignData($index,$object) {

        switch($index) {

            case 'EMPVIEW' :     $repgen = new ReportGenerator();
                                $repgen = $object;

                                $sqlQ = $repgen->reportQueryBuilder();


                                $dbConnection = new DMLFunctions();
                                $message2 = $dbConnection -> executeQuery($sqlQ);

                                $i=0;
                                while ($line = mysql_fetch_array($message2, MYSQL_NUM)) {

                                    for($c=0;count($repgen->field)>$c;$c++)
                                        $arrayDispList[$i][$c] = $line[$c];
                                    $i++;
                                    }

                                    $repgen->reportDisplay($arrayDispList);

                                    break;
        }
    }

    function selectIndexId($pageNO,$schStr,$mode) {

        switch ($this->indexCode) {

        case 'EMPVIEW' :

            $this-> reports = new EmpReport();
            $message = $this-> reports -> getListofUserGroupReports($_SESSION['userGroup'],$pageNO,$schStr,$mode);
            return $message;
            break;

        case 'EMPDEF' :

            $this-> reports = new EmpReport();
            $message = $this-> reports ->  getListofUserGroupReports($_SESSION['userGroup'],$pageNO,$schStr,$mode);
            return $message;
            break;
        }
    }


    function getHeadingInfo($indexCode) {

        $this->indexCode = $indexCode;

        switch ($this->indexCode) {

        case 'EMPDEF' :

            $this->headingInfo = array ('Report ID','Report Name',1,'Define Employee Reports','Deletion might affect Company Hierarchy');
            return $this->headingInfo;
            break;

        case 'EMPVIEW' :

            $this->headingInfo = array ('Report ID','Report Name',0,'View Employee Reports','Deletion might affect Company Hierarchy');
            return $this->headingInfo;
            break;
        }

    }

    function getInfo($indexCode,$pageNO,$schStr='',$mode=0) {

        $this->indexCode = $indexCode;
        return $this->selectIndexId($pageNO,$schStr,$mode);

    }

    function countList($index,$schStr='',$mode=0) {

        $this->indexCode=$index;

        switch ($this->indexCode) {

            case 'EMPDEF' :

            $this-> report = new EmpReport();
            $message = $this-> report -> countReports($schStr,$mode);
            return $message;
            break;

            case 'EMPVIEW' :

            $this-> report = new EmpReport();
            $message = $this-> report -> countReports($schStr,$mode);
            return $message;
            break;
        }
    }

    function addData($index,$object) {

            switch ($index) {

                case 'EMPDEF'  :        $report = new EmpReport();
                                        $report = $object;

                                        $res = $report -> addReport();

                                        if ($res){
                                            $id = $report -> getRepID();

                                            $repusg = new EmpRepUserGroup();

                                            $repusg -> setRepCode($id);
                                            $repusg -> setUserGroupID($_SESSION['userGroup']);
                                            $repusg -> addRepUserGroup();
                                        }

                                        break;
            }

            // Checking whether the $message Value returned is 1 or 0
            if ($res) {

                switch($index) {

                    case 'EMPDEF' :
                                header("Location: ./CentralController.php?repcode=RUG&id=$id");
                                break;

                    default:
                                $showMsg = "ADD_SUCCESS"; //If $message is 1 setting up the

                                $repcode = $index;

                                header("Location: ./CentralController.php?message=$showMsg&repcode=$repcode&VIEW=MAIN");
                }

            } else {
                $errorCode = mysql_errno();

                switch ($errorCode) {
                    case 1062:
                        $showMsg = 'DUPLICATE_NAME_ADDED';
                        break;

                    default:
                        $showMsg = 'ADD_FAILURE';
                        break;
                }

                $repcode = $index;
                header("Location: ./CentralController.php?msg=$showMsg&capturemode=addmode&repcode=$repcode");
            }
        }


    function updateData($index,$id,$object) {

            switch ($index) {

                case 'EMPDEF'  :        $report = new EmpReport();
                                        $report = $object;
                                        $res = $report -> updateReport();
                                        break;
            }

            // Checking whether the $message Value returned is 1 or 0
            if ($res) {
                $showMsg = "UPDATE_SUCCESS"; //If $message is 1 setting up the

                $repcode = $index;

                header("Location: ./CentralController.php?message=$showMsg&repcode=$repcode&VIEW=MAIN");
            } else {

                $errorCode = mysql_errno();

                switch ($errorCode) {
                    case 1062:
                        $showMsg = 'UPDATED_TO_DUPLICATE_NAME';
                        break;

                    default:
                        $showMsg = 'UPDATE_FAILURE';
                        break;
                }

                $repcode = $index;
                header("Location: ./CentralController.php?msg=$showMsg&id=$id&capturemode=updatemode&repcode=$repcode");
            }
    }

    function addUserGroups($repusg) {
        $repusg -> addRepUserGroup();
    }

    function delUserGroups($postArr,$getArr) {

            $repusg = new EmpRepUserGroup();

            $arr[0]=$postArr['chkdel'];
            $size = count($arr[0]);

            for($c=0 ; $size > $c ; $c++)
                if($arr[0][$c]!=NULL)
                    $arr[1][$c]=$getArr['id'];

            $repusg -> delRepUserGroup($arr);
    }

    function reDirect($getArr,$postArr,$object = null) {

        $form_creator = new FormCreator($getArr,$postArr);

        if ($_SESSION['isAdmin'] !== 'Yes') {
            trigger_error("Unauthorized access", E_USER_NOTICE);
        }

        switch ($getArr['repcode']) {

            case 'EMPDEF' : //if ()
                            $form_creator ->formPath = '/templates/report/emprepinfo.php';

                            $form_creator->popArr['arrAgeSim'] = array ('Inf&eacute;rieur &agrave;' => '>','Sup&eacute;rieur &agrave;' =>'<','Entre' =>'range');
                            //$form_creator->popArr['arrEmpType']= array( 'Permanent', 'Expatriate', 'Contract', 'Temporary' , 'Others');
                            $form_creator->popArr['arrSerPer'] = array ('Inf&eacute;rieur &agrave;' => '>','Sup&eacute;rieur &agrave;' =>'<','Entre' =>'range');
                            $form_creator->popArr['arrJoiDat'] = array ('Employ&eacute; apr&egrave;s le' => '>','Employ&eacute; avant le' =>'<','Employ&eacute; entre' =>'range');

                            $report = new EmpReport();
                            $empinfo = new EmpInfo();
                            $edu = new Education();
                            $jobtit   = new JobTitle();
                            $salgrd = new SalaryGrades();
                            $empstat = new EmploymentStatus();
                            $langObj = new LanguageInfo();
                            $skillObj = new Skills();

                            $form_creator->popArr['grdlist'] = $salgrd ->getSalGrdCodes();
                            $form_creator->popArr['edulist'] = $edu ->getAllEducation();
                            $form_creator->popArr['deslist'] = $jobtit ->getJobTit();
                            $form_creator->popArr['arrEmpType'] = $empstat ->getEmpStat();
                            $form_creator->popArr['languageList'] = $langObj->getLang();
                            $form_creator->popArr['skillList'] = $skillObj->getSkillCodes();


                            if($getArr['capturemode'] == 'updatemode') {
                                $form_creator ->popArr['editArr'] = $edit = $report->filterReport($getArr['id']);
                                $criteria_value = explode('|',$edit[0][2]);

                            for($c=0;count($criteria_value)>$c;$c++) {
                                $crit_data = explode("=",$criteria_value[$c]);
                                $criteriaChkBox[$c] = $crit_data[0];

                                for($d=1;count($crit_data)>$d;$d++)
                                    $crit_form_data[$crit_data[0]][$d-1] = $crit_data[$d];
                            }

                                $form_creator->popArr['editCriteriaChk'] = $criteriaChkBox;
                                $form_creator->popArr['editCriteriaData'] = $crit_form_data;

                                $form_creator->popArr['fieldList'] = explode('|',$edit[0][3]);
                            }

                            if($object != null){
                                $form_creator-> popArr['empqual']      =   $empqual->getQualifications($object -> TypeCode);
                            }

                            break;

            case 'EMPVIEW' :
                                $report = new EmpReport();
                                $repgen = new ReportGenerator();

                                $form_creator ->popArr['editArr'] = $edit = $report->filterReport($getArr['id']);

                                $repgen ->repID = $edit[0][0];
                                $repgen ->repName = $edit[0][1];

                                $criteria = explode('|',$edit[0][2]);

                                for($c=0;count($criteria)>$c;$c++) {
                                    $crit_value = explode("=",$criteria[$c]);

                                    $repgen -> criteria[$crit_value[0]] = '';
                                    for($d=1;count($crit_value)>$d;$d++)
                                        if($d==count($crit_value)-1)
                                            $repgen -> criteria[$crit_value[0]] .= $crit_value[$d];
                                        else
                                            $repgen -> criteria[$crit_value[0]] .= $crit_value[$d] . "|";
                                }

                                $field = explode('|',$edit[0][3]);

                                $empNoField=false;
                                for($c=0;count($field)>$c;$c++) {
                                    $repgen->field[$field[$c]] = 1;
                                    if ($field[$c] == 'EMPNO') {
                                        $empNoField=true;
                                    }
                                }

                                $repgen->field['EMPNO'] = 1;

                                $sqlQ = $repgen->reportQueryBuilder();
                                //echo $sqlQ."<br/>";

                                $dbConnection = new DMLFunctions();
                                $message2 = $dbConnection -> executeQuery($sqlQ);

                                $i=0;
                                while ($line = mysql_fetch_array($message2, MYSQL_NUM)) {
                                    for ($c=0;count($repgen->field)*2>$c;$c+=2) {
                                        $arrayDispList[$line[0]][$c/2][$line[$c]] = $line[$c+1];
                                    }
                                    $i++;
                                }
                                //print_r($arrayDispList);

                                if (!isset($arrayDispList)) {
                                    $arrayDispList = null;
                                }
                                $repgen->reportDisplay($arrayDispList, $empNoField);
                                return;

                            break;

            case 'RUG' :    $form_creator ->formPath = '/templates/report/repusg.php';
                            $emprepgroup = new EmpRepUserGroup();

                            $form_creator ->popArr['repDet'] = $emprepgroup -> getReportInfo();
                            $form_creator->popArr['usgAll'] = $emprepgroup -> getAllUserGroups();
                            $form_creator ->popArr['repUsgAss'] = $emprepgroup ->getAssignedUserGroup($getArr['id']);
                            $form_creator ->popArr['usgUnAss'] = $emprepgroup ->getUnAssUserGroups($getArr['id']);

                            break;

        }

        $form_creator->display();
    }

}
?>
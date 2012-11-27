<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Created on : 8-23-2009
 *
 * Infos on http://www.finapro.fr
 *
 */

/**
 *
 * Name : hrm.class.php.php
 * GLE-1.1
 */
class hrm {

    public $db;
    public $hrmdb;
    public $teamRessource;

    public function hrm($db) {
        global $conf;
        $this->db = $db;
        require_once(DOL_DOCUMENT_ROOT . '/Synopsis_Hrm/orange/lib/confs/Conf.php');
        $Hconf = new HrmConf();
        $this->hrmdb = getDoliDBInstance($conf->db->type, $Hconf->dbhost, $Hconf->dbuser, $hconf->dbpass, $Hconf->dbname, $Hconf->dbport);
        if ($this->hrmdb->error) {
            dol_print_error($this->hrmdb, "Impossible de se connecter a la base HRM : host=" . $Hconf->dbhost . ", port=" . $Hconf->dbport . ", user=" . $Hconf->dbuser . ", databasename=" . $Hconf->dbname . ", " . $this->hrmdb->error);
            exit;
        }
    }

    public $allRessource = array();

    public function listRessources() {
        $requete = "SELECT *
                      FROM hs_hr_employee";
        $sql1 = $this->hrmdb->query($requete);
        if ($sql1) {
            while ($res1 = $this->hrmdb->fetch_object($sql1)) {
                $requete2 = "SELECT *
                               FROM " . MAIN_DB_PREFIX . "Synopsis_hrm_user
                              WHERE hrm_id = " . $res1->emp_number . "
                                AND user_id is not null
                           ORDER BY startDate DESC
                              LIMIT 1";
                $sql2 = $this->db->query($requete2);
                if ($sql2) {
                    $res2 = $this->db->fetch_object($sql2);

                    //            print $res1->employee_id."<br>";
                    $this->allRessource[$res1->emp_number]['empId'] = $res1->employee_id;
                    $this->allRessource[$res1->emp_number]['lastName'] = $res1->emp_lastname;
                    $this->allRessource[$res1->emp_number]['firstName'] = $res1->emp_firstname;
                    $this->allRessource[$res1->emp_number]['nickName'] = $res1->emp_nick_name;
                    $this->allRessource[$res1->emp_number]['GLEId'] = $res2->user_id;
                    $this->allRessource[$res1->emp_number]['empNumber'] = $res1->emp_number;
                }
            }
        }
    }

    public function listRessource($empNumber) {
        $requete = "SELECT *
                      FROM hs_hr_employee
                     WHERE emp_number =" . $empNumber;
        $sql1 = $this->hrmdb->query($requete);
        if ($sql1) {
            while ($res1 = $this->hrmdb->fetch_object($sql1)) {
                $requete2 = "SELECT *
                               FROM " . MAIN_DB_PREFIX . "Synopsis_hrm_user
                              WHERE hrm_id = " . $res1->emp_number . "
                                    AND user_id is not null
                           ORDER BY startDate DESC
                              LIMIT 1";
                $sql2 = $this->db->query($requete2);
                if ($sql2) {
                    $res2 = $this->db->fetch_object($sql2);

                    $this->allRessource[$res1->emp_number]['empId'] = $res1->employee_id;
                    $this->allRessource[$res1->emp_number]['lastName'] = $res1->emp_lastname;
                    $this->allRessource[$res1->emp_number]['firstName'] = $res1->emp_firstname;
                    $this->allRessource[$res1->emp_number]['nickName'] = $res1->emp_nick_name;
                    $this->allRessource[$res1->emp_number]['GLEId'] = $res2->user_id;
                    $this->allRessource[$res1->emp_number]['empNumber'] = $res1->emp_number;
                }
            }
        }
    }

    public function GleId2HrmId($gleId) {
        global $db;
        $this->db = $db;
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_hrm_user
                     WHERE user_id = " . $gleId . "
                  ORDER BY startDate DESC
                     LIMIT 1";
        $sql = $this->db->query($requete);
        if ($sql) {
            $res = $this->db->fetch_object($sql);
            return($res->hrm_id);
        }
    }

    public function HrmId2GleId($hrmId) {
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_hrm_user
                     WHERE hrm_id = " . $hrmId . "
                       AND user_id is not null
                  ORDER BY startDate DESC
                     LIMIT 1";
        $sql = $this->db->query($requete);
        if ($sql) {
            $res = $this->db->fetch_object($sql);
            return($res->user_id);
        }
    }

    public function listTeam() {
        $requete = "SELECT hs_hr_emp_subdivision_history.emp_number,
                           start_date,
                           hs_hr_compstructtree.title,
                           hs_hr_compstructtree.id
                      FROM hs_hr_emp_subdivision_history,
                           hs_hr_compstructtree
                     WHERE hs_hr_compstructtree.title = hs_hr_emp_subdivision_history.name
                       AND (end_date is null OR end_date > now())
                       AND start_date < now()";
        $sql1 = $this->hrmdb->query($requete);
        while ($res1 = $this->hrmdb->fetch_object($sql1)) {
            $this->listRessource($res1->emp_number);
            $this->teamRessource[$res1->id]['qte'] = ($this->teamRessource[$res1->id]['qte'] . "x" == "x" ? 0 : $this->teamRessource[$res1->id]['qte']) + 1;
            $this->teamRessource[$res1->id]['name'] = $res1->title;
            $this->teamRessource[$res1->id]['empInfo'][$res1->emp_number] = $this->allRessource[$res1->emp_number];
        }
    }

    public function getTeam($gleId) {
        $hrmId = $this->GleId2HrmId($gleId);
        $requete = "SELECT hs_hr_emp_subdivision_history.emp_number,
                           start_date,
                           hs_hr_compstructtree.title,
                           hs_hr_compstructtree.id
                      FROM hs_hr_emp_subdivision_history,
                           hs_hr_compstructtree
                     WHERE hs_hr_compstructtree.title = hs_hr_emp_subdivision_history.name
                       AND (end_date is null OR end_date > now())
                       AND start_date < now()
                       AND emp_number = " . $hrmId;
        $sql1 = $this->hrmdb->query($requete);
        $res1 = $this->hrmdb->fetch_object($sql1);

        $this->listRessource($res1->emp_number);
        $this->teamRessource[$res1->id]['qte'] = ($this->teamRessource[$res1->id]['qte'] . "x" == "x" ? 0 : $this->teamRessource[$res1->id]['qte']) + 1;
        $this->teamRessource[$res1->id]['name'] = $res1->title;
        $this->teamRessource[$res1->id]['empInfo'][$res1->emp_number] = $this->allRessource[$res1->emp_number];
        return($res1->id);
    }

    public function getOrgTree() {

//                                <li><span >Folder 1</span>
//                                    <ul>
//                                        <li><span >Item 1.1</span></li>
//                                    </ul>
//                                </li>
//                                <li><span >Folder 2</span>
//                                    <ul>
//                                        <li class="opened"><span >Subfolder 2.1</span>
//                                            <ul>
//                                                <li><span >File 2.1.1</span></li>
//                                                <li><span >File 2.1.2</span></li>
//                                            </ul>
//                                        </li>
//                                        <li><span >File 2.2</span></li>
//                                    </ul>
//                                </li>

        $requete = "SELECT * FROM hs_hr_compstructtree WHERE parnt = 0";
        $sql = $this->hrmdb->query($requete);
        $html = "";
        while ($res = $this->hrmdb->fetch_object($sql)) {
            $html .= '<li class="open"><span id=' . $res->id . ' onClick="SelectGrp(' . $res->id . ');">' . preg_replace("/[\s]*&[\w;]*$/", "", htmlentities($res->title)) . '</span>';
            $requete = "SELECT count(*) as cnt FROM hs_hr_compstructtree WHERE parnt = " . $res->id;
            $sql1 = $this->hrmdb->query($requete);
            $res1 = $this->hrmdb->fetch_object($sql1);
            if ($res1->cnt > 0) {
                $html .= "<ul>";
                $html .= $this->getOrgTreeRecurs($res->id);
                $html .= "</ul>";
            } else {
                $html .= $this->getOrgTreeRecurs($res->id);
            }
            $html .= "</li>";
        }
        return($html);
    }

    private function getOrgTreeRecurs($seekId) {
        $requete = "SELECT * FROM hs_hr_compstructtree WHERE parnt = " . $seekId;
        $sql = $this->hrmdb->query($requete);
        $html = "";
        while ($res = $this->hrmdb->fetch_object($sql)) {
            $html .= '  <li class="closed"><span id=' . $res->id . '  onClick="SelectGrp(' . $res->id . ');">' . preg_replace("/[\s]*&[\w;]*$/", "", htmlentities($res->title)) . '</span>';
            $requete = "SELECT count(*) as cnt FROM hs_hr_compstructtree WHERE parnt = " . $res->id;
            $sql1 = $this->hrmdb->query($requete);
            $res1 = $this->hrmdb->fetch_object($sql1);
            if ($res1->cnt > 0) {
                $html .= "<ul >";
                $html .= $this->getOrgTreeRecurs($res->id);
                $html .= "</ul>";
            } else {
                $html .= $this->getOrgTreeRecurs($res->id);
            }
            $html .= "</li>";
        }
        return($html);
    }

    public function getRessourcesFromGLEid($gleId) {
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_hrm_user
                     WHERE user_id = " . $gleId . "
                  ORDER BY startDate DESC
                     LIMIT 1";
        $sql = $this->db->query($requete);
        if ($sql) {
            $res = $this->db->fetch_object($sql);
            $empId = $res->hrm_id;
            $this->listRessource($empId);
            return($this->allRessource[$empId]);
        }
    }

    public function create($userObj) {
        $requete = "SELECT max(emp_number) as newId FROM hs_hr_employee";
        $sql = $this->hrmdb->query($requete);
        $res = $this->hrmdb->fetch_object($sql);
        $newId = $res->newId + 1;
        $newEmpNumber = $newId;
        $newemp_lastname = $userObj->nom;
        $newemp_firstname = $userObj->prenom;
        $newemp_nick_name = $userObj->login;
        $newemp_work_email = $userObj->email;
        $newemp_mobile = $userObj->user_mobile;
        $newemp_work_telephone = $userObj->office_phone;
        if (strlen($res->newId) < 3) {
            $cnt = 3 - strlen($res->newId);
            $newEmpNumber = str_repeat('0', $cnt) . $newEmpNumber;
        }
        $this->hrmdb->begin();
        $requete = "INSERT INTO hs_hr_employee (emp_number, employee_id, emp_lastname, emp_firstname, emp_nick_name, emp_work_email, emp_work_telephone, emp_mobile)
                            VALUES ($newId,'$newEmpNumber','$newemp_lastname','$newemp_firstname','$newemp_nick_name','$newemp_work_email','$newemp_work_telephone','$newemp_mobile')";
        $sql = $this->hrmdb->query($requete);

        $requete = " INSERT INTO hs_hr_users (`id` ,`user_name` ,`user_password` ,`first_name` ,`last_name` ,`emp_number` ,`user_hash` ,`is_admin` ,`receive_notification` ,`description` ,`date_entered` ,`date_modified` ,`modified_user_id` ,`created_by` ,`title` ,`department` ,`phone_home` ,`phone_mobile` ,`phone_work` ,`phone_other` ,`phone_fax` ,`email1` ,`email2` ,`status` ,`address_street` ,`address_city` ,`address_state` ,`address_country` ,`address_postalcode` ,`user_preferences` ,`deleted` ,`employee_status`, `userg_id`)
                            VALUES ('USR".$newId."', '".$userObj->firstname."', '".$userObj->pass_indatabase_crypted."', '".$userObj->firstname."', '".$userObj->lastname."', '".$newId."', '', 'No', '1', '', NULL , NULL , NULL , NULL , '', '', '', '', '', '', '', '', '', 'Enabled', '', '', '', '', '', '', '0', '', 'USG001')";
//INSERT INTO hs_hr_employee (emp_number, employee_id, emp_lastname, emp_firstname, emp_nick_name, emp_work_email, emp_work_telephone, emp_mobile)
//                            VALUES ($newId,'$newEmpNumber','$newemp_lastname','$newemp_firstname','$newemp_nick_name','$newemp_work_email','$newemp_work_telephone','$newemp_mobile')";
        $sql2 = $this->hrmdb->query($requete);
        //create PIM access
        if ($sql && $sql2) {
            $lastid = $newId;
            $sql1 = $this->createESS($userObj, false);
            if ($sql1) {

                //insert into ".MAIN_DB_PREFIX."Synopsis_hrm_user
///TODO lastid KO
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_hrm_user (user_id, hrm_id) VALUES (" . $userObj->id . "," . $lastid . ")";
                $sql2 = $this->db->query($requete);
                if ($sql2) {
                    $this->hrmdb->commit();
                    $this->db->commit();
                    return($newId);
                } else {
                    $this->hrmdb->rollback();
                    $this->db->rollback();
                    return(false);
                }
            } else {
                $this->hrmdb->rollback();
                return(false);
            }
        } else {
            $this->hrmdb->rollback();
            return(false);
        }
    }

    public function update($userObj) {
        $hrmid = $this->GleId2HrmId($userObj->id);
        $newemp_lastname = $userObj->nom;
        $newemp_firstname = $userObj->prenom;
        $newemp_nick_name = $userObj->login;
        $newemp_work_email = $userObj->email;
        $newemp_mobile = $userObj->user_mobile;
        $newemp_work_telephone = $userObj->office_phone;
        $requete = "UPDATE hs_hr_users
                       SET emp_lastname='" . $newemp_lastname . "',
                           emp_firstname='" . $newemp_firstname . "',
                           emp_nick_name='" . $newemp_nick_name . "',
                           emp_work_email='" . $newemp_work_email . "',
                           emp_work_telephone='" . $newemp_work_telephone . "',
                           emp_mobile='" . $newemp_mobile . "'
                    WHERE emp_number = " . $hrmid . " ";
        $this->hrmdb->query($requete);
    }

    public function createESS($userObj, $commit = true) {
        global $user;
        //create ESS account
        $requete = "SELECT * FROM hs_hr_users WHERE id <> 'USR666'";
        $sql = $this->hrmdb->query($requete);
        $newEmpId = 0;
        while ($res = $this->hrmdb->fetch_object($sql)) {
            $id = preg_replace('/^USR/', '', $res->id);
            if ($id > $newEmpId)
                $newEmpId = $id;
            if ($id == 665)
                ; $id +=2;
        }
        $newEmpId += 1;
        $newEmpId = "USR" . $newEmpId;
        //get EmpNumber
        $hrmid = $this->GleId2HrmId($userObj->id);
        $myid = $this->GleId2HrmId($user->id);

        $newemp_lastname = $userObj->nom;
        $newemp_firstname = $userObj->prenom;
        $newemp_nick_name = $userObj->login;


        //username = login
        //voir les droit pour is_admin
        $is_admin = "No";
        if ($user->rights->hrm->hrm->Admin) {
            $is_admin = "Yes";
        }
        $requete = "INSERT INTO hs_hr_users
                                (id, user_name,user_password,first_name, last_name, emp_number, is_admin, date_entered, created_by, status)
                         VALUES ('" . $newEmpId . "','" . $newemp_nick_name . "', '" . $newemp_firstname . "', '" . $newemp_lastname . "', $hrmid, '" . $is_admin . "',now(),'" . $myid . "','Enabled') ";
        if ($sql) {
            if ($commit)
                $this->hrmdb->commit();
            return ($newEmpId);
        } else {
            $this->hrmdb->rollback();
            return (false);
        }
    }

    public function projectTask_GLEId2HRMId($id) {
        $requete = "SELECT HRMId as hrmid
                      FROM Babel_hrm_project_task
                     WHERE GLEid = " . $id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $hrmId = $res->hrmid;
        if ($hrmId . "x" == "x") {
            $hrmId = 0;
        }
        return ($hrmId);
    }

    public function projectTask_HRMId2GLEId($id) {
        $requete = "SELECT GLEid as gleid
                      FROM Babel_hrm_project_task
                     WHERE HRMId = " . $id;
        $sql = $this->db->query($requete);
        if ($sql) {
            $res = $this->db->fetch_object($sql);
            $gleId = $res->gleid;
            if ($gleId . "x" == "x") {
                $gleId = 0;
            }
            return ($gleId);
        }
    }

    public function project_GLEId2HRMId($id) {
        $requete = "SELECT HRMId as hrmid
                      FROM Babel_hrm_project
                     WHERE GLEid = " . $id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $hrmId = $res->hrmid;
        if ($hrmId . "x" == "x") {
            $hrmId = 0;
        }
        return ($hrmId);
    }

    public function customer_GLEId2HRMId($id) {
        $requete = "SELECT HRMId as hrmCustId FROM Babel_hrm_customer WHERE GLEid = " . $object->socid;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $hrmCustId = $res->hrmCustId;
        if ($hrmCustId . "x" == "x") {
            $hrmCustId = 0;
        }
        return ($hrmCustId);
    }

    public function jourFerie($minDate = false, $maxDate = false) {
        $decalHoraire = intval(date('O') / 100);
        $requeteHrm = "SELECT unix_timestamp(date) + (3600 * $decalHoraire )  as Fdate
                        FROM hs_hr_holidays
                       WHERE 1=1 ";
        if ($minDate) {
            $requeteHrm .= " AND unix_timestamp(date) > '" . $minDate . "' ";
        }
        if ($maxDate) {
            $requeteHrm .= " AND unix_timestamp(date) < '" . $maxDate . "' ";
        }
        $sql = $this->hrmdb->query($requeteHrm);
        $arr = array();
        while ($res = $this->hrmdb->fetch_object($sql)) {
            array_push($arr, $res->Fdate);
        }
        return($arr);
    }

}

?>
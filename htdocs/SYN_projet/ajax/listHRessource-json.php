<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 24 aout 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listTeam-json.php.php
  * GLE-1.1
  */

$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction



if(!$sidx) $sidx =1; // connect to the database



require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Hrm/hrm.class.php');
$hrm = new hrm($db);



$result = $db->query("SELECT count(*) as count
                        FROM hs_hr_employee
                   LEFT JOIN hs_hr_emp_jobtitle_history on hs_hr_emp_jobtitle_history.emp_number = hs_hr_employee.emp_number
                   LEFT JOIN hs_hr_job_title on hs_hr_emp_jobtitle_history.name = hs_hr_job_title.jobtit_name
                       WHERE hs_hr_emp_jobtitle_history.startDate < now()
                         AND (hs_hr_emp_jobtitle_history.endDate is null OR hs_hr_emp_jobtitle_history.endDate > now())");
$row = $db->fetch_array($result,MYSQL_ASSOC);
$count = $row['count'];
if( $count >0 )
{
    $total_pages = ceil($count/$limit);
} else {
    $total_pages = 0;
}
if ($page > $total_pages) $page=$total_pages;
$start = $limit*$page - $limit; // do not put $limit*($page - 1)
if ($start<0) $start = 0;
//attn history

//         LEFT JOIN hs_hr_emp_skill on hs_hr_employee.emp_number = hs_hr_emp_skill.emp_number
//         LEFT JOIN hs_hr_emp_licenses on hs_hr_emp_licenses.emp_number = hs_hr_employee.emp_number


$requete = "SELECT ifnull(jobtit_name,'A configurer')  as poste,
                   concat (emp_firstname,' ',emp_lastname) as fullname,
                   hs_hr_employee.emp_number as id
              FROM hs_hr_employee
         LEFT JOIN hs_hr_emp_jobtitle_history on hs_hr_emp_jobtitle_history.emp_number = hs_hr_employee.emp_number
         LEFT JOIN hs_hr_job_title on hs_hr_emp_jobtitle_history.name = hs_hr_job_title.jobtit_name
             WHERE (hs_hr_emp_jobtitle_history.start_date is null OR hs_hr_emp_jobtitle_history.start_date < now())
               AND (hs_hr_emp_jobtitle_history.end_date is null OR hs_hr_emp_jobtitle_history.end_date > now())
          ORDER BY $sidx $sord
             LIMIT $start , $limit";
die($requete);
$sql1 = $hrm->hrmdb->query($requete);
$count=0;
while ($res1 = $hrm->hrmdb->fetch_object($sql1))
{
//        $this->listRessource($res1->emp_number);
//        $this->teamRessource[$res1->id]['qte']=($this->teamRessource[$res1->id]['qte']."x" =="x"?0:$this->teamRessource[$res1->id]['qte'])+1;
//        $this->teamRessource[$res1->id]['name']=$res1->title;
//        $this->teamRessource[$res1->id]['empInfo'][$res1->emp_number]=$this->allRessource[$res1->emp_number];
    $requetea = "SELECT couthoraire
                  FROM ".MAIN_DB_PREFIX."Synopsis_hrm_user
                 WHERE $res1->id = hrm_id
              ORDER BY startDate DESC
                 LIMIT 1";
    $sql1a = $db->query($requetea);
    if ($sql1a)
    {
        $res1a = $db->fetch_object($sql1a);
        $coutHoraire = $res1a->couthoraire;
    }

    $responce->rows[$count]['id']=$res1->id;
    $responce->rows[$count]['cell']=array($res1->id,$res1->fullname,$res1->poste,price(($coutHoraire."x"=="x"?0:$coutHoraire))."&euro");

    $count ++;
}

$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;


echo json_encode($responce);

?>

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



$result = $db->query("SELECT COUNT(*) AS count
                        FROM hs_hr_emp_subdivision_history,
                             hs_hr_compstructtree
                       WHERE hs_hr_compstructtree.title = hs_hr_emp_subdivision_history.name
                         AND (end_date is null OR end_date > now())
                         AND start_date < now()
                    GROUP BY hs_hr_compstructtree.title");
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

$requete = "SELECT start_date,
                   hs_hr_compstructtree.title, hs_hr_compstructtree.id,
                   count(emp_number) as qte
              FROM hs_hr_emp_subdivision_history,
                   hs_hr_compstructtree
             WHERE hs_hr_compstructtree.title = hs_hr_emp_subdivision_history.name
               AND (end_date is null OR end_date > now())
               AND start_date < now()
          GROUP BY hs_hr_compstructtree.title
          ORDER BY $sidx $sord
             LIMIT $start , $limit";

$sql1 = $hrm->hrmdb->query($requete);
$count=0;
$hrm->listTeam();
while ($res1 = $hrm->hrmdb->fetch_object($sql1))
{
//        $this->listRessource($res1->emp_number);
//        $this->teamRessource[$res1->id]['qte']=($this->teamRessource[$res1->id]['qte']."x" =="x"?0:$this->teamRessource[$res1->id]['qte'])+1;
//        $this->teamRessource[$res1->id]['name']=$res1->title;
//        $this->teamRessource[$res1->id]['empInfo'][$res1->emp_number]=$this->allRessource[$res1->emp_number];
    $requetea = "SELECT *
                  FROM Babel_hrm_team
                 WHERE $res1->id = teamId ORDER BY startDate DESC LIMIT 1";
    $sql1a = $db->query($requetea);
    $res1a = $db->fetch_object($sql1a);

//cout reel = cout de chaque memebre de la team / nb personne dans la team
    $i=0;
    $couthorairereel = 0;

    foreach($hrm->teamRessource[$res1a->teamId]['empInfo'] as $key=>$val)
    {
        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."Synopsis_hrm_user
                     WHERE hrm_id = $key
                  ORDER BY startDate DESC limit 1";
        $sql2 = $db->query($requete);
        if ($sql2)
        {
            $res2 = $db->fetch_object($sql2);
            if ('x'.$res2->couthoraire != "x")
            {
                $i ++;
                $couthorairereel += $res2->couthoraire;
            }
        }
    }
    for ($j=$i;$j<$res1->qte;$j++)
    {
        $couthorairereel += ($res1a->couthoraire."x"=="x"?0:$res1a->couthoraire);
    }
    $couthorairereel = price($couthorairereel / $res1->qte)."&euro;";

    $responce->rows[$count]['id']=$res1->id;
    $responce->rows[$count]['cell']=array($res1->id,$res1->title,$res1->qte,price(($res1a->couthoraire."x"=="x"?0:$res1a->couthoraire))."&euro",$couthorairereel);

    $count ++;
}

$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;


echo json_encode($responce);

?>

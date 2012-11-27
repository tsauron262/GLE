<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../../main.inc.php');


 $campId = $_REQUEST['campId'];
 $user_id = $_REQUEST['userId'];

 $action = $_REQUEST['action'];

$user->id = $user_id;
$user->fetch();
$user->getrights();
$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction
if ('x'.$socid == "")
{
    $socid = false;
}

if(!$sidx) $sidx =1; // connect to the database



$wh = "";
$searchOn = $_REQUEST['_search'];
if($searchOn=='true')
{
    $oper="";
    $searchField = $_REQUEST['searchField'];
    $searchString = $_REQUEST['searchString'];

    if ($searchField == "socname")
    {
        $searchField = "".MAIN_DB_PREFIX."societe.nom";
    }
    if ($searchField == "date_prisecharge")
    {
        $tmpDate = trim($searchString);
        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/",$tmpDate,$arr))
        {
            $searchString = $arr[3].'-'.$arr[2].'-'.$arr[1];
        }
    }

    if ($searchField == "closeStatut" && $searchString == "0" && $_REQUEST['searchOper'] == 'eq')
    {
        $wh .=  " AND " . $searchField . " is null ";
    } else if ($searchField == "closeStatut" && $searchString == "0" && $_REQUEST['searchOper'] == 'ne')
    {
        $wh .=  " AND " . $searchField . " is not null ";
    } else if ($searchField == "closeStComm" && $searchString == "-2" && $_REQUEST['searchOper'] == 'eq')
    {
        $wh .=  " AND " . $searchField . " is  null ";
    } else if ($searchField == "closeStComm" && $searchString == "-2" && $_REQUEST['searchOper'] == 'ne')
    {
        $wh .=  " AND " . $searchField . " is not null ";
    }  else if ($searchField == "user_id" && $searchString == "-2" && $_REQUEST['searchOper'] == 'eq')
    {
        $wh .=  " AND " . $searchField . " is  null ";
    } else if ($searchField == "user_id" && $searchString == "-2" && $_REQUEST['searchOper'] == 'ne')
    {
        $wh .=  " AND " . $searchField . " is not null ";
    } else {
        if ($_REQUEST['searchOper'] == 'eq')
        {
            $oper = '=';
            $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
        } else if ($_REQUEST['searchOper'] == 'ne')
        {
            $oper = '<>';
            $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
        }  else if ($_REQUEST['searchOper'] == 'lt')
        {
            $oper = '<';
            $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
        }   else if ($_REQUEST['searchOper'] == 'gt')
        {
            $oper = '>';
            $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
        }   else if ($_REQUEST['searchOper'] == 'le')
        {
            $oper = '<=';
            $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
        }   else if ($_REQUEST['searchOper'] == 'ge')
        {
            $oper = '>=';
            $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
        }   else if ($_REQUEST['searchOper'] == 'bw')
        {
            $wh .= ' AND ' . $searchField . " LIKE  '".$searchString."%'" ;
        } else if ($_REQUEST['searchOper'] == 'bn')
        {
            $wh .= ' AND ' . $searchField . " NOT LIKE  '".$searchString."%'" ;
        } else if ($_REQUEST['searchOper'] == 'in')
        {
            $wh .= ' AND ' . $searchField . " IN  ('".$searchString."')" ;
        } else if ($_REQUEST['searchOper'] == 'ni')
        {
            $wh .= ' AND ' . $searchField . " NOT IN  ('".$searchString."')" ;
        } else if ($_REQUEST['searchOper'] == 'ew')
        {
            $wh .= ' AND ' . $searchField . " LIKE  '%".$searchString."'" ;
        } else if ($_REQUEST['searchOper'] == 'en')
        {
            $wh .= ' AND ' . $searchField . " NOT LIKE  '%".$searchString."'" ;
        } else if ($_REQUEST['searchOper'] == 'cn')
        {
            $wh .= ' AND ' . $searchField . " LIKE  '%".$searchString."%'" ;
        } else if ($_REQUEST['searchOper'] == 'nc')
        {
            $wh .= ' AND ' . $searchField . " NOT LIKE  '%".$searchString."%'" ;
        }
    }

}


switch ($action)
{
    case 'sub':
    {
        $SQL = "SELECT count(*) as cnt
                  FROM Babel_campagne_societe
             LEFT JOIN Babel_campagne_avancement ON  Babel_campagne_societe.campagne_refid = Babel_campagne_avancement.campagne_refid    AND Babel_campagne_societe.societe_refid = Babel_campagne_avancement.societe_refid  ";
        $SQL .= "
                 WHERE 1 = 1";
          $SQL .= " AND Babel_campagne_societe.societe_refid = ".$_REQUEST["campsocid"];
          $SQL .= " AND Babel_campagne_societe.id = ".$campId;

        $SQL .= "  ".$wh;
//        print $SQL;
        $result = $db->query($SQL);
        $row = $db->fetch_array($result,MYSQL_ASSOC);
        $count = $row['cnt'];
        if( $count >0 )
        {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        //avancement du projet,
        //nb de tache pour moi/en tout ,
        //date debut,
        //date fin,
        //role

        $SQL = "SELECT date_format(Babel_campagne_avancement.dateModif,'%d/%m/%Y') as dateModiff,
                       Babel_campagne_avancement.avis,
                       Babel_campagne_avancement.avancement,
                       Babel_campagne_avancement.note,
                       Babel_campagne_avancement.id
                  FROM Babel_campagne_societe
            RIGHT JOIN Babel_campagne_avancement ON  Babel_campagne_societe.campagne_refid = Babel_campagne_avancement.campagne_refid
                                                AND Babel_campagne_societe.societe_refid = Babel_campagne_avancement.societe_refid ";
        $SQL .= "
                 WHERE 1 = 1";
          $SQL .= " AND Babel_campagne_societe.campagne_refid = ".$campId;
          $SQL .= " AND Babel_campagne_societe.id = ".$_REQUEST["campsocid"];

        $SQL .= "  ".$wh."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
//        print $SQL;

        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_array($result,MYSQL_ASSOC))
        {
            $soc= new Societe($db);
            $soc->fetch($row[socid]);
            $responce->rows[$i]['id']=$row[id];
            $localUser = new User($db);
            $localUser->id = $row[fk_user_resp];
            $localUser->fetch();
            $overallprogress = '<div class="progressbar ui-corner-all">'.round($row[statut]).'</div>';
            $responce->rows[$i]['cell']=array($row[id],
                                              $row[dateModiff],
                                              starratingPhp("Avis",$row[avis]),
                                              '<div class="progressbar ui-corner-all">'.$row[avancement] * 10 .'</div>',
                                              $row[note]
                                              );
            $i++;
        }
        echo json_encode($responce);
    }
    break;
    default :
    {
        $SQL = "SELECT count(*) as cnt
                  FROM Babel_campagne_societe ";
        $SQL .= "
                 WHERE 1 = 1";
          $SQL .= " AND Babel_campagne_societe.campagne_refid = ".$campId;

        $SQL .= "  ".$wh;
        //print $SQL;
        $result = $db->query($SQL);
        $row = $db->fetch_array($result,MYSQL_ASSOC);
        $count = $row['cnt'];
        if( $count >0 )
        {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        //avancement du projet,
        //nb de tache pour moi/en tout ,
        //date debut,
        //date fin,
        //role

        $SQL = "SELECT Babel_campagne_societe.id,
                       Babel_campagne_societe.closeStatut,
                       ifnull(Babel_campagne_societe.closeStComm,-2) as closeStComm,
                       date_format(Babel_campagne_societe.date_prisecharge,'%d/%m/%Y') as date_prisechargeF,
                       Babel_campagne_societe.fk_statut,
                       Babel_campagne_societe.user_id,
                       ".MAIN_DB_PREFIX."societe.nom as socname
                  FROM Babel_campagne_societe
             LEFT JOIN ".MAIN_DB_PREFIX."societe ON  Babel_campagne_societe.societe_refid = ".MAIN_DB_PREFIX."societe.rowid ";
        $SQL .= "
                 WHERE 1 = 1";
          $SQL .= " AND Babel_campagne_societe.campagne_refid = ".$campId;

        $SQL .= "  ".$wh."
              ORDER BY $sidx $sord
                 LIMIT $start , $limit";
//        print $SQL;

        $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        $requete1 = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_stcomm where active = 1";
        $sql1 = $db->query($requete1);
        $arrStcomm = array();
//        $arrStcomm["a-2"]="<img align=absmiddle  src='".DOL_URL_ROOT."/theme/auguria/img/statut0.png'>  " . $langs->trans("En cours");
        while ($res1 = $db->fetch_object($sql1))
        {
            $arrStcomm["a".$res1->id] = img_action(0,$res1->id)." ".htmlentities($res1->libelle);
        }
        $arrRes[1]="<img align=absmiddle  height=12px width=12px src='".DOL_URL_ROOT."/theme/".$conf->theme."/plus.gif'> Positif";
        $arrRes[2]="<img align=absmiddle  height=12px width=12px src='".DOL_URL_ROOT."/theme/".$conf->theme."/moins.gif'> N&eacute;gatif";


//var_dump($arrStcomm);
//        $arrStcomm[-1]="<img align=absmiddle  src='".DOL_URL_ROOT."/theme/auguria/img/stcomm-1.png'>" . $langs->trans("ChangeDoNotContact");
//        $arrStcomm[0]="<img align=absmiddle   src='".DOL_URL_ROOT."/theme/auguria/img/stcomm0.png'>" . $langs->trans("ChangeNeverContacted");
//        $arrStcomm[1]="<img align=absmiddle   src='".DOL_URL_ROOT."/theme/auguria/img/stcomm4.png'>" . $langs->trans("ChangeToContact");
//        $arrStcomm[2]="<img align=absmiddle   src='".DOL_URL_ROOT."/theme/auguria/img/stcomm2.png'>" .$langs->trans("ChangeContactInProcess");
//        $arrStcomm[3]="<img align=absmiddle   src='".DOL_URL_ROOT."/theme/auguria/img/stcomm3.png'>" . $langs->trans("ChangeContactDone");
//        $arrStcomm[4]="<img align=absmiddle   src='".DOL_URL_ROOT."/theme/auguria/img/stcomm4.png'>";

        $arrStatut[1]="En attente";
        $arrStatut[2]="En cours";
        $arrStatut[3]="Ferm&eacute;";
        $arrStatut[4]="Repousser";

        while($row = $db->fetch_array($result,MYSQL_ASSOC))
        {
            $soc= new Societe($db);
            $soc->fetch($row[socid]);
            $responce->rows[$i]['id']=$row[id];
            $localUser = new User($db);
            $localUser->id = $row[user_id];
            $localUser->fetch();
            $overallprogress = '<div class="progressbar ui-corner-all">'.round($row[statut]).'</div>';
            $imgClose =  $arrStcomm["a".$row[closeStComm]]."\n";
            if ($arrStcomm["a".$row[closeStComm]] . "x" == "x" )
            {
//                var_dump ($row[closeStComm]. " ".$row[socname]);
            }
            $responce->rows[$i]['cell']=array($row[id],
                                              $arrRes[$row[closeStatut]],
                                              $imgClose,
                                              $row[date_prisechargeF],
                                              $arrStatut[$row[fk_statut]],
                                              utf8_encode($localUser->fullname),
                                              utf8_encode($row[socname]));
            $i++;
        }
//        var_dump($responce);
        echo json_encode($responce);
    }
    break;
}
function starratingPhp($name,$value)
{
    $ret = "";
    $ret .= '<div id="starrating">';
    for($i=0.5;$i<5.5;$i+=0.5)
    {
        if ($value == $i)
        {
            $ret .= '<input class="star {half:true}" type="radio" name="'.$name.'" value="'.preg_replace('/,/',".",$i).'" checked="checked"/>';
        } else {
            $ret .= '<input class="star {half:true}" type="radio" name="'.$name.'" value="'.preg_replace('/,/',".",$i).'"/>';
        }
    }
    $ret .= '</div>';
    return ($ret);
}

?>
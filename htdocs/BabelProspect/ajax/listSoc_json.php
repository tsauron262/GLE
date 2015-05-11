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

//TODO filtering

 $action = $_REQUEST['action'];
 $campagne_id = $_REQUEST['campagneId'];

$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction
if(!$sidx) $sidx =1; // connect to the database

//$wh = "";
//$searchOn = $_REQUEST['_search'];
//if($searchOn=='true')
//{
//    $sarr = $_REQUEST;
//    foreach( $sarr as $k=>$v)
//    {
//        switch ($k)
//        {
//            case 'id':
//            case 'nom':
//            case 'fk_effectif':
//            case 'fk_departement':
//            case 'fk_secteur':
//                $wh .= " AND ".$k." LIKE '".$v."%'";
//            break;
//        }
//    }
//}
$wh = "";
$searchOn = $_REQUEST['_search'];
if($searchOn=='true')
{
    $oper="";
    $searchField =  $_REQUEST['searchField'];
    if ($searchField == 'nom'){ $searchField = "".MAIN_DB_PREFIX."societe.nom";}
    if ($_REQUEST['searchOper'] == 'eq' && $_REQUEST['searchString']!= -1)
    {
        $oper = '=';
        $wh .=  " AND " . $searchField . " ".$oper." '".$_REQUEST['searchString']."'";
    } else if ($_REQUEST['searchOper'] == 'ne' && $_REQUEST['searchString']!= -1)
    {
        $oper = '<>';
        $wh .=  " AND " . $searchField . " ".$oper." '".$_REQUEST['searchString']."'";
    }  else if ($_REQUEST['searchOper'] == 'lt' && $_REQUEST['searchString']!= -1)
    {
        $oper = '<';
        $wh .=  " AND " . $searchField . " ".$oper." '".$_REQUEST['searchString']."'";
    }   else if ($_REQUEST['searchOper'] == 'gt' && $_REQUEST['searchString']!= -1)
    {
        $oper = '>';
        $wh .=  " AND " . $searchField . " ".$oper." '".$_REQUEST['searchString']."'";
    }   else if ($_REQUEST['searchOper'] == 'le' && $_REQUEST['searchString']!= -1)
    {
        $oper = '<=';
        $wh .=  " AND " . $searchField . " ".$oper." '".$_REQUEST['searchString']."'";
    }   else if ($_REQUEST['searchOper'] == 'ge' && $_REQUEST['searchString']!= -1)
    {
        $oper = '>=';
        $wh .=  " AND " . $searchField . " ".$oper." '".$_REQUEST['searchString']."'";
    }   else if ($_REQUEST['searchOper'] == 'bw' && $_REQUEST['searchString']!= -1)
    {
        $wh .= ' AND ' .$searchField . " LIKE  '".$_REQUEST['searchString']."%'" ;
    } else if ($_REQUEST['searchOper'] == 'bn' && $_REQUEST['searchString']!= -1)
    {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '".$_REQUEST['searchString']."%'" ;
    } else if ($_REQUEST['searchOper'] == 'in' && $_REQUEST['searchString']!= -1)
    {
        $wh .= ' AND ' . $searchField . " IN  ('".$_REQUEST['searchString']."')" ;
    } else if ($_REQUEST['searchOper'] == 'ni' && $_REQUEST['searchString']!= -1)
    {
        $wh .= ' AND ' .$searchField. " NOT IN  ('".$_REQUEST['searchString']."')" ;
    } else if ($_REQUEST['searchOper'] == 'ew' && $_REQUEST['searchString']!= -1)
    {
        $wh .= ' AND ' . $searchField. " LIKE  '%".$_REQUEST['searchString']."'" ;
    } else if ($_REQUEST['searchOper'] == 'en' && $_REQUEST['searchString']!= -1)
    {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '%".$_REQUEST['searchString']."'" ;
    } else if ($_REQUEST['searchOper'] == 'cn' && $_REQUEST['searchString']!= -1)
    {
        $wh .= ' AND ' . $searchField . " LIKE  '%".$_REQUEST['searchString']."%'" ;
    } else if ($_REQUEST['searchOper'] == 'nc' && $_REQUEST['searchString'] != -1)
    {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '%".$_REQUEST['searchString']."%'" ;
    } else {

        $array = array('fk_departement','fk_effectif','fk_secteur');
        foreach($_REQUEST as $key=>$val)
        {
            if (in_array($key,$array) &&  $val != "-1")
            {
                $wh .= ' AND ' . $key . " =  '".$val."'" ;
            } else if ($key == "nom")
            {
                if ($key=='nom'){ $key="".MAIN_DB_PREFIX."societe.nom";}
                $wh .= ' AND ' . $key . " LIKE  '%".$val."%'" ;
            }
        }



    }

}

switch ($action)
 {
    case 'add';
        require_once(DOL_DOCUMENT_ROOT.'/BabelProspect/Campagne.class.php');
        $socidStr = $_REQUEST['socidStr'];
        $socidArr = preg_split('/,/', $socidStr);
        $listed = $_REQUEST['listed'];
        $obj = new CampagneSoc($db);
        if ($listed == 'unlisted')
        {
            foreach($socidArr as $key => $socid)
                $obj->create($socid,$campagne_id);
        } else {
            foreach($socidArr as $key => $socid)
                $obj->delete($socid,$campagne_id);
        }
        header("Content-Type: text/xml");
        $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
        print "<ajax-response>\n";
        print "</ajax-response>\n";

    break;
        case 'listed':

        $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."societe WHERE client > 0 AND ".MAIN_DB_PREFIX."societe.rowid not in (SELECT societe_refid FROM Babel_campagne_societe WHERE campagne_refid = ".$campagne_id.") ".$wh);
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
         $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                                       ".MAIN_DB_PREFIX."societe.client,
                                       ".MAIN_DB_PREFIX."societe.nom,
                                       ".MAIN_DB_PREFIX."societe.ville,
                                       ".MAIN_DB_PREFIX."societe.fk_effectif,
                                       ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
                                       ".MAIN_DB_PREFIX."societe.fk_departement,
                                       CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
                                       ".MAIN_DB_PREFIX."societe.fk_secteur,
                                       ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
                                      FROM ".MAIN_DB_PREFIX."societe
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
                                     WHERE client > 0 ".$wh."
                                       AND ".MAIN_DB_PREFIX."societe.rowid not in (SELECT societe_refid FROM Babel_campagne_societe WHERE campagne_refid = ".$campagne_id.")
                                  ORDER BY $sidx $sord
                                     LIMIT $start , $limit";

//
//        print $requete;
        $result = $db->query( $requete ) or die("Couldn t execute query.".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_array($result,MYSQL_ASSOC))
        {
            $responce->rows[$i]['id']=$row[rowid];
            $responce->rows[$i]['cell']=array($row[rowid],$row[nom],$row[fk_effectif],$row[effectifStr],$row[fk_departement],$row[departmentStr],$row[fk_secteur],$row[secteurStr]);
            $i++;
        }
        echo json_encode($responce);
    break;
    case 'unlisted':
        $result = $db->query("SELECT COUNT(*) AS count FROM ".MAIN_DB_PREFIX."societe WHERE client > 0  AND  ".MAIN_DB_PREFIX."societe.rowid in (SELECT societe_refid FROM Babel_campagne_societe WHERE campagne_refid = ".$campagne_id.") ".$wh);
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
         $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                                       ".MAIN_DB_PREFIX."societe.client,
                                       ".MAIN_DB_PREFIX."societe.nom,
                                       ".MAIN_DB_PREFIX."societe.ville,
                                       ".MAIN_DB_PREFIX."societe.fk_effectif,
                                       ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
                                       ".MAIN_DB_PREFIX."societe.fk_departement,
                                       CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
                                       ".MAIN_DB_PREFIX."societe.fk_secteur,
                                       ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
                                      FROM ".MAIN_DB_PREFIX."societe
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
                                 LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
                                     WHERE client > 0 ".$wh."
                                       AND ".MAIN_DB_PREFIX."societe.rowid  in (SELECT societe_refid FROM Babel_campagne_societe WHERE campagne_refid = ".$campagne_id.")
                                  ORDER BY $sidx $sord
                                     LIMIT $start , $limit" ;

//        print $requete;
        $result = $db->query( $requete ) or die("Couldn t execute query.".mysql_error());
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i=0;
        while($row = $db->fetch_array($result,MYSQL_ASSOC))
        {
            $responce->rows[$i]['id']=$row[rowid];
            $responce->rows[$i]['cell']=array($row[rowid],utf8_encode($row[nom]),$row[fk_effectif],utf8_encode($row[effectifStr]),$row[fk_departement],utf8_encode($row[departmentStr]),$row[fk_secteur],utf8_encode($row[secteurStr]));
            $i++;
        }
        echo json_encode($responce);

}
//switch ($action)
// {
//    case 'add';
//        require_once(DOL_DOCUMENT_ROOT.'/BabelProspect/Campagne.class.php');
//        $socid = $_REQUEST['socid'];
//        $listed = $_REQUEST['listed'];
//        $obj = new CampagneSoc($db);
//        if ($listed == 'unlisted')
//        {
//            $obj->create($socid,$campagne_id);
//        } else {
//            $obj->delete($socid,$campagne_id);
//        }
//    break;
//    case 'listed':
//    $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
//                       ".MAIN_DB_PREFIX."societe.client,
//                       ".MAIN_DB_PREFIX."societe.nom,
//                       ".MAIN_DB_PREFIX."societe.ville,
//                       ".MAIN_DB_PREFIX."societe.fk_effectif,
//                       ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
//                       ".MAIN_DB_PREFIX."societe.fk_departement,
//                       CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
//                       ".MAIN_DB_PREFIX."societe.fk_secteur,
//                       ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
//                      FROM ".MAIN_DB_PREFIX."societe
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
//                     WHERE client > 0
//                       AND ".MAIN_DB_PREFIX."societe.rowid not in (SELECT societe_refid FROM Babel_campagne_societe WHERE id = ".$campagne_id.")
//                  ORDER BY ".MAIN_DB_PREFIX."societe.nom ,
//                           ".MAIN_DB_PREFIX."societe.client,
//                           ".MAIN_DB_PREFIX."societe.ville,
//                           ".MAIN_DB_PREFIX."societe.fk_departement,
//                           ".MAIN_DB_PREFIX."societe.fk_effectif,
//                           ".MAIN_DB_PREFIX."societe.fk_secteur ";
////                           print $requete;
////        print "<table>";
//        header("Content-Type: text/xml");
//        $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
//
//        print "<ajax-response>\n";
//        print "\t<response type='object' id='".$id."_updater'>\n";
//        print "\t\t<rows update_ui='true' offset='".$offset."'>\n";
//        if ($resql = $db->query($requete))
//        {
//            while ($res=$db->fetch_object($resql))
//            {
//                print "\t\t\t<tr>
//                           \t\t\t\t<td>".$res->rowid."</td>\n
//                           \t\t\t\t<td>".$res->rowid."</td>\n
//                           \t\t\t\t<td>".$res->client."</td>\n
//                           \t\t\t\t<td>". utf8_encode($res->nom)."</td>\n
//                           \t\t\t\t<td>". utf8_encode($res->ville)."</td>\n
//                           \t\t\t\t<td>". utf8_encode($res->departmentStr)."</td>\n
//                           \t\t\t\t<td>".$res->fk_effectif."</td>\n
//                           \t\t\t\t<td>".$res->effectifStr."</td>\n
//                           \t\t\t\t<td>".$res->fk_secteur."</td>\n
//                           \t\t\t\t<td>".$res->secteurStr."</td></tr>\n";
//            }
//        }
//        print "\t\t</rows>\n";
//        print "\t</response>\n";
//        print "</ajax-response>\n";
//
//    break;
//    case 'unlisted':
//    $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
//                       ".MAIN_DB_PREFIX."societe.client,
//                       ".MAIN_DB_PREFIX."societe.nom,
//                       ".MAIN_DB_PREFIX."societe.ville,
//                       ".MAIN_DB_PREFIX."societe.fk_effectif,
//                       ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
//                       ".MAIN_DB_PREFIX."societe.fk_departement,
//                       CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
//                       ".MAIN_DB_PREFIX."societe.fk_secteur
//                       ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
//                      FROM ".MAIN_DB_PREFIX."societe
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
//                 LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
//                     WHERE client > 0
//                       AND ".MAIN_DB_PREFIX."societe.rowid not in (SELECT societe_refid FROM Babel_campagne_societe WHERE id = ".$campagne_id.")
//                  ORDER BY ".MAIN_DB_PREFIX."societe.nom ,
//                           ".MAIN_DB_PREFIX."societe.client,
//                           ".MAIN_DB_PREFIX."societe.ville,
//                           ".MAIN_DB_PREFIX."societe.fk_departement,
//                           ".MAIN_DB_PREFIX."societe.fk_effectif,
//                           ".MAIN_DB_PREFIX."societe.fk_secteur ";
////                           print $requete;
////        print "<table>";
//        header("Content-Type: text/xml");
//        $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
//
//        print "<ajax-response>\n";
//        print "\t<response type='object' id='".$id."_updater'>\n";
//        print "\t\t<rows update_ui='true' offset='".$offset."'>\n";
//        if ($resql = $db->query($requete))
//        {
//            while ($res=$db->fetch_object($resql))
//            {
//                print "\t\t\t<tr>
//                           \t\t\t\t<td>".$res->rowid."</td>\n
//                           \t\t\t\t<td>".$res->rowid."</td>\n
//                           \t\t\t\t<td>".$res->client."</td>\n
//                           \t\t\t\t<td>". utf8_encode($res->nom)."</td>\n
//                           \t\t\t\t<td>". utf8_encode($res->ville)."</td>\n
//                           \t\t\t\t<td>". utf8_encode($res->departmentStr)."</td>\n
//                           \t\t\t\t<td>".$res->fk_effectif."</td>\n
//                           \t\t\t\t<td>".$res->effectifStr."</td>\n
//                           \t\t\t\t<td>".$res->fk_secteur."</td>\n
//                           \t\t\t\t<td>".$res->secteurStr."</td></tr>\n";
//            }
//        }
//        print "\t\t</rows>\n";
//        print "\t</response>\n";
//        print "</ajax-response>\n";
//
//    break;
//
// }

?>
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


 $action = $_REQUEST['action'];
 $campagne_id = $_REQUEST['campagneId'];

$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction



if(!$sidx) $sidx =1; // connect to the database


$wh = "";
$searchOn = $_REQUEST['_search'];
if($searchOn=='true')
{
    $sarr = $_REQUEST;
    foreach( $sarr as $k=>$v)
    {
        switch ($k)
        {
            case 'id':
            case 'nom':
            case 'datedeb':
            case 'datefin':
                $wh .= " AND ".$k." LIKE '".$v."%'";
            break;
            case 'Tiers':
                if (preg_match('/[<>]{1}/',$v,$arr))
                {
                    $wh .= " AND (SELECT count(*) FROM Babel_campagne_societe  WHERE campagne_refid = Babel_campagne.id)  ".$arr[1]. $v . " ";
                } else if (preg_match('/[!=]/',$v)){
                    $wh .= " AND (SELECT count(*) FROM Babel_campagne_societe  WHERE campagne_refid = Babel_campagne.id)  ".$arr[1] .$v . " ";
                } else {
                    $wh .= " AND (SELECT count(*) FROM Babel_campagne_societe  WHERE campagne_refid = Babel_campagne.id) = ".$v . " ";

                }
            break;
            case 'fk_statut':
                if ($v > 0)
                    $wh .= " AND fk_statut = ".$v;
            break;
        }
    }
}


$result = $db->query("SELECT COUNT(*) AS count FROM Babel_campagne WHERE 1=1 ".$wh);
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
$SQL = "SELECT id,
               nom,
               ifnull(dateDebutEffective,dateDebut) as datedeb,
               ifnull(dateFinEffective, dateFin) as datefin,
               fk_statut as statut,
               (SELECT count(*)
                  FROM Babel_campagne_societe
                 WHERE campagne_refid = Babel_campagne.id) as Tiers
          FROM Babel_campagne
         WHERE 1 = 1 ".$wh."
      ORDER BY $sidx $sord
         LIMIT $start , $limit";
//print $SQL;
$result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error());
$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
$i=0;
while($row = $db->fetch_array($result,MYSQL_ASSOC))
{
    require_once('../Campagne.class.php');
    $cam = new Campagne($db);
    $cam->fetch($row[id]);
    $nom = $cam->getNomUrl(1);
    $statut = $cam->getLibStatut(5);
    $responce->rows[$i]['id']=$row[id];
    $responce->rows[$i]['cell']=array($row[id],$nom,$row[datedeb],$row[datefin],$statut,$row[Tiers]);
    $i++;
}
echo json_encode($responce);


?>
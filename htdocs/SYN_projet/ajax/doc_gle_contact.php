<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 21 oct. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : doc_zimbra_contact.php
  * GLE-1.1
  */
require_once('../../master.inc.php');
$tmp = $_GET["q"];
$q = strtolower(utf8_decode($tmp));
//print $q . '<br/>';
$limit = 10;
if($_REQUEST['limit'].'x' != 'x')
{
    $limit = $_REQUEST['limit'];
}
if (!$q) return;


//Get User par l'entree
//fait le array
//envoi

$requete = "(SELECT concat('s',rowid) as id, name as nom, email , firstname
              FROM ".MAIN_DB_PREFIX."socpeople
             WHERE name like '%".$q."%'
                OR firstname like '%".$q."%'
                OR email like '%".$q."%' )
UNION DISTINCT
            (SELECT concat('o',rowid) as id, nom, email, null
              FROM ".MAIN_DB_PREFIX."societe
             WHERE nom like '%".$q."%'
                OR email like '%".$q."%')
UNION DISTINCT
            (SELECT concat('u',rowid) as id, name as nom, email, firstname
              FROM ".MAIN_DB_PREFIX."user
             WHERE login like '%".$q."%'
                OR email like '%".$q."%'
                OR name like '%".$q."%'
                OR firstname like '%".$q."%'
            )
LIMIT ".$limit;

$sql = $db->query($requete);
while ($res = $db->fetch_object($sql))
{
    echo $res->id.'|'.($res->firstname.'x' != 'x'?utf8_encode($res->firstname)." ":"") . utf8_encode($res->nom) .'|'.$res->email."\n";
}

//$items = array(
//"Great <em>Bittern</em>"=>"Botaurus stellaris",
//"Little <em>Grebe</em>"=>"Tachybaptus ruficollis",
//"Spanish Wagtail"=>"Motacilla iberiae",
//"Song Sparrow"=>"Melospiza melodia",
//"Rock Bunting"=>"Emberiza cia",
//"Siberian Rubythroat"=>"Luscinia calliope",
//"Pallid Swift"=>"Apus pallidus",
//"Eurasian Pygmy Owl"=>"Glaucidium passerinum",
//"Madeira Little Shearwater"=>"Puffinus baroli",
//"House Finch"=>"Carpodacus mexicanus",
//"Green Heron"=>"Butorides virescens",
//"Solitary Sandpiper"=>"Tringa solitaria",
//"Heuglin's Gull"=>"Larus heuglini"
//);
//
//$i=0;
//foreach ($items as $key=>$value) {
//    if (strpos(strtolower($key), $q) !== false) {
//        echo "$key|$value|$i\n";
//        $i++;
//    }
//}

?>
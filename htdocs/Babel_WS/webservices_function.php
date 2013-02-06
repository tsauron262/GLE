<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 30 juin 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : webservices_function.php
  * GLE-1.1
  */
require_once('../main.inc.php');

function getItemCount($upc){
    global $db;
    //in reality, this data would be coming from a database
    $items = array('12345'=>5,'19283'=>100,'23489'=>'234');
    return $items[$upc];
}
function getContratList($upc){
    global $db;
    $upc=utf8_decode($upc);
    $upc = addslashes($upc);
    //in reality, this data would be coming from a database
    $requete = "SELECT ".MAIN_DB_PREFIX."contrat.rowid, ".MAIN_DB_PREFIX."contrat.ref
                  FROM ".MAIN_DB_PREFIX."contrat,
                       ".MAIN_DB_PREFIX."societe
                 WHERE fk_soc = ".MAIN_DB_PREFIX."societe.rowid
                  AND (".MAIN_DB_PREFIX."societe.nom = '".$upc."' OR  ".MAIN_DB_PREFIX."societe.code_client = '".$upc."' OR  ".MAIN_DB_PREFIX."societe.rowid = '".$upc."')  ";
    //TODO and is active
    $sql = $db->query($requete);
//    file_put_contents('/tmp/toto',$requete);
    $items = array();
    $arr = array();
    while ($res=$db->fetch_object($sql))
    {
        array_push($arr,$res->rowid.":".utf8_encode($res->ref));
    }
    $items = join(',',$arr);
    return $items;
}
function getItemList(){
    global $db;
    //in reality, this data would be coming from a database
    $item = array();
    for ($i=0;$i<100;$i++)
    {
        $item[$i]=rand(0,1024);
    }
    array_rand($item,100);
    $items = join(',',$item);
    return $items;
}
function setContratNbTicket($newValue){
    global $db;
    $toto = "roro";
    //$toto .= print_r($db,1);
    return 'new Value: '.$newValue." ".$toto;
}

function listSocWithContract()
{
    global $db;
    $requete ="SELECT distinct ".MAIN_DB_PREFIX."societe.nom, ".MAIN_DB_PREFIX."societe.rowid, ".MAIN_DB_PREFIX."societe.code_client
                 FROM ".MAIN_DB_PREFIX."contrat,
                      ".MAIN_DB_PREFIX."societe
                WHERE fk_soc = ".MAIN_DB_PREFIX."societe.rowid ";
    $sql = $db->query($requete);
    $arr = array();
    while ($res=$db->fetch_object($sql))
    {
        array_push($arr,$res->code_client.":".utf8_encode($res->nom));
    }
    $items = join(',',$arr);
    return $items;
}


function createTicketAction($idsoc,$idcont,$userMail,$subject,$queue,$priority)
{
    global $db;
error_log('toto',0);
    $idsoc=utf8_decode($idsoc);
    $idsoc = addslashes($idsoc);
    //Socid
    $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid
                  FROM ".MAIN_DB_PREFIX."societe
                 WHERE ".MAIN_DB_PREFIX."societe.nom = '".$idsoc."'";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $socid  = $res->rowid;
    //userid
    $userMail=utf8_decode($userMail);
    $userMail = addslashes($userMail);
    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."user
                 WHERE email ='".$userMail."'";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $userid  = $res->rowid;
    //Stock le ticket
    error_log(print_r($res),0);

    //Si contrat au ticket => soustraction
    //retourne le nombre de contrat
    return(4012);



}
?>

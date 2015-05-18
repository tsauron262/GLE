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
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");

$tmp = $_GET["q"];
$q = strtolower(utf8_decode($tmp));
//print $q . '<br/>';
$limit = 10;
if($_REQUEST['limit'].'x' != 'x')
{
    $limit = $_REQUEST['limit'];
}
if (!$q) return;
$userId = $_REQUEST['userid'];

//Connect Zimbra
$tmpUser = new User($db);
$tmpUser->fetch($userId);

$zimuser="";
if ($conf->global->ZIMBRA_ZIMBRA_USE_LDAP=="true")
{
    $zimuser=$tmpUser->login;
} else {
    $user->getZimbraCred($tmpUser->id);
    $zimuser=$tmpUser->ZimbraLogin;
}
 $zim = new Zimbra($zimuser);
 $zim->debug=false;
 $zim->langs=$langs;
 $ret = $zim->connect();

$optionArr = array();
array_push($optionArr,array('lastname' => $q.'*"', 'condition' => "OR") );
array_push($optionArr,array('firstname' => $q.'*', 'condition' => "OR") );
array_push($optionArr,array('email' => $q.'*', 'condition' => "OR") );
array_push($optionArr,array('company' => $q.'*', 'condition' => "OR") );

 $ret1 = $zim->BabelSearchRequest($optionArr, "anywhere",'contact',$limit,'nameAsc');
var_dump($ret1);



//Get User parl'entree
//fait le array
//envoi

//$items = array(
//"Great <em>Bittern</em>"=>"Botaurus stellaris",
//"Little <em>Grebe</em>"=>"Tachybaptus ruficollis",
//"Black-necked Grebe"=>"Podiceps nigricollis",
//"Little Bittern"=>"Ixobrychus minutus",
//"Black-crowned Night Heron"=>"Nycticorax nycticorax",
//"Purple Heron"=>"Ardea purpurea",
//"White Stork"=>"Ciconia ciconia",
//"Spoonbill"=>"Platalea leucorodia",
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
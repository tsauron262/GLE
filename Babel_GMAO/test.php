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
  * Name : test.php
  * GLE-1.1
  */

require_once('../main.inc.php');

$username = "root";
$password = "password";
$url = "http://rtdemo.etatcritik.dyndns.org/rt/REST/1.0/ticket/3/show?user=$username&pass=$password";
require_once('Var_Dump.php');
$request = new HttpRequest($url, HTTP_METH_GET);
$request->addPostFields( $post_data );
$response = $request->send();
Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));
Var_Dump::display($response);

require_once('rt.class.php');
$rt = new rt($db);
//$rt->fetch(3);
//$rt->showLinks();
//$rt->getHistory();

$res = $rt->searchTicket(urlencode("(( Status = 'new' OR Status = 'open' ) AND (Created > '2010-06-06 00:00'))"));
Var_Dump::display($res);

$res = $rt->searchTicket(urlencode("( Status = 'resolved' AND Queue = 'General')"));
$res = $rt->searchTicket(urlencode("( Queue = 'General' AND Owner = 'eos')"));


//$username = "root";
//$password = "password";
//$url = "http://rtdemo.etatcritik.dyndns.org/REST/1.0/ticket/new?user=$username&pass=$password";
//
//$request = new HttpRequest($url, HTTP_METH_POST);
//$post_data=array("content"=>"Queue: General\nRequestor: user@domain\nSubject: REST test 1\nOwner: userX\nAdminCc: userX\nText: This is a REST test\n");
//$request->addPostFields( $post_data );
//
//$response = $request->send();
//
//try {
//   echo $request->send()->getBody();
//} catch (HttpException $ex) {
//   echo $ex;
//}


?>

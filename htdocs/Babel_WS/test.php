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
  require_once('../master.inc.php');
  $cookieName = "GleWSCookie";

  //$user->id=59;

ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
ini_set('soap.wsdl_cache_ttl',0);
ini_set('soap.wsdl_cache',0);
clearstatcache();
//phpinfo();


    ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
    //var_dump(ini_get_all("soap"));
//    $secret= md5(time()*rand(0,1000)/1000);
//    $requete = "DELETE FROM `Babel_WS_auth` WHERE user_id = ".$user->id;
//    $sql=$db->query($requete);
//
//    $requete = "INSERT INTO `Babel_WS_auth`
//                            (`tms`,`cookie_value`,`user_id`)
//                     VALUES (now(), '".$secret."', ".$user->id.")";
//    $sql=$db->query($requete);
try {
        $preclient = new SoapClient("http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/connect.wsdl");
        $login ='eos';
        $md5pass = md5('redalert');
        $return = $preclient->WSconnect($login,$md5pass);
        //var_dump($return);
        $secret = $return;

        try {
            $client = new SoapClient("http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/BabelGMAO.wsdl",array('cache_wsdl' => 0));
            $client->__setCookie ( $cookieName, $secret);
//            $client->connect('eos');

//            $return = $client->listSocWithContract();
//            print_r($return);
//            print "<br/>";


//            $return = $client->getItemCount('12345');

//            print_r($return);
            print "<br/>";
//            $return=$client->getContratList(177);
$idsoc ="177";
$idCont="1";
$userMail="eos@demo.synopsis-erp.com";
$subject="test";
$queue = "hotline";
$priority=1;
$return = $client->createTicketAction($idsoc,$idcont,$userMail,$subject,$queue,$priority);
            print_r($return);
//
//            print "<br/>";
//            $return=$client->getItemList('123123');
//            print_r(  explode(",",$return));
//
//            print "<br/>";
//            $return=$client->setContratNbTicket('123123');
//            print_r(  $return);
        }
        catch( Exception $e ) {
            //var_dump($e);
            echo 'Exception recue : ',  $e->getMessage(), "\n";

        }


}
catch( Exception $e ) {
    var_dump($e);
    echo 'Exception recue : ',  $e->getMessage(), "\n";

}



?>

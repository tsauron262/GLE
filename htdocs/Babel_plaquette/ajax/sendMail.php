<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : sendMail.php
  * GLE-1.1
  */

  require_once("../../main.inc.php");
  require_once(DOL_DOCUMENT_ROOT.'/core/lib/CMailFile.class.php');
  require_once(DOL_DOCUMENT_ROOT."/Babel_plaquette/plaquette.class.php");

  global $langs, $user;

  $subject = utf8_decode($_REQUEST['subject']);
  $to=utf8_decode($_REQUEST['to']);
  $from=$user->email;
  $model = utf8_decode($_REQUEST['id']);
  $filename_list=array();
  $mimetype_list=array();
  $mimefilename_list=array();
  $addr_cc="";
  $addr_bcc="";
  $deliveryreceipt=0;
  $msgishtml=1;
  $errors_to='';

  $requete = "SELECT content
                FROM Babel_plaquette
               WHERE id = ".$model;
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);
  $msg = $res->content;

  $arrDes=array();
  $arrDes['genre']='MR';
  $arrDes['nom']='Dupont';
  $arrDes['prenom']='Pierre';
  $arrDes['email']='p.dupont@test-email.com';

  $plaq = new Plaquette($db);

  $requete = "SELECT ecm_id
                FROM Babel_Plaquette_label
            ORDER BY RAND( )
               LIMIT 1";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $randId = $res->ecm_id;

    $plaq->fetch($randId);

    $res=$plaq->sendMail($subject,$to,$from,$model,false,$arrDes,
                         $filename_list,$mimetype_list,$mimefilename_list,
                         $addr_cc,$addr_bcc,$deliveryreceipt,$msgishtml,$errors_to);

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") )
    {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    $xml = "<ajax-response>";

    echo $xml;

    print "</ajax-response>";
?>

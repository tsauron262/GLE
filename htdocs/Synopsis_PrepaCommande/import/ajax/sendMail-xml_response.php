<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : sendMail-xml_response.php
  * GLE-1.2
  */
    require_once('../../../main.inc.php');

  $id = $_REQUEST['id'];

if ($conf->global->BIMP_MAIL_TO ."x" == 'x' || $conf->global->BIMP_MAIL_FROM."x" == "x")
{
    $xml = "<KO>KO</KO>";
} else {
    $requete = "SELECT mailContent FROM BIMP_import_history WHERE id =  ".$id;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);

    $ret = sendMail('[Historique] Rapport d\'import',$conf->global->BIMP_MAIL_TO,$conf->global->BIMP_MAIL_FROM,$res->mailContent,
             array(),array(),array(),
             ($conf->global->BIMP_MAIL_CC."x" == "x"?"":$conf->global->BIMP_MAIL_CC),
             ($conf->global->BIMP_MAIL_BCC."x" == "x"?"":$conf->global->BIMP_MAIL_BCC),
             0,1,
             ($conf->global->BIMP_MAIL_CC."x" == "x"?"":$conf->global->BIMP_MAIL_FROM));
    $xml = "<OK>OK</OK>";
    if ($ret < 0) $xml = "<KO>KO</KO>";

}



    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print "<ajax-response>";
    print $xml;
    print "</ajax-response>";



    function sendMail($subject,$to,$from,$msg,$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),$addr_cc='',$addr_bcc='',$deliveryreceipt=0,$msgishtml=1,$errors_to='')
    {
        global $mysoc;

          require_once(DOL_DOCUMENT_ROOT.'/core/lib/CMailFile.class.php');

          global $langs, $user;
          $mail = new CMailFile($subject,$to,$from,$msg,
                                $filename_list,$mimetype_list,$mimefilename_list,
                                $addr_cc,$addr_bcc,$deliveryreceipt,$msgishtml,$errors_to);

          $res = $mail->sendfile();
          if ($res)
          {
              return (1);
          } else {
              return -1;
          }

    }

?>

<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 5 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : devalFinance-xml_response.php
  *
  * GLE-1.2
  *
  */

  require_once('../../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Tools/class/CMailFile.class.php');
  $id = $_REQUEST['comId'];
  $xmlStr = "<ajax-response>";
  $requete = "UPDATE ".MAIN_DB_PREFIX."commande SET finance_statut=0 WHERE rowid = ".$id;
  $sql = $db->query($requete);


  require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
  $commande = new Synopsis_Commande($db);
  $commande->fetch($id);
  $arrGrpTmp = $commande->listGroupMember();
  foreach($arrGrpTmp as $key=>$val)
  {
      $requete = "UPDATE ".MAIN_DB_PREFIX."commande SET finance_statut=0 WHERE rowid = ".$val->id;
      $sql = $db->query($requete);
  }

  if ($sql){
      $xmlStr .= "<OK>OK</OK>";
        require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
        $commande = new Synopsis_Commande($db);
        $commande->fetch($id);
        $tmpUser = new User($db);
        $tmpUser->id = $commande->user_author_id;
        $tmpUser->fetch();

        //Notification
        //TO commercial author
        //CC Resp Tech et Resp logistique et financier

        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('PREPACOM_DEVAL_FINANCE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers

        $subject="[Finance Commande] Nouveau message concernant la commande ".$commande->ref;
        $to = $tmpUser->email;

        $msg = "Bonjour ".$tmpUser->fullname.",<br/><br/>";
        $msg .= "La commande ".$commande->getNomUrl(1,6)." a &eacute;t&eacute; invalid&eacute;e financi&egrave;rement.";

        $msg .= "<br/><br/>Cordialement,<br/>\nGLE\n";
        $from = $conf->global->BIMP_MAIL_FROM;
        $addr_cc = $conf->global->BIMP_MAIL_GESTFINANCIER.", ".$conf->global->BIMP_MAIL_GESTPROD;


    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Tools/class/CMailFile.class.php');
    sendMail($subject,$to,$from,$msg,array(),array(),array(),$addr_cc,'',0,1,$from);

  } else {
      $xmlStr .= "<KO>KO</KO>";
  }
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print $xmlStr;
    print "</ajax-response>";


function sendMail($subject,$to,$from,$msg,$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),$addr_cc='',$addr_bcc='',$deliveryreceipt=0,$msgishtml=1,$errors_to='')
{
    global $mysoc;
    global $langs;
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
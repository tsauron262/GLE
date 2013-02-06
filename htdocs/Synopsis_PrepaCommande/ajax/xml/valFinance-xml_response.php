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
  * Name : valFinance-xml_response.php
  *
  * GLE-1.2
  *
  */

  require_once('../../../main.inc.php');
  $id = $_REQUEST['comId'];
  $xmlStr = "<ajax-response>";
  $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_commande SET finance_statut=1 WHERE rowid = ".$id;
  $sql = $db->query($requete);


  require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
  $commande = new Synopsis_Commande($db);
  $commande->fetch($id);
  $arrGrpTmp = $commande->listGroupMember();
  foreach($arrGrpTmp as $key=>$val)
  {
      $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_commande SET finance_statut=1 WHERE rowid = ".$val->id;
      $sql = $db->query($requete);
  }
  if ($sql){
      $xmlStr .= "<OK>OK</OK>";
        require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
        $commande = new Synopsis_Commande($db);
        $commande->fetch($id);
        $tmpUser = new User($db);
        $tmpUser->fetch($commande->user_author_id);

        //Notification
        //TO commercial author
        //CC Resp Tech et Resp logistique et financier
        $subject="[OK Finance] pour le client ".$commande->societe->nom." commande ".$commande->ref." du ".date('d/m/Y',$commande->date);

        $statusFin = "-";
        if ($commande->finance_ok == 1){
            $statusFin = 'OK';
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($db);
            $result=$interface->run_triggers('PREPACOM_OK_FINANCE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $errors=$interface->errors; }
            // Fin appel triggers
        }else if($commande->finance_ok == 0){
            $statusFin = 'Non';
            $subject="[Non Finance] pour le client ".$commande->societe->nom." commande ".$commande->ref." du ".date('d/m/Y',$commande->date);
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($db);
            $result=$interface->run_triggers('PREPACOM_KO_FINANCE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $errors=$interface->errors; }
            // Fin appel triggers
        }else if($commande->finance_ok == 2){
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($db);
            $result=$interface->run_triggers('PREPACOM_PARTIAL_FINANCE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $errors=$interface->errors; }
            // Fin appel triggers
            $statusFin = 'Partiel';
            $subject="[Non Finance] pour le client ".$commande->societe->nom." commande ".$commande->ref." du ".date('d/m/Y',$commande->date);
        }
        if ($commande->finance_statut == 1)
        {
            $statusFin .= "&nbsp;&nbsp;&nbsp;<b>D&eacute;finitif</b>";
        } else {
            $statusFin .= "&nbsp;&nbsp;&nbsp;<b>Temporaire</b>";
        }


        $to = $tmpUser->email;

        $msg = "Bonjour ".$tmpUser->fullname.",<br/><br/>";
        if($commande->finance_ok=1)
            $msg .= "La commande de ".$commande->societe->nom." ref: ".$commande->getNomUrl(1,6)." du ".date('d/m/Y',$commande->date)." a &eacute;t&eacute; valid&eacute;e financi&egrave;rement.";
        else
            $msg .= "La commande de ".$commande->societe->nom." ref: ".$commande->getNomUrl(1,6)." du ".date('d/m/Y',$commande->date)." a &eacute;t&eacute; valid&eacute;e financi&egrave;rement.";
        $msg .= " Ce statut est ".$statusFin;

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
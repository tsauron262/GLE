<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 6 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : BIMPStatus-xml_response.php
  * GLE-1.2
  */
$id=$_REQUEST['id'];
$statut = $_REQUEST['statut'];
require_once('../../../main.inc.php');
//$requete = "UPDATE BIMP_commande_status SET statut_refid = ".$statut . " WHERE commande_refid = ".$id;
//$sql = $db->query($requete);
$sql = setElementElement("commande", "statutS", $id, $statut);
$retmsg="KO";
if ($sql)
{
    $retmsg = "OK";
    require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
    $commande = new Synopsis_Commande($db);
    $commande->fetch($id);
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_c_commande_status WHERE id = ".$statut;
    $sql = $db->query($requete);
    $res =$db->fetch_object($sql);
    $tmpUser = new User($db);
    $tmpUser->id = $commande->user_author_id;
    $tmpUser->fetch();

    // Appel des triggers
    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
    $interface=new Interfaces($this->db);
    $result=$interface->run_triggers('PREPACOM_UPDATE_STATUT',$this,$user,$langs,$conf);
    if ($result < 0) { $error++; $this->errors=$interface->errors; }
    // Fin appel triggers


    //Notification
    //TO commercial author
    //CC Resp Tech et Resp logistique et financier
    $subject=utf8_encodeRien("[Statut Commande] La commande ".$commande->ref." est maintenant au statut \"".$res->label."\"");
    $to = $tmpUser->email;

    $msg = "Bonjour ".$tmpUser->fullname.",<br/><br/>";
    $msg .= "La commande ".$commande->getNomUrl(1,6)." a &eacute;t&eacute; modifi&eacute;e. Elle est maintenant au statut : ".$res->label;
    $msg .= "<br/><br/>Cordialement,<br/>GLE";
    $from = $conf->global->BIMP_MAIL_FROM;
    $addr_cc = $conf->global->BIMP_MAIL_GESTPROD;

    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Tools/class/CMailFile.class.php');
    sendMail($subject,$to,$from,utf8_encodeRien($msg),array(),array(),array(),$addr_cc,'',0,$msgishtml=1,$from);

}
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
       header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print "<ajax-response>";
    print "<res>".$retmsg."</res>";
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
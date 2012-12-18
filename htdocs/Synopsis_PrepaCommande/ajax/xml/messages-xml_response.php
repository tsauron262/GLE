<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : messages-xml_response.php
  * GLE-1.2
  */
  require_once('../../../main.inc.php');
  $id = $_REQUEST['id'];
  $xmlStr = "<ajax-response>";


  if ($id > 0)
  {
//                        var data = "message="+message+"&userid="+userid+"&id="+comId+"&typeMsg="+typeMsg;
    $message = addslashes($_REQUEST['message']);
    $userId = $_REQUEST['userid'];
    $typeMsg = ($_REQUEST['typeMsg']=='general'?false:$_REQUEST['typeMsg']);

    $requete = "INSERT INTO llx_Synopsis_PrepaCom_messages
                            (message,user_author,commande_refid,type)
                     VALUES ('".$message."',".$userId.",".$id.",".($typeMsg?"'".$typeMsg."'":"NULL").")";
    $sql = $db->query($requete);
    if ($sql)
    {
        $xmlStr .= "<OK>OK</OK>";
        $addr_cc = $conf->global->BIMP_MAIL_GESTLOGISTIQUE.", ".$conf->global->BIMP_MAIL_GESTFINANCIER.", ".$conf->global->BIMP_MAIL_GESTPROD;
        require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
        $commande = new Synopsis_Commande($db);
        $commande->fetch($id);
        $tmpUser = new User($db);
        $tmpUser->fetch($commande->user_author_id);

        $tmpUser1 = new User($db);
        $tmpUser1->fetch($userId);

        $addr_cc .= ", ".$tmpUser1->email;

        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $result=$interface->run_triggers('PREPACOM_INTERNAL_MESSAGE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $errors=$interface->errors; }
        // Fin appel triggers

        //Notification
        //TO commercial author
        //CC Resp Tech et Resp logistique et financier
        $subject="[Nouveau message GLE] Nouveau message concernant la commande ".$commande->ref;
        $to = $tmpUser->email;
        $msg = "Bonjour ".$tmpUser->fullname.", <br/><br/>";
        if ($typeMsg)
        {
            switch($typeMsg)
            {
                case 'logistique':{
                    $addr_cc =$tmpUser->email.", ".$conf->global->BIMP_MAIL_GESTPROD;
                    $to = $conf->global->BIMP_MAIL_GESTLOGISTIQUE;
                    $msg = "Bonjour, <br/><br/>";
                }
                break;
                case 'finance':{
                    $addr_cc =$tmpUser->email.", ".$conf->global->BIMP_MAIL_GESTPROD;
                    $to = $conf->global->BIMP_MAIL_GESTFINANCIER;
                    $msg = "Bonjour, <br/><br/>";
                }
                break;
                case 'intervention':{
                    $addr_cc = $conf->global->BIMP_MAIL_GESTPROD;
                    $msg = "Bonjour ".$tmpUser->fullname.", <br/><br/>";
                }
                break;
            }
        }

        $msg .= "Un nouveau message a &eacute;t&eacute; post&eacute; concernant la commande ".$commande->getNomUrl(1,6).".";
        $msg .= "<br/><div><table border=1 width=100%><tr><td>De</td><td>
".$tmpUser->getNomUrl(1,"",true)."\n</td></tr><tr><td colspan=2>".nl2br($message)."</td></tr></table>\n</div>";

        $msg .= "<br/><br/>Cordialement,<br/>\nGLE\n";
        $from = $conf->global->BIMP_MAIL_FROM;
        //$addr_cc = $conf->global->BIMP_MAIL_GESTLOGISTIQUE.", ".$conf->global->BIMP_MAIL_GESTFINANCIER.", ".$conf->global->BIMP_MAIL_GESTPROD;


    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Tools/class/CMailFile.class.php');
    sendMail(utf8_encodeRien($subject),$to,$from,utf8_encodeRien($msg),array(),array(),array(),$addr_cc,'',0,$msgishtml=1,$from);


    } else {
        $xmlStr .= "<KO>KO</KO>";
    }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print $xmlStr;
    print "</ajax-response>";

  } else {
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print "<ajax-response>";
    print "<error>Aucun Element</error>";
    print "</ajax-response>";

  }
function convDur($duration)
{

    // Initialisation
    $duration = abs($duration);
    $converted_duration = array();

    // Conversion en semaines
    $converted_duration['weeks']['abs'] = floor($duration / (60*60*24*7));
    $modulus = $duration % (60*60*24*7);

    // Conversion en jours
    $converted_duration['days']['abs'] = floor($duration / (60*60*24));
    $converted_duration['days']['rel'] = floor($modulus / (60*60*24));
    $modulus = $modulus % (60*60*24);

    // Conversion en heures
    $converted_duration['hours']['abs'] = floor($duration / (60*60));
    $converted_duration['hours']['rel'] = floor($modulus / (60*60));
    $modulus = $modulus % (60*60);

    // Conversion en minutes
    $converted_duration['minutes']['abs'] = floor($duration / 60);
    $converted_duration['minutes']['rel'] = floor($modulus / 60);
    if ($converted_duration['minutes']['rel'] <10){$converted_duration['minutes']['rel'] ="0".$converted_duration['minutes']['rel']; } ;
    $modulus = $modulus % 60;

    // Conversion en secondes
    $converted_duration['seconds']['abs'] = $duration;
    $converted_duration['seconds']['rel'] = $modulus;

    // Affichage
    return( $converted_duration);
}

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

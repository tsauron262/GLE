<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  *//*
 *
 * $Id: interface_modNotification_Notification.class.php,v 1.1 2007/12/27 21:18:25 eldy Exp $
 */

/**
        \file       htdocs/includes/triggers/interface_modNotification_notification.class.php
        \ingroup    notification
        \brief      Fichier de gestion des notifications sur evenement Dolibarr
*/


/**
        \class      InterfaceNotification
        \brief      Classe des fonctions triggers des actions personalisees du workflow
*/
$toto;

class InterfaceNotification
{
    public $db;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceNotification($DB)
    {
        $this->db = $DB ;

        $this->name = "Babel-Notification";
        $this->family = "notification";
        $this->description = "Les triggers de ce composant envoie les notifications par mail/notifier selon configuration du module Babel - Notification.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
    }

    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return GLE_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code a executer en reaction de l'action
        // Les donnees de l'action sont stockees dans $object
        global $toto;

        //1 on cherche si y'a une notification Model attaché a cet evenement
        $requete = "SELECT m.id,
                           m.mail_model_refid,
                           m.notifier_model_refid
                      FROM Babel_notification_model as m,
                           Babel_trigger as t
                     WHERE t.id = m.trigger_refid AND t.code = '".$action."'";
        $sql = $this->db->query($requete);
        $toto = $object;
        while($res = $this->db->fetch_object($sql))
        {
            //2 genere les messages
            $msg= false;
            $msgHtml = false;
            $mail = false;
            $mailHtml = false;

            if ($res->mail_model_refid > 0)
            {
                $mailHtmlMsg="";
                $mailSubject="";
                $mailTxtMsg="";
                $requete1 = "SELECT *
                               FROM Babel_notification_model_mail
                              WHERE id = ".$res->mail_model_refid;
                $sql1 = $this->db->query($requete1);
                while ($res1 = $this->db->fetch_object($sql1))
                {
                    $rawHtmlMsg = $res1->htmlContent;
                    $rawTxtMsg = $res1->textContent;
                    $subject = $res1->subject;
                    $mailSubject = preg_replace_callback('/\[\[([\w\W]*)\]\]/','apply_var',$subject);
                    $mailHtmlMsg = preg_replace_callback('/\[\[([\w\W]*)\]\]/','apply_var',$rawHtmlMsg);
                    $mailTxtMsg = preg_replace_callback('/\[\[([\w\W]*)\]\]/','apply_var',$rawTxtMsg);
                }

                $notifSubject = "";
                $notifHtmlMsg = "";
                $notifTxtMsg = "";
                $requete1 = "SELECT *
                               FROM Babel_notification_model_notifier
                              WHERE id = ".$res->notifier_model_refid;
                $sql1 = $this->db->query($requete1);
                while ($res1 = $this->db->fetch_object($sql1))
                {
                    $rawHtmlMsg = $res1->htmlContent;
                    $rawTxtMsg = $res1->textContent;
                    $subject = $res1->subject;
                    $notifSubject = preg_replace_callback('/\[\[([\w\W]*)\]\]/','apply_var',$subject);
                    $notifHtmlMsg = preg_replace_callback('/\[\[([\w\W]*)\]\]/','apply_var',$rawHtmlMsg);
                    $notifTxtMsg = preg_replace_callback('/\[\[([\w\W]*)\]\]/','apply_var',$rawTxtMsg);
                }

                //3 insert dans notify
                $requete = "INSERT INTO Babel_notification
                                        (model_refid,statut,html_mail_message, text_mail_message, mail_subject, html_notif_message,text_notif_message,notif_subject)
                                VALUES (".$res->id.",0,'".$mailHtmlMsg."','".$mailTxtMsg."','".$mailSubject."','".$notifHtmlMsg."','".$notifTxtMsg."','".$notifSubject."')";
                $sql2 = $this->db->query($requete);
                $newId = $this->db->last_insert_id('Babel_notification');
                //4 destinataire
                $arr = $this->copyDestinatairesModel($res->id,$newId);

            }
        }
        //envoie tous les mails qui n'ont pas été envoyé
        $requete = "SELECT n.mail_subject,
                           n.id,
                           n.html_mail_message,
                           n.text_mail_message
                      FROM Babel_notification as n,
                           Babel_notification_model as m,
                           Babel_notification_model_mail as mm
                     WHERE n.model_refid = m.id
                       AND m.mail_model_refid = mm.id
                       AND (n.mail_sent = 0 OR n.mail_sent IS NULL)";
        $sql = $this->db->query($requete);
        while($res = $this->db->fetch_object($sql))
        {
            print $res->id.'<br/>';
            //send mails
            $subject = $res->mail_subject;
            $body = $res->html_mail_message;
            $arr = $this->getDestinataires($res->id,array('isMail' => 1));
            $to = join(',',$arr['to']);
            $cc = array();
            $cc = $arr['cc'];
            $cci = array();
            $cci = $arr['bcc'];
            $resM = $this->sendMail($subject,$to,"GLE Notifier <gle.synopsis-erp.com>",$body,array(),array(),array(),join(',',$cc),join(',',$cci),0,1);
            if ($resM > 0){
                $requete = "UPDATE Babel_notification SET mail_sent = 1 WHERE id = ".$res->id;
                $this->db->query($requete);
                print $requete;
            } else {
                $requete = "UPDATE Babel_notification SET mail_sent = 0 WHERE id = ".$res->id;
                $this->db->query($requete);
            }
        }
        return 0;
    }
    private function sendMail($subject,$to,$from,$msg,$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),$addr_cc='',$addr_bcc='',$deliveryreceipt=0,$msgishtml=1,$errors_to='')
    {
        global $mysoc;
        global $langs;
        require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');
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

    function getDestinataires($notif_id,$option=array())
    {
        global $user,$langs,$object,$conf;
        $db = $this->db;
        foreach(array('Destinataire' => 'to', 'CC' => 'cc', 'BCC' => 'bcc') as $key=>$val)
        {

            //3 affiche la configuration des envoies
            $requete = "SELECT *
                          FROM Babel_notification_destinataire as d,
                               Babel_li_notificationModel_destinataire as ld,
                               Babel_notification_destinataire_role as r
                         WHERE d.id = ld.destinataire_refid
                           AND ld.role_refid = r.id
                           AND r.code = '".$val."' ";
            if ($option['isMail'] == 1){
                $requete .= " AND ld.isMail = 1";
            }
            if ($option['isNotif'] == 1){
                $requete .= " AND ld.isNotif = 1";
            }
            $sql = $db->query($requete);
            while($res = $db->fetch_object($sql))
            {
                if($res->type == 'group')
                {
                    require_once(DOL_DOCUMENT_ROOT."/usergroup.class.php");
                    $group = new UserGroup($db);
                    if ($res->email_str > 0)
                    {
                        $group->fetch($res->email_str);
                        //user in group
                        //foreach
                        $arr[$val][]=array(email => ($res->user_refid > 0?$res->email:($res->contact_refid>0?$res->people_email:$email_str)));
                    }
                } else if ($res->type=='user')
                {
                    $tmpUser = new User($db);
                    if ($res->email_str > 0)
                    {
                        $tmpUser->id = $res->email_str;
                        $tmpUser->fetch();
                        $arr[$val][]=$tmpUser->email;
                    }
                } else if ($res->type=='contact')
                {
                    require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
                    $socpeople = new Contact($db);
                    if ($res->email_str > 0){
                        $socpeople->fetch($res->email_str);
                        $arr[$val][]=$socpeople->email;
                    }
                } else if ($res->type == 'libre')
                {
//                    var_dump($res->email_str);
//                    exit;
                    $email_str = preg_replace_callback('/\[\[([\w\W]*)\]\]/','apply_var',$res->email_str);
                    $arr[$val][]=$email_str;
                } else if ($res->type == 'conf')
                {
                    $tmp = $res->email_str;
                    $arr[$val][]=$conf->global->$tmp;
                }
            }
        }

        return($arr);

//        $requete= "SELECT d.type,
//                          d.user_refid,
//                          d.contact_refid,
//                          d.email_str,
//                          u.email,
//                          p.email as people_email
//                     FROM Babel_li_notification_destinataire as l,
//                          Babel_notification_destinataire as d
//                LEFT JOIN llx_user as u ON d.user_refid = u.rowid
//                LEFT JOIN llx_socpeople as p ON d.contact_refid = p.rowid
//                    WHERE l.notification_refid = ".$notif_id."
//                      AND l.destinataire_refid = d.id";
//        $sql = $this->db->query($requete);
//        $arr = array();
//        while($res = $this->db->fetch_object($sql))
//        {
//            $email_str = preg_replace_callback('/\[\[([\w\W]*)\]\]/','apply_var',$res->email_str);
//
//            $arr[$res->type][]=array(userid => $res->user_refid,
//                                     contact_id => $res->contact_refid,
//                                     email => ($res->user_refid > 0?$res->email:($res->contact_refid>0?$res->people_email:$email_str)));
//        }
//        return($arr);
    }
    function copyDestinatairesModel($notif_model_id,$notif_id)
    {
        global $user,$langs,$object,$conf;
        $requete= "SELECT d.id, role_refid, isMail, isNotif
                     FROM Babel_li_notificationModel_destinataire as l,
                          Babel_notification_destinataire as d
                    WHERE l.notification_model_refid = ".$notif_model_id."
                      AND l.destinataire_refid = d.id";
        $sql = $this->db->query($requete);
        $arr = array();
        while($res = $this->db->fetch_object($sql))
        {
            $requete1 = "INSERT INTO Babel_li_notification_destinataire
                                     (notification_refid,destinataire_refid,role_refid, isMail, isNotif)
                            VALUES   (".$notif_id.",".$res->id.",".$res->role_refid.",".$res->isMail.",".$res->isNotif.")";
            $sql1 = $this->db->query($requete1);
        }
    }
}
function apply_var($matches)
{
    global $user,$conf, $langs,$toto;
    $object = $toto;
    $tmp = $matches[1];
    try {
        eval("\$matche=$tmp;");

    } catch (Exception $e) {
        var_dump($e);
    }

    return $matche;
}
?>
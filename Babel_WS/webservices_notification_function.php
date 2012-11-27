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
  * Name : webservices_function.php
  * GLE-1.1
  */
require_once('../main.inc.php');

function getNotifications($userId){
    global $db;
    //Group

    $requete = "SELECT statut,
                       text_notif_message,
                       html_notif_message,
                       notif_subject,
                       n.id
                  FROM Babel_notification as n,
                       Babel_li_notification_destinataire as ln,
                       Babel_notification_destinataire as d
                 WHERE n.id = ln.notification_refid
                   AND ln.destinataire_refid = d.id
                   AND ln.isNotif = 1
                   AND ((d.type='user' AND d.email_str = ".$userId.") OR (d.type='group' AND d.email_str IN (SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_user WHERE fk_user = '".$userId."'))) ";
    $sql = $db->query($requete);
//$xml.= $requete;
    $xml = "<notifs>\n";
    while($res = $db->fetch_object($sql))
    {
        $xml .= "\t<notif>\n";
        $xml .= "\t\t<id>".$res->id."</id>\n";
        $xml .= "\t\t<statut>".$res->statut."</statut>\n";
        $xml .= "\t\t<textContent>".$res->text_notif_message."</textContent>\n";
        $xml .= "\t\t<htmlContent><![CDATA[".preg_replace('/\[\[/','[',$res->html_notif_message)."]]></htmlContent>\n";
        $xml .= "\t\t<subject><![CDATA[".preg_replace('/\[\[/','[',$res->notif_subject)."]]></subject>\n";
        $xml .= "\t</notif>\n";
    }
    $xml .= "</notifs>\n";
    return $xml;
}

?>

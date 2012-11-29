<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 18 mars 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Babel_Notifications.php
  * GLE-1.2
  */


  require_once('pre.inc.php');

  $js = <<<EOF
    <script>
        jQuery(document).ready(function(){
            jQuery('#tabs').tabs({
                spinner:'',
                cache: false,
                fx:{ height: 'toggle', opacity: 'toggle'}
            })
        });
    </script>

EOF;
  llxHeader($js,"Notification - configuration");
  //1 configure les modeles de mails
  //2 configure les modeles de notifier
  //3 configure les évent + destinataire % trigger
  print "<div id='tabs'>";
    print "<ul>";
    print " <li><a href='#ev'><span>Event</span></a></li>";
    print " <li><a href='#mail'><span>Mails</span></a></li>";
    print " <li><a href='#notif'><span>Notifier</span></a></li>";
    print "</ul>";
    print "<div id='ev'>";

    //List des notif en cours + suppression / modification
    $requete = "SELECT m.label,
                       m.id as id,
                       m.description,
                       mm.label as MailLabel,
                       mn.label as NotifLabel,
                       t.code as event
                  FROM Babel_notification_model as m
             LEFT JOIN Babel_notification_model_mail as mm ON mm.id = m.mail_model_refid
             LEFT JOIN Babel_notification_model_notifier as mn ON mn.id = m.notifier_model_refid
             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_trigger as t ON m.trigger_refid = t.id
              ORDER BY m.label";
    $sql  = $db->query($requete);
    print "<table cellpadding=15 width=100%>";
    print "<tr><th class='ui-state-default ui-widget-header'>Label";
    print "    <th class='ui-state-default ui-widget-header'>Description";
    print "    <th class='ui-state-default ui-widget-header'>Event";
    print "    <th class='ui-state-default ui-widget-header'>Model Mail";
    print "    <th class='ui-state-default ui-widget-header'>Model Notifier";
    print "    <th class='ui-state-default ui-widget-header'>Actions";
    while($res = $db->fetch_object($sql))
    {
        print "<tr><td class='ui-widget-content'>".$res->label;
        print "<td class='ui-widget-content'>".$res->description;
        print "<td class='ui-widget-content'>".($res->event."x"!="x"?$res->event:'');
        print "<td class='ui-widget-content'>".($res->MailLabel.'x'!='x'?$res->MailLabel:"");
        print "<td class='ui-widget-content'>".($res->NotifLabel.'x'!='x'?$res->NotifLabel:"");
        print "<td class='ui-widget-content'><button class='butAction' onClick='location.href=\"Babel_Notifications_notif.php?id=".$res->id."\"'><span style='float:left;' class='ui-icon ui-icon-trash'></span>Configurer</button><button class='butActionDelete'><span style='float:left;' class='ui-icon ui-icon-trash'></span>Supprimer</button>";
    }
    print "</table><br/>";
    //Ajout
    print "<table cellpadding=15 width=100%>";
    print "<tr><th class='ui-state-default ui-widget-header'>Label";
    print "    <th class='ui-state-default ui-widget-header'>Description";
    print "    <th class='ui-state-default ui-widget-header'>Event";
    print "    <th class='ui-state-default ui-widget-header'>Model Mail";
    print "    <th class='ui-state-default ui-widget-header'>Model Notifier";
    print "    <th class='ui-state-default ui-widget-header'>Action";
    print "<tr><td class='ui-widget-content'><input name='labelNotif'>";
    print "    <td class='ui-widget-content'><input name='descriptionNotif'>";
    print "    <td class='ui-widget-content'><SELECT name='eventNotif' >";
    $requete= "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_trigger ORDER BY code";
    $sql = $db->query($requete);
    while($res = $db->fetch_object($sql))
    {
        print "<OPTION value='".$res->id."'>".$res->code."</OPTION>";
    }
    print "        </SELECT>";
    print "    <td class='ui-widget-content'><SELECT name='MailNotif' >";
    $requete= "SELECT * FROM Babel_notification_model_mail ORDER BY label";
    $sql = $db->query($requete);
    while($res = $db->fetch_object($sql))
    {
        print "<OPTION value='".$res->id."'>".$res->label."</OPTION>";
    }
    print "        </SELECT>";
    print "    <td class='ui-widget-content'><SELECT name='NotifNotif' >";
    $requete= "SELECT * FROM Babel_notification_model_notifier ORDER BY label";
    $sql = $db->query($requete);
    while($res = $db->fetch_object($sql))
    {
        print "<OPTION value='".$res->id."'>".$res->label."</OPTION>";
    }
    print "        </SELECT>";

    print "    <td class='ui-widget-content'><button class='butAction'><span style='float:left;' class='ui-icon ui-icon-plus'></span>Ajouter</button>";


    print "</div>";
    print "<div id='mail'>";
    print "</div>";
    print "<div id='notif'>";
    print "</div>";
  print "</div>";
?>

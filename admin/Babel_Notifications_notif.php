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
  * Name : Babel_Notifications_notif.php
  * GLE-1.2
  */


    require_once('pre.inc.php');

    $id = $_REQUEST['id'];
    $js = <<<EOF
    <script>
        jQuery(document).ready(function(){
            jQuery('#tabs').tabs({
                spinner:'',
                cache: false,
                fx:{ height: 'toggle', opacity: 'toggle'}
            });
        });
    </script>

EOF;
    llxHeader($js,"Notifications - Configuration");
  //1 retour config
    print "<div id='tabs'>";
    print "<ul>";
    print " <li><a href='#notif'><span>Notifications</span></a></li>";
    print "</ul>";
    print "<div id='notif'>";
    //2 affiche la notification
    print "<table width=100% cellpadding=15>";
    $requete = "SELECT * FROM Babel_notification_model WHERE id=".$id;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    print "<tr><th class='ui-widget-header ui-state-default'>Label<td class='ui-widget-content'><input name='label' value='".$res->label."'></input>";
    print "<tr><th class='ui-widget-header ui-state-default'>Description<td class='ui-widget-content'><textarea name='description'>".$res->description."</textarea>";
    print "<tr><th class='ui-widget-header ui-state-default'>Action<td class='ui-widget-content'><SELECT name='eventNotif' >";
    $requete= "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_trigger ORDER BY code";
    $sql = $db->query($requete);
    while($res1 = $db->fetch_object($sql))
    {
        if ($res1->id == $res->trigger_refid)
            print "<OPTION SELECTED value='".$res1->id."'>".$res1->code."</OPTION>";
        else
            print "<OPTION value='".$res1->id."'>".$res1->code."</OPTION>";
    }
    print "        </SELECT>";
    print "<tr><th class='ui-widget-header ui-state-default'>Mod&egrave;le de mail";
    print "    <td class='ui-widget-content'><SELECT name='MailNotif' >";
    $requete= "SELECT * FROM Babel_notification_model_mail ORDER BY label";
    $sql = $db->query($requete);
    while($res1 = $db->fetch_object($sql))
    {
        if ($res1->id == $res->mail_model_refid)
            print "<OPTION value='".$res1->id."'>".$res1->label."</OPTION>";
        else
            print "<OPTION value='".$res1->id."'>".$res1->label."</OPTION>";
    }
    print "        </SELECT>";
    print "<tr><th class='ui-widget-header ui-state-default'>Mod&egrave;le du notifier";
    print "    <td class='ui-widget-content'><SELECT name='NotifNotif' >";
    $requete= "SELECT * FROM Babel_notification_model_notifier ORDER BY label";
    $sql = $db->query($requete);
    while($res1 = $db->fetch_object($sql))
    {

        if ($res1->id == $res->notifier_model_refid)
            print "<OPTION SELECTED value='".$res1->id."'>".$res1->label."</OPTION>";
        else
            print "<OPTION value='".$res1->id."'>".$res1->label."</OPTION>";
    }
    print "        </SELECT>";

    print "<tr><th class='ui-widget-header ui-state-default' colspan=2>Destinataires";
    print "<tr><td colspan=2 style='padding:0px;' class='ui-widget-content'>";
    print "<table width=100%><tr>";
    foreach(array('mail','notif') as $val1)
    {
        print "<td><table width=100% cellpadding=10>";
        foreach(array('Destinataire' => 'to', 'CC' => 'cc', 'BCC' => 'bcc') as $key=>$val)
        {

            //3 affiche la configuration des envoies
            print "<tr><th class='ui-widget-header ui-state-default' width=150>".$key." ".$val1;
            print "    <td class='ui-widget-content'>";
            $requete = "SELECT *
                          FROM Babel_notification_destinataire as d,
                               Babel_li_notificationModel_destinataire as ld,
                               Babel_notification_destinataire_role as r
                         WHERE d.id = ld.destinataire_refid
                           AND ld.role_refid = r.id
                           AND r.code = '".$val."' ";
            if ($val1 == 'mail') $requete .= " AND ld.isMail = 1";
            if ($val1 == 'notif') $requete .= " AND ld.isNotif = 1";
            $sql = $db->query($requete);
            print '<table width=100%>';
            while($res = $db->fetch_object($sql))
            {
                if($res->type == 'group')
                {
                    require_once(DOL_DOCUMENT_ROOT."/usergroup.class.php");
                    $group = new UserGroup($db);
                    if ($res->email_str > 0)
                    {
                        $group->fetch($res->email_str);
                        print '<tr><td>'.$group->getNomUrl(1);
                        print '<td width=16><table><tr><td width=16><span class="ui-icon ui-icon-trash"></span></table>';
                    } else {
                        print '<tr><td>';
                        print "Erreur !";
                        print '<td width=16><table><tr><td width=16><span class="ui-icon ui-icon-trash"></span></table>';
                    }
                } else if ($res->type=='user')
                {
                    $tmpUser = new User($db);
                    if ($res->email_str > 0)
                    {
                        $tmpUser->id = $res->email_str;
                        $tmpUser->fetch();
                        print '<tr><td>'.$tmpUser->getNomUrl(1);
                        print '<td width=16><table><tr><td><span class="ui-icon ui-icon-trash"></span></table>';
                    } else {
                        print '<tr><td>';
                        print "Erreur !";
                        print '<td width=16><table><tr><td><span class="ui-icon ui-icon-trash"></span></table>';
                    }
                } else if ($res->type=='contact')
                {
                    require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
                    $socpeople = new Contact($db);
                    if ($res->email_str > 0){
                        $socpeople->fetch($res->email_str);
                        print '<tr><td>'.$socpeople->getNomUrl(1);
                        print '<td width=16><table><tr><td><span class="ui-icon ui-icon-trash"></span></table>';
                    } else {
                        print '<tr><td>';
                        print "Erreur !";
                        print '<td width=16><table><tr><td><span class="ui-icon ui-icon-trash"></span></table>';
                    }
                } else if ($res->type == 'libre')
                {
                    print '<tr><td>'.$res->email_str;
                    print '<td width=16><table><tr><td><span class="ui-icon ui-icon-trash"></span></table>';
                } else if ($res->type == 'conf')
                {
                    $tmp = $res->email_str;
                    print '<tr><td>'.$conf->global->$tmp;
                    print '<td width=16><table><tr><td><span class="ui-icon ui-icon-trash"></span></table>';
                }
            }
            print "</table>";
        }
        print "</table>";
    }
    print '</table>';

    print '<tr><th class="ui-widget-header ui-state-default" colspan=1>Ajouter un destinataire<td class="ui-widget-content">';
    $requete = "SELECT * FROM Babel_notification_destinataire_role ORDER by role";
    $sql1 = $db->query($requete);
    $srt = "";
    while ($res1 = $db->fetch_object($sql1))
    {
        $str .= "<OPTION value='".$res1->id."'>".$res1->role."</OPTION>";
    }
    print "<table cellpadding=5><tr><td>Role<td><select name='type'>".$str."</select>";
    print "<td>Type<td><select name='type'><option value='config'>Configuration</option><option value='contact'>Contact</option><option value='group'>Groupe</option><option value='libre'>Libre</option><option value='user'>Utilisateur</option></select>";
    print "<td>Valeur<td><input name='newVal'></input>";
    print "<td>Notif<td><input type='checkbox' checked name='notif'></input>";
    print "<td>Mail<td><input type='checkbox' checked name='mail'></input><td>";
    print '<span class="ui-icon ui-icon-plus"></span></table>';
    print "</table>";

    print "</div>";
  print "</div>";


?>

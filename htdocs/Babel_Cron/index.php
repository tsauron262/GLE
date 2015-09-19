<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 avr. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * GLE-1.1
  */



require_once('./pre.inc.php');
$dir = DOL_DOCUMENT_ROOT."/Babel_Cron/scripts/";

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

if (!($user->rights->Cron->Global->Afficher || $user->rights->Cron->Global->Modifier || $user->admin || $user->local_admin))
{
    accessforbidden();
}

if ($user->rights->Cron->Global->Modifier||$user->admin || $user->local_admin)
{
    if ($_REQUEST['run']."x" != "x")
    {
        require_once($dir.$_REQUEST['run']."Script.class.php");
        $objStr = $_REQUEST['run']."Script";
        $obj = new $objStr($db);
        $obj->do_action();
    }

    if ($_REQUEST['setup']."x" != "x")
    {
        require_once($dir.$_REQUEST['setup']."Script.class.php");
        $objStr = $_REQUEST['setup']."Script";
        $obj = new $objStr($db);
        $obj->init();
    }
    if ($_REQUEST['activate']."x" != "x")
    {
        require_once($dir.$_REQUEST['activate']."Script.class.php");
        $objStr = $_REQUEST['activate']."Script";
        $obj = new $objStr($db);
        $obj->activate();
    }
    if ($_REQUEST['deactivate']."x" != "x")
    {
        require_once($dir.$_REQUEST['deactivate']."Script.class.php");
        $objStr = $_REQUEST['deactivate']."Script";
        $obj = new $objStr($db);
        $obj->deactivate();
    }
    if ($_REQUEST['delete']."x" != "x")
    {
        require_once($dir.$_REQUEST['delete']."Script.class.php");
        $objStr = $_REQUEST['delete']."Script";
        $obj = new $objStr($db);
        $obj->delete();
    }

    if ($_REQUEST['info']."x" != "x")
    {
        require_once($dir.$_REQUEST['info']."Script.class.php");
        $objStr = $_REQUEST['info']."Script";
        $obj = new $objStr($db);
        $obj->when_run();
    }
}

//Liste les taches
//Affiche les resultat du dernier lancement
llxHeader();

    print "<br>";
    load_fiche_titre("Ordonnanceur");
    print "<br>";

print "<table cellpadding=5 width=100%>";
print "<tr><th class='ui-widget-header ui-state-default'>Nom<th class='ui-widget-header ui-state-default'>Description";

if ($user->rights->Cron->Global->Modifier||$user->admin || $user->local_admin)
{
    print "<th colspan=3 class='ui-widget-header ui-state-default'>Action";
}

if ($handle = opendir($dir)) {

    /* Ceci est la façon correcte de traverser un dossier. */
    while (false !== ($file = readdir($handle))) {
        if (preg_match('/([\w]*)Script.class.php$/',$file,$arr))
        {
            $requete = "SELECT * FROM Babel_Cron WHERE object = '".$arr[1]."Script'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $num = $db->num_rows($sql);
//
//            if ('x'.$res->nom == "x")
//            {
//
//            }

            require_once($dir.$file);
            $className=$arr[1].'Script';
            $tmpObj = new $className($db);
            $nom = $arr[1];
            $description = $tmpObj->description();
            $version = $tmpObj->version();

            print "<tr><td width=200 align=center class='ui-widget-content'>".$nom."<td class='ui-widget-content'align=left >".$description;
            if ($num > 0 && ($user->rights->Cron->Global->Modifier || $user->admin || $user->local_admin))
            {
                print "<td width=150 align=center class='ui-widget-content'>
                          <button class='ui-widget-header ui-state-default ui-corner-all' onClick='location.href=\"".$_SERVER['PHP_SELF']."?delete=".$arr[1]."\"'>
                                <span class='ui-icon ui-icon-trash' style='float: left; margin-right: 5px;'></span>
                                <span style='float: left; margin-top:2px;'>Effacer</span>
                          </button>
                       </td>"."";
                if ($res->active == 1)
                {
                    print "<td width=150 align=center class='ui-widget-content'>
                          <button class='ui-widget-header ui-state-default ui-corner-all' onClick='location.href=\"".$_SERVER['PHP_SELF']."?deactivate=".$arr[1]."\"'>
                                <span class='ui-icon ui-icon-stop' style='float: left; margin-right: 5px;'></span>
                                <span style='float: left; margin-top:2px;'>D&eacute;sactiver</span>
                          </button></td>";
                } else {
                    print "<td width=150 align=center class='ui-widget-content'>
                          <button class='ui-widget-header ui-state-default ui-corner-all' onClick='location.href=\"".$_SERVER['PHP_SELF']."?activate=".$arr[1]."\"'>
                                <span class='ui-icon ui-icon-play' style='float: left; margin-right: 5px;'></span>
                                <span style='float: left; margin-top:2px;'>Activer</span>
                          </button></td>";
                }
            } else if ($user->rights->Cron->Global->Modifier || $user->admin || $user->local_admin)
            {
                print "<td width=150 colspan=2 align=center class='ui-widget-content'>
                          <button class='ui-widget-header ui-state-default ui-corner-all' onClick='location.href=\"".$_SERVER['PHP_SELF']."?setup=".$arr[1]."\"'>
                                <span class='ui-icon ui-icon-key' style='float: left;margin-right: 5px;'></span>
                                <span style='float: left; margin-top: 2px;'>Installer</span>
                          </button>
                       </td>"."";
            }
//            print "<a href='".$_SERVER['PHP_SELF']."?run=".$arr[1]."'>run ".$arr[1]."</a>"."<br/>";
//            print "<a href='".$_SERVER['PHP_SELF']."?info=".$arr[1]."'>info ".$arr[1]."</a>"."<br/>";
        }
    }
    closedir($handle);
}
print "</table>";
print "<br/>";
print "<br/>";
$requete = "SELECT nom, description, unix_timestamp(nextRun) as nextRun, unix_timestamp(lastRun) as lastRun,last_result
              FROM Babel_Cron, Babel_Cron_Schedule
             WHERE active = 1 AND Babel_Cron.id = Babel_Cron_Schedule.cron_refid
          ORDER BY Babel_Cron_Schedule.nextRun, Babel_Cron.nom
          LIMIT 100";
$sql = $db->query($requete);
$arrRes[0]='KO ';
$arrRes[1]='OK ';
print "<table width=100% border=1 cellpadding=5>";
print "<tr><th class='ui-widget-header ui-state-default'>Nom
           <th class='ui-widget-header ui-state-default'>Description
           <th class='ui-widget-header ui-state-default'>Prochaine ex&eacute;cution
           <th class='ui-widget-header ui-state-default'>R&eacute;sultat pr&eacute;c&eacute;dent";
while ($res = $db->fetch_object($sql))
{
    print "<tr><td align=center class='ui-widget-content'>".$res->nom.
              "<td align=center class='ui-widget-content'>".$res->description.
              "<td align=center class='ui-widget-content'>".date('d/m/Y H:i',$res->nextRun);
    if ($res->lastRun > 0)
    {
        print "<td align=center class='ui-widget-content'>".date('d/m/Y H:i',$res->lastRun) ." ".$arrRes[$res->last_result];
    } else {
        print "<td align=center class='ui-widget-content'> - ";
    }
}
print "</table>";


?>

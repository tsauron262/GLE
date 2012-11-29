<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 18 aout 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : minifiche.php
  * GLE-1.2
  */

  $cookieName = "GleWSCookie";
    if ($_REQUEST['authByPass']."x" != "x")
    {
        require_once('../master.inc.php');
        $login = $_REQUEST['authByPass'];
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE login ='".$login."'";
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        if ($res->pass."x" != "x")
        {
            $requete = "DELETE FROM `Babel_WS_auth` WHERE user_id = ".$res->rowid;
            $sql=$db->query($requete);

            $requete = "INSERT INTO `Babel_WS_auth`
                                    (`tms`,`cookie_value`,`user_id`)
                             VALUES (now(), '".md5($res->pass)."', ".$res->rowid.")";
            $sql=$db->query($requete);
        }
    }

    if($_COOKIE[$cookieName] ."x" != "x")
    {
        require_once('../master.inc.php');
        $requete = "SELECT * FROM Babel_WS_auth WHERE cookie_value = '".$_COOKIE[$cookieName]."'";
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".$res->user_id;
        $sql1 = $db->query($requete1);
        $res1 = $db->fetch_object($sql1);
        $_POST["username"]=$res1->login;
        $_POST["password"]=$res1->pass;
    }
        require_once('../master.inc.php');

//  require_once('../main.inc.php');
  $idsoc=$_REQUEST['socid'];
  if (strlen($idsoc) > 0)
  {
    $idsoc=urldecode($idsoc);
    $idsoc=utf8_decode($idsoc);
    $idsoc = addslashes($idsoc);
    $soc = new Societe($db);
    $soc->fetch_by_name($idsoc);

    print "<table width='450'>";
    print "<tr><th width=20%>Client<td>".utf8_encode($soc->getNomUrl(1));
    print "<tr><th valign=top rowspan=2 width=20%>Adresse<td>".utf8_encode($soc->adresse);
    print "<tr><td>".utf8_encode($soc->cp);
    print " ".utf8_encode($soc->ville)."<br>";
    $rowspan = 1;
    if ("x".$soc->email != "x")
    {
        $rowspan++;
    }
    if ("x".$soc->fax != "x")
    {
        $rowspan++;
    }
    print "<tr><th valign=top rowspan=3 width=20%>Communication<td>Tel: ".utf8_encode($soc->tel);
    if ("x".$soc->fax != "x")
    {
        print "<tr><td>Fax: ".utf8_encode($soc->fax);
    }
    if ("x".$soc->email != "x")
    {
        print "<tr><td>Email: ".utf8_encode($soc->email);
    }

    print "</table>";
    print "<br/>";
  } else {
    print "Pas d'information client";
  }
?>

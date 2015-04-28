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
$curDir =  __FILE__;
$curDir = preg_replace('/Babel_Cron\/cron.php$/','',$curDir);

require_once($curDir.'/master.inc.php');

$dir = $curDir."/Babel_Cron/scripts/";

if ($handle = opendir($dir)) {

    /* Ceci est la façon correcte de traverser un dossier. */
    while (false !== ($file = readdir($handle))) {
        if (preg_match('/([\w]*)Script.class.php$/',$file,$arr))
        {
            $requete = "SELECT * FROM Babel_Cron WHERE object = '".$arr[1]."Script'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $num = $db->num_rows($sql);
            if ($res->active == 1)
            {
                require_once($dir.$arr[1]."Script.class.php");
                $objStr = $arr[1]."Script";
                $obj = new $objStr($db);
                $obj->do_action();
            }
        }
    }
}

?>
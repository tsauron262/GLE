<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../../main.inc.php');
require_once('../Campagne.class.php');


 $campagne_id = $_REQUEST['campagneId'];
 $idList = $_REQUEST['idList'];
 $arrList = preg_split('/__/',$idList);
 array_pop($arrList);
 $campSoc = new CampagneSoc($db);
 $allOk = true;
 foreach ($arrList as $key)
 {
    $ret = $campSoc->create($key, $campagne_id);
    if ($ret != 1)
    {
        print '<ko>'.$db->error()."</ko>";
        $allOk=false;
        break;
    } else {
        $allOk = true;
    }

 }

header("Content-Type: text/xml");
print '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
if ($allOk)
{
    print "<ok/>";
}


?>

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


 $campagne_id = $_REQUEST['campagneId'];

$requete = "DELETE FROM Babel_campagne_societe WHERE campagne_refid = ".$campagne_id;


header("Content-Type: text/xml");
print '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';



if ($db->query($requete))
{
    print "<ok/>";
} else {
    print '<ko>'.$db->error()."</ko>";
}


?>

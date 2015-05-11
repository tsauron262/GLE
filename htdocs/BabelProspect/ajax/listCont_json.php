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

 $socid = $_REQUEST['socid'];

         $requete = "SELECT rowid,
                            civility,
                            name,
                            firstname
                       FROM ".MAIN_DB_PREFIX."socpeople
                      WHERE fk_soc = ".$socid."
                   ORDER BY name ,firstname";

        $result = $db->query( $requete ) or die("Couldn t execute query.".mysql_error());
        $i=0;
        while($row = $db->fetch_array($result,MYSQL_ASSOC))
        {
            $name = htmlentities(utf8_decode($row["civility"]. " ".utf8_encode($row["name"]. " ".$row["firstname"])));
            $responce->rows[$i]['id']=$row["rowid"];
            $responce->rows[$i]['cell']=$name;
            $i++;
        }
        echo json_encode($responce);
?>
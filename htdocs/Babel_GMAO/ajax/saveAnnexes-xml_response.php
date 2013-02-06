<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 7 mars 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : saveAnnexes-xml_response.php
  * GLE-1.2
  */

    require_once('../../main.inc.php');


    $xml = "";

    $modele = $_REQUEST['modele'];
    $id = $_REQUEST['id'];
    $db->begin();
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_contrat_annexe WHERE contrat_refid = ".$id;
    $sql1 = $db->query($requete);
    $rang = 0;
    $ok = false;
    if (count($modele) == 0) $ok = true;
    foreach($modele as $key => $val)
    {
        $rang ++;
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_contrat_annexe
                                (annexe_refid, contrat_refid, rang )
                         VALUES (".$val.",".$id.",".$rang.")";
        $sql = $db->query($requete);
        if ($sql)
        {
            $ok = true;
        } else {
            $ok = false;
            break;
        }
    }
    if ($sql1 && $ok){
        $xml = "<OK>OK</OK>";
        $db->commit();
    } else {
        $xml = "<KO>KO</KO>";
        $db->rollback;
    }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
         header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo "<ajax-response>";
    echo $xml;
    echo "</ajax-response>";



?>

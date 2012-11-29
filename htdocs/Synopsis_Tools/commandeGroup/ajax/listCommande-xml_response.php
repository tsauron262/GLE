<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 24 oct. 2010
  *
  * Infos on http://www.babel-services.com
  *
  */
 /**
  *
  * Name : listCommande-xml_response.php
  *
  * GLE-1.2
  *
  *
  */

    require_once('../../../main.inc.php');
    $id = $_REQUEST['id'];
    $comGrpId = $_REQUEST['comGrpId'];
    $requete = "SELECT ref,
                       rowid
                  FROM llx_commande
                 WHERE fk_soc = ".$id ."
                   AND rowid not in (SELECT command_refid FROM Babel_commande_grp,Babel_commande_grpdet WHERE Babel_commande_grpdet.commande_group_refid = Babel_commande_grp.id AND Babel_commande_grp.id =".$comGrpId.")";
    $sql = $db->query($requete);
    $xml= "";
    while ($res=$db->fetch_object($sql))
    {
        $xml .= "<commande id='".$res->rowid."'>".$res->ref."</commande>";
    }
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print "<ajax-response>";
    print $xml;
    print "</ajax-response>";

?>
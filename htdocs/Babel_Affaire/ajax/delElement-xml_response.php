<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : delElement-xml_response.php
  * GLE-1.2
  */
  require_once('../../main.inc.php');
  $id = $_REQUEST['id'];
  $xml = '';
  if ($id > 0)
  {
    //On recuepre le type et le eid
    $requete = "SELECT *
                  FROM Babel_Affaire_Element
                 WHERE id = ".$id;
    $sql = $db->query($requete);
    $return = "";
    if ($sql)
    {
        $res = $db->fetch_object($sql);
        $return .=  "<type>".$res->type."</type>";
        $return .=  "<eid>".$res->id."</eid>";
        switch($res->type)
        {
            case 'propale':
            {
                //Propale / PropaleGA
                if ($res->id > 0)
                {
                    require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
                    $prop = new Propal($db);
                    $prop->fetch($res->element_id);
                    $return .=  "<soc>".$prop->socid."</soc>";
                    $return .=  "<socname>".$prop->societe->nom."</socname>";
                }
            }
            break;
            case 'commande':
            {
                //Commande
                if ($res->id > 0)
                {
                    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                    $prop = new Commande($db);
                    $prop->fetch($res->element_id);
                    $return .=  "<soc>".$prop->socid."</soc>";
                    $return .=  "<socname>".$prop->societe->nom."</socname>";
                }

            }
            break;
        }

    }

     $requete = "DELETE FROM Babel_Affaire_Element
                       WHERE id = ".$id;
     $sql = $db->query($requete);
     if ($sql){
        $xml .= "<OK>OK</OK>";
        $xml .= $return;
     }  else {
        $xml .= "<KO>KO</KO>";
     }
  }
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo "<ajax-response>";
    echo $xml;
    echo "</ajax-response>";

?>

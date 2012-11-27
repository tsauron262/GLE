<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 8 ao�t 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : seekBySerial-xml_response.php
  * GLE-1.2
  */


   require_once('../../main.inc.php');

   $serial = $_REQUEST['serial'];
   $requete = "SELECT * FROM llx_product_serial_view WHERE serial_number ='". $serial."'";
   $sql = $db->query($requete);
   $xml="";
   while ($res=$db->fetch_object($sql))
   {
      $element_type = $res->element_type;
      if (preg_match('/^contrat/',$element_type))
      {
        $element_type='contrat';
      }
      $arrFR['contratSAV']='Extension de garantie';
      $arrFR['contratTkt']='Contrat au ticket';
      $arrFR['contratMnt']='Contrat de maintenance';
      $arrFR['contrat']='Contrat';
      $arrFR['facture']='Facture';
      $arrFR['expedition']='Expedition';
      $xml .= "<element>";
      $xml .= "<type><![CDATA[".$arrFR[$res->element_type]."]]></type>";
      $xml .= "<dbtype><![CDATA[".$res->element_type."]]></dbtype>";
      $xml .= "<id>".$res->element_id."</id>";
      $obj="";
      $id = 0;
      switch($element_type)
      {
        case "facture":
        {
            require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
            $obj = new Facture($db);
            $requete = "SELECT fk_facture FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = ".$res->element_id;
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            $id = $res1->fk_facture;
            $obj->fetch($id);
        }
        break;
        case "contrat":
        {
            require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
            $obj = new Contrat($db);
            $requete = "SELECT fk_contrat FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$res->element_id;
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            $id = $res1->fk_contrat;
            $obj->fetch($id);

        }
        break;
        case "expedition":
        {
            require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
            $obj = new Expedition($db);
            $requete = "SELECT fk_expedition FROM ".MAIN_DB_PREFIX."expeditiondet WHERE rowid = ".$res->element_id;
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            $id = $res1->fk_expedition;
            $obj->fetch($id);
        }
        break;
        default:
        {
            var_dump($element_type);
        }

      }

      $xml .= "<eid><![CDATA[".$id."]]></eid>";
      $xml .= "<socid><![CDATA[".$obj->socid."]]></socid>";

      $xml .= "<url><![CDATA[".$obj->getNomUrl(1)."]]></url>";
      $xml .= "<soc><![CDATA[".$obj->societe->getNomUrl(1)."]]></soc>";
      $xml .= "</element>";
   }


    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= "<xml>".$xml."</xml>";
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;

?>

<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 15 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : SAVgetData-xml_response.php
  * GLE-1.2
  */

  //data = "serial="+serial+"&fk_prod="+fk_prod+"&socid="+socid;
  require_once('../../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
  $serial = $_REQUEST['serial'];
  $fk_prod = $_REQUEST['fk_prod'];
  $socid = $_REQUEST['socid'];

//TODO verif societe => si serial et si produit
//TODO recherche tout les serial du client

//TODO si serial pas trouvé, switch to product, si pas de produit trouvé du type , switch to societe


  $xml="";

  if ($serial ."x" != "x")
  {
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product_serial_view WHERE serial_number = '".$serial."'";
    $sql = $db->query($requete);
    while($res=$db->fetch_object($sql))
    {
        $xml .= "<serial>";
        $xml .= "<element_id>".$res->element_id."</element_id>";
        //rectifier le type
        $type = $res->element_type;
        $ref = "";
        $prodRef = "";
        $label= "";
        if (preg_match('/^contrat/i',$type))
        {
            $type = 'contrat';
            $requete = "SELECT fk_contrat, fk_product FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$res->element_id;
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            //var_dump($res1->fk_contrat);
            $tmpProd = new Product($db);
            $tmpProd->fetch($res1->fk_product);
            $label = $tmpProd->label;
            $prodRef = $tmpProd->getNomUrl(1);
            //var_dump($res1->fk_contrat);
            require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
            $tmpObj = new Contrat($db);
            $tmpObj->fetch($res1->fk_contrat);
            $ref = $tmpObj->getNomUrl(1);
        } else if ($type=='expedition'){
            $requete = "SELECT ".MAIN_DB_PREFIX."expeditiondet.fk_expedition,
                               ".MAIN_DB_PREFIX."commandedet.fk_product
                          FROM ".MAIN_DB_PREFIX."expeditiondet,
                               ".MAIN_DB_PREFIX."commandedet
                         WHERE ".MAIN_DB_PREFIX."expeditiondet.rowid = ".$res->element_id. "
                           AND ".MAIN_DB_PREFIX."expeditiondet.fk_origin_line = ".MAIN_DB_PREFIX."commandedet.rowid ";
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            $tmpProd = new Product($db);
            $tmpProd->fetch($res1->fk_product);
            $prodRef = $tmpProd->getNomUrl(1);
            $label = $tmpProd->label;
            require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
            $tmpObj = new Expedition($db);
            $tmpObj->fetch($res1->fk_expedition);
            $ref = $tmpObj->getNomUrl(1);
        } else if ($type=='facture'){
            $requete = "SELECT fk_facture, fk_product FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = ".$res->element_id;
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            $tmpProd = new Product($db);
            $tmpProd->fetch($res1->fk_product);
            $prodRef = $tmpProd->getNomUrl(1);
            $label = $tmpProd->label;
            require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
            $tmpObj = new Facture($db);
            $tmpObj->fetch($res1->fk_facture);
            $ref = $tmpObj->getNomUrl(1);
        }
        $xml .= "<element_type><![CDATA[".$type."]]></element_type>";
        $xml .= "<ref><![CDATA[".$ref."]]></ref>";
        //Trouver le produit
        $xml .= "<label><![CDATA[".$label."]]></label>";
        $xml .= "<prodRef><![CDATA[".$prodRef."]]></prodRef>";
        $xml .= "</serial>";
    }
  } else if ($fk_prod > 0){
    //TODO recherche dans facture, expedition et contrat
    $requete = "SELECT ".MAIN_DB_PREFIX."contratdet.rowid, ".MAIN_DB_PREFIX."contratdet.fk_contrat, ".MAIN_DB_PREFIX."contratdet.fk_product
                  FROM ".MAIN_DB_PREFIX."contratdet,
                       ".MAIN_DB_PREFIX."contrat
                 WHERE ".MAIN_DB_PREFIX."contrat.fk_soc=".$socid."
                   AND ".MAIN_DB_PREFIX."contratdet.fk_contrat = ".MAIN_DB_PREFIX."contrat.rowid
                   AND ".MAIN_DB_PREFIX."contratdet.fk_product =".$fk_prod;
    $sql = $db->query($requete);
    require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    while($res=$db->fetch_object($sql))
    {
        if ($db->num_rows($sql) > 0)
        {
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product_serial_view WHERE element_type LIKE 'contrat%' AND element_id =".$res->rowid;
            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);

            $prod = new Product($db);
            $prod->fetch($res->fk_product);

            $contrat = new Contrat($db);
            $contrat->fetch($res->fk_contrat);

            $xml .= "<product>";
            $xml .= "  <serial><![CDATA[".$res1->serial_number."]]></serial>";
            $xml .= "  <ref><![CDATA[".$prod->getNomUrl(1)."]]></ref>";
            $xml .= "  <label><![CDATA[".$prod->libelle."]]></label>";
            $xml .= "  <refElement><![CDATA[".$contrat->getNomUrl(1)."]]></refElement>";
            $xml .= "  <type>contrat</type>";
            $xml .= "  <htmlId>contrat-".$res->rowid."</htmlId>";
            $xml .= "</product>";
        }
    }
    $requete = "SELECT ".MAIN_DB_PREFIX."facturedet.rowid, ".MAIN_DB_PREFIX."facturedet.fk_product
                  FROM ".MAIN_DB_PREFIX."facturedet,
                       ".MAIN_DB_PREFIX."facture
                 WHERE fk_product ".$fk_prod." AND ".MAIN_DB_PREFIX."facturedet.fk_facture = ".MAIN_DB_PREFIX."facture.rowid AND fk_soc = ".$socid;
    $sql = $db->query($requete);
    while($res=$db->fetch_object($sql))
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product_serial_view WHERE element_type = 'facture' AND element_id = ".$res->rowid;
        $sql1 = $db->query($requete);
        if ($db->num_rows($sql1) > 0)
        {
            $res1 = $db->fetch_object($sql1);
            $prod = new Product($db);
            $prod->fetch($res->fk_product);
            require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
            $facture = new Facture($db);
            $facture->fetch($res->fk_facture);
            $xml .= "<product>";
            $xml .= "  <serial><![CDATA[".$res1->serial_number."]]></serial>";
            $xml .= "  <ref><![CDATA[".$prod->getNomUrl(1)."]]></ref>";
            $xml .= "  <label><![CDATA[".$prod->label."]]></label>";
            $xml .= "  <refElement><![CDATA[".$facture->getNomUrl(1)."]]></refElement>";
            $xml .= "  <type>facture</type>";
            $xml .= "  <htmlId>facture-".$res->rowid."</htmlId>";
            $xml .= "</product>";
        }
    }
    $requete = "SELECT ".MAIN_DB_PREFIX."expeditiondet.fk_product, ".MAIN_DB_PREFIX."expeditiondet.fk_expedition, ".MAIN_DB_PREFIX."expeditiondet.rowid
                  FROM ".MAIN_DB_PREFIX."expeditiondet, ".MAIN_DB_PREFIX."expedition
                 WHERE fk_product = ".$fk_prod."
                   AND ".MAIN_DB_PREFIX."expedition.rowid = ".MAIN_DB_PREFIX."expeditiondet.fk_expedition
                   AND rowid =". $res->element_id;
    $sql = $db->query($requete);
    while($res=$db->fetch_object($sql))
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product_serial_view WHERE element_type LIKE 'expedition'";
        $sql1 = $db->query($requete);
        if ($db->num_rows($sql1) > 0)
        {
            $res1 = $db->fetch_object($sql1);
            $prod = new Product($db);
            $prod->fetch($res1->fk_product);
            require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
            $expedition = new Expedition($db);
            $expedition->fetch($res1->fk_expedition);
            $xml .= "<product>";
            $xml .= "  <serial><![CDATA[".$res->serial_number."]]></serial>";
            $xml .= "  <ref><![CDATA[".$prod->getNomUrl(1)."]]></ref>";
            $xml .= "  <label><![CDATA[".$prod->label."]]></label>";
            $xml .= "  <refElement><![CDATA[".$expedition->getNomUrl(1)."]]></refElement>";
            $xml .= "  <type>expedition</type>";
            $xml .= "  <htmlId>expedition-".$res->rowid."</htmlId>";
            $xml .= "</product>";
        }
    }

  } else if ($socid > 0)
  {
    //TODO recherche tout les serial du client
  }


    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= "<xml>".$xml."</xml>";
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
?>

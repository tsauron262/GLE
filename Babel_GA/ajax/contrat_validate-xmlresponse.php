<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 5 avr. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : contrat_validate-xmlresponse.php
  * GLE-1.1
  */

  //Liste  les produit du contrat et les quantite
  $id = $_REQUEST['id'];


    require_once('../../main.inc.php');
    $action = $_REQUEST['action'];
    $xml = "<ajax-response>";
    switch ($action)
    {
        case 'validateContrat':
        {
            require_once(DOL_DOCUMENT_ROOT."/Babel_GA/ContratGA.class.php");
            $contrat = new ContratGA($db);
            $contrat->fetch($id);
            $contrat->fetch_lignes();
            $data = array();
            foreach($_REQUEST as $key=>$val)
            {
                if (preg_match('/fk_product-([0-9]*)/',$key,$arr))
                {
                    $data[$arr[1]]['fk_product']=$val;
                    $data[$arr[1]]['serial']=$_REQUEST['serial-'.$arr[1]];
                    //$data[$arr[1]]['force_commande']=($_REQUEST['serial-'.$arr[1]]."x" == "x"?false:true);
                }
            }
            $contrat->facFournRef = false;
            if ($_REQUEST['factFournRef'].'x' != 'x') $contrat->facFournRef = $_REQUEST['factFournRef'];
            $res = $contrat->validate($data);
            if ($res > 0)
            {
                $xml .= "<OK>OK</OK>";
            } else {
                $xml .= "<KO>KO</KO>";
                $xml .= "<KOText><![CDATA[".$contrat->error."]]></KOText>";
            }
        }
        break;
        case 'renew':
        {
            require_once(DOL_DOCUMENT_ROOT."/Babel_GA/ContratGA.class.php");
            $contrat = new ContratGA($db);
            $contrat->fetch($id);
            $contrat->fetch_lignes();
            $contrat->create();
        }
        default:
        {
              $requete = "SELECT *
                            FROM ".MAIN_DB_PREFIX."contratdet
                           WHERE fk_product IS NOT NULL
                             AND fk_contrat = ".$id ;
               $sql = $db->query($requete);
               while ($res = $db->fetch_object($sql))
               {
                    $qteStock = 0;
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_product=".$res->fk_product;
                    $sql1 = $db->query($requete);
                    $res1 = $db->fetch_object($sql1);
                    if ($res1->reel > 0)
                    {
                        $qteStock = $res1->reel;
                    }
                    $requete = "SELECT count(*) as cnt FROM Babel_GA_entrepotdet WHERE fk_product=".$res->fk_product ." AND statut = 0";
                    $sql1 = $db->query($requete);
                    $res1 = $db->fetch_object($sql1);
                    $qteStockGA=0;
                    if ($res1->cnt > 0)
                    {
                        $qteStockGA = $res1->cnt;
                    }

                    $xml .= utf8_encode("<prod id='".$res->fk_product."' qte='".$res->qty."' qteStockDispo='".$qteStock."' qteGADispo='".$qteStockGA."' ><![CDATA[".$res->description."]]>");
                    $xml .= "</prod>";
               }

        }
        break;
    }


    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo $xml;
    echo "</ajax-response>";



?>
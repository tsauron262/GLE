<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 16 juil. 2010
  *
  * Infos on http://www.babel-services.com
  *
  */
 /**
  *
  * Name : stat_produit_json.php
  * GLE-1.1
  */

  require_once('../../../main.inc.php');

  $type=$_REQUEST['type'];

//$sql  = "SELECT p.rowid,
//                p.label,
//                p.ref,
//                fk_product_type,
//                count(*) as c
//           FROM ".MAIN_DB_PREFIX."propaldet as pd,
//                ".MAIN_DB_PREFIX."product as p,
//                ".MAIN_DB_PREFIX."propal as pl
//          WHERE p.rowid = pd.fk_product
//            AND pl.rowid = pd.fk_propal
//            AND pl.datep > date_sub(now(), interval 12 month)
//            AND pl.fk_statut in (2,4)";
$sql  = "SELECT p.rowid,
                p.label,
                p.ref,
                fk_product_type,
                sum(pd.qty) as c
           FROM ".MAIN_DB_PREFIX."commandedet as pd,
                ".MAIN_DB_PREFIX."product as p,
                ".MAIN_DB_PREFIX."commande as pl
          WHERE p.rowid = pd.fk_product
            AND pl.rowid = pd.fk_commande
            AND pl.date_commande > date_sub(now(), interval 12 month)
            AND pl.fk_statut in (2,4)";
if ($type."x" != "x")
{
    $sql .= 'AND p.fk_product_type='.$type;
}
$sql .= " GROUP BY (p.rowid)
          ORDER BY c DESC
             LIMIT 5";
//file_put_contents("/tmp/toto",$sql);
$result=$db->query($sql) ;
$arr=array();

$arrVal= array();
$nolabel=false;
$radius=100;
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;

  $var=True;
  while ($i < $num)
  {
      $res = $db->fetch_object($result);
      if ($_REQUEST['fullSize'])
      {
          $nolabel=false;
          $radius=150;
          $arrVal[$i]=array("value" => round($res->c), "label" => dol_trunc($res->label,45), "on-click" => $dolibarr_main_url_root.'/product/fiche.php?id='.$res->rowid );
      } else {
          $arrVal[$i]=array("value" => round($res->c), "label" => dol_trunc($res->label,20), "on-click" => $dolibarr_main_url_root.'/product/fiche.php?id='.$res->rowid );
      }
      $i++;
  }
}
      $arr['elements']=array(array("tip" => "#val# ventes de #label#",
                               "colours" => array(  "0x24A12B",  "0x243D8A",
                                                     "0xF43210",
                                                    "0xEB7916", "0x3F0E63", "0xEDEC0A",
                                                    "0x21A56F",  "0x221B6F",
                                                     "0xEB3914",
                                                    "0xEEA60D" , "0x91135E", "0x6AC720"
                                                     ),
                                   "alpha" => 0.8,
                             "start_angle" => 135,
                               "no-labels" => $nolabel,
                                 "animate" => array(0=> array("type"=>"bounce","distance"=>35), 1=> array("type"=>fade)),
                                  "values" => $arrVal,
                                    "type" => "pie",
                                   "radius"=> $radius,
                                  "border" => "2" ));
      $arr['bg_colour'] ="#FAFCBC";
      $arr['ani--mate']=true;
      if (!isset($type))
      {
          $arr['title']=array('text' => "5 meilleurs ventes produits/services (12 mois)", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
      } else if($type==0)
      {
        $arr['title']=array('text' => "5 meilleurs ventes produits (12 mois)", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
      } else if ($type==1){
        $arr['title']=array('text' => "5 meilleurs ventes services (12 mois)", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
      } else if ($type==2){
        $arr['title']=array('text' => "5 meilleurs ventes produits contrats (12 mois)", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
      } else {
        $arr['title']=array('text' => "5 meilleurs ventes produits/services (12 mois)", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
      }


      echo json_encode($arr);

?>

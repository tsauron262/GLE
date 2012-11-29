<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 18 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : prod_list.php
  * GLE-1.0
  */

$wh = "";
if ($_REQUEST["q"] ."x" != "x" && $_REQUEST['type']=="s")
{
    $wh = " AND llx_product.label LIKE '".utf8_decode($_REQUEST["q"]) ."%'";
} else {
    $wh = " AND (llx_product.label LIKE '%".utf8_decode($_REQUEST["q"]) ."%'
             OR llx_product.ref  LIKE '%".addslashes(utf8_decode($_REQUEST["q"]))."%'
             OR llx_product.label LIKE '%".addslashes(utf8_decode($_REQUEST["q"]))."%'
             OR llx_product.description LIKE '%".addslashes(utf8_decode($_REQUEST["q"]))."%'
             OR llx_product.note LIKE '%".addslashes(utf8_decode($_REQUEST["q"]))."%' ) ";
}
$limit="";
if ($_REQUEST['limit'] ."x" != "x")
{
    $limit = " LIMIT ".$_REQUEST['limit']." ";
} else {
    $limit = " LIMIT 10 ";
}



require_once('../../main.inc.php');
  $requete = "SELECT * FROM llx_product";
  if ($_REQUEST['fournid']."x" != "x" && preg_match('/[0-9]*/',$_REQUEST['fournid']))
{
    $requete .= " , llx_product_fournisseur";
}
    $requete .= " WHERE 1=1 ";
if ($_REQUEST['fournid']."x" != "x" && preg_match('/[0-9]*/',$_REQUEST['fournid']))
{
    $requete .= " AND llx_product_fournisseur.fk_soc = ".$_REQUEST['fournid'];
    $requete .= " AND llx_product_fournisseur.fk_product = llx_product.rowid ";
}
if ("x".$_REQUEST['typeId'] != "x")
{
    $requete .= " AND llx_product.fk_product_type =  ".$_REQUEST["typeId"];
}
if ('x'.$_REQUEST['envente'] != "x")
{
    $requete .= " AND llx_product.envente =  ".$_REQUEST["envente"];
}


$requete .= $wh;
$requete .= " ORDER BY llx_product.label ";
$requete .= $limit;




$sql=$db->query($requete);
$arr=array();
while ($res = $db->fetch_object($sql))
{
    array_push($arr,array('id' =>$res->rowid , 'label' => utf8_encode($res->label)));
}
echo json_encode($arr);

?>

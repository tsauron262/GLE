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

require_once('magento_soap.class.php');

$mag = new magento_soap($conf);
//
//require_once('Var_Dump.php');
//Var_Dump::displayInit(array('display_mode' => 'HTML4_Table'), array('mode' => 'normal','offset' => 4));

$mag->connect();
$rs = $mag->prod_prod_list();
$rs1 = $mag->prod_cat_list();
$mag->parseProdList($rs);

//Parse category

$mag->parseCat($rs1,$catId);

echo json_encode($mag->jsonArr);
$mag->disconnect();



?>
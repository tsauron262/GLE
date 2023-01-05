<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);

$tabBox = array(
    array('module'=>'bimpcommercial', 'object'=>'Bimp_Facture', 'methode'=>'dataGraphPayeAn'),
    array('module'=>'bimpcommercial', 'object'=>'Bimp_Facture', 'methode'=>'dataGraphSecteur'),
    array('module'=>'bimpcore', 'object'=>'Bimp_User', 'methode'=>'boxCreateUser'),
    array('module'=>'bimpcore', 'object'=>'Bimp_User', 'methode'=>'boxServiceUser'),
    
    
    
);

$sql = $db->query("SELECT * FROM `llx_boxes_def` WHERE `file` = 'box_bimp_core.php@bimpcore' ORDER BY `rowid` DESC");
$exist = array();
while ($ln = $db->fetch_object($sql)){
    $infos = json_decode($ln->note, true);
    $exist[] = $infos['module'].$infos['object'].$infos['methode'];
}

foreach($tabBox as $infos){
    if(!in_array($infos['module'].$infos['object'].$infos['methode'], $exist)){
        $db->query("INSERT INTO `llx_boxes_def`(`file`, `entity`, `note`) VALUES ('box_bimp_core.php@bimpcore', 1, '". json_encode($infos)."')");
        $id = $db->last_insert_id('llx_boxes_def');
        $db->query("INSERT INTO `llx_boxes`(`entity`, `box_id`, `position`, `box_order`, `fk_user`) VALUES ('1', ".$id.", 0, 0, 0)");
        echo 'box '.$infos['module'].$infos['object'].$infos['methode'].' ajouté<br/>';
    }
}

echo 'ok fin';

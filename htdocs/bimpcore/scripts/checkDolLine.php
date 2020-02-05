<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK Dol lines', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

echo 'DEBUT <br/><br/>';

global $db;




$tabInfo = $errors = array();

$tabInfo[] = array('fk_propal', 'llx_propaldet', array('llx_bimp_propal_line', 'llx_bs_sav_propal_line'), 'Bimp_Propal');
$tabInfo[] = array('fk_commande', 'llx_commandedet', 'llx_bimp_commande_line', 'Bimp_Commande');
$tabInfo[] = array('fk_facture', 'llx_facturedet', 'llx_bimp_facture_line', 'Bimp_Facture');
$tabInfo[] = array('fk_commande', 'llx_commande_fournisseurdet', 'llx_bimp_commande_fourn_line', 'Bimp_CommandeFourn');
$tabInfo[] = array('fk_facture_fourn', 'llx_facture_fourn_det', 'llx_bimp_facture_fourn_line', 'Bimp_FactureFourn');

foreach($tabInfo as $info){
    $i = 0;
    if(!is_array($info[2]))
        $info[2] = array($info[2]);
    $where = array();
    foreach($info[2] as $table)
        $where[] = 'rowid NOT IN (SELECT id_line FROM '.$table.')';
    $req = 'SELECT DISTINCT(`'.$info[0].'`) as id FROM `'.$info[1].'` WHERE '.implode(' AND ', $where).' ORDER BY`rowid` ASC';
    $sql = $db->query($req);
    $tot= $db->num_rows($sql);
    $sql = $db->query($req.' LIMIT 0,1000');
    while ($ln = $db->fetch_object($sql)){
        $comm = BimpCache::getBimpObjectInstance('bimpcommercial', $info[3], $ln->id);
        if($comm->isLoaded()){
            $errors = array_merge($errors, $comm->checkLines());
            $i++;
        }
//        echo $ln->id."<br/>";
    }
    echo '<br/>fin '.$i.' / '.$tot.' corrections de '.$info[1];
}

echo "<br/>";
print_r($errors);

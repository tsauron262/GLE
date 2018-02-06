<?php

/**
 *  \file       htdocs/bimpequipment/addequipment/viewAddEquipment.php
 *  \ingroup    bimpequipment
 *  \brief      Used while adding equipments
 */

require_once '../../main.inc.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/addequipment/css/styles.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/addequipment/js/ajax.js');


/*
 * Functions  
 */

function getAllEntrepots($db) {
    
    $entrepots = array();

    $sql = 'SELECT rowid, label';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            $entrepots[$obj->rowid] = $obj->label;
        }
    }
    return $entrepots;
}

function getAllproducts($db) {
    
    $prods = array();

    $sql = 'SELECT rowid, label';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product';
    $sql .= ' LIMIT 25'; // TODO remove that

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            $prods[$obj->rowid] = dol_trunc($obj->label, 100);
        }
    }
    return $prods;
}

/*
 * 	View
 */

llxHeader('', 'Ajouter équipement', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Ajouter équipement', $linkback);

$entrepots = getAllEntrepots($db);

print 'Entrepot <select id="entrepot" class="select2 cust" style="width: 200px;">';
foreach ($entrepots as $id => $name) {
    print '<option value="'.$id.'">'.$name.'</option>';
}
print '</select><br/><br/>';

$prods = getAllproducts($db);

//$types = array(
//        1 => 'Ordinateur',
//        2 => 'Accessoire',
//        3 => 'License',
//        4 => 'Serveur',
//        5 => 'Matériel réseau',
//        6 => 'Autre'
//    );

print 'Type de produit <select id="type" class="select2 cust" style="width: 400px;">';
foreach ($prods as $id => $name) {
    print '<option value="'.$id.'">'.$name.'</option>';
}
print '</select>';

print '<table id="hereEquipment">';
print '<thead>';
print '<th>Nombre de produits scannés</th>';
print '<th>Type de produit</th>';
print '<th>Numéro de série</th>';
print '<th>Note</th>';
print '<th>Réponse serveur</th>';
print '</thead>';
print '</table>';
//print '<div id="hereEquipment" style="margin-top: 20px"></div>';
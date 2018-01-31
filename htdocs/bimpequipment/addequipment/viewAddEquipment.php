<?php

/**
 *  \file       htdocs/bimpequipment/addequipment/viewAddEquipment.php
 *  \ingroup    bimpequipment
 *  \brief      Used while adding equipments
 */

require_once '../../main.inc.php';

include DOL_DOCUMENT_ROOT.'core/class/html.form.class.php';

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

$form = new Form($db);
$form->select_produits();
print '<text id="alertProd" style="color: red ; margin-left: 10px"> </text>';

print '<table id="hereEquipment">';
print '<thead>';
print '<th>Nombre de produits scannés</th>';
print '<th>Identifiant du produit</th>';
print '<th>Numéro de série</th>';
print '<th>Note</th>';
print '<th>Réponse serveur</th>';
print '</thead>';
print '</table>';


print '<br><input type="button" class="butAction" value="Enregistrer">';


$db->close();

llxFooter();

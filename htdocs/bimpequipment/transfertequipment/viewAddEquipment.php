<?php

/**
 *  \file       htdocs/bimpequipment/addequipment/viewAddEquipment.php
 *  \ingroup    bimpequipment
 *  \brief      Used while adding equipments
 */
require_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . 'core/class/html.form.class.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/transfertequipment/css/styles.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/transfertequipment/js/ajax.js');


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

llxHeader('', 'Transférer équipement', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Transférer équipement', $linkback);

$entrepots = getAllEntrepots($db);

print 'Entrepôt de départ <select id="entrepotStart" class="select2 cust" style="width: 200px;">';
foreach ($entrepots as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select><br/><br/>';

print 'Entrepôt d\'arrivé <select id="entrepotEnd" class="select2 cust" style="width: 200px;">';
foreach ($entrepots as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select><br/><br/>';

print 'Réf. ou code barre ou numéro de série ';
print '<input name="refScan" class="custInput" style="width : 300px"><br>';


$form = new Form($db);
$form->select_produits();
print '<br><span> Quantité : </span><input id="qty" type="number" class="custInput" style="width: 40px" value=1 min=1><br>';
print '<span>Ajouter : </span><img id="addProduct" src="css/plus.ico" class="clickable" style="margin-bottom : -7px">';

print '<table id="productTable" class="custTable">';
print '<thead>';
print '<th>Identifiant</th>';
print '<th>Référence</th>';
print '<th>Numéro de série</th>';
print '<th>Label</th>';
print '<th style="border-right:none">Quantité</th>';
print '<th style="border-left:none">Modifier</th>';
print '<th>Produit Restant</th>';
print '<th>Supprimer</th>';
print '</thead>';
print '</table>';

print '<br/><div id="alertProd"></div><br/><br/>';
    
print '<input id="enregistrer" type="button" class="butAction" value="Enregistrer">';
print '<br/><div id="alertEnregistrer"></div>';

print '<audio id="bipAudio" preload="auto"><source src="audio/bip.wav" type="audio/mp3" /></audio>';
print '<audio id="bipAudio2" preload="auto"><source src="audio/bip2.wav" type="audio/mp3" /></audio>';
print '<audio id="bipError" preload="auto"><source src="audio/error.wav" type="audio/mp3" /></audio>';

include(DOL_DOCUMENT_ROOT."//bimpequipment/transfertequipment/scan/scan.php");

$db->close();

llxFooter();

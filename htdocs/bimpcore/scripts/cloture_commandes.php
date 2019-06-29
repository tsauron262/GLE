<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'CLOTURE COMMANDES', 0, 0, array(), array());

echo '<body>';
BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);



echo 'Clôture des commmandes clients: ';
if ($bdb->update('commande', array(
            'fk_statut'         => 3,
            'logistique_status' => 6,
            'shipment_status'   => 2,
            'invoice_status'    => 2,
            'id_user_resp'      => 0,
            'status_forced'     => json_encode(array(
                'logistique' => 1,
                'shipment'   => 1,
                'invoice'    => 1,
                    ), 1)
        ), '`fk_statut` > 0') <= 0) {
    echo '<span class="danger">[ECHEC] - ' . $bdb->db->lasterror() . '</span>';
} else {
    echo '<span class="success">[OK]</span>';
}
echo '<br/>';




echo 'Mise à 0 des lignes de type texte: ';

$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'bimp_commande_line a';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet cdet ON cdet.rowid = a.id_line';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande c ON c.rowid = a.id_obj';
$sql .= ' SET a.qty_total = 0, a.qty_modif = 0, cdet.qty = 0';
$sql .= ' WHERE a.type = 2 AND c.fk_statut > 0';

if ($bdb->execute($sql) <= 0) {
    echo '<span class="danger">[ECHEC] - ' . $bdb->db->lasterror() . '</span>';
} else {
    echo '<span class="success">[OK]</span>';
}
echo '<br/>';




echo 'Maj qté totale lignes commandes clients: ';

$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'bimp_commande_line a';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande c ON c.rowid = a.id_obj';
$sql .= ' SET a.qty_total = a.qty_modif + (SELECT cdet.qty FROM ' . MAIN_DB_PREFIX . 'commandedet cdet WHERE cdet.rowid = a.id_line)';
$sql .= ' WHERE c.fk_statut > 0';

if ($bdb->execute($sql) <= 0) {
    echo '<span class="danger">[ECHEC] - ' . $bdb->db->lasterror() . '</span>';
} else {
    echo '<span class="success">[OK]</span>';
}

echo '<br/>';




echo 'Maj qtés logistiques >= 0 lignes commandes clients: ';

$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'bimp_commande_line a';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande c ON c.rowid = a.id_obj';
$sql .= ' SET';
$sql .= ' a.qty_shipped = a.qty_total';
$sql .= ', a.qty_billed = a.qty_total';
$sql .= ', a.qty_to_ship = 0';
$sql .= ', a.qty_to_bill = 0';
$sql .= ' WHERE a.qty_total >= 0 AND c.fk_statut > 0';

if ($bdb->execute($sql) <= 0) {
    echo '<span class="danger">[ECHEC] - ' . $bdb->db->lasterror() . '</span>';
} else {
    echo '<span class="success">[OK]</span>';
}

echo '<br/>';




echo 'Maj qtés logistiques < 0 lignes commandes clients: ';

$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'bimp_commande_line a';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande c ON c.rowid = a.id_obj';
$sql .= ' SET';
$sql .= ' a.qty_shipped = a.qty_total * -1';
$sql .= ', a.qty_billed = a.qty_total * -1';
$sql .= ', a.qty_to_ship = 0';
$sql .= ', a.qty_to_bill = 0';
$sql .= ' WHERE a.qty_total < 0 AND c.fk_statut > 0';

if ($bdb->execute($sql) <= 0) {
    echo '<span class="danger">[ECHEC] - ' . $bdb->db->lasterror() . '</span>';
} else {
    echo '<span class="success">[OK]</span>';
}

echo '<br/>';

echo '</body></html>';

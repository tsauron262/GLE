<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'EXPORT CONGES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$date_from = '2019-12-16';
$date_to = '2020-01-19';

$where = 'date_debut <= \'' . $date_to . '\'';
$where .= ' AND date_fin >= \'' . $date_from . '\'';
$where .= ' AND statut = 6';

$rows = $bdb->getRows('holiday', $where, null, 'array', null, 'rowid', 'desc');

$data = array();

$userCP = new User($bdb->db);
$typesConges = array(
    0 => 'Congés payés',
    1 => 'Absence exceptionnelle',
    2 => 'RTT'
);

$ent_rows = $bdb->getRows('entrepot', 1, NULL, 'array', array('rowid', 'town'));
$entrepots = array();
foreach ($ent_rows as $er) {
    $entrepots[(int) $er['rowid']] = $er['town'];
}


foreach ($rows as $r) {
    if ($r['date_debut'] < $date_from) {
        $r['date_debut'] = $date_from;
    }
    if ($r['date_fin'] > $date_to) {
        $r['date_fin'] = $date_to;
    }

    $date_debut_gmt = $bdb->db->jdate($r['date_debut'], 1);
    $date_fin_gmt = $bdb->db->jdate($r['date_fin'], 1);
    $nbJours = num_open_dayUser((int) $r['fk_user'], $date_debut_gmt, $date_fin_gmt, 0, 1, (int) $r['halfday']);
    $userCP->fetch((int) $r['fk_user']);

    if (!BimpObject::objectLoaded($userCP)) {
        echo BimpRender::renderAlerts('USER KO: ' . $r['fk_user']);
        continue;
    }

    $id_user_ent = isset($userCP->array_options['options_defaultentrepot']) ? (int) $userCP->array_options['options_defaultentrepot'] : 0;

    $dt_from = new DateTime($r['date_debut']);
    $dt_to = new DateTime($r['date_fin']);
    
    $data[] = array(
        $userCP->lastname,
        $userCP->firstname,
        $bdb->getValue('user', 'matricule', 'rowid = '.(int) $userCP->id),
        ($id_user_ent && isset($entrepots[$id_user_ent]) ? $entrepots[$id_user_ent] : 'LYON'),
        $typesConges[(int) $r['type_conges']],
        str_replace(';', ',', str_replace("\n", ' ', $r['description'])),
        $dt_from->format('d / m / Y'),
        $dt_to->format('d / m / Y'),
        $nbJours
    );
}

$str = 'NOM;PRENOM;MATRICULE;VILLE;TYPE CONGES;INFOS;DATE DEBUT;DATE FIN;NOMBRE JOURS' . "\n";

foreach ($data as $line) {
    $str .= implode(';', $line) . "\n";
}

if (file_put_contents(DOL_DATA_ROOT . '/bimpcore/export_conges.csv', $str)) {
    echo 'FICHIER OK';
} else {
    echo 'FICHIER KO';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
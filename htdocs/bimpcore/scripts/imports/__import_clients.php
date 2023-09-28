<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'IMPORT FOURNS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

global $bdb, $keys;

//SELECT CT_Num Id, Name, Email, Phone, CreatedAt, UpdatedAt, SiretNumber, VatNumber, LastActivity, Street, StreetBis, PostalCode, City, CountryCode, Country, EnCours

$keys = array(
    'ref'           => 0,
    'name'          => 1,
    'email'         => 2,
    'phone'         => 3,
    'date_create'   => 4,
    'date_update'   => 5,
    'siret'         => 6,
    'num_tva'       => 7,
    'last_activity' => 8,
    'street'        => 9,
    'street2'       => 10,
    'zip'           => 11,
    'town'          => 12,
    'code_pays'     => 13,
    'pays'          => 14,
    'encours'       => 15
);

$bdb = new BimpDb($db);

$dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';
$file_name = 'import_fourn.csv';

if (!file_exists($dir . $file_name)) {
    echo BimpRender::renderAlerts('Le fichier "' . $dir . $file_name . '" n\'existe pas');
    exit;
}

$rows = array();
$lines = file($dir . $file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $idx => $line) {
    $data = str_getcsv($line, ';');
    $row = array();

    foreach ($keys as $code => $i) {
        if ($data[$i] == 'NULL') {
            $row[$code] = '';
            continue;
        }
        $row[$code] = $data[$i];
    }

    $rows[] = $row;
}

//echo '<pre>';
//print_r($rows);
//echo '</pre>';

if (!(int) BimpTools::getValue('exec', 0)) {
    if (is_array($rows) && count($rows)) {
        echo count($rows) . ' élément(s) à traiter <br/><br/>';

        $path = pathinfo(__FILE__);
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Exécuter';
        echo '</a>';
        exit;
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
    exit;
}

//'ref'           => 0,
//    'name'          => 1,
//    'email'         => 2,
//    'phone'         => 3,
//    'date_create'   => 4,
//    'date_update'   => 5,
//    'siret'         => 6,
//    'num_tva'       => 7,
//    'last_atcivity' => 8,
//    'street'        => 9,
//    'street2'       => 10,
//    'zip'           => 11,
//    'town'          => 12,
//    'code_pays'     => 13,
//    'pays'          => 14,
//    'encours'       => 15

$results = $bdb->getRows('c_country', $where, null, 'array', array('rowid', 'code_iso'));
$countries = array();

foreach ($results as $result) {
    $countries[$result['code_iso']] = (int) $result['rowid'];
}

$societe = new Societe($bdb->db);

$nOk = 0;
$nFails = 0;

foreach ($rows as $r) {
    $societe->code_fournisseur = -1;
    $societe->get_codefournisseur($societe, 1);

    $id_pays = 0;
    if ($r['code_pays'] == 'FRA' || $r['pays'] == 'FRANCE') {
        $id_pays = 1;
    } elseif (isset($countries[$r['code_pays']])) {
        $id_pays = $countries[$r['code_pays']];
    }

    if ($bdb->insert('societe', array(
                'code_fournisseur'        => $societe->code_fournisseur,
                'code_compta'        => $r['ref'],
                'nom'                => $r['name'],
                'fournisseur'        => 1,
                'fk_typent'          => ($r['siret'] ? 100 : 8),
                'email'              => $r['email'],
                'phone'              => $r['phone'],
                'siret'              => $r['siret'],
                'siren'              => ($r['siret'] ? substr($r['siret'], 0, 9) : ''),
                'tva_intra'          => $r['num_tva'],
                'address'            => $r['street'],
                'zip'                => $r['zip'],
                'town'               => $r['town'],
                'fk_pays'            => $id_pays,
                'date_last_activity' => substr($r['last_activity'], 0, 10),
                'datec'              => substr($r['date_create'], 0, 19),
            )) <= 0) {
        echo 'ECHEC insertion - ' . $r['ref'] . ' - ' . $bdb->err() . '<br/>';
        $nFails++;
    } else {
        $nOk++;
    }

//    break;
}

echo '<br/>';
echo $nOk . 'OK<br/>';
echo $nFails . ' échecs <br/>';
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

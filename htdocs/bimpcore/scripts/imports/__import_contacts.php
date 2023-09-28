<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'IMPORT CONTACTS', 0, 0, array(), array());

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
    'ref'    => 0,
    'civ'    => 1,
    'nom'    => 2,
    'prenom' => 3,
    'poste'  => 4,
    'tel'    => 5,
    'mobile' => 6,
    'email'  => 7
);

$bdb = new BimpDb($db);

$dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';
$file_name = 'import_contacts.csv';

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

$nOk = 0;
$nFails = 0;

foreach ($rows as $r) {
    echo '<br/>' . $r['ref'] . ' : ';

    $id_client = (int) $bdb->getValue('societe', 'rowid', 'code_compta = \'' . $r['ref'] . '\'');

    if ($id_client) {
        echo '<span class="success"> Client #' . $id_client . '</span>';

        $civility = 0;
        switch ($r['civ']) {
            case 0:
            default:
                $civility = 2;
                break;

            case 1:
                $civility = 1;
                break;

            case 2:
                $civility = 3;
                break;
        }

        $id_contact = $bdb->insert('socpeople', array(
            'datec'         => date('Y-m-d H:i:s'),
            'fk_soc'        => $id_client,
            'civility'      => $civility,
            'lastname'      => $r['nom'],
            'firstname'     => $r['prenom'],
            'email'         => $r['email'],
            'phone_perso'   => $r['rel'],
            'phone_mobile'  => $r['mobile'],
            'poste'         => $r['poste'],
            'fk_user_creat' => 1
                ), true);

//        $errors = array();
//        $contact = BimpObject::createBimpObject('bimpcore', 'Bimp_Contact', array(
//                    'fk_soc'       => $id_client,
//                    'civility'     => $civility,
//                    'lastname'     => $r['nom'],
//                    'firstname'    => $r['prenom'],
//                    'email'        => $r['email'],
//                    'phone_perso'  => $r['rel'],
//                    'phone_mobile' => $r['mobile'],
//                    'poste'        => $r['poste']
//                        ), true, $errors);

        if (!$id_contact) {
            echo '<span class="danger">FAIL - ' . $bdb->err() . '</span>';
            $nFails++;
        } else {
            echo ' - <span class="success">[OK]</span> #' . $id_contact;
            $nOk++;
        }

//        break;
    } else {
        echo '<span class="danger">Client non trouvé</span>';
    }
}

echo '<br/>';
echo $nOk . 'OK<br/>';
echo $nFails . ' échecs <br/>';
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

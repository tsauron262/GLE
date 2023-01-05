<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';

global $db;
$bdb = new BimpDb($db);

$where = '`id_sav` > 0 AND `id_equipment` > 0 AND `status` = 304';
$rows = $bdb->getRows('br_reservation', $where, null, 'array');

foreach ($rows as $r) {
    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $r['id_sav']);
    if (!BimpObject::objectLoaded($sav)) {
        echo 'RES ' . $r['id'] . ': SAV invalide (' . $r['id_sav'] . ') <br/>';
    } else {
        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $r['id_equipment']);
        if (!BimpObject::objectLoaded($equipment)) {
            echo 'RES ' . $r['id'] . ': Equipement invalide (' . $r['id_equipment'] . ') <br/>';
        } else {
            $client = $sav->getChildObject('client');
            if (!BimpObject::objectLoaded($client)) {
                echo 'SAV ' . $sav->id . ': client invalide (' . $sav->getData('id_client') . ') <br/>';
            } else {
                $place = $equipment->getCurrentPlace();
                if (!BimpObject::objectLoaded($place)) {
                    echo '[ERREUR] RES ' . $r['id'] . ': aucun emplacement enregistré (Equipement ' . $equipment->id . ') <br/>';
                } else {
                    if ((int) $place->getData('type') !== BE_Place::BE_PLACE_CLIENT) {
                        echo '[ERREUR] RES ' . $r['id'] . ': type d\'emplacement invalide (Equipement ' . $equipment->id . ') <br/>';
                    } elseif ((int) $place->getData('id_client') !== (int) $client->id) {
                        echo '[ERREUR] RES ' . $r['id'] . ': ID du client pour l\'emplacement invalide (Equipement ' . $equipment->id . ') <br/>';
                    }
                }
            }
        }
    }
}

echo '</body></html>';

//llxFooter();

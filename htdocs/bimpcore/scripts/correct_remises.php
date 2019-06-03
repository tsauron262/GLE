<?php

define('NOLOGIN', '1');

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(7200);

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);

$rows = $bdb->getRows('object_line_remise', 1, null, 'array');

if (!is_null($rows) && count($rows)) {
    foreach ($rows as $r) {
        switch ($r['object_type']) {
            case 'sav_propal':
                $module = 'bimpsupport';
                $object_name = 'BS_SavPropalLine';
                break;

            case 'propal':
                $module = 'bimpcommercial';
                $object_name = 'Bimp_PropalLine';
                break;

            default:
                continue;
        }
        $object_line = BimpObject::getInstance($module, $object_name, (int) $r['id_object_line']);
        if ($object_line->isLoaded()) {
            $object_line->calcRemise();
        }
    }
}

echo '</body></html>';

//llxFooter();
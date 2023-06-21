<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

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

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/BF_Lib.php';
BimpObject::loadClass('bimpfinancement', 'BF_Refinanceur');
BimpObject::loadClass('bimpfinancement', 'BF_Demande');

$err = array();
$montant_materiels = 19536.44;
$montant_services = 0;
//$tx = 7.4;
$nb_mois = 36;
$marge = 10;
$vr_achat = 5;
$mode_calcul = 1;
$periodicity = 3;

$tx_cession = BF_Refinanceur::getTauxMoyen($montant_materiels);
$marge = BF_Demande::getDefaultMargePercent($montant_materiels);

echo 'tx: ' . $tx_cession . '<br/>';
echo 'marge : ' . $marge . '<br/>';

$values = BFTools::getCalcValues($montant_materiels, $montant_services, $tx_cession, $nb_mois, $marge / 100, $vr_achat, $mode_calcul, $periodicity, $err);

echo '<pre>';
print_r($err);
echo '</pre>';
echo '<pre>';
print_r($values);
exit;

//$data = json_decode(file_get_contents(__DIR__ . '/test.json'), 1);
//
//global $db;
//$extrafields = new ExtraFields($db);
////$attrname, $label, $type, $pos, $size, $elementtype, $unique = 0, $required = 0, $default_value = '', $param = '', $alwayseditable = 0, $perms = '', $list = '-1', $help = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0, $printable = 0
//
//foreach ($data['data'] as $ef) {
//    echo 'ADD ' . $ef['label'] . ' (' . $ef['name'] . ' - ' . $ef['elementtype'] . ') : ';
//    $res = $extrafields->addExtraField($ef['name'], $ef['label'], $ef['type'], $ef['pos'], $ef['size'], $ef['elementtype'], $ef['fieldunique'], $ef['fieldrequired'], $ef['fielddefault'], $ef['param'], $ef['alwayseditable'], $ef['perms'], $ef['list'], $ef['help'], $ef['fieldcomputed'], $ef['entity'], $ef['langs'], $ef['enabled'], $ef['totalizable'], $ef['printable']);
//    if ($res > 0) {
//        echo '[OK]';
//    } else {
//        echo ' FAIL ' . $res;
//        echo '<br/>' . $extrafields->error .' - ' . $extrafields->errno;
//    }
//    echo '<br/>';
//}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();

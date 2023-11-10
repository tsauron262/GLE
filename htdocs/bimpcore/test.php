<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

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

$periodicity = 3;
$duration = 12;
$date_ouverture = '2023-01-01';
$date_fin = '2023-12-31';
$date_fac = '2023-08-24';
$date_first_period_start = '';
$date_first_period_end = '';
$prorata = 0;

echo 'Periodicité : ' . $periodicity . '<br/>';
echo 'Durée : ' . $duration . '<br/>';
echo 'Début ' . $date_ouverture . '<br/>';
echo 'Fin : ' . $date_fin . '<br/>';
echo 'début fac : ' . $date_fac . '<br/><br/>';

$diff_data = BimpTools::getDatesIntervalData($date_ouverture, $date_fac, true);

$nb_full_periods_since_ouv = floor($diff_data['nb_monthes_decimal'] / $periodicity);
$dt = new DateTime($date_ouverture);
$dt->add(new DateInterval('P' . ($nb_full_periods_since_ouv * $periodicity) . 'M'));
$date_first_period_start = $dt->format('Y-m-d');
$dt->add(new DateInterval('P' . $periodicity . 'M'));
$dt->sub(new DateInterval('P1D'));
$date_first_period_end = $dt->format('Y-m-d');

echo 'First period start : ' . $date_first_period_start . '<br/>';
echo 'First period end : ' . $date_first_period_end . '<br/>';

$diff = BimpTools::getDatesIntervalData($date_first_period_start, $date_first_period_end, true);
$nb_period_days = $diff['full_days'];

$diff = BimpTools::getDatesIntervalData($date_fac, $date_first_period_end, true);
$nb_invoiced_days = $diff['full_days'];

$prorata = $nb_invoiced_days / $nb_period_days;
echo 'prorata : ' . $prorata . ' (' . $nb_invoiced_days . ' / ' . $nb_period_days . ')<br/>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();

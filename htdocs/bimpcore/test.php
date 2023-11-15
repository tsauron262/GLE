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

$data = array(
    'nb_total_periods'        => 0, // Nombre total de périodes
    'nb_periods_tobill_max'   => 0, // Nombre total de périodes restant à facturer. 
    'nb_periods_tobill_today' => 0, // Nombre de périodes à facturer à date.
);

$periodicity = 1;
$duration = 12;
$date_ouverture = '2023-01-01';
$date_fin = '2023-12-31';
$date_next_facture = '2023-09-24';

echo 'Periodicité : ' . $periodicity . '<br/>';
echo 'Durée : ' . $duration . '<br/>';
echo 'Début ' . $date_ouverture . '<br/>';
echo 'Fin : ' . $date_fin . '<br/>';
echo 'Proch fac : ' . $date_next_facture . '<br/><br/>';

$date_now = date('Y-m-d');
$data['nb_total_periods'] = ceil($duration / $periodicity);

$dt_start = new DateTime(date('Y-m-d', strtotime($date_ouverture)));
$dt_end = new DateTime($dt_start->format('Y-m-d'));
$dt_end->add(new DateInterval('P' . $duration . 'M'));
$dt_now = new DateTime($date_now);
$dt_next_fac = new DateTime(date('Y-m-d', strtotime($date_next_facture)));

echo 'CALC MAX PERIODS : <br/>';
// Calcul du nombre de périodes restant à facturer
$interval = BimpTools::getDatesIntervalData($date_next_facture, $date_fin, true);
$nb_month = $interval['full_monthes']; // Nombre de mois complets
if ($interval['remain_days'] > 0) {
    $nb_month++;
}

echo 'nb perdiods ' . $nb_month / $periodicity . '<br/>';

if ($nb_month > 0) {
    $data['nb_periods_tobill_max'] = ceil($nb_month / $periodicity);

    if ($data['nb_periods_tobill_max'] < 0) {
        $data['nb_periods_tobill_max'] = 0;
    }

    if ($data['nb_periods_tobill_max'] > $data['nb_total_periods']) {
        $data['nb_periods_tobill_max'] = $data['nb_total_periods'];
    }
}


echo '<br/><br/>CALC PERIODS TO BILL TODAY: <br/>';
// Calcul du nombre de périodes à facturer aujourd'hui : 
if ($date_now == $date_next_facture) {
    $data['nb_periods_tobill_today'] = 1;
} elseif ($date_now > $date_next_facture) {
    $interval = BimpTools::getDatesIntervalData($date_next_facture, $date_now, true);
    $nb_month = $interval['full_monthes']; // Nombre de mois complets
    if ($interval['remain_days'] > 0) {
        $nb_month++;
    }
    
    echo 'nb perdiods ' . $nb_month / $periodicity. '<br/>';
    
    if ($nb_month > 0) {
        $data['nb_periods_tobill_today'] = ceil($nb_month / $periodicity);

        if ($data['nb_periods_tobill_today'] < 0) {
            $data['nb_periods_tobill_today'] = 0;
        }

        if ($data['nb_periods_tobill_today'] > $data['nb_total_periods']) {
            $data['nb_periods_tobill_today'] = $data['nb_total_periods'];
        }
    }
}

if ($data['nb_periods_tobill_max'] < $data['nb_periods_tobill_today']) {
    $data['nb_periods_tobill_max'] = $data['nb_periods_tobill_today']; // Par précaution
}

echo 'DATA<pre>';
print_r($data);
exit;

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();

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
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

$dfs = BimpCache::getBimpObjectObjects('bimpfinancement', 'BF_Demande', array('date_loyer' => 'IS_NOT_NULL', 'duration' => array('operator' => '>', 'value' => 0)));

foreach ($dfs as $df) {
    echo 'DF #' . $df->id;

    $date_loyer = $df->getData('date_loyer');
    $duration = (int) $df->getData('duration');
    if ($date_loyer && $duration) {
        $dt = new DateTime($date_loyer);
        $dt->add(new DateInterval('P' . $duration . 'M'));
        $dt->sub(new DateInterval('P1D'));
        $err = $df->updateField('date_fin', $dt->format('Y-m-d'));
        
        if (count($err)) {
            echo '<pre>';
            print_r($err);
            echo '</pre>';
        } else {
            echo 'OK ' . $dt->format('d / m / Y');
        }
        echo '<br/>';
        break;
    }
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();

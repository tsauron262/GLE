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

$refs = array();

$w = array();
foreach ($refs as $ref) {
    echo '<br/>' . $ref . ' : ';

    $p = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                'ref' => $ref
    ));

    if (!BimpObject::objectLoaded($p)) {
        echo '<span class="danger">';
        echo 'NON TROUVE';
        echo '</span>';
        continue;
    }

    echo $p->getLink() . ' - ';
    
    $ref = str_replace('SERV-', 'SERVEDUC-', $ref);
    
    $p->set('ref', $ref);

//    $p->set('tosell', 0);
//    $p->set('tobuy', 0);

    $err = $p->update($w, true);

    if (!empty($err)) {
        echo BimpRender::renderAlerts($err);
    } else {
        echo 'OK';
    }

//    break;
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();

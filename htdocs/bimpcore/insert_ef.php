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

$data = json_decode(file_get_contents(__DIR__ . '/test.json'), 1);

echo 'DATA<pre>';
print_r($data);
echo '</pre>';

global $db;
$extrafields = new ExtraFields($db);
//$attrname, $label, $type, $pos, $size, $elementtype, $unique = 0, $required = 0, $default_value = '', $param = '', $alwayseditable = 0, $perms = '', $list = '-1', $help = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0, $printable = 0

foreach ($data['data'] as $ef) {
    echo 'ADD ' . $ef['label'] . ' (' . $ef['name'] . ' - ' . $ef['elementtype'] . ') : ';
    $res = $extrafields->addExtraField($ef['name'], $ef['label'], $ef['type'], $ef['pos'], $ef['size'], $ef['elementtype'], $ef['fieldunique'], $ef['fieldrequired'], $ef['fielddefault'], $ef['param'], $ef['alwayseditable'], $ef['perms'], $ef['list'], $ef['help'], $ef['fieldcomputed'], $ef['entity'], $ef['langs'], $ef['enabled'], $ef['totalizable'], $ef['printable']);
    if ($res > 0) {
        echo '[OK]';
    } else {
        echo ' FAIL ' . $res;
        echo '<br/>' . $extrafields->error .' - ' . $extrafields->errno;
    }
    echo '<br/>';
}

echo '<br/>FIN';
echo '</body></html>';
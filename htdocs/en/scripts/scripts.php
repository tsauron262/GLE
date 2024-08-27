<?php

require_once("../../main.inc.php");

require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
ini_set('display_errors', 1);error_reporting(E_ALL);

ignore_user_abort(0);

top_htmlhead('', 'QUICK SCRIPTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$action = BimpTools::getValue('action', '');

if (!$action) {
    $actions = array(
        'createHisto'                       => 'Créer histo calc',
    );

    $path = pathinfo(__FILE__);

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/en/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }
    exit;
}

BimpCore::setMaxExecutionTime(2400);

switch ($action) {
    case 'createHisto':
        global $db;
        $warnings = array();
        $sql = $db->query("TRUNCATE TABLE `llx_en_historyArch2`;");
        $sql = $db->query("INSERT INTO llx_en_historyArch2 (SELECT cmd_id, DATE(datetime), value, id FROM llx_en_historyArch WHERE id IN (
SELECT MAX(a.id) FROM llx_en_historyArch a LEFT JOIN llx_en_cmd a___parent ON a___parent.id = a.cmd_id WHERE (a___parent.generic_type = 'CONSUMPTION') GROUP BY cmd_id, DATE(datetime)
));");
        
        $sql = $db->query("SELECT count(cmd_id) as nb, date FROM llx_en_historyArch2 GROUP BY date HAVING nb != 23 ORDER BY date DESC;");
        while ($ln = $db->fetch_object($sql)){
            $sql2 = $db->query("SELECT * FROM llx_en_cmd WHERE id NOT IN (SELECT cmd_id FROM llx_en_historyArch2 WHERE date = '".$ln->date."') AND generic_type = 'CONSUMPTION';");
            while($ln2 = $db->fetch_object($sql2)){
                $db->query("INSERT INTO llx_en_historyArch2 (cmd_id, date, value)  (SELECT ".$ln2->id.", '".$ln->date."', value FROM llx_en_historyArch2 WHERE cmd_id = ".$ln2->id." AND date < '".$ln->date."' ORDER BY date DESC LIMIT 1);");
            }
        }
        
        
        break;
    

    default:
        echo 'Action invalide';
        break;
}

echo '<br/>FIN';
echo '</body></html>';


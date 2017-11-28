<?php

if (!defined('BIMP_LIB')) {
    define('BIMP_LIB', 1);
    $dir = __DIR__ . '/classes/';

    require_once __DIR__ . '/libs/spyc/Spyc.php';

    require_once $dir . 'BimpDb.php';
    require_once $dir . 'BimpTools.php';
    require_once $dir . 'BimpConfig.php';
    require_once $dir . 'BimpInput.php';
    require_once $dir . 'BimpRender.php';
    require_once $dir . 'BimpCard.php';
    require_once $dir . 'BimpStruct.php';
    require_once $dir . 'BimpList.php';
    require_once $dir . 'BimpForm.php';
    require_once $dir . 'BimpView.php';
    require_once $dir . 'BimpViewsList.php';
    require_once $dir . 'BimpObject.php';
    require_once $dir . 'BimpController.php';

    checkBimpCoreVersion();
}

function checkBimpCoreVersion()
{
    global $db;
    $bdb = new BimpDb($db);
    $current_version = (float) $bdb->getValue('bimpcore_conf', 'value', '`name` = \'bimpcore_version\'');

    if (is_null($current_version) || !$current_version) {
        $current_version = 0.0;
    }

    $files = scandir(DOL_DOCUMENT_ROOT . '/bimpcore/updates');
    $updates = array();
    foreach ($files as $f) {
        if (in_array($f, array('.', '..'))) {
            continue;
        }

        if (preg_match('/^(\d+\.\d{1})\.sql$/', $f, $matches)) {
            if ((float) $matches[1] > (float) $current_version) {
                $updates[] = (float) $matches[1];
            }
        }
    }

    if (count($updates)) {
        if (!BimpTools::isSubmit('bimpcore_update_confirm')) {
            $url = $_SERVER['REQUEST_URI'];
            if (empty($_SERVER['QUERY_STRING'])) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= 'bimpcore_update_confirm=1';
            echo 'Le module BimpCore doit etre mis a jour<br/><br/>';
            echo '<button type="button" onclick="window.location = \'' . $url . '\'">OK</button>';
            exit;
        } else {
            sort($updates);
            foreach ($updates as $version) {
                echo 'Mise a jour du module bimpcore a la version ' . $version;
                if ($bdb->executeFile(DOL_DOCUMENT_ROOT . '/bimpcore/updates/' . $version . '.sql')) {
                    echo '[OK]<br/>';
                    $bdb->update('bimpcore_conf', array(
                        'value' => $version
                            ), '`name` = \'bimpcore_version\'');
                } else {
                    echo '[ECHEC] - ' . $db->error() . '<br/>';
                }
            }
            $url = str_replace('bimpcore_update_confirm=1', '', $_SERVER['REQUEST_URI']);
            echo '<br/><button type="button" onclick="window.location = \'' . $url . '\'">OK</button>';
            exit;
        }
    }
}

<?php

if (!defined('BIMP_LIB')) {
    define('BIMP_LIB', 1);

    global $bimp_start_time;
    $bimp_start_time = microtime(1);

    if (defined('MOD_DEV')) {
        ini_set('display_errors', 1);
    }

//    set_time_limit(3);

    $dir = __DIR__ . '/classes/';

    require_once __DIR__ . '/libs/spyc/Spyc.php';

    require_once $dir . 'BimpLog.php';
    require_once $dir . 'BimpDb.php';
    require_once $dir . 'BimpValidate.php';
    require_once $dir . 'BimpCache.php';
    require_once $dir . 'BimpTools.php';
    require_once $dir . 'BimpConfig.php';
    require_once $dir . 'BimpDebug.php';
    require_once $dir . 'BimpInput.php';
    require_once $dir . 'BimpRender.php';
    require_once $dir . 'BimpConfigDefinitions.php';
    require_once $dir . 'BimpComponent.php';

    require_once $dir . 'components/BC_Field.php';
    require_once $dir . 'components/BC_Input.php';
    require_once $dir . 'components/BC_Display.php';
    require_once $dir . 'components/BC_Search.php';
    require_once $dir . 'components/BC_Card.php';
    require_once $dir . 'components/BC_Panel.php';
    require_once $dir . 'components/BC_Filter.php';
    require_once $dir . 'components/BC_FiltersPanel.php';
    require_once $dir . 'components/BC_List.php';
    require_once $dir . 'components/BC_ListTable.php';
    require_once $dir . 'components/BC_ListViews.php';
    require_once $dir . 'components/BC_ListCustom.php';
    require_once $dir . 'components/BC_FieldsTable.php';
    require_once $dir . 'components/BC_Form.php';
    require_once $dir . 'components/BC_View.php';
    require_once $dir . 'components/BC_Input.php';
    require_once $dir . 'components/BC_Page.php';

    require_once $dir . 'BimpStruct.php';
    require_once $dir . 'BimpAssociation.php';
    require_once $dir . 'BimpObject.php';
    require_once $dir . 'BimpStats.php';
    require_once $dir . 'BimpCore.php';
    require_once $dir . 'FixeTabs.php';
    require_once $dir . 'BimpController.php';
}

function checkBimpCoreVersion()
{
    global $db;
    $bdb = new BimpDb($db);

    $dir = DOL_DOCUMENT_ROOT . '/bimpcore/updates';
    $updates = array();
    foreach (scandir($dir) as $subDir) {
        if (in_array($subDir, array('.', '..'))) {
            continue;
        }

        if (preg_match('/^[a-z]+$/', $subDir) && is_dir($dir . '/' . $subDir)) {
            $current_version = (float) BimpCore::getVersion($subDir);
            foreach (scandir($dir . '/' . $subDir) as $f) {
                if (in_array($f, array('.', '..'))) {
                    continue;
                }
                if (preg_match('/^(\d+(\.\d{1})*)\.sql$/', $f, $matches)) {
                    if ((float) $matches[1] > (float) $current_version) {
                        if (!isset($updates[$subDir])) {
                            $updates[$subDir] = array();
                        }
                        $updates[$subDir][] = (float) $matches[1];
                    }
                }
            }
            if (isset($updates[$subDir])) {
                sort($updates[$subDir]);
            }
        }
    }

    if (!empty($updates)) {
        if (!BimpTools::isSubmit('bimpcore_update_confirm')) {
            $url = $_SERVER['REQUEST_URI'];
            if (empty($_SERVER['QUERY_STRING'])) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= 'bimpcore_update_confirm=1';
            echo 'Le module BimpCore doit etre mis a jour<br/><br/>';
            echo 'Liste des mise à jour: ';
            echo '<pre>';
            print_r($updates);
            echo '</pre>';
            echo '<button type="button" onclick="window.location = \'' . $url . '\'">OK</button>';
            exit;
        } else {
            foreach ($updates as $dev => $dev_updates) {
                sort($dev_updates);
                $new_version = 0;
                $dev_dir = DOL_DOCUMENT_ROOT . '/bimpcore/updates/' . $dev . '/';
                foreach ($dev_updates as $version) {
                    $new_version = $version;
                    $version = (string) $version;
                    if (!file_exists($dev_dir . $version . '.sql') && preg_match('/^[0-9]+$/', $version)) {
                        if (file_exists($dev_dir . $version . '.0.sql')) {
                            $version .= '.0';
                        }
                    }
                    if (!file_exists($dev_dir . $version . '.sql')) {
                        echo 'FICHIER ABSENT: ' . $dev_dir . $version . '.sql <br/>';
                        continue;
                    }
                    echo 'Mise a jour du module bimpcore a la version: ' . $dev . '/' . $version;
                    if ($bdb->executeFile($dev_dir . $version . '.sql')) {
                        echo ' [OK]<br/>';
                        $bdb->update('bimpcore_conf', array(
                            'value' => $version
                                ), '`name` = \'bimpcore_version\'');
                    } else {
                        echo ' [ECHEC] - ' . $db->error() . '<br/>';
                    }
                }
                echo '<br/>';

                BimpCore::setVersion($dev, $new_version);
            }
            $url = str_replace('bimpcore_update_confirm=1', '', $_SERVER['REQUEST_URI']);
            echo '<br/><button type="button" onclick="window.location = \'' . $url . '\'">OK</button>';
            exit;
        }
    }
}

<?php

define('NOLOGIN', 1);

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';

global $db, $user;

$user = new User($db);
$user->fetch(1);

$id_cron = (int) BimpTools::getValue('id_cron', 0);
$debug = (int) BimpTools::getValue('debug', 0);

if ($debug) {
    ignore_user_abort(0);
    top_htmlhead('', 'BDS - CRON #' . $id_cron, 0, 0, array(), array());

    echo '<body>';
    BimpCore::displayHeaderFiles();

    echo '<div style="padding: 20px">';
}

if (!$id_cron) {
    $errors[] = 'ID de la tâche cron absent';
} else {
    $cron = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessCron', $id_cron);

    if (!BimpObject::objectLoaded($cron)) {
        $errors[] = 'La tâche cron #' . $id_cron . ' n\'existe pas';
    } else {
        $cron_errors = array();

        $result = $cron->executeOperation($cron_errors, $debug);

        if ($debug) {
            if (count($cron_errors)) {
                echo BimpRender::renderAlerts($cron_errors);
            }

            if (isset($result['debug_content'])) {
                echo $result['debug_content'];
            }

            if (isset($result['id_report'])) {
                echo '<h3>Rapports</h3>';
                $report = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Report', (int) $result['id_report']);

                if (!BimpObject::objectLoaded($report)) {
                    echo BimpRender::renderAlerts('Le rapport #' . $result['id_report']) . ' n\'existe pas';
                } else {
                    echo $report->renderChildrenList('objects_data', 'report', 1, 'Infos objets traités', 'fas_chart-bar');
                    echo $report->renderChildrenList('lines', 'report', 1, 'Notifications', 'fas_comment');
                }
            }
        } else {
            $errors = array_merge($errors, $cron_errors);
        }
    }
}

if ($debug) {
    if (count($errors)) {
        echo BimpRender::renderAlerts($errors);
    }

    echo '<br/>FIN';
    echo '</div>';
    echo '</body></html>';
} elseif (count($errors)) {
    $msg = '[ERREURS BDS CRON] - ';
    if ($id_cron) {
        $msg .= 'CRON #' . $id_cron . ' - ';
    }
    $msg .= BimpTools::getMsgFromArray($errors);
    dol_syslog($msg, LOG_ERR);
}


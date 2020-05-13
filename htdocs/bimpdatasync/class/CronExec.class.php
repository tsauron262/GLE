<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';

class CronExec
{

    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function executeProcessOperation($id_process_cron)
    {
        $error = '';

        if (!(int) $id_process_cron) {
            $error = 'ID de la tâche planifiée absent';
        } else {
            $cron = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessCron', (int) $id_process_cron);

            if (BimpObject::objectLoaded($cron)) {
                $cron_errors = array();
                $cron->executeOperation($cron_errors);

                if (count($cron_errors)) {
                    $error = BimpTools::getMsgFromArray($cron_errors);
                }
            } else {
                $error = 'La tâche planifiée #' . $id_process_cron . ' n\'existe plus';
            }
        }

        if ($error) {
            $msg = 'BimpDataSync: Erreurs lors de l\'exécution de la tâche planifiée #' . $id_process_cron . ' - ' . $error;
            dol_syslog($msg, 3);
            return 'KO';
        } else {
            return 'OK';
        }
    }

    public function cleanReports()
    {
        BimpObject::loadClass('bimpdatasync', 'BDS_Report');

        $errors = array();

        $n = BDS_Report::cleanReports($errors);

        return 'OK: ' . $n . ' - KO: ' . count($errors);
    }
}

<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class CronExec extends BimpCron
{

    public function __construct($db)
    {
        $this->db = $db;

        parent::__construct($db);
    }

    public function executeProcessOperation($id_process_cron)
    {
        $error = '';

        $this->current_cron_name = 'Opération BDS';

        if (!(int) $id_process_cron) {
            $error = 'ID de la tâche planifiée absent';
        } else {
            $this->current_cron_name .= ' - CRON #' . $id_process_cron;

            $cron = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessCron', (int) $id_process_cron);

            if (BimpObject::objectLoaded($cron)) {
                $this->current_cron_name .= ' - ' . $cron->getData('title');

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
            $log_data = array(
                'Erreur' => $error
            );

            $process = null;
            if (BimpObject::objectLoaded($cron)) {
                $log_data['Tâche'] = '#' . $cron->id . ' - ' . $cron->getData('title');

                $process = $cron->getParentInstance();
                if (BimpObject::objectLoaded($process)) {
                    $log_data['Processus'] = '#' . $process->id . ' - ' . $process->getData('title');

                    $operation = $cron->getChildObject('operation');

                    if (BimpObject::objectLoaded($operation)) {
                        $log_data['Opération'] = '#' . $operation->id . ' - ' . $operation->getData('title');
                    }
                } else {
                    $process = null;
                }
            }

            BimpCore::addlog('BDS: Erreur exécution tâche CRON', Bimp_Log::BIMP_LOG_ERREUR, 'bds', $process, $log_data);
            return 'KO';
        } else {
            return 'OK';
        }
    }

    public function cleanReports()
    {
        $this->current_cron_name = 'Nettoyage des rapports BDS';

        BimpObject::loadClass('bimpdatasync', 'BDS_Report');

        $errors = array();

        $n = BDS_Report::cleanReports($errors);

        return 'OK: ' . $n . ' - KO: ' . count($errors);
    }
}

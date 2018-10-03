<?php

require_once __DIR__ . '/../BDS_Lib.php';

class CronExec
{

    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function execute($id_process_cron)
    {
        $error = '';
        $processCron = new BDSProcessCron();
        if ($processCron->fetch($id_process_cron)) {
            if (isset($processCron->id_process) && $processCron->id_process) {
                $user = new User($this->db);
                $user->fetch(1);
                $options = $processCron->getOptionsData();
                $process = BDS_Process::createProcessById($user, $processCron->id_process, $error, $options);
                if ($error) {
                    $error = ' - ' . $error;
                } elseif (is_null($process)) {
                    $error .= ' - Echec du chargement du processus';
                } else {
                    $process->executeCronProcess($processCron->id_operation);
                }
            } else {
                $error .= ' - ID du processus absent';
            }
        } else {
            $error .= ' - Aucun enregistrement trouvé pour l\'ID ' . $id_process_cron;
        }

        if ($error) {
            $msg = 'BimpDataSync: Echec de l\'exécution d\'une tâche planifiée' . $error;
            dol_syslog($msg, 3);
        }
    }
}

<?php

require_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';

class BDS_ProcessCron extends BimpObject
{

    public static $periods = array(
        'min'  => 'Minute',
        'hour' => 'Heure',
        'day'  => 'Jour',
        'week' => 'Semaine'
    );
    public static $frequency_units = array(
        'min'  => 60,
        'hour' => 3600,
        'day'  => 86400,
        'week' => 604800
    );

    // Droits user: 

    public function canExecute()
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        return 0;
    }

    // Getters params: 

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $cronOption = BimpObject::getInstance('bimpdatasync', 'BDS_ProcessCronOption');
            $buttons[] = array(
                'label'   => 'Options',
                'icon'    => 'far_check-square',
                'onclick' => $cronOption->getJsLoadModalList('default', array(
                    'extra_filters' => array(
                        'id_cron' => (int) $this->id
                    )
                ))
            );

            if ($this->canExecute()) {
                $url = DOL_URL_ROOT . '/bimpdatasync/cron.php?id_cron=' . $this->id . '&debug=1';
                $buttons[] = array(
                    'label'   => 'Exécuter',
                    'icon'    => 'fas_cogs',
                    'onclick' => 'window.open(\'' . $url . '\')'
                );
            }
        }

        return $buttons;
    }

    // Getters array: 

    public function getOperationsArray($include_empty = false)
    {
        $process = $this->getParentInstance();

        if (BimpObject::objectLoaded($process)) {
            return $process->getChildrenListArray('operations', array(), $include_empty);
        }

        return array();
    }

    // Getters données: 

    public function getOptions($with_default = false)
    {
        $options = array();

        if ($this->isLoaded()) {
            $operation = $this->getChildObject('operation');

            $cronOptions = array();

            foreach ($this->getChildrenObjects('cron_options') as $opt) {
                $cronOptions[$opt->getData('id_option')] = $opt->getData('value');
            }

            if (BimpObject::objectLoaded($operation)) {
                $opOptions = $operation->getAssociatesObjects('options');

                foreach ($opOptions as $opt) {
                    if (isset($cronOptions[(int) $opt->id])) {
                        $options[$opt->getData('name')] = $cronOptions[(int) $opt->id];
                    } elseif ($with_default) {
                        $options[$opt->getData('name')] = (string) $opt->getData('default_value');
                    }
                }
            }
        }

        return $options;
    }

    // Affichages: 

    public function displayFrequency()
    {
        return $this->getData('freq_val') . ' ' . $this->displayData('freq_type') . '(s)';
    }

    // Traitements: 

    protected function saveCronJob()
    {
        $errors = array();
        $cronJob = new Cronjob($this->db->db);

        if ((int) $this->getData('id_cronjob')) {
            if ($cronJob->fetch((int) $this->getData('id_cronjob')) <= 0) {
                unset($cronJob);
                $cronJob = new Cronjob($this->db->db);
                $this->updateField('id_cronjob', 0);
            }
        }

        if (!count($errors)) {
            $processLabel = $this->db->getValue('bds_process', 'title', '`id` = ' . (int) $this->getData('id_process'));
            $operationLabel = $this->db->getValue('bds_process_operation', 'title', '`id` = ' . (int) $this->getData('id_operation'));

            $cronJob->jobtype = 'method';
            $cronJob->label = 'BimpDataSync: ' . $this->getData('title');
            $cronJob->command = '';
            $cronJob->priority = 0;
            $cronJob->classesname = 'bimpdatasync/class/CronExec.class.php';
            $cronJob->objectname = 'CronExec';
            $cronJob->methodename = 'executeProcessOperation';
            $cronJob->params = $this->id;
            $cronJob->md5params = '';
            $cronJob->module_name = 'bimpdatasync';
            $cronJob->note = 'Processus: "' . $processLabel . '", opération: "' . $operationLabel . '".<br/><br/>';
            $cronJob->note .= $this->getData('description');

            $cronJob->datestart = $this->db->db->jdate($this->getData('start'));
            $cronJob->dateend = '';

            if ($this->getData('active')) {
                $cronJob->status = 1;
            } else {
                $cronJob->status = 0;
            }

            $cronJob->datenextrun = '';
            $cronJob->unitfrequency = self::$frequency_units[$this->getData('freq_type')];
            $cronJob->frequency = (int) $this->getData('freq_val');
            $cronJob->maxrun = '';

            global $user;
            if (BimpObject::objectLoaded($cronJob)) {
                if ($cronJob->update($user) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($cronJob), 'Echec de la mise à jour du travail planifié #' . $cronJob->id);
                }
            } else {
                $id_cronjob = $cronJob->create($user);
                if ($id_cronjob <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($cronJob), 'Echec de la création du travail planifié');
                } else {
                    $this->updateField('id_cronjob', $id_cronjob);
                }
            }

            if (!count($errors)) {
                if ($cronJob->reprogram_jobs($user->login, dol_now()) <= 0) {
                    $errors[] = 'Echec de l\'initialisation de la prochaine exécution - ' . $cronJob->error;
                }
            }
        }
        return $errors;
    }

    public function executeOperation(&$errors = array(), $debug = false)
    {
        $result = array();

        if ($this->isLoaded($errors)) {
            if ((int) $this->getData('active') || $debug) {
                $id_process = (int) $this->getData('id_process');
                $id_operation = (int) $this->getData('id_operation');

                if (!$id_process) {
                    $errors[] = 'ID du processus absent';
                }

                if (!$id_operation) {
                    $errors[] = 'ID de l\'opération absent';
                }

                if (!count($errors)) {
                    $options = $this->getOptions(true);
                    $options['debug'] = $debug;
                    $options['mode'] = 'cron';

                    $process = BDSProcess::createProcessById($id_process, $errors, $options);

                    if (!is_null($process)) {
                        $result = $process->executeFullOperation($id_operation, $errors);
                    }
                }
            }
        }

        return $result;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $operation = $this->getChildObject('operation');

            if (BimpObject::objectLoaded($operation)) {
                $options = $operation->getAssociatesObjects('options');

                foreach ($options as $option) {
                    $opt_errors = array();
                    BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessCronOption', array(
                        'id_cron'   => (int) $this->id,
                        'id_option' => (int) $option->id,
                        'value'     => $option->getData('default_value')
                            ), true, $opt_errors);

                    if (count($opt_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($opt_errors, 'Echec de la création de l\'option "' . $option->getData('label') . '"');
                    }
                }
            }

            $warnings = BimpTools::merge_array($warnings, $this->saveCronJob());
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $warnings = BimpTools::merge_array($warnings, $this->saveCronJob());
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id_cronjob = (int) $this->getData('id_cronjob');

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            if ($id_cronjob) {
                global $user;
                $cronJob = new Cronjob($this->db->db);
                $cronJob->fetch($id_cronjob);

                if (BimpObject::objectLoaded($cronJob)) {
                    if ($cronJob->delete($user) <= 0) {
                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($cronJob), 'Echec de la suppression du travail planifié associé (ID ' . $id_cronjob . ')');
                    }
                }
            }
        }

        return $errors;
    }
}

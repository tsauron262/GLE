<?php

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

        if ($user->id == 1) {
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
            return $process->getChildrenListArray('operations', $include_empty);
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

    public function executeOperation(&$errors = array(), $debug = false)
    {
        $result = array();

        if ($this->isLoaded($errors)) {
            if ((int) $this->getData('active')) {
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
        }

        return $errors;
    }
}

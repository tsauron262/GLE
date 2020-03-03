<?php

require_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';

class BDSProcessCron extends BDSObject
{

    public static $table = 'bds_process_cron';
    public static $parent_id_property = 'id_process';
    public $id_process;
    public $id_operation;
    public $id_cronjob;
    public $title;
    public $description;
    public $active;
    public $frequency_val;
    public $frequency_type;
    public $frequency_start;
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
    public static $labels = array(
        'name'      => 'tâche planifiée',
        'name_plur' => 'tâches planifiées',
        'isFemale'  => 1
    );
    public static $fields = array(
        'id_process'      => array(
            'label'    => 'ID du processus',
            'type'     => 'int',
            'required' => true
        ),
        'id_cronjob'      => array(
            'label'         => 'ID du travail planifié',
            'type'          => 'int',
            'input'         => 'hidden',
            'required'      => false,
            'default_value' => 0
        ),
        'title'           => array(
            'label'    => 'Nom',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'description'     => array(
            'label'    => 'Description',
            'type'     => 'string',
            'input'    => 'textarea',
            'required' => false
        ),
        'id_operation'    => array(
            'label'    => 'Opération',
            'type'     => 'int',
            'input'    => 'select',
            'options'  => 'operations',
            'required' => true
        ),
        'active'          => array(
            'label'         => 'Activée',
            'type'          => 'bool',
            'input'         => 'switch',
            'required'      => true,
            'default_value' => 0
        ),
        'frequency_val'   => array(
            'label'         => 'Fréquence d\'exécution (valeur)',
            'type'          => 'int',
            'input'         => 'text',
            'required'      => true,
            'default_value' => 1
        ),
        'frequency_type'  => array(
            'label'    => 'Fréquence d\'exécution (unité)',
            'type'     => 'string',
            'input'    => 'select',
            'options'  => 'periods',
            'required' => true
        ),
        'frequency_start' => array(
            'label'       => 'Date et heure de première exécution',
            'type'        => 'datetime',
            'input'       => 'datetime',
            'display_now' => true,
            'required'    => true,
        )
    );
    public static $list_params = array(
        'checkboxes'   => 1,
        'bulk_actions' => array(
            array(
                'label'     => 'Supprimer les tâches planifiées sélectionnées',
                'onclick'   => 'deleteSelectedObjects(\'BDSProcessCron\', $(this));',
                'btn_class' => 'butActionDelete deleteSelectedObjects'
            )
        ),
        'headers'      => array(
            array(
                'width' => 20,
                'label' => 'Nom',
            ),
            array(
                'width' => 35,
                'label' => 'Description',
            ),
            array(
                'width' => 10,
                'label' => 'Activée',
            ),
            array(
                'width' => 35
            )
        ),
        'cols'         => array(
            array(
                'name'      => 'title',
                'data_type' => 'string',
            ),
            array(
                'name'      => 'description',
                'data_type' => 'string',
            ),
            array(
                'name'      => 'active',
                'data_type' => 'bool',
            ),
        ),
        'edit_btn'     => 1,
        'delete_btn'   => 1
    );
    public static $objects = array(
        'options' => array(
            'class_name' => 'BDSProcessCronOption',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
    );

    public function create()
    {
        $errors = parent::create();
        if (count($errors)) {
            return $errors;
        }

        $errors = $this->saveCronJob();

        // Création des options: 
        $options = BDSProcessOperation::getOperationOptions((int) $this->id_operation);
        foreach ($options as $option) {
            $cronOption = new BDSProcessCronOption();
            $cronOption->id_option = (int) $option['id'];
            $cronOption->id_process_cron = (int) $this->id;
            $cronOption->use_def_val = 1;

            $errors = BimpTools::merge_array($errors, $cronOption->create());
            unset($cronOption);
        }

        if (count($errors)) {
            $this->delete();
        }
        return $errors;
    }

    public function update()
    {
        $initial_id_operation = (int) $this->db->getValue(self::$table, 'id_operation', '`id` = ' . (int) $this->id);
        $errors = parent::update();
        if (count($errors)) {
            return $errors;
        }

        $errors = $this->saveCronJob();

        if ($initial_id_operation !== (int) $this->id_operation) {
            BDSProcessCronOption::deleteByParent($this->db, $this->id);

            // Création des nouvelles options: 
            $options = BDSProcessOperation::getOperationOptions((int) $this->id_operation);
            foreach ($options as $option) {
                $cronOption = new BDSProcessCronOption();
                $cronOption->id_option = (int) $option['id'];
                $cronOption->id_process_cron = (int) $this->id;
                $cronOption->use_def_val = 1;

                $errors = BimpTools::merge_array($errors, $cronOption->create());
                unset($cronOption);
            }
        }

        return $errors;
    }

    public function delete()
    {
        $errors = parent::delete();
        if (isset($this->id_cronjob) && $this->id_cronjob) {
            $cronjob = new Cronjob($this->db->db);
            if ($cronjob->fetch($this->id_cronjob) > 0) {
                global $user;
                if (!$cronjob->delete($user)) {
                    $errors[] = 'Echec de la suppression du tavail planifié (ID ' . $this->id_cronjob . ')';
                }
            }
        }
        return $errors;
    }

    protected function saveCronJob()
    {
        $errors = array();
        $cronJob = new Cronjob($this->db->db);

        if (isset($this->id_cronjob) && $this->id_cronjob) {
            if ($cronJob->fetch($this->id_cronjob) <= 0) {
                $errors[] = 'Echec du chargement du travail planifié (ID ' . $this->id_cronjob . ')';
            }
        }

        if (!count($errors)) {
            $processLabel = $this->db->getValue(BDSProcess::$table, 'title', '`id` = ' . (int) $this->id_process);
            $operationLabel = $this->db->getValue(BDSProcessOperation::$table, 'title', '`id` = ' . (int) $this->id_operation);

            $cronJob->jobtype = 'method';
            $cronJob->label = $this->title;
            $cronJob->command = '';
            $cronJob->priority = 0;
            $cronJob->classesname = 'CronExec.class.php';
            $cronJob->objectname = 'CronExec';
            $cronJob->methodename = 'execute';
            $cronJob->params = $this->id;
            $cronJob->md5params = '';
            $cronJob->module_name = 'bimpdatasync';
            $cronJob->note = 'Processus: "' . $processLabel . '", opération: "' . $operationLabel . '".<br/><br/>';
            $cronJob->note .= $this->description;

            $cronJob->datestart = $this->db->db->jdate($this->frequency_start);
            $cronJob->dateend = '';

            if ($this->active) {
                $cronJob->dateend = '';
                $cronJob->status = 1;
            } else {
                $cronJob->status = 0;
            }

            $cronJob->datenextrun = '';
            $cronJob->unitfrequency = self::$frequency_units[$this->frequency_type];
            $cronJob->frequency = (int) $this->frequency_val;
            $cronJob->maxrun = '';

            global $user;
            if (isset($this->id_cronjob) && $this->id_cronjob) {
                if ($cronJob->update($user) <= 0) {
                    $errors[] = 'Echec de la mise à jour du travail planifié - ' . $cronJob->error;
                }
            } else {
                $id_cronjob = $cronJob->create($user);
                if ($id_cronjob <= 0) {
                    $errors[] = 'Echec de création du travail planifié - ' . $cronJob->error;
                } else {
                    if ($this->db->update(self::$table, array(
                                'id_cronjob' => (int) $id_cronjob
                                    ), '`id` = ' . (int) $this->id) <= 0) {
                        $errors[] = 'Echec de l\'enregistrement de l\'ID du travail planifié';
                    }
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

    public function getOptionsData()
    {
        $options = array();
        foreach (BDSProcessOperation::getOperationOptions($this->id_operation) as $option) {
            $options[$option['id']] = array(
                'name'          => $option['name'],
                'default_value' => isset($option['default_value']) && !is_null($option['default_value']) ? $option['default_value'] : '');
        }

        $cron_options = BDSProcessCronOption::getListData($this->db, $this->id);
        $return = array();

        foreach ($cron_options as $co) {
            if (array_key_exists($co['id_option'], $options)) {
                if ($option['use_def_val']) {
                    $value = $options[$co['id_option']]['default_value'];
                } else {
                    $value = $co['value'];
                }
                $return[$options[$co['id_option']]['name']] = $value;
            }
        }

        return $return;
    }

    public static function getClass()
    {
        return 'BDSProcessCron';
    }

    public static function getOperationsQueryArray($id_parent = null)
    {
        $operations = array();
        if (!is_null($id_parent)) {
            global $db;
            $bdb = new BDSDb($db);

            $rows = BDSProcessOperation::getListData($bdb, $id_parent);
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $operations[$r['id']] = $r['title'];
                }
            }
        }
        return $operations;
    }

    public static function getPeriodsQueryArray($id_parent = null)
    {
        return static::$periods;
    }

    public function renderEditForm($id_parent = null)
    {
        $html = parent::renderEditForm($id_parent);

        if (isset($this->id_cronjob) && $this->id_cronjob) {
            $html .= '<div class="buttonsContainer">';
            $html .= '<a class="butAction" href="' . DOL_URL_ROOT . '/cron/card.php?id=' . $this->id_cronjob . '"';
            $html .= ' target="_blank">Accéder au travail planifié associé (Cron Job)</a>';
            $html .= '</div>';
        }

        $html .= $this->renderObjectsList('options');
        return $html;
    }

    public static function checkAllOptions($id_process)
    {
        global $db;
        $bdb = new BDSDb($db);

        $crons = static::getListData($bdb, $id_process);


        foreach ($crons as $cron) {
            $options = $bdb->getValues('bds_process_operation_option', 'id_option', '`id_operation` = ' . (int) $cron['id_operation']);
            $cronOptions = $bdb->getValues(BDSProcessCronOption::$table, 'id_option', '`id_process_cron` = ' . (int) $cron['id']);

            // Recherche des options à supprimer
            $rows = $bdb->getRows(BDSProcessCronOption::$table, '`id_process_cron` = ' . (int) $cron['id']);
            echo $db->error();
            foreach ($rows as $r) {
                if (!in_array($r->id_option, $options)) {
                    $bdb->delete(BDSProcessCronOption::$table, '`id` = ' . (int) $r->id);
                }
            }
            foreach ($options as $id_option) {
                // Recherche des options à créer
                if (!in_array($id_option, $cronOptions)) {
                    $cronOption = new BDSProcessCronOption();
                    $cronOption->id_option = (int) $id_option;
                    $cronOption->id_process_cron = (int) $cron['id'];
                    $cronOption->use_def_val = 1;
                    $cronOption->create();
                    unset($cronOption);
                }
            }
        }
    }
}

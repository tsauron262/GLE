<?php

class BDSProcessCron extends BimpObject
{

    public static $table = 'bds_process_cron';
    public static $parent_id_property = 'id_process';
    public $id_process;
    public $id_operation;
    public $id_cronjob;
    public $title;
    public $description;
    public $active;
    public static $periods = array(
        'min'   => 'Minute',
        'day'   => 'Jour',
        'week'  => 'Semaine',
        'month' => 'Mois'
    );
    public static $labels = array(
        'name'      => 'tâche planifiée',
        'name_plur' => 'tâches planifiées',
        'isFemale'  => 1
    );
    public static $fields = array(
        'id_process'     => array(
            'label'    => 'ID du processus',
            'type'     => 'int',
            'required' => true
        ),
        'id_cronjob'     => array(
            'label'    => 'ID du travail planifié',
            'type'     => 'int',
            'input'    => 'hidden',
            'required' => false
        ),
        'title'          => array(
            'label'    => 'Nom',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'description'    => array(
            'label'    => 'Description',
            'type'     => 'string',
            'input'    => 'textarea',
            'required' => false
        ),
        'id_operation'   => array(
            'label'    => 'Opération',
            'type'     => 'int',
            'input'    => 'select',
            'options'  => 'operations',
            'required' => true
        ),
        'active'         => array(
            'label'         => 'Activée',
            'type'          => 'bool',
            'input'         => 'switch',
            'required'      => true,
            'default_value' => 0
        ),
        'frequency_val'  => array(
            'label'         => 'Fréquence d\'exécution (valeur)',
            'type'          => 'int',
            'input'         => 'text',
            'required'      => true,
            'default_value' => 1
        ),
        'frequency_type' => array(
            'label'    => 'Fréquence d\'exécution (type)',
            'type'     => 'string',
            'input'    => 'select',
            'options'  => 'periods',
            'required' => true
        ),
        'frequency_start' => array(
            'label' => 'Date et heure de début d\'exécution',
            'type' => 'datetime',
            'input' => 'datetime',
            'required' => true,
            'default_value' => '0000-00-00 00:00'
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

    public static function getClass()
    {
        return 'BDSProcessCron';
    }

    public static function getOperationsQueryArray($id_parent = null)
    {
        $operations = array();
        if (!is_null($id_parent)) {
            global $db;
            $bdb = new BimpDb($db);

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
        $html .= $this->renderObjectsList('options');
        return $html;
    }
}

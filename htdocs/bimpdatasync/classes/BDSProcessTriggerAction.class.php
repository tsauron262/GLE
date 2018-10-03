<?php

class BDSProcessTriggerAction extends BDSObject
{

    public static $table = 'bds_process_trigger_action';
    public static $parent_id_property = 'id_process';
    public $id_process;
    public $action_name;
    public $active;
    public static $labels = array(
        'name'      => 'action sur trigger',
        'name_plur' => 'actions sur trigger',
        'isFemale'  => true
    );
    public static $fields = array(
        'id_process'  => array(
            'label'    => 'ID du processus',
            'required' => true,
        ),
        'action_name' => array(
            'label'    => 'Nom de l\'action',
            'required' => true
        ),
        'active'      => array(
            'label'    => 'Valeur',
            'required' => false
        )
    );
    public static $list_params = array(
        'title'           => 'Liste des actions sur trigger',
        'no_items'        => 'Aucune action enregistrée pour le moment',
        'checkboxes'      => 1,
        'bulk_actions'    => array(
            array(
                'label'     => 'Supprimer les actions sélectionnées',
                'onclick'   => 'deleteSelectedObjects(\'BDSProcessTriggerAction\', $(this));',
                'btn_class' => 'butActionDelete deleteSelectedObjects'
            )
        ),
        'headers'         => array(
            array(
                'width' => 20,
                'label' => 'Nom de l\'action',
                'input' => true
            ),
            array(
                'width' => 25,
                'label' => 'Activé',
                'input' => true
            ),
            array(
                'width' => 50
            )
        ),
        'cols'            => array(
            array(
                'name'  => 'action_name',
                'input' => 'text'
            ),
            array(
                'name'  => 'active',
                'input' => 'switch'
            ),
        ),
        'update_btn'      => 1,
        'delete_btn'      => 1,
        'row_form_inputs' => array(
            array(
                'type'          => 'text',
                'name'          => 'action_name',
                'id'            => 'action_name',
                'default_value' => ''
            ),
            array(
                'type'          => 'switch',
                'name'          => 'active',
                'id'            => 'active',
                'default_value' => '0'
            )
        )
    );

    public static function getClass()
    {
        return 'BDSProcessTriggerAction';
    }

    public static function getTriggerActionProcesses($action)
    {
        global $db, $user;
        $bdb = new BDSDb($db);

        $sql = 'SELECT p.id FROM ' . MAIN_DB_PREFIX . 'bds_process p ';
        $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . self::$table . ' a ON p.id = a.id_process ';
        $sql .= 'WHERE p.active = 1 AND a.active = 1';
        $sql .= ' AND a.action_name LIKE \'' . $db->escape($action) . '\'';

        $rows = $bdb->executeS($sql);
        unset($bdb);

        $processes = array();
        if (!is_null($rows)) {
            $error = '';
            foreach ($rows as $r) {
                $process = BDS_Process::createProcessById($user, $r->id, $error);
                if (!is_null($process)) {
                    $processes[] = $process;
                }
            }
        }

        return $processes;
    }
}

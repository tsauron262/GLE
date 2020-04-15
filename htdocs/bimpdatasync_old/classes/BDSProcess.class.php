<?php

include_once __DIR__ . '/BDSObject.php';

class BDSProcess extends BDSObject
{

    public static $table = 'bds_process';
    public $id;
    public $name;
    public $title;
    public $description;
    public $type;
    public $active;
    
    public static $types = array(
        'import' => 'Import',
        'export' => 'Export',
        'sync'   => 'Synchronisation',
        'ws'     => 'Web service'
    );
    
    public static $labels = array(
        'name'      => 'processus',
        'isFemale'  => false,
        'name_plur' => 'processus'
    );
    
    public static $fields = array(
        'name'        => array(
            'label'    => 'Nom système',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'title'       => array(
            'label'    => 'Nom public',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'description' => array(
            'label'    => 'Description',
            'type'     => 'string',
            'input'    => 'textarea',
            'required' => false
        ),
        'type'        => array(
            'label'        => 'Type',
            'input'        => 'select',
            'options'      => 'types',
            'is_key_array' => 'types',
            'required'     => true,
        ),
        'active'      => array(
            'label'         => 'Activé',
            'type'          => 'bool',
            'input'         => 'switch',
            'required'      => false,
            'default_value' => 0
        )
    );
    public static $objects = array(
        'parameters'     => array(
            'class_name' => 'BDSProcessParameter',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
        'options'        => array(
            'class_name' => 'BDSProcessOption',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
        'matching'       => array(
            'class_name' => 'BDSProcessMatchingValues',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
        'trigger_action' => array(
            'class_name' => 'BDSProcessTriggerAction',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
        'operations'     => array(
            'class_name' => 'BDSProcessOperation',
            'relation'   => 'HasMany',
            'delete'     => true
        ),
        'crons'     => array(
            'class_name' => 'BDSProcessCron',
            'relation'   => 'HasMany',
            'delete'     => true
        )
    );

//    public static $objects = array(
//        'options'        => array(
//            'table_suffixe' => 'option',
//            'label'         => 'Option',
//            'fields'        => array(
//            )
//        ),
//        'triggerActions' => array(
//            'table_suffixe' => 'trigger_action',
//            'label'         => 'Trigger',
//            'fields'        => array(
//            )
//        )
//    );

    public static function getClass()
    {
        return 'BDSProcess';
    }

    public static function getTypesQueryArray($id_parent = null)
    {
        return self::$types;
    }

    public static function getProcessesQuery()
    {
        global $db;
        $bdb = new BDSDb($db);

        $processes = array();
        $rows = $bdb->getRows(self::$table);
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $processes[$r->id] = array(
                    'id'   => $r->id,
                    'name' => $r->title
                );
            }
        }

        ksort($processes);
        return $processes;
    }

    public static function renderListRows($id_parent = null)
    {
        global $db;
        $bdb = new BDSDb($db);
        $processes = self::getListData($bdb);
        $html = '';
        if (count($processes)) {
            foreach ($processes as $process) {
                $html .= '<tr>';
                $html .= '<td width="5%" style="text-align: center"><strong>' . $process['id'] . '</<strong></td>';
                $html .= '<td width="25%">';
                $html .= '<a href="' . DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $process['id'] . '">';
                $html .= $process['title'] . '</a></td>';
                $html .= '<td width="55%">' . $process['description'] . '</td>';
                $html .= '<td width="5%" style="text-align: center">' . self::$types[$process['type']] . '</td>';
                $html .= '<td width="5%" style="text-align: center">';
                if ((int) $process['active']) {
                    $html .= '<span class="success">activé</span>';
                } else {
                    $html .= '<span class="danger">désactivé</span>';
                }
                $html .= '</td>';
                $html .= '<td width="5%">';
                $html .= '<a class="button" href="' . DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $process['id'] . '">Afficher</a>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>';
            $html .= '<td>';
            $html .= '<p class="alert alert-info">Aucun processus enregistré</p>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        return $html;
    }
}

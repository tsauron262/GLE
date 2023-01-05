<?php

class BDSReport extends BimpReport
{

    public $dir;
    public static $ref_syntaxe = '{date}-{time}_{id_process}_{type}';
    public static $types = array(
        'operations' => array(
            'name'      => 'Opération manuelle',
            'name_plur' => 'Opérations manuelles',
            'color'     => '#005DA0',
        ),
        'triggers'   => array(
            'name'      => 'Opération automatique',
            'name_plur' => 'Opérations automatiques',
            'color'     => '#C86400'
        ),
        'cron'       => array(
            'name'      => 'Tâche planifiée',
            'name_plur' => 'Tâches planifiées',
            'color'     => '#500050'
        ),
        'requests'   => array(
            'name'      => 'Requête entrante',
            'name_plur' => 'Requêtes entrantes',
            'color'     => '#A00000'
        )
    );
    public static $data_def = array(
        'title'        => 'Titre',
        'id_process'   => 'ID Processus',
        'id_operation' => 'ID Opération',
        'begin'        => 'Début',
        'end'          => 'Fin',
        'nbSuccess'    => 'Nombre de succès',
        'nbErrors'     => 'Nombre d\'erreurs',
        'nbWarnings'   => 'Nombre d\'alertes'
    );
    public static $rows_def = array(
        'row'  => array(
            'type'        => 'Statut',
            'time'        => 'Heure',
            'module'      => 'Module',
            'object_name' => 'Type d\'objet',
            'id_object'   => 'ID objet',
            'ref'         => 'Référence',
            'msg'         => 'Message'
        ),
        'elem' => array(
            'nbToProcess'   => 0,
            'nbProcessed'   => 0,
            'nbUpdated'     => 0,
            'nbCreated'     => 0,
            'nbDeleted'     => 0,
            'nbIgnored'     => 0,
            'nbActivated'   => 0,
            'nbDeactivated' => 0
        )
    );

    public function __construct($fileRef = '')
    {
        $this->data['id_process'] = 0;
        $this->data['id_operation'] = 0;

        parent::__construct($fileRef);
    }

    public function addElementTypeData($elementType)
    {
        if (!isset($this->rows['elem'])) {
            $this->rows['elem'] = array();
        }

        if (!isset($this->rows['elem'][$elementType])) {
            $this->rows['elem'][$elementType] = static::$rows_def['elem'];
        }
    }

    public function increaseElementTypeData($elementType, $dataName)
    {
        $this->addElementTypeData($elementType);

        if (isset($this->rows['elem'][$elementType][$dataName])) {
            $this->rows['elem'][$elementType][$dataName] ++;
        }
    }

    public function setElementTypeData($elementType, $dataName, $dataValue)
    {
        $this->addElementTypeData($elementType);

        if (isset($this->rows['elem'][$elementType][$dataName])) {
            $this->rows['elem'][$elementType][$dataName] = $dataValue;
        }
    }

    public static function getObjectNotifications($module, $object_name, $id_object, $params = array())
    {
        $dir = DOL_DATA_ROOT . '/' . static::$dirBase;

        if (isset($params['subDir']) && $params['subDir']) {
            $dir .= '/' . $params['subDir'];
        }

        if (!file_exists($dir)) {
            return array();
        }

        if (!(string) $module || !(string) $object_name || !(int) $id_object) {
            return array();
        }

        $data = array();

        $reports = scandir($dir);
        foreach ($reports as $report_file) {
            if (in_array($report_file, array('.', '..'))) {
                continue;
            }

            $report = new BDSReport($report_file);

            if (preg_match('/^((\d{8}\-\d{6}).*)\.csv$/', $report_file, $matches)) {
                $from = BimpTools::getArrayValueFromPath($params, 'from', '');
                $to = BimpTools::getArrayValueFromPath($params, 'to', '');


                if ($from || $to) {
                    $report_date = $report->getData('begin');

                    if ($from && $report_date < $from) {
                        continue;
                    }

                    if ($to && $report_date > $to) {
                        continue;
                    }
                }

                $type = $report->getData('type');

                if (isset($params['type']) && (string) $params['type'] && (string) $params['type'] !== $type) {
                    continue;
                }

                $id_process = (int) $report->getData('id_process');

                if (isset($params['id_process']) && (int) $params['id_process'] && (int) $params['id_process'] !== $id_process) {
                    continue;
                }

                $type_label = self::$types[$type]['name'];

                foreach ($report->rows['row'] as $r) {
                    if ($module !== $r['module']) {
                        continue;
                    }
                    if ($object_name !== $r['object_name']) {
                        continue;
                    }
                    if ((int) $id_object && (int) $id_object !== (int) $r['id_object']) {
                        continue;
                    }

                    if (!array_key_exists($report->file_ref, $data)) {
                        $data[$report->file_ref] = $report->data;
                        $data[$report->file_ref]['rows'] = array();
                    }

                    $data[$report->file_ref]['rows'][] = $r;
                }
            }
        }

        krsort($data);
        return $data;
    }

    public function getElementTypeInfos($type, $label, $is_female = false)
    {
        $infos = array();

        if (isset($this->rows['elem'][$type])) {
            foreach ($this->rows['elem'][$type] as $data) {
                foreach ($data as $dataName => $value) {
                    $name = '';
                    if ((int) $value > 0) {
                        switch ($dataName) {
                            case 'nbProcessed':
                                $name = 'Nombre ' . $label . ' traité' . ($is_female ? 'e' : '') . 's';
                                break;

                            case 'nbUpdated':
                                $name = 'Nombre ' . $label . ' mis' . ($is_female ? 'es' : '') . ' à jour';
                                break;

                            case 'nbCreated':
                                $name = 'Nombre ' . $label . ' créé' . ($is_female ? 'e' : '') . 's';
                                break;

                            case 'nbDeleted':
                                $name = 'Nombre ' . $label . ' supprimé' . ($is_female ? 'e' : '') . 's';
                                break;

                            case 'nbActivated':
                                $name = 'Nombre ' . $label . ' activé' . ($is_female ? 'e' : '') . 's';
                                break;

                            case 'nbDeactivated':
                                $name = 'Nombre ' . $label . ' désactivé' . ($is_female ? 'e' : '') . 's';
                                break;

                            case 'nbIgnored':
                                $name = 'Nombre ' . $label . ' ignoré' . ($is_female ? 'e' : '') . 's';
                                break;
                        }
                        if ($name) {
                            $infos[] = array(
                                'name'  => $name,
                                'value' => $value
                            );
                        }
                    }
                }
            }
        }

        return $infos;
    }
}

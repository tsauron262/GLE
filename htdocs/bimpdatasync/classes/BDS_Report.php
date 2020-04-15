<?php

//ini_set('display_errors', 1);

class BDS_Report
{

    public $dir;
    public static $refRegex = '/\d{8}\-\d{6}_\d+_(.+)/';
    public static $refDateFormat = 'Ymd';
    public static $refTimeFormat = 'His';
    public static $fullRefSyntax = '{date}-{time}_{id_process}_{type}';
    public static $deleteDelay = 'P1M';
    public static $messagesTypes = array('success, info, warning, danger');
    public static $OperationsTypes = array(
        'operations' => array(
            'name'      => 'Opération manuelle',
            'name_plur' => 'Opérations manuelles',
            'color' => '#005DA0',
        ),
        'actions'    => array(
            'name'      => 'Opération automatique',
            'name_plur' => 'Opérations automatiques',
            'color' => '#C86400'
        ),
        'cron'      => array(
            'name'      => 'Tâche planifiée',
            'name_plur' => 'Tâches planifiées',
            'color' => '#500050'
        ),
        'requests'   => array(
            'name'      => 'Requête entrante',
            'name_plur' => 'Requêtes entrantes',
            'color' => '#A00000'
        )
    );
    public static $row_def = array(
        'type'      => 'Statut',
        'time'      => 'Heure',
        'object'    => 'Objet BIMP-ERP',
        'id_object' => 'ID Objet BIMP-ERP',
        'reference' => 'Référence objet',
        'msg'       => 'Message'
    );
    public static $objects_data_def = array(
        'nbToProcess'   => 0,
        'nbProcessed'   => 0,
        'nbUpdated'     => 0,
        'nbCreated'     => 0,
        'nbDeleted'     => 0,
        'nbIgnored'     => 0,
        'nbActivated'   => 0,
        'nbDeactivated' => 0
    );
    public static $objectsLabels = array(
        'Product'   => 'produit{s}',
        'Categorie' => 'categorie{s}[F]',
        'Societe'   => 'client{s}',
        'Contact'   => 'contact{s}',
        'Commande'  => 'commande{s}[F]'
    );
    public $file_ref = 0;
    public $rows = array();
    protected $data = array(
        'title'        => '',
        'id_process'   => 0,
        'id_operation' => 0,
        'begin'        => '',
        'end'          => '',
        'nbErrors'     => 0,
        'nbAlerts'     => 0
    );
    protected $objects_data = array();

    public static function createReference($id_process, $type = null)
    {
        $DT = new DateTime();
        if (is_null($type)) {
            return $DT->format(self::$refDateFormat) . '-' . $DT->format(self::$refTimeFormat);
        }

        $ref = str_replace('{date}', $DT->format(self::$refDateFormat), self::$fullRefSyntax);
        $ref = str_replace('{time}', '000000', $ref);
        $ref = str_replace('{id_process}', $id_process, $ref);
        $ref = str_replace('{type}', $type, $ref);
        return $ref;
    }

    public function __construct($id_process = null, $title = null, $fileRef = null, $type = null)
    {
        $this->dir = DOL_DATA_ROOT . '/bimpdatasync/reports/';

        if (!file_exists($this->dir)) {
            mkdir($this->dir, 0777);
        }

        if (!is_null($id_process)) {
            $this->data['id_process'] = (int) $id_process;
        }
        if (!is_null($title)) {
            $this->data['title'] = $title;
        }

        if (is_null($fileRef) || !$fileRef) {
            $this->file_ref = self::createReference($id_process, $type);
            $this->data['begin'] = date('Y-m-d H:i:s');
            $this->data['end'] = '';
        } else {
            $this->file_ref = $fileRef;
            $this->loadFile();
        }
    }

    // Getters : 
    
    // Traitements: 
    
    // Méthodes statiques: 
        
    public function loadFile()
    {
        $fileName = $this->dir . $this->file_ref . '.csv';
        if (file_exists($fileName)) {
            $rows = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($rows as $idx => $r) {
                if ($idx === 0) {
                    $header_values = explode(';', $r);
                    $i = 0;
                    foreach ($this->data as $key => $value) {
                        $this->data[$key] = $header_values[$i];
                        $i++;
                    }
                } else {
                    $values = array();

                    $data = explode(';', $r);
                    switch ($data[0]) {
                        case '[OBJECT_DATA]':
                            $this->addObjectData($data[1]);
                            $i = 2;
                            foreach (self::$objects_data_def as $key => $value) {
                                $this->objects_data[$data[1]][$key] = $data[$i];
                                $i++;
                            }
                            break;

                        case '[ROW]':
                            $i = 1;
                            foreach (self::$row_def as $key => $name) {
                                $values[$key] = $data[$i];
                                $i++;
                            }
                            $this->rows[] = $values;
                            break;
                    }
                }
            }
        } else {
            $this->data['begin'] = date('Y-m-d H:i:s');
            $this->data['end'] = '';
        }
    }

    public function saveFile()
    {
        if (!file_exists($this->dir)) {
            return;
        }
        if ($this->data['end'] === '') {
            $this->end();
        }

        $txt = '';
        $firstLoop = true;
        foreach ($this->data as $key => $value) {
            if (!$firstLoop) {
                $txt .= ';';
            } else {
                $firstLoop = false;
            }
            $txt .= $value;
        }
        $txt .= "\n";

        foreach ($this->objects_data as $objectName => $data) {
            $txt .= '[OBJECT_DATA];' . $objectName;
            foreach ($data as $value) {
                $txt .= ';' . $value;
            }
            $txt .= "\n";
        }

        foreach ($this->rows as $row) {
            $line = '[ROW]';
            foreach ($row as $key => $value) {
                $line .= ';' . $value;
            }
            $txt .= $line . "\n";
        }
        file_put_contents($this->dir . $this->file_ref . '.csv', $txt);
    }

    public function addObjectData($objectName)
    {
        if (!array_key_exists($objectName, $this->objects_data)) {
            $this->objects_data[$objectName] = self::$objects_data_def;
        }
    }

    public function setData($dataName, $value)
    {
        if (array_key_exists($dataName, $this->data)) {
            $this->data[$dataName] = $value;
        } else {
            $msg = 'Erreur technique - donnée de rapport inéxistante: "' . $dataName . '"';
            $this->addRow('error', $msg, '');
        }
    }

    public function getData($dataName)
    {
        if (isset($this->data[$dataName])) {
            return $this->data[$dataName];
        }
        return '';
    }

    public function getObjectsData()
    {
        return $this->objects_data;
    }

    public function increaseObjectData($objectName, $dataName)
    {
        if (!array_key_exists($objectName, $this->objects_data)) {
            $this->addObjectData($objectName);
        }
        if (isset($this->objects_data[$objectName][$dataName])) {
            $this->objects_data[$objectName][$dataName] ++;
        }
    }

    public function setObjectDataValue($objectName, $dataName, $dataValue)
    {
        if (!array_key_exists($objectName, $this->objects_data)) {
            $this->addObjectData($objectName);
        }
        if (isset($this->objects_data[$objectName][$dataName])) {
            $this->objects_data[$objectName][$dataName] = $dataValue;
        }
    }

    public function addRow($type, $msg, $objectName = '', $id_object = '', $reference = '')
    {
        if ($type === 'error') {
            $type = 'danger';
        } elseif ($type === 'alert') {
            $type = 'warning';
        }

        if (is_null($objectName)) {
            $objectName = '';
        }
        if (is_null($id_object)) {
            $id_object = '';
        }
        if (is_null($reference)) {
            $reference = '';
        }

        if (is_array($msg)) {
            $msgs = $msg;
            $msg = '';
            $firstLoop = true;
            foreach ($msgs as $name => $value) {
                if (!$firstLoop) {
                    $msg .= ', ';
                } else {
                    $firstLoop = false;
                }
                $msg .= $name . ' => ' . $value;
            }
        } else {
            $msg = str_replace(';', ', ', $msg);
        }

        $this->rows[] = array(
            'type'      => $type,
            'time'      => date('H:i:s'),
            'object'    => $objectName,
            'id_object' => $id_object,
            'reference' => $reference,
            'msg'       => $msg
        );

        switch ($type) {
            case 'danger': $this->data['nbErrors'] ++;
                break;
            case 'warning': $this->data['nbAlerts'] ++;
                break;
        }

        $this->data['end'] = '';
    }

    public function end()
    {
        $this->data['end'] = date('d-m-Y H:i:s');
    }

    public static function getReportsList()
    {
        $dir = DOL_DATA_ROOT . '/bimpdatasync/reports/';
        if (!file_exists($dir)) {
            return array();
        }

        $files = scandir($dir);

        $reports = array();
        arsort($files);

        $DT = new DateTime();
        $DT->sub(new DateInterval(self::$deleteDelay));
        $deleteDate = $DT->format('Y-m-d');

        foreach ($files as $f) {
            if (in_array($f, array('', '.', '..'))) {
                continue;
            }
            $ref = str_replace('.csv', '', $f);
            $report = new BDS_Report(null, null, $ref);

            if (!count($report->rows)) {
                unlink($dir . $f);
                unset($report);
                continue;
            }

            $datetime = $report->getData('begin');
            if (!$datetime) {
                $datetime = date('Y-m-d H:i:s');
                $report->setData('begin', $datetime);
                $report->saveFile();
            }
            $date = new DateTime($datetime);

            if ($date->format('Y-m-d') <= $deleteDate) {
                unlink($dir . $f);
                unset($report);
                continue;
            }

            $nErrors = (int) $report->getData('nbErrors');
            $nAlerts = (int) $report->getData('nbAlerts');

            $name = 'Le ' . $date->format('d / m / Y') . ' à ' . $date->format('H:i:s') . ' - ' . $report->getData('title');

            if ($nErrors > 0) {
                $name .= ' (' . $nErrors . ' erreur' . ($nErrors > 1 ? 's' : '') . ')';
            }
            if ($nAlerts > 0) {
                $name .= ' (' . $nAlerts . ' alerte' . ($nAlerts > 1 ? 's' : '') . ')';
            }

            $type = 'operations';
            if (preg_match(self::$refRegex, $ref, $matches)) {
                $type = $matches[1];
            }

            $reports[] = array(
                'file'       => $f,
                'ref'        => $ref,
                'date'       => $date->format('Y-m-d'),
                'name'       => $name,
                'id_process' => $report->getData('id_process'),
                'type'       => $type,
                'nErrors'    => $nErrors,
                'nAlerts'    => $nAlerts
            );
        }

        return $reports;
    }

    public static function getObjectNotifications($objectName, $id_object, $from = null, $to = null, $reference = null, $id_process = null)
    {
        $dir = DOL_DATA_ROOT . '/bimpdatasync/reports/';
        if (!file_exists($dir)) {
            return array();
        }
        $data = array();
        if (is_null($id_object) && is_null($reference)) {
            return $data;
        }
        if (!preg_match('/^\d{8}\-\d{6}$/', $from)) {
            $from = null;
        }
        if (!preg_match('/^\d{8}\-\d{6}$/', $to)) {
            $to = null;
        }

        if (is_null($to)) {
            $to = date('Ymd-His');
        }

        $objectName = strtolower($objectName);

        $reports = scandir($dir);
        foreach ($reports as $report_file) {
            if (in_array($report_file, array('.', '..'))) {
                continue;
            }
            if (preg_match('/^((\d{8}\-\d{6}).*)\.csv$/', $report_file, $matches)) {
                if ($matches[2] > $to) {
                    continue;
                }
                if (!is_null($from) && ($matches[2] < $from)) {
                    continue;
                }
                $report = new BDS_Report(null, null, $matches[1]);

                $type = 'operations';
                if (preg_match(self::$refRegex, $report->file_ref, $matches2)) {
                    $type = $matches2[1];
                }

                $type_label = self::$OperationsTypes[$type]['name'];

                foreach ($report->rows as $r) {
                    if ($objectName !== strtolower($r['object'])) {
                        continue;
                    }
                    if (!is_null($id_process)) {
                        if ((int) $report->getData('id_process') !== (int) $id_process) {
                            continue;
                        }
                    }

                    if (($r['id_object'] && ($id_object == $r['id_object']))) {
                        if (!array_key_exists($report->file_ref, $data)) {
                            $data[$report->file_ref] = array();
                        }
                        $r['operation_type'] = $type;
                        $r['operation_type_label'] = $type_label;
                        $data[$report->file_ref][] = $r;
                    }
                }
            }
        }

        krsort($data);

        $return = array();
        foreach ($data as $file_ref => $rows) {
            if (preg_match('/^(\d{4})(\d{2})(\d{2})\-\d{6}.*$/', $file_ref, $matches)) {
                $date_str = $matches[3] . '/' . $matches[2] . '/' . $matches[1];
                foreach ($rows as $r) {
                    $row = $r;
                    $row['date'] = $date_str;
                    $row['file_ref'] = $file_ref;
                    $return[] = $row;
                }
            }
        }

        return $return;
    }

    public static function getReportsDetails($from, $to)
    {
        $dir = DOL_DATA_ROOT . '/bimpdatasync/reports/';
        if (!file_exists($dir)) {
            return array();
        }
        $data = array();

        if (!preg_match('/^\d{8}\-\d{6}$/', $from)) {
            $from = null;
        }
        if (!preg_match('/^\d{8}\-\d{6}$/', $to)) {
            $to = null;
        }

        if (is_null($to)) {
            $to = date('Ymd-His');
        }

        if (is_null($from)) {
            $from = date('Ymd-His');
        }

        $reports = scandir($dir);
        foreach ($reports as $report_file) {
            if (in_array($report_file, array('.', '..'))) {
                continue;
            }
            if (preg_match('/^((\d{8}\-\d{6}).*)\.csv$/', $report_file, $matches)) {
                if ($matches[2] > $to) {
                    continue;
                }
                if (!is_null($from) && ($matches[2] < $from)) {
                    continue;
                }
                $report = new BDS_Report(null, null, $matches[1]);

                $type = 'operations';
                if (preg_match(self::$refRegex, $report->file_ref, $matches2)) {
                    $type = $matches2[1];
                }

                $id_process = $report->getData(('id_process'));
                if (!isset($data[$id_process])) {
                    $data[$id_process] = array();
                }

                $data[$id_process][$report->getData('begin')] = array(
                    'report_ref'   => $report->file_ref,
                    'title'        => $report->getData('title'),
                    'type'         => $type,
                    'begin'        => $report->getData('begin'),
                    'end'          => $report->getData('end'),
                    'nbErrors'     => $report->getData('nbErrors'),
                    'nbAlerts'     => $report->getData('nbAlerts'),
                    'objectsInfos' => $report->getObjectsInfos()
                );
            }
        }
        foreach ($data as $id_process => $array) {
            krsort($data[$id_process]);
        }
        return $data;
    }

    public static function deleteRef($file_ref)
    {
        $dir = DOL_DATA_ROOT . '/bimpdatasync/reports/';

        if (file_exists($dir . $file_ref . '.csv')) {
            unlink($dir . $file_ref . '.csv');
        }
    }

    public static function deleteAll()
    {
        $dir = DOL_DATA_ROOT . '/bimpdatasync/reports/';
        if (!file_exists($dir)) {
            return;
        }
        ini_set('display_errors', 1);
        $files = scandir($dir);
        foreach ($files as $f) {
            if (in_array($f, array('.', '..'))) {
                continue;
            }
            unlink($dir . $f);
        }
    }

    public static function getObjectLabel($object, $plurial = false)
    {
        $label = '';
        if (!array_key_exists($object, self::$objectsLabels)) {
            if ($plurial) {
                $label = 'objets';
            } else {
                $label = 'objets';
            }
        } else {
            $label = self::$objectsLabels[$object];
            if ($plurial) {
                $label = str_replace('{', '', $label);
                $label = str_replace('}', '', $label);
            } else {
                $label = str_replace('{s}', '', $label);
            }
            $label = str_replace('[F]', '', $label);
        }
        return $label;
    }

    public static function getObjectLabelData($object)
    {
        $data = array(
            'name'     => 'object',
            'plurial'  => 'objets',
            'isFemale' => 0
        );

        if (array_key_exists($object, self::$objectsLabels)) {
            $label = self::$objectsLabels[$object];
            if (preg_match('/^.+\[F\]$/', $label)) {
                $data['isFemale'] = 1;
                $label = str_replace('[F]', '', $label);
            } else {
                $data['isFemale'] = 0;
            }

            $data['name'] = str_replace('{s}', '', $label);
            $label = str_replace('{', '', $label);
            $label = str_replace('}', '', $label);
            $data['plurial'] = $label;
        }

        return $data;
    }

    public static function getObjetsQuery()
    {
        $query = array();
        foreach (self::$objectsLabels as $name => $label) {
            $query[] = array(
                'name'  => strtolower($name),
                'label' => ucfirst(self::getObjectLabel($name))
            );
        }
        return $query;
    }

    public function getObjectsInfos()
    {
        $objectsInfos = array();
        foreach ($this->objects_data as $object_name => $data) {
            $label_data = $this->getObjectLabelData($object_name);
            $objectInfos = array(
                'name'  => ucfirst($label_data['plurial']),
                'infos' => array()
            );
            $label = BDS_Tools::makeObjectLabel($label_data['name'], 'of_plur', $label_data['isFemale'], $label_data['plurial']);
            foreach ($data as $dataName => $value) {
                $name = 0;
                if ((int) $value > 0) {
                    switch ($dataName) {
                        case 'nbProcessed':
                            $name = 'Nombre ' . $label . ' traité' . ($label_data['isFemale'] ? 'e' : '') . 's';
                            break;

                        case 'nbUpdated':
                            $name = 'Nombre ' . $label . ' mis' . ($label_data['isFemale'] ? 'es' : '') . ' à jour';
                            break;

                        case 'nbCreated':
                            $name = 'Nombre ' . $label . ' créé' . ($label_data['isFemale'] ? 'e' : '') . 's';
                            break;

                        case 'nbDeleted':
                            $name = 'Nombre ' . $label . ' supprimé' . ($label_data['isFemale'] ? 'e' : '') . 's';
                            break;

                        case 'nbActivated':
                            $name = 'Nombre ' . $label . ' activé' . ($label_data['isFemale'] ? 'e' : '') . 's';
                            break;

                        case 'nbDeactivated':
                            $name = 'Nombre ' . $label . ' désactivé' . ($label_data['isFemale'] ? 'e' : '') . 's';
                            break;

                        case 'nbIgnored':
                            $name = 'Nombre ' . $label . ' ignoré' . ($label_data['isFemale'] ? 'e' : '') . 's';
                            break;
                    }
                    if ($name) {
                        $objectInfos['infos'][] = array(
                            'name'  => $name,
                            'value' => $value
                        );
                    }
                }
            }
            $objectsInfos[] = $objectInfos;
        }
        return $objectsInfos;
    }
}

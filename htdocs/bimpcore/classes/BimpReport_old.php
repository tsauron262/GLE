<?php

class BimpReport
{

    public static $dirBase = 'bimpcore/reports';
    public static $refDateFormat = 'Ymd';
    public static $refTimeFormat = 'His';
    public static $ref_syntaxe = '{date}-{time}_{name}';
    public static $deleteDelay = 'P1M';
    public static $rows_def = array(
        'row' => array(
            'type' => 'Type',
            'time' => 'Heure',
            'msg'  => 'Message'
        )
    );
    public static $data_def = array(
        'title'      => 'Titre',
        'begin'      => 'Début',
        'end'        => 'Fin',
        'nbSuccess'  => 'Nombre de succès',
        'nbErrors'   => 'Nombre d\'erreurs',
        'nbWarnings' => 'Nombre d\'alertes'
    );
    public $dir;
    public $file_ref = '';
    public $rows = array();
    public $data = array(
        'title'       => '',
        'type'        => '',
        'begin'       => '',
        'end'         => '',
        'nbSuccesses' => 0,
        'nbErrors'    => 0,
        'nbWarnings'  => 0,
        'nbInfos'     => 0
    );
    protected $objects_data = array();

    public static function createReference($ref_values = array())
    {
        $ref = static::$ref_syntaxe;

        $ref = str_replace('{date}', date(static::$refDateFormat), $ref);
        $ref = str_replace('{time}', date(static::$refTimeFormat), $ref);

        foreach ($ref_values as $key => $value) {
            $ref = str_replace('{' . $key . '}', $value, $ref);
        }

        return $ref;
    }

    public function __construct($fileRef = '')
    {
        if ($fileRef) {
            if (preg_match('/^(.+)\.csv$/', $fileRef, $matches)) {
                $fileRef = $matches[1];
            }

            $this->file_ref = $fileRef;
            $this->loadFile();
        }
    }

    public function init($title = '', $ref_values = array(), $type = '', $subDir = '')
    {
        $this->dir = DOL_DATA_ROOT . '/' . static::$dirBase;

        if ($subDir) {
            $this->dir .= '/' . $subDir;
        }
        if (!file_exists($this->dir)) {
            BimpTools::makeDirectories(static::$dirBase . '/' . $subDir);
        }

        $this->file_ref = self::createReference($ref_values);

        $this->data['title'] = $title;
        $this->data['type'] = $type;
        $this->data['begin'] = date('Y-m-d H:i:s');
        $this->data['end'] = '';
    }

    public function loadFile()
    {
        $filePath = $this->dir . '/' . $this->file_ref . '.csv';
        if (file_exists($filePath)) {
            $rows = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($rows as $idx => $r) {
                if ($idx === 0) {
                    $header_values = explode(';', $r);
                    $i = 0;
                    foreach (static::$data_def as $key => $label) {
                        $this->data[$key] = $header_values[$i];
                        $i++;
                    }
                } else {
                    $data = explode(';', $r);
                    $row_type = $data[0];

                    if (!isset(static::$rows_def[$row_type])) {
                        continue;
                    }

                    if (!isset($this->rows[$row_type])) {
                        $this->rows[$row_type] = array();
                    }

                    $values = array();
                    $i = 1;
                    foreach (static::$rows_def[$row_type] as $key => $name) {
                        $values[$key] = $data[$i];
                        $i++;
                    }
                    $this->rows[$row_type][] = $values;
                    break;
                }
            }
        } else {
            $this->data['begin'] = date('Y-m-d H:i:s');
            $this->data['end'] = '';
        }
    }

    public function saveFile()
    {
        if (!file_exists($this->dir) || !is_dir($this->dir)) {
            return;
        }

        if ($this->data['end'] === '') {
            $this->end();
        }

        $txt = '';

        // En-tête: 
        $fl = true;

        foreach (static::$data_def as $key => $label) {
            if (!$fl) {
                $txt .= ';';
            } else {
                $fl = false;
            }

            $txt .= (isset($this->data[$key]) ? $this->data[$key] : '');
        }
        $txt .= "\n";

        foreach ($this->rows as $row_type => $rows) {
            foreach ($rows as $row) {
                $txt .= $row_type;
                foreach ($row as $value) {
                    $txt .= ';' . str_replace(';', ',', $value);
                }
                $txt .= "\n";
            }
        }

        file_put_contents($this->dir . $this->file_ref . '.csv', $txt);
    }

    public function setData($dataName, $value)
    {
        if (array_key_exists($dataName, static::$data_def)) {
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

    public function addRow($data = array(), $rowType = 'row')
    {
        $type = BimpTools::getArrayValueFromPath($data, 'type', 'danger');
        $msg = BimpTools::getArrayValueFromPath($data, 'msg', '');

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

        if ($rowType === 'row') {
            $row = array(
                'type' => $type,
                'time' => date('H:i:s'),
                'msg'  => BimpTools::getMsgFromArray($msg)
            );
        } else {
            $row = array();
        }

        foreach ($data as $key => $value) {
            if ($rowType === 'row' && in_array($key, array('type', 'time', 'msg'))) {
                continue;
            }

            if (isset(static::$rows_def[$rowType][$key])) {
                $row[$key] = $value;
            }
        }

        if (!isset($this->rows[$rowType])) {
            $this->rows[$rowType] = array();
        }

        $this->rows[$rowType][] = $row;

        switch ($type) {
            case 'danger': $this->data['nbErrors'] ++;
                break;
            case 'warning': $this->data['nbAlerts'] ++;
                break;
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

    public static function getReportsList($subDir = '')
    {
        $dir = DOL_DATA_ROOT . '/' . static::$dirBase;

        if ($subDir) {
            $dir .= '/' . $subDir;
        }

        if (!file_exists($dir) || !is_dir($dir)) {
            return array();
        }

        $files = scandir($dir);

        $reports = array();
        arsort($files);

        $DT = new DateTime();
        $DT->sub(new DateInterval(static::$deleteDelay));
        $deleteDate = $DT->format('Y-m-d');

        foreach ($files as $f) {
            if (in_array($f, array('', '.', '..'))) {
                continue;
            }

            $fileRef = str_replace('.csv', '', $f);
            $report = new BDSReport($fileRef);

            if (empty($report->rows)) {
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

            $reports[] = array(
                'file'    => $f,
                'ref'     => $fileRef,
                'date'    => $date->format('Y-m-d'),
                'name'    => $name,
                'type'    => $report->getData('type'),
                'nErrors' => $nErrors,
                'nAlerts' => $nAlerts
            );
        }

        return $reports;
    }

    public static function getReportsDetails($params = array(), $data_filters = array())
    {
        $dir = DOL_DATA_ROOT . '/' . static::$dirBase;

        if (isset($params['subDir']) && (string) $params['subDir']) {
            $dir .= '/' . $params['subDir'];
        }

        if (!file_exists($dir)) {
            return array();
        }

        $from = BimpTools::getArrayValueFromPath($params, 'from', '');
        $to = BimpTools::getArrayValueFromPath($params, 'to', '');

        $data = array();

        if (!preg_match('/^\d{8}\-\d{6}$/', $from)) {
            $from = '';
        }
        if (!preg_match('/^\d{8}\-\d{6}$/', $to)) {
            $to = '';
        }

        if (is_null($to)) {
            $to = date('Ymd-His');
        }

        $reports = scandir($dir);
        foreach ($reports as $report_file) {
            if (in_array($report_file, array('', '.', '..'))) {
                continue;
            }

            $report = new BDS_Report($report_file);
            $report_date = $report->getData('begin');

            if ($to && ($report_date > $to)) {
                continue;
            }
            if ($from && ($report_date < $from)) {
                continue;
            }

            foreach ($data_filters as $data_name => $filter_value) {
                if (array_key_exists($data_name, static::$data_def)) {
                    if ($report->getData($data_name) != $filter_value) {
                        continue 2;
                    }
                }
            }

            $data[$report_date] = $report->data;
            $data[$report_date]['report_ref'] = $report->file_ref;

            switch (BimpTools::getArrayValueFromPath($params, 'sort_way', 'desc')) {
                case 'asc':
                    ksort($data);
                    break;

                case 'desc':
                    krsort($data);
                    break;
            }
        }
        return $data;
    }

    public static function deleteRef($file_ref, $subDir = '')
    {
        $dir = DOL_DATA_ROOT . '/' . static::$dirBase;

        if ($subDir) {
            $dir .= '/' . $subDir;
        }

        $fileName = $file_ref;

        if (!preg_match('/^.+\.csv$/', $fileName)) {
            $fileName .= '.csv';
        }

        if (file_exists($dir . '/' . $fileName)) {
            unlink($dir . '/' . $fileName);
        }
    }

    public static function deleteAll($subDir = '')
    {
        if (!$subDir) {
            return;
        }

        $dir = DOL_DATA_ROOT . '/' . static::$dirBase . '/' . $subDir;

        if (!file_exists($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $f) {
            if (in_array($f, array('.', '..'))) {
                continue;
            }
            unlink($dir . '/' . $f);
        }
    }
}

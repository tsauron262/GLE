<?php

//ini_set('display_errors', 1);

class BDS_Report
{

    public static $deleteDelay = 'P1M';
    public static $messagesTypes = array('success, info, warning, danger');
    public static $row_def = array(
        'type'      => 'Statut',
        'time'      => 'Heure',
        'object'    => 'Objet GLE',
        'id_object' => 'ID Objet GLE',
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
    public static $psObjectsLabels = array(
        'Product'        => 'produit{s}',
        'Category'       => 'categorie{s}[F]',
        'Customer'       => 'client{s}',
        'Address'        => 'adresse{s}[F]',
        'Order'          => 'commande{s}[F]',
        'OrderState'     => 'état{s} de commande',
        'OrderHistory'   => 'historique{s} d\'une commande',
        'Cart'           => 'panier{s}',
        'Combination'    => 'déclinaison{s}[F]',
        'Attribute'      => 'attribut{s}',
        'AttributeGroup' => 'type{s} d\'attribut',
        'Carrier'        => 'transporteur{s}',
        'Country'        => 'pays',
        'Feature'        => 'caractéristique{s}[F]',
        'FeatureValue'   => 'valeur{s} de caractéristique[F]',
        'Language'       => 'langue{s}[F]',
        'Manufacturer'   => 'marque{s}[F]',
        'State'          => 'état{s} / Région{s}',
        'Store'          => 'magasin{s}',
        'Supplier'       => 'fournisseur{s}',
        'Zone'           => 'zone{s}[F]',
        'StockAvailable' => 'stock{s} produit',
        'TaxRule'        => 'règle{s} de taxe[F]'
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

    public function __construct($fileRef = null)
    {
        if (!isset($fileRef) || empty($fileRef)) {
            $date = new DateTime();
            $this->file_ref = $date->format('Ymd-His');
            $this->data['begin'] = $date->format('d/m/Y H:i:s');
            $this->data['end'] = '';
        } else {
            $this->file_ref = $fileRef;
            $this->loadFile();
        }
    }
    
    public function loadFile()
    {
        $fileName = __DIR__ . '/../reports/' . $this->file_ref . '.csv';
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
            $this->data['begin'] = date('d/m/Y H:i:s');
            $this->data['end'] = '';
        }
    }

    public function saveFile()
    {
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

        file_put_contents(__DIR__ . '/../reports/' . $this->file_ref . '.csv', $txt);
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
            'type'         => $type,
            'time'         => date('H:i:s'),
            'object'       => $objectName,
            'id_object' => $id_object,
            'reference'    => $reference,
            'msg'          => $msg
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
        $date = new DateTime();
        $this->data['end'] = $date->format('d/m/Y H:i:s');
    }

    public static function getReportsList($full = false)
    {
        $files = scandir(__DIR__ . '/../reports/');

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
            $report = new BDS_Report($ref);
            
            if (!count($report->rows)) {
                unlink(__DIR__.'/../reports/'.$f);
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
                unlink(__DIR__ . '/../reports/' . $f);
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
                'file'       => $f,
                'ref'        => $ref,
                'date'       => $date->format('Y-m-d'),
                'name'       => $name,
                'id_process' => $report->getData('id_process'),
                'nErrors'    => $nErrors,
                'nAlerts'    => $nAlerts
            );
        }

        return $reports;
    }

    public static function getObjectNotifications($objectName, $id_object = null, $reference = null, $from = null, $to = null)
    {
        $data = array();
        if (is_null($id_object) && is_null($reference)) {
            return $data;
        }
        if (!preg_match('/^\d{8}\-\d{6}$/', $from)) {
            $from = null;
        }
        if (is_null($to)) {
            $to = date('Ymd-His');
        }

        $objectName = strtolower($objectName);

        $reports = scandir(__DIR__ . '/../reports/');
        foreach ($reports as $report_file) {
            if (!in_array($report_file, array('.', '..'))) {
                if (preg_match('/^(.+)\.csv$/', $report_file, $matches)) {
                    if ($matches[1] > $to) {
                        continue;
                    }
                    if (!is_null($from) && ($matches[1] < $from)) {
                        continue;
                    }
                    $report = new BDS_Report($matches[1]);
                    foreach ($report->rows as $r) {
                        if ($objectName !== strtolower($r['object'])) {
                            continue;
                        }
                        if ((!is_null($id_object) && ((int) $id_object === (int) $r['id_object'])) ||
                                (!is_null($reference) && ($reference === $r['reference']))) {
                            if (!array_key_exists($report->file_ref, $data)) {
                                $data[$report->file_ref] = array();
                            }
                            $data[$report->file_ref][] = $r;
                        }
                    }
                }
            }
        }

        krsort($data);
        return $data;
    }

    public static function deleteRef($file_ref)
    {
        if (file_exists(__DIR__ . '/reports/' . $file_ref . '.csv')) {
            unlink(__DIR__ . '/reports/' . $file_ref . '.csv');
        }
    }

    public static function deleteAll()
    {
        ini_set('display_errors', 1);
        $files = scandir(__DIR__ . '/../reports/');
        foreach ($files as $f) {
            if (in_array($f, array('.', '..'))) {
                continue;
            }
            unlink(__DIR__ . '/../reports/' . $f);
        }
    }

    public static function getPsObjectLabel($psObject, $plurial = false)
    {
        $label = '';
        if (!array_key_exists($psObject, self::$psObjectsLabels)) {
            if ($plurial) {
                $label = 'objets';
            } else {
                $label = 'objets';
            }
        } else {
            $label = self::$psObjectsLabels[$psObject];
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

    public static function getPsObjectLabelData($psObject)
    {
        $data = array(
            'name'     => 'object',
            'plurial'  => 'objets',
            'isFemale' => 0
        );

        if (array_key_exists($psObject, self::$psObjectsLabels)) {
            $label = self::$psObjectsLabels[$psObject];
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

    public static function getPsObjetsQuery()
    {
        $query = array();
        foreach (self::$psObjectsLabels as $name => $label) {
            $query[] = array(
                'name'  => strtolower($name),
                'label' => ucfirst(self::getPsObjectLabel($name))
            );
        }
        return $query;
    }
}

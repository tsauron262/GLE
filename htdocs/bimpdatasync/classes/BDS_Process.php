<?php

abstract class BDS_Process
{

    public static $files_dir_name = '';
    public static $debug_mod = true;
    public static $memory_limit = '1000M';
    public static $objects = array();
    public $db = null;
    public $user = null;
    public $use_references = false;
    public $report = null;
    public $langs = 0;
    public $parameters_ok = true;
    public $options_ok = true;
    public $current_object = array(
        'name'     => null,
        'id'       => null,
        'ref'      => null,
        'increase' => false
    );
    public static $id = null;
    public static $name = '';
    public static $title = '';
    public static $active = 1;
    public $filesDir = '';
    public $parameters = array();
    public $options = array();
    public $triggers = array();
    protected $references = array();

    public function __construct($user, $params = null)
    {
        if (is_null(static::$id)) {
            $msg = 'ID null lors de l\'initalisation ';
            if (static::$title) {
                $msg .= 'du processus "' . static::$title . '"';
            } else {
                $msg .= 'd\'un processus (nom inconnu)';
            }
            if (static::$name) {
                $msg .= '. (' . static::$name . ')';
            }
            $this->logError($msg);
        }

        set_time_limit(0);
        ini_set('memory_limit', static::$memory_limit);

        global $db, $langs;
        $this->db = new BimpDb($db);
        $this->langs = $langs;
        $this->user = $user;

        $this->filesDir = __DIR__ . '/../files/' . static::$files_dir_name . '/';

        if (self::$debug_mod) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
        }

        if (!is_null($params)) {
            foreach ($params as $option_name => $option_value) {
                if (array_key_exists($option_name, $this->options)) {
                    $this->options[$option_name] = $option_value;
                }
            }
        }

        if (self::$debug_mod) {
            echo '<h1>' . static::$title . '</h1>';
        }
    }

    public function deleteObjects($objects)
    {
        
    }

    public function end($saveFile = true)
    {
        if (!is_null($this->report)) {
            $this->report->end();
            if ($saveFile) {
                $this->report->saveFile();
            }
        }
    }

    // Déclenchement des opération: 

    public function executeTriggerAction($action, $object)
    {
        $report_ref = 'actions_' . static::$id . '_' . date('Y-m-d') . ' 00:00:00';
        if (!file_exists(__DIR__ . '/../reports/' . $report_ref . '.csv')) {
            $this->report = new BDS_Report();
            $this->report->file_ref = $report_ref;
            $title = static::$title . ' - Opérations automatiques (triggers) du ' . date('d / m / Y');
            $this->report->setData('title', $title);
            $this->report->setData('id_process', static::$id);
        } else {
            $this->report = new BDS_Report($report_ref);
        }

        $check = true;
        if (method_exists($this, 'initTriggerActionExecution')) {
            if (!$this->initTriggerActionExecution($action, $object)) {
                $check = false;
            }
        }

        $words = explode('_', $action);
        $action = '';
        foreach ($words as $word) {
            $action .= ucfirst(strtolower($word));
        }

        if ($check) {
            $method = 'triggerAction' . $action;
            if (method_exists($this, $method)) {
                $this->{$method}($object);
            } else {
                $this->Error('Erreur technique : Méthode "' . $method . '" inexistante');
            }
        }

        $this->end();
    }

    public function executeSoapRequest($params, &$errors)
    {

        $return = array();

        if (is_null($this->report)) {
            $this->report = new BDS_Report('requests_' . date('Y-m-d'));
            $this->report->setData('title', 'Requêtes du ' . date('d / m / Y'));
            $this->report->setData('id_process', static::$id);
        }

        if (!isset($params['operation']) || !$params['operation']) {
            $msg = 'Aucune opération spécifiée';
            $errors[] = $msg;
            $this->Error($msg);
        } elseif (!method_exists($this, $params['operation'])) {
            $msg = 'Action spécifiée non valide "' . $params['operation'] . '" ';
            $errors[] = $msg;
            $this->Error($msg);
        } else {
            $return = $this->{$params['operation']}($params, $errors);
        }
        $this->end();

        return $return;
    }

    // Gestion des listes de références: 

    public function setReferences($references, $object_name = null)
    {
        $this->use_references = true;
        if (!is_null($object_name)) {
            $this->references[$object_name] = $references;
        } else {
            $this->references = $references;
        }
    }

    public function addReferences($object_name, $references)
    {
        $this->use_references = true;
        if (!array_key_exists($object_name, $this->references)) {
            $this->references[$object_name] = array();
        }
        if (!is_array($references)) {
            $references = array($references);
        }
        foreach ($references as $reference) {
            if (!in_array($reference, $this->references[$object_name])) {
                $this->references[$object_name] = $reference;
            }
        }
    }

    public function getReferences($object_name = null)
    {
        if (!is_null($object_name)) {
            if (array_key_exists($object_name, $this->references)) {
                return $this->references;
            }
            return array();
        }
        return $this->references;
    }

    // Outils divers

    public function curName()
    {
        return $this->current_object['name'];
    }

    public function curId()
    {
        return $this->current_object['id'];
    }

    public function curRef()
    {
        return $this->current_object['ref'];
    }

    public function isCurrent($object)
    {
        if (!is_null($this->current_object['name'])) {
            if ($this->getObjectClass($object) === $this->current_object['name']) {
                return true;
            }
        }
        return false;
    }

    public function getObjectClass($object)
    {
        if (is_string($object)) {
            return $object;
        } elseif (is_object($object)) {
            return get_class($object);
        }

        return '';
    }

    // Gestion des entrées dans le rapport:

    public function setCurrentObject($object, $id_object = null, $reference = null, $increase = true)
    {
        $this->current_object = array(
            'name'     => $this->getObjectClass($object),
            'id'       => $id_object,
            'ref'      => $reference,
            'increase' => $increase
        );
    }

    public function logError($msg, $level = 3)
    {
        if (is_array($msg)) {
            $array = $msg;
            $msg = '';
            foreach ($array as $line) {
                $msg .= $line . "\n";
            }
        }
        dol_syslog($msg, $level);
    }

    public function Msg($msg, $class = '', $tag = 'p')
    {
        if (!self::$debug_mod) {
            $this->logError($msg);
            return;
        }
        echo '<' . $tag . ($class ? ' class="' . $class . '"' : '') . '>';
        if (is_array($msg)) {
            echo '<ul>';
            foreach ($msg as $m) {
                echo '<li>';
                if (is_array($m)) {
                    echo '<pre>';
                    print_r($m);
                    echo '</pre>';
                } else {
                    echo $m;
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo $msg;
        }
        echo '</' . $tag . '>';
    }

    public function addReportRow($type, $msg, $objectName = '', $id_object = '', $reference = '')
    {
        if (is_array($msg)) {
            $txt = '<ul>';
            foreach ($msg as $m) {
                $txt .= '<li>' . $m . '</li>';
            }
            $txt .= '</ul>';
            $msg = $txt;
        }
        if (!is_null($this->report)) {
            $this->report->addRow($type, $msg, $objectName, $id_object, $reference);
        }
    }

    public function Alert($msg, $objectName = '', $id_object = '', $reference = '')
    {
        $this->addReportRow('warning', $msg, $objectName, $id_object, $reference);
        if (self::$debug_mod) {
            $this->Msg($msg, 'warning');
        }
    }

    public function Error($msg, $objectName = '', $id_object = '', $reference = '')
    {
        $this->addReportRow('error', $msg, $objectName, $id_object, $reference);
        if (self::$debug_mod) {
            $this->Msg($msg, 'error');
        }
    }

    public function SqlError($msg, $objectName = '', $id_object = '', $reference = '')
    {
        $sqlError = $this->db->db->lasterror();
        if ($sqlError) {
            $msg .= ' - Erreur SQL: <span class="sqlError">' . $sqlError . '</span>';
        }
        $this->addReportRow('error', $msg, $objectName, $id_object, $reference);
        if (self::$debug_mod) {
            $this->Msg($msg, 'error');
        }
    }

    public function Success($msg, $objectName = '', $id_object = '', $reference = '')
    {
        $this->addReportRow('success', $msg, $objectName, $id_object, $reference);
        if (self::$debug_mod) {
            $this->Msg($msg, 'success');
        }
    }

    public function Info($msg, $objectName = '', $id_object = '', $reference = '')
    {
        $this->addReportRow('info', $msg, $objectName, $id_object, $reference);
        if (self::$debug_mod) {
            $this->Msg($msg, 'info');
        }
    }

    public function setElementsNumber($object_name, $number)
    {
        if (!is_null($this->report)) {
            $this->report->setObjectDataValue($object_name, 'nbToProcess', $number);
        }
    }

    // Gestion des incrémentations d'objet dans le rapport:

    public function incProcessed()
    {
        if (!is_null($this->report) && !is_null($this->current_object['name']) &&
                $this->current_object['increase']) {
            $this->report->increaseObjectData($this->current_object['name'], 'nbProcessed');
        }
    }

    public function incCreated()
    {
        if (!is_null($this->report) && !is_null($this->current_object['name']) &&
                $this->current_object['increase']) {
            $this->report->increaseObjectData($this->current_object['name'], 'nbCreated');
        }
    }

    public function incUpdated()
    {
        if (!is_null($this->report) && !is_null($this->current_object['name']) &&
                $this->current_object['increase']) {
            $this->report->increaseObjectData($this->current_object['name'], 'nbUpdated');
        }
    }

    public function incActivated()
    {
        if (!is_null($this->report) && !is_null($this->current_object['name']) &&
                $this->current_object['increase']) {
            $this->report->increaseObjectData($this->current_object['name'], 'nbActivated');
        }
    }

    public function incDeactivated()
    {
        if (!is_null($this->report) && !is_null($this->current_object['name']) &&
                $this->current_object['increase']) {
            $this->report->increaseObjectData($this->current_object['name'], 'nbDeactivated');
        }
    }

    public function incIgnored()
    {
        if (!is_null($this->report) && !is_null($this->current_object['name']) &&
                $this->current_object['increase']) {
            $this->report->increaseObjectData($this->current_object['name'], 'nbIgnored');
        }
    }

    public function incDeleted()
    {
        if (!is_null($this->report) && !is_null($this->current_object['name']) &&
                $this->current_object['increase']) {
            $this->report->increaseObjectData($this->current_object['name'], 'nbDeleted');
        }
    }

    // Gestion statique des processus:

    public static function createProcess($fuser, $processName, &$error, $params = null)
    {
        $className = 'BDS_' . $processName . 'Process';
        if (!self::loadProcessClass($className)) {
            $error = 'Classe "' . $className . '" inexistante';
            return null;
        }
        return new $className($fuser, $params);
    }

    public static function loadProcessClass($className)
    {
        if (class_exists($className)) {
            return true;
        }

        $file = __DIR__ . '/process_overrides/' . $className . '.php';
        if (file_exists($file)) {
            require_once($file);
            return class_exists($className);
        }
        return false;
    }

    public static function getTriggerActionProcesses($action)
    {
        global $db, $user;
        $bdb = new BimpDb($db);
        $processes = array();
        $rows = $bdb->getRows('bds_process_trigger_action', '`active` = 1 AND `action` LIKE \'' . $db->escape($action) . '\'');
        if ($rows) {
            foreach ($rows as $r) {
                $error = 0;
                $process = self::createProcess($user, $r->process_name, $error);
                if (is_null($process) && $process::$active) {
                    $msg = 'Bimp Data Sync - Trigger action "' . $action . '" - Echec de la création du processus "' . $r->process_name . '"';
                    if ($error) {
                        $msg .= ' - Erreur: ' . $error;
                    }
                    dol_syslog($msg, LOG_ERR);
                } else {
                    $processes[] = $process;
                }
            }
        }

        return $processes;
    }

    // Installation:

    public static function addProcessTriggerActions($actions)
    {
        global $db;
        $bdb = new BimpDb($db);

        $errors = array();

        if (!$bdb->delete('bds_process_trigger_action', '`id_process` = ' . (int) static::$id)) {
            $msg = 'Echec de la suppression des actions actuelles';
            $msg .= ' - Erreur SQL ' . $db->error();
            $errors[] = $msg;
            return $errors;
        }

        foreach ($actions as $action) {
            if (isset($action['name']) && $action['name']) {
                if (!$bdb->insert('bds_process_trigger_action', array(
                            'id_process'   => (int) static::$id,
                            'process_name' => $db->escape(static::$name),
                            'action'       => $db->escape($action['name']),
                            'active'       => (isset($action['active']) ? (int) $action['active'] : 0)
                        ))) {
                    $msg = 'Echec de l\'ajout de l\'action "' . $action['action'] . '" pour le process "' . static::$name . '"';
                    $msg .= ' - Erreur SQL: ' . $db->error();
                    $errors[] = $msg;
                }
            } else {
                $errors[] = 'Une action non enregistrée (Nom de l\'action non spécifié) pour le process "' . static::$name . '"';
            }
        }
        return $errors;
    }
}

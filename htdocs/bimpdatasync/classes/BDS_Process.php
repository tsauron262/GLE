<?php

abstract class BDS_Process
{

    public static $process_name = null;
    public static $files_dir_name = '';
    public static $debug_mod = false;
    public static $memory_limit = '1000M';
    public static $objects = array();
    public $db = null;
    public $user = null;
    public $use_references = false;
    public $report = null;
    public $langs = 0;
    public $filesDir = '';
    public $processDefinition = null;
    public $parameters = array();
    public $options = array();
    public $triggers = array();
    protected $references = array();
    public $parameters_ok = true;
    public $options_ok = true;
    public $current_object = array(
        'name'     => null,
        'id'       => null,
        'ref'      => null,
        'increase' => false
    );

    public function __construct(BDSProcess $processDefinition, $user, $params = null)
    {
        if (!isset($processDefinition->id) || !$processDefinition->id) {
            $msg = 'ID null lors de l\'initalisation ';
            if (isset($processDefinition->title)) {
                $msg .= 'du processus "' . $processDefinition->title . '"';
            } else {
                $msg .= 'du processus (nom inconnu)';
            }
            if (isset($processDefinition->name)) {
                $msg .= '. (' . $processDefinition->name . ')';
            }
            $this->logError($msg);
        }

        $this->processDefinition = $processDefinition;

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

        $parameters = BDSProcessParameter::getListData($this->db, $processDefinition->id);
        foreach ($parameters as $p) {
            if (!isset($p['value']) || empty($p['value']) || $p['value'] === '') {
                $msg = 'Erreur de configuration: aucune valeur spécifiée pour le paramètre "';
                $msg .= $p['label'] . '" (ID ' . $p['id_parameter'] . ')';
                $this->Alert($msg);
                $this->parameters_ok = false;
            } else {
                $this->parameters[$p['name']] = $p['value'];
            }
        }

        if (!is_null($params)) {
            $options = BDSProcessOption::getListData($this->db, $processDefinition->id);
            foreach ($options as $o) {
                if (isset($params[$o['name']])) {
                    $this->options[$o['name']] = $params[$o['name']];
                } elseif (isset($o['default_value']) && !is_null($o['default_value']) && ($o['default_value'] !== '')) {
                    if ($o['type'] === 'switch') {
                        $this->options[$o['name']] = (int) $o['default_value'];
                    } else {
                        $this->options[$o['name']] = $o['default_value'];
                    }
                }
            }
        }

        if (isset($params['references'])) {
            $this->setReferences($params['references']);
        }

        if (self::$debug_mod) {
            echo '<h1>' . $this->processDefinition->title . '</h1>';
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

    public function initOperation($id_operation, &$errors)
    {
        $data = array();
        $data['id_process'] = $this->processDefinition->id;
        $data['id_operation'] = $id_operation;
        $data['use_report'] = true;
        $data['report_ref'] = '';
        $data['operation_title'] = '';

        if (is_null($id_operation) || !$id_operation) {
            $errors[] = 'ID de l\'opération absent';
        } else {
            $operation = new BDSProcessOperation();
            if (!$operation->fetch($id_operation)) {
                $errors[] = 'Cette opération semble ne pas être enregistrée';
            } else {
                $data['operation_title'] = $operation->title;

                $options = BDSProcessOperation::getOperationOptions($operation->id);
                foreach ($options as $option) {
                    if (!isset($this->options[$option['name']])) {
                        if (isset($option['required']) && $option['required']) {
                            $errors[] = 'Option obligatoire non spécifiée: "' . $option['label'] . '"';
                            $this->options_ok = false;
                        }
                    }
                }

                $method = 'init';
                $words = explode('_', $operation->name);
                foreach ($words as $word) {
                    $method .= ucfirst($word);
                }

                if (!method_exists($this, $method)) {
                    $errors[] = 'Erreur  technique: méthode "' . $method . '" inexistatnte';
                }

                if (!count($errors)) {
                    $this->{$method}($data, $errors);
                    if (!count($errors) && $data['use_report']) {
                        $title = $this->processDefinition->title . ' - ' . $operation->title . ' du ' . date('d / m / Y');
                        $this->report = new BDS_Report($this->processDefinition->id, $title);
                        $this->report->setData('id_operation', $id_operation);
                        $this->report->saveFile();
                        $data['report_ref'] = $this->report->file_ref;
                    }
                }
            }
        }

        return $data;
    }

    public function executeOperationStep($id_operation, $step, &$errors, $report_ref = null)
    {
        $result = array();
        if (isset($this->processDefinition->id) && $this->processDefinition->id) {
            if (!is_null($report_ref)) {
                $this->report = new BDS_Report($this->processDefinition->id, null, $report_ref);
            }
            $operation = new BDSProcessOperation();
            if (!$operation->fetch($id_operation)) {
                $msg = 'Erreur technique : l\'opération d\'ID ' . $id_operation . ' semble ne pas être enregistrée';
                $errors[] = $msg;
                $this->Error($msg);
            } else {
                $options = BDSProcessOperation::getOperationOptions($operation->id);
                foreach ($options as $option) {
                    if (!isset($this->options[$option['name']])) {
                        if ($option['required']) {
                            $errors[] = 'Option obligatoire non spécifiée: "' . $option['label'] . '"';
                            $this->options_ok = false;
                        }
                    }
                }

                if ($this->options_ok) {
                    $method = 'execute';
                    $words = explode('_', $operation->name);
                    foreach ($words as $word) {
                        $method .= ucfirst($word);
                    }
                    if (!method_exists($this, $method)) {
                        $msg = 'Erreur technique - Méthode "' . $method . '" inexistante';
                        $this->Error($msg);
                        $errors[] = $msg;
                    } else {
                        $result = $this->{$method}($step, $errors);
                    }
                }
            }
        } else {
            $msg = 'Erreur technique: Définitions du processus absentes';
            $errors[] = $msg;
            $this->Error($msg);
        }
        $this->end(!is_null($report_ref));
        return $result;
    }

    public function executeTriggerAction($action, $object)
    {
        if (is_null($this->report)) {
            $report_ref = BDS_Report::createReference($this->processDefinition->id, 'actions');
            $title = $this->processDefinition->title . ' - Opérations automatiques du ' . date('d / m / Y');
            $this->report = new BDS_Report($this->processDefinition->id, $title, $report_ref);
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
            $report_ref = BDS_Report::createReference($this->processDefinition->id, 'requests');
            $title = $this->processDefinition->title . ' - Requêtes d\'import du ' . date('d / m / Y');
            $this->report = new BDS_Report($this->processDefinition->id, $title, $report_ref);
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
    public static function createProcessByName($fuser, $processName, &$error, $params = null)
    {
        global $db;
        $bdb = new BimpDb($db);

        $where = '`name` = \'' . $processName . '\'';
        $id_process = $bdb->getValue('bds_process', 'id', $where);
        if (is_null($id_process) || !$id_process) {
            $error = 'Processus "' . $processName . '" non enregistré';
            return null;
        }

        return self::createProcessById($fuser, $id_process, $error, $params);
    }

    public static function createProcessById($fuser, $id_process, &$error, $params = null)
    {
        $processDefinition = new BDSProcess();
        if (!$processDefinition->fetch($id_process)) {
            $error = 'Echec du chargement des données du processus (ID ' . $id_process . ')';
            return null;
        }

        $className = 'BDS_' . $processDefinition->name . 'Process';
        if (!self::loadProcessClass($className)) {
            $error = 'Classe "' . $className . '" inexistante';
            return null;
        }
        return new $className($processDefinition, $fuser, $params);
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

    // Installation:

    public function addProcessParameters($parameters)
    {
        global $db;
        $bdb = new BimpDb($db);

        $errors = array();

        BDSProcessParameter::deleteByParent($bdb, $this->processDefinition->id);

        foreach ($parameters as $name => $data) {
            $parameter = new BDSProcessParameter();
            $parameter->id_process = (int) $this->processDefinition->id;
            $parameter->name = $name;
            $parameter->label = $data['label'];
            $parameter->value = '' . $data['value'];
            if (!$parameter->create()) {
                $msg = 'Echec de l\'ajout du paramètre "' . $data['label'] . '" pour le process "' . $this->processDefinition->name . '"';
                $msg .= ' - Erreur SQL: ' . $db->error();
                $errors[] = $msg;
            }
            unset($parameter);
        }
        return $errors;
    }

    public function addProcessOptions($options)
    {
        global $db;
        $bdb = new BimpDb($db);

        $errors = array();

        BDSProcessOption::deleteByParent($bdb, $this->processDefinition->id);

        foreach ($options as $name => $data) {
            $option = new BDSProcessOption();
            $option->id_process = (int) $this->processDefinition->id;
            $option->name = $name;
            $option->label = $data['label'];
            $option->info = isset($data['info']) ? $data['info'] : '';
            $option->type = $data['type'];
            $option->default_value = isset($data['default_value']) ? $data['default_value'] : '';
            if (!$option->create()) {
                $msg = 'Echec de l\'ajout de l\'option "' . $data['label'] . '" pour le process "' . $this->processDefinition->name . '"';
                $msg .= ' - Erreur SQL: ' . $db->error();
                $errors[] = $msg;
            }
            unset($option);
        }
        return $errors;
    }

    public function addProcessTriggerActions($actions)
    {
        global $db;
        $bdb = new BimpDb($db);

        $errors = array();

        BDSProcessTriggerAction::deleteByParent($bdb, $this->processDefinition->id);

        foreach ($actions as $data) {
            if (isset($data['name']) && $data['name']) {
                $action = new BDSProcessTriggerAction();
                $action->id_process = (int) $this->processDefinition->id;
                $action->action_name = $data['name'];
                $action->active = (isset($data['active']) ? (int) $data['active'] : 0);
                if (!$action->create()) {
                    $msg = 'Echec de l\'ajout de l\'action "' . $data['name'] . '" pour le process "' . $this->processDefinition->name . '"';
                    $msg .= ' - Erreur SQL: ' . $db->error();
                    $errors[] = $msg;
                }
                unset($action);
            } else {
                $errors[] = 'Une action non enregistrée (Nom de l\'action non spécifié) pour le process "' . $this->processDefinition->name . '"';
            }
        }
        return $errors;
    }
}

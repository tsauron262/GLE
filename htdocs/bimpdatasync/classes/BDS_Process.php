<?php

include_once DOL_DOCUMENT_ROOT.'/synopsistools/SynDiversFunction.php';

abstract class BDS_Process
{

    public static $process_name = null;
    public static $files_dir_name = '';
    public static $debug_mod = false;
    public static $memory_limit = '1000M';
    public static $objects = array();
    public $db = null;
    public $user = null;
    public $debug_content = '';
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
        set_time_limit(600);
        ini_set('memory_limit', static::$memory_limit);

        global $db, $langs;

        if (isset($params['debug_mod']) && $params['debug_mod']) {
            self::$debug_mod = true;
            $this->options['debug_mod'] = true;
        }

        if (self::$debug_mod) {
            ini_set('display_errors', 1);
            error_reporting(E_ERROR);
            $lvl = ob_get_level();
            if ((int) $lvl > 0) {
                ob_end_flush();
            }
            ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
        } else {
            ini_set('display_errors', 0);
        }

        $this->db = new BDSDb($db);
        $this->langs = $langs;
        $this->langs->load("errors");

        global $user;
        $this->user = $user;

        $this->filesDir = DOL_DATA_ROOT . '/bimpdatasync/files/' . static::$files_dir_name . '/';

        if (!file_exists($this->filesDir)) {
            $result = BDS_Tools::makeDirectories(array(
                        'files' => static::$files_dir_name
            ));
            if ($result) {
                $this->logError($result);
                $this->parameters_ok = false;
                $this->Msg($result);
            }
        }

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
            $this->Msg($msg, 'danger');
        }

        $this->processDefinition = $processDefinition;

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
            if (isset($params['mode'])) {
                $this->options['mode'] = $params['mode'];
            }
            if (isset($params['debug_mod']) && !isset($this->options['debug_mode'])) {
                $this->options['debug_mod'] = (int) $params['debug_mod'];
            }
        }

        if (isset($params['references'])) {
            $this->setReferences($params['references']);
        }

        if (self::$debug_mod) {
            $this->debug_content .= '<h3>' . $this->processDefinition->title . '</h3>';
        }
    }

    public static function getClassName()
    {
        return 'BDS_Process';
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

        if (self::$debug_mod) {
            $notifications = ob_get_clean();
            if ($notifications) {
                $this->debug_content .= '<h4>Notifications: </h4>' . $notifications;
                dol_syslog($notifications, 3);
            }
        }

        $this->cleanTempDirectory();
    }

    // Déclenchement des opération: 

    public function initOperation($id_operation, &$errors)
    {
        if (!isset($this->options['mode'])) {
            $this->options['mode'] = 'debug';
            self::$debug_mod = true;
            $this->options['debug_mod'] = true;
        }

        $data = array(
            'id_process'      => $this->processDefinition->id,
            'id_operation'    => $id_operation,
            'use_report'      => true,
            'report_ref'      => '',
            'operation_title' => '',
            'debug_content'   => ''
        );

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
                        if ($this->options['mode'] === 'cron') {
                            $DT = new DateTime();
                            $file_ref = $DT->format(BDS_Report::$refDateFormat);
                            $file_ref .= '-' . $DT->format(BDS_Report::$refTimeFormat);
                            $file_ref .= '_' . $this->processDefinition->id . '_cron';
                        }
                        $this->report = new BDS_Report($this->processDefinition->id, $title, $file_ref);
                        $this->report->setData('id_operation', $id_operation);
                        $this->report->saveFile();
                        $data['report_ref'] = $this->report->file_ref;
                    }
                }
            }
        }

        if (self::$debug_mod) {
            $this->debug_content .= '<h4>Options: </h4><pre>';
            $this->debug_content .= print_r($this->options, 1);
            $this->debug_content .= '</pre>';
            $this->debug_content .= '<h4>Paramètres: </h4><pre>';
            $this->debug_content .= print_r($this->parameters, 1);
            $this->debug_content .= '</pre>';
            $this->debug_content .= '<h4>Données: </h4><pre>';
            $this->debug_content .= print_r($data, 1);
            $this->debug_content .= '</pre>';
        }

        $this->end(false);

        if (isset($this->options['debug_mod']) && $this->options['debug_mod']) {
            $html = '<div class="foldable_section closed">';
            $html .= '<div class="foldable_section_caption">';
            $html .= '[INITIALISATION]';
            $html .= '</div>';
            $html .= '<div class="foldable_section_content">' . $this->debug_content . '</div>';
            $html .= '</div>';
            $data['debug_content'] = $html;
        }

        if ($this->options['mode'] === 'debug') {
            echo $data['debug_content'];

            if (isset($data['result_html']) && $data['result_html']) {
                echo $data['result_html'];
            }
        }

        return $data;
    }

    public function executeOperationStep($id_operation, $step, &$errors, $report_ref = null, $iteration = 0)
    {
        if (!isset($this->options['mode'])) {
            $this->options['mode'] = 'debug';
            self::$debug_mod = true;
            $this->options['debug_mod'] = true;
        }

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

        if (self::$debug_mod) {
            if ($result) {
                $this->debug_content .= '<h4>Résultat: </h4><pre>';
                $this->debug_content .= print_r($result, 1);
                $this->debug_content .= '</pre>';
            }
        }

        $this->end(!is_null($report_ref));

        if ($this->debug_content) {
            $html = '<div class="foldable_section closed">';
            $html .= '<div class="foldable_section_caption">';
            $html .= 'Opération: "' . $step . '" ' . ($iteration ? '#' . $iteration : '');
            $html .= '</div>';
            $html .= '<div class="foldable_section_content" id="debugContent">';
            $html .= $this->debug_content;
            $html .= '</div>';
            $html .= '</div>';

            $result['debug_content'] = $html;
        }

        if ($this->options['mode'] === 'debug') {
            echo $result['debug_content'];
        }

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
        // On désactive la temporisation de sortie de manière à ce que le client puisse recevoir 
        // les notifications, notamment en cas d'erreur fatale.
        if (self::$debug_mod) {
            ob_end_flush();
        }

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

    public function executeObjectProcess($action, $object_name, $id_object)
    {
        if (!isset($this->options['mode'])) {
            $this->options['mode'] = 'debug';
            self::$debug_mod = true;
            $this->options['debug_mod'] = true;
        }

        $this->setCurrentObject($object_name, $id_object, '');

        $title = $this->processDefinition->title = $this->processDefinition->title . ' - Objet "' . $object_name . '"';
        $title .= ' - ID ' . $id_object . ' - Le ' . date('d / m / Y à H:i:s');
        $this->report = new BDS_Report($this->processDefinition->id, $title, null);

        $method = 'executeObject' . ucfirst($action);
        if (method_exists($this, $method)) {
            $this->{$method}($object_name, $id_object);
        } else {
            $this->Error('Erreur technique: méthode "' . $method . '" absente');
        }

        $return = array(
            'report_rows' => $this->report->rows
        );

        $this->end();

        if (self::$debug_mod) {
            $html = '<div class="foldable_section closed">';
            $html .= '<div class="foldable_section_caption">';
            $html .= 'Données debug';
            $html .= '</div>';
            $html .= '<div class="foldable_section_content" id="debugContent">';
            $html .= $this->debug_content;
            $html .= '</div>';
            $html .= '</div>';

            $return['debug_content'] = $html;
        }

        return $return;
    }

    public function executeCronProcess($id_operation)
    {
        self::$debug_mod = false;
        $this->options['mode'] = 'cron';
        set_time_limit(600);

        $errors = array();
        $data = $this->initOperation($id_operation, $errors);

        if (!count($errors)) {
            if (is_null($id_operation) || !$id_operation) {
                $errors[] = 'ID de l\'opération absent';
            } else {
                $operation = new BDSProcessOperation();
                if (!$operation->fetch($id_operation)) {
                    $errors[] = 'L\'opération d\'ID ' . $id_operation . ' semble ne pas être enregistrée';
                } else {
                    if (isset($data['steps']) && count($data['steps'])) {
                        $this->executeCronOperationSteps($operation, $data['steps']);
                    }
                }
            }
        }

        if (count($errors)) {
            $this->Error($errors);
        }

        $this->end();
    }

    protected function executeCronOperationSteps(BDSProcessOperation $operation, $steps)
    {
        if (!count($steps)) {
            return array();
        }

        $extraSteps = array();

        foreach ($steps as $step => $step_params) {
            if (isset($step_params['elements']) && count($step_params['elements'])) {
                $n = 0;
                while ($n < count($step_params['elements'])) {
                    $elements = array();
                    for ($i = 0; $i < 100; $i++) {
                        if (!isset($step_params['elements'][$n])) {
                            $n = count($step_params['elements']);
                            break;
                        }
                        $elements[] = $step_params['elements'][$n];
                        $n++;
                    }
                    if (count($elements)) {
                        set_time_limit(count($elements) * 30);
                        $this->setReferences($elements);
                        $errors = array();
                        $result = $this->executeCronOperationStep($operation, $step, $errors);
                        if (count($errors)) {
                            if (isset($step_params['on_error'])) {
                                if ($step_params['on_error'] === 'continue') {
                                    continue;
                                } else {
                                    $this->Error('Une erreur est survenue. Opération abandonnée');
                                    break 2;
                                }
                            }
                        } else {
                            if (isset($result['new_steps'])) {
                                $extraSteps = BimpTools::merge_array($extraSteps, $result['new_steps']);
                            }
                        }
                    }
                }
            } else {
                set_time_limit(600);
                $errors = array();
                $result = $this->executeCronOperationStep($operation, $step, $errors);
                if (count($errors)) {
                    if (isset($step_params['on_error'])) {
                        if ($step_params['on_error'] === 'continue') {
                            continue;
                        } else {
                            $this->Error('Une erreur est survenue. Opération abandonnée');
                            break;
                        }
                    }
                } else {
                    if (isset($result['new_steps'])) {
                        $extraSteps = BimpTools::merge_array($extraSteps, $result['new_steps']);
                    }
                }
            }
        }

        if (count($extraSteps)) {
            $this->executeCronOperationSteps($operation, $extraSteps);
        }
    }

    protected function executeCronOperationStep(BDSProcessOperation $operation, $step, &$errors)
    {
        $method = 'execute';
        $words = explode('_', $operation->name);
        foreach ($words as $word) {
            $method .= ucfirst($word);
        }
        $result = array();
        if (!method_exists($this, $method)) {
            $msg = 'Erreur technique - Méthode "' . $method . '" inexistante';
            $errors[] = $msg;
            $this->Error($msg);
        } else {
            $errors = array();
            $result = $this->{$method}($step, $errors);
        }
        return $result;
    }

    // Outils de connexion et d'extraction des données:

    protected function openXML($fileSubDir, $file)
    {
        if (!isset($file) || empty($file) || ($file === '')) {
            $this->Error('Aucun nom spécifié pour le fichier XML');
        } elseif (!file_exists($this->filesDir . $fileSubDir . $file)) {
            $this->Error('Fichier XML non trouvé: "' . $this->filesDir . $fileSubDir . $file . '"');
        } else {
            $XML = simplexml_load_file($this->filesDir . $fileSubDir . $file, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (is_null($XML) || !$XML) {
                $this->Error('Echec du chargement du fichier "' . $file . '"');
                return null;
            }
            return $XML;
        }
        return null;
    }

    protected function openXLS($fileSubDir, $file)
    {
        if (!isset($file) || empty($file) || ($file === '')) {
            $this->Error('Aucun nom spécifié pour le fichier Excel');
        } elseif (!file_exists($this->filesDir . $fileSubDir . $file)) {
            $this->Error('Fichier Excel non trouvé: "' . $this->filesDir . $fileSubDir . $file . '"');
        } else {
            if (!defined('PHPEXCEL_ROOT')) {
                include_once __DIR__ . '/phpExcel/PHPExcel.php';
            }
            $XLS = PHPExcel_IOFactory::load($this->filesDir . $fileSubDir . $file);
            if (is_null($XLS) || !$XLS) {
                $this->Error('Echec du chargement du fichier "' . $file . '"');
                return null;
            }
            return $XLS;
        }
        return null;
    }

    protected function ftpConnect($host, $login, $pword, $passive = true, &$errors = null)
    {
        $ftp = ftp_connect($host);

        if ($ftp === false) {
            $msg = 'Echec de la connexion FTP avec le serveur "' . $host . '"';
            $this->Error($msg);
            if (!is_null($errors)) {
                $errors[] = $msg;
            }
            return false;
        }

        if (!ftp_login($ftp, $login, $pword)) {
            $msg = 'Echec de la connexion FTP - Identifiant ou mot de passe incorrect.';
            $msg .= 'Veuillez vérifier les paramètres de connexion';
            $this->Error($msg);
            if (!is_null($errors)) {
                $errors[] = $msg;
            }
            return false;
        }

        if ($passive) {
            ftp_pasv($ftp, true);
        }
        return $ftp;
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
        $this->debug_content .= '<' . $tag . ($class ? ' class="' . $class . '"' : '') . '>';
        if (is_array($msg)) {
            $this->debug_content .= '<ul>';
            foreach ($msg as $m) {
                $this->debug_content .= '<li>';
                if (is_array($m)) {
                    $this->debug_content .= '<pre>';
                    print_r($m);
                    $this->debug_content .= '</pre>';
                } else {
                    $this->debug_content .= $m;
                }
                $this->debug_content .= '</li>';
            }
            $this->debug_content .= '</ul>';
        } else {
            $this->debug_content .= $msg;
        }
        $this->debug_content .= '</' . $tag . '>';
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
        $bdb = new BDSDb($db);

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

    public static function getClassByProcessId($id_process)
    {
        global $db;
        $bdb = new BDSDb($db);

        $className = null;

        $process_name = $bdb->getValue(BDSProcess::$table, 'name', '`id` = ' . (int) $id_process);
        if (!is_null($process_name) && $process_name) {
            $className = 'BDS_' . $process_name . 'Process';
            if (!class_exists($className)) {
                self::loadProcessClass($className);
                if (!class_exists($className)) {
                    $className = null;
                }
            }
        }

        unset($bdb);
        return $className;
    }

    // Gestion statique des données objets

    public static function getObjectProcessesData($id_object, $object_name)
    {
        global $db;
        $bdb = new BDSDb($db);

        $processes = BDSProcess::getListData($bdb);

        $return = array();
        foreach ($processes as $p) {
            $className = 'BDS_' . $p['name'] . 'Process';
            if (!class_exists($className)) {
                self::loadProcessClass($className);
            }
            if (!class_exists($className)) {
                continue;
            }
            if (method_exists($className, 'getObjectProcessData')) {
                $data = $className::getObjectProcessData($p['id'], $id_object, $object_name);
                if (!is_null($data)) {
                    $return[] = array(
                        'id_process'   => (int) $p['id'],
                        'process_name' => $p['title'],
                        'id_object'    => $id_object,
                        'object_name'  => $object_name,
                        'data'         => $data
                    );
                }
            }
        }
        return $return;
    }

    public static function getProcessObjectsStatusInfos($id_process, $object_name = null)
    {
        $className = self::getClassByProcessId($id_process);
        if (!is_null($className)) {
            $method = 'getObjectsStatusInfos';
            if (method_exists($className, $method)) {
                return $className::{$method}($id_process, $object_name);
            }
        }

        return null;
    }

    // Installation:

    public function addProcessParameters($parameters)
    {
        global $db;
        $bdb = new BDSDb($db);

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
        $bdb = new BDSDb($db);

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
        $bdb = new BDSDb($db);

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

    // Outils divers

    protected function findProductsInCategory($id_category, $search_in_children = true)
    {
        $categories = array($id_category);
        if ($search_in_children) {
            $categories = BimpTools::merge_array($categories, $this->findChildrenCategories($id_category));
        }

        $products = array();
        foreach ($categories as $id_cat) {
            $ids = $this->db->getValues('categorie_product', 'fk_product', '`fk_categorie` = ' . (int) $id_cat);
            if (!is_null($ids) && count($ids)) {
                foreach ($ids as $id_product)
                    if (!in_array((int) $id_product, $products)) {
                        $products[] = (int) $id_product;
                    }
            }
        }
        return $products;
    }

    protected function findChildrenCategories($id_parent)
    {
        $categories = BDS_Tools::getChildrenCategoriesIds($this->db, $id_parent);

        foreach ($categories as $idc) {
            $subCats = $this->findChildrenCategories($idc);
            foreach ($subCats as $idsc) {
                if (!in_array($idsc, $categories)) {
                    $categories[] = (int) $idsc;
                }
            }
        }

        return $categories;
    }

    public function getParameterLabelByName($name)
    {
        $where = '`id_process` = ' . (int) $this->processDefinition->id;
        $where .= ' AND `name` = \'' . $name . '\'';

        $label = $this->db->getValue('bds_process_parameter', 'label', $where);
        if (is_null($label) || !$label) {
            return $name;
        }

        return $label;
    }

    protected function cleanTempDirectory($subDir = '')
    {
//        $dir = DOL_DOCUMENT_ROOT . 'bimpdatasync/temp_files' . ($subDir ? '/' . $subDir : '');
//
//        if (!file_exists($dir)) {
//            return;
//        }
//
//        $files = scandir($dir);
//
//        if (is_array($files)) {
//            foreach ($files as $f) {
//                if (in_array($f, array('.', '..'))) {
//                    continue;
//                }
//
//                if (is_dir($dir . '/' . $f)) {
//                    $this->cleanTempDirectory($subDir . '/' . $f);
//                } else {
//                    unlink($dir . '/' . $f);
//                }
//            }
//        }
//
//        if ($subDir) {
//            rmdir($dir);
//        }
    }

    protected function ObjectError($object, $unset_errors = true)
    {
        $msg = '';
        if (isset($object->error) && $object->error) {
            $msg .= ' - Erreur: ' . html_entity_decode(htmlspecialchars_decode($this->langs->trans($object->error), ENT_QUOTES));
        }
        if (isset($object->errors) && is_array($object->errors) && count($object->errors)) {
            $msg .= '<br/>Erreurs:';
            foreach ($object->errors as $err) {
                $msg .= '<br/> - ' . html_entity_decode(htmlspecialchars_decode($this->langs->trans($err), ENT_QUOTES));
            }
        }
        if ($unset_errors) {
            $object->error = '';
            $object->errors = array();
        }
        return $msg;
    }

    protected function checkParameter($name, $type = '', $required = true)
    {
        if (!isset($this->parameters[$name]) || !$this->parameters[$name]) {
            if ($required) {
                $this->Error('Paramètre "' . $name . '" absent');
                return false;
            }
        }

        if ($type) {
            switch ($type) {
                case 'int':
                    if (!preg_match('/^\-?[0-9]+$/', $this->parameters[$name])) {
                        $this->Error('Paramètre "' . $name . '" invalide (Doit être un nombre entier)');
                        return false;
                    }
                    break;

                case 'float':
                    if (!preg_match('/^\-?[0-9]+\.?[0-9]*$/', $this->parameters[$name])) {
                        $this->Error('Paramètre "' . $name . '" invalide (Doit être un nombre décimal)');
                        return false;
                    }
                    break;
            }
        }
        return true;
    }
}

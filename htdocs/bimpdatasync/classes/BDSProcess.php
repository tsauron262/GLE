<?php

include_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';

abstract class BDSProcess
{
    public static $debug = false;
    public static $process_name = null;
    public static $files_dir_name = '';
    public static $memory_limit = '1000M';
    public static $objects = array();
    public $db = null;
    public $user = null;
    public $debug_content = '';
    public $use_references = false;
    public $report = null;
    public $langs = 0;
    public $filesDir = '';
    public $process = null;
    public $params = array();
    public $options = array(
        'debug' => false,
        'mode'  => ''
    );
    public $triggers = array();
    protected $references = array();
    public $params_ok = true;
    public $options_ok = true;
    public $current_object = array(
        'name'     => null,
        'id'       => null,
        'ref'      => null,
        'increase' => false
    );

    public function __construct(BDS_Process $process, $options = array(), $references = array())
    {
        set_time_limit(0);
        ini_set('memory_limit', static::$memory_limit);

        global $db;

        $this->options = BimpTools::overrideArray($this->options, $options);

        if (self::$debug) {
            $this->options['debug'] = true;
        }

        $this->start();

        $this->db = new BimpDb($db);

        global $user;

        if (!BimpObject::objectLoaded($user)) {
            $user = new User($db);
            $user->fetch(1);
        }

        $this->user = $user;

        if (!BimpObject::objectLoaded($process)) {
            $msg = 'Processus absent';
            $this->logError($msg);
            $this->Msg($msg, 'danger');
        } else {
            $this->process = $process;
        }

        // Chargement des paramètres du processus: 
        $parameters = $this->process->getChildrenObjects('params');
        foreach ($parameters as $p) {
            $value = (string) $p->getData('value');
            $name = (string) $p->getData('name');
            if ($name) {
                $this->params[$name] = $value;
            } else {
                $msg = 'Erreur de configuration: nom système absent pour le paramètre "';
                $msg .= $p->getData('label') . '" (ID ' . $p->id . ')';
                $this->Error($msg);
                $this->params_ok = false;
            }
        }

        if (!empty($references)) {
            $this->setReferences($references);
        }
    }

    public static function getClassName()
    {
        return 'BDSProcess';
    }

    public function deleteObjects($objects)
    {
        
    }

    public function start()
    {
        if ($this->options['debug']) {
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
    }

    public function end()
    {
        if (BimpObject::objectLoaded($this->report)) {
            $this->report->end();
        }

        if ($this->options['debug']) {
            $notifications = ob_get_clean();
            ini_set('display_errors', 0);
            error_reporting(E_ERROR);

            if ($notifications) {
                $this->debug_content .= '<h4>Notifications: </h4>' . $notifications;
            }
        }
    }

    // Déclenchement des opération: 

    public function initOperation($id_operation, &$errors)
    {
        $data = array(
            'id_process'      => $this->process->id,
            'id_operation'    => $id_operation,
            'use_report'      => true,
            'id_report'       => 0,
            'operation_title' => '',
            'debug_content'   => ''
        );

        if ($this->options['debug']) {
            $this->debug_content .= '<h3>' . $this->process->getData('title') . '</h3>';
        }

        if (is_null($id_operation) || !$id_operation) {
            $errors[] = 'ID de l\'opération absent';
        } else {
            $operation = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', (int) $id_operation);
            if (!BimpObject::objectLoaded($operation)) {
                $errors[] = 'L\'opération d\'ID ' . $id_operation . ' n\'existe plus';
            } else {
                $data['operation_title'] = $operation->getData('title');
                $data['use_report'] = (int) $operation->getData('use_report');

                // Vérification des options: 
                $options = $operation->getAssociatesObjects('options');
                foreach ($options as $option) {
                    if (!isset($this->options[$option->getData('name')])) {
                        if ((int) $option->getData('required')) {
                            $errors[] = 'Option obligatoire non spécifiée: "' . $option->getData('label') . '"';
                            $this->options_ok = false;
                        }
                    }
                }

                $method = 'init';
                $words = explode('_', $operation->getData('name'));
                foreach ($words as $word) {
                    $method .= ucfirst($word);
                }

                if (!method_exists($this, $method)) {
                    $errors[] = 'Erreur technique: méthode "' . $method . '" inexistante';
                }

                if (!count($errors)) {
                    // Exécution de l\'opération: 
                    $this->{$method}($data, $errors);
                    if (!count($errors) && $data['use_report']) {
                        $title = $this->process->getData('title') . ' - ' . $operation->getData('title') . ' du ' . date('d / m / Y');
                        $this->createReport($title, $this->options['mode'], $id_operation);
                        $data['id_report'] = $this->report->id;
                    }
                }
            }
        }

        if ($this->options['debug']) {
            $this->debug_content .= '<h4>Options: </h4><pre>';
            $this->debug_content .= print_r($this->options, 1);
            $this->debug_content .= '</pre>';
            $this->debug_content .= '<h4>Paramètres: </h4><pre>';
            $this->debug_content .= print_r($this->params, 1);
            $this->debug_content .= '</pre>';
            $this->debug_content .= '<h4>Données: </h4><pre>';
            $this->debug_content .= print_r($data, 1);
            $this->debug_content .= '</pre>';
        }

        $this->end(false);

        if (isset($this->options['debug']) && $this->options['debug']) {
            $html = BimpRender::renderFoldableContainer('[INITIALISATION]', $this->debug_content, array('open' => 0, 'offset_left' => true));
            $data['debug_content'] = $html;
        }

        return $data;
    }

    public function executeFullOperation($id_operation, &$errors = array())
    {
        $result = array(
            'debug_content' => '',
            'result_html'   => '',
            'id_report'     => 0
        );

        if (!$this->params_ok) {
            $errors[] = 'Certain paramètres sont incorrects';
            return $result;
        }

        $operation = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', (int) $id_operation);
        if (!BimpObject::objectLoaded($operation)) {
            $errors[] = 'L\'opération #' . $id_operation . ' n\'existe pas';
            return $result;
        }

        $data = $this->initOperation($id_operation, $errors);
        $result['id_report'] = BimpTools::getArrayValueFromPath($data, 'id_report', 0);

        $debug = $data['debug_content'];

        if (!count($errors) && $this->options_ok) {
            if (isset($data['result_html'])) {
                $result['result_html'] = $data['result_html'];
            } elseif (isset($data['steps']) && !empty($data['steps'])) {
                $method = 'execute';
                $words = explode('_', $operation->getData('name'));
                foreach ($words as $word) {
                    $method .= ucfirst($word);
                }
                if (!method_exists($this, $method)) {
                    $errors[] = 'Erreur technique - Méthode "' . $method . '" inexistante';
                } else {
                    $steps = $data['steps'];
                    $steps_done = array();

                    while (true) {
                        $done = false;
                        foreach ($steps as $step_name => $step_data) {
                            if (in_array($step_name, $steps_done)) {
                                continue;
                            }

                            $done = true;
                            $this->start();
                            $steps_done[] = $step_name;

                            $step_errors = array();
                            $this->debug_content = '';
                            $this->references = BimpTools::getArrayValueFromPath($step_data, 'elements', array());

                            if ($this->options['debug']) {
                                if (is_array($this->references) && !empty($this->references)) {
                                    $this->debug_content .= '<h4>Eléments: </h4>';
                                    $this->debug_content .= '<pre>';
                                    $this->debug_content .= print_r($this->references, 1);
                                    $this->debug_content .= '</pre>';
                                }
                            }

                            // Exécution de l'opération pour cette étape: 
                            $step_result = $this->{$method}($step_name, $step_errors);

//                            $this->DebugData($step_result, 'Resultat étape');

                            if (isset($step_result['new_steps'])) {
                                foreach ($step_result['new_steps'] as $new_step_name => $new_step_data) {
                                    $steps[$new_step_name] = $new_step_data;
                                }
                                $this->DebugData($steps, 'New steps');
                            }

                            $this->end();

                            if ($this->options['debug']) {
                                $debug .= BimpRender::renderFoldableContainer('Etape "' . BimpTools::getArrayValueFromPath($step_data, 'label', $step_name) . '"', $this->debug_content, array(
                                            'open'        => 0,
                                            'offset_left' => true
                                ));
                            }

                            if (count($step_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($step_errors, 'Etape "' . BimpTools::getArrayValueFromPath($step_data, 'label', $step_name) . '"');
                                if (BimpTools::getArrayValueFromPath($step_data, 'on_error', 'stop') !== 'continue') {
                                    break 2;
                                }
                            }

                            break;
                        }

                        if (!$done || count($steps_done) >= count($steps)) {
                            break;
                        }
                    }
                }
            }
        }

        $this->end();

        if ($this->options['debug'] && $debug) {
            $result['debug_content'] = BimpRender::renderFoldableContainer(BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos débug', $debug, array(
                        'id'          => 'processDebugContent',
                        'open'        => 0,
                        'offset_left' => true
            ));
        }

        return $result;
    }

    public function executeOperationStep($id_operation, $step_name, $id_report = 0, $iteration = 0)
    {
        $result = array();
        $errors = array();

        if ($this->options['debug']) {
            if (is_array($this->references) && !empty($this->references)) {
                $this->debug_content .= '<h4>Eléments: </h4>';
                $this->debug_content .= '<pre>';
                $this->debug_content .= print_r($this->references, 1);
                $this->debug_content .= '</pre>';
            }
        }

        if (BimpObject::objectLoaded($this->process)) {
            if ((int) $id_report && (!BimpObject::objectLoaded($this->report) || $this->report != $id_report)) {
                $this->report = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Report', (int) $id_report);
            }

            $operation = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', (int) $id_operation);
            if (!BimpObject::objectLoaded($operation)) {
                $msg = 'Erreur technique : l\'opération d\'ID ' . $id_operation . ' n\'existe plus';
                $errors[] = $msg;
                $this->Error($msg);
            } else {
                // Vérification des options: 
                $options = $operation->getAssociatesObjects('options');
                foreach ($options as $option) {
                    if (!isset($this->options[$option->getData('name')])) {
                        if ((int) $option->getData('required')) {
                            $errors[] = 'Option obligatoire non spécifiée: "' . $option->getData('label') . '"';
                            $this->options_ok = false;
                        }
                    }
                }

                if ($this->options_ok) {
                    // Excution de l'opération: 
                    $method = 'execute';
                    $words = explode('_', $operation->getData('name'));
                    foreach ($words as $word) {
                        $method .= ucfirst($word);
                    }
                    if (!method_exists($this, $method)) {
                        $errors[] = 'Erreur technique - Méthode "' . $method . '" inexistante';
                    } else {
                        $result = $this->{$method}($step_name, $errors);
                    }
                }
            }
        } else {
            $errors[] = 'Erreur technique: Définitions du processus absentes';
        }

        if ($this->options['debug']) {
            if (!empty($result)) {
                $this->debug_content .= '<h4>Résultat: </h4>';
                $this->debug_content .= '<pre>';
                $this->debug_content .= print_r($result, 1);
                $this->debug_content .= '</pre>';
            }
        }

        if (count($errors)) {
            $this->Error(BimpTools::getMsgFromArray($errors, 'Erreur(s) technique(s)'));
        }

        $this->end();

        if ($this->debug_content) {
            $title = 'Etape "' . $step_name . '"' . ($iteration ? ' #' . $iteration : '');
            $result['debug_content'] = BimpRender::renderFoldableContainer($title, $this->debug_content, array('open' => false, 'offset_left' => true));
        }

        if (!isset($result['errors'])) {
            $result['errors'] = array();
        }

        $result['errors'] = array_merge($result['errors'], $errors);
        return $result;
    }

    public function executeTriggerAction($action, $object)
    {
        if (is_null($this->report)) {
            $title = $this->process->getData('title') . ' - Opérations automatiques du ' . date('d / m / Y');
            $this->createReport($title, 'triggers');
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
        // todo: la fonction doit être revue suite à la nouvelle version
//        // On désactive la temporisation de sortie de manière à ce que le client puisse recevoir 
//        // les notifications, notamment en cas d'erreur fatale.
//        if ($this->options['debug']) {
//            ob_end_flush();
//        }
//
//        $return = array();
//
//        if (is_null($this->report)) {
//            $title = $this->process->getData('title') . ' - Requêtes d\'import du ' . date('d / m / Y');
//            $this->createReport($title, 'requests');
//        }
//
//        if (!isset($params['operation']) || !$params['operation']) {
//            $msg = 'Aucune opération spécifiée';
//            $errors[] = $msg;
//            $this->Error($msg);
//        } elseif (!method_exists($this, $params['operation'])) {
//            $msg = 'Action spécifiée non valide "' . $params['operation'] . '" ';
//            $errors[] = $msg;
//            $this->Error($msg);
//        } else {
//            $return = $this->{$params['operation']}($params, $errors);
//        }
//
//        $this->end();
//
//        return $return;
    }

    public function executeObjectProcess($action, BimpObject $object = null, $reference = '', $increase = true)
    {
        // todo: la fonction doit être revue suite à la nouvelle version
//        $this->setCurrentObject($object, $reference, $increase);
//
//        $title = $this->process->getData('title') . ' - Objet "' . $object_name . '"';
//        $title .= ' - ID ' . $id_object . ' - Le ' . date('d / m / Y à H:i:s');
//        $this->report = new BDSReport($this->process->id, $title, null);
//
//        $method = 'executeObject' . ucfirst($action);
//        if (method_exists($this, $method)) {
//            $this->{$method}($object_name, $id_object);
//        } else {
//            $this->Error('Erreur technique: méthode "' . $method . '" absente');
//        }
//
//        $return = array(
//            'id_report' => $this->report->id
//        );
//
//        $this->end();
//
//        if ($this->options['debug']) {
//            $html = '<div class="foldable_section closed">';
//            $html .= '<div class="foldable_section_caption">';
//            $html .= 'Données debug';
//            $html .= '</div>';
//            $html .= '<div class="foldable_section_content" id="debugContent">';
//            $html .= $this->debug_content;
//            $html .= '</div>';
//            $html .= '</div>';
//
//            $return['debug_content'] = $html;
//        }
//
//        return $return;
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
                BimpCore::loadPhpExcel();
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

    protected function ftpConnect($host, $login, $pword, $port = 21, $passive = true, &$errors = null)
    {
        $ftp = ftp_connect($host, $port);

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
        } else {
            ftp_pasv($ftp, false);
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

    public function createReport($title, $type, $id_operation = 0)
    {
        if (is_null($this->report)) {
            if (BimpObject::objectLoaded($this->process)) {
                $this->report = BimpObject::getInstance('bimpdatasync', 'BDS_Report');
                $this->report->validateArray(array(
                    'id_process'   => (int) $this->process->id,
                    'id_operation' => (int) $id_operation,
                    'type'         => $type,
                    'title'        => $title
                ));
            }

            $this->report->create();
        }
    }

    public function setCurrentObject($object = null, $reference = null, $increase = true)
    {
        $this->current_object = array(
            'module'   => '',
            'name'     => '',
            'id'       => 0,
            'ref'      => $reference,
            'increase' => $increase
        );

        if (is_a($object, 'BimpObject')) {
            $this->current_object['module'] = $object->module;
            $this->current_object['name'] = $object->object_name;

            if (BimpObject::objectLoaded($object)) {
                $this->current_object['id'] = (int) $object->id;
            }
        }
    }
    
    public function setCurrentObjectData($module, $object_name, $id_object = 0, $reference = null, $increase = true)
    {
        $this->current_object = array(
            'module'   => $module,
            'name'     => $object_name,
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

    public function Msg($msg, $class = 'info')
    {
//        if (!$this->options['debug'] && $class == 'danger') {
//            $this->logError($msg);
//            return;
//        }

        $this->debug_content .= BimpRender::renderAlerts($msg, $class);
    }

    public function DebugData($data, $title = '')
    {
        $msg = '';

        if ($title) {
            $msg .= '<h4>' . $title . '</h4>';
        }
        $msg .= '<pre>';
        $msg .= print_r($data, 1);
        $msg .= '</pre>';

        $this->debug_content .= $msg;
    }

    public function addReportRow($type, $msg, $object = null, $reference = '')
    {
        if (BimpObject::objectLoaded($this->report)) {
            if (is_array($msg)) {
                $msg = BimpTools::getMsgFromArray($msg);
            }

            $data = array(
                'type'       => $type,
                'msg'        => $msg,
                'ref'        => $reference,
                'obj_module' => '',
                'obj_name'   => '',
                'id_obj'     => 0
            );

            if (!is_null($object) && is_a($object, 'BimpObject')) {
                $data['obj_module'] = $object->module;
                $data['obj_name'] = $object->object_name;

                if (BimpObject::objectLoaded($object) || (isset($object->id) && (int) $object->id)) {
                    $data['id_obj'] = $object->id;
                }
            }

            $errors = array();

            $this->report->addLine($data, $errors);

            if (count($errors) && $this->options['debug']) {
                $this->Msg($errors, 'danger');
            }
        }
    }

    public function Alert($msg, $object = null, $reference = '')
    {
        $this->addReportRow('warning', $msg, $object, $reference);
        if ($this->options['debug']) {
            $this->Msg($msg, 'warning');
        }
    }

    public function Error($msg, $object = null, $reference = '')
    {
        $this->addReportRow('danger', $msg, $object, $reference);
        if ($this->options['debug']) {
            $this->Msg($msg, 'danger');
        }
    }

    public function SqlError($msg, $object = null, $reference = '')
    {
        $sqlError = $this->db->db->lasterror();
        if ($sqlError) {
            $msg .= ' - Erreur SQL: <span class="sqlError">' . $sqlError . '</span>';
        }
        $this->addReportRow('danger', $msg, $object, $reference);
        if ($this->options['debug']) {
            $this->Msg($msg, 'danger');
        }
    }

    public function Success($msg, $object = null, $reference = '')
    {
        $this->addReportRow('success', $msg, $object, $reference);
        if ($this->options['debug']) {
            $this->Msg($msg, 'success');
        }
    }

    public function Info($msg, $object = null, $reference = '')
    {
        $this->addReportRow('info', $msg, $object, $reference);
        if ($this->options['debug']) {
            $this->Msg($msg, 'info');
        }
    }

    // Gestion des incrémentations d'objet dans le rapport:

    public function incObjectData($data_name, $object = 'current')
    {
        if (BimpObject::objectLoaded($this->report)) {
            if ($object === 'current') {
                if ($this->current_object['module'] &&
                        $this->current_object['name'] &&
                        $this->current_object['increase']) {
                    $this->report->increaseObjectData($this->current_object['module'], $this->current_object['name'], $data_name);
                }
            } elseif (is_a($object, 'BimpObject')) {
                $this->report->increaseObjectData($object->module, $object->object_name, $data_name);
            }
        }
    }

    public function incProcessed($object = 'current')
    {
        $this->incObjectData('nbProcessed', $object);
    }

    public function incCreated($object = 'current')
    {
        $this->incObjectData('nbCreated', $object);
    }

    public function incUpdated($object = 'current')
    {
        $this->incObjectData('nbUpdated', $object);
    }

    public function incActivated($object = 'current')
    {
        $this->incObjectData('nbActivated', $object);
    }

    public function incDeactivated($object = 'current')
    {
        $this->incObjectData('nbDeactivated', $object);
    }

    public function incIgnored($object = 'current')
    {
        $this->incObjectData('nbIgnored', $object);
    }

    public function incDeleted($object = 'current')
    {
        $this->incObjectData('nbDeleted', $object);
    }

    // Gestion statique des processus:

    public static function createProcessByName($processName, &$errors = array(), $options = array(), $references = array())
    {
        global $db;
        $bdb = new BDSDb($db);

        $where = '`name` = \'' . $processName . '\'';
        $id_process = $bdb->getValue('bds_process', 'id', $where);
        if (is_null($id_process) || !$id_process) {
            $errors[] = 'Processus "' . $processName . '" non enregistré';
            return null;
        }

        return self::createProcessById($id_process, $errors, $options, $references);
    }

    public static function createProcessById($id_process, &$errors = array(), $options = array(), $references = array())
    {
        $process = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Process', (int) $id_process);
        if (!BimpObject::objectLoaded($process)) {
            $errors[] = 'Le processus d\'ID ' . $id_process . ' n\'existe pas';
            return null;
        }

        $className = 'BDS_' . $process->getData('name') . 'Process';
        if (!self::loadProcessClass($className)) {
            $errors[] = 'Classe "' . $className . '" inexistante';
            return null;
        }

        return new $className($process, $options, $references);
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
        $className = null;
        $process = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Process', (int) $id_process);

        if (BimpObject::objectLoaded($process)) {
            $name = $process->getData('name');
            if ($name) {
                $className = 'BDS_' . $name . 'Process';
                if (!class_exists($className)) {
                    self::loadProcessClass($className);
                    if (!class_exists($className)) {
                        $className = null;
                    }
                }
            }
        }

        return $className;
    }

    // Gestion statique des données objets

    public static function getObjectProcessesData($id_object, $object_name)
    {
        $processes = BimpCache::getBimpObjectObjects('bimpdatasync', 'BDS_Process');

        $return = array();
        foreach ($processes as $p) {
            $className = 'BDS_' . $p->getData('name') . 'Process';
            if (!class_exists($className)) {
                self::loadProcessClass($className);
            }
            if (!class_exists($className)) {
                continue;
            }
            if (method_exists($className, 'getObjectProcessData')) {
                $data = $className::getObjectProcessData($p->id, $id_object, $object_name);
                if (!is_null($data)) {
                    $return[] = array(
                        'id_process'   => (int) $p->id,
                        'process_name' => $p->getData('name'),
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
        $errors = array();

        $param = BimpObject::getInstance('bimpdatasync', 'BDS_ProcessParam');
        $errors = array();
        $param->deleteByParent($this->process->id, $errors, true);

        foreach ($parameters as $name => $data) {
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $this->process->id,
                'name'       => $name,
                'label'      => $data['label'],
                'value'      => (string) $data['value']
                    ), true, $errors);
        }
        return $errors;
    }

    public function addProcessOptions($options)
    {
        $errors = array();

        $option = BimpObject::getInstance('bimpdatasync', 'BDS_ProcessOption');
        $option->deleteByParent((int) $this->process->id, $errors, true);

        foreach ($options as $name => $data) {
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                'id_process'    => (int) $this->process->id,
                'name'          => $name,
                'label'         => BimpTools::getArrayValueFromPath($data, 'label', ''),
                'info'          => BimpTools::getArrayValueFromPath($data, 'info', ''),
                'type'          => BimpTools::getArrayValueFromPath($data, 'type', 'text'),
                'select_values' => BimpTools::getArrayValueFromPath($data, 'select_values', ''),
                'default_value' => BimpTools::getArrayValueFromPath($data, 'default_value', '')
                    ), true, $errors);
        }

        return $errors;
    }

    public function addProcessTriggerActions($triggers)
    {
        $errors = array();

        $trigger = BimpObject::getInstance('bimpdatasync', 'BDS_ProcessTrigger');
        $trigger->deleteByParent((int) $this->process->id, $errors, true);

        foreach ($triggers as $data) {
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                'id_process'  => (int) $this->process->id,
                'action_name' => BimpTools::getArrayValueFromPath($data, 'name', ''),
                'active'      => BimpTools::getArrayValueFromPath($data, 'active', 0),
                    ), true, $errors);
        }

        return $errors;
    }

    // Outils divers

    public function getParameterLabelByName($name)
    {
        $param = BimpCache::findBimpObjectInstance('bimpdatasync', 'BDS_ProcessParam', array(
                    'id_process' => (int) $this->process->id,
                    'name'       => $name
        ));

        if (BimpObject::objectLoaded($param)) {
            return (string) $param->getData('label');
        }

        return '';
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
        if (!isset($this->params[$name]) || !$this->params[$name]) {
            if ($required) {
                $this->Error('Paramètre "' . $name . '" absent');
                return false;
            }
        }

        if ($type) {
            switch ($type) {
                case 'int':
                    if (!preg_match('/^\-?[0-9]+$/', $this->params[$name])) {
                        $this->Error('Paramètre "' . $name . '" invalide (Doit être un nombre entier)');
                        return false;
                    }
                    break;

                case 'float':
                    if (!preg_match('/^\-?[0-9]+\.?[0-9]*$/', $this->params[$name])) {
                        $this->Error('Paramètre "' . $name . '" invalide (Doit être un nombre décimal)');
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    protected function updateParameter($name, $new_value)
    {
        $errors = array();

        if (BimpObject::objectLoaded($this->process)) {
            $param = BimpCache::findBimpObjectInstance('bimpdatasync', 'BDS_ProcessParam', array(
                        'id_process' => (int) $this->process->id,
                        'name'       => $name
                            ), true);

            if (BimpObject::objectLoaded($param)) {
                $errors = $param->updateField('value', $new_value);
                if (!count($errors)) {
                    $this->params[$name] = $new_value;
                }
            } else {
                $errors[] = 'Le paramètre "' . $name . '" n\'existe pas';
            }
        } else {
            $errors[] = 'ID du processus absent';
        }

        return $errors;
    }
}

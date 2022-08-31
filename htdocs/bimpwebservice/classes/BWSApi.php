<?php

class BWSApi
{

    protected $request_name = '';
    protected $ws_user = null;
    protected $params = array();
    protected $errors = array();
    public static $requests = array(
        'getObjectData'  => array(
            'desc'   => 'Retourne toutes les données d\'un objet',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'id'          => array('label' => 'ID de l\'objet', 'data_type' => 'id', 'require_if_missing' => 'ref'),
                'ref'         => array('label' => 'Référence de l\'objet', 'require_if_missing' => 'id')
            )
        ),
        'getObjectValue' => array(
            'desc'   => 'Retourne la valeur d\'un champ objet',
            'info'   => 'Fournir au moins l\'un des deux éléments parmi: ID, Référence',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'id'          => array('label' => 'ID de l\'objet', 'data_type' => 'id', 'require_if_missing' => 'ref'),
                'ref'         => array('label' => 'Référence de l\'objet', 'require_if_missing' => 'id'),
                'field'       => array('label' => 'Champ', 'required' => 1)
            )
        ),
        'getObjectsList' => array(
            'desc'   => 'Retourne une liste d\'IDs d\'objets selon les filtres indiqués',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'filters'     => array('label' => 'Filtres'),
                'order_by'    => array('label' => 'Trier par'),
                'order_way'   => array('label' => 'Sens du trie')
            )
        ),
        'createObject'   => array(
            'desc'   => 'Ajoute un objet',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'data'        => array('label' => 'Données')
            )
        ),
        'updateObject'   => array(
            'desc'   => 'Met à jour un objet selon les données indiquées',
            'info'   => 'Fournir au moins l\'un des deux éléments parmi: ID, Référence',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'id'          => array('label' => 'ID de l\'objet', 'data_type' => 'id', 'require_if_missing' => 'ref'),
                'ref'         => array('label' => 'Référence de l\'objet', 'require_if_missing' => 'id'),
                'data'        => array('label' => 'Données')
            )
        ),
        'deleteObject'   => array(
            'desc'   => 'Supprime un objet',
            'info'   => 'ID de l\'objet obligatoire pour cette opération',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'id'          => array('label' => 'ID de l\'objet', 'required' => 1)
            )
        )
    );

    public function __construct($request_name, $params)
    {
        $this->request_name = $request_name;
        $this->params = $params;

        ini_set('display_errors', 0);
        error_reporting(E_ERROR);
        register_shutdown_function(array($this, 'onExit'));
        set_error_handler(array($this, 'handleError'), E_ALL);
    }

    // Getters statics: 

    public static function getRequestsArray()
    {
        $requests = array();

        foreach (self::$requests as $name => $data) {
            $requests[$name] = $name;
        }

        return $requests;
    }

    // Getters: 

    public function getErrors()
    {
        return $this->errors;
    }

    // Traitements: 

    public function init($login, $pword)
    {
        // check requête: 
        if (!isset(self::$requests[$this->request_name])) {
            $this->addError('REQUEST_INVALID', 'La reqête "' . $this->request_name . '" n\'existe pas');
            return false;
        }

        if (!method_exists($this, 'wsRequest_' . $this->request_name)) {
            BimpCore::addlog('Méthode absente pour la requête webservice "' . $this->request_name . '"', Bimp_Log::BIMP_LOG_URGENT, 'ws', null, array(), true);
            $this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
            return false;
        }

        // check user: 
        $this->ws_user = BimpCache::findBimpObjectInstance('bimpwebservice', 'BWS_User', array(
                    'email' => $login
        ));

        if (!BimpObject::objectLoaded($this->ws_user)) {
            $this->addError('LOGIN_INVALIDE', 'Identifiant ou mot de passe du compte utilisateur invalide');
            return false;
        }

        if (!$this->ws_user->checkPWord($pword)) {
            $this->addError('LOGIN_INVALIDE', 'Identifiant ou mot de passe du compte utilisateur invalide');
            return false;
        }

        // check params:
        if (!$this->checkParams()) {
            return false;
        }

        // check droit requête:
        $module = BimpTools::getArrayValueFromPath($this->params, 'module', 'any');
        $object_name = BimpTools::getArrayValueFromPath($this->params, 'object_name', 'any');

        if (!$this->ws_user->hasRight($this->request_name, $module, $object_name)) {
            $this->addError('UNAUTHORIZED', 'Opération non permise - ' . $module . ' - ' . $object_name);
            return false;
        }

        // Init USER 
        $id_user = (int) $this->ws_user->getIdUserErpUsed();
        if (!$id_user) {
            BimpCore::addlog('Utilisateur ERP associé non défini', Bimp_Log::BIMP_LOG_URGENT, 'ws', $this->ws_user, array(), true);
            $this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
            return false;
        }

        global $user, $db;
        $user = new User($db);
        $user->fetch($id_user);
        if (!BimpObject::objectLoaded($user)) {
            unset($user);
            BimpCore::addlog('Utilisateur ERP associé invalide', Bimp_Log::BIMP_LOG_URGENT, 'ws', $this->ws_user, array(
                'Erreur' => 'L\'utilisateur #' . $id_user . ' n\'existe pas'
                    ), true);
            $this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
            return false;
        }

        return true;
    }

    public function exec()
    {
        $response = array();

        if (!count($this->errors)) {
            $method = 'wsRequest_' . $this->request_name;
            if (method_exists($this, $method)) {
                $response = $this->{$method}();
            }
        }

        return $response;
    }

    public function addError($code, $message)
    {
        $this->errors[] = array(
            'code'    => $code,
            'message' => $message
        );
    }

    protected function checkParams()
    {
        $check = true;
        if (isset(self::$requests[$this->request_name]['params'])) {
            foreach (self::$requests[$this->request_name]['params'] as $param_name => $param_def) {
                $data_type = BimpTools::getArrayValueFromPath($param_def, 'data_type', 'string');

                $missing = false;

                if (!isset($this->params[$param_name])) {
                    $missing = true;
                } elseif (in_array($data_type, BC_Field::$missing_if_empty_types) && empty($this->params[$param_name])) {
                    $missing = true;
                } else {
                    $param_errors = array();
                    if (!BimpTools::checkValueByType($data_type, $this->params[$param_name], $param_errors)) {
                        if (empty($param_errors)) {
                            $param_errors[] = 'Format invalide';
                        }

                        $this->addError('INVALID_PARAMETER', BimpTools::getMsgFromArray($param_errors, 'Paramètre "' . BimpTools::getArrayValueFromPath($param_def, 'label', $param_name) . '"', true));
                        $check = false;
                        continue;
                    }
                }

                if ($missing) {
                    $required = false;
                    if (BimpTools::getArrayValueFromPath($param_def, 'required', 0)) {
                        $required = true;
                    } else {
                        $required_if_param_name = BimpTools::getArrayValueFromPath($param_def, '', '');
                        if ($required_if_param_name) {
                            $required_if_data_type = BimpTools::getArrayValueFromPath(self::$requests, $this->request_name . '/params/' . $required_if_param_name . '/data_type', 'string');
                            if (!isset($this->params[$required_if_param_name]) || (in_array($required_if_data_type, BC_Field::$missing_if_empty_types) && empty($this->params[$required_if_param_name]))) {
                                $required = true;
                            }
                        }
                    }
                    if ($required) {
                        $check = false;
                        $this->addError('MISSING_PARAMETER', 'Paramètre obligatoire absent: "' . BimpTools::getArrayValueFromPath($param_def, 'label', $param_name) . '" (' . $param_name . ')');
                    }
                }
            }
        }
        return $check;
    }

    protected function getObjectInstance()
    {
        $module = BimpTools::getArrayValueFromPath($this->params, 'module', 'any');
        $object_name = BimpTools::getArrayValueFromPath($this->params, 'object_name', 'any');
        return BimpObject::getInstance($module, $object_name);
    }

    protected function findObject(BimpObject $obj_instance = null)
    {
        if (is_null($obj_instance)) {
            $obj_instance = $this->getObjectInstance();
        }

        if (isset(self::$requests[$this->request_name]['params']['id'])) {
            $id_object = (int) BimpTools::getArrayValueFromPath($this->params, 'id', 0);

            if ($id_object) {
                $object = BimpCache::getBimpObjectInstance($obj_instance->module, $obj_instance->object_name, $id_object);

                if (!BimpObject::objectLoaded($object)) {
                    $this->addError('UNFOUND', 'Aucun' . $obj_instance->e() . ' ' . $obj_instance->getLabel() . ' trouvé' . $obj_instance->e() . ' pour l\'ID ' . $id_object);
                    return null;
                }
                return $object;
            } elseif (BimpTools::getArrayValueFromPath(self::$requests, $this->request_name . '/params/id/required', 0)) {
                $this->addError('OBJECT_IDENTIFIER_MISSING', 'Veuillez renseigner l\'identifiant de l\'objet demandé');
                return null;
            }
        }

        if (isset(self::$requests[$this->request_name]['params']['ref'])) {
            $ref_object = BimpTools::getArrayValueFromPath($this->params, 'ref', '');

            if ($ref_object) {
                $ref_prop = $obj_instance->getRefProperty();

                if (!$ref_prop) {
                    $this->addError('NO_REF_PROPERTY', 'Aucun champ de type "Référence" pour les ' . $obj_instance->getLabel('name_plu') . '. Veuillez renseigner un ID');
                } else {
                    $object = BimpCache::findBimpObjectInstance($obj_instance->module, $obj_instance->object_name, array(
                                $ref_prop => $ref_object
                    ));

                    if (!BimpObject::objectLoaded($object)) {
                        $this->addError('UNFOUND', 'Aucun' . $obj_instance->e() . ' ' . $obj_instance->getLabel() . ' trouvé' . $obj_instance->e() . ' pour la référence "' . $ref_object . '"');
                        return null;
                    }
                    return $object;
                }
            } else {
                $this->addError('OBJECT_IDENTIFIER_MISSING', 'Veuillez renseigner l\'identifiant ou la référence de l\'objet demandé');
                return null;
            }
        }

        if (!count($this->errors)) {
            $this->addError('UNFOUND', 'Aucun' . $obj_instance->e() . ' ' . $obj_instance->getLabel() . ' trouvé' . $obj_instance->e());
        }

        return null;
    }

    public function handleError($level, $msg, $file, $line)
    {
        global $bimp_errors_handle_locked;

        if ($bimp_errors_handle_locked) {
            return;
        }

        ini_set('display_errors', 0);

        switch ($level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                $txt = '';
                $txt .= '<strong>ERP:</strong> ' . DOL_URL_ROOT . "\n";
                $txt .= '<strong>Requête:</strong> ' . $this->request_name . "\n";

                if (is_a($this->ws_user, 'BWS_User') && BimpObject::objectLoaded($this->ws_user)) {
                    $txt .= '<strong>Utilisateur ws:</strong> #' . $this->ws_user->id . ' - ' . $this->ws_user->getName() . ' (' . $this->ws_user->getData('email') . ')' . "\n";
                }

                $txt .= "\n";

                $txt .= 'Le <strong>' . date('d / m / Y') . ' à ' . date('H:i:s') . "\n\n";
                $txt .= $file . ' - Ligne ' . $line . "\n\n";
                $txt .= $msg;

                if (!empty($this->params)) {
                    $txt .= "\n\n";
                    $txt .= 'Params requête: ' . "\n";
                    $txt .= '<pre>' . print_r($this->params, 1) . '</pre>';
                }

                mailSyn2('ERREUR FATALE WEBSERVICE - ' . str_replace('/', '', DOL_URL_ROOT), BimpCore::getConf('devs_email'), 'f.martinez@bimp.fr', $txt);

                BimpCore::addlog('ERREUR FATALE WEBSERVICE - ' . $msg, Bimp_Log::BIMP_LOG_URGENT, 'ws', null, array(
                    'Requête' => $this->request_name,
                    'User ws' => (BimpObject::objectLoaded($this->ws_user) ? $this->ws_user->getLink() : 'Non défini'),
                    'Fichier' => $file,
                    'Ligne'   => $line
                ));

                die(json_encode(array(
                    'errors' => array(
                        'code'    => 'INTERNAL_ERROR',
                        'message' => 'Erreur interne - opération indisponible pour le moment'
                    )
                )));
                break;
        }
    }

    public function onExit()
    {
        $error = error_get_last();

        // On cache les identifiants de la base
        $error = preg_replace('/mysqli->real_connect(.*)3306/', 'mysqli->real_connect(adresse_caché, login_caché, mdp_caché, bdd_caché, port_caché', $error);

        if (isset($error['type']) && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR))) {
            $this->handleError(E_ERROR, $error['message'], $error['file'], $error['line']);
        }
    }

    // Requêtes: 

    public function wsRequest_getObjectData()
    {
        $response = array();

        if (!count($this->errors)) {
            $object = $this->findObject();
            if (BimpObject::objectLoaded($object)) {
                $response = array('object_data' => $object->getDataArray(true));
            }
        }

        return $response;
    }

    public function wsRequest_getObjectValue()
    {
        $response = array();

        if (!count($this->errors)) {
            $obj_instance = $this->getObjectInstance();
            $field = BimpTools::getArrayValueFromPath($this->params, 'field', '');
            if (!$field) {
                $this->addError('MISSING_PARAMETER', 'Paramètre "field" absent');
            } else {
                if (!$obj_instance->field_exists($field)) {
                    $this->addError('INVALID_PARAMETER', 'Le champ "' . $field . '" n\'existe pas pour les ' . $obj_instance->getLabel('name_plur'));
                } else {
                    $object = $this->findObject($obj_instance);
                    if (BimpObject::objectLoaded($object)) {
                        $response = array(
                            $field => $object->getData($field)
                        );
                    }
                }
            }
        }

        return $response;
    }

    public function wsRequest_getObjectsList()
    {
        $response = array();

        if (!count($this->errors)) {
            $filters = BimpTools::getArrayValueFromPath($this->params, 'filters', '');

            if ($filters) {
                $filters = json_decode($filters, 1);
            }

            if (empty($filters)) {
                $this->addError('INVALID_PARAMETERS', 'Vous devez obligatoirement spécifier au moins un filtre');
            }

            $obj_instance = $this->getObjectInstance();
            $primary = $obj_instance->getPrimary();

            $order_by = BimpTools::getArrayValueFromPath($this->params, 'order_by', $primary);
            $order_way = BimpTools::getArrayValueFromPath($this->params, 'order_way', 'ASC');

            $rows = $obj_instance->getList($filters, null, null, $order_by, $order_way, 'array', array($primary));
            $list = array();

            if (!empty($rows)) {
                foreach ($rows as $r) {
                    $list[] = (int) $r[$primary];
                }
            }

            $response['list'] = $list;
        }

        return $response;
    }

    public function wsRequest_createObject()
    {
        $response = array();

        if (!count($this->errors)) {
            $obj_instance = $this->getObjectInstance();
            $data = BimpTools::getArrayValueFromPath($this->params, 'data', '');

            if ($data) {
                $data = json_decode($data, 1);
            }

            if (empty($data)) {
                $this->addError('INVALID_PARAMETERS', 'Données absentes pour la création ' . $obj_instance->getLabel('of_the'));
            }

            $errors = array();
            $warnings = array();
            $obj = BimpObject::createBimpObject($obj_instance->module, $obj_instance->object_name, $data, true, $errors, $warnings, false, true);

            if (!BimpObject::objectLoaded($obj)) {
                $this->addError('FAIL', BimpTools::getMsgFromArray($errors, null, true));
            } else {
                $response = array('success' => 1, 'id' => $obj->id);
            }
        }

        return $response;
    }

    public function wsRequest_updateObject()
    {
        $response = array();

        if (!count($this->errors)) {
            $obj_instance = $this->getObjectInstance();
            $object = $this->findObject($obj_instance);
            if (BimpObject::objectLoaded($object)) {
                $data = BimpTools::getArrayValueFromPath($this->params, 'data', '');

                if ($data) {
                    $data = json_decode($data, 1);
                }

                if (empty($data)) {
                    $this->addError('INVALID_PARAMETERS', 'Données à mettre à jour absentes pour l\'édition' . $obj_instance->getLabel('of_the'));
                } else {
                    $errors = $object->validateArray($data);
                    if (!count($errors)) {
                        $warnings = array();
                        $errors = $object->update($warnings, true);
                    }

                    if (count($errors)) {
                        $this->addError('FAIL', BimpTools::getMsgFromArray($errors, 'Echec de la mise à jour ' . $object->getLabel('of_the') . ' ' . $object->getRef(true), true));
                    } else {
                        $response = array('success' => 1, 'id' => $object->id);
                    }
                }
            }
        }

        return $response;
    }

    public function wsRequest_deleteObject()
    {
        $response = array();

        if (!count($this->errors)) {
            $obj_instance = $this->getObjectInstance();
            $object = $this->findObject($obj_instance);
            if (BimpObject::objectLoaded($object)) {
                $ref_object = $object->getRef(true);
                $warnings = array();
                $errors = $object->delete($warnings, true);

                if (count($errors)) {
                    $this->addError('FAIL', BimpTools::getMsgFromArray($errors, 'Echec de la suppression ' . $obj_instance->getLabel('of_the') . ' ' . $ref_object, true));
                } else {
                    $response = array('success' => 1);
                }
            }
        }

        return $response;
    }
}

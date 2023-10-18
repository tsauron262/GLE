<?php

/*
 * curl -k -X POST -H "BWS-LOGIN: cGV0ZXJAYmltcC5mcg==" "https://erp.bimp.fr/bimp8/bimpwebservice/request.php?req=authenticate" -F pword=cVpwYlc4M0pTb0M2




  curl -k -X POST -H "BWS-TOKEN: Zkd5Y0RiTFhBek4xOTByWU5PVDBxVjM1" -H "BWS-LOGIN: cGV0ZXJAYmltcC5mcg==" "https://erp.bimp.fr/bimp8/bimpwebservice/request.php?req=getContractInfo"
 */

class BWSApi
{

    protected $request_name = '';
    protected $ws_user = null;
    protected $params = array();
    protected $errors = array();
    protected $check_erp_user_rights = true;
    public static $requests = array(
        'authenticate'   => array(
            'desc'   => 'Authentification',
            'params' => array(
                'pword' => array('label' => 'Mot de passe', 'required' => 1)
            )
        ),
        'getObjectData'  => array(
            'desc'   => 'Retourne toutes les données d\'un objet',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'id'          => array('label' => 'ID de l\'objet', 'data_type' => 'id', 'require_if_missing' => 'ref'),
                'ref'         => array('label' => 'Référence de l\'objet', 'require_if_missing' => 'id'),
                'children'    => array('label' => 'Objets enfants à retourner')
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
        ),
        'findClient'     => array(
            'desc'   => 'Retourne les données d\'un client',
            'params' => array(
                'code' => array('Code client'),
//                'nom'   => array('label' => 'Nom'),
//                'tel'   => array('label' => 'Numéro de téléphone'),
//                'email' => array('label' => 'Adresse e-mail')
            )
        ),
//        'findContactsClient' => array(
//            'desc'   => 'Retourne une liste de contacts clients selon les termes de recherche',
//            'params' => array(
//                'code'    => array('Code client'),
//                'nom'     => array('label' => 'Nom'),
//                'prenom'  => array('label' => 'Prénom'),
//                'tel'     => array('label' => 'Numéro de téléphone'),
//                'email'   => array('label' => 'Adresse e-mail'),
//                'address' => array('label' => 'Adresse'),
//                'zip'     => array('label' => 'Code postal'),
//                'town'    => array('label' => 'Ville'),
//            )
//        )
    );

    public static function getInstance($request_name, $params)
    {
        $class_name = 'BWSApi';

        if (defined('BIMP_EXTENDS_VERSION')) {
            if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . BIMP_EXTENDS_VERSION . '/classes/BWSApi.php')) {
                $class_name = 'BWSApi_ExtVersion';

                if (!class_exists($class_name)) {
                    require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . BIMP_EXTENDS_VERSION . '/classes/BWSApi.php';
                }
            }
        }

        if (BimpCore::getExtendsEntity() != '') {
            if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/BWSApi.php')) {
                $class_name = 'BWSApi_ExtEntity';

                if (!class_exists($class_name)) {
                    require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/BWSApi.php';
                }
            }
        }

        return new $class_name($request_name, $params);
    }

    public function __construct($request_name, $params)
    {
        $this->request_name = $request_name;
        $this->params = $params;
        $this->check_erp_user_rights = (int) BimpCore::getConf('check_erp_user_rights', null, 'bimpwebservice');

        ini_set('display_errors', 0);
        error_reporting(E_ERROR);
        register_shutdown_function(array($this, 'onExit'));
        set_error_handler(array($this, 'handleError'), E_ALL);
    }

    // Getters statics: 

    public static function getRequestsArray()
    {
        $requests = array();

        foreach (static::$requests as $name => $data) {
            $requests[$name] = $name;
        }

        return $requests;
    }

    // Getters: 

    public function getParam($param_path, $default_value = null)
    {
        return BimpTools::getArrayValueFromPath($this->params, $param_path, $default_value);
    }

    public function setParam($param_path, $value)
    {
        return BimpTools::setArrayValueFromPath($this->params, $param_path, $value);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    // Traitements: 

    public function init($login, $token)
    {
        // check requête: 
        if (!isset(self::$requests[$this->request_name])) {
            $this->addError('REQUEST_INVALID', 'La requête "' . $this->request_name . '" n\'existe pas');
            return false;
        }

        if (!method_exists($this, 'wsRequest_' . $this->request_name)) {
            BimpCore::addlog('Méthode absente pour la requête webservice "' . $this->request_name . '"', Bimp_Log::BIMP_LOG_URGENT, 'ws', null, array(), true);
            $this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
            return false;
        }

        // check user: 
        $login = base64_decode($login);
        $this->ws_user = BimpCache::findBimpObjectInstance('bimpwebservice', 'BWS_User', array(
                    'email' => $login
        ));

        if (!BimpObject::objectLoaded($this->ws_user)) {
            $this->addError('LOGIN_INVALIDE', 'Identifiant ou mot de passe du compte utilisateur invalide (L: ' . $login . ')');
            return false;
        }

        if ($this->request_name !== 'authenticate') {
            $token = base64_decode($token);
            if (!$this->ws_user->checkToken($token)) {
                $this->addError('TOKEN_INVALIDE', 'Token invalide ou arrivé à expiration');
                return false;
            }
        }

        // check params:
        if (isset(self::$requests[$this->request_name]['params'])) {
            if (!$this->checkParams(self::$requests[$this->request_name]['params'])) {
                return false;
            }
        }

        // check droit requête:
        if ($this->request_name !== 'authenticate') {
            $module = BimpTools::getArrayValueFromPath($this->params, 'module', 'any');
            $object_name = BimpTools::getArrayValueFromPath($this->params, 'object_name', 'any');

            if (!$this->ws_user->hasRight($this->request_name, $module, $object_name)) {
                $this->addError('UNAUTHORIZED', 'Opération non permise');
                return false;
            }
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

    protected function checkParams($params_def, $path = '')
    {
        $check = true;
        foreach ($params_def as $param_name => $param_def) {
            $data_type = BimpTools::getArrayValueFromPath($param_def, 'data_type', 'string');

            $missing = false;

            $param = BimpTools::getArrayValueFromPath($this->params, $path . $param_name);

            if (is_null($param)) {
                $missing = true;
            } elseif (in_array($data_type, BC_Field::$missing_if_empty_types) && empty($param)) {
                $missing = true;
            } else {
                $param_errors = array();
                if (!BimpTools::checkValueByType($data_type, $param, $param_errors)) {
                    if (empty($param_errors)) {
                        $param_errors[] = 'Format invalide';
                    }

                    $this->addError('INVALID_PARAMETER', BimpTools::getMsgFromArray($param_errors, 'Paramètre "' . BimpTools::getArrayValueFromPath($param_def, 'label', $param_name) . '"', true));
                    $check = false;
                    continue;
                } else {
                    $this->setParam($path . $param_name, $param);
                }
            }

            if ($missing) {
                $required = false;
                if (BimpTools::getArrayValueFromPath($param_def, 'required', 0)) {
                    $required = true;
                } else {
                    $required_if_param_name = BimpTools::getArrayValueFromPath($param_def, 'required_if', '');
                    if ($required_if_param_name) {
                        $required_if_param_path = '';
                        foreach (explode('/', $required_if_param_name) as $p) {
                            $required_if_param_path .= ($required_if_param_path ? '/sub_params/' : '') . $p;
                        }
                        $required_if_data_type = BimpTools::getArrayValueFromPath(self::$requests, $this->request_name . '/params/' . $required_if_param_path . '/data_type', 'string');
                        $required_if_value = $this->getParam($required_if_param_name);
                        if (is_null($required_if_value) ||
                                (in_array($required_if_data_type, BC_Field::$missing_if_empty_types) && empty($required_if_value)) ||
                                ($required_if_data_type === 'bool' && (int) $required_if_value)) {
                            $required = true;
                        }
                    }
                }
                if ($required) {
                    $check = false;
                    $this->addError('MISSING_PARAMETER', 'Paramètre obligatoire absent: "' . BimpTools::getArrayValueFromPath($param_def, 'label', $param_name) . '" (' . $path . $param_name . ')');
                }
            }

            if (isset($param_def['sub_params'])) {
                if (!$this->checkParams($param_def['sub_params'], $path . $param_name . '/')) {
                    $check = false;
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

        $object = null;
        $id_object = 0;
        if (isset(self::$requests[$this->request_name]['params']['id'])) {
            $id_object = (int) BimpTools::getArrayValueFromPath($this->params, 'id', 0);

            if ($id_object) {
                $object = BimpCache::getBimpObjectInstance($obj_instance->module, $obj_instance->object_name, $id_object);

                if (!BimpObject::objectLoaded($object)) {
                    $this->addError('UNFOUND', 'Aucun' . $obj_instance->e() . ' ' . $obj_instance->getLabel() . ' trouvé' . $obj_instance->e() . ' pour l\'ID ' . $id_object);
                    return null;
                }
            } elseif (BimpTools::getArrayValueFromPath(self::$requests, $this->request_name . '/params/id/required', 0)) {
                $this->addError('OBJECT_IDENTIFIER_MISSING', 'Veuillez renseigner l\'identifiant de l\'objet demandé');
                return null;
            }
        }

        if (!$id_object && isset(self::$requests[$this->request_name]['params']['ref'])) {
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
                }
            } else {
                $this->addError('OBJECT_IDENTIFIER_MISSING', 'Veuillez renseigner l\'identifiant ou la référence de l\'objet demandé');
                return null;
            }
        }

        if (BimpObject::objectLoaded($object)) {
            if ($this->check_erp_user_rights) {
                if (!$object->can('view')) {
                    $this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir les données ' . $object->getLabel('of_the') . ' ' . $object->getRef(true));
                    return null;
                }
            }
            // Check objet selon les filtres autorisés:
            $ids = $this->checkObjectRightFilters($object, array($object->id));
            if (empty($ids)) {
                if (!count($this->errors)) {
                    $this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir les données ' . $object->getLabel('of_the') . ' ' . $object->getRef(true));
                }
                return null;
            }
            return $object;
        } elseif (!count($this->errors)) {
            $this->addError('UNFOUND', 'Aucun' . $obj_instance->e() . ' ' . $obj_instance->getLabel() . ' trouvé' . $obj_instance->e());
        }

        return null;
    }

    protected function checkObjectRightFilters($obj_instance, $ids_objects)
    {
        $valid_ids = array();

        if (is_a($this->ws_user, 'BWS_User') && is_a($obj_instance, 'BimpObject')) {
            $id_right = $this->ws_user->getRightId($this->request_name, $obj_instance->module, $obj_instance->object_name);
            if ($id_right) {
                $right = BimpCache::getBimpObjectInstance('bimpwebservice', 'BWS_ProfileRight', $id_right);
                if (BimpObject::objectLoaded($right)) {
                    $filters = $right->getData('obj_filters');
                    if (!empty($filters)) {
                        $filters_errors = array();
                        $bc_filters = new BC_FiltersPanel($obj_instance);
                        $bc_filters->setFilters($filters);
                        $valid_ids = $bc_filters->applyFiltersToObjectIds($ids_objects, $filters_errors);

                        if (count($filters_errors)) {
                            $this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
                            BimpCore::addlog('ERREUR WEBSERVICE', Bimp_Log::BIMP_LOG_URGENT, 'ws', $right, array(
                                'Message' => 'Erreurs lors de l\'application des filtres du droit webservice #' . $right->id,
                                'Requête' => $this->request_name,
                                'Module'  => $obj_instance->module,
                                'Objet'   => $obj_instance->object_name,
                                'Erreurs' => $filters_errors
                                    ), true);
                            return array();
                        }
                    } else {
                        $valid_ids = $ids_objects;
                    }
                }
            }
        }

        return $valid_ids;
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

                mailSyn2('ERREUR FATALE WEBSERVICE - ' . str_replace('/', '', DOL_URL_ROOT), BimpCore::getConf('devs_email', 'f.martinez@bimp.fr'), '', $txt);

                BimpCore::addlog('ERREUR FATALE WEBSERVICE - ' . $msg, Bimp_Log::BIMP_LOG_URGENT, 'ws', null, array(
                    'Requête' => $this->request_name,
                    'User ws' => (BimpObject::objectLoaded($this->ws_user) ? $this->ws_user->getLink() : 'Non défini'),
                    'Fichier' => $file,
                    'Ligne'   => $line
                ));

                die(json_encode(array(
                    'errors' => array(
                        array(
                            'code'    => 'INTERNAL_ERROR',
                            'message' => 'Erreur interne - opération indisponible pour le moment'
                        )
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

    protected function wsRequest_authenticate()
    {
        $response = array();

        if (!count($this->errors)) {
            $pword = $this->getParam('pword', '');

            if (!$this->ws_user->checkPWord($pword)) {
                $pword = base64_decode($this->getParam('pword', ''));

                if (!$this->ws_user->checkPWord($pword)) {
                    $this->addError('LOGIN_INVALIDE', 'Identifiant ou mot de passe du compte utilisateur invalide - (PW)');
                    return array();
                }
            }

            $errors = array();
            $response = $this->ws_user->generateToken($errors);

            if (count($errors)) {
                BimpCore::addlog('Erreur lors de la génération d\'un token d\'authentification', Bimp_Log::BIMP_LOG_ERREUR, 'ws', $this->ws_user, array(
                    'Erreurs' => $errors
                ));
                $this->addError('INTERNAL_ERROR', 'Une erreur est survenue - échec de l\'authentification');
            }
        }

        return $response;
    }

    protected function wsRequest_getObjectData()
    {
        $response = array();

        if (!count($this->errors)) {
            $object = $this->findObject();
            if (BimpObject::objectLoaded($object)) {
                // Check des children: 
                $children = BimpTools::getArrayValueFromPath($this->params, 'children', '');
                if ($children) {
                    $children = json_decode($children);
                }

                $children_data = array();
                if (!empty($children)) {
                    foreach ($children as $child_name) {
                        if (!$object->config->isDefined('objects/' . $child_name) || ($object->config->get('objects/' . $child_name . '/relation', '') !== 'hasMany')) {
                            $this->addError('INVALID_PARAMETER', 'L\'objet enfant "' . $child_name . '" n\'existe pas pour les ' . $object->getLabel('name_plur'));
                        } else {
                            $child = $object->getChildObject($child_name);
                            if (!is_a($child, 'BimpObject')) {
                                $this->addError('INVALID_PARAMETER', 'L\'obtention des données des objets enfants "' . $child_name . '" n\'est pas possible');
                            } elseif (!$this->ws_user->hasRight($this->request_name, $child->module, $child->object_name) ||
                                    ($this->check_erp_user_rights && !$child->can('view'))) {
                                $this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir les données des objets enfants "' . $child_name . '"');
                            } else {
                                $children_data[$child_name] = array();
                                foreach ($object->getChildrenObjects($child_name) as $child_object) {
                                    $children_data[$child_name][] = $child_object->getDataArray(true, $this->check_erp_user_rights);
                                }
                            }
                        }
                    }
                }

                $response = array(
                    'object_data' => $object->getDataArray(true, $this->check_erp_user_rights),
                    'children'    => $children_data
                );
            }
        }

        return $response;
    }

    protected function wsRequest_getObjectValue()
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
                        if ($this->check_erp_user_rights && !$object->canViewField($field)) {
                            $this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir la valeur du champ "' . $field . '"');
                        } else {
                            $response = array(
                                $field => $object->getData($field)
                            );
                        }
                    }
                }
            }
        }

        return $response;
    }

    protected function wsRequest_getObjectsList()
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

            $list = array();
            $obj_instance = $this->getObjectInstance();

            if ($this->check_erp_user_rights && !$obj_instance->can('view')) {
                $this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir une liste ' . $obj_instance->getLabel('of_plur'));
            } else {
                $primary = $obj_instance->getPrimary();

                $order_by = BimpTools::getArrayValueFromPath($this->params, 'order_by', $primary);
                $order_way = BimpTools::getArrayValueFromPath($this->params, 'order_way', 'ASC');

                $rows = $obj_instance->getList($filters, null, null, $order_by, $order_way, 'array', array($primary));

                if (!empty($rows)) {
                    foreach ($rows as $r) {
                        $list[] = (int) $r[$primary];
                    }
                }

                // Check liste: 
                $list = $this->checkObjectRightFilters($obj_instance, $list);
            }

            $response['list'] = $list;
        }

        return $response;
    }

    protected function wsRequest_createObject()
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
            $obj = BimpObject::createBimpObject($obj_instance->module, $obj_instance->object_name, $data, ($this->check_erp_user_rights ? false : true), $errors, $warnings, false, true);

            if (!BimpObject::objectLoaded($obj)) {
                $this->addError('FAIL', BimpTools::getMsgFromArray($errors, null, true));
            } else {
                $obj->addObjectLog('Créé par utilisateur webservice: ' . $this->ws_user->getLink());
                $response = array('success' => 1, 'id' => $obj->id);
            }
        }

        return $response;
    }

    protected function wsRequest_updateObject()
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
                        $errors = $object->update($warnings, ($this->check_erp_user_rights ? false : true));
                    }

                    if (count($errors)) {
                        $this->addError('FAIL', BimpTools::getMsgFromArray($errors, 'Echec de la mise à jour ' . $object->getLabel('of_the') . ' ' . $object->getRef(true), true));
                    } else {
                        $object->addObjectLog('Edition par utilisateur webservice: ' . $this->ws_user->getLink() . '<br/><br/>Données mises à jour: <pre>' . print_r($data) . '</pre>');
                        $response = array('success' => 1, 'id' => $object->id);
                    }
                }
            }
        }

        return $response;
    }

    protected function wsRequest_deleteObject()
    {
        $response = array();

        if (!count($this->errors)) {
            $obj_instance = $this->getObjectInstance();
            $object = $this->findObject($obj_instance);
            if (BimpObject::objectLoaded($object)) {
                $ref_object = $object->getRef(true);
                $warnings = array();
                $errors = $object->delete($warnings, ($this->check_erp_user_rights ? false : true));

                if (count($errors)) {
                    $this->addError('FAIL', BimpTools::getMsgFromArray($errors, 'Echec de la suppression ' . $obj_instance->getLabel('of_the') . ' ' . $ref_object, true));
                } else {
                    $response = array('success' => 1);
                }
            }
        }

        return $response;
    }

    protected function wsRequest_findClient()
    {
        $response = array();

        if (!count($this->errors)) {
            $code_client = $this->getParam('code', '');

            if (!$code_client) {
                $this->addError('INVALID_PARAMETERS', 'Veuillez renseigner le code client');
            } else {
                $client = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Client', array(
                            'code_client' => $code_client
                                ), true, false);

                if (!BimpObject::objectLoaded($client)) {
                    $this->addError('UNFOUND', 'Aucun client trouvé pour le code "' . $code_client . '"');
                } else {
                    $client_data = array(
                        'nom'         => (string) $client->getData('nom'),
                        'adresse'     => (string) $client->getData('address'),
                        'code_postal' => (string) $client->getData('zip'),
                        'ville'       => (string) $client->getData('town'),
                        'pays'        => (string) $client->displayDataDefault('fk_pays', 1, 1),
                        'tel'         => (string) $client->getData('phone'),
                        'email'       => (string) $client->getData('email'),
                        'contacts'    => array()
                    );

                    foreach ($client->getChildrenObjects('contacts') as $contact) {
                        $client_data['contacts'][] = array(
                            'nom'         => (string) $contact->getData('lastname'),
                            'prenom'      => (string) $contact->getData('firstname'),
//                            'adresse'     => (string) $contact->getData('address'),
//                            'code_postal' => (string) $contact->getData('zip'),
//                            'ville'       => (string) $contact->getData('town'),
//                            'pays'        => (string) $contact->displayDataDefault('fk_pays', 1, 1),
                            'tel_perso'   => (string) $contact->getData('phone_perso'),
                            'tel_mobile'  => (string) $contact->getData('phone_mobile'),
                            'tel_pro'     => (string) $contact->getData('phone'),
                            'poste'       => (string) $contact->getData('poste'),
                            'email'       => (string) BimpTools::cleanEmailsStr($contact->getData('email')),
                        );
                    }
                }

                $response = $client_data;
            }
        }

        return $response;
    }
}

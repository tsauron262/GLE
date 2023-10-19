<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class ErpAPI extends BimpAPI
{

    public static $name = 'erp';
    public static $allow_multiple_instances = true;
    public static $include_debug_json = false;
    public static $default_post_mode = 'array';
    public static $urls_bases = array();
    public static $requests = array(
        'authenticate'   => array(
            'label' => 'Authentification'
        ),
        'getObjectData'  => array(
            'label' => 'Obtenir les données d\'un objet'
        ),
        'getObjectValue' => array(
            'label' => 'Obtenir la valeur d\'un champ d\'un objet'
        ),
        'getObjectsList' => array(
            'label' => 'Obtenir une liste d\'ID d\'objets'
        ),
        'createObject'   => array(
            'label' => 'Créer un objet'
        ),
        'updateObject'   => array(
            'label' => 'Mettre à jour un objet'
        ),
        'deleteObject'   => array(
            'label' => 'Supprimer un objet'
        ),
        'findClient'     => array(
            'label' => 'Obtenir les données d\'un client'
        )
    );

    // Requêtes: 

    public function getObjectData($module, $object_name, $id_object = null, $ref_object = null, $children = array(), &$errors = array(), &$warnings = array())
    {
        if (!(int) $id_object && !(string) $ref_object) {
            $errors[] = 'Identifiant de l\'objet absent (ID ou référence)';
            return null;
        }

        $params = array(
            'fields' => array(
                'module'      => $module,
                'object_name' => $object_name,
                'id'          => $id_object,
                'ref'         => $ref_object
            )
        );

        if (!empty($children)) {
            $params['fields']['children'] = json_encode($children);
        }

        $response = $this->execCurl('getObjectData', $params, $errors);

        if (!count($errors)) {
            return $response;
        }

        return null;
    }

    public function getObjectValue($module, $object_name, $field, $id_object = null, $ref_object = null, &$errors = array(), &$warnings = array())
    {
        if (!(int) $id_object && !(string) $ref_object) {
            $errors[] = 'Identifiant de l\'objet absent (ID ou référence)';
            return null;
        }

        $params = array(
            'fields' => array(
                'module'      => $module,
                'object_name' => $object_name,
                'field'       => $field,
                'id'          => $id_object,
                'ref'         => $ref_object
            )
        );

        $response = $this->execCurl('getObjectValue', $params, $errors);

        if (!count($errors)) {
            if (isset($response[$field])) {
                return $response[$field];
            }
        }

        return null;
    }

    public function getObjectsList($module, $object_name, $filters, $order_by = null, $order_way = null, &$errors = array(), &$warnings = array())
    {
        $params = array(
            'fields' => array(
                'module'      => $module,
                'object_name' => $object_name,
                'filters'     => json_encode($filters),
            )
        );

        if (!is_null($order_by)) {
            $params['fields']['order_by'] = $order_by;
        }

        if (!is_null($order_way)) {
            $params['fields']['order_way'] = $order_way;
        }

        $response = $this->execCurl('getObjectsList', $params, $errors);

        if (!count($errors)) {
            if (isset($response['list'])) {
                return $response['list'];
            } else {
                $errors[] = 'Aucune réponse reçue';
            }
        }

        return null;
    }

    public function createObject($module, $object_name, $data, &$errors = array(), &$warnings = array())
    {
        $params = array(
            'fields' => array(
                'module'      => $module,
                'object_name' => $object_name,
                'data'        => json_encode($data)
            )
        );

        $response = $this->execCurl('createObject', $params, $errors);

        if (!count($errors)) {
            if (isset($response['id']) && (int) $response['id']) {
                return (int) $response['id'];
            }
        }

        return null;
    }

    public function updateObject($module, $object_name, $data, $id_object = null, $ref_object = null, &$errors = array(), &$warnings = array())
    {
        if (!(int) $id_object && !(string) $ref_object) {
            $errors[] = 'Identifiant de l\'objet absent (ID ou référence)';
            return false;
        }

        $params = array(
            'fields' => array(
                'module'      => $module,
                'object_name' => $object_name,
                'data'        => json_encode($data),
                'id'          => $id_object,
                'ref'         => $ref_object
            )
        );

        $response = $this->execCurl('updateObject', $params, $errors);

        if (!count($errors)) {
            if (isset($response['success']) && (int) $response['success']) {
                return true;
            }
        }

        return false;
    }

    public function deleteObject($module, $object_name, $id_object = null, &$errors = array(), &$warnings = array())
    {
        if (!(int) $id_object) {
            $errors[] = 'ID de l\'objet à supprimer absent';
            return false;
        }

        $params = array(
            'fields' => array(
                'module'      => $module,
                'object_name' => $object_name,
                'id'          => $id_object
            )
        );

        $response = $this->execCurl('deleteObject', $params, $errors);

        if (!count($errors)) {
            if (isset($response['success']) && (int) $response['success']) {
                return true;
            }
        }

        return false;
    }

    public function findClient($code_client, &$errors = array(), &$warnings = array())
    {
        $params = array(
            'fields' => array(
                'code' => $code_client,
            )
        );

        $response = $this->execCurl('findClient', $params, $errors);

        if (!count($errors)) {
            if (!empty($response)) {
                return $response;
            } else {
                $errors[] = 'Aucune réponse reçue';
            }
        }

        return null;
    }

    public function testRequest(&$errors = array(), &$warnings = array())
    {
        return $this->findClient('CLGLE040220', $errors, $warnings);
    }

    // Overrides: 

    public function connect(&$errors = array(), &$warnings = array())
    {
        if (!count($errors) && $this->isUserAccountOk($errors)) {
            $pword = $this->userAccount->getData('pword');
            $result = $this->execCurl('authenticate', array(
                'fields' => array(
                    'pword' => base64_encode($pword)
                )
                    ), $errors);

            if (isset($result['token']) && (string) $result['token']) {
                $expire = BimpTools::getArrayValueFromPath($result, 'expire', '');

                if (!$expire) {
                    $dt_now = new DateTime();
                    $dt_now->add(new DateInterval('PT720M'));
                    $expire = $dt_now->format('Y-m-d H:i:s');
                }

                $this->saveToken('auth', $result['token'], $expire);
            } elseif (!count($errors)) {
                $errors[] = 'Echec de la connexion pour une raison inconnue';
            }
        }

        return (!count($errors));
    }

    public function getDefaultRequestsHeaders($request_name, &$errors = array())
    {
        $headers = array();

        if ($this->isUserAccountOk($errors)) {
            $login = $this->userAccount->getData('login');

            if (!$login) {
                $errors[] = 'Login utilisateur absent';
            } else {
                $headers['BWS-LOGIN'] = base64_encode($login);
            }

            if ($request_name !== 'authenticate') {
                $token = $this->userAccount->getToken('auth');

                if (!$token) {
                    $errors[] = 'Token absent';
                } else {
                    $headers['BWS-TOKEN'] = base64_encode($token);
                }
            }
        }

        return $headers;
    }

    public function execCurl($request_name, $params = array(), &$errors = array(), &$response_headers = array(), &$response_code = -1)
    {
        $params['url_end'] = '/bimpwebservice/request.php';
        $params['header_out'] = false;
        $params['allow_reconnect'] = false;

        if (!isset($params['url_params'])) {
            $params['url_params'] = array();
        }

        $params['url_params']['req'] = $request_name;

        return parent::execCurl($request_name, $params, $errors, $response_headers, $response_code);
    }

    public function processRequestResponse($request_name, $response_code, $response_body, $response_headers = array(), &$infos = '', &$errors = array())
    {
        parent::processRequestResponse($request_name, $response_code, $response_body, $response_headers, $infos, $errors);

        if (isset($response_body['errors'])) {
            foreach ($response_body['errors'] as $error) {
                $errors[] = $error['message'] . (isset($error['code']) ? ' (Code: ' . $error['code'] . ')' : '');
            }
        }

        return $response_body;
    }

    public static function getDefaultApiTitle()
    {
        return 'ERP';
    }

    // Install: 
    public function install($title = '', &$warnings = array())
    {
        $errors = array();

        $api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
                    'name'  => 'erp',
                    'title' => ($title ? $title : $this->getDefaultApiTitle())
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($api)) {
            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'url_base_default_test',
                        'title'  => 'Base URL Test'
                            ), true, $warnings, $warnings);

            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'url_base_default_prod',
                        'title'  => 'Base URL Prod'
                            ), true, $warnings, $warnings);

            $param = BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
                        'id_api' => $api->id,
                        'name'   => 'external_erp_name',
                        'title'  => 'Nom ERP Externe'
                            ), true, $warnings, $warnings);
        }

        return $errors;
    }
}

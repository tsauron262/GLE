<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

class apiController extends BimpController
{

    public $api_name = '';
    public $api_idx = 0;
    public $api = null;

    public function setApi($api_name, $api_idx = 0, &$errors = array())
    {
        if (!is_null($this->api) && ($this->api->name !== $api_name || $this->api->idx !== $api_idx)) {
            $this->api = null;
        }

        if (is_null($this->api)) {
            $this->api_name = $api_name;
            $this->api_idx = $api_idx;
            $this->api = BimpAPI::getApiInstance($api_name, $api_idx);
        }

        return $this->isApiOk($errors);
    }

    public function isApiOk(&$errors = array())
    {
        if (!is_a($this->api, 'BimpAPI')) {
            $errors[] = 'API invalide: "' . $this->api_name . '"';
            return 0;
        }

        return $this->api->isOk();
    }

    public function displayHead()
    {
        if ($this->isApiOk()) {
            echo $this->api->renderJsVars();
        }
    }

    // Reqêtes

    public function apiRequest($api_name, $api_idx, $request_name, $method, $params, $options)
    {
        $errors = array();

        if (!$api_name) {
            $errors[] = 'Nom de l\'API absent';
        }

        if (!$request_name) {
            $errors[] = 'Nom de la requête absent';
        }

        if (!$method) {
            $errors[] = 'Nom de la fonction API absent';
        } elseif (!method_exists($this, $method)) {
            $errors[] = 'Fonction API invalide: "' . $method . '"';
        }

        if (!count($errors)) {
            if ($this->setApi($api_name, $api_idx, $errors)) {
                if ((int) BimpTools::getArrayValueFromPath($options, 'need_connection', 1)) {
                    if (!$this->api->isLogged()) {
                        $this->api->connect($errors);
                    }
                }

                if (!count($errors)) {
                    return $this->{$method}($request_name, $params, $options);
                }
            }
        }

        return array(
            'errors' => $errors
        );
    }

    protected function apiProcessRequest($request_name, $params, $options = array())
    {
        return $this->api->setRequest($request_name, $params);
    }

    // Gestion des requêtes via formulaire: 

    protected function apiLoadRequestForm($request_name, $params, $options = array())
    {
        $errors = array();
        $title = '';
        $html = '';

        $values = $this->api->getRequestFormValues($request_name, $params, $errors);

        if (!count($errors)) {
            $apiRequest = new BimpApiRequest($this->api, $request_name);

            if (count($apiRequest->errors)) {
                $errors[] = BimpTools::getMsgFromArray($apiRequest->errors, 'Echec génération du formulaire');
            } else {
                $html = $apiRequest->generateRequestFormHtml($values, $params);
            }
        }

        return array(
            'errors' => $errors,
            'title'  => $title,
            'html'   => $html
        );
    }

    protected function apiProcessRequestForm($request_name, $params = array(), $options = array())
    {
        $errors = array();
        $warnings = array();

        $apiRequests = new BimpApiRequest($this->api, $request_name);
        $fields = $apiRequests->processRequestForm();

        if (!count($apiRequests->errors)) {
            $errors = $this->api->requestFormFieldsOverride($request_name, $fields, $params, $warnings);

            if (!count($errors)) {
                $params['fields'] = $fields;
                $return = $this->api->setRequest($request_name, $params);

                if (!count($return['errors'])) {
                    $this->api->onRequestFormSuccess($request_name, $return['result'], $return['warnings']);
                }

                return $return;
            }
        } else {
            $errors[] = BimpTools::getMsgFromArray($apiRequests->errors, 'Erreurs lors du traitement des données');
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Ajax Process: 

    protected function ajaxProcessBimpApiRequest()
    {
        // Point d'entrée de toutes les requêtes ajax nécessitant une connexion à une API
        $api_name = BimpTools::getValue('api_name', '', 'aZ09comma');
        $api_idx = (int) BimpTools::getValue('api_idx', 0, 'int');
        $request_name = BimpTools::getValue('api_requestName', '', 'aZ09comma');
        $method = BimpTools::getValue('api_method', 'apiRequest', 'aZ09comma');
        $params = BimpTools::getValue('api_params', array(), 'json_nohtml');
        $options = BimpTools::getValue('api_options', array(), 'json_nohtml');

        if (is_string($params)) {
            $params = json_decode($params, 1);

            if (!is_array($params)) {
                $params = array();
            }
        }

        if (is_string($options)) {
            $options = json_decode($options, 1);

            if (!is_array($options)) {
                $options = array();
            }
        }

        $result = $this->apiRequest($api_name, $api_idx, $request_name, $method, $params, $options);

        if ((int) BimpTools::getArrayValueFromPath($options, 'need_connection', 1) && $this->isApiOk() && !$this->api->isLogged()) {
            return array(
                'errors'        => array('Vous n\'êtes pas connecté à l\'API "' . $this->api->options['public_name'] . '"'),
                'warnings'      => array(),
                'api_no_logged' => 1
            );
        }

        return $result;
    }

    protected function ajaxProcessBimpApiLogout()
    {
        $errors = array();
        $warnings = array();

        $api_name = BimpTools::getValue('api_name', '', 'aZ09comma');
        $api_idx = (int) BimpTools::getValue('api_idx', 0, 'int');

        if (!$api_name) {
            $errors[] = 'Type d\'API absent';
        } else {
            if ($this->setApi($api_name, $api_idx, $errors)) {
                $errors = $this->api->logout($warnings);
            }
        }

        return array(
            'errors'        => $errors,
            'warnings'      => array(),
            'api_no_logged' => 1,
//            'success_callback' => 'BimpApi.openLoginModal(null, \''.$api_name.'\');'
        );
    }
}

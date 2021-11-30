<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

class apiController extends BimpController
{

    public $api_name = '';
    public $api = null;

    public function setApi($api_name, &$errors = array())
    {
        if (!is_null($this->api) && $this->api->name !== $api_name) {
            $this->api = null;
        }

        if (is_null($this->api)) {
            $this->api_name = $api_name;
            $this->api = BimpAPI::getApiInstance($api_name);
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

    public function apiRequest($api_name, $request_name, $method, $params, $options)
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
            if ($this->setApi($api_name, $errors)) {
                return $this->{$method}($request_name, $params, $options);
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
        $button = null;

        $values = $this->api->getRequestFormValues($request_name, $params, $errors);

        if (!count($errors)) {
            $apiRequest = new BimpApiRequest($this->api, $request_name);

            if (count($apiRequest->errors)) {
                $errors[] = BimpTools::getMsgFromArray($apiRequest->errors, 'Echec génération du formulaire');
            } else {
                $html = $apiRequest->generateRequestFormHtml($values, $params);
                $title = $apiRequest->requestLabel;
            }
        }

        return array(
            'errors' => $errors,
            'title'  => $title,
            'html'   => $html,
            'button' => $button
        );
    }

    protected function apiProcessRequestForm($request_name, $params, $options = array())
    {
        $errors = array();
        $warnings = array();

        $apiRequests = new BimpApiRequest($this->api, $request_name);
        $result = $apiRequests->processRequestForm();

        $errors = $this->api->requestFormResultOverride($request_name, $result, $params, $warnings);

        if (!count($errors)) {
            if (is_array($result) && !empty($result)) {
                $params['fields'] = BimpTools::overrideArray($params['fields'], $result, false, true);
                return $this->api->setRequest($request_name, $params);
            } else {
                $errors[] = BimpTools::getMsgFromArray($apiRequests->errors, 'Erreurs lors du traitement des données');
            }
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
        $api_name = BimpTools::getValue('api_name', '');
        $request_name = BimpTools::getValue('api_requestName', '');
        $method = BimpTools::getValue('api_method', 'apiRequest');
        $params = BimpTools::getValue('api_params', array());
        $options = BimpTools::getValue('api_options', array());

        $result = $this->apiRequest($api_name, $request_name, $method, $params, $options);

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

        $api_name = BimpTools::getValue('api_name', '');

        if (!$api_name) {
            $errors[] = 'Type d\'API absent';
        } else {
            if ($this->setApi($api_name, $errors)) {
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

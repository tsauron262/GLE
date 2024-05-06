<?php

class BWS_User extends BimpObject
{

    protected $rights = null;

    // Droits users: 

    public function canView()
    {
        return BimpCore::isUserDev();
    }

    public function canCreate()
    {
        return BimpCore::isUserDev();
    }

    public function canEdit()
    {
        return BimpCore::isUserDev();
    }

    public function canDelete()
    {
        return BimpCore::isUserDev();
    }

    public function canSetAction($action): int
    {
        return BimpCore::isUserDev();
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('reinitPWord') && $this->canSetAction('reinitPWord')) {
            $buttons[] = array(
                'label'   => 'Réinitialiser le mot de passe',
                'icon'    => 'fas_redo',
                'onclick' => $this->getJsActionOnclick('reinitPWord', array(), array(
                    'confirm_msg' => 'Veuiilez confirmer la réinitialisation du mot de passe'
                ))
            );
        }
        $buttons[] = array(
            'label'   => 'Test requête',
            'icon'    => 'fas_vials',
            'onclick' => $this->getJsActionOnclick('testReq', array(), array(
                'form_name' => 'testReq'
            ))
        );

        return $buttons;
    }

    public function actionTestReq($data, &$success)
    {
        $errors = $warnings = array();

        $params = array();
        $req = BimpTools::getPostFieldValue('req', '', 'aZ09');

        $headers = array();
        $headers['BWS-LOGIN'] = base64_encode($this->getData('email'));
        if ($req == 'authenticate')
            $params['pword'] = $this->getData('pword');
        else {
            $headers['BWS-TOKEN'] = base64_encode($this->getData('token'));

//            print_r($_POST['extra_data']);
            $params['module'] = BimpTools::getPostFieldValue('module_name', '', 'aZ09');
            $params['object_name'] = BimpTools::getPostFieldValue('obj_name', '', 'aZ09');
            foreach ($_POST['extra_data'] as $name => $value) {
                if (stripos($name, 'show_') === false && stripos($name, 'add_') !== 0 && $name != 'module_name' && $name != 'obj_name' && $name != 'url' && $name != 'certif') {
                    $params[$name] = BimpTools::getPostFieldValue($name, '', 'alphanohtml');
                }
            }
//            $params['filters'] = BimpTools::getPostFieldValue('filters');
//            $filter1 = json_decode(BimpTools::getPostFieldValue('panel_filters'),1);
//            $filter2 = json_decode(BimpTools::getPostFieldValue('panel_filters2'),1);
//            $params['panel_filters'] = json_encode(BimpTools::merge_array($filter1, $filter2, true));
        }

        $url = $data['url'] . '?req=' . $req;
//        $url = 'https://erpi.bimp.fr/bimp8/bimpwebservice/request.php?req='.$req;


        $curl_str = 'curl -s -X POST ';
        foreach ($headers as $param => $value) {
            $curl_str .= ' -H "' . $param . ': ' . addslashes($value) . '"';
        }
        $curl_str .= ' \'' . $url . '\'';
        foreach ($params as $param => $value) {
            if ($value != '' && $value != 'any') {
                $curl_str .= ' -F ' . $param . '="' . addslashes($value) . '"';
            }
        }


        $headers_str = array();
        foreach ($headers as $header_name => $header_value) {
            $headers_str[] = $header_name . ': ' . $header_value;
        }
        $ch = curl_init('');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_str);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (!$data['certif']) {
            $curl_str .= ' -k';
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_PROXY_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
//        curl_setopt($ch, CURLOPT_HEADER, 0);

        $response = curl_exec($ch);

        if (BimpTools::getPostFieldValue('panel_filters', '') != '')
            $errors[] = 'Filtres JSON  : <textarea style="min-width: 1000px;min-height: 100px;">' . BimpTools::getPostFieldValue('panel_filters') . '</textarea>';
        $errors[] = 'Resultat : <textarea style="min-width: 1000px;min-height: 100px;">' . $response . '</textarea>';
        $errors[] = 'Req Curl : <textarea style="min-width: 1000px;min-height: 100px;">' . str_replace(";", ",", $curl_str) . '</textarea>';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Getters booléens: 

    public function hasRight($request_name, $module = 'any', $object_name = 'any')
    {
        return ($this->getRightId($request_name, $module, $object_name) ? 1 : 0);
    }

    // Getters données: 

    public function getMainProfile()
    {
        foreach ($this->getData('profiles') as $id_profile) {
            $profile = BimpCache::getBimpObjectInstance('bimpwebservice', 'BWS_Profile', $id_profile);
            if (BimpObject::objectLoaded($profile)) {
                return $profile;
            }
        }

        return null;
    }

    public function getRights()
    {
        if (is_null($this->rights)) {
            $this->rights = array();

            foreach ($this->getData('profiles') as $id_profile) {
                $profile = BimpCache::getBimpObjectInstance('bimpwebservice', 'BWS_Profile', $id_profile);

                if (BimpObject::objectLoaded($profile) && (int) $profile->getData('active')) {
                    $profile_rights = $profile->getChildrenObjects('rights');
                    foreach ($profile_rights as $right) {
                        $request = $right->getData('request_name');
                        $module = $right->getData('obj_module');
                        $object_name = $right->getData('obj_name');

                        if (!$module) {
                            $module = 'any';
                        }

                        if (!$object_name) {
                            $object_name = 'any';
                        }

                        if (!isset($this->rights[$request])) {
                            $this->rights[$request] = array();
                        }

                        if (!isset($this->rights[$request][$module])) {
                            $this->rights[$request][$module] = array();
                        }

                        $this->rights[$request][$module][$object_name] = $right->id;
                    }
                }
            }
        }

        return $this->rights;
    }

    public function list_req()
    {
        $rights = $this->getRights();
        $list = array();
        if (!defined('BWS_LIB_INIT')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/BWS_Lib.php';
        }
        foreach ($rights as $reqName => $data) {
            $list[$reqName] = BWSApi::$requests[$reqName]['desc'] . ' (' . $reqName . ')';
        }
        return $list;
    }

    public function getfields()
    {
        $fields = array();

        $fields[] = array(
            'show'       => 1,
            'custom'     => 1,
            'label'      => 'Url',
            'input_name' => 'url',
            'input'      => array(
                'type' => 'text'
            ),
            'value'      => $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['SERVER_NAME'] . '/' . DOL_URL_ROOT . '/bimpwebservice/request.php'
        );

        $fields[] = array(
            'show'       => 1,
            'custom'     => 1,
            'label'      => 'Verifier certificat',
            'input_name' => 'certif',
            'input'      => array(
                'type' => 'toggle'
            ),
            'value'      => 1
        );

        $fields[] = array(
            'show'       => 1,
            'custom'     => 1,
            'label'      => 'Requête',
            'input_name' => 'req',
            'input'      => array(
                'type' => 'select'
            ),
            'values'     => $this->list_req(),
            'help'       => 'Ne sont géré actuelement que Authenticate et getObjectList'
        );

        if (!defined('BWS_LIB_INIT')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/BWS_Lib.php';
        }
        foreach (BWSApi::$requests as $reqName => $data) {
            foreach ($data['params'] as $name_param => $data2) {
                $origNameParams = $name_param;
                if ($name_param == 'module')
                    $name_param = 'module_name';
                if ($name_param == 'object_name')
                    $name_param = 'obj_name';
                if ($name_param != 'pword') {
                    $fields[$name_param] = array(
                        'custom'     => 1,
                        'label'      => $data2['label'],
                        'input_name' => $name_param,
                        'input'      => array(
                            'type' => 'text'
                        ),
//                          'value' => $name_param,
                        'display_if' => array(
                            'field_name'  => 'show_' . $name_param,
                            'show_values' => 1
                        )
                    );
                    if ($name_param == 'module_name') {
                        $fields[$name_param]['input']['type'] = 'select';
                        $fields[$name_param]['values'] = $this->list_module();
                        $fields[$name_param]['depends_on'] = 'req';
                    }
                    if ($name_param == 'obj_name') {
                        $fields[$name_param]['input']['type'] = 'select';
                        $fields[$name_param]['values'] = $this->list_object();
                        $fields[$name_param]['depends_on'] = 'req,module_name';
                    }
                    if ($name_param == 'panel_filters') {
                        $fields[$name_param]['input']['type'] = 'object_filters';
                        $fields[$name_param]['input']['obj_module'] = BimpTools::getPostFieldValue('module_name', '', 'aZ09');
                        $fields[$name_param]['input']['obj_name'] = BimpTools::getPostFieldValue('obj_name', '', 'aZ09');
                        $fields[$name_param]['depends_on'] = 'module_name,obj_name';
                    }

                    $fields['show_' . $name_param] = array(
                        'custom'     => 1,
//                            'label'=> 'show_'.$data2['label'],
                        'input_name' => 'show_' . $name_param,
                        'hidden'     => 1,
                        'input'      => array(
                            'type' => 'hidden'
                        ),
                        'value'      => isset(BWSApi::$requests[BimpTools::getPostFieldValue('req', '', 'aZ09')]['params'][$origNameParams]) ? 1 : 0,
                        'depends_on' => 'req'
                    );
                }
            }
        }
        $this->config->addParams('forms/testReq/', array('rows' => $fields), 'overrides', true);
        return $fields;
    }

    public function list_module()
    {
        $reqName = BimpTools::getPostFieldValue('req', '', 'aZ09');
        $rights = $this->getRights();
        $list = array();
        foreach ($rights[$reqName] as $module => $data) {
            $list[$module] = $module;
        }
        return $list;
    }

    public function list_object()
    {
        $reqName = BimpTools::getPostFieldValue('req', '', 'aZ09');
        $module = BimpTools::getPostFieldValue('module_name', '', 'aZ09');
        $rights = $this->getRights();
        $list = array();
        foreach ($rights[$reqName][$module] as $object_name => $data) {
            $list[$object_name] = $object_name;
        }
        return $list;
    }

    public function getParam($params)
    {
        return BimpTools::getPostFieldValue($params);
    }

    public function hidenField()
    {
        return 0;
    }

    public function getRightId($request_name, $module = 'any', $object_name = 'any')
    {
        $rights = $this->getRights();
        if (isset($rights[$request_name][$module][$object_name])) {
            return (int) $rights[$request_name][$module][$object_name];
        }

        return 0;
    }

    public function getIdUserErpUsed(&$origine = '')
    {
        if ((int) $this->getData('id_user')) {
            $origine = 'Utilisateur ERP lié';
            return (int) $this->getData('id_user');
        }

        foreach ($this->getData('profiles') as $id_profile) {
            $profile = BimpCache::getBimpObjectInstance('bimpwebservice', 'BWS_Profile', $id_profile);
            if (BimpObject::objectLoaded($profile)) {
                if ((int) $profile->getData('id_user_def')) {
                    $origine = 'Utilisateur par défaut du profile ' . $profile->getLink();
                    return (int) $profile->getData('id_user_def');
                }
            }
        }

        $origine = 'Utilisateur par défaut du module "bimpwebservice"';
        return (int) BimpCore::getConf('id_default_user', null, 'bimpwebservice');
    }

    // Affichages: 

    public function displayUserErpUsed($display = 'nom_url', $with_origine = true)
    {
        $html = '';
        $origine = '';
        $id_user = $this->getIdUserErpUsed($origine);

        if ($id_user) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
            if (BimpObject::objectLoaded($user)) {
                switch ($display) {
                    case 'nom':
                        $html .= $user->getName();
                        break;

                    case 'nom_url':
                        $html .= $user->getLink();
                        break;

                    case 'card':
                        $card = new BC_Card($user);
                        $html .= $card->renderHtml();
                        break;
                }

                if ($with_origine) {
                    $html .= '<br/><span class="small"><b>Origine:</b>' . $origine . '</span>';
                }
            } else {
                $html .= BimpRender::renderAlerts('L\utilisateur #' . $id_user . ' n\'existe plus');
            }
        } else {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Aucun utilisateur défini</span>';
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderRequestsRightsList()
    {
        $html = '';

        $rights = array();

        foreach ($this->getData('profiles') as $id_profile) {
            $profile = BimpCache::getBimpObjectInstance('bimpwebservice', 'BWS_Profile', $id_profile);

            if (BimpObject::objectLoaded($profile) && (int) $profile->getData('active')) {
                $profile_rights = $profile->getChildrenObjects('rights');
                foreach ($profile_rights as $right) {
                    $request = $right->getData('request_name');
                    $module = $right->getData('obj_module');
                    $object_name = $right->getData('obj_name');
                    $filters = $right->getData('obj_filters');

                    if (!isset($rights[$request])) {
                        $rights[$request] = array(
                            'profiles' => array($profile->id),
                            'modules'  => array()
                        );
                    } elseif (!in_array($profile->id, $rights[$request]['profiles'])) {
                        $rights[$request]['profiles'][] = $profile->id;
                    }

                    if ($module && $module != 'any' && $object_name && $object_name !== 'any') {
                        if (!isset($rights[$request]['modules'][$module])) {
                            $rights[$request]['modules'][$module] = array();
                        }
                        if (!isset($object_name, $rights[$request]['modules'][$module][$object_name])) {
                            $rights[$request]['modules'][$module][$object_name] = array();
                        }
                        if ($filters) {
                            $rights[$request]['modules'][$module][$object_name][$profile->id] = $right->displayData('obj_filters', 'default', false);
                        }
                    }
                }
            }
        }

        $rows = array();

        $headers = array(
            'req'  => 'Requête',
            'prof' => 'Profils',
            'objs' => 'Objets'
        );

        if (empty($rights)) {
            $rows[] = array(
                'full_row_content' => BimpRender::renderAlerts(BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Aucune requête autorisée', 'warning'),
                'row_style'        => 'text-align: center'
            );
        } else {
            foreach ($rights as $request_name => $data) {
                $profiles = '';

                foreach ($data['profiles'] as $id_profile) {
                    $profile = BimpCache::getBimpObjectInstance('bimpwebservice', 'BWS_Profile', $id_profile);
                    if (BimpObject::objectLoaded($profile)) {
                        $profiles .= ($profiles ? '<br/>' : '') . $profile->getLink();
                    }
                }

                $objs = '';

                if (!empty($data['modules'])) {
                    $objs .= '<table class="bimp_list_table">';
                    $objs .= '<tbody>';

                    foreach ($data['modules'] as $module => $objects) {
                        $objs .= '<tr>';
                        $objs .= '<th colspan="2" style="text-align: left">';
                        $objs .= $module;
                        $objs .= '</th>';
                        $objs .= '</tr>';

                        foreach ($objects as $object_name => $filters) {
                            $obj_instance = BimpObject::getInstance($module, $object_name);
                            $objs .= '<tr>';
                            $objs .= '<td>';
                            if ($obj_instance->params['icon']) {
                                $objs .= BimpRender::renderIcon($obj_instance->params['icon'], 'iconLeft');
                            }
                            $objs .= BimpTools::ucfirst($obj_instance->getLabel());
                            $objs .= '</td>';

                            $objs .= '<td>';

                            if (!empty($filters)) {
                                foreach ($filters as $id_profile => $profile_filters) {
                                    $profile = BimpCache::getBimpObjectInstance('bimpwebservice', 'BWS_Profile', $id_profile);
                                    if (BimpObject::objectLoaded($profile)) {
                                        $objs .= $profile->getLink() . ' : <br/>';
                                        $objs .= $profile_filters;
                                    }
                                }
                            }

                            $objs .= '</td>';
                            $objs .= '</tr>';
                        }
                    }

                    $objs .= '</tbody>';
                    $objs .= '</table>';
                }

                $rows[] = array(
                    'req'  => $request_name,
                    'prof' => $profiles,
                    'objs' => $objs
                );
            }
        }

        $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                    'searchable' => true
        ));
        $title = BimpRender::renderIcon('far_check-square', 'iconLeft') . 'Requêtes autorisées';
        return BimpRender::renderPanel($title, $html, '', array(
                    'type' => 'secondary'
        ));
    }

    public function renderUserErpUsedPanel()
    {
        $title = BimpRender::renderIcon('fas_user-check', 'iconLeft') . 'Utilisateur ERP utilisé';
        return BimpRender::renderPanel($title, $this->displayUserErpUsed('card'), '', array(
                    'type' => 'secondary'
        ));
    }

    // Traitements: 

    public function checkPWord($pword)
    {
        return (hash('sha256', $pword) === $this->getData('pword') || $pword === $this->getData('pword'));
    }

    public function checkToken($token, &$error = '')
    {
        if ($this->isLoaded()) {
            $cur_token = $this->getData('token');

            if ($cur_token) {
                $expire = $this->getData('token_expire');

                if ($expire > date('Y-m-d H:i:s')) {
                    if ($token === $cur_token || hash('sha256', $token) === $cur_token) {
                        return true;
                    }
                    $error = 'Token invalide';
                    return false;
                }
                $error = 'Token expiré - Veuillez reconnecter votre compte';
                return false;
            }
            $error = 'Token absent';
        }
        return false;
    }

    public function reinitPassword(&$warnings = array(), &$pword_clear = '')
    {
        $errors = array();
        $pword_clear = BimpTools::randomPassword(12);
        $errors = $this->updateField('pword', hash('sha256', $pword_clear));

        if (!count($errors)) {
            $subject = 'Nouveau mot de passe pour votre accès au Webservice BIMP';
            $msg = 'Bonjour,<br/><br/>Le mot de passe pour votre accès au Webservice BIMP a été réinitialisé.<br/><br/>';
            $msg .= '<b>Nouveau mot de passe : </b>' . $pword_clear;
        }
        $bimpMail = new BimpMail($this, $subject, $this->getData('email'), '', $msg);

        if (BimpCore::isEntity('bimp')) {
            $bimpMail->setFromType('ldlc');
        }

        $bimpMail->send($errors, $warnings);
        return $errors;
    }

    public function generateToken(&$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded($errors)) {
            $profile = $this->getMainProfile();

            $delay_minutes = 0;
            if (BimpObject::objectLoaded($profile)) {
                $delay_minutes = (int) $profile->getData('token_expire_delay');
            }

            if (!$delay_minutes) {
                $delay_minutes = BimpCore::getConf('default_tokens_expire_delay', null, 'bimpwebservice');
            }

            $token = BimpTools::randomPassword(24);
            $this->set('token', hash('sha256', $token));

            $dt = new DateTime();
            $dt->add(new DateInterval('PT' . $delay_minutes . 'M'));
            $expire = $dt->format('Y-m-d H:i:s');
            $this->set('token_expire', $expire);

            $up_errors = $this->update($warnings, true);

            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du token');
            } else {
                return array(
                    'token'  => $token,
                    'expire' => $expire
                );
            }
        }

        return array();
    }

    // Actions: 

    public function actionReinitPWord($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mot de passe réinitialisé avec succès';

        $pw = '';
        $errors = $this->reinitPassword($warnings, $pw);

        if (!count($errors)) {
            $warnings[] = '<b>Nouveau mot de passe:</b> ' . $pw;
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $pw = $this->getData('password');
        if (!$pw) {
            $pw = BimpTools::randomPassword(12);
        }
        $this->set('password', hash('sha256', $pw));

        $key = $this->getData('key');
        if (!$key) {
            $key = BimpTools::randomPassword(18);
        }

        return parent::create($warnings, $force_create);
    }
}

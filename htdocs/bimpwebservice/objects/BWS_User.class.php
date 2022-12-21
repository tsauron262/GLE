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

        return $buttons;
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
        return (hash('sha256', $pword) === $this->getData('pword'));
    }

    public function checkToken($token)
    {
        if ($this->isLoaded()) {
            $cur_token = $this->getData('token');

            if ($cur_token) {
                $expire = $this->getData('token_expire');

                if ($expire > date('Y-m-d H:i:s')) {
                    return (hash('sha256', $token) === $cur_token);
                }
                return true;
            }
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
            $msg = 'Bonjour,<br/><br/>Le mot de passe pour votre accès au Webservice BIPMP a été réinitialisé.<br/><br/>';
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

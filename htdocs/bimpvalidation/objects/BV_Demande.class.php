<?php

require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/classes/BimpValidation.php';

class BV_Demande extends BimpObject
{

    const BV_CANCELED = -2;
    const BV_REFUSED = -1;
    const BV_ATTENTE = 0;
    const BV_ACCEPTED = 1;
    const BV_ACCEPTED_AUTO = 2;

    public static $status_list = array(
        self::BV_CANCELED      => array('label' => 'Abandonnée', 'icon' => 'fas_times-circle', 'classes' => array('danger')),
        self::BV_REFUSED       => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::BV_ATTENTE       => array('label' => 'En attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::BV_ACCEPTED      => array('label' => 'acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        self::BV_ACCEPTED_AUTO => array('label' => 'acceptée (auto)', 'icon' => 'fas_check', 'classes' => array('success')),
    );
    protected $obj_instance = null;

    // Droits users: 

    public function canSetAction($action)
    {
        switch ($action) {
            case 'reopen':
            case 'accept':
            case 'refuse':
                return $this->canProcess();
        }
        return parent::canSetAction($action);
    }

    public function canProcess()
    {
        global $user;

        $id_user_affected = (int) $this->getData('id_user_affected');

        if ($id_user_affected && $user->id == $id_user_affected) {
            return 1;
        }

        $users = $this->getData('validation_users');

        if (!empty($users)) {
            if (in_array($user->id, $users)) {
                return 1;
            }

            if ($id_user_affected && !in_array($id_user_affected, $users)) {
                $users[] = $id_user_affected;
            }

            // Pour chaque user on vérifie que l'utilisateur n'est pas un supérieur (quelque soit le niveau hiérarchique) 
            foreach ($users as $id_user) {
                $cur_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                if ($cur_user->isUserSuperior($user->id, 100)) {
                    return 1;
                }
            }
        }

        return 0;
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'reopen':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if (!(int) $this->getData('status')) {
                    $errors[] = 'Le statut de cette demande de validation ne permet pas cette opération';
                    return 0;
                }

                $obj = $this->getObjInstance();
                if (!BimpObject::objectLoaded($obj)) {
                    $errors[] = 'Objet lié invalide';
                    return 0;
                }

                if ((int) $obj->getData('fk_statut') != 0) {
                    $errors[] = ucfirst($obj->getLabel('this')) . ' n\'est plus en brouillon';
                    return 0;
                }
                return 1;

            case 'accept':
            case 'refuse':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ((int) $this->getData('status') != 0) {
                    $errors[] = 'Le statut de cette demande de validation ne permet pas cette opération';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isAccepted()
    {
        if ((int) $this->getData('status') > 0) {
            return 1;
        }

        return 0;
    }

    public static function objectHasDemandesRefused($object, &$nb_refused)
    {
        if (!BimpObject::objectLoaded($object)) {
            return 0;
        }

        $type_obj = self::getObjectType($object);
        if ($type_obj) {
            $where = 'type_object = \'' . $type_obj . '\' AND id_object = ' . $object->id . ' AND status = ' . self::BV_REFUSED;
            $nb_refused = (int) self::getBdb()->getCount('bv_demande', $where);

            if ($nb_refused > 0) {
                return 1;
            }
        }

        return 0;
    }

    public static function objectHasDemandeAccepted($object, $type_validation)
    {
        if (!BimpObject::objectLoaded($object)) {
            return 0;
        }

        $type_obj = self::getObjectType($object);
        if ($type_obj) {
            $where = 'type_object = \'' . $type_obj . '\' AND id_object = ' . $object->id . ' AND status > 0';
            $where .= ' AND type_validation = \'' . $type_validation . '\'';
            $nb_accepted = (int) self::getBdb()->getCount('bv_demande', $where);

            if ($nb_accepted > 0) {
                return 1;
            }
        }

        return 0;
    }

    // Getters params: 

    public function getClientSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((int) $value) {
            BimpObject::loadClass('bimpvalidation', 'BV_Rule');
            $filtreTab = array();
            foreach (BV_Rule::$objets as $type_obj => $obj_params) {
                if (isset($obj_params['table'])) {
                    $joins[$obj_params['table']] = array(
                        'table' => $obj_params['table'],
                        'on'    => $obj_params['table'] . '.rowid = ' . $main_alias . '.id_obj',
                        'alias' => $obj_params['table']
                    );
                    $filtreTab[] = $main_alias . '.type_object =  \'' . $type_obj . '\' AND ' . $obj_params['table'] . '.fk_soc = ' . $value;
                }
            }

            $filters['custom'] = array('custom' => '((' . implode(') OR (', $filtreTab) . '))');
        }
    }

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('accept') && $this->canSetAction('accept')) {
            $buttons[] = array(
                'label'   => 'Accepter',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('accept', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        if ($this->isActionAllowed('refuse') && $this->canSetAction('refuse')) {
            $buttons[] = array(
                'label'   => 'Refuser',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('refuse', array(), array(
                    'form_name' => 'refuse'
                ))
            );
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => 'Remettre en attente',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        return $buttons;
    }

    // Getters array: 

    public function getTypesObjectsArray()
    {
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');
        return BV_Rule::$objects_list;
    }

    public function getTypesValidationsArray()
    {
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');
        return BV_Rule::$types;
    }

    // Getters données: 

    public function getObjInstance()
    {
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');
        $type = $this->getData('type_object');
        $id_object = (int) $this->getData('id_object');

        foreach (BV_Rule::$objects_list as $obj_type => $obj_def) {
            if ($type == $obj_type) {
                if (!is_null($this->obj_instance)) {
                    if (!is_a($this->obj_instance, $obj_def['object_name']) || $this->obj_instance->id != $id_object) {
                        $this->obj_instance = null;
                    }
                }

                if (is_null($this->obj_instance)) {
                    $this->obj_instance = BimpCache::getBimpObjectInstance($obj_def['module'], $obj_def['object_name'], $id_object);
                }

                return $this->obj_instance;
            }
        }

        return null;
    }

    public function getClient()
    {
        $obj = $this->getObjInstance();

        if (BimpObject::objectLoaded($obj)) {
            if (method_exists($obj, 'getClientFacture')) {
                return $obj->getClientFacture();
            }

            return $obj->getChildObject('client');
        }

        return null;
    }

    public function getObjLabel($with_ref = true)
    {
        $label = '';

        $obj = $this->getObjInstance();
        if (is_a($obj, 'BimpObject') && BimpObject::objectLoaded($obj)) {
            $label = BimpTools::ucfirst($obj->getLabel());

            if ($with_ref) {
                $label .= ' ' . $obj->getRef();
            }
        } else {
            BimpObject::loadClass('bimpvalidation', 'BV_Rule');
            $type_obj = $this->getData('type_object');

            if (isset(BV_Rule::$objects_list[$type_obj])) {
                $label = BV_Rule::$objects_list[$type_obj]['label'];
            } else {
                $label = ucfirst($type_obj);
            }

            if ($with_ref && (int) $this->getData('id_object')) {
                $label .= ' #' . (int) $this->getData('id_object');
            }
        }

        return $label;
    }

    public function getUserNotificationsDemandes($id_user, $id_max, &$errors = array())
    {
        $demandes = array(
            'content'           => array(),
            'nb_user_demandes'  => 0,
            'nb_other_demandes' => 0
        );

        $filters = array(
            'status'  => 0,
            'or_user' => array(
                'or' => array(
                    'id_user_affected' => (int) $id_user,
                    'validation_users' => array(
                        'part_type' => 'middle',
                        'part'      => '[' . $id_user . ']'
                    )
                )
            )
        );

        $secteurs = BimpCache::getSecteursArray();
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');

        foreach (BimpCache::getBimpObjectObjects('bimpvalidation', 'BV_Demande', $filters) as $demande) {
            $obj = $demande->getObjInstance();

            if (BimpObject::objectLoaded($obj)) {
                $obj_params = BimpValidation::getObjectParams($obj);
                $client = $obj->getChildObject('client');
                $user_demande = $demande->getChildObject('user_demande');

                $new_demande = array(
                    'type'         => $demande->displayValidationType(),
                    'obj_link'     => $obj->getLink(),
                    'url'          => $obj->getUrl(),
                    'client'       => (BimpObject::objectLoaded($client) ? $client->getLink() : ''),
                    'secteur'      => lcfirst($secteurs[$obj_params['secteur']]),
                    'val'          => $demande->displayObjVal(),
                    'id'           => $demande->id,
                    'date_create'  => $demande->getData('date_create'),
                    'user_demande' => (BimpObject::objectLoaded($user_demande) ? $user_demande->getName() : ''),
                    'can_process'  => $demande->canProcess(),
                    'can_view'     => $demande->canView()
                );

                if ((int) $demande->getData('id_user_affected') === $id_user) {
                    $new_demande['id_tab'] = 'user_demandes';
                    $demandes['nb_user_demandes']++;
                } else {
                    $new_demande['id_tab'] = 'other_demandes';
                    $demandes['nb_other_demandes']++;
                }

                $demandes['content'][] = $new_demande;
            }
        }

        return $demandes;
    }

    public static function getObjectType($object)
    {
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');
        foreach (BV_Rule::$objects_list as $type => $params) {
            if (is_a($object, $params['object_name'])) {
                return $type;
            }
        }

        return '';
    }

    // Affichages: 

    public function displayObj()
    {
        $obj = $this->getObjInstance();
        if (BimpObject::objectLoaded($obj)) {
            return $obj->getLink();
        }

        return '';
    }

    public function displayObjVal()
    {
        $html = '';
        $obj = $this->getObjInstance();

        if (BimpObject::objectLoaded($obj)) {
            $type = $this->getData('type_validation');
            $errors = array();

            $obj_data = BimpValidation::getObjectData($type, $obj, $errors);

            if (isset($obj_data['val_str'])) {
                BimpObject::loadClass('bimpvalidation', 'BV_Rule');
                if (isset(BV_Rule::$types[$type]['val_label'])) {
                    $html .= '<b>' . BV_Rule::$types[$type]['val_label'] . ' : </b>';
                }

                $html .= $obj_data['val_str'];
            }

            if (isset($obj_data['percent_marge'])) {
                $html .= ($html ? '<br/>' : '') . '<b>Marge : </b>' . BimpTools::displayFloatValue($obj_data['percent_marge'], 4, ',', 1, 1, 0, 1, 1) . ' %';
            }

            if (count($errors)) {
                $html .= BimpRender::renderAlerts($errors);
            }
        }

        return $html;
    }

    public function displayClient()
    {
        $client = $this->getClient();

        if (BimpObject::objectLoaded($client)) {
            return $client->getlink();
        }

        return '';
    }

    public function displayValidationType()
    {
        BimpObject::loadClass('bimpvalidation', 'BV_Rule');
        $type = $this->getData('type_validation');

        if ($type && isset(BV_Rule::$types[$type])) {
            return BV_Rule::$types[$type]['label2'];
        }

        return '';
    }

    // Rendus HTML: 

    public function renderQuickView()
    {
        $html = '';

        $html .= '<div style="margin: 5px; padding: 5px; border: 1px solid #DCDCDC; display: inline-block">';
        $html .= '<b>Validation ' . $this->displayValidationType() . '</b> ' . $this->displayDataDefault('status');

        if ($this->can('view')) {
            $html .= '<span class="btn btn-small btn-default" onclick="' . htmlentities($this->getJsLoadModalView()) . '">';
            $html .= BimpRender::renderIcon('fas_eye');
            $html .= '</span>';
        }

        $html .= '<br/>';
        $html .= $this->displayObjVal();
        if ($this->canProcess()) {
            $buttons = $this->getActionsButtons();

            if (!empty($buttons)) {
                $html .= '<div style="text-align: right; margin-top: 5px">';
                foreach ($buttons as $button) {
                    $html .= '<span class="btn btn-small btn-default" onclick="' . htmlentities($button['onclick']) . '">';
                    $html .= BimpRender::renderIcon($button['icon'], 'iconLeft');
                    $html .= $button['label'];
                    $html .= '</span>';
                }
                $html .= '</div>';
            }
        }
        $html .= '</div><br/>';

        return $html;
    }

    // Traitements:

    public function setAccepted($check_object = true, &$warnings = array(), &$success = '')
    {
        $errors = array();

        global $user;

        $this->set('status', 1);
        $this->set('id_user_validate', $user->id);
        $this->set('date_validate', date('Y-m-d H:i:s'));

        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $this->addObjectLog('Demande acceptée', 'ACCEPTED');

            if ($check_object) {
                $obj = $this->getObjInstance();

                $validation_success = '';
                $validation_errors = BimpValidation::checkObjectValidations($obj, $this->getData('type_object'), $validation_success);

                if (count($validation_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($validation_errors, 'Erreurs lors de la tentative de validation ' . (BimpObject::objectLoaded($obj) ? $obj->getLabel('of_the') : 'de l\'objet lié'));
                } else {
                    $success .= ($success ? '<br/>' : '') . $validation_success;
                }
            }
        }

        return $errors;
    }

    public function autoAccept($reason = '', $check_object = true, &$warnings = array(), &$success = '')
    {
        $errors = array();

        if ((int) $this->getData('status') == 0) {
            $this->set('status', 2);
            $this->set('date_validate', date('Y-m-d H:i:s'));

            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $this->addObjectLog('Demande acceptée automatiquement' . ($reason ? '<br/><b>Motif: </b>' . $reason : ''), 'AUTO_ACCEPTED');

                if ($check_object) {
                    $obj = $this->getObjInstance();

                    $validation_success = '';
                    $validation_errors = BimpValidation::checkObjectValidations($obj, $this->getData('type_object'), $validation_success);

                    if (count($validation_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($validation_errors, 'Erreurs lors de la tentative de validation ' . (BimpObject::objectLoaded($obj) ? $obj->getLabel('of_the') : 'de l\'objet lié'));
                    } else {
                        $success .= ($success ? '<br/>' : '') . $validation_success;
                    }
                }
            }
        } else {
            $errors[] = 'Cette demande de validation n\'est pas en attente d\'acceptation';
        }

        return $errors;
    }

    public function checkAffectedUser($notify_if_change = true, &$infos = '')
    {
        $errors = array();

        $id_cur_affected_user = (int) $this->getData('id_user_affected');
        $users = $this->getData('validation_users');

        if (empty($users)) {
            $errors[] = 'Aucun utilisateur ne peut traiter cette demande de validation';
        } else {
            require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/BV_Lib.php';

            $id_main_user = (int) $users[0];
            $id_new_user_affected = (int) self::getFirstAvailableUser($users, $infos);

            if (!$id_new_user_affected) {
                $errors[] = 'Aucun utilisateur disponible pour effectuer cette validation';
            } elseif ($id_new_user_affected !== $id_cur_affected_user) {
                $this->updateField('id_user_affected', $id_new_user_affected);

                if ($notify_if_change && (!$id_cur_affected_user || $id_new_user_affected != $id_main_user)) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_new_user_affected);

                    if (!BimpObject::objectLoaded($user)) {
                        $errors[] = 'L\'utilisateur #' . $id_new_user_affected . ' n\'existe plus';
                        return $errors;
                    }

                    $type = $this->getData('type_validation');
                    $types = $this->getTypesValidationsArray();
                    $obj = $this->getObjInstance();

                    if (!BimpObject::objectLoaded($obj)) {
                        $errors[] = 'Objet lié invalide';
                    } else {
                        $email = BimpTools::cleanEmailsStr($user->getData('email'));

                        if (!$email) {
                            $errors[] = 'Adresse e-mail absente pour l\'utilisateur ' . $user->getLink();
                        } else {
                            $subject = 'Demande de validation ' . $types[$type]['label2'];
                            $msg = 'Bonjour, <br/><br/>';

                            $msg .= 'La validation ' . $types[$type]['label2'] . ' <a href="' . $obj->getUrl() . '">' . $obj->getLabel('of_the') . ' (PROV' . $obj->id . ')</a>';
                            $msg .= ' est en attente.<br/>';

                            $user_demande = $this->getChildObject('user_demande');
                            if (BimpObject::objectLoaded($user_demande)) {
                                $msg .= '<br/><b>Demandeur : </b>' . $user_demande->getName();
                            }

                            $client = $this->getClient();
                            if (BimpObject::objectLoaded($client)) {
                                $msg .= '<br/><b>Client : </b>' . $client->getLink();

                                if ($this->getData('type_validation') === 'rtp') {
                                    $url = str_replace('client&', 'client&navtab-maintabs=commercial&navtab-commercial_view=client_relances_list_tab&', $client->getUrl());
                                    if ($url) {
                                        $msg .= ' - <a href="' . $url . '">Onglet relances de paiement</a>';
                                    }
                                }
                            }

                            $val = $this->displayObjVal();
                            if ($val) {
                                $msg .= '<br/>' . $val;
                            }

                            if ($infos) {
                                $msg .= '<br/><br/>Cette demande de validation vous a été attribuée pour les motifs suivants : <br/><br/>';
                                $msg .= $infos;
                            }

                            $this->addObjectLog('Demande attribuée à {{Utilisateur:' . $id_new_user_affected . '}}' . ($infos ? '<br/><b>Motif : </b>' . $infos : ''));
                            if (!mailSyn2($subject, $email, '', $msg)) {
                                $errors[] = 'Echec de l\'envoi de l\'e-mail de notification à ' . $user->getName();
                                $this->addObjectLog('Echec de l\'envoi de la notification à ' . $user->getName());
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public static function getFirstAvailableUser($users, &$infos = '', $superiors_depth = 2)
    {
        $bdb = BimpCache::getBdb();
        BimpObject::loadClass('bimpcore', 'Bimp_User');

        for ($n = 0; $n <= $superiors_depth; $n++) {
            foreach ($users as $id_user) {
                if ($n > 0) {
                    // Recherche du supérieur de niveau $n
                    for ($i = 0; $i < $n; $i++) {
                        $id_user = (int) $bdb->getValue('user', 'fk_user', 'rowid = ' . $id_user);

                        if (!$id_user) {
                            break;
                        }
                    }

                    if (!$id_user) {
                        continue;
                    }
                }

                $unavailable_reason = '';
                $errors = array();
                if (Bimp_User::isUserAvailable($id_user, null, $errors, $unavailable_reason)) {
                    return $id_user;
                }

                $bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                $infos .= ($infos ? '<br/>' : '') . $bimp_user->getName() . ' est ' . $unavailable_reason;
            }
        }

        return 0;
    }

    // Actions: 

    public function actionAccept($data, &$success)
    {
        $errors = array();
        $warnings = array();

        $success = 'Demande ' . $this->displayValidationType() . ' acceptée';
        $success_callback = 'bimp_reloadPage();';

        $this->setAccepted(true, $warnings, $success);

        global $user;
        if (!count($errors)) {
            $this->addObjectLog('Demande accepté', 'ACCEPTED');

            $user_demande = $this->getChildObject('user_demande');
            $obj = $this->getObjInstance();

            if (BimpObject::objectLoaded($user_demande) && BimpObject::objectLoaded($obj)) {
                $email = BimpTools::cleanEmailsStr($user_demande->getData('email'));
                if ($email) {
                    global $langs;

                    $validation_type = $this->displayValidationType();
                    $subject = $this->getObjLabel(true) . ' - Validation ' . $validation_type . ' acceptée';

                    $msg = 'Bonjour,<br/><br/>';
                    $msg .= 'La validation ' . $validation_type . ' ' . $obj->getLabel('of_the') . ' ' . $obj->getLink();
                    $msg .= ' a été acceptée par ' . $user->getFullName($langs) . '<br/><br/>';

                    $object = $this->getObjInstance();

                    if (mailSyn2($subject, $email, '', $msg)) {
                        if (is_a($object, 'BimpObject')) {
                            $object->addObjectLog('Notification de validation ' . $validation_type . ' envoyée à "' . $email . '"');
                        }
                    } else {
                        if (is_a($object, 'BimpObject')) {
                            $object->addObjectLog('Echec de l\'envoi de la notification de validation ' . $validation_type . ' à "' . $email . '"');
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionRefuse($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demande ' . $this->displayValidationType() . ' refusée';
        $success_callback = 'bimp_reloadPage();';

        $motif = BimpTools::getArrayValueFromPath($data, 'motif', '');

        global $user;

        $this->set('status', -1);
        $this->set('id_user_validate', $user->id);
        $this->set('date_validate', date('Y-m-d H:i:s'));

        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $this->addObjectLog('Demande  refusée' . ($motif ? '<br/><b>Motif : </b>' . $motif : ''), 'REFUSED');

            $user_demande = $this->getChildObject('user_demande');
            $obj = $this->getObjInstance();

            if (BimpObject::objectLoaded($user_demande) && BimpObject::objectLoaded($obj)) {
                $email = BimpTools::cleanEmailsStr($user_demande->getData('email'));
                if ($email) {
                    global $langs;

                    $subject = $this->getObjLabel(true) . ' - Validation ' . $this->displayValidationType() . ' refusée';
                    $msg = 'Bonjour,<br/><br/>';
                    $msg .= 'La validation ' . $this->displayValidationType() . ' ' . $obj->getLabel('of_the') . ' ' . $obj->getLink();
                    $msg .= ' a été refusée par ' . $user->getFullName($langs) . '<br/><br/>';

                    if ($motif) {
                        $msg .= '<b>Motif : </b><br/>' . $motif;
                    }
                    mailSyn2($subject, $email, '', $msg);
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Demande ' . $this->displayValidationType() . ' remise en attente';
        $success_callback = 'bimp_reloadPage();';

        $this->set('status', 0);
        $this->set('id_user_validate', 0);
        $this->set('date_validate', null);

        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $this->addObjectLog('Demande remise en attente', 'REOPEN');
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides: 

    public function reset()
    {
        parent::reset();
        $this->obj_instance = null;
    }

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            if ((float) $this->getData('val_min') > (float) $this->getData('val_max')) {
                $errors[] = 'La valeur minimale ne peut pas être supérieure à la valeur maximale';
            }
        }

        return $errors;
    }
}

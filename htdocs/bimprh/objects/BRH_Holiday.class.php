<?php

class BRH_Holiday extends BimpObject
{

    const STATUS_BROUILLON = 1;
    const STATUS_ATT_APPROB = 2;
    const STATUS_ATT_APPROB_DRH = 3;
    const STATUS_ANNULEE = 4;
    const STATUS_REFUSEE = 5;
    const STATUS_APPOUVEE = 6;

    public static $status_list = array(
        self::STATUS_BROUILLON      => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        self::STATUS_ATT_APPROB     => array('label' => 'Attente approbation', 'icon' => 'fas_hourglass-start', 'classes' => array('info')),
        self::STATUS_ATT_APPROB_DRH => array('label' => 'Attente approbation (DRH)', 'icon' => 'fas_hourglass-start', 'classes' => array('info')),
        self::STATUS_ANNULEE        => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::STATUS_REFUSEE        => array('label' => 'Refusée', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger')),
        self::STATUS_APPOUVEE       => array('label' => 'Approuvée', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $typesConges = array(
        0 => 'Congés payés',
        1 => 'Absence exceptionnelle',
        2 => 'RTT'
    );
    public static $halfday = array(
        'mat' => 'Matin',
        'apr' => 'Après-midi'
    );

    // Droits User: 

    public function canEditField($field_name)
    {
        global $user;

        switch ($field_name) {
            case 'fk_user':
            case 'fk_group':
            case 'fk_validator':
                if ($this->isUserDRH()) {
                    return 1;
                }
                return 0;
        }

        return parent::canEditField($field_name);
    }

    public function canApprove()
    {
        if ($this->isLoaded()) {
            global $user;
        }
    }

    public function canSetAction($action)
    {
        global $user;

        $status = (int) $this->getData('statut');

        switch ($action) {
            case 'validate':
            case 'reopen':
                $id_user = (int) $this->getData('fk_user');
                if ($id_user && $id_user == (int) $user->id) {
                    return 1;
                }
                return 0;

            case 'approve':
            case 'refuse':
                if ($this->isUserDRH()) {
                    return 1;
                }
                if ((int) $this->getData('fk_validator') == $user->id) {
                    if ($action === 'refuse' && $status !== self::STATUS_ATT_APPROB) {
                        return 0;
                    }
                    return 1;
                }
                return 0;

            case 'approve_drh':
                if ($this->isUserDRH()) {
                    return 1;
                }
                return 0;

            case 'cancel':
                if ($user->id == (int) $this->getData('fk_user')) {
                    return 1;
                }
                return 0;

            case 'cancelApprove':
                if ($this->isUserDRH() || $user->id == (int) $this->getData('fk_validator')) {
                    return 1;
                }
                return 0;
            case 'cancelApproveDRH':
                if ($this->isUserDRH()) {
                    return 1;
                }
                return 0;
            case 'cancelRefuse':
                if ($this->isUserDRH()) {
                    return 1;
                }
                if ((int) $this->getData('fk_user_refuse') === (int) $this->getData('fk_validator') &&
                        $user->id == (int) $this->getData('fk_validator')) {
                    return 1;
                }
                return 0;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens

    public function isUserDRH()
    {
        global $user;

        // Todo ajouter droits user. 
//        if ($user->admin) {
        return 1;
//        }

        return 0;
    }

    public function isUserNotDRH()
    {
        return $this->isUserDRH() ? 0 : 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array())) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        $status = (int) $this->getData('statut');
        $now = date('Y-m-d');

        switch ($action) {
            case 'validate':
                if (!in_array($status, array(self::STATUS_BROUILLON))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                return 1;

            case 'approve':
                if (!in_array($status, array(self::STATUS_ATT_APPROB))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                return 1;

            case 'approve_drh':
                if (!in_array($status, array(self::STATUS_ATT_APPROB, self::STATUS_ATT_APPROB_DRH))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                return 1;

            case 'refuse':
                if (!in_array($status, array(self::STATUS_ATT_APPROB, self::STATUS_ATT_APPROB_DRH))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                return 1;

            case 'cancel':
                if (!in_array($status, array(self::STATUS_BROUILLON, self::STATUS_ATT_APPROB, self::STATUS_ATT_APPROB_DRH, self::STATUS_APPOUVEE))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                if ($now >= $this->getData('date_debut')) {
                    $errors[] = 'Cette demande de congés ne peut plus être annulée à ce jour';
                    return 0;
                }
                return 1;

            case 'cancelApprove':
                if (!in_array($status, array(self::STATUS_ATT_APPROB_DRH))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                return 1;

            case 'cancelApproveDRH':
                if (!in_array($status, array(self::STATUS_APPOUVEE))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                return 1;

            case 'cancelRefuse':
                if (!in_array($status, array(self::STATUS_REFUSEE))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                return 1;

            case 'reopen':
                if (!in_array($status, array(self::STATUS_ANNULEE))) {
                    $errors[] = 'Le statut actuel de la demande de congés ne permet pas cette opération';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons[] = array(
                'label'   => 'Annuler cette demande',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'annulation de cette demande de congés'
                ))
            );
        }

        if ($this->isActionAllowed('validate') && $this->canSetAction('validate')) {
            $buttons[] = array(
                'label'   => 'Valider cette demande',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('validate', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la validation de cette demande de congés'
                ))
            );
        }

        if ($this->isActionAllowed('approve_drh') && $this->canSetAction('approve_drh')) {
            $buttons[] = array(
                'label'   => 'Approuver',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('approve_drh', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'approbation de cette demande de congés'
                ))
            );
        } elseif ($this->isActionAllowed('approve') && $this->canSetAction('approve')) {
            $buttons[] = array(
                'label'   => 'Approuver',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('approve', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'approbation de cette demande de congés'
                ))
            );
        }

        if ($this->isActionAllowed('refuse') && $this->canSetAction('refuse')) {
            $buttons[] = array(
                'label'   => 'Refuser',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('refuse', array(), array(
                    'form_name' => 'refuse'
                ))
            );
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => 'Réouvrir',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la réouverture de cette demande de congés'
                ))
            );
        }

        if ($this->isActionAllowed('cancelApprove') && $this->canSetAction('cancelApprove')) {
            $buttons[] = array(
                'label'   => 'Annuler l\'approbation',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('cancelApprove', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'annulation de l\\\'approbation de cette demande de congés'
                ))
            );
        }

        if ($this->isActionAllowed('cancelApproveDRH') && $this->canSetAction('cancelApproveDRH')) {
            $buttons[] = array(
                'label'   => 'Annuler l\'approbation',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('cancelApproveDRH', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'annulation de l\\\'approbation de cette demande de congés'
                ))
            );
        }

        if ($this->isActionAllowed('cancelRefuse') && $this->canSetAction('cancelRefuse')) {
            $buttons[] = array(
                'label'   => 'Annuler le refus',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('cancelRefuse', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'annulation du refus de cette demande de congés'
                ))
            );
        }

        return $buttons;
    }

    // Getters données: 

    public function getDateHalfdayValue($date_type)
    {
        // 0:Full days, 2:Start afternoon end morning, -1:Start afternoon end afternoon, 1:Start morning end morning
        $hd = (int) $this->getData('halfday');
        $value = '';

        switch ($date_type) {
            case 'from':
                $value = 'mat';
                switch ($hd) {
                    case 0:
                    case 1:
                        $value = 'mat';
                        break;
                    case -1:
                    case 2:
                        $value = 'apr';
                        break;
                }
                break;

            case 'to':
                switch ($hd) {
                    case 0:
                    case -1:
                        $value = 'apr';
                        break;

                    case 1:
                    case 2:
                        $value = 'mat';
                        break;
                }
                break;
        }

        return $value;
    }

    public function getIdValidator()
    {
        $id_user = (int) $this->getData('fk_user');

        if ($id_user) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);

            if (BimpObject::objectLoaded($user)) {
                return (int) $user->getData('fk_user');
            }
        }

        return 0;
    }

    // Affichages: 

    public function displayDateFrom()
    {
        $date = $this->displayData('date_debut');
        $hd = $this->getDateHalfdayValue('from');

        if ($hd) {
            $date .= ' ' . self::$halfday[$hd];
        }

        return $date;
    }

    public function displayDateTo()
    {
        $date = $this->displayData('date_fin');
        $hd = $this->getDateHalfdayValue('to');

        if ($hd) {
            $date .= ' ' . self::$halfday[$hd];
        }

        return $date;
    }

    // Rendus HTML: 

    public function renderDateCongesInput($date_type)
    {
        $html = '';

        $field_name = 'date_';

        switch ($date_type) {
            case 'from':
                $field_name .= 'debut';
                break;

            case 'to':
                $field_name .= 'fin';
                break;
        }

        $html .= BimpInput::renderInput('date', $field_name, $this->getData($field_name));

        $hd_value = $this->getDateHalfdayValue($date_type);

        $html .= BimpInput::renderInput('select', $field_name . '_halfday', $hd_value, array(
                    'options' => self::$halfday
        ));

        return $html;
    }

    // Traitements: 

    public function sendEmail($email_type)
    {
        $errors = array();

        if ($this->isLoaded()) {
            
        }

        return $errors;
    }

    // Actions: 

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Annulation effectuée avec succès';

        global $user;
        $this->set('statut', self::STATUS_ANNULEE);
        $this->set('fk_user_cancel', $user->id);
        $this->set('date_cancel', date('Y-m-d H:i:s'));

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Validation effectuée avec succès';

        $this->set('statut', self::STATUS_ATT_APPROB);
        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionApprove($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Approbation effectuée avec succès';

        global $user;

        $this->set('statut', self::STATUS_ATT_APPROB_DRH);
        $this->set('fk_user_valid', (int) $user->id);
        $this->set('date_valid', date('Y-m-d H:i:s'));

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionApprove_drh($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Approbation du DRH effectuée avec succès';

        global $user;

        $this->set('statut', self::STATUS_APPOUVEE);
        $this->set('fk_user_drh_valid', (int) $user->id);
        $this->set('date_drh_valid', date('Y-m-d H:i:s'));

        $this->set('fk_user_cancel', 0);
        $this->set('fk_user_refuse', 0);
        $this->set('date_cancel', null);
        $this->set('date_refuse', null);

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRefuse($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Refus de la demande de congés enregistré avec succès';

        global $user;

        $this->set('statut', self::STATUS_REFUSEE);
        $this->set('fk_user_refuse', (int) $user->id);
        $this->set('date_refuse', date('Y-m-d H:i:s'));
        $this->set('fk_user_valid', 0);
        $this->set('date_valid', null);
        $this->set('fk_user_drh_valid', 0);
        $this->set('date_drh_valid', null);

        if (isset($data['detail_refuse'])) {
            $this->set('detail_refuse', $data['detail_refuse']);
        }

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture de la demande de congés enregistré avec succès';

        $this->set('fk_user_cancel', 0);
        $this->set('date_cancel', null);

        if ((int) $this->getData('fk_user_drh_valid')) {
            $this->set('statut', self::STATUS_APPOUVEE);
        } elseif ((int) $this->getData('fk_user_valid')) {
            $this->set('statut', self::STATUS_ATT_APPROB_DRH);
        } elseif ((int) $this->getData('fk_user_refuse')) {
            $this->set('statut', self::STATUS_REFUSEE);
        } else {
            $this->set('statut', self::STATUS_BROUILLON);
        }

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelApprove($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Annulation de l\'approbation effectuée avec succès';

        $this->set('statut', self::STATUS_ATT_APPROB);
        $this->set('fk_user_valid', 0);
        $this->set('date_valid', null);
        $this->set('fk_user_drh_valid', 0);
        $this->set('date_drh_valid', null);

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelApproveDRH($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Approbation du DRH effectuée avec succès';

        $this->set('statut', self::STATUS_ATT_APPROB_DRH);
        $this->set('fk_user_drh_valid', 0);
        $this->set('date_drh_valid', null);

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelRefuse($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Refus de la demande de congés enregistré avec succès';

        if ((int) $this->getData('fk_user_valid')) {
            $this->set('statut', self::STATUS_ATT_APPROB_DRH);
        } else {
            $this->set('statut', self::STATUS_ATT_APPROB);
        }

        $this->set('fk_user_refuse', 0);
        $this->set('date_refuse', null);
        $this->set('detail_refuse', '');

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validatePost()
    {
        $errors = array();

        $hd_from = BimpTools::getPostFieldValue('date_debut_halfday', '');
        $hd_to = BimpTools::getPostFieldValue('date_fin_halfday', '');

        if ($hd_from && $hd_to) {
            $hd = 0;
            if ($hd_from === 'mat') {
                if ($hd_to === 'apr') {
                    $hd = 0;
                } else {
                    $hd = 1;
                }
            } else {
                if ($hd_to === 'apr') {
                    $hd = -1;
                } else {
                    $hd = 2;
                }
            }
            $this->set('halfday', $hd);
        }

        if (!count($errors)) {
            $errors = parent::validatePost();
        }

        return $errors;
    }

    public function validate()
    {
        $errors = array();

        if (!$this->isLoaded()) {
            if (!(int) BimpTools::getPostFieldValue('is_collectif', 0)) {
                if (!(int) $this->getData('fk_user')) {
                    $errors[] = 'Veullez sélectionner un utilisateur';
                }

                $this->set('fk_group', 0);
            } else {
                if (!(int) $this->getData('fk_group')) {
                    $errors[] = 'Veullez sélectionner un utilisateur';
                }

                $this->set('fk_user', 0);
            }

            $this->set('date_create', date('Y-m-d H:i:s'));

            if ($this->isUserDRH()) {
                global $user;
                $this->set('statut', self::STATUS_APPOUVEE);
                $this->set('fk_validator', 0);
                $this->set('fk_user_drh_valid', $user->id);
                $this->set('date_drh_valid', date('Y-m-d H:i:s'));
            } else {
                $this->set('statut', self::STATUS_BROUILLON);
            }
        }

        $date_fom = $this->getData('date_debut');
        $date_to = $this->getData('date_fin');

        if ($date_to < $date_fom) {
            $errors[] = 'La date de fin ne peut être inférieure à la date de début';
        } elseif ($date_fom == $date_to) {
            $dh_from = $this->getDateHalfdayValue('from');
            $dh_to = $this->getDateHalfdayValue('to');

            if ($dh_from == 'apr' && $dh_to == 'mat') {
                $errors[] = 'La date de fin ne peut être inférieure à la date de début';
            }
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ((int) $this->getData('statut') === self::STATUS_ATT_APPROB_DRH) {
                $email_errors = $this->sendEmail();

                if (count($email_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($email_errors, 'Erreurs lors de l\'envoi du mail de notification');
                }
            }
        }
    }
}

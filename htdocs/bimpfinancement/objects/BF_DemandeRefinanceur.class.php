<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/BF_Lib.php';

class BF_DemandeRefinanceur extends BimpObject
{

    const STATUS_TODO = 0;
    const STATUS_ATTENTE = 1;
    const STATUS_ACCEPTEE = 10;
    const STATUS_ACCEPTEE_CONDS = 11;
    const STATUS_SELECTIONNEE = 12;
    const STATUS_REFUSEE = 20;
    const STATUS_ANNULEE = 21;

    public static $status_list = array(
        self::STATUS_TODO           => array('label' => 'Demande à effectuer', 'icon' => 'fas_exclamation-circle', 'classes' => array('important')),
        self::STATUS_ATTENTE        => array('label' => 'Réponse en attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::STATUS_ACCEPTEE       => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        self::STATUS_ACCEPTEE_CONDS => array('label' => 'Acceptée sous conditions', 'icon' => 'fas_check-square', 'classes' => array('info')),
        self::STATUS_SELECTIONNEE   => array('label' => 'Acceptée / sélectionnée', 'icon' => 'fas_check-double', 'classes' => array('success')),
        self::STATUS_REFUSEE        => array('label' => 'Refusée', 'icon' => 'fas_exclamation-triangle', 'classes' => array('danger')),
        self::STATUS_ANNULEE        => array('label' => 'Annulée', 'icon' => 'fas_times-circle', 'classes' => array('danger'))
    );
    public static $payments = array(
        0 => '-',
        1 => 'Prélévement auto',
        2 => 'Virement',
        3 => 'Mandat administratif'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1  => 'mensuel',
        3  => 'trimestriel',
        6  => 'semestriel',
        12 => 'annuel'
    );
    public static $period_label = array(
        1  => 'mois',
        3  => 'trimestre',
        6  => 'semestre',
        12 => 'an'
    );
    public static $period_label_plur = array(
        1  => 'mois',
        3  => 'trimestres',
        6  => 'semestres',
        12 => 'ans'
    );

    // getters booléens: 

    public function isActionAllowed($action, &$errors = [])
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        $status = (int) $this->getData('status');

        switch ($action) {
            case 'processDemande':
                if ($status !== self::STATUS_TODO) {
                    $errors[] = 'La demande a déjà été effectuée';
                    return 0;
                }
                return 1;

            case 'demandeDone':
                if ($status !== self::STATUS_TODO) {
                    $errors[] = 'La demande est déjà marquée comme effectuée';
                    return 0;
                }
                return 1;

            case 'cancel':
                if ($status >= 10) {
                    $errors[] = 'Cette demande ne peux plus être annulée';
                    return 0;
                }
                return 1;

            case 'accepted':
            case 'refused':
                if ($status !== self::STATUS_ATTENTE) {
                    $errors[] = 'Cette demande n\'est pas en attente d\'acceptation';
                    return 0;
                }
                return 1;

            case 'selected':
                if ($status < 10 || $status >= 20) {
                    $errors[] = 'Cette demande n\'est pas sélectionnable';
                    return 0;
                }

                $demande = $this->getParentInstance();
                if (!BimpObject::objectLoaded($demande)) {
                    $errors[] = 'Demande de financement liée absente';
                    return 0;
                }

                if ((int) $demande->getData('status') >= 10) {
                    $errors[] = 'Le statut de la demande de financement ne permet pas cette opération';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isNewStatusAllowed($new_status, &$errors = [])
    {
        $cur_status = (int) $this->getData('status');

        switch ($new_status) {
            case self::STATUS_TODO:
                if ($cur_status === self::STATUS_ATTENTE) {
                    return 1;
                }
                return 0;

            case self::STATUS_ATTENTE:
                if ($cur_status >= 10) {
                    return 1;
                }
                return 0;
        }
        return parent::isNewStatusAllowed($new_status, $errors);
    }

    public function isCreatable($force_create = false, &$errors = array())
    {
        $demande = $this->getParentInstance();

        if (BimpObject::objectLoaded($demande)) {
            if (!(int) $demande->getData('accepted')) {
                return 1;
            }
        }

        return 0;
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return $this->isCreatable($force_edit, $errors);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return $this->isCreatable($force_delete, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (!$this->isLoaded()) {
            return 1;
        }

        if ($force_edit) {
            return 1;
        }

        $status = (int) $this->getData('status');
        switch ($field) {
            case 'id_demande':
            case 'id_refinanceur':
                return 0;

            case 'rate':
            case 'coef':
            case 'qty':
            case 'amount_ht':
            case 'payment':
            case 'periodicity':
                if ($status >= 10) {
                    return 0;
                }
                return 1;
        }
        return parent::isFieldEditable($field, $force_edit);
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('processDemande') && $this->canSetAction('processDemande')) {
            $refinanceur = $this->getChildObject('refinanceur');

            if (BimpObject::objectLoaded($refinanceur)) {
                $url = $refinanceur->getData('url_demande');

                if ($url) {
                    $soc = $refinanceur->getChildObject('societe');
                    $title = 'Demande refinanceur ' . $soc->getName();

                    $buttons[] = array(
                        'label'   => 'Effectuer la demande',
                        'icon'    => 'fas_cogs',
                        'onclick' => htmlentities('window.open(\'' . $url . '\', \'' . $title . '\', "menubar=no, status=no, width=1200, height=800")')
//                        'onclick' => 'window.open(\'' . $url . '\');'
                    );
                }
            }
        }

        if ($this->isActionAllowed('demandeDone') && $this->canSetAction('demandeDone')) {
            $buttons[] = array(
                'label'   => 'Demande éffectuée',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsLoadModalForm('demande_done', 'Demande effectuée')
            );
        }

        if ($this->isActionAllowed('accepted') && $this->canSetAction('accepted')) {
            $buttons[] = array(
                'label'   => 'Demande acceptée',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsLoadModalForm('accepted', 'Demande acceptée par le refinanceur')
            );
        }

        if ($this->isActionAllowed('refused') && $this->canSetAction('refused')) {
            $buttons[] = array(
                'label'   => 'Demande refusée',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('refused', array(), array(
                    'form_name' => 'refused'
                ))
            );
        }

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons[] = array(
                'label'   => 'Abandonner cette demande',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                    'form_name' => 'cancel'
                ))
            );
        }

        if ($this->isActionAllowed('selected') && $this->canSetAction('selected')) {
            $buttons[] = array(
                'label'   => 'Sélectionner (validation définitive)',
                'icon'    => 'fas_check-double',
                'onclick' => $this->getJsActionOnclick('selected', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        // Remises au statut précédent: 
        foreach (array(self::STATUS_TODO, self::STATUS_ATTENTE) as $reset_status) {
            if ($this->isNewStatusAllowed($reset_status) && $this->canSetStatus($reset_status)) {
                $buttons[] = array(
                    'label'   => 'Remettre au statut "' . self::$status_list[$reset_status]['label'] . '"',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsNewStatusOnclick($reset_status, array(), array(
                        'confirm_msg' => htmlentities('Veuillez confirmer la remise au statut "' . self::$status_list[$reset_status]['label'] . '"')
                    ))
                );
            }
        }

        return $buttons;
    }

    // Getters array: 

    public static function getRefinanceursArray($include_empty = true)
    {
        $cache_key = 'bf_refinanceurs_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $instance = BimpObject::getInstance('bimpfinancement', 'BF_Refinanceur');

            foreach ($instance->getList(array(), null, null, 'id', 'asc', 'array', array('id', 'id_societe')) as $item) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $item['id_societe']);
                if ($soc->isLoaded()) {
                    self::$cache[$cache_key][(int) $item['id']] = $soc->getName();
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Getters données:

    public function getNbMois()
    {
        return $this->getData("quantity") * $this->getData("periodicity");
    }

    public function getTotalDemande()
    {
        $demande = $this->getParentInstance();

        if (BimpObject::objectLoaded($demande)) {
            return $demande->getTotalDemande();
        }

        return 0;
    }
    
    public function getTotalRemboursement()
    {
        return $this->getData("quantity") * $this->getData("amount_ht");
    }

    // Affichage: 

    public function displayRefinanceur($display_name = 'nom_url')
    {
        if ($this->isLoaded()) {
            $refinanceur = BimpCache::getBimpObjectInstance($this->module, 'BF_Refinanceur', (int) $this->getData('id_refinanceur'));

            if (!$refinanceur->isLoaded()) {
                return $this->renderChildUnfoundMsg('id_refinanceur', $refinanceur);
            } else {
                $soc = $refinanceur->getChildObject('societe');
                if (BimpObject::objectLoaded($soc)) {
                    if ($display_name === 'nom_url') {
                        return $soc->getLink();
                    }
                    if ($display_name === 'card') {
                        $card = new BC_Card($soc);
                        return $card->renderHtml();
                    }
                }

                return $refinanceur->getName();
            }
        }

        return '';
    }

    // Actions:

    public function actionRefused($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Refus enregistré avec succès';

        $errors = $this->updateField('status', self::STATUS_REFUSEE); // Pour contourner le log automatique

        if (!count($errors)) {
            $reasons = BimpTools::getArrayValueFromPath($data, 'reasons', '');
            if ($reasons) {
                $comment = $this->getData('comment');
                $comment .= ($comment ? '<br/><br/>' : '') . '<b>Raisons du refus par le refinanceur : </b>' . $reasons;
                $this->updateField('comment', $comment);
            }

            $this->addObjectLog('Demande refusée par le refinanceur.' . ($reasons ? '<br/><br/><b>Raisons : </b>' . $reasons : ''));

            $demande = $this->getParentInstance();
            if (BimpObject::objectLoaded($demande)) {
                $demande->checkStatus();
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Abandon enregistré avec succès';

        $errors = $this->updateField('status', self::STATUS_ANNULEE); // Pour contourner le log automatique

        if (!count($errors)) {
            $reasons = BimpTools::getArrayValueFromPath($data, 'reasons', '');
            if ($reasons) {
                $comment = $this->getData('comment');
                $comment .= ($comment ? '<br/><br/>' : '') . '<b>Raisons de l\'abandon : </b>' . $reasons;
                $this->updateField('comment', $comment);
            }

            $this->addObjectLog('Demande abandonnée.' . ($reasons ? '<br/><br/><b>Raisons : </b>' . $reasons : ''));
            $demande = $this->getParentInstance();
            if (BimpObject::objectLoaded($demande)) {
                $demande->checkStatus();
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSelected($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Validation définitive effectuée avec succès';

        $demande = $this->getParentInstance();
        if (BimpObject::objectLoaded($demande)) {
            $errors = $this->setNewStatus(self::STATUS_SELECTIONNEE);
        } else {
            $errors[] = 'Demande de financement liée absente';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function reset()
    {
        $this->calcValues = null;
        parent::reset();
    }

    public function validate()
    {
        $errors = array();
        $calc_mode = BimpTools::getPostFieldValue('calc_mode', '');

        if (!$calc_mode) {
            if ((float) $this->getData('rate')) {
                $calc_mode = 'rate';
            } elseif ((float) $this->getData('coef')) {
                $calc_mode = 'coef';
            }
        }

        switch ($calc_mode) {
            case 'rate':
                $this->set('coef', 0);
                break;

            case 'coef':
                $this->set('rate', 0);
                break;
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }

    public function create(&$warnings = [], $force_create = false)
    {
        $date_demande = '';
        if ((int) BimpTools::getPostFieldValue('demande_done', 0)) {
            $date_demande = BimpTools::getPostFieldValue('date_demande', '');
            $this->set('status', self::STATUS_ATTENTE);
        } else {
            $this->set('status', self::STATUS_TODO);
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ($date_demande) {
                $this->addObjectLog('Demande effectuée le ' . date('d / m / H à H:i', strtotime($date_demande)), 'DEMANDE_DONE');
            }
        }

        return $errors;
    }

    public function update(&$warnings = [], $force_update = false)
    {
        $date_demande = '';
        $conds = '';
        $new_status = null;

        if ((int) $this->getData('status') === self::STATUS_TODO && (int) BimpTools::getPostFieldValue('demande_done', 0)) {
            $date_demande = BimpTools::getPostFieldValue('date_demande', '');
            $this->set('status', self::STATUS_ATTENTE);
        }
        if ((int) $this->getData('status') === self::STATUS_ATTENTE && (int) BimpTools::getPostFieldValue('demande_accepted', 0)) {
            if ((int) BimpTools::getPostFieldValue('under_conditions', 0)) {
                $new_status = self::STATUS_ACCEPTEE_CONDS;

                $conds = BimpTools::getPostFieldValue('conditions', '');
                if ($conds) {
                    $comment = $this->getData('comment');
                    $comment .= ($comment ? '<br/><br/>' : '') . '<b>Conditions d\'acceptation : </b><br/>' . $conds;
                    $this->set('comment', $comment);
                }
            } else {
                $new_status = self::STATUS_ACCEPTEE;
            }
        }

        if (!is_null($new_status)) {
            $this->set('status', $new_status);
        }

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($date_demande) {
                $this->addObjectLog('Demande effectuée le ' . date('d / m / H à H:i', strtotime($date_demande)));
            }

            if (!is_null($new_status)) {
                switch ($new_status) {
                    case self::STATUS_ACCEPTEE:
                        $this->addObjectLog('Demande acceptée');
                        break;
                    case self::STATUS_ACCEPTEE_CONDS:
                        $this->addObjectLog('Demande acceptée sous conditions.' . ($conds ? ' <br/><b>Conditions : </b>' . $conds : ''));
                        break;
                }
            }
        }

        return $errors;
    }
}

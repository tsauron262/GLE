<?php

class BCT_ContratLine extends BimpObject
{

    const TYPE_TEXT = 1;
    const TYPE_ABO = 2;

    public static $types = array(
        self::TYPE_ABO  => array('label' => 'Abonnement', 'icon' => 'fas_calendar-alt'),
        self::TYPE_TEXT => array('label' => 'Texte', 'icon' => 'fas_align-left')
    );

    const STATUS_PROPAL_REFUSED = -3;
    const STATUS_ATT_PROPAL = -2;
    const STATUS_NONE = -1;
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 4;
    const STATUS_CLOSED = 5;

    public static $status_list = array(
        self::STATUS_PROPAL_REFUSED => array('label' => 'Devis refusé', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger')),
        self::STATUS_ATT_PROPAL     => array('label' => 'Attente acceptation devis', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::STATUS_NONE           => array('label' => 'Non Applicable'),
        self::STATUS_INACTIVE       => array('label' => 'Inactif', 'icon' => 'fas_times', 'classes' => array('warning')),
        self::STATUS_ACTIVE         => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success')),
        self::STATUS_CLOSED         => array('label' => 'Fermé', 'icon' => 'fas_times-circle', 'classes' => array('danger')),
    );
    public static $periodicities = array(
        0  => 'Aucune',
        1  => 'Mensuelle',
        2  => 'Bimensuelle',
        3  => 'Trimestrielle',
        4  => 'Quadrimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle',
        24 => 'Biannuelle',
        36 => 'Triannuelle'
    );
    public static $periodicities_masc = array(
        1  => 'Mensuel',
        2  => 'Bimensuel',
        3  => 'Trimestriel',
        4  => 'Quadrimestriel',
        6  => 'Semestriel',
        12 => 'Annuel',
        24 => 'Biannuel',
        36 => 'Triannuel'
    );
    public static $dol_fields = array('fk_contrat', 'fk_product', 'label', 'description', 'commentaire', 'statut', 'qty', 'price_ht', 'subprice', 'tva_tx', 'remise_percent', 'remise', 'fk_product_fournisseur_price', 'buy_price_ht', 'total_ht', 'total_tva', 'total_ttc', 'date_commande', 'date_ouverture_prevue', 'date_fin_validite', 'date_cloture', 'fk_user_author', 'fk_user_ouverture', 'fk_user_cloture');
    protected $data_at_date = null;
    public $process_bundle_lines = true;

    // Droits User:

    public function canSetAction($action)
    {
        global $user;
        switch ($action) {
            case 'facturationAvance':
                if (!empty($user->rights->bimpcontrat->facturation_avance)) {
                    return 1;
                }

            case 'activate':
                return (int) ($user->admin || !empty($user->rights->bimpcontract->to_validate));

            case 'periodicFacProcess':
                return $user->admin || $user->rights->facture->creer;

            case 'periodicAchatProcess':
                return (int) $user->rights->fournisseur->commande->creer;

            case 'renouv':
                return 1;

            case 'deactivate':
                return ($user->admin || in_array($user->login, array('a.remeur', 'p.bachorz')) ? 1 : 0);

            case 'addUnits':
                return 1;

            case 'checkDateNextFac':
                return BimpCore::isUserDev();

            case 'MoveToOtherContrat':
                return 1;
        }
        return parent::canSetAction($action);
    }

    public function canEditField($field_name)
    {
        global $user;
        if (in_array($field_name, array('statut'))) {
            if (!$user->admin) {
                return 0;
            }
        }

        return 1;
    }

    // Getters booléens:

    public function isValide(&$errors = array())
    {
        return 1;
    }

    public function isCreatable($force_create = false, &$errors = [])
    {
        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if (!$force_delete) {
            if ($this->isSubline()) {
                return 0;
            }

            global $user;

//            if (!$user->admin && $this->getData('line_origin_type') == 'propal_line' && (int) $this->getData('id_line_origin') > 0) {
//                return 0;
//            }
        }

        $status = (int) $this->getData('statut');
        if (!$force_delete && $status === self::STATUS_ATT_PROPAL) {
            $errors[] = 'En attente d\'acceptation du devis';
            return 0;
        }

        if ($status <= 0) {
            return 1;
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (!$force_edit && in_array((int) $this->getData('statut'), array(self::STATUS_ATT_PROPAL, self::STATUS_PROPAL_REFUSED))) {
            return 0;
        }

        if ($this->isLoaded() && in_array($field, array('line_type'))) {
            return 0;
        }

        $status = (int) $this->getData('statut');

        if (!$force_edit && $status > 0 && in_array($field, array('fk_product', 'qty', 'price_ht', 'subprice', 'tva_tx', 'remise_percent', 'fac_periodicity', 'duration', 'variable_pu_ht', 'variable_qty', 'date_ouverture_prevue', 'date_fac_start', 'date_achat_start'))) {
            return 0;
        }

        if ((int) $this->getData('id_parent_line') && in_array($field, array('fac_periodicity', 'duration', 'fac_term', 'nb_renouv', 'date_ouverture_prevue', 'date_fac_start', 'date_achat_start', 'variable_qty', 'variable_pu_ht'))) {
            return 0;
        }

        if ($this->isLoaded() && (int) $this->getData('id_linked_line') && in_array($field, array('fac_periodicity', 'achat_periodicity', 'duration', 'fac_term', 'nb_renouv', 'variable_qty', 'variable_pu_ht'))) {
            return 0;
        }


        if (in_array($field, array('achat_periodicity', 'variable_qty', 'variable_pu_ht'))) {
            if ((int) $this->getData('fk_product')) {
                $product = $this->getChildObject('product');
                if (BimpObject::objectLoaded($product) && $product->isBundle()) {
                    return 0;
                }
            }
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isLinkedToOtherRef()
    {
        if ((int) $this->getData('id_linked_line')) {
            $linked_line = $this->getChildObject('linked_line');

            if (BimpObject::objectLoaded($linked_line)) {
                if ((int) $linked_line->getData('fk_product') !== (int) $this->getData('fk_product')) {
                    return 1;
                }
            }
        }

        return 0;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        $contrat = null;

        if (in_array($action, array('deactivate', 'facRegul', 'setResiliateDate')) && !$this->isLoaded($errors)) {
            return 0;
        }

        if ((int) $this->getData('id_parent_line') && in_array($action, array('activate', 'deactivate', 'renouv', 'setResiliateDate'))) {
            $errors[] = 'Action non possible pour les sous-lignes. Veuillez effectuer cette action sur la ligne parente';
            return 0;
        }

        if (in_array($action, array('activate'))) {
            if (!$this->isLoaded()) {
                return 1; // pour les bulk actions
            }

            $contrat = $this->getParentInstance();
            if (!BimpObject::objectLoaded($contrat)) {
                $errors[] = 'Contrat absent';
                return 0;
            }
        }

        $status = (int) $this->getData('statut');

        switch ($action) {
            case 'activate':
                if (!in_array($this->getData('line_type'), array(self::TYPE_ABO))) {
                    $errors[] = 'Une ligne de type "' . self::$types[(int) $this->getData('line_type')]['label'] . '" ne peux pas être activée';
                    return 0;
                }

                if ($status > 0) {
                    $errors[] = 'Cette ligne de contrat a déjà été activée';
                    return 0;
                }

                if ($status == self::STATUS_ATT_PROPAL) {
                    $errors[] = 'Attente acceptation du devis';
                    return 0;
                }

                if ($status == self::STATUS_PROPAL_REFUSED) {
                    $errors[] = 'Devis refusé - Réviser le devis ou supprimer la ligne de contrat';
                    return 0;
                }

                if ((int) $contrat->getData('statut') <= 0) {
                    $errors[] = 'Le contrat n\'est pas validé';
                    return 0;
                }

                if (!$this->isValide($errors)) {
                    return 0;
                }
                return 1;

            case 'deactivate':
                if ($status !== self::STATUS_ACTIVE) {
                    $errors[] = 'Cette ligne de contrat est déjà désactivée';
                    return 0;
                }

                if ((int) $this->getData('id_parent_line')) {
                    $errors[] = 'Sous-ligne: veuillez désactiver la ligne parente';
                    return 0;
                }

                if ((int) $this->db->getCount('contratdet', 'id_linked_line = ' . $this->id . ' AND statut = ' . self::STATUS_ACTIVE, 'rowid') > 0) {
                    $errors[] = 'Il existe des lignes liées actives';
                    return 0;
                }
                return 1;

            case 'renouv':
                if ($this->isLoaded()) {
                    if ($status <= 0) {
                        $errors[] = 'Le statut actuel de cette ligne de contrat ne permet pas son renouvellement';
                        return 0;
                    }

                    if (!in_array($this->getData('line_type'), array(self::TYPE_ABO))) {
                        $errors[] = 'Renouvellement non possible pour ce type de ligne de contrat';
                        return 0;
                    }

                    if ((int) $this->getData('id_line_renouv')) {
                        $errors[] = 'Cette ligne de contrat a déjà été renouvellée';
                        return 0;
                    }
                }
                return 1;

            case 'facRegul':
                if ($status <= 0) {
                    $errors[] = 'Le statut actuel de cette ligne de contrat ne permet pas d\'établir une facture de régularisation';
                    return 0;
                }

                if ((int) $this->getData('line_type') !== self::TYPE_ABO) {
                    $errors[] = 'Cette ligne n\'est pas un abonnement';
                    return 0;
                }

                if (!(int) $this->getData('variable_qty')) {
                    $errors[] = 'Cette ligne n\'est pas à quantité variable';
                    return 0;
                }

                return 1;

            case 'setResiliateDate':
                if ($status !== self::STATUS_ACTIVE) {
                    $errors[] = 'Cet abonnement n\'est pas actif';
                    return 0;
                }

                if ((int) $this->getData('line_type') !== self::TYPE_ABO) {
                    $errors[] = 'Cette ligne n\'est pas un abonnement';
                    return 0;
                }
                break;

            case 'addUnits':
                if ($this->isLoaded()) {
                    if ((int) $this->getData('statut') != self::STATUS_ACTIVE) {
                        $errors[] = 'Cette ligne n\'est pas active';
                        return 0;
                    }

                    if ((int) $this->getData('id_parent_line')) {
                        $errors[] = 'Sous-ligne : veuillez ajouter les unités à la ligne parente';
                        return 0;
                    }

                    if ($this->getDateFinReele() <= date('Y-m-d') . ' 00:00:00') {
                        $errors[] = 'Date de fin dépassée';
                        return 0;
                    }

                    if ($this->isResiliated()) {
                        $errors[] = 'Cet abonnement est en cours de résiliation';
                        return 0;
                    }
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isActive()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if ((int) $this->getData('statut') !== self::STATUS_ACTIVE) {
            return 0;
        }

        if ($this->getDateFinReele() < date('Y-m-d H:i:s')) {
            return 0;
        }

        return 1;
    }

    public function isResiliated()
    {
        if ((int) $this->getData('statut') > 0) {
            $date_cloture = $this->getData('date_cloture');
            if ($date_cloture && $date_cloture < $this->getData('date_fin_validite')) {
                return 1;
            }
        }

        return 0;
    }

    public function isSubline()
    {
        if ((int) $this->getData('id_parent_line')) {
            $parent_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $this->getData('id_parent_line'));

            if (BimpObject::objectLoaded($parent_line)) {
                return 1;
            }
        }

        return 0;
    }

    public function areBulkActionsAllowed()
    {
        if ((int) $this->getData('id_parent_line')) {
            return 0;
        }

        return 1;
    }

    // Getters params:

    public function getModalView()
    {
        switch ((int) $this->getData('line_type')) {
            case self::TYPE_TEXT:
                return 'text';

            case self::TYPE_ABO:
                return 'abonnement';
        }

        return 'abonnement';
    }

    public function getListHeaderButtons($list_name = 'default')
    {
        $buttons = array();

        switch ($list_name) {
            case 'contrat':
                $contrat = $this->getParentInstance();

                if (BimpObject::objectLoaded($contrat) && $contrat->areLinesEditable() && $this->can('create')) {
                    $buttons[] = array(
                        'label'   => 'Abonnenement',
                        'icon'    => 'fas_calendar-alt',
                        'onclick' => $this->getJsLoadModalForm('abonnement', 'Ajouter un abonnement', array(
                            'fk_contrat' => (int) $this->getData('fk_contrat')
                        ))
                    );

                    $buttons[] = array(
                        'label'   => 'Text',
                        'icon'    => 'fas_align-left',
                        'onclick' => $this->getJsLoadModalForm('text', 'Ajouter une ligne de texte', array(
                            'fk_contrat' => (int) $this->getData('fk_contrat')
                        ))
                    );
                }
                break;

            case 'facturation':
                if ($this->canSetAction('periodicFacProcess')) {
                    $buttons[] = array(
                        'label'   => 'Traitement en masse des facturations',
                        'icon'    => 'fas_cogs',
                        'onclick' => $this->getJsActionOnclick('periodicFacProcess', array(
                            'operation_type' => 'fac',
                            'id_client'      => (isset($this->periods_list_id_client) ? (int) $this->periods_list_id_client : 0),
                            'id_contrat'     => (isset($this->periods_list_id_contrat) ? (int) $this->periods_list_id_client : 0),
                            'id_product'     => (isset($this->periods_list_id_product) ? (int) $this->periods_list_id_product : 0)
                                ), array(
                            'form_name'        => 'periodic_process',
                            'on_form_submit'   => 'function($form, extra_data) { return BimpContrat.onPeriodicFacProcessFormSubmit($form, extra_data); }',
                            'use_bimpdatasync' => true,
                            'use_report'       => true
                        ))
                    );
                }
                break;

            case 'achat':
                if ($this->canSetAction('periodicAchatProcess')) {
                    $buttons[] = array(
                        'label'   => 'Traitement en masse des achats',
                        'icon'    => 'fas_cogs',
                        'onclick' => $this->getJsActionOnclick('periodicAchatProcess', array(
                            'operation_type' => 'achat',
                            'id_client'      => (isset($this->periods_list_id_client) ? (int) $this->periods_list_id_client : 0),
                            'id_contrat'     => (isset($this->periods_list_id_contrat) ? (int) $this->periods_list_id_client : 0),
                            'id_product'     => (isset($this->periods_list_id_product) ? (int) $this->periods_list_id_product : 0),
                            'id_fourn'       => (isset($this->periods_list_id_fourn) ? (int) $this->periods_list_id_fourn : 0),
                                ), array(
                            'form_name'        => 'periodic_process',
                            'on_form_submit'   => 'function($form, extra_data) { return BimpContrat.onPeriodicAchatProcessFormSubmit($form, extra_data); }',
                            'use_bimpdatasync' => true,
                            'use_report'       => true
                        ))
                    );
                }
                break;
        }


        return $buttons;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        if ($this->isActionAllowed('activate') && $this->canSetAction('activate')) {
            $buttons[] = array(
                'label'   => 'Activer',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('activate', array(), array(
                    'form_name' => 'activate'
                ))
            );
        }

        if ((int) $this->getData('statut') > 0) {
            $prod = $this->getChildObject('product');

//            $buttons[] = array(
//                'label'   => 'Liste des facturations effectuées',
//                'icon'    => 'fas_file-invoice-dollar',
//                'onclick' => $this->getJsLoadModalCustomContent('renderFacturesTable', 'Facturations' . (BimpObject::objectLoaded($prod) ? ' - ' . $prod->getRef() . ' ' . $prod->getName() : ''))
//            );
//
//            $buttons[] = array(
//                'label'   => 'Liste des achats effectués',
//                'icon'    => 'fas_cart-arrow-down',
//                'onclick' => $this->getJsLoadModalCustomContent('renderAchatsTable', 'Achats ' . (BimpObject::objectLoaded($prod) ? ' - ' . $prod->getRef() . ' ' . $prod->getName() : ''))
//            );

            $buttons[] = array(
                'label'   => 'Synthèse facturations / achats',
                'icon'    => 'fas_list',
                'onclick' => $this->getJsLoadModalCustomContent('renderFacAchatsSynthese', 'Facturation / achats ' . (BimpObject::objectLoaded($prod) ? ' - ' . $prod->getRef() . ' ' . $prod->getName() : ''))
            );
        }

        if ($this->isActionAllowed('addUnits') && $this->canSetAction('addUnits')) {
            $buttons[] = array(
                'label'   => 'Ajouter / retirer des unités',
                'icon'    => 'fas_plus',
                'onclick' => $this->getJsActionOnclick('addUnits', array(
                    'id_objects' => array($this->id)
                        ), array(
                    'form_name'      => 'add_units',
                    'on_form_submit' => 'function($form, extra_data) { return BimpContrat.onAddUnitsFormSubmit($form, extra_data); }'
                ))
            );
        }

        if ($this->isActionAllowed('facRegul') && $this->canSetAction('facRegul')) {
            $buttons[] = array(
                'label'   => 'Facture de régularisation',
                'icon'    => 'fas_file-medical',
                'onclick' => $this->getJsActionOnclick('facRegul', array(), array(
                    'form_name' => 'fac_regul'
                ))
            );
        }

        if ($this->isActionAllowed('renouv') && $this->canSetAction('renouv')) {
            $buttons[] = array(
                'label'   => 'Renouveller',
                'icon'    => 'fas_redo',
                'onclick' => $this->getJsActionOnclick('renouv', array(), array(
                    'form_name'      => 'renouvellement',
                    'on_form_submit' => 'function($form, extra_data) { return BimpContrat.onRenouvAbonnementFormSubmit($form, extra_data); }'
                ))
            );
        }

        if ($this->isActionAllowed('setResiliateDate') && $this->canSetAction('setResiliateDate')) {
            $buttons[] = array(
                'label'   => ($this->getData('date_cloture') ? 'Annuler / modifier la résiliation' : 'Résilier'),
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('setResiliateDate', array(), array(
                    'form_name'      => 'date_cloture',
                    'on_form_submit' => 'function($form, extra_data) { return BimpContrat.onResiliateAbonnementFormSubmit($form, extra_data); }'
                ))
            );
        }

        if ($this->isActionAllowed('deactivate') && $this->canSetAction('deactivate')) {
            $buttons[] = array(
                'label'   => 'Désactiver',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('deactivate', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        if ($this->isActionAllowed('checkDateNextFac') && $this->canSetAction('checkDateNextFac')) {
            $buttons[] = array(
                'label'   => 'Vérif date next fac (dev)',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('checkDateNextFac', array(), array())
            );
        }

        return $buttons;
    }

    public function getListsBulkActions($list_name = 'default')
    {
        $id_contrat = 0;
        if (BimpTools::getValue('fc', '', 'aZ09comma') === 'contrat') {
            $id_contrat = (int) BimpTools::getValue('id', 0, 'int');
        }

        $actions = array();

        if ($this->canEdit()) {
            $actions[] = array(
                'label'   => 'Editer les dates d\'activation des lignes sélectionnées',
                'icon'    => 'fas_calendar-alt',
                'onclick' => $this->getJsBulkActionOnclick('bulkEdit', array(), array(
                    'form_name'     => 'bulk_edit',
                    'single_action' => true
                ))
            );
        }

        if (in_array($list_name, array('global', 'contrat'))) {
            if ($this->canDelete()) {
                $actions[] = array(
                    'label'   => 'supprimer les lignes sélectionnées',
                    'icon'    => 'fas_trash-alt',
                    'onclick' => 'deleteSelectedObjects(\'list_id\', $(this))'
                );
            }

            if ($this->canSetAction('activate')) {
                $actions[] = array(
                    'label'   => 'Activer les lignes sélectionnées',
                    'icon'    => 'fas_check-circle',
                    'onclick' => $this->getJsBulkActionOnclick('activate', array(), array(
                        'confirm_msg'   => 'Veuillez confirmer',
                        'single_action' => true
                    ))
                );
            }
        }

        if (in_array($list_name, array('contrat', 'facturation'))) {
            if ($this->canSetAction('periodicFacProcess')) {
                $actions[] = array(
                    'label'   => 'Traiter les facturations périodiques des lignes sélectionnées',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => $this->getJsBulkActionOnclick('periodicFacProcess', array(
                        'operation_type' => 'fac'
                            ), array(
                        'form_name'        => 'periodic_process',
                        'on_form_submit'   => 'function($form, extra_data) { return BimpContrat.onPeriodicFacProcessFormSubmit($form, extra_data); }',
                        'use_bimpdatasync' => true,
                        'use_report'       => true
                    ))
                );
            }
        }

        if (in_array($list_name, array('contrat', 'achat'))) {
            if ($this->canSetAction('periodicAchatProcess')) {
                $actions[] = array(
                    'label'   => 'Traiter les achats périodiques des lignes sélectionnées',
                    'icon'    => 'fas_cart-arrow-down',
                    'onclick' => $this->getJsBulkActionOnclick('periodicAchatProcess', array(
                        'operation_type' => 'achat'
                            ), array(
                        'form_name'        => 'periodic_process',
                        'on_form_submit'   => 'function($form, extra_data) { return BimpContrat.onPeriodicAchatProcessFormSubmit($form, extra_data); }',
                        'use_bimpdatasync' => true,
                        'use_report'       => true
                    ))
                );
            }
        }

        if (in_array($list_name, array('contrat')) && $id_contrat) {
            if ($this->canSetAction('addUnits')) {
                $actions[] = array(
                    'label'   => 'Ajouter / retirer des unités aux lignes sélectionnées',
                    'icon'    => 'fas_plus',
                    'onclick' => $this->getJsBulkActionOnclick('addUnits', array(
                        'fk_contrat' => $id_contrat
                            ), array(
                        'form_name'      => 'add_units',
                        'on_form_submit' => 'function($form, extra_data) { return BimpContrat.onAddUnitsFormSubmit($form, extra_data); }'
                    ))
                );
            }

            if ($this->canSetAction('renouv')) {
                $actions[] = array(
                    'label'   => 'Renouveller les lignes sélectionnées',
                    'icon'    => 'fas_redo',
                    'onclick' => $this->getJsBulkActionOnclick('renouv', array(
                        'fk_contrat' => $id_contrat
                            ), array(
                        'form_name' => 'bulk_renouvellement'
                    ))
                );
            }

            if ($this->canSetAction('MoveToOtherContrat')) {
                $actions[] = array(
                    'label'   => 'Déplacer vers un autre contrat',
                    'icon'    => 'fas_sign-out-alt',
                    'onclick' => $this->getJsBulkActionOnclick('MoveToOtherContrat', array(
                        'id_contrat_src' => $id_contrat
                            ), array(
                        'form_name' => 'move'
                    ))
                );
            }
        }

        return $actions;
    }

    public function getEditForm()
    {
        switch ((int) $this->getData('line_type')) {
            case self::TYPE_TEXT:
                return 'text';

            case self::TYPE_ABO:
                return 'abonnement';
        }

        return '';
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'periodic_facs_to_process':
                if (!empty($values)) {
                    $lines = $this->getPeriodicFacLinesToProcess(array(
                        'return' => 'list'
                    ));

                    foreach ($values as $value) {
                        if (!empty($lines)) {
                            $filters = BimpTools::mergeSqlFilter($filters, $main_alias . '.rowid', array(
                                        ((int) $value ? 'in' : 'not_in') => $lines
                                            ), 'or');
                        } elseif ($value) {
                            $filters[$main_alias . '.rowid'] = '< 0';
                        }
                    }
                }
                break;

            case 'periodic_achats_to_process':
                if (!empty($values)) {
                    $lines = $this->getPeriodicAchatLinesToProcess(array(
                        'return' => 'list'
                    ));

                    foreach ($values as $value) {
                        if (!empty($lines)) {
                            $filters = BimpTools::mergeSqlFilter($filters, $main_alias . '.rowid', array(
                                        ((int) $value ? 'in' : 'not_in') => $lines
                                            ), 'or');
                        } elseif ($value) {
                            $filters[$main_alias . '.rowid'] = '< 0';
                        }
                    }
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    // Getters données:

    public function getLastClientPropalId()
    {
        $contrat = $this->getParentInstance();

        if (BimpObject::objectLoaded($contrat)) {
            $id_client = (int) $contrat->getData('fk_soc');

            if ($id_client) {
                return (int) $this->db->getValue('propal', 'rowid', 'fk_soc = ' . $id_client . ' AND fk_statut = 0', 'rowid', 'desc');
            }
        }

        return -1;
    }

    public function getDataAtDate($field_name, $date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        if (is_null($this->data_at_date)) {
            $this->fetchDataAtDates();
        }

        $return = $this->getData($field_name);

        if (isset($this->data_at_date[$field_name])) {
            foreach ($this->data_at_date as $data_date => $value) {
                if ($data_date <= $date) {
                    $return = $value;
                }
            }
        }

        return $return;
    }

    public function getValueForProduct($field_name, $prod = null)
    {
        if (!BimpObject::objectLoaded($prod)) {
            $prod = $this->getChildObject('product');
        }

        if (BimpObject::objectLoaded($prod)) {
            switch ($field_name) {
                case 'subprice':
                    return (float) $prod->getData('price');

                case 'tva_tx':
                    return (float) $prod->getData('tva_tx');

                case 'fk_product_fournisseur_price':
                    $id_fourn = null;
                    if ((int) $this->getData('line_type') === self::TYPE_ABO) {
                        $id_fourn = (int) $prod->getData('achat_def_id_fourn');
                    }
                    return $prod->getCurrentFournPriceId($id_fourn, true);

                case 'buy_price_ht':
                    if ((int) $this->getData('fk_product_fournisseur_price')) {
                        $pfp = $this->getChildObject('fourn_price');
                        if (BimpObject::objectLoaded($pfp)) {
                            return (float) $pfp->getData('price');
                        }
                    }

                    $id_fourn = (int) $prod->getData('achat_def_id_fourn');
                    return $prod->getCurrentPaHt($id_fourn);

                case 'fac_periodicity':
                    return (int) $prod->getData('fac_def_periodicity');

                case 'achat_periodicity':
                    $def_periodicity = (int) $prod->getData('achat_def_periodicity');
                    if ($def_periodicity === -1) {
                        return (int) $prod->getData('fac_def_periodicity');
                    }
                    return $def_periodicity;

                case 'variable_pu_ht':
                    return (int) $prod->getData('variable_pu_ht');

                case 'variable_qty':
                    return (int) $prod->getData('variable_qty');

                case 'description':
                    return (string) $prod->getData('description');
            }
        }

        return 0;
    }

    public function getInputValue($field_name)
    {
        $value = $this->getData($field_name);

        if (in_array($field_name, array('fac_periodicity', 'fac_term', 'achat_periodicity', 'duration'))) {
            $id_linked_line = (int) BimpTools::getPostFieldValue('id_linked_line', 0, 'int');
            if ($id_linked_line && $id_linked_line !== (int) $this->getInitData('id_linked_line')) {
                // Si la ligne liée vient de changer, on reprend les même params: 
                $linked_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_linked_line);
                if (BimpObject::objectLoaded($linked_line) && (int) $this->getData('fk_product') === (int) $linked_line->getData('fk_product')) {
                    return $linked_line->getData($field_name);
                }
            }
        }

        switch ($field_name) {
            case 'subprice':
            case 'tva_tx':
            case 'fk_product_fournisseur_price':
            case 'buy_price_ht':
            case 'fac_periodicity':
            case 'achat_periodicity':
            case 'variable_pu_ht':
            case 'variable_qty':
            case 'description':
                if ((int) $this->getData('fk_product') !== (int) $this->getInitData('fk_product') ||
                        ($field_name == 'buy_price_ht' &&
                        (int) $this->getData('fk_product_fournisseur_price') !== (int) $this->getInitData('fk_product_fournisseur_price'))) {
                    return $this->getValueForProduct($field_name);
                }
                break;

            case 'qty_per_period':
                return $this->getFacQtyPerPeriod();

            case 'date_ouverture':
//            case 'date_fac_start':
//            case 'date_achat_start':
                $date = $this->getData($field_name);
                if (!$date) {
                    $date = $this->getData('date_ouverture_prevue');
                }
                return $date;
        }

        return $value;
    }

    public function getFacNbPeriods()
    {
        $duration = (int) $this->getData('duration');
        $fac_periodicity = (int) $this->getData('fac_periodicity');

        if ($duration && $fac_periodicity) {
            return $duration / $fac_periodicity;
        }

        return 0;
    }

    public function getAchatNbPeriods()
    {
        $duration = (int) $this->getData('duration');
        $fac_periodicity = (int) $this->getData('achat_periodicity');

        if ($duration && $fac_periodicity) {
            return $duration / $fac_periodicity;
        }

        return 0;
    }

    public function getFacQtyPerPeriod()
    {
        $total_qty = (float) $this->getData('qty');
        $nb_periods = $this->getFacNbPeriods();
        if ($total_qty && $nb_periods) {
            return $total_qty / $nb_periods;
        }

        return 0;
    }

    public function getAchatQtyPerPeriod()
    {
        $total_qty = (float) $this->getData('qty');
        $nb_periods = $this->getAchatNbPeriods();
        if ($total_qty && $nb_periods) {
            return $total_qty / $nb_periods;
        }

        return 0;
    }

    public function getDateFacStart()
    {
        $date_fac_start = $this->getData('date_fac_start');

        if (!$date_fac_start) {
            $date_fac_start = $this->getData('date_ouverture');

            if ($date_fac_start) {
                $date_fac_start = date('Y-m-d', strtotime($date_fac_start));
            } else {
                $date_fac_start = $this->getData('date_debut_validite');
            }
        }

        return $date_fac_start;
    }

    public function getDateNextFacture($check_date = false, &$errors = array(), &$infos = array())
    {
        if (!$this->isLoaded() || (int) $this->getData('statut') <= 0) {
            return '';
        }

        $date = $this->getData('date_next_facture');
        $date_fac_start = $this->getDateFacStart();

        if (!$date || $date < $date_fac_start || $check_date) {
            $check_errors = array();
            $new_date = '';
//            $sel_nb_avoirs = '(SELECT COUNT(av.rowid) FROM ' . MAIN_DB_PREFIX . 'facture av WHERE av.fk_facture_source = f.rowid)';

            $sel_nb_avoirs = '(SELECT COUNT(avl.id) FROM ' . MAIN_DB_PREFIX . 'bimp_facture_line avl';
            $sel_nb_avoirs .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture av ON avl.id_obj = av.rowid';
            $sel_nb_avoirs .= ' WHERE avl.linked_object_name = \'contrat_line\'';
            $sel_nb_avoirs .= ' AND avl.linked_id_object = ' . $this->id;
            $sel_nb_avoirs .= ' AND av.fk_facture_source = f.rowid)';
            $sql = BimpTools::getSqlFullSelectQuery('facturedet', array('MAX(a.date_end) as max_date'), array(
                        'f.type'                => array(0, 1, 2),
                        'f.fk_statut'           => array(0, 1, 2),
                        'f.fk_facture_source'   => array(
                            'or_field' => array(
                                'IS_NULL',
                                0
                            )
                        ),
                        $sel_nb_avoirs          => 0,
                        'fl.linked_object_name' => 'contrat_line',
                        'fl.linked_id_object'   => $this->id
                            ), array(
                        'fl' => array(
                            'table' => 'bimp_facture_line',
                            'on'    => 'fl.id_line = a.rowid'
                        ),
                        'f'  => array(
                            'table' => 'facture',
                            'on'    => 'f.rowid = a.fk_facture'
                        )
            ));

            $res = $this->db->executeS($sql, 'array');

            if (is_null($res)) {
                $check_errors[] = 'Echec de la vérification de la date de prochaine facturation - ' . $this->db->err();
            }

            if (isset($res[0]['max_date']) && $res[0]['max_date']) {
                $dt = new DateTime($res[0]['max_date']);
                $dt->add(new DateInterval('P1D'));
                $new_date = $dt->format('Y-m-d');
                $infos[] = 'Date dernière fac + 1 jour : ' . $new_date;
            } elseif ($date_fac_start) {
                $new_date = $date_fac_start;
                $infos[] = 'Date fac start : ' . $new_date;
            }

            if ($new_date) {
                if ($new_date < $date_fac_start) {
                    if (!(int) $this->getData('fac_term')) {
                        $date_debut = $this->getData('date_debut_validite');
                        $periodicity = (int) $this->getData('fac_periodicity');
                        if (!$date_debut) {
                            $check_errors[] = 'Date de début de validité non définie';
                        }
                        if (!$periodicity) {
                            $check_errors[] = 'Périodicité non définie';
                        }

                        if (!count($check_errors)) {
                            // calcul du terme échu de la première période facturée partiellement : 
                            $interval = BimpTools::getDatesIntervalData($date_debut, $date_fac_start);
                            $nb_periods = floor($interval['nb_monthes_decimal'] / $periodicity) + 1;
                            $dt = new DateTime($date_debut);
                            $dt->add(new DateInterval('P' . ($nb_periods * $periodicity) . 'M'));
                            $new_date = $dt->format('Y-m-d');

                            $infos[] = 'Date fac échue  : ' . $new_date;
                        }
                    } else {
                        $new_date = $date_fac_start;
                        $infos[] = 'Date fac start 2 : ' . $new_date;
                    }
                } elseif (!(int) $this->getData('fac_term')) {
                    $dt = new DateTime($new_date);
                    $dt->add(new DateInterval('P' . (int) $this->getData('fac_periodicity') . 'M'));
                    $new_date = $dt->format('Y-m-d');
                    $infos[] = 'Terme échu : ' . $new_date;
                }
            }

//            if ($new_date && !(int) $this->getData('fac_term')) {
//                $date_fin = $this->getData('date_fin_validite');
//
//                if ($date_fin) {
//                    $date_fin = date('Y-m-d', strtotime($date_fin));
//
//                    if ($new_date > $date_fin) {
//                        $new_date = $date_fin;
//                        $infos[] = 'Terme échu - ajusté sur date fin : ' . $new_date;
//                    }
//                } else {
//                    $infos[] = 'date fin ok : ' . $date_fin;
//                }
//            }

            if ($new_date && $new_date != $date) {
                $infos[] = 'NEW DATE SET : ' . $new_date;
                $check_errors = $this->updateField('date_next_facture', date('Y-m-d', strtotime($new_date)));

                if (!count($check_errors)) {
                    BimpCore::addlog('Correction automatique de la date de prochaine facturation d\'un abonnement', Bimp_Log::BIMP_LOG_NOTIF, 'contrat', $this, array(
                        'Ancienne date' => $date,
                        'Nouvelle date' => $new_date
                    ));

                    $date = $new_date;
                }
            }

            if (count($check_errors)) {
                $errors[] = BimpTools::getMsgFromArray($check_errors);
                BimpCore::addlog('Echec de la correction automatique de la date de prochaine facturation d\'un abonnement', Bimp_Log::BIMP_LOG_ERREUR, 'contrat', $this, array(
                    'Ancienne date' => $date,
                    'Nouvelle date' => $new_date,
                    'Erreurs'       => $check_errors
                ));
            }
        }

        return $date;
    }

    public function getDateAchatStart()
    {
        $date_achat_start = $this->getData('date_achat_start');

        if (!$date_achat_start) {
            $date_achat_start = $this->getData('date_ouverture');

            if ($date_achat_start) {
                $date_achat_start = date('Y-m-d', strtotime($date_achat_start));
            } else {
                $date_achat_start = $this->getData('date_debut_validite');
            }
        }

        return $date_achat_start;
    }

    public function getDateNextAchat($check_date = false, &$errors = array())
    {
        if (!$this->isLoaded() || (int) $this->getData('statut') <= 0) {
            return '';
        }

        $date = $this->getData('date_next_achat');
        $date_achat_start = $this->getDateAchatStart();

        if (!$date || $date < $date_achat_start || $check_date) {
            $check_errors = array();
            $new_date = '';
            $sql = BimpTools::getSqlFullSelectQuery('commande_fournisseurdet', array('MAX(a.date_end) as max_date'), array(
                        'cf.fk_statut'           => array(0, 1, 2, 3, 4, 5),
                        'cfl.linked_object_name' => 'contrat_line',
                        'cfl.linked_id_object'   => $this->id
                            ), array(
                        'cfl' => array(
                            'table' => 'bimp_commande_fourn_line',
                            'on'    => 'cfl.id_line = a.rowid'
                        ),
                        'cf'  => array(
                            'table' => 'commande_fournisseur',
                            'on'    => 'cf.rowid = a.fk_commande'
                        )
            ));

            $res = $this->db->executeS($sql, 'array');

            if (is_null($res)) {
                $check_errors[] = 'Echec de la vérification de la date de prochain achat - ' . $this->db->err();
            }

            if (isset($res[0]['max_date']) && $res[0]['max_date']) {
                $dt = new DateTime($res[0]['max_date']);
                $dt->add(new DateInterval('P1D'));
                $new_date = $dt->format('Y-m-d');
            } else {
                $new_date = $date_achat_start;
            }

            if ($new_date) {
                if ($new_date < $date_achat_start) {
                    $new_date = $date_achat_start;
                }

                if ($new_date != $date) {
                    $check_errors = $this->updateField('date_next_achat', $new_date);

                    if (!count($check_errors)) {
                        BimpCore::addlog('Correction automatique de la date de prochain achat d\'un abonnement', Bimp_Log::BIMP_LOG_NOTIF, 'contrat', $this, array(
                            'Ancienne date' => $date,
                            'Nouvelle date' => $new_date
                        ));

                        $date = $new_date;
                    }
                }
            }

            if (count($check_errors)) {
                $errors[] = BimpTools::getMsgFromArray($check_errors);
                BimpCore::addlog('Echec de la correction automatique de la date de prochain achat d\'un abonnement', Bimp_Log::BIMP_LOG_ERREUR, 'contrat', $this, array(
                    'Ancienne date' => $date,
                    'Nouvelle date' => $new_date,
                    'Erreurs'       => $check_errors
                ));
            }
        }

        return $date;
    }

    public function getDateFinReele()
    {
        $date_fin = $this->getData('date_fin_validite');
        $date_cloture = $this->getData('date_cloture');

        if ($date_cloture && $date_cloture < $date_fin) {
            return $date_cloture;
        }

        return $date_fin;
    }

    public function getPeriodsToBillData(&$errors = array(), $check_date = true, $check_remaining_periods_to_bill = false)
    {
        $data = array(
            'date_next_facture'       => '', // Date prochaine facture
            'date_next_period_tobill' => '', // Date début de la prochaine période à facturer (différent de date_next_facture si facturation à terme échu)
//            'date_first_fac'          => '', // Date première facture
            'date_fac_start'          => '', // Date début de facturation réelle (cas des facturation partielles / différent de date_first_fac si facturation à terme échu)
            'nb_total_periods'        => 0, // Nombre total de périodes
            'nb_periods_billed'       => 0, // Nombre de périodes déjà facturées
            'nb_periods_tobill_max'   => 0, // Nombre total de périodes restant à facturer. 
            'nb_periods_tobill_today' => 0, // Nombre de périodes à facturer à date.
            'nb_periods_before_start' => 0, // Nombre de périodes avant 1ère période facturée
            'nb_periods_never_billed' => 0, // Nombre de périodes non facturées en cas de résiliation
            'qty_for_1_period'        => 0,
            'first_period_prorata'    => 1, // Prorata de facturation de la première période
            'date_first_period_start' => '', // Début de la première période facturée
            'date_first_period_end'   => '', // Fin de la première période facturée
            'debug'                   => array()
        );

        if ($this->isLoaded()) {
            $total_qty = (float) $this->getData('qty');

            if (!(float) $total_qty) {
                return $data;
            }

            $periodicity = (int) $this->getData('fac_periodicity');
            $duration = (int) $this->getData('duration');

            if ($periodicity && $duration) {
                $periodic_interval = new DateInterval('P' . $periodicity . 'M');
                $date_now = date('Y-m-d');
                $is_echu = (!(int) $this->getData('fac_term')); // Facturation à terme échu
                $data['nb_total_periods'] = ceil($duration / $periodicity);

                $date_debut = $this->getData('date_debut_validite');

                if (!$date_debut) {
                    $errors[] = 'Date de début de validité non définie';
                    return $data;
                }

                $date_debut = date('Y-m-d', strtotime($date_debut));

                $date_fin = $this->getData('date_fin_validite');
                if (!$date_fin) {
                    $errors[] = 'Date de fin de validité non définie';
                    return $data;
                } else {
                    $date_fin = date('Y-m-d', strtotime($date_fin));
                }

                $data['debug']['date_debut'] = $date_debut;
                $data['debug']['date_fin'] = $date_fin;

                // Date prochaine facture : 
                $date_next_facture = $this->getDateNextFacture($check_date, $errors);
                if (!$date_next_facture) {
                    $errors[] = 'Date de prochaine facturation non définie';
                    return $data;
                }
                $data['date_next_facture'] = $date_next_facture;

                // Date début des facturations :
                $date_fac_start = $this->getDateFacStart();
                $data['date_fac_start'] = $date_fac_start;

                // Date prochaine période à facturer : 
                $date_next_period_tobill = $date_next_facture;
                if ($is_echu) {
                    $dt = new DateTime($date_next_period_tobill);
                    $dt->sub($periodic_interval);
                    $date_next_period_tobill = $dt->format('Y-m-d');
                }
                if ($date_next_period_tobill < $date_fac_start) {
                    $date_next_period_tobill = $date_fac_start;
                }

                // Cas d'une première facturation sur une période partielle
                if ($date_fac_start != $date_debut) {
                    // Calcul du début de la première période facturée partiellement : 
                    $interval = BimpTools::getDatesIntervalData($date_debut, $date_fac_start);
                    $data['debug']['nb_periods_before_start'] = array(
                        'interval'      => $interval,
                        'value_decimal' => $interval['nb_monthes_decimal'] / $periodicity
                    );

                    $nb_periods = floor($interval['nb_monthes_decimal'] / $periodicity); // Nombre de périodes entières avant début de la première période à facturer partiellement
                    $dt = new DateTime($date_debut);
                    if ($nb_periods > 0) {
                        $dt->add(new DateInterval('P' . ($nb_periods * $periodicity) . 'M'));
                        $data['nb_periods_before_start'] = $nb_periods;
                    }
                    // Ne pas faire de sub la 1ère période doit être celle de l'abo de base avec un prorata > 1
//                    elseif ($nb_periods < 0) {
//                        $dt->sub(new DateInterval('P' . (abs($nb_periods) * $periodicity) . 'M'));
//                    }
                    $data['date_first_period_start'] = $dt->format('Y-m-d');

                    // Calcul de la fin de la première période facturée partiellement : 
                    $dt->add($periodic_interval);
                    $dt->sub(new DateInterval('P1D'));
                    $data['date_first_period_end'] = $dt->format('Y-m-d');

                    // Calcul du prorata de facturation :
                    $interval = BimpTools::getDatesIntervalData($data['date_first_period_start'], $data['date_first_period_end']);
                    $nb_full_period_days = $interval['full_days'];

                    $interval = BimpTools::getDatesIntervalData($date_fac_start, $data['date_first_period_end']);
                    $nb_invoiced_days = $interval['full_days'];

                    if ($nb_full_period_days && $nb_invoiced_days) {
                        $data['first_period_prorata'] = ($nb_invoiced_days / $nb_full_period_days);
                    }

                    if ($date_next_period_tobill <= $data['date_first_period_end']) {
                        $date_next_period_tobill = $data['date_first_period_start'];
                    }
                } else {
                    $data['date_first_period_start'] = $date_fac_start;
                    $dt = new DateTime($date_fac_start);
                    $dt->add($periodic_interval);
                    $dt->sub(new DateInterval('P1D'));
                    $data['date_first_period_end'] = $dt->format('Y-m-d');
                }

                $data['date_next_period_tobill'] = $date_next_period_tobill;

                if (!count($errors)) {
                    // Calcul du nombre de périodes restant à facturer
                    if ($date_next_period_tobill < $date_fin) {
                        $interval = BimpTools::getDatesIntervalData($date_next_period_tobill, $date_fin);
                        if ($interval['nb_monthes_decimal'] > 0) {
                            $data['debug']['nb_periods_tobill_max'] = array(
                                'interval'      => $interval,
                                'value_decimal' => $interval['nb_monthes_decimal'] / $periodicity
                            );
                            $data['nb_periods_tobill_max'] = ceil($interval['nb_monthes_decimal'] / $periodicity);

                            if ($data['nb_periods_tobill_max'] < 0) {
                                $data['nb_periods_tobill_max'] = 0;
                            }

                            if ($data['nb_periods_tobill_max'] > $data['nb_total_periods']) {
                                $data['nb_periods_tobill_max'] = $data['nb_total_periods'];
                            }
                        }
                    }


                    $date_fin_reele = $date_fin;
                    $date_cloture = $this->getData('date_cloture');

                    if ($date_cloture) {
                        $date_cloture = date('Y-m-d', strtotime($date_cloture));

                        // Si abo résilié: 
                        if ($date_cloture < $date_fin) {
                            $interval = BimpTools::getDatesIntervalData($date_cloture, $date_fin);
                            if ($interval['nb_monthes_decimal'] > 0) {
                                $data['debug']['nb_periods_never_billed'] = array(
                                    'interval'      => $interval,
                                    'value_decimal' => $interval['nb_monthes_decimal'] / $periodicity
                                );
                                $data['nb_periods_never_billed'] = ceil($interval['nb_monthes_decimal'] / $periodicity);
                                $data['nb_periods_tobill_max'] -= $data['nb_periods_never_billed'];
                            }

                            $dt_fin = new DateTime($date_cloture);
                            $dt_fin->sub(new DateInterval('P1D'));
                            $date_fin_reele = $dt_fin->format('Y-m-d');
                        }
                    }

                    $data['nb_periods_billed'] = $data['nb_total_periods'] - $data['nb_periods_before_start'] - $data['nb_periods_tobill_max'] - $data['nb_periods_never_billed'];

                    if ($date_next_period_tobill > $date_fin_reele) {
                        if ($check_remaining_periods_to_bill) {
                            $errors[] = 'Toutes les facturations ont été effectuées';
                        }
                        return $data;
                    }

                    // Calcul du nombre de périodes à facturer aujourd'hui : 
                    if ($date_now == $date_next_facture) {
                        $data['nb_periods_tobill_today'] = 1;
                    } elseif ($date_now > $date_next_facture) {
                        $interval = BimpTools::getDatesIntervalData($date_next_period_tobill, $date_now);
                        if ($interval['nb_monthes_decimal'] > 0) {
                            $nb_periods_decimal = ($interval['nb_monthes_decimal'] / $periodicity);
                            if ($is_echu) {
                                $data['nb_periods_tobill_today'] = floor($nb_periods_decimal);
                            } else {
                                $data['nb_periods_tobill_today'] = ceil($nb_periods_decimal);
                            }

                            if ($data['nb_periods_tobill_today'] < 0) {
                                $data['nb_periods_tobill_today'] = 0;
                            }

                            if ($data['nb_periods_tobill_today'] > $data['nb_periods_tobill_max']) {
                                $data['nb_periods_tobill_today'] = $data['nb_periods_tobill_max'];
                            }
                        }
                    }
                }
            }

            if ($total_qty && $data['nb_total_periods']) {
                $data['qty_for_1_period'] = $total_qty / $data['nb_total_periods'];
            }
        }

        return $data;
    }

    public function getPeriodsToBuyData(&$errors = array(), $check_date = true, $check_remaining_periods_to_buy = false)
    {
        $data = array(
            'date_next_achat'             => '', // Date prochain achat
            'date_achat_start'            => '', // Date début des achat sréelle
            'nb_total_periods'            => 0, // Nombre total de périodes
            'nb_periods_bought'           => 0, // Nombre de périodes déjà achetées
            'nb_periods_tobuy_max'        => 0, // Nombre total de périodes restant à facturer. 
            'nb_periods_tobuy_today'      => 0, // Nombre de périodes à facturer à date.
            'nb_periods_before_start'     => 0, // Nombre de périodes avant 1ère période achetée
            'nb_periods_never_bought'     => 0, // Nombre de périodes non achetées en cas de résiliation
            'nb_periods_bought_never_fac' => 0, // Nombre de périodes achetées qui ne seront pas facturées en cas de résiliation
            'qty_for_1_period'            => 0,
            'first_period_prorata'        => 1, // Prorata de la première période
            'date_first_period_start'     => '', // Début de la première période à acheter
            'date_first_period_end'       => '', // Fin de la première période à acheter
            'debug'                       => array()
        );

        if ($this->isLoaded()) {
            $total_qty = (float) $this->getData('qty');

            if (!(float) $total_qty) {
                return $data;
            }

            $periodicity = (int) $this->getData('achat_periodicity');
            $duration = (int) $this->getData('duration');

            if ($periodicity && $duration) {
                $periodic_interval = new DateInterval('P' . $periodicity . 'M');
                $date_now = date('Y-m-d');
                $data['nb_total_periods'] = ceil($duration / $periodicity);

                $date_debut = date('Y-m-d', strtotime($this->getData('date_debut_validite')));

                if (!$date_debut) {
                    $errors[] = 'Date de début de validité non définie';
                    return $data;
                }

                $date_fin = $this->getData('date_fin_validite');
                if (!$date_fin) {
                    $errors[] = 'Date de fin de validité non définie';
                    return $data;
                } else {
                    $date_fin = date('Y-m-d', strtotime($date_fin));
                }

                // Date prochaine achat : 
                $date_next_achat = $this->getDateNextAchat($check_date, $errors);
                if (!$date_next_achat) {
                    $errors[] = 'Date de prochain achat non définie';
                    return $data;
                }
                $data['date_next_achat'] = $date_next_achat;

                // Date début des achats :
                $date_achat_start = $this->getDateAchatStart();
                $data['date_achat_start'] = $date_achat_start;

                if ($data['date_next_achat'] < $date_achat_start) {
                    $data['date_next_achat'] = $date_achat_start;
                }

                // Cas d'un premier achat sur une période partielle
                if ($date_achat_start != $date_debut) {
                    // Calcul du début de la première période : 
                    $interval = BimpTools::getDatesIntervalData($date_debut, $date_achat_start);
                    $nb_periods = floor($interval['nb_monthes_decimal'] / $periodicity); // Nombre de périodes entières avant début de la première période partielle
                    $dt = new DateTime($date_debut);
                    if ($nb_periods > 0) {
                        $dt->add(new DateInterval('P' . ($nb_periods * $periodicity) . 'M'));
                        $data['nb_periods_before_start'] = $nb_periods;
                    }
                    // Ne pas faire de sub la 1ère période doit être celle de l'abo de base avec un prorata > 1
//                    elseif ($nb_periods < 0) {
//                        $dt->sub(new DateInterval('P' . (abs($nb_periods) * $periodicity) . 'M'));
//                    }
                    $data['date_first_period_start'] = $dt->format('Y-m-d');

                    // Calcul de la fin de la première période facturée partiellement : 
                    $dt->add($periodic_interval);
                    $dt->sub(new DateInterval('P1D'));
                    $data['date_first_period_end'] = $dt->format('Y-m-d');

                    // Calcul du prorata :
                    $interval = BimpTools::getDatesIntervalData($data['date_first_period_start'], $data['date_first_period_end']);
                    $nb_full_period_days = $interval['full_days'];

                    $interval = BimpTools::getDatesIntervalData($date_achat_start, $data['date_first_period_end']);
                    $nb_first_period_days = $interval['full_days'];

                    if ($nb_full_period_days && $nb_first_period_days) {
                        $data['first_period_prorata'] = ($nb_first_period_days / $nb_full_period_days);
                    }
                } else {
                    $data['date_first_period_start'] = $date_achat_start;
                    $dt = new DateTime($date_achat_start);
                    $dt->add($periodic_interval);
                    $dt->sub(new DateInterval('P1D'));
                    $data['date_first_period_end'] = $dt->format('Y-m-d');
                }

                if (!count($errors)) {
                    // Calcul du nombre de périodes restant à acheter
                    $interval = BimpTools::getDatesIntervalData($date_next_achat, $date_fin);
                    $data['debug']['nb_periods_tobuy_max'] = array(
                        'interval' => $interval
                    );

                    if ($interval['nb_monthes_decimal'] > 0) {
                        $data['debug']['nb_periods_tobuy_max']['value_decimal'] = $interval['nb_monthes_decimal'] / $periodicity;
                        $data['nb_periods_tobuy_max'] = ceil($interval['nb_monthes_decimal'] / $periodicity);

                        if ($data['nb_periods_tobuy_max'] < 0) {
                            $data['nb_periods_tobuy_max'] = 0;
                        }

                        if ($data['nb_periods_tobuy_max'] > $data['nb_total_periods']) {
                            $data['nb_periods_tobuy_max'] = $data['nb_total_periods'];
                        }
                    }

                    $date_fin_reele = $date_fin;
                    $date_cloture = $this->getData('date_cloture');

                    // Si abo résilié: 
                    if ($date_cloture) {
                        $date_cloture = date('Y-m-d', strtotime($date_cloture));

                        if ($date_cloture < $date_fin) {
                            $interval = BimpTools::getDatesIntervalData($date_cloture, $date_fin);
                            if ($interval['nb_monthes_decimal'] > 0) {
                                $data['debug']['nb_periods_never_bought'] = array(
                                    'interval'      => $interval,
                                    'value_decimal' => $interval['nb_monthes_decimal'] / $periodicity
                                );
                                $data['nb_periods_never_bought'] = ceil($interval['nb_monthes_decimal'] / $periodicity);
                                $data['nb_periods_tobuy_max'] -= $data['nb_periods_never_bought'];
                            }

                            if ($date_cloture < $date_next_achat) {
                                $interval = BimpTools::getDatesIntervalData($date_cloture, $date_next_achat, false, false);
                                if ($interval['nb_monthes_decimal'] > 0) {
                                    $data['debug']['nb_periods_bought_never_fac'] = array(
                                        'interval'      => $interval,
                                        'value_decimal' => $interval['nb_monthes_decimal'] / $periodicity
                                    );
                                    $data['nb_periods_bought_never_fac'] = ceil($interval['nb_monthes_decimal'] / $periodicity);
                                    $data['nb_periods_never_bought'] -= $data['nb_periods_bought_never_fac'];
                                    $data['nb_periods_tobuy_max'] = 0;
                                }
                                $date_fin_reele = $date_next_achat;
                            } else {
                                $dt_fin = new DateTime($date_cloture);
                                $dt_fin->sub(new DateInterval('P1D'));
                                $date_fin_reele = $dt_fin->format('Y-m-d');
                            }
                        }
                    }

                    $data['nb_periods_bought'] = $data['nb_total_periods'] - $data['nb_periods_before_start'] - $data['nb_periods_tobuy_max'] - $data['nb_periods_never_bought'];

                    if ($check_remaining_periods_to_buy && $date_next_achat > $date_fin_reele) {
                        $errors[] = 'Tous les achats ont déjà été effectués';
                        return $data;
                    }

                    // Calcul du nombre de périodes à acheter aujourd'hui : 
                    if ($date_now == $date_next_achat) {
                        $data['nb_periods_tobuy_today'] = 1;
                    } elseif ($date_now > $date_next_achat) {
                        $interval = BimpTools::getDatesIntervalData($date_next_achat, $date_now);
                        $data['debug']['nb_periods_tobuy_today'] = array(
                            'interval' => $interval
                        );
                        if ($interval['nb_monthes_decimal'] > 0) {
                            $nb_periods_decimal = ($interval['nb_monthes_decimal'] / $periodicity);
                            $data['debug']['nb_periods_tobuy_today']['value_decimal'] = $nb_periods_decimal;
                            $data['nb_periods_tobuy_today'] = ceil($nb_periods_decimal);

                            if ($data['nb_periods_tobuy_today'] < 0) {
                                $data['nb_periods_tobuy_today'] = 0;
                            }

                            if ($data['nb_periods_tobuy_today'] > $data['nb_periods_tobuy_max']) {
                                $data['nb_periods_tobuy_today'] = $data['nb_periods_tobuy_max'];
                            }
                        }
                    }
                }
            }

            if ($total_qty && $data['nb_total_periods']) {
                $data['qty_for_1_period'] = $total_qty / $data['nb_total_periods'];
            }
        }
        return $data;
    }

    public function getFacturesLines($id_facture = 0, &$errors = array(), $regul = false)
    {
        $lines = array();

        if ($this->isLoaded($errors)) {
            $where = 'linked_object_name = \'contrat_line' . ($regul ? '_regul' : '') . '\' AND linked_id_object = ' . $this->id;

            if ($id_facture) {
                $where .= ' AND id_obj = ' . $id_facture;
            }

            $rows = $this->db->getRows('bimp_facture_line', $where, null, 'array', array('DISTINCT id'), 'id', 'asc');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', (int) $r['id']);
                    if (BimpObject::objectLoaded($line)) {
                        $lines[] = $line;
                    }
                }
            } else {
                $errors[] = 'Echec de la récupération des lignes de factures liées - ' . $this->db->err();
            }
        }

        return $lines;
    }

    public function getCommandesFournLines($id_commande_fourn = 0, &$errors = array())
    {
        $lines = array();

        if ($this->isLoaded($errors)) {
            $where = 'linked_object_name = \'contrat_line\' AND linked_id_object = ' . $this->id;

            if ($id_commande_fourn) {
                $where .= ' AND id_obj = ' . $id_commande_fourn;
            }

            $rows = $this->db->getRows('bimp_commande_fourn_line', $where, null, 'array', array('DISTINCT id'), 'id', 'asc');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $r['id']);
                    if (BimpObject::objectLoaded($line)) {
                        $lines[] = $line;
                    }
                }
            } else {
                $errors[] = 'Echec de la récupération des lignes de commande fournisseur liées - ' . $this->db->err();
            }
        }

        return $lines;
    }

    public function getBulkActivationOpenDate()
    {
        $date = '';

        $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');

        if (!empty($id_lines)) {
            $where = 'rowid IN (' . implode(',', $id_lines) . ')';
            $where .= ' AND date_ouverture_prevue IS NOT NULL AND date_ouverture_prevue != \'\'';
            $date = $this->db->getMin('contratdet', 'date_ouverture_prevue', $where);

            if ($date) {
                $date = date('Y-m-d', strtotime($date));
            }
        }

        if (!$date) {
            $date = date('Y-m-d');
        }

        return $date;
    }

    public function getTotalHT($with_remises = true)
    {
        $pu_ht = (float) $this->getData('subprice');
        $qty = (float) $this->getData('qty');

        if ($pu_ht && $qty) {

            if ($with_remises) {
                $remise = $this->getData('remise_percent');

                if ($remise) {
                    $pu_ht -= (float) ($pu_ht * ($remise / 100));
                }
            }

            return ($pu_ht * $qty);
        }

        return 0;
    }

    public function getNbUnits()
    {
        $prod_duration = 1;
        $prod = $this->getChildObject('product');
        if (BimpObject::objectLoaded($prod)) {
            $prod_duration = (int) $prod->getData('duree');
        }

        $duration = (int) $this->getData('duration');
        if (!$duration) {
            $duration = 1;
        }

        return ((float) $this->getData('qty') / $duration) * $prod_duration;
    }

    public function getFacturesData($include_reguls = false, $return = 'data')
    {
        $data = array();

//        $sel_nb_avoirs = '(SELECT COUNT(avl.id) FROM ' . MAIN_DB_PREFIX . 'bimp_facture_line avl';
//        $sel_nb_avoirs .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture av ON avl.id_obj = av.rowid';
//        $sel_nb_avoirs .= ' WHERE avl.linked_object_name IN (\'contrat_line\'' . ($include_reguls ? ',\'contrat_line_regul\'' : '') . ')';
//        $sel_nb_avoirs .= ' AND avl.id_linked_object = ' . $this->id;
//        $sel_nb_avoirs .= ' AND av.fk_facture_source = f.rowid)';

        $sql = BimpTools::getSqlFullSelectQuery('facturedet', array('f.rowid as id_facture', 'a.date_start', 'a.date_end', 'a.qty', 'fl.linked_object_name', 'a.subprice as pu_ht'), array(
                    'f.type'                => array(0, 1, 2),
                    'f.fk_statut'           => array(0, 1, 2),
//                    'f.fk_facture_source'   => array(
//                        'or_field' => array(
//                            'IS_NULL',
//                            0
//                        )
//                    ),
//                    $sel_nb_avoirs          => 0,
                    'fl.linked_object_name' => ($include_reguls ? array('contrat_line', 'contrat_line_regul') : 'contrat_line'),
                    'fl.linked_id_object'   => $this->id
                        ), array(
                    'fl' => array(
                        'table' => 'bimp_facture_line',
                        'on'    => 'fl.id_line = a.rowid'
                    ),
                    'f'  => array(
                        'table' => 'facture',
                        'on'    => 'f.rowid = a.fk_facture'
                    )
                        ), array(
                    'order_by'  => 'a.date_start',
                    'order_way' => 'ASC'
        ));

        $rows = $this->db->executeS($sql, 'array');

        switch ($return) {
            case 'data':
                foreach ($rows as $r) {
                    $data[] = array(
                        'id_facture' => (int) $r['id_facture'],
                        'from'       => date('Y-m-d', strtotime($r['date_start'])),
                        'to'         => date('Y-m-d', strtotime($r['date_end'])),
                        'qty'        => $r['qty'],
                        'pu_ht'      => (float) $r['pu_ht'],
                        'is_regul'   => ($r['linked_object_name'] == 'contrat_line_regul' ? 1 : 0)
                    );
                }
                break;

            case 'qties':
                $data = array(
                    'fac_qty'   => 0,
                    'regul_qty' => 0
                );
                foreach ($rows as $r) {
                    if ($r['linked_object_name'] == 'contrat_line_regul') {
                        $data['regul_qty'] += (float) $r['qty'];
                    } else {
                        $data['fac_qty'] += (float) $r['qty'];
                    }
                }
                break;
        }

        return $data;
    }

    public function getCommandesFournData($include_reguls = false, $return = 'data')
    {
        $data = array();

        $sql = BimpTools::getSqlFullSelectQuery('commande_fournisseurdet', array('cf.rowid as id_cf', 'cfl.id as id_line', 'a.date_start', 'a.date_end', '(a.qty + cfl.qty_modif) as full_qty', 'cfl.linked_object_name', 'a.subprice as pa_ht'), array(
                    'cf.fk_statut'           => array(
                        'operator' => '<',
                        'value'    => 6
                    ),
                    'cfl.linked_object_name' => ($include_reguls ? array('contrat_line', 'contrat_line_regul') : 'contrat_line'),
                    'cfl.linked_id_object'   => $this->id
                        ), array(
                    'cfl' => array(
                        'table' => 'bimp_commande_fourn_line',
                        'on'    => 'cfl.id_line = a.rowid'
                    ),
                    'cf'  => array(
                        'table' => 'commande_fournisseur',
                        'on'    => 'cf.rowid = a.fk_commande'
                    )
                        ), array(
                    'order_by'  => 'a.date_start',
                    'order_way' => 'ASC'
        ));

        $rows = $this->db->executeS($sql, 'array');

        switch ($return) {
            case 'data':
                foreach ($rows as $r) {
                    $data[] = array(
                        'id_cf'    => (int) $r['id_cf'],
                        'id_line'  => (int) $r['id_line'],
                        'from'     => date('Y-m-d', strtotime($r['date_start'])),
                        'to'       => date('Y-m-d', strtotime($r['date_end'])),
                        'qty'      => $r['full_qty'],
                        'pa_ht'    => (float) $r['pa_ht'],
                        'is_regul' => ($r['linked_object_name'] == 'contrat_line_regul' ? 1 : 0)
                    );
                }
                break;

            case 'qties':
                $data = array(
                    'achat_qty' => 0,
                    'regul_qty' => 0
                );
                foreach ($rows as $r) {
                    if ($r['linked_object_name'] == 'contrat_line_regul') {
                        $data['regul_qty'] += (float) $r['full_qty'];
                    } else {
                        $data['achat_qty'] += (float) $r['full_qty'];
                    }
                }
                break;
        }

        return $data;
    }

    public function getQtiesToInvoice($periods_data = null, &$errors = array())
    {
        $data = array();

        if (is_null($periods_data)) {
            $periods_data = $this->getPeriodsToBillData($errors, true, true);
        }

        $fac_periodicity = (int) $this->getData('fac_periodicity');

        if (!$fac_periodicity) {
//            $errors[] = 'Périodicité de facturation non définie';
        }

        if (!count($errors)) {
            $fac_data = $this->getFacturesData(true);
            $achats_data = $this->getCommandesFournData(true);

            $periodic_interval = new DateInterval('P' . $fac_periodicity . 'M');
            $on_day_interval = new DateInterval('P1D');

            $dt = new DateTime($periods_data['date_next_period_tobill']);
            $dt->sub($on_day_interval);
            $from = $dt->format('Y-m-d');

            $bought = 0;
            $billed = 0;

            // Qtés avant prochaine période facturée:
            foreach ($achats_data as $a_data) {
                if ($a_data['from'] <= $from) {
                    $bought += BimpTools::calcProrataQty(($a_data['qty']), $a_data['from'], $a_data['to'], $a_data['from'], $from);
                }
            }
            foreach ($fac_data as $f_data) {
                if ($f_data['from'] <= $from) {
                    $billed += BimpTools::calcProrataQty(($f_data['qty']), $f_data['from'], $f_data['to'], $f_data['from'], $from);
                }
            }

            $data[0] = array(
                'bought' => $bought,
                'billed' => $billed
            );

            // Qté à facturer pour chaque période : 
            for ($i = 1; $i <= $periods_data['nb_periods_tobill_max']; $i++) {
                $dt->add($on_day_interval);
                $from = $dt->format('Y-m-d');
                $dt->add($periodic_interval);
                $dt->sub($on_day_interval);
                $to = $dt->format('Y-m-d');

                $bought = 0;
                $billed = 0;

                foreach ($achats_data as $a_data) {
                    $bought += BimpTools::calcProrataQty(($a_data['qty']), $a_data['from'], $a_data['to'], $from, $to);
                }

                foreach ($fac_data as $f_data) {
                    $billed += BimpTools::calcProrataQty(($f_data['qty']), $f_data['from'], $f_data['to'], $from, $to);
                }

                $data[$i] = array(
                    'from'   => $from,
                    'to'     => $to,
                    'dates'  => 'Du ' . date('d / m / Y', strtotime($from)) . ' au ' . date('d / m / Y', strtotime($to)),
                    'bought' => $bought,
                    'billed' => $billed
                );
            }
        }

        return $data;
    }

    public function getQtiesToBuy($periods_data = null, &$errors = array())
    {
        $data = array();

        if (is_null($periods_data)) {
            $periods_data = $this->getPeriodsToBuyData($errors, true, true);
        }

        $achat_periodicity = (int) $this->getData('achat_periodicity');

        if (!$achat_periodicity) {
            $errors[] = 'Périodicité d\'achat non définie';
        }

        if (!count($errors)) {
            $fac_data = $this->getFacturesData(true);
            $achats_data = $this->getCommandesFournData(true);

            $periodic_interval = new DateInterval('P' . $achat_periodicity . 'M');
            $on_day_interval = new DateInterval('P1D');

            $dt = new DateTime($periods_data['date_next_achat']);
            $dt->sub($on_day_interval);
            $from = $dt->format('Y-m-d');

            $bought = 0;
            $billed = 0;

            // Qtés avant prochaine période achetée:
            foreach ($achats_data as $a_data) {
                if ($a_data['from'] <= $from) {
                    $bought += BimpTools::calcProrataQty(($a_data['qty']), $a_data['from'], $a_data['to'], $a_data['from'], $from);
                }
            }
            foreach ($fac_data as $f_data) {
                if ($f_data['from'] <= $from) {
                    $billed += BimpTools::calcProrataQty(($f_data['qty']), $f_data['from'], $f_data['to'], $f_data['from'], $from);
                }
            }

            $data[0] = array(
                'bought' => $bought,
                'billed' => $billed
            );

            // Qté à acheter pour chaque période : 
            for ($i = 1; $i <= $periods_data['nb_periods_tobuy_max']; $i++) {
                $dt->add($on_day_interval);
                $from = $dt->format('Y-m-d');
                $dt->add($periodic_interval);
                $dt->sub($on_day_interval);
                $to = $dt->format('Y-m-d');

                $bought = 0;
                $billed = 0;

                foreach ($achats_data as $a_data) {
                    $bought += BimpTools::calcProrataQty(($a_data['qty']), $a_data['from'], $a_data['to'], $from, $to);
                }

                foreach ($fac_data as $f_data) {
                    $billed += BimpTools::calcProrataQty(($f_data['qty']), $f_data['from'], $f_data['to'], $from, $to);
                }

                $data[$i] = array(
                    'from'   => $from,
                    'to'     => $to,
                    'dates'  => 'Du ' . date('d / m / Y', strtotime($from)) . ' au ' . date('d / m / Y', strtotime($to)),
                    'bought' => $bought,
                    'billed' => $billed
                );
            }
        }

        return $data;
    }

    // Getters statiques:

    public static function getPeriodicFacLinesToProcess($params = array(), &$errors = array())
    {
        $params = BimpTools::overrideArray(array(
                    'return'     => 'data',
                    'id_lines'   => array(),
                    'id_client'  => 0,
                    'id_product' => 0,
                    'id_contrat' => 0
                        ), $params);

        if (!in_array($params['return'], array('data', 'list', 'count'))) {
            $errors[] = 'Type de retour invalide';
            return array();
        }

        BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');
        $lines = array();

        $joins = array(
            'c' => array(
                'table' => 'contrat',
                'on'    => 'c.rowid = a.fk_contrat'
            )
        );

        if (empty($params['id_lines']) || $params['return'] == 'data') {
            $joins['cef'] = array(
                'table' => 'contrat_extrafields',
                'on'    => 'c.rowid = cef.fk_object'
            );
        }

        $filters = array();

        if (!empty($params['id_lines'])) {
            $filters['a.rowid'] = $params['id_lines'];
        } else {
            $filters = array(
                'a.statut'          => 4,
                'a.fac_periodicity' => array(
                    'operator' => '>',
                    'value'    => 0
                ),
                'a.fac_ended'       => 0,
                'date_next_facture' => array(
                    'operator' => '<=',
                    'value'    => date('Y-m-d')
                )
            );

            $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');
            if (!empty($id_lines)) {
                $filters['a.rowid'] = $id_lines;
            }

            if ($params['id_client']) {
                $filters['soc_custom'] = array(
                    'custom' => '(((c.fk_soc_facturation IS NULL OR c.fk_soc_facturation = 0) AND c.fk_soc = ' . $params['id_client'] . ') OR c.fk_soc_facturation = ' . $params['id_client'] . ')'
                );
            }

            if ($params['id_product']) {
                $filters['a.fk_product'] = $params['id_product'];
            }

            if ($params['id_contrat']) {
                $filters['a.fk_contrat'] = $params['id_contrat'];
            }
        }

        BimpTools::addSqlFilterEntity($filters, BimpObject::getInstance('bimpcontrat', 'BCT_Contrat'), 'c');

        $fields = array();

        switch ($params['return']) {
            case 'data':
                $fields = array(
                    'DISTINCT a.rowid as id_line',
                    'c.rowid as id_contrat',
                    'c.label as libelle_contrat',
                    'c.fk_soc as id_client',
                    'c.fk_soc_facturation as id_client_facture',
                    'cef.entrepot as id_entrepot',
                    'c.secteur',
                    'cef.expertise',
                    'cef.moderegl as id_mode_reglement',
                    'cef.condregl as id_cond_reglement'
                );
                break;

            default:
            case 'list':
                $fields = array('DISTINCT a.rowid as id_line');
                break;

            case 'count':
                $fields = array('COUNT(DISTINCT a.rowid) as nb_lines');
                break;
        }


        $sql = BimpTools::getSqlFullSelectQuery('contratdet', $fields, $filters, $joins, array(
                    'order_by'  => 'a.rowid',
                    'order_way' => 'asc'
        ));

        $bdb = BimpCache::getBdb();
        $rows = $bdb->executeS($sql, 'array');

        if (is_null($rows)) {
            $errors[] = 'Echec de la récupération des facturations périodiques à traiter - ' . $bdb->err();
        } else {
            switch ($params['return']) {
                case 'data':
                    foreach ($rows as $r) {
                        $id_client = (int) $r['id_client_facture'];
                        if (!$id_client) {
                            $id_client = (int) $r['id_client'];
                        }
                        if (!isset($lines[$id_client])) {
                            $lines[$id_client] = array();
                        }

                        $lines[$id_client][(int) $r['id_line']] = array(
                            'id_contrat'        => (int) $r['id_contrat'],
                            'libelle_contrat'   => $r['libelle_contrat'],
                            'id_entrepot'       => (int) $r['id_entrepot'],
                            'secteur'           => $r['secteur'],
                            'id_mode_reglement' => $r['id_mode_reglement'],
                            'id_cond_reglement' => $r['id_cond_reglement'],
                            'expertise'         => $r['expertise']
                        );
                    }
                    break;

                case 'list':
                default:
                    foreach ($rows as $r) {
                        $lines[] = (int) $r['id_line'];
                    }
                    break;

                case 'count':
                    if (isset($rows[0]['nb_lines'])) {
                        return (int) $rows[0]['nb_lines'];
                    }
                    return 0;
            }
        }

        return $lines;
    }

    public static function getPeriodicAchatLinesToProcess($params = array(), &$errors = array())
    {
        $params = BimpTools::overrideArray(array(
                    'return'     => 'data',
                    'id_lines'   => array(),
                    'id_client'  => 0,
                    'id_fourn'   => 0,
                    'id_product' => 0,
                    'id_contrat' => 0
                        ), $params);

        if (!in_array($params['return'], array('data', 'list', 'count'))) {
            $errors[] = 'Type de retour invalide';
            return array();
        }

        BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');
        $lines = array();

        $joins = array(
            'c' => array(
                'table' => 'contrat',
                'on'    => 'c.rowid = a.fk_contrat'
            )
        );

        if (empty($params['id_lines']) || $params['return'] == 'data') {
            $joins['cef'] = array(
                'table' => 'contrat_extrafields',
                'on'    => 'c.rowid = cef.fk_object'
            );
        }

        if (!empty($params['id_lines'])) {
            $filters['or_lines'] = array(
                'or' => array(
                    'a.rowid'          => $params['id_lines'],
                    'a.id_parent_line' => $params['id_lines']
                )
            );
        } else {
            $filters = array(
                'a.statut'            => 4,
                'a.achat_periodicity' => array(
                    'operator' => '>',
                    'value'    => 0
                ),
                'a.achat_ended'       => 0,
                'a.date_next_achat'   => array(
                    'operator' => '<=',
                    'value'    => date('Y-m-d')
                ),
            );

            if ($params['id_fourn']) {
                $joins['pfp'] = array(
                    'table' => 'product_fournisseur_price',
                    'on'    => 'pfp.rowid = a.fk_product_fournisseur_price'
                );
                $filters['pfp.fk_soc'] = $params['id_fourn'];
            }

            if ($params['id_client']) {
                $filters['soc_custom'] = array(
                    'custom' => '(((c.fk_soc_facturation IS NULL OR c.fk_soc_facturation = 0) AND c.fk_soc = ' . $params['id_client'] . ') OR c.fk_soc_facturation = ' . $params['id_client'] . ')'
                );
            }

            if ($params['id_product']) {
                $filters['a.fk_product'] = $params['id_product'];
            }

            if ($params['id_contrat']) {
                $filters['a.fk_contrat'] = $params['id_contrat'];
            }
        }

        BimpTools::addSqlFilterEntity($filters, BimpObject::getInstance('bimpcontrat', 'BCT_Contrat'), 'c');

        $fields = array();
        switch ($params['return']) {
            case 'data':
                $fields = array('DISTINCT a.rowid as id_line',
                    'c.fk_soc as id_client',
                    'c.fk_soc_facturation as id_client_facture',
                    'cef.entrepot as id_entrepot'
                );
                break;

            default:
            case 'list':
                $fields = array('DISTINCT a.rowid as id_line');
                break;

            case 'count':
                $fields = array('COUNT(DISTINCT a.rowid) as nb_lines');
                break;
        }


        $sql = BimpTools::getSqlFullSelectQuery('contratdet', $fields, $filters, $joins, array(
                    'order_by'  => 'rowid',
                    'order_way' => 'asc'
        ));

        $bdb = BimpCache::getBdb();

        $rows = $bdb->executeS($sql, 'array');

        if (is_null($rows)) {
            $errors[] = 'Echec de la récupération des achats périodiques à traiter - ' . $bdb->err();
        } else {
            switch ($params['return']) {
                case 'data':
                    $id_stocks_entrepot = (int) BimpCore::getConf('abos_id_entrepot', null, 'bimpcontrat');

                    foreach ($rows as $r) {
                        if (!isset($lines[(int) $r['id_line']])) {
                            $lines[(int) $r['id_line']] = array('id_entrepot' => ((int) $r['id_entrepot'] ? (int) $r['id_entrepot'] : $id_stocks_entrepot));
                        }
                    }
                    break;

                case 'list':
                default:
                    foreach ($rows as $r) {
                        if (!in_array((int) $r['id_line'], $lines)) {
                            $lines[] = (int) $r['id_line'];
                        }
                    }
                    break;

                case 'count':
                    if (isset($rows[0]['nb_lines'])) {
                        return (int) $rows[0]['nb_lines'];
                    }
                    return 0;
            }
        }
        return $lines;
    }

    // Getters array:

    public function getNbRenouvellementsArray($max = 10)
    {
        $n = array(
            0  => 'Aucun',
            -1 => 'Illimité',
        );

        for ($i = 1; $i <= $max; $i++) {
            $n[$i] = $i;
        }
        return $n;
    }

    public function getCommandesFournisseursArray($id_fourn = 0, $id_entrepot = 0)
    {
        $commandes = array(
            'new' => 'Nouvelle commande'
        );

        if (!(int) $id_entrepot) {
            if (BimpTools::isPostFieldSubmit('id_entrepot')) {
                $id_entrepot = (int) BimpTools::getPostFieldValue('id_entrepot', 0, 'int');
            } elseif ($this->isLoaded()) {
                $contrat = $this->getParentInstance();

                if (BimpObject::objectLoaded($contrat)) {
                    $id_entrepot = (int) $contrat->getData('entrepot');
                }
            }
        }

        if ($id_fourn && $id_entrepot) {
            $sql = 'SELECT cf.rowid as id, cf.ref, cf.date_creation as date, s.nom FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur cf';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields cfe ON cf.rowid = cfe.fk_object';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON s.rowid = cf.fk_soc';
            $sql .= ' WHERE cf.fk_soc = ' . (int) $id_fourn . ' AND cf.fk_statut = 0 AND cfe.entrepot = ' . (int) $id_entrepot;
            $sql .= ' ORDER BY cf.rowid DESC';

            $rows = $this->db->executeS($sql);
            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $obj) {
                    $DT = new DateTime($obj->date);
                    $commandes[(int) $obj->id] = $obj->nom . ' ' . $obj->ref . ' - Créée le ' . $DT->format('d / m / Y à H:i');
                }
            }
        }
        return $commandes;
    }

    public function getLinkableContratLinesArray()
    {
        $lines = array(
            0 => 'Aucun'
        );

        $id_prod = (int) BimpTools::getPostFieldValue('fk_product', $this->getData('fk_product'), 'int');
        $other_product = (int) BimpTools::getPostFieldValue('type_linked_line', 0, 'int');
        if ($id_prod) {
            $contrat = $this->getParentInstance();
            if (BimpObject::objectLoaded($contrat)) {
                $filters = array(
                    'id_parent_line' => 0,
                    'id_linked_line' => 0
                );

                if ($this->isLoaded()) {
                    $filters['rowid'] = array(
                        'operator' => '!=',
                        'value'    => $this->id
                    );
                }

                if ($other_product) {
                    $filters['fk_product'] = array(
                        'operator' => '!=',
                        'value'    => $id_prod
                    );
                } else {
                    $filters['fk_product'] = $id_prod;
                }

                $contrat_lines = $contrat->getLines('abo', false, $filters);

                foreach ($contrat_lines as $line) {
                    if (!$line->isActive() || $line->isResiliated()) {
                        continue;
                    }

                    $lines[$line->id] = $line->displayProduct('ref_nom') . ' (' . $line->displayPeriods(true) . ')';
                }

                return $lines;
            }
        }

        return $lines;
    }

    public function getClientPropalesArray()
    {
        $propales = array(
            -1 => 'NON',
            0  => 'Nouveau devis'
        );

        $contrat = $this->getParentInstance();

        if (BimpObject::objectLoaded($contrat)) {
            $id_client = (int) $contrat->getData('fk_soc');

            if ($id_client) {
                foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Propal', array(
                    'fk_soc'    => $id_client,
                    'fk_statut' => 0
                        ), 'rowid', 'desc') as $propal) {
                    $propales[(int) $propal->id] = $propal->getRef() . ' - ' . $propal->getName();
                }
            }
        }

        return $propales;
    }

    // Affichage:

    public function displayDesc()
    {
        $html = '';

        $product = $this->getChildObject('product');
        if (BimpObject::objectLoaded($product)) {
            $html .= $product->getLink();
            $html .= '<br/>' . '<b>' . str_replace("  ", " ", BimpTools::cleanString($product->getData('label'))) . '</b>';
        }

        $label = str_replace("  ", " ", $this->getData('label'));
        if ($label) {
            $html .= ($html ? '<br/>' : '') . $label;
        }

        $html .= '<br/>';
        if ((int) $this->getData('statut') > 0) {
            $html .= 'Du ';
            $date_ouv = $this->getData('date_ouverture');
            $date_fin = $this->getData('date_fin_validite');

            if ($date_ouv) {
                $html .= '<b>' . date('d / m / Y', strtotime($date_ouv)) . '</b>';
            } else {
                $html .= '<span class="danger">(non défini)</span>';
            }

            $html .= ' au ';

            if ($date_fin) {
                $html .= '<b>' . date('d / m / Y', strtotime($date_fin)) . '</b>';
            } else {
                $html .= '<span class="danger">(non défini)</span>';
            }
        } else {
            $date = $this->getData('date_ouverture_prevue');
            if ($date) {
                $html .= '<span class="info">Ouverture prévue : ' . date('d / m / Y', strtotime($date)) . '</span>';
            } else {
                $html .= '<span class="danger">Date d\'ouverture non définie</span>';
            }
        }

        $desc = $this->getData('description');
        if ($desc) {
            $html .= ($html ? '<br/><br/>' : '') . $desc;
        }

        return $html;
    }

    public function displayQties()
    {
        $html = '';

        switch ($this->getData('line_type')) {
            case self::TYPE_TEXT:
                return $this->getData('qty');

            case self::TYPE_ABO:
                $date_cloture = $this->getData('date_cloture');

                if ($date_cloture) {
                    $html .= '<span style="display: inline-block" class="danger">' . BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Résiliation le ' . date('d / m / Y', strtotime($date_cloture)) . '</span><br/>';
                }

                $id_line_renouv_origin = (int) $this->db->getValue('contratdet', 'rowid', 'id_line_renouv = ' . $this->id);
                if ($id_line_renouv_origin) {
                    $line_renouv_origin = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line_renouv_origin);
                    if (BimpObject::objectLoaded($line_renouv_origin)) {
                        $html .= '<span style="display: inline-block" class="important">' . BimpRender::renderIcon('fas_sync', 'iconLeft') . 'Renouvellement de la ligne n°' . $line_renouv_origin->getData('rang') . '</span><br/>';
                    }
                }

                if ((int) $this->getData('id_line_renouv')) {
                    $line_revouv = $this->getChildObject('line_renouv');
                    if (BimpObject::objectLoaded($line_revouv)) {
                        $html .= '<span style="display: inline-block" class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Renouvellé (Ligne n°' . $line_revouv->getData('rang') . ')</span><br/>';
                    }
                }

                if ((int) $this->getData('id_linked_line')) {
                    $linked_line = $this->getChildObject('linked_line');
                    if (BimpObject::objectLoaded($linked_line)) {
                        $html .= '<span style="display: inline-block" class="info">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Abonnement lié à la ligne n° ' . $linked_line->getData('rang') . '</span><br/>';
                    } else {
                        $html .= '<span style="display: inline-block" class="danger">l\'abonnement lié (ligne #' . $this->getData('id_linked_line') . ') n\'existe plus</span><br/>';
                    }
                }

                if ((int) $this->getData('variable_pu_ht')) {
                    $html .= '<span style="display: inline-block" class="important">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Abonnement à prix de vente variable</span><br/>';
                }

                $is_variable = (int) $this->getData('variable_qty');
                if ($is_variable) {
                    $html .= '<span style="display: inline-block" class="important">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Abonnement à qtés variables</span><br/>';
                }

                $nb_units = $this->getNbUnits();

                $html .= ($html ? '<br/>' : '');

                $html .= 'Nb unité(s) : <b>' . $nb_units . '</b><br/>';
                $html .= 'Qté totale ' . ($is_variable ? 'estimée ' : '') . ': <b>' . BimpTools::displayFloatValue($this->getData('qty'), 8, ',', 0, 0, 0, 0, 1, 1) . '</b><br/>';
                $html .= 'Durée abonnement : <b>' . $this->getData('duration') . ' mois</b><br/>';
                $html .= 'Qté par période facturée ' . ($is_variable ? '(estimée) ' : '') . ': <b>' . $this->getFacQtyPerPeriod() . '</b><br/>';
                $html .= 'Qté par période d\'achat ' . ($is_variable ? '(estimée) ' : '') . ': <b>' . $this->getAchatQtyPerPeriod() . '</b>';
        }

        return $html;
    }

    public function displayPeriodicity($type)
    {
        $html = '';

        if ($this->field_exists($type . '_periodicity')) {
            $val = (int) $this->getData($type . '_periodicity');

            if (!$val) {
                $html .= '<span class="danger">Aucune</span>';
            } elseif (isset(self::$periodicities[$val])) {
                $html .= self::$periodicities[$val];
            } else {
                $html .= 'Tous les ' . $val . ' mois';
            }
        }

        return $html;
    }

    public function displayProduct($display = 'ref_nom', $single_line = true, $include_duration = false)
    {
        $html = '';
        $product = $this->getChildObject('product');

        if (BimpObject::objectLoaded($product)) {
            switch ($display) {
                case 'ref':
                    $html .= $product->getRef();
                    break;

                case 'nom':
                    $html .= $product->getName();
                    break;

                case 'ref_nom':
                    $html .= $product->getRef() . ($single_line ? ' - ' : '<br/>' ) . $product->getName();
                    break;

                case 'nom_url':
                    $html .= $product->getLink();
                    break;

                case 'link_nom':
                    $html .= $product->getLink() . ($single_line ? ' - ' : '<br/>' ) . $product->getName();
            }

            if ($include_duration) {
                $duree = $product->getData('duree');
                $html .= ($single_line ? ' ' : '<br/>');
                $html .= '<span style="color: #999999">' . BimpRender::renderIcon('fas_calendar-check', 'iconLeft') . ($duree ? $duree : 'Non définie') . ' mois</span>';
            }
        }

        return $html;
    }

    public function displayPeriods($no_html = false)
    {
        $html = '';

        $date_ouv = $this->getData('date_ouverture');
        $date_fin = $this->getData('date_fin_validite');

        if ($date_ouv) {
            if ($date_fin) {
                $html .= 'Du <b>' . date('d / m / Y', strtotime($date_ouv)) . '</b>';
                $html .= ' au <b>' . date('d / m / Y', strtotime($date_fin)) . '</b>';
            } else {
                $html .= 'A partir du <b>' . date('d / m / Y', strtotime($date_ouv)) . '</b>';
            }
        } elseif ($date_fin) {
            $html .= 'Jusqu\'au <b>' . date('d / m / Y', strtotime($date_fin)) . '</b>';
        }

        $date_cloture = $this->getData('date_cloture');
        if ($date_cloture) {
            $html .= ($no_html ? ' - ' : '<br/>') . '<span class="danger">Résiliation le ' . date('d / m / Y', strtotime($date_cloture)) . '</span>';
        }
        return $html;
    }

    public function displayAboTotalQty()
    {
        $html = '';

        $fac_periodicity = (int) BimpTools::getPostFieldValue('fac_periodicity', 0, 'int');
        $duration = (int) BimpTools::getPostFieldValue('duration', 0, 'int');
        $qty_per_period = (float) BimpTools::getPostFieldValue('qty_per_period', 0, 'float');

        if ($fac_periodicity && $duration) {
            if ($duration % $fac_periodicity != 0) {
                $html .= '<span class="danger">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'La durée totale doit être un multple du nombre de mois correspondant à la périodicité de facturation (' . $fac_periodicity . ' mois)</span>';
            } else {
                $nb_periods = $duration / $fac_periodicity;
                $html .= '<b>Nombre total de facturations : </b>' . $nb_periods;

                if ($qty_per_period) {
                    $html .= '<br/><b>Qté totale : </b>' . BimpTools::displayFloatValue($qty_per_period * $nb_periods, 8, ',', 0, 0, 0, 0, 1, 1);
                }
            }
        }

        return $html;
    }

    public function displayNbPeriodsBilled($periods_data = null)
    {
        $html = '';

        $errors = array();

        if (is_null($periods_data)) {
            $periods_data = $this->getPeriodsToBillData($errors);
        }

        if (!count($errors)) {
            if ($periods_data['first_period_prorata'] != 1) {
                $html .= '<span class="small">Prorata 1ère période : <b>' . BimpTools::displayFloatValue((float) $periods_data['first_period_prorata'], 2, ',', 0, 0, 0, 0, 1, 1) . '</b></span><br/>';
            }
            $nb_total_periods_fac = $periods_data['nb_total_periods'] - $periods_data['nb_periods_never_billed'] - $periods_data['nb_periods_before_start'];
            $class = ($periods_data['nb_periods_billed'] >= $nb_total_periods_fac ? 'success' : ($periods_data['nb_periods_billed'] > 0 ? 'warning' : 'danger'));

            $html .= 'Nb périodes facturées: <span class="' . $class . '">' . $periods_data['nb_periods_billed'] . ' sur ' . $nb_total_periods_fac . '</span>';

            if ($periods_data['nb_periods_billed'] < $nb_total_periods_fac) {
                $html .= '<br/>Prochaine facturation : ' . $this->displayNextFacDate(true);
            }

            if ($periods_data['nb_periods_never_billed'] > 0) {
                $date_cloture = $this->getData('date_cloture');

                $msg = '<span style="font-size: 11px; font-style: italic">Abonnement résilié au <b>' . date('d / m / Y', strtotime($date_cloture)) . '</b></span>';
                $msg .= '<br/><span style="font-size: 11px; font-style: italic" class="danger">' . $periods_data['nb_periods_never_billed'] . ' période' . ($periods_data['nb_periods_never_billed'] > 1 ? 's ne seront pas facturées' : ' ne sera pas facturée') . '</span>';
                $html .= BimpRender::renderAlerts($msg, 'warning');
            }
        } else {
            $html .= BimpRender::renderAlerts($errors);
        }

        if (BimpCore::isUserDev()) {
            $html .= BimpRender::renderFoldableContainer('Infos dev', '<pre>' . print_r($periods_data, 1) . '</pre>', array('open' => false));
        }

        return $html;
    }

    public function displayNbPeriodsBought($periods_data = null)
    {
        $html = '';

        $errors = array();
        if (is_null($periods_data)) {
            $periods_data = $this->getPeriodsToBuyData($errors);
        }

        if (!count($errors)) {
            $nb_total_periods_achat = $periods_data['nb_total_periods'] - $periods_data['nb_periods_never_bought'] - $periods_data['nb_periods_before_start'];
            $nb_periods_bought = $periods_data['nb_periods_bought'];
            $class = ($nb_periods_bought >= $nb_total_periods_achat ? 'success' : ($nb_periods_bought > 0 ? 'warning' : 'danger'));

            $html .= 'Nb périodes achetées: <span class="' . $class . '">' . $nb_periods_bought . ' sur ' . $nb_total_periods_achat . '</span>';

            if ($nb_periods_bought < $nb_total_periods_achat) {
                $html .= '<br/>Prochaine achat : ' . $this->displayNextAchatDate(true);
            }

            if ($periods_data['nb_periods_never_bought'] > 0 || $periods_data['nb_periods_bought_never_fac'] > 0) {
                $date_cloture = $this->getData('date_cloture');

                $msg = '<span style="font-size: 11px; font-style: italic">Abonnement résilié au <b>' . date('d / m / Y', strtotime($date_cloture)) . '</b></span>';

                if ($periods_data['nb_periods_bought_never_fac'] > 0) {
                    $msg .= '<br/><span style="font-size: 11px; font-style: italic" class="warning">' . $periods_data['nb_periods_bought_never_fac'] . ' période' . ($periods_data['nb_periods_bought_never_fac'] > 1 ? 's achetées ne seront pas facturées' : ' achetée ne sera facturée') . '</span>';
                }

                if ($periods_data['nb_periods_never_bought'] > 0) {
                    $msg .= '<br/><span style="font-size: 11px; font-style: italic" class="danger">' . $periods_data['nb_periods_never_bought'] . ' période' . ($periods_data['nb_periods_never_bought'] > 1 ? 's ne seront pas achetées' : ' ne sera pas achetée') . '</span>';
                }
                $html .= BimpRender::renderAlerts($msg, 'warning');
            }

            if (BimpCore::isUserDev()) {
                $html .= BimpRender::renderFoldableContainer('Infos dev', '<pre>' . print_r($periods_data, 1) . '</pre>', array('open' => false));
            }
        } else {
            $html .= BimpRender::renderAlerts($errors);
        }


        return $html;
    }

    public function displayFacInfos()
    {
        $html = '';

        $fac_periodicity = (int) $this->getData('fac_periodicity');
        if ($fac_periodicity) {
            $html .= '<b>Facturation ';
            if (isset(self::$periodicities[$fac_periodicity])) {
                $html .= self::$periodicities[$fac_periodicity] . ' ';
            } else {
                $html .= 'tous les ' . $fac_periodicity . ' mois ';
            }

            $date_start = $this->getData('date_fac_start');
            $date_fin = $this->getData('date_fin_validite');

            $html .= '</b>';

            if ($date_start && (!$date_fin || $date_start < $date_fin)) {
                $html .= '<br/>A partir du <b>' . date('d / m / Y', strtotime($date_start)) . '</b>';
            }


            if ((int) $this->getData('statut') > 0) {
                $html .= '<br/><br/>';
                $html .= $this->displayNbPeriodsBilled();

                if ((int) $this->getData('variable_qty')) {
                    $qties = $this->getFacturesData(true, 'qties');

                    if ($qties['fac_qty']) {
                        $html .= '<div style="padding: 8px; margin-top: 10px; border: 1px solid #DCDCDC">';
                        $html .= '<b>Qtés facturées: </b><br/>';
                        $html .= 'Facturations régulières : <b>' . BimpTools::displayFloatValue($qties['fac_qty'], 6, ',', 0, 0, 0, 0, 1, 1) . '</b>';

                        if ($qties['regul_qty']) {
                            $html .= '<br/>Régularisations : <b>' . BimpTools::displayFloatValue($qties['regul_qty'], 6, ',', 0, 0, 0, 0, 1, 1) . '</b>';
                            $html .= '<br/>Total : <b>' . BimpTools::displayFloatValue($qties['fac_qty'] + $qties['regul_qty'], 6, ',', 0, 0, 0, 0, 1, 1) . '</b>';
                        }
                        $html .= '</div>';
                    }
                }
            }
        } else {
            $html .= '<span class="warning">Pas de facturation périodique</span>';
        }

        return $html;
    }

    public function displayAchatInfos($with_pa_infos = true)
    {
        $html = '';

        $achat_periodicity = (int) $this->getData('achat_periodicity');

        if ($achat_periodicity) {
            $periods_data = array();

            if ((int) $this->getData('statut') > 0) {
                $periods_data = $this->getPeriodsToBuyData();
            }

            $html .= '<b>Achats ';
            if (isset(self::$periodicities_masc[$achat_periodicity])) {
                $html .= self::$periodicities_masc[$achat_periodicity] . 's';
            } else {
                $html .= ' tous les ' . $achat_periodicity . ' mois';
            }
            $date_start = $this->getData('date_achat_start');
            $date_fin = $this->getData('date_fin_validite');

            $html .= '</b>';

            if ($date_start && (!$date_fin || $date_start < $date_fin)) {
                $html .= '<br/>A partir du : <b>' . date('d / m / Y', strtotime($date_start)) . '</b>';

                if ((int) $this->getData('statut') > 0) {
                    if ($periods_data['first_period_prorata'] != 1) {
                        $html .= '<br/><span class="small">Prorata 1ère période : <b>' . BimpTools::displayFloatValue((float) $periods_data['first_period_prorata'], 2, ',', 0, 0, 0, 0, 1, 1) . '</b></span>';
                    }
                }
            }

            $html .= '<br/><br/>';

            if ($with_pa_infos) {
                if ((int) $this->getData('fk_product_fournisseur_price')) {

                    $pfp = $this->getChildObject('fourn_price');
                    if (BimpObject::objectLoaded($pfp)) {
                        $html .= '<b>Prix d\'achat HT actuel: </b>' . BimpTools::displayMoneyValue($pfp->getData('price'));
                        $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $pfp->getData('fk_soc'));

                        if (BimpObject::objectLoaded($fourn)) {
                            $html .= '<br/><b>Fournisseur : </b>' . $fourn->getLink();
                        }
                    } else {
                        $html .= '<span class="danger">Le prix d\'achat fournisseur #' . $this->getData('fk_product_fournisseur_price') . ' n\'existe plus</span>';
                    }
                } else {
                    $html .= '<span class="danger">Aucun prix d\'achat fournisseur spécifié</span>';
                }

                $html .= '<br/><br/>';
            }

            if ((int) $this->getData('statut') > 0) {
                $html .= $this->displayNbPeriodsBought($periods_data);

                if ((int) $this->getData('variable_qty')) {
                    $qties = $this->getCommandesFournData(true, 'qties');

                    if ($qties['achat_qty']) {
                        $html .= '<div style="padding: 8px; margin-top: 10px; border: 1px solid #DCDCDC">';
                        $html .= '<b>Qtés achetées: </b><br/>';
                        $html .= 'Achats réguliers : <b>' . BimpTools::displayFloatValue($qties['achat_qty'], 6, ',', 0, 0, 0, 0, 1, 1) . '</b>';

                        if ($qties['regul_qty']) {
                            $html .= '<br/>Régularisations : <b>' . BimpTools::displayFloatValue($qties['regul_qty'], 6, ',', 0, 0, 0, 0, 1, 1) . '</b>';
                            $html .= '<br/>Total : <b>' . BimpTools::displayFloatValue($qties['achat_qty'] + $qties['regul_qty'], 6, ',', 0, 0, 0, 0, 1, 1) . '</b>';
                        }
                        $html .= '</div>';
                    }
                }
            }
        } else {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Pas d\'achats périodiques</span>';
        }

        return $html;
    }

    public function displayClientNameInput()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0, 'int');
        if ($id_client) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
            if (BimpObject::objectLoaded($client)) {
                return $client->getLink();
            }
        }

        return '';
    }

    public function displayFournNameInput()
    {
        $id_fourn = (int) BimpTools::getPostFieldValue('id_fourn', 0, 'int');
        if ($id_fourn) {
            $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
            if (BimpObject::objectLoaded($fourn)) {
                return $fourn->getLink();
            }
        }

        return '';
    }

    public function displayProductNameInput()
    {
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0, 'int');
        if ($id_product) {
            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
            if (BimpObject::objectLoaded($prod)) {
                return $prod->getLink();
            }
        }

        return '';
    }

    public function displayContratNameInput()
    {
        $id_contrat = (int) BimpTools::getPostFieldValue('id_contrat', 0, 'int');
        if ($id_contrat) {

            $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat);
            if (BimpObject::objectLoaded($contrat)) {
                return $contrat->getLink();
            }
        }

        return '';
    }

    public function displayNbFacsToProcess()
    {
        if ($this->isLoaded() && (int) $this->getData('fac_periodicity')) {
            $errors = array();
            $data = $this->getPeriodsToBillData($errors, false);

            if (isset($data['nb_periods_tobill_today'])) {
                return '<div style="text-align: center"><span class="badge badge-' . ($data['nb_periods_tobill_today'] > 0 ? ($data['nb_periods_tobill_today'] > 1 ? 'important' : 'warning') : 'success') . '">' . $data['nb_periods_tobill_today'] . '</span></div>';
            }
        }

        return '';
    }

    public function displayNbAchatsToProcess()
    {
        if ($this->isLoaded() && (int) $this->getData('achat_periodicity')) {
            $errors = array();
            $data = $this->getPeriodsToBuyData($errors, false);

//            return '<pre>' . print_r($data, 1) . '</pre>';
            if (isset($data['nb_periods_tobuy_today'])) {
                return '<div style="text-align: center"><span class="badge badge-' . ($data['nb_periods_tobuy_today'] > 0 ? ($data['nb_periods_tobuy_today'] > 1 ? 'important' : 'warning') : 'success') . '">' . $data['nb_periods_tobuy_today'] . '</span></div>';
            }
        }

        return '';
    }

    public function displayNextFacDate($with_color = false)
    {
        $html = '';

        $date = $this->getData('date_next_facture');
        if (!$date) {
            if ($with_color) {
                $html .= '<span class="warning">Non définie</span>';
            } else {
                $html .= 'Non définie';
            }
        } elseif ($date == '9999-12-31' || $date == '0000-00-00') {
            if ($with_color) {
                $html .= '<span class="danger">Aucune</span>';
            } else {
                $html .= 'Aucune';
            }
        } else {
            if ($with_color) {
                if ($date <= date('Y-m-d')) {
                    $html .= '<span class="success">' . $this->displayData('date_next_facture') . '</span>';
                } else {
                    $html .= '<span class="danger">' . $this->displayData('date_next_facture') . '</span>';
                }
            }
        }

        return $html;
    }

    public function displayNextAchatDate($with_color = false)
    {
        $html = '';

        $date = $this->getData('date_next_achat');
        if (!$date) {
            if ($with_color) {
                $html .= '<span class="warning">Non définie</span>';
            } else {
                $html .= 'Non définie';
            }
        } elseif ($date == '9999-12-31' || $date == '0000-00-00') {
            if ($with_color) {
                $html .= '<span class="danger">Aucune</span>';
            } else {
                $html .= 'Aucune';
            }
        } else {
            if ($with_color) {
                if ($date <= date('Y-m-d')) {
                    $html .= '<span class="success">' . $this->displayData('date_next_achat') . '</span>';
                } else {
                    $html .= '<span class="danger">' . $this->displayData('date_next_achat') . '</span>';
                }
            }
        }

        return $html;
    }

    public function displayLineOrigin()
    {
        $html = '';
        $id_line_origin = (int) $this->getData('id_line_origin');
        if ($id_line_origin) {
            switch ($this->getData('line_origin_type')) {
                case 'propal_line':
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_PropalLine', $id_line_origin);
                    if (BimpObject::objectLoaded($line)) {
                        $propal = $line->getParentInstance();
                        $html .= 'Ligne n°' . $line->getData('position') . ' - Devis : ' . $propal->getLink();
                    }
                    break;

                case 'commande_line':
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line_origin);
                    if (BimpObject::objectLoaded($line)) {
                        $commande = $line->getParentInstance();
                        $html .= 'Ligne n°' . $line->getData('position') . ' - Commande : ' . $commande->getLink();
                    }
                    break;
            }
        }

        return $html;
    }

    // Rendus HTML:

    public function renderOriginLink()
    {
        $html = '';
        if ($this->isLoaded()) {
            $contrat = $this->getParentInstance();

            if (BimpObject::objectLoaded($contrat)) {
                $html .= '<span class="bold">Contrat d\'abonnement d\'origine: </span><br/>';
                $html .= $contrat->getLink() . ' - Ligne n°' . $this->getData('rang');
            }
        }

        return $html;
    }

    public function renderFournPriceInput()
    {
        if ((int) $this->getData('line_type') == self::TYPE_ABO) {
            $fourn_prices = array(0 => '');

            if ((int) $this->getData('fk_product')) {
                BimpObject::loadClass('bimpcore', 'Bimp_Product');
                $fourn_prices = Bimp_Product::getFournisseursPriceArray((int) $this->getData('fk_product'));
            }

            return BimpInput::renderInput('select', 'fk_product_fournisseur_price', (int) $this->getInputValue('fk_product_fournisseur_price'), array(
                        'options' => $fourn_prices
            ));
        }

        return BimpInput::renderInput('search_object', 'fk_product_fournisseur_price', (int) $this->getInputValue('fk_product_fournisseur_price'), array(
                    'object' => $this->getChildObject('fourn_price')
        ));
    }

    public function renderPeriodicProcessInputs()
    {
        $html = '';
        $errors = array();

        $operation_type = BimpTools::getPostFieldValue('operation_type', '', 'aZ09');

        if (!$operation_type) {
            $errors[] = 'Type d\'opération périodique non spécifiée';
        } else {
            $method = 'renderPeriodic' . ucfirst($operation_type) . 'ProcessInputs';
            if (!method_exists($this, $method)) {
                $errors[] = 'Type d\'opération invalide - ' . $operation_type;
            } else {
                $html = $this->{$method}($errors);
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }


        return $html;
    }

    public function renderPeriodicFacProcessInputs(&$errors = array())
    {
        $html = '';

        BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');
        $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0, 'int');
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0, 'int');
        $id_contrat = (int) BimpTools::getPostFieldValue('id_contrat', 0, 'int');

        $errors = array();
        $debug = (BimpCore::isModeDev() || BimpCore::isUserDev());

        $lines_by_clients = self::getPeriodicFacLinesToProcess(array(
                    'return'     => 'data',
                    'id_lines'   => $id_lines,
                    'id_client'  => $id_client,
                    'id_product' => $id_product,
                    'id_contrat' => $id_contrat
                        ), $errors);

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        } elseif (empty($lines_by_clients)) {
            $msg = 'Aucune facturation périodique à effectuer à date';

            if ($id_client) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                if (BimpObject::objectLoaded($client)) {
                    $msg .= ' pour le client ' . $client->getRef() . ' - ' . $client->getName();
                }
            } elseif ($id_product) {
                $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
                if (BimpObject::objectLoaded($product)) {
                    $msg .= ' pour le produit ' . $product->getRef() . ' - ' . $product->getName();
                }
            } elseif ($id_contrat) {
                $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat);
                if (BimpObject::objectLoaded($contrat)) {
                    $msg .= ' pour le ' . $contrat->getLabel() . ' ' . $contrat->getRef();
                }
            }

            $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . '<span class = "success">' . $msg . '</span>';
        } else {
            // Répartition par entrepôt / secteur / mode et conditions de réglement : 

            $clients_factures = array();
            $all_lines = array();

            foreach ($lines_by_clients as $id_client => $lines) {
                $clients_factures[$id_client] = array();
                $contrats_libelles = array();

                foreach ($lines as $id_line => $line_data) {
                    $id_contrat = (int) BimpTools::getArrayValueFromPath($line_data, 'id_contrat', 0);
                    if ($id_contrat && !isset($contrats_libelles[$id_contrat])) {
                        $libelle_contrat = BimpTools::getArrayValueFromPath($line_data, 'libelle_contrat', '');
                        if ($libelle_contrat) {
                            $contrats_libelles[$id_contrat] = $libelle_contrat;
                        }
                    }
                    $check = false;

                    foreach ($clients_factures[$id_client] as $idx => $cf_data) {
                        if ($cf_data['id_entrepot'] == $line_data['id_entrepot'] && $cf_data['secteur'] == $line_data['secteur'] && $cf_data['expertise'] == $line_data['expertise'] &&
                                $cf_data['id_mode_reglement'] == $line_data['id_mode_reglement'] && $cf_data['id_cond_reglement'] == $line_data['id_cond_reglement']) {
                            $clients_factures[$id_client][$idx]['lines'][] = $id_line;
                            $check = true;
                            break;
                        }
                    }

                    if (!$check) {
                        $clients_factures[$id_client][] = array(
                            'contrats_libelles' => $contrats_libelles,
                            'id_entrepot'       => $line_data['id_entrepot'],
                            'secteur'           => $line_data['secteur'],
                            'expertise'         => $line_data['expertise'],
                            'id_mode_reglement' => $line_data['id_mode_reglement'],
                            'id_cond_reglement' => $line_data['id_cond_reglement'],
                            'lines'             => array($id_line)
                        );
                    }

                    $all_lines[] = $id_line;
                }
            }

            $html .= '<div class="buttonsContainer align-right" style="margin:  0 0 5px 0; padding: 0">';
            $html .= '<span class="btn btn-default check_all_lines">';
            $html .= BimpRender::renderIcon('far_check-square', 'iconLeft') . 'Tout cocher';
            $html .= '</span>';
            $html .= '<span class="btn btn-default uncheck_all_lines">';
            $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Tout décocher';
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<table class = "bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="min-width: 30px; max-width: 30px; text-align: center"></th>';
            $html .= '<th>Contrat</th>';
            $html .= '<th>Produit / service</th>';
            $html .= '<th>Quantités</th>';
            $html .= '<th>PU HT</th>';
            $html .= '<th>Date prochaine facturation</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            $secteurs = BimpCache::getSecteursArray(false);
            $expertises = BimpDolObject::$expertise;
            $modes_reglement = BimpCache::getModeReglements();
            $conds_reglement = BimpCache::getCondReglementsArray(false);
            $primary_color = BimpCore::getParam('colors/primary');

            foreach ($clients_factures as $id_client => $client_factures) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                if (!BimpObject::objectLoaded($client)) {
                    $html .= '<tr>';
                    $html .= '<td colspan="99">' . BimpRender::renderAlerts('Le client #' . $id_client . ' n\'existe plus') . '</td>';
                    $html .= '</tr>';
                    continue;
                }

                $html .= '<tr class="client_row" data-id_client="' . $id_client . '">';
                $html .= '<td colspan="99" style="font-weight: bold; font-size: 14px; padding: 10px; background-color: #DCDCDC">';
                $html .= 'Client : ' . $client->getLink();
                $html .= '</td>';
                $html .= '</tr>';

                foreach ($client_factures as $fac_idx => $facture_data) {
                    $entrepot = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Entrepot', $facture_data['id_entrepot']);
                    $html .= '<tr class="client_fac_row" data-id_client="' . $id_client . '" data-fac_idx="' . $fac_idx . '"';
                    $html .= ' data-id_entrepot="' . $facture_data['id_entrepot'] . '"';
                    $html .= ' data-secteur="' . $facture_data['secteur'] . '"';
                    $html .= ' data-id_mode_reglement="' . $facture_data['id_mode_reglement'] . '"';
                    $html .= ' data-id_cond_reglement="' . $facture_data['id_cond_reglement'] . '"';
                    $html .= ' data-expertise="' . $facture_data['expertise'] . '"';
                    $html .= '>';
                    $html .= '<td colspan="99" style="font-size: 12px; padding: 10px; background-color: #DCDCDC">';
                    $html .= '<div style="display: inline-block">';
                    $html .= 'Entrepôt : ' . $entrepot->getLink() . '<br/>';
                    $html .= 'Secteur : <b>' . (isset($secteurs[$facture_data['secteur']]) ? $secteurs[$facture_data['secteur']] : '<span class="danger">' . ($facture_data['secteur'] ? 'inconnu (' . $facture_data['secteur'] . ')' : 'non spécifié') . '</span>') . '</b><br/>';
                    $html .= 'Expertise : <b>' . (isset($expertises[$facture_data['expertise']]) ? $expertises[$facture_data['expertise']] : '<span class="danger">' . ($facture_data['expertise'] ? 'inconnu (' . $facture_data['expertise'] . ')' : 'non spécifié') . '</span>') . '</b><br/>';
                    $html .= 'Mode de réglement : <b>' . (isset($modes_reglement[$facture_data['id_mode_reglement']]) ? $modes_reglement[$facture_data['id_mode_reglement']] : '<span class="danger">' . ($facture_data['id_mode_reglement'] ? 'inconnu (' . $facture_data['id_mode_reglement'] . ')' : 'non spécifié') . '</span>') . '</b><br/>';
                    $html .= 'Conditions de réglement : <b>' . (isset($conds_reglement[$facture_data['id_cond_reglement']]) ? $conds_reglement[$facture_data['id_cond_reglement']] : '<span class="danger">' . ($facture_data['id_cond_reglement'] ? 'inconnu (' . $facture_data['id_cond_reglement'] . ')' : 'non spécifié') . '</span>') . '</b><br/>';
                    $html .= '</div>';

                    $factures = array('0' => 'Nouvelle facture');

                    foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array(
                        'a.fk_soc'            => $id_client,
                        'a.fk_statut'         => 0,
                        'a.type'              => 0,
                        'a.fk_mode_reglement' => (int) $facture_data['id_mode_reglement'],
                        'a.fk_cond_reglement' => (int) $facture_data['id_cond_reglement'],
                        'fef.expertise'       => $facture_data['expertise'],
                        'fef.entrepot'        => $facture_data['id_entrepot'],
                        'fef.type'            => $facture_data['secteur']
                            ), 'rowid', 'asc', array(
                        'fef' => array(
                            'table' => 'facture_extrafields',
                            'on'    => 'fef.fk_object = a.rowid'
                        )
                    )) as $fac) {
                        $factures[$fac->id] = $fac->getRef() . ' (créée le ' . date('d / m / Y', strtotime($fac->getData('datec'))) . ')';
                    }

                    $html .= '<div style="display: inline-block; max-width: 400px; margin-left: 30px; font-size: 12px; font-weight: normal">';
                    $html .= '<span class="small bold">Facture : </span>';

                    if (count($factures) > 1) {
                        $html .= BimpInput::renderInput('select', 'client_' . $id_client . '_fac_' . $fac_idx, 0, array(
                                    'options'     => $factures,
                                    'extra_class' => 'client_facture_select'
                        ));
                    } else {
                        $html .= 'Nouvelle facture';
                        $html .= '<input type="hidden" name="client_' . $id_client . '_fac_' . $fac_idx . '" value="0"/>';
                    }

                    $fac_contrats = array();

                    foreach ($facture_data['lines'] as $id_line) {
                        $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $id_contrat = (int) $line->getData('fk_contrat');
                            if (!in_array($id_contrat, $fac_contrats)) {
                                $fac_contrats[] = $id_contrat;
                            }
                        }
                    }

                    $fac_libelle = '';
                    if (isset($facture_data['contrats_libelles']) && count($facture_data['contrats_libelles']) == 1) {
                        $fac_libelle = array_shift($facture_data['contrats_libelles']);
                    } else {
                        $fac_libelle = 'Facturation abonnement' . ($facture_data['lines'] > 1 ? 's' : '');
                    }

                    $html .= '<div class="fac_libelle_container" style="margin-top: 10px">';
                    $html .= '<span class="small bold">Libellé facture : </span>';
                    $html .= BimpInput::renderInput('text', 'client_' . $id_client . '_fac_' . $fac_idx . '_libelle', $fac_libelle);
                    $html .= '</div>';

                    $html .= '</div>';

                    $html .= '</td>';
                    $html .= '</tr>';

                    foreach ($facture_data['lines'] as $id_main_line) {
                        $main_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_main_line);
                        if (BimpObject::objectLoaded($main_line)) {
                            $id_parent_line = (int) $main_line->getData('id_parent_line');
                            if ($id_parent_line && in_array($id_parent_line, $all_lines)) {
                                continue;
                            }

                            $sub_lines = array($id_main_line);

                            $list = $this->getList(array(
                                'id_parent_line' => $id_main_line
                                    ), null, null, 'rang', 'asc', 'array', array('rowid'));

                            if (!empty($list)) {
                                foreach ($list as $item) {
                                    $sub_lines[] = (int) $item['rowid'];
                                }
                            }

                            foreach ($sub_lines as $id_line) {
                                $tr_class = '';
                                $row_html = '';

                                $is_sub_line = false;

                                if ($id_main_line !== $id_line) {
                                    $is_sub_line = true;
                                    $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);
                                } else {
                                    $line = $main_line;
                                }

                                if (BimpObject::objectLoaded($line)) {
                                    $tr_class = '';
                                    $line_errors = array();
                                    $nb_decimals = 6;
                                    $periods_data = $line->getPeriodsToBillData($line_errors, true, true);
                                    $canFactAvance = $this->canSetAction('facturationAvance');

                                    $row_html .= '<td style="min-width: 30px; max-width: 30px; text-align: center;' . ($is_sub_line ? 'border-left: 3px solid #' . $primary_color : '') . '">';
                                    if (empty($line_errors) &&
                                            ($periods_data['nb_periods_tobill_today'] > 0 || ($canFactAvance && $periods_data['nb_periods_tobill_max'] > 0))) {
                                        if (!$is_sub_line) {

                                            if (!$id_parent_line && $periods_data['nb_periods_tobill_today'] > 0) {
                                                $tr_class = 'selected';
                                                $main_line_selected = true;
                                            }

                                            $row_html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check"' . (!$id_parent_line && $periods_data['nb_periods_tobill_today'] > 0 ? ' checked="1"' : '') . '/>';
                                        } else {
                                            if ($main_line_selected) {
                                                $tr_class = 'selected';
                                            }
                                            $row_html .= BimpRender::renderIcon('fas_level-up-alt', '', 'transform: rotate(90deg);font-size: 16px;');
                                        }
                                    } else {
                                        $tr_class = 'deactivated';
                                    }
                                    $row_html .= '</td>';

                                    $row_html .= '<td>';
                                    $contrat = $line->getParentInstance();
                                    if (BimpObject::objectLoaded($contrat)) {
                                        $row_html .= $contrat->getLink();
                                    }

                                    if ($debug) {
                                        $row_html .= BimpRender::renderFoldableContainer('Infos débug', '<pre>' . print_r($periods_data, 1) . '</pre>', array(
                                                    'open' => false
                                        ));
                                    }
                                    $row_html .= '</td>';

                                    $row_html .= '<td>';
                                    $product = $line->getChildObject('product');
                                    if (BimpObject::objectLoaded($product)) {
                                        $nb_decimals = (int) $product->getData('variable_qty_decimals');
                                        $row_html .= $product->getLink() . '<br/>';
                                        $row_html .= $product->getName() . '<br/>';
                                        $row_html .= $line->displayPeriodicity(false, array('fac'));
                                    }
                                    $row_html .= '</td>';

                                    $row_html .= '<td style="min-width: 250px">';

                                    if (!$is_sub_line && $id_parent_line) {
                                        $row_html .= BimpRender::renderAlerts('Attention : cette ligne est incluse dans un bundle - Veuillez de préférence sélectionner la ligne principale du bundle afin de facturer simultanément l\'ensemble des lignes du bundle', 'warning');
                                    }

                                    $variable_qty = (int) $line->getData('variable_qty');
                                    $class = ($periods_data['nb_periods_tobill_today'] > 0 ? ($periods_data['nb_periods_tobill_today'] > 1 ? 'important' : 'warning') : 'danger');
                                    $s = ($periods_data['nb_periods_tobill_today'] > 1 ? 's' : '');
                                    $qty = $periods_data['nb_periods_tobill_today'] * $periods_data['qty_for_1_period'];

                                    $row_html .= 'A traiter aujoud\'hui : <span class="' . $class . '">' . $periods_data['nb_periods_tobill_today'] . ' période' . $s . ' de facturation</span>';

                                    if (!$variable_qty) {
                                        $row_html .= '&nbsp;(';
                                        $row_html .= BimpTools::displayFloatValue($qty, 4, ',', 0, 1, 0, 1, 1, 1);
                                        $row_html .= ' unité' . (abs($qty) > 1 ? 's' : '') . ')';
                                    }
                                    $row_html .= '<br/>';

                                    if (!empty($line_errors)) {
                                        $row_html .= BimpRender::renderAlerts($line_errors);
                                    } elseif ($periods_data['nb_periods_tobill_today'] > 0 || ($canFactAvance && $periods_data['nb_periods_tobill_max'] > 0)) {
                                        $is_first_period = ($periods_data['date_next_period_tobill'] == $periods_data['date_first_period_start']);
                                        if ($is_first_period && $periods_data['first_period_prorata'] != 1) {
                                            $msg = BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft');
                                            $msg .= 'Première période du <b>' . date('d / m / Y', strtotime($periods_data['date_first_period_start']));
                                            $msg .= '</b> au <b>' . date('d / m / Y', strtotime($periods_data['date_first_period_end'])) . '</b>';
                                            $msg .= ' facturée à partir du <b>' . date('d / m / Y', strtotime($periods_data['date_fac_start'])) . '</b>';
                                            $msg .= '<br/>Prorata : <b>' . BimpTools::displayFloatValue($periods_data['first_period_prorata'], 4, ',', 0, 1, 0, 1, 1, 1) . '</b>';
                                            $row_html .= BimpRender::renderAlerts($msg, 'info');
                                        } else {
                                            $row_html .= '<br/>';
                                        }

                                        if (!$is_sub_line) {
                                            $row_html .= '<b>Nb périodes à facturer: </b><br/>';
                                            $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_nb_periods', $periods_data['nb_periods_tobill_today'], array(
                                                        'extra_class' => 'line_nb_periods',
                                                        'max_label'   => true,
                                                        'data'        => array(
                                                            'max'      => $periods_data['nb_periods_tobill_max'],
                                                            'min'      => 0,
                                                            'decimals' => 0
                                                        )
                                            ));
                                        }

                                        if (BimpObject::objectLoaded($product)) {
                                            if ($variable_qty) {
                                                $qty_per_period = $line->getFacQtyPerPeriod();
                                                $unit_label = $product->getData('variable_qty_unit');

                                                $row_html .= '<div style="margin-top: 10px" class="variable_qty_inputs_container">';
                                                $row_html .= BimpInput::renderInput('select', 'line_' . $line->id . '_qty_mode', 'per_period', array(
                                                            'extra_class' => 'line_qty_mode',
                                                            'data'        => array(
                                                                'id_line' => $line->id
                                                            ),
                                                            'options'     => array(
                                                                'per_period' => 'Quantité à facturer par période',
                                                                'total'      => 'Quantité totale à facturer'
                                                            )
                                                ));
                                                $row_html .= '<input type="hidden" value="' . $qty_per_period . '" name="line_' . $line->id . '_qty_per_period" class="²"/>';

                                                $row_html .= '<div style="margin: 5px 0" class="line_' . $line->id . '_qties_per_period">';
                                                $qties_errors = array();
                                                $qties_data = $line->getQtiesToInvoice($periods_data, $qties_errors);
                                                $qty_total_today = 0;

                                                if (!count($qties_errors)) {
                                                    if (isset($qties_data[0])) {
                                                        $bought = $qties_data[0]['bought'];
                                                        $billed = $qties_data[0]['billed'];
                                                        $diff = $bought - $billed;
                                                        if (($qty_per_period > 0 && $diff > 0) || ($qty_per_period < 0 && $diff < 0)) {
                                                            $row_html .= '<span class="small">Qté achetée non facturée avant prochaine période facturée : <b>' . round($diff, 6) . '</b></span><br/>';
                                                        }
                                                    }

                                                    for ($i = 1; $i <= $periods_data['nb_periods_tobill_max']; $i++) {
                                                        $row_html .= '<div style="margin-top: 5px;' . ($i > $periods_data['nb_periods_tobill_today'] ? 'display: none' : '') . '" class="period_qty_input_container" data-period="' . $i . '">';

                                                        $row_html .= ' - <b>Période ' . $i . '</b> <span class="small">(' . $qties_data[$i]['dates'] . ')</span> : <br/>';

                                                        $qty = 0;
                                                        if ($i === 1 && (($qty_per_period > 0 && $diff > 0) || ($qty_per_period < 0 && $diff < 0))) {
                                                            $qty += $diff;
                                                        }

                                                        $bought = $qties_data[$i]['bought'];
                                                        $billed = $qties_data[$i]['billed'];
                                                        $diff = $bought - $billed;
                                                        $qty += (($qty_per_period > 0 && $diff > 0) || ($qty_per_period < 0 && $diff < 0) ? $diff : $qty_per_period);

                                                        if ($i === 1 && $is_first_period && ($qty == $qty_per_period)) {
                                                            $qty *= $periods_data['first_period_prorata'];
                                                        }

                                                        if ($i <= $periods_data['nb_periods_tobill_today']) {
                                                            $qty_total_today += $qty;
                                                        }

                                                        $qty = round($qty, 6);

                                                        $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty_period_' . $i, $qty, array(
                                                                    'extra_class' => 'line_period_qty',
                                                                    'data'        => array(
                                                                        'period'   => $i,
                                                                        'min'      => ($qty_per_period >= 0 ? 0 : 'none'),
                                                                        'max'      => ($qty_per_period < 0 ? 0 : 'none'),
                                                                        'decimals' => 6
                                                                    )
                                                                )) . ($unit_label ? ' ' . $unit_label : '') . '&nbsp;&nbsp;&nbsp;&nbsp;';

                                                        if (($qty_per_period > 0 && $diff > 0) || ($qty_per_period < 0 && $diff < 0)) {
                                                            $row_html .= '<span class="small success">(Qté achetée non facturée)</span>';
                                                        } else {
                                                            $row_html .= '<span class="small warning">(Qté par défaut)</span>';
                                                        }
                                                        $row_html .= '</div>';
                                                    }

                                                    $row_html .= '<div style="margin: 8px 0; padding: 5px; border: 2px solid #B38424">';
                                                    $row_html .= 'Qté totale à facturer : <span class="line_total_qty bold">' . round($qty_total_today, $nb_decimals) . '</span>';
                                                    $row_html .= '</div>';
                                                } else {
                                                    $row_html .= BimpRender::renderAlerts($qties_errors);
                                                    $qty_total_today = $qty_per_period * $periods_data['nb_periods_tobill_today'];
                                                }
                                                $row_html .= '</div>';

                                                $row_html .= '<div style="margin: 5px 0; display: none" class="line_' . $line->id . '_total_qty">';

                                                $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_total_qty', round($qty_total_today, $nb_decimals), array(
                                                            'data' => array(
                                                                'min'      => ($qty_per_period >= 0 ? 0 : 'none'),
                                                                'max'      => ($qty_per_period < 0 ? 0 : 'none'),
                                                                'decimals' => 6
                                                            )
                                                        )) . ($unit_label ? ' ' . $unit_label : '');

                                                $row_html .= '</div>';
                                                $row_html .= '</div>';
                                            }
                                        }
                                    }

                                    $row_html .= '</td>';

                                    $row_html .= '<td>';
                                    if ((int) $line->getData('variable_pu_ht')) {
                                        $row_html .= '<p class="warning">';
                                        $row_html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'PU HT à renseigner';
                                        $row_html .= '</p>';

                                        $row_html .= BimpInput::renderInput('text', 'line_' . $line->id . '_subprice', (float) $line->getData('subprice'), array(
                                                    'data'        => array(
                                                        'data_type' => 'number',
                                                        'decimals'  => 7
                                                    ),
                                                    'addon_right' => '<i class="fa fa-' . BimpTools::getCurrencyIcon('EUR') . '"></i>',
                                                    'style'       => 'width: 120px'
                                        ));
                                    } else {
                                        $row_html .= BimpTools::displayMoneyValue($line->getData('subprice'));
                                    }
                                    $row_html .= '</td>';

                                    $row_html .= '<td style="text-align: center">';
                                    $row_html .= '<b>' . date('d / m / Y', strtotime($periods_data['date_next_facture'])) . '</b>';
                                    $row_html .= '</td>';
                                } else {
                                    $row_html .= '<td colspan="99">';
                                    $row_html .= BimpRender::renderAlerts('La ligne de contrat #' . $id_line . ' n\'existe plus');
                                    $row_html .= '</td>';
                                }

                                $html .= '<tr class="contrat_line_row' . ($tr_class ? ' ' . $tr_class : '') . ($is_sub_line ? ' sub_line line_' . $id_main_line . '_sub_line' : '' ) . '"';
                                $html .= ' data-id_client="' . $id_client . '"';
                                $html .= ' data-fac_idx="' . $fac_idx . '"';
                                $html .= ' data-id_line="' . $id_line . '"';
                                $html .= ' data-is_sub_line="' . (int) $is_sub_line . '"';
                                $html .= ' data-nb_periods_default="' . $periods_data['nb_periods_tobill_today'] . '"';
                                $html .= ' data-nb_decimals="' . $nb_decimals . '"';
                                $html .= '>';
                                $html .= $row_html;
                                $html .= '</tr>';
                            }
                        }
                    }
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public function renderPeriodicAchatProcessInputs(&$errors = array())
    {
        $html = '';

        $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');
        $id_fourn_filter = (int) BimpTools::getPostFieldValue('id_fourn', 0, 'int');
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0, 'int');
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0, 'int');
        $id_contrat = (int) BimpTools::getPostFieldValue('id_contrat', 0, 'int');

        $errors = array();
        $debug = (BimpCore::isModeDev() || BimpCore::isUserDev());
        $lines = self::getPeriodicAchatLinesToProcess(array(
                    'return'     => 'data',
                    'id_lines'   => $id_lines,
                    'id_client'  => $id_client,
                    'id_product' => $id_product,
                    'id_contrat' => $id_contrat
                        ), $errors);

        // Trie par fournisseur et entrepot:
        $lines_by_fourns = array();

        foreach ($lines as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);
            if (!BimpObject::objectLoaded($line)) {
                continue;
            }

            $id_entrepot = (int) BimpTools::getArrayValueFromPath($line_data, 'id_entrepot', 0);
            if (!$id_entrepot) {
                $errors[] = 'Ligne #' . $id_line . ' : entrepôt absent';
                continue;
            }

            $line_errors = array();

            $id_fourn = 0;
            $pa_ht_line = (float) $line->getData('buy_price_ht');
            $pa_ht_fourn = 0;

            $id_pfp = (int) $line->getData('fk_product_fournisseur_price');
            if (!$id_pfp) {
                $line_errors[] = 'Aucun prix d\'achat fournisseur sélectionné pour cette ligne de contrat';
            } else {
                $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);
                if (!BimpObject::objectLoaded($pfp)) {
                    $line_errors[] = 'Le prix d\'achat fournisseur #' . $id_pfp . ' n\'existe plus';
                } else {
                    $id_fourn = $pfp->getData('fk_soc');
                    $pa_ht_fourn = $pfp->getData('price');
                }
            }

            if ($id_fourn_filter && $id_fourn != $id_fourn_filter) {
                continue;
            }

            if (!isset($lines_by_fourns[$id_fourn])) {
                $lines_by_fourns[$id_fourn] = array();
            }

            if (!isset($lines_by_fourns[$id_fourn][$id_entrepot])) {
                $lines_by_fourns[$id_fourn][$id_entrepot] = array();
            }

            $lines_by_fourns[$id_fourn][$id_entrepot][$id_line] = array(
                'pa_ht_line'  => $pa_ht_line,
                'pa_ht_fourn' => $pa_ht_fourn,
                'errors'      => $line_errors
            );
        }

        if (empty($lines_by_fourns)) {
            $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . '<span class = "success">Aucun achat périodique à effectuer à date</span>';
        } else {
            $html .= '<div class="buttonsContainer align-right" style="margin:  0 0 5px 0; padding: 0">';
            $html .= '<span class="btn btn-default check_all_lines">';
            $html .= BimpRender::renderIcon('far_check-square', 'iconLeft') . 'Tout cocher';
            $html .= '</span>';
            $html .= '<span class="btn btn-default uncheck_all_lines">';
            $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Tout décocher';
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<table class = "bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="min-width: 30px; max-width: 30px; text-align: center"></th>';
            $html .= '<th>Contrat</th>';
            $html .= '<th>Produit / service</th>';
            $html .= '<th>Quantités</th>';
            $html .= '<th>Date prochain achat</th>';
            $html .= '<th>Prix d\'achat</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            foreach ($lines_by_fourns as $id_fourn => $entrepots) {
                if ($id_fourn) {
                    $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                    if (!BimpObject::objectLoaded($fourn)) {
                        $html .= '<tr>';
                        $html .= '<td colspan="99">' . BimpRender::renderAlerts('Le fournisseur #' . $id_fourn . ' n\'existe plus') . '</td>';
                        $html .= '</tr>';
                        continue;
                    }
                }

                $html .= '<tr class="fourn_row" data-id_fourn="' . $id_fourn . '">';
                $html .= '<td colspan="99" style="font-weight: bold; font-size: 14px; padding: 10px; background-color: #DCDCDC">';
                if ($id_fourn) {
                    $html .= 'Fournisseur : ' . $fourn->getLink();
                } else {
                    $html .= '<span class="danger">';
                    $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Lignes de commandes sans prix d\'achat fournisseur défini';
                    $html .= '</span>';
                }

                $html .= '</td>';
                $html .= '</tr>';

                foreach ($entrepots as $id_entrepot => $entrepot_lines) {
                    if ($id_entrepot) {
                        $entrepot = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Entrepot', $id_entrepot);
                        $html .= '<tr class="fourn_entrepot_row" data-id_fourn="' . $id_fourn . '" data-id_entrepot="' . $id_entrepot . '">';
                        $html .= '<td colspan="99" style="font-weight: bold; font-size: 12px; padding: 10px; background-color: #DCDCDC">';
                        $html .= 'Entrepôt : ' . $entrepot->getLink();

                        if ($id_fourn) {
                            $commandes = $this->getCommandesFournisseursArray($id_fourn, $id_entrepot);
                        } else {
                            $commandes = array();
                        }

                        $html .= '<div style="display: inline-block; max-width: 400px; margin-left: 30px; font-size: 12px; font-weight: normal">';
                        $html .= '<span class="small">Commande fournisseur : </span>';

                        if (count($commandes) > 1) {
                            $html .= BimpInput::renderInput('select', 'fourn_' . $id_fourn . '_entrepot_' . $id_entrepot . '_commande_fourn', 'new', array(
                                        'options' => $commandes
                            ));
                        } else {
                            $html .= 'Nouvelle commande fournisseur';
                            $html .= '<input type="hidden" name="fourn_' . $id_fourn . '_entrepot_' . $id_entrepot . '_commande_fourn" value="new"/>';
                        }

                        $html .= '</div>';

                        $html .= '</td>';
                        $html .= '</tr>';
                    }

                    foreach ($entrepot_lines as $id_line => $line_data) {
                        $tr_class = '';
                        $row_html = '';

                        $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            if (!(int) $line->getData('achat_periodicity') || in_array($line->getData('linked_object_name'), array('bundleCorrect'))) {
                                continue;
                            }
                            $line_errors = array();
                            $nb_decimals = 6;
                            $periods_data = $line->getPeriodsToBuyData($line_errors);
                            $line_errors = BimpTools::merge_array($line_errors, BimpTools::getArrayValueFromPath($line_data, 'errors', array()));

                            $row_html .= '<td style="min-width: 30px; max-width: 30px; text-align: center">';
                            if ($id_fourn) {
                                $tr_class = '';
                                if ($periods_data['nb_periods_tobuy_max'] > 0) {
                                    if ($periods_data['nb_periods_tobuy_today'] > 0) {
                                        $tr_class = 'selected';
                                    }
                                    $row_html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check"' . ($periods_data['nb_periods_tobuy_today'] > 0 ? ' checked="1"' : '') . '/>';
                                } else {
                                    $tr_class = 'deactivated';
                                }
                            } else {
                                $tr_class = 'deactivated';
                            }

                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $contrat = $line->getParentInstance();
                            if (BimpObject::objectLoaded($contrat)) {
                                $row_html .= $contrat->getLink();
                            } else {
                                $row_html .= '<span class="danger">Contrat #' . $line->getData('fk_contrat') . ' (n\'existe plus)</span>';
                            }
                            if ($debug) {
                                $row_html .= BimpRender::renderFoldableContainer('Infos débug', '<pre>' . print_r($periods_data, 1) . '</pre>', array(
                                            'open' => false
                                ));
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $product = $line->getChildObject('product');
                            if (BimpObject::objectLoaded($product)) {
                                $nb_decimals = (int) $product->getData('variable_qty_decimals');
                                $row_html .= $product->getLink() . '<br/>';
                                $row_html .= $product->getName() . '<br/>';
                            }
                            $row_html .= '</td>';

                            if (count($line_errors)) {
                                $row_html .= '<td colspan="4">';
                                $row_html .= BimpRender::renderAlerts($line_errors);
                                $row_html .= '</td>';
                            } else {
                                $row_html .= '<td style="min-width: 390px">';

                                $variable_qty = (int) $line->getData('variable_qty');
                                $class = ($periods_data['nb_periods_tobuy_today'] > 0 ? ($periods_data['nb_periods_tobuy_today'] > 1 ? 'important' : 'warning') : 'danger');
                                $s = ($periods_data['nb_periods_tobuy_today'] > 1 ? 's' : '');
                                $row_html .= 'A traiter aujoud\'hui : <span class="' . $class . '">' . $periods_data['nb_periods_tobuy_today'] . ' période' . $s . '</span>';

                                if (!$variable_qty) {
                                    $row_html .= '&nbsp;(' . ($periods_data['nb_periods_tobuy_today'] * $periods_data['qty_for_1_period']) . ' unité' . $s . ')';
                                }
                                $row_html .= '<br/>';

                                if ($id_fourn && $periods_data['nb_periods_tobuy_max'] > 0) {
                                    $is_first_period = ($periods_data['date_next_achat'] == $periods_data['date_achat_start']);
                                    if ($is_first_period && $periods_data['first_period_prorata'] != 1) {
                                        $msg = BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft');
                                        $msg .= 'Première période du <b>' . date('d / m / Y', strtotime($periods_data['date_first_period_start']));
                                        $msg .= '</b> au <b>' . date('d / m / Y', strtotime($periods_data['date_first_period_end'])) . '</b>';
                                        $msg .= ' en achat partiel à partir du <b>' . date('d / m / Y', strtotime($periods_data['date_achat_start'])) . '</b>';
                                        $msg .= '<br/>Prorata : <b>' . BimpTools::displayFloatValue($periods_data['first_period_prorata'], 4, ',', 0, 1, 0, 1, 1, 1) . '</b>';
                                        $row_html .= BimpRender::renderAlerts($msg, 'info');
                                    } else {
                                        $row_html .= '<br/>';
                                    }

                                    $row_html .= '<b>Nb périodes à acheter: </b><br/>';
                                    $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_nb_periods', $periods_data['nb_periods_tobuy_today'], array(
                                                'extra_class' => 'line_nb_periods',
                                                'max_label'   => true,
                                                'data'        => array(
                                                    'max'      => $periods_data['nb_periods_tobuy_max'],
                                                    'min'      => 0,
                                                    'decimals' => 0
                                                )
                                    ));

                                    if ($variable_qty) {
                                        $qty_per_period = $line->getAchatQtyPerPeriod();
                                        $unit_label = $product->getData('variable_qty_unit');

                                        $row_html .= '<div style="margin-top: 10px" class="variable_qty_inputs_container">';
                                        $row_html .= BimpInput::renderInput('select', 'line_' . $line->id . '_qty_mode', 'per_period', array(
                                                    'extra_class' => 'line_qty_mode',
                                                    'data'        => array(
                                                        'id_line' => $line->id
                                                    ),
                                                    'options'     => array(
                                                        'per_period' => 'Quantité à acheter par période',
                                                        'total'      => 'Quantité totale à acheter'
                                                    )
                                        ));
                                        $row_html .= '<input type="hidden" value="' . $qty_per_period . '" name="line_' . $line->id . '_qty_per_period"/>';

                                        $row_html .= '<div style="margin: 5px 0" class="line_' . $line->id . '_qties_per_period">';
                                        $qties_errors = array();
                                        $qties_data = $line->getQtiesToBuy($periods_data, $qties_errors);
                                        $qty_total_today = 0;

                                        if (!count($qties_errors)) {
                                            if (isset($qties_data[0])) {
                                                $bought = $qties_data[0]['bought'];
                                                $billed = $qties_data[0]['billed'];
                                                $diff = $billed - $bought;
                                                if (($qty_per_period > 0 && $diff > 0) || ($qty_per_period < 0 && $diff < 0)) {
                                                    $row_html .= '<span class="small">Qté facturée non achetée avant prochaine période d\'achat : <b>' . round($diff, 6) . '</b></span><br/>';
                                                }
                                            }

                                            for ($i = 1; $i <= $periods_data['nb_periods_tobuy_max']; $i++) {
                                                $row_html .= '<div style="margin-top: 5px;' . ($i > $periods_data['nb_periods_tobuy_today'] ? 'display: none' : '') . '" class="period_qty_input_container" data-period="' . $i . '">';

                                                $row_html .= ' - <b>Période ' . $i . '</b> <span class="small">(' . $qties_data[$i]['dates'] . ')</span> : <br/>';

                                                $qty = 0;
                                                if ($i === 1 && ($qty_per_period > 0 && $diff > 0) || ($qty_per_period < 0 && $diff < 0)) {
                                                    $qty += $diff;
                                                }

                                                $bought = $qties_data[$i]['bought'];
                                                $billed = $qties_data[$i]['billed'];
                                                $diff = $billed - $bought;
                                                $qty += (($qty_per_period > 0 && $diff > 0) || ($qty_per_period < 0 && $diff < 0) ? $diff : $qty_per_period);

                                                if ($i === 1 && $is_first_period && ($qty == $qty_per_period)) {
                                                    $qty *= $periods_data['first_period_prorata'];
                                                }

                                                if ($i <= $periods_data['nb_periods_tobuy_today']) {
                                                    $qty_total_today += $qty;
                                                }

                                                $qty = round($qty, 6);

                                                $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty_period_' . $i, $qty, array(
                                                            'extra_class' => 'line_period_qty',
                                                            'data'        => array(
                                                                'period'   => $i,
                                                                'min'      => ($qty_per_period >= 0 ? 0 : 'none'),
                                                                'max'      => ($qty_per_period < 0 ? 0 : 'none'),
                                                                'decimals' => 6
                                                            )
                                                        )) . ($unit_label ? ' ' . $unit_label : '') . '&nbsp;&nbsp;';

                                                if (($qty_per_period > 0 && $diff > 0) || ($qty_per_period < 0 && $diff < 0)) {
                                                    $row_html .= '<span class="small success">(Qté facturée non achetée : ' . $qty . ')</span>';
                                                } else {
                                                    $row_html .= '<span class="small warning">(Qté par défaut : ' . $qty_per_period . ')</span>';
                                                }
                                                $row_html .= '</div>';
                                            }

                                            $row_html .= '<div style="margin: 8px 0; padding: 5px; border: 2px solid #B38424">';
                                            $row_html .= 'Qté totale à acheter : <span class="line_total_qty bold">' . round($qty_total_today, $nb_decimals) . '</span>';
                                            $row_html .= '</div>';
                                        } else {
                                            $row_html .= BimpRender::renderAlerts($qties_errors);
                                            $qty_total_today = $qty_per_period * $periods_data['nb_periods_tobuy_today'];
                                        }
                                        $row_html .= '</div>';

                                        $row_html .= '<div style="margin: 5px 0; display: none" class="line_' . $line->id . '_total_qty">';

                                        $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_total_qty', round($qty_total_today, $nb_decimals), array(
                                                    'data' => array(
                                                        'min'      => ($qty_per_period >= 0 ? 0 : 'none'),
                                                        'max'      => ($qty_per_period < 0 ? 0 : 'none'),
                                                        'decimals' => 6
                                                    )
                                                )) . ($unit_label ? ' ' . $unit_label : '');

                                        $row_html .= '</div>';
                                        $row_html .= '</div>';
                                    }
                                }

                                $row_html .= '</td>';
                                $row_html .= '<td>';
                                $row_html .= $line->displayDataDefault('date_next_achat');
                                $row_html .= '</td>';

                                $row_html .= '<td>';
                                $pa_label = 'Prix d\'achat actuel du fournisseur';
                                $pa_ht = (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht_fourn', 0);
                                if (!$pa_ht) {
                                    $pa_ht = (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht_line', 0);
                                    $pa_label = 'PA HT enregistré pour cette ligne de contrat';
                                }

                                if ($id_fourn && $periods_data['nb_periods_tobuy_max'] > 0) {
                                    $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                                    if (BimpObject::objectLoaded($fourn)) {
                                        $row_html .= 'Fournisseur : ' . $fourn->getLink() . '<br/>';
                                    }
                                    $row_html .= BimpInput::renderInput('text', 'line_' . $line->id . '_pa_ht', $pa_ht, array(
                                                'extra_class' => 'line_pa_ht',
                                                'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                                                'data'        => array(
                                                    'data_type' => 'number',
                                                    'decimals'  => 5
                                                )
                                    ));
                                    $row_html .= '<p class="small">' . $pa_label . '</p>';
                                } else {
                                    $row_html .= '<b>' . BimpTools::displayMoneyValue($pa_ht) . '</b>';
                                }

                                $row_html .= '</td>';
                            }
                        } else {
                            $row_html .= '<td colspan="99">';
                            $row_html .= BimpRender::renderAlerts('La ligne de commande #' . $id_line . ' n\'existe plus');
                            $row_html .= '</td>';
                        }

                        $html .= '<tr class="contrat_line_row' . ($tr_class ? ' ' . $tr_class : '') . '"';
                        $html .= ' data-id_fourn="' . $id_fourn . '"';
                        $html .= ' data-id_entrepot="' . $id_entrepot . '"';
                        $html .= ' data-id_line="' . $id_line . '"';
                        $html .= ' data-nb_periods_default="' . $periods_data['nb_periods_tobuy_today'] . '"';
                        $html .= ' data-nb_decimals="' . $nb_decimals . '"';
                        $html .= '>';
                        $html .= $row_html;
                        $html .= '</tr>';
                    }
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public function renderPeriodicOperationsToProcessOverview($params = array())
    {
        $html = '';

        $params = BimpTools::overrideArray(array(
                    'return'     => 'count',
                    'id_contrat' => 0,
                    'id_client'  => 0,
                    'id_fourn'   => 0,
                    'id_product' => 0
                        ), $params);

        $nb_facs = self::getPeriodicFacLinesToProcess($params);
        $nb_achats = self::getPeriodicAchatLinesToProcess($params);

        $html .= '<table class="bimp_list_table">';
        $html .= '<tbody class="headers_col">';

        if (!(int) $params['id_fourn']) {
            // Facturation : 
            $html .= '<tr>';
            $html .= '<th>' . BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Facturations</th>';
            $html .= '<td><span class="badge badge-' . ($nb_facs > 0 ? 'warning' : 'success') . '">' . $nb_facs . '</span></td>';
            $html .= '<td style="text-align: right">';
            if ($nb_facs > 0 && $this->canSetAction('periodicFacProcess')) {
                $onclick = $this->getJsActionOnclick('periodicFacProcess', array(
                    'operation_type' => 'fac',
                    'id_client'      => $params['id_client'],
                    'id_product'     => $params['id_product'],
                    'id_contrat'     => $params['id_product']
                        ), array(
                    'form_name'        => 'periodic_process',
                    'on_form_submit'   => 'function($form, extra_data) { return BimpContrat.onPeriodicFacProcessFormSubmit($form, extra_data); }',
                    'use_bimpdatasync' => true,
                    'use_report'       => true
                ));

                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $html .= 'Tout traiter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                $html .= '</span>';
            }
            $html .= '</tr>';
        }

        // Achats : 
        $html .= '<tr>';
        $html .= '<th>' . BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . 'Achats</th>';
        $html .= '<td><span class="badge badge-' . ($nb_achats > 0 ? 'warning' : 'success') . '">' . $nb_achats . '</span></td>';
        $html .= '<td style="text-align: right">';
        if ($nb_achats > 0 && $this->canSetAction('periodicAchatProcess')) {
            $onclick = $this->getJsActionOnclick('periodicAchatProcess', array(
                'operation_type' => 'achat',
                'id_client'      => $params['id_client'],
                'id_fourn'       => $params['id_fourn'],
                'id_contrat'     => $params['id_contrat'],
                'id_product'     => $params['id_product']
                    ), array(
                'form_name'        => 'periodic_process',
                'on_form_submit'   => 'function($form, extra_data) { return BimpContrat.onPeriodicAchatProcessFormSubmit($form, extra_data); }',
                'use_bimpdatasync' => true,
                'use_report'       => true
            ));

            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= 'Tout traiter' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
            $html .= '</span>';
        }
        $html .= '</tr>';

        $html .= '</tag>';
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderPeriodicOperationsList($type, $id_client = 0, $id_product = 0, $id_fourn = 0, $id_contrat = 0)
    {
        $title = '';
        $list_name = '';

        switch ($type) {
            case 'fac':
                $title = 'Facturations périodiques';
                $list_name = 'facturation';
                break;

            case 'achat':
                $title = 'Achats';
                $list_name = 'achat';
                break;

            default:
                return BimpRender::renderAlerts('Type de liste invalide');
        }

        if ($id_contrat) {
            $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat);
            if (!BimpObject::objectLoaded($contrat)) {
                return BimpRender::renderAlerts('Le contrat #' . $id_contrat . ' n\'existe pas');
            }
            $title .= ' du contrat ' . $contrat->getRef();
        } elseif ($id_client) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
            if (!BimpObject::objectLoaded($client)) {
                return BimpRender::renderAlerts('Le client #' . $id_client . ' n\'existe pas');
            }
            $title .= ' du client ' . $client->getRef() . ' - ' . $client->getName();
        } elseif ($id_product) {
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
            if (!BimpObject::objectLoaded($product)) {
                return BimpRender::renderAlerts('Le produit #' . $id_product . ' n\'existe pas');
            }
            $title .= ' pour le produit ' . $product->getRef() . ' - ' . $product->getName();
        } elseif ($type == 'achat' && $id_fourn) {
            $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
            if (!BimpObject::objectLoaded($fourn)) {
                return BimpRender::renderAlerts('Le fournisseur #' . $id_fourn . ' n\'existe pas');
            }
            $title .= ' auprès du fournisseur ' . $fourn->getRef() . ' - ' . $fourn->getName();
        }

        $this->periods_list_id_contrat = $id_contrat;
        $this->periods_list_id_client = $id_client;
        $this->periods_list_id_fourn = $id_fourn;
        $this->periods_list_id_product = $id_product;

        $bc_list = new BC_ListTable($this, $list_name, 1, null, $title, 'fas_calendar-alt');
        $bc_list->addJoin('contrat', 'a.fk_contrat = parent.rowid', 'parent');
        $bc_list->addFieldFilterValue('parent.statut', array(
            'operator' => '>',
            'value'    => 0
        ));
        $bc_list->addFieldFilterValue('a.' . $type . '_periodicity', array(
            'operator' => '>',
            'value'    => 0
        ));

        if ($id_client) {
            $bc_list->addFieldFilterValue('parent.fk_soc', (int) $id_client);
        } elseif ($id_product) {
            $bc_list->addFieldFilterValue('a.fk_product', (int) $id_product);
        } elseif ($type == 'achat' && $id_fourn) {
            $bc_list->addJoin('product_fournisseur_price', 'a.fk_product_fournisseur_price = pfp.rowid', 'pfp');
            $bc_list->addFieldFilterValue('pfp.fk_soc', (int) $id_fourn);
        }

        $list_html = $bc_list->renderHtml();

        if ($id_client || $id_fourn || $id_product || $id_contrat) {
            return $list_html;
        }

        $tabs = array();

        $tabs[] = array(
            'id'      => 'periodic_' . $type . '_list_tab',
            'title'   => BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Liste',
            'content' => $list_html
        );

        $tabs[] = array(
            'id'            => 'periodic_' . $type . '_reports_tab',
            'title'         => BimpRender::renderIcon('fas_file-alt', 'iconLeft') . 'Rapports',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderPeriodsReportsList', '$(\'#periodic_' . $type . '_reports_tab .nav_tab_ajax_result\')', array($type), array('button' => ''))
        );

        return BimpRender::renderNavTabs($tabs);
    }

    public function renderPeriodsReportsList($type)
    {
        if (!in_array($type, array('exp', 'fac', 'achat'))) {
            return BimpRender::renderAlerts('Type d\'opération invalide');
        }

        $report = BimpObject::getInstance('bimpdatasync', 'BDS_Report');

        $title = 'Contrats d\'abonnement - Rapports des ';

        switch ($type) {
            case 'fac':
                $code = 'CONTRATS_LINES_FATURATION';
                $title .= 'facturations';
                break;

            case 'achat':
                $code = 'CONTRATS_LINES_ACHATS';
                $title .= 'achats';
                break;
        }

        $list = new BC_ListTable($report, 'operation', 1, null, $title);
        $list->addIdentifierSuffix($type);
        $list->addFieldFilterValue('code', $code);

        return $list->renderHtml();
    }

    public function renderFacAchatsSynthese()
    {
        $tabs = array();

        $tabs[] = array(
            'id'      => 'fac_achat_qties',
            'title'   => 'Synthèse quantités',
            'icon'    => 'fas_list',
            'content' => $this->renderFacAchatSyntheseTab()
        );

        $tabs[] = array(
            'id'      => 'fac',
            'title'   => 'Factures',
            'icon'    => 'fas_file-invoice-dollar',
            'content' => $this->renderFacturesTab()
        );

        $tabs[] = array(
            'id'      => 'comm_fourn',
            'title'   => 'Commandes fournisseurs',
            'icon'    => 'fas_cart-arrow-down',
            'content' => $this->renderAchatsTab()
        );

        return BimpRender::renderNavTabs($tabs, 'line_' . $this->id . '_synthese');
    }

    public function renderFacAchatSyntheseTab()
    {
        $html = '';

        $total_achats_qty = 0;
        $total_fac_qty = 0;

        $last_achat_date_to = '';
        $last_fac_date_to = '';

        $variable_pu_ht = (int) $this->getData('variable_pu_ht');

        $html .= '<table style="width: 100%">';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td style="width: 50%; padding: 10px; vertical-align: top">';

        // Achats : 
        $html .= '<h3>' . BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . 'Quantités achetées</h3>';
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Période</th>';
        $html .= '<th style="text-align: center">Qté achetée</th>';

        if ($variable_pu_ht) {
            $html .= '<th style="text-align: center">PA HT</th>';
        }

        $html .= '<th style="text-align: center">Qté réceptionnée</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $achats = $this->getCommandesFournData(true, 'data');

        if (!empty($achats)) {
            foreach ($achats as $achat) {
                $html .= '<tr>';
                $html .= '<td>';
                $html .= 'Du <b>' . date('d / m / Y', strtotime($achat['from'])) . '</b> au <b>' . date('d / m / Y', strtotime($achat['to'])) . '</b>';
                if ($achat['is_regul']) {
                    $html .= '<br/><span class="important">(Régularisation)</span>';
                }
                $html .= '</td>';
                $html .= '<td  style="text-align: center">' . $achat['qty'] . '</td>';

                if ($variable_pu_ht) {
                    $html .= '<td  style="text-align: center">' . BimpTools::displayMoneyValue($achat['pa_ht']) . '</td>';
                }

                $html .= '<td  style="text-align: center">';
                $cf_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', $achat['id_line']);
                if (BimpObject::objectLoaded($cf_line)) {
                    $received_qty = (float) $cf_line->getReceivedQty(null, true);
                    $received_class = ($received_qty > 0 ? ($received_qty >= $achat['qty'] ? 'success' : 'warning') : 'danger');
                    $html .= '<span class="badge badge-' . $received_class . '">' . $received_qty . '</span>';
                } else {
                    $html .= '<span class="danger">Inconnue</span>';
                }
                $html .= '</td>';
                $html .= '</tr>';

                $total_achats_qty += $achat['qty'];

                if (!$last_achat_date_to || $achat['to'] > $last_achat_date_to) {
                    $last_achat_date_to = $achat['to'];
                }
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="3">';
            $html .= BimpRender::renderAlerts('Aucun achat effectué', 'warning');
            $html .= '</td>';
            $html .= '</tr>';
        }


        $html .= '</tbody>';
        $html .= '</table>';

        $achat_errors = array();
        $achat_periods_data = $this->getPeriodsToBuyData($achat_errors);
        if (count($achat_errors)) {
            $html .= BimpRender::renderAlerts($achat_errors);
        } else {
            $html .= '<div style="margin: 10px 0; border: 1px solid #DCDCDC; padding: 8px">';
            if ((int) $achat_periods_data['nb_periods_tobuy_max']) {
                $dt = new DateTime($achat_periods_data['date_next_achat']);
                $html .= '<span class="info">Il reste ' . $achat_periods_data['nb_periods_tobuy_max'] . ' période(s) à acheter</span><br/>';
                $html .= 'Prochaine période à acheter : <b>du ' . $dt->format('d / m / Y');
                $dt->add(new DateInterval('P' . (int) $this->getData('achat_periodicity') . 'M'));
                $dt->sub(new DateInterval('P1D'));
                $html .= ' au ' . $dt->format('d / m / Y');
            } else {
                $html .= '<span class="danger">Il ne reste plus aucune période à acheter</span>';
            }
            $html .= '</div>';
        }
        $html .= '</td>';

        $html .= '<td style="width: 50%; padding: 10px; vertical-align: top">';

        // Facturations : 
        $html .= '<h3>' . BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Quantités facturées</h3>';
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Période</th>';
        $html .= '<th style="text-align: center">Qté facturée</th>';
        if ($variable_pu_ht) {
            $html .= '<th style="text-align: center">PU HT</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $facs = $this->getFacturesData(true, 'data');

        if (!empty($facs)) {
            foreach ($facs as $fac) {
                $html .= '<tr>';
                $html .= '<td>';
                $html .= 'Du <b>' . date('d / m / Y', strtotime($fac['from'])) . '</b> au <b>' . date('d / m / Y', strtotime($fac['to'])) . '</b>';
                if ($fac['is_regul']) {
                    $html .= '<br/><span class="important">(Régularisation)</span>';
                }
                $html .= '</td>';
                $html .= '<td  style="text-align: center">' . $fac['qty'] . '</td>';
                if ($variable_pu_ht) {
                    $html .= '<td  style="text-align: center">' . BimpTools::displayMoneyValue($fac['pu_ht']) . '</td>';
                }
                $html .= '</tr>';

                $total_fac_qty += $fac['qty'];

                if (!$last_fac_date_to || $fac['to'] > $last_fac_date_to) {
                    $last_fac_date_to = $fac['to'];
                }
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="3"  style="text-align: center">';
            $html .= BimpRender::renderAlerts('Aucune facturation effectuée', 'warning');
            $html .= '</td>';
            $html .= '</tr>';
        }


        $html .= '</tbody>';
        $html .= '</table>';

        $fac_errors = array();
        $fac_periods_data = $this->getPeriodsToBillData($fac_errors);
        if (count($fac_errors)) {
            $html .= BimpRender::renderAlerts($fac_errors);
        } else {
            $html .= '<div style="margin: 10px 0; border: 1px solid #DCDCDC; padding: 8px">';
            if ((int) $fac_periods_data['nb_periods_tobill_max']) {
                $dt = new DateTime($fac_periods_data['date_next_period_tobill']);
                $html .= '<span class="info">Il reste ' . $fac_periods_data['nb_periods_tobill_max'] . ' période(s) à facturer</span><br/>';
                $html .= 'Prochaine période facturée : <b>du ' . $dt->format('d / m / Y');
                $dt->add(new DateInterval('P' . (int) $this->getData('fac_periodicity') . 'M'));
                $dt->sub(new DateInterval('P1D'));
                $html .= ' au ' . $dt->format('d / m / Y');
            } else {
                $html .= '<span class="danger">Il ne reste plus aucune période à facturer</span>';
            }
            $html .= '</div>';
        }
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '<table class="bimp_list_table" style="margin-top: 30px; width: auto">';
        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<th>';
        $html .= 'Total qté achetée' . ($last_achat_date_to ? ' (au ' . date('d / m / Y', strtotime($last_achat_date_to)) . ')' : '');
        $html .= '</th>';
        $html .= '<td style="text-align: center">';
        $html .= '<b>' . $total_achats_qty . '</b>';
        $html .= '</td>';
        $html .= '<td></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>';
        $html .= 'Total qté facturée' . ($last_fac_date_to ? ' (au ' . date('d / m / Y', strtotime($last_fac_date_to)) . ')' : '');
        $html .= '</th>';
        $html .= '<td style="text-align: center">';
        $html .= '<b>' . $total_fac_qty . '</b>';
        $html .= '</td>';
        $html .= '<td></td>';
        $html .= '</tr>';

        if ($total_achats_qty != $total_fac_qty) {
            $html .= '<tr>';
            if ($total_achats_qty > $total_fac_qty) {
                $html .= '<th>Qté achetée non facturée</th>';
            } else {
                $html .= '<th>Surplus facturé</th>';
            }

            $html .= '<td style="text-align: center"><span class="badge badge-important">' . abs($total_achats_qty - $total_fac_qty) . '</span></td>';
            $html .= '<td style="text-align: center">';
            if ($this->isActionAllowed('regul') && $this->canSetAction('regul')) {
                $onclick = $this->getJsActionOnclick('facRegul', array(), array(
                    'form_name' => 'fac_regul'
                ));
                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_file-medical', 'iconLeft') . 'Facture de régularisation';
                $html .= '</span>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
//        if ($total_fac_qty != $total_achats_qty) {
//            $html .= '<tr>';
//            $html .= '<th>Qté facturée non achetée</th>';
//            $html .= '<td style="text-align: center"><span class="badge badge-important">' . ($total_fac_qty - $total_achats_qty) . '</span></td>';
//            $html .= '<td style="text-align: center">';
////                if ($this->isActionAllowed('regul') && $this->canSetAction('regul')) {
////                    $onclick = $this->getJsActionOnclick('facRegul', array(), array(
////                        'form_name' => 'fac_regul'
////                    ));
////                    $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
////                    $html .= BimpRender::renderIcon('fas_file-medical', 'iconLeft') . 'Facture de régularisation';
////                    $html .= '</span>';
////                }
//            $html .= '</td>';
//            $html .= '</tr>';
//        }

        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    public function renderFacturesTab($id_facture = 0, $with_totals = true)
    {
        $html = '';

        $errors = array();
        $fac_lines = $this->getFacturesLines($id_facture, $errors, false);
        $fac_lines_regul = $this->getFacturesLines($id_facture, $errors, true);

        if (!empty($errors)) {
            $html .= BimpRender::renderAlerts($errors, 'danger');
        } elseif (empty($fac_lines) && empty($fac_lines_regul)) {
            $html .= BimpRender::renderAlerts('Aucune facturation effectuée', 'warning');
        } else {
            $headers = array(
                'period'      => 'Période',
                'facture'     => 'Facture',
                'qty'         => 'Qté',
                'total_ht'    => 'Total HT',
                'total_ttc'   => 'Total TTC',
                'date_create' => 'Créée le',
                'user_create' => 'Créée par'
            );

            $total_ht = 0;
            $total_ttc = 0;
            $total_qty = 0;

            $total_regul_ht = 0;
            $total_regul_ttc = 0;
            $total_regul_qty = 0;

            foreach (array(0, 1) as $regul) {
                if ($regul) {
                    $lines = $fac_lines_regul;
                } else {
                    $lines = $fac_lines;
                }

                if (empty($lines)) {
                    continue;
                }

                $rows = array();

                foreach ($lines as $fac_line) {
                    $facture = $fac_line->getParentInstance();

                    if (BimpObject::objectLoaded($facture)) {
                        $period = 'Du ';
                        if ($fac_line->date_from) {
                            $period .= '<b>' . date('d / m / Y', strtotime($fac_line->date_from)) . '</b>';
                        } else {
                            $period .= '<span class="danger">Date de début non définie</span>';
                        }

                        $period .= ' au ';
                        if ($fac_line->date_to) {
                            $period .= '<b>' . date('d / m / Y', strtotime($fac_line->date_to)) . '</b>';
                        } else {
                            $period .= '<span class="danger">Date de fin non définie</span>';
                        }

                        $user_author = $facture->getChildObject('user_author');

                        if ($regul) {
                            $total_regul_ht += $fac_line->getTotalHT();
                            $total_regul_ttc += $fac_line->getTotalTTC();
                            $total_regul_qty += $fac_line->getFullQty();
                        } else {
                            $total_ht += $fac_line->getTotalHT();
                            $total_ttc += $fac_line->getTotalTTC();
                            $total_qty += $fac_line->getFullQty();
                        }

                        $rows[] = array(
                            'period'      => $period,
                            'facture'     => $facture->getLink() . '&nbsp;&nbsp;' . $facture->displayDataDefault('fk_statut'),
                            'qty'         => $fac_line->displayLineData('qty'),
                            'total_ht'    => $fac_line->displayLineData('total_ht'),
                            'total_ttc'   => $fac_line->displayLineData('total_ttc'),
                            'date_create' => $facture->displayDataDefault('datec'),
                            'user_create' => (BimpObject::objectLoaded($user_author) ? $user_author->getLink() : '')
                        );
                    }
                }

                if (!empty($rows)) {
                    if ($regul) {
                        $html .= '<h3>Factures de régularisation</h3>';
                    } else {
                        $html .= '<h3>Factures régulières</h3>';
                    }
                    $html .= BimpRender::renderBimpListTable($rows, $headers);
                }
            }

            if ($with_totals) {
                $html .= '<table style="margin-top: 30px; width: ' . ($total_regul_ht ? '600px' : '300px') . ';" class="bimp_list_table">';
                if ($total_regul_ht > 0) {
                    $html .= '<thead>';
                    $html .= '<tr>';
                    $html .= '<th></th>';
                    $html .= '<th>Factures régulières</th>';
                    $html .= '<th>Régularisations</th>';
                    $html .= '<th>Total</th>';
                    $html .= '</tr>';
                    $html .= '</thead>';
                }
                $html .= '<tbody class="headers_col">';
                $html .= '<tr>';
                $html .= '<th>Total Qté facturée</th>';
                $html .= '<td>' . BimpTools::displayFloatValue($total_qty, 6, ',', 0, 0, 0, 0, 1, 1) . '</td>';

                if ($total_regul_ht > 0) {
                    $html .= '<td>' . BimpTools::displayFloatValue($total_regul_qty, 6, ',', 0, 0, 0, 0, 1, 1) . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($total_qty + $total_regul_qty, 6, ',', 0, 0, 0, 0, 1, 1) . '</td>';
                }

                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<th>Total HT Facturé</th>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_ht) . '</td>';

                if ($total_regul_ht > 0) {
                    $html .= '<td>' . BimpTools::displayMoneyValue($total_regul_ht) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($total_ht + $total_regul_ht) . '</td>';
                }
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<th>Total TTC Facturé</th>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc) . '</td>';

                if ($total_regul_ht > 0) {
                    $html .= '<td>' . BimpTools::displayMoneyValue($total_regul_ttc) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc + $total_regul_ttc) . '</td>';
                }

                $html .= '</tr>';
                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function renderAchatsTab($id_commande_fourn = 0, $with_totals = true)
    {
        $html = '';

        $errors = array();
        $cf_lines = $this->getCommandesFournLines($id_commande_fourn, $errors);

        if (!empty($errors)) {
            $html .= BimpRender::renderAlerts($errors, 'danger');
        } elseif (empty($cf_lines)) {
            $html .= BimpRender::renderAlerts('Aucun achat effectué', 'warning');
        } else {
            $headers = array(
                'period'      => 'Période',
                'facture'     => 'Commande fournisseur',
                'qty'         => 'Qté',
                'received'    => 'Qté réceptionnée',
                'total_ht'    => 'Total HT',
                'total_ttc'   => 'Total TTC',
                'date_create' => 'Créée le',
                'user_create' => 'Créée par'
            );

            $rows = array();

            $total_ht = 0;
            $total_ttc = 0;

            foreach ($cf_lines as $cf_line) {
                $cf = $cf_line->getParentInstance();

                if (BimpObject::objectLoaded($cf)) {
                    $period = 'Du ';
                    if ($cf_line->date_from) {
                        $period .= '<b>' . date('d / m / Y', strtotime($cf_line->date_from)) . '</b>';
                    } else {
                        $period .= '<span class="danger">Date de début non définie</span>';
                    }

                    $period .= ' au ';
                    if ($cf_line->date_to) {
                        $period .= '<b>' . date('d / m / Y', strtotime($cf_line->date_to)) . '</b>';
                    } else {
                        $period .= '<span class="danger">Date de fin non définie</span>';
                    }

                    $user_author = $cf->getChildObject('user_author');

                    $total_ht += $cf_line->getTotalHT();
                    $total_ttc += $cf_line->getTotalTTC();

                    $received_qty = (float) $cf_line->getReceivedQty(null, true);
                    $received_class = ($received_qty > 0 ? ($received_qty >= $cf_line->getFullQty() ? 'success' : 'warning') : 'danger');

                    $rows[] = array(
                        'period'      => $period,
                        'facture'     => $cf->getLink() . '<br/>' . $cf->displayDataDefault('fk_statut'),
                        'qty'         => $cf_line->displayLineData('qty'),
                        'received'    => '<span class="badge badge-' . $received_class . '">' . $received_qty . '</span>',
                        'total_ht'    => $cf_line->displayLineData('total_ht'),
                        'total_ttc'   => $cf_line->displayLineData('total_ttc'),
                        'date_create' => $cf->displayDataDefault('date_creation'),
                        'user_create' => (BimpObject::objectLoaded($user_author) ? $user_author->getLink() : '')
                    );
                }
            }

            $html .= BimpRender::renderBimpListTable($rows, $headers);

            if ($with_totals) {
                $html .= '<table style="margin-top: 30px; width: 300px;" class="bimp_list_table">';
                $html .= '<tbody class="headers_col">';
                $html .= '<tr>';
                $html .= '<th>Total HT achats</th>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_ht) . '</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<th>Total TTC achats</th>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc) . '</td>';
                $html .= '</tr>';
                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function renderLinkedLinesCheckInputs($type_action)
    {
        $html = '';

        $errors = array();

        if (!in_array($type_action, array('renouv', 'setResiliateDate'))) {
            $errors[] = 'Type d\'action invalide';
        } else {
            if ($this->isLoaded($errors)) {
                $linked_lines_filter = array($this->id);
                $id_linked_line = (int) $this->getData('id_linked_line');
                if ($id_linked_line) {
                    $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_linked_line);
                    if (!BimpObject::objectLoaded($line)) {
                        $line = $this;
                    } else {
                        $linked_lines_filter[] = $id_linked_line;
                        if ((int) $line->getData('fk_product') !== (int) $this->getData('fk_product')) {
                            $line = $this;
                        }
                    }
                } else {
                    $line = $this;
                }

                $prod = $line->getChildObject('product');
                $prod_duration = 0;
                if (BimpObject::objectLoaded($prod)) {
                    $prod_duration = (int) $prod->getData('duree');
                }
                if (!$prod_duration) {
                    $prod_duration = 1;
                }

                $duration = (int) $line->getData('duration');

                if (!$duration) {
                    $errors[] = 'Durée non définie';
                }

                if (!count($errors)) {
                    $html .= '<input type="hidden" name="id_main_line" value="' . $line->id . '"/>';
                    $html .= '<table class="bimp_list_table">';
                    $html .= '<thead>';
                    $html .= '<tr>';
                    $html .= '<th style="width: 45px"></th>';
                    $html .= '<th>Ligne n°</th>';
                    $html .= '<th>Statut</th>';
                    $html .= '<th>Nombre d\'unités</th>';
                    $html .= '<th></th>';
                    $html .= '</tr>';
                    $html .= '</thead>';

                    $html .= '<tbody>';

                    $action_err = array();
                    $allowed = $line->isActionAllowed($type_action, $action_err);

                    $html .= '<tr>';
                    $html .= '<td style="width: 45px">';
                    if ($allowed) {
                        $html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check" checked="1" data-id_line="' . $line->id . '"/>';
                    }
                    $html .= '</td>';

                    $html .= '<td>' . $line->getData('rang') . ' (principale)</td>';

                    $html .= '<td>' . $line->displayDataDefault('statut') . '</td>';

                    $nb_units = ($line->getData('qty') / $duration) * $prod_duration;
                    $html .= '<td>' . $nb_units . '</td>';

                    $html .= '<td>';
                    if (count($action_err)) {
                        $html .= BimpRender::renderAlerts($action_err, 'warning');
                    }
                    $html .= '</td>';
                    $html .= '</tr>';

                    $sub_lines = BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                                'fk_contrat'     => $line->getData('fk_contrat'),
                                'fk_product'     => $line->getData('fk_product'),
                                'id_linked_line' => $linked_lines_filter,
                                'rowid'          => array('operator' => '!=', 'value' => $line->id)
                                    ), 'rang');

                    foreach ($sub_lines as $sub_line) {
                        $action_err = array();
                        $allowed = $sub_line->isActionAllowed($type_action, $action_err);

                        $html .= '<tr>';
                        $html .= '<td style="width: 45px">';
                        if ($allowed) {
                            $html .= '<input type="checkbox" name="line_' . $sub_line->id . '_check" class="line_check" checked="1" data-id_line="' . $sub_line->id . '"/>';
                        }
                        $html .= '</td>';

                        $html .= '<td>' . $sub_line->getData('rang') . '</td>';

                        $html .= '<td>' . $sub_line->displayDataDefault('statut') . '</td>';

                        $nb_units = ($sub_line->getData('qty') / (int) $sub_line->getData('duration')) * $prod_duration;
                        $html .= '<td>' . $nb_units . '</td>';

                        $html .= '<td>';
                        if (count($action_err)) {
                            $html .= BimpRender::renderAlerts($action_err, 'warning');
                        }
                        $html .= '</td>';

                        $html .= '</tr>';
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';
                }
            }
        }


        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    public function renderRegulFactureSelect()
    {
        $id_fac = 0;

        $factures = array(
            0 => 'Nouvelle facture'
        );

        $contrat = $this->getParentInstance();

        $id_client = (int) $contrat->getData('fk_soc_facturation');

        if (!$id_client) {
            $id_client = (int) $contrat->getData('fk_soc');
        }

        if (BimpObject::objectLoaded($contrat)) {
            foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array(
                'a.fk_soc'      => $id_client,
                'a.fk_statut'   => 0,
                'fef.entrepot'  => (int) $contrat->getData('entrepot'),
                'fef.expertise' => (int) $contrat->getData('expertise'),
                'fef.type'      => $contrat->getData('secteur'),
                    ), 'rowid', 'desc', array(
                'fef' => array(
                    'table' => 'facture_extrafields',
                    'on'    => 'fef.fk_object = a.rowid'
                )
            )) as $fac) {
                if (!$id_fac) {
                    $id_fac = $fac->id;
                }
                $factures[(int) $fac->id] = $fac->getRef() . ' - ' . $fac->getName();
            }
        }

        return BimpInput::renderInput('select', 'id_fac_regul', $id_fac, array(
                    'options' => $factures
        ));
    }

    public function renderFacRegulFromSelect()
    {
        $from = '';
        $facs = $this->getFacturesData();

        $options = array();

        foreach ($facs as $data) {
            if (!$from || $data['from'] < $from) {
                $from = $data['from'];
            }

            $options[$data['from']] = date('d / m / Y', strtotime($data['from']));
        }

        ksort($options);

        return BimpInput::renderInput('select', 'period_from', $from, array(
                    'options' => $options
        ));
    }

    public function renderFacRegulToSelect()
    {
        $to = '';
        $facs = $this->getFacturesData();

        $options = array();

        foreach ($facs as $data) {
            if (!$to || $data['to'] > $to) {
                $to = $data['to'];
            }

            $options[$data['to']] = date('d / m / Y', strtotime($data['to']));
        }

        ksort($options);

        return BimpInput::renderInput('select', 'period_to', $to, array(
                    'options' => $options
        ));
    }

    public function renderFacRegulQtyInput()
    {
        $html = '';

        $period_to = BimpTools::getPostFieldValue('period_to', '', 'date');

        $errors = array();

        if (!$period_to) {
            $errors[] = 'Veuillez sélectionner une fin de période max à régulariser';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        } else {
            $facs_data = $this->getFacturesData(true);
            $achats_data = $this->getCommandesFournData(true);

            $qty_fac = 0;
            $qty_achat = 0;

            $html .= '<b>Facturations sur la période sélectionnée : </b><br/>';
            if (!empty($facs_data)) {
                foreach ($facs_data as $fac_data) {
                    if ($fac_data['qty'] && $fac_data['from'] <= $period_to) {
                        $qty_fac += $fac_data['qty'];

                        $html .= ' - Du ' . date('d / m / Y', strtotime($fac_data['from'])) . ' au ' . date('d / m / Y', strtotime($fac_data['to']));

                        if ($fac_data['is_regul']) {
                            $html .= ' <span class="important">[REGUL]</span>';
                        }

                        $html .= ' : ' . $fac_data['qty'] . '<br/>';
                    }
                }
            }

            if (!$qty_fac) {
                $html .= '<span class="warning">Aucune facturation effectuée</span><br/>';
            }

            $html .= '<br/><b>Achats sur la période sélectionnée : </b><br/>';
            if (!empty($achats_data)) {
                foreach ($achats_data as $achat_data) {
                    if ($achat_data['qty'] && $achat_data['from'] <= $period_to) {
                        $qty_achat += (float) $achat_data['qty'];

                        $html .= ' - Du ' . date('d / m / Y', strtotime($achat_data['from'])) . ' au ' . date('d / m / Y', strtotime($achat_data['to']));

                        if ($achat_data['is_regul']) {
                            $html .= '<span class="important">[REGUL]</span> ';
                        }

                        $html .= ' : ' . $achat_data['qty'] . '<br/>';
                    }
                }
            }

            if (!$qty_achat) {
                $html .= '<span class="warning">Aucun achat effectué</span><br/>';
            }

            $html .= '<div style="margin: 15px 0; padding: 8px; border: 1px solid #DCDCDC">';
            $html .= 'Quantité totale achetée : <b>' . $qty_achat . '</b><br/>';
            $html .= 'Quantité totale facturée : <b>' . $qty_fac . '</b><br/>';
            $html .= 'Différence : <b>' . ($qty_achat - $qty_fac) . '</b>';
            $html .= '</div>';

            $html .= '<br/>Quantité à régulariser : <br/>';
            $html .= BimpInput::renderInput('qty', 'regul_qty', $qty_achat - $qty_fac, array(
                        'data' => array(
                            'data_type' => 'number',
                            'decimals'  => 6
                        )
            ));
        }
        return $html;
    }

    public function renderClotureDateInput()
    {
        $html = '';

        $errors = array();

        if ($this->isLoaded($errors)) {
            $fac_periodicity = (int) $this->getData('fac_periodicity');

            if (!$fac_periodicity) {
                $errors[] = 'périodicité de facturation absente';
            } else {
                $period_data = $this->getPeriodsToBillData($errors);

                if (!count($errors)) {
                    $dates = array();

                    $date_cloture = $this->getData('date_cloture');
                    if ($date_cloture) {
                        $dates[''] = 'Annuler la résiliation';
                    }

                    $dt = new DateTime($period_data['date_next_period_tobill']);
                    $dates[$dt->format('Y-m-d')] = $dt->format('d / m / Y') . ' (0 période restant à facturer)';

                    if ($date_cloture) {
                        $value = date('Y-m-d', strtotime($date_cloture));
                    } else {
                        $value = $dt->format('Y-m-d');
                    }

                    $nb_periods_to_bill = $period_data['nb_periods_tobill_max'] + $period_data['nb_periods_never_billed'];

                    if ($nb_periods_to_bill > 0) {
                        $interval = new DateInterval('P' . $fac_periodicity . 'M ');
                        for ($i = 1; $i <= $nb_periods_to_bill; $i++) {
                            $dt->add($interval);
                            $dates[$dt->format('Y-m-d')] = $dt->format('d / m / Y') . ' (' . $i . ' période' . ($i > 1 ? 's' : '') . ' restant à facturer)';
                        }
                    }

                    $html .= BimpInput::renderInput('select', 'date_cloture', $value, array(
                                'options' => $dates
                    ));
                }
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    public function renderAddUnitsInputs()
    {
        $errors = array();
        $html = '';

        $id_lines = BimpTools::getPostFieldValue('id_objects', array(), 'array');

        if (empty($id_lines)) {
            $errors[] = 'Aucune ligne sélectionnée';
        } else {
            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Ligne</th>';
            $html .= '<th style="text-align: center">Unités à ajouter / retirer</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            foreach ($id_lines as $id_line) {
                $line_errors = array();
                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

                $line_label = '';
                if (BimpObject::objectLoaded($line)) {
                    $id_linked_line = (int) $line->getData('id_linked_line');
                    if ($id_linked_line && in_array($id_linked_line, $id_lines)) {
                        $linked_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_linked_line);
                        if (BimpObject::objectLoaded($linked_line) && (int) $linked_line->getData('fk_product') === (int) $line->getData('fk_product')) {
                            continue;
                        }
                    }

                    $line->isActionAllowed('addUnits', $line_errors);

                    $extra_lines = array();

                    $rows = $this->db->getRows('contratdet', 'id_linked_line = ' . $line->id . ' AND fk_product = ' . (int) $line->getData('fk_product'), null, 'array', array('rowid', 'rang'));
                    if (is_array($rows)) {
                        foreach ($rows as $r) {
                            if (in_array((int) $r['rowid'], $id_lines)) {
                                $extra_lines[] = $r['rang'];
                            }
                        }
                    } else {
                        die($this->db->err());
                    }

                    if (empty($extra_lines)) {
                        $line_label = 'Ligne n° ' . $line->getData('rang');
                    } else {
                        $line_label = 'Lignes n° ' . $line->getData('rang') . ', ' . implode(', ', $extra_lines);
                    }

                    $prod = $line->getChildObject('product');
                    if (BimpObject::objectLoaded($prod)) {
                        $line_label .= '<br/>' . $prod->getLink();
                        $line_label .= '<br/>' . $prod->getName();
                    }
                } else {
                    $line_errors[] = 'cette ligne n\'existe plus';
                    $line_label = 'Ligne #' . $id_line;
                }

                $html .= '<tr>';
                $html .= '<td>' . $line_label . '</td>';

                if (count($line_errors)) {
                    $html .= '<td>';
                    $html .= '<span class="danger">' . BimpTools::getMsgFromArray($line_errors) . '</span>';
                    $html .= '</td>';
                } else {
                    $html .= '<td style="text-align: center">';
                    $html .= BimpInput::renderInput('qty', 'line_' . $id_line . '_nb_units', 1, array(
                                'extra_class' => 'line_nb_units',
                                'data'        => array(
                                    'id_line'  => $line->id,
                                    'decimals' => 0
                                )
                    ));
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }
        return $html;
    }

    public function renderMoveToOtherContratInput()
    {
        $html = '';

        $id_contrat = (int) BimpTools::getPostFieldValue('id_contrat_src', 0);

        if ($id_contrat) {
            $id_client = (int) $this->db->getValue('contrat', 'fk_soc', 'rowid = ' . $id_contrat);

            if ($id_client) {
                $where = 'fk_soc = ' . $id_client . ' AND version = 2 AND rowid != ' . $id_contrat;
                $rows = $this->db->getRows('contrat', $where, null, 'array', array(
                    'rowid',
                    'ref',
                    'label'
                        ), 'rowid', 'desc');

                $contrats = array();

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $label = 'Contrat ' . $r['ref'];

                        if ($r['label']) {
                            $label .= ' - ' . $r['label'];
                        }

                        $contrats[(int) $r['rowid']] = $label;
                    }

                    $html .= BimpInput::renderInput('select', 'id_contrat_dest', '', array(
                                'options' => $contrats
                    ));
                }
            } else {
                $html .= BimpRender::renderAlerts('ID du client absent');
            }
        } else {
            $html .= BimpRender::renderAlerts('ID du contrat absent');
        }

        return $html;
    }

    // Traitements:

    public function checkLinkedLine(&$errors = array(), $update = false)
    {
        $check = true;
        $id_linked_line = (int) $this->getData('id_linked_line');
        if ($id_linked_line) {
            $linked_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_linked_line);

            if (BimpObject::objectLoaded($linked_line)) {
                if ((int) $this->getData('fk_contrat') !== (int) $linked_line->getData('fk_contrat')) {
                    $errors[] = 'La ligne liée n\'appartient pas au même contrat';
                    $check = false;
                } else {
                    $this->set('fac_periodicity', $linked_line->getData('fac_periodicity'));
                    $this->set('achat_periodicity', $linked_line->getData('achat_periodicity'));
                    $this->set('duration', $linked_line->getData('duration'));
                    $this->set('variable_qty', $linked_line->getData('variable_qty'));
                    $this->set('nb_renouv', $linked_line->getData('nb_renouv'));
                    $this->set('fac_term', $linked_line->getData('fac_term'));

                    if ($update) {
                        $warnings = array();
                        $this->update($warnings, true);
                    }
                }
            } else {
                $errors[] = 'La ligne de contrat d\'abonnement liée #' . $id_linked_line . ' n\'existe plus';
                $check = false;
            }
        }

        return $check;
    }

    public function fetchDataAtDates()
    {
        $this->data_at_date = array();
    }

    public function reset()
    {
        parent::reset();

        $this->data_at_date = null;
    }

    public function onSave(&$errors = [], &$warnings = [])
    {
        $this->hydrateFromDolObject();

        parent::onSave($errors, $warnings);

        if (!count($errors)) {
            $this->majBundle($errors, $warnings);
            $this->checkStatus();

            $lines = BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                        'id_linked_line' => $this->id
            ));

            if (!empty($lines)) {
                foreach ($lines as $line) {
                    $err = array();
                    $line->checkLinkedLine($err, true);
                }
            }
        }
    }

    public function activate($date_ouverture = null)
    {
        $errors = array();

        switch ($this->getData('line_type')) {
            case self::TYPE_ABO:
                if (!$date_ouverture) {
                    $date_ouverture = $this->getData('date_ouverture');

                    if (!$date_ouverture) {
                        $date_ouverture = $this->getData('date_ouverture_prevue');
                    }
                }

                if ($date_ouverture) {
                    $date_ouverture = date('Y-m-d 00:00:00', strtotime($date_ouverture));
                } else {
                    $errors[] = 'Date d\'ouverture non définie';
                }

                if (!count($errors)) {
                    if ((int) $this->getData('id_linked_line')) {
                        if ($this->checkLinkedLine($errors)) {
                            $linked_line = $this->getChildObject('linked_line');

                            if ((int) $linked_line->getData('statut') !== self::STATUS_ACTIVE) {
                                $errors[] = 'La ligne d\'abonnement liée n\'est pas active. Il n\'est pas possible d\'activer cet abonnement';
                            } else {
                                $line_errors = $linked_line->validate();
                                if (count($line_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'La ligne d\'abonnement liée contient des erreurs');
                                } else {
                                    $date_fac_start = date('Y-m-d', strtotime($date_ouverture));
                                    $date_debut = $linked_line->getData('date_ouverture');

                                    if (!$date_debut) {
                                        $date_debut = $linked_line->getData('date_debut_validite');
                                    }

                                    if ($date_debut) {
                                        $date_debut = date('Y-m-d', strtotime($date_debut));
                                    }
                                    $date_fin = $linked_line->getData('date_fin_validite');

//                                    if ($date_fac_start < $date_debut) {
//                                        $errors[] = 'La date de début des facturations (' . date('d / m / Y', strtotime($date_fac_start)) . ') ne peut pas être inférieure à la date de début de l\'abonnement lié (' . date('d / m / Y', strtotime($date_debut)) . ')';
//                                    }
                                    if ($date_ouverture > $date_fin) {
                                        $errors[] = 'La date d\'ouverture ne peut pas être supérieure à la date de fin de validité de l\'abonnement lié';
                                    }
                                }
                            }

                            if (!count($errors)) {
                                $this->set('date_ouverture', date('Y-m-d 00:00:00', strtotime($date_ouverture)));
                                $this->set('date_debut_validite', $date_debut);
                                $this->set('date_fin_validite', date('Y-m-d 23:59:59', strtotime($date_fin)));
                                $this->set('date_fac_start', $date_fac_start);
                                $this->set('date_achat_start', $date_fac_start);
                            }
                        }
                    } else {
                        $date_debut = $date_ouverture;
                        $this->set('date_ouverture', date('Y-m-d 00:00:00', strtotime($date_ouverture)));

                        if (!(int) $this->getData('duration')) {
                            $errors[] = 'Durée de l\'abonnement non définie';
                        }

                        $dt = new DateTime($date_ouverture);

                        if (!$this->getData('date_fac_start') || $this->getData('date_fac_start') < $date_ouverture) {
                            $this->set('date_fac_start', $dt->format('Y-m-d'));
                        } else {
                            $date_debut = $this->getData('date_fac_start');
                        }

                        if (!$this->getData('date_achat_start') || $this->getData('date_achat_start') < $date_ouverture) {
                            $this->set('date_achat_start', $dt->format('Y-m-d'));
                        }

                        $dt->add(new DateInterval('P' . $this->getData('duration') . 'M'));
                        $dt->sub(new DateInterval('P1D'));
                        $this->set('date_fin_validite', $dt->format('Y-m-d 23:59:59'));

                        $this->set('date_debut_validite', date('Y-m-d', strtotime($date_debut)));
                    }
                }
                break;

            default:
                $errors[] = 'Ce type de ligne ne nécessite pas d\'activation';
                break;
        }

        if (!count($errors)) {
            global $user;
            $this->set('fk_user_ouverture', $user->id);
            $this->set('statut', self::STATUS_ACTIVE);

            $warnings = array();
            $errors = $this->update($warnings, true);

            $this->getDateNextFacture(true);
            $this->getDateNextAchat(true);

            if (!count($errors)) {
                $sub_lines = BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                            'id_parent_line' => $this->id
                ));

                if (!empty($sub_lines)) {
                    foreach ($sub_lines as $sub_line) {
                        $sub_line_errors = $sub_line->activate($date_ouverture);

                        if (count($sub_line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($sub_line_errors, 'Echec de l\'activation de la sous-ligne n° ' . $sub_line->getData('rang'));
                        }
                    }
                }

                if (!count($errors)) {
                    $contrat = $this->getParentInstance();
                    if (BimpObject::objectLoaded($contrat)) {
                        $msg = 'Abonnement {{Produit:' . $this->getData('fk_product') . '}} activé.<br/>';
                        $msg .= 'Date d\'ouverture effective : ' . date('d / m / Y', strtotime($this->getData('date_ouverture'))) . '.<br/>';
                        $msg .= 'Durée: ' . $this->getData('duration') . ' mois';
                        $contrat->addObjectLog($msg);
                    }
                }
            }
        }

        return $errors;
    }

    public function onFactureValidated($fac_line, &$success = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $qty = $fac_line->qty;

            if ($qty) {
                $product = $this->getChildObject('product');
                if (BimpObject::objectLoaded($product) && $product->isTypeProduct()) {
                    $contrat = $this->getParentInstance();
                    if (!BimpObject::objectLoaded($contrat)) {
                        $errors[] = 'Contrat absent';
                    } elseif ((int) $contrat->getData('version') !== 2) {
                        return array();
                    }

                    $id_entrepot = (int) BimpCore::getConf('abos_id_entrepot', null, 'bimpcontrat');

                    if (!$id_entrepot) {
                        $id_entrepot = (int) $contrat->getData('entrepot');
                    }

                    if (!$id_entrepot) {
                        $errors[] = 'Aucun entrepôt défini pour le contrat ' . $contrat->getLink();
                    } else {
                        $label = 'Facturation contrat ' . $contrat->getRef() . ' - Ligne #' . $this->id;
                        $code_mvt = 'BCT' . $contrat->id . '_LN' . $this->id . '_FACLN' . $fac_line->id;

                        if ((int) $this->db->getCount('stock_mouvement', 'inventorycode = \'' . $code_mvt . '\'', 'rowid') > 0) {
                            $errors[] = 'Stock déjà traité';
                        } else {
//                        $done_qty = (float) $this->getData('remain_stock_done');
//
//                        if ($done_qty > 0) {
//                            $diff = $done_qty - $qty;
//
//                            if ($diff >= 0) {
//                                $this->updateField('remain_stock_done', $diff);
//                            } else {
//                                $this->updateField('remain_stock_done', 0);
//                                $errors = $product->correctStocks($id_entrepot, abs($diff), 1, $label, $code_mvt, 'contrat_line', $this->id);
//                            }
//                        } else {
                            $mvt = 1;
                            if ($qty < 0) {
                                $mvt = 0;
                            }
                            $errors = $product->correctStocks($id_entrepot, abs($qty), $mvt, $code_mvt, $label, 'bimp_contrat', $contrat->id);

                            if (!count($errors)) {
                                if ($qty > 0) {
                                    $success = 'Retrait de ' . $qty . ' unité(s) du stock effectué';
                                } else {
                                    $success = 'Ajout de ' . abs($qty) . ' unité(s) au stock effectué';
                                }
                            }
//                        }                            
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function onFactureDelete($id_facture)
    {
        // Vérif date next facture et statut: 
        $this->getDateNextFacture(true);
        $this->checkStatus();
    }

    public function onLinkedCommandeFournLineDelete()
    {
        // Vérif date next achat et statut: 
        $this->getDateNextAchat(true);
        $this->checkStatus();
    }

    public function checkStatus(&$infos = array())
    {
        if ($this->isLoaded()) {
            $status = (int) $this->getData('statut');

            if ($status > 0) {
                $new_status = 4;
                $date_fin_validite = $this->getData('date_fin_validite');

                if ($date_fin_validite) {
                    // Vérif facturation terminée: 
                    $fac_ended = true;
                    if ((int) $this->getData('fac_periodicity')) {
                        $fac_data = $this->getPeriodsToBillData();
                        if ($fac_data['date_next_period_tobill'] <= $date_fin_validite) {
                            $fac_ended = false;
                        }
                    }

                    if ((int) $fac_ended !== (int) $this->getData('fac_ended')) {
                        $this->updateField('fac_ended', (int) $fac_ended);
                        $infos[] = 'Facturations terminées';
                    }

                    // Vérif achats terminés: 
                    $achat_ended = true;
                    if ((int) $this->getData('achat_periodicity')) {
                        $fac_data = $this->getPeriodsToBuyData();
                        if ($fac_data['date_next_achat'] <= $date_fin_validite) {
                            $achat_ended = false;
                        }
                    }

                    if ((int) $achat_ended !== (int) $this->getData('achat_ended')) {
                        $this->updateField('achat_ended', (int) $achat_ended);
                        $infos[] = 'Achats terminés';
                    }

                    if ($fac_ended && $achat_ended && $date_fin_validite < date('Y-m-d') . ' 00:00:00') {
                        $new_status = 5;
                    }

                    if ($new_status != $status) {
                        $errors = $this->updateField('statut', $new_status);

                        if (count($errors)) {
                            $infos[] = 'Echec màj statut (' . $new_status . ') - <pre>' . print_r($errors, 1) . '</pre>';
                        } else {
                            $infos[] = 'Màj statut (' . $new_status . ')';
                        }
                    }
                }
            }
        }
    }

    public function majBundle(&$errors = array(), &$warnings = array())
    {
        global $no_bundle_lines_process;

        if ($no_bundle_lines_process) {
            return;
        }

        if ((int) $this->getData('fk_product')) {
            $product = $this->getChildObject('product');
            if (BimpObject::objectLoaded($product) && $product->isBundle()) {
                $fieldsCopy = array('fk_contrat', 'line_type', 'fac_periodicity', 'fac_term', 'duration', 'nb_renouv', 'date_ouverture_prevue', 'date_fac_start', 'date_achat_start');

                //on ajoute les sous lignes et calcule le tot
                $bundle_total_ht = $this->getData('total_ht');
                $qty = $this->getData('qty');
                $totPa = 0;
                if ($bundle_total_ht) {
                    $lines_total_ht_sans_remises = 0;

                    $child_prods = $product->getChildrenObjects('child_products');

                    foreach ($child_prods as $child_prod) {
                        $newLn = BimpCache::findBimpObjectInstance($this->module, $this->object_name, array(
                                    'id_parent_line'     => $this->id,
                                    'linked_id_object'   => $child_prod->id,
                                    'linked_object_name' => 'bundle'
                                        ), true, true, true);

                        if (!BimpObject::objectLoaded($newLn)) {
                            $newLn = BimpObject::getInstance('bimpcontrat', 'BCT_ContratLine');
                        }

                        $id_prod = (int) (int) $child_prod->getData('fk_product_fils');

                        $newLn->set('statut', (int) $this->getData('statut'));
                        $newLn->set('qty', $child_prod->getData('qty') * $qty);
                        $newLn->set('fk_product', $id_prod);
                        $newLn->set('id_parent_line', $this->id);
                        $newLn->set('linked_id_object', $child_prod->id);
                        $newLn->set('linked_object_name', 'bundle');

                        if ($id_prod) {
                            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);

                            if (!BimpObject::objectLoaded($newLn) && BimpObject::objectLoaded($prod)) {
                                $newLn->set('subprice', $newLn->getValueForProduct('subprice', $prod));
                                $newLn->set('tva_tx', $newLn->getValueForProduct('tva_tx', $prod));
                                $newLn->set('fk_product_fournisseur_price', $newLn->getValueForProduct('fk_product_fournisseur_price', $prod));
                                $newLn->set('buy_price_ht', $newLn->getValueForProduct('buy_price_ht', $prod));
                            }
                        }

                        foreach ($fieldsCopy as $field) {
                            $newLn->set($field, $this->getData($field));
                        }

                        $line_errors = $line_warnings = array();

                        if (!$newLn->isLoaded()) {
                            $line_errors = $newLn->create($line_warnings, true);
                            if (count($line_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec ajout de la ligne pour le produit ' . $child_prod->getRef());
                                continue;
                            }
                        } else {
                            $line_errors = $newLn->update($line_warnings, true);
                            if (count($line_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec mise à jour de la ligne pour le produit ' . $child_prod->getRef());
                                continue;
                            }
                        }

                        $newLnQty = $newLn->getData('qty');
                        $lines_total_ht_sans_remises += $newLn->getTotalHT(false);

                        $totPa += (float) $newLn->getData('buy_price_ht') * $newLnQty;
                    }

                    if ($lines_total_ht_sans_remises) {
                        $pourcent = 100 - ($bundle_total_ht / $lines_total_ht_sans_remises * 100);

                        if (abs($pourcent) > 0.01) {
                            foreach (BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array('id_parent_line' => $this->id)) as $sub_line) {
                                $sub_line->set('remise_percent', $pourcent);
                                $line_warnings = array();
                                $line_errors = $sub_line->update($line_warnings, true);

                                if (count($line_errors)) {
                                    $prod = $sub_line->getChildObject('product');
                                    $errors[] = BimpTools::getMsgFromArray($line_errors, (BimpObject::objectLoaded($prod) ? 'Produit ' . $prod->getRef() : 'Ligne n° ' . $sub_line->getData('rang')) . ' : échec ajout de la remise du bundle');
                                }
                            }
                        }

                        //ajout de la ligne de compensation
                        $newLn = BimpCache::findBimpObjectInstance($this->module, $this->object_name, array(
                                    'id_parent_line'     => $this->id,
                                    'linked_object_name' => 'bundleCorrect'
                                        ), true, true, true);

                        if (!BimpObject::objectLoaded($newLn)) {
                            $newLn = BimpObject::getInstance('bimpcontrat', 'BCT_ContratLine');
                        }

                        $newLn->set('statut', (int) $this->getData('statut'));
                        $newLn->set('description', 'Annulation double prix Bundle');
                        $newLn->set('qty', (float) $qty);
                        $newLn->set('fk_product', 0);
                        $newLn->set('id_parent_line', $this->id);
                        $newLn->set('linked_id_object', 0);
                        $newLn->set('linked_object_name', 'bundleCorrect');

                        $newLn->set('subprice', -$lines_total_ht_sans_remises / $qty);
                        $newLn->set('tva_tx', $this->getData('tva_tx'));
                        $newLn->set('buy_price_ht', ($qty ? -$totPa / $qty : 0));

                        if (abs($pourcent) > 0.01) {
                            $newLn->set('remise_percent', $pourcent);
                        }

                        foreach ($fieldsCopy as $field) {
                            $newLn->set($field, $this->getData($field));
                        }

                        $line_warnings = array();
                        if (!$newLn->isLoaded()) {
                            $line_errors = $newLn->create($line_warnings, true);
                            if (count($line_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec ajout de la ligne d\'annulation du double prix du bundle');
                            }
                        } else {
                            $line_errors = $newLn->update($line_warnings, true);
                            if (count($line_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec mise à jour de la ligne d\'annulation du double prix du bundle');
                            }
                        }

                        if ($qty) {
                            $this->updateField('buy_price_ht', $totPa / $qty);
                        }
                    }
                }
            }
        }
    }

    public function renouvAbonnement($options = array(), &$lines_renouv = array(), &$errors = array(), &$warnings = array(), &$success = '', &$success_callback = '')
    {
        if (!$this->isLoaded($errors)) {
            return;
        }

        $prod = $this->getChildObject('product');
        $options = BimpTools::overrideArray(array(
                    'id_propal'         => -1,
                    'propal_label'      => '',
                    'fac_periodicity'   => (int) $this->getData('fac_periodicity'),
                    'achat_periodicity' => (int) $this->getData('achat_periodicity'),
                    'subprice'          => $this->getValueForProduct('subprice', $prod),
                    'duration'          => (int) $this->getData('duration'),
                    'fac_term'          => (int) $this->getData('fac_term'),
                    'lines'             => array()
                        ), $options);

        $contrat = $this->getParentInstance();
        if (!BimpObject::objectLoaded($contrat)) {
            $errors[] = 'Contrat lié absent';
        }

        if (!BimpObject::objectLoaded($prod)) {
            $errors[] = 'Produit lié absent';
        } else {
            $prod_duration = 0;
            if (BimpObject::objectLoaded($prod)) {
                $prod_duration = (int) $prod->getData('duree');
            }
            if (!$prod_duration) {
                $errors[] = 'Durée unitaire du produit non définie';
            } else {
                if ($options['duration'] % $options['fac_periodicity'] != 0) {
                    $errors[] = 'La durée totale de l\'abonnement doit être un multiple du nombre de mois correspondant à la périodicité de facturation (' . $options['fac_periodicity'] . ' mois)';
                }
                if ($options['duration'] % $prod_duration != 0) {
                    $errors[] = 'La durée totale de l\'abonnement  doit être un multiple de la durée unitaire de produit (' . $prod_duration . ' mois)';
                } elseif ($options['duration'] < $prod_duration) {
                    $errors[] = 'La durée totale de l\'abonnement ne peut pas être inférieure à la durée unitaire de produit (' . $prod_duration . ' mois)';
                }
            }
        }

        if (!count($errors)) {
            $lines = $options['lines'];
            if (empty($lines)) {
                $lines = array($this->id);
                $id_linked_line = $this->id;

                if ((int) $this->getData('id_linked_line')) {
                    $id_linked_line = (int) $this->getData('id_linked_line');
                }

                $where = 'fk_contrat = ' . $contrat->id . ' AND (rowid = ' . $id_linked_line . ' OR id_linked_line = ' . $id_linked_line . ')';
                $where .= ' AND fk_product = ' . $this->getData('fk_product') . ' AND rowid != ' . $this->id . ' AND id_line_renouv = 0';

                $rows = $this->db->getRows('contratdet', $where, null, 'array', array('rowid'));

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $lines[] = (int) $r['rowid'];
                    }
                }
            }

            $mult = $options['duration'] / $prod_duration;
            $qty = 0;

            foreach ($lines as $id_line) {
                $sub_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

                if (!BimpObject::objectLoaded($sub_line)) {
                    $errors[] = 'La ligne #' . $id_line . ' n\'existe plus';
                } else {
                    $sub_line_duration = (int) $sub_line->getData('duration');
                    if (!$sub_line_duration) {
                        $errors[] = 'Ligne #' . $id_line . ' (n° ' . $sub_line->getData('rang') . ') : durée non définie';
                        continue;
                    }

                    $nb_units = ((float) $sub_line->getData('qty') / $sub_line_duration) * $prod_duration;
                    if ($nb_units) {
                        $qty += $nb_units * $mult;
                    }
                }
            }

            if (!count($errors)) {
                if (!$qty) {
                    $errors[] = 'Aucune unité à renouveller';
                } else {
                    $pa_ht = (float) $this->getData('buy_price_ht');
                    $id_pfp = (int) $this->getData('fk_product_fournisseur_price');
                    if ($id_pfp) {
                        $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);

                        if (BimpObject::objectLoaded($pfp)) {
                            $pa_ht = (float) $pfp->getData('price');
                        }
                    }

                    $date_fin = $this->getData('date_fin_validite');

                    if (!$date_fin) {
                        $errors[] = 'La date de fin de validité de la ligne à renouveller n\'est pas définie';
                    } else {
                        $date_cloture = (string) $this->getData('date_cloture');

                        if ($date_cloture) {
                            $dt_ouv = new DateTime($date_cloture);
                        } else {
                            $dt_ouv = new DateTime($date_fin);
                            $dt_ouv->add(new DateInterval('P1D'));
                        }

                        $nb_renouv = (int) $this->getData('nb_renouv');

                        if ($nb_renouv > 0) {
                            $nb_renouv--;
                        }

                        $new_line = BimpObject::createBimpObject('bimpcontrat', 'BCT_ContratLine', array(
                                    'fk_contrat'                   => $this->getData('fk_contrat'),
                                    'fk_product'                   => $this->getData('fk_product'),
                                    'label'                        => $this->getData('label'),
                                    'statut'                       => ($options['id_propal'] >= 0 ? self::STATUS_ATT_PROPAL : 0),
                                    'line_type'                    => BCT_ContratLine::TYPE_ABO,
                                    'description'                  => $this->getData('description'),
                                    'product_type'                 => $this->getData('product_type'),
                                    'qty'                          => $qty,
                                    'subprice'                     => $options['subprice'],
                                    'tva_tx'                       => $prod->getData('tva_tx'),
                                    'remise_percent'               => $this->getData('remise_percent'),
                                    'fk_product_fournisseur_price' => $id_pfp,
                                    'buy_price_ht'                 => $pa_ht,
                                    'fac_periodicity'              => $options['fac_periodicity'],
                                    'duration'                     => $options['duration'],
                                    'fac_term'                     => $options['fac_term'],
                                    'nb_renouv'                    => $nb_renouv,
                                    'id_line_origin'               => ($options['id_propal'] < 0 ? $this->id : 0),
                                    'line_origin_type'             => ($options['id_propal'] < 0 ? 'contrat_line' : ''),
                                    'achat_periodicity'            => $options['achat_periodicity'],
                                    'variable_qty'                 => $this->getData('variable_qty'),
                                    'date_ouverture_prevue'        => $dt_ouv->format('Y-m-d') . ' 00:00:00'
                                        ), true, $errors, $warnings);

                        if (!count($errors)) {
                            $success .= ($success ? '<br/>' : '') . 'Création de la nouvelle ligne de contrat OK';

                            foreach ($lines as $id_line) {
                                $sub_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

                                $line_errors = $sub_line->updateField('id_line_renouv', $new_line->id);

                                if (count($line_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $new_line->getData('rang') . ' : échec de l\'enregistrement de l\'ID de la ligne de renouvellement');
                                }

                                $lines_renouv[] = $id_line;
                            }

                            if (!count($errors) && $options['id_propal'] >= 0) {
                                if ($options['id_propal']) {
                                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $options['id_propal']);
                                    if (!BimpObject::objectLoaded($propal)) {
                                        $errors[] = 'Le devis #' . $options['id_propal'] . ' n\'existe plus';
                                    }
                                } else {
                                    $propal_errors = array();
                                    $propal = $contrat->createPropal($options['propal_label'], $propal_errors, $warnings);

                                    if (count($propal_errors)) {
                                        $errors[] = BimpTools::getMsgFromArray($propal_errors, 'Echec de la création du devis');
                                    } else {
                                        $success .= ($success ? '<br/>' : '') . 'Création du devis ' . $propal->getLink() . ' OK';
                                    }
                                }

                                if (!count($errors)) {
                                    $new_line->createLinkedPropalLine($propal, array(), $errors, $warnings, $success);

                                    if (!count($errors)) {
                                        $url = $propal->getUrl();

                                        if ($url) {
                                            $success_callback .= 'window.open(\'' . $url . '\');';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function setDateCloture($date_cloture, &$warnings = array(), $check = true)
    {
        $errors = array();

        if ($check && !$this->isActionAllowed('setResiliateDate', $errors)) {
            return $errors;
        }

        if ($date_cloture) {
            $date_cloture = date('Y-m-d', strtotime($date_cloture));
        }

        $periods_data = $this->getPeriodsToBillData($errors);

        if (!count($errors)) {
            if ($date_cloture && $date_cloture < $periods_data['date_next_period_tobill']) {
                $date_cloture = $periods_data['date_next_period_tobill'];

                $warnings[] = 'Ligne n° ' . $this->getData('rang') . ' : date de cloture décalée au ' . date('d / m / Y', strtotime($date_cloture));
            }

            $errors = $this->updateField('date_cloture', $date_cloture . ' 00:00:00');

            if (!count($errors)) {
                $sub_lines = BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                            'id_parent_line' => $this->id
                ));

                if (!empty($sub_lines)) {
                    foreach ($sub_lines as $sub_line) {
                        $sub_line_errors = $sub_line->setDateCloture($date_cloture, $warnings, false);

                        if (count($sub_line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($sub_line_errors, 'Echec de l\'enregisrement de la date de résiliation pour la sous-ligne n° ' . $sub_line->getData('rang'));
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function createLinkedLine($nb_units, &$id_propal = -1, $options = array(), &$errors = array(), &$warnings = array(), &$success = '', &$success_callback = '')
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        if (!(float) $nb_units) {
            $errors[] = 'Aucune unité à ajouter ou retirer';
        }

        $prod = $this->getChildObject('product');
        $options = BimpTools::overrideArray(array(
                    'propal_label'          => '',
                    'date_ouverture_prevue' => null
                        ), $options);

        $contrat = $this->getParentInstance();
        if (!BimpObject::objectLoaded($contrat)) {
            $errors[] = 'Contrat lié absent';
        }

        if (!BimpObject::objectLoaded($prod)) {
            $errors[] = 'Produit lié absent';
        } else {
            $duration = (int) $this->getData('duration');
            $fac_periodicity = (int) $this->getData('fac_periodicity');
            $date_fin = $this->getData('date_fin_validite');

            $prod_duration = 0;
            if (BimpObject::objectLoaded($prod)) {
                $prod_duration = (int) $prod->getData('duree');
            }
            if (!$prod_duration) {
                $errors[] = 'Durée unitaire du produit non définie';
            }

            if (!$duration) {
                $errors[] = 'Durée de l\'abonnement non définie';
            }

            if (!$fac_periodicity) {
                $errors[] = 'Périodicité de facturation non définie';
            }

            if (!$date_fin) {
                $errors[] = 'La date de fin de validité non définie';
            }

            if (!count($errors)) {
                if ($duration % $fac_periodicity != 0) {
                    $errors[] = 'La durée totale de l\'abonnement doit être un multiple du nombre de mois correspondant à la périodicité de facturation (' . $fac_periodicity . ' mois)';
                }
                if ($duration % $prod_duration != 0) {
                    $errors[] = 'La durée totale de l\'abonnement  doit être un multiple de la durée unitaire de produit (' . $prod_duration . ' mois)';
                } elseif ($duration < $prod_duration) {
                    $errors[] = 'La durée totale de l\'abonnement ne peut pas être inférieure à la durée unitaire de produit (' . $prod_duration . ' mois)';
                }
            }
        }

        if (!count($errors)) {
            $qty = $nb_units * ($duration / $prod_duration);
            $pa_ht = (float) $this->getData('buy_price_ht');
            $id_pfp = (int) $this->getData('fk_product_fournisseur_price');
            if ($id_pfp) {
                $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);

                if (BimpObject::objectLoaded($pfp)) {
                    $pa_ht = (float) $pfp->getData('price');
                }
            }

            $id_linked_line = $this->id;
            if ($this->getData('id_linked_line')) {
                $id_linked_line = (int) $this->getData('id_linked_line');
            }

            $new_line = BimpObject::createBimpObject('bimpcontrat', 'BCT_ContratLine', array(
                        'fk_contrat'                   => $this->getData('fk_contrat'),
                        'fk_product'                   => $prod->id,
                        'label'                        => $this->getData('label'),
                        'statut'                       => ($id_propal >= 0 ? self::STATUS_ATT_PROPAL : 0),
                        'line_type'                    => BCT_ContratLine::TYPE_ABO,
                        'description'                  => $this->getData('description'),
                        'product_type'                 => $this->getData('product_type'),
                        'id_linked_line'               => $id_linked_line,
                        'qty'                          => $qty,
                        'subprice'                     => $prod->getData('price'),
                        'tva_tx'                       => $prod->getData('tva_tx'),
                        'remise_percent'               => $this->getData('remise_percent'),
                        'fk_product_fournisseur_price' => $id_pfp,
                        'buy_price_ht'                 => $pa_ht,
                        'fac_periodicity'              => $fac_periodicity,
                        'duration'                     => $duration,
                        'fac_term'                     => $this->getData('fac_term'),
                        'nb_renouv'                    => $this->getData('nb_renouv'),
                        'id_line_origin'               => ($id_propal < 0 ? $this->id : 0),
                        'line_origin_type'             => ($id_propal < 0 ? 'contrat_line' : ''),
                        'achat_periodicity'            => $this->getData('achat_periodicity'),
                        'variable_qty'                 => $this->getData('variable_qty'),
                        'date_ouverture_prevue'        => ($options['date_ouverture_prevue'] ? date('Y-m-d', strtotime($options['date_ouverture_prevue'])) . ' 00:00:00' : null)
                            ), true, $errors, $warnings);

            if (!count($errors)) {
                $success .= ($success ? '<br/>' : '') . 'Création de la nouvelle ligne de contrat OK';

                if (!count($errors) && $id_propal >= 0) {
                    if ($id_propal) {
                        $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_propal);
                        if (!BimpObject::objectLoaded($propal)) {
                            $errors[] = 'Le devis #' . $id_propal . ' n\'existe plus';
                        }
                    } else {
                        $propal_errors = array();
                        $propal = $contrat->createPropal($options['propal_label'], $propal_errors, $warnings);

                        if (count($propal_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($propal_errors, 'Echec de la création du devis');
                        } else {
                            $id_propal = $propal->id;
                            $success .= ($success ? '<br/>' : '') . 'Création du devis ' . $propal->getLink() . ' OK';
                        }
                    }

                    if (!count($errors)) {
                        $new_line->createLinkedPropalLine($propal, array(
                            'id_linked_contrat_line' => $id_linked_line
                                ), $errors);

                        if (!count($errors)) {
                            $url = $propal->getUrl();

                            if ($url) {
                                $success_callback .= 'window.open(\'' . $url . '\');';
                            }
                        }
                    }
                }

                return $new_line->id;
            }
        }

        return 0;
    }

    public function createLinkedPropalLine($propal, $options = array(), &$errors = array(), &$warnings = array(), &$success = '')
    {
        $options = BimpTools::overrideArray(array(
                    'id_linked_contrat_line' => 0
                        ), $options);
        if (!$this->isLoaded($errors)) {
            return null;
        }

        $prod = $this->getChildObject('product');
        if (!BimpObject::objectLoaded($prod)) {
            $errors[] = 'Aucun produit';
            return null;
        }

        if ((int) $this->getData('id_line_origin')) {
            $errors[] = 'Impossible d\'ajouter une ligne de devis à partir de cette ligne de contrat. Celle-ci est déjà liée à une pièce d\'origine';
            return null;
        }

        $is_bundle = $prod->isBundle();
        $propal_line = BimpObject::getInstance('bimpcommercial', 'Bimp_PropalLine');

        if ($is_bundle) {
            $propal_line->no_maj_bundle = true;
        }

        $propal_line->id_product = $prod->id;
        $propal_line->product_type = $this->getData('product_type');
        $propal_line->desc = $this->getData('description');
        $propal_line->qty = $this->getData('qty');
        $propal_line->pu_ht = $this->getData('subprice');
        $propal_line->tva_tx = $prod->getData('tva_tx');
        $propal_line->pa_ht = $this->getData('buy_price_ht');
        $propal_line->id_fourn_price = $this->getData('fk_product_fournisseur_price');
        $propal_line->remise = $this->getData('remise_percent');
        $propal_line->date_from = $this->getData('date_ouverture_prevue');

        $propal_line_errors = $propal_line->validateArray(array(
            'id_obj'                 => $propal->id,
            'type'                   => 1,
            'abo_fac_periodicity'    => $this->getData('fac_periodicity'),
            'abo_duration'           => $this->getData('duration'),
            'abo_fac_term'           => $this->getData('fac_term'),
            'abo_nb_renouv'          => $this->getData('nb_renouv'),
            'id_linked_contrat_line' => $options['id_linked_contrat_line']
        ));

        if (!count($propal_line_errors)) {
            $propal_line_errors = $propal_line->create($warnings, true);
        }

        if (count($propal_line_errors)) {
            $errors[] = BimpTools::getMsgFromArray($propal_line_errors, 'Echec de l\'ajout de la ligne au devis ' . $propal->getLink());
        } else {
            $this->set('line_origin_type', 'propal_line');
            $this->set('id_line_origin', $propal_line->id);

            $err = $this->update($warnings, true);
            if (count($err)) {
                $errors[] = BimpTools::getMsgFromArray($err, 'Echec de l\'enregistrement de la ligne du devis liée à la nouvelle ligne de contrat');
            }

            $success .= ($success ? '<br/>' : '') . 'Ajout de la ligne au devis ' . $propal->getLink() . ' OK';

            if ($is_bundle) {
                // Ajout des sous-lignes de bundle au devis : 
                foreach (BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                    'id_parent_line' => $this->id
                )) as $bundle_line) {
                    $bundle_propal_line = BimpObject::getInstance('bimpcommercial', 'Bimp_PropalLine');
                    $bundle_propal_line->no_maj_bundle = true;

                    $bundle_propal_line->id_product = $bundle_line->getData('fk_product');
                    $bundle_propal_line->product_type = $bundle_line->getData('product_type');
                    $bundle_propal_line->desc = $bundle_line->getData('description');
                    $bundle_propal_line->qty = $bundle_line->getData('qty');
                    $bundle_propal_line->pu_ht = $bundle_line->getData('subprice');
                    $bundle_propal_line->tva_tx = $bundle_line->getData('tva_tx');
                    $bundle_propal_line->pa_ht = $bundle_line->getData('buy_price_ht');
                    $bundle_propal_line->id_fourn_price = $bundle_line->getData('fk_product_fournisseur_price');
                    $bundle_propal_line->remise = $bundle_line->getData('remise_percent');
                    $bundle_propal_line->date_from = $bundle_line->getData('date_ouverture_prevue');

                    $err = $bundle_propal_line->validateArray(array(
                        'id_obj'              => $propal->id,
                        'id_parent_line'      => $propal_line->id,
                        'type'                => ($bundle_line->getData('linked_object_name') === 'bundleCorrect' ? ObjectLine::LINE_FREE : ObjectLine::LINE_PRODUCT),
                        'editable'            => 0,
                        'deletable'           => 0,
                        'linked_object_name'  => $bundle_line->getData('linked_object_name'),
                        'linked_id_object'    => $bundle_line->getData('linked_id_object'),
                        'abo_fac_periodicity' => $bundle_line->getData('fac_periodicity'),
                        'abo_duration'        => $bundle_line->getData('duration'),
                        'abo_fac_term'        => $bundle_line->getData('fac_term'),
                        'abo_nb_renouv'       => $bundle_line->getData('nb_renouv'),
                    ));

                    if ($bundle_line->getData('linked_object_name') === 'bundleCorrect') {
                        $bundle_propal_line->set('remisable', 2);
                    }

                    if (!count($err)) {
                        $err = $bundle_propal_line->create($warnings, true);
                    }

                    if (count($err)) {
                        $bundle_prod = $bundle_line->getChildObject('product');
                        $errors[] = BimpTools::getMsgFromArray($err, 'Echec de l\'ajout d\une sous-ligne de bundle au devis (Produit ' . (BimpObject::objectLoaded($bundle_prod) ? $bundle_line->getRef() : '#' . $bundle_line->getData('fk_product')) . ')');
                    } else {
                        $bundle_line->set('line_origin_type', 'propal_line');
                        $bundle_line->set('id_line_origin', $bundle_propal_line->id);

                        $err = $bundle_line->update($warnings, true);
                        if (count($err)) {
                            $bundle_prod = $bundle_line->getChildObject('product');
                            $errors[] = BimpTools::getMsgFromArray($err, 'Produit ' . (BimpObject::objectLoaded($bundle_prod) ? $bundle_line->getRef() : '#' . $bundle_line->getData('fk_product')) . ' : Echec de l\'enregistrement de la ligne du devis liée à la nouvelle ligne de contrat ');
                        }
                    }
                }
            }

            return $propal_line;
        }

        return null;
    }

    // Gestion positions:

    public function checkPosition($position)
    {
        $contrat = $this->getParentInstance();

        if (!BimpObject::objectLoaded($contrat) || (isset($contrat->isDeleting) && $contrat->isDeleting)) {
            return;
        }

        $id_parent_line = (int) $this->getData('id_parent_line');
        if ($id_parent_line) {
            // Vérification de la nouvelle position de la ligne si elle est enfant d'une autre ligne.
            $parent_position = (int) $this->db->getValue('contratdet', 'rang', 'rowid = ' . $id_parent_line);

            if ($parent_position) {
                if ($position <= $parent_position) {
                    $position = $parent_position + 1;
                } elseif ($position > ($parent_position + 1)) {
                    // on vérifie l'existance d'autres lignes enfants pour la même ligne parente: 
                    $nb_children_lines = (int) $this->db->getCount('contratdet', 'fk_contrat = ' . $contrat->id . ' AND id_parent_line = ' . $id_parent_line);
                    if ($nb_children_lines) {
                        $max_pos = $parent_position + $nb_children_lines;
                        if ($position > $max_pos) {
                            $position = $max_pos;
                        }
                    }
                }
            }
        } else {
            // Vérification que la nouvelle position ne sépare pas des lignes enfants de leur parent.
            $rows = $this->getList(array(
                'fk_contrat'     => (int) $contrat->id,
                'id_parent_line' => array(
                    'operator' => '>',
                    'value'    => 0
                )
                    ), null, null, 'rang', 'asc', 'array', array('rowid', 'id_parent_line', 'rang'));

            $init_pos = (int) $this->getInitData('rang');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    if ((int) $r['rowid'] === (int) $this->id) {
                        continue;
                    }

                    $r_pos = (int) $r['rang'];

                    if ($init_pos < $r_pos && $position > $r_pos) {
                        $r_pos--; // La ligne sera décalée de -1. 
                    }

                    if ((int) $r_pos === (int) $position && (int) $r['id_parent_line']) {
                        $position++;
                    }
                }
            }
        }
        return $position;
    }

    public function resetPositions()
    {
        if ($this->getConf('positions', false, false, 'bool')) {
            $id_contrat = (int) $this->getData('fk_contrat');
            if (!$id_contrat) {
                return;
            }

            $items = $this->getList(array(
                'fk_contrat'     => $id_contrat,
                'id_parent_line' => 0
                    ), null, null, 'rang', 'asc', 'array', array('rowid', 'rang'));
            $i = 1;
            $done = array();
            foreach ($items as $item) {
                if (in_array((int) $item['rowid'], $done)) {
                    continue;
                }

                if ((int) $item['rang'] !== $i) {
                    $this->db->update('contratdet', array(
                        'rang' => (int) $i
                            ), '`rowid` = ' . (int) $item['rowid']);
                }

                $done[] = (int) $item['rowid'];
                $i++;

                $children = $this->getList(array(
                    'fk_contrat'     => (int) $id_contrat,
                    'id_parent_line' => (int) $item['rowid']
                        ), null, null, 'rang', 'asc', 'array', array('rowid', 'rang'));
                if (!is_null($children)) {
                    foreach ($children as $child) {
                        if ((int) $child['rang'] !== $i) {
                            $this->db->update('contratdet', array(
                                'rang' => (int) $i
                                    ), '`rowid` = ' . (int) $child['rowid']);
                        }
                        $done[] = (int) $child['rowid'];
                        $i++;
                    }
                }
            }
        }
    }

    public function setPosition($position, &$errors = array())
    {
        $debug = false;
        $check = true;

        if ($debug)
            echo 'POS BEF: ' . $position . '<br/>';

        $position = (int) $this->checkPosition($position);

        if ($debug)
            echo 'POS AFT: ' . $position . '<br/>';

        if (!$this->isLoaded($errors)) {
            $check = false;
        } elseif ($this->getConf('positions', false, false, 'bool')) {
            $id_contrat = (int) $this->getData('fk_contrat');
            if (!$id_contrat) {
                $check = false;
            } else {
                $id_parent_line = (int) $this->getData('id_parent_line');
                $lines = $this->getList(array(
                    'fk_contrat' => $id_contrat
                        ), null, null, 'rang', 'asc', 'array', array('rowid', 'rang', 'id_parent_line'));

                $i = 1;
                $done = array();

                foreach ($lines as $line) {
                    if ($i === $position) {
                        // Attribution de la nouvelle position: 
                        if (!in_array($this->id, $done)) {
                            if ($debug)
                                echo 'THIS => ' . $position . '<br/>';
                            $this->db->update('contratdet', array(
                                'rang' => (int) $position
                                    ), '`rowid` = ' . (int) $this->id);
                            $this->set('rang', $position);
                            $i++;
                            $done[] = $this->id;

                            // Attribution des positions suivantes aux enfants de cette ligne:
                            $children = $this->getList(array(
                                'fk_contrat'     => (int) $id_contrat,
                                'id_parent_line' => (int) $this->id
                                    ), null, null, 'rang', 'asc', 'array', array('rowid', 'rang'));
                            if (!is_null($children)) {
                                foreach ($children as $child) {
                                    if ($debug)
                                        echo 'THIS CHILD #' . $child['rowid'] . ' => ' . $i . '<br/>';
                                    if ((int) $child['rang'] !== $i) {
                                        $this->db->update('contratdet', array(
                                            'rang' => (int) $i
                                                ), '`rowid` = ' . (int) $child['rowid']);
                                    }
                                    $done[] = (int) $child['rowid'];
                                    $i++;
                                }
                            }
                        }
                    }

                    if ((int) $line['rowid'] === (int) $this->id) {
                        continue;
                    }

                    if (in_array($line['rowid'], $done)) {
                        continue;
                    }

                    if ((int) $line['id_parent_line']) {
                        if ($i < $position) {
                            $position--;
                        }
                        continue;
                    }

                    // Attribution de la position courante à la ligne courante: 
                    if ((int) $line['rang'] !== $i) {
                        if ($debug)
                            echo 'LINE #' . $line['rowid'] . ' => ' . $i . '<br/>';
                        $this->db->update('contratdet', array(
                            'rang' => (int) $i
                                ), '`rowid` = ' . (int) $line['rowid']);
                    }
                    $done[] = $line['rowid'];
                    $i++;

                    // Attribution des positions suivantes aux enfants de cette ligne:
                    $children = $this->getList(array(
                        'fk_contrat'     => (int) $id_contrat,
                        'id_parent_line' => (int) $line['rowid']
                            ), null, null, 'rang', 'asc', 'array', array('rowid', 'rang'));
                    if (!is_null($children)) {
                        foreach ($children as $child) {
                            if ($i === $position) {
                                if ($id_parent_line === (int) $line['rowid']) {
                                    if ($debug)
                                        echo 'THIS AS CHILD => ' . $i . '<br/>';
                                    $this->db->update('contratdet', array(
                                        'rang' => (int) $position
                                            ), '`rowid` = ' . (int) $this->id);
                                    $i++;
                                    $done[] = $this->id;
                                } else {
                                    $position++;
                                }
                            }
                            if (!in_array((int) $child['rowid'], $done) && (int) $child['rowid'] !== $this->id) {
                                if ($debug)
                                    echo 'LINE #' . $line['rowid'] . ' CHILD #' . $child['rowid'] . ' => ' . $i . '<br/>';
                                if ((int) $child['position'] !== $i) {
                                    $this->db->update('contratdet', array(
                                        'rang' => (int) $i
                                            ), '`rowid` = ' . (int) $child['rowid']);
                                }
                                $done[] = (int) $child['rowid'];
                                $i++;
                            }
                        }
                    }
                }

                if (!in_array($this->id, $done)) {
                    if ($debug)
                        echo 'THIS DEF => ' . $position . '<br/>';
                    $this->db->update('contratdet', array(
                        'position' => (int) $i
                            ), '`contratdet` = ' . (int) $this->id);
                    $this->set('position', $i);
                }
            }
        } else {
            $check = false;
        }

        if ($debug)
            exit;
        return $check;
    }

    public function getNextPosition()
    {
        if ($this->getConf('positions', false, false, 'bool')) {
            $id_contrat = (int) $this->getData('fk_contrat');

            if ($id_contrat) {
                return (int) $this->db->getMax('contratdet', 'rang', 'fk_contrat = ' . $id_contrat) + 1;
            }
        }

        return 1;
    }

    // Actions:

    public function actionActivate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if ($this->isLoaded()) {
            $date_ouverture = BimpTools::getArrayValueFromPath($data, 'date_ouverture_prevue', '');

            if (!$date_ouverture) {
                $errors[] = 'Veuillez renseigner la date d\'ouverture';
            }

            $id_linked_line = (int) BimpTools::getArrayValueFromPath($data, 'id_linked_line', $this->getData('id_linked_line'));
            if ($id_linked_line) {
                $this->set('id_linked_line', $id_linked_line);
                $this->checkLinkedLine($errors);
            }

            if (!count($errors)) {
                $success = 'Ligne de contrat activée';
                $errors = $this->activate($date_ouverture);
            }
        } else {
            $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

            if (empty($ids)) {
                $errors[] = 'Aucune ligne de contrat sélectionnée';
            } else {
                $nOk = 0;
                $nFails = 0;
                $lines_errors = array();
                foreach ($ids as $id_line) {
                    $line_errors = array();
                    $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

                    if (!BimpObject::objectLoaded($line)) {
                        $line_errors[] = 'Ligne #' . $id_line . ' inexistante';
                    } else {
                        $id_parent_line = (int) $line->getData('id_parent_line');

                        if ($id_parent_line && in_array($id_parent_line, $ids)) {
                            continue;
                        }

                        if ($line->isActionAllowed('activate', $line_errors)) {
                            $line_errors = $line->activate();
                        }
                    }

                    if (count($line_errors)) {
                        $nFails++;
                        $prod_label = '';
                        $prod = $line->getChildObject('product');
                        if (BimpObject::objectLoaded($prod)) {
                            $prod_label .= ' (' . $prod->getRef() . ' - ' . $prod->getName() . ')';
                        }
                        $lines_errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('rang') . $prod_label);
                    } else {
                        $nOk++;
                    }
                }
            }

            if ($nFails > 0) {
                $s = ($nFails > 1 ? 's' : '');
                $warnings[] = $nFails . ' ligne' . $s . ' n\'' . ($s ? 'ont' : 'a') . ' pas pu être activée' . $s;

                if (!empty($lines_errors)) {
                    $warnings = BimpTools::merge_array($warnings, $lines_errors);
                }
            }

            if ($nOk > 0) {
                $s = ($nOk > 1 ? 's' : '');
                $success = $nOk . ' ligne' . $s . ' activée' . $s . ' avec succès';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionDeactivate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Désactivation effectuée';

        $this->set('statut', 0);
        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $sub_lines = BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                        'id_parent_line' => $this->id
            ));

            if (!empty($sub_lines)) {
                foreach ($sub_lines as $sub_line) {
                    $sub_line->set('statut', 0);
                    $sub_line_errors = $this->update($warnings, true);

                    if (count($sub_line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($sub_line_errors, 'Echec de la désactivation de la sous-ligne n° ' . $sub_line->getData('rang'));
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionBulkEdit($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $lines = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (!count($lines)) {
            $errors[] = 'Aucune ligne sélectionnée';
        }

        $date_ouv = BimpTools::getArrayValueFromPath($data, 'date_ouverture_prevue');
        $date_fac_start = BimpTools::getArrayValueFromPath($data, 'date_fac_start');
        $date_achat_start = BimpTools::getArrayValueFromPath($data, 'date_achat_start');

        $nOk = 0;
        foreach ($lines as $id_line) {
            $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

            if (!BimpObject::objectLoaded($line)) {
                $warnings[] = 'La ligne de contrat #' . $id_line . ' n\'existe plus';
                continue;
            }

            $contrat = $line->getParentInstance();
            if ((int) $line->getData('statut') > 0) {
                $warnings[] = 'La ligne n° ' . $line->getData('rang') . (BimpObject::objectLoaded($contrat) ? ' (Contrat ' . $contrat->getRef() . ')' : '') . ' a déjà été activée';
                continue;
            }

            if ($date_ouv) {
                $line->set('date_ouverture_prevue', $date_ouv);
            }
            if ($date_fac_start) {
                $line->set('date_fac_start', $date_fac_start);
            }
            if ($date_achat_start) {
                $line->set('date_achat_start', $date_achat_start);
            }

            $line_warnings = array();
            $line_errors = $line->update($line_warnings);

            if (count($line_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Echec de la mise à jour de la ligne n°' . $line->getData('rang') . (BimpObject::objectLoaded($contrat) ? ' (Contrat ' . $contrat->getRef() . ')' : ''));
            } else {
                $nOk++;
            }
        }

        if ($nOk) {
            $s = ($nOk > 1 ? 's' : '');
            $success = $nOk . ' ligne' . $s . ' mise' . $s . ' à jours avec succès';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionFacRegul($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_facture = (int) BimpTools::getArrayValueFromPath($data, 'id_fac_regul', 0);
        $to = BimpTools::getArrayValueFromPath($data, 'period_to', '');
        $qty = (float) BimpTools::getArrayValueFromPath($data, 'regul_qty', 0);

        //        $from = BimpTools::getArrayValueFromPath($data, 'period_from', '');
        $from = $this->getDateFacStart();
        if (!$from) {
            $errors[] = 'Date de début des facturations non définie';
        }

        if (!$to) {
            $errors[] = 'Veuillez sélectionner une date de fin de période à régulariser';
        }

        if (!$qty) {
            $errors[] = 'Veuillez saisir la quantité à régulariser';
        }

        $contrat = $this->getParentInstance();
        if (!BimpObject::objectLoaded($contrat)) {
            $errors[] = 'Contrat absent';
        }

        if (!count($errors)) {
            $facture = null;

            if ($id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
                }
            } else {
                // Création de la facture:
                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

                $id_client = (int) $contrat->getData('fk_soc_facturation');

                if (!$id_client) {
                    $id_client = (int) $contrat->getData('fk_soc');
                }

                $fac_errors = array();
                $facture = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                            'fk_soc'            => $id_client,
                            'entrepot'          => $contrat->getData('entrepot'),
                            'ef_type'           => $contrat->getData('secteur'),
                            'expertise'         => $contrat->getData('expertise'),
                            'libelle'           => BimpTools::getArrayValueFromPath($data, 'fac_libelle', ''),
                            'fk_mode_reglement' => $contrat->getData('moderegl'),
                            'fk_cond_reglement' => $contrat->getData('condregl'),
                            'datef'             => date('Y-m-d')
                                ), true, $fac_errors);

                if (count($fac_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture');
                } else {
                    $success = 'Création de la facture effectuée avec succès';
                }
            }

            if (!count($errors)) {
                addElementElement('bimp_contrat', 'facture', $contrat->id, $facture->id);

                // Création de la ligne de l'intitulé du contrat d'origine si nécessaire: 
                $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                            'id_obj'             => (int) $facture->id,
                            'linked_object_name' => 'contrat_origin_label',
                            'linked_id_object'   => (int) $contrat->id
                ));

                if (!BimpObject::objectLoaded($fac_line)) {
                    $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                    $fac_line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => ObjectLine::LINE_TEXT,
                        'linked_id_object'   => $contrat->id,
                        'linked_object_name' => 'contrat_origin_label',
                    ));
                    $fac_line->qty = 1;
                    $fac_line->desc = 'Selon votre contrat ' . $contrat->getRef();
                    $libelle = $contrat->getData('libelle');
                    if ($libelle) {
                        $fac_line->desc .= ' - ' . $libelle;
                    }
                    $fac_line_warnings = array();
                    $fac_line->create($fac_line_warnings, true);
                }

                // Création de la ligne de facture: 
                $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                $fac_line->validateArray(array(
                    'id_obj'             => (int) $facture->id,
                    'type'               => Bimp_FactureLine::LINE_PRODUCT,
                    'remisable'          => 2,
                    'editable'           => 0,
                    'pa_editable'        => 0,
                    'linked_id_object'   => (int) $this->id,
                    'linked_object_name' => 'contrat_line_regul'
                ));

                $date_from = date('Y-m-d 00:00:00', strtotime($from));
                $date_to = date('Y-m-d 23:59:59', strtotime($to));

                $id_fourn = 0;
                $pa_ht_line = (float) $this->getData('buy_price_ht');
                $pa_ht_fourn = 0;

                $id_pfp = (int) $this->getData('fk_product_fournisseur_price');
                if ($id_pfp) {
                    $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);
                    if (!BimpObject::objectLoaded($pfp)) {
                        $line_errors[] = 'Le prix d\'achat fournisseur #' . $id_pfp . ' n\'existe plus';
                    } else {
                        $id_fourn = $pfp->getData('fk_soc');
                        $pa_ht_fourn = $pfp->getData('price');
                    }
                }

                $fac_line->qty = $qty;
                $fac_line->desc = '(Régularisation sur cette période)<br/><br/>' . $this->getData('description');
                $fac_line->id_product = (int) $this->getData('fk_product');
                $fac_line->pu_ht = $this->getData('subprice');
                $fac_line->tva_tx = $this->getData('tva_tx');
                $fac_line->pa_ht = ($pa_ht_fourn ? $pa_ht_fourn : $pa_ht_line);
                $fac_line->id_fourn_price = $this->getData('fk_product_fournisseur_price');
                $fac_line->date_from = $date_from;
                $fac_line->date_to = $date_to;
                $fac_line->no_remises_arrieres_auto_create = true;

                $line_warnings = array();
                $line_errors = $fac_line->create($line_warnings, true);

                if (!count($line_errors)) {
                    // Ajout de la remise: 
                    $remise_percent = (float) $this->getData('remise_percent');
                    if ($remise_percent) {
                        $remises_errors = array();
                        BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemise', array(
                            'id_object_line' => $fac_line->id,
                            'object_type'    => 'facture',
                            'type'           => 1,
                            'percent'        => $remise_percent
                                ), true, $remises_errors);

                        if (count($remises_errors)) {
                            $line_errors[] = BimpTools::getMsgFromArray($remises_errors, 'Echec de l\'ajout de la remise à la ligne de facture');
                        }
                    }
                }

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne à la facture');
                } else {
                    $success .= ($success ? '<br/>' : '') . 'Ajout de la ligne à la facture effectué avec succès';
                }
            }

            if (!count($errors)) {
                $url = $facture->getUrl();
                if ($url) {
                    $sc = 'window.open(\'' . $url . '\');';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionRenouv($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $id_propal = (int) BimpTools::getArrayValueFromPath($data, 'id_propal', -1);
        $propal_label = '';

        if ($id_propal === 0) {
            $propal_label = BimpTools::getArrayValueFromPath($data, 'propal_label', '');

            if (!$propal_label) {
                $errors[] = 'Veuillez saisir le libellé du nouveau devis';
            }
        }

        $lines_renouv = array();

        if ($this->isLoaded()) {
            $fac_periodicity = (int) BimpTools::getArrayValueFromPath($data, 'fac_periodicity', 0);
            $achat_periodicity = (int) BimpTools::getArrayValueFromPath($data, 'achat_periodicity', 0);
            $subprice = (float) BimpTools::getArrayValueFromPath($data, 'renouv_subprice', 0);
            $duration = (int) BimpTools::getArrayValueFromPath($data, 'duration', 0);
            $fac_term = (int) BimpTools::getArrayValueFromPath($data, 'fac_term', 1);

            $id_main_line = (int) BimpTools::getArrayValueFromPath($data, 'id_main_line', 0);
            $lines = BimpTools::getArrayValueFromPath($data, 'lines', array());

            if (!$fac_periodicity) {
                $errors[] = 'Périodicité de facturation non définie';
            }

            if (!$duration) {
                $errors[] = 'Durée non définie';
            }

            if (!$subprice) {
                $errors[] = 'Prix unitaire HT non défini';
            }

            if (!$id_main_line) {
                $errors[] = 'ID ligne principale absent';
            }

            if (empty($lines)) {
                $errors[] = 'Aucune ligne à renouveller sélectionnée';
            }

            if (!count($errors)) {
                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_main_line);
                if (!BimpObject::objectLoaded($line)) {
                    $errors[] = 'La ligne principale d\'ID ' . $id_main_line . ' n\'existe plus';
                } else {
                    $line->renouvAbonnement(array(
                        'id_propal'         => $id_propal,
                        'propal_label'      => $propal_label,
                        'fac_periodicity'   => $fac_periodicity,
                        'achat_periodicity' => $achat_periodicity,
                        'subprice'          => $subprice,
                        'duration'          => $duration,
                        'fac_term'          => $fac_term,
                        'lines'             => $lines
                            ), $lines_renouv, $errors, $warnings, $success, $sc);
                }
            }
        } else {
            $lines = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

            if (empty($lines)) {
                $errors[] = 'Aucune ligne sélectionnée';
            } else {
                $nOk = 0;
                foreach ($lines as $id_line) {
                    $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

                    if (!BimpObject::objectLoaded($line)) {
                        $warnings[] = 'La ligne #' . $id_line . ' n\'existe plus';
                    } else {
                        if (in_array($line->id, $lines_renouv)) {
                            continue;
                        }

                        $line_errors = array();

                        if ($line->isActionAllowed('renouv', $line_errors)) {
                            $line->renouvAbonnement(array(
                                'id_propal'    => $id_propal,
                                'propal_label' => $propal_label
                                    ), $lines_renouv, $line_errors);

                            if (!count($line_errors)) {
                                $nOk++;

                                if (!$id_propal) {
                                    $id_propal_line = (int) $this->db->getValue('contratdet', 'id_line_origin', 'rowid = ' . (int) $line->getData('id_line_renouv'));
                                    if ($id_propal_line) {
                                        $id_propal = (int) $this->db->getValue('bimp_propal_line', 'id_obj', 'id = ' . $id_propal_line);
                                    }

                                    if ($id_propal <= 0) {
                                        $errors[] = 'Erreur technique : échec de la récupération de l\'ID du devis créé';
                                        break;
                                    }
                                }
                            }
                        }

                        if (count($line_errors)) {
                            $contrat = $line->getParentInstance();
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Contrat ' . (BimpObject::objectLoaded($contrat) ? $contrat->getRef() : '#' . $line->getData('fk_contrat')) . ' - Ligne n° ' . $line->getData('rang') . ' : échec du renouvellement');
                        }
                    }
                }

                if ($nOk) {
                    $s = ($nOk > 1 ? 's' : '');
                    $success = $nOk . ' ligne' . $s . ' renouvellée' . $s . ' avec succès';
                    if ($id_propal > 0) {
                        $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_propal);
                        if (BimpObject::objectLoaded($propal)) {
                            $url = $propal->getUrl();
                            if ($url) {
                                $sc = 'window.open(\'' . $url . '\');';
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionSetResiliateDate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $date_cloture = BimpTools::getArrayValueFromPath($data, 'date_cloture', '');
        if (!$date_cloture && !$this->getData('date_cloture')) {
            $errors[] = 'Veuillez sélectionner une date de clôture';
        }

        $lines = BimpTools::getArrayValueFromPath($data, 'lines', array());
        if (empty($lines)) {
            $errors[] = 'Aucune ligne sélectionnée';
        }

        if (!count($errors)) {
            foreach ($lines as $id_line) {
                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

                if (!BimpObject::objectLoaded($line)) {
                    $errors[] = 'La ligne #' . $id_line . ' n\'existe plus';
                } else {
                    $line_errors = $line->setDateCloture($date_cloture, $warnings);

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n° ' . $line->getData('rang'));
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddUnits($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $lines = BimpTools::getArrayValueFromPath($data, 'lines', array());
        if (empty($lines)) {
            $errors[] = 'Aucune ligne sélectionnée';
        } else {
            $id_propal = (int) BimpTools::getArrayValueFromPath($data, 'id_propal', -1);
            $propal_label = '';

            if ($id_propal === 0) {
                $propal_label = BimpTools::getArrayValueFromPath($data, 'propal_label', '');

                if (!$propal_label) {
                    $errors[] = 'Veuillez saisir le libellé du nouveau devis';
                }
            }
        }

        if (!count($errors)) {
            $date_ouv = BimpTools::getArrayValueFromPath($data, 'date_ouv', null);
            $nOk = 0;
            foreach ($lines as $line_data) {
                if (!(float) $line_data['nb_units']) {
                    continue;
                }

                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $line_data['id_line']);

                if (!BimpObject::objectLoaded($line)) {
                    $warnings[] = 'La ligne #' . $line_data['id_line'] . ' n\'existe plus';
                } else {
                    $line_errors = array();

                    if ($line->isActionAllowed('addUnits', $line_errors)) {
                        $line->createLinkedLine($line_data['nb_units'], $id_propal, array(
                            'propal_label'          => $propal_label,
                            'date_ouverture_prevue' => $date_ouv
                                ), $line_errors);

                        if (!count($line_errors)) {
                            $nOk++;
                        }
                    }

                    if (count($line_errors)) {
                        $contrat = $line->getParentInstance();
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Contrat ' . (BimpObject::objectLoaded($contrat) ? $contrat->getRef() : '#' . $line->getData('fk_contrat')) . ' - Ligne n° ' . $line->getData('rang') . ' : échec de l\'ajout d\'unité');
                    }
                }
            }

            if ($nOk) {
                $s = ($nOk > 1 ? 's' : '');
                $success = $nOk . ' ligne' . $s . ' traitée' . $s . ' avec succès';
                if ($id_propal > 0) {
                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_propal);
                    if (BimpObject::objectLoaded($propal)) {
                        $url = $propal->getUrl();
                        if ($url) {
                            $sc = 'window.open(\'' . $url . '\');';
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionCheckDateNextFac($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Check fait';

        $this->getDateNextFacture(true, $errors, $warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionMoveToOtherContrat($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $id_contrat_src = (int) BimpTools::getArrayValueFromPath($data, 'id_contrat_src', 0);
        if (!$id_contrat_src) {
            $errors[] = 'ID du contrat source absent';
        } else {
            $contrat_src = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat_src);

            if (!BimpObject::objectLoaded($contrat_src)) {
                $errors[] = 'Le contrat source #' . $id_contrat_src . ' n\'existe plus';
            }
        }

        $id_contrat_dest = (int) BimpTools::getArrayValueFromPath($data, 'id_contrat_dest', 0);
        if (!$id_contrat_dest) {
            $errors[] = 'ID du contrat de destination absent';
        } else {
            $contrat_dest = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat_dest);

            if (!BimpObject::objectLoaded($contrat_dest)) {
                $errors[] = 'Le contrat de destination #' . $id_contrat_dest . ' n\'existe plus';
            }
        }

        $id_lines = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        if (empty($id_lines)) {
            $errors[] = 'Aucune ligne à déplacer sélectionnée';
        }

        if (!count($errors)) {
            $nOk = 0;

            foreach ($id_lines as $id_line) {
                if ($this->db->update('contratdet', array(
                            'fk_contrat' => $id_contrat_dest
                                ), 'rowid = ' . $id_line . ' AND fk_contrat = ' . $id_contrat_src) <= 0) {
                    $errors[] = 'Ligne #' . $id_line . ' : Echec du déplacement - ' . $this->db->err();
                } else {
                    $nOk++;
                }
            }

            if ($nOk) {
                $s = ($nOk > 1 ? 's' : '');
                $success .= $nOk . ' ligne' . $s . ' déplacée' . $s . ' vers le contrat ' . $contrat_dest->getLink() . ' avec succès';
                $sc = 'window.open(\'' . $contrat_dest->getUrl() . '\');';

                $contrat_src->resetChildrenPositions('lines');
                $contrat_dest->resetChildrenPositions('lines');
            }
        }
        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    // Actions BimpDataSync:

    public function initBdsActionPeriodicFacProcess($process, &$action_data = array(), &$errors = array(), $extra_data = array())
    {
        $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');
        if ($use_db_transactions) {
            $this->db->db->begin();
        }

        $action_data['operation_title'] = 'Traitement des facturations des contrats d\'abonnement';
        $action_data['report_code'] = 'CONTRATS_LINES_FATURATION';
        $facs_lines = array();

        // Check des factures:
        $clients = BimpTools::getArrayValueFromPath($extra_data, 'clients', array());

        if (empty($clients)) {
            $errors[] = 'Aucune facturation à traiter';
        } else {
            $process->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');

            foreach ($clients as $id_client => $client_facs) {
                if (!$id_client || empty($client_facs)) {
                    continue;
                }

                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Le client #' . $id_client . ' n\'existe plus';
                } else {
                    foreach ($client_facs as $fac_idx => $fac_data) {
                        $lines = BimpTools::getArrayValueFromPath($fac_data, 'lines', array());

                        if (empty($lines)) {
                            continue;
                        }

                        $id_facture = (int) BimpTools::getArrayValueFromPath($fac_data, 'id_facture', 0);
                        if ((int) $id_facture > 0) {
                            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                            if (!BimpObject::objectLoaded($facture)) {
                                $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
                                continue;
                            } else {
                                if ((int) $facture->getData('fk_statut') != 0) {
                                    $errors[] = 'La facture n° ' . $facture->getRef() . ' pour le client ' . $client->getRef() . ' ' . $client->getName() . ' n\'est plus au statut brouillon';
                                    continue;
                                }
                            }
                        } else {
                            // Création de la facture :
                            $fac_errors = array();
                            $id_entrepot = (int) BimpTools::getArrayValueFromPath($fac_data, 'id_entrepot', 0, $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': entrepôt absent');
                            $secteur = BimpTools::getArrayValueFromPath($fac_data, 'secteur', '', $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': secteur absent');
                            $expertise = BimpTools::getArrayValueFromPath($fac_data, 'expertise', '', $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': expertise absente');
                            $id_mode_reglement = (int) BimpTools::getArrayValueFromPath($fac_data, 'id_mode_reglement', 0, $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': mode de réglement absent');
                            $id_cond_reglement = (int) BimpTools::getArrayValueFromPath($fac_data, 'id_cond_reglement', 0, $fac_errors, true, 'Facture n° ' . $fac_idx + 1 . ' pour le client ' . $client->getName() . ': conditions de réglement absentes');
                            $libelle = BimpTools::getArrayValueFromPath($fac_data, 'libelle', 'Facturation périodique');

                            if (!count($fac_errors)) {
                                $fac = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                                            'fk_soc'            => $id_client,
                                            'entrepot'          => $id_entrepot,
                                            'ef_type'           => $secteur,
                                            'expertise'         => $expertise,
                                            'libelle'           => $libelle,
                                            'fk_mode_reglement' => $id_mode_reglement,
                                            'fk_cond_reglement' => $id_cond_reglement,
                                            'datef'             => date('Y-m-d')
                                                ), true, $fac_errors);
                            }

                            if (count($fac_errors)) {
                                $msg = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture n° ' . ($fac_idx + 1) . ' pour le client "' . $client->getRef() . ' - ' . $client->getName() . '"');
                                $errors[] = $msg;
                                $process->Error($msg, $client);
                                continue;
                            } else {
                                $process->Success('Création de la facture n° ' . ($fac_idx + 1) . ' pour le client "' . $client->getRef() . ' - ' . $client->getName() . '" OK (Facture : ' . $fac->getLink() . ')', $client);
                                $process->incCreated();
                                $id_facture = $fac->id;
                            }
                        }

                        if ($id_facture) {
                            $facs_lines[$id_facture] = $fac_data['lines'];
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            // Ajout des lignes:

            $action_data['steps'] = array();
            $factures = array();
            foreach ($facs_lines as $id_facture => $fac_lines) {
                if (empty($fac_lines)) {
                    continue;
                }

                $elements = array();

                foreach ($fac_lines as $line_data) {
                    $elements[] = json_encode(array(
                        'id_line'    => (int) BimpTools::getArrayValueFromPath($line_data, 'id_line', 0),
                        'nb_periods' => (int) BimpTools::getArrayValueFromPath($line_data, 'nb_periods', 0),
                        'total_qty'  => (float) BimpTools::getArrayValueFromPath($line_data, 'total_qty', 0),
                        'subprice'   => (float) BimpTools::getArrayValueFromPath($line_data, 'subprice', 0),
                        'sub_lines'  => BimpTools::getArrayValueFromPath($line_data, 'sub_lines', array()),
                    ));
                }

                if (!empty($elements)) {
                    $factures[] = $id_facture;
                    $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                    $action_data['steps']['process_facture_' . $id_facture . '_lines'] = array(
                        'label'                  => 'Ajout des lignes à la la facture ' . $fac->getRef(),
                        'on_error'               => 'continue',
                        'elements'               => $elements,
                        'nbElementsPerIteration' => 10
                    );
                }
            }

            if (!empty($factures)) {
                $action_data['steps']['finalize_facs'] = array(
                    'label'                  => 'Vérifications et finalisation',
                    'on_error'               => 'stop',
                    'elements'               => $factures,
                    'nbElementsPerIteration' => 100
                );
            }
        }

        if ($use_db_transactions) {
            if (count($errors)) {
                $this->db->db->rollback();
            } else {
                $this->db->db->commit();
            }
        }
    }

    public function executeBdsActionPeriodicFacProcess($process, $step_name, $elements = array(), &$errors = array(), $operation_extra_data = array(), $action_extra_data = array())
    {
        if (empty($elements)) {
            $errors[] = 'Aucune ligne de commande client à traiter';
            return;
        }

        $use_db_transaction = (int) BimpCore::getConf('use_db_transactions');
        if ($use_db_transaction) {
            $this->db->db->commitAll();
        }

        switch ($step_name) {
            default:
                if (preg_match('/^process_facture_(\d+)_lines$/', $step_name, $matches)) {
                    $id_facture = (int) $matches[1];

                    if (!$id_facture) {
                        $errors[] = 'ID de la facture à traiter absent';
                    } else {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                        if (!BimpObject::objectLoaded($facture)) {
                            $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
                        } else {
                            $lines_data = array();

                            foreach ($elements as $element) {
                                $process->setCurrentObjectData('bimpcommercial', 'Bimp_FactureLine');
                                $line_data = json_decode($element, 1);

                                $id_line = BimpTools::getArrayValueFromPath($line_data, 'id_line', 0);
                                $nb_periods = (int) BimpTools::getArrayValueFromPath($line_data, 'nb_periods', 0);
                                $subprice = null;

                                if (!$id_line) {
                                    $process->incIgnored();
                                    $process->Alert('Une ligne ignorée (ID de la ligne de commande client absent)', $facture);
                                    continue;
                                }

                                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);
                                if (!BimpObject::objectLoaded($line)) {
                                    $process->incIgnored();
                                    $process->Error('La ligne de contrat d\'abonnement #' . $id_line . ' n\'existe plus', $facture);
                                    continue;
                                }

                                $contrat = $line->getParentInstance();
                                $line_ref = (BimpObject::objectLoaded($contrat) ? 'Contrat {{Contrat2:' . $contrat->id . '}} - Ligne n° ' . $line->getData('rang') : 'Ligne #' . $line->id);
                                $product = $line->getChildObject('product');

                                if (!BimpObject::objectLoaded($product)) {
                                    $process->incIgnored();
                                    if ((int) $line->id_product) {
                                        $process->Error('Le produit #' . $line->id_product . ' n\'existe plus', $facture, $line_ref);
                                        continue;
                                    }
                                } else {
                                    $line_ref .= ' - Produit ' . $product->getRef();
                                }

                                if (!$nb_periods) {
                                    $process->incIgnored();
                                    $process->Alert('Ligne ignorée (Aucune période à facturer)', $facture, $line_ref);
                                    continue;
                                }

                                // Check des qty:
                                $line_errors = array();
                                $line_periods_data = $line->getPeriodsToBillData($line_errors, true, true);

                                if (count($line_errors)) {
                                    $process->incIgnored();
                                    $process->Error($line_errors, $facture, $line_ref);
                                    continue;
                                }

                                // Check subprice variable: 
                                if ((int) $line->getData('variable_pu_ht')) {
                                    $subprice = BimpTools::getArrayValueFromPath($line_data, 'subprice', null);
                                    if (is_null($subprice)) {
                                        $process->Alert('ATTENTION : Prix de vente non défini', $facture, $line_ref);
                                    } else {
                                        $subprice = (float) $subprice;
                                    }
                                }

                                $qty = 0;
                                if ((int) $line->getData('variable_qty')) {
                                    $qty = (float) BimpTools::getArrayValueFromPath($line_data, 'total_qty', 0);
                                } else {
                                    $qty_per_period = $line->getFacQtyPerPeriod();

                                    if ($line_periods_data['date_next_period_tobill'] == $line_periods_data['date_first_period_start'] &&
                                            $line_periods_data['first_period_prorata'] != 1) {
                                        $qty = $qty_per_period * (float) $line_periods_data['first_period_prorata'];

                                        if ($nb_periods > 1) {
                                            $qty += $qty_per_period * ($nb_periods - 1);
                                        }
                                    } else {
                                        $qty = $qty_per_period * $nb_periods;
                                    }
                                }

                                if (!$qty) {
                                    $process->incIgnored();
                                    $process->Alert('Ligne ignorée (Aucune quantité à facturer)', $facture, $line_ref);
                                    continue;
                                }

                                $qty = round($qty, 6);

                                if (!(int) $line_periods_data['nb_periods_tobill_max']) {
                                    $process->incIgnored();
                                    $msg = 'Il ne reste plus de période à facturer pour cette ligne de commande client';
                                    $msg .= '<pre>' . print_r($line_periods_data, 1) . '</pre>';
                                    $process->Error($msg, $facture, $line_ref);
                                    continue;
                                }

                                if ($nb_periods > $line_periods_data['nb_periods_tobill_max']) {
                                    $msg = 'Il ne reste que ' . $line_periods_data['nb_periods_tobill_max'] . ' période(s) à facturer (' . ($line_periods_data['nb_periods_tobill_max'] * $line_periods_data['qty_for_1_period']) . ' unité(s))';
                                    $process->Alert($msg, $facture, $line_ref);
                                    $nb_periods = $line_periods_data['nb_periods_tobill_max'];
                                }

                                $list = $this->getList(array(
                                    'id_parent_line' => $id_line
                                        ), null, null, 'rang', 'asc', 'array', array('rowid'));

                                $has_sub_line_error = false;
                                $sub_lines_data = array();

                                if (!empty($list)) {
                                    foreach ($list as $item) {
                                        $sub_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $item['rowid']);

                                        if (BimpObject::objectLoaded($sub_line)) {
                                            $sub_line_errors = array();
                                            $sub_line_periods_data = $line->getPeriodsToBillData($sub_line_errors, true, true);

                                            if (count($sub_line_errors)) {
                                                $process->incIgnored();
                                                $process->Error($sub_line_errors, $facture, $line_ref . ' (Sous-ligne n°' . $sub_line->getData('rang') . ')');
                                                $has_sub_line_error = true;
                                                continue;
                                            }

                                            if (!(int) $sub_line_periods_data['nb_periods_tobill_max']) {
                                                $process->incIgnored();
                                                $msg = 'Il ne reste plus de période à facturer pour cette ligne de commande client';
                                                $msg .= '<pre>' . print_r($sub_line_periods_data, 1) . '</pre>';
                                                $process->Error($msg, $facture, $line_ref . ' (Sous-ligne n°' . $sub_line->getData('rang') . ')');
                                                $has_sub_line_error = true;
                                                continue;
                                            }

                                            if ($nb_periods > $sub_line_periods_data['nb_periods_tobill_max']) {
                                                $msg = 'Il ne reste que ' . $sub_line_periods_data['nb_periods_tobill_max'] . ' période(s) à facturer (' . ($sub_line_periods_data['nb_periods_tobill_max'] * $sub_line_periods_data['qty_for_1_period']) . ' unité(s))';
                                                $process->Error($msg, $facture, $line_ref . ' (Sous-ligne n°' . $sub_line->getData('rang') . ')');
                                                $has_sub_line_error = true;
                                                continue;
                                            }

                                            $sub_line_qty = 0;
                                            $sub_line_qty_per_period = 0;

                                            if (isset($line_data['sub_lines'][$sub_line->id]['total_qty'])) {
                                                $sub_line_qty = $line_data['sub_lines'][$sub_line->id]['total_qty'];
                                            } else {
                                                $sub_line_qty_per_period = $sub_line->getFacQtyPerPeriod();

                                                if ($sub_line_periods_data['date_next_period_tobill'] == $sub_line_periods_data['date_first_period_start'] &&
                                                        $sub_line_periods_data['first_period_prorata'] != 1) {
                                                    $sub_line_qty = $sub_line_qty_per_period * (float) $sub_line_periods_data['first_period_prorata'];

                                                    if ($nb_periods > 1) {
                                                        $sub_line_qty += $sub_line_qty_per_period * ($nb_periods - 1);
                                                    }
                                                } else {
                                                    $sub_line_qty = $sub_line_qty_per_period * $nb_periods;
                                                }
                                            }

                                            if (!$sub_line_qty) {
                                                $process->incIgnored();
                                                $process->Alert('Ligne ignorée (Aucune quantité à facturer)', $facture, $line_ref . ' (Sous-ligne n°' . $sub_line->getData('rang') . ')');
                                                continue;
                                            }

                                            $sub_line_qty = round($sub_line_qty, 6);
                                            $sub_lines_data[$sub_line->id] = array(
                                                'nb_periods' => $nb_periods,
                                                'qty'        => $sub_line_qty,
                                                'editable'   => 0
                                            );

                                            if ((int) $sub_line->getData('variable_pu_ht')) {
                                                $sub_lines_data[$sub_line->id]['subprice'] = BimpTools::getArrayValueFromPath($line_data, 'sub_lines/' . $sub_line->id . '/subprice', null);
                                                $sub_lines_data[$sub_line->id]['editable'] = 1;

                                                if (is_null($sub_lines_data[$sub_line->id]['subprice'])) {
                                                    $process->Alert('ATTENTION : Prix de vente non défini', $facture, $line_ref . ' (Sous-ligne n°' . $sub_line->getData('rang') . ')');
                                                } else {
                                                    $sub_lines_data[$sub_line->id]['subprice'] = (float) $sub_lines_data[$sub_line->id]['subprice'];
                                                }
                                            }
                                        }
                                    }
                                }
                                if (!$has_sub_line_error) {
                                    $lines_data[$id_line] = array(
                                        'nb_periods' => $nb_periods,
                                        'qty'        => $qty,
                                        'editable'   => ($line->getData('variable_pu_ht') ? 1 : 0)
                                    );

                                    if (!is_null($subprice)) {
                                        $lines_data[$id_line]['subprice'] = $subprice;
                                    }

                                    foreach ($sub_lines_data as $id_sub_line => $sub_line_data) {
                                        $lines_data[$id_sub_line] = $sub_line_data;
                                    }
                                }
                            }

                            if (!empty($lines_data)) {
                                // Ajout des lignes à la facture :
                                $contrat_instance = BimpObject::getInstance('bimpcontrat', 'BCT_Contrat');
                                $nOk = 0;
                                $lines_errors = $contrat_instance->addLinesToFacture($id_facture, $lines_data, true, true, $nOk);

                                if (count($lines_errors)) {
                                    $process->incIgnored('current', count($lines_errors));
                                    $process->Error(BimpTools::getMsgFromArray($lines_errors), $facture);
                                }

                                if ($nOk > 0) {
                                    $process->incCreated('current', $nOk);
                                    $process->Success($nOk . ' ligne(s) traitée(s) avec succès', $facture);
                                }
                            }
                        }
                    }
                } else {
                    $errors[] = 'Etape invalide';
                }
                break;

            case 'finalize_facs':
                $facs_ok = array();
                $process->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');
                foreach ($elements as $id_facture) {
                    $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                    if (BimpObject::objectLoaded($fac) && (int) $fac->getData('fk_statut') == 0) {
                        if ($this->db->getCount('facturedet a', 'a.fk_facture = ' . $id_facture . ' AND fl.linked_object_name != \'contrat_origin_label\'', 'rowid', array(
                                    'fl' => array('table' => 'bimp_facture_line', 'on' => 'fl.id_line = a.rowid')
                                )) == 0) {
                            if ($use_db_transaction) {
                                $this->db->db->begin();
                            }

                            $fac_warnings = array();
                            $fac_errors = $fac->delete($fac_warnings, true);

                            if (!count($fac_errors)) {
                                $process->Alert('Aucune ligne ajoutée à la facture #' . $id_facture . '. Celle-ci a été supprimée');
                                $process->incDeleted();

                                if ($use_db_transaction) {
                                    $this->db->db->commit();
                                }

                                continue;
                            } else {
                                if ($use_db_transaction) {
                                    $this->db->db->rollback();
                                }
                            }
                        }
                    }

                    $facs_ok[] = $id_facture;
                }

                if (!empty($facs_ok)) {
                    $s = (count($facs_ok) > 1 ? 's' : '');
                    $msg = count($facs_ok) . ' facture' . $s . ' traitée' . $s . ' avec succès.<br/>';

                    foreach ($facs_ok as $id_fac) {
                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

                        $sql = BimpTools::getSqlFullSelectQuery('bimp_facture_line', array('DISTINCT cl.fk_contrat as id_contrat'), array(
                                    'a.id_obj'             => $id_fac,
                                    'a.linked_object_name' => 'contrat_line'
                                        ), array(
                                    'cl' => array(
                                        'table' => 'contratdet',
                                        'on'    => 'cl.rowid = a.linked_id_object'
                                    )
                        ));

                        $contrats = $this->db->executeS($sql, 'array');

                        // Liens contrats: 
                        if (!empty($contrats)) {
                            foreach ($contrats as $c) {
                                addElementElement('bimp_contrat', 'facture', $c['id_contrat'], $id_fac);
                            }
                        }

                        // Contacts: 
                        $contacts = array();
                        $users = array();
                        if (!empty($contrats)) {
                            $client = $fac->getChildObject('client');
                            foreach ($contrats as $c) {
                                $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', (int) $c['id_contrat']);
                                if (BimpObject::objectLoaded($contrat)) {
                                    $ct_contacts = $contrat->getContactsByCodes('external');
                                    $ct_users = $contrat->getContactsByCodes('internal');

                                    foreach ($ct_contacts as $code => $contact_ids) {
                                        if (!isset($contacts[$code])) {
                                            $contacts[$code] = array();
                                        }

                                        foreach ($contact_ids as $id_contact) {
                                            if (!in_array($id_contact, $contacts[$code])) {
                                                $contacts[$code][] = $id_contact;
                                            }
                                        }
                                    }

                                    foreach ($ct_users as $code => $users_ids) {
                                        if (!isset($users[$code])) {
                                            $users[$code] = array();
                                        }

                                        foreach ($users_ids as $id_user) {
                                            if (!in_array($id_user, $users[$code])) {
                                                $users[$code][] = $id_user;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if (BimpObject::objectLoaded($client)) {
                            if (!isset($contacts['BILLING2']) || empty($contacts['BILLING2'])) {
                                // On récupère le contact e-mail facturation par défaut de la fiche client : 
                                $id_def_contact = (int) $client->getData('contact_default');
                                if ($id_def_contact) {
                                    $contacts['BILLING2'] = array($id_def_contact);
                                }
                            }

                            if (!isset($users['SALESREPFOLL']) || empty($users['SALESREPFOLL'])) {
                                // On récupère le commerical du client : 
                                $id_def_commercial = (int) $client->getCommercial(false);
                                if ($id_def_commercial) {
                                    $users['SALESREPFOLL'] = array($id_def_commercial);
                                }
                            }
                        }

                        $fac_contacts = $fac->getContactsByCodes('external');
                        $fac_users = $fac->getContactsByCodes('internal');

                        foreach ($contacts as $code => $contacts_ids) {
                            foreach ($contacts_ids as $id_contact) {
                                if (!isset($fac_contacts[$code]) || !in_array($id_contact, $fac_contacts[$code])) {
                                    $fac->dol_object->add_contact($id_contact, $code, 'external');
                                    $fac_contacts[$code][] = $id_contact;
                                }
                            }
                        }

                        foreach ($users as $code => $users_ids) {
                            foreach ($users_ids as $id_user) {
                                if (!isset($fac_users[$code]) || !in_array($id_user, $fac_users[$code])) {
                                    $fac->dol_object->add_contact($id_user, $code, 'internal');
                                    $fac_users[$code][] = $id_user;
                                }
                            }
                        }

                        $msg .= '<br/>' . $fac->getLink();
                    }

                    $process->Success($msg);
                }
                break;
        }
    }

    public function initBdsActionPeriodicAchatProcess($process, &$action_data = array(), &$errors = array(), $extra_data = array())
    {
        $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');
        if ($use_db_transactions) {
            $this->db->db->begin();
        }

        $action_data['operation_title'] = 'Traitement des achats des contrats d\'abonnement';
        $action_data['report_code'] = 'CONTRATS_LINES_ACHATS';

        // Check des commandes fourns:
        $fourns = BimpTools::getArrayValueFromPath($extra_data, 'fourns', array());
        if (empty($fourns)) {
            $errors[] = 'Aucune commande fournisseur spécifiée';
        } else {
            $process->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeFourn');

            foreach ($fourns as $id_fourn => $entrepots) {
                if (!$id_fourn || empty($entrepots)) {
                    continue;
                }

                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                if (!BimpObject::objectLoaded($fourn)) {
                    $errors[] = 'Le fournisseur #' . $id_fourn . ' n\'existe plus';
                } else {
                    foreach ($entrepots as $id_entrepot => $entrepot_data) {
                        $lines = BimpTools::getArrayValueFromPath($entrepot_data, 'lines', array());
                        if (empty($lines)) {
                            continue;
                        }

                        $id_cf = (int) BimpTools::getArrayValueFromPath($entrepot_data, 'id_commande_fourn', 0);
                        if ($id_cf != 'new' && (int) $id_cf > 0) {
                            $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                            if (!BimpObject::objectLoaded($cf)) {
                                $errors[] = 'La commande fournisseur #' . $id_cf . ' n\'existe plus';
                            } else {
                                if ((int) $cf->getData('fk_statut') != 0) {
                                    $errors[] = 'La commande fournisseur ' . $cf->getLink() . ' n\'est plus au statut brouillon';
                                }
                            }
                        } else {
                            // Création de la CF : 
                            $cf_errors = array();
                            $cf = BimpObject::createBimpObject('bimpcommercial', 'Bimp_CommandeFourn', array(
                                        'fk_soc'   => $id_fourn,
                                        'entrepot' => $id_entrepot,
                                        'ef_type'  => 'C',
                                        'libelle'  => 'Achats abonnements'
                                            ), true, $cf_errors);

                            if (count($cf_errors)) {
                                $msg = BimpTools::getMsgFromArray($cf_errors, 'Echec de la création de la commande pour le fournisseur "' . $fourn->getName() . '"');
                                $errors[] = $msg;
                                $process->Error($msg, $fourn);
                            } else {
                                $fourns[$id_fourn][$id_entrepot]['id_commande_fourn'] = (int) $cf->id;
                                $process->Success('Création de la CF pour le fournisseur "' . $fourn->getName() . '" OK', $fourn);
                                $process->incCreated();
                            }
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            // Ajout des lignes:

            $action_data['steps'] = array();
            $commandes = array();

            foreach ($fourns as $id_fourn => $entrepots) {
                foreach ($entrepots as $id_entrepot => $entrepot_data) {
                    $lines = BimpTools::getArrayValueFromPath($entrepot_data, 'lines', array());
                    $id_cf = (int) BimpTools::getArrayValueFromPath($entrepot_data, 'id_commande_fourn', 0);

                    if (!$id_fourn || !$id_cf || empty($lines)) {
                        continue;
                    }
                }

                $elements = array();

                foreach ($lines as $line_data) {
                    $elements[] = json_encode(array(
                        'id_line'    => (int) BimpTools::getArrayValueFromPath($line_data, 'id_line', 0),
                        'nb_periods' => (int) BimpTools::getArrayValueFromPath($line_data, 'nb_periods', 0),
                        'total_qty'  => (float) BimpTools::getArrayValueFromPath($line_data, 'total_qty', 0),
                        'pa_ht'      => (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht', 0),
                    ));
                }

                if (!empty($elements)) {
                    if (!in_array($id_cf, $commandes)) {
                        $commandes[] = $id_cf;
                    }

                    $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                    $action_data['steps']['process_cf_' . $id_cf . '_lines'] = array(
                        'label'                  => 'Ajout des lignes à la CF pour le fournisseur "' . $fourn->getName() . '"',
                        'on_error'               => 'continue',
                        'elements'               => $elements,
                        'nbElementsPerIteration' => 10
                    );
                }
            }

            if (!empty($commandes)) {
                $action_data['steps']['finalize_achats'] = array(
                    'label'                  => 'Vérifications et finalisation',
                    'on_error'               => 'stop',
                    'elements'               => $commandes,
                    'nbElementsPerIteration' => 100
                );
            }
        }

        if ($use_db_transactions) {
            if (count($errors)) {
                $this->db->db->rollback();
            } else {
                $this->db->db->commit();
            }
        }
    }

    public function executeBdsActionPeriodicAchatProcess($process, $step_name, $elements = array(), &$errors = array(), $operation_extra_data = array(), $action_extra_data = array())
    {
        if (empty($elements)) {
            $errors[] = 'Aucune ligne de commande client à traiter';
            return;
        }

        $use_db_transaction = (int) BimpCore::getConf('use_db_transactions');
        if ($use_db_transaction) {
            $this->db->db->commitAll();
        }

        switch ($step_name) {
            default:
                if (preg_match('/^process_cf_(\d+)_lines$/', $step_name, $matches)) {
                    $id_cf = (int) $matches[1];

                    if (!$id_cf) {
                        $errors[] = 'ID de la commande fournisseur à traiter absent';
                    } else {
                        $id_fourn = (int) $this->db->getValue('commande_fournisseur', 'fk_soc', 'rowid = ' . $id_cf);
                        $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                        if (!BimpObject::objectLoaded($cf)) {
                            $errors[] = 'La commande fournisseur #' . $id_cf . ' n\'existe plus';
                        } else {
                            $lines_data = array();

                            foreach ($elements as $element) {
                                $process->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeFournLine');
                                $line_data = json_decode($element, 1);

                                $id_line = BimpTools::getArrayValueFromPath($line_data, 'id_line', 0);
                                $nb_periods = (int) BimpTools::getArrayValueFromPath($line_data, 'nb_periods', 0);
                                $total_qty = (float) BimpTools::getArrayValueFromPath($line_data, 'total_qty', 0);
                                $pa_ht = (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht', 0);

                                if (!$id_line) {
                                    $process->incIgnored();
                                    $process->Alert('Une ligne ignorée (ID de la ligne de contrat absent)', $cf);
                                    continue;
                                }

                                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);
                                if (!BimpObject::objectLoaded($line)) {
                                    $process->incIgnored();
                                    $process->Error('La ligne de contrat #' . $id_line . ' n\'existe plus', $cf);
                                    continue;
                                }

                                $contrat = $line->getParentInstance();
                                $line_ref = (BimpObject::objectLoaded($contrat) ? '{{Contrat2: ' . $contrat->id . '}} - Ligne n° ' . $line->getData('position') : 'Ligne #' . $line->id);
                                $product = $line->getChildObject('product');

                                if (!BimpObject::objectLoaded($product)) {
                                    $process->incIgnored();
                                    $id_product = (int) $line->getData('fk_product');
                                    if ($id_product) {
                                        $process->Error('Le produit #' . $id_product . ' n\'existe plus', $cf, $line_ref);
                                    } else {
                                        $process->Error('Aucun produit pour cette ligne de contrat', $cf, $line_ref);
                                    }
                                    continue;
                                }

                                $line_ref .= ' - Produit ' . $product->getRef();

                                if (!$nb_periods) {
                                    $process->incIgnored();
                                    $process->Alert('Ligne ignorée (Aucune période à commander)', $cf, $line_ref);
                                    continue;
                                }

                                // Check des qty:
                                $line_periods_data = $line->getPeriodsToBuyData();

                                if (!(int) $line_periods_data['nb_periods_tobuy_max']) {
                                    $process->incIgnored();
                                    $msg = 'Il ne reste plus de période à acheter pour cette ligne de commande client';
                                    $process->Error($msg, $cf, $line_ref);
                                    continue;
                                } elseif ($nb_periods > $line_periods_data['nb_periods_tobuy_max']) {
                                    $msg = 'Il ne reste que ' . $line_periods_data['nb_periods_tobuy_max'] . ' période(s) à acheter (' . ($line_periods_data['nb_periods_tobuy_max'] * $line_periods_data['qty_for_1_period']) . ' unité(s))';
                                    $process->Alert($msg, $cf, $line_ref);
                                    $nb_periods = $line_periods_data['nb_periods_tobuy_max'];
                                }

                                $qty = 0;
                                if ((int) $line->getData('variable_qty')) {
                                    $qty = $total_qty;
                                } else {
                                    $qty_per_period = $line->getAchatQtyPerPeriod();

                                    if ($line_periods_data['date_next_achat'] == $line_periods_data['date_achat_start'] &&
                                            $line_periods_data['first_period_prorata'] != 1) {
                                        $qty = $qty_per_period * (float) $line_periods_data['first_period_prorata'];

                                        if ($nb_periods > 1) {
                                            $qty += $qty_per_period * ($nb_periods - 1);
                                        }
                                    } else {
                                        $qty = $qty_per_period * $nb_periods;
                                    }
                                }

                                if (!$qty) {
                                    $process->incIgnored();
                                    $process->Alert('Aucune unité à commander', $cf, $line_ref);
                                    continue;
                                }

                                $qty = round($qty, 6);

                                $lines_data[$id_line] = array(
                                    'id_fourn'   => $id_fourn,
                                    'nb_periods' => $nb_periods,
                                    'qty'        => $qty,
                                    'pa_ht'      => $pa_ht,
                                );
                            }

                            if (!empty($lines_data)) {
                                // Ajout des lignes à la commande fourn :
                                $contrat_instance = BimpObject::getInstance('bimpcontrat', 'BCT_Contrat');
                                $nOk = 0;
                                $lines_errors = $contrat_instance->addLinesToCommandeFourn($id_cf, $lines_data, true, true, $nOk);

                                if (count($lines_errors)) {
                                    $process->incIgnored('current', count($lines_errors));
                                    $process->Error(BimpTools::getMsgFromArray($lines_errors), $cf);
                                }

                                if ($nOk > 0) {
                                    $process->incCreated('current', $nOk);
                                    $process->Success($nOk . ' ligne(s) traitée(s) avec succès', $cf);
                                }
                            }
                        }
                    }
                } else {
                    $errors[] = 'Etape invalide';
                }
                break;

            case 'finalize_achats':
                $commandes_ok = array();
                $process->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeFourn');
                foreach ($elements as $id_cf) {
                    $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                    if (BimpObject::objectLoaded($cf) && (int) $cf->getData('fk_statut') == 0) {
                        if ($this->db->getCount('commande_fournisseurdet', 'fk_commande = ' . $id_cf, 'rowid') == 0) {
                            if ($use_db_transaction) {
                                $this->db->db->begin();
                            }

                            $cf_warnings = array();
                            $cf_errors = $cf->delete($cf_warnings, true);

                            if (!count($cf_errors)) {
                                $process->Alert('Aucune ligne ajoutée à la commande fournisseur #' . $id_cf . '. Celle-ci a été supprimée');
                                $process->incDeleted();

                                if ($use_db_transaction) {
                                    $this->db->db->commit();
                                }

                                continue;
                            } else {
                                if ($use_db_transaction) {
                                    $this->db->db->rollback();
                                }
                            }
                        }
                    }

                    $commandes_ok[] = $id_cf;
                }

                if (!empty($commandes_ok)) {
                    $s = (count($commandes_ok) > 1 ? 's' : '');
                    $msg = count($commandes_ok) . ' commande' . $s . ' fournisseur' . $s . ' traitée' . $s . ' avec succès.<br/>';

                    foreach ($commandes_ok as $id_cf) {
                        $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                        $msg .= '<br/>' . $cf->getLink();
                    }

                    $process->Success($msg);
                }
                break;
        }
    }

    // Overrides:

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (!count($errors)) {
            switch ((int) $this->getData('line_type')) {
                case self::TYPE_ABO:
                    $fac_periodicity = (int) $this->getData('fac_periodicity');

                    if (!$fac_periodicity) {
                        $errors[] = 'Veuillez sélectionner la périodicité de facturation';
                    }

                    if (BimpTools::isPostFieldSubmit('qty_per_period')) {
                        $qty_per_period = (float) BimpTools::getPostFieldValue('qty_per_period', 0, 'float');

                        if (!$qty_per_period) {
                            $errors[] = 'Veuillez définir une quantité par période';
                        }

                        if (!count($errors)) {
                            $duration = (int) $this->getData('duration');

                            if ($duration % $fac_periodicity != 0) {
                                $errors[] = 'La durée totale doit être un multiple du nombre de mois correspondant à la périodicité de facturation (' . $fac_periodicity . ' mois)';
                            } else {
                                $nb_periods = $duration / $fac_periodicity;
                                $this->set('qty', $qty_per_period * $nb_periods);
                            }
                        }
                    }
                    break;
            }
        }

        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $prod = $this->getChildObject('product');

            switch ($this->getData('line_type')) {
                case self::TYPE_TEXT:
                    $this->validateArray(array(
                        'fk_product'                   => 0,
                        'qty'                          => 1,
                        'statut'                       => 0,
                        'price_ht'                     => 0,
                        'subprice'                     => 0,
                        'tva_tx'                       => 0,
                        'remise_percent'               => 0,
                        'buy_price_ht'                 => 0,
                        'fk_product_fournisseur_price' => 0,
                        'duration'                     => 0,
                        'variable_qty'                 => 0,
                        'fac_periodicity'              => 0,
                        'achat_periodicity'            => 0,
                        'nb_renouv'                    => 0,
                        'fac_term'                     => 0
                    ));
                    break;

                case self::TYPE_ABO:
                    $this->checkLinkedLine($errors);

                    if (!count($errors)) {
                        $is_bundle = false;
                        $fac_periodicity = (int) $this->getData('fac_periodicity');
                        $achat_periodicity = (int) $this->getData('achat_periodicity');

                        if ((int) $this->getData('fk_product')) {
                            $prod = $this->getChildObject('product');

                            if (!BimpObject::objectLoaded($prod)) {
                                $errors[] = 'Le produit #' . $this->getData('fk_product') . ' n\'existe plus';
                            } else {
                                $prod->isVendable($errors);

                                if (!$prod->isAbonnement()) {
                                    $errors[] = 'Le produit ' . $prod->getRef() . ' n\'est pas de type abonnement';
                                }

                                if (!$this->isLoaded() || (int) $this->getData('statut') == self::STATUS_INACTIVE) {
                                    if ($fac_periodicity && !(int) $prod->getData('tosell')) {
                                        $errors[] = 'Le produit ' . $prod->getRef() . ' n\'est pas en vente';
                                    }

//                                    if ($achat_periodicity && !(int) $prod->getData('tobuy')) {
//                                        $errors[] = 'Les achats du produit ' . $prod->getRef() . ' sont désactivés';
//                                    }
                                }

                                $is_bundle = $prod->isBundle();
                            }
                        } elseif ($this->getData('linked_object_name') !== 'bundleCorrect') {
                            $errors[] = 'Aucun produit sélectionné';
                        }

                        if ($fac_periodicity) {
                            $prod_duration = (int) $prod->getData('duree');
                            $duration = (int) $this->getData('duration');

                            if (!$duration || !$prod_duration) {
                                if (!$duration) {
                                    $errors[] = 'Durée de l\'abonnement non définie';
                                }
                                if (!$prod_duration && $this->getData('linked_object_name') !== 'bundleCorrect') {
                                    $errors[] = 'Durée unitaire du produit non définie';
                                }
                            } else {
                                if ($duration % $fac_periodicity != 0) {
                                    $errors[] = 'La durée totale de l\'abonnement doit être un multiple du nombre de mois correspondant à la périodicité de facturation (' . $fac_periodicity . ' mois)';
                                }
                                if ($duration % $prod_duration != 0) {
                                    $errors[] = 'La durée totale de l\'abonnement doit être un multiple de la durée unitaire de produit (' . $prod_duration . ' mois)';
                                }
                                if ($duration < $prod_duration) {
                                    $errors[] = 'La durée totale de l\'abonnement ne peut pas être inférieure à la durée unitaire de produit (' . $prod_duration . ' mois)';
                                }
                            }
                        }

                        if ($is_bundle || $this->getData('linked_object_name') === 'bundleCorrect') {
                            $this->set('achat_periodicity', 0);
                        } else {
                            if ($achat_periodicity && $fac_periodicity && $fac_periodicity < $achat_periodicity) {
                                $errors[] = 'La périodicité de facturation ne peut pas être inférieure à la périodicité d\'achat';
                            }
                        }

                        if ($is_bundle || (int) $this->getData('id_parent_line')) {
                            $this->set('variable_qty', 0);
                        }
                    }
                    break;
            }

            if ((int) $this->getData('fk_product')) {
                if (BimpObject::objectLoaded($prod)) {
                    if (is_null($this->getData('subprice'))) {
                        $this->set('subprice', $this->getValueForProduct('subprice', $prod));
                    }
                    if (is_null($this->getData('tva_tx'))) {
                        $this->set('tva_tx', $this->getValueForProduct('tva_tx', $prod));
                    }
                    if (is_null($this->getData('fk_product_fournisseur_price'))) {
                        $this->set('fk_product_fournisseur_price', $this->getValueForProduct('fk_product_fournisseur_price', $prod));
                    }
                    if (is_null($this->getData('buy_price_ht'))) {
                        $this->set('buy_price_ht', $this->getValueForProduct('buy_price_ht', $prod));
                    }
                }
            }
        }

        return $errors;
    }

    public function createDolObject(&$errors = array(), &$warnings = array())
    {
        $contrat = $this->getParentInstance();

        if (!BimpObject::objectLoaded($contrat)) {
            $errors[] = 'Contrat non défini';
            return 0;
        }

        if (!is_null($this->dol_object) && isset($this->dol_object->id) && $this->dol_object->id) {
            unset($this->dol_object);
            $this->dol_object = null;
        }

        $id = 0;
        $this->noFetchOnTrigger = true;

        $fields = $this->getDbData();
        $bimpObjectFields = array();
        $extrafields = array();

        foreach ($fields as $field_name => $value) {
            if (!in_array($field_name, self::$dol_fields)) {
                if ($this->isDolExtraField($field_name)) {
                    $extrafields['options_' . $field_name] = $value;
                } else {
                    $bimpObjectFields[$field_name] = $value;
                }
            }
        }

        if (!count($errors)) {
            $statut = (int) $this->getData('statut');
            $desc = $this->getData('description');
            $pu_ht = (float) $this->getData('subprice');
            $qty = (float) $this->getData('qty');
            $txtva = (float) $this->getData('tva_tx');
            $fk_product = (int) $this->getData('fk_product');
            $remise_percent = (float) $this->getData('remise_percent');
            $date_start = $this->getData('date_ouverture_prevue');
            $date_end = $this->getData('date_fin_validite');
            $fk_fournprice = (int) $this->getData('fk_product_fournisseur_price');
            $pa_ht = (float) $this->getData('buy_price_ht');

            $result = $contrat->dol_object->addline($desc, $pu_ht, $qty, $txtva, 0, 0, $fk_product, $remise_percent, $date_start, $date_end, 'HT', 0.0, 0, $fk_fournprice, $pa_ht, $extrafields);
            if ($result <= 0) {
                if (isset($contrat->dol_object->error) && $contrat->dol_object->error) {
                    $errors[] = $contrat->dol_object->error;
                } elseif (count($contrat->dol_object->errors)) {
                    global $langs;
                    $langs->load("errors");
                    foreach ($this->$contrat->errors as $error) {
                        $errors[] = 'Erreur: ' . $langs->trans($error);
                    }
                }
            } else {
                $id = $result;
                $this->dol_object = $this->config->getObject('dol_object');
                $this->dol_object->fetch($id);
                $this->hydrateFromDolObject();

                if (!empty($bimpObjectFields)) {
                    if ($statut !== self::STATUS_INACTIVE) {
                        $this->set('statut', $statut);
                        $this->dol_object->statut = $statut;
                        $bimpObjectFields['statut'] = $statut;
                    }
                    $up_result = $this->db->update('contratdet', $bimpObjectFields, '`rowid` = ' . (int) $id);

                    if ($up_result <= 0) {
                        $msg = 'Echec de l\'insertion des champs additionnels';
                        $sql_errors = $this->db->db->lasterror;
                        if ($sql_errors) {
                            $msg .= ' - Erreur SQL: ' . $sql_errors;
                        }

                        $errors[] = $msg;
                    }
                } elseif ($statut !== self::STATUS_INACTIVE) {
                    $this->set('statut', $statut);
                    $this->dol_object->statut = $statut;
                    $this->db->update('contratdet', array('statut' => $statut), '`rowid` = ' . (int) $id);
                }
            }
        }

        $this->noFetchOnTrigger = false;
        return $id;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = $this->id;
        $id_contrat = (int) $this->getData('fk_contrat');

        $id_line_origin = (int) $this->getData('id_line_origin');
        $type_origine = $this->getData('line_origin_type');

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            $sub_lines = BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                        'fk_contrat'     => $id_contrat,
                        'id_parent_line' => $id
            ));

            if (!empty($sub_lines)) {
                foreach ($sub_lines as $sub_line) {
                    $line_warnings = array();
                    $line_err = $sub_line->delete($line_warnings, true);

                    if (count($line_err)) {
                        $errors[] = BimpTools::getMsgFromArray($line_err, 'Echec de la suppression d\'une sous-ligne du bundle (Ligne n°' . $sub_line->getData('rang') . ')');
                    }
                }
            }

            $this->db->update('contratdet', array(
                'id_line_renouv' => 0
                    ), 'id_line_renouv = ' . $id);

            if ($id_line_origin && $type_origine === 'propal_line') {
                $id_propal = (int) $this->db->getValue('bimp_propal_line', 'id_obj', 'id = ' . $id_line_origin);

                if ($id_propal) {
                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_propal);
                    if (BimpObject::objectLoaded($propal)) {
                        $this->checkProcessesStatus();
                    }
                }
            }
        }

        return $errors;
    }
}

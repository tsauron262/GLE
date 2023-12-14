<?php

class BCT_ContratLine extends BimpObject
{

    const TYPE_TEXT = 1;
    const TYPE_ABO = 2;

    public static $types = array(
        self::TYPE_ABO  => array('label' => 'Abonnement', 'icon' => 'fas_calendar-alt'),
        self::TYPE_TEXT => array('label' => 'Texte', 'icon' => 'fas_align-left')
    );

    const STATUS_NONE = -1;
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 4;
    const STATUS_CLOSED = 5;

    public static $status_list = array(
        self::STATUS_NONE     => array('label' => 'Non Applicable'),
        self::STATUS_INACTIVE => array('label' => 'Inactif', 'icon' => 'fas_times', 'classes' => array('warning')),
        self::STATUS_ACTIVE   => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success')),
        self::STATUS_CLOSED   => array('label' => 'Fermé', 'icon' => 'fas_times-circle', 'classes' => array('danger')),
    );
    public static $periodicities = array(
        0  => 'Aucune',
        1  => 'Mensuelle',
        2  => 'Bimensuelle',
        3  => 'Trimestrielle',
        4  => 'Quadrimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1  => 'Mensuel',
        2  => 'Bimensuel',
        3  => 'Trimestriel',
        4  => 'Quadrimestriel',
        6  => 'Semestriel',
        12 => 'Annuel'
    );
    public static $dol_fields = array('fk_contrat', 'fk_product', 'label', 'description', 'commentaire', 'statut', 'qty', 'price_ht', 'subprice', 'tva_tx', 'remise_percent', 'remise', 'fk_product_fournisseur_price', 'buy_price_ht', 'total_ht', 'total_tva', 'total_ttc', 'date_commande', 'date_ouverture_prevue', 'date_fin_validite', 'date_cloture', 'fk_user_author', 'fk_user_ouverture', 'fk_user_cloture');
    protected $data_at_date = null;

    // Droits User:

    public function canSetAction($action)
    {
        global $user;
        switch ($action) {
            case 'facturationAvance':
            case 'activate':
                return (int) !empty($user->rights->bimpcontract->to_validate);

            case 'periodicFacProcess':
                return 1;

            case 'periodicAchatProcess':
                return 1;

            case 'deactivate':
                return ($user->admin ? 1 : 0);
        }
        return parent::canSetAction($action);
    }

    public function canEditField($field_name)
    {
//        $status = (int) $this->getData('statut');
//        if (in_array($field_name, array(''))) {
//            
//        }

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
        if ((int) $this->getData('statut') <= 0) {
            return 1;
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($this->isLoaded() && in_array($field, array('line_type'))) {
            return 0;
        }

        $status = (int) $this->getData('statut');

        if ($status > 0 && in_array($field, array('fk_product', 'qty', 'price_ht', 'subprice', 'tva_tx', 'remise_percent', 'fac_periodicity', 'duration', 'variable_qty', 'date_fac_start', 'date_achat_start'))) {
            return 0;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        $contrat = null;

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

                if ((int) $contrat->getData('statut') <= 0) {
                    $errors[] = 'Le contrat n\'est pas validé';
                    return 0;
                }

                if (!$this->isValide($errors)) {
                    return 0;
                }
                return 1;

            case 'deactivate':
                if ($status == 0) {
                    $errors[] = 'Cette ligne de contrat est déjà désactivée';
                    return 0;
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

        if ($this->getData('date_fin_validite') < date('Y-m-d H:i:s')) {
            return 0;
        }

        return 1;
    }

    // Getters params:

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

            $buttons[] = array(
                'label'   => 'Liste des facturations effectuées',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => $this->getJsLoadModalCustomContent('renderFacturesTable', 'Facturations' . (BimpObject::objectLoaded($prod) ? ' - ' . $prod->getRef() . ' ' . $prod->getName() : ''))
            );

            $buttons[] = array(
                'label'   => 'Liste des achats effectués',
                'icon'    => 'fas_cart-arrow-down',
                'onclick' => $this->getJsLoadModalCustomContent('renderAchatsTable', 'Achats ' . (BimpObject::objectLoaded($prod) ? ' - ' . $prod->getRef() . ' ' . $prod->getName() : ''))
            );

            if ($this->isActionAllowed('deactivate') && $this->canSetAction('deactivate')) {
                $buttons[] = array(
                    'label'   => 'Désactiver',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('deactivate', array(), array(
                        'confirm_msg' => 'Veuillez confirmer'
                    ))
                );
            }
        }

        return $buttons;
    }

    public function getListsBulkActions($list_name = 'default')
    {
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

    public function getModalView()
    {
        switch ((int) $this->getData('line_type')) {
            case self::TYPE_ABO:
                return 'abonnement';
        }

        return null;
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

                case 'variable_qty':
                    return (int) $prod->getData('variable_qty');
            }
        }

        return 0;
    }

    public function getInputValue($field_name)
    {
        $value = $this->getData($field_name);

        if (in_array($field_name, array('fac_periodicity', 'fac_term', 'achat_periodicity', 'duration'))) {
            $id_linked_line = (int) BimpTools::getPostFieldValue('id_linked_line');
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
            case 'variable_qty':
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

    public function getDateNextFacture($check_date = false, &$errors = array())
    {
        if (!$this->isLoaded() || (int) $this->getData('statut') <= 0) {
            return '';
        }

        $date = $this->getData('date_next_facture');
        $date_fac_start = $this->getDateFacStart();

        if (!$date || $date < $date_fac_start || $check_date) {
            $check_errors = array();
            $new_date = '';
            $sql = BimpTools::getSqlFullSelectQuery('facturedet', array('MAX(a.date_end) as max_date'), array(
                        'f.type'                => array(0, 1, 2),
                        'f.fk_statut'           => array(0, 1, 2),
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
            } elseif ($date_fac_start) {
                $new_date = $date_fac_start;
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
                        }
                    } else {
                        $new_date = $date_fac_start;
                    }
                } elseif (!(int) $this->getData('fac_term')) {
                    $dt = new DateTime($new_date);
                    $dt->add(new DateInterval('P' . (int) $this->getData('fac_periodicity') . 'M'));
                    $new_date = $dt->format('Y-m-d');
                }
            }

            if ($new_date && $new_date != $date) {
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

    public function getPeriodsToBillData(&$errors = array(), $check_date = true)
    {
        $data = array(
            'date_next_facture'       => '', // Date prochaine facture
            'date_next_period_tobill' => '', // Date début de la prochaine période à facturer (différent de date_next_facture si facturation à terme échu)
//            'date_first_fac'          => '', // Date première facture
            'date_fac_start'          => '', // Date début de facturation réelle (cas des facturation partielles / différent de date_first_fac si facturation à terme échu)
            'nb_total_periods'        => 0, // Nombre total de périodes
            'nb_periods_tobill_max'   => 0, // Nombre total de périodes restant à facturer. 
            'nb_periods_tobill_today' => 0, // Nombre de périodes à facturer à date.
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
                if ($date_fac_start > $date_debut) {
                    // Calcul du début de la première période facturée partiellement : 
                    $interval = BimpTools::getDatesIntervalData($date_debut, $date_fac_start);
                    $nb_periods = floor($interval['nb_monthes_decimal'] / $periodicity); // Nombre de périodes entières avant début de la première période à factuer partiellement
                    $dt = new DateTime($date_debut);
                    if ($nb_periods) {
                        $dt->add(new DateInterval('P' . ($nb_periods * $periodicity) . 'M'));
                    }
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

                if ($date_next_period_tobill > $date_fin) {
                    $errors[] = 'Toutes les facturations ont été effectuées';
                    return $data;
                }

                if (!count($errors)) {
                    // Calcul du nombre de périodes restant à facturer
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

                    // Calcul du nombre de périodes à facturer aujourd'hui : 
                    if ($date_now == $date_next_facture) {
                        $data['nb_periods_tobill_today'] = 1;
                    } elseif ($date_now > $date_next_facture) {
                        $interval = BimpTools::getDatesIntervalData($date_next_period_tobill, $date_now);
                        if ($interval['nb_monthes_decimal'] > 0) {
//                            $data['interval'] = $interval;
                            $nb_periods_decimal = ($interval['nb_monthes_decimal'] / $periodicity);
//                            $data['nb_periods_dec'] = $nb_periods_decimal;
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

    public function getPeriodsToBuyData(&$errors = array(), $check_date = true)
    {
        $data = array(
            'date_next_achat'         => '', // Date prochain achat
            'date_achat_start'        => '', // Date début des achat sréelle
            'nb_total_periods'        => 0, // Nombre total de périodes
            'nb_periods_tobuy_max'    => 0, // Nombre total de périodes restant à facturer. 
            'nb_periods_tobuy_today'  => 0, // Nombre de périodes à facturer à date.
            'qty_for_1_period'        => 0,
            'first_period_prorata'    => 1, // Prorata de la première période
            'date_first_period_start' => '', // Début de la première période à acheter
            'date_first_period_end'   => '', // Fin de la première période à acheter
            'debug'                   => array()
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
                if ($date_achat_start > $date_debut) {
                    // Calcul du début de la première période : 
                    $interval = BimpTools::getDatesIntervalData($date_debut, $date_achat_start);
                    $nb_periods = floor($interval['nb_monthes_decimal'] / $periodicity); // Nombre de périodes entières avant début de la première période partielle
                    $dt = new DateTime($date_debut);
                    if ($nb_periods) {
                        $dt->add(new DateInterval('P' . ($nb_periods * $periodicity) . 'M'));
                    }
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

                if ($date_next_achat > $date_fin) {
                    $errors[] = 'Tous les achats ont déjà été effectués';
                    return $data;
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

    public function getFacturesLines($id_facture = 0, &$errors = array())
    {
        $lines = array();

        if ($this->isLoaded($errors)) {
            $where = 'linked_object_name = \'contrat_line\' AND linked_id_object = ' . $this->id;

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

        $id_lines = BimpTools::getPostFieldValue('id_objects');

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
                'date_next_facture' => array(
                    'operator' => '<=',
                    'value'    => date('Y-m-d')
                )
            );

            $id_lines = BimpTools::getPostFieldValue('id_objects', array());
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
            $filters['a.rowid'] = $params['id_lines'];
        } else {
            $filters = array(
                'a.statut'            => 4,
                'a.achat_periodicity' => array(
                    'operator' => '>',
                    'value'    => 0
                ),
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
                    foreach ($rows as $r) {
                        if (!isset($lines[(int) $r['id_line']])) {
                            $lines[(int) $r['id_line']] = array('id_entrepot' => (int) $r['id_entrepot']);
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
                $id_entrepot = (int) BimpTools::getPostFieldValue('id_entrepot', 0);
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
        if ($this->isLoaded() && (int) $this->getData('line_type') == self::TYPE_ABO) {
            $id_prod = (int) BimpTools::getPostFieldValue('fk_product', $this->getData('fk_product'));
            if ($id_prod) {
                $contrat = $this->getParentInstance();
                if (BimpObject::objectLoaded($contrat)) {
                    return $contrat->getAboLinesArray(array(
                                'include_empty' => true,
                                'empty_label'   => 'Aucun',
                                'active_only'   => true,
                                'with_periods'  => true,
                                'id_product'    => $id_prod
                    ));
                }
            }
        }

        return array();
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
                if ((int) $this->getData('id_linked_line')) {
                    $linked_line = $this->getChildObject('linked_line');
                    if (BimpObject::objectLoaded($linked_line)) {
                        $html .= '<span class="info">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Abonnement lié à la ligne n° ' . $linked_line->getData('rang') . '</span><br/><br/>';
                    } else {
                        $html .= '<span class="danger">l\'abonnement lié (ligne #' . $this->getData('id_linked_line') . ') n\'existe plus</span><br/><br/>';
                    }
                }
                $is_variable = (int) $this->getData('variable_qty');
                if ($is_variable) {
                    $html .= '<div style="display: inline-block" class="important">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Abonnement à qté variable</div><br/>';
                }
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

    public function displayProduct($display = 'ref_nom')
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
                    $html .= $product->getRef() . ' - ' . $product->getName();
                    break;

                case 'nom_url':
                    $html .= $product->getLink();
                    break;
            }
        }

        return $html;
    }

    public function displayPeriods()
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

        return $html;
    }

    public function displayAboTotalQty()
    {
        $html = '';

        $fac_periodicity = (int) BimpTools::getPostFieldValue('fac_periodicity', 0);
        $duration = (int) BimpTools::getPostFieldValue('duration', 0);
        $qty_per_period = (float) BimpTools::getPostFieldValue('qty_per_period', 0);

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

            $html .= '<br/><br/>';

            $periods_data = $this->getPeriodsToBillData();
            $nb_periods_billed = $periods_data['nb_total_periods'] - $periods_data['nb_periods_tobill_max'];
            $class = ($nb_periods_billed > 0 ? ($nb_periods_billed < $periods_data['nb_total_periods'] ? 'warning' : 'success') : 'danger');

            $html .= 'Nb périodes facturées: <span class="' . $class . '">' . $nb_periods_billed . ' sur ' . $periods_data['nb_total_periods'] . '</span>';

            if ($nb_periods_billed < $periods_data['nb_total_periods']) {
                $html .= '<br/>Prochaine facturation : ' . $this->displayNextFacDate(true);
            }

            if (BimpCore::isUserDev()) {
                $html .= BimpRender::renderFoldableContainer('Infos dev', '<pre>' . print_r($periods_data, 1) . '</pre>', array('open' => false));
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

            $periods_data = $this->getPeriodsToBuyData();
            $nb_periods_bought = $periods_data['nb_total_periods'] - $periods_data['nb_periods_tobuy_max'];
            $class = ($nb_periods_bought > 0 ? ($nb_periods_bought < $periods_data['nb_total_periods'] ? 'warning' : 'success') : 'danger');

            $html .= 'Nb périodes achetées: <span class="' . $class . '">' . $nb_periods_bought . ' sur ' . $periods_data['nb_total_periods'] . '</span>';

            if ($nb_periods_bought < $periods_data['nb_total_periods']) {
                $html .= '<br/>Prochaine achat : ' . $this->displayNextAchatDate(true);
            }

            if (BimpCore::isUserDev()) {
                $html .= BimpRender::renderFoldableContainer('Infos dev', '<pre>' . print_r($periods_data, 1) . '</pre>', array('open' => false));
            }
        } else {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Pas d\'achats périodiques</span>';
        }

        return $html;
    }

    public function displayClientNameInput()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0);
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
        $id_fourn = (int) BimpTools::getPostFieldValue('id_fourn', 0);
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
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0);
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
        $id_contrat = (int) BimpTools::getPostFieldValue('id_contrat', 0);
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

        $operation_type = BimpTools::getPostFieldValue('operation_type', '');

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
        $id_lines = BimpTools::getPostFieldValue('id_objects', array());
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0);
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0);
        $id_contrat = (int) BimpTools::getPostFieldValue('id_contrat', 0);

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

            foreach ($lines_by_clients as $id_client => $lines) {
                $clients_factures[$id_client] = array();

                foreach ($lines as $id_line => $line_data) {
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
                            'id_entrepot'       => $line_data['id_entrepot'],
                            'secteur'           => $line_data['secteur'],
                            'expertise'         => $line_data['expertise'],
                            'id_mode_reglement' => $line_data['id_mode_reglement'],
                            'id_cond_reglement' => $line_data['id_cond_reglement'],
                            'lines'             => array($id_line)
                        );
                    }
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
            $html .= '<th>Date prochaine facturation</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            $secteurs = BimpCache::getSecteursArray(false);
            $expertises = BimpDolObject::$expertise;
            $modes_reglement = BimpCache::getModeReglements();
            $conds_reglement = BimpCache::getCondReglementsArray(false);

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

                    $fac_libelle = 'Facturation abonnement' . ($facture_data['lines'] > 1 ? 's' : '');

                    $html .= '<div class="fac_libelle_container" style="margin-top: 10px">';
                    $html .= '<span class="small bold">Libellé facture : </span>';
                    $html .= BimpInput::renderInput('text', 'client_' . $id_client . '_fac_' . $fac_idx . '_libelle', $fac_libelle);
                    $html .= '</div>';

                    $html .= '</div>';

                    $html .= '</td>';
                    $html .= '</tr>';

                    foreach ($facture_data['lines'] as $id_line) {
                        $tr_class = '';
                        $row_html = '';

                        $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $line_errors = array();
                            $periods_data = $line->getPeriodsToBillData($line_errors, true);
                            $canFactAvance = $this->canSetAction('facturationAvance');

                            $row_html .= '<td style="min-width: 30px; max-width: 30px; text-align: center">';
                            if (empty($line_errors) &&
                                    ($periods_data['nb_periods_tobill_today'] > 0 || ($canFactAvance && $periods_data['nb_periods_tobill_max'] > 0))) {
                                $tr_class = '';
                                if ($periods_data['nb_periods_tobill_today'] > 0) {
                                    $tr_class = 'selected';
                                }

                                $row_html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check"' . ($periods_data['nb_periods_tobill_today'] > 0 ? ' checked="1"' : '') . '/>';
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
                                $row_html .= $product->getLink() . '<br/>';
                                $row_html .= $product->getName() . '<br/>';
                                $row_html .= $line->displayPeriodicity(false, array('fac'));
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td style="min-width: 250px">';
                            $class = ($periods_data['nb_periods_tobill_today'] > 0 ? ($periods_data['nb_periods_tobill_today'] > 1 ? 'important' : 'warning') : 'danger');
                            $s = ($periods_data['nb_periods_tobill_today'] > 1 ? 's' : '');
                            $qty = $periods_data['nb_periods_tobill_today'] * $periods_data['qty_for_1_period'];

                            $row_html .= 'A traiter aujoud\'hui : <span class="' . $class . '">' . $periods_data['nb_periods_tobill_today'] . ' période' . $s . ' de facturation</span>';
                            $row_html .= '&nbsp;(';
                            $row_html .= BimpTools::displayFloatValue($qty, 4, ',', 0, 1, 0, 1, 1, 1);
                            $row_html .= ' unité' . ($qty > 1 ? 's' : '') . ')<br/>';

                            if (!empty($line_errors)) {
                                $row_html .= BimpRender::renderAlerts($line_errors);
                            } elseif ($periods_data['nb_periods_tobill_today'] > 0 || ($canFactAvance && $periods_data['nb_periods_tobill_max'] > 0)) {
                                $is_first_period = ($periods_data['date_next_period_tobill'] == $periods_data['date_first_period_start']);
                                if ($is_first_period && $periods_data['first_period_prorata'] < 1 && $periods_data['first_period_prorata'] > 0) {
                                    $msg .= BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft');
                                    $msg .= 'Premimère période du <b>' . date('d / m / Y', strtotime($periods_data['date_first_period_start']));
                                    $msg .= '</b> au <b>' . date('d / m / Y', strtotime($periods_data['date_first_period_end'])) . '</b>';
                                    $msg .= ' facturée à partir du <b>' . date('d / m / Y', strtotime($periods_data['date_fac_start'])) . '</b>';
                                    $msg .= '<br/>Prorata : <b>' . BimpTools::displayFloatValue($periods_data['first_period_prorata'], 4, ',', 0, 1, 0, 1, 1, 1) . '</b>';
                                    $row_html .= BimpRender::renderAlerts($msg, 'info');
                                } else {
                                    $row_html .= '<br/>';
                                }

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

                                $product = $line->getChildObject('product');
                                if (BimpObject::objectLoaded($product)) {
                                    if ((int) $line->getData('variable_qty')) {
                                        $nb_decimals = (int) $product->getData('variable_qty_decimals');
                                        $qty_per_period = $line->getFacQtyPerPeriod();

//                                        if ($is_first_period && $periods_data['first_period_prorata'] < 1 && $periods_data['first_period_prorata'] > 0) {
//                                            $nb_decimals = 8;
//                                            $real_qty = $qty_per_period * $periods_data['first_period_prorata'];
//
//                                            if ((int) $periods_data['nb_periods_tobill_today'] > 1) {
//                                                $real_qty += $qty_per_period * ($periods_data['nb_periods_tobill_today'] - 1);
//                                            }
//                                        } else {
//                                            $real_qty = $qty_per_period * $periods_data['nb_periods_tobill_today'];
//                                        }
//                                        $real_qty = BimpTools::displayFloatValue($real_qty, $nb_decimals, '.', 0, 0, 1, 0, 0, 1);

                                        $row_html .= '<div style="margin-top: 10px">';
                                        $row_html .= '<b>Quantité à facturer par période:</b><br/>';
                                        $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty_per_period', $qty_per_period, array(
                                                    'extra_class' => 'line_qty_per_period',
                                                    'data'        => array(
//                                                        'qty_per_period' => $qty_per_period,
                                                        'min'      => 0,
                                                        'decimals' => $nb_decimals
                                                    )
                                        ));
                                        $unit_label = $product->getData('variable_qty_unit');
                                        if ($unit_label) {
                                            $row_html .= ' ' . $unit_label;
                                        }
                                        $row_html .= '</div>';
                                    }
                                }
                            }

                            $row_html .= '</td>';
                            $row_html .= '<td>';
                            $row_html .= date('d / m / Y', strtotime($periods_data['date_next_facture']));
                            $row_html .= '</td>';
                        } else {
                            $row_html .= '<td colspan="99">';
                            $row_html .= BimpRender::renderAlerts('La ligne de contrat #' . $id_line . ' n\'existe plus');
                            $row_html .= '</td>';
                        }

                        $html .= '<tr class="contrat_line_row' . ($tr_class ? ' ' . $tr_class : '') . '"';
                        $html .= ' data-id_client="' . $id_client . '"';
                        $html .= ' data-fac_idx="' . $fac_idx . '"';
                        $html .= ' data-id_line="' . $id_line . '"';
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

    public function renderPeriodicAchatProcessInputs(&$errors = array())
    {
        $html = '';

        $id_lines = BimpTools::getPostFieldValue('id_objects', array());
        $id_fourn_filter = (int) BimpTools::getPostFieldValue('id_fourn', 0);
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0);
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0);
        $id_contrat = (int) BimpTools::getPostFieldValue('id_contrat', 0);

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
//                $product = $line->getChildObject('product');
//                    if (BimpObject::objectLoaded($product)) {
//                      $product->getLastFournPriceId();                        
//                    }
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
                            $line_errors = array();
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
                                $row_html .= $product->getLink() . '<br/>';
                                $row_html .= $product->getName() . '<br/>';
                            }
                            $row_html .= '</td>';

                            if (count($line_errors)) {
                                $row_html .= '<td colspan="4">';
                                $row_html .= BimpRender::renderAlerts($line_errors);
                                $row_html .= '</td>';
                            } else {
                                $row_html .= '<td style="min-width: 250px">';
                                $class = ($periods_data['nb_periods_tobuy_today'] > 0 ? ($periods_data['nb_periods_tobuy_today'] > 1 ? 'important' : 'warning') : 'danger');
                                $s = ($periods_data['nb_periods_tobuy_today'] > 1 ? 's' : '');
                                $row_html .= 'A traiter aujoud\'hui : <span class="' . $class . '">' . $periods_data['nb_periods_tobuy_today'] . ' période' . $s . '</span>';
                                $row_html .= '&nbsp;(' . ($periods_data['nb_periods_tobuy_today'] * $periods_data['qty_for_1_period']) . ' unité' . $s . ')<br/>';

                                if ($id_fourn && $periods_data['nb_periods_tobuy_max'] > 0) {
                                    $is_first_period = ($periods_data['date_next_achat'] == $periods_data['date_achat_start']);
                                    if ($is_first_period && $periods_data['first_period_prorata'] < 1 && $periods_data['first_period_prorata'] > 0) {
                                        $msg .= BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft');
                                        $msg .= 'Premimère période du <b>' . date('d / m / Y', strtotime($periods_data['date_first_period_start']));
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

                                    if ((int) $line->getData('variable_qty')) {
                                        $nb_decimals = (int) $product->getData('variable_qty_decimals');
                                        $qty_per_period = $line->getAchatQtyPerPeriod();

                                        $row_html .= '<div style="margin-top: 10px">';
                                        $row_html .= '<b>Quantité à acheter par période:</b><br/>';
                                        $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty_per_period', $qty_per_period, array(
                                                    'extra_class' => 'line_qty_per_period',
                                                    'data'        => array(
                                                        'min'      => 0,
                                                        'decimals' => $nb_decimals
                                                    )
                                        ));
                                        $unit_label = $product->getData('variable_qty_unit');
                                        if ($unit_label) {
                                            $row_html .= ' ' . $unit_label;
                                        }
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
                                                    'decimals'  => 2
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

                        $html .= '<tr class="contrat_line_row' . ($tr_class ? ' ' . $tr_class : '') . '" data-id_fourn="' . $id_fourn . '" data-id_entrepot="' . $id_entrepot . '" data-id_line="' . $id_line . '">';
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

    public function renderFacturesTable($id_facture = 0, $with_totals = true)
    {
        $html = '';

        $errors = array();
        $fac_lines = $this->getFacturesLines($id_facture, $errors);

        if (!empty($errors)) {
            $html .= BimpRender::renderAlerts($errors, 'danger');
        } elseif (empty($fac_lines)) {
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

            $rows = array();

            $total_ht = 0;
            $total_ttc = 0;

            foreach ($fac_lines as $fac_line) {
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

                    $total_ht += $fac_line->getTotalHT();
                    $total_ttc += $fac_line->getTotalTTC();

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

            $html .= BimpRender::renderBimpListTable($rows, $headers);

            if ($with_totals) {
                $html .= '<table style="margin-top: 30px; width: 300px;" class="bimp_list_table">';
                $html .= '<tbody class="headers_col">';
                $html .= '<tr>';
                $html .= '<th>Total HT Facturé</th>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_ht) . '</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<th>Total TTC Facturé</th>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc) . '</td>';
                $html .= '</tr>';
                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function renderAchatsTable($id_commande_fourn = 0, $with_totals = true)
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

                    $rows[] = array(
                        'period'      => $period,
                        'facture'     => $cf->getLink() . '&nbsp;&nbsp;' . $cf->displayDataDefault('fk_statut'),
                        'qty'         => $cf_line->displayLineData('qty'),
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

    // Traitements:

    public function checkLinkedLine(&$errors = array())
    {
        $check = true;
        $id_linked_line = (int) $this->getData('id_linked_line');
        if ($id_linked_line) {
            $linked_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_linked_line);

            if (BimpObject::objectLoaded($linked_line)) {
                if ((int) $this->getData('fk_contrat') !== (int) $linked_line->getData('fk_contrat')) {
                    $errors[] = 'La ligne liée n\'appartient pas au même contrat';
                    $check = false;
                } elseif ((int) $linked_line->getData('fk_product') !== (int) $this->getData('fk_product')) {
                    $errors[] = 'Abonnement lié : le produit ne correspond pas';
                    $check = false;
                } else {
                    $this->set('fac_periodicity', $linked_line->getDataAtDate('fac_periodicity'));
                    $this->set('achat_periodicity', $linked_line->getDataAtDate('achat_periodicity'));
                    $this->set('duration', $linked_line->getDataAtDate('duration'));
                    $this->set('variable_qty', $linked_line->getDataAtDate('variable_qty'));
                    $this->set('nb_renouv', $linked_line->getDataAtDate('nb_renouv'));
                    $this->set('fac_term', $linked_line->getDataAtDate('fac_term'));
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
                                    $date_debut = $linked_line->getData('date_debut_validite');
                                    if (!$date_debut) {
                                        $date_debut = $linked_line->getData('date_ouverture');
                                    }
                                    if ($date_debut) {
                                        $date_debut = date('Y-m-d', strtotime($date_debut));
                                    }
                                    $date_fin = $linked_line->getData('date_fin_validite');

                                    if ($date_fac_start < $date_debut) {
                                        $errors[] = 'La date d\'ouverture ne peut pas être inférieure à la date de début de l\'abonnement lié';
                                    }
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
                        if (!(int) $this->getData('fac_periodicity')) {
                            $errors[] = 'Périodicité de facturation non définie';
                        }

                        $dt = new DateTime($date_ouverture);

                        if (!$this->getData('date_fac_start')) {
                            $this->set('date_fac_start', $dt->format('Y-m-d'));
                        } else {
                            $date_debut = $this->getData('date_fac_start');
                        }

                        if (!$this->getData('date_achat_start')) {
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
                $contrat = $this->getParentInstance();
                if (BimpObject::objectLoaded($contrat)) {
                    $msg = 'Abonnement {{Produit:' . $this->getData('fk_product') . '}} activé.<br/>';
                    $msg .= 'Date d\'ouverture effective : ' . date('d / m / Y', strtotime($this->getData('date_ouverture'))) . '.<br/>';
                    $msg .= 'Durée: ' . $this->getData('duration') . ' mois';
                    $contrat->addObjectLog($msg);
                }
            }
        }

        return $errors;
    }

    public function onFactureValidate($fac_line, &$success = '')
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

                    $id_entrepot = (int) $contrat->getData('entrepot');
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
                            $errors = $product->correctStocks($id_entrepot, $qty, 1, $code_mvt, $label, 'bimp_contrat', $contrat->id);

                            if (!count($errors)) {
                                $success = 'Retrait de ' . $qty . ' unité(s) du stock effectué';
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

    public function checkStatus(&$infos = '')
    {
        if ($this->isLoaded()) {
            $status = (int) $this->getData('statut');

            if ($status > 0) {
                $new_status = 4;
                $date_fin_validite = $this->getData('date_fin_validite');
                if ($date_fin_validite && $date_fin_validite < date('Y-m-d') . ' 00:00:00') {
                    // Vérif facturation terminée: 
                    $fac_ended = true;
                    if ((int) $this->getData('fac_periodicity')) {
                        $fac_data = $this->getPeriodsToBillData();
                        if ($fac_data['date_next_period_tobill'] <= $date_fin_validite) {
                            $fac_ended = false;
                        }
                    }

                    $achat_ended = true;
                    if ((int) $this->getData('achat_periodicity')) {
                        $fac_data = $this->getPeriodsToBuyData();
                        if ($fac_data['date_next_achat'] <= $date_fin_validite) {
                            $achat_ended = false;
                        }
                    }

                    if ($fac_ended && $achat_ended) {
                        $new_status = 5;
                    }
                }

                if ($new_status != $status) {
                    $errors = $this->updateField('statut', $new_status);

                    $infos .= ($infos ? '<br/>' : '') . 'Contrat #' . $this->getData('fk_contrat') . ' - ligne n°' . $this->getData('rang') . ' : ';
                    if (count($errors)) {
                        $infos .= 'échec màj statut (' . $new_status . ') - <pre>' . print_r($errors, 1) . '</pre>';
                    } else {
                        $infos .= 'Màj statut (' . $new_status . ')';
                    }
                }
            }
        }
    }

    public function majBundle(&$errors = array(), &$warnings = array())
    {
        if ((int) $this->id_product) {
            $product = $this->getProduct();
            if ($product->isBundle()) {
                $fieldsCopy = array('fk_contrat', 'line_type', 'fac_periodicity', 'fac_term', 'achat_periodicity', 'duration', 'nb_renouv', 'date_ouverture_prevue', 'date_fac_start', 'date_achat_start');
                $isAbonnement = $product->isAbonnement();

                //on ajoute les sous lignes et calcule le tot
                $bundle_total_ht = $this->getData('total_ht');
                $qty = $this->getData('qty');
                $totPa = 0;
                if ($bundle_total_ht > 0) {
                    $lines_total_ht = 0;
                    $child_prods = $product->getChildrenObjects('child_products');

                    foreach ($child_prods as $child_prod) {
                        if (!$child_prod->isAbonnement()) {
                            continue;
                        }

                        $newLn = BimpCache::findBimpObjectInstance($this->module, $this->object_name, array(
                                    'id_parent_line'     => $this->id,
                                    'linked_id_object'   => $child_prod->id,
                                    'linked_object_name' => 'bundle'
                                        ), true, true, true);

                        if (!BimpObject::objectLoaded($newLn)) {
                            $newLn = BimpObject::getInstance('bimpcontrat', 'BCT_ContratLine');
                        }

                        $newLn->set('qty', $child_prod->getData('qty') * $qty);
                        $newLn->set('fk_product', (int) $child_prod->getData('fk_product_fils'));
                        $newLn->set('id_parent_line', $this->id);
                        $newLn->set('linked_id_object', $child_prod->id);
                        $newLn->set('linked_object_name', 'bundle');

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
                        $lines_total_ht += $newLn->getData('total_ht');
                        $totPa += $newLn->getData('buy_price_ht') * $newLnQty;

                        if ($isAbonnement && !$newLn->isAbonnement()) {
                            BimpCore::addlog('Attention, composant d\'un bundle abonnement pas abonnement LN : ' . $newLn->id . ' prod : ' . $product->getLink());
                        }
                    }

                    if ($lines_total_ht) {
                        $pourcent = 100 - ($bundle_total_ht / $lines_total_ht * 100);

                        if (abs($pourcent) > 0.01) {
                            $childs = BimpCache::getBimpObjectObjects($this->module, $this->object_name, array('id_parent_line' => $this->id));
                            foreach ($childs as $child) {
                                $errors = BimpTools::merge_array($errors, $child->setRemise($pourcent3, 'Remise bundle ' . $product->getData('ref')));
                            }
                        }

                        //ajout de la ligne de compensation
                        $newLn = BimpCache::findBimpObjectInstance($this->module, $this->object_name, array('id_parent_line' => $this->id, 'linked_object_name' => 'bundleCorrect'), true, true, true);
                        if (is_null($newLn))
                            $newLn = BimpObject::getInstance($this->module, $this->object_name);
                        $newLn->qty = $this->qty;
                        $newLn->id_product = 0;
                        $newLn->pu_ht = -$totHtSansRemise / $this->qty;
                        $newLn->tva_tx = $this->tva_tx;
                        $newLn->desc = 'Annulation double prix Bundle';
                        $newLn->pa_ht = -$totPa / $this->qty;
                        $newLn->set('linked_object_name', 'bundleCorrect');
                        $newLn->set('type', static::LINE_FREE);
                        $newLn->set('editable', 0);
//                        $newLn->set('remisable', 0);
                        $newLn->set('deletable', 0);
                        $newLn->set('id_parent_line', $this->id);
//                        $newLn->set('id_obj', $this->getData('id_obj'));
                        foreach ($fieldsCopy as $field) {
                            $newLn->set($field, $this->getData($field));
                        }
                        if (!$newLn->isLoaded())
                            $errors = BimpTools::merge_array($errors, $newLn->create($warnings, true));
                        else
                            $errors = BimpTools::merge_array($errors, $newLn->update($warnings, true));
                        if (abs($pourcent) > 0.01 || abs($pourcent) < 0.01) {
                            $errors = BimpTools::merge_array($errors, $newLn->setRemise($pourcent2, 'Remise bundle ' . $product->getData('ref')));
                        }

                        //gestion pa 
                        $this->pa_ht = $totPa / $this->qty;
                        $this->update($warnings);
                        //                    die($thisTot.'rr'.$totHt.' '.$pourcent);
                    }
                } /* else
                  die('pas de prix. Ln : '.$this->id); */
            }
        }
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
                        'id_line'        => (int) BimpTools::getArrayValueFromPath($line_data, 'id_line', 0),
                        'nb_periods'     => (int) BimpTools::getArrayValueFromPath($line_data, 'nb_periods', 0),
                        'qty_per_period' => (int) BimpTools::getArrayValueFromPath($line_data, 'qty_per_period', 0),
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
                                $real_qty_per_period = (float) BimpTools::getArrayValueFromPath($line_data, 'qty_per_period', 0);

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

                                $qty = 0;
                                if ((int) $line->getData('variable_qty')) {
                                    $qty_per_period = $real_qty_per_period;
                                } else {
                                    $qty_per_period = $line->getFacQtyPerPeriod();
                                }

                                $periods_data = $line->getPeriodsToBillData();

                                if ($periods_data['date_next_period_tobill'] == $periods_data['date_first_period_start'] &&
                                        $periods_data['first_period_prorata'] < 1) {
                                    $qty = $qty_per_period * (float) $periods_data['first_period_prorata'];

                                    if ($nb_periods > 1) {
                                        $qty += $qty_per_period * ($nb_periods - 1);
                                    }
                                } else {
                                    $qty = $qty_per_period * $nb_periods;
                                }

                                if (!$qty) {
                                    $process->incIgnored();
                                    $process->Alert('Ligne ignorée (Aucune quantité à facturer)', $facture, $line_ref);
                                    continue;
                                }

                                $qty = round($qty, 6);

                                // Check des qty:
                                $line_errors = array();
                                $line_periods_data = $line->getPeriodsToBillData($line_errors, true);

                                if (count($line_errors)) {
                                    $process->incIgnored();
                                    $process->Error($line_errors, $facture, $line_ref);
                                    continue;
                                }

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
                                    continue;
                                }

                                $lines_data[$id_line] = array(
                                    'nb_periods' => $nb_periods,
                                    'qty'        => $qty
                                );
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
                        'id_line'        => (int) BimpTools::getArrayValueFromPath($line_data, 'id_line', 0),
                        'nb_periods'     => (int) BimpTools::getArrayValueFromPath($line_data, 'nb_periods', 0),
                        'qty_per_period' => (int) BimpTools::getArrayValueFromPath($line_data, 'qty_per_period', 0),
                        'pa_ht'          => (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht', 0),
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
                                $real_qty_per_period = (float) BimpTools::getArrayValueFromPath($line_data, 'qty_per_period', 0);
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

                                if ((int) $line->getData('variable_qty')) {
                                    $qty_per_period = $real_qty_per_period;
                                } else {
                                    $qty_per_period = $line->getFacQtyPerPeriod();
                                }

                                $periods_data = $line->getPeriodsToBillData();

                                $qty = 0;
                                if ($periods_data['date_next_achat'] == $periods_data['date_achat_start'] &&
                                        $periods_data['first_period_prorata'] < 1) {
                                    $qty = $qty_per_period * (float) $periods_data['first_period_prorata'];

                                    if ($nb_periods > 1) {
                                        $qty += $qty_per_period * ($nb_periods - 1);
                                    }
                                } else {
                                    $qty = $qty_per_period * $nb_periods;
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
                        $qty_per_period = (float) BimpTools::getPostFieldValue('qty_per_period', 0);

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
                        if ((int) $this->getData('fk_product')) {
                            $prod = $this->getChildObject('product');

                            if (!BimpObject::objectLoaded($prod)) {
                                $errors[] = 'Le produit #' . $this->getData('fk_product') . ' n\'existe plus';
                            } else {
                                if (!$prod->isAbonnement()) {
                                    $errors[] = 'Le produit ' . $prod->getRef() . ' n\'est pas de type abonnement';
                                }
                            }
                        } else {
//                            $errors[] = 'Aucun produit sélectionné';
                        }

                        $periodicity = (int) $this->getData('fac_periodicity');
                        if (!$periodicity) {
                            $errors[] = 'Périodicité de facturation non définie';
                        } else {
                            $duration = (int) $this->getData('duration');

                            if (!$duration) {
                                $errors[] = 'Durée de l\'abonnement non définie';
                            } elseif ($duration % $periodicity != 0) {
                                $errors[] = 'La durée totale doit être un multiple du nombre de mois correspondant à la périodicité de facturation (' . $periodicity . ' mois)';
                            }
                        }

                        $achat_periodicity = (int) $this->getData('achat_periodicity');
                        if ($achat_periodicity) {
                            if ($periodicity < $achat_periodicity) {
                                $errors[] = 'La périodicité de facturation ne peut pas être inférieure à la périodicité d\'achat';
                            }
                        }
                    }
                    break;
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
                    $up_result = $this->db->update('contratdet', $bimpObjectFields, '`rowid` = ' . (int) $id);

                    if ($up_result <= 0) {
                        $msg = 'Echec de l\'insertion des champs additionnels';
                        $sql_errors = $this->db->db->lasterror;
                        if ($sql_errors) {
                            $msg .= ' - Erreur SQL: ' . $sql_errors;
                        }

                        $errors[] = $msg;
                    }
                }
            }
        }

        $this->noFetchOnTrigger = false;
        return $id;
    }
}

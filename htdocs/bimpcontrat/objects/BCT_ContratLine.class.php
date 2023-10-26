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

    // Droits User : 

    public function canSetAction($action)
    {
        global $user;
        switch ($action) {
            case 'activate':
                return (int) !empty($user->rights->bimpcontract->to_validate);

            case 'periodicFacProcess':
                return 1;

            case 'periodicAchatProcess':
                return 1;
        }
        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isValide(&$errors = array())
    {
        return 1;
    }

    public function isCreatable($force_create = false, &$errors = []): int
    {
        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($this->isLoaded() && in_array($field, array('line_type'))) {
            return 0;
        }

        $status = (int) $this->getData('statut');

        if ($status > 0 && in_array($field, array('qty', 'price_ht', 'tva_tx', 'remise_percent', 'fac_periodicity', 'duration', 'variable_qty'))) {
            return 0;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('activate'))) {
            if (!$this->isLoaded()) {
                return 1; // pour les bulk actions
            }
        }

        $contrat = $this->getParentInstance();
        if (!BimpObject::objectLoaded($contrat)) {
            $errors[] = 'Contrat absent';
            return 0;
        }

        $status = (int) $this->getData('statut');
        $contrat_status = (int) $contrat->getData('statut');

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

                if ($contrat_status <= 0) {
                    $errors[] = 'Le contrat n\'est pas validé';
                    return 0;
                }

                if (!$this->isValide($errors)) {
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
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
        return $buttons;
    }

    public function getListsBulkActions($list_name = 'default')
    {
        $actions = array();

        switch ($list_name) {
            case 'contrat':
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
                            'form_name' => 'activate'
                        ))
                    );
                }
                break;

            case 'facturation':
                if ($this->canSetAction('periodicFacProcess')) {
                    $actions[] = array(
                        'label'   => 'Traiter les facturations périodiques des lignes sélectionnées',
                        'icon'    => 'fas_cogs',
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
                break;

            case 'achat':
                if ($this->canSetAction('periodicAchatProcess')) {
                    $actions[] = array(
                        'label'   => 'Traiter les achats périodiques des lignes sélectionnées',
                        'icon'    => 'fas_cogs',
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
                break;
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
                    $lines = $this->getPeriodicFacLinesToProcess(0, 0, 0, 'list');

                    if (!empty($lines)) {
                        foreach ($values as $value) {
                            $filters = BimpTools::mergeSqlFilter($filters, $main_alias . '.rowid', array(
                                        ((int) $value ? 'in' : 'not_in') => $lines
                                            ), 'or');
                        }
                    }
                }
                break;

            case 'periodic_achats_to_process':
                if (!empty($values)) {
                    $lines = $this->getPeriodicAchatLinesToProcess(0, 0, 0, 0, 'list');

                    if (!empty($lines)) {
                        foreach ($values as $value) {
                            $filters = BimpTools::mergeSqlFilter($filters, $main_alias . '.rowid', array(
                                        ((int) $value ? 'in' : 'not_in') => $lines
                                            ), 'or');
                        }
                    }
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    // Getters données: 

    public function getValueForProduct($field_name, $prod = null)
    {
        if (!BimpObject::objectLoaded($prod)) {
            $prod = $this->getChildObject('product');
        }

        if (BimpObject::objectLoaded($prod)) {
            switch ($field_name) {
                case 'price_ht':
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

        switch ($field_name) {
            case 'price_ht':
            case 'tva_tx':
            case 'fk_product_fournisseur_price':
            case 'buy_price_ht':
            case 'fac_periodicity':
            case 'achat_periodicity':
            case 'variable_qty':
                if ((int) $this->getData('fk_product') !== (int) $this->getInitData('fk_product')) {
                    return $this->getValueForProduct($field_name);
                }
                break;

            case 'qty_per_period':
                return $this->getFacQtyPerPeriod();
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

    public function getDateNextFacture($check_date = false, &$errors = array())
    {
        if (!$this->isLoaded() || (int) $this->getData('statut') !== self::STATUS_ACTIVE) {
            return '';
        }

        $date = $this->getData('date_next_facture');

        if (!$date || $check_date) {
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
            } else {
                $date_ouverture = $this->getData('date_ouverture');
                if (!$date_ouverture) {
                    $check_errors[] = 'Date d\'ouverture non définie';
                } else {
                    $new_date = date('Y-m-d', strtotime($date_ouverture));
                }
            }

            if ($new_date && !(int) $this->getData('fac_term')) { // A terme échu
                $dt = new DateTime($new_date);
                $dt->add(new DateInterval('P' . (int) $this->getData('fac_periodicity') . 'M'));
                $new_date = $dt->format('Y-m-d');
            }

            if ($new_date && $new_date != $date) {
                $check_errors = $this->updateField('date_next_facture', $new_date);

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

    public function getPeriodsToBillData(&$errors = array(), $check_date = true)
    {
        $data = array(
            'date_next_facture'       => '',
            'nb_total_periods'        => 0, // Nombre total de périodes
            'nb_periods_tobill_max'   => 0, // Nombre total de périodes restant à facturer. 
            'nb_periods_tobill_today' => 0, // Nombre de périodes à facturer à date.
            'qty_for_1_period'        => 0
        );

        if ($this->isLoaded()) {
            $total_qty = (float) $this->getData('qty');

            if (!(float) $total_qty) {
                return $data;
            }

            $periodicity = (int) $this->getData('fac_periodicity');
            $duration = (int) $this->getData('duration');

            if ($periodicity && $duration) {
                $date_now = date('Y-m-d');
                $data['nb_total_periods'] = ceil($duration / $periodicity);

                $date_ouverture = date('Y-m-d', strtotime($this->getData('date_ouverture')));

                if (!$date_ouverture) {
                    $errors[] = 'Date d\'ouverture non définie';
                    return $data;
                }

                $date_fin = date('Y-m-d', strtotime($this->getData('date_fin_validite')));
                if (!$date_fin) {
                    $errors[] = 'Date de fin de validité non définie';
                    return $data;
                }

                $date_next_facture = $this->getDateNextFacture($check_date, $errors);
                if (!$date_next_facture) {
                    $errors[] = 'Date de prochaine facturation non définie';
                    return $data;
                }

                $data['date_next_facture'] = $date_next_facture;

                if ($date_next_facture > $date_fin) {
                    $errors[] = 'Abonnement terminé';
                    return $data;
                }

                if ($date_ouverture > $date_now) {
                    $data['nb_periods_tobill_max'] = $data['nb_total_periods'];
                    return $data;
                }

                if (!count($errors)) {
                    // Calcul du nombre de périodes restant à facturer
                    $interval = BimpTools::getDatesIntervalData($date_next_facture, $date_fin);
                    $nb_month = $interval['full_monthes']; // Nombre de mois complets
                    if ($interval['remain_days'] > 0) {
                        $nb_month++;
                    }

                    if ($nb_month > 0) {
                        $data['nb_periods_tobill_max'] = ceil($nb_month / $periodicity);

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
                        $interval = BimpTools::getDatesIntervalData($date_next_facture, $date_now);
                        $nb_month = $interval['full_monthes']; // Nombre de mois complets
                        if ($interval['remain_days'] > 0) {
                            $nb_month++;
                        }

                        if ($nb_month > 0) {
                            $data['nb_periods_tobill_today'] = ceil($nb_month / $periodicity);

                            if ($data['nb_periods_tobill_today'] < 0) {
                                $data['nb_periods_tobill_today'] = 0;
                            }

                            if ($data['nb_periods_tobill_today'] > $data['nb_total_periods']) {
                                $data['nb_periods_tobill_today'] = $data['nb_total_periods'];
                            }
                        }
                    }

                    if ($data['nb_periods_tobill_max'] < $data['nb_periods_tobill_today']) {
                        $data['nb_periods_tobill_max'] = $data['nb_periods_tobill_today']; // Par précaution
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
            'date_next_achat'        => '',
            'nb_total_periods'       => 0, // Nombre total de périodes
            'nb_periods_tobuy_max'   => 0, // Nombre total de périodes restant à facturer. 
            'nb_periods_tobuy_today' => 0, // Nombre de périodes à facturer à date.
            'qty_for_1_period'       => 0
        );

        if ($this->isLoaded()) {
            $total_qty = (float) $this->getData('qty');

            if (!(float) $total_qty) {
                return $data;
            }

            $periodicity = (int) $this->getData('achat_periodicity');
            $duration = (int) $this->getData('duration');

            if ($periodicity && $duration) {
                $date_now = date('Y-m-d');
                $data['nb_total_periods'] = ceil($duration / $periodicity);

                $date_ouverture = date('Y-m-d', strtotime($this->getData('date_ouverture')));

                if (!$date_ouverture) {
                    $errors[] = 'Date d\'ouverture non définie';
                    return $data;
                }

                $date_fin = date('Y-m-d', strtotime($this->getData('date_fin_validite')));
                if (!$date_fin) {
                    $errors[] = 'Date de fin de validité non définie';
                    return $data;
                }

                $date_next_facture = $this->getDateNextFacture($check_date, $errors);
                if (!$date_next_facture) {
                    $errors[] = 'Date de prochaine facturation non définie';
                    return $data;
                }

                $data['date_next_facture'] = $date_next_facture;

                if ($date_next_facture > $date_fin) {
                    $errors[] = 'Abonnement terminé';
                    return $data;
                }

                if ($date_ouverture > $date_now) {
                    $data['nb_periods_tobill_max'] = $data['nb_total_periods'];
                    return $data;
                }

                if (!count($errors)) {
                    // Calcul du nombre de périodes restant à facturer
                    $interval = BimpTools::getDatesIntervalData($date_next_facture, $date_fin, true);
                    $nb_month = $interval['full_monthes']; // Nombre de mois complets
                    if ($interval['remain_days'] > 0) {
                        $nb_month++;
                    }

                    if ($nb_month > 0) {
                        $data['nb_periods_tobill_max'] = ceil($nb_month / $periodicity);

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
                        $interval = BimpTools::getDatesIntervalData($date_next_facture, $date_now, true);
                        $nb_month = $interval['full_monthes']; // Nombre de mois complets
                        if ($interval['remain_days'] > 0) {
                            $nb_month++;
                        }

                        if ($nb_month > 0) {
                            $data['nb_periods_tobill_today'] = ceil($nb_month / $periodicity);

                            if ($data['nb_periods_tobill_today'] < 0) {
                                $data['nb_periods_tobill_today'] = 0;
                            }

                            if ($data['nb_periods_tobill_today'] > $data['nb_total_periods']) {
                                $data['nb_periods_tobill_today'] = $data['nb_total_periods'];
                            }
                        }
                    }

                    if ($data['nb_periods_tobill_max'] < $data['nb_periods_tobill_today']) {
                        $data['nb_periods_tobill_max'] = $data['nb_periods_tobill_today']; // Par précaution
                    }
                }
            }

            if ($total_qty && $data['nb_total_periods']) {
                $data['qty_for_1_period'] = $total_qty / $data['nb_total_periods'];
            }
        }

        return $data;
    }

    // Getters statiques : 

    public static function getPeriodicFacLinesToProcess($id_client = null, $id_product = null, $id_contrat = null, $return = 'data', &$errors = array())
    {
        if (!in_array($return, array('data', 'list', 'count'))) {
            $errors[] = 'Type de retour invalide';
            return array();
        }

        $lines = array();

        BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');

        $joins = array(
            'c'   => array(
                'table' => 'contrat',
                'on'    => 'c.rowid = a.fk_contrat'
            ),
            'cef' => array(
                'table' => 'contrat_extrafields',
                'on'    => 'c.rowid = cef.fk_object'
            )
        );

        $filters = array(
            'a.statut'            => 4,
            'a.fac_periodicity'   => array(
                'operator' => '>',
                'value'    => 0
            ),
            'a.date_next_facture' => array(
                'and' => array(
                    array(
                        'operator' => '<=',
                        'value'    => date('Y-m-d')
                    )
                )
            )
        );

        $id_lines = BimpTools::getPostFieldValue('id_objects', array());
        if (!empty($id_lines)) {
            $filters['a.rowid'] = $id_lines;
        }

        if ($id_client) {
            $filters['soc_custom'] = array(
                'custom' => '(((c.fk_soc_facturation IS NULL OR c.fk_soc_facturation = 0) AND c.fk_soc = ' . $id_client . ') OR c.fk_soc_facturation = ' . $id_client . ')'
            );
        }

        if ($id_product) {
            $filters['a.fk_product'] = $id_product;
        }

        if ($id_contrat) {
            $filters['a.fk_contrat'] = $id_contrat;
        }

        BimpTools::addSqlFilterEntity($filters, BimpObject::getInstance('bimpcontrat', 'BCT_Contrat'), 'c');

        $fields = array();
        switch ($return) {
            case 'data':
                $fields = array(
                    'DISTINCT a.rowid as id_line',
                    'c.fk_soc as id_client',
                    'c.fk_soc_facturation as id_client_facture',
                    'cef.entrepot as id_entrepot',
                    'c.secteur',
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
                    'order_way' => 'desc'
        ));

//        die($sql);
        
        $bdb = BimpCache::getBdb();
        $rows = $bdb->executeS($sql, 'array');

        if (is_null($rows)) {
            $errors[] = 'Echec de la récupération des facturations périodiques à traiter - ' . $bdb->err();
        } else {
            switch ($return) {
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
                            'id_cond_reglement' => $r['id_cond_reglement']
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

    public static function getPeriodicAchatLinesToProcess($id_fourn = null, $id_client = null, $id_product = null, $id_contrat = null, $return = 'data', &$errors = array())
    {
        $lines = array();

        BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');

        $joins = array(
            'c'   => array(
                'table' => 'contrat',
                'on'    => 'c.rowid = a.fk_contrat'
            ),
            'cef' => array(
                'table' => 'contrat_extrafields',
                'on'    => 'c.rowid = cef.fk_object'
            )
        );

        $filters = array(
            'a.statut'            => 4,
            'a.achat_periodicity' => array(
                'operator' => '>',
                'value'    => 0
            ),
            'a.date_next_achat'   => array(
                'operator' => '<=',
                'value'    => date('Y-m-d')
            )
        );

        $id_lines = BimpTools::getPostFieldValue('id_objects', array());
        if (!empty($id_lines)) {
            $filters['a.rowid'] = $id_lines;
        }

        if ($id_fourn) {
            $joins['pfp'] = array(
                'table' => 'product_fournisseur_price',
                'on'    => 'pfp.rowid = a.fk_product_fournisseur_price'
            );
            $filters['pfp.fk_soc'] = $id_fourn;
        }

        if ($id_client) {
            $filters['soc_custom'] = array(
                'custom' => '(((c.fk_soc_facturation IS NULL OR c.fk_soc_facturation = 0) AND c.fk_soc = ' . $id_client . ') OR c.fk_soc_facturation = ' . $id_client . ')'
            );
        }

        if ($id_product) {
            $filters['a.fk_product'] = $id_product;
        }

        if ($id_contrat) {
            $filters['a.fk_contrat'] = $id_contrat;
        }

        BimpTools::addSqlFilterEntity($filters, BimpObject::getInstance('bimpcontrat', 'BCT_Contrat'), 'c');

        $fields = array();
        switch ($return) {
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
                    'order_way' => 'desc'
        ));

        $bdb = BimpCache::getBdb();

        $rows = $bdb->executeS($sql, 'array');

        if (is_null($rows)) {
            $errors[] = 'Echec de la récupération des achats périodiques à traiter - ' . $bdb->err();
        } else {
            switch ($return) {
                case 'data':
                    foreach ($rows as $r) {
                        if (!isset($lines[(int) $r['id_line']])) {
                            $lines[(int) $r['id_line']] = array('id_entrepot' => (int) $r['entrepot']);
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

    // Getters array : 

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
                $is_variable = (int) $this->getData('variable_qty');
                if ($is_variable) {
                    $html .= '<div style="display: inline-block" class="important">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Abonnement à qté variable</div><br/>';
                }
                $html .= '<b>Qté totale ' . ($is_variable ? 'estimée ' : '') . ': </b>' . BimpTools::displayFloatValue($this->getData('qty'), 8, ',', 0, 0, 0, 0, 1, 1) . '<br/>';
                $html .= '<b>Durée abonnement : </b>' . $this->getData('duration') . ' mois<br/>';
                $html .= '<b>Périodicité : </b>' . $this->displayDataDefault('fac_periodicity') . '<br/>';
                $html .= '<b>Nombre de périodes : </b>' . $this->getFacNbPeriods() . '<br/>';
                $html .= '<b>Qté par période ' . ($is_variable ? 'estimée ' : '') . ': </b>' . $this->getFacQtyPerPeriod() . '<br/>';
        }

        return $html;
    }

    public function displayPeriodicity()
    {
        $html = '';
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

    public function displayAchatInfos()
    {
        $html = '';

        $html .= '<b>Prix d\'achat HT : </b>' . $this->displayDataDefault('buy_price_ht');

        if ((int) $this->getData('fk_product_fournisseur_price')) {
            $id_fourn = (int) $this->db->getValue('product_fournisseur_price', 'fk_soc', 'rowid = ' . (int) $this->getData('fk_product_fournisseur_price'));

            if ($id_fourn) {
                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);

                if (BimpObject::objectLoaded($fourn)) {
                    $html .= '<br/><b>Fournisseur : </b>' . $fourn->getLink();
                }
            }
        }

        $achat_periodicity = (int) $this->getData('achat_periodicity');
        if ($achat_periodicity) {
            $html .= '<br/><b>Périodicité d\'achat : </b>' . $this->displayDataDefault('achat_periodicity');
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
        if ($this->isLoaded() && (int) $this->getData('fac_periodicity')) {
            $errors = array();
            $data = $this->getPeriodsToBillData($errors, false);

            if (isset($data['nb_periods_tobill_today'])) {
                return '<div style="text-align: center"><span class="badge badge-' . ($data['nb_periods_tobill_today'] > 0 ? ($data['nb_periods_tobill_today'] > 1 ? 'important' : 'warning') : 'success') . '">' . $data['nb_periods_tobill_today'] . '</span></div>';
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

    // Rendus HTML:

    public function renderProductInput($sub_type_filters = null)
    {
        $prod_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $values = $prod_instance->getProductsArrayByType2($sub_type_filters);

        $value = (isset($values[$this->getData('fk_product')]) ? $this->getData('fk_product') : 0);
        return BimpInput::renderInput('select', 'fk_product', $value, array(
                    'options' => $values
        ));
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

        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0);
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0);
        $id_contrat = (int) BimpTools::getPostFieldValue('id_contrat', 0);

        $errors = array();
        $lines_by_clients = self::getPeriodicFacLinesToProcess($id_client, $id_product, $id_contrat, 'data', $errors);

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
                        if ($cf_data['id_entrepot'] == $line_data['id_entrepot'] && $cf_data['secteur'] == $line_data['secteur'] &&
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
                    $html .= '>';
                    $html .= '<td colspan="99" style="font-size: 12px; padding: 10px; background-color: #DCDCDC">';
                    $html .= '<div style="display: inline-block">';
                    $html .= 'Entrepôt : ' . $entrepot->getLink() . '<br/>';
                    $html .= 'Secteur : <b>' . (isset($secteurs[$facture_data['secteur']]) ? $secteurs[$facture_data['secteur']] : '<span class="danger">' . ($facture_data['secteur'] ? 'inconnu (' . $facture_data['secteur'] . ')' : 'non spécifié') . '</span>') . '</b><br/>';
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

                            $row_html .= '<td style="min-width: 30px; max-width: 30px; text-align: center">';
                            if (empty($line_errors) && $periods_data['nb_periods_tobill_today'] > 0) {
                                $tr_class = 'selected';
                                $row_html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check" checked="1"/>';
                            } else {
                                $tr_class = 'deactivated';
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $contrat = $line->getParentInstance();
                            if (BimpObject::objectLoaded($contrat)) {
                                $row_html .= $contrat->getLink();
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
                            } elseif ($periods_data['nb_periods_tobill_today'] > 0) {
                                $row_html .= '<br/>';
                                $row_html .= '<b>Nb périodes à facturer: </b><br/>';
                                $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_nb_periods', $periods_data['nb_periods_tobill_today'], array(
                                            'extra_class' => 'line_qty',
                                            'max_label'   => true,
                                            'data'        => array(
                                                'max'      => $periods_data['nb_periods_tobill_today'],
                                                'min'      => 0,
                                                'decimals' => 0
                                            )
                                ));
                            }

                            $row_html .= '</td>';
                            $row_html .= '<td>';
                            $row_html .= date('d / m / Y', strtotime($periods_data['date_next_facture']));
                            $row_html .= '</td>';
                        } else {
                            $row_html .= '<td colspan="99">';
                            $row_html .= BimpRender::renderAlerts('La ligne de commande #' . $id_line . ' n\'existe plus');
                            $row_html .= '</td>';
                        }

                        $html .= '<tr class="commande_line_row' . ($tr_class ? ' ' . $tr_class : '') . '"';
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
        // Todo : adapter

        $html = '';

        $id_fourn_filter = (int) BimpTools::getPostFieldValue('id_fourn', 0);
        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0);
        $id_product = (int) BimpTools::getPostFieldValue('id_product', 0);

        $lines = self::getPeriodicAchatLinesToProcess($errors, $id_fourn_filter, $id_client, $id_product);

        // Trie par fournisseur et entrepot: 
        $lines_by_fourns = array();

        foreach ($lines as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
            if (!BimpObject::objectLoaded($line)) {
                continue;
            }

            $id_entrepot = (int) BimpTools::getArrayValueFromPath($line_data, 'id_entrepot', 0);
            if (!$id_entrepot) {
                $errors[] = 'Ligne #' . $id_line . ' : entrepôt absent';
                continue;
            }

            $line_errors = array();
            $params = $line->getData('periodicity_extra_params');

            $id_fourn = 0;
            $pa_ht = 0;
            $type_pa = (int) BimpTools::getArrayValueFromPath($params, 'achat_type_pa', 0);
            $type_pa_label = '';
            switch ($type_pa) {
                case 1:
                    $type_pa_label = 'PA fournisseur configuré';
                    $id_pfp = (int) BimpTools::getArrayValueFromPath($params, 'achat_id_fourn_price', 0);
                    if ($id_pfp) {
                        $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);
                        if (BimpObject::objectLoaded($pfp)) {
                            $type_pa_label = 'Dernier PA fournisseur enregistré pour ce produit';
                            $id_fourn = $pfp->getData('fk_soc');
                            $pa_ht = $pfp->getData('price');
                        } else {
                            $line_errors[] = 'PA fournisseur #' . $id_pfp . ' n\'existe plus';
                        }
                    } else {
                        $line_errors[] = 'PA fournisseur non défini';
                    }
                    break;

                case 2:
                    $type_pa_label = 'PA personnalisé';
                    $id_fourn = (int) BimpTools::getArrayValueFromPath($params, 'achat_id_fourn', 0);
                    $pa_ht = (float) BimpTools::getArrayValueFromPath($params, 'achat_pa_ht', 0);
                    break;

                default:
                    $product = $line->getProduct();
                    if (BimpObject::objectLoaded($product)) {
                        $pfp = $product->getLastFournPrice();
                        if (BimpObject::objectLoaded($pfp)) {
                            $type_pa_label = 'Dernier PA fournisseur enregistré pour ce produit';
                            $id_fourn = (int) $pfp->getData('fk_soc');
                            $pa_ht = (float) $pfp->getData('price');
                            break;
                        }
                    }
                    $line_errors[] = 'Aucun prix d\'achat configuré';
                    break;
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
                'pa_ht'         => $pa_ht,
                'type_pa_label' => $type_pa_label,
                'errors'        => $line_errors
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
            $html .= '<th>Commande client</th>';
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

                        $commandes = $this->getCommandesFournisseursArray($id_fourn, $id_entrepot);

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

                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                        if (BimpObject::objectLoaded($line)) {
                            $periods_data = $line->getNbPeriodesToBuyData(null);

                            $row_html .= '<td style="min-width: 30px; max-width: 30px; text-align: center">';
                            if ($id_fourn) {
                                if ($periods_data['nb_periods_tobuy_today'] > 0) {
                                    $tr_class = 'selected';
                                    $row_html .= '<input type="checkbox" name="line_' . $line->id . '_check" class="line_check" checked="1"/>';
                                } else {
                                    $tr_class = 'deactivated';
                                }
                            } else {
                                $tr_class = 'deactivated';
                            }

                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $commande = $line->getParentInstance();
                            if (BimpObject::objectLoaded($commande)) {
                                $row_html .= $commande->getLink();
                            }
                            $row_html .= '</td>';

                            $row_html .= '<td>';
                            $product = $line->getProduct();
                            if (BimpObject::objectLoaded($product)) {
                                $row_html .= $product->getLink() . '<br/>';
                                $row_html .= $product->getName() . '<br/>';
                                $row_html .= $line->displayPeriodicity(false, array('exp'));
                            }
                            $row_html .= '</td>';

                            $line_errors = BimpTools::getArrayValueFromPath($line_data, 'errors', array());

                            if (count($line_errors)) {
                                $row_html .= '<td colspan="4">';
                                $row_html .= BimpRender::renderAlerts($line_errors);
                                $row_html .= '</td>';
                            } else {
                                $row_html .= '<td style="min-width: 250px">';
                                $row_html .= 'Déjà traité : ' . $line->displayBoughtPeriods(true, $periods_data) . '<br/>';

                                $class = ($periods_data['nb_periods_tobuy_today'] > 0 ? ($periods_data['nb_periods_tobuy_today'] > 1 ? 'important' : 'warning') : 'danger');
                                $s = ($periods_data['nb_periods_tobuy_today'] > 1 ? 's' : '');
                                $row_html .= 'A traiter aujoud\'hui : <span class="' . $class . '">' . $periods_data['nb_periods_tobuy_today'] . ' période' . $s . '</span>';
                                $row_html .= '&nbsp;(' . ($periods_data['nb_periods_tobuy_today'] * $periods_data['qty_for_1_period']) . ' unité' . $s . ')<br/>';

                                if ($id_fourn && $periods_data['nb_periods_tobuy_today'] > 0) {
                                    $row_html .= '<br/>';
                                    $row_html .= '<b>Nb périodes à acheter: </b><br/>';
                                    $row_html .= BimpInput::renderInput('qty', 'line_' . $line->id . '_qty', $periods_data['nb_periods_tobuy_today'], array(
                                                'extra_class' => 'line_qty',
                                                'max_label'   => true,
                                                'data'        => array(
                                                    'max'      => $periods_data['nb_periods_tobuy_today'],
                                                    'min'      => 0,
                                                    'decimals' => 0
                                                )
                                    ));
                                }

                                $row_html .= '</td>';
                                $row_html .= '<td>';
                                $row_html .= $line->displayNextPeriodDate('achat', true);
                                $row_html .= '</td>';

                                $row_html .= '<td>';
                                $pa_ht = (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht', 0);

                                if ($id_fourn && $periods_data['nb_periods_tobuy_today'] > 0) {
                                    $row_html .= BimpInput::renderInput('text', 'line_' . $line->id . '_pa_ht', $pa_ht, array(
                                                'extra_class' => 'line_pa_ht',
                                                'addon_right' => BimpRender::renderIcon('fas_euro-sign'),
                                                'data'        => array(
                                                    'data_type' => 'number',
                                                    'decimals'  => 2
                                                )
                                    ));
                                } else {
                                    $row_html .= '<b>' . BimpTools::displayMoneyValue($pa_ht) . '</b>';
                                }


                                $pa_label = BimpTools::getArrayValueFromPath($line_data, 'type_pa_label', '');
                                if ($pa_label) {
                                    $row_html .= '<br/><span class="small">' . $pa_label . '</span>';
                                }

                                $row_html .= '</td>';
                            }
                        } else {
                            $row_html .= '<td colspan="99">';
                            $row_html .= BimpRender::renderAlerts('La ligne de commande #' . $id_line . ' n\'existe plus');
                            $row_html .= '</td>';
                        }

                        $html .= '<tr class="commande_line_row' . ($tr_class ? ' ' . $tr_class : '') . '" data-id_fourn="' . $id_fourn . '" data-id_entrepot="' . $id_entrepot . '" data-id_line="' . $id_line . '">';
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
                    'id_contrat' => 0,
                    'id_client'  => 0,
                    'id_fourn'   => 0,
                    'id_product' => 0
                        ), $params);

        $nb_facs = self::getPeriodicFacLinesToProcess($params['id_client'], $params['id_product'], $params['id_contrat'], 'count');
        $nb_achats = self::getPeriodicAchatLinesToProcess($params['id_fourn'], $params['id_client'], $params['id_product'], $params['id_contrat'], 'count');

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

    // Traitements:

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
                if (!(int) $this->getData('duration')) {
                    $errors[] = 'Durée de l\'abonnement non définie';
                }
                if (!(int) $this->getData('fac_periodicity')) {
                    $errors[] = 'Périodicité de facturation non définie';
                }

                if ($date_ouverture) {
                    $this->set('date_ouverture', date('Y-m-d 00:00:00', strtotime($date_ouverture)));
                } elseif (!(string) $this->getData('date_ouverture')) {
                    if ($this->getData('date_ouverture_prevue')) {
                        $this->set('date_ouverture', date('Y-m-d 00:00:00', strtotime($this->getData('date_ouverture_prevue'))));
                    } else {
                        $errors[] = 'Date d\'ouverture non définie';
                    }
                }

                $dt = new DateTime($this->getData('date_ouverture'));
                $dt->add(new DateInterval('P' . $this->getData('duration') . 'M'));
                $dt->sub(new DateInterval('P1D'));
                $this->set('date_fin_validite', $dt->format('Y-m-d 23:59:59'));

                $dt = new DateTime($this->getData('date_ouverture'));
                if ((int) $this->getData('achat_periodicity')) {
                    $this->set('date_next_achat', $dt->format('Y-m-d'));
                }

                if (!(int) $this->getData('fac_term')) { // A terme échu
                    $dt->add('P' . (int) $this->getData('fac_periodicity') . 'M');
                }
                $this->set('date_next_facture', $dt->format('Y-m-d'));
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

    // Actions : 

    public function actionActivate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $date_ouverture = BimpTools::getArrayValueFromPath($data, 'date_ouverture', '');
        if (!$date_ouverture) {
            $errors[] = 'Veuillez renseigner la date d\'ouverture';
        } else {
            if ($this->isLoaded()) {
                $success = 'Ligne de contrat activée';
                $errors = $this->activate($date_ouverture);
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
                                $line_errors = $line->activate($date_ouverture);
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
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
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

                    $qty_per_period = (float) BimpTools::getPostFieldValue('qty_per_period', 0);

                    if (!$qty_per_period) {
                        $errors[] = 'Veuillez définir une quantité par période';
                    } else {
                        $duration = (int) $this->getData('duration');

                        if ($duration % $fac_periodicity != 0) {
                            $errors[] = 'La durée totale doit être un multiple du nombre de mois correspondant à la périodicité de facturation (' . $fac_periodicity . ' mois)';
                        } else {
                            $nb_periods = $duration / $fac_periodicity;
                            $this->set('qty', $qty_per_period * $nb_periods);
                        }
                    }
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
                        'achat_periodicity'            => 0
                    ));
                    break;

                case self::TYPE_ABO:
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
                        $errors[] = 'Aucun produit sélectionné';
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
                        } else {
                            $date_ouverture = $this->getData('date_ouverture_prevue');
                            if ($date_ouverture) {
                                $dt = new DateTime($date_ouverture);

                                $dt->add(new DateInterval('P' . $duration . 'M'));
                                $this->set('date_fin_validite', $dt->format('Y-m-d H:i:s'));
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
            $pu_ht = (float) $this->getData('price_ht');
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

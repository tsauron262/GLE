<?php

class BimpCommission extends BimpObject
{

    const TYPE_USER = 1;
    const TYPE_ENTREPOT = 2;

    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $types = array(
        self::TYPE_USER     => array('label' => 'Commission utilisateur', 'icon' => 'fas_user'),
        self::TYPE_ENTREPOT => array('label' => 'Commission entrepôt', 'icon' => 'fas_warehouse'),
    );

    // Gestion des droits user: 

    public function canDelete()
    {

        return (int) $this->canCreate();
    }

    public function canCreate()
    {
        global $user;
        return ($user->admin || $user->rights->bimpcommercial->commission->write);
    }

    public function canEdit()
    {
        return (int) $this->canCreate();
    }

    public function canView()
    {
        global $user;
        if (!$this->isLoaded() || $user->id == $this->getData('id_user'))
            return 1;

        return $this->canViewAll();
    }

    public function canViewAll()
    {
        global $user;
        return ($user->admin || $user->rights->bimpcommercial->commission->read);
    }

    public function canSetAction($action)
    {
        global $user;
        switch ($action) {
            case 'createCommissions';
            case 'validate':
            case 'generateFactureCommissions':
                return (int) $this->can('create');

            case 'reopen':
                return (int) $user->admin; //$this->can('delete');
        }

        return (int) parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'validate':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ((int) $this->getData('status') !== 0) {
                    $errors[] = 'Cette commission n\'est plus au statut "brouillon"';
                    return 0;
                }
                return 1;

            case 'reopen':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ((int) $this->getData('status') == 0) {
                    $errors[] = 'Cette commission est déjà au statut "brouillon"';
                    return 0;
                }
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ((int) $this->getData('status') === 0) {
            return 1;
        }

        $errors[] = 'Cette commission n\'est plus au statut "brouillon"';

        return 0;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('validate')) {
            if ($this->canSetAction('validate')) {
                $buttons[] = array(
                    'label'   => 'Valider',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('validate')
                );
            }
        } elseif ($this->isActionAllowed('reopen')) {
            if ($this->canSetAction('reopen')) {
                $buttons[] = array(
                    'label'   => 'Réouvrir',
                    'icon'    => 'fas_redo',
                    'onclick' => $this->getJsActionOnclick('reopen')
                );
            }
        }

        return $buttons;
    }

    public function getListExtraBtn()
    {
        $buttons = $this->getActionsButtons();
        return $buttons;
    }

    public function getListHeaderButtons()
    {
        $buttons = array();

//        $buttons[] = array(
//            'classes'     => array('btn', 'btn-default'),
//            'label'       => 'Générer des commissions par groupe',
//            'icon_before' => 'fas_cogs',
//            'attr'        => array(
//                'onclick' => $this->getJsActionOnclick('createCommissions', array(), array(
//                    'form_name' => 'generate'
//                ))
//            )
//        );

        if (BimpCore::isEntity('bimp')) {
            if ($this->canSetAction('generateFactureCommissions')) {
                $buttons[] = array(
                    'label'   => 'Générer facture de commissionnement',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $this->getJsActionOnclick('generateFactureCommissions', array(), array(
                        'form_name'        => 'facture_commissions',
                        'use_bimpdatasync' => 1,
                        'use_report'       => 1
                    ))
                );
            }
        }

        return $buttons;
    }

    // Getters données: 

    public function getName($withGeneric = true)
    {
        if ($this->isLoaded()) {
            $label = 'Commission ';
            switch ((int) $this->getData('type')) {
                case self::TYPE_USER:
                    $label .= ' utilisateur';
                    break;

                case self::TYPE_ENTREPOT:
                    $label .= ' entrepôt';
                    break;
            }

            $label .= ' #' . $this->id;

            return $label;
        }

        return '';
    }

    // Getters cache: 

    public function getRevalorisationsListCacheKey()
    {
        if ($this->isLoaded()) {
            return 'commission_' . $this->id . '_revalorisations_list';
        }

        return '';
    }

    public function getFacturesList($refresh_cache = false)
    {
        if ($this->isLoaded()) {
            $cache_key = 'commission_' . $this->id . '_factures_list';

            if ($refresh_cache || !isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $field = '';
                switch ((int) $this->getData('type')) {
                    case self::TYPE_USER:
                        $field = 'id_user_commission';
                        break;

                    case self::TYPE_ENTREPOT:
                        $field = 'id_entrepot_commission';
                        break;
                }

                $rows = $this->db->getRows('facture', $field . ' = ' . (int) $this->id, null, 'array', array('rowid'));
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][] = (int) $r['rowid'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getAvailableFacturesList($paid_only = false, $secteur = '')
    {
        if ($this->isLoaded()) {
            $date = $this->getData('date');
            $type = (int) $this->getData('type');
            $sql = '';

            switch ($type) {
                case self::TYPE_USER:
                    $id_user = (int) $this->getData('id_user');

                    if ($date && $id_user) {
                        $sql = 'SELECT f.`rowid` as id FROM ' . MAIN_DB_PREFIX . 'facture f ';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_contact ec ON ec.element_id = f.rowid ';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields fef ON f.rowid = fef.fk_object ';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'entrepot e ON fef.entrepot = e.rowid';
                        $sql .= ' WHERE f.id_user_commission = 0';
                        $sql .= ' AND f.date_valid <= \'' . $date . ' 23:59:59\'';
                        $sql .= ' AND f.fk_statut IN (1,2)';
                        $sql .= ' AND f.type IN (0,1,2)';
                        $sql .= ' AND ec.fk_c_type_contact = ';
                        $sql .= '(SELECT ctc.`rowid` FROM ' . MAIN_DB_PREFIX . 'c_type_contact ctc';
                        $sql .= ' WHERE ctc.source = \'internal\' AND ctc.element = \'facture\' AND ctc.code = \'SALESREPFOLL\')';
                        $sql .= ' AND ec.fk_socpeople = ' . $id_user;
                        $sql .= ' AND e.has_users_commissions = 1';

                        if ($paid_only) {
                            $sql .= ' AND (f.paye = 1 OR f.remain_to_pay < 0)';
                        }
                    }
                    break;

                case self::TYPE_ENTREPOT:
                    $id_entrepot = (int) $this->getData('id_entrepot');

                    if ($date && $id_entrepot) {
                        $sql = 'SELECT f.`rowid` as id FROM ' . MAIN_DB_PREFIX . 'facture f ';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields fef ON f.rowid = fef.fk_object ';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'entrepot e ON fef.entrepot = e.rowid';
                        $sql .= ' WHERE f.id_entrepot_commission = 0';
                        $sql .= ' AND f.date_valid <= \'' . $date . ' 23:59:59\'';
                        $sql .= ' AND f.fk_statut IN (1,2)';
                        $sql .= ' AND f.type IN (0,1,2)';
                        $sql .= ' AND e.rowid = ' . $id_entrepot;

                        if ($paid_only) {
                            $sql .= ' AND (f.paye = 1 OR f.remain_to_pay < 0)';
                        }
                        if ($secteur) {
                            $sql .= ' AND fef.type = \'' . $secteur . '\'';
                        }
                    }
                    break;
            }

            if ($sql) {
                $rows = $this->db->executeS($sql, 'array');

                if (is_array($rows)) {
                    $return = array();

                    foreach ($rows as $r) {
                        $return[] = (int) $r['id'];
                    }

                    return $return;
                } else {
                    echo 'Err: ' . $this->db->db->lasterror();
                }
            }
        }

        return array();
    }

    public function getRevalorisationsList($refresh_cache = false)
    {
        if ($this->isLoaded()) {
            $cache_key = 'commission_' . $this->id . '_revalorisations_list';

            if ($refresh_cache || !isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $field = '';
                switch ((int) $this->getData('type')) {
                    case self::TYPE_USER:
                        $field = 'id_user_commission';
                        break;

                    case self::TYPE_ENTREPOT:
                        $field = 'id_entrepot_commission';
                        break;
                }

                $rows = $this->db->getRows('bimp_revalorisation', $field . ' = ' . (int) $this->id, null, 'array', array('id'));
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][] = (int) $r['id'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getAvailableRevalorisationsList($paid_only = false, $secteur = '')
    {
        if ($this->isLoaded()) {
            $type = (int) $this->getData('type');
            $sql = '';

            switch ($type) {
                case self::TYPE_USER:
                    $id_user = (int) $this->getData('id_user');

                    if ($id_user) {
                        $sql = 'SELECT r.id FROM ' . MAIN_DB_PREFIX . 'bimp_revalorisation r';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_contact ec ON ec.element_id = r.id_facture';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture f ON f.rowid = r.id_facture';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields fef ON r.id_facture = fef.fk_object';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'entrepot e ON fef.entrepot = e.rowid';
                        $sql .= ' WHERE r.id_user_commission = 0';
                        $sql .= ' AND r.status = 1';
                        $sql .= ' AND ec.fk_c_type_contact = ';
                        $sql .= '(SELECT ctc.`rowid` FROM ' . MAIN_DB_PREFIX . 'c_type_contact ctc';
                        $sql .= ' WHERE ctc.source = \'internal\' AND ctc.element = \'facture\' AND ctc.code = \'SALESREPFOLL\')';
                        $sql .= ' AND ec.fk_socpeople = ' . $id_user;
                        $sql .= ' AND e.has_users_commissions = 1';
                        $sql .= ' AND f.fk_statut IN (1,2)';
                        if ($paid_only) {
                            $sql .= ' AND f.paye = 1';
                        }
                    }
                    break;

                case self::TYPE_ENTREPOT:
                    $id_entrepot = (int) $this->getData('id_entrepot');

                    if ($id_entrepot) {
                        $sql = 'SELECT r.id FROM ' . MAIN_DB_PREFIX . 'bimp_revalorisation r ';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields fef ON fef.fk_object = r.id_facture';
                        if ($paid_only) {
                            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture f ON f.rowid = r.id_facture';
                        }
                        $sql .= ' WHERE r.id_entrepot_commission = 0';
                        $sql .= ' AND r.status = 1';
                        $sql .= ' AND fef.entrepot = ' . $id_entrepot;
                        if ($paid_only) {
                            $sql .= ' AND f.paye = 1';
                        }

                        if ($secteur) {
                            $sql .= ' AND fef.type = \'' . $secteur . '\'';
                        }
                    }
                    break;
            }


            if ($sql) {
                $rows = $this->db->executeS($sql, 'array');

                if (is_array($rows)) {
                    $return = array();

                    foreach ($rows as $r) {
                        $return[] = (int) $r['id'];
                    }

                    return $return;
                }
            }
        }

        return array();
    }

    public function getAmountsCacheData($recalculate = false)
    {
        $data = array(
            'total_ca'          => 0,
            'total_ca_serv'     => 0,
            'total_ca_prod'     => 0,
            'total_pa'          => 0,
            'total_pa_serv'     => 0,
            'total_pa_prod'     => 0,
            'total_reval'       => 0,
            'total_reval_serv'  => 0,
            'total_reval_prod'  => 0,
            'total_marges'      => 0,
            'total_marges_serv' => 0,
            'total_marges_prod' => 0,
            'tx_marge'          => 0,
            'tx_marque'         => 0
        );

        if (!$this->isLoaded()) {
            return $data;
        }

        $cache_key = 'commission_' . $this->id . '_amounts_data';

        if ($recalculate || !isset(self::$cache[$cache_key])) {
            // Factures: 
            $factures_list = $this->getFacturesList(true);

            foreach ($factures_list as $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                if (BimpObject::objectLoaded($facture)) {
                    $lines = $facture->getLines('not_text');

                    $tot1 = 0;
                    foreach ($lines as $line) {
                        $data['total_ca'] += (float) $line->getTotalHTWithRemises();
                        if ($line->isService())
                            $data['total_ca_serv'] += (float) $line->getTotalHTWithRemises();
                        else
                            $data['total_ca_prod'] += (float) $line->getTotalHTWithRemises();
                        $data['total_pa'] += ((float) $line->pa_ht * (float) $line->qty);
                        if ($line->isService())
                            $data['total_pa_serv'] += ((float) $line->pa_ht * (float) $line->qty);
                        else
                            $data['total_pa_prod'] += ((float) $line->pa_ht * (float) $line->qty);
                        $tot1 += (((float) $line->getTotalHTWithRemises() - ((float) $line->pa_ht * (float) $line->qty)));
                    }

                    if (((float) $tot1 - (float) $facture->getData('marge')) > 0.01 ||
                            ((float) $tot1 - (float) $facture->getData('marge')) < -0.01)
                        echo "<br/>Probléme de Marge : " . $tot1 . " " . $facture->getNomUrl() . " " . (float) $facture->getData('marge') . "<br/>";
//                    }
                }
            }

            $data['total_marges'] = $data['total_ca'] - $data['total_pa'];
            $data['total_marges_serv'] = $data['total_ca_serv'] - $data['total_pa_serv'];
            $data['total_marges_prod'] = $data['total_ca_prod'] - $data['total_pa_prod'];

            // Revalorisations: 
            $revals_list = $this->getRevalorisationsList(true);

            foreach ($revals_list as $id_reval) {
                $reval = BimpCache::getBimpObjectInstance('bimpfinanc', 'BimpRevalorisation', (int) $id_reval);

                if (BimpObject::objectLoaded($reval)) {
                    $data['total_reval'] += (float) $reval->getTotal();

                    $lineReval = $reval->getChildObject('facture_line');
                    if ($lineReval->isService())
                        $data['total_reval_serv'] += (float) $reval->getTotal();
                    else
                        $data['total_reval_prod'] += (float) $reval->getTotal();
                }
            }

            $data['total_marges'] += $data['total_reval'];
            $data['total_marges_serv'] += $data['total_reval_serv'];
            $data['total_marges_prod'] += $data['total_reval_prod'];

            if ($data['total_pa']) {
                $data['tx_marge'] = ($data['total_marges'] / $data['total_pa']) * 100;
            }
            if ($data['total_ca']) {
                $data['tx_marque'] = ($data['total_marges'] / $data['total_ca']) * 100;
            }

            self::$cache[$cache_key] = $data;
        }

        return self::$cache[$cache_key];
    }

    // Affichages: 

    public function displayTaux($type = "marque")
    {
        if ($this->can('view')) {
            $totM = (float) $this->getData('total_marges');
            $val = 0;

            if ($totM) {
                if ($type == "marque") {
                    if ((float) $this->getData('total_ca')) {
                        $val = ($totM / $this->getData('total_ca')) * 100;
                    }
                } elseif ($totM && (float) $this->getData('total_pa')) {
                    $val = ($totM / $this->getData('total_pa')) * 100;
                }
            }

            return BimpTools::displayFloatValue((float) $val, 4, ',', true) . ' %';
        }
        return '';
    }

    public function getListFilters()
    {
        global $user;
        $return = array();
        if (!$this->canViewAll()) {
            $return[] = array(
                'name'   => 'id_user',
                'filter' => $user->id
            );
        }

        return $return;
    }

    public function displayAmount($amount_type)
    {
        if ($this->can('view')) {
            $data = $this->getAmountsCacheData();

            if (isset($data[$amount_type])) {
                switch ($amount_type) {
                    case 'total_ca':
                    case 'total_ca_prod':
                    case 'total_ca_serv':
                    case 'total_pa':
                    case 'total_pa_prod':
                    case 'total_pa_serv':
                    case 'total_marges':
                    case 'total_marges_prod':
                    case 'total_marges_serv':
                    case 'total_reval':
                    case 'total_reval_prod':
                    case 'total_reval_serv':
                        return BimpTools::displayMoneyValue((float) $data[$amount_type], 'EUR', true);

                    case 'tx_marge':
                    case 'tx_marque':
                        return BimpTools::displayFloatValue((float) $data[$amount_type], 4, ',', true) . ' %';

                    default:
                        return BimpTools::displayFloatValue((float) $data[$amount_type], 4, ',', true);
                }
            }
        }

        return '';
    }

    public function displaySecteursErrors()
    {
        $html = '';
        if ($this->isLoaded()) {
            $n = 0;

            foreach ($this->getFacturesList() as $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                switch ((int) $this->getData('type')) {
                    case self::TYPE_USER:
                        if (BimpObject::objectLoaded($facture)) {
                            $user = $facture->getCommercial();
                            $secteur = $facture->getData('ef_type');

                            if (BimpObject::objectLoaded($user)) {
                                $user_secteur = $user->getData('secteur');

                                if ($user_secteur) {
                                    if (($user_secteur != $secteur)) {
                                        $n++;
                                    }
                                }
                            }
                        }
                        break;

                    case self::TYPE_ENTREPOT:
                        if (BimpObject::objectLoaded($facture)) {
                            $secteur = $this->getData('secteur');
                            if ($secteur) {
                                $fac_secteur = $facture->getData('ef_type');
                                if ($secteur != $fac_secteur) {
                                    $n++;
                                }
                            }
                        }
                        break;
                }
            }


            $html .= '<span class="badge badge-' . ($n > 0 ? 'danger' : 'success') . '">';
            $html .= $n;
            $html .= '</span>';
        }

        return $html;
    }

    public function displayLinkedObject($display_name = 'nom_url', $display_input_value = true, $no_html = false)
    {
        switch ((int) $this->getData('type')) {
            case self::TYPE_USER:
                return $this->displayData('id_user', $display_name, $display_input_value, $no_html);

            case self::TYPE_ENTREPOT:
                return $this->displayData('id_entrepot', $display_name, $display_input_value, $no_html);
        }

        return '';
    }

    // Rendus HTML: 

    public function renderDetailsView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la commission absent');
        }

        $html = '';

        $tabs = array();

        $content = '';

        $obj_field = '';
        $list_name = '';

        switch ((int) $this->getData('type')) {
            case self::TYPE_USER:
                $obj_field = 'id_user_commission';
                $list_name = 'user_commission';
                break;

            case self::TYPE_ENTREPOT:
                $obj_field = 'id_entrepot_commission';
                $list_name = 'entrepot_commission';
                break;
        }

        // Factures: 

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

        $bc_list = new BC_ListTable($facture, $list_name, 1, null, 'Factures liées', 'fas_link');
        $bc_list->addFieldFilterValue($obj_field, $this->id);
        $bc_list->addIdentifierSuffix('associated_to_' . $this->id);

        $content .= $bc_list->renderHtml();

        $bc_list = new BC_ListTable($facture, $list_name, 1, null, 'Factures disponibles', 'fas_check');
        $bc_list->addFieldFilterValue('rowid', array(
            'in' => $this->getAvailableFacturesList()
        ));
        $bc_list->addIdentifierSuffix('available_for_' . $this->id);

        $content .= $bc_list->renderHtml();

        $tabs[] = array(
            'id'      => 'factures',
            'title'   => 'Factures',
            'content' => $content
        );

        // Revalorisations: 
        $content = '';
        $revalorisation = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');

        $bc_list = new BC_ListTable($revalorisation, $list_name, 1, null, 'Revalorisations liées', 'fas_link');
        $bc_list->addFieldFilterValue($obj_field, $this->id);
        $bc_list->addIdentifierSuffix('associated_to_' . $this->id);

        $content .= $bc_list->renderHtml();

        $bc_list = new BC_ListTable($revalorisation, $list_name, 1, null, 'Revalorisations disponibles', 'fas_check');
        $bc_list->addFieldFilterValue('id', array(
            'in' => $this->getAvailableRevalorisationsList()
        ));
        $bc_list->addIdentifierSuffix('available_for_' . $this->id);

        $content .= $bc_list->renderHtml();

        $tabs[] = array(
            'id'      => 'revalorisations',
            'title'   => 'Revalorisations',
            'content' => $content
        );

        $html .= BimpRender::renderNavTabs($tabs, 'commission_details');

        return $html;
    }

    // Traitements

    public function updateAmounts(&$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $amounts = $this->getAmountsCacheData(true);

        return $this->updateFields($amounts, true, $warnings);
    }

    // Actions

    public function actionCreateCommissions($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commissions créées avec succès';

        $id_group = isset($data['id_group']) ? (int) $data['id_group'] : 0;
        $id_user = isset($data['id_user']) ? (int) $data['id_user'] : 0;
        $date = isset($data['date']) ? (string) $data['date'] : '';

        if (!$id_group && !$id_user) {
            $errors[] = 'Aucun groupe ou utilisateur sélectionné';
        }
        if (!$date) {
            $errors[] = 'Aucune date sélectionnée';
        }

        if (!count($errors)) {
            if ($id_group) {
                $errors = self::CreateGroupCommissions($id_group, $date, $warnings, $success);
            } elseif ($id_user) {
                $success = 'Commission créée avec succès';
                $errors = self::createUserCommission($id_user, $date, $warnings);
            }
        }

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

        $errors = $this->updateField('status', 1);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture effectuée avec succès';

        $errors = $this->updateField('status', 0);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Actions BDS: 

    public function initBdsActionGenerateFactureCommissions($process, &$action_data = array(), &$errors = array(), $extra_data = array())
    {
        $warnings = array();
        $action_data['operation_title'] = 'Facturation AppleCare';

        $file_name = BimpTools::getArrayValueFromPath($extra_data, 'file/0', '');
        $fourn = BimpTools::getArrayValueFromPath($extra_data, 'fourn', '');
        $fourn_label = '';
        $id_fourn = 0;
        $nbElementsPerIteration = 10;
        $cols = array();

        switch ($fourn) {
            case 'techdata':
                $cols = array(
                    'ref_br'      => array(0, 'Billing document'),
                    'desc'        => array(6, 'Material'),
                    'ref_cf'      => array(7, 'Customer PO number'),
                    'serial'      => array(8, 'Serial Number'),
                    'price_ht'    => array(10, 'SO Net Sell Price (Loc )'),
                    'comm_amount' => array(11, 'AC+ Reseller Commission Value (LC)')
                );
                $fourn_label = 'TECHDATA';
                $id_fourn = 229890;
                break;

            case 'ingram':
                $fourn_label = 'INGRAM MICRO';
                $id_fourn = 230496;
                $nbElementsPerIteration = 1;
                $cols = array(
                    'ref_cf'            => array(2, 'Custpo Nbr'),
                    'ref_prod'          => array(7, 'Ref fournisseur'),
                    'desc'              => array(8, 'Product Descr1'),
                    'desc_2'            => array(9, 'Product Descr2'),
                    'qty'               => array(11, 'Quantité'),
                    'total_ht'          => array(12, 'CA HT'),
                    'total_comm_amount' => array(13, 'Montant à verser')
                );
                break;

            default:
                $errors[] = 'Fournisseur sélectionné invalide';
                break;
        }

        if (!$file_name) {
            $errors[] = 'Fichier absent';
        } else {
            $file = DOL_DATA_ROOT . '/' . BimpTools::getTmpFilesDir() . '/' . $file_name;

            if (!file_exists($file)) {
                $errors[] = 'Le fichier semble ne pas avoir été téléchargé correctement';
            }
        }

        if (!count($errors)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $elements = array();
            $line_errors = array();

            $facs_refs = array();
            $serials = array();

            // Vérif de l'en-tête:
            if (isset($lines[0])) {
                $line = str_getcsv($lines[0], ';');

                foreach ($cols as $col_data) {
                    if ($line[$col_data[0]] != $col_data[1]) {
                        $errors[] = 'En-tête absent ou invalide : ' . $col_data[1] . ' (colonne ' . ($col_data[0] + 1) . ') - Veuillez vérifier le fichier';
                    }
                }
            } else {
                $errors[] = 'Fichier vide';
            }


            if (!count($errors)) {
                $i = 0;
                $totals_by_br = array();

                foreach ($lines as $idx => $line) {
                    if ($idx === 0) {
                        continue;
                    }

                    $i++;
                    $line_data = str_getcsv($line, ';');

                    switch ($fourn) {
                        case 'techdata':
                            $serial = $line_data[$cols['serial'][0]];

                            if (!$serial) {
                                $line_errors[] = 'Ligne n° ' . $i . ' : numéro de série absent';
                                continue;
                            }

                            if (in_array($serial, $serials)) {
                                $line_errors[] = 'Ligne n° ' . $i . ' : le n° de série "' . $serial . '" est présent en double dans le fichier';
                                continue;
                            }

                            $id_eq = (int) $this->db->getValue('be_equipment', 'id', 'serial = \'' . $serial . '\' OR serial = \'S' . $serial . '\'');
                            $id_fac = (int) $this->db->getValue('bimp_revalorisation', 'id_facture', 'type = \'fac_ac\' AND (serial = \'' . $serial . '\'' . ($id_eq ? ' OR equipments = \'[' . $id_eq . ']\'' : '') . ')');
                            if ($id_fac) {
                                if (!isset($facs_refs[$id_fac])) {
                                    $facs_refs[$id_fac] = $this->db->getValue('facture', 'ref', 'rowid = ' . $id_fac);
                                }
                                $line_errors[] = 'Ligne n° ' . $i . ' : une facturation de commissionnement existe déjà pour le numéro de série "' . $serial . '" - Facture ' . $facs_refs[$id_fac];
                                continue;
                            }

                            $price = (float) str_replace(',', '.', $line_data[$cols['price_ht'][0]]);

                            if ($price) {
                                $ref_br = $line_data[$cols['ref_br'][0]];

                                if (!isset($totals_by_br[$ref_br])) {
                                    $totals_by_br[$ref_br] = 0;
                                }

                                $totals_by_br[$ref_br] += $price;
                            }

                            $data = array();
                            foreach ($cols as $col_data) {
                                $data[] = $line_data[$col_data[0]];
                            }

                            $serials[] = $serial;
                            $elements[] = $i . ';' . implode(';', $data);
                            break;

                        case 'ingram':
                            $cols = array(
                                'ref_cf'            => array(2, 'Custpo Nbr'),
                                'ref_prod'          => array(7, 'Ref fournisseur'),
                                'desc'              => array(8, 'Product Descr1'),
                                'desc_2'            => array(9, 'Product Descr2'),
                                'qty'               => array(11, 'Quantité'),
                                'total_ht'          => array(12, 'CA HT'),
                                'total_comm_amount' => array(13, 'Montant à verser')
                            );

                            $ref_prod = $line_data[$cols['ref_prod'][0]];
                            if (!$ref_prod) {
                                $line_errors[] = 'Ligne n° ' . $i . ' : référence produit absente';
                                continue;
                            }

                            $id_prod = (int) $this->db->getValue('product', 'rowid', 'ref = \'APP-' . $ref_prod . '\'');
                            if (!$id_prod) {
                                $line_errors[] = 'Ligne n° ' . $i . ' : aucun produit trouvé pour la référence APP-' . $ref_prod;
                                continue;
                            }

                            $ref_cf = $line_data[$cols['ref_cf'][0]];
                            if (!$ref_cf) {
                                $line_errors[] = 'Ligne n° ' . $i . ' : référence de la commande fournisseur absente';
                                continue;
                            }

                            $id_cf = (int) $this->db->getValue('commande_fournisseur', 'rowid', 'ref = \'' . $ref_cf . '\'');
                            if (!$id_cf) {
                                $line_errors[] = 'Ligne n° ' . $i . ' : aucune commande fournisseur trouvée pour la référence ' . $ref_cf;
                                continue;
                            }

                            $qty = (int) $line_data[$cols['qty'][0]];
                            if (!$qty) {
                                $line_errors[] = 'Ligne n° ' . $i . ' : Quantité absente ou nulle';
                                continue;
                            }

                            $desc = trim($line_data[$cols['desc'][0]]);
                            $desc2 = trim($line_data[$cols['desc_2'][0]]);

                            if ($desc2) {
                                $desc .= ' ' . $desc2;
                            }

                            $price = ((float) str_replace(',', '.', preg_replace("/[^0-9\.,\-]+/", '', $line_data[$cols['total_ht'][0]]))) / $qty;
                            $comm_amount = ((float) str_replace(',', '.', preg_replace("/[^0-9\.,\-]+/", '', $line_data[$cols['total_comm_amount'][0]]))) / $qty;
                            $elements[] = $i . ';' . $ref_cf . ';' . $id_cf . ';' . $ref_prod . ';' . $id_prod . ';' . $desc . ';' . $qty . ';' . $price . ';' . $comm_amount;
                            break;
                    }
                }

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Il n\'est pas possible de créer la facture');
                } elseif (empty($elements)) {
                    $errors[] = 'Le fichier fourni est vide';
                }

                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_fourn);

                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Fiche client de ' . $fourn_label . ' absente';
                }

                if (!count($errors)) {
                    if ((int) BimpCore::getConf('use_db_transactions')) {
                        $this->db->db->begin();
                    }

                    $process->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');
                    $process->incProcessed();

                    // Création facture: 
                    $facture = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                                'libelle'        => 'Commissions AppleCare ' . $fourn_label,
                                'fk_soc'         => $id_fourn,
                                'model_pdf'      => 'bimpfact',
                                'datef'          => date('Y-m-d'),
                                'type'           => 0,
                                'entrepot'       => 50,
                                'ef_type'        => 'C',
                                'applecare_data' => array(
                                    'totals_by_br' => $totals_by_br
                                )
                                    ), true, $errors, $warnings);

                    if (BimpObject::objectLoaded($facture)) {
                        $process->incCreated();
                        $process->Success('Facture créée avec succès', $facture);
                        $action_data['steps'] = array(
                            'process_lines' => array(
                                'label'                  => 'Ajout des lignes de facture',
                                'on_error'               => 'continue',
                                'elements'               => $elements,
                                'nbElementsPerIteration' => 10
                            )
                        );
                        $action_data['data'] = array(
                            'id_facture' => $facture->id,
                            'id_fourn'   => $id_fourn,
                            'fourn'      => $fourn
                        );
                    } else {
                        $process->incIgnored();
                        $process->Error('Echec de création de la facture');
                    }
                }
            }
        }

        if ((int) BimpCore::getConf('use_db_transactions')) {
            if (count($errors)) {
                $this->db->db->rollback();
            } else {
                $this->db->db->commit();
            }
        }
    }

    public function executeBdsActionGenerateFactureCommissions($process, $step_name, $elements = array(), &$errors = array(), $operation_extra_data = array(), $action_extra_data = array())
    {
        switch ($step_name) {
            case 'process_lines':
                $id_facture = (int) BimpTools::getArrayValueFromPath($operation_extra_data, 'operation/id_facture', 0);
                if (!$id_facture) {
                    $errors[] = 'ID de la facture absent';
                }

                $fourn = BimpTools::getArrayValueFromPath($operation_extra_data, 'operation/fourn', '');
                if (!$fourn) {
                    $errors[] = 'Type de fournisseur absent';
                }

                if (!count($errors)) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                    if (!BimpObject::objectLoaded($facture)) {
                        $errors[] = 'La facture #' . $id_facture . ' n\'existe pas';
                    } else {
                        if (!empty($elements)) {
                            switch ($fourn) {
                                case 'techdata':
                                    $keys = array(
                                        'num'           => 0,
                                        'ref_fac_fourn' => 1,
                                        'desc'          => 2,
                                        'ref_commande'  => 3,
                                        'serial'        => 4,
                                        'price_ht'      => 5,
                                        'comm_amount'   => 6
                                    );
                                    break;

                                case 'ingram':
                                    $keys = array(
                                        'num'         => 0,
                                        'ref_cf'      => 1,
                                        'id_cf'       => 2,
                                        'ref_prod'    => 3,
                                        'id_prod'     => 4,
                                        'desc'        => 5,
                                        'qty'         => 6,
                                        'price_ht'    => 7,
                                        'comm_amount' => 8
                                    );
                                    break;
                            }

                            BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');
                            
                            $facs_refs = array();
                            foreach ($elements as $line) {
                                $process->setCurrentObjectData('bimpcommercial', 'Bimp_FactureLine');
                                $process->incProcessed();
                                $line_data = str_getcsv($line, ';');
                                $i = $line_data[$keys['num']];
                                $line_desc = '';
                                $serial = '';
                                $equipments = array();
                                $qty = 1;
                                $tva_tx = 0;

                                switch ($fourn) {
                                    case 'techdata':
                                        $line_desc = '<b>' . $line_data[$keys['desc']] . '</b><br/>';
                                        $line_desc .= 'Ref BR: ' . $line_data[$keys['ref_fac_fourn']] . '<br/>';
                                        $line_desc .= 'Ref CF : ' . $line_data[$keys['ref_commande']] . '<br/>';
                                        $line_desc .= 'Montant initial HT : ' . $line_data[$keys['price_ht']];

                                        $serial = $line_data[$keys['serial']];

                                        if (!$serial) {
                                            $process->Alert('Ligne n° ' . $i . ' : numéro de série absent', $facture);
                                            $process->incIgnored();
                                            continue;
                                        }

                                        $id_eq = (int) $this->db->getValue('be_equipment', 'id', 'serial = \'' . $serial . '\' OR serial = \'S' . $serial . '\'');
                                        $id_fac = (int) $this->db->getValue('bimp_revalorisation', 'id_facture', 'type = \'fac_ac\' AND (serial = \'' . $serial . '\'' . ($id_eq ? ' OR equipments = \'[' . $id_eq . ']\'' : '') . ')');
                                        if ($id_fac) {
                                            if (!isset($facs_refs[$id_fac])) {
                                                $facs_refs[$id_fac] = $this->db->getValue('facture', 'ref', 'rowid = ' . $id_fac);
                                            }
                                            $process->Error('Ligne n° ' . $i . ' : une facturation de commissionnement existe déjà pour ce numéro de série - Facture ' . $facs_refs[$id_fac], $facture, $serial);
                                            $process->incIgnored();
                                            continue;
                                        }
                                        break;

                                    case 'ingram':
                                        $tva_tx = BimpCache::cacheServeurFunction('getDefaultTva');
                                        $line_desc = '<b>' . $line_data[$keys['desc']] . '</b><br/>';
                                        $line_desc .= 'Ref AppleCare: ' . $line_data[$keys['ref_prod']] . '<br/>';
                                        $line_desc .= 'Ref CF : ' . $line_data[$keys['ref_cf']] . '<br/>';
                                        $line_desc .= 'Montant initial HT : ' . $line_data[$keys['price_ht']];

                                        $id_cf = (int) $line_data[$keys['id_cf']];
                                        $id_prod = (int) $line_data[$keys['id_prod']];
                                        $ref_prod = $line_data[$keys['ref_prod']];
                                        $qty = (int) $line_data[$keys['qty']];

                                        // Recherche serials dans commande client: 
                                        $where = 'a.id_obj = ' . $id_cf . ' AND cfl.fk_product = ' . $id_prod;
                                        $rows = $this->db->getRows('bimp_commande_fourn_line a', $where, null, 'array', array('a.linked_object_name', 'a.linked_id_object'), null, null, array(
                                            'cfl' => array(
                                                'alias' => 'cfl',
                                                'table' => 'commande_fournisseurdet',
                                                'on'    => 'a.id_line = cfl.rowid'
                                            )
                                        ));

                                        if (!empty($rows)) {
                                            $serials_errors = array();
                                            foreach ($rows as $r) {
                                                if ($r['linked_object_name'] !== 'commande_line' || !(int) $r['linked_id_object']) {
                                                    $serials_errors[] = 'Aucune ligne de commande client liée';
                                                    continue;
                                                }

                                                $line_ac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $r['linked_id_object']);
                                                if (!BimpObject::objectLoaded($line_ac)) {
                                                    $serials_errors[] = 'Ligne de commande client #' . $r['linked_id_object'] . ' non trouvée';
                                                    continue;
                                                }

                                                $ac_prod = $line_ac->getProduct();
                                                if (!BimpObject::objectLoaded($ac_prod)) {
                                                    $serials_errors[] = 'Produit AppleCare non trouvé';
                                                    continue;
                                                }

                                                $id_commande = (int) $line_ac->getData('id_obj');
                                                if (!$id_commande) {
                                                    $serials_errors[] = 'Commande client non trouvée';
                                                    continue;
                                                }

                                                $sql = BimpTools::getSqlSelect('a.id');
                                                $sql .= BimpTools::getSqlFrom('bimp_commande_line', array(
                                                            'cdet' => array(
                                                                'table' => 'commandedet',
                                                                'on'    => 'cdet.rowid = a.id_line'
                                                            ),
                                                            'pef'  => array(
                                                                'table' => 'product_extrafields',
                                                                'on'    => 'pef.fk_object = cdet.fk_product'
                                                            )
                                                ));
                                                $sql .= BimpTools::getSqlWhere(array(
                                                            'a.id_obj'                 => $id_commande,
                                                            'a.position'               => array(
                                                                'operator' => '<',
                                                                'value'    => (int) $line_ac->getData('position')
                                                            ),
                                                            '(cdet.qty + a.qty_modif)' => $qty,
                                                            'pef.serialisable'         => 1
                                                ));
                                                $sql .= BimpTools::getSqlOrderBy('a.position', 'DESC');

//                                                $process->Info('SQL : ' . $sql);
                                                $comm_lines = $this->db->executeS($sql, 'array');

                                                if (is_array($comm_lines)) {
                                                    $process->Info('LINES<pre>' . print_r($comm_lines, 1) . '</pre>');

                                                    $lines_infos = '';
                                                    foreach ($comm_lines as $comm_line) {
                                                        $lines_infos .= 'Vérif ligne de commande #' . $comm_line['id'] . ': <br/>';
                                                        $line_prod = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $comm_line['id']);

                                                        if (!BimpObject::objectLoaded($line_prod)) {
                                                            $lines_infos .= ' - non trouvée <br/><br/>';
                                                            continue;
                                                        }

                                                        $line_eqs = $line_prod->getReservationsEquipmentsList();
                                                        if (count($line_eqs) == $qty) {
                                                            $lines_infos .= count($line_eqs) . ' équipements trouvés.<br/>';
                                                            $prod = $line_prod->getProduct();
                                                            if (BimpObject::objectLoaded($prod)) {
                                                                $eqs_check = true;
                                                                foreach ($line_eqs as $id_eq) {
                                                                    $id_fac = (int) $this->db->getValue('bimp_revalorisation', 'id_facture', 'type = \'fac_ac\' AND equipments LIKE \'%[' . $id_eq . ']%\'');
                                                                    if ($id_fac) {
                                                                        $lines_infos .= 'L\'équipement #' . $id_eq . ' est attribué à la facture #' . $id_fac . '<br/><br/>';
                                                                        $eqs_check = false;
                                                                        break;
                                                                    }
                                                                }

                                                                if ($eqs_check) {
                                                                    $lines_infos .= 'Equipements OK<br/><br/>';
                                                                    $equipments = $line_eqs;
                                                                    $msg = count($equipments) . ' équipement(s) trouvé(s) pour ' . $ac_prod->getLink() . '<br/>';
                                                                    $msg .= 'Libellé : <b>' . $ac_prod->getName() . '<br/><br/>';
                                                                    $msg .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Verifier que le produit ci-dessous correspond bien à cet AppleCare';
                                                                    $msg .= ' (corriger les équipements si ce n\'est pas le cas) : <br/>';
                                                                    $msg .= 'Produit: ' . $prod->getLink() . '<br/>';
                                                                    $msg .= 'Libellé : <b>' . $prod->getName();
                                                                    $process->Info($msg, $facture, '');
                                                                    break;
                                                                }
                                                            }
                                                        } else {
                                                            $lines_infos .= ' le nombre d\'équipements ne correspond pas.<br/><br/>';
                                                        }
                                                    }
                                                    $process->Info('TEST DES LIGNES DE COMMANDE : <br/><br/>' . $lines_infos, $facture, $ref_prod);
                                                } else {
                                                    $process->Alert('Erreur SQL - ' . $this->db->err(), $facture, $ref_prod);
                                                }

                                                if (!empty($equipments)) {
                                                    break;
                                                }
                                            }
                                        }

                                        if (count($serials_errors)) {
                                            $process->Alert(BimpTools::getMsgFromArray($serials_errors, 'Erreurs lors de la recherche des N° de série'), $facture, $ref_prod);
                                        } elseif (empty($equipments)) {
                                            $process->Alert('Aucun n° de série trouvé', $facture, $ref_prod);
                                        }
                                        break;
                                }

                                // Création ligne de facture
                                $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                                $fac_line->validateArray(array(
                                    'id_obj'      => (int) $facture->id,
                                    'type'        => 3,
                                    'pa_editable' => 1
                                ));

                                $price = (float) str_replace(',', '.', $line_data[$keys['comm_amount']]);

                                $fac_line->desc = $line_desc;
                                $fac_line->qty = $qty;
                                $fac_line->pu_ht = $price;
                                $fac_line->tva_tx = $tva_tx;
                                $fac_line->pa_ht = 0;

                                $line_warnings = array();
                                $line_errors = $fac_line->create($line_warnings, true);

                                if (count($line_errors)) {
                                    $process->incIgnored();
                                    $process->Error(BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne n° ' . $i), $facture, $serial);
                                    break;
                                } else {
                                    $process->incCreated();
                                    $process->Success('Ajout de la ligne n° ' . $i . ' OK', $facture, $serial);

                                    // Création revalorisation: 
                                    $process->setCurrentObjectData('bimpfinanc', 'BimpRevalorisation');
                                    $process->incProcessed();

                                    $reval_errors = array();
                                    $status = (int) BimpRevalorisation::STATUS_ATT_EQUIPMENTS;
                                    if ((count($equipments) == $qty) || ($qty == 1 && $serial)) {
                                        $status = (int) BimpRevalorisation::STATUS_ATTENTE;
                                    }

                                    BimpObject::createBimpObject('bimpfinanc', 'BimpRevalorisation', array(
                                        'id_facture'      => $facture->id,
                                        'id_facture_line' => $fac_line->id,
                                        'status'          => $status,
                                        'type'            => 'fac_ac',
                                        'qty'             => $qty,
                                        'amount'          => -$price,
                                        'equipments'      => $equipments,
                                        'serial'          => $serial,
                                        'date'            => date('Y-m-d')
                                            ), true, $reval_errors);

                                    if (count($reval_errors)) {
                                        $process->incIgnored();
                                        $process->Error(BimpTools::getMsgFromArray($reval_errors, 'Ligne n°' . $i . ' - échec de la création de la revalorisation'), $facture, $serial);
                                    } else {
                                        $process->incCreated();
                                        $process->Success('Ligne n°' . $i . ' - Création de la revalorisation OK', $facture, $serial);
                                    }
                                }
                            }
                        }
                    }
                }
                break;
        }
    }

    public function finalizeBdsActionGenerateFactureCommissions($process, &$errors = array(), $operation_extra_data = array(), $action_extra_data = array())
    {
        $result = array();

        $id_facture = (int) BimpTools::getArrayValueFromPath($operation_extra_data, 'id_facture', 0);
        if ($id_facture) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

            if (BimpObject::objectLoaded($facture)) {
                $report = $process->report;

                if (BimpObject::objectLoaded($report)) {
                    $nb_errors = $report->getNbErrors();

                    if ($nb_errors) {
                        if ($nb_errors == 1) {
                            $errors[] = 'Une erreur est survenue. Facture supprimée';
                        } else {
                            $errors[] = $nb_errors . ' erreurs sont survenues. Facture supprimée';
                        }

                        $facture->delete($w, true);
                    } else {
                        $url = $facture->getUrl();

                        if ($url) {
                            $result['success_callback'] = 'window.open(\'' . $url . '\');';
                        }
                    }
                }
            }
        }

        return $result;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            // Ajout des factures dispos: 
            $factures = $this->getAvailableFacturesList((int) BimpTools::getPostFieldValue('paid_only', 0), $this->getData('secteur'));
            $obj_field = '';
            switch ((int) $this->getData('type')) {
                case self::TYPE_USER:
                    $obj_field = 'id_user_commission';
                    break;

                case self::TYPE_ENTREPOT:
                    $obj_field = 'id_entrepot_commission';
                    break;
            }

            foreach ($factures as $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                if (BimpObject::objectLoaded($facture)) {
                    $up_errors = $facture->updateField($obj_field, (int) $this->id);

                    if (count($up_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de la commission pour la facture ' . $facture->getRef());
                    }
                }
            }

            // Ajout des reval dispos: 
            $revals = $this->getAvailableRevalorisationsList((int) BimpTools::getPostFieldValue('paid_only', 0), $this->getData('secteur'));
            foreach ($revals as $id_reval) {
                $reval = BimpCache::getBimpObjectInstance('bimpfinanc', 'BimpRevalorisation', (int) $id_reval);
                if (BimpObject::objectLoaded($reval)) {
                    $up_errors = $reval->updateField($obj_field, (int) $this->id);

                    if (count($up_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de la commission pour la revalorisation #' . $reval->id);
                    }
                }
            }

            $up_warnings = array();
            $up_errors = $this->updateAmounts($up_warnings);

            if (count($up_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement des montants de la commission');
            }

            if (count($up_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($up_warnings, 'Erreurs lors de l\'enregistrement des montants de la commission');
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = $this->id;
        $type = (int) $this->getData('type');

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors) && (int) $id) {
            switch ($type) {
                case self::TYPE_USER:
                    if ($this->db->update('facture', array(
                                'id_user_commission' => 0
                                    ), '`id_user_commission` = ' . (int) $id) <= 0) {
                        $warnings[] = 'Echec du retrait de la commission supprimée des factures associées. ' . $this->db->db->lasterror();
                    }
                    if ($this->db->update('bimp_revalorisation', array(
                                'id_user_commission' => 0
                                    ), '`id_user_commission` = ' . (int) $id) <= 0) {
                        $warnings[] = 'Echec du retrait de la commission supprimée des revalorisations associées. ' . $this->db->db->lasterror();
                    }
                    break;

                case self::TYPE_ENTREPOT:
                    if ($this->db->update('facture', array(
                                'id_entrepot_commission' => 0
                                    ), '`id_entrepot_commission` = ' . (int) $id) <= 0) {
                        $warnings[] = 'Echec du retrait de la commission supprimée des factures associées. ' . $this->db->db->lasterror();
                    }
                    if ($this->db->update('bimp_revalorisation', array(
                                'id_entrepot_commission' => 0
                                    ), '`id_entrepot_commission` = ' . (int) $id) <= 0) {
                        $warnings[] = 'Echec du retrait de la commission supprimée des revalorisations associées. ' . $this->db->db->lasterror();
                    }
                    break;
            }
        }

        return $errors;
    }

    // Méthodes statiques:

    public static function CreateGroupCommissions($id_group, $date, &$warnings = array(), &$success = '')
    {
        $errors = array();

        $users = self::getGroupUsersList($id_group);

        $n = 0;
        foreach ($users as $id_user) {
            $user_warnings = array();
            $user_errors = self::createUserCommission($id_user, $date, $user_warnings);

            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);

            if (count($user_errors)) {
                $errors[] = BimpTools::getMsgFromArray($user_errors, 'Echec de la création de la commission pour l\'utilisateur #' . $id_user . ' (' . $user->getName() . ')');
            } else {
                $n++;
            }

            if (count($user_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($user_warnings, 'Erreurs lors de la création de la commission pour l\'utilisateur #' . $id_user . ' (' . $user->getName() . ')');
            }
        }

        if (!$n) {
            $warnings[] = 'Aucune commission à créer n\'a été trouvée';
            $success = '';
        } elseif ($n <= 1) {
            $success = 'Une commission a été créée avec succès';
        } else {
            $success = $n . ' commissions ont été créées avec succès';
        }

        return $errors;
    }

    public static function createUserCommission($id_user, $date, &$warnings = array())
    {
        $commission = BimpObject::getInstance('bimpfinanc', 'BimpCommission');

        $errors = $commission->validateArray(array(
            'type'    => BimpCommission::TYPE_USER,
            'id_user' => (int) $id_user,
            'date'    => $date
        ));

        if (!count($errors)) {
            $errors = $commission->create($warnings, true);
        }

        return $errors;
    }
}

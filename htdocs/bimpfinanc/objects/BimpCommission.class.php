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
        global $user;

        return (int) 1;//($user->admin ? 1 : 0);
    }

    public function canCreate()
    {
        // todo
        return 1;
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'createCommissions';
            case 'validate':
                return (int) $this->can('create');

            case 'reopen':
                return (int) $this->can('delete');
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

    public function getAvailableFacturesList($paid_only = false)
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
                            $sql .= ' AND f.paye = 1';
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
                            $sql .= ' AND f.paye = 1';
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

    public function getAvailableRevalorisationsList()
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
                    }
                    break;

                case self::TYPE_ENTREPOT:
                    $id_entrepot = (int) $this->getData('id_entrepot');

                    if ($id_entrepot) {
                        $sql = 'SELECT r.id FROM ' . MAIN_DB_PREFIX . 'bimp_revalorisation r ';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields fef ON fef.fk_object = r.id_facture';
                        $sql .= ' WHERE r.id_entrepot_commission = 0';
                        $sql .= ' AND r.status = 1';
                        $sql .= ' AND fef.entrepot = ' . $id_entrepot;
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
            'total_ca'     => 0,
            'total_pa'     => 0,
            'total_reval'  => 0,
            'total_marges' => 0,
            'tx_marge'     => 0,
            'tx_marque'    => 0
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

                    foreach ($lines as $line) {
                        $data['total_ca'] += (float) $line->getTotalHTWithRemises();
                        $data['total_pa'] += ((float) $line->pa_ht * (float) $line->qty);
                    }
                }
            }

            $data['total_marges'] = $data['total_ca'] - $data['total_pa'];

            // Revalorisations: 
            $revals_list = $this->getRevalorisationsList(true);

            foreach ($revals_list as $id_reval) {
                $reval = BimpCache::getBimpObjectInstance('bimpfinanc', 'BimpRevalorisation', (int) $id_reval);

                if (BimpObject::objectLoaded($reval)) {
                    $data['total_reval'] += (float) $reval->getTotal();
                }
            }

            $data['total_marges'] += $data['total_reval'];

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

    public function displayAmount($amount_type)
    {
        $data = $this->getAmountsCacheData();

        if (isset($data[$amount_type])) {
            switch ($amount_type) {
                case 'total_ca':
                case 'total_pa':
                case 'total_marges':
                case 'total_reval':
                    return BimpTools::displayMoneyValue((float) $data[$amount_type], 'EUR', true);

                case 'tx_marge':
                case 'tx_marque':
                    return BimpTools::displayFloatValue((float) $data[$amount_type], 4, ',', true) . ' %';

                default:
                    return BimpTools::displayFloatValue((float) $data[$amount_type], 4, ',', true);
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

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            // Ajout des factures dispos: 
            $factures = $this->getAvailableFacturesList((int) BimpTools::getPostFieldValue('paid_only', 0));
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
            $revals = $this->getAvailableRevalorisationsList();
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

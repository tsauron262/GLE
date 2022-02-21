<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Societe.class.php';

class Bimp_Client extends Bimp_Societe
{

    public $soc_type = "client";
    public static $max_nb_relances = 5;

    // Droits user:

    public function canClientView()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            if ($this->isLoaded() && $this->id == $userClient->getData('id_client')) {
                return 1;
            }
        }

        return 0;
    }

    public function canClientEdit()
    {
//        global $userClient;
//        if (BimpObject::objectLoaded($userClient)) {
//            if ($userClient->isAdmin() && $this->id == (int) $userClient->getData('id_client')) {
//                return 1;
//            }
//        }

        return 0;
    }

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'setRelancesActives':
                if ($user->admin || $user->rights->bimpcommercial->admin_deactivate_relances) {
                    return 1;
                }
                return 0;

            case 'relancePaiements':
                if ($user->admin || (int) $user->id === 1237 ||
                        (!($this->isLoaded()) && $user->rights->bimpcommercial->admin_relance_global) ||
                        ($this->isLoaded() && $user->rights->bimpcommercial->admin_relance_individuelle)) {
                    return 1;
                }
                return 0;

            case 'addFreeRelance':
                if ($this->isLoaded() && ($user->admin || $user->rights->bimpcommercial->admin_recouvrement)) {
                    return 1;
                }
                return 0;

            case 'attributeCommercial':
                return (int) $user->admin;
        }

        return (int) parent::canSetAction($action);
    }

    public function canEditField($field_name)
    {
        if (BimpCore::isContextPublic()) {
            if (in_array($field_name, array('nom', 'name_alias', 'address', 'zip', 'town', 'fk_pays', 'email', 'phone', 'fax', 'skype'))) {
                return 1;
            }

            return 0;
        }

        return parent::canEditField($field_name);
    }

    // Getters booléens:

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'relancePaiements':
                if ($this->isLoaded()) { // L'instance peut ne pas être loadée dans le cas des relances groupées. 
                    if (!(int) $this->getData('relances_actives')) {
                        $errors[] = 'Les relances de paiement ne sont pas activées pour ce client';
                        return 0;
                    }
                }
                return 1;

            case 'addFreeRelance':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isRelanceAllowed(&$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        // Relances activées: 
        if (!(int) $this->getData('relances_actives')) {
            $url = $this->getUrl();
            $msg = 'Les <a href="' . $url . '" target="_blank">relances de paiements</a> sont désactivées pour ce client.<br/>';
            $msg .= '<strong>Motif: </strong>';
            $relances_infos = $this->getData('relances_infos');
            if ($relances_infos) {
                $msg .= $relances_infos;
            } else {
                $msg .= '<span class="warning">Non spécifié</span>';
            }
            $errors[] = $msg;
        }

        // Avoirs disponibles: 
        $available_discounts = (float) $this->getAvailableDiscountsAmounts();
        if ($available_discounts) {
            $url = DOL_URL_ROOT . '/comm/remx.php?id=' . $this->id;
            $errors[] = 'Ce client dispose de <strong>' . BimpTools::displayMoneyValue($available_discounts) . '</strong> de <a href="' . $url . '" target="_blank">remises non consommées' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';
        }

        // Paiements inconnus: 
        $paiements_inc = $this->getTotalPaiementsInconnus();
        if ($paiements_inc) {
            $url = $this->getUrl() . '&navtab=commercial&navtab-commercial_view=client_paiements_inc_list_tab';
            $errors[] = 'Ce client dispose de <strong>' . BimpTools::displayMoneyValue($paiements_inc) . '</strong> de <a href="' . $url . '" target="_blank">paiements non identifiés' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';
        }

        // Avoirs conertibles en remises: 
        $convertible_amount = $this->getConvertibleToDiscountAmount();
        if ($convertible_amount) {
            $url = $this->getUrl() . '&navtab=commercial&navtab-commercial_view=client_factures_list_tab';
            $errors[] = 'Ce client dispose de <strong>' . BimpTools::displayMoneyValue($convertible_amount) . '</strong> <a href="' . $url . '" target="_blank">d\'avoirs ou de trop perçus' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a> pouvant être convertis en remise';
        }

        return (count($errors) ? 0 : 1);
    }

    public function isActifContratAuto()
    {
        global $conf;
        if (isset($conf->global->MAIN_MODULE_BIMPCONTRATAUTO) && $conf->global->MAIN_MODULE_BIMPCONTRATAUTO)
            return 1;
        return 0;
    }

    // Getters params:

    public function getRefProperty()
    {
        return 'code_client';
    }

    public function getSearchListFilters()
    {
        return array(
            'client' => 1
        );
    }

    public function getActionsButtons()
    {
        global $user;

        $buttons = parent::getActionsButtons();

        if ($this->isLoaded()) {
            // Nouvelle propale: 
            $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');
            if ($instance->canCreate()) {
                $buttons[] = array(
                    'label'   => 'Nouvelle proposition commerciale',
                    'icon'    => $instance->params['icon'],
                    'onclick' => $instance->getJsLoadModalForm('default', 'Nouvelle proposition commerciale', array(
                        'fields' => array(
                            'fk_soc' => (int) $this->id
                        )
                            ), null, 'open')
                );
            }

            // Nouvelle commande: 
            $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
            if ($instance->canCreate()) {
                $buttons[] = array(
                    'label'   => 'Nouvelle commande',
                    'icon'    => $instance->params['icon'],
                    'onclick' => $instance->getJsLoadModalForm('default', 'Nouvelle commande', array(
                        'fields' => array(
                            'fk_soc' => (int) $this->id
                        )
                            ), null, 'open')
                );
            }

            // Nouvelle facture: 
            $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
            if ($instance->canCreate()) {
                $buttons[] = array(
                    'label'   => 'Nouvelle facture',
                    'icon'    => $instance->params['icon'],
                    'onclick' => $instance->getJsLoadModalForm('default', 'Nouvelle facture', array(
                        'fields' => array(
                            'fk_soc' => (int) $this->id
                        )
                            ), null, 'open')
                );
            }

            // Nouveau ticket hotline: 
            $instance = BimpObject::getInstance('bimpsupport', 'BS_Ticket');
            if ($instance->canCreate()) {
                $buttons[] = array(
                    'label'   => 'Nouveau ticket hotline',
                    'icon'    => $instance->params['icon'],
                    'onclick' => $instance->getJsLoadModalForm('default', 'Nouveau ticket hotline', array(
                        'fields' => array(
                            'id_client' => (int) $this->id
                        )
                            ), null, 'open')
                );
            }

            // Nouveau SAV: 
            $instance = BimpObject::getInstance('bimpsupport', 'BS_SAV');
            if ($instance->canCreate()) {
                $buttons[] = array(
                    'label'   => 'Nouveau SAV',
                    'icon'    => $instance->params['icon'],
                    'onclick' => $instance->getJsLoadModalForm('default', 'Nouveau SAV', array(
                        'fields' => array(
                            'id_client' => (int) $this->id
                        )
                            ), null, 'open')
                );
            }

            // Nouveau Prêt de matériel: 
            $instance = BimpObject::getInstance('bimpsupport', 'BS_Pret');
            if ($instance->canCreate()) {
                $buttons[] = array(
                    'label'   => 'Nouveau prêt de matériel',
                    'icon'    => $instance->params['icon'],
                    'onclick' => $instance->getJsLoadModalForm('default', 'Nouveau prêt de matériel', array(
                        'fields' => array(
                            'id_client' => (int) $this->id
                        )
                            ), null, 'open')
                );
            }

            // Nouvelle demande inter: 
            $instance = BimpObject::getInstance('bimpfichinter', 'Bimp_Demandinter');
            if ($instance->canCreate()) {
                $buttons[] = array(
                    'label'   => 'Nouvelle demande d\'intervention',
                    'icon'    => $instance->params['icon'],
                    'onclick' => $instance->getJsLoadModalForm('default', 'Nouvelle demande d\\\'intervention', array(
                        'fields' => array(
                            'fk_soc' => (int) $this->id
                        )
                            ), null, 'open')
                );
            }

            // Nouveau Prêt de matériel: 
            $instance = BimpObject::getInstance('bimpfichinter', 'Bimp_Fichinter');
            if ($instance->canCreate()) {
                $buttons[] = array(
                    'label'   => 'Nouvelle fiche intervention',
                    'icon'    => $instance->params['icon'],
                    'onclick' => $instance->getJsLoadModalForm('default', 'Nouvelle fiche intervention', array(
                        'fields' => array(
                            'fk_soc' => (int) $this->id
                        )
                            ), null, 'open')
                );
            }

            // Relances paiements: 
            if ($this->isActionAllowed('relancePaiements') && $this->canSetAction('relancePaiements') && $user->rights->bimpcommercial->admin_relance_individuelle) {
                $buttons[] = array(
                    'label'   => 'Relance paiements',
                    'icon'    => 'fas_comment-dollar',
                    'onclick' => $this->getJsActionOnclick('relancePaiements', array(), array(
                        'form_name' => 'relance_paiements'
                    ))
                );
            }

            // Relances actives: 
            if ($this->canSetAction('setRelancesActives') && $this->isActionAllowed('setRelancesActives')) {
                if ((int) $this->getData('relances_actives')) {
                    $buttons[] = array(
                        'label'   => 'Désactiver les relances',
                        'icon'    => 'fas_times-circle',
                        'onclick' => $this->getJsActionOnclick('setRelancesActives', array(
                            'relances_actives' => 0
                                ), array(
                            'form_name' => 'deactivate_relances'
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'   => 'Activer les relances',
                        'icon'    => 'fas_check-circle',
                        'onclick' => $this->getJsActionOnclick('setRelancesActives', array(
                            'relances_actives' => 1
                                ), array(
                            'confirm_msg' => 'Veuillez confirmer l\\\'activation des relances pour ce client'
                        ))
                    );
                }
            }

            if ($this->isActionAllowed('checkSolvabilite') && $this->canSetAction('checkSolvabilite')) {
                $buttons[] = array(
                    'label'   => 'Vérifier le statut solvabilité',
                    'icon'    => 'fas_check-circle',
                    'onclick' => $this->getJsActionOnclick('checkSolvabilite')
                );
            }
        }

        return $buttons;
    }

    public function getListExtraBulkActions()
    {
        global $user;

        $actions = array();

        if ($this->canSetAction('relancePaiements') && $user->rights->bimpcommercial->admin_relance_global) {
            $actions[] = array(
                'label'   => 'Relancer les impayés',
                'icon'    => 'fas_comment-dollar',
                'onclick' => $this->getJsBulkActionOnclick('relancePaiements', array(), array(
                    'form_name'     => 'relance_paiements',
                    'single_action' => 'true'
                ))
            );
        }

        if ($this->canSetAction('bulkEditField') && $this->canEditField('solvabilite_status')) {
            $actions[] = array(
                'label'   => 'Editer solvabilité',
                'icon'    => 'fas_pen',
                'onclick' => $this->getJsBulkActionOnclick('bulkEditField', array(
                    'field_name'   => 'solvabilite_status',
                    'update_mode'  => 'update_field',
                    'force_update' => 1
                        ), array(
                    'form_name' => 'bulk_edit_field'
                ))
            );
        }

        if ($this->canSetAction('bulkEditField') && $this->canEditField('status')) {
            $actions[] = array(
                'label'   => 'Editer statut',
                'icon'    => 'fas_pen',
                'onclick' => $this->getJsBulkActionOnclick('bulkEditField', array(
                    'field_name'   => 'status',
                    'update_mode'  => 'update_field',
                    'force_update' => 1
                        ), array(
                    'form_name' => 'bulk_edit_field'
                ))
            );
        }

        if ($this->canSetAction('attributeCommercial')) {
            $actions[] = array(
                'label'   => 'Attribuer commercial',
                'icon'    => 'fas_user',
                'onclick' => $this->getJsBulkActionOnclick('attributeCommercial', array(), array(
                    'form_name' => 'attribute_commercial'
                ))
            );
        }
        if ($user->admin) {
            $actions[] = array(
                'label'   => 'Condition/Mode réglement',
                'icon'    => 'fas_user',
                'onclick' => $this->getJsBulkActionOnclick('set_cond_mode_reglement', array(), array(
                    'form_name' => 'cond_mode_reglement'
                ))
            );
        }

        return $actions;
    }

    public function getFilteredListActions()
    {
        global $user;
        $actions = array();

        if ($this->canEditField('solvabilite_status')) {
            $actions[] = array(
                'label'      => 'Editer solvabilité',
                'icon'       => 'fas_pen',
                'action'     => 'bulkEditField',
                'form_name'  => 'bulk_edit_field',
                'extra_data' => array(
                    'field_name'   => 'solvabilite_status',
                    'update_mode'  => 'update_field',
                    'force_update' => 1
                )
            );
        }

        if ($this->canSetAction('bulkEditField') && $this->canEditField('status')) {
            $actions[] = array(
                'label'      => 'Editer statut',
                'icon'       => 'fas_pen',
                'action'     => 'bulkEditField',
                'form_name'  => 'bulk_edit_field',
                'extra_data' => array(
                    'field_name'   => 'status',
                    'update_mode'  => 'update_field',
                    'force_update' => 1
                )
            );
        }

        if ($this->canSetAction('bulkEditField') && $this->canSetAction('attributeCommercial')) {
            $actions[] = array(
                'label'      => 'Attribuer commercial',
                'icon'       => 'fas_user',
                'action'     => 'attributeCommercial',
                'form_name'  => 'attribute_commercial',
                'extra_data' => array()
            );
        }
        if ($user->admin) {
            $actions[] = array(
                'label'     => 'Condition/Mode réglement',
                'icon'      => 'fas_user',
                'action'    => 'set_cond_mode_reglement',
                'form_name' => 'cond_mode_reglement'
            );
        }

        return $actions;
    }

    public function getDefaultListExtraHeaderButtons()
    {
        global $user;
        $buttons = array();

        if ($this->canSetAction('relancePaiements') && $user->rights->bimpcommercial->admin_relance_global) {
            $buttons[] = array(
                'label'       => 'Relance impayés',
                'icon_before' => 'fas_cogs',
                'classes'     => array('btn', 'btn-default'),
                'attr'        => array(
                    'onclick' => $this->getJsActionOnclick('relancePaiements', array(), array(
                        'form_name' => 'relance_paiements',
//                        'on_form_submit' => 'function($form, extra_data) {return onRelanceClientsPaiementsFormSubmit($form, extra_data);}'
                    ))
                )
            );
        }

        return $buttons;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'nb_notes_suivi':
                foreach ($values as $value) {
                    $or_field = array();
                    foreach ($values as $value) {
                        $or_field[] = BC_Filter::getRangeSqlFilter($value, $errors);
                    }

                    if (!empty($or_field)) {
                        $sql = '(SELECT COUNT(suivi.id) FROM ' . MAIN_DB_PREFIX . 'bimpclient_suivi_recouv suivi WHERE suivi.id_societe = a.rowid)';

                        $filters[$sql] = array(
                            'or_field' => $or_field
                        );
                    }
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    // Getters données:

    public function getFacturesToRelanceByClients($to_process_only = false, $allowed_factures = null, $allowed_clients = array(), $relance_idx_allowed = null, $exclude_paid_partially = false, $display_mode = null)
    {
        $clients = array();

        if (is_null($display_mode)) {
            $display_mode = BimpTools::getPostFieldValue('display_mode', '');

            if (!$display_mode) {
                return array();
            }
        }

        if ($this->isLoaded()) {
            $allowed_clients[] = $this->id;
        }

        $id_inc_entrepot = 0;
        $id_excl_entrepot = 0;

        if (empty($allowed_clients) && !$this->isLoaded()) {
            if (preg_match('/^(.+)_WITHOUT_(\d+)$/', $display_mode, $matches)) {
                $display_mode = $matches[1];
                $id_excl_entrepot = (int) $matches[2];
            } elseif (preg_match('/^(.+)_ONLY_(\d+)$/', $display_mode, $matches)) {
                $display_mode = $matches[1];
                $id_inc_entrepot = (int) $matches[2];
            }
        }

        BimpTools::loadDolClass('compta/facture', 'facture');
        $now = date('Y-m-d');

        $where = 'a.type IN (' . Facture::TYPE_STANDARD . ',' . Facture::TYPE_DEPOSIT . ',' . Facture::TYPE_CREDIT_NOTE . ') AND a.paye = 0 AND a.fk_statut = 1 AND a.date_lim_reglement < \'' . $now . '\'';
        $where .= ' AND a.relance_active = 1';
        $where .= ' AND (a.nb_relance > 0 OR a.datec > \'2019-06-30\')';

        if (!empty($allowed_clients)) {
            $where .= ' AND a.fk_soc IN (' . implode(',', $allowed_clients) . ')';
        } elseif ($this->isLoaded()) {
            $where .= ' AND a.fk_soc = ' . (int) $this->id;
        } else {
            $from_date_lim_reglement = BimpCore::getConf('relance_paiements_globale_date_lim', '');

            if ($from_date_lim_reglement) {
                $where .= ' AND a.date_lim_reglement > \'' . $from_date_lim_reglement . '\'';
            }
            $exclude_paid_partially = true;
        }

        $where .= ' AND a.paiement_status != 5';

        if (!$this->isLoaded()) {
            $excluded_modes_reglement = BimpCore::getConf('relance_paiements_globale_excluded_modes_reglement', '');

            if ($excluded_modes_reglement) {
                $where .= ' AND (a.nb_relance > 0 OR a.fk_mode_reglement NOT IN (' . $excluded_modes_reglement . '))';
            }
        }

        if (!is_null($relance_idx_allowed)) {
            $idx_list = array();
            foreach ($relance_idx_allowed as $idx) {
                if ((int) $idx > 0) {
                    $idx_list[] = (int) $idx - 1;
                }
            }

            $where .= ' AND a.nb_relance IN (' . implode(',', $idx_list) . ')';
        } else {
            $where .= ' AND a.nb_relance < ' . self::$max_nb_relances;
        }

        if ($id_inc_entrepot) {
            $where .= ' AND fef.entrepot = ' . $id_inc_entrepot;
        }
        if ($id_excl_entrepot) {
            $where .= ' AND fef.entrepot != ' . $id_excl_entrepot;
        }

        $joins = array();
        if ($id_inc_entrepot || $id_excl_entrepot) {
            $joins[] = array(
                'table' => 'facture_extrafields',
                'alias' => 'fef',
                'on'    => 'fef.fk_object = a.rowid'
            );
        }

        $rows = $this->db->getRows('facture a', $where, null, 'array', array('a.rowid', 'a.fk_soc'), 'a.rowid', 'asc', $joins);

        if (!is_null($rows)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/RelancePaiementPDF.php';
            BimpObject::loadClass('bimpcommercial', 'BimpRelanceClientsLine');
            $relance_delay = BimpCore::getConf('relance_paiements_facture_delay_days', 15);
            $excluded_modes_reglement = explode(',', BimpCore::getConf('relance_paiements_globale_excluded_modes_reglement', ''));

            foreach ($rows as $r) {
                if (!is_null($allowed_factures) && !in_array((int) $r['rowid'], $allowed_factures)) {
                    continue;
                }

                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $r['fk_soc']);
                if (!BimpObject::objectLoaded($client)) {
                    continue;
                }

                $client_relances_actives = (int) $client->getData('relances_actives');

                if ($display_mode === 'relancables' && !$client_relances_actives) {
                    continue;
                }

                if ($display_mode === 'not_relancables' && $client_relances_actives) {
                    continue;
                }

                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);
                if (BimpObject::objectLoaded($fac)) {
                    $fac->checkIsPaid();
                    $remainToPay = $fac->getRemainToPay();

                    if ($remainToPay > 0) {
                        if (!isset($clients[(int) $r['fk_soc']])) {
                            $clients[(int) $r['fk_soc']] = array(
                                'relances_actives'    => (int) $client->getData('relances_actives'),
                                'relances_infos'      => $client->getData('relances_infos'),
                                'available_discounts' => $client->getAvailableDiscountsAmounts(),
                                'convertible_amounts' => $client->getConvertibleToDiscountAmount(),
                                'paiements_inc'       => $client->getTotalPaiementsInconnus(),
                                'relances'            => array()
                            );
                        }

                        $nb_relances = (int) $fac->getData('nb_relance');
                        $relance_idx = $nb_relances + 1;
                        $dates = $fac->getRelanceDates($relance_delay);

                        if ($to_process_only && (!$dates['next'] || $dates['next'] > $now)) {
                            continue;
                        }

                        if (!isset($clients[(int) $r['fk_soc']]['relances'][$relance_idx])) {
                            $clients[(int) $r['fk_soc']]['relances'][$relance_idx] = array();
                        }

                        // Recherche de relance en attente pour la facture: 
                        $where = '`status` IN (' . BimpRelanceClientsLine::RELANCE_ATTENTE_MAIL . ',' . BimpRelanceClientsLine::RELANCE_ATTENTE_COURRIER . ')';
                        $where .= ' AND `factures` LIKE \'%[' . $r['rowid'] . ']%\'';
                        $id_cur_relance = (int) $this->db->getValue('bimp_relance_clients_line', 'id_relance', $where);

                        $clients[(int) $r['fk_soc']]['relances'][$relance_idx][(int) $r['rowid']] = array(
                            'total_ttc'         => (float) $fac->getData('total_ttc'),
                            'remain_to_pay'     => $remainToPay,
                            'nb_relances'       => $nb_relances,
                            'date_lim'          => $dates['lim'],
                            'retard'            => $dates['retard'],
                            'date_last_relance' => $dates['last'],
                            'date_next_relance' => $dates['next'],
                            'id_cur_relance'    => $id_cur_relance
                        );
                    }
                }
            }
        }

        return $clients;
    }

    public function getConvertibleToDiscountAmount()
    {
        $amount = 0;

        if ($this->isLoaded()) {
            BimpTools::loadDolClass('compta/facture', 'facture');

            $sql = 'SELECT f.rowid as id_fac FROM ' . MAIN_DB_PREFIX . 'facture f';
            $sql .= ' WHERE f.fk_soc = ' . $this->id . ' AND f.paye = 0 AND f.fk_statut = 1';
            $sql .= ' AND f.type IN (' . Facture::TYPE_STANDARD . ',' . Facture::TYPE_DEPOSIT . ',' . Facture::TYPE_CREDIT_NOTE . ')';
            $sql .= ' AND (SELECT COUNT(r.rowid) FROM ' . MAIN_DB_PREFIX . 'societe_remise_except r WHERE r.fk_facture_source = f.rowid) = 0';

            $rows = $this->db->executeS($sql, 'array');

            foreach ($rows as $r) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['id_fac']);
                if (BimpObject::objectLoaded($fac)) {
                    $remainToPay = $fac->getRemainToPay();
                    if ($remainToPay < 0) {
                        $amount += abs($remainToPay);
                    }
                }
            }
        }

        return $amount;
    }

    public function getDefaultRelancesDisplayMode()
    {
        $entrepots = BimpCore::getConf('bimpcore_relances_clients_entrepots_speciaux', '');

        if ($entrepots) {
            $entrepots = explode(',', $entrepots);

            foreach ($entrepots as $id_entrepot) {
                return 'relancables_WITHOUT_' . $id_entrepot;
            }
        }

        return 'relancables';
    }

    public function getUnpaidFactures($date_from = '')
    {
        if ($this->isLoaded()) {
            $filters = array(
                'fk_soc'             => (int) $this->id,
                'paye'               => 0,
                'type'               => array(
                    'in' => array(0, 1, 2, 3)
                ),
                'fk_statut'          => array(
                    'in' => array(1, 2)
                ),
                'date_lim_reglement' => array(
                    'operator' => '<',
                    'value'    => date('Y-m-d')
                )
            );

            if ($date_from) {
                $filters['datec'] = array(
                    'operator' => '>',
                    'value'    => $date_from
                );
            }

            return BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', $filters, 'rowid', 'asc');
        }

        return array();
    }

    public function getIdContactForRelances()
    {
        if ((int) $this->getData('id_contact_relances')) {
            return (int) $this->getData('id_contact_relances');
        }

        if ((int) $this->getData('contact_default')) {
            return (int) $this->getData('contact_default');
        }

        return 0;
    }

    public function getTotalUnpayed($since = '2019-06-30')
    {
        $factures = $this->getUnpaidFactures($since);
        $total_unpaid = 0;

        foreach ($factures as $fac) {
//            $fac->checkIsPaid(); // TODO laisser? => non nécessaire: getRemainToPay() effectue le calcul en direct. 
            $total_unpaid += (float) $fac->getRemainToPay(true);
        }

        return $total_unpaid;
    }

    public function getTotalUnpayedTolerance($since = '2019-06-30', $euros_tolere = 2000, $day_tolere = 5)
    {
        $factures = $this->getUnpaidFactures($since);
        $total_unpaid = 0;
        $has_retard = 0;

        if (!empty($factures)) {

            $now = new DateTime();

            foreach ($factures as $fac) {

                $date_tolere = new DateTime($fac->getData('date_lim_reglement'));
                $date_tolere->add(new DateInterval('P' . $day_tolere . 'D'));

                if ($has_retard or $date_tolere < $now)
                    $has_retard = 1;

                $total_unpaid += (float) $fac->getRemainToPay(true);
            }
        }

        if ($has_retard or $euros_tolere < $total_unpaid)
            return $total_unpaid;

        return 0;
    }

    public function getAtradiusFileName($force = false, $show_ext = true)
    {
        $name = 'atradius';
        $ext = '.pdf';
        if ($force || file_exists($this->getFilesDir() . $name . $ext)) {
            if ($show_ext)
                return $name . $ext;
            else
                return $name;
        }
        return 0;
    }

    // Données piste: 

    public function getChorusStructuresList(&$errors = array())
    {
        if ($this->isLoaded($errors)) {
            $siret = $this->getSiret();

            if ($siret) {
                $cache_key = 'client_' . $this->id . '_chorus_structures';

                if (!isset(self::$cache[$cache_key])) {
                    BimpCore::loadBimpApiLib();

                    $api = BimpAPI::getApiInstance('piste');

                    if (is_a($api, 'BimpAPI') && $api->isOk($errors)) {
                        $response = $api->rechercheClientStructures($siret, array(), $errors);

                        if (is_array($response) && !count($errors)) {
                            self::$cache[$cache_key] = $response;
                            return $response;
                        }
                    }
                }
            } else {
                $errors[] = 'N° SIRET absent';
            }
        }

        return null;
    }

    public function getChorusStructureData($id_structure, &$errors = array())
    {
        if ($this->isLoaded($errors)) {
            $cache_key = 'client_' . $this->id . '_chorus_structure_' . $id_structure . '_data';

            if (!isset(self::$cache[$cache_key])) {
                BimpCore::loadBimpApiLib();

                $api = BimpAPI::getApiInstance('piste');

                if (is_a($api, 'BimpAPI') && $api->isOk($errors)) {
                    $response = $api->consulterStructure($id_structure, array(), $errors);

                    if (is_array($response) && !count($errors)) {
                        self::$cache[$cache_key] = $response;
                        return $response;
                    }
                }
            }
        }

        return null;
    }

    public function getChorusStructureServices($id_structure, &$errors = array())
    {
        if ($this->isLoaded($errors)) {
            $cache_key = 'client_' . $this->id . '_chorus_structure_' . $id_structure . '_services';

            if (!isset(self::$cache[$cache_key])) {
                BimpCore::loadBimpApiLib();

                $api = BimpAPI::getApiInstance('piste');

                if (is_a($api, 'BimpAPI') && $api->isOk($errors)) {
                    $response = $api->rechercheClientServices($id_structure, array(), $errors);

                    if (is_array($response) && !count($errors)) {
                        self::$cache[$cache_key] = $response;
                        return $response;
                    }
                }
            }
        }

        return null;
    }

    // Getters Array:

    public function getRelancesDisplayModesArray()
    {
        $entrepots = BimpCore::getConf('bimpcore_relances_clients_entrepots_speciaux', '');

        if ($entrepots) {
            $options = array(
                'all' => 'Tout',
            );
            $entrepots = explode(',', $entrepots);

            foreach ($entrepots as $id_entrepot) {
                $lieu = $this->db->getValue('entrepot', 'lieu', 'rowid = ' . (int) $id_entrepot);

                if ($lieu) {
                    $options['relancables_WITHOUT_' . $id_entrepot] = 'Hors "' . $lieu . '" - Seulement les clients dont les relances sont activées';
                    $options['not_relancables_WITHOUT_' . $id_entrepot] = 'Hors "' . $lieu . '" - Seulement les clients dont les relances sont désactivées';
                    $options['relancables_ONLY_' . $id_entrepot] = '"' . $lieu . '" - Seulement les clients dont les relances sont activées';
                    $options['not_relancables_ONLY_' . $id_entrepot] = '"' . $lieu . '" - Seulement les clients dont les relances sont désactivées';
                }
            }

            if (count($options) > 1) {
                return $options;
            }
        }

        return array(
            'all'             => 'Tout',
            'relancables'     => 'Seulement les clients dont les relances sont activées',
            'not_relancables' => 'Seulement les clients dont les relances sont désactivées'
        );
    }

    // Affichagges: 

    public function displayOutstanding()
    {
        $html = '';

        if ($this->isLoaded()) {
            $encours = $this->getAllEncoursForSiret(false);
            if ($encours['total'] > 0) {
                $html .= '<b>Encours sur factures restant dues: </b>';
                $html .= BimpTools::displayMoneyValue($encours['factures']['socs'][$this->id]);

                if (count($encours['factures']['socs']) > 1) {
                    $html .= '<br/>';

                    foreach ($encours['factures']['socs'] as $id_soc => $soc_encours) {
                        if ($id_soc == $this->id) {
                            continue;
                        }

                        $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_soc);

                        $html .= '<br/>Client ';

                        if (BimpObject::objectLoaded($soc)) {
                            $html .= $soc->getLink();
                        } else {
                            $html .= '#' . $id_soc;
                        }

                        $html .= ' : ' . BimpTools::displayMoneyValue($soc_encours);
                    }

                    $html .= '<br/><br/>';
                    $html .= '<b>Total encours sur factures restant dues pour l\'entreprise (Siren): </b>' . BimpTools::displayMoneyValue($encours['factures']['total']);
                }
            } else {
                $html .= '<span class="warning">Aucun encours trouvé sur cet établissement (Siret)</span>';
            }

            // Calcul encours sur commandes non facturées: 
            $html .= '<div style="margin: 10px 0; padding: 10px; border: 1px solid #737373">';
            $html .= '<b>Encours sur les commandes non facturées: </b>';
            $html .= '<div id="client_' . $this->id . '_encours_non_facture"></div>';
            $onclick = $this->getJsLoadCustomContent('displayEncoursNonFacture', '$(\'#client_' . $this->id . '_encours_non_facture' . '\')');

            $html .= '<div style="margin-top: 10px; text-align: center">';
            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_calculator', 'iconLeft') . 'Calculer';
            $html .= '</span>';
            $html .= '</div>';
            $html .= '</div>';

            // Bouton aperçu: 
            $html .= '<div class="buttonsContainer align-right">';
            $url = DOL_URL_ROOT . '/compta/recap-compta.php?socid=' . $this->id;
            $html .= '<a href="' . $url . '" target="_blank" class="btn btn-default">';
            $html .= 'Aperçu client' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
            $html .= '</div>';
        }

        return $html;
    }

    public function displayNbNotesSuivi()
    {
        if ($this->isLoaded()) {
            $nb = (int) $this->db->getCount('bimpclient_suivi_recouv', 'id_societe = ' . (int) $this->id);

            return '<span class="badge badge-' . ($nb > 0 ? 'info' : 'warning') . '">' . $nb . '</span>';
        }

        return '';
    }

    public function displayAtradiusFile()
    {
        global $user, $langs;
        $html = '';
        $file = $this->getAtradiusFileName();
        $buttons = array();
        $note = BimpObject::getInstance("bimpcore", "BimpNote");
        if (!is_null($file) && $file) {
            $html .= '<a target="__blanck" href="' . DOL_URL_ROOT . '/document.php?modulepart=societe&file=' . $this->id . '/' . $file . '">Fichier</a><br/>';
        } else {
//            return BimpInput::renderInput('file_upload', 'atradius_file');
            // Demande encours altriadus

            $buttons[] = array(
                'label'   => 'Ajouter PDF du Rapport Assurance crédit',
                'icon'    => 'fas_comment-dollar',
                'onclick' => $this->getJsLoadModalForm('atradius_file')
            );

            $list = BimpCache::getBimpObjectObjects('bimpcore', 'BimpNote', array('content'    => array(
                            'operator' => 'like',
                            'value'    => '%licite pour ce client un encours%'
                        ), "obj_type"   => "bimp_object", "obj_module" => $this->module, "obj_name"   => $this->object_name, "id_obj"     => $this->id));

            if (count($list) == 0) {
                $buttons[] = array(
                    'label'   => 'Demander encours',
                    'icon'    => 'far_paper-plane',
                    'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_GROUP, "fk_group_dest" => 680, "content" => "Bonjour, " . $user->getFullName($langs) . " sollicite pour ce client un encours à XX XXX  €\\n\\nNB : si ce client est une Administration publique (son Siren commence par 1 ou par 2) ou si vous pensez qu\'il fait partie des Autres Administrations et Institution demandez 50 000 € d\'encours\\nCette information sera vérifiée par l\'équipe en charge de l\'attribution des encours"), array('form_name' => 'rep'))
                );
            } else {
//                print_r($list);die;
                $noteT = current($list);
                $buttons[] = array(
                    'label'   => 'Refus d\'encours',
                    'icon'    => 'far_paper-plane',
                    'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_USER, "fk_user_dest" => $noteT->getData('user_create'), "content" => "Bonjour\\n\\nVotre demande d\'encours pour le client " . addslashes($this->getName()) . " a été refusée\\n\\nNous pouvons toutefois solliciter Atradius pour une révision de cette décision \\n\\nPour cela nous devons fournir un maximum d\'éléments prouvant la bonne santé financière de cette entreprise (dernier bilan, compte de résultats, etc)\\n\\nMerci de bien vouloir les demander à votre client puis les transmettre à GDS-OLYS-atradiusolys@ldlc.com \\n\\nDans l\'attente de cette éventuelle révision, les commandes ne peuvent être traitées que sur application de l\'une des solutions suivantes :\\nsoit le client nous règle au comptant, c\'est à dire à la commande ou avant livraison ou à réception de facture (ce dernier choix implique un règlement immédiat à date de facture)\\nsoit il accepte qu\'on le prélève à 30 jours date de factures (dans ce cas-là il nous faut un Mandat SEPA accepté et un RIB)\\nNB : la solution du prélèvement concernerait toutes ses factures, et si nous rencontrons le moindre incident de paiement, par exemple un prélèvement rejeté, nous reviendrons obligatoirement à la solution du paiement au comptant"), array('form_name' => 'rep'))
                );
            }
        }
        $buttons[] = array(
            'label'   => 'Demander révision encours',
            'icon'    => 'far_paper-plane',
            'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_GROUP, "fk_group_dest" => 680, "content" => "Bonjour, " . $user->getFullName($langs) . " sollicite pour ce client une révision d\'encours à XX XXX  €"), array('form_name' => 'rep'))
        );
        foreach ($buttons as $button) {
            $html .= BimpRender::renderButton($button) . '<br/>';
        }
        return $html;
    }

    // Rendus HTML:

    public function renderHeaderExtraRight()
    {
        $html = '';

        if ($this->isLoaded() && (int) $this->getData('fournisseur')) {
            $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=fournisseur&id=' . $this->id;
            $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\');">';
            $html .= BimpRender::renderIcon('fas_building', 'iconLeft') . 'Fiche fournisseur' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</span>';
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        return $this->displayData('solvabilite_status');
    }

    public function renderCardView()
    {
        $tabs = array();

        // Infos: 
        $view = new BC_View($this, 'default');
        $view->params['panel'] = 0;
        $tabs[] = array(
            'id'      => 'client_card_infos_tab',
            'title'   => BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos',
            'content' => $view->renderHtml()
        );

        // Contacts / adresses: 
        $tabs[] = array(
            'id'            => 'client_contacts_list_tab',
            'title'         => BimpRender::renderIcon('fas_address-book', 'iconLeft') . 'Contacts / adresses',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_contacts_list_tab .nav_tab_ajax_result\')', array('contacts'), array('button' => ''))
        );

        // Comptes bancaires: 
        $tabs[] = array(
            'id'            => 'client_bank_accounts_list_tab',
            'title'         => BimpRender::renderIcon('fas_university', 'iconLeft') . 'Comptes bancaires',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_bank_accounts_list_tab .nav_tab_ajax_result\')', array('bank_accounts'), array('button' => ''))
        );

        // Utilisateurs: 
        $tabs[] = array(
            'id'            => 'client_users_list_tab',
            'title'         => BimpRender::renderIcon('fas_users', 'iconLeft') . 'Utilisateurs',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_users_list_tab .nav_tab_ajax_result\')', array('client_users'), array('button' => ''))
        );

        // Equipements: 
        $tabs[] = array(
            'id'            => 'client_equipments_list_tab',
            'title'         => BimpRender::renderIcon('fas_desktop', 'iconLeft') . 'Equipements',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_equipments_list_tab .nav_tab_ajax_result\')', array('equipments'), array('button' => ''))
        );

        // Evénements: 
        $tabs[] = array(
            'id'            => 'client_events_list_tab',
            'title'         => BimpRender::renderIcon('fas_calendar-check', 'iconLeft') . 'Evénements',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_events_list_tab .nav_tab_ajax_result\')', array('events'), array('button' => ''))
        );

        // Atradius: 
        $tabs[] = array(
            'id'            => 'client_atradius_list_tab',
            'title'         => BimpRender::renderIcon('fas_dollar-sign', 'iconLeft') . 'Assurance crédit',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderNavtabView', '$(\'#client_atradius_list_tab .nav_tab_ajax_result\')', array('atradius'), array('button' => ''))
        );

        $html = BimpRender::renderNavTabs($tabs, 'card_view');
        $html .= $this->renderNotesList();

        return $html;
    }

    public function renderCommercialView()
    {
        $tabs = array();

        // Propales
        $tabs[] = array(
            'id'            => 'client_propales_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-invoice', 'iconLeft') . 'Propositions commerciales',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_propales_list_tab .nav_tab_ajax_result\')', array('propales'), array('button' => ''))
        );

        // Commandes client
        $tabs[] = array(
            'id'            => 'client_commandes_list_tab',
            'title'         => BimpRender::renderIcon('fas_dolly', 'iconLeft') . 'Commandes',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_commandes_list_tab .nav_tab_ajax_result\')', array('commandes'), array('button' => ''))
        );

        // Livraisons
        $tabs[] = array(
            'id'            => 'client_shipments_list_tab',
            'title'         => BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Livraisons',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_shipments_list_tab .nav_tab_ajax_result\')', array('shipments'), array('button' => ''))
        );

        // Factures
        $tabs[] = array(
            'id'            => 'client_factures_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Factures',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_factures_list_tab .nav_tab_ajax_result\')', array('factures'), array('button' => ''))
        );

        // Contrats
        $tabs[] = array(
            'id'            => 'client_contrats_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-signature', 'iconLeft') . 'Contrats',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_contrats_list_tab .nav_tab_ajax_result\')', array('contrats'), array('button' => ''))
        );

        // Avoirs client: 
        $tabs[] = array(
            'id'            => 'client_remises_except_list_tab',
            'title'         => BimpRender::renderIcon('fas_money-check-alt', 'iconLeft') . 'Avoirs client',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderNavtabView', '$(\'#client_remises_except_list_tab .nav_tab_ajax_result\')', array('remises_except'), array('button' => ''))
        );

        // Paiements non identifiés: 
        $tabs[] = array(
            'id'            => 'client_paiements_inc_list_tab',
            'title'         => BimpRender::renderIcon('fas_question-circle', 'iconLeft') . 'Paiements non identifiés',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_paiements_inc_list_tab .nav_tab_ajax_result\')', array('paiements_inc'), array('button' => ''))
        );

        // Relances paiements: 
        $tabs[] = array(
            'id'            => 'client_relances_list_tab',
            'title'         => BimpRender::renderIcon('fas_comment-dollar', 'iconLeft') . 'Relances paiements',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_relances_list_tab .nav_tab_ajax_result\')', array('relances'), array('button' => ''))
        );

        // Contacts Relances paiements: 
        $tabs[] = array(
            'id'            => 'client_suivi_recouvrement_list_tab',
            'title'         => BimpRender::renderIcon('fas_history', 'iconLeft') . 'Suivi Recouvrement',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_suivi_recouvrement_list_tab .nav_tab_ajax_result\')', array('suivi_recouvrement'), array('button' => ''))
        );

        // stats par date: 
        $tabs[] = array(
            'id'            => 'client_stat_date_list_tab',
            'title'         => BimpRender::renderIcon('fas_chart-bar', 'iconLeft') . 'Stat par date',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_stat_date_list_tab .nav_tab_ajax_result\')', array('stat_date'), array('button' => ''))
        );

        return BimpRender::renderNavTabs($tabs, 'commercial_view');
    }

    public function renderSupportView()
    {
        $html = '';

        $tabs = array();

        // Tickets Hotline
        $tabs[] = array(
            'id'            => 'client_tickets_list_tab',
            'title'         => BimpRender::renderIcon('fas_headset', 'iconLeft') . 'Tickets hotline',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_tickets_list_tab .nav_tab_ajax_result\')', array('tickets'), array('button' => ''))
        );

        // SAV
        $tabs[] = array(
            'id'            => 'client_sav_list_tab',
            'title'         => BimpRender::renderIcon('fas_wrench', 'iconLeft') . 'SAV',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_sav_list_tab .nav_tab_ajax_result\')', array('sav'), array('button' => ''))
        );

        // Prêts matériel

        $tabs[] = array(
            'id'            => 'client_prets_list_tab',
            'title'         => BimpRender::renderIcon('fas_mobile-alt', 'iconLeft') . 'Prêts matériel',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_prets_list_tab .nav_tab_ajax_result\')', array('prets'), array('button' => ''))
        );

        $html = BimpRender::renderNavTabs($tabs, 'suppport_view');

        return $html;
    }

    public function renderIntersView()
    {
        $html = '';

        $tabs = array();

        // DI
        $tabs[] = array(
            'id'            => 'client_di_list_tab',
            'title'         => BimpRender::renderIcon('fas_comment-medical', 'iconLeft') . 'Demandes d\'intervention',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_di_list_tab .nav_tab_ajax_result\')', array('di'), array('button' => ''))
        );

        // FI
        $tabs[] = array(
            'id'            => 'client_fi_list_tab',
            'title'         => BimpRender::renderIcon('fas_ambulance', 'iconLeft') . 'Fiches inter',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_fi_list_tab .nav_tab_ajax_result\')', array('fi'), array('button' => ''))
        );

        $html = BimpRender::renderNavTabs($tabs, 'inters_lists');

        return $html;
    }

    public function renderNavtabView($nav_tab)
    {
        $html = '';

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        switch ($nav_tab) {
            case 'remises_except':
                $html .= '<h3>Avoirs du client ' . $this->getRef() . ' - ' . $this->getName() . '</h3>';
                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_SocRemiseExcept'), 'client', 1, null, 'Avoirs disponibles', 'fas_check');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                $list->addFieldFilterValue('fk_facture', array(
                    'or_field' => array(
                        'IS_NULL',
                        0
                    )
                ));
                $list->addFieldFilterValue('fk_facture_line', array(
                    'or_field' => array(
                        'IS_NULL',
                        0
                    )
                ));
                $html .= $list->renderHtml();

                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_SocRemiseExcept'), 'client', 1, null, 'Avoirs consommés', 'fas_times');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);

                $list->addFieldFilterValue('or_fac_desc', array(
                    'or' => array(
                        'fk_facture'      => array(
                            'operator' => '>',
                            'value'    => 0
                        ),
                        'fk_facture_line' => array(
                            'operator' => '>',
                            'value'    => 0
                        )
                    )
                ));
                $html .= $list->renderHtml();
                break;

            case 'atradius':
                $view = new BC_View($this, 'atradius');
                $html .= $view->renderHtml();
        }

        return $html;
    }

    public function renderLinkedObjectList($list_type)
    {
        $errors = array();
        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $html = '';

        $list = null;
        $list2 = null;
        $client_label = $this->getRef() . ' - ' . $this->getName();

        switch ($list_type) {
            case 'contacts':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_Contact'), 'soc', 1, $this->id, 'Contacts du client "' . $client_label . '"');
                break;

            case 'client_users':
                $list = new BC_ListTable(BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient'), 'client', 1, null, 'Utilisateurs du client "' . $client_label . '"', 'fas_users');
                $list->addFieldFilterValue('id_client', (int) $this->id);
                break;

            case 'bank_accounts':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_SocBankAccount'), 'client', 1, null, 'Comptes bancaires du client "' . $client_label . '"', 'fas_university');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'equipments':
                BimpObject::loadClass('bimpequipment', 'BE_Place');
                $list = new BC_ListTable(BimpObject::getInstance('bimpequipment', 'Equipment'), 'client', 1, null, 'Equipements du client "' . $client_label . '"', 'fas_desktop');
                $list->addJoin('be_equipment_place', 'a.id = place.id_equipment AND place.type = ' . BE_Place::BE_PLACE_CLIENT . ' AND place.position = 1', 'place');
                $list->addFieldFilterValue('place.id_client', (int) $this->id);
                break;

            case 'events':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_ActionComm'), 'client', 1, null, 'Evénements', 'fas_calendar-check');
                $list->addFieldFilterValue('fk_soc', $this->id);
                break;

            case 'propales':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_Propal'), 'client', 1, null, 'Propositions commerciales du client "' . $client_label . '"');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'commandes':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_Commande'), 'client', 1, null, 'Commandes du client "' . $client_label . '"');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'shipments':
                $list = new BC_ListTable(BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment'), 'client', 1, null, 'Livraisons du client "' . $client_label . '"', 'fas_shipping-fast');
                $list->addFieldFilterValue('commande.fk_soc', $this->id);
                $list->addJoin('commande', 'a.id_commande_client=commande.rowid', 'commande');
                break;

            case 'factures':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_Facture'), 'client', 1, null, 'Factures du client "' . $client_label . '"');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'contrats':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcontract', 'BContract_contrat'), 'default', 1, null, 'Contrats du client "' . $client_label . '"', 'fas_file-signature');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'paiements_inc':
                $list = new BC_ListTable(BimpObject::getInstance('bimpfinanc', 'Bimp_PaiementInc'), 'client', 1, null, 'Paiements non identifiés du client "' . $client_label . '"', 'fas_question-circle');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'suivi_recouvrement':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_Client_Suivi_Recouvrement'), 'default', 1, null, 'Suivi Recouvrement "' . $client_label . '"', 'fas_history');
                $list->addFieldFilterValue('id_societe', (int) $this->id);
                break;

            case 'stat_date':
                $obj = BimpObject::getInstance('bimpcommercial', 'Bimp_Stat_Date');
                $list = new BC_ListTable($obj, 'clientMonth', 1, null, 'State par date "' . $client_label . '"', 'fas_history');
                $list->addIdentifierSuffix('month');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                $list2 = new BC_ListTable($obj, 'clientYear', 1, null, 'State par date "' . $client_label . '"', 'fas_history');
                $list2->addIdentifierSuffix('year');
                $list2->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'relances':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'BimpRelanceClientsLine'), 'client', 1, null, 'Relances de paiement du client "' . $client_label . '"', 'fas_comment-dollar');
                $list->addFieldFilterValue('id_client', (int) $this->id);
                break;

            case 'tickets':
                $list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_Ticket'), 'client_admin', 1, null, 'Tickets hotline du client "' . $client_label . '"', 'fas_headset');
                $list->addFieldFilterValue('id_client', (int) $this->id);
                break;

            case 'sav':
                $list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_SAV'), 'client', 1, null, 'SAV du client "' . $client_label . '"', 'fas_wrench');
                $list->addFieldFilterValue('id_client', (int) $this->id);
                break;

            case 'prets':
                $list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_Pret'), 'client', 1, null, 'Prêts de matériel au client "' . $client_label . '"', 'fas_mobile-alt');
                $list->addFieldFilterValue('id_client', $this->id);
                break;

            case 'di':
                $list = new BC_ListTable(BimpObject::getInstance('bimpfichinter', 'Bimp_Demandinter'), 'client', 1, null, 'Demandes d\'intervention du client "' . $client_label . '"', 'fas_comment-medical');
                $list->addFieldFilterValue('fk_soc', $this->id);
                break;

            case 'fi':
                $list = new BC_ListTable(BimpObject::getInstance('bimptechnique', 'BT_ficheInter'), 'client', 1, null, 'Fiche interventions du client "' . $client_label . '"', 'fas_ambulance');
                $list->addFieldFilterValue('fk_soc', $this->id);
                break;
        }

        if (is_a($list, 'BC_ListTable')) {
            $html .= $list->renderHtml();
        } elseif ($list_type) {
            $html .= BimpRender::renderAlerts('La liste de type "' . $list_type . '" n\'existe pas');
        } else {
            $html .= BimpRender::renderAlerts('Type de liste non spécifié');
        }

        if (is_a($list2, 'BC_ListTable'))
            $html .= $list2->renderHtml();

        return $html;
    }

    public function renderFacturesToRelancesInputs($with_checkboxes = true, $clients = null)
    {
        $html = '';

        if (is_null($clients)) {
            $allowed_clients = BimpTools::getPostFieldValue('id_objects', array()); // Cas des clients sélectionnés dans liste. 
            $clients = $this->getFacturesToRelanceByClients(false, null, $allowed_clients);
        }

        $html .= '<div class="factures_to_relance_inputs">';
        if (empty($clients)) {
            $label = 'Aucun paiement à relancer trouvé';
            if ($this->isLoaded()) {
                $label .= ' pour ' . $this->getLabel('this');
            }

            $html .= BimpRender::renderAlerts($label, 'warning');
        } else {
            if ($with_checkboxes) {
                $html .= BimpRender::renderAlerts('Veuillez sélectionner les factures à relancer', 'info');
            }
            $colspan = 8;

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            if ($with_checkboxes) {
                $html .= '<th style="width: 30px;min-width: 30px;"></th>';
                $colspan++;
            }
            $html .= '<th style="min-width: 200px">Facture</th>';
            $html .= '<th>Montant facture</th>';
            $html .= '<th>Reste à payer</th>';
            $html .= '<th>Date échéance</th>';
            $html .= '<th>Retard</th>';
            $html .= '<th>Nb relances déjà effectuées</th>';
            $html .= '<th>Date dernière relance</th>';
            $html .= '<th>Date prochaine relance</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody class="relances_rows">';

            $now = date('Y-m-d');
            foreach ($clients as $id_client => $client_data) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $id_client);
                $relances_allowed = true;
                if (BimpObject::objectLoaded($client)) {
                    if ($with_checkboxes) {
                        $html .= '<tr>';
                        $html .= '<td colspan="' . $colspan . '" style="padding-top: 20px;background-color: #F0F0F0!important;">';

//                        if (!$this->isLoaded()) {
                        $html .= '<span class="bold">Client: </span>' . $client->getLink();
//                        }
                        if (!(int) $client_data['relances_actives']) {
                            $msg = 'Les relances de paiements sont désactivées pour ce client.<br/>';
                            $msg .= '<strong>Motif: </strong>';
                            if (isset($client_data['relances_infos']) && $client_data['relances_infos']) {
                                $msg .= $client_data['relances_infos'];
                            } else {
                                $msg .= '<span class="warning">non spécifié</span>';
                            }
                            $html .= BimpRender::renderAlerts($msg, 'warning');
                            $relances_allowed = false;
                        }

                        if ($client_data['available_discounts'] > 0) {
                            $url = DOL_URL_ROOT . '/comm/remx.php?id=' . $client->id;
                            $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($client_data['available_discounts']) . '</strong> de <a href="' . $url . '" target="_blank">remises non consommées' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>', 'warning');
                            $relances_allowed = false;
                        }

                        if ($client_data['convertible_amounts'] > 0) {
                            $url = $client->getUrl() . '&navtab=commercial&navtab-commercial_view=client_factures_list_tab';
                            $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($client_data['convertible_amounts']) . '</strong> <a href="' . $url . '" target="_blank">d\'avoirs ou de trop perçus' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a> pouvant être convertis en remise', 'warning');
                            $relances_allowed = false;
                        }

                        if ($client_data['paiements_inc']) {
                            $url = $client->getUrl() . '&navtab=commercial&navtab-commercial_view=client_paiements_inc_list_tab';
                            $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($client_data['paiements_inc']) . '</strong> de <a href="' . $url . '" target="_blank">paiements non identifiés' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>', 'warning');
                            $relances_allowed = false;
                        }

                        $html .= '</td>';
                        $html .= '</tr>';
                    }

                    $facs_rows_html = '';
                    $nSelectables = 0;
                    $checkbox_class = 'check_facture_client_' . $client->id;

                    foreach ($client_data['relances'] as $relance_idx => $factures) {
                        foreach ($factures as $id_fac => $fac_data) {
                            $relances_allowed_for_this_fact = true;
                            $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);

                            $acompteEnLiens = $fac->getPotentielRemise();
                            if (count($acompteEnLiens) > 0) {
                                foreach ($acompteEnLiens as $acompteEnLien) {
                                    $html .= '<tr><td colspan="' . $colspan . '">' . BimpRender::renderAlerts('Cette facture présente un crédit en lien avec la commande ' . $acompteEnLien[1]->getLink() . ' de <strong>' . BimpTools::displayMoneyValue($acompteEnLien[0]) . '</strong>', 'warning') . '</td></tr>';
                                }
                                $relances_allowed_for_this_fact = false;
                            }

                            if (BimpObject::objectLoaded($fac)) {
                                $relance = ($relances_allowed_for_this_fact && $relances_allowed && ($now >= $fac_data['date_next_relance']) && (int) $relance_idx <= self::$max_nb_relances && !(int) $fac_data['id_cur_relance']);

                                $facs_rows_html .= '<tr>';
                                if ($with_checkboxes) {
                                    $facs_rows_html .= '<td>';
                                    if ($relance) {
                                        $nSelectables++;
                                        $facs_rows_html .= '<input type="checkbox" class="facture_check ' . $checkbox_class . '" value="' . $id_fac . '" name="factures[]" checked="1"';
                                        $facs_rows_html .= ' data-id_client="' . $id_client . '"';
                                        $facs_rows_html .= '/>';
                                    }
                                    $facs_rows_html .= '</td>';
                                }

                                $facs_rows_html .= '<td>' . $fac->getLink() . '</td>';
                                $facs_rows_html .= '<td class="center">' . BimpTools::displayMoneyValue($fac_data['total_ttc']) . '</td>';
                                $facs_rows_html .= '<td class="center">' . BimpTools::displayMoneyValue($fac_data['remain_to_pay']) . '</td>';

                                if ($fac_data['date_lim']) {
                                    $dt = new DateTime($fac_data['date_lim']);
                                    $facs_rows_html .= '<td class="center"><span class="bold">' . $dt->format('d / m / Y') . '</span></td>';
                                } else {
                                    $facs_rows_html .= '<td></td>';
                                }

                                $facs_rows_html .= '<td>';
                                if ((int) $fac_data['retard'] > 0) {
                                    if ((int) $fac_data['retard'] < 15) {
                                        $class = 'info';
                                    } elseif ((int) $fac_data['retard'] < 30) {
                                        $class = 'warning';
                                    } elseif ((int) $fac_data['retard'] < 45) {
                                        $class = 'danger';
                                    } else {
                                        $class = 'important';
                                    }
                                    $facs_rows_html .= '<span class="' . $class . '">';
                                    $facs_rows_html .= $fac_data['retard'] . ' jour' . ((int) $fac_data['retard'] > 1 ? 's' : '');
                                    $facs_rows_html .= '</span>';
                                }
                                $facs_rows_html .= '</td>';

                                $badge_class = '';

                                switch ((int) $fac_data['nb_relances']) {
                                    case 0: $badge_class = 'info';
                                        break;
                                    case 1: $badge_class = 'warning';
                                        break;
                                    case 2: $badge_class = 'danger';
                                        break;
                                    default: $badge_class = 'important';
                                        break;
                                }
                                $facs_rows_html .= '<td class="center"><span class="badge badge-' . $badge_class . '">' . $fac_data['nb_relances'] . '</span></td>';

                                if ((int) $fac_data['id_cur_relance']) {
                                    $facs_rows_html .= '<td colspan="2">';
                                    $relance = BimpCache::getBimpObjectInstance('bimpcommercial', 'BimpRelanceClients', (int) $fac_data['id_cur_relance']);
                                    if (BimpObject::objectLoaded($relance)) {
                                        $facs_rows_html .= '<span class="warning">';
                                        $facs_rows_html .= 'Relance #' . $relance->id . ' en attente d\'envoi';
                                        $facs_rows_html .= '</span>';
                                        $facs_rows_html .= '<span class="objectIcon" onclick="' . $relance->getJsLoadModalView() . '">';
                                        $facs_rows_html .= BimpRender::renderIcon('fas_eye');
                                        $facs_rows_html .= '</span>';
                                    } else {
                                        $facs_rows_html .= '<span class="danger">';
                                        $facs_rows_html .= 'Erreur: la relance #' . (int) $fac_data['id_cur_relance'] . ' n\'existe plus';
                                        $facs_rows_html .= '</span>';
                                    }
                                    $facs_rows_html .= '</td>';
                                } else {
                                    if ($fac_data['date_last_relance']) {
                                        $dt = new DateTime($fac_data['date_last_relance']);
                                        $facs_rows_html .= '<td class="center"><span class="bold">' . $dt->format('d / m / Y') . '</span></td>';
                                    } else {
                                        $facs_rows_html .= '<td></td>';
                                    }

                                    if ($fac_data['date_next_relance']) {
                                        $dt = new DateTime($fac_data['date_next_relance']);
                                        $facs_rows_html .= '<td class="center"><span class="' . ($now >= $fac_data['date_next_relance'] ? 'danger' : 'success') . '">' . $dt->format('d / m / Y') . '</span></td>';
                                    } else {
                                        $facs_rows_html .= '<td></td>';
                                    }
                                }


                                $facs_rows_html .= '</tr>';
                            }
                        }
                    }

                    if ($nSelectables > 1) {
                        $html .= '<tr>';
                        $html .= '<td colspan="' . $colspan . '">';
                        $html .= BimpInput::renderToggleAllCheckboxes('$(this).findParentByClass(\'relances_rows\')', '.' . $checkbox_class);
                        $html .= '</td>';
                        $html .= '</tr>';
                    }

                    $html .= $facs_rows_html;
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }
        $html .= '</div>';

        return BimpInput::renderInputContainer('factures', '', $html, '', 0, 0, '', array(
                    'check_list' => 1
        ));
    }

    public function renderFreeRelancesFacturesInputs($with_checkboxes = true)
    {
        $relance_idx = (int) BimpTools::getPostFieldValue('relance_idx', 0);

        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID du client absent');
        }

        $html = '';

        $factures = $this->getUnpaidFactures();

        if (!count($factures)) {
            $html .= BimpRender::renderAlerts('Il n\'y a aucune facture impayées pour ce client', 'warning');
        } else {
            $headers = array(
                'fac'               => 'Facture',
                'rtp'               => 'Reste à payer',
                'date_lim'          => 'Date lim. réglement',
                'retard'            => array('label' => 'Retard (jours)', 'align' => 'center'),
                'nb_relance'        => array('label' => 'Nb relances', 'align' => 'center'),
                'activate_relances' => array('label' => 'Continuer le cycle normal des relances', 'align' => 'center')
            );

            $rows = array();

            foreach ($factures as $fac) {
                $fac->checkIsPaid();
                $rtp = $fac->getRemainToPay(true);
                $dates = $fac->getRelanceDates();
                $fac_errors = array();

                $is_relancable = $fac->isRelancable('free', $fac_errors);

                if ($is_relancable && (int) $fac->getData('nb_relance') >= 3) {
                    $is_relancable = 0;
                    $fac_errors[] = (int) $fac->getData('nb_relance') . ' relances déjà faites';
                }

                $input = '';

                if ($is_relancable) {
                    if ($relance_idx >= 3) {
                        $input = '<input type="hidden" value="1" name="fac_' . $fac->id . '_activate_relances"/>';
                        $input .= '<span class="success">OUI</span>';
                    } else {
                        $input = BimpInput::renderInput('toggle', 'fac_' . $fac->id . '_activate_relances', 1);
                    }
                } else {
                    $input = BimpRender::renderAlerts($fac_errors, 'warning');
                }

                $rows[] = array(
                    'item_checkbox'     => $is_relancable,
                    'row_data'          => array(
                        'id_facture' => $fac->id
                    ),
                    'fac'               => $fac->getLink(),
                    'rtp'               => BimpTools::displayMoneyValue($rtp),
                    'date_lim'          => date('d / m / Y', strtotime($dates['lim'])),
                    'retard'            => '<span class="badge badge-' . $dates['retard_class'] . '">' . $dates['retard'] . '</span>',
                    'nb_relance'        => $fac->getData('nb_relance'),
                    'activate_relances' => $input
                );
            }

            $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                        'main_class' => 'bimp_factures_list',
                        'searchable' => true,
                        'sortable'   => true,
                        'sort_col'   => 'retard',
                        'sort_way'   => 'desc',
                        'checkboxes' => true
            ));
        }

        return $html;
    }

    public function renderUnpaidFactures()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID du client absent');
        }

        $html = '';

        // Statut relances client: 
        $html .= '<div>';
        if ((int) $this->getData('relances_actives')) {
            $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Relances activées pour ce client</span>';
        } else {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Relances désactivées pour ce client</span>';
        }
        $html .= '</div>';

        // Recherche factures impayées: 
        $factures = $this->getUnpaidFactures('2019-06-30');

        $total_unpaid = 0;
        $total_abandonned = 0;
        $total_irrecouvrable = 0;
        $total_by_relance = array();

        $facs_rtp = array();

        if (!count($factures)) {
            $html .= BimpRender::renderAlerts('Aucune facture avec retard de paiement', 'success');
        } else {
            $rows = array();
            $now = date('Y-m-d');
            $excluded_modes_reglement = explode(',', BimpCore::getConf('relance_paiements_globale_excluded_modes_reglement', ''));
            foreach ($factures as $fac) {
                $fac->checkIsPaid();
                $rtp = (float) $fac->getRemainToPay(true);
                $facs_rtp[(int) $fac->id] = $rtp;

                if ($rtp > 0) {
                    $total_unpaid += $rtp;

                    if ($fac->getData('fk_statut') == 3) {
                        $total_abandonned += $rtp;
                    } elseif ($fac->getData('paiement_status') == 5) {
                        $total_irrecouvrable += $rtp;
                    } else {
                        $relance_idx = (int) $fac->getData('nb_relance');

                        if (!isset($total_by_relance[$relance_idx])) {
                            $total_by_relance[$relance_idx] = 0;
                        }

                        $total_by_relance[$relance_idx] += $rtp;
                    }
                }

                $dates = $fac->getRelanceDates();

                $next_relance = '';
                if ($fac->getData('nb_relance') >= 5) {
                    $next_relance .= '<span class="important">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Dépôt contentieux effectué</span>';
                } elseif ((int) $fac->getData('paiement_status') == 5) {
                    $next_relance .= '<span class="important">' . BimpRender::renderIcon('fas_exclamation', 'iconLeft') . 'Déclarée irrécouvrable</span>';
                } elseif (!$fac->getData('relance_active')) {
                    $next_relance .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Relances désactivées</span>';
                } elseif ($fac->getData('fk_statut') == 3) {
                    $next_relance .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Facture abandonnée</span>';
                } elseif ($dates['next']) {
                    $next_relance .= '<span class="' . ($dates['next'] <= $now ? 'success' : 'danger') . '">' . date('d / m / Y', strtotime($dates['next'])) . '</span>';
                    if (in_array((int) $fac->getData('fk_mode_reglement'), $excluded_modes_reglement)) {
                        $next_relance .= '<br/>';
                        $next_relance .= '<span class="warning">Exclue des relances globales (mode réglement: ' . $fac->displayData('fk_mode_reglement') . ')</span>';
                    }
                } else {
                    $next_relance .= '<span class="warning">Non défini</span>';
                }

                $rows[] = array(
                    'fac'          => $fac->getLink(),
                    'rtp'          => BimpTools::displayMoneyValue($rtp),
                    'date_lim'     => $fac->displayData('date_lim_reglement'),
                    'retard'       => '<span class="badge badge-' . $dates['retard_class'] . '">' . $dates['retard'] . '</span>',
                    'nb_relance'   => $fac->displayData('nb_relance', 'bagde'),
                    'last_relance' => $fac->displayData('date_relance'),
                    'next_relance' => $next_relance
                );
            }

            // Affichage des montants impayés: 
            if ($total_unpaid) {
                $html .= '<div style="margin-bottom: 15px">';
                $html .= BimpRender::renderInfoCard('Total impayés', $total_unpaid, array(
                            'icon'      => 'fas_dollar-sign',
                            'data_type' => 'money',
                            'class'     => 'secondary',
                ));

                if ($total_abandonned) {
                    $html .= BimpRender::renderInfoCard('Abandonné', $total_abandonned, array(
                                'icon'      => 'fas_times-circle',
                                'data_type' => 'money',
                                'class'     => 'danger',
                    ));
                }

                if ($total_irrecouvrable) {
                    $html .= BimpRender::renderInfoCard('Irrécouvrable', $total_irrecouvrable, array(
                                'icon'      => 'fas_times-circle',
                                'data_type' => 'money',
                                'class'     => 'important',
                    ));
                }

                for ($i = 0; $i <= 5; $i++) {
                    if (isset($total_by_relance[$i]) && (float) $total_by_relance[$i]) {
                        $label = '';
                        $icon = '';
                        $class = '';
                        switch ($i) {
                            case 0;
                                $label = 'Jamais relancé<sup>*</sup>';
                                $class = 'success';
                                $icon = 'fas_times';
                                break;

                            case 1:
                                $label = 'Relancé 1 fois<sup>*</sup>';
                                $class = 'info';
                                $icon = 'fas_hourglass-start';
                                break;

                            case 2:
                                $label = 'Relancé 2 fois';
                                $class = 'info';
                                $icon = 'fas_hourglass-half';
                                break;

                            case 3:
                                $label = 'Relancé 3 fois';
                                $class = 'warning';
                                $icon = 'fas_hourglass-end';
                                break;

                            case 4:
                                $label = 'Mis en demeure';
                                $class = 'danger';
                                $icon = 'fas_exclamation';
                                break;

                            case 5:
                                $label = 'Dépôt contentieux';
                                $class = 'important';
                                $icon = 'fas_exclamation-circle';
                                break;
                        }

                        $html .= BimpRender::renderInfoCard($label, $total_by_relance[$i], array(
                                    'icon'      => $icon,
                                    'data_type' => 'money',
                                    'class'     => $class,
                        ));
                    }
                }
                $html .= '<div class="smallInfo">(Depuis le 1er Juillet 2019)</div>';
                $html .= '</div>';
            }

            // Boutons d'actions: 
            $buttons = array();
            if ($this->canSetAction('setRelancesActives') && $this->isActionAllowed('setRelancesActives')) {
                if ((int) $this->getData('relances_actives')) {
                    $buttons[] = array(
                        'label'   => 'Désactiver les relances',
                        'icon'    => 'fas_times-circle',
                        'onclick' => $this->getJsActionOnclick('setRelancesActives', array(
                            'relances_actives' => 0
                                ), array(
                            'form_name' => 'deactivate_relances'
                        )),
                        'type'    => 'danger'
                    );
                } else {
                    $buttons[] = array(
                        'label'   => 'Activer les relances',
                        'icon'    => 'fas_check-circle',
                        'onclick' => $this->getJsActionOnclick('setRelancesActives', array(
                            'relances_actives' => 1
                                ), array(
                            'confirm_msg' => 'Veuillez confirmer l\\\'activation des relances pour ce client'
                        ))
                    );
                }
            }

            if ($this->isActionAllowed('relancePaiements') && $this->canSetAction('relancePaiements')) {
                $buttons[] = array(
                    'label'   => 'Effectuer une nouvelle relance',
                    'icon'    => 'fas_comment-dollar',
                    'onclick' => $this->getJsActionOnclick('relancePaiements', array(), array(
                        'form_name' => 'relance_paiements'
                    ))
                );
            }

            if ($this->isActionAllowed('addFreeRelance') && $this->canSetAction('addFreeRelance')) {
                $buttons[] = array(
                    'label'   => 'Enregistrer une relance déjà effectuée',
                    'icon'    => 'fas_pen',
                    'onclick' => $this->getJsActionOnclick('addFreeRelance', array(), array(
                        'form_name'      => 'free_relance',
                        'on_form_submit' => 'onClientAddFreeRelanceFormSubmit'
                    ))
                );
            }

            if (count($buttons)) {
                $html .= '<div class="buttonsContainer align-right">';
                foreach ($buttons as $button) {
                    $html .= BimpRender::renderButton($button);
                }
                $html .= '</div>';
            }

            // Montants à traiter client: 
            $available_discounts = $this->getAvailableDiscountsAmounts();
            $convertible_amounts = $this->getConvertibleToDiscountAmount();
            $paiements_inc = $this->getTotalPaiementsInconnus();

            if ($available_discounts > 0) {
                $url = DOL_URL_ROOT . '/comm/remx.php?id=' . $this->id;
                $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($available_discounts) . '</strong> de <a href="' . $url . '" target="_blank">remises non consommées' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>', 'warning');
            }

            if ($convertible_amounts > 0) {
                $url = $this->getUrl() . '&navtab=commercial&navtab-commercial_view=client_factures_list_tab';
                $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($convertible_amounts) . '</strong> <a href="' . $url . '" target="_blank">d\'avoirs ou de trop perçus' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a> pouvant être convertis en remise', 'warning');
            }

            if ($paiements_inc) {
                $url = $this->getUrl() . '&navtab=commercial&navtab-commercial_view=client_paiements_inc_list_tab';
                $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($paiements_inc) . '</strong> de <a href="' . $url . '" target="_blank">paiements non identifiés' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>', 'warning');
            }

            // Affichage liste: 
            if (count($rows)) {
                $headers = array(
                    'fac'          => 'Facture',
                    'rtp'          => 'Reste à payer',
                    'date_lim'     => 'Date lim. réglement',
                    'retard'       => array('label' => 'Retard (jours)', 'align' => 'center'),
                    'nb_relance'   => array('label' => 'Nb relances', 'align' => 'center'),
                    'last_relance' => array('label' => 'Date dernère relance', 'align' => 'center'),
                    'next_relance' => array('label' => 'Date prochaine relance', 'align' => 'center')
                );

                $list_html = BimpRender::renderBimpListTable($rows, $headers, array(
                            'searchable' => true,
                            'sortable'   => true
                ));

                $html .= BimpRender::renderPanel('Factures avec retard de paiement', $list_html, '', array(
                            'type' => 'secondary'
                ));
            }
        }

        return $html;
    }

    public function renderContratAuto()
    {
        global $user, $db;
        $html = '';
        $html .= '<h3> Contrats actifs '
                . '<div class="miniCustomDiv">Services inactifs</div>'
                . '<div class="miniCustomDiv isGreen">Services actifs</div>'
                . '<div class="miniCustomDiv isRed">Services (bientôt) périmés</div>'
                . '<div class="miniCustomDiv isGrey">Services fermés</div>'
                . '</h3>';

        $html .= '<div id="containerForActif" class="customContainer">';
        $html .= '</div>';
        $html .= '<h3> Contrats inactifs</h3>';
        $html .= '<div id="containerForInactif" class="customContainer">';
        $html .= '</div>';

        $html .= '<h3>Nouveau contrat</h3>';

        if ($user->rights->contrat->creer) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcontratauto/class/BimpContratAuto.class.php';
            $staticbca = new BimpContratAuto($db);

            $tabService = $staticbca->getTabService($db);
            $html .= '<div class="alert alert-danger" id="alertError"></div>';
            $html .= '<h5>Services</h5>';

            $html .= '<div id="invisibleDiv">';

            foreach ($tabService as $service) {
                $html .= '<div id=' . $service['id'] . ' name="' . $service['name'] . '" class="customDiv containerWithBorder">';
                $html .= '<div class="customDiv fixDiv">' . $service['name'] . '</div><br>';
                $isFirst = true;
                foreach ($service['values'] as $value) {
                    if ($isFirst) {
                        $html .= '<div class="customDiv divClikable isSelected">' . $value . '</div>';
                        $isFirst = false;
                    } else {
                        $html .= '<div class="customDiv divClikable">' . $value . '</div>';
                    }
                }
                $html .= '</div>';
            }

            /* Date début */

            $html .= '<h5>Date de début</h5>';

            $html .= '<input type="text" id="datepicker"><p id="errorDate"></p><br>';

            $html .= '<h5>N° de série (Séparés par un saut de ligne)</h5>';

            $html .= '<textarea id="note"></textarea><br>';

            $html .= '<div class="buttonCustom">Valider</div>';

            $html .= '</div>';
        } else {
            $html .= "<p>Vous n'avez pas les droits requis pour créer un nouveau contrat.<p>";
        }
        return $html;
    }

    // Traitements:

    public function relancePaiements($clients = array(), $mode = 'global', &$warnings = array(), &$pdf_url = '', $date_prevue = null, $send_emails = true)
    {
        $errors = array();

        if (is_null($date_prevue)) {
            $date_prevue = date('Y-m-d');
        }

        if (empty($clients) && $mode == 'cron') {
            // Si liste de factures clients non fournie et si mode cron, on récup la liste complète des factures à relancer. 
            $clients = $this->getFacturesToRelanceByClients(true);
        }

        if (empty($clients)) {
            $errors[] = 'Aucune paiement de facture à relancer';
        } else {
            global $user;
            $now = date('Y-m-d');

            // Création de la relance:
            $relance = BimpObject::createBimpObject('bimpcommercial', 'BimpRelanceClients', array(
                        'id_user'     => (BimpObject::objectLoaded($user) ? (int) $user->id : 1),
                        'date'        => date('Y-m-d H:i:s'),
                        'mode'        => $mode,
                        'date_prevue' => $date_prevue
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($relance)) {
                $acomptes = array();

                foreach ($clients as $id_client => $client_data) {
                    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $id_client);

                    if (!BimpObject::objectLoaded($client)) {
                        $warnings[] = 'Le client d\'ID ' . $id_client . ' n\'existe plus';
                        continue;
                    }

                    if (!(int) $client->getData('relances_actives')) {
                        $warnings[] = 'Les relances de paiements sont désactivées pour le client "' . $client->getRef() . ' - ' . $client->getName() . '"';
                        continue;
                    }

                    if ($client_data['available_discounts'] > 0 || $client_data['convertible_amounts'] > 0) {
                        $warnings[] = 'Le client "' . $client->getRef() . ' - ' . $client->getName() . '" ne peut pas être relancé car il dispose d\'avoirs non consommés ou de trop perçus non convertis en remises';
                        continue;
                    }

                    if ((float) $client_data['paiements_inc']) {
                        $warnings[] = 'Le client "' . $client->getRef() . ' - ' . $client->getName() . '" ne peut pas être relancé car il dispose de paiements non identifiés';
                        continue;
                    }

                    foreach ($client_data['relances'] as $relance_idx => $factures) {
                        if ($relance_idx > self::$max_nb_relances) {
                            continue;
                        }

                        $facturesByContacts = array();

                        // Trie des factures par contact: 
                        foreach ($factures as $id_fac => $fac_data) {
                            if ((int) $fac_data['id_cur_relance']) {
                                continue;
                            }

                            $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                            if (!BimpObject::objectLoaded($fac)) {
                                $warnings[] = 'La facture d\'ID ' . $id_fac . ' n\'existe plus';
                                continue;
                            }

                            if ($fac->getData('type') == Facture::TYPE_DEPOSIT) {
                                $acomptes[(int) $fac->id] = $fac;
                                continue;
                            }

                            if ($now < $fac_data['date_next_relance']) {
                                $warnings[] = 'La facture "' . $fac->getRef() . '" n\'a pas été traitée car sa date de prochaine relance est ultérieure à la date du jour';
                                continue;
                            }

                            $id_contact = (int) $this->getData('id_contact_relances');

                            if (!$id_contact) {
                                $id_contact = $fac->getIdContactForRelance($relance_idx);
                            }

                            if (!isset($facturesByContacts[$id_contact])) {
                                $facturesByContacts[$id_contact] = array();
                            }

                            $facturesByContacts[$id_contact][] = $fac;
                        }

                        // Création des lignes de relance: 
                        $i = 0;
                        foreach ($facturesByContacts as $id_contact => $contact_factures) {
                            $i++;
                            $contact = null;
                            $email = '';

                            if ((int) $id_contact) {
                                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                            }

                            if ($relance_idx <= 3) {
                                if (BimpObject::objectLoaded($contact)) {
                                    $email = $contact->getData('email');
                                } else {
                                    $email = $client->getData('email');
                                }
                            }

                            // Création de la ligne de relance: 
                            $relanceLine = BimpObject::getInstance('bimpcommercial', 'BimpRelanceClientsLine');
                            $relanceLine->validateArray(array(
                                'id_relance'  => $relance->id,
                                'id_client'   => $client->id,
                                'id_contact'  => $id_contact,
                                'relance_idx' => $relance_idx,
                                'email'       => $email,
                                'date_prevue' => $date_prevue
                            ));

                            $relanceLine_factures = array();
                            foreach ($contact_factures as $fac) {
                                $relanceLine_factures[] = (int) $fac->id;
                            }

                            $relanceLine->set('factures', $relanceLine_factures);
                            $rl_warnings = array();
                            $rl_errors = $relanceLine->create($rl_warnings, true);
                            if (count($rl_errors)) {
                                $err_label = 'Relance n°' . $relance_idx . ' - client: ' . $client->getRef() . ' ' . $client->getName();
                                if (BimpObject::objectLoaded($contact)) {
                                    $err_label .= ' - contact: ' . $contact->getName();
                                }
                                $warnings[] = BimpTools::getMsgFromArray($rl_errors, 'Echec de la création de la ligne de relance (' . $err_label . ')');
                            }
                        }
                    }
                }

                if ($date_prevue == $now) {
                    if ($send_emails) {
                        // Envoi des emails: 
                        $mail_warnings = array();
                        $mail_errors = $relance->sendEmails(false, $mail_warnings);
                        $mail_errors = array_merge($mail_errors, $mail_warnings);
                        if (count($mail_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($mail_errors, 'Erreurs lors de l\'envoi des emails de relance');
                        }
                    }

                    // Génération des PDF à envoyer par courrier: 
                    $pdf_warnings = array();
                    $pdf_errors = $relance->generateRemainToSendPdf($pdf_url, $pdf_warnings);
                    $pdf_errors = array_merge($pdf_errors, $pdf_warnings);
                    if (count($pdf_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($pdf_errors, 'Erreurs lors de la génération des PDF de relance par courrier');
                    }
                }

                if (!empty($acomptes)) {
                    foreach ($acomptes as $id_fac => $fac) {
                        if (BimpObject::objectLoaded($fac)) {
                            $client = $fac->getChildObject('client');
                            $subject = 'ACOMPTE IMPAYE - Client : ';
                            if (BimpObject::objectLoaded($client)) {
                                $subject .= $client->getRef() . ' ' . $client->getName();
                            } else {
                                $subject = 'inconnu';
                            }

                            $msg = 'Bonjour, <br/><br/>';
                            $msg .= 'L\'acompte ' . $fac->getRef();

                            if (BimpObject::objectLoaded($client)) {
                                $msg .= 'Pour le client ' . $client->getRef() . ' ' . $client->getName();
                            }
                            $msg .= ' est impayé.<br/>';
                            $msg .= 'Merci d\'en vérifier la raison et de procéder à sa régularisation.<br/>';
                            $msg .= 'Lien acompte: ' . $fac->getLink();

                            if (mailSyn2($subject, 'recouvrementolys@bimp.fr', '', $msg)) {
                                $fac->updateField('relance_active', 0);
                            } else {
                                $warnings[] = 'Echec de l\'envoi du mail de notification au service recouvrement pour l\'acompte ' . $fac->getRef();
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function checkRelancesLinesContact(&$errors = array())
    {
        if ($this->isLoaded()) {
            $contact = $this->getChildObject('contact_relances');

            if (BimpObject::objectLoaded($contact)) {
                $relances = BimpCache::getBimpObjectObjects('bimpcommercial', 'BimpRelanceClientsLine', array(
                            'id_client'  => (int) $this->id,
                            'status'     => array(
                                'operator' => '<',
                                'value'    => 10
                            ),
                            'or_contact' => array(
                                'or' => array(
                                    'id_contact' => array(
                                        'operator' => '!=',
                                        'value'    => $contact->id
                                    ),
                                    'email'      => array(
                                        'operator' => '!=',
                                        'value'    => (string) $contact->getData('email')
                                    )
                                )
                            )
                ));

                $w = array();
                foreach ($relances as $relance) {
                    $relance->set('id_contact', (int) $contact->id);
                    $relance->set('email', $contact->getData('email'));
                    $rel_err = $relance->update($w, true);

                    if (count($rel_err)) {
                        $errors[] = BimpTools::getMsgFromArray($rel_err, 'Echec de la mise à jour de la ligne de relance #' . $relance->id . ' (enregistrement du contact pour les relances de paiement)');
                    }
                }
            }
        }
    }

    // Actions:

    public function actionRelancePaiements($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        global $user;

        $mode = 'global';

        if ($this->isLoaded()) {
            if (!$user->admin && !$user->rights->bimpcommercial->admin_relance_global) {
                $errors[] = 'Vous n\'avez pas la permission d\'effectuer des relances groupées';
            }
            $mode = 'indiv';
        } else {
            if (!$user->admin && !$user->rights->bimpcommercial->admin_relance_individuelle) {
                $errors[] = 'Vous n\'avez pas la permission d\'effectuer des relances individuelles';
            }
        }

        if (!count($errors)) {
            $factures = BimpTools::getArrayValueFromPath($data, 'factures', array());
            $send_emails = BimpTools::getArrayValueFromPath($data, 'send_emails', true);
            $date_prevue = BimpTools::getArrayValueFromPath($data, 'date_prevue', date('Y-m-d'));

            if (!is_array($factures) || empty($factures)) {
                $errors[] = 'Aucune facture à relancer spécifiée';
            } else {
                $clients = $this->getFacturesToRelanceByClients(true, $factures);
                $pdf_url = '';
                $errors = $this->relancePaiements($clients, $mode, $warnings, $pdf_url, $date_prevue, $send_emails);

                if ($pdf_url) {
                    $success_callback = 'window.open(\'' . $pdf_url . '\')';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSetRelancesActives($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $active = BimpTools::getArrayValueFromPath($data, 'relances_actives', null);
        $infos = '';

        if (is_null($active)) {
            $errors[] = 'Valeur pour les relances actives ou non absente';
        } else {
            if (!(int) $active) {
                $infos = BimpTools::getArrayValueFromPath($data, 'relances_infos', '');
                if (!$infos) {
                    $errors[] = 'Vous devez obligatoirement spécifié un motif pour la déactivation des relances';
                }
            }
        }

        if (!count($errors)) {
            if ($active) {
                if ((int) $this->getData('relances_actives')) {
                    $errors[] = 'Les relances sont déjà activées pour ce client';
                } else {
                    $errors = $this->updateField('relances_actives', 1, null, true, true);

                    if (!count($errors)) {
                        $this->updateField('relances_infos', '', null, true, true);
                        $this->updateField('date_relances_deactivated', null);
                    }
                }

                $success = 'Relances activées';
            } else {
                if (!(int) $this->getData('relances_actives')) {
                    $errors[] = 'Les relances sont déjà désactivées pour ce client';
                } else {
                    $errors = $this->updateField('relances_actives', 0, null, true, true);
                    if (!count($errors)) {
                        $this->updateField('relances_infos', $infos, null, true, true);
                        $this->updateField('date_relances_deactivated', date('Y-m-d'));
                    }
                }

                $success = 'Relances désactivées';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSet_cond_mode_reglement($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Maj avec succès';

        $ids = array();

        if ($this->isLoaded()) {
            $ids[] = (int) $this->id;
        } else {
            $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        }

        $id_mode = (int) BimpTools::getArrayValueFromPath($data, 'mode_reglement', 0);
        $id_cond = (int) BimpTools::getArrayValueFromPath($data, 'cond_reglement', 0);

        if (!$id_mode && !$id_cond) {
            $errors[] = 'Aucun mode et aucune condition sélectionné';
        }

        if (empty($ids)) {
            $errors[] = 'Aucun client sélectionné';
        }

        if (!count($errors)) {
            if ($id_mode) {
                $this->db->db->query('UPDATE ' . MAIN_DB_PREFIX . 'societe SET mode_reglement = ' . $id_mode . ' WHERE rowid IN (' . implode(",", $ids) . ')');
            }
            if ($id_cond) {
                $this->db->db->query('UPDATE ' . MAIN_DB_PREFIX . 'societe SET cond_reglement = ' . $id_cond . ' WHERE rowid IN (' . implode(",", $ids) . ')');
            }
        }
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAttributeCommercial($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commercial attribué avec succès';

        $ids = array();

        if ($this->isLoaded()) {
            $ids[] = (int) $this->id;
        } else {
            $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        }

        $id_comm = (int) BimpTools::getArrayValueFromPath($data, 'id_user_commercial', 0);

        if (!$id_comm) {
            $errors[] = 'Aucun utilisateur sélectionné';
        }

        if (empty($ids)) {
            $errors[] = 'Aucun client sélectionné';
        }

        if (!count($errors)) {
            // societe_commerciaux
            $where = 'fk_soc IN(' . implode(',', $ids) . ')';
            if ($this->db->delete('societe_commerciaux', $where) <= 0) {
                $errors[] = 'Echec du retrait des commerciaux actuels - ' . $this->db->err();
            }

            if (!count($errors)) {
                foreach ($ids as $id_client) {
                    if ($this->db->insert('societe_commerciaux', array(
                                'fk_user' => $id_comm,
                                'fk_soc'  => $id_client
                            )) <= 0) {
                        $errors[] = 'Client #' . $id_client . ' - Echec de l\'enregistrement du commercial - ' . $this->db->err();
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddFreeRelance($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Relance enregistrée avec succès';

        $relance_idx = (int) BimpTools::getArrayValueFromPath($data, 'relance_idx', 0);

        if (!$relance_idx) {
            $errors[] = 'N° de relance absent';
        } elseif ($relance_idx < 1 || $relance_idx > 5) {
            $errors[] = 'N° de relance invalide - doit être compris entre 1 et 5';
        }

        $date = BimpTools::getArrayValueFromPath($data, 'date', '');

        if (!$date) {
            $errors[] = 'Aucune date sélectionnée';
        }

        $method = (int) BimpTools::getArrayValueFromPath($data, 'method', 0);

        if (!$method) {
            $errors[] = 'Aucune méthode de relance sélectionnée';
        }

        $id_contact = (int) BimpTools::getArrayValueFromPath($data, 'id_contact', 0);
        $note = BimpTools::getArrayValueFromPath($data, 'note', '');

        $factures = BimpTools::getArrayValueFromPath($data, 'factures', array());
        $facs = array();

        if (empty($factures)) {
            $errors[] = 'Aucune facture sélectionnée';
        } else {
            foreach ($factures as $fac_data) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $fac_data['id']);

                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'La facture #' . $fac_data['id'] . ' n\'existe pas';
                } else {
                    $fac_errors = array();
                    if (!$facture->isRelancable('free', $fac_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Facture ' . $facture->getLink());
                    } else {
                        $facs[] = $facture->id;
                    }
                }
            }
        }

        if (!count($errors)) {
            global $user;

            $relance = BimpObject::createBimpObject('bimpcommercial', 'BimpRelanceClients', array(
                        'id_user'     => $user->id,
                        'date'        => date('Y-m-d H:i:s'),
                        'mode'        => 'free',
                        'date_prevue' => $date,
                        'note'        => $note
                            ), true, $errors, $warnings);

            if (!count($errors) && BimpObject::objectLoaded($relance)) {
                $line_errors = array();
                $line_warnings = array();

                BimpObject::createBimpObject('bimpcommercial', 'BimpRelanceClientsLine', array(
                    'id_relance'   => (int) $relance->id,
                    'id_client'    => (int) $this->id,
                    'status'       => ($relance_idx <= 3 ? 10 : ($relance_idx < 5 ? 11 : 12)),
                    'relance_idx'  => (int) $relance_idx,
                    'method'       => (int) $method,
                    'id_contact'   => (int) $id_contact,
                    'date_prevue'  => $date,
                    'date_send'    => $date . ' 00:00:00',
                    'id_user_send' => (int) $user->id,
                    'factures'     => $facs
                        ), true, $line_errors, $line_warnings);
                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors);
                    $relance->delete($warnings, true);
                }
                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings);
                }

                if (!count($errors)) {
                    foreach ($factures as $fac_data) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $fac_data['id']);

                        if (BimpObject::objectLoaded($facture)) {
                            if ((int) $facture->getData('nb_relance') < $relance_idx) {
                                $facture->updateField('nb_relance', $relance_idx);
                                $facture->updateField('date_relance', $date);
                            }

                            $activate = (int) BimpTools::getArrayValueFromPath($fac_data, 'activate_relances', 0);
                            $facture->updateField('relance_active', $activate);
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides:

    public function validate()
    {
        $errors = array();

        if ((int) $this->getData('id_contact_relances') && (int) $this->getData('id_contact_relances') !== (int) $this->getInitData('id_contact_relances')) {
            $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $this->getData('id_contact_relances'));
            if (!BimpObject::objectLoaded($contact)) {
                $errors[] = 'Le contact #' . $this->getData('id_contact_relances') . ' pour les relances de paiement n\'existe pas';
            } elseif (!$contact->getData('email')) {
                $errors[] = 'Adresse e-mail absente pour le contact de relances des paiements.Veuillez corriger ce contact ou en sélectionner un autre.';
            }
        }

        $errors = BimpTools::merge_array($errors, parent::validate());

        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        $this->checkRelancesLinesContact($warnings);

        parent::onSave($errors, $warnings);
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $name = 'file';
        $success = 'Fichier uploadé';
        $errors = array();

        if (file_exists($_FILES[$name]["tmp_name"])) {
            if (stripos($_FILES[$name]['name'], '.pdf') > 0) {
                $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile');
                $values = array();
                $values['parent_module'] = 'bimpcore';
                $values['parent_object_name'] = 'Bimp_Societe';
                $values['id_parent'] = $this->id;
                $values['file_name'] = $this->getAtradiusFileName(true, false);
                $values['is_deletable'] = 0;

                $file->validateArray($values);

                $errors = $file->create();
                if (!count($errors))
                    $this->set('date_atradius', dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S'));
            } else
                $errors[] = 'Uniquement des fichier PDF';
        }

        if (count($errors))
            return $errors;

        return parent::update($warnings, $force_update);
    }

    // Méthodes statiques: 

    public static function checkRelancesDeactivatedToNotify()
    {
        global $db;
        $bdb = new BimpDb($db);

        $dt = new DateTime();
        $dt->sub(new DateInterval('P14D'));
        $date_begin = $dt->format('Y-m-d');

        $where = 'status = 1 AND relances_actives = 0 AND date_relances_deactivated <= \'' . $date_begin . '\'';
        $rows = $bdb->getRows('societe', $where, null, 'array', array('rowid', 'date_relances_deactivated'));

        if (is_array($rows)) {
            $interval = new DateInterval('P7D');
            foreach ($rows as $r) {
                if (!$r['date_relances_deactivated']) {
                    continue;
                }

                $dt = new DateTime($date_begin);
                $date = $dt->format('Y-m-d');
                $i = 0; // Précaution boucle infinie

                while ($date >= $r['date_relances_deactivated']) {
                    if ($r['date_relances_deactivated'] == $date) {
                        // Envoi mail
                        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $r['rowid']);

                        if (BimpObject::objectLoaded($client) && $client->getData('is_subsidiary') == 0) {
//                            $is_comm_default = false;
//                            $email = $client->getCommercialEmail(false, false);
//
//                            if (!$email) {
//                                $email = $client->getCommercialEmail(true, true);
//                                $is_comm_default = true;
//                            }
                            $email = 'recouvrementolys@bimp.fr';

                            if ($email) {
                                $subject = 'Client ' . $client->getRef() . ' ' .$client->getName() . ' - Vérifier relances à réactiver';

                                $html = 'Bonjour,<br/><br/>';
                                $html .= 'Les relances du client ' . $client->getLink();
                                $html .= ' ont été désactivées le ' . $dt->format('d / m / Y') . '<br/><br/>';

                                $html .= '<b>Il convient de vérifier ce compte et en réactiver les relances dès que possible</b>';

//                                if ($is_comm_default) {
//                                    $html .= '<br/><br/>Note: vous avez reçu ce message car vous êtes commercial par défaut.<br/>';
//                                    $html .= 'Pour ne plus recevoir de type de notification pour ce client, il est nécessaire de lui attribuer un commercial attitré';
//                                }

                                mailSyn2($subject, $email . ',f.martinez@bimp.fr', '', $html);
                            }
                        }
                        break;
                    }

                    $dt->sub($interval);
                    $date = $dt->format('Y-m-d');

                    $i++;
                    if ($i > 200) {
                        break;
                    }
                }
            }
        }
    }
}

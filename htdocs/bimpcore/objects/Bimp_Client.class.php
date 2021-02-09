<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Societe.class.php';

class Bimp_Client extends Bimp_Societe
{

    public $soc_type = "client";
    public static $max_nb_relances = 5;

    // Droits user:

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
                        $user->rights->bimpcommercial->admin_relance_global ||
                        $user->rights->bimpcommercial->admin_relance_individuelle) {
                    return 1;
                }
                return 0;

            case 'attributeCommercial':
                return (int) $user->admin;
        }

        return (int) parent::canSetAction($action);
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

        return $actions;
    }

    public function getFilteredListActions()
    {
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

    // Getters données:

    public function getFacturesToRelanceByClients($to_process_only = false, $allowed_factures = null, $allowed_clients = array(), $relance_idx_allowed = null, $exclude_paid_partially = false)
    {
        $clients = array();
        $display_mode = BimpTools::getPostFieldValue('display_mode', '');

        if (!$display_mode) {
            return array();
        }

        $id_inc_entrepot = 0;
        $id_excl_entrepot = 0;

        if (preg_match('/^(.+)_WITHOUT_(\d+)$/', $display_mode, $matches)) {
            $display_mode = $matches[1];
            $id_excl_entrepot = (int) $matches[2];
        } elseif (preg_match('/^(.+)_ONLY_(\d+)$/', $display_mode, $matches)) {
            $display_mode = $matches[1];
            $id_inc_entrepot = (int) $matches[2];
        }

        BimpTools::loadDolClass('compta/facture', 'facture');
        $now = date('Y-m-d');

        $where = 'a.type IN (' . Facture::TYPE_STANDARD . ',' . Facture::TYPE_DEPOSIT . ',' . Facture::TYPE_CREDIT_NOTE . ') AND a.paye = 0 AND a.fk_statut = 1 AND a.date_lim_reglement < \'' . $now . '\'';
        $where .= ' AND a.relance_active = 1';
        $where .= ' AND a.datec > \'2019-06-30\'';

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

//        if ($exclude_paid_partially) {
//            $where .= ' AND a.fk_mode_reglement NOT IN(3,60)';
//        }

        $excluded_modes_reglement = BimpCore::getConf('relance_paiements_globale_excluded_modes_reglement', '');

        if ($excluded_modes_reglement) {
            $where .= ' AND a.fk_mode_reglement NOT IN (' . $excluded_modes_reglement . ')';
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

//                    if ($exclude_paid_partially && $remainToPay < round((float) $fac->dol_object->total_ttc, 2)) { // Par précaution même si déjà filtré en sql via "paiement_status"
//                        continue;
//                    }

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

    public function getTotalPaiementsInconnus()
    {
        if ($this->isLoaded()) {
            return (float) $this->db->getSum('Bimp_PaiementInc', 'total', 'fk_soc = ' . (int) $this->id);
        }

        return 0;
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

    public function getEncours($withAutherSiret = true)
    {
        if ($withAutherSiret && $this->getData('siren') . 'x' != 'x' && strlen($this->getData('siren')) == 9) {
            $tot = 0;
            $lists = BimpObject::getBimpObjectObjects($this->module, $this->object_name, array('siren' => $this->getData('siren')));
            foreach ($lists as $idO => $obj) {
                $tot += $obj->getEncours(false);
            }
            return $tot;
        } else {
            $values = $this->dol_object->getOutstandingBills();
            if (isset($values['opened']))
                return $values['opened'];
        }
        return 0;
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
        $tot = 0;
        if ($this->isLoaded()) {
            $values = $this->getEncours(false);
            $tot += $values;

            if ($values > 0) {
                $html .= BimpTools::displayMoneyValue($values);
            } else {
                $html .= '<span class="warning">Aucun encours trouvé sur cet établissement (Siret)</span>';
            }

            $html .= '<div class="buttonsContainer align-right">';
            $url = DOL_URL_ROOT . '/compta/recap-compta.php?socid=' . $this->id;
            $html .= '<a href="' . $url . '" target="_blank" class="btn btn-default">';
            $html .= 'Aperçu client' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
            $html .= '</div>';
        }

        if ($this->getData('siren') . 'x' != 'x' && strlen($this->getData('siren')) == 9) {
            $lists = BimpObject::getBimpObjectObjects($this->module, $this->object_name, array('siren' => $this->getData('siren')));
            //        print_r($lists);
            foreach ($lists as $idO => $obj) {
                if ($idO != $this->id) {
                    $enCli = $obj->getEncours(false);
                    $tot += $enCli;
                    $html .= '<br/>Client ' . $obj->getLink() . ' : ' . BimpTools::displayMoneyValue($enCli);
                }
            }

            if ($tot != $values)
                $html .= '<br/><br/>Encours TOTAL sur l\'entreprise (Siren): ' . BimpTools::displayMoneyValue($tot);
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
            'ajax_callback' => $this->getJsLoadCustomContent('renderNavtabView', '$(\'#client_relances_list_tab .nav_tab_ajax_result\')', array('client_relances_list_tab'), array('button' => ''))
        );

        // Contacts Relances paiements: 
        $tabs[] = array(
            'id'            => 'client_suivi_recouvrement_list_tab',
            'title'         => BimpRender::renderIcon('fas_comment-dollar', 'iconLeft') . 'Suivi Recouvrement',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#client_suivi_recouvrement_list_tab .nav_tab_ajax_result\')', array('suivi_recouvrement'), array('button' => ''))
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
            case 'client_relances_list_tab':
                $html .= '<h2>Relances des factures impayées</h2>';
                $data = $this->getFacturesToRelanceByClients();

                if (!empty($data)) {
                    foreach ($data as $id_client => $client_data) {
                        if ((int) $id_client === (int) $this->id) {
                            $totals = array(
                                0 => 0,
                                1 => 0,
                                2 => 0,
                                3 => 0,
                                4 => 0
                            );

                            foreach ($client_data['relances'] as $relances_idx => $relances) {
                                $idx = $relances_idx;
                                if ($idx > 5) {
                                    $idx = 5;
                                }
                                foreach ($relances as $id_facture => $facture_data) {
                                    $totals[0] += (float) $facture_data['remain_to_pay'];
                                    $totals[(int) $relances_idx] += (float) $facture_data['remain_to_pay'];
                                }
                            }

                            if ($totals[0]) {
                                $html .= '<div style="margin-bottom: 15px;">';
                                foreach (array(
                            0 => array('label' => 'Total paiements en attente', 'icon' => 'fas_dollar-sign', 'class' => 'secondary'),
                            1 => array('label' => 'Jamais relancé', 'icon' => 'fas_times', 'class' => 'secondary'),
                            2 => array('label' => 'Relancé 1 fois', 'icon' => 'fas_hourglass-start', 'class' => 'info'),
                            3 => array('label' => 'Relancé 2 fois', 'icon' => 'fas_hourglass-half', 'class' => 'warning'),
                            4 => array('label' => 'Relancé 3 fois', 'icon' => 'fas_hourglass-end', 'class' => 'danger'),
                            5 => array('label' => 'Relancé 4 fois ou +', 'icon' => 'fas_exclamation-circle', 'class' => 'important'),
                                ) as $idx => $total_type) {
                                    if ($idx && !$totals[$idx]) {
                                        continue;
                                    }
                                    $html .= BimpRender::renderInfoCard($total_type['label'], $totals[$idx], array(
                                                'icon'      => $total_type['icon'],
                                                'data_type' => 'money',
                                                'class'     => $total_type['class'],
                                    ));
                                }
                                $html .= '</div>';
                            }

                            if ($client_data['available_discounts'] > 0) {
                                $url = DOL_URL_ROOT . '/comm/remx.php?id=' . $this->id;
                                $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($client_data['available_discounts']) . '</strong> de <a href="' . $url . '" target="_blank">remises non consommées' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>', 'warning');
                            }

                            if ($client_data['convertible_amounts'] > 0) {
                                $url = $this->getUrl() . '&navtab=commercial&navtab-commercial_view=client_factures_list_tab';
                                $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($client_data['convertible_amounts']) . '</strong> <a href="' . $url . '" target="_blank">d\'avoirs ou de trop perçus' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a> pouvant être convertis en remise', 'warning');
                            }

                            if ($client_data['paiements_inc']) {
                                $url = $this->getUrl() . '&navtab=commercial&navtab-commercial_view=client_paiements_inc_list_tab';
                                $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($client_data['paiements_inc']) . '</strong> de <a href="' . $url . '" target="_blank">paiements non identifiés' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>', 'warning');
                            }
                        }
                    }

                    $html .= BimpRender::renderPanel(BimpRender::renderIcon('fas_hourglass-start', 'iconLeft') . 'Factures en attente de paiement', $this->renderFacturesToRelancesInputs(false, $data), '', array(
                                'type' => 'secondary'
                    ));
                }

                $html .= $this->renderLinkedObjectList('relances');
                break;

            case '':
                break;
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
        $client_label = $this->getRef() . ' - ' . $this->getName();

        switch ($list_type) {
            case 'contacts':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_Contact'), 'soc', 1, $this->id, 'Contacts du client "' . $client_label . '"');
                break;

            case 'client_users':
                $list = new BC_ListTable(BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient'), 'full', 1, null, 'Utilisateurs du client "' . $client_label . '"', 'fas_users');
                $list->addFieldFilterValue('attached_societe', (int) $this->id);
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
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_Client_Suivi_Recouvrement'), 'default', 1, null, 'Suivi Recouvrement "' . $client_label . '"', 'fas_question-circle');
                $list->addFieldFilterValue('id_societe', (int) $this->id);
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
                $list = new BC_ListTable(BimpObject::getInstance('bimpfichinter', 'Bimp_Fichinter'), 'client', 1, null, 'Fiche interventions du client "' . $client_label . '"', 'fas_ambulance');
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
                                        $facs_rows_html .= '<input type="checkbox" class="facture_check ' . $checkbox_class . '" value="' . $id_fac . '" name="factures[]"' . ($relance ? ' checked="1"' : '');
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

    public function relancePaiements($clients = array(), $mode = 'auto', &$warnings = array(), &$pdf_url = '', $date_prevue = null, $send_emails = true)
    {
        // modes: auto / man
        // mode auto (cron) => relances mails uniquement (1ère et 2ème relances) 

        $errors = array();

        if (is_null($date_prevue)) {
            $date_prevue = date('Y-m-d');
        }

        if (empty($clients) && $mode = 'auto') {
            // Si liste de factures clients non fournie et si mode auto, on récup la liste complète des factures à relancer. 
            $clients = $this->getFacturesToRelanceByClients(true);
        }

        if (empty($clients)) {
            $errors[] = 'Aucune paiement de facture à relancer';
        } else {
            global $user;
            $now = date('Y-m-d');

            // Création de la relance:
            $relance = BimpObject::createBimpObject('bimpcommercial', 'BimpRelanceClients', array(
                        'id_user'     => ($mode === 'man' && BimpObject::objectLoaded($user) ? (int) $user->id : 0),
                        'date'        => date('Y-m-d H:i:s'),
                        'mode'        => $mode,
                        'date_prevue' => $date_prevue
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($relance)) {
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

        if ($this->isLoaded()) {
            if (!$user->admin && !$user->rights->bimpcommercial->admin_relance_global) {
                $errors[] = 'Vous n\'avez pas la permission d\'effectuer des relances groupées';
            }
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
                $errors = $this->relancePaiements($clients, 'man', $warnings, $pdf_url, $date_prevue, $send_emails);

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
}

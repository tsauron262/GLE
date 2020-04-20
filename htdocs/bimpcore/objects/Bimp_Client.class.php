<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Societe.class.php';

class Bimp_Client extends Bimp_Societe
{

    public $soc_type = "client";

    // Droits user:

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'relancePaiements':
                if ($user->admin) {
                    return 1;
                }

                return 0; // todo: Droit à définir
        }

        return (int) parent::canSetAction($action);
    }

    // Getters booléens:

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'relancePaiements':
                if ($this->isLoaded()) { // L'instance peut ne pas être loadée dans le cas des relances groupés. 
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
            $errors[] = 'Les <a href="' . $url . '" target="_blank">relances de paiements</a> sont désactivées pour ce client';
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
            if ($this->isActionAllowed('relancePaiements') && $this->canSetAction('relancePaiements')) {
                $buttons[] = array(
                    'label'   => 'Relance paiements',
                    'icon'    => 'fas_comment-dollar',
                    'onclick' => $this->getJsActionOnclick('relancePaiements', array(), array(
                        'form_name' => 'relance_paiements'
                    ))
                );
            }
        }

        return $buttons;
    }

    public function getListExtraBulkActions()
    {
        $actions = array();

        if ($this->canSetAction('relancePaiements')) {
            $actions[] = array(
                'label'   => 'Relancer les impayés',
                'icon'    => 'fas_comment-dollar',
                'onclick' => $this->getJsBulkActionOnclick('relancePaiements', array(), array(
                    'form_name'     => 'relance_paiements',
                    'single_action' => 'true'
                ))
            );
        }

        return $actions;
    }

    public function getDefaultListExtraHeaderButtons()
    {
        $buttons = array();

        if ($this->canSetAction('relancePaiements')) {
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

        BimpTools::loadDolClass('compta/facture', 'facture');
        $now = date('Y-m-d');
        $now_tms = strtotime($now);

        $where = 'type IN (' . Facture::TYPE_STANDARD . ',' . Facture::TYPE_DEPOSIT . ',' . Facture::TYPE_CREDIT_NOTE . ') AND paye = 0 AND fk_statut = 1 AND date_lim_reglement < \'' . $now . '\'';
        $where .= ' AND relance_active = 1';
        $where .= ' AND datec > \'2019-06-30\'';

        if (!empty($allowed_clients)) {
            $where .= ' AND fk_soc IN (' . implode(',', $allowed_clients) . ')';
        } elseif ($this->isLoaded()) {
            $where .= ' AND fk_soc = ' . (int) $this->id;
        } else {
            $from_date_lim_reglement = BimpCore::getConf('relance_paiements_globale_date_lim', '');

            if ($from_date_lim_reglement) {
                $where .= ' AND date_lim_reglement > \'' . $from_date_lim_reglement . '\'';
            }

            $excluded_modes_reglement = BimpCore::getConf('relance_paiements_globale_excluded_modes_reglement', '');

            if ($excluded_modes_reglement) {
                $where .= ' AND fk_mode_reglement NOT IN (' . $excluded_modes_reglement . ')';
            }

            $exclude_paid_partially = true;
        }

        if ($exclude_paid_partially) {
            $where .= ' AND paiement_status = 0';
        }

        if (!is_null($relance_idx_allowed)) {
            $idx_list = array();
            foreach ($relance_idx_allowed as $idx) {
                if ((int) $idx > 0) {
                    $idx_list[] = (int) $idx - 1;
                }
            }

            $where .= ' AND nb_relance IN (' . implode(',', $idx_list) . ')';
        }

        $rows = $this->db->getRows('facture', $where, null, 'array', array('rowid', 'fk_soc'), 'rowid', 'asc');

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

                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);
                if (BimpObject::objectLoaded($fac)) {
                    $fac->checkIsPaid();
                    $remainToPay = (float) $fac->getRemainToPay(true);

                    if ($exclude_paid_partially && $remainToPay < (float) $fac->dol_object->total_ttc) { // Par précaution
                        continue;
                    }

                    if ($remainToPay > 0) {
                        if (!isset($clients[(int) $r['fk_soc']])) {
                            $clients[(int) $r['fk_soc']] = array(
                                'relances_actives'    => (int) $client->getData('relances_actives'),
                                'available_discounts' => $client->getAvailableDiscountsAmounts(),
                                'convertible_amounts' => $client->getConvertibleToDiscountAmount(),
                                'paiements_inc'       => $client->getTotalPaiementsInconnus(),
                                'relances'            => array()
                            );
                        }

                        $nb_relances = (int) $fac->getData('nb_relance');

                        $relance_idx = $nb_relances + 1;
                        $date_lim = $fac->getData('date_lim_reglement');
                        if (!$date_lim) {
                            $date_lim = $fac->getData('datef');
                        }

                        $date_relance = (string) $fac->getData('date_relance');

                        $date_next_relance = '';

                        if ($nb_relances > 0) {
                            if ($date_relance) {
                                $dt_relance = new DateTime($date_relance);
                            } else {
                                $dt_relance = new DateTime($date_lim);
                            }
                            $dt_relance->add(new DateInterval('P' . $relance_delay . 'D'));
                            $date_next_relance = $dt_relance->format('Y-m-d');
                        } else {
                            $dt_relance = new DateTime($date_lim);
                            $dt_relance->add(new DateInterval('P1D'));
                            $date_next_relance = $dt_relance->format('Y-m-d');
                        }

                        if ($to_process_only && $date_next_relance > $now) {
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
                            'date_lim'          => $date_lim,
                            'retard'            => floor(($now_tms - strtotime($date_lim)) / 86400),
                            'date_last_relance' => $date_relance,
                            'date_next_relance' => $date_next_relance,
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
            $sql .= ' WHERE f.fk_soc = ' . $this->id . ' AND f.paye = 0 AND AND f.fk_statut = 1';
            $sql .= ' AND f.type IN (' . Facture::TYPE_STANDARD . ',' . Facture::TYPE_DEPOSIT . ',' . Facture::TYPE_CREDIT_NOTE . ')';
            $sql .= ' AND (SELECT COUNT(r.rowid) FROM ' . MAIN_DB_PREFIX . 'societe_remise_except r WHERE r.fk_facture_source = f.rowid) = 0';

            $rows = $this->db->executeS($sql);

            foreach ($rows as $r) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['id_fac']);
                if (BimpObject::objectLoaded($fac)) {
                    $remainToPay = (float) $fac->getRemainToPay();
                    if ($remainToPay < 0) {
                        $amount += abs($remainToPay);
                    }
                }
            }
        }

        return $amount;
    }

    // Affichagges: 

    public function displayOutstanding()
    {
        $html = '';
        if ($this->isLoaded()) {
            $values = $this->dol_object->getOutstandingBills();

            if (isset($values['opened'])) {
                $html .= BimpTools::displayMoneyValue($values['opened']);
            } else {
                $html .= '<span class="warning">Aucun encours trouvé</span>';
            }

            $html .= '<div class="buttonsContainer align-right">';
            $url = DOL_URL_ROOT . '/compta/recap-compta.php?socid=' . $this->id;
            $html .= '<a href="' . $url . '" target="_blank" class="btn btn-default">';
            $html .= 'Aperçu client' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
            $html .= '</div>';
        }

        return $html;
    }

    // Rendus HTML:

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
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_contacts_list_tab .nav_tab_ajax_result\')', array('contacts'), array('button' => ''))
        );

        // Comptes bancaires: 
        $tabs[] = array(
            'id'            => 'client_bank_accounts_list_tab',
            'title'         => BimpRender::renderIcon('fas_university', 'iconLeft') . 'Comptes bancaires',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_bank_accounts_list_tab .nav_tab_ajax_result\')', array('bank_accounts'), array('button' => ''))
        );

        // Utilisateurs: 
        $tabs[] = array(
            'id'            => 'client_users_list_tab',
            'title'         => BimpRender::renderIcon('fas_users', 'iconLeft') . 'Utilisateurs',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_users_list_tab .nav_tab_ajax_result\')', array('client_users'), array('button' => ''))
        );

        // Equipements: 
        $tabs[] = array(
            'id'            => 'client_equipments_list_tab',
            'title'         => BimpRender::renderIcon('fas_desktop', 'iconLeft') . 'Equipements',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_equipments_list_tab .nav_tab_ajax_result\')', array('equipments'), array('button' => ''))
        );

        // Evénements: 
        $tabs[] = array(
            'id'            => 'client_events_list_tab',
            'title'         => BimpRender::renderIcon('fas_calendar-check', 'iconLeft') . 'Evénements',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_events_list_tab .nav_tab_ajax_result\')', array('events'), array('button' => ''))
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
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_propales_list_tab .nav_tab_ajax_result\')', array('propales'), array('button' => ''))
        );

        // Commandes client
        $tabs[] = array(
            'id'            => 'client_commandes_list_tab',
            'title'         => BimpRender::renderIcon('fas_dolly', 'iconLeft') . 'Commandes',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_commandes_list_tab .nav_tab_ajax_result\')', array('commandes'), array('button' => ''))
        );

        // Livraisons
        $tabs[] = array(
            'id'            => 'client_shipments_list_tab',
            'title'         => BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Livraisons',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_shipments_list_tab .nav_tab_ajax_result\')', array('shipments'), array('button' => ''))
        );

        // Factures
        $tabs[] = array(
            'id'            => 'client_factures_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Factures',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_factures_list_tab .nav_tab_ajax_result\')', array('factures'), array('button' => ''))
        );

        // Contrats
        $tabs[] = array(
            'id'            => 'client_contrats_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-signature', 'iconLeft') . 'Contrats',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_contrats_list_tab .nav_tab_ajax_result\')', array('contrats'), array('button' => ''))
        );

        // Paiements non identifiés: 
        $tabs[] = array(
            'id'            => 'client_paiements_inc_list_tab',
            'title'         => BimpRender::renderIcon('fas_question-circle', 'iconLeft') . 'Paiements non identifiés',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_paiements_inc_list_tab .nav_tab_ajax_result\')', array('paiements_inc'), array('button' => ''))
        );

        // Relances paiements: 
        $tabs[] = array(
            'id'            => 'client_relances_list_tab',
            'title'         => BimpRender::renderIcon('fas_comment-dollar', 'iconLeft') . 'Relances paiements',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderNavtabView', '$(\'#client_relances_list_tab .nav_tab_ajax_result\')', array('client_relances_list_tab'), array('button' => ''))
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
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_tickets_list_tab .nav_tab_ajax_result\')', array('tickets'), array('button' => ''))
        );

        // SAV
        $tabs[] = array(
            'id'            => 'client_sav_list_tab',
            'title'         => BimpRender::renderIcon('fas_wrench', 'iconLeft') . 'SAV',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_sav_list_tab .nav_tab_ajax_result\')', array('sav'), array('button' => ''))
        );

        // Prêts matériel

        $tabs[] = array(
            'id'            => 'client_prets_list_tab',
            'title'         => BimpRender::renderIcon('fas_mobile-alt', 'iconLeft') . 'Prêts matériel',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_prets_list_tab .nav_tab_ajax_result\')', array('prets'), array('button' => ''))
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
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_di_list_tab .nav_tab_ajax_result\')', array('di'), array('button' => ''))
        );

        // FI
        $tabs[] = array(
            'id'            => 'client_fi_list_tab',
            'title'         => BimpRender::renderIcon('fas_ambulance', 'iconLeft') . 'Fiches inter',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderClientList', '$(\'#client_fi_list_tab .nav_tab_ajax_result\')', array('fi'), array('button' => ''))
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

                $html .= $this->renderClientList('relances');
                break;

            case '':
                break;
        }

        return $html;
    }

    public function renderClientList($list_type)
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
                            $html .= BimpRender::renderAlerts('Les relances de paiements sont désactivées pour ce client', 'warning');
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
                            $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);

                            if (BimpObject::objectLoaded($fac)) {
                                $relance = ($relances_allowed && ($now >= $fac_data['date_next_relance']) && (int) $relance_idx <= 4 && !(int) $fac_data['id_cur_relance']);

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
                        if ($relance_idx > 4) {
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

                            $id_contact = $fac->getIdContactForRelance($relance_idx);

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

                            if ($relance_idx <= 2) {
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

    // Actions:

    public function actionRelancePaiements($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

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

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
}

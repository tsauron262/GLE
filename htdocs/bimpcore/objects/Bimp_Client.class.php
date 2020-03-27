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
                if ((int) $user->id == 1) {
                    return 1;
                }

                return 0; // todo: Droit à définir
        }

        return (int) parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('relancePaiements'))) {
            return 1; // L'objet peut ne pas être loadé.
        }

        return parent::isActionAllowed($action, $errors);
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

    public function getFacturesToRelanceByClients($to_process_only = false, $allowed_factures = null)
    {
        $clients = array();

        BimpTools::loadDolClass('compta/facture', 'facture');
        $now = date('Y-m-d');
        $now_tms = strtotime($now);

        $where = 'type IN (' . Facture::TYPE_STANDARD . ',' . Facture::TYPE_DEPOSIT . ',' . Facture::TYPE_CREDIT_NOTE . ') AND paye = 0 AND fk_statut = 1 AND date_lim_reglement < \'' . $now . '\'';
        $where .= ' AND relance_active = 1';
        $where .= ' AND datec > \'2019-06-30\'';
        if ($this->isLoaded()) {
            $where .= ' AND fk_soc = ' . (int) $this->id;
        }

//        $where .= ' AND fk_soc = 335884'; // Pour tests
//        $where .= ' AND nb_relance = 0'; // Pour tests
//        $rows = $this->db->getRows('facture', $where, 30, 'array', array('rowid', 'fk_soc'), 'rowid', 'asc'); // Pour tests
        $rows = $this->db->getRows('facture', $where, null, 'array', array('rowid', 'fk_soc'), 'rowid', 'asc');

        if (!is_null($rows)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/RelancePaiementPDF.php';
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

                    if (!isset($clients[(int) $r['fk_soc']])) {
                        $clients[(int) $r['fk_soc']] = array(
                            'available_discounts' => $client->getAvailableDiscountsAmounts(),
                            'convertible_amounts' => 0,
                            'paiements_inc'       => $client->getTotalPaiementsInconnus(),
                            'relances'            => array()
                        );
                    }

                    $remainToPay = round($remainToPay, 2);
                    if ($remainToPay < 0) {
                        // On Vérifie l'existance d'une remise: 
                        BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                        $discount = new DiscountAbsolute($this->db->db);
                        $discount->fetch(0, $fac->id);
                        $remainToPay += $discount->amount_ttc;

                        $remainToPay = round($remainToPay, 2);
                        if ($remainToPay < 0) {
                            $clients[(int) $r['fk_soc']]['convertible_amounts'] += abs($remainToPay);
                        }
                    }

                    if ($remainToPay > 0) {
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

                        $clients[(int) $r['fk_soc']]['relances'][$relance_idx][(int) $r['rowid']] = array(
                            'total_ttc'         => (float) $fac->getData('total_ttc'),
                            'remain_to_pay'     => $remainToPay,
                            'nb_relances'       => $nb_relances,
                            'date_lim'          => $date_lim,
                            'retard'            => floor(($now_tms - strtotime($date_lim)) / 86400),
                            'date_last_relance' => $date_relance,
                            'date_next_relance' => $date_next_relance
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
                                $relances_allowed = false;
                            }

                            if ($client_data['convertible_amounts'] > 0) {
                                $url = $this->getUrl() . '&navtab=commercial&navtab-commercial_view=client_factures_list_tab';
                                $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($client_data['convertible_amounts']) . '</strong> <a href="' . $url . '" target="_blank">d\'avoirs ou de trop perçus' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a> pouvant être convertis en remise', 'warning');
                                $relances_allowed = false;
                            }

                            if ($client_data['paiements_inc']) {
                                $url = $this->getUrl() . '&navtab=commercial&navtab-commercial_view=client_paiements_inc_list_tab';
                                $html .= BimpRender::renderAlerts('Ce client dispose de <strong>' . BimpTools::displayMoneyValue($client_data['paiements_inc']) . '</strong> de <a href="' . $url . '" target="_blank">paiements non identifiés' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>', 'warning');
                                $relances_allowed = false;
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
            $clients = $this->getFacturesToRelanceByClients(false);
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
                                $relance = ($relances_allowed && ($now >= $fac_data['date_next_relance']) && (int) $relance_idx <= 4);

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

    public function relancePaiements($clients = array(), $mode = 'auto', &$warnings = array(), &$pdf_file = '', &$success = '', $date_prevue = null, $send_mail = true)
    {
        // modes: auto / man
        // mode auto (cron) => relances mails uniquement (1ère et 2ème relances) 

        global $db;

        $errors = array();

        if (is_null($date_prevue)) {
            $date_prevue = date('Y-m-d');
        }

        if (empty($clients) && $mode = 'auto') {
            // On récup la liste complète des factures à relancer. 
            $clients = $this->getFacturesToRelanceByClients(true);
        }

        if (empty($clients)) {
            $errors[] = 'Aucune paiement de facture à relancer';
        } else {
            global $user;
            $now = date('Y-m-d');
            $pdf_files = array();

            $relance = BimpObject::createBimpObject('bimpcommercial', 'BimpRelanceClients', array(
                        'id_user'     => ($mode === 'man' && BimpObject::objectLoaded($user) ? (int) $user->id : 0),
                        'date'        => date('Y-m-d H:i:s'),
                        'mode'        => $mode,
                        'date_prevue' => $date_prevue
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($relance)) {
                foreach ($clients as $id_client => $client_data) {
                    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $id_client);
                    $dir = $client->getFilesDir();

                    if (!BimpObject::objectLoaded($client)) {
                        $warnings[] = 'Le client d\'ID ' . $id_client . ' n\'existe plus';
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
                            $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                            if (!BimpObject::objectLoaded($fac)) {
                                $warnings[] = 'La facture d\'ID ' . $id_fac . ' n\'existe plus';
                                continue;
                            }

                            if ($now < $fac_data['date_next_relance']) {
                                $warnings[] = 'La facture "' . $fac->getRef() . '" n\'a pas été traitée car sa date de prochaine relance est ultérieure à la date du jour';
                                continue;
                            }

                            $id_contact = 0;

                            if (in_array($relance_idx, array(1, 2))) {
                                $contacts = $fac->dol_object->getIdContact('external', 'BILLING2');
                                if (isset($contacts[0]) && (int) $contacts[0]) {
                                    $id_contact = (int) $contacts[0];
                                    $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                                    if (!BimpObject::objectLoaded($contact)) {
                                        $id_contact = 0;
                                    }
                                    if (!$contact->getData('email')) {
                                        $id_contact = 0;
                                    }
                                }
                            }

                            if (!$id_contact) {
                                $contacts = $fac->dol_object->getIdContact('external', 'BILLING');
                                if (isset($contacts[0]) && (int) $contacts[0]) {
                                    $id_contact = (int) $contacts[0];
                                    $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                                    if (!BimpObject::objectLoaded($contact)) {
                                        $id_contact = 0;
                                    }
                                    if (in_array($relance_idx, array(1, 2)) && !$contact->getData('email')) {
                                        $id_contact = 0;
                                    }
                                }
                            }

                            if (!isset($facturesByContacts[$id_contact])) {
                                $facturesByContacts[$id_contact] = array();
                            }
                            $facturesByContacts[$id_contact][] = $fac;
                        }

                        // Génération PDF et envoi mail pour chaque contact: 
                        $i = 0;
                        foreach ($facturesByContacts as $id_contact => $contact_factures) {
                            $i++;
                            $contact = null;

                            if ((int) $id_contact) {
                                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                            }

                            // Création des données du PDF: 
                            $pdf_data = array(
                                'relance_idx'  => (int) $relance_idx,
                                'factures'     => array(),
                                'rows'         => array(),
                                'solde'        => 0,
                                'total_debit'  => 0,
                                'total_credit' => 0
                            );

                            foreach ($contact_factures as $fac) {
                                $pdf_data['factures'][] = $fac;
                                $this->hydrateRelancePdfDataFactureRows($fac, $pdf_data);
                            }

                            $file_name = 'Relance_' . $relance_idx . '_' . date('Y-m-d_H-i') . '_' . $i . '.pdf';
                            $pdf = new RelancePaiementPDF($db);
                            $pdf->client = $client;
                            $pdf->contact = $contact;
                            $pdf->data = $pdf_data;

                            if (!count($pdf->errors)) {
                                // Génération du PDF: 
                                $pdf->render($dir . '/' . $file_name, false);
                            }

                            if (count($pdf->errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($pdf->errors, 'Echec de la génération du PDF pour la relance n°' . $relance_idx . ' du client ' . $client->getRef() . ' - ' . $client->getName());
                            } else {
                                // Création de la ligne de relance: 
                                $relanceLine = BimpObject::getInstance('bimpcommercial', 'BimpRelanceClientsLine');
                                $relanceLine->validateArray(array(
                                    'id_relance'  => $relance->id,
                                    'id_client'   => $client->id,
                                    'id_contact'  => $id_contact,
                                    'relance_idx' => $relance_idx,
                                    'pdf_file'    => $file_name,
                                    'status'      => BimpRelanceClientsLine::RELANCE_ATTENTE_COURRIER
                                ));

                                if ($relance_idx > 2) {
                                    $pdf_files[] = $dir . '/' . $file_name;
                                } else {
                                    // Envoi du mail: 
                                    $email = '';
                                    if (BimpObject::objectLoaded($contact)) {
                                        $email = $contact->getData('email');
                                    } else {
                                        $email = $client->getData('email');
                                    }

                                    if (!$email) {
                                        $msg = 'Email absent pour l\'envoi de la relance n°' . $relance_idx . ' au client "' . $client->getRef() . ' - ' . $client->getName() . '"';
                                        if (BimpObject::objectLoaded($contact)) {
                                            $msg .= ' (contact: ' . $contact->getName() . ')';
                                        }
                                        $msg .= '. Le PDF a été ajouté à la liste des PDF à envoyer par courrier';
                                        $warnings[] = $msg;
                                        $pdf_files[] = $dir . '/' . $file_name;
                                    } else {
                                        $email = str_replace(' ', '', $email);
                                        $email = str_replace(';', ',', $email);

                                        $relanceLine->set('email', $email);

                                        $mail_body = $pdf->content_html;
                                        $mail_body = str_replace('font-size: 7px;', 'font-size: 9px;', $mail_body);
                                        $mail_body = str_replace('font-size: 8px;', 'font-size: 10px;', $mail_body);
                                        $mail_body = str_replace('font-size: 9px;', 'font-size: 11px;', $mail_body);
                                        $mail_body = str_replace('font-size: 10px;', 'font-size: 12;', $mail_body);

                                        $subject = ($relance_idx == 1 ? 'LETTRE DE RAPPEL' : 'DEUXIEME RAPPEL');

                                        $from = '';

                                        $commercial = $client->getCommercial(false);

                                        if (BimpObject::objectLoaded($commercial)) {
                                            $from = $commercial->getData('email');
                                        }

                                        if (!$from) {
                                            // todo: utiliser config en base. 
                                            $from = 'recouvrement@bimp.fr';
                                        }

                                        if (!mailSyn2($subject, $email, $from, $mail_body, array($dir . '/' . $file_name), array('application/pdf'), array($file_name))) {
                                            // Mail KO
                                            $msg = 'Echec de l\'envoi par email de la relance n°' . $relance_idx . ' au client "' . $client->getRef() . ' - ' . $client->getName() . '" (';
                                            if (BimpObject::objectLoaded($contact)) {
                                                $msg .= 'contact: ' . $contact->getName() . ', ';
                                            }
                                            $msg .= 'email: ' . $email . ')';
                                            $msg .= '. Le PDF a été ajouté à la liste des PDF à envoyer par courrier';
                                            $warnings[] = $msg;
                                            $pdf_files[] = $dir . '/' . $file_name;
                                        } else {
                                            // Mail OK
                                            $relanceLine->set('status', BimpRelanceClientsLine::RELANCE_OK_MAIL);
                                            $relanceLine->set('date_send', date('Y-m-d H:i:s'));
                                            if ($mode === 'man' && BimpObject::objectLoaded($user)) {
                                                $relanceLine->set('id_user_send', (int) $user->id);
                                            }
                                        }
                                    }
                                }

                                // Maj des données de la facture
                                $relanceLine_factures = array();
                                foreach ($contact_factures as $fac) {
                                    $fac->updateField('nb_relance', (int) $relance_idx, null, true);
                                    $fac->updateField('date_relance', $now, null, true);
                                    $relanceLine_factures[] = (int) $fac->id;
                                }

                                // Création de la ligne de relance: 
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
                }

                if (!empty($pdf_files)) {
                    $pdf_dir = 'relances/' . date('Y') . '/' . date('m');
                    dol_mkdir(DOL_DATA_ROOT . '/bimpcore/' . $pdf_dir, DOL_DATA_ROOT);
                    $file_name = 'relances_' . date('Y-m-d_H-i') . '.pdf';
                    $pdf = new BimpConcatPdf();
                    $pdf->concatFiles(DOL_DATA_ROOT . '/bimpcore/' . $pdf_dir . '/' . $file_name, $pdf_files, 'F');

                    $pdf_file = $pdf_dir . '/' . $file_name;

                    $relance->updateField('pdf_file', str_replace('relances/', '', $pdf_dir) . '/' . $file_name);
                }
            }
        }

        return $errors;
    }

    public function hydrateRelancePdfDataFactureRows($facture, &$pdf_data)
    {
        $commandes_list = BimpTools::getDolObjectLinkedObjectsList($facture->dol_object, $this->db, array('commande'));
        $comm_refs = '';

        foreach ($commandes_list as $item) {
            $comm_ref = $this->db->getValue('commande', 'ref', 'rowid = ' . (int) $item['id_object']);
            if ($comm_ref) {
                $comm_refs = ($comm_refs ? '<br/>' : '') . $comm_ref;
            }
        }

        $fac_total = (float) $facture->getData('total_ttc');

        // Total facture: 
        $pdf_data['rows'][] = array(
            'date'     => $facture->displayData('datef', 'default', false),
            'fac'      => $facture->getRef(),
            'comm'     => $comm_refs,
            'lib'      => 'Total ' . $facture->getLabel(),
            'debit'    => ($fac_total > 0 ? BimpTools::displayMoneyValue($fac_total, '') . ' €' : ''),
            'credit'   => ($fac_total < 0 ? BimpTools::displayMoneyValue(abs($fac_total), '') . ' €' : ''),
            'echeance' => $facture->displayData('date_lim_reglement', 'default', false)
        );

        if ($fac_total > 0) {
            $pdf_data['total_debit'] += $fac_total;
        } else {
            $pdf_data['total_credit'] += abs($fac_total);
        }

        // Ajout des avoirs utilisés:
        $rows = $this->db->getRows('societe_remise_except', '`fk_facture` = ' . (int) $facture->id, null, 'array');
        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $amount = (float) $r['amount_ttc'];

                if (!$amount) {
                    continue;
                }

                $avoir_fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture_source']);
                $label = '';
                if ($avoir_fac->isLoaded()) {
                    switch ((int) $avoir_fac->getData('type')) {
                        case Facture::TYPE_DEPOSIT:
                            $label = 'Acompte ' . $avoir_fac->getRef();
                            break;

                        case facture::TYPE_CREDIT_NOTE:
                            $label = 'Avoir ' . $avoir_fac->getRef();
                            break;

                        default:
                            $label = 'Trop perçu sur facture ' . $avoir_fac->getRef();
                            break;
                    }
                } else {
                    $label = ((string) $r['description'] ? $r['description'] : 'Remise');
                }

                $dt = new DateTime($r['datec']);
                $pdf_data['rows'][] = array(
                    'date'     => $dt->format('d / m / Y'),
                    'fac'      => $facture->getRef(),
                    'comm'     => '',
                    'lib'      => $label,
                    'debit'    => ($amount < 0 ? BimpTools::displayMoneyValue(abs($amount), '') . ' €' : ''),
                    'credit'   => ($amount > 0 ? BimpTools::displayMoneyValue($amount, '') . ' €' : ''),
                    'echeance' => ''
                );

                if ($amount < 0) {
                    $pdf_data['total_debit'] += abs($amount);
                } else {
                    $pdf_data['total_credit'] += $amount;
                }
            }
        }

        // Ajout des paiements de la facture: 
        $rows = $this->db->getRows('paiement_facture', '`fk_facture` = ' . (int) $facture->id, null, 'array');
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $amount = (float) $r['amount'];

                if (!$amount) {
                    continue;
                }

                $paiement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', (int) $r['fk_paiement']);
                if (BimpObject::objectLoaded($paiement)) {
                    $pdf_data['rows'][] = array(
                        'date'     => $paiement->displayData('datep'),
                        'fac'      => $facture->getRef(),
                        'comm'     => '',
                        'lib'      => 'Paiement ' . $paiement->displayType(),
                        'debit'    => ($amount < 0 ? BimpTools::displayMoneyValue(abs($amount), '') . ' €' : ''),
                        'credit'   => ($amount > 0 ? BimpTools::displayMoneyValue($amount, '') . ' €' : ''),
                        'echeance' => ''
                    );
                    if ($amount < 0) {
                        $pdf_data['total_debit'] += abs($amount);
                    } else {
                        $pdf_data['total_credit'] += $amount;
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

        $factures = BimpTools::getArrayValueFromPath($data, 'factures', array());

        if (!is_array($factures) || empty($factures)) {
            $errors[] = 'Aucune facture à relancer spécifiée';
        } else {
            $clients = $this->getFacturesToRelanceByClients(true, $factures);
            $pdf_file = '';
            $errors = $this->relancePaiements($clients, 'man', $warnings, $pdf_file);

            if ($pdf_file) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($pdf_file);
                $success_callback = 'window.open(\'' . $url . '\')';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
}

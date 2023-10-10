<?php

class InterfaceClientController extends BimpPublicController
{

    public static $user_client_required = true;
    public $tabActive = 'home';
    public $sideTabs = array();

    public function init()
    {
        parent::init();
        $this->cssFiles[] = '/bimpinterfaceclient/views/css/bootstrap_public.css';
        $this->cssFiles[] = '/bimpinterfaceclient/views/css/public.css';

        $this->jsFiles[] = '/bimpinterfaceclient/views/js/bootstrap_public.js';
        $this->jsFiles[] = '/bimpinterfaceclient/views/js/public.js';
//        $this->jsFiles[] = '/bimpinterfaceclient/views/js/ajax.js';


        global $userClient;
        if (BimpObject::objectLoaded($userClient)) {
            $base_url = BimpObject::getPublicBaseUrl();
            $this->sideTabs = array(
                'home'       => array('url' => $base_url . 'tab=home', 'label' => 'Accueil', 'icon' => 'pe_home'),
                'infos'      => array('url' => $base_url . 'tab=infos', 'label' => 'Mes informations', 'icon' => 'pe_id'),
                'contrats'   => array('url' => $base_url . 'tab=contrats', 'label' => 'Mes contrats', 'icon' => 'pe_news-paper'),
                'signatures' => array('url' => $base_url . 'tab=signatures', 'label' => 'Mes signatures', 'icon' => 'pe_pen'),
                'factures'   => array('url' => $base_url . 'tab=factures', 'label' => 'Mes factures', 'icon' => 'pe_file')
            );

            if ((int) BimpCore::getConf('use_tickets', null, 'bimpsupport')) {
                $this->sideTabs['tickets'] = array('url' => $base_url . 'tab=tickets', 'label' => 'Support téléphonique', 'icon' => 'pe_headphones');
            }

            if ((int) BimpCore::getConf('use_sav', null, 'bimpsupport')) {
                $this->sideTabs['sav'] = array('url' => $base_url . 'tab=sav', 'label' => 'SAV - Réparations', 'icon' => 'pe_tools');
            }

            if ($userClient->isAdmin()) {
                $this->sideTabs['users'] = array('url' => $base_url . 'tab=users', 'label' => 'Utilisateurs', 'icon' => 'pe_users');
            } else {
                unset($this->sideTabs['factures']);
            }
        }

        $this->tabActive = BimpTools::getValue('tab', 'home');
    }

    public function renderHtml()
    {
        $html = '';

        $html .= '<div class="wrapper bic_container">';
        $html .= $this->renderSideBar();

        $html .= '<div class="bic_main-panel">';

        $html .= $this->renderTop();

        $html .= '<div class="bic_content">';
        $html .= $this->renderContent();
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderSideBar()
    {
        $html = '';
        if (count($this->sideTabs)) {
            $html .= '<div class="bic_sidebar" data-color="bimp">';
            $html .= '<div class="sidebar-wrapper">';

            $html .= '<ul class="nav">';

            foreach ($this->sideTabs as $id => $tab) {
                $icon = BimpTools::getArrayValueFromPath($tab, 'icon', '');
                $url = BimpTools::getArrayValueFromPath($tab, 'url', '#');
                $label = BimpTools::getArrayValueFromPath($tab, 'label', '');

                $html .= '<li' . ($id == $this->tabActive ? ' class="active"' : '') . '>';
                $html .= '<a href="' . $url . '"><p>' . ($icon ? BimpRender::renderIcon($icon, 'iconLeft') : '') . $label . '</p></a>';
                $html .= '</li>';
            }

            $html .= '</ul>';

            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }

    public function renderTop()
    {
        $html = '';

        global $userClient, $langs;

        $html .= '<nav class="navbar navbar-default navbar-fixed" style="background: rgba(255, 255, 255, 0.96)">';
        $html .= '<div class="container-fluid">';

        $html .= '<div class="navbar-header">';
        $html .= '<a class="navbar-brand" href="#">';

        $file_url = '';
        global $public_entity;
        if ($public_entity) {
            $logo = BimpCore::getConf('public_logo', null, 'bimpinterfaceclient');
            if (strpos($logo, '{') === 0) {
                $logos = json_decode($logo, 1);
                $logo = null;
                if (isset($logos[$public_entity])) {
                    $logo = $logos[$public_entity];
                }
            }
        }

        if (!$file_url) {
            $file_url = BimpTools::getMyCompanyLogoUrl($logo);
            
            if (!$file_url) {
                $file_url = BimpTools::getMyCompanyLogoUrl();
            }
        }

        $html .= '<img src="' . $file_url . '" style="width: auto; height: 50px"/>';
        $html .= '</a>';
        $html .= '</div>';

        $html .= '<div class="collapse navbar-collapse">';
        $html .= '<ul class="nav navbar-nav navbar-right">';
        if (BimpObject::objectLoaded($userClient)) {
            $html .= '<li><a href="">' . $langs->trans('loggedAs') . ' : <span class="user_login" style="color: #' . BimpCore::getParam('interface_client/primary', '000000') . ';">' . $userClient->getData('email') . '</span></a></li>';
            $html .= '<li><a href="' . BimpObject::getPublicBaseUrl() . 'bic_logout=1">' . BimpRender::renderIcon('pe_power', 'iconLeft') . '<span class="icon">' . $langs->trans('deconnexion') . '</span></a></li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    public function renderContent()
    {
        $html = '';

        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            $client = $userClient->getParentInstance();

            if ($client->can('view')) {
                $html .= '<div style="margin-bottom: 30px; border-bottom: 1px solid #999999; vertical-align: middle">';
                $html .= '<h5 style="margin: 0"><span style="font-size: 22px; vertical-align: middle;">';
                $html .= BimpRender::renderIcon('pe_user', 'iconLeft') . '</span>';
                $html .= $client->getRef() . ' - ' . $client->getName() . '</h5>';
                $html .= '</div>';
            }
        }

        if ($this->tabActive) {
            $method = 'renderTab' . ucfirst($this->tabActive);
            if (method_exists($this, $method)) {
                $html .= $this->{$method}();
            }
        }

        return $html;
    }

    // Contenus des tabs: 

    public function renderTabHome()
    {
        global $userClient;

        $html = '<h2>Bienvenue dans votre espace client ' . $this->public_entity_name . '</h2><br/>';

        if (!BimpObject::objectLoaded($userClient)) {
            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
        } else {

            if ((int) BimpCore::getConf('sav_public_reservations', 0, 'bimpsupport')) {
                $html .= '<div class="buttonsContainer align-right" style="margin: 15px 0">';
                $html .= '<span class="btn btn-default" onclick="window.location = \'' . BimpObject::getPublicBaseUrl() . 'fc=savForm\'">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouvelle demande de réparation';
                $html .= '</span>';
                $html .= '</div>';
            }

            // Signatures en attentes:
            $signataires = BimpCache::getBimpObjectObjects('bimpcore', 'BimpSignataire', array(
                        'id_client'  => (int) $userClient->getData('id_client'),
                        'status'     => 0,
                        'type'       => 1,
                        'allow_dist' => 1
                            ), 'id', 'desc');

            if (!empty($signataires)) {
                $html .= '<div class="row" style="margin-bottom: 30px">';
                $html .= '<div class="col-lg-12">';
                $html .= '<h3>' . BimpRender::renderIcon('pe_pen', 'iconLeft') . 'Mes signatures en attente</h3>';

                $headers = array(
                    'obj'             => 'Elément lié',
                    'doc_type'        => 'Type de document',
                    'doc_ref'         => 'Référence document',
                    'public_document' => 'Fichier PDF',
                    'public_sign'     => array('label' => '', 'col_style' => 'text-align: right')
                );

                $rows = array();

                foreach ($signataires as $signataire) {
                    if ($signataire->can('view')) {
                        $signature = $signataire->getParentInstance();

                        if (BimpObject::objectLoaded($signature)) {
                            $rows[] = array(
                                'obj'             => $signature->displayObj(),
                                'doc_type'        => $signature->displayDocType(),
                                'doc_ref'         => $signature->displayDocRef(),
                                'public_document' => $signataire->displayPublicDocument(),
                                'public_sign'     => $signataire->dispayPublicSign(),
                            );
                        }
                    }
                }

                $html .= BimpRender::renderBimpListTable($rows, $headers, array());

                $html .= '</div>';
                $html .= '</div>';
            }

            // Contrats en cours: 
            $html .= '<div class="row" style="margin-bottom: 30px">';
            $html .= '<div class="col-lg-12">';
            $html .= '<h3>' . BimpRender::renderIcon('pe_news-paper', 'iconLeft') . 'Mes contrats en cours</h3>';

            $contrats_ouverts = $userClient->getContratsVisibles(true);

            $html .= '<div>';
            if (!empty($contrats_ouverts)) {
                foreach ($contrats_ouverts as $id_contrat => $c) {
                    $html .= $c->display_card();
                }
            } else {
                $html .= BimpRender::renderAlerts('Vous n\'avez aucun contrat en cours', 'info');
            }
            $html .= '</div>';

            $html .= '</div>';
            $html .= '</div>';

            // Tickets support: 
            if ((int) BimpCore::getConf('use_tickets', null, 'bimpsupport')) {
                $html .= '<div class="row" style="margin-bottom: 30px">';
                $html .= '<div class="col-lg-12">';
                $html .= '<h3>' . BimpRender::renderIcon('pe_headphones', 'iconLeft') . 'Mes tickets de support téléphonique en cours</h3>';

                $filters = array(
                    'id_client' => (int) $userClient->getData('id_client'),
                    'status'    => array(
                        'operator' => '<',
                        'value'    => 999
                    )
                );

                if (!$userClient->isAdmin()) {
                    $filters['id_user_client'] = $userClient->id;
                }

                $tickets = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_Ticket', $filters, 'date_create', 'desc');

                if (empty($tickets)) {
                    $html .= BimpRender::renderAlerts('Vous n\'avez aucun ticket de support téléphonique en cours', 'info');
                }

                $contact = null;

                if (BimpObject::objectLoaded($userClient) && (int) $userClient->getData('id_contact')) {
                    $contact = $userClient->getChildObject('contact');
                }

                $ticket = BimpObject::getInstance('bimpsupport', 'BS_Ticket');

                if ($ticket->can('create')) {
                    $onclick = $ticket->getJsLoadModalForm('public_create', 'Nouveau ticket de support téléphonique', array(
                        'fields' => array(
                            'id_client'        => (int) $userClient->getData('id_client'),
                            'id_user_client'   => (BimpObject::objectLoaded($userClient) ? (int) $userClient->id : 0),
                            'contact_in_soc'   => (BimpObject::objectLoaded($contact) ? $contact->getName() : ''),
                            'adresse_envois'   => (BimpObject::objectLoaded($contact) ? BimpTools::replaceBr($contact->displayFullAddress()) : ''),
                            'email_bon_retour' => (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : '')
                        )
                    ));

                    $html .= '<div class="buttonsContainer align-right" style="margin: 15px 0">';
                    $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouveau ticket';
                    $html .= '</span>';
                    $html .= '</div>';
                }

                if (!empty($tickets)) {
                    $headers = array(
                        'ref'    => 'Ref.',
                        'datec'  => 'Créé le',
                        'user'   => 'Demandeur',
                        'status' => 'Statut',
                        'sujet'  => 'Description',
                        'tools'  => array('label' => '', 'col_style' => 'text-align: right')
                    );

                    $rows = array();

                    foreach ($tickets as $ticket) {
                        $url = $ticket->getPublicUrl();
                        $button = '';
                        if ($url) {
                            $button .= '<a class="btn btn-default" href="' . $url . '">';
                            $button .= BimpRender::renderIcon('fas_eye', 'iconLeft') . 'Détail';
                            $button .= '</a>';
                        }
                        $rows[] = array(
                            'ref'    => $ticket->getData('ticket_number'),
                            'datec'  => $ticket->displayData('date_create', 'default', false),
                            'user'   => $ticket->displayData('id_user_client', 'nom_url', false),
                            'status' => $ticket->displayData('status', 'default', false),
                            'sujet'  => $ticket->displayData('sujet', 'default', false),
                            'tools'  => $button
                        );
                    }

                    $html .= BimpRender::renderBimpListTable($rows, $headers, array());
                }

                $html .= '</div>';
                $html .= '</div>';
            }

            // SAV: 
            if ((int) BimpCore::getConf('use_sav', null, 'bimpsupport')) {
                $savs = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_SAV', array(
                            'id_client' => (int) $userClient->getData('id_client'),
                            'status'    => array(
                                'and' => array(
                                    array(
                                        'operator' => '>',
                                        'value'    => -2
                                    ),
                                    array(
                                        'operator' => '<',
                                        'value'    => 999
                                    )
                                )
                            )
                                ), 'date_create', 'desc');

                $html .= '<div class="row" style="margin-bottom: 30px">';
                $html .= '<div class="col-lg-12">';
                $html .= '<h3>' . BimpRender::renderIcon('pe_tools', 'iconLeft') . 'Mes réparations en cours</h3>';

                if (empty($savs)) {
                    $html .= BimpRender::renderAlerts('Vous n\'avez aucune réparation en cours', 'info');
                }

                if ((int) BimpCore::getConf('sav_public_reservations', 0, 'bimpsupport')) {
                    $html .= '<div class="buttonsContainer align-right" style="margin: 15px 0">';
                    $html .= '<span class="btn btn-default" onclick="window.location = \'' . BimpObject::getPublicBaseUrl() . 'fc=savForm\'">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouvelle demande de réparation';
                    $html .= '</span>';
                    $html .= '</div>';
                }

                if (!empty($savs)) {
                    $headers = array(
                        'ref'    => 'Ref.',
                        'datec'  => 'Créé le',
                        'status' => 'Statut',
                        'centre' => 'Centre de prise en charge',
                        'user'   => 'Technicien',
                        'eq'     => 'N° de série',
                        'tools'  => array('label' => '', 'col_style' => 'text-align: right')
                    );

                    $rows = array();

                    foreach ($savs as $sav) {
                        $button = '';
                        if ($sav->can('edit') && $sav->getData('resgsx') && $sav->getData('status') == -1) {
                            $url = BimpObject::getPublicBaseUrl() . 'fc=savForm&cancel_rdv=1&sav=' . $sav->id . '&r=' . $sav->getRef() . '&res=' . $sav->getData('resgsx');
                            $button .= '<a class="btn btn-default" href="' . $url . '">';
                            $button .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Annuler';
                            $button .= '</a>';
                        }

                        if ($sav->can('view')) {
                            $url = $sav->getPublicUrl();
                            if ($url) {
                                $button .= '<a class="btn btn-default" href="' . $url . '">';
                                $button .= BimpRender::renderIcon('fas_eye', 'iconLeft') . 'Détail';
                                $button .= '</a>';
                            }
                        }

                        $rows[] = array(
                            'ref'    => $sav->getData('ref'),
                            'datec'  => $sav->displayData('date_create', 'default', false),
                            'status' => $sav->displayData('status', 'default', false),
                            'centre' => $sav->displayData('code_centre', 'default', false),
                            'user'   => $sav->displayData('id_user_tech', 'nom_url', false),
                            'eq'     => $sav->displayData('id_equipment', 'nom_url', false),
                            'tools'  => $button
                        );
                    }

                    $html .= BimpRender::renderBimpListTable($rows, $headers, array());
                }

                $html .= '</div>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderTabInfos()
    {
        $html = '';

        global $userClient;

        $html .= '<h3>' . BimpRender::renderIcon('pe_file', 'iconLeft') . 'Mes Informations</h3>';

        if (!BimpObject::objectLoaded($userClient)) {
            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
        } else {
            if ($userClient->isAdmin()) {
                $client = $userClient->getParentInstance();
                if (BimpObject::objectLoaded($client) && $client->can('view')) {
                    $view = new BC_View($client, 'public_client');
                    $html .= $view->renderHtml();
                }
            }

            $view = new BC_View($userClient, 'public');
            $html .= $view->renderHtml();
        }

        return $html;
    }

    public function renderTabContrats()
    {
        $html = '';

        global $userClient;

        $content = BimpTools::getValue('content', 'list');

        switch ($content) {
            case 'list':
                $html .= '<h3>' . BimpRender::renderIcon('pe_news-paper', 'iconLeft') . 'Mes contrats</h3>';
                $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
//
                if (!BimpObject::objectLoaded($userClient) || !$contrat->can('view')) {
                    $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
                } else {
                    $client = $userClient->getParentInstance();

                    if (!BimpObject::objectLoaded($client)) {
                        $html .= BimpRender::renderAlerts('Ce compte client n\'existe plus');
                    } else {
                        $contrats_ouverts = $userClient->getContratsVisibles(true);

                        $html .= '<div class="row" style="margin-bottom: 30px">';
                        $html .= '<p>Le client <b><i>' . $client->getName() . '</i></b> est actuellement ';
                        if (!empty($contrats_ouverts)) {
                            $html .= '<span class="success">sous contrat ' . BimpRender::renderIcon('fas_check', 'iconRight') . '</span></p>';

                            $html .= '<h3 style="color:#EF7D00" >Mes contrats en cours' . BimpRender::renderIcon('arrow-down', 'iconRight') . '</h3>';

                            $html .= '<div>';
                            foreach ($contrats_ouverts as $id_contrat => $c) {
                                $html .= $c->display_card();
                            }
                            $html .= '</div>';
                        } else {
                            $html .= '<span class="danger">hors contrat ' . BimpRender::renderIcon('fas_times', 'iconRight') . '</span></p>';
                        }
                        $html .= '</div>';

                        $html .= '<div class="row">';

                        // Listes contrats inactifs: 

                        $list = new BC_ListTable($contrat, 'public', 1, null, 'Mes contrats inactifs', 'fas_times-circle');
                        $list->addFieldFilterValue('fk_soc', $client->id);
                        $list->addFieldFilterValue('statut', array(
                            'and' => array(
                                array('operator' => '>', 'value' => 0),
                                array('operator' => '!=', 'value' => 11)
                            )
                        ));

                        if (!$userClient->isAdmin()) {
                            $list->addFieldFilterValue('rowid', array(
                                'in' => $userClient->getAssociatedContratsList()
                            ));
                        }

                        $html .= $list->renderHtml();
                        $html .= '</div>';
                    }
                }
                break;

            case 'card':
                $list_url = $this->sideTabs['contrats']['url'];
                if ($list_url) {
                    $html .= '<div style="margin-bottom: 10px;">';
                    $html .= '<span class="btn btn-default" onclick="window.location = \'' . $list_url . '\';">';
                    $html .= BimpRender::renderIcon('fas_arrow-left', 'iconLeft') . 'Retour à la liste de vos contrats';
                    $html .= '</span>';
                    $html .= '</div>';
                }

                $id_contrat = (int) BimpTools::getValue('id_contrat', 0);
                if (!$id_contrat) {
                    $html .= BimpRender::renderAlerts('Référence du contrat absente');
                } else {
                    $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $id_contrat);

                    if (!BimpObject::objectLoaded($contrat)) {
                        $html .= BimpRender::renderAlerts('Ce Contrat n\'existe plus');
                    } else {
                        if (!$contrat->can('view') || !$contrat->canClientViewDetail()) {
                            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce contrat', 'warning');
                        } else {
                            $view = new BC_View($contrat, 'public_client');
                            $html .= $view->renderHtml();
                        }
                    }
                }

                break;
        }

        return $html;
    }

    public function renderTabSignatures()
    {
        $html = '';

        global $userClient;

        $html .= '<h3>' . BimpRender::renderIcon('pe_pen', 'iconLeft') . 'Mes signatures</h3>';

        $signataire = BimpObject::getInstance('bimpcore', 'BimpSignataire');

        if (!BimpObject::objectLoaded($userClient) || !$signataire->can('view')) {
            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
        } else {
            $list = new BC_ListTable($signataire, 'public_client');
            $list->addFieldFilterValue('id_client', (int) $userClient->getData('id_client'));

            $html .= $list->renderHtml();
        }

        return $html;
    }

    public function renderTabFactures()
    {
        $html = '';

        global $userClient;

        $html .= '<h3>' . BimpRender::renderIcon('pe_file', 'iconLeft') . 'Mes factures client</h3>';

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

        if (!BimpObject::objectLoaded($userClient) || !$facture->can('view')) {
            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
        } else {
            $list = new BC_ListTable($facture, 'public_client');
            $list->addFieldFilterValue('fk_soc', (int) $userClient->getData('id_client'));
            $list->addFieldFilterValue('fk_statut', array(
                'in' => array(1, 2)
            ));
            $html .= $list->renderHtml();
        }

        return $html;
    }

    public function renderTabTickets()
    {
        if (!(int) BimpCore::getConf('use_tickets', null, 'bimpsupport')) {
            return BimpRender::renderAlerts('Le support téléphonique n\'est plus disponible sur cet espace');
        }
        $html = '';

        global $userClient;
        $content = BimpTools::getValue('content', 'list');

        switch ($content) {
            case 'list':
                $html .= '<h3>' . BimpRender::renderIcon('pe_headphones', 'iconLeft') . 'Tickets de support téléphonique</h3>';
                $ticket = BimpObject::getInstance('bimpsupport', 'BS_Ticket');

                if (!BimpObject::objectLoaded($userClient) || !$ticket->can('view')) {
                    $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
                } else {
                    if ($ticket->can('create')) {
                        $html .= '<div class="buttonsContainer align-right">';
                        $contact = null;
                        if ((int) $userClient->getData('id_contact')) {
                            $contact = $userClient->getChildObject('contact');
                        }

                        $onclick = $ticket->getJsLoadModalForm('public_create', 'Nouveau ticket de support téléphonique', array(
                            'fields' => array(
                                'id_client'        => (int) $userClient->getData('id_client'),
                                'id_user_client'   => (BimpObject::objectLoaded($userClient) ? (int) $userClient->id : 0),
                                'contact_in_soc'   => (BimpObject::objectLoaded($contact) ? $contact->getName() : ''),
                                'adresse_envois'   => (BimpObject::objectLoaded($contact) ? BimpTools::replaceBr($contact->displayFullAddress()) : ''),
                                'email_bon_retour' => (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : '')
                            )
                        ));
                        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouveau ticket';
                        $html .= '</span>';
                        $html .= '</div>';
                    }
                    $list = new BC_ListTable($ticket, 'public_client');
                    $list->addFieldFilterValue('id_client', (int) $userClient->getData('id_client'));
                    if (!$userClient->isAdmin()) {
                        $list->addFieldFilterValue('id_user_client', (int) $userClient->id);
                    }
                    $html .= $list->renderHtml();
                }
                break;

            case 'card':
                $list_url = $this->sideTabs['tickets']['url'];
                if ($list_url) {
                    $html .= '<div style="margin-bottom: 10px;">';
                    $html .= '<span class="btn btn-default" onclick="window.location = \'' . $list_url . '\';">';
                    $html .= BimpRender::renderIcon('fas_arrow-left', 'iconLeft') . 'Retour à la liste de vos tickets';
                    $html .= '</span>';
                    $html .= '</div>';
                }

                $id_ticket = (int) BimpTools::getValue('id_ticket', 0);
                if (!$id_ticket) {
                    $html .= BimpRender::renderAlerts('Référence du ticket absente');
                } else {
                    $ticket = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket', $id_ticket);

                    if (!BimpObject::objectLoaded($ticket)) {
                        $html .= BimpRender::renderAlerts('Ce ticket n\'existe plus');
                    } else {
                        if (!$ticket->can('view')) {
                            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce Ticket', 'warning');
                        } else {
                            $view = new BC_View($ticket, 'public_client');
                            $html .= $view->renderHtml();

                            $inter = BimpObject::getInstance('bimpsupport', 'BS_Inter');
                            $list = new BC_ListTable($inter, 'public_client', 1, $id_ticket);
                            $list->addFieldFilterValue('is_public', 1);

                            $html .= '<div style="margin-top: 30px">';
                            $html .= $list->renderHtml();
                            $html .= '</div>';

                            $note = BimpObject::getInstance('bimpsupport', 'BS_Note');
                            $list = new BC_ListTable($note, 'public', 1, $id_ticket);
                            $list->addFieldFilterValue('visibility', 1);

                            $html .= '<div style="margin-top: 30px">';
                            $html .= $list->renderHtml();
                            $html .= '</div>';

                            $file = BimpObject::getInstance('bimpcore', 'BimpFile');
                            $list = new BC_ListTable($file, 'public', 1, $id_ticket, 'Fichiers', 'fas_folder-open');
                            $list->addFieldFilterValue('parent_module', 'bimpsupport');
                            $list->addFieldFilterValue('parent_object_name', 'BS_Ticket');

                            $html .= '<div style="margin-top: 30px">';
                            $html .= $list->renderHtml();
                            $html .= '</div>';
                        }
                    }
                }
                break;

            case 'new':
                break;
        }

        return $html;
    }

    public function renderTabSav()
    {
        $html = '';

        if (!(int) BimpCore::getConf('use_sav', null, 'bimpsupport')) {
            return BimpRender::renderAlerts('La gestion de vos dossiers SAV n\'est plus disponible sur cet espace');
        }

        global $userClient;
        $content = BimpTools::getValue('content', 'list');

        switch ($content) {
            case 'list':
                $html .= '<h3>' . BimpRender::renderIcon('pe_tools', 'iconLeft') . 'Suivis réparations SAV</h3>';
                $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');

                if (!BimpObject::objectLoaded($userClient) || !$sav->can('view')) {
                    $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
                } else {
                    if ((int) BimpCore::getConf('sav_public_reservations', 0, 'bimpsupport')) {
                        $html .= '<div class="buttonsContainer align-right" style="margin: 15px 0">';
                        $html .= '<span class="btn btn-default" onclick="window.location = \'' . BimpObject::getPublicBaseUrl() . 'fc=savForm\'">';
                        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouvelle demande de réparation';
                        $html .= '</span>';
                        $html .= '</div>';
                    }

                    $list = new BC_ListTable($sav, 'public_client', 1, null, 'Liste des réparations');
                    $list->addFieldFilterValue('id_client', (int) $userClient->getData('id_client'));
                    $html .= $list->renderHtml();
                }
                break;

            case 'card':
                $list_url = $this->sideTabs['sav']['url'];
                if ($list_url) {
                    $html .= '<div style="margin-bottom: 10px;">';
                    $html .= '<span class="btn btn-default" onclick="window.location = \'' . $list_url . '\';">';
                    $html .= BimpRender::renderIcon('fas_arrow-left', 'iconLeft') . 'Retour à la liste de vos réparations';
                    $html .= '</span>';
                    $html .= '</div>';
                }

                $id_sav = (int) BimpTools::getValue('id_sav', 0);
                if (!$id_sav) {
                    $html .= BimpRender::renderAlerts('Référence du SAV absente');
                } else {
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);

                    if (!BimpObject::objectLoaded($sav)) {
                        $html .= BimpRender::renderAlerts('Cette réparation n\'existe plus');
                    } else {
                        if (!$sav->can('view')) {
                            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission de voir cette réparation', 'warning');
                        } else {
                            $view = new BC_View($sav, 'public_client');
                            $html .= $view->renderHtml();

                            $sql = BimpTools::getSqlSelect(array('id'));
                            $sql .= BimpTools::getSqlFrom('bimpcore_signature');
                            $sql .= ' WHERE ';
                            $sql .= '(obj_module = \'bimpsupport\' AND obj_name = \'BS_SAV\' AND id_obj = ' . $sav->id . ')';
                            $sql .= ' OR ';
                            $sql .= '(obj_module = \'bimpcommercial\' AND obj_name = \'Bimp_Propal\' AND id_obj = ' . $sav->getData('id_propal') . ')';

                            $rows = BimpCache::getBdb()->executeS($sql, 'array');

                            if (is_array($rows) && !empty($rows)) {
                                $ids = array();

                                foreach ($rows as $r) {
                                    $ids[] = (int) $r['id'];
                                }

                                $signataire = BimpObject::getInstance('bimpcore', 'BimpSignataire');
                                $list = new BC_ListTable($signataire, 'public_client', 1, null, 'Signatures', 'fas_signature');
                                $list->addFieldFilterValue('id_signature', $ids);
                                $html .= '<div style="margin-top: 30px">';
                                $html .= $list->renderHtml();
                                $html .= '</div>';
                            }

                            $note = BimpObject::getInstance('bimpcore', 'BimpNote');
                            $list = new BC_ListTable($note, 'public');
                            $list->addFieldFilterValue('obj_type', 'bimp_object');
                            $list->addFieldFilterValue('obj_module', 'bimpsupport');
                            $list->addFieldFilterValue('obj_name', 'BS_SAV');
                            $list->addFieldFilterValue('id_obj', (int) $sav->id);
                            $list->addFieldFilterValue('visibility', 4);

                            $html .= '<div style="margin-top: 30px">';
                            $html .= $list->renderHtml();
                            $html .= '</div>';
                        }
                    }
                }

                break;
        }

        return $html;
    }

    public function renderTabUsers()
    {
        $html = '';

        global $userClient;

        if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
            $instance = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient');

            $list = new BC_ListTable($instance, 'public_client', 1, null, 'Liste des comptes utilisateurs');
            $list->addFieldFilterValue('id_client', $userClient->getData('id_client'));
            $html .= $list->renderHtml();
        } else {
            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
        }

        return $html;
    }
}

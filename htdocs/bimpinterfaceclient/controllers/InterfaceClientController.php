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
            $this->sideTabs = array(
                'home'     => array('url' => DOL_URL_ROOT . '/bimpinterfaceclient/client.php?tab=home', 'label' => 'Accueil', 'icon' => 'pe_home'),
                'infos'    => array('url' => DOL_URL_ROOT . '/bimpinterfaceclient/client.php?tab=infos', 'label' => 'Mes informations', 'icon' => 'pe_id'),
                'contrats' => array('url' => DOL_URL_ROOT . '/bimpinterfaceclient/client.php?tab=contrats', 'label' => 'Mes contrats', 'icon' => 'pe_news-paper'),
                'factures' => array('url' => DOL_URL_ROOT . '/bimpinterfaceclient/client.php?tab=factures', 'label' => 'Mes factures', 'icon' => 'pe_file'),
                'tickets'  => array('url' => DOL_URL_ROOT . '/bimpinterfaceclient/client.php?tab=tickets', 'label' => 'Tickets support', 'icon' => 'pe_headphones'),
                'sav'      => array('url' => DOL_URL_ROOT . '/bimpinterfaceclient/client.php?tab=sav', 'label' => 'Suivis SAV', 'icon' => 'pe_tools')
            );

            if ($userClient->isAdmin()) {
                $this->sideTabs['users'] = array('url' => DOL_URL_ROOT . '/bimpinterfaceclient/client.php?tab=users', 'label' => 'Utilisateurs', 'icon' => 'pe_users');
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

        global $mysoc, $userClient, $langs;

        $html .= '<nav class="navbar navbar-default navbar-fixed" style="background: rgba(255, 255, 255, 0.96)">';
        $html .= '<div class="container-fluid">';

        $html .= '<div class="navbar-header">';
        $html .= '<a class="navbar-brand" href="#">';
        $html .= '<img src="' . DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . $mysoc->logo . '" style="width: 43%"/>';
        $html .= '</a>';
        $html .= '</div>';

        $html .= '<div class="collapse navbar-collapse">';
        $html .= '<ul class="nav navbar-nav navbar-right">';
        if (BimpObject::objectLoaded($userClient)) {
            $html .= '<li><a href="">' . $langs->trans('loggedAs') . ' : <span class="user_login" style="color: #' . BimpCore::getParam('interface_client/primary', '000000') . ';">' . $userClient->getData('email') . '</span></a></li>';
            $html .= '<li><a href="client.php?bic_logout=1">' . BimpRender::renderIcon('pe_power', 'iconLeft') . '<span class="icon">' . $langs->trans('deconnexion') . '</span></a></li>';
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

        $html = '<h2>Bienvenue dans votre espace client BIMP</h2><br/>';

        if (!BimpObject::objectLoaded($userClient)) {
            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
        } else {
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
            $html .= '<div class="row" style="margin-bottom: 30px">';
            $html .= '<div class="col-lg-12">';
            $html .= '<h3>' . BimpRender::renderIcon('pe_headphones', 'iconLeft') . 'Mes tickets supports en cours</h3>';

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
                $html .= BimpRender::renderAlerts('Vous n\'avez aucun ticket support en cours', 'info');
            }

            $contact = null;

            if (BimpObject::objectLoaded($userClient) && (int) $userClient->getData('id_contact')) {
                $contact = $userClient->getChildObject('contact');
            }

            $ticket = BimpObject::getInstance('bimpsupport', 'BS_Ticket');
            $onclick = $ticket->getJsLoadModalForm('public_create', 'Nouveau ticket support', array(
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
            $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouveau ticket support';
            $html .= '</span>';
            $html .= '</div>';

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

            // SAV: 
            $savs = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_SAV', array(
                        'id_client' => (int) $userClient->getData('id_client'),
                        'status'    => array(
                            'operator' => '<',
                            'value'    => 999
                        )
                            ), 'date_create', 'desc');

            $html .= '<div class="row" style="margin-bottom: 30px">';
            $html .= '<div class="col-lg-12">';
            $html .= '<h3>' . BimpRender::renderIcon('pe_tools', 'iconLeft') . 'Mes SAV en cours</h3>';

            if (empty($savs)) {
                $html .= BimpRender::renderAlerts('Vous n\'avez aucun sav en cours', 'info');
            }

//            $html .= '<div class="buttonsContainer align-right" style="margin: 15px 0">';
//            $html .= '<span class="btn btn-default" onclick="window.location = \''.DOL_URL_ROOT.'/bimpinterfaceclient/client.php?fc=savForm\'">';
//            $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouveau SAV';
//            $html .= '</span>';
//            $html .= '</div>';

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
                    $url = $sav->getPublicUrl();
                    $button = '';
                    if ($url) {
                        $button .= '<a class="btn btn-default" href="' . $url . '">';
                        $button .= BimpRender::renderIcon('fas_eye', 'iconLeft') . 'Détail';
                        $button .= '</a>';
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
        $html = '';

        global $userClient;
        $content = BimpTools::getValue('content', 'list');

        switch ($content) {
            case 'list':
                $html .= '<h3>' . BimpRender::renderIcon('pe_headphones', 'iconLeft') . 'Tickets support</h3>';
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

                        $onclick = $ticket->getJsLoadModalForm('public_create', 'Nouveau ticket support', array(
                            'fields' => array(
                                'id_client'        => (int) $userClient->getData('id_client'),
                                'id_user_client'   => (BimpObject::objectLoaded($userClient) ? (int) $userClient->id : 0),
                                'contact_in_soc'   => (BimpObject::objectLoaded($contact) ? $contact->getName() : ''),
                                'adresse_envois'   => (BimpObject::objectLoaded($contact) ? BimpTools::replaceBr($contact->displayFullAddress()) : ''),
                                'email_bon_retour' => (BimpObject::objectLoaded($userClient) ? $userClient->getData('email') : '')
                            )
                        ));
                        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouveau ticket support';
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
                    $html .= BimpRender::renderIcon('fas_arrow-left', 'iconLeft') . 'Retour à la liste de vos tickets support';
                    $html .= '</span>';
                    $html .= '</div>';
                }

                $id_ticket = (int) BimpTools::getValue('id_ticket', 0);
                if (!$id_ticket) {
                    $html .= BimpRender::renderAlerts('Référence du ticket absente');
                } else {
                    $ticket = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket', $id_ticket);

                    if (!BimpObject::objectLoaded($ticket)) {
                        $html .= BimpRender::renderAlerts('Ce ticket support n\'existe plus');
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

        global $userClient;
        $content = BimpTools::getValue('content', 'list');

        switch ($content) {
            case 'list':
                $html .= '<h3>' . BimpRender::renderIcon('pe_tools', 'iconLeft') . 'Suivis SAV</h3>';
                $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');

                if (!BimpObject::objectLoaded($userClient) || !$sav->can('view')) {
                    $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu');
                } else {
                    $list = new BC_ListTable($sav, 'public_client');
                    $list->addFieldFilterValue('id_client', (int) $userClient->getData('id_client'));
                    $html .= $list->renderHtml();
                }
                break;

            case 'card':
                $list_url = $this->sideTabs['sav']['url'];
                if ($list_url) {
                    $html .= '<div style="margin-bottom: 10px;">';
                    $html .= '<span class="btn btn-default" onclick="window.location = \'' . $list_url . '\';">';
                    $html .= BimpRender::renderIcon('fas_arrow-left', 'iconLeft') . 'Retour à la liste de vos SAV';
                    $html .= '</span>';
                    $html .= '</div>';
                }

                $id_sav = (int) BimpTools::getValue('id_sav', 0);
                if (!$id_sav) {
                    $html .= BimpRender::renderAlerts('Référence du SAV absente');
                } else {
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);

                    if (!BimpObject::objectLoaded($sav)) {
                        $html .= BimpRender::renderAlerts('Ce SAV n\'existe plus');
                    } else {
                        if (!$sav->can('view')) {
                            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce SAV', 'warning');
                        } else {
                            $view = new BC_View($sav, 'public_client');
                            $html .= $view->renderHtml();
                        }
                    }
                }

                break;

            case 'new':
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

<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Societe.class.php';

class Bimp_Fournisseur extends Bimp_Societe
{

    public $soc_type = "fournisseur";

    public function getRefProperty()
    {
        return 'code_fournisseur';
    }

    public function getRef($with_generic = true)
    {
        return $this->getData('code_fournisseur');
    }

    public function getSearchListFilters()
    {
        return array(
            'fournisseur' => 1
        );
    }

    // Affichages: 

    public function displayOutstanding()
    {
        return '';
    }

    // Rendus HTML: 

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '';

        if ($this->isLoaded() && (int) $this->getData('client')) {
            $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=client&id=' . $this->id;
            $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\');">';
            $html .= BimpRender::renderIcon('fas_user-circle', 'iconLeft') . 'Fiche client' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</span>';
        }

        return $html;
    }

    public function renderCardView()
    {
        $tabs = array();

        // Infos: 
        $view = new BC_View($this, 'default');
        $view->params['panel'] = 0;
        $tabs[] = array(
            'id'      => 'fourn_card_infos_tab',
            'title'   => BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos',
            'content' => $view->renderHtml()
        );

        // Contacts / adresses: 
        $tabs[] = array(
            'id'            => 'fourn_contacts_list_tab',
            'title'         => BimpRender::renderIcon('fas_address-book', 'iconLeft') . 'Contacts / adresses',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#fourn_contacts_list_tab .nav_tab_ajax_result\')', array('contacts'), array('button' => ''))
        );

        // Comptes bancaires: 
        $tabs[] = array(
            'id'            => 'fourn_bank_accounts_list_tab',
            'title'         => BimpRender::renderIcon('fas_university', 'iconLeft') . 'Comptes bancaires',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#fourn_bank_accounts_list_tab .nav_tab_ajax_result\')', array('bank_accounts'), array('button' => ''))
        );

        // Evénements: 
        $tabs[] = array(
            'id'            => 'fourn_events_list_tab',
            'title'         => BimpRender::renderIcon('fas_calendar-check', 'iconLeft') . 'Evénements',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#fourn_events_list_tab .nav_tab_ajax_result\')', array('events'), array('button' => ''))
        );

        $html = BimpRender::renderNavTabs($tabs, 'card_view');
        $html .= $this->renderNotesList();

        return $html;
    }

    public function renderCommercialView()
    {
        $tabs = array();

        // Commandes fourn: 
        $tabs[] = array(
            'id'            => 'fourn_commandes_list_tab',
            'title'         => BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . 'Commandes',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#fourn_commandes_list_tab .nav_tab_ajax_result\')', array('commandes_fourn'), array('button' => ''))
        );

        // Réceptions: 
        if ($this->isModuleActif('bimplogistique'))
            $tabs[] = array(
                'id'            => 'fourn_receptions_list_tab',
                'title'         => BimpRender::renderIcon('fas_arrow-circle-down', 'iconLeft') . 'Réceptions',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#fourn_receptions_list_tab .nav_tab_ajax_result\')', array('receptions'), array('button' => ''))
            );

        // Factures fourn: 
        $tabs[] = array(
            'id'            => 'fourn_factures_list_tab',
            'title'         => BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Factures',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#fourn_factures_list_tab .nav_tab_ajax_result\')', array('factures_fourn'), array('button' => ''))
        );

        // product fourn: 
        $tabs[] = array(
            'id'            => 'fourn_factures_list_tab',
            'title'         => BimpRender::renderIcon('fas_box', 'iconLeft') . 'Produits',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#fourn_factures_list_tab .nav_tab_ajax_result\')', array('products_fourn'), array('button' => ''))
        );
        
        if ((int) BimpCore::getConf('use_logistique_periodicity', null, 'bimpcommercial')) {
            $tabs[] = array(
            'id'            => 'fourn_periodicity_view_tab',
            'title'         => BimpRender::renderIcon('fas_calendar-alt', 'iconLeft') . 'Achats périodiques',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderPeriodicityView', '$(\'#fourn_periodicity_view_tab .nav_tab_ajax_result\')', array(), array('button' => ''))
        );
        }

        return BimpRender::renderNavTabs($tabs, 'commercial_view');
    }

    public function renderNavtabView($nav_tab)
    {
        $html = '';

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        switch ($nav_tab) {
            
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
        $fourn_label = $this->getRef() . ' - ' . $this->getName();

        switch ($list_type) {
            case 'contacts':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_Contact'), 'soc', 1, $this->id, 'Contacts du fournisseur "' . $fourn_label . '"');
                break;

            case 'bank_accounts':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_SocBankAccount'), 'fourn', 1, null, 'Comptes bancaires du fournisseur "' . $fourn_label . '"', 'fas_university');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'events':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_ActionComm'), 'fourn', 1, null, 'Evénements', 'fas_calendar-check');
                $list->addFieldFilterValue('fk_soc', $this->id);
                break;

            case 'commandes_fourn':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn'), 'fourn', 1, null, 'Commandes du fournisseur "' . $fourn_label . '"', 'fas_cart-arrow-down');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'receptions':
                $list = new BC_ListTable(BimpObject::getInstance('bimplogistique', 'BL_CommandeFournReception'), 'fourn', 1, null, 'Réceptions du fournisseur "' . $fourn_label . '"', 'fas_arrow-circle-down');
                $list->addFieldFilterValue('commande_fourn.fk_soc', $this->id);
                $list->addJoin('commande_fournisseur', 'a.id_commande_fourn = commande_fourn.rowid', 'commande_fourn');
                break;

            case 'factures_fourn':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn'), 'fourn', 1, null, 'Factures du fournisseur "' . $fourn_label . '"', 'fas_file-invoice-dollar');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
                break;

            case 'products_fourn':
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice'), 'fourn', 1, null, 'Produits du fournisseur "' . $fourn_label . '"', 'fas_file-invoice-dollar');
                $list->addFieldFilterValue('fk_soc', (int) $this->id);
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
    
    public function renderPeriodicityView()
    {
        $errors = array();
        if (!$this->isLoaded($errors)) {
            return BimpRender::renderAlerts($errors);
        }
        
        $commandeController = BimpController::getInstance('bimpcommercial', 'commandes');
        return $commandeController->renderPeriodsTab(array(
            'id_fourn' => $this->id
        ));
    }

    // Traitements: 

    public function onSave(&$errors = [], &$warnings = [])
    {
        if ($this->isLoaded() && !$this->getData('code_fournisseur')) {
            $this->dol_object->get_codefournisseur($this->dol_object, 1);
            $code_fourn = $this->dol_object->code_fournisseur;

            if ($code_fourn) {
                $this->db->update('societe', array(
                    'code_fournisseur' => $code_fourn
                        ), 'rowid = ' . $this->id);
            }
        }

        parent::onSave($errors, $warnings);
    }
}

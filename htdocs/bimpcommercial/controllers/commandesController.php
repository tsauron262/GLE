<?php

class commandesController extends BimpController
{

    var $socid = "";
    var $soc = null;

    public function displayHead()
    {
        global $db, $langs, $user;

        $this->getSocid();
        if (BimpTools::getValue("socid") > 0) {
            $this->socid = BimpTools::getValue("socid");
            require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
            $head = societe_prepare_head($this->soc->dol_object);
            dol_fiche_head($head, 'bimpcomm', '');

            $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

            dol_banner_tab($this->soc->dol_object, 'id', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&fc=commandes');
        }
    }

    public function getSocid()
    {
        if ($this->socid < 1) {
            if (BimpTools::getValue("socid") > 0) {
                $this->socid = BimpTools::getValue("socid");
                $this->soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);
            }
        }
    }

    public function renderCommandesTab()
    {
//        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeLine');
//        Bimp_CommandeLine::checkAllQties();

        $this->getSocid();
        $list = 'default';
        $titre = 'Commandes';
        $propal = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
        if ($this->socid) {

            if (!BimpObject::objectLoaded($this->soc)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            $list = 'client';
            $titre .= ' du client ' . $this->soc->getData('code_client') . ' - ' . $this->soc->getData('nom');
        }

        if (isset($_REQUEST['fk_statut'])) {
            $filtres = explode(",", $_REQUEST['fk_statut']);
            foreach ($filtres as $val) {
                if (isset($propal::$status_list[$val]))
                    $labels[] = $propal::$status_list[$val]['label'];
            }

            if (!empty($labels)) {
                $titre .= ' au statut ' . implode(' ou ', $labels);
            }
        }


        $list = new BC_ListTable($propal, $list, 1, null, $titre);

        if ($this->socid) {
            $list->addFieldFilterValue('fk_soc', (int) $this->soc->id);
            $list->params['add_form_values']['fields']['fk_soc'] = (int) $this->soc->id;
        }
        if (isset($_REQUEST['fk_statut'])) {
            $filtres = explode(",", $_REQUEST['fk_statut']);
            $list->addFieldFilterValue('fk_statut', $filtres);
        }

        return $list->renderHtml();
    }

    public function renderShipmentsTab()
    {
        $this->getSocid();

        $titre = 'Liste des expéditions';
        if ($this->socid) {
            if (!BimpObject::objectLoaded($this->soc)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            //$list = 'client';
            $titre .= ' du client ' . $this->soc->getData('code_client') . ' - ' . $this->soc->getData('nom');
        }
        $shipment = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
        $list = new BC_ListTable($shipment, 'default', 1, null, $titre, 'fas_shipping-fast');

        if ($this->socid) {
            $list->addJoin('commande', 'a.id_commande_client = parent.rowid', 'parent');
            $list->addFieldFilterValue('parent.fk_soc', (int) $this->socid);
            //$list->params['add_form_values']['fields']['fk_soc'] = (int) $this->soc->id;
        }
        return $list->renderHtml();
    }

    public function renderProdsTabs()
    {
        $this->getSocid();
//        $id_entrepot = (int) BimpTools::getValue('id_entrepot', 0);

        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');

        $titre = 'Liste des produits en commande';
        if ($this->socid) {
            if (!BimpObject::objectLoaded($this->soc)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            $titre .= ' du client ' . $this->soc->getData('code_client') . ' - ' . $this->soc->getData('nom');
        }

        $bc_list = new BC_ListTable($line, 'general', 1, null, $titre, 'fas_bars');
        $bc_list->addJoin('commande', 'a.id_obj = parent.rowid', 'parent');
        $bc_list->addFieldFilterValue('parent.fk_statut', array(
            'operator' => '>',
            'value'    => 0
        ));


        if ($this->socid) {
            $bc_list->addFieldFilterValue('parent.fk_soc', (int) $this->socid);
        }

        return $bc_list->renderHtml();
    }

    public function renderPeriodsTab()
    {
        $tabs = array();

        $this->getSocid();

        $line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');

        // Overview: 
        $content = '<div class="periods_overview_content">';
        $content .= $line_instance->renderPeriodsToProcessOverview();
        $content .= '</div>';

        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-4">';
        $title = BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'A traiter aujourd\'hui';
        $onclick = $line_instance->getJsLoadCustomContent('renderPeriodsToProcessOverview', '$(this).findParentByClass(\'panel\').find(\'.periods_overview_content\')');
        $footer = '<div style="text-align: right">';
        $footer .= '<span class="btn btn-default" onclick="' . $onclick . '">';
        $footer .= 'Actualiser' . BimpRender::renderIcon('fas_redo', 'iconRight');
        $footer .= '</span>';
        $footer .= '</div>';
        $html .= BimpRender::renderPanel($title, $content, $footer, array(
                    'type' => 'secondary'
        ));
        $html .= '</div>';
        $html .= '</div>';

        // Livraisons: 
        $tabs[] = array(
            'id'            => 'exp_periods_tab',
            'title'         => BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Livraisons périodiques',
            'ajax'          => 1,
            'ajax_callback' => $line_instance->getJsLoadCustomContent('renderPeriodsList', '$(\'#exp_periods_tab .nav_tab_ajax_result\')', array('exp', $this->socid), array('button' => ''))
        );

        // Facturations: 
        $tabs[] = array(
            'id'            => 'fac_periods_tab',
            'title'         => BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Facturations périodiques',
            'ajax'          => 1,
            'ajax_callback' => $line_instance->getJsLoadCustomContent('renderPeriodsList', '$(\'#fac_periods_tab .nav_tab_ajax_result\')', array('fac', $this->socid), array('button' => ''))
        );

        // Achats: 
        $tabs[] = array(
            'id'            => 'achat_periods_tab',
            'title'         => BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . 'Achats périodiques',
            'ajax'          => 1,
            'ajax_callback' => $line_instance->getJsLoadCustomContent('renderPeriodsList', '$(\'#achat_periods_tab .nav_tab_ajax_result\')', array('achat', $this->socid), array('button' => ''))
        );

        $html .= BimpRender::renderNavTabs($tabs);

        return $html;
    }
}

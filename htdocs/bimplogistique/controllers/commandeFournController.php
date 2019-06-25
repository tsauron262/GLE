<?php

//require_once DOL_DOCUMENT_ROOT . '/bimpreservation/controllers/reservationController.php';

class commandeFournController extends BimpController
{

//    public function displayHead()
//    {
//        global $langs;
//        $commande = $this->config->getObject('', 'commande');
//        require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
//        $head = commande_prepare_head($commande->dol_object);
//        dol_fiche_head($head, 'bimplogisitquecommande', $langs->trans("CustomerOrder"), -1, 'order');
//    }
    
    public function getPageTitle()
    {
        $title = 'Logistique ';
        $commande = $this->config->getObject('', 'commande_fourn');
        
        if (BimpObject::objectLoaded($commande)) {
            $title .= $commande->getRef();
        } else {
            $title .= 'commande fournisseur';
        }
        
        return $title;
    }
    

    public function renderContentHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de la commande fournisseur absent');
        }

        $commande = $this->config->getObject('', 'commande_fourn');
        if (!BimpObject::objectLoaded($commande)) {
            return BimpRender::renderAlerts('Aucune commande fournisseur trouvée pour l\'ID ' . BimpTools::getValue('id', ''));
        }

        if (!$commande->isLogistiqueActive()) {
            return BimpRender::renderAlerts('Cette commande fournisseur doit avoir été commandée pour accéder à cet onglet');
        }

        $_GET['id_entrepot'] = (int) $commande->getData('entrepot');

        $html = '';

        $html .= BimpRender::renderNavTabs(array(
                    array(
                        'id'      => 'receptions',
                        'title'   => 'Réceptions',
                        'content' => $this->renderReceptionsTab($commande)
                    ),
                    array(
                        'id'      => 'lines',
                        'title'   => 'Logistique produits / services',
                        'content' => $this->renderCommandesFournLinesLogisticTab($commande)
                    ),
                    array(
                        'id'      => 'invoices',
                        'title'   => 'Factures / Avoirs',
                        'content' => $this->renderFacturesTab($commande)
                    ),
        ));

        $html .= $commande->renderNotesList(true);

        return $html;
    }

    public function renderCommandesFournLinesLogisticTab(Bimp_CommandeFourn $commande)
    {
        $html = '';

        $html .= '<div class="buttonsContainer align-right">';
        $html .= $commande->renderLogistiqueButtons();
        $html .= '</div>';

        $html .= $commande->renderChildrenList('lines', 'logistique', 1);

        return $html;
    }

    public function renderReceptionsTab(Bimp_CommandeFourn $commande)
    {
        $html = '';

        $reception = BimpObject::getInstance('bimplogistique', 'BL_CommandeFournReception');
        $list = new BC_ListTable($reception, 'commandes_fourn', 1, (int) $commande->id, 'Liste des réceptions', 'fas_arrow-circle-down');
        $list->setAddFormValues(array(
            'fields' => array(
                'id_entrepot' => (int) $commande->getData('entrepot')
            )
        ));
        $html .= $list->renderHtml();

        return $html;
    }

    public function renderFacturesTab(Bimp_CommandeFourn $commande)
    {
        $html = '';

        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn');
        $list = new BC_ListTable($instance, 'default', 1, null, 'Factures fournisseur');
        $list->params['add_btn'] = 0;
        $list->addObjectAssociationFilter($commande, $commande->id, 'factures');
        $list->addObjectChangeReload('Bimp_CommandeFourn');

        $html .= $list->renderHtml();

//        $list = new BC_ListTable($instance, 'default', 1, null, 'Avoirs');
//        $list->params['add_btn'] = 0;
//        $list->addObjectAssociationFilter($commande, $commande->id, 'avoirs');
//        $list->addObjectChangeReload('Bimp_Commande');
//
//        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

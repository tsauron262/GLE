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

    public function renderContentHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de la commande fournisseur absent');
        }

        $commande = $this->config->getObject('', 'commande_fourn');
        if (!BimpObject::objectLoaded($commande)) {
            return BimpRender::renderAlerts('Aucune commande fournisseur trouvée pour l\'ID ' . BimpTools::getValue('id', ''));
        }

        if ($commande->getData('fk_statut') < 1) {
            return BimpRender::renderAlerts('Cette commande fournisseur doit etre validée pour accéder à cet onglet');
        }

        $_GET['id_entrepot'] = (int) $commande->getData('entrepot');

        $html = '';

        $html .= BimpRender::renderNavTabs(array(
                    array(
                        'id'      => 'lines',
                        'title'   => 'Logistique produits / services',
                        'content' => $this->renderCommandesFournLinesLogisticTab($commande)
                    ),
                    array(
                        'id'      => 'receptions',
                        'title'   => 'Réceptions',
                        'content' => $this->renderReceptionsTab($commande)
                    ),
                    array(
                        'id'      => 'invoices',
                        'title'   => 'Factures / Avoirs',
                        'content' => $this->renderFacturesTab($commande)
                    ),
        ));

        return $html;
    }
    
    public function renderCommandesFournLinesLogisticTab(Bimp_CommandeFourn $commande)
    {        
        $html = '';
        
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
        
    }
}


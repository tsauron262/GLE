<?php

require_once DOL_DOCUMENT_ROOT . '/bimpreservation/controllers/reservationController.php';

class commandeController extends reservationController
{

    public function displayHead()
    {
        global $langs;
        $commande = $this->config->getObject('', 'commande');
        require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
        $head = commande_prepare_head($commande);
        dol_fiche_head($head, 'bimplogisitquecommande', $langs->trans("CustomerOrder"), -1, 'order');
    }

    public function renderHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de la commande absent');
        }

        $commande = $this->config->getObject('', 'commande');
        if (is_null($commande) || !isset($commande->id) || !$commande->id) {
            return BimpRender::renderAlerts('Aucune commande trouvée pour l\'ID ' . BimpTools::getValue('id', ''));
        }

        if ($commande->statut < 1) {
            return BimpRender::renderAlerts('Cette commande doit etre validée pour accéder à cet onglet');
        }

        $html = '';

        $html .= '<div class="page_content container-fluid">';
        $html .= '<h1>Commande client "' . $commande->ref . '"</h1>';

        $html .= BimpRender::renderNavTabs(array(
                    array(
                        'id'      => 'reservations',
                        'title'   => 'Réservations',
                        'content' => $this->renderReservationsTab($commande)
                    ),
                    array(
                        'id'      => 'supplier_orders',
                        'title'   => 'Gestion des commandes fournisseurs',
                        'content' => $this->renderSupplierOrdersTab($commande)
                    )
        ));

        $html .= '</div>';

        return $html;
    }

    protected function renderReservationsTab($commande)
    {
        $html = '';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $html .= '<div class="buttonsContainer">';
        $html .= '<button id="openEquipmentsFormButton" type="button" class="btn btn-primary btn-large"';
        $html .= ' onclick="openEquipmentsForm();">';
        $html .= '<i class="fa fa-arrow-circle-down iconLeft"></i>Attribuer des équipements';
        $html .= '</button>';

        $html .= '<button id="generateBLButton" type="button" class="btn btn-default btn-large"';
        $html .= ' onclick="generateBL($(this), ' . (int) $commande->id . ');" style="float: right">';
        $html .= '<i class="fa fa-file-text iconLeft"></i>Bon de livraison';
        $html .= '</button>';

        $html .= '</div>';

        $html .= $this->renderEquipmentForm((int) $commande->id);

        $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
        $list = new BC_ListTable($reservation, 'commandes', 1, null, 'Liste des réservations');
        $list->addFieldFilterValue('id_commande_client', (int) $commande->id);
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function renderSupplierOrdersTab($commande)
    {
        $html = '';

        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $rcf = BimpObject::getInstance($this->module, 'BR_ReservationCmdFourn');
        $list = new BC_ListTable($rcf, 'default', 1, null, 'Liste des produits en commande / à commander');
        $list->addFieldFilterValue('id_commande_client', (int) $commande->id);
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

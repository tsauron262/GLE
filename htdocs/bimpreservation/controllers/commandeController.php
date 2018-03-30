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
        $html .= '</div>';

        $html .= $this->renderEquipmentForm((int) $commande->id);

        $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
        $list = new BC_ListTable($reservation, 'commandes', 1, null, 'Liste des réservations');
        $list->addFieldFilterValue('id_commande_client', (int) $commande->id);
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $html .= '<div class="buttonsContainer">';
        $html .= '<button id="createShipmentButton" type="button" class="btn btn-default btn-large bs-popover"';
        $html .= ' onclick="createShipment($(this), ' . (int) $commande->id . ');"';
        $html .= BimpRender::renderPopoverData(htmlentities('Créer une expédition pour toutes les réservation ayant le statut "prêt pour expédition"'), 'top', 'true');
        $html .= '>';
        $html .= '<i class="fa fa-sign-out iconLeft"></i>Créer une nouvelle expédition';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div id="newShipmentResult" class="ajaxResultContainer" style="display: none">';
        $html .= '</div>';

        $shipment = BimpObject::getInstance($this->module, 'BR_CommandeShipment');
        $list = new BC_ListTable($shipment, 'commandes', 1, null, 'Liste des expéditions', 'sign-out');
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
        $list = new BC_ListTable($rcf, 'default', 1, null, 'Liste des réservations en commande / à commander');
        $list->addFieldFilterValue('id_commande_client', (int) $commande->id);
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function ajaxProcessCreateShipment()
    {
        $success = 'Création de l\'expédition effectuée avec succès';

        BimpObject::loadClass($this->module, 'BR_Reservation');
        $errors = BR_Reservation::createShipment((int) BimpTools::getValue('id_commande_client', 0));

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
}

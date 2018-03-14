<?php

require_once DOL_DOCUMENT_ROOT . '/bimpreservation/controllers/reservationController.php';

class commandeController extends reservationController
{

    public function renderHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de la commande absent');
        }

        $commande = $this->config->getObject('', 'commande');
        if (is_null($commande) || !isset($commande->id) || !$commande->id) {
            return BimpRender::renderAlerts('Aucune commande trouvée pour l\'ID ' . BimpTools::getValue('id', ''));
        }

        $html = '';
        $html .= '<div class="page_content container-fluid">';
        $html .= '<div class="row">';

        $html .= '<div class="col-lg-12">';
        $html .= '<h1>Commande ' . $commande->ref . '</h1>';
        $html .= '<h2>Réservations</h2>';

        $html .= '<div class="buttonsContainer">';
        $html .= '<button id="openEquipmentsFormButton" type="button" class="btn btn-primary btn-large"';
        $html .= ' onclick="openEquipmentsForm();">';
        $html .= '<i class="fa fa-arrow-circle-down iconLeft"></i>Réceptionner des équipements';
        $html .= '</button>';
        $html .= '</div>';

        $rows = array(
            array(
                'label' => 'Numéro de série d\'un équipement à réceptionner',
                'input' => '<input type="text" class="large_input" name="serial" id="findEquipmentSerial" value=""/>'
            )
        );

        $buttons = array();

        $button = '<button id="hideEquipmentFormButton" type="button" class="btn btn-danger buttonLeft"';
        $button .= ' onclick="hideEquipmentForm();">';
        $button .= '<i class="fa fa-times iconLeft"></i>Annuler</button>';
        $buttons[] = $button;

        $button = '<button id="findEquipmentButton" type="button" class="btn btn-primary"';
        $button .= ' onclick="findEquipmentToReceive($(this), ' . $commande->id . ');">';
        $button .= '<i class="fa fa-check iconLeft"></i>Valider</button>';
        $buttons[] = $button;

        $html .= '<div id="equipmentForm" style="display: none;">';
        $html .= '<div style="display: inline-block">';
        $html .= BimpRender::renderFreeForm($rows, $buttons, 'Reception d\'équipement');
        $html .= '</div>';
        $html .= '</div>';

        $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
        $list = new BC_ListTable($reservation, 'commandes', 1, null, 'Liste des réservations');
        $list->addFieldFilterValue('id_commande_client', (int) $commande->id);
        $html .= $list->renderHtml();

        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

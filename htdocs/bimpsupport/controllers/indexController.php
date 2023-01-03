<?php

class indexController extends BimpController
{

    public function displayHead()
    {
        if (!class_exists('GSX_v2')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
        }

        echo GSX_v2::renderJsVars();
    }

    public function renderTestAssos()
    {
        $equipment = BimpObject::getInstance('bimpsupport', 'Equipment');
        $list = new BimpList($equipment, 'default');
        $list->addBulkAssociation('contrats', 609, 'Associer au contrat HL10140002');
        $html = $list->render();
        unset($list);

        $list = new BimpList($equipment, 'default');
        $list->addBulkDeassociation('contrats', 609);
        $html .= $list->render();
        unset($list);

        return $html;
    }

    public function renderPartsPendingList()
    {
        $html = '';

        $shipment = BimpObject::getInstance('bimpapple', 'AppleShipment');
        $onclick = $shipment->getJsLoadCustomContent('renderPartsPendingList', '$(\'#partsPendingListContainer\')');

        $html .= '<div style="margin: 30px 0" id="partsPendingListContainer">';
        $html .= '<span class="btn btn-primary btn-large" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_download', 'iconLeft') . 'Charger la liste des pièces en attente de retour';
        $html .= '</span>';
        $html .= '</div>';

        return $html;
    }
}

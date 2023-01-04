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

        $shipTo = '';
        $user = BimpCore::getBimpUser();
        if (BimpObject::objectLoaded($user)) {
            $shipTo = $user->getData('apple_shipto');
        }
        $shipment = BimpObject::getInstance('bimpapple', 'AppleShipment');
//        $onclick = $shipment->getJsLoadCustomContent('renderPartsPendingList', '$(\'#partsPendingListContainer\')');
        $onclick = 'loadObjectCustomContent($(this), $(\'#partsPendingListContainer\'), ';
        $onclick .= $shipment->getJsObjectData() . ', \'renderPartsPendingList\', ';
        $onclick .= '{shipto: $(\'select[name=shipto_select]\').val()});';

        $shiptos = $shipment->getShiptosArray(false);

        $html .= '<div class="shipto_select_container">';
        $html .= '<div style="display: inline-block; vertical-align: middle">';
        $html .= '<b>N° Ship-To : </b>';
        $html .= BimpInput::renderInput('select', 'shipto_select', $shipTo, array(
                    'options' => $shiptos
        ));
        $html .= '<span class="btn btn-primary" onclick="' . htmlentities($onclick) . '" style="display: inline-block; margin-left: 30px">';
        $html .= BimpRender::renderIcon('fas_download', 'iconLeft') . 'Charger la liste des pièces en attente de retour';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<div style="margin: 30px 0" id="partsPendingListContainer">';
        $html .= '</div>';

        return $html;
    }
}

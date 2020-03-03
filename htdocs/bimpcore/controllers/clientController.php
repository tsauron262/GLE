<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/controllers/societeController.php';

class clientController extends societeController
{

    public function displayHead()
    {
        
    }

//    public function renderHtml()
//    {
//        if (!BimpTools::isSubmit('id')) {
//            return BimpRender::renderAlerts('ID du client absent');
//        }
//
//        $client = $this->config->getObject('', 'client');
//        if (is_null($client) || !isset($client->id) || !$client->id) {
//            return BimpRender::renderAlerts('Aucun client trouvé pour l\'ID ' . BimpTools::getValue('id', ''));
//        }
//
//        $html = '';
//
//        $html .= '<div class="page_content container-fluid">';
//        $html .= '<h1>' . $client->getData('nom') . '</h1>';
//
//        $html .= BimpRender::renderNavTabs(array(
//                    array(
//                        'id'      => 'equipments',
//                        'title'   => 'Equipements',
//                        'content' => $this->renderEquipmentsTab($client)
//                    ),
//                    array(
//                        'id'      => 'accounts',
//                        'title'   => 'Comptes utilisateurs',
//                        'content' => $this->renderUserAccountsTab($client)
//                    )
//        ));
//
//        $html .= '</div>';
//        return $html;
//    }

    public function renderEquipmentsTab($client)
    {
        $html = '';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        BimpObject::loadClass('bimpequipment', 'BE_Place');
        $list = new BC_ListTable($equipment, 'default', 1, null, 'Equipements du client "' . $client->getData('nom') . '"');
        $list->params['add_form_name'] = null;
        $list->addFieldFilterValue('epl.type', (int) BE_Place::BE_PLACE_CLIENT);
        $list->addFieldFilterValue('epl.id_client', (int) $client->id);
        $list->addFieldFilterValue('epl.position', 1);
        $list->addJoin('be_equipment_place', 'a.id = epl.id_equipment', 'epl');
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderUserAccountsTab($client)
    {
        $html = '';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $userAccount = BimpObject::getInstance('bimpequipment', 'BE_UserAccount');
        $list = new BC_ListTable($userAccount, 'default', 1, null, 'Comptes utilisateur du client "' . $client->getData('nom') . '"');
        $list->addFieldFilterValue('id_client', (int) $client->id);
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

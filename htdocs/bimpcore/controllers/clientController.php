<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/controllers/societeController.php';

class clientController extends societeController
{

    public function init()
    {
        $id_soc = (int) BimpTools::getValue('id', 0);
        
        if (!BimpTools::getValue('ajax', 0) && $id_soc) {
            $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
            
            if (BimpObject::objectLoaded($soc)) {
                if (!(int) $soc->getData('client') && (int) $soc->getData('fournisseur')) {
                    $url = DOL_URL_ROOT.'/bimpcore/index.php?fc=fournisseur&id='.$id_soc;
                    header("location: " . $url);
                    exit;
                }
            }
        }
    }

//    public function renderEquipmentsTab($client)
//    {
//        $html = '';
//        $html .= '<div class="row">';
//        $html .= '<div class="col-lg-12">';
//
//        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
//        BimpObject::loadClass('bimpequipment', 'BE_Place');
//        $list = new BC_ListTable($equipment, 'default', 1, null, 'Equipements du client "' . $client->getData('nom') . '"');
//        $list->params['add_form_name'] = null;
//        $list->addFieldFilterValue('epl.type', (int) BE_Place::BE_PLACE_CLIENT);
//        $list->addFieldFilterValue('epl.id_client', (int) $client->id);
//        $list->addFieldFilterValue('epl.position', 1);
//        $list->addJoin('be_equipment_place', 'a.id = epl.id_equipment', 'epl');
//        $html .= $list->renderHtml();
//
//        $html .= '</div>';
//        $html .= '</div>';
//
//        return $html;
//    }
//
//    public function renderUserAccountsTab($client)
//    {
//        $html = '';
//        $html .= '<div class="row">';
//        $html .= '<div class="col-lg-12">';
//
//        $userAccount = BimpObject::getInstance('bimpequipment', 'BE_UserAccount');
//        $list = new BC_ListTable($userAccount, 'default', 1, null, 'Comptes utilisateur du client "' . $client->getData('nom') . '"');
//        $list->addFieldFilterValue('id_client', (int) $client->id);
//        $html .= $list->renderHtml();
//
//        $html .= '</div>';
//        $html .= '</div>';
//
//        return $html;
//    }
}

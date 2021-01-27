<?php


class indexController extends BimpController
{

    public function renderMyValidations() {

        global $user;
        
        $demande = BimpObject::getInstance('bimpvalidateorder', 'DemandeValidComm');
        $list = new BC_ListTable($demande, 'my_demand', 1, null, 'Mes validations en attente');
        $list->addFieldFilterValue('id_user_affected', (int) $user->id);
        $list->addFieldFilterValue('status', (int) DemandeValidComm::STATUS_PROCESSING);

        return $list->renderHtml();;
    
    }
}

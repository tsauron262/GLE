<?php


class indexController extends BimpController
{

    public function renderMyValidations($valideur = true) {

        global $user;
        
        $demande = BimpObject::getInstance('bimpvalidateorder', 'DemandeValidComm');
        if($valideur){
            $list = new BC_ListTable($demande, 'my_demand', 1, null, 'Mes validations en attente (valideur)');
            $list->addFieldFilterValue('id_user_affected', (int) $user->id);
        }
        else{
            $list = new BC_ListTable($demande, 'my_demand2', 1, null, 'Mes validations en attente (demandeur)');
            $list->addFieldFilterValue('id_user_ask', (int) $user->id);
        }
        $list->addFieldFilterValue('status', (int) DemandeValidComm::STATUS_PROCESSING);

        return $list->renderHtml();;
    
    }
}

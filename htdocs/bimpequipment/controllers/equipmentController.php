<?php

class equipmentController extends BimpController
{

    public function renderPretsList()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de l\'équipement absent');
        }

        $instance = BimpObject::getInstance('bimpsupport', 'BS_SavPret');
        $list = new BC_ListTable($instance, 'default', 1, null, 'Prêts de l\'équipement');
        $list->addAssociateAssociationFilter('equipments', BimpTools::getValue('id', 0));
        return $list->renderHtml();
    }

    public function renderSavList()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de l\'équipement absent');
        }

        $instance = BimpObject::getInstance('bimpsupport', 'BS_SAV');
        $list = new BC_ListTable($instance, 'default', 1, null, 'SAV enresgitrés pour cet équipement', 'wrench');
        $list->addFieldFilterValue('id_equipment', BimpTools::getValue('id', 0));
        return $list->renderHtml();
    }
}

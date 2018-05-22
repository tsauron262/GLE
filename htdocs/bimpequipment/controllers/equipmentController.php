<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';

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

    protected function ajaxProcessEquipmentGgxLookup()
    {
        $errors = array();

        $serial = (string) BimpTools::getValue('serial', '');

        $data = array();
        
        if (!$serial) {
            $errors[] = 'Numéro de série absent';
        } else {
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

            if (!count($errors)) {
                $data = $equipment->gsxLookup($serial, $errors);
            }
        }


        die(json_encode(array(
            'errors'     => $errors,
            'data'       => $data,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
}

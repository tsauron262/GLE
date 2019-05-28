<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';

class equipmentController extends BimpController
{

    public function renderPretsList()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de l\'équipement absent');
        }

        $list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_Pret'), 'default', 1, null, 'Prêts de l\'équipement');
        $list->addAssociateAssociationFilter('equipments', BimpTools::getValue('id', 0));
        return $list->renderHtml();
    }

    public function renderSavList()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de l\'équipement absent');
        }

        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) BimpTools::getValue('id', 0));

        if (!BimpObject::objectLoaded($equipment)) {
            return BimpRender::renderAlerts('ID de l\'équipement invalide');
        }

        $list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_SAV'), 'default', 1, null, 'SAV enregistrés pour cet équipement', 'wrench');
        $list->addFieldFilterValue('id_equipment', BimpTools::getValue('id', 0));

        $place = $equipment->getCurrentPlace();
        if (BimpObject::objectLoaded($place)) {
            if ((int) $place->getData('type') === BE_Place::BE_PLACE_CLIENT) {
                $values = array();
                $id_client = (int) $place->getData('id_client');
                if ($id_client) {
                    $values['fields']['id_client'] = $id_client;
                }
                $id_contact = (int) $place->getData('id_contact');
                if ($id_contact) {
                    $values['fields']['id_contact'] = $id_contact;
                }
                $list->setAddFormValues($values);
            }
        }

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
                
                if(isset($data['warning']) && $data['warning'] != "")
                    $data['warning'] = BimpRender::renderAlerts($data['warning'], 'danger');
            }
        }


        die(json_encode(array(
            'errors'     => $errors,
            'data'       => $data,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessGetEquipmentGsxInfos()
    {
        $errors = array();
        $sucess = '';
        $html = '';

        $id_equipment = (int) BimpTools::getValue('id_equipment', 0);
        if ($id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            if (BimpObject::objectLoaded($equipment)) {

                $data = $equipment->gsxLookup($equipment->getData('serial'), $errors);

                if (isset($data['warning']) && $data['warning']) {
                    $html .= BimpRender::renderAlerts($data['warning'], 'danger');
                }
                if (isset($data['date_warranty_end']) && $data['date_warranty_end']) {
                    if ($data['date_warranty_end'] < date('Y-m-d')) {
                        $class = 'danger';
                    } else {
                        $class = 'info';
                    }

                    $DT = new DateTime($data['date_warranty_end']);
                    $msg = 'Date de fin de garantie: ' . $DT->format('d / m / Y');
                    $html .= BimpRender::renderAlerts($msg, $class);
                }
                else{
                    $html .= BimpRender::renderAlerts("Date de fin de garantie: inconnue", 'danger');
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $sucess,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
}

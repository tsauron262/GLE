<?php

require_once DOL_DOCUMENT_ROOT . "/bimpequipment/controllers/equipmentController.php";

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/controllers/equipmentController.php';

class entrepotController extends equipmentController
{

    public function displayHead()
    {
        
    }

    public function renderHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de l\'entrepôt absent');
        }

        $entrepot = $this->config->getObject('', 'entrepot');
        if (is_null($entrepot) || !isset($entrepot->id) || !$entrepot->id) {
            return BimpRender::renderAlerts('Aucun entrepôt trouvé pour l\'ID ' . BimpTools::getValue('id', ''));
        }

        $html = '';

        $html .= '<div class="page_content container-fluid">';
        $html .= '<h1>Liste des équipements pour l\'entrepôt "' . $entrepot->libelle . ($entrepot->description ? ' - ' . $entrepot->description : '') . '"</h1>';

        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $equipment = BimpObject::getInstance($this->module, 'Equipment');
        BimpObject::loadClass($this->module, 'BE_Place');
        $list = new BC_ListTable($equipment, 'default', 1, null, 'Equipements');
        $list->addFieldFilterValue('epl.type', (int) BE_Place::BE_PLACE_ENTREPOT);
        $list->addFieldFilterValue('epl.id_entrepot', (int) $entrepot->id);
        $list->addFieldFilterValue('epl.position', 1);
        $list->addJoin('be_equipment_place', 'a.id = epl.id_equipment', 'epl');
        $list->setAddFormValues(array(
            'objects' => array(
                'places' => array(
                    'fields' => array(
                        'type'        => BE_Place::BE_PLACE_ENTREPOT,
                        'id_entrepot' => (int) $entrepot->id
                    )
                )
            )
        ));
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }
}

<?php

class BE_Package extends BimpObject
{

    // Droits Users: 

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'addEquipment':
            case 'addProduct':
            case 'removeEquipment':
            case 'removeProduct':
            case 'saveProductQty':
                return (int) $this->can('edit');
        }
        return (int) parent::canSetAction($action);
    }

    // Getters: 

    public function getCurrentPlace()
    {
        return null;
    }

    // Traitements:

    public function checkEquipments()
    {
        $equipments = $this->getData('equipments');
        $update = false;

        foreach ($equipments as $key => $id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            if (!BimpObject::objectLoaded($equipment)) {
                unset($equipments[$key]);
                $update = true;
            }
        }

        if ($update) {
            $this->updateField('equipments', $equipments);
        }
    }

    public function checkProducts()
    {
        $products = $this->getData('products');
        $update = false;

        foreach ($products as $id_product => $qty) {
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);
            if (!BimpObject::objectLoaded($product)) {
                unset($products[(int) $id_product]);
                $update = true;
            }
        }

        if ($update) {
            $this->updateField('products', $products);
        }
    }

    public function addEquipment($id_equipment)
    {
        
    }

    public function removeEquipment($id_equipment, $id_entrepot)
    {
        
    }

    public function addProduct($id_product, $id_entrepot)
    {
        
    }

    public function removeProduct($id_product, $id_entrepot)
    {
        
    }

    public function saveProductQty($id_product, $new_qty, $id_entrepot)
    {
        
    }

    public function onNewPlace()
    {
        
    }

    // Rendus HTML: 

    public function renderEquipmentsView()
    {
        $html = '';

        $html .= '<div class="packageAddEquipmentForm singleLineForm" style="margin-bottom: 15px; width: 100%">';
        $html .= '<div class="singleLineFormCaption">';
        $html .= '<h4>' . BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajout d\'équipements</h4>';
        $html .= '</div>';

        $html .= '<div class="singleLineFormContent">';
        $content .= BimpInput::renderInput('text', 'search_serial', '', array(
                    'extra_class' => 'large',
                    'style'       => 'width: 300px;'
        ));
        $content .= '<br/><span class="small">N° de série</span>';
        $html .= BimpInput::renderInputContainer('search_serial', '', $content, '');

        $html .= '<button type="button" class="btn btn-primary" onclick="addPackageEquipment($(this));">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= $this->renderEquipmentsList();

        return $html;
    }

    public function renderEquipmentsList()
    {
        $html .= '';

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');

        $list = new BC_ListTable($equipment, 'package', 1, null, 'Equipements inclus', $equipment->params['icon']);
        $list->addFieldFilterValue('id_package', (int) $this->id);

        $html .= $list->renderHtml();
        return $html;
    }

    public function renderProductsView()
    {
        $html = '';

        $html .= $this->renderProductsList();

        return $html;
    }

    public function renderProductsList()
    {
        $html = '';

        return $html;
    }

    // Actions: 

    public function actionAddProduct($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveProduct($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSaveProductQty($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}

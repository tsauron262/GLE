<?php

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

class BS_Pret extends BimpObject
{

    // Getters: 

    public function getCreateJsCallback()
    {
        if ((int) $this->getData('id_sav')) {
            $result = $this->actionGeneratePDF(array(), $success);
            if (count($result['errors'])) {
                $msg = BimpTools::getMsgFromArray($result['errors'], 'Echec de la génération du bon de prêt');
                return 'bimp_msg(\'' . addslashes($msg) . '\', \'danger\');';
            }

            return 'window.open("' . $result['file_url'] . '");';
        }

        return '';
    }

    public function getEquipmentsArray()
    {
        $equipments = array();

        $sav = $this->getChildObject('sav');

        $equipment_instance = BimpObject::getInstance('bimpequipment', 'Equipment');
        $unreturned = array();

        if (BimpObject::objectLoaded($sav)) {
            $code_centre = $this->getData('code_centre');
            if (!$code_centre) {
                $code_centre = (string) $sav->getData('code_centre');
            }
            if ($code_centre) {
                $unreturned = $this->getSavUnreturnedEquipments($code_centre);
                $list = $equipment_instance->getList(array(
                    'p.position'    => 1,
                    'p.type'        => 7,
                    'p.code_centre' => $code_centre
                        ), null, null, 'id', 'desc', 'array', array('a.id'), array(
                    array(
                        'table' => 'be_equipment_place',
                        'alias' => 'p',
                        'on'    => 'p.id_equipment = a.id'
                    )
                ));
            }
        } else {
            $id_entrepot = (int) BimpTools::getPostFieldValue('id_entrepot', 0);
            
            $unreturned = $this->getUnreturnedEquipments($id_entrepot);

            $list = $equipment_instance->getList(array(
                'p.position'    => 1,
                'p.type'        => 7,
                'p.id_entrepot' => (int) $id_entrepot
                    ), null, null, 'id', 'desc', 'array', array('a.id'), array(
                array(
                    'table' => 'be_equipment_place',
                    'alias' => 'p',
                    'on'    => 'p.id_equipment = a.id'
                )
            ));
        }

        foreach ($list as $item) {
            if (in_array((int) $item['id'], $unreturned)) {
                continue;
            }
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $item['id']);
            if ($equipment->isLoaded()) {
                $equipments[(int) $item['id']] = $equipment->getData('serial') . ' - ' . $equipment->displayProduct('nom', true);
            }
        }

        return $equipments;
    }

    public function getSavUnreturnedEquipments($code_centre = '')
    {
        $filters = array(
            'returned' => 0,
        );
        if ($code_centre) {
            $filters['code_centre'] = $code_centre;
        }
        $list = $this->getList($filters, null, null, 'id', 'desc', 'array', array('id'));
        $items = array();
        foreach ($list as $item) {
            $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $item['id']);
            if ($instance->isLoaded()) {
                $asso = new BimpAssociation($instance, 'equipments');
                foreach ($asso->getAssociatesList() as $id_equipment) {
                    $items[] = (int) $id_equipment;
                }
            }
        }
        return $items;
    }

    public function getUnreturnedEquipments($id_entrepot = 0)
    {
        $filters = array(
            'returned' => 0,
        );
        if ($id_entrepot) {
            $filters['id_entrepot'] = $id_entrepot;
        }
        $list = $this->getList($filters, null, null, 'id', 'desc', 'array', array('id'));
        $items = array();
        foreach ($list as $item) {
            $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $item['id']);
            if ($instance->isLoaded()) {
                $asso = new BimpAssociation($instance, 'equipments');
                foreach ($asso->getAssociatesList() as $id_equipment) {
                    $items[] = (int) $id_equipment;
                }
            }
        }
        return $items;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        $buttons[] = array(
            'label'   => 'Bon de prêt',
            'icon'    => 'fas_file-pdf',
            'onclick' => $this->getJsActionOnclick('generatePDF', array(
                'file_type' => 'pret'
                    ), array(
                'success_callback' => $callback
            ))
        );

        return $buttons;
    }

    public function getListFilters()
    {
        $filters = array();
        if (BimpTools::isSubmit('id_entrepot')) {
            $entrepots = explode('-', BimpTools::getValue('id_entrepot'));

            $filters[] = array('name'   => 'id_entrepot', 'filter' => array(
                    'IN' => implode(',', $entrepots)
            ));
        }

        return $filters;
    }

    public function defaultDisplayEquipmentsItem($id_equipment)
    {
        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
        if ($equipment->isLoaded()) {
            $label = '';
            $product = $equipment->getChildObject('product');
            if (!is_null($product) && isset($product->id) && $product->id) {
                $label = $product->label;
            } else {
                $label = $equipment->getData('product_label');
            }

            $label .= ' - N° série: ' . $equipment->getData('serial');

            $url = $equipment->getUrl();
            $html = BimpRender::renderIcon($equipment->params['icon']);
            $html .= '&nbsp;&nbsp;<a href="' . $url . '">' . $label . '</a>';
            $html .= BimpRender::renderObjectIcons($equipment, true, 'default', $url);
            return $html;
        }
        return BimpRender::renderAlerts('Equipement non trouvé (ID ' . $id_equipment . ')', 'warning');
    }

    // Actions: 

    public function actionGeneratePDF($data, &$success)
    {
        $success = 'Fichier PDF généré avec succès';

        $errors = array();
        $file_url = '';

        require_once DOL_DOCUMENT_ROOT . "/bimpsupport/core/modules/bimpsupport/modules_bimpsupport.php";

        $errors = bimpsupport_pdf_create($this->db->db, $this, 'sav', 'pret');

        if (!count($errors)) {
            $sav = $this->getChildObject('sav');
            $ref = 'Pret-' . $sav->getData('ref') . '-' . $this->getData('ref');
            $file_url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('sav/' . $sav->id . '/' . $ref . '.pdf');
        }

        return array(
            'errors'   => $errors,
            'file_url' => $file_url
        );
    }

    // Overrides: 

    public function checkObject()
    {
        if (!(int) $this->getData('id_entrepot') && $this->getData('code_centre')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';
            global $tabCentre;

            if (isset($tabCentre[$this->getData('code_centre')][8])) {
                $id_entrepot = (int) $tabCentre[$this->getData('code_centre')][8];
                $this->updateField('id_entrepot', $id_entrepot);
            }
        }
    }

    public function validate()
    {
        $errors = parent::validate();

        if ((int) $this->getData('id_sav')) {
            $sav = $this->getChildObject('sav');
            if (BimpObject::objectLoaded($sav)) {
                if (!(int) $this->getData('id_client')) {
                    $this->set('id_client', (int) $sav->getData('id_client'));
                }
                if (!(int) $this->getData('id_entrepot')) {
                    $this->set('id_entrepot', (int) $sav->getData('id_entrepot'));
                }
                if (!(string) $this->getData('code_centre')) {
                    $this->set('code_centre', $sav->getData('code_centre'));
                }
            }
        } else {
            $id_client = (int) $this->getData('id_client');
            if (!$id_client) {
                $errors[] = 'Veuillez sélectionner un client ou un SAV';
            } else {
                $client = $this->getChildObject('client');
                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Le client d\'ID ' . $this->getData('id_client') . ' n\'existe pas';
                }
            }
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();
        $errors = parent::create($warnings, $force_create);

        if ($this->isLoaded()) {
            $this->updateField('ref', 'PRET' . $this->id);
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_returned = (int) $this->getInitData('returned');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ((int) $this->getData('returned') !== $init_returned) {
                $products = $this->getChildrenObjects('products');

                foreach ($products as $pret_product) {
                    $prod_errors = array();
                    if ((int) $this->getData('returned')) {
                        $prod_errors = $pret_product->increaseStock();
                    } else {
                        $prod_errors = $pret_product->decreaseStock();
                    }

                    if (count($prod_errors)) {
                        $product = $pret_product->getChildObject('product');
                        if (BimpObject::objectLoaded($product)) {
                            $prod_label = '"' . $product->getRef() . '"';
                        } else {
                            $prod_label = ' d\'ID ' . $pret_product->getData('id_product');
                        }
                        $warnings[] = BimpTools::getMsgFromArray($prod_errors, 'Produit ' . $prod_label . ': erreurs lors de la correction des stocks');
                    }
                }
            }
        }

        return $errors;
    }
}

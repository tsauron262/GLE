<?php

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

class BS_SavPret extends BimpObject
{

    public function getCreateJsCallback()
    {
        $result = $this->actionGeneratePDF(array(), $success);
        if (count($result['errors'])) {
            $msg = count($result['errors']) . ' erreur(s) détectée(s): <br/>';
            foreach ($result['errors'] as $error) {
                $msg .= ' - ' . $error . '<br/>';
            }
            return 'bimp_msg(\'' . addslashes($msg) . '\', \'danger\');';
        }

        return 'window.open("' . $result['file_url'] . '");';
    }

    public function getEquipmentsArray()
    {
        $equipments = array();

        $sav = $this->getParentInstance();
        $code_centre = (string) $sav->getData('code_centre');
        if ($code_centre) {
            $unreturned = $this->getUnreturnedEquipments($code_centre);
            $instance = BimpObject::getInstance('bimpequipment', 'Equipment');
            $list = $instance->getList(array(
                'p.position'    => 1,
                'p.type'        => 7,
                'p.code_centre' => $code_centre
                    ), null, null, 'id', 'desc', 'array', array(
                'a.id' =>
                  F  ), array(
                array(
                    'table' => 'be_equipment_place',
                    'alias' => 'p',
                    'on'    => 'p.id_equipment = a.id'
                )
            ));
            foreach ($list as $item) {
                if (in_array((int) $item['id'], $unreturned)) {
                    continue;
                }

                if ($instance->fetch((int) $item['id'])) {
                    $equipments[(int) $item['id']] = $instance->getData('serial') . ' - ' . $instance->displayProduct('nom', true);
                }
            }
        }

        return $equipments;
    }

    public function getUnreturnedEquipments($code_centre = '')
    {
        $filters = array(
            'returned' => 0,
        );
        if ($code_centre) {
            $filters['code_centre'] = $code_centre;
        }
        $list = $this->getList($filters, null, null, 'id', 'desc', 'array', array('id'));
        $items = array();
        $instance = BimpObject::getInstance($this->module, $this->object_name);
        foreach ($list as $item) {
            if ($instance->fetch((int) $item['id'])) {
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

    public function defaultDisplayEquipmentsItem($id_equipment)
    {
        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        if ($equipment->fetch($id_equipment)) {
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
            $sav = $this->getParentInstance();
            $ref = 'Pret-' . $sav->getData('ref') . '-' . $this->getData('ref');
            $file_url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('sav/' . $sav->id . '/' . $ref . '.pdf');
        }

        return array(
            'errors'   => $errors,
            'file_url' => $file_url
        );
    }

    // Overrides: 

    public function create()
    {
        $sav = $this->getParentInstance();
        if (BimpObject::objectLoaded($sav)) {
            $id_client = (int) $sav->getData('id_client');
            if (!$id_client) {
                return array('Aucun client enregistré pour ce SAV');
            }
            $code_centre = (string) $sav->getData('code_centre');
            if (!$code_centre) {
                return array('Aucun centre enregistré pour ce SAV');
            }
            $this->set('id_client', $id_client);
            $this->set('code_centre', $code_centre);
        } else {
            return array('SAV non spécifié');
        }

        if (!count($this->associations['equipments'])) {
            $errors[] = 'Aucun équipement sélectionné';
        } else {
            $errors = parent::create();

            if ($this->isLoaded()) {
                $this->set('ref', 'PRET' . $this->id);
                $this->update();
            }
        }

        return $errors;
    }
}

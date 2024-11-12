<?php

class BimpLocationLine extends BimpObject
{

    // Getters booléens : 

    public function isCreatable($force_create = false, &$errors = array())
    {
        $loc = $this->getParentInstance();

        if (BimpObject::objectLoaded($loc)) {
            return $loc->areLinesEditable();
        }

        return 0;
    }

    public function isEquipmentAvailable($date_from, $date_to, &$errors = array())
    {
        $id_eq = (int) $this->getData('id_equipment');

        if (!$id_eq) {
            $errors[] = 'Aucun équipement sélectionné';
            return 0;
        }

        $eq = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_eq);
        if (!BimpObject::objectLoaded($eq)) {
            $errors[] = 'L\'équipement #' . $id_eq . ' n\'existe plus';
            return 0;
        }
        $place = $eq->getCurrentPlace();
        if (!BimpObject::objectLoaded($place) && $place->getData('type') !== 30) {
            $errors[] = 'L\'équipement ' . $eq->getLink() . ' n\'est pas un emplacement de type "location"';
            return 0;
        }

        $where = 'l.location >= 0 AND a.id_equipment = ' . $id_eq . ' AND a.date_from <= \'' . $date_to . '\' AND a.date_to >= \'' . $date_from . '\'';

        if ($this->isLoaded()) {
            $where .= ' AND a.id != ' . $this->id;
        }

        $rows = $this->db->getRows('bimp_location_line a', $where, null, 'array', array('id'), null, null, array(
            'l' => array(
                'table' => 'bimp_location',
                'on'    => 'l.id = a.id_location'
            )
        ));

        if (!empty($rows)) {
            $errors[] = 'L\'équipement ' . $eq->getLink() . ' n\'est pas disponibles aux dates sélectionnées';
            return 0;
        }

        return 1;
    }

    // Getters params: 

    public function getListExtraBtn()
    {
        $buttons = array();

        return $buttons;
    }

    // Getters données :

    public function getAmounts()
    {
        $amounts = array(
            'pu_ht'     => 0,
            'tva_tx'    => 0,
            'qty'       => 0,
            'total_ht'  => 0,
            'total_tva' => 0,
            'total_ttc' => 0
        );

        $period_data = BimpTools::getDatesIntervalData($this->getData('date_from'), $this->getData('date_to'), false, true);
        $amounts['qty'] = $period_data['full_days'];

        $prod = $this->getChildObject('forfait');
        if (BimpObject::objectLoaded($prod)) {
            $amounts['pu_ht'] = $prod->getData('price');
            $amounts['tva_tx'] = $prod->getData('tva_tx');
        }

        return $amounts;
    }

    public function getInputValue($field_name)
    {
        if (!$this->isLoaded()) {
            switch ($field_name) {
                case 'date_from':
                case 'date_to':
                    $loc = $this->getParentInstance();
                    if (BimpObject::objectLoaded($loc)) {
                        return $loc->getData($field_name);
                    }
                    break;
            }
        }

        return $this->getData($field_name);
    }

    // Getters array: 

    public function getSelectForfaitsArray()
    {
        $forfaits = array();

        $date_from = $this->getData('date_from');
        $date_to = $this->getData('date_to');
        $id_eq = (int) $this->getData('id_equipment');

        if ($date_from && $date_to && $id_eq) {
            $eq = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_eq);

            if (BimpObject::objectLoaded($eq)) {
                $product = $eq->getChildObject('bimp_product');
                if (BimpObject::objectLoaded($product)) {
                    $interval = BimpTools::getDatesIntervalData($date_from, $date_to);
                    $nDays = $interval['full_days'];
                    $prod_forfaits = $product->getData('forfaits_location');

                    foreach ($prod_forfaits as $id_forfait) {
                        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_forfait);

                        if (BimpObject::objectLoaded($prod) && $prod->getData('min_qty') <= $nDays) {
                            $forfaits[$id_forfait] = $prod->getRef() . ' - ' . $prod->getData('label');
                        }
                    }
                }
            }
        }


        return $forfaits;
    }

    // Affichages : 

    public function displayArticle()
    {
        $html = '';
        if ((int) $this->getData('id_equipment')) {
            $eq = $this->getChildObject('equipment');
            if (BimpObject::objectLoaded($eq)) {
                $html .= $eq->displayProduct('default', false, true);
                $html .= $html .= 'Equipement : ' . $eq->getLink() . '<br/>';
            } else {
                $html .= BimpRender::renderAlerts('L\'équipement #' . $this->getData('id_equipment') . ' n\'existe plus');
            }
        }
        return $html;
    }

    public function displayForfaitInfos()
    {
        $html = '';

        if ((int) $this->getData('id_forfait')) {
            $forfait = $this->getChildObject('forfait');
            if (BimpObject::objectLoaded($forfait)) {
                $html .= $forfait->getLink() . '<br/>';
                $html .= '<b>' . $forfait->getData('label') . '</b><br/>';
//                $html .= $forfait->displayDataDefault('price') . ' € / jour';
            } else {
                $html .= BimpRender::renderAlerts('Le forfait #' . $this->getData('id_forfait') . ' n\'existe plus');
            }
        }

        return $html;
    }

    public function displayDates()
    {
        $html = '';

        $html .= 'Du ' . $this->displayDataDefault('date_from') . '<br/>';
        $html .= 'Au ' . $this->displayDataDefault('date_to') . '<br/>';
        return $html;
    }

    // Rendus HTML : 

    public function renderForfaitInput()
    {
        $html = '';

        $errors = array();

        $date_from = $this->getData('date_from');
        $date_to = $this->getData('date_to');

        if (!$date_from) {
            $errors[] = 'Veuillez saisir la date de début';
        }

        if (!$date_to) {
            $errors[] = 'Veuillez saisir la date de fin';
        }

        if (!count($errors)) {
            if ($this->isEquipmentAvailable($date_from, $date_to, $errors)) {
                return BimpInput::renderInput('select', 'id_forfait', (int) $this->getData('id_forfait'), array(
                            'options' => $this->getSelectForfaitsArray()
                ));
            }
        }

        $html .= '<input type="hidden" value="0" name="id_forfait"/>';
        $html .= BimpRender::renderAlerts($errors);

        return $html;
    }
}

<?php

class BimpLocationLine extends BimpObject
{

    protected $amounts = null;

    // Getters booléens : 

    public function isCreatable($force_create = false, &$errors = array())
    {
        $loc = $this->getParentInstance();

        if (BimpObject::objectLoaded($loc)) {
            return $loc->areLinesEditable();
        }

        return 0;
    }

    public function isEquipmentAvailable($id_eq, $date_from = '', $date_to = '', $id_entrepot = 0, &$errors = array())
    {
        if (!$id_eq) {
            $id_eq = (int) $this->getData('id_equipment');
        }

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
        if (!BimpObject::objectLoaded($place) || (int) $place->getData('type') !== BE_Place::BE_PLACE_LOCATION) {
            $errors[] = 'L\'équipement ' . $eq->getLink() . ' n\'est pas dans un emplacement de type "location"';
            return 0;
        }

        if ($id_entrepot && $id_entrepot !== $place->getData('id_entrepot')) {
            $entrepot = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Entrepot', $id_entrepot);
            $errors[] = 'L\'équipement ' . $eq->getLink() . ' n\'est pas en location dans l\'entrepot ' . (BimpObject::objectLoaded($entrepot) ? $entrepot->getName() : '#' . $id_entrepot);
            return 0;
        }

        if (!$date_from) {
            $date_from = $this->getData('date_from');
        }

        if (!$date_to) {
            $date_to = $this->getData('date_to');
        }

        $where = 'l.status >= 0 AND a.cancelled = 0 AND a.id_equipment = ' . $id_eq . ' AND a.date_from <= \'' . $date_to . '\' AND a.date_to >= \'' . $date_from . '\'';

        if ($this->isLoaded()) {
            $where .= ' AND a.id != ' . $this->id;
        }

        $rows = $this->db->getRows('bimp_location_line a', $where, null, 'array', array('a.id', 'a.id_location', 'a.date_from', 'a.date_to'), null, null, array(
            'l' => array(
                'table' => 'bimp_location',
                'on'    => 'l.id = a.id_location'
            )
        ));

        if (is_array($rows)) {
            if (!empty($rows)) {
                $msg = 'L\'équipement ' . $eq->getLink() . ' n\'est pas disponible aux dates sélectionnées' . (BimpCore::isUserDev() ? ' (' . $date_from . ' - ' . $date_to . ')' : '');
                foreach ($rows as $r) {
                    $loc = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocation', (int) $r['id_location']);
                    $msg .= '<br/>- Réservé du ' . date('d / m / Y', strtotime($r['date_from'])) . ' au ' . date('d / m / Y', strtotime($r['date_to']));
                    $msg .= (BimpObject::objectLoaded($loc) ? ' : ' . $loc->getLink() : '');
                }
                $errors[] = $msg;
            }
        } else {
            $errors[] = 'Echec de la vérification des disponibilités - ' . $this->db->err();
        }

        if (count($errors)) {
            return 0;
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = [])
    {
        if ((int) $this->getData('cancelled')) {
            $errors[] = 'Cette ligne est annulée';
            return 0;
        }

        return parent::isDeletable($force_delete, $errors);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'reopen':
                if (!(int) $this->getData('cancelled')) {
                    $errors[] = 'Cette ligne de location n\'est pas annulée';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getListExtraBtn()
    {
        $buttons = array();

        return $buttons;
    }

    // Getters données :

    public function getAmounts($recalculate = false)
    {
        if (is_null($this->amounts) || $recalculate) {
            $this->amounts = array(
                'pu_ht'          => 0,
                'remise'         => 0,
                'pu_ht_remise'   => 0,
                'tva_tx'         => 0,
                'pu_ttc'         => 0,
                'qty'            => 0,
                'total_ht'       => 0,
                'total_tva'      => 0,
                'total_ttc'      => 0,
                'total_billed'   => 0,
                'remain_to_bill' => 0
            );

            if ((int) $this->getData('cancelled')) {
                $this->amounts['qty'] = 0;
            } else {
                $period_data = BimpTools::getDatesIntervalData($this->getData('date_from'), $this->getData('date_to'), false, true);
                $this->amounts['qty'] = $period_data['full_days'];
            }

            $this->amounts['pu_ht'] = $this->amounts['pu_ht_remise'] = (float) $this->getData('pu_ht');
            $this->amounts['remise'] = (float) $this->getData('remise');

            if ($this->amounts['remise']) {
                $this->amounts['pu_ht_remise'] -= ($this->amounts['pu_ht'] * ($this->amounts['remise'] / 100));
            }

            $this->amounts['tva_tx'] = (float) $this->getData('tva_tx');
            $this->amounts['pu_ttc'] = $this->amounts['pu_ht_remise'] * (1 + ($this->amounts['tva_tx'] / 100));

            $this->amounts['total_ht'] = $this->amounts['pu_ht_remise'] * $this->amounts['qty'];
            $this->amounts['total_ttc'] = $this->amounts['total_ht'] * (1 + ($this->amounts['tva_tx'] / 100));
            $this->amounts['total_tva'] = $this->amounts['total_ttc'] - $this->amounts['total_ht'];

            $this->amounts['total_billed'] = $this->getTotalBilled();
            $this->amounts['remain_to_bill'] = $this->amounts['total_ttc'] - $this->amounts['total_billed'];
        }

        return $this->amounts;
    }

    public function getTotalBilled()
    {
        if ($this->isLoaded()) {
            $where = '((fl.linked_object_name = \'location_line\' AND fl.linked_id_object = ' . $this->id . ')';
            $where .= ' OR (fl.linked_object_name = \'bc_vente_article\' AND (SELECT va.linked_id_object FROM ' . MAIN_DB_PREFIX . 'bc_vente_article va WHERE va.id = fl.linked_id_object) = ' . $this->id . '))';
            $where .= ' AND f.fk_statut IN(0,1,2) AND f.type IN(0,1,2,3)';

            return (float) $this->db->getSum('facturedet a', 'a.total_ttc', $where, array(
                        'fl' => array(
                            'table' => 'bimp_facture_line',
                            'on'    => 'a.rowid = fl.id_line'
                        ),
                        'f'  => array(
                            'table' => 'facture',
                            'on'    => 'f.rowid = a.fk_facture'
                        )
            ));
        }

        return 0;
    }

    public function getBilledQties(&$errors = array())
    {
        $data = array();

        if ($this->isLoaded($errors)) {
            $linked_filters = '((fl.linked_object_name = \'location_line\' AND fl.linked_id_object = ' . $this->id . ')';
            $linked_filters .= ' OR (fl.linked_object_name = \'bc_vente_article\' AND (SELECT va.linked_id_object FROM ' . MAIN_DB_PREFIX . 'bc_vente_article va WHERE va.id = fl.linked_id_object) = ' . $this->id . '))';

            $sql = BimpTools::getSqlFullSelectQuery('facturedet', array(
                        'a.fk_product',
                        'a.subprice as pu_ht',
                        'a.tva_tx',
                        'a.remise_percent as remise',
                        'a.qty',
                            ), array(
                        'linked_custom' => array(
                            'custom' => $linked_filters
                        ),
                        'f.fk_statut'   => array(0, 1, 2),
                        'f.type'        => array(0, 1, 2, 3)
                            ), array(
                        'f'  => array(
                            'table' => 'facture',
                            'on'    => 'f.rowid = a.fk_facture'
                        ),
                        'fl' => array(
                            'table' => 'bimp_facture_line',
                            'on'    => 'fl.id_line = a.rowid'
                        )
            ));

            $rows = $this->db->executeS($sql, 'array');

            foreach ($rows as $r) {
                if (!isset($data[$r['fk_product']])) {
                    $data[$r['fk_product']] = array();
                }

                $pu_ttc = $r['pu_ht'] * (1 + ($r['tva_tx'] / 100));

                if ($r['remise']) {
                    $pu_ttc -= ($pu_ttc * ($r['remise'] / 100));
                }

                if (!isset($data[$r['fk_product']][$pu_ttc])) {
                    $data[$r['fk_product']][$pu_ttc] = array(
                        'pu_ht'  => $r['pu_ht'],
                        'tva_tx' => $r['tva_tx'],
                        'remise' => $r['remise'],
                        'qty'    => 0
                    );
                }

                $data[$r['fk_product']][$pu_ttc]['qty'] += $r['qty'];
            }
        }

        return $data;
    }

    public function getInputValue($field_name)
    {
        switch ($field_name) {
            case 'date_from':
            case 'date_to':
                if (!$this->isLoaded()) {
                    $loc = $this->getParentInstance();
                    if (BimpObject::objectLoaded($loc)) {
                        return $loc->getData($field_name);
                    }
                }
                break;

            case 'pu_ht':
            case 'tva_tx':
                if (!$this->isLoaded() || (int) $this->getData('id_forfait') !== (int) $this->getInitData('id_forfait')) {
                    $prod = $this->getChildObject('forfait');
                    if (BimpObject::objectLoaded($prod)) {
                        switch ($field_name) {
                            case 'pu_ht':
                                return $prod->getData('price');

                            case 'tva_tx':
                                return $prod->getData('tva_tx');
                        }
                    }
                    return 0;
                }
                break;
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

    public function displayEquipment($no_html = false)
    {
        $html = '';
        if ((int) $this->getData('id_equipment')) {
            $eq = $this->getChildObject('equipment');
            if (BimpObject::objectLoaded($eq)) {
                $html .= $eq->displayProduct('default', $no_html, true);
                $html .= '<br/><b>Equipement : </b>' . ($no_html ? $eq->getRef() : $eq->getLink()) . '<br/>';
            } else {
                $html .= BimpRender::renderAlerts('L\'équipement #' . $this->getData('id_equipment') . ' n\'existe plus');
            }
        }
        return $html;
    }

    public function displayForfaitInfos($with_price = true)
    {
        $html = '';

        if ((int) $this->getData('id_forfait')) {
            $forfait = $this->getChildObject('forfait');
            if (BimpObject::objectLoaded($forfait)) {
                $html .= $forfait->getLink() . '<br/>';
                $html .= '<b>' . $forfait->getData('label') . '</b><br/>';
                if ($with_price) {
                    $html .= $this->displayDataDefault('pu_ht') . ' / jour';
                    $remise = $this->getData('remise');
                    if ($remise) {
                        $html .= ' (-' . BimpTools::displayFloatValue($remise, 2, ',', 0, 0, 1, 0, 1, 1) . ' %)';
                    }
                }
            } else {
                $html .= BimpRender::renderAlerts('Le forfait #' . $this->getData('id_forfait') . ' n\'existe plus');
            }
        }

        return $html;
    }

    public function displayDates($single_line = false, $display_availabilities = true)
    {
        $html = '';

        if ((int) $this->getData('cancelled')) {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Annulée</span>';
        } else {
            $loc_from = '';
            $loc_to = '';

            $loc = $this->getParentInstance();
            if (BimpObject::objectLoaded($loc)) {
                $loc_from = $loc->getData('date_from');
                $loc_to = $loc->getData('date_to');
            }

            $html .= 'Du ';

            if ($loc_from && $loc_from != $this->getData('date_from')) {
                $html .= '<span class="important">';
                $html .= $this->displayDataDefault('date_from');
                $html .= '</span>' . ($single_line ? ' ' : '<br/>');
            } else {
                $html .= $this->displayDataDefault('date_from');
            }

            $html .= ($single_line ? ' au ' : '<br/>Au ');

            if ($loc_to && $loc_to != $this->getData('date_to')) {
                $html .= '<span class="important">';
                $html .= $this->displayDataDefault('date_to');
                $html .= '</span>';
            } else {
                $html .= $this->displayDataDefault('date_to');
            }
        }

        if ($display_availabilities) {
            $html .= $this->renderAvailablitiesAlerts(false);
        }


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

        $loc = $this->getParentInstance();
        if (!BimpObject::objectLoaded($loc)) {
            $errors[] = 'Location absente';
        }

        if (!count($errors)) {
            if ($this->isEquipmentAvailable((int) $this->getData('id_equipment'), $date_from, $date_to, (int) $loc->getData('id_entrepot'), $errors)) {
                $html .= $this->renderAvailablitiesAlerts(false);

                $html .= BimpInput::renderInput('select', 'id_forfait', (int) $this->getData('id_forfait'), array(
                            'options' => $this->getSelectForfaitsArray()
                ));

                return $html;
            }
        }

        $html .= '<input type="hidden" value="0" name="id_forfait"/>';
        $html .= BimpRender::renderAlerts($errors);

        return $html;
    }

    public function renderAvailablitiesAlerts($icon_only = true)
    {
        $html = '';

        $date_from = $this->getData('date_from');
        $date_to = $this->getData('date_to');
        $id_eq = (int) $this->getData('id_equipment');

        $warnings = array();

        if ($date_from && $date_to && $id_eq) {
            $dt = new DateTime($date_from);
            $dt->sub(new DateInterval('P15D'));
            $where = 'l.status >= 0 AND a.cancelled = 0 AND a.id_equipment = ' . $id_eq . ' AND a.date_to < \'' . $date_from . '\'';
            $where .= ' AND a.date_to >= \'' . $dt->format('Y-m-d') . '\'';

            if ($this->isLoaded()) {
                $where .= ' AND a.id != ' . $this->id;
            }

            $rows = $this->db->getRows('bimp_location_line a', $where, 1, 'array', array('a.id', 'a.id_location', 'a.date_to'), 'a.date_to', 'desc', array(
                'l' => array(
                    'table' => 'bimp_location',
                    'on'    => 'l.id = a.id_location'
                )
            ));

            if (isset($rows[0])) {
                $dt = new DateTime($rows[0]['date_to']);
                $dt->add(new DateInterval('P1D'));
                $loc = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocation', (int) $rows[0]['id_location']);
                $warnings[] = 'Equipement disponible à partir du <b>' . $dt->format('d / m / Y') . '</b>' . (BimpObject::objectLoaded($loc) ? ' : ' . $loc->getRef() : '');
            }


            $dt = new DateTime($date_to);
            $dt->add(new DateInterval('P15D'));
            $where = 'l.status >= 0 AND a.cancelled = 0 AND a.id_equipment = ' . $id_eq . ' AND a.date_from > \'' . $date_to . '\'';
            $where .= ' AND a.date_from <= \'' . $dt->format('Y-m-d') . '\'';

            if ($this->isLoaded()) {
                $where .= ' AND a.id != ' . $this->id;
            }

            $rows = $this->db->getRows('bimp_location_line a', $where, 1, 'array', array('a.id', 'a.id_location', 'a.date_from'), 'a.date_from', 'asc', array(
                'l' => array(
                    'table' => 'bimp_location',
                    'on'    => 'l.id = a.id_location'
                )
            ));

            if (isset($rows[0])) {
                $dt = new DateTime($rows[0]['date_from']);
                $dt->sub(new DateInterval('P1D'));
                $loc = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocation', (int) $rows[0]['id_location']);
                $warnings[] = 'Equipement disponible jusqu\'au <b>' . $dt->format('d / m / Y') . '</b>' . (BimpObject::objectLoaded($loc) ? ' : ' . $loc->getRef() : '');
            }
        }

        if (!empty($warnings)) {
            if ($icon_only) {
                $msg = '<ul>';
                foreach ($warnings as $warning) {
                    $msg .= '<li>';
                    $msg .= '<span class="warning">' . $warning . '</span>';
                    $msg .= '</li>';
                }
                $msg .= '</ul>';
                $html .= '<span class="warning bs-popover"' . BimpRender::renderPopoverData($msg, 'top', true) . ' style="margin: 12px; font-size: 16px">';
                $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                $html .= '</span>';
            } else {
                $html .= BimpRender::renderAlerts($warnings, 'warning');
            }
        }

        return $html;
    }

    // Overrides : 

    public function reset()
    {
        parent::reset();

        $this->amounts = null;
    }

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            if ($this->getData('date_to') < $this->getData('date_from')) {
                $errors[] = 'La date de fin est antérieur à la date de début';
            }

            $forfait = $this->getChildObject('forfait');
            if (!BimpObject::objectLoaded($forfait)) {
                $errors[] = 'Le forfait #' . $this->getData('id_forfait') . ' n\'existe plus';
            } else {
                if (!(float) $this->getData('pu_ht')) {
                    $this->set('pu_ht', $forfait->getData('price'));
                }

                if (!(float) $this->getData('tva_tx')) {
                    $this->set('tva_tx', $forfait->getData('tva_tx'));
                }

                $location = $this->getParentInstance();

                $this->isEquipmentAvailable(0, '', '', $location->getData('id_entrepot'), $errors);
            }
        }

        return $errors;
    }

    public function delete(&$warnings = [], $force_delete = false)
    {
        $loc = $this->getParentInstance();

        if (BimpObject::objectLoaded($loc) && (!isset($loc->isDeleting) || !$loc->isDeleting)) {
            $amounts = $this->getAmounts();
            if ($amounts['total_billed'] > 0) {
                return $this->updateField('cancelled', 1);
            }
        }

        return parent::delete($warnings, $force_delete);
    }
}

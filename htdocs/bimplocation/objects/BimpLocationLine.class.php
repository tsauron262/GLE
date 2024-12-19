<?php

class BimpLocationLine extends BimpObject
{

    const STATUS_CANCELLED = -1;
    const STATUS_RESERVED = 0;
    const STATUS_ONGOING = 1;
    const STATUS_RESTITUTED = 10;

    public static $status_list = array(
        self::STATUS_CANCELLED  => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::STATUS_RESERVED   => array('label' => 'Réservé', 'icon' => 'fas_lock', 'classes' => array('warning')),
        self::STATUS_ONGOING    => array('label' => 'Mis à disposition', 'icon' => 'fas_hand-holding', 'classes' => array('info')),
        self::STATUS_RESTITUTED => array('label' => 'Restitué', 'icon' => 'fas_check', 'classes' => array('success')),
    );
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

    public function isEquipmentAvailable($id_eq = 0, $date_from = '', $date_to = '', $id_entrepot = 0, &$errors = array())
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

        if (!$id_entrepot) {
            $loc = $this->getParentInstance();
            if (BimpObject::objectLoaded($loc)) {
                $id_entrepot = (int) $loc->getData('id_entrepot');
            }
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

        $where = 'l.status >= 0 AND a.status >= 0 AND a.id_equipment = ' . $id_eq . ' AND a.date_from <= \'' . $date_to . '\' AND a.date_to >= \'' . $date_from . '\'';

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
        if ((int) $this->getData('status') < 0) {
            $errors[] = 'Cette ligne est annulée';
            return 0;
        }

        return parent::isDeletable($force_delete, $errors);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'reopen':
                if ((int) $this->getData('status') >= 0) {
                    $errors[] = 'Cette ligne de location n\'est pas annulée';
                    return 0;
                }
                return 1;

            case 'sellEquipment':
                if (!$this->isLoaded($errors)) {
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

    public function getQty()
    {
        $fac_from = $this->getFacFrom();
        $fac_to = $this->getFacTo();

        if ($fac_from && $fac_to && ($fac_from <= $fac_to)) {
            $period_data = BimpTools::getDatesIntervalData($fac_from, $fac_to, false, true);
            return $period_data['full_days'];
        }

        return 0;
    }

    public function getFacFrom()
    {
        $fac_from = $this->getData('fac_date_from');

        if (!$fac_from) {
            return $this->getData('date_from');
        }

        return $fac_from;
    }

    public function getFacTo()
    {
        $fac_to = $this->getData('fac_date_to');

        if (!$fac_to) {
            return $this->getData('date_to');
        }

        return $fac_to;
    }

    public function getAmounts($recalculate = false)
    {
        if (is_null($this->amounts) || $recalculate) {
            $fac_from = $this->getFacFrom();
            $fac_to = $this->getFacTo();
            $this->amounts = array(
                'fac_from'       => $fac_from,
                'fac_to'         => $fac_to,
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

            if ((int) $this->getData('status') < 0) {
                $this->amounts['qty'] = 0;
            } else {
                $period_data = BimpTools::getDatesIntervalData($fac_from, $fac_to, false, true);
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

            if ($this->amounts['remain_to_bill'] > -0.01 && $this->amounts['remain_to_bill'] < 0.01) {
                $this->amounts['remain_to_bill'] = 0;
            }
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
            case 'status':
                if (!$this->isLoaded()) {
                    $loc = $this->getParentInstance();
                    if (BimpObject::objectLoaded($loc)) {
                        return (int) $loc->getData('lines_process_status');
                    }
                }
                return (int) $this->getData('status');
            case 'date_from':
            case 'date_to':
            case 'fac_date_from':
            case 'fac_date_to':
                if (!$this->isLoaded()) {
                    $loc = $this->getParentInstance();
                    if (BimpObject::objectLoaded($loc)) {
                        return $loc->getData($field_name);
                    }
                }
                break;

//            case 'pu_ht':
//            case 'tva_tx':
//                if (!$this->isLoaded() || (int) $this->getData('id_forfait') !== (int) $this->getInitData('id_forfait')) {
//                    $prod = $this->getChildObject('forfait');
//                    if (BimpObject::objectLoaded($prod)) {
//                        switch ($field_name) {
//                            case 'pu_ht':
//                                return $prod->getData('price');
//
//                            case 'tva_tx':
//                                return $prod->getData('tva_tx');
//                        }
//                    }
//                    return 0;
//                }
//                break;
        }

        return $this->getData($field_name);
    }

    // Getters array: 

    public function getSelectForfaitsArray()
    {
        $forfaits = array();

//        $date_from = $this->getData('date_from');
//        $date_to = $this->getData('date_to');
        $id_eq = (int) $this->getData('id_equipment');

//        if ($date_from && $date_to && $id_eq) {
        if ($id_eq) {
            $eq = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_eq);

            if (BimpObject::objectLoaded($eq)) {
                $product = $eq->getChildObject('bimp_product');
                if (BimpObject::objectLoaded($product)) {
//                    $interval = BimpTools::getDatesIntervalData($date_from, $date_to);
//                    $nDays = $interval['full_days'];
                    $prod_forfaits = $product->getData('forfaits_location');

                    foreach ($prod_forfaits as $id_forfait) {
                        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_forfait);

//                        if (BimpObject::objectLoaded($prod) && $prod->getData('min_qty') <= $nDays) {
//                            $forfaits[$id_forfait] = $prod->getRef() . ' - ' . $prod->getData('label');
//                        }
                        if (BimpObject::objectLoaded($prod)) {
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

        if ((int) $this->getData('status') < 0) {
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

            $from = $this->getData('date_from');
            $to = $this->getData('date_to');

            if ($loc_from && $loc_from != $from) {
                $html .= '<span class="important">';
                $html .= $this->displayDataDefault('date_from');
                $html .= '</span>';
            } else {
                $html .= $this->displayDataDefault('date_from');
            }

            $html .= ($single_line ? ' au ' : '<br/>Au ');

            if ($loc_to && $loc_to != $to) {
                $html .= '<span class="important">';
                $html .= $this->displayDataDefault('date_to');
                $html .= '</span>';
            } else {
                $html .= $this->displayDataDefault('date_to');
            }

            if ($display_availabilities && $single_line) {
                $html .= $this->renderAvailablitiesAlerts(true);
            }

            $fac_from = $this->getData('fac_date_from');
            if ($fac_from && $fac_from == $from) {
                $fac_from = '';
            }
            $fac_to = $this->getData('fac_date_to');
            if ($fac_to && $fac_to == $to) {
                $fac_to = '';
            }

            if ($fac_from || $fac_to) {
                $html .= '<br/><span class="warning" style="font-size: 11px; font-style: italic">';
                $html .= 'Facturé ';

                if ($fac_from) {
                    $html .= ($fac_to ? 'du' : 'à partir du') . ' ' . date('d / m / Y', strtotime($fac_from));
                }

                if ($fac_to) {
                    $html .= ($fac_from ? ' au' : 'jusqu\'au') . ' ' . date('d / m / Y', strtotime($fac_to));
                }
                $html .= '</span>';
            }

            if ($display_availabilities && !$single_line) {
                $html .= $this->renderAvailablitiesAlerts(false);
            }
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
            $where = 'l.status >= 0 AND a.status >= 0 AND a.id_equipment = ' . $id_eq . ' AND a.date_to < \'' . $date_from . '\'';
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
            $where = 'l.status >= 0 AND a.status >= 0 AND a.id_equipment = ' . $id_eq . ' AND a.date_from > \'' . $date_to . '\'';
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

    // Traitements : 

    public function onSave(&$errors = array(), &$warnings = array())
    {
        parent::onSave($errors, $warnings);

        $loc = $this->getParentInstance();

        if (BimpObject::objectLoaded($loc)) {
            $loc->checkStatus();
        }
    }

    // Actions :

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture de la ligne de location effectuée avec succès';

        if ($this->isEquipmentAvailable(0, '', '', 0, $errors)) {
            $errors = $this->updateField('status', (int) BimpTools::getArrayValueFromPath($data, 'status', 0));

            if (!empty($errors)) {
                $loc = $this->getParentInstance();
                if (BimpObject::objectLoaded($loc)) {
                    $loc->checkStatus();
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSellEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $loc = $this->getParentInstance();
        if (!BimpObject::objectLoaded($loc)) {
            $errors[] = 'Location absent';
        }

        $eq = $this->getChildObject('equipment');
        if (!BimpObject::objectLoaded($eq)) {
            $errors[] = 'Equipement absent';
        }

        if (!count($errors)) {
            $errors = $this->updateField('status', self::STATUS_CANCELLED);

            if (!count($errors)) {
                $id_entrepot = (int) $loc->getData('id_entrepot');
                if ($id_entrepot) {
                    BimpObject::loadClass('bimpequipment', 'BE_Place');
                    $errors = $eq->moveToPlace(BE_Place::BE_PLACE_ENTREPOT, $id_entrepot, '', 'Mise en vente équipement');

                    if (!count($errors)) {
                        $sc = 'selectArticle($(), ' . $eq->id . ', \'equipment\');';

                        $loc->checkStatus();
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionEditStatus($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        $status = (int) BimpTools::getArrayValueFromPath($data, 'status', 0);

        if (empty($ids)) {
            $errors[] = 'Aucun équipement sélectionné';
        } else {
            $nOk = 0;
            $locs = array();

            foreach ($ids as $id) {
                $line = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocationLine', $id);

                if (!BimpObject::objectLoaded($line)) {
                    $errors[] = 'Ligne de location #' . $id . ' inexsitante';
                } else {
                    $id_loc = (int) $line->getData('id_location');
                    if (!in_array($id_loc, $locs)) {
                        $loc = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocation', $id_loc);
                        $loc->lines_mass_processing = true;
                        $locs[$id_loc] = $loc;
                    }

                    $line_err = $line->updateField('status', $status);

                    if (count($line_err)) {
                        $eq = $line->getChildObject('equipment');
                        $errors[] = BimpTools::getMsgFromArray($line_err, 'Echec de la mise à jour pour l\'équipement ' . (BimpObject::objectLoaded($eq) ? $eq->getRef() : '#' . $line->getData('id_equipment')));
                    } else {
                        $nOk++;
                    }
                }
            }

            if (!count($errors)) {
                $success = $nOk . ' équipement' . ($nOk > 1 ? 's' : '') . ' mis à jour avec succès';
                foreach ($locs as $id_loc => $loc) {
                    $loc->lines_mass_processing = false;
                    $loc->checkStatus();
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionEditDates($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        $date_from = BimpTools::getArrayValueFromPath($data, 'date_from', '');
        $date_to = BimpTools::getArrayValueFromPath($data, 'date_to', '');
        $fac_from = BimpTools::getArrayValueFromPath($data, 'fac_date_from', '');
        $fac_to = BimpTools::getArrayValueFromPath($data, 'fac_date_to', '');

        if (empty($ids)) {
            $errors[] = 'Aucun équipement sélectionné';
        } else {
            $nOk = 0;
            $locs = array();

            foreach ($ids as $id) {
                $line = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocationLine', $id);

                if (!BimpObject::objectLoaded($line)) {
                    $errors[] = 'Ligne de location #' . $id . ' inexsitante';
                } else {
                    $id_loc = (int) $line->getData('id_location');
                    if (!in_array($id_loc, $locs)) {
                        $loc = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocation', $id_loc);
                        $loc->lines_mass_processing = true;
                        $locs[$id_loc] = $loc;
                    }

                    if ($date_from) {
                        $line->set('date_from', $date_from);
                    }

                    if ($date_to) {
                        $line->set('date_to', $date_to);
                    }

                    if ($fac_from) {
                        $line->set('fac_date_from', $fac_from);
                    }

                    if ($fac_to) {
                        $line->set('fac_date_to', $fac_to);
                    }

                    $line_w = array();
                    $line_err = $line->update($line_w, true);

                    if (count($line_err)) {
                        $eq = $line->getChildObject('equipment');
                        $errors[] = BimpTools::getMsgFromArray($line_err, 'Echec de la mise à jour pour l\'équipement ' . (BimpObject::objectLoaded($eq) ? $eq->getRef() : '#' . $line->getData('id_equipment')));
                    } else {
                        $nOk++;
                    }
                }
            }

            if (!count($errors)) {
                $success = $nOk . ' équipement' . ($nOk > 1 ? 's' : '') . ' mis à jour avec succès';
                foreach ($locs as $id_loc => $loc) {
                    $loc->lines_mass_processing = false;
                    $loc->checkStatus();
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
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
                $errors[] = 'La date de fin est inférieure à la date de début';
            }

            $fac_from = $this->getData('fac_date_from');
            $fac_to = $this->getData('fac_date_to');

            if ($fac_to && $fac_from && $fac_to < $fac_from) {
                $errors[] = 'La date de fin de facturation est inférieure à la date de début de facturation';
            }

            if (!count($errors)) {
                if ($fac_from && $fac_from < $this->getData('date_from')) {
                    $errors[] = 'La date de début de facturation ne peut pas être inférieure à la date de début de location';
                }

                if ($fac_to && $fac_to > $this->getData('date_to')) {
                    $errors[] = 'La date de fin de facturation ne peut pas être supérieure à la date de fin de location';
                }
            }

            $forfait = $this->getChildObject('forfait');
            if (!BimpObject::objectLoaded($forfait)) {
                $errors[] = 'Le forfait #' . $this->getData('id_forfait') . ' n\'existe plus';
            } else {
                if (!count($errors) && (int) BimpCore::getConf('use_price_rules') && (!$this->isLoaded() ||
                        (int) $this->getData('id_forfait') !== (int) $this->getInitData('id_forfait') ||
                        $this->getData('date_from') !== $this->getInitData('date_from') ||
                        $this->getData('date_to') !== $this->getInitData('date_to') ||
                        $this->getData('fac_date_from') !== $this->getInitData('fac_date_from') ||
                        $this->getData('fac_date_to') !== $this->getInitData('fac_date_to'))) {

                    BimpObject::loadClass('bimpcore', 'Bimp_ProductPriceRule');
                    
                    $this->set('pu_ht', Bimp_ProductPriceRule::getBestPriceForProduct($forfait, array('qty' => $this->getQty())));
                }
                else{
                    $this->set('pu_ht', $forfait->getData('price'));
                }

                if (!(float) $this->getData('tva_tx')) {
                    $this->set('tva_tx', $forfait->getData('tva_tx'));
                }
            }

            $location = $this->getParentInstance();

            if (!$this->isLoaded() || (int) $this->getData('id_equipment') !== (int) $this->getInitData('id_equipment')) {
                $this->isEquipmentAvailable(0, '', '', $location->getData('id_entrepot'), $errors);
            }
        }
        
        $remiseTtc = BimpTools::getValue('remiseTtc', 0);
        if($remiseTtc > 0){
            $remisePourcent = $remiseTtc / (($this->getData('pu_ht') * (100+$this->getData('tva_tx')))/ 100)*100;
            $this->set('remise', $remisePourcent);
        }

        return $errors;
    }

    public function delete(&$warnings = [], $force_delete = false)
    {
        $loc = $this->getParentInstance();

        if (BimpObject::objectLoaded($loc) && (!isset($loc->isDeleting) || !$loc->isDeleting)) {
            $amounts = $this->getAmounts();
            if ($amounts['total_billed'] > 0) {
                return $this->updateField('status', self::STATUS_CANCELLED);
            }
        }

        return parent::delete($warnings, $force_delete);
    }
}

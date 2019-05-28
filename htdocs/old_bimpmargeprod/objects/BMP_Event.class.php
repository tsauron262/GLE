<?php

class BMP_Event extends BimpObject
{

    // Recettes: 
    public static $id_billets_2_1_type_montant = 21;
    public static $id_billets_5_5_type_montant = 52;
    public static $id_bar20_type_montant = 22;
    public static $id_bar55_type_montant = 23;
    public static $id_billets_location_montant = 57;
    // Frais: 
    public static $id_achats_bar_montant = 24;
    public static $id_frais_billets_materiels = 41;
    // Taxes SACEM / CNV:
    public static $id_sacem_bar_montant = 3;
    public static $id_sacem_billets_montant = 26;
    public static $id_sacem_autre_montant = 30;
    public static $id_sacem_groupe = 62;
    public static $id_cnv_montant = 5;
    public static $id_sacem_secu_montant = 29;
    // Calculs:
    public static $id_calc_sacem_billets = 2;
    public static $id_calc_cnv_billets = 1;
    public static $id_calc_frais_bar = 3;
    public static $id_calc_sacem_bar = 7;
    public static $id_calc_sacem_secu = 9;
    // Catégories:
    public static $id_taxes_category = 1;
    public static $id_bar_category = 15;
    public static $id_billets_category = 14;
    public static $id_coprods_category = 12;
    public static $montant_frais_billet_materiel = 0.2;
    public static $types = array(
        1 => 'Production',
        2 => 'Co-production',
        3 => 'Mise à disposition',
        4 => 'Location',
        5 => 'Autre'
    );
    public static $places = array(
        1 => 'Club',
        2 => 'Grande salle coupée',
        3 => 'Grande salle',
        4 => 'Double salle',
        5 => 'Club + grande salle',
        6 => 'Hors les murs'
    );
    public static $status = array(
        1 => array('label' => 'Edition prévisionnel', 'classes' => array('warning')),
        2 => array('label' => 'Edition montants réels', 'classes' => array('info')),
        3 => array('label' => 'Validé', 'classes' => array('success'))
    );
    public static $tarifs = array(
        'GUICHET', 'PREVENTE', 'TARIF REDUIT', 'FILGOOD', 'TARIF SPECIAL', 'TARIF SPECIAL LE FIL', 'TARIF CE', 'INVITATIONS'
    );
    public static $analytics = array(
        ''     => '',
        'PRO'  => 'PRO',
        'LOC'  => 'LOC',
        'STU'  => 'STU',
        'RES'  => 'RES',
        'JEU'  => 'JEU',
        'AUTR' => 'AUTR',
        'INT'  => 'INT',
        'NUM'  => 'NUM'
    );

    public function isEditable($force_edit = false)
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') === 3) {
                return 0;
            }
        }

        return 1;
    }

    public function isInPrevisionnelMode()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') === 1) {
                return 1;
            }
        }

        return 0;
    }

    public function isInReelMode()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') > 1) {
                return 1;
            }
        }

        return 0;
    }

    public function getTotalAmounts($status = false)
    {
        $return = array(
            'categories'     => array(),
            'total_frais'    => 0,
            'total_recettes' => 0,
            'solde'          => 0
        );

        if (!is_null($status)) {
            $return['status'] = array();
        }

        if (!$this->isLoaded()) {
            return $return;
        }


        $montants = $this->getChildrenObjects('montants');

        $coprods = $this->getChildrenObjects('coprods');

        foreach ($coprods as $cp) {
            $return['coprod_' . $cp->id] = 0;
        }

        foreach ($montants as $montant) {
            $m_status = (int) $montant->getData('status');

            $id_category = (int) $montant->getData('id_category_montant');
            if (!isset($return['categories'][$id_category])) {
                $return['categories'][$id_category] = array(
                    'total_frais'    => 0,
                    'total_recettes' => 0,
                    'solde'          => 0
                );
                foreach ($coprods as $cp) {
                    $return['categories'][$id_category]['coprod_' . $cp->id] = 0;
                }
            }

            if ($status) {
                if (!isset($return['status'][$m_status])) {
                    $return['status'][$m_status] = array(
                        'categories'     => array(),
                        'total_frais'    => 0,
                        'total_recettes' => 0,
                        'solde'          => 0
                    );

                    foreach ($coprods as $cp) {
                        $return['status'][$m_status]['coprod_' . $cp->id] = 0;
                    }
                }

                if (!isset($return['status']['categories'][$id_category])) {
                    $return['status'][$m_status]['categories'][$id_category] = array(
                        'total_frais'    => 0,
                        'total_recettes' => 0,
                        'solde'          => 0
                    );
                    foreach ($coprods as $cp) {
                        $return['status'][$m_status]['categories'][$id_category]['coprod_' . $cp->id] = 0;
                    }
                }
            }

            $value = (float) $montant->getData('amount');
            $type = (int) $montant->getData('type');

            switch ($type) {
                case 1:
                    $return['total_frais'] += $value;
                    $return['solde'] -= $value;
                    $return['categories'][$id_category]['total_frais'] += $value;
                    $return['categories'][$id_category]['solde'] -= $value;

                    if ($status) {
                        $return['status'][$m_status]['total_frais'] += $value;
                        $return['status'][$m_status]['solde'] -= $value;
                        $return['status'][$m_status]['categories'][$id_category]['total_frais'] += $value;
                        $return['status'][$m_status]['categories'][$id_category]['solde'] -= $value;
                    }
                    break;

                case 2:
                    $return['total_recettes'] += $value;
                    $return['categories'][$id_category]['total_recettes'] += $value;
                    $return['solde'] += $value;
                    $return['categories'][$id_category]['solde'] += $value;

                    if ($status) {
                        $return['status'][$m_status]['total_recettes'] += $value;
                        $return['status'][$m_status]['categories'][$id_category]['total_recettes'] += $value;
                        $return['status'][$m_status]['solde'] += $value;
                        $return['status'][$m_status]['categories'][$id_category]['solde'] += $value;
                    }
                    break;
            }


            foreach ($coprods as $cp) {
                $cp_part = (float) $montant->getCoProdPart($cp->id);
                if ($cp_part > 0) {
                    $cp_amount = (float) ($value * ($cp_part / 100));
                    switch ($type) {
                        case 1:
                            $return['categories'][$id_category]['coprod_' . $cp->id] -= $cp_amount;
                            $return['coprod_' . $cp->id] -= $cp_amount;

                            if ($status) {
                                $return['status'][$m_status]['categories'][$id_category]['coprod_' . $cp->id] -= $cp_amount;
                                $return['status'][$m_status]['coprod_' . $cp->id] -= $cp_amount;
                            }
                            break;

                        case 2:
                            $return['categories'][$id_category]['coprod_' . $cp->id] += $cp_amount;
                            $return['coprod_' . $cp->id] += $cp_amount;

                            if ($status) {
                                $return['status'][$m_status]['categories'][$id_category]['coprod_' . $cp->id] += $cp_amount;
                                $return['status'][$m_status]['coprod_' . $cp->id] += $cp_amount;
                            }
                            break;
                    }
                }
            }
        }
        return $return;
    }

    public function getBilletsIdTypeMontant()
    {
        if (!$this->isLoaded()) {
            return 0;
        }
        if ($this->getData('tva_billets') === 1) {
            return self::$id_billets_2_1_type_montant;
        }

        return self::$id_billets_5_5_type_montant;
    }

    public function GetFreeBilletsRatio()
    {
        if ($this->isLoaded()) {
            $tarifs = BimpObject::getInstance($this->module, 'BMP_Tarif');
            $freeTarifs = array();

            foreach ($tarifs->getList(array(
                'id_event' => (int) $this->id,
                'amount'   => 0
                    ), null, null, 'id', 'asc', 'array', array('id')) as $item) {
                $freeTarifs[] = $item['id'];
            }

            $nTotal = 0;
            $nFree = 0;

            if ((int) $this->getData('status') > 1) {
                foreach ($this->getChildrenObjects('billets') as $billet) {
                    $qty = $billet->getData('quantity');
                    $nTotal += $qty;
                    if (in_array((int) $billet->getData('id_tarif'), $freeTarifs)) {
                        $nFree += $qty;
                    }
                }
            } else {
                foreach ($tarifs->getList(array(
                    'id_event' => (int) $this->id
                        ), null, null, 'id', 'asc', 'array', array('id', 'previsionnel')) as $tarif) {
                    $nTotal += (int) $tarif['previsionnel'];
                    if (in_array((int) $tarif['id'], $freeTarifs)) {
                        $nFree += (int) $tarif['previsionnel'];
                    }
                }
            }

            if ($nTotal > 0) {
                return (float) $nFree / ($nTotal - $nFree   );
            }
        }

        return 0;
    }

    public function getCoprodsSoldes()
    {
        $return = array();

        if (!$this->isLoaded()) {
            return $return;
        }

        $montants = $this->getChildrenObjects('montants');
        $coprods = $this->getCoProds();

        foreach ($coprods as $id_cp => $cp_name) {
            $return[$id_cp] = 0;
        }

        foreach ($montants as $montant) {
            $amount = $montant->getData('amount');
            $type = (int) $montant->getData('type');
            $montant_id_coprod = (int) $montant->getData('id_coprod');

            foreach ($coprods as $id_cp => $cp_name) {
                $part = $montant->getCoProdPartAmount((int) $id_cp);

                switch ($type) {
                    case BMP_TypeMontant::BMP_TYPE_FRAIS:
                        if ((int) $id_cp === (int) $montant_id_coprod) {
                            $part -= $amount;
                        }
                        $return[(int) $id_cp] -= $part;
                        break;

                    case BMP_TypeMontant::BMP_TYPE_RECETTE:
                        if ((int) $id_cp === (int) $montant_id_coprod) {
                            $part -= $amount;
                        }
                        $return[(int) $id_cp] += $part;
                        break;
                }
            }
        }

        return $return;
    }

    public function getSoldes()
    {
        $return = array(
            'frais'    => 0,
            'recettes' => 0,
            'solde'    => 0
        );

        if (!$this->isLoaded()) {
            return $return;
        }

        $montants = $this->getChildrenObjects('montants');

        foreach ($montants as $montant) {
            $amount = $montant->getData('amount');
            $type = (int) $montant->getData('type');
            $montant_id_coprod = (int) $montant->getData('id_coprod');

            if (!$montant_id_coprod) {
                switch ($type) {
                    case BMP_TypeMontant::BMP_TYPE_FRAIS:
                        $return['frais'] += $amount;
                        $return['solde'] -= $amount;
                        break;

                    case BMP_TypeMontant::BMP_TYPE_RECETTE:
                        $return['recettes'] += $amount;
                        $return['solde'] += $amount;
                        break;
                }
            }
        }

        $cp_soldes = $this->getCoprodsSoldes();

        foreach ($cp_soldes as $id_cp => $cp_solde) {
            if ($cp_solde < 0) {
                $return['recettes'] -= $cp_solde;
                $return['solde'] -= $cp_solde;
            } else {
                $return['frais'] += $cp_solde;
                $return['solde'] -= $cp_solde;
            }
        }

        return $return;
    }

    public function getCoProds()
    {
        if (!isset($this->id) || !$this->id) {
            return array();
        }

        $objs = $this->getChildrenObjects('coprods');
        $coprods = array();
        if (!is_null($objs)) {
            foreach ($objs as $obj) {
                $soc = $obj->getChildObject('societe');
                $coprods[(int) $obj->id] = BimpObject::getInstanceNom($soc);
            }
        }

        return $coprods;
    }

    public function showCoprods()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('type') === 1) {
                return 0;
            }
            return 1;
        }

        return 0;
    }

    public function getGroupsArray()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $group = BimpObject::getInstance($this->module, 'BMP_EventGroup');
        $list = $group->getList(array(
            'id_event' => $this->id
        ));

        $groups = array(
            0 => ''
        );

        foreach ($list as $item) {
            $groups[(int) $item['id']] = $item['name'];
        }

        return $groups;
    }

    // Rendus HTML: 

    public function renderMontantsTotaux()
    {
        $coprods = array();
        foreach ($this->getChildrenObjects('coprods') as $cp) {
            $societe = $cp->getChildObject('societe');
            $coprods[] = array(
                'id'   => $cp->id,
                'name' => $societe->nom
            );
        }
        $colspan = 3;
        if (count($coprods)) {
            $colspan += 1 + count($coprods);
        }

        $status = array(
            array('code' => null, 'title' => 'généraux', 'id' => 'generals', 'tab' => 'Généraux'),
            array('code' => 2, 'title' => 'des montants confirmés', 'id' => 'confirmed', 'tab' => 'Confirmés'),
            array('code' => 1, 'title' => 'des montants à confirmer', 'id' => 'to_confirm', 'tab' => 'A confirmer'),
            array('code' => 3, 'title' => 'des montants optionnels', 'id' => 'optionals', 'tab' => 'Optionnels')
        );

        $tabs = array();

        $break_html = '<div class="row">';
        $break_html .= '<div class="col-sm-12 col-md-6 col-lg-4">';
        $break_html .= BimpRender::renderPanel('Indicateurs', $this->renderPrevisionnelBreaks(), '', array(
                    'type' => 'secondary',
                    'icon' => 'pie-chart'
        ));
        $break_html .= '</div>';
        $break_html .= '</div>';

        $all_amounts = $this->getTotalAmounts(true);

        foreach ($status as $s) {
            if (!is_null($s['code'])) {
                if (!isset($all_amounts['status'][$s['code']])) {
                    continue;
                }

                $amounts = $all_amounts['status'][$s['code']];
            } else {
                $amounts = $all_amounts;
            }

            $tab = array(
                'title'   => $s['tab'],
                'id'      => $s['id'],
                'content' => ''
            );

            $html = '';
            $html .= '<div class="row">';
            $html .= '<div class="col-sm-12 col-md-11 col-lg-9">';
            $html .= '<div class="objectFieldsTableContainer">';

            $html .= '<table class="objectFieldsTable foldable open">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th colspan="' . $colspan . '">';
            $html .= 'Totaux ' . $s['title'];
            $html .= '<span class="foldable-caret"></span>';
            $html .= '</th>';
            $html .= '</tr>';

            $html .= '<tr class="col_headers">';
            $html .= '<th>Solde</th>';
            $html .= '<th>Total Charges</th>';
            $html .= '<th>Total Recettes</th>';

            if (count($coprods)) {
                foreach ($coprods as $cp) {
                    $html .= '<th>Part ' . $cp['name'] . '</th>';
                }
                $html .= '<th>Part restante</th>';
            }

            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['solde'], 'EUR') . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_frais'], 'EUR') . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_recettes'], 'EUR') . '</td>';

            if (count($coprods)) {
                $rest = (float) $amounts['solde'];
                foreach ($coprods as $cp) {
                    $html .= '<td>';
                    if (isset($amounts['coprod_' . $cp['id']])) {
                        $html .= BimpTools::displayMoneyValue($amounts['coprod_' . $cp['id']], 'EUR');
                        $rest -= (float) $amounts['coprod_' . $cp['id']];
                    } else {
                        $html .= '<span class="warning">Inconnu</span>';
                    }
                    $html .= '</td>';
                }
                $html .= '<td>' . BimpTools::displayMoneyValue($rest, 'EUR') . '</td>';
            }

            $html .= '</tr>';
            $html .= '</tbody>';

            $html .= '</table>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';


            if (count($amounts['categories'])) {

                $content = '<div class="row">';

                $content .= '<div class="col-sm-12 col-md-6 col-lg-6">';
                $content .= '<div class="objectFieldsTableContainer">';
                $content .= '<table class="objectFieldsTable">';

                $content .= '<thead>';
                $content .= '<tr class="col_headers">';
                $content .= '<th colspan="2">';
                $content .= 'Charges';
                $content .= '</th>';
                $content .= '</tr>';
                $content .= '</thead>';

                $content .= '<tbody>';

                $category = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');

                $tatal_frais = 0;

                foreach ($amounts['categories'] as $id_category => $cat_amounts) {
                    if (in_array($id_category, array(self::$id_taxes_category, self::$id_bar_category))) {
                        continue;
                    }
                    $category->reset();
                    if ($category->fetch($id_category)) {
                        $cat_name = $category->getData('name');
                    } else {
                        $cat_name = 'Catégorie ' . $id_category;
                    }

                    $tatal_frais += $cat_amounts['total_frais'];

                    $content .= '<tr style="font-weight: bold; color: #' . $category->getData('color') . ';">';
                    $content .= '<td>' . $cat_name . '</td>';
                    $content .= '<td>' . BimpTools::displayMoneyValue($cat_amounts['total_frais'], 'EUR') . '</td>';
                    $content .= '</tr>';
                }

                $content .= '<tr style="font-weight: bold;">';
                $content .= '<td style="background-color: #EE7D00; color: #fff; text-align: right">Total</td>';
                $content .= '<td style="background-color: #DCDCDC">' . BimpTools::displayMoneyValue($tatal_frais, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '</tbody>';

                $content .= '</table>';
                $content .= '</div>';
                $content .= '</div>';

                $content .= '<div class="col-sm-12 col-md-6 col-lg-6">';
                $content .= '<div class="objectFieldsTableContainer">';
                $content .= '<table class="objectFieldsTable">';

                $content .= '<thead>';
                $content .= '<tr class="col_headers">';
                $content .= '<th colspan="2">';
                $content .= 'Recettes';
                $content .= '</th>';
                $content .= '</tr>';
                $content .= '</thead>';

                $content .= '<tbody>';

                $category = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');

                $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
                $calc_instance = BimpObject::getInstance($this->module, 'BMP_CalcMontant');

                $sacem_secu_rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_sacem_secu);

                $billets_ht_brut = 0;
                if ($eventMontant->find(array(
                            'id_event'   => (int) $this->id,
                            'id_montant' => $this->getBilletsIdTypeMontant()
                        ))) {
                    if (is_null($s['code']) || (int) $eventMontant->getData('status') === (int) $s['code']) {
                        $billets_ht_brut = $eventMontant->getData('amount');
                    }
                }


                $billets_ht_net = $billets_ht_brut;

                $rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_sacem_billets);
                if (!is_null($rate)) {
                    $sacem_billets = $billets_ht_brut * ($rate / 100);
                    $sacem_billets += ($sacem_billets * ($sacem_secu_rate / 100));
                } else {
                    $sacem_billets = 0;
                }

                $billets_ht_net -= $sacem_billets;

                $rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_cnv_billets);
                if (!is_null($rate)) {
                    $cnv_billets = $billets_ht_brut * ($rate / 100);
                } else {
                    $cnv_billets = 0;
                }

                $billets_ht_net -= $cnv_billets;

                $content .= '<tr>';
                $content .= '<td>Billetterie BRUT</td>';
                $content .= '<td>' . BimpTools::displayMoneyValue($billets_ht_brut, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr>';
                $content .= '<td>SACEM</td>';
                $content .= '<td>- ' . BimpTools::displayMoneyValue($sacem_billets, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr>';
                $content .= '<td>CNV</td>';
                $content .= '<td>- ' . BimpTools::displayMoneyValue($cnv_billets, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr style="font-weight: bold">';
                $content .= '<td>Billetterie NET</td>';
                $content .= '<td>' . BimpTools::displayMoneyValue($billets_ht_net, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr style="font-weight: bold">';
                $content .= '<td>Ratio invitations / total billets</td>';
                $content .= '<td>' . round($this->GetFreeBilletsRatio() * 100, 2) . ' %</td>';
                $content .= '</tr>';

                $content .= '<tr><td></td><td></td></tr>';

                $bar_ht_brut = 0;
                if ($eventMontant->find(array(
                            'id_event'   => (int) $this->id,
                            'id_montant' => (int) self::$id_bar55_type_montant
                        ))) {
                    if (is_null($s['code']) || (int) $s['code'] === (int) $eventMontant->getData('status')) {
                        $bar_ht_brut += (float) $eventMontant->getData('amount');
                    }
                }

                if ($eventMontant->find(array(
                            'id_event'   => (int) $this->id,
                            'id_montant' => (int) self::$id_bar20_type_montant
                        ))) {
                    if (is_null($s['code']) || (int) $s['code'] === (int) $eventMontant->getData('status')) {
                        $bar_ht_brut += (float) $eventMontant->getData('amount');
                    }
                }

                $sacem_bar = 0;
                if ($eventMontant->find(array(
                            'id_event'   => (int) $this->id,
                            'id_montant' => (int) self::$id_sacem_bar_montant
                        ))) {
                    $sacem_bar = (float) $eventMontant->getData('amount');
                    $sacem_bar += $sacem_bar * ($sacem_secu_rate / 100);
                }

                $frais_bar = 0;
                if ($eventMontant->find(array(
                            'id_event'   => (int) $this->id,
                            'id_montant' => (int) self::$id_achats_bar_montant
                        ))) {
                    if (is_null($s['code']) || (int) $s['code'] === (int) $eventMontant->getData('status')) {
                        $frais_bar = (float) $eventMontant->getData('amount');
                    }
                }

                $bar_ht_net = $bar_ht_brut - $sacem_bar - $frais_bar;

                $content .= '<tr>';
                $content .= '<td>Recettes bar BRUT</td>';
                $content .= '<td>' . BimpTools::displayMoneyValue($bar_ht_brut, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr>';
                $content .= '<td>SACEM bar</td>';
                $content .= '<td>- ' . BimpTools::displayMoneyValue($sacem_bar, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr>';
                $content .= '<td>Approvisionnement bar</td>';
                $content .= '<td>- ' . BimpTools::displayMoneyValue($frais_bar, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr style="font-weight: bold">';
                $content .= '<td>Recettes bar NET</td>';
                $content .= '<td>' . BimpTools::displayMoneyValue($bar_ht_net, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr><td></td><td></td></tr>';

                $total_autre_brut = 0;

                foreach ($amounts['categories'] as $id_cat => $cat_amounts) {
                    if (in_array($id_cat, array(
                                self::$id_bar_category,
                                self::$id_billets_category
                            ))) {
                        continue;
                    }

                    $total_autre_brut += $cat_amounts['total_recettes'];
                }

                $sacem_autre = 0;
                if ($eventMontant->find(array(
                            'id_event'   => (int) $this->id,
                            'id_montant' => (int) self::$id_sacem_autre_montant
                        ))) {
                    $sacem_autre = (float) $eventMontant->getData('amount');
                }
                if ($eventMontant->find(array(
                            'id_event'   => (int) $this->id,
                            'id_montant' => (int) self::$id_sacem_groupe
                        ))) {
                    $sacem_autre += (float) $eventMontant->getData('amount');
                }
                
                if($sacem_autre > 0)
                $sacem_autre += $sacem_autre * ($sacem_secu_rate / 100);

                $total_autre_net = $total_autre_brut - $sacem_autre;

                $content .= '<tr>';
                $content .= '<td>Autre recettes BRUT</td>';
                $content .= '<td>' . BimpTools::displayMoneyValue($total_autre_brut, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr>';
                $content .= '<td>SACEM autres recettes</td>';
                $content .= '<td>' . BimpTools::displayMoneyValue($sacem_autre, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '<tr style="font-weight: bold">';
                $content .= '<td>Autre recettes NET</td>';
                $content .= '<td>' . BimpTools::displayMoneyValue($total_autre_net, 'EUR') . '</td>';
                $content .= '</tr>';

                $tatal_recettes = $billets_ht_net + $bar_ht_net + $total_autre_net;

                $content .= '<tr style="font-weight: bold">';
                $content .= '<td style="background-color: #EE7D00; color: #fff; text-align: right">Total</td>';
                $content .= '<td style="background-color: #DCDCDC">' . BimpTools::displayMoneyValue($tatal_recettes, 'EUR') . '</td>';
                $content .= '</tr>';

                $content .= '</tbody>';

                $content .= '</table>';
                $content .= '</div>';
                $content .= '</div>';

                $content .= '</div>';

                $html .= '<div class="row">';
                $html .= '<div class="col-sm-12 col-md-10 col-lg-9">';
                $html .= BimpRender::renderPanel('Totaux ' . $s['title'] . ' par catégorie', $content, '', array(
                            'type' => 'secondary',
                            'icon' => 'euro'
                ));
                $html .= '</div>';
                $html .= '</div>';

//                $html .= '<div class="row">';
//                $html .= '<div class="col-sm-12 col-md-11 col-lg-9">';
//                $html .= '<div class="objectFieldsTableContainer">';
//
//                $html .= '<table class="objectFieldsTable foldable open">';
//
//                $html .= '<thead>';
//                $html .= '<tr>';
//                $html .= '<th colspan="' . ($colspan + 1 ) . '">';
//                $html .= 'Totaux ' . $s['title'] . ' par catégorie';
//                $html .= '<span class="foldable-caret"></span>';
//                $html .= '</th>';
//                $html .= '</tr>';
//
//                $html .= '<tr class="col_headers">';
//                $html .= '<th>Catégorie</th>';
//                $html .= '<th>Solde</th>';
//                $html .= '<th>Total Charges</th>';
//                $html .= '<th>Total Recette</th>';
//
//                if (count($coprods)) {
//                    foreach ($coprods as $cp) {
//                        $html .= '<th>Part ' . $cp['name'] . '</th>';
//                    }
//
//                    $html .= '<th>Part restante</th>';
//                }
//
//                $html .= '</tr>';
//                $html .= '</thead>';
//
//                $html .= '<tbody>';
//
//                $category = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');
//
//                foreach ($amounts['categories'] as $id_category => $cat_amounts) {
//                    $category->reset();
//                    if ($category->fetch($id_category)) {
//                        $cat_name = $category->getData('name');
//                    } else {
//                        $cat_name = 'Catégorie ' . $id_category;
//                    }
//
//                    $html .= '<tr style="font-weight: bold; color: #' . $category->getData('color') . ';">';
//                    $html .= '<th>' . $cat_name . '</th>';
//                    $html .= '<td>' . BimpTools::displayMoneyValue($cat_amounts['solde'], 'EUR') . '</td>';
//                    $html .= '<td>' . BimpTools::displayMoneyValue($cat_amounts['total_frais'], 'EUR') . '</td>';
//                    $html .= '<td>' . BimpTools::displayMoneyValue($cat_amounts['total_recettes'], 'EUR') . '</td>';
//
//                    if (count($coprods)) {
//                        $cat_rest = (float) $cat_amounts['solde'];
//
//                        foreach ($coprods as $cp) {
//                            $html .= '<td>';
//                            if (isset($cat_amounts['coprod_' . $cp['id']])) {
//                                $html .= BimpTools::displayMoneyValue($cat_amounts['coprod_' . $cp['id']], 'EUR');
//                                $cat_rest -= (float) $cat_amounts['coprod_' . $cp['id']];
//                            } else {
//                                $html .= '<span class="warning">Inconnu</span>';
//                            }
//                            $html .= '</td>';
//                        }
//                        $html .= '<td>' . BimpTools::displayMoneyValue($cat_rest, 'EUR') . '</td>';
//                    }
//                    $html .= '</tr>';
//                }
//
//                $html .= '</tbody>';
//                $html .= '</table>';
//
//                $html .= '</div>';
//                $html .= '</div>';
//                $html .= '</div>';
            }

            $html .= $break_html;
            $tab['content'] = $html;
            $tabs[] = $tab;
        }

        if (count($tabs)) {
            return BimpRender::renderNavTabs($tabs);
        }

        return BimpRender::renderAlerts('Aucun montant à calculer', 'warning');
    }

    public function renderSoldes()
    {
        $soldes = $this->getSoldes();

        $html = '';
        $html .= '<div class="row">';
        $html .= '<div class="col-sm-10 col-md-8 col-lg-6">';
        $html .= '<div class="objectFieldsTableContainer">';

        $html .= '<table class="objectFieldsTable foldable open">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="4">';
        $html .= 'Solde';
        $html .= '<span class="foldable-caret"></span>';
        $html .= '</th>';
        $html .= '</tr>';

        $html .= '<tr class="col_headers">';
        $html .= '<th>Solde</th>';
        $html .= '<th>Charges</th>';
        $html .= '<th>Recettes</th>';

        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<td>' . BimpTools::displayMoneyValue($soldes['solde'], 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($soldes['frais'], 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($soldes['recettes'], 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderFraisHotel()
    {
        $tabs = array();
        $montants = array(
            array('id_type_montant' => 8, 'title' => 'Frais d\'hôtel', 'label' => 'Hôtel', 'id' => 'frais_hotel'),
            array('id_type_montant' => 9, 'title' => 'Frais de repas (TVA à 5,5%)', 'label' => 'Repas (5,5%)', 'id' => 'repas_5_5'),
            array('id_type_montant' => 10, 'title' => 'Frais de repas (TVA à 20%)', 'label' => 'Repas (20%)', 'id' => 'repas_19_6'),
        );

        $eventMontant = BimpObject::getInstance('bimpmargeprod', 'BMP_EventMontant');

        foreach ($montants as $m) {
            $html = '';
            $eventMontant->reset();
            if ($eventMontant->find(array(
                        'id_event'   => (int) $this->id,
                        'id_montant' => $m['id_type_montant'],
                        'id_coprod'  => 0
                    ))) {
                $html = $eventMontant->renderChildrenList('details', 'default', true, $m['title'], 'far_file-alt');
            } else {
                $html .= BimpRender::renderAlerts($m['title'] . ': montant correspondant non trouvé');
            }
            $tabs[] = array(
                'id'      => $m['id'],
                'title'   => $m['label'],
                'content' => $html
            );
        }

        return BimpRender::renderNavTabs($tabs);
    }

    public function renderPrevisionnelBreaks()
    {
        $html = '';

        $tarifs = $this->getChildrenObjects('tarifs');

        $nTarifs = count($tarifs);

        $prix_moyen_ttc = 0;
        $prix_moyen_ht = 0;
        $prix_moyen_hors_loc_ht = 0;
        $prix_moyen_net = 0;

        $ca_bar_moyen_ttc = 0;
        $ca_bar_moyen_ht = 0;
        $ca_bar_moyen_net = 0;

        $ca_moyen_ttc = 0;
        $ca_moyen_net = 0;

        $break_billets = 0;
        $break_total = 0;

        $calc_instance = BimpObject::getInstance($this->module, 'BMP_CalcMontant');

        $debug = (int) BimpTools::getValue('db_mode', 0);

        if ($debug) {
            $html .= '<h3>Détails calculs: </h3>';
        }

        if ($nTarifs > 0) {
            $prix_total_ttc = 0;
            $prix_total_ht = 0;
            $prix_total_hors_loc_ht = 0;
//            $billets_loc_total_ttc = 0;
//            $billets_loc_total_ht = 0;

            $id_tax_billets = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) $this->getBilletsIdTypeMontant());
            $id_tax_billets_loc = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) self::$id_billets_location_montant);
            $id_tax_bar = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) self::$id_bar20_type_montant);

            $nbTarifs = 0;
            $nbBillets = 0;

            $sacem_secu_rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_sacem_secu);

            foreach ($tarifs as $tarif) {
                $prix_ttc = (float) $tarif->getData('amount');
                $loc_ttc = (float) $tarif->getData('droits_loc');
                $nbBillet = (float) $tarif->getData('previsionnel');

                $prix_ht = BimpTools::calculatePriceTaxEx($prix_ttc, BimpTools::getTaxeRateById($id_tax_billets));
                $loc_ht = BimpTools::calculatePriceTaxEx($loc_ttc, BimpTools::getTaxeRateById($id_tax_billets_loc));

                if ($prix_ht > 0) {
                    $prix_total_ttc += $prix_ttc * $nbBillet;
                    $prix_total_ht += $prix_ht * $nbBillet;
                    $nbTarifs++;
                    $nbBillets += $nbBillet;
                    $prix_total_hors_loc_ht += ($prix_ht - $loc_ht) * $nbBillet;
                }

//                $billets_loc_total_ttc += $loc_ttc;
//                $billets_loc_total_ht += $loc_ht;
            }

            if ($nbTarifs) {
                $prix_moyen_ttc = $prix_total_ttc / $nbBillets;
                $prix_moyen_ht = $prix_total_ht / $nbBillets;
                $prix_moyen_hors_loc_ht = $prix_total_hors_loc_ht / $nbBillets;
            }

            if ($debug) {
                $html .= '<h4>Bar: </h4>';
            }

            $ca_bar_moyen_ttc = (float) $this->getData('ca_moyen_bar');
            $ca_bar_moyen_ht = BimpTools::calculatePriceTaxEx($ca_bar_moyen_ttc, BimpTools::getTaxeRateById($id_tax_bar));
            $ca_bar_moyen_net = $ca_bar_moyen_ht;

            if ($debug) {
                $html .= 'CA Bar moyen ttc: ' . $ca_bar_moyen_ttc . '<br/>';
                $html .= 'CA Bar moyen ht: ' . $ca_bar_moyen_ht . '<br/>';
            }

            $rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_frais_bar);
            if (!is_null($rate)) {
                $frais_bar = ($ca_bar_moyen_ht * ($rate / 100));
                if ($debug) {
                    $html .= 'Charges Bar Moyen: ' . $frais_bar . ' (taux: ' . $rate . ')<br/>';
                }
            } else {
                $frais_bar = 0;
            }

            $rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_sacem_bar);
            if (!is_null($rate)) {
                $sacem_bar = $ca_bar_moyen_ht * ($rate / 100);
                if ($debug) {
                    $html .= 'SACEM Bar Moyen: ' . $sacem_bar . ' (taux: ' . $rate . ')<br/>';
                }
            } else {
                $sacem_bar = 0;
            }

            if (!is_null($sacem_secu_rate)) {
                $sacem_bar_secu = ($sacem_bar * ((float) $sacem_secu_rate / 100));
                if ($debug) {
                    $html .= 'SACEM bar sécu: ' . $sacem_bar_secu . ' (taux: ' . $sacem_secu_rate . ')<br/>';
                }
            }

            $ca_bar_moyen_net = $ca_bar_moyen_ht - $frais_bar - $sacem_bar - $sacem_bar_secu;
            if ($debug) {
                $html .= '<strong>CA Bar moyen net: ' . $ca_bar_moyen_net . '</strong><br/>';
            }
        }

        if ($prix_moyen_ht) {
            if ($debug) {
                $html .= '<h4>Billets: </h4>';
            }

            $sacem_billets = 0;
            $sacem_secu = 0;
            $cnv = 0;

            if ($debug) {
                $html .= 'Prix billet moyen HT: ' . $prix_moyen_ht . '<br/>';
                $html .= 'Prix billet moyen HT hors frais location: ' . $prix_moyen_hors_loc_ht . '<br/>';
            }

            $rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_sacem_billets);
            if (!is_null($rate)) {
                $sacem_billets = ($prix_moyen_hors_loc_ht * ((float) $rate / 100));
                if ($debug) {
                    $html .= 'SACEM billets: ' . $sacem_billets . ' (taux: ' . $rate . ')<br/>';
                }
            }

            if (!is_null($sacem_secu_rate)) {
                $sacem_secu = ($sacem_billets * ((float) $sacem_secu_rate / 100));
                if ($debug) {
                    $html .= 'SACEM sécu: ' . $sacem_secu . ' (taux: ' . $sacem_secu_rate . ')<br/>';
                }
            }

            $rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_cnv_billets);
            if (!is_null($rate)) {
                $cnv = ($prix_moyen_hors_loc_ht * ((float) $rate / 100));
                if ($debug) {
                    $html .= 'CNV: ' . $cnv . ' (taux: ' . $rate . ')<br/>';
                }
            }

            $prix_moyen_net = $prix_moyen_ht - $sacem_billets - $sacem_secu - $cnv;

            if ($debug) {
                $html .= '<strong>CA billet moyen net: ' . $prix_moyen_net . '</strong><br/><br/>';
                $html .= '<h4>Charges (hors bar et taxes): </h4>';
            }

            $total_frais_hors_bar = 0;
            $list_frais = $this->getChildrenObjects('frais');

            $excludes = array(
                self::$id_cnv_montant,
                self::$id_sacem_bar_montant,
                self::$id_sacem_billets_montant,
                self::$id_sacem_secu_montant,
                self::$id_achats_bar_montant
            );
            foreach ($list_frais as $montant) {
                if (!in_array((int) $montant->getData('id_montant'), $excludes)) {
                    $tm = $montant->getChildObject('type_montant');
                    $total_frais_hors_bar += (float) $montant->getData('amount');
                    if ($debug) {
                        $html .= ' - ' . $tm->getData('name') . ': ' . $montant->getData('amount') . '<br/>';
                    }
                }
            }

            $secu_sacem_autre = 0;
            $sacem_montant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
            if ($sacem_montant->find(array(
                        'id_event'   => (int) $this->id,
                        'id_montant' => (int) self::$id_sacem_secu_montant,
                        'id_coprod'  => 0
                    ))) {
                $rate = (float) $calc_instance->getSavedData('percent', self::$id_calc_sacem_secu);
                if (!is_null($rate)) {
                    $secu_sacem_autre = ((float) $sacem_montant->getData('amount') * ((float) $rate / 100));
                }
            }

            if ($debug) {
                $html .= '- sécu sacem (sur SACEM autre): ' . $secu_sacem_autre . '<br/>';
                $html .= 'Total charges: ' . $total_frais_hors_bar . '<br/><br/>';
                $html .= '<h4>Recettes (hors bar et vente de billets)</h4>';
            }

            $total_extra_recettes = 0;
            $list_recettes = $this->getChildrenObjects('recettes');
            foreach ($list_recettes as $montant) {
                if (!in_array((int) $montant->getData('id_category_montant'), array(14, 15))) {
                    $tm = $montant->getChildObject('type_montant');
                    $total_extra_recettes += (float) $montant->getData('amount');

                    if ($debug) {
                        $html .= ' - ' . $tm->getData('name') . ': ' . $montant->getData('amount') . '<br/>';
                    }
                }
            }

            $solde = $total_frais_hors_bar - $total_extra_recettes;

            if ($debug) {
                $html .= 'Total recettes: ' . $total_extra_recettes . '<br/><br/>';
                $html .= 'SOLDE: ' . $solde . '<br/><br/>';
            }

            $ca_moyen_net = $prix_moyen_net + $ca_bar_moyen_net;
            $ca_moyen_ttc = $prix_moyen_ttc + $ca_bar_moyen_ttc;
            $break_billets = $solde / $prix_moyen_net;
            $break_total = $solde / $ca_moyen_net;
        }

        $table_id = 'objectViewTable_' . rand(0, 999999);

        $html .= '<div class="objectFieldsTableContainer">';
        $html .= '<table class="' . $this->object_name . '_FieldsTable objectFieldsTable foldable open" id="' . $table_id . '">';

        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<th>Prix billet TOTAL TTC</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($prix_total_ttc, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Prix billet moyen TTC</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($prix_moyen_ttc, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Prix billet moyen HT</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($prix_moyen_ht, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Prix billet moyen net</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($prix_moyen_net, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Break Billetterie</th>';
        $html .= '<td><span class="danger">' . round($break_billets, 2) . ' billets</span></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Break toute recette</th>';
        $html .= '<td><span class="danger">' . round($break_total, 2) . ' billets</span></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>CA Moyen TTC / billet</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($ca_moyen_ttc, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>CA moyen net / billet</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($ca_moyen_net, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Ratio invitations / total billets</th>';
        $html .= '<td>' . round($this->GetFreeBilletsRatio() * 100, 2) . ' %</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    public function renderTotalBillets()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $tarifs = array();
        foreach ($this->getChildrenObjects('tarifs') as $tarif) {
            $tarifs[(int) $tarif->id] = $tarif;
        }

        $total_billets_ttc = 0;
        $total_loc_ttc = 0;
        $qty = 0;

        if ($this->getData('status') === 1) {
            foreach ($tarifs as $id_tarif => $tarif) {
                $qty += (int) $tarif->getData('previsionnel');
                $total = (float) $tarif->getData('amount') * (int) $tarif->getData('previsionnel');
                $loc = (float) $tarif->getData('droits_loc') * (int) $tarif->getData('previsionnel');
                $total_loc_ttc += $loc;
                $total_billets_ttc += ($total - $loc);
//                echo $total.', '.$loc . "\n";
            }
        } else {
            $children = $this->getChildrenObjects('billets');
            foreach ($children as $child) {
                if (!is_null($child) && is_a($child, 'BMP_EventBillets')) {
                    $qty += (int) $child->getData('quantity');
                    $total = $child->getTotal();
                    $loc = 0;

                    $id_tarif = (int) $child->getData('id_tarif');
                    if ($id_tarif && array_key_exists((int) $id_tarif, $tarifs)) {
                        $loc = (float) $tarifs[$id_tarif]->getData('droits_loc') * (int) $child->getData('quantity');
                        $total_loc_ttc += $loc;
                    }
                    $total_billets_ttc += ($total - $loc);
                }
            }
        }

//        echo $total_billets_ttc; exit;

        $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) $this->getBilletsIdTypeMontant());
        $total_billets_ht = BimpTools::calculatePriceTaxEx($total_billets_ttc, BimpTools::getTaxeRateById($id_tax));

        $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) self::$id_billets_location_montant);
        $total_loc_ht = BimpTools::calculatePriceTaxEx($total_loc_ttc, BimpTools::getTaxeRateById($id_tax));


        $html .= '<div class="objectFieldsTableContainer">';
        $html .= '<table class="objectFieldsTable foldable open">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="4">Total Billetterie</th>';
        $html .= '</tr>';
        $html .= '<tr class="col_headers">';
        $html .= '<th>Unités vendues</th>';
        $html .= '<th>Total HT hors droits de location</th>';
        $html .= '<th>Total droits de location HT</th>';
        $html .= '<th>Total HT</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td>' . $qty . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_billets_ht, 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_loc_ht, 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue(($total_loc_ht + $total_billets_ht), 'EUR') . '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    // Checks-up: 

    public function checkCoprodsParts()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $coprods = $this->getCoProds();
            $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
            $categs = $eventMontant->getAllCategoriesArray();

            $cpdp = BimpObject::getInstance($this->module, 'BMP_EventCoProdDefPart');
            $list = $cpdp->getList(array(
                'id_event' => (int) $this->id
            ));

            $categories = array();

            foreach ($list as $item) {
                if (!isset($categories[(int) $item['id_category_montant']])) {
                    $categories[(int) $item['id_category_montant']] = array();
                }
                $categories[(int) $item['id_category_montant']][(int) $item['id_event_coprod']] = (float) $item['part'];
            }

            foreach ($categories as $id_cat => $cat_coprods) {
                $total = 0;
                $total_wo_def = 0;

                $coprods_def = array();
                foreach ($cat_coprods as $id_coprod => $part) {
                    $def_part = (float) $this->db->getValue('bmp_event_coprod', 'default_part', '`id` = ' . $id_coprod);
                    if ($def_part !== $part) {
                        $total_wo_def += $part;
                    } else {
                        $coprods_def[] = $id_coprod;
                    }
                    $total += $part;
                }

                if ($total > 100) {
                    if ($total_wo_def < 100 && count($coprods_def)) {
                        $def_part = (100 - $total_wo_def) / count($coprods_def);
                        foreach ($coprods_def as $id_cp) {
                            $where = '`id_event_coprod` = ' . (int) $id_cp . ' AND `id_category_montant` = ' . (int) $id_cat;
                            $where .= ' AND `id_event` = ' . (int) $this->id;
                            if ($this->db->update('bmp_event_coprod_def_part', array(
                                        'part' => (float) $def_part
                                            ), $where) <= 0) {
                                $msg = 'Echec de la mise à jour de la part du Co-producteur "' . $coprods[$id_cp] . '" ';
                                $msg .= 'pour la catégorie "' . $categs[$id_cat] . '"';
                                $errors[] = $msg;
                            }
                        }
                    } else {
                        $diff = round((100 - $total) / count($cat_coprods), 2, PHP_ROUND_HALF_UP);
                        foreach ($cat_coprods as $id_cp => $part) {
                            $where = '`id_event_coprod` = ' . (int) $id_cp . ' AND `id_category_montant` = ' . (int) $id_cat;
                            $where .= ' AND `id_event` = ' . (int) $this->id;
                            if ($this->db->update('bmp_event_coprod_def_part', array(
                                        'part' => (float) ($part - $diff)
                                            ), $where) <= 0) {
                                $msg = 'Echec de la mise à jour de la part du Co-producteur "' . $coprods[$id_cp] . '" ';
                                $msg .= 'pour la catégorie "' . $categs[$id_cat] . '"';
                                $errors[] = $msg;
                            }
                        }
                    }
                }
            }

            $eventMontants = $this->getChildrenObjects('montants');
            foreach ($eventMontants as $em) {
                $em->checkCoprodsParts();
            }
        }

        return $errors;
    }

    // Calculs: 
    public function getBilletsTaxEx($billets_ttc)
    {
        if ((int) $this->getData('tva_billets') === 1) {
            $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) self::$id_billets_2_1_type_montant);
        } else {
            $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) self::$id_billets_5_5_type_montant);
        }

        return BimpTools::calculatePriceTaxEx($billets_ttc, BimpTools::getTaxeRateById($id_tax));
    }

    public function calcBilletsAmount()
    {
        if (!isset($this->id) || !$this->id) {
            return;
        }

        $total_billets_ttc = 0;
        $total_loc_ttc = 0;
        $frais_billets = 0;

        if ($this->getData('status') === 1) {
            $previsionnels = $this->getPrevisionnels();
            $total_billets_ttc = $previsionnels['billets_ttc'];
            $total_loc_ttc = $previsionnels['billets_loc_ttc'];
            $frais_billets = $previsionnels['total_frais_materiel'];
        } else {
            $children = $this->getChildrenObjects('billets');
            $tarifs = array();
            foreach ($this->getChildrenObjects('tarifs') as $tarif) {
                $tarifs[(int) $tarif->id] = $tarif;
            }

            $event_type = (int) $this->getData('type');

            foreach ($children as $child) {
                if (!is_null($child) && is_a($child, 'BMP_EventBillets')) {
                    $total = $child->getTotal();
                    $loc = 0;

                    $id_tarif = (int) $child->getData('id_tarif');
                    if ($id_tarif && array_key_exists((int) $id_tarif, $tarifs)) {
                        $loc = ((float) $tarifs[$id_tarif]->getData('droits_loc') * (int) $child->getData('quantity'));
                        $total_loc_ttc += $loc;
                        if (in_array($event_type, array(1, 2)) ||
                                (float) $tarifs[$id_tarif]->getData('amount') > 0) {
                            $frais_billets += self::$montant_frais_billet_materiel * (int) $child->getData('quantity');
                        }
                    }
                    $total_billets_ttc += ($total - $loc);
                }
            }
        }

        $eventMontant = BimpObject::getInstance('bimpmargeprod', 'BMP_EventMontant');
        if ($eventMontant->find(array(
                    'id_event'   => $this->id,
                    'id_montant' => $this->getBilletsIdTypeMontant(),
                    'id_coprod'  => 0
                ))) {

            $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) $this->getBilletsIdTypeMontant());
            $total_billets_ht = BimpTools::calculatePriceTaxEx($total_billets_ttc, BimpTools::getTaxeRateById($id_tax));

            $current_amount = (float) $eventMontant->getData('amount');

            if ($current_amount !== $total_billets_ht) {
                $eventMontant->set('amount', $total_billets_ht);
                $eventMontant->update();
            }
            $eventMontant->reset();
        }

        $id_type_montant = 0;
        switch ((int) $this->getData('tva_billets')) {
            case 1:
                $id_type_montant = self::$id_billets_5_5_type_montant;
                break;

            case 2:
                $id_type_montant = self::$id_billets_2_1_type_montant;
                break;
        }

        if ($id_type_montant) {
            if ($eventMontant->find(array(
                        'id_event'   => $this->id,
                        'id_montant' => $id_type_montant,
                        'id_coprod'  => 0
                    ))) {

                if ((float) $eventMontant->getData('amount')) {
                    $eventMontant->set('amount', 0);
                    $eventMontant->update();
                }
                $eventMontant->reset();
            }
        }

        if ($eventMontant->find(array(
                    'id_event'   => $this->id,
                    'id_montant' => self::$id_billets_location_montant,
                    'id_coprod'  => 0
                ))) {

            $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) self::$id_billets_location_montant);
            $total_loc_ht = BimpTools::calculatePriceTaxEx($total_loc_ttc, BimpTools::getTaxeRateById($id_tax));

            $current_amount = (float) $eventMontant->getData('amount');

            if ($current_amount !== $total_loc_ht) {
                $eventMontant->set('amount', $total_loc_ht);
                $eventMontant->update();
            }
            $eventMontant->reset();
        }

        if ($eventMontant->find(array(
                    'id_event'   => (int) $this->id,
                    'id_montant' => (int) self::$id_frais_billets_materiels,
                    'id_coprod'  => 0
                ))) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $frais_billets) {
                $eventMontant->set('amount', $frais_billets);
                $eventMontant->update();
            }
            $eventMontant->reset();
        }
    }

    public function calcMontant($id_type_montant, $id_coprod = null)
    {
        if (!$this->isLoaded()) {
            return;
        }

        $montant = BimpObject::getInstance($this->module, 'BMP_EventMontant');

        $filters = array(
            'id_event'   => $this->id,
            'id_montant' => (int) $id_type_montant
        );

        if (!is_null($id_coprod)) {
            $filters['id_coprod'] = (int) $id_coprod;
        }

        $list = $montant->getList($filters);

        $items = array();
        foreach ($this->getChildrenObjects('calcs_montants') as $eventCalcMontant) {
            $calcMontant = $eventCalcMontant->getChildObject('calc_montant');
            if ((int) $calcMontant->getData('id_target') === $id_type_montant) {
                $items[] = $eventCalcMontant;
            }
        }

        foreach ($list as $elem) {
            $montant->reset();
            if ($montant->fetch((int) $elem['id'])) {
                $id_coprod = (int) $montant->getData('id_coprod');
                if (count($items)) {
                    $amount_ht = 0;
                    foreach ($items as $item) {
                        if ((int) $item->getData('active')) {
                            $amount_ht += $item->getAmount($id_coprod);
                        }
                    }
                    if ((float) $amount_ht !== $montant->getData('amount')) {
                        $montant->set('amount', $amount_ht);
                        $montant->update();
                    }
                }
            }
        }
    }

    public function getPrevisionnels()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $montants = array(
            'total_billets_ttc'    => 0,
            'billets_ttc'          => 0,
            'billets_ht'           => 0,
            'billets_loc_ttc'      => 0,
            'billets_loc_ht'       => 0,
            'bar_ht'               => 0,
            'nb_total_billets'     => 0,
            'total_frais_materiel' => 0
        );

        $event_type = $this->getData('type');

        $children = $this->getChildrenObjects('tarifs');

        foreach ($children as $child) {
            if (!is_null($child) && is_a($child, 'BimpObject')) {
                $amount = (float) $child->getData('amount');
                $qty = (int) $child->getData('previsionnel');
                $loc = (float) $child->getData('droits_loc');
                $montants['total_billets_ttc'] += ($qty * $amount);
                $montants['billets_loc_ttc'] += ($qty * $loc);
                $montants['nb_total_billets'] += $qty;
                if (in_array($event_type, array(1, 2)) || $amount > 0) {
                    $montants['total_frais_materiel'] += (float) self::$montant_frais_billet_materiel * $qty;
                }
            }
        }

        $montants['billets_ttc'] = $montants['total_billets_ttc'] - $montants['billets_loc_ttc'];

        $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) $this->getBilletsIdTypeMontant());
        $montants['billets_ht'] = BimpTools::calculatePriceTaxEx($montants['billets_ttc'], BimpTools::getTaxeRateById($id_tax));

        $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) self::$id_billets_location_montant);
        $montants['billets_loc_ht'] = BimpTools::calculatePriceTaxEx($montants['billets_loc_ttc'], BimpTools::getTaxeRateById($id_tax));

        $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . (int) self::$id_bar20_type_montant);
        $ca_bar_taxe_rate = BimpTools::getTaxeRateById($id_tax);

        $ca_moyen_bar_ttc = (float) $this->getData('ca_moyen_bar');
        $ca_moyen_bar_ht = BimpTools::calculatePriceTaxEx((float) $ca_moyen_bar_ttc, $ca_bar_taxe_rate);

        $montants['bar_ttc'] += ((int) $montants['nb_total_billets'] * $ca_moyen_bar_ttc);
        $montants['bar_ht'] += ((int) $montants['nb_total_billets'] * $ca_moyen_bar_ht);

        return $montants;
    }

    public function calcPrevisionnels()
    {
        if (!$this->isLoaded()) {
            return;
        }

        if ((int) $this->getData('status') !== 1) {
            return;
        }

        $previsionnels = $this->getPrevisionnels();

        $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');

        if ($eventMontant->find(array(
                    'id_event'   => (int) $this->id,
                    'id_montant' => (int) $this->getBilletsIdTypeMontant(),
                    'id_coprod'  => 0
                ))) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $previsionnels['billets_ht']) {
                $eventMontant->set('amount', $previsionnels['billets_ht']);
                $eventMontant->update();
            }
            $eventMontant->reset();
        }

        $id_type_montant = 0;
        switch ((int) $this->getData('tva_billets')) {
            case 1:
                $id_type_montant = self::$id_billets_5_5_type_montant;
                break;

            case 2:
                $id_type_montant = self::$id_billets_2_1_type_montant;
                break;
        }

        if ($id_type_montant) {
            if ($eventMontant->find(array(
                        'id_event'   => $this->id,
                        'id_montant' => $id_type_montant,
                        'id_coprod'  => 0
                    ))) {

                if ((float) $eventMontant->getData('amount')) {
                    $eventMontant->set('amount', 0);
                    $eventMontant->update();
                }
                $eventMontant->reset();
            }
        }

        if ($eventMontant->find(array(
                    'id_event'   => (int) $this->id,
                    'id_montant' => (int) self::$id_billets_location_montant,
                    'id_coprod'  => 0
                ))) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $previsionnels['billets_loc_ht']) {
                $eventMontant->set('amount', $previsionnels['billets_loc_ht']);
                $eventMontant->update();
            }
            $eventMontant->reset();
        }

        if ($eventMontant->find(array(
                    'id_event'   => (int) $this->id,
                    'id_montant' => (int) self::$id_frais_billets_materiels,
                    'id_coprod'  => 0
                ))) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $previsionnels['total_frais_materiel']) {
                $eventMontant->set('amount', $previsionnels['total_frais_materiel']);
                $eventMontant->update();
            }
            $eventMontant->reset();
        }

        if ($eventMontant->find(array(
                    'id_event'   => (int) $this->id,
                    'id_montant' => (int) self::$id_bar20_type_montant,
                    'id_coprod'  => 0
                ))) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $previsionnels['bar_ht']) {
                $eventMontant->set('amount', $previsionnels['bar_ht']);
                $eventMontant->update();
            }
            $eventMontant->reset();
        }

        if ($eventMontant->find(array(
                    'id_event'   => (int) $this->id,
                    'id_montant' => (int) self::$id_bar55_type_montant,
                    'id_coprod'  => 0
                ))) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== 0) {
                $eventMontant->set('amount', 0);
                $eventMontant->update();
            }
        }
    }

    // Events: 

    public function onMontantChange(BMP_EventMontant $eventMontant)
    {
        if (!$this->isLoaded()) {
            return;
        }

        $id_type_montant = (int) $eventMontant->getData('id_montant');
        $id_coprod = (int) $eventMontant->getData('id_coprod');
//        $calcsMontants = $this->getChildrenObjects('calcs_montants');
        // Traitements des calculs automatiques dont le montant intervient dans le montant source:
        $sql = BimpTools::getSqlSelect(array('a.id_target'));
        $sql .= BimpTools::getSqlFrom('bmp_calc_montant', array(array(
                        'table' => 'bmp_event_calc_montant',
                        'on'    => 'b.id_calc_montant = a.id',
                        'alias' => 'b'
        )));

        $sql .= ' WHERE ' . (int) $id_type_montant . ' IN (';
        $sql .= 'SELECT c.id_type_montant FROM llx_bmp_calc_montant_type_montant c WHERE c.id_calc_montant = a.id';
        $sql .= ')';
        $sql .= ' AND a.active = 1 AND b.active = 1 AND b.id_event = ' . (int) $this->id;

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $this->calcMontant((int) $r['id_target'], $id_coprod);
            }
        }

//        foreach ($calcsMontants as $eventCalcMontant) {
//            if ((int) $eventCalcMontant->getData('active')) {
//                $calcMontant = $eventCalcMontant->getChildObject('calc_montant');
//                if (!is_null($calcMontant) && $calcMontant->isLoaded()) {
//                    $type_source = $calcMontant->getData('type_source');
//                    if (($type_source === 1) && (int) $calcMontant->getData('id_montant_source') === $id_type_montant) {
//                        $this->calcMontant($calcMontant->getData('id_target'), $id_coprod);
//                    }
//                }
//            }
//        }
////        
//        // Si le montant est inclus dans un total intermédiaire, 
//        // traitement des calculs automatiques dont ce total intermédiaire est la source: 
//        $totalInterInstance = BimpObject::getInstance($this->module, 'BMP_TotalInter');
//        $tm_instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
//        $ti_list = $totalInterInstance->getList();
//
//        foreach ($ti_list as $ti) {
//            $totalInterInstance->reset();
//            $totalInterInstance->fetch((int) $ti['id']);
//            if ($totalInterInstance->isLoaded()) {
//                $process = false;
//                $montantType = $eventMontant->getData('type');
//                if ($montantType === BMP_TypeMontant::BMP_TYPE_FRAIS &&
//                        (int) $totalInterInstance->getData('all_frais')) {
//                    $process = true;
//                } elseif ($montantType === BMP_TypeMontant::BMP_TYPE_RECETTE &&
//                        (int) $totalInterInstance->getData('all_recettes')) {
//                    $process = true;
//                }
//
//                if (!$process) {
//                    $id_categorie = $eventMontant->getData('id_category_montant');
//
//                    $sql = 'SELECT COUNT(DISTINCT a.id) as nb_rows';
//                    $sql .= BimpTools::getSqlFrom('bimp_objects_associations');
//                    $sql .= ' WHERE a.src_object_name = \'BMP_TotalInter\'';
//                    $sql .= ' AND a.src_id_object = ' . $ti['id'];
//                    $sql .= ' AND ((a.dest_object_name = \'BMP_CategorieMontant\' AND a.dest_id_object = ' . $id_categorie . ')';
//                    $sql .= ' OR (a.dest_object_name = \'BMP_TypeMontant\' AND a.dest_id_object = ' . $id_type_montant . '))';
//
//                    $result = $this->db->execute($sql);
//                    if ($result > 0) {
//                        $obj = $this->db->db->fetch_object($result);
//                        if ((int) $obj->nb_rows > 0) {
//                            $process = true;
//                        }
//                    }
//                }
//
//                if ($process) {
//                    foreach ($calcsMontants as $eventCalcMontant) {
//                        if ((int) $eventCalcMontant->getData('active')) {
//                            $calcMontant = $eventCalcMontant->getChildObject('calc_montant');
//                            if (!is_null($calcMontant) && $calcMontant->isLoaded()) {
//                                $type_source = $calcMontant->getData('type_source');
//                                if (($type_source === 2) && (int) $calcMontant->getData('id_total_source') === (int) $ti['id']) {
//                                    $this->calcMontant($calcMontant->getData('id_target'), $id_coprod);
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
    }

    public function onChildSave(BimpObject $child)
    {
        if (in_array($child->object_name, array('BMP_EventBillets', 'BMP_Tarif'))) {
            if ((int) $this->getData('status') === 1) {
                $this->calcPrevisionnels();
            } else {
                $this->calcBilletsAmount();
            }
        } elseif ($child->object_name === 'BMP_EventMontant') {
            $this->onMontantChange($child);
        } elseif ($child->object_name === 'BMP_EventCalcMontant') {
            $calcMontant = $child->getChildObject('calc_montant');
            if (!is_null($calcMontant) && $calcMontant->isLoaded()) {
                $this->calcMontant((int) $calcMontant->getData('id_target'));
            }
        }
    }

    // Overrides:

    public function create()
    {
        $errors = parent::create();

        if (isset($this->id) && $this->id) {
            // Création des montants frais/recettes obligatoires:
            $typeMontant = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
            $list = $typeMontant->getList(array(
                'required' => 1
            ));

            $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
            foreach ($list as $item) {
                $eventMontant->reset();
                $eventMontant->validateArray(array(
                    'id_event'            => (int) $this->id,
                    'id_category_montant' => (int) $item['id_category'],
                    'id_montant'          => (int) $item['id'],
                    'amount'              => 0,
                    'status'              => 1,
                    'type'                => $item['type'],
                    'id_coprod'           => 0
                ));
                $errors = array_merge($errors, $eventMontant->create());
            }
            unset($eventMontant);
            unset($typeMontant);

            // Création des Calculs Automatiques oblgatoires:
            $calcMontant = BimpObject::getInstance($this->module, 'BMP_CalcMontant');
            $list = $calcMontant->getList(array(
                'required' => 1,
                'active'   => 1
            ));

            $eventCalcMontant = BimpObject::getInstance($this->module, 'BMP_EventCalcMontant');
            foreach ($list as $item) {
                $eventCalcMontant->reset();
                $eventCalcMontant->validateArray(array(
                    'id_event'        => (int) $this->id,
                    'id_calc_montant' => (int) $item['id'],
                    'percent'         => (float) $item['percent'],
                    'active'          => 1
                ));
                $errors = array_merge($errors, $eventCalcMontant->create());
            }

            // Création des tarifs standards:
            $tarif = BimpObject::getInstance($this->module, 'BMP_Tarif');

            foreach (self::$tarifs as $name) {
                $tarif->reset();
                $tarif->validateArray(array(
                    'id_event'     => (int) $this->id,
                    'name'         => $name,
                    'amount'       => 0,
                    'previsionnel' => 0,
                    'ca_moyen_bar' => 0
                ));
                $errors = array_merge($errors, $tarif->create());
            }
        }

        return $errors;
    }

    public function update()
    {
        $current_status = (int) $this->getSavedData('status');

        $errors = parent::update();

        if (!count($errors)) {
            $new_status = (int) $this->getData('status');

            $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');

            if ($new_status === 1) {
                if ($current_status !== $new_status) {
                    $amount_20 = 0;
                    $amount_55 = 0;

                    if ($eventMontant->find(array(
                                'id_event'   => $this->id,
                                'id_montant' => self::$id_bar20_type_montant,
                                'id_coprod'  => 0
                            ))) {
                        $amount_20 = (float) $eventMontant->getData('amount');
                    }

                    if ($eventMontant->find(array(
                                'id_event'   => $this->id,
                                'id_montant' => self::$id_bar55_type_montant,
                                'id_coprod'  => 0
                            ))) {
                        $amount_55 = (float) $eventMontant->getData('amount');
                    }

                    $this->db->update($this->getTable(), array(
                        'bar_20_save' => $amount_20,
                        'bar_55_save' => $amount_55
                            ), '`id` = ' . (int) $this->id);
                }
                $this->calcPrevisionnels();
            } else {
                $this->calcBilletsAmount();

                if ($current_status === 1) {
                    if ($eventMontant->find(array(
                                'id_event'   => $this->id,
                                'id_montant' => self::$id_bar20_type_montant,
                                'id_coprod'  => 0
                            ))) {
                        $this->getSavedData('bar_20_save');
                        $eventMontant->set('amount', (float) $this->getSavedData('bar_20_save'));
                        $eventMontant->update();
                        $eventMontant->reset();
                    }

                    if ($eventMontant->find(array(
                                'id_event'   => $this->id,
                                'id_montant' => self::$id_bar55_type_montant,
                                'id_coprod'  => 0
                            ))) {
                        $eventMontant->set('amount', (float) $this->getSavedData('bar_55_save'));
                        $eventMontant->update();
                        $eventMontant->reset();
                    }
                }
            }
        }
        return $errors;
    }
}

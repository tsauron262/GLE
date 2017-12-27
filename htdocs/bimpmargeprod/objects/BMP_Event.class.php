<?php

class BMP_Event extends BimpObject
{

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
        4 => 'Extérieur'
    );
    public static $status = array(
        1 => array('label' => 'Brouillon', 'classes' => array('warning')),
        2 => array('label' => 'Validé', 'classes' => array('success'))
    );

    public function getTotalAmounts($type, $status = null)
    {
        $return = array(
            'categories' => array(),
            'total'      => 0
        );

        if (!isset($this->id) || !$this->id || !$type) {
            return $return;
        }

        $amounts = $this->getChildrenObjects($type);
        $coprods = $this->getChildrenObjects('coprods');
        foreach ($coprods as $cp) {
            $return['coprod_' . $cp->id] = 0;
        }

        foreach ($amounts as $a) {
            if (!is_null($status)) {
                if ((int) $a->getData('status') !== (int) $status) {
                    continue;
                }
            }
            $value = (float) $a->getData('amount');
            $return['total'] += $value;

            $id_category = (int) $a->getData('id_category_montant');
            if (!isset($return['categories'][$id_category])) {
                $return['categories'][$id_category] = array(
                    'total' => 0,
                );
                foreach ($coprods as $cp) {
                    $return['categories'][$id_category]['coprod_' . $cp->id] = 0;
                }
            }

            $return['categories'][$id_category]['total'] += $value;
            foreach ($coprods as $cp) {
                $cp_part = (float) $a->getCoProdPart($cp->id);
                if ($cp_part > 0) {
                    $cp_amount = (float) ($value * ($cp_part / 100));
                    $return['categories'][$id_category]['coprod_' . $cp->id] += $cp_amount;
                    $return['coprod_' . $cp->id] += $cp_amount;
                }
            }
        }

        return $return;
    }

    public function renderMontantsTotaux($type)
    {
        $coprods = array();
        foreach ($this->getChildrenObjects('coprods') as $cp) {
            $societe = $cp->getChildObject('societe');
            $coprods[] = array(
                'id'   => $cp->id,
                'name' => $societe->nom
            );
        }
        $colspan = count($coprods) + 3;

        $status = array(
            array('code' => null, 'title' => 'généraux', 'id' => 'generals', 'tab' => 'Généraux'),
            array('code' => 2, 'title' => 'des montants confirmés', 'id' => 'confirmed', 'tab' => 'Confirmés'),
            array('code' => 1, 'title' => 'des montants à confirmer', 'id' => 'to_confirm', 'tab' => 'A confirmer'),
            array('code' => 3, 'title' => 'des montants optionnels', 'id' => 'optionals', 'tab' => 'Optionnels')
        );

        $type_object = '';
        switch ($type) {
            case 1:
                $type_object = 'frais';
                $amounts = $this->getTotalAmounts('frais');
                break;

            case 2:
                $type_object = 'recettes';

                break;

            default:
                return '';
        }

        $tabs = array();

        foreach ($status as $s) {
            $amounts = $this->getTotalAmounts($type_object, $s['code']);

            if (!$amounts['total']) {
                continue;
            }

            $tab = array(
                'title'   => $s['tab'],
                'id'      => $s['id'],
                'content' => ''
            );

            $html = '';
            $html .= '<div class="row">';
            $html .= '<div class="col-sm-12 col-md-6">';
            $html .= '<div class="objectViewTableContainer">';

            $html .= '<table class="objectViewtable foldable open">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th colspan="' . $colspan . '">';
            $html .= 'Totaux ' . $s['title'];
            $html .= '<span class="foldable-caret"></span>';
            $html .= '</th>';
            $html .= '</tr>';

            $html .= '<tr class="col_headers">';
            $html .= '<th>Total</th>';

            foreach ($coprods as $cp) {
                $html .= '<th>Part ' . $cp['name'] . '</th>';
            }

            $html .= '<th>Part restante</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total'], 'EUR') . '</td>';
            $rest = (float) $amounts['total'];
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
            $html .= '</tr>';
            $html .= '</tbody>';

            $html .= '</table>';
            $html .= '</div>';
            $html .= '</div>';


            if (count($amounts['categories'])) {
                $html .= '<div class="col-sm-12 col-md-6">';
                $html .= '<div class="objectViewTableContainer">';

                $html .= '<table class="objectViewtable foldable open">';

                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th colspan="' . ($colspan + 1 ) . '">';
                $html .= 'Totaux ' . $s['title'] . ' par catégorie';
                $html .= '<span class="foldable-caret"></span>';
                $html .= '</th>';
                $html .= '</tr>';

                $html .= '<tr class="col_headers">';
                $html .= '<th>Catégorie</th>';
                $html .= '<th>Total</th>';

                foreach ($coprods as $cp) {
                    $html .= '<th>Part ' . $cp['name'] . '</th>';
                }

                $html .= '<th>Part restante</th>';

                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                $category = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');

                foreach ($amounts['categories'] as $id_category => $cat_amounts) {
                    $category->reset();
                    if ($category->fetch($id_category)) {
                        $cat_name = $category->getData('name');
                    } else {
                        $cat_name = 'Catégorie ' . $id_category;
                    }

                    $html .= '<tr>';
                    $html .= '<th>' . $cat_name . '</th>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($cat_amounts['total'], 'EUR') . '</td>';
                    $cat_rest = (float) $cat_amounts['total'];

                    foreach ($coprods as $cp) {
                        $html .= '<td>';
                        if (isset($cat_amounts['coprod_' . $cp['id']])) {
                            $html .= BimpTools::displayMoneyValue($cat_amounts['coprod_' . $cp['id']], 'EUR');
                            $cat_rest -= (float) $cat_amounts['coprod_' . $cp['id']];
                        } else {
                            $html .= '<span class="warning">Inconnu</span>';
                        }
                        $html .= '</td>';
                    }
                    $html .= '<td>' . BimpTools::displayMoneyValue($cat_rest, 'EUR') . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';

                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $tab['content'] = $html;
            $tabs[] = $tab;
        }

        if (count($tabs)) {
            return BimpRender::renderNavTabs($tabs);
        }

        return BimpRender::renderAlerts('Aucun montant à calculer', 'warning');
    }

    public function renderFraisHotel()
    {
        $tabs = array();
        $montants = array(
            array('id_type_montant' => 8, 'title' => 'Frais d\'hôtel', 'label' => 'Hôtel', 'id' => 'frais_hotel'),
            array('id_type_montant' => 9, 'title' => 'Frais de repas (TVA à 5,5%)', 'label' => 'Repas (5,5%)', 'id' => 'repas_5_5'),
            array('id_type_montant' => 10, 'title' => 'Frais de repas (TVA à 19,6%)', 'label' => 'Repas (19,6%)', 'id' => 'repas_19_6'),
        );

        $eventMontant = BimpObject::getInstance('bimpmargeprod', 'BMP_EventMontant');

        foreach ($montants as $m) {
            $html = '';
            $eventMontant->reset();
            if ($eventMontant->find(array(
                        'id_event'   => (int) $this->id,
                        'id_montant' => $m['id_type_montant']
                    ))) {
                $html = $eventMontant->renderChildrenList('details', 'default', true, $m['title'], 'file-text-o');
            } else {
                $html .= BimpRender::renderAlerts($m['title'] . ': montant correspondant non trouvé');
            }
            $tabs[] = array(
                'id' => $m['id'],
                'title' => $m['label'],
                'content' => $html
            );
        }
        
        return BimpRender::renderNavTabs($tabs);
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
                $coprods[(int) $obj->id] = $obj->displayData('id_soc', 'nom');
            }
        }
        return $coprods;
    }

    public function create()
    {
        $errors = parent::create();

        if (isset($this->id) && $this->id) {
            // Création des montants frais/recettes obligatoires:
            $typeMontant = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');
            $list = $typeMontant->getList(array(
                'required' => 1
            ));

            $eventMontant = BimpObject::getInstance('bimpmargeprod', 'BMP_EventMontant');
            foreach ($list as $item) {
                $eventMontant->reset();
                $eventMontant->validateArray(array(
                    'id_event'            => (int) $this->id,
                    'id_category_montant' => (int) $item['id_category'],
                    'id_montant'          => (int) $item['id'],
                    'amount'              => 0,
                    'status'              => 1,
                    'type'                => $item['type']
                ));
                $errors = array_merge($errors, $eventMontant->create());
            }
            unset($eventMontant);
            unset($typeMontant);
        }

        return $errors;
    }
}

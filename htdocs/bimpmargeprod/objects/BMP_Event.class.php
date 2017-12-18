<?php

class BMP_Event extends BimpObject
{

    public function getTotalAmounts($type)
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
        $html = '';

        $coprods = array();
        foreach ($this->getChildrenObjects('coprods') as $cp) {
            $societe = $cp->getChildObject('societe');
            $coprods[] = array(
                'id'   => $cp->id,
                'name' => $societe->nom
            );
        }
        $colspan = count($coprods) + 3;

        switch ($type) {
            case 1:
                $amounts = $this->getTotalAmounts('frais');
                break;
            
            case 2:
                $amounts = $this->getTotalAmounts('recettes');
                break;
            
            default: 
                $amounts = array();
        }
        
        $html .= '<div class="col-sm-12 col-md-6">';
        $html .= '<div class="objectViewTableContainer">';

        $html .= '<table class="objectViewtable" style="text-align: center">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="' . $colspan . '">';
        $html .= 'Totaux généraux';
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

            $html .= '<table class="objectViewtable">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th colspan="' . ($colspan + 1 ) . '">';
            $html .= 'Totaux par catégorie';
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

        return $html;
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
}

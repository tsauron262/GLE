<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/controllers/gsxController.php';

class stockOrderController extends gsxController
{

    public function gsx_stockOrderSearchParts($params)
    {
        $html = '';
        $errors = array();

        $id_stock_order = (int) BimpTools::getArrayValueFromPath($params, 'id_stock_order', 0);
        if (!$id_stock_order) {
            $errors[] = 'ID de la commande de stock absent';
        } else {
            $order = BimpCache::getBimpObjectInstance('bimpapple', 'StockOrder', $id_stock_order);
            if (!BimpObject::objectLoaded($order)) {
                $errors[] = 'La commande de stock #' . $id_stock_order . ' n\'existe plus';
            }
        }

        $search_type = BimpTools::getArrayValueFromPath($params, 'search_type', '');
        if (!$search_type) {
            $errors[] = 'Type de recherche absent';
        }

        $search_terms = BimpTools::getArrayValueFromPath($params, 'search_terms', '');
        if (!$search_terms) {
            $errors[] = 'Termes de recherche absents';
        }

        if (!count($errors)) {
            if ($this->gsx_v2->logged) {
                $result = $this->gsx_v2->stockingOrderPartsSummary(array(
                    $search_type => $search_terms
                ));

                if (is_array($result)) {
                    if (empty($result)) {
                        $html .= BimpRender::renderAlerts('Aucun composant trouvé', 'warning');
                    } else {
                        $headers = array(
                            'ref'     => 'Ref.',
                            'desc'    => 'Désignation',
                            'qty'     => 'Qté à commander',
                            'price'     => 'Prix Stock',
                            'buttons' => ''
                        );

                        $i = 0;
                        foreach ($result as $part) {
                            $i++;

                            $onclick = 'StockOrder.addPart($(this), ' . $id_stock_order . ', \'' . htmlentities($part['partNumber']) . '\', \'' . htmlentities($part['description']) . '\', \'' . htmlentities($part['stockPrice']) . '\')';
                            $button_html = '<span class="btn btn-default" onclick="' . $onclick . '">';
                            $button_html .= 'Ajouter' . BimpRender::renderIcon('fas_plus-circle', 'iconRight');
                            $button_html .= '</span>';

                            $rows[] = array(
                                'ref'     => $part['partNumber'],
                                'desc'    => $part['description'],
                                'qty'     => BimpInput::renderInput('qty', 'part_' . $i . '_qty', 1, array(
                                    'extra_class' => 'part_qty'
                                )),
                                'price'   => $part['stockPrice'].' €',
                                'buttons' => $button_html
                            );
                        }

                        $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                                    'searchable' => 1
                        ));
                    }
                } else {
                    $errors = $this->gsx_v2->getErrors();
                }
            }
        }

        return array(
            'errors' => $errors,
            'html'   => $html
        );
    }
}

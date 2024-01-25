<?php

class indexController extends BimpController
{

    public function renderAbonnementsTab($params = array())
    {
        BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');
        return BCT_Contrat::renderAbonnementsTabs($params);
    }

    public function renderModifiedBundlesTab()
    {
        $html = '';

        $bdb = BimpCache::getBdb();
        $bundles = array();
        $bundles_prods = array();

        $rows = $bdb->getRows('product_association', '1', null, 'array', array(
            'fk_product_pere as id_bundle',
            'fk_product_fils as id_product',
            'qty'
        ));

        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (!isset($bundles[(int) $r['id_bundle']])) {
                    $bundles[(int) $r['id_bundle']] = array();
                    $bundles_prods[] = (int) $r['id_bundle'];
                }

                $bundles[(int) $r['id_bundle']][(int) $r['id_product']] = (float) $r['qty'];
            }
        }

        $html .= '<h3>Bundles contrats modifiés</h3>';

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Contrat</th>';
        $html .= '<th>Bundle</th>';
        $html .= '<th>Erreur(s)</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        foreach (BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
            'statut'     => array(0, 4),
            'fk_product' => $bundles_prods
        )) as $line) {
            $errors = array();
            $bundle_units = $line->getNbUnits();
            if (!$bundle_units) {
                continue;
            }

            $contrat = $line->getParentInstance();

            if (!BimpObject::objectLoaded($contrat)) {
                continue;
            }

            $bundle = $line->getChildObject('product');

            if (!BimpObject::objectLoaded($bundle) || !isset($bundles[$bundle->id])) {
                $errors[] = 'Le bundle #' . $line->getData('fk_product') . ' n\'existe plus';
            } else {
                $line_prods = array();

                foreach (BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                    'id_parent_line' => $line->id,
                    'fk_product'     => array(
                        'operator' => '>',
                        'value'    => 0
                    )
                )) as $sub_line) {
                    $sub_line_errors = array();
                    $id_prod = (int) $sub_line->getData('fk_product');
                    $line_prods[] = $id_prod;

                    if (!isset($bundles[$bundle->id][$id_prod])) {
                        $sub_line_errors[] = 'Ce produit ne fait plus partie du bundle';
                    } else {
                        $sub_line_units = $sub_line->getNbUnits();

                        if ($sub_line_units / $bundle_units != $bundles[$bundle->id][$id_prod]) {
                            $msg = 'Le nombre d\'unités ne correspond pas.<br/>';
                            $msg .= 'Attendu : ' . ($bundles[$bundle->id][$id_prod] * $bundle_units) . '.<br/>';
                            $msg .= 'Enregistré dans le contrat  : ' . $sub_line_units;
                            $sub_line_errors[] = $msg;
                        }
                    }

                    if (!empty($sub_line_errors)) {
                        $sub_prod = $sub_line->getChildObject('product');
                        $errors[] = BimpTools::getMsgFromArray($sub_line_errors, 'Ligne n° ' . $sub_line->getData('rang') . ' (produit ' . (BimpObject::objectLoaded($sub_prod) ? $sub_prod->getLink() : '#' . $id_prod) . ')');
                    }
                }

                foreach ($bundles[$bundle->id] as $id_child_prod => $child_prod_qty) {
                    if (!in_array($id_child_prod, $line_prods)) {
                        $child_prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_child_prod);
                        $errors[] = 'Produit absent dans le bundle du contrat : ' . $child_prod->getLink();
                    }
                }

                if (!empty($errors)) {
                    $html .= '<tr>';
                    $html .= '<td>' . $contrat->getLink() . '</td>';
                    $html .= '<td>' . $bundle->getLink() . '<br/>Ligne n° ' . $line->getData('rang') . '</td>';
                    $html .= '<td>';
                    $html .= BimpRender::renderAlerts($errors);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}

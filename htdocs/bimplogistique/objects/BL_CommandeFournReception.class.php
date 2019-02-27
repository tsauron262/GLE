<?php

class BL_CommandeFournReception extends BimpObject
{

    // Getters booléens: 

    public function isFieldEditable($field)
    {
        if ($field === 'id_entrepot' && $this->isLoaded()) {
            return 0;
        }

        return parent::isFieldEditable($field);
    }

    // Getters valeurs: 

    public function getName($with_generic = true)
    {
        return 'Réception #' . $this->getData('num_reception');
    }

    // Getters config: 

    public function getListsExtraBtn()
    {
        return array();
    }

    public function getCommandesFournListbulkActions()
    {
        return array();
    }

    // Rendus HTML: 

    public function renderCommandeFournLinesForm()
    {
        $html = '';

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $html .= BimpRender::renderAlerts('ID de la commande fournisseur absent');
        } else {
            $lines = array();
            ;
            foreach ($commande->getChildrenObjects('lines') as $line) {
                if ((int) $line->getData('type') === Bimp_CommandeFournLine::LINE_PRODUCT) {
                    $product = $line->getProduct();
                    if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                        if ((float) $line->qty > (float) $line->getReceivedQty()) {
                            $lines[] = $line;
                        }
                    }
                }
            }

            if (!count($lines)) {
                $html .= BimpRender::renderAlerts('Il n\'y a aucune unité à réceptionner pour cette commande fournisseur', 'warning');
            } else {
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Désignation</th>';
                $html .= '<th>Qté</th>';
                $html .= '<th>Prix d\'achat</th>';
                $html .= '<th>Tx TVA</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody class="receptions_rows">';
                foreach ($lines as $line) {
                    $tpl = $line->renderReceptionFormRowTpl(false);
                    $tpl = str_replace('receptionidx', '1', $tpl);
                    $tpl = str_replace('linetotalmaxinputclass', 'line_' . $line->id . '_reception_max', $tpl);
                    $html .= '<tr>';
                    $html .= '<td>'.$line->displayLineData('desc').'</td>';
                    $html .= $tpl;
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $commande = $this->getParentInstance();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'ID de la commande fournisseur absent';
        } else {
            $sql = 'SELECT MAX(num_reception) as num FROM ' . MAIN_DB_PREFIX . 'bl_commande_fourn_reception ';
            $sql .= 'WHERE `id_commande_fourn` = ' . (int) $commande->id;

            $result = $this->db->execute($sql);
            $result = $this->db->db->fetch_object($result);

            if (is_null($result) || !isset($result->num)) {
                $num = 0;
            } else {
                $num = (int) $result->num;
            }

            $num++;

            if (!(int) $this->getData('id_entrepot')) {
                $this->set('id_entrepot', (int) $commande->getData('entrepot'));
            }

            $this->set('num_reception', $num);
        }

        if (count($errors)) {
            return $errors;
        }

        $errors = parent::create($warnings, $force_create);
        
        return $errors;
    }
}

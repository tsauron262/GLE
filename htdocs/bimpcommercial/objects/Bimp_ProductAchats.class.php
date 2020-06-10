<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Product.class.php';

class Bimp_ProductAchats extends Bimp_Product
{

    public static $inc_fourns_filters = array();
    public static $excl_fourns_filters = array();

    public function getCustomFilterValueLabel($field_name, $value)
    {
        switch ($field_name) {
            case 'id_fourn_achats':
                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $value);
                if (BimpObject::ObjectLoaded($fourn)) {
                    return $fourn->getRef() . ' - ' . $fourn->getName();
                }
                break;
        }

        return parent::getCustomFilterValueLabel($field_name, $value);
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'id_fourn_achats':
                if ($excluded) {
                    self::$excl_fourns_filters = $values;
                } else {
                    self::$inc_fourns_filters = $values;
                }
                $sql = 'SELECT COUNT(fl.rowid) FROM ' . MAIN_DB_PREFIX . 'facture_fourn_det fl, ' . MAIN_DB_PREFIX . 'facture_fourn ff';
                $sql .= ' WHERE fl.fk_product = a.rowid AND ff.rowid = fl.fk_facture_fourn AND ff.fk_soc ' . ($excluded ? 'NOT ' : '') . 'IN (' . implode(',', $values) . ') AND ff.fk_statut';

                $filters['custom_fourns_achats'] = array(
                    'custom' => '(' . $sql . ') > 0'
                );
                break;
        }

        return parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    public function getAchatsData()
    {
        if ($this->isLoaded()) {
            $key = 'product_' . $this->id . '_achats_data';

            if (!isset(self::$cache[$key])) {
                self::$cache[$key] = array(
                    'qty'      => 0,
                    'total_ht' => 0,
                    'fourns'   => array()
                );

                $sql = 'SELECT l.qty, l.total_ht, l.total_ttc, f.fk_soc as id_fourn';
                $sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture_fourn_det l';
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_fourn f ON f.rowid = l.fk_facture_fourn';
                $sql .= ' WHERE f.fk_statut IN(0,1,2) AND l.fk_product = ' . (int) $this->id;

                $rows = $this->db->executeS($sql, 'array');

                foreach ($rows as $r) {
                    self::$cache[$key]['qty'] += (float) $r['qty'];
                    self::$cache[$key]['total_ht'] += (float) $r['total_ht'];

                    if (!isset(self::$cache[$key]['fourns'][(int) $r['id_fourn']])) {
                        self::$cache[$key]['fourns'][(int) $r['id_fourn']] = array(
                            'qty'      => 0,
                            'total_ht' => 0
                        );
                    }

                    self::$cache[$key]['fourns'][(int) $r['id_fourn']]['qty'] += (float) $r['qty'];
                    self::$cache[$key]['fourns'][(int) $r['id_fourn']]['total_ht'] += (float) $r['total_ht'];
                }
            }

            return self::$cache[$key];
        }

        return array(
            'qty'      => 0,
            'total_ht' => 0,
            'fourns'   => array()
        );
    }

    public function displayAchatsData($field)
    {
        $data = $this->getAchatsData();

        switch ($field) {
            case 'total_ht':
                $value = 0;
//                if (!empty(static::$inc_fourns_filters)) {
//                    foreach (static::$inc_fourns_filters as $id_fourn) {
//                        if (isset($data['fourns'][$id_fourn])) {
//                            $value += $data['fourns'][$id_fourn]['total_ht'];
//                        }
//                    }
//                } else {
                    $value = $data['total_ht'];
//                }
                return BimpTools::displayMoneyValue($value, 'EUR', false, true);

            case 'total_ttc':
                $value = 0;
//                if (!empty(static::$inc_fourns_filters)) {
//                    foreach (static::$inc_fourns_filters as $id_fourn) {
//                        if (isset($data['fourns'][$id_fourn])) {
//                            $value += $data['fourns'][$id_fourn]['total_ttc'];
//                        }
//                    }
//                } else {
                    $value = $data['total_ttc'];
//                }
                return BimpTools::displayMoneyValue($value, 'EUR', false, true);

            case 'qty':
                $value = 0;
//                if (!empty(static::$inc_fourns_filters)) {
//                    foreach ($fourns as $id_fourn) {
//                        if (isset($data['fourns'][$id_fourn])) {
//                            $value += $data['fourns'][$id_fourn]['qty'];
//                        }
//                    }
//                } else {
                    $value = $data['qty'];
//                }
                return $value;

            case 'fourns_details':
                $html = '';

                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';
                
                foreach ($data['fourns'] as $id_fourn => $fourn_data) {
                    $html .= '<tr>';
                    if (!empty(static::$inc_fourns_filters) && !in_array($id_fourn, static::$inc_fourns_filters)) {
                        continue;
                    }
                    
                    if (!empty(static::$excl_fourns_filters) && in_array($id_fourn, static::$excl_fourns_filters)) {
                        continue;
                    }

                    $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fourn);

                    $html .= '<td>';
                    $html .= '<strong>';
                    if (BimpObject::objectLoaded($fourn)) {
                        $html .= $fourn->getRef() . ' - ' . $fourn->getName();
                    } else {
                        $html .= '#' . $id_fourn;
                    }
                    $html .= '</strong>';
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($fourn_data['total_ht'], 'EUR', false, true);
                    $html .= ' / ' . $fourn_data['qty'];
                    $html .= '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody>';
                $html .= '</table>';

                return $html;
        }

        return 0;
    }
}

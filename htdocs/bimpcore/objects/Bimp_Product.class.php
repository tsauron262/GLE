<?php

class Bimp_Product extends BimpObject
{

    public $stocks = null;
    public static $sousTypes = array(
        0 => '',
        1 => 'Service inter',
        2 => 'Service contrat',
        3 => 'Déplacement inter',
        4 => 'Déplacement contrat',
        5 => 'Logiciel'
    );

    public function isSerialisable()
    {
        if ($this->isLoaded()) {
            return (int) $this->getData('serialisable');
        }

        return 0;
    }

    public function isNotSerialisable()
    {
        return (int) !$this->isSerialisable();
    }

    public function getDolObjectUpdateParams()
    {
        global $user;
        if ($this->isLoaded()) {
            return array($this->id, $user);
        }
        return array(0, $user);
    }

    public function getInstanceName()
    {
        if (!$this->isLoaded()) {
            return 'Produit';
        }

        return $this->getData('ref') . ' - ' . $this->getData('label');
    }

    public function getStocksForEntrepot($id_entrepot)
    {
        $stocks = array(
            'id_stock'       => 0,
            'reel'           => 0,
            'commandes'      => 0, // qté en commande fournisseur
            'dispo'          => 0, // Stock réel - réel réservés
            'virtuel'        => 0, // reel - total_reserves + commandes
            'total_reserves' => 0, // Réservations du statut 0 à - de 300
            'reel_reserves'  => 0, // Réservations du statut 200 à - de 300
        );

        if ($this->isLoaded()) {
            $product = $this->dol_object;

            $product->load_stock('novirtual');
            if (isset($product->stock_warehouse[(int) $id_entrepot])) {
                $stocks['id_stock'] = $product->stock_warehouse[(int) $id_entrepot]->id;
                $stocks['reel'] = $product->stock_warehouse[(int) $id_entrepot]->real;
            }

            $sql = 'SELECT SUM(line.qty) as total_qty FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet line';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur c ON c.rowid = line.fk_commande';
            $sql .= ' WHERE line.fk_product = ' . (int) $this->id;
            $sql .= ' AND c.billed = 0';

            $result = $this->db->executeS($sql);
            if (!is_null($result) && isset($result['total_qty'])) {
                $stocks['commandes'] = (int) $result['total_qty'];
            }

            BimpObject::loadClass('bimpreservation', 'BR_Reservation');

            $reserved = BR_Reservation::getProductCounts($this->id, (int) $id_entrepot);
            $stocks['total_reserves'] = $reserved['total'];
            $stocks['reel_reserves'] = $reserved['reel'];

            $stocks['dispo'] = $stocks['reel'] - $stocks['reel_reserves'];
            $stocks['virtuel'] = $stocks['reel'] - $stocks['total_reserves'] + $stocks['commandes'];
        }

        return $stocks;
    }

    public function fetchStocks()
    {
        $this->stocks = array();

        $where = '`statut` > 0';
        $rows = $this->db->getRows('entrepot', $where, null, 'array', array(
            'rowid', 'label'
        ));


        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $stocks = $this->getStocksForEntrepot((int) $r['rowid']);
                $this->stocks[(int) $r['rowid']] = array(
                    'entrepot_label' => $r['label'],
                    'reel'           => $stocks['reel'],
                    'dispo'          => $stocks['dispo'],
                    'virtuel'        => $stocks['virtuel'],
                    'commandes'      => $stocks['commandes'],
                    'total_reserves' => $stocks['total_reserves'],
                    'reel_reserves'  => $stocks['reel_reserves']
                );
            }
        }
    }

    public function renderStocksByEntrepots($id_entrepot = null)
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID du produit absent');
        }

        if (is_null($this->stocks)) {
            $this->fetchStocks();
        }

        $html = '';

        $html .= '<div class="productStocksContent" data-id_product="' . $this->id . '">';
        $html .= '<h3><i class="fas fa5-box-open iconLeft"></i>Stocks produit ' . $this->getData('ref') . '</h3>';
        $html .= '<div class="stockSearchContainer">';
        $html .= '<i class="fa fa-search iconLeft"></i>';
        $html .= BimpInput::renderInput('text', 'stockSearch', '');
        $html .= '</div>';
        $html .= '<table class="productStockTable bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Entrepôt</th>';
        $html .= '<th>Réel</th>';
        $html .= '<th>Dispo</th>';
        $html .= '<th>Virtuel</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        if (!is_null($id_entrepot) && isset($this->stocks[(int) $id_entrepot])) {
            $html .= '<tr class="currentEntrepot">';
            $html .= '<td>' . $this->stocks[(int) $id_entrepot]['entrepot_label'] . '</td>';
            $html .= '<td>' . $this->stocks[(int) $id_entrepot]['reel'] . '</td>';
            $html .= '<td>' . $this->stocks[(int) $id_entrepot]['dispo'] . '</td>';
            $html .= '<td>' . $this->stocks[(int) $id_entrepot]['virtuel'] . '</td>';
            $html .= '</tr>';
        }

        foreach ($this->stocks as $id_ent => $stocks) {
            if (!is_null($id_entrepot) && ((int) $id_entrepot === (int) $id_ent)) {
                continue;
            }
            $html .= '<tr>';
            $html .= '<td>' . $stocks['entrepot_label'] . '</td>';
            $html .= '<td>' . $stocks['reel'] . '</td>';
            $html .= '<td>' . $stocks['dispo'] . '</td>';
            $html .= '<td>' . $stocks['virtuel'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function getNomExtraIcons($id_entrepot = null)
    {
        if ($this->isLoaded()) {
            return self::getStockIconStatic($this->id, $id_entrepot);
        }
        return '';
    }

    public static function getStockIconStatic($id_product, $id_entrepot = null)
    {
        if (is_null($id_entrepot)) {
            if (BimpTools::isSubmit('id_entrepot')) {
                $id_entrepot = BimpTools::getValue('id_entrepot');
            } elseif (BimpTools::isSubmit('param_list_filters')) {
                $filters = json_decode(BimpTools::getValue('param_list_filters', array()));
                foreach ($filters as $filter) {
                    if ($filter->name === 'id_commande_client') {
                        $commande = BimpObject::getInstance('bimpcore', 'Bimp_Commande', (int) $filter->filter);
                        if (BimpObject::objectLoaded($commande)) {
                            $id_entrepot = (int) $commande->dol_object->array_options['options_entrepot'];
                            break;
                        }
                    }
                }
            }
        }

        $html = '<span class="objectIcon displayProductStocksBtn" title="Stocks" data-id_product="' . $id_product . '" data-id_entrepot="' . (int) $id_entrepot . '"><i class="fas fa5-box-open"></i></span>';
        $html .= '<div class="productStocksContainer hideOnClickOut" id="product_' . $id_product . '_stocks_popover_container"></div>';

        return $html;
    }
}

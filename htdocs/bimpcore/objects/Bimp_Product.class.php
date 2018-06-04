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
            'virtuel'        => 0, // reel - reel_reserves + commandes
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
                $stocks = $this->getStocksForEntrepot((int) $r['id']);
                $this->stocks[(int) $r['id']] = array(
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
        
        $html .= '<div class="productStocksContainer" data-id_product="'.$this->id.'">';
                
        $html .= '</div>';
        
        return $html;
    }
}

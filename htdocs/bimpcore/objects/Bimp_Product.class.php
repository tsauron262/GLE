<?php

class Bimp_Product extends BimpObject
{

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
            'dispo'          => 0, // Stock réel - total réservés
            'total_reserves' => 0, // Stock réel réservé + réservés en attente de réception
            'reel_reserves'  => 0, // seulement réel réservé
        );
        if ($this->isLoaded()) {
            $product = $this->dol_object;

            $product->load_stock('novirtual');
            if (isset($product->stock_warehouse[(int) $id_entrepot])) {
                $stocks['id_stock'] = $product->stock_warehouse[(int) $id_entrepot]->id;
                $stocks['reel'] = $product->stock_warehouse[(int) $id_entrepot]->real;
            }

            BimpObject::loadClass('bimpreservation', 'BR_Reservation');

            $reserved = BR_Reservation::getProductCounts($this->id, (int) $id_entrepot);
            $stocks['total_reserves'] = $reserved['total'];
            $stocks['reel_reserves'] = $reserved['reel'];

            $stocks['dispo'] = $stocks['reel'] - $stocks['total_reserves'];
        }
        
        return $stocks;
    }
}

<?php

class BC_VenteArticle extends BimpObject
{

    public function getTotal()
    {
        if ($this->isLoaded()) {
            $vente = $this->getParentInstance();
            if ($vente->isLoaded()) {
                if ((int) $vente->getData('vente_ht')) {
                    return (float) ((float) $this->getData('unit_price_tax_ex') * (int) $this->getData('qty'));
                } else {
                    return (float) ((float) $this->getData('unit_price_tax_in') * (int) $this->getData('qty'));
                }
            }
        }
        return 0;
    }

    public function displayTotal()
    {
        if ($this->isLoaded()) {
            return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
        }

        return '';
    }

    public function getProductStock($id_entrepôt, $stock_data = 'dispo')
    {
        if (!(int) $id_entrepôt) {
            return 0; // Entrepôt obligatoire. 
        }
        $product = $this->getChildObject('product');

        if (BimpObject::objectLoaded($product)) {
            if ($product->isTypeService()) {
                return 999;
            }

            $stocks = $product->getStocksForEntrepot($id_entrepôt);
            if (array_key_exists($stock_data, $stocks)) {
                return (float) $stocks[$stock_data];
            }
        }

        return 0;
    }

    public function checkPlace($id_entrepot, &$errors = array())
    {
        if ($this->isLoaded()) {
            $equipment = $this->getChildObject('equipment');
            if ($equipment->isLoaded()) {
                return (int) $equipment->isInEntrepot($id_entrepot, $errors);
            }
        }

        return 1;
    }

    public function getTotalRemisesPercent($extra_percent = 0)
    {
        if ($this->isLoaded()) {
            $vente = $this->getParentInstance();

            if (!$vente->isLoaded()) {
                return $extra_percent;
            }

            $remises = BimpObject::getInstance($this->module, 'BC_VenteRemise');

            $list = $remises->getList(array(
                'id_vente'   => (int) $vente->id,
                'id_article' => (int) $this->id
            ));

            if ((int) $vente->getData('vente_ht')) {
                $unit_price_ttc = (float) $this->getData('unit_price_tax_ex');
            } else {
                $unit_price_ttc = (float) $this->getData('unit_price_tax_in');
            }

            $qty = (int) $this->getData('qty');
            $total_ttc = (float) ($unit_price_ttc * $qty);

            if (!$total_ttc) {
                return $extra_percent;
            }

            $total_remises = 0;

            if (!is_null($list) && count($list)) {
                foreach ($list as $remise) {
                    $per_unit = (int) $remise['per_unit'];
                    $montant = 0;

                    switch ((int) $remise['type']) {
                        case 1:
                            $percent = (float) $remise['percent'];
                            if ($percent) {
                                if ($per_unit) {
                                    $montant = (float) ((float) $total_ttc * ($percent / 100));
                                } else {
                                    $montant = (float) ((float) $unit_price_ttc * ($percent / 100));
                                }
                            }
                            break;

                        case 2:
                            $montant = (float) $remise['montant'];
                            if ($per_unit) {
                                $montant *= $qty;
                            }
                            break;
                    }

                    $total_remises += $montant;
                }
            }

            if ($total_remises) {
                return (float) (($total_remises / $total_ttc) * 100) + $extra_percent;
            }
        }
        return $extra_percent;
    }
}

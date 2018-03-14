<?php

class BC_VenteArticle extends BimpObject
{

    public function getTotal()
    {
        if ($this->isLoaded()) {
            return (float) ((float) $this->getData('unit_price_tax_in') * (int) $this->getData('qty'));
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

    public function getProductStock($id_entrepôt)
    {
        if ($this->isLoaded()) {

            $product = $this->getChildObject('product');
            $product->load_stock();

            if (isset($product->stock_warehouse[(int) $id_entrepôt])) {
                return $product->stock_warehouse[(int) $id_entrepôt]->real;
            }
        }


        return 0;
    }

    public function checkPlace($id_entrepot)
    {
        if ($this->isLoaded()) {
            $equipment = $this->getChildObject('equipment');
            if ($equipment->isLoaded()) {
                $place = $equipment->getCurrentPlace();
                if (is_null($place) || !$place->isLoaded()) {
                    return false;
                } else {
                    if ((int) $place->getData('type') !== BE_Place::BE_PLACE_ENTREPOT) {
                        return false;
                    }

                    if ((int) $place->getData('id_entrepot') !== (int) $id_entrepot) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}

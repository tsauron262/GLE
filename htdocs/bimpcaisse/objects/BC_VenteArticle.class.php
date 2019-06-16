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

    public function getProductStock($id_entrepôt)
    {
        if ($this->isLoaded()) {

            $product = $this->getChildObject('product');
            if ($product->type == 1)//service
                return 999;
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

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ((int) $this->getData('id_equipment')) {
                $equipment = $this->getChildObject('equipment');
                if (BimpObject::ObjectLoaded($equipment)) {
                    $equipment->updateField('available', 0, null, true);
                }
            }
        }

        return $errors;
    }
}

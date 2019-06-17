<?php

class BC_VenteReturn extends BimpObject
{

    public function getLabel()
    {
        $label = '';

        $equipment = $this->getChildObject('equipment');
        if (BimpObject::objectLoaded($equipment)) {
            $label = $equipment->displayProduct('nom', false);
//            $label .= ' - ' . $equipment->getData('serial');
        } elseif ((int) $this->getData('id_product')) {
            $label = $this->displayData('id_product', 'nom', false, true);
        }

        return $label;
    }

    public function getPrice()
    {
        $product = null;

        if ((int) $this->getData('id_equipment')) {
            $equipment = $this->getChildObject('equipment');
            if (BimpObject::objectLoaded($equipment)) {
                if ((float) $equipment->getData('prix_vente') > 0) {
                    return round((float) $equipment->getData('prix_vente'), 2);
                } elseif ((float) $equipment->getData('prix_vente_except')) {
                    return round((float) $equipment->getData('prix_vente_except'), 2);
                } else {
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $equipment->getData('id_product'));
                }
            }
        } elseif ((int) $this->getData('id_product')) {
            $product = $this->getChildObject('product');
        }

        if (BimpObject::objectLoaded($product)) {
            return round((float) $product->dol_object->price_ttc, 2);
        }

        return 0;
    }

    public function getTotal()
    {
        return (float) $this->getData('unit_price_tax_in') * (int) $this->getData('qty');
    }

    public function displayProduct($display_name = 'default', $no_html = false)
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_equipment')) {
                $equipment = $this->getChildObject('equipment');
                if (BimpObject::objectLoaded($equipment)) {
                    return $equipment->displayProduct($display_name, $no_html);
                }
            } else {
                return $this->displayData('id_product', $display_name, ($no_html ? 0 : 1), $no_html);
            }
        }

        return '';
    }

    public function displayTotal()
    {
        return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
    }

    public function renderSearchEquipmentInput()
    {
        $html = '';

        $html .= BimpInput::renderInput('text', 'search_equipment', '', array(), null, null, 'search_equipment_to_return');

        $html .= '<p class="inputHelp">Numéro de série d\'un équipement</p>';

        $rand = rand(111111, 999999);
        $button_id = 'searchReturnedEquipmentButton_' . $rand;
        $html .= '<div class="buttonsContainer align-right">';
        $html .= '<span id="' . $button_id . '" class="btn btn-primary" onclick="searchReturnedEquipment($(this))">';
        $html .= '<i class="fa fa-search iconLeft"></i>Rechercher';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<script type="text/javascript">';
        $html .= '$(\'#search_equipment_to_return\').keyup(function(e) {';
        $html .= 'if (e.key === \'Enter\') {';
        $html .= 'e.stopPropagation(); e.no_submit = 1; searchReturnedEquipment($(\'#' . $button_id . '\'));';
        $html .= '}});';
        $html .= '</script>';
        return $html;
    }

    // Overrides: 

    public function create($warnings, $force_create = false)
    {
        $errors = array();

        if (!$this->getData('id_vente')) {
            return array('ID de la vente absent');
        }

        $vente = $this->getParentInstance();

        if (!BimpObject::objectLoaded($vente)) {
            return array('ID de la vente invalide');
        }

        if ((int) $this->getData('id_equipment')) {
            $currentReturnedEquipments = array();
            $returns = $vente->getChildrenObjects('returns');

            foreach ($returns as $return) {
                if ((int) $return->getData('id_equipment')) {
                    $currentReturnedEquipments[] = (int) $return->getData('id_equipment');
                }
            }

            if (in_array($this->getData('id_equipment'), $currentReturnedEquipments)) {
                return array('Cet équipement a déjà été ajouté aux retours produits');
            }

            $equipment = $this->getChildObject('equipment');
            if (!BimpObject::objectLoaded($equipment)) {
                $errors[] = 'L\'équipement d\'ID ' . $this->getData('id_equipment') . ' semble ne pas exister';
            } else {
                if (!$equipment->getData('return_available')) {
                    $errors[] = $equipment->displayReturnUnavailable();
                } else {
                    $place = $equipment->getCurrentPlace();
                    $id_client = 0;
                    if (BimpObject::objectLoaded($place)) {
                        if ((int) $place->getData('type') === BE_Place::BE_PLACE_CLIENT) {
                            $id_client = (int) $place->getData('id_client');
                        }

                        if (!$id_client || $id_client !== (int) $vente->getData('id_client')) {
                            return array('L\'équipement sélectionné n\'est pas enregistré pour ce client');
                        }
                    }
                    $id_product = (int) $equipment->getData('id_product');
                    if ($id_product) {
                        $this->set('id_product', $id_product);
                        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);
                        if (!BimpObject::objectLoaded($product)) {
                            $errors[] = 'Le produit associé à l\'équipement sélectionné semble ne plus exister';
                        } else {
                            $this->set('unit_price_tax_ex');
                            $prix_ht = 0;
                            $prix_ttc = 0;
                            $tva_tx = (float) $product->dol_object->tva_tx;

                            if (BimpTools::isSubmit('price_ttc')) {
                                $prix_ttc = round((float) BimpTools::getValue('price_ttc'), 2);
                            } else {
                                if ((float) $equipment->getData('prix_vente') > 0) {
                                    $prix_ttc = round((float) $equipment->getData('prix_vente'), 2);
                                } elseif ((float) $equipment->getData('prix_vente_except') > 0) {
                                    $prix_ttc = round((float) $equipment->getData('prix_vente_except'), 2);
                                } else {
                                    $prix_ttc = round((float) $product->dol_object->price_ttc, 2);
                                }
                            }

                            $prix_ht = (float) BimpTools::calculatePriceTaxEx($prix_ttc, (float) $tva_tx);

                            $this->set('unit_price_tax_ex', round($prix_ht, 2));
                            $this->set('unit_price_tax_in', $prix_ttc, 2);
                            $this->set('tva_tx', $tva_tx);
                            $this->set('qty', 1);
                        }
                    } else {
                        $errors[] = 'Aucun produit associé à l\'équipement ' . $equipment->getData('serial');
                    }
                }
            }
        } else {
            if (!(int) $this->getData('id_product')) {
                return array('Aucun produit sélectionné');
            }

            $result = $this->db->getValue('bc_vente_return', 'id', '`id_vente` = ' . (int) $vente->id . ' AND `id_product` = ' . (int) $this->getData('id_product'));

            if (!is_null($result)) {
                return array('Ce produit a déjà été ajouté aux retours');
            }

            $product = $this->getChildObject('product');
            if (!BimpObject::objectLoaded($product)) {
                return array('Le produit d\'ID ' . $id_product . ' semble ne plus exister');
            }

            if ($product->isSerialisable()) {
                return array('Produit sérialisable: sélection d\'un équipement obligatoire');
            }

            $prix_ttc = 0;

            if (BimpTools::isSubmit('price_ttc')) {
                $prix_ttc = round((float) BimpTools::getValue('price_ttc', 0), 2);
            } else {
                $prix_ttc = round((float) $product->dol_object->price_ttc, 2);
            }

            $this->set('unit_price_tax_in', $prix_ttc);
            $this->set('tva_tx', (float) $product->dol_object->tva_tx);
            $price_ht = (float) BimpTools::calculatePriceTaxEx($prix_ttc, (float) $product->dol_object->tva_tx);
            $this->set('unit_price_tax_ex', round($price_ht, 2));
        }

        if (count($errors)) {
            return $errors;
        }

        return parent::create($warnings, $force_create);
    }
}

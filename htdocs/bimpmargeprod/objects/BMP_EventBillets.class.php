<?php

class BMP_EventBillets extends BimpObject
{

    public function isEventEditable()
    {
        $event = $this->getParentInstance();
        if (!is_null($event) && $event->isLoaded()) {
            return $event->isEditable();
        }

        return 0;
    }

    public function getCreateForm()
    {
        if ($this->isEventEditable()) {
            return 'default';
        }

        return '';
    }

    public function getTarifsArray()
    {
        $event = $this->getParentInstance();
        $tarifs = array();
        if (!is_null($event)) {
            $list = $event->getChildrenObjects('tarifs');
            if (!is_null($list) && count($list)) {
                foreach ($list as $item) {
                    $tarifs[$item->id] = $item->getData('name');
                }
            }
        }
        return $tarifs;
    }

    public function displaySeller()
    {
        if (isset($this->id) && $this->id) {

            $id_sellet = $this->getData('id_soc_seller');
            if (!is_null($id_sellet) && $id_sellet) {
                return $this->displayData('id_soc_seller');
            } else {
                $name = $this->getData('seller_name');
                if (!is_null($name)) {
                    return $name;
                }
            }
        }

        return '';
    }

    public function displayTarif()
    {
        $tarif = $this->getChildObject('tarif');
        if (isset($tarif->id) && $tarif->id) {
            $name = $tarif->getData('name');
            $amount = $tarif->getData('amount');
            if (!is_null($name)) {
                return $name . ' (' . BimpTools::displayMoneyValue($amount, 'EUR') . ')';
            } elseif (!is_null($amount) && $amount) {
                return $amount . ' ' . BimpTools::getCurrencyHtml('EUR');
            }
        }

        return '<span class="warning">Aucun</span>';
    }

    public function getTotal()
    {
        if (!isset($this->id) || !$this->id) {
            return 0;
        }

        $tarif = $this->getChildObject('tarif');

        if (!is_null($tarif)) {
            return ((float) $tarif->getData('amount') * (int) $this->getData('quantity'));
        }

        return 0;
    }

    public function displayTotalTTC()
    {
        return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
    }

    public function displayTotalHT()
    {
        $total_ttc = $this->getTotal();
        $event = BimpObject::getInstance($this->module, 'BMP_Event');
        $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . BMP_Event::$id_billets_type_montant);
        $total_ht = (float) BimpTools::calculatePriceTaxEx($total_ttc, BimpTools::getTaxeRateById($id_tax));
        return BimpTools::displayMoneyValue($total_ht, 'EUR');
    }
}

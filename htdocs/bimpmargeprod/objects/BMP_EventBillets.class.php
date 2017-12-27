<?php

class BMP_EventBillets extends BimpObject
{

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
}

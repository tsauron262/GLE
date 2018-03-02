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
}

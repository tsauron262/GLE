<?php

class BS_SavProduct extends BimpObject
{

    public function displayProduct()
    {
        $product = $this->getChildObject('product');

        if ($product->isLoaded()) {
            return $product->displayData('label');
        }

        return '';
    }

    public function getUnitPrice()
    {
        $product = $this->getChildObject('product');

        if ($product->isLoaded()) {
            return (float) $product->getData('price_ttc');
        }

        return 0;
    }

    public function getTotal()
    {
        $qty = (int) $this->getData('qty');
        $price = (float) $this->getUnitPrice();
        return (float) $qty * $price;
    }

    public function displayUnitPrice()
    {
        return BimpTools::displayMoneyValue($this->getUnitPrice(), 'EUR');
    }

    public function displayTotal()
    {
        return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
    }
}

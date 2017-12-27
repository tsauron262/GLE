<?php

class BMP_EventMontantDetail extends BimpObject {
     public function getTotal()
     {
         $qty = (int) $this->getData('quantity');
         $price = (int) $this->getData('unit_price');
         
         return round(($qty * $price), 2);
     }
     
     public function displayTotal()
     {
         return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
     }
}
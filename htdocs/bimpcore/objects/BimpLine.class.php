<?php

class BimpLine extends BimpObject
{

    // Getters: 

    public function isParentEditable()
    {
        return 1;
    }

    public function isEditable()
    {
        return $this->isParentEditable();
    }

    public function isDeletable()
    {
        return $this->isParentEditable();
    }

    public function getTotalHT()
    {
        return 0;
    }

    public function getTotalTTC()
    {
        return 0;
    }

    public function getListExtraBtn()
    {
        return array();
    }

    // Affichages: 

    public function displayTotalHT()
    {
        return BimpTools::displayMoneyValue($this->getTotalHT(), 'EUR');
    }

    public function displayTotalTTC()
    {
        return BimpTools::displayMoneyValue($this->getTotalTTC(), 'EUR');
    }
}

<?php

class BC_VenteRemise extends BimpObject
{

    const BC_REMISE_PERCENT = 1;
    const BC_REMISE_AMOUNT = 2;

    public function getArticlesArray()
    {
        $vente = $this->getParentInstance();

        $articles = array(
            0 => ''
        );
        if (!is_null($vente) && $vente->isLoaded()) {
            foreach ($vente->getArticlesArray() as $id_article => $label) {
                $articles[(int) $id_article] = $label;
            }
        }

        return $articles;
    }

    public function displayMontant()
    {
        if ($this->isLoaded()) {
            $type = (int) $this->getData('type');
            switch ($type) {
                case self::BC_REMISE_PERCENT:
                    return str_replace('.', ',', '' . $this->getData('percent')) . ' %';

                case self::BC_REMISE_AMOUNT:
                    return BimpTools::displayMoneyValue((float) $this->getData('montant'), 'EUR');
            }
        }

        return '';
    }
}

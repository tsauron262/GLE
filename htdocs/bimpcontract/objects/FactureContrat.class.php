<?php

class FactureContrat extends BimpObject
{

    public function display_montant_ln()
    {
        if ($this->isLoaded()) {
            $contrat = $this->getChildObject('contrat');
            $renouvellement = $contrat->getRenouvellementNumberFromDate($this->getData('datef'));
            if ($renouvellement == $this->getData('renouvellement')){
                $totalCt = $contrat->getTotal($renouvellement);
                if($totalCt)
                    return BimpTools::displayMoneyValue($this->getData('totalLn') / $totalCt * $this->getData('totalFact'));
            }
        }
        return 0;
    }
}

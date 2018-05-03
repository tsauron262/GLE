<?php

class BS_Pret extends BimpObject
{

    public static $types = array(
        1 => 'Iphone de prêt'
    );

    public function getCentresArray()
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

        global $tabCentre;

        $centres = array();

        foreach ($tabCentre as $code => $data) {
            $centres[$code] = $data[2];
        }

        return $centres;
    }

    // Overrides

    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            return $this->getData('ref') . ' - ' . $this->getData('serial');
        }
    }
}

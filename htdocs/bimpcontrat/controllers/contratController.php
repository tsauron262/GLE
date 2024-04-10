<?php

class contratController extends BimpController
{

    public function init()
    {
        if (!(int) BimpTools::getValue('ajax', 0, 'int')) {
            $contrat = $this->config->getObject('', 'contrat');
            if (BimpObject::objectLoaded($contrat)) {
                if ((int) $contrat->getData('version') !== 2) {
                    header("Location: " . DOL_URL_ROOT . '/bimpcontract/index.php?fc=contrat&id=' . $contrat->id);
                    exit();
                }
            }
        }
    }
}

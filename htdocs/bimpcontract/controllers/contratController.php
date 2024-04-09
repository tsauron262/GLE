<?php

class contratController extends BimpController
{

    public function init()
    {
        if (!(int) BimpTools::getValue('ajax', 0, 'int')) {
            $contrat = $this->config->getObject('', 'contrat');
            if (BimpObject::objectLoaded($contrat)) {
                if ((int) $contrat->getData('version') !== 1) {
                    header("Location: " . DOL_URL_ROOT . '/bimpcontrat/index.php?fc=contrat&id=' . $contrat->id);
                    exit();
                }
            }
        }
    }
}

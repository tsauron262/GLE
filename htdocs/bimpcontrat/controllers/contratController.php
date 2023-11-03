<?php

class contratController extends BimpController
{

    public function init()
    {
        if (!BimpTools::getValue('ajax', 0)) {
            $contrat = $this->config->getObject('', 'contrat');
            if (BimpObject::objectLoaded($contrat)) {
                if ((int) $contrat->getData('version') !== 2) {
                    die('VER CONT : '. (int) $contrat->getData('version'));
//                    header("Location: " . DOL_URL_ROOT . '/bimpcontract/index.php?fc=contrat&id=' . $contrat->id);
//                    exit();
                }
            }
        }
    }
}

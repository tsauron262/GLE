<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/controllers/BimpCommController.php';

class propalController extends BimpCommController
{

    public function init()
    {
        if (!BimpTools::getValue('ajax', 0)) {
            $propal = $this->config->getObject('', 'propal');
            if (BimpObject::objectLoaded($propal)) {
                $id_sav = (int) $propal->getIdSav();
                if ($id_sav) {
                    header("Location: " . DOL_URL_ROOT . '/bimpsupport/index.php?fc=sav&id=' . $id_sav);
                    exit();
                }
            }
        }
    }
}

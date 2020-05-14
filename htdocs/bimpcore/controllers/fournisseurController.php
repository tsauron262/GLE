<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/controllers/societeController.php';

class fournisseurController extends societeController
{

    public function init()
    {
        $id_soc = (int) BimpTools::getValue('id', 0);

        if ($id_soc) {
            $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);

            if (BimpObject::objectLoaded($soc)) {
                if ((int) $soc->getData('client') && !(int) $soc->getData('fournisseur')) {
                    $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=client&id=' . $id_soc;
                    header("location: " . $url);
                    exit;
                }
            }
        }
    }
}

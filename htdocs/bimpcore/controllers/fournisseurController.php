<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/controllers/societeController.php';

class fournisseurController extends societeController
{

    public function init()
    {
        if (!BimpTools::getValue('ajax', 0, 'int')) {
            $id_soc = (int) BimpTools::getValue('id', 0, 'int');

            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);

                if (BimpObject::objectLoaded($soc)) {
                    if ((int) $soc->isClient() && !(int) $soc->isFournisseur()) {
                        $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=client&id=' . $id_soc;
                        header("location: " . $url);
                        exit;
                    }
                }
            }
        }
    }
}

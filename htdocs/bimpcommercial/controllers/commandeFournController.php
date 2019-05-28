<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/controllers/BimpCommController.php';

class commandeFournController extends BimpCommController
{

    public function init()
    {
        
    }

    public function showLogistique()
    {
        $commande = $this->config->getObject('', 'commande_fourn');

        if (BimpObject::objectLoaded($commande)) {
            if ((int) $commande->getData('fk_statut') > 2) {
                return 1;
            }
        }

        return 0;
    }
}

<?php

class indexController extends BimpController
{

    public function renderAbonnementsTab($params = array())
    {
        BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');
        return BCT_Contrat::renderAbonnementsTabs($params);
    }
}

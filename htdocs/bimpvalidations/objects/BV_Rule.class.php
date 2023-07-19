<?php

class BV_Rule extends BimpObject
{

    public static $types = array(
        'comm' => 'Commerciale',
        'fin'  => 'Financière',
        'rtp'  => 'Retard de paiement'
    );
    public static $objects_list = array(
        'bimpcomm'  => 'Toutes pièces commerciales',
        'propales'  => 'Devis',
        'commandes' => 'Commandes',
        'factures'  => 'Factures'
    );

    // Droits users: 

    public function canCreate()
    {
        global $user;
        return (int) ($user->admin || $user->rights->BimpValidation->rule->admin);
    }

    public function canView()
    {
        return $this->canCreate();
    }

    public function renderExtraParamsInput()
    {
        $html = '';
        return $html;
    }
}

<?php

class BV_Rule extends BimpObject
{

    public static $types = array(
        'comm' => 'Commerciale',
        'fin'  => 'Financière (Encours)',
        'rtp'  => 'Retard de paiement'
    );
    public static $objects_list = array(
        'bimpcomm' => 'Toute pièce commerciale',
        'propale'  => 'Devis',
        'commande' => 'Commande',
        'facture'  => 'Facture',
        'contrat'  => 'Contrat'
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

    // Getters booléens: 

    public function isUserAllowed($id_user)
    {
        $users = $this->getData('users');

        if (!empty($users) && in_array($id_user, $users)) {
            return 1;
        }

        return 0;
    }

    // Rendus HTML: 
    public function renderExtraParamsInput()
    {
        $html = '';
        return $html;
    }
}

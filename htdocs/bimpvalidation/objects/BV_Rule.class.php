<?php

class BV_Rule extends BimpObject
{

    public static $types = array(
        'comm' => 'Commerciale (Remises)',
        'fin'  => 'Financière (Encours)',
        'rtp'  => 'Retard de paiement'
    );
    public static $objects_list = array(
        'propal'   => array('label' => 'Devis', 'module' => 'bimpcommercial', 'object_name' => 'Bimp_Propal'),
        'commande' => array('label' => 'Commande', 'module' => 'bimpcommercial', 'object_name' => 'Bimp_Commande'),
        'facture'  => array('label' => 'Facture', 'module' => 'bimpcommercial', 'object_name' => 'Bimp_Facture'),
        'contrat'  => array('label' => 'Contrat', 'module' => 'bimpcontract', 'object_name' => 'BContract_contrat'),
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

    // Getters données: 

    public function getValidationUsers()
    {
        $users = $this->getData('users');

        $groups = $this->getData('groups');

        foreach ($groups as $id_group) {
            $group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $id_group);
            if (BimpObject::objectLoaded($group)) {
                $group_users = $group->getUserGroupUsers(true);
                foreach ($group_users as $id_user) {
                    if (!in_array($id_user, $users)) {
                        $users[] = $id_user;
                    }
                }
            }
        }

        return $users;
    }

    // Rendus HTML: 
    public function renderExtraParamsInput()
    {
        $html = '';
        return $html;
    }
}

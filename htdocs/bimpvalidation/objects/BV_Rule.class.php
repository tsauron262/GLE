<?php

class BV_Rule extends BimpObject
{

    public static $types = array(
        'comm' => array('label' => 'Commerciale (Remises)', 'val_label' => 'Remises (%)', 'label2' => 'commerciale'),
        'fin'  => array('label' => 'Financière (Encours)', 'val_label' => 'Encours client', 'label2' => 'financière'),
        'rtp'  => array('label' => 'Retard de paiement', 'val_lavel' => 'Total paiements en retard', 'label2' => 'sur retards de paiement')
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
        if ((int) $this->getData('all_users')) {
            return 1;
        }

        $users = $this->getValidationUsers();

        if (!empty($users) && in_array($id_user, $users)) {
            return 1;
        }

        return 0;
    }

    // Getters données: 

    public function getValidationUsers()
    {
        if ((int) $this->getData('all_users')) {
            return array();
        }

        global $user;

        $extra_params = (int) $this->getData('extra_params');
        $sup_only = (int) BimpTools::getArrayValueFromPath($extra_params, 'if_sup_only', 0);

        $users = array();

        if ((int) $this->getData('user_superior')) {
            if (BimpObject::objectLoaded($user) && (int) $user->fk_user) {
                $users[] = $user->fk_user;
            }

            if ($sup_only) {
                return $users;
            }
        }

        $bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $user->id);

        foreach ($this->getData('users') as $id_user) {
            if (!in_array($id_user, $users)) {
                if ($sup_only && !$bimp_user->isUserSuperior($id_user, 100)) {
                    continue;
                }

                $users[] = $id_user;
            }
        }

        $groups = $this->getData('groups');

        foreach ($groups as $id_group) {
            $group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $id_group);
            if (BimpObject::objectLoaded($group)) {
                $group_users = $group->getUserGroupUsers(true);
                foreach ($group_users as $id_user) {
                    if (!in_array($id_user, $users)) {
                        if ($sup_only && !$bimp_user->isUserSuperior($id_user, 100)) {
                            continue;
                        }

                        $users[] = $id_user;
                    }
                }
            }
        }

        return $users;
    }

    // Affichges: 
    public function displayObjects()
    {
        $html = '';

        if ($this->getData('all_objects')) {
            $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Tous les objets</span>';
        } else {
            foreach ($this->getData('objects') as $obj) {
                if (!isset(self::$objects_list[$obj])) {
                    continue;
                }
                $html .= ' - ' . self::$objects_list[$obj]['label'] . '<br/>';
            }
        }

        return $html;
    }

    public function displaySecteurs()
    {
        $html = '';

        if ((int) $this->getData('all_secteurs')) {
            $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Tous les secteurs</span>';
        } else {
            $secteurs = self::getSecteursArray(false);
            foreach ($this->getData('secteurs') as $secteur) {
                if (!isset($secteurs[$secteur])) {
                    continue;
                }
                $html .= ' - ' . $secteurs[$secteur] . '<br/>';
            }
        }

        return $html;
    }

    public function displayUsers()
    {
        $html = '';

        if ((int) $this->getData('user_superior')) {
            $html .= '<span class="important">' . BimpRender::renderIcon('fas_user-circle', 'iconLeft') . 'Sup. hiérarchique du demandeur</span>';
        }


        if ((int) $this->getData('all_users')) {
            $html .= ($html ? '<br/>' : '') . '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Tout le monde</span>';
        }

        $users = $this->getData('users');
        if (!empty($users)) {
            foreach ($users as $id_user) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

                if (BimpObject::objectLoaded($user)) {
                    $html .= ($html ? '<br/>' : '') . $user->getLink();
                }
            }
        }

        $groups = $this->getData('groups');
        if (!empty($groups)) {
            foreach ($groups as $id_group) {
                $group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $id_group);

                if (BimpObject::objectLoaded($group)) {
                    $html .= ($html ? '<br/>' : '') . $group->getLink();
                }
            }
        }

        return $html;
    }

    public function displayExtraParams()
    {
        $html = '';

        $extra_params = $this->getData('extra_params');

        $val = (int) BimpTools::getArrayValueFromPath($extra_params, 'if_sup_only', 0);
        $html .= '<b>Sup. seulement: </b><span class="' . ($val ? 'success">OUI' : 'danger">NON') . '</span><br/>';

        switch ($this->getData('type')) {
            case 'comm':
                $val = (int) BimpTools::getArrayValueFromPath($extra_params, 'sur_marge', 0);
                $html .= '<b>Sur Marge : </b><span class="' . ($val ? 'success">OUI' : 'danger">NON') . '</span><br/>';
                break;
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderExtraParamsInput()
    {
        $html = '';

        $has_inputs = false;
        $extra_params = $this->getData('extra_params');

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead></thead>';

        $html .= '<tbody class="headers_col">';

        $html .= '<tr>';
        $html .= '<th>Si sup. hiérarchique du demandeur seulement</th>';
        $html .= '<td>';
        $html .= BimpInput::renderInput('toggle', 'if_sup_only', (int) BimpTools::getArrayValueFromPath($extra_params, 'if_sup_only', 0));
        $html .= '</td>';
        $html .= '</tr>';

        switch (BimpTools::getPostFieldValue('type', '')) {
            case 'comm':
                $has_inputs = true;
                $html .= '<tr>';
                $html .= '<th>Sur marge</th>';
                $html .= '<td>';
                $html .= BimpInput::renderInput('toggle', 'sur_marge', (int) BimpTools::getArrayValueFromPath($extra_params, 'sur_marge', 0));
                $html .= '</td>';
                $html .= '</tr>';
                break;
        }
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    // Ovverides: 

    public function validatePost()
    {
        $errors = parent::validatePost();

        $extra_params = $this->getData('extra_params');
        if (BimpTools::isPostFieldSubmit('if_sup_only')) {
            $extra_params['if_sup_only'] = (int) BimpTools::getPostFieldValue('if_sup_only');
        }

        switch ($this->getData('type')) {
            case 'comm':
                if (BimpTools::isPostFieldSubmit('sur_marge')) {
                    $extra_params['sur_marge'] = (int) BimpTools::getPostFieldValue('sur_marge');
                }
                break;
        }
        $this->set('extra_params', $extra_params);

        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        if ((int) $this->getData('all_objects')) {
            $this->set('objects', array());
        } elseif (empty($this->getData('objects'))) {
            $errors[] = 'Aucun objet sélectionné';
        }

        if ((int) $this->getData('all_secteurs')) {
            $this->set('secteurs', array());
        } elseif (empty($this->getData('secteurs'))) {
            $errors[] = 'Aucun secteur sélectionné';
        }

        if ((int) $this->getData('all_users')) {
            $this->set('users', array());
            $this->set('groups', array());
            $this->set('user_superior', 0);
        } elseif (!(int) $this->getData('user_superior') && empty($this->getData('users')) && empty($this->getData('groups'))) {
            $errors[] = 'Aucun utilisateur ni  groupe sélectionné';
        }

        // Check extra_params: 
        $extra_params = $this->getData('extra_params');

        if (!isset($extra_params['if_sup_only'])) {
            $extra_params['if_sup_only'] = 0;
        }

        switch ($this->getData('type')) {
            case 'comm':
                if (!isset($extra_params['sur_marge'])) {
                    $extra_params['sur_marge'] = 0;
                }
                break;
        }

        $this->set('extra_params', $extra_params);

        return $errors;
    }
}

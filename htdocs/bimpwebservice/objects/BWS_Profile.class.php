<?php

class BWS_Profile extends BimpObject
{
    /* Attention: "profil" prend un "e" à la fin en anglais mais pas en français */

    // Droits users: 

    public function canView()
    {
        return BimpCore::isUserDev();
    }

    public function canCreate()
    {
        return BimpCore::isUserDev();
    }

    public function canEdit()
    {
        return BimpCore::isUserDev();
    }

    public function canDelete()
    {
        return BimpCore::isUserDev();
    }

    public function canSetAction($action): int
    {
        return BimpCore::isUserDev();
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('duplicate'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getListExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $right = BimpObject::getInstance('bimpwebservice', 'BWS_ProfileRight');
            if ($right->can('view')) {
                $buttons[] = array(
                    'label'   => 'Droits',
                    'icon'    => 'fas_bars',
                    'onclick' => $right->getJsLoadModalList('profile', array(
                        'id_parent' => $this->id,
                        'title'     => 'Droits du profile "' . $this->getData('name') . '"'
                    ))
                );
            }

            if ($this->isActionAllowed('duplicate') && $this->canSetAction('duplicate')) {
                $buttons[] = array(
                    'label'   => 'Cloner',
                    'icon'    => 'fas_paste',
                    'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
                        'form_name' => 'duplicate'
                    ))
                );
            }
        }
        return $buttons;
    }

    // Rendus HTML: 

    public function renderUsersList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $ws_user = BimpObject::getInstance('bimpwebservice', 'BWS_User');
            $list = new BC_ListTable($ws_user, 'default', 1, null, 'Utilisateurs ws associés au profile "' . $this->getName() . '"', 'fas_users');
            $list->addFieldFilterValue('a.profiles', array(
                'part_type' => 'middle',
                'part'      => '[' . $this->id . ']'
            ));
            $html .= $list->renderHtml();
        }

        return $html;
    }

    // Actions: 

    public function actionDuplicate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $name = BimpTools::getArrayValueFromPath($data, 'name', '', $errors, true, 'Veuillez saisir un nom');

        if ($name && $name == $this->getData('name')) {
            $errors[] = 'Veuillez saisir un nom différent du profile à dupliquer';
        }

        if (!count($errors)) {
            $new_profile = BimpObject::createBimpObject('bimpwebservice', 'BWS_Profile', array(
                        'name'   => $name,
                        'desc'   => BimpTools::getArrayValueFromPath($data, 'desc', ''),
                        'active' => 1
                            ), true, $errors, $warnings);

            if (!count($errors)) {
                if (BimpObject::objectLoaded($new_profile)) {
                    $rights = $this->getChildrenObjects('rights');

                    foreach ($rights as $right) {
                        $right_data = $right->getDataArray();
                        $right_data['id_profile'] = $new_profile->id;

                        $right_errors = array();
                        BimpObject::createBimpObject('bimpwebservice', 'BWS_ProfileRight', $right_data, true, $right_errors);

                        if (count($right_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($right_errors, 'Echec création du droit "' . $right_data['request'] . '"' . ($right_data['obj_name'] ? ' (Objet ' . $right_data['obj_module'] . '/' . $right_data['obj_name'] . ')' : ''));
                        }
                    }
                } else {
                    $errors[] = 'Echec de la création du nouveau profil pour une raison inconnue';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validate()
    {
        $errors = array();
        $name = $this->getData('name');

        $where = 'name = \'' . $name . '\'';

        if ($this->isLoaded()) {
            $where .= ' AND id != ' . $this->id;
        }

        $id_profile = $this->db->getValue('bws_profile', 'id', $where);

        if ($id_profile) {
            $errors[] = 'Un profil portant le nom "' . $name . '" existe déjà';
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }
}

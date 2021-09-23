<?php

class Bimp_UserGroup extends BimpObject
{

    // Droits user: 

    public function canSetAction($action): int
    {
        global $user;

        switch ($action) {
            case 'deassociateUser':
                if ($user->admin) {
                    return 1;
                }
                return 0;
        }
        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('deassociateUser'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getUserListExtraButtons()
    {
        $buttons = array();

        if (BimpTools::getValue('fc', '') === 'user') {
            $id_user = (int) BimpTools::getValue('id', 0);
            if ($id_user && $this->isActionAllowed('deassociateUser') && $this->canSetAction('deassociateUser')) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

                if (BimpObject::objectLoaded($user)) {
                    $buttons[] = array(
                        'label'   => 'Retirer l\'utilisateur du groupe',
                        'icon'    => 'fas_unlink',
                        'onclick' => $this->getJsActionOnclick('deassociateUser', array(
                            'id_user' => $id_user
                                ), array(
                            'confirm_msg' => htmlentities('Veuillez confirmer le retrait de l\\\'utilisateur "' . $user->getName() . '" du groupe "' . $this->getName() . '"')
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    // Actions: 
    
    public function actionDeassociateUser($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'L\'utilisateur a été retiré du groupe avec succès';

        $id_user = (int) BimpTools::getArrayValueFromPath($data, 'id_user', 0);

        if (!$id_user) {
            $errors[] = 'Aucun utilisateur sélectionné';
        } else {
            $where = 'fk_user = ' . $id_user . ' AND fk_usergroup = ' . $this->id;
            if ($this->db->delete('usergroup_user', $where) <= 0) {
                $errors[] = 'Echec du retrait de l\'utilisateur du groupe "' . $this->getName() . '" - ' . $this->db->err();
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}

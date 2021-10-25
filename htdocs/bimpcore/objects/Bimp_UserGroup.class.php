<?php

class Bimp_UserGroup extends BimpObject
{

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
}

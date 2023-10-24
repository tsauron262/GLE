<?php
/*
 * Neccesite les champ
 * users_follow
 * users_no_follow
 * emails_follow
 */

class BimpAbstractFollow extends BimpObject
{
    public function getButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('follow') && $this->canSetAction('follow')) {
            $buttons[] = array(
                'label'   => 'Suivre',
                'icon'    => 'fas_bell',
                'onclick' => $this->getJsActionOnclick('follow', array(), array())
            );
        }

        if ($this->isActionAllowed('unfollow') && $this->canSetAction('unfollow')) {
            $buttons[] = array(
                'label'   => 'Ne plus suivre',
                'icon'    => 'fas_bell-slash',
                'onclick' => $this->getJsActionOnclick('unfollow', array(), array())
            );
        }
        return $buttons;
    }
    
    public function actionFollow($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Suivi enregistré avec succès';

        global $user;
        $id_user = (int) BimpTools::getArrayValueFromPath($data, 'id_user', $user->id);
        $email = BimpTools::getArrayValueFromPath($data, 'email', '');
  
        if($email != ''){
            $emails_follow = $this->getData('emails_follow');
            if (!in_array($email, $emails_follow)) {
                $emails_follow[] = $email;
            }
            
            $this->set('emails_follow', $emails_follow);
            $errors = $this->update($warnings, true);
        }
        elseif (!$id_user) {
            $errors[] = 'Aucun utilisateur spécifié';
        } else {
            $u = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
            if (!BimpObject::objectLoaded($u)) {
                $errors[] = 'Cet utilisateur n\'existe plus';
            } else {
                if (!(int) $u->getData('statut')) {
                    $errors[] = 'Cet utilisateur est désactivé';
                } else {
                    $users_no_follow = BimpTools::unsetArrayValue($this->getData('users_no_follow'), $id_user);
                    $users_follow = $this->getData('users_follow');
                    if (!in_array($id_user, $users_follow)) {
                        $users_follow[] = $id_user;
                    }

                    $this->set('users_no_follow', $users_no_follow);
                    $this->set('users_follow', $users_follow);

                    $errors = $this->update($warnings, true);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionUnfollow($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Arrêt du suivi enregistré avec succès';

        global $user;
        $id_user = (int) BimpTools::getArrayValueFromPath($data, 'id_user', $user->id);
        $email = BimpTools::getArrayValueFromPath($data, 'email', '');

        
        if($email != ''){
            $emails_follow = BimpTools::unsetArrayValue($this->getData('emails_follow'), $email);
            
            $this->set('emails_follow', $emails_follow);
            $errors = $this->update($warnings, true);
        }
        elseif (!$id_user) {
            $errors[] = 'Aucun utilisateur spécifié';
        } else {
            $users_follow = BimpTools::unsetArrayValue($this->getData('users_follow'), $id_user);
            $users_no_follow = $this->getData('users_no_follow');
            if (!in_array($id_user, $users_no_follow)) {
                $users_no_follow[] = $id_user;
            }

            $this->set('users_no_follow', $users_no_follow);
            $this->set('users_follow', $users_follow);

            $errors = $this->update($warnings, true);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
    
    /*
     * mode 0 tous, 1 les ajouter manuellement (supprimable) 2 les auto (non supprimable)
     */
    public function getEmailFollow($mode = 0){
        if($mode < 2)
            return $this->getData('emails_follow');
        return array();
    }
    
    public function getUsersFollow($excludeMe = false, $exclude_unactive = true, &$users_no_follow = array()){
        global $user;
        $users = array();
        $users_no_follow = BimpTools::merge_array($users_no_follow, $this->getData('users_no_follow'));
        foreach ($this->getData('users_follow') as $id_user) {
            if ($excludeMe && $id_user == $user->id) {
                continue;
            }

            if (is_array($users_no_follow) && in_array($id_user, $users_no_follow)) {
                continue;
            }

            if (!array_key_exists($id_user, $users)) {
                $u = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

                if (BimpObject::objectLoaded($u)) {
                    if ($exclude_unactive && !(int) $u->getData('statut')) {
                        continue;
                    }

                    $users[$id_user] = $u;
                }
            }
        }
        return $users;
    }
    
    public function displayFollower()
    {
        $html = '';
        $users = $this->getUsersFollow(false);

        $edit = $this->canEdit();

        foreach ($users as $user) {
            if ($edit) {
                $onclick = $this->getJsActionOnclick('unfollow', array(
                    'id_user' => $user->id
                        ), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'arrêt du suivi pour ' . $user->getName()
                ));
                $html .= '<span class="trash_button" style="display: inline-block; margin-right: 15px" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_trash-alt');
                $html .= '</span>';
            }
            $html .= $user->getLink() . '<br/>';
        }
        
        foreach($this->getEmailFollow(2) as $mail){
            $html .= $mail . '<br/>';
        }
        
        foreach($this->getEmailFollow(1) as $mail){
            if ($edit) {
                $onclick = $this->getJsActionOnclick('unfollow', array(
                    'email' => $mail
                        ), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'arrêt du suivi pour ' . $mail
                ));
                $html .= '<span class="trash_button" style="display: inline-block; margin-right: 15px" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_trash-alt');
                $html .= '</span>';
            }
            $html .= $mail . '<br/>';
        }

        if ($edit) {
            $onclick = $this->getJsActionOnclick('follow', array(), array(
                'form_name' => 'add_user_follow'
            ));

            $html .= '<div style="margin-bottom: 10px; text-align: right">';
            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_user-plus', 'iconLeft') . 'Ajouter un utilisateur à notifier';
            $html .= '</span>';
            $html .= '</div>';
            
            $onclick = $this->getJsActionOnclick('follow', array(), array(
                'form_name' => 'add_email_follow'
            ));

            $html .= '<div style="margin-bottom: 10px; text-align: right">';
            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_user-plus', 'iconLeft') . 'Ajouter un email à notifier';
            $html .= '</span>';
            $html .= '</div>';
        }

        return $html;
    }
    
    
    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'follow':
                $email = BimpTools::getPostFieldValue('email', '');
                if($email != ''){
                    if (in_array($email, $this->getEmailFollow())) {
                        $labels = $this->getLabels();
                        $errors[] = $email.' est déja notifier pour '.$labels['this'];
                        return 0;
                    }
                }
                else{
                    global $user;
                    $id_user = BimpTools::getPostFieldValue('id_user', $user->id);
                    if (!$id_user) {
                        $errors[] = 'Aucun utilisateur connecté';
                        return 0;
                    }

                    if (array_key_exists($id_user, $this->getUsersFollow(false))) {
                        if ($id_user == $user->id) {
                            $errors[] = 'Vous suivez déjà cette tâche';
                        } else {
                            $errors[] = 'Cet utilisateur suit déjà cette tâche';
                        }
                        return 0;
                    }
                }
                return 1;

            case 'unfollow':
                $email = BimpTools::getPostFieldValue('email', '');
                if($email != ''){
                    if (!in_array($email, $this->getData('emails_follow'))) {
                        $labels = $this->getLabels();
                        $errors[] = 'L\'email '.$email.' n\'est pas notifier pour '.$labels['this'];
                        return 0;
                    }
                }
                else{
                    global $user;
                    $id_user = BimpTools::getPostFieldValue('id_user', $user->id);
                    if (!$id_user) {
                        $errors[] = 'Aucun utilisateur connecté';
                        return 0;
                    }

                    if (in_array($id_user, $this->getData('users_no_follow'))) {
                        $labels = $this->getLabels();
                        if ($id_user == $user->id) {
                            $errors[] = 'Vous avez déjà refusé le suivi de '.$labels['this'];
                        } else {
                            $errors[] = 'Cet utilisateur a déjà refusé le suivi de '.$labels['this'];
                        }
                        return 0;
                    }

                    if (!array_key_exists($id_user, $this->getUsersFollow(false))) {
                        $labels = $this->getLabels();
                        if ($id_user == $user->id) {
                            $errors[] = 'Vous n\'êtes pas notifié pour '.$labels['this'];
                        } else {
                            $errors[] = 'Cet utilisateur n\'est pas notifié pour '.$labels['this'];
                        }
                        return 0;
                    }
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }
}

<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';

class userController extends BimpController
{

    public function displayHead()
    {        
        $b_user = $this->config->getObject('', 'user');
        $head = user_prepare_head($b_user->dol_object);
        dol_fiche_head($head, 'formSimple', 'Mes infos', -1, 'user');
    }

    public function renderHtml()
    {
        $html = '';

        global $user;
        $b_user = $this->config->getObject('', 'user');

        if ($user->id == $b_user->id) {//On est dans le form de l'utilisateur
            $object = $user;
            $droitLire = 1;
            $droitModifSimple = 1;
            $droitModif = 0; //$object->rights->user->self->creer;
        } else {
            $object = $b_user->dol_object;
            $object->getrights('user');
            $droitLire = $user->rights->user->user->lire;
            $droitModifSimple = $user->rights->user->user->creer;
            $droitModif = $droitModifSimple;
        }

        $b_user = $this->config->getObject('', 'user');
        $view = new BC_View($b_user, 'default', false, 1, 'Utilisateur ' . $b_user->getData('login'));

        $full_rights = false;
        if ($droitModif) {
            $view->params['edit_form'] = 'default';
        } elseif ($droitModifSimple) {
            $view->params['edit_form'] = 'light';
        } elseif ($droitLire) {
            $view->params['edit_form'] = null;
        }

        if ($droitLire) {
            $html .= $view->renderHtml();
        } else
            $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission de voir cette page');

        return $html;
    }
}

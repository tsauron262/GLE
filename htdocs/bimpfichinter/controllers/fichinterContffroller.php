<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';

class fichinterController extends BimpController
{

    public function displayHead()
    {        
//        $b_user = $this->config->getObject('', 'user');
//        $head = user_prepare_head($b_user->dol_object);
//        dol_fiche_head($head, 'formSimple', 'Essentiel', -1, 'user');
    }

    public function renderHtml()
    {
        $html = '';


        $b_user = $this->config->getObject('', 'user');
        $view = new BC_View($b_user, 'default');


            $html .= $view->renderHtml();

        return $html;
    }
}


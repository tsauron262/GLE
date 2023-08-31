<?php

class userController extends BimpController
{

    public function renderUserDemandesList($type)
    {
        switch ($type) {
            case 'user_to_process':
                $title = 'Mes demandes (Valideur)';
                $name = 'user';
                break;

            case 'user_demandes':
                $title = 'Mes demandes (Demandeur)';
                $name = 'user';
                break;

            case 'all':
                $title = 'Toutes les demandes';
                $name = 'default';
                break;
        }

        $demande = BimpObject::getInstance('bimpvalidation', 'BV_Demande');
        $list = new BC_ListTable($demande, $name, 1, null, $title, 'far_check-circle');

        global $user;
        switch ($type) {
            case 'user_to_process':
                $list->addFieldFilterValue('id_user_affected', $user->id);
                break;

            case 'user_demandes':
                $title = 'Mes demandes en attente (Demandeur)';
                $list->addFieldFilterValue('id_user_demande', $user->id);
                break;
        }

        return $list->renderHtml();
    }
}

<?php

class usersController extends BimpController
{

    public function renderRightsTab()
    {
        $tabs = array();

        $userRight = BimpObject::getInstance('bimpcore', 'Bimp_UserRight');
        $list = new BC_ListTable($userRight, 'default', 1, null, 'Droits utlisateurs', 'fas_user');

        $tabs[] = array(
            'id'      => 'users_rights',
            'title'   => BimpRender::renderIcon('fas_user', 'iconLeft') . 'Droits utilisateurs',
            'content' => $list->renderHtml()
        );

        $userGroupRight = BimpObject::getInstance('bimpcore', 'Bimp_UserGroupRight');
        $list = new BC_ListTable($userGroupRight, 'default', 1, null, 'Droits groupes', 'fas_users');

        $tabs[] = array(
            'id'      => 'groups_rights',
            'title'   => BimpRender::renderIcon('fas_users', 'iconLeft') . 'Droits groupes',
            'content' => $list->renderHtml()
        );

        return BimpRender::renderNavTabs($tabs, 'rights');
    }
}

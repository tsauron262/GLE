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

    public function renderTestButton()
    {
        $html = '';

        $ac = BimpObject::getInstance('bimpcore', 'Bimp_ActionComm');
        $title = 'Ajout d\\\'un événement';
        $values = array(
            'fields' => array(
                'datep'          => '2023-03-31 15:00:00',
                'datep2'         => '2023-03-31 16:00:00',
                'users_assigned' => array(270),
                'fk_soc' => 946,
                'contacts_assigned' => array(243143, 243139)
            )
        );
        $onclick = $ac->getJsLoadModalForm('add', $title, $values);

        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
        $html .= 'TEST';
        $html .= '</span>';
        return $html;
    }
}

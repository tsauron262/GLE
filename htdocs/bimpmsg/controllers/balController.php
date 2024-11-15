<?php

class balController extends BimpController
{

    public function renderHtml()
    {
        $balType = BimpTools::getValue('bal_type');
        $balValue = BimpTools::getValue('bal_value');
        $dest = BimpTools::getValue('dest', true);

        $html = '<div class="row">';
//        if(!isset($balType) || !$balType || !isset($balValue) || !$balValue)
        $html .= '<div class="col-sm-4 col-md-3">' . BimpRender::renderPanel('Menu', $this->menu()) . '</div>';

        if (isset($balType) && $balType && isset($balValue) && $balValue)
            $html .= '<div class="col-sm-12 col-md-9">' . BimpRender::renderPanel('Messages', $this->getListBal($balType, $balValue, $dest)) . '</div>';

        $html .= '</div>';
        return $html;
    }

    public function getListBal($balType, $balValue, $dest = true)
    {
        $note = BimpObject::getInstance('bimpcore', 'BimpNote');
        $list = new BC_ListTable($note, 'bal');
        if ($balType == 'user') {
            if ($dest) {
                $list->addFieldFilterValue('type_dest', 1);
                $list->addFieldFilterValue('fk_user_dest', $balValue);
            } else {
                $list->addFieldFilterValue('user_create', $balValue);
                $list->addFieldFilterValue('type_dest', array('operator' => ">", "value" => 0));
            }
        } else {
            $list->addFieldFilterValue('type_dest', BimpNote::BN_DEST_GROUP);
            $list->addFieldFilterValue('fk_group_dest', $balValue);
        }

        $list2 = clone($list);

        $list->addFieldFilterValue('viewed', 0);
        $list->params['title'] = 'Non Lue';
        $list->identifier = 'nl';
        $list2->params['title'] = 'Tous';
        $list2->identifier = 'tous';
        $html = $list->renderHtml() . $list2->renderHtml();
        return $html;
    }

    public function menu()
    {
        ini_set('display_errors', 1);

        $html = '';

        $button = array();

        global $user, $db, $langs;

        $idsUsers = array($user->id => $user->getFullName($langs));
        $users_ids = array($user->id);

        $html .= '<h4 style="margin-bottom: 20px; padding-bottom: 5px; border-bottom: 1px solid #636363">' . BimpRender::renderIcon('fas_user', 'iconLeft') . 'Boîtes Perso</h4>';
        
        if ($user->admin) {
            $idsUsers[1] = 'Admin';
        }

        $bdb = BimpCache::getBdb();
        $users_delegations = $bdb->getValues('user', 'rowid', 'delegations LIKE \'%[' . $user->id . ']%\'');
        if (!empty($users_delegations)) {
            foreach ($users_delegations as $id_user) {
                $u = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                if (BimpObject::objectLoaded($u)) {
                    $idsUsers[$id_user] = $u->getName();
                    $users_ids[] = $id_user;

                    $html .= BimpRender::renderAlerts($u->getName() . ' vous a accordé l\'accès à ses messages', 'info');
                }
            }
        }

        foreach ($idsUsers as $idUser => $name) {
            $filters = array('type_dest' => 1, 'fk_user_dest' => $idUser, 'viewed' => 0);
            $list = BimpCache::getBimpObjectList('bimpcore', 'BimpNote', $filters);
            $button = array(
                'label'   => 'Boite Personnel ' . $name . ' ' . BimpTools::getBadge(count($list)),
                'icon'    => 'fas_comment',
                'onclick' => 'window.location.href = \'?fc=bal&bal_type=user&bal_value=' . $idUser . '\';',
                'url'     => 'google.com'
            );
            if ($idUser == BimpTools::getValue('bal_value') && BimpTools::getValue('bal_type') == 'user' && BimpTools::getValue('dest', true) == 1)
                $button['classes'] = array('btn-primary');
            $html .= BimpRender::renderButton($button) . '<br/>';

            $filters = array('type_dest' => 1, 'fk_user_dest' => $idUser, 'viewed' => 0);
            $list = BimpCache::getBimpObjectList('bimpcore', 'BimpNote', $filters);
            $button = array(
                'label'   => 'Boite Envoi ' . $name,
                'icon'    => 'fas_comment',
                'onclick' => 'window.location.href = \'?fc=bal&bal_type=user&dest=0&bal_value=' . $idUser . '\';',
                'url'     => 'google.com'
            );
            if ($idUser == BimpTools::getValue('bal_value') && BimpTools::getValue('bal_type') == 'user' && BimpTools::getValue('dest', true) == 0)
                $button['classes'] = array('btn-primary');
            $html .= BimpRender::renderButton($button) . '<br/>';
        }

        $idsGroups = array();

        if ($user->admin)
            $orOrAnd = 'OR';
        else
            $orOrAnd = 'AND';

        $sql = $db->query('SELECT rowid, nom FROM llx_usergroup WHERE rowid IN (SELECT DISTINCT(fk_group_dest) FROM `llx_bimpcore_note`) ' . $orOrAnd . ' rowid IN (SELECT fk_usergroup FROM llx_usergroup_user WHERE fk_user IN (' . implode(',', $users_ids) . '));');
//        else
//            $sql = $db->query('SELECT rowid, nom FROM llx_usergroup WHERE rowid IN (SELECT fk_usergroup FROM llx_usergroup_user WHERE fk_user = '.$user->id.' AND fk_usergroup IN (SELECT DISTINCT(fk_group_dest) FROM `llx_bimpcore_note` WHERE viewed = 0));');
        while ($ln = $db->fetch_object($sql)) {
            $idsGroups[$ln->rowid] = $ln->nom;
        }

        if (!empty($idsGroups)) {
            $html .= '<h4 style="margin: 20px 0; padding-bottom: 5px; border-bottom: 1px solid #636363">' . BimpRender::renderIcon('fas_users', 'iconLeft') . 'Boîtes Groupes</h4>';

            foreach ($idsGroups as $idGroupe => $name) {
                $filters = array('type_dest' => BimpNote::BN_DEST_GROUP, 'fk_group_dest' => $idGroupe, 'viewed' => 0);
                $list = BimpCache::getBimpObjectList('bimpcore', 'BimpNote', $filters);
                $button = array(
                    'label'   => 'Boite ' . $name . ' ' . BimpTools::getBadge(count($list)),
                    'icon'    => 'fas_comment',
                    'onclick' => 'window.location.href = \'?fc=bal&bal_type=group&bal_value=' . $idGroupe . '\';',
                    'url'     => 'google.com'
                );
                if ($idGroupe == BimpTools::getValue('bal_value') && BimpTools::getValue('bal_type') == 'group')
                    $button['classes'] = array('btn-primary');
                $html .= BimpRender::renderButton($button) . '<br/>';
            }
        }

//        $html .= BimpRender::renderButtonsGroup($button, $params);


        return $html;
    }
}

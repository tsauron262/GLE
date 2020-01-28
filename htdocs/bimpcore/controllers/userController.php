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
        $tabs = array();

        $b_user = $this->config->getObject('', 'user');

        $view = new BC_View($b_user, 'default', false, 1, 'Utilisateur ' . $b_user->getData('login'));

        $tabs[] = array(
            'id'      => 'infos',
            'title'   => BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos',
            'content' => $view->renderHtml()
        );

        $title = $b_user->getName() . ' - Liste des commissions';
        $commission = BimpObject::getInstance('bimpfinanc', 'BimpCommission');

        $list = new BC_ListTable($commission, 'user', 1, null, $title, $commission->params['icon']);
        $list->addFieldFilterValue('type', BimpCommission::TYPE_USER);
        $list->addFieldFilterValue('id_user', (int) $b_user->id);

        $tabs[] = array(
            'id'      => 'commissions',
            'title'   => BimpRender::renderIcon($commission->params['icon'], 'iconLeft') . 'Commissions',
            'content' => $list->renderHtml()
        );

        return BimpRender::renderNavTabs($tabs);
    }
}

<?php

class userController extends BimpController
{

    public function displayHead()
    {
        global $langs, $user;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
        $userS = $this->config->getObject('', 'user')->dol_object;
        $head = user_prepare_head($userS);
        dol_fiche_head($head, 'bimplogisitquecommande', $langs->trans("CustomerOrder"), -1, 'order');
        
	$linkback = '';
	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
	}
	
        dol_banner_tab($userS,'id',$linkback,$user->rights->user->user->lire || $user->admin, 'rowid', 'ref', '', '&fc=user');
    }

    public function renderHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de l\'utilisateur absent');
        }

        $frais_user = $this->config->getObject('', 'user');
        if (!BimpObject::objectLoaded($frais_user)) {
            return BimpRender::renderAlerts('Utilisateur non trouvé pour l\'ID ' . BimpTools::getValue('id', ''));
        }

        global $user, $langs;
        $html = '';

        $html .= '<div class="page_content container-fluid">';
        $html .= '<h1>' . $frais_user->dol_object->getFullName($langs) . ' - Notes de frais par quinzaines</h1>';

        $instance = BimpObject::getInstance('bimprh', 'BRH_Period');
        $errors = $instance->checkUserPeriods($frais_user->id);

        if (count($errors)) {
            $msg = 'Echec de la création de la période en cours pour cet utilisateur';
            $errors = BimpTools::merge_array(array($msg), $errors);
            $html .= BimpRender::renderAlerts($errors);
        }

        $list = new BC_ListTable($instance, 'user', 1, null, 'Liste des quinzaines', 'far_calendar-alt');
        $list->addFieldFilterValue('id_user', $frais_user->id);
        $html .= $list->renderHtml();

        $html .= '</div>';

        return $html;
    }
}

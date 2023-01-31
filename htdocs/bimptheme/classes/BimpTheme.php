<?php

class BimpTheme
{

    public static $files = array(
        'css' => array(
            'iconkit' => '/bimpcore/views/css/iconkit.min.css'
        ),
        'js'  => array(
            'theme'      => '/bimptheme/views/js/theme.js',
//            'theme'      => '/bimptheme/views/js/theme.min.js',
            'scrollbar'  => '/bimptheme/views/js/perfect-scrollbar.min.js',
            'screenfull' => '/bimptheme/views/js/screenfull.js',
            'bimptheme'  => '/bimptheme/views/js/bimptheme.js'
        )
    );

    public static function initLayout()
    {
        $layout = BimpLayout::getInstance();

        if (is_a($layout, 'BimpThemeLayout')) {
            foreach (self::$files['css'] as $css_file) {
                $layout->addCssFile(BimpCore::getFileUrl($css_file, true, false));
            }
            foreach (self::$files['js'] as $js_file) {
                $layout->addJsFile(BimpCore::getFileUrl($js_file, true, false));
            }

            self::initTopNavHtml($layout);
            self::initTopSearchHtml($layout);
            self::initTopDevToolsHtml($layout);
            self::initTopUserToolsHtml($layout);
            self::initTopAccountHtml($layout);
        }
    }

    public static function initTopNavHtml(BimpThemeLayout $layout)
    {
        global $conf;
        $layout->top_nav_html .= '<a href="' . DOL_URL_ROOT . '/" class="nav-link bs-popover header-icon"';
        $layout->top_nav_html .= BimpRender::renderPopoverData('Accueil', 'bottom');
        $layout->top_nav_html .= '>';
        $layout->top_nav_html .= BimpRender::renderIcon('fas_home');
        $layout->top_nav_html .= '</a>';

        $layout->top_nav_html .= '<button id="navbar-fullscreen" class="nav-link bs-popover header-icon"';
        $layout->top_nav_html .= BimpRender::renderPopoverData('Plein écran', 'bottom') . '>';
        $layout->top_nav_html .= BimpRender::renderIcon('fas_expand');
        $layout->top_nav_html .= '</button>';

//        $layout->top_nav_html .= '<button class="nav-link header-icon bs-popover"';
//        $layout->top_nav_html .= BimpRender::renderPopoverData('Historique de navigation', 'bottom');
//        $layout->top_nav_html .= '>';
//        $layout->top_nav_html .= BimpRender::renderIcon('fas_history');
//        $layout->top_nav_html .= '</button>';

        if (isset($conf->global->MAIN_MODULE_SYNOPSISHISTO)) {
            $content = '';

            if (!class_exists('histoNavigation')) {
                require_once DOL_DOCUMENT_ROOT . '/synopsishisto/class/actions_synopsishisto.class.php';
            }

            $tabElem = getTypeAndId();
            $element_type = $tabElem[0];
            $element_id = $tabElem[1];

            histoNavigation::saveHisto($element_type, $element_id);
            $content .= '<div class="userHistoContent">';
            $content .= histoNavigation::getBlocHisto(0);
            $content .= '</div>';

            $label = BimpRender::renderIcon('fas_history');
            $layout->top_nav_html .= BimpRender::renderDropDownContent('userNavHistory', $label, $content, array(
                        'type'        => 'span',
                        'extra_class' => 'nav-link header-icon',
                        'side'        => 'left'
            ));
        }
    }

    public static function initTopSearchHtml(BimpThemeLayout $layout)
    {
        global $db, $langs, $hookmanager;
        $form = new Form($db);
        $selected = -1;
        $usedbyinclude = 1;
        include_once DOL_DOCUMENT_ROOT . '/core/ajax/selectsearchbox.php';
        $layout->top_search_html .= $form->selectArrayAjax('searchselectcombo', DOL_URL_ROOT . '/core/ajax/selectsearchbox.php', $selected, '', '', 0, 1, 'vmenusearchselectcombo', 1, $langs->trans("Search"), 1);
    }

    public static function initTopDevToolsHtml(BimpThemeLayout $layout)
    {
        if (BimpCore::isUserDev()) {
            
        }
    }

    public static function initTopUserToolsHtml(BimpThemeLayout $layout)
    {
        $layout->top_user_tools_html .= self::renderUserMessagesIcon();

        $toprightmenu = '';
        global $hookmanager;
        $hookmanager->initHooks(array('toprightmenu'));

        $parameters = array();
//        hooks should output string like '<div class="login"><a href="">mylink</a></div>'
        $result = $hookmanager->executeHooks('printTopRightMenu', $parameters);    // Note that $action and $object may have been modified by some hooks
        if (is_numeric($result)) {
            if ($result == 0)
                $toprightmenu .= $hookmanager->resPrint;  // add
            else
                $toprightmenu = $hookmanager->resPrint;   // replace
        } else {
            $toprightmenu .= $result; // For backward compatibility
        }

        if ($toprightmenu) {
            $layout->top_user_tools_html .= $toprightmenu;
        }
    }

    public static function initTopAccountHtml(BimpThemeLayout $layout)
    {
        global $user;
        $label = Form::showphoto('userphoto', $user, 28, 28, 0, '', 'mini', 0);
//        $label = '<img class="avatar" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=1&file=' . $user->photo . '" alt="" style="display: inline-block; margin-right: 12px">';
        $label .= '<span class="mobile-hidden">' . $user->firstname . ' ' . $user->lastname . '</span>';

        $content = '';
        $content .= '<div style="padding: 15px;">';

        // Mon profile: 
        $content .= '<div style="margin-bottom: 12px">';
        $content .= '<a href="' . DOL_URL_ROOT . '/bimpcore/index.php?fc=user&id=' . $user->id . '">';
        $content .= BimpRender::renderIcon('fas_user', 'iconLeft') . 'Mon profil';
        $content .= '</a>';
        $content .= '</div>';

        // Mon agenda: 
        $content .= '<div style="margin-bottom: 12px">';
        $content .= '<a href="' . DOL_URL_ROOT . '/synopsistools/agenda/vue.php">';
        $content .= BimpRender::renderIcon('fas_calendar-alt', 'iconLeft') . 'Mon Agenda';
        $content .= '</a>';
        $content .= '</div>';

        // Ma messagerie: 
        $content .= '<div style="margin-bottom: 12px">';
        $content .= '<a href="' . DOL_URL_ROOT . '/bimpmsg/index.php?fc=bal">';
        $content .= BimpRender::renderIcon('fas_envelope-open-text', 'iconLeft') . 'Ma messagerie';
        $content .= '</a>';
        $content .= '</div>';

        // Toutes mes tâches:
        $content .= '<div style="margin-bottom: 12px">';
        $content .= '<a href="' . DOL_URL_ROOT . '/bimpcore/index.php?fc=user&id=' . $user->id . '&navtab-maintabs=tasks&navtab-tasks=my_tasks">';
        $content .= BimpRender::renderIcon('fas_tasks', 'iconLeft') . 'Toutes mes tâches';
        $content .= '</a>';
        $content .= '</div>';

        // Logout: 
        $content .= '<div style="margin-top: 10px; text-align: center">';
        $content .= '<a class="btn btn-danger" href="' . DOL_URL_ROOT . '/user/logout.php">';
        $content .= BimpRender::renderIcon('fas_power-off', 'iconLeft') . 'Se déconnecter';
        $content .= '</a>';
        $content .= '</div>';

        $content .= '</div>';
        $layout->top_account_html .= BimpRender::renderDropDownContent('userDropdown', $label, $content, array(
                    'type'        => 'span',
                    'extra_class' => 'dropdown-profile modifDropdown'
        ));
    }

    // Rendus HTML: 

    public static function renderUserMessagesIcon()
    {
        global $user;
        $note_instance = BimpObject::getInstance('bimpcore', "BimpNote");
        $nbMessage = count($note_instance->getList(["fk_user_dest" => $user->id, "viewed" => 0, "auto" => 0]));

        $html = '';

        $html .= '<div class="dropdown modifDropdown login_block_other">';

        if (BimpCore::isModuleActive('bimptask') && BimpCore::getConf('allow_bug_task', null, 'bimptask')) {
            $task = BimpObject::getInstance('bimptask', 'BIMP_Task');
            $html .= '<span class="bs-popover header-icon"';
            $html .= BimpRender::renderPopoverData('Signaler un bug', 'bottom');
            $html .= ' onclick="' . $task->getJsLoadModalForm('bug', 'Signaler un bug', array(
                        'fields' => array(
                            'comment' => $task->getDefaultBugCommentInputValue()
                        )
                    )) . '"';
            $html .= '>';
            $html .= BimpRender::renderIcon('fas_bug');
            $html .= '</span>';
        }

        $html .= '<a class="nav-link dropdown-toggle" href="#" id="notiDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= BimpRender::renderIcon('fas_comments');

        if ($nbMessage) {
            $html .= '<span class="badge bg-danger">' . $nbMessage . '</span>';
        }

        $html .= '</a>';
        $html .= '<div class="dropdown-menu dropdown-menu-right notification-dropdown" aria-labelledby="notiDropdown">';
        $html .= '<h4 class="header">Notifications</h4>';

        $html .= '<div class="notifications-wrap">';
        $html .= '</div>';

        $html .= '<div class="footer">';
        $html .= '<a href="javascript:void(0);">See all activity</a>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

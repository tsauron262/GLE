<?php

class BimpThemeLayout extends BimpLayout
{

    public $top_nav_html = '';
    public $top_search_html = '';
    public $top_dev_tools_html = '';
    public $top_user_tools_html = '';
    public $top_account_html = '';
    public $menu_items = array();

    // Affichages: 

    public function displayTop()
    {
        global $conf;

        echo '<div id="BimpThemeWrapper" class="wrapper">' . "\n";
        if (empty($conf->dol_hide_topmenu) || GETPOST('dol_invisible_topmenu', 'int')) {
            echo $this->renderTop();
        }
    }

    public function displayLeft()
    {
        global $conf;

        echo '<div class="page-wrap">' . "\n";

        if (empty($conf->dol_hide_leftmenu)) {
            echo $this->renderLeftMenu();
        }

        echo '<div class="main-content" style="background-color: white !important;">' . "\n";
    }

    public function displayBottom()
    {
        echo '</div><!-- End .main-content -->' . "\n";
        echo '</div><!-- End .page-wrap -->' . "\n";
        echo '</div><!-- End #BimpThemeWrapper -->' . "\n";
    }

    // Rendus HTML: 

    protected function renderTop()
    {
        $html = '';

        $html .= '<header class="header-top" header-theme="light">';
        $html .= '<div class="container-fluid" >';
        $html .= '<div class="d-flex justify-content-between">';

        // Toolbar left: 
        $html .= '<div class="top-menu d-flex align-items-center pull-left">';
        $html .= '<span class="mobile-visible">';
        $html .= '<button type="button" id="openSideBarButton" class="nav-link header-icon" onclick=""><span>';
        $html .= BimpRender::renderIcon('fas_bars');
        $html .= '</span></button>';
        $html .= '</span>';
        $html .= $this->top_nav_html;
        $html .= $this->top_search_html;
        $html .= '</div>';

        // Toolbar right: 
        $html .= '<div class="bimpTopMenuRight">';
        $html .= '<div class="top-menu d-flex align-items-center pull-right">';
        $html .= $this->top_dev_tools_html;
        $html .= $this->top_user_tools_html;
        $html .= $this->top_account_html;
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</header>' . "\n\n";

        return $html;
    }

    protected function renderLeftMenu()
    {
        $html = '';

        $html .= '<div id="id-container" class="id-container">'; // Pour compatibilité llx_footer
        $html .= '<div class="app-sidebar colored">' . "\n";
        $html .= '<div class="sidebar-header">' . "\n";

        $html .= '<a class="header-brand" href="' . DOL_URL_ROOT . '/">' . "\n";
        $html .= '<div id="logo-img" class="logo-img">';
        if (BimpCore::getExtendsEntity() != '' && file_exists(DOL_DOCUMENT_ROOT . '/bimptheme/extends/entities/' . BimpCore::getExtendsEntity() . '/logo.png')) {
            $html .= '<img src="' . DOL_URL_ROOT . '/bimptheme/extends/entities/' . BimpCore::getExtendsEntity() . '/logo.png" class="header-brand-img" alt="Logo" width="125">';
        } else {
            global $conf;
            $logo_file = $conf->mycompany->dir_output . '/logos/' . $conf->global->MAIN_INFO_SOCIETE_LOGO;
            if(is_file($logo_file)){
                $html .= '<img src="' . DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=logos%2F'.$conf->global->MAIN_INFO_SOCIETE_LOGO.'" class="header-brand-img" alt="Logo" width="125">';
            }
            else{
                $html .= 'GLE';
            }
        }
        $html .= '</div>';
        $html .= '</a>' . "\n";

        $html .= '<button id="sidebarOpen" onclick="hideBimpLogo();" type="button" class="nav-toggle"><i data-toggle="expanded" class="ik ik-toggle-right toggle-icon"></i></button>';
        $html .= '<button id="sidebarClose" class="nav-close"><i class="ik ik-x"></i></button>';
        $html .= '</div>' . "\n"; // .sidebar-header

        $html .= '<div class="sidebar-content">' . "\n";
        $html .= '<div class="nav-container">' . "\n";
        $html .= '<nav id="main-menu-navigation" class="navigation-main">' . "\n";

        BimpObject::loadClass('bimptheme', 'Bimp_Menu');
        $items = Bimp_Menu::getFullMenu(null, true, true, true);
        $html .= $this->renderMenuItems($items, 1);

        // todo: à refondre: 
//        BimpObject::loadClass('bimpsupport', 'BS_SAV');
//        $html .= BS_SAV::renderMenuQuickAccess();
        
        $html .= '<div class="bimptheme_menu_extra_section">';
        global $hookmanager, $user, $db, $conf, $langs;
        if (!empty($conf->bookmark->enabled) && $user->rights->bookmark->lire) {
            include_once (DOL_DOCUMENT_ROOT . '/bookmarks/bookmarks.lib.php');
            $langs->load("bookmarks");

            $html .= printDropdownBookmarksList($db, $langs);
        }
        
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printLeftBlock', $parameters);    // Note that $action and $object may have been modified by some hooks
        $html .= $hookmanager->resPrint;

//        $parameters = array();
//        $reshook = $hookmanager->executeHooks('printMenuAfter', $parameters);    // Note that $action and $object may have been modified by some hooks
//        $html .= $hookmanager->resPrint;
        $html .= '</div>';

        $html .= ' </nav><!-- End #main-menu-navigation -->' . "\n";
        $html .= '</div><!-- End .nav-container-->' . "\n";
        $html .= '</div><!-- End .sidebar-content -->' . "\n";
        $html .= '</div><!-- End .app-sidebar -->' . "\n";

        return $html;
    }

    protected function renderMenuItems($items)
    {
        $html = '';
        global $langs;

        foreach ($items as $item_data) {
            $titre = $item_data['titre'];
            
            $language = BimpTools::getArrayValueFromPath($item_data, 'langs', '');
            if ($language) {
                $langs->load($language);
                $titre = $langs->trans($titre);
            }

            if (isset($item_data['sub_items']) && !empty($item_data['sub_items'])) {
                $html .= '<div class="nav-item has-sub">';
                $html .= '<a class="menu-item" href="javascript:void(0)">';
                $html .= BimpRender::renderIcon(BimpTools::getArrayValueFromPath($item_data, 'bimp_icon', 'fas_bars'));
                $html .= '<span> ' . $titre . '</span>';
                $html .= '</a>';

                $html .= '<div class="submenu-content">';
                $url = BimpTools::getArrayValueFromPath($item_data, 'url', '');
                if ($url) {
                    $url = DOL_URL_ROOT . (!preg_match('/^\/.+$/', $url) ? '/' : '') . $url;
                    $html .= '<a class="menu-item" href="' . $url . '">';
                    $html .= '<span> ' . $titre . '</span>' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                    $html .= '</a>';
                }

                $html .= $this->renderMenuItems($item_data['sub_items']);

                $html .= '</div>';
                $html .= '</div>';
            } else {
                $url = BimpTools::getArrayValueFromPath($item_data, 'url', '');
                if ($url) {
                    $url = DOL_URL_ROOT . (!preg_match('/^\/.+$/', $url) ? '/' : '') . $url;
                }
                $html .= '<div class="nav-item">';
                $html .= '<a class="menu-item" href="' . $url . '">';
                $html .= BimpRender::renderIcon(BimpTools::getArrayValueFromPath($item_data, 'bimp_icon', 'fas_bars'));
                $html .= '<span>' . $titre . '</span></a>';
                $html .= '</div>';
            }
        }
        return $html;
    }
}

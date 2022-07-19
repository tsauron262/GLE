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
//        $html .= '<button type="button" id="responsiveButton" class="btn-icon mobile-nav-toggle"><span></span></button>';
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
        $html .= '<img src="' . DOL_URL_ROOT . '/theme/BimpTheme/views/imgs/bimp-logo.png" class="header-brand-img" alt="Bimp logo" width="125">';
        $html .= '</div>';
        $html .= '</a>' . "\n";

        $html .= '<button id="sidebarOpen" onclick="hideBimpLogo();" type="button" class="nav-toggle"><i data-toggle="expanded" class="ik ik-toggle-right toggle-icon"></i></button>';
        $html .= '<button id="sidebarClose" class="nav-close"><i class="ik ik-x"></i></button>';
        $html .= '</div>' . "\n"; // .sidebar-header

        $html .= '<div class="sidebar-content">' . "\n";
        $html .= '<div class="nav-container">' . "\n";
        $html .= '<nav id="main-menu-navigation" class="navigation-main">' . "\n";

        global $db, $langs;
        $tableLangs = ["commercial"];

        foreach ($tableLangs as $nomLang) {
            $langs->load($nomLang);
        }

        $sql = $db->query('SELECT * FROM `' . MAIN_DB_PREFIX . 'menu` WHERE `type` = "top" ORDER BY `position`');

        while ($ln = $db->fetch_object($sql)) {
            $html .= $this->renderMenuAndSubMenu($ln->rowid, 1);
        }

        $html .= ' </nav><!-- End #main-menu-navigation -->' . "\n";
        $html .= '</div><!-- End .nav-container-->' . "\n";
        $html .= '</div><!-- End .sidebar-content -->' . "\n";
        $html .= '</div><!-- End .app-sidebar -->' . "\n";

        return $html;
    }

    protected function renderMenuAndSubMenu($id, $niveau = 1)
    {
        global $db, $langs;

        $html = $htmlSub = "";

        $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "menu WHERE rowid = " . $id);
        if ($db->num_rows($sql) > 0) {
            $res = $db->fetch_object($sql);

            if ($res->perms != '') {
                $test = 'if(' . $res->perms . ') {  }else{ return ""; }';

                eval($test);
            }

            if ($res->langs != '')
                $langs->load($res->langs);

            $sql2 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "menu WHERE fk_menu = " . $id . " ORDER BY position ASC");
            if ($db->num_rows($sql2) > 0) {
                $menu_icon = (!is_null($res->icon)) ? $res->icon : "bars";

                $htmlSub .= '<div class="submenu-content">';
                $htmlSub .= '<a class="menu-item" href="' . DOL_URL_ROOT . '/' . $res->url . '"><span> ' . $langs->trans($res->titre) . '</span></a>';

                while ($res2 = $db->fetch_object($sql2)) {
                    $htmlSub .= $this->renderMenuAndSubMenu($res2->rowid, $niveau + 1);
                }

                $htmlSub .= '</div>';

                $html .= '<div class="nav-item has-sub">';
                $html .= '<a class="menu-item" href="javascript:void(0)">' . BimpRender::renderIcon($menu_icon) . '<span> ' . $langs->trans($res->titre) . '</span></a>';

                $html .= $htmlSub;
                $html .= '</div>';
            } else {
                $menu_icon = (!is_null($res->icon)) ? BimpRender::renderIcon($res->icon) : "";

                $html .= '<div class="nav-item">';
                $html .= '<a class="menu-item" href="' . DOL_URL_ROOT . '/' . $res->url . '">' . $menu_icon . '<span>' . $langs->trans($res->titre) . '</span></a>';
                $html .= '</div>';
            }
        }

        return $html;
    }
}

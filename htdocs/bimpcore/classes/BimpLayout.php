<?php


class BimpLayout
{

    public static function displayHeaderTop()
    {
        global $hookmanager, $langs, $db;

        echo '<header class="header-top" header-theme="light">';

        echo '<div class="container-fluid" >';
        echo '<div class="d-flex justify-content-between">';

        // Toolbar left: 
        echo '<div class="top-menu d-flex align-items-center pull-left">';
        // Bouton menu responsive:
        echo '<button type="button" id="responsiveButton" class="btn-icon mobile-nav-toggle"><span></span></button>';

        echo '<a href="' . DOL_URL_ROOT . '/" class="nav-link bs-popover header-icon"';
        echo BimpRender::renderPopoverData('Accueil', 'bottom');
        echo '>';
        echo BimpRender::renderIcon('fas_home');
        echo '</a>';

        echo '<button id="navbar-fullscreen" class="nav-link bs-popover header-icon"';
        echo BimpRender::renderPopoverData('Plein écran', 'bottom') . '>';
        echo BimpRender::renderIcon('fas_expand');
        echo '</button>';

        echo '<button class="nav-link header-icon bs-popover"';
        echo BimpRender::renderPopoverData('Historique de navigation', 'bottom');
        echo '>';
        echo BimpRender::renderIcon('fas_history');
        echo '</button>';

        $form = new Form($db);
        $selected = -1;
        $usedbyinclude = 1;
        include_once DOL_DOCUMENT_ROOT . '/core/ajax/selectsearchbox.php';
        echo $form->selectArrayAjax('searchselectcombo', DOL_URL_ROOT . '/core/ajax/selectsearchbox.php', $selected, '', '', 0, 1, 'vmenusearchselectcombo', 1, $langs->trans("Search"), 1);

        echo '</div>';

        echo '<div class="modifMenuTopRight">';
        echo '<div class="top-menu d-flex align-items-center pull-right">';

        echo displayMessageIcone();
        echo displayAcountIcone();

        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</header>';
    }

    public static function renderFooter()
    {
        
    }

    protected static function renderTop()
    {
        $html = '';

//        BimpObject::loadClass('bimpcore', 'BimpAlert'); // A placer correctement... 
//        $html .= BimpAlert::getMsgs();

        return $html;
    }

    protected function renderMenu()
    {
        $html = '';

        return $html;
    }
}

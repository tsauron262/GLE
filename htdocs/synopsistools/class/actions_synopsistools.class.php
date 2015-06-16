<?php

class ActionsSynopsisTools {

    function doActions($parameters, &$object, &$action, $hookmanager) {
        return 0;
    }
    
    function printSearchForm($parameters, &$object, &$action, $hookmanager) {
        global $conf, $langs;
        $return = '';
        if (isset($conf->global->MAIN_MODULE_SYNOPSISCONTRAT)) {
//            $return .= '<div id="blockvmenusearch" class="blockvmenusearch">';
            $return .= '<form method="get" action="' . DOL_URL_ROOT . '/contrat/list.php">';
            $return .= '<div class="menu_titre menu_titre_search"><a class="vsmenu" href="' . DOL_URL_ROOT . '/contrat/list.php?leftmenu=contracts">
                    ' . img_object("Contrat", "contract") . ' Contrats</a><br></div>';
            $return .= '<input type="text" class="flat" name="search_contract" size="10">';
            $return .= '<input type="submit" value="' . $langs->trans("Go") . '" class="button">';
            $return .= '</form>';
        }
        $this->resprints = $return;
        return 0;
    }
    function printTopRightMenu($parameters, &$object, &$action, $hookmanager) {
        return 0;
    }
    function printMenuAfter($parameters, &$object, &$action, $hookmanager) {
        
        if (isset($conf->global->MAIN_MODULE_SYNOPSISCONTRAT)) {
//            $return .= '<div id="blockvmenusearch" class="blockvmenusearch">';
            $return .= '<form method="get" action="' . DOL_URL_ROOT . '/contrat/list.php">';
            $return .= '<div class="menu_titre menu_titre_search"><a class="vsmenu" href="' . DOL_URL_ROOT . '/contrat/list.php?leftmenu=contracts">
                    ' . img_object("Contrat", "contract") . ' Contrats</a><br></div>';
            $return .= '<input type="text" class="flat" name="search_contract" size="10">';
            $return .= '<input type="submit" value="' . $langs->trans("Go") . '" class="button">';
            $return .= '</form>';
        }
        $this->resprints = $return;
        return 0;
    }
}
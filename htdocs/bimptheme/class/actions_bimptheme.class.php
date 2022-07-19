<?php

require_once DOL_DOCUMENT_ROOT . '/bimptheme/classes/BimpTheme.php';

class Actionsbimptheme
{

    function initBimpLayout($parameters, &$object, &$action, $hookmanager)
    {
        global $conf;

        if ($conf->theme == 'BimpTheme') {
            BimpTheme::initLayout();
        }
    }
}

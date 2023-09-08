<?php

require_once DOL_DOCUMENT_ROOT . '/bimptheme/classes/BimpTheme.php';

class Actionsbimptheme
{
    function __construct() {
        global $conf;
        if ($conf->theme == 'BimpTheme') {
            $conf->global->SYNOPSIS_HISTO_LENGTH = 15;
        }
    }

    function initBimpLayout($parameters, &$object, &$action, $hookmanager)
    {
        global $conf;

        if ($conf->theme == 'BimpTheme') {
            BimpTheme::initLayout();
        }

        return 0;
    }
}

<?php

abstract class Abstract_margeprod extends BimpObject
{

    function canView()
    {
        global $conf, $user;
        if (!isset($conf->global->MAIN_MODULE_BIMPMARGEPROD) || $conf->global->MAIN_MODULE_BIMPMARGEPROD != 1)
            return 0;
        if (!isset($user->rights->bimpmargeprod->read))
            return false;

        return true;
    }
}

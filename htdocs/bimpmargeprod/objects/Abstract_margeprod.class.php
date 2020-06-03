<?php

abstract class Abstract_margeprod extends BimpObject
{

    function canView()
    {
        global $conf, $user;
        if (!$user->admin) {
            if (!isset($conf->global->MAIN_MODULE_BIMPMARGEPROD) || $conf->global->MAIN_MODULE_BIMPMARGEPROD != 1)
                return 0;
            if (!isset($user->rights->bimpmargeprod->read))
                return 0;
        }

        return 1;
    }
}

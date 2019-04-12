<?php


    define("NO_REDIRECT_LOGIN", 1);

require_once '../bimpcore/main.php';

require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';

if(!BimpCore::getConf('module_version_bimpinterfaceclient')) {
    accessforbidden();
}

if(BimpTools::getContext() == 'public' || $user->id < 1)
    require 'client.php';
else
    require 'admin.php';


?>

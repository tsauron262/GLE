<?php

require_once('../main.inc.php');
//die(DOL_DOCUEMNT_ROOT . '/bimpcore/Bimp_Lib.php');
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';


sleep(10);

$version = (int) BimpCore::getConf('git_version', 1)+1;
echo 'version '.$version;
BimpCore::setConf('git_version', $version);

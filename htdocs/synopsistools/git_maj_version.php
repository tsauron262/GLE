<?php

require_once('../main.inc.php');

llxHeader();


sleep(5);

$version = BimpCore::getConf('git_version', 1)+1;
echo 'version '.$version;
BimpCore::setConf('git_version', $version);



llxFooter();
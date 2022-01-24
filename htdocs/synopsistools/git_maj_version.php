<?php

require_once('../main.inc.php');



sleep(5);

$version = BimpCore::getConf('git_version', 1)+1;
echo 'version '.$version;
BimpCore::setConf('git_version', $version);

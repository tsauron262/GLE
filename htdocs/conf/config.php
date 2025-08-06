<?php
$path = str_replace('config.php', '', __FILE__);

require_once $path.'/config-instance.inc.php';
require_once $path.'/config-app.inc.php';
require_once $path.'/config-server.inc.php';
require_once $path.'/config-default.inc.php';

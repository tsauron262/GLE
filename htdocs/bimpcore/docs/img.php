<?php
require_once('../../main.inc.php');
header('Content-type:image/'.pathinfo($_GET['name'],PATHINFO_EXTENSION));
echo file_get_contents(DOL_DATA_ROOT.'/bimpcore/docs/image/'.$_GET['name']);

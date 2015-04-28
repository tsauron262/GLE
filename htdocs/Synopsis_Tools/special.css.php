<?php

require_once('../main.inc.php');

$css = file_get_contents(DOL_DATA_ROOT."/special.css");

echo "<style>".$css."</style>";die;

echo str_replace("\n", "</br>",$css);
?>

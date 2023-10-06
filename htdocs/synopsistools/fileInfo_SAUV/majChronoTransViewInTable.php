<?php

$_REQUEST['createTableSur'] = true;
require_once DOL_DOCUMENT_ROOT.'/synopsischrono/ajax/testCreateView.php';
$tabSql = array("UPDATE ".MAIN_DB_PREFIX."synopsischrono_conf SET picto = replace(picto, '[KEY|1056]', '[KEY|105-Etat]')",
    "UPDATE ".MAIN_DB_PREFIX."Synopsis_Process_lien SET picto = replace(replace(picto, '[KEY|1056]', '[KEY|105-Etat]'),'object_','')",
    "RENAME TABLE  ".MAIN_DB_PREFIX."synopsischrono_value TO  `".MAIN_DB_PREFIX."synopsischrono_valueSAUV`",
    );
$text = "Maj des table chrono";
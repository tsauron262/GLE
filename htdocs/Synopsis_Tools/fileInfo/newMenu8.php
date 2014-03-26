<?php

$tabTableSuppr = array();


$modulesInit = array("SynopsisTools", "SynopsisChrono", "SynopsisPrepaCommande", "SynopsisHotline");




$tabsql = array();
foreach ($tabTableSuppr as $table)
    $tabSql[] = "DROP table " . MAIN_DB_PREFIX . $table;





$text = "Verif lien Tools + Chrono/process";
$php = '';

foreach ($modulesInit as $module)
    $php .= 'require_once(DOL_DOCUMENT_ROOT."/core/modules/mod' . $module . '.class.php");
$module = new mod' . $module . '($db);
echo @$module->init()."<br/>";';


//        $tabSql = array("UPDATE ".MAIN_DB_PREFIX."Synopsis_Chrono_conf set active = 2 where active = 1",
//        "UPDATE ".MAIN_DB_PREFIX."Synopsis_Chrono_conf set active = 1 where active = 0",
//        "UPDATE ".MAIN_DB_PREFIX."Synopsis_Chrono_conf set active = 0 where active = 2");
?>

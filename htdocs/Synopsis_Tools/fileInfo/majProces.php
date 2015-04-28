<?php
$tabSql = array("DROP table ".MAIN_DB_PREFIX."Synopsis_Process_lien",
        "DROP table ".MAIN_DB_PREFIX."synopsischrono_conf",
        "DROP table ".MAIN_DB_PREFIX."synopsischrono_key",
        "DROP table ".MAIN_DB_PREFIX."Synopsis_Process",
        "ALTER TABLE  `".MAIN_DB_PREFIX."Synopsis_contrat_annexePdf` ADD  `type` INT NOT NULL DEFAULT  '1'");
    
$text = "Maj de test";
$php = 'require_once(DOL_DOCUMENT_ROOT."/core/modules/modSynopsisProcess.class.php");
$module = new modSynopsisProcess($db);
echo $module->init()."<br/>";
require_once(DOL_DOCUMENT_ROOT."/core/modules/modSynopsisChrono.class.php");
$module = new modSynopsisChrono($db);
echo $module->init()."<br/>";';


//        $tabSql = array("UPDATE ".MAIN_DB_PREFIX."synopsischrono_conf set active = 2 where active = 1",
//        "UPDATE ".MAIN_DB_PREFIX."synopsischrono_conf set active = 1 where active = 0",
//        "UPDATE ".MAIN_DB_PREFIX."synopsischrono_conf set active = 0 where active = 2");
?>

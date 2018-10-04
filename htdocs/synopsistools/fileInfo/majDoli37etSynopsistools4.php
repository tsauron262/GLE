<?php

$tabSql = array(
    "RENAME TABLE `".MAIN_DB_PREFIX."Synopsis_Tools_bug` TO `".MAIN_DB_PREFIX."synopsistools_bug` ;",
    "RENAME TABLE `".MAIN_DB_PREFIX."Synopsis_Tools_notificationUser` TO `".MAIN_DB_PREFIX."synopsistools_notificationUser` ;",
    "UPDATE ".MAIN_DB_PREFIX."Synopsis_Process_lien SET picto = replace(picto, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE ".MAIN_DB_PREFIX."synopsistools_bug SET text = replace(text, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE ".MAIN_DB_PREFIX."synopsischrono_conf SET picto = replace(picto, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE ".MAIN_DB_PREFIX."cronjob SET module_name = replace(module_name, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE ".MAIN_DB_PREFIX."synopsistools_bug SET text = replace(text, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE ".MAIN_DB_PREFIX."synopsistools_bug SET text = replace(text, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE ".MAIN_DB_PREFIX."const SET value = replace(value, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE ".MAIN_DB_PREFIX."menu SET url = replace(url, 'Synopsis_Tools', 'synopsistools'),  langs = replace(langs, 'Synopsis_Tools', 'synopsistools')",
    
    
    
    "UPDATE ".MAIN_DB_PREFIX."synopsistools_bug SET text = replace(text, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."Synopsis_Process_form_requete SET postTraitement = replace(postTraitement, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."Synopsis_Process_lien SET urlObj = replace(urlObj, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."Synopsis_Process_type_element SET ficheUrl = replace(ficheUrl, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."bookmark SET url = replace(url, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."contrat SET note_public = replace(note_public, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."contratdet SET description = replace(description, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."fichinter SET description = replace(description, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."menu SET url = replace(url, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."synopsiscaldav_event SET agendaplus = replace(agendaplus, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."synopsistools_bug SET text = replace(text, 'fiche.php', 'card.php')",
    "UPDATE ".MAIN_DB_PREFIX."mailing_cibles SET source_url = replace(source_url, 'fiche.php', 'card.php')",
    
    
    
    
    "UPDATE ".MAIN_DB_PREFIX."Synopsis_Histo_User SET element_type = 'synopsischrono' WHERE element_type = 'chrono'",
    "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_MODULE_GOOGLE_HOOKS'",
    "DELETE FROM ".MAIN_DB_PREFIX."boxes WHERE box_id IN (SELECT rowid FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = 'box_demandeInterv.php')",
    "DELETE FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = 'box_demandeInterv.php'",
    "DELETE FROM ".MAIN_DB_PREFIX."boxes WHERE box_id IN (SELECT rowid FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = 'box_synopsisdemandeinterv.php')",
    "DELETE FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = 'box_synopsisdemandeinterv.php'",
    "ALTER TABLE  ".MAIN_DB_PREFIX."holiday_users CHANGE  `nb_holiday_current`  `nb_holiday` DOUBLE NOT NULL DEFAULT  '0' COMMENT  'Année en cours'"
    
);

$activeModule = array("modSynopsistools", "modSynopsisdemandeinterv", "modSynopsisChrono", "modSynopsishisto");

$text = "Maj des table contenant Synopsis_Tools";


//RENAME TABLE `".MAIN_DB_PREFIX."Synopsis_Tools_fileInfo` TO `".MAIN_DB_PREFIX."synopsistools_fileInfo` ;
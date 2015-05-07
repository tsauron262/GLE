<?php

$tabSql = array(
    "RENAME TABLE `llx_Synopsis_Tools_bug` TO `llx_synopsistools_bug` ;",
    "RENAME TABLE `llx_Synopsis_Tools_notificationUser` TO `llx_synopsistools_notificationUser` ;",
    "UPDATE llx_Synopsis_Process_lien SET picto = replace(picto, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE llx_synopsistools_bug SET text = replace(text, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE llx_synopsischrono_conf SET picto = replace(picto, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE llx_cronjob SET module_name = replace(module_name, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE llx_synopsistools_bug SET text = replace(text, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE llx_synopsistools_bug SET text = replace(text, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE llx_const SET value = replace(value, 'Synopsis_Tools', 'synopsistools')",
    "UPDATE llx_menu SET url = replace(url, 'Synopsis_Tools', 'synopsistools'),  langs = replace(langs, 'Synopsis_Tools', 'synopsistools')",
    
    
    
    "UPDATE llx_synopsistools_bug SET text = replace(text, 'fiche.php', 'card.php')",
    "UPDATE llx_Synopsis_Process_form_requete SET postTraitement = replace(postTraitement, 'fiche.php', 'card.php')",
    "UPDATE llx_Synopsis_Process_lien SET urlObj = replace(urlObj, 'fiche.php', 'card.php')",
    "UPDATE llx_Synopsis_Process_type_element SET ficheUrl = replace(ficheUrl, 'fiche.php', 'card.php')",
    "UPDATE llx_bookmark SET url = replace(url, 'fiche.php', 'card.php')",
    "UPDATE llx_contrat SET note_public = replace(note_public, 'fiche.php', 'card.php')",
    "UPDATE llx_contratdet SET description = replace(description, 'fiche.php', 'card.php')",
    "UPDATE llx_fichinter SET description = replace(description, 'fiche.php', 'card.php')",
    "UPDATE llx_menu SET url = replace(url, 'fiche.php', 'card.php')",
    "UPDATE llx_synopsiscaldav_event SET agendaplus = replace(agendaplus, 'fiche.php', 'card.php')",
    "UPDATE llx_synopsistools_bug SET text = replace(text, 'fiche.php', 'card.php')",
    "UPDATE llx_mailing_cibles SET source_url = replace(source_url, 'fiche.php', 'card.php')",
    
    
    
    
    "UPDATE llx_Synopsis_Histo_User SET element_type = 'synopsischrono' WHERE element_type = 'chrono'",
    "DELETE FROM llx_const WHERE name = 'MAIN_MODULE_GOOGLE_HOOKS'",
    "DELETE FROM llx_boxes WHERE box_id IN (SELECT rowid FROM llx_boxes_def WHERE file = 'box_demandeInterv.php')",
    "DELETE FROM llx_boxes_def WHERE file = 'box_demandeInterv.php'",
    "DELETE FROM llx_boxes WHERE box_id IN (SELECT rowid FROM llx_boxes_def WHERE file = 'box_synopsisdemandeinterv.php')",
    "DELETE FROM llx_boxes_def WHERE file = 'box_synopsisdemandeinterv.php'"
    
);

$activeModule = array("modSynopsisdemandeinterv", "modSynopsisChrono", "modSynopsishisto");

$text = "Maj des table contenant Synopsis_Tools";


//RENAME TABLE `llx_Synopsis_Tools_fileInfo` TO `llx_synopsistools_fileInfo` ;
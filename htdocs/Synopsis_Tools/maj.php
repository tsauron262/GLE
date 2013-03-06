<?php

/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../main.inc.php');

$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";
llxHeader("", "Importation de données");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Importation de données"));


if ($user->rights->SynopsisTools->Global->import != 1) {
    print "Ce module ne vous est pas accessible";
    llxFooter();
    exit(0);
}




if (isset($_GET['action']) && $_GET['action'] == "import") {
    $dbD = $db;
//$dbS = getDoliDBInstance($conf->db->type, "127.0.0.1", "root", "roland2007", "gle1main", $Hconf->dbport);
//$dbS = getDoliDBInstance($conf->db->type, "127.0.0.1", "root", "x", "synopsis_oldBimp3", $Hconf->dbport);


    if (defined('IMPORT_BDD_HOST') && defined('IMPORT_BDD_USER') && defined('IMPORT_BDD_PASSE') && defined('IMPORT_BDD_NAME'))
        $dbS = getDoliDBInstance($conf->db->type, IMPORT_BDD_HOST, IMPORT_BDD_USER, IMPORT_BDD_PASSE, IMPORT_BDD_NAME, $Hconf->dbport);
    else
        die("Les info de la base a importé sont incorrecte");

    include_once("./class/maj.class.php");
    $maj = new maj($dbS, $dbD);
    $maj->req("DELETE FROM `" . MAIN_DB_PREFIX . "product_lang`");
    $maj->startMaj(getTab());
    $maj->startMaj(array( // Modification de certaine table
        array("babel_categorie_association", MAIN_DB_PREFIX . "categorie",
            array('fk_categorie_fille_babel', 'fk_categorie_mere_babel'),
            array('rowid', 'fk_parent')
        ),
        array("llx_contrat", MAIN_DB_PREFIX . "Synopsis_contrat_GMAO",
            array('rowid', 'condReg_refid', 'modeReg_refid'),
            array('id', 'condReg_refid', 'modeReg_refid')
            )), true);
//    $maj->rectifId(array(629,395,630,395,631,396,632,396,633,397,634,397,635,398,636,398,637,399,638,399,639,400,640,400,641,401,642,401,643,402,644,402,645,403,646,403,647,404,648,404,649,405,650,405,651,406,652,406,653,407,654,407,655,408,656,408,657,409,658,409,659,410,660,410,661,411,662,411,663,412,664,412,665,413,666,413,699,341,700,341,701,342,702,342,703,343,704,343,705,335,706,335,707,336,708,336,709,420,710,420,711,421,712,421,713,422,714,422,715,423,716,423,717,424,718,424,719,425,720,425,721,426,722,426,723,427,724,427,725,428,726,428,727,429,728,429,729,430,730,430,731,431,732,431,733,432,734,432,735,433,736,433,737,434,738,434,739,435,740,435,741,436,742,436,743,437,744,437,745,438,746,438,747,439,748,439,749,440,750,440,751,441,752,441,753,442,754,442,755,443,756,443,757,444,758,444,775,1579,776,1579));

    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "commandedet` SET `product_type`= 5 WHERE `fk_product` is null AND `total_ttc` = 0");
    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "contratdet` SET `fk_product` = (SELECT `fk_contrat_prod` FROM `llx_Synopsis_contratdet_GMAO` WHERE `contratdet_refid` = `rowid`)");
    $maj->req("DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Histo_User WHERE element_type = 'prepaCom'");
} else {
    echo '<form action=""><input type="hidden" name="action" value="import"/><input type="submit" value="Importer" class="butAction"/></form>';
}





llxFooter();

function getTab() {
    $oldPref = "llx_";
    return array(
        array($oldPref . "user", MAIN_DB_PREFIX . "user",
            array("rowid", "external_id", "datec", "tms", "login", "pass", "pass_crypted", "pass_temp", "name", "firstname", "office_phone", "office_fax", "user_mobile", "email", "admin", /* "local_admin", */ "webcal_login", "phenix_login", "phenix_pass", "module_comm", "module_compta", "fk_societe", "fk_socpeople", "fk_member", "note", "datelastlogin", "datepreviouslogin", "egroupware_id", "ldap_sid", "statut", "lang", /* "CV_ndf", "Propal_seuilWarn", "PropalWarnValidator", "Propal_seuilValidResp", "Propal_validatorResp", "empnumber", "IM_user_name" */),
            array("rowid", /* "entity", */ "ref_ext"/* , "ref_int" */, "datec", "tms", "login", "pass", "pass_crypted", "pass_temp"/* , "civilite" */, "name", "firstname", "office_phone", "office_fax", "user_mobile", "email"/* , "signature" */, "admin", "webcal_login", "phenix_login", "phenix_pass", "module_comm", "module_compta", "fk_societe", "fk_socpeople", "fk_member", "note", "datelastlogin", "datepreviouslogin", "egroupware_id", "ldap_sid", /* "openid", */ "statut", /* "photo", */ "lang")
        ),
        array($oldPref . "user_rights", MAIN_DB_PREFIX . "user_rights",
            array(),
            array()
        ),
        array($oldPref . "usergroup", MAIN_DB_PREFIX . "usergroup",
            array('rowid', 'datec', 'tms', 'nom', 'note'),
            array('rowid', 'datec', 'tms', 'nom', 'note')
        ),
        array($oldPref . "usergroup_rights", MAIN_DB_PREFIX . "usergroup_rights",
            array(),
            array()
        ),
        array($oldPref . "usergroup_user", MAIN_DB_PREFIX . "usergroup_user",
            array('rowid', 'fk_user', 'fk_usergroup'),
            array('rowid', 'fk_user', 'fk_usergroup')
        ),
        array($oldPref . "societe", MAIN_DB_PREFIX . "societe",
            array("rowid", "nom", "external_id", "statut", "parent", "tms", "datec", "datea", "titre", "code_client", "code_fournisseur", "code_compta", "code_compta_fournisseur", "address", "cp", "ville", "fk_departement", "fk_pays", "tel", "fax", "url", "email", "fk_secteur", "fk_effectif", "fk_typent", "fk_forme_juridique", "siren", "siret", "ape", "idprof4", "tva_intra", "capital", /* "description", */ "fk_stcomm", "note"/* , "services"/* , "prefix_comm" */, "client", "fournisseur", "supplier_account", "fk_prospectlevel", "customer_bad", "customer_rate", "supplier_rate", "fk_user_creat", "fk_user_modif", "remise_client", "mode_reglement", "cond_reglement", "tva_assuj"),
            array("rowid", "nom", "ref_ext", "statut", "parent", "tms", "datec", "datea", "status", "code_client", "code_fournisseur", "code_compta", "code_compta_fournisseur", "address", "cp", "ville", "fk_departement", "fk_pays", "tel", "fax", "url", "email", "ref_int", "fk_effectif", "fk_typent", "fk_forme_juridique", "siren", "siret", "ape", "idprof4", "tva_intra", "capital", /* "description", */ "fk_stcomm", "note"/* , "services"/* , "prefix_comm" */, "client", "fournisseur", "supplier_account", "fk_prospectlevel", "customer_bad", "customer_rate", "supplier_rate", "fk_user_creat", "fk_user_modif", "remise_client", "mode_reglement", "cond_reglement", "tva_assuj")
        ),
        array($oldPref . "c_type_contact", MAIN_DB_PREFIX . "c_type_contact",
            array(),
            array()
        ),
        array($oldPref . "socpeople", MAIN_DB_PREFIX . "socpeople",
            array('rowid', 'datec', 'tms', 'fk_soc', 'civilite', 'name', 'firstname', 'address', 'cp', 'ville', 'fk_pays', 'birthday', 'poste', 'phone', 'phone_perso', 'phone_mobile', 'fax', 'email', 'jabberid', 'priv', 'fk_user_creat', 'fk_user_modif', 'note', 'external_id'/* , 'email2', 'email3', 'email4' */),
            array('rowid', 'datec', 'tms', 'fk_soc', /* 'entity', */ 'civilite', 'name', 'firstname', 'address', 'cp', 'ville', /* 'fk_departement', */ 'fk_pays', 'birthday', 'poste', 'phone', 'phone_perso', 'phone_mobile', 'fax', 'email', 'jabberid', 'priv', 'fk_user_creat', 'fk_user_modif', 'note', /* 'default_lang', 'canvas', */ 'import_key')
        ),
        array($oldPref . "societe_adresse_livraison", MAIN_DB_PREFIX . "socpeople",
            array( 'rowid', 'datec', 'tms', 'fk_societe', 'label', 'address', 'cp', 'ville', 'fk_pays', 'tel', 'fax', 'fk_user_creat', 'fk_user_modif', 'note', 'external_id'),
            array( 'rowid',  'datec', 'tms', 'fk_soc', /* 'entity', 'civilite', */'name', /* 'firstname', */ 'address', 'cp', 'ville', /* 'fk_departement', */'fk_pays', /* 'birthday', 'poste', */ 'phone', /* 'phone_perso', 'phone_mobile', */'fax', /* 'email', 'jabberid', 'priv', */'fk_user_creat', 'fk_user_modif', 'note'/* , 'default_lang', 'canvas' */, 'import_key')
        ),
//        array($oldPref . "societe_adresse_livraison", MAIN_DB_PREFIX . "element_contact",
//            array( '$%4', 'fk_societe', '$%102', 'rowid', 'external_id'),
//            array( 'statut',  'element_id', 'fk_c_type_contact', 'fk_socpeople')
//        ),
        array($oldPref . "element_contact", MAIN_DB_PREFIX . "element_contact",
            array('rowid', 'datecreate', 'statut', 'element_id', 'fk_c_type_contact', 'fk_socpeople'/* , 'inPDF' */),
            array('rowid', 'datecreate', 'statut', 'element_id', 'fk_c_type_contact', 'fk_socpeople')
        ),
        array($oldPref . "cond_reglement", MAIN_DB_PREFIX . "c_payment_term",
            array('rowid', 'code', 'sortorder', 'active', 'libelle', 'libelle_facture', 'fdm', 'nbjour', 'decalage'),
            array('rowid', 'code', 'sortorder', 'active', 'libelle', 'libelle_facture', 'fdm', 'nbjour', 'decalage')
        ),
//        array("babel_projet", MAIN_DB_PREFIX."Synopsis_projet",
//            array(),
//            array()
//        ),
//        array("babel_projet_document_group", MAIN_DB_PREFIX."Synopsis_projet_document_group",
//            array(),
//            array()
//        ),
//        array("babel_projet_risk_group", MAIN_DB_PREFIX."Synopsis_projet_risk_group",
//            array(),
//            array()
//        ),
//        array("babel_projet_task", MAIN_DB_PREFIX."Synopsis_projet_task",
//            array(),
//            array()
//        ),
//        array("babel_projet_task_actors", MAIN_DB_PREFIX."Synopsis_projet_task_actors",
//            array(),
//            array()
//        ),
//        array("babel_projet_task_time", MAIN_DB_PREFIX."Synopsis_projet_task_time",
//            array(),
//            array()
//        ),
//        array("babel_projet_task_time_effective", MAIN_DB_PREFIX."Synopsis_projet_task_time_effective",
//            array(),
//            array()
//        ),
//        array("babel_projet_task_time_special", MAIN_DB_PREFIX."Synopsis_projet_task_time_special",
//            array(),
//            array()
//        ),
//        array($oldPref."product", MAIN_DB_PREFIX."product",
//            array('rowid', 'ref', 'datec', 'tms', 'label', 'description', 'note', 'price', 'price_ttc', 'price_base_type', 'tva_tx', /* 'price_loc', 'price_loc_ttc', */ 'fk_user_author', /* 'envente', 'nbvente', */'fk_product_type', 'duration', /* 'stock_propale', 'stock_commande', */ 'seuil_stock_alerte', /* 'stock_loc', */ 'barcode', 'fk_barcode_type', 'partnumber', 'weight', 'weight_units', 'volume', 'volume_units', 'canvas', /* 'magento_id', 'magento_product', 'magento_type', 'magento_sku', 'magento_cat', 'durSav', 'isSAV', 'durValid', 'reconductionAuto', 'VisiteSurSite', 'SLA', 'Maintenance', 'TeleMaintenance', 'Hotline', 'PrixAchatHT', 'qte', 'clause', */ 'external_id', /* 'qteTempsPerDuree', 'qteTktPerDuree' */),
//            array('rowid', 'ref', /* 'entity', 'ref_ext', */ 'datec', 'tms', /* 'virtual', 'fk_parent', */ 'label', 'description', 'note', /* 'customcode', 'fk_country', */ 'price', 'price_ttc', /* 'price_min', 'price_min_ttc', */'price_base_type', 'tva_tx', /* 'recuperableonly', 'localtax1_tx', 'localtax2_tx', */ 'fk_user_author', /* 'tosell', 'tobuy', */ 'fk_product_type', 'duration', 'seuil_stock_alerte', 'barcode', 'fk_barcode_type', /* 'accountancy_code_sell', 'accountancy_code_buy', */ 'partnumber', 'weight', 'weight_units', /* 'length', 'length_units', 'surface', 'surface_units', */ 'volume', 'volume_units', /* 'stock', 'pmp', */ 'canvas', /* 'finished', 'hidden', */ 'import_key')
//        ),
        array($oldPref . "commande", MAIN_DB_PREFIX . "commande",
            array("rowid", "ref", "ref_client", "fk_soc", "fk_projet", "tms", "date_creation", "date_valid", "date_cloture", "date_commande", "fk_user_author", "fk_user_valid", "fk_user_cloture", "source", "fk_statut", "amount_ht", "remise_percent", "remise_absolue", "remise", "tva", "total_ht", "total_ttc", "note", "note_public", "model_pdf", "facture", "fk_cond_reglement", "fk_mode_reglement", "date_livraison"),
            array("rowid", "ref", "ref_client", "fk_soc", "fk_projet", "tms", "date_creation", "date_valid", "date_cloture", "date_commande", "fk_user_author", "fk_user_valid", "fk_user_cloture", "source", "fk_statut", "amount_ht", "remise_percent", "remise_absolue", "remise", "tva", "total_ht", "total_ttc", "note", "note_public", "model_pdf", "facture", "fk_cond_reglement", "fk_mode_reglement", "date_livraison")
        ),
        array($oldPref . "commandedet", MAIN_DB_PREFIX . "commandedet",
            array('rowid', 'fk_commande', 'fk_product', 'description', 'tva_tx', 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'price', 'subprice', 'total_ht', 'total_tva', 'total_ttc', 'info_bits', /* 'marge_tx', 'marque_tx', */ 'special_code', 'rang', /* 'finance_ok', 'logistique_ok', 'logistique_date_dispo', 'coef', 'external_id', 'pu_achat_ht', 'propaldet_refid' */),
            array('rowid', 'fk_commande', /* 'fk_parent_line', */ 'fk_product', 'description', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx', */ 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'price', 'subprice', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */ 'total_ttc', /* 'product_type', 'date_start', 'date_end', */'info_bits', /* 'marge_tx', 'marque_tx', */ 'special_code', 'rang', /* 'import_key' */)
        ),
        array($oldPref . "commande_fournisseur", MAIN_DB_PREFIX . "commande_fournisseur",
            array(),
            array()
        ),
        array($oldPref . "commande_fournisseurdet", MAIN_DB_PREFIX . "commande_fournisseurdet",
            array(),
            array()
        ),
        array($oldPref . "commande", MAIN_DB_PREFIX . "Synopsis_commande",
            array("rowid", "logistique_ok", "logistique_statut", "finance_ok", "finance_statut", "logistique_date_dispo"),
            array("rowid", "logistique_ok", "logistique_statut", "finance_ok", "finance_statut", "logistique_date_dispo")
        ),
        array($oldPref . "commandedet", MAIN_DB_PREFIX . "Synopsis_commandedet",
            array('rowid', 'finance_ok', 'logistique_ok', 'logistique_date_dispo', 'coef'),
            array('rowid', 'finance_ok', 'logistique_ok', 'logistique_date_dispo', 'coef')
        ),
        array("Babel_commande_grp", MAIN_DB_PREFIX . "Synopsis_commande_grp",
            array(),
            array()
        ),
        array("Babel_commande_grpdet", MAIN_DB_PREFIX . "Synopsis_commande_grpdet",
            array(),
            array()
        ),
        array($oldPref . "propal", MAIN_DB_PREFIX . "propal",
            array('rowid', 'ref', 'ref_client', 'fk_soc', 'fk_projet', 'tms', 'datec', 'datep', 'fin_validite', 'date_valid', 'date_cloture', 'fk_user_author', 'fk_user_valid', 'fk_user_cloture', 'fk_statut', 'price', 'remise_percent', 'remise_absolue', 'remise'/* , 'date_abandon', 'fk_user_abandon', 'accompte_ht' */, 'total_ht', 'tva', 'total', 'fk_cond_reglement', 'fk_mode_reglement', 'note', 'note_public', 'model_pdf', 'date_livraison', 'fk_adresse_livraison'/* , 'date_demandeValid', 'isFinancement', 'isLocation', 'date_devis_fourn', 'fournisseur_refid', 'tva_tx_fin_refid', 'revision', 'orig_ref' */),
            array('rowid', 'ref', /* 'entity', 'ref_ext', 'ref_int', */'ref_client', 'fk_soc', 'fk_projet', 'tms', 'datec', 'datep', 'fin_validite', 'date_valid', 'date_cloture', 'fk_user_author', 'fk_user_valid', 'fk_user_cloture', 'fk_statut', 'price', 'remise_percent', 'remise_absolue', 'remise', 'total_ht', 'tva', /* 'localtax1', 'localtax2', */'total', /* 'fk_account', 'fk_currency', */ 'fk_cond_reglement', 'fk_mode_reglement', 'note', 'note_public', 'model_pdf', 'date_livraison', /* 'fk_availability', 'fk_demand_reason', 'import_key', 'extraparams', */ 'fk_adresse_livraison')
        ),
        array($oldPref . "propaldet", MAIN_DB_PREFIX . "propaldet",
            array('rowid', 'fk_propal', 'fk_product', 'description', 'fk_remise_except', 'tva_tx', 'qty', 'remise_percent', 'remise', 'price', 'subprice', 'total_ht', 'total_tva', 'total_ttc', 'info_bits', 'pa_ht', 'marge_tx', 'marque_tx', 'special_code', 'rang'/* , 'coef', 'dureeLoc' */),
            array('rowid', 'fk_propal', /* 'fk_parent_line', */'fk_product', 'description', 'fk_remise_except', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx'*, */ 'qty', 'remise_percent', 'remise', 'price', 'subprice', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */ 'total_ttc'/* , 'product_type', 'date_start', 'date_end' */, 'info_bits', 'pa_ht', 'marge_tx', 'marque_tx', 'special_code', 'rang')
        ),
//        array("Babel_Chrono", MAIN_DB_PREFIX."Synopsis_Chrono",
//            array(),
//            array()
//        ),
//        array("Babel_Chrono_group_rights", MAIN_DB_PREFIX."Synopsis_Chrono_group_rights",
//            array(),
//            array()
//        ),
//        array("Babel_Chrono_value", MAIN_DB_PREFIX."Synopsis_Chrono_value",
//            array(),
//            array()
//        ),
//        array("Babel_Process_group_rights", MAIN_DB_PREFIX."Synopsis_Process_group_rights",
//            array(),
//            array()
//        ),
//        array("Babel_Processdet", MAIN_DB_PREFIX."Synopsis_Processdet",
//            array(),
//            array()
//        ),
//        array("Babel_Processdet_active", MAIN_DB_PREFIX."Synopsis_Processdet_active",
//            array(),
//            array()
//        ),
//        array("Babel_Processdet_validation", MAIN_DB_PREFIX."Synopsis_Processdet_validation",
//            array(),
//            array()
//        ),
//        array("Babel_Processdet_value", MAIN_DB_PREFIX."Synopsis_Processdet_value",
//            array(),
//            array()
//        ),
        array($oldPref . "facture", MAIN_DB_PREFIX . "facture",
            array('rowid', 'facnumber', 'ref_client', 'type', 'increment', 'fk_soc', 'datec', 'datef', 'date_valid', 'paye', 'amount', 'remise_percent', 'remise_absolue', 'remise', 'close_code', 'close_note', 'tva', 'total', 'total_ttc', 'fk_statut', 'fk_user_author', 'fk_user_valid', 'fk_facture_source', 'fk_projet', 'fk_cond_reglement', 'fk_mode_reglement', 'date_lim_reglement', 'note', 'note_public', 'model_pdf'),
            array('rowid', 'facnumber'/* , 'entity', 'ref_ext', 'ref_int' */, 'ref_client', 'type', 'increment', 'fk_soc', 'datec', 'datef', 'date_valid'/* , 'tms' */, 'paye', 'amount', 'remise_percent', 'remise_absolue', 'remise', 'close_code', 'close_note', 'tva', /* 'localtax1', 'localtax2', */ 'total', 'total_ttc', 'fk_statut', 'fk_user_author', 'fk_user_valid', 'fk_facture_source', 'fk_projet'/* , 'fk_account', 'fk_currency' */, 'fk_cond_reglement', 'fk_mode_reglement', 'date_lim_reglement', 'note', 'note_public', 'model_pdf'/* , 'import_key', 'extraparams' */)
        ),
        array($oldPref . "facturedet", MAIN_DB_PREFIX . "facturedet",
            array('rowid', 'fk_facture', 'fk_product', 'description', 'tva_taux', 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'subprice', 'price', 'total_ht', 'total_tva', 'total_ttc', 'product_type', 'date_start', 'date_end', 'info_bits', 'fk_code_ventilation', 'fk_export_compta', 'special_code', 'rang'/* ,              'durSav', 'coef', 'lineFromComId', 'lineFromPropId' */),
            array('rowid', 'fk_facture'/* , 'fk_parent_line' */, 'fk_product', 'description', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx', */ 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'subprice', 'price', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */'total_ttc', 'product_type', 'date_start', 'date_end', 'info_bits', 'fk_code_ventilation', 'fk_export_compta', 'special_code', 'rang'/* , 'import_key' */)
        ),
        array($oldPref . "societe_remise_except", MAIN_DB_PREFIX . "societe_remise_except",
            array(),
            array()
        ),
        array($oldPref . "fa_pr", MAIN_DB_PREFIX . "element_element",
            array('fk_propal', '$%propal', 'fk_facture', '$%facture'),
            array('fk_source', 'sourcetype', 'fk_target', 'targettype')
        ),
        array($oldPref . "co_fa", MAIN_DB_PREFIX . "element_element",
            array('fk_commande', '$%commande', 'fk_facture', '$%facture'),
            array('fk_source', 'sourcetype', 'fk_target', 'targettype')
        ),
        array($oldPref . "co_pr", MAIN_DB_PREFIX . "element_element",
            array('fk_commande', '$%commande', 'fk_propale', '$%propal'),
            array('fk_source', 'sourcetype', 'fk_target', 'targettype')
        ),
        array($oldPref . "paiement", MAIN_DB_PREFIX . "paiement",
            array('rowid'/* , 'fk_facture' */, 'datec', 'tms', 'datep', 'amount', 'fk_paiement', 'num_paiement', 'note', 'fk_bank', 'fk_user_creat', 'fk_user_modif', 'statut', 'fk_export_compta'),
            array('rowid'/* , 'entity' */, 'datec', 'tms', 'datep', 'amount', 'fk_paiement', 'num_paiement', 'note', 'fk_bank', 'fk_user_creat', 'fk_user_modif', 'statut', 'fk_export_compta')
        ),
        array($oldPref . "paiement_facture", MAIN_DB_PREFIX . "paiement_facture",
            array(),
            array()
        ),
        array($oldPref . "fichinter", MAIN_DB_PREFIX . "Synopsis_fichinter",
            array(),
            array()
        ),
        array($oldPref . "fichinterdet", MAIN_DB_PREFIX . "Synopsis_fichinterdet",
            array(),
            array()
        ),
        array("Babel_Interv_extra_value", MAIN_DB_PREFIX . "Synopsis_fichinter_extra_value",
            array(),
            array()
        ),
        array("babel_product", MAIN_DB_PREFIX . "product",
            array('rowid', 'ref', 'datec', 'tms', 'label', 'description', 'note', 'price', 'price_ttc', 'price_base_type', 'tva_tx', /* 'price_loc', 'price_loc_ttc', */ 'fk_user_author', /* 'envente', 'nbvente', */'fk_product_type', 'duration', /* 'stock_propale', 'stock_commande', */ 'seuil_stock_alerte', /* 'stock_loc', */ 'barcode', 'fk_barcode_type', 'partnumber', 'weight', 'weight_units', 'volume', 'volume_units', 'canvas', /* 'magento_id', 'magento_product', 'magento_type', 'magento_sku', 'magento_cat', 'durSav', 'isSAV', 'durValid', 'reconductionAuto', 'VisiteSurSite', 'SLA', 'Maintenance', 'TeleMaintenance', 'Hotline', 'PrixAchatHT', 'qte', 'clause', */ 'external_id', /* 'qteTempsPerDuree', 'qteTktPerDuree' */),
            array('rowid', 'ref', /* 'entity', 'ref_ext', */ 'datec', 'tms', /* 'virtual', 'fk_parent', */ 'label', 'description', 'note', /* 'customcode', 'fk_country', */ 'price', 'price_ttc', /* 'price_min', 'price_min_ttc', */'price_base_type', 'tva_tx', /* 'recuperableonly', 'localtax1_tx', 'localtax2_tx', */ 'fk_user_author', /* 'tosell', 'tobuy', */ 'fk_product_type', 'duration', 'seuil_stock_alerte', 'barcode', 'fk_barcode_type', /* 'accountancy_code_sell', 'accountancy_code_buy', */ 'partnumber', 'weight', 'weight_units', /* 'length', 'length_units', 'surface', 'surface_units', */ 'volume', 'volume_units', /* 'stock', 'pmp', */ 'canvas', /* 'finished', 'hidden', */ 'import_key')
        ),
        array("babel_product", MAIN_DB_PREFIX . "product_extrafields",
            array('rowid', 'durSav', 'isSAV', 'durValid', 'reconductionAuto', 'VisiteSurSite', 'SLA', 'Maintenance', 'TeleMaintenance', 'Hotline', 'PrixAchatHT', 'qte', 'clause', 'qteTempsPerDuree', 'qteTktPerDuree', 'annexe'),
            array('fk_object', '2dureeSav', '2isSav', '2dureeVal', '2reconductionAuto', '2visiteSurSite', '2sla', '2maintenance', '2teleMaintenance', '2hotline', '2prixAchatHt', '2qte', '2clause', '2timePerDuree', '2qtePerDuree', '2annexe')
        ),
        array("babel_categorie", MAIN_DB_PREFIX . "categorie",
            array('rowid', 'label', 'type', 'description', 'visible', 'magento_id'/* , 'position', 'magento_product', 'level' */),
            array('rowid', 'label', 'type'/* , 'entity' */, 'description'/* , 'fk_soc' */, 'visible', 'import_key')
        ),
        array("Babel_prepaCom_c_cat_listContent", MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_listContent",
            array(),
            array()
        ),
        array("Babel_prepaCom_c_cat_total", MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_total",
            array(),
            array()
        ),
        array("babel_categorie_product", MAIN_DB_PREFIX . "categorie_product",
            array(),
            array()
        ),
        array($oldPref . "expedition", MAIN_DB_PREFIX . "expedition",
            array('rowid', 'tms', 'ref', 'ref_client', 'fk_soc', 'date_creation', 'fk_user_author', 'date_valid', 'fk_user_valid', 'date_expedition', 'fk_adresse_livraison', 'fk_expedition_methode', 'fk_statut', 'note', 'model_pdf'),
            array('rowid', 'tms', 'ref'/* ,  'entity' */, 'ref_ext', 'fk_soc'/* , 'ref_int', 'ref_customer' */, 'date_creation', 'fk_user_author', 'date_valid', 'fk_user_valid', 'date_expedition'/* , 'date_delivery' */, 'fk_address', 'fk_expedition_methode'/* , 'tracking_number' */, 'fk_statut'/* , 'height', 'width', 'size_units', 'size', 'weight_units', 'weight' */, 'note', 'model_pdf')
        ),
        array($oldPref . "expeditiondet", MAIN_DB_PREFIX . "expeditiondet",
            array(),
            array()
        ),
        array($oldPref . "contrat", MAIN_DB_PREFIX . "contrat",
            array('rowid', 'ref', 'tms', 'datec', 'date_contrat', 'statut'/* , 'modelPdf' */, 'mise_en_service', 'fin_validite', 'date_cloture', 'fk_soc', 'fk_projet', 'fk_commercial_signature', 'fk_commercial_suivi', 'fk_user_author', 'fk_user_mise_en_service', 'fk_user_cloture', 'note', 'note_public', 'linkedTo', /* , 'date_valid', 'is_financement', 'cessionnaire_refid', 'fournisseur_refid', 'tva_tx', 'line_order', 'warned', */'type'), // 'prorata', 'facturation_freq', 'condReg_refid', 'modeReg_refid'),
            array('rowid', 'ref'/* , 'entity' */, 'tms', 'datec', 'date_contrat', 'statut', 'mise_en_service', 'fin_validite', 'date_cloture', 'fk_soc', 'fk_projet', 'fk_commercial_signature', 'fk_commercial_suivi', 'fk_user_author', 'fk_user_mise_en_service', 'fk_user_cloture', 'note', 'note_public', 'import_key', 'extraparams')
        ),
        array($oldPref . "contratdet", MAIN_DB_PREFIX . "contratdet",
            array('rowid', 'tms', 'fk_contrat', 'fk_product', 'statut', 'label', 'description', 'fk_remise_except', 'date_commande', 'date_ouverture_prevue', 'date_ouverture', 'date_fin_validite', 'date_cloture', 'tva_tx', 'qty', 'remise_percent'/* , 'isSubPricePerMonth' */, 'subprice', 'price_ht', 'remise', 'total_ht', 'total_tva', 'total_ttc', 'info_bits', 'fk_user_author', 'fk_user_ouverture', 'fk_user_cloture', 'commentaire'), //, 'line_order', 'fk_commande_ligne', 'avenant', 'date_valid', 'prod_duree_loc'),
            array('rowid', 'tms', 'fk_contrat', 'fk_product', 'statut', 'label', 'description', 'fk_remise_except', 'date_commande', 'date_ouverture_prevue', 'date_ouverture', 'date_fin_validite', 'date_cloture', 'tva_tx'/* , 'localtax1_tx', 'localtax2_tx' */, 'qty', 'remise_percent', 'subprice', 'price_ht', 'remise', 'total_ht', 'total_tva'/* , 'total_localtax1', 'total_localtax2' */, 'total_ttc', 'info_bits', 'fk_user_author', 'fk_user_ouverture', 'fk_user_cloture', 'commentaire')
        ),
        array("Babel_GMAO_contratdet_prop", MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO",
            array(),
            array()
        ),
        array("Babel_GMAO_contrat_prop", MAIN_DB_PREFIX . "Synopsis_contrat_GMAO",
            array(),
            array()
        ),
        array($oldPref . "contratdet", MAIN_DB_PREFIX . "element_element",
            array('fk_commande_ligne', '$%commandedet', 'rowid', '$%contratdet'),
            array('fk_source', 'sourcetype', 'fk_target', 'targettype')
        ),
        array("Babel_demandeInterv", MAIN_DB_PREFIX . "Synopsis_demandeInterv",
            array(),
            array()
        ),
        array("Babel_demandeIntervdet", MAIN_DB_PREFIX . "Synopsis_demandeIntervdet",
            array(),
            array()
        ),
        array($oldPref . "co_exp", MAIN_DB_PREFIX . "element_element",
            array("fk_commande", 'fk_expedition', '$%commande', '$%shipping'),
            array("fk_source", "fk_target", 'sourcetype', "targettype")
        ),
        array("Babel_li_interv", MAIN_DB_PREFIX . "element_element",
            array("di_refid", 'fi_refid', '$%DI', '$%FI'),
            array("fk_source", "fk_target", 'sourcetype', "targettype")
        ),
        array("Babel_contrat_annexe", MAIN_DB_PREFIX . "Synopsis_contrat_annexe",
            array(),
            array()
        ),
        array("Babel_contrat_annexePdf", MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf",
            array(),
            array()
        ),
        array("BIMP_site", MAIN_DB_PREFIX . "entrepot",
            array('id', 'label', 'label', 'email'),
            array('rowid', 'label', 'lieu', "description")
        ),
        array("BIMP_messages", MAIN_DB_PREFIX . "Synopsis_PrepaCom_messages",
            array(),
            array()
        ),
        array("Babel_Histo_User", MAIN_DB_PREFIX . "Synopsis_Histo_User",
            array(),
            array()
        ),
        array($oldPref . "commande", MAIN_DB_PREFIX . "element_contact",
            array( '$%4', 'rowid', '$%102', 'fk_adresse_livraison'),
            array( 'statut',  'element_id', 'fk_c_type_contact', 'fk_socpeople')
        ),
        array($oldPref . "expedition", MAIN_DB_PREFIX . "element_contact",
            array( '$%4', 'rowid', '$%102', 'fk_adresse_livraison'),
            array( 'statut',  'element_id', 'fk_c_type_contact', 'fk_socpeople')
        )
    );
}

?>

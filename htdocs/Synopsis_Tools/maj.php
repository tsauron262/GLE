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
$dbS = getDoliDBInstance($conf->db->type, "127.0.0.1", "root", "freeparty", "old_bimp", $Hconf->dbport);
//    $dbS = getDoliDBInstance($conf->db->type, "127.0.0.1", "root", "freeparty", "oldCapsim2", $Hconf->dbport);

    include_once("./class/maj.class.php");
    $maj = new maj($dbS, $dbD);
    $maj->startMaj(getTab());
//    $maj->rectifId(array(629,395,630,395,631,396,632,396,633,397,634,397,635,398,636,398,637,399,638,399,639,400,640,400,641,401,642,401,643,402,644,402,645,403,646,403,647,404,648,404,649,405,650,405,651,406,652,406,653,407,654,407,655,408,656,408,657,409,658,409,659,410,660,410,661,411,662,411,663,412,664,412,665,413,666,413,699,341,700,341,701,342,702,342,703,343,704,343,705,335,706,335,707,336,708,336,709,420,710,420,711,421,712,421,713,422,714,422,715,423,716,423,717,424,718,424,719,425,720,425,721,426,722,426,723,427,724,427,725,428,726,428,727,429,728,429,729,430,730,430,731,431,732,431,733,432,734,432,735,433,736,433,737,434,738,434,739,435,740,435,741,436,742,436,743,437,744,437,745,438,746,438,747,439,748,439,749,440,750,440,751,441,752,441,753,442,754,442,755,443,756,443,757,444,758,444,775,1579,776,1579));
} else {
    echo '<form action=""><input type="hidden" name="action" value="import"/><input type="submit" value="Importer" class="butAction"/></form>';
}





llxFooter();

function getTab() {
    return array(
        array("llx_user", "llx_user",
            array("rowid", "external_id", "datec", "tms", "login", "pass", "pass_crypted", "pass_temp", "name", "firstname", "office_phone", "office_fax", "user_mobile", "email", "admin", /* "local_admin", */ "webcal_login", "phenix_login", "phenix_pass", "module_comm", "module_compta", "fk_societe", "fk_socpeople", "fk_member", "note", "datelastlogin", "datepreviouslogin", "egroupware_id", "ldap_sid", "statut", "lang", /* "CV_ndf", "Propal_seuilWarn", "PropalWarnValidator", "Propal_seuilValidResp", "Propal_validatorResp", "empnumber", "IM_user_name" */),
            array("rowid", /* "entity", */ "ref_ext"/* , "ref_int" */, "datec", "tms", "login", "pass", "pass_crypted", "pass_temp"/* , "civilite" */, "name", "firstname", "office_phone", "office_fax", "user_mobile", "email"/* , "signature" */, "admin", "webcal_login", "phenix_login", "phenix_pass", "module_comm", "module_compta", "fk_societe", "fk_socpeople", "fk_member", "note", "datelastlogin", "datepreviouslogin", "egroupware_id", "ldap_sid", /* "openid", */ "statut", /* "photo", */ "lang")
        ),
        array("llx_user_rights", "llx_user_rights",
            array(),
            array()
        ),
        array("llx_usergroup", "llx_usergroup",
            array('rowid', 'datec', 'tms', 'nom', 'note'),
            array('rowid', 'datec', 'tms', 'nom', 'note')
        ),
        array("llx_usergroup_rights", "llx_usergroup_rights",
            array(),
            array()
        ),
        array("llx_usergroup_user", "llx_usergroup_user",
            array('rowid', 'fk_user', 'fk_usergroup'),
            array('rowid', 'fk_user', 'fk_usergroup')
        ),
        array("llx_societe", "llx_societe",
            array("rowid", "nom", "external_id", "statut", "parent", "tms", "datec", "datea", "titre", "code_client", "code_fournisseur", "code_compta", "code_compta_fournisseur", "address", "cp", "ville", "fk_departement", "fk_pays", "tel", "fax", "url", "email", "fk_secteur", "fk_effectif", "fk_typent", "fk_forme_juridique", "siren", "siret", "ape", "idprof4", "tva_intra", "capital", "description", "fk_stcomm", "note", "services"/* , "prefix_comm" */, "client", "fournisseur", "supplier_account", "fk_prospectlevel", "customer_bad", "customer_rate", "supplier_rate", "fk_user_creat", "fk_user_modif", "remise_client", "mode_reglement", "cond_reglement", "tva_assuj"),
            array("rowid", "nom", "ref_ext", "statut", "parent", "tms", "datec", "datea", "status", "code_client", "code_fournisseur", "code_compta", "code_compta_fournisseur", "address", "cp", "ville", "fk_departement", "fk_pays", "tel", "fax", "url", "email", "fk_secteur", "fk_effectif", "fk_typent", "fk_forme_juridique", "siren", "siret", "ape", "idprof4", "tva_intra", "capital", "description", "fk_stcomm", "note", "services"/* , "prefix_comm" */, "client", "fournisseur", "supplier_account", "fk_prospectlevel", "customer_bad", "customer_rate", "supplier_rate", "fk_user_creat", "fk_user_modif", "remise_client", "mode_reglement", "cond_reglement", "tva_assuj")
        ),
        array("llx_socpeople", "llx_socpeople",
            array('rowid', 'datec', 'tms', 'fk_soc', 'civilite', 'name', 'firstname', 'address', 'cp', 'ville', 'fk_pays', 'birthday', 'poste', 'phone', 'phone_perso', 'phone_mobile', 'fax', 'email', 'jabberid', 'priv', 'fk_user_creat', 'fk_user_modif', 'note', 'external_id'/* , 'email2', 'email3', 'email4' */),
            array('rowid', 'datec', 'tms', 'fk_soc', /* 'entity', */ 'civilite', 'name', 'firstname', 'address', 'cp', 'ville', /* 'fk_departement', */ 'fk_pays', 'birthday', 'poste', 'phone', 'phone_perso', 'phone_mobile', 'fax', 'email', 'jabberid', 'priv', 'fk_user_creat', 'fk_user_modif', 'note', /* 'default_lang', 'canvas', */ 'import_key')
        ),
        array("llx_societe_adresse_livraison", "llx_socpeople",
            array(/* 'rowid', */'datec', 'tms', 'fk_societe', 'nom', 'address', 'cp', 'ville', 'fk_pays', 'tel', 'fax', 'fk_user_creat', 'fk_user_modif', 'note', 'external_id'),
            array(/* 'rowid', */ 'datec', 'tms', 'fk_soc', /* 'entity', 'civilite', */'name', /* 'firstname', */ 'address', 'cp', 'ville', /* 'fk_departement', */'fk_pays', /* 'birthday', 'poste', */ 'phone', /* 'phone_perso', 'phone_mobile', */'fax', /* 'email', 'jabberid', 'priv', */'fk_user_creat', 'fk_user_modif', 'note'/* , 'default_lang', 'canvas' */, 'import_key')
        ),
        array("llx_c_type_contact", "llx_c_type_contact",
            array(),
            array()
        ),
        array("llx_element_contact", "llx_element_contact",
            array('rowid', 'datecreate', 'statut', 'element_id', 'fk_c_type_contact', 'fk_socpeople'/* , 'inPDF' */),
            array('rowid', 'datecreate', 'statut', 'element_id', 'fk_c_type_contact', 'fk_socpeople')
        ),
        array("llx_cond_reglement", "llx_c_payment_term",
            array('rowid', 'code', 'sortorder', 'active', 'libelle', 'libelle_facture', 'fdm', 'nbjour', 'decalage'),
            array('rowid', 'code', 'sortorder', 'active', 'libelle', 'libelle_facture', 'fdm', 'nbjour', 'decalage')
        ),
        array("babel_projet", "llx_Synopsis_projet",
            array(),
            array()
        ),
        array("babel_projet_document_group", "llx_Synopsis_projet_document_group",
            array(),
            array()
        ),
        array("babel_projet_risk_group", "llx_Synopsis_projet_risk_group",
            array(),
            array()
        ),
        array("babel_projet_task", "llx_Synopsis_projet_task",
            array(),
            array()
        ),
        array("babel_projet_task_actors", "llx_Synopsis_projet_task_actors",
            array(),
            array()
        ),
        array("babel_projet_task_time", "llx_Synopsis_projet_task_time",
            array(),
            array()
        ),
        array("babel_projet_task_time_effective", "llx_Synopsis_projet_task_time_effective",
            array(),
            array()
        ),
        array("babel_projet_task_time_special", "llx_Synopsis_projet_task_time_special",
            array(),
            array()
        ),
        array("llx_product", "llx_product",
            array('rowid', 'ref', 'datec', 'tms', 'label', 'description', 'note', 'price', 'price_ttc', 'price_base_type', 'tva_tx', /* 'price_loc', 'price_loc_ttc', */ 'fk_user_author', /* 'envente', 'nbvente', */'fk_product_type', 'duration', /* 'stock_propale', 'stock_commande', */ 'seuil_stock_alerte', /* 'stock_loc', */ 'barcode', 'fk_barcode_type', 'partnumber', 'weight', 'weight_units', 'volume', 'volume_units', 'canvas', /* 'magento_id', 'magento_product', 'magento_type', 'magento_sku', 'magento_cat', 'durSav', 'isSAV', 'durValid', 'reconductionAuto', 'VisiteSurSite', 'SLA', 'Maintenance', 'TeleMaintenance', 'Hotline', 'PrixAchatHT', 'qte', 'clause', */ 'external_id', /* 'qteTempsPerDuree', 'qteTktPerDuree' */),
            array('rowid', 'ref', /* 'entity', 'ref_ext', */ 'datec', 'tms', /* 'virtual', 'fk_parent', */ 'label', 'description', 'note', /* 'customcode', 'fk_country', */ 'price', 'price_ttc', /* 'price_min', 'price_min_ttc', */'price_base_type', 'tva_tx', /* 'recuperableonly', 'localtax1_tx', 'localtax2_tx', */ 'fk_user_author', /* 'tosell', 'tobuy', */ 'fk_product_type', 'duration', 'seuil_stock_alerte', 'barcode', 'fk_barcode_type', /* 'accountancy_code_sell', 'accountancy_code_buy', */ 'partnumber', 'weight', 'weight_units', /* 'length', 'length_units', 'surface', 'surface_units', */ 'volume', 'volume_units', /* 'stock', 'pmp', */ 'canvas', /* 'finished', 'hidden', */ 'import_key')
        ),
        array("llx_commande", "llx_commande",
            array("rowid", "ref", "ref_client", "fk_soc", "fk_projet", "tms", "date_creation", "date_valid", "date_cloture", "date_commande", "fk_user_author", "fk_user_valid", "fk_user_cloture", "source", "fk_statut", "amount_ht", "remise_percent", "remise_absolue", "remise", "tva", "total_ht", "total_ttc", "note", "note_public", "model_pdf", "facture", "fk_cond_reglement", "fk_mode_reglement", "date_livraison", "fk_adresse_livraison"),
            array("rowid", "ref", "ref_client", "fk_soc", "fk_projet", "tms", "date_creation", "date_valid", "date_cloture", "date_commande", "fk_user_author", "fk_user_valid", "fk_user_cloture", "source", "fk_statut", "amount_ht", "remise_percent", "remise_absolue", "remise", "tva", "total_ht", "total_ttc", "note", "note_public", "model_pdf", "facture", "fk_cond_reglement", "fk_mode_reglement", "date_livraison", "fk_adresse_livraison")
        ),
        array("llx_commandedet", "llx_commandedet",
            array('rowid', 'fk_commande',                         'fk_product', 'description', 'tva_tx',                                       'qty', 'remise_percent', 'remise', 'fk_remise_except', 'price', 'subprice', 'total_ht', 'total_tva',                                             'total_ttc',                                                'info_bits', 'marge_tx', 'marque_tx', 'special_code', 'rang', /* 'finance_ok', 'logistique_ok', 'logistique_date_dispo', 'coef', 'external_id', 'pu_achat_ht', 'propaldet_refid' */),
            array('rowid', 'fk_commande', /* 'fk_parent_line', */ 'fk_product', 'description', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx', */ 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'price', 'subprice', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */ 'total_ttc', /* 'product_type', 'date_start', 'date_end', */'info_bits', 'marge_tx', 'marque_tx', 'special_code', 'rang', /* 'import_key' */)
        ),
        array("llx_commande", "llx_Synopsis_commande",
            array("rowid", "logistique_ok", "logistique_statut", "finance_ok", "finance_statut", "logistique_date_dispo"),
            array("rowid", "logistique_ok", "logistique_statut", "finance_ok", "finance_statut", "logistique_date_dispo")
        ),
        array("llx_commandedet", "llx_Synopsis_commandedet",
            array('rowid', 'finance_ok', 'logistique_ok', 'logistique_date_dispo', 'coef'),
            array('rowid', 'finance_ok', 'logistique_ok', 'logistique_date_dispo', 'coef')
        ),
        array("llx_propal", "llx_propal",
            array('rowid', 'ref', 'ref_client', 'fk_soc', 'fk_projet', 'tms', 'datec', 'datep', 'fin_validite', 'date_valid', 'date_cloture', 'fk_user_author', 'fk_user_valid', 'fk_user_cloture', 'fk_statut', 'price', 'remise_percent', 'remise_absolue', 'remise'/* , 'date_abandon', 'fk_user_abandon', 'accompte_ht' */, 'total_ht', 'tva', 'total', 'fk_cond_reglement', 'fk_mode_reglement', 'note', 'note_public', 'model_pdf', 'date_livraison', 'fk_adresse_livraison'/* , 'date_demandeValid', 'isFinancement', 'isLocation', 'date_devis_fourn', 'fournisseur_refid', 'tva_tx_fin_refid', 'revision', 'orig_ref' */),
            array('rowid', 'ref', /* 'entity', 'ref_ext', 'ref_int', */'ref_client', 'fk_soc', 'fk_projet', 'tms', 'datec', 'datep', 'fin_validite', 'date_valid', 'date_cloture', 'fk_user_author', 'fk_user_valid', 'fk_user_cloture', 'fk_statut', 'price', 'remise_percent', 'remise_absolue', 'remise', 'total_ht', 'tva', /* 'localtax1', 'localtax2', */'total', /* 'fk_account', 'fk_currency', */ 'fk_cond_reglement', 'fk_mode_reglement', 'note', 'note_public', 'model_pdf', 'date_livraison', /* 'fk_availability', 'fk_demand_reason', 'import_key', 'extraparams', */ 'fk_adresse_livraison')
        ),
        array("llx_propaldet", "llx_propaldet",
            array('rowid', 'fk_propal', 'fk_product', 'description', 'fk_remise_except', 'tva_tx', 'qty', 'remise_percent', 'remise', 'price', 'subprice', 'total_ht', 'total_tva', 'total_ttc', 'info_bits', 'pa_ht', 'marge_tx', 'marque_tx', 'special_code', 'rang'/* , 'coef', 'dureeLoc' */),
            array('rowid', 'fk_propal', /* 'fk_parent_line', */'fk_product', 'description', 'fk_remise_except', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx'*, */ 'qty', 'remise_percent', 'remise', 'price', 'subprice', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */ 'total_ttc'/* , 'product_type', 'date_start', 'date_end' */, 'info_bits', 'pa_ht', 'marge_tx', 'marque_tx', 'special_code', 'rang')
        ),
        array("Babel_Chrono", "llx_Synopsis_Chrono",
            array(),
            array()
        ),
        array("Babel_Chrono_group_rights", "llx_Synopsis_Chrono_group_rights",
            array(),
            array()
        ),
        array("Babel_Chrono_value", "llx_Synopsis_Chrono_value",
            array(),
            array()
        ),
        array("Babel_Process_group_rights", "llx_Synopsis_Process_group_rights",
            array(),
            array()
        ),
        array("Babel_Processdet", "llx_Synopsis_Processdet",
            array(),
            array()
        ),
        array("Babel_Processdet_active", "llx_Synopsis_Processdet_active",
            array(),
            array()
        ),
        array("Babel_Processdet_validation", "llx_Synopsis_Processdet_validation",
            array(),
            array()
        ),
        array("Babel_Processdet_value", "llx_Synopsis_Processdet_value",
            array(),
            array()
        ),
        array("llx_facture", "llx_facture",
            array('rowid', 'facnumber', 'ref_client', 'type', 'increment', 'fk_soc', 'datec', 'datef', 'date_valid', 'paye', 'amount', 'remise_percent', 'remise_absolue', 'remise', 'close_code', 'close_note', 'tva', 'total', 'total_ttc', 'fk_statut', 'fk_user_author', 'fk_user_valid', 'fk_facture_source', 'fk_projet', 'fk_cond_reglement', 'fk_mode_reglement', 'date_lim_reglement', 'note', 'note_public', 'model_pdf'),
            array('rowid', 'facnumber'/* , 'entity', 'ref_ext', 'ref_int' */, 'ref_client', 'type', 'increment', 'fk_soc', 'datec', 'datef', 'date_valid'/* , 'tms' */, 'paye', 'amount', 'remise_percent', 'remise_absolue', 'remise', 'close_code', 'close_note', 'tva', /* 'localtax1', 'localtax2', */ 'total', 'total_ttc', 'fk_statut', 'fk_user_author', 'fk_user_valid', 'fk_facture_source', 'fk_projet'/* , 'fk_account', 'fk_currency' */, 'fk_cond_reglement', 'fk_mode_reglement', 'date_lim_reglement', 'note', 'note_public', 'model_pdf'/* , 'import_key', 'extraparams' */)
        ),
        array("llx_facturedet", "llx_facturedet",
            array('rowid', 'fk_facture', 'fk_product', 'description', 'tva_taux', 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'subprice', 'price', 'total_ht', 'total_tva', 'total_ttc', 'product_type', 'date_start', 'date_end', 'info_bits', 'fk_code_ventilation', 'fk_export_compta', 'special_code', 'rang'/* ,              'durSav', 'coef', 'lineFromComId', 'lineFromPropId' */),
            array('rowid', 'fk_facture'/* , 'fk_parent_line' */, 'fk_product', 'description', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx', */ 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'subprice', 'price', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */'total_ttc', 'product_type', 'date_start', 'date_end', 'info_bits', 'fk_code_ventilation', 'fk_export_compta', 'special_code', 'rang'/* , 'import_key' */)
        ),
        array("llx_fa_pr", "llx_element_element",
            array('fk_propal', '$%propal', 'fk_facture', '$%facture'),
            array('fk_source', 'sourcetype', 'fk_target', 'targettype')
        ),
        array("llx_co_fa", "llx_element_element",
            array('fk_commande', '$%commande', 'fk_facture', '$%facture'),
            array('fk_source', 'sourcetype', 'fk_target', 'targettype')
        ),
        array("llx_co_pr", "llx_element_element",
            array('fk_commande', '$%commande', 'fk_propale', '$%propal'),
            array('fk_source', 'sourcetype', 'fk_target', 'targettype')
        ),
        array("llx_paiement", "llx_paiement",
            array('rowid'/* , 'fk_facture' */, 'datec', 'tms', 'datep', 'amount', 'fk_paiement', 'num_paiement', 'note', 'fk_bank', 'fk_user_creat', 'fk_user_modif', 'statut', 'fk_export_compta'),
            array('rowid'/* , 'entity' */, 'datec', 'tms', 'datep', 'amount', 'fk_paiement', 'num_paiement', 'note', 'fk_bank', 'fk_user_creat', 'fk_user_modif', 'statut', 'fk_export_compta')
        ),
        array("llx_paiement_facture", "llx_paiement_facture",
            array(),
            array()
        ),
        array("llx_fichinter", "llx_Synopsis_fichinter",
            array(),
            array()
        ),
        array("llx_fichinterdet", "llx_Synopsis_fichinterdet",
            array(),
            array()
        ),
        array("babel_product", "llx_product",
            array('rowid', 'ref', 'datec', 'tms', 'label', 'description', 'note', 'price', 'price_ttc', 'price_base_type', 'tva_tx', /* 'price_loc', 'price_loc_ttc', */ 'fk_user_author', /* 'envente', 'nbvente', */'fk_product_type', 'duration', /* 'stock_propale', 'stock_commande', */ 'seuil_stock_alerte', /* 'stock_loc', */ 'barcode', 'fk_barcode_type', 'partnumber', 'weight', 'weight_units', 'volume', 'volume_units', 'canvas', /* 'magento_id', 'magento_product', 'magento_type', 'magento_sku', 'magento_cat', 'durSav', 'isSAV', 'durValid', 'reconductionAuto', 'VisiteSurSite', 'SLA', 'Maintenance', 'TeleMaintenance', 'Hotline', 'PrixAchatHT', 'qte', 'clause', */ 'external_id', /* 'qteTempsPerDuree', 'qteTktPerDuree' */),
            array('rowid', 'ref', /* 'entity', 'ref_ext', */ 'datec', 'tms', /* 'virtual', 'fk_parent', */ 'label', 'description', 'note', /* 'customcode', 'fk_country', */ 'price', 'price_ttc', /* 'price_min', 'price_min_ttc', */'price_base_type', 'tva_tx', /* 'recuperableonly', 'localtax1_tx', 'localtax2_tx', */ 'fk_user_author', /* 'tosell', 'tobuy', */ 'fk_product_type', 'duration', 'seuil_stock_alerte', 'barcode', 'fk_barcode_type', /* 'accountancy_code_sell', 'accountancy_code_buy', */ 'partnumber', 'weight', 'weight_units', /* 'length', 'length_units', 'surface', 'surface_units', */ 'volume', 'volume_units', /* 'stock', 'pmp', */ 'canvas', /* 'finished', 'hidden', */ 'import_key')
        ),
        array("babel_categorie", "llx_categorie",
            array('rowid', 'label', 'type'           ,  'description',           'visible', 'magento_id'/*, 'position', 'magento_product', 'level'*/),
            array('rowid', 'label', 'type'/*, 'entity'*/, 'description'/*, 'fk_soc'*/, 'visible', 'import_key')
        ),
        array("Babel_prepaCom_c_cat_listContent", "llx_Synopsis_PrepaCom_c_cat_listContent",
            array(),
            array()
        ),
        array("Babel_prepaCom_c_cat_total", "llx_Synopsis_PrepaCom_c_cat_total",
            array(),
            array()
        ),
        array("babel_categorie_association", "llx_categorie_association",
            array('fk_categorie_fille_babel', 'fk_categorie_mere_babel'),
            array('fk_categorie_fille', 'fk_categorie_mere')
        ),
        array("babel_categorie_product", "llx_categorie_product",
            array(),
            array()
        ),
        array("llx_expedition", "llx_expedition",
            array('rowid', 'tms', 'ref',          'ref_client', 'fk_soc',                                      'date_creation', 'fk_user_author', 'date_valid', 'fk_user_valid', 'date_expedition',          'fk_adresse_livraison', 'fk_expedition_methode',                  'fk_statut',                                                                    'note', 'model_pdf'),
            array('rowid', 'tms', 'ref'/*,  'entity'*/, 'ref_ext',  'fk_soc'/*, 'ref_int', 'ref_customer'*/, 'date_creation', 'fk_user_author', 'date_valid', 'fk_user_valid', 'date_expedition'/*, 'date_delivery'*/, 'fk_address', 'fk_expedition_methode'/*, 'tracking_number'*/, 'fk_statut'/*, 'height', 'width', 'size_units', 'size', 'weight_units', 'weight'*/, 'note', 'model_pdf')
        ),
        array("llx_expeditiondet", "llx_expeditiondet",
            array(),
            array()
        ),
        array("llx_contrat", "llx_contrat",
            array('rowid', 'ref',           'tms', 'datec', 'date_contrat', 'statut'/*, 'modelPdf'*/, 'mise_en_service', 'fin_validite', 'date_cloture', 'fk_soc', 'fk_projet', 'fk_commercial_signature', 'fk_commercial_suivi', 'fk_user_author', 'fk_user_mise_en_service', 'fk_user_cloture', 'note', 'note_public', 'linkedTo'),//, 'date_valid', 'is_financement', 'cessionnaire_refid', 'fournisseur_refid', 'tva_tx', 'line_order', 'warned', 'type', 'prorata', 'facturation_freq', 'condReg_refid', 'modeReg_refid'),
            array('rowid', 'ref'/*, 'entity'*/, 'tms', 'datec', 'date_contrat', 'statut',             'mise_en_service', 'fin_validite', 'date_cloture', 'fk_soc', 'fk_projet', 'fk_commercial_signature', 'fk_commercial_suivi', 'fk_user_author', 'fk_user_mise_en_service', 'fk_user_cloture', 'note', 'note_public', 'import_key')//, 'extraparams')
        ),
        array("llx_contratdet", "llx_contratdet",
            array('rowid', 'tms', 'fk_contrat', 'fk_product', 'statut', 'label', 'description', 'fk_remise_except', 'date_commande', 'date_ouverture_prevue', 'date_ouverture', 'date_fin_validite', 'date_cloture', 'tva_tx',                                 'qty', 'remise_percent'/*, 'isSubPricePerMonth'*/, 'subprice', 'price_ht', 'remise', 'total_ht', 'total_tva',                                       'total_ttc', 'info_bits', 'fk_user_author', 'fk_user_ouverture', 'fk_user_cloture', 'commentaire'),//, 'line_order', 'fk_commande_ligne', 'avenant', 'date_valid', 'prod_duree_loc'),
            array('rowid', 'tms', 'fk_contrat', 'fk_product', 'statut', 'label', 'description', 'fk_remise_except', 'date_commande', 'date_ouverture_prevue', 'date_ouverture', 'date_fin_validite', 'date_cloture', 'tva_tx'/*, 'localtax1_tx', 'localtax2_tx'*/, 'qty', 'remise_percent',                       'subprice', 'price_ht', 'remise', 'total_ht', 'total_tva'/*, 'total_localtax1', 'total_localtax2'*/, 'total_ttc', 'info_bits', 'fk_user_author', 'fk_user_ouverture', 'fk_user_cloture', 'commentaire')
        ),
        array("Babel_demandeInterv", "llx_Synopsis_demandeInterv",
            array(),
            array()
        ),
        array("Babel_demandeIntervdet", "llx_Synopsis_demandeIntervdet",
            array(),
            array()
        ),
        array("llx_co_exp", "llx_element_element",
            array("fk_commande", 'fk_expedition', '$%commande', '$%shipping'),
            array("fk_source", "fk_target", 'sourcetype', "targettype")
        )
    );
}

?>



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

include_once("./class/maj.class.php");



global $oldPref, $nbIframeMax, $nbIframe, $nbErreur;
$oldPref = "llx_";

$nbIframeMax = 4;



$nbIframe = 0;
$nbErreur = 0;

$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";
llxHeader("", "Importation de données");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Importation de données"));


if ($user->rights->SynopsisTools->Global->import != 1) {
    print "Ce module ne vous est pas accessible";
    llxFooter();
    exit(0);
}

require_once(DOL_DOCUMENT_ROOT . "/synopsistools/class/cronSynopsis.class.php");
$cron = new CronSynopsis($db);


if (isset($_GET['action']) && $_GET['action'] == "majSav") {
    $cron->majSav(isset($_REQUEST['mail']) && $_REQUEST['mail'] == "true");
} elseif (isset($_GET['action']) && $_GET['action'] == "verifCompte") {
    $cron->verifCompteFermer();
} elseif (isset($_GET['action']) && $_GET['action'] == "fusionChrono") {
    $cron->fusionChrono($_REQUEST['id1'], $_REQUEST['id2']);
    $cron->majChrono();
}
if (isset($_REQUEST['action']) && $_REQUEST['action'] == "majChrono") {
    $cron->majChrono();
} else if (isset($_GET['action']) && $_GET['action'] == "verif") {
    $cron->verif();
}



if (isset($_GET['action']) && $_GET['action'] == "majExpedition") {
    $sql = $db->query("SELECT * FROM `llx_expeditiondet` WHERE `fk_origin_line` NOT IN (SELECT `rowid` FROM `llx_commandedet` WHERE 1)");
    while ($result = $db->fetch_object($sql)) {
//        echo($result->fk_expedition."  ".$result->fk_origin_line."  <br/>");
//        $sql2 = $db->query("SELECT * FROM llx_expedition WHERE rowid = ".$result->fk_expedition ."");
//        $result2 = $db->fetch_object($sql2);
        $sql3 = $db->query("SELECT * FROM llx_commandedet WHERE fk_commande = (SELECT fk_source 
FROM  `llx_element_element` 
WHERE  `sourcetype` LIKE  'commande'
AND  `targettype` LIKE  'shipping' AND fk_target = " . $result->fk_expedition . ")");
        while ($result3 = $db->fetch_object($sql3)) {
            if ($result3->qty == $result->qty) {
                if (!isset($tabUtiliser[$result->rowid])) {
                    if (!isset($tabUtiliser2[$result3->rowid])) {
                        $tabUtiliser[$result->rowid] = true;
                        $tabUtiliser2[$result3->rowid] = true;
                        echo $result->rowid . "|" . $result->fk_origin_line . "|" . $result3->rowid . "expe " . $result->fk_expedition . "<br/>";
                        $db->query("UPDATE  `llx_expeditiondet` SET  `fk_origin_line` =  " . $result3->rowid . " WHERE  `rowid` = " . $result->rowid . " AND fk_origin_line = " . $result->fk_origin_line);
//                    break;
                    }
                }
            }
        }
    }
    echo "Fin maj";
}

if (isset($_GET['action']) && $_GET['action'] == "majFile") {
    $repl1 = "-";
    $repl2 = "–";
    echo "majFichier<br/><br/>";
    $dir = DOL_DATA_ROOT . "/propale/";
    $dataDir = opendir($dir);
    while ($Entry = readdir($dataDir)) {
        if (is_dir($dir . $Entry) && $Entry != "." && $Entry != "..") {
            $oldDir = $Entry;
            $newdir = str_replace($repl1, $repl2, $oldDir);
            $dataDir2 = opendir($dir . $oldDir . "/");
            while ($Entry2 = readdir($dataDir2)) {
                if (is_file($dir . $oldDir . "/" . $Entry2)) {
                    $oldFile = $dir . $oldDir . "/" . $Entry2;
                    $newFile = $dir . $oldDir . "/" . str_replace($oldDir, $newdir, $Entry2);
                    rename($oldFile, $newFile);
                    echo "Fichier " . $oldFile . " renomer en " . $newFile . "<br/>";
                }
            }
            rename($dir . "/" . $oldDir, $dir . "/" . $newdir);
            echo "Dossier " . $oldDir . " renomer en " . $newdir . "<br/>";
        }
    }
}
if (isset($_GET['action']) && $_GET['action'] == "sauvBdd") {
    echo maj::sauvBdd();
}
if (isset($_GET['action']) && $_GET['action'] == "import") {
    $dbD = $db;
//$dbS = getDoliDBInstance($conf->db->type, "127.0.0.1", "root", "roland2007", "gle1main", $Hconf->dbport);
//$dbS = getDoliDBInstance($conf->db->type, "127.0.0.1", "root", "x", "synopsis_oldBimp3", $Hconf->dbport);


    if (defined('IMPORT_BDD_HOST') && defined('IMPORT_BDD_USER') && defined('IMPORT_BDD_PASSE') && defined('IMPORT_BDD_NAME'))
        $dbS = getDoliDBInstance($conf->db->type, IMPORT_BDD_HOST, IMPORT_BDD_USER, IMPORT_BDD_PASSE, IMPORT_BDD_NAME, $dbD->dbport);
    else
        die("Les info de la base a importé sont incorrecte");

    $maj = new maj($dbS, $dbD);
    $maj->req("DELETE FROM " . MAIN_DB_PREFIX . "product_lang");
    $maj->startMaj(getTab());
    $maj->startMaj(array(// Modification de certaine table
        array("babel_categorie_association", MAIN_DB_PREFIX . "categorie",
            array('fk_categorie_fille_babel', 'fk_categorie_mere_babel'),
            array('rowid', 'fk_parent')
        ),
        array($oldPref . "contrat", MAIN_DB_PREFIX . "Synopsis_contrat_GMAO",
            array('rowid', 'condReg_refid', 'modeReg_refid'),
            array('id', 'condReg_refid', 'modeReg_refid')
        )), true);
//    $maj->rectifId(array(629,395,630,395,631,396,632,396,633,397,634,397,635,398,636,398,637,399,638,399,639,400,640,400,641,401,642,401,643,402,644,402,645,403,646,403,647,404,648,404,649,405,650,405,651,406,652,406,653,407,654,407,655,408,656,408,657,409,658,409,659,410,660,410,661,411,662,411,663,412,664,412,665,413,666,413,699,341,700,341,701,342,702,342,703,343,704,343,705,335,706,335,707,336,708,336,709,420,710,420,711,421,712,421,713,422,714,422,715,423,716,423,717,424,718,424,719,425,720,425,721,426,722,426,723,427,724,427,725,428,726,428,727,429,728,429,729,430,730,430,731,431,732,431,733,432,734,432,735,433,736,433,737,434,738,434,739,435,740,435,741,436,742,436,743,437,744,437,745,438,746,438,747,439,748,439,749,440,750,440,751,441,752,441,753,442,754,442,755,443,756,443,757,444,758,444,775,1579,776,1579));
    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "commandedet` SET `product_type`= 106 WHERE `fk_product` is null AND `total_ttc` = 0");
    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "contratdet` SET `fk_product` = (SELECT `fk_contrat_prod` FROM `" . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO` WHERE `contratdet_refid` = `rowid`)");
    $maj->req("DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Histo_User WHERE element_type = 'prepaCom'");
    $maj->req("DELETE FROM " . MAIN_DB_PREFIX . "expedition WHERE rowid NOT IN (SELECT DISTINCT `fk_expedition` FROM " . MAIN_DB_PREFIX . "expeditiondet)");
    $maj->req("UPDATE " . MAIN_DB_PREFIX . "commande SET `fk_user_author` = `fk_user_valid` WHERE `fk_user_author` is NULL AND `fk_user_valid` IS NOT NULL");
    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "product_extrafields` SET `2SLA` = '8' WHERE `2SLA` = '8 heures ouvrées' OR `2SLA` = '8 h. ouvrées' OR `2SLA` = '8 H. OUVRÉES'");
    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "product_extrafields` SET `2SLA` = '4M' WHERE `2SLA` = '4 heures ouvrées pour prise en main'");
    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "product_extrafields` SET `2SLA` = '4' WHERE `2SLA` = '4 heures ouvrées'");
    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "product_extrafields` SET `2SLA` = '16' WHERE `2SLA` = '16 heures ouvrées'");
    $maj->req("UPDATE " . MAIN_DB_PREFIX . "synopsischrono SET fk_societe = (SELECT `fk_soc` FROM " . MAIN_DB_PREFIX . "contrat c, " . MAIN_DB_PREFIX . "contratdet cd WHERE c.rowid = cd.fk_contrat AND cd.rowid = id)");
    $maj->req("DELETE FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id NOT IN (SELECT `chrono_refid` FROM `" . MAIN_DB_PREFIX . "synopsischrono_value` WHERE `key_id` = 1011) AND id IN (SELECT `chrono_refid` FROM `" . MAIN_DB_PREFIX . "synopsischrono_value` WHERE `key_id` = 1010 AND value IS NULL)");
    $maj->req("DELETE FROM " . MAIN_DB_PREFIX . "synopsischrono_value WHERE chrono_refid NOT IN (SELECT `id` FROM `" . MAIN_DB_PREFIX . "synopsischrono`)");
    $maj->req("DELETE FROM " . MAIN_DB_PREFIX . "element_element WHERE fk_target NOT IN (SELECT `id` FROM `" . MAIN_DB_PREFIX . "synopsischrono`) AND targettype = 'productCli'");
    $maj->req("UPDATE `" . MAIN_DB_PREFIX . "synopsischrono` c SET `ref` = CONCAT('PROD-', (SELECT `value` FROM `" . MAIN_DB_PREFIX . "synopsischrono_value` WHERE `chrono_refid` = c.id AND `key_id` = 1011 LIMIT 1)) WHERE ref IS NULL");
    $maj->req("update `" . MAIN_DB_PREFIX . "societe` set status = 1");
    $maj->req("update `" . MAIN_DB_PREFIX . "product_extrafields` set `2hotline` = 0, `2teleMaintenance` = 0 where `2visiteSurSite` > 0;");
    $maj->req("update `" . MAIN_DB_PREFIX . "product_extrafields` set `2hotline` = 0 where `2teleMaintenance` > 0;");
    $maj->req("INSERT INTO `" . MAIN_DB_PREFIX . "usergroup_user` (fk_usergroup, fk_user) VALUES(14,1)");

    $maj->ajoutDroitGr(array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 14), array(80000, 80001, 80002, 80003, 80004, 80005, 80885,
        342, 343, 230001, 161881, 161882, 229201,
        2411, 2412, 2413, 2414));
    $maj->ajoutDroitGr(array(14), array(358, 80881, 80882, 80883, 80884, 80886, 106,
        1001, 1002, 1003, 1004, 1005,
        229202, 229203, 229204, 229205, 229206)); //Admin
    $maj->ajoutDroitGr(array(2, 7, 14), array(21, 22, 23, 24, 25, 26, 27, 28, 230002)); //Propal
}


if (isset($_REQUEST['action']))
$cron->sortieHtml();

echo "<br/><br/>";

echo '<form action=""><input type="hidden" name="action" value="sauvBdd"/><input type="submit" value="Sauv BDD" class="butAction"/></form>';
echo "<br/>";
if (defined('IMPORT_BDD_HOST')) {
    echo '<form action=""><input type="hidden" name="action" value="import"/><input type="submit" value="Importer" class="butAction"/></form>';
    echo "<br/>";
}
echo '<form action=""><input type="hidden" name="action" value="majChrono"/><input type="submit" value="MAJ Chrono" class="butAction"/></form>';
echo "<br/>";
echo '<form action=""><input type="hidden" name="action" value="verif"/><input type="submit" value="Vérif général" class="butAction"/></form>';
echo "<br/>";
echo '<form action=""><input type="hidden" name="action" value="majFile"/><input type="submit" value="Migration fichiers" class="butAction"/></form>';
echo "<br/>";
echo '<form action=""><input type="hidden" name="action" value="majSav"/><input type="submit" value="Verif Sav" class="butAction"/></form>';
echo "<br/>";
echo '<form action=""><input type="hidden" name="action" value="verifCompte"/><input type="submit" value="Verif compte" class="butAction"/></form>';



global $logLongTime;
$logLongTime = false;
llxFooter();




    function getTab() {
        global $oldPref;
        return array(
            array($oldPref . "user", MAIN_DB_PREFIX . "user",
                array("rowid", "external_id", "datec", "tms", "login", "pass", "pass_crypted", "pass_temp", "name", "firstname", "office_phone", "office_fax", "user_mobile", "email", "admin", /* "local_admin",  "webcal_login", "phenix_login", "phenix_pass", */ "module_comm", "module_compta", "fk_societe", "fk_socpeople", "fk_member", "note", "datelastlogin", "datepreviouslogin", "egroupware_id", "ldap_sid", "statut", "lang", /* "CV_ndf", "Propal_seuilWarn", "PropalWarnValidator", "Propal_seuilValidResp", "Propal_validatorResp", "empnumber", "IM_user_name" */),
                array("rowid", /* "entity", */ "ref_ext"/* , "ref_int" */, "datec", "tms", "login", "pass", "pass_crypted", "pass_temp"/* , "civility" */, "lastname", "firstname", "office_phone", "office_fax", "user_mobile", "email"/* , "signature" */, "admin", /* "webcal_login", "phenix_login", "phenix_pass", */ "module_comm", "module_compta", "fk_societe", "fk_socpeople", "fk_member", "note", "datelastlogin", "datepreviouslogin", "egroupware_id", "ldap_sid", /* "openid", */ "statut", /* "photo", */ "lang")
            ),
//        array($oldPref . "user_rights", MAIN_DB_PREFIX . "user_rights",
//            array(),
//            array()
//        ),
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
                array("rowid", "nom", "external_id", "statut", "parent", "tms", "datec", "datea", "$this->titre", "code_client", "code_fournisseur", "code_compta", "code_compta_fournisseur", "address", "cp", "ville", "fk_departement", "fk_pays", "tel", "fax", "url", "email", "fk_secteur", "fk_effectif", "fk_typent", "fk_forme_juridique", "siren", "siret", "ape", "idprof4", "tva_intra", "capital", /* "description", */ "fk_stcomm", "note"/* , "services"/* , "prefix_comm" */, "client", "fournisseur", "supplier_account", "fk_prospectlevel", "customer_bad", "customer_rate", "supplier_rate", "fk_user_creat", "fk_user_modif", "remise_client", "mode_reglement", "cond_reglement", "tva_assuj"),
                array("rowid", "nom", "ref_ext", "statut", "parent", "tms", "datec", "datea", "status", "code_client", "code_fournisseur", "code_compta", "code_compta_fournisseur", "address", "zip", "town", "fk_departement", "fk_pays", "phone", "fax", "url", "email", "ref_int", "fk_effectif", "fk_typent", "fk_forme_juridique", "siren", "siret", "ape", "idprof4", "tva_intra", "capital", /* "description", */ "fk_stcomm", "note_private"/* , "services"/* , "prefix_comm" */, "client", "fournisseur", "supplier_account", "fk_prospectlevel", "customer_bad", "customer_rate", "supplier_rate", "fk_user_creat", "fk_user_modif", "remise_client", "mode_reglement", "cond_reglement", "tva_assuj")
            ),
            array($oldPref . "c_type_contact", MAIN_DB_PREFIX . "c_type_contact",
                array(),
                array()
            ),
            array($oldPref . "socpeople", MAIN_DB_PREFIX . "socpeople",
                array('rowid', 'datec', 'tms', 'fk_soc', 'civilite', 'name', 'firstname', 'address', 'cp', 'ville', 'fk_pays', 'birthday', 'poste', 'phone', 'phone_perso', 'phone_mobile', 'fax', 'email', 'jabberid', 'priv', 'fk_user_creat', 'fk_user_modif', 'note', 'external_id'/* , 'email2', 'email3', 'email4' */),
                array('rowid', 'datec', 'tms', 'fk_soc', /* 'entity', */ 'civility', 'lastname', 'firstname', 'address', 'zip', 'town', /* 'fk_departement', */ 'fk_pays', 'birthday', 'poste', 'phone', 'phone_perso', 'phone_mobile', 'fax', 'email', 'jabberid', 'priv', 'fk_user_creat', 'fk_user_modif', 'note_private', /* 'default_lang', 'canvas', */ 'import_key')
            ),
            array($oldPref . "societe_adresse_livraison", MAIN_DB_PREFIX . "socpeople",
                array('rowid+100000', 'datec', 'tms', 'fk_societe', 'label', 'address', 'cp', 'ville', 'fk_pays', 'tel', 'fax', 'fk_user_creat', 'fk_user_modif', 'note', 'external_id'),
                array('rowid', 'datec', 'tms', 'fk_soc', /* 'entity', 'civility', */ 'lastname', /* 'firstname', */ 'address', 'zip', 'town', /* 'fk_departement', */ 'fk_pays', /* 'birthday', 'poste', */ 'phone', /* 'phone_perso', 'phone_mobile', */ 'fax', /* 'email', 'jabberid', 'priv', */ 'fk_user_creat', 'fk_user_modif', 'note_private'/* , 'default_lang', 'canvas' */, 'import_key')
            ),
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
                array("rowid", "ref", "ref_client", "fk_soc", "fk_projet", "tms", "date_creation", "date_valid", "date_cloture", "date_commande", "fk_user_author", "fk_user_valid", "fk_user_cloture", "source", "fk_statut", "amount_ht", "remise_percent", "remise_absolue", "remise", "tva", "total_ht", "total_ttc", "note_private", "note_public", "model_pdf", "facture", "fk_cond_reglement", "fk_mode_reglement", "date_livraison")
            ),
            array($oldPref . "commandedet", MAIN_DB_PREFIX . "commandedet",
                array('rowid', 'fk_commande', 'fk_product', 'description', 'tva_tx', 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'price', 'subprice', 'total_ht', 'total_tva', 'total_ttc', 'info_bits', /* 'marge_tx', 'marque_tx', */ 'special_code', 'rang', /* 'finance_ok', 'logistique_ok', 'logistique_date_dispo', 'coef', 'external_id', 'pu_achat_ht', 'propaldet_refid' */),
                array('rowid', 'fk_commande', /* 'fk_parent_line', */ 'fk_product', 'description', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx', */ 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'price', 'subprice', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */ 'total_ttc', /* 'product_type', 'date_start', 'date_end', */ 'info_bits', /* 'marge_tx', 'marque_tx', */ 'special_code', 'rang', /* 'import_key' */)
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
            array("BIMP_commande_status", MAIN_DB_PREFIX . "element_element",
                array('commande_refid', '$%commande', 'statut_refid', '$%statutS'),
                array('fk_source', 'sourcetype', 'fk_target', 'targettype')
            ),
            array($oldPref . "propal", MAIN_DB_PREFIX . "propal",
                array('rowid', 'ref', 'ref_client', 'fk_soc', 'fk_projet', 'tms', 'datec', 'datep', 'fin_validite', 'date_valid', 'date_cloture', 'fk_user_author', 'fk_user_valid', 'fk_user_cloture', 'fk_statut', 'price', 'remise_percent', 'remise_absolue', 'remise'/* , 'date_abandon', 'fk_user_abandon', 'acompte_ht' */, 'total_ht', 'tva', 'total', 'fk_cond_reglement', 'fk_mode_reglement', 'note', 'note_public', 'model_pdf', 'date_livraison', 'fk_adresse_livraison'/* , 'date_demandeValid', 'isFinancement', 'isLocation', 'date_devis_fourn', 'fournisseur_refid', 'tva_tx_fin_refid', 'revision', 'orig_ref' */),
                array('rowid', 'ref', /* 'entity', 'ref_ext', 'ref_int', */ 'ref_client', 'fk_soc', 'fk_projet', 'tms', 'datec', 'datep', 'fin_validite', 'date_valid', 'date_cloture', 'fk_user_author', 'fk_user_valid', 'fk_user_cloture', 'fk_statut', 'price', 'remise_percent', 'remise_absolue', 'remise', 'total_ht', 'tva', /* 'localtax1', 'localtax2', */ 'total', /* 'fk_account', 'fk_currency', */ 'fk_cond_reglement', 'fk_mode_reglement', 'note_private', 'note_public', 'model_pdf', 'date_livraison', /* 'fk_availability', 'fk_demand_reason', 'import_key', 'extraparams', */ 'fk_delivery_address')
            ),
            array($oldPref . "propaldet", MAIN_DB_PREFIX . "propaldet",
                array('rowid', 'fk_propal', 'fk_product', 'description', 'fk_remise_except', 'tva_tx', 'qty', 'remise_percent', 'remise', 'price', 'subprice', 'total_ht', 'total_tva', 'total_ttc', 'info_bits', 'pa_ht', 'marge_tx', 'marque_tx', 'special_code', 'rang'/* , 'coef', 'dureeLoc' */),
                array('rowid', 'fk_propal', /* 'fk_parent_line', */ 'fk_product', 'description', 'fk_remise_except', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx'*, */ 'qty', 'remise_percent', 'remise', 'price', 'subprice', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */ 'total_ttc'/* , 'product_type', 'date_start', 'date_end' */, 'info_bits', 'pa_ht', 'marge_tx', 'marque_tx', 'special_code', 'rang')
            ),
//        array("Babel_Chrono", MAIN_DB_PREFIX."synopsischrono",
//            array(),
//            array()
//        ),
//        array("Babel_Chrono_group_rights", MAIN_DB_PREFIX."synopsischrono_group_rights",
//            array(),
//            array()
//        ),
//        array("Babel_Chrono_value", MAIN_DB_PREFIX."synopsischrono_value",
//            array(),
//            array()
//        ),
//        array("Babel_Process_group_rights", MAIN_DB_PREFIX."Synopsis_Process_group_rights",
//            array(),
//            array()
//        ),
            array("Babel_Processdet", MAIN_DB_PREFIX . "Synopsis_Processdet",
                array(),
                array()
            ),
            array("Babel_Processdet_active", MAIN_DB_PREFIX . "Synopsis_Processdet_active",
                array(),
                array()
            ),
            array("Babel_Processdet_validation", MAIN_DB_PREFIX . "Synopsis_Processdet_validation",
                array(),
                array()
            ),
            array("Babel_Processdet_value", MAIN_DB_PREFIX . "Synopsis_Processdet_value",
                array(),
                array()
            ),
            array($oldPref . "facture", MAIN_DB_PREFIX . "facture",
                array('rowid', 'facnumber', 'ref_client', 'type', 'increment', 'fk_soc', 'datec', 'datef', 'date_valid', 'paye', 'amount', 'remise_percent', 'remise_absolue', 'remise', 'close_code', 'close_note', 'tva', 'total', 'total_ttc', 'fk_statut', 'fk_user_author', 'fk_user_valid', 'fk_facture_source', 'fk_projet', 'fk_cond_reglement', 'fk_mode_reglement', 'date_lim_reglement', 'note', 'note_public', 'model_pdf'),
                array('rowid', 'facnumber'/* , 'entity', 'ref_ext', 'ref_int' */, 'ref_client', 'type', 'increment', 'fk_soc', 'datec', 'datef', 'date_valid'/* , 'tms' */, 'paye', 'amount', 'remise_percent', 'remise_absolue', 'remise', 'close_code', 'close_note', 'tva', /* 'localtax1', 'localtax2', */ 'total', 'total_ttc', 'fk_statut', 'fk_user_author', 'fk_user_valid', 'fk_facture_source', 'fk_projet'/* , 'fk_account', 'fk_currency' */, 'fk_cond_reglement', 'fk_mode_reglement', 'date_lim_reglement', 'note_private', 'note_public', 'model_pdf'/* , 'import_key', 'extraparams' */)
            ),
            array($oldPref . "facturedet", MAIN_DB_PREFIX . "facturedet",
                array('rowid', 'fk_facture', 'fk_product', 'description', 'tva_taux', 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'subprice', 'price', 'total_ht', 'total_tva', 'total_ttc', 'product_type', 'date_start', 'date_end', 'info_bits', 'fk_code_ventilation', 'fk_export_compta', 'special_code', 'rang'/* ,              'durSav', 'coef', 'lineFromComId', 'lineFromPropId' */),
                array('rowid', 'fk_facture'/* , 'fk_parent_line' */, 'fk_product', 'description', 'tva_tx', /* 'localtax1_tx', 'localtax2_tx', */ 'qty', 'remise_percent', 'remise', 'fk_remise_except', 'subprice', 'price', 'total_ht', 'total_tva', /* 'total_localtax1', 'total_localtax2', */ 'total_ttc', 'product_type', 'date_start', 'date_end', 'info_bits', 'fk_code_ventilation', 'fk_export_compta', 'special_code', 'rang'/* , 'import_key' */)
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
                array('rowid'/* , 'entity' */, 'datec', 'tms', 'datep', 'amount', 'fk_paiement', 'num_paiement', 'note_private', 'fk_bank', 'fk_user_creat', 'fk_user_modif', 'statut', 'fk_export_compta')
            ),
            array($oldPref . "paiement_facture", MAIN_DB_PREFIX . "paiement_facture",
                array(),
                array()
            ),
            array($oldPref . "fichinter", MAIN_DB_PREFIX . "fichinter",
                array('rowid', 'fk_soc', 'fk_projet', 'fk_contrat', 'ref', 'tms', 'datec', 'date_valid', 'datei', 'fk_user_author', 'fk_user_valid', 'fk_statut', 'duree', 'description', 'note_private', 'note_public', 'model_pdf'),
                array('rowid', 'fk_soc', 'fk_projet', 'fk_contrat', 'ref', 'tms', 'datec', 'date_valid', 'datei', 'fk_user_author', 'fk_user_valid', 'fk_statut', 'duree', 'description', 'note_private', 'note_public', 'model_pdf')
            ),
            array($oldPref . "fichinter", MAIN_DB_PREFIX . "synopsisfichinter",
                array('rowid', 'fk_commande', 'total_ht', 'total_tva', 'total_ttc', 'natureInter'),
                array('rowid', 'fk_commande', 'total_ht', 'total_tva', 'total_ttc', 'natureInter')
            ),
            array($oldPref . "fichinterdet", MAIN_DB_PREFIX . "fichinterdet",
                array('rowid', 'fk_fichinter', 'date', 'description', 'duree', 'rang'),
                array('rowid', 'fk_fichinter', 'date', 'description', 'duree', 'rang')
            ),
            array($oldPref . "fichinterdet", MAIN_DB_PREFIX . "synopsisfichinterdet",
                array('rowid', 'fk_typeinterv', 'fk_depProduct', 'tx_tva', 'pu_ht', 'qte', 'total_ht', 'total_tva', 'total_ttc', 'fk_contratdet', 'fk_commandedet', 'isForfait'),
                array('rowid', 'fk_typeinterv', 'fk_depProduct', 'tx_tva', 'pu_ht', 'qte', 'total_ht', 'total_tva', 'total_ttc', 'fk_contratdet', 'fk_commandedet', 'isForfait')
            ),
            array("Babel_Interv_extra_value", MAIN_DB_PREFIX . "synopsisfichinter_extra_value",
                array(),
                array()
            ),
            array("babel_product", MAIN_DB_PREFIX . "product",
                array('rowid', 'ref', 'datec', 'tms', 'label', 'description', 'note', 'price', 'price_ttc', 'price_base_type', 'tva_tx', /* 'price_loc', 'price_loc_ttc', */ 'fk_user_author', /* 'envente', 'nbvente', */ 'fk_product_type', 'duration', /* 'stock_propale', 'stock_commande', */ 'seuil_stock_alerte', /* 'stock_loc', */ 'barcode', 'fk_barcode_type', 'partnumber', 'weight', 'weight_units', 'volume', 'volume_units', 'canvas', /* 'magento_id', 'magento_product', 'magento_type', 'magento_sku', 'magento_cat', 'durSav', 'isSAV', 'durValid', 'reconductionAuto', 'VisiteSurSite', 'SLA', 'Maintenance', 'TeleMaintenance', 'Hotline', 'PrixAchatHT', 'qte', 'clause', */ 'external_id', /* 'qteTempsPerDuree', 'qteTktPerDuree' */),
                array('rowid', 'ref', /* 'entity', 'ref_ext', */ 'datec', 'tms', /* 'virtual', 'fk_parent', */ 'label', 'description', 'note', /* 'customcode', 'fk_country', */ 'price', 'price_ttc', /* 'price_min', 'price_min_ttc', */ 'price_base_type', 'tva_tx', /* 'recuperableonly', 'localtax1_tx', 'localtax2_tx', */ 'fk_user_author', /* 'tosell', 'tobuy', */ 'fk_product_type', 'duration', 'seuil_stock_alerte', 'barcode', 'fk_barcode_type', /* 'accountancy_code_sell', 'accountancy_code_buy', */ 'partnumber', 'weight', 'weight_units', /* 'length', 'length_units', 'surface', 'surface_units', */ 'volume', 'volume_units', /* 'stock', 'pmp', */ 'canvas', /* 'finished', 'hidden', */ 'import_key')
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
                array('rowid', 'tms', 'ref'/* ,  'entity' */, 'ref_ext', 'fk_soc'/* , 'ref_int', 'ref_customer' */, 'date_creation', 'fk_user_author', 'date_valid', 'fk_user_valid', 'date_expedition'/* , 'date_delivery' */, 'fk_address', 'fk_shipping_method'/* , 'tracking_number' */, 'fk_statut'/* , 'height', 'width', 'size_units', 'size', 'weight_units', 'weight' */, 'note_private', 'model_pdf')
            ),
            array($oldPref . "expeditiondet", MAIN_DB_PREFIX . "expeditiondet",
                array(),
                array()
            ),
            array($oldPref . "contrat", MAIN_DB_PREFIX . "contrat",
                array('rowid', 'ref', 'tms', 'datec', 'date_contrat', 'statut'/* , 'modelPdf' */, 'mise_en_service', 'fin_validite', 'date_cloture', 'fk_soc', 'fk_projet', 'fk_commercial_signature', 'fk_commercial_suivi', 'fk_user_author', 'fk_user_mise_en_service', 'fk_user_cloture', 'note', 'note_public', 'linkedTo', /* , 'date_valid', 'is_financement', 'cessionnaire_refid', 'fournisseur_refid', 'tva_tx', 'line_order', 'warned', */ 'type'), // 'prorata', 'facturation_freq', 'condReg_refid', 'modeReg_refid'),
                array('rowid', 'ref'/* , 'entity' */, 'tms', 'datec', 'date_contrat', 'statut', 'mise_en_service', 'fin_validite', 'date_cloture', 'fk_soc', 'fk_projet', 'fk_commercial_signature', 'fk_commercial_suivi', 'fk_user_author', 'fk_user_mise_en_service', 'fk_user_cloture', 'note_private', 'note_public', 'import_key', 'extraparams')
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
            array("Babel_demandeInterv", MAIN_DB_PREFIX . "synopsisdemandeinterv",
                array(),
                array()
            ),
            array("Babel_demandeIntervdet", MAIN_DB_PREFIX . "synopsisdemandeintervdet",
                array('rowid', 'fk_demandeInterv', 'date', 'description', 'duree', 'rang', 'fk_typeinterv', 'tx_tva', 'pu_ht', 'qte', 'total_ht', 'total_tva', 'total_ttc', 'fk_contratdet', 'fk_commandedet', 'isForfait'),
                array('rowid', 'fk_synopsisdemandeinterv', 'date', 'description', 'duree', 'rang', 'fk_typeinterv', 'tx_tva', 'pu_ht', 'qte', 'total_ht', 'total_tva', 'total_ttc', 'fk_contratdet', 'fk_commandedet', 'isForfait')
            ),
            array($oldPref . "co_exp", MAIN_DB_PREFIX . "element_element",
                array("fk_commande", 'fk_expedition', '$%commande', '$%shipping'),
                array("fk_source", "fk_target", 'sourcetype', "targettype")
            ),
            array("Babel_li_interv", MAIN_DB_PREFIX . "element_element",
                array("di_refid", 'fi_refid', '$%DI', '$%FI'),
                array("fk_source", "fk_target", 'sourcetype', "targettype")
            ),
//        array("Babel_contrat_annexe", MAIN_DB_PREFIX . "Synopsis_contrat_annexe",
//            array(),
//            array()
//        ),
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
                array('$%4', 'rowid', '$%102', 'fk_adresse_livraison+100000'),
                array('statut', 'element_id', 'fk_c_type_contact', 'fk_socpeople')
            ),
//        array($oldPref . "expedition", MAIN_DB_PREFIX . "element_contact",
//            array( '$%4', 'rowid', '$%102', 'fk_adresse_livraison'),
//            array( 'statut',  'element_id', 'fk_c_type_contact', 'fk_socpeople')
//        ),
            array("Babel_product_serial_cont", MAIN_DB_PREFIX . "Synopsis_product_serial_cont",
                array(),
                array()
            ),
            array("Babel_GMAO_contratdet_prop", MAIN_DB_PREFIX . "synopsischrono",
                array("contratdet_refid", "$%101", "$%1"),
                array("id", "model_refid", "fk_user_author")
            ),
            array("Babel_GMAO_contratdet_prop", MAIN_DB_PREFIX . "synopsischrono_value",
                array("contratdet_refid", "fk_prod", "$%1010"),
                array("chrono_refid", "value", "key_id")
            ),
            array("Babel_GMAO_contratdet_prop", MAIN_DB_PREFIX . "element_element",
                array("contratdet_refid", "$%contratdet", "contratdet_refid", "$%productCli"),
                array('fk_source', 'sourcetype', 'fk_target', 'targettype')
            ),
            array($oldPref . "contrat", MAIN_DB_PREFIX . "element_element",
                array("linkedTo", "$%commande", "rowid", "$%contrat"),
                array('fk_source', 'sourcetype', 'fk_target', 'targettype')
            ),
            array("Babel_product_serial_cont", MAIN_DB_PREFIX . "synopsischrono_value",
                array("element_id", "serial_number", "$%1011"),
                array("chrono_refid", "value", "key_id")
            ),
            array("Babel_product_serial_cont", MAIN_DB_PREFIX . "synopsischrono_value",
                array("element_id", "date_creation", "$%1014"),
                array("chrono_refid", "value", "key_id")
            ),
            array("Babel_product_serial_cont", MAIN_DB_PREFIX . "synopsischrono_value",
                array("element_id", "date_fin_SAV", "$%1015"),
                array("chrono_refid", "value", "key_id")
            ),
            array("Babel_User_PrixTypeInterv", MAIN_DB_PREFIX . "synopsisfichinter_User_PrixTypeInterv",
                array(),
                array()
            ),
            array("Babel_User_PrixDepInterv", MAIN_DB_PREFIX . "synopsisfichinter_User_PrixDepInterv",
                array(),
                array()
            ),
            array($oldPref . "actioncomm", MAIN_DB_PREFIX . "actioncomm",
                array('id', 'datec', 'datep', 'datep2', 'datea', 'datea2', 'tms', 'fk_action', 'label', /* 'fk_projet', */ 'fk_soc', 'fk_contact', 'fk_parent', 'fk_user_action', 'fk_user_done', 'fk_user_author', 'fk_user_mod', 'priority', 'punctual', 'percent', 'durationp', 'durationa', 'note', /* 'propalrowid', 'fk_commande', 'fk_facture' */),
                array('id', 'datec', 'datep', 'datep2', 'datea', 'datea2', 'tms', 'fk_action', 'label', 'fk_soc', 'fk_contact', 'fk_parent', 'fk_user_action', 'fk_user_done', 'fk_user_author', 'fk_user_mod', 'priority', 'punctual', 'percent', 'durationp', 'durationa', 'note')
            ),
        );
    }


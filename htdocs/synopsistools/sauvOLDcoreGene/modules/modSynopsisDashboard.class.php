<?php

/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 		\defgroup   mymodule     Module MyModule
 *      \brief      Example of a module descriptor.
 * 					Such a file must be copied into htdocs/includes/module directory.
 */
/**
 *      \file       htdocs/core/modules/modMyModule.class.php
 *      \ingroup    mymodule
 *      \brief      Description and activation file for module MyModule
 * 		\version	$Id: modMyModule.class.php,v 1.67 2011/08/01 13:26:21 hregis Exp $
 */
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
 * 		\class      modMyModule
 *      \brief      Description and activation class for module MyModule
 */
class modSynopsisDashboard extends DolibarrModules {

    /**
     *   \brief      Constructor. Define names, constants, directories, boxes, permissions
     *   \param      DB      Database handler
     */
    function modSynopsisDashboard($DB) {
        global $langs, $conf;

        $this->db = $DB;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 430001;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'modSynopsisDashboard';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "Synopsis";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = "Dashboard";
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Ajoute un tableau de bord ajax pour chaque utilisateur";
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = '1.0';
        // Key used in ".MAIN_DB_PREFIX."const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_SYNOPSISDASHBOARD';
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'generic';

        // Defined if the directory /mymodule/includes/triggers/ contains triggers or not
        $this->triggers = 0;

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/mymodule/temp");
        $this->dirs = array();
        $r = 0;

        // Relative path to module style sheet if exists. Example: '/mymodule/css/mycss.css'.
        //$this->style_sheet = '/mymodule/mymodule.css.php';
        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array("mymodulesetuppage.php");

        // Dependencies
        $this->depends = array();  // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array(); // List of modules id to disable if this one is disabled
        $this->phpmin = array(5, 0);     // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3, 0); // Minimum version of Dolibarr required by module
        $this->langfiles = array("langfiles@mymodule");

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
        //                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
        //                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',1)
        $this->const = array();

        // Array to add new pages in new tabs
        // Example: $this->tabs = array('objecttype:+tabname1:Title1:@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  // To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  // To add another new tab identified by code tabname2
        //                              'objecttype:-tabname');                                                     // To remove an existing tab identified by code tabname
        // where objecttype can be
        // 'thirdparty'       to add a tab in third party view
        // 'intervention'     to add a tab in intervention view
        // 'order_supplier'   to add a tab in supplier order view
        // 'invoice_supplier' to add a tab in supplier invoice view
        // 'invoice'          to add a tab in customer invoice view
        // 'order'            to add a tab in customer order view
        // 'product'          to add a tab in product view
        // 'stock'            to add a tab in stock view
        // 'propal'           to add a tab in propal view
        // 'member'           to add a tab in fundation member view
        // 'contract'         to add a tab in contract view
        // 'user'             to add a tab in user view
        // 'group'            to add a tab in group view
        // 'contact'          to add a tab in contact view
        // 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        $this->tabs = array();

        // Dictionnaries
        $this->dictionnaries = array();
        /*
          $this->dictionnaries=array(
          'langs'=>'cabinetmed@cabinetmed',
          'tabname'=>array(MAIN_DB_PREFIX."cabinetmed_diaglec",MAIN_DB_PREFIX."cabinetmed_examenprescrit",MAIN_DB_PREFIX."cabinetmed_motifcons"),
          'tablib'=>array("DiagnostiqueLesionnel","ExamenPrescrit","MotifConsultation"),
          'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_diaglec as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_examenprescrit as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_motifcons as f'),
          'tabsqlsort'=>array("label ASC","label ASC","label ASC"),
          'tabfield'=>array("code,label","code,label","code,label"),
          'tabfieldvalue'=>array("code,label","code,label","code,label"),
          'tabfieldinsert'=>array("code,label","code,label","code,label"),
          'tabrowid'=>array("rowid","rowid","rowid"),
          'tabcond'=>array($conf->cabinetmed->enabled,$conf->cabinetmed->enabled,$conf->cabinetmed->enabled)
          );
         */

        // Boxes
        // Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();   // List of boxes
        $r = 0;
        // Example:
        /*
          $this->boxes[$r][1] = "myboxa.php";
          $r++;
          $this->boxes[$r][1] = "myboxb.php";
          $r++;
         */

        // Permissions
        $this->rights = array();  // Permission array used by this module
        $r = 0;

        // Add here list of permission defined by an id, a label, a boolean and two constant strings.
        // Example:
        // $this->rights[$r][0] = 2000; 				// Permission id (must not be already used)
        // $this->rights[$r][1] = 'Permision label';	// Permission label
        // $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
        // $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        // $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        // $r++;
        // Main menu entries
        $this->menus = array();   // List of menus to add
        $r = 0;

        // Add here entries to declare new menus
        // Example to declare the Top Menu entry:
        // $this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
        //							'type'=>'top',			// This is a Top menu entry
        //							'titre'=>'MyModule top menu',
        //							'mainmenu'=>'mymodule',
        //							'leftmenu'=>'1',		// Use 1 if you also want to add left menu entries using this descriptor.
        //							'url'=>'/mymodule/pagetop.php',
        //							'langs'=>'mylangfile',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
        //							'position'=>100,
        //							'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
        //							'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
        //							'target'=>'',
        //							'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
        // $r++;
        //
		// Example to declare a Left Menu entry:
        // $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
        //							'type'=>'left',			// This is a Left menu entry
        //							'titre'=>'MyModule left menu 1',
        //							'mainmenu'=>'mymodule',
        //							'url'=>'/mymodule/pagelevel1.php',
        //							'langs'=>'mylangfile',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
        //							'position'=>100,
        //							'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
        //							'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
        //							'target'=>'',
        //							'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
        // $r++;
        //
		// Example to declare another Left Menu entry:
        // $this->menu[$r]=array(	'fk_menu'=>'r=1',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
        //							'type'=>'left',			// This is a Left menu entry
        //							'titre'=>'MyModule left menu 2',
        //							'mainmenu'=>'mymodule',
        //							'url'=>'/mymodule/pagelevel2.php',
        //							'langs'=>'mylangfile',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
        //							'position'=>100,
        //							'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
        //							'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
        //							'target'=>'',
        //							'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
        // $r++;
        // Exports
        $r = 1;

        // Example:
        // $this->export_code[$r]=$this->rights_class.'_'.$r;
        // $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        // $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
        // $this->export_permission[$r]=array(array("facture","facture","export"));
        // $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.ref'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
        // $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.ref'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
        // $this->export_sql_start[$r]='SELECT DISTINCT ';
        // $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
        // $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
        // $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
        // $r++;
    }

    /**
     * 		Function called when module is enabled.
     * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * 		It also creates data directories.
     *      @return     int             1 if OK, 0 if KO
     */
    function init() {
        $sql = array("CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_Dashboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `params` longtext,
  `user_refid` int(11) DEFAULT NULL,
  `dash_type_refid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_refid` (`user_refid`),
  KEY `dash_type_refid` (`dash_type_refid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_Dashboard_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_refid` int(11) DEFAULT NULL,
  `type_refid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `module_refid` (`module_refid`),
  KEY `type_refid` (`type_refid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_Dashboard_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(50) DEFAULT NULL,
  `parent` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_Dashboard_settings` (
  `module_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `value` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_index` (`module`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_Dashboard_page` (`id`, `page`, `parent`) VALUES
(1, 'GMAO-SAV', 2),
(2, 'GMAO', NULL),
(3, 'Ventes', NULL),
(4, 'Accueil', NULL),
(5, 'Gestion', NULL),
(6, 'Produit/Services', NULL),
(7, 'Achat', NULL),
(8, 'Tech', NULL),
(9, 'Tiers', NULL),
(10, 'Produits', 6),
(11, 'Services', 6),
(12, 'Stock', 6),
(13, 'Catégorie', 6),
(14, 'Facture', 7),
(15, 'Commande', 7),
(16, 'Prospect', 3),
(17, 'Client', 3),
(18, 'Contacts', 3),
(19, 'Action Co.', 3),
(20, 'Propal', 3),
(21, 'Commandes', 3),
(22, 'Expéditions', 3),
(23, 'Contrat', 3),
(24, 'Interventions', 3),
(25, 'Calendrier', 3),
(26, 'Fourn', 5),
(27, 'Client', 5),
(28, 'Cessionnaire', 5),
(29, 'Remise Chèque', 5),
(30, 'propale', 5),
(31, 'Commande', 5),
(32, 'Taxes', 5),
(33, 'Ventilation', 5),
(34, 'Prélèvement', 5),
(35, 'Banques', 5),
(36, 'Projet', NULL),
(37, 'Tache', 36),
(38, 'Activité', 36),
(39, 'Outils', NULL),
(40, 'Interventions', 8),
(41, 'Affaire', NULL),
(42, 'GMAO-Retour', 2),
(43, 'Prepa.', NULL),
(44, 'Groupe Com.', 3),
(45, 'Process', NULL),
(46, 'Chrono', NULL);",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget` (`id`, `nom`, `module`, `active`) VALUES
(4, 'SAV - En attente de traitement', 'listSAVAttenteTraitement', 1),
(8, 'SAV - Nouvelle demande', 'creerSAV', 1),
(9, 'SAV - En r&eacute;paration', 'listSAVAttenteReparation', 1),
(10, 'SAV - En attente du client', 'listSAVAttenteClient', 1),
(12, 'GMAO - Liens', 'liensGMAO', 1),
(13, 'GMAO - Tickets non pris', 'listTicketAttente', 1),
(14, 'Propositions commerciales - Recherche', 'recherchePropale', 1),
(15, 'Propositions commerciales - 10 dernier&egrave;s brouillons', 'listPropaleBrouillon', 1),
(16, 'Contrat - Recherche', 'rechercheContrat', 1),
(17, 'Commande client - 10 derniers brouillons', 'listCommandeBrouillon', 1),
(18, 'Action com. - 10 derni&egrave;res actions', 'listActionTODO', 1),
(19, 'Action com. - 10 derni&egrave;res effectu&eacute;es', 'listActionDone', 1),
(20, 'Tiers - 5 derniers clients/prospects', 'listClientProspect', 1),
(21, 'Propositions commerciales - 10 dernier&egrave;s prop. ouvertes', 'listPropaleOuverte', 1),
(22, 'Propositions commerciales - 10 dernier&egrave;s prop. ferm&eacute;es', 'listPropaleFerme', 1),
(23, 'Propositions commerciales - 10 dernier&egrave;s prop. &agrave; valider', 'listPropaleAValider', 1),
(24, 'Facture - Recherche', 'rechercheFacture', 1),
(25, 'Facture - 10 derniers brouillons', 'listFactureBrouillon', 1),
(26, 'Facture fournisseur - 10 derniers brouillons', 'listFactureFournBrouillon', 1),
(27, 'Charges sociales - A payer', 'listChargeAPayer', 1),
(28, 'Tiers - 5 derniers clients', 'listClient', 1),
(29, 'Tiers - 5 derniers fournisseurs', 'listFourn', 1),
(30, 'Commande client - 10 derniers com. &agrave; facturer', 'listCommandeAFacturer', 1),
(31, 'Facture - 10 derniers impay&eacute;s', 'listFactureImpayee', 1),
(32, 'Facture fournisseur - 10 derni&egrave;res impay&eacute;s', 'listFactureFournImpayee', 1),
(33, 'Fournisseur - Cat&eacute;gories', 'categorieFourn', 1),
(34, 'Fournisseur - Stat. commandes', 'statCommandeFourn', 1),
(35, 'Commande fournisseur - 10 derniers brouillons', 'listCommandeFournBrouillon', 1),
(36, 'Produits/Services - Recherche', 'rechercheProduit', 1),
(37, 'Produits/Services - Statistiques', 'statProduitService', 1),
(38, 'Produits - 10 derniers ajout&eacute;s', 'listProduit', 1),
(49, 'Fournisseurs - Les 10 derni&egrave;res factures impay&eacute;es', 'boxFactureFournImp', 1),
(50, 'Propositions commerciales - Les 10 derni&egrave;res prop. enregistr&eacute;s', 'boxDernierePropale', 1),
(51, 'Votre tableau de board - Aide', 'hello_world', 1),
(52, 'Zimbra - RSS', 'boxZimbra', 0),
(55, 'Hello Bevan', 'hello_bevan', 0),
(58, 'Contrat - Derniers produits/services contract&eacute;s', 'boxServicesVendus', 1),
(59, 'Tiers - Derniers prospects', 'boxProspect', 1),
(60, 'Facture - Derni&egrave;res factures clients', 'boxFacture', 1),
(61, 'Tiers - Derniers fournisseurs', 'boxFournisseurs', 1),
(62, 'Produits/Services - Derniers produits/services', 'boxProduits', 1),
(63, 'Action com. - Derni&egrave;res actions', 'boxAction', 1),
(65, 'Tiers - Derniers clients', 'boxClient', 1),
(66, 'Commande client - Derni&egrave;res commandes', 'boxCommande', 1),
(67, 'Banque - Soldes Comptes courants', 'boxCompte', 1),
(68, 'DI - Derni&egrave;res demandes d&#039;intervention', 'boxsynopsisdemandeinterv', 1),
(69, 'NDF - Informations sur les derniers d&eacute;placements', 'boxDeplacement', 1),
(70, 'Facture - Derni&egrave;res factures fournisseurs', 'boxFactureFourn', 1),
(72, 'Facture - Plus anciennes factures clients impay&eacute;es', 'boxFactureImp', 1),
(73, 'Cat&eacute;gories - Arbre produit', 'treeCategoriesProduits', 1),
(74, 'Commande Fournisseur - Personnes habilit&eacute;es &agrave; approuver les commandes', 'listUserCanApproveComFourn', 1),
(82, 'Produits/Services - 10 derniers ajouts', 'listProduitService', 1),
(83, 'Services - 10 derniers ajouts', 'listService', 1),
(85, 'Produits - Statistiques', 'statProduit', 1),
(86, 'Services - Statistiques', 'statService', 1),
(87, 'Stats - 5 meilleures ventes produits/services', 'statOfcProduits', 1),
(88, 'Stats - Toutes les propositions com. par status', 'statOfcPropalStatusAll', 1),
(92, 'Stats - Mes propositions com. par status', 'statOfcPropalMyStatus', 1),
(93, 'Stats - Toutes les commandes par status', 'statOfcOrderStatusAll', 1),
(94, 'Stats - Solde bancaire', 'statOfcSoldeBanque', 1),
(95, 'Prospects - Statistiques', 'statProspect', 1),
(97, 'Prospects - 10 derniers &agrave; contacter', 'listSocToContact', 1),
(98, 'Liens CRM', 'liensCRM', 1),
(99, 'Commande client - 10 derniers com. &agrave; traiter', 'listCommandeATraiter', 1),
(100, 'Commande client - 10 derniers com. cl&ocirc;tur&eacute;es', 'listCommandeCloture', 1),
(101, 'Commande client - 10 derniers com. en cours', 'listCommandeEnCours', 1),
(104, 'Exp&eacute;dition - Rechercher', 'rechercheExpedition', 1),
(105, 'Exp&eacute;dition - 5 derni&egrave;res exp&eacute;dition', 'listExpedition', 1),
(106, 'Contrat - 10 derniers brouillons', 'listContratBrouillon', 1),
(107, 'Contrat - L&eacute;gendes', 'contratLegend', 1),
(108, 'Contrat - 10 derniers contrats modifi&eacute;s', 'listContrat', 1),
(109, 'Contrat - 10 derniers services inactifs (parmi les contrats valid&eacute;s)', 'listContratServiceInactif', 1),
(110, 'Contrat - 10 derniers services modifi&eacute;s', 'listContratServiceModifie', 1),
(111, 'Remise de ch&egrave;que - 10 derni&egrave;res remises', 'listBordereauRemiseCheque', 1),
(112, 'Remise de ch&egrave;que - Statistiques', 'statRemiseCheque', 1),
(113, 'Compta - Lignes &agrave; ventiller', 'statAVentiler', 1),
(114, 'Compta - Stats. comptes g&eacute;n&eacute;raux (client)', 'statComptaVentile', 1),
(116, 'Compta - Stats. comptes g&eacute;n&eacute;raux (fourn.)', 'statComptaVentileFourn', 1),
(118, 'Pr&eacute;l&egrave;vement - 10 derniers pr&eacute;l&egrave;vements', 'listPrelevement', 1),
(119, 'Pr&eacute;l&egrave;vement - 10 derni&egrave;re factures en attente', 'listPrelevementFacture', 1),
(120, 'Pr&eacute;l&egrave;vement - Statistiques', 'statPrelevement', 1),
(121, 'Projets - Statistiques', 'statProject', 1),
(122, 'Projets - Statistiques par soci&eacute;t&eacute;', 'statProjectPerSoc', 1),
(131, 'Projets - T&acirc;che en d&eacute;passement', 'statProjectWarning', 1),
(134, 'Projets - Statistiques temps par t&acirc;che', 'statProjectTaskPerDuration', 1),
(135, 'Projets - Statistiques par t&acirc;che', 'statProjectPerTask', 1),
(137, 'Affaire - 10 derni&egrave;res affaires', 'listAffaire', 1),
(138, 'Stats - 5 meilleures ventes produits', 'statOfcProduit', 1),
(139, 'Stats - 5 meilleures ventes de contrat', 'statOfcProduitContrat', 1),
(140, 'Stats - 5 meilleures ventes services', 'statOfcService', 1),
(141, 'Stock - Rechercher', 'rechercheStock', 1),
(142, 'Stock - 10 derniers entrepots', 'listStock', 1),
(143, 'Actions - Rechercher', 'rechercheAction', 1),
(144, 'Liens Actions', 'liensAction', 1),
(145, 'Commande client - Rechercher', 'rechercheCommande', 1),
(146, 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions &agrave; valider', 'listExpeditionAValider', 1),
(147, 'FI - Les  derni&egrave;res fiches d&#039;intervention', 'boxFicheInterv', 1),
(148, 'Projet - Liste par soci&eacute;t&eacute', 'listProjetSoc', 1),
(149, 'Projet - Liste &amp; avancement', 'listProjet', 1),
(150, 'Projets - Activit&eacute;', 'listProjetActivite', 1),
(151, 'Stats - Activit&eacute;s pr&eacute;vues', 'statOfcProjetActivitePrevu', 1),
(152, 'Stats - Activit&eacute;s effectu&eacute;es', 'statOfcProjetActiviteEffective', 1),
(153, 'Stats - Mon Activit&eacute;s pr&eacute;vus', 'statOfcProjetMonActivitePrevu', 1),
(154, 'Stats - Mon Activit&eacute;s effectu&eacute;es', 'statOfcProjetMonActiviteEffective', 1),
(155, 'Projets - Mon activit&eacute; journali&egrave;re', 'listProjetMonActiviteDetail', 1),
(156, 'Projets - Mon activit&eacute;', 'listProjetMonActivite', 1),
(158, 'Retards - Liste des retards', 'listRetard', 1),
(159, 'Cat&eacute;gories - Arbre soci&eacute;t&eacute;s', 'treeCategoriesSocietes', 1),
(160, 'Cat&eacute;gories - Arbre fournisseurs', 'treeCategoriesFourn', 1),
(161, 'Tiers - 5 derniers cessionnaires', 'listCess', 1),
(163, 'GA - 10 derniers contrats modifi&eacute;s', 'listContratGA', 1),
(164, 'GA - 10 derni&egrave;res factures', 'listFactureCess', 1),
(165, 'GMAO - 10 derniers contrats SAV modifi&eacute;s', 'listContratSAV', 1),
(166, 'GMAO - 10 prochains contrats SAV &agrave &eacute;ch&eacute;ance', 'listContratSAVParDateFin', 1),
(167, 'Pr&eacute;paration commande - 10 derniers com. non examin&eacute;', 'listPrepaCommande', 1),
(168, 'Pr&eacute;paration commande - 10 derniers com. en cours', 'listPrepaCommandeEnCours', 1),
(169, 'Pr&eacute;paration commande - 10 derniers com. en attente', 'listPrepaCommandeProb', 1),
(170, 'Pr&eacute;paration commande - 10 derniers groupes modifi&eacute;s', 'listPrepaGroupCommande', 1),
(171, 'Navigation - Derniers &eacute;l&eacute;ments visit&eacute;s', 'NavigationUser', 1),
(172, 'Contrat - 10 prochains contrats à échéance', 'listContratMixte', 1),
(173, 'Pr&eacute;paration commande - Exp&eacute;dition - Derni&egrave;res exp&eacute;ditions', 'listExpeditionPrepaCommande', 1),
(174, 'Pr&eacute;paration commande - Derniers contrats &agrave; saisir', 'listPrepaCommandeContratAFaire', 1),
(175, 'Propositions commerciales - 10 derni&egrave;res prop. de financement', 'listPropaleFinancement', 0),
(176, 'Pr&eacute;paration commande - Exp&eacute;dition - Derni&egrave;res exp&eacute;ditions &agrave; traiter', 'listExpeditionPrepaCommandeAvecFiltreNotify', 1),
(177, 'DI - Prochaines demandes d&#039;intervention', 'boxsynopsisdemandeinterv2', 1);
",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_Dashboard` (`id`, `params`, `user_refid`, `dash_type_refid`) VALUES
(321, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:1;s:9:\"liensGMAO\";i:0;}i:1;a:4:{s:8:\"creerSAV\";i:0;s:24:\"listSAVAttenteTraitement\";i:1;s:24:\"listSAVAttenteReparation\";i:1;s:20:\"listSAVAttenteClient\";i:1;}i:2;a:1:{s:17:\"listTicketAttente\";i:0;}}', NULL, 2),
(382, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:1;s:8:\"creerSAV\";i:0;}i:1;a:3:{s:24:\"listSAVAttenteTraitement\";i:1;s:24:\"listSAVAttenteReparation\";i:1;s:20:\"listSAVAttenteClient\";i:1;}i:2;a:1:{s:9:\"liensGMAO\";i:0;}}', NULL, 1),
(472, 'a:3:{i:0;a:3:{s:11:\"hello_world\";i:0;s:16:\"rechercheContrat\";i:0;s:16:\"recherchePropale\";i:0;}i:1;a:2:{s:20:\"listPropaleBrouillon\";i:0;s:21:\"listCommandeBrouillon\";i:0;}i:2;a:5:{s:14:\"listActionTODO\";i:0;s:14:\"listActionDone\";i:0;s:18:\"listClientProspect\";i:0;s:18:\"listPropaleOuverte\";i:0;s:16:\"listPropaleFerme\";i:0;}}', NULL, 3),
(488, 'a:3:{i:0;a:2:{s:16:\"rechercheFacture\";i:0;s:16:\"listChargeAPayer\";i:0;}i:1;a:2:{s:20:\"listFactureBrouillon\";i:0;s:25:\"listFactureFournBrouillon\";i:0;}i:2;a:5:{s:10:\"listClient\";i:0;s:9:\"listFourn\";i:0;s:21:\"listCommandeAFacturer\";i:0;s:18:\"listFactureImpayee\";i:0;s:23:\"listFactureFournImpayee\";i:0;}}', NULL, 5),
(534, 'a:3:{i:0;a:2:{s:16:\"rechercheProduit\";i:0;s:18:\"statProduitService\";i:0;}i:1;a:1:{s:22:\"treeCategoriesProduits\";i:0;}i:2;a:1:{s:11:\"boxProduits\";i:0;}}', NULL, 6),
(552, 'a:3:{i:0;a:2:{s:11:\"statService\";i:0;s:16:\"rechercheProduit\";i:0;}i:1;a:1:{s:22:\"treeCategoriesProduits\";i:0;}i:2;a:1:{s:11:\"listService\";i:0;}}', NULL, 11),
(560, 'a:3:{i:0;a:2:{s:11:\"statProduit\";i:0;s:16:\"rechercheProduit\";i:0;}i:1;a:1:{s:11:\"listProduit\";i:0;}i:2;a:2:{s:22:\"treeCategoriesProduits\";i:0;s:15:\"statOfcProduits\";i:0;}}', NULL, 10),
(589, 'a:3:{i:0;a:5:{s:11:\"hello_world\";i:1;s:9:\"boxCompte\";i:0;s:11:\"boxCommande\";i:0;s:10:\"boxFacture\";i:0;s:15:\"boxFactureFourn\";i:0;}i:1;a:4:{s:9:\"boxClient\";i:0;s:11:\"boxProspect\";i:0;s:15:\"boxFournisseurs\";i:0;s:11:\"boxProduits\";i:0;}i:2;a:1:{s:14:\"listActionTODO\";i:0;}}', NULL, 4),
(740, 'a:3:{i:0;a:3:{s:16:\"recherchePropale\";i:0;s:12:\"statProspect\";i:0;s:11:\"boxProspect\";i:0;}i:1;a:2:{s:18:\"listPropaleOuverte\";i:0;s:16:\"listSocToContact\";i:0;}i:2;a:2:{s:14:\"listActionTODO\";i:0;s:19:\"listPropaleAValider\";i:0;}}', NULL, 16),
(751, 'a:3:{i:0;a:2:{s:15:\"rechercheAction\";i:0;s:11:\"liensAction\";i:0;}i:1;a:2:{s:14:\"listActionDone\";i:0;s:14:\"listActionTODO\";i:0;}i:2;a:1:{s:9:\"boxAction\";i:0;}}', NULL, 19),
(771, 'a:3:{i:0;a:4:{s:18:\"listPropaleOuverte\";i:0;s:16:\"listPropaleFerme\";i:0;s:19:\"listPropaleAValider\";i:0;s:20:\"listPropaleBrouillon\";i:0;}i:1;a:4:{s:11:\"boxProduits\";i:0;s:12:\"statProspect\";i:0;s:16:\"listSocToContact\";i:0;s:18:\"statProduitService\";i:0;}i:2;a:2:{s:15:\"rechercheAction\";i:0;s:14:\"listActionTODO\";i:0;}}', NULL, 20),
(780, 'a:3:{i:0;a:2:{s:17:\"rechercheCommande\";i:0;s:21:\"listCommandeBrouillon\";i:0;}i:1;a:1:{s:20:\"listCommandeATraiter\";i:0;}i:2;a:2:{s:19:\"listCommandeEnCours\";i:0;s:19:\"listCommandeCloture\";i:0;}}', NULL, 21),
(792, 'a:3:{i:0;a:2:{s:19:\"rechercheExpedition\";i:0;s:22:\"listExpeditionAValider\";i:0;}i:1;a:1:{s:14:\"listExpedition\";i:0;}i:2;a:2:{s:19:\"listCommandeEnCours\";i:0;s:20:\"listCommandeATraiter\";i:0;}}', NULL, 22),
(803, 'a:3:{i:0;a:3:{s:16:\"rechercheContrat\";i:0;s:13:\"contratLegend\";i:0;s:20:\"listContratBrouillon\";i:0;}i:1;a:2:{s:25:\"listContratServiceInactif\";i:0;s:25:\"listContratServiceModifie\";i:0;}i:2;a:1:{s:11:\"listContrat\";i:0;}}', NULL, 23),
(806, 'a:2:{i:0;a:1:{s:16:\"boxsynopsisdemandeinterv\";i:0;}i:1;a:1:{s:14:\"boxFicheInterv\";i:0;}}', NULL, 24),
(811, 'a:3:{i:0;a:1:{s:22:\"listPrelevementFacture\";i:0;}i:1;a:1:{s:15:\"listPrelevement\";i:0;}i:2;a:1:{s:15:\"statPrelevement\";i:0;}}', NULL, 34),
(819, 'a:3:{i:0;a:1:{s:9:\"boxCompte\";i:0;}i:1;a:2:{s:25:\"listBordereauRemiseCheque\";i:0;s:18:\"statOfcSoldeBanque\";i:1;}i:2;a:1:{s:16:\"statRemiseCheque\";i:0;}}', NULL, 29),
(824, 'a:3:{i:0;a:1:{s:13:\"statAVentiler\";i:0;}i:1;a:1:{s:17:\"statComptaVentile\";i:0;}i:2;a:1:{s:22:\"statComptaVentileFourn\";i:0;}}', NULL, 33),
(828, 'a:3:{i:0;a:2:{s:16:\"rechercheFacture\";i:0;s:16:\"listChargeAPayer\";i:0;}i:1;a:2:{s:20:\"listFactureBrouillon\";i:0;s:25:\"listFactureFournBrouillon\";i:0;}i:2;a:5:{s:10:\"listClient\";i:0;s:9:\"listFourn\";i:0;s:21:\"listCommandeAFacturer\";i:0;s:18:\"listFactureImpayee\";i:0;s:23:\"listFactureFournImpayee\";i:0;}}', NULL, 26),
(829, 'a:3:{i:0;a:2:{s:16:\"rechercheFacture\";i:0;s:16:\"listChargeAPayer\";i:0;}i:1;a:2:{s:20:\"listFactureBrouillon\";i:0;s:25:\"listFactureFournBrouillon\";i:0;}i:2;a:5:{s:10:\"listClient\";i:0;s:9:\"listFourn\";i:0;s:21:\"listCommandeAFacturer\";i:0;s:18:\"listFactureImpayee\";i:0;s:23:\"listFactureFournImpayee\";i:0;}}', NULL, 27),
(835, 'a:3:{i:0;a:1:{s:16:\"rechercheFacture\";i:0;}i:1;a:2:{s:25:\"listFactureFournBrouillon\";i:0;s:23:\"listFactureFournImpayee\";i:0;}i:2;a:2:{s:9:\"listFourn\";i:0;s:21:\"listCommandeAFacturer\";i:0;}}', NULL, 26),
(840, 'a:3:{i:0;a:2:{s:16:\"rechercheFacture\";i:0;s:10:\"listClient\";i:0;}i:1;a:1:{s:20:\"listFactureBrouillon\";i:0;}i:2;a:2:{s:21:\"listCommandeAFacturer\";i:0;s:18:\"listFactureImpayee\";i:0;}}', NULL, 27),
(850, 'a:2:{i:0;a:1:{s:16:\"boxsynopsisdemandeinterv\";i:0;}i:1;a:1:{s:14:\"boxFicheInterv\";i:0;}}', NULL, 40),
(853, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:1;s:9:\"boxAction\";i:0;}i:1;a:1:{s:14:\"boxDeplacement\";i:0;}i:2;a:2:{s:16:\"boxsynopsisdemandeinterv\";i:0;s:14:\"boxFicheInterv\";i:0;}}', NULL, 8),
(871, 'a:3:{i:0;a:1:{s:26:\"statProjectTaskPerDuration\";i:0;}i:1;a:1:{s:18:\"statProjectWarning\";i:0;}i:2;a:3:{s:18:\"statProjectPerTask\";i:0;s:11:\"statProject\";i:0;s:17:\"statProjectPerSoc\";i:0;}}', NULL, 36),
(880, 'a:3:{i:0;a:2:{s:11:\"statProject\";i:0;s:18:\"statProjectWarning\";i:0;}i:1;a:1:{s:18:\"statProjectPerTask\";i:0;}i:2;a:1:{s:26:\"statProjectTaskPerDuration\";i:0;}}', NULL, 37),
(913, 'a:3:{i:0;a:3:{s:11:\"statProject\";i:0;s:26:\"statOfcProjetActivitePrevu\";i:0;s:29:\"statOfcProjetMonActivitePrevu\";i:0;}i:1;a:3:{s:18:\"listProjetActivite\";i:0;s:21:\"listProjetMonActivite\";i:0;s:27:\"listProjetMonActiviteDetail\";i:0;}i:2;a:3:{s:26:\"statProjectTaskPerDuration\";i:0;s:30:\"statOfcProjetActiviteEffective\";i:1;s:33:\"statOfcProjetMonActiviteEffective\";i:0;}}', NULL, 38),
(919, 'a:3:{i:0;a:3:{s:11:\"hello_world\";i:0;s:11:\"boxProspect\";i:0;s:22:\"treeCategoriesSocietes\";i:0;}i:1;a:1:{s:9:\"boxClient\";i:0;}i:2;a:1:{s:15:\"boxFournisseurs\";i:0;}}', NULL, 9),
(936, 'a:3:{i:0;a:3:{s:16:\"rechercheFacture\";i:0;s:16:\"rechercheContrat\";i:0;s:15:\"listFactureCess\";i:0;}i:1;a:1:{s:13:\"listContratGA\";i:0;}i:2;a:2:{s:10:\"listClient\";i:0;s:8:\"listCess\";i:0;}}', NULL, 28),
(937, 'a:3:{i:0;a:1:{s:11:\"listProduit\";i:0;}i:1;a:1:{s:14:\"rechercheStock\";i:0;}i:2;a:1:{s:9:\"listStock\";i:0;}}', NULL, 12),
(938, 'a:3:{i:0;a:1:{s:22:\"treeCategoriesProduits\";i:0;}i:1;a:1:{s:18:\"statProduitService\";i:1;}i:2;a:1:{s:11:\"boxProduits\";i:1;}}', NULL, 13),
(939, 'a:3:{i:0;a:4:{s:11:\"listProduit\";i:1;s:11:\"statProduit\";i:1;s:16:\"rechercheProduit\";i:1;s:14:\"statOfcProduit\";i:1;}i:1;a:3:{s:23:\"listFactureFournImpayee\";i:1;s:25:\"listFactureFournBrouillon\";i:1;s:18:\"boxFactureFournImp\";i:1;}i:2;a:1:{s:26:\"listUserCanApproveComFourn\";i:1;}}', NULL, 14),
(940, 'a:3:{i:0;a:2:{s:26:\"listCommandeFournBrouillon\";i:0;s:14:\"statOfcProduit\";i:1;}i:1;a:2:{s:17:\"statCommandeFourn\";i:0;s:26:\"listUserCanApproveComFourn\";i:0;}i:2;a:2:{s:14:\"categorieFourn\";i:0;s:18:\"boxFactureFournImp\";i:0;}}', NULL, 15),
(956, 'a:3:{i:0;a:2:{s:17:\"statCommandeFourn\";i:0;s:14:\"categorieFourn\";i:0;}i:1;a:1:{s:26:\"listCommandeFournBrouillon\";i:0;}i:2;a:2:{s:9:\"listFourn\";i:0;s:25:\"listFactureFournBrouillon\";i:0;}}', NULL, 7),
(977, 'a:3:{i:0;a:6:{s:24:\"listContratSAVParDateFin\";i:0;s:9:\"liensGMAO\";i:1;s:8:\"creerSAV\";i:1;s:20:\"listSAVAttenteClient\";i:1;s:24:\"listSAVAttenteTraitement\";i:1;s:22:\"listExpeditionAValider\";i:1;}i:1;a:3:{s:21:\"statOfcProduitContrat\";i:1;s:15:\"statOfcProduits\";i:1;s:11:\"statProduit\";i:1;}i:2;a:4:{s:10:\"listRetard\";i:1;s:16:\"boxsynopsisdemandeinterv\";i:1;s:14:\"boxFicheInterv\";i:1;s:17:\"listTicketAttente\";i:1;}}', NULL, 43),
(1022, 'a:3:{i:0;a:2:{s:17:\"rechercheCommande\";i:0;s:22:\"listPrepaGroupCommande\";i:0;}i:1;a:1:{s:11:\"boxCommande\";i:0;}i:2;a:3:{s:21:\"listPrepaCommandeProb\";i:0;s:24:\"listPrepaCommandeEnCours\";i:0;s:17:\"listPrepaCommande\";i:0;}}', NULL, 44),
(1024, 'a:1:{i:0;a:1:{s:11:\"listAffaire\";i:0;}}', NULL, 41),
(1106, 'a:3:{i:0;a:5:{s:11:\"hello_world\";i:1;s:9:\"boxCompte\";i:0;s:11:\"boxCommande\";i:0;s:10:\"boxFacture\";i:0;s:15:\"boxFactureFourn\";i:0;}i:1;a:3:{s:9:\"boxClient\";i:0;s:11:\"boxProspect\";i:0;s:15:\"boxFournisseurs\";i:0;}i:2;a:2:{s:14:\"listActionTODO\";i:0;s:11:\"boxProduits\";i:0;}}', 10, 4),
(1109, 'a:3:{i:0;a:5:{s:11:\"hello_world\";i:1;s:9:\"boxCompte\";i:0;s:11:\"boxCommande\";i:0;s:10:\"boxFacture\";i:0;s:15:\"boxFactureFourn\";i:0;}i:1;a:3:{s:9:\"boxClient\";i:0;s:15:\"boxFournisseurs\";i:0;s:11:\"boxProduits\";i:0;}i:2;a:1:{s:14:\"listActionTODO\";i:0;}}', 8, 4),
(1121, 'a:3:{i:0;a:2:{s:16:\"rechercheProduit\";i:0;s:18:\"statProduitService\";i:0;}i:1;a:1:{s:22:\"treeCategoriesProduits\";i:0;}i:2;a:1:{s:11:\"boxProduits\";i:0;}}', 8, 6),
(1131, 'a:2:{i:0;a:3:{s:11:\"hello_world\";i:1;s:9:\"boxClient\";i:0;s:11:\"boxCommande\";i:0;}i:1;a:1:{s:14:\"listActionTODO\";i:0;}}', 18, 4),
(1142, 'a:2:{i:0;a:2:{s:11:\"hello_world\";i:0;s:14:\"boxFicheInterv\";i:0;}i:1;a:1:{s:16:\"boxsynopsisdemandeinterv\";i:0;}}', 5, 8),
(1149, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:0;s:9:\"boxAction\";i:0;}i:1;a:1:{s:14:\"boxDeplacement\";i:0;}i:2;a:2:{s:16:\"boxsynopsisdemandeinterv\";i:0;s:14:\"boxFicheInterv\";i:0;}}', 4, 8),
(1153, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:1;s:9:\"liensGMAO\";i:0;}i:1;a:4:{s:8:\"creerSAV\";i:0;s:24:\"listSAVAttenteTraitement\";i:1;s:24:\"listSAVAttenteReparation\";i:1;s:20:\"listSAVAttenteClient\";i:1;}i:2;a:1:{s:17:\"listTicketAttente\";i:0;}}', 8, 2),
(1160, 'a:3:{i:0;a:2:{s:16:\"boxsynopsisdemandeinterv\";i:0;s:14:\"listActionTODO\";i:1;}i:1;a:2:{s:14:\"boxFicheInterv\";i:0;s:14:\"boxDeplacement\";i:1;}i:2;a:1:{s:11:\"hello_world\";i:1;}}', 10, 8),
(1175, 'a:2:{i:0;a:1:{s:11:\"hello_world\";i:0;}i:1;a:1:{s:9:\"boxClient\";i:0;}}', 2, 9),
(1181, 'a:3:{i:0;a:1:{s:21:\"listPrepaCommandeProb\";i:0;}i:1;a:2:{s:22:\"listPrepaGroupCommande\";i:1;s:17:\"listPrepaCommande\";i:0;}i:2;a:1:{s:24:\"listPrepaCommandeEnCours\";i:1;}}', 7, 43),
(1189, 'a:2:{i:0;a:10:{s:11:\"hello_world\";i:1;s:11:\"boxCommande\";i:0;s:17:\"rechercheCommande\";i:2;s:16:\"rechercheContrat\";i:3;s:16:\"boxsynopsisdemandeinterv\";i:4;s:14:\"boxFicheInterv\";i:5;s:14:\"listContratSAV\";i:6;s:24:\"listContratSAVParDateFin\";i:7;s:9:\"liensGMAO\";i:8;s:17:\"listTicketAttente\";i:9;}i:1;a:1:{s:9:\"boxClient\";i:0;}}', 11, 4),
(1257, 'a:1:{i:0;a:1:{s:10:\"listClient\";i:0;}}', 7, 5),
(1261, 'a:1:{i:0;a:2:{s:11:\"hello_world\";i:0;s:9:\"boxClient\";i:0;}}', 7, 9),
(1276, 'a:3:{i:0;a:4:{s:11:\"hello_world\";i:1;s:9:\"boxCompte\";i:0;s:11:\"boxCommande\";i:0;s:10:\"boxFacture\";i:0;}i:1;a:4:{s:9:\"boxClient\";i:0;s:11:\"boxProspect\";i:0;s:15:\"boxFournisseurs\";i:0;s:11:\"boxProduits\";i:0;}i:2;a:1:{s:14:\"listActionTODO\";i:0;}}', 15, 4),
(1291, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:1;s:11:\"boxCommande\";i:0;}i:1;a:2:{s:9:\"boxClient\";i:0;s:11:\"boxProduits\";i:0;}i:2;a:1:{s:14:\"listActionTODO\";i:0;}}', 7, 4),
(1295, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:1;s:11:\"boxCommande\";i:0;}i:1;a:4:{s:9:\"boxClient\";i:0;s:11:\"boxProspect\";i:0;s:15:\"boxFournisseurs\";i:0;s:11:\"boxProduits\";i:0;}i:2;a:1:{s:14:\"listActionTODO\";i:0;}}', 13, 4),
(1299, 'a:3:{i:0;a:2:{s:17:\"listPrepaCommande\";i:0;s:19:\"listCommandeEnCours\";i:0;}i:1;a:4:{s:17:\"rechercheCommande\";i:0;s:19:\"rechercheExpedition\";i:0;s:16:\"rechercheContrat\";i:0;s:30:\"listPrepaCommandeContratAFaire\";i:0;}i:2;a:3:{s:10:\"listRetard\";i:0;s:20:\"listContratBrouillon\";i:0;s:14:\"boxFicheInterv\";i:0;}}', 5, 43),
(1307, 'a:2:{i:0;a:3:{s:17:\"rechercheCommande\";i:0;s:21:\"listCommandeBrouillon\";i:0;s:11:\"boxCommande\";i:0;}i:1;a:1:{s:19:\"listCommandeEnCours\";i:0;}}', 33, 43),
(1309, 'a:1:{i:0;a:2:{s:14:\"boxFicheInterv\";i:0;s:16:\"boxsynopsisdemandeinterv\";i:1;}}', 15, 8),
(1317, 'a:2:{i:0;a:4:{s:17:\"listTicketAttente\";i:0;s:24:\"listSAVAttenteTraitement\";i:0;s:8:\"creerSAV\";i:0;s:24:\"listSAVAttenteReparation\";i:0;}i:1;a:1:{s:20:\"listSAVAttenteClient\";i:0;}}', 9, 2),
(1332, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:1;s:9:\"boxAction\";i:0;}i:1;a:1:{s:14:\"boxDeplacement\";i:0;}i:2;a:2:{s:16:\"boxsynopsisdemandeinterv\";i:0;s:14:\"boxFicheInterv\";i:0;}}', 8, 8),
(1431, 'a:3:{i:0;a:1:{s:16:\"boxsynopsisdemandeinterv\";i:0;}i:1;a:1:{s:14:\"boxFicheInterv\";i:0;}i:2;a:1:{s:11:\"hello_world\";i:0;}}', 14, 4),
(1440, 'a:3:{i:0;a:5:{s:11:\"hello_world\";i:1;s:9:\"boxCompte\";i:0;s:11:\"boxCommande\";i:0;s:10:\"boxFacture\";i:0;s:15:\"boxFactureFourn\";i:0;}i:1;a:4:{s:9:\"boxClient\";i:0;s:11:\"boxProspect\";i:0;s:15:\"boxFournisseurs\";i:0;s:11:\"boxProduits\";i:0;}i:2;a:1:{s:14:\"listActionTODO\";i:0;}}', 35, 4),
(1445, 'a:2:{i:0;a:1:{s:17:\"boxServicesVendus\";i:0;}i:1;a:2:{s:13:\"contratLegend\";i:0;s:16:\"rechercheContrat\";i:0;}}', 5, 23),
(1447, 'a:3:{i:0;a:2:{s:16:\"rechercheContrat\";i:0;s:20:\"listContratBrouillon\";i:0;}i:1;a:2:{s:25:\"listContratServiceInactif\";i:0;s:25:\"listContratServiceModifie\";i:0;}i:2;a:1:{s:11:\"listContrat\";i:0;}}', 2, 23),
(1456, 'a:2:{i:0;a:1:{s:10:\"listClient\";i:0;}i:1;a:2:{s:9:\"listFourn\";i:0;s:21:\"listCommandeAFacturer\";i:0;}}', 2, 5),
(1500, 'a:3:{i:0;a:2:{s:16:\"rechercheFacture\";i:0;s:16:\"listChargeAPayer\";i:0;}i:1;a:2:{s:20:\"listFactureBrouillon\";i:0;s:25:\"listFactureFournBrouillon\";i:0;}i:2;a:4:{s:10:\"listClient\";i:0;s:9:\"listFourn\";i:0;s:21:\"listCommandeAFacturer\";i:0;s:23:\"listFactureFournImpayee\";i:0;}}', 39, 5),
(1518, 'a:2:{i:0;a:4:{s:11:\"hello_world\";i:1;s:11:\"boxCommande\";i:1;s:9:\"boxClient\";i:1;s:22:\"listExpeditionAValider\";i:0;}i:1;a:1:{s:17:\"rechercheCommande\";i:0;}}', 34, 4),
(1526, 'a:3:{i:0;a:6:{s:24:\"listContratSAVParDateFin\";i:0;s:9:\"liensGMAO\";i:1;s:8:\"creerSAV\";i:1;s:20:\"listSAVAttenteClient\";i:1;s:24:\"listSAVAttenteTraitement\";i:0;s:22:\"listExpeditionAValider\";i:0;}i:1;a:3:{s:21:\"statOfcProduitContrat\";i:1;s:15:\"statOfcProduits\";i:1;s:11:\"statProduit\";i:1;}i:2;a:4:{s:10:\"listRetard\";i:1;s:16:\"boxsynopsisdemandeinterv\";i:0;s:14:\"boxFicheInterv\";i:0;s:17:\"listTicketAttente\";i:1;}}', 28, 43),
(1538, 'a:2:{i:0;a:1:{s:17:\"rechercheCommande\";i:0;}i:1;a:1:{s:22:\"listExpeditionAValider\";i:0;}}', 34, 43),
(1552, 'a:3:{i:0;a:2:{s:16:\"rechercheProduit\";i:0;s:18:\"statProduitService\";i:0;}i:1;a:1:{s:22:\"treeCategoriesProduits\";i:0;}i:2;a:1:{s:11:\"boxProduits\";i:0;}}', 2, 6),
(1553, 'a:1:{i:0;a:2:{s:16:\"rechercheProduit\";i:0;s:18:\"statProduitService\";i:1;}}', 5, 10),
(1567, 'a:3:{i:0;a:2:{s:18:\"listProduitService\";i:0;s:16:\"rechercheProduit\";i:0;}i:1;a:1:{s:11:\"boxProduits\";i:0;}i:2;a:1:{s:18:\"statProduitService\";i:0;}}', 5, 6),
(1581, 'a:3:{i:0;a:3:{s:17:\"rechercheCommande\";i:0;s:16:\"rechercheProduit\";i:0;s:18:\"statProduitService\";i:0;}i:1;a:2:{s:16:\"rechercheContrat\";i:0;s:11:\"hello_world\";i:1;}i:2;a:2:{s:22:\"listExpeditionAValider\";i:0;s:20:\"listCommandeATraiter\";i:0;}}', 5, 4),
(1582, 'a:1:{i:0;a:1:{s:22:\"treeCategoriesProduits\";i:0;}}', 5, 11),
(1585, 'a:3:{i:0;a:4:{s:17:\"listPrepaCommande\";i:0;s:21:\"listPrepaCommandeProb\";i:0;s:30:\"listPrepaCommandeContratAFaire\";i:0;s:43:\"listExpeditionPrepaCommandeAvecFiltreNotify\";i:0;}i:1;a:6:{s:20:\"listCommandeATraiter\";i:0;s:21:\"listCommandeAFacturer\";i:0;s:19:\"listCommandeCloture\";i:1;s:19:\"listCommandeEnCours\";i:0;s:17:\"rechercheCommande\";i:0;s:11:\"boxCommande\";i:0;}i:2;a:4:{s:22:\"listPrepaGroupCommande\";i:0;s:24:\"listPrepaCommandeEnCours\";i:0;s:27:\"listExpeditionPrepaCommande\";i:0;s:21:\"listCommandeBrouillon\";i:1;}}', 2, 43),
(1588, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:1;s:9:\"liensGMAO\";i:0;}i:1;a:4:{s:8:\"creerSAV\";i:0;s:24:\"listSAVAttenteTraitement\";i:0;s:24:\"listSAVAttenteReparation\";i:0;s:20:\"listSAVAttenteClient\";i:0;}i:2;a:1:{s:17:\"listTicketAttente\";i:0;}}', 2, 2),
(1596, 'a:3:{i:0;a:4:{s:11:\"statProduit\";i:0;s:16:\"rechercheProduit\";i:0;s:18:\"statProduitService\";i:0;s:11:\"statService\";i:0;}i:1;a:1:{s:11:\"listProduit\";i:0;}i:2;a:1:{s:22:\"treeCategoriesProduits\";i:0;}}', 2, 10),
(1597, 'a:1:{i:1;a:1:{s:17:\"rechercheCommande\";i:0;}}', 39, 4),
(1602, 'a:2:{i:0;a:5:{s:16:\"rechercheContrat\";i:0;s:10:\"listRetard\";i:0;s:16:\"boxsynopsisdemandeinterv\";i:0;s:21:\"listCommandeBrouillon\";i:0;s:17:\"boxServicesVendus\";i:0;}i:1;a:2:{s:16:\"rechercheProduit\";i:0;s:11:\"boxCommande\";i:0;}}', 5, 3),
(1619, 'a:3:{i:0;a:2:{s:11:\"hello_world\";i:0;s:9:\"boxAction\";i:0;}i:1;a:1:{s:14:\"boxDeplacement\";i:0;}i:2;a:2:{s:16:\"boxsynopsisdemandeinterv\";i:0;s:14:\"boxFicheInterv\";i:0;}}', 2, 8),
(1628, 'a:1:{i:0;a:6:{s:18:\"boxDernierePropale\";i:0;s:20:\"listPropaleBrouillon\";i:1;s:19:\"listPropaleAValider\";i:2;s:16:\"listPropaleFerme\";i:3;s:18:\"listPropaleOuverte\";i:4;s:16:\"recherchePropale\";i:5;}}', 2, 3),
(1630, 'a:2:{i:1;a:3:{s:16:\"rechercheProduit\";i:0;s:22:\"treeCategoriesProduits\";i:0;s:18:\"statProduitService\";i:0;}i:2;a:1:{s:11:\"boxProduits\";i:0;}}', 48, 6),
(1633, 'a:3:{i:0;a:5:{s:9:\"liensGMAO\";i:1;s:8:\"creerSAV\";i:1;s:20:\"listSAVAttenteClient\";i:1;s:24:\"listSAVAttenteTraitement\";i:1;s:22:\"listExpeditionAValider\";i:1;}i:1;a:3:{s:21:\"statOfcProduitContrat\";i:1;s:15:\"statOfcProduits\";i:1;s:11:\"statProduit\";i:1;}i:2;a:4:{s:10:\"listRetard\";i:1;s:16:\"boxsynopsisdemandeinterv\";i:1;s:14:\"boxFicheInterv\";i:1;s:17:\"listTicketAttente\";i:1;}}', 39, 43),
(1638, 'a:3:{i:0;a:3:{s:14:\"listExpedition\";i:0;s:22:\"listExpeditionAValider\";i:0;s:10:\"listClient\";i:0;}i:1;a:2:{s:43:\"listExpeditionPrepaCommandeAvecFiltreNotify\";i:0;s:21:\"listPrepaCommandeProb\";i:0;}i:2;a:3:{s:14:\"boxFicheInterv\";i:0;s:16:\"listContratMixte\";i:0;s:18:\"statProduitService\";i:0;}}', 2, 4),
(1640, 'a:3:{i:0;a:3:{s:11:\"hello_world\";i:1;s:9:\"boxAction\";i:0;s:16:\"boxsynopsisdemandeinterv\";i:0;}i:1;a:1:{s:14:\"boxDeplacement\";i:0;}i:2;a:1:{s:14:\"boxFicheInterv\";i:0;}}', 35, 8),
(1645, 'a:3:{i:0;a:6:{s:11:\"hello_world\";i:0;s:9:\"boxCompte\";i:1;s:11:\"boxCommande\";i:1;s:10:\"boxFacture\";i:0;s:15:\"boxFactureFourn\";i:0;s:16:\"boxsynopsisdemandeinterv\";i:0;}i:1;a:4:{s:9:\"boxClient\";i:1;s:11:\"boxProspect\";i:0;s:15:\"boxFournisseurs\";i:0;s:11:\"boxProduits\";i:0;}i:2;a:1:{s:14:\"listActionTODO\";i:1;}}', 9, 4);
",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_Dashboard_module` (`id`, `module_refid`, `type_refid`) VALUES
(85, 4, 1),
(86, 8, 1),
(87, 9, 1),
(88, 10, 1),
(89, 12, 1),
(90, 13, 1),
(91, 51, 1),
(92, 4, 2),
(93, 8, 2),
(94, 9, 2),
(95, 10, 2),
(96, 12, 2),
(97, 13, 2),
(98, 51, 2),
(117, 19, 3),
(118, 18, 3),
(119, 63, 3),
(120, 17, 3),
(122, 16, 3),
(125, 69, 3),
(126, 38, 3),
(127, 15, 3),
(128, 21, 3),
(129, 50, 3),
(130, 22, 3),
(131, 23, 3),
(132, 14, 3),
(133, 20, 3),
(134, 51, 3),
(135, 63, 7),
(136, 32, 7),
(137, 26, 7),
(138, 33, 7),
(139, 34, 7),
(140, 49, 7),
(141, 29, 7),
(142, 36, 7),
(143, 35, 7),
(144, 62, 6),
(145, 38, 6),
(146, 37, 6),
(147, 36, 6),
(148, 28, 9),
(149, 20, 9),
(151, 29, 9),
(152, 65, 9),
(153, 61, 9),
(154, 59, 9),
(155, 51, 9),
(156, 68, 8),
(157, 63, 8),
(158, 69, 8),
(159, 51, 8),
(160, 18, 8),
(161, 19, 8),
(163, 66, 3),
(164, 58, 3),
(165, 68, 3),
(166, 62, 3),
(167, 36, 3),
(168, 37, 3),
(169, 65, 3),
(170, 61, 3),
(171, 59, 3),
(172, 28, 3),
(173, 24, 5),
(174, 25, 5),
(175, 26, 5),
(176, 27, 5),
(177, 28, 5),
(178, 29, 5),
(179, 30, 5),
(180, 31, 5),
(181, 32, 5),
(183, 16, 5),
(184, 36, 5),
(185, 22, 5),
(186, 14, 5),
(187, 51, 5),
(188, 63, 4),
(189, 67, 4),
(190, 66, 4),
(191, 58, 4),
(192, 16, 4),
(193, 68, 4),
(195, 70, 4),
(196, 60, 4),
(197, 72, 4),
(198, 24, 4),
(199, 34, 4),
(200, 12, 4),
(201, 13, 4),
(202, 69, 4),
(203, 62, 4),
(204, 37, 4),
(205, 15, 4),
(206, 23, 4),
(207, 21, 4),
(208, 50, 4),
(209, 36, 4),
(211, 10, 4),
(212, 9, 4),
(213, 4, 4),
(214, 28, 4),
(215, 20, 4),
(216, 29, 4),
(217, 65, 4),
(218, 61, 4),
(219, 59, 4),
(220, 51, 4),
(222, 38, 4),
(223, 25, 4),
(224, 14, 4),
(225, 22, 4),
(226, 33, 4),
(227, 49, 4),
(228, 26, 4),
(229, 32, 4),
(230, 31, 4),
(231, 35, 4),
(232, 30, 4),
(233, 19, 4),
(234, 18, 4),
(235, 18, 4),
(236, 27, 4),
(237, 27, 4),
(238, 27, 4),
(239, 17, 4),
(240, 17, 4),
(241, 17, 4),
(242, 8, 4),
(243, 8, 4),
(244, 8, 4),
(245, 73, 6),
(247, 73, 4),
(248, 74, 4),
(249, 73, 13),
(250, 82, 6),
(251, 83, 4),
(252, 82, 4),
(253, 83, 6),
(254, 73, 10),
(255, 38, 10),
(256, 82, 10),
(257, 62, 10),
(258, 36, 10),
(259, 37, 10),
(261, 83, 10),
(262, 38, 11),
(263, 82, 11),
(264, 62, 11),
(265, 36, 11),
(266, 37, 11),
(267, 83, 11),
(268, 73, 11),
(269, 38, 13),
(270, 82, 13),
(271, 62, 13),
(272, 36, 13),
(273, 37, 13),
(274, 83, 13),
(275, 86, 6),
(276, 85, 6),
(277, 85, 10),
(278, 86, 10),
(279, 85, 11),
(280, 86, 11),
(281, 87, 6),
(282, 87, 10),
(283, 87, 11),
(284, 88, 4),
(285, 87, 4),
(286, 92, 4),
(287, 85, 4),
(288, 86, 4),
(289, 93, 4),
(290, 94, 4),
(291, 95, 4),
(292, 97, 4),
(293, 98, 4),
(295, 99, 4),
(296, 100, 4),
(297, 101, 4),
(298, 104, 4),
(299, 105, 4),
(300, 106, 4),
(301, 107, 4),
(302, 108, 4),
(303, 109, 4),
(304, 110, 4),
(305, 112, 4),
(306, 111, 4),
(307, 113, 4),
(308, 114, 4),
(309, 116, 4),
(310, 119, 4),
(311, 118, 4),
(312, 120, 4),
(314, 121, 4),
(315, 122, 4),
(316, 131, 4),
(317, 134, 4),
(318, 135, 4),
(320, 137, 41),
(321, 139, 4),
(322, 140, 4),
(323, 138, 4),
(324, 137, 4),
(326, 139, 6),
(327, 140, 6),
(328, 138, 6),
(329, 138, 6),
(330, 139, 10),
(331, 138, 10),
(332, 140, 10),
(333, 139, 11),
(334, 138, 11),
(335, 140, 11),
(336, 38, 12),
(337, 142, 12),
(338, 141, 12),
(339, 35, 14),
(340, 74, 14),
(341, 32, 14),
(342, 26, 14),
(343, 34, 14),
(344, 33, 14),
(345, 49, 14),
(346, 38, 14),
(347, 85, 14),
(348, 36, 14),
(349, 138, 14),
(350, 35, 15),
(351, 74, 15),
(352, 33, 15),
(353, 49, 15),
(354, 34, 15),
(355, 138, 15),
(356, 36, 15),
(357, 38, 15),
(358, 14, 16),
(359, 50, 16),
(360, 22, 16),
(361, 21, 16),
(362, 15, 16),
(363, 23, 16),
(364, 97, 16),
(365, 95, 16),
(366, 18, 16),
(367, 19, 16),
(368, 19, 16),
(369, 59, 16),
(370, 63, 16),
(371, 18, 19),
(372, 19, 19),
(376, 63, 19),
(377, 143, 4),
(378, 144, 4),
(379, 144, 4),
(380, 144, 4),
(381, 144, 4),
(382, 142, 4),
(383, 141, 4),
(384, 143, 19),
(385, 144, 19),
(386, 15, 20),
(387, 23, 20),
(388, 22, 20),
(389, 21, 20),
(390, 50, 20),
(391, 14, 20),
(392, 97, 20),
(393, 95, 20),
(394, 38, 20),
(395, 85, 20),
(396, 82, 20),
(397, 62, 20),
(398, 36, 20),
(399, 37, 20),
(401, 18, 20),
(402, 19, 20),
(403, 63, 20),
(404, 143, 20),
(405, 17, 21),
(406, 30, 21),
(407, 99, 21),
(408, 100, 21),
(409, 101, 21),
(410, 66, 21),
(412, 145, 21),
(413, 99, 22),
(414, 105, 22),
(415, 104, 22),
(416, 101, 22),
(417, 146, 4),
(418, 145, 4),
(419, 146, 22),
(420, 107, 23),
(421, 16, 23),
(422, 106, 23),
(423, 108, 23),
(424, 110, 23),
(425, 109, 23),
(426, 58, 23),
(427, 68, 24),
(428, 147, 4),
(429, 147, 24),
(430, 67, 35),
(531, 24, 26),
(532, 25, 26),
(533, 26, 26),
(534, 27, 26),
(535, 28, 26),
(536, 29, 26),
(537, 30, 26),
(538, 31, 26),
(539, 32, 26),
(541, 16, 26),
(542, 36, 26),
(543, 22, 26),
(544, 14, 26),
(545, 51, 26),
(546, 24, 27),
(547, 25, 27),
(548, 26, 27),
(549, 27, 27),
(550, 28, 27),
(551, 29, 27),
(552, 30, 27),
(553, 31, 27),
(554, 32, 27),
(556, 16, 27),
(557, 36, 27),
(558, 22, 27),
(559, 14, 27),
(560, 51, 27),
(561, 24, 28),
(562, 25, 28),
(563, 26, 28),
(564, 27, 28),
(565, 28, 28),
(566, 29, 28),
(567, 30, 28),
(568, 31, 28),
(569, 32, 28),
(571, 16, 28),
(572, 36, 28),
(573, 22, 28),
(574, 14, 28),
(575, 51, 28),
(576, 120, 34),
(577, 119, 34),
(580, 118, 34),
(581, 111, 29),
(582, 112, 29),
(584, 67, 29),
(585, 94, 29),
(586, 113, 33),
(587, 114, 33),
(588, 116, 33),
(589, 68, 40),
(590, 147, 40),
(591, 69, 40),
(592, 147, 8),
(593, 148, 36),
(594, 121, 36),
(595, 122, 36),
(596, 135, 36),
(597, 134, 36),
(598, 131, 36),
(600, 149, 36),
(601, 121, 37),
(602, 122, 37),
(603, 135, 37),
(604, 134, 37),
(605, 131, 37),
(606, 149, 4),
(607, 148, 4),
(608, 131, 38),
(609, 149, 38),
(610, 148, 38),
(611, 121, 38),
(612, 122, 38),
(613, 135, 38),
(614, 134, 38),
(615, 150, 4),
(616, 150, 36),
(617, 150, 38),
(618, 150, 37),
(619, 151, 4),
(620, 151, 36),
(621, 151, 38),
(622, 151, 37),
(623, 152, 4),
(624, 152, 36),
(625, 152, 38),
(626, 152, 37),
(627, 156, 4),
(628, 155, 4),
(629, 154, 4),
(630, 153, 4),
(631, 156, 36),
(632, 155, 36),
(633, 154, 36),
(634, 153, 36),
(635, 154, 38),
(636, 153, 38),
(637, 156, 37),
(638, 155, 37),
(639, 154, 37),
(640, 92, 37),
(641, 156, 38),
(642, 155, 38),
(643, 158, 4),
(644, 158, 7),
(645, 158, 41),
(646, 158, 5),
(647, 158, 3),
(648, 159, 4),
(649, 159, 9),
(650, 160, 4),
(651, 160, 9),
(652, 160, 7),
(653, 160, 5),
(654, 159, 5),
(655, 160, 26),
(656, 159, 27),
(657, 161, 4),
(658, 161, 28),
(659, 163, 4),
(660, 163, 23),
(661, 163, 5),
(662, 161, 5),
(663, 163, 28),
(664, 164, 4),
(665, 164, 5),
(666, 164, 28),
(667, 165, 4),
(668, 165, 2),
(669, 165, 1),
(672, 166, 4),
(673, 166, 2),
(674, 166, 1),
(675, 166, 23),
(676, 165, 23),
(677, 73, 43),
(678, 159, 43),
(679, 17, 43),
(680, 30, 43),
(681, 99, 43),
(682, 100, 43),
(683, 101, 43),
(684, 66, 43),
(685, 145, 43),
(686, 106, 43),
(687, 108, 43),
(688, 109, 43),
(689, 110, 43),
(690, 58, 43),
(691, 107, 43),
(692, 16, 43),
(693, 68, 43),
(694, 146, 43),
(695, 105, 43),
(696, 104, 43),
(697, 147, 43),
(698, 165, 43),
(699, 166, 43),
(700, 12, 43),
(701, 13, 43),
(702, 38, 43),
(703, 85, 43),
(704, 82, 43),
(705, 62, 43),
(706, 36, 43),
(707, 37, 43),
(708, 4, 43),
(709, 158, 43),
(710, 10, 43),
(711, 9, 43),
(712, 8, 43),
(713, 139, 43),
(714, 138, 43),
(715, 87, 43),
(716, 140, 43),
(717, 140, 43),
(719, 51, 43),
(720, 167, 4),
(721, 167, 43),
(722, 168, 4),
(723, 169, 4),
(724, 169, 43),
(725, 168, 43),
(726, 170, 4),
(727, 170, 43),
(728, 170, 21),
(729, 169, 44),
(730, 168, 44),
(731, 167, 44),
(732, 170, 44),
(733, 17, 44),
(734, 30, 44),
(735, 99, 44),
(736, 100, 44),
(737, 101, 44),
(738, 66, 44),
(739, 145, 44),
(740, 172, 4),
(741, 171, 4),
(742, 174, 4),
(743, 173, 4),
(744, 174, 43),
(745, 173, 43),
(746, 176, 4),
(747, 176, 43),
(748, 169, 23),
(749, 168, 23),
(750, 167, 23),
(751, 170, 23),
(752, 174, 23),
(753, 173, 23),
(754, 176, 23),
(755, 177, 4);");

//		$result=$this->load_tables();

        return $this->_init($sql);
    }

    /**
     * 		Function called when module is disabled.
     *      Remove from database constants, boxes and permissions from Dolibarr database.
     * 		Data directories are not deleted.
     *      @return     int             1 if OK, 0 if KO
     */
    function remove() {
        $sql = array();

        return $this->_remove($sql);
    }

    /**
     * 		\brief		Create tables, keys and data required by module
     * 					Files ".MAIN_DB_PREFIX."table1.sql, ".MAIN_DB_PREFIX."table1.key.sql ".MAIN_DB_PREFIX."data.sql with create table, create keys
     * 					and create data commands must be stored in directory /mymodule/sql/
     * 					This function is called by this->init.
     * 		\return		int		<=0 if KO, >0 if OK
     */
//	function load_tables()
//	{
//		return $this->_load_tables('/synopsistools/dasboard2/sql/');
//	}
}

?>

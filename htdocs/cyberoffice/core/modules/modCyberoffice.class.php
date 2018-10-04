<?php
/**
 *	CyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2014 LVSInformatique
 *	@license   NoLicence
 *  @version   1.3.0
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

class modcyberoffice extends DolibarrModules
{

   /**
    *   \brief      Constructor. Define names, constants, directories, boxes, permissions
    *   \param      DB      Database handler
    */
	function modcyberoffice ($DB)
	{
		global $langs;
		$this->db = $DB;
		$langs->load("cyberoffice@cyberoffice");
		// Id for module (must be unique).
		// Use here a free id.
		$this->numero = 17100;
		$this->rights_class = 'cyberoffice';	// Permission key
		// Family can be 'crm','financial','hr','projects','product','technic','other'
		// It is used to sort modules in module setup page
		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = $langs->trans("Synchronization from Prestashop to Dolibarr ERP/CRM");
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.3.2';//'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (XXX is id value)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 2;
		// Name of png file (without png) used for this module
		$this->picto='cyberoffice@cyberoffice';
		// Call to inside lang's file
		$this->langfiles = array("cyberoffice@cyberoffice");
		$this->module_parts = array();
		$this->triggers = 0;
		//$this->style_sheet= '/ecmTiers/ecmTiers.css'; 
		// Data directories to create when module is enabled
		$this->dirs = array();
		$r=0;
		// Config pages. Put here list of php page names stored in admmin directory used to setup module
		$this->config_page_url = array("cyberoffice_setupapage.php@cyberoffice");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,0);
		$this->need_dolibarr_version = array(3,5);
		/*
		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		//                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',0)
		$this->const = array(
			0=>array('CYBEROFFICE_SHIPPING1','chaine','myvalue','service\'s rowid',1),
		    1=>array('CYBEROFFICE_DISCOUNT1','chaine','myvalue','service\'s rowid',1) 
		    );
		   */
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		
		// Array to add new pages in new tabs
		//$this->tabs = array('thirdparty:+tabname1:Title1:@mymodule:/mymodule/mynewtab1.php?id=__ID__');
		$this->tabs = '';
		// where entity can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'supplier_order'   to add a tab in supplier order view
		// 'supplier_invoice' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
		// Example:
        //$this->boxes[$r][1] = "myboxa.php";
    	//$r++;
        //$this->boxes[$r][1] = "myboxb.php";
    	//$r++;

		// Permissions
		
		$this->rights = array();		// Permission array used by this module
	/*	
		$r++;
		$this->rights[$r][0] = 170060;
		$this->rights[$r][1] = 'Consulter les documents';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'download';

		$r++;
		$this->rights[$r][0] = 170061;
		$this->rights[$r][1] = 'Soumettre ou supprimer des documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'upload';

		$r++;
		$this->rights[$r][0] = 170062;
		$this->rights[$r][1] = 'Administrer les rubriques de documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'setup';
*/
        // Menus
		//------
		$this->menu = array();
		$r=0;
	}

	/**
     *		\brief      Function called when module is enabled.
     *					The init function add previous constants, boxes and permissions into Dolibarr database.
     *					It also creates data directories.
     */
	function init($options='')
  	{	
  		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
  		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
  		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
  		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
  		global $db,$conf,$langs,$user,$mysoc;
  		//longdescript, longdescript, text, , 2000, product, 0, 0,, Array
  		/* ====================== extrafiels=======================*/
  		$myextra = new ExtraFields($db);
  		$myextra->addExtraField('longdescript', 'longdescript', 'text',0, 2000, 'product');
  		//$myextra2 = new ExtraFields($db);
  		//$myextra2->addExtraField('cyberprice', 'cyberprice', 'double',0, '24,8', 'product');
  		$form = new Form($db);
  		$num = $form->load_cache_vatrates("'".$mysoc->country_code."'");
  		//print $num;die();
        if ($num > 0)
        {
        	// Definition du taux a pre-selectionner (si defaulttx non force et donc vaut -1 ou '')
        	if ($defaulttx < 0 || dol_strlen($defaulttx) == 0)
        	{
        		if (DOL_VERSION >= '4.0.0') {
					$tmpthirdparty=new Societe($db);
	        		$defaulttx=get_default_tva($mysoc,(is_object($societe_acheteuse)?$societe_acheteuse:$tmpthirdparty),$idprod);
	        		$defaultnpr=get_default_npr($mysoc,(is_object($societe_acheteuse)?$societe_acheteuse:$tmpthirdparty),$idprod);
	        		if (empty($defaulttx)) $defaultnpr=0;
				} else {
					$defaulttx=get_default_tva($mysoc,'',$idprod);
        			$defaultnpr=get_default_npr($mysoc,'',$idprod);
				}
        	}

        	// Si taux par defaut n'a pu etre determine, on prend dernier de la liste.
        	// Comme ils sont tries par ordre croissant, dernier = plus eleve = taux courant
        	if ($defaulttx < 0 || dol_strlen($defaulttx) == 0 || $defaulttx == 0)
        	{
        		if (empty($conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS)) $defaulttx = $form->cache_vatrates[$num-1]['txtva'];
        		else $defaulttx=$conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS;
        	}
		}
		//@chmod('../images_temp', octdec(0777));
		//print $defaulttx;die();
  		$produit = new Product($db);
  		$produit->price_base_type 	= 'HT';
		$produit->price				= 0;
		$produit->price_ttc 		= 0;
		$produit->tva_tx			= $defaulttx;
		if (DOL_VERSION < '3.8.0')
				$produit->libelle				= 'PrestaShipping';
		else
				$produit->label				= 'PrestaShipping';
		$produit->description		= 'PrestaShipping';
		$produit->type				= 1;
		$produit->status			= 1;
		$produit->status_buy		= 1;
		$produit->ref				= 'PrestaShipping';
		//barcode
		//empty($code) && $this->code_null
		dol_syslog("Cyberoffice::produit->createPrestaShipping");
  		$results = $produit->create($user);
  		if ($results > 0) 
  			dolibarr_set_const($db, 'CYBEROFFICE_SHIPPING', $results);
  		else 
  		{
  			if ($produit->fetch('','PrestaShipping') > 0)
  				dolibarr_set_const($db, 'CYBEROFFICE_SHIPPING', $produit->id);
  		}
  		dol_syslog("Cyberoffice::produit->createPrestaShipping ".$results );
		
		$produitD = new Product($db);
  		$produitD->price_base_type 	= 'HT';
		$produitD->price				= 0;
		$produitD->price_ttc 		= 0;
		$produitD->tva_tx			= $defaulttx;
		if (DOL_VERSION < '3.8.0')
				$produitD->libelle				= 'PrestaDiscount';
		else
				$produitD->label				= 'PrestaDiscount';
		$produitD->description		= 'PrestaDiscount';
		$produitD->type				= 1;
		$produitD->status			= 1;
		$produitD->status_buy		= 1;
		$produitD->ref				= 'PrestaDiscount';
		dol_syslog("Cyberoffice::produit->createPrestaDiscount ");
  		$resultd = $produitD->create($user);
  		if ($resultd > 0) 
  			dolibarr_set_const($db, 'CYBEROFFICE_DISCOUNT', $resultd);
  		else 
  		{
  			if ($produitD->fetch('','PrestaDiscount') > 0)
  				dolibarr_set_const($db, 'CYBEROFFICE_DISCOUNT', $produitD->id);
  		}
  		dol_syslog("Cyberoffice::produit->createPrestaDiscount ".$resultd );
  		//print $resultd;die();
		$this->const = array(
			0=>array('CYBEROFFICE_SHIPPING','chaine',$results,'service\'s rowid',1),
		    1=>array('CYBEROFFICE_DISCOUNT','chaine',$resultd,'service\'s rowid',1),
		    2=>array('CYBEROFFICE_invoice','chaine',1,'Invoice Synchronization',1),
		    3=>array('CYBEROFFICE_stock','chaine',1,'Stock Synchronization',1),
		    4=>array('CYBEROFFICE_chanel','chaine',5,'number of chanels',1) 
		    );

    	$sql = array();
		$sql=array(array('sql' => "ALTER TABLE ".MAIN_DB_PREFIX."c_paiement ADD COLUMN cyberbank int(11) DEFAULT NULL;", 'ignoreerror' => 1));
		$result=$this->load_tables();

		return $this->_init($sql, $options);
  	}

	/**
	 *		\brief		Function called when module is disabled.
 	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
 	 *					Data directories are not deleted.
 	 */
	function remove($options='')
	{
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
  		global $db,$conf,$langs,$user,$mysoc;
  		
  		dol_syslog("Cyberoffice::myextra->delete");
  		$myextra = new ExtraFields($db);
  		$resultExtra=$myextra->delete('longdescript','product');
		$resultExtra=$myextra->delete('cyberprice','product');
		
  		$produitS = new Product($db);
		if($produitS->fetch('','PrestaShipping') > 0) {
			$res = $produitS->delete($produitS->id);
			dol_syslog("Cyberoffice::produit->delete ".$produitS->id.' ' . $res);
			if ($res > 0) dolibarr_del_const($db, 'CYBEROFFICE_SHIPPING', -1);
				else dolibarr_set_const($db, 'CYBEROFFICE_SHIPPING', $produitS->id);
		}
		$produitD = new Product($db);
		if($produitD->fetch('','PrestaDiscount') > 0) {
			$res = $produitD->delete($produitD->id);
			dol_syslog("Cyberoffice::produit->delete ".$produitD->id.' ' . $res);
			if ($res > 0) dolibarr_del_const($db, 'CYBEROFFICE_DISCOUNT', -1);
				else dolibarr_set_const($db, 'CYBEROFFICE_DISCOUNT', $produitD->id);
		}
		$sql = array();

		return $this->_remove($sql, $options);
	}
	function load_tables()
	{
		// ALTER TABLE llx_c_ziptown ADD COLUMN fk_pays integer NOT NULL DEFAULT 0 after fk_county; -->
		return $this->_load_tables('/cyberoffice/sql/');
	}

}

?>

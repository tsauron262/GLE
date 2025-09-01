<?php
/*
 * @author 	LVSinformatique <contact@lvsinformatique.com>
 * @copyright  	2014 LVSInformatique
 * This source file is subject to a commercial license from LVSInformatique
 * Use, copy or distribution of this source file without written
 * license agreement from LVSInformatique is strictly forbidden.
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

class modcyberoffice8 extends DolibarrModules
{

	function __construct($db)
    {
        global $conf, $langs;
		$this->db = $db;
		$langs->load('cyberoffice@cyberoffice8');
		$this->numero = 171100;
		$this->rights_class = 'cyberoffice8';
        $this->family = "technic";
		$this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = $langs->trans("SynchronizationfromPrestashoptoDolibarrERPCRM");
		$this->version = '8.2.1';
		$this->editor_name = 'LVSInformatique';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 2;
		$this->picto='cyberoffice@cyberoffice8';
		$this->langfiles = array("cyberoffice@cyberoffice8");
		$this->module_parts = array();
		$this->triggers = 0;
		$this->dirs = array();
		$r=0;
		$this->config_page_url = array("cyberoffice_setupapage.php@cyberoffice8");

		$this->depends = array();
		$this->depends= array('modCategorie', 'modService');
		$this->requiredby = array();
		$this->phpmin = array(7,3);
		$this->need_dolibarr_version = array(17,0);
		$this->tabs = '';
		// Dictionaries
		//$this->dictionaries=array();
        $this->dictionaries=array(
            'langs'=>'cyberoffice@cyberoffice8',
            'tabname'=>array(   MAIN_DB_PREFIX."c_cyberoffice",
                                MAIN_DB_PREFIX."c_cyberoffice2"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array(    "cyberoffice_extrafield",
                                "cyberoffice_carrier_warehouse"),													// Label of tables
            'tabsql'=>array(    'SELECT f.rowid as rowid, f.extrafield, f.idpresta, f.active FROM '.MAIN_DB_PREFIX.'c_cyberoffice as f',
                                'SELECT g.rowid as rowid, g.warehouse, g.carrier, g.active FROM '.MAIN_DB_PREFIX.'c_cyberoffice2 as g'),	// Request to select fields
            'tabsqlsort'=>array(    "extrafield ASC",
                                    "warehouse ASC"),																					// Sort order
            'tabfield'=>array("extrafield,idpresta",
                                "warehouse,carrier"),																					// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("extrafield,idpresta",
                                    "warehouse,carrier"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("extrafield,idpresta",
                                    "warehouse,carrier"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array(1,1)												// Condition to show each dictionary
        );
        
		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();		// Permission array used by this module

			// Menus
		$this->menu = array();
		$r=0;
        /*$this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=companies,fk_leftmenu=customers',
            'type'=>'left',
            'titre'=>$langs->trans("List_Cyber"),
            'mainmenu'=>'companies',
            'leftmenu'=>'customers',
            'url'=>'/custom/cyberoffice8/list.php',
            'langs'=>'cyberoffice@cyberoffice8',
            'position'=>100,
            'enabled'=>'$conf->cyberoffice8->enabled',
            'perms'=>1,
            'target'=>'',
            'user'=>2
        );
        $r++;*/
    }

    function init($options='')
    {	
        global $db,$conf,$langs,$user,$mysoc;

		$langs->load('cyberoffice@cyberoffice8');
		$rep = getcwd()."/../cyberoffice8";
        if (file_exists($rep)) {
            setEventMessages($langs->trans('errorcustom8'), '', 'errors');
            return -1;
        }
		if (!empty($conf->global->MAIN_MODULE_CYBEROFFICE)) {
			setEventMessages($langs->trans('MAIN_MODULE_CYBEROFFICEActive'), '', 'errors');
            return -1;
		}

        $rep = $conf->file->dol_document_root['main']."/cyberoffice";
        if (file_exists($rep)) {
            $res = $this->rrmdir($rep);
        }
        
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
  	
  		//longdescript, longdescript, text, , 2000, product, 0, 0,, Array
  		/* ====================== extrafiels=======================*/
  		$myextra = new ExtraFields($db);
  		$myextra->addExtraField('longdescript', 'longdescript', 'text',0, 2000, 'product');
  		//$myextra2 = new ExtraFields($db);
  		//$myextra2->addExtraField('cyberprice', 'cyberprice', 'double',0, '24,8', 'product');
  		$form = new Form($db);
  		$num = $form->load_cache_vatrates("'".$mysoc->country_code."'");
  		$defaulttx = -1;
		$societe_acheteuse = null;
		$idprod = null;
        if ($num > 0) {
        	// Definition du taux a pre-selectionner (si defaulttx non force et donc vaut -1 ou '')
        	if ($defaulttx < 0 || dol_strlen($defaulttx) == 0) {
				$tmpthirdparty=new Societe($db);
				$defaulttx=get_default_tva($mysoc,(is_object($societe_acheteuse)?$societe_acheteuse:$tmpthirdparty),$idprod);
				$defaultnpr=get_default_npr($mysoc,(is_object($societe_acheteuse)?$societe_acheteuse:$tmpthirdparty),$idprod);
				if (empty($defaulttx))
					$defaultnpr=0;
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
		$produit->label				= 'PrestaShipping';
		$produit->description		= 'PrestaShipping';
		$produit->type				= 1;
		$produit->status			= 1;
		$produit->status_buy		= 1;
		$produit->ref				= 'PrestaShipping';
		//barcode
		$produit->barcode = -1;
		//dol_syslog("Cyberoffice::produit->createPrestaShipping");
  		$results = $produit->create($user);
  		if ($results > 0) 
  			dolibarr_set_const($db, 'CYBEROFFICE_SHIPPING', $results, 'chaine', 0, '', $conf->entity);
        else {
			if ($produit->fetch('','PrestaShipping') > 0) {
				$results = $produit->id;
			dolibarr_set_const($db, 'CYBEROFFICE_SHIPPING', $produit->id, 'chaine', 0, '', $conf->entity);
			}
  		}
  		//dol_syslog("Cyberoffice::produit->createPrestaShipping ".$results );
		
		$produitD = new Product($db);
  		$produitD->price_base_type 	= 'HT';
		$produitD->price				= 0;
		$produitD->price_ttc 		= 0;
		$produitD->tva_tx			= $defaulttx;
		$produitD->label				= 'PrestaDiscount';
		$produitD->description		= 'PrestaDiscount';
		$produitD->type				= 1;
		$produitD->status			= 1;
		$produitD->status_buy		= 1;
		$produitD->ref				= 'PrestaDiscount';
        $produitD->barcode = -1;
		//dol_syslog("Cyberoffice::produit->createPrestaDiscount ");
  		$resultd = $produitD->create($user);
  		if ($resultd > 0) 
  			dolibarr_set_const($db, 'CYBEROFFICE_DISCOUNT', $resultd, 'chaine', 0, '', $conf->entity);
  		else {
  			if ($produitD->fetch('','PrestaDiscount') > 0) {
                $resultd = $produitD->id;
  				dolibarr_set_const($db, 'CYBEROFFICE_DISCOUNT', $produitD->id, 'chaine', 0, '', $conf->entity);
            }
  		}
        $produitD = new Product($db);
  		$produitD->price_base_type 	= 'HT';
		$produitD->price				= 0;
		$produitD->price_ttc 		= 0;
		$produitD->tva_tx			= $defaulttx;
		$produitD->label				= 'EcoParticipation';
		$produitD->description		= 'EcoParticipation';
		$produitD->type				= 1;
		$produitD->status			= 1;
		$produitD->status_buy		= 1;
		$produitD->ref				= 'EcoParticipation';
        $produitD->barcode = -1;
		//dol_syslog("Cyberoffice::produit->createPrestaDiscount ");
  		$resultep = $produitD->create($user);
  		if ($resultep > 0) 
  			dolibarr_set_const($db, 'CYBEROFFICE_ECOP', $resultep, 'chaine', 0, '', $conf->entity);
  		else {
  			if ($produitD->fetch('','EcoParticipation') > 0) {
                $resultep = $produitD->id;
  				dolibarr_set_const($db, 'CYBEROFFICE_ECOP', $produitD->id, 'chaine', 0, '', $conf->entity);
            }
  		}
  		//dol_syslog("Cyberoffice::produit->createPrestaDiscount ".$resultd );
                
        $produitW = new Product($db);
  		$produitW->price_base_type 	= 'HT';
		$produitW->price				= 0;
		$produitW->price_ttc 		= 0;
		$produitW->tva_tx			= $defaulttx;
		$produitW->label				= 'Prestawrapping';
		$produitW->description		= 'Prestawrapping';
		$produitW->type				= 1;
		$produitW->status			= 1;
		$produitW->status_buy		= 1;
		$produitW->ref				= 'Prestawrapping';
        $produitW->barcode = -1;
		//dol_syslog("Cyberoffice::produit->createPrestawrapping ");
  		$resultw = $produitW->create($user);
  		if ($resultw > 0) 
  			dolibarr_set_const($db, 'CYBEROFFICE_wrapping', $resultw, 'chaine', 0, '', $conf->entity);
  		else {
  			if ($produitW->fetch('','Prestawrapping') > 0) {
                $resultw = $produitW->id;
  				dolibarr_set_const($db, 'CYBEROFFICE_wrapping', $produitW->id, 'chaine', 0, '', $conf->entity);
            }
  		}
  		//dol_syslog("Cyberoffice::produit->createPrestawrapping ".$resultw );
                
  		//print $resultd;die();
		$this->const = array(
			0=>array('CYBEROFFICE_SHIPPING','chaine',$results,'service\'s rowid',1, 'current'),
		    1=>array('CYBEROFFICE_DISCOUNT','chaine',$resultd,'service\'s rowid',1, 'current'),
		    2=>array('CYBEROFFICE_invoice','chaine',1,'Invoice Synchronization',1, 'current'),
		    3=>array('CYBEROFFICE_stock','chaine',1,'Stock Synchronization',1, 'current'),
		    4=>array('CYBEROFFICE_chanel','chaine',0,'number of chanels',1, 'current'),
            5=>array('CYBEROFFICE_wrapping','chaine',$resultw,'service\'s rowid',1, 'current'),
		);
                    
    	$sql = array();
		$sql=array(
            array('sql' => "ALTER TABLE ".MAIN_DB_PREFIX."c_paiement ADD COLUMN cyberbank INTEGER DEFAULT NULL;", 'ignoreerror' => 1),
            array('sql' => "create table ".MAIN_DB_PREFIX."c_cyberoffice (
                    rowid         integer AUTO_INCREMENT PRIMARY KEY,
                    extrafield    varchar(64) NOT NULL,
                    idpresta      integer DEFAULT 0 NOT NULL,
                    active        tinyint DEFAULT 1  NOT NULL
                )ENGINE=innodb;", 'ignoreerror' => 1),
            array('sql' => "create table ".MAIN_DB_PREFIX."c_cyberoffice2 (
                    rowid       integer AUTO_INCREMENT PRIMARY KEY,
                    warehouse   integer NOT NULL,
                    carrier     integer DEFAULT 0 NOT NULL,
                    active      tinyint DEFAULT 1  NOT NULL
                )ENGINE=innodb;", 'ignoreerror' => 1),
            array('sql' => "create table ".MAIN_DB_PREFIX."c_cyberoffice3 (
                    rowid       integer AUTO_INCREMENT PRIMARY KEY,
                    attribut    integer DEFAULT NULL,
                    variant     integer DEFAULT NULL,
                    shop        varchar(2) DEFAULT NULL
                )ENGINE=innodb;", 'ignoreerror' => 1),
            array('sql' => "ALTER TABLE ".MAIN_DB_PREFIX."c_cyberoffice ADD UNIQUE INDEX uk_c_cyberoffice(extrafield);", 'ignoreerror' => 1),
            array('sql' => "ALTER TABLE ".MAIN_DB_PREFIX."c_cyberoffice2 ADD UNIQUE INDEX uk_c_cyberoffice2(carrier);", 'ignoreerror' => 1),
            );
		$result=$this->load_tables();
        ////extrafield
        foreach($myextra->fetch_name_optionals_label('product') as $key => $value)
        {
            $mysql = "INSERT INTO ".MAIN_DB_PREFIX."c_cyberoffice (extrafield) VALUES ('".$key."')";
            if ($key != 'longdescript') 
                $resql = $db->query($mysql);
        }
        
        $entrepot = new Entrepot($db);
        foreach($entrepot->list_array() as $key => $value)
        {
            $mysql = "INSERT INTO ".MAIN_DB_PREFIX."c_cyberoffice2 (warehouse, carrier) VALUES (".$key.",0)";
            $resql = $db->query($mysql);
        }

        return $this->_init($sql, $options);
    }

	function remove($options='')
	{
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
  		global $db,$conf,$langs,$user,$mysoc;
  		
  		//dol_syslog("Cyberoffice::myextra->delete");
  		$myextra = new ExtraFields($db);
  		$resultExtra=$myextra->delete('longdescript','product');
		//$resultExtra=$myextra->delete('cyberprice','product');
		
  		$sql = array();

		return $this->_remove($sql, $options);
	}
	function load_tables()
	{
		return $this->_load_tables('');
	}
	function rrmdir($src)
	{
		$dir = opendir($src);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				$full = $src . '/' . $file;
				if ( is_dir($full) ) {
					$this->rrmdir($full);
				} else {
					unlink($full);
				}
			}
		}
		closedir($dir);
		rmdir($src);
	}
}

<?php
/**
 *	MyCyberOffice
 *
 *  @author		LVSinformatique <contact@lvsinformatique.com>
 *  @copyright	2015 LVSInformatique
 *	@license	NoLicence
 *  @version	1.2.2
 */


/**
 *  Class of triggers
 */
class InterfaceCust
{
    var $db;
    var $location;
    
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "mycyberoffice";
        $this->description = "Triggers of this module allows to manage mycyberoffice workflow";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'mycyberoffice@mycyberoffice';
    }
    
    
    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }
    
    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
    	dol_syslog("Mycyber executed by ".$action.'::'.$_SERVER['HTTP_USER_AGENT']);
    	$pos_soap = stripos($_SERVER['HTTP_USER_AGENT'], 'NuSOAP');
    	$pos_curl = stripos($_SERVER['HTTP_USER_AGENT'], 'CURL');
    	if($pos_soap !== false || $pos_curl !== false) 
    		{$action = "xxx";dol_syslog("Mycyber NOT executed by ".$action.'::'.$_SERVER['HTTP_USER_AGENT']);}

        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Products
        if ($action == 'xxPRODUCT_CREATExx')
        {
        	//print '<pre>';print_r($object);print '</pre>';exit;
        	$cats = array();
			$cats[] = array('id'				=>	$object->id,
							'ref'				=>	$object->ref,
							'label'				=>	(DOL_VERSION < '3.8.0'?$object->libelle:$object->label),
							'description'		=>	$object->description,
							'longdescript'		=>	$object->array_options['options_longdescript'],
							'reel'				=>  'cybernull',
							'cyberprice'		=>	$object->array_options['options_cyberprice'],
							'price'				=>	($object->price && $object->price > 0?$object->price:'cybernull'),
							'tva_tx'			=>	$conf->global->{"MYCYBEROFFICE_tax".(float)$object->tva_tx},
							'status'			=>	$object->status,
							'import_key'		=>  $object->import_key,
							'ean13'				=>	($object->barcode_type==2?$object->barcode:'cybernull'),
							'upc'				=>	($object->barcode_type==3?$object->barcode:'cybernull'),
							'isbn'				=>	($object->barcode_type==4?$object->barcode:'cybernull'),
							'weight'			=>  $object->weight,
							'length'			=>  $object->length,
							'cost_price'		=>	(DOL_VERSION < '3.9.0'?$object->pmp:$object->cost_price),
							'imageDel'			=>	'cybernull',
							);
		
		//	if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '') 
				$this->MyProduct($cats,$object);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id.', importkey='.$cats['0']['import_key'].','.substr($cats['0']['import_key'], -1, 1));
        	//dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
        }
        elseif ($action == 'PRODUCT_MODIFY')
        {
        	//print '<pre>';print_r($object);print '</pre>';die();
            $cats = array();
			$cats[] = array('id'				=>	$object->id,
							'ref'				=>	$object->ref,
							'label'				=>	(DOL_VERSION < '3.8.0'?$object->libelle:$object->label),
							'description'		=>	$object->description,
							'longdescript'		=>	$object->array_options['options_longdescript'],
							'reel'				=>  'cybernull',
							'cyberprice'		=>	$object->array_options['options_cyberprice'],
							'price'				=>	$object->price,
							'tva_tx'			=>	$conf->global->{"MYCYBEROFFICE_tax".(float)$object->tva_tx},
							'status'			=>	$object->status,
							'import_key'		=>  $object->import_key,
							'ean13'				=>	($object->barcode_type==2?$object->barcode:'cybernull'),
							'upc'				=>	($object->barcode_type==3?$object->barcode:'cybernull'),
							'isbn'				=>	($object->barcode_type==4?$object->barcode:'cybernull'),
							'weight'			=>  $object->weight,
							'length'			=>  $object->length,
							'cost_price'		=>	(DOL_VERSION < '3.9.0'?$object->pmp:$object->cost_price),
							'imageDel'			=>	'cybernull',
							);
			//if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '') 
				$this->MyProduct($cats,$object);
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id.', importkey='.$cats['0']['import_key']);

        }
		elseif ($action == 'PRODUCT_PRICE_MODIFY')
        {
        	//print_r($object);exit;
        	//print_r($_SERVER);
        	$cats = array();
			$cats[] = array('id'				=>	$object->id,
							'ref'				=>	$object->ref,
							'label'				=>	(DOL_VERSION < '3.8.0'?$object->libelle:$object->label),
							'description'		=>	$object->description,
							'longdescript'		=>	$object->array_options['options_longdescript'],
							'reel'				=>  'cybernull',
							'cyberprice'		=>	'cybernull',
							'price'				=>	$object->price,
							'tva_tx'			=>	$conf->global->{"MYCYBEROFFICE_tax".(float)$object->tva_tx},
							'status'			=>	'cybernull',
							'import_key'		=>  $object->import_key,
							'ean13'				=>	($object->barcode_type==2?$object->barcode:'cybernull'),
							'upc'				=>	($object->barcode_type==3?$object->barcode:'cybernull'),
							'isbn'				=>	($object->barcode_type==4?$object->barcode:'cybernull'),
							'weight'			=>  $object->weight,
							'length'			=>  $object->length,
							'cost_price'		=>	(DOL_VERSION < '3.9.0'?$object->pmp:$object->cost_price),
							'imageDel'			=>	'cybernull',
							);
			//if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '' ) 
				$this->MyProduct($cats,$object);
			
			/***** A FAIRE :: mise à jour de tous les taux de tva sur decliansion 
			updatePrice($newprice, $newpricebase, $user, $newvat='',$newminprice='', $level=0, $newnpr=0, $newpsq=0)
			$object->price, $object->price_base_type,$user,$object->tva_tx)
			
				$count = 0;
				$count = substr_count($object->import_key, '-');
				if($count == 2) {
					$nbr = 0;
					$nbr = strrpos($object->import_key, '-');
					if ($nbr === false) $nbr=0;
					$product_search = substr($object->import_key, 0, $nbr + 1);
					$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'product
						WHERE import_key LIKE "'.$product_search.'%"';
					dol_syslog("MyCyberoffice ".$action." sql = ".$sql);
					$resql = $db->query($sql);
					if ($resql) {
						while ( $product = $db->fetch_object($resql) )
						{
							print_r($product);
							print '<br/>';exit;
						}
					}
				}
		*/
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id.' price '.$object->price.', importkey='.$cats['0']['import_key']);

        }
        elseif ($action == 'STOCK_MOVEMENT')
        {
        	//if ($object->entrepot_id == $conf->global->MYCYBEROFFICE_warehouse || $conf->global->MYCYBEROFFICE_warehouse == 0) {
	        	$cats = array();
				$sql = 'SELECT p.*, ps.reel, ps.fk_entrepot
						FROM '.MAIN_DB_PREFIX.'product p
						LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps ON (ps.fk_product = p.rowid and ps.fk_entrepot = '.$object->entrepot_id.')
						WHERE p.entity IN (0, '.$conf->entity.') AND p.rowid='.$object->product_id.' ORDER BY p.rowid ';//1322
				$res = $this->db->query($sql);
				$produits = $this->db->fetch_array($res);
				
				$sql = 'SELECT p.*, SUM(ps.reel) as reel
						FROM '.MAIN_DB_PREFIX.'product p
						LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps ON (ps.fk_product = p.rowid)
						WHERE p.entity IN (0, '.$conf->entity.') AND p.rowid='.$object->product_id.' GROUP BY p.rowid ';//1322
				$res = $this->db->query($sql);
				$produits0 = $this->db->fetch_array($res);

				$cats = array();
			$cats[] = array('id'				=>	$object->product_id,
							'ref'				=>	$produits['ref'],
							'label'				=>	'cybernull',
							'description'		=>	$object->description,
							'longdescript'		=>	$object->array_options['options_longdescript'],
							'reel'				=>  $produits['reel'],
							'cyberprice'		=>	'cybernull',
							'price'				=>	'cybernull',
							'tva_tx'			=>	$conf->global->{"MYCYBEROFFICE_tax".(float)$object->tva_tx},
							'status'			=>	'cybernull',
							'import_key'		=>  $produits['import_key'],
							'ean13'				=>	($object->barcode_type==2?$object->barcode:'cybernull'),
							'upc'				=>	($object->barcode_type==3?$object->barcode:'cybernull'),
							'isbn'				=>	($object->barcode_type==4?$object->barcode:'cybernull'),
							'cost_price'		=>	(DOL_VERSION < '3.9.0'?$object->pmp:$object->cost_price),
							'imageDel'			=>	'cybernull',
							'reel0'				=>  $produits0['reel'],
							);
			//print_r($cats);print substr($cats['0']['import_key'], -1, 1);exit;				
			//if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '') 
				$this->MyProduct($cats,$object, $object->entrepot_id);
		        dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->product_id.' qty='.$produits['reel']);
	        //}
	    }
	    elseif ($action == 'PICTURE_CREATE')
        {
        	//echo '<pre>';print_r($object);echo '</pre>';die();
        	if (isset($object->mycyber) && is_array($object->mycyber) && count($object->mycyber) > 0) {
	        	$cats = array();
				$cats[] = array('id'				=>	$object->id,
								'ref'				=>	$object->ref,
								'label'				=>	'cybernull',
								'reel'				=>	'cybernull',
								'image'				=>	$object->mycyber,
								'cyberprice'		=>	'cybernull',
								'price'				=>	'cybernull',
								'status'			=>	'cybernull',
								'ean13'				=>	($object->barcode_type==2?$object->barcode:'cybernull'),
								'upc'				=>	($object->barcode_type==3?$object->barcode:'cybernull'),
								'isbn'				=>	($object->barcode_type==4?$object->barcode:'cybernull'),
								'imageDel'			=>	'cybernull',
								'import_key'		=>  $object->import_key,
								'action'			=> $action
								);
							
			$this->MyProduct($cats,$object);
			}
		    dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
	    }
	    elseif ($action == 'PICTURE_DELETE')
        {
        	//echo '<pre>';print_r($object);echo '</pre>';die();
        		$cats = array();
				$cats[] = array('id'				=>	$object->id,
								'ref'				=>	$object->ref,
								'label'				=>	'cybernull',
								'reel'				=>	'cybernull',
								'image'				=>	$object->mycyber,
								'cyberprice'		=>	'cybernull',
								'price'				=>	'cybernull',
								'status'			=>	'cybernull',
								'ean13'				=>	($object->barcode_type==2?$object->barcode:'cybernull'),
								'upc'				=>	($object->barcode_type==3?$object->barcode:'cybernull'),
								'isbn'				=>	($object->barcode_type==4?$object->barcode:'cybernull'),
								'imageDel'			=>	$object->mycyberDel,
								'import_key'		=>  $object->import_key,
								'action'			=> $action
								);
			//print_r($cats);exit;				
			$this->MyProduct($cats,$object);
		    dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
	    }

	    elseif ($action == 'CATEGORY_LINK')
		{	
		//print '<pre>';print_r($object);print '</pre>';die();
			$sql = "SELECT rowid, import_key";
			$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
			$sql.= " WHERE rowid = '".$object->id."'";
			$resql = $this->db->query($sql);
			if ($resql) {
				$res = $this->db->fetch_array($resql);
				$import_key = $res['import_key'];
			}
			if (!$object->linkto->import_key) {
				$newobject = new Product($this->db);
				$newobject->fetch($object->linkto->id);
				$newimport_key = $newobject->import_key;
				$cats = array();
				$cats[] = array('id'				=>	$newobject->id,
								'import_key'		=>  $newobject->import_key,
								'ref'				=>	$newobject->ref,
								'label'				=>	(DOL_VERSION < '3.8.0'?$newobject->libelle:$newobject->label),
								'description'		=>	$newobject->description,
								'longdescript'		=>	$newobject->array_options['options_longdescript'],
								'reel'				=>  'cybernull',
								'cyberprice'		=>	$newobject->array_options['options_cyberprice'],
								'price'				=>	($newobject->price && $newobject->price > 0?$newobject->price:'cybernull'),
								'tva_tx'			=>	$conf->global->{"MYCYBEROFFICE_tax".(float)$newobject->tva_tx},
								'status'			=>	$newobject->status,
								'ean13'				=>	($newobject->barcode_type==2?$newobject->barcode:'cybernull'),
								'upc'				=>	($newobject->barcode_type==3?$newobject->barcode:'cybernull'),
								'isbn'				=>	($newobject->barcode_type==4?$newobject->barcode:'cybernull'),
								'weight'			=>  $newobject->weight,
								'length'			=>  $newobject->length,
								'cost_price'		=>	(DOL_VERSION < '3.9.0'?$newobject->pmp:$newobject->cost_price),
								'imageDel'			=>	'cybernull',
								'cat'				=>	$import_key,
								'action'			=> 'add',
								);
			} else {
				$newobject = new Product($this->db);
				$newobject->fetch($object->linkto->id);
				$newimport_key = $object->linkto->import_key;
				$cats = array();
				$cats[] = array('id'				=>	$object->linkto->id,
								'import_key'		=>  $newimport_key,
								'ref'				=>	$object->linkto->ref,
								'label'				=>	(DOL_VERSION < '3.8.0'?$newobject->libelle:$newobject->label),
								'description'		=>	$newobject->description,
								'longdescript'		=>	$newobject->array_options['options_longdescript'],
								'reel'				=>  'cybernull',
								'cyberprice'		=>	$newobject->array_options['options_cyberprice'],
								'price'				=>	($newobject->price && $newobject->price > 0?$newobject->price:'cybernull'),
								'tva_tx'			=>	$conf->global->{"MYCYBEROFFICE_tax".(float)$newobject->tva_tx},
								'status'			=>	$newobject->status,
								'ean13'				=>	($newobject->barcode_type==2?$newobject->barcode:'cybernull'),
								'upc'				=>	($newobject->barcode_type==3?$newobject->barcode:'cybernull'),
								'isbn'				=>	($newobject->barcode_type==4?$newobject->barcode:'cybernull'),
								'weight'			=>  $newobject->weight,
								'length'			=>  $newobject->length,
								'cost_price'		=>	(DOL_VERSION < '3.9.0'?$newobject->pmp:$newobject->cost_price),
								'imageDel'			=>	'cybernull',
								'cat'				=>	$import_key,
								'action'			=> 'add',
								);
			}
			//print '<pre>';print_r($cats);print '</pre>';die();
			//if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '')			
				$this->MyProduct($cats,$object->linkto);
		    dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->linkto->id);
		}
		elseif ($action == 'CATEGORY_UNLINK')
		{	
			//echo '<pre>';print_r($object);echo '</pre>';die();
			$sql = "SELECT rowid, import_key";
			$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
			$sql.= " WHERE rowid = '".$object->id."'";
			$resql = $this->db->query($sql);
			if ($resql) {
				$res = $this->db->fetch_array($resql);
				$import_key = $res['import_key'];
			}
			$cats = array();
			$cats[] = array('id'				=>	$object->unlinkoff->id,
							'import_key'		=>  $object->unlinkoff->import_key,
							'ref'				=>	$object->unlinkoff->ref,
							'reel'				=>	'cybernull',
							'cyberprice'		=>	'cybernull',
							'price'				=>	'cybernull',
							'ean13'				=>	($object->barcode_type==2?$object->barcode:'cybernull'),
							'upc'				=>	($object->barcode_type==3?$object->barcode:'cybernull'),
							'isbn'				=>	($object->barcode_type==4?$object->barcode:'cybernull'),
							'cat'				=>	$import_key,
							'action'			=> 'remove',
							'imageDel'			=>	'cybernull',
							);
							
			if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '') 
				$this->MyProduct($cats,$object->unlinkoff);
		    dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->unlinkoff->id);
		}
		elseif ($action == 'CATEGORY_CREATE' || $action == 'CATEGORY_MODIFY')
		{	
			$sql = 'SELECT c.*, c2.import_key as c2_import_key
				FROM '.MAIN_DB_PREFIX.'categorie c
				LEFT JOIN '.MAIN_DB_PREFIX.'categorie c2 ON c.fk_parent = c2.rowid	
				WHERE c.rowid = '.$object->id;
			$resql = $this->db->query($sql);
			$cats = array();
			if ($resql) {
				$res = $this->db->fetch_array($resql);
				$import_key = $res['import_key'];
				$cats[] = array('id'				=>	$res['rowid'],
								'import_key'		=>  $res['import_key'],
								'parent'			=>	$res['c2_import_key'],
								'name'				=>	$res['label'],
								'description'		=>	$res['description'],
								'action'			=>	$action,
								);
							
				$this->MyCategory($cats,$action);
			}
		    dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__." id=".$object->id.$action);
		}
		elseif ($action == 'CATEGORY_DELETE')
        {
        	dol_syslog("mycyberoffice ".__LINE__.$object->description);
			if ($object->description == 'mycyberoffice') return 0;

        	$this->db->rollback();
        	dol_syslog("mycyberoffice ".__LINE__.$object->description);
        	$sql = 'SELECT c.*
				FROM '.MAIN_DB_PREFIX.'categorie c
				WHERE c.rowid = '.$object->id;
			$resql = $this->db->query($sql);
			if ($resql) {
				$res = $this->db->fetch_array($resql);
				$import_key = $res['import_key'];
			}
			dol_syslog("mycyberoffice ".__LINE__.$import_key);
			/*if (!$import_key)
			{ 
				$category_del = new Categorie($this->db);
				$category_del->fetch($object->id);
				$category_del->delete($user);
				return 0;
			} else {*/
				$category_del = new Categorie($this->db);
				$category_del->fetch($object->id);
				$category_del->description = 'mycyberoffice';
				$category_del->update($object->id);
				//$sql = 'UPDATE '.MAIN_DB_PREFIX.'categorie SET description = "mycyberoffice"	
				//WHERE rowid = '.$object->id;
				//$resql = $this->db->query($sql);

				$category_del->delete($user);
			//}
			dol_syslog("CATEGORY_DELETE::".__LINE__.$import_key).$res['rowid'];
        	//print '<pre>'.$res['import_key'].$res['rowid'];print_r($object);print '</pre>';die();
			$cats[] = array('id'				=>	$object->id,
							'import_key'		=>  $import_key,
							'parent'			=>	$import_key,
							'name'				=>	'',
							'description'		=>	'',
							'action'			=>	$action,
						);
							
			if ($import_key)
				$this->MyCategory($cats, $action);
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__." id=".$object->id.$object->cyber);
        }

		// Customer orders
		elseif ($action == 'ORDER_CLOSE')
        {
			$this->MyOrder($object->id, $action);
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id.$object->cyber);
        }
        elseif ($action == 'PAYMENT_ADD_TO_BANKxxx')
        {
        	$this->MyOrder($cats);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'SHIPPING_MODIFY')
        {
        	$this->MyOrder($object->id, $action);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'SHIPPING_VALIDATE')
        {
        	$this->MyOrder($object->id, $action);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

		return 0;
    }
    
    function MyOrder($cats, $action) {
    	global $conf, $db;
    	//require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
    	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    	//require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
    	if ($action=='ORDER_CLOSE') {
    		$order = new Commande($this->db);
    		$order->fetch($cats);
    		$ref_customer=$order->ref_client;
    	} else {
	       	require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
            $expedition = new Expedition($this->db);
            $expedition->fetch($cats);
            $expedition->fetchObjectLinked();
            if (count($expedition->linkedObjectsIds['commande']) > 0 && $conf->global->MYCYBEROFFICE_InvoiceNumber==1)
            {
            	foreach ($expedition->linkedObjectsIds['commande'] as $key => $value)
                {
                	$originforcontact = 'commande';
                	$originidforcontact = $value;
                	break; // We take first one
                }
            }

	        $order = new Commande($this->db);
    		$order->fetch($originidforcontact);
    		$ref_customer=$order->ref_client;
        }
        
        $RefInvoice = 'cybernull';
        $order->fetchObjectLinked();
		if (count($order->linkedObjectsIds['facture']) > 0)
        {
            foreach ($order->linkedObjectsIds['facture'] as $key => $value)
            {
            	$originidInvoice = $value;
               	break; // We take first one
            }
            $objectInvoice = new Facture($db);
            $objectInvoice->fetch($originidInvoice);
            $RefInvoice = ereg_replace("[^0-9]","",$objectInvoice->ref);
		}
   
        $pos1 = strrpos($ref_customer, ":");
		if ($pos1 === false) {
			$pos1 = 0;
		} else $pos1 = $pos1 +1;
		$pos2 = strrpos($ref_customer, "/");
		if ($pos2 === false) {
			$pos2 = strlen($ref_customer);
		}
		$myref = substr($ref_customer, $pos1, $pos2 - $pos1);
		$ref_customer = $myref;
        dol_syslog("MyCyberoffice_Trigger_MyOrder:: ".__LINE__.'::'.$action.' ref_customer='.$ref_customer.' cde='.$order->id);
		/*
    	print '<pre>';
    	print_r($expedition);
    	print '</pre>';
    	exit;
    	*/
    	
    	$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'const WHERE name LIKE "CYBEROFFICE_SHOP%" ORDER BY name';
		$resql1758 = $db->query($sql);
		if ($resql1758) {
			while ($obj = $db->fetch_object($resql1758))
			{
					/*
					$h = (int)substr($obj->name,-2);
					$head[$h][0] = $_SERVER["PHP_SELF"]."?shop=".substr($obj->name,-2);
					$head[$h][1] = $langs->trans("Shop").substr($obj->name,-2);
					$head[$h][2] = $langs->trans("Shop").substr($obj->name,-2);
					$head[$h][3] = substr($obj->name,-2);//shop
					$head[$h][4] = $obj->value;//indice
					$head[$h][5] = $obj->note;//path
					*/
				$myglobalkey = "MYCYBEROFFICE_key" . substr($obj->name,-2);
				$myglobalshop = "MYCYBEROFFICE_shop" . substr($obj->name,-2);
				$mygloballang = "MYCYBEROFFICE_lang" . substr($obj->name,-2);
				$myglobalSexpedie = "MYCYBEROFFICE_Sexpedie" . substr($obj->name,-2);
				$myglobalSlivre = "MYCYBEROFFICE_Slivre" . substr($obj->name,-2);
					
				dol_syslog("MyCyberoffice_Trigger_MyOrder::shop=".$myglobalshop.'::'.$obj->note);

		    	$ws_dol_url = $obj->note.'modules/mycyberoffice/server_order_soap.php';
				$ws_method  = 'Create';
				$ns = 'http://www.lvsinformatique.com/ns/';
				// Set the WebService URL
				$options = array('location' => $obj->note.'modules/mycyberoffice/server_order_soap.php',
			                  'uri' => $obj->note);
				$soapclient = new SoapClient(NULL,$options);
			
				// Call the WebService method and store its result in $result.
				$authentication = array(
					'dolibarrkey'=>htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8'),
					'sourceapplication'	=> 'LVSInformatique',
					'login'				=> '',
					'password'			=> '',
					'shop'				=> $conf->global->$myglobalshop,
					'myurl'				=> $_SERVER["PHP_SELF"]
				);
				
				$myparam = array(
					'commande'			=>$ref_customer,
					'expedie'			=>$conf->global->$myglobalSexpedie,
					'livre'				=>$conf->global->$myglobalSlivre,
					'action'			=>$action,
					'tracking_number'	=>$expedition->tracking_number,
					'RefInvoice'		=>$RefInvoice
					);
				
				try {
					$result = $soapclient->create($authentication, $myparam, $ns, '');
					}
					
				      catch(SoapFault $fault)
				      {
				        if($fault->faultstring != 'Could not connect to host')
				        {
				        	/*
				        	print '<pre>';
		    				print_r($fault);
		    				print '</pre>';
		    				exit;
				          throw $fault;
				          */
				        }
				      }
			      
					if (! $result)
					{
						$result = array(
							'result'=>array('result_code' => 'KO', 'result_label' => 'KO'),
							'repertoire' => $obj->note,
							'repertoireTF' => 'KO',
							'webservice' => 'KO',
							'dolicyber' => 'KO',
							'indice' => -1
						);
					}
				if($conf->global->MYCYBEROFFICE_debug==1) {	
					print '<pre>';
		    		print_r($result );
		    		print_r($fault);
		    		print_r($myparam);
		    		print '</pre>';
		    		exit;
		    	}
		    }
		}	
    }
    
	function MyProduct($cats, $object, $warehouse=0) {
    	global $conf, $db;
    	//print '<pre>';print_r($object);print '</pre>';die();
    	dol_syslog("MyCyberoffice_Trigger_MyProduct::id=".$object->id);
    	if($object->entrepot_id && $object->entrepot_id > 0) 
    		$product_id = (int)$object->product_id;
    	else
    		$product_id = (int) $object->id;
		$sql = 'SELECT distinct SUBSTRING(c.import_key,2,2) as numshop
				FROM ' . MAIN_DB_PREFIX . 'categorie_product as ct, ' . MAIN_DB_PREFIX . 'categorie as c
				WHERE ct.fk_categorie = c.rowid AND ct.fk_product = ' . $product_id . ' AND c.type = "product"
				AND c.entity IN (' . getEntity( 'category', 1 ) . ') AND c.import_key is not null';
		dol_syslog("MyCyberoffice_Trigger_MyProduct::sql numshop=".$sql);
		if ($cats[0]['action'] == 'add')
			$numshops = substr($cats[0]['cat'],1,2);
		else
			$numshops = '00';
		$res = $db->query($sql);
		if ($res)
		{
			while ($obj = $db->fetch_object($res))
			{
				if ($obj->numshop) $numshops.= ','.$obj->numshop;
				dol_syslog("MyCyberoffice_Trigger_MyProduct::sql numshop=".$numshops);
			}
		}

    	if($conf->global->CYBEROFFICE_SHIPPING != $product_id &&  $conf->global->CYBEROFFICE_DISCOUNT != $product_id) {	
    		
			$posD = strrpos($cats[0]['import_key'], "-");
        	$posP = strpos($cats[0]['import_key'], "-");
        	$indiceid = substr($cats[0]['import_key'],$posD + 1);
			/*
			print $posD.' **' .$posP.' **' .$indiceid;
			exit;*/
			if($object->entrepot_id && $object->entrepot_id > 0)
				$sql = 'SELECT c.name as name, c.note as note, c1.value as warehouse 
					FROM '.MAIN_DB_PREFIX.'const c 
					left join '.MAIN_DB_PREFIX.'const c1 on (SUBSTRING(c.name,-2) = SUBSTRING(c1.name,-2) AND c1.name LIKE "MYCYBEROFFICE_warehouse%") 
					WHERE c.name LIKE "CYBEROFFICE_SHOP%" AND (c1.value = ' . $object->entrepot_id . ' OR c1.value = 0)
					AND c.value in ('.$numshops.')';
			else
				$sql = 'SELECT *, 999 as warehouse FROM '.MAIN_DB_PREFIX.'const 
					WHERE name LIKE "CYBEROFFICE_SHOP%" AND value in ('.$numshops.')
					ORDER BY name';
					
			dol_syslog("MyCyberoffice_Trigger_MyProduct::sql=".$sql);
			$resql = $db->query($sql);

			if ($resql) {
				while ($obj = $db->fetch_object($resql))
				{
					if ($obj->value == substr($cats[0]['cat'],1,2) || $cats[0]['action']!='add' || $cats[0]['action']!='remove')
					{
						//dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
						//print '<pre>';print_r( $cats);print '</pre>';print 'warehouse='.(int)$obj->warehouse;
						
						if ((int)$obj->warehouse == 0)
							{$cats['0']['reel']=$cats['0']['reel0'];}
						
						$cats['0']['tva_tx'] = $conf->global->{"MYCYBEROFFICE_tax".substr($obj->name,-2).(float)$object->tva_tx};
						
						//print "MYCYBEROFFICE_tax".substr($obj->name,-2).(float)$object->tva_tx;
						//print $conf->global->{"MYCYBEROFFICE_tax".substr($obj->name,-2).(float)$object->tva_tx};
						
						//print '<pre>';print_r( $cats);print '</pre>';exit;
						/*print $cats['0']['reel0'].'<pre>';
						print_r($cats);
						print '</pre>';exit;*/	
						$myglobalkey = "MYCYBEROFFICE_key" . substr($obj->name,-2);
						$myglobalshop = "MYCYBEROFFICE_shop" . substr($obj->name,-2);
						$mygloballang = "MYCYBEROFFICE_lang" . substr($obj->name,-2);
						
						dol_syslog("MyCyberoffice_Trigger_MyProduct::shop=".$myglobalshop.'::'.$obj->note);
						
						$ws_dol_url = $obj->note.'modules/mycyberoffice/server_product_soap.php';
						$ws_method  = 'Create';
						$ns = 'http://www.lvsinformatique.com/ns/';
				
						// Set the WebService URL
						$options = array('location' => $obj->note.'modules/mycyberoffice/server_product_soap.php',
				                  'uri' => $obj->note);
						$soapclient = new SoapClient(NULL,$options);
						
						// Call the WebService method and store its result in $result.
						$authentication = array(
							'dolibarrkey'=>htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8'),
							'sourceapplication'	=> 'LVSInformatique',
							'login'				=> '',
							'password'			=> '',
							'shop'				=> $conf->global->$myglobalshop,
							'lang' 				=> $conf->global->$mygloballang,
							'myurl'				=> $_SERVER["PHP_SELF"]
						);
						//print_r($authentication );
						
						//print_r($cats);exit;
						$myparam = $cats;
						//print_r($myparam );
						//$parameters = array('authentication'=>$authentication, $myparam);
						
						try {
						$result0 = $soapclient->create($authentication, $myparam, $ns, '');
						}
					      catch(SoapFault $fault)
					      {
					        if($fault->faultstring != 'Could not connect to host')
					        {
					        	//print_r($fault);
					          	//throw $fault;
					        }
					      }
				      
						if (! $result0)
						{
							$result = array(
								'result'=>array('result_code' => 'KO', 'result_label' => 'KO'),
								'repertoire' => $obj->note,
								'repertoireTF' => 'KO',
								'webservice' => 'KO',
								'dolicyber' => 'KO',
								'lang' => $conf->global->$mygloballang,
								'Result' => $result0
							);
						}
						if ($result0['result']['result_label'] != 'NOK' && $result0['result']['result_label'] != 'OK')
						{
							/*
							$sql = "SELECT *";
							$sql.= " FROM ".MAIN_DB_PREFIX."const";
							$sql.= " WHERE note = '".$conf->global->MYCYBEROFFICE_path."/'";
							$resql = $db->query($sql);
							$num = 0;
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res = $db->fetch_array($resql);
									$num=$res['value'];
								}
							}
							*/
							$num = $obj->value;
							$myid = explode(":", $result0['result']['result_label']);
							$import_key = 'P'.$num.'-'.$myid[0];
							$sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
							$sql .= " import_key='".$import_key."'";
							$sql.= " WHERE rowid=".$object->id;
							dol_syslog("MyCyberoffice_Trigger_MyProduct::avant maj import_key=".$import_key.'/'.$num.'/'.$myid[0]);
							if (substr($import_key, -1, 1) != '-' && $num != 0 && $myid[0] && $myid[0]>0) {
								dol_syslog("MyCyberoffice_Trigger_MyProduct::maj import_key ok".$sql);
								$db->begin();
								$reqsql = $db->query($sql);
								//dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
								$db->commit();
								//dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
							}
						}
						if($conf->global->MYCYBEROFFICE_debug==1) {
							print '<pre>';
							print_r($result0);
				    		print_r($result );
				    		print_r($fault);
				    		print_r($myparam);
				    		print_r($object);
				    		print '</pre>';
				    		exit;
			    		}
			    		//dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
			    		//print '<pre>';print_r($obj);print '</pre>';
			    	}
		    	}
		    	//exit;
		    	/*print'***';
		    	print '<pre>';print_r($resql);print '</pre>';
				die();*/
		    	//dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
		    }
		    //dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
	    }	//fin test service
	    //$db->close();
	    //dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
	}
	
	function MyCategory($cats, $action)
	{
		//print_r($cats);die();
    	global $conf, $db;
    	dol_syslog("MyCyberoffice_Trigger_MyCategory::id=".$cats[0]['id']);
		$numshop = substr($cats[0]['parent'],1,2);
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'const 
		WHERE name LIKE "CYBEROFFICE_SHOP%" AND value = "'.$numshop.'"
		ORDER BY name';

		$resql = $db->query($sql);

		if ($resql) {
			while ($obj = $db->fetch_object($resql))
			{

				$myglobalkey = "MYCYBEROFFICE_key" . substr($obj->name,-2);
				$myglobalshop = "MYCYBEROFFICE_shop" . substr($obj->name,-2);
				$mygloballang = "MYCYBEROFFICE_lang" . substr($obj->name,-2);
					
				dol_syslog("MyCyberoffice_Trigger_MyCategory::shop=".$myglobalshop.'::'.$obj->note);
					
				$ws_dol_url = $obj->note.'modules/mycyberoffice/server_category_soap.php';
				$ws_method  = 'Create';
				$ns = 'http://www.lvsinformatique.com/ns/';
					
				$options = array('location' => $obj->note.'modules/mycyberoffice/server_category_soap.php',
					                  'uri' => $obj->note);
				$soapclient = new SoapClient(NULL,$options);
							
				$authentication = array(
					'dolibarrkey'=>htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8'),
					'sourceapplication'	=> 'LVSInformatique',
					'login'				=> '',
					'password'			=> '',
					'shop'				=> $conf->global->$myglobalshop,
					'lang' 				=> $conf->global->$mygloballang,
					'myurl'				=> $_SERVER["PHP_SELF"]
				);
				$myparam = $cats;
							
				try 
				{
					$result0 = $soapclient->create($authentication, $myparam, $ns, '');
				}
				catch(SoapFault $fault)
				{
					if($fault->faultstring != 'Could not connect to host')
					{
						$result = array(
							'result'=>array('result_code' => 'KO', 'result_label' => 'KO'),
							'repertoire' => $obj->note,
							'repertoireTF' => 'KO',
							'webservice' => 'KO',
							'dolicyber' => 'KO',
							'lang' => $conf->global->$mygloballang,
							'Result' => $result0
						);
					}
				}
					      
				if (! $result0)
				{
					$result = array(
						'result'=>array('result_code' => 'KO', 'result_label' => 'KO'),
						'repertoire' => $obj->note,
						'repertoireTF' => 'KO',
						'webservice' => 'KO',
						'dolicyber' => 'KO',
						'lang' => $conf->global->$mygloballang,
						'Result' => $result0
					);
				}
				if ($result0['result']['result_label'] != 'NOK' && $result0['result']['result_label'] != 'OK' && $result0['result']['result_label'] != 'KO')
				{
					$num = $obj->value;
					$myid = explode(":", $result0['result']['result_label']);
					$import_key = 'P'.$num.'-'.$myid[0];
					$sql = "UPDATE ".MAIN_DB_PREFIX."categorie SET";
					$sql .= " import_key='".$import_key."'";
					$sql.= " WHERE rowid=".$cats[0]['id'];
					dol_syslog("MyCyberoffice_Trigger_MyCategory::avant maj import_key=".$import_key.'/'.$result0['result']['result_label']);
					if (substr($import_key, -1, 1) != '-' && $num != 0 && $myid[0] && $myid[0]>0)
					{
						dol_syslog("MyCyberoffice_Trigger_MyCategory::maj import_key ok".$sql);
						$db->begin();
						$reqsql = $db->query($sql);
						$db->commit();
					}
				}
				if($conf->global->MYCYBEROFFICE_debug==1) {
					print '<pre>';
					print_r($result0);
					print_r($result );
					print_r($fault);
					print_r($myparam);
					print_r($object);
					print '</pre>';
					exit;
				}
				$result='';
				$result0='';
			}
		}//if
	}

}
?>

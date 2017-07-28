<?php
/* CyberOffice
*  @author 		LVSinformatique <contact@lvsinformatique.com>
*  @copyright  	2014 LVSInformatique
*  @version   	1.2.37
*/

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cyberoffice/class/nusoap/lib/nusoap.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/cyberoffice/class/cyberoffice.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
dol_syslog("Call Dolibarr webservices interfaces::ServerOrder");
//set_time_limit(3600);
@ini_set('default_socket_timeout', 160);
$langs->load("main");
$langs->load("cyberoffice");
// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.lvsinformatique.com/ns/';
$server->configureWSDL('WebServicesDolibarrOrder',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
    	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
    	'login' => array('name'=>'login','type'=>'xsd:string'),
        'password' => array('name'=>'password','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
        'myurl' => array('name'=>'myurl','type'=>'xsd:string')
    )
);
// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string')
    )
);

// Define specific object
$server->wsdl->addComplexType(
    'params',
    'complexType',
    'struct',
    'all',
    '',
	array(
		'id_order' 					=> array('name'=>'id_order','type'=>'xsd:string'),
		'id_carrier' 				=> array('name'=>'id_carrier','type'=>'xsd:string'),
		'id_customer' 				=> array('name'=>'id_customer','type'=>'xsd:string'),
		'id_cart' 					=> array('name'=>'id_cart','type'=>'xsd:string'),
		'id_shop' 					=> array('name'=>'id_shop','type'=>'xsd:string'),
		'company' 					=> array('name'=>'company','type'=>'xsd:string'), 
		'lastname' 					=> array('name'=>'lastname','type'=>'xsd:string'), 
		'firstname' 				=> array('name'=>'firstname','type'=>'xsd:string'), 
		'address1'					=> array('name'=>'address1','type'=>'xsd:string'),
		'postcode' 					=> array('name'=>'postcode','type'=>'xsd:string'), 
		'city' 						=> array('name'=>'city','type'=>'xsd:string'), 
		'id_country' 				=> array('name'=>'id_country','type'=>'xsd:string'),
		'payment' 					=> array('name'=>'payment','type'=>'xsd:string'),
		'module' 					=> array('name'=>'module','type'=>'xsd:string'),
		'total_discounts' 			=> array('name'=>'total_discounts','type'=>'xsd:string'),
		'total_paid' 				=> array('name'=>'total_paid','type'=>'xsd:string'), 
		'total_products' 			=> array('name'=>'total_products','type'=>'xsd:string'),  
		'total_products_wt' 		=> array('name'=>'total_products_wt','type'=>'xsd:string'),  
		'total_shipping' 			=> array('name'=>'total_shipping','type'=>'xsd:string'),  
		'invoice_number' 			=> array('name'=>'invoice_number','type'=>'xsd:string'), 
		'invoice_date' 				=> array('name'=>'invoice_date','type'=>'xsd:string'), 
		'delivery_date' 			=> array('name'=>'delivery_date','type'=>'xsd:string'),
		'id_address_invoice'		=> array('name'=>'id_address_invoice','type'=>'xsd:string'),
		'id_address_delivery'		=> array('name'=>'id_address_delivery','type'=>'xsd:string'),
		'valid' 					=> array('name'=>'valid','type'=>'xsd:string'), 
		'date_add' 					=> array('name'=>'date_add','type'=>'xsd:string'),
		'warehouse' 				=> array('name'=>'warehouse','type'=>'xsd:string'),
		'lines' 					=> array('name'=>'lines','type'=>'xsd:string'),
		'match'						=> array('name'=>'match','type'=>'xsd:string'),
		'current_shop'				=> array('name'=>'current_shop','type'=>'xsd:string'),
		'commOK'					=> array('name'=>'commOK','type'=>'xsd:string'),
		'factOK'					=> array('name'=>'factOK','type'=>'xsd:string')
		)
);

// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped

// Register WSDL
$server->register(
    'Create',
    // Entry values
    array('authentication'=>'tns:authentication','params'=>'tns:params'),
    // Exit values
    array('result'=>'tns:result','description'=>'xsd:string'),
    $ns,
    $ns.'#Create',
    $styledoc,
    $styleuse,
    'WS to Create Order'
);

function Create($authentication,$params)
{
    global $db,$conf,$langs;
    
    $now=dol_now();

    dol_syslog("Function: create login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    $error=0;
dol_syslog("CyberOffice_server_order::line=".__LINE__);
        include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
        
        $user = new User($db);
        $user->fetch('', $authentication['login'],'',0);
		$user->getrights();

        $cyber = new Cyberoffice;
		$cyber->entity = 0;
		$cyber->myurl = $authentication['myurl'];
		$indice = $cyber->numShop();
		   
        $db->begin();
        //echo "<pre>".print_r($params)."</pre>";die();
        dol_syslog("CyberOffice_server_order::number of orders=".count($params));
        $socid_old=0;
        foreach ($params as $commande)
		{
			$cyber_current = new Cyberoffice;
			$cyber_current->entity = 0;
			$cyber_current->myurl = $commande['current_shop'];
			$indice_current = $cyber_current->numShop();

			dol_syslog("CyberOffice_server_order::traitement order=".$commande['id_order']);
			$newobject=new Commande($db);
					/*****recherche de la correspondance
					************************************/
			$invoiced=0;
			$commOK_0=0;
			$sql = "SELECT rowid,facture,ref";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande";
			$sql.= " WHERE import_key = 'P".$indice_current."-".$commande['id_order']."'";
			dol_syslog("CyberOffice_server_order::fetch sql=".$sql);
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					$res = $db->fetch_array($resql);
					$commande_id=$res['rowid'];
					$invoiced=$res['facture'];
					$commOK_0=1;
				} else $commande_id=0;
			} else $commande_id=0;
			
			if ($invoiced==0) {
				$newobject->fetch($commande_id);
					//$newobject->deleteObjectLinked($commande_id, 'commande', '', 'facture');
					//	deleteObjectLinked($commande_id, 'commande', '', 'facture')
					//$facture = new Facture($db);
					//$result = $facture->fetch($commande_id);
					//$facture->fetch_thirdparty();
					//$facture->delete(0,0);
					
				$sql = "DELETE ".MAIN_DB_PREFIX."commandedet ";
				$sql.= " FROM ".MAIN_DB_PREFIX."commandedet";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande ON ".MAIN_DB_PREFIX."commandedet.fk_commande = ".MAIN_DB_PREFIX."commande.rowid";
				$sql.= " WHERE ".MAIN_DB_PREFIX."commande.import_key = 'P".$indice_current."-".$commande['id_order']."'";
				dol_syslog("CyberOffice_server_order::delete1 sql=".$sql);
				$resql = $db->query($sql);
				if($resql) {
					$sql = "DELETE ";
					$sql.= " FROM ".MAIN_DB_PREFIX."commande";
					$sql.= " WHERE ".MAIN_DB_PREFIX."commande.import_key = 'P".$indice_current."-".$commande['id_order']."'";
					dol_syslog("CyberOffice_server_order::delete2 sql=".$sql);
					$resql = $db->query($sql);
					$sql = "SELECT rowid,ref";
					$sql.= " FROM ".MAIN_DB_PREFIX."commande";
					$sql.= " WHERE import_key = 'P".$indice_current."-".$commande['id_order']."'";
						
					dol_syslog("CyberOffice_server_order::fetch sql=".$sql);
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$commande_id=$res['rowid'];
						} else $commande_id=0;
					} else $commande_id=0;
						
						/*****creation
						**************/
					$newobject=new Commande($db);
					$sql = "SELECT rowid";
					$sql.= " FROM ".MAIN_DB_PREFIX."societe";
					$sql.= " WHERE import_key = 'P".$indice."-".$commande['id_customer']."'";
					dol_syslog("CyberOffice_server_order::fetch sql=".$sql);
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$customer_id=$res['rowid'];
						} else $customer_id=0;
					} else $customer_id=0;
					if ($customer_id==0) {
						$list_ok.="<br/>client " . $commande['id_customer'] . " inexistant : commande : ".$commande['id_order'].'->'.$res['ref'];
						continue;
					}
					$newobject->socid 				= $customer_id;
						//$user->id
					$user = new User($db);
					$user->fetch('', $authentication['login'],'',0);
					$user->getrights();
				    $newobject->fk_project			= "null";
				    $newobject->date				= $commande['invoice_date'];
				    $newobject->demand_reason_id	= 1;//'SRC_INTE';
				        //$newobject->note_private		= htmlentities("eBoutique N°".$commande['id_order']."\n".$_SERVER['SERVER_NAME']."/index.php?tab=AdminOrders&amp;id_order=".$commande['id_order']."&amp;vieworder",ENT_QUOTES,'UTF-8');
				    $newobject->cond_reglement_id	= "null";
				    //// mode reglement
				    $cyberbank = 0;
				    $sql = 'SELECT c.id, c.code, c.libelle, c.cyberbank
							FROM '.MAIN_DB_PREFIX.'c_paiement as c
					 		WHERE c.libelle = "'.$db->escape($commande['payment']).'"';
						$resql = $db->query($sql);
						if ($resql) {
							if ($db->num_rows($resql) > 0) {
								$res 	= $db->fetch_array($resql);
								$accountid = $res['id'];
								$cyberbank = $res['cyberbank'];
							} else $accountid =0;
						} else $accountid =0;
					if ($accountid==0) {
						$sql = 'SELECT max(id) newid from '.MAIN_DB_PREFIX.'c_paiement ';
			            $resql= $db->query($sql);
			            if ($resql)
			            {
			                $obj = $db->fetch_object($resql);
			                $newid=($obj->newid + 1);
			            }
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'c_paiement (id,code,libelle,type)
					 		VALUES ('.$newid.',"P'.$commande['id_order'].'", "'.$db->escape($commande['payment']).'", 2)';
						$resql = $db->query($sql);
						if ($resql)
							$accountid = $newid;
					}
					dol_syslog("CyberOffice_server_order::paiement ".$accountid." sql=".$sql);
				    $newobject->mode_reglement_id	= ($accountid > 0? $accountid:"null");
				    $newobject->date_livraison		= $commande['invoice_date'];
				    $newobject->model				= $conf->global->COMMANDE_ADDON_PDF;
				    $newobject->ref					= '';
				    if($commande['id_shop'] && $commande['id_shop'] > 0)
				    	$newobject->ref_client = $commande['id_shop']  . ':' . $commande['id_order'] . '/' . $commande['id_cart'];
				    else
				    	$newobject->ref_client = $commande['id_order'] . '/' . $commande['id_cart'];
				    $line=array();
					if ($commande['lines']) {
						$i=0;
						foreach ($commande['lines'] as $line) {
							$sql = "SELECT rowid";
							$sql.= " FROM ".MAIN_DB_PREFIX."product";
							if ($commande['match'] == '{ref}')
								$sql.= " WHERE ref = '".$line['reference']."'";
							else
								$sql.= " WHERE import_key = 'P".$indice."-".$line['fk_product']."'";
							dol_syslog("CyberOffice_server_order::fetch sql=".$sql);
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res = $db->fetch_array($resql);
									$product_id=$res['rowid'];
								} else $product_id=0;
							} else $product_id=0;
							//$newobject->lines 						= array();
							$newobject->lines[$i]->desc				= $line['desc'];                   
							$newobject->lines[$i]->subprice			= $line['subprice'];
	                        $newobject->lines[$i]->qty				= $line['qty'];
	                        $newobject->lines[$i]->tva_tx			= $line['tva_tx'];
	                        $newobject->lines[$i]->fk_product		= $product_id;                   
	                        $newobject->lines[$i]->remise_percent	= $line['remise_percent'];
	                        $newobject->lines[$i]->product_type		= 0;
	                        $newobject->lines[$i]->label			= $line['label'];
	                        $test_tva = $line['tva_tx'];
							$i++;
							dol_syslog("CyberOffice_server_order::line =".$i);
						}
					}
						//shipping
					if ($commande['total_shipping']>0) {
						$sql = "SELECT tva_tx,description,label";
						$sql.= " FROM ".MAIN_DB_PREFIX."product";
						$sql.= " WHERE rowid = ".$conf->global->CYBEROFFICE_SHIPPING;
						$resql = $db->query($sql);
						if ($resql) {
							if ($db->num_rows($resql) > 0) {
								$res 	= $db->fetch_array($resql);
								$tva_tx	= ($test_tva == 0 ? 0 : $res['tva_tx']);
								$desc	= $res['description'];
								$label	= $res['label'];
							} else {$tva_tx=0;$desc='shipping';$label='shipping';}
						} else {$tva_tx=0;$desc='shipping';$label='shipping';}
						//$newobject->lines 						= array();
						$newobject->lines[$i]->desc				= $desc;                   
						$newobject->lines[$i]->subprice			= round($commande['total_shipping']/(1 + $tva_tx/100),$conf->global->MAIN_MAX_DECIMALS_UNIT);
	                    $newobject->lines[$i]->qty				= 1;
	                    $newobject->lines[$i]->tva_tx			= $tva_tx;
	                    $newobject->lines[$i]->fk_product		= $conf->global->CYBEROFFICE_SHIPPING;                  
	                        //$newobject->lines[$i]->remise_percent	= $line['remise_percent'];
	                    $newobject->lines[$i]->product_type		= 1;
	                    $newobject->lines[$i]->label			= $label;   
	                    $i++;
	                    dol_syslog("CyberOffice_server_order::line =shipping ".$i);
					}
						//discount
					if ($commande['total_discounts']>0) {
						$sql = "SELECT tva_tx,description,label";
						$sql.= " FROM ".MAIN_DB_PREFIX."product";
						$sql.= " WHERE rowid = ".$conf->global->CYBEROFFICE_DISCOUNT;
						$resql = $db->query($sql);
						if ($resql) {
							if ($db->num_rows($resql) > 0) {
								$res 	= $db->fetch_array($resql);
								$tva_tx	= ($test_tva == 0 ? 0 : $res['tva_tx']);//$res['tva_tx'];
								$desc	= $res['description'];
								$label	= $res['label'];
							} else {$tva_tx=0;$desc='discount';$label='discount';}
						} else {$tva_tx=0;$desc='discount';$label='discount';}
						//$newobject->lines 						= array();
						$newobject->lines[$i]->desc				= $desc;                   
						$newobject->lines[$i]->subprice			= round($commande['total_discounts']/(1 + $tva_tx/100),$conf->global->MAIN_MAX_DECIMALS_UNIT);
	                    $newobject->lines[$i]->qty				= -1;
	                    $newobject->lines[$i]->tva_tx			= $tva_tx;
	                    $newobject->lines[$i]->fk_product		= $conf->global->CYBEROFFICE_DISCOUNT;                  
	                        //$newobject->lines[$i]->remise_percent	= $line['remise_percent'];
	                    $newobject->lines[$i]->product_type		= 1;
	                    $newobject->lines[$i]->label			= $label;   
	                    $i++;
	                    dol_syslog("CyberOffice_server_order::line =discount ".$i);
					}
	
					$newobject->socid = $customer_id;
					$newobject->fetch_thirdparty();
						        							
				    $result = -9;
				    if ($i>0) $result = $newobject->create($user);
				    if ($result < 0) {
				        dol_syslog("server_order::erreur creation=".$commande['id_order'].' errorcode = '.$result);
				        $list_ok.="<br/>Order error : ".$commande['id_order'];
				        continue;
				    }
				    $newobject->fetch($result);
				    $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";
					$sql .= " import_key='P".$indice_current."-".$commande['id_order']."'";
					$sql.= " WHERE rowid=".$result;
					dol_syslog("server_order::update key - sql=".$sql);
					$resql = $db->query($sql);
						/*
						$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
						$sql .= " import_key='P".$indice."-".$commande['id_order']."'";
						$sql.= " WHERE fk_commande=".$result;
						dol_syslog("server_order::update key - sql=".$sql);
						$resql = $db->query($sql);
						*/
						//$user->getrights();
					$newobject->id = $result;
					$newobject->socid = $customer_id;
					$idwarehouse = $commande['warehouse'];//$line['warehouse'];
					$result_valid = $newobject->valid($user, $idwarehouse);
					dol_syslog("Cyberoffice::order validation " . $result_valid);
					
					/* ajout adresse livraison
					**************************/
					$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'socpeople WHERE import_key = "P'.$indice.'-'.$commande['id_address_delivery'].'"';
						$resql = $db->query($sql);
						if ($resql) {
							if ($db->num_rows($resql) > 0) {
								$res = $db->fetch_array($resql);
								$contact_ship=$res['rowid'];
							} else $contact_ship=0;
						} else $contact_ship=0;
					if($contact_ship > 0) $newobject->add_contact($contact_ship, 'SHIPPING', 'external');
					
					/* ajout adresse facturation
					****************************/
					$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'socpeople WHERE import_key = "P'.$indice.'-'.$commande['id_address_invoice'].'"';
						$resql = $db->query($sql);
						if ($resql) {
							if ($db->num_rows($resql) > 0) {
								$res = $db->fetch_array($resql);
								$contact_ship=$res['rowid'];
							} else $contact_ship=0;
						} else $contact_ship=0;
					if($contact_ship > 0) $newobject->add_contact($contact_ship, 'BILLING', 'external');
	
					
					if ($result_valid >= 0) {
						/* Decrease Stock
						*****************/
						if ($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1 || $conf->global->STOCK_CALCULATE_ON_BILL == 1 || $conf->global->STOCK_CALCULATE_ON_SHIPMENT == 1) $DecreaseYesNo = 0;
						else $DecreaseYesNo = 1;
	
						if ($conf->global->CYBEROFFICE_stock==1 && $DecreaseYesNo == 1) {
							require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
							$langs->load("cyberoffice@cyberoffice");
							// Loop on each line
							$cpt=count($newobject->lines);
							for ($i = 0; $i < $cpt; $i++)
							{
								if ($newobject->lines[$i]->fk_product > 0)
								{
									$mouvP = new MouvementStock($db);
									$mouvP->origin = &$newobject;
									// We decrease stock for product
									$resultStock=$mouvP->livraison($user, $newobject->lines[$i]->fk_product, $idwarehouse, $newobject->lines[$i]->qty, $newobject->lines[$i]->subprice, $langs->trans("OrderValidatedInDolibarrFromCyberoffice",$newobject->newref));
									dol_syslog("Cyberoffice::Decrease Stock on warehouse " . $idwarehouse . " product=" . $newobject->lines[$i]->fk_product);									}
							}
						}
						$outputlangs=new Translate("",$conf);
						if (isset($conf->MAIN_LANG_DEFAULT) && $conf->MAIN_LANG_DEFAULT != 'auto')
						{	// If user has defined its own language (rare because in most cases, auto is used)
							$outputlangs->getDefaultLang($conf->MAIN_LANG_DEFAULT);
						}
						else
						{	// If user has not defined its own language, we used current language
							$outputlangs=$langs;
						}
	
								//$outputlangs = new Translate("", $conf);//MAIN_LANG_DEFAULT
								//$outputlangs->setDefaultLang('');
						if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
							$ret = $newobject->fetch($result); // Reload to get new records
							commande_pdf_create($db, $newobject, $newobject->model, $outputlangs, $hidedetails, $hidedesc, $hideref);
						}
					}
						/* facture
						**********/
					//if ($conf->global->CYBEROFFICE_invoice==1) { 
					if($commande['factOK']==1) {					
						$facture = new Facture($db);
						//$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
						$dateinvoice = $commande['invoice_date'];
						$facture->socid				= $customer_id;
						$facture->type           	= 0;
						$facture->mode_reglement_id	= $newobject->mode_reglement_id;
						$facture->date           	= $dateinvoice;
							//$facture->date_lim_reglement= $dateinvoice;
							//$object->note_public	= trim($_POST['note_public']);
							//$object->note_private   = trim($_POST['note_private']);
						if($commande['id_shop'] && $commande['id_shop'] > 0)
				    		$facture->ref_client = $commande['id_shop']  . ':' . $commande['id_order'] . '/' . $commande['id_cart'];
				    	else
				    		$facture->ref_client = $commande['id_order'] . '/' . $commande['id_cart'];

							//$facture->ref_int     		= $_POST['ref_int'];
						$facture->modelpdf       	= $conf->global->FACTURE_ADDON_PDF;
						$facture->origin			= 'commande';
						$facture->originid			= $newobject->id;
							//$facture->fac_rec 			= $_POST['fac_rec'];
							//$facture->amount			=
							//$idFacture = $facture->create($user);
	///////////////////////
						$facture->fetch_thirdparty();
						$element = $subelement = $facture->origin;
						if (preg_match('/^([^_]+)_([^_]+)/i', $facture->origin, $regs)) {
							$element = $regs [1];
							$subelement = $regs [2];
						}
							// For compatibility
						if ($element == 'order') {
							$element = $subelement = 'commande';
						}
	
						$facture->origin			= $element;
						$facture->origin_id			= $newobject->id;
							
						$facture->linked_objects [$facture->origin] = $facture->origin_id;
						$idFacture = $facture->create($user);
	
						if ($idFacture > 0)
						{
							dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');
	
							$classname = ucfirst($subelement);
							$srcobject = new $classname($db);
	
							dol_syslog("Cyberoffice::Try to find source object origin=" . $facture->origin . " originid=" . $facture->origin_id . " to add lines");
							$resultF = $srcobject->fetch($facture->origin_id);
							if ($resultF > 0)
							{
								$lignes = $srcobject->lines;
								if (empty($lignes) && method_exists($srcobject, 'fetch_lines')) $lignes = $srcobject->fetch_lines();
	
								$fk_parent_line=0;
								$num=count($lignes);
								for ($i=0;$i<$num;$i++)
								{
									$label=(! empty($lignes[$i]->label)?$lignes[$i]->label:'');
									$desc=(! empty($lignes[$i]->desc)?$lignes[$i]->desc:$lignes[$i]->libelle);
	
									if ($lignes [$i]->subprice < 0)
									{
											// Negative line, we create a discount line
										$discount = new DiscountAbsolute($db);
										$discount->fk_soc = $facture->socid;
										$discount->amount_ht = abs($lignes [$i]->total_ht);
										$discount->amount_tva = abs($lignes [$i]->total_tva);
										$discount->amount_ttc = abs($lignes [$i]->total_ttc);
										$discount->tva_tx = $lignes [$i]->tva_tx;
										$discount->fk_user = $user->id;
										$discount->description = $desc;
										$discountid = $discount->create($user);
										if ($discountid > 0) {
											$resultL = $facture->insert_discount($discountid); // This include link_to_invoice
										} else {
											$mesgs [] = $discount->error;
												//$error ++;
											break;
										}
									} else {
											// Positive line
										$product_type = ($lignes [$i]->product_type ? $lignes [$i]->product_type : 0);
	
											// Date start
										$date_start = false;
										if ($lignes [$i]->date_debut_prevue)
											$date_start = $lignes [$i]->date_debut_prevue;
										if ($lignes [$i]->date_debut_reel)
											$date_start = $lignes [$i]->date_debut_reel;
										if ($lignes [$i]->date_start)
											$date_start = $lignes [$i]->date_start;
	
											// Date end
										$date_end = false;
										if ($lignes [$i]->date_fin_prevue)
											$date_end = $lignes [$i]->date_fin_prevue;
										if ($lignes [$i]->date_fin_reel)
											$date_end = $lignes [$i]->date_fin_reel;
										if ($lignes [$i]->date_end)
											$date_end = $lignes [$i]->date_end;
	
											// Reset fk_parent_line for no child products and special product
										if (($lignes [$i]->product_type != 9 && empty($lignes [$i]->fk_parent_line)) || $lignes [$i]->product_type == 9) {
											$fk_parent_line = 0;
										}
	
										// voir /core/class/commonobject/update_price(1,'auto',0,$mysoc)
										$resultL= $facture->addline($desc, $lignes [$i]->subprice, $lignes [$i]->qty, $lignes [$i]->tva_tx, $lignes [$i]->localtax1_tx, $lignes [$i]->localtax2_tx, $lignes [$i]->fk_product, $lignes [$i]->remise_percent, $date_start, $date_end, 0, $lignes [$i]->info_bits, $lignes [$i]->fk_remise_except, 'HT', 0, $product_type, $lignes [$i]->rang, $lignes [$i]->special_code, $facture->origin, $lignes [$i]->rowid, $fk_parent_line, $lignes [$i]->fk_fournprice, $lignes [$i]->pa_ht, $label, $array_option);
	/*									
	///////// Insert line
				$lineInsert =					new FactureLigne($db);
				//$lineInsert->context = 			$facture->context;
				$lineInsert->fk_facture=		$facture->id;
				//$lineInsert->label=				$label;	// deprecated
				$lineInsert->desc=				$desc;
				$lineInsert->qty=				$lignes [$i]->qty;
				$lineInsert->tva_tx=			$lignes [$i]->tva_tx;
				$lineInsert->localtax1_tx=		$lignes [$i]->localtax1_tx;
				$lineInsert->localtax2_tx=		$lignes [$i]->localtax2_tx;
				$lineInsert->fk_product=		$lignes [$i]->fk_product;
				$lineInsert->product_type=		$product_type;
				$lineInsert->remise_percent=	$lignes [$i]->remise_percent;
				$lineInsert->subprice=       	$lignes [$i]->subprice;
				$lineInsert->date_start=		$date_start;
				$lineInsert->date_end=			$date_end;
				//$lineInsert->ventil=			$ventil;
				$lineInsert->rang=				$lignes [$i]->rang;
				$lineInsert->info_bits=			$lignes [$i]->info_bits;
				$lineInsert->fk_remise_except=	$lignes [$i]->fk_remise_except;
				$lineInsert->total_ht=       	$lignes [$i]->total_ht;
				$lineInsert->total_tva=      	$lignes [$i]->total_tva;
				$lineInsert->total_localtax1=	$lignes [$i]->total_localtax1;
				$lineInsert->total_localtax2=	$lignes [$i]->total_localtax2;
				$lineInsert->total_ttc=      	$lignes [$i]->total_ttc;
				//$lineInsert->special_code=		$lignes [$i]->special_code;
				//$lineInsert->fk_parent_line=	$fk_parent_line;
				//$lineInsert->origin=			$origin;
				//$lineInsert->origin_id=			$origin_id;
				$result_lineInsert=$lineInsert->insert();
				$resultL=$result_lineInsert;
	////////////////////
	*/
										if ($resultL> 0) {
											$lineid = $result;
										} 
											// Defined the new fk_parent_line
										if ($resultL> 0 && $lignes [$i]->product_type == 9) {
											$fk_parent_line = $result;
										}
									}
								}//fin for
							} 
						}
						$facture->fetch($idFacture);
						$facture->cond_reglement_code = 0; // To clean property
						$facture->cond_reglement_id = 0; // To clean property
						$facture->date_lim_reglement = $dateinvoice;
						$resultR = $facture->update($user);
						if (DOL_VERSION < '4.0.0')
							$ret=$newobject->classifyBilled();
						else $ret=$newobject->classifyBilled($user);
						if ($commande['valid']== 4 || $commande['valid']== 5)
							$ret=$newobject->cloture($user);//que sur le statut en cours livraison & livré
						$facture->fetch_thirdparty();
						$resultF = $facture->validate($user);
							//$result = $facture->set_paid($user);
						$outputlangs = $langs;
						$newlang = $facture->client->default_lang;
						if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
							$ret = $facture->fetch($idFacture); // Reload to get new records
							facture_pdf_create($db, $facture, $facture->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
						}
							/*payment
							*********/
							/*
							if ($commande['module'] == 'cheque') {
								$sql = "SELECT ba.rowid FROM ".MAIN_DB_PREFIX."bank_account as ba WHERE ba.code='".$db->escape($commande['payment'])."'";
								$resql = $db->query($sql);
								if ($resql) {
									if ($db->num_rows($resql) > 0) {
										$res 	= $db->fetch_array($resql);
										$accountid = $res['rowid'];
									} else $accountid =0;
								} else $accountid =0;
	
							} else {
							*/
						//$sql = "SELECT ba.rowid FROM ".MAIN_DB_PREFIX."bank_account as ba WHERE ba.label='".$db->escape($commande['payment'])."'";
					if (empty($conf->global->CYBEROFFICE_nopayment) || $commande['valid'] != $conf->global->CYBEROFFICE_nopayment)	{
						if ($cyberbank > 0) 
							$test=1;//$accountid = $cyberbank;
						else {
							$sql = "SELECT ba.rowid FROM ".MAIN_DB_PREFIX."bank_account as ba WHERE ba.ref='".substr($db->escape($commande['module']),0,12)."'";
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res 	= $db->fetch_array($resql);
									$cyberbank= $res['rowid'];
								} else $cyberbank=0;
							} else $cyberbank=0;
							if ($cyberbank== 0) {
								$account = new Account($db,0);
								$account->comment		= 'PrestaShop';
								$account->ref			= $commande['module'];//$commande['payment'];
								$account->label			= $commande['module'];//$commande['payment'];
								$account->date_solde	= dol_now();
								$account->courant		= 1;
								$account->currency_code	= $conf->global->MAIN_MONNAIE;
								$country_id = explode( ':' , $conf->global->MAIN_INFO_SOCIETE_COUNTRY );
								$account->country_id	= $country_id[0];//1:FR:France
								if (DOL_VERSION < '4.0.0')
									$cyberbank= $account->create($user->id);
								else
									$cyberbank= $account->create($user);
							}
						}
						$user = new User($db);
						$user->fetch('', $authentication['login'],'',0);
						$user->getrights();
						$paiement = new Paiement($db);
						$paiement->datepaye     = $dateinvoice;
						$paiement->amounts      = array($idFacture => $commande['total_paid']);
						$paiement->amount       = array($idFacture => $commande['total_paid']);
						$paiement->paiementid   = $accountid;//($commande['module'] == 'cheque'?dol_getIdFromCode($db,'CHQ','c_paiement'):dol_getIdFromCode($db,'VAD','c_paiement'));
						$paiement->num_paiement = $commande['id_order'];
								//$paiement->note         = $_POST['comment'];
								//$account->fetch(0,$commande['payment']);
						$paiement_id = $paiement->create($user, 1);
						$label='(CustomerInvoicePayment)';
						$resultP=$paiement->addPaymentToBank($user,'payment',$label,$cyberbank,$commande['firstname'].''.$commande['lastname'],'');//27968
					}//fin paiement	
				}
					if ($result> 0) $list_ok.="<br/>Create Order : ".$commande['id_order'].'->'.$result." (".$commande['invoice_date'].")";
				}//fin resql delete
			}//fin invoiced
			else $list_ok.="<br/>Order exist : ".$commande['id_order'].'->'.$res['ref'];
			
				$sql = "SELECT rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."commande";
				$sql.= " WHERE import_key = 'P".$indice_current."-".$commande['id_order']."'";
				dol_syslog("CyberOffice_server_order::fetch".__LINE__." sql=".$sql);
				$resql = $db->query($sql);
				if ($resql) {
					if ($db->num_rows($resql) > 0) {
						$res = $db->fetch_array($resql);
						$commande_id=$res['rowid'];
					} else $commande_id=0;
				} else $commande_id=0;
			
				if ($commande_id>0) {
					$newobject=new Commande($db);
					$newobject->fetch($commande_id);
					$user = new User($db);
					$user->fetch('', $authentication['login'],'',0);
					$user->getrights();
					if($commande['commOK']==0 && $commOK_0==0) $newobject->delete($user, 1);
					else {
						//$db->commit();
						//$db->begin();
						dol_syslog("CyberOffice_server_order::".__LINE__. " : ".$commande['valid']);
						if ($commande['valid']== 4)//expédié
									$ret = $newobject->setStatut(2);
						if ($commande['valid']== 5)//livré
									$ret=$newobject->cloture($user);//que sur le statut en cours livraison & livré
					}
				}
		}  //fin foreach
		
  

        if (! $error)
        {
            $db->commit();
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'description'=>$list_ok);
        }
        else
        {
            $db->rollback();
            $error++;
            $errorcode='KO';
            $errorlabel=$newobject->error;
        }
	
    	if ($error)
    	{
        	$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'description'=>$list_ko);
    	}
		return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
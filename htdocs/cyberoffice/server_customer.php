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
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/cyberoffice/class/cyberoffice.class.php';
dol_syslog("Call Dolibarr webservices interfaces::ServerCustomer");

$langs->load("main");

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
$server->configureWSDL('WebServicesDolibarrCustomer',$ns);
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
	    'id_customer'	=> array('name'=>'id_customer','type'=>'xsd:string'),
		'email' 		=> array('name'=>'email','type'=>'xsd:string'),
		'birthday' 		=> array('name'=>'birthday','type'=>'xsd:string'),
		'id_gender' 	=> array('name'=>'id_gender','type'=>'xsd:string'),
		'firstname' 	=> array('name'=>'firstname','type'=>'xsd:string'),
		'lastname' 		=> array('name'=>'lastname','type'=>'xsd:string'), 
		'id_address' 	=> array('name'=>'id_address','type'=>'xsd:string'),
		'company' 		=> array('name'=>'company','type'=>'xsd:string'),
		'address'		=> array('name'=>'address','type'=>'xsd:string'),
		'postcode'		=> array('name'=>'postcode','type'=>'xsd:string'),
		'city'			=> array('name'=>'city','type'=>'xsd:string'),
		'phone'			=> array('name'=>'phone','type'=>'xsd:string'),
		'phone_mobile'	=> array('name'=>'phone_mobile','type'=>'xsd:string'),
		'vat_number'	=> array('name'=>'vat_number','type'=>'xsd:string'),
		'state_iso'		=> array('name'=>'state_iso','type'=>'xsd:string'),
		'c_iso_code'	=> array('name'=>'c_iso_code','type'=>'xsd:string'),
		'client'		=> array('name'=>'client','type'=>'xsd:string')
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
    'WS to Create Customer'
);
function Create($authentication,$params)
{
    global $db,$conf,$langs;
//echo "<pre>".print_r($params)."</pre>";die();
    $now=dol_now();

    dol_syslog("Function: create login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    $error=0;
dol_syslog("CyberOffice_server_customer::line=".__LINE__);
        include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
        // raz de la base
        //$sql = "DELETE FROM llx_categorie WHERE import_key IS NOT NULL";
        //$resql = $db->query($sql);
        
        $user = new User($db);
        $user->fetch('', $authentication['login'],'',0);
		$user->getrights();

        $cyber = new Cyberoffice;
		$cyber->entity = 0;
		$cyber->myurl = $authentication['myurl'];
		$indice = $cyber->numShop();
/*
        $objectcat=new Categorie($db);
        $catparent0 = array();
        if (DOL_VERSION < '3.8.0')
			$catparent0 = $objectcat->rechercher(null,$cyber->myurl,2);
		else
			$catparent0 = $objectcat->rechercher(null,$cyber->myurl,'customer');
        foreach ($catparent0 as $cat_parent0)
		{
			$idparent0 = $cat_parent0->id;
		}
*/

        $newobject=new Societe($db);
        $db->begin();
        //echo "<pre>".print_r($params)."</pre>";die();
        $socid_old=0;
        foreach ($params as $customer)
		{
			$list_ok.= '<br/>Traitement : '.$customer['id_customer'];
			//echo "<pre>".print_r($customer)."</pre>";die();
			//recherche de la correspondance
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe";
			$sql.= " WHERE import_key = 'P".$indice."-".$customer['id_customer']."'";
			dol_syslog("CyberOffice_server_customer::fetch sql=".$sql);
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					$res = $db->fetch_array($resql);
					$res_rowid=$res['rowid'];
				} else $res_rowid=0;
			} else $res_rowid=0;
			//creation
			$newobject->status			= 1;
			$newobject->name 			= ($customer['company']?$customer['company']:$customer['lastname'].' '.$customer['firstname']);
			$newobject->client			= ($customer['client']==2?2:1);//1=customer 2=prospect
			$newobject->fournisseur		= 0;
			$newobject->import_key		= "P".$indice."-".$customer['id_customer'];
			$newobject->email			= $customer['email'];
			$newobject->code_client 	= -1;
			$newobject->id_customer		= $customer['id_customer']; 
			$newobject->civility_id		= '';//$customer['id_gender'];  //1 homme, 2 femme 9 inconnu
			$newobject->firstname 		= $customer['firstname']; 
			$newobject->id_address 		= $customer['id_address']; 
			$newobject->company 		= $customer['company'];
			$newobject->address			= $customer['address']; 
			$newobject->zip				= $customer['postcode'];
			$newobject->town			= $customer['city'];
			$newobject->phone_pro 		= $customer['phone'];
			$newobject->phone	 		= $customer['phone_mobile'];
			$newobject->tva_intra		= $customer['vat_number'];
			if (DOL_VERSION < '3.7.0')
				$sql='SELECT rowid FROM '.MAIN_DB_PREFIX.'c_pays WHERE code LIKE "'.$customer['c_iso_code'].'"';
			else
				$sql='SELECT rowid FROM '.MAIN_DB_PREFIX.'c_country WHERE code LIKE "'.$customer['c_iso_code'].'"';
			dol_syslog("CyberOffice_server_customer::pays sql=".$sql);
		        $resql = $db->query($sql);
				if ($resql) {
					if ($db->num_rows($resql) > 0) {
						$res = $db->fetch_array($resql);
							$pays_rowid	=	$res['rowid'];
					} else 	$pays_rowid	=	0;
				} else 		$pays_rowid	=	0;
		    $newobject->country_id      = $pays_rowid;
			$newobject->commercial_id 	= -1;
			$user = new User($db);
			//$user = new User($db);
        	$user->fetch('', $authentication['login'],'',0);
        	$user->getrights();
			dol_syslog("CyberOffice_server_customer::".__LINE__);
			$result = $res_rowid;
			if ($res_rowid == 0) {
				$result = $newobject->create($user);
					dol_syslog("CyberOffice_server_customer::create ".__LINE__);
				if ($result > 0) $list_ok.="<br/>Create Tiers : ".$result. ' : ' .$newobject->name;
			} else {
				$sql = "SELECT rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."societe";
				$sql.= " WHERE import_key = 'P".$indice."-".$customer['id_customer']."'";
				dol_syslog("CyberOffice_server_customer::fetch sql=".$sql);
				$resql = $db->query($sql);
				if ($resql) {
					if ($db->num_rows($resql) > 0) {
						$res = $db->fetch_array($resql);
						$societe_id=$res['rowid'];
					} else $societe_id=0;
				} else $societe_id=0;
				dol_syslog("CyberOffice_server_customer::".__LINE__);
				if ($societe_id>0) {
					$newobject->fetch($societe_id);
					$newobject->status			= 1;
					$newobject->name 			= ($customer['company']?$customer['company']:$customer['lastname'].' '.$customer['firstname']);
					$newobject->client			= 1;
					$newobject->fournisseur		= 0;
					$newobject->import_key		= "P".$indice."-".$customer['id_customer'];
					$newobject->email			= $customer['email'];
					$newobject->code_client 	= -1;
					$newobject->id_customer		= $customer['id_customer']; 
					$newobject->civility_id		= '';//$customer['id_gender'];  //1 homme, 2 femme 9 inconnu
					$newobject->firstname 		= $customer['firstname']; 
					$newobject->id_address 		= $customer['id_address']; 
					$newobject->company 		= $customer['company'];
					$newobject->address			= $customer['address']; 
					$newobject->zip				= $customer['postcode'];
					$newobject->town			= $customer['city'];
					if($customer['phone']) $newobject->phone_pro = $customer['phone'];
					if($customer['phone_mobile']) $newobject->phone = $customer['phone_mobile'];
					$newobject->tva_intra		= $customer['vat_number'];
					if (DOL_VERSION < '3.7.0')
						$sql='SELECT rowid FROM '.MAIN_DB_PREFIX.'c_pays WHERE code LIKE "'.$customer['c_iso_code'].'"';
					else
						$sql='SELECT rowid FROM '.MAIN_DB_PREFIX.'c_country WHERE code LIKE "'.$customer['c_iso_code'].'"';
					dol_syslog("CyberOffice_server_customer::pays sql=".$sql);
				    $resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$pays_rowid	=	$res['rowid'];
						} else 	$pays_rowid	=	0;
					} else 		$pays_rowid	=	0;
				    $newobject->country_id  = $pays_rowid;
					dol_syslog("CyberOffice_server_customer::".__LINE__);
					/////
					//$db->commit();
					//$db->begin();
					$resultS_U=$newobject->update($societe_id,$user);
					dol_syslog("CyberOffice_server_customer::update".__LINE__);
				}
				if ($resultS_U> 0) $list_ok.="<br/>Update Tiers : ".$customer['id_customer'].'->'.$societe_id . ' : ' .$newobject->name;
			}
			//if (!$customer['company']) {
				$newobject->lastname        = $customer['lastname'];
				$newobject->name 		    = $customer['lastname'];
				$newobject->name_bis 		= $customer['lastname'];
		        $newobject->socid           = $result;
		        $newobject->id           	= $result;
		        $newobject->statut          = 1;
		        $newobject->priv            = 0;//0 = public, 1 = private
		        //$contact->state_id          = $this->state_id;
		        //*****recherche si existe
					$sql = "SELECT rowid";
					$sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
					$sql.= " WHERE import_key = 'P".$indice."-".$customer['id_address']."'";
					dol_syslog("CyberOffice_server_customer::search contact sql=".$sql);
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$contact_id=$res['rowid'];
						} else $contact_id=0;
					} else $contact_id=0;
		$resultC = $contact_id;
		if ($contact_id==0) {
				$socid_old=$newobject->socid;
				dol_syslog("CyberOffice_server_customer::individual ".__LINE__);
				$resultC = $newobject->create_individual($user);
				if ($resultC > 0) $list_ok.="<br/>Create Contact : ".$customer['id_address'].'->'.$resultC . ' : ' .$newobject->name;
		} else {
			dol_syslog("CyberOffice_server_customer::contact ".__LINE__);
			require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
        	$contact=new Contact($db);
        	$contact->fetch($contact_id);
			if($customer['lastname']) 		$contact->lastname		= $customer['lastname'];
			if($customer['firstname']) 		$contact->firstname 	= $customer['firstname'];
			if($customer['address']) 		$contact->address 		= $customer['address']; 
			if($customer['postcode']) 		$contact->zip 			= $customer['postcode'];
			if($customer['city']) 			$contact->town 			= $customer['city'];
			if($pays_rowid) 				$contact->country_id 	= $pays_rowid;
			if($customer['phone']) 			$contact->phone_pro 	= $customer['phone'];
			if($customer['phone_mobile']) 	$contact->phone_mobile 	= $customer['phone_mobile'];
			if($customer['email']) 			$contact->email			= $customer['email'];
			$contact->update($contact_id, $user);
			dol_syslog("CyberOffice_server_customer::updatecontact ".__LINE__);
		}		
				$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET";
				$sql.= " birthday=".($customer['birthday'] ? "'".$customer['birthday']."'" : "null");
				$sql .= ", import_key='P".$indice."-".$customer['id_address']."'";
				$sql.= " WHERE rowid=".$resultC;
				dol_syslog("server_customer::create - sql=".$sql);
				$resql = $db->query($sql);
				
			if ($result <= 0) {
				$error++;
				$list_ok.= '<br/>Erreur : '.$customer['id_customer'];
				$list_ref.= ' '.$newobject->label ;
			} 
		}    

        if (! $error)
        {
            $db->commit();
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'description'=>$list_ok);
        }
        else
        {
            $db->commit();
            $error++;
            $errorcode='KO';
            $errorlabel=$newobject->error;
        }
	
    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'description'=>$list_ok);
    }
	//$db->close();
    return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
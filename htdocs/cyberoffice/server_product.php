<?php
/**
 *	CyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2014 LVSInformatique
 *	@license   NoLicence
 *  @version   1.2.37
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
require_once DOL_DOCUMENT_ROOT.'/cyberoffice/class/cyberoffice.class.php';
dol_syslog("cyberoffice::Call Dolibarr webservices interfaces::ServerProduct_0");
//set_time_limit(3600);
@ini_set('default_socket_timeout', 160);
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
$server->configureWSDL('WebServicesDolibarrProduct',$ns);
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
		'id_product' 			=> array('name'=>'id_product','type'=>'xsd:string'),
		'ean13' 				=> array('name'=>'ean13','type'=>'xsd:string'),
		'upc' 					=> array('name'=>'upc','type'=>'xsd:string'),
		'isbn' 					=> array('name'=>'isbn','type'=>'xsd:string'),
		'price' 				=> array('name'=>'price','type'=>'xsd:string'),
		'width' 				=> array('name'=>'width','type'=>'xsd:string'),
		'weight' 				=> array('name'=>'weight','type'=>'xsd:string'),
		'description' 			=> array('name'=>'description','type'=>'xsd:string'),
		'description_short' 	=> array('name'=>'description_short','type'=>'xsd:string'),
		'name' 					=> array('name'=>'name','type'=>'xsd:string'),
		'tax_rate' 				=> array('name'=>'tax_rate','type'=>'xsd:string'),
		'reference'				=> array('name'=>'reference','type'=>'xsd:string'),
		'active'				=> array('name'=>'active','type'=>'xsd:string'),
		'quantity'				=> array('name'=>'quantity','type'=>'xsd:string'),
		'warehouse'				=> array('name'=>'warehouse','type'=>'xsd:string'),
		'image'					=> array('name'=>'image','type'=>'xsd:string'),
		'images'				=> array('name'=>'images','type'=>'xsd:string'),
		'category'				=> array('name'=>'category','type'=>'xsd:string'),
		'product_url'			=> array('name'=>'product_url','type'=>'xsd:string'),
		'manufacturer'			=> array('name'=>'manufacturer','type'=>'xsd:string'),
		'id_manufacturer'		=> array('name'=>'id_manufacturer','type'=>'xsd:string'),
		'eco_tax'				=> array('name'=>'eco_tax','type'=>'xsd:string'),
		'match'					=> array('name'=>'match','type'=>'xsd:string'),
		'wholesale_price'		=> array('name'=>'wholesale_price','type'=>'xsd:string')
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
    'WS to Create Product'
);
function Create($authentication,$params)
{
	global $db,$conf,$langs;
		if ($conf->global->CYBEROFFICE_chanel==0) $objectresp = CreateWoCurl($authentication,$params);
			else $objectresp = CreateWCurl($authentication,$params);
	return $objectresp;
}
function CreateWoCurl($authentication,$params)
{
    global $db,$conf,$langs;
    
    $now=dol_now();

    dol_syslog("cyberoffice::Function: CreateWoCurl login=".$authentication['login']);
    $_POST["authentication"] = $authentication;
    $_POST["params"] = $params;
    include ('server_product.inc.php');
}
function CreateWCurl($authentication,$params)
{
	global $db,$conf,$langs;
		$pageURL = 'http';
 		if ($_SERVER["HTTPS"] == "on") $pageURL .= "s";
 			$pageURL .= "://";
 		$pageURL0 = $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 		if ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443") 
  			$pageURL0 = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 		$pageURL .= $pageURL0;
 		$pageURL1 = str_replace('server_product.php', 'server_product_ws3.php',$pageURL);
 		
		dol_syslog("cyberoffice::envoi curl sur ".$pageURL1);
		$nbcanal = (($conf->global->CYBEROFFICE_chanel && (int)$conf->global->CYBEROFFICE_chanel>0)?(int)$conf->global->CYBEROFFICE_chanel:5);
		$number=sizeof($params,0);
		$partlen = floor( $number / $nbcanal );
		$partrem = $number % $nbcanal;
		$partition = array();
    	$mark = 0;
    	for ($px = 0; $px < $nbcanal; $px++) {
	        $incr = ($px < $partrem) ? $partlen + 1 : $partlen;
	        $partition[$px] = array_slice( $params, $mark, $incr );
	        $mark += $incr;
	    }
	    $cmi = curl_multi_init();
    	for ($px = 0; $px < $nbcanal; $px++) {
			$fields = array('authentication' => $authentication,'params' => $partition[$px]);
			$field_string = http_build_query($fields);
			${'ch'.$px}= curl_init();
			curl_setopt(${'ch'.$px}, CURLOPT_URL, $pageURL1);
			curl_setopt(${'ch'.$px},CURLOPT_RETURNTRANSFER, 1);//false
			//curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($ch, CURLOPT_TIMEOUT, 1);
			//curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1);
			curl_setopt(${'ch'.$px}, CURLOPT_USERAGENT, 'CURL');
			curl_setopt(${'ch'.$px}, CURLOPT_POST, 1);//sizeof($fields)
	    	curl_setopt(${'ch'.$px}, CURLOPT_POSTFIELDS, $field_string);
	    	//curl_setopt(${'ch'.$px}, CURLOPT_PROXY, 'proxy url');
	    	curl_multi_add_handle($cmi, ${'ch'.$px});
    	}
    	
    	$running = null;
    	$active = null;
    	
    	do {
			$mrc = curl_multi_exec($cmi, $active);
			//$res = curl_multi_info_read($cmi);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		/*
		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($cmi) != -1) {
				do {
					$mrc = curl_multi_exec($cmi, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
		*/
		while ($active && $mrc == CURLM_OK) {
			// Wait for activity on any curl-connection
			if (curl_multi_select($cmi) == -1) {
				usleep(1);
			}
			// Continue to exec until curl is ready to
			// give us more data
			do {
				$mrc = curl_multi_exec($cmi, $active);
				//$res = curl_multi_info_read($cmi);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}
		
    	/*
    	do {
			$mrc = curl_multi_exec($cmi, $running);
			sleep(1);
			break;
		} while ($running > 0);
		*/
		$res = array();
		for ($px = 0; $px < $nbcanal; $px++) {
			//$res[] = curl_multi_getcontent(${'ch'.$px});
			//array_push($res, $res0);
			curl_multi_remove_handle($cmi, ${'ch'.$px});
		}
		/*foreach ($ch AS $i => $c) {
		    $res = curl_multi_getcontent($c);
		    curl_multi_remove_handle($cmi, $c);
		}*/
		curl_multi_close($cmi);
		//$res = curl_multi_info_read($cmi);
		//$objectresp=array('result::e'=>array('result_code'=>'result_code', 'result_label'=>'result_label'),'description'=>'description');
		$objectresp=array('result'=>array('result_code' => '', 'result_label' => ''), 'description'=>json_encode($res));
		return $objectresp;
}
function Creates($authentication,$params)
{
		$pageURL = 'http';
 		if ($_SERVER["HTTPS"] == "on") $pageURL .= "s";
 			$pageURL .= "://";
 		if ($_SERVER["SERVER_PORT"] != "80") 
  			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 		else 
  			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 
 		$pageURL1 = str_replace('server_product.php', 'server_product_ws3.php',$pageURL);

		$fields = array('authentication' => $authentication,'params' => $params);
		$field_string = http_build_query($fields);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $pageURL1);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,false);
		//curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		//curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		//curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'CURL');
		curl_setopt($ch, CURLOPT_POST, 1);//sizeof($fields)
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
		curl_exec($ch);
		curl_close($ch);
		$objectresp=array('resulte'=>array('result_code'=>'result_code', 'result_label'=>'result_label'),'description'=>'description');
		return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
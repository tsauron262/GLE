<?php
/* CyberOffice
*  @author 		LVSinformatique <contact@lvsinformatique.com>
*  @copyright  	2014 LVSInformatique
*  @version   	1.2.31
*/

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cyberoffice/class/nusoap/lib/nusoap.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/cyberoffice/class/cyberoffice.class.php';
dol_syslog("Call Dolibarr webservices interfaces");

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
$server->configureWSDL('WebServicesDolibarrConfig',$ns);
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
        'myurl' => array('name'=>'myurl','type'=>'xsd:string'),
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
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    )
);


// Define other specific objects
$server->wsdl->addComplexType(
    'myparam',
    'complexType',
    'struct',
    'all',
    '',
    array(
        //'limit' => array('name'=>'limit','type'=>'xsd:string'),
        'repertoire' 	=> array('name'=>'repertoire','type'=>'xsd:string'),
        'supplier' 		=> array('name'=>'supplier','type'=>'xsd:string'),
    	'category' 		=> array('name'=>'category','type'=>'xsd:string'),
    	'myurl' 		=> array('name'=>'myurl','type'=>'xsd:string')
    )
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
    'getConfig',
    // Entry values
    array('authentication'=>'tns:authentication','myparam'=>'tns:myparam'),
    // Exit values
    array('result'=>'tns:result','repertoire'=>'xsd:string','repertoireTF'=>'xsd:string','webservice'=>'xsd:string','dolicyber'=>'xsd:string','indice'=>'xsd:string','version'=>'xsd:string','cleweb'=>'xsd:string'),
    $ns,
    $ns.'#getConfig',
    $styledoc,
    $styleuse,
    'WS to get Config'
);
function getConfig($authentication,$myparam)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: getConfig login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $resultparam=array();

    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    $error=0;
    // Check parameters
    $repertoire='KO';
	if (file_exists('../cyberoffice/')) $repertoire='OK';
	
	$dolicyber='KO';
	if (! empty($conf->global->MAIN_MODULE_CYBEROFFICE))
	{
		$dolicyber='OK';
	} else {
		$langs->load("admin");
		$dolicyber= $langs->trans("WarningModuleNotActive",'CyberOffice').'.<br>';
		$dolicyber.= $langs->trans("ToActivateModule");
	}

	$webservic='KO';
	if (! empty($conf->global->MAIN_MODULE_WEBSERVICES))
	{
		$webservic='OK';
	} else {
		$langs->load("admin");
		$webservic = $langs->trans("WarningModuleNotActive",'WebServices').'.<br>';
		$webservic.= $langs->trans("ToActivateModule");
	}
	$cyber = new Cyberoffice;
	$cyber->entity = $authentication['entity'];
	$cyber->myurl = $myparam['myurl'];
	$indice = $cyber->nbShop();
	
	require_once DOL_DOCUMENT_ROOT.'/cyberoffice/core/modules/modCyberoffice.class.php';
	$Modcyber = new modcyberoffice($db) ;

    if ($error)
    {
        $objectresp = array(
            'result'		=>array('result_code' => $errorcode, 'result_label' => $errorlabel),
            'repertoire' 	=> $myparam['repertoire'],
            'repertoireTF' 	=> $repertoire,
            'webservice' 	=> $webservic,
            'dolicyber' 	=> $dolicyber,
            'indice'	 	=> $indice,
            'version'		=> $Modcyber->version,
            'cleweb'		=> sha1($conf->global->WEBSERVICES_KEY)
        );
    }
    else
    {
        $objectresp = array(
            'result'		=>array('result_code' => 'OK', 'result_label' => $myparam['repertoire']),
            'repertoire' 	=> $myparam['repertoire'],
            'repertoireTF' 	=> $repertoire,
            'webservice' 	=> $webservic,
            'dolicyber' 	=> $dolicyber,
            'indice'	 	=> $indice,
            'version'		=> $Modcyber->version,
            'cleweb'		=> sha1($conf->global->WEBSERVICES_KEY)
        );
    }
	
    return $objectresp;
}

// Return the results.
//if ( !isset( $HTTP_RAW_POST_DATA ) ) $HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
//$server->service($HTTP_RAW_POST_DATA);
$server->service(file_get_contents("php://input"));
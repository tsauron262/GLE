<?php
/**
 *  CyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2014 LVSInformatique
 *  @license   NoLicence
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */	

$path = __DIR__.'/';
define('NOCSRFCHECK', 1);

require_once $path."../../master.inc.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/cyberoffice8/class/cyberoffice.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
class DataServer
{
    public function create($authentication, $myparam, $myparam1, $myparam2)
    {
		global $db, $conf, $langs;

		dol_syslog("cyberoffice::Call Dolibarr webservices interfaces::ServerProduct_0",6,0, '_cyber');
		if ($authentication['entity']) {
            $conf->entity=$authentication['entity'];
        }
        if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
            dol_include_once(DOL_DOCUMENT_ROOT . '/custom/multicompany/class/actions_multicompany.class.php');
            $mc = new ActionsMulticompany($db);
            $returnmc = $mc->switchEntity($authentication['entity']);
            $conf->global->WEBSERVICES_KEY=dolibarr_get_const($db, 'WEBSERVICES_KEY', $authentication['entity']);
        }
		$objectresp = array();

		$errorcode='';$errorlabel='';
		$error=0;
        $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
		//set_time_limit(3600);
		@ini_set('default_socket_timeout', 160);
		$langs->load("main");

		if (empty($conf->global->WEBSERVICES_KEY)) {
			$langs->load("cyberoffice@cyberoffice8");
			dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled",6,0, '_cyber');
			print $langs->trans("KeyForWebServicesAccess2");
			exit;
		}

		if ($conf->global->CYBEROFFICE_chanel==0) {
			$objectresp = $this->CreateWoCurl($authentication,$myparam);
		} else {
			$objectresp = $this->CreateWCurl($authentication,$myparam);
		}

		return $objectresp;
	}
	function CreateWoCurl($authentication,$params)
	{
		global $db,$conf,$langs;

		dol_syslog("cyberoffice::Function: CreateWoCurl login=".$authentication['login'],6,0, '_cyber');
		$_POST["authentication"] = $authentication;
		$_POST["params"] = $params;
		include ('server_product.inc.php');
		//$this->myLog("CyberOffice_server_product ERROR::=".print_r($objectresp, true));
		return $objectresp;
	}
	function CreateWCurl($authentication,$params)
	{
		global $db,$conf,$langs;
		dol_syslog("cyberoffice::Function: CreateWCurl login=".$authentication['login'],6,0, '_cyber');
		$res = '';//array();
		$pageURL = 'http';
		if ($_SERVER["HTTPS"] == "on")
			$pageURL .= "s";
		$pageURL .= "://";
		$pageURL0 = $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		if ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443")
			$pageURL0 = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		$pageURL .= $pageURL0;
		$pageURL1 = str_replace('server_product.php', 'server_product_ws3.php',$pageURL);

		dol_syslog("cyberoffice::envoi curl sur ".$pageURL1,6,0, '_cyber');
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
			//$aCurlHandles[$pageURL1] = ${'ch'.$px};
		}

		$running = null;
		$active = null;

		do {
			$mrc = curl_multi_exec($cmi, $active);
			//$res.= $mrc;
			//$res[] = curl_multi_info_read($cmi);
			//$res[] = $mrc;
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($cmi) == -1) {
				usleep(1);
			}
			do {
				$mrc2 = curl_multi_exec($cmi, $active);
				//$res.= $mrc2;
				//$res[] = curl_multi_info_read($cmi);
				//$res[] = $mrc2;
			} while ($mrc2 == CURLM_CALL_MULTI_PERFORM);
		}

		for ($px = 0; $px < $nbcanal; $px++) {
			/*$result = curl_multi_getcontent(${'ch'.$px});
			$res[]=$px;$res[]=$result;*/
			curl_multi_remove_handle($cmi, ${'ch'.$px});
		}
		/*foreach ($aCurlHandles as $url=>$ch) {
			$html = curl_multi_getcontent($ch);
			$res[]=$html;
			curl_multi_remove_handle($cmi, $ch);
		}*/
		curl_multi_close($cmi);
		//$res[] = curl_multi_info_read($cmi);
		//$objectresp=array('result::e'=>array('result_code'=>'result_code', 'result_label'=>'result_label'),'description'=>'description');
		$objectresp=array('result'=>array('result_code' => '', 'result_label' => ''), 'description'=>json_encode($res));
		return $objectresp;
	}									
}
$options = array('uri' => $_SERVER['SERVER_NAME']);

$server = new SoapServer(null, $options);

$server->setClass('DataServer');

$server->handle();

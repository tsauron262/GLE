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

define('NOCSRFCHECK', 1);
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/cyberoffice8/class/cyberoffice.class.php';
class DataServer
{
    public function getConfig($authentication, $myparam, $myparam1, $myparam2)
    {
		global $db,$conf,$langs;
		dol_syslog("Call Dolibarr webservices interfaces");

		$langs->load("main");

		if (empty($conf->global->WEBSERVICES_KEY)) {
			$langs->load("cyberoffice@cyberoffice8");
			dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
			print $langs->trans("KeyForWebServicesAccess2").'.<br><br>';
			exit;
		}

		//$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

		$now=dol_now();

		dol_syslog("Function: getConfig login=".$authentication['login']);

		if ($authentication['entity']) $conf->entity=$authentication['entity'];

        if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
            dol_include_once(DOL_DOCUMENT_ROOT . '/custom/multicompany/class/actions_multicompany.class.php');
            $mc = new ActionsMulticompany($db);
            $returnmc = $mc->switchEntity($authentication['entity']);
            $conf->global->WEBSERVICES_KEY=dolibarr_get_const($db, 'WEBSERVICES_KEY', $authentication['entity']);
        }
		// Init and check authentication
		$objectresp=array();
		$resultparam=array();

		$errorcode='';$errorlabel='';
		$error=0;
		$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
		if ($error)
		{
			$objectresp = array('result'=>array('result_code' => 'ko', 'result_label' => $errorlabel),'webservice'=>'login');
			return $objectresp;
		}
		$error=0;
		// Check parameters
		$repertoire='KO';
		if (file_exists('../cyberoffice8/')) $repertoire='OK';

		$dolicyber='KO';
		if (! empty($conf->global->MAIN_MODULE_CYBEROFFICE8))
		{
			$dolicyber='OK';
		} else {
			$langs->load("admin");
			$dolicyber= $langs->trans("WarningModuleNotActive",'CyberOffice').'.<br>';
			$dolicyber.= $langs->trans("ToActivateModule");
		}

		$webservic='KO';
		if (!empty($conf->global->WEBSERVICES_KEY)) {
			$webservic='OK';
		} else {
			$langs->load("cyberoffice@cyberoffice8");
			$webservic = $langs->trans("KeyForWebServicesAccess2").'.<br>';
		}
		$cyber = new Cyberoffice;
		$cyber->entity = $authentication['entity'];
		$cyber->myurl = $myparam['myurl'];
		$indice = $cyber->nbShop();
		dol_syslog("cyberoffice.class=".$authentication['entity'].' '.$myparam['myurl']);
		require_once DOL_DOCUMENT_ROOT . '/custom/cyberoffice8/core/modules/modCyberoffice8.class.php';
		$Modcyber = new modcyberoffice8($db) ;

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
				'cleweb'		=> sha1(dolibarr_get_const($db, 'WEBSERVICES_KEY', $conf->entity))
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
				'cleweb'		=> sha1(dolibarr_get_const($db, 'WEBSERVICES_KEY', $conf->entity))
			);
		}

		return $objectresp;
	}
}
$options = array('uri' => $_SERVER['SERVER_NAME']);

$server = new SoapServer(null, $options);

$server->setClass('DataServer');

$server->handle();
<?php
/**
 *	MyCyberOffice
 *
 *  @author		LVSinformatique <contact@lvsinformatique.com>
 *  @copyright	2015 LVSInformatique
 *	@license	NoLicence
 *  @version	1.0.16
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
//require_once(DOL_DOCUMENT_ROOT.'/CashdeskPro/class/html.form.class.php');
// Security check
if (!$user->admin)
	accessforbidden();

$langs->load("admin");
$langs->load("mycyberoffice@mycyberoffice");
$form=new Form($db);
$formproduct=new FormProduct($db);

/*
 * Actions
 */
if ($_POST["action"] == 'set')
{
	$shop = GETPOST('shop', int);

	if ($_POST["MYCYBEROFFICE_key".$shop] && strlen($_POST["MYCYBEROFFICE_key".$shop]) < 32) 
				$msg =  '<div class="error">'.$langs->trans('Key length must be 32 character long').'</div>';
	else {
		dolibarr_set_const($db,"MYCYBEROFFICE_InvoiceNumber",$_POST["MYCYBEROFFICE_InvoiceNumber"],'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"MYCYBEROFFICE_key".$shop,$_POST["MYCYBEROFFICE_key".$shop],'chaine',0,'',$conf->entity);
		//dolibarr_set_const($db,"MYCYBEROFFICE_path",$_POST["MYCYBEROFFICE_path"],'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"MYCYBEROFFICE_debug",$_POST["MYCYBEROFFICE_debug"],'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"MYCYBEROFFICE_shop".$shop,$_POST["MYCYBEROFFICE_shop".$shop],'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"MYCYBEROFFICE_lang".$shop,$_POST["MYCYBEROFFICE_lang".$shop],'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"MYCYBEROFFICE_warehouse".$shop,(isset($_POST["MYCYBEROFFICE_warehouse".$shop])?$_POST["MYCYBEROFFICE_warehouse".$shop]:1),'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"MYCYBEROFFICE_Sexpedie".$shop,($_POST["MYCYBEROFFICE_Sexpedie".$shop]?$_POST["MYCYBEROFFICE_Sexpedie".$shop]:4),'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"MYCYBEROFFICE_Slivre".$shop,($_POST["MYCYBEROFFICE_Slivre".$shop]?$_POST["MYCYBEROFFICE_Slivre".$shop]:5),'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"MYCYBEROFFICE_Sfacture".$shop,($_POST["MYCYBEROFFICE_Sfacture".$shop]?$_POST["MYCYBEROFFICE_Sfacture".$shop]:2),'chaine',0,'',$conf->entity);
		$code_country="'".$mysoc->country_code."'";
		$num = $form->load_cache_vatrates($code_country);
		foreach ($form->cache_vatrates as $rate)
			dolibarr_set_const($db,"MYCYBEROFFICE_tax" . $shop . $rate['txtva'], ($_POST["MYCYBEROFFICE_tax".$shop.number_format($rate['txtva'],2,'-','')]?$_POST["MYCYBEROFFICE_tax".$shop.number_format($rate['txtva'],2,'-','')]:0),'chaine',0,'',$conf->entity);

		$msg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
		}
}



/*
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("mycyberofficeSetup"),$linkback,'setup');
if ($msg) dol_htmloutput_mesg($msg);
// Mode
		$h = 0;
		$head = array();
		$head[$h][0] = $_SERVER["PHP_SELF"]."?shop=".$h;
		$head[$h][1] = $langs->trans("Setup");
		$head[$h][2] = $langs->trans("Setup");
		$h++;

		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'const WHERE name LIKE "CYBEROFFICE_SHOP%" ORDER BY name';
		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql))
			{
				$h = (int)substr($obj->name,-2);
				$head[$h][0] = $_SERVER["PHP_SELF"]."?shop=".substr($obj->name,-2);
				$head[$h][1] = $langs->trans("Shop").substr($obj->name,-2);
				$head[$h][2] = $langs->trans("Shop").substr($obj->name,-2);
				$head[$h][3] = substr($obj->name,-2);//shop
				$head[$h][4] = $obj->value;//indice
				$head[$h][5] = $obj->note;//path
				//$h++;
			}
		}
$titre='MyCyberOffice';
$picto = dol_buildpath('mycyberoffice/img/object_mycyberoffice30.png',1);
if (GETPOST('shop',int) > 0)
	$active = $langs->trans("Shop").GETPOST('shop',int);//$head[GETPOST('shop',int)][3];
else
	$active = $langs->trans("Setup");

dol_fiche_head($head, $active, $titre, 0, $picto, 1);

$var=true;
print '<div id="content" class="nobootstrap">';
if (GETPOST('shop',int) == 0) {
	print '
			<fieldset><legend><img src="../img/info.png" /> '.$langs->trans('MyCyberOffice Infos').'</legend>
			1) '.$langs->trans('Open the "Webservice" page under the "Advanced Parameters" menu, and then click the "Add New" button to access the account configuration section.').'<br/>
			2) '.$langs->trans('Create an access key and set the permissions').' <br/>
			</fieldset><br/>';
	print '
			<fieldset><legend><img src="../img/choose.gif" /> '.$langs->trans('MyCyberOffice Documentation').'</legend>
			<a href="https://www.gitbook.com/book/lvs-1fo/mycyberoffice/details" target="_blank">'.$langs->trans('MyCyberOffice Documentation').'</a>
			</fieldset><br/>';
	/*
	print '		
			<fieldset><legend><img src="../img/contact.gif" /> '.$langs->trans('MyCyberOffice SAV').'</legend>
				<a href="https://addons.prestashop.com/contact-form.php?id_product=25807" target="_blank">'.$langs->trans('MyCyberOffice SAV').'</a>
			</fieldset>';
	*/		
}
if (GETPOST('shop',int) > 0) {

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set">';
	print '<input type="hidden" name="indice" value="'.$head[(int)GETPOST('shop',int)][4].'">';
	print '<input type="hidden" name="shop" value="'.$head[(int)GETPOST('shop',int)][3].'">';
	$myglobal = "MYCYBEROFFICE_key" . GETPOST('shop',int);
	print '
		<fieldset><legend><img src="../img/object_mycyberoffice.png" alt="" title="" height="30px"/> '.$langs->trans('Settings').'</legend>
			<label>'.$langs->trans('key').'</label>
				<div class="margin-form">
				<input type="text" size="40" name="MYCYBEROFFICE_key'.GETPOST('shop',int).'" id="MYCYBEROFFICE_key'.GETPOST('shop',int).'" value="'.htmlentities($conf->global->$myglobal, ENT_COMPAT, 'UTF-8').'" />
				'.$langs->trans('Copier ici la cle generee dans PrestashopWebservices.').'
				</div>
			<br/>
			<label>'.$langs->trans('chemin d acces a votre boutique').'</label>
				<div class="margin-form">
				<input type="text" size="40" name="CYBEROFFICE_SHOP'.GETPOST('shop',int).'" value="'.$head[(int)GETPOST('shop',int)][5].'" readonly/> 
				</div>
			<br/>
			<label>'.$langs->trans('Warehouse to use').'</label>
				<div class="margin-form">
				<input type="text" size="5" name="MYCYBEROFFICE_warehouse'.GETPOST('shop',int).'" value="'.(isset($conf->global->{"MYCYBEROFFICE_warehouse".GETPOST('shop',int)})?$conf->global->{"MYCYBEROFFICE_warehouse".GETPOST('shop',int)}:1) .'" />

				'.$langs->trans('0 : tous les entrepots seront transferes sur Prestahop').'
				</div>
			<br/>
<!--	
			<label>'.$langs->trans('Lang id').'</label>
				<div class="margin-form">
				<input type="text" size="5" name="MYCYBEROFFICE_lang'.GETPOST('shop',int).'" value="'.(!$conf->global->{"MYCYBEROFFICE_lang".GETPOST('shop',int)}?1:$conf->global->{"MYCYBEROFFICE_lang".GETPOST('shop',int)}).'" />
				</div>
			<br/>
-->			
			<label>'.$langs->trans('shop id').'</label>
				<div class="margin-form">
				<input type="text" size="5" name="MYCYBEROFFICE_shop'.GETPOST('shop',int).'" value="'.(!$conf->global->{"MYCYBEROFFICE_shop".GETPOST('shop',int)}?1:$conf->global->{"MYCYBEROFFICE_shop".GETPOST('shop',int)}).'" />
				</div>
			<br/>
			
			<label>'.$langs->trans('InvoiceNumberSynchronization').'</label>
			<div class="margin-form">
			<select name="MYCYBEROFFICE_InvoiceNumber">
				<option value="0" '.($conf->global->MYCYBEROFFICE_InvoiceNumber==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
				<option value="1" '.($conf->global->MYCYBEROFFICE_InvoiceNumber==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
			</select>
			</div>
			<br/>
			<label>'.$langs->trans('Prestashop Orders statuses').'</label>
				<div class="margin-form">
				<input type="text" size="5" name="MYCYBEROFFICE_Sexpedie'.GETPOST('shop',int).'" value="'.(!$conf->global->{'MYCYBEROFFICE_Sexpedie'.GETPOST('shop',int)}?4:$conf->global->{"MYCYBEROFFICE_Sexpedie".GETPOST('shop',int)}).'" />'.$langs->trans('Expedie').'
				</div>
				<div class="margin-form">
				<input type="text" size="5" name="MYCYBEROFFICE_Slivre'.GETPOST('shop',int).'" value="'.(!$conf->global->{'MYCYBEROFFICE_Slivre'.GETPOST('shop',int)}?5:$conf->global->{'MYCYBEROFFICE_Slivre'.GETPOST('shop',int)}).'" />'.$langs->trans('Livre').'
				</div>
	
			<br/>
			<label>'.$langs->trans('VAT').'</label>
			<div class="margin-form">';
	//print __LINE__.'<br/>';
				if ($conf->global->{'MYCYBEROFFICE_key' . GETPOST('shop',int)} && $conf->global->{'CYBEROFFICE_SHOP' . GETPOST('shop',int)}) 
					$test = testConfig(GETPOST('shop',int), $head[(int)GETPOST('shop',int)][5]);
				//print  $test['result']['result_code'];
	//print __LINE__.'<br/>';
				$code_country="'".$mysoc->country_code."'";
	//print __LINE__.'::'.$code_country.'<br/>';
				$num = $form->load_cache_vatrates($code_country);
	//print __LINE__.'::'.$num.'<br/>';
	//print_r($mysoc);
				print '<table><tr><td style="text-align:center;font-weight:bold">Prestashop</td><td style="text-align:center;font-weight:bold">Dolibarr</td></tr>';
				if (is_array($test) && $test['result']['result_code'] != 'KO')
					foreach ($form->cache_vatrates as $rate)
					{
						print '<tr>';
						$html = '<td><select name="MYCYBEROFFICE_tax' . GETPOST('shop',int) . number_format($rate['txtva'], 2, '-', '') . '">';
						foreach ($test['tax'] as $k => $v) 
							$html.= '<option value="'.$v['id_tax_rules_group'].'" '.($conf->global->{"MYCYBEROFFICE_tax" . GETPOST('shop',int) . $rate['txtva']}==$v['id_tax_rules_group']?'selected="selected"':'').'>'.$v['name'].'</option>';
						$html.='</select></td>';
						print $html;
						print '<td style="text-align:right;font-weight:bold">'.$rate['libtva'].'</td></tr>';
					}
	
		print '</table>
			</div>
			<br/>
	
			<label>'.$langs->trans('Debug Mode').'</label>
			<div class="margin-form">
				<select name="MYCYBEROFFICE_debug">
					<option value="0" '.($conf->global->MYCYBEROFFICE_debug==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
					<option value="1" '.($conf->global->MYCYBEROFFICE_debug==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
				</select>
			</div>
			<br/>
			<label>Permissions</label>
			<div class="margin-form">
				<a href="'.$head[(int)GETPOST('shop',int)][5].'api/" target="_blank">'.$langs->trans('Click to see permissions for this key').'</a>
			</div>
			<br/>
	';
	print '<label><input type="submit" class="button" value="'.$langs->trans("Save").'"></label>';
	print '</fieldset>';
	print "</form>\n";
	
	$test = testConfig(GETPOST('shop',int), $head[(int)GETPOST('shop',int)][5]);
}
/*
print '<pre>';
print_r($test);
print '</pre>';
*/
llxFooter();
function testConfig($myshop, $myurl)
	{
		//print __LINE__.$myshop;
		global $conf;
		$ws_dol_url = $conf->global->{'CYBEROFFICE_SHOP'.$myshop}.'modules/mycyberoffice/server_config_soap.php';
		$ws_method  = 'getConfig';
		$ns = 'http://www.lvsinformatique.com/ns/';

		// Set the WebService URL
		$options = array('location' => $myurl.'modules/mycyberoffice/server_config_soap.php',
                  'uri' => $myurl);
		$soapclient = new SoapClient(NULL,$options);
		//if ($soapclient)
		//	$soapclient->soap_defencoding = 'UTF-8';

		//$soapclient2 = new nusoap_client($ws_dol_url);
		//if ($soapclient2)
		//	$soapclient2->soap_defencoding = 'UTF-8';

		// Call the WebService method and store its result in $result.
		$authentication = array(
				'dolibarrkey'=>htmlentities($conf->global->{'MYCYBEROFFICE_key'.$myshop}, ENT_COMPAT, 'UTF-8'),
				'sourceapplication'	=> 'LVSInformatique',
				'login'				=> '',
				'password'			=> '',
				'shop'				=> $conf->global->{'MYCYBEROFFICE_shop'.$myshop},
				'lang' 				=> $conf->global->{'MYCYBEROFFICE_lang'.$myshop},
				'myurl'				=> $_SERVER["PHP_SELF"]
			);
		//print_r($authentication );
		$myparam = array(
			'repertoire'=>$myurl,
			'supplier' 	=> 1,
			'category' 	=> 2,
			'myurl'		=> $_SERVER["PHP_SELF"]
		);
		//print_r($myparam );
		//$parameters = array('authentication'=>$authentication, $myparam);
		
		try {
			$result = $soapclient->getConfig($authentication, $myparam, $ns, '');
		}
	      catch(SoapFault $fault)
	      {
	      	print 'faultstring = '.$fault->faultstring;
	        if($fault->faultstring != 'Could not connect to host' && $fault->faultstring != 'Not Found')
	        {
	        	echo '<pre>';
	        	print_r($fault);
	        	echo '</pre>';
	          throw $fault;
	        }
	      }

		if (! $result || $result['result']['result_label'] != 'OK')
		{
			print '<br/><div class="error">**NOK**'. __LINE__.'::'.$result['result']['result_label'].'</div>';
			//$result = '**NOK**';
			$result = array(
				'result'=>array('result_code' => 'KO'),
				'repertoire' => $myurl,
				'repertoireTF' => 'KO',
				'webservice' => 'KO',
				'dolicyber' => 'KO',
				'indice' => -1
			);
		}
		
	if($conf->global->MYCYBEROFFICE_debug==1) {
		print '<pre>'.print_r($result).'</pre>';
		// show soap request and response
		echo "<h2>Request</h2>";
		echo "<pre>" . htmlspecialchars($soapclient->request, ENT_QUOTES) . "</pre>";
		echo "<h2>Response</h2>";
		echo "<pre>" . htmlspecialchars($soapclient->response, ENT_QUOTES) . "</pre>";
	}

		return $result;

		//return $result['description']['repertoire'];
	}

////////////////////////////////////
?>
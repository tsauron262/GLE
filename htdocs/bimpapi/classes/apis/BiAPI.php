<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class BiAPI extends BimpAPI
{
	/*
        */


	public static $asUser = false;
	public static $name = 'Bi';
	public static $include_debug_json = false;

	public static $default_accept = 'text/xml';
	public static $urls_bases = array(
		'default' => array(
//			'prod' => 'https://erp.bimp.fr/bimp/bimpapi/public/',
			'prod' => 'http://172.24.2.31/OLAP/',
			'test' => ''
		)
	);
	public static $requests = array(
		'req' => array(
			'label'         => 'Requête',
			'url'           => 'msmdpump.dll',
//			'url'           => 'rebond.php',
			'content_type' 	=> 'text/xml',
		),
	);
	public function sendReq($req){
		$errors = array();
		$xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <soap:Body>
    <Execute xmlns="urn:schemas-microsoft-com:xml-analysis">
      <Command>
        <Statement>'.str_replace('<br>', '', $req).'</Statement>
      </Command>
      <Properties>
        <PropertyList>
          <Catalog>SiteRDC</Catalog>
          <Format>Tabular</Format>
        </PropertyList>
      </Properties>
    </Execute>
  </soap:Body>
</soap:Envelope>';


		$return = $this->execCurl('req', array(
			'header_out'=>1,
			'post_mode'	=> 'xml',
			'fields'	=> $xml,
			'curl_options' => array(
//				CURLOPT_USERPWD => $this->getParam('login') . ":" . $this->getParam('mdp'),
				CURLOPT_POST => 1
			)
		), $errors);



		$return = '<?xml version="1.0" encoding="utf-8"?>'.str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $return);
		libxml_use_internal_errors(true);
		$objXmlDocument = simplexml_load_string($return);

		if ($objXmlDocument == FALSE) {
			echo "There were errors parsing the XML file.\n";
			foreach(libxml_get_errors() as $error) {
				echo $error->message;
			}
			exit;
		}
		$objJsonDocument = json_encode($objXmlDocument->Body->ExecuteResponse->return->root);
		$arrOutput = json_decode($objJsonDocument, TRUE);

		$newTab = array();
//		$tabConvert = array(
//			'Vendeurs_x005B_Nom_x005D_'=> 'Name',
//			'Vendeurs_x005B_FreeShipping_x005D_'=> 'FreeShipping',
//			'_x005B_CA_x0020_Total_x0020_HT_x005D_'=> 'ca',
//		);
		foreach($arrOutput['row'] as $ln){
			$cat = 0;
			$name = $ln['Vendeurs_x005B_VendeurNomSociete_x005D_'];
			$shopId = $ln['orders_x005B_shop_id_x005D_'];
			if(isset($ln['_x005B_CA_x0020_par_x0020_Cat_x005D_'])){
				$val = $ln['_x005B_CA_x0020_par_x0020_Cat_x005D_']*10 / 10;
			}
			elseif(isset($ln['_x005B_CA_x0020_Total_x0020_HT_x005D_'])){
				$val = $ln['_x005B_CA_x0020_Total_x0020_HT_x005D_']*10 / 10;
			}
			if(isset($ln['FamillesProduitsSite_x005B_Level_2_Name_x005D_'])){
				$cat = $ln['FamillesProduitsSite_x005B_Level_2_Name_x005D_'];
			}

			if(isset($ln['FamillesProduitsSite_x005B_Level_3_Name_x005D_'])){
				$cat .= '%$'.$ln['FamillesProduitsSite_x005B_Level_3_Name_x005D_'];
			}
			if(isset($ln['FamillesProduitsSite_x005B_Level_4_Name_x005D_'])){
				$cat .= '%$'.$ln['FamillesProduitsSite_x005B_Level_4_Name_x005D_'];
			}
			if(isset($ln['FamillesProduitsSite_x005B_Level_5_Name_x005D_'])){
				$cat .= '%$'.$ln['FamillesProduitsSite_x005B_Level_5_Name_x005D_'];
			}

			if($name != '')
				$newTab[$shopId][$cat] = $val;
		}

		return $newTab;
	}

	public function getDefaultCurlOptions($request_name, &$errors = array())
	{
		$options = array(
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false
		);
		return $options;
	}

	public function getDefaultRequestsHeaders($request_name, &$errors = []){
		$headers = array();
		if($this->getParam('basic') != ''){
			$headers['Authorization'] = 'Basic ' . $this->getParam('basic');
		}
		else{
			$headers['Authorization'] = 'Basic ' . base64_encode($this->getParam('login') . ":" . $this->getParam('mdp'));
		}
		return $headers;
	}


	public function testRequest(&$errors = array(), &$warnings = array())
	{
		$this->majCaWithNbDay(10, $warnings, $errors);

/*
		$mois = array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
		$annee = array("2020", "2021", "2022", "2023");
		$annee = array("2024","2025");
		$categorie = 1;
		$this->majCa($annee, $mois, $categorie, $warnings, $errors);
*/
	}

	public function majCaWithNbDay($nbDay, &$warnings = array(), &$errors = array()){
		$date = new DateTime();
		$date->modify('-'.$nbDay.' day');
		$mois = array($date->format('m'));
		$annee = array($date->format('Y'));
		$this->majCa($annee, $mois, 0, $warnings, $errors);
		$this->majCa($annee, $mois, 1, $warnings, $errors);
	}

	public function majCa($annee, $mois = array(), $categ = 0, &$warnings = array(), &$errors = array())
	{
		foreach ($annee as $val) {
			$return = $this->traiteStats($val, 0, $categ, $warnings);
//			$warnings[] = "<pre>".print_r($return,1);
			foreach($mois as $val2){
				$return = $this->traiteStats($val, $val2, $categ, $warnings);
//				$warnings[] = "<pre>".print_r($return,1);
			}
		}
		$warnings2 = array();
		foreach($warnings as $msg){
			if(!in_array($msg, $warnings2))
				$warnings2[] = $msg;
		}
		$warnings = $warnings2;
	}

	public function getStats($annee, $mois = 0, $catergorie = 0){
		$req = 'EVALUATE SUMMARIZECOLUMNS(
	Vendeurs[VendeurNomSociete],
	orders[shop_id],';
		if ($catergorie){
			$req .= '
     FamillesProduitsSite[Level_2_Name],
    FamillesProduitsSite[Level_3_Name],
    FamillesProduitsSite[Level_4_Name],
    FamillesProduitsSite[Level_5_Name],';
		}
		$req .= 'KEEPFILTERS( TREATAS( {"Expédiée", "Préparée", "Réceptionnée", "Validée", "Validée par le vendeur", "En préparation"}, StatutCommandes[StatutCommande] )),';
		$req .= 'KEEPFILTERS( TREATAS( {'.$annee.'}, Calendrier[Année] )),';
		if($mois > 0){
			$req .= 'KEEPFILTERS( TREATAS( {'.(int) $mois.'}, Calendrier[Mois Numéro] )),';
		}
		$req .= '"CA Total HT", [CA Total HT]
)
ORDER BY
    Vendeurs[VendeurNomSociete] ASC';

	$return = $this->sendReq($req);
		return $return;
	}

	public function traiteStats($annee, $mois = 0, $catergorie = 0, &$warnings = array())
	{
		BimpObject::loadClass('bimpcore', 'Bimp_ChiffreAffaire');
		$errors = array();
		$ok = $bad = 0;
		$return = $this->getStats($annee, $mois, $catergorie);
//		echo '<pre>';print_r($return);die;

		$dict = BimpDict::getDictionnary('ca_categories');

		foreach ($return as $key => $tabT) {
			foreach ($tabT as $cat => $val) {
				$soc = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Client', array('shopid' => $key), true);
//				if (!$soc || !$soc->isLoaded()) {
//					$soc = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Societe', array('name_alias' => $key));
//				}
				if (!$soc || !$soc->isLoaded()) {
					$soc = BimpObject::createBimpObject('bimpcore', 'Bimp_Client', array('nom' => 'Marchand inconnue importé de BI', 'zip' => '69000', 'town' => 'Lyon', 'shopid' => $key), true, $warnings, $warnings);
				}
				if (!$soc || !$soc->isLoaded()) {
					$warnings[] = 'Societe introuvable et non céable : ' . $key;
					$bad++;
				} else {
					$ok++;
					if ($mois == 0) {
						$date = $annee . '-01-01';
					} else {
						$date = $annee . '-' . $mois . '-01';
					}
					$dataFiltre = array(
						'id_obj'       => $soc->id,
						'type_obj'     => 'Bimp_Societe',
						'fk_period'    => ($mois == 0) ? 0 : 3,
						'debut_period' => $date,
					);

					if ($cat != 0) {
						$tabCat = explode('%$', $cat);
						for($i=1;$i<5;$i++){
//						foreach($tabCat as $i => $cat){
							if(isset($tabCat[$i-1]))
								$cat = $tabCat[$i-1];
							else
								$cat = 'N/C';
							$cat = $dict->getByValue($cat, true, true);
							if($i == 1)
								$dataFiltre['fk_category'] = $cat;
							else
								$dataFiltre['fk_category'.$i] = $cat;
						}
					}
					else
						$dataFiltre['fk_category'] = 0;


					$data = array(
						'ca' => $val
					);
					BimpObject::createOrUpdateBimpObject('bimpcore', 'Bimp_ChiffreAffaire', $dataFiltre, $data, true, true, $errors, $warnings);
				}
			}
		}
		if($bad > 0)
			return $ok.' Ok'.' '.$bad.' Bad'.print_r($warnings,1).print_r($errors,1);
	}




	// Install:

	public function install($title = '', &$warnings = array())
	{
		$errors = array();

		$api = BimpObject::createBimpObject('bimpapi', 'API_Api', array(
			'name'  => static::$name,
			'title' => ($title ? $title : $this->getDefaultApiTitle())
		), true, $errors, $warnings);

		if (BimpObject::objectLoaded($api)) {

			BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
				'id_api' => $api->id,
				'name'   => 'url',
				'title'  => 'Url'
			), true, $warnings, $warnings);

			BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
				'id_api' => $api->id,
				'name'   => 'login',
				'title'  => 'Login'
			), true, $warnings, $warnings);

			BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
				'id_api' => $api->id,
				'name'   => 'mdp',
				'title'  => 'Mdp'
			), true, $warnings, $warnings);
			BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
				'id_api' => $api->id,
				'name'   => 'basic',
				'title'  => 'Basic'
			), true, $warnings, $warnings);
		}

		return $errors;
	}
}

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
			'prod' => 'http://172.24.2.31/OLAP/',
			'test' => ''
		)
	);
	public static $requests = array(
		'req' => array(
			'label'         => 'Requête',
			'url'           => 'msmdpump.dll',
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


//		$ch = curl_init($this->getParam('url'));
//		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
//		curl_setopt($ch, CURLOPT_HEADER, 1);
//		curl_setopt($ch, CURLOPT_USERPWD, $this->getParam('login') . ":" . $this->getParam('mdp'));
//		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
//		curl_setopt($ch, CURLOPT_POST, 1);
//		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
//		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//		$return = curl_exec($ch);
//		curl_close($ch);
		return $return;
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
		$return = $this->sendReq('EVALUATE SUMMARIZECOLUMNS(
	Vendeurs[Nom],
	Vendeurs[FreeShipping],
	KEEPFILTERS( TREATAS( {2025}, Calendrier[Année] )),
"CA Total HT", [CA Total HT]
)
ORDER BY
    Vendeurs[Nom] ASC,
    Vendeurs[FreeShipping] ASC');
		$warnings[] = $return;
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

<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class MiraklAPI extends BimpAPI
{
	/*
	https://mirakl-web.groupe-rueducommerce.fr/
	t.sauron@bimp.fr
	65712d48-2b0a-4290-83f9-a38b586dc807
	https://developer.mirakl.com/content/product/mmp/rest/operator/openapi3/stores/s20
        */


	public static $asUser = false;
	public static $name = 'Mirakl';
	public static $include_debug_json = false;
	public static $urls_bases = array(
		'default' => array(
			'prod' => 'https://mirakl-web.groupe-rueducommerce.fr/',
			'test' => ''
		)
	);
	public static $requests = array(
		'getShops' => array(
			'label'         => 'Récupération des informations des shops ',
			'url'           => '/api/shops'
		),
	);
//    public static $tokens_types = array(
//        'access' => 'Token d\'accès'
//    );

	// Requêtes:

	// Settings:

	public function testRequest(&$errors = array(), &$warnings = array())
	{
		$data = $this->getShopInfo(2037, $errors);

		if(isset($data['shops']))
			$warnings[] = count($data['shops']).' résultats';


		return $data;
	}

	public function getShopInfo($shop_id, &$errors = array())
	{
		$data = $this->execCurl('getShops', array(
			'url_params' => array('shop_ids' => $shop_id)
		), $errors);

		return $data;
	}


	public function getDefaultRequestsHeaders($request_name, &$errors = array())
	{
		if ($this->options['mode'] === 'test') {
			$apiKey = BimpTools::getArrayValueFromPath($this->params, 'test_api_key', '');
		} else {
			$apiKey = BimpTools::getArrayValueFromPath($this->params, 'prod_api_key', '');
		}

		return array(
			'Authorization' => $apiKey,
			'Accept'    => 'application/json'
		);
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
				'name'   => 'prod_api_key',
				'title'  => 'Clé API en mode production'
			), true, $warnings, $warnings);

			BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
				'id_api' => $api->id,
				'name'   => 'test_api_key',
				'title'  => 'Clé API en mode test'
			), true, $warnings, $warnings);
		}

		return $errors;
	}
}

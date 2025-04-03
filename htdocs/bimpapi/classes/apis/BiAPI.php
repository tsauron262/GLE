<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class BiAPI extends BimpAPI
{
	/*
        */


	public static $asUser = false;
	public static $name = 'Bi';
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

		try {
			$hostname = "ven-bi-1.siege.ldlc.com/DATABI";
			$port = 10060;
			$dbname = "SiteRDC";
			$username = "t.sauron";
			$pw = 'zLpVTs$VwPmP01EUc7dfFu';
			$dbh = new PDO ("dblib:host=$hostname:$port;dbname=$dbname","$username","$pw");
		} catch (PDOException $e) {
			echo "Failed to get DB handle: " . $e->getMessage() . "\n";
			exit;
		}
		$stmt = $dbh->prepare("select name from master..sysdatabases where name = db_name()");
		$stmt->execute();
		while ($row = $stmt->fetch()) {
			print_r($row);
		}
		unset($dbh); unset($stmt);
















//		$serverName = "your_server_name";
//		$connectionOptions = array(
//			"Database" => "your_database_name",
//			"UID" => "your_username",
//			"PWD" => "your_password"
//		);
//
//		// Établir la connexion
//		$conn = sqlsrv_connect($serverName, $connectionOptions);
//
//		if ($conn === false) {
//			die(print_r(sqlsrv_errors(), true));
//		}
//
//		// Exécuter une requête
//		$sql = 'EVALUATE
//SUMMARIZECOLUMNS(
//    Vendeurs[Nom],
//    Vendeurs[FreeShipping],
//KEEPFILTERS( TREATAS( {2025}, Calendrier[Année] )),
//"CA Total HT", [CA Total HT]
//)
//ORDER BY
//    Vendeurs[Nom] ASC,
//    Vendeurs[FreeShipping] ASC;';
//		$stmt = sqlsrv_query($conn, $sql);
//
//		if ($stmt === false) {
//			die(print_r(sqlsrv_errors(), true));
//		}
//
//		// Traiter les résultats
//		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
//			print_r($row);
//		}
//
//		// Fermer la connexion
//		sqlsrv_free_stmt($stmt);
//		sqlsrv_close($conn);
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
		}

		return $errors;
	}
}

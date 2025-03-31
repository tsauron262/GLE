<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/BimpAPI.php';

class InpiAPI extends BimpAPI
{
	/*
        */

	public static $asUser = false;
	public static $name = 'Inpi';
	public $result = array();
	private $filedsAdress = array('numVoie', 'typeVoie', 'voie', 'complementLocalisation', 'codePostal', 'commune', 'pays');
	public static $include_debug_json = false;
	public static $urls_bases = array(
		'default' => array(
			'prod' => 'https://registre-national-entreprises.inpi.fr/api/',
			'test' => ''
		)
	);
	public static $requests = array(
		'authenticate' => array(
			'label'         => 'Login',
			'url'           => 'sso/login'
		),
		'getSoc' => array(
			'label'         => 'Get Societe',
			'url'           => 'companies'
		),
	);

	// Requêtes:

	// Settings:

	public function testRequest(&$errors = array(), &$warnings = array())
	{
//		$filters = array('companyName' => 'Lou CREEZ\'ART');
		$filters = array('siren' => array('92923901000010'));
//
//		$data = $this->getSoc($filters, $errors);

//		$data = $this->getCompanyFromSiret('92923901000010', $errors, $warnings);
		$data = $this->getCompany('LDLC', '', $errors);

//		$data = $this->getCompanyFromSiret('31659184100051', $errors, $warnings);
//$warnings[] = '<pre>'.print_r($data, true).'</pre>';
//die('<pre>'.print_r($data, true).'</pre>');
		if(isset($data))
			$warnings[] = count($data).' résultats';


		return $data;
	}

	public function getSocSiren(){
		$return = array();
		foreach($this->getCache() as $soc){
			$return[$soc['siren']] = $soc['siren'].' - '.$soc['name'];
		}
		return $return;
	}
	public function getSocSiret($siren){
		$siren = str_replace(' ', '', $siren);
		$return = array();
		foreach($this->getCache() as $soc) {
			if($soc['siren'] == $siren) {
				foreach ($soc['etablissements'] as $etb) {
					$add = '';
					foreach ($this->filedsAdress as $field) {
						$add .= $etb['adresse'][$field] . ' ';
					}
					$return[$etb['siret']] = $etb['siret'] . ' - ' . $add;
				}
				if(!count($return)){
					$add = '';
					foreach ($this->filedsAdress as $field) {
						$add .= $soc['adresse'][$field] . ' ';
					}
					$return[$siren] = $siren  . ' - ' . $add;
				}
			}
		}
		return $return;
	}
	public function getSiret($siret){
		$siret = str_replace(' ', '', $siret);
		$siren = substr($siret, 0, 9);
		$return = array();
		foreach($this->getCache() as $soc) {
			if($soc['siren'] == $siren) {
				if($siret == $siren && isset($soc['siretP'])){
					$siret = $soc['siretP'];
				}
				foreach ($soc['etablissements'] as $etb) {
					if ($etb['siret'] == $siret) {
						$return = array(
							'name' => $soc['name'],
							'ape' => $etb['ape'],
							'adresse' => $etb['adresse'],
							'adresseFull' => $etb['adresse']['numVoie'] . ' ' . $etb['adresse']['typeVoie'] . ' ' . $etb['adresse']['voie'] . ' ' . $etb['adresse']['complementLocalisation']
						);
					}
				}
			}
		}

		$dataR = array();
		$dataR['nom'] = $return['name'];
		$dataR['ape'] = $return['ape'];
		$dataR['address'] = $return['adresseFull'];
		$dataR['siret'] = $siret;
		$dataR['siren'] = $siren;
		$dataR['zip'] = $return['adresse']['codePostal'];
		$dataR['town'] = $return['adresse']['commune'];



		return $dataR;
	}
	public function getCompany($name = '', $siretSiren = '', &$errors = array())
	{
		$siretSiren = str_replace(' ', '', $siretSiren);
//		return array();
		$filters = array();
		if ($siretSiren != '') {
			$siren = substr($siretSiren, 0, 9);
			$filters['siren'] = array($siren);
		}
		if($name != ''){
			$filters['companyName'] = $name;
		}
		if(!count($filters)){
			$errors[] = 'Aucun filtre';
			return array();
		}

		$data = $this->getSoc($filters, $errors);

		$siretP = '';
		foreach($data as $result) {
			if (isset($result['formality'])) {
				$etab = array();
				if(isset($result['formality']['content']['personneMorale'])){
					$name = $result['formality']['content']['personneMorale']['identite']['entreprise']['denomination'];
					$adresse = $result['formality']['content']['personneMorale']['adresseEntreprise']['adresse'];
				}
				elseif(isset($result['formality']['content']['personnePhysique'])){
					$name = $result['formality']['content']['personnePhysique']['identite']['entrepreneur']['descriptionPersonne']['nom']. ' '. implode(' ', $result['formality']['content']['personnePhysique']['identite']['entrepreneur']['descriptionPersonne']['prenoms']);
					$adresse = $result['formality']['content']['personnePhysique']['adresseEntreprise']['adresse'];
				}
				$siren = $result['formality']['siren'];
				if(strlen($siretSiren) == 9 && $siren != $siretSiren)
					continue;
				$newAdresse = array();
				foreach ($this->filedsAdress as $field) {
					$newAdresse[$field] = $adresse[$field];
				}
				if(isset($result['formality']['content']['personneMorale']['etablissementPrincipal'])){
					$etab[] = $result['formality']['content']['personneMorale']['etablissementPrincipal'];
					$siretP = $result['formality']['content']['personneMorale']['etablissementPrincipal']['descriptionEtablissement']['siret'];
				}
				foreach ($result['formality']['content']['personneMorale']['autresEtablissements'] as $etablissement) {
					$etab[] = $etablissement;
				}

				if(isset($result['formality']['content']['personnePhysique']['etablissementPrincipal'])){
					$etab[] = $result['formality']['content']['personnePhysique']['etablissementPrincipal'];
					$siretP = $result['formality']['content']['personnePhysique']['etablissementPrincipal']['descriptionEtablissement']['siret'];
				}
				foreach ($result['formality']['content']['personnePhysique']['autresEtablissements'] as $etablissement) {
					$etab[] = $etablissement;
				}

				$newEtab = array();
				foreach ($etab as $etablissement){
					if($siretSiren == '' || strlen($siretSiren) != 14 || $siretSiren == $etablissement['descriptionEtablissement']['siret']){
						$tmpAdresse = array();
						foreach ($this->filedsAdress as $field) {
							$tmpAdresse[$field] = $etablissement['adresse'][$field];
						}
						$newEtab[] = array(
							'adresse' => $tmpAdresse,
							'siret'   => $etablissement['descriptionEtablissement']['siret'],
							'ape'	  => $etablissement['descriptionEtablissement']['codeApe']
						);
					}
				}
				$return[] = array(
					'name'           => $name,
					'adresse'        => $newAdresse,
					'siren'          => $siren,
					'etablissements' => $newEtab,
					'siretP'		 => $siretP
				);
			}



		}
		$this->setCache($filters, $return);
		return $return;
	}

	public function setCache($filters, $data){
		$_SESSION['last_inpi_api'] = $data;
	}

	public function getCache($filters = array()){
		if(isset($_SESSION['last_inpi_api']))
			return $_SESSION['last_inpi_api'];
		else
			return array();
	}

	public function getSoc($filters, &$errors = array())
	{
		if(!count($filters))
			$errors[] = 'Aucun filtre';
		else {
			$data = $this->execCurl('getSoc', array(
				'url_params' => $filters
			), $errors);

			return $data;
		}
	}


	public function connect(&$errors = array(), &$warnings = array())
	{
		if (!count($errors) && $this->isUserAccountOk($errors)) {
			$result = $this->execCurl('authenticate', array(
				'fields' => array(
					'username' => $this->getParam('login'),
					'password' => $this->getParam('password')
				)
			), $errors);

			if (isset($result['token']) && (string) $result['token']) {

				$this->updateParam('prod_api_key', $result['token']);
			} elseif (!count($errors)) {
				$errors[] = 'Echec de la connexion pour une raison inconnue';
			}
		}

		return (!count($errors));
	}

	public function getDefaultRequestsHeaders($request_name, &$errors = array())
	{
		$headers = array();

		if ($this->isUserAccountOk($errors)) {
			if ($request_name !== 'authenticate') {
				$token = $this->getParam('prod_api_key');

				if (!$this->getParam('prod_api_key'))
					$this->connect($errors);
				$headers['Authorization'] = 'Bearer '.$token;
			}
		}

		return $headers;
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
				'name'   => 'login',
				'title'  => 'Login',
				'value'	 => 'tommy@bimp.fr'
			), true, $warnings, $warnings);
			BimpObject::createBimpObject('bimpapi', 'API_ApiParam', array(
				'id_api' => $api->id,
				'name'   => 'password',
				'title'  => 'Password',
				'value'	 => '99iySWzjBbLq\/3&'
			), true, $warnings, $warnings);
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


	public function processRequestResponse($request_name, $response_code, $response_body, $response_headers = array(), &$infos = '', &$errors = array())
	{
		$return = $response_body;
		switch ($response_code) {
//            case '400':
//                $errors[] = 'Requête incorrecte';
//                break;

			case '401':
				$errors[] = 'Non autentifié';
				$return = 'unauthenticate';
				break;

			case '403':
				$errors[] = 'Accès refusé';
				break;

			case '404':
				$errors[] = 'API non trouvée';
				break;

			case '405':
				$errors[] = 'Format de la requête non supoorté';
				break;

			case '500':
				$errors[] = 'Erreur interne serveur';
				break;
		}

		if (isset($return['errorCode']) || isset($return['message'])) {
			$msg = '';
			if (isset($return['errorCode'])) {
				$msg .= $return['errorCode'];
			}
			if (isset($return['message'])) {
				$msg .= ($msg ? ' : ' : '') . $return['message'];
			}

			if ($msg) {
				$errors[] = $msg;
				die($msg);
			}
		}

		return $return;
	}
}

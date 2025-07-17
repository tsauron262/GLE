<?php

/*
 * curl -k -X POST -H "BWS-LOGIN: cGV0ZXJAYmltcC5mcg==" "https://erp.bimp.fr/bimp8/bimpwebservice/request.php?req=authenticate" -F pword=cVpwYlc4M0pTb0M2

  curl -k -X POST -H "BWS-TOKEN: Zkd5Y0RiTFhBek4xOTByWU5PVDBxVjM1" -H "BWS-LOGIN: cGV0ZXJAYmltcC5mcg==" "https://erp.bimp.fr/bimp8/bimpwebservice/request.php?req=getContractInfo"
 */

class BWSApi
{

	public $request_name = '';
	public $ws_user = null;
	public $params = array();
	public $errors = array();
	public $log_request = false;
	public $response_code = 200;

	protected $check_erp_user_rights = true;
	public static $requests = array(
		'authenticate'   => array(
			'desc'   => 'Authentification',
			'params' => array(
				'pword' => array('label' => 'Mot de passe', 'required' => 1)
			)
		),
		'getObjectData'  => array(
			'desc'   => 'Retourne toutes les données d\'un objet',
			'params' => array(
				'module'      => array('label' => 'Nom du module', 'require' => 1),
				'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
				'id'          => array('label' => 'ID de l\'objet', 'data_type' => 'id', 'require_if_missing' => 'ref'),
				'ref'         => array('label' => 'Référence de l\'objet', 'require_if_missing' => 'id'),
				'children'    => array('label' => 'Objets enfants à retourner')
			)
		),
		'getObjectValue' => array(
			'desc'   => 'Retourne la valeur d\'un champ objet',
			'info'   => 'Fournir au moins l\'un des deux éléments parmi: ID, Référence',
			'params' => array(
				'module'      => array('label' => 'Nom du module', 'require' => 1),
				'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
				'id'          => array('label' => 'ID de l\'objet', 'data_type' => 'id', 'require_if_missing' => 'ref'),
				'ref'         => array('label' => 'Référence de l\'objet', 'require_if_missing' => 'id'),
				'field'       => array('label' => 'Champ', 'required' => 1)
			)
		),
		'getObjectsList' => array(
			'desc'   => 'Retourne une liste d\'IDs d\'objets selon les filtres indiqués',
			'params' => array(
				'module'        => array('label' => 'Nom du module', 'require' => 1),
				'object_name'   => array('label' => 'Nom de l\'objet', 'required' => 1),
				'filters'       => array('label' => 'Filtres'),
				'panel_filters' => array('label' => 'Filtres JSON'),
				'order_by'      => array('label' => 'Trier par'),
				'order_way'     => array('label' => 'Sens du trie')
			)
		),
		'createObject'   => array(
			'desc'   => 'Ajoute un objet',
			'params' => array(
				'module'      => array('label' => 'Nom du module', 'require' => 1),
				'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
				'data'        => array('label' => 'Données')
			)
		),
		'updateObject'   => array(
			'desc'   => 'Met à jour un objet selon les données indiquées',
			'info'   => 'Fournir au moins l\'un des deux éléments parmi: ID, Référence',
			'params' => array(
				'module'      => array('label' => 'Nom du module', 'require' => 1),
				'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
				'id'          => array('label' => 'ID de l\'objet', 'data_type' => 'id', 'require_if_missing' => 'ref'),
				'ref'         => array('label' => 'Référence de l\'objet', 'require_if_missing' => 'id'),
				'data'        => array('label' => 'Données')
			)
		),
		'deleteObject'   => array(
			'desc'   => 'Supprime un objet',
			'info'   => 'ID de l\'objet obligatoire pour cette opération',
			'params' => array(
				'module'      => array('label' => 'Nom du module', 'require' => 1),
				'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
				'id'          => array('label' => 'ID de l\'objet', 'required' => 1)
			)
		),
		'findClient'     => array(
			'desc'   => 'Retourne les données d\'un client',
			'params' => array(
				'code' => array('label' => 'Code client'),
//                'nom'   => array('label' => 'Nom'),
//                'tel'   => array('label' => 'Numéro de téléphone'),
//                'email' => array('label' => 'Adresse e-mail')
			)
		),
//        'findContactsClient' => array(
//            'desc'   => 'Retourne une liste de contacts clients selon les termes de recherche',
//            'params' => array(
//                'code'    => array('Code client'),
//                'nom'     => array('label' => 'Nom'),
//                'prenom'  => array('label' => 'Prénom'),
//                'tel'     => array('label' => 'Numéro de téléphone'),
//                'email'   => array('label' => 'Adresse e-mail'),
//                'address' => array('label' => 'Adresse'),
//                'zip'     => array('label' => 'Code postal'),
//                'town'    => array('label' => 'Ville'),
//            )
//        )
		'createOrder'    => array(
			'desc'   => 'Création d\'une commande complète',
			'params' => array(
				'origine'       => array('label' => 'Origine', 'required' => 1),
				'client'        => array(
					'label' => 'Client', 'data_type' => 'array', 'required' => 1, 'sub_params' => array(
						'nom'                 => array('label' => 'Nom', 'required' => 1),
						'prenom'              => array('label' => 'Prénom', 'required' => 1),
						'email'               => array('label' => 'Adresse e-mail', 'required' => 1),
						'num_tel'             => array('label' => 'Numéro de téléphone'),
						'date_fin_validite'   => array('label' => 'Date de fin de validité du statut étudiant', 'data_type' => 'date'),
						'adresse_facturation' => array(
							'label' => 'Adresse de facturation', 'required' => 1, 'data_type' => 'array', 'sub_params' => array(
//						'nom'         => array('label' => 'Nom'),
//						'prenom'      => array('label' => 'Prénom'),
								'adresse'     => array('label' => 'Adresse', 'required' => 1),
								'code_postal' => array('label' => 'Code postal', 'required' => 1),
								'ville'       => array('label' => 'Ville', 'required' => 1),
//						'pays'        => array('label' => 'Pays', 'default' => 'France')
							)
						),
						'adresse_livraison'   => array(
							'label' => 'Adresse de livraison', 'data_type' => 'array', 'sub_params' => array(
								'nom'         => array('label' => 'Nom'),
								'prenom'      => array('label' => 'Prénom'),
								'adresse'     => array('label' => 'Adresse', 'required' => 1),
								'code_postal' => array('label' => 'Code postal', 'required' => 1),
								'ville'       => array('label' => 'Ville', 'required' => 1),
//						'pays'        => array('label' => 'Pays', 'default' => 'France')
							)
						)
					),
				),
				'lines'         => array(
					'label' => 'Lignes', 'data_type' => 'array', 'multiple' => 1, 'sub_params' => array(
						'ref_produit' => array('label' => 'Référence produit', 'required' => 1),
						'pu_ht'       => array('label' => 'Prix unitaire HT', 'data_type' => 'float', 'required' => 1),
						'tva_tx'      => array('label' => 'Taux de TVA', 'data_type' => 'float', 'required' => 1),
						'quantite'    => array('label' => 'Quantité', 'data_type' => 'float', 'required' => 1)
					)
				),
				'total_ttc'     => array('label' => 'Total TTC', 'data_type' => 'float', 'required' => 1),
				'mode_paiement' => array('label' => 'Mode de paiement', 'required' => 1)
			)
		)
	);

	public static function getInstance($request_name, $params)
	{
		$class_name = 'BWSApi';

		if (BimpCore::getExtendsVersion()) {
			if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . BimpCore::getExtendsVersion() . '/classes/BWSApi.php')) {
				$class_name = 'BWSApi_ExtVersion';

				if (!class_exists($class_name)) {
					require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . BimpCore::getExtendsVersion() . '/classes/BWSApi.php';
				}
			}
		}

		if (BimpCore::getExtendsEntity() != '') {
			if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/BWSApi.php')) {
				$class_name = 'BWSApi_ExtEntity';

				if (!class_exists($class_name)) {
					require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/BWSApi.php';
				}
			}
		}

		return new $class_name($request_name, $params);
	}

	public function __construct($request_name, $params)
	{
		$this->request_name = $request_name;
		$this->params = $params;
		$this->check_erp_user_rights = (int) BimpCore::getConf('check_erp_user_rights', null, 'bimpwebservice');

		ini_set('display_errors', 0);
		error_reporting(E_ERROR);
		register_shutdown_function(array($this, 'onExit'));
		set_error_handler(array($this, 'handleError'), E_ALL);
	}

	// Getters statics:

	public static function getRequestsArray()
	{
		$requests = array();

		foreach (static::$requests as $name => $data) {
			$requests[$name] = $name;
		}

		return $requests;
	}

	// Getters:

	public function getParam($param_path, $default_value = null)
	{
		return BimpTools::getArrayValueFromPath($this->params, $param_path, $default_value);
	}

	public function setParam($param_path, $value)
	{
		return BimpTools::setArrayValueFromPath($this->params, $param_path, $value);
	}

	public function getErrors()
	{
		return $this->errors;
	}

	protected function getChildrenData($object, $children)
	{
		$children_data = array();
		if (!empty($children)) {
			foreach ($children as $child_name => $child_data) {
				if (!is_array($child_data) && !is_object($child_data)) {
					$child_name = $child_data;
				}
				if (!$object->config->isDefined('objects/' . $child_name)) {
					$this->addError('INVALID_PARAMETER', 'L\'objet enfant "' . $child_name . '" n\'existe pas pour les ' . $object->getLabel('name_plur'));
				} else {
					if ($object->config->get('objects/' . $child_name . '/relation', '') === 'hasMany') {
						$child = $object->getChildObject($child_name);
						if (!is_a($child, 'BimpObject')) {
							$this->addError('INVALID_PARAMETER', 'L\'obtention des données des objets enfants "' . $child_name . '" n\'est pas possible');
						} elseif (!$this->ws_user->hasRight($this->request_name, $child->module, $child->object_name) ||
							($this->check_erp_user_rights && !$child->can('view'))) {
							$this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir les données des objets enfants "' . $child_name . '"');
						} else {
							$children_data[$child_name] = array();
							foreach ($object->getChildrenObjects($child_name) as $child_object) {
								$data = $child_object->getDataArray(true, $this->check_erp_user_rights);
								$data['children'] = (is_array($child_data) ? $this->getChildrenData($child_object, $child_data) : array());
								$children_data[$child_name][] = $data;
							}
						}
					} elseif ($object->config->get('objects/' . $child_name . '/relation', '') === 'hasOne') {
						$child = $object->getChildObject($child_name);
						if (!is_a($child, 'BimpObject')) {
							$this->addError('INVALID_PARAMETER', 'L\'obtention des données des objets enfants "' . $child_name . '" n\'est pas possible');
						} elseif (!$this->ws_user->hasRight($this->request_name, $child->module, $child->object_name) ||
							(0)) {
							$this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir les données des objets enfants "' . $child_name . '"');
						} else {
							$data = $child->getDataArray(true, $this->check_erp_user_rights);
							$data['children'] = (is_array($child_data) ? $this->getChildrenData($child, $child_data) : array());
							$children_data[$child_name] = $data;
						}
					} else {
						$this->addError('INVALID_PARAMETER', 'Pas de relations avec ' . $child_name);
					}
				}
			}
		}
		return $children_data;
	}

	// Traitements:

	public function init($login, $token)
	{
		// check requête:
		if (!isset(self::$requests[$this->request_name])) {
			$this->addError('REQUEST_INVALID', 'La requête "' . $this->request_name . '" n\'existe pas');
			$this->response_code = 400;
			return false;
		}

		if (!method_exists($this, 'wsRequest_' . $this->request_name)) {
			BimpCore::addlog('Méthode absente pour la requête webservice "' . $this->request_name . '"', Bimp_Log::BIMP_LOG_URGENT, 'ws', null, array(), true);
			$this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
			$this->response_code = 500;
			return false;
		}

		// check user:
		$login = base64_decode($login);
		$this->ws_user = BimpCache::findBimpObjectInstance('bimpwebservice', 'BWS_User', array(
			'email' => $login
		));

		if (!BimpObject::objectLoaded($this->ws_user)) {
			$this->addError('LOGIN_INVALIDE', 'Identifiant ou mot de passe du compte utilisateur invalide (L: ' . $login . ')');
			$this->response_code = 401;
			return false;
		}

		if ((int) $this->ws_user->getData('check_ip')) {
			$allowed_ips = $this->ws_user->getData('allowed_ip');
			if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
				$this->addError('IP_UNAUTHORIZED', 'Adresse IP non autorisée : ' . $_SERVER['REMOTE_ADDR']);
				$this->response_code = 401;
			}
		}

		if ($this->request_name !== 'authenticate') {
			$token = base64_decode($token);
			$err = '';
			if (!$this->ws_user->checkToken($token, $err)) {
				$this->addError('TOKEN_INVALIDE', ($err ? $err : 'Token invalide ou arrivé à expiration'));
				$this->response_code = 401;
				return false;
			}
		}

		// check params:
		if (isset(self::$requests[$this->request_name]['params'])) {
			$params_infos = array();
			if (!$this->checkParams(self::$requests[$this->request_name]['params'], '', $params_infos)) {
				$this->response_code = 400;
				return false;
			}
//			die(json_encode(array('infos' => $params_infos, 'params' => $this->params), JSON_UNESCAPED_UNICODE));
		}

		// check droit requête:
		if ($this->request_name !== 'authenticate') {
			$module = BimpTools::getArrayValueFromPath($this->params, 'module', 'any');
			$object_name = BimpTools::getArrayValueFromPath($this->params, 'object_name', 'any');

			if (!$this->ws_user->hasRight($this->request_name, $module, $object_name)) {
				$this->addError('FORBIDDEN', 'Opération non permise');
				$this->response_code = 403;
				return false;
			}
		}

		// Init USER
		$id_user = (int) $this->ws_user->getIdUserErpUsed();
		if (!$id_user) {
			BimpCore::addlog('Utilisateur ERP associé non défini', Bimp_Log::BIMP_LOG_URGENT, 'ws', $this->ws_user, array(), true);
			$this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
			$this->response_code = 500;
			return false;
		}

		global $user, $db;
		$user = new User($db);
		$user->fetch($id_user);
		$user->getrights();
		if (!BimpObject::objectLoaded($user)) {
			unset($user);
			BimpCore::addlog('Utilisateur ERP associé invalide', Bimp_Log::BIMP_LOG_URGENT, 'ws', $this->ws_user, array(
				'Erreur' => 'L\'utilisateur #' . $id_user . ' n\'existe pas'
			), true);
			$this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
			$this->response_code = 500;
			return false;
		}

		return true;
	}

	public function exec()
	{
		$response = array();

		if (!count($this->errors)) {
			$method = 'wsRequest_' . $this->request_name;
			if (method_exists($this, $method)) {
				$response = $this->{$method}();
			}
		}

		return $response;
	}

	public function addError($code, $message)
	{
		$this->errors[] = array(
			'code'    => $code,
			'message' => $message
		);
	}

	protected function checkParams($params_def, $path = '', &$infos = array())
	{
		$infos[] = 'CHECK PARAMS - Path : ' . $path;
		$check = true;
		foreach ($params_def as $param_name => $param_def) {
			$data_type = BimpTools::getArrayValueFromPath($param_def, 'data_type', 'string');
			$info = 'Check ' . $param_name . ' (' . $data_type . ') : ';

			$missing = false;
			$param_errors = array();

			$param = BimpTools::getArrayValueFromPath($this->params, $path . $param_name);

			if (is_null($param)) {
				$missing = true;
			} elseif (in_array($data_type, BC_Field::$missing_if_empty_types) && empty($param)) {
				$missing = true;
			} elseif ($data_type == 'json') {
				$param = BimpTools::json_decode_array($param, $param_errors);
				$data_type = 'array';

				if (empty($param)) {
					$missing = true;
					$param = array();
				}
			}

			if (!$missing) {
				if (!count($param_errors) && !BimpTools::checkValueByType($data_type, $param, $param_errors)) {
					if (empty($param_errors)) {
						$param_errors[] = 'Format invalide';
					}
				}

				if (count($param_errors)) {
					$info .= 'Err<pre>' . print_r($param_errors, true) . '</pre>';
					$this->addError('INVALID_PARAMETER', BimpTools::getMsgFromArray($param_errors, 'Paramètre "' . BimpTools::getArrayValueFromPath($param_def, 'label', $param_name) . '"', true));
					$check = false;
				} else {
					$info .= 'OK';
					$this->setParam($path . $param_name, $param);

					if (isset($param_def['sub_params'])) {
						if (isset($param_def['multiple']) && (int) $param_def['multiple']) {
							if ($check && is_array($param) && count($param)) {
								foreach ($param as $idx => $item) {
									if (!$this->checkParams($param_def['sub_params'], $path . $param_name . '/' . $idx . '/', $infos)) {
										$check = false;
									}
								}
							}
						} else {
							if (!$this->checkParams($param_def['sub_params'], $path . $param_name . '/', $infos)) {
								$check = false;
							}
						}

					}
				}
			} else {
				$info .= 'MISSING';
				$required = false;
				if (BimpTools::getArrayValueFromPath($param_def, 'required', 0)) {
					$required = true;
				} else {
					$required_if_param_name = BimpTools::getArrayValueFromPath($param_def, 'required_if', '');
					if ($required_if_param_name) {
						$required_if_param_path = '';
						foreach (explode('/', $required_if_param_name) as $p) {
							$required_if_param_path .= ($required_if_param_path ? '/sub_params/' : '') . $p;
						}
						$required_if_data_type = BimpTools::getArrayValueFromPath(self::$requests, $this->request_name . '/params/' . $required_if_param_path . '/data_type', 'string');
						$required_if_value = $this->getParam($required_if_param_name);
						if (is_null($required_if_value) ||
							(in_array($required_if_data_type, BC_Field::$missing_if_empty_types) && empty($required_if_value)) ||
							($required_if_data_type === 'bool' && (int) $required_if_value)) {
							$required = true;
						}
					}
				}
				if ($required) {
					$info .= ' - REQUIRED';
					$check = false;
					$this->addError('MISSING_PARAMETER', 'Paramètre obligatoire absent: "' . BimpTools::getArrayValueFromPath($param_def, 'label', $param_name) . '" (' . $path . $param_name . ')');
				}
			}

			$infos[] = $info;
		}

		return $check;
	}

	protected function getObjectInstance()
	{
		$module = BimpTools::getArrayValueFromPath($this->params, 'module', 'any');
		$object_name = BimpTools::getArrayValueFromPath($this->params, 'object_name', 'any');
		return BimpObject::getInstance($module, $object_name);
	}

	protected function findObject(BimpObject $obj_instance = null)
	{
		if (is_null($obj_instance)) {
			$obj_instance = $this->getObjectInstance();
		}

		$object = null;
		$id_object = 0;
		if (isset(self::$requests[$this->request_name]['params']['id'])) {
			$id_object = (int) BimpTools::getArrayValueFromPath($this->params, 'id', 0);

			if ($id_object) {
				$object = BimpCache::getBimpObjectInstance($obj_instance->module, $obj_instance->object_name, $id_object);

				if (!BimpObject::objectLoaded($object)) {
					$this->addError('UNFOUND', 'Aucun' . $obj_instance->e() . ' ' . $obj_instance->getLabel() . ' trouvé' . $obj_instance->e() . ' pour l\'ID ' . $id_object);
					return null;
				}
			} elseif (BimpTools::getArrayValueFromPath(self::$requests, $this->request_name . '/params/id/required', 0)) {
				$this->addError('OBJECT_IDENTIFIER_MISSING', 'Veuillez renseigner l\'identifiant de l\'objet demandé');
				return null;
			}
		}

		if (!$id_object && isset(self::$requests[$this->request_name]['params']['ref'])) {
			$ref_object = BimpTools::getArrayValueFromPath($this->params, 'ref', '');

			if ($ref_object) {
				$ref_prop = $obj_instance->getRefProperty();

				if (!$ref_prop) {
					$this->addError('NO_REF_PROPERTY', 'Aucun champ de type "Référence" pour les ' . $obj_instance->getLabel('name_plu') . '. Veuillez renseigner un ID');
				} else {
					$object = BimpCache::findBimpObjectInstance($obj_instance->module, $obj_instance->object_name, array(
						$ref_prop => $ref_object
					));

					if (!BimpObject::objectLoaded($object)) {
						$this->addError('UNFOUND', 'Aucun' . $obj_instance->e() . ' ' . $obj_instance->getLabel() . ' trouvé' . $obj_instance->e() . ' pour la référence "' . $ref_object . '"');
						return null;
					}
				}
			} else {
				$this->addError('OBJECT_IDENTIFIER_MISSING', 'Veuillez renseigner l\'identifiant ou la référence de l\'objet demandé');
				return null;
			}
		}

		if (BimpObject::objectLoaded($object)) {
			if ($this->check_erp_user_rights) {
				if (!$object->can('view')) {
					$this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir les données ' . $object->getLabel('of_the') . ' ' . $object->getRef(true));
					return null;
				}
			}
			// Check objet selon les filtres autorisés:
			$ids = $this->checkObjectRightFilters($object, array($object->id));
			if (empty($ids)) {
				if (!count($this->errors)) {
					$this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir les données ' . $object->getLabel('of_the') . ' ' . $object->getRef(true));
				}
				return null;
			}
			return $object;
		} elseif (!count($this->errors)) {
			$this->addError('UNFOUND', 'Aucun' . $obj_instance->e() . ' ' . $obj_instance->getLabel() . ' trouvé' . $obj_instance->e());
		}

		return null;
	}

	protected function checkObjectRightFilters($obj_instance, $ids_objects)
	{
		$valid_ids = array();

		if (is_a($this->ws_user, 'BWS_User') && is_a($obj_instance, 'BimpObject')) {
			$id_right = $this->ws_user->getRightId($this->request_name, $obj_instance->module, $obj_instance->object_name);
			if ($id_right) {
				$right = BimpCache::getBimpObjectInstance('bimpwebservice', 'BWS_ProfileRight', $id_right);
				if (BimpObject::objectLoaded($right)) {
					$filters = $right->getData('obj_filters');
					if (!empty($filters)) {
						$filters_errors = array();
						$bc_filters = new BC_FiltersPanel($obj_instance);
						$bc_filters->setFilters($filters);
						$valid_ids = $bc_filters->applyFiltersToObjectIds($ids_objects, $filters_errors);

						if (count($filters_errors)) {
							$this->addError('INTERNAL_ERROR', 'Erreur interne - opération non disponible actuellement');
							BimpCore::addlog('ERREUR WEBSERVICE', Bimp_Log::BIMP_LOG_URGENT, 'ws', $right, array(
								'Message' => 'Erreurs lors de l\'application des filtres du droit webservice #' . $right->id,
								'Requête' => $this->request_name,
								'Module'  => $obj_instance->module,
								'Objet'   => $obj_instance->object_name,
								'Erreurs' => $filters_errors
							), true);
							return array();
						}
					} else {
						$valid_ids = $ids_objects;
					}
				}
			}
		}

		return $valid_ids;
	}

	public function handleError($level, $msg, $file, $line)
	{
		global $bimp_errors_handle_locked;

		if ($bimp_errors_handle_locked) {
			return;
		}

		ini_set('display_errors', 0);

		switch ($level) {
			case E_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
				$this->response_code = 500;
				$txt = '';
				$txt .= '<strong>ERP:</strong> ' . DOL_URL_ROOT . "\n";
				$txt .= '<strong>Requête:</strong> ' . $this->request_name . "\n";

				if (is_a($this->ws_user, 'BWS_User') && BimpObject::objectLoaded($this->ws_user)) {
					$txt .= '<strong>Utilisateur ws:</strong> #' . $this->ws_user->id . ' - ' . $this->ws_user->getName() . ' (' . $this->ws_user->getData('email') . ')' . "\n";
				}

				$txt .= "\n";

				$txt .= 'Le <strong>' . date('d / m / Y') . ' à ' . date('H:i:s') . "\n\n";
				$txt .= $file . ' - Ligne ' . $line . "\n\n";
				$txt .= $msg;

				if (!empty($this->params)) {
					$txt .= "\n\n";
					$txt .= 'Params requête: ' . "\n";
					$txt .= '<pre>' . print_r($this->params, 1) . '</pre>';
				}

				$code = 'webservice_error_fatal';
				$sujet = 'ERREUR FATALE WEBSERVICE - ' . str_replace('/', '', DOL_URL_ROOT);
				BimpUserMsg::envoiMsg($code, $sujet, $txt);

				BimpCore::addlog('ERREUR FATALE WEBSERVICE - ' . $msg, Bimp_Log::BIMP_LOG_URGENT, 'ws', null, array(
					'Requête' => $this->request_name,
					'User ws' => (BimpObject::objectLoaded($this->ws_user) ? $this->ws_user->getLink() : 'Non défini'),
					'Fichier' => $file,
					'Ligne'   => $line
				));

				die(json_encode(array(
					'errors' => array(
						array(
							'code'    => 'INTERNAL_ERROR',
							'message' => 'Erreur interne - opération indisponible pour le moment'
						)
					)
				)));
				break;
		}
	}

	public function onExit()
	{
		$error = error_get_last();

		// On cache les identifiants de la base
		$error = preg_replace('/mysqli->real_connect(.*)3306/', 'mysqli->real_connect(adresse_caché, login_caché, mdp_caché, bdd_caché, port_caché', $error);

		if (isset($error['type']) && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR))) {
			$this->handleError(E_ERROR, $error['message'], $error['file'], $error['line']);
		}
	}

	// Requêtes:

	protected function wsRequest_authenticate()
	{
		$response = array();

		if (!count($this->errors)) {
			$pword = $this->getParam('pword', '');

			if (!$this->ws_user->checkPWord($pword)) {
				$pword = base64_decode($this->getParam('pword', ''));

				if (!$this->ws_user->checkPWord($pword)) {
					$this->addError('LOGIN_INVALIDE', 'Identifiant ou mot de passe du compte utilisateur invalide - (PW)');
					$this->response_code = 401;
					return array();
				}
			}

			$errors = array();
			$response = $this->ws_user->generateToken($errors);

			if (count($errors)) {
				BimpCore::addlog('Erreur lors de la génération d\'un token d\'authentification', Bimp_Log::BIMP_LOG_ERREUR, 'ws', $this->ws_user, array(
					'Erreurs' => $errors
				));
				$this->addError('INTERNAL_ERROR', 'Une erreur est survenue - échec de l\'authentification');
				$this->response_code = 500;
			}
		}

		return $response;
	}

	protected function wsRequest_getObjectData()
	{
		$response = array();

		if (!count($this->errors)) {
			$object = $this->findObject();
			if (BimpObject::objectLoaded($object)) {
				// Check des children:
				$children = BimpTools::getArrayValueFromPath($this->params, 'children', '');
				if ($children) {
					$children = json_decode($children, true);
					if (!is_array($children)) {
						$this->addError('INVALID_PARAMETER', 'Paramètre "children" invalide');
					}
				}

				/*
				 * Todo implémenter recursivité des child exemple pour contrat array('lines'=>array('produit'))
				 * curl -s -X POST -H "BWS-LOGIN: cC50a2F0Y2hlbmtvQGJpbXAuZnI=" -H "BWS-TOKEN:RFdVNTJ0WENEcmkwdVByZlM4Z0ZSQXc2" 'https://erp.bimp.fr/bimp8/bimpwebservice/request.php?req=getObjectData' -F module=bimpcontract -F object_name=BContract_contrat -F ref=CDP2401-0003 -F "children={\"lines\":[\"produit\"]}
				 */
				$children_data = $this->getChildrenData($object, $children);

				$response = array(
					'object_data' => $object->getDataArray(true, $this->check_erp_user_rights),
					'children'    => $children_data
				);
			}
		}

		return $response;
	}

	protected function wsRequest_getObjectValue()
	{
		$response = array();

		if (!count($this->errors)) {
			$obj_instance = $this->getObjectInstance();
			$field = BimpTools::getArrayValueFromPath($this->params, 'field', '');
			if (!$field) {
				$this->addError('MISSING_PARAMETER', 'Paramètre "field" absent');
			} else {
				if (!$obj_instance->field_exists($field)) {
					$this->addError('INVALID_PARAMETER', 'Le champ "' . $field . '" n\'existe pas pour les ' . $obj_instance->getLabel('name_plur'));
				} else {
					$object = $this->findObject($obj_instance);
					if (BimpObject::objectLoaded($object)) {
						if ($this->check_erp_user_rights && !$object->canViewField($field)) {
							$this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir la valeur du champ "' . $field . '"');
						} else {
							$response = array(
								$field => $object->getData($field)
							);
						}
					}
				}
			}
		}

		return $response;
	}

	protected function wsRequest_getObjectsList()
	{
		$response = array();

		if (!count($this->errors)) {
			$filters = BimpTools::getArrayValueFromPath($this->params, 'filters', '');
			$joins = array();

			$list = array();
			$obj_instance = $this->getObjectInstance();

			if ($filters) {
				$filters = json_decode($filters, 1);
			} else {
				$panel_filters = BimpTools::getArrayValueFromPath($this->params, 'panel_filters', '');
				$panel = new BC_FiltersPanel($obj_instance);
				$panel->setFilters(json_decode($panel_filters, 1));
				$filters = array();
				$panel->getSqlFilters($filters, $joins);
			}

			if (empty($filters)) {
				$this->addError('INVALID_PARAMETERS', 'Vous devez obligatoirement spécifier au moins un filtre');
			}


			if ($this->check_erp_user_rights && !$obj_instance->can('view')) {
				$this->addError('UNAUTHORIZED', 'Vous n\'avez pas la permission d\'obtenir une liste ' . $obj_instance->getLabel('of_plur'));
			} else {
				$primary = $obj_instance->getPrimary();

				$order_by = BimpTools::getArrayValueFromPath($this->params, 'order_by', $primary);
				$order_way = BimpTools::getArrayValueFromPath($this->params, 'order_way', 'ASC');

				$rows = $obj_instance->getList($filters, null, null, $order_by, $order_way, 'array', array($primary), $joins);

				if (!empty($rows)) {
					foreach ($rows as $r) {
						$list[] = (int) $r[$primary];
					}
				}

				// Check liste:
				$list = $this->checkObjectRightFilters($obj_instance, $list);
			}

			$response['list'] = $list;
		}

		return $response;
	}

	protected function wsRequest_createObject()
	{
		$response = array();

		if (!count($this->errors)) {
			$obj_instance = $this->getObjectInstance();
			$data = BimpTools::getArrayValueFromPath($this->params, 'data', '');

			if ($data) {
				$data = json_decode($data, 1);
			}

			if (empty($data)) {
				$this->addError('INVALID_PARAMETERS', 'Données absentes pour la création ' . $obj_instance->getLabel('of_the'));
			}

			$errors = array();
			$warnings = array();
			$obj = BimpObject::createBimpObject($obj_instance->module, $obj_instance->object_name, $data, ($this->check_erp_user_rights ? false : true), $errors, $warnings, false, true);

			if (!BimpObject::objectLoaded($obj)) {
				$this->addError('FAIL', BimpTools::getMsgFromArray($errors, null, true));
			} else {
				$obj->addObjectLog('Créé par utilisateur webservice: ' . $this->ws_user->getLink());
				$response = array('success' => 1, 'id' => $obj->id);
			}
		}

		return $response;
	}

	protected function wsRequest_updateObject()
	{
		$response = array();

		if (!count($this->errors)) {
			$obj_instance = $this->getObjectInstance();
			$object = $this->findObject($obj_instance);
			if (BimpObject::objectLoaded($object)) {
				$data = BimpTools::getArrayValueFromPath($this->params, 'data', '');

				if ($data) {
					$data = json_decode($data, 1);
				}

				if (empty($data)) {
					$this->addError('INVALID_PARAMETERS', 'Données à mettre à jour absentes pour l\'édition' . $obj_instance->getLabel('of_the'));
				} else {
					$errors = $object->validateArray($data);
					if (!count($errors)) {
						$warnings = array();
						$errors = $object->update($warnings, ($this->check_erp_user_rights ? false : true));
					}

					if (count($errors)) {
						$this->addError('FAIL', BimpTools::getMsgFromArray($errors, 'Echec de la mise à jour ' . $object->getLabel('of_the') . ' ' . $object->getRef(true), true));
					} else {
						$object->addObjectLog('Edition par utilisateur webservice: ' . $this->ws_user->getLink() . '<br/><br/>Données mises à jour: <pre>' . print_r($data) . '</pre>');
						$response = array('success' => 1, 'id' => $object->id);
					}
				}
			}
		}

		return $response;
	}

	protected function wsRequest_deleteObject()
	{
		$response = array();

		if (!count($this->errors)) {
			$obj_instance = $this->getObjectInstance();
			$object = $this->findObject($obj_instance);
			if (BimpObject::objectLoaded($object)) {
				$ref_object = $object->getRef(true);
				$warnings = array();
				$errors = $object->delete($warnings, ($this->check_erp_user_rights ? false : true));

				if (count($errors)) {
					$this->addError('FAIL', BimpTools::getMsgFromArray($errors, 'Echec de la suppression ' . $obj_instance->getLabel('of_the') . ' ' . $ref_object, true));
				} else {
					$response = array('success' => 1);
				}
			}
		}

		return $response;
	}

	protected function wsRequest_findClient()
	{
		$response = array();

		if (!count($this->errors)) {
			$code_client = $this->getParam('code', '');

			if (!$code_client) {
				$this->addError('INVALID_PARAMETERS', 'Veuillez renseigner le code client');
			} else {
				$client = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Client', array(
					'code_client' => $code_client
				), true, false);

				if (!BimpObject::objectLoaded($client)) {
					$this->addError('UNFOUND', 'Aucun client trouvé pour le code "' . $code_client . '"');
				} else {
					$client_data = array(
						'nom'         => (string) $client->getData('nom'),
						'adresse'     => (string) $client->getData('address'),
						'code_postal' => (string) $client->getData('zip'),
						'ville'       => (string) $client->getData('town'),
						'pays'        => (string) $client->displayDataDefault('fk_pays', 1, 1),
						'tel'         => (string) $client->getData('phone'),
						'email'       => (string) $client->getData('email'),
						'contacts'    => array()
					);

					foreach ($client->getChildrenObjects('contacts') as $contact) {
						$client_data['contacts'][] = array(
							'nom'         => (string) $contact->getData('lastname'),
							'prenom'      => (string) $contact->getData('firstname'),
							'adresse'     => (string) $contact->getData('address'),
							'code_postal' => (string) $contact->getData('zip'),
							'ville'       => (string) $contact->getData('town'),
							'pays'        => (string) $contact->displayDataDefault('fk_pays', 1, 1),
							'tel_perso'   => (string) $contact->getData('phone_perso'),
							'tel_mobile'  => (string) $contact->getData('phone_mobile'),
							'tel_pro'     => (string) $contact->getData('phone'),
							'poste'       => (string) $contact->getData('poste'),
							'email'       => (string) BimpTools::cleanEmailsStr($contact->getData('email')),
						);
					}
				}

				$response = $client_data;
			}
		}

		return $response;
	}

	protected function wsRequest_createOrder()
	{
		global $db, $user, $langs;
		$bdb = new BimpDb($db);
		$db->begin();

		$response = array();
		$ref_commande = '';
		$ref_client = '';

		if (!count($this->errors)) {
			$errors = array();
			$warnings = array();

			$origine = $this->getParam('origine');
			$client_email = $this->getParam('client/email');
			$nom = $this->getParam('client/nom');
			$prenom = $this->getParam('client/prenom');
			$type_educ_fin_validite = '';

			$total_ttc = $this->getParam('total_ttc');
			$mode_paiement = $this->getParam('mode_paiement');

			$id_mode_paiement = (int) $bdb->getValue('c_paiement', 'id', 'code = \'' . $mode_paiement . '\'');

			if (!(int) $id_mode_paiement) {
				$this->addError('INVALID_PARAMETER', 'Mode de paiement invalide : ' . $mode_paiement);
			}

			$paiement_amount = $total_ttc;
			$id_bank_account = 0;

			$client_data = array(
				'nom'     => ucfirst($prenom) . ' ' . strtoupper($nom),
				'email'   => $client_email,
				'phone'   => $this->getParam('client/num_tel', ''),
				'client'  => 1,
				'address' => $this->getParam('client/adresse_facturation/adresse'),
				'zip'     => $this->getParam('client/adresse_facturation/code_postal'),
				'town'    => $this->getParam('client/adresse_facturation/ville'),
			);

			$commande_data = array();

			switch ($origine) {
				case 'site_educ':
					$client_data['fk_typent'] = 8; // Particulier
					$client_data['type_educ'] = 'E4'; // Étudiant

					$commande_data = array(
						'libelle'           => 'Commande en ligne',
						'entrepot'          => 164, // LD
						'ef_type'           => 'E',
						'fk_cond_reglement' => 20, // A la commande
						'fk_mode_reglement' => $id_mode_paiement,
						'fk_input_reason'   => 0
					);

					$dataSecteur = BimpCache::getSecteursData();
					$id_bank_account = (int) (isset($dataSecteur['E']['id_default_bank_account']) ? $dataSecteur['E']['id_default_bank_account'] : BimpCore::getConf('id_default_bank_account', (!empty($conf->global->FACTURE_RIB_NUMBER) ? $conf->global->FACTURE_RIB_NUMBER : 0)));
					break;

				default:
					$errors[] = 'Origine invalide';
					break;
			}

			// vérif des lignes.
			$lines = $this->getParam('lines', array());
			$commande_lines = array();

			if (empty($lines)) {
				$errors[] = 'Aucune ligne de commande spécifiée';
			} else {
				$i = 0;
				foreach ($lines as $idx => $line) {
					$i++;

					/** @var Bimp_Product $prod */
					$prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
						'ref' => $line['ref_produit']
					));

					if (!BimpObject::objectLoaded($prod)) {
						$errors[] = 'Ligne n°' . $i . ' : aucun produit trouvé pour la référence "' . $line['ref_produit'] . '"';
					} else {
						$lines[$idx]['id_product'] = $prod->id;
					}
				}
			}

			if (!count($errors) && !count($this->errors)) {
				$client = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Client', array(
					'email' => $client_email
				), true, false);

				if (!BimpObject::objectLoaded($client)) {
					$client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
				}

				$client_errors = $client->validateArray($client_data);

				if ($client->field_exists('type_educ_fin_validite')) {
					$type_educ_fin_validite = $this->getParam('client/date_fin_validite', '');
					$cur_type_educ_fin_validite = $client->getData('type_educ_fin_validite');
					$dt_tomorrow = new DateTime();
					$dt_tomorrow->add(new DateInterval('P1D'));
					$tomorrow = $dt_tomorrow->format('Y-m-d');
					if (!$cur_type_educ_fin_validite || ($type_educ_fin_validite && $type_educ_fin_validite > $cur_type_educ_fin_validite) || (!$type_educ_fin_validite && $cur_type_educ_fin_validite < $tomorrow)) {
						if (!$type_educ_fin_validite) {
							$type_educ_fin_validite = $tomorrow;
						}
						$client->set('type_educ_fin_validite', $type_educ_fin_validite);
					}
				}

				if (!count($client_errors)) {
					if (BimpObject::objectLoaded($client)) {
						$client_errors = $client->update($warnings, true);
					} else {
						$client_errors = $client->create($warnings, true);
					}
				}

				if (count($client_errors)) {
					$errors[] = BimpTools::getMsgFromArray($client_errors, 'Erreurs client', true);
				} else {
					$client->fetch($client->id);
					$ref_client = $client->getRef();
					$contact_liv = null;
					$address_liv = $this->getParam('client/adresse_livraison');
					if (!empty($address_liv)) {
						$contact_data = array(
							'fk_soc'    => $client->id,
							'firstname' => (!empty($address_liv['prenom']) ? $address_liv['prenom'] : $prenom),
							'lastname'  => (!empty($address_liv['nom']) ? $address_liv['nom'] : $nom),
							'address'   => (!empty($address_liv['adresse']) ? $address_liv['adresse'] : $client_data['address']),
							'zip'       => (!empty($address_liv['code_postal']) ? $address_liv['code_postal'] : $client_data['zip']),
							'town'      => (!empty($address_liv['ville']) ? $address_liv['ville'] : $client_data['town']),
							'email'     => $client_data['email']
						);
						$contact_liv = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Contact', $contact_data);

						if (!BimpObject::objectLoaded($contact_liv)) {
							$contact_liv = BimpObject::getInstance('bimpcore', 'Bimp_Contact');
							$contact_liv->validateArray($contact_data);
							$contact_errors = $contact_liv->create($warnings, true);

							if (count($contact_errors)) {
								$errors[] = BimpTools::getMsgFromArray($contact_errors, 'Echec de l\'enregistrement de l\'adresse de livraison', true);
							}
						}
					}

					if (!count($errors)) {
						$commande_data['fk_soc'] = $client->id;
						$commande_data['date_commande'] = date('Y-m-d');
						$commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
						$commande->validateArray($commande_data);

						$commande_errors = $commande->create($warnings, true);

						if (count($commande_errors)) {
							$errors[] = BimpTools::getMsgFromArray($commande_errors, 'Echec de création de la commande', true);
						} else {
							if (BimpObject::objectLoaded($contact_liv)) {
								if ($commande->dol_object->add_contact($contact_liv->id, 'SHIPPING', 'external') <= 0) {
									$errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($commande->dol_object), 'Echec de l\'enregistrement de l\'adresse de livraison dans la commande');
								}
							}
							// Ajout des lignes :
							$i = 0;
							$total_lines_ttc = 0;
							foreach ($lines as $line) {
								$i++;

								/** @var Bimp_Product $product */
								$product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $line['id_product']);

								$new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
								$new_line->set('id_obj', $commande->id);
								$new_line->pu_ht = $line['pu_ht'];
								$new_line->tva_tx = $line['tva_tx'];
								$new_line->qty = $line['quantite'];
								$new_line->id_product = $product->id;
								$new_line->pa_ht = $product->getCurrentPaHt();

								$crt_ra = $product->getRemiseArriere('crt');
								if (BimpObject::objectLoaded($crt_ra)) {
									$new_line->set('remise_crt', 1);
								}

								$line_errors = $new_line->create($warnings, true);

								if (count($line_errors)) {
									$errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne n° ' . $i . ' à la commande (Produit ' . $product->getRef() . ')', true);
								}

								$total_lines_ttc += ($new_line->pu_ht * $new_line->qty * (1 + ($new_line->tva_tx / 100)));
							}

							$diff = $total_lines_ttc - $total_ttc;
							if (abs($diff) > 0) {
								if (abs($diff) > 1) {
									$errors[] = 'Le total TTC calculé (' . $total_lines_ttc . ') ne correspond pas au montant du paiement (' . $paiement_amount . ')';
								} else {
									$warnings[] = 'ATTENTION : il y a un écart de ' . $diff . ' euros entre le montant payé (' . $paiement_amount . ') et le total ttc calculé (' . $total_lines_ttc . ').';
								}
							}


							if (!count($errors)) {
								$fac_errors = array();

								// Créa fac accompte paiement
								$fac_acompte = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
									'fk_soc'            => $client->id,
									'type'              => 3,
									'datef'             => dol_now(),
									'entrepot'          => $commande->getData('entrepot'),
									'ef_type'           => $commande->getData('ef_type'),
									'fk_cond_reglement' => $commande->getData('fk_cond_reglement'),
									'fk_mode_reglement' => $id_mode_paiement
								), true, $fac_errors);

								if (count($fac_errors)) {
									$errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture d\'acompte pour le paiement', true);
								} else {
									addElementElement('commande', 'facture', $commande->id, $fac_acompte->id);

									$fac_acompte_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
									$fac_acompte_line->set('id_obj', $fac_acompte->id);
									$fac_acompte_line->set('type', 3);
									$fac_acompte_line->desc = 'Acompte';
									$fac_acompte_line->pu_ht = $paiement_amount / 1.2;
									$fac_acompte_line->pa_ht = $paiement_amount / 1.2;
									$fac_acompte_line->tva_tx = 20;
									$line_errors = $fac_acompte_line->create($warnings, true);

									if (count($line_errors)) {
										$errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne à la facture d\'acompte pour le paiement', true);
									} elseif ($fac_acompte->dol_object->validate($user) <= 0) {
										$errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($fac_acompte->dol_object), 'Echec de la validation de la facture d\'acompte');
									} else {
										// Création du paiement:
										BimpTools::loadDolClass('compta/paiement', 'paiement');
										$payement = new Paiement($db);
										$payement->amounts = array($fac_acompte->id => $paiement_amount);
										$payement->datepaye = dol_now();
										$payement->paiementid = (int) $id_mode_paiement;
										if ($payement->create($user) <= 0) {
											$errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Des erreurs sont survenues lors de la création du paiement de la facture d\'acompte');
										} else {
											// Ajout du paiement au compte bancaire:
											if ($payement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_bank_account, '', '') <= 0) {
												$warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Echec de l\'ajout de l\'acompte au compte bancaire #' . $id_bank_account);
											}

											$fac_acompte->dol_object->set_paid($user);
										}

										// Création de la remise client:
										BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
										$discount = new DiscountAbsolute($db);
										$discount->description = "Acompte";
										$discount->socid = $client->id;
										$discount->fk_facture_source = $fac_acompte->id;
										$discount->amount_ht = $paiement_amount / 1.2;
										$discount->amount_ttc = $paiement_amount;
										$discount->amount_tva = $paiement_amount - ($paiement_amount / 1.2);
										$discount->tva_tx = 20;
										if ($discount->create($user) <= 0) {
											$warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Des erreurs sont survenues lors de la création de la remise sur acompte');
										} else {
											$line_errors = $commande->insertDiscount((int) $discount->id);

											if (count($line_errors)) {
												$warnings[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de l\'ajout de l\'acompte à la commande');
											}
										}
									}
								}
							}

							if (!count($errors)) {
								// Validation auto commande :
								BimpTools::cleanDolEventsMsgs();
								$result = $commande->dol_object->valid($user);
								$comm_errors = BimpTools::getDolEventsMsgs(array('errors'));
								$warnings = array_merge($warnings, BimpTools::getDolEventsMsgs(array('warnings')));

								if (count($comm_errors)) {
									$warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Echec de la validation de la commande');
								} else {
									$commande->dol_object->generateDocument($commande->getModelPdf(), $langs);
								}

								$msg = 'Nouvelle commande depuis le site e-commerce éducaton';

								if (count($warnings)) {
									$msg .= '<br/>Attention, des erreurs sont survenues lors de la création de la commande. Veuillez vérifier la commande et effectuer les corrections manuelles si nécessaire. <br/>';
									$msg .= BimpTools::getMsgFromArray($warnings);
								}

								$id_dest_group = 180; // Groupe "Education"
								$commande->addNote($msg, null, 0, 1, '', 1, BimpNote::BN_DEST_GROUP, $id_dest_group);

								// Mail confirmation :
								$commande->fetch($commande->id);
								$ref_commande = $commande->getRef();
								$primary = BimpCore::getParam('public_email/primary');

								$subject = 'Confirmation de commande';
								$message = 'Bonjour, <br/><br/>';
								$message .= 'Merci pour votre commande...<br/><br/>';
								$message .= 'Récapitulatif : <br/>';
								$message .= '<table style="width: 100%" cellspacing="0">';
								$message .= '<thead>';
								$message .= '<tr>';
								$message .= '<th style="padding: 5px; background-color: ' . $primary . '; text-align: left; color: #ffffff">Produit</th>';
								$message .= '<th style="padding: 5px; background-color: ' . $primary . '; text-align: left; color: #ffffff">Prix unitaire HT</th>';
								$message .= '<th style="padding: 5px; background-color: ' . $primary . '; text-align: left; color: #ffffff">TVA</th>';
								$message .= '<th style="padding: 5px; background-color: ' . $primary . '; text-align: left; color: #ffffff">Qté</th>';
								$message .= '<th style="padding: 5px; background-color: ' . $primary . '; text-align: left; color: #ffffff">Total TTC</th>';
								$message .= '</tr>';
								$message .= '</thead>';
								$message .= '<tbody>';

								foreach ($lines as $line) {
									$product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $line['id_product']);
									$message .= '<td style="padding: 5px; border-bottom: 1px solid #999999;">';
									$message .= '<b>' . $product->getRef() . '</b><br/>';
									$message .= $product->getName();
									$message .= '</td>';

									$message .= '<td style="padding: 5px; border-bottom: 1px solid #999999;">';
									$message .= BimpTools::displayMoneyValue($line['pu_ht']);
									$message .= '</td>';

									$message .= '<td style="padding: 5px; border-bottom: 1px solid #999999;">';
									$message .= BimpTools::displayFloatValue($line['tva_tx'], 2, ',', 0, 0, 0, 0, 1, 1) . ' %';
									$message .= '</td>';

									$message .= '<td style="padding: 5px; border-bottom: 1px solid #999999;">';
									$message .= BimpTools::displayFloatValue($line['quantite'], 4, ',', 0, 0, 0, 0, 1, 1);
									$message .= '</td>';

									$message .= '<td style="padding: 5px; border-bottom: 1px solid #999999;">';
									$message .= BimpTools::displayMoneyValue((float) $line['pu_ht'] * (1 + ((float) $line['tva_tx'] / 100)) * (float) $line['quantite']);
									$message .= '</td>';
								}

								$message .= '</tbody>';
								$message .= '</table>';

								$mail = new BimpMail($commande, $subject, $client_email, '', $message);
								$mail_errors = array();
								if (!$mail->send($mail_errors)) {
									$warnings[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de confirmation au client');
								}
							}
						}
					}
				}
			}

			$response = array(
				'success'      => 0,
				'ref_commande' => '',
				'ref_client'   => '',
				'warnings'     => $warnings
			);

			if (count($errors)) {
				$db->rollback();
				$this->addError('CREATION_FAIL', BimpTools::getMsgFromArray($errors, '', true));
				$this->response_code = 400;

				BimpCore::addlog('Echec de la création d\'une commande depuis le site e-commerce éducation', 4, 'bimpcomm', null, array(
					'Erreurs'             => $errors,
					'Warnings'            => $warnings,
					'Paramètres requêtes' => $this->params
				));
			} else {
				$response['success'] = 1;
				$response['ref_commande'] = $ref_commande;
				$response['ref_client'] = $ref_client;

				$db->commitAll();
			}
		}

		return $response;
	}
}

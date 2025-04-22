<?php

class Bimp_Client_ExtEntity extends Bimp_Client
{
	public static $statusRdc = array(
		0 => array('label' => 'N/C', 'icon' => 'fas_calendar-day', 'classes' => array('danger')),
		1 => array('label' => 'Prospection: demande entrante', 'icon' => 'fas_suitcase', 'classes' => array('important')),
		2 => array('label' => 'Prospection: lead identifié', 'icon' => 'fas_suitcase', 'classes' => array('important')),
		3 => array('label' => 'Prospection: prise de contact', 'icon' => 'fas_suitcase', 'classes' => array('important')),
		4 => array('label' => 'Prospection: contact et présentation ok', 'icon' => 'fas_suitcase', 'classes' => array('important')),
		5 => array('label' => 'Prospect KO', 'icon' => 'fas_suitcase', 'classes' => array('danger')),
				6 => array('label' => 'KYC en cours'),
				7 => array('label' => 'MANGOPAY en cours'),
		8 => array('label' => 'En attente onboarding catalogue', 'icon' => 'fas_handshake', 'classes' => array('important')),
		9 => array('label' => 'Onboarding catalogue KO', 'icon' => 'fas_handshake', 'classes' => array('danger')),
		10 => array('label' => 'Onboarding catalogue OK', 'icon' => 'fas_handshake', 'classes' => array('success')),
		11 => array('label' => 'Live', 'icon' => 'fas_thumbs-up', 'classes' => array('success')),
		12 => array('label' => 'Résilié', 'icon' => 'fas_thumbs-down', 'classes' => array('danger')),
		13 => array('label' => 'Suspendu', 'icon' => 'fas_thumbs-down', 'classes' => array('danger')),
		14 => array('label' => 'Fermé', 'icon' => 'fas_thumbs-down', 'classes' => array('danger'))
	);

	const PENDING_SUBMISSION = 1;
	const PENDING_APPROVAL = 2;
	const APPROVED = 3;
	const REFUSED = 4;
	public static $statut_kyc_list = array(
		0 => array('label' => 'N/C', 'icon' => 'fas_calendar-day', 'classes' => array('danger')),
		self::PENDING_SUBMISSION => array('label' => 'En attente de soumission KYC', 'icon' => 'fas_hourglass', 'classes' => array('important')),
		self::PENDING_APPROVAL => array('label' => 'Vérification KYC en cours', 'icon' => 'fas_spinner' , 'classes' => array('important')),
		self::APPROVED => array('label' => 'KYC validé', 'icon' => 'fas_check', 'classes' => array('success')),
		self::REFUSED => array('label' => 'KYC non valide', 'icon' => 'fas_times', 'classes' => array('danger')),
	);


//self::BS_SAV_RESERVED          => array('label' => 'Réservé par le client', 'icon' => 'fas_calendar-day', 'classes' => array('important')),
//self::BS_SAV_CANCELED_BY_CUST  => array('label' => 'Annulé par le client', 'icon' => 'fas_times', 'classes' => array('danger')),
//self::BS_SAV_CANCELED_BY_USER  => array('label' => 'Annulé par utilisateur', 'icon' => 'fas_times', 'classes' => array('danger')),

	public static $categorieMaitreRdc = array(
		0 => array('label' => 'N/C', 'icon' => 'fas_calendar-day', 'classes' => array('danger')),
		1 => array('label' => 'Catégrorie 1'),
		2 => array('label' => 'Catégorie 2'),
		3 => array('label' => 'Catégorie 3'),
		4 => array('label' => 'Catégorie 4'),
	);
	public static $statut_rdc_live = 11;
	public static $statut_rdc_prospect_array = array(3, 4);

	public static $actions_selon_statut_rdc = array(
		0 => array( // N/C
			1, 2, 3, 4
		),
		1 => array( // Prospection: demande entrante
			2, 3, 4, 5, 8
		),
		2 => array( // Prospection: lead identifié
			3, 4, 5, 8
		),
		3 => array( // prospection: prise de contact
			4, 5, 8
		),
		4 => array( // Prospection: contact et présentation ok
			5, 8
		),
		5 => array( // Prospect KO
			8, 3
		),
		8 => array( // En attente onboarding catalogue
			9, 10, 5
		),
		9 => array( // Onboarding catalogue KO
			10, 5
		),
		10 => array( // Onboarding catalogue OK
			5
		),
//		11 => array( // Live
//			13, 14, // suspendu, férmé
//		),
//		13 => array( // suspendu
//
//		),
//		14 => array( // Fermé
//			12
//		),
	);

	public static $group_allowed_actions = array(
		1 => array('BD'),
		2 => array('BD'),
		3 => array('BD'),
		4 => array('BD'),
		5 => array('BD'),
		6 => array('BD'),
		7 => array('BD'),
		8 => array('BD'),
		9 => array('TECH_RDC'),
		10 => array('TECH_RDC'),
		11 => array(),
		12 => array('BD'),
		13 => array(),
		14 => array(),
	);


	public static function getUserGroupsArray($include_empty = 1, $nom_url = 0)
	{
		$grouparray = array(
			BimpCore::getUserGroupId('BD'),
			BimpCore::getUserGroupId('KAM'),
			BimpCore::getUserGroupId('TECH_RDC'),
		);

		$cache_key = 'users_groups';
		if ($nom_url) {
			$cache_key .= '_nom_url';
		}

		$cache_key .= '_array';

		if (!isset(self::$cache[$cache_key])) {
			$rows = self::getBdb()->getRows('usergroup', 'rowid IN (' . implode(', ', $grouparray) . ')', null, 'object', array('rowid', 'nom'), 'nom', 'asc');
			if (!is_null($rows)) {
				$icon = BimpRender::renderIcon('fas_users', 'iconLeft');
				foreach ($rows as $r) {
					if ($nom_url) {
						$url = BimpTools::getDolObjectUrl('UserGroup', $r->rowid);
						self::$cache[$cache_key][$r->rowid] = '<a href="' . $url . '" target="_blank">' . $icon . $r->nom . '</a>';
					} else {
						self::$cache[$cache_key][$r->rowid] = $r->nom;
					}
				}
			}
		}
		return self::getCacheArray($cache_key, $include_empty);
	}

	public function getContrefacon()
	{
		if ($this->getData('contrefacon')) return '<span class="danger">Oui</span>';
		else return '<span class="success">Non</span>';
	}
    public function getActionsButtons()
	{
//		echo '<pre>'; print_r($this->data); echo '</pre>';die;
		$actioncomm = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm');
		$buttons[] = array(
			'label'   => 'CR échange',
			'icon'    => 'fas_comment',
//			'onclick' => $actioncomm->getJsActionOnclick('cr_echange', array('fk_soc' => $this->id), array('form_name' => 'formCREchange'))
			'onclick' => $actioncomm->getJsLoadModalForm('formCREchange', 'Compte rendu d\\\'échange', array('fields' => array('fk_soc' => $this->id)))
		);

		$buttons[] = array(
			'label'   => 'Actions',
			'icon'    => 'fas_edit',
			'onclick' => $this->getJsActionOnclick('change_status_rdc', array(), array('form_name' => 'formActionRdc'))
		);

		if ($this->getData('shopid') > 0) {
			$buttons[] = array(
				'label'   => 'Synchro Mirakl',
				'icon'    => 'fas_sync',
				'onclick' => $this->getJsActionOnclick('synchroMirakl', array(), array())
			);
		}




		return $buttons;
	}

    public function getListButtons()
	{
		$buttons = array();

		$statu = $this->getData('fk_statut_rdc');
//		if($statu == 0)
//			$statu = 1;
		if (isset(self::$actions_selon_statut_rdc[$statu])) {
			foreach (self::$actions_selon_statut_rdc[$statu] as $statut) {
				$listGroup_allowed = self::$group_allowed_actions[$statut];
				$user_in_group = false;
				foreach ($listGroup_allowed as $group) {
					if ($this->isUserInGroup($group)) {
						$user_in_group = true;
						break;
					}
				}
				if($user_in_group)
					$buttons[$statut] = array(
						'label'   => 'Passer le statut à ' . self::$statusRdc[$statut]['label'],
						'icon'    => 'fas_edit',
//						'onclick' => $this->getJsActionOnclick('change_status_rdc', array('status' => $statut), array('form_name' => 'formActionRdc'))
						'onclick' => $this->getJsActionOnclick('change_status_rdc', array('status' => $statut))
					);
				else
					$buttons[$statut] = array(
						'label'   => 'Passer le statut à ' . self::$statusRdc[$statut]['label'],
						'icon'    => 'fas_times',
						'onclick' => '',
						'disabled' => 1,
						'popover' => 'Vous n\'avez pas les droits pour effectuer cette action'
					);
			}
		}
		return $buttons;
		/*$groups = array();
		if (!empty($buttons)) {
			$groups[] = array(
				'label'   => 'Actions',
				'icon'    => 'fas_cogs',
				'buttons' => $buttons
			);
		}

		if (!empty($groups)) {
			return array(
				'buttons_groups' => $groups,
			);
		}

		return array();*/
	}

	public function getMiraklLink()
	{
		if ($this->getData('shopid') > 0) {
			$url = 'https://mirakl-web.groupe-rueducommerce.fr/mmp/operator/shop/' . $this->getData('shopid');
			return $this->getHref($url);
		}
		else return '';
	}

	public function getUrlMarchand()	{
		if ($this->getData('url')) {
			$url = $this->getData('url');
			return $this->getHref($url);
		}
		else return ' ';
	}

	public function getHref($url, $target="_blank")
	{
		if (substr($url, 0, 4) != 'http')	{
			$url = 'https://' . $url;
		}
//		$href = '<a href="' . $url . '" target="' . $target . '"><i class="fas fa-external-link-alt"></></a>';
		$href = '<a href="' . $url . '" target="' . $target . '">' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';
		return $url . " " . $href;
	}

	public function isShopIdEditable()
	{
//		$id = BimpTools::getPostFieldValue('id');
//		if (!$id) return false;
		if ($this->getData('shopid')) return false;
		else return $this->isUserBD();
	}

	public function isAdmin()
	{
//		return 0;
		global $user;
		return $user->admin;
	}

	public function isUserBD()
	{
		/*todo a voir si on garde*/
		if ($this->isAdmin()) return true;
		return $this->isUserInGroup('BD');
	}

	public function isUserKAM()
	{
		/*todo a voir si on garde*/
		if ($this->isAdmin()) return true;
		return $this->isUserInGroup('KAM');
	}

	public function isUserTECH()
	{
		/*todo a voir si on garde*/
		if ($this->isAdmin()) return true;
		return $this->isUserInGroup('TECH_RDC');
	}

	public function isUserBDKAM()
	{
		return $this->isUserBD() || $this->isUserKAM();
	}

	public function isUserInGroup($g)
	{
		global $user;
		$id_group = BimpCore::getConf('id_user_group_' . $g);
		$groups = $this->db->getRow('usergroup_user', 'fk_user = ' . $user->id . ' AND fk_usergroup = ' . $id_group , array('rowid'), 'array');
		if($groups)
			return true;

		return false;
	}

	public function isCommentaireStatutKoRequired()	{
		$statut = $this->getData('fk_statut_rdc');
		if ($statut == 5) { // KO
			return true;
		}
		return false;
	}

	public function isPrestataireSourceRequired()
	{
		$source = $this->getData('fk_source_rdc');
		if ($source == 20) { // Prestataire/agrégateur
			return true;
		}
		return false;
	}

	public function renderHeaderStatusExtra()	{
		$html = '';
		$tab = self::$statusRdc[$this->getData('fk_statut_rdc')];
		$classes = '';
		if (isset($tab['classes'])) {
			foreach ($tab['classes'] as $class) {
				$classes .= ($classes!='' ? ', ' : '') . $class;
			}
		}
		$html .= '<div><span class="' .$classes . '">Statut de prospection&nbsp;: ';
		$icon = '';
		if(isset($tab['icon'])) {
			$icon = BimpRender::renderIcon($tab['icon'], 'iconLeft');
		}
		$html .= $icon . $tab['label'];
		$html .= '</span>';
		if ($this->getData('date_changement_statut_rdc')) {
			$html .= '<br />Dernier changement de statut le&nbsp;: ' . date('d / m / Y', strtotime($this->getData('date_changement_statut_rdc')));
		}
		$html .= '</div><div>&nbsp;</div>';

		$tab = self::$statut_kyc_list[$this->getData('fk_statut_kyc')];
		$classes = '';
		if (isset($tab['classes'])) {
			foreach ($tab['classes'] as $class) {
				$classes .= ($classes!='' ? ', ' : '') . $class;
			}
		}
		$html .= '<div class="' .$classes . '">Statut KYC&nbsp;: ';
		$icon = '';
		if(isset($tab['icon'])) {
			$icon = BimpRender::renderIcon($tab['icon'], 'iconLeft');
		}
		$html .= $icon . $tab['label'];
		$html .= '</div><div>&nbsp;</div>';

		return $html;
	}

	public function getStatusProperty()	{
		return '';
	}

	public function getUserAttrByGroupArray()	{
		$idGroup = BimpTools::getPostFieldValue('fk_group_rdc', $this->getData('fk_group_rdc'), 'int');
		$rows = $this->getBdb()->getRows(
			'user AS u',
			($idGroup ? 'ug.fk_usergroup=' . $idGroup : ''),
			null,
			'object',
			array('u.rowid', 'CONCAT(u.lastname, \' \', u.firstname) AS nomComplet'),
			'u.lastname', 'asc',
			array(
				'ug' => array(
					'table' => 'usergroup_user',
					'on'    => 'u.rowid = ug.fk_user',
				)
			)
		);
		$users = array(0 => '');
		foreach ($rows as $row) {
			$users[$row->rowid] = $row->nomComplet;
		}
		return $users;
	}

	public function getIdSourcePresta()	{
		return array(BimpCore::getConf('id_source_presta'));
	}

	public function renderPageView()
	{
		global $user;

		$tabs = array();
		$isAdmin = $user->admin;

		$tabs[] = array(
			'id' => 'default',
			'title' => 'Compte rendu d\'échange',
			'content' => $this->renderActionsCommView('manuel')
		);
		$tabs[] = array(
			'id' => 'auto',
			'title' => 'Actions automatiques',
			'content' => $this->renderActionsCommView('auto'),
		);

		return BimpRender::renderNavTabs($tabs);
	}

	public function renderActionsCommView($type)
	{
		if (!isset($this->id) || !$this->id) {
			$msg = ucfirst($this->getLabel('this')) . ' n\'existe plus';
			return BimpRender::renderAlerts($msg);
		}

		$filtre_type = array();
		$titre = '';
		switch ($type)	{
			case 'manuel':
				$filtre_type = 'not_in';
				$titre = 'Liste des échanges';
				break;
			case 'auto':
				$filtre_type = 'in';
				$titre = 'Actions automatiques';
				break;
		}

		$obj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm');
		$list = new BC_ListTable($obj, 'default', 1, null, $titre, 'fas_calendar-check');
		$list->addFieldFilterValue('fk_soc', $this->id);
		$list->addFieldFilterValue('fk_action', array(
			$filtre_type => array(40)
		));
		return $list->renderHtml();
	}

	public function create(&$warnings = array(), $force_create = false)
	{
		$this->checkAttr();
		return parent::create($warnings, $force_create);
	}

	public function update(&$warnings = array(), $force_update = false)
	{
		$errors = array();
		if ( BimpTools::getPostFieldValue('fk_source_rdc') == BimpCore::getConf('id_source_presta', 20) && strlen(BimpTools::getPostFieldValue('presta_source')) <= 0) {
			$errors[] = 'Le champ Prestataire/agrégateur est obligatoire';
		}

		if (count($errors))
			return $errors;

		$this->checkAttr();
		$this->checkPassageLive();

		return parent::update($warnings, $force_update);
	}

	public function actionSynchroMirakl($data, &$success){
		$errors = $warnings = array();
		$success = 'Synchro OK';
		$this->appelMiraklS20($errors);
		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	/*
	public function onSave(&$errors = array(), &$warnings = array())
	{
		parent::onSave($errors, $warnings);
	}
	*/

	public function appelMiraklS20(&$warnings = array())
	{
		$shopid = $this->getData('shopid');
		if(BimpTools::isModuleDoliActif('bimpapi')) {
			require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';
			$api = BimpAPI::getApiInstance('mirakl');
			if(!isset($api) || !is_object($api)) {
				$warnings[] = 'Module API non actif';
				return;
			}
			$data = $api->getShopInfo($shopid);
			if(!is_array($data)) {
				$warnings[] = 'Erreur lors de la récupération des données Mirakl';
				return;
			}

			$errors = array();
			if ($data['total_count'] == 0) {
				$warnings[] = 'ShopId ' . $shopid . ' non trouvé sur mirakl';
				$this->set('shopid', 0);
			} else {
				$shop = $data['shops'][0];
//				echo '<pre>'; print_r($shop); echo '</pre>';
				// traitement des données reçues : mise a jour du tiers
				$kyc = constant('self::' . $shop['kyc']['status']);
				if ($kyc)
					$this->set('fk_statut_kyc', $kyc);
				$this->set('nom', $shop['pro_details']['corporate_name']);
				$this->set('name_alias', $shop['shop_name']);
				$add = $shop['contact_informations']['street1'];
				if ($shop['contact_informations']['street2']) {
					$add .= ' ' . $shop['contact_informations']['street2'];
				}
				$this->set('address', $add);
				$this->set('zip', $shop['contact_informations']['zip_code']);
				$this->set('town', $shop['contact_informations']['city']);
				if ($shop['contact_informations']['country']) {
					$id_pays = $this->db->getValue('c_country', 'rowid', 'code_iso LIKE \'' . $shop['contact_informations']['country'] . '\'');
					if ($id_pays) {
						$this->set('fk_pays', $id_pays);
					}
				}
				$this->set('email', $shop['contact_informations']['email']);
				$this->set('phone', $shop['contact_informations']['phone']);
				$this->set('url', $shop['contact_informations']['site_web']);

				// traitement des données reçues : mise a jour / creation du contact
				$contacts = $this->getChildrenObjects('contacts');
				$nbModif = 0;
				if (count($contacts)) {
					foreach ($contacts as $contact) {    // tentative de mise a jour du contact (si mail et tel identiques)
						$nbModif += $this->updateContact($contact, $shop['contact_informations']);
					}
					if (!$nbModif) { // aucun contact modifié => on en crée un
						$this->createContact($shop['contact_informations']);
					}
				} else { // pas de contact connu => on en crée un
					$this->createContact($shop['contact_informations']);
				}
				// surcharge attribution
				if ($shop['assignees'])	{
					$emailAssign = strtolower($shop['assignees'][0]['email']);
					$userAttr = $this->getBdb()->getRows(
						'user AS u',
						'u.email LIKE \'' . $emailAssign . '\' OR LOCATE(\'' . $emailAssign . '\', ue.alias)',
						1,'array',array('u.rowid'),null,null,array(
							'ue' => array(
								'table' => 'user_extrafields',
								'on'    => 'u.rowid = ue.fk_object'
							))
					);
					if (isset($userAttr[0]['rowid']) && $userAttr[0]['rowid']) $this->set('fk_user_attr_rdc', $userAttr[0]['rowid']);
					else $warnings [] = 'Utilisateur d\'attribution non trouvé. ' . $emailAssign;
				}

				// surcharge statut
				if ($shop['shop_state'] === 'SUSPENDED' && !in_array($this->getData('fk_statut_rdc') , array(12, 13, 14))) 	{
					$this->set('fk_statut_rdc', 13);
					$this->set('date_changement_statut_rdc', date('Y-m-d'));
				}
				if ($shop['shop_state'] === 'OPEN' && $this->getData('shopid') > 0) {
					$this->set('fk_statut_rdc', self::$statut_rdc_live);
					$this->set('date_changement_statut_rdc', date('Y-m-d'));
					if(!$this->getData('date_ouverture'))
						$this->set('date_ouverture', date('Y-m-d', strtotime($shop['date_created'])));
				}
				$this->set('date_maj_mirakl', date('Y-m-d H:i:s'));
				$this->update($warnings);
			}
		}
		else{
			$warnings[] = 'Module API non actif';
		}
	}

	public function createContact($contact)
	{
		global $user;
		$obj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact');
		$obj->set('civility', $this->traduct_civility($contact['civility']));
		$obj->set('lastname', $contact['lastname']);
		$obj->set('firstname', $contact['firstname']);
		$add = $contact['street1'];
		if ($contact['street2'])	$add .= ' ' . $contact['street2'];
		$obj->set('address', $add);
		$obj->set('zip', $contact['zip_code']);
		$obj->set('town', $contact['city']);
		if ($contact['country'])	{
			$id_pays = $this->db->getValue('c_country', 'rowid', 'code_iso LIKE \'' . $contact['country'] . '\'');
			if ($id_pays)	$obj->set('fk_pays', $id_pays);
		}
//		echo '<pre>'; print_r($contact); echo '</pre>';die;
		$obj->set('phone', $contact['phone']);
		$obj->set('email', $contact['email']);
		$obj->set('datec', date('Y-m-d H:i:s'));
		$obj->set('fk_user_creat', $user->id);
		$obj->set('fk_soc', $this->id);
		$obj->set('dol_object', true);
		$obj->create();
	}

	public function updateContact($contact, $info)
	{
		if($contact->getData('email') == $info['email'] && $contact->getData('phone') == $info['phone'])	{
			$contact->set('civility', $this->traduct_civility($info['civility']));
			$contact->set('lastname', $info['lastname']);
			$contact->set('firstname', $info['firstname']);
			$add = $info['street1'];
			if ($info['street2'])	$add .= ' ' . $info['street2'];
			$this->set('address', $add);
			$contact->set('zip', $info['zip_code']);
			$contact->set('town', $info['city']);
			if ($info['country'])	{
				$id_pays = $this->db->getValue('c_country', 'rowid', 'code_iso LIKE \'' . $info['country'] . '\'');
				if ($id_pays)
					$contact->set('fk_pays', $id_pays);
			}
			$contact->set('phone', $info['phone']);
			$contact->set('email', $info['email']);
			$err = $contact->update();
			if($err) return 0;
			else return 1;
		}
		return 1;
	}

	public function checkAttr() {
		global $user;
		$attr = $this->getData('fk_user_attr_rdc');
		if ($attr != $user->id)	{
			if ($attr && ($this->getInitData('fk_user_attr_rdc') != $attr)) { // si changement d'attribution
				$code = 'Attribution_rdc';
				$sujet = 'Attribution Compte';
				$msg = 'Le compte ' . $this->getLink() . ' vient de vous être attribué par ' . $user->getNomUrl();
//				BimpUserMsg::envoiMsg($code, $sujet, $msg, $attr);
			}
		}
	}

	public function change_status_rdc() {
		if ($this->getInitData('fk_statut_rdc') != $this->getData('fk_statut_rdc')) {
			$this->set('date_changement_statut_rdc', date('Y-m-d'));
		}
	}

	public function traduct_civility($civility) {
		switch ($civility) {
			case 'Mr':
				return 'MR';
			case 'Mrs':
				return 'MME';
			case 'Ms':
				return 'Mlle';
			default:
				return '';
		}
	}

	public function actionChange_status_rdc($data, &$warnings = array())
	{
		$warnings = array();
        $errors = array();
		$success = 'Changement de statut effectué';

		if (!$data['status'])	{
			$errors[] = 'Aucun statut sélectionné';
		}
		else {
			// update de la date_debut_prospect (si statut_rdc dans la liste des statuts de début de prospection)
			if (in_array($data['status'], self::$statut_rdc_prospect_array)) {
				if (empty($this->getData('date_debut_prospect'))) {
					$this->set('date_debut_prospect', date('Y-m-d'));
				}
			}
		}

		$this->set('fk_statut_rdc', $data['status']);
		$this->set('date_changement_statut_rdc', date('Y-m-d'));
		$this->update($warnings, true);
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
	}

	public function checkPassageLive()
	{
		if ((int)$this->getData('fk_statut_rdc') == self::$statut_rdc_live) 	{
			if (empty($this->getData('date_ouverture'))) {
				$this->set('date_ouverture', date('Y-m-d'));
				if (!empty($this->getData('date_debut_prospect'))) {
					$date_p = new DateTime($this->getData('date_debut_prospect'));
					$date_o = new DateTime();
					$interval = $date_p->diff($date_o);
					$delai = $interval->days;
					$this->set('delai_ouv', $delai);
				}
			}
		}
	}
}

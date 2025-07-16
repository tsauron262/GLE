<?php

class Bimp_Client_ExtEntity extends Bimp_Client
{
	const ID_ONBOARDING_OK = 12;        // YANN
	const PENDING_SUBMISSION = 1;
	const PENDING_APPROVAL = 2;
	const APPROVED = 3;
	const REFUSED = 4;
	public static $statut_kyc_list = array(
		0                        => array('label' => 'N/C', 'icon' => 'fas_calendar-day', 'classes' => array('danger')),
		self::PENDING_SUBMISSION => array('label' => 'En attente de soumission KYC', 'icon' => 'fas_hourglass', 'classes' => array('important')),
		self::PENDING_APPROVAL   => array('label' => 'Vérification KYC en cours', 'icon' => 'fas_spinner', 'classes' => array('important')),
		self::APPROVED           => array('label' => 'KYC validé', 'icon' => 'fas_check', 'classes' => array('success')),
		self::REFUSED            => array('label' => 'KYC non valide', 'icon' => 'fas_times', 'classes' => array('danger')),
	);
	const STATUS_RDC_PROSPECT_DEM_ENTRANT = 1;
	const STATUS_RDC_PROSPECT_LEAD_IDENTIF = 2;
	const STATUS_RDC_PROSPECT_PRISE_CONTACT = 3;
	const STATUS_RDC_PROSPECT_CONTACT_PRES_OK = 4;
	const STATUS_RDC_PROSPECT_KO = 5;
	const STATUS_RDC_ATTENTE_ONBORDING = 8;
	const STATUS_RDC_ONBOARDING_KO = 9;
	const STATUS_RDC_ONBOARDING_OK = 10;
	const STATUS_RDC_LIVE = 11;
	const STATUS_RDC_RESIL = 12;
	const STATUS_RDC_SUSPENDED = 13;
	const STATUS_RDC_CLOSED = 14;

	public static $statusRdc = array(
		0                                         => array('label' => 'N/C', 'icon' => 'fas_calendar-day', 'classes' => array('danger')),
		self::STATUS_RDC_PROSPECT_DEM_ENTRANT     => array('label' => 'Prospection: demande entrante', 'icon' => 'fas_suitcase', 'classes' => array('important')),
		self::STATUS_RDC_PROSPECT_LEAD_IDENTIF    => array('label' => 'Prospection: lead identifié', 'icon' => 'fas_suitcase', 'classes' => array('important')),
		self::STATUS_RDC_PROSPECT_PRISE_CONTACT   => array('label' => 'Prospection: prise de contact', 'icon' => 'fas_suitcase', 'classes' => array('important')),
		self::STATUS_RDC_PROSPECT_CONTACT_PRES_OK => array('label' => 'Prospection: contact et présentation ok', 'icon' => 'fas_suitcase', 'classes' => array('important')),
		self::STATUS_RDC_PROSPECT_KO              => array('label' => 'Prospect KO', 'icon' => 'fas_suitcase', 'classes' => array('danger')),
//				6 => array('label' => 'KYC en cours'),
//				7 => array('label' => 'MANGOPAY en cours'),
		self::STATUS_RDC_ATTENTE_ONBORDING        => array('label' => 'En attente onboarding catalogue', 'icon' => 'fas_handshake', 'classes' => array('important')),
		self::STATUS_RDC_ONBOARDING_KO            => array('label' => 'Onboarding catalogue KO', 'icon' => 'fas_handshake', 'classes' => array('danger')),
		self::STATUS_RDC_ONBOARDING_OK            => array('label' => 'Onboarding catalogue OK', 'icon' => 'fas_handshake', 'classes' => array('success')),
		self::STATUS_RDC_LIVE                     => array('label' => 'Live', 'icon' => 'fas_thumbs-up', 'classes' => array('success')),
		self::STATUS_RDC_RESIL                    => array('label' => 'Résilié', 'icon' => 'fas_thumbs-down', 'classes' => array('danger')),
		self::STATUS_RDC_SUSPENDED                => array('label' => 'Suspendu', 'icon' => 'fas_thumbs-down', 'classes' => array('danger')),
		self::STATUS_RDC_CLOSED                   => array('label' => 'Fermé', 'icon' => 'fas_thumbs-down', 'classes' => array('danger'))
	);
	public static $statut_rdc_prospect_array = array(self::STATUS_RDC_PROSPECT_PRISE_CONTACT, self::STATUS_RDC_PROSPECT_CONTACT_PRES_OK);

	public static $actions_selon_statut_rdc = array(
		0                                         => array( // N/C
			self::STATUS_RDC_PROSPECT_DEM_ENTRANT,
			self::STATUS_RDC_PROSPECT_LEAD_IDENTIF,
			self::STATUS_RDC_PROSPECT_PRISE_CONTACT,
			self::STATUS_RDC_PROSPECT_CONTACT_PRES_OK
		),
		self::STATUS_RDC_PROSPECT_DEM_ENTRANT     => array( // Prospection: demande entrante
			self::STATUS_RDC_PROSPECT_LEAD_IDENTIF,
			self::STATUS_RDC_PROSPECT_PRISE_CONTACT,
			self::STATUS_RDC_PROSPECT_CONTACT_PRES_OK,
			self::STATUS_RDC_PROSPECT_KO,
			self::STATUS_RDC_ATTENTE_ONBORDING
		),
		self::STATUS_RDC_PROSPECT_LEAD_IDENTIF    => array( // Prospection: lead identifié
			self::STATUS_RDC_PROSPECT_PRISE_CONTACT,
			self::STATUS_RDC_PROSPECT_CONTACT_PRES_OK,
			self::STATUS_RDC_PROSPECT_KO,
			self::STATUS_RDC_ATTENTE_ONBORDING
		),
		self::STATUS_RDC_PROSPECT_PRISE_CONTACT   => array( // prospection: prise de contact
			self::STATUS_RDC_PROSPECT_CONTACT_PRES_OK,
			self::STATUS_RDC_PROSPECT_KO,
			self::STATUS_RDC_ATTENTE_ONBORDING
		),
		self::STATUS_RDC_PROSPECT_CONTACT_PRES_OK => array( // Prospection: contact et présentation ok
			self::STATUS_RDC_PROSPECT_KO,
			self::STATUS_RDC_ATTENTE_ONBORDING
		),
		self::STATUS_RDC_PROSPECT_KO              => array( // Prospect KO
			self::STATUS_RDC_ATTENTE_ONBORDING, self::STATUS_RDC_PROSPECT_PRISE_CONTACT
		),
		self::STATUS_RDC_ATTENTE_ONBORDING        => array( // En attente onboarding catalogue
			self::STATUS_RDC_ONBOARDING_KO, self::STATUS_RDC_ONBOARDING_OK, self::STATUS_RDC_PROSPECT_KO
		),
		self::STATUS_RDC_ONBOARDING_KO            => array( // Onboarding catalogue KO
			self::STATUS_RDC_ONBOARDING_OK, self::STATUS_RDC_PROSPECT_KO
		),
		self::STATUS_RDC_ONBOARDING_OK            => array( // Onboarding catalogue OK
			self::STATUS_RDC_PROSPECT_KO
		),
		self::STATUS_RDC_SUSPENDED                => array(
			self::STATUS_RDC_PROSPECT_KO,
		),
	);

	public static $group_allowed_actions = array(
		self::STATUS_RDC_PROSPECT_DEM_ENTRANT     => array('BD'),
		self::STATUS_RDC_PROSPECT_LEAD_IDENTIF    => array('BD'),
		self::STATUS_RDC_PROSPECT_PRISE_CONTACT   => array('BD'),
		self::STATUS_RDC_PROSPECT_CONTACT_PRES_OK => array('BD'),
		self::STATUS_RDC_PROSPECT_KO              => array('BD'),
//		6  => array('BD'),
//		7  => array('BD'),
		self::STATUS_RDC_ATTENTE_ONBORDING        => array('BD'),
		self::STATUS_RDC_ONBOARDING_KO            => array('BD'),
		self::STATUS_RDC_ONBOARDING_OK            => array('BD'),
		self::STATUS_RDC_LIVE                     => array(),
		self::STATUS_RDC_RESIL                    => array('BD'),
		self::STATUS_RDC_SUSPENDED                => array(),
		self::STATUS_RDC_CLOSED                   => array(),
	);

	public static $valuesContrefacon = array(
		0 => array('label' => ' '),
		1 => array('label' => 'OUI', 'icon' => 'fas_exclamation', 'classes' => array('danger')),
	);

	public static $show_comm_statut_ko = array(self::STATUS_RDC_PROSPECT_KO);

	// Droits users

	public function canEditField($field_name)
	{
		switch ($field_name) {
			case 'presta_source':
			case 'fk_categorie_maitre':
			case 'potentiel_catalogue':
				return $this->isUserBDKAM();

			case 'fk_priorite':
			case 'fk_source_rdc':
			case 'name_alias':
			case 'nom':
				return $this->isUserBD();

			case 'contrefacon':
			case 'comment_quality':
				return $this->isUserQuality();

			case 'fk_group_rdc':
			case 'fk_user_attr_rdc':
				return $this->isUserManager();

			case 'fk_statut_rdc':
				return 0;
		}
		return parent::canEditField($field_name);
	}

	// Getters booléens

	public function isFieldEditable($field, $force_edit = false)
	{
		switch ($field) {
			case 'shopid':
				if ($this->getData('shopid')) {
					return false;
				} else {
					return $this->isUserBD();
				}
		}
		return parent::isFieldEditable($field, $force_edit);
	}

	public function isAdmin()
	{
		global $user;
		return BimpTools::isUserInGroup($user->id, 'Admin');
	}

	public function isUserBD()
	{
		global $user;
		return BimpTools::isUserInGroup($user->id, 'BD') || BimpTools::isUserInGroup($user->id, 'Qualité') || $this->isUserManager();
	}

	public function isUserKAM()
	{
		global $user;
		return BimpTools::isUserInGroup($user->id, 'KAM') || BimpTools::isUserInGroup($user->id, 'Qualité') || $this->isUserManager();
	}

	public function isUserManager()
	{
		global $user;
		return ($user->admin || BimpTools::isUserInGroup($user->id, 'MANAGER') || BimpTools::isUserInGroup($user->id, 'ADMIN'));
	}

	public function isUserTECH()
	{
		global $user;
		return BimpTools::isUserInGroup($user->id, 'TECH_RDC') || $this->isUserManager();
	}

	public function isUserQuality()
	{
		global $user;
		return BimpTools::isUserInGroup($user->id, 'Qualité') || $this->isUserManager();
	}

	public function isUserBDKAM()
	{
		return $this->isUserBD() || $this->isUserKAM();
	}

	public function isCommentaireStatutKoRequired()
	{
		$statut = $this->getData('fk_statut_rdc');
		if (in_array($statut, self::$show_comm_statut_ko)) {
			return true;
		}
		return false;
	}

	// Getters params

	public function getActionsButtons()
	{
		$actioncomm = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm');
		$buttons[] = array(
			'label'   => 'CR échange',
			'icon'    => 'fas_comment',
//			'onclick' => $actioncomm->getJsActionOnclick('cr_echange', array('fk_soc' => $this->id), array('form_name' => 'formCREchange'))
			'onclick' => $actioncomm->getJsLoadModalForm('formCREchange', 'Compte rendu d\\\'échange', array('fields' => array('fk_soc' => $this->id)))
		);

		$buttons[] = array(
			'label'   => 'Changer le statut',
			'icon'    => 'fas_pen',
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

	public function getDefaultListExtraButtons()
	{
		$buttons = array();

		$actioncomm = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm');
		$buttons[] = array(
			'label'   => 'Ajouter un evenement',
			'icon'    => 'fas_calendar-plus',
			'onclick' => $actioncomm->getJsLoadModalForm('formCREchange', 'Compte rendu d\\\'échange', array('fields' => array('fk_soc' => $this->id)))
		);
		return $buttons;
	}

	public function getRefProperty()
	{
		return '';
	}

	public function getStatusProperty()
	{
		return '';
	}

	public function getNameProperties()
	{
		return array('name_alias', 'nom');
	}

	// Getters array

	public static function getUserGroupsArray($include_empty = 1, $nom_url = 0)
	{
		$grouparray = array(
			BimpCore::getUserGroupId('BD'),
			BimpCore::getUserGroupId('KAM'),
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

	public function getUserAttrByGroupArray()
	{
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

	public function getSelectStatusArray()
	{
		global $user;
		$status_options = array();

		$statut_rdc = $this->getData('fk_statut_rdc');
		if (isset(self::$actions_selon_statut_rdc[$statut_rdc])) {
			foreach (self::$actions_selon_statut_rdc[$statut_rdc] as $statut) {
				$listGroup_allowed = self::$group_allowed_actions[$statut];
				$user_in_group = false;
				foreach ($listGroup_allowed as $group) {
					if (BimpTools::isUserInGroup($user->id, $group) || $this->isUserManager()) {
						$user_in_group = true;
						break;
					}
				}
				if ($user_in_group) {
					$status_options[$statut] = self::$statusRdc[$statut];
				}
			}
		}
		return $status_options;
	}

	// Getters Données

	public function getPageTitle()
	{
		$html = '';
		if ($this->getData('name_alias')) {
			$html .= $this->getData('name_alias');
		}
		if ($this->getData('shopid')) {
			if ($html != '') {
				$html .= ' - ';
			}
			$html .= $this->getData('shopid');
		}
		if ($html) {
			return $html;
		}

		return parent::getPageTitle();
	}

	public function getRef($withGeneric = true)
	{
		$prop = $this->getRefProperty();

		if (isset($this->data[$prop]) && $this->data[$prop]) {
			return $this->data[$prop];
		}

		return '';
	}

	public function getName($withGeneric = true)
	{
		$name = '';

		$nom = $this->getData('nom');
		$alias = $this->getData('name_alias');

		if ($alias) {
			$name .= $alias;
		}

		if ($nom && strpos($alias, $nom) === false) {
			if ($name) {
				$name .= ' (' . $nom . ')';
			} else {
				$name = $nom;
			}
		}

		return $name;
	}

	public function getMiraklLink()
	{
		if ($this->getData('shopid') > 0) {
			$url = 'https://mirakl-web.groupe-rueducommerce.fr/mmp/operator/shop/' . $this->getData('shopid');
			return $this->getHref($url);
		} else {
			return '';
		}
	}

	public function getUrlMarchand()
	{
		$html = ' ';
		if ($this->getData('url')) {
			$urls = explode("%0D%0A", urlencode($this->getData('url')));
			foreach ($urls as $url) {
				$lien = $this->getHref(urldecode($url));
				if ($html != ' ') {
					$html .= '<br>';
				}
				$html .= $lien;
			}
		}
		return $html;
	}

	public function getHref($url, $target = "_blank")
	{
		if (substr($url, 0, 4) != 'http') {
			$url = 'https://' . $url;
		}
		$href = '<a href="' . $url . '" target="' . $target . '">' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

		if (strlen($url) > 80) {
			$short = substr($url, 0, 80);
			$ret = '<span class="bs-popover"';
			$ret .= BimpRender::renderPopoverData($url);
			$ret .= '>' . $short . '</span>';
		} else {
			$ret = $url;
		}
		return $ret . " " . $href;
	}

	public function getIdSourcePresta()
	{
		return array(BimpCore::getConf('id_source_presta'));
	}

	public function getFlagImport()
	{
		$html = '';
		$import_key = $this->getData('import_key');
		if ($import_key) {
			if (strpos($import_key, 'IMP_FLO') !== false) {
				$html .= '<span class="" title="Importé Florian">';
			} elseif (strpos($import_key, 'IMP_MOALING_') !== false) {
				$html .= '<span class="" title="Importé Moaling">';
			} else {
				$html .= '<span class="success" title="Importé Salesforce">';
			}
			$html .= BimpRender::renderIcon('fas_file-import', 'iconRight');
			$html .= '</span>';
		}
		return $html;
	}

	public function getCommentQuality()
	{
		define('MAX_COMMENT_QUALITY_LENGTH', 150);
		$comment = $this->getData('comment_quality');
		if ($comment) {
			if (strlen($comment) > MAX_COMMENT_QUALITY_LENGTH) {
				$short = substr($comment, 0, MAX_COMMENT_QUALITY_LENGTH);
				$ret = '<span class="bs-popover"';
				$ret .= BimpRender::renderPopoverData($comment);
				$ret .= '>' . $short . '</span>';
				return $ret;
			} else {
				return $comment;
			}
		}
		return '';
	}

	// Affichages
	public function displayFullContactInfosNoWeb()
	{
		$params = array(
			'url' => false,
//			'phone' => true,
		);
		return parent::displayFullContactInfos(1, 0, $params) ? : ' ';
	}

	public function displayFullAddress($icon = false, $single_line = false)
	{
		return parent::displayFullAddress($icon, $single_line) ? : ' ';
	}

	// Rendus HTML

	public function renderHeaderStatusExtra()
	{
		$html = '';
		$tab = self::$statusRdc[$this->getData('fk_statut_rdc')];
		$classes = '';
		if (isset($tab['classes'])) {
			foreach ($tab['classes'] as $class) {
				$classes .= ($classes != '' ? ', ' : '') . $class;
			}
		}
		$html .= '<div><span class="' . $classes . '">Statut de prospection&nbsp;: ';
		$icon = '';
		if (isset($tab['icon'])) {
			$icon = BimpRender::renderIcon($tab['icon'], 'iconLeft');
		}
		$html .= $icon . $tab['label'];
		$html .= '</span>';
		if ($this->getData('fk_statut_rdc') == self::STATUS_RDC_PROSPECT_KO) {
			$html .= '<br />Motif KO&nbsp;: ' . $this->getData('commentaire_statut_ko');
		}
		if ($this->getData('date_changement_statut_rdc')) {
			$html .= '<br />Dernier changement de statut le&nbsp;: ' . date('d / m / Y', strtotime($this->getData('date_changement_statut_rdc')));
		}
		$html .= '</div><div>&nbsp;</div>';

		$tab = self::$statut_kyc_list[$this->getData('fk_statut_kyc')];
		$classes = '';
		if (isset($tab['classes'])) {
			foreach ($tab['classes'] as $class) {
				$classes .= ($classes != '' ? ', ' : '') . $class;
			}
		}
		$html .= '<div class="' . $classes . '">Statut KYC&nbsp;: ';
		$icon = '';
		if (isset($tab['icon'])) {
			$icon = BimpRender::renderIcon($tab['icon'], 'iconLeft');
		}
		$html .= $icon . $tab['label'];
		$html .= '</div><div>&nbsp;</div>';

		return $html;
	}

	public function renderHeaderExtraLeft()
	{
		$html = '';

		$shopid = $this->getData('shopid');

		if ($shopid) {
			$html .= '<div style="font-size: 15px; margin-top: -10px; margin-bottom: 10px;">';
			$html .= '<span class="info">Shop ID : ' . $shopid . '</span>';
			$html .= '</div>';
		}

		return $html . parent::renderHeaderExtraLeft();
	}

	public function renderActionsCommView($type)
	{
		if (!isset($this->id) || !$this->id) {
			$msg = ucfirst($this->getLabel('this')) . ' n\'existe plus';
			return BimpRender::renderAlerts($msg);
		}

		$filtre_type = array();
		$titre = '';
		switch ($type) {
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

	public function renderPageView()
	{
		$tabs = array();

		$tabs[] = array(
			'id'      => 'default',
			'title'   => 'Compte rendu d\'échange',
			'content' => $this->renderActionsCommView('manuel')
		);
		$tabs[] = array(
			'id'      => 'auto',
			'title'   => 'Actions automatiques',
			'content' => $this->renderActionsCommView('auto'),
		);

		return BimpRender::renderNavTabs($tabs, 'actioncomm');
	}

	// Traitements

	public function createOrUpdateContacts($datas)
	{
		// traitement des données reçues : mise a jour / creation du contact
		$contacts = $this->getChildrenObjects('contacts');

		if (count($contacts)) {
			$nbModif = 0;
			foreach ($contacts as $contact) {    // tentative de mise a jour du contact (si mail et tel identiques)
				$nbModif += $this->updateContact($contact, $datas);
			}
			if (!$nbModif) { // aucun contact modifié => on en crée un
				$this->createContact($datas);
			}
		} else { // pas de contact connu => on en crée un
			$this->createContact($datas);
		}
	}

	public function createContact($contact)
	{
		global $user;
		$obj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact');
		$obj = $this->setObj($obj, $contact);
		$obj->set('poste', $contact['poste']);
		$obj->set('datec', date('Y-m-d H:i:s'));
		$obj->set('fk_user_creat', $user->id);
		$obj->set('fk_soc', $this->id);
		$obj->set('dol_object', true);
		$obj->create();
	}

	public function updateContact($contact, $info)
	{
		if ($contact->getData('email') == $info['email']) {
			$contact = $this->setObj($contact, $info);
			$poste = $contact->getData('poste');
			if (!$poste || $poste && strstr($poste, $info['poste']) === false) {
				// c'est un nouveau poste, on l'ajoute
				if ($poste) {
					$poste .= '<br>';
				}
				$poste .= $info['poste'];
				$contact->set('poste', $poste);
			}
			$err = $contact->update();
			if ($err) {
				return 0;
			} else {
				return 1;
			}
		}
		return 0;
	}

	public function setObj($obj, $contact)
	{
		$obj->set('civility', $contact['civility'] ? $this->traduct_civility($contact['civility']) : $obj->getData('civility'));
		$obj->set('lastname', $contact['lastname'] ? : $obj->getData('lastname'));
		$obj->set('firstname', $contact['firstname'] ? : $obj->getData('firstname'));
		$add = $contact['street1'] ? : $this->getData('address');
		if ($contact['street1'] && $contact['street2']) {
			$add .= ' ' . $contact['street2'];
		}
		$obj->set('address', $add);
		$obj->set('zip', $contact['zip_code'] ? : $this->getData('zip'));
		$obj->set('town', $contact['city'] ? : $this->getData('town'));
		$id_pays = 0;
		if ($contact['country']) {
			$id_pays = $this->db->getValue('c_country', 'rowid', 'code_iso LIKE \'' . $contact['country'] . '\' OR label LIKE \'' . $contact['country'] . '\'');
			if ($id_pays) {
				$obj->set('fk_pays', $id_pays);
			}
		}
		if (!$id_pays) {
			$obj->set('fk_pays', $this->getData('fk_pays'));
		}
		$obj->set('phone', $contact['phone']);
		$obj->set('email', $contact['email']);
		return $obj;
	}

	public function checkAttr()
	{
		global $user;
		$attr = $this->getData('fk_user_attr_rdc');
		if ($attr != $user->id) {
			if ($attr && ($this->getInitData('fk_user_attr_rdc') != $attr)) { // si changement d'attribution
				$msg = 'Ce marchand vient de vous être attribué';
				$this->addNote(
					$msg, BimpNote::BN_AUTHOR, 0, 1, '',
					BimpNote::BN_AUTHOR_USER, BimpNote::BN_DEST_USER, 0, $attr
				);
			}
		}
	}

	public function traduct_civility($civility)
	{
		switch ($civility) {
			case 'Mr':
			case 'M':
			case 'MR':
				return 'MR';
			case 'Mrs':
			case 'MRS':
			case 'MME':
				return 'MME';
			case 'Ms':
			case 'MS':
			case 'MLLE':
				return 'Mlle';
			default:
				return '';
		}
	}

	public function AlerteQualite()
	{
		global $user, $langs;
		if ($this->getData('contrefacon') && !$this->getInitData('contrefacon') && $this->getData('fk_user_attr_rdc') != $user->id) {
			$msg = 'Ce marchand a été signalé pour un problème de qualité';
			$this->addNote($msg, BimpNote::BN_AUTHOR, 0, 1, '',
				BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, $this->getData('fk_user_attr_rdc')
			);
		}
	}

	public function alerteOnboarding_catalogue_OK()
	{
		if ($this->getData('fk_status_rdc') == self::STATUS_RDC_ONBOARDING_OK && $this->getData('fk_statut_rdc') != $this->getInitData('fk_statut_rdc')) {
			$msg = 'Ce marchand a été mis en "Onboarding catalogue OK"';
			$this->addNote($msg, BimpNote::BN_AUTHOR, 0, 1, '',
				BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, self::ID_ONBOARDING_OK);
		}
	}

	public function alertePassage_live()
	{
		if ($this->getData('fk_status_rdc') == self::STATUS_RDC_LIVE && $this->getData('fk_statut_rdc') != $this->getInitData('fk_statut_rdc')) {
			$msg = 'Ce marchand vient de passer en LIVE';
			$this->addNote($msg, BimpNote::BN_AUTHOR, 0, 1, '',
				BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, $this->getData('fk_user_attr_rdc'));
		}
	}

	public function alertePassage_resil()
	{
		if ($this->getData('fk_status_rdc') == self::STATUS_RDC_RESIL && $this->getData('fk_statut_rdc') != $this->getInitData('fk_statut_rdc')) {
			$msg = 'Ce marchand vient de passer en RESILIE';
			$this->addNote($msg, BimpNote::BN_AUTHOR, 0, 1, '',
				BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, $this->getData('fk_user_attr_rdc'));
		}
	}

	public function alertePassage_suspendu()
	{
		if ($this->getData('fk_status_rdc') == self::STATUS_RDC_SUSPENDED && $this->getData('fk_statut_rdc') != $this->getInitData('fk_statut_rdc')) {
			$msg = 'Ce marchand vient de passer en SUSPENDU';
			$this->addNote($msg, BimpNote::BN_AUTHOR, 0, 1, '',
				BimpNote::BN_AUTHOR_USER, BImpNote::BN_DEST_USER, 0, $this->getData('fk_user_attr_rdc'));
		}
	}

	public function alertePassage_XX($s)
	{
		switch ($s) {
			case self::STATUS_RDC_ONBOARDING_OK:
				$this->alerteOnboarding_catalogue_OK();
				break;
			case self::STATUS_RDC_LIVE:
				$this->alertePassage_live();
				break;
			case self::STATUS_RDC_RESIL:
				$this->alertePassage_resil();
				break;
			case self::STATUS_RDC_SUSPENDED:
				$this->alertePassage_suspendu();
				break;
		}
	}

	public function appelMiraklS20(&$warnings = array())
	{
		$shopid = $this->getData('shopid');
		if (BimpTools::isModuleDoliActif('bimpapi')) {
			require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';
			$api = BimpAPI::getApiInstance('mirakl');
			if (!isset($api) || !is_object($api)) {
				$warnings[] = 'Module API non actif';
				return;
			}
			$data = $api->getShopInfo($shopid);
			if (!is_array($data)) {
				$warnings[] = 'Erreur lors de la récupération des données Mirakl';
				return;
			}

			$errors = array();
			if ($data['total_count'] == 0) {
				$warnings[] = 'ShopId ' . $shopid . ' non trouvé sur mirakl';
				$this->set('shopid', 0);
			} else {
				$shop = $data['shops'][0];
//				echo '<pre>'; print_r($shop); echo '</pre>';  exit;
				// traitement des données reçues : mise a jour du tiers
				$kyc = constant('self::' . $shop['kyc']['status']);
				if ($kyc) {
					$this->set('fk_statut_kyc', $kyc);
				}
				$this->set('nom', $shop['pro_details']['corporate_name']);
				$this->set('name_alias', $shop['shop_name']);
				$add = $shop['contact_informations']['street1'];
				if ($shop['contact_informations']['street2']) {
					$add .= ' ' . $shop['contact_informations']['street2'];
				}
				if ($add) {
					$this->set('address', $add);
				}
				if ($shop['contact_informations']['zip_code']) {
					$this->set('zip', $shop['contact_informations']['zip_code']);
				}
				if ($shop['contact_informations']['city']) {
					$this->set('town', $shop['contact_informations']['city']);
				}
				if ($shop['contact_informations']['country']) {
					$id_pays = $this->db->getValue('c_country', 'rowid', 'code_iso LIKE \'' . $shop['contact_informations']['country'] . '\'');
					if ($id_pays) {
						$this->set('fk_pays', $id_pays);
					}
				}
				if ($shop['contact_informations']['email']) {
					$this->set('email', $shop['contact_informations']['email']);
				}
				if ($shop['contact_informations']['phone']) {
					$this->set('phone', $shop['contact_informations']['phone']);
				}
				if ($shop['contact_informations']['site_web']) {
					$this->set('url', $shop['contact_informations']['site_web']);
				}

				$datas = array(
					'civility'  => $this->traduct_civility($shop['contact_informations']['civility']),
					'lastname'  => $shop['contact_informations']['lastname'],
					'firstname' => $shop['contact_informations']['firstname'],
					'street1'   => $shop['contact_informations']['street1'],
					'street2'   => $shop['contact_informations']['street2'],
					'zip'       => $shop['contact_informations']['zip_code'],
					'town'      => $shop['contact_informations']['city'],
					'email'     => $shop['contact_informations']['email'],
					'phone'     => $shop['contact_informations']['phone'],
					'poste'     => 'Mirakl: Contact principal',
				);
				$this->createOrUpdateContacts($datas);

				// traitement des champs additionnels
				$additional_fields = array();
				foreach ($shop['shop_additional_fields'] as $value) {
					$additional_fields[$value['code']] = array('value' => $value['value'], 'type' => $value['type']);
				}
				if (!empty($additional_fields['lastname-cs']) || !empty($additional_fields['email-cs']) || !empty($additional_fields['address-cs']) || !empty($additional_fields['firstname-cs']) || !empty($additional_fields['phone-cs'])) {
					$datas = array(
						'lastname'  => $additional_fields['lastname-cs']['value'],
						'firstname' => $additional_fields['firstname-cs']['value'],
						'email'     => $additional_fields['email-cs']['value'],
						'phone'     => $additional_fields['phone-cs']['value'],
						'civility'  => $additional_fields['civ-cs']['value'],
						'street1'   => $additional_fields['address-cs']['value'],
						'street2'   => $additional_fields['address-cs-2']['value'],
						'zip'       => $additional_fields['postal-code-cs']['value'],
						'town'      => $additional_fields['city-cs']['value'],
						'country'   => $additional_fields['country-cs']['value'],
						'poste'     => 'Mirakl: Contact CS',
					);
					$this->createOrUpdateContacts($datas);
				}
				if (!empty($additional_fields['nom-contact-commercial']) || !empty($additional_fields['email-contact-commercial']) || !empty($additional_fields['prenom-contact-commercial']) || !empty($additional_fields['telephone-contact-commercial'])) {
					$datas = array(
						'lastname'  => $additional_fields['nom-contact-commercial']['value'],
						'firstname' => $additional_fields['prenom-contact-commercial']['value'],
						'email'     => $additional_fields['email-contact-commercial']['value'],
						'phone'     => $additional_fields['telephone-contact-commercial']['value'],
						'poste'     => 'Mirakl: Contact commercial',
					);
					$this->createOrUpdateContacts($datas);
				}
				if (!empty($additional_fields['lastname-tech']) || !empty($additional_fields['email-tech']) || !empty($additional_fields['firstname-tech']) || !empty($additional_fields['phone-tech'])) {
					$datas = array(
						'lastname'  => $additional_fields['lastname-tech']['value'],
						'firstname' => $additional_fields['firstname-tech']['value'],
						'email'     => $additional_fields['email-tech']['value'],
						'phone'     => $additional_fields['phone-tech']['value'],
						'civility'  => $additional_fields['civ-tech']['value'],
						'poste'     => 'Mirakl: Contact technique',
					);
					$this->createOrUpdateContacts($datas);
				}
				if (!empty($additional_fields['accounting-lastname']) || !empty($additional_fields['accounting-email']) || !empty($additional_fields['accounting-firstname']) || !empty($additional_fields['accounting-phone'])) {
					$datas = array(
						'lastname'  => $additional_fields['accounting-lastname']['value'],
						'firstname' => $additional_fields['accounting-firstname']['value'],
						'email'     => $additional_fields['accounting-email']['value'],
						'phone'     => $additional_fields['accounting-phone']['value'],
						'poste'     => 'Mirakl: Contact comptabilité',
					);
					$this->createOrUpdateContacts($datas);
				}


				// surcharge attribution
				if ($shop['assignees']) {
					$emailAssign = strtolower($shop['assignees'][0]['email']);
					$userAttr = $this->getBdb()->getRows(
						'user AS u',
						'u.email LIKE \'' . $emailAssign . '\' OR LOCATE(\'' . $emailAssign . '\', ue.alias)',
						1, 'array', array('u.rowid'), null, null, array(
							'ue' => array(
								'table' => 'user_extrafields',
								'on'    => 'u.rowid = ue.fk_object'
							)
						)
					);
					if (isset($userAttr[0]['rowid']) && $userAttr[0]['rowid']) {
						$this->set('fk_user_attr_rdc', $userAttr[0]['rowid']);
					} else {
						$warnings [] = 'Utilisateur d\'attribution non trouvé. ' . $emailAssign;
					}
				}
				// surcharge statut
				if ($shop['shop_state'] === 'SUSPENDED' && !in_array($this->getData('fk_statut_rdc'), array(self::STATUS_RDC_RESIL, self::STATUS_RDC_CLOSED, self::STATUS_RDC_PROSPECT_KO))) {
					$this->set('fk_statut_rdc', self::STATUS_RDC_SUSPENDED);
					$this->set('date_changement_statut_rdc', date('Y-m-d', strtotime($shop['last_updated_date'])));
				}
				if ($shop['shop_state'] === 'OPEN' && $this->getData('fk_statut_rdc') != self::STATUS_RDC_LIVE) {
					$this->set('fk_statut_rdc', self::STATUS_RDC_LIVE);
					$this->set('date_changement_statut_rdc', date('Y-m-d'));
					if (!$this->getData('date_ouverture') && $shop['last_updated_date']) {
						$this->set('date_ouverture', date('Y-m-d', strtotime($shop['last_updated_date'])));
					}

					if ($this->getData('date_debut_prospect')) {
						$dp = new DateTime($this->getData('date_debut_prospect'));
						$do = new DateTime($this->getData('date_ouverture'));
						$diff = $dp->diff($do);
						if ($diff->invert == 0) {
							$this->set('delai_ouverture', $diff->format('%a'));
						}
					}
				}
				$this->set('date_maj_mirakl', date('Y-m-d H:i:s'));
				$this->update($warnings);
			}
		} else {
			$warnings[] = 'Module API non actif';
		}
	}

	// Actions

	public function actionChange_status_rdc($data, &$warnings = array())
	{
		$warnings = array();
		$errors = array();
		$success = 'Changement de statut effectué';

		if (!$data['status']) {
			$errors[] = 'Aucun statut sélectionné';
		} elseif ($data['status'] == self::STATUS_RDC_PROSPECT_KO && !$data['commentaire_statut_ko']) {
			$errors[] = 'Merci de remplir le commentaire';
		} else {
			// update de la date_debut_prospect (si statut_rdc dans la liste des statuts de début de prospection)
			if (in_array($data['status'], self::$statut_rdc_prospect_array)) {
				if (empty($this->getData('date_debut_prospect'))) {
					$this->set('date_debut_prospect', date('Y-m-d'));
				}
			}
			$this->set('fk_statut_rdc', $data['status']);
			$this->set('date_changement_statut_rdc', date('Y-m-d'));
			if ($data['status'] == self::STATUS_RDC_PROSPECT_KO) {
				$this->set('commentaire_statut_ko', $data['commentaire_statut_ko']);
			}

			$this->update($warnings, true);
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	public function actionSynchroMirakl($data, &$success)
	{
		$errors = $warnings = array();
		$success = 'Synchro OK';
		$this->appelMiraklS20($errors);
		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	// Overrides

	public function create(&$warnings = array(), $force_create = false)
	{
		$this->checkAttr();
		return parent::create($warnings, $force_create);
	}

	public function update(&$warnings = array(), $force_update = false)
	{
		$errors = array();
		if (BimpTools::getPostFieldValue('fk_source_rdc') == BimpCore::getConf('id_source_presta', 20) && strlen(BimpTools::getPostFieldValue('presta_source')) <= 0) {
			$errors[] = 'Le champ Prestataire/agrégateur est obligatoire';
		}

		if (BimpTools::getPostFieldValue('contrefacon') == 1 && strlen(BimpTools::getPostFieldValue('comment_quality')) <= 0) {
			$errors[] = 'Le champ Commentaire qualité est obligatoire';
		}

		if (count($errors)) {
			return $errors;
		}

		$this->checkAttr();
		$this->AlerteQualite();
		$this->alertePassage_XX($this->getData('fk_statut_rdc'));

		return parent::update($warnings, $force_update);
	}

}

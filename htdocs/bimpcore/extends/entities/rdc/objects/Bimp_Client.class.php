<?php

class Bimp_Client_ExtEntity extends Bimp_Client
{
	public static $statut_rdc_live = 11;
	public static $statut_rdc_prospect_array = array(1, 2, 3, 4);

	public static $actions_selon_statut_rdc = array(
		1 => array( // Prospection: demande entrante
			2, 3, 4, 5 // les autres statuts de prospection
		),
		2 => array(
			1, 3, 4, 5
		),
		3 => array(
			1, 2, 4, 5
		),
		4 => array(
			1, 2, 3, 5
		),
		5 => array(
			1, 2, 3, 4, 11
		),
		11 => array( // Live
			12, 13, 14, // résilié, suspendu, férmé
		),
	);

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

	public function getActionsButtons()
	{
		$buttons = array();

		$statuts_rdc = BimpCache::getStatuts_rdc();

		if (isset(self::$actions_selon_statut_rdc[$this->getData('fk_statut_rdc')])) {
			foreach (self::$actions_selon_statut_rdc[$this->getData('fk_statut_rdc')] as $statut) {
				$buttons[] = array(
					'label'   => 'Passer le statut à ' . $statuts_rdc[$statut]['libelle'],
					'icon'    => 'fas_edit',
					'onclick' => $this->getJsActionOnclick('change_status_rdc', array('status' => $statut))
				);
			}
		}

		$groups = array();
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

		return array();
	}

	public function isShopIdVisible()
	{
		$id = BimpTools::getPostFieldValue('id');
		$shopid = $this->db->getValue('societe_rdc', 'shopid', 'fk_soc = ' . $id);
//		exit(var_dump($shopid));
		if ($shopid) return false;
		else return true;
	}

	public function renderHeaderStatusExtra()	{
		return '';
	}

	public function getStatusProperty()	{
		return '';
	}

	public function getUserAttrByGroupArray()	{
		$idGroup = BimpTools::getPostFieldValue('fk_group', $this->getData('fk_group'), 'int');
		$rows = self::getBdb()->getRows(
			'user AS u',
			'ug.fk_usergroup=' . $idGroup,
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

	public function renderPageView()
	{
		global $user;

		$tabs = array();
		$isAdmin = $user->admin;

		$tabs[] = array(
			'id' => 'default',
			'title' => 'Actions commerciales',
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
				$titre = 'Actions commerciales';
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

	public function onSave(&$errors = array(), &$warnings = array())
	{
		self::checkAttr(true);	// envoi de mail si changement d'attribtion
		$shopid = BimpTools::getPostFieldValue('shopid');
		if ($shopid) self::appelMiraklS20($shopid);

		parent::onSave($errors, $warnings);
	}

	public function update(&$warnings = array(), $force_update = false)
	{
		self::checkAttr();	// envoi de mail si changement d'attribtion

		/* le menu de changement de statut_rdc ayant été désactivé, ceci devient inutile
		self::change_status_rdc(); // update de la date de changement de statut_rdc (si changement de fk_statut_rdc) et date_ouvert (si statut_rdc = live)
		*/
//
		// on a enregistré le shopId => on met à jour le Tiers avec API Mirakl S20
		if (BimpTools::isPostFieldSubmit('shopid')) {
			self::appelMiraklS20(BimpTools::getPostFieldValue('shopid'));
		}

		// todo: autres traitements à ajouter ici,
		// alertes, etc...

		parent::update($warnings, $force_update);
	}

	public function appelMiraklS20($shopid, &$warnings = array())
	{
		require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';
		$api = BimpAPI::getApiInstance('mirakl');
		$data = $api->getShopInfo($shopid);

		$errors = array();
		if ($data['total_count'] == 0)	{
			$warnings[] = 'ShopId ' . $shopid . ' non trouvé sur mirakl';
			$this->set('shopid', 0);
		}
		else	{
			$shop = $data['shops'][0];

			// traitement des données reçues : mise a jour du tiers
			$this->set('nom', $shop['pro_details']['corporate_name']);
			$this->set('name_alias', $shop['shop_name']);
			$add = $shop['contact_informations']['street1'];
			if ($shop['contact_informations']['street2'])	$add .= ' ' . $shop['contact_informations']['street2'];
			$this->set('address', $add);
			$this->set('zip', $shop['contact_informations']['zip_code']);
			$this->set('town', $shop['contact_informations']['city']);
			$id_pays = $this->db->getValue('c_country', 'rowid', 'code_iso LIKE \'' . $shop['contact_informations']['country']) . '\'';
			$this->set('fk_pays', $id_pays);
			$this->set('email', $shop['contact_informations']['email']);
			$this->set('phone', $shop['contact_informations']['phone']);
			$this->set('url', $shop['contact_informations']['site_web']);

			// traitement des données reçues : mise a jour / creation du contact
			$contacts = $this->getChildrenObjects('contacts');
			$nbModif = 0;
			if (count($contacts)) {
				foreach ($contacts as $contact) {	// tentative de mise a jour du contact (si mail et tel identiques)
					$nbModif += self::updateContact($contact, $shop['contact_informations']);
				}
				if (!$nbModif) { // aucun contact modifié => on en crée un
					self::createContact($shop['contact_informations']);
				}
			} else { // pas de contact connu => on en crée un
				self::createContact($shop['contact_informations']);
			}
		}
	}

	public function createContact($contact)
	{
		global $user;
		$obj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact');
		$obj->set('civility', self::traduct_civility($contact['civility']));
		$obj->set('lastname', $contact['lastname']);
		$obj->set('firstname', $contact['firstname']);
		$add = $contact['street1'];
		if ($contact['street2'])	$add .= ' ' . $contact['street2'];
		$obj->set('address', $add);
		$obj->set('zip', $contact['zip_code']);
		$obj->set('town', $contact['city']);
		$id_pays = $this->db->getValue('c_country', 'rowid', 'code_iso LIKE \'' . $contact['country']) . '\'';
		$obj->set('fk_pays', $id_pays);
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
			$contact->set('civility', self::traduct_civility($info['civility']));
			$contact->set('lastname', $info['lastname']);
			$contact->set('firstname', $info['firstname']);
			$add = $info['contact_informations']['street1'];
			if ($info['contact_informations']['street2'])	$add .= ' ' . $info['contact_informations']['street2'];
			$this->set('address', $add);
			$contact->set('zip', $info['zip_code']);
			$contact->set('town', $info['city']);
			$id_pays = $this->db->getValue('c_country', 'rowid', 'code_iso LIKE \'' . $info['country']) . '\'';
			$contact->set('fk_pays', $id_pays);
			$contact->set('phone', $info['phone']);
			$contact->set('email', $info['email']);
			$err = $contact->update();
			if($err) return 0;
			else return 1;
		}
	}
	public function checkAttr($onSave = false) {
		global $user;
		$attr = $this->getData('fk_user_attr');
		if ($attr != $user->id)	{
			if ($attr && ($this->getInitData('fk_user_attr') != $attr || $onSave)) { // si changement d'attribution ou onSave (creation)
				$code = 'Attribution_rdc';
				$sujet = 'Attribution Compte';
				$msg = 'Le compte ' . $this->getLink() . ' vient de vous être attribué par ' . $user->getNomUrl();

				//////////////////
				// todo: envoi du mail avec bimp UserMessage
				$user_attr = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $attr);
				$to = $user_attr->getData('email');
				$res = mailSyn2($sujet, $to, '', $msg);
				//////////////////
			}
		}
	}

	public function change_status_rdc() {
		if ($this->getInitData('fk_statut_rdc') != $this->getData('fk_statut_rdc')) {
			$this->set('date_changement_statut_rdc', date('Y-m-d H:i:s'));

			// update de la date_debut_prospection (si statut_rdc dans la liste des statuts de début de prospection)
			if (in_array((int)$this->getData('fk_statut_rdc'), self::$statut_rdc_prospect_array)) {
				if (empty($this->getInitData('date_debut_prospection'))) {
					$this->set('date_debut_prospection', date('Y-m-d H:i:s'));
				}
			}

			// update de la date_ouverture (si statut_rdc = live pour la premiere fois)
			if ((int)$this->getData('fk_statut_rdc') == self::$statut_rdc_live && (int)$this->getData('fk_statut_rdc') != (int)$this->getInitData('fk_statut_rdc')) 	{
				if (empty($this->getInitData('date_ouverture'))) {
					$this->set('date_ouverture', date('Y-m-d H:i:s'));
					// todo : calc nb jour entre date_debut_prospection et date_ouverture et le set dans le champ 'delai_ouv'
				}
			}
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
		$success = 'Changement de statut effectué';

		$this->set('fk_statut_rdc', $data['status']);
		$this->update($warnings);
	}
}

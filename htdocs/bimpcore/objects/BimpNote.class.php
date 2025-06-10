<?php

class BimpNote extends BimpObject
{
	# Visibilités:
	# ATTENTION : ne pas utiliser 3 et 4 (anciennes valeurs)

	const BN_AUTHOR = 1;
	const BN_ADMIN = 2;
	const BN_MEMBERS = 10;
	const BN_PARTNERS = 11;
	const BN_ALL = 20;

	public static $visibilities = array(
		self::BN_AUTHOR   => array('label' => 'Auteur/Destinataire seulement', 'classes' => array('danger')),
		self::BN_ADMIN    => array('label' => 'Administrateurs seulement', 'classes' => array('important')),
		self::BN_MEMBERS  => array('label' => 'Membres', 'classes' => array('warning')),
		self::BN_PARTNERS => array('label' => 'Membres et partenaires', 'classes' => array('warning')),
		self::BN_ALL      => array('label' => 'Membres, partenaires et clients', 'classes' => array('success')),
	);
	# Types d'auteur:

	const BN_AUTHOR_USER = 1;
	const BN_AUTHOR_SOC = 2;
	const BN_AUTHOR_FREE = 3;
	const BN_AUTHOR_GROUP = 4;

	public static $types_author = array(
		self::BN_AUTHOR_USER  => 'Utilisateur',
		self::BN_AUTHOR_GROUP => 'Groupe',
		self::BN_AUTHOR_SOC   => 'Tiers',
		self::BN_AUTHOR_FREE  => 'Libre'
	);
	# Types destinataire:

	const BN_DEST_NO = 0;
	const BN_DEST_USER = 1;
	const BN_DEST_SOC = 2;
	const BN_DEST_GROUP = 4;

	public static $types_dest = array(
		self::BN_DEST_NO    => 'Aucun',
		self::BN_DEST_USER  => 'Utilisateur',
		self::BN_DEST_GROUP => 'Groupe',
		self::BN_DEST_SOC   => 'Tiers (par e-mail)'
	);

	// Droits users:
	public function canEdit()
	{
		global $user;
		if ($user->admin) {
			return 1;
		}
		if ($this->getData("user_create") == $user->id && !$this->getInitData("viewed") && !$this->getData("auto")) {
			return 1;
		}
		return 0;
	}

	public function canClientView()
	{
		global $userClient;

		if (BimpObject::objectLoaded($userClient)) {
			if ($this->isLoaded()) {
				if ($this->getData('visibility') < self::BN_ALL) {
					return 0;
				}
			}

			return 1;
		}

		return 0;
	}

	public function canSetAction($action)
	{
		global $user;

		switch ($action) {
			case 'setAsViewed':
				if ($this->isLoaded()) {
					if ($this->getData('user_create') == $user->id && $this->getData('type_author') == self::BN_AUTHOR_USER) {
						return 0;
					}

					if (!$this->isUserDest()) {
						return 0;
					}
				}
				return 1;

			case 'repondre':
				return 1;
		}

		return parent::canSetAction($action);
	}

	public function canEditField($field_name)
	{
		switch ($field_name) {
			case 'viewed':
				if (!$this->isLoaded()) {
					return 0;
				}
				return $this->canSetAction('setAsViewed');
		}
		return parent::canEditField($field_name);
	}

	// Getters booléens:

	public function isFieldEditable($field, $force_edit = false)
	{
		if ($field == "viewed") {
			if ($this->getData("type_dest") != self::BN_DEST_NO) {
				return 0;
			}
		}

		return parent::isFieldEditable($field, $force_edit);
	}

	public function isCreatable($force_create = false, &$errors = array())
	{
		if ($this->modeArchive) {
			return 0;
		}
		return (int) $this->isEditable($force_create, $errors);
	}

	public function isEditable($force_edit = false, &$errors = array())
	{
		if ($this->modeArchive) {
			return 0;
		}

		$parent = $this->getParentInstance();

		if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
			return (int) $parent->areNotesEditable();
		}

		return 1;
	}

	public function isDeletable($force_delete = false, &$errors = array())
	{
		if ($this->modeArchive) {
			return 0;
		}
		return (int) $this->isEditable($force_delete, $errors);
	}

	public function isActionAllowed($action, &$errors = [])
	{
		if ($this->modeArchive) {
			$errors[] = 'Mode archive';
			return 0;
		}

		switch ($action) {
			case 'repondre':
				if ($this->isLoaded()) {
					global $user;
					if ((int) $this->getData('type_author') === self::BN_AUTHOR_USER && $this->getData('user_create') == $user->id) {
						$errors[] = 'L\'utilisateur connecté est l\'auteur';
						return 0;
					}
				}

				return 1;

			case 'setAsViewed':
				if (!$this->isLoaded($errors)) {
					return 0;
				}

				if ((int) $this->getData('viewed')) {
					$errors[] = 'Déjà vue';
					return 0;
				}

				return 1;
		}
		return parent::isActionAllowed($action, $errors);
	}

	public function isUserDest($users_delegations = null)
	{
		global $user;

		if ($this->getData("type_dest") == self::BN_DEST_USER && (int) $this->getData("fk_user_dest") == $user->id) {
			return 1;
		}

		$listIdGr = self::getUserUserGroupsList($user->id);

		if ($this->getData("type_dest") == self::BN_DEST_GROUP && in_array((int) $this->getData("fk_group_dest"), $listIdGr)) {
			return 1;
		}

		if (is_null($users_delegations)) {
			$users_delegations = BimpCache::getBdb()->getValues('user', 'rowid', 'delegations LIKE \'%[' . $user->id . ']%\'');
//            $users_delegations = array();
		}

		if (!empty($users_delegations)) {
			foreach ($users_delegations as $id_user) {
				if ($this->getData("type_dest") == self::BN_DEST_USER && $this->getData("fk_user_dest") == $id_user) {
					return 1;
				}

				$listIdGr = self::getUserUserGroupsList($id_user);

				if ($this->getData("type_dest") == self::BN_DEST_GROUP && in_array($this->getData("fk_group_dest"), $listIdGr)) {
					return 1;
				}
			}
		}
		return 0;
	}

	public function isUserAuthor()
	{
		global $user;
		if ($this->getData("type_author") == self::BN_AUTHOR_USER && $this->getData("user_create") == $user->id) {
			return 1;
		}

		return 0;
	}

	public function hasEmailCc()
	{
		return (int) !empty($this->getEMailToCc());
	}

	// Getters Overrides BimpObject:

	public function getParentInstance()
	{
		if (is_null($this->parent)) {
			$object_type = (string) $this->getData('obj_type');
			$module = (string) $this->getData('obj_module');
			$object_name = (string) $this->getData('obj_name');
			$id_object = (int) $this->getData('id_obj');

			if ($object_type && $module && $object_name && $id_object) {
				if ($object_type === 'bimp_object') {
					$this->parent = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
					if (!BimpObject::objectLoaded($this->parent)) {
						unset($this->parent);
						$this->parent = null;
					}
				}
			}
		}

		return $this->parent;
	}

	public function getParentLink()
	{
		$html = '';
		if (is_null($this->parent)) {
			$object_type = (string) $this->getData('obj_type');
			$module = (string) $this->getData('obj_module');
			$object_name = (string) $this->getData('obj_name');
			$id_object = (int) $this->getData('id_obj');

			if ($object_type && $module && $object_name && $id_object) {
				if ($object_type === 'bimp_object') {
//                    $coll = new BimpCollection($module, $object_name);
					$html = BimpCache::getBimpObjectLink($module, $object_name, $id_object);
				}
			}
		} else {
			return $this->parent->getLink();
		}

		return $html;
	}

	// Getters params:

	public function getActionsButtons()
	{
		$buttons = array();
		if ($this->isActionAllowed('repondre') && $this->canSetAction('repondre')) {
			$buttons[] = array(
				'label'   => 'Répondre',
				'icon'    => 'fas_share',
				'onclick' => $this->getJsRepondre()
			);
		}

		if ($this->isActionAllowed('setAsViewed') && $this->canSetAction('setAsViewed')) {
			$buttons[] = array(
				'label'   => 'Marquer comme vue',
				'icon'    => 'fas_envelope-open',
				'onclick' => $this->getJsActionOnclick('setAsViewed')
			);
		}

		return $buttons;
	}

	public function getListsExtraBulkActions()
	{
		return array(
			array(
				'label'   => 'Marquer vues',
				'icon'    => 'fas_check',
				'onclick' => $this->getJsBulkActionOnclick('setAsViewed', array(), array(
						'confirm_msg' => 'Veuillez confirmer (Attention certaines notes sont supprimées après lecture)'
					)
				)
			)
		);
	}

	public function getListHeaderButtons()
	{
		$buttons = array();

		if ((int) BimpCore::getConf('use_notes_models')) {
			$parent = $this->getParentInstance();

			if (is_a($parent, 'BimpObject')) {
				$model = BimpObject::getInstance('bimpcore', 'BimpNoteModel');
				$model->set('obj_module', $parent->module);
				$model->set('obj_name', $parent->object_name);

				if ($model->can('view')) {
					$buttons[] = array(
						'label'   => 'Modèles',
						'icon'    => 'far_file-alt',
						'onclick' => $model->getJsLoadModalList('obj', array(
							'title'         => 'Modèles de notes des ' . $parent->getLabel('name_plur'),
							'extra_filters' => array(
								'obj_module' => $parent->module,
								'obj_name'   => $parent->object_name
							)
						))
					);
				}
			}
		}

		return $buttons;
	}

	public function getListExtraBtn()
	{
		return $this->getActionsButtons();
	}

	// Getters array :

	public function getEmailDestsArray()
	{

		$emails = array();

		$parent = $this->getParentInstance();

		if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpObject')) {
			$client = $parent->getChildObject('client');

			if (BimpObject::objectLoaded($client) && $client->field_exists('email')) {
				$email = $client->getData('email');

				if ($email) {
					$emails['soc_' . $client->id] = BimpTools::ucfirst($client->getLabel()) . ' "' . $client->getName() . '" : ' . $email;
				}

				if (is_a($client, 'Bimp_Societe')) {
					$contacts = BimpCache::getSocieteContactsArray($client->id, false, '', true);

					if (!empty($contacts)) {
						foreach ($contacts as $id_contact => $contact_label) {
							$contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
							if (BimpObject::objectLoaded($contact)) {
								$email = $contact->getData('email');
								if ($email) {
									$emails['contact_' . $id_contact] = 'Contact "' . $contact_label . '" : ' . $email;
								}
							}
						}
					}
				}
			}
		}

		$emails['custom'] = 'Autre';

		return $emails;
	}

	// Getters données:

	public static function getFiltersByUser($id_user = null)
	{
		$filters = array();

		if (is_null($id_user) || (int) $id_user < 1) {
			global $user;
		} else {
			global $db;
			$user = new User($db);
			$user->fetch((int) $id_user);
		}

		if (!BimpObject::objectLoaded($user)) {
			$filters['visibility'] = array(
				'operator' => '>=',
				'value'    => self::BN_ALL
			);
		} elseif (/* !$user->admin */ 1) {
			$filters['or_visibility'] = array(
				'or' => array(
					'visibility'     => array(
						'operator' => '>=',
						'value'    => self::BN_MEMBERS
					),
					'user_create'    => $user->id,
					'and_user_dest'  => array(
						'and_fields' => array(
							'fk_user_dest' => $user->id,
							'type_dest'    => self::BN_DEST_USER
						)
					),
					'and_group_dest' => array(
						'and_fields' => array(
							'fk_group_dest' => self::getUserUserGroupsList($user->id),
							'type_dest'     => self::BN_DEST_GROUP
						)
					)
				)
			);
		}
		return $filters;
	}

	public function getLink($params = [], $forced_context = '')
	{
		if (!isset($params['parent_link']) || (int) $params['parent_link']) {
			$parent = $this->getParentInstance();
			if (is_object($parent) && method_exists($parent, 'getLink')) {
				return $parent->getLink($params, $forced_context);
			}
		}

		return parent::getLink($params, $forced_context);
	}

	public function getHisto()
	{
		$parent = $this->getParentInstance();
		if ($parent && is_object($parent)) {
			return $parent->renderNotesList(false, 'chat', '', false, false);
		}
	}

	public function getInitiales($str, $length = 3)
	{
		$str = strip_tags($str);
		$str = str_replace(array('"', '\''), '', $str);
		$str = str_replace(array("_", "-"), " ", $str);
		$str = str_replace(array("(", ")"), " ", $str);
		$str = str_replace('@', " @ ", $str);
		$return = "";
		if (strlen($str) > 0) {
			$tabT = explode(" ", $str);
			foreach ($tabT as $part) {
				$return .= substr($part, 0, 1);
			}
			$return = strtoupper(substr($return, 0, $length));
		}
		return $return;
	}

	public function getJsRepondre($with_email_cc = false)
	{
		$filtre = array(
			"content"       => "",
			"id"            => "",
			"type_dest"     => "",
			"fk_group_dest" => "",
			"fk_user_dest"  => $this->getData("user_create")
		);

		if ($this->getData('type_author') == self::BN_AUTHOR_USER) {
			$filtre['type_dest'] = self::BN_DEST_USER;
		} elseif ($this->getData('type_author') == self::BN_AUTHOR_GROUP) {
			$filtre['type_dest'] = self::BN_DEST_GROUP;
			$filtre['fk_group_dest'] = $this->getData("fk_group_author");
		} elseif ($this->getData('type_author') == self::BN_AUTHOR_SOC) {
			$filtre['type_dest'] = self::BN_DEST_SOC;
			$filtre['mail_dest'] = $this->getData("email");
		}

		if ($with_email_cc) {
			$email_cc = $this->getData('email_cc');

			if ($email_cc) {
				$filtre['email_to_copy'] = explode(',', str_replace(' ', '', BimpTools::cleanEmailsStr($email_cc)));
//				echo $email_cc . '<pre>' . print_r($filtre, 1) . '</pre>';
//				exit;
			}
		}


		return $this->getJsActionOnclick('repondre', $filtre, array(
				'form_name' => 'rep'
			)
		);
	}

	public function getContentDefaultValue($with_default = true)
	{
		$id_model = (int) BimpTools::getPostFieldValue('note_modele', 0, 'int');

		if ($id_model) {
			$model = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNoteModel', $id_model);
			if (BimpObject::objectLoaded($model)) {
				$content = $model->getData('content');

				$module = $this->getData('obj_module');
				$obj_name = $this->getData('obj_name');
				$id_obj = (int) $this->getData('id_obj');

				if ($module && $obj_name && $id_obj) {
					$obj = BimpCache::getBimpObjectInstance($module, $obj_name, $id_obj);
					if (BimpObject::objectLoaded($obj)) {
						$content = $obj->replaceFieldsValues($content, true);
					}
				}

				return $content;
			}
		}

		if ($with_default && isset($this->data['content'])) { // pas de getData sinon boucle infinie.
			return $this->data['content'];
		}

		return '';
	}

	public function getMailFrom($withName = true)
	{
		$parent = $this->getParentInstance();
		if (method_exists($parent, 'getMailFrom')) {
			$infoMail = $parent->getMailFrom();
			if (is_array($infoMail) && isset($infoMail[1]) && $withName) {
				return $infoMail[1] . '<' . $infoMail[0] . '>';
			} elseif (is_array($infoMail)) {
				return $infoMail[0];
			} else {
				return $infoMail;
			}
		}
		return BimpCore::getConf('mailReponse', null, 'bimptask');
	}

	public function getMailTo()
	{
		$email = $this->getData('email');
		if ($email) {
			return $email;
		}

		$parent = $this->getParentInstance();
		if (BimpObject::objectLoaded($parent)) {
			if (method_exists($parent, 'getMailTo')) {
				return $parent->getMailTo();
			} elseif ($parent->getData('email') != '') {
				return $parent->getData('email');
			} else {
				$client = $parent->getChildObject('client');
				if ($client && $client->isLoaded()) {
					return $client->getData('email');
				}
			}
		}

		return '';
	}

	public function getEMailToCc($return_array = true)
	{
		$cc = '';

//		if ((int) BimpTools::getPostFieldValue('email_with_cc', 0, 'int')) {
			$parent = $this->getParentInstance();
			if (BimpObject::objectLoaded($parent)) {
				if (method_exists($parent, 'getEMailToCc')) {
					$cc = $parent->getEMailToCc();
				}
			}
//		}

		if ($return_array && !is_array($cc)) {
			$cc = explode(',', str_replace(' ', '', BimpTools::cleanEmailsStr($cc)));
		}

		if (!$return_array && is_array($cc)) {
			$cc = implode(', ', $cc);
		}

		return $cc;
	}

	public function getMailToContacts()
	{
		$parent = $this->getParentInstance();
		if ($parent && $parent->isLoaded()) {
			if (method_exists($parent, 'getMailToContacts')) {
				return $parent->getMailToContacts();
			}
		}

		return array();
	}

	public function getParentMsgId() {}

	public function getAuthorName()
	{
		switch ((int) $this->getData('type_author')) {
			case self::BN_AUTHOR_USER:
				$user = $this->getChildObject('user_create');
				if (BimpObject::objectLoaded($user) && strtolower($user->getData('login')) !== 'client_user') {
					return $user->getName();
				}
				return '';

			case self::BN_AUTHOR_SOC:
			case self::BN_AUTHOR_FREE:
				$soc = $this->getChildObject('societe');
				if (BimpObject::objectLoaded($soc)) {
					return $soc->getName();
				}
				return $this->getData('email');

			case self::BN_AUTHOR_GROUP:
				$group = $this->getChildObject('author_group');
				if (BimpObject::objectLoaded($group)) {
					return $group->getName();
				}
				return '';
		}

		return '';
	}

	public function getDestName()
	{
		$html = '';

		switch ((int) $this->getData('type_dest')) {
			case self::BN_DEST_USER:
				$user = $this->getChildObject('user_dest');
				if (BimpObject::objectLoaded($user)) {
					return $user->getName();
				}
				return '';

			case self::BN_DEST_GROUP:
				$group = $this->getChildObject('user_group');
				if (BimpObject::objectLoaded($group)) {
					return $group->getName();
				}

			case self::BN_DEST_SOC:
				$soc = $this->getChildObject('societe');
				if (BimpObject::objectLoaded($soc)) {
					return $soc->getName();
				}
				return $this->getData('email');
		}

		return '';
	}

	// Affichage:

	public function displayDestinataire($display_input_value = true, $no_html = false, $with_icon = false)
	{
		$html = '';

		switch ((int) $this->getData('type_dest')) {
			case self::BN_DEST_USER:
				if ((int) $this->getData('fk_user_dest')) {
					if ($with_icon) {
						$html .= BimpRender::renderIcon('fas_user', 'iconLeft');
					}
					$html .= $this->displayData('fk_user_dest', 'nom_url', $display_input_value, $no_html);
				}
				break;

			case self::BN_DEST_GROUP:
				if ((int) $this->getData('fk_group_dest')) {
					if ($with_icon) {
						$html .= BimpRender::renderIcon('fas_users', 'iconLeft');
					}
					$html .= $this->displayData('fk_group_dest', 'nom_url', $display_input_value, $no_html);
				}
				break;

			case self::BN_DEST_SOC:
				$id_soc = (int) $this->getData('id_societe');
				$email = $this->getData('email');

				if ($id_soc) {
					if ($with_icon) {
						$html .= BimpRender::renderIcon('fas_building', 'iconLeft');
					}

					$html .= $this->displayData('id_societe', 'nom_url', $display_input_value, $no_html);
					if ($email) {
						$html .= ' (' . $email . ')';
					}
				} elseif ($email) {
					$html .= $email;
				}
				break;
		}

		return $html;
	}

	public function displayAuthor($display_input_value = true, $no_html = false, $with_icon = false)
	{
		$html = '';

		switch ((int) $this->getData('type_author')) {
			case self::BN_AUTHOR_USER:
				$user = $this->getChildObject('user_create');
				if (BimpObject::objectLoaded($user) && strtolower($user->getData('login')) === 'client_user') {
					return '';
				}

				if ($with_icon) {
					$html .= BimpRender::renderIcon('fas_user', 'iconLeft');
				}
				$html .= $this->displayData('user_create', 'nom_url', $display_input_value, $no_html);
				break;

			case self::BN_AUTHOR_SOC:
				if ($with_icon) {
					$html .= BimpRender::renderIcon('fas_building', 'iconLeft');
				}
				$id_soc = (int) $this->getData('id_societe');
				$email = $this->getData('email');

				if ($id_soc) {
					$html .= $this->displayData('id_societe', 'nom_url', $display_input_value, $no_html);
					if ($email) {
						$html .= ' (' . $email . ')';
					}
				} elseif ($email) {
					$html .= $email;
				}
				break;

			case self::BN_AUTHOR_FREE:
				$html .= $this->displayData('email', 'default', $display_input_value, $no_html);
				break;

			case self::BN_AUTHOR_GROUP:
				if ($with_icon) {
					$html .= BimpRender::renderIcon('fas_users', 'iconLeft');
				}
				$html .= $this->displayData('fk_group_author', 'nom_url', $display_input_value, $no_html);
				break;
		}

		return $html;
	}

	public function displayAuthorBadge()
	{
		$name = $this->getAuthorName();

		if ($name) {
			$html = '<span class="userBadge author ' . ($this->isUserAuthor() ? 'user' : '') . '" title="' . $name . '">';
			$html .= $this->getInitiales($name, 2);
			$html .= '</span>';
		}

		return $html;
	}

	public function displayDestBadge()
	{
		$name = $this->getDestName();

		if ($name) {
			$html = '<span class="userBadge author ' . ($this->isUserDest() ? 'user' : '') . '" title="' . $name . '">';
			$html .= $this->getInitiales($name, 2);
			$html .= '</span>';
		}

		return $html;
	}

	public function displayDate($relative_today = false)
	{
		if ($relative_today) {
			$dt = new DateTime($this->getData('date_create'));
			$now = new DateTime();

			if ($dt->format('Y-m-d') == $now->format('Y-m-d')) {
				return $dt->format('H:i');
			}

			$now->sub(new DateInterval('P1D'));
			if ($dt->format('Y-m-d') == $now->format('Y-m-d')) {
				return 'Hier | ' . $dt->format('H:i');
			}
		}

		return date('d/m/Y | H:i', strtotime($this->getData('date_create')));
	}

	public function displayChatMsg()
	{
		global $user;
		$html = '';

		$author = $this->displayAuthor(false, true, true);
		$dest = $this->displayDestinataire(false, true, true);
		$email_cc = $this->getData('email_cc');

		$parent = $this->getParentInstance();
		if ($parent && is_a($parent, 'Bimp_Ticket')) {
			$side = ($this->getData('type_author') == self::BN_AUTHOR_SOC || $this->getData('type_author') == self::BN_AUTHOR_FREE) ? "left" : "right";
		} else {
			$side = ($this->isUserDest() ? "left" : ($this->isUserAuthor() ? "right" : ""));
		}

		$buttons = '';
		if ($this->isActionAllowed('setAsViewed') && $this->canSetAction('setAsViewed')) {
			$buttons .= BimpRender::renderRowButton('Vu', 'far_envelope-open', $this->getJsActionOnclick('setAsViewed'));
		}
		if ($this->isActionAllowed('repondre') && $this->canSetAction('repondre')) {
			$buttons .= BimpRender::renderRowButton('Répondre', 'fas_reply', $this->getJsRepondre());

			if ($email_cc) {
				$buttons .= BimpRender::renderRowButton('Répondre à tous', 'fas_reply-all', $this->getJsRepondre(true));
			}
		}

		$html .= '<div class="bimp_chat_msg_container ' . $side . '">';
		$html .= '<div class="bimp_chat_msg">';

		$html .= '<div class="bimp_chat_msg_header">';
		$html .= '<span class="bimp_chat_author">De : ' . $author . '</span>';
		$html .= '<span class="bimp_chat_date">' . $this->displayDate(true) . '</span>';
		$html .= '</div>';

		$html .= '<div class="bimp_chat_msg_body">';
		$html .= '<div class="bimp_chat_author_badge">' . $this->displayAuthorBadge() . '</div>';
		$html .= '<div class="bimp_chat_msg_content">';
		$html .= $this->displayData("content");
		$html .= '</div>';
		$html .= '<div class="bimp_chat_dest_badge">' . $this->displayDestBadge() . '</div>';
		$html .= '</div>';

		$html .= '<div class="bimp_chat_msg_footer">';
		if ($dest) {
			$html .= '<div class="bimp_chat_dest">À : ' . $dest;
			if ($email_cc) {
				$html .= '<br/>CC : ' . $email_cc;
			}
			$html .= '</div>';
		}
		if ($buttons) {
			$html .= '<div class="bimp_chat_buttons">' . $buttons . '</div>';
		}
		$html .= '</div>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public function displayChatMsg_old($style = '', $checkview = false)
	{
		global $user;
		$html = "";

		$author = $this->displayAuthor(false, true);

		$parent = $this->getParentInstance();
		if ($parent && is_a($parent, 'Bimp_Ticket')) {
			$position = ($this->getData('type_author') == self::BN_AUTHOR_SOC || $this->getData('type_author') == self::BN_AUTHOR_FREE) ? "start" : "end";
		} else {
			$position = ($this->isUserDest() ? "start" : ($this->isUserAuthor() ? "end" : ""));
		}

		$html .= '<div class="d-flex justify-content-' . $position . ($style == "petit" ? ' petit' : '') . ' mb-4">';

		$html .= BimpTools::getBadge($this->getInitiales($author), ($style == "petit" ? '35' : '55'), ($this->getData('type_author') == self::BN_AUTHOR_USER ? 'info' : 'warnings'), $author);

		$html .= '<div class="msg_cotainer">' . $this->displayData("content");
		if ($style != "petit" && $this->getData('user_create') != $user->id) {
			$html .= '<span class="rowButton bs-popover"><i class="fas fa-share link" onclick="' . $this->getJsRepondre() . '"></i></span>';
		}
		if ($style != "petit" && $this->isActionAllowed('setAsViewed') && $this->canSetAction('setAsViewed')) {
			$html .= '<span class="rowButton bs-popover"><i class="far fa5-envelope-open" onclick="' . $this->getJsActionOnclick('setAsViewed') . '"></i></span>';
		}

		$html .= '<span class="msg_time">' . dol_print_date($this->db->db->jdate($this->getData("date_create")), "%d/%m/%y %H:%M:%S") . '</span>
                                                                </div>';
		if ($this->getData('type_dest') != self::BN_DEST_NO) {
			$dest = $this->displayDestinataire(false, true);
			if ($dest != "") {
				$html .= BimpTools::getBadge($this->getInitiales($dest), ($style == "petit" ? '28' : '45'), ($this->getData('type_dest') == self::BN_DEST_USER ? 'info' : 'warnings'), $dest);
			}
		}
		$html .= "";

		$html .= '</div>';

		if ($checkview && !(int) $this->getData('viewed') && $this->isUserDest()) {
			$this->updateField('viewed', 1);
		}
		return $html;
	}

	// Rendus HTML :

	public function renderModelInput()
	{
		$html = '';

		$obj_module = $this->getData('obj_module');
		$obj_name = $this->getData('obj_name');

		if ($obj_module && $obj_name) {
			$modele = BimpObject::getInstance('bimpcore', 'BimpNoteModel');
			$modele->set('obj_module', $obj_module);
			$modele->set('obj_name', $obj_name);

			$html .= BimpInput::renderInput('select', 'note_modele', 0, array(
				'options' => $modele->getModelsArray($obj_module, $obj_name)
			));

			if ($modele->can('view')) {
				$html .= '<div class="buttonsContainer" style="text-align: right; margin-top: 10px">';

				$obj = BimpObject::getInstance($obj_module, $obj_name);
				$onclick = $modele->getJsLoadModalList('obj', array(
					'title'         => 'Modèles de notes des ' . $obj->getLabel('name_plur'),
					'extra_filters' => array(
						'obj_module' => $obj_module,
						'obj_name'   => $obj_name
					)
				));
				$html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
				$html .= BimpRender::renderIcon('far_file-alt', 'iconLeft') . 'Gérer les modèles';
				$html .= '</span>';

				$onclick = 'reloadObjectInput($(this).findParentByClass(\'object_form\').attr(\'id\'), \'note_modele\', {obj_module: \'' . $obj_module . '\', obj_name: \'' . $obj_name . '\'});';
				$html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
				$html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
				$html .= '</span>';
				$html .= '</div>';
			}
		}

		return $html;
	}

	public function renderCustomContentInput()
	{
		$value = $this->getContentDefaultValue(false);

		return BimpInput::renderInput('html', 'content', $value, array(
			'hashtags'               => 1,
			'field_path'             => 1,
			'field_path_module'      => $this->getData('obj_module'),
			'field_path_object_name' => $this->getData('obj_name')
		));
	}

	// Traitements:

	public function traiteContent()
	{
		$note = $this->getData('content');
		$note = trim($note);
		$tab = array(CHR(13) . CHR(10) => "[saut]", CHR(13) . CHR(10) . ' ' => "[saut]", CHR(10) => "[saut]");
		$tab2 = array("[saut][saut][saut][saut][saut][saut]" => CHR(13) . CHR(10) . CHR(13) . CHR(10), "[saut][saut][saut][saut][saut]" => CHR(13) . CHR(10) . CHR(13) . CHR(10), "[saut][saut][saut][saut]" => CHR(13) . CHR(10) . CHR(13) . CHR(10), "[saut][saut][saut]" => CHR(13) . CHR(10) . CHR(13) . CHR(10), "[saut]" => CHR(13) . CHR(10));
		$note = strtr($note, $tab);
		$note = strtr($note, $tab2);
		$note = strtr($note, $tab);
		$note = strtr($note, $tab2);
		$note = strtr($note, $tab);
		$note = strtr($note, $tab2);
//        die('<textarea>'.$note.'</textarea>');
		$this->set('content', $note);
	}

	public function repMail($dst, $src, $subj, $txt)
	{
		$matches = array();
		preg_match('(.*@bimp-groupe.net)', $txt, $matches);
		if (isset($matches[0])) {
			$tabTxt = explode($matches[0], $txt);
			$txt = $tabTxt[0];
		}

		$errors = array();
		$data = array();
		$data['obj_type'] = $this->getData('obj_type');
		$data['obj_module'] = $this->getData('obj_module');
		$data['obj_name'] = $this->getData('obj_name');
		$data['id_obj'] = $this->getData('id_obj');
		$data['id_parent_note'] = $this->id;
		$data['type_author'] = self::BN_AUTHOR_SOC;
		$data['email'] = $src;
		$data['content'] = $txt;
		$data['type_dest'] = $this->getData('type_author');
		$data['fk_group_dest'] = $this->getData('fk_group_author');
		$data['fk_user_dest'] = $this->getData('user_create');
		$parent = $this->getParentInstance();
		if ($parent->getData('id_client') > 0) {
			$data['id_societe'] = $parent->getData('id_client');
		} elseif ($parent->getData('id_soc') > 0) {
			$data['id_societe'] = $parent->getData('id_soc');
		} elseif ($parent->getData('fk_soc') > 0) {
			$data['id_societe'] = $parent->getData('fk_soc');
		}


		//gestion des PJ
		$dir = $parent->getFilesDir() . "/";
		if (!is_dir($dir)) {
			mkdir($dir);
		}
		foreach ($_FILES as $fileT) {
			$nameFile = $fileT['name'];
			$file = BimpTools::cleanStringForUrl(str_replace('.' . pathinfo($nameFile, PATHINFO_EXTENSION), '', $nameFile)) . '.' . pathinfo($nameFile, PATHINFO_EXTENSION);
			$data['content'] .= '<br/>Ajout de la PJ ' . $file;

			move_uploaded_file($fileT['tmp_name'], $dir . $file);
		}


		if (!count($errors)) {
			$obj = BimpObject::createBimpObject($this->module, $this->object_name, $data, true, $errors, $warnings);
			if (!count($errors)) {
				return 1;
			} else {
				BimpCore::addlog('Création reponse mail impossible', 1, 'bimpcore', $this, $errors);
			}
		}
		return 0;
	}

	// Actions:

	public function actionRepondre($data, &$success = '')
	{
		$errors = array();
		$warnings = array();

		global $user;

		if ($this->getData('viewed') == 0 && $this->isUserDest()) {
			$this->updateField('viewed', 1);
		}

		$data["user_create"] = $user->id;
		$data["viewed"] = 0;
		$data['id_parent_note'] = $this->id;

		if ((int) $this->getData('visibility') === self::BN_PARTNERS) {
			$data['visibility'] = self::BN_PARTNERS;
			$data['type_author'] = self::BN_AUTHOR_USER;
		}

		if (!count($errors)) {
			BimpObject::createBimpObject($this->module, $this->object_name, $data, true, $errors, $warnings);
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	public function actionSetAsViewed($data, &$success = '')
	{
		$errors = array();
		$warnings = array();
		$success = '';

		if (isset($data['id_objects'])) {
			$nOk = 0;
			$ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

			foreach ($ids as $id_note) {
				$note = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Note', $id_note);
				if (BimpObject::objectLoaded($note)) {
					if ($note->isActionAllowed('setAsViewed') && $note->canSetAction('setAsViewed')) {
						$note_errors = array();
						if ((int) $this->getData('delete_on_view')) {
							$note_errors = $note->delete($warnings, true);
						} else {
							$note_errors = $note->updateField('viewed', 1);
						}
						if (!count($note_errors)) {
							$nOk++;
						}
					}
				}
			}

			if ($nOk > 1) {
				$success = $nOk . ' notes marquées vues avec succès';
			} else {
				$success = $nOk . ' note marquée vue avec succès';
			}
		}
		if ($this->isLoaded($errors)) {
			$success = 'Marquée comme vue';
			if ((int) $this->getData('delete_on_view')) {
				$errors = $this->delete($warnings, true);
			} else {
				$errors = $this->updateField('viewed', 1);
			}
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	// Overrrides:

	public function validate()
	{
		$this->traiteContent();

		if (in_array((int) $this->getData('visibilty'), array(3, 4))) {
			BimpCore::addlog('Visibilité note à modifier', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this, array(
				'visibilité' => $this->getData('visibilty'),
				'Info'       => 'Les identifiants ont changé : remplacer dans le code PHP 3 par 10 et 4 par 20.<br/>Toujours utliser les constantes de classes quand elles existent(ex : BimpNote::BN_ALL) et jamais les valeurs numériques directement.'
			), true);
			switch ($this->getData('visiblity')) {
				case 3:
					$this->set('visiblity', self::BN_MEMBERS);
					break;
				case 4:
					$this->set('visiblity', self::BN_ALL);
					break;
			}
		}

		$errors = parent::validate();

		if (!count($errors)) {
			$parent = $this->getParentInstance();

			if (BimpObject::objectLoaded($parent)) {
//				$content = $this->getData('content');
//				$content = $parent->replaceFieldsValues($content, true, $errors);
//				$this->set('content', $content);
			} else {
				$errors[] = 'Objet parent absent ou invalide';
			}

			switch ((int) $this->getData('type_author')) {
				case self::BN_AUTHOR_USER:
					break;

				case self::BN_AUTHOR_SOC:
					if (!(int) $this->getData('id_societe')) {
						$parent = $this->getParentInstance();
						if ($parent->isLoaded() && $parent->field_exists('fk_soc') && $parent->getData('fk_soc') > 0) {
							$this->set('id_societe', $parent->getData('fk_soc'));
						} else {
							$errors[] = 'Société à l\'origine de la note absente';
						}
					}
					break;

				case self::BN_AUTHOR_FREE:
					if (!(string) $this->getData('email')) {
						$errors[] = 'Adresse e-mail absente';
					}
					break;
			}
		}

		return $errors;
	}

	public function create(&$warnings = array(), $force_create = false)
	{
		$errors = array();
		$content = $this->getData('content');
		$email_to = '';
		$email_cc = '';
		$email_from = '';

		$type_dest = $this->getData('type_dest');
		if ($type_dest == self::BN_DEST_SOC) {
			$this->set('visiblity', self::BN_ALL);

			$type_email = BimpTools::getPostFieldValue('type_email_dest', '', 'aZ09');

			if (!$type_email) {
				$errors[] = 'Aucun destinataire sélectionné pour l\'envoi par e-mail';
			} else {
				if ($type_email == 'custom') {
					$email_to = BimpTools::getPostFieldValue('email_dest_custom', '', 'email');
				} elseif (preg_match('/^(.+)_(\d+)$/', $type_email, $matches)) {
					switch ($matches[1]) {
						case 'soc':
							$soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $matches[2]);
							if (BimpObject::objectLoaded($soc)) {
								$email_to = $soc->getData('email');
							}
							break;

						case 'contact':
							$contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $matches[2]);
							if (BimpObject::objectLoaded($contact)) {
								$email_to = $contact->getData('email');
							}
							break;
					}
				}

				if (!$email_to) {
					$errors[] = 'Adresse e-mail du destinataire absente';
				} else {
					$email_to = BimpTools::cleanEmailsStr($email_to);
					if (!BimpValidate::isEmail($email_to)) {
						$errors[] = 'Adresse e-mail du destinataire invalide : "' . $email_to . '"';
					}
				}


			}

			$email_from = BimpTools::cleanEmailsStr(BimpTools::getPostFieldValue('email_from', '', 'email'));
			$emails_copy = BimpTools::getPostFieldValue('email_to_copy', array(), 'array');

			if (!empty($emails_copy)) {
				foreach ($emails_copy as $email_copy) {
					$email_copy = BimpTools::cleanEmailsStr($email_copy);
					if (!BimpValidate::isEmail($email_copy)) {
						$errors[] = 'Adresse e-mail en copie invalide : "' . $email_copy . '"';
					} else {
						$email_cc .= ($email_cc ? ', ' : '') . $email_copy;
					}
				}

				$this->set('email_cc', $email_cc);
			}

			$this->set('email', $email_to);
		}

		if (!count($errors)) {
			$errors = parent::create($warnings, $force_create);

			if ($type_dest == self::BN_DEST_SOC) {
//				$sep = "<br/>---------------------<br/>";
				$html = '';

				if ($this->getData('id_parent_note') > 0) {
					$html .= 'Réponse à votre message : <br/>';
				}

				$html .= $content;
//                if ($this->getData('id_parent_note') > 0) {
//                    $oldNote = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $this->getData('id_parent_note'));
//                    $html .= '<br/><br/>Rappel du message initial : <br/>' . $oldNote->getData('content');
//                }
				$parent = $this->getParentInstance();

				$messageId = '';
				if ($parent->isLoaded() && $parent->field_exists('email_msgid') && $parent->getData('email_msgid')) {
					$messageId = $parent->getData('email_msgid');
				}
				$subject = 'Nouveau message';
				if ($parent->isLoaded() && method_exists($parent, 'getObjectMail')) {
					$subject = $parent->getObjectMail();
				}

				$email_errors = array();
				$bimpMail = new BimpMail($this->getParentInstance(), $subject, $email_to, ($email_from ? $email_from : $this->getMailFrom()), $html, '', $email_cc, '', 0, '', $messageId);
				if (!$bimpMail->send($email_errors)) {
					$errors[] = BimpTools::getMsgFromArray($email_errors, 'Echec de l\'envoi de l\'e-mail');
				}
			}

			if (!count($errors)) {
				$obj = $this->getParentInstance();
				if (is_object($obj) && $obj->isLoaded() && method_exists($obj, 'afterCreateNote')) {
					$obj->afterCreateNote($this);
				}
			}
		}

		return $errors;
	}

	public function update(&$warnings = array(), $force_update = false)
	{
		$return = parent::update($warnings, $force_update);
		return $return;
	}

	public function fetch($id, $parent = null)
	{
		$return = parent::fetch($id, $parent);

		// Par précaution + compat avec les notes archivées:
		if (in_array($this->getData('visibility'), array(3, 4))) {
			switch ($this->getData('visibility')) {
				case 3:
					$this->set('visibility', self::BN_MEMBERS);
					break;

				case 4:
					$this->set('visibility', self::BN_ALL);
					break;
			}
		}
		return $return;
	}

	// Méthodes statiques:

	public static function copyObjectNotes($object_src, $object_dest)
	{
		$errors = array();

		if (!is_a($object_src, 'BimpObject') || !BimpObject::objectLoaded($object_src)) {
			$errors[] = 'Objet source invalide';
		}

		if (!is_a($object_dest, 'BimpObject') || !BimpObject::objectLoaded($object_dest)) {
			$errors[] = 'Objet de destination invalide';
		}

		if (!count($errors)) {
			$notes = BimpCache::getBimpObjectObjects('bimpcore', 'BimpNote', array(
				'obj_type'   => 'bimp_object',
				'obj_module' => $object_src->module,
				'obj_name'   => $object_src->object_name,
				'id_obj'     => (int) $object_src->id
			));

			foreach ($notes as $note) {
				$newNote = BimpObject::getInstance('bimpcore', 'BimpNote');
				$newNote->validateArray($note->getDataArray());
				$newNote->set('obj_type', 'bimp_object');
				$newNote->set('obj_module', $object_dest->module);
				$newNote->set('obj_name', $object_dest->object_name);
				$newNote->set('id_obj', (int) $object_dest->id);

				$warnings = array();
				$create_errors = $newNote->create($warnings, true);

				if (count($create_errors)) {
					$errors[] = BimpTools::getMsgFromArray($create_errors, 'Echec de la copie de la note #' . $note->id);
				}
			}
		}

		return $errors;
	}

	public static function getMyConversations($notViewedInFirst = true, $limit = 10)
	{
		global $user;
		$listIdGr = self::getUserUserGroupsList($user->id);
		$reqDeb = "SELECT `obj_type`,`obj_module`,`obj_name`,`id_obj`, MIN(viewed) as mviewed, MAX(date_create) as mdate_create, MAX(id) as idNoteRef FROM `" . MAIN_DB_PREFIX . "bimpcore_note` "
			. "WHERE "; //auto = 0 AND ";
		$where = "(type_dest = 1 AND fk_user_dest = " . $user->id . ") "
			. "         OR (type_dest = 4 AND fk_group_dest IN ('" . implode("','", $listIdGr) . "'))"
			. "         ";
		$reqFin = " GROUP BY `obj_type`,`obj_module`,`obj_name`,`id_obj`";
//        if($notViewedInFirst)
//            $reqFin .= " ORDER by mviewed ASC";
//        else
		$reqFin .= " ORDER by mdate_create DESC";
		$reqFin .= " LIMIT 0," . $limit;
		$tabFils = array();
		$tabNoDoublons = array();
		$tabReq = array($reqDeb . "(" . $where . ") AND viewed = 0 " . $reqFin, $reqDeb . "(" . $where . " OR (type_author = 1 AND user_create = " . $user->id . "))" . $reqFin);
//        echo '<pre>';
//        print_r($tabReq);
//        die();
		foreach ($tabReq as $rang => $req) {
			$sql = self::getBdb()->db->query($req);
			if ($sql) {
				while ($ln = self::getBdb()->db->fetch_object($sql)) {
					$hash = $ln->obj_module . $ln->obj_name . $ln->id_obj;
					if (!isset($tabNoDoublons[$hash])) {
						$tabNoDoublons[$hash] = true;
						if ($ln->obj_type == "bimp_object") {
							$tabFils[] = array("lu" => $rang, "obj" => BimpObject::getInstance($ln->obj_module, $ln->obj_name, $ln->id_obj), "idNoteRef" => $ln->idNoteRef);
						}
					}
				}
			}
		}
		return $tabFils;
	}

	public static function getNotesForUser($id_user, $tms = '', $options = array(), &$errors = array())
	{
		if ((int) BimpCore::getConf('mode_eco')) {
			return array();
		}

		$options = BimpTools::overrideArray(array(
			'max' => 30
		), $options);

		$data = array(
			'tms'      => date('Y-m-d H:i:s'),
			'elements' => array()
		);

		$notes = self::getUserNewNotes($tms, $options['max'], $id_user, false);

		$users_delegations = array();

//        $bdb = self::getBdb();
//        $users_delegations = $bdb->getValues('user', 'rowid', 'delegations LIKE \'%[' . $id_user . ']%\'');
//        if (!empty($users_delegations)) {
//            foreach ($users_delegations as $id_user_delegation) {
//                $user_notes = self::getUserNewNotes($tms, $options['max'], $id_user_delegation, false);
//                foreach ($user_notes as $id_user_note) {
//                    if (!in_array($id_user_note, $notes)) {
//                        $notes[] = $id_user_note;
//                    }
//                }
//            }
//        }

		foreach ($notes as $id_note) {
			$note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', $id_note);
			$obj_link = '';

			$id_obj = (int) $note->getData('id_obj');
			if ($id_obj) {
				$bc = BimpCollection::getInstance($note->getData('obj_module'), $note->getData('obj_name'));
				$obj_link = $bc->getLink($id_obj, array('card' => ''));
				$obj_url = $bc->getUrl($id_obj, 'private');
			}

			if (BimpObject::objectLoaded($note)) {
				$data['elements'][$id_note] = array(
					'sort_val'            => $id_note,
					'id'                  => $id_note,
					'content'             => $note->displayDataDefault('content'),
					'date_create'         => $note->getData('date_create'),
					'is_user_author'      => $note->isUserAuthor(),
					'is_user_dest'        => $note->isUserDest($users_delegations),
					'is_dest_user_or_grp' => (int) ($note->getData('type_dest') != self::BN_DEST_NO),
					'is_viewed'           => (int) $note->getData('viewed'),
					'obj_link'            => $obj_link,
					'obj_url'             => $obj_url,
					'obj_module'          => $note->getData('obj_module'),
					'obj_name'            => $note->getData('obj_name'),
					'id_obj'              => $id_obj,
					'author'              => array(
						'id'  => (int) $note->getData('user_create'),
						'nom' => $note->displayAuthor(false, true)
					),
					'dest'                => array(
						'nom' => $note->displayDestinataire(false, true)
					)
				);
			}
		}

		return $data;
	}

	public static function getUserNewNotes($tms = '', $limit = 10, $idUser = null, $onlyNotViewed = false)
	{
		if (is_null($idUser)) {
			global $user;
			$idUser = $user->id;
		}

		$bdb = BimpCache::getBdb();
		$user_groups = self::getUserUserGroupsList($idUser);
		$notes = array();

		$where = '';

		if ($onlyNotViewed) {
			$where .= 'a.viewed = 0 AND ';
		}

		if ($tms) {
			$where .= 'a.tms > \'' . $tms . '\' AND ';
		}

		$where .= '(';

		$where .= '(a.type_dest = 1 AND a.fk_user_dest = ' . $idUser . ')';
		if (!empty($user_groups)) {
			$where .= ' OR (a.type_dest = 4 AND a.fk_group_dest IN (' . implode(',', $user_groups) . '))';
		}

		if (!$onlyNotViewed) {
			$where .= ' OR (a.type_author = 1 AND a.user_create = ' . $idUser . ' AND a.type_dest > 0)';
		}

		$where .= ')';

		if (!$onlyNotViewed && !(int) BimpCore::getConf('mode_eco')) {
			// Si non lu ou si aucune autre note plus récente pour le même objet :
			$where .= ' AND (a.viewed = 0 OR (';
			$where .= '(SELECT COUNT(b.id) FROM ' . MAIN_DB_PREFIX . 'bimpcore_note b WHERE b.obj_name = a.obj_name AND b.id_obj = a.id_obj AND b.id > a.id) = 0';
			$where .= '))';
		}

		$rows = $bdb->getRows('bimpcore_note a', $where, $limit, 'array', array('DISTINCT a.id'), 'id', 'DESC');

		if (is_array($rows)) {
			foreach ($rows as $r) {
				$notes[] = $r['id'];
			}
		}

		return $notes;
	}

	public static function cronNonLu()
	{
		$listUser = BimpObject::getBimpObjectList('bimpcore', 'Bimp_User', array('statut' => 1));

		$bdb = BimpCache::getBdb();
		$maxForMail = 20;
		$erp_name = BimpCore::getConf('erp_name');

		foreach ($listUser as $idUser) {
			$html = '';
			$notes = BimpNote::getUserNewNotes('', 500, $idUser, true);

			if (!empty($notes)) {
				$n = count($notes);
				$s = ($n > 1 ? 's' : '');

				$subject = $n . ' message' . $s . ' non lu' . $s . ' dans l\'ERP' . ($erp_name ? ' ' . $erp_name : '');
				$html .= 'Bonjour vous avez ' . $n . ' message' . $s . ' non lu' . $s . ' : <br/>';

				if ($n > $maxForMail) {
					$html .= 'Voici les ' . $maxForMail . ' derniers<br/>';
				}
				$html .= '<br/>Pour désactiver cette relance, vous pouvez : ';
				$html .= '<br/>- soit répondre au message de la pièce émettrice (dans les notes de pied de page) <br/>';
				$html .= '- soit cliquer sur la petite enveloppe "Message" en haut à droite de la page ERP.';

				$i = 0;
				foreach ($notes as $id_note) {
					$i++;
					if ($i > $maxForMail) {
						break;
					}

					$note = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNote', $id_note);
					$html .= '<br/><br/>';

					$author = $note->displayAuthor(false, true);
					$link = $note->getParentLink();

					$html .= '<b>Message' . ($author ? ' de ' . $author : '') . ($link ? ' concernant ' . $link : '') . ' : </b><br/>';
					$html .= '<i>' . $note->displayDataDefault('content') . '</i>';
				}

				$code = 'message_ERP_nonlu';
				BimpUserMsg::envoiMsg($code, $subject, $html, $idUser);
			}
		}

		return '';
	}
}

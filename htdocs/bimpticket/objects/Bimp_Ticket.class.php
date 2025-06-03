<?php
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class Bimp_Ticket extends BimpDolObject
{
	/* @var Ticket */
	public $dol_object;

	public static $dol_module = 'ticket';

	public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old

	const STATUS_DRAFT = 0;
	const STATUS_READ = 1;
	const STATUS_ASSIGNED = 2;
	const STATUS_IN_PROGRESS = 3;
	const STATUS_NEED_MORE_INFO = 5;
	const STATUS_WAITING = 7;
	const STATUS_CLOSED = 8;
	const STATUS_CANCELED = 9;
	const STATUS_TRANSFERED = 11;

	public static $status_list = array(
		self::STATUS_DRAFT          => array('label' => 'Nouveau', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
		self::STATUS_READ           => array('label' => 'A assigner', 'icon' => 'fas_exclamation-circle', 'classes' => array('warning')),
		self::STATUS_ASSIGNED       => array('label' => 'Assigné', 'icon' => 'fas_user-check', 'classes' => array('info')),
		self::STATUS_IN_PROGRESS    => array('label' => 'En cours', 'icon' => 'fas_cogs', 'classes' => array('info')),
		self::STATUS_NEED_MORE_INFO => array('label' => 'En attente d\'infos', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
		self::STATUS_WAITING        => array('label' => 'En attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
		self::STATUS_CLOSED         => array('label' => 'Terminé', 'icon' => 'fas_check', 'classes' => array('success')),
		self::STATUS_CANCELED       => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('danger')),
		self::STATUS_TRANSFERED     => array('label' => 'Transféré', 'icon' => 'fas_sign-out-alt', 'classes' => array('important')),
	);

	const MAIL_TICKET_GENERAL = 'test@test.fr';

	public static $types = array();
	public static $mail_typeTicket = array(); // A définir dans les entités

	// Droits users :

	public function canSetAction($action)
	{
		global $user;

		switch ($action) {
			case 'assign':
				return $user->admin || $user->rights->bimpticket->assign;

			case 'newStatus':
				if ($user->admin || $user->rights->bimpticket->assign) {
					return 1;
				}
				if ($this->getData('fk_user_assign') == $user->id) {
					return 1;
				}
				return 0;
		}

		return parent::canSetAction($action);
	}

	public function canDelete()
	{
		global $user;
		return ($user->rights->ticket->delete ? 1 : 0);
	}

	// Getters booléens:

	public function isActionAllowed($action, &$errors = array())
	{
		switch ($action) {
			case 'assign':
				if ($this->isLoaded()) {
					if ((int) $this->getData('fk_statut') >= self::STATUS_CLOSED) {
						$errors[] = 'Ticket déjà fermé';
						return 0;
					}
				}
				return 1;

			case 'newStatus':
				if ($this->isLoaded()) {
					$cur_status = (int) $this->getData('fk_statut');
					if ($cur_status < self::STATUS_ASSIGNED) {
						$errors[] = 'Ticket non assigné';
						return 0;
					}
				}
				return 1;
		}
		return parent::isActionAllowed($action, $errors);
	}

	// Getters données:

	public function getAddContactIdClient()
	{
		return (int) BimpTools::getPostFieldValue('fk_soc', (int) $this->getData('fk_soc'), 'int');
	}

	// Getters params:

	public function getHeaderButtons()
	{
		$buttons = array();

		if ($this->isActionAllowed('assign') && $this->canSetAction('assign')) {
			$id_user = (int) $this->getData('fk_user_assign');

			$buttons[] = array(
				'label'   => ($id_user ? 'Changer d\'assignation' : 'Assigner'),
				'icon'    => 'fas_user-plus',
				'onclick' => $this->getJsActionOnclick('assign', array(), array(
					'form_name' => 'assign'
				))
			);
		}
		if ($this->isActionAllowed('newStatus') && $this->canSetAction('newStatus')) {
			$cur_status = (int) $this->getData('fk_statut');
			$buttons[] = array(
				'label'   => 'Changer le statut',
				'icon'    => 'fas_pen',
				'onclick' => $this->getJsActionOnclick('newStatus', array(), array(
					'form_name' => 'new_status'
				))
			);

			if ($cur_status < self::STATUS_IN_PROGRESS) {
				if ($this->isActionAllowed('newStatus') && $this->canSetAction('newStatus')) {
					$buttons[] = array(
						'label'   => 'En cours',
						'icon'    => 'fas_cogs',
						'onclick' => $this->getJsActionOnclick('newStatus', array(
							'new_status' => self::STATUS_IN_PROGRESS,
						), array())
					);
				}
			} elseif ($cur_status < self::STATUS_CLOSED) {
				$buttons[] = array(
					'label'   => 'Terminé',
					'icon'    => 'fas_check',
					'onclick' => $this->getJsActionOnclick('newStatus', array(
						'new_status' => self::STATUS_CLOSED,
					), array())
				);
			}
		}

		if ($this->isLoaded($errors)) {
			$note = BimpObject::getInstance("bimpcore", "BimpNote");

			$buttons[] = array(
				'label'   => 'E-mail Tier',
				'icon'    => 'fas_envelope',
				'onclick' => $note->getJsLoadModalForm('default', 'Envoyer un e-mail au tiers', array(
					'fields' => array(
						"obj_type"    => "bimp_object",
						"obj_module"  => $this->module,
						"obj_name"    => $this->object_name,
						"id_obj"      => $this->id,
						'visibility'  => $note::BN_ALL,
						'type_author' => $note::BN_AUTHOR_USER,
						"type_dest"   => $note::BN_DEST_SOC
					)
				))
			);
		}

		return $buttons;
	}

	// Getters arrays:

	public function getNewStatusOptionsArray()
	{
		$cur_status = (int) $this->getData('fk_statut');

		$options = self::$status_list;

		unset($options[$cur_status]);
		if ($cur_status != self::STATUS_READ) {
			unset($options[self::STATUS_READ]);
		}
		if ($cur_status != self::STATUS_ASSIGNED) {
			unset($options[self::STATUS_ASSIGNED]);
		}
		if ($cur_status != self::STATUS_NEED_MORE_INFO) {
			unset($options[self::STATUS_NEED_MORE_INFO]);
		}

		if ($cur_status >= self::STATUS_IN_PROGRESS) {
			unset($options[self::STATUS_DRAFT]);
		}

		return $options;
	}

	public function getDirOutput()
	{
		global $conf;
		$ref = dol_sanitizeFileName($this->dol_object->ref);
		if ($this->isLoaded() && $this->dol_object->entity > 0) {
			return $conf->ticket->multidir_output[$this->dol_object->entity] . '/';
		} else {
			return $conf->ticket->dir_output . '/';
		}
	}

	// Affichages :

	public function displayLastNoteClient()
	{
		$html = '';

		$note = $this->getChildObject('last_note_client');

		if (BimpObject::objectLoaded($note)) {
			$txt = trim(BimpTools::htmlToString($note->getData('content'), 1200));
			$html .= '<span class="bs-popover" ' . BimpRender::renderPopoverData($txt, 'bottom', 'true') . ' style="display: inline-block">';
			$html .= date('d / m / Y H:i', strtotime($note->getData('date_create'))) . '&nbsp;&nbsp;';
			if ((int) $note->getData('viewed')) {
				$html .= '<span class="success">';
				$html .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Lu';
				$html .= '</span>';
			} else {
				$html .= '<span class="danger">';
				$html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non lu';
				$html .= '</span>';
			}
			$html .= '</span>';
		}

		return $html;
	}

	public function displayNbNotesUnread()
	{
		$html = '';

		if ($this->isLoaded($errors)) {
			$nb = $this->db->getCount('bimpcore_note', 'obj_name = \'Bimp_Ticket\' AND id_obj = \'' . $this->id . '\' AND type_author IN (2,3) AND viewed = 0');

			if (!$nb) {
				$html .= '<span class="badge badge-success">0</span>';
			} else {
				$html .= '<span class="badge badge-danger">' . $nb . '</span>';
			}
		}

		return $html;
	}

	// Rendus HTML :

	public function renderHeaderExtraLeft()
	{
		$html = '';

		if ($this->isLoaded($errors)) {
			$datec = $this->getData('datec');
			if ($datec) {
				$html .= '<div class="object_header_infos">';
				$html .= 'Créé le ' . $this->displayDataDefault('datec');

				if ((int) $this->getData('fk_user_create')) {
					$user_create = $this->getChildObject('user_create');
					if (BimpObject::objectLoaded($user_create)) {
						$html .= ' par ' . $user_create->getLink();
					}
				}
				$html .= '</div>';
			}

			$dateu = $this->getData('date_update');
			if ($dateu) {
				$html .= '<div class="object_header_infos">';
				$html .= 'Dernière mise à jour le ' . $this->displayDataDefault('date_update');

				if ((int) $this->getData('fk_user_update')) {
					$user_update = $this->getChildObject('user_update');
					if (BimpObject::objectLoaded($user_update)) {
						$html .= ' par ' . $user_update->getLink();
					}
				}
				$html .= '</div>';
			}

			$date_close = $this->getData('date_close');
			if ($date_close) {
				$html .= '<div class="object_header_infos">';
				$html .= 'Fermée le ' . $this->displayDataDefault('date_close');
				$html .= '</div>';
			}

			if ((int) $this->getData('fk_user_assign')) {
				$user_assign = $this->getChildObject('user_assign');
				if (BimpObject::objectLoaded($user_assign)) {
					$html .= '<div style="margin-top: 10px">';
					$html .= '<b>Assigné à</b> ' . $user_assign->getLink();
					$html .= '</div>';
				}
			}
			$client = $this->getChildObject('client');
			if (BimpObject::objectLoaded($client)) {
				$html .= '<div style="margin-top: 10px">';
				$html .= '<b>Marchand : </b> ' . $client->getLink();
				$html .= '</div>';
			}
		}

		return $html;
	}

	public function renderNotifyEmailInput()
	{
		$html = '';

		$soc = $this->getChildObject('client');
		if (BimpObject::objectLoaded($soc)) {
			$values = array();
			$email = $soc->getData('email');
			if ($email) {
				$values[$email] = 'Tiers (' . $email . ')';
			}

			$id_contact = (int) BimpTools::getPostFieldValue('id_contact_suivi');
			if ($id_contact) {
				/** @var Bimp_Contact $contact */
				$contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);

				if (BimpObject::objectLoaded($contact)) {
					$email = $contact->getData('email');
					if ($email) {
						$values[$email] = 'Contact suivi (' . $email . ')';
					}
				}
			}

			return BimpInput::renderInput('select', 'notify_email', 'client', array(
				'options' => $values
			));
		} else {
			$html .= '<span class="danger">';
			$html .= 'Aucun marchand sélectionné';
			$html .= '</span>';
			$html .= '<input type="hidden" value="" name="notify_email" />';
		}


		return $html;
	}

	public function renderDescription()
	{
		$html = $this->displayDataDefault('message');
		$title = BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Description';

		$images = $this->renderImages(false);
		if ($images) {
			$html .= ($html ? '<br/><br/>' : '') . '<div style="font-size: 14px; border-top: 1px solid #999; margin-top: 10px; padding-top: 10px">' . BimpRender::renderIcon('fas_images', 'iconLeft') . 'Images liées : </div>' . $images;
		}

		return BimpRender::renderPanel($title, $html, '', array('type' => 'secondary'));
	}
	// Traitements :

	public function checkStatus()
	{
		$cur_status = (int) $this->getData('fk_statut');

		if (!(int) $this->getData('fk_user_assign')) {
			if ($cur_status < self::STATUS_CLOSED) {
				$this->updateField('fk_statut', self::STATUS_READ);
			}
		} elseif ($cur_status <= self::STATUS_READ) {
			$this->updateField('fk_statut', self::STATUS_ASSIGNED);
		}
	}

	public function checkUserAssigned($check_status = true, $init_user_assign = 0)
	{
		if ($this->isLoaded()) {
			$fk_user_assigned = (int) $this->getData('fk_user_assign');
			$users_assigned = $this->dol_object->getIdContact('internal', 'SUPPORTTEC');

			if ($fk_user_assigned) {
				$id_type_contact = $this->db->getValue('c_type_contact', 'rowid', 'element = \'ticket\' AND source = \'internal\' AND code = \'SUPPORTTEC\'');
				$this->db->delete('element_contact', 'fk_c_type_contact = ' . $id_type_contact . ' AND element_id = ' . $this->id . ' AND fk_socpeople != ' . $fk_user_assigned);

				if (!(int) $this->db->getValue('element_contact', 'rowid', 'fk_c_type_contact = ' . $id_type_contact . ' AND element_id = ' . $this->id . ' AND fk_socpeople = ' . $fk_user_assigned)) {
					$this->dol_object->add_contact($fk_user_assigned, 'SUPPORTTEC', 'internal');
				}
			} else {
				$users_assigned = $this->dol_object->getIdContact('internal', 'SUPPORTTEC');
				if (isset($users_assigned[0]) && (int) $users_assigned[0]) {
					$this->updateField('fk_user_assign', (int) $users_assigned[0]);
					$fk_user_assigned = (int) $users_assigned[0];
				}
			}

			if ($init_user_assign !== $fk_user_assigned) {
				$this->addObjectLog('Assigné à ' . ($fk_user_assigned ? '{{Utilisateur:' . $fk_user_assigned . '}}' : 'personne'), 'ASSIGNED');
			}

			if ($check_status) {
				$this->checkStatus();
			}
		}
	}

	public function onContactsListUpdate()
	{
		$users_assigned = $this->dol_object->getIdContact('internal', 'SUPPORTTEC');
		$id_user_assigned = 0;

		if (isset($users_assigned[0])) {
			$id_user_assigned = $users_assigned[0];
		}

		$cur_user_assign = (int) $this->getData('fk_user_assign');
		if ($id_user_assigned !== $cur_user_assign) {
			$err = $this->updateField('fk_user_assign', $id_user_assigned);

			if (empty($err)) {
				$this->checkUserAssigned(true, $cur_user_assign);
			}
		}
	}

	public function afterCreateNote($note)
	{
		$type_author = $note->getData('type_author');
		if ($note->getData('type_author') == BimpNote::BN_AUTHOR_SOC || ($type_author == BimpNote::BN_AUTHOR_FREE && $note->getData('email'))) {
			$this->updateField('id_last_note_client', $note->id);
		}
	}

	// Actions:

	public function actionAssign($data, &$success = '')
	{
		$errors = array();
		$warnings = array();
		$success = '';

		if ($this->isLoaded($errors)) {
			$init_user_assign = (int) $this->getInitData('fk_user_assign');
			$fk_user_assign = BimpTools::getArrayValueFromPath($data, 'fk_user_assign', 0);

			$errors = $this->updateField('fk_user_assign', $fk_user_assign);

			if (BimpTools::isPostFieldSubmit('type_code')) {
				$errors = BimpTools::merge_array($errors, $this->updateField('type_code', BimpTools::getPostFieldValue('type_code', $this->getData('type_code'), 'alphanohtml')));
			}

			if (!count($errors)) {
				$this->checkUserAssigned(true, $init_user_assign);
			}
		} else {
			$ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

			if (empty($ids)) {
				$errors[] = 'Aucun ticket sélectionné';
			} else {
				$nbOk = 0;
				foreach ($ids as $id) {
					$ticket = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', $id);
					if (BimpObject::objectLoaded($ticket)) {
						$res = $ticket->setObjectAction('assign', $data, $success);

						if (!empty($res['errors'])) {
							$warnings = BimpTools::getMsgFromArray($res['errors'], 'Echec de la mise à jour du ticket ' . $ticket->getRef());
						}
					} else {
						$warnings[] = 'Le tcket #' . $id . ' n\'existe plus';
					}
				}
			}
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	public function actionNewStatus($data, &$success = '')
	{
		$errors = array();
		$warnings = array();
		$success = '';

		$new_status = BimpTools::getPostFieldValue('new_status', null, 'int');
		if (is_null($new_status)) {
			$errors[] = 'Aucun statut sélectionné';
		} else {
			$new_status = (int) $new_status;

			if ($new_status === (int) $this->getData('fk_statut')) {
				$errors[] = 'Ce ticket a déjà le statut "' . self::$status_list[$new_status]['label'] . '"';
			} else {
				$this->set('fk_statut', $new_status);

				if ($new_status === self::STATUS_CLOSED) {
					$this->set('resolution', BimpTools::getArrayValueFromPath($data, 'resolution', ''));
					$date_now = date('Y-m-d H:i:s');
					$this->set('date_close', $date_now);

					$datec = $this->getData('datec');
					if ($datec) {
						$interval = BimpTools::getDatesIntervalData($datec, $date_now);
						$this->set('resolution', $interval['full_days']);
					}
				}

				$errors = $this->update($warnings, true);

				if (!count($errors)) {
					$msg = 'Mise au statut "' . self::$status_list[$new_status]['label'] . '"';
					$success = $msg;

					if (in_array($new_status, array(self::STATUS_WAITING, self::STATUS_CANCELED, self::STATUS_TRANSFERED))) {
						$reason = BimpTools::getArrayValueFromPath($data, 'reason', '');
						if ($reason) {
							$msg .= '<br/><b>Motif : </b>' . $reason;
						}
					}

					$this->addObjectLog($msg, 'STATUS_' . $new_status);
				}
			}
		}


		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	public function actionAddContact($data, &$success)
	{
		$errors = array();

		$type = (int) BimpTools::getArrayValueFromPath($data, 'type', 0);
		if ($type == 2) {
			$id_type_contact = (int) $this->db->getValue('c_type_contact', 'rowid', 'source = \'internal\' AND element = \'ticket\' AND code = \'SUPPORTTEC\'');
			if ($id_type_contact && (int) BimpTools::getArrayValueFromPath($data, 'user_type_contact', 0) == $id_type_contact) {
				$users_assigned = $this->dol_object->getIdContact('internal', 'SUPPORTTEC');
				if (!empty($users_assigned)) {
					$errors[] = 'Ce ticket est déjà assigné à un utilisateur';
				}
			}
		}

		if (!count($errors)) {
			return parent::actionAddContact($data, $success);
		}

		return array(
			'errors'    => $errors,
			'wanrnings' => array()
		);
	}

	// Overrides :

	public function onSave(&$errors = array(), &$warnings = array())
	{
		parent::onSave($errors, $warnings);
	}

	public function validate()
	{
		$errors = parent::validate();

		if (!count($errors)) {
			global $user;

			if (BimpObject::objectLoaded($user)) {
				$this->set('fk_user_update', $user->id);
			}
		}

		return $errors;
	}

	public function create(&$warnings = array(), $force_create = false)
	{
		$errors = array();

		$notify = (int) BimpTools::getPostFieldValue('notify', 0, 'int');
		if ($notify) {
			$notify_email = BimpTools::getPostFieldValue('notify_email', '', 'alphanohtml');
			$subject = $this->getData('subject');
			$msg = $this->getData('message');

			if (!$subject) {
				$errors[] = 'Sujet obligatoire pour notification du tiers par e-mail';
			}
			if (!$msg) {
				$errors[] = 'Description obligatoire pour notification du tiers par e-mail';
			}
			if (!$notify_email) {
				$errors[] = 'Adresse e-mail absente pour notification du tiers';
			}

			if (count($errors)) {
				return $errors;
			}
		}

		$this->set('ref', $this->dol_object->getDefaultRef());

		$errors = parent::create($warnings, $force_create);

		if (!count($errors)) {
			$this->checkUserAssigned(true);

			$contacts = (int) $this->dol_object->getIdContact('external', 'SUPPORTCLI');
			if (!isset($contacts[0])) {
				$id_contact_suivi = (int) BimpTools::getPostFieldValue('id_contact_suivi', 0);
				if (!$id_contact_suivi) {
					$client = $this->getChildObject('client');
					if (BimpObject::objectLoaded($client)) {
						$id_contact_suivi = (int) $client->getData('contact_default');
					}
				}

				if ($id_contact_suivi) {
					$this->dol_object->add_contact($id_contact_suivi, 'SUPPORTCLI', 'external');
				}
			}

			if ($notify) {
				$mail_errors = array();
				$mail = new BimpMail($this, $subject, $notify_email, $this->getMailFrom(), $msg);
				if (!$mail->send($mail_errors)) {
					$warnings[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de notification à l\'adresse "' . $notify_email . '"');
				}
			}
		}

		if ($this->getData('email_msgid') == '') {
			$this->updateField('email_msgid', randomPassword('35') . '@bimpticket');
		}

		return $errors;
	}

	public function update(&$warnings = array(), $force_update = false)
	{
		$init_fk_user_assign = (int) $this->getInitData('fk_user_assign');
		$errors = parent::update($warnings, $force_update);

		if (!count($errors)) {
			if ($init_fk_user_assign !== (int) $this->getData('fk_user_assign')) {
				$this->checkUserAssigned(true, $init_fk_user_assign);
			}
		}

		return $errors;

	}

	// Méthodes statiques :

	public static function getTicketsForUser($id_user, $tms = '', $options = array(), &$errors = array())
	{
		if ((int) BimpCore::getConf('mode_eco')) {
			return array();
		}

		$data = array(
			'tms'      => date('Y-m-d H:i:s'),
			'elements' => array()
		);

		$bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
		if (!BimpObject::objectLoaded($bimp_user)) {
			return $data;
		}

		$bdb = self::getBdb();
		$bimp_user->dol_object->loadRights();
		$filters = array();

		if ($tms) {
			$filters['a.tms'] = array(
				'operator' => '>',
				'value'    => $tms
			);
		}

		if (!empty($options['excluded_tickets'])) {
			$filters['a.rowid'] = array(
				'not_in' => $options['excluded_tickets']
			);
		}

		$filters['a.fk_statut'] = array(
			'operator' => '<',
			'value'    => self::STATUS_CLOSED
		);

		$users_filters = array($id_user);

//		if (...) { // todo : check droits users pour voir / assigner nouveaux tickets.
		$users_filters = array_merge($users_filters, array(0, null));
//		}

		if ((int) BimpTools::getArrayValueFromPath($options, 'include_delegations', 1)) {
			$users_delegations = $bdb->getValues('user', 'rowid', 'delegations LIKE \'%[' . $id_user . ']%\'');

			if (!empty($users_delegations)) {
				foreach ($users_delegations as $id_user_delegation) {
					if ($id_user_delegation != $id_user) {
						$users_filters[] = $id_user_delegation;
					}
				}
			}
		}

		$filters['fk_user_assign'] = $users_filters;

		$i = 0;
		$tickets = array();

		$sql = 'SELECT DISTINCT a.rowid';
		$sql .= BimpTools::getSqlFrom('ticket');
		$sql .= BimpTools::getSqlWhere($filters);
		$sql .= BimpTools::getSqlOrderBy('datec', 'DESC', 'a');
		$sql .= BimpTools::getSqlLimit(50);

//		echo 'SQL : ' . $sql .'<br/><br/>';

		$rows = $bdb->executeS($sql, 'array');

		if (!is_array($rows)) {
			$errors[] = 'Echec requête SQL : ' . $bdb->err();
			return array();
		}

		foreach ($rows as $r) {
			$t = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', (int) $r['rowid']);
			if (!BimpObject::objectLoaded($t)) {
				continue;
			}

			$status = (int) $t->getData('fk_statut');
			$status_icon = '<span class="' . implode(' ', self::$status_list[$status]['classes']) . ' bs-popover" style="margin-right: 8px"';
			$status_icon .= BimpRender::renderPopoverData(self::$status_list[$status]['label']) . '>';
			$status_icon .= BimpRender::renderIcon(self::$status_list[$status]['icon']) . '</span>';

			$user_author = $t->getChildObject('user_create');

			$dest = '';
			if ((int) $t->getData('fk_user_assign') !== $id_user) {
				$user_assign = $t->getChildObject('user_assign');
				if (BimpObject::objectLoaded($user_assign)) {
					$dest = $user_assign->getName();
				}
			}

			$nb_msgs = 0;

			foreach ($users_filters as $id_u) {
				if ((int) $id_u) {
					$nb_msgs += $t->getNbNotesFormUser($id_u);
				}
			}

			$nb_msgs = $t->getNbNotesFormUser($id_user);


			$client = $t->getChildObject('client');

			$ticket = array(
				'id'            => $t->id,
				'ref'           => $t->getRef(),
				'sort_val'      => $t->getData('datec'),
				'affected'      => (int) ($t->getData('fk_user_assign') > 0),
				'status_icon'   => $status_icon,
				'subj'          => $t->getData('subject'),
				'src'           => $t->getData('origin_email'),
//				'txt'           => $t->displayData("message", 'default', false),
				'txt'           => trim(BimpTools::htmlToString($t->getData('message'), 1200, true)),
				'date_create'   => $t->getData('datec'),
				'url'           => DOL_URL_ROOT . '/bimpticket/index.php?fc=ticket&id=' . $t->id,
				'can_begin'     => (int) ($t->canSetAction('newStatus') && $t->isActionAllowed('newStatus') && $status < self::STATUS_IN_PROGRESS && $status >= self::STATUS_READ),
				'can_close'     => (int) ($t->canSetAction('newStatus') && $t->isActionAllowed('newStatus') && $status < self::STATUS_CLOSED && $status >= self::STATUS_IN_PROGRESS),
				'can_attribute' => (int) ($t->canSetAction('assign') && $t->isActionAllowed('assign')),
				'can_edit'      => (int) $t->can('edit'),
				'author'        => (BimpObject::objectLoaded($user_author) ? $user_author->getName() : ''),
				'dest'          => $dest,
				'nb_msgs'       => $nb_msgs,
				'client'        => (BimpObject::objectLoaded($client) ? $client->getLink() : ''),
			);

			$tickets[] = $ticket;
		}

		$data['elements'] = $tickets;

		return $data;
	}

	public function getMailToContacts()
	{
		$contacts = $return = array();
		$contacts = $this->dol_object->liste_contact(-1, 'external');
		foreach ($contacts as $contact) {
			$return[$contact['email']] = $contact['lastname'] . ' ' . $contact['firstname'] . ' (' . $contact['email'] . ')';
		}
//		echo '<pre>';print_r($contacts);
		return $return;
	}

	public function getMailFrom()
	{
		$from = '';
		$type = $this->getData('type_code');

		if ($type) {
			$from = array_search($type, static::$mail_typeTicket);
		}

		if (!$from) {
			$from = static::MAIL_TICKET_GENERAL;
		}
		return $from;
	}

	public function getObjectMail()
	{
		return 'Rép. : ' . $this->getData('subject');
	}
}

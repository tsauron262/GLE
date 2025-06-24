<?php

class Bimp_ActionComm extends BimpObject
{
	public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old

	public static $transparencies = array(
		0 => 'Disponible',
		1 => 'Occupé',
		2 => 'Occupé (événements refusés)'
	);
	public static $progressions = array(
		-1  => 'Non applicable',
		0   => 'A faire',
		50  => 'En cours',
		100 => 'Terminé'
	);

	// Droits users:

	public function canView()
	{
		//ne fonctionne pas

		return $this->getRight('read');
	}

	public function canDelete()
	{
		return $this->getRight('delete');
	}

	public function canEdit()
	{
		return $this->getRight('create');
	}

	public function canSetAction($action)
	{
		global $user;
		switch ($action) {
			case 'done':
			case 'clone':
				if ($this->isLoaded() && $this->isUserAssigned()) {
					return 1;
				}

				return $this->canEdit();
		}
		return parent::canSetAction($action);
	}

	// Getters booléens:

	public function isCreatable($force_create = false, &$errors = array())
	{
		return $this->isEditable();
	}

	public function isEditable($force_edit = false, &$errors = array())
	{
//        return $this->getRight('create');// pas de droits user ici
		return 1;
	}

	public function isDeletable($force_delete = false, &$errors = array())
	{
//        return $this->getRight('delete');// pas de droits user ici
		return 1;
	}

	public function isUserAssigned()
	{
		global $user;

		if ($user->id == $this->getData('fk_user_action')) {
			return 1;
		}

		foreach ($this->dol_object->userassigned as $userassigned) {
			if ($user->id == $userassigned['id']) {
				return 1;
			}
		}
		return 0;
	}

	public function isActionAllowed($action, &$errors = array())
	{
		switch ($action) {
			case 'done':
				if ($this->isLoaded()) {
					$percent = (int) $this->getData('percent');
					if ($percent < 0 || $percent >= 100) {
						$errors[] = 'Cet événement n\'est pas en attente d\'être terminé';
						return 0;
					}
				}
				return 1;
			case 'clone':
				return 1;
		}

		return parent::isActionAllowed($action, $errors);
	}

	// Getters array:

	public function getTypesArray($include_empty = false)
	{
		$cache_key = 'action_comm_types_values_array';

		if (!isset(self::$cache[$cache_key])) {
			$rows = $this->db->getRows('c_actioncomm', '1', null, 'array', array('id', 'icon', 'libelle'), 'position', 'asc');

			if (is_array($rows)) {
				foreach ($rows as $r) {
					self::$cache[$cache_key][(int) $r['id']] = array('label' => $r['libelle'], 'icon' => $r['icon']);
				}
			}
		}

		return self::getCacheArray($cache_key, $include_empty);
	}

	public function getUsersAssigned()
	{
		global $user;
		$users = array();
		foreach ($this->dol_object->userassigned as $userassigned) {
			$users[] = $userassigned['id'];
		}
		if (!count($users)) {
			$users = BimpTools::getPostFieldValue('param_values/fields/users_assigned', array(), 'array');
			if (!count($users)) {
				$users[] = $user->id;
			}
		}
		return $users;
	}

	public function getContactsAssigned()
	{
		$socpeople = array();
		foreach ($this->dol_object->socpeopleassigned as $socpeopleassigned) {
			$socpeople[] = $socpeopleassigned['id'];
		}
		return $socpeople;
	}

	// Getters params:

	public function getRight($code)
	{
		global $user;

		if ($user->rights->agenda->allactions->$code) {
			return 1;
		}

		$usersAssigned = BimpTools::getPostFieldValue('users_assigned', $this->getUsersAssigned(), 'array');

		if (!$this->isLoaded()) {
			$idUserCreate = $user->id;
			if (count($usersAssigned) != 1 || !in_array($user->id, $usersAssigned)) //n'est pas l'utilisateur assignée, et n'a pas le droit de créer des action pour d'autres.
			{
				return 0;
			}
		} else {
			$idUserCreate = $this->getData('fk_user_author');
		}

		if ((($idUserCreate == $user->id) || (!$this->isLoaded() && count($usersAssigned) && in_array($user->id, $usersAssigned))) &&
			$user->rights->agenda->myactions->$code) {
			return 1;
		}

		return 0;
	}

	public function getRefProperty()
	{
		return '';
	}

	public function getStatusProperty()
	{
		return '';
	}

	public function getCustomFilterValueLabel($field_name, $value)
	{
		switch ($field_name) {
			case 'propal':
				if ((int) $value) {
					return $this->db->getValue('propal', 'ref', 'rowid = ' . (int) $value);
				}
				break;
			case 'commande':
				if ((int) $value) {
					return $this->db->getValue('commande', 'ref', 'rowid = ' . (int) $value);
				}
				break;
			case 'facture':
				if ((int) $value) {
					return $this->db->getValue('facture', 'ref', 'rowid = ' . (int) $value);
				}
				break;
		}
		return parent::getCustomFilterValueLabel($field_name, $value);
	}

	public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
	{
		switch ($field_name) {
			case 'propal':
			case 'commande':
			case 'facture':
				$element_type = '';
				switch ($field_name) {
					case 'propal':
						$element_type = 'propal';
						break;
					case 'commande':
						$element_type = 'order';
						break;
					case 'facture':
						$element_type = 'facture';
						break;
				}
				$filters[$main_alias . '.elementtype'] = $element_type;
				$filters[$main_alias . '.fk_element'] = array(
					($excluded ? 'not_' : '') . 'in' => $values
				);
				break;
		}
		parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors);
	}

	public function getListFilters($list = 'default')
	{
		global $user;
		$filters = array();

		switch ($list) {
			case 'ficheInter':
				$filters[] = array('name' => 'fk_element', 'filter' => $_REQUEST['id']);
				$filters[] = array('name' => 'elementtype', 'filter' => 'fichinter');
				break;
		}

		return $filters;
	}

	public function getFilesDir()
	{
		global $conf;
		if ($this->isLoaded()) {
			return $conf->agenda->multidir_output[$this->dol_object->entity] . '/' . dol_sanitizeFileName($this->dol_object->ref);
		} else {
			echo 'NOT LOADED';
			exit;
		}
	}

	public function getFileUrl($file_name, $page = 'document')
	{
		if (!$file_name) {
			return '';
		}

		if (!$this->isLoaded()) {
			return '';
		}

		$file = $this->id . '/' . $file_name;

		return DOL_URL_ROOT . '/' . $page . '.php?modulepart=actions&entity=' . $this->dol_object->entity . '&file=' . urlencode($file);
	}

	public function getActionsButtons()
	{
		$buttons = array();

		if ($this->isActionAllowed('done') && $this->canSetAction('done')) {
			$buttons[] = array(
				'label'   => 'Terminé',
				'icon'    => 'fas_check',
				'onclick' => $this->getJsActionOnclick('done', array(), array(
					'confirm_msg' => 'Veuillez confirmer'
				))
			);
		}
		if ($this->isActionAllowed('clone') && $this->canSetAction('clone')) {
			$buttons[] = array(
				'label'   => 'Cloner',
				'icon'    => 'fas_copy',
				'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
					'form_name' => 'duplicate'
				))
			);
		}

		return $buttons;
	}

	public function getListsBulkActions($list_type = 'default')
	{
		$actions = array();

		// La vérif des droits se fera individuellement pour chaque événement

		$actions[] = array(
			'label'   => 'Supprimer les événements sélectionnés',
			'icon'    => 'fas_trash-alt',
			'onclick' => 'deleteSelectedObjects(\'list_id\', $(this))'
		);

		$actions[] = array(
			'label'   => 'Marquer terminés',
			'icon'    => 'fas_check',
			'onclick' => $this->getJsBulkActionOnclick('done', array(), array(
				'confirm_msg'   => 'Veuillez confirmer',
				'single_action' => true
			))
		);

		return $actions;
	}

	// Getters données:

	public function getLinkedElement()
	{
		if ((int) $this->getData('fk_element') && $this->getData('elementtype')) {
			return BimpTools::getInstanceByElementType($this->getData('elementtype'), (int) $this->getData('fk_element'));
		}

		return null;
	}

	public function getLinkedUrl($externe = false)
	{
		$html = '';
		$instance = $this->getLinkedElement();
		if (is_null($instance)) {
			if ($this->getData('elementtype') && (int) $this->getData('fk_element')) {
				$html .= '<span class="danger">Type "' . $this->getData('elementtype') . '" inconnu</span>';
			}
		} elseif (BimpObject::objectLoaded($instance)) {
			if ($externe) {
				$html .= $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['SERVER_NAME'];
			}
			$html .= BimpObject::getInstanceUrl($instance);
		} else {
			$html .= BimpTools::ucfirst(BimpObject::getInstanceLabel($instance) . ' #' . $this->getData('fk_element'));
		}

		return $html;
	}

	// Affichages:

	public function displayExternalUsers()
	{
		$return = '';
		if ($this->isLoaded()) {

			$ln = $this->db->getRow('synopsiscaldav_event', '`fk_object` = ' . $this->id, array('participentExt'));
			if ($ln) {
				$tab = explode(',', $ln->participentExt);
				foreach ($tab as $usersExt) {
					$tmp = explode('|', $usersExt);
					$return .= $tmp[0];
					if (isset($tmp[1])) {
						if ($tmp[1] == 'NEEDS-ACTION') {
							$return .= ' ' . BimpRender::renderIcon('fas_info-circle', 'warning');
						} elseif ($tmp[1] == 'ACCEPTED') {
							$return .= ' ' . BimpRender::renderIcon('fas_check-circle', 'success');
						} elseif ($tmp[1] == 'NEEDS-ACTION') {
							$return .= ' ' . BimpRender::renderIcon('fa_times-circle', 'danger');
						}
					}
					$return .= '<br/>';
				}
			}
		}
		return $return;
	}

	public function displayElement()
	{
		$html = '';
		$instance = $this->getLinkedElement();

		if (is_null($instance)) {
			if ($this->getData('elementtype') && (int) $this->getData('fk_element')) {
				$html .= '<span class="danger">Type "' . $this->getData('elementtype') . '" inconnu</span>';
			}
		} elseif (BimpObject::objectLoaded($instance)) {
			$html .= BimpObject::getInstanceNomUrl($instance);
		} else {
			$html .= BimpTools::ucfirst(BimpObject::getInstanceLabel($instance) . ' #' . $this->getData('fk_element'));
		}

		return $html;
	}

	public function displayState($badge = false)
	{
		if ($this->isLoaded()) {
			$percent = (float) $this->getData('percent');

			if ($percent < 0) {
				$date_begin = $this->getData('datep');
				$date_end = $this->getData('datep2');
				$date_now = date('Y-m-d H:i:s');

				if ($date_now < $date_begin) {
					return '<span class="' . ($badge ? 'badge badge-' : '') . 'warning">' . BimpRender::renderIcon('fas_exclamation', 'iconLeft') . 'A venir</span>';
				} elseif ($date_now < $date_end) {
					return '<span class="' . ($badge ? 'badge badge-' : '') . 'info">' . BimpRender::renderIcon('fas_cogs', 'iconLeft') . 'En cours</span>';
				} else {
					return '<span class="' . ($badge ? 'badge badge-' : '') . 'success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Terminé</span>';
				}
			} else {
				if (!$percent) {
					return '<span class="' . ($badge ? 'badge badge-' : '') . 'warning">' . BimpRender::renderIcon('fas_exclamation', 'iconLeft') . 'A faire</span>';
				} elseif ($percent < 100) {
					return '<span class="' . ($badge ? 'badge badge-' : '') . 'info">' . BimpRender::renderIcon('fas_cogs', 'iconLeft') . 'En cours (' . $percent . ' %)</span>';
				} else {
					return '<span class="' . ($badge ? 'badge badge-' : '') . 'success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Terminé</span>';
				}
			}
		}

		return '';
	}

	public function displayDates($with_icon = false)
	{
		$html = '';
		$from = $this->getData('datep');
		$to = $this->getData('datep2');
		$fullday = (int) $this->getData('fulldayevent');
		$multiple_days = false;

		if ($from) {
			$dt_from = new DateTime($from);

			if (!$to) {
				$html .= 'Le ' . $dt_from->format('d/m/Y');

				if ($fullday) {
					$html .= ' (Journée)';
				} else {
					$html .= ' à ' . $dt_from->format('H:i');
				}
			} else {
				$dt_to = new DateTime($to);

				if ($dt_from->format('Y-m-d') == $dt_to->format('Y-m-d')) {
					$html .= 'Le ' . $dt_from->format('d/m/Y');

					if ($fullday) {
						$html .= ' (Journée)';
					} else {
						$html .= ' de ' . $dt_from->format('H:i') . ' à ' . $dt_to->format('H:i');
					}
				} else {
					$multiple_days = true;
					$html .= 'Du ' . $dt_from->format('d/m/Y') . ($fullday ? ' ' . $dt_from->format('H:i') : '');
					$html .= ' au ' . $dt_to->format('d/m/Y') . ($fullday ? ' ' . $dt_to->format('H:i') : '');

					if ($fullday) {
						$html .= ' (Journées entières)';
					}
				}
			}
		}

		if ($with_icon) {
			$icon = 'fas_' . ($multiple_days ? 'calendar-week' : ($fullday ? 'calendar-day' : 'clock'));
			$html = BimpRender::renderIcon($icon, 'iconLeft') . $html;
		}

		return $html;
	}

	public function displayUsersAssigned()
	{
		$html = '';

		if ($this->isLoaded()) {
			$main_user = $this->getChildObject('user_action');
			$users = $this->dol_object->userassigned;

			if (BimpObject::objectLoaded($main_user)) {
				if (!empty($users)) {
					foreach ($users as $key => $u) {
						if ($u['id'] == $main_user->id) {
							unset($users[$key]);
						}
					}
				}

				$html .= (!empty($users) ? 'Principal : ' : '') . $main_user->getLink();

				if (!empty($users)) {
					$html .= '<br/><br/>' . 'Autres utilisateurs:<br/>';
				}
			}

			if (!empty($users)) {
				$html .= '<ul>';
				foreach ($users as $user) {
					$user_instance = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $user['id']);
					$html .= '<li>' . $user_instance->getLink() . '</li>';
				}
				$html .= '</ul>';
			}
		}

		return $html;
	}

	// Rendus HTML:

	public function renderHeaderStatusExtra()
	{
		return $this->displayState(true);
	}

	public function renderHeaderExtraLeft()
	{
		$html = '';

		$html .= '<div>';
		$html .= '<psan class="info" style="font-size: 14px">' . $this->displayDates(true) . '</span>';
		$html .= '</div>';

		$loc = $this->getData('location');
		if ($loc) {
			$html .= '<div>';
			$html .= BimpRender::renderIcon('fas_map-marker-alt', 'iconLeft') . $loc;
			$html .= '</div>';
		}

		return $html;
	}

	public function renderDateInput($field_name)
	{
		$date = $this->getData($field_name);

		if (BimpTools::isPostFieldSubmit($field_name)) {
			$date = BimpTools::getPostFieldValue($field_name, $date, 'date');
		}

		$input_type = 'datetime';
		if ((int) $this->getData('fulldayevent')) {
			$input_type = 'date';
			$date = date('Y-m-d', strtotime($date));
		} else {
			if ($this->isLoaded()) {
				$init_date = $this->getInitData($field_name);
				$date = date('Y-m-d', strtotime($date)) . ' ' . date('H:i:s', strtotime($init_date));
			} else {
				$date = date('Y-m-d H:i:s', $date ? strtotime($date) : time());
			}
		}

		return BimpInput::renderInput($input_type, $field_name, $date);
	}

	public function renderHeaderButtons()
	{
		$html = '';
		$ret = '';
		$div = '<div style="margin: 10px; text-align: center">';
		if ($this->isLoaded() && $this->canDelete()) {
			$html .= '<span class="btn btn-danger" onclick="' . $this->getJsDeleteOnClick(array(
					'success_callback' => "function(){bimpModal.hide();$('#calendar').weekCalendar('refresh');}"
				)) . '">';
			$html .= BimpRender::renderIcon('fas_trash-alt', 'iconLeft') . 'Supprimer cet événement';
			$html .= '</span>';
		}
		if ($this->isActionAllowed('clone') && $this->canSetAction('clone')) {
			$html .= '<span class="btn btn-primary" onclick="' .
				$this->getJsActionOnclick('duplicate', array(), array(
						'form_name' => 'duplicate',
						'success_callback' => "function(){bimpModal.hide();$('#calendar').weekCalendar('refresh');}"
					)
				) . '" >';
			$html .= BimpRender::renderIcon('copy', 'iconLeft') . 'Dupliquer cet événement';
			$html .= '</span>';
		}

		if ($html != '')	$ret = $div . $html . '</div>';

		return $ret;
	}

	public function renderDolTabs()
	{
		global $langs;
		require_once DOL_DOCUMENT_ROOT . '/core/lib/agenda.lib.php';

		$paramnoaction = '';
		$head = calendars_prepare_head($paramnoaction);

		dol_fiche_head($head, "list", $langs->trans('Agenda'), 0, 'action');
	}

	public function renderUserEventsList($type)
	{
		$list = null;

		$ac = BimpObject::getInstance('bimpcore', 'Bimp_ActionComm');
		$list = new BC_ListTable($ac, 'user');
		$date_now = date('Y-m-d');

		switch ($type) {
			case 'todo':
				$list->params['title'] = 'Mes événements à faire ou à venir';
				$list->addFieldFilterValue('or_percent', array(
					'or' => array(
						'and_no_percent' => array(
							'and_fields' => array(
								'a.percent' => array(
									'operator' => '<',
									'value'    => 0
								),
								'or_date'   => array(
									'or' => array(
										'a.datep'  => array(
											'operator' => '>=',
											'value'    => $date_now . ' 00:00:00'
										),
										'a.datep2' => array(
											'operator' => '>=',
											'value'    => $date_now . ' 00:00:00'
										)
									)
								)
							)
						),
						'a.percent'      => array(
							'and' => array(
								array(
									'operator' => '>=',
									'value'    => 0
								),
								array(
									'operator' => '<',
									'value'    => 100
								)
							)

						)
					)
				));
				break;

			case 'done':
				$list->params['title'] = 'Mes événements  terminés';
				$list->addFieldFilterValue('or_done', array(
					'or' => array(
						'and_no_percent' => array(
							'and_fields' => array(
								'a.percent' => array(
									'operator' => '<',
									'value'    => 0
								),
								'a.datep2'  => array(
									'operator' => '<',
									'value'    => date('Y-m-d H:i:s')
								)
							)
						),
						'a.percent'      => array(
							'operator' => '>=',
							'value'    => 100
						)
					)
				));
				break;

			case 'all':
				$list->params['title'] = 'Tous mes événements';
				break;

			default:
				unset($list);
				return BimpRender::renderAlerts('Type de liste invalide : ' . $type, 'danger');
		}

		global $user;
		$list->addIdentifierSuffix($type);
		$list->addFieldFilterValue('or_user', array(
			'or' => array(
				'a.fk_user_action'                                                                                                                                                                    => $user->id,
				'(SELECT COUNT(acr.rowid) FROM ' . MAIN_DB_PREFIX . 'actioncomm_resources acr WHERE acr.fk_actioncomm = a.id AND acr.fk_element = ' . $user->id . ' AND acr.element_type = \'user\')' => array(
					'operator' => '>',
					'value'    => 0
				)
			)
		));
		return $list->renderHtml();
	}

	// Actions:

	public function actionDone($data, &$success = '')
	{
		$errors = array();
		$warnings = array();

		if ($this->isLoaded()) {
			$this->set('percent', 100);
			$errors = $this->update($warnings, true);
			$success = 'Événement terminé';
		} else {
			$ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

			if (empty($ids)) {
				$errors[] = 'Aucun événement sélectionné';
			} else {
				$nb_ok = 0;
				foreach ($ids as $id) {
					$ac_errors = array();

					$event = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm', (int) $id);
					if (BimpObject::objectLoaded($event)) {
						if ($event->canSetAction('done')) {
							if ($event->isActionAllowed('done', $ac_errors)) {
								$event->set('percent', 100);
								$ac_errors = $event->update($warnings, true);
							}
						} else {
							$ac_errors[] = 'Vous n\'avez pas la permission pour terminer cet événement';
						}
					} else {
						$ac_errors[] = 'L\'événement #' . $id . ' n\'existe plus';
					}

					if (count($ac_errors)) {
						$warnings[] = BimpTools::getMsgFromArray($ac_errors, 'Événement #' . $id);
					} else {
						$nb_ok++;
					}
				}

				if ($nb_ok) {
					$success = $nb_ok . ' événement(s) terminé(s)';
				}
			}
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	public function actionDuplicate($data, &$success = '')	{
		global $user;

		$errors = array();
		$warnings = array();

		$ac = BimpObject::getDolObjectInstance($this->getData('id'), 'actioncomm');
		$idClone = $ac->createFromClone($user, $this->getData('fk_soc'));

		$clone = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ActionComm', (int) $idClone);
		$clone->set('label', $data['label']);
		$clone->set('datep', $data['datep']);
		$clone->set('datep2', $data['datep2']);
		$clone->set('percent', -1);
		$clone->set('fk_user_action', $data['users_assigned'][0]);

		$transparency = (int) $this->getData('transparency');

		$clone->dol_object->userassigned = array();
		foreach ($data['users_assigned'] as $id_user) {
			if (!isset($clone->dol_object->userassigned[$id_user])) {
				$clone->dol_object->userassigned[$id_user] = array(
					'id'           => $id_user,
					'transparency' => $transparency
				);
			}
		}

		$clone->update($warnings);
		$success = 'Evenement cloné avec succes ' . $clone->getLink(array('target' => 'blank'));

		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

	// Overrides:

	public function validatePost()
	{

		$errors = parent::validatePost();

		if ($this->canEdit()) {
			$this->dol_object->userassigned = array();
			$users = BimpTools::getPostFieldValue('users_assigned', array(), 'array');
			$transparency = (int) $this->getData('transparency');

			if (!empty($users)) {
				foreach ($users as $id_user) {
					if (!isset($this->dol_object->userassigned[$id_user])) {
						$this->dol_object->userassigned[$id_user] = array(
							'id'           => $id_user,
							'transparency' => $transparency
						);
					}
				}
			}

			$usergroups = BimpTools::getPostFieldValue('usergroups_assigned', array(), 'array');
			if (!empty($usergroups)) {
				foreach ($usergroups as $id_group) {
					$users = BimpCache::getGroupUsersList($id_group);

					if (!empty($users)) {
						foreach ($users as $id_user) {
							if (!isset($this->dol_object->userassigned[$id_user])) {
								$this->dol_object->userassigned[$id_user] = array(
									'id'           => $id_user,
									'transparency' => $transparency
								);
							}
						}
					}
				}
			}

			if (empty($this->dol_object->userassigned)) {
				$this->set('fk_user_action', 0);
			} else {
				foreach ($this->dol_object->userassigned as $id_user => $data) {
					$this->set('fk_user_action', $id_user);
					break;
				}
			}

			if (BimpTools::isPostFieldSubmit('contacts_assigned')) {
				$contacts = BimpTools::getPostFieldValue('contacts_assigned', array(), 'array');

				$this->dol_object->socpeopleassigned = array();

				if (empty($contacts)) {
					$this->set('fk_contact', 0);
				} else {
					$this->set('fk_contact', (int) $contacts[0]);
					foreach ($contacts as $id_contact) {
						$this->dol_object->socpeopleassigned[$id_contact] = array(
							'id' => $id_contact
						);
					}
				}
			}
		}

		return $errors;
	}

	public function validate()
	{
		global $conf;
		$errors = parent::validate();

		if ((int) $this->getData('fulldayevent')) {
			$datep = $this->getData('datep');
			if ($datep) {
				$this->set('datep', date('Y-m-d', strtotime($datep)) . ' 00:00:00');
			}

			$datef = $this->getData('datep2');
			if ($datef) {
				$this->set('datep2', date('Y-m-d', strtotime($datef)) . ' 23:59:59 ');
			}
		}

		if ((int) $this->getData('percent') == 100 && !$this->getData('datep2')) {
			$errors[] = 'Date de fin obligatoire';
		}

		if (empty($conf->global->AGENDA_USE_EVENT_TYPE) && !$this->getData('label')) {
			$errors[] = 'Libellé obligatoire';
		}

		if (!(int) $this->getData('fk_user_action')) {
			$errors[] = 'Aucun utilisateur assigné à cet événement';
		}

		if ((int) $this->getData('fk_action')) {
			$this->dol_object->type_code = $this->db->getValue('c_actioncomm', 'code', 'id = ' . (int) $this->getData('fk_action'));

			if (!$this->dol_object->type_code) {
				$errors[] = 'Type invalide';
			}
		}
		return $errors;
	}

	public function onSave(&$errors = [], &$warnings = [])
	{
		if ($this->isLoaded() && BimpTools::isPostFieldSubmit('actioncomm_categories')) {
			$categories = BimpTools::getPostFieldValue('actioncomm_categories', array(), 'array');
			$this->dol_object->setCategories($categories);
		}

		parent::onSave($errors, $warnings);
	}

	public function update(&$warnings = array(), $force_update = false)
	{
		$fk_action = BimpTools::getPostFieldValue('fk_action', 0, 'int');
		if ($fk_action) {
			$code = $this->db->getValue('c_actioncomm', 'code', 'id = ' . (int) $fk_action);
			if ($code) {
				$this->set('code', $code);
			}
		}

		return parent::update($warnings, $force_update);
	}

	public function create(&$warnings = [], $force_create = false)
	{
		$errors = array();

		if (in_array('actioncomm_add_reminder', array(1, 'on'))) { // A implémenter dans le form "add"
			$offsetvalue = BimpTools::getPostFieldValue('reminder_offset_value', 0, 'float');
			$offsetunit = BimpTools::getPostFieldValue('reminder_offset_unit', '', 'alphanohtml');
			$remindertype = BimpTools::getPostFieldValue('reminder_type', '', 'alphanohtml');
			$modelmail = BimpTools::getPostFieldValue('reminder_model_email', '', 'alphanohtml');

			if (!$offsetvalue || !$offsetunit || !$remindertype || !$modelmail) {
				$errors[] = 'Paramètres invalide pour l\'envoi du rappel';
			}
		}

		if (!count($errors)) {
			$errors = parent::create($warnings, $force_create);

			if (!count($errors)) {
				// Create reminders
				if ($offsetvalue && $offsetunit && $remindertype && $modelmail) {
					$actionCommReminder = new ActionCommReminder($this->db->db);

					$dateremind = dol_time_plus_duree($this->getData('datep'), -$offsetvalue, $offsetunit);

					$actionCommReminder->dateremind = $dateremind;
					$actionCommReminder->typeremind = $remindertype;
					$actionCommReminder->offsetunit = $offsetunit;
					$actionCommReminder->offsetvalue = $offsetvalue;
					$actionCommReminder->status = $actionCommReminder::STATUS_TODO;
					$actionCommReminder->fk_actioncomm = $this->id;
					if ($remindertype == 'email') {
						$actionCommReminder->fk_email_template = $modelmail;
					}

					global $user;
					foreach ($this->dol_object->userassigned as $userassigned) {
						$actionCommReminder->fk_user = $userassigned['id'];

						if ($actionCommReminder->create($user) <= 0) {
							$user_assigned = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $userassigned['id']);
							$warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($actionCommReminder), 'Echec de la création du rappel pour l\'utilisateur ' . (BimpObject::objectLoaded($user_assigned) ? $user_assigned->getName() : '#' . $userassigned['id']));
						}
					}
				}
			}
		}

		return $errors;
	}

	// Méthodes statiques :

	public static function getActionCommEventsForUser($id_user, $tms, $options = array(), &$errors = array())
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

		$date_now = date('Y-m-d');

		$joins = array(
			'ac_type' => array(
				'table' => 'c_actioncomm',
				'on'    => 'ac_type.id = a.fk_action'
			)
		);

		$filters = array();

		if ($tms) {
			$filters['a.tms'] = array(
				'operator' => '>',
				'value'    => $tms
			);
		}

		$filters['ac_type.user_notif'] = 1;

		$filters['or_user'] = array(
			'or' => array(
				'a.fk_user_action'                                                                                                                                                                   => $id_user,
				'(SELECT COUNT(acr.rowid) FROM ' . MAIN_DB_PREFIX . 'actioncomm_resources acr WHERE acr.fk_actioncomm = a.id AND acr.fk_element = ' . $id_user . ' AND acr.element_type = \'user\')' => array(
					'operator' => '>',
					'value'    => 0
				)
			)
		);

		$filters['or_todo'] = array(
			'or' => array(
				'and_no_percent' => array(
					'and_fields' => array(
						'a.percent' => array(
							'operator' => '<',
							'value'    => 0
						),
						'or_date'   => array(
							'or' => array(
								'a.datep'  => array(
									'operator' => '>=',
									'value'    => $date_now . ' 00:00:00'
								),
								'a.datep2' => array(
									'operator' => '>=',
									'value'    => $date_now . ' 00:00:00'
								)
							)
						)
					)
				),
				'a.percent'      => array(
					'and' => array(
						array(
							'operator' => '>=',
							'value'    => 0
						),
						array(
							'operator' => '<',
							'value'    => 100
						)
					)

				)
			)
		);

		if (!empty($options['excluded_events'])) {
			$filters['a.id'] = array(
				'not_in' => $options['excluded_events']
			);
		}

		$datetime_now = date('Y-m-d H:i:s');
		foreach (BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_ActionComm', $filters, 'a.datep', 'asc', $joins, 30) as $ac) {
			$tiers_str = '';
			$tiers = $ac->getChildObject('societe');

			if (BimpObject::objectLoaded($tiers)) {
				if ($tiers->isClient()) {
					$tiers = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $tiers->id);
					$tiers_str = '<b>Client : </b>' . $tiers->getLink();
				} else {
					$tiers_str = '<b>Tiers : </b>' . $tiers->getLink();
				}
			}

			$contact = $ac->getChildObject('contact');

			$today = 0;
			$bg_type = '';
			$icon = '';

			$dt_start = new DateTime($ac->getData('datep'));
			if ($dt_start->format('Y-m-d H:i:s') < $datetime_now) {
				$today = 1;
				$icon = 'exclamation-circle';
				$bg_type = 'danger';
			} elseif ($dt_start->format('Y-m-d') == $date_now) {
				$today = 1;
				$icon = 'exclamation';
				$bg_type = 'warning';
			}

			$data['elements'][] = array(
				'id'        => $ac->id,
				'url'       => $ac->getUrl(),
				'icon'      => $icon,
				'label'     => $ac->getData('label'),
				'type'      => $ac->displayDataDefault('fk_action'),
				'date_str'  => $ac->displayDates(true),
				'code'      => $ac->getData('code'),
				'tiers'     => $tiers_str,
				'contact'   => (BimpObject::objectLoaded($contact) ? $contact->getLink() : ''),
				'obj'       => $ac->displayElement(),
				'bg_type'   => $bg_type,
				'today'     => $today,
				'state'     => $ac->displayState(true),
				'lieu'      => $ac->getData('location'),
				'desc'      => $ac->getData('note'),
				'close_btn' => (int) ($ac->isActionAllowed('done') && $ac->canSetAction('done')),
				'can_edit'  => $ac->can('edit')
			);
		}

//		if ((int) BimpTools::getArrayValueFromPath($options, 'include_delegations', 1)) {
//			$bdb = self::getBdb();
//			$users_delegations = $bdb->getValues('user', 'rowid', 'delegations LIKE \'%[' . $id_user . ']%\'');

//			if (!empty($users_delegations)) {
//				$events_ids = array();
//
//				foreach ($data['elements'] as $event) {
//					$events_ids[] = $event['id'];
//				}
//
//				foreach ($users_delegations as $id_user_delegation) {
//					$user_delegation = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user_delegation);
//					$user_name = (BimpObject::objectLoaded($user_delegation) ? $user_delegation->getName() : 'Utilisateur #' . $id_user_delegation);
//
//					$user_events = self::getActionCommEventsForUser($id_user_delegation, $tms, array(
//						'excluded_events'     => $events_ids,
//						'include_delegations' => 0
//					), $errors);
//
//					if (!empty($user_events)) {
//						foreach ($user_events['elements'] as $idx => $event) {
//							$events_ids[] = $event['id'];
//							$user_events['elements'][$idx]['dest'] = $user_name;
//						}
//
//						$data['elements'] = BimpTools::merge_array($data['elements'], $user_events['elements']);
//					}
//				}
//			}
//		}

		return $data;
	}
}

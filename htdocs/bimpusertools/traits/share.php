<?php

trait share
{

	public $ownerTypes = array(
		1 => 'Utilisateur',
		2 => 'Groupe'
	);

	public function canEdit()
	{
		global $user;

		if ($this->isOwner($user->id)) {
			return 1;
		}

		if ($this->isSharedToUser($user->id, true)) {
			return 1;
		}

		return 0;
	}

	public function canDelete()
	{
		global $user;

		if ($this->isOwner($user->id)) {
			return 1;
		}

		return 0;
	}

	public function isUserOwner($id_user = null)
	{
		if (is_null($id_user)) {
			global $user;
			$id_user = $user->id;
		}

		if (!(int) $id_user) {
			return 0;
		}

		switch ($this->getData('onwer_type')) {
			case 1:
				return (int) ($id_user === (int) $this->getData('id_owner'));

			case 2:
				$userGroups = Bimp_UserGroup::getUserUserGroupsList($id_user);
				return (int) (in_array((int) $this->getData('id_owner'), $userGroups));
		}

		return 0;
	}

	public function isSharedToUser($id_user = null, $can_edit_only = false)
	{
		if (!$this->isLoaded()) {
			return 0;
		}

		if (is_null($id_user)) {
			global $user;
			$id_user = $user->id;
		}

		if (!(int) $id_user) {
			return 0;
		}

		$filters = array(
			'obj_module' => $this->module,
			'obj_name'   => $this->obj_name,
			'id_obj'     => $this->id,
			'id_user'    => $id_user
		);

		if ($can_edit_only) {
			$filters['can_edit'] = 1;
		}

		if ((int) $this->db->getValue('bimp_user_share', 'id', BimpTools::getSqlWhere($filters, '', ''))) {
			return 1;
		}

		$userGroups = Bimp_UserGroup::getUserUserGroupsList($id_user);

		if (!empty($userGroups)) {
			$filters = array(
				'obj_module' => $this->module,
				'obj_name'   => $this->obj_name,
				'id_obj'     => $this->id,
				'id_group'   => $userGroups
			);

			if ($can_edit_only) {
				$filters['can_edit'] = 1;
			}

			if ((int) $this->db->getValue('bimp_usergroup_share', 'id', BimpTools::getSqlWhere($filters, '', ''))) {
				return 1;
			}
		}

		return 0;
	}

	public function getUserSharedObjectsIds($id_user = null)
	{
		if (is_null($id_user)) {
			global $user;
			$id_user = $user->id;
		}

		if (!(int) $id_user) {
			return array();
		}

		$objects = array();
		$primary = $this->getPrimary();
		$table = $this->getTable();

		$user_groups = implode(',', Bimp_UserGroup::getUserUserGroupsList($id_user));

		$sql = 'SELECT DISTINCT a.' . $primary . ' as id_obj, DISTINCT us.id_obj as id_obj_user, ugs.id_obj as id_obj_group';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $table . ' a';
		$sql .= ', ' . MAIN_DB_PREFIX . 'bimp_user_share us';
		$sql .= ', ' . MAIN_DB_PREFIX . 'bimp_usergroup_share ugs';
		$sql .= ' WHERE ';
		$sql .= '((a.owner_type = 1 AND id_owner = ' . $id_user . ') OR (a.owner_type = 2 AND id_owner IN (' . $user_groups . ')))';
		$sql .= ' OR (us.obj_module = \'' . $this->module . '\' AND us.obj_name = \'' . $this->object_name . '\' AND us.id_user = ' . $id_user . ')';
		$sql .= ' OR (ugs.obj_module = \'' . $this->module . '\' AND ugs.obj_name = \'' . $this->object_name . '\' AND ugs.id_group IN ' . $user_groups . ')';

		$rows = $this->db->executeS($sql, 'array');

		if (is_array($rows)) {
			foreach ($rows as $r) {
				if ((int) $r['id_obj'] && !in_array((int) $r['id_obj'], $objects)) {
					$objects[] = (int) $r['id_obj'];
					continue;
				}
				if ((int) $r['id_obj_user'] && !in_array((int) $r['id_obj_user'], $objects)) {
					$objects[] = (int) $r['id_obj_user'];
					continue;
				}
				if ((int) $r['id_obj_group'] && !in_array((int) $r['id_obj_group'], $objects)) {
					$objects[] = (int) $r['id_obj_group'];
				}
			}
		}

		return $objects;
	}

	public function renderSharedUsers()
	{
		$html = '';

		if ($this->isLoaded()) {
			$filters = array(
				'obj_module' => $this->module,
				'obj_name'   => $this->object_name,
				'id_obj'     => $this->id
			);

			$rows = $this->db->getRows('bimp_user_share', BimpTools::getSqlWhere($filters, '', ''), null, 'array', array('id_user'));

			if (is_array($rows) && !empty($rows)) {
				$html .= '<ul>';
				foreach ($rows as $r) {
					$user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $r['id_user']);
					if (BimpObject::objectLoaded($user)) {
						$html .= '<li>' . $user->getLink() . '</li>';
					}
				}
				$html .= '</ul>';
			}
		}

		return $html;
	}

	public function renderSharedUserGroups() {
		$html = '';

		if ($this->isLoaded()) {
			$filters = array(
				'obj_module' => $this->module,
				'obj_name'   => $this->object_name,
				'id_obj'     => $this->id
			);

			$rows = $this->db->getRows('bimp_usergroup_share', BimpTools::getSqlWhere($filters, '', ''), null, 'array', array('id_group'));

			if (is_array($rows) && !empty($rows)) {
				$html .= '<ul>';
				foreach ($rows as $r) {
					$group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $r['id_group']);
					if (BimpObject::objectLoaded($group)) {
						$html .= '<li>' . $group->getLink() . '</li>';
					}
				}
				$html .= '</ul>';
			}
		}

		return $html;
	}
}

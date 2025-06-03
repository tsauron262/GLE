<?php

trait share
{

	public $ownerTypes = array(
		1 => 'Utilisateur',
		2 => 'Groupe'
	);

	// Droits utilisateurs :
	public function share_canEdit()
	{
		global $user;

		if (!$this->isLoaded($errors)) {
			return 1;
		}

		if ($this->share_isUserOwner($user->id)) {
			return 1;
		}

		if ($this->share_isSharedToUser($user->id, true)) {
			return 1;
		}

		return 0;
	}

	public function share_canDelete()
	{
		if (!$this->isLoaded($errors)) {
			return 1;
		}

		global $user;

		if ($this->share_isUserOwner($user->id)) {
			return 1;
		}

		return 0;
	}

	public function canEditShares()
	{
		return $this->share_isUserOwner();
	}

	// Getters booléens :
	public function share_isUserOwner($id_user = null)
	{
		if (is_null($id_user)) {
			global $user;
			$id_user = $user->id;
		}

		if (!(int) $id_user) {
			return 0;
		}

		switch ($this->getData('owner_type')) {
			case 1:
				return ($id_user == (int) $this->getData('id_owner'));

			case 2:
				BimpObject::loadClass('bimpcore', 'Bimp_UserGroup');
				$userGroups = Bimp_UserGroup::getUserUserGroupsList($id_user);
				return (in_array((int) $this->getData('id_owner'), $userGroups));
		}

		return 0;
	}

	public function share_isSharedToUser($id_user = null, $can_edit_only = false)
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
			'obj_name'   => $this->object_name,
			'id_obj'     => $this->id,
			'id_user'    => $id_user
		);

		if ($can_edit_only) {
			$filters['can_edit'] = 1;
		}

		if ((int) $this->db->getValue('bimp_user_share', 'id', BimpTools::getSqlWhere($filters, '', ''))) {
			return 1;
		}

		BimpObject::loadClass('bimpcore', 'Bimp_UserGroup');

		$userGroups = Bimp_UserGroup::getUserUserGroupsList($id_user);

		if (!empty($userGroups)) {
			$filters = array(
				'obj_module' => $this->module,
				'obj_name'   => $this->object_name,
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

	// Getters params :

	public function share_getListsExtraButtons()
	{
		$buttons = array();

		if ($this->isLoaded()) {
			if ($this->canEdit()) {
				$title = 'Partages ' . $this->getLabel('of_the') . ' ' . $this->getName();
				$buttons[] = array(
					'label'   => 'Partages',
					'icon'    => 'fas_share-alt',
					'onclick' => $this->getJsLoadModalCustomContent('share_renderShareLists', $title)
				);
			}
		}

		return $buttons;
	}

	public function share_getUserFilter($alias = 'a')
	{
		global $user;
		$id_user = $user->id;
		BimpObject::loadClass('bimpcore', 'Bimp_UserGroup');
		$user_groups = Bimp_UserGroup::getUserUserGroupsList($id_user);
		$primary = $this->getPrimary();

		$filter = array(
			'or' => array(
				'and_owner_user'        => array(
					'and_fields' => array(
						$alias . '.owner_type' => 1,
						$alias . '.id_owner'   => $id_user
					)
				),
				$alias . '.' . $primary => array(
					'or_field' => array(
						array(
							'in' => '(SELECT DISTINCT a_user_share.id_obj FROM ' . MAIN_DB_PREFIX . 'bimp_user_share a_user_share WHERE a_user_share.obj_module = \'' . $this->module . '\' AND a_user_share.obj_name = \'' . $this->object_name . '\' AND a_user_share.id_user = ' . (int) $id_user . ')'
						)
					)
				)
			)
		);

		if (!empty($user_groups)) {
			$filter['or']['and_owner_group'] = array(
				'and_fields' => array(
					$alias . '.owner_type' => 2,
					$alias . '.id_owner'   => $user_groups
				)
			);

			$filter['or'][$alias . '.' . $primary ]['or_field'][] = array(
				'in' => '(SELECT DISTINCT a_usergroup_share.id_obj FROM ' . MAIN_DB_PREFIX . 'bimp_usergroup_share a_usergroup_share WHERE a_usergroup_share.obj_module = \'' . $this->module. '\' AND a_usergroup_share.obj_name = \'' . $this->object_name . '\' AND a_usergroup_share.id_group IN (' . implode(',', $user_groups) . '))'
			);
		}

		return $filter;
	}

	public function share_getObjListFilters()
	{
		return array(
			array(
				'name'   => 'obj_user_filters',
				'filter' => $this->share_getUserFilter('a')
			)
		);
	}

	// Getters données :

	public function share_getUseObjectsIds($id_user = null, $filters = array(), $joins = array())
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

		BimpObject::loadClass('bimpcore', 'Bimp_UserGroup');
		$user_groups = Bimp_UserGroup::getUserUserGroupsList($id_user);

		$joins['us'] = array(
			'table' => 'bimp_user_share',
			'on'    => 'us.obj_module = \'' . $this->module . '\' AND us.obj_name = \'' . $this->object_name . '\' AND us.id_obj = a.' . $primary
		);

		$joins['ugs'] = array(
			'table' => 'bimp_usergroup_share',
			'on'    => 'ugs.obj_module = \'' . $this->module . '\' AND ugs.obj_name = \'' . $this->object_name . '\' AND ugs.id_obj = a.' . $primary
		);

		$filters['or_share'] = array(
			'or' => array(
				'and_owner_user'  => array(
					'and_fields' => array(
						'a.owner_type' => 1,
						'a.id_owner'   => $id_user
					)
				),
				'and_owner_group' => array(
					'and_fields' => array(
						'a.owner_type' => 2,
						'a.id_owner'   => $user_groups
					)
				),
				'us.id_user'      => $id_user,
				'ugs.id_group'    => $user_groups
			)
		);

		$sql = BimpTools::getSqlFullSelectQuery($table, array('DISTINCT a.' . $primary . ' as id'), $filters, $joins);
		$rows = $this->db->executeS($sql, 'array');

		if (is_array($rows)) {
			foreach ($rows as $r) {
				if ((int) $r['id'] && !in_array((int) $r['id'], $objects)) {
					$objects[] = (int) $r['id'];
				}
			}
		}

		return $objects;
	}

	// Affichages :

	public function share_displayOwner()
	{
		$id_owner = (int) $this->getData('id_owner');

		if (!$id_owner) {
			return '';
		}

		$html = '';

		switch ((int) $this->getData('owner_type')) {
			case 1:
				$user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('id_owner'));
				if (BimpObject::objectLoaded($user)) {
					$html .= $user->getLink();
				} else {
					$html .= '<span class="error">Utilisateur introuvable</span>';
				}
				break;

			case 2:
				$group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $this->getData('id_owner'));
				if (BimpObject::objectLoaded($group)) {
					$html .= $group->getLink();
				}
				break;
		}

		return $html;
	}

	// Rendus HTML
	public function share_renderShareUsers()
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
				foreach ($rows as $r) {
					$user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $r['id_user']);
					if (BimpObject::objectLoaded($user)) {
						$html .= ($html ? '<br/>' : '') . $user->getLink();
					}
				}
			}
		}

		return $html;
	}

	public function share_renderShareUserGroups()
	{
		$html = '';

		if ($this->isLoaded()) {
			$filters = array(
				'obj_module' => $this->module,
				'obj_name'   => $this->object_name,
				'id_obj'     => $this->id
			);

			$rows = $this->db->getRows('bimp_usergroup_share', BimpTools::getSqlWhere($filters, '', ''), null, 'array', array('id_group'));

			if (is_array($rows) && !empty($rows)) {
				foreach ($rows as $r) {
					$group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $r['id_group']);
					if (BimpObject::objectLoaded($group)) {
						$html .= ($html ? '<br/>' : '') . $group->getLink();
					}
				}
			}
		}

		return $html;
	}

	public function share_renderShareLists()
	{
		$html = '';

		$userShare = BimpObject::getInstance('bimpusertools', 'BimpUserShare');
		$list = new BC_ListTable($userShare, 'obj');
		$list->addFieldFilterValue('obj_module', $this->module);
		$list->addFieldFilterValue('obj_name', $this->object_name);
		$list->addFieldFilterValue('id_obj', $this->id);
		$html .= $list->renderHtml();

		$groupShare = BimpObject::getInstance('bimpusertools', 'BimpUserGroupShare');
		$list = new BC_ListTable($groupShare, 'obj');
		$list->addFieldFilterValue('obj_module', $this->module);
		$list->addFieldFilterValue('obj_name', $this->object_name);
		$list->addFieldFilterValue('id_obj', $this->id);
		$html .= $list->renderHtml();

		return $html;
	}


	// Overrides :
	public function share_create(&$warnings = array(), $force_create = false)
	{
		if (!(int) $this->getData('id_owner')) {
			global $user;
			$this->set('id_owner', $user->id);
			$this->set('owner_type', 1);
		}

		return array();
	}
}

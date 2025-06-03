<?php

require_once DOL_DOCUMENT_ROOT . '/bimpusertools/traits/share.php';

class BimpNoteModel extends BimpObject
{
	public static $traits = array('share');
	use share;

	// Getters droits users :

	public function canView()
	{
		return 1;
	}

	public function canCreate()
	{
		return 1;
	}

	public function canEdit()
	{
		return $this->share_canEdit();
	}

	public function canDelete()
	{
		return $this->share_canDelete();
	}

	public function canSetAction($action)
	{
		global $user;

		switch ($action) {
		}

		return parent::canSetAction($action);
	}

	// Getters booléens:


	// Getters params:

	public function getListsExtraButtons()
	{
		return $this->share_getListsExtraButtons();
	}

	// Getters array:

	public function getModelsArray($obj_module, $obj_name, $user_filters = true, $active_only = true, $include_empty = true) {
		$modeles = ($include_empty ? array(0 => '') : array());

		if ($obj_module && $obj_name) {
			$filters = array(
				'obj_module' => $obj_module,
				'obj_name'   => $obj_name,
				'active'     => 1
			);

			if ($active_only) {
				$filters['active'] = 1;
			}

			if ($user_filters) {
				$filters['obj_user_filters'] = $this->share_getUserFilter('a');
			}

			$rows = $this->getList($filters, null, null, 'id', 'ASC', 'array', array('id', 'name'));

			if (!empty($rows)) {
				foreach ($rows as $r) {
					$modeles[$r['id']] = $r['name'];
				}
			}
		}
		return $modeles;
	}

	// Getters données:

	public function getObjInstance()
	{
		$module = $this->getData('obj_module');
		$object_name = $this->getData('obj_name');

		if ($module && $object_name) {
			return BimpObject::getInstance($module, $object_name);
		}

		return null;
	}

	// Affichages:

	public function displayObj()
	{
		$obj = $this->getObjInstance();

		if (is_a($obj, 'BimpObject')) {
			return BimpTools::ucfirst($obj->getLabel());
		}

		return '';
	}

	// Rendus HTML:

	// Traitements:

	// Actions:

	// Overrides:
}

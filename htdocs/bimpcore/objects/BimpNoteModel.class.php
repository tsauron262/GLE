<?php

class BimpNoteModel extends BimpObject
{

	// Getters droits users :

	public function canView()
	{
		return 1;
		global $user;
		return (int) $user->admin;
	}

	public function canCreate()
	{
		return $this->canView();
	}

	public function canEdit()
	{
		return $this->canCreate();
	}

	public function canDelete()
	{
		return $this->canCreate();
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

	// Getters array:

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

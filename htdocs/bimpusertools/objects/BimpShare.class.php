<?php

class BimpShare extends BimpObject {
	public function canCreate()
	{
		$obj = $this->getObj();
		if (BimpObject::objectLoaded($obj) && method_exists($obj, 'canEditShares')) {
			return $obj->canEditShares();
		}

		return 1;
	}

	public function canEdit()
	{
		return $this->canCreate();
	}

	public function canDelete() {
		return $this->canCreate();
	}

	public function getObj()
	{
		$module = $this->getData('obj_module');
		$object_name = $this->getData('obj_name');
		$id_obj = (int) $this->getData('id_obj');

		if ($module && $object_name && $id_obj) {
			return BimpCache::getBimpObjectInstance($module, $object_name, $id_obj);
		}

		return null;
	}

	public function displayObj()
	{
		$obj = $this->getObj();

		if (BimpObject::objectLoaded($obj)) {
			return $obj->getLink();
		}

		return '';
	}
}

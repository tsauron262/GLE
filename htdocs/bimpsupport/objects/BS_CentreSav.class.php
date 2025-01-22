<?php

class BS_CentreSav extends BimpObject	{
	public function canCreate()
	{
		global $user;
		if ($user->admin) return 1;
		else return 0;
	}
	public function canDelete()
	{
		return $this->canCreate();
	}
	public function validate()
	{
		$errors = parent::validate();
		$code = $this->getData('code');
		if($code)	{
			$id_centre = $this->db->getValue($this->getTable(), "id", 'code = "'.$code.'"'. ($this->isLoaded() ? ' AND id != ' . $this->id : ''));
			if($id_centre)	{
				$errors[] = "Centre '".$code."' existe déjà.";
			}
		}
		return $errors;
	}
	public static function getCentreSav($code)	{
		return BimpCache::findBimpObjectInstance('bimpsupport', 'BS_CentreSav', array('code' => $code));
	}
}

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
}

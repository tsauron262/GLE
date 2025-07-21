<?php

class BS_CentreSav extends BimpObject
{
	public function canCreate()
	{
		global $user;
		if ($user->admin || $user->login == 'f.lauby') {
			return 1;
		} else {
			return 0;
		}
	}

	public function canEdit()
	{
		return $this->canCreate();
	}

	public function canDelete()
	{
		return $this->canCreate();
	}

	public function validate()
	{
		$errors = parent::validate();
		$code = $this->getData('code');
		if ($code) {
			$id_centre = $this->db->getValue($this->getTable(), "id", 'code = "' . $code . '"' . ($this->isLoaded() ? ' AND id != ' . $this->id : ''));
			if ($id_centre) {
				$errors[] = "Centre '" . $code . "' existe déjà.";
			}
		}
		return $errors;
	}

	public static function getCentreSav($code)
	{
		return BimpCache::findBimpObjectInstance('bimpsupport', 'BS_CentreSav', array('code' => $code));
	}

	public function renderLinkedListes($list_type = 'sav_centre')
	{
		global $db, $conf, $langs;

		$html = '';

		$errors = array();
		if (!$this->isLoaded($errors)) {
			return BimpRender::renderAlerts($errors);
		}

		$list = null;
		switch ($list_type) {
			case 'sav_centre':
				$list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_SAV'), 'centre');
				$list->addFieldFilterValue('a.code_centre', $this->getData('code'));
				break;
			case 'cgv_centre':
				$list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'BimpCGV'), 'cgvCentre');
				$list->addFieldFilterValue('a.id_centre', $this->getData('id'));
				break;
		}

		if (is_a($list, 'BC_ListTable')) {
			$html .= $list->renderHtml();
		} elseif ($list_type) {
			$html .= BimpRender::renderAlerts('La liste de type "' . $list_type . '" n\'existe pas');
		} else {
			$html .= BimpRender::renderAlerts('Type de liste non spécifié');
		}

		return $html;
	}
}

<?php

class Bimp_Concurrence_ExtEntity extends BimpObject	{
	public function displayUrl(){
		return '<a href="'.$this->getData('site').'">Site '.$this->getData('nom').'</a>';
	}

	public function getListExtraButtons()
	{
		$buttons = array();

		$ca = BimpObject::getBimpObjectInstance($this->module, 'Bimp_ChiffreAffaire');
		if ($this->isActionAllowed('setProcessed') && $this->canSetAction('processed')) {
			$buttons[] = array(
				'label'   => 'Liste des Ca',
				'icon'    => 'fas_euro-sign',
				'onclick' => $ca->getJsLoadModalList('default', array('extra_filters'=>array('type_obj'=>1, 'id_obj'=>$this->id), 'title'=>'Chiffre d\\\'affaire de la concurrence "'.$this->getData('nom').'"')),
			);
		}
		return $buttons;
	}
}

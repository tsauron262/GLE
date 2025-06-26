<?php

class Bimp_Concurrence_ExtEntity extends Bimp_Concurrence	{
	public function displayUrl(){
		return '<a href="'.$this->getData('site').'">Site '.$this->getData('nom').'</a>';
	}

	public function getListExtraButtons()
	{
		$buttons = array();

//		$ca = BimpObject::getBimpObjectInstance($this->module, 'Bimp_ChiffreAffaire');
		if ($this->isActionAllowed('setProcessed') && $this->canSetAction('processed')) {
			$buttons[] = array(
				'label'   => 'Liste des Ca',
				'icon'    => 'fas_euro-sign',
				'onclick' => $this->getJsLoadModalView('ca')
	//				'onclick' => $ca->getJsLoadModalList('default', array('extra_filters'=>array('type_obj'=>1, 'id_obj'=>$this->id), 'title'=>'Chiffre d\\\'affaire de la concurrence "'.$this->getData('nom').'"')),
			);
			$buttons[] = array(
				'label'   => 'Graph des Ca',
				'icon'    => 'fas_chart-bar',
				'onclick' => $this->getJsLoadModalView('ca_graph')
			);
		}
		return $buttons;
	}

	public function displayCa(){
		$cas = $this->getChildrenObjects('chiffre_affaire', array('fk_category'=>0, 'fk_period'=>0), 'debut_period', 'DESC');
		foreach($cas as $ca){
			return $ca->displayPeriode().' : '.BimpTools::displayMoneyValue($ca->getData('ca'));
		}
		/*
		 * ona pas trouvé de global on cherche avec categ
		 */
		$cas = $this->getChildrenObjects('chiffre_affaire', array('fk_period'=>0), 'debut_period', 'DESC');
		foreach($cas as $ca){
			return $ca->displayData('fk_category').' : '.$ca->displayPeriode().' : '.BimpTools::displayMoneyValue($ca->getData('ca'));
		}
//		print_r($cas);
//		return count($cas);
	}

	public function canAddConcurrence()	{
		$id = BimpTools::getPostFieldValue('id');
		$soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id);

		return $soc->isUserBD();
	}
}

/* todo comme le non extend n'existe pas*/
class Bimp_Concurrence extends BimpObject	{


}

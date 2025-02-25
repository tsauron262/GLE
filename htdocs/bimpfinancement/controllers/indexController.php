<?php


class indexController extends BimpController
{
	public function renderDemandesTab()
	{
		$tabs = array();

		$demande = BimpObject::getInstance('bimpfinancement', 'BF_Demande');

		foreach (array(
					 'en_cours'       => 'En cours d\'élaboration',
					 'complets'       => 'Contrats complés non cédés',
					 'en_place'       => 'Contrats mis en place',
					 'cession_finale' => 'Cession finale faite',
					 'canceled'       => 'Demande abandonnée',
					 'all'            => 'Toutes les demandes'
				 ) as $key => $label) {
			$tabs[] = array(
				'id'            => $key,
				'title'         => $label,
				'ajax'          => 1,
				'ajax_callback' => $demande->getJsLoadCustomContent('renderDemandesList', '$(\'#' . $key . ' .nav_tab_ajax_result\')', array($key, $label), array('button' => ''))
			);
		}

		return BimpRender::renderNavTabs($tabs);
	}
}

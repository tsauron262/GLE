<?php

class user_agendaController extends BimpController
{
	public function renderUserEventsTab()
	{
		$html = '';

		global $user;
		if (!BimpObject::objectLoaded($user)) {
			$html .= BimpRender::renderAlert('Aucun utilisateur connecté', 'danger');
		} else {
			$ac = BimpObject::getInstance('bimpcore', 'Bimp_ActionComm');
			$tabs = array(
				array(
					'id' => 'user_events_todo',
					'title' => 'À faire ou à venir',
					'icon' => 'fas_hourglass-start',
					'content' => $ac->renderUserEventsList('todo')
				),
				array(
					'id' => 'user_events_done',
					'title' => 'Terminés',
					'icon' => 'fas_check',
					'ajax'          => 1,
					'ajax_callback' => $ac->getJsLoadCustomContent('renderUserEventsList', '$(\'#user_events_done .nav_tab_ajax_result\')', array('done'), array('button' => ''))
				),
				array(
					'id' => 'user_events_all',
					'title' => 'Tous mes événements',
					'icon' => 'fas_bars',
					'ajax'          => 1,
					'ajax_callback' => $ac->getJsLoadCustomContent('renderUserEventsList', '$(\'#user_events_all .nav_tab_ajax_result\')', array('all'), array('button' => ''))
				)

			);

			$html .= BImpRender::renderNavTabs($tabs);
		}

		return $html;
	}
}

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
			$tabs = array(
				array(
					'id' => 'user_events_todo',
					'title' => 'À faire ou à venir',
					'content' => $this->renderUserEventsList('todo')
				)
			);

			$html .= BImpRender::renderNavTabs($tabs);
		}

		return $html;
	}

	public function renderUserEventsList($type)
	{
		$list = null;

		$ac = BimpObject::getInstance('bimpcore', 'Bimp_ActionComm');
		$list = new BC_ListTable($ac, 'user');
		$date_now = date('Y-m-d');

		switch ($type) {
			case 'todo':
				$list->params['title'] = 'Mes événements à faire ou à venir';
				$list->addFieldFilterValue('or_percent', array(
					'or' => array(
						'and_no_percent' => array(
							'and_fields' => array(
								'a.percent' => array(
									'operator' => '<',
									'value'    => 0
								),
								'or_date'   => array(
									'or' => array(
										'a.datep'  => array(
											'operator' => '>=',
											'value'    => $date_now . ' 00:00:00'
										),
										'a.datep2' => array(
											'operator' => '>=',
											'value'    => $date_now . ' 00:00:00'
										)
									)
								)
							)
						),
						'a.percent'      => array(
							'and' => array(
								array(
									'operator' => '>=',
									'value'    => 0
								),
								array(
									'operator' => '<',
									'value'    => 100
								)
							)

						)
					)
				));
				break;

			case 'done':
				$list->params['title'] = 'Mes événements  terminés';
				$list->addFieldFilterValue('or_percent', array(
					'or_done' => array(
						'and_no_percent' => array(
							'and_fields' => array(
								'a.percent' => array(
									'operator' => '<',
									'value'    => 0
								),
								'a.datep2' => array(
									'operator' => '<',
									'value'    => date('Y-m-d H:i:s')
								)
							)
						),
						'a.percent'      => array(
							'operator' => '>=',
							'value'    => 100
						)
					)
				));
				break;

			case 'all':
				$list->params['title'] = 'Tous mes événements';
				break;

			default:
				unset($list);
				return '';
		}

		global $user;
		$list->addFieldFilterValue('fk_user_action', $user->id);
		return $list->renderHtml();
	}
}

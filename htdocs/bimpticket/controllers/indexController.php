<?php

class indexController extends BimpController
{
	public function renderTicketList($name = '')	{
		global $user;
		$html = '';
		$ticket = BimpObject::getInstance('bimpticket', 'Bimp_Ticket');
		$list = new BC_ListTable($ticket);

		switch ($name)	{
			case 'mytickets' :
				$list->params['title']	= 'Tickets qui me sont assignés';
				$list->addFieldFilterValue('fk_user_assign', $user->id);
			break;
			case 'not_assigned' :
				$list->params['title'] = 'Tickets non assignés';
				$sql = 'a.fk_user_assign IS NULL';
				$list->addFieldFilterValue('fk_user_assign', array('custom' => $sql));
			break;
		}

		$html .= $list->renderHtml();

		return $html;
	}
}

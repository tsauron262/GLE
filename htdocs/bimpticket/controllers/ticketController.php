<?php

class TicketController extends BimpController
{
	public function init()
	{
		parent::init();

		$id_ticket = (int) BimpTools::getValue('id', 0, 'int');

		if (!BimpTools::getValue('ajax', 0, 'int') && $id_ticket) {
			$ticket = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', $id_ticket);

			if (BimpObject::objectLoaded($ticket) && !(int) $ticket->getData('fk_statut')) {
				$ticket->checkStatus();
			}
		}
	}
}

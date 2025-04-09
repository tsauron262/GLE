<?php

class testController extends BimpController
{

	public function renderHtml()
	{
		if (!BimpCore::isUserDev()) {
			return BimpRender::renderAlerts('Page réservée aux développeurs');
		}

		$id_user = BimpTools::getValue('id_user', 270);

		$errors = array();
		$html = '';


//		BimpObject::loadClass('bimpcore', 'Bimp_ActionComm');
//		$data = Bimp_ActionComm::getActionCommEventsForUser($id_user, '');

//		BimpObject::loadClass('bimpticket', 'Bimp_Ticket');
//		$data = Bimp_Ticket::getTicketsForUser($id_user, '', array(), $errors);
//
//		if (count($errors)) {
//			$html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'danger'));
//		}
//		$html .= 'User ' . $id_user . '<pre>' . print_r($data, 1) . '</pre>';

		$ticket = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', 4);

		if (BimpObject::objectLoaded($ticket)) {
			$html .= 'NB : ' .$ticket->getNbNotesFormUser(270);
			$html .= ' - err : ' .  BimpCache::getBdb()->err();
		}
		return $html;
	}
}

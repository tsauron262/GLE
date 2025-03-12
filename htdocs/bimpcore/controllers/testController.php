<?php

class testController extends BimpController
{

	public function renderHtml()
	{
		if (!BimpCore::isUserDev()) {
			return BimpRender::renderAlerts('Page réservée aux développeurs');
		}

		$id_user = BimpTools::getValue('id_user', 270);

		$html = '';
		BimpObject::loadClass('bimpcore', 'Bimp_ActionComm');
		$data = Bimp_ActionComm::getActionCommEventsForUser($id_user, '');

		$html .= 'User ' . $id_user . '<pre>' . print_r($data, 1) . '</pre>';

		return $html;
	}
}

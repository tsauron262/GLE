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

		$html .= BimpInput::renderinput('object_field', 'test', '', array(
			'module' => 'bimpticket',
			'object_name' => 'Bimp_Ticket'
		));

		return $html;
	}
}

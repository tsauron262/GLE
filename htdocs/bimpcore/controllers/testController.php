<?php

class testController extends BimpController
{

	public function renderHtml()
	{
		if (!BimpCore::isUserDev()) {
			return BimpRender::renderAlerts('Page réservée aux développeurs');
		}
		$html = '';

		if ((int) BimpTools::getValue('err_fat', 0, 'int')) {
			dfkmjghdlkjghfd();
		} else {
			$html .= 'Aj err_fat=1 dans l\'url pour déclencher une err fatale';
		}

		return $html;
	}
}

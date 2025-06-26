<?php

class testController extends BimpController
{

	public function renderHtml()
	{
		if (!BimpCore::isUserDev()) {
			return BimpRender::renderAlerts('Page réservée aux développeurs');
		}

		$html = '';

		$ticket = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', 8428);
		if (BimpObject::objectLoaded($ticket)) {
			$html .= '<h1>TESTS</h1>';

			$txt = $ticket->getData('message');

			$html .= htmlentities($txt);
			$html .= '<br/><br/>';
			$html .= $txt;

			$txt = BimpTools::replaceBr($txt, '[[BR]]');
			foreach (array('div', 'p') as $tag) {
				$txt = str_replace('</' . $tag . '>', '</' . $tag . '>[[BR]]', $txt);
			}

			$html .= '<br/>------------------<br/>';
			$html .= htmlentities($txt);
			$html .= '<br/><br/>';
			$html .= $txt;

			$html .= '<br/>------------------<br/>';
			$txt = strip_tags($txt);
			$txt = str_replace('[[BR]]', "\n", $txt);
			$txt = preg_replace("(\n+)", '<br/>', $txt);
			$html .= $txt;

		}
		return $html;
	}
}

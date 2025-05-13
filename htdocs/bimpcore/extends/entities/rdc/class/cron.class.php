<?php

require_once DOL_DOCUMENT_ROOT.'/bimpcore/classes/BimpCron.php';

class cron extends BimpCron
{
	public function weeklyProcess()
	{
		$retCron = array();
		if ($this->relance6mois() != 1)
			$retCron[] = 'Erreur lors de la relance 6 mois';

		if(count($retCron))	{
			$this->output = implode("<br>", $retCron);
			return 1;
		}
		$this->output = 'Tâches cron effectuées avec succès';
		return 0;
	}

	public function relance6mois()
	{
		$err = array();
		// faire la liste par DB/KAM des marchants à relancer (date der_contact > 6 mois)
		$marchants = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client');
		$list = $marchants->getList(array(
			'date_der_contact' => array('custom' => 'date_der_contact <= DATE_SUB(NOW(), INTERVAL 6 MONTH)'),
			'fk_user_attr_rdc' => '> 0',
		));
		$relance = array();
		foreach ($list as $element) {
			$relance[$element['fk_user_attr_rdc']][] = $element['rowid'];
		}

		foreach ($relance as $bdkam => $socIds) {
			$err0 = array();
			$user_dest = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $bdkam);
			$x = count($socIds);
			$s = $x>1 ? 's' : '';
			$message = "Bonjour,<p>Voici " . $x . " marchant" .$s . " non contacté depuis 6 mois :</p><ul>";
			foreach ($socIds as $id) {
				$socMarchant = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id);
				$message .= '<li>' . $socMarchant->getData('nom') . '<br>' . $socMarchant->getData('name_alias') . '<br>' . $socMarchant->getLink() . '</li>';
			}
			$message .= "</ul><p>Merci de prendre contact rapidement,</p><p>Cordialement,</p>";

			$err0[] = BimpUserMsg::envoiMsg('relance_6mois_bdkam','Relance 6 mois',$message,$bdkam);
//			echo '<pre>'; print_r($err0); echo '</pre>';
			if($err0[0]) $err[] = implode(",", $err0[0]);
		}

		if (count($err) > 0) {
			BimpCore::addlog('Erreur lors de l\'envoi des messages : ' . implode(', ', $err), 4);
			return 0;
		}
		else return 1;
	}

}

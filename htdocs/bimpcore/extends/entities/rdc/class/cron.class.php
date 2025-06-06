<?php

require_once DOL_DOCUMENT_ROOT.'/bimpcore/classes/BimpCron.php';

class cron extends BimpCron
{
	const API_BI = 2;

	public function weeklyProcess()
	{
		$retCron = array();
		if ($this->relanceEnAttenteOnboarding() != 1) {
			$retCron[] = 'Erreur lors de la relance des onboarding';
		}
		if ($this->relance6mois() != 1) {
			$retCron[] = 'Erreur lors de la relance 6 mois';
		}

		if (count($retCron)) {
			$this->output = implode("<br>", $retCron);
			return 1;
		}
		$this->output = 'Tâches cron effectuées avec succès';
		return 0;
	}

	public function relance6mois()
	{
		$err = array();
		// faire la liste par DB/KAM des marchands à relancer (date der_contact > 6 mois)
		$marchands = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client');
		$list = $marchands->getList(array(
			'date_der_contact' => array('custom' => 'date_der_contact <= DATE_SUB(NOW(), INTERVAL 6 MONTH)'),
			'fk_user_attr_rdc' => '> 0',
		));
		$relance = array();
		foreach ($list as $element) {
			$relance[$element['fk_user_attr_rdc']][] = $element['rowid'];
		}

		foreach ($relance as $bdkam => $socIds) {
			$err0 = array();
			$x = count($socIds);
			$s = $x > 1 ? 's' : '';
			$message = "Bonjour,<p>Voici " . $x . " marchand" . $s . " non contacté depuis 6 mois :</p><ul>";
			foreach ($socIds as $id) {
				$socMarchand = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id);
				$message .= '<li>Raison sociale&nbsp;: ' . $socMarchand->getLink() . '<br>Boutique&nbsp;: ' . $socMarchand->getData('name_alias')  . '</li>';
			}
			$message .= "</ul><p>Merci de prendre contact rapidement,</p><p>Cordialement,</p>";

			$err0[] = BimpUserMsg::envoiMsg('relance_6mois_bdkam', 'Relance 6 mois', $message, $bdkam);
//			echo '<pre>'; print_r($err0); echo '</pre>';
			if ($err0[0]) {
				$err[] = implode(",", $err0[0]);
			}
		}

		if (count($err) > 0) {
			BimpCore::addlog('Erreur lors de l\'envoi des messages relance6mois : ' . implode(', ', $err), 4);
			return 0;
		} else {
			return 1;
		}
	}

	public function relanceEnAttenteOnboarding()
	{
		$err = array();
		// faire la liste par DB/KAM des marchands à relancer (marchand en attente onboarding)
		$marchands = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client');
		$list = $marchands->getList(array(
			'fk_statut_rdc'    => '8',
			'fk_user_attr_rdc' => '> 0',
		));
		$relance = array();
		foreach ($list as $element) {
			$relance[$element['fk_user_attr_rdc']][] = $element['rowid'];
		}

		foreach ($relance as $bdkam => $socIds) {
			$err0 = array();
			$x = count($socIds);
			$s = $x > 1 ? 's' : '';
			$message = "Bonjour,<p>Voici " . $x . " marchand" . $s . " en attente d'onboarding :</p><ul>";
			foreach ($socIds as $id) {
				$socMarchand = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id);
				$message .= '<li>Raison sociale&nbsp;: ' . $socMarchand->getLink() . '<br>Boutique&nbsp;: ' . $socMarchand->getData('name_alias')  . '</li>';
			}
			$message .= "</ul><p>Cordialement,</p>";

			$err0[] = BimpUserMsg::envoiMsg('relance_onboarding_bdkam', 'Relance onboarding', $message, $bdkam);
			if ($err0[0]) {
				$err[] = implode(",", $err0[0]);
			}
		}
		if (count($err) > 0) {
			BimpCore::addlog('Erreur lors de l\'envoi des messages relanceEnAttenteOnboarding : ' . implode(', ', $err), 4);
			return 0;
		} else {
			return 1;
		}

	}


	public function dailyProcess()
	{
		$retCron = array();
//		if ($this->updateMirakl() != 1) {
//			$retCron[] = 'Erreur lors de la mise à jour des données Mirakl';
//		}


		if (count($retCron)) {
			$this->output = implode("<br>", $retCron);
			return 1;
		}
		$this->output = 'Tâches cron effectuées avec succès';
		return 0;
	}

	public function updateMirakl()	{
		$err = array();
		// faire la liste des marchands à mettre à jour (Date de mise à jour Mirakl vide ou > 1 jour) et shopid > 0
		global $db;
		$bdd = new BimpDb($db);
		$list = $bdd->getRows('societe', 'shopid > 0 AND (date_maj_mirakl <= DATE_SUB(NOW(), INTERVAL 1 DAY) OR date_maj_mirakl IS NULL)', 500, 'array', array('rowid'));
		foreach ($list as $element) {
			// on ne met à jour que les marchands
			$socMarchand = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $element['rowid']);
			$err0 = $socMarchand->appelMiraklS20();
			if ($err0[0]) {
				$err[] = implode(",", $err0[0]);
			}
		}

		if (count($err) > 0) {
			$this->output = 'Erreur lors de la mise à jour des données Mirakl : ' . implode(', ', $err);
			BimpCore::addlog('Erreur lors de la mise a jour Mirakl : ' . implode(', ', $err), 4);
			return 1;
		} else {
			$verif = $bdd->getRows('societe', 'date_maj_mirakl >= DATE_SUB(NOW(), INTERVAL 1 DAY)', null, 'array', array('rowid'));
			$this->output = 'Données Mirakl effectuée avec succès pour ' . count($verif) . ' marchands';
			return 0;
		}
	}

	public function updateCaByPowerBi()
	{
		$warnings = array();
		$errors = array();
		require_once DOL_DOCUMENT_ROOT . 'bimpapi/BimpApi_Lib.php';
		$api = BimpAPI::getApiInstance('bi');
		//$api = BimpCache::getBimpObjectInstance('bimpapi', 'BiAPI');
		$api->majCaWithNbDay(10, $warnings, $errors);

		$this->output = 'Mise à jour du CA effectuée avec succès : ' . implode(', ', $warnings);
		return 0;
	}
}

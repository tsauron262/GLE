<?php

require_once DOL_DOCUMENT_ROOT.'/bimpcore/classes/BimpCron.php';

class cron extends BimpCron
{
	const API_BI = 2;

	public function relance6mois()
	{
		$err = array();
		// faire la liste par DB/KAM des marchands à relancer (date der_contact > 6 mois)
		$marchands = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client');
		$list = $marchands->getList(array(
			'date_der_contact' => array('custom' => 'date_der_contact <= DATE_SUB(NOW(), INTERVAL 6 MONTH)'),
		));
		foreach ($list as $element) {
			$text = 'Ce marchand n\'a pas été contacté depuis plus de 6 mois';
			$err0 = array();
			$soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $element['rowid']);

			$dejaInfo = false;
			$oldNotes = $soc->getNotes();
			$dejaInfo = $this->dejaInfo($oldNotes, $text, 'P6M');
			if ($dejaInfo) continue;

			list($type_dest, $idGroup, $idUser, $msg)	= $this->getParamsNote($soc->getData('fk_user_attr_rdc'), $text);

			$err0 = $soc->addNote($msg,
				BimpNote::BN_AUTHOR, 0, 1, '',
				BimpNote::BN_AUTHOR_USER, $type_dest,
				$idGroup, $idUser
			);
			if ($err0[0]) {
				$err[] = implode(",", $err0[0]);
			}
		}

		if (count($err) > 0) {
			BimpCore::addlog('Erreur lors de la relance6mois : ' . implode(', ', $err), 4);
			return 1;
		} else {
			$this->output = 'Relance 6mois OK pour ' . strval(count($list)) . ' marchands';
			return 0;
		}
	}

	public function relanceEnAttenteOnboarding()
	{
		$err = array();
		// faire la liste par DB/KAM des marchands à relancer (marchand en attente onboarding)
		$marchands = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client');
		$list = $marchands->getList(array(
			'fk_statut_rdc'    => $marchands::STATUS_RDC_ATTENTE_ONBORDING,
		));
		foreach ($list as $element) {
			$text = 'Ce marchand est en attente onboarding';
			$err0 = array();
			$soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $element['rowid']);

			list($type_dest, $idGroup, $idUser, $msg)	= $this->getParamsNote($soc->getData('fk_user_attr_rdc'), $text);

			$err0 = $soc->addNote($msg,
				BimpNote::BN_AUTHOR, 0, 1, '',
				BimpNote::BN_AUTHOR_USER, $type_dest,
				$idGroup, $idUser
			);
			if ($err0[0]) {
				$err[] = implode(",", $err0[0]);
			}
		}

		if (count($err) > 0) {
			BimpCore::addlog('Erreur lors de relanceEnAttenteOnboarding : ' . implode(', ', $err), 4);
			return 1;
		} else {
			$this->output = 'relanceEnAttenteOnboarding OK pour ' . strval(count($list)) . ' marchands';
			return 0;
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
		$list = $bdd->getRows('societe', 'shopid > 0 AND (date_maj_mirakl <= DATE_SUB(NOW(), INTERVAL 1 DAY) OR date_maj_mirakl IS NULL)', 250, 'array', array('rowid'));
		foreach ($list as $element) {
			$warnings = array();
			// on ne met à jour que les marchands
			$socMarchand = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $element['rowid']);
			$socMarchand->appelMiraklS20($warnings);
			if ($warnings) {
				$err = BimpTools::merge_array($err, $warnings);
			}
		}

		if (count($err) > 0) {
			BimpCore::addlog('Erreur lors de la mise a jour Mirakl :<br>' . implode('<br>', $err), 4);
		}

		$verif = $bdd->getRows('societe', 'date_maj_mirakl >= DATE_SUB(NOW(), INTERVAL 1 DAY)', null, 'array', array('rowid'));
		$this->output = 'Données Mirakl à jour pour ' . count($verif) . ' marchands';
		if (count($err) > 0)
			$this->output .= '<hr>Erreurs :<br>' . implode('<br>', $err);
		return 0;
	}

	public function rechercheManquantMirakl()
	{
		require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';
		$api = BimpAPI::getApiInstance('mirakl');
		if (!isset($api) || !is_object($api)) {
			$this->output = 'Module API non actif';
			return 1;
		}

		$step = $max = 100;
		$shopManquants = array();
		$shopEnDoublon = array();
		$errors = array();
		$bdd = new BimpDb($this->db);

		for ($i = 0; $i < $max; $i += $step) {
//			echo '<hr>Récupération des shops Mirakl de ' . $i . ' à ' . ($i + $step) . '<br>';
			$ret = $api->getShopsPagination($i, $step, $errors);
			$max = $ret['total_count'];
			foreach ($ret['shops'] as $shop) {
				$shopId = $shop['shop_id'];
				// on verifie si le shop existant en base de données
				$nb = $bdd->getCount('societe', 'shopid = ' . $shopId, 'rowid');
				if ($nb == 1) continue;

				if ($nb == 0) {
					$shopManquants[] = array(
						'id' => $shopId,
						'shop_name' => $shop['shop_name'],
						'corporate_name' => $shop['pro_details']['corporate_name'],
					);
				}
				else {
					$shopEnDoublon[] = array(
						'id' => $shopId,
						'shop_name' => $shop['shop_name'],
						'corporate_name' => $shop['pro_details']['corporate_name'],
						'nb' => $nb,
					);
				}
			}
		}

		if (count($errors)) {
			$this->output = 'Erreur lors de la récupération des shops Mirakl : ' . implode(', ', $errors);
			return 1;
		}

		$ret = array();
		$titres = array();
		$msg = '';
		if (count($shopManquants) == 0) {
			$ret[] = 'Aucun shop manquant trouvé dans Mirakl';
			$titres[] = '<p>Aucun shop manquant trouvé dans Mirakl</p>';
		}
		else {
			$ret[] = 'Nombre de shops manquants trouvés dans Mirakl : ' . count($shopManquants);
			$titres[] = '<p>Nombre de shops manquants trouvés dans Mirakl : <strong>' . count($shopManquants) . '</strong></p>';
			$msg .= '<h3>Liste des shops manquants :</h3>';
			$msg .= '<table style="width: 100%; border-collapse: collapse;">';
			$msg .= '<tr><th>Shop ID</th><th>Nom de la boutique</th><th>Raison sociale</th></tr>';
			foreach ($shopManquants as $shop) {
				$msg .= '<tr>';
				$msg .= '<td>' . $shop['id'] . '</td>';
				$msg .= '<td style="padding-left: 1em;">' . $shop['shop_name'] . '</td>';
				$msg .= '<td style="padding-left: 1em;">' . $shop['corporate_name'] . '</td>';
				$msg .= '</tr>';
			}
			$msg .= '</table>';
		}

		if (count($shopEnDoublon) == 0) {
			$ret[] = 'Aucun shop en doublon trouvé dans GLE';
			$titres[] = 'Aucun shop en doublon trouvé dans GLE</p>';
		}
		else {
			$ret[] = 'Nombre de shops en doublon trouvés dans GLE : ' . count($shopEnDoublon);
			$titres[] = '<p>Nombre de shops en doublon trouvés dans Mirakl : <strong>' . count($shopEnDoublon) . '</strong></p>';
			$msg .= '<h3>Liste des shops en doublon :</h3>';
			$msg .= '<table style="width: 100%; border-collapse: collapse;">';
			$msg .= '<tr><th>Shop ID</th><th>Nom de la boutique</th><th>Raison sociale</th><th>NB</th></tr>';
			foreach ($shopEnDoublon as $shop) {
				$msg .= '<tr>';
				$msg .= '<td>' . $shop['id'] . '</td>';
				$msg .= '<td style="padding-left: 1em;">' . $shop['shop_name'] . '</td>';
				$msg .= '<td style="padding-left: 1em;">' . $shop['corporate_name'] . '</td>';
				$msg .= '<td>' . $shop['nb'] . '</td>';
				$msg .= '</tr>';
			}
			$msg .= '</table>';
		}


		$contenu = implode('', $titres) . $msg;
		$code = 'rechercheManquantMirakl';
		$sujet = 'Rapport de recherche des shops en annomalie dans Mirakl';
		BimpUserMsg::envoiMsg($code, $sujet, $contenu);

		$this->output = implode("<br>", $ret);
		return 0;
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

	public function getParamsNote($attr, $text)	{
		if ($attr) {
			$bdd = new BimpDb($this->db);
			$sql = 'SELECT g.rowid FROM ' . MAIN_DB_PREFIX . 'usergroup AS g';
			$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'usergroup_user AS ugu ON ugu.fk_usergroup = g.rowid';
			$sql .= ' WHERE g.nom IN ("BD", "KAM", "Qualité", "Manager")';
			$sql .= ' AND ugu.fk_user = ' . $attr;

			$ret = $bdd->executeS($sql, 'array');

			if($ret[0])	{
				$t = BimpNote::BN_DEST_GROUP;
				$g = (int)$ret[0]['rowid'];
				$u = 0;
			}
			else	{
				$t = BimpNote::BN_DEST_USER;
				$g = 0;
				$u = $attr;
			}
		}
		else  {
			$t = BimpNote::BN_DEST_GROUP;
			$g = 12; // managers
			$u = 0;
			$text .= '<br>(Pas de DB/KAM désigné)';
		}
		return array($t, $g, $u, $text);
	}

	public function dejaInfo($notes, $text, $interval)	{
		$dejaInfo = false;
		foreach ($notes as $note) {
			$dateNote = new DateTime($note->getData('date_note'));
			$dateNote->add(new DateInterval($interval));
			$aujourdhui = new DateTime();
			if ($dateNote > $aujourdhui) {
				if (strstr($note->getData('content'), $text) !== false) {
					$dejaInfo = true;
				}
			}
		}
		return $dejaInfo;
	}
}

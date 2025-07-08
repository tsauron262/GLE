<?php

require_once DOL_DOCUMENT_ROOT.'/bimpcore/classes/BimpCron.php';
require_once DOL_DOCUMENT_ROOT.'/bimpfinancement/objects/BF_Demande.class.php';

class cron extends BimpCron
{
	public function notif_fin_contrat()
	{
		global $db, $user;
		$warnings = array();
		$err = array();
		$messages = array();
		// selection les demandes Acceptée qui on une date de fin de mois de 4 mois
		$bdd = new bimpDB($db);
		$where = 'd.status = ' . BF_Demande::STATUS_ACCEPTED;
		$where .= ' AND d.devis_status = ' . BF_Demande::DOC_ACCEPTED;
		$where .= ' AND d.contrat_status = ' . BF_Demande::DOC_ACCEPTED;
		$where .= ' AND d.pvr_status = ' . BF_Demande::DOC_ACCEPTED;
		$where .= ' AND d.id_facture_fin > 0';
		$where .= ' AND d.id_facture_fourn > 0';
		$where .= ' AND (id_facture_cli_rev = 0 OR id_facture_fourn_rev = 0)'; // renderDemandesList case en_place
		$where .= ' AND d.date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 4 MONTH)';

		$demandes = $bdd->getRows('bf_demande AS d', $where);
		foreach ($demandes as $d) {
			$demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $d->id);
			$date_fin = new DateTime($demande->getData('date_fin'));
			$df = $date_fin->format('d/m/Y');
			$decal = $demande->getData('nb_mois_avant_notif') ? $demande->getData('nb_mois_avant_notif') : BimpCore::getConf('def_mois_avant_notif', 3,'bimpfinancement') ;
			$date_fin->modify('-'.$decal.' month');
			$diff = $date_fin->diff(new DateTime(date('Y-m-d')));
			if ($diff->invert == 1) continue;

			$id_resp = $demande->getData('id_user_resp');
			// y'a-t-il déja une note de notification

			$notes = $demande->getNotes();
			$OK_notif = true;
			$baseContent = 'Fin de contrat prévu le ';
			foreach ($notes as $note) {
				if(strpos($note->getData('content'), $baseContent) !== false) $OK_notif = false;
			}
			if (!$OK_notif) continue;
			$code = 'fin_financement_resp_prolease';
			$sujet = 'Fin de financement de location';
			$contenu = $baseContent . $df;
			$messages[] = $contenu;
			$err0 = BimpUserMsg::envoiMsg($code, $sujet, $contenu, $demande);
			if($err0) $err[] = implode(',', $err0);

			if ($demande->getData('id_supplier_contact')) { // apporteur externe (mail)
				$apporteur = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $demande->getData('id_supplier_contact'));
				if (BimpObject::objectLoaded($apporteur)) {
					$code = 'fin_financement_apporteur_externe';
					$msg = 'Bonjour ' . $apporteur->getData('firstname') . ', <br>';
					$msg .= 'Le contrat de location ' . $demande->getData('ref') . ' arrive à expiration le ' .date('d/m/Y',strtotime($demande->getData('date_fin')));

					$err0 = BimpUserMsg::envoiMsg($code, $sujet, $msg, $apporteur->getData('email'));
					if($err0) $err[] = implode(',', $err0);
				}
			}

			if ($demande->getData('id_main_source')) { // apporteur interne (note via API)
				$source = $demande->getChildObject('main_source');
				$api = $source->getAPI($err, 1);
				$api->sendNotifFinContrat(
					$source->getData('id_demande'),
					$source->getData('type_origine'),
					$source->getData('id_origine'),
					$source->getData('id_commercial'),
					$contenu,
					$err,
					$warnings
				);
			}
		}

		if (count($err) > 0) {
			$this->output = 'Erreur  : ' . implode(', ', $err);
			BimpCore::addlog('Erreur lors des notification de fin de contrat : ' . implode(', ', $err), 4);
			return 1;
		} else {
			$this->output = 'Ok pour la notification de fin de contrat : ' . implode('<br>', $messages);
			return 0;
		}
	}
}

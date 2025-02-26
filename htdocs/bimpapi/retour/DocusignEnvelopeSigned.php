<?php

define('NOREQUIREHTML', 1);
define('NOCSRFCHECK', 1);
define('NO_REDIRECT_LOGIN', 1);
define('NOLOGIN', 1);
require_once("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

//recipient-completed = {"event":"recipient-completed","apiVersion":"v2.1","uri":"/restapi/v2.1/accounts/8b411bfe-54f5-47fc-bbf2-55d9a71a200f/envelopes/71799c95-8417-4b16-90d5-86ff24a91d09","retryCount":0,"configurationId":10363315,"generatedDateTime":"2022-12-09T18:29:29.7360000Z","data":{"accountId":"8b411bfe-54f5-47fc-bbf2-55d9a71a200f","userId":"4214323f-c281-4a0e-80f7-37b3ea7d8665","envelopeId":"71799c95-8417-4b16-90d5-86ff24a91d09","recipientId":"1"}}
//envelope-completed = {"event":"envelope-completed","apiVersion":"v2.1","uri":"/restapi/v2.1/accounts/8b411bfe-54f5-47fc-bbf2-55d9a71a200f/envelopes/71799c95-8417-4b16-90d5-86ff24a91d09","retryCount":0,"configurationId":10363315,"generatedDateTime":"2022-12-09T18:32:13.2990000Z","data":{"accountId":"8b411bfe-54f5-47fc-bbf2-55d9a71a200f","userId":"4214323f-c281-4a0e-80f7-37b3ea7d8665","envelopeId":"71799c95-8417-4b16-90d5-86ff24a91d09"}}

//mailSyn2('Retour DocuSign begin', 'f.martinez@bimp.fr', '', 'Retour DocuSign');

$errors = $warnings = array();
$mode_debug = false;

global $user;
$user->fetch(1);

$signature = null;


if ($mode_debug) {
	$body = file_get_contents(DOL_DATA_ROOT . '/docusign_webhook.txt');
} else {
	$body = file_get_contents('php://input');
//	file_put_contents(DOL_DATA_ROOT . '/docusign_webhook.txt', $body);
}

if (!$body) {
	$errors[] = 'Donnée absentes';
} else {
	$data = json_decode($body, 1);

	if (!is_array($data) || empty($data)) {
		$errors[] = 'Données reçues invalides';
	} else {
		if ($mode_debug) {
			echo 'DATA :<pre>';
			print_r($data);
			echo '</pre>';
		}

		mailSyn2('Retour DocuSign', 'f.martinez@bimp.fr', '', '<pre>' . print_r($data, 1) . '</pre>');

		$envelopeId = BimpTools::getArrayValueFromPath($data, 'data/envelopeId', '');
		$event = BimpTools::getArrayValueFromPath($data, 'event', '');

		if (!$envelopeId) {
			$errors[] = 'ID DocuSign absent';
		} else {
			$signature = BimpCache::findBimpObjectInstance('bimpcore', 'BimpSignature', array(
				'id_envelope_docu_sign' => $envelopeId
			), true);

			if (!BimpObject::objectLoaded($signature)) {
				$errors[] = "Aucune signature existante pour l'ID DocuSign " . $envelopeId;
			} else {
				BimpCore::addlog('Retour DocuSign', Bimp_Log::BIMP_LOG_NOTIF, 'api', $signature, array(
					'event' => $event,
					'data'  => $data
				));

				if ($event == 'envelope-completed' || $event == 'recipient-completed') {
					$success = '';
					$refresh_errors = $signature->refreshDocuSignDocument(true, $warnings, $success);

					if (count($refresh_errors)) {
						$errors[] = BimpTools::getMsgFromArray($refresh_errors, 'Echec de la mise à jour des données DocuSign');
					}
				}
			}
		}
	}
}

if ($mode_debug && count($warnings)) {
	echo 'ALERTES :<pre>';
	print_r($warnings);
	echo '</pre>';
}

if (count($errors)) {
	if ($mode_debug) {
		echo 'ERREURS : <pre>';
		print_r($errors);
		echo '</pre>';
	} else {
		BimpCore::addlog('Erreurs lors du webhook DocusignEnvelopeSigned', Bimp_Log::BIMP_LOG_URGENT, 'api', $signature, array(
			'Erreurs' => $errors
		));
	}
} else {
	echo 'OK : ' . $success . '<br/>';
}

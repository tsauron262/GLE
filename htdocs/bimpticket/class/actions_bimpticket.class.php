<?php

class ActionsBimpticket
{
	// Class properties and methods go here
	function addmoduletoeamailcollectorjoinpiece($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter
		$myvalue = 'test'; // A result value
		if (in_array('emailcolector', explode(':', $parameters['context']))) {
			global $db, $conf, $user;

			$sql = 'select rowid, ref from ' . MAIN_DB_PREFIX . 'ticket ORDER BY rowid DESC LIMIT 1';
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
			} else {
				$error++;
				$this->errors[] = 'Error fetching ticket';
			}

			$bimpT = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', $obj->rowid);
			$folder = $bimpT->getFilesDir();

			foreach ($parameters['data'] as $key => $value) {
				$filename = $folder . '/' . $key;
				$fp = file_put_contents($filename, $value);
				if (!$fp) {
					$error++;
					$this->errors[] = 'Error writing file: ' . $filename;
				}
			}
//			exit (var_dump( $folder));

			/*
						$folder = DOL_DATA_ROOT.'/ticket/'.$obj->ref;

						echo $folder . '<pre>';
						print_r($obj); // oui, ca c'est le bon ticket
						echo '</pre>';

						foreach ($parameters['data'] as $key => $value) {
							var_dump($folder . '/' . $key, file_put_contents($folder . '/' . $key,
								$value));
							exit;
			//				echo $key . '<pre>';
			//				print_r($value);
			//				echo '</pre>';
			//				exit;
						}
			*/
//			echo '<pre>';
//			print_r($parameters['data']); // ca contient les fichiers joints
//			echo '</pre>';
//			exit('end of function');

			// do something only for the context 'somecontext'
		}


		if (!$error) {
//			$this->results = array('myreturn' => $myvalue);
			$this->resprints = 'Piece jointe ajoutée avec succès';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	function addMoreActionsEmailCollector($parameters, &$object, &$action, $hookmanager)
	{
		$this->results['hookBimpticketResponse'] = "Traitement réponse Bimp_Ticket";
		$this->results['hookBimpticketInitial'] = "Traitement initial Bimp_Ticket";
		return 0;
	}

	function doCollectImapOneCollector($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $user;
		$bdb = BimpCache::getBdb();

		$errors = array();

		$msg = $parameters['imapemail']->bodies['html'];
		if ($msg == '') {
			$msg = $parameters['imapemail']->bodies['text'];
		}

		$traite = 0;

		$matches = array();
		preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)(\r\n|\s$)/m', $parameters['header'], $matches);
		$headers = array_combine($matches[1], $matches[2]);

		$to_emails = array();

		foreach ($parameters['imapemail']->get('to')->toArray() as $to) {
			$to_emails[] = $to->mail;
		}

		$cc = array();
		if (!empty($parameters['cc'])) {
			$cc = $parameters['cc'];
			if (!is_array($cc)) {
				$cc = explode(', ', BimpTools::cleanEmailsStr($cc));
			}

			if ($user->login == 'f.martinez') {
				echo '<pre>' . print_r($parameters['cc'], 1) . '</pre>';
				echo '<pre>' . print_r($cc, 1) . '</pre>';
			}
		}


//		echo '<pre>' . print_r($matches[2], true) . '</pre>';
		switch ($action) {
			case 'hookBimpticketResponse':
				break;

			case 'hookBimpticketInitial':
				//		echo '<pre>';print_r($parameters['from'].$bimp_ticket->id);echo '</pre>';die;
				if ($parameters['new']) {
					if (!$traite && isset($parameters['objectemail']) && is_a($parameters['objectemail'], 'ticket')) {
						$ticket = $parameters['objectemail'];
						$Bimp_Ticket = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', $ticket->id);
						// trouver le type de ticket selon mail de reception
						$type_code = '';
						foreach ($to_emails as $to_email) {
							if (isset($Bimp_Ticket::$mail_typeTicket[$to_email])) {
								$type_code = $Bimp_Ticket::$mail_typeTicket[$to_email];
								break;
							} elseif (preg_match('/^(.+)\.com$/',  $to_email, $matches)) {
								if (isset($Bimp_Ticket::$mail_typeTicket[$matches[1] .'.fr'])) {
									$type_code = $Bimp_Ticket::$mail_typeTicket[$matches[1] .'.fr'];
									break;
								}
							}
						}

						// correction des object avec des accents
						$up = false;
						$s = str_replace("_", " ", mb_decode_mimeheader($Bimp_Ticket->getData('subject')));
						$Bimp_Ticket->set('subject', $s);
						if ($type_code != '') {
							$Bimp_Ticket->set('type_code', $type_code);
						}
						$Bimp_Ticket->addObjectLog(BimpTools::cleanHtml($Bimp_Ticket->getData('message')));
						$Bimp_Ticket->set("message", BimpTools::cleanHtml($msg));
						if (!$Bimp_Ticket->getData('fk_user_assign')) {
							$Bimp_Ticket->set('fk_user_assign', 0);
						}
						if (!empty($cc)) {
							$Bimp_Ticket->set('emails_cc', $cc);
						}

						$up_errors = $Bimp_Ticket->update($warnings, true);
						if (count($up_errors)) {
							$errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du ticket ' . $Bimp_Ticket->getRef());
						}

						$contact_static = new Contact($db);
						$contact_static->fetch(0, null, '', $Bimp_Ticket->getData('origin_email'));
						if ($contact_static->id > 0 and $contact_static->fk_soc == $Bimp_Ticket->getData('fk_soc')) {
							$Bimp_Ticket->add_contact($contact_static->id, 'SUPPORTCLI', 'external');
						}
						$traite = 1;
					}
				} else {
					$bimp_ticket = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', $parameters['objectemail']->id);
					if ($bimp_ticket->id > 0) {
						// on purifie le message

						$tabT = explode('lineBreakAtBeginningOfMessage', $msg);
						if (isset($tabT[1])) {
							$msg = $tabT[0] . '">';
						}

						$tabT = explode('appendonsend', $msg);
						if (isset($tabT[1])) {
							$msg = $tabT[0] . '">';
						}

						$tabT = explode('<blockquote type="cite"', $msg);
						if (isset($tabT[1])) {
							$msg = $tabT[0];
						}

//						$id_note = (int) $bdb->getValue('bimpcore_note', 'id', 'obj_name = \'Bimp_Ticket\' AND id_obj = ' . $bimp_ticket->id . ' AND content = \'' . $msg . '\'');
//						if (!$id_note) {
							$id_user_assign = (int) $bimp_ticket->getData('fk_user_assign');
							$id_soc = (int) $bimp_ticket->getData('fk_soc');
							$errors = $bimp_ticket->addNote(BimpTools::cleanHtml($msg), 20, 0, 0, $parameters['from'], ($id_soc ? 2 : 3), ($id_user_assign) ? 1 : 0, 0, $id_user_assign, 0, $id_soc, $cc);

							if (!count($errors)) {
								$traite = 1;

								if (!empty($parameters['attachments'])) {
									$destdir = $bimp_ticket->getFilesDir();
									if (!dol_is_dir($destdir)) {
										dol_mkdir($destdir);
									}

									foreach ($parameters['attachments'] as $attachment) {
										$filename = $attachment->getName();
										$content = $attachment->getContent();
										if (!file_put_contents($destdir . $filename, $content)) {
											$errors[] = 'Echec de l\'enregistrement de la pièce jointe ' . $filename;
										}
									}
								}
							}
//						}
					} else {
						$errors[] = 'Pas de ticket trouvé pour ' . str_replace(array('<', '>'), '', $headers['References']);
					}
				}
				break;

			default:
				// todo : pour tests à suppr:
//				echo '<pre>';
//				print_r($parameters);
//				echo '</pre>';
//				die('pas de traitement trouvé');
				break;
		}

		if (count($errors)) {
			BimpCore::addLog('Erreurs collecte e-mail', 4, 'bimpcore', $parameters['objectemail'], array(
				'Erreurs' => $errors
			));
			if ($user->login == 'f.martinez') {
				echo 'Err - <pre>' . print_r($errors, 1) . '</pre>';
				exit;
			}
			return -1;
		}

//		echo '<pre>';print_r($parameters['imapemail']->bodies['html']);echo '</pre>';
//		echo '<pre>';print_r($parameters['imapemail']);echo '</pre>';
//		echo '<br/><br/><br/>';
//		echo '<pre>';print_r($object);echo '</pre>';
//		echo '<pre>';print_r($action);echo '</pre>';
		return 1;
	}
}



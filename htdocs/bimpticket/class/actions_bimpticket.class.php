<?php

class ActionsBimpticket {
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
		$this->results['hookBimpticketResponse'] = "Traitement response Bimp_Ticket";
		$this->results['hookBimpticketInitial'] = "Traitement initial Bimp_Ticket";
		return 0;
	}

	function doCollectImapOneCollector($parameters, &$object, &$action, $hookmanager)
	{
		global $db;

		$traite = 0;

		$matches = array();
		preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)(\r\n|\s$)/m', $parameters['header'], $matches);
		$headers = array_combine($matches[1], $matches[2]);

		if($action == 'hookBimpticketResponse') {
			$bimp_ticket = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', $parameters['objectemail']->id);
			if ($bimp_ticket->id > 0) {
				/*
				 * on purifie le message
				 */
				$msg = $parameters['imapemail']->bodies['html'];
				$tabT = explode('lineBreakAtBeginningOfMessage', $msg);
				if (isset($tabT[1])) {
					$msg = $tabT[0] . '">';
				}
				$tabT = explode('appendonsend', $msg);
				if (isset($tabT[1])) {
					$msg = $tabT[0] . '">';
				}





				$userAttribut = $bimp_ticket->getData('fk_user_assign');
				$errors = $bimp_ticket->addNote($msg, 20, 0, 0, $parameters['from'], 2, ($userAttribut) ? 1 : 0, 0, $userAttribut);
				if (!count($errors)) {
					$traite = 1;
				} else {
					die(print_r($errors, 1));
				}
			} else {
				die('Pas de ticket trouvé pour '.str_replace(array('<', '>'), '', $headers['References']));
			}
		}




		if($action == 'hookBimpticketInitial') {
//		echo '<pre>';print_r($parameters['from'].$bimp_ticket->id);echo '</pre>';die;
			if (!$traite && isset($parameters['objectemail']) && is_a($parameters['objectemail'], 'ticket')) {
				$ticket = $parameters['objectemail'];
				$Bimp_Ticket = BimpCache::getBimpObjectInstance('bimpticket', 'Bimp_Ticket', $ticket->id);

				$contact_static = new Contact($db);
				$contact_static->fetch(0, null, '', $Bimp_Ticket->getData('origin_email'));
				if ($contact_static->id > 0 and $contact_static->fk_soc == $Bimp_Ticket->getData('fk_soc')) {
					$ticket->add_contact($contact_static->id, 'SUPPORTCLI', 'external');
				}
				$traite = 1;
//			echo $Bimp_Ticket->printData();
//			die('yes');
			}
		}

		if(!$traite){
			echo '<pre>';print_r($parameters);echo '</pre>';die('pas de traitement trouvé');
		}


//		echo '<pre>';print_r($parameters['imapemail']->bodies['html']);echo '</pre>';
//		echo '<pre>';print_r($parameters['imapemail']);echo '</pre>';
//		echo '<br/><br/><br/>';
//		echo '<pre>';print_r($object);echo '</pre>';
//		echo '<pre>';print_r($action);echo '</pre>';
		return 1;
	}
}



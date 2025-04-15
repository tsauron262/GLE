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
}



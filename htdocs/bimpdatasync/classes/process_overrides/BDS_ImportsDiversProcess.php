<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_ImportsDiversProcess extends BDSProcess
{

    public static $current_version = 6;
    public static $default_public_title = 'Imports Divers';

    // Imports AppleCare:

	public function initImportProductsAppleCare(&$data, &$errors = array())
	{
		$file = $this->getOption('csv_file', '');

		if (!$file) {
			$errors[] = 'Fichier absent';
		} else {
			$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

			if (empty($rows)) {
				$errors[] = 'Fichier vide';
			} else {
				$data['steps'] = array(
					'import' => array(
						'label'                  => 'Création des remises AppleCare',
						'on_error'               => 'continue',
						'elements'               => $rows,
						'nbElementsPerIteration' => 100
					)
				);
			}
		}
	}

	public function executeImportProductsAppleCare($step_name, &$errors = array(), $extra_data = array())
	{
		$this->setCurrentObjectData('bimpcore', 'Bimp_ProductRA');
		$prod = BimpObject::getInstance('bimpcore', 'Bimp_Product');
		foreach ($this->references as $line) {
			$this->incProcessed();

			$data = explode(';', $line);
			$ref = $data[0];

			if (isset($data[1])) {
				$value = (float) $data[1];
			} else {
				$value = 20;
			}

			if ($ref && $value) {
				$id_product = (int) $this->db->getValue('product', 'rowid', 'ref = \'APP-' . $ref . '\'');

				if (!$id_product) {
					$this->Error('Produit non trouvé', null, $ref);
				} else {
					$prod->id = $id_product;
					$id_ra = (int) $this->db->getValue('product_remise_arriere', 'id', 'id_product = ' . $id_product . ' AND type = \'applecare\'');
					if ($id_ra) {
						$this->Alert('Remise AppleCare déjà créée pour ce produit', $prod, $ref);
					} else {
						$id_ra = (int) $this->db->insert('product_remise_arriere', array(
							'id_product' => $id_product,
							'type'       => 'applecare',
							'nom'        => 'AppleCare',
							'value'      => $value,
							'active'     => 1
						), true);

						if ($id_ra <= 0) {
							$this->Error('Echec ajout AppleCare (' . $value . ' %) - ' . $this->db->err(), $prod, $ref);
						} else {
							$this->Success('Ajout AppleCare OK (' . $value . ' %)', $prod, $ref);
							$this->incCreated();
							continue;
						}
					}
				}
			} else {
				$this->Error('Ligne invalide: ' . $line);
			}
			$this->incIgnored();
		}
	}
	public function initImportClientRDC(&$data, &$errors = array())
	{
		$file = $this->getOption('csv_file2', '');
		if ($file) {
			$this->updateParameter('csv_file', $file);
			$this->updateParameter('elem_ok', 0);
		}
		else{
			$file = $this->getParam('csv_file', '');
		}
		if (!$file) {
			$errors[] = 'Fichier absent';
		} else {
			$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

			$this->data_persistante['header'] = explode(';', $rows[0]);
//			echo '<pre>'; print_r($this->data_persistante['header']); echo '</pre>';			exit;
			unset($rows[0]);

			if (empty($rows)) {
				$errors[] = 'Fichier vide';
			} else {
				$data['steps'] = array(
					'import' => array(
						'label'                  => 'Création des clients',
						'on_error'               => 'continue',
						'elements'               => array_keys($rows),
						'nbElementsPerIteration' => 100
					)
				);
			}
		}
	}


	public function traiteLn($entre){
		$entre = substr($entre,1,-1);
		return explode('","',$entre);
	}

	public function executeImportClientRDC($step_name, &$errors = array(), $extra_data = array())
	{
		$correspondance = array(
			'import_key'		=> 'Id',
			'date_der_contact'	=> 'date_der_contact',
			'date_debut_prospect' => 'date_debut_prospection',
			'name_alias'		=> 'nom_boutique',
			'nom'				=> 'nom_societe',
			'shopid'			=> 'shopid',
			'priorite_label'	=> 'priorite',
			'statut_prospect_label'	=> 'statut',
			'commentaire_statut_ko' => 'commentaire_statut',
			'date_changement_statut_rdc' =>	'date_chang_statut',
			'source_rdc'		=> 'Sources',
			'presta_source' => 'prestataire',
			'url' => 'url',

			'nom_contact' => 'nom_contact',
			'prenom_contact' => 'prenom_contact',
			'fonction_contact' => 'fonction_contact',
			'email' => 'mail_contact',
			'tel_contact' => 'tel_contact',

			'pays' => 'pays',
			'cat_maitre' => 'cat_maitre',
			'potentiel_catalogue' => 'potentiel',

			'site_concurrent' => 'site_concurrent',
			'ca_concurrent' => 'ca_concurrent',
			'lien_site_concurrent' => 'lien_site_concurrent',

			'date_echange' => 'date_echange',
			'Sujet' => 'Sujet',
			'attribution_echange' => 'attribution_echange',
			'type_echange' => 'type_echange',
			'Motif' => 'Motif',
			'Action' => 'Action',
			'pt_positif' => 'pt_positif',
			'risque_identifie' => 'risque_identifie',
			'commentaire_rdc' => 'Commentaire',
		);

		$keys = array(
			'import_key'
		);
		$correspondance2 = array_flip($correspondance);
		$ok = array();
		$file = $this->getParam('csv_file', '');
		$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$traite = $this->getParam('elem_ok', 0);
		foreach ($this->references as $id) {
			if($id <= $traite){
				$this->incIgnored();
				continue;
			}
			$line = $rows[$id];
			$this->incProcessed();
			$data = explode(';', $line);
//			$data = $this->traiteLn($line);
			if (count($data) != count($this->data_persistante['header'])) {
				$this->Error('Erreur Nb colonnes: <pre>' . print_r($data, true) . '</pre>', null, 'Ligne ' . ($id + 1));
				continue;
			}
			$ln = array_combine($this->data_persistante['header'], $data);
			if ($ln['nom_boutique']) {
				$data = $dataFiltres = array();
				if (!$ln['nom_societe'])	$ln['nom_societe'] = $ln['nom_boutique'];
				foreach($ln as $key => $value){
					$value = utf8_encode($value);
					if($correspondance2[$key]){
						if(stripos($value, '<script') !== false){
							if(stripos($ln['Name'], '<script') === false){
								$this->Error('Script détecté', null, $ln['Name'].' ligne '.($id+1));
								continue 2;
							}
							else{
								$this->Error('Script détecté', null, 'Non cachée ligne '.($id+1));
								continue 2;
							}
						}
						$data[$correspondance2[$key]] = $value;
						if(in_array($correspondance2[$key], $keys)){
							$dataFiltres[$correspondance2[$key]] = $value;
						}
					}
				}
				if ($data['date_der_contact']) {
					$data['date_der_contact'] = $this->convertDate($ln['date_der_contact']);
				}
				if ($data['date_debut_prospect']) {
					$data['date_debut_prospect'] = $this->convertDate($ln['date_debut_prospect']);
				}
				if ($data['date_changement_statut_rdc']) {
					$data['date_changement_statut_rdc'] = $this->convertDate($ln['date_changement_statut_rdc']);
				}
				if ($data['priorite_label']) {
					$nomDic = 'societe_rdc_priorities';
					$data['fk_priorite'] = $this->getBimpDict($data['priorite_label'], $nomDic);
				}
				if ($data['statut_prospect_label']) {
					$txt = str_replace('?', 'é', $data['statut_prospect_label']);
					$soc = BimpObject::getBimpObjectInstance('bimpcore', 'Bimp_Client');
					$dic = array_column($soc::$statusRdc, 'label');
					$id_pros = array_search($txt, $dic);
					if ($id === false) {
						$this->Error('Statut prospect non trouvé', null, $ln['Name'] . ' ligne ' . ($id + 1));
						continue;
					}
					$data['fk_statut_rdc'] = $id_pros;
				}
				if ($data['pays']) {
					$data['fk_pays'] = $this->getPays($data['pays'], 'label');
				} else $data['fk_pays'] = 0;
				if ($data['cat_maitre']) {
					$nomDic = 'societe_rdc_cat_maitre';
					$cats = explode('#', trim($data['cat_maitre']));
					$cats_id = array();
					foreach ($cats as $cat) {
						$cat = str_replace('?', 'é', $cat);
						$idcat = $this->getBimpDict($cat, $nomDic);
						if ($idcat === '') {
							$this->Error('Catégorie non trouvée', null, $ln['nom_boutique'] . ' cat: ' . $cat);
						} else {
							$cats_id[] = (int) $idcat;
						}
					}
					$data['fk_categorie_maitre'] = implode(',', $cats_id);
				}
				if ($data['source_rdc']) {
					$txt = str_replace('?', 'é', $data['source_rdc']);
					$nomDic = 'societe_rdc_sources';
					$data['fk_source_rdc'] = $this->getBimpDict($txt, $nomDic);
				}
				if (strlen($data['import_key']) < 3) {
					$data['import_key'] = substr('IMP_FLO_' . str_replace(" ", "",$data['name_alias']), 0, 29);
					$dataFiltres['import_key'] = substr('IMP_FLO_' . str_replace(" ", "",$data['name_alias']), 0, 29);
				}
				if ($data['url'])	{
					if(strpos($data['url'], '<br') !== false) {
						$r = str_replace(array('<br />', '<br/>', '<br>'), urldecode("%0D%0A"), $data['url']);
						$data['url'] = $r;
					}
				}
				$errors = $warnings = array();
				$obj = BimpObject::createOrUpdateBimpObject('bimpcore', 'Bimp_Client', $dataFiltres, $data, true, true, $errors, $warnings);
				foreach ($errors as $error) {
					$this->Error($error, null, $ln['nom_boutique']);
				}
				foreach ($warnings as $warning) {
					$this->Alert($warning, null, $ln['nom_boutique']);
				}
				if (!$errors && !$warnings)	{
					$fk_soc = $obj->id;
					// creation contact
					if($data['nom_contact']) {
						$dataPeople = array(
							'lastname'   => $data['nom_contact'],
							'firstname'  => $data['prenom_contact'],
							'email'      => $data['email'],
							'phone'      => $data['tel_contact'],
							'fk_soc'     => $fk_soc,
							'poste'      => $data['fonction_contact'],
							'import_key' => $data['import_key'],
							'fk_pays'    => $data['fk_pays'],
						);
						$objPeople = BimpObject::createOrUpdateBimpObject('bimpcore', 'Bimp_Contact', $dataFiltres, $dataPeople, true, true, $errors, $warnings);
						foreach ($errors as $error) {
							$this->Error($error, null, $ln['nom_boutique']);
						}
						foreach ($warnings as $warning) {
							$this->Alert($warning, null, $ln['nom_boutique']);
						}
					}
					// creation CR échange
					if ($data['date_echange'] && !$errors && !$warnings)	{
						$type = $this->db->getValue('c_actioncomm', 'id', 'libelle LIKE \'%'.trim($data['type_echange']).'\'');
						$dataAction = array(
							'fk_soc' => $fk_soc,
							'fk_user_author' => $this->user->id,
							'datep' => $this->convertDate($data['date_echange']),
							'label' => $data['Sujet'],
							'fk_user_action' => 17,
							'fk_action' => $type,
							'fk_motif_echange' => 3,
							'action_echange' => $data['Action'],
							'point_positif_echange' => $data['pt_positif'],
							'risque_identifie_echange' => $data['risque_identifie'],
							'note' => $data['Commentaire'],
							'import_key' => $data['import_key'],
						);
						$objAction = BimpObject::createOrUpdateBimpObject('bimpcore', 'Bimp_ActionComm', $dataFiltres, $dataAction, true, true, $errors, $warnings);
						foreach ($errors as $error) {
							$this->Error($error, null, $ln['nom_boutique']);
						}
						foreach ($warnings as $warning) {
							$this->Alert($warning, null, $ln['nom_boutique']);
						}
					}
				}
				$ok[] = $ln['nom_boutique'].' - '.$obj->id;
			} else {
				$this->Error('Ligne invalide: <pre>' . print_r($ln, true).'</pre>', null, $ln['Name']);
			}
			$this->incIgnored();
		}
		if(count($ok)){
			$this->Success('OK<pre>' . print_r($ok, true).'</pre>', null, '');
			if(isset($id) && $id > 0)
				$this->updateParameter('elem_ok', $id);
		}
//		die('fin');
	}

	public function initImportContactRDC(&$data, &$errors = array())
	{
		$file = $this->getOption('csv_file3', '');
		if ($file) {
			$this->updateParameter('csv_file', $file);
			$this->updateParameter('elem_ok', 0);
		}
		else{
			$file = $this->getParam('csv_file', '');
		}
		if (!$file) {
			$errors[] = 'Fichier absent';
		} else {
			$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
//			$this->data_persistante['header'] = $this->traiteLn($rows[0]);
			$this->data_persistante['header'] = explode(';', $rows[0]);
			unset($rows[0]);

			if (empty($rows)) {
				$errors[] = 'Fichier vide';
			} else {
				$data['steps'] = array(
					'import' => array(
						'label'                  => 'Création des contacts',
						'on_error'               => 'continue',
						'elements'               => array_keys($rows),
						'nbElementsPerIteration' => 200
					)
				);
			}
		}
	}


	public function executeImportContactRDC($step_name, &$errors = array(), $extra_data = array())
	{
		$correspondance = array(
			'lastname' 			=> 'LastName',
			'firstname' 		=> 'FirstName',
			'address'			=> 'MailingStreet',
			'zip'				=> 'MailingPostalCode',
			'town'				=> 'MailingCity',
			'country'			=> 'MailingCountry',
			'phone'				=> 'Phone',
			'phone_mobile'		=> 'MobilePhone',
			'email'				=> 'Email',
			'poste'				=> 'Title',
			'note_public'		=> 'Description',
			'import_key'		=> 'Id',
		);
		$keys = array(
			'import_key',
		);
		$correspondance2 = array_flip($correspondance);
		$ok = array();
		$file = $this->getParam('csv_file', '');
		$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$traite = $this->getParam('elem_ok', 0);
		$antidouble = array();
		foreach ($this->references as $id) {
			if($id <= $traite){
				$this->incIgnored();
				continue;
			}
			$line = $rows[$id];
			$this->incProcessed();
//			$data = $this->traiteLn($line);
			$data = explode(';', $line);
//			echo '<pre>'.print_r($this->data_persistante, true).'</pre>'; echo '<pre>'.print_r($data, true).'</pre>';die;
			$ln = array_combine($this->data_persistante['header'], $data);

			if (strlen($ln['LastName'])) {
				$data = $dataFiltres = array();
				$errors = $warnings = array();
				foreach($ln as $key => $value){
					$value = utf8_encode($value);
					if($correspondance2[$key]){
						if(stripos($value, '<script') !== false){
							if(stripos($ln['LastName'], '<script') === false){
								$this->Error('Script détecté', null, $ln['Name'].' ligne '.($id+1));
								continue 2;
							}
							else{
								$this->Error('Script détecté', null, 'Non cachée ligne '.($id+1));
								continue 2;
							}
						}
						$data[$correspondance2[$key]] = $value;
						if(in_array($correspondance2[$key], $keys)){
							$dataFiltres[$correspondance2[$key]] = $value;
						}
					}
				}
				// retrouver le fk_soc selon le AccountId
				$fk_soc = $this->db->getValue('societe', 'rowid', 'import_key = \''.$ln['AccountId'].'\'');
				if($fk_soc){
					$data['fk_soc'] = $fk_soc;
					$dataFiltres['fk_soc'] = $fk_soc;
				} else {
					$this->Error('Société non trouvée : '.strlen($ln['LastName']), null, $ln['AccountId'].' ligne '.($id+1));
					continue;
				}

				$obj = BimpObject::createOrUpdateBimpObject('bimpcore', 'Bimp_Contact', $dataFiltres, $data, true, true, $errors, $warnings);
				foreach ($errors as $error) {
					$this->Error($error, null, $ln['LastName'] . ' # ' . $ln['FirstName'] . ' ligne '.($id+1));
				}
				foreach ($warnings as $warning) {
					$this->Alert($warning, null, $ln['LastName'].' ligne '.($id+1));
				}
				$ok[] = $ln['LastName'].' - '.$obj->id;
//				if(!count($errors))
//					$this->Success('OK<pre>' . print_r($data, true).'</pre>', null, $ln['Name']);
			} else {
				$this->Error('Ligne invalide: <pre>' . print_r($ln, true).'</pre>', null, $ln['FirstName'] . "/" . $ln['LastName'] . "/" . $ln['Email'] .' ligne '.($id+1));
			}
			$this->incIgnored();
		}
		if(count($ok)){
			$this->Success('OK<pre>' . print_r($ok, true).'</pre>', null, '');
			if(isset($id) && $id > 0)
				$this->updateParameter('elem_ok', $id);
		}
	}


	public function initImportCasesRDC(&$data, &$errors = array())	{
		$file = $this->getOption('csv_file4', '');
		if ($file) {
			$this->updateParameter('csv_file', $file);
			$this->updateParameter('elem_ok', 0);
		}
		else{
			$file = $this->getParam('csv_file', '');
		}
		if (!$file) {
			$errors[] = 'Fichier absent';
		} else {
			$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$this->data_persistante['header'] = explode(';', $rows[0]);
//			echo '<pre>'; print_r($this->data_persistante); echo '</pre>'; die;
			unset($rows[0]);

			if (empty($rows)) {
				$errors[] = 'Fichier vide';
			} else {
				$data['steps'] = array(
					'import' => array(
						'label'                  => 'Création des tickets',
						'on_error'               => 'continue',
						'elements'               => array_keys($rows),
						'nbElementsPerIteration' => 100
					)
				);
			}
		}
	}

	public function executeImportCasesRDC($step_name, &$errors = array(), $extra_data = array())
	{
		$correspondance = array(
			'import_key'       => 'CaseNumber',        // colonne D
			'fk_soc'           => 'AccountId',        // colonne F
			'origin_mail'      => 'SuppliedEmail',   // colonne N
			'type_code'        => 'Type',            // colonne Q
			'fk_status'        => 'Status',            // colonne S
			'subject'          => 'Subject',            // colonne X
			'message'          => 'Description',        // colonne Z
			'date_close'       => 'ClosedDate',        // colonne AB
			'datec'            => 'CreatedDate',        // colonne AM
			'date_update'      => 'LastModifiedDate',// colone AO,
			'IdContact' 	   => 'ContactId',	// colonne E (ne pas mettre contact_id en key pour eviter les effets de bord par rapport à la class de base)
		);
		$keys = array(
			'import_key'
		);
		$correspondance2 = array_flip($correspondance);
		$ok = array();
		$file = $this->getParam('csv_file', '');
		$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$traite = $this->getParam('elem_ok', 0);

		foreach ($this->references as $id) {
			if ($id <= $traite) {
				$this->incIgnored();
				continue;
			}
			$line = $rows[$id];
			$data = explode(';', $line);
			$this->incProcessed();

//			echo '<pre>'; print_r($this->data_persistante['header']); echo '</pre>';
//			echo '<pre>'; print_r($data); echo '</pre>';
//			die(var_dump(count($data), count($this->data_persistante['header'])));
			if (count($data) != count($this->data_persistante['header'])) {
				$this->Error('Erreur Nb colonnes: <pre>' . print_r($data, true) . '</pre>', null, 'Ligne ' . ($id + 1));
				continue;
			}
			$ln = array_combine($this->data_persistante['header'], $data);
//			echo '<pre>'; print_r($ln); echo '</pre>'; die;
			if (strlen($ln['Subject'])) {
				$data = $dataFiltres = array();
				$errors = $warnings = array();

				foreach ($ln as $key => $value) {
//					$value = utf8_encode($value);
					if ($correspondance2[$key]) {
						if (stripos($value, '<script') !== false) {
							if (stripos($ln['Description'], '<script') === false) {
								$this->Error('Script détecté', null, $ln['Description'] . ' ligne ' . ($id + 1));
								continue 2;
							} else {
								$this->Error('Script détecté', null, 'Non cachée ligne ' . ($id + 1));
								continue 2;
							}
						}
						$data[$correspondance2[$key]] = $value;
						if (in_array($correspondance2[$key], $keys)) {
							$dataFiltres[$correspondance2[$key]] = $value;
						}
					}
				}
				$data['type_code'] = null;
				$data['datec'] = $this->convertDate($data['datec']);
				$data['date_close'] = $this->convertDate($data['date_close']);
				$data['date_update'] = $this->convertDate($data['date_update'], 1);
				if ($data['fk_status'] == 'En cours SM') $data['fk_status'] = 3; else {
					$data['fk_status'] = 8;
					$data['resolution'] = BimpTools::getDatesIntervalData($data['datec'], $data['date_close'])['full_days'];
				}


				// retrouver le fk_soc selon le AccountId
				if ($ln['AccountId'] !== '000000000000000AAA') {
					$fk_soc = $this->db->getValue('societe', 'rowid', 'import_key = \'' . $ln['AccountId'] . '\'');
					if ($fk_soc) {
						$data['fk_soc'] = $fk_soc;
					} else {
						$this->Error('Société non trouvée : ' . $ln['CaseNumber'], null, $ln['AccountId'] . ' ligne ' . ($id + 1));
						continue;
					}
				} else $data['fk_soc'] = 0;
				// retoruver le contact selon le ContactId
				if ($ln['ContactId'] !== '000000000000000AAA') {
					$fk_contact = $this->db->getValue('socpeople', 'rowid', 'import_key = \'' . $ln['ContactId'] . '\'');
					if ($fk_contact) {
						$data['fk_contact'] = $fk_contact;
					} else {
						$this->Alert('Contact non trouvé : ' . $ln['CaseNumber'], null, $ln['ContactId'] . ' ligne ' . ($id + 1));
					}
				} else $data['fk_contact'] = 0;

				// retrouver l'user selon le OwnerId
				$owner = $this->db->getValue('user', 'rowid', 'import_key = \'' . $ln['OwnerId'] . '\'');
				if ($owner) $data['fk_user_assign'] = $owner;


//				echo '<pre>'; print_r($data); echo '</pre>'; die;
				$obj = BimpCache::findBimpObjectInstance('bimpticket', 'Bimp_Ticket', $dataFiltres, true, true, false);
				$action = is_null($obj) || !BimpObject::objectLoaded($obj) ? 'create' : 'update';
				$obj = BimpObject::createOrUpdateBimpObject('bimpticket', 'Bimp_Ticket', $dataFiltres, $data, true, true, $errors, $warnings);
				foreach ($errors as $error) {
					$this->Error($error, null, $ln['CaseNumber']);
				}
				foreach ($warnings as $warning) {
					$this->Alert($warning, null, $ln['CaseNumber']);
				}
				$upValues = array(
					'import_key' => $ln['CaseNumber'],
					'datec'      => $data['datec']
				);
				if ($data['date_close']){
					$upValues['date_close'] = $data['date_close'];
				}
				if ($data['date_update']){
					$upValues['date_update'] = $data['date_update'];
				}
				$upValues['fk_statut'] = $data['fk_status'];
//				echo '<pre>'; print_r($upValues); echo '</pre>';
				$this->db->update('ticket', $upValues, 'rowid = ' . $obj->id);
				if ($data['fk_contact'])	{
					// verif si deja dans la table llx_element_contact
					$role = $this->db->getValue('c_type_contact', 'rowid', 'element = \'ticket\' AND code = \'SUPPORTCLI\'');
					if ($role) {
						$fk_element = $this->db->getValue('element_contact', 'rowid', 'element_id = ' . $obj->id . ' AND fk_c_type_contact = ' . $role . ' AND fk_socpeople = ' . $data['fk_contact']);
						if (!$fk_element) {
							// insert dans la table llx_element_contact
							$this->db->insert('element_contact', array(
								'statut'            => 4,
								'element_id'        => $obj->id,
								'fk_c_type_contact' => $role,
								'fk_socpeople'      => $data['fk_contact'],
								'datecreate'        => date('Y-m-d H:i:s')
							));
						}
					}
				}
				$ok[] = $ln['CaseNumber'] . ' - ' . $obj->id;
			}
			$this->incIgnored();
		}
		if(count($ok)){
			$this->Success('OK<pre>' . print_r($ok, true).'</pre>', null, '');
			if(isset($id) && $id > 0)
				$this->updateParameter('elem_ok', $id);
		}
	}

	public function initImportActioncommRDC(&$data, &$errors = array())
	{
		$file = $this->getOption('csv_file5', '');
		if ($file) {
			$this->updateParameter('csv_file', $file);
			$this->updateParameter('elem_ok', 0);
		}
		else{
			$file = $this->getParam('csv_file', '');
		}
		if (!$file) {
			$errors[] = 'Fichier absent';
		} else {
			$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$this->data_persistante['header'] = explode(';', $rows[0]);
//			echo '<pre>'; print_r($this->data_persistante); echo '</pre>'; die;
			unset($rows[0]);

			if (empty($rows)) {
				$errors[] = 'Fichier vide';
			} else {
				$data['steps'] = array(
					'import' => array(
						'label'                  => 'Création des actioncomm',
						'on_error'               => 'continue',
						'elements'               => array_keys($rows),
						'nbElementsPerIteration' => 100
					)
				);
			}
		}
	}

	public function executeImportActioncommRDC(&$data, &$errors = array())
	{
		$correspondance = array(
			'ref_ext'       => 'Id',
//			'fk_soc'           => 'AccountId',
			'label'           => 'Subject',
			'note'           => 'Description',
			'datec'            => 'CreatedDate',
			'datep'            => 'ActivityDate',
		);
		$keys = array(
			'ref_ext'
		);

		$correspondance2 = array_flip($correspondance);
		$ok = array();
		$file = $this->getParam('csv_file', '');
		$rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$traite = $this->getParam('elem_ok', 0);
		foreach ($this->references as $id) {
			if($id <= $traite){
				$this->incIgnored();
				continue;
			}
			$line = $rows[$id];
			$this->incProcessed();
//			$data = $this->traiteLn($line);
			$data = explode(';', $line);
			if (count($data) != count($this->data_persistante['header'])) {
//				$this->Error('Erreur Nb colonnes: <pre>' . print_r($data, true) . '</pre>', null, 'Ligne ' . ($id + 1) . '<br/>' . print_r(array(count($this->data_persistante['header']), count($data)), true) );
				continue;
			}
			$ln = array_combine($this->data_persistante['header'], $data);

			if (strlen($ln['Subject'])) {
				$data = $dataFiltres = array();
				$errors = $warnings = array();
				foreach($ln as $key => $value){
					$value = utf8_encode($value);
					if($correspondance2[$key]){
						if(stripos($value, '<script') !== false){
							$this->Error('Script détecté', null, $ln['Subject'].' ligne '.($id+1));
							continue 2;
						}
						$data[$correspondance2[$key]] = $value;
						if(in_array($correspondance2[$key], $keys)){
							$dataFiltres[$correspondance2[$key]] = $value;
						}
					}
				}
				// retrouver le fk_soc selon le AccountId
				$fk_soc = $this->db->getValue('societe', 'rowid', 'import_key = \''.$ln['AccountId'].'\'');
				if($fk_soc){
					$data['fk_soc'] = $fk_soc;
				} else {
					$this->Error('Société non trouvée : ', null, $ln['AccountId'].' ligne '.($id+1));
					continue;
				}
//				echo '<pre>' . print_r($data, true).'</pre>';
				$data['fk_action'] = 4;
				$data['datep'] = $this->convertDate($data['datep'], 0, 'Y-m-d H:i:s');
				$data['datec'] = $this->convertDate($data['datec'], 0, 'Y-m-d H:i:s');
				$data['datep2'] = $data['datep'];
				$data['datef'] = $data['datep'];
				$data['fk_user_action'] = 17;
				$data['fk_user_author'] = $this->user->id;

				$obj = BimpObject::createOrUpdateBimpObject('bimpcore', 'Bimp_ActionComm', $dataFiltres, $data, true, true, $errors, $warnings);
				foreach ($errors as $error) {
					$this->Error($error, null, ' ligne '.($id+1));
				}
				foreach ($warnings as $warning) {
					$this->Alert($warning, null, $ln['LastName'].' ligne '.($id+1));
				}
				$ok[] = ' - '.$obj->id;
//				if(!count($errors))
//					$this->Success('OK<pre>' . print_r($data, true).'</pre>', null, $ln['Name']);
			} else {
				$this->Error('Ligne invalide: <pre>' . print_r($ln, true).'</pre>', null, 'Ligne '.($id+1));
			}
			$this->incIgnored();
		}
		if(count($ok)){
			$this->Success('OK<pre>' . print_r($ok, true).'</pre>', null, '');
			if(isset($id) && $id > 0)
				$this->updateParameter('elem_ok', $id);
		}
	}

	public function convertDate($date, $seconde = false, $formatRetour = 'Y-m-d'){
		$date = substr($date, 0, 10);
		$date .= ' 00:00';
		$datetime = DateTime::createFromFormat('d/m/Y H:i' . ($seconde ? ':s' : '') , $date);
		if ($datetime) {
			return $datetime->format($formatRetour);
		}
		return null;
	}

    // Install / updates:

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process:
        BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
            'name'        => 'ImportsDivers',
            'title'       => ($title ? $title : static::$default_public_title),
            'description' => '',
            'type'        => 'import',
            'active'      => 1
                ), true, $errors, $warnings);
    }

    public static function updateProcess($id_process, $cur_version, &$warnings = array())
    {
        $errors = array();

		if ($cur_version < 2) {
			// Options "Fichier CSV":
			BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $id_process,
				'label'         => 'Fichier CSV',
				'name'          => 'csv_file',
				'info'          => '',
				'type'          => 'file',
				'default_value' => '',
				'required'      => 1
			), true, $errors, $warnings);

			// Opération "Import Remises AppleCare produits":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Import Remises AppleCare produits',
				'name'          => 'importProductsAppleCare',
				'description'   => '',
				'warning'       => 'Fichier CSV : ref_produit;pourcentage_remise',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 15
			), true, $errors, $warnings);

			if (BimpObject::objectLoaded($op)) {
				$errors = array_merge($errors, $op->addOptions(array('csv_file')));
			}
		}

		if ($cur_version < 3) {
		// Opération "Import client rdc":
			BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $id_process,
				'label'         => 'Fichier CSV',
				'name'          => 'csv_file2',
				'info'          => '',
				'type'          => 'file',
				'default_value' => '',
				'required'      => 0
			), true, $errors, $warnings);
		$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
			'id_process'    => (int) $id_process,
			'title'         => 'Import Client RDC',
			'name'          => 'importClientRDC',
			'description'   => '',
			'warning'       => 'Fichier CSV : ',
			'active'        => 1,
			'use_report'    => 1,
			'reports_delay' => 15
		), true, $errors, $warnings);
			BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
				'id_process' => (int) $id_process,
				'name'       => 'csv_file',
				'label'      => 'Fichier CSV',
				'value'      => ''
			), true, $warnings, $warnings);

			BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
				'id_process' => (int) $id_process,
				'name'       => 'elem_ok',
				'label'      => 'Id traité',
				'value'      => 0
			), true, $warnings, $warnings);

		if (BimpObject::objectLoaded($op)) {
			$errors = array_merge($errors, $op->addOptions(array('csv_file2')));
		}
	}
		if ($cur_version < 4) {
			// Opération "Import contact rdc":
			BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $id_process,
				'label'         => 'Fichier CSV',
				'name'          => 'csv_file3',
				'info'          => '',
				'type'          => 'file',
				'default_value' => '',
				'required'      => 0
			), true, $errors, $warnings);
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Import Contact RDC',
				'name'          => 'importContactRDC',
				'description'   => '',
				'warning'       => 'Fichier CSV : ',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 15
			), true, $errors, $warnings);

			if (BimpObject::objectLoaded($op)) {
				$errors = array_merge($errors, $op->addOptions(array('csv_file3')));
			}
		}

		if ($cur_version < 5) {
			// Opération "Import cases rdc -> transformation en ticket":
			BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $id_process,
				'label'         => 'Fichier CSV',
				'name'          => 'csv_file4',
				'info'          => '',
				'type'          => 'file',
				'default_value' => '',
				'required'      => 0
			), true, $errors, $warnings);
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Import Cases RDC -> Ticket',
				'name'          => 'importCasesRDC',
				'description'   => '',
				'warning'       => 'Fichier CSV : ',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 15
			), true, $errors, $warnings);
		}

		if ($cur_version < 6) {
			// Opération "Import activitées saleforce -> transformation en actioncomm":
			BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $id_process,
				'label'         => 'Fichier CSV',
				'name'          => 'csv_file5',
				'info'          => '',
				'type'          => 'file',
				'default_value' => '',
				'required'      => 0
			), true, $errors, $warnings);
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Import Activités -> Actioncomm',
				'name'          => 'importActioncommRDC',
				'description'   => '',
				'warning'       => 'Fichier CSV : ',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 15
			), true, $errors, $warnings);
		}

        return $errors;
    }

	public function getBimpDict($label, $nomDic)
	{
		$res = $this->db->getRows(
			'bimpcore_dictionnary_value as dv',
			'dv.label LIKE "%' . trim($label) . '%" AND d.code = "' . $nomDic . '"',
			null,
			'array',
			array('dv.id'),
			null,
			null,
			array('d' => array(
				'table' => 'bimpcore_dictionnary',
				'on'    => 'd.id = dv.id_dict'
			))
		);
		return $res[0]['id'] ?? '';
	}

	public function getPays($needle, $field)	{
		return $this->db->getValue('c_country', 'rowid', $field . ' = \'' . trim($needle) . '\'');
	}
}

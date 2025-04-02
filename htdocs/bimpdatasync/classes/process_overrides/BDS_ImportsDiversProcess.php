<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_ImportsDiversProcess extends BDSProcess
{

    public static $current_version = 4;
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
			$this->data_persistante['header'] = $this->traiteLn($rows[0]);
			unset($rows[0]);

			if (empty($rows)) {
				$errors[] = 'Fichier vide';
			} else {
				$data['steps'] = array(
					'import' => array(
						'label'                  => 'Création des clients',
						'on_error'               => 'continue',
						'elements'               => array_keys($rows),
						'nbElementsPerIteration' => 20
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
			'nom'				=> 'Name',
			'email' 			=> 'E_mail_siege_social__c',
			'address'			=> 'BillingStreet',
			'zip'				=> 'BillingPostalCode',
			'town'				=> 'BillingCity',
			'phone'				=> 'Phone',
			'note_public'		=> 'Description',
			'siren'				=> 'N_SIREN__c',
			'shopid'			=> 'ShopId__c',
			'tva_intra'			=> 'N_TVA_IC__c',
			'import_key'		=> 'Id',
		);
		$keys = array(
			'nom',
			'siren'
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
			$data = $this->traiteLn($line);
//			echo '<pre>'.print_r($this->data_persistante, true).'</pre>'; echo '<pre>'.print_r($data, true).'</pre>';die;
			$ln = array_combine($this->data_persistante['header'], $data);


			if ($ln['Name']) {
				$data = $dataFiltres = array();
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
				$errors = $warnings = array();
//				echo '<pre>';print_r($data);
				$obj = BimpObject::createOrUpdateBimpObject('bimpcore', 'Bimp_Client', $dataFiltres, $data, true, true, $errors, $warnings);
				foreach ($errors as $error) {
					$this->Error($error, null, $ln['Name']);
				}
				foreach ($warnings as $warning) {
					$this->Alert($warning, null, $ln['Name']);
				}
				$ok[] = $ln['Name'].' - '.$obj->id;
//				if(!count($errors))
//					$this->Success('OK<pre>' . print_r($data, true).'</pre>', null, $ln['Name']);
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
			$this->data_persistante['header'] = $this->traiteLn($rows[0]);
			unset($rows[0]);

			if (empty($rows)) {
				$errors[] = 'Fichier vide';
			} else {
				$data['steps'] = array(
					'import' => array(
						'label'                  => 'Création des contacts',
						'on_error'               => 'continue',
						'elements'               => array_keys($rows),
						'nbElementsPerIteration' => 20
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
			'phone'				=> 'Phone',
			'phone_mobile'		=> 'MobilePhone',
			'email'				=> 'Email',
			'poste'				=> 'Title',
			'import_key'		=> 'Id',
		);
		$keys = array(
			'fk_soc',
			'lastname',
			'firstname'
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
			$data = $this->traiteLn($line);
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
					// retrouver le fk_soc selon le AccountId
					$fk_soc = $this->db->getValue('societe', 'rowid', 'import_key = \''.$ln['AccountId'].'\'');
					if($fk_soc){
						$data['fk_soc'] = $fk_soc;
						$dataFiltres['fk_soc'] = $fk_soc;
					} else {
						$this->Error('Société non trouvée', null, $ln['AccountId'].' ligne '.($id+1));
						continue 2;
					}
				}

				$obj = BimpObject::createOrUpdateBimpObject('bimpcore', 'Bimp_Contact', $dataFiltres, $data, true, true, $errors, $warnings);
				foreach ($errors as $error) {
					$this->Error($error, null, $ln['LastName'] . ' # ' . $ln['FirstName'] . ' ligne '.($id+1));
				}
				foreach ($warnings as $warning) {
					$this->Alert($warning, null, $ln['LastName'].' ligne '.($id+1));
				}
				$ok[] = $ln['Name'].' - '.$obj->id;
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

        return $errors;
    }
}

<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_VerifsProcess extends BDSProcess
{

	public static $current_version = 15;
	public static $default_public_title = 'Vérifications et corrections diverses';

	// Vérifs marges factures :

	public function initCheckFacsMargin(&$data, &$errors = array())
	{
		$date_from = $this->getOption('date_from', '');
		$date_to = $this->getOption('date_to', '');
		$nbElementsPerIteration = $this->getOption('nb_elements_per_iterations', 100);

		if (!preg_match('/^[0-9]+$/', $nbElementsPerIteration) || !(int) $nbElementsPerIteration) {
			$errors[] = 'Le nombre d\'élements par itération doit être un nombre entier positif';
		}

		if ($date_from && $date_to && $date_from > $date_to) {
			$errors[] = 'La date de début doit être inférieure à la date de fin';
		}

		if (!count($errors)) {
			$where = '';
			if ($date_from) {
				$where .= 'datec >= \'' . $date_from . ' 00:00:00\'';
			}
			if ($date_to) {
				$where .= ($where ? ' AND ' : '') . 'datec <= \'' . $date_to . ' 23:59:59\'';
			}

			$rows = $this->db->getRows('facture', $where, null, 'array', array('rowid'), 'rowid', 'desc');
			$elements = array();

			if (is_array($rows)) {
				foreach ($rows as $r) {
					$elements[] = (int) $r['rowid'];
				}
			}

			if (empty($elements)) {
				$errors[] = 'Aucune facture a traiter trouvée';
			} else {
				$data['steps'] = array(
					'check_margins' => array(
						'label'                  => 'Vérifications des marges',
						'on_error'               => 'continue',
						'elements'               => $elements,
						'nbElementsPerIteration' => (int) $nbElementsPerIteration
					)
				);
			}
		}
	}

	public function executeCheckFacsMargin($step_name, &$errors = array(), $extra_data = array())
	{
		$result = array();

		switch ($step_name) {
			case 'check_margins':
				if (!empty($this->references)) {
					$this->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');
					foreach ($this->references as $id_fac) {
						$this->incProcessed();
						$fac_errors = array();
						$fac_infos = array();
						$fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

						if (BimpObject::objectLoaded($fac)) {
							$fac_errors = $fac->checkMargin(true, true, $fac_infos);
							$fac_errors = BimpTools::merge_array($fac_errors, $fac->checkTotalAchat(false));
						} else {
							$fac_errors[] = 'Fac #' . $id_fac . ' non trouvée';
						}

						if (count($fac_errors)) {
							$this->incIgnored();
							$this->Error(BimpTools::getMsgFromArray($fac_errors, 'Fac #' . $id_fac), $fac, $id_fac);
						} elseif (count($fac_infos)) {
							$this->incUpdated();
							$this->Success(BimpTools::getMsgFromArray($fac_infos), $fac, $id_fac);
						}
					}
				}
				break;
		}

		return $result;
	}

	// Vérifs marges commandes :

	public function initCheckCommandesMargin(&$data, &$errors = array())
	{
		$date_from = $this->getOption('date_from', '');
		$date_to = $this->getOption('date_to', '');
		$nbElementsPerIteration = $this->getOption('nb_elements_per_iterations', 100);

		if (!preg_match('/^[0-9]+$/', $nbElementsPerIteration) || !(int) $nbElementsPerIteration) {
			$errors[] = 'Le nombre d\'élements par itération doit être un nombre entier positif';
		}

		if ($date_from && $date_to && $date_from > $date_to) {
			$errors[] = 'La date de début doit être inférieure à la date de fin';
		}

		if (!count($errors)) {
//            $where = 'tms > \'2023-02-03 10:16:35\' AND (tms < \'2024-05-21 00:00:00\' OR date_creation > \'2023-01-01 00:00:00\')';

			$where = '';

			if ($date_from || $date_to) {
				if ($date_from) {
					$where .= 'tms >= \'' . $date_from . ' 00:00:00\'';
				}
				if ($date_to) {
					$where .= ($where ? ' AND ' : '') . 'tms <= \'' . $date_to . ' 23:59:59\'';
				}
			} else {
				$tms = BimpCore::getConf('commandes_marges_last_check_tms', '');
				if ($tms) {
					$where .= 'tms > \'' . $tms . '\'';
				}
			}

			$this->debug_content .= '<br/>WHERE : ' . $where . '<br/>';

			$rows = $this->db->getRows('commande', $where, null, 'array', array('rowid'), 'tms', 'asc');
			$elements = array();

			if (is_array($rows)) {
				foreach ($rows as $r) {
					$elements[] = (int) $r['rowid'];
				}
			}

			if (empty($elements)) {
				$errors[] = 'Aucune commande a traiter trouvée';
			} else {
				$data['steps'] = array(
					'check_margins' => array(
						'label'                  => 'Vérifications des marges',
						'on_error'               => 'continue',
						'elements'               => $elements,
						'nbElementsPerIteration' => (int) $nbElementsPerIteration
					)
				);
			}
		}
	}

	public function executeCheckCommandesMargin($step_name, &$errors = array(), $extra_data = array())
	{
		$result = array();
		$bdb = BimpCache::getBdb();

		switch ($step_name) {
			case 'check_margins':
				if (!empty($this->references)) {
					$this->setCurrentObjectData('bimpcommercial', 'Bimp_Commande');
					$max_tms = '';
					foreach ($this->references as $id_commande) {
						$this->incProcessed();
						$cmde_errors = array();
						$cmde_info = '';
						$cmde = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

						if (BimpObject::objectLoaded($cmde)) {
							$cmde_errors = $cmde->checkMarge($cmde_info);
							if ($bdb->update('commande', array('tms' => date('Y-m-d H:i:s')), 'rowid = ' . $id_commande) <= 0) {
								$cmde_errors[] = 'Err màj tms ' . $bdb->err();
							}
						} else {
							$cmde_errors[] = 'Commande #' . $id_commande . ' non trouvée';
						}

						if (count($cmde_errors)) {
							$this->incIgnored();
							$this->Error(BimpTools::getMsgFromArray($cmde_errors, 'Commande #' . $id_commande), $cmde, $id_commande);
						} elseif ($cmde_info) {
							$this->incUpdated();
							$this->Success($cmde_info, $cmde, $id_commande);
						}

						$tms = $cmde->dol_object->date_modification;

						if (!$max_tms || $tms > $max_tms) {
							$max_tms = $tms;
						}
					}

					$cur_tms = BimpCore::getConf('commandes_marges_last_check_tms', '');
					$new_tms = date('Y-m-d H:i:s', $max_tms);
					if (!$cur_tms || $new_tms > $cur_tms) {
						BimpCore::setConf('commandes_marges_last_check_tms', $new_tms);
					}
				}
				break;
		}

		return $result;
	}

	// Vérifs marges propal :

	public function initCheckPropalsMargin(&$data, &$errors = array())
	{
		$date_from = $this->getOption('date_from', '');
		$date_to = $this->getOption('date_to', '');
		$nbElementsPerIteration = $this->getOption('nb_elements_per_iterations', 100);

		if (!preg_match('/^[0-9]+$/', $nbElementsPerIteration) || !(int) $nbElementsPerIteration) {
			$errors[] = 'Le nombre d\'élements par itération doit être un nombre entier positif';
		}

		if ($date_from && $date_to && $date_from > $date_to) {
			$errors[] = 'La date de début doit être inférieure à la date de fin';
		}

		if (!count($errors)) {
//            $where = 'tms > \'2023-02-03 10:16:35\' AND (tms < \'2024-05-21 00:00:00\' OR date_creation > \'2023-01-01 00:00:00\')';

			$where = '';

			if ($date_from || $date_to) {
				if ($date_from) {
					$where .= 'tms >= \'' . $date_from . ' 00:00:00\'';
				}
				if ($date_to) {
					$where .= ($where ? ' AND ' : '') . 'tms <= \'' . $date_to . ' 23:59:59\'';
				}
			} else {
				$tms = BimpCore::getConf('propals_marges_last_check_tms', '');
				if ($tms) {
					$where .= 'tms > \'' . $tms . '\'';
				}
			}
			$where .= ($where ? ' AND ' : '') . 'entity IN (' . getEntity('propal') . ')';

			$this->debug_content .= '<br/>WHERE : ' . $where . '<br/>';

			$rows = $this->db->getRows('propal', $where, null, 'array', array('rowid'), 'tms', 'asc');
			$elements = array();

			if (is_array($rows)) {
				foreach ($rows as $r) {
					$elements[] = (int) $r['rowid'];
				}
			} else {
				$errors[] = $this->db->err();
			}

			if (empty($elements)) {
				$errors[] = 'Aucune propal a traiter trouvée';
			} else {
				$data['steps'] = array(
					'check_margins' => array(
						'label'                  => 'Vérifications des marges',
						'on_error'               => 'continue',
						'elements'               => $elements,
						'nbElementsPerIteration' => (int) $nbElementsPerIteration
					)
				);
			}
		}
	}

	public function executeCheckPropalsMargin($step_name, &$errors = array(), $extra_data = array())
	{
		$result = array();
		$bdb = BimpCache::getBdb();

		switch ($step_name) {
			case 'check_margins':
				if (!empty($this->references)) {
					$this->setCurrentObjectData('bimpcommercial', 'Bimp_Propal');
					$max_tms = '';
					foreach ($this->references as $id_propal) {
						$this->incProcessed();
						$cmde_errors = array();
						$cmde_info = '';
						$cmde = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_propal);

						if (BimpObject::objectLoaded($cmde)) {
							$cmde_errors = $cmde->checkMarge($cmde_info);
							//sinon on tourne en boucle
//							if ($bdb->update('propal', array('tms' => date('Y-m-d H:i:s')), 'rowid = ' . $id_propal) <= 0) {
//								$cmde_errors[] = 'Err màj tms ' . $bdb->err();
//							}
						} else {
							$cmde_errors[] = 'Propal #' . $id_propal . ' non trouvée';
						}

						if (count($cmde_errors)) {
							$this->incIgnored();
							$this->Error(BimpTools::getMsgFromArray($cmde_errors, 'Propal #' . $id_propal), $cmde, $id_propal);
						} elseif ($cmde_info) {
							$this->incUpdated();
							$this->Success($cmde_info, $cmde, $id_propal);
						}

						$tms = $cmde->dol_object->date_modification;

						if (!$max_tms || $tms > $max_tms) {
							$max_tms = $tms;
						}
					}

					$cur_tms = BimpCore::getConf('propals_marges_last_check_tms', '');
					$new_tms = date('Y-m-d H:i:s', $max_tms);
					if (!$cur_tms || $new_tms > $cur_tms) {
						BimpCore::setConf('propals_marges_last_check_tms', $new_tms);
					}
				}
				break;
		}

		return $result;
	}

	// Vérifs Restes à payer factures:

	public function initCheckFacsRtp(&$data, &$errors = array())
	{
		$date_from = $this->getOption('date_from', '');
		$date_to = $this->getOption('date_to', '');
		$nbElementsPerIteration = $this->getOption('nb_elements_per_iterations', 100);
		$not_classified_only = $this->getOption('not_classified_only', 1);
		$zero_only = $this->getOption('rtp_zero_only', 0);

		if (!preg_match('/^[0-9]+$/', $nbElementsPerIteration) || !(int) $nbElementsPerIteration) {
			$errors[] = 'Le nombre d\'élements par itération doit être un nombre entier positif';
		}

		if ($date_from && $date_to && $date_from > $date_to) {
			$errors[] = 'La date de début doit être inférieure à la date de fin';
		}

		if (!count($errors)) {
			$where = 'fk_statut > 0';

			if ($date_from) {
				$where .= ' AND date_valid >= \'' . $date_from . ' 00:00:00\'';
			}
			if ($date_to) {
				$where .= ' AND date_valid <= \'' . $date_to . ' 23:59:59\'';
			}

			if ($not_classified_only) {
				$where .= ' AND paye = 0';
			}

			if ($zero_only) {
				$where .= ' and remain_to_pay = 0';
			}

			$rows = $this->db->getRows('facture', $where, null, 'array', array('rowid'), 'rowid', 'desc');
			$elements = array();

			if (is_array($rows)) {
				foreach ($rows as $r) {
					$elements[] = (int) $r['rowid'];
				}
			}

			if (empty($elements)) {
				$errors[] = 'Aucune facture a traiter trouvée';
			} else {
				$data['steps'] = array(
					'check_rtp' => array(
						'label'                  => 'Vérifications des restes à payer',
						'on_error'               => 'continue',
						'elements'               => $elements,
						'nbElementsPerIteration' => (int) $nbElementsPerIteration
					)
				);
			}
		}
	}

	public function executeCheckFacsRtp($step_name, &$errors = array(), $extra_data = array())
	{
		$result = array();

		switch ($step_name) {
			case 'check_rtp':
				if (!empty($this->references)) {
					$this->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');
					foreach ($this->references as $id_fac) {
						$this->incProcessed();
						$fac_errors = array();
						$fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

						if (BimpObject::objectLoaded($fac)) {
							$fac_errors = $fac->checkIsPaid(false);
						} else {
							$fac_errors[] = 'Fac #' . $id_fac . ' non trouvée';
						}

						if (count($fac_errors)) {
							$this->incIgnored();
							$this->Error(BimpTools::getMsgFromArray($fac_errors, 'Fac #' . $id_fac), $fac, $id_fac);
						} else {
							$this->incUpdated();
							$this->Success('Vérif Reste à payer OK', $fac, $id_fac);
						}
					}
				}
				break;
		}

		return $result;
	}

	// Vérifs réceptions:

	public function initCheckReceptions(&$data, &$errors = array())
	{
		$date_from = $this->getOption('date_from', '');
		$date_to = $this->getOption('date_to', '');
		$nbElementsPerIteration = $this->getOption('nb_elements_per_iterations', 100);
		$include_serialized = (int) $this->getOption('include_serialized', 0);

		if (!preg_match('/^[0-9]+$/', $nbElementsPerIteration) || !(int) $nbElementsPerIteration) {
			$errors[] = 'Le nombre d\'élements par itération doit être un nombre entier positif';
		}

		if ($date_from && $date_to && $date_from > $date_to) {
			$errors[] = 'La date de début doit être inférieure à la date de fin';
		}

		if (!count($errors)) {
			$this->db->execute('TRUNCATE TABLE llx_stock_mouvement_reverse;');
			$this->db->execute('INSERT INTO llx_stock_mouvement_reverse (SELECT *, REVERSE(inventorycode) as inventorycodereverse FROM llx_stock_mouvement WHERE datem > \'2021-01-01 00:00:00\' AND (inventorycode LIKE \'CMDF%\' OR inventorycode LIKE \'ANNUL$_CMDF%\' ESCAPE \'$\'));'); //. ($date_from ? ' WHERE datem >= \'' . $date_from . ' 00:00:00\'' : '') . ');');
//
			$elements = array();

			// Recherche par réception existantes:
			$where = 'status = 1';
			if ($date_from) {
				$where .= ' AND date_received >= \'' . $date_from . ' 00:00:00\'';
			}
			if ($date_to) {
				$where .= ' AND date_received <= \'' . $date_from . ' 23:59:59\'';
			}
			$rows = $this->db->getRows('bl_commande_fourn_reception', $where, null, 'array', array('id'), 'id', 'asc');
			if (is_array($rows)) {
				$elements = array();
				foreach ($rows as $r) {
					$elements[] = (int) $r['id'];
				}
			} else {
				$errors[] = $this->db->err();
			}

			// Recherche par codes mouvements:
			$where = 'a.inventorycode LIKE \'%$_RECEP%\' ESCAPE \'$\'';
			if (!$include_serialized) {
				$where .= ' AND p.serialisable = 0';
			}
			if ($date_from) {
				$where .= ' AND a.datem >= \'' . $date_from . ' 00:00:00\'';
			}

			if ($date_to) {
				$where .= ' AND a.datem <= \'' . $date_from . ' 23:59:59\'';
			}
			$rows = $this->db->getRows('stock_mouvement a', $where, null, 'array', array('a.rowid', 'a.inventorycode'), null, null, array(
				array(
					'alias' => 'p',
					'table' => 'product_extrafields',
					'on'    => 'p.fk_object = a.fk_product'
				)
			));

			if (is_array($rows)) {
				foreach ($rows as $r) {
					if (preg_match('/^(ANNUL_)?CMDF(\d+)_LN(\d+)_RECEP(\d+)$/', $r['inventorycode'], $matches)) {
						$id_reception = (int) $matches[4];
						if ($id_reception && !in_array($id_reception, $elements)) {
							$elements[] = $id_reception;
						}
					} else {
						$this->Alert('Code inventaire incorrect', null, $r['inventorycode']);
					}
				}
			} else {
				$errors[] = $this->db->err();
			}

			if (empty($elements)) {
				$errors[] = 'Aucune réception a traiter trouvée';
			} else {
				$data['steps'] = array(
					'check_receptions' => array(
						'label'                  => 'Vérifications des réceptions',
						'on_error'               => 'continue',
						'elements'               => $elements,
						'nbElementsPerIteration' => (int) $nbElementsPerIteration
					)
				);
			}
		}
	}

	public function executeCheckReceptions($step_name, &$errors = array(), $extra_data = array())
	{
		if (!empty($this->references)) {
			$include_serialized = (int) $this->getOption('include_serialized', 0);
			$prod_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
			$this->setCurrentObject(BimpObject::getInstance('bimplogistique', 'BL_CommandeFournReception'));

			$entrepots = BimpCache::getEntrepotsArray(false, false, true);

			foreach ($this->references as $id_r) {
				$reception_status = $this->db->getValue('bl_commande_fourn_reception', 'status', 'id = ' . (int) $id_r);
				if (is_null($reception_status)) {
					$reception_status_label = 'Supprimée';
				} else {
					$reception_status = (int) $reception_status;
					$reception_status_label = BimpTools::getArrayValueFromPath(BL_CommandeFournReception::$status_list[$reception_status], 'label', 'Statut #' . $reception_status);
				}
//                $where = '(inventorycode LIKE \'%$_RECEP' . $id_r . '\' ESCAPE \'$\' OR inventorycode LIKE \'%$_RECEP$_' . $id_r . '\' ESCAPE \'$\')';
//                $mvts = $this->db->getRows('stock_mouvement a', $where, null, 'array', array('a.*', 'p.serialisable'), null, null, array(
//                    array(
//                        'alias' => 'p',
//                        'table' => 'product_extrafields',
//                        'on'    => 'p.fk_object = a.fk_product'
//                    )
//                ));
//                $this->DebugData($mvts, 'MVTS 1');

				$where = '(inventorycodereverse LIKE REVERSE(\'%_$RECEP' . $id_r . '\') ESCAPE \'$\' OR inventorycodereverse LIKE REVERSE(\'%_$RECEP_$' . $id_r . '\') ESCAPE \'$\')';
				$mvts = $this->db->getRows('stock_mouvement_reverse a', $where, null, 'array', array('a.*', 'p.serialisable'), null, null, array(
					array(
						'alias' => 'p',
						'table' => 'product_extrafields',
						'on'    => 'p.fk_object = a.fk_product'
					)
				));

//                $this->DebugData($mvts, 'MVTS 2');

				if (is_array($mvts)) {
					$lines = array();

					// Trie par ligne:
					foreach ($mvts as $m) {
						if (!$include_serialized && (int) $m['serialisable']) {
							continue;
						}
						$prod_instance->id = (int) $m['fk_product'];

						if ($m['inventorycode']) {
							if (preg_match('/^(ANNUL_)?CMDF(\d+)_LN(\d+)_RECEP' . $id_r . '$/', $m['inventorycode'], $matches)) {
								$id_cmd = (int) $matches[2];
								$id_line = (int) $matches[3];

								if (!isset($lines[$id_cmd])) {
									$lines[$id_cmd] = array();
								}
								if (!isset($lines[$id_cmd][$id_line])) {
									$lines[$id_cmd][$id_line] = array(
										'recep'      => array(),
										'annul'      => array(),
										'id_prod'    => (int) $m['fk_product'],
										'receptions' => array()
									);

									$receptions = $this->db->getValue('bimp_commande_fourn_line', 'receptions', 'id = ' . $id_line);
									if ($receptions) {
										$lines[$id_cmd][$id_line]['receptions'] = json_decode($receptions, 1);
									}
								}

								if ($matches[1]) {
									$lines[$id_cmd][$id_line]['annul'][] = $m;
								} else {
									$lines[$id_cmd][$id_line]['recep'][] = $m;
								}
							} else {
								$this->Alert('RECEP #' . $id_r . ' - MVT #' . $m['rowid'] . ': CODE INCORRECT: ' . $m['inventorycode'], $prod_instance);
							}
						} else {
							$this->Alert('RECEP #' . $id_r . ' - MVT #' . $m['rowid'] . ': AUCUN CODE', $prod_instance);
						}
					}

					if (!empty($lines)) {
						$this->incProcessed();
						foreach ($lines as $id_comm => $comm_lines) {
							foreach ($comm_lines as $id_line => $line) {
								$reception_data = BimpTools::getArrayValueFromPath($line, 'receptions/' . $id_r, array('received' => 0, 'qty' => 0));
								$qtyAttendu = (isset($reception_data['received']) && $reception_data['received'] ? $reception_data['qty'] : 0);
								$qtyMvt = 0;
								foreach ($line['recep'] as $m) {
									$qtyMvt += $m['value'];
								}
								foreach ($line['annul'] as $m) {
									$qtyMvt += $m['value']; //car deja inversé
								}
								$diffQty = $qtyAttendu - $qtyMvt;
								$diff = count($line['recep']) - count($line['annul']);
								if ($diffQty != 0) {
									$prod_instance->id = (int) $line['id_prod'];
									$title = '<a target="_blank" href="' . DOL_URL_ROOT . '/bimplogistique/index.php?fc=commandeFourn&id=' . $id_comm . '">';
									$title .= (isset($entrepots[(int) $m['fk_entrepot']]) ? $entrepots[(int) $m['fk_entrepot']] : 'Entrepôt #' . $m['fk_entrepot']) . ' - ';
									$title .= 'Réception #' . $id_r . ' (' . $reception_status_label . ')';
									$title .= '</a> : ';

									$html = '<br/><br/>';
									$html .= 'Attendu : <b>' . $qtyAttendu . '</b> - Mouvements : <b>' . $qtyMvt . '</b><br/>';

									foreach (array('recep' => 'réception(s)', 'annul' => 'annulation(s)') as $code => $label) {
										if (count($line[$code])) {
											$html .= '<b>' . count($line[$code]) . ' ' . $label . '</b><br/>';
											foreach ($line[$code] as $m) {
												$html .= '   - <b>' . ((int) $m['type_mouvement'] ? 'Sortie' : 'Entrée') . '</b> : ' . $m['value'] . ' - Mvt #' . $m['rowid'] . '<br/>';
											}
										}
									}
									$html .= '<a target="_blank" href="' . DOL_URL_ROOT . '/bimpcore/index.php?fc=product&id=' . $line['id_prod'] . '&navtab-maintabs=stock&navtab-stocks_view=stocks_mvts_tab">Mouvements produit ' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

									if (!$diff) {
										$this->Alert($title . 'tous les mouvements annulés.' . $html, $prod_instance, $m['inventorycode']);
									} else {
										$this->Error($title . 'incohérence trouvée.' . $html, $prod_instance, $m['inventorycode']);
									}
								}
							}
						}
					}
				} else {
					$this->Error($this->db->err());
				}
			}
		}
	}

	// Vérifs expéditions:

	public function initCheckShipments(&$data, &$errors = array())
	{
		$date_from = $this->getOption('date_from', '');
		$date_to = $this->getOption('date_to', '');
		$nbElementsPerIteration = $this->getOption('nb_elements_per_iterations', 100);
		if (!preg_match('/^[0-9]+$/', $nbElementsPerIteration) || !(int) $nbElementsPerIteration) {
			$errors[] = 'Le nombre d\'élements par itération doit être un nombre entier positif';
		}

		if ($date_from && $date_to && $date_from > $date_to) {
			$errors[] = 'La date de début doit être inférieure à la date de fin';
		}

		if (!count($errors)) {

			$elements = array();

			// Recherche par codes mouvements:
			$where = 'a.inventorycode LIKE \'CO%$_EXP%\' ESCAPE \'$\'';
			$where .= ' AND p.serialisable = 0';
			if ($date_from) {
				$where .= ' AND a.datem >= \'' . $date_from . ' 00:00:00\'';
			}

			if ($date_to) {
				$where .= ' AND a.datem <= \'' . $date_from . ' 23:59:59\'';
			}
			$rows = $this->db->getRows('stock_mouvement a', $where, null, 'array', array('a.rowid', 'a.inventorycode'), null, null, array(
				array(
					'alias' => 'p',
					'table' => 'product_extrafields',
					'on'    => 'p.fk_object = a.fk_product'
				)
			));

			if (is_array($rows)) {
				$this->debug_content .= 'Nombre de mouvements à traiter: ' . count($rows) . '<br/>';
				foreach ($rows as $r) {
					if (preg_match('/^CO(\d+)_EXP(\d+)(_ANNUL)?$/', $r['inventorycode'], $matches)) {
						$id_cmd = (int) $matches[1];
						$id_shipment = (int) $matches[2];
						if ($id_cmd && $id_shipment && !in_array($id_cmd . '_' . $id_shipment, $elements)) {
							$elements[] = $id_cmd . '_' . $id_shipment;
						}
					} else {
						$this->Alert('Code inventaire incorrect', null, $r['inventorycode']);
					}
				}
			} else {
				$errors[] = $this->db->err();
			}

			if (empty($elements)) {
				$errors[] = 'Aucune expédition a traiter trouvée';
			} else {
				$data['steps'] = array(
					'check_shipments' => array(
						'label'                  => 'Vérifications des expéditions',
						'on_error'               => 'continue',
						'elements'               => $elements,
						'nbElementsPerIteration' => (int) $nbElementsPerIteration
					)
				);
			}
		}
	}

	public function executeCheckShipments($step_name, &$errors = array(), $extra_data = array())
	{
		if (!empty($this->references)) {
			$prod_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
			$commande_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
			$this->setCurrentObject(BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment'));

			$entrepots = BimpCache::getEntrepotsArray(false, false, true);

			// Trie par commande et expéditions:
			$commandes = array();
			foreach ($this->references as $ref) {
				if (preg_match('/^(\d+)_(\d+)$/', $ref, $matches)) {
					$id_cmd = (int) $matches[1];
					$id_exp = (int) $matches[2];
				}

				if (!isset($commandes[$id_cmd])) {
					$commandes[$id_cmd] = array(
						'prods' => array(),
						'exps'  => array()
					);
				}

				if (!in_array($id_exp, $commandes[$id_cmd]['exps'])) {
					$commandes[$id_cmd]['exps'][] = $id_exp;
				}
			}

			// Traitement par commande:
			foreach ($commandes as $id_cmd => $cmd_data) {
				$commande_instance->id = $id_cmd;
				// Calcul qty attendues par produits:
				$lines = $this->db->getRows('commandedet det', 'det.fk_commande = ' . $id_cmd . ' AND det.fk_product > 0 AND pef.serialisable = 0 AND p.fk_product_type = 0', null, 'array', array('l.id', 'l.shipments', 'det.fk_product'), null, null, array(
					array(
						'alias' => 'l',
						'table' => 'bimp_commande_line',
						'on'    => 'l.id_line = det.rowid'
					),
					array(
						'alias' => 'p',
						'table' => 'product',
						'on'    => 'p.rowid = det.fk_product'
					),
					array(
						'alias' => 'pef',
						'table' => 'product_extrafields',
						'on'    => 'pef.fk_object = det.fk_product'
					)
				));

				if (is_array($lines)) {
					if (empty($lines)) {
						$this->Alert('Aucunes lignes', $commande_instance);
					} else {
						foreach ($lines as $line) {
							$id_prod = (int) $line['fk_product'];
							if (!isset($commandes[$id_cmd]['prods'][$id_prod])) {
								$commandes[$id_cmd]['prods'][$id_prod] = array();
							}

							if ($line['shipments']) {
								$line_shipments = json_decode($line['shipments'], 1);

								if (is_array($line_shipments)) {
									foreach ($line_shipments as $id_shipment => $shipment_data) {
										if (!in_array($id_shipment, $cmd_data['exps'])) {
											continue;
										}
										if (!isset($commandes[$id_cmd]['prods'][$id_prod][$id_shipment])) {
											$commandes[$id_cmd]['prods'][$id_prod][$id_shipment] = array(
												'qty_attendue' => 0,
												'qty_mvts'     => 0
											);
										}
										if ((int) BimpTools::getArrayValueFromPath($shipment_data, 'shipped', 0)) {
											$commandes[$id_cmd]['prods'][$id_prod][$id_shipment]['qty_attendue'] += (float) BimpTools::getArrayValueFromPath($shipment_data, 'qty', 0);
										}
									}
								}
							}
						}
					}
				} else {
					$errors[] = $this->db->err();
					return;
				}
			}

			// Calcul qty mouvements par produits:
			foreach ($commandes as $id_cmd => $cmd_data) {
				foreach ($cmd_data['prods'] as $id_prod => $prod_shipments) {
					foreach ($prod_shipments as $id_shipment => $qties) {
						$where = '(inventorycode LIKE \'CO' . $id_cmd . '$_EXP' . $id_shipment . '\' ESCAPE \'$\'';
						$where .= ' OR inventorycode LIKE \'CO' . $id_cmd . '$_EXP' . $id_shipment . '$_ANNUL\' ESCAPE \'$\')';
						$where .= ' AND fk_product = ' . $id_prod;
						$mvts = $this->db->getRows('stock_mouvement a', $where, null, 'array');

						if (is_array($mvts)) {
							foreach ($mvts as $m) {
								$commandes[$id_cmd]['prods'][$id_prod][$id_shipment]['qty_mvts'] -= (float) $m['value'];
							}
						} else {
							$errors[] = $this->db->err();
							return;
						}
					}
				}
			}

			$this->DebugData($commandes, 'DONNEES COMMANDES');

			// Comparaisons:
			foreach ($commandes as $id_cmd => $cmd_data) {
				foreach ($cmd_data['prods'] as $id_prod => $prod_shipments) {
					foreach ($prod_shipments as $id_shipment => $qties) {
						$this->incProcessed();
						if ((float) $qties['qty_attendue'] !== (float) $qties['qty_mvts']) {
							$prod_instance->id = $id_prod;
							$id_entrepot = (int) $this->db->getValue('commande_extrafields', 'entrepot', 'fk_object = ' . $id_cmd);

							$html = '<a target="_blank" href="' . DOL_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $id_cmd . '">';
							$html .= (isset($entrepots[$id_entrepot]) ? $entrepots[$id_entrepot] : 'Entrepôt #' . $id_entrepot) . ' - ';
							$html .= 'Expédition #' . $id_shipment;
							$html .= '</a> : ';
							$html .= '<br/>';
							$html .= 'Attendu : <b>' . $qties['qty_attendue'] . '</b><br/>Mouvements : <b>' . $qties['qty_mvts'] . '</b><br/>';
							$html .= '<a target="_blank" href="' . DOL_URL_ROOT . '/bimpcore/index.php?fc=product&id=' . $id_prod . '&navtab-maintabs=stock&navtab-stocks_view=stocks_mvts_tab">Mouvements produit ' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

							$this->Error($html, $prod_instance, 'CO' . $id_cmd . '_EXP' . $id_shipment);
						}
					}
				}
			}
		}
	}

	// Correction code mouvements annulations réceptions:

	public function initCorrectCodeMvt(&$data, &$errors = array())
	{
//        $where = 'inventorycode LIKE \'%$_RECEP$_%\' ESCAPE \'$\'';
		$where = 'inventorycode LIKE \'EQ%$_SUPPR\' ESCAPE \'$\'';
		$where .= ' AND origintype = \'order_supplier\' AND fk_origin > 0';
		$rows = $this->db->getRows('stock_mouvement', $where, null, 'array', array('rowid'), 'rowid', 'asc');

		if (is_array($rows)) {
			$elements = array();
			foreach ($rows as $r) {
				$elements[] = (int) $r['rowid'];
			}
			$data['steps'] = array(
				'correct' => array(
					'label'                  => 'Correction des codes mouvement',
					'on_error'               => 'continue',
					'elements'               => $elements,
					'nbElementsPerIteration' => 100
				)
			);
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCorrectCodeMvt($step_name, &$errors = array(), $extra_data = array())
	{
		$rows = $this->db->getRows('stock_mouvement', 'rowid IN (' . implode(',', $this->references) . ')', null, 'array', array('rowid', 'inventorycode', 'label', 'datem', 'fk_origin'));

		if (is_array($rows)) {
			$mvt_instance = BimpObject::getInstance('bimpcore', 'BimpProductMouvement');
			$this->setCurrentObject($mvt_instance);
			foreach ($rows as $r) {
				$this->incProcessed();
//                if (preg_match('/^(ANNUL_)?CMDF_(\d+)_LN_(\d+)_RECEP_(\d+)$/', $r['inventorycode'], $matches)) {
//                    $code = $matches[1] . 'CMDF' . $matches[2] . '_LN' . $matches[3] . '_RECEP' . $matches[4];
//                    if ($this->db->update('stock_mouvement', array(
//                                'inventorycode' => $code
//                                    ), 'rowid = ' . (int) $r['rowid']) <= 0) {
//                        $this->Error('ECHEC - ' . $this->db->err(), null, $r['inventorycode']);
//                    } else {
//                        $this->Success('Code corrigé: ' . $code, null, $r['inventorycode']);
//                    }
//                } else {
//                    $this->Alert('Code incorrect: ' . $r['inventorycode']);
//                }

				if (preg_match('/^.+ \- serial: (.+)$/', $r['label'], $matches)) {
					$serial = $matches[1];
					$where = 'inventorycode LIKE \'CMDF%\' AND datem < \'' . $r['datem'] . '\'';
					$where .= ' AND origintype = \'order_supplier\' AND fk_origin = ' . $r['fk_origin'];
					$where .= ' AND label LIKE \'% - serial: "' . $serial . '"\'';
					$mvt = $this->db->getRow('stock_mouvement', $where, array('rowid', 'inventorycode'), 'array', 'rowid', 'DESC');

					$mvt_instance->id = (int) $r['rowid'];
					if ($this->db->update('stock_mouvement', array(
							'inventorycode' => 'ANNUL_' . $mvt['inventorycode'],
							'label'         => $r['label'] . ' - (Code inventaire corrigé automatiquement depuis mvt #' . $mvt['rowid'] . ' - ancien code: ' . $r['inventorycode'] . ')'
						), 'rowid = ' . (int) $r['rowid']) <= 0) {
						$this->Error('Echec mise à jour du code inventaire - ' . $this->db->err(), $mvt_instance, $r['inventorycode']);
					} else {
						$this->Success('Code inventaire corrigé (Ancien code: ' . $r['inventorycode'] . ')', $mvt_instance, 'ANNUL_' . $mvt['inventorycode']);
						$this->incUpdated();
						continue;
					}
				}
				$this->incIgnored();
			}
		} else {
			$this->Error($this->db->err());
		}
	}

	// Corrections positions:

	public function initCorrectPositions(&$data, &$errors = array())
	{
		$objects = array(
			'equipements' => array(
				'table' => 'be_equipment_place',
				'field' => 'id_equipment'
			),
			'packages'    => array(
				'table' => 'be_package_place',
				'field' => 'id_package'
			)
		);

		$data['steps'] = array();

		foreach ($objects as $name => $obj) {
			$elements = array();
			$rows = $this->db->getRows($obj['table'], 'position = 0', null, 'array', array($obj['field']), null, 'id', 'desc');

			foreach ($rows as $r) {
				if (!in_array((int) $r[$obj['field']], $elements)) {
					$elements[] = (int) $r[$obj['field']];
				}
			}

			if (!empty($elements)) {
				$data['steps']['correct_' . $name] = array(
					'label'                  => 'Correction ' . $name,
					'on_error'               => 'continue',
					'elements'               => $elements,
					'nbElementsPerIteration' => 250
				);
			}
		}
	}

	public function executeCorrectPositions($step_name, &$errors = array(), $extra_data = array())
	{
		$objects = array(
			'equipements' => array(
				'instance'     => BimpObject::getInstance('bimpequipment', 'Equipment'),
				'table'        => 'be_equipment_place',
				'parent_field' => 'id_equipment',
				'way'          => 'DESC'
			),
			'packages'    => array(
				'instance'     => BimpObject::getInstance('bimpequipment', 'BE_Package'),
				'table'        => 'be_package_place',
				'parent_field' => 'id_package',
				'way'          => 'DESC'
			)
		);

		if (preg_match('/^correct_(.+)$/', $step_name, $matches)) {
			$name = $matches[1];

			if (isset($objects[$name])) {
				$obj = $objects[$name];
				$this->setCurrentObject($obj['instance']);

				foreach ($this->references as $id_obj) {
					$this->incProcessed();
					$obj['instance']->id = $id_obj;
					$rows = $this->db->getRows($obj['table'], $obj['parent_field'] . ' = ' . (int) $id_obj, null, 'array', array('id'), 'id', $obj['way']);

					if (is_array($rows)) {
						$check = true;
						$i = 1;
						foreach ($rows as $r) {
							if ($this->db->update($obj['table'], array(
									'position' => $i
								), 'id = ' . (int) $r['id']) <= 0) {
								$this->Error($name . ' - Echec mise à jour ligne n° ' . $i . ' - ' . $this->db->err(), $obj['instance'], $r['id']);
								$check = false;
								break 2;
							}
							$i++;
						}

						if ($check) {
							$this->incUpdated();
							$this->Success('Correction des position OK', $obj['instance']);
							continue;
						}
					} else {
						$errors[] = $this->db->err();
					}
					$this->incIgnored();
				}
			}
		}
	}

	// Corrections doc signés:

	public function initCorrectSignedDoc(&$data, &$errors = array())
	{
		$date = $this->getOption('date_from');

		if (!$date) {
			$errors[] = 'Veuillez saisir une date';
		} else {
			$sql = 'SELECT a.id FROM ' . MAIN_DB_PREFIX . 'bimpcore_signature a ';
			$sql .= 'WHERE (';
			$sql .= 'SELECT COUNT(s.id) FROM ' . MAIN_DB_PREFIX . 'bimpcore_signature_signataire s ';
			$sql .= 'WHERE s.id_signature = a.id AND s.status = 10 AND s.type_signature IN (1,3) ';
			$sql .= 'AND date_signed >= \'' . $date . ' 00:00:00\')';
			$sql .= ' = 1;';

			$rows = $this->db->executeS($sql, 'array');

			if (is_array($rows)) {
				$signatures = array();

				foreach ($rows as $r) {
					$signatures[] = (int) $r['id'];
				}

				$data['steps']['correction'] = array(
					'label'                  => 'Reconstruction des docs signés',
					'on_error'               => 'continue',
					'elements'               => $signatures,
					'nbElementsPerIteration' => 25
				);
			} else {
				$errors[] = 'ERR SQL - ' . $this->db->err();
			}
		}
	}

	public function executeCorrectSignedDoc($step_name, &$errors = array(), $extra_data = array())
	{
		$this->setCurrentObjectData('bimpcore', 'Bimp_Signature');
		foreach ($this->references as $id_signature) {
			$this->incProcessed();
			$signature = BimpCache::getBimpObjectInstance('bimpcore', 'BimpSignature', $id_signature);

			if (BimpObject::objectLoaded($signature)) {
				$err = $signature->rebuildSignedDoc();

				if (count($err)) {
					$this->Error(BimpTools::getMsgFromArray($err, 'Echec reconstruction doc signé'), $signature);
				} else {
					$this->Success('Reconstruction doc signé OK', $signature);
					$this->incUpdated();
					continue;
				}
			}

			$this->incIgnored();
		}
	}

	// Vérifs clients finaux factures:

	public function initCheckClientsFinauxFactures(&$data, &$errors = array())
	{
		$sql = 'SELECT a.rowid FROM ' . MAIN_DB_PREFIX . 'commande a ';
		$sql .= 'WHERE a.id_client_facture > 0 AND a.id_client_facture != a.fk_soc';

		$rows = $this->db->executeS($sql, 'array');

		if (is_array($rows)) {
			$commandes = array();

			foreach ($rows as $r) {
				$commandes[] = (int) $r['rowid'];
			}

			$data['steps']['correction'] = array(
				'label'                  => 'Vérif des clients finaux',
				'on_error'               => 'continue',
				'elements'               => $commandes,
				'nbElementsPerIteration' => 25
			);
		} else {
			$errors[] = 'ERR SQL - ' . $this->db->err();
		}
	}

	public function executeCheckClientsFinauxFactures($step_name, &$errors = array(), $extra_data = array())
	{
		$this->setCurrentObjectData('bimpcore', 'Bimp_Commande');
		foreach ($this->references as $id_commande) {
			$this->incProcessed();
			$commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

			if (BimpObject::objectLoaded($commande)) {
				$nbDone = 0;
				$err = $commande->checkClientsFinauxFactures($nbDone);
				if (count($err)) {
					$this->Error(BimpTools::getMsgFromArray($err, 'Echec'), $commande);
				} else {
					if ($nbDone > 0) {
						$this->Success($nbDone . ' facture(s) mise(s) à jour', $commande);
					}
				}
			} else {
				$this->incIgnored();
			}
		}
	}

	// Vérifs produits sans mouvements depuis 2 ans

	public function initCheckInactiveProducts(&$data, &$errors = array())
	{
		$dt_max_datec = new DateTime();
		$dt_max_datec->sub(new DateInterval('P3M'));

		$dt_min_date_mvt = new DateTime();
		$dt_min_date_mvt->sub(new DateInterval('P2Y'));

		$sql = 'SELECT a.rowid FROM ' . MAIN_DB_PREFIX . 'product a ';
		$sql .= 'WHERE a.datec < "' . $dt_max_datec->format('Y-m-d') . ' 00:00:00" AND (a.tosell > 0 OR a.tobuy > 0)';
		$sql .= ' AND a.fk_product_type = 0';
		$sql .= ' AND (SELECT COUNT(mvt.rowid) FROM ' . MAIN_DB_PREFIX . 'stock_mouvement mvt WHERE';
		$sql .= ' mvt.fk_product = a.rowid AND mvt.datem > "' . $dt_min_date_mvt->format('Y-m-d') . ' 00:00:00"';
		$sql .= ') = 0';

		$this->debug_content .= 'SQL : ' . $sql;

		$rows = $this->db->executeS($sql, 'array');

		if (is_array($rows)) {
			$elements = array();

			foreach ($rows as $r) {
				$elements[] = (int) $r['rowid'];
			}

			$data['steps']['process'] = array(
				'label'                  => 'Désactivation des produits',
				'on_error'               => 'continue',
				'elements'               => $elements,
				'nbElementsPerIteration' => 1000
			);
		} else {
			$errors[] = 'ERR SQL - ' . $this->db->err();
		}
	}

	public function executeCheckInactiveProducts($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcore', 'Bimp_Product');
			if (!empty($this->references)) {
				$this->incProcessed('current', count($this->references));
				if ($this->db->update('product', array(
						'tosell' => 0,
						'tobuy'  => 0
					), 'rowid IN (' . implode(',', $this->references) . ')') <= 0) {
					$this->incIgnored('current', count($this->references));
					$this->Error('Echec màj - ' . $this->db->err());
				} else {
					$this->incUpdated('current', count($this->references));
				}
			}
		}
	}

	// Vérifs et correction des stocks contrats d'abonnement :

	public function initCheckContratsStocks(&$data, &$errors = array())
	{
		$filters = array(
			'f.fk_statut'          => array(1, 2),
			'f.type'               => array(0, 1, 2),
			'a.linked_object_name' => 'contrat_line',
			'p.fk_product_type'    => 0
		);

//        $key = 'SELECT (COUNT(sm.rowid) FROM ' . MAIN_DB_PREFIX . 'stock_mouvement sm WHERE inventorycode LIKE CONCAT(\'BCT%$_LN%$_FACLN\', a.id) ESCAPE \'$\')';
//        $filters[$key] = 0;

		$joins = array(
			'f'  => array(
				'table' => 'facture',
				'on'    => 'f.rowid = a.id_obj'
			),
			'fl' => array(
				'table' => 'facturedet',
				'on'    => 'fl.rowid = a.id_line'
			),
			'p'  => array(
				'table' => 'product',
				'on'    => 'p.rowid = fl.fk_product'
			)
		);

		$sql = BimpTools::getSqlFullSelectQuery('bimp_facture_line', array('a.id as id_line'), $filters, $joins);

		$rows = $this->db->executeS($sql, 'array');

		if (is_array($rows)) {
			$elements = array();

			foreach ($rows as $r) {
				$elements[] = (int) $r['id_line'];
			}

			$data['steps']['process'] = array(
				'label'                  => 'Correction des stocks',
				'on_error'               => 'continue',
				'elements'               => $elements,
				'nbElementsPerIteration' => 100
			);
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCheckContratsStocks($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcore', 'Bimp_FactureLine');
			if (!empty($this->references)) {
				foreach ($this->references as $id_fac_line) {
					$this->incProcessed();
					$fac_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', $id_fac_line);

					if (!BimpObject::objectLoaded($fac_line)) {
						$this->Error('Ligne de facture #' . $id_fac_line . ' inexistante');
						$this->incIgnored();
						continue;
					}

					$contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $fac_line->getData('linked_id_object'));
					if (!BimpObject::objectLoaded($contrat_line)) {
						$this->Error('Ligne de contrat liée #' . $fac_line->getData('linked_id_object') . ' inexistante', $fac_line);
						$this->incIgnored();
						continue;
					}

					$success = '';
					$line_errors = $contrat_line->onFactureValidated($fac_line, $success);

					if (count($line_errors)) {
						$this->Error($line_errors, $contrat_line, 'Ligne de facture #' . $fac_line->id);
						$this->incIgnored();
					} elseif ($success) {
						$this->incUpdated();
						$this->Success($success, $contrat_line, 'Ligne de facture #' . $fac_line->id);
					} else {
						$this->incIgnored();
					}
				}
			}
		}
	}

	// Vérifs et correction des statuts des abonnements :

	public function initCheckAbonnementsStatus(&$data, &$errors = array())
	{
		$filters = array(
			'a.line_type' => 2,
			'a.statut'    => array('operator' => '>', 'value' => 0),
			'c.version'   => 2,
		);

		$joins = array(
			'c' => array(
				'table' => 'contrat',
				'on'    => 'c.rowid = a.fk_contrat'
			)
		);

		$sql = BimpTools::getSqlFullSelectQuery('contratdet', array('a.rowid'), $filters, $joins);
		$rows = $this->db->executeS($sql, 'array');

		if (is_array($rows)) {
			$elements = array();

			foreach ($rows as $r) {
				$elements[] = (int) $r['rowid'];
			}

			$data['steps']['process'] = array(
				'label'                  => 'Vérifs de statuts des abonnements',
				'on_error'               => 'continue',
				'elements'               => $elements,
				'nbElementsPerIteration' => 100
			);
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCheckAbonnementsStatus($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcontrat', 'BCT_ContratLine');
			if (!empty($this->references)) {
				foreach ($this->references as $id_line) {
					$this->incProcessed();
					$line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

					if (!BimpObject::objectLoaded($line)) {
						$this->Error('Ligne de contrat #' . $id_line . ' inexistante');
						$this->incIgnored();
						continue;
					}

					$infos = array();
					$line->checkStatus($infos);
					if (!empty($infos)) {
						$this->Info($infos, $line);
					}
				}
			}
		}
	}

	// Vérifs et correction des statuts abonnements dans les devis:

	public function initCheckPropalsContratsStatus(&$data, &$errors = array())
	{
		$rows = $this->db->getRows('propal', '(fk_statut IN (2, 4) AND date_valid IS NULL OR date_valid >= \'2023-10-01 00:00:00\') AND contrats_status = 0', null, 'array', array('rowid'));

		if (is_array($rows)) {
			foreach ($rows as $r) {
				$elements = array();

				foreach ($rows as $r) {
					$elements[] = (int) $r['rowid'];
				}

//                die('N : ' . count($elements));

				$data['steps']['process'] = array(
					'label'                  => 'Vérifs des statuts abonnements',
					'on_error'               => 'continue',
					'elements'               => $elements,
					'nbElementsPerIteration' => 100
				);
			}
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCheckPropalsContratsStatus($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcommercial', 'Bimp_Propal');
			if (!empty($this->references)) {
				foreach ($this->references as $id_propal) {
					$this->incProcessed();
					$propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_propal);

					if (!BimpObject::objectLoaded($propal)) {
						$this->Error('Propal #' . $id_propal . ' inexistante');
						$this->incIgnored();
						continue;
					}

					$cur_status = (int) $propal->getData('contrats_status');
					$err = $propal->checkContratsStatus(null);
					$new_status = (int) $propal->getData('contrats_status');

//                    $this->Info('New : ' . $new_status . ' - old : ' . $cur_status, $propal);

					if (!empty($err)) {
						$this->Error($err, $propal);
						$this->incIgnored();
					} elseif ($cur_status !== $new_status) {
						$this->incUpdated();
					}
				}
			}
		}
	}

	// Vérifs et correction des statuts abonnements dans les devis:

	public function initCheckCommandesFournStatus(&$data, &$errors = array())
	{
		$rows = $this->db->getRows('commande_fournisseur', 'closed = 0 AND fk_statut IN (3,4,5) AND invoice_status < 2', null, 'array', array('rowid'));

		if (is_array($rows)) {
			foreach ($rows as $r) {
				$elements = array();

				foreach ($rows as $r) {
					$elements[] = (int) $r['rowid'];
				}

				$data['steps']['process'] = array(
					'label'                  => 'Vérifs des statuts des commandes founisseur',
					'on_error'               => 'continue',
					'elements'               => $elements,
					'nbElementsPerIteration' => 1000
				);
			}
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCheckCommandesFournStatus($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcommercial', 'Bimp_CommandeFourn');
			if (!empty($this->references)) {
				foreach ($this->references as $id_cf) {
					// Le simple fait de fetcher déclenche la vérif du statut
					$cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);

					if (!BimpObject::objectLoaded($cf)) {
						$this->Error('CF #' . $id_cf . ' inexistante');
						$this->incIgnored();
					} else {
						$this->incProcessed();
					}
				}
			}
		}
	}

	// Revalorisations PA factures:

	public function initFacsRevals(&$data, &$errors = array())
	{
		$date_from = $this->getOption('date_from', '');
		$date_to = $this->getOption('date_to', '');

		if ($date_from && $date_to) {
			if ($date_from < $date_to) {
				$errors[] = 'La date de fin ne peut pas être inférieure à la date de début';
			}
		}

		$refs = explode(',', $this->getOption('refs_list', ''));

		if (empty($refs)) {
			$errors[] = 'Aucune référence indiquée';
		}

		if (!count($errors)) {
			$prods = array();

			foreach ($refs as $ref) {
				$id_prod = (int) $this->db->getValue('product', 'rowid', 'ref = \'' . $ref . '\'');

				if ($id_prod) {
					$prods[] = $id_prod;
				} else {
					$errors[] = 'Aucun produit trouvé pour la référence "' . $ref . '"';
				}
			}

			if (!count($errors)) {
				$where = 'a.fk_product IN (' . implode(',', $prods) . ')';
				$where .= ' AND f.type IN (0,1,2)';
				$where .= ' AND f.fk_statut IN (0,1,2)';
				if ($date_from) {
					$where .= ' AND f.datef >= \'' . date('Y-m-d', strtotime($date_from)) . '\'';
				}
				if ($date_to) {
					$where .= ' AND f.datef <= \'' . date('Y-m-d', strtotime($date_to)) . '\'';
				}

				$rows = $this->db->getRows('facturedet a', $where, null, 'array', array('a.rowid', 'a.fk_product'), null, null, array(
					'f' => array(
						'table' => 'facture',
						'on'    => 'f.rowid = a.fk_facture'
					)
				));

				if (is_array($rows)) {
					foreach ($rows as $r) {
						$elements = array();

						foreach ($rows as $r) {
							$elements[] = (int) $r['fk_product'] . ';' . (int) $r['rowid'];
						}

						$data['steps']['process'] = array(
							'label'                  => 'Vérifs revalorisations prix d\'achat',
							'on_error'               => 'continue',
							'elements'               => $elements,
							'nbElementsPerIteration' => 100
						);
					}
				} else {
					$errors[] = $this->db->err();
				}
			}
		}
	}

	public function executeFacsRevals($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcommercial', 'Bimp_FactureLine');
			if (!empty($this->references)) {
				$prod = null;

				foreach ($this->references as $ref) {
					$this->incProcessed();

					$data = explode(';', $ref);
					$id_prod = (int) $data[0];
					$id_line = (int) $data[1];

					if (!BimpObject::objectLoaded($prod) || $prod->id !== $id_prod) {
						$prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);

						if (!BimpObject::objectLoaded($prod)) {
							$this->Error('Produit #' . $id_prod . ' non trouvé');
							$this->incIgnored();
							continue;
						}
					}

					$fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
						'id_line' => $id_line
					));

					if (!BimpObject::objectLoaded($fac_line)) {
						$this->Error('Aucune ligne de facture trouvée pour l\'ID ' . $id_line);
						$this->incIgnored();
						continue;
					}

					$facture = $fac_line->getParentInstance();
					if (!BimpObject::objectLoaded($facture)) {
						$this->Error('Facture absente pour la ligne #' . $fac_line->id);
						$this->incIgnored();
						continue;
					}

					$cur_pa_ht = $prod->getCurrentPaHt();
					$line_pa_ht = $fac_line->getPaWithRevalorisations();

					if ($cur_pa_ht == $line_pa_ht) {
						$this->Info('PA revalorisé à jour (' . $line_pa_ht . ')', $fac_line);
						$this->incIgnored();
						continue;
					}

					$line_errors = $fac_line->updatePrixAchat($cur_pa_ht);

					if (count($line_errors)) {
						$this->Error(BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour du prix d\'achat'), $fac_line);
						$this->incIgnored();
						continue;
					}

					$this->Success('Màj PA ok (' . $cur_pa_ht . ')', $fac_line);
					$this->incUpdated();
				}
			}
		}
	}

	// Vérifs et correction des statuts commande dans les devis:

	public function initCheckPropalsCommandeStatus(&$data, &$errors = array())
	{
		$rows = $this->db->getRows('propal', 'commande_status = 1', null, 'array', array('rowid'));

		if (is_array($rows)) {
			foreach ($rows as $r) {
				$elements = array();

				foreach ($rows as $r) {
					$elements[] = (int) $r['rowid'];
				}

//                die('N : ' . count($elements));

				$data['steps']['process'] = array(
					'label'                  => 'Vérifs des statuts commande',
					'on_error'               => 'continue',
					'elements'               => $elements,
					'nbElementsPerIteration' => 100
				);
			}
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCheckPropalsCommandeStatus($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcommercial', 'Bimp_Propal');
			if (!empty($this->references)) {
				foreach ($this->references as $id_propal) {
					$this->incProcessed();
					$propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_propal);

					if (!BimpObject::objectLoaded($propal)) {
						$this->Error('Propal #' . $id_propal . ' inexistante');
						$this->incIgnored();
						continue;
					}

					$cur_status = (int) $propal->getData('commande_status');
					$err = $propal->checkCommandeStatus(null);
					$new_status = (int) $propal->getData('commande_status');

					$this->Info('New : ' . $new_status . ' - old : ' . $cur_status, $propal);

					if (!empty($err)) {
						$this->Error($err, $propal);
						$this->incIgnored();
					} elseif ($cur_status !== $new_status) {
						$this->incUpdated();
					}
				}
			}
		}
	}

	// Vérifs PA Factures :

	public function initCheckFacturesPA(&$data, &$errors = array())
	{
		$rows = $this->db->getRows('facture f', 'fef.type = \'S\' AND f.datec > \'2024-11-28 00:00:00\'', null, 'array', array('f.rowid'), null, null, array(
			'fef' => array(
				'table' => 'facture_extrafields',
				'on'    => 'f.rowid = fef.fk_object'
			)
		));

		if (is_array($rows)) {
			$elements = array();

			foreach ($rows as $r) {
				$elements[] = (int) $r['rowid'];
			}

			$data['steps']['process'] = array(
				'label'                  => 'Vérifs des PA Factures',
				'on_error'               => 'continue',
				'elements'               => $elements,
				'nbElementsPerIteration' => 10
			);
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCheckFacturesPA($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcommercial', 'Bimp_FactureLine');
			if (!empty($this->references)) {
				foreach ($this->references as $id_fac) {
					$this->incProcessed();
					$fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

					if (BimpObject::objectLoaded($fac)) {
						$lines = $fac->getLines('product');

						foreach ($lines as $line) {
							$this->incProcessed();

							$details = array();
							$cur_pa = $line->pa_ht;
							$line->checkPrixAchat($details);

							if ($cur_pa != $line->pa_ht) {
								$this->Info('MAJ PA ' . $cur_pa . ' => ' . $line->pa_ht . '<br/>Détail : <pre>' . print_r($details, 1) . '</pre>', $fac, 'Ligne n° ' . $line->getData('position'));
								$this->incUpdated();
								break;
							}
						}
					}
				}
			}
		}
	}

	// Vérifs Remises Arrières Factures :

	public function initCheckFacturesRA(&$data, &$errors = array())
	{
		$rows = $this->db->getRows('facture f', 'f.date_valid > \'2025-05-01 00:00:00\'', null, 'array', array('f.rowid'), null, null, array());

		if (is_array($rows)) {
			$elements = array();

			foreach ($rows as $r) {
				$elements[] = (int) $r['rowid'];
			}

			$data['steps']['process'] = array(
				'label'                  => 'Vérifs des remises arrières Factures',
				'on_error'               => 'continue',
				'elements'               => $elements,
				'nbElementsPerIteration' => 10
			);
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCheckFacturesRA($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
			$this->setCurrentObjectData('bimpcommercial', 'Bimp_FactureLine');
			if (!empty($this->references)) {
				foreach ($this->references as $id_fac) {
					$this->incProcessed();
					$fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

					if (BimpObject::objectLoaded($fac)) {
						$lines = $fac->getLines('not_text');

						foreach ($lines as $line) {
							$this->incProcessed();

							$details = array();
							$errors = array();
							$line->checkRemisesArrieres($errors, $details);

							if (count($details)) {
								$this->Info('<pre>' . print_r($details, 1) . '</pre>', $fac, 'Ligne n° ' . $line->getData('position'));
								$this->incUpdated();
							}

							if (count($errors)) {
								$this->Error('<pre>' . print_r($errors, 1) . '</pre>', $fac, 'Ligne n° ' . $line->getData('position'));
							}
						}
					}
				}
			}
		}
	}

	// Vérifs des mouvements de stocks des réceptions

	public function initCheckReceptionsStocksMvt(&$data, &$errors = array())
	{
		$sql = "SELECT cfdet.`fk_product` as id_prod, rl.qty, cfl.id_obj as id_cf, rl.id_reception as id_br, cfl.id as id_line,
(SELECT SUM(mvt.value) FROM llx_stock_mouvement mvt WHERE mvt.fk_product = cfdet.`fk_product` AND (mvt.inventorycode = CONCAT('CMDF', cfl.id_obj, '_LN', cfl.id, '_RECEP', rl.`id_reception`) OR mvt.inventorycode = CONCAT('CMDF_', cfl.id_obj, '_LN', cfl.id, '_RECEP', rl.`id_reception`) OR mvt.inventorycode = CONCAT('ANNUL_CMDF', cfl.id_obj, '_LN', cfl.id, '_RECEP', rl.`id_reception`))) AS total_mvt
FROM llx_bl_reception_line rl
LEFT JOIN llx_bl_commande_fourn_reception br on br.id = rl.id_reception
LEFT JOIN llx_bimp_commande_fourn_line cfl ON cfl.id = rl.id_commande_fourn_line
LEFT JOIN llx_commande_fournisseurdet cfdet ON cfdet.rowid = cfl.id_line
WHERE rl.qty != ROUND(rl.qty, 0)
AND br.status = 1
HAVING total_mvt != rl.qty;";

		$rows = $this->db->executeS($sql, 'array');

		if (is_array($rows)) {
			$elements = array();

			foreach ($rows as $r) {
				$elements[] = json_encode(array(
					'id_prod'   => (int) $r['id_prod'],
					'id_cf'     => (int) $r['id_cf'],
					'id_br'     => (int) $r['id_br'],
					'id_line'   => (int) $r['id_line'],
					'qty'       => (float) $r['qty'],
					'total_mvt' => (float) $r['total_mvt']
				));
			}

			echo '<pre>' . print_r($elements, 1) . '</pre>';
			exit;

			$data['steps']['process'] = array(
				'label'                  => 'Vérifs des remises arrières Factures',
				'on_error'               => 'continue',
				'elements'               => $elements,
				'nbElementsPerIteration' => 50
			);
		} else {
			$errors[] = $this->db->err();
		}
	}

	public function executeCheckReceptionsStocksMvt($step_name, &$errors = array(), $extra_data = array())
	{
		if ($step_name == 'process') {
//			$this->setCurrentObjectData('bimpcore', 'Bimp_Product');
//			if (!empty($this->references)) {
//				foreach ($this->references as $id_prod) {
//					$this->incProcessed();
//					$prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);
//
//					if (BimpObject::objectLoaded($prod)) {
//						$codes = array(
//							'CMDF'
//						);
//						$where = '';
//						$diff = $this->db->getSum('stock_mouvement', 'value', 'fk_product = ' . (int) $rl->getData('fk_product') . ' AND (inventorycode = CONCAT(\'CMDF\', ' . (int) $rl->getData('id_obj') . ', \'_LN\', ' . (int) $rl->getData('id_line') . ', \'_RECEP\', ' . (int) $rl->getData('id_reception') . ') OR inventorycode = CONCAT(\'CMDF_\', ' . (int) $rl->getData('id_obj') . ', \'_LN\', ' . (int) $rl->getData('id_line') . ', \'_RECEP\', ' . (int) $rl->getData('id_reception') . ') OR inventorycode = CONCAT(\'ANNUL_CMDF\', ' . (int) $rl->getData('id_obj') . ', \'_LN\', ' . (int) $rl->getData('id_line') . ', \'_RECEP\', ' . (int) $rl->getData('id_reception') . ') )');
//						$lines = $fac->getLines('not_text');
//
//						foreach ($lines as $line) {
//							$this->incProcessed();
//
//							$details = array();
//							$errors = array();
//							$line->checkRemisesArrieres($errors, $details);
//
//							if (count($details)) {
//								$this->Info('<pre>' . print_r($details, 1) . '</pre>', $fac, 'Ligne n° ' . $line->getData('position'));
//								$this->incUpdated();
//							}
//
//							if (count($errors)) {
//								$this->Error('<pre>' . print_r($errors, 1) . '</pre>', $fac, 'Ligne n° ' . $line->getData('position'));
//							}
//						}
//					} else {
//						$this->Error('PROD #' . $id_prod . ' inexistant');
//						$this->incIgnored();
//					}
//				}
//			}
		}
	}

	// Install:

	public static function install(&$errors = array(), &$warnings = array(), $title = '')
	{
		// Process:
		$process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
			'name'        => 'Verifs',
			'title'       => ($title ? $title : static::$default_public_title),
			'description' => '',
			'type'        => 'other',
			'active'      => 1
		), true, $errors, $warnings);

		if (BimpObject::objectLoaded($process)) {
			// Options:

			$options = array();

			$opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $process->id,
				'label'         => 'A partir du',
				'name'          => 'date_from',
				'info'          => '',
				'type'          => 'date',
				'default_value' => '',
				'required'      => 0
			), true, $warnings, $warnings);

			if (BimpObject::objectLoaded($opt)) {
				$options['date_from'] = (int) $opt->id;
			}

			$opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $process->id,
				'label'         => 'Jusqu\'au',
				'name'          => 'date_to',
				'info'          => '',
				'type'          => 'date',
				'default_value' => '',
				'required'      => 0
			), true, $warnings, $warnings);

			if (BimpObject::objectLoaded($opt)) {
				$options['date_to'] = (int) $opt->id;
			}

			$opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $process->id,
				'label'         => 'Nb éléments par itérations',
				'name'          => 'nb_elements_per_iterations',
				'info'          => '',
				'type'          => 'text',
				'default_value' => '100',
				'required'      => 0
			), true, $warnings, $warnings);

			if (BimpObject::objectLoaded($opt)) {
				$options['nb_elements_per_iterations'] = (int) $opt->id;
			}

			$opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $process->id,
				'label'         => 'Vérifier seulement les factures non classées payées',
				'name'          => 'not_classified_only',
				'info'          => '',
				'type'          => 'toggle',
				'default_value' => '1',
				'required'      => 0
			), true, $warnings, $warnings);

			if (BimpObject::objectLoaded($opt)) {
				$options['not_classified_only'] = (int) $opt->id;
			}

			$opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $process->id,
				'label'         => 'Restes à payer à 0 seulement',
				'name'          => 'rtp_zero_only',
				'info'          => '',
				'type'          => 'toggle',
				'default_value' => '0',
				'required'      => 0
			), true, $warnings, $warnings);

			if (BimpObject::objectLoaded($opt)) {
				$options['rtp_zero_only'] = (int) $opt->id;
			}

			// Opérations:
			// Vérifs marges factures:
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'  => (int) $process->id,
				'title'       => 'Vérifier les marges + revals OK des factures',
				'name'        => 'checkFacsMargin',
				'description' => '',
				'warning'     => '',
				'active'      => 1,
				'use_report'  => 0,
			), true, $warnings, $warnings);

			if (BimpObject::objectLoaded($op)) {
				$op_options = array();

				if (isset($options['date_from'])) {
					$op_options[] = $options['date_from'];
				}
				if (isset($options['date_to'])) {
					$op_options[] = $options['date_to'];
				}
				if (isset($options['nb_elements_per_iterations'])) {
					$op_options[] = $options['nb_elements_per_iterations'];
				}

				$warnings = array_merge($warnings, $op->addAssociates('options', $op_options));
			}

			// Vérifs restes à payer factures:
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'  => (int) $process->id,
				'title'       => 'Vérifier les restes à payer des factures',
				'name'        => 'checkFacsRtp',
				'description' => '',
				'warning'     => '',
				'active'      => 1,
				'use_report'  => 0,
			), true, $warnings, $warnings);

			if (BimpObject::objectLoaded($op)) {
				$op_options = array();

				if (isset($options['date_from'])) {
					$op_options[] = $options['date_from'];
				}
				if (isset($options['date_to'])) {
					$op_options[] = $options['date_to'];
				}
				if (isset($options['nb_elements_per_iterations'])) {
					$op_options[] = $options['nb_elements_per_iterations'];
				}
				if (isset($options['not_classified_only'])) {
					$op_options[] = $options['not_classified_only'];
				}
				if (isset($options['rtp_zero_only'])) {
					$op_options[] = $options['rtp_zero_only'];
				}

				$warnings = array_merge($warnings, $op->addAssociates('options', $op_options));
			}
		}
	}

	public static function updateProcess($id_process, $cur_version, &$warnings = array())
	{
		$bdb = BimpCache::getBdb();

		$errors = array();

		if ($cur_version < 2) {
			// Opération "Reconstruction des docs signés":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Reconstruction des docs signés',
				'name'          => 'correctSignedDoc',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 15
			), true, $errors, $warnings);

			if (BimpObject::objectLoaded($op)) {
				$errors = array_merge($errors, $op->addOptions(array('date_from')));
			}
		}

		if ($cur_version < 3) {
			// Opération "Vérif des clients finaux des factures":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérification des clients finaux des factures',
				'name'          => 'checkClientsFinauxFactures',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 15
			), true, $errors, $warnings);
		}

		if ($cur_version < 4) {
			// Opération "Désactivation des produits sans mouvements depuis 2 ans":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Désactivation des produits sans mouvements depuis 2 ans',
				'name'          => 'checkInactiveProducts',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 365
			), true, $errors, $warnings);
		}

		if ($cur_version < 5) {
			// Opération "Vérif des stocks des facturations des contrats d\'abonnement":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif des stocks des facturations des contrats d\'abonnement',
				'name'          => 'checkContratsStocks',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 365
			), true, $errors, $warnings);
		}

		if ($cur_version < 6) {
			// Opération "Vérif des statuts des abonnements":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif des statuts des abonnements',
				'name'          => 'checkAbonnementsStatus',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);
		}

		if ($cur_version < 7) {
			// Opération "Vérif des statuts abonnements des propales":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif des statuts abonnements des propales',
				'name'          => 'checkPropalsContratsStatus',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);
		}

		if ($cur_version < 8) {
			// Opération "Vérif des marges des commandes":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif des marges des commandes',
				'name'          => 'checkCommandesMargin',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);

			if (BimpObject::objectLoaded($op)) {
				$op_options = array();

				$id_option = (int) $bdb->getValue('bds_process_option', 'id', 'id_process = ' . $id_process . ' AND name = \'date_from\'');
				if ($id_option) {
					$op_options[] = $id_option;
				}
				$id_option = (int) $bdb->getValue('bds_process_option', 'id', 'id_process = ' . $id_process . ' AND name = \'date_to\'');
				if ($id_option) {
					$op_options[] = $id_option;
				}
				$id_option = (int) $bdb->getValue('bds_process_option', 'id', 'id_process = ' . $id_process . ' AND name = \'nb_elements_per_iterations\'');
				if ($id_option) {
					$op_options[] = $id_option;
				}

				if (!empty($op_options)) {
					$warnings = array_merge($warnings, $op->addAssociates('options', $op_options));
				}
			}
		}

		if ($cur_version < 9) {
			// Opération "Vérif des marges des commandes":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif des statuts des commandes fournisseur',
				'name'          => 'checkCommandesFournStatus',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);
		}

		if ($cur_version < 10) {
			// Opération "Revalorisation PA factures":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Revalorisation PA factures',
				'name'          => 'facsRevals',
				'description'   => 'Génère des revalorisations du PA des factures créées depuis la date indiquée en fonction du PA actuel des réfs indiquées',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 90
			), true, $errors, $warnings);

			$op_options = array();

			$id_option = (int) $bdb->getValue('bds_process_option', 'id', 'id_process = ' . $id_process . ' AND name = \'date_from\'');
			if ($id_option) {
				$op_options[] = $id_option;
			}

			$id_option = (int) $bdb->getValue('bds_process_option', 'id', 'id_process = ' . $id_process . ' AND name = \'date_to\'');
			if ($id_option) {
				$op_options[] = $id_option;
			}

			$opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
				'id_process'    => (int) $id_process,
				'label'         => 'Liste des références',
				'name'          => 'refs_list',
				'info'          => 'Séparer chaque réf. par une virgule sans espace',
				'type'          => 'text',
				'default_value' => '',
				'required'      => 1
			), true, $warnings, $warnings);

			if (BimpObject::objectLoaded($opt)) {
				$op_options[] = (int) $opt->id;
			}

			$warnings = array_merge($warnings, $op->addAssociates('options', $op_options));
		}

		if ($cur_version < 11) {
			// Opération "Vérif des statuts commande des propales":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif des statuts commande des propales',
				'name'          => 'checkPropalsCommandeStatus',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);
		}

		if ($cur_version < 12) {
			// Opération "Vérif des statuts commande des propales":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif PA FActures',
				'name'          => 'checkFacturesPA',
				'description'   => '',
				'warning'       => 'Attention, les filtres sont en dur dans le code',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);
		}

		if ($cur_version < 13) {
			// Opération "Vérif des statuts commande des propales":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif Remises Arrières FActures',
				'name'          => 'checkFacturesRA',
				'description'   => '',
				'warning'       => 'Attention, les filtres sont en dur dans le code',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);
		}

		if ($cur_version < 14) {
			// Opération "Vérif des marges des propales":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif des marges des propal',
				'name'          => 'checkPropalsMargin',
				'description'   => '',
				'warning'       => '',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);

			if (BimpObject::objectLoaded($op)) {
				$op_options = array();

				$id_option = (int) $bdb->getValue('bds_process_option', 'id', 'id_process = ' . $id_process . ' AND name = \'date_from\'');
				if ($id_option) {
					$op_options[] = $id_option;
				}
				$id_option = (int) $bdb->getValue('bds_process_option', 'id', 'id_process = ' . $id_process . ' AND name = \'date_to\'');
				if ($id_option) {
					$op_options[] = $id_option;
				}
				$id_option = (int) $bdb->getValue('bds_process_option', 'id', 'id_process = ' . $id_process . ' AND name = \'nb_elements_per_iterations\'');
				if ($id_option) {
					$op_options[] = $id_option;
				}

				if (!empty($op_options)) {
					$warnings = array_merge($warnings, $op->addAssociates('options', $op_options));
				}
			}
		}

		if ($cur_version < 15) {
			// Opération "Vérif des statuts commande des propales":
			$op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
				'id_process'    => (int) $id_process,
				'title'         => 'Vérif Mouvements stocks réceptions',
				'name'          => 'checkReceptionsStocksMvt',
				'description'   => '',
				'warning'       => 'Attention, les filtres sont en dur dans le code',
				'active'        => 1,
				'use_report'    => 1,
				'reports_delay' => 30
			), true, $errors, $warnings);
		}

		return $errors;
	}
}

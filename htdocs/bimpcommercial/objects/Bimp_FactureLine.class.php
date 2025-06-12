<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_FactureLine extends ObjectLine
{

	public static $parent_comm_type = 'facture';
	public static $dol_line_table = 'facturedet';
	public static $dol_line_parent_field = 'fk_facture';
	public $equipment_required = true;
	public static $equipment_required_in_entrepot = false;

	// Droits user:

	public function canCreate()
	{
		global $user;
		if (/* $user->rights->facture->paiement */ $user->rights->bimpcommercial->factureAnticipe) {
			return 1;
		}

		return 0;
	}

	public function canModifiyFactureAmount()
	{
		if (!$this->isLoaded()) {
			return 1;
		}

		global $user;
		if (isset($user->rights->bimpcommercial->modif_fac_amount) && (int) $user->rights->bimpcommercial->modif_fac_amount) {
			return 1;
		}

		return 0;
	}

	public function canEditPrixAchat()
	{
		if (!(int) BimpCore::getConf('check_factures_amounts_modif_rights', null, 'bimpcommercial')) {
			return parent::canEditPrixAchat();
		}

		return $this->canModifiyFactureAmount();
	}

	public function canEditPrixVente()
	{
		if (!(int) BimpCore::getConf('check_factures_amounts_modif_rights', null, 'bimpcommercial')) {
			return parent::canEditPrixVente();
		}

		return $this->canModifiyFactureAmount();
	}

	public function canEditQty()
	{
		if (!(int) BimpCore::getConf('check_factures_amounts_modif_rights', null, 'bimpcommercial')) {
			return 1;
		}

		return $this->canModifiyFactureAmount();
	}

	public function getTotalMarge()
	{
		$margin = (float) $this->getMargin();
		$total_reval = 0;

		$done = false;
		$facture = $this->getParentInstance();

		if (BimpObject::objectLoaded($facture)) {
			if ((int) $facture->getData('fk_statut')) {
				$done = true;
				$revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
					'id_facture_line' => (int) $this->id
				));

				foreach ($revals as $reval) {
					if (in_array((int) $reval->getData('status'), array(0, 1))) {
						$reval_amount = $reval->getTotal();
						$margin += $reval_amount;
					}
				}
			}
		}

		if (!$done) {
			$remises_arrieres = $this->getTotalRemisesArrieres(false);

			$total_reval += $remises_arrieres;
			$margin += $remises_arrieres;
		}
		return $margin;
	}

	public function canSetAction($action)
	{
		global $user;

		switch ($action) {
			case 'bulkCreateRevalorisation':
				if ($user->admin) {
					return 1;
				}
				return 0;
		}

		return parent::canSetAction($action);
	}

	// Getters booléens:

	public function isEquipmentAvailable(Equipment $equipment = null)
	{
		// Aucune vérif pour les factures (L'équipement est attribué à titre indicatif)
		return array();
	}

	public function isRemiseEditable()
	{
		return $this->isParentDraft();
	}

	public function isFieldEditable($field, $force_edit = false)
	{
		switch ($field) {
			case 'pa_editable':
				return 1;

			case 'remise_crt':
				if (!$this->isParentDraft()) {
					return 0;
				}
				break;

			case 'qty':
				if (!$force_edit) {
					if ($this->getData('linked_object_name') === 'commande_line') {
						return 0;
					}
				}
				break;
		}

		return parent::isFieldEditable($field, $force_edit);
	}

	public function isActionAllowed($action, &$errors = array())
	{
//        switch ($action) {
//            case 'attributeEquipment':
//                if ($this->getData('linked_object_name') === 'commande_line') {
//                    $errors[] = 'L\'attribution d\'équipement doit être faite depuis la page logistique de la commande';
//                    return 0;
//                }
//                break;
//        }

		return (int) parent::isActionAllowed($action, $errors);
	}

	public function isTypeProductAllowed()
	{
		$facture = $this->getParentInstance();

		if (BimpObject::objectLoaded($facture)) {
			$comms = $facture->getCommandesOriginList();
			if (count($comms)) {
				return 0;
			}
		}

		return 1;
	}

	// Getters params:

	public function getListExtraBtnApporteur()
	{
		$buttons = array();

		if ($this->isLoaded() && $this->isNotTypeText()) {
			$tmp = $this->getData('commission_apporteur');
			$tabTmp = explode("-", $tmp);
			$idComm = $tabTmp[0];
			$idFiltre = $tabTmp[1];
			if ($idComm > 0 && $idFiltre > 0) {
				$commission = BimpObject::getInstance('bimpfinanc', 'Bimp_CommissionApporteur', $idComm);
				if ($commission->getData('status') == 0 || $commission->getData('status') == 2) {
					$buttons[] = array(
						'label'   => 'Supprimer de la commission',
						'icon'    => 'fas_trash',
						'onclick' => $commission->getJsActionOnclick('delLine', array('idLn' => $this->id, 'idFiltre' => $idFiltre))
					);
					$buttons[] = array(
						'label'   => 'Changer de commission',
						'icon'    => 'fas_exchange-alt',
						'onclick' => $commission->getJsActionOnclick('changeLine', array('idLn' => $this->id, 'idFiltre' => $idFiltre), array('form_name' => 'change'))
					);
				}
			}
		}
		return $buttons;
	}

	public function getListExtraBtn()
	{
		$buttons = parent::getListExtraBtn();

		if ($this->isLoaded() && $this->isNotTypeText() && BimpCore::isModuleActive('bimpfinanc')) {
			$facture = $this->getParentInstance();
			if (BimpObject::objectLoaded($facture)) {
				$reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');
				$onclick = $reval->getJsLoadModalForm('default', 'Ajout d\\\'une revalorisation', array(
					'fields' => array(
						'id_facture'      => (int) $facture->id,
						'id_facture_line' => (int) $this->id
					)
				));

				$buttons[] = array(
					'label'   => 'Ajouter une revalorisation',
					'icon'    => 'fas_search-dollar',
					'onclick' => $onclick
				);
			}
		}

		return $buttons;
	}

	public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
	{
		switch ($field_name) {
			case 'type_soc':
				$fac_alias = $main_alias . '___facture';
				if (!isset($joins[$fac_alias])) {
					$joins[$fac_alias] = array(
						'alias' => $fac_alias,
						'table' => 'facture',
						'on'    => $fac_alias . '.rowid = ' . $main_alias . '.id_obj'
					);
				}

				$soc_alias = $main_alias . '___client';
				if (!isset($joins[$soc_alias])) {
					$joins[$soc_alias] = array(
						'alias' => $soc_alias,
						'table' => 'societe',
						'on'    => $soc_alias . '.rowid = ' . $fac_alias . '.fk_soc'
					);
				}

				$filters[$soc_alias . '.fk_typent'] = array(
					($excluded ? 'not_' : '') . 'in' => $values
				);
				break;
		}

		return parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
	}

	// Getters Array:

	public function getTypesArray()
	{
		global $current_bc;

		if (is_a($current_bc, 'BC_Form') || is_a($current_bc, 'BC_Field')) {
			if (!$this->isTypeProductAllowed()) {
				return array(
					self::LINE_TEXT => 'Text libre'
				);
			}
		}

		return parent::getTypesArray();
	}

	public function getRevalTypesArray()
	{
		BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');
		return BimpRevalorisation::$types;
	}

	// Getters données:

	public function getPaWithRevalorisations()
	{
		$pa = $this->pa_ht;

		if ($this->isLoaded()) {
			$revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
				'id_facture_line' => (int) $this->id,
				'type'            => 'correction_pa',
				'status'          => array(
					'in' => array(0, 1)
				)
			));

			foreach ($revals as $reval) {
				$pa -= (float) $reval->getData('amount');
			}
		}

		return $pa;
	}

	// Affichages:

	public function displayCommissionApporteur()
	{
		$temp = $this->getData('commission_apporteur');
		$tabT = explode('-', $temp);
		if (isset($tabT[0]) && $tabT[0] > 0) {
			$obj = BimpCache::getBimpObjectInstance('bimpfinanc', 'Bimp_CommissionApporteur', $tabT[0]);
			return $obj->getLink();
		}
		return '';
	}

	public function displayRevalorisations()
	{
		$html = '';
		if ($this->isLoaded()) {
			$total_attente = 0;
			$total_accepted = 0;
			$total_refused = 0;

			$revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
				'id_facture_line' => (int) $this->id
			));

			foreach ($revals as $reval) {
				switch ((int) $reval->getData('status')) {
					case 0:
					case 10:
					case 20:
						$total_attente += (float) $reval->getTotal();
						break;

					case 1:
						$total_accepted += (float) $reval->getTotal();
						break;

					case 2:
						$total_refused += (float) $reval->getTotal();
						break;
				}
			}

			if ($total_attente) {
				$html .= '<span class="warning">';
				$html .= BimpRender::renderIcon('fas_hourglass-start', 'iconLeft');
				$html .= BimpTools::displayMoneyValue($total_attente);
				$html .= '</span>';
			}

			if ($total_accepted) {
				if ($html) {
					$html .= '<br/>';
				}
				$html .= '<span class="success">';
				$html .= BimpRender::renderIcon('fas_check', 'iconLeft');
				$html .= BimpTools::displayMoneyValue($total_accepted);
				$html .= '</span>';
			}

			if ($total_refused) {
				if ($html) {
					$html .= '<br/>';
				}
				$html .= '<span class="danger">';
				$html .= BimpRender::renderIcon('fas_times', 'iconLeft');
				$html .= BimpTools::displayMoneyValue($total_refused);
				$html .= '</span>';
			}
		}

		return $html;
	}

	public function displayPdfAboInfos()
	{
		$html = '';

		if ($this->isLoaded() && $this->qty) {
			$prod = $this->getProduct();
			if (BimpObject::objectLoaded($prod) && $prod->isAbonnement() && $this->date_from && $this->date_to) {
				$prod_duration = (int) $prod->getData('duree');
				$interval = BimpTools::getDatesIntervalData($this->date_from, $this->date_to);
				$nb_monthes = $interval['nb_monthes_decimal'];

				if ($prod_duration && $nb_monthes && $prod_duration) {
					$nb_units = ($this->qty / $nb_monthes) * $prod_duration;

					if ($nb_units) {
						$nb_prod_periods = $nb_monthes / $prod_duration;
						$html .= '<b>' . BimpTools::displayFloatValue($nb_units, 2, ',', 0, 0, 0, 0, 1, 1) . ' unité(s) de ' . $prod_duration . ' mois ' . ($nb_prod_periods > 1 ? ' x ' . BimpTools::displayFloatValue($nb_prod_periods, 6, ',', 0, 0, 0, 0, 1, 1) : '') . '</b>';
					}
				}
			}
		}

		return $html;
	}

	// Rendus HTML:

	public function renderQuickAddForm($bc_list)
	{
		if (!$this->isTypeProductAllowed()) {
			return '';
		}

		return parent::renderQuickAddForm($bc_list);
	}

	// Traitements:

	public function onFactureValidate()
	{
		if ($this->isLoaded()) {
			if ($this->isProductSerialisable()) {
				// Enregistrements des données de la vente dans les équipements:
				$eq_lines = $this->getEquipmentLines();

				foreach ($eq_lines as $eq_line) {
					$equipment = $eq_line->getChildObject('equipment');

					if (BimpObject::ObjectLoaded($equipment)) {
						$pu_ht = (float) $this->getUnitPriceHTWithRemises();
						$pu_ttc = BimpTools::calculatePriceTaxIn($pu_ht, (float) $this->tva_tx);

						$equipment->set('prix_vente', $pu_ttc);
						$equipment->set('vente_tva_tx', (float) $this->tva_tx);
						$equipment->set('date_vente', date('Y-m-d H:i:s'));
						$equipment->set('id_facture', (int) $this->getData('id_obj'));

						if (!static::useLogistique()) {
							$facture = $this->getParentInstance();
							$place = $equipment->getCurrentPlace();
							if (BimpObject::ObjectLoaded($place) && BimpObject::ObjectLoaded($facture)) {
								if ($place->getData('type') != BE_Place::BE_PLACE_CLIENT || $place->getData('id_client') != $facture->getData('fk_soc')) {
									$equipment->moveToPlace(BE_Place::BE_PLACE_CLIENT, $facture->getData('fk_soc'), 'Vente ' . $facture->id, 'Vente : ' . $facture->getRef(), 1);
								}
							}
						}

						$warnings = array();
						$equipment->update($warnings, true);
					}
				}
			}

			$this->checkPrixAchat();
		}
	}

	public function onSave(&$errors = array(), &$warnings = array())
	{
		if ($this->isLoaded()) {
			if ($this->getData('linked_object_name') === 'commande_line') {
				$facture = $this->getParentInstance();

				if (!BimpObject::objectLoaded($facture) || !$facture->areLinesEditable()) {
					return;
				}

				$commLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
				if (BimpObject::objectLoaded($commLine)) {
					$commande = $commLine->getParentInstance();

					if (BimpObject::objectLoaded($commande)) {
						$commande->processFacturesRemisesGlobales();
					}

					if ((int) $commLine->getData('fac_periodicity') > 0) {
						$commLine->checkPeriodicityData('fac');
					}
				}
			} elseif ($this->getData('linked_object_name') === 'location_line' && (int) $this->getData('linked_id_object')) {
				$location_line = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocationLine', (int) $this->getData('linked_id_object'));
				if (BimpObject::objectLoaded($location_line)) {
					$location = $location_line->getParentInstance();
					if (BimpObject::objectLoaded($location)) {
						$location->checkStatus();
					}
				}
			}

			$this->checkPrixAchat();
		}

		parent::onSave($errors, $warnings);
	}

	public function onEquipmentAttributed($id_equipment)
	{
		if ($this->isLoaded()) {
			$facture = $this->getParentInstance();

			if (BimpObject::objectLoaded($facture) && (int) $facture->getData('fk_statut') > 0) {
				$equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
				if (BimpObject::objectLoaded($equipment)) {
					$pu_ht = (float) $this->getUnitPriceHTWithRemises();
					$pu_ttc = BimpTools::calculatePriceTaxIn($pu_ht, (float) $this->tva_tx);

					$equipment->set('prix_vente', $pu_ttc);
					$equipment->set('vente_tva_tx', (float) $this->tva_tx);
					$equipment->set('date_vente', date('Y-m-d H:i:s'));
					$equipment->set('id_facture', (int) $this->getData('id_obj'));

					$warnings = array();
					$equipment->update($warnings, true);
				}


				if (!static::useLogistique()) {
					$place = $equipment->getCurrentPlace();
					if ($place->getData('type') != BE_Place::BE_PLACE_CLIENT || $place->getData('id_client') != $facture->getData('fk_soc')) {
						$equipment->moveToPlace(BE_Place::BE_PLACE_CLIENT, $facture->getData('fk_soc'), 'Vente ' . $facture->id, 'Vente : ' . $facture->getRef(), 1);
					}
				}
			}
		}
	}

	public function checkPrixAchat(&$details = array())
	{
		$errors = array();
		if ($this->isLoaded($errors)) {
			$pa_ht = $this->calcPrixAchat(null, $details);
			$errors = $this->updatePrixAchat($pa_ht);
		}
		return $errors;
	}

	public function calcPrixAchat($date = null, &$details = array(), &$errors = array())
	{
		$pa_ht = (float) $this->pa_ht;
		$fullQty = abs((float) $this->getFullQty());

		if (is_null($date)) {
			$facture = $this->getParentInstance();
			if (BimpObject::objectLoaded($facture)) {
				$date = $facture->getData('datec');
			} else {
				$date = '';
			}
		}

		if ((int) $this->getData('type') === self::LINE_PRODUCT && (int) $this->getData('pa_editable') && $fullQty > 0) {
			$product = $this->getProduct();
			if (BimpObject::objectLoaded($product)) {
				if ($product->isSerialisable()) {
					$cur_pa_ht = null;
					$errors = BimpTools::merge_array($errors, $this->calcPaByEquipments(false, $date, $pa_ht, $cur_pa_ht, $details));
				} else {
					$linked_object_name = $this->getData('linked_object_name');
					$linked_id_object = (int) $this->getData('linked_id_object');

					$def_pa_ht = (float) $this->pa_ht;
					$def_pa_label = '';

					$remain_qty = $fullQty;
					$total_achats = 0;

					$commande_line = null;
					$contrat_line = null;

					if (!(int) $product->getData('no_fixe_prices')) {
						$def_pa_ht = (float) $product->getCurrentPaHt(null, true, $date);
						if ($date) {
							$dt = new DateTime($date);
							$def_pa_label = 'PA courant du produit au ' . $dt->format('d / m / Y');
						} else {
							$def_pa_label = 'PA courant du produit';
						}
					}

					if ($linked_object_name === 'contrat_line') {
						if ($linked_id_object) {
							$contrat_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $linked_id_object);
							if (BimpObject::objectLoaded($contrat_line)) {
								$def_pa_ht = $contrat_line->getPaHtForPeriod($this->date_from, $this->date_to, $def_pa_label);
							}
						}

						if (!$def_pa_ht) {
							$def_pa_ht = (float) $this->pa_ht;
							$def_pa_label = 'PA enregistré dans cette ligne de facture';
						}
					} elseif ($linked_object_name === 'commande_line') {
						if ($linked_id_object) {
							$commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $linked_id_object);
						}

						if ((int) $product->getData('no_fixe_prices') && BimpObject::objectLoaded($commande_line)) {
							$comm_ref = (string) $this->db->getValue('commande', 'ref', 'rowid = ' . (int) $commande_line->getData('id_obj'));

							if ($comm_ref) {
								$def_pa_ht = (float) $commande_line->pa_ht;
								$def_pa_label = 'PA commande client ' . $comm_ref;
							}

							if (!$def_pa_ht) {
								$def_pa_ht = (float) $this->pa_ht;
								$def_pa_label = 'PA enregistré dans cette ligne de facture';
							}
						}

						if (BimpObject::objectLoaded($commande_line)) {
							// Recherche des PA réels dans les factures fourn, BR et commandes fourn.
							$comm_fourn_lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_CommandeFournLine', array(
								'linked_object_name' => 'commande_line',
								'linked_id_object'   => (int) $commande_line->id
							));

							foreach ($comm_fourn_lines as $cf_line) {
								$comm_fourn_data = $this->db->getRow('commande_fournisseur', 'rowid = ' . (int) $cf_line->getData('id_obj'), array('ref', 'fk_statut'), 'array');

								if (is_null($comm_fourn_data)) {
									continue;
								}

								$cf_line_remain_qty = abs((float) $cf_line->qty);
								if ($cf_line_remain_qty > $remain_qty) {
									$cf_line_remain_qty = $remain_qty;
								}

								if (!$cf_line_remain_qty) {
									continue;
								}

								$remain_qty -= $cf_line_remain_qty;
								$cf_line_pu_ht = (float) $cf_line->getUnitPriceHTWithRemises();

								$fac_fourn_lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_FactureFournLine', array(
									'linked_object_name' => 'commande_fourn_line',
									'linked_id_object'   => (int) $cf_line->id
								));

								// Vérification des lignes de factures fourn:
								foreach ($fac_fourn_lines as $ff_line) {
									$ff_line_qty = abs((float) $ff_line->getFullQty());
									if ($ff_line_qty > $cf_line_remain_qty) {
										$ff_line_qty = $cf_line_remain_qty;
									}

									if (!$ff_line_qty) {
										continue;
									}

									$fac_fourn_data = $this->db->getRow('facture_fourn', 'rowid = ' . (int) $ff_line->getData('id_obj'), array('ref', 'fk_statut'), 'array');
									if (!is_null($fac_fourn_data)) {
										$total_achats += ($ff_line->pu_ht * $ff_line_qty);
										$detail = 'PA Facture fournisseur ' . $fac_fourn_data['ref'];
										if ((int) $fac_fourn_data['fk_statut'] === 0) {
											$detail .= ' <span class="warning">(non validée)</span>';
										}
										$detail .= ' pour ' . $ff_line_qty . ' unité(s) : ' . BimpTools::displayMoneyValue((float) $ff_line->pu_ht);
										$details[] = $detail;
										$cf_line_remain_qty -= $ff_line_qty;
									}
								}

								if ($cf_line_remain_qty > 0) {
									// Vérification des réceptions validées non facturées:
									$receptions = $cf_line->getData('receptions');
									foreach ($receptions as $id_reception => $reception_data) {
										if (!isset($reception_data['received']) || !(int) $reception_data['received']) {
											continue;
										}

										$br_values = $this->db->getRow('bl_commande_fourn_reception', 'id = ' . (int) $id_reception, array('num_reception', 'ref', 'status', 'id_facture'), 'array');
										if (!is_null($br_values)) {
											$br_qty = abs((float) $reception_data['qty']);
											if ($br_qty > $cf_line_remain_qty) {
												$br_qty = $cf_line_remain_qty;
											}

											if (!$br_qty) {
												continue;
											}

											// Calcul PA moyen de la réception:
											$br_total_qty = 0;
											$br_total_amount = 0;
											if (isset($reception_data['qties'])) {
												foreach ($reception_data['qties'] as $qty_data) {
													$br_total_qty += abs((float) $qty_data['qty']);
													$pu_ht = (float) (isset($qty_data['pu_ht']) ? $qty_data['pu_ht'] : $cf_line_pu_ht);
													$br_total_amount += (abs((float) $qty_data['qty']) * $pu_ht);
												}
											}

											if ($br_total_qty > 0) {
												$pu_moyen = $br_total_amount / $br_total_qty;
												$detail = 'PA réception n°' . $br_values['num_reception'] . ' - ' . $br_values['ref'];
												$detail .= ' (Commande fournisseur ' . $comm_fourn_data['ref'] . ')';
												$detail .= ' pour ' . $br_qty . ' unité(s) - Moyenne: ' . BimpTools::displayMoneyValue($pu_moyen);
												$details[] = $detail;
												$cf_line_remain_qty -= $br_qty;
												$total_achats += ($pu_moyen * $br_qty);
											}
										}
									}

									// Attribution du PA commande fourn pour les qtés restantes:
									if ($cf_line_remain_qty > 0) {
										$total_achats += ($cf_line_pu_ht * $cf_line_remain_qty);
										$detail = 'PA Commande fournisseur ' . $comm_fourn_data['ref'];
										if ((int) $comm_fourn_data['fk_statut'] === 0) {
											$detail .= ' <span class="warning">(non validée)</span>';
										}
										$detail .= ' pour ' . $cf_line_remain_qty . ' unité(s) : ' . BimpTools::displayMoneyValue($cf_line_pu_ht);
										$details[] = $detail;
									}
								}
							}
						}
					}

					// Attribution du PA par défaut pour les qtés restantes:
					if ($remain_qty > 0) {
						$total_achats += ($def_pa_ht * $remain_qty);

						if (!$linked_object_name || $linked_object_name !== 'contrat_line') {
							$details[] = $def_pa_label . ' pour ' . $remain_qty . ' unité(s) : ' . BimpTools::displayMoneyValue($def_pa_ht);
						} else {
							$details[] = $def_pa_label;
						}
					}

					$pa_ht = $total_achats / $fullQty;
				}
			}
		} else {
			$details[] = 'PA enregistré dans la ligne de facture : ' . BimpTools::displayMoneyValue((float) $this->pa_ht);
		}

		return $pa_ht;
	}

	public function findValidPrixAchat($date = '')
	{
		if (!$date) {
			$date = $this->getData('datec');
		}

		$details = array();
		$pa_ht = (float) $this->calcPrixAchat($date, $details);

		return array(
			'pa_ht'  => $pa_ht,
			'origin' => BimpTools::getMsgFromArray($details)
		);
	}

	public function updatePrixAchat($new_pa_ht)
	{
		$errors = array();

		if ($this->isLoaded($errors)) {
			$qty = (float) $this->getFullQty();
			if ($qty) {
				$facture = $this->getParentInstance();

				if (!BimpObject::objectLoaded($facture)) {
					$errors[] = 'ID de la facture absent';
				} else {
					// Création de revalorisations si facture commissionnée / Màj directe en base sinon.
					if ((int) $facture->getData('id_user_commission') || (int) $facture->getData('id_entrepot_commission')) {
						$total_reval = ((float) $this->pa_ht - (float) $new_pa_ht) * $qty;

						// Check des revals existantes:
						$revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
							'id_facture'      => (int) $facture->id,
							'id_facture_line' => (int) $this->id,
							'type'            => 'correction_pa'
						));

						foreach ($revals as $reval) {
							// Déduction du montant des revals validées / suppr. des autres.
							if ((int) $reval->getData('status') === 1) {
								$total_reval -= (float) $reval->getTotal();
							} else {
								$w = array();
								$del_errors = $reval->delete($w, true);
								if (count($del_errors)) {
									$total_reval -= (float) $reval->getTotal();
								}
							}
						}

						if ($total_reval) {
							$reval_amount = ($total_reval / $qty);

							// Créa nouvelle revalorisation:
							$reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');
							$reval_errors = $reval->validateArray(array(
								'id_facture'      => (int) $facture->id,
								'id_facture_line' => (int) $this->id,
								'type'            => 'correction_pa',
								'qty'             => (float) $qty,
								'amount'          => (float) $reval_amount,
								'date'            => date('Y-m-d'),
								'note'            => 'Correction du prix d\'achat après ajout de la facture à une commission (Nouveau prix d\'achat: ' . $new_pa_ht . ')'
							));

							if (!count($reval_errors)) {
								$reval_warnings = array();
								$reval_errors = $reval->create($reval_warnings, true);
							}

							if (count($reval_errors)) {
								$errors[] = BimpTools::getMsgFromArray($reval_errors, 'Echec de la création ' . $reval->getLabel('of_the'));
							}
						}
					} else {
						return parent::updatePrixAchat($new_pa_ht);
					}
				}
			}
		}

		return $errors;
	}

	public function checkRemisesArrieres(&$errors = array(), &$infos = array(), $force_facture = false, $recreate = false)
	{
		if (!$this->isLoaded() || !$this->qty) {
			return;
		}

		$facture = $this->getParentInstance();
		if (!BimpObject::objectLoaded($facture)) {
			$errors[] = 'Ligne #' . $this->id . ' : facture absente';
			return;
		}

		if (!$force_facture && !in_array((int) $facture->getData('fk_statut'), array(1, 2))) {
			return;
		}

		$remises_arrieres = $this->getRemisesArrieres();
		$tot_ra = array(
			'crt'       => 0,
			'applecare' => 0,
			'oth'       => array(
				'amount' => 0,
				'label'  => ''
			)
		);

		if (!empty($remises_arrieres)) {
			foreach ($remises_arrieres as $remise_arriere) {
				$ra_type = $remise_arriere->getData('type');

				if ($ra_type == 'oth') {
					$tot_ra[$ra_type]['amount'] += ($remise_arriere->getRemiseAmount() * $this->qty);
					$tot_ra[$ra_type]['label'] .= ($tot_ra[$ra_type]['label'] ? ' / ' : '') . $remise_arriere->getData('label');
				} else {
					$tot_ra[$ra_type] += ($remise_arriere->getRemiseAmount() * $this->qty);
				}
			}
		}

		$dt = new DateTime($facture->getData('datec'));
		foreach ($tot_ra as $type_ra => $ra_data) {
			$ra_amount = 0;
			$ra_label = '';
			$tot_reval = 0;

			if (is_array($ra_data)) {
				$ra_label = $ra_data['label'];
				$ra_amount = $ra_data['amount'];
			} else {
				$ra_amount = $ra_data;
			}

			$revals_for_type = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
				'id_facture'      => (int) $facture->id,
				'id_facture_line' => (int) $this->id,
				'type'            => $type_ra,
				'status'          => array(
					'operator' => '<',
					'value'    => 2
				)
			));

			foreach ($revals_for_type as $reval) {
				$tot_reval += (float) $reval->getTotal();
			}

			if (round($tot_reval, 4) != round($ra_amount, 4)) {
				$reval_errors = array();

				if ($recreate) {
					$serials = array();
					$equipments = array();
					$reval_infos = array();
					foreach ($revals_for_type as $rev) {
						$rev_serials = $rev->getData('serial');

						if ($rev_serials) {
							$rev_serials = str_replace(array(',', ';', "\n", "\t"), array(' ', ' ', ' ', ' '), $serials);
							$rev_serials = explode(' ', $rev_serials);

							foreach ($rev_serials as $rev_serial) {
								if (!in_array($rev_serial, $serials)) {
									$serials[] = $rev_serial;
								}
							}
						}

						$rev_equipments = $rev->getData('equipments');
						if (!empty($rev_equipments)) {
							foreach ($rev_equipments as $id_eq) {
								if (!in_array($id_eq, $equipments)) {
									$equipments[] = $id_eq;
								}
							}
						}

						$rev_w = array();
						$rev_info = 'Reval #' . $rev->id . ' supprimée (Montant total ' . $rev->getTotal() . ' - Type : ' . BimpRevalorisation::$types[$rev->getData('type')] . ')';
						$rev_errors = $rev->delete($rev_w, true);
						if (count($rev_errors)) {
							$reval_errors[] = BimpTools::getMsgFromArray($rev_errors, 'Echec de la suppression de la reval #' . $rev->id);
						} else {
							$reval_infos[] = $rev_info;
						}
					}

					if (!empty($reval_infos)) {
						$infos[] = BimpTools::getMsgFromArray($reval_infos, 'Ligne n° ' . $this->getData('position'));
					}

					if (!count($reval_errors)) {
						$reval = BimpObject::createBimpObject('bimpfinanc', 'BimpRevalorisation', array(
							'id_facture'      => (int) $facture->id,
							'id_facture_line' => (int) $this->id,
							'type'            => $type_ra,
							'date'            => $dt->format('Y-m-d'),
							'amount'          => ($ra_amount / $this->qty),
							'qty'             => (float) $this->qty,
							'note'            => $ra_label,
							'equipments'      => $equipments,
							'serials'         => implode("\n", $serials)
						), true, $reval_errors);

						if (count($reval_errors)) {
							$errors[] = BimpTools::getMsgFromArray($reval_errors, 'Ligne n° ' . $this->getData('position') . ' : échec de la création d\'une nouvelle revalorisation de type "' . BimpRevalorisation::$types[$type_ra]) . '"' . ($ra_label ? ' (remise arrière "' . $ra_label . '")' : '');
						} else {
							$infos[] = 'Ligne n° ' . $this->getData('position') . ' recréation complète de la revalorisation de type "' . BimpRevalorisation::$types[$type_ra] . '" - Montant total : ' . $ra_amount;
						}
					}
				} else {
					$diff = $ra_amount - $tot_reval;
					$reval = BimpCache::findBimpObjectInstance('bimpfinanc', 'BimpRevalorisation', array(
						'id_facture'      => (int) $facture->id,
						'id_facture_line' => (int) $this->id,
						'type'            => $type_ra,
						'status'          => 0
					), true);

					if (!BimpObject::objectLoaded($reval) || (float) $reval->getData('qty') !== (float) $this->qty) {
						$reval = BimpObject::createBimpObject('bimpfinanc', 'BimpRevalorisation', array(
							'id_facture'      => (int) $facture->id,
							'id_facture_line' => (int) $this->id,
							'type'            => $type_ra,
							'date'            => $dt->format('Y-m-d'),
							'amount'          => ($diff / $this->qty),
							'qty'             => (float) $this->qty,
							'note'            => $ra_label
						), true, $reval_errors);

						if (count($reval_errors)) {
							$errors[] = BimpTools::getMsgFromArray($reval_errors, 'Ligne n° ' . $this->getData('position') . ' : échec de la création d\'une nouvelle revalorisation de type "' . BimpRevalorisation::$types[$type_ra]) . '"' . ($ra_label ? ' (remise arrière "' . $ra_label . '")' : '');
						} else {
							$infos[] = 'Ligne n° ' . $this->getData('position') . ' création d\'une revalorisation de type "' . BimpRevalorisation::$types[$type_ra] . '" - Montant : ' . ($diff / $this->qty);
						}
					} else {
						$reval_amount = ($reval->getTotal() + $diff) / (float) $reval->getData('qty');

						$reval->set('amount', $reval_amount);
						$reval_errors = $reval->update($reval_warnings, true);

						if (count($reval_errors)) {
							$errors[] = BimpTools::getMsgFromArray($reval_errors, 'Ligne n° ' . $this->getData('position') . ' : échec de la mise à jour de la revalorisation de type "' . BimpRevalorisation::$types[$type_ra]) . '"' . ($ra_label ? ' (remise arrière "' . $ra_label . '")' : '');
						} else {
							$infos[] = 'Ligne n° ' . $this->getData('position') . ' mise à jour de la revalorisation de type "' . BimpRevalorisation::$types[$type_ra] . '" - Nouveau montant : ' . $reval_amount;
						}
					}
				}
			}
		}
	}

// Actions:

	public
	function actionBulkCreateRevalorisation($data, &$success)
	{
		$errors = array();
		$warnings = array();
		$success = '';

		$id_lines = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

		if (!count($id_lines)) {
			$errors[] = 'Aucune lignes de factures à traiter';
		} else {
			$type = BimpTools::getArrayValueFromPath($data, 'type', '', $errors, 1, 'Type de revalorisation absent');
			$date = BimpTools::getArrayValueFromPath($data, 'date', date('Y-m-d'));
			$amount_type = BimpTools::getArrayValueFromPath($data, 'amount_type', '', $errors, 1, 'Type de montant absent');
			$amount = BimpTools::getArrayValueFromPath($data, 'amount', null);
			$note = BimpTools::getArrayValueFromPath($data, 'note', '');

			if (is_null($amount)) {
				$errors[] = 'Aucun montant spécitifé';
			}

			$nOK = 0;
			if (!count($errors)) {
				foreach ($id_lines as $id_line) {
					$line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', $id_line);

					if (!BimpObject::objectLoaded($line)) {
						$warnings[] = 'La ligne de facture d\'ID ' . $id_line . ' n\'existe plus';
						continue;
					}

					$facture = $line->getParentInstance();

					if (!BimpObject::objectLoaded($facture)) {
						$warnings[] = 'Aucune facture trouvée pour la ligne de facture #' . $id_line;
						continue;
					}

					$reval_amount = 0;

					switch ($amount_type) {
						case 'new_pa':
							$pa_ht = (float) $line->getPaWithRevalorisations();
							$reval_amount = $pa_ht - $amount;
							break;

						default:
						case 'reval':
							$reval_amount = $amount;
							break;
					}
					$rev_errors = array();
					$rev_warnings = array();

					BimpObject::createBimpObject('bimpfinanc', 'BimpRevalorisation', array(
						'id_facture'      => (int) $facture->id,
						'id_facture_line' => (int) $line->id,
						'type'            => $type,
						'date'            => $date,
						'amount'          => $reval_amount,
						'qty'             => (float) $line->getFullQty(),
						'note'            => $note
					), true, $rev_errors, $rev_warnings);

					if (count($rev_errors)) {
						$warnings[] = BimpTools::getMsgFromArray($rev_errors, 'Facture "' . $facture->getRef() . '" - Ligne n°' . $line->getData('position'));
					} else {
						$nOK++;
					}

					if (count($rev_warnings)) {
						$warnings[] = BimpTools::getMsgFromArray($rev_warnings, 'Facture "' . $facture->getRef() . '" - Ligne n°' . $line->getData('position'));
					}
				}

				if ($nOK > 0) {
					$success = $nOK . ' revalorisation(s) créée(s) avec succès';
				}
			}
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}

// Overrides:

	public
	function create(&$warnings = array(), $force_create = false)
	{
		$errors = array();
		$details = array();

		$this->pa_ht = (float) $this->calcPrixAchat(date('Y-m-d H:i:s'), $details, $errors);
		$this->id_fourn_price = 0;

		if (count($errors)) {
			return $errors;
		}

		return parent::create($warnings, $force_create);
	}

	public
	function delete(&$warnings = array(), $force_delete = false)
	{
		$commLine = null;
		$contratLine = null;
		$location_line = null;
		$id_facture = (int) $this->getData('id_obj');

		if ($this->isLoaded()) {
			if ($this->getData('linked_object_name') === 'commande_line' && (int) $this->getData('linked_id_object')) {
				$commLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('linked_id_object'));
			}
			if ($this->getData('linked_object_name') === 'contrat_line' && (int) $this->getData('linked_id_object')) {
				$contratLine = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $this->getData('linked_id_object'));
			}
			if ($this->getData('linked_object_name') === 'location_line' && (int) $this->getData('linked_id_object')) {
				$location_line = BimpCache::getBimpObjectInstance('bimplocation', 'BimpLocationLine', (int) $this->getData('linked_id_object'));
			}
		}

		$errors = parent::delete($warnings, $force_delete);

		if (!count($errors)) {
			$prevDeleting = $this->isDeleting;
			$this->isDeleting = true;

			if (BimpObject::objectLoaded($commLine)) {
				$commLine->onFactureDelete($id_facture);
			}

			if (BimpObject::objectLoaded($contratLine)) {
				$contratLine->onFactureDelete($id_facture);
			}

			if (BimpObject::objectLoaded($location_line)) {
				$location = $location_line->getParentInstance();
				if (BimpObject::objectLoaded($location)) {
					$location->checkStatus();
				}
			}

			$this->isDeleting = $prevDeleting;
		}

		return $errors;
	}
}

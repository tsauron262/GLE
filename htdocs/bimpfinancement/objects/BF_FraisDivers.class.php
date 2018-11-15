<?php 

	class BF_FraisDivers extends BimpObject {

		public function displayFacture() {
			global $langs;
			if($this->isLoaded()) {
				$id_facture = $this->getData('id_facture');
				BimpTools::loadDolClass('compta/facture', 'facture');
				$facture = new Facture($this->db->db);
				$facture->fetch($id_facture);
				if($id_facture) {
					return $facture->getNomUrl();
				} else {
					return $langs->trans('erreurInvoiceExistFrais');
				}
			} else {
				return $langs->trans('erreurLoadedObject');
			}
		}

		public function canEdit() {
			$id_facture = $this->getData('id_facture');
			$facture = BimpObject::getInstance('bimpcommercial', 'Bimp_facture');
			if($id_facture > 0) {
				$facture->fetch($id_facture);
				if(!$facture->isDeletable()) {
					return 0;
				}
			} 
			return 1;
		}

		public function canDelete() {
			return $this->canEdit();
		}

		public function displayExtraButton() {
			global $langs, $user;
			$buttons = array();
			$callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
			$id_facture = (int) $this->getData('id_facture');
			if($id_facture == 0) {
				$buttons[] = array(
		            'label'   => 'Créer une facture',
		            'icon'    => 'fas_plus',
		            'onclick' => $this->getJsActionOnclick('genFacture', array(
		                'file_type' => 'pret'
		                    ), array(
		                'success_callback' => $callback
		            ))
		        );
			} else {
				$facture = BimpObject::getInstance('bimpcommercial', 'Bimp_facture');
				$facture->fetch($id_facture);
				if($facture->isDeletable()) {
					
					$buttons[] = array(
						'label' => 'Mettre à jour la facture',
						'icon' => 'fas_sync-alt',
						'onclick' => $this->getJsActionOnclick('updFacture', array(
							'file_type' => 'pret'
								), array(
							'success_callback' => $callback
 						))
					);
					$buttons[] = array(
						'label' => 'Supprimer la facture',
						'icon' => 'fas_times',
						'onclick' => $this->getJsActionOnclick('delFacture', array(
							'file_type' => 'pret'
								), array(
							'success_callback' => $callback
 						))
					);
				}
				
			}
			return $buttons;
		}

		public function actiongenFacture() {
			if($this->isLoaded()) {
				global $langs, $user;
				$id = (int) $this->getData('id');
				$id_demande = (int) $this->getData('id_demande');
				BimpTools::loadDolClass('compta/facture', 'facture');
				$facture = new Facture($this->db->db);
				$facture->socid = $this->db->getValue('bf_demande', 'id_client', '`id` = ' . $id_demande);
				$facture->date = $this->getData('date');
				if($facture->create($user) > 0) {
					$this->updateField('id_facture', (int) $facture->id);
					$description = "Frais divers : " . $this->getData('description') . ' pour la demande de financement N°DF' . $this->getData('id_demande');
					$total = $this->getData('amount');
					$facture->addline($description, $total, 1, 0);
					$success = $langs->trans('successInvoiceCreate');
				} else {
					return $facture->error;
				}
			} else {
				$errors[] = $langs->trans('erreurFraisId');
			}
			
			return array(
				'errors' => $errors,
				'success' => $success,
			);
		}
		public function actionupdFacture() {
			if($this->isLoaded()) {
				global $langs, $user;
				$id_facture = $this->getData('id_facture');
				BimpTools::loadDolClass('compta/facture', 'facture');
				$facture = new Facture($this->db->db);
				$facture->fetch($id_facture);
				$facture->date = $this->getData('date');
				if($facture->update($user) > 0) {
					$line = $this->db->getValue('facturedet', 'rowid', '`fk_facture` = ' . $facture->id);
					$description = "Frais divers : '" . $this->getData('description') . "' pour la demande de financement N°DF" . $this->getData('id_demande');
					$total = $this->getData('amount');
					$facture->updateline($line, $description, $total, 1, 0, $facture->date, $dacture->date, 0);
					$success = $langs->trans('successInvoiceUpdate');
				} else {
					return $facture->error;
				}
			} else {
				$errors[] = $langs->trans('erreurFraisId');
			}
			return array(
				'success' => $success,
				'errors' => $errors
			);
		}

		public function actiondelFacture() {
			if($this->isLoaded()) {
				global $langs;
				$id_facture = (int) $this->getData('id_facture');
				BimpTools::loadDolClass('compta/facture', 'facture');
				$facture = new Facture($this->db->db);
				$facture->fetch($id_facture);
				if($facture->delete($user) > 0) {
					$this->updateField('id_facture', (int) 0);
					$success = $langs->trans('successInvoiceDelete');
				} else {
					return $facture->error;
				}
			} else {
				$errors[] = $langs->trans('erreurFraisId');
			}
			return array(
					'success' => $success,
					'errors' => $errors
				);
		}

	}
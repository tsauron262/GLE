<?php 

	class BRH_FraisInvite extends BimpObject {

		public static $typePersonne = array(
			1 => array('label' => 'EXTERNE', 'classes' => array('danger'), 'icon' => ''),
			2 => array('label' => 'INTERNE', 'classes' => array('warning'), 'icon' => ''),
		);

		public function showInvite() {
			global $db;
			$BimpDb = new BimpDb($db);
			if(!$this->getData('name')) {
			 	$lastname = $BimpDb->getValue('user', 'lastname', '`rowid` = ' . $this->getData('id_user'));
			 	$firstname = $BimpDb->getValue('user', 'firstname', '`rowid` = ' . $this->getData('id_user'));
			 	return $lastname . " " . $firstname;
			 } else {
			 	return $this->getData('name');
			}
		}

		public function getListeMontant() {
			global $db;
			$return = array();
			$BimpDb = new BimpDb($db);
			$id_frais = $this->getData('id_frais');
			$result = $BimpDb->getRows('bnf_frais_montant', '`id_frais` = ' . $id_frais);
			foreach ($result as $amount) {
				$return[$amount->id] = array('label' => "Frais <b>#" . $amount->id . ' d\'un montant de ' . $amount->amount . '€', 'classes' => array(''));
			}
			return $return;
		}

		public function showMontant() {
			return '<b>#' . $this->getData('id_montant') . "</b>";
		}


	}
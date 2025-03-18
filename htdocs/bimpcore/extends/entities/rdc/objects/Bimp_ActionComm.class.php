<?php

class Bimp_ActionComm_ExtEntity extends Bimp_ActionComm {
	public function onSave(&$errors = [], &$warnings = [])
	{
		$id = BimpTools::getPostFieldValue('id');
		$fk_action = $this->getData('fk_action');
		$maj = $this->db->getValue('c_actioncomm', 'maj_dercontact_rdc', 'id = ' . $fk_action);
		if ($id && $maj && $this->isLoaded()) {
			$now = new DateTime();
			$dateActionComm = new DateTime(BimpTools::getPostFieldValue('datep'));
			$difference = $now->diff($dateActionComm);

			if ($difference->invert == 1) { // if the date is in the past => update date_der_contact
				$sql = "UPDATE " . MAIN_DB_PREFIX . "societe_rdc SET date_der_contact = '" . $dateActionComm->format('Y-m-d') . "' WHERE fk_soc = " . $id;
				$this->db->execute($sql);
			}
		}

		parent::onSave($errors, $warnings);
	}
}

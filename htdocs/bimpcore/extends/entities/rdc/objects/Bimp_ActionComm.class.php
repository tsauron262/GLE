<?php

class Bimp_ActionComm_ExtEntity extends Bimp_ActionComm {
	public function onSave(&$errors = [], &$warnings = [])
	{
		$id = BimpTools::getPostFieldValue('id');
		if ($id && $this->isLoaded()) {
			$actions_date_der_contact = explode(',', BimpCore::getConf('actions_date_der_contact'));
			$fk_action = BimpTools::getPostFieldValue('fk_action');
			if (in_array($fk_action, $actions_date_der_contact)) {
				$now = new DateTime();
				$dateActionComm = new DateTime(BimpTools::getPostFieldValue('datep'));
				$difference = $now->diff($dateActionComm);

				if ($difference->invert == 1) { // if the date is in the past => update date_der_contact
					$sql = "UPDATE " . MAIN_DB_PREFIX . "societe_rdc SET date_der_contact = '" . $dateActionComm->format('Y-m-d') . "' WHERE fk_soc = " . $id;
					$this->db->execute($sql);
				}
			}
		}

		parent::onSave($errors, $warnings);
	}
}

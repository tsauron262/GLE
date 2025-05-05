<?php

class Bimp_ActionComm_ExtEntity extends Bimp_ActionComm {
	public static $motifEchange = array(
		'0' => array('label' => 'N/A', 'classes' => array('danger')),
//		'8' => array('label' => 'Prospection'),
		'1' => array('label' => 'Résolution de problème'),
		'2' => array('label' => 'Offre commerciale'),
		'3' => array('label' => 'Suivi'),
		'4' => array('label' => 'Qualité'),
		'5' => array('label' => 'Contrefaçon'),
		'6' => array('label' => 'Réclamation')
	);

	public static $c_actioncomm_AfficheEchange = array('1', '4', '14'); // 'AC_TEL', 'AC_EMAIL', 'AC_CHAT'

	public function getIdsAfficheEchange()
	{
		return self::$c_actioncomm_AfficheEchange;
	}

	public function getAfficheEchange()
	{
		return (int)in_array($this->getData('fk_action'), self::$c_actioncomm_AfficheEchange);
	}

	public function getTypesArray($include_empty = false)
	{
		$cache_key = 'action_comm_types_values_array';

		if (!isset(self::$cache[$cache_key])) {
			$rows = $this->db->getRows('c_actioncomm', 'active = 1', null, 'array', array('id', 'icon', 'libelle'), 'position', 'asc');

			if (is_array($rows)) {
				foreach ($rows as $r) {
					self::$cache[$cache_key][(int) $r['id']] = array('label' => $r['libelle'], 'icon' => $r['icon']);
				}
			}
		}

		return self::getCacheArray($cache_key, $include_empty);
	}

	public function getUsersAssigned()
	{
		global $user;
		$users = array();
		foreach ($this->dol_object->userassigned as $userassigned) {
			$users[] = $userassigned['id'];
		}
		if (!count($users)) {
			$users = BimpTools::getPostFieldValue('param_values/fields/users_assigned', array(), 'array');
			if (!count($users)) {
				$id = BimpTools::getPostFieldValue('id');
				$soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id);
				if ($soc->isLoaded() &&  $soc->getData('fk_user_attr_rdc')) {
					$users[] = $soc->getData('fk_user_attr_rdc');
				}
				else $users[] = $user->id;
			}
		}
		return $users;
	}

	public function create(&$warnings = array(), $force_create = false)
	{
		if (!$this->getData('datep2')) {
			$this->set('datep2', $this->getData('datep'));
		}

		return parent::create($warnings, $force_create);
	}

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
				$sql = "UPDATE " . MAIN_DB_PREFIX . "societe SET date_der_contact = '" . $dateActionComm->format('Y-m-d') . "' WHERE rowid = " . $id;
				$this->db->execute($sql);
			}
		}

		parent::onSave($errors, $warnings);
	}

}

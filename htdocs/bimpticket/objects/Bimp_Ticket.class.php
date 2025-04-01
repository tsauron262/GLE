<?php
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class Bimp_Ticket extends BimpDolObject
{

	const STATUS_DRAFT = 0;
	const STATUS_READ = 1;
	const STATUS_ASSIGNED = 2;
	const STATUS_IN_PROGRESS = 3;
	const STATUS_NEED_MORE_INFO = 5;
	const STATUS_WAITING = 7;
	const STATUS_CLOSED = 8;
	const STATUS_CANCELED = 9;
	const STATUS_TRANSFERED = 11;

	public static $status_list = array(
		self::STATUS_DRAFT       => array('label' => 'Nouveau', 'icon' => 'fas_file-alt', 'classes' => array('info')),
//		self::STATUS_READ        => array('label' => 'Lu', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
		self::STATUS_ASSIGNED    => array('label' => 'Assigné', 'icon' => 'fas_user-check', 'classes' => array('info')),
		self::STATUS_IN_PROGRESS => array('label' => 'En cours', 'icon' => 'fas_cogs', 'classes' => array('warning')),
		self::STATUS_WAITING     => array('label' => 'En attente', 'icon' => 'fas_check', 'classes' => array('warning')),
		self::STATUS_CLOSED      => array('label' => 'Terminé', 'icon' => 'fas_check', 'classes' => array('success')),
		self::STATUS_CANCELED    => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('danger')),
		self::STATUS_TRANSFERED  => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('important')),
	);

	public static $types = array();

	// Getters params:

	public function getHeaderButtons()
	{
		$buttons = array();
		return $buttons;
	}

	// Getters Array
	public function getClientContactsArray($include_empty = true, $active_only = true)
	{
		$id_client = (int) $this->getData('id_client');

		if ($id_client) {
			return self::getSocieteContactsArray($id_client, $include_empty, '', $active_only);
		}

		return array();
	}

	// Overrides :

	public function create(&$warnings = array(), $force_create = false)
	{
		$this->set('ref', $this->dol_object->getDefaultRef());

		return parent::create($warnings, $force_create);
	}
}

<?php

class BTK_Ticket extends BimpObject
{

	const STATUS_CANCELED = -1;
	const STATUS_DRAFT = 0;
	const STATUS_ONGOING = 1;
	const STATUS_CLOSED = 2;
	const STATUS_TRANSFERED = 3;


	public static $status_list = array(
		self::STATUS_CANCELED   => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('danger')),
		self::STATUS_DRAFT      => array('label' => 'Nouveau', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
		self::STATUS_ONGOING    => array('label' => 'En cours', 'icon' => 'fas_cogs', 'classes' => array('info')),
		self::STATUS_CLOSED     => array('label' => 'Terminé', 'icon' => 'fas_check', 'classes' => array('success')),
		self::STATUS_TRANSFERED => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('important')),
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
}

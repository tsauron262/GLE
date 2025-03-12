<?php

class Bimp_Client_ExtEntity extends Bimp_Client
{
	static public function getUserGroupsArray($include_empty = 1, $nom_url = 0)
	{
		$grouparray = array(
			BimpCore::getUserGroupId('BD'),
			BimpCore::getUserGroupId('KAM'),
		);

		$cache_key = 'users_groups';
		if ($nom_url) {
			$cache_key .= '_nom_url';
		}

		$cache_key .= '_array';

		if (!isset(self::$cache[$cache_key])) {
			$rows = self::getBdb()->getRows('usergroup', 'rowid IN (' . implode(', ', $grouparray) . ')', null, 'object', array('rowid', 'nom'), 'nom', 'asc');
			if (!is_null($rows)) {
				$icon = BimpRender::renderIcon('fas_users', 'iconLeft');
				foreach ($rows as $r) {
					if ($nom_url) {
						$url = BimpTools::getDolObjectUrl('UserGroup', $r->rowid);
						self::$cache[$cache_key][$r->rowid] = '<a href="' . $url . '" target="_blank">' . $icon . $r->nom . '</a>';
					} else {
						self::$cache[$cache_key][$r->rowid] = $r->nom;
					}
				}
			}
		}
		return self::getCacheArray($cache_key, $include_empty);
	}

	public function getActionsButtons()
	{
		$buttons = array();

		$shopid = (int) $this->getData('shopid');
		if ($shopid > 0) {
			$buttons[] = array(
				'label'   => 'Mettre à jour depuis Mirakl',
				'icon'    => 'fas_sync',
				'onclick' => $this->getJsActionOnclick('refreshS20')
			);
		} else {
			$buttons[] = array(
				'label'   => 'Mettre à jour depuis Mirakl',
				'icon'    => 'fas_sync',
				'onclick' => '',
				'disabled' => 1,
				'popover' => 'ShopId non renseigné'
			);
		}

		$buttons[] = array(
			'label'   => 'Passer le statut à ...',
			'icon'    => 'fas_edit',
			'onclick' => $this->getJsActionOnclick('XX')
		);

		$buttons[] = array(
			'label'   => 'Créer un ticket ...',
			'icon'    => 'fas_ticket-alt',
			'onclick' => $this->getJsActionOnclick('XXX')
		);

		$buttons[] = array(
			'label'   => 'autre ... selon le contexte et les droits',
			'icon'    => 'fas_edit',
			'onclick' => $this->getJsActionOnclick('XXXX')
		);

		$groups = array();
		if (!empty($buttons)) {
			$groups[] = array(
				'label'   => 'Actions',
				'icon'    => 'fas_cogs',
				'buttons' => $buttons
			);
		}

		if (!empty($groups)) {
			return array(
				'buttons_groups' => $groups,
			);
		}

		return array();
	}

	public function renderHeaderStatusExtra()	{
		return '';
	}

	public function getStatusProperty()	{
		return '';
	}

	public function getUserAttrByGroupArray()	{
		$idGroup = BimpTools::getPostFieldValue('fk_group', $this->getData('fk_group'), 'int');
		$rows = self::getBdb()->getRows(
			'user AS u',
			'ug.fk_usergroup=' . $idGroup,
			null,
			'object',
			array('u.rowid', 'CONCAT(u.lastname, \' \', u.firstname) AS nomComplet'),
			'u.lastname', 'asc',
			array(
				'ug' => array(
					'table' => 'usergroup_user',
					'on'    => 'u.rowid = ug.fk_user',
				)
			)
		);
		$users = array(0 => '');
		foreach ($rows as $row) {
			$users[$row->rowid] = $row->nomComplet;
		}
		return $users;
	}

}

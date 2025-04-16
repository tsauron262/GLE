<?php

/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *    \defgroup   mymodule     Module MyModule
 *  \brief      MyModule module descriptor.
 *
 *  \file       htdocs/bimpstatsfacture/core/modules/BimpStatsFacture.class.php
 *  \ingroup    bimpstatsfacture
 *  \brief      Descriptor for the module product bimpstatsfacture
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart

/**
 *  Description and activation class for module MyModule
 */
class modBimpticket extends DolibarrModules
{

	// @codingStandardsIgnoreEnd
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 3508000;
		$this->rights_class = 'bimpticket';
		$this->family = "Bimp";
		$this->module_position = 500;
		$this->name = 'bimpticket';
		$this->description = "Gestion du ticketing";
		$this->descriptionlong = "Gestion du ticketing";
		$this->editor_url = '';
		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto = 'generic';

		$this->module_parts = array(
			'hooks'  => array('printTopRightMenu', 'toprightmenu', "searchform", "emailcolector"),  // Set here all hooks context you want to support)
			"models" => 1
		);

		$this->tabs = array();

		// Permissions
		$this->rights = array();  // Permission array used by this module
		$r = 0;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Assigner les tickets'; // Permission label
		$this->rights[$r][3] = 0;      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'assign';    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		$this->rights[$r][5] = '';        // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		$r++;

		$this->menu = array();
	}

	/**
	 *        Function called when module is enabled.
	 *        The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *        It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return     int                1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$sql = array();

		if (!defined('BIMP_LIB')) {
			require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
		}

		if (!BimpCore::isModuleActive('ticket')) {
			setEventMessage('Module dolibarr "ticket" non installé. Veuillez l\'installer préalablement', 'errors');
			return 0;
		}

		$errors = array();
		$bdb = BimpCache::getBdb();

		$name = 'module_version_' . strtolower($this->name);

		if (BimpCore::getConf($name, '') === "") {
			BimpCore::setConf($name, floatval($this->version));
			if (!$bdb->executeFile(DOL_DOCUMENT_ROOT . '/' . strtolower($this->name) . '/sql/install.sql')) {
				setEventMessage('Echec exécution du fichier install.sql - ' . $bdb->err(), 'errors');
			}
		}

		if (!(int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'bimp_ticket_types\'')) {
			$dict = BimpDict::addDolDictionnary('bimp_ticket_types', 'Types de ticket', 'c_ticket_type', 45, array(
				'use_default' => array('type' => 'bool', 'default' => 0),
				'description' => array('default' => '')
			), array(
				'key_field' => 'code',
				'key_data_type' => 'string',
				'position_field' => 'pos',
			), $errors);

			if (BimpObject::objectLoaded($dict)) {
				$bdb->delete('c_ticket_type', '1', true);
				if ($bdb->insert('c_ticket_type', array(
						'code'        => 'INT',
						'label'       => 'Intégration',
						'use_default' => 0,
						'pos'         => '01'
					)) <= 0) {
					$errors[] = 'Echec insertion type ticket - ' . $bdb->err();
				}
				$bdb->insert('c_ticket_type', array(
					'code'        => 'OFF',
					'label'       => 'Offres',
					'use_default' => 0,
					'pos'         => '02'
				));
				$bdb->insert('c_ticket_type', array(
					'code'        => 'CM',
					'label'       => 'Création marque',
					'use_default' => 0,
					'pos'         => '03'
				));
				$bdb->insert('c_ticket_type', array(
					'code'        => 'LIV',
					'label'       => 'Livraison',
					'use_default' => 0,
					'pos'         => '04'
				));
				$bdb->insert('c_ticket_type', array(
					'code'        => 'FDP',
					'label'       => 'Frais de port',
					'use_default' => 0,
					'pos'         => '05'
				));
				$bdb->insert('c_ticket_type', array(
					'code'        => 'ERRPROD',
					'label'       => 'Erreur fiche produit',
					'use_default' => 0,
					'pos'         => '06'
				));
				$bdb->insert('c_ticket_type', array(
					'code'        => 'API',
					'label'       => 'Problèmes API',
					'use_default' => 0,
					'pos'         => '07'
				));
				$bdb->insert('c_ticket_type', array(
					'code'        => 'PROMO',
					'label'       => 'Promotion',
					'use_default' => 0,
					'pos'         => '08'
				));
				$bdb->insert('c_ticket_type', array(
					'code'        => 'SOL',
					'label'       => 'Solde',
					'use_default' => 0,
					'pos'         => '09'
				));
				$bdb->insert('c_ticket_type', array(
					'code'        => 'OTHER',
					'label'       => 'Autre',
					'use_default' => 0,
					'pos'         => '10'
				));
			}
		}

		if (!(int) $bdb->getValue('bimp_notification', 'id', 'method = \'getTicketsForUser\'')) {
			BimpObject::createBimpObject('bimpcore', 'BimpNotification', array(
				'label'  => 'Tickets en cours',
				'nom'    => 'notif_ticket',
				'module' => 'bimpticket',
				'class'  => 'Bimp_Ticket',
				'method' => 'getTicketsForUser',
				'active' => 1
			), true, $errors);
		}

		if (count($errors)) {
			setEventMessage(BimpTools::getMsgFromArray($errors), 'errors');
		}

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return     int                1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}

}

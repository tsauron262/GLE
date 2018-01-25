<?php
/* Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modMyModule_MyModuleTriggers.class.php
 * \ingroup mymodule
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modMyModule_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for BimpNumerotation module
 */
class InterfaceBimpNumerotationTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "bimpnumerotation";
		$this->description = "BimpNumerotation triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0';
		$this->picto = 'bimpnumerotation@bimpnumerotation';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
     //   if (!empty($conf->bimpnumerotation->enabled)) return 0;   // Module not active, we do nothing
            
        
	    // Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
                    if ($action == 'BILL_VALIDATE'){
                        $object->fetch_optionals();
                        if(!isset($object->array_options['options_type']) || $object->array_options['options_type'] == "" || $object->array_options['options_type'] == "0"){
                            $this->error = "Pas de secteur ".$object->array_options['options_type'];
                            return -1;
                        }
                        return 0;
                    }
                    
                    if ($action == 'BILL_DELETE' || $action == 'BILL_UNVALIDATE' || $action == 'BILL_CANCEL'){
                        
                        if(isset($object->extraparams[0]) && $object->extraparams[0] == "1"){
                            $this->error = "Déja exportée";
                            return -1;
                        }
                        return 0;
                    }
//        


       
		//return 0;
	}
}

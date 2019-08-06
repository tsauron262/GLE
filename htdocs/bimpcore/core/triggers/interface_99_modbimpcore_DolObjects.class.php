<?php

/* Copyright (C) 2005-2014 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014      Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015      Bahfir Abbes        <bafbes@gmail.com>
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
 *  \file       htdocs/core/triggers/interface_90_all_Demo.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 * 				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for demo module
 */
class InterfaceDolObjects extends DolibarrTriggers
{

    public $picto = 'technic';
    public $description = "Rechargement des BimpObjects en cache lors d'une mise à jour sur l'objet Dolibarr associé";
    public $version = self::VERSION_DOLIBARR;
    public static $dolObjectsUpdates = array(
        // Objets BimpComm: 
        'PROPAL' => array('bimpcommercial', 'Bimp_Propal'),
        'LINEPROPAL' => array('bimpcommercial', 'Bimp_PropalLine'),
        'ORDER' => array('bimpcommercial', 'Bimp_Commande'),
        'LINEORDER' => array('bimpcommercial', 'Bimp_CommandeLine'),
        'BILL' => array('bimpcommercial', 'Bimp_Facture'),
        'LINEBILL' => array('bimpcommercial', 'Bimp_FactureLine'),
        'ORDER_SUPPLIER' => array('bimpcommercial'),
        'LINEORDER_SUPPLIER' => array('bimpcommercial'),
        'BILL_SUPPLIER' => array('bimpcommercial'),
        'LINEBILL_SUPPLIER' => array('bimpcommercial'),
        // objets BimpCore: 
        'COMPANY' => array('bimpcore', 'Bimp_Societe'),
        'CONTACT' => array('bimpcore', 'Bimp_Contact'),
        'PRODUCT' => array('bimpcore', 'Bimp_Product'),
        'USER' => array('bimpcore', 'Bimp_User'),
    );

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * @param string		$action		Event action code
     * @param Object		$object     Object concerned. Some context information may also be provided into array property object->context.
     * @param User		    $user       Object user
     * @param Translate 	$langs      Object langs
     * @param conf		    $conf       Object conf
     * @return int         				<0 if KO: 0 if no triggered ran: >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        // Edit signature
        if($action == 'USER_CREATE' or $action == 'USER_MODIFY') {
            global $db;
            // tab card and signature unset
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                if ((int) $object->id > 0)
                    define('ID_SELECTED_FOR_SIGNATURE', (int) $object->id); // used in next script
                if(ID_SELECTED_FOR_SIGNATURE > 0) {
                    $user = new User($db);
                    $user->fetch(ID_SELECTED_FOR_SIGNATURE);
//                    if($user->signature == '')
                    require_once DOL_DOCUMENT_ROOT . '/bimpcore/scripts/edit_signature.php';
                }
        }
        
        // On re-fetch() le BimpObject lié au DolObject afin d'être sûr que l'objet soit à jour dans le cache. 
        // (ou on le supprime du cache si l'action est de type DELETE. 
        if ((BimpObject::objectLoaded($object))) {
            if (preg_match('/^([A-Z_]+)_([A-Z]+)$/', $action, $matches)) {
                $objKey = $matches[1];
                $actionCode = $matches[2];

                if (array_key_exists($objKey, self::$dolObjectsUpdates)) {
                    $data = self::$dolObjectsUpdates[$objKey];
                    $cache_key = 'bimp_object_' . $data[0] . '_' . $data[1] . '_' . $object->id;
                    if (BimpCache::cacheExists($cache_key)) {
                        if ($actionCode === 'DELETE') {
                            BimpCache::unsetBimpObjectInstance($data[0], $data[1], $object->id);
                        } elseif ($actionCode !== 'CREATE') {
                            $bimp_object = BimpCache::getBimpObjectInstance($data[0], $data[1], $object->id);
                            // noFetchOnTrigger: On est dans le cas d'un create() ou update() via le BimpObject, donc pas de fetch(). 
                            if (BimpObject::objectLoaded($bimp_object) && !$bimp_object->noFetchOnTrigger) { 
                                $bimp_object->fetch((int) $object->id);
                            }
                        }
                    }
                }
            }
        }
        
        return 0;
    }
}

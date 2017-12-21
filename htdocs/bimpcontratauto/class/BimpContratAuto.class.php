<?php

/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 * 	\file       htdocs/bimpcontratauto/class/BimpcontratAuto.class.php
 * 	\ingroup    bimpcontratauto
 * 	\brief      Chose 
 */
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class BimpContratAuto {

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    function __construct($db) {
        global $conf;
        $this->db = $db;
    }

    function create() {
        dol_syslog(get_class($this) . '::create', LOG_DEBUG);
    }

    /**
     * Return array with all contrats and services for that societe
     */
    function getAllContrats($socid) {
        $dolContratObject = $this->getDolContratsObject($socid);
        $contrats = $this->fillCustomFieldForContrat($dolContratObject);
        $contrats = $this->addServices($dolContratObject, $contrats);
        return $contrats;
    }

    /**
     * Create an array of contrats and its service
     * To send to the view
     */
    function fillCustomFieldForContrat($dolContratObject) {
        $contrats = array();
        foreach ($dolContratObject as $contratObj) {
            $contrats[$contratObj->id] = array('ref' => $contratObj->ref,
                'dateDebutContrat' => $contratObj->date_creation,
                'nbService' => sizeof($contratObj->lines),
                'statut' => $contratObj->statut);       // 1 -> open ; 0 -> closed
        }
        return $contrats;
    }

    /**
     * Add services to contrats
     */
    function addServices($dolContratObject, $contrats) {
        foreach ($dolContratObject as $contratObj) {
            $prixTotalContrat = 0;
            $lines = $contratObj->fetch_lines();    // get services
            $services = array();                    // init tab service
            $contratIsActive = false;
            $endContrat = 0;
            foreach ($lines as $line) {
                $duree = $this->getServiceDuration($line->fk_product);

                if (!isset($lowestDuration)) {                  // Recherche du
                    $lowestDuration = $line->qty;               // service qui se
                } else if ($lowestDuration > $line->qty) {      // fini 
                    $lowestDuration = $duree;                   // en
                }                                               // premier

                /* Définition début et fin de service et contrat */
                if ($line->statut == 0) {     // si le service n'est pas encore ouvert
                    $date_ouverture = $line->date_ouverture_prevue;
                    $date_fin = $line->date_fin_validite;
                } else if ($line->statut == 4) {  // si le service est inactif
                    $date_ouverture = $line->date_ouverture;
                    $date_fin = $line->date_fin_validite;
                    if ($contratIsActive && $line->date_fin_validite < $endContrat) // si le service est actif et que sa date est antérieur à la date de fin du contrat
                        $endContrat = $line->date_fin_validite;
                    else if (!$contratIsActive) // si le contrat n'est pas ENCORE actif
                        $endContrat = $line->date_fin_validite; // avancer la date de fin à celle de ce service
                    $contratIsActive = true;
                } else {    // si le service est inactif
                    $date_ouverture = $line->date_ouverture;
                    $date_fin = $line->date_cloture;
                    if (!$contratIsActive && $endContrat < $line->date_cloture) // si le contrat n'est pas actif et que ce service est, pour l'instant, le dernier
                        $endContrat = $line->date_cloture;  // reculer la date de fin du contrat à ce service
                }

                $facture = $this->getFacture($contratObj->socid);

                $services[] = array(
                    'id_product' => $line->id, // attention l'id n'est pas toujours définit
                    'ref' => $line->ref,
                    'duree' => $duree, // non utilisé
                    'qty' => $line->qty, // duréee du service (en mois)
                    'dateDebutService' => dol_print_date($date_ouverture),
                    'dateFinService' => dol_print_date($date_fin),
                    'prixUnitaire' => $line->price_ht,
                    'prixTotal' => $line->qty * $line->price_ht, // TODO prixTotal dépend de quantité et non pas de durée
                    'statut' => $this->checkStatut($line->statut, dol_time_plus_duree($line->date_ouverture, $line->qty, 'm'))); // if 1 => OK if 0 => have to be closed
                $prixTotalContrat += $line->qty * $line->price_ht;
            }
            if ($contratIsActive) {
                
            }
            $contrats[$contratObj->id]['dateFinContrat'] = dol_print_date($endContrat);
            $contrats[$contratObj->id]['services'] = $services;
            $contrats[$contratObj->id]['prixTotalContrat'] = $prixTotalContrat;
            $contrats[$contratObj->id]['dateDebutContrat'] = dol_print_date($contrats[$contratObj->id]['dateDebutContrat']);
        }
        return $contrats;
    }

    function getFacture($socid) {
        
    }

    /**
     * Check if the service is active, if it is and is passed
     * Set check to 0 if the service has to be closed
     */
    function checkStatut($statut, $timeEndService) {
        if ($statut == 4 && dol_now() > $timeEndService) {
            return 0;
        }
        return 1;   // The service is OK
    }

    /**
     * Get the duration of a service by instancing a product
     */
    function getServiceDuration($fk_product) {
        if ($fk_product > 0) {
            $product = new Product($this->db);
            $product->fetch($fk_product);
            return $product->duration;
        }
        return 'unfetchable_product';
    }

    /**
     * Search all contract with that client and return it
     * @param type $socid
     * @return \Contrat     array of contrat objects
     */
    function getDolContratsObject($socid) {
        global $conf;

        $dolContratObject = array();

        if (isset($conf->global->MAIN_SIZE_SHORTLIST_LIMIT))
            $MAXLIST = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
        else
            $MAXLIST = 25;

        $contratstatic = new Contrat($this->db);
        $contratstatic->socid = $socid;
        return $contratstatic->getListOfContracts();
    }

    function createContrat($socid, $contrat, $dateDeb) {
//    $soc = new Societe($db);
//    if ($socid>0)
//        $soc->fetch($socid);
    }

    static function getTabService($db) {

        /* If you want to add a service, add its reference in that array */
        $refService = array('CTR-ASSISTANCE', 'CTR-PNEUMATIQUE', 'CTR-MAINTENANCE', 'CTR-EXTENSION', 'Blyyd Connect', 'dzd');  // ref in database

        $tabService = array();
        $sql = 'SELECT rowid, ref ';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'product';
        $sql.= ' WHERE';
        foreach ($refService as $i => $ref) {
            if ($i == 0) {
                $sql.=' ref=\'' . $ref . '\'';
            } else {
                $sql.=' OR ref=\'' . $ref . '\'';
            }
        }
        $result = $db->query($sql);
        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                $service = array('id' => $obj->rowid,
                                 'name' => $obj->ref,
                                 'values'=> array("Non", 12, 24, 6));
                $tabService[] = $service;
            }
            return $tabService;
        } else {
            dol_print_error($db->db);
            return -3;
        }
        return -1;
    }
}
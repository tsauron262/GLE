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
require_once DOL_DOCUMENT_ROOT . '/synopsisres/manipElementElement.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';

class BimpContratAuto {

    const NO_SERVICE = "Non";

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
        $contrats = $this->sortArrayByDate($contrats);
        $contrats = $this->tmsToDates($contrats);
        return $contrats;
    }

    /* Translate date in format tms to a human readable's one */
    function tmsToDates($contrats) {
        foreach ($contrats as $i => $contrat) {
            if ($contrat['dateFinContrat'] != 'non définie')
            $contrats[$i]['dateFinContrat'] = dol_print_date($contrat['dateFinContrat']);
        }
        return $contrats;
    }

    /* Used to sort contrats by date of end */
    function sortArrayByDate($contrats) {

        $dates = array();
        foreach ($contrats as $key => $row) {
            $dates[$key] = $row['dateFinContrat'];
        }
        array_multisort($dates, SORT_ASC, $contrats);

        foreach ($contrats as $key => $row) {
            $dates[$key] = $row['dateFinContrat'];
        }
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

    /* Return an array with 3 values about the bill linked to that contrat */

    function getTotalFacture($elements, $prixTotalContrat) {
        $totalFacturer = 0;
        $totalPayer = 0;
        $totalRestant = 0;
        foreach ($elements as $element) {
            $newFacture = new Facture($this->db);
            $newFacture->fetch($element['d']); // id of facture
            $totalFacturer += $newFacture->total_ht;
            foreach ($newFacture->getListOfPayments() as $payment) {
                $totalPayer += $payment['amount'];
            }
        }

        $totalRestant = $prixTotalContrat - $totalPayer;
        return array('totalFacturer' => $totalFacturer, 'totalPayer' => $totalPayer, 'totalRestant' => $totalRestant);
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
                if ($line->statut == 0) {     // si le service est inactif
                    $date_ouverture = $line->date_ouverture_prevue;
                    $date_fin = $line->date_fin_validite;
                    if (!$contratIsActive && $endContrat < $line->date_fin_validite) {    // si contrat inactif
                        $endContrat = $line->date_fin_validite;
                    }
                } else if ($line->statut == 4) {  // si le service actif
                    $date_ouverture = $line->date_ouverture;
                    $date_fin = $line->date_fin_validite;
                    if ($contratIsActive && $line->date_fin_validite < $endContrat) { // si le contrat est actif et que la date de fin du service est antérieur à la date de fin du contrat
                        $endContrat = $line->date_fin_validite;
                    } else if (!$contratIsActive) { // si le contrat n'est pas encore actif
                        $endContrat = $line->date_fin_validite; // avancer la date de fin à celle de ce service
                        $contratIsActive = true;
                    }
                } else {    // si le service est fermé
                    $date_ouverture = $line->date_ouverture;
                    $date_fin = $line->date_cloture;
                    if (!$contratIsActive && $endContrat < $line->date_cloture) { // si le contrat n'est pas actif et que ce service est, pour l'instant, le dernier
                        $endContrat = $line->date_cloture;  // reculer la date de fin du contrat à ce service
                    }
                }

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

            $elements = getElementElement('contrat', 'facture', $contratObj->id, null);
            $totalFacture = $this->getTotalFacture($elements, $prixTotalContrat);

            $contrats[$contratObj->id]['totalFacturer'] = $totalFacture['totalFacturer'];
            $contrats[$contratObj->id]['totalPayer'] = $totalFacture['totalPayer'];
            $contrats[$contratObj->id]['totalRestant'] = $totalFacture['totalRestant'];
            if ($endContrat != 0)
                $contrats[$contratObj->id]['dateFinContrat'] = $endContrat;
            else
                $contrats[$contratObj->id]['dateFinContrat'] = 'non définie';
            $contrats[$contratObj->id]['services'] = $services;
            $contrats[$contratObj->id]['prixTotalContrat'] = $prixTotalContrat;
            $contrats[$contratObj->id]['dateDebutContrat'] = dol_print_date($contrats[$contratObj->id]['dateDebutContrat']);
        }
        return $contrats;
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

    /**
     * Create a new contrat with its service
     * @param type $socid
     * @param type $services    array of services
     * @param type $dateDeb     date start contrat
     * @param type $user        the user who is creating the contrat
     */
    function createContrat($socid, $services, $dateDeb, $user) {
        $nContrat = new Contrat($this->db);

        $nContrat->date_contrat = convertirDate($dateDeb, false);   // translate date
        $nContrat->socid = $socid;
        $nContrat->commercial_suivi_id = $user->id;
        $nContrat->commercial_signature_id = $user->id;
        $nContrat->create($user);    // create contrat

        foreach ($services as $id => $service) {
            if ($service['value'] == self::NO_SERVICE)      // dont add that service
                continue;
            $prod = new Product($this->db);
            $prod->fetch($service['id']);
            $dateFin = dol_time_plus_duree($dateDeb, $service['value'], 'm');   // define the end of the contrat
            $nContrat->addline('', $prod->price, $service['value'], 0.0, 0, 0, $service['id'], 0, $dateDeb, $dateFin);  // add service
        }
    }

    /**
     * Statics functions
     */

    /**
     * Used to display in the view
     */
    static function getTabService($db) {
        /* If you want to add a service, add its reference and its values in that array
          /!\ Dont forget to add self::NO_SERVICE the service is not mandatory
         */
        $services = array(
            array('ref' => 'CTR-ASSISTANCE', 'values' => array(self::NO_SERVICE, 12, 24, 36)),
            array('ref' => 'CTR-PNEUMATIQUE', 'values' => array(self::NO_SERVICE, 12, 24, 36)),
            array('ref' => 'CTR-MAINTENANCE', 'values' => array(self::NO_SERVICE, 12, 24)),
            array('ref' => 'CTR-EXTENSION', 'values' => array(self::NO_SERVICE, 12, 24, 36)),
            array('ref' => 'Blyyd Connect', 'values' => array(self::NO_SERVICE, 12, 24, 36, 48))
        );

        $tabService = array();
        $sql = 'SELECT rowid, ref ';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'product';
        $sql.= ' WHERE';
        $first = true;
        foreach ($services as $service) {   // loop to make a single query
            if ($first) {
                $sql.=' ref=\'' . $service['ref'] . '\'';
            } else {
                $sql.=' OR ref=\'' . $service['ref'] . '\'';
            }
            $first = false;
        }
        $result = $db->query($sql);
        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                foreach ($services as $service) {   // search different values that can be associated with that reference
                    if ($service['ref'] == $obj->ref) {
                        $values = $service['values'];
                        break;
                    }
                }
                $tabService[] = array('id' => $obj->rowid,
                    'name' => $obj->ref,
                    'values' => $values);
            }
            return $tabService;
        } else {
            dol_print_error($db->db);
            return -3;
        }
        return -1;
    }

}

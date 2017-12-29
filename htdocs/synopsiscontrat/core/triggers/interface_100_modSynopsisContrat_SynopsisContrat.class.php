<?php

require_once DOL_DOCUMENT_ROOT."/synopsisres/manipElementElement.php";

/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/core/triggers/interface_50_modNotification_Notification.class.php
 *  \ingroup    notification
 *  \brief      File of class of triggers for notification module
 */

/**
 *  Class of triggers for notification module
 */
class InterfaceSynopsisContrat {

    var $db;
    var $listofmanagedevents = array(
        'CONTRACT_MODIFY'
    );

    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db) {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "Synopsis";
        $this->description = "Triggers of this module send email notifications according to Notification module setup.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->picto = 'email';
    }

    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName() {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc() {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion() {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental')
            return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr')
            return DOL_VERSION;
        elseif ($this->version)
            return $this->version;
        else
            return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
    function run_trigger($action, $object, $user, $langs, $conf) {
        global $db, $infoEvent;
        if ($action == "CONTRACT_MODIFY") {
            $object->fetch($object->id);
            $date = date("Y-m-d H:i:s", $object->date_contrat);
            $this->setDate($date, $date,$object);
        }
    }
    
    public function setDate($dateDeb, $dateProlongation, $object) {
        $object->fetch_lines();
        $dureeMax = 0;
        $tmpProd = new Product($this->db);
        foreach ($object->lines as $ligne) {
            $duree = 12;
            if (isset($ligne->fk_product) && $ligne->fk_product > 0) {
                $tmpProd->fetch($ligne->fk_product);
                $tmpProd->fetch_optionals($ligne->fk_product);
                if ($isSAV && $tmpProd->array_options['options_2dureeSav'] > 0)
                    $duree = $tmpProd->array_options['options_2dureeSav'];
                elseif ($tmpProd->array_options['options_2dureeVal'] > 0)
                    $duree = $tmpProd->array_options['options_2dureeVal'];
            }
            if ($duree > $dureeMax)
                $dureeMax = $duree;
            $query = "UPDATE " . MAIN_DB_PREFIX . "contratdet SET date_ouverture_prevue = '" . $dateDeb . "'";
            //echo $query;
            //echo $dateDeb;
            if ($ligne->date_ouverture)
                $query .= ", date_ouverture = '" . $dateDeb . "', date_fin_validite = date_add(date_add('" . $dateProlongation . "',INTERVAL " . $duree . " MONTH), INTERVAL -1 DAY)";
//die($query . " WHERE rowid = " . $ligne->id);
            $sql = $this->db->query($query . " WHERE rowid = " . $ligne->id);
            $query = "UPDATE " . MAIN_DB_PREFIX . "contrat SET date_contrat = '" . $dateDeb . "'";
            
            if ($object->mise_en_service)
                $query .= ", mise_en_service = '" . $dateDeb . "', fin_validite = date_add(date_add('" . $dateProlongation . "',INTERVAL " . $dureeMax . " MONTH), INTERVAL -1 DAY)";
            $sql = $this->db->query($query . " WHERE rowid = " . $object->id);
            //echo $query . " WHERE rowid = " . $object->id;
        }
    }

}


?>

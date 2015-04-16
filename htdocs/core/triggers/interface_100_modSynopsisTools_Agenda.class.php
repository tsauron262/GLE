<?php

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
class InterfaceAgenda {

    var $db;
    var $listofmanagedevents = array(
        'ACTION_CREATE',
        'ACTION_DELETE',
        'ACTION_MODIFY'
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
        global $user;
        if (in_array($action, array("ACTION_DELETE", "ACTION_MODIFY"))) {
            if ($action == "ACTION_MODIFY") {
dol_syslog("av action lier1",3);
                $object->fetch($object->id);
dol_syslog("av action lier2",3);
                if ($object->elementtype == "synopsisdemandeinterv" && $object->fk_element > 0) {
dol_syslog("av action lier3",3);
                    require_once (DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
dol_syslog("av action lier4",3);
                    $di = new Synopsisdemandeinterv($this->db);
dol_syslog("av action lier5",3);
                    $di->fetch($object->fk_element);
dol_syslog("av action lier6",3);
                    $di->set_date_delivery($user, $object->datep, true);
dol_syslog("av action lier7",3);
//                    $di->getExtra();
                    if($di->fk_user_prisencharge != $object->usertodo->id)
                            $di->preparePrisencharge($object->usertodo);
dol_syslog("av action lier8",3);
                    $di->update();
dol_syslog("av action lier9",3);
                    return false;
                }
            }
dol_syslog("av action lier10",3);


            require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
            $idActionLier = array();
            $idRef = $object->id;
            if (isset($object->ref_ext) && $object->ref_ext > 0) {
                $idMaitre = $object->ref_ext;
                $idActionLier[] = $idMaitre;
            } else
                $idMaitre = $object->id;


            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "actioncomm WHERE ref_ext = '" . $idMaitre . "'");
            if ($this->db->num_rows($sql) > 0) {
                while ($result = $this->db->fetch_object($sql)) {
                    if ($result->id != $idRef)
                        $idActionLier[] = $result->id;
                }
            }
dol_syslog("av action lier",3);
            foreach ($idActionLier as $id) {
                $actionTmp = new ActionComm($this->db);
                $actionTmp->fetch($id);
                if ($action == "ACTION_DELETE") {
                    $actionTmp->delete();
                }


                if ($action == "ACTION_MODIFY") {
                    $actionTmp->datec = $object->datec;
                    $actionTmp->datef = $object->datef;
                    $actionTmp->datem = $object->datem;
                    $actionTmp->datep = $object->datep;
                    $actionTmp->datep = $object->datep;
                    $actionTmp->note = $object->note;
                    $actionTmp->label = $object->label;
                    $actionTmp->fk_action = $object->fk_action;
                    $actionTmp->percentage = $object->percentage;

                    $actionTmp->update($user, 1);
                }
            }
            return 0;
        }
    }

}

?>

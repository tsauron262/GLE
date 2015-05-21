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
class InterfaceCaldav {

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
        global $user, $db;
        if ($action == "ACTION_MODIFY" || $action == "ACTION_CREATE" || $action == "ACTION_DELETE") {
//                $db->query("UPDATE " . MAIN_DB_PREFIX . "user_extrafields SET ctag = ctag+1 WHERE fk_object = " . $object->userownerid);
                
                $object->fetch_userassigned();
                
                $tIdUser = array();
                if (isset($object->userownerid) && $object->userownerid > 0)
                    $tIdUser[$object->userownerid] = $object->userownerid;
                foreach($object->userassigned as $val)
                    $tIdUser[$val['id']] = $val['id'];
                foreach($tIdUser as $idUser){
                    $tabT = getElementElement("user", "idCaldav", $idUser);
                    if(isset($tabT[0]))
                        setElementElement ("user", "idCaldav", $idUser, $tabT[0]['d']+1);
                    else
                        addElementElement ("user", "idCaldav", $idUser, 1);
                }
                
        }
        if ($action == "ACTION_MODIFY"){
            $db->query("UPDATE ".MAIN_DB_PREFIX."synopsiscaldav_event SET etag = '".random(15)."', uri = IF(uri is not null, uri, CONCAT(CONCAT('-', fk_object), '.ics')) WHERE fk_object = ".$object->id);
        }
        if ($action == "ACTION_CREATE"){
            global $objectUriTemp, $objectEtagTemp, $objectDataTemp;
            $objectUri2 = (isset($objectUriTemp) && $objectUriTemp != "") ? $objectUriTemp : "-".$object->id.".ics";
            $objectEtag2 = (isset($objectEtagTemp) && $objectEtagTemp != "") ? $objectEtagTemp : random(15);
            $objectDataTemp = (isset($objectDataTemp) && $objectDataTemp != "") ? addslashes($objectDataTemp) : "";
            $db->query("INSERT INTO ".MAIN_DB_PREFIX."synopsiscaldav_event (etag, uri, fk_object, agendaplus) VALUES ('".$objectEtag2."', '".$objectUri2."', '".$object->id."', '".$objectDataTemp."')");
        }
        if ($action == "ACTION_DELETE"){
            $db->query("DELETE FROM ".MAIN_DB_PREFIX."synopsiscaldav_event WHERE fk_object = ".$object->id);
        }
    }

}

function random($car) {
$string = "";
$chaine = "abcdefghijklmnpqrstuvwxy";
srand((double)microtime()*1000000);
for($i=0; $i<$car; $i++) {
$string .= $chaine[rand()%strlen($chaine)];
}
return $string;
}

?>

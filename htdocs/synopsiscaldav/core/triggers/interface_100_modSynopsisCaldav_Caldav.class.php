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
    function runTrigger($action, $object, $user, $langs, $conf) {
        global $db, $infoEvent;
        if ($action == "ACTION_MODIFY" || $action == "ACTION_CREATE" || $action == "ACTION_DELETE") {
//                if(!in_array($object->type_id, array(3,8,9,10,30,31,40)))
//                    $db->query("UPDATE " . MAIN_DB_PREFIX . "user_extrafields SET ctag = ctag+1 WHERE fk_object = " . $object->userownerid);

                $object->fetch_userassigned();
                
                
                //mise a jour du ctag
                if(!in_array($object->type_id, array(3,8,9,10,30,31,40))){
                    
                    
                    
                    $tIdUser = array();
                    if (isset($object->userownerid) && $object->userownerid > 0)
                        $tIdUser[$object->userownerid] = $object->userownerid;
                    foreach($object->userassigned as $val)
                        $tIdUser[$val['id']] = $val['id'];
                    if(isset($object->oldcopy->userassigned) && is_array($object->oldcopy->userassigned))
                        foreach($object->oldcopy->userassigned as $val)//Pour incrémenté le CTAG aussi des user sortanrt
                            $tIdUser[$val['id']] = $val['id'];
                    foreach($tIdUser as $idUser){
                        $tabT = getElementElement("user", "idCaldav", $idUser);
                        if(isset($tabT[0]))
                            setElementElement ("user", "idCaldav", $idUser, $tabT[0]['d']+1);
                        else
                            addElementElement ("user", "idCaldav", $idUser, 1);
                    }
                }
                
                
                $newUri = $conf->global->MAIN_APPLICATION_TITLE.(defined("CHAINE_CALDAV")? "-".CHAINE_CALDAV : "") ."-".$object->id.".ics";
                $objectUri2 = (isset($infoEvent["uri"]) && $infoEvent["uri"] != "") ? $infoEvent["uri"] : $newUri;
                $objectEtag2 = (isset($infoEvent["etag"]) && $infoEvent["etag"] != "") ? $infoEvent["etag"] : random(15);
                $objectDataTemp = (isset($infoEvent["data"]) && $infoEvent["data"] != "") ? addslashes($infoEvent["data"]) : "";
                $participentExt = (isset($infoEvent["participentExt"]) && $infoEvent["participentExt"] != "") ? "'".addslashes($infoEvent["participentExt"])."'" : "participentExt";
                $objectRappel = (isset($infoEvent["rappel"]) && $infoEvent["rappel"] != "") ? addslashes($infoEvent["rappel"]) : 0;
                $organisateur = (isset($infoEvent["organisateur"]) && $infoEvent["organisateur"] != "") ? $infoEvent["organisateur"] : "";
                
                if($organisateur == "")   {//ne devrais pas arrivé
                   if($object->userownerid > 0){
                       $userOrga = new User($this->db);
                       $userOrga->fetch($object->userownerid);
                       $organisateur = $userOrga->email;
                   }
                   else
                       $organisateur = (in_array($user->id, $tIdUser) ? $user->email : "gle_suivi@bimp.fr");
                }
                
                if(isset($infoEvent["dtstamp"]) && $infoEvent["dtstamp"] != ""){//On vien en caldav                    
                    $dtstamp = addslashes($infoEvent["dtstamp"]);
                    $sequence = ((isset($infoEvent["sequence"]) && ($infoEvent["sequence"] > 0 OR $infoEvent["sequence"] === 0))? $infoEvent["sequence"] : "sequence");
                }
                else{
                    $dtstamp = gmdate('Ymd').'T'. gmdate('His') . "Z";
                    $sequence = "sequence";// + 1";
                }

                
        }
        if ($action == "ACTION_MODIFY"){
            $db->query("UPDATE ".MAIN_DB_PREFIX."synopsiscaldav_event SET organisateur = '".$organisateur."', dtstamp = '".$dtstamp."', participentExt = ".$participentExt.", sequence = ".$sequence.", etag = '".$objectEtag2."', uri = IF(uri is not null, uri, CONCAT(CONCAT('-', fk_object), '.ics')) WHERE fk_object = ".$object->id);
        }
        if ($action == "ACTION_CREATE"){
//            $db->query("INSERT INTO ".MAIN_DB_PREFIX."synopsiscaldav_event (etag, uri, fk_object, agendaplus, Rappel) VALUES ('".$objectEtag2."', '".$objectUri2."', '".$object->id."', '".$objectDataTemp."', '".$objectRappel."')");

            $db->query("INSERT INTO ".MAIN_DB_PREFIX."synopsiscaldav_event (etag, uri, fk_object, agendaplus, organisateur, participentExt, dtstamp) VALUES ('".$objectEtag2."', '".$objectUri2."', '".$object->id."', '".$objectDataTemp."', '".$organisateur."', ".$participentExt.", '".$dtstamp."')");
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

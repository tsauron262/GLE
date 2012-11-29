<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/* show  ActionCom Filter */

$showActionCom = $_REQUEST['showActionCom']=='on'?true:false;
$showActionComCreate = $_REQUEST['showActionComCreate']=='on'?true:false;
$showActionComValid = $_REQUEST['showActionComValid']=='on'?true:false;
$showActionComDate = $_REQUEST['showActionComDate']=='on'?true:false;


array_push($arrFilter,
        array(  "name" => "ActionCom" ,
                "data" => array( 0 => array( "checked" => $showActionCom, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showActionComCreate, "trans" => "creation",     "idx" => "showActionComCreate"),
                                 2 => array( "checked" => $showActionComValid, "trans" => "validation",   "idx" => "showActionComValid"),
                                 3 => array( "checked" => $showActionComDate, "trans" => "Action Com",     "idx" => "showActionComDate"),
                               )
             )
);
  //action com (".MAIN_DB_PREFIX."actioncomm)
    //datec
    //datep
    //datep2
    //datea
    //datea2
    //note
    //fk_contact
    //fk_user_author
    //fkaction (".MAIN_DB_PREFIX."c_actioncomm)
$requete = "SELECT
    ".MAIN_DB_PREFIX."actioncomm.id,
    ".MAIN_DB_PREFIX."actioncomm.datec,
    ".MAIN_DB_PREFIX."actioncomm.datep,
    ".MAIN_DB_PREFIX."actioncomm.datep2,
    ".MAIN_DB_PREFIX."actioncomm.datea,
    ".MAIN_DB_PREFIX."actioncomm.datea2,
    ".MAIN_DB_PREFIX."actioncomm.fk_action,
    ".MAIN_DB_PREFIX."actioncomm.label,
    ".MAIN_DB_PREFIX."actioncomm.fk_projet,
    ".MAIN_DB_PREFIX."actioncomm.fk_contact,
    ".MAIN_DB_PREFIX."actioncomm.fk_user_action,
    ".MAIN_DB_PREFIX."actioncomm.fk_user_done,
    ".MAIN_DB_PREFIX."actioncomm.fk_user_author,
    ".MAIN_DB_PREFIX."actioncomm.fk_user_mod,
    ".MAIN_DB_PREFIX."actioncomm.durationp,
    ".MAIN_DB_PREFIX."actioncomm.durationa,
    ".MAIN_DB_PREFIX."actioncomm.note,
    ".MAIN_DB_PREFIX."actioncomm.propalrowid,
    ".MAIN_DB_PREFIX."actioncomm.fk_commande,
    ".MAIN_DB_PREFIX."actioncomm.fk_facture,
    ".MAIN_DB_PREFIX."c_actioncomm.code,
    ".MAIN_DB_PREFIX."c_actioncomm.type,
    ".MAIN_DB_PREFIX."c_actioncomm.libelle,
    ".MAIN_DB_PREFIX."c_actioncomm.module,
    ".MAIN_DB_PREFIX."c_actioncomm.active,
    ".MAIN_DB_PREFIX."c_actioncomm.todo
FROM
    dolibarr24dev.".MAIN_DB_PREFIX."actioncomm ".MAIN_DB_PREFIX."actioncomm,
    dolibarr24dev.".MAIN_DB_PREFIX."c_actioncomm ".MAIN_DB_PREFIX."c_actioncomm
WHERE
    ".MAIN_DB_PREFIX."actioncomm.fk_action = ".MAIN_DB_PREFIX."c_actioncomm.id AND
    ".MAIN_DB_PREFIX."actioncomm.fk_soc =
" . $socid;
?>
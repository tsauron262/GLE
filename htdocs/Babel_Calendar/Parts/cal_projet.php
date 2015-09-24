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
/* show Projet Filter */

$showProjet = $_REQUEST['showProjet']=='on'?true:false;
$showProjetCreate = $_REQUEST['showProjetCreate']=='on'?true:false;
$showProjetValid = $_REQUEST['showProjetValid']=='on'?true:false;
$showProjetDate = $_REQUEST['showProjetDate']=='on'?true:false;

array_push($arrFilter,
        array(  "name" => "Projet" ,
                "data" => array( 0 => array( "checked" => $showProjet, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showProjetCreate, "trans" => "creation",     "idx" => "showProjetCreate"),
                                 2 => array( "checked" => $showProjetValid, "trans" => "validation",   "idx" => "showProjetValid"),
                                 3 => array( "checked" => $showProjetDate, "trans" => "Projet",     "idx" => "showProjetDate"),
                               )
             )
);

  //projet
    //Date création dateo
    //title
    //ref
    //
$requete = "SELECT
    ".MAIN_DB_PREFIX."projet.rowid,
    ".MAIN_DB_PREFIX."projet.fk_soc,
    ".MAIN_DB_PREFIX."projet.fk_statut,
    ".MAIN_DB_PREFIX."projet.dateo,
    ".MAIN_DB_PREFIX."projet.ref,
    ".MAIN_DB_PREFIX."projet.title,
    ".MAIN_DB_PREFIX."projet.fk_user_resp,
    ".MAIN_DB_PREFIX."projet.fk_user_creat,
    ".MAIN_DB_PREFIX."projet.note,
    ".MAIN_DB_PREFIX."projet_task.rowid,
    ".MAIN_DB_PREFIX."projet_task.title,
    ".MAIN_DB_PREFIX."projet_task.fk_statut,
    ".MAIN_DB_PREFIX."projet_task.note,
    ".MAIN_DB_PREFIX."projet_task_time.task_date,
    ".MAIN_DB_PREFIX."projet_task_time.task_duration,
    ".MAIN_DB_PREFIX."projet_task_time.fk_user,
    ".MAIN_DB_PREFIX."projet_task_time.note
FROM
    ".MAIN_DB_PREFIX."projet ".MAIN_DB_PREFIX."projet
LEFT JOIN
    ".MAIN_DB_PREFIX."projet_task ".MAIN_DB_PREFIX."projet_task
ON
    ".MAIN_DB_PREFIX."projet.rowid = ".MAIN_DB_PREFIX."projet_task.fk_projet
LEFT JOIN
    ".MAIN_DB_PREFIX."projet_task_time ".MAIN_DB_PREFIX."projet_task_time
ON
    ".MAIN_DB_PREFIX."projet_task.rowid = ".MAIN_DB_PREFIX."projet_task_time.fk_task
WHERE
    ".MAIN_DB_PREFIX."projet.fk_soc =
".$socid;


?>
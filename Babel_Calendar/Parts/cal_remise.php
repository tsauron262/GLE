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
/* show remise Filter */

$showRemise = $_REQUEST['showRemise']=='on'?true:false;
$showRemiseCreate = $_REQUEST['showRemiseCreate']=='on'?true:false;
$showRemiseValid = $_REQUEST['showRemiseValid']=='on'?true:false;
$showRemiseDate = $_REQUEST['showRemiseDate']=='on'?true:false;

array_push($arrFilter,
        array(  "name" => "Remise" ,
                "data" => array( 0 => array( "checked" => $showRemise, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showRemiseCreate, "trans" => "creation",     "idx" => "showRemiseCreate"),
                                 2 => array( "checked" => $showRemiseValid, "trans" => "validation",   "idx" => "showRemiseValid"),
                                 3 => array( "checked" => $showRemiseDate, "trans" => "remise",     "idx" => "showRemiseDate"),
                               )
             )
);

  //remise ".MAIN_DB_PREFIX."societe_remise
    //Date creation datec
    //remise_client
    //note

$requete = "SELECT
    rowid,
    fk_soc,
    datec,
    fk_user_author,
    remise_client,
    note
FROM
    ".MAIN_DB_PREFIX."societe_remise
WHERE
    fk_soc =
". $socid;

  //remise ".MAIN_DB_PREFIX."societe_remise_except
    //Condition fk_facture_source = 0 => avoir
    //Condition fk_facture_line = 0 => avoir
    //Condition fk_facture = 0 => avoir
    //Condition ".MAIN_DB_PREFIX."propaldet.fk_remise_except is not null => utilisé
    //Date creation datec
    // amount_ht
    //note

$requete = "SELECT
    ".MAIN_DB_PREFIX."societe_remise_except.rowid,
    ".MAIN_DB_PREFIX."societe_remise_except.fk_soc,
    ".MAIN_DB_PREFIX."societe_remise_except.datec,
    ".MAIN_DB_PREFIX."societe_remise_except.amount_ht,
    ".MAIN_DB_PREFIX."societe_remise_except.fk_user,
    ".MAIN_DB_PREFIX."societe_remise_except.fk_facture_source,
    ".MAIN_DB_PREFIX."societe_remise_except.fk_facture_line,
    ".MAIN_DB_PREFIX."societe_remise_except.fk_facture,
    ".MAIN_DB_PREFIX."societe_remise_except.description,
    ".MAIN_DB_PREFIX."propaldet.fk_propal,
    ".MAIN_DB_PREFIX."propaldet.fk_remise_except
FROM
    ".MAIN_DB_PREFIX."societe_remise_except ".MAIN_DB_PREFIX."societe_remise_except
LEFT JOIN
    ".MAIN_DB_PREFIX."propaldet ".MAIN_DB_PREFIX."propaldet
ON
    ".MAIN_DB_PREFIX."societe_remise_except.rowid = ".MAIN_DB_PREFIX."propaldet.fk_remise_except
WHERE
    ".MAIN_DB_PREFIX."societe_remise_except.fk_soc = ".$socid;

?>
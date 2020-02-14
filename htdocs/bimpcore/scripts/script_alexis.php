<?php
/*
 * 
 *  Script Alexis
 * 
 */

require_once("../../main.inc.php");

$extrafields = new ExtraFields($db);
$extrafields->addExtraField('entrepot', 'Entrepot', 'varchar', 104, 8, 'contrat');

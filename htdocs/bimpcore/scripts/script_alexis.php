<?php
/*
 * 
 *  Script Alexis
 * 
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';
//$extrafields = new ExtraFields($db);
//$extrafields->addExtraField('entrepot', 'Entrepot', 'varchar', 104, 8, 'contrat');

/* 
 * Vendredi 13 Février 2020 
 * Insertion de la date de fin des contrats dans le champ: end_date_contrat
 */

$contrat = BimpObject::getInstance('bimpcontract', "BContract_contrat");
$liste_contrats = $contrat->getList(['statut' => 11]);

foreach ($liste_contrats as $nb => $crt) {
    $contrat->fetch($crt['rowid']);
    
    $errors = $contrat->updateField('end_date_contrat', $contrat->getEndDate()->format('Y-m-d'));
    
    if(!count($errors)) {
        $validation = "<b style='color:green'>OUI</b>";
    } else {
        $validation = "<b style='color:red'>NON</b>";
    }
    
    
    echo '<b>' . $contrat->getData('ref') . ': '.$validation.'</b><br />';
}
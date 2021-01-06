<?php
/*
 * 
 *  Script Alexis
 * 
 */
//
//require_once("../../main.inc.php");
//require_once __DIR__ . '/../Bimp_Lib.php';
////$extrafields = new ExtraFields($db);
////$extrafields->addExtraField('entrepot', 'Entrepot', 'varchar', 104, 8, 'contrat');
//ini_set('display_errors', 1);
//$can_execute = ($user->admin) ? true : false;
//
//if($can_execute) {
//
//    $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
//    $list = $contrat->getList(['statut' => 11]);
//    foreach($list as $id) {
//        $contrat->fetch($id['rowid']);
//        if(!$contrat->getData('initial_renouvellement')) {
//            $contrat->updateField('initial_renouvellement', $contrat->getData('tacite'));
//            echo "MAJ<br />";
//        }
//    }
//    
//    
//} else {
//    
//}
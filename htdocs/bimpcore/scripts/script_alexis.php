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
ini_set('display_errors', 1);
$can_execute = ($user->admin) ? true : false;

if($can_execute) {
    
//    $echeancier = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');
//    
//    $list = $echeancier->getList();
//    foreach($list as $i => $infos) {
//        $echeancier->fetch($infos['id']);
//        $echeancier->switch_statut();
//        
//        $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $echeancier->getData('id_contrat'));
//        $echeancier->updateField('commercial', $contrat->getData('fk_commercial_suivi'));
//        
//        $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $contrat->getData('fk_soc'));
//        $echeancier->updateField('client', $client->id);
//        
//    }

    
    $contrats = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
    
    $list = $contrats->getList(['statut' => 11]);
    foreach($list as $i => $infos) {
        
        $contrats->fetch($infos['rowid']);
       // echo '<i>'.$contrats->getData('ref').'</i><br />';
        $lines = BimpObject::getInstance('bimpcontract', 'BContract_contratLine');
        $lines_list = $lines->getList(['statut' => 0, 'fk_contrat' => $contrats->id]);
        if(count($lines_list)) {
            echo $contrats->getData('ref') . '<br />';
        }
    }
    
    
    
}
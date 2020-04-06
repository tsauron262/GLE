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
    /* 
     * Vendredi 13 Février 2020 
     * Insertion de la date de fin des contrats dans le champ: end_date_contrat
     */

    //$contrat = BimpObject::getInstance('bimpcontract', "BContract_contrat");
    //$liste_contrats = $contrat->getList(['statut' => 11]);
    //
    //foreach ($liste_contrats as $nb => $crt) {
    //    $contrat->fetch($crt['rowid']);
    //    
    //    $errors = $contrat->updateField('end_date_contrat', $contrat->getEndDate()->format('Y-m-d'));
    //    
    //    if(!count($errors)) {
    //        $validation = "<b style='color:green'>OUI</b>";
    //    } else {
    //        $validation = "<b style='color:red'>NON</b>";
    //    }
    //    
    //    
    //    echo '<b>' . $contrat->getData('ref') . ': '.$validation.'</b><br />';
    //}

    /*
     * Lundi 6 Avril 2020
     * Passage de tous les anciens contrats validé en actif
     */
    
    
    $contrats = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
    $liste = $contrats->getList(['statut' => 1]);
    foreach($liste as $nb => $ctr) {
        
        $contrats->fetch($ctr['rowid']);
        $datec = new DateTime($contrats->getData('datec'));
        $datec_limit = strtotime("2019-12-06 00:00:00"); // date de création du premier nouveau contrat
        
        if ($datec->getTimestamp() >= $datec_limit) {
            
            $maxDate = $contrats->db->getMax('contratdet', 'date_fin_validite', 'fk_contrat = ' . $contrats->id);
            
            $maxDateTm = strtotime($maxDate);
            
            if($maxDateTm >= $nowtmp) {
                $errors = $contrats->updateField('statut', 11);
                if(!count($errors))
                    echo $contrats->getData('ref') . ' ('.$maxDate.') <br />';
                else
                    echo 'ERREUR : ' .  $contrats->getData('ref') . ' ('.$maxDate.') <br />';
            }
            
        }
        
    }
}
<?php

exit; // Eviter l'exec du script.

// Init BimpCore:
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

// création de l'instance: 
$equipement = BimpObject::getInstance('bimpequipment', 'Equipment');

//$types = array(
//        1 => 'Ordinateur',
//        2 => 'Accessoire',
//        3 => 'License',
//        4 => 'Serveur',
//        5 => 'Matériel réseau',
//        6 => 'Autre'
//    );

// Assigne et vérifie les données: 
$equipement->validateArray(array(
    'id_product' => 123, // ID du produit. 
    'type' => 1, // cf $types
    'serial' => 'xxx', // num série
    'reserved' => 0, // réservé ou non
    'date_purchase' => 'AAAA-MM-JJ', // date d'achat
    'warranty_type' => 0, // type de garantie (liste non définie actuellement)
    'admin_login' => '', 
    'admin_pword' => '',
    'note' => ''
));

// Créé l'équipement: 
$errors = $equipement->create(); // si erreurs est vide, tout est OK. 

// Pour associer avec un contrat:
$asso = new BimpAssociation($equipement, 'contrats');
$id_contrat = 123;
$asso_errors = $asso->addObjectAssociation($id_contrat);

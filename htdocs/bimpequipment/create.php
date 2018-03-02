<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

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


// Pour ajouter un emplacement: 
$emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');

//$types = array(
//        1 => 'Client',
//        2 => 'Entrepôt',
//        3 => 'Utilisateur',
//        4 => 'Champ libre'
//    );

$emplacement->validateArray(array(
    'id_equipment' => $equipement->id,
    'type' => 1, // cf $types
    'id_client' => 123, // si type = 1
    'id_contact' => 123, // id contact associé au client
    'id_entrepot' => 123, // si type = 2
    'id_user' => 123, // si type = 3
    'place_name' => '', // Nom de l'emplacement si type = 4
    'infos' => '...', 
    'date' => 'AAAA-MM-JJ HH:MM:SS' // date et heure d'arrivée
));

$errors = $emplacement->create();


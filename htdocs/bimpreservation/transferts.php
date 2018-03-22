<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

// Création d'une réservation pour un transfert: 
$reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

// status pour les transferts: 
// 201: transfert en cours
// 301: transféré
// 303: réservation annulée

$errors = $reservation->validateArray(array(
    'id_entrepot' => 123, // ID entrepot: obligatoire. 
    'status'      => 201, // cf status
    'type'        => 2, // 2 = transfert
    'id_commercial', // id user du commercial (facultatif)
    'id_equipment', // si produit sérialisé
    'id_product', // sinon
    'id_transfert',
    'qty', // quantités si produit non sérialisé
    'date_from', // date de début de la résa (AAAA-MM-JJ HH:MM:SS) 
    'note' // note facultative
        ));

if (!count($errors)) {
    $errors = $reservation->create();
}


// Passer à transféré: 

$errors = $reservation->setNewStatus(301, $qty); // $qty : quantités auxquelles attribuer le nouveau statut. Faculatif, seulement pour les produits non sérialisés

if (!count($errors)) {
    $reservation->update();
}


// Annuler: idem que le statut transferé mais avec le code 303

// Lister des résas avec un id_transfert: 

$list = $reservation->getList(array(
    'id_transfert' => 123, // ID du transfert
    'type' => 2
), null, null, 'id', 'asc', 'array', array(
    'id' // Mettre ici la liste des champs à retourner. 
));

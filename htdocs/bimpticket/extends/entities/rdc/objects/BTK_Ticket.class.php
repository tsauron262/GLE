<?php
//Entity: Rue du commerce (rdc)

require_once DOL_DOCUMENT_ROOT . '/bimpticket/objects/Bimp_Ticket.class.php';

class Bimp_Ticket_ExtEntity extends Bimp_Ticket
{
	public static $types = array(
		1  => 'Intégration',
		2  => 'Offres',
		3  => 'Produits',
		4  => 'Création marque',
		5  => 'Livraison',
		6  => 'Frais de port',
		7  => 'Erreur fiche produit',
		8  => 'API',
		9  => 'Promotion',
		10 => 'Soldes'
	);
}

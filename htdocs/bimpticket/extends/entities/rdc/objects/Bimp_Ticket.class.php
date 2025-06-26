<?php
//Entity: rdc

require_once DOL_DOCUMENT_ROOT . '/bimpticket/objects/Bimp_Ticket.class.php';

class Bimp_Ticket_ExtEntity extends Bimp_Ticket {
	const MAIL_TICKET_GENERAL = 'service.marchands@rueducommerce.fr';
	const TYPE_TICKET_GENERAL = '';
	const MAIL_TICKET_SIGNALEMENT = 'moderation-mkp@rueducommerce.fr';
	const TYPE_TICKET_SIGNALEMENT = 'SIGNAL';
	const MAIL_TICKET_DEMANDE_ENTRANTE = 'marketplace@rueducommerce.fr';
	const TYPE_TICKET_DEMANDE_ENTRANTE = 'DEMENT';
	const MAIL_TICKET_QUALITE = 'qualite-marketplace@rueducommerce.fr';
	const TYPE_TICKET_QUALITE = 'QUA';
	const MAIL_TICKET_FORMULAIRE = 'partenariat_marketplace@rueducommerce.fr';
	const TYPE_TICKET_FORMULAIRE = 'FORMU';

	public static $mail_typeTicket = array(
		self::MAIL_TICKET_GENERAL          => self::TYPE_TICKET_GENERAL,
		self::MAIL_TICKET_SIGNALEMENT      => self::TYPE_TICKET_SIGNALEMENT,
		self::MAIL_TICKET_DEMANDE_ENTRANTE => self::TYPE_TICKET_DEMANDE_ENTRANTE,
		self::MAIL_TICKET_QUALITE          => self::TYPE_TICKET_QUALITE,
		self::MAIL_TICKET_FORMULAIRE       => self::TYPE_TICKET_FORMULAIRE
	);
}

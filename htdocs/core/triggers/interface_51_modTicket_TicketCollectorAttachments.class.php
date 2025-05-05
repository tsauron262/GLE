<?php

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceTicketCollectorAttachments extends DolibarrTriggers {
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->ticket) || !isModEnabled('ticket')) {
			return 0; // Module not active, we do nothing
		}

		switch ($action) {
			case 'TICKET_CREATE_echec':
				global $db;

				require_once DOL_DOCUMENT_ROOT.'/emailcollector/class/emailcollector.class.php';
				$collector = new EmailCollector($db);
				$collector->fetch(8);

				echo '<pre>';
				print_r($collector);
				echo '</pre>';

				exit('TICKET_CREATE trigger not implemented yet');
				break;
		}
	}
}

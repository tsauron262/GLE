<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpsupport/objects/BS_Ticket.class.php';

class BIC_UserTickets extends BS_Ticket {

    public function getListFiltersInterface() {
        global $userClient;
        return Array(
            Array(
                'name' => 'id_client',
                'filter' => $userClient->getData('attached_societe')
            ),
            Array(
                'name' => 'id_contrat',
                'filter' => $_REQUEST['id']
            )
        );
    }

    public function userClient($field) {
        global $userClient;
        return $userClient->getData($field);
    }

    public function currentContrat() {
        return $_REQUEST['id'];
    }

    public function create(&$warnings, $force_create = false) {
        global $userClient;
        if (parent::create($warnings, $force_create) > 1) {
            if (BimpTools::getValue('notif_email')) {
                $liste_destinataires = $userClient->getData('email');
                $listUser = $userClient->getList(array('attached_societe' => 142));
                foreach($listUser as $user) {
                    if($user['id'] != $userClient->getData('id') && $user['role'] == 1) {
                        $liste_destinataires .= ', ' . $user['email'];
                    }
                }
                mailSyn2('Création Ticket Support BIMP N°' . $this->getData('ticket_number'), $liste_destinataires, 'noreply@bimp.fr', 'Notification email');
            }
        }
    }

}

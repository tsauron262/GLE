<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpsupport/objects/BS_Ticket.class.php';

class BIC_UserTickets extends BS_Ticket {

    public function getListFiltersInterface($filter_send = null) {
        global $userClient;
        
        if(BimpTools::getContext() == 'public'){
            $filter = Array(Array('name' => 'id_client','filter' => $userClient->getData('attached_societe')));
        }
        
        if($filter_send == 'contrat') {
            $filter = array_merge($filter, Array(Array('name' => 'id_contrat','filter' => $_REQUEST['id'])));
        }
        if($filter_send == 'user') {
            $idUser = BimpTools::getValue("id");
            if($idUser < 1)
                $idUser = $userClient->id;
            $filter = array_merge($filter, Array(Array('name' => 'id_user_client','filter' => $idUser)));
        }
        return $filter;        
    }

    public function userClient($field) {
        global $userClient;
        if(isset($userClient)){
            return $userClient->getData($field);
        }
        
    }

    public function currentContrat() {
        return $_REQUEST['id'];
    }
    
    public function create(&$warnings, $force_create = false) {
        global $userClient;
        if (parent::create($warnings, $force_create) > 1) {
            if (BimpTools::getValue('notif_email')) {
                $liste_destinataires = $userClient->getData('email');
                $listUser = $userClient->getList(array('attached_societe' => $this->getData('attached_societe')));
                foreach ($listUser as $user) {
                    if ($user['id'] != $userClient->getData('id') && $user['role'] == 1) {
                        $liste_destinataires .= ', ' . $user['email'];
                    }
                }
                mailSyn2('Création Ticket Support BIMP N°' . $this->getData('ticket_number'), $liste_destinataires, 'noreply@bimp.fr', 'Notification email');
            }
        }
    }

}

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
            $this->updateField('impact_demande_client', BimpTools::getValue('impact'));
            $this->updateField('priorite_demande_client', BimpTools::getValue('priorite'));
            $this->updateField('cover', 1);
            $this->updateField('id_user_resp', 0);
                $liste_destinataires = Array($userClient->getData('email'));
                $liste_destinataires = array_merge($liste_destinataires, Array('hotline@bimp.fr'));
                $liste_destinataires = array_merge($liste_destinataires, $userClient->get_dest('admin'));
                $liste_destinataires = array_merge($liste_destinataires, $userClient->get_dest('commerciaux'));
                
                $prio = 'Non Urgent'; $prio = ($this->getData('priorite') == 2) ? 'Urgent' : $prio; $prio = ($this->getData('priorite') == 3) ? 'Très Urgent' : $prio;
                $impact = 'Faible'; $impact = ($this->getData('priorite') == 2) ? 'Moyen' : $impact; $impact = ($this->getData('priorite') == 3) ? 'Haut' : $impact;
                $tmpContrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
                mailSyn2('BIMP-CLIENT : Création Ticket Support N°' . $this->getData('ticket_number'), implode(', ', $liste_destinataires), 'noreply@bimp.fr',
                        '<h3>Ticket support numéro : '.$this->getData('ticket_number').'</h3>'
                        . 'Sujet du ticket : ' . $this->getData('sujet') . '<br />'
                        . 'Demandeur : ' . $userClient->getData('email') . '<br />'
                        . 'Contact dans la société : ' . $this->getData('contact_in_soc') . '<br />'
                        . 'Contrat : ' . $tmpContrat->getData('ref') . '<br />'
                        . 'Priorité : ' . $prio . '<br />'
                        . 'Impact : ' . $impact . '<br />'
                        );
                $tmpContrat = null;
        }
    }

}

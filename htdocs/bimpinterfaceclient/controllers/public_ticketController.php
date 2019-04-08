<?php

class public_ticketController extends Bimp_user_client_controller {
    public function renderHtml() {
        global $userClient;
        $tickets = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserTickets');
        $list = new BC_ListTable($tickets, 'interface_client', 1, null, 'Tickets', 'wrench');
        $list->addFieldFilterValue('id_client', (int) $userClient->getData("attached_societe"));
        return $list->renderHtml();
    }
}

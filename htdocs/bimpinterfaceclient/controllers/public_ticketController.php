<?php

class public_ticketController extends BimpController {
    public function renderHtml() {
        global $userClient;
        $tickets = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserTickets');
        $list = new BC_ListTable($tickets, 'interface_client', 1, null, 'Tickets', 'wrench');
        $list->addFieldFilterValue('id_client', (int) $userClient->getData("attached_societe"));
        return $list->renderHtml();
    }
}

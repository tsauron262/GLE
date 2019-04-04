<?php

class public_ticketController extends BimpController {
    public function renderHtml() {
        global $connected_client;
        $tickets = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserTickets');
        $list = new BC_ListTable($tickets, 'interface_client', 1, null, 'Tickets', 'wrench');
        $list->addFieldFilterValue('id_client', (int) $connected_client);
        return $list->renderHtml();
    }
}

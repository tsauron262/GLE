<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/controllers/BimpCommController.php';

class commandeController extends BimpCommController
{

    public function init()
    {
        global $langs;

        $langs->load('orders');
        $langs->load('sendings');
        $langs->load('companies');
        $langs->load('bills');
        $langs->load('propal');
        $langs->load('deliveries');
        $langs->load('sendings');
        $langs->load('products');
        $langs->load('other');
    }

    public function asSupport()
    {
        if(!BimpCore::getConf('use_tickets', 0, 'bimpsupport'))
                return 0;
        $tickets = BimpObject::getInstance('bimpsupport', 'BS_Ticket');
        $commande = $this->config->getObject('', 'commande');
        $lines = $commande->getChildrenList('lines');
        return ($tickets->getListCount(array('id_service'=> $lines)) > 0) ? 1 : 0;
    }

    public function renderSupport()
    {
        $commande = $this->config->getObject('', 'commande');
        $lines = $commande->getChildrenList('lines');

        $orderLine = BimpObject::getInstance('bimpsupport', 'BS_Ticket');
        $list = new BC_ListTable($orderLine, 'default', 1, $commande->id, 'Tickets commande ' . $commande->getNomUrl());
        $list->addFieldFilterValue('id_service', $lines);
        $html = $list->renderHtml();

        return $html;
    }
}

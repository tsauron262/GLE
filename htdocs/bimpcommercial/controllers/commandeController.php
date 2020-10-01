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

    private function getListSupport()
    {
        $commande = $this->config->getObject('', 'commande');
        $lines = $commande->getChildrenList('lines');

        $orderLine = BimpObject::getInstance('bimpsupport', 'BS_Ticket');
        $list = new BC_ListTable($orderLine, 'default', 1, $commande->id, 'Tickets commande ' . $commande->getNomUrl());
        $list->addFieldFilterValue('id_service', $lines);
        return $list;
    }

    public function asSupport()
    {
        $list = $this->getListSupport();
        $t = $list->getItems();
        return (count($t) > 0) ? 1 : 0;
    }

    public function renderSupport()
    {
        $list = $this->getListSupport();
        $html .= $list->renderHtml();

        return $html;
    }
}

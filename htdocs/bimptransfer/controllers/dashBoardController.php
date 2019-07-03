<?php

require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';

class dashBoardController extends BimpController {

    public function displayHead() {
        global $user;
        $id_warehouse = $this->getIdWarehouse();
        $transfer = BimpObject::getInstance('bimptransfer', 'Transfer');
        $warehouses = $transfer->getAllWarehouses();

        $html .= '<div id="warehouse_div" style="margin: 20px;float:left">';
        $html .= '<strong>Entrepot</strong> ';
        $html .= '<select id="warehouse_select" class="select2 cust" style="width: 200px;">';
        $html .= '<option></option>';

        foreach ($warehouses as $id => $name) {
            if ($id == $id_warehouse)
                $html .= '<option value="' . $id . '" selected>' . $name . '</option>';
            else
                $html .= '<option value="' . $id . '">' . $name . '</option>';
        }
        $html .= '</select> ';
        $html .= '</div>';
        $html .= '<br/>';


        //    $html .=$this->getButtonLink(Créer transfert" onclick="location.href=\'' . DOL_URL_ROOT . '/bimpequipment/manageequipment/viewTransfer.php?entrepot=' . fk_warehouse );';
        $html .= $this->getButtonLink('Accéder equipement', '/bimpequipment/?fc=entrepot&id=' . $id_warehouse);
        $html .= $this->getButtonLink('Accéder réservation', '/bimpreservation/index.php?fc=entrepot&id=' . $id_warehouse . '#all_res');
//    $html .=$this->getButtonLink(Accéder inventaire" onclick="location.href=\'' . DOL_URL_ROOT . '/bimpequipment/manageequipment/viewInventoryMain.php?entrepot=' . fk_warehouse );';
        if ((int) $user->rights->bimpequipment->caisse->read === 1)
            $html .= $this->getButtonLink('Accéder caisse', '/bimpcaisse/?id_entrepot=' . $id_warehouse);
        if ((int) $user->rights->bimpequipment->caisse_admin->read === 1)
            $html .= $this->getButtonLink('Accéder caisse admin', '/bimpcaisse/?fc=admin&id_entrepot=' . $id_warehouse);
        $html .= $this->getButtonLink('Tous les transferts', '/bimptransfer?entrepot_id=' . $id_warehouse);
//    $html .=$this->getButtonLink(BL Non envoyés" onclick="location.href=\'' . DOL_URL_ROOT . '/bimpreservation/index.php?fc=shipments&shipped=0&invoiced=0&id_entrepot=' . fk_warehouse );');
//    $html .=$this->getButtonLink(BL Non facturés" onclick="location.href=\'' . DOL_URL_ROOT . '/bimpreservation/index.php?fc=shipments&shipped=1&invoiced=0&id_entrepot=' . fk_warehouse );');


        echo $html;
//        $html = 'test';
    }

    public function renderSend() {

        $id_warehouse = $this->getIdWarehouse();
        $html = '<div class = "row">';
        $html .= '<div class = "col-lg-12">';

        // TRANSFER
        $transfer = BimpObject::getInstance('bimptransfer', 'Transfer');
        BimpObject::loadClass('bimptransfer', 'Transfer');
        $list = new BC_ListTable($transfer, 'dash_board_send', 1, null, 'Transfert: envoie');
        $list->addFieldFilterValue('id_warehouse_source', $id_warehouse);
        $list->addFieldFilterValue('status', (int) Transfer::STATUS_SENDING);
        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        // TRANSFER LINE
        $transfer_line = BimpObject::getInstance('bimptransfer', 'TransferLine');
        BimpObject::loadClass('bimptransfer', 'Transfer');
        $list = new BC_ListTable($transfer_line, 'dash_board_send', 1, null, 'Ligne de transfert: envoie');
        $list->addFieldFilterValue('t.id_warehouse_source', $id_warehouse);
        $list->addFieldFilterValue('t.status', (int) Transfer::STATUS_SENDING);
        $list->addJoin('bt_transfer', 'a.id_transfer = t.id', 't');
        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderReception() {

        $id_warehouse = $this->getIdWarehouse();
        $html = '<div class = "row">';
        $html .= '<div class = "col-lg-12">';

        // TRANSFER
        $transfer = BimpObject::getInstance('bimptransfer', 'Transfer');
        BimpObject::loadClass('bimptransfer', 'Transfer');
        $list = new BC_ListTable($transfer, 'dash_board_reception', 1, null, 'Transfert: réception');
        $list->addFieldFilterValue('id_warehouse_dest', $id_warehouse);
        $list->addFieldFilterValue('status', (int) Transfer::STATUS_RECEPTING);
        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        // TRANSFER LINE
        $transfer_line = BimpObject::getInstance('bimptransfer', 'TransferLine');
        BimpObject::loadClass('bimptransfer', 'Transfer');
        $list = new BC_ListTable($transfer_line, 'dash_board_reception', 1, null, 'Ligne de transfert: réception');
        $list->addFieldFilterValue('t.id_warehouse_dest', $id_warehouse);
        $list->addFieldFilterValue('t.status', (int) Transfer::STATUS_RECEPTING);
        $list->addJoin('bt_transfer', 'a.id_transfer = t.id', 't');
        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderOrderFourn() {


        $id_warehouse = $this->getIdWarehouse();
        $status_accepted = array(
            (int) CommandeFournisseur::STATUS_RECEIVED_PARTIALLY,
            (int) CommandeFournisseur::STATUS_ORDERSENT);

        $html = '<div class = "row">';
        $html .= '<div class = "col-lg-12">';

        // ORDER
        $transfer = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');
        $list = new BC_ListTable($transfer, 'default', 1, null, 'Commande fournisseur');
        $list->addFieldFilterValue('e.entrepot', $id_warehouse);
        $list->addFieldFilterValue('a.fk_statut', array('IN' => implode(',', $status_accepted)));
        $list->addJoin('commande_fournisseur_extrafields', 'a.rowid=e.fk_object', 'e');
        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        // ORDER LINE
        $transfer_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
        $list = new BC_ListTable($transfer_line, 'default', 1, null, 'Ligne de commande fournisseur');
        $list->addFieldFilterValue('e.entrepot', $id_warehouse);
        $list->addFieldFilterValue('c.fk_statut', array('IN' => implode(',', $status_accepted)));
        $list->addJoin('commande_fournisseur', 'a.id_obj=c.rowid', 'c');
        $list->addJoin('commande_fournisseur_extrafields', 'c.rowid=e.fk_object', 'e');
        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderTabsSection() {
        $id_warehouse = $this->getIdWarehouse();
        if ($id_warehouse > 0)
            parent::renderTabsSection();
    }

    private function getIdWarehouse() {
        global $user;
        $id_warehouse = (int) BimpTools::getValue('id_warehouse');
        if (!$id_warehouse > 0)
            $id_warehouse = (int) $user->array_options['options_defaultentrepot'];
        return $id_warehouse;
    }

    private function getButtonLink($label, $url) {
        return '<input type="button" class="butAction" value="' . $label .
                '" onclick="location.href=\'' . DOL_URL_ROOT . $url . '\'"/>';
    }

}

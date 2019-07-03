<?php

class dashBoardController extends BimpController {

    public function displayHead() {
        $id_warehouse = BimpTools::getValue('id_warehouse');

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

        $html .= '<br/><br/>';
        echo $html;
//        $html = 'test';
    }

    public function renderSend() {

        $id_warehouse = (int) BimpTools::getValue('id_warehouse');
        $html = '<div class="row">';
        $html .= '<div class="col-lg-12">';

        // TRANSFER
        $transfer = BimpObject::getInstance('bimptransfer', 'Transfer');
        BimpObject::loadClass('bimptransfer', 'Transfer');
        $list = new BC_ListTable($transfer, 'dash_board_send', 1, null, 'Transfert: envoie');
//(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
        $list->addFieldFilterValue('id_warehouse_source', $id_warehouse);
        $list->addFieldFilterValue('status', (int) Transfer::STATUS_SENDING);
        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        // TRANSFER LINE
        $transfer_line = BimpObject::getInstance('bimptransfer', 'TransferLine');
        BimpObject::loadClass('bimptransfer', 'Transfer');
        $list = new BC_ListTable($transfer_line, 'dash_board_send', 1, null, 'Ligne de transfert: envoie');
//(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
        $list->addFieldFilterValue('t.id_warehouse_source', $id_warehouse);
        $list->addFieldFilterValue('t.status', (int) Transfer::STATUS_SENDING);
        $list->addJoin('bt_transfer', 'a.id_transfer=t.id', 't');

        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderReception() {

        $id_warehouse = (int) BimpTools::getValue('id_warehouse');
        $html = '<div class="row">';
        $html .= '<div class="col-lg-12">';

        // TRANSFER
        $transfer = BimpObject::getInstance('bimptransfer', 'Transfer');
        BimpObject::loadClass('bimptransfer', 'Transfer');
        $list = new BC_ListTable($transfer, 'dash_board_reception', 1, null, 'Transfert: réception');
//(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
        $list->addFieldFilterValue('id_warehouse_dest', $id_warehouse);
        $list->addFieldFilterValue('status', (int) Transfer::STATUS_RECEPTING);
        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        // TRANSFER LINE
        $transfer_line = BimpObject::getInstance('bimptransfer', 'TransferLine');
        BimpObject::loadClass('bimptransfer', 'Transfer');
        $list = new BC_ListTable($transfer_line, 'dash_board_reception', 1, null, 'Ligne de transfert: réception');
//(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
        $list->addFieldFilterValue('t.id_warehouse_dest', $id_warehouse);
        $list->addFieldFilterValue('t.status', (int) Transfer::STATUS_RECEPTING);
        $list->addJoin('bt_transfer', 'a.id_transfer=t.id', 't');

        $list->setAddFormValues(array());
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderTabsSection() {
        $id_warehouse = BimpTools::getValue('id_warehouse');
        if ($id_warehouse > 0)
            parent::renderTabsSection();
    }

}

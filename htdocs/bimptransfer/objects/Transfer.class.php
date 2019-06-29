<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpRender.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class Transfer extends BimpDolObject {

    CONST CONTRAT_STATUS_SENDING = 0;
    CONST CONTRAT_STATUS_RECEPTING = 1;
    CONST CONTRAT_STATUS_CLOSED = 2;

    public static $status_list = Array(
        self::CONTRAT_STATUS_SENDING => Array('label' => 'En cours d\'envoie', 'classes' => Array('success')/* , 'icon' => 'fas_check' */),
        self::CONTRAT_STATUS_RECEPTING => Array('label' => 'En cours de réception', 'classes' => Array('warning')/* , 'icon' => 'fas_receipt' */),
        self::CONTRAT_STATUS_CLOSED => Array('label' => 'Fermé', 'classes' => Array('danger')/* , 'icon' => 'fas_trash-alt' */),
    );
    
    
    public function canDelete() {
        return 0;
    }

//fas_check fas_times fas_trash-alt
    public function update(&$warnings = array(), $force_update = false) {
        $status = (int) $this->getData('status');

        // Draft
        if ($status == 0) {
            $this->data['date_opening'] = '';
            $this->data['date_closing'] = '';
            // Open
        } elseif ($status == 1) {
            $this->data['date_opening'] = date("Y-m-d H:i:s");
            $this->data['date_closing'] = '';
        } elseif ($status == 2) {
            $this->data['date_closing'] = date("Y-m-d H:i:s");
        } else {
            $warnings[] = "Statut non reconnu, value = " . $status;
        }

        if (sizeof($warnings) == 0)
            $errors = parent::update($warnings);

        return $errors;
    }

    public function renderAddInputs() {
        global $user;
        $html = '';

        // status

        if ($this->isLoaded()) {
            if ($this->getData('status') == Transfer::CONTRAT_STATUS_CLOSED and ! $user->rights->bimptransfer->admin) {
                $html .= '<p>Le statut du transfert ne permet pas d\'ajouter des lignes</p>';
            } else {
                $header_table = 'Lignes ';
                $header_table .= '<span style="margin-left: 100px">Ajouter</span>';
                $header_table .= '<input class="search_list_input"  name="insert_line" type="text" style="width: 400px; margin-left: 10px;" value="" >';
                $header_table .= '<span style="margin-left: 100px">Quantité</span>';
                $header_table .= '<input class="search_list_input"  name="insert_quantity" type="number" min=1 style="width: 80px; margin-left: 10px;" value="1" >';

                $html = BimpRender::renderPanel($header_table, $html, '', array(
                            'foldable' => false,
                            'type' => 'secondary',
                            'icon' => 'fas_link',
                ));
//                require_once DOL_DOCUMENT_ROOT . '/bimptransfer/scan/scan.php';
            }
        }

        return $html;
    }

    public function getActionsButtons() {
        global $user;
        $buttons = array();
        if ($this->isLoaded()){
            if($this->getData('status') == Transfer::CONTRAT_STATUS_RECEPTING ){
                $buttons[] = array(
                    'label' => 'Transférer',
                    'icon' => 'fas_box',
                    'onclick' => $this->getJsActionOnclick('doTransfer', array(), array(
                        'success_callback' => 'function(result) {reloadTransfertLines();}',
                    ))
                );
                if ($this->isLoaded() and $user->rights->bimptransfer->admin) {
                    $buttons[] = array(
                        'label' => 'Transférer tous les produits envoyés',
                        'icon' => 'fas_box',
                        'onclick' => $this->getJsActionOnclick('doTransfer', array('total' => true), array(
                            'success_callback' => 'function(result) {reloadTransfertLines();}',
                        ))
                    );
                    $buttons[] = array(
                        'label' => 'Revenir en mode envoie',
                        'icon' => 'fas_box',
                        'onclick' => $this->getJsActionOnclick('setSatut', array("status"=>Transfer::CONTRAT_STATUS_SENDING), array(
                            'success_callback' => 'function(result) {reloadTransfertLines();}',
                        ))
                    );
                }
            }
            elseif($this->getData('status') == Transfer::CONTRAT_STATUS_SENDING){
                $buttons[] = array(
                    'label' => 'Validé envoie',
                    'icon' => 'fas_box',
                    'onclick' => $this->getJsActionOnclick('setSatut', array("status"=>Transfer::CONTRAT_STATUS_RECEPTING), array(
                        'success_callback' => 'function(result) {reloadTransfertLines();}',
                    ))
                );
            }
        }

        return $buttons;
    }
    
    public function actionSetSatut($data = array(), &$success = ''){
        $this->updateField("status", $data['status']);
    }

    public function actionDoTransfer($data = array(), &$success = '') {
        $errors = array();

        // Test if warehouse dest is set
        $id_warehouse_dest = $this->getData('id_warehouse_dest');
        if (!$id_warehouse_dest > 0) {
            $errors[] = "Merci de renseigner un entrepôt d'arrivé avant d'effectuer le transfert.";
            return $errors;
        }

        // TRANSFERT LINES
        $transfer_lines_obj = BimpObject::getInstance('bimptransfer', 'TransferLine');
        $transfer_lines = $transfer_lines_obj->getList(array(
            'id_transfer' => $this->getData('id')
                ), null, null, 'date_create', 'desc', 'array', array(
            'id',
            'id_product',
            'id_equipment',
            'quantity_sent',
            'quantity_received',
            'quantity_transfered'
        ));
        // Update all reservation for this transfer
        foreach ($transfer_lines as $t_line) {
            $transfer_lines_obj = BimpCache::getBimpObjectInstance('bimptransfer', 'TransferLine', $t_line['id']);
            if ($data['total']) {
                $transfer_lines_obj->set('quantity_received', $transfer_lines_obj->getData('quantity_sent'));
                $transfer_lines_obj->update();
            }
            $transfer_lines_obj->transfer();
        }

        return $errors;
    }

    public function canEditField($field_name) {
        global $user;
        if (($field_name == 'status' or $field_name == 'id_warehouse_dest') and ! $user->rights->bimptransfer->admin)
            return 0;

        return parent::canEditField($field_name);
    }

    public function userIsAdmin() {
        global $user;
        return $user->rights->bimptransfer->admin;
    }

    // TODO enlever le bouton plutôt que le désactiver pour ceux qui n'ont pas le droit
    public function create(&$warnings = array(), $force_create = false) {
//        if (!$this->userIsAdmin()) {
//            $warnings[] = "Vous n'avez pas les droits de créer un transfert.";
//            return;
//        }
        return parent::create($warnings, $force_create);
    }

}

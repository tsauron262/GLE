<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class Transfer extends BimpDolObject {

    CONST STATUS_SENDING = 0;
    CONST STATUS_RECEPTING = 1;
    CONST STATUS_CLOSED = 2;

    public static $status_list = Array(
        self::STATUS_SENDING => Array('label' => 'En cours d\'envoi', 'classes' => Array('success'), 'icon' => 'fas_cogs'),
        self::STATUS_RECEPTING => Array('label' => 'En cours de réception', 'classes' => Array('warning'), 'icon' => 'fas_arrow-alt-circle-down'),
        self::STATUS_CLOSED => Array('label' => 'Fermé', 'classes' => Array('danger'), 'icon' => 'fas_times')
    );

    public function canDelete() {
        global $user;
        return ($user->rights->bimptransfer->admin || $this->getData("user_create") == $user->id);
    }

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

    public function actionClose($params) {
        $errors = array();
        foreach ($this->getLines() as $line) {
            if (((int) $line->getData('quantity_sent') - $line->getData('quantity_received')) > 0)
                $errors = array_merge($errors, $line->cancelReservation());
        }
        if (count($errors) == 0)
            $errors = array_merge($errors, $this->updateField("status", self::STATUS_CLOSED));
        else {
            print_r($errors);
        }
        return $errors;
    }

    public function renderAddInputs() {
        global $user;
        $html = '';


        if (!$this->isEditable())
            return '';
        // status

        if ($this->isLoaded()) {
            if ($this->getData('status') == Transfer::STATUS_CLOSED and ! $user->rights->bimptransfer->admin) {
                $html .= '<p>Le statut du transfert ne permet pas d\'ajouter des lignes</p>';
            } else {
//                $header_table = 'Lignes ';
                $header_table .= '<span style="margin-left: 100px">Ajouter</span>';
//                $header_table .= '<input class="search_list_input"  name="insert_line" type="text" style="width: 400px; margin-left: 10px;" value="" >';
                $header_table .= BimpInput::renderInput('search_product', 'insert_line', (int) 11, array('filter_type' => 'both'));

                $header_table .= '<span style="margin-left: 100px">Quantité</span>';
                $header_table .= '<input class="search_list_input"  name="insert_quantity" type="number" min=1 style="width: 80px; margin-left: 10px;" value="1" >';

//                $header_table .= '<p class="inputHelp">Entrez la référence ou le code-barre d\'un produit.<br/>Laissez vide si vous sélectionnez un équipement.</p>';

                $html = BimpRender::renderPanel($header_table, $html, '', array(
                            'foldable' => false,
                            'type' => 'secondary',
                            'icon' => 'fas_plus-circle',
                ));
//                require_once DOL_DOCUMENT_ROOT . '/bimptransfer/scan/scan.php';
            }
        }

        return $html;
    }

    public function getActionsButtons() {
        global $user;
        $buttons = array();
        if (!$this->isLoaded())
            return $buttons;

        if ($this->getData('status') == Transfer::STATUS_RECEPTING) {
            $buttons[] = array(
                'label' => 'Transférer',
                'icon' => 'fas_box',
                'onclick' => $this->getJsActionOnclick('doTransfer', array(), array(
                    'success_callback' => 'function(result) {reloadTransfertLines();}',
                ))
            );
            if ($user->rights->bimptransfer->admin) {
                $buttons[] = array(
                    'label' => 'Réceptionner',
                    'icon' => 'fas_arrow-alt-circle-down',
                    'onclick' => $this->getJsActionOnclick('doTransfer', array(), array(
                        'success_callback' => 'function(result) {reloadTransfertLines();}',
                    ))
                );
                if ($user->rights->bimptransfer->admin) {
                    $buttons[] = array(
                        'label' => 'Réceptionner tous les produits envoyés',
                        'icon' => 'fas_arrow-alt-circle-down',
                        'onclick' => $this->getJsActionOnclick('doTransfer', array('total' => true), array(
                            'success_callback' => 'function(result) {reloadTransfertLines();}',
                        ))
                    );
                    $buttons[] = array(
                        'label' => 'Revenir en mode envoi',
                        'icon' => 'fas_undo',
                        'onclick' => $this->getJsActionOnclick('setSatut', array("status" => Transfer::STATUS_SENDING), array(
                            'success_callback' => 'function(result) {reloadTransfertLines();}',
                        ))
                    );
                }
                if ($user->rights->bimptransfer->admin || $this->isGood()) {
                    $buttons[] = array(
                        'label' => 'Terminer le transfert',
                        'icon' => 'fas_window-close',
                        'onclick' => $this->getJsActionOnclick('close', array(), array(
                            'success_callback' => 'function(result) {reloadTransfertLines();}',
                        ))
                    );
                }
            } elseif ($this->getData('status') == Transfer::STATUS_SENDING) {
                $buttons[] = array(
                    'label' => 'Valider envoi',
                    'icon' => 'fas_check-circle',
                    'onclick' => $this->getJsActionOnclick('setSatut', array("status" => Transfer::STATUS_RECEPTING), array(
                        'success_callback' => 'function(result) {reloadTransfertLines();}',
                    ))
                );
            }
        } elseif ($this->getData('status') == Transfer::STATUS_SENDING) {
            $buttons[] = array(
                'label' => 'Valider envoie',
                'icon' => 'fas_box',
                'onclick' => $this->getJsActionOnclick('setSatut', array("status" => Transfer::STATUS_RECEPTING), array(
                    'success_callback' => 'function(result) {reloadTransfertLines();}',
                ))
            );
        }

        return $buttons;
    }

    public function actionSetSatut($data = array(), &$success = '') {
        if ($data['status'] == Transfer::STATUS_CLOSED)
            $errors = $this->prepareClose();

        if (sizeof($errors) == 0)
            $this->updateField("status", $data['status']);

        $this->update();
    }

    public function prepareClose() {
        $errors = array();

        // TODO transféré ceux en cours avant ?
        $transfer_lines_obj = BimpObject::getInstance('bimptransfer', 'TransferLine');
        $transfer_lines = $transfer_lines_obj->getList(array(
            'id_transfer' => $this->getData('id')
                ), null, null, 'date_create', 'desc', 'array', array(
            'id'
//            'id_product',
//            'id_equipment',
//            'quantity_sent',
//            'quantity_received',
//            'quantity_transfered'
        ));

        foreach ($transfer_lines as $line) {
            $transfer_lines_obj->fetch($line['id']);
            $transfer_lines_obj->updateReservation(true);
        }

        // Date fermeture

        return $errors;
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
        // Update all reservation for this transfer
        foreach ($this->getLines() as $transfer_lines_obj) {
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
        if ($field_name == 'status')
            return 0;
        if ($field_name == 'id_warehouse_dest' && !$this->isDeletable())
            return 0;

        return parent::canEditField($field_name);
    }

    public function isEditable($force_edit = false) {
        if ($this->getData('status') == Transfer::STATUS_CLOSED)
            return 0;

        return parent::isEditable($force_edit);
    }

    public function isDeletable($force_delete = false) {
        foreach ($this->getLines() as $line) {
            if ($line->getData("quantity_transfered") > 0 || $line->getData("quantity_received") > 0)
                return 0;
        }
        return 1;
    }

    public function isGood() {
        foreach ($this->getLines() as $line) {
            if ($line->getData("quantity_transfered") < $line->getData("quantity_sent"))
                return 0;
        }
        return 1;
    }

    public function displayIsGood() {
        if ($this->isGood()) {
            return '<span class="success">OUI</span>';
        }

        return '</span class="danger">NON</span>';
    }

    public function getLines() {
        // TRANSFERT LINES
        $return = array();
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
            $return[] = $transfer_lines_obj;
        }
        return $return;
    }

    public function userIsAdmin() {
        global $user;
        return $user->rights->bimptransfer->admin;
    }

    public function userIsAdminOrStatusSending() {
        return (int) ($this->userIsAdmin() or (int) $this->getData('status') == (int) Transfer::STATUS_SENDING);
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

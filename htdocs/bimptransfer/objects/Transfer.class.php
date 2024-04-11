<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class Transfer extends BimpDolObject
{

    CONST STATUS_SENDING = 0;
    CONST STATUS_RECEPTING = 1;
    CONST STATUS_CLOSED = 2;

    public static $status_list = Array(
        self::STATUS_SENDING   => Array('label' => 'En cours d\'envoi', 'classes' => Array('success'), 'icon' => 'fas_cogs'),
        self::STATUS_RECEPTING => Array('label' => 'En cours de réception', 'classes' => Array('warning'), 'icon' => 'fas_arrow-alt-circle-down'),
        self::STATUS_CLOSED    => Array('label' => 'Fermé', 'classes' => Array('danger'), 'icon' => 'fas_times')
    );

    // Droits Users: 

    public function canDelete()
    {
        global $user;
        return ($user->rights->bimptransfer->admin || $this->getData("user_create") == $user->id);
    }

    public function canEditField($field_name)
    {
        global $user;
        if ($field_name == 'status')
            return 0;
        if ($field_name == 'id_warehouse_dest' && !$this->isDeletable())
            return 0;

        return parent::canEditField($field_name);
    }

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'addLinesFromCsv':
                return 1;//(int) $user->admin;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isEditable($force_edit = false, &$errors = array())
    {
        if ($this->getInitData('status') == Transfer::STATUS_CLOSED) {
            return 0;
        }

        return parent::isEditable($force_edit);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($this->isLoaded()) {
            foreach ($this->getLines() as $line) {
                if ($line->getData("quantity_transfered") > 0 || $line->getData("quantity_received") > 0)
                    return 0;
            }
        }
        return 1;
    }

    public function isActionAllowed($action, &$errors = []): int
    {
        if (in_array($action, array('setSatut', 'doTransfer', 'close', 'addLinesFromCsv'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        switch ($action) {
            case 'addLinesFromCsv':
                if ((int) $this->getData('status') !== self::STATUS_SENDING) {
                    $errors[] = 'Ce tranfert n\'est pas en cours d\'envoi';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isGood()
    {
        if ($this->isLoaded()) {
            foreach ($this->getLines() as $line) {
                if ($line->getData("quantity_transfered") < $line->getData("quantity_sent"))
                    return 0;
            }
            return 1;
        }
        return 0;
    }

    public function userIsAdmin()
    {
        global $user;
        return $user->rights->bimptransfer->admin;
    }

    public function userIsAdminOrStatusSending()
    {
        return (int) ($this->userIsAdmin() or (int) $this->getData('status') == (int) Transfer::STATUS_SENDING);
    }

    public function hasLines()
    {
        if ($this->isLoaded()) {
            if ((int) $this->db->getCount('bt_transfer_det', '`id_transfer` = ' . (int) $this->id)) {
                return 1;
            }
        }

        return 0;
    }

    // Getters Données: 
    
    public function displayValo()
    {
        $tot = 0;
        foreach($this->getLines() as $line){
//            $prod = $this->getChildObject('prod');
            $prod = $line->getChildObject('product');
            if(BimpObject::objectLoaded($prod)){
                $tot += $prod->getCurrentPaHt() * $line->getData('quantity_sent');
            }
        }
        return BimpTools::displayMoneyValue($tot). ' Ht';
    }

    public function getAllWarehouses()
    {
        // TODO est-ce que cette fonction existe quelque part ?
        // OUI:
        return BimpCache::getEntrepotsArray();
    }

    public function getActionsButtons()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        global $user;
        $buttons = array();

        if ($this->isActionAllowed('addLinesFromCsv') && $this->canSetAction('addLinesFromCsv')) {
            $buttons[] = array(
                'label'   => 'Import CSV',
                'icon'    => 'fas_file-download',
                'onclick' => $this->getJsLoadModalForm('csv', 'Import refs depuis CSV')
            );
        }

        if ($this->getData('status') == Transfer::STATUS_RECEPTING) {
            if ($user->rights->bimptransfer->admin || $this->isGood()) {
                $buttons[] = array(
                    'label'   => 'Terminer le transfert',
                    'icon'    => 'fas_window-close',
                    'onclick' => $this->getJsActionOnclick('setSatut', array("status" => Transfer::STATUS_CLOSED), array(
                        'success_callback' => 'function(result) {reloadTransfertLines(); removeInputs()}',
                    ))
                );
            }
        }
        if ($user->rights->bimptransfer->admin || $this->getData('status') == Transfer::STATUS_RECEPTING) {
            $buttons[] = array(
                'label'   => 'Transférer',
                'icon'    => 'fas_box',
                'onclick' => $this->getJsActionOnclick('doTransfer', array(), array(
                    'success_callback' => 'function(result) {reloadTransfertLines();}',
                ))
            );
        }
        if ($user->rights->bimptransfer->admin) {
            $buttons[] = array(
                'label'   => 'Réceptionner tous les produits envoyés',
                'icon'    => 'fas_arrow-alt-circle-down',
                'onclick' => $this->getJsActionOnclick('doTransfer', array('total' => true), array(
                    'success_callback' => 'function(result) {reloadTransfertLines();}',
                ))
            );
            if ($this->getData('status') == Transfer::STATUS_RECEPTING)
                $buttons[] = array(
                    'label'   => 'Revenir en mode envoi',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsActionOnclick('setSatut', array("status" => Transfer::STATUS_SENDING), array(
                        'success_callback' => 'function(result) {reloadTransfertLines();}',
                    ))
                );
        }
        if ($this->getData('status') == Transfer::STATUS_SENDING) {
            $buttons[] = array(
                'label'   => 'Valider envoi',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('setSatut', array("status" => Transfer::STATUS_RECEPTING), array(
                    'success_callback' => 'function(result) {reloadTransfertLines();}',
                ))
            );
        }

        if ($this->hasLines()) {
            $buttons[] = array(
                'label'   => 'Bon de transfert',
                'icon'    => 'fas_file-pdf',
                'onclick' => 'window.open(\'' . DOL_URL_ROOT . '/bimptransfer/pdf.php?id_transfer=' . (int) $this->id . '\')'
            );
        }

        return $buttons;
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->hasLines()) {
                $buttons[] = array(
                    'label'   => 'Bon de transfert',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => 'window.open(\'' . DOL_URL_ROOT . '/bimptransfer/pdf.php?id_transfer=' . $this->id . '\')'
                );
            }
        }

        return $buttons;
    }

    public function getLines()
    {
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

    // Affichage / Rendus HTML: 

    public function displayIsGood()
    {
        if ($this->isGood()) {
            return '<span class="success">OUI</span>';
        }

        return '</span class="danger">NON</span>';
    }

    public function renderAddInputs()
    {
        global $user;
        $html = '';

        if (!$this->isEditable())
            return '';
        // status

        if ($this->isLoaded()) {
            if ($this->getData('status') == Transfer::STATUS_CLOSED and!$user->rights->bimptransfer->admin) {
                $html .= '<p>Le statut du transfert ne permet pas d\'ajouter des lignes</p>';
            } else {
                $header_table .= '<span style="margin-left: 100px">Ajouter</span>';
                $header_table .= BimpInput::renderInput('search_product', 'insert_line', '', array('filter_type' => 'both'));

                $header_table .= '<span style="margin-left: 100px">Quantité</span>';
                $header_table .= '<input class=""  name="insert_quantity" type="number" min=1 style="width: 80px; margin-left: 10px;" value="1" >';

                $html = BimpRender::renderPanel($header_table, $html, '', array(
                            'foldable' => false,
                            'type'     => 'secondary',
                            'icon'     => 'fas_plus-circle',
                ));
            }
        }

        return $html;
    }

    // Traitements: 

    public function prepareClose()
    {
        $errors = array();
        global $user;

        $transfer_lines_obj = BimpObject::getInstance('bimptransfer', 'TransferLine');
        $transfer_lines = $transfer_lines_obj->getList(array(
            'id_transfer' => $this->getData('id')
                ), null, null, 'date_create', 'desc', 'array', array(
            'id'
        ));

        foreach ($transfer_lines as $line) {
            $transfer_lines_obj->fetch($line['id']);
            $errors = BimpTools::merge_array($errors, $transfer_lines_obj->transfer());
            $transfer_lines_obj->updateReservation(true);
        }

        $this->updateField('user_valid', (int) $user->id);

        return $errors;
    }

    public function addLinesFromCsv(&$warnings = array())
    {
        $errors = array();

        if (!$this->isActionAllowed('addlinesFromCsv', $errors)) {
            return $errors;
        }

        if (!$this->canSetAction('addLinesFromCsv')) {
            $errors[] = 'Vous n\'avez pas la permission';
            return $errors;
        }

        if (!isset($_FILES['csv_file'])) {
            $errors[] = 'Fichier CSV absent ou non transmis';
        } else {
            $lines = file($_FILES['csv_file']['tmp_name'], FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

            if (!is_array($lines) || empty($lines)) {
                $errors[] = 'Aucune ligne dans le fichier';
            } else {
                global $user;
                $now = date('Y-m-d H:i:s');
                $separator = BimpTools::getPostFieldValue('separator', ';');
                $recup_serials = 1; //(int) BimpTools::getPostFieldValue('recup_serials', 1);
                $test_only = (int) BimpTools::getPostFieldValue('test_only', 0);
                $id_entrepot = (int) $this->getData('id_warehouse_source');

                $i = 0;
                foreach ($lines as $line) {
                    $i++;

                    $data = explode($separator, $line);
                    if (!isset($data[0]) || !$data[0]) {
                        $errors[] = 'Ligne n° ' . $i . ': ref absente';
                    } elseif (!isset($data[1]) || !(float) $data[1]) {
                        $errors[] = 'Ligne n° ' . $i . ': aucune quantité '.$data[1];
                    } else {
                        $ref = $data[0];
                        $qty = (float) $data[1];

                        $prod = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                    'ref' => $ref
                                        ), true);

                        if (!BimpObject::objectLoaded($prod)) {
                            $errors[] = 'Ligne n° ' . $i . ': aucun produit trouvé pour la réf "' . $ref . '"';
                        } else {
                            if ($prod->isSerialisable()) {
                                if ($recup_serials) {
                                    $sql = BimpTools::getSqlSelect(array('a.id', 'a.serial'));
                                    $sql .= BimpTools::getSqlFrom('be_equipment', array(
                                                'p' => array(
                                                    'alias' => 'p',
                                                    'table' => 'be_equipment_place',
                                                    'on'    => 'p.id_equipment = a.id'
                                                )
                                    ));
                                    $sql .= BimpTools::getSqlWhere(array(
                                                'a.id_product'  => $prod->id,
                                                'p.position'    => 1,
                                                'p.id_entrepot' => $id_entrepot
                                    ));

                                    $rows = $this->db->executeS($sql, 'array');

                                    if (is_array($rows) && !empty($rows)) {
                                        if (count($rows) != $qty) {
                                            $warnings[] = 'Ligne n°' . $i . ': le nombre d\'équipements en stock ne correspond pas (attendu: ' . $qty . ' / en stock: ' . count($rows) . ')';
                                        } else {
                                            foreach ($rows as $r) {
                                                if (!$test_only) {
                                                    if ($this->db->insert('bt_transfer_det', array(
                                                                'id_transfer'   => $this->id,
                                                                'id_product'    => $prod->id,
                                                                'id_equipment'  => $r['id'],
                                                                'quantity_sent' => 1,
                                                                'user_create'   => $user->id,
                                                                'user_update'   => $user->id,
                                                                'date_create'   => $now,
                                                                'date_update'   => $now
                                                            )) <= 0) {
                                                        $errors[] = 'Ligne n° ' . $i . ': échec ajout serial "' . $r['serial'] . '" - ' . $this->db->err();
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        $warnings[] = 'Ligne n° ' . $i . ': aucun équipement trouvé - ' . $prod->getLink();
                                    }
                                } else {
                                    // todo (3è colonne serials) 
                                }
                            } else {
                                // recherche d'une ligne existante pour le produit: 
                                if ((int) $this->db->getCount('bt_transfer_det', 'id_transfer = ' . $this->id . ' AND id_product = ' . (int) $prod->id) > 0) {
                                    $warnings[] = 'Ligne n° ' . $i . ': il y a déjà une ligne de transfert pour le produit "' . $ref . '"';
                                    continue;
                                } else {
                                    if (!$test_only) {
                                        if ($this->db->insert('bt_transfer_det', array(
                                                    'id_transfer'   => $this->id,
                                                    'id_product'    => $prod->id,
                                                    'quantity_sent' => $qty,
                                                    'user_create'   => $user->id,
                                                    'user_update'   => $user->id,
                                                    'date_create'   => $now,
                                                    'date_update'   => $now
                                                )) <= 0) {
                                            $errors[] = 'Ligne n° ' . $i . ': échec ajout - ' . $this->db->err();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        return $errors;
    }

    // Actions: 

    public function actionDoTransfer($data = array(), &$success = '')
    {
        $errors = array();
        global $user;

        // Test if warehouse dest is set
        $id_warehouse_dest = $this->getData('id_warehouse_dest');
        if (!$id_warehouse_dest > 0) {
            $errors[] = "Merci de renseigner un entrepôt d'arrivé avant d'effectuer le transfert.";
        } else {
            // TRANSFERT LINES
            // Update all reservation for this transfer
            foreach ($this->getLines() as $transfer_lines_obj) {
                if ($data['total']) {
                    $transfer_lines_obj->set('quantity_received', $transfer_lines_obj->getData('quantity_sent'));
                    $transfer_lines_obj->update();
                }
                $transfer_lines_obj->transfer();
            }

            $this->updateField('user_valid', (int) $user->id);
        }

        return array('errors' => $errors);
    }

    public function actionClose($data = array(), &$success = '')
    {
        $errors = array();
        foreach ($this->getLines() as $line) {
            if (((int) $line->getData('quantity_sent') - (int) $line->getData('quantity_received')) > 0)
                $errors = BimpTools::merge_array($errors, $line->cancelReservation());
        }
        if (count($errors) == 0)
            $errors = BimpTools::merge_array($errors, $this->updateField("status", self::STATUS_CLOSED));
        else {
            print_r($errors);
        }

        return array('errors' => $errors);
    }

    public function actionSetSatut($data = array(), &$success = '')
    {
        $errors = array();
        $warnings = array();

        $success = 'Nouveau statut enregistré avec succès';

        if ($data['status'] == Transfer::STATUS_CLOSED) {
            $errors = $this->prepareClose();
        }

        if (sizeof($errors) == 0) {
            $this->set("status", $data['status']);
            $errors = $this->update($warnings, true);
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    // Overrides: 

    public function validate()
    {
        $errors = array();

        $status = (int) $this->getData('status');

        if (!$status) {
            $status = self::STATUS_SENDING;
        }

        if (!array_key_exists($status, self::$status_list)) {
            $errors[] = 'Statut invalide';
        } else {
            $init_status = (int) $this->getInitData('status');

            if ($init_status !== $status) {
                if ($status == self::STATUS_SENDING) {
                    $this->set("date_opening", '');
                    $this->set("date_closing", '');
                } elseif ($status == self::STATUS_RECEPTING) {
                    $this->set("date_opening", date("Y-m-d H:i:s"));
                    $this->set("date_closing", '');
                } elseif ($status == self::STATUS_CLOSED) {
                    $this->set("date_closing", date("Y-m-d H:i:s"));
                }
            }
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if (isset($_FILES['csv_file'])) {
            return $this->addLinesFromCsv($warnings);
        }

        return parent::update($warnings, $force_update);
    }
}

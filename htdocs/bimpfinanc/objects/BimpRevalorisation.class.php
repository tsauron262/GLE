<?php

class BimpRevalorisation extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'En Attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        10 => array('label' => 'Déclarée', 'icon' => 'fas_pause-circle', 'classes' => array('success')),
        1 => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        2 => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
    );
    public static $types = array(
        'crt'           => 'Remise CRT',
        'correction_pa' => 'Correction du prix d\'achat',
        'achat_sup'     => 'Achat complémentaire',
        'oth'           => 'Autre'
    );

    // Gestion des droits user: 

    public function canCreate()
    {
        global $user;
        return ($user->admin || $user->rights->bimpcommercial->reval->write);
    }

    public function canEdit()
    {
        return (int) $this->canCreate();
    }

    public function canValid()
    {
        global $user;
        return ($user->admin || $user->rights->bimpcommercial->reval->valid);
    }

    public function canView()
    {
        global $user;
        return ($user->admin || $user->rights->bimpcommercial->reval->read);
    }

    public function canDelete()
    {
        global $user;

        return (int) ($user->admin ? 1 : 0);
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'process':
            case 'cancelProcess':
                return $this->canValid();

            case 'addToCommission':
            case 'removeFromUserCommission':
            case 'removeFromEntrepotCommission':
                $commission = BimpObject::getInstance('bimpfinanc', 'BimpCommission');
                return (int) $commission->can('create');
        }

        return (int) parent::canSetAction($action);
    }

    public function getCommercialSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((int) $value) {
            $filters['typecont.element'] = 'facture';
            $filters['typecont.source'] = 'internal';
            $filters['typecont.code'] = 'SALESREPFOLL';
            $filters['elemcont.fk_socpeople'] = (int) $value;

            $joins['elemcont'] = array(
                'table' => 'element_contact',
                'on'    => 'elemcont.element_id = ' . $main_alias . '.id_facture',
                'alias' => 'elemcont'
            );
            $joins['typecont'] = array(
                'table' => 'c_type_contact',
                'on'    => 'elemcont.fk_c_type_contact = typecont.rowid',
                'alias' => 'typecont'
            );
        }
    }
    
    public function actionSetStatus($data, &$success){
        $success = 'Maj status OK';
        if ($this->canSetAction('process')) {
            if($data['status'] == 1 || $data['status'] == 2){
                foreach($data['id_objects'] as $nb => $idT){
                    $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $idT);
                    if(($instance->getData('type') == 'crt' && $instance->getData('status') != 10) || 
                            ($instance->getData('type') != 'crt' && $instance->getData('status') != 0)){
                        $errors[] = ($nb+1).' éme ligne séléctionné, statut : '.static::$status_list[$instance->getData('status')]['label'].' invalide pour passage au staut '.static::$status_list[$data['status']]['label'];
                    }
                    if(!$instance->isActionAllowed('process'))
                        $errors[] = ($nb+1).' éme ligne séléctionné opération impossible';
                }
                if(!count($errors)){
                    foreach($data['id_objects'] as $nb => $idT){
                        $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $idT);
                        $instance->updateField('status', $data['status']);
                    }
                }
            }
            else{
                $errors[] = 'Action non géré';
            }
        }
        else{
            $errors[] = 'Vous n\'avez pas la permission';
        }
        return $errors;
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'process':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ((int) $this->getData('status') !== 0 && $this->getData('status') !== 10) {
                    $errors[] = 'Cette revalorisation n\'est plus en attente d\'acceptation';
                    return 0;
                }

                return 1;

            case 'cancelProcess':
                switch ((int) $this->getData('status')) {
                    case 2:
                        return 1;

                    case 1:
                        if ((int) $this->getData('id_user_commission') || (int) $this->getData('id_entrepot_commission')) {
                            $user_commission = $this->getChildObject('user_commission');
                            if (BimpObject::objectLoaded($user_commission)) {
                                if ((int) $user_commission->getData('status') !== 0) {
                                    $errors[] = 'Cette revalorisation a été ajoutée à une commission utilisateur qui n\'est plus au statut "En attente"';
                                    return 0;
                                }
                            }
                            $entrepot_commission = $this->getChildObject('entrepot_commission');
                            if (BimpObject::objectLoaded($entrepot_commission)) {
                                if ((int) $entrepot_commission->getData('status') !== 0) {
                                    $errors[] = 'Cette revalorisation a été ajoutée à une commission entrepôt qui n\'est plus au statut "En attente"';
                                    return 0;
                                }
                            }
                        }
                        return 1;

                    case 0:
                        $errors[] = 'Cette revalorisation est déjà au statut "En attente"';
                        return 0;
                }

            case 'addToCommission':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ((int) $this->getData('status') !== 1) {
                    $errors[] = 'Cette revalorisation n\'a pas le statut "Acceptée"';
                    return 0;
                }
                if ((int) $this->getData('id_user_commission') && (int) $this->getData('id_entrepot_commission')) {
                    $errors[] = 'Cette revalorisation n\'est pas attribuable à une nouvelle commission';
                    return 0;
                }
                return 1;

            case 'removeFromUserCommission':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (!(int) $this->getData('id_user_commission')) {
                    $errors[] = 'Cette revalorisation n\'est attribuée à aucune commission utilisateur';
                    return 0;
                }
                $commission = $this->getChildObject('user_commission');
                if (BimpObject::objectLoaded($commission)) {
                    if ((int) $commission->getData('status') !== 0) {
                        $errors[] = 'La commission utilisateur n\'est plus au statut "brouillon"';
                        return 0;
                    }
                }
                return 1;

            case 'removeFromEntrepotCommission':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (!(int) $this->getData('id_entrepot_commission')) {
                    $errors[] = 'Cette revalorisation n\'est attribuée à aucune commission entrepôt';
                    return 0;
                }
                $commission = $this->getChildObject('entrepot_commission');
                if (BimpObject::objectLoaded($commission)) {
                    if ((int) $commission->getData('status') !== 0) {
                        $errors[] = 'La commission entrepôt n\'est plus au statut "brouillon"';
                        return 0;
                    }
                }
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('id_facture', 'id_facture_line', 'qty'))) {
            if ((int) $this->getData('status') !== 0) {
                return 0;
            }
        }
        if (in_array($field, array('amount'))) {
            if ((int) $this->getData('status') !== 0 && (int) $this->getData('status') !== 10) {
                return 0;
            }
        }

        return parent::isFieldEditable($field, $force_edit);
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        $isGlobal = BimpTools::getValue('global', 0);
        if($isGlobal){
            if($this->getData('type') == 'crt')
                return array('Type CRT non valable pour les revalorisation global');
            
            $_POST['global'] = 0;
            $amount = $this->getData('amount');
            $fact = $this->getChildObject('facture');
            $totalFact = $fact->getData('total');
            $lines = $fact->getLines();
            foreach($lines as $line){
                $totalLine = $line->getTotalHTWithRemises();
                $revalLineAmount = $amount / $totalFact * $totalLine;
                if($revalLineAmount !=  0){
                    $this->set('id_facture_line', $line->id);
                    $this->set('amount', $revalLineAmount);
                    $this->create();
                }
            }
        }
        else
            return parent::create($warnings, $force_create);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ((int) $this->getData('status') === 1) {
            return 0;
        }

        return 1;
    }

    // Getters array: 

    public function getDraftCommissionArray()
    {
        $return = array();

        if ($this->isLoaded()) {
            $facture = $this->getChildObject('facture');
            if (BimpObject::objectLoaded($facture)) {
                $id_entrepot = (int) $facture->getData('entrepot');
                if ($id_entrepot) {
                    if (!(int) $this->getData('id_user_commission')) {
                        $has_user_commissions = (int) $this->db->getValue('entrepot', 'has_users_commissions', '`rowid` = ' . $id_entrepot);
                        if ($has_user_commissions) {
                            $id_user = (int) $facture->getCommercialId();
                            if ($id_user) {
                                foreach (BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpCommission', array(
                                    'type'    => 1,
                                    'id_user' => $id_user,
                                    'status'  => 0
                                )) as $commission) {
                                    $return[(int) $commission->id] = $commission->getName() . ' (' . $commission->displayData('date', 'default', false, true) . ')';
                                }
                            }
                        }
                    }

                    if (!(int) $this->getData('id_entrepot_commission')) {
                        $has_entrepot_commissions = (int) $this->db->getValue('entrepot', 'has_entrepot_commissions', '`rowid` = ' . $id_entrepot);
                        if ($has_entrepot_commissions) {
                            foreach (BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpCommission', array(
                                'type'        => 2,
                                'id_entrepot' => $id_entrepot,
                                'status'      => 0
                            )) as $commission) {
                                $return[(int) $commission->id] = $commission->getName() . ' (' . $commission->displayData('date', 'default', false, true) . ')';
                            }
                        }
                    }
                }
            }
        }

        return $return;
    }

    public function getFactureLinesArray()
    {
        $return = array();
        $id_facture = (int) BimpTools::getPostFieldValue('id_facture', (int) $this->getData('id_facture'));

        if ($id_facture) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);


            if (BimpObject::objectLoaded($facture)) {
                $lines = $facture->getLines('not_text');
                foreach ($lines as $line) {
                    $return[(int) $line->id] = 'N°' . $line->getData('position') . ' - ' . str_replace('<br/>', ' ', $line->displayLineData('desc_light'));
                }
            }
        }

        return $return;
    }

    // Getters Données: 

    public function getTotal()
    {
        return (float) $this->getData('amount') * (float) $this->getData('qty');
    }

    public function getDefaultQty()
    {
        if (!$this->isLoaded()) {
            if ((int) $this->getData('id_facture_line')) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', (int) $this->getData('id_facture_line'));
                if (BimpObject::objectLoaded($line)) {
                    return (float) $line->qty;
                }
            }
        }
        
        if (isset($this->data['qty'])) { // Pas de $this->getData('qty') sinon boucle infinie... 
            return (float) $this->data['qty'];
        }
        
        return 0;
    }
    
    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'id_product':
                $alias = 'f';
                $alias2 = 'f_det';
                if (!$excluded) {
                    $joins[$alias] = array(
                        'alias' => $alias,
                        'table' => 'facture',
                        'on'    => $alias . '.rowid'  . ' = a.id_facture'
                    );
                    $joins[$alias2] = array(
                        'alias' => $alias2,
                        'table' => 'facturedet',
                        'on'    => $alias2 . '.fk_facture'  . ' = '.$alias.'.rowid'
                    );
                    $key = 'in';
                    if ($excluded) {
                        $key = 'not_in';
                    }
                    $filters[$alias2 . '.fk_product'] = array(
                        $key => $values
                    );
                } else {
                    $alias .= '_not';
                    $filters['a.' . $this->getPrimary()] = array(
                        'not_in' => '(SELECT ' . $alias . '.' . $line::$dol_line_parent_field . ' FROM ' . MAIN_DB_PREFIX . $line::$dol_line_table . ' ' . $alias . ' WHERE ' . $alias . '.fk_product' . ' IN (' . implode(',', $values) . '))'
                    );
                }
                break;

            
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }
    
        public function getCustomFilterValueLabel($field_name, $value)
    {
        switch ($field_name) {
            case 'id_product':
                $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $value);
                if (BimpObject::ObjectLoaded($product)) {
                    return $product->getRef();
                }
                break;
        }
    }


    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = $this->getCommissionListButtons();

        if ($this->isLoaded()) {
            if ($this->isActionAllowed('process') && $this->canSetAction('process')) {
                if($this->getData('status') == 0 && $this->getData('type') == 'crt')
                    $buttons[] = array(
                        'label'   => 'Déclarer',
                        'icon'    => 'fas_pause-circle',
                        'onclick' => $this->getJsActionOnclick('process', array(
                            'type' => 'declarer'
                                ), array())
                    );
                if($this->getData('status') == 10 || $this->getData('type') != 'crt'){
                    $buttons[] = array(
                        'label'   => 'Accepter',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('process', array(
                            'type' => 'accept'
                                ), array(
                            'confirm_msg' => 'Veuillez confirmer l\\\'acceptation de cette revalorisation'
                        ))
                    );
                    $buttons[] = array(
                        'label'   => 'Refuser',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('process', array(
                            'type' => 'refuse'
                                ), array(
                            'confirm_msg' => 'Veuillez confirmer le refus de cette revalorisation'
                        ))
                    );
                }
            } elseif ($this->isActionAllowed('cancelProcess') && $this->canSetAction('cancelProcess')) {
                $label = 'Annuler ';
                switch ((int) $this->getData('status')) {
                    case 1:
                        $label .= 'l\'acceptation';
                        break;

                    case 2:
                        $label .= 'le refus';
                        break;
                }
                $buttons[] = array(
                    'label'   => $label,
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsActionOnclick('cancelProcess', array(), array(
                        'confirm_msg' => 'Veuillez confirmer la remise au statut brouillon de cette revalorisation'
                    ))
                );
            }
        }

        return $buttons;
    }

    public function getCommissionListButtons($comm_type = null)
    {
        $buttons = array();

        if (is_null($comm_type) || (int) $comm_type === 1) {
            if ($this->isActionAllowed('removeFromUserCommission') && $this->canSetAction('removeFromUserCommission')) {
                $commission = $this->getChildObject('user_commission');
                if (BimpObject::objectLoaded($commission)) {
                    $buttons[] = array(
                        'label'   => 'Retirer de la commission utilisateur #' . $commission->id,
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('removeFromUserCommission', array(), array(
                            'confirm_msg' => 'Veuillez confirmer le retrait de la commission #' . $commission->id
                        ))
                    );
                }
            }
        }

        if (is_null($comm_type) || (int) $comm_type === 2) {
            if ($this->isActionAllowed('removeFromEntrepotCommission') && $this->canSetAction('removeFromEntrepotCommission')) {
                $commission = $this->getChildObject('entrepot_commission');
                if (BimpObject::objectLoaded($commission)) {
                    $buttons[] = array(
                        'label'   => 'Retirer de la commission entrepôt #' . $commission->id,
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('removeFromEntrepotCommission', array(), array(
                            'confirm_msg' => 'Veuillez confirmer le retrait de la commission entrepôt #' . $commission->id
                        ))
                    );
                }
            }
        }

        if (is_null($comm_type) ||
                ($comm_type === 1 && !(int) $this->getData('id_user_commission')) ||
                ($comm_type === 2 && !(int) $this->getData('id_entrepot_commission'))) {
            if ($this->isActionAllowed('addToCommission') && $this->canSetAction('addToCommission')) {
                $buttons[] = array(
                    'label'   => 'Ajouter à une commission',
                    'icon'    => 'fas_comment-dollar',
                    'onclick' => $this->getJsActionOnclick('addToCommission', array(), array(
                        'form_name' => 'add_to_commission'
                    ))
                );
            }
        }

        return $buttons;
    }

    // Affichage: 

    public function displayDesc()
    {
        $html = '';

        if ($this->isLoaded()) {
            $line = $this->getChildObject('facture_line');
            if (BimpObject::objectLoaded($line)) {
                $html .= 'Ligne n°' . $line->getData('position') . '<br/>';
                $html .= $line->displayLineData('desc_light');
            } elseif ($this->getData('id_facture_line')) {
                $html .= $this->renderChildUnfoundMsg('id_facture_line');
            }
        }

        return $html;
    }

    public function displayTotal()
    {
        return BimpTools::displayMoneyValue((float) $this->getTotal());
    }

    public function displayCommissions()
    {
        $html = '';
        if ($this->isLoaded()) {
            $user_comm = $this->getChildObject('user_commission');
            if (BimpObject::objectLoaded($user_comm)) {
                $html .= 'Utilisateur: ' . $user_comm->getNomUrl(1, 1, 1, 'default');
            }

            $entrepot_comm = $this->getChildObject('entrepot_commission');
            if (BimpObject::objectLoaded($entrepot_comm)) {
                $html .= ($html ? '<br/>' : ' ') . 'Entrepôt: ' . $entrepot_comm->getNomUrl(1, 1, 1, 'default');
            }
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $status = (int) $this->getData('status');
            if ($status > 0) {
                $html .= '<div class="object_header_infos">';
                $user = $this->getChildObject('user_processed');
                $dt = new DateTime($this->getData('date_processed'));

                switch ($status) {
                    case 1:
                        $html .= 'Acceptée';
                        break;

                    case 2:
                        $html .= 'Refusée';
                }

                $html .= ' le ' . $dt->format('d / m / Y');

                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par ' . BimpObject::getInstanceNomUrl($user);
                }

                $html .= '</div>';
            }
        }

        return $html;
    }

    // Actions 

    public function actionProcess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $type = (isset($data['type']) ? $data['type'] : '');
        if (!in_array($type, array('accept', 'refuse', 'declarer'))) {
            $errors[] = 'Type de processus (acceptation ou refus) absent ou invalide';
        } else {
            global $user;
            if (BimpObject::objectLoaded($user)) {
                $this->set('id_user_processed', (int) $user->id);
            }
            $this->set('date_processed', date('Y-m-d'));

            switch ($type) {
                case 'accept':
                    $success = 'Acceptation de la revalorisation effectuée avec succès';
                    $this->set('status', 1);
                    break;
                case 'declarer':
                    $success = 'Acceptation de la revalorisation effectuée avec succès';
                    $this->set('status', 10);
                    break;

                case 'refuse':
                    $success = 'Refus de la revalorisation effectué avec succès';
                    $this->set('status', 2);
                    break;
            }

            $errors = $this->update($warnings, true);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancelProcess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise en attente d\'acceptation effectuée avec succès';

        $this->set('id_user_processed', 0);
        $this->set('date_processed', '');
        $this->set('id_commission', 0);
        $this->set('status', 0);

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddToCommission($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_commission = (isset($data['id_commission']) ? (int) $data['id_commission'] : 0);

        if (!$id_commission) {
            $errors[] = 'Aucune commission sélectionnée';
        } else {
            $commission = BimpCache::getBimpObjectInstance('bimpfinanc', 'BimpCommission', $id_commission);

            if (!BimpObject::objectLoaded($commission)) {
                $errors[] = 'La commission d\'ID ' . $id_commission . ' n\'existe pas';
            } elseif ((int) $commission->getData('status') !== 0) {
                $errors[] = 'La commission #' . $commission->id . ' n\'est plus au statut "brouillon"';
            } else {
                $draftCommissions = $this->getDraftCommissionArray();
                if (!array_key_exists((int) $commission->id, $draftCommissions)) {
                    $errors[] = 'Cette revalorisation n\'est pas attribuable à la commission #' . $commission->id;
                }
            }
        }

        if (!count($errors)) {
            switch ((int) $commission->getData('type')) {
                case BimpCommission::TYPE_USER:
                    if ((int) $this->getData('id_user_commission')) {
                        $errors[] = 'Cette révalorisation est déjà attribuée à une commission utilisateur';
                    } else {
                        $errors = $this->updateField('id_user_commission', (int) $commission->id);
                    }
                    break;

                case BimpCommission::TYPE_ENTREPOT:
                    if ((int) $this->getData('id_entrepot_commission')) {
                        $errors[] = 'Cette révalorisation est déjà attribuée à une commission entrepôt';
                    } else {
                        $errors = $this->updateField('id_entrepot_commission', (int) $commission->id);
                    }
                    break;
            }


            if (!count($errors)) {
                $comm_warnings = array();
                $comm_errors = $commission->updateAmounts($comm_warnings);

                if (count($comm_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($comm_warnings, 'Erreurs lors de la mise à jour des montants de la commission #' . $commission->id);
                }

                if (count($comm_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Echec de la mise à jour des montants de la commission #' . $commission->id);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveFromUserCommission($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Retrait de la commission utilisateur effectuée avec succès';

        $commission = $this->getChildObject('user_commission');

        if (!BimpObject::objectLoaded($commission)) {
            $errors[] = 'Commission absente ou invalide';
        } else {
            $errors = $this->updateField('id_user_commission', 0);

            if (!count($errors)) {
                $comm_warnings = array();
                $comm_errors = $commission->updateAmounts($comm_warnings);

                if (count($comm_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($comm_warnings, 'Erreurs lors de la mise à jour des montants de la commission #' . $commission->id);
                }

                if (count($comm_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Echec de la mise à jour des montants de la commission #' . $commission->id);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveFromEntrepotCommission($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Retrait de la commission entrepôt effectuée avec succès';

        $commission = $this->getChildObject('entrepot_commission');

        if (!BimpObject::objectLoaded($commission)) {
            $errors[] = 'Commission absente ou invalide';
        } else {
            $errors = $this->updateField('id_entrepot_commission', 0);

            if (!count($errors)) {
                $comm_warnings = array();
                $comm_errors = $commission->updateAmounts($comm_warnings);

                if (count($comm_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($comm_warnings, 'Erreurs lors de la mise à jour des montants de la commission #' . $commission->id);
                }

                if (count($comm_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Echec de la mise à jour des montants de la commission #' . $commission->id);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}

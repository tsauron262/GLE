<?php

class BimpRevalorisation extends BimpObject
{

    const STATUS_ATTENTE = 0;
    const STATUS_DECLARED = 10;
    const STATUS_ATT_EQUIPMENTS = 20;
    const STATUS_ACCEPTED = 1;
    const STATUS_REFUSED = 2;

    public static $status_list = array(
        0  => array('label' => 'En Attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        10 => array('label' => 'Déclarée', 'icon' => 'fas_pause-circle', 'classes' => array('success')),
        20 => array('label' => 'Attente équipements', 'icon' => 'fas_pause-circle', 'classes' => array('warning')),
        1  => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        2  => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
    );
    public static $types = array(
        'crt'            => 'CRT',
        'correction_pa'  => 'Correction du prix d\'achat',
        'achat_sup'      => 'Achat complémentaire',
        'commission_app' => 'Commission Apporteur',
        'applecare'      => 'Commission AppleCare',
        'fac_ac'         => 'Facturation commission AC',
        'oth'            => 'Autre'
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

        return (int) ($user->admin || $this->canEdit() ? 1 : 0);
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

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'process':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ((int) $this->getData('status') !== 0 && $this->getData('status') !== 10 && $this->getData('status') !== 20) {
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
            if ((int) $this->getData('status') !== 0 && $this->getData('status') !== 20) {
                return 0;
            }
        }
        if (in_array($field, array('amount'))) {
            if ((int) $this->getData('status') !== 0 && (int) $this->getData('status') !== 10 && $this->getData('status') !== 20) {
                return 0;
            }
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ((int) $this->getData('status') === 1) {
            return 0;
        }

        return 1;
    }

    public function isValidable($manuel = false)
    {
        if ($this->getData('type') == 'crt') {
            if ($this->getData('status') == 10)
                return 1;
        } elseif ($this->getData('type') == 'applecare') {
            if ($this->getData('status') == 0) {
                return 1;
            }
        } elseif ($this->getData('type') == 'fac_ac') {
            return 0;
        } else {
            return 1;
        }

        return 0;
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

    public function getAvailableEquipmentsArray()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $items = array();
        $factures = array();
        $facture = $this->getChildObject('facture');
        $all_eqs = array();

        if (BimpObject::objectLoaded($facture)) {
            $factures[$facture->id] = array(
                'ref'   => $facture->getRef(),
                'prods' => array()
            );
            $id_client = (int) $facture->getData('fk_soc');
            if ($id_client) {
                $where = 'fk_soc = ' . $id_client . ' AND fk_statut IN (0,1,2) AND type IN (0,1,2)';
                $facs = $this->db->getRows('facture', $where, null, 'array', array('rowid', 'ref'));

                if (is_array($facs)) {
                    foreach ($facs as $f) {
                        $factures[(int) $f['rowid']] = array(
                            'ref'   => $f['ref'],
                            'prods' => array()
                        );
                    }
                }
            }

            foreach ($factures as $id_fac => $fac_data) {
                $prods = array();
                $sql = BimpTools::getSqlFullSelectQuery('object_line_equipment', array('e.id', 'e.id_product', 'e.serial', 'p.ref'), array(
                            'f.rowid'        => $id_fac,
                            'a.object_type'  => 'facture',
                            'a.id_equipment' => array(
                                'operator' => '>',
                                'value'    => 0
                            ),
                            'e.id_product'   => array(
                                'operator' => '>',
                                'value'    => 0
                            ),
                                ), array(
                            'l' => array(
                                'alias' => 'l',
                                'table' => 'bimp_facture_line',
                                'on'    => 'l.id = a.id_object_line'
                            ),
                            'f' => array(
                                'alias' => 'f',
                                'table' => 'facture',
                                'on'    => 'f.rowid = l.id_obj'
                            ),
                            'e' => array(
                                'alias' => 'e',
                                'table' => 'be_equipment',
                                'on'    => 'e.id = a.id_equipment'
                            ),
                            'p' => array(
                                'alias' => 'p',
                                'table' => 'product',
                                'on'    => 'p.rowid = e.id_product'
                            )
                ));

                $rows = $this->db->executeS($sql, 'array');

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        if (in_array((int) $r['id'], $all_eqs)) {
                            continue;
                        }

                        $id = $this->db->getValue('bimp_revalorisation', 'id', 'equipments LIKE \'[' . (int) $r['id'] . ']\' AND type = \'applecare\' AND id != ' . $this->id);
                        if ($id) {
                            continue;
                        }

                        if (!isset($prods[(int) $r['id_product']])) {
                            $prods[(int) $r['id_product']] = array(
                                'ref' => $r['ref'],
                                'eqs' => array()
                            );
                        }

                        $prods[(int) $r['id_product']]['eqs'][(int) $r['id']] = $r['serial'];
                        $all_eqs[] = (int) $r['id'];
                    }

                    if (!empty($prods)) {
                        $factures[$id_fac]['prods'] = $prods;
                    }
                }
            }

            foreach ($factures as $id_fac => $fac_data) {
                if (!empty($fac_data['prods'])) {
                    $fac_item = array(
                        'label'      => 'Facture ' . $fac_data['ref'],
                        'selectable' => 0,
                        'open'       => ($id_fac == $facture->id ? 1 : 0),
                        'children'   => array()
                    );

                    foreach ($fac_data['prods'] as $id_prod => $prod_data) {
                        if (empty($prod_data['eqs'])) {
                            continue;
                        }

                        $prod_item = array(
                            'label'      => 'Produit ' . $prod_data['ref'],
                            'selectable' => 0,
                            'open'       => 1,
                            'children'   => array()
                        );

                        foreach ($prod_data['eqs'] as $id_eq => $serial) {
                            $prod_item['children'][$id_eq] = $serial;
                        }

                        $fac_item['children'][] = $prod_item;
                    }

                    $items[] = $fac_item;
                }
            }
        }

        $other_eqs = array();
        $cur_eqs = $this->getData('equipments');
        foreach ($cur_eqs as $id_cur_eq) {
            if (!in_array((int) $id_cur_eq, $all_eqs)) {
                $other_eqs[] = $id_cur_eq;
            }
        }

        if (!empty($other_eqs)) {
            $other_item = array(
                'label'      => 'Autre(s) client(s)',
                'selectable' => 0,
                'open'       => 1,
                'children'   => array()
            );

            foreach ($other_eqs as $id_other_eq) {
                $serial = $this->db->getValue('be_equipment', 'serial', 'id = ' . $id_other_eq);

                if ($serial) {
                    $other_item['children'][$id_other_eq] = $serial;
                }
            }

            if (!empty($other_item['children'])) {
                $items[] = $other_item;
            }
        }


        return $items;
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

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'id_product':
                if (!$excluded) {
                    $fac_alias = $main_alias . '___facture';
                    $joins[$fac_alias] = array(
                        'alias' => $fac_alias,
                        'table' => 'facture',
                        'on'    => $fac_alias . '.rowid' . ' = ' . $main_alias . '.id_facture'
                    );

                    $line_alias = $main_alias . '___facturedet';
                    $joins[$line_alias] = array(
                        'alias' => $line_alias,
                        'table' => 'facturedet',
                        'on'    => $line_alias . '.fk_facture' . ' = ' . $fac_alias . '.rowid'
                    );
                    $key = 'in';
                    if ($excluded) {
                        $key = 'not_in';
                    }
                    $filters[$line_alias . '.fk_product'] = array(
                        $key => $values
                    );
                } else {
                    $line_alias = $main_alias . '___facturedet_not';
                    $filters[$main_alias . '.' . $this->getPrimary()] = array(
                        'not_in' => '(SELECT ' . $line_alias . '.' . $line::$dol_line_parent_field . ' FROM ' . MAIN_DB_PREFIX . $line::$dol_line_table . ' ' . $line_alias . ' WHERE ' . $line_alias . '.fk_product' . ' IN (' . implode(',', $values) . '))'
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
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
                if ($this->getData('status') == 0 && $this->getData('type') == 'crt')
                    $buttons[] = array(
                        'label'   => 'Déclarer',
                        'icon'    => 'fas_pause-circle',
                        'onclick' => $this->getJsActionOnclick('process', array(
                            'type' => 'declarer'
                                ), array())
                    );
                if ($this->isValidable(true)) {
                    $buttons[] = array(
                        'label'   => 'Accepter',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('process', array(
                            'type' => 'accept'
                                ), array(
                            'confirm_msg' => 'Veuillez confirmer l\\\'acceptation de cette revalorisation'
                        ))
                    );
                }
                if ($this->getData('status') == 0 || $this->getData('status') == 10 || $this->getData('status') == 20) {
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
                if (!in_array((int) $this->getData('status'), array(1, 2)) && $this->getData('type') == 'applecare') {
                    $buttons[] = array(
                        'label'   => 'Attribuer les équipements',
                        'icon'    => 'fas_arrow-circle-down',
                        'onclick' => $this->getJsLoadModalForm('set_equipments', 'Attribuer les équipements')
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

    // Traitements: 

    public function checkSerials($update = false, &$nb_ok = 0)
    {
        $type = $this->getData('type');
        if (!in_array($type, array('applecare', 'fac_ac'))) {
            return array();
        }

        $serials = $this->getData('serial');

        if (!$serials) {
            return array();
        }

        $errors = array();
        $serials = str_replace(array(',', ';', "\n", "\t"), array(' ', ' ', ' ', ' '), $serials);
        $serials = explode(' ', $serials);

        $equipments = $this->getData('equipments');

        $new_serials = array();
        foreach ($serials as $serial) {
            $serial = trim($serial);
            if (!$serial) {
                continue;
            }
            if (!preg_match('/^[a-zA-Z0-9\-_\/]+$/', $serial)) {
                $errors[] = 'Format du n° de série incorrect: "' . $serial . '"';
                $new_serials[] = $serial;
                continue;
            }

            $where = 'serial = \'' . $serial . '\'';

            if (strpos($serial, 'S') !== 0) {
                $where .= ' OR serial = \'S' . $serial . '\'';
            }
            if (strpos($serial, 'S') === 0) {
                $where .= ' OR concat("S", serial) = \'' . $serial . '\'';
            }

            $id_eq = (int) $this->db->getValue('be_equipment', 'id', $where);

            if ($id_eq) {
                $where = 'equipments LIKE \'%[' . $id_eq . ']%\' AND type = \'' . $type . '\'';

                if ($this->isLoaded()) {
                    $where .= ' AND id != ' . $this->id;
                }

                $id = $this->db->getValue('bimp_revalorisation', 'id', $where);
                if ($id) {
                    $id_facture = (int) $this->db->getValue('bimp_revalorisation', 'id_facture', 'id = ' . $id);
                    $msg = 'Le n° de série "' . $serial . '" est déjà attribué à une autre revalorisation du même type';

                    if ($id_facture) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                        if (BimpObject::objectLoaded($facture)) {
                            $msg .= ' - Facture: ' . $facture->getLink();
                        }
                    }
                    $errors[] = $msg;
                    continue;
                }

                if (!in_array($id_eq, $equipments)) {
                    $nb_ok++;
                    $equipments[] = $id_eq;
                }
            } else {
                $new_serials[] = $serial;
            }
        }

        $status = (int) $this->getData('status');
        if ($this->getData('type') === 'applecare') {
            if ($status == 20 && count($equipments) == (int) $this->getData('qty')) {
                $status = 0;
            } elseif ($status == 0) {
                $status = 20;
            }
        }

        $this->set('status', $status);
        $this->set('equipments', $equipments);
        $this->set('serial', implode(' ', $new_serials));

        if ($update && $this->isLoaded()) {
            if ($this->db->update($this->getTable(), array(
                        'equipments' => $this->getDbValue('equipments', $equipments),
                        'serial'     => implode(' ', $new_serials),
                        'status'     => $status
                            ), 'id = ' . $this->id) <= 0) {
                $errors[] = 'Echec de la mise à jour - ' . $this->db->err();
            }
        }

        return $errors;
    }

    // Actions 

    public function actionSetStatus($data, &$success = '')
    {
        $errors = $warnings = array();
        $success = 'Maj status OK';

        if ($this->canSetAction('process')) {
            if ($data['status'] == 1 || $data['status'] == 2) {
                foreach ($data['id_objects'] as $nb => $idT) {
                    $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $idT);
                    if (($instance->getData('type') == 'crt' && $instance->getData('status') != 10) ||
                            ($instance->getData('type') != 'crt' && $instance->getData('status') != 0)) {
                        $errors[] = ($nb + 1) . ' éme ligne séléctionné, statut : ' . static::$status_list[$instance->getData('status')]['label'] . ' invalide pour passage au staut ' . static::$status_list[$data['status']]['label'];
                    }
                    if (!$instance->isActionAllowed('process'))
                        $errors[] = ($nb + 1) . ' éme ligne séléctionné opération impossible';
                }
                if (!count($errors)) {
                    foreach ($data['id_objects'] as $nb => $idT) {
                        $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $idT);
                        $instance->updateField('status', $data['status']);
                    }
                }
            } elseif ($data['status'] == 0) {
                if ($instance->getData('type') == 'applecare' && $instance->getData('status') != 20) {
                    $errors[] = ($nb + 1) . ' éme ligne séléctionné, statut : ' . static::$status_list[$instance->getData('status')]['label'] . ' invalide pour passage au staut ' . static::$status_list[$data['status']]['label'];
                }
            } else {
                $errors[] = 'Action non géré';
            }
        } else {
            $errors[] = 'Vous n\'avez pas la permission';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionProcess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $type = (isset($data['type']) ? $data['type'] : '');
        if (!in_array($type, array('accept', 'refuse', 'declarer', 'setSerial'))) {
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
                    $facture = $this->getChildObject('facture');
                    $facture->addNoteToCommercial('Une revalorisation a été refusée');
                    break;

                case 'setSerial':
                    $success = 'Saisie du serial OK';
                    $this->set('status', 0);
                    $this->set('serial', $data['serial']);
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

    public function actionCheckAppleCareSerials($data, &$success)
    {
        $warnings = array();
        $success = 'Equipements attribuées';
        $errors = self::checkAppleCareSerials();
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCheckBilledApplecareReval($data, &$success = '')
    {
        $warnings = array();

        $nbOk = 0;
        $warnings = self::checkBilledApplecareReval($data['id_fact'], $nbOk);

        if ($nbOk) {
            $success = $nbOk . ' revalorisation(s) validée(s) avec succès';
        } else {
            $warnings[] = 'Aucune validation effectuée';
        }

        return array(
            'errors'   => array(),
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validate()
    {
        $errors = array();

        $serials = $this->getData('serial');
        if ($serials) {
            $serial_errors = $this->checkSerials(false);
            if (count($serial_errors)) {
                $errors = BimpTools::merge_array($errors, $serial_errors);
            }
        }

        $serials = $this->getData('serial');

        if ($serials) {
            $serials = explode(' ', $serials);
        } else {
            $serials = array();
        }

        $eqs = $this->getData('equipments');
        $nb_eqs = count($eqs) + count($serials);
        if ($nb_eqs > (int) abs($this->getData('qty'))) {
            $errors[] = 'Veuillez retirer ' . ($nb_eqs - (int) abs($this->getData('qty'))) . ' équipements ou n° de série (' . $nb_eqs . ' / ' . abs($this->getData('qty')) . ')';
        }

        if (in_array($this->getData('type'), array('applecare', 'fac_ac'))) {
            if (count($eqs) == (int) $this->getData('qty')) {
                if ((int) $this->getData('status') == 20) {
                    $this->set('status', 0);
                }
            } else {
                if ((int) $this->getData('status') == 0) {
                    $this->set('status', 20);
                }
            }
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        parent::onSave($errors, $warnings);

        $facture = $this->getChildObject('facture');

        if (BimpObject::objectLoaded($facture)) {
            $facture->onChildSave($this);
        }
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $isGlobal = BimpTools::getValue('global', 0);
        if ($isGlobal) {
            $type = $this->getData('type');
            if ($type == 'crt') {
                $errors = array('Type CRT non valable pour les revalorisations globales');
            }if ($type == 'applecare') {
                $errors = array('Type commission AppleCare non valable pour les revalorisations globales');
            } else {
                $_POST['global'] = 0;
                $amount = $this->getData('amount');
                $fact = $this->getChildObject('facture');
                $totalFact = $fact->getData('total_ht');
                $lines = $fact->getLines();
                $i = 1;
                foreach ($lines as $line) {
                    $totalLine = $line->getTotalHTWithRemises();
                    $revalLineAmount = $amount / $totalFact * $totalLine;
                    if ($revalLineAmount != 0) {
                        $this->set('id_facture_line', $line->id);
                        $this->set('amount', $revalLineAmount);
                        $reval_warnings = array();
                        $reval_errors = $this->create($reval_warnings, $force_create);

                        if (count($reval_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($reval_warnings, 'Revalorisation n°' . $i);
                        }

                        if (count($reval_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($reval_errors, 'Revalorisation n°' . $i);
                        }

                        $i++;
                    }
                }
            }
        } else {
            $errors = parent::create($warnings, $force_create);
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = $this->id;
        $facture = $this->getChildObject('facture');

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors) && BimpObject::objectLoaded($facture)) {
            $facture->onChildDelete($this, $id);
        }
        return $errors;
    }

    // Méthodes statiques: 

    public static function checkAppleCareSerials(&$nb_ok = 0)
    {
        $errors = array();
        $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
                    'type'   => array('applecare', 'fac_ac'),
                    'serial' => array(
                        'operator' => '!=',
                        'value'    => ''
                    ),
                    'status' => array(
                        'not_in' => array(1, 2)
                    )
        ));

        if (is_array($revals)) {
            foreach ($revals as $reval) {
                $reval_errors = $reval->checkSerials(true, $nb_ok);

                if (count($reval_errors)) {
                    BimpCore::addlog('Reval Applecare : erreur serial', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', $reval, array(
                        'Erreurs' => $reval_errors
                    ));
                    $errors = BimpTools::merge_array($errors, $reval_errors);
                }
            }
        }
        return $errors;
    }

    public static function checkBilledApplecareReval($id_facture = null, &$nbOk = 0)
    {
        $errors = array();

        $filters = array(
            'a.type'       => 'fac_ac',
            'a.status'     => 0,
            'f.fk_statut'  => array(1, 2),
            'a.equipments' => array(
                'operator' => '!=',
                'value'    => ''
            )
        );

        if ($id_facture) {
            $filters['a.id_facture'] = $id_facture;
        }

        $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', $filters, 'id', 'asc', array(
                    'f' => array(
                        'alias' => 'f',
                        'table' => 'facture',
                        'on'    => 'f.rowid = a.id_facture'
                    )
        ));

        if (!empty($revals)) {
            $bdb = BimpCache::getBdb();
            $bdb->db->commitAll();

            foreach ($revals as $reval) {
                $equipments = $reval->getData('equipments');
                if (!empty($equipments)) {
                    $reval_errors = array();
                    $bdb->db->begin();

                    $nb_eqs_ok = 0;
                    foreach ($equipments as $id_eq) {
                        if ($id_eq) {
                            $fac_reval = BimpCache::findBimpObjectInstance('bimpfinanc', 'BimpRevalorisation', array(
                                        'type'       => 'applecare',
                                        'equipments' => array(
                                            'part_type' => 'middle',
                                            'part'      => '[' . $id_eq . ']'
                                        ),
                                            ), true);

                            if (BimpObject::objectLoaded($fac_reval)) {
                                $facture = $reval->getChildObject('facture');
                                if ((int) $fac_reval->getData('status') !== 1) {
                                    if ((int) $fac_reval->getData('qty') > 1) {
                                        // Création d'une nouvelle reval: 
                                        $data = $fac_reval->getDataArray();
                                        $data['qty'] = 1;
                                        $data['status'] = 1;
                                        $data['equipments'] = array($id_eq);
                                        $data['date_processed'] = date('Y-m-d');
                                        $data['note'] .= ($data['note'] ? "\n\n" : '') . 'Validée en auto' . (BimpObject::objectLoaded($facture) ? ' via facture ' . $facture->getRef() : '');

                                        BimpObject::createBimpObject('bimpfinanc', 'BimpRevalorisation', $data, true, $reval_errors);

                                        if (!count($reval_errors)) {
                                            $eqs = $fac_reval->getData('equipments');
                                            $eqs = BimpTools::unsetArrayValue($eqs, $id_eq);
                                            $fac_reval->set('qty', (int) $fac_reval->getData('qty') - 1);
                                            $fac_reval->set('equipments', $eqs);
                                            $reval_errors = $fac_reval->update($w, true);

                                            if (!count($reval_errors)) {
                                                $nbOk++;
                                                $nb_eqs_ok++;
                                            }
                                        }
                                    } else {
                                        $fac_reval->set('status', 1);
                                        $note = $fac_reval->getData('note');
                                        $note .= ($note ? "\n\n" : '') . 'Validée en auto' . (BimpObject::objectLoaded($facture) ? ' via facture ' . $facture->getRef() : '');
                                        $fac_reval->set('note', $note);
                                        $fac_reval->set('date_processed', date('Y-m-d'));
                                        $reval_errors = $fac_reval->update($w, true);

                                        if (!count($reval_errors)) {
                                            $nbOk++;
                                            $nb_eqs_ok++;
                                        }
                                    }
                                } else {
                                    $nb_eqs_ok++;
                                }
                            }
                        }
                    }

                    if ($nb_eqs_ok == (int) $reval->getData('qty')) {
                        $reval->set('status', 1);
                        $reval->set('date_processed', date('Y-m-d'));
                        $reval_errors = $reval->update($w, true);
                    }

                    if (count($reval_errors)) {
                        $bdb->db->rollback();
                        $errors[] = BimpTools::getMsgFromArray($reval_errors, 'Revalorisation #' . $reval->id);
                    } else {
                        $bdb->db->commit();
                    }
                }
            }
        }

        return $errors;
    }
}

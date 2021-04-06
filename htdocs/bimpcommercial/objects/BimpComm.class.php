<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BimpComm extends BimpDolObject
{

    const BC_ZONE_FR = 1;
    const BC_ZONE_UE = 2;
    const BC_ZONE_HORS_UE = 3;
    const BC_ZONE_UE_SANS_TVA = 4;

    public static $element_name = '';
    public static $external_contact_type_required = true;
    public static $internal_contact_type_required = true;
    public static $discount_lines_allowed = true;
    public static $use_zone_vente_for_tva = true;
    public static $cant_edit_zone_vente_secteurs = array('M');
    public static $remise_globale_allowed = true;
    public $acomptes_allowed = false;
    public $remise_globale_line_rate = null;
    public $lines_locked = 0;
    public $useCaisseForPayments = false;
    public static $pdf_periodicities = array(
        0  => 'Aucune',
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        12 => 'Annuelle'
    );
    public static $pdf_periodicity_label_masc = array(
        0  => '',
        1  => 'mois',
        3  => 'trimestre',
        12 => 'an'
    );
    public static $exportedStatut = [
        0   => ['label' => 'Non traitée en comptabilité', 'classes' => ['danger'], 'icon' => 'times'],
        1   => ['label' => 'Comptabilisée', 'classes' => ['success'], 'icon' => 'check'],
        102 => ['label' => 'Comptabilisation suspendue', 'classes' => ['important'], 'icon' => 'refresh'],
        204 => ['label' => 'Non comptabilisable', 'classes' => ['warning'], 'icon' => 'times'],
    ];
    public static $zones_vente = array(
        self::BC_ZONE_FR      => 'France',
        self::BC_ZONE_UE      => 'Union Européenne',
        //self::BC_ZONE_UE_SANS_TVA => 'Union Européenne sans TVA',
        self::BC_ZONE_HORS_UE => 'Hors UE'
    );
    protected $margins_infos = null;

    public function __construct($module, $object_name)
    {
        $this->useCaisseForPayments = BimpCore::getConf('use_caisse_for_payments');
        parent::__construct($module, $object_name);
    }

    // Gestion des droits: 

    protected function canView()
    {
        global $user;

        if (isset($user->rights->bimpcommercial->read) && (int) $user->rights->bimpcommercial->read) {
            return 1;
        }

        return 0;
    }

    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'logs':
                global $user;
                return (BimpObject::objectLoaded($user) && $user->admin ? 1 : 0);
        }

        return (int) parent::canEditField($field_name);
    }

    public function canEditCommercial()
    {
        if ($this->object_name === 'Bimp_Facture') {
            global $user;

            $secteur = $this->getData('ef_type');

            if ($secteur && in_array($secteur, array('M'))) {
                return 1;
            }

            if ($user->admin || $user->rights->bimpcommercial->edit_commercial) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    // Getters booléens: 

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ((int) $this->getData('fk_statut') === 0) {
            return 1;
        }
        return 0;
    }

    public function isFieldActivated($field_name)
    {
        if ($field_name == "marge" && !BimpCore::getConf("USE_MARGE_IN_PARENT_BIMPCOMM"))
            return 0;
        if (in_array($field_name, array('statut_export', 'douane_number')) && !BimpCore::getConf("USE_STATUT_EXPORT"))
            return 0;
        if (in_array($field_name, array('statut_relance', 'nb_relance')) && !BimpCore::getConf("USE_RELANCE"))
            return 0;

        return parent::isFieldActivated($field_name);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'replaced_ref':
                return 1;

            case 'fk_soc':
                if (!$force_edit) {
                    return (int) ((int) $this->getData('fk_statut') === 0);
                }
                break;

            case 'zone_vente':
                if (!$this->isLoaded()) {
                    return 0;
                }

                if (static::$use_zone_vente_for_tva) {
                    global $user;
                    if (!(int) $user->rights->bimpcommercial->priceVente && in_array($this->getData('ef_type'), static::$cant_edit_zone_vente_secteurs)) {
                        return 0;
                    }

                    if (!(int) $this->areLinesEditable()) {
                        return 0;
                    }

                    if (!$user->rights->bimpcommercial->edit_zone_vente)
                        return 0;
                }
                break;
        }

        if ($force_edit) {
            return 1;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isValidatable(&$errors = array())
    {
        global $conf;

        if (!$this->isLoaded($errors)) {
            return 0;
        }

        // Vérif des lignes: 
        $lines = $this->getLines('not_text');
        if (!count($lines) && !is_a($this, 'BS_SavPropal')) {
            $errors[] = 'Aucune ligne ajoutée  ' . $this->getLabel('to') . ' (Hors text)';
            return 0;
        }

        if ($this->useEntrepot() && !(int) $this->getData('entrepot')) {
            $errors[] = 'Aucun entrepôt associé';
        }

        if (!count($errors)) {
            if (in_array($this->object_name, array('Bimp_Propal', 'Bimp_Commande', 'Bimp_Facture'))) {
                global $user;
                if ($this->object_name === 'Bimp_Commande' && (int) $this->getData('id_client_facture')) {
                    $client = $this->getChildObject('client_facture');
                } else {
                    $client = $this->getChildObject('client');
                }

                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Client absent';
                } else {

                    // Module de validation activé
                    if ((int) $conf->global->MAIN_MODULE_BIMPVALIDATEORDER == 1) {
                        BimpObject::loadClass('bimpvalidateorder', 'ValidComm');

                        // Non prit en charge par le module de validation
                        if (ValidComm::getObjectClass($this) == -2)
                            $errors = BimpTools::merge_array($errors, $this->checkContacts());
                    } else
                        $errors = BimpTools::merge_array($errors, $this->checkContacts());

                    // Vérif conditions de réglement: 
                    // Attention pas de conditions de reglement sur les factures acomptes
                    if ($this->object_name !== 'Bimp_Facture') {
                        $cond_reglement = $this->getData('fk_cond_reglement');
                        if (in_array((int) $cond_reglement, array(0, 39)) || $cond_reglement == "VIDE") {
                            $errors[] = 'Conditions de réglement absentes';
                        }
                    }
                }
            }
        }

        return (count($errors) ? 0 : 1);
    }

    public function isUnvalidatable(&$errors = array())
    {
        return (count($errors) ? 0 : 1);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'addAcompte':
                if (!$this->acomptes_allowed) {
                    $errors[] = 'Acomptes non autorisés pour les ' . $this->getLabel('name_plur');
                    return 0;
                }
                if (!$this->isLoaded()) {
                    $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                    return 0;
                }
//                if ($this->object_name !== 'Bimp_Facture' && (int) $this->getData('fk_statut') > 0) {
//                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est plus au statut "brouillon"';
//                    return 0;
//                }
//                if ($this->field_exists('invoice_status') && (int) $this->getData('invoice_status') === 2) {
//                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est entièrement facturé' . $this->e();
//                    return 0;
//                }

                $client = $this->getChildObject('client');

                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Client absent';
                    return 0;
                }
                return 1;

            case 'useRemise':
                if ($this->object_name === 'Bimp_Facture') {
                    if ((int) $this->getData('fk_statut') === 0) {
                        return 1;
                    } elseif ((int) $this->getData('fk_statut') !== 1) {
                        $errors[] = 'Le statut actuel ' . $this->getLabel('of_this') . ' ne permet pas d\'appliquer un avoir';
                        return 0;
                    }

                    if (!in_array($this->getData('type'), array(Facture::TYPE_STANDARD, Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT, Facture::TYPE_REPLACEMENT))) {
                        $errors[] = 'il n\'est pas possible d\'appliquer un avoir sur ce type de facture';
                        return 0;
                    }

                    if ((int) $this->getData('paye')) {
                        $errors[] = ucfirst($this->getLabel('this')) . ' est marqué' . $this->e() . ' "payé' . $this->e() . '"';
                        return 0;
                    }

                    if ((int) $this->getData('type') === Facture::TYPE_CREDIT_NOTE) {
                        $remain_to_pay = (float) $this->getRemainToPay();
                        if ($remain_to_pay <= 0) {
                            $errors[] = 'Il n\'y a aucun montant à payer par le client pour cet avoir';
                            return 0;
                        }
                    }

                    return 1;
                }

                if ($this->object_name === 'Bimp_Commande') {
                    if (!in_array((int) $this->getData('status'), array(0, 1))) {
                        $errors[] = 'Statut actuel ' . $this->getLabel('of_the') . ' ne permet pas l\'ajout d\'avoir disponible';
                        return 0;
                    }
//                    if ($this->field_exists('invoice_status') && (int) $this->getData('invoice_status') === 2) {
//                        $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est entièrement facturé' . $this->e();
//                        return 0;
//                    }
                    return 1;
                }

                if (!static::$discount_lines_allowed) {
                    $errors[] = 'L\ajout d\'avoirs n\'est pas possible pour les ' . $this->getLabel('name_plur');
                    return 0;
                }

                if ((int) $this->getData('statut') !== 0) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est plus au statut brouillon';
                    return 0;
                }
                return 1;
        }

        return 1;
    }

    public function areLinesEditable()
    {
        if ($this->field_exists('fk_statut')) {
            if ((int) $this->getData('fk_statut') > 0) {
                return 0;
            }
        }

        return 1;
    }

    public function areLinesValid(&$errors = array())
    {
        $result = 1;
        foreach ($this->getLines() as $line) {
            $line_errors = array();

            if (!$line->isValid($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                $result = 0;
            }
        }

        return $result;
    }

    public function hasRemiseGlobale()
    {
        return (int) static::$remise_globale_allowed;
    }

    public function hasRemisesGlobales()
    {
        if ($this->hasRemiseGlobale()) {
            $rgs = $this->getRemisesGlobales();

            if (!empty($rgs)) {
                return 1;
            }
        }

        return 0;
    }

    public function isTvaActive()
    {
        if (static::$use_zone_vente_for_tva && $this->dol_field_exists('zone_vente')) {
            if ((int) $this->getData('zone_vente') === self::BC_ZONE_HORS_UE || (int) $this->getData('zone_vente') === self::BC_ZONE_UE) {
                return 0;
            }
        }

        return 1;
    }

    public function useEntrepot()
    {
        return (int) BimpCore::getConf("USE_ENTREPOT");
    }

    public function showForceCreateBySoc()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client) && is_a($client, 'Bimp_Societe')) {
            if (!$client->isSolvable($this->object_name)) {
                return 1;
            }
        }

        return 0;
    }

    // Getters array: 

    public function getClientContactsArray()
    {
        global $db;

        $id_client = $this->getAddContactIdClient();
        if ($id_client > 0) {
            $contacts = self::getSocieteContactsArray($id_client, false);
            $soc = new Societe($db);
            $soc->fetch_optionals($id_client);
            $contact_default = $soc->array_options['options_contact_default'];

            // Remove empty option
            unset($contacts['']);

            // If there is a default contact
            if (0 < (int) $contact_default) {
                $label_default = $contacts[$contact_default];
                unset($contacts[$contact_default]);
                $contacts = array($contact_default => $label_default . ' (Contact facturation email par défaut)') + $contacts;
            }
        }


        return $contacts;
    }

    public function getSocAvailableDiscountsArray()
    {
        if ((int) $this->getData('fk_soc')) {
            if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn'))) {
                $is_fourn = true;
                $soc = $this->getChildObject('fournisseur');
            } else {
                $is_fourn = false;
                $soc = $this->getChildObject('client');
            }

            if (BimpObject::objectLoaded($soc)) {
                return $soc->getAvailableDiscountsArray($is_fourn, $this->getSocAvalaibleDiscountsAllowed());
            }
        }

        return array();
    }

    // Getters paramètres: 

    public static function getInstanceByType($type, $id_object = null)
    {
        switch ($type) {
            case 'propal':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_object);

            case 'facture':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_object);

            case 'commande':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_object);

            case 'commande_fournisseur':
            case 'order_supplier':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_object);

            case 'facture_fourn':
            case 'facture_fournisseur':
            case 'invoice_supplier':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', $id_object);
        }

        return null;
    }

    public function getLineInstance()
    {
        return $this->getChildObject('lines');
    }

    public function getActionsButtons()
    {
        $buttons = array();

        // Edition historique: 
        if ($this->canEditField('logs')) {
            $buttons[] = array(
                'label'   => 'Editer logs',
                'icon'    => 'fas_history',
                'onclick' => $this->getJsLoadModalForm('logs', 'Editer les logs')
            );
        }

        // Ajout acompte: 
        if ($this->isActionAllowed('addAcompte') && $this->canSetAction('addAcompte')) {
            $id_mode_paiement = 0;
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $id_mode_paiement = $client->dol_object->mode_reglement_id;
            }
            $buttons[] = array(
                'label'   => 'Ajouter un acompte',
                'icon'    => 'fas_hand-holding-usd',
                'onclick' => $this->getJsActionOnclick('addAcompte', array(
                    'id_mode_paiement' => $id_mode_paiement
                        ), array(
                    'form_name' => 'acompte'
                ))
            );
        }

        $note = BimpObject::getInstance("bimpcore", "BimpNote");
        $buttons[] = array(
            'label'   => 'Message logistique',
            'icon'    => 'far_paper-plane',
            'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_GROUP, "fk_group_dest" => $note::BN_GROUPID_LOGISTIQUE, "content" => ""), array('form_name' => 'rep'))
        );

        $buttons[] = array(
            'label'   => 'Message facturation',
            'icon'    => 'far_paper-plane',
            'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_GROUP, "fk_group_dest" => $note::BN_GROUPID_FACT, "content" => "Bonjour, merci de bien vouloir facturer cette commande."), array('form_name' => 'rep'))
        );

        if ((int) $this->getData('fk_soc')) {
            $sql = 'SELECT datef FROM ' . MAIN_DB_PREFIX . 'facture WHERE fk_soc = ' . (int) $this->getData('fk_soc') . ' AND fk_statut IN (1,2,3)';
            $sql .= ' ORDER BY datef ASC LIMIT 1';

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['datef'])) {
                $debut = $result[0]['datef'];
            } else {
                $debut = date('Y-m-d');
            }

            $buttons[] = array(
                'label'   => 'Relevé facturation client',
                'icon'    => 'fas_clipboard-list',
                'onclick' => $this->getJsActionOnclick('releverFacturation', array(
                    'date_debut' => $debut,
                    'date_fin'   => date('Y-m-d')
                        ), array(
                    'form_name' => 'releverFacturation'
                ))
            );
        }

        return $buttons;
    }

    public function getDefaultListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Vue rapide du contenu',
                'icon'    => 'fas_list',
                'onclick' => $this->getJsLoadModalView('content', 'Contenu ' . $this->getLabel('of_the') . ' ' . $this->getRef())
            );
        }

        return $buttons;
    }

    public function getLinesListHeaderExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ((int) $this->getData('fk_statut') === 0) {
                $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');

                $buttons[] = array(
                    'label'       => 'Créer un produit',
                    'icon_before' => 'fas_box',
                    'classes'     => array('btn', 'btn-default'),
                    'attr'        => array(
                        'onclick' => $product->getJsLoadModalForm('lightFourn', 'Nouveau produit')
                    )
                );
            }

            if ($this->isActionAllowed('useRemise') && $this->canSetAction('useRemise')) {
                if ($this->object_name === 'Bimp_Commande' || (int) $this->getData('fk_statut') === 0) {
                    $buttons[] = array(
                        'label'       => 'Ajouter un avoir disponible',
                        'icon_before' => 'fas_file-import',
                        'classes'     => array('btn', 'btn-default'),
                        'attr'        => array(
                            'onclick' => $this->getJsActionOnclick('useRemise', array(), array(
                                'form_name' => 'use_remise'
                            ))
                        )
                    );
                }
            }
        }

        return $buttons;
    }

    public function getListFilters()
    {
        global $user;
        $return = array();
        if (BimpTools::getValue("my") == 1) {
            $return[] = array(
                'name'   => 'fk_user_author',
                'filter' => 2
            );
        }

        return $return;
    }

    public function getDefaultRemiseGlobaleLabel()
    {
        return 'Remise exceptionnelle sur l\'intégralité ' . $this->getLabel('of_the');
    }

    public function getCardFields($card_name)
    {
        $fields = parent::getCardFields($card_name);

        switch ($card_name) {
            case 'default':
                $fields[] = 'model_pdf';
                break;
        }

        return $fields;
    }

    // Getters filtres: 

    public function getCommercialSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((int) $value) {
            $filters['typecont.element'] = static::$dol_module;
            $filters['typecont.source'] = 'internal';
            $filters['typecont.code'] = 'SALESREPFOLL';
            $filters['elemcont.fk_socpeople'] = (int) $value;

            $joins['elemcont'] = array(
                'table' => 'element_contact',
                'on'    => 'elemcont.element_id = ' . $main_alias . '.rowid',
                'alias' => 'elemcont'
            );
            $joins['typecont'] = array(
                'table' => 'c_type_contact',
                'on'    => 'elemcont.fk_c_type_contact = typecont.rowid',
                'alias' => 'typecont'
            );
        }
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

            case 'id_commercial':
                if ((int) $value) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $value);
                    if (BimpObject::ObjectLoaded($user)) {
                        return $user->dol_object->getFullName();
                    }
                } else {
                    return 'Aucun';
                }
                break;
        }

        return parent::getCustomFilterValueLabel($field_name, $value);
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'id_product':
                if (!empty($values)) {
                    $line = $this->getLineInstance();
                    $alias = $line::$parent_comm_type . '_det';
                    if (!$excluded) {
                        $joins[$alias] = array(
                            'alias' => $alias,
                            'table' => $line::$dol_line_table,
                            'on'    => $alias . '.' . $line::$dol_line_parent_field . ' = a.' . $this->getPrimary()
                        );
                        $key = 'in';
                        if ($excluded) {
                            $key = 'not_in';
                        }
                        $filters[$alias . '.fk_product'] = array(
                            $key => $values
                        );
                    } else {
                        $alias .= '_not';
                        $filters['a.' . $this->getPrimary()] = array(
                            'not_in' => '(SELECT ' . $alias . '.' . $line::$dol_line_parent_field . ' FROM ' . MAIN_DB_PREFIX . $line::$dol_line_table . ' ' . $alias . ' WHERE ' . $alias . '.fk_product' . ' IN (' . implode(',', $values) . '))'
                        );
                    }
                }
                break;

            case 'id_commercial':
                $ids = array();
                $empty = false;

                foreach ($values as $idx => $value) {
                    if ($value === 'current') {
                        global $user;
                        if (BimpObject::objectLoaded($user)) {
                            $ids[] = (int) $user->id;
                        }
                    } elseif ((int) $value) {
                        $ids[] = (int) $value;
                    } else {
                        $empty = true;
                    }
                }
                $joins['elemcont'] = array(
                    'table' => 'element_contact',
                    'on'    => 'elemcont.element_id = a.rowid',
                    'alias' => 'elemcont'
                );
                $joins['typecont'] = array(
                    'table' => 'c_type_contact',
                    'on'    => 'elemcont.fk_c_type_contact = typecont.rowid',
                    'alias' => 'typecont'
                );

                $sql = '';

                if (!empty($ids)) {
                    $sql .= '(';
                    $sql .= 'typecont.element = \'' . static::$dol_module . '\' AND typecont.source = \'internal\'';
                    $sql .= ' AND typecont.code = \'SALESREPFOLL\' AND elemcont.fk_socpeople ' . ($excluded ? 'NOT ' : '') . 'IN (' . implode(',', $ids) . ')';
                    $sql .= ')';

                    if (!$empty && $excluded) {
                        $sql .= ' OR (SELECT COUNT(ec2.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc2 ON tc2.rowid = ec2.fk_c_type_contact';
                        $sql .= ' WHERE tc2.element = \'' . static::$dol_module . '\'';
                        $sql .= ' AND tc2.source = \'internal\'';
                        $sql .= ' AND tc2.code = \'SALESREPFOLL\'';
                        $sql .= ' AND ec2.element_id = a.rowid) = 0';
                    }
                }

                if ($empty) {
                    $sql .= ($sql ? ($excluded ? ' AND ' : ' OR ') : '');
                    $sql .= '(SELECT COUNT(ec2.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc2 ON tc2.rowid = ec2.fk_c_type_contact';
                    $sql .= ' WHERE tc2.element = \'' . static::$dol_module . '\'';
                    $sql .= ' AND tc2.source = \'internal\'';
                    $sql .= ' AND tc2.code = \'SALESREPFOLL\'';
                    $sql .= ' AND ec2.element_id = a.rowid) ' . ($excluded ? '>' : '=') . ' 0';
                }

                if ($sql) {
                    $filters['commercial_custom'] = array(
                        'custom' => '(' . $sql . ')'
                    );
                }
                break;
            case 'categorie':
            case 'collection':
            case 'nature':
            case 'famille':
            case 'gamme':
                $alias = 'product_ef';
                $line = $this->getLineInstance();
                $line_alias = $line::$parent_comm_type . '_det';
                if (!$excluded) {
                    $joins[$line_alias] = array(
                        'alias' => $line_alias,
                        'table' => $line::$dol_line_table,
                        'on'    => $line_alias . '.' . $line::$dol_line_parent_field . ' = a.' . $this->getPrimary()
                    );
                    $joins[$alias] = array(
                        'alias' => $alias,
                        'table' => 'product_extrafields',
                        'on'    => $alias . '.fk_object = ' . $line_alias . '.fk_product'
                    );

                    $filters[$alias . '.' . $field_name] = array(
                        ($excluded ? 'not_' : '') . 'in' => $values
                    );
                } else {
                    $alias .= '_not';
                    $filters['a.' . $this->getPrimary()] = array(
                        'not_in' => '(SELECT ' . $line_alias . '.' . $line::$dol_line_parent_field . ' FROM ' . MAIN_DB_PREFIX . $line::$dol_line_table . ' ' . $line_alias . ', ' . MAIN_DB_PREFIX . 'product_extrafields ' . $alias . ' WHERE ' . $alias . '.fk_object = ' . $line_alias . '.fk_product AND ' . $alias . '.' . $field_name . ' IN (' . implode(',', $values) . '))'
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    // Getters données: 

    public function getLines($types = null)
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpcommercial', 'ObjectLine');

            $filters = array();
            if (!is_null($types)) {
                if (is_string($types)) {
                    $type_code = $types;
                    $types = array();
                    switch ($type_code) {
                        case 'product':
                            $types[] = ObjectLine::LINE_PRODUCT;
                            break;

                        case 'free':
                            $types[] = ObjectLine::LINE_FREE;
                            break;

                        case 'text':
                            $types[] = ObjectLine::LINE_TEXT;
                            break;

                        case 'not_text':
                            $types[] = ObjectLine::LINE_PRODUCT;
                            $types[] = ObjectLine::LINE_FREE;
                            break;
                    }
                }

                if (is_array($types) && !empty($types)) {
                    $filters = array(
                        'type' => array(
                            'in' => $types
                        )
                    );
                }
            }

            return $this->getChildrenObjects('lines', $filters, 'position', 'asc');
        }

        return array();
    }

    public function getTotalHt()
    {
        if ($this->isDolObject()) {
            if (property_exists($this->dol_object, 'total_ht')) {
                return (float) $this->dol_object->total_ht;
            }
        }

        return 0;
    }

    public function getTotalTtc()
    {
        if ($this->isDolObject()) {
            if (property_exists($this->dol_object, 'total_ttc')) {
                return (float) $this->dol_object->total_ttc;
            }
        }

        return 0;
    }

    public function getTotalTtcWithoutRemises($exclude_discounts = false)
    {
        $total = 0;

        if ($this->isLoaded()) {
            $lines = $this->getLines();
            foreach ($lines as $line) {
                if ($exclude_discounts && (int) $line->id_remise_except) {
                    continue;
                }

                $total += $line->getTotalTtcWithoutRemises();
            }
        }
        return $total;
    }

    public function getTotalTtcWithoutDiscountsAbsolutes()
    {
        $total = 0;

        if ($this->isLoaded()) {
            $lines = $this->getLines();
            foreach ($lines as $line) {
                if (!(int) $line->id_remise_except) {
                    $total += $line->getTotalTTC();
                }
            }
        }
        return $total;
    }

    public function getAddContactIdClient()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return $id_client;
    }

    public function getDefaultSecteur()
    {
        global $user;
        if (isset($user->array_options['options_secteur']) && $user->array_options['options_secteur'] != "")
            return $user->array_options['options_secteur'];
        if (userInGroupe(43, $user->id))
            return "M";
        return "";
    }

    public function getRemisesInfos()
    {
        $infos = array(
            'remises_lines_percent'           => 0,
            'remises_lines_amount_ht'         => 0,
            'remises_lines_amount_ttc'        => 0,
            'remises_globales_percent'        => 0,
            'remises_globales_amount_ht'      => 0,
            'remises_globales_amount_ttc'     => 0,
            'ext_remises_globales_percent'    => 0,
            'ext_remises_globales_amount_ht'  => 0,
            'ext_remises_globales_amount_ttc' => 0,
            'remise_total_percent'            => 0,
            'remise_total_amount_ht'          => 0,
            'remise_total_amount_ttc'         => 0
        );

        if ($this->isLoaded()) {
            $total_ttc_without_remises = 0;

            $lines = $this->getChildrenObjects('lines');

            foreach ($lines as $line) {
                $line_infos = $line->getRemiseTotalInfos();
                $infos['remises_lines_amount_ttc'] += (float) $line_infos['line_amount_ttc'];
                $infos['remises_lines_amount_ht'] += (float) $line_infos['line_amount_ht'];
                $infos['remises_globales_amount_ht'] += (float) $line_infos['global_amount_ht'];
                $infos['remises_globales_amount_ttc'] += (float) $line_infos['global_amount_ttc'];
                $infos['ext_remises_globales_amount_ht'] += (float) $line_infos['ext_global_amount_ht'];
                $infos['ext_remises_globales_amount_ttc'] += (float) $line_infos['ext_global_amount_ttc'];
                $total_ttc_without_remises += $line_infos['total_ttc_without_remises'];
            }

            if ($total_ttc_without_remises && $infos['remises_lines_amount_ttc']) {
                $infos['remises_lines_percent'] = ($infos['remises_lines_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            if ($total_ttc_without_remises && $infos['remises_globales_amount_ttc']) {
                $infos['remises_globales_percent'] = ($infos['remises_globales_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            if ($total_ttc_without_remises && $infos['ext_remises_globales_amount_ttc']) {
                $infos['ext_remises_globales_percent'] = ($infos['ext_remises_globales_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            $infos['remise_total_percent'] = $infos['remises_lines_percent'] + $infos['remises_globales_percent'] + $infos['ext_remises_globales_percent'];
            $infos['remise_total_amount_ht'] = $infos['remises_lines_amount_ht'] + $infos['remises_globales_amount_ht'] + $infos['ext_remises_globales_amount_ht'];
            $infos['remise_total_amount_ttc'] = $infos['remises_lines_amount_ttc'] + $infos['remises_globales_amount_ttc'] + $infos['ext_remises_globales_amount_ttc'];
        }

        return $infos;
    }

    public function getCreateFromOriginCheckMsg()
    {
        if (!$this->isLoaded()) {
            $origin = BimpTools::getPostFieldValue('origin');
            $origin_id = BimpTools::getPostFieldValue('origin_id');

            if ($origin && $origin_id) {
                $where = '`fk_source` = ' . (int) $origin_id . ' AND `sourcetype` = \'' . $origin . '\'';
                $where .= ' AND `targettype` = \'' . $this->dol_object->element . '\'';

                $result = $this->db->getValue('element_element', 'rowid', $where);

                if (!is_null($result) && (int) $result) {
                    $content = 'Attention: ' . $this->getLabel('a') . ' a déjà été créé' . ($this->isLabelFemale() ? 'e' : '') . ' à partir de ';
                    switch ($origin) {
                        case 'propal':
                            $content .= 'cette proposition commerciale';
                            break;

                        default:
                            $content .= 'l\'objet "' . $origin . '"';
                            break;
                    }
                    return array(array(
                            'content' => $content,
                            'type'    => 'warning'
                    ));
                }
            }
        }

        return null;
    }

    public function getMarginInfosArray($force_price = false)
    {
        if (is_null($this->margins_infos)) {
            global $conf, $db;

            $marginInfos = array(
                'pa_products'          => 0,
                'pv_products'          => 0,
                'margin_on_products'   => 0,
                'margin_rate_products' => '',
                'mark_rate_products'   => '',
                'pa_services'          => 0,
                'pv_services'          => 0,
                'margin_on_services'   => 0,
                'margin_rate_services' => '',
                'mark_rate_services'   => '',
                'pa_total'             => 0,
                'pv_total'             => 0,
                'total_margin'         => 0,
                'total_margin_rate'    => '',
                'total_mark_rate'      => ''
            );

            if (!$this->isLoaded()) {
                return $marginInfos;
            }

            $lines = $this->getChildrenObjects('lines');
            foreach ($lines as $bimp_line) {
                $line = $bimp_line->getChildObject('line');

//        foreach ($object->lines as $line) {
                if (empty($line->pa_ht) && isset($line->fk_fournprice) && !$force_price) {
                    require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
                    $product = new ProductFournisseur($db);
                    if ($product->fetch_product_fournisseur_price($line->fk_fournprice))
                        $line->pa_ht = $product->fourn_unitprice * (1 - $product->fourn_remise_percent / 100);
                }
//            if ($bimp_line->getData("remise_pa") > 0) {
//                $line->pa_ht = $line->pa_ht * (100 - $bimp_line->getData("remise_pa")) / 100;
//            }
                // si prix d'achat non renseigné et devrait l'être, alors prix achat = prix vente
                if ((!isset($line->pa_ht) || $line->pa_ht == 0) && $line->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1)) {
                    $line->pa_ht = $line->subprice * (1 - ($line->remise_percent / 100));
                }

                $pv = $line->qty * $line->subprice * (1 - $line->remise_percent / 100);
                $pa_ht = $line->pa_ht;

                $pa = $line->qty * $pa_ht;

                // calcul des marges
                if (isset($line->fk_remise_except) && isset($conf->global->MARGIN_METHODE_FOR_DISCOUNT)) {    // remise
                    if ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '1') { // remise globale considérée comme produit
                        $marginInfos['pa_products'] += $pa;
                        $marginInfos['pv_products'] += $pv;
                        $marginInfos['pa_total'] += $pa;
                        $marginInfos['pv_total'] += $pv;
                        $marginInfos['margin_on_products'] += $pv - $pa;
                    } elseif ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '2') { // remise globale considérée comme service
                        $marginInfos['pa_services'] += $pa;
                        $marginInfos['pv_services'] += $pv;
                        $marginInfos['pa_total'] += $pa;
                        $marginInfos['pv_total'] += $pv;
                        $marginInfos['margin_on_services'] += $pv - $pa;
                    } elseif ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '3') { // remise globale prise en compte uniqt sur total
                        $marginInfos['pa_total'] += $pa;
                        $marginInfos['pv_total'] += $pv;
                    }
                } else {
                    $type = $line->product_type ? $line->product_type : $line->fk_product_type;
                    if ($type == 0) {  // product
                        $marginInfos['pa_products'] += $pa;
                        $marginInfos['pv_products'] += $pv;
                        $marginInfos['pa_total'] += $pa;
                        $marginInfos['pv_total'] += $pv;
                        $marginInfos['margin_on_products'] += $pv - $pa;
                    } elseif ($type == 1) {  // service
                        $marginInfos['pa_services'] += $pa;
                        $marginInfos['pv_services'] += $pv;
                        $marginInfos['pa_total'] += $pa;
                        $marginInfos['pv_total'] += $pv;
                        $marginInfos['margin_on_services'] += $pv - $pa;
                    }
                }
            }
            if ($marginInfos['pa_products'] > 0)
                $marginInfos['margin_rate_products'] = 100 * $marginInfos['margin_on_products'] / $marginInfos['pa_products'];
            if ($marginInfos['pv_products'] > 0)
                $marginInfos['mark_rate_products'] = 100 * $marginInfos['margin_on_products'] / $marginInfos['pv_products'];

            if ($marginInfos['pa_services'] > 0)
                $marginInfos['margin_rate_services'] = 100 * $marginInfos['margin_on_services'] / $marginInfos['pa_services'];
            if ($marginInfos['pv_services'] > 0)
                $marginInfos['mark_rate_services'] = 100 * $marginInfos['margin_on_services'] / $marginInfos['pv_services'];

            $marginInfos['total_margin'] = $marginInfos['pv_total'] - $marginInfos['pa_total'];
            if ($marginInfos['pa_total'] > 0)
                $marginInfos['total_margin_rate'] = 100 * $marginInfos['total_margin'] / $marginInfos['pa_total'];
            if ($marginInfos['pv_total'] > 0)
                $marginInfos['total_mark_rate'] = 100 * $marginInfos['total_margin'] / $marginInfos['pv_total'];

            $this->margins_infos = $marginInfos;
        }

        return $this->margins_infos;
    }

    public function getCondReglementBySociete()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0)) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc', 0);
            if (!$id_soc) {
                $id_soc = (int) BimpTools::getPostFieldValue('id_client', 0);
            }

            if (!$id_soc && $this->getData('fk_soc') > 0) {
                $id_soc = $this->getData('fk_soc');
            }
            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn'))) {
                        return (int) $soc->getData('cond_reglement_supplier');
                    } else {
                        return (int) $soc->getData('cond_reglement');
                    }
                }
            }
        }

        if (isset($this->data['fk_cond_reglement']) && (int) $this->data['fk_cond_reglement']) {
            return (int) $this->data['fk_cond_reglement']; // pas getData() sinon boucle infinie (getCondReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_cond_reglement', 0);
    }

    public function getModeReglementBySociete()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0)) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc', 0);
            if (!$id_soc && $this->getData('fk_soc') > 0) {
                $id_soc = $this->getData('fk_soc');
            }
            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn'))) {
                        return (int) $soc->getData('mode_reglement_supplier');
                    } else {
                        return (int) $soc->getData('mode_reglement');
                    }
                }
            }
        }

        if (isset($this->data['fk_mode_reglement']) && (int) $this->data['fk_mode_reglement']) {
            return (int) $this->data['fk_mode_reglement']; // pas getData() sinon boucle infinie (getModeReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return BimpCore::getConf('societe_id_default_mode_reglement', 0);
    }

    public static function getZoneByCountry(Bimp_Societe $client)
    {
        $zone = self::BC_ZONE_FR;
        $id_country = $client->getData('fk_pays');

        if (!(int) $id_country) {
            $id_country = BimpCore::getConf('default_id_country');
        }

        if ((int) $id_country) {
            $country = self::getBdb()->getRow('c_country', '`rowid` = ' . (int) $id_country);
            if (!is_null($country)) {
                if ($country->code === 'FR') {
                    $zone = self::BC_ZONE_FR;
                } elseif ((int) $country->in_ue) {
                    $zone = self::BC_ZONE_UE;
                } else {
                    $zone = self::BC_ZONE_HORS_UE;
                }
            }
        }

        return $zone;
    }

    public function getCommercialId()
    {
        if ($this->isLoaded()) {
            $contacts = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
            if (isset($contacts[0]) && $contacts[0]) {
                return (int) $contacts[0];
            }
        }

        return 0;
    }

    public function getCommercial()
    {
        $id_comm = (int) $this->getCommercialId();
        if ($id_comm) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_comm);
            if (BimpObject::objectLoaded($user)) {
                return $user;
            }
        }

        return null;
    }

    public function getSocAvailableDiscountsAmounts()
    {
        if ((int) $this->getData('fk_soc')) {
            if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn'))) {
                $is_fourn = true;
                $soc = $this->getChildObject('fournisseur');
            } else {
                $is_fourn = false;
                $soc = $this->getChildObject('client');
            }

            if (BimpObject::objectLoaded($soc)) {
                return $soc->getAvailableDiscountsAmounts(false, $this->getSocAvalaibleDiscountsAllowed());
            }
        }

        return 0;
    }

    public function getSocAvalaibleDiscountsAllowed()
    {
        $allowed = array();
        if ($this->isLoaded()) {
            if (is_a($this, 'Bimp_Facture')) {
                $commandes = $this->getCommandesOriginList();

                if (!empty($commandes)) {
                    $allowed['commandes'] = $commandes;
                    $allowed['propales'] = array();

                    foreach ($commandes as $id_commande) {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);
                        if (BimpObject::objectLoaded($commande)) {
                            $propales = $commande->getPropalesOriginList();
                            foreach ($propales as $id_propal) {
                                if (!in_array((int) $id_propal, $allowed['propales'])) {
                                    $allowed['propales'][] = (int) $id_propal;
                                }
                            }
                        }
                    }
                }
            } elseif (is_a($this, 'Bimp_Commande')) {
                $allowed['propales'] = $this->getPropalesOriginList();
            }
        }

        return $allowed;
    }

    public function getTxMarge()
    {
        if ($this->isLoaded()) {
            $margins = $this->getMarginInfosArray();

            if (isset($margins['total_margin_rate'])) {
                return (float) $margins['total_margin_rate'];
            }
        }

        return 0;
    }

    public function getTxMarque()
    {
        if ($this->isLoaded()) {
            $margins = $this->getMarginInfosArray();

            if (isset($margins['total_mark_rate'])) {
                return (float) $margins['total_mark_rate'];
            }
        }

        return 0;
    }

    public function getTotal_paListTotal($filters = array(), $joins = array())
    {
        $return = array(
            'data_type' => 'money',
            'value'     => 0
        );

        $line = $this->getLineInstance();

        if (is_a($line, 'ObjectLine')) {
            $joins['det'] = array(
                'table' => $line::$dol_line_table,
                'alias' => 'det',
                'on'    => 'a.rowid = det.' . $line::$dol_line_parent_field
            );

            $sql = 'SELECT SUM(det.qty * det.buy_price_ht) as total';
            $sql .= BimpTools::getSqlFrom($this->getTable(), $joins, 'a');
            $sql .= BimpTools::getSqlWhere($filters);

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['total'])) {
                $return['value'] = (float) $result[0]['total'];
            }
        }

        return $return;
    }

    public function getIdContact($type = 'external', $code = 'SHIPPING')
    {
        if ($this->isLoaded()) {
            $contacts = $this->dol_object->getIdContact($type, $code);
            if (isset($contacts[0]) && $contacts[0]) {
                return (int) $contacts[0];
            }
        }

        return 0;
    }

    public function getRemisesGlobales()
    {
        if ($this->isLoaded() && static::$remise_globale_allowed) {
            $cache_key = $this->object_name . '_' . $this->id . '_remises_globales';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = BimpCache::getBimpObjectObjects('bimpcommercial', 'RemiseGlobale', array(
                            'obj_type' => static::$element_name,
                            'id_obj'   => (int) $this->id
                ));
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getTotalRemisesGlobalesAmount()
    {
        $total_rg = 0;
        if ($this->isLoaded()) {
            $rgs = $this->getRemisesGlobales();

            if (!empty($rgs)) {
                $total_ttc = (float) $this->getTotalTtcWithoutRemises(true);

                foreach ($rgs as $rg) {
                    $remise_amount = 0;
                    switch ($rg->getData('type')) {
                        case 'percent':
                            $remise_rate = (float) $rg->getData('percent');
                            $remise_amount = $total_ttc * ($remise_rate / 100);
                            break;

                        case 'amount':
                            $remise_amount = (float) $rg->getData('amount');
                            break;
                    }

                    if ($remise_amount) {
                        $total_rg += $remise_amount;
                    }
                }
            }
        }

        return $total_rg;
    }

    // Getters - Overrides BimpObject

    public function getName($with_generic = true)
    {
        if ($this->isLoaded()) {
            $name = (string) $this->getData('libelle');
            if ($name) {
                return $this->getData('ref') . ' : ' . $name;
            }

            if ($with_generic) {
//                return BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
                return $this->getData('ref');
            }
        }

        return '';
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                if (isset(static::$files_module_part)) {
                    $module_part = static::$files_module_part;
                } else {
                    $module_part = static::$dol_module;
                }
                return DOL_URL_ROOT . '/' . $page . '.php?modulepart=' . $module_part . '&file=' . urlencode($this->getRef()) . '/' . urlencode($file_name);
            }
        }

        return '';
    }

    // Setters:

    public function setRef($ref)
    {
        if ($this->field_exists('ref')) {
            $this->set('ref', $ref);
            $dol_prop = $this->getConf('fields/ref/dol_prop', 'ref');
            if (property_exists($this->dol_object, $dol_prop)) {
                $this->dol_object->{$dol_prop} = $ref;
            }
        }
    }

    // Affichages: 

    public function displayRemisesClient()
    {
        $html = '';

        if ($this->isLoaded()) {
            $soc = $this->getChildObject('client');
            if (BimpObject::objectLoaded($soc)) {

                $discounts = (float) $this->getSocAvailableDiscountsAmounts();

                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';
                $html .= '<tr>';
                $html .= '<td style="width: 140px">Remise par défaut: </td>';
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayFloatValue((float) $soc->dol_object->remise_percent) . '%</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td style="width: 140px">Avoirs disponibles: </td>';
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayMoneyValue((float) $discounts, 'EUR', 0, 0, 0, 2, 1) . '</td>';
                $html .= '</tr>';
                $html .= '</tbody>';
                $html .= '</table>';

                if ($discounts) {
                    $html .= '<div class="buttonsContainer align-right">';
                    if (static::$discount_lines_allowed && (int) $this->getData('fk_statut') === 0) {
                        $onclick = $this->getJsActionOnclick('useRemise', array(), array(
                            'form_name' => 'use_remise'
                        ));
                        $label = '';

                        if ($this->object_name !== 'Bimp_Facture' || (int) $this->getData('fk_statut') === 0) {
                            $label = 'Ajouter un avoir disponible';
                        } elseif ($this->object_name === 'Bimp_Facture' && in_array((int) $this->getData('fk_statut'), array(1, 2))) {
                            $label = 'Appliquer un avoir ou un trop perçu disponible';
                        }

                        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= '<i class="' . BimpRender::renderIconClass('fas_file-import') . ' iconLeft"></i>' . $label;
                        $html .= '</button>';
                    }

                    $url = DOL_URL_ROOT . '/comm/remx.php?id=' . $soc->id;
                    $html .= '<a class="btn btn-default" href="' . $url . '" target="_blank">';
                    $html .= '<i class="' . BimpRender::renderIconClass('percent') . ' iconLeft"></i>Remises client';
                    $html .= '</a>';

                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    public function displayTotalRemises()
    {
        $html = '';

        if ($this->isLoaded()) {
            $infos = $this->getRemisesInfos();

            if ($infos['remise_total_amount_ttc']) {
                $html .= '<table class="bimp_list_table">';

                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th></th>';
                $html .= '<th>HT</th>';
                $html .= '<th>TTC</th>';
                $html .= '<th>%</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                if ($infos['remises_lines_amount_ttc']) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Remises lignes: </td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_lines_amount_ht'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_lines_amount_ttc'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['remises_lines_percent'], 4) . ' %</td>';
                    $html .= '</tr>';
                }

                if ($infos['remises_globales_amount_ttc']) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Remises globales: </td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_globales_amount_ht'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_globales_amount_ttc'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['remises_globales_percent'], 4) . ' %</td>';
                    $html .= '</tr>';
                }

                if ($infos['ext_remises_globales_amount_ttc']) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Parts de remises globales externes: </td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['ext_remises_globales_amount_ht'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['ext_remises_globales_amount_ttc'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['ext_remises_globales_percent'], 4) . ' %</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';

                $html .= '<tfoot>';
                $html .= '<td style="font-weight: bold;width: 160px;">Total Remises: </td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_total_amount_ht'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_total_amount_ttc'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                $html .= '<td>' . BimpTools::displayFloatValue($infos['remise_total_percent'], 4) . ' %</td>';
                $html .= '</tfoot>';
                $html .= '</table>';
            } else {
                $html .= '<p>Aucune remise</p>';
            }
        }

        return $html;
    }

    public function displayTotalPA()
    {
        $lines = $this->getLines('not_text');

        $total = 0;

        foreach ($lines as $line) {
            $total += $line->getTotalPA();
        }

        return BimpTools::displayMoneyValue($total, '', 0, 0, 0, 2, 1);
    }

    public function getIdCommercial()
    {
        if ($this->isLoaded()) {
            $contacts = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
            if (isset($contacts[0]) && $contacts[0]) {
                return $contacts[0];
            }
        }
        return 0;
    }

    public function displayCommercial()
    {
        $id = $this->getIdCommercial();
        if ($id > 0) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id);
            if (BimpObject::objectLoaded($user)) {
                global $modeCSV;
                if ($modeCSV) {
                    return $user->getName();
                } else {
                    return $user->getLink();
                }
            }
        }
        return '';
    }

    public function displayClientFact()
    {
        $html = '';
        if ($this->isLoaded()) {
            $list_ext = $this->dol_object->liste_contact(-1, 'external', 0, 'BILLING');
            if (count($list_ext) > 0) {
                foreach ($list_ext as $contact) {
                    if ($contact['socid'] != $this->getData('fk_soc')) {
                        $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $contact['socid']);
                        if (BimpObject::objectLoaded($soc)) {
                            $html .= $soc->getLink();
                        } else {
                            $html .= 'Client #' . $contact['socid'];
                        }
                    }
                }
            }
        }
        return $html;
    }

    public function displayTxMarge()
    {
        return BimpTools::displayFloatValue($this->getTxMarge(), 4, ',') . '%';
    }

    public function displayTxMarque()
    {
        return BimpTools::displayFloatValue($this->getTxMarque(), 4, ',') . '%';
    }

    public function displayCountNotes($hideIfNotNotes = false)
    {
        $notes = $this->getNotes();
        $nb = count($notes);
        if ($nb > 0 || $hideIfNotNotes == false)
            return '<br/><span class="warning"><span class="badge badge-warning">' . $nb . '</span> Note' . ($nb > 1 ? 's' : '') . '</span>';
        return '';
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->field_exists('replaced_ref') && $this->getData('replaced_ref')) {
            $html .= '<div style="margin-bottom: 8px">';
            $html .= '<span class="warning" style="font-size: 15px">Annule et remplace ' . $this->getLabel('the') . ' "' . $this->getData('replaced_ref') . '" (données perdues)</span>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        return $this->displayCountNotes(true);
    }

    public function renderMarginsTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (!class_exists('FormMargin')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
            }

            $marginInfo = $this->getMarginInfosArray();

            if (!empty($marginInfo)) {
                global $conf;
                $conf_tx_marque = (int) BimpCore::getConf('bimpcomm_tx_marque');

                $html .= '<table class="bimp_list_table">';

                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Marges</th>';
                $html .= '<th>Prix de vente</th>';
                $html .= '<th>Prix de revient</th>';
                $html .= '<th>Marge';
                if ($conf_tx_marque) {
                    $html .= ' (Tx marque)';
                } else {
                    $html .= ' (Tx marge)';
                }
                $html .= '</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';
                if (!empty($conf->product->enabled)) {
                    $html .= '<tr>';
                    $html .= '<td>Marge / Produits</td>';
                    $html .= '<td>' . price($marginInfo['pv_products']) . '</td>';
                    $html .= '<td>' . price($marginInfo['pa_products']) . '</td>';
                    $html .= '<td>' . price($marginInfo['margin_on_products']) . ' (';
                    if ($conf_tx_marque) {
                        $html .= round($marginInfo['mark_rate_products'], 4);
                    } else {
                        $html .= round($marginInfo['margin_rate_products'], 4);
                    }
                    $html .= ' %)</td>';
                    $html .= '</tr>';
                }

                if (!empty($conf->service->enabled)) {
                    $html .= '<tr>';
                    $html .= '<td>Marge / Services</td>';
                    $html .= '<td>' . price($marginInfo['pv_services']) . '</td>';
                    $html .= '<td>' . price($marginInfo['pa_services']) . '</td>';
                    $html .= '<td>' . price($marginInfo['margin_on_services']) . ' (';
                    if ($conf_tx_marque) {
                        $html .= round($marginInfo['mark_rate_services'], 4);
                    } else {
                        $html .= round($marginInfo['margin_rate_services'], 4);
                    }
                    $html .= ' %)</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';

                $html .= '<tfoot>';
                $html .= '<tr>';
                $html .= '<td>Marge totale</td>';
                $html .= '<td>' . price($marginInfo['pv_total']) . '</td>';
                $html .= '<td>' . price($marginInfo['pa_total']) . '</td>';
                $html .= '<td>' . price($marginInfo['total_margin']) . ' (';
                if ($conf_tx_marque) {
                    $html .= round($marginInfo['total_mark_rate'], 4);
                } else {
                    $html .= round($marginInfo['total_margin_rate'], 4);
                }

                $html .= ' %)</td>';
                $html .= '</tr>';

                if (method_exists($this, 'renderMarginTableExtra')) {
                    $html .= $this->renderMarginTableExtra($marginInfo);
                }

                $html .= '</tfoot>';

                $html .= '</table>';
            }
        }

        return $html;
    }

    public function renderMarginTableExtra($marginInfo)
    {
        if (in_array($this->object_name, array('Bimp_Propal', 'BS_SavPropal', 'Bimp_Commande'))) {
            $remises_crt = 0;

            $lines = $this->getLines('not_text');

            foreach ($lines as $line) {
                $remises_crt += (float) $line->getRemiseCRT() * (float) $line->qty;
            }

            $total_pv = (float) $marginInfo['pv_total'];
            $total_pa = (float) $marginInfo['pa_total'];

            if ($remises_crt) {
                $html .= '<tr>';
                $html .= '<td>Remises CRT prévues</td>';
                $html .= '<td></td>';
                $html .= '<td><span class="danger">-' . BimpTools::displayMoneyValue($remises_crt, '', 0, 0, 0, 2, 1) . '</span></td>';
                $html .= '<td></td>';
                $html .= '</tr>';

                $total_pa -= $remises_crt;
            }

            if ((float) $total_pa !== (float) $marginInfo['pa_total']) {
                $total_marge = $total_pv - $total_pa;
                $tx = 0;


                if (BimpCore::getConf('bimpcomm_tx_marque')) {
                    if ($total_pv) {
                        $tx = ($total_marge / $total_pv) * 100;
                    }
                } else {
                    if ($total_pa) {
                        $tx = ($total_marge / $total_pa) * 100;
                    }
                }

                $html .= '<tr>';
                $html .= '<td>Marge finale prévue</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_pv, '', 0, 0, 0, 2, 1) . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_pa, '', 0, 0, 0, 2, 1) . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_marge, '', 0, 0, 0, 2, 1) . ' (' . BimpTools::displayFloatValue($tx, 4) . ' %)</td>';
                $html .= '</tr>';
            }
        }
        return $html;
    }

    public function renderFilesTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            global $conf;

            $dir = $this->getDirOutput() . '/' . dol_sanitizeFileName($this->getRef());

            if (!function_exists('dol_dir_list')) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            }

            $files_list = dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);

            $html .= '<table class="bimp_list_table">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Fichier</th>';
            $html .= '<th>Taille</th>';
            $html .= '<th>Date</th>';
            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';


            if (count($files_list)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . dol_sanitizeFileName($this->getRef()) . urlencode('/');
                foreach ($files_list as $file) {
                    $html .= '<tr>';

                    $html .= '<td><a class="btn btn-default" href="' . $url . $file['name'] . '" target="_blank">';
                    $html .= '<i class="' . BimpRender::renderIconClass(BimpTools::getFileIcon($file['name'])) . ' iconLeft"></i>';
                    $html .= $file['name'] . '</a></td>';

                    $html .= '<td>';
                    if (isset($file['size']) && $file['size']) {
                        $html .= $file['size'];
                    } else {
                        $html .= 'taille inconnue';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    if ((int) $file['date']) {
                        $html .= date('d / m / Y H:i:s', $file['date']);
                    }
                    $html .= '</td>';


                    $html .= '<td class="buttons">';
                    $html .= BimpRender::renderRowButton('Aperçu', 'search', '', 'documentpreview', array(
                                'attr' => array(
                                    'target' => '_blank',
                                    'mime'   => dol_mimetype($file['name'], '', 0),
                                    'href'   => $url . $file['name'] . '&attachment=0'
                                )
                                    ), 'a');

                    $onclick = $this->getJsActionOnclick('deleteFile', array('file' => htmlentities($file['fullname'])), array(
                        'confirm_msg'      => 'Veuillez confirmer la suppression de ce fichier',
                        'success_callback' => 'function() {bimp_reloadPage();}'
                    ));
                    $html .= BimpRender::renderRowButton('Supprimer', 'trash', $onclick);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="4">';
                $html .= BimpRender::renderAlerts('Aucun fichier', 'info');
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Documents PDF ' . $this->getLabel('of_the'), $html, '', array(
                        'icon'     => 'fas_file',
                        'type'     => 'secondary',
                        'foldable' => true
            ));
        }

        return $html;
    }

    public function renderEventsTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (!class_exists('FormActions')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
            }

            BimpTools::loadDolClass('comm/action', 'actioncomm', 'ActionComm');

            $type_element = static::$dol_module;
            $fk_soc = (int) $this->getData('fk_soc');
            switch ($type_element) {
                case 'facture':
                    $type_element = 'invoice';
                    break;

                case 'commande':
                    $type_element = 'order';
                    break;

                case 'commande_fournisseur':
                    $type_element = 'order_supplier';
                    $fk_soc = 0;
                    break;
            }

            $list = ActionComm::getActions($this->db->db, $fk_soc, $this->id, $type_element);

            if (!is_array($list)) {
                $html .= BimpRender::renderAlerts('Echec de la récupération de la liste des événements');
            } else {
                global $conf;

                $urlBack = DOL_URL_ROOT . '/' . $this->module . '/index.php?fc=' . $this->getController() . '&id=' . $this->id;
                $href = DOL_URL_ROOT . '/comm/action/card.php?action=create&datep=' . dol_print_date(dol_now(), 'dayhourlog');
                $href .= '&origin=' . $type_element . '&originid=' . $this->id . '&socid=' . (int) $this->getData('fk_soc');
                $href .= '&backtopage=' . urlencode($urlBack);

                if (isset($this->dol_object->fk_project) && (int) $this->dol_object->fk_project) {
                    $href .= '&projectid=' . $this->dol_object->fk_project;
                }

                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Réf.</th>';
                $html .= '<th>Action</th>';
                $html .= '<th>Type</th>';
                $html .= '<th>Date</th>';
                $html .= '<th>Par</th>';
                $html .= '<th></th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                if (count($list)) {
                    $userstatic = new User($this->db->db);

                    foreach ($list as $action) {
                        $html .= '<tr>';
                        $html .= '<td>' . $action->getNomUrl(1, -1) . '</td>';
                        $html .= '<td>' . $action->getNomUrl(0, 0) . '</td>';
                        $html .= '<td>';
                        if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
                            if ($action->type_picto) {
                                $html .= img_picto('', $action->type_picto);
                            } else {
                                switch ($action->type_code) {
                                    case 'AC_RDV':
                                        $html .= img_picto('', 'object_group');
                                        break;
                                    case 'AC_TEL':
                                        $html .= img_picto('', 'object_phoning');
                                        break;
                                    case 'AC_FAX':
                                        $html .= img_picto('', 'object_phoning_fax');
                                        break;
                                    case 'AC_EMAIL':
                                        $html .= img_picto('', 'object_email');
                                        break;
                                }
                                $html .= $action->type;
                            }
                        }
                        $html .= '</td>';
                        $html .= '<td align="center">';
                        $html .= dol_print_date($action->datep, 'dayhour');
                        if ($action->datef) {
                            $tmpa = dol_getdate($action->datep);
                            $tmpb = dol_getdate($action->datef);
                            if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
                                if ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes'] && $tmpa['seconds'] != $tmpb['seconds']) {
                                    $html .= '-' . dol_print_date($action->datef, 'hour');
                                }
                            } else {
                                $html .= '-' . dol_print_date($action->datef, 'dayhour');
                            }
                        }
                        $html .= '</td>';
                        $html .= '<td>';
                        if (!empty($action->author->id)) {
                            $userstatic->id = $action->author->id;
                            $userstatic->firstname = $action->author->firstname;
                            $userstatic->lastname = $action->author->lastname;
                            $html .= $userstatic->getNomUrl(1, '', 0, 0, 16, 0, '', '');
                        }
                        $html .= '</td>';
                        $html .= '<td align="right">';
                        if (!empty($action->author->id)) {
                            $html .= $action->getLibStatut(3);
                        }
                        $html .= '</td>';
                        $html .= '<td></td>';
                        $html .= '</tr>';
                    }
                } else {
                    $html .= '<tr>';
                    $html .= '<td colspan="6">';
                    $html .= BimpRender::renderAlerts('Aucun événement enregistré', 'info');
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';

                $html = BimpRender::renderPanel('Evénements', $html, '', array(
                            'foldable'       => true,
                            'type'           => 'secondary-forced',
                            'icon'           => 'fas_clock',
                            'header_buttons' => array(
                                array(
                                    'label'       => 'Créer un événement',
                                    'icon_before' => 'plus-circle',
                                    'classes'     => array('btn', 'btn-default'),
                                    'attr'        => array(
                                        'onclick' => 'window.location = \'' . $href . '\''
                                    )
                                )
                            )
                ));
            }
        }

        return $html;
    }

    public function renderContacts()
    {
        $html = '';

        $html .= '<table class="bimp_list_table">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Nature</th>';
        $html .= '<th>Tiers</th>';
        $html .= '<th>Utilisateur / Contact</th>';
        $html .= '<th>Type de contact</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';
        $html .= '<tbody id="' . $list_id . '">';
        $html .= $this->renderContactsList();

        $html .= '</tbody>';

        $html .= '</table>';

        return BimpRender::renderPanel('Liste des contacts', $html, '', array(
                    'type'           => 'secondary',
                    'icon'           => 'user-circle',
                    'header_buttons' => array(
                        array(
                            'label'       => 'Ajouter un contact',
                            'icon_before' => 'plus-circle',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => $this->getJsActionOnclick('addContact', array('id_client' => (int) $this->getData('fk_soc')), array(
                                    'form_name'        => 'contact',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                                ))
                            )
                        )
                    )
        ));
    }

    public function renderContactsList()
    {
        $html = '';

        $list = array();

        if ($this->isLoaded() && method_exists($this->dol_object, 'liste_contact')) {
            $list_int = $this->dol_object->liste_contact(-1, 'internal');
            $list_ext = $this->dol_object->liste_contact(-1, 'external');
            $list = BimpTools::merge_array($list_int, $list_ext);
        }

        if (count($list)) {
            global $conf;
            BimpTools::loadDolClass('societe');
            BimpTools::loadDolClass('contact');

            $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';

            foreach ($list as $item) {
                $html .= '<tr>';
                switch ($item['source']) {
                    case 'internal':
                        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $item['id']);
                        $html .= '<td>Utilisateur</td>';
                        $html .= '<td>' . $conf->global->MAIN_INFO_SOCIETE_NOM . '</td>';
                        $html .= '<td>';
                        if (BimpObject::objectLoaded($user)) {
                            $html .= $user->getLink();
                        } else {
                            $html .= '<span class="danger">L\'utilisateur #' . $item['id'] . ' n\'existe plus</span>';
                        }
                        $html .= '</td>';
                        break;

                    case 'external':
                        $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $item['socid']);
                        $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $item['id']);

                        $html .= '<td>Contact tiers</td>';
                        $html .= '<td>';
                        if (BimpObject::objectLoaded($soc)) {
                            $html .= $soc->getLink();
                        } else {
                            $html .= '<span class="danger">Le tiers #' . $item['socid'] . ' n\'existe plus</span>';
                        }
                        $html .= '</td>';
                        $html .= '<td>';
                        if (BimpObject::objectLoaded($contact)) {
                            $html .= $contact->getLink();
                        } else {
                            $html .= '<span class="danger">Le contact #' . $item['id'] . ' n\'existe plus</span>';
                        }
                        $html .= '</td>';
                        break;
                }
                $html .= '<td>' . $item['libelle'] . '</td>';
                $html .= '<td style="text-align: right">';
                $html .= BimpRender::renderRowButton('Supprimer le contact', 'trash', $this->getJsActionOnclick('removeContact', array('id_contact' => (int) $item['rowid']), array(
                                    'confirm_msg'      => 'Etes-vous sûr de vouloir supprimer ce contact?',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                )));
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="5">';
            $html .= BimpRender::renderAlerts('Aucun contact enregistré', 'info');
            $html .= '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    public function renderContentExtraLeft()
    {
        return '';
    }

    public function renderContentExtraRight()
    {
        return '';
    }

    public function renderRemisesGlobalesList()
    {
        $html = '';

        if ($this->isLoaded() && static::$remise_globale_allowed) {
            $rg = BimpObject::getInstance('bimpcommercial', 'RemiseGlobale');
            $list = new BC_ListTable($rg);
            $list->addFieldFilterValue('obj_type', static::$element_name);
            $list->addFieldFilterValue('id_obj', (int) $this->id);
            $list->setAddFormValues(array(
                'fields' => array(
                    'obj_type'  => static::$element_name,
                    'id_object' => (int) $this->id,
                    'label'     => 'Remise exceptionnelle sur l\'int\\égralit\\é ' . $this->getLabel('of_the')
                )
            ));
            $html = $list->renderHtml();
        }

        return $html;
    }

    public function renderForceCreateBySoc()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $html = 'Ce client est au statut ' . $client->displayData('solvabilite_status') . '<br/>';
            $html .= BimpInput::renderInput('toggle', 'force_create_by_soc', 0);
        } else {
            $html = '<input type="hidden" value="0" input_name="force_create_by_soc"/><span class="danger">NON</span>';
        }

        return $html;
    }

    // Traitements:

    public function checkLines()
    {
        $errors = array();

        if ($this->lines_locked) {
            return array();
        }

        if (($this->isLoaded())) {
            $dol_lines = array();
            $bimp_lines = array();

            foreach ($this->dol_object->lines as $line) {
                $dol_lines[(int) $line->id] = $line;
            }

            $bimp_line = $this->getChildObject('lines');
            $rows = $this->db->getRows($bimp_line->getTable(), '`id_obj` = ' . (int) $this->id, null, 'array', array('id', 'id_line', 'position', 'remise', 'type'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $bimp_lines[(int) $r['id_line']] = array(
                        'id'       => (int) $r['id'],
                        'position' => (int) $r['position'],
                        'remise'   => (float) $r['remise'],
                        'type'   => (float) $r['type']
                    );
                }
            }

            // Suppression des lignes absentes de l'objet dolibarr:
            foreach ($bimp_lines as $id_dol_line => $data) {
                if (!(int) $id_dol_line) {
                    continue;
                }
                if (!array_key_exists((int) $id_dol_line, $dol_lines)) {
                    if ($bimp_line->fetch((int) $data['id'], $this, true)) {
                        $line_warnings = array();
                        $line_errors = $bimp_line->delete($line_warnings, true);
                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la suppression d\'une ligne supprimée depuis l\'ancienne version');
                        }
                        unset($bimp_lines[$id_dol_line]);
                    }
                }
                
                elseif($data['type'] == $bimp_line::LINE_TEXT && $dol_lines[$id_dol_line]->total > 0)
                    $errors[] = 'Ligne '.$dol_lines[$id_dol_line]->desc.' de type text avec un montant !!!!!!!';
            }

            // Création des lignes absentes de l'objet bimp: 
            $bimp_line->reset();
            $i = 0;
            foreach ($dol_lines as $id_dol_line => $dol_line) {
                $i++;
                if (!array_key_exists($id_dol_line, $bimp_lines) && method_exists($bimp_line, 'createFromDolLine')) {
                    $objectLine = BimpObject::getInstance($bimp_line->module, $bimp_line->object_name);
                    $objectLine->parent = $this;

                    BimpCore::addlog('Ajout ligne absente ' . $this->getLabel('of_the'), Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $this, array(
                        'ID DOL LINE' => $id_dol_line
                    ));

                    $line_errors = $objectLine->createFromDolLine((int) $this->id, $dol_line);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la récupération des données pour la ligne n° ' . $i);
                    }
                } else {
                    if ((int) $bimp_lines[(int) $id_dol_line]['position'] !== (int) $dol_line->rang) {
                        $bimp_line->updateField('position', (int) $dol_line->rang, $bimp_lines[(int) $id_dol_line]['id']);
                    }
                    if ((float) $bimp_lines[(int) $id_dol_line]['remise'] !== (float) $dol_line->remise_percent) {
                        if ($bimp_line->fetch((int) $bimp_lines[(int) $id_dol_line]['id'], $this)) {
                            $remises_errors = $bimp_line->checkRemises();
                            if (count($remises_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($remises_errors, 'Des erreurs sont survenues lors de la synchronisation des remises pour la ligne n° ' . $i);
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function resetLines()
    {
        if ($this->isLoaded()) {
            $cache_key = $this->module . '_' . $this->object_name . '_' . $this->id . '_lines_list';
            if (self::cacheExists($cache_key)) {
                unset(self::$cache[$cache_key]);
            }
        }
    }

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $errors = array();

        if (!$force_create && !$this->can("create")) {
            return array('Vous n\'avez pas la permission de créer ' . $this->getLabel('a'));
        }

        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!method_exists($this->dol_object, 'createFromClone')) {
            return array('Cette fonction n\'est pas disponible pour ' . $this->getLabel('the_plur'));
        }

        $lines_errors = $this->checkLines();

        if (count($lines_errors)) {
            return BimpTools::getMsgFromArray($lines_errors, 'Copie impossible');
        }

        if ($this->field_exists('replaced_ref')) {
            $new_data['replaced_ref'] = '';
        }

//        $validate_errors = $this->validate();
//        if (count($validate_errors)) {
//            return array(BimpTools::getMsgFromArray($validate_errors), BimpTools::ucfirst($this->getLabel('this')) . ' comporte des erreurs. Copie impossible');
//        }

        global $user, $conf, $hookmanager;

        $new_object = clone $this;
        $new_object->id = null;
        $new_object->id = 0;

        if ($this->dol_field_exists('zone_vente')) {
            $new_object->set('zone_vente', 1);
        }

        $new_soc = false;

        if (isset($new_data['fk_soc']) && ((int) $new_data['fk_soc'] !== (int) $this->getData('fk_soc'))) {
            $new_soc = true;
            $new_object->set('ref_client', '');
            $new_object->dol_object->fk_project = '';
            $new_object->dol_object->fk_delivery_address = '';
        } elseif (empty($conf->global->MAIN_KEEP_REF_CUSTOMER_ON_CLONING)) {
            $new_object->set('ref_client', '');
        }

        foreach ($new_data as $field => $value) {
            $new_object->set($field, $value);
        }

        $new_object->set('id', 0);
        $new_object->set('ref', '');
        $new_object->set('fk_statut', 0);
        $new_object->set('logs', '');

        $new_object->dol_object->user_author = $user->id;
        $new_object->dol_object->user_valid = '';

        $copy_errors = $new_object->create($warnings, $force_create);

        if (count($copy_errors)) {
            $errors[] = BimpTools::getMsgFromArray($copy_errors, 'Echec de la copie ' . $this->getLabel('of_the'));
        } else {
            // Copie des contacts: 
            $new_object->copyContactsFromOrigin($this, $errors);

            // Copie des lignes: 
            $params = array(
                'is_clone' => true
            );


            if (isset($new_data['inverse_qty']))
                $params['inverse_qty'] = $new_data['inverse_qty'];

            $lines_errors = $new_object->createLinesFromOrigin($this, $params);

            if (count($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la copie des lignes ' . $this->getLabel('of_the'));
            }

            // Copie des remises globales: 
            if (static::$remise_globale_allowed) {
                $new_object->copyRemisesGlobalesFromOrigin($this, $errors, $params['inverse_qty']);
            }

            if (is_object($hookmanager)) {
                $parameters = array('objFrom' => $this->dol_object, 'clonedObj' => $new_object->dol_object);
                $action = '';
                $hookmanager->executeHooks('createFrom', $parameters, $new_object->dol_object, $action);
            }

            $this->reset();
            $this->fetch($new_object->id);
        }

        return $errors;
    }

    public function createLinesFromOrigin($origin, $params = array())
    {
        $errors = array();

        $params = BimpTools::overrideArray(array(
                    'inverse_prices'        => false,
                    'inverse_qty'           => false,
                    'pa_editable'           => true,
                    'is_clone'              => false,
                    'is_review'             => false,
                    'copy_remises_globales' => false,
                        ), $params);

        if (!BimpObject::objectLoaded($origin) || !is_a($origin, 'BimpComm')) {
            return array('Element d\'origine absent ou invalide');
        }

        $lines = $origin->getChildrenObjects('lines', array(), 'position', 'asc');

        $warnings = array();
        $i = 0;

        // Création des lignes: 
        $lines_new = array();

        foreach ($lines as $line) {
            $i++;

            // Lignes à ne pas copier en cas de clonage: 
            if ($params['is_clone'] && (in_array($line->getData('linked_object_name'), array(
                        'discount'
                    )) || (int) $line->id_remise_except)) {
                continue;
            }

            $new_line = BimpObject::getInstance($this->module, $this->object_name . 'Line');

            $data = $line->getDataArray();
            $data['id_obj'] = $this->id;
            unset($data['id_line']);
            unset($data['id_parent_line']);

            if (!$params['is_review']) {
                unset($data['linked_object_name']);
                unset($data['linked_id_object']);

                if ($line->getData('linked_object_name')) {
                    $data['deletable'] = 1;
                    $data['editable'] = 1;
                }
            }

            if (($params['is_clone'])) {
                switch ($origin->object_name) {
                    case 'BS_SavPropal':
                        unset($data['id_reservation']);
                        break;

                    case 'Bimp_Commande':
                        unset($data['ref_reservations']);
                        unset($data['shipments']);
                        unset($data['factures']);
                        unset($data['equipments_returned']);
                        unset($data['qty_modif']);
                        unset($data['qty_total']);
                        unset($data['qty_shipped']);
                        unset($data['qty_to_ship']);
                        unset($data['qty_billed']);
                        unset($data['qty_to_bill']);
                        unset($data['qty_shipped_not_billed']);
                        unset($data['qty_billed_not_shipped']);
                        unset($data['exp_periods_start']);
                        unset($data['next_date_exp']);
                        unset($data['fac_periods_start']);
                        unset($data['next_date_fac']);
                        unset($data['achat_periods_start']);
                        unset($data['next_date_achat']);
                        break;

                    case 'Bimp_CommandeFourn':
                        unset($data['receptions']);
                        unset($data['qty_modif']);
                        unset($data['qty_total']);
                        unset($data['qty_received']);
                        unset($data['qty_to_receive']);
                        unset($data['qty_billed']);
                        unset($data['qty_to_billed']);
                        break;
                }
            }

            if ($new_line->field_exists('pa_editable')) {
                $data['pa_editable'] = (int) $params['pa_editable'];
            }

            if ($line->field_exists('remise_pa') &&
                    $new_line->field_exists('remise_pa')) {
                $data['remise_pa'] = (float) $line->getData('remise_pa');
                if ($params['inverse_prices']) {
                    $data['remise_pa'] *= -1;
                }
            }

            foreach ($data as $field => $value) {
                if (!$new_line->field_exists($field)) {
                    unset($data[$field]);
                }
            }

            $qty = (float) $line->getFullQty();

            if ($params['inverse_qty']) {
                $qty *= -1;
            }

            $new_line->validateArray($data);

            $new_line->desc = $line->desc;
            $new_line->tva_tx = $line->tva_tx;
            $new_line->id_product = $line->id_product;
            $new_line->qty = $qty;
            $new_line->pu_ht = $line->pu_ht;
            $new_line->pa_ht = $line->pa_ht;
            $new_line->id_fourn_price = $line->id_fourn_price;
            $new_line->date_from = $line->date_from;
            $new_line->date_to = $line->date_to;
            $new_line->id_remise_except = $line->id_remise_except;

            if ($params['inverse_prices']) {
                $new_line->pu_ht *= -1;
                $new_line->pa_ht *= -1;
            }

            // On libère l'avoir associé dans le cas d'une révision
            if ($params['is_review'] && (int) $line->id_remise_except) {
                $this->db->update($line::$dol_line_table, array(
                    'fk_remise_except' => 0
                        ), '`' . $line::$dol_line_primary . '` = ' . (int) $line->getData('id_line'));
            }

            $line_errors = $new_line->create($warnings, true);
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne n°' . $i);

                // On réassocie l'avoir dans le cas d'une révision
                if ($params['is_review'] && (int) $new_line->id_remise_except) {
                    $this->db->update($line::$dol_line_table, array(
                        'fk_remise_except' => $new_line->id_remise_except
                            ), '`' . $line::$dol_line_primary . '` = ' . (int) $line->getData('id_line'));
                }
                continue;
            } else {
                if ($params['is_review'] && ((int) $line->getData('linked_id_object') || (string) $line->getData('linked_object_name'))) {
                    // On  désassocie l'objet lié de l'ancienne ligne dans le cas d'une révision: 
                    $this->db->update($line->getTable(), array(
                        'linked_id_object'   => 0,
                        'linked_object_name' => ''
                            ), '`' . $line->getPrimary() . '` = ' . (int) $line->id);
                }
            }

            $lines_new[(int) $line->id] = (int) $new_line->id;

            // Attribution des équipements si nécessaire: 
            if (!$params['is_clone'] && $line->equipment_required && $new_line->equipment_required && $line->isProductSerialisable()) {
                $equipmentlines = $line->getEquipmentLines();

                foreach ($equipmentlines as $equipmentLine) {
                    $data = $equipmentLine->getDataArray();

                    if ($params['inverse_prices']) {
                        $data['pu_ht'] *= -1;
                        $data['id_fourn_price'] = 0;
                        $data['pa_ht'] *= -1;
                    }

                    $new_line->attributeEquipment($data['id_equipment']);
                }
            }

            // Création des remises pour la ligne en cours:
            $errors = BimpTools::merge_array($errors, $new_line->copyRemisesFromOrigin($line, $params['inverse_prices'], $params['copy_remises_globales']));
        }

        // Attribution des lignes parentes: 
        foreach ($lines as $line) {
            $id_parent_line = (int) $line->getData('id_parent_line');

            if ($id_parent_line) {
                if (isset($lines_new[(int) $line->id]) && (int) $lines_new[(int) $line->id] && isset($lines_new[$id_parent_line]) && (int) $lines_new[$id_parent_line]) {
                    $new_line = BimpCache::getBimpObjectInstance($this->module, $this->object_name . 'Line', (int) $lines_new[(int) $line->id]);
                    if (BimpObject::objectLoaded($new_line)) {
                        $new_line->updateField('id_parent_line', (int) $lines_new[(int) $id_parent_line]);
                    }
                }
            }
        }
        return $errors;
    }

    public function copyRemisesGlobalesFromOrigin($origin, &$errors = array(), $inverse_price = false)
    {
        if ($this->isLoaded($errors)) {
            if (BimpObject::objectLoaded($origin) && is_a($origin, 'BimpComm')) {
                // Conversion des remises globales externes: 
                $lines = $origin->getLines('not_text');
                $ext_rgs = array();

                foreach ($lines as $line) {
                    foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'ObjectLineRemise', array(
                        'id_object_line'           => (int) $line->id,
                        'object_type'              => $line::$parent_comm_type,
                        'linked_id_remise_globale' => array(
                            'operator' => '>',
                            'value'    => 0
                        )
                    )) as $remise) {
                        if (!isset($ext_rgs[(int) $remise->getData('linked_id_remise_globale')])) {
                            $ext_rgs[(int) $remise->getData('linked_id_remise_globale')] = 0;
                        }

                        $amount = 0;
                        switch ($remise->getData('type')) {
                            case ObjectLineRemise::OL_REMISE_AMOUNT:
                                $amount = (float) $remise->getData('montant');
                                if ((int) $remise->getData('per_unit')) {
                                    $amount *= $line->getFullQty();
                                }
                                break;

                            case ObjectLineRemise::OL_REMISE_PERCENT:
                                $amount = ($line->getTotalTtcWithoutRemises(true) * ((float) $remise->getData('percent') / 100));
                                break;
                        }
                        $ext_rgs[(int) $remise->getData('linked_id_remise_globale')] += $amount;
                    }
                }

                foreach ($ext_rgs as $id_rg => $amount) {
                    $rg = BimpCache::getBimpObjectInstance('bimpcommercial', 'RemiseGlobale', (int) $id_rg);

                    if (BimpObject::objectLoaded($rg)) {
                        $new_rg = BimpObject::getInstance('bimpcommercial', 'RemiseGlobale');

                        $label = 'Part de la remise "' . $rg->getData('label') . '"';

                        $rg_obj = $rg->getParentObject();
                        if (BimpObject::objectLoaded($rg_obj)) {
                            $label .= ' (' . BimpTools::ucfirst($rg_obj->getLabel()) . ' ' . $rg_obj->getRef() . ')';
                        }

                        if ($inverse_price) {
                            $amount *= -1;
                        }

                        $new_rg->validateArray(array(
                            'obj_type' => static::$element_name,
                            'id_obj'   => (int) $this->id,
                            'label'    => $label,
                            'type'     => 'amount',
                            'amount'   => (float) $amount
                        ));

                        $rg_warnings = array();
                        $rg_errors = $new_rg->create($rg_warnings, true);
                        if (count($rg_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($rg_errors, 'Echec de la création de la remise globale: ' . $label);
                        }

                        if (count($rg_warnings)) {
                            $errors[] = BimpTools::getMsgFromArray($rg_warnings, 'Erreur lors de la création de la remise globale: ' . $label);
                        }
                    }
                }

                // Copie des remises globales
                $rgs = array();
                if ($origin::$remise_globale_allowed && $this::$remise_globale_allowed) {
                    $rgs = $origin->getRemisesGlobales();

                    if (!empty($rgs)) {
                        foreach ($rgs as $rg) {
                            $new_rg = BimpObject::getInstance('bimpcommercial', 'RemiseGlobale');
                            $new_rg->trigger_parent_process = false;

                            $data = $rg->getDataArray();
                            $data['obj_type'] = static::$element_name;
                            $data['id_obj'] = (int) $this->id;
                            $label_pattern = 'Remise exceptionnelle sur l\'intégralité';
                            if (preg_match('/^' . preg_quote($label_pattern) . '/', $data['label'])) {
                                $data['label'] = 'Remise exceptionnelle sur l\'intégralité ' . $this->getLabel('of_the');
                            }

                            if ($inverse_price && $data['type'] === 'amount' && (float) $data['amount']) {
                                $data['amount'] *= -1;
                            }

                            $rg_warnings = array();
                            $rg_errors = $new_rg->validateArray($data);

                            if (!count($rg_errors)) {
                                $rg_errors = $new_rg->create($rg_warnings, true);
                            }

                            if (count($rg_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($rg_errors, 'Echec de la copie de la remise globale #' . $rg->id);
                            }

                            if (count($rg_warnings)) {
                                $errors[] = BimpTools::getMsgFromArray($rg_warnings, 'Erreur lors de la copie de la remise globale #' . $rg->id);
                            }

                            $new_rg->trigger_parent_process = true;
                        }
                    }
                }

                if (!empty($rgs) || !empty($ext_rgs)) {
                    $process_errors = $this->processRemisesGlobales();

                    if (count($process_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($process_errors, 'Erreurs lors du calcul de la répartition des remises globales');
                    }
                }
            }
        }

        return $errors;
    }

    public function checkEquipmentsAttribution(&$errors = array())
    {
        $errors = array();

        $lines = $this->getChildrenObjects('lines');

        foreach ($lines as $line) {
            $line_errors = $line->checkEquipmentsAttribution();
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
            }
        }

        return count($errors) ? 0 : 1;
    }

    public function createAcompte($amount, $id_mode_paiement, $id_bank_account = 0, $date_paiement = null, $use_caisse = false, $num_paiement = '', $nom_emetteur = '', $banque_emetteur = '', &$warnings = array())
    {

        global $user, $langs;
        $errors = array();

        $caisse = null;
        $id_caisse = 0;

        $type_paiement = $id_mode_paiement;

        $id_mode_paiement = $this->db->getValue('c_paiement', 'id', '`code` = \'' . $id_mode_paiement . '\'');

        if (!$this->useCaisseForPayments) {
            $use_caisse = false;
        } elseif (!$use_caisse && in_array($type_paiement, array('LIQ'))) {
            $errors[] = 'Paiement en caisse obligatoire pour les réglements en espèces';
            return $errors;
        }

        if ($use_caisse) {
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
            if (!$id_caisse) {
                $errors[] = 'Veuillez vous connecter à une caisse';
            } else {
                $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                if (!$caisse->isLoaded()) {
                    $errors[] = 'La caisse à laquelle vous êtes connecté est invalide.';
                } else {
                    $caisse->isValid($errors);
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        if (!(int) $id_bank_account) {
            if ($use_caisse) {
                $id_bank_account = (int) $caisse->getData('id_account');
            }
            if (!$id_bank_account) {
                $id_bank_account = (int) BimpCore::getConf('bimpcaisse_id_default_account');
            }
        }

        $bank_account = null;

        if (!$id_bank_account) {
            $errors[] = 'Compte bancaire absent';
        } else {
            BimpTools::loadDolClass('compta/bank', 'account');
            $bank_account = new Account($this->db->db);
            $bank_account->fetch((int) $id_bank_account);

            if (!BimpObject::objectLoaded($bank_account)) {
                $errors[] = 'Le compte bancaire d\'ID ' . $id_bank_account . ' n\'existe pas';
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $client = $this->getChildObject('client');

        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Client absent';
            return $errors;
        }

        $id_client = (int) $client->id;

        if ($amount > 0 && !count($errors)) {
            // Création de la facture: 
            BimpTools::loadDolClass('compta/facture', 'facture');
            $factureA = new Facture($this->db->db);
            $factureA->type = 3;
            $factureA->date = ($date_paiement) ? strtotime($date_paiement) : dol_now();
            $factureA->socid = $id_client;
            $factureA->cond_reglement_id = 1;
            $factureA->modelpdf = 'bimpfact';
            $factureA->fk_account = $id_bank_account;

            if ($this->field_exists('ef_type') && $this->dol_field_exists('ef_type')) {
                $factureA->array_options['options_type'] = $this->getData('ef_type');
            }
            if ($this->field_exists('entrepot') && $this->dol_field_exists('entrepot')) {
                $factureA->array_options['options_entrepot'] = $this->getData('entrepot');
            }
            if ($factureA->create($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($factureA), 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
            } else {
                $factureA->addline("Acompte", $amount / 1.2, 1, 20, null, null, null, 0, null, null, null, null, null, 'HT', null, 1, null, null, null, null, null, null, $amount / 1.2);
                $user->rights->facture->creer = 1;
                $factureA->validate($user);

                // Création du paiement: 
                BimpTools::loadDolClass('compta/paiement', 'paiement');
                $payement = new Paiement($this->db->db);
                $payement->amounts = array($factureA->id => $amount);
                $payement->datepaye = ($date_paiement ? BimpTools::getDateForDolDate($date_paiement) : dol_now());
                $payement->paiementid = (int) $id_mode_paiement;
                $payement->num_paiement = $num_paiement;
                if ($payement->create($user) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Des erreurs sont survenues lors de la création du paiement de la facture d\'acompte');
                } else {
                    // Ajout du paiement au compte bancaire: 
                    if ($payement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_bank_account, $nom_emetteur, $banque_emetteur) < 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Echec de l\'ajout de l\'acompte au compte bancaire ' . $bank_account->bank);
                    }

                    // Enregistrement du paiement caisse: 
                    if ($use_caisse) {
                        $errors = BimpTools::merge_array($errors, $caisse->addPaiement($payement, $factureA->id));
                    }

                    $factureA->set_paid($user);
                }

                // Création de la remise client: 
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discount = new DiscountAbsolute($this->db->db);
                $discount->description = "Acompte";
                $discount->fk_soc = $factureA->socid;
                $discount->fk_facture_source = $factureA->id;
                $discount->amount_ht = $amount / 1.2;
                $discount->amount_ttc = $amount;
                $discount->amount_tva = $amount - ($amount / 1.2);
                $discount->tva_tx = 20;
                if ($discount->create($user) <= 0) {
                    $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Des erreurs sont survenues lors de la création de la remise sur acompte');
                } else {
                    $line_errors = $this->insertDiscount((int) $discount->id);

                    if (count($line_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de l\'ajout de l\'acompte ' . $this->getLabel('to_the'));
                    }
                }

                addElementElement(static::$dol_module, $factureA->table_element, $this->id, $factureA->id);

                include_once(DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php');
                if ($factureA->generateDocument('bimpfact', $langs) <= 0) {
                    $fac_errors = BimpTools::getErrorsFromDolObject($factureA, $error = null, $langs);
                    $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création du fichier PDF de la facture d\'acompte');
                }
            }
        }

        return $errors;
    }

    public function removeLinesTvaTx()
    {
        $errors = array();

        if (!$this->areLinesEditable()) {
            $errors[] = 'Les lignes ' . $this->getLabel('of_this') . ' ne sont pas éditables';
            return $errors;
        }

        $lines = $this->getLines('no_text');

        foreach ($lines as $line) {
            $line->tva_tx = 0;
            $line_errors = $line->update();
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
            }
        }

        return $errors;
    }

    public function checkPrice()
    {
        
    }

    public function insertDiscount($id_discount)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!method_exists($this->dol_object, 'insert_discount')) {
                $errors[] = 'L\'utilisation de remise n\'est pas possible pour ' . $this->getLabel('the_plur');
            }
//            elseif ($this->object_name !== 'Bimp_Facture' && (int) $this->getData('fk_statut') > 0) {
//                $errors[] = $error_label . ' - ' . $this->getData('the') . ' doit avoit le statut "Brouillon';
//            } 
            else {
                if (!class_exists('DiscountAbsolute')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
                }

                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch((int) $id_discount);

                if (!BimpObject::objectLoaded($discount)) {
                    $errors[] = 'La remise d\'ID ' . $id_discount . ' n\'existe pas';
                } else {
                    BimpObject::loadClass('bimpcore', 'Bimp_Societe');
                    $used_label = Bimp_Societe::getDiscountUsedLabel($id_discount, true, $this->getSocAvalaibleDiscountsAllowed());
                    if ($used_label) {
                        $errors[] = 'La remise #' . $id_discount . ' de ' . BimpTools::displayMoneyValue($discount->amount_ttc) . ' a été ' . str_replace('Ajouté', 'ajoutée', $used_label);
                    }

                    if (!count($errors)) {
                        if ($this->object_name === 'Bimp_Facture') {
                            // Recherche d'une éventuelle ligne de commmande à traiter: 
                            $sql = 'SELECT l.rowid as id_line FROM ' . MAIN_DB_PREFIX . 'commandedet l';
                            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande c ON c.rowid = l.fk_commande';
                            $sql .= ' WHERE l.fk_remise_except = ' . (int) $discount->id;
                            $sql .= ' AND c.fk_statut > 0';

                            $result = $this->db->executeS($sql, 'array');

                            $commLine = null;
                            if (isset($result[0]['id_line']) && (int) $result[0]['id_line']) {
                                $commLine = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                                            'id_line' => (int) $result[0]['id_line']
                                ));
                            }

                            $ok = false;
                            if ((int) $this->getData('fk_statut') > 0) {
                                if ($discount->link_to_invoice(0, $this->id) <= 0) {
                                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Echec de l\'application de la remise');
                                } else {
                                    if (BimpObject::objectLoaded($commLine) && (float) $commLine->getFullQty() == 1) {
                                        $commLine->updateField('factures', array(
                                            $this->id => array(
                                                'qty' => 1
                                            )
                                        ));
                                        $commLine->checkQties();
                                    }
                                }
                            } else {
                                $line = $this->getLineInstance();

                                $line_errors = $line->validateArray(array(
                                    'id_obj'    => (int) $this->id,
                                    'type'      => ObjectLine::LINE_FREE,
                                    'deletable' => 1,
                                    'editable'  => 0,
                                    'remisable' => 0,
                                ));

                                if (BimpObject::objectLoaded($commLine)) {
                                    $line->set('linked_object_name', 'commande_line');
                                    $line->set('linked_id_object', $commLine->id);
                                } else {
                                    $line->set('linked_object_name', 'discount');
                                    $line->set('linked_id_object', $discount->id);
                                }

                                if (!count($line_errors)) {
                                    $line->desc = BimpTools::getRemiseExceptLabel($discount->description);
                                    $line->id_product = 0;
                                    $line->pu_ht = -$discount->amount_ht;
                                    $line->pa_ht = -$discount->amount_ht;
                                    $line->qty = 1;
                                    $line->tva_tx = (float) $discount->tva_tx;
                                    $line->id_remise_except = (int) $discount->id;
                                    $line->remise = 0;

                                    $line_warnings = array();
                                    $line_errors = $line->create($line_warnings, true);
                                }

                                if (count($line_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne de remise');
                                } else {
                                    if (BimpObject::objectLoaded($commLine) && (float) $commLine->getFullQty() == 1) {
                                        $commLine->updateField('factures', array(
                                            $this->id => array(
                                                'qty' => 1
                                            )
                                        ));
                                        $commLine->checkQties();
                                    }
                                }
                            }
                            $this->checkIsPaid();
                        } else {
                            $line = $this->getLineInstance();

                            $line_errors = $line->validateArray(array(
                                'id_obj'             => (int) $this->id,
                                'type'               => ObjectLine::LINE_FREE,
                                'deletable'          => 1,
                                'editable'           => 0,
                                'remisable'          => 0,
                                'linked_id_object'   => (int) $discount->id,
                                'linked_object_name' => 'discount'
                            ));

                            if (!count($line_errors)) {
                                $line->desc = BimpTools::getRemiseExceptLabel($discount->description);
                                $line->id_product = 0;
                                $line->pu_ht = -$discount->amount_ht;
                                $line->pa_ht = -$discount->amount_ht;
                                $line->qty = 1;
                                $line->tva_tx = (float) $discount->tva_tx;
                                $line->id_remise_except = (int) $discount->id;
                                $line->remise = 0;

                                if ($this->object_name === 'Bimp_Commande' && (int) $this->getData('fk_statut') !== 0) {
                                    $line->qty = 0;
                                    $line->set('qty_modif', 1);
                                }

                                $line_warnings = array();
                                $line_errors = $line->create($line_warnings, true);
                            }

                            if (count($line_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne de remise');
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function processRemisesGlobales()
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->areLinesEditable()) {
            $remises = $this->getRemisesGlobales();
            $lines = $this->getLines('not_text');

            if (count($remises)) {
                $total_ttc = (float) $this->getTotalTtcWithoutRemises(true);
                $total_lines = 0;


                foreach ($lines as $line) {
                    if ($line->isRemisable()) {
                        $total_lines += (float) $line->getTotalTtcWithoutRemises();
                    }
                }

                foreach ($remises as $rg) {
                    $lines_rate = 0;

                    if ($total_ttc) {
                        switch ($rg->getData('type')) {
                            case 'percent':
                                $remise_rate = (float) $rg->getData('percent');
                                $remise_amount = $total_ttc * ($remise_rate / 100);
                                break;

                            case 'amount':
                                $remise_amount = (float) $rg->getData('amount');
                                break;
                        }

                        if ($total_lines) {
                            $lines_rate = ($remise_amount / $total_lines) * 100;
                        }
                    }

                    foreach ($lines as $line) {
                        $errors = BimpTools::merge_array($errors, $line->setRemiseGlobalePart($rg, $lines_rate));
                    }
                }
            }

            foreach ($lines as $line) {
                $line->checkRemisesGlobales();
            }
        }

        return $errors;
    }

    public function checkContacts()
    {
        $errors = array();

        if (in_array($this->object_name, array('Bimp_Propal', 'Bimp_Commande', 'Bimp_Facture'))) {
            global $user;
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                // Vérif commercial suivi: 
                $tabConatact = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
//                print_r($tabConatact);
                if (count($tabConatact) < 1) {
                    $ok = false;
                    $tabComm = $client->dol_object->getSalesRepresentatives($user);

                    // Il y a un commercial pour ce client
                    if (count($tabComm) > 0) {
//                        die('AAAAAAAAAAAAAAAA');
                        $this->dol_object->add_contact($tabComm[0]['id'], 'SALESREPFOLL', 'internal');
                        $ok = true;

                        // Il y a un commercial définit par défaut (bimpcore)
                    } elseif ((int) BimpCore::getConf('user_as_default_commercial', 1)) {
                        $this->dol_object->add_contact($user->id, 'SALESREPFOLL', 'internal');
                        $ok = true;
//                        die('CCCCCCCCCCCCCCCC');
                        // L'objet est une facture et elle a une facture d'origine
                    } elseif ($this->object_name === 'Bimp_Facture' && (int) $this->getData('fk_facture_source')) {
                        $fac_src = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $this->getData('fk_facture_source'));
                        if (BimpObject::objectLoaded($fac_src)) {
                            $contacts = $fac_src->dol_object->getIdContact('internal', 'SALESREPFOLL');
                            if (count($contacts) > 0) {
                                $this->dol_object->add_contact($contacts[0]['id'], 'SALESREPFOLL', 'internal');
                                $ok = true;
                            }
                        }
                    }

                    if (!$ok) {
                        $errors[] = 'Pas de Commercial Suivi';
                    }
                }

                // Vérif contact signataire: 
                $tabConatact = $this->dol_object->getIdContact('internal', 'SALESREPSIGN');
                if (count($tabConatact) < 1) {
//                                            die('DDDDDDDDDDDDDD');

                    $this->dol_object->add_contact($user->id, 'SALESREPSIGN', 'internal');
                }
            }
        }
        return $errors;
    }

    public function addLog($text)
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->field_exists('logs')) {
            $logs = (string) $this->getData('logs');
            if ($logs) {
                $logs .= '<br/>';
            }
            global $user, $langs;
            $logs .= ' - <strong>Le ' . date('d / m / Y à H:i') . '</strong> par ' . $user->getFullName($langs) . ': ' . $text;
            $errors = $this->updateField('logs', $logs, null, true);
        }

        return $errors;
    }

    public function checkRemisesGlobales($echo = false, $create_avoir = false)
    {
        if ($this->isLoaded()) {
            if ($echo) {
                echo BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id . ' - ' . $this->getRef() . ': ';
            }
            $total_rg = round($this->getTotalRemisesGlobalesAmount(), 2);
            $remises_infos = $this->getRemisesInfos();
            $total_rg_lines = round($remises_infos['remises_globales_amount_ttc'], 2);

            if ($total_rg != $total_rg_lines) {
                if ($echo) {
                    echo '<span class="danger">DIFF: Total RG: ' . $total_rg . ' - RG lignes: ' . $total_rg_lines . '</span>';

                    $rtp = 0;

                    if ($this->object_name === 'Bimp_Facture') {
                        $rtp = $this->getRemainToPay();

                        if (($total_rg - $total_rg_lines) > $rtp) {
                            echo '<span class="warning">ATTENTION: différence &gt; reste à payer</span>';
                        }
                    }
                    // Création de l\'avoir correctif: 
                    if ($create_avoir && $this->object_name == 'Bimp_Facture') {
                        $diff = $total_rg - $total_rg_lines;

                        if ($diff > 0 && $diff <= $rtp) {
                            global $user;
                            echo '<br/>Création de l\'avoir: ';

                            // Calcul du montant des lignes de l'avoir:
                            $total_lines = 0;
                            $total_lines_product = 0;
                            $total_lines_service = 0;
                            $lines = $this->getLines('not_text');

                            foreach ($lines as $line) {
                                if ($line->isRemisable()) {
                                    $line_total = (float) $line->getTotalTtcWithoutRemises();
                                    $total_lines += $line_total;
                                    $prod = $line->getProduct();
                                    if (BimpObject::objectLoaded($prod)) {
                                        if ($prod->isTypeProduct()) {
                                            $total_lines_product += $line_total;
                                        } else {
                                            $total_lines_service += $line_total;
                                        }
                                    } else {
                                        if (!(int) $this->db->getValue('facturedet', 'product_type', 'rowid = ' . (int) $line->getData('id_line'))) {
                                            $total_lines_product += $line_total;
                                        } else {
                                            $total_lines_service += $line_total;
                                        }
                                    }
                                }
                            }

                            $lines_rate = ($diff / $total_lines);
                            $product_amount = $total_lines_product * $lines_rate;
                            $service_amount = $total_lines_service * $lines_rate;
                            $total_amount = ($product_amount + $service_amount);

                            if ($total_amount > ($diff + 0.01) || $total_amount < ($diff - 0.01)) {
                                echo BimpRender::renderAlerts('Montants des lignes incorrect - produits: ' . $product_amount . ' - services: ' . $service_amount . ' - total: ' . $total_amount . ' - Attendu: ' . $diff);
                            } else {
                                $errors = array();
                                $warnings = array();

                                // Création avoir: 
                                $avoir = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                                            'fk_facture_source' => $this->id,
                                            'type'              => 0,
                                            'fk_soc'            => (int) $this->getData('fk_soc'),
                                            'entrepot'          => (int) $this->getData('entrepot'),
                                            'contact_id'        => (int) $this->getData('contact_id'),
                                            'fk_account'        => (int) $this->getData('fk_account'),
                                            'ef_type'           => ($this->getData('ef_type') ? $this->getData('ef_type') : 'S'),
                                            'datef'             => date('Y-m-d'),
                                            'libelle'           => 'Régularisation facture ' . $this->getRef(),
                                            'relance_active'    => 0,
                                            'fk_cond_reglement' => 1,
                                            'fk_mode_reglement' => 4
                                                ), true, $errors, $warnings);

                                if (!BimpObject::objectLoaded($avoir)) {
                                    echo '<span class="danger">ECHEC</span>';
                                    if (count($errors)) {
                                        echo BimpRender::renderAlerts($errors);
                                    }
                                    if (count($warnings)) {
                                        echo BimpRender::renderAlerts($warnings, 'warning');
                                    }
                                } else {
                                    echo '<span class="success">Création avoir OK</span>';
                                    // Création des lignes: 
                                    $lines_errors = array();
                                    if ($product_amount) {
                                        $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                                        $new_line->validateArray(array(
                                            'id_obj'      => (int) $avoir->id,
                                            'type'        => ObjectLine::LINE_FREE,
                                            'remisable'   => 0,
                                            'pa_editable' => 0
                                        ));

                                        $new_line->qty = 1;
                                        $new_line->desc = 'Correction remise(s) globale(s) sur les produits';
                                        $new_line->pu_ht = $product_amount * -1;
                                        $new_line->tva_tx = 0;
                                        $new_line->pa_ht = $product_amount * -1;

                                        $line_warnings = array();
                                        $line_errors = $new_line->create($line_warnings, true);

                                        if (!empty($line_errors)) {
                                            $lines_errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne produits');
                                        } elseif ((int) $new_line->getData('id_line')) {
                                            $this->db->update('facturedet', array(
                                                'product_type' => 0
                                                    ), 'rowid = ' . (int) $new_line->getData('id_line'));
                                        }
                                    }

                                    if (empty($lines_errors) && $service_amount) {
                                        $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                                        $new_line->validateArray(array(
                                            'id_obj'      => (int) $avoir->id,
                                            'type'        => ObjectLine::LINE_FREE,
                                            'remisable'   => 0,
                                            'pa_editable' => 0
                                        ));

                                        $new_line->qty = 1;
                                        $new_line->desc = 'Correction remise(s) globale(s) sur les services';
                                        $new_line->pu_ht = $service_amount * -1;
                                        $new_line->tva_tx = 0;
                                        $new_line->pa_ht = $service_amount * -1;

                                        $line_warnings = array();
                                        $line_errors = $new_line->create($line_warnings, true);

                                        if (!empty($line_errors)) {
                                            $lines_errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne services');
                                        } elseif ((int) $new_line->getData('id_line')) {
                                            $this->db->update('facturedet', array(
                                                'product_type' => 1
                                                    ), 'rowid = ' . (int) $new_line->getData('id_line'));
                                        }
                                    }

                                    if (count($lines_errors)) {
                                        echo BimpRender::renderAlerts($lines_errors);
                                        echo '<span class="info">Avoir supprimé</span>';
                                        $avoir->delete($warnings, true);
                                    } else {
                                        echo ' - ';
                                        echo '<span class="success">Création des lignes OK</span>';
                                        setElementElement('facture', 'facture', $avoir->id, $this->id);
                                        if ($avoir->dol_object->validate($user, '', 0, 0) <= 0) {
                                            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir->dol_object), 'Echec de la validation de l\'avoir'), 'danger');
                                        } else {
                                            $avoir->fetch($avoir->id);
                                            // Conversion en remise: 
                                            $conv_errors = $avoir->convertToRemise();
                                            if ($conv_errors) {
                                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($conv_errors, 'ECHEC CONVERSION EN REMISE'));
                                            } else {
                                                echo ' - <span class="success">CONV REM OK</span>';

                                                // Application de la remise à la facture: 
                                                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                                                $discount = new DiscountAbsolute($this->db->db);
                                                $discount->fetch(0, $avoir->id);

                                                if (BimpObject::objectLoaded($discount)) {
                                                    if ($discount->link_to_invoice(0, $this->id) <= 0) {
                                                        echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'ECHEC UTILISATION REMISE'));
                                                    } else {
                                                        echo ' - <span class="success">UTILISATION REM OK</span>';
                                                    }
                                                }

                                                $this->checkIsPaid();
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            echo BimpRender::renderAlerts('PAS DE CREATION D\'AVOIR CAR DIFFERENCE NEGATIVE');
                        }
                    }
                } else {
                    BimpCore::addlog('Erreur Remises globales', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', $this, array(
                        'Total RG'        => $total_rg,
                        'Total RG lignes' => $total_rg_lines
                    ));
                }
            } else {
                if ($echo) {
                    echo '<span class="success">OK</span>';
                }
            }

            if ($echo) {
                echo '<br/>';
            }
        }
    }

    public function checkValidationSolvabilite($client, &$errors = array())
    {
        if ($this->isLoaded()) {
            $emails = BimpCore::getConf('bimpcomm_solvabilite_validation_emails', '');

            if ($emails) {
                if (BimpObject::objectLoaded($client) && is_a($client, 'Bimp_Societe')) {
                    if (!$client->isSolvable($this->object_name)) {
                        global $user;
                        if (!$user->rights->bimpcommercial->admin_recouvrement) {
                            $solv_label = Bimp_Societe::$solvabilites[(int) $client->getData('solvabilite_status')]['label'];
                            $errors[] = 'Vous n\'avez pas la possiblité de valider ' . $this->getLabel('this') . ' car le client est au statut "' . $solv_label . '"<br/>Un e-mail a été envoyé à un responsable pour validation de la commande';

                            $msg = 'Demande de validation d\'une commande dont le client est au statut "' . $solv_label . '"' . "\n\n";
                            $url = $this->getUrl();
                            $msg .= '<a href="' . $url . '">Commande ' . $this->getRef() . '</a>';
                            mailSyn2('Demande de validation de commande Client ' . $client->getData('code_client') . ' - ' . $client->getName(), $emails, '', $msg);
                            return 0;
                        }
                    }
                }
            }
        }

        return 1;
    }

    // post process: 

    public function onCreate(&$warnings = array())
    {
        return array();
    }

    public function onDelete(&$warnings = array())
    {
        return array();
    }

    public function onValidate(&$warnings = array())
    {
        return array();
    }

    public function onUnvalidate(&$warnings = array())
    {
        return array();
    }

    public function onChildSave($child)
    {
        if ($this->isLoaded()) {
            if (is_a($child, 'objectLine')) {
                $this->processRemisesGlobales();
            }
        }
        return array();
    }

    public function onChildDelete($child)
    {
        if ($this->isLoaded()) {
            if (is_a($child, 'objectLine')) {
                $this->processRemisesGlobales();
            }
        }
        return array();
    }

    // Actions:

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $infos = array();

        $success = BimpTools::ucfirst($this->getLabel('')) . ' validé';

        if ($this->isLabelFemale()) {
            $success .= 'e';
        }

        $success .= ' avec succès';
        $success_callback = 'bimp_reloadPage();';

        global $conf, $langs, $user;

        $result = $this->dol_object->valid($user);

        if ($result > 0) {
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $this->fetch($this->id);
                $this->dol_object->generateDocument($this->getModelPdf(), $langs);
            }
        } else {
            $obj_errors = BimpTools::getDolEventsMsgs(array('errors'));

            if (!count($obj_errors)) {
                $obj_errors[] = BimpTools::ucfirst($this->getLabel('the')) . ' ne peut pas être validé' . $this->e();
            }
            $errors[] = BimpTools::getMsgFromArray($obj_errors);
        }

        $obj_warnings = BimpTools::getDolEventsMsgs(array('warnings'));

        if (!empty($obj_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($obj_warnings);
        }

        $obj_infos = BimpTools::getDolEventsMsgs(array('mesgs'));
        if (!empty($obj_infos)) {
            $infos[] = BimpTools::getMsgFromArray($obj_infos);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionModify($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise au statut "Brouillon" effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!$this->can("edit")) {
            $errors[] = 'Vous n\'avez pas la permission d\'effectuer cette action';
        } elseif (!method_exists($this->dol_object, 'set_draft')) {
            $errors[] = 'Erreur: cette action n\'est pas possible';
        } else {
            global $user;
            if ($this->dol_object->set_draft($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la remise au statut "Brouillon"');
            } else {
                global $conf, $langs;

                if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                    $this->fetch($this->id);
                    if ($this->dol_object->generateDocument($this->getModelPdf(), $langs) <= 0) {
                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de la génération du document PDF');
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionAddContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ajout du contact effectué avec succès' . $success;

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['type']) || !(int) $data['type']) {
                $errors[] = 'Nature du contact absent';
            } else {
                switch ((int) $data['type']) {
                    case 1:
                        $id_contact = isset($data['id_contact']) ? (int) $data['id_contact'] : 0;
                        $type_contact = isset($data['tiers_type_contact']) ? (int) $data['tiers_type_contact'] : 0;
                        if (!$id_contact) {
                            $errors[] = 'Contact non spécifié';
                        }
                        if (!$type_contact && static::$external_contact_type_required) {
                            $errors[] = 'Type de contact non spécifié';
                        }

                        if (!count($errors)) {
                            if ($this->dol_object->add_contact($id_contact, $type_contact, 'external') <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                            }
                        }
                        break;

                    case 2:
                        $id_user = isset($data['id_user']) ? (int) $data['id_user'] : 0;
                        $type_contact = isset($data['user_type_contact']) ? (int) $data['user_type_contact'] : 0;
                        if (!$id_user) {
                            $errors[] = 'Utilisateur non spécifié';
                        }

                        if (!$type_contact && static::$internal_contact_type_required) {
                            $errors[] = 'Type de contact non spécifié';
                        }

                        $id_type_commercial = (int) $this->db->getValue('c_type_contact', 'rowid', 'source = \'internal\' AND element = \'' . $this->dol_object->element . '\' AND code = \'SALESREPFOLL\'');
                        if ($type_contact == $id_type_commercial && !$this->canEditCommercial()) {
                            $errors[] = 'Vous n\'avez pas la permission de changer le commercial ' . $this->getLabel('of_a');
                        }

                        if (!count($errors)) {
                            if ($this->dol_object->add_contact($id_user, $type_contact, 'internal') <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                            }
                        }
                        break;
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'contact_list_html' => $this->renderContactsList()
        );
    }

    public function actionRemoveContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Suppression du contact effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['id_contact']) || !(int) $data['id_contact']) {
                $errors[] = 'Contact à supprimer non spécifié';
            } else {
                $id_type_contact = (int) $this->db->getValue('element_contact', 'fk_c_type_contact', 'rowid = ' . $data['id_contact']);
                $id_type_commercial = (int) $this->db->getValue('c_type_contact', 'rowid', 'source = \'internal\' AND element = \'' . $this->dol_object->element . '\' AND code = \'SALESREPFOLL\'');
                if ($id_type_contact == $id_type_commercial && !$this->canEditCommercial()) {
                    $errors[] = 'Vous n\'avez pas la permission de changer le commercial ' . $this->getLabel('of_a');
                } else {
                    if ($this->dol_object->delete_contact((int) $data['id_contact']) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression du contact');
                    }
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'contact_list_html' => $this->renderContactsList()
        );
    }

    public function actionDuplicate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Copie effectuée avec succès';

        $errors = $this->duplicate($data, $warnings);

        $url = '';

        if (!count($errors)) {
            $url = $_SERVER['php_self'] . '?fc=' . $this->getController() . '&id=' . $this->id;
        }

        $success_callback = 'window.location = \'' . $url . '\'';

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionUseRemise($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise insérée avec succès';

        if (!isset($data['id_discount']) || !(int) $data['id_discount']) {
            $errors[] = 'Aucune remise sélectionnée';
        } else {
            $errors = $this->insertDiscount((int) $data['id_discount']);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddAcompte($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Acompte créé avec succès';

        $id_mode_paiement = isset($data['id_mode_paiement']) ? $data['id_mode_paiement'] : '';
        $id_bank_account = isset($data['bank_account']) ? (int) $data['bank_account'] : 0;
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;

        if (!$data['date']) {
            $errors[] = 'Date de paiement absent';
        }

        if ($data['id_mode_paiement'] == 'VIR') {
            BimpObject::loadClass('bimpcommercial', 'Bimp_Paiement');
            if (!Bimp_Paiement::canCreateVirement()) {
                $errors[] = 'Vous n\'avez pas la permission d\'enregistrer des paiements par virement';
            } elseif (!$id_bank_account) {
                $errors[] = "Le compte banqaire est obligatoire pour un virement bancaire";
            }
        }

        if (!$id_mode_paiement) {
            $errors[] = 'Mode de paiement absent';
        }
        if (!$amount) {
            $errors[] = 'Montant absent';
        }

        $use_caisse = false;

        if ((int) BimpCore::getConf('use_caisse_for_payments')) {
            if (isset($data['use_caisse']) && (int) $data['use_caisse']) {
                $use_caisse = true;
            }
        }

        $num_paiement = '';
        $nom_emetteur = '';
        $banque_emetteur = '';

        if (in_array($id_mode_paiement, array('CHQ', 'VIR'))) {
            $num_paiement = isset($data['num_paiement']) ? $data['num_paiement'] : '';
            $nom_emetteur = isset($data['nom_emetteur']) ? $data['nom_emetteur'] : '';
        }

        if ($id_mode_paiement === 'CHQ') {
            $banque_emetteur = isset($data['banque_emetteur']) ? $data['banque_emetteur'] : '';
        }

        if (!count($errors)) {
            $errors = $this->createAcompte($amount, $id_mode_paiement, $id_bank_account, $data['date'], $use_caisse, $num_paiement, $nom_emetteur, $banque_emetteur, $warnings);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionRemoveDiscount($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Retrait de la remise effectué avec succès';

        if (!isset($data['id_discount']) || !(int) $data['id_discount']) {
            $errors[] = 'Aucune remise à retirer spécifiée';
        } else {
            if (!class_exists('DiscountAbsolute')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
            }

            $discount = new DiscountAbsolute($this->db->db);
            if ($discount->fetch((int) $data['id_discount']) <= 0) {
                $errors[] = 'La remise d\'ID ' . $data['id_discount'] . ' n\'existe pas';
            } else {
                if ($discount->unlink_invoice() <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Echec du retrait de la remise');
                } elseif (is_a($this, 'Bimp_Facture')) {
                    $this->fetch($this->id);
                    $this->checkIsPaid();
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReleverFacturation($data, &$success)
    {
        $errors = array();

        global $langs;
        BimpTools::loadDolClass('societe');
        $societe = new Societe($this->db->db);
        $societe->fetch($this->getData('fk_soc'));
        $societe->borne_debut = $data['date_debut'];
        $societe->borne_fin = $data['date_fin'];

        if ($societe->generateDocument('invoiceStatement', $langs) > 0) {
            $success = "Relevé de facturation généré avec succès";
        } else {
            $errors[] = "Echec de la génération du relevé de facturation";
        }
        $callback = "window.open('" . DOL_URL_ROOT . "/document.php?modulepart=company&file=" . $societe->id . "%2FRelevé_facturation.pdf&entity=1', '_blank');";
        return [
            'success_callback' => $callback,
            'errors'           => $errors,
            'warnings'         => array()
        ];
    }

    // Overrides BimpObject:

    public function reset()
    {
        $this->resetLines();
        parent::reset();
    }

    public function checkObject($context = '', $field = '')
    {
//        if ($this->isLoaded() && $context === 'fetch') {
//            global $user;
//            if (BimpObject::objectLoaded($user) && $user->admin) {
//                $this->dol_object->update_price();
//            }
//        }

        parent::checkObject($context, $field);
    }

    public function validate()
    {
        if (static::$use_zone_vente_for_tva && $this->dol_field_exists('zone_vente')) {
            $zone = self::BC_ZONE_FR;
            if ((in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn')) || $this->getData('entrepot') == '164' || $this->getInitData('entrepot') == '164'
                    ) && (((int) $this->getData('fk_soc') !== (int) $this->getInitData('fk_soc')) || (int) $this->getData('entrepot') !== (int) $this->getInitData('entrepot'))) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $this->getData('fk_soc'));
                if (BimpObject::objectLoaded($soc)) {
                    $zone = $this->getZoneByCountry($soc);
                    if ($this->getData('zone_vente') != $zone) {
                        $this->set('zone_vente', $zone);
                        $this->addNote('Zone de vente changé en auto ' . $this->displayData('zone_vente', 'default', falsex, true));
                    }
                }
            }
        }

        return parent::validate();
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $origin = BimpTools::getValue('origin', '');
        $origin_id = BimpTools::getValue('origin_id', 0);
        $origin_object = null;
        if ($this->field_exists('fk_user_author')) {
            if (is_null($this->data['fk_user_author']) || !(int) $this->data['fk_user_author']) {
                global $user;
                if (BimpObject::objectLoaded($user)) {
                    $this->data['fk_user_author'] = (int) $user->id;
                }
            }
        }

        if ($origin && $origin_id) {
            $origin_object = self::getInstanceByType($origin, $origin_id);

            if (!BimpObject::objectLoaded($origin_object)) {
                return array('Elément d\'origine invalide');
            }

            if ($this->isDolObject()) {
                $this->dol_object->origin = $origin;
                $this->dol_object->origin_id = $origin_id;

                if ($this->object_name !== 'Bimp_FactureFourn') {
                    $this->dol_object->linked_objects[$this->dol_object->origin] = $origin_id;
                }
            }
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            switch ($this->object_name) {
                case 'Bimp_Propal':
                case 'Bimp_Facture':
                case 'Bimp_Commande':
                    // Billing2
                    $contacts_suivi = $this->dol_object->liste_contact(-1, 'external', 0, 'BILLING2');

                    if (count($contacts_suivi) == 0) {
                        // Get id of the default contact
                        global $db;
                        $id_client = $this->getAddContactIdClient();
                        if ($id_client > 0) {
                            $soc = new Societe($db);
                            $soc->fetch_optionals($id_client);
                            $contact_default = (int) $soc->array_options['options_contact_default'];

                            if (!count($errors) && $contact_default > 0) {
                                if ($this->dol_object->add_contact($contact_default, 'BILLING2', 'external') <= 0)
                                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                            }
                        }
                    }
                    break;
            }

            if (method_exists($this->dol_object, 'fetch_lines')) {
                $this->dol_object->fetch_lines();
            }
            $this->checkLines(); // Des lignes ont pu être créées via un trigger.

            if ($origin && $origin_id) {
                $warnings = BimpTools::merge_array($warnings, $this->createLinesFromOrigin($origin_object));
                if (is_a($origin_object, 'BimpComm') && static::$remise_globale_allowed && $origin_object::$remise_globale_allowed) {
                    $remises_globales = $origin_object->getRemisesGlobales();

                    if (!empty($remises_globales)) {
                        foreach ($remises_globales as $rg) {
                            $rem_data = $rg->getDataArray(false);
                            $rem_data['obj_typ'] = static::$element_name;
                            $rem_data['id_obj'] = (int) $this->id;

                            BimpObject::createBimpObject('bimpcommercial', 'RemiseGlobale', $rem_data, true, $warnings);
                        }
                        $this->processRemisesGlobales();
                    }
                }
            }

            $this->hydrateFromDolObject();
        }

        // Ajout des exterafileds du parent qui ne sont pas envoyé   
        if (!count($errors) && $origin_object && isset($origin_object->dol_object)) {
            $update = false;
            foreach ($origin_object->dol_object->array_options as $options_key => $value) {
                if (!isset($this->data[$options_key])) {
                    $options_key = str_replace("options_", "", $options_key);
                }

                if (isset($this->data[$options_key]) && !BimpTools::isSubmit($options_key)) {
                    $update = true;
                    $this->set($options_key, $value);
                }
            }
            if ($update) {
                $errors = $this->update();
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_zone = '';
        if ($this->dol_field_exists('zone_vente')) {
            $init_zone = (int) $this->getInitData('zone_vente');
        }

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if (static::$use_zone_vente_for_tva && $init_zone && $this->areLinesEditable()) {
                $cur_zone = (int) $this->getData('zone_vente');

                if ($cur_zone !== $init_zone && in_array($cur_zone, array(self::BC_ZONE_HORS_UE, self::BC_ZONE_UE))) {
                    $lines_errors = $this->removeLinesTvaTx();
                    if (count($lines_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la suppression des taux de TVA');
                    }
                }
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $lines = $this->getLines();
        $remisesGlobales = $this->getRemisesGlobales();


        // Suppression des demandes de validation liées à cet objet
        $valid_comm = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
        $type_de_piece = $valid_comm::getObjectClass($this);
        $filters = array(
            'type_de_piece' => (int) $type_de_piece,
            'id_piece'      => (int) $this->id
        );
        $demandes_a_suppr = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            foreach ($lines as $line) {
                $line_pos = $line->getData('position');
                $line_warnings = array();
                $line->bimp_line_only = true;
                $line_errors = $line->delete($line_warnings, true);

                $line_errors = BimpTools::merge_array($line_warnings, $line_errors);
                if (count($line_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs lors de la suppression de la ligne n°' . $line_pos);
                }
            }

            foreach ($remisesGlobales as $rg) {
                $rg_warnings = array();
                $rg_errors = $rg->delete($rg_warnings, true);

                if (count($rg_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($rg_errors, 'Echec de la suppression de la remise globale #' . $rg->id);
                }
            }

            foreach ($demandes_a_suppr as $d) {
                $dem_warnings = array();
                $dem_errors = $d->delete($dem_warnings, true);

                if (count($dem_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($dem_errors, 'Echec de la suppression de la demande de validation #' . $rg->id);
                }
            }
        }

        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        parent::onSave($errors, $warnings);

        $this->processRemisesGlobales();
    }

    // Méthodes statiques: 

    public static function checkAllObjectLine($id_product, &$sortie = '', $nbMax = 10)
    {
        global $db;
        $tabInfo = $errors = array();
        $sortie = '';

        $tabInfo[] = array('fk_propal', 'llx_propaldet', array('llx_bimp_propal_line', 'llx_bs_sav_propal_line'), 'Bimp_Propal');
        $tabInfo[] = array('fk_commande', 'llx_commandedet', 'llx_bimp_commande_line', 'Bimp_Commande');
        $tabInfo[] = array('fk_facture', 'llx_facturedet', 'llx_bimp_facture_line', 'Bimp_Facture');
        $tabInfo[] = array('fk_commande', 'llx_commande_fournisseurdet', 'llx_bimp_commande_fourn_line', 'Bimp_CommandeFourn');
        $tabInfo[] = array('fk_facture_fourn', 'llx_facture_fourn_det', 'llx_bimp_facture_fourn_line', 'Bimp_FactureFourn');

        foreach ($tabInfo as $info) {
            $i = 0;
            if (!is_array($info[2]))
                $info[2] = array($info[2]);
            $where = array();
            foreach ($info[2] as $table)
                $where[] = 'rowid NOT IN (SELECT id_line FROM ' . $table . ')';
            $req = 'SELECT DISTINCT(`' . $info[0] . '`) as id FROM `' . $info[1] . '` WHERE ' . implode(' AND ', $where) . ' ';

            if ($id_product > 0)
                $req .= ' AND fk_product = ' . $id_product;

            $sql = $db->query($req);
            $tot = $db->num_rows($sql);
            $sql = $db->query($req . ' LIMIT 0,' . $nbMax);
            while ($ln = $db->fetch_object($sql)) {
                $comm = BimpCache::getBimpObjectInstance('bimpcommercial', $info[3], $ln->id);
                if ($comm->isLoaded()) {
                    $errors = BimpTools::merge_array($errors, $comm->checkLines());
                    $i++;
                }

                BimpCache::$cache = array();
            }
            $sortie .= '<br/>fin ' . $i . ' / ' . $tot . ' corrections de ' . $info[1];
        }
        return $errors;
    }

    public function renderDemandesList()
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
            $objectName = ValidComm::getObjectClass($this);
            if ($objectName != -2) {
                BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
                $demande = BimpObject::getInstance('bimpvalidateorder', 'DemandeValidComm');
                $list = new BC_ListTable($demande);
                $list->addFieldFilterValue('type_de_piece', $objectName);
                $list->addFieldFilterValue('id_piece', (int) $this->id);

                return $list->renderHtml();
            } else {
                return '';
            }
        }

        return BimpRender::renderAlerts('Impossible d\'afficher la liste des demande de validation (ID ' . $this->getLabel('of_the') . ' absent)');
    }
}

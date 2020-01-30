<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BimpComm extends BimpDolObject
{

    const BC_ZONE_FR = 1;
    const BC_ZONE_UE = 2;
    const BC_ZONE_HORS_UE = 3;
    const BC_ZONE_UE_SANS_TVA = 4;

    public static $email_type = '';
    public static $element_name = '';
    public static $mail_event_code = '';
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
        0   => ['label' => 'Non traitée en comptabilitée', 'classes' => ['danger'], 'icon' => 'times'],
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

    // Getters booléens: 

    public function isDeletable($force_delete = false)
    {
        if ((int) $this->getData('fk_statut') === 0) {
            return 1;
        }
        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
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
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        // Vérif des lignes: 
        $lines = $this->getLines('not_text');
        if (!count($lines) && !is_a($this, 'BS_SavPropal')) {
            $errors[] = 'Aucune ligne ajoutée  ' . $this->getLabel('to') . ' (Hors text)';
            return 0;
        }

        if (!BimpCore::getConf("NOT_USE_ENTREPOT") && !(int) $this->getData('entrepot')) {
            $errors[] = 'Aucun entrepôt associé';
        }

        if (!count($errors)) {
            if (in_array($this->object_name, array('Bimp_Propal', 'Bimp_Commande', 'Bimp_Facture'))) {
                global $user;
                $client = $this->getChildObject('client');
                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Client absent';
                } else {
                    // Vérif de l'encours client: 
                    $actuel = $client->dol_object->get_OutstandingBill();

                    if ($this->object_name === 'Bimp_Facture') {
                        $actuel -= $this->dol_object->total_ttc;
                    }
                    $max = $client->dol_object->outstanding_limit;
                    $futur = $actuel + $this->dol_object->total_ttc;

                    if ($max > 0 && $this->dol_object->total_ttc > 0 && $max < $futur) {
                        $msg = "Montant encours client dépassé. Maximum : " . price($max) . " €. Actuel :" . price($actuel) . " €. Necessaire : " . price($futur) . " €.";
                        $errors[] = $msg;
                    }

                    // Vérif commercial suivi: 
                    $tabConatact = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
                    if (count($tabConatact) < 1) {
                        $tabComm = $client->dol_object->getSalesRepresentatives($user);
                        if (count($tabComm) > 0) {
                            $this->dol_object->add_contact($tabComm[0]['id'], 'SALESREPFOLL', 'internal');
                        } elseif ((int) BimpCore::getConf('BIMPCOMM_user_as_default_commercial', 1)) {
                            $this->dol_object->add_contact($user->id, 'SALESREPFOLL', 'internal');
                        } else {
                            $errors[] = 'Pas de Commercial Suivi';
                        }
                    }

                    // Vérif contact signataire: 
                    $tabConatact = $this->dol_object->getIdContact('internal', 'SALESREPSIGN');
                    if (count($tabConatact) < 1) {
                        $this->dol_object->add_contact($user->id, 'SALESREPSIGN', 'internal');
                    }

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
        return !BimpCore::getConf("NOT_USE_ENTREPOT");
    }

    // Getters array: 

    public function getModelsPdfArray()
    {
        return array();
    }

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

    public function getEmailModelsArray()
    {
        if (!static::$email_type) {
            return array();
        }

        return self::getEmailTemplatesArray(static::$email_type, true);
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

    public function getMailsToArray()
    {
        global $user, $langs;

        $client = $this->getChildObject('client');

        $emails = array(
            ""           => "",
            $user->email => $user->getFullName($langs) . " (" . $user->email . ")"
        );

        if ($this->isLoaded()) {
            $contacts = $this->dol_object->liste_contact(-1, 'external');
            foreach ($contacts as $item) {
                if (!isset($emails[(int) $item['id']])) {
                    $emails[(int) $item['id']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
                }
            }
        }

        if (BimpObject::objectLoaded($client)) {
            $client_emails = self::getSocieteEmails($client->dol_object);
            if (is_array($client_emails)) {
                foreach ($client_emails as $value => $label) {
                    if (!isset($emails[$value])) {
                        $emails[$value] = $label;
                    }
                }
            }
        }

        if ($this->isLoaded()) {
            $contacts = $this->dol_object->liste_contact(-1, 'internal');
            foreach ($contacts as $item) {
                if (!isset($emails[$item['email']])) {
                    $emails[$item['email']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
                }
            }
        }

        $emails['custom'] = 'Autre';

        return $emails;
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
            'label'   => 'Message logistique ',
            'icon'    => 'far fa-paper-plane',
            'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_GROUP, "fk_group_dest" => $note::BN_GROUPID_LOGISTIQUE, "content" => ""), array('form_name' => 'rep'))
        );

        $buttons[] = array(
            'label'   => 'Message facturation ',
            'icon'    => 'far fa-paper-plane',
            'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_GROUP, "fk_group_dest" => $note::BN_GROUPID_FACT, "content" => "Bonjour, merci de bien vouloir facturer cette commande."), array('form_name' => 'rep'))
        );

        $buttons[] = array(
            'label'   => 'Relevé facturation client',
            'icon'    => 'fas fa-ticket',
            'onclick' => $this->getJsActionOnclick('releverFacturation', array(), array(
                'form_name' => 'releverFacturation'
            ))
        );

        return $buttons;
    }

    public function getDirOutput()
    {
        return '';
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

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array())
    {
        switch ($field_name) {
            case 'id_product':
                $line = $this->getLineInstance();
                $alias = $line::$parent_comm_type . '_det';
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => $line::$dol_line_table,
                    'on'    => $alias . '.' . $line::$dol_line_parent_field . ' = a.' . $this->getPrimary()
                );
                $filters[$alias . '.fk_product'] = array(
                    'in' => $values
                );
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
                    $sql = '(typecont.element = \'' . static::$dol_module . '\' AND typecont.source = \'internal\'';
                    $sql .= ' AND typecont.code = \'SALESREPFOLL\' AND elemcont.fk_socpeople IN (' . implode(',', $ids) . '))';
                }

                if ($empty) {
                    $sql .= ($sql ? ' OR ' : '');
                    $sql = '(SELECT COUNT(ec2.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc2 ON tc2.rowid = ec2.fk_c_type_contact';
                    $sql .= ' WHERE tc2.element = \'' . static::$dol_module . '\'';
                    $sql .= ' AND tc2.source = \'internal\'';
                    $sql .= ' AND tc2.code = \'SALESREPFOLL\'';
                    $sql .= ' AND ec2.element_id = a.rowid) = 0';
                }

                if ($sql) {
                    $filters['commercial_custom'] = array(
                        'custom' => $sql
                    );
                }

                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors);
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

    public function getEmailUsersFromArray()
    {
        global $user, $langs, $conf;

        $emails = array();

        // User connecté: 

        if (!empty($user->email)) {
            $emails[$user->email] = $user->getFullName($langs) . ' (' . $user->email . ')';
        }

        if (!$user->admin)
            return $emails;

        if (!empty($user->email_aliases)) {
            foreach (explode(',', $user->email_aliases) as $alias) {
                $alias = trim($alias);
                if ($alias) {
                    $alias = str_replace('/</', '', $alias);
                    $alias = str_replace('/>/', '', $alias);
                    if (!isset($emails[$alias])) {
                        $emails[$alias] = $user->getFullName($langs) . ' (' . $alias . ')';
                    }
                }
            }
        }

        // Société: 

        if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL)) {
            $emails[$conf->global->MAIN_INFO_SOCIETE_MAIL] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $conf->global->MAIN_INFO_SOCIETE_MAIL . ')';
        }

        if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES)) {
            foreach (explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES) as $alias) {
                $alias = trim($alias);
                if ($alias) {
                    $alias = str_replace('/</', '', $alias);
                    $alias = str_replace('/>/', '', $alias);
                    if (!isset($emails[$alias])) {
                        $emails[$alias] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $alias . ')';
                    }
                }
            }
        }

        // Contacts pièce: 

        if ($this->isLoaded()) {
            $c_user = new User($this->db->db);
            $contacts = $this->dol_object->liste_contact(-1, 'internal');
            foreach ($contacts as $item) {
                $c_user->fetch($item['id']);
                if (BimpObject::objectLoaded($c_user)) {
                    if (!empty($c_user->email) && !isset($emails[$c_user->email])) {
                        $emails[$c_user->email] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $c_user->email . ')';
                    }

                    if (!empty($c_user->email_aliases)) {
                        foreach (explode(',', $c_user->email_aliases) as $alias) {
                            $alias = trim($alias);
                            if ($alias) {
                                $alias = str_replace('/</', '', $alias);
                                $alias = str_replace('/>/', '', $alias);
                                if (!isset($emails[$alias])) {
                                    $emails[$alias] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $alias . ')';
                                }
                            }
                        }
                    }
                }
            }
        }

        return $emails;
    }

    public function getDocumentFileId()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $ref = dol_sanitizeFileName($this->getRef());
        $where = '`parent_module` = \'' . $this->module . '\' AND `parent_object_name` = \'' . $this->object_name . '\' AND `id_parent` = ' . (int) $this->id;
        $where .= ' AND `file_name` = \'' . $ref . '\' AND `file_ext` = \'pdf\'';

        return (int) $this->db->getValue('bimpcore_file', 'id', $where);
    }

    public function getJoinFilesValues()
    {
        $values = BimpTools::getValue('fields/join_files', array());

        $id_main_pdf_file = (int) $this->getDocumentFileId();

        if (!in_array($id_main_pdf_file, $values)) {
            $values[] = $id_main_pdf_file;
        }

        $list = $this->getAllFiles();
        $idSepa = 0;
        $idSepaSigne = 0;
        foreach ($list as $id => $elem)
            if (stripos($elem, "sepa")) {
                $idSepa = $id;
                if (stripos($elem, "signe"))
                    $idSepaSigne = $id;
            }


        if ($idSepa > 0 && $idSepaSigne < 1)
            $values[] = $idSepa;




        return $values;
    }

    public function getAllFiles($withLink = true)
    {
        $objects = $this->getBimpObjectsLinked();
        $list = $this->getFilesArray(0);
        if ($withLink) {
            foreach ($objects as $object) {
                $list = $list + $object->getFilesArray(0);
            }
        }
        return $list;
    }

    public function getEmailTopicByModel()
    {
        $topic = '';
        $id_model = (int) BimpTools::getPostFieldValue('id_model', 0);

        if ($id_model) {
            $template = self::getEmailTemplateData($id_model);

            if (!is_null($template)) {
                if ($this->isLoaded()) {
                    if (!class_exists('FormMail')) {
                        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                    }

                    global $langs;

                    $formMail = new FormMail($this->db->db);
                    $formMail->setSubstitFromObject($this->dol_object, $langs);
                    $formMail->substit['__LINES__'] = '';
                    $topic = $template['topic'];
                    $topic = make_substitutions($topic, $formMail->substit);

                    $soc = $this->getChildObject("client");
                    if (isset($soc) && is_object($soc)) {
                        $formMail->setSubstitFromObject($soc->dol_object, $langs);
                        $topic = make_substitutions($topic, $formMail->substit);
                    }
                    $soc = $this->getChildObject("societe");
                    if (isset($soc) && is_object($soc)) {
                        $formMail->setSubstitFromObject($soc->dol_object, $langs);
                        $topic = make_substitutions($topic, $formMail->substit);
                    }
                }
            }
        }

        return $topic;
    }

    public function getEmailContentByModel()
    {
        $content = '';
        $id_model = (int) BimpTools::getPostFieldValue('id_model', 0);

        if ($id_model) {
            $template = self::getEmailTemplateData($id_model);

            if (!is_null($template)) {
                if ($this->isLoaded()) {
                    if (!class_exists('FormMail')) {
                        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                    }

                    global $langs;

                    $formMail = new FormMail($this->db->db);
                    $formMail->setSubstitFromObject($this->dol_object, $langs);

                    if (isset($template['content_lines']) && $template['content_lines']) {
                        $lines = '';
                        foreach ($formMail->substit_lines as $substit_line) {
                            $lines .= make_substitutions($template['content_lines'], $substit_line) . "\n";
                        }
                        $formMail->substit['__LINES__'] = $lines;
                    } else {
                        $formMail->substit['__LINES__'] = '';
                    }

                    $content = str_replace('\n', "\n", $template['content']);

                    if (dol_textishtml($content) && !dol_textishtml($formMail->substit['__SIGNATURE__'])) {
                        $formMail->substit['__SIGNATURE__'] = dol_nl2br($formMail->substit['__SIGNATURE__']);
                    } else if (!dol_textishtml($content) && dol_textishtml($this->substit['__SIGNATURE__'])) {
                        $content = dol_nl2br($content);
                    }

                    $content = make_substitutions($content, $formMail->substit);
                    $content = preg_replace("/^(<br>)+/", "", $content);
                    $content = preg_replace("/^\n+/", "", $content);
                }
            }
        }

        return $content;
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

            if ($infos['remises_lines_amount_ttc']) {
                $infos['remises_lines_percent'] = ($infos['remises_lines_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            if ($infos['remises_globales_amount_ttc']) {
                $infos['remises_globales_percent'] = ($infos['remises_globales_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            if ($infos['ext_remises_globales_amount_ttc']) {
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
        if (!$this->isLoaded()) {
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
                    if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn')))
                        return (int) $soc->dol_object->cond_reglement_supplier_id;
                    else
                        return (int) $soc->dol_object->cond_reglement_id;
                }
            }
            return 0;
        }

        if (isset($this->data['fk_cond_reglement'])) {
            return (int) $this->data['fk_cond_reglement']; // pas getData() sinon boucle infinie (getCondReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return 0;
    }

    public function getModeReglementBySociete()
    {
        if (!$this->isLoaded()) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc', 0);
            if (!$id_soc && $this->getData('fk_soc') > 0) {
                $id_soc = $this->getData('fk_soc');
            }
            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn')))
                        return (int) $soc->dol_object->mode_reglement_supplier_id;
                    else
                        return (int) $soc->dol_object->mode_reglement_id;
                }
            }
        }

        if (isset($this->data['fk_mode_reglement']) && (int) $this->data['fk_mode_reglement']) {
            return (int) $this->data['fk_mode_reglement']; // pas getData() sinon boucle infinie (getModeReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return BimpCore::getConf('default_id_mode_paiement');
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

    public function getDefaultMailTo()
    {
        return array();
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
            return BimpCache::getBimpObjectObjects('bimpcommercial', 'RemiseGlobale', array(
                        'obj_type' => static::$element_name,
                        'id_obj'   => (int) $this->id
            ));
        }

        return array();
    }

    public function getBimpObjectsLinked()
    {
        $objects = array();
        if ($this->isLoaded()) {
            if ($this->isDolObject()) {
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    $id = $item['id_object'];
                    $class = "";
                    $label = "";
                    $module = "bimpcommercial";
                    switch ($item['type']) {
                        case 'propal':
                            $class = "Bimp_Propal";
                            break;
                        case 'facture':
                            $class = "Bimp_Facture";
                            break;
                        case 'commande':
                            $class = "Bimp_Commande";
                            break;
                        case 'order_supplier':
                            $class = "Bimp_CommandeFourn";
                            break;
                        case 'invoice_supplier':
                            $class = "Bimp_FactureFourn";
                            break;
                        default:
                            break;
                    }
                    if ($class != "") {
                        $objT = BimpCache::getBimpObjectInstance($module, $class, $id);
//                        if ($objT->isLoaded()) { // Ne jamais faire ça: BimpCache renvoie null si l'objet n'existe pas => erreur fatale. 
                        if (BimpObject::objectLoaded($objT)) {
                            $objects[] = $objT;
                        }
                    }
                }
            }

            $client = $this->getChildObject('client');

            if ($client->isLoaded()) {
                $objects[] = $client;
            }
        }


        return $objects;
    }

    // Getters - Overrides BimpObject

    public function getName($with_generic = true)
    {
        if ($this->isLoaded()) {
            $name = (string) $this->getData('libelle');
            if ($name) {
                return $name;
            }

            if ($with_generic) {
                return BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
            }
        }

        return '';
    }

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            $dir_output = $this->getDirOutput();
            if ($dir_output) {
                return $dir_output . '/' . dol_sanitizeFileName($this->getRef()) . '/';
            }
        }

        return '';
    }

    public function getFileUrl($file_name)
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                if (isset(static::$files_module_part)) {
                    $module_part = static::$files_module_part;
                } else {
                    $module_part = static::$dol_module;
                }
                return DOL_URL_ROOT . '/document.php?modulepart=' . $module_part . '&file=' . urlencode($this->getRef()) . '/' . urlencode($file_name);
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
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayMoneyValue((float) $discounts, 'EUR') . '</td>';
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
                            $label = 'Appliquer un avoir disponible';
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
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_lines_amount_ht'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_lines_amount_ttc'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['remises_lines_percent'], 4) . ' %</td>';
                    $html .= '</tr>';
                }

                if ($infos['remises_globales_amount_ttc']) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Remises globales: </td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_globales_amount_ht'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_globales_amount_ttc'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['remises_globales_percent'], 4) . ' %</td>';
                    $html .= '</tr>';
                }

                if ($infos['ext_remises_globales_amount_ttc']) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Parts de remises globales externes: </td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['ext_remises_globales_amount_ht'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['ext_remises_globales_amount_ttc'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['ext_remises_globales_percent'], 4) . ' %</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';

                $html .= '<tfoot>';
                $html .= '<td style="font-weight: bold;width: 160px;">Total Remises: </td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_total_amount_ht'], 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_total_amount_ttc'], 'EUR') . '</td>';
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

        return BimpTools::displayMoneyValue($total);
    }

    public function displayPDFButton($display_generate = true, $with_ref = true, $btn_label = '')
    {
        $html = '';
        $ref = dol_sanitizeFileName($this->getRef());

        if ($ref) {
            $file_url = $this->getFileUrl($ref . '.pdf');
            if ($file_url) {
                $onclick = 'window.open(\'' . $file_url . '\');';
                $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $html .= '<i class="fas fa5-file-pdf ' . (($with_ref || $btn_label) ? 'iconLeft' : '') . '"></i>';
                if ($with_ref) {
                    $html .= $ref . '.pdf';
                } elseif ($btn_label) {
                    $html .= $btn_label;
                }
                $html .= '</button>';

                if ($display_generate) {
                    $onclick = 'toggleElementDisplay($(this).parent().find(\'.' . static::$dol_module . 'PdfGenerateContainer\'), $(this));';
                    $html .= '<span class="btn btn-light-default open-close action-open bs-popover" onclick="' . $onclick . '"';
                    $html .= BimpRender::renderPopoverData('Re-générer le document', 'top', 'false');
                    $html .= '>';
                    $html .= BimpRender::renderIcon('fas_sync');
                    $html .= '</span>';
                }
            }

            if ($display_generate) {
                $models = $this->getModelsPdfArray();
                if (count($models)) {
                    $html .= '<div class="' . static::$dol_module . 'PdfGenerateContainer" style="' . ($file_url ? 'margin-top: 15px; display: none;' : '') . '">';
                    $html .= BimpInput::renderInput('select', static::$dol_module . '_model_pdf', $this->getModelPdf(), array(
                                'options' => $models
                    ));
                    $onclick = 'var model = $(this).parent(\'.' . static::$dol_module . 'PdfGenerateContainer\').find(\'[name=' . static::$dol_module . '_model_pdf]\').val();setObjectAction($(this), ' . $this->getJsObjectData() . ', \'generatePdf\', {model: model}, null, null, null, null);';
                    $html .= '<button type="button" onclick="' . $onclick . '" class="btn btn-default">';
                    $html .= '<i class="fas fa5-sync iconLeft"></i>Générer';
                    $html .= '</button>';
                    $html .= '</div>';
                }
            }
        }

        return $html;
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
            BimpTools::loadDolClass('contact');
            $user = new User($this->db->db);
            if ($user->fetch((int) $id) > 0) {
                global $modeCSV, $langs;
                if ($modeCSV)
                    return $user->getFullName($langs);
                else
                    return $user->getNomUrl(1) . BimpRender::renderObjectIcons($user);
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
                        $socTemp = new Societe($this->db->db);
                        $socTemp->fetch($contact['socid']);
                        $html .= $socTemp->getNomUrl(1);
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

    // Rendus HTML: 

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
                $html .= '<td><span class="danger">-' . BimpTools::displayMoneyValue($remises_crt, '') . '</span></td>';
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
                $html .= '<td>' . BimpTools::displayMoneyValue($total_pv, '') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_pa, '') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_marge, '') . ' (' . BimpTools::displayFloatValue($tx, 4) . ' %)</td>';
                $html .= '</tr>';
            }
        }
        return $html;
    }

    public function renderExtraFile()
    {
        $html = "";
        $objects = $this->getBimpObjectsLinked();
        foreach ($objects as $obj)
            $html .= $this->renderListFileForObject($obj);
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
            $list = array_merge($list_int, $list_ext);
        }

        if (count($list)) {
            global $conf;
            BimpTools::loadDolClass('societe');
            BimpTools::loadDolClass('contact');

            $soc = new Societe($this->db->db);
            $user = new User($this->db->db);
            $contact = new Contact($this->db->db);

            $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';

            foreach ($list as $item) {
                $html .= '<tr>';
                switch ($item['source']) {
                    case 'internal':
                        $user->id = $item['id'];
                        $user->lastname = $item['lastname'];
                        $user->firstname = $item['firstname'];
                        $user->photo = $item['photo'];
                        $user->login = $item['login'];

                        $html .= '<td>Utilisateur</td>';
                        $html .= '<td>' . $conf->global->MAIN_INFO_SOCIETE_NOM . '</td>';
                        $html .= '<td>' . $user->getNomUrl(-1) . BimpRender::renderObjectIcons($user) . '</td>';
                        break;

                    case 'external':
                        $soc->fetch((int) $item['socid']);
                        $contact->id = $item['id'];
                        $contact->lastname = $item['lastname'];
                        $contact->firstname = $item['firstname'];

                        $html .= '<td>Contact tiers</td>';
                        $html .= '<td>' . $soc->getNomUrl(1) . BimpRender::renderObjectIcons($soc) . '</td>';
                        $html .= '<td>' . $contact->getNomUrl(1) . BimpRender::renderObjectIcons($contact) . '</td>';
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

    public function renderMailToInputs($input_name)
    {
        $emails = $this->getMailsToArray();

        $html = '';

        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array(
                    'options'     => $emails,
                    'extra_class' => 'emails_select principal'
        ));


        $html .= '<p class="inputHelp selectMailHelp">';
        $html .= 'Sélectionnez une adresse e-mail puis cliquez sur "Ajouter"';
        $html .= '</p>';

        $html .= '<div class="mail_custom_value" style="display: none; margin-top: 10px">';
        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
        $html .= '<p class="inputHelp">Entrez une adresse e-mail valide puis cliquez sur "Ajouter"</p>';
        $html .= '</div>';

        return $html;
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
            $rows = $this->db->getRows($bimp_line->getTable(), '`id_obj` = ' . (int) $this->id, null, 'array', array('id', 'id_line', 'position', 'remise'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $bimp_lines[(int) $r['id_line']] = array(
                        'id'       => (int) $r['id'],
                        'position' => (int) $r['position'],
                        'remise'   => (float) $r['remise']
                    );
                }
            }

            // Suppression des lignes absentes de l'objet dolibarr:
            foreach ($bimp_lines as $id_dol_line => $data) {
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
            }

            // Création des lignes absentes de l'objet bimp: 
            $bimp_line->reset();
            $i = 0;
            foreach ($dol_lines as $id_dol_line => $dol_line) {
                $i++;
                if (!array_key_exists($id_dol_line, $bimp_lines) && method_exists($bimp_line, 'createFromDolLine')) {
                    $objectLine = BimpObject::getInstance($bimp_line->module, $bimp_line->object_name);
                    $objectLine->parent = $this;
                    $line_errors = $objectLine->createFromDolLine((int) $this->id, $dol_line, $warnings);
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

        $validate_errors = $this->validate();
        if (count($validate_errors)) {
            return array(BimpTools::getMsgFromArray($validate_errors), BimpTools::ucfirst($this->getLabel('this')) . ' comporte des erreurs. Copie impossible');
        }

        global $user, $conf, $hookmanager;

        $new_object = clone $this;
        $new_object->id = null;
        $new_object->id = 0;
        $new_object->set('id', 0);
        $new_object->set('ref', '');
        $new_object->set('fk_statut', 0);

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

        $new_object->dol_object->user_author = $user->id;
        $new_object->dol_object->user_valid = '';

        $copy_errors = $new_object->create($warnings, $force_create);

        if (count($copy_errors)) {
            $errors[] = BimpTools::getMsgFromArray($copy_errors, 'Echec de la copie ' . $this->getLabel('of_the'));
        } else {
            // Copie des contacts: 
            $new_object->copyContactsFromOrigin($this, $errors);

            // Copie des lignes: 
            $lines_errors = $new_object->createLinesFromOrigin($this, array(
                'is_clone' => true
            ));

            if (count($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la copie des lignes ' . $this->getLabel('of_the'));
            }

            // Copie des remises globales: 
            if (static::$remise_globale_allowed) {
                $new_object->copyRemisesGlobalesFromOrigin($this, $errors);
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

            $new_line->validateArray($data);

            $new_line->desc = $line->desc;
            $new_line->tva_tx = $line->tva_tx;
            $new_line->id_product = $line->id_product;
            $new_line->qty = $line->qty;
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
            } elseif ($params['is_review'] && ((int) $line->getData('linked_id_object') || (string) $line->getData('linked_object_name'))) {
                // On  désassocie l'objet lié de l'anicienne ligne dans le cas d'une révision: 
                $this->db->update($line->getTable(), array(
                    'linked_id_object'   => 0,
                    'linked_object_name' => ''
                        ), '`' . $line->getPrimary() . '` = ' . (int) $line->id);
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
            $errors = array_merge($new_line->copyRemisesFromOrigin($line, $params['inverse_prices'], $params['copy_remises_globales']));
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

    public function copyContactsFromOrigin($origin, &$errors = array())
    {
        if ($this->isLoaded() && BimpObject::objectLoaded($origin) && is_a($origin, 'BimpComm')) {
            BimpTools::resetDolObjectErrors($this->dol_object);
            if ($this->dol_object->copy_linked_contact($origin->dol_object, 'internal') < 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la copie des contacts internes');
            }
            if ((int) $this->getData('fk_soc') === (int) $origin->getData('fk_soc')) {
                BimpTools::resetDolObjectErrors($this->dol_object);
                if ($this->dol_object->copy_linked_contact($origin->dol_object, 'external') < 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la copie des contacts externes');
                }
            }
        }
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

                        $this->processRemisesGlobales();
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
                        $errors = array_merge($errors, $caisse->addPaiement($payement, $factureA->id));
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
                                    echo 'ici';
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
                        $errors = array_merge($errors, $line->setRemiseGlobalePart($rg, $lines_rate));
                    }
                }
            }

            foreach ($lines as $line) {
                $line->checkRemisesGlobales();
            }
        }

        return $errors;
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
    }

    public function onChildDelete($child)
    {
        if ($this->isLoaded()) {
            if (is_a($child, 'objectLine')) {
                $this->processRemisesGlobales();
            }
        }
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
                if ($this->dol_object->delete_contact((int) $data['id_contact']) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression du contact');
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

    public function actionSendEMail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Email envoyé avec succès';

        if (!isset($data['from']) || !(string) $data['from']) {
            $errors[] = 'Emetteur absent';
        } elseif (!BimpValidate::isEmail($data['from'])) {
            $errors[] = 'L\'adresse email de l\'émetteur (' . $data['from'] . ') n\'est pas valide';
        }

        if (!isset($data['mail_to']) || !is_array($data['mail_to']) || !count($data['mail_to'])) {
            $errors[] = 'Liste des destinataires absente';
        }

        if (!isset($data['mail_object']) || !(string) $data['mail_object']) {
            $errors[] = 'Objet de l\'e-mail absent';
        }

        if (!isset($data['msg_html']) || !(string) $data['msg_html']) {
            $errors[] = 'Veuillez saisir un message dans le corps de l\'e-mail';
        }

        if (!count($errors)) {
            $from = $data['from'];
            $to = '';
            $cc = '';

            foreach (array('mail_to', 'copy_to') as $type) {
                if (isset($data[$type]) && is_array($data[$type])) {
                    foreach ($data[$type] as $mail_to) {
                        $name = '';
                        $emails = '';
                        if (preg_match('/^[0-9]+$/', '' . $mail_to)) {
                            $contact = BimpObject::getInstance('bimpcore', 'Bimp_Contact', (int) $mail_to);
                            if ($contact->isLoaded()) {
                                if (!(string) $contact->getData('email')) {
                                    $errors[] = 'Aucune adresse e-mail enregistrée pour le contact "' . $contact->getData('firstname') . ' ' . $contact->getData('lastname') . '"';
                                } else {
                                    $emails = $contact->getData('email');
                                    $name = $contact->getData('firstname') . ' ' . $contact->getData('lastname');
                                }
                            } else {
                                $errors[] = 'Le contact d\'ID ' . $mail_to . ' n\'existe pas';
                            }
                        } elseif ($mail_to === 'thirdparty') {
                            $client = $this->getChildObject('client');
                            if (BimpObject::objectLoaded($client)) {
                                if (!(string) $client->getData('email')) {
                                    $errors[] = 'Aucune adresse e-mail enregistrée pour le client';
                                } else {
                                    $name = $client->getData('nom');
                                    $emails = $client->getData('email');
                                }
                            } else {
                                $errors[] = 'Aucun client enregistré pour ' . $this->getLabel('this');
                            }
                        } elseif (is_string($mail_to)) {
                            if (BimpValidate::isEmail($mail_to)) {
                                $emails = $mail_to;
                            } else {
                                $errors[] = '"' . $mail_to . '" n\'est pas une adresse e-mail valide';
                            }
                        }

                        if ($emails) {
                            $emails = str_replace(' ', '', $emails);
                            $emails = str_replace(';', ',', $emails);

                            foreach (explode(',', $emails) as $email) {
                                if ($name) {
                                    switch ($type) {
                                        case 'mail_to': $to .= ($to ? ', ' : '') . $name . ' <' . $email . '>';
                                            break;

                                        case 'copy_to': $cc .= ($cc ? ', ' : '') . $name . ' <' . $email . '>';
                                            break;
                                    }
                                } else {
                                    switch ($type) {
                                        case 'mail_to': $to .= ($to ? ', ' : '') . $email;
                                            break;

                                        case 'copy_to': $cc .= ($cc ? ', ' : '') . $email;
                                            break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $filename_list = array();
            $mimetype_list = array();
            $mimefilename_list = array();

            if (isset($data['join_files']) && is_array($data['join_files'])) {
                foreach ($data['join_files'] as $id_file) {
                    $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', (int) $id_file);
                    if ($file->isLoaded()) {
                        $file_path = $file->getFilePath();
                        $file_name = $file->getData('file_name') . '.' . $file->getData('file_ext');
                        if (!file_exists($file_path)) {
                            $errors[] = 'Le fichier "' . $file_name . '" n\'existe pas';
                        } else {
                            $filename_list[] = $file_path;
                            $mimetype_list[] = dol_mimetype($file_name);
                            $mimefilename_list[] = $file_name;
                        }
                    } else {
                        $errors[] = 'Le fichier d\'ID ' . $id_file . ' n\'existe pas';
                    }
                }
            }

            if (!$from) {
                $errors[] = 'Aucun expéditeur valide';
            }

            if (!$to) {
                $errors[] = 'Aucun destinataire valide';
            }

            if (!count($errors)) {
                $mail_object .= $data['mail_object'];

                $deliveryreceipt = (isset($data['confirm_reception']) ? (int) $data['confirm_reception'] : 0);
                if (mailSyn2($mail_object, $to, $from, $data['msg_html'], $filename_list, $mimetype_list, $mimefilename_list, $cc, '', $deliveryreceipt)) {
                    if (static::$mail_event_code) {
                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                        global $user, $langs, $conf;
                        $interface = new Interfaces($this->db->db);
                        if ($interface->run_triggers(static::$mail_event_code, $this->dol_object, $user, $langs, $conf) < 0) {
                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($interface), 'Echec de l\'enregistrement de l\'envoi du mail dans la liste des événements');
                        }
//                        global $user;
//                        BimpTools::loadDolClass('comm/action', 'actioncomm', 'ActionComm');
//                        $ac = new ActionComm($this->db->db);
//                        $ac->code = static::$mail_event_code;
//                        $ac->datep = dol_now();
//                        $ac->authorid = (int) $user->id;
//                        $ac->userownerid = (int) $user->id;
//                        $ac->socid = (int) $this->getData('fk_soc');
//                        $ac->label = BimpTools::ucfirst($this->getLabel()) . ' envoyé' . $this->e() . ' par e-mail';
//                        $ac->elementid = (int) $this->id;
//                        $ac->elementtype = static::$element_name;
//
//                        $note = 'Emetteur: ' . $from . '<br/>';
//                        $note .= 'Destinataire: ' . $to . '<br/>';
//                        if ($cc) {
//                            $note .= 'Copie: ' . $cc . '<br/>';
//                        }
//                        $note .= '<br/>';
//
//                        $note .= 'Objet: ' . $mail_object . '<br/>';
//                        $note .= 'Corps du message: <br/>';
//                        $note .= $data['msg_html'] . '<br/>';
//
//                        if (!empty($mimefilename_list)) {
//                            $note .= 'Fichiers joints: <br/>';
//                            foreach ($mimefilename_list as $file_name) {
//                                $note .= $file_name . '<br/>';
//                            }
//                        }
//                        $ac->note = $note;
//                        if ($ac->create($user) <= 0) {
//                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($ac), 'Echec de l\'enregistrement de l\'envoi dans la liste des événements');
//                        }
                    }
                } else {
                    $errors[] = 'Echec de l\'envoi du mail';
                }
            }
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

        if ($data['id_mode_paiement'] == 'VIR' && is_null($data['bank_account'])) {
            $errors[] = "Le compte banqaire est obligatoire pour un virement bancaire";
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

            if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn')) && (int) $this->getData('fk_soc') !== (int) $this->getInitData('fk_soc')) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $this->getData('fk_soc'));
                if (BimpObject::objectLoaded($soc)) {
                    $zone = $this->getZoneByCountry($soc);
                    $this->set('zone_vente', $zone);
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

        if (!count($errors)) {
            if (method_exists($this->dol_object, 'fetch_lines')) {
                $this->dol_object->fetch_lines();
            }
            $this->checkLines(); // Des lignes ont pu être créées via un trigger.

            if ($origin && $origin_id) {
                $warnings = array_merge($warnings, $this->createLinesFromOrigin($origin_object));
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
        }

        $this->hydrateFromDolObject();

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

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            foreach ($lines as $line) {
                $line_pos = $line->getData('position');
                $line_warnings = array();
                $line->bimp_line_only = true;
                $line_errors = $line->delete($line_warnings, true);

                $line_errors = array_merge($line_warnings, $line_errors);
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
        }

        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        parent::onSave($errors, $warnings);

        $this->processRemisesGlobales();
    }
}

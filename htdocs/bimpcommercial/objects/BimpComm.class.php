<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BimpComm extends BimpDolObject
{

    const BC_ZONE_FR = 1;
    const BC_ZONE_UE = 2;
    const BC_ZONE_HORS_UE = 3;

    public static $email_type = '';
    public static $external_contact_type_required = true;
    public static $internal_contact_type_required = true;
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
    public static $zones_vente = array(
        self::BC_ZONE_FR      => 'France',
        self::BC_ZONE_UE      => 'Union Européenne',
        self::BC_ZONE_HORS_UE => 'Hors UE'
    );

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
        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'zone_vente':
                return (int) $this->areLinesEditable();
        }
        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'setRemiseGlobale':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                    return 0;
                }
                if (!$this->field_exists('remise_globale')) {
                    $errors[] = 'Les remises globales ne sont pas disponibles pour ' . $this->getLabel('the_plur');
                    return 0;
                }
                if (!$this->areLinesEditable()) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' ne peut plus être éditée';
                    return 0;
                }
                return 1;

            case 'addAcompte':
                if (!$this->acomptes_allowed) {
                    $errors[] = 'Acomptes non autorisés pour les ' . $this->getLabel('name_plur');
                    return 0;
                }
                if (!$this->isLoaded()) {
                    $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                    return 0;
                }
                if($this->getData('invoice_status') === null || $this->getData('invoice_status') > 0){
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est déja facturé';
                    return 0;
                }
                    
                
//                if (!$this->areLinesEditable()) {
//                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' ne peut plus être éditée';
//                    return 0;
//                }

                $client = $this->getChildObject('client');

                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Client absent';
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
        if ($this->field_exists('remise_globale') && (float) $this->getData('remise_globale')) {
            return 1;
        }

        return 0;
    }

    public function isTvaActive()
    {
        if ($this->field_exists('zone_vente') && $this->dol_field_exists('zone_vente')) {
            if ((int) $this->getData('zone_vente') === self::BC_ZONE_HORS_UE) {
                return 0;
            }
        }

        return 1;
    }

    // Getters array: 

    public function getModelsPdfArray()
    {
        return array();
    }

    public function getClientContactsArray()
    {
        $id_client = $this->getAddContactIdClient();
        return self::getSocieteContactsArray($id_client, false);
    }

    public function getEmailModelsArray()
    {
        if (!static::$email_type) {
            return array();
        }

        return self::getEmailTemplatesArray(static::$email_type, true);
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

        // Remise globale: 
        if ($this->isActionAllowed('setRemiseGlobale') && $this->canSetAction('setRemiseGlobale')) {
            $buttons[] = array(
                'label'   => 'Remise globale',
                'icon'    => 'fas_percent',
                'onclick' => $this->getJsActionOnclick('setRemiseGlobale', array(
                    'remise_globale'       => (float) $this->getData('remise_globale'),
                    'remise_globale_label' => addslashes($this->getData('remise_globale_label'))
                        ), array(
                    'form_name' => 'remise_globale'
                ))
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
            'label'   => 'Message logistique ',
            'icon'    => 'far fa-paper-plane',
            'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_GROUP, "fk_group_dest" => $note::BN_GROUPID_LOGISTIQUE, "content" => ""), array('form_name' => 'rep'))
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
        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');

        return array(
            array(
                'label'       => 'Créer un produit',
                'icon_before' => 'fas_box',
                'classes'     => array('btn', 'btn-default'),
                'attr'        => array(
                    'onclick' => $product->getJsLoadModalForm('light', 'Nouveau produit')
                )
            )
        );
    }

    public function getRequestSelectRemisesFilters()
    {
        $filter = '';

        $discounts = (int) BimpTools::getValue('discounts', BimpTools::getValue('param_values/fields/discounts', 1));
        if ($discounts) {
            $filter = "(fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND ((description LIKE '(DEPOSIT)%' OR description LIKE 'Acompte%') AND description NOT LIKE '(EXCESS RECEIVED)%')))";
        }
        $creditNotes = (int) BimpTools::getValue('credit_notes', BimpTools::getValue('param_values/fields/credit_notes', 1));
        if ($creditNotes) {
            if ($discounts) {
                $filter .= ' OR ';
            }
            $filter .= "(fk_facture_source IS NOT NULL AND ((description NOT LIKE '(DEPOSIT)%' AND description NOT LIKE 'Acompte%') OR description LIKE '(EXCESS RECEIVED)%'))";
        }

        if ($discounts && $creditNotes) {
            return '(' . $filter . ')';
        }

        return $filter;
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

            return $this->getChildrenObjects('lines', $filters);
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

    public function getTotalTtcWithoutRemises()
    {
        $total = 0;

        if ($this->isLoaded()) {
            $lines = $this->getChildrenObjects('lines');
            foreach ($lines as $line) {
                $total += $line->getTotalTtcWithoutRemises();
            }
        }
        return $total;
    }

    public function getRemiseGlobaleAmount()
    {
        if ($this->isLoaded() && $this->field_exists('remise_globale')) {
            $total_ttc = (float) $this->getTotalTtcWithoutRemises();
            return round($total_ttc * ((float) $this->getData('remise_globale') / 100), 2);
        }

        return 0;
    }

    public function getRemiseGlobaleLineRate($recalculate = false)
    {
        if ($recalculate || is_null($this->remise_globale_line_rate)) {
            $this->remise_globale_line_rate = 0;

            $remise_globale = (float) $this->getData('remise_globale');
            if ($remise_globale) {
                $ttc = $this->getTotalTtcWithoutRemises();
                $remise_amount = $ttc * ($remise_globale / 100);
                $total_lines = 0;

                $lines = $this->getChildrenObjects('lines');
                foreach ($lines as $line) {
                    if ($line->isRemisable()) {
                        $total_lines += (float) $line->getTotalTtcWithoutRemises();
                    }
                }

                if ($total_lines) {
                    $this->remise_globale_line_rate = ($remise_amount / $total_lines) * 100;
                }
            }
        }

        return $this->remise_globale_line_rate;
    }

    public function getAddContactIdClient()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return $id_client;
    }

    public function getEmailUsersFromArray()
    {
        global $user, $langs, $conf;

        $emails = array();

        if (empty($user->email)) {
            $langs->load('errors');
            $emails['user'] = $user->getFullName($langs) . ' &lt;' . $langs->trans('ErrorNoMailDefinedForThisUser') . '&gt;';
        } else {
            $emails['user'] = $user->getFullName($langs) . ' &lt;' . $user->email . '&gt;';
        }

        $emails['company'] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' &lt;' . $conf->global->MAIN_INFO_SOCIETE_MAIL . '&gt;';

        $aliases = array('user_aliases' => $user->email_aliases, 'global_aliases' => $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);

        foreach ($aliases as $type => $list) {
            $i = 0;
            foreach (explode(',', $list) as $alias) {
                $i++;
                $alias = trim($alias);
                if ($alias) {
                    $alias = preg_replace('/</', '&lt;', $alias);
                    $alias = preg_replace('/>/', '&gt;', $alias);
                    if (!preg_match('/&lt;/', $alias)) {
                        $alias = '&lt;' . $alias . '&gt;';
                    }
                    $emails[$type . '_' . $i] = $alias;
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

        return $values;
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
            'remises_lines_percent'     => 0,
            'remises_lines_amount_ht'   => 0,
            'remises_lines_amount_ttc'  => 0,
            'remise_globale_percent'    => 0,
            'remise_globale_amount_ht'  => 0,
            'remise_globale_amount_ttc' => 0,
            'remise_total_percent'      => 0,
            'remise_total_amount_ht'    => 0,
            'remise_total_amount_ttc'   => 0
        );

        if ($this->isLoaded()) {
            $total_ttc_without_remises = 0;

            $lines = $this->getChildrenObjects('lines');

            if ($this->field_exists('remise_globale')) {
                $infos['remise_globale_percent'] = (float) $this->getData('remise_globale');
            }

            foreach ($lines as $line) {
                $line_infos = $line->getRemiseTotalInfos();
                $infos['remises_lines_amount_ttc'] += (float) $line_infos['line_amount_ttc'];
                $infos['remises_lines_amount_ht'] += (float) $line_infos['line_amount_ht'];
                $total_ttc_without_remises += $line_infos['total_ttc_without_remises'];

                if ($infos['remise_globale_percent']) {
                    $infos['remise_globale_amount_ttc'] += $line_infos['remise_globale_amount_ttc'];
                    $infos['remise_globale_amount_ht'] += $line_infos['remise_globale_amount_ht'];
                }
            }

            if ($infos['remises_lines_amount_ttc']) {
                $infos['remises_lines_percent'] = ($infos['remises_lines_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            $infos['remise_total_percent'] = $infos['remises_lines_percent'] + $infos['remise_globale_percent'];
            $infos['remise_total_amount_ttc'] = $infos['remises_lines_amount_ttc'] + $infos['remise_globale_amount_ttc'];
            $infos['remise_total_amount_ht'] = $infos['remises_lines_amount_ht'] + $infos['remise_globale_amount_ht'];
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
            if ($bimp_line->getData("remise_pa") > 0) {
                $line->pa_ht = $line->pa_ht * (100 - $bimp_line->getData("remise_pa")) / 100;
            }

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

        return $marginInfos;
    }

    public function getCondReglementBySociete()
    {
        if (!$this->isLoaded()) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc', 0);
            if (!$id_soc) {
                $id_soc = (int) BimpTools::getPostFieldValue('id_client', 0);
            }
            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
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
            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    return (int) $soc->dol_object->mode_reglement_id;
                }
            }
            return 0;
        }

        if (isset($this->data['fk_mode_reglement'])) {
            return (int) $this->data['fk_mode_reglement']; // pas getData() sinon boucle infinie (getModeReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return 0;
    }

    public static function getZoneByCountry($id_country)
    {
        $zone = self::BC_ZONE_FR;
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

    // Getters - Overrides BimpObject
//    public function getName()
//    {
//        $name = parent::getName();
//        return BimpTools::ucfirst($this->getLabel()) . ' ' . ($name ? '"' . $name . '"' : '');
//    }

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
                return DOL_URL_ROOT . '/document.php?modulepart=' . $module_part . '&file=' . htmlentities(dol_sanitizeFileName($this->getRef()) . '/' . $file_name);
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

    public function setRemiseGlobalePercent($remise_globale)
    {
        $errors = array();

        $errors = $this->updateField('remise_globale', round((float) $remise_globale, 8));

        if (!count($errors)) {
            $lines = $this->getChildrenObjects('lines');

            $remise_globale_lines_rate = (float) $this->getRemiseGlobaleLineRate(true);

            foreach ($lines as $line) {
                $line->parent = $this;
                $line->calcRemise($remise_globale_lines_rate);
            }
        }

        return $errors;
    }

    // Affichages: 

    public function displayRemisesClient()
    {
        $html = '';

        if ($this->isLoaded()) {
            $soc = $this->getChildObject('client');
            if (BimpObject::objectLoaded($soc)) {
                global $langs, $conf;

                $soc = $soc->dol_object;

//                if ($soc->remise_percent) {
//                    $html .= $langs->trans("CompanyHasRelativeDiscount", $soc->remise_percent);
//                } else {
//                    $html .= $langs->trans("CompanyHasNoRelativeDiscount");
//                }

                if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) { // Never use this
                    $filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
                    $filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
                } else {
                    $filterabsolutediscount = "fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND ((description LIKE '(DEPOSIT)%' OR description LIKE 'Acompte%') AND description NOT LIKE '(EXCESS RECEIVED)%'))";
                    $filtercreditnote = "fk_facture_source IS NOT NULL AND ((description NOT LIKE '(DEPOSIT)%' AND description NOT LIKE 'Acompte%') OR description LIKE '(EXCESS RECEIVED)%')";
                }

                $absolute_discount = (float) round(price2num($soc->getAvailableDiscounts('', $filterabsolutediscount), 'MT'), 2);
                $absolute_creditnote = (float) round(price2num($soc->getAvailableDiscounts('', $filtercreditnote), 'MT'), 2);

                $can_use_discount = false;

//                if ($absolute_discount > 0.009) {
//                    $html .= '<br/>';
//                    $html .= $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                // bug dans dolibarr: Commande::insert_discount - désactivé
//                    if ($status === Commande::STATUS_DRAFT) {
//                        $can_use_discount = true;
//                    }
//                }
//                if ($absolute_creditnote > 0.009) {
//                    $html .= '<br/>';
//                    $html .= $langs->trans("CompanyHasCreditNote", price($absolute_creditnote), $langs->transnoentities("Currency" . $conf->currency));
//                }
//
//                if (!$absolute_discount && !$absolute_creditnote) {
//                    $html .= '<br/>';
//                    $html .= $langs->trans("CompanyHasNoAbsoluteDiscount");
//                }

                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';
                $html .= '<tr>';
                $html .= '<td style="width: 140px">Remise par défaut: </td>';
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayFloatValue((float) $soc->remise_percent) . '%</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td style="width: 140px">Acomptes: </td>';
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayMoneyValue((float) $absolute_discount, 'EUR') . '</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td style="width: 140px">Avoirs: </td>';
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayMoneyValue((float) $absolute_creditnote, 'EUR') . '</td>';
                $html .= '</tr>';
                $html .= '</tbody>';
                $html .= '</table>';

                if ($can_use_discount) {
                    $html .= '<div class="buttonsContainer align-right">';
                    $onclick = $this->getJsActionOnclick('useRemise', array(
                        'discounts'    => ($can_use_discount ? 1 : 0),
                        'credit_notes' => 0
                            ), array(
                        'form_name' => 'use_remise'
                    ));
                    $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                    $html .= '<i class="' . BimpRender::renderIconClass('fas_file-import') . ' iconLeft"></i>Appliquer une remise disponible';
                    $html .= '</button>';

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

                if ($infos['remise_globale_amount_ttc']) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Remise globale: </td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_globale_amount_ht'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_globale_amount_ttc'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['remise_globale_percent'], 4) . ' %</td>';
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

    public function displayPDFButton($display_generate = true, $with_ref = true)
    {
        $html = '';
        $ref = dol_sanitizeFileName($this->getRef());

        if ($ref) {
            $file_url = $this->getFileUrl($ref . '.pdf');
            if ($file_url) {
                $onclick = 'window.open(\'' . $file_url . '\');';
                $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $html .= '<i class="fas fa5-file-pdf ' . ($with_ref ? 'iconLeft' : '') . '"></i>';
                if ($with_ref) {
                    $html .= $ref . '.pdf';
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
                if (!empty($conf->product->enabled) && !empty($conf->service->enabled)) {
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
                }
                $html .= '</tfoot>';
                $html .= '</table>';
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
                        $html .= '<td>' . $action->getNomUrl(0, 38) . '</td>';
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
                            'type'           => 'secondary',
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

    public function renderLinkedObjectsTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            $objects = array();

            if ($this->isDolObject()) {
                $propal_instance = null;
                $facture_instance = null;
                $commande_instance = null;
                $commande_fourn_instance = null;
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    switch ($item['type']) {
                        case 'propal':
                            $propal_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);
                            if ($propal_instance->isLoaded()) {
                                $icon = $propal_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($propal_instance->getLabel()),
                                    'ref'      => $propal_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $propal_instance->displayData('datep'),
                                    'total_ht' => $propal_instance->displayData('total_ht'),
                                    'status'   => $propal_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'facture':
                            $facture_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $item['id_object']);
                            if ($facture_instance->isLoaded()) {
                                $icon = $facture_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($facture_instance->getLabel()),
                                    'ref'      => $facture_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $facture_instance->displayData('datef'),
                                    'total_ht' => $facture_instance->displayData('total'),
                                    'status'   => $facture_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'commande':
                            $commande_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);
                            if ($commande_instance->isLoaded()) {
                                $icon = $commande_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_instance->getLabel()),
                                    'ref'      => $commande_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $commande_instance->displayData('date_commande'),
                                    'total_ht' => $commande_instance->displayData('total_ht'),
                                    'status'   => $commande_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'order_supplier':
                            $commande_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $item['id_object']);
                            if ($commande_fourn_instance->isLoaded()) {
                                $icon = $commande_fourn_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_fourn_instance->getLabel()),
                                    'ref'      => $commande_fourn_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $commande_fourn_instance->displayData('date_commande'),
                                    'total_ht' => $commande_fourn_instance->displayData('total_ht'),
                                    'status'   => $commande_fourn_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'invoice_supplier':
                            $facture_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($facture_fourn_instance)) {
                                $icon = $facture_fourn_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($facture_fourn_instance->getLabel()),
                                    'ref'      => $facture_fourn_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $facture_fourn_instance->displayData('datef'),
                                    'total_ht' => $facture_fourn_instance->displayData('total_ht'),
                                    'status'   => $facture_fourn_instance->displayData('fk_statut')
                                );
                            }
                            break;
                    }
                }
            }

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Type</th>';
            $html .= '<th>Réf.</th>';
            $html .= '<th>Date</th>';
            $html .= '<th>Montant HT</th>';
            $html .= '<th>Statut</th>';
//            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (count($objects)) {
                foreach ($objects as $data) {
                    $html .= '<tr>';
                    $html .= '<td><strong>' . $data['type'] . '</strong></td>';
                    $html .= '<td>' . $data['ref'] . '</td>';
                    $html .= '<td>' . $data['date'] . '</td>';
                    $html .= '<td>' . $data['total_ht'] . '</td>';
                    $html .= '<td>' . $data['status'] . '</td>';
//                    $html .= '<td style="text-align: right">';
//                    
//                    $html .= BimpRender::renderRowButton('Supprimer le lien', 'trash', '');
//
//                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="5">' . BimpRender::renderAlerts('Aucun objet lié', 'info') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Objets liés', $html, '', array(
                        'foldable' => true,
                        'type'     => 'secondary',
                        'icon'     => 'fas_link',
//                        'header_buttons' => array(
//                            array(
//                                'label'       => 'Lier à...',
//                                'icon_before' => 'plus-circle',
//                                'classes'     => array('btn', 'btn-default'),
//                                'attr'        => array(
//                                    'onclick' => ''
//                                )
//                            )
//                        )
            ));
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

    public function renderMailForm()
    {
        return BimpRender::renderAlerts('L\'envoi par email n\'est pas disponible pour ' . $this->getLabel('the_plur'));
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
        $html = '';

        $client = $this->getChildObject('client');

        $emails = array(
            '' => ''
        );

        if (BimpObject::objectLoaded($client)) {
            $client_emails = self::getSocieteEmails($client->dol_object);
            if (is_array($client_emails)) {
                foreach ($client_emails as $value => $label) {
                    $emails[$value] = $label;
                }
            }
        }

        $emails['custom'] = 'Autre';

        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array(
                    'options'     => $emails,
                    'extra_class' => 'emails_select'
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

//        $new_id = $this->dol_object->createFromClone(isset($new_data['fk_soc']) ? (int) $new_data['fk_soc'] : 0);

        $new_object = clone $this;
        $new_object->id = null;
        $new_object->id = 0;
        $new_object->set('id', 0);
        $new_object->set('ref', '');
        $new_object->set('fk_statut', 0);
        $new_object->set('remise_globale', 0);

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
            BimpTools::resetDolObjectErrors($this->dol_object);
            if ($new_object->dol_object->copy_linked_contact($this->dol_object, 'internal') < 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_object->dol_object), 'Echec de la copie des contacts internes');
            }
            if (!$new_soc) {
                BimpTools::resetDolObjectErrors($this->dol_object);
                if ($new_object->dol_object->copy_linked_contact($this->dol_object, 'external') < 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_object->dol_object), 'Echec de la copie des contacts externes');
                }
            }
            $lines_errors = $new_object->createLinesFromOrigin($this);
            if (count($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la copie des lignes ' . $this->getLabel('of_the'));
            } else {
                $remise_globale = (float) $this->getData('remise_globale');
                if ($remise_globale) {
                    $new_object->updateField('remise_globale', $remise_globale);
                    $lines = $new_object->getChildrenObjects('lines');
                    $remise_globale_lines_rate = (float) $this->getRemiseGlobaleLineRate(true);
                    foreach ($lines as $line) {
                        $line->calcRemise($remise_globale_lines_rate);
                    }
                }

                if (is_object($hookmanager)) {
                    $parameters = array('objFrom' => $this->dol_object, 'clonedObj' => $new_object->dol_object);
                    $action = '';
                    $hookmanager->executeHooks('createFrom', $parameters, $new_object->dol_object, $action);
                }
            }

            $this->reset();
            $this->fetch($new_object->id);
        }

        return $errors;
    }

    public function createLinesFromOrigin($origin)
    {
        $errors = array();

        if (!BimpObject::objectLoaded($origin) || !is_a($origin, 'BimpComm')) {
            return array('Element d\'origine absent ou invalide');
        }

        $isClone = ($this->object_name === $origin->object_name);

        $lines = $origin->getChildrenObjects('lines', array(), 'position', 'asc');

        $warnings = array();
        $i = 0;

        // Création des lignes: 
        $lines_new = array();

        foreach ($lines as $line) {
            $i++;

            // Lignes à ne pas copier en cas de clonage: 
            if ($isClone && in_array($line->getData('linked_object_name'), array(
                        'discount' // Acomptes
                    ))) {
                continue;
            }
            $line_instance = BimpObject::getInstance($this->module, $this->object_name . 'Line');
            $line_instance->validateArray(array(
                'id_obj'    => (int) $this->id,
                'type'      => $line->getData('type'),
                'deletable' => $line->getData('deletable'),
                'editable'  => $line->getData('Editable'),
                'remisable' => $line->getData('remisable')
            ));

            if ($line->getData('linked_object_name')) {
                $line_instance->set('deletable', 1);
            }

            $line_instance->desc = $line->desc;
            $line_instance->tva_tx = $line->tva_tx;
            $line_instance->id_product = $line->id_product;
            $line_instance->qty = $line->qty;
            $line_instance->pu_ht = $line->pu_ht;
            $line_instance->pa_ht = $line->pa_ht;
            $line_instance->id_fourn_price = $line->id_fourn_price;
            $line_instance->date_from = $line->date_from;
            $line_instance->date_to = $line->date_to;
            $line_instance->id_remise_except = $line->id_remise_except;

            if ($line->field_exists('remise_crt') &&
                    $line_instance->field_exists('remise_crt')) {
                $line_instance->set('remise_crt', (int) $line->getData('remise_crt'));
            }

            if ($line->field_exists('remise_pa') &&
                    $line_instance->field_exists('remise_pa')) {
                $line_instance->set('remise_pa', (float) $line->getData('remise_pa'));
            }

            $line_errors = $line_instance->create($warnings, true);
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne n°' . $i);
                continue;
            }

            $lines_new[(int) $line->id] = (int) $line_instance->id;

            // Attribution des équipements si nécessaire: 
            if ($line->equipment_required && $line_instance->equipment_required && $line->isProductSerialisable()) {
                $equipmentlines = $line->getEquipmentLines();

                foreach ($equipmentlines as $equipmentLine) {
                    $data = $equipmentLine->getDataArray();
                    $line_instance->attributeEquipment($data['id_equipment'], $data['pu_ht'], $data['tva_tx'], $data['id_fourn_price']);
                }
            }

            // Création des remises pour la ligne en cours:
            $remises = $line->getRemises();
            if (!is_null($remises) && count($remises)) {

                $j = 0;
                foreach ($remises as $r) {
                    $j++;
                    $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                    $remise->validateArray(array(
                        'id_object_line' => (int) $line_instance->id,
                        'object_type'    => $line_instance::$parent_comm_type,
                        'label'          => $r->getData('label'),
                        'type'           => (int) $r->getData('type'),
                        'percent'        => (float) $r->getData('percent'),
                        'montant'        => (float) $r->getData('montant'),
                        'per_unit'       => (int) $r->getData('per_unit')
                    ));
                    $remise_errors = $remise->create($warnings, true);
                    if (count($remise_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise n°' . $j . ' pour la ligne n°' . $i);
                    }
                }
            }
        }

        // Attribution des lignes parentes: 
        foreach ($lines as $line) {
            $id_parent_line = (int) $line->getData('id_parent_line');

            if ($id_parent_line) {
                if (isset($lines_new[(int) $line->id]) && (int) $lines_new[(int) $line->id] && isset($lines_new[$id_parent_line]) && (int) $lines_new[$id_parent_line]) {
                    $line_instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name . 'Line', (int) $lines_new[(int) $line->id]);
                    if (BimpObject::objectLoaded($line_instance)) {
                        $line_instance->updateField('id_parent_line', (int) $lines_new[(int) $id_parent_line]);
                    }
                }
            }
        }
        return $errors;
    }

    public function onChildSave($child)
    {
        if ($this->isLoaded()) {
            if (is_a($child, 'objectLine')) {
                if ($this->areLinesEditable()) {
                    // Vérification du changement de taux de remise globale: 
                    $current_rate = (float) $this->remise_globale_line_rate;
                    $new_rate = (float) $this->getRemiseGlobaleLineRate(true);

                    if ($new_rate !== $current_rate) {
                        foreach ($this->getChildrenObjects('lines') as $line) {
                            $line->calcRemise($new_rate);
                        }
                    }
                }
            }
        }
    }

    public function checkEquipmentsAttribution(&$errors)
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

    public function createAcompte($amount, $id_mode_paiement, &$warnings = array())
    {
        global $user, $langs;
        $errors = array();

        $caisse = null;
        $id_caisse = 0;

        if ($this->useCaisseForPayments) {
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
            if (!$id_caisse) {
                $errors[] = 'Veuillez-vous <a href="' . DOL_URL_ROOT . '/bimpcaisse/index.php" target="_blank">connecter à une caisse</a> pour l\'enregistrement de l\'acompte';
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
            $factureA->date = dol_now();
            $factureA->socid = $id_client;
            $factureA->cond_reglement_id = 1;
            $factureA->modelpdf = 'bimpfact';

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
                $factureA->validate($user);

                // Création du paiement: 
                BimpTools::loadDolClass('compta/paiement', 'paiement');
                $payement = new Paiement($this->db->db);
                $payement->amounts = array($factureA->id => $amount);
                $payement->datepaye = dol_now();
                $payement->paiementid = (int) $id_mode_paiement;
                if ($payement->create($user) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Des erreurs sont survenues lors de la création du paiement de la facture d\'acompte');
                } else {
                    if ($this->useCaisseForPayments) {
                        $id_account = (int) $caisse->getData('id_account');
                    } else {
                        $id_account = (int) BimpCore::getConf('bimpcaisse_id_default_account');
                    }

                    // Ajout du paiement au compte bancaire: 
                    if ($payement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                        $account_label = '';

                        if ($this->useCaisseForPayments) {
                            $account = $caisse->getChildObject('account');

                            if (BimpObject::objectLoaded($account)) {
                                $account_label = '"' . $account->bank . '"';
                            }
                        }

                        if (!$account_label) {
                            $account_label = ' d\'ID ' . $id_account;
                        }
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Echec de l\'ajout de l\'acompte au compte bancaire ' . $account_label);
                    }

                    // Enregistrement du paiement caisse: 
                    if ($this->useCaisseForPayments) {
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
                    $line = $this->getLineInstance();

                    $line_errors = $line->validateArray(array(
                        'id_obj'             => (int) $this->id,
                        'type'               => ObjectLine::LINE_FREE,
                        'deletable'          => 0,
                        'editable'           => 0,
                        'remisable'          => 0,
                        'linked_id_object'   => (int) $discount->id,
                        'linked_object_name' => 'discount'
                    ));

                    if (!count($line_errors)) {
                        $line->desc = 'Acompte';
                        $line->id_product = 0;
                        $line->pu_ht = -$discount->amount_ht;
                        $line->pa_ht = -$discount->amount_ht;
                        $line->qty = 1;
                        $line->tva_tx = 20;
                        $line->id_remise_except = (int) $discount->id;
                        $line->remise = 0;

                        $line_warnings = array();
                        $line_errors = $line->create($line_warnings, true);
                        $line_errors = array_merge($line_errors, $line_warnings);
                    }

                    if (count($line_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la création de la ligne d\'acompte');
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

    // Actions:

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
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
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Des erreurs sont survenues lors de la validation ' . $this->getLabel('of_the'));
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
        $success = 'Ajout du contact effectué avec succès';

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

        $error_label = 'Insertion de la remise impossible';

        if (!method_exists($this->dol_object, 'insert_discount')) {
            $errors[] = 'L\'utilisation de remise n\'est pas possible pour ' . $this->getLabel('the_plur');
        } elseif (!isset($data['id_discount']) || !(int) $data['id_discount']) {
            $errors[] = 'Aucune remise sélectionnée';
        } elseif ((int) $this->getData('fk_statut') > 0) {
            $errors[] = $error_label . ' - ' . $this->getData('the') . ' doit avoit le statut "Brouillon';
        } else {
            if (!class_exists('DiscountAbsolute')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
            }
            $discount = new DiscountAbsolute($this->db->db);
            $discount->fetch((int) $data['id_discount']);

            if (!BimpObject::objectLoaded($discount)) {
                $errors[] = 'La remise d\'ID ' . $data['id_discount'] . ' n\'existe pas';
            } else {
                if ($this->dol_object->insert_discount($discount->id) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'insertion de la remise');
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionSendEMail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Email envoyé avec succès';

        global $user, $langs, $conf;
        $langs->load('errors');

        if (!isset($data['from']) || !(string) $data['from']) {
            $errors[] = 'Emetteur absent';
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
            $from = '';
            $to = '';
            $cc = '';

            if ($data['from'] === 'user') {
                if (empty($user->email)) {
                    $errors[] = 'Aucune adresse e-mail enregistrée pour l\'utilisateur "' . $user->getFullName($langs) . '"';
                } else {
                    $from = $user->getFullName($langs) . ' <' . $user->email . '>';
                }
            } elseif ($data['from'] === 'company') {
                $from = $conf->global->MAIN_INFO_SOCIETE_NOM . ' <' . $conf->global->MAIN_INFO_SOCIETE_MAIL . '>';
            } elseif (preg_match('/^user_aliases_(\d+)$/', $data['from'], $matches)) {
                if (isset($user->email_aliases)) {
                    $aliases = explode(', ', (string) $user->email_aliases);
                    if (isset($aliases[((int) $matches[1]) - 1])) {
                        $from = $aliases[((int) $matches[1]) - 1];
                    }
                }
            } elseif (preg_match('/^global_aliases_(\d+)$/', $data['from'], $matches)) {
                if (isset($conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES)) {
                    $aliases = explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);
                    if (isset($aliases[((int) $matches[1] - 1)])) {
                        $from = $aliases[((int) $matches[1]) - 1];
                    }
                }
            }

            foreach (array('mail_to', 'copy_to') as $type) {
                if (isset($data[$type]) && is_array($data[$type])) {
                    foreach ($data[$type] as $mail_to) {
                        $email = '';
                        if (preg_match('/^[0-9]+$/', '' . $mail_to)) {
                            $contact = BimpObject::getInstance('bimpcore', 'Bimp_Contact', (int) $mail_to);
                            if ($contact->isLoaded()) {
                                if (!(string) $contact->getData('email')) {
                                    $errors[] = 'Aucune adresse e-mail enregistrée pour le contact "' . $contact->getData('firstname') . ' ' . $contact->getData('lastname') . '"';
                                } else {
                                    $email = $contact->getData('firstname') . ' ' . $contact->getData('lastname') . ' <' . $contact->getData('email') . '>';
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
                                    if ($client->getData('nom')) {
                                        $email = $client->getData('nom') . ' <' . $client->getData('email') . '>';
                                    } else {
                                        $email = $client->getData('email');
                                    }
                                }
                            } else {
                                $errors[] = 'Aucun client enregistré pour ' . $this->getLabel('this');
                            }
                        } elseif (is_string($mail_to)) {
                            if (BimpValidate::isEmail($mail_to)) {
                                $email = $mail_to;
                            } else {
                                $errors[] = '"' . $mail_to . '" n\'est pas une adresse e-mail valide';
                            }
                        }

                        if ($email) {
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
                // todo: loguer l'envoi du mail
                $deliveryreceipt = (isset($data['confirm_reception']) ? (int) $data['confirm_reception'] : 0);
                mailSyn2($data['mail_object'], $to, $from, $data['msg_html'], $filename_list, $mimetype_list, $mimefilename_list, $cc, '', $deliveryreceipt);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetRemiseGlobale($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise globale enregistrée avec succès';

        $total_ttc_without_remises = $this->getTotalTtcWithoutRemises();

        if (!count($errors)) {
            if (isset($data['use_amount']) && (int) $data['use_amount']) {
                if (!isset($data['remise_globale_amount'])) {
                    $errors[] = 'Montant de la remise globale absent';
                }

                if ($total_ttc_without_remises > 0) {
                    $data['remise_globale'] = round(((float) $data['remise_globale_amount'] / $total_ttc_without_remises) * 100, 8);
                } else {
                    $data['remise_globale'] = 0;
                }
            }

            if (!count($errors)) {
                if (!isset($data['remise_globale'])) {
                    $errors[] = 'Montant de la remise globale absent';
                } else {
                    if ($this->field_exists('remise_globale_label') && isset($data['remise_globale_label'])) {
                        $this->updateField('remise_globale_label', (string) $data['remise_globale_label']);
                    }

                    $errors = $this->setRemiseGlobalePercent((float) $data['remise_globale']);
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

        $id_mode_paiement = isset($data['id_mode_paiement']) ? (int) $data['id_mode_paiement'] : 0;
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;

        if (!$id_mode_paiement) {
            $errors[] = 'Mode de paiement absent';
        }
        if (!$amount) {
            $errors[] = 'Montant absent';
        }

        if (!count($errors)) {
            $errors = $this->createAcompte($amount, $id_mode_paiement, $warnings);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    // Overrides BimpObject:

    public function validate()
    {
        if ($this->field_exists('zone_vente') && $this->dol_field_exists('zone_vente')) {
            $zone = self::BC_ZONE_FR;

            if ((int) $this->getData('fk_soc') !== (int) $this->getInitData('fk_soc')) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $this->getData('fk_soc'));
                if (BimpObject::objectLoaded($client)) {
                    $zone = $this->getZoneByCountry((int) $client->getData('fk_pays'));
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

            if ($this->field_exists('remise_globale_label') && $origin_object->field_exists('remise_globale_label')) {
                $label = str_replace($origin_object->getLabel('of_the'), $this->getLabel('of_the'), $origin_object->getData('remise_globale_label'));
                $this->set('remise_globale_label', $label);
            }

            if ($this->field_exists('remise_globale')) {
                $this->set('remise_globale', 0);
            }
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if (method_exists($this->dol_object, 'fetch_lines')) {
                $this->dol_object->fetch_lines();
            }
            $this->checkLines(); // Des lignes ont pu être créées via un trigger.

            if ($origin && $origin_id) {
                $warnings = array_merge($warnings, $this->createLinesFromOrigin($origin_object));
                if ($this->field_exists('remise_globale') && $origin_object->field_exists('remise_globale')) {
                    $rem_errors = $this->setRemiseGlobalePercent((float) $origin_object->getData('remise_globale'));

                    if (count($rem_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($rem_errors, 'Erreurs lors de l\'ajout de la remise globale');
                    }
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_zone = (int) $this->getInitData('zone_vente');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($this->areLinesEditable()) {
                $cur_zone = (int) $this->getData('zone_vente');

                if ($cur_zone !== $init_zone && $cur_zone === self::BC_ZONE_HORS_UE) {
                    $lines_errors = $this->removeLinesTvaTx();
                    if (count($lines_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la suppression des taux de TVA');
                    }
                }
            }
        }

        return $errors;
    }
}

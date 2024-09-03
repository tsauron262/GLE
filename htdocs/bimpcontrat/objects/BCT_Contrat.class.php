<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BCT_Contrat extends BimpDolObject
{

    public $redirectMode = 0;
    public static $email_type = 'contract';
    public static $element_name = "contrat";
    public static $dol_module = 'contrat';
    public static $files_module_part = 'contract';
    public static $modulepart = 'contract';

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 2;

    public static $status_list = Array(
        self::STATUS_DRAFT     => Array('label' => 'Brouillon', 'classes' => Array('warning'), 'icon' => 'fas_trash-alt'),
        self::STATUS_VALIDATED => Array('label' => 'Validé', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::STATUS_CLOSED    => Array('label' => 'Fermé', 'classes' => Array('danger'), 'icon' => 'fas_times')
    );
    public static $fac_modes = array(
        1 => 'Mois en cours',
        2 => 'A date'
    );

    // Droits user : 

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $userClient->getData('id_client') !== (int) $this->getData('fk_soc')) {
                return 0;
            }

            if ($userClient->isAdmin()) {
                return 1;
            }

            if (in_array($this->id, $userClient->getAssociatedContratsList())) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canClientViewDetail()
    {
        global $userClient;
        if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
            return 1;
        }
        return 0;
    }

    public function canEditField($field_name)
    {
        return 1;
    }

    public function canSetAction($action)
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        switch ($action) {
            case 'validate':
                if ($user->rights->bimpcontract->to_validate) {
                    return 1;
                }
                return 0;

            case 'CorrectAbosStocksAll':
                return ($user->admin ? 1 : 0);

            case 'mergeContrat':
                return 0; // seulement admins
        }

        return parent::canSetAction($action);
    }

    // Getters booléens : 

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($force_delete) {
            return 1;
        }

        if ((int) $this->getData('statut') != self::STATUS_DRAFT) {
            return 0;
        }

        return parent::isDeletable();
    }

    public function isActionAllowed($action, &$errors = [])
    {
        if (in_array($action, array('validate', 'createSignature', 'mergeContrat')) && !$this->isLoaded($errors)) {
            return 0;
        }

        $status = (int) $this->getData('statut');

        switch ($action) {
            case 'validate':
                if ($status != self::STATUS_DRAFT) {
                    $errors[] = 'Ce contrat n\'est pas au satut brouillon';
                    return 0;
                }
                return 1;

            case 'createSignature':
                if ((int) $this->getData('id_signature')) {
                    $errors[] = 'Signature déjà créée';
                    return 0;
                }

                if ((int) $this->getData('statut') !== self::STATUS_VALIDATED) {
                    $errors[] = 'Ce contrat n\'est pas au statu "Validé"';
                    return 0;
                }

                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isClientCompany()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->isCompany();
        }

        return 0;
    }

    public function isSigned()
    {
        if ((int) $this->getData('id_signature')) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                return $signature->isSigned();
            }
        }

        return 0;
    }

    public function areLinesEditable()
    {
        return 1;
    }

    // Getters params : 

    public function getActionsButtons()
    {
        $buttons = Array();

        // Valider : 
        if ($this->isActionAllowed('validate') && $this->canSetAction('validate')) {
            $buttons[] = array(
                'label'   => 'Valider',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('validate', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la validation du contrat'
                ))
            );
        }

        $line_instance = BimpObject::getInstance('bimpcontrat', 'BCT_ContratLine');

        if ($this->isLoaded() && (int) $this->getData('statut') == 1) {
            if ($line_instance->canSetAction('periodicFacProcess')) {
                $buttons[] = array(
                    'label'   => 'Traiter les facturations',
                    'icon'    => 'fas_file-invoice-dollar',
                    'onclick' => $line_instance->getJsActionOnclick('periodicFacProcess', array(
                        'operation_type' => 'fac',
                        'id_contrat'     => $this->id
                            ), array(
                        'form_name'        => 'periodic_process',
                        'on_form_submit'   => 'function($form, extra_data) { return BimpContrat.onPeriodicFacProcessFormSubmit($form, extra_data); }',
                        'use_bimpdatasync' => true,
                        'use_report'       => true
                    ))
                );
            }

            if ($line_instance->canSetAction('periodicAchatProcess')) {
                $buttons[] = array(
                    'label'   => 'Traiter les achats',
                    'icon'    => 'fas_cart-arrow-down',
                    'onclick' => $line_instance->getJsActionOnclick('periodicAchatProcess', array(
                        'operation_type' => 'achat',
                        'id_contrat'     => $this->id
                            ), array(
                        'form_name'        => 'periodic_process',
                        'on_form_submit'   => 'function($form, extra_data) { return BimpContrat.onPeriodicAchatProcessFormSubmit($form, extra_data); }',
                        'use_bimpdatasync' => true,
                        'use_report'       => true
                    ))
                );
            }
        }
        $id_group = BimpCore::getUserGroupId('console');
        $note = BimpObject::getInstance("bimpcore", "BimpNote");

        if ($id_group) {
            $buttons[] = array(
                'label'   => 'Message console',
                'icon'    => 'far_paper-plane',
                'onclick' => $note->getJsActionOnclick('repondre', array(
                    "obj_type"      => "bimp_object",
                    "obj_module"    => $this->module,
                    "obj_name"      => $this->object_name,
                    "id_obj"        => $this->id,
                    "type_dest"     => $note::BN_DEST_GROUP,
                    "fk_group_dest" => $id_group,
                    "content"       => ""
                        ), array(
                    'form_name' => 'rep'
                ))
            );
        }

        $id_group = BimpCore::getUserGroupId('facturation');
        if ($id_group) {
            $buttons[] = array(
                'label'   => 'Message facturation',
                'icon'    => 'far_paper-plane',
                'onclick' => $note->getJsActionOnclick('repondre', array(
                    "obj_type"      => "bimp_object",
                    "obj_module"    => $this->module,
                    "obj_name"      => $this->object_name,
                    "id_obj"        => $this->id,
                    "type_dest"     => $note::BN_DEST_GROUP,
                    "fk_group_dest" => $id_group,
                    "content"       => ''
                        ), array(
                    'form_name' => 'rep'
                ))
            );
        }

        if ($this->isActionAllowed('mergeContrat') && $this->canSetAction('mergeContrat')) {
            $buttons[] = array(
                'label'   => 'Fusioner un contrat',
                'icon'    => 'fas_object-group',
                'onclick' => $this->getJsActionOnclick('mergeContrat', array(), array(
                    'form_name' => 'merge'
                ))
            );
        }

        return $buttons;
    }

    public function getDirOutput()
    {
        global $conf;
        return $conf->contract->dir_output;
    }

    public function getDefaultListHeaderButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('CorrectAbosStocksAll') && $this->canSetAction('CorrectAbosStocksAll')) {
            $buttons[] = array(
                'label'   => 'Corriger stocks abos (Admin)',
                'icon'    => 'fas_cogs',
                'onclick' => $this->getJsActionOnclick('CorrectAbosStocksAll', array(), array())
            );
        }

        return $buttons;
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Lignes',
                'icon'    => 'fas_bars',
                'onclick' => $this->getJsLoadModalView('lines', 'Lignes du contrat ' . $this->getRef())
            );

            $buttons[] = array(
                'label'   => 'Synthèse',
                'icon'    => 'fas_list-alt',
                'onclick' => $this->getJsLoadModalView('synthese', 'Synthèse du contrat ' . $this->getRef())
            );
        }

        return $buttons;
    }

    // Getters données : 

    public function getBimpObjectsLinked($not_for = '')
    {
        $this->dol_object->element = 'bimp_contrat';
        $result = parent::getBimpObjectsLinked($not_for = '');
        $this->dol_object->element = 'contrat';

        return $result;
    }

    public function getConditionReglementClient()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0, 'int')) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc_facturation', (int) BimpTools::getPostFieldValue('fk_soc', 0, 'int'), 'int');
            if (!$id_soc) {
                if ((int) $this->getData('fk_soc_facturation') > 0) {
                    $id_soc = $this->getData('fk_soc_facturation');
                } elseif ((int) $this->getData('fk_soc')) {
                    $id_soc = $this->getData('fk_soc');
                }
            }

            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    return (int) $soc->getData('cond_reglement');
                }
            }
        }

        if (isset($this->data['condregl']) && (int) $this->data['condregl']) {
            return (int) $this->data['condregl']; // pas getData() sinon boucle infinie (getCondReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_cond_reglement', 0);
    }

    public function getModeReglementClient()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0, 'int')) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc_facturation', BimpTools::getPostFieldValue('fk_soc', 0, 'int'), 'int');
            if (!$id_soc) {
                if ((int) $this->getData('fk_soc_facturation') > 0) {
                    $id_soc = $this->getData('fk_soc_facturation');
                } elseif ((int) $this->getData('fk_soc')) {
                    $id_soc = $this->getData('fk_soc');
                }
            }

            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    return (int) $soc->getData('mode_reglement');
                }
            }
        }

        if (isset($this->data['moderegl']) && (int) $this->data['moderegl']) {
            return (int) $this->data['moderegl']; // pas getData() sinon boucle infinie (getModeReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_mode_reglement', 0);
    }

    public function getLines($types = null, $ids_only = false, $filters = array())
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpcontrat', 'BCT_ContratLine');

            if (!is_null($types)) {
                if (is_string($types)) {
                    $type_code = $types;
                    $types = array();
                    switch ($type_code) {
                        case 'text':
                            $types[] = BCT_ContratLine::TYPE_TEXT;
                            break;

                        case 'abo':
                        case 'not_text':
                            $types[] = BCT_ContratLine::TYPE_ABO;
                            break;
                    }
                }

                if (is_array($types) && !empty($types)) {
                    $filters['line_type'] = $types;
                }
            }
            if ($ids_only) {
                return $this->getChildrenList('lines', $filters, 'rang', 'asc');
            }

            return $this->getChildrenObjects('lines', $filters, 'rang', 'asc');
        }

        return array();
    }

    public function getFacturesList(&$errors = array())
    {
        $factures = array();

        if ($this->isLoaded($errors)) {
            $this->dol_object->element = 'bimp_contrat';
            $items = BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db, array('facture'));
            $this->dol_object->element = 'contrat';

            foreach ($items as $item) {
                if (!in_array((int) $item['id_object'], $factures)) {
                    $factures[] = (int) $item['id_object'];
                }
            }
        }

        return $factures;
    }

    public function getFactures(&$errors = array())
    {
        $factures = array();
        $list = $this->getFacturesList($errors);

        if (!empty($list)) {
            foreach ($list as $id_facture) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                if (BimpObject::objectLoaded($fac)) {
                    $factures[] = $fac;
                }
            }
        }

        return $factures;
    }

    public function getCommandesFournList(&$errors = array())
    {
        $commandes = array();

        if ($this->isLoaded($errors)) {
            $this->dol_object->element = 'bimp_contrat';
            $items = BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db, array('order_supplier'));
            $this->dol_object->element = 'contrat';

            foreach ($items as $item) {
                if (!in_array((int) $item['id_object'], $commandes)) {
                    $commandes[] = (int) $item['id_object'];
                }
            }
        }

        return $commandes;
    }

    public function getCommandesFourn(&$errors = array())
    {
        $commandes = array();
        $list = $this->getCommandesFournList($errors);

        if (!empty($list)) {
            foreach ($list as $id_cf) {
                $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
                if (BimpObject::objectLoaded($cf)) {
                    $commandes[] = $cf;
                }
            }
        }

        return $commandes;
    }

    public function getPropalesOriginList()
    {
        if ($this->isLoaded()) {
            $this->dol_object->element = 'bimp_contrat';
            $items = BimpTools::getDolObjectLinkedObjectsListByTypes($this->dol_object, $this->db, array('propal'));
            $this->dol_object->element = 'contrat';

            if (isset($items['propal'])) {
                return $items['propal'];
            }
        }

        return array();
    }

    public function getCommercialId($params = array(), &$is_superior = false, &$is_default = false)
    {
        $id_user = (int) $this->getData('fk_commercial_suivi');
        if ($id_user) {
            if (isset($params['check_active']) && (int) $params['check_active']) {
                if ((int) $this->db->getValue('user', 'statut', 'rowid = ' . $id_user)) {
                    return $id_user;
                }
            } else {
                return $id_user;
            }
        }

        return parent::getCommercialId($params, $is_superior, $is_default);
    }

    // Getters Array :

    public function getClientRibsArray()
    {
        $id_client = (int) $this->getData('fk_soc_facturation');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }
        $entity = 1;
        if ($this->getData('entity') > 1)
            $entity = $this->getData('entity');
        return BimpCache::getSocieteRibsArray($id_client, true, $entity);
    }

    public function getContratsToMergeArray()
    {
        $contrats = array();

        if ($this->isLoaded()) {
            $rows = $this->getList(array(
                'rowid'              => array('operator' => '!=', 'value' => $this->id),
                'fk_soc'             => $this->getData('fk_soc'),
                'fk_soc_facturation' => $this->getData('fk_soc_facturation'),
                'entrepot'           => $this->getData('entrepot'),
                'secteur'            => $this->getData('secteur'),
                'expertise'          => $this->getData('expertise')
                    ), null, null, 'rowid', 'DESC', 'array', array(
                'rowid', 'ref'
            ));

            foreach ($rows as $r) {
                $contrats[(int) $r['rowid']] = $r['ref'];
            }
        }

        return $contrats;
    }

    public static function getClientAbosLinesArray($id_client, $filters = array(), $include_empty = true, $empty_label = '', $display_refs = false)
    {
        $lines = array();

        if ($include_empty) {
            $lines[0] = $empty_label;
        }

        BimpObject::loadClass('bimpcontrat', 'BCT_ContratLine');

        $fields = array('a.rowid as id_line', 'c.ref', 'c.label');

        if ($display_refs) {
            $fields[] = 'p.ref as prod_ref';
        }

        $filters = BimpTools::merge_array(array(
                    'c.fk_soc'         => $id_client,
                    'c.version'        => 2,
                    'a.line_type'      => BCT_ContratLine::TYPE_ABO,
                    'a.id_linked_line' => 0,
                    'a.id_parent_line' => 0,
                    'a.statut'         => 4,
                    'a.date_cloture'   => 'IS_NULL'
                        ), $filters);

        $joins = array(
            'c' => array(
                'table' => 'contrat',
                'on'    => 'c.rowid = a.fk_contrat'
            )
        );

        if ($display_refs) {
            $joins['p'] = array(
                'table' => 'product',
                'on'    => 'p.rowid = a.fk_product'
            );
        }

        $sql = BimpTools::getSqlFullSelectQuery('contratdet', $fields, $filters, $joins);

        $rows = self::getBdb()->executeS($sql, 'array');

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $r['id_line']);
                if (BimpObject::objectLoaded($line)) {
                    $label = 'Contrat ' . $r['ref'];

                    if ($r['label']) {
                        $label .= ' (' . $r['label'] . ')';
                    }

                    $label .= ' - ligne n° ' . $line->getData('rang');

                    if ($display_refs) {
                        $label .= ' (' . $r['prod_ref'] . ')';
                    }

                    $label .= ' - ' . $line->displayPeriods(true);
                    $lines[$line->id] = $label;
                }
            }
        } else {
            die(self::getBdb()->err());
        }

        return $lines;
    }

    // Rendus HTML : 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . BimpTools::printDate($this->getData('datec'), 'strong') . '</strong>';

            $user = $this->getChildObject('user_create');
            if (BimpObject::objectLoaded($user)) {
                $html .= ' par ' . $user->getLink();
            }

            $html .= '</div>';

            if ((int) $this->getData('statut') > 0) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validé le <strong>' . BimpTools::printDate($this->getData('date_validate'), 'strong') . '</strong>';

                $user = $this->getChildObject('user_validate');
                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par ' . $user->getLink();
                }

                $html .= '</div>';
            }

            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $html .= '<div style="margin-top: 10px; font-size: 12px;">';
                $html .= 'Client : ' . $client->getLink();
                $html .= '</div>';
            }

            $this->dol_object->element = 'bimp_contrat';
            $items = BimpTools::getDolObjectLinkedObjectsListByTypes($this->dol_object, $this->db, array('propal'));
            $this->dol_object->element = 'contrat';
            if (isset($items['propal'])) {
                foreach ($items['propal'] as $id) {
                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id);
                    $items = BimpTools::getDolObjectLinkedObjectsList($propal->dol_object, $this->db, array('commande'));
                    //                print_r($items);
                    foreach ($items as $id) {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id['id_object']);
                        if ($commande->isLoaded() && in_array((int) $commande->getData('fk_statut'), array(0, 1, 2))) {
                            $html .= BimpRender::renderAlerts('Attention, le devis lié a donné lieu également à une commande ' . $commande->getLink(), 'warning');
                        }
                    }
                }
            }
        }

        return $html;
    }

    public function renderLinkedObjectsTable($htmlP = '', $excluded_types = array())
    {
        $this->dol_object->element = 'bimp_contrat';

        $html = parent::renderLinkedObjectsTable($htmlP, array('facture', 'order_supplier'));

        $this->dol_object->element = 'contrat';

        return $html;
    }

    public function renderContacts($nature = 0, $code = '', $input_name = '')
    {
        $html = '';
        if ($input_name != '') {
            $html .= '<span class="btn btn-default" onclick="reloadParentInput($(this), \'' . $input_name . '\');">';
            $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
            $html .= '</span>';
        }

        $html .= '<table class="bimp_list_table">';

        $html .= '<thead>';
        $html .= '<tr>';

        if ($nature == 0) {
            $html .= '<th>Nature</th>';
        }

        $html .= '<th>Tiers</th>';
        $html .= '<th>Utilisateur / Contact</th>';
        if ($code == '') {
            $html .= '<th>Type de contact</th>';
        }
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list_' . $nature . '_' . $code;

        $html .= '<tbody id="' . $list_id . '">';
        $html .= $this->renderContactsList($nature, $code, $list_id);

        $html .= '</tbody>';
        $html .= '</table>';

        $filtre = array(
            'id_client' => (int) $this->getData('fk_soc'),
            'nature'    => $nature,
            'code'      => $code,
            'list_id'   => $list_id
        );

        if ($nature && $code != '') {
            if ($nature == 'internal') {
                $filtre['user_type_contact'] = $this->getIdTypeContact($nature, $code);
            } elseif ($nature == 'external') {
                $filtre['tiers_type_contact'] = $this->getIdTypeContact($nature, $code);
            }
        }

        return BimpRender::renderPanel('Liste des contacts', $html, '', array(
                    'type'           => 'secondary',
                    'icon'           => 'user-circle',
                    'header_buttons' => array(
                        array(
                            'label'       => 'Ajouter un contact',
                            'icon_before' => 'plus-circle',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => $this->getJsActionOnclick('addContact', $filtre, array(
                                    'form_name'        => 'contact',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                                ))
                            )
                        )
                    )
        ));
    }

    public function renderSyntheseTab($refresh_btn = true)
    {
        $html = '';

        if ($refresh_btn) {
            $onclick = $this->getJsLoadCustomContent('renderSyntheseTab', '$(this).findParentByClass(\'nav_tab_ajax_result\')', array(), array('button' => '$(this)'));

            $html .= '<div class="buttonsContainer align-right" style="margin-bottom: 10px">';
            $html .= '<span class="btn btn-default refreshContratSyntheseButton" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
            $html .= '</span>';
            $html .= '</div>';
        }

        $lines = $this->getLines('abo', false, array(
            'fk_product'    => array(
                'operator' => '>',
                'value'    => 0
            ),
            'product:type2' => array(
                'operator' => '!=',
                'value'    => 20
            )
        ));

        if (empty($lines)) {
            $html .= BimpRender::renderAlerts('Aucune ligne d\'abonnement enregistrée', 'warning');
        } else {
            $prods = array();

            foreach ($lines as $line) {
                $id_product = (int) $line->getData('fk_product');
                if (!$id_product) {
                    continue;
                }

                if (!isset($prods[$id_product])) {
                    $prods[$id_product] = array();
                }

                $id_linked_line = (int) $line->getData('id_linked_line');
                if ($id_linked_line) {
                    if (!isset($prods[$id_product][$id_linked_line])) {
                        $prods[$id_product][$id_linked_line] = array(
                            'line'         => null,
                            'linked_lines' => array()
                        );
                    }

                    $prods[$id_product][$id_linked_line]['linked_lines'][] = $line;
                } else {
                    if (!isset($prods[$id_product][$line->id])) {
                        $prods[$id_product][$line->id] = array(
                            'line'         => $line,
                            'linked_lines' => array()
                        );
                    } else {
                        $prods[$id_product][$line->id]['line'] = $line;
                    }
                }
            }

            if (!empty($prods)) {
                $now = date('Y-m-d');
                $dt = new DateTime();
                $dt->add(new DateInterval('P1M'));
                $one_monce_from_now = $dt->format('Y-m-d');

                $headers = array(
                    'prod'      => 'Produit',
                    'units'     => 'Unités',
                    'qty'       => 'Qté totale',
                    'echeances' => 'Echéances',
                    'buttons'   => ''
                );

                $lines_headers = array(
                    'linked'  => array('label' => '', 'colspan' => 0),
                    'desc'    => array('label' => 'Ligne', 'colspan' => 2),
                    'statut'  => 'statut',
                    'dates'   => 'Dates',
                    'fac'     => 'Facturation',
                    'achats'  => 'Achats',
                    'units'   => 'Unités',
                    'qty'     => 'Qté totale',
                    'pu_ht'   => 'PU HT',
                    'buttons' => ''
                );

                $rows = array();
                $linked_icon = BimpRender::renderIcon('fas_level-up-alt', '', 'transform: rotate(90deg);font-size: 22px;');

                foreach ($prods as $id_prod => $prod_lines) {
                    $echeances = array();

                    $units = array(
                        'active'   => 0,
                        'inactive' => 0,
                        'closed'   => 0
                    );

                    $qties = array(
                        'active'   => 0,
                        'inactive' => 0,
                        'closed'   => 0
                    );

                    $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);
                    $desc = '';
                    $prod_duration = 0;
                    if (BimpObject::objectLoaded($prod)) {
                        $prod_duration = (int) $prod->getData('duree');
                        $desc .= $prod->getLink() . '<br/><b>' . $prod->getName() . '</b><br/>';
                        $desc .= '<span style="color: #999999; font-size: 11px">' . BimpRender::renderIcon('fas_calendar-check', 'iconLeft') . 'Durée unitaire : ' . ($prod_duration ? $prod_duration . ' mois' : 'Non définie') . '</span>';
                    } else {
                        $prod_duration = 1;
                        $desc .= '<span class="danger">Le produit #' . $id_prod . ' n\'existe plus</span>';
                    }

                    $lines_content = '';
                    $lines_rows = array();

                    foreach ($prod_lines as $id_line => $line_data) {
                        $linked_lines = array();

                        if (is_object($line_data['line'])) {
                            $linked_lines[] = $line_data['line'];
                        }

                        if (!empty($line_data['linked_lines'])) {
                            foreach ($line_data['linked_lines'] as $line) {
                                $linked_lines[] = $line;
                            }
                        }

                        foreach ($linked_lines as $idx => $line) {
                            $is_sub_line = ((int) $line->id !== (int) $id_line);
                            $is_last = (($idx + 1) >= count($linked_lines));

                            $duration = (int) $line->getData('duration');
                            if (!$duration) {
                                $duration = 1;
                            }

                            $qty = (float) $line->getData('qty');
                            $nb_units = ($qty / $duration) * $prod_duration;
                            $line_statut = (int) $line->getData('statut');
                            $line_statut_code = '';

                            switch ($line_statut) {
                                case -2:
                                case -1:
                                case 0:
                                    $units['inactive'] += $nb_units;
                                    $qties['inactive'] += $qty;
                                    $line_statut_code = 'inactive';
                                    break;

                                case 4:
                                    $units['active'] += $nb_units;
                                    $qties['active'] += $qty;
                                    $line_statut_code = 'active';
                                    break;

                                case 5:
                                    $units['closed'] += $nb_units;
                                    $qties['closed'] += $qty;
                                    $line_statut_code = 'closed';
                                    break;
                            }

                            $dates = '';

                            if ($line_statut > 0) {
                                $dates .= 'Du <b>' . date('d / m / Y', strtotime($line->getData('date_ouverture'))) . '</b>';
                                $dates .= '<br/>Au <b>' . date('d / m / Y', strtotime($line->getData('date_fin_validite'))) . '</b>';

                                if (!(int) $line->getData('id_line_renouv')) {
                                    $date_fin = date('Y-m-d', strtotime($line->getData('date_fin_validite')));
                                    if (!in_array($date_fin, $echeances)) {
                                        $echeances[] = $date_fin;
                                    }
                                }
                            } else {
                                $dates .= 'Ouverture prévue : ';
                                $date_ouverture_prevue = $line->getData('date_ouverture_prevue');
                                if ($date_ouverture_prevue) {
                                    $dates .= '<b>' . date('d / m / Y', strtotime($date_ouverture_prevue)) . '</b>';
                                } else {
                                    $dates .= 'non définie';
                                }
                            }

                            $id_line_renouv = (int) $line->getData('id_line_renouv');
                            if ($id_line_renouv) {
                                $line_renouv = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line_renouv);

                                if (BimpObject::objectLoaded($line_renouv)) {
                                    $dates .= '<br/><span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft');
                                    $dates .= 'Renouvellé (ligne n°' . $line_renouv->getData('rang') . ')</span>';
                                }
                            }

                            $line_desc = '<b>N° ' . $line->getData('rang') . '</b>';

                            $id_parent_line = (int) $line->getData('id_parent_line');
                            if ($id_parent_line) {
                                $parent_line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_parent_line);
                                if (BimpObject::objectLoaded($parent_line)) {
                                    $line_desc .= '<br/><span class="small" style="color: #888888">(Bundle l. n° ' . $parent_line->getData('rang') . ')</span>';
                                }
                            }

                            $description = $line->getData('description');
                            if ($description) {
                                $line_desc .= '<br/>' . BimpRender::renderExpandableText($description, 120, 11, 180);
                            }

                            $buttons_html = '';

                            foreach ($line->getListExtraBtn() as $button) {
                                $buttons_html .= BimpRender::renderRowButton($button['label'], $button['icon'], $button['onclick']);
                            }

                            $lines_rows[] = array(
                                'show_tr'         => ($line_statut_code != 'closed' ? 1 : 0),
                                'row_style'       => 'border-bottom-color: #' . ($is_last ? '595959' : 'ccc') . ';border-bottom-width: ' . ($is_last ? '2px;' : '1px;'),
                                'row_extra_class' => 'status_' . $line_statut_code,
                                'desc'            => array('content' => $line_desc, 'colspan' => ($is_sub_line ? 1 : 2)),
                                'linked'          => array('content' => ($is_sub_line ? $linked_icon : ''), 'colspan' => ($is_sub_line ? 1 : 0)),
                                'statut'          => $line->displayDataDefault('statut'),
                                'dates'           => $dates,
                                'fac'             => $line->displayFacInfos(),
                                'achats'          => $line->displayAchatInfos(false),
                                'units'           => $nb_units,
                                'qty'             => $qty,
                                'pu_ht'           => $line->displayDataDefault('subprice'),
                                'buttons'         => $buttons_html
                            );
                        }
                    }

//                    return '<pre>' . print_r($lines_rows, 1) . '</pre>';

                    $units_html = '';

                    if ($units['active'] != 0) {
                        $units_html .= '<span class="success">Actives : ' . $units['active'] . '</span><br/>';
                    }
                    if ($units['inactive'] != 0) {
                        $units_html .= '<span class="warning">Inactives : ' . $units['inactive'] . '</span><br/>';
                    }
                    if ($units['closed'] != 0) {
                        $units_html .= '<span class="danger">Fermées : ' . $units['closed'] . '</span>';
                    }

                    $qties_html = '';
                    if ($qties['active'] != 0) {
                        $qties_html .= '<span class="success">Actives : ' . $qties['active'] . '</span><br/>';
                    }
                    if ($qties['inactive'] != 0) {
                        $qties_html .= '<span class="warning">Inactives : ' . $qties['inactive'] . '</span><br/>';
                    }
                    if ($qties['closed'] != 0) {
                        $qties_html .= '<span class="danger">Fermées : ' . $qties['closed'] . '</span>';
                    }

                    $echeances_html = '';
                    if (!empty($echeances)) {
                        sort($echeances);
                        foreach ($echeances as $echeance) {
                            $class = ($echeance > $one_monce_from_now ? 'success' : ($echeance > $now ? 'warning' : 'danger'));
                            $echeances_html .= ($echeances_html ? '<br/>' : '') . '<span class="' . $class . '">' . date('d / m / Y', strtotime($echeance)) . '</span>';
                        }
                    } else {
                        $echeances_html .= 'Aucune';
                    }

                    $detail_btn = '<span class="openCloseButton open-content" data-parent_level="3" data-content_extra_class="prod_' . $id_prod . '_detail">';
                    $detail_btn .= 'Détail';
                    $detail_btn .= '</span>';

                    $rows[] = array(
                        'prod'      => $desc,
                        'units'     => $units_html,
                        'qty'       => $qties_html,
                        'echeances' => $echeances_html,
                        'buttons'   => $detail_btn
                    );

                    $lines_content .= '<div class="prod_sublines_container" style="padding: 10px 15px; margin-left: 15px; border-left: 3px solid #777">';
                    $lines_content .= '<div style="margin-bottom: 5px;">';
                    $lines_content .= BimpInput::renderInput('check_list', 'prod_' . $prod->id . '_display_filters', array('active', 'inactive'), array(
                                'items'              => array(
                                    'active'   => 'Actives',
                                    'inactive' => 'Inactives',
                                    'closed'   => 'Fermées'
                                ),
                                'search_input'       => 0,
                                'select_all_buttons' => 0,
                                'inline'             => 1,
                                'onchange'           => 'BimpContrat.onSyntheseProdLineDisplayFilterChange($(this));'
                    ));
                    $lines_content .= '</div>';

                    $lines_content .= BimpRender::renderBimpListTable($lines_rows, $lines_headers, array(
                                'is_sublist' => true
                    ));
                    $lines_content .= '</div>';

                    $rows[] = array(
                        'tr_style'         => 'display: none',
                        'row_extra_class'  => 'openCloseContent prod_' . $id_prod . '_detail',
                        'full_row_content' => $lines_content
                    );
                }

                $html .= BimpRender::renderBimpListTable($rows, $headers, array());
            }
        }

        return $html;
    }

    public function renderFacturesTab()
    {
        $html = '';

        $onclick = $this->getJsLoadCustomContent('renderFacturesTab', '$(this).findParentByClass(\'nav_tab_ajax_result\')', array(), array('button' => '$(this)'));

        $html .= '<div class="buttonsContainer align-right" style="margin-bottom: 10px">';
        $html .= '<span class="btn btn-default refreshContratFacturesButton" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
        $html .= '</span>';
        $html .= '</div>';

        $errors = array();
        $factures = $this->getFactures($errors);

        if (!empty($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        } elseif (empty($factures)) {
            $html .= BimpRender::renderAlerts('Aucune facture liée à ce contrat', 'warning');
        } else {
            $headers = array(
                'facture'     => 'Facture',
                'status'      => 'Statut',
                'total_ht'    => 'Total HT',
                'total_ttc'   => 'Total TTC',
                'date_create' => 'Créée le',
                'user_create' => 'Créée par',
                'detail'      => ''
            );

            $lines_headers = array(
                'desc'      => 'Description',
                'qty'       => 'Qté',
                'pu_ht'     => 'PU HT',
                'total_ht'  => 'Total HT',
                'total_ttc' => 'Total TTC'
            );

            $rows = array();

            $total_ht = 0;
            $total_ttc = 0;

            foreach ($factures as $facture) {
                if (BimpObject::objectLoaded($facture)) {
                    $user_author = $facture->getChildObject('user_author');

                    $total_ht += $facture->getTotalHt();
                    $total_ttc += $facture->getTotalTtc();

                    $detail_btn = '<span class="openCloseButton open-content" data-parent_level="3" data-content_extra_class="fac_' . $facture->id . '_detail">';
                    $detail_btn .= 'Détail';
                    $detail_btn .= '</span>';

                    $rows[] = array(
                        'facture'     => $facture->getLink(),
                        'status'      => $facture->displayDataDefault('fk_statut'),
                        'total_ht'    => BimpTools::displayMoneyValue($facture->getTotalHt()),
                        'total_ttc'   => BimpTools::displayMoneyValue($facture->getTotalTtc()),
                        'date_create' => $facture->displayDataDefault('datec'),
                        'user_create' => (BimpObject::objectLoaded($user_author) ? $user_author->getLink() : ''),
                        'detail'      => $detail_btn
                    );

                    $lines_content = '';

                    $contrat_lines = $this->getLines('not_text', true);

                    $fac_lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_FactureLine', array(
                                'id_obj'             => $facture->id,
                                'linked_object_name' => array('contrat_line', 'contrat_line_regul'),
                                'linked_id_object'   => $contrat_lines
                                    ), 'position', 'asc');

                    if (empty($fac_lines)) {
                        $lines_content .= BimpRender::renderAlerts('Aucune ligne liée à ce contrat dans cette facture');
                    } else {
                        $lines_rows = array();
                        foreach ($fac_lines as $fac_line) {
                            $desc = '';
                            if ($fac_line->getData('linked_object_name') == 'contrat_line_regul') {
                                $desc .= '<span class="important">[REGULARISATION]</span><br/>';
                            }
                            $desc .= $fac_line->displayLineData('desc_light');
                            $lines_rows[] = array(
                                'desc'      => $desc,
                                'qty'       => $fac_line->displayLineData('qty'),
                                'pu_ht'     => $fac_line->displayLineData('pu_ht'),
                                'total_ht'  => $fac_line->displayLineData('total_ht'),
                                'total_ttc' => $fac_line->displayLineData('total_ttc'),
                            );
                        }

                        $lines_content .= '<div style="padding: 10px 15px; margin-left: 15px; border-left: 3px solid #777">';
                        $lines_content .= BimpRender::renderBimpListTable($lines_rows, $lines_headers, array(
                                    'is_sublist' => true
                        ));
                        $lines_content .= '</div>';
                    }

                    $rows[] = array(
                        'tr_style'         => 'display: none',
                        'row_extra_class'  => 'openCloseContent fac_' . $facture->id . '_detail',
                        'full_row_content' => $lines_content
                    );
                }
            }

            $html .= BimpRender::renderBimpListTable($rows, $headers);

            $html .= '<table style="margin-top: 30px; width: 300px;" class="bimp_list_table">';
            $html .= '<tbody class="headers_col">';
            $html .= '<tr>';
            $html .= '<th>Total HT Facturé</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_ht) . '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Total TTC Facturé</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc) . '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public function renderAchatsTab()
    {
        $html = '';

        $onclick = $this->getJsLoadCustomContent('renderAchatsTab', '$(this).findParentByClass(\'nav_tab_ajax_result\')', array(), array('button' => '$(this)'));

        $html .= '<div class="buttonsContainer align-right" style="margin-bottom: 10px">';
        $html .= '<span class="btn btn-default refreshContratAchatsButton" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
        $html .= '</span>';
        $html .= '</div>';

        $errors = array();
        $commandes = $this->getCommandesFourn();

        if (!empty($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        } elseif (empty($commandes)) {
            $html .= BimpRender::renderAlerts('Aucune commande fournisseur liée à ce contrat', 'warning');
        } else {
            $headers = array(
                'facture'     => 'Commande',
                'status'      => 'Statut',
                'total_ht'    => 'Total HT',
                'total_ttc'   => 'Total TTC',
                'date_create' => 'Créée le',
                'user_create' => 'Créée par',
                'detail'      => ''
            );

            $lines_headers = array(
                'desc'      => 'Description',
                'qty'       => 'Qté',
                'received'  => 'Qté réceptionnée',
                'pu_ht'     => 'PU HT',
                'total_ht'  => 'Total HT',
                'total_ttc' => 'Total TTC'
            );

            $rows = array();

            $total_ht = 0;
            $total_ttc = 0;

            foreach ($commandes as $cf) {
                if (BimpObject::objectLoaded($cf)) {
                    $user_author = $cf->getChildObject('user_author');

                    $total_ht += $cf->getTotalHt();
                    $total_ttc += $cf->getTotalTtc();

                    $detail_btn = '<span class="openCloseButton open-content" data-parent_level="3" data-content_extra_class="cf_' . $cf->id . '_detail">';
                    $detail_btn .= 'Détail';
                    $detail_btn .= '</span>';

                    $rows[] = array(
                        'facture'     => $cf->getLink(),
                        'status'      => $cf->displayDataDefault('fk_statut'),
                        'total_ht'    => BimpTools::displayMoneyValue($cf->getTotalHt()),
                        'total_ttc'   => BimpTools::displayMoneyValue($cf->getTotalTtc()),
                        'date_create' => $cf->displayDataDefault('date_creation'),
                        'user_create' => (BimpObject::objectLoaded($user_author) ? $user_author->getLink() : ''),
                        'detail'      => $detail_btn
                    );

                    $lines_content = '';

                    $contrat_lines = $this->getLines('not_text', true);

                    $cf_lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_CommandeFournLine', array(
                                'id_obj'             => $cf->id,
                                'linked_object_name' => 'contrat_line',
                                'linked_id_object'   => $contrat_lines
                                    ), 'position', 'asc');

                    if (empty($cf_lines)) {
                        $lines_content .= BimpRender::renderAlerts('Aucune ligne liée à ce contrat dans cette commande fournisseur');
                    } else {
                        $lines_rows = array();
                        foreach ($cf_lines as $cf_line) {
                            $received_qty = (float) $cf_line->getReceivedQty(null, true);
                            $received_class = ($received_qty > 0 ? ($received_qty >= $qty ? 'success' : 'warning') : 'danger');
                            $lines_rows[] = array(
                                'desc'      => $cf_line->displayLineData('desc_light'),
                                'qty'       => $cf_line->displayLineData('qty'),
                                'received'  => '<span class="badge badge-' . $received_class . '">' . $received_qty . '</span>',
                                'pu_ht'     => $cf_line->displayLineData('pu_ht'),
                                'total_ht'  => $cf_line->displayLineData('total_ht'),
                                'total_ttc' => $cf_line->displayLineData('total_ttc'),
                            );
                        }

                        $lines_content .= '<div style="padding: 10px 15px; margin-left: 15px; border-left: 3px solid #777">';
                        $lines_content .= BimpRender::renderBimpListTable($lines_rows, $lines_headers, array(
                                    'is_sublist' => true
                        ));
                        $lines_content .= '</div>';
                    }

                    $rows[] = array(
                        'tr_style'         => 'display: none',
                        'row_extra_class'  => 'openCloseContent cf_' . $cf->id . '_detail',
                        'full_row_content' => $lines_content
                    );
                }
            }

            $html .= BimpRender::renderBimpListTable($rows, $headers);

            $html .= '<table style="margin-top: 30px; width: 300px;" class="bimp_list_table">';
            $html .= '<tbody class="headers_col">';
            $html .= '<tr>';
            $html .= '<th>Total HT achats</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_ht) . '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Total TTC achats</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc) . '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public static function renderAbonnementsTabs($params)
    {
        $html = '';

        $params = BimpTools::overrideArray(array(
                    'id_contrat' => 0,
                    'id_client'  => 0,
                    'id_fourn'   => 0,
                    'id_product' => 0
                        ), $params);

        $tabs = array();

        $line_instance = BimpObject::getInstance('bimpcontrat', 'BCT_ContratLine');

        // Overview: 
        $content = '<div class="periodic_operations_overview_content">';
        $content .= $line_instance->renderPeriodicOperationsToProcessOverview($params);
        $content .= '</div>';

        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-4">';
        $title = BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'A traiter aujourd\'hui';

        $footer = '<div style="text-align: right">';
        $onclick = $line_instance->getJsLoadCustomContent('renderPeriodicOperationsToProcessOverview', '$(this).findParentByClass(\'panel\').find(\'.periodic_operations_overview_content\')', array($params));
        $footer .= '<span class="btn btn-default" onclick="' . $onclick . '">';
        $footer .= 'Actualiser' . BimpRender::renderIcon('fas_redo', 'iconRight');
        $footer .= '</span>';
        $footer .= '</div>';

        $html .= BimpRender::renderPanel($title, $content, $footer, array(
                    'type' => 'secondary'
        ));
        $html .= '</div>';
        $html .= '</div>';

        if (!(int) $params['id_fourn']) {
            // Facturations: 
            $tabs[] = array(
                'id'            => 'fac_periods_tab',
                'title'         => BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Facturations périodiques',
                'ajax'          => 1,
                'ajax_callback' => $line_instance->getJsLoadCustomContent('renderPeriodicOperationsList', '$(\'#fac_periods_tab .nav_tab_ajax_result\')', array('fac', $params['id_client'], $params['id_product']), array('button' => ''))
            );
        }

        // Achats: 
        $tabs[] = array(
            'id'            => 'achat_periods_tab',
            'title'         => BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . 'Achats périodiques',
            'ajax'          => 1,
            'ajax_callback' => $line_instance->getJsLoadCustomContent('renderPeriodicOperationsList', '$(\'#achat_periods_tab .nav_tab_ajax_result\')', array('achat', $params['id_client'], $params['id_product'], $params['id_fourn'], $params['id_contrat']), array('button' => ''))
        );

        $html .= BimpRender::renderNavTabs($tabs);

        return $html;
    }

    // Traitements : 

    public function addLinesToFacture($id_facture, $lines_data = null, $commit_each_line = false, $new_qties = true, &$nOk = 0)
    {
        // $commit_each_line : nécessaire pour le traitement des facturations périodiques.
        $errors = array();

        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
        $facture->checkLines();

        if (!BimpObject::objectLoaded($facture)) {
            $errors[] = 'La facture d\'ID ' . $id_facture . ' n\'existe pas';
            return $errors;
        }

        if ((int) $facture->getData('fk_statut') > 0) {
            $errors[] = 'La facture ' . $facture->getRef() . ' n\'est plus au statut brouillon';
            return $errors;
        }

        // Trie des lignes par contrats:
        $orderedLines = array();

        foreach ($lines_data as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $id_line);
            if (BimpObject::objectLoaded($line)) {
                $id_contrat = (int) $line->getData('fk_contrat');
                if (!array_key_exists($id_contrat, $orderedLines)) {
                    $orderedLines[$id_contrat] = array();
                }
                $orderedLines[$id_contrat][(int) $id_line] = $line_data;
            } else {
                $errors[] = 'La ligne de contrat d\'abonnement #' . $id_line . ' n\'existe plus';
            }
        }

        $lines_data = array();

        // Trie des lignes par positions dans le contrat: 
        foreach ($orderedLines as $id_contrat => $lines) {
            $lines_data[$id_contrat] = array();

            $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', (int) $id_contrat);

            if (BimpObject::objectLoaded($contrat)) {
                $rows = $this->db->getRows('contratdet', 'fk_contrat = ' . (int) $id_contrat, null, 'array', array('rowid'), 'rang', 'ASC');
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        if (array_key_exists((int) $r['rowid'], $lines)) {
                            $lines_data[$id_contrat][(int) $r['rowid']] = $lines[(int) $r['rowid']];
                        }
                    }
                }
            } else {
                foreach ($lines as $id_line => $line_data) {
                    $lines_data[$id_contrat][$id_line] = $line_data;
                }
            }
        }

        $assos = array();

        foreach ($lines_data as $id_contrat => $contrat_lines_data) {
            $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', (int) $id_contrat);

            if (BimpObject::objectLoaded($contrat)) {
                // Création de la ligne de l'intitulé du contrat d'origine si nécessaire: 
                $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                            'id_obj'             => (int) $facture->id,
                            'linked_object_name' => 'contrat_origin_label',
                            'linked_id_object'   => (int) $id_contrat
                ));

                if (!BimpObject::objectLoaded($fac_line)) {
                    $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                    $fac_line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => ObjectLine::LINE_TEXT,
                        'linked_id_object'   => (int) $id_contrat,
                        'linked_object_name' => 'contrat_origin_label',
                    ));
                    $fac_line->qty = 1;
                    $fac_line->desc = 'Selon votre contrat ' . $contrat->getRef();
                    $libelle = $contrat->getData('libelle');
                    if ($libelle) {
                        $fac_line->desc .= ' - ' . $libelle;
                    }
                    $fac_line_warnings = array();
                    $fac_line->create($fac_line_warnings, true);
                }
            }

            $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');
            $has_line_ok = false;
            foreach ($contrat_lines_data as $id_line => $line_data) {
                if ($use_db_transactions && $commit_each_line) {
                    $this->db->db->begin();
                }

                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $id_line);
                $line_label = 'Ligne n° ' . $line->getData('rang') . (BimpObject::objectLoaded($contrat) ? ' du contrat ' . $contrat->getRef() : '');

                $line_errors = array();
                $line_warnings = array();
                $line_qty = (float) $line_data['qty'];

                if (!$line_qty) {
                    continue;
                }

                $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                if ((int) $line->getData('line_type') === BCT_ContratLine::TYPE_TEXT) {
                    // Création d'une ligne de texte: 
                    $fac_line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => Bimp_FactureLine::LINE_TEXT,
                        'linked_id_object'   => (int) $line->id,
                        'linked_object_name' => 'contrat_line',
                    ));
                    $fac_line->qty = 1;
                    $fac_line->desc = $line->getData('description');

                    $line_errors = $fac_line->create($line_warnings, true);

                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, $line_label . ' : échec de la création de la ligne de texte');
                        if ($use_db_transactions && $commit_each_line) {
                            $this->db->db->rollback();
                        }
                    } else {
                        if ($use_db_transactions && $commit_each_line) {
                            $nOk++;
                            $this->db->db->commit();
                        }
                    }
                    continue;
                }

                // Création de la ligne de facture: 
                $fac_line->validateArray(array(
                    'id_obj'             => (int) $facture->id,
                    'type'               => ($line->getData('fk_product') > 0 ? Bimp_FactureLine::LINE_PRODUCT : Bimp_FactureLine::LINE_FREE),
                    'remisable'          => 2,
                    'editable'           => (isset($line_data['editable']) ? (int) $line_data['editable'] : 0),
                    'pa_editable'        => 0,
                    'linked_id_object'   => (int) $line->id,
                    'linked_object_name' => 'contrat_line',
                    'hide_in_pdf'        => 0
                ));

                $date_from = null;
                $date_to = null;
                $new_date_next_facture = null;

                $periodicity = (int) $line->getData('fac_periodicity');
                if ((int) $line_data['nb_periods'] && $periodicity) {
                    $periods_data = $line->getPeriodsToBillData();
                    if ($periods_data['date_first_period_start'] == $periods_data['date_next_period_tobill'] &&
                            $periods_data['date_fac_start'] != $periods_data['date_first_period_start']) {
                        // Première période partielle : 
                        $date_from = date('Y-m-d 00:00:00', strtotime($periods_data['date_fac_start']));
                        $dt = new DateTime($periods_data['date_first_period_end']);

                        if ((int) $line_data['nb_periods'] > 1) {
                            $dt->add(new DateInterval('P' . (((int) $line_data['nb_periods'] - 1) * $periodicity) . 'M'));
                        }
                        $date_to = $dt->format('Y-m-d 23:59:59');
                    } else {
                        $dt = new DateTime($periods_data['date_next_period_tobill']);
                        $date_from = $dt->format('Y-m-d 00:00:00');
                        $dt->add(new DateInterval('P' . ((int) $line_data['nb_periods'] * $periodicity) . 'M'));
                        $new_date_next_facture = $dt->format('Y-m-d');
                        $dt->sub(new DateInterval('P1D'));
                        $date_to = $dt->format('Y-m-d 23:59:59');
                    }
                }

                $id_parent_line = $line->getData('id_parent_line');
                if ($id_parent_line) {
                    $id_fac_parent_line = (int) $this->db->getValue('bimp_facture_line', 'id', 'linked_object_name = \'contrat_line\' AND linked_id_object = ' . $id_parent_line . ' AND id_obj = ' . $facture->id);

                    if ($id_fac_parent_line) {
                        $fac_line->set('id_parent_line', $id_fac_parent_line);

                        if ($line->getData('linked_object_name') == 'bundle' || $line->getData('linked_object_name') == 'bundleCorrect') {
                            $fac_line->set('hide_in_pdf', 1);
                        }
                    } elseif ($line->getData('linked_object_name') == 'bundleCorrect') {
                        $errors[] = 'La ligne n° ' . $line->getData('rang') . ' n\'a pas été ajouté à la facture car il s\'agit d\'une compensation d\'un bundle dont la ligne principale n\{a pas été ajoutée à la facture';
                        if ($use_db_transactions && $commit_each_line) {
                            $this->db->db->rollback();
                        }
                        continue;
                    }
                }

                $id_fourn = 0;
                $pa_ht_line = (float) $line->getData('buy_price_ht');
                $pa_ht_fourn = 0;

                $id_pfp = (int) $line->getData('fk_product_fournisseur_price');
                if ($id_pfp) {
                    $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);
                    if (!BimpObject::objectLoaded($pfp)) {
                        $line_errors[] = 'Le prix d\'achat fournisseur #' . $id_pfp . ' n\'existe plus';
                    } else {
                        $id_fourn = $pfp->getData('fk_soc');
                        $pa_ht_fourn = $pfp->getData('price');
                    }
                }

                $fac_line->qty = $line_qty;
                $fac_line->desc = $line->getData('description');
                $fac_line->id_product = (int) $line->getData('fk_product');
                $fac_line->pu_ht = (isset($line_data['subprice']) ? $line_data['subprice'] : $line->getData('subprice'));
                $fac_line->tva_tx = $line->getData('tva_tx');
                $fac_line->pa_ht = ($pa_ht_fourn ? $pa_ht_fourn : $pa_ht_line);
                $fac_line->id_fourn_price = $line->getData('fk_product_fournisseur_price');
                $fac_line->date_from = $date_from;
                $fac_line->date_to = $date_to;
                $fac_line->no_remises_arrieres_auto_create = true;

                $line_errors = $fac_line->create($line_warnings, true);

                if (!count($line_errors)) {
                    // Ajout de la remise: 
                    $remise_percent = (float) $line->getData('remise_percent');
                    if ($remise_percent) {
                        $remises_errors = array();
                        BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemise', array(
                            'id_object_line' => $fac_line->id,
                            'object_type'    => 'facture',
                            'type'           => 1,
                            'percent'        => $remise_percent
                                ), true, $remises_errors);

                        if (count($remises_errors)) {
                            $line_errors[] = BimpTools::getMsgFromArray($remises_errors, 'Echec de l\'ajout de la remise (Ligne de contrat #' . $line->id . ')');
                        }
                    }
                }

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, ucfirst($line_label));
                    if ($use_db_transactions && $commit_each_line) {
                        $this->db->db->rollback();
                    }
                } else {
                    if ($new_date_next_facture) {
                        $line->updateField('date_next_facture', $new_date_next_facture);
                    }

                    $line->checkStatus();

                    $has_line_ok = true;
                    if ($use_db_transactions && $commit_each_line) {
                        $nOk++;
                        $this->db->db->commit();
                    }
                }
            }

            if ($has_line_ok && !in_array($id_contrat, $assos)) {
                $assos[] = $id_contrat;
            }
        }

        // Assos contrats / factures : 
        if (count($assos) && (!count($errors) || ($use_db_transactions && $commit_each_line))) {
            foreach ($assos as $id_contrat) {
                addElementElement('bimp_contrat', 'facture', $id_contrat, $id_facture);
            }
        }

        return $errors;
    }

    public function addLinesToCommandeFourn($id_cf, $lines_data = null, $commit_each_line = false, $new_qties = true, &$nOk = 0)
    {
        // $commit_each_line : nécessaire pour le traitement des achats périodiques.
        $errors = array();

        $cf = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_cf);
        $cf->checkLines();

        if (!BimpObject::objectLoaded($cf)) {
            $errors[] = 'La commande fournisseur d\'ID ' . $id_cf . ' n\'existe pas';
            return $errors;
        }

        if ((int) $cf->getData('fk_statut') > 0) {
            $errors[] = 'La commande fournisseur ' . $cf->getRef() . ' n\'est plus au statut brouillon';
            return $errors;
        }

        // Trie des lignes par contrats:
        $orderedLines = array();

        foreach ($lines_data as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $id_line);
            if (BimpObject::objectLoaded($line)) {
                $id_contrat = (int) $line->getData('fk_contrat');
                if (!array_key_exists($id_contrat, $orderedLines)) {
                    $orderedLines[$id_contrat] = array();
                }
                $orderedLines[$id_contrat][(int) $id_line] = $line_data;
            } else {
                $errors[] = 'La ligne de contrat d\'abonnement #' . $id_line . ' n\'existe plus';
            }
        }

        $lines_data = array();

        // Trie des lignes par positions dans le contrat: 
        foreach ($orderedLines as $id_contrat => $lines) {
            $lines_data[$id_contrat] = array();

            $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', (int) $id_contrat);

            if (BimpObject::objectLoaded($contrat)) {
                $rows = $this->db->getRows('contratdet', 'fk_contrat = ' . (int) $id_contrat, null, 'array', array('rowid'), 'rang', 'ASC');
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        if (array_key_exists((int) $r['rowid'], $lines)) {
                            $lines_data[$id_contrat][(int) $r['rowid']] = $lines[(int) $r['rowid']];
                        }
                    }
                }
            } else {
                foreach ($lines as $id_line => $line_data) {
                    $lines_data[$id_contrat][$id_line] = $line_data;
                }
            }
        }

        $assos = array();

        foreach ($lines_data as $id_contrat => $contrat_lines_data) {
            $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');
            $has_line_ok = false;
            foreach ($contrat_lines_data as $id_line => $line_data) {
                if ($use_db_transactions && $commit_each_line) {
                    $this->db->db->begin();
                }

                $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', (int) $id_line);

                if (!BimpObject::objectLoaded($line)) {
                    continue;
                }

                if ((int) $line->getData('line_type') === BCT_ContratLine::TYPE_TEXT) {
                    continue;
                }

                $line_label = 'Ligne n° ' . $line->getData('rang') . (BimpObject::objectLoaded($contrat) ? ' du contrat ' . $contrat->getRef() : '');

                $line_errors = array();
                $line_warnings = array();
                $line_qty = (float) $line_data['qty'];

                if (!$line_qty) {
                    continue;
                }

                $cf_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');

                // Création de la ligne de commande fourn: 
                $cf_line->validateArray(array(
                    'id_obj'             => (int) $cf->id,
                    'type'               => Bimp_CommandeFournLine::LINE_PRODUCT,
                    'remisable'          => 1,
                    'linked_id_object'   => (int) $line->id,
                    'linked_object_name' => 'contrat_line'
                ));

                $date_from = null;
                $date_to = null;
                $new_date_next_achat = null;

                $periodicity = (int) $line->getData('achat_periodicity');
                $nb_periods = (int) BimpTools::getArrayValueFromPath($line_data, 'nb_periods', 0);

                if ($nb_periods && $periodicity) {
                    $periods_data = $line->getPeriodsToBuyData();
                    if ($periods_data['date_next_achat'] == $periods_data['date_achat_start'] &&
                            $periods_data['date_achat_start'] != $periods_data['date_first_period_start']) {
                        // Première période partielle : 
                        $date_from = date('Y-m-d 00:00:00', strtotime($periods_data['date_achat_start']));
                        $dt = new DateTime($periods_data['date_first_period_end']);

                        if ($nb_periods > 1) {
                            $dt->add(new DateInterval('P' . (($nb_periods - 1) * $periodicity) . 'M'));
                        }
                        $date_to = $dt->format('Y-m-d 23:59:59');
                    } else {
                        $dt = new DateTime($periods_data['date_next_achat']);
                        $date_from = $dt->format('Y-m-d 00:00:00');
                        $dt->add(new DateInterval('P' . ($nb_periods * $periodicity) . 'M'));
                        $new_date_next_achat = $dt->format('Y-m-d');
                        $dt->sub(new DateInterval('P1D'));
                        $date_to = $dt->format('Y-m-d 23:59:59');
                    }
                }

                $pa_ht = (float) BimpTools::getArrayValueFromPath($line_data, 'pa_ht', 0);
                $tva_tx = (float) BimpTools::getArrayValueFromPath($line_data, 'tva_tx', 0);
                $id_pfp = (int) BimpTools::getArrayValueFromPath($line_data, 'id_fourn_price', 0);
                $ref_supplier = BimpTools::getArrayValueFromPath($line_data, 'ref_supplier', '');
                $id_fourn = (int) BimpTools::getArrayValueFromPath($line_data, 'id_fourn', 0);

                $pfp = null;

                if ($id_pfp) {
                    $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', $id_pfp);
                }

                if (!BimpObject::objectLoaded($pfp) && $id_fourn && $pa_ht) {
                    $product = $line->getChildObject('product');
                    $pfp = $product->getLastFournPrice($id_fourn);
                    if (BimpObject::objectLoaded($pfp) && (round($pfp->getData('price'), 2) == round($pa_ht, 2))) {
                        $id_pfp = $pfp->id;

                        if (!$tva_tx) {
                            $tva_tx = $pfp->getData('tva_tx');
                        }
                    }
                }

                if (BimpObject::objectLoaded($pfp)) {
                    if (!$pa_ht) {
                        $pa_ht = $pfp->getData('price');
                    }
                    if (!$tva_tx) {
                        $tva_tx = $pfp->getData('tva_tx');
                    }
                    if (!$ref_supplier) {
                        $ref_supplier = $pfp->getData('ref_fourn');
                    }
                }

                $cf_line->qty = $line_qty;
                $cf_line->desc = $line->getData('description');
                $cf_line->id_product = (int) $line->getData('fk_product');
                $cf_line->date_from = $date_from;
                $cf_line->date_to = $date_to;
                $cf_line->pu_ht = $pa_ht;
                $cf_line->tva_tx = $tva_tx;
                $cf_line->id_fourn_price = $id_pfp;
                $cf_line->ref_supplier = $ref_supplier;

                $line_errors = $cf_line->create($line_warnings, true);

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, ucfirst($line_label));
                    if ($use_db_transactions && $commit_each_line) {
                        $this->db->db->rollback();
                    }
                } else {
                    if ($new_date_next_achat) {
                        $line->updateField('date_next_achat', $new_date_next_achat);
                    }

                    $line->checkStatus();

                    $has_line_ok = true;
                    if ($use_db_transactions && $commit_each_line) {
                        $nOk++;
                        $this->db->db->commit();
                    }
                }
            }

            if ($has_line_ok && !in_array($id_contrat, $assos)) {
                $assos[] = $id_contrat;
            }
        }

        // Assos contrats / factures : 
        if (count($assos) && (!count($errors) || ($use_db_transactions && $commit_each_line))) {
            foreach ($assos as $id_contrat) {
                addElementElement('bimp_contrat', 'order_supplier', $id_contrat, $id_cf);
            }
        }

        return $errors;
    }

    public function createPropal($label, &$errors = array(), &$warnings = array())
    {
        $propal = null;

        if ($this->isLoaded($errors)) {
            $propal = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Propal', array(
                        'fk_soc'            => $this->getData('fk_soc'),
                        'libelle'           => $label,
                        'datep'             => date('Y-m-d'),
                        'entrepot'          => $this->getData('entrepot'),
                        'ef_type'           => $this->getData('secteur'),
                        'expertise'         => $this->getData('expertise'),
                        'rib_client'        => $this->getData('rib_client'),
                        'fk_cond_reglement' => $this->getData('condregl'),
                        'fk_mode_reglement' => $this->getData('moderegl')
                            ), true, $errors, $warnings);
        }

        return $propal;
    }

    // Actions : 

    public function actionValidate($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = 'Contrat validé avec succès';

        global $user;

        if ($this->dol_object->validate($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la validation');
        } else {
            $this->hydrateFromDolObject();

            $this->set('date_validate', date('Y-m-d H:i:s'));
            $this->set('fk_user_validate', $user->id);

            $errors = $this->update($warnings, true);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionMergeContrat($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_contrat_to_import = (int) BimpTools::getArrayValueFromPath($data, 'id_contrat_to_import', 0);

        if (!$id_contrat_to_import) {
            $errors[] = 'Aucun contrat à importer sélectionné';
        } else {
            $contrat_to_import = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat_to_import);

            if (!BimpObject::objectLoaded($contrat_to_import)) {
                $errors[] = 'Le contrat à importer #' . $id_contrat_to_import . ' n\'existe plus';
            } else {
                if ((int) $contrat_to_import->getData('version') !== 2) {
                    $errors[] = 'Le contrat ' . $contrat_to_import->getRef() . ' n\'est pas de type abonnement';
                }

                if ((int) $contrat_to_import->getData('fk_soc') != (int) $this->getData('fk_soc')) {
                    $errors[] = 'Les clients ne correspondent pas';
                }

                if ((int) $contrat_to_import->getData('fk_soc_facturation') != (int) $this->getData('fk_soc_facturation')) {
                    $errors[] = 'Les clients facturation ne correspondent pas';
                }

                if ($contrat_to_import->getData('expertise') != $this->getData('expertise')) {
                    $errors[] = 'Les expertises ne correspondent pas';
                }

                if ($contrat_to_import->getData('entrepot') != $this->getData('entrepot')) {
                    $errors[] = 'Les entrepôts ne correspondent pas';
                }

                if ($contrat_to_import->getData('secteur') != $this->getData('secteur')) {
                    $errors[] = 'Les secteurs ne correspondent pas';
                }

                if (!count($errors)) {
                    // Transefert des lignes:
                    if ($this->db->update('contratdet', array(
                                'fk_contrat' => $this->id
                                    ), 'fk_contrat = ' . $id_contrat_to_import) <= 0) {
                        $errors[] = 'Echec du transfert des lignes - ' . $this->db->err();
                    }

                    // Transfert des fichiers: 
                    if (!count($errors)) {
                        $files = $contrat_to_import->getChildrenObjects('files');

                        foreach ($files as $file) {
                            $err = $file->moveToObject($this);

                            if (count($err)) {
                                $errors[] = BimpTools::getMsgFromArray($err, 'Echec du transfert du fichier "' . $file->getData('file_name') . '.' . $file->getData('file_ext') . '"');
                            }
                        }
                    }

                    // Suppr du contrat importé: 
                    if (!count($errors)) {
                        $this->addObjectLog('Import et fusion du contrat ' . $contrat_to_import->getRef());

                        $del_errors = $contrat_to_import->delete($warnings, true);

                        if (count($del_errors)) {
                            $errors[] = 'Echec de la suppression du contrat à importer';
                        }
                    }

                    // Autres transferts: 
                    if (!count($errors)) {
                        $this->db->update('element_element', array(
                            'fk_source' => $this->id
                                ), '(sourcetype = \'bimp_contrat\' OR sourcetype = \'contrat\') AND fk_source = ' . $id_contrat_to_import);

                        $this->db->update('element_element', array(
                            'fk_target' => $this->id
                                ), '(targettype = \'bimp_contrat\' OR targettype = \'contrat\') AND fk_target = ' . $id_contrat_to_import);

                        $this->db->update('bimpcore_object_log', array(
                            'id_object' => $this->id
                                ), 'obj_module = \'bimpcontrat\' AND obj_name = \'BCT_Contrat\' AND id_object = ' . $id_contrat_to_import);

                        $this->db->update('bimpcore_object_log', array(
                            'id_object' => $this->id
                                ), 'obj_module = \'bimpcontrat\' AND obj_name = \'BCT_Contrat\' AND id_object = ' . $id_contrat_to_import);

                        $this->db->update('bimpcore_note', array(
                            'id_obj' => $this->id
                                ), 'obj_module = \'bimpcontrat\' AND obj_name = \'BCT_Contrat\' AND id_obj = ' . $id_contrat_to_import);

                        $this->db->update('bimpcore_link', array(
                            'src_id' => $this->id
                                ), 'src_module = \'bimpcontrat\' AND src_name = \'BCT_Contrat\' AND src_id = ' . $id_contrat_to_import);

                        $this->db->update('bimpcore_link', array(
                            'linked_id' => $this->id
                                ), 'linked_module = \'bimpcontrat\' AND linked_name = \'BCT_Contrat\' AND linked_id = ' . $id_contrat_to_import);

                        $this->db->update('bimpcore_history', array(
                            'id_object' => $this->id
                                ), 'module = \'bimpcontrat\' AND object = \'BCT_Contrat\' AND id_object = ' . $id_contrat_to_import);

                        $this->db->update('bimpcore_signature', array(
                            'id_obj' => $this->id
                                ), 'obj_module = \'bimpcontrat\' AND obj_name = \'BCT_Contrat\' AND id_obj = ' . $id_contrat_to_import);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCorrectAbosStocksAll($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_entrepot = (int) BimpCore::getConf('abos_id_entrepot', null, 'bimpcontrat');

        if (!$id_entrepot) {
            $errors[] = 'Pas d\'entrepôt défini pour les produit abonnement (param "abos_id_entrepot")';
        } else {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            $where = '.a.`fk_entrepot` != ' . $id_entrepot . ' AND pef.type2 IN (' . implode(',', Bimp_Product::$abonnements_sous_types) . ')';
            $where .= ' AND a.reel != 0';
            $rows = $this->db->getRows('product_stock a', $where, null, 'array', array('a.*'), null, null, array(
                'pef' => array(
                    'table' => 'product_extrafields',
                    'on'    => 'pef.fk_object = a.fk_product'
                )
            ));

            if (is_array($rows) && !empty($rows)) {
                $this->db->db->commitAll();

                foreach ($rows as $r) {
                    $qty = (float) $r['reel'];
                    $id_entrepot_src = (int) $r['fk_entrepot'];
                    $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $r['fk_product']);
                    $code = 'CORRECTION_ABONNEMENT';
                    $label = 'Correction entrepôt (Abonnement)';

                    if (BimpObject::objectLoaded($prod)) {
                        $prod->force_abos_stock_entrepot = true;
                        $this->db->db->begin();
                        $prod_errors = array();
                        $mvt = ($qty > 0 ? 1 : 0);
                        $prod_errors = $prod->correctStocks($id_entrepot_src, abs($qty), $mvt, $code, $label);

                        if (!count($prod_errors)) {
                            $mvt = ($qty > 0 ? 0 : 1);
                            $prod_errors = $prod->correctStocks($id_entrepot, abs($qty), $mvt, $code, $label);
                        }

                        if (count($prod_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($prod_errors, 'Produit ' . $prod->getLink());
                            $this->db->db->rollback();
                        } else {
                            $success .= ($success ? '<br/>' : '') . $prod->getRef() . ' : transfert de ' . $qty . ' unité(s) ok';
                            $this->db->db->commit();
                        }
                        $prod->force_abos_stock_entrepot = false;
                    }
                }
            } else {
                $warnings[] = 'Aucun stock à corriger';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides : 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $client = $this->getChildObject('client');

            $comms = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
            $id_commercial = 0;
            if (BimpObject::objectLoaded($client)) {
                $client_commerciaux = $client->getIdCommercials(true);

                if (isset($client_commerciaux[0]) && $client_commerciaux[0]) {
                    $id_commercial = $client_commerciaux[0];

                    if (!empty($comms) && (count($comms) > 1 || $comms[0] != $id_commercial)) {
                        $this->dol_object->delete_linked_contact('internal', 'SALESREPFOLL');
                        $comms = array();
                    }
                }
            }

            if (empty($comms)) {
                if (!$id_commercial) {
                    $id_commercial = (int) $this->getData('fk_commercial_suivi');
                }

                if ($id_commercial) {
                    $this->dol_object->add_contact($id_commercial, 'SALESREPFOLL', 'internal');
                }
            }

            if ($id_commercial && (int) $this->getData('fk_commercial_suivi') !== $id_commercial ||
                    (!empty($comms) && (!(int) $this->getData('fk_commercial_suivi') || !in_array((int) $this->getData('fk_commercial_suivi'), $comms)))) {
                if (!$id_commercial && !empty($comms)) {
                    $id_commercial = $comms[0];
                }

                if ($id_commercial) {
                    $this->updateField('fk_commercial_suivi', $id_commercial);
                }
            }

            $id_contact = (int) $client->getData('contact_default');
            if ($id_contact) {
                $contacts = $this->dol_object->getIdContact('external', 'BILLING2');

                if (!empty($contacts) && (count($contacts) > 1 || $contacts[0] != $id_contact)) {
                    $this->dol_object->delete_linked_contact('external', 'BILLING2');
                }

                if (empty($contacts) || !in_array($id_contact, $contacts)) {
                    $this->dol_object->add_contact($id_contact, 'BILLING2', 'external');
                }
            }
        }

        return $errors;
    }

    // Méthodes statiques : 

    public static function RenouvAuto()
    {
        $infos = '';

        $bdb = BimpCache::getBdb();
        BimpObject::loadClass('bimpcontrat', 'BCT_ContratLine');

        $delay = (int) BimpCore::getConf('abo_renouv_auto_delay', null, 'bimpcontrat');

        if (!$delay) {
            return 'Renouvellements auto désactivés';
        }

        $date = new DateTime();
        $date->add(new DateInterval('P' . $delay . 'D'));

        $sql = BimpTools::getSqlFullSelectQuery('contratdet', array('a.rowid as id_line', 'c.rowid as id_contrat'), array(
                    'a.statut'            => array(BCT_ContratLine::STATUS_ACTIVE, BCT_ContratLine::STATUS_CLOSED),
                    'a.line_type'         => BCT_ContratLine::TYPE_ABO,
                    'id_linked_line'      => 0,
                    'id_parent_line'      => 0,
                    'id_line_renouv'      => 0,
                    'a.nb_renouv'         => array(
                        'or_field' => array(
                            -1,
                            array('operator' => '>', 'value' => 0)
                        )
                    ),
                    'a.date_fin_validite' => array(
                        'and' => array(
                            'IS_NOT_NULL',
                            array('operator' => '<=', 'value' => $date->format('Y-m-d') . ' 00:00:00')
                        )
                    ),
                    'a.date_cloture'      => 'IS_NULL',
                    'c.version'           => 2,
                    'c.statut'            => 1,
                        ), array('c' => array('table' => 'contrat', 'on' => 'c.rowid = a.fk_contrat')));

        $rows = $bdb->executeS($sql, 'array');

        // Trie par contrats :

        if (!is_array($rows)) {
            $infos .= '<span class="danger">Erreur SQL - ' . $bdb->err() . '</span>';
        } else {
            $contrats = array();
            foreach ($rows as $r) {
                if (!isset($contrats[(int) $r['id_contrat']])) {
                    $contrats[(int) $r['id_contrat']] = array();
                }

                $contrats[(int) $r['id_contrat']][] = (int) $r['id_line'];
            }

            if (empty($contrats)) {
                $infos .= '<span class="danger">Aucun renouvellement auto à affectuer</span>';
            } else {
                foreach ($contrats as $id_contrat => $lines) {
                    $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat);

                    if (!BimpObject::objectLoaded($contrat)) {
                        $infos .= '<br/><br/><span class="danger">Contrat #' . $id_contrat . ' non trouvé</span>';
                        continue;
                    }

                    $infos .= '<br/><br/>Contrat ' . $contrat->getLink() . ' : <br/>';

                    $lines_ok = '';
                    $nb_contrat_lines_ok = 0;
                    foreach ($lines as $id_line) {
                        $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

                        if (BimpObject::objectLoaded($line)) {
                            $infos .= 'Ligne #' . $id_line . ' : ';
                            $line_errors = array();

                            $line->renouvAbonnement(array(), $line_errors);

                            if (count($line_errors)) {
                                $infos .= '<span class="danger">' . BimpTools::getMsgFromArray($line_errors) . '</span>';
                            } else {
                                $infos .= '<span class="success">OK</span>';
                                $lines_ok .= ($lines_ok ? ', ' : '') . $line->getData('rang');
                                $nb_contrat_lines_ok++;
                            }
                            $infos .= '<br/>';
                        }
                    }



                    if ($nb_contrat_lines_ok > 0) {
                        $id_group = BimpCore::getUserGroupId('console');

                        if ($id_group) {
                            $s = ($nb_contrat_lines_ok > 1 ? 's' : '');
                            $msg = $nb_contrat_lines_ok . ' ligne' . $s . ' renouvellée' . $s . ' automatiquement.<br/>';
                            $msg .= 'Ligne' . $s . ' n° : ' . $lines_ok;
                            $contrat->addNote($msg, BimpNote::BN_MEMBERS, 0, 0, '', BimpNote::BN_AUTHOR_USER, BimpNote::BN_DEST_GROUP, $id_group, 0, 1);
                        }
                    }
                }
            }
        }

        return $infos;
    }

    public static function createRenouvTasks()
    {
        $infos = '';

        $bdb = BimpCache::getBdb();
        BimpObject::loadClass('bimpcontrat', 'BCT_ContratLine');

        $delay = (int) BimpCore::getConf('abo_renouv_task_delay', null, 'bimpcontrat');

        if (!$delay) {
            return 'Tâches renouvellements manuels désactivées';
        }

        $date = new DateTime();
        $date->add(new DateInterval('P' . $delay . 'D'));

        $sql = BimpTools::getSqlFullSelectQuery('contratdet', array('a.rowid as id_line', 'c.rowid as id_contrat'), array(
                    'a.statut'            => array(BCT_ContratLine::STATUS_ACTIVE, BCT_ContratLine::STATUS_CLOSED),
                    'a.line_type'         => BCT_ContratLine::TYPE_ABO,
                    'id_linked_line'      => 0,
                    'id_parent_line'      => 0,
                    'id_line_renouv'      => 0,
                    'nb_renouv'           => 0,
                    'a.renouv_task'       => 0,
                    'a.date_fin_validite' => array(
                        'and' => array(
                            'IS_NOT_NULL',
                            array('operator' => '<=', 'value' => $date->format('Y-m-d') . ' 00:00:00'),
                            array('operator' => '>', 'value' => date('Y-m-d') . ' 00:00:00')
                        )
                    ),
                    'a.date_cloture'      => 'IS_NULL',
                    'c.version'           => 2,
                    'c.statut'            => 1,
                        ), array('c' => array('table' => 'contrat', 'on' => 'c.rowid = a.fk_contrat')));

        $rows = $bdb->executeS($sql, 'array');

        // Trie par contrats :

        $contrats = array();
        foreach ($rows as $r) {
            if (!isset($contrats[(int) $r['id_contrat']])) {
                $contrats[(int) $r['id_contrat']] = array();
            }

            $contrats[(int) $r['id_contrat']][] = (int) $r['id_line'];
        }


        if (empty($contrats)) {
            $infos .= '<span class="danger">Aucune tâche de renouvellement à créer</span>';
        } else {
            foreach ($contrats as $id_contrat => $lines) {
                $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat);

                if (!BimpObject::objectLoaded($contrat)) {
                    continue;
                }

                $client = $contrat->getChildObject('client');
                $id_commercial = (int) $contrat->getCommercialId(array(
                            'check_active' => true
                ));

                $infos .= '<br/><br/>Contrat ' . $contrat->getLink() . ' : <br/>';

                if (!$id_commercial) {
                    BimpCore::addlog('Aucun commercial pour renouvellements manuels', Bimp_Log::BIMP_LOG_URGENT, 'contrat', $contrat);
                    $infos .= '<span class="danger">Aucun commercial</span>';
                    continue;
                }

                foreach ($lines as $id_line) {
                    $line = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_ContratLine', $id_line);

                    if (BimpObject::objectLoaded($line)) {
                        $infos .= 'Ligne #' . $id_line . ' : ';
                        $task_errors = array();

                        $desc = 'Contrat {{Contrat2:' . $contrat->id . '}}<br/>';

                        if (BimpObject::objectLoaded($client)) {
                            $desc .= 'Client {{Client:' . $client->id . '}}<br/>';
                        }

                        $desc .= 'Ligne n°' . $line->getData('rang') . ' - {{Produit:' . $line->getData('fk_product') . '}}<br/>';
                        $desc .= 'Fin de validité : le ' . date('d / m / Y', strtotime($line->getData('date_fin_validite')));

                        BimpObject::createBimpObject('bimptask', 'BIMP_Task', array(
                            'subj'          => 'Contrat d\'abonnement ' . $contrat->getRef() . ' - Renouvellement abonnement à effectuer',
                            'txt'           => $desc,
                            'comment'       => 'Cette tâche sera automatiquement fermée lors du renouvellement',
                            'id_user_owner' => $id_commercial,
                            'test_ferme'    => 'contratdet:rowid = ' . $line->id . ' AND id_line_renouv > 0'
                                ), true, $task_errors);

                        if (count($task_errors)) {
                            $infos .= '<span class="danger">' . BimpTools::getMsgFromArray($task_errors) . '</span>';
                        } else {
                            $infos .= '<span class="success">OK</span>';

                            $line->updateField('renouv_task', 1);
                        }
                        $infos .= '<br/>';
                    }
                }
            }
        }

        return $infos;
    }

    public static function checkInactivesLines()
    {
        $nOk = 0;
        $bdb = self::getBdb();
        $id_group = BimpCore::getUserGroupId('console');

        if ($id_group) {
            $where = 'a.date_ouverture_prevue IS NOT NULL AND a.date_ouverture_prevue < \'' . date('Y-m-d') . ' 00:00:00\' AND a.statut = 0';
            $where .= ' AND a.id_parent_line = 0 AND c.version = 2';
            $rows = $bdb->getRows('contratdet a', $where, null, 'array', array('a.rowid', 'a.fk_contrat', 'a.rang'), null, null, array(
                'c' => array('table' => 'contrat', 'on' => 'c.rowid = a.fk_contrat')
            ));

            if (is_array($rows)) {
                $contrats = array();

                foreach ($rows as $r) {
                    if (!isset($contrats[(int) $r['fk_contrat']])) {
                        $contrats[(int) $r['fk_contrat']] = array();
                    }

                    $contrats[(int) $r['fk_contrat']][] = $r['rang'];
                }

                if (!empty($contrats)) {
                    $where_note = 'obj_module = \'bimpcontrat\' AND obj_name = \'BCT_Contrat\' AND id_obj = ';

                    foreach ($contrats as $id_contrat => $lines_rangs) {
                        $contrat = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', $id_contrat);

                        if (BimpObject::objectLoaded($contrat)) {
                            $s = (count($lines_rangs) > 1 ? 's' : '');
                            $msg = count($lines_rangs) . ' ligne' . $s . ' encore inactive' . $s . ' dont la date d\'ouverture prévue est dépassée : ligne' . $s . ' n° ' . implode(', ', $lines_rangs);

                            if (!(int) $bdb->getValue('bimpcore_note', 'id', $where_note . $contrat->id . ' AND content = \'' . addslashes($msg) . '\'')) {
                                if (empty($contrat->addNote($msg, BimpNote::BN_MEMBERS, 0, 0, '', BimpNote::BN_AUTHOR_USER, BimpNote::BN_DEST_GROUP, $id_group, 0, 1))) {
                                    $nOk++;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $nOk . ' alerte(s) créées';
    }
}

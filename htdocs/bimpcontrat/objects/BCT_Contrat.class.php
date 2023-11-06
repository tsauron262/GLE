<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BCT_Contrat extends BimpDolObject
{

    public $redirectMode = 0;

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 2;

    public static $status_list = Array(
        self::STATUS_DRAFT     => Array('label' => 'Brouillon', 'classes' => Array('warning'), 'icon' => 'fas_trash-alt'),
        self::STATUS_VALIDATED => Array('label' => 'Validé', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::STATUS_CLOSED    => Array('label' => 'Fermé', 'classes' => Array('danger'), 'icon' => 'fas_times')
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

    public function isActionAllowed($action, &$errors = []): int
    {
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

    // Getters params: 

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

        return $buttons;
    }

    public function getFilesDir()
    {
        global $conf;
        return $conf->contract->dir_output;
    }

    // Getters données : 

    public function getConditionReglementClient()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0)) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc_facturation', BimpTools::getPostFieldValue('fk_soc', 0));
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
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0)) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc_facturation', BimpTools::getPostFieldValue('fk_soc', 0));
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

    public function getLines($types = null, $ids_only = false)
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpcontrat', 'BCT_ContratLine');

            $filters = array();
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
                    $filters = array(
                        'line_type' => array(
                            'in' => $types
                        )
                    );
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

    // Getters Array: 

    public function getClientRibsArray()
    {
        $id_client = (int) $this->getData('fk_soc_facturation');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return BimpCache::getSocieteRibsArray($id_client, true);
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
        }

        return $html;
    }

    public function renderLinkedObjectsTable($htmlP = '')
    {
        $this->dol_object->element = 'bimp_contrat';

        return parent::renderLinkedObjectsTable($htmlP);
    }

    public function renderFacturesTab()
    {
        $html = '';

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
                                'linked_object_name' => 'contrat_line',
                                'linked_id_object'   => $contrat_lines
                                    ), 'position', 'asc');

                    if (empty($fac_lines)) {
                        $lines_content .= BimpRender::renderAlerts('Aucune ligne liée à ce contrat dans cette facture');
                    } else {
                        $lines_rows = array();
                        foreach ($fac_lines as $fac_line) {
                            $lines_rows[] = array(
                                'desc'      => $fac_line->displayLineData('desc_light'),
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
            'ajax_callback' => $line_instance->getJsLoadCustomContent('renderPeriodsList', '$(\'#achat_periods_tab .nav_tab_ajax_result\')', array('achat', $params['id_client'], $params['id_product'], $params['id_fourn'], $params['id_contrat']), array('button' => ''))
        );

        $html .= BimpRender::renderNavTabs($tabs);

        return $html;
    }

    // Traitements : 

    public function addLinesToFacture($id_facture, $lines_data = null, $commit_each_line = false, $new_qties = true, &$nOk = 0)
    {
        // $commit_each_line : nécessaire pour le traitement des facturation périodiques.
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
                $errors[] = 'La ligne de copntrat d\'abonnement #' . $id_line . ' n\'existe plus';
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
                // Création de la ligne de l'intitulé de la commande d'origine si nécessaire: 
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
                        'linked_id_object'   => (int) $contrat,
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

//                $product = $line->getChildObject('product');
                $line_errors = array();
                $line_warnings = array();
                $line_qty = (float) $line_data['qty'];

                $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                            'id_obj'             => (int) $facture->id,
                            'linked_object_name' => 'contrat_line',
                            'linked_id_object'   => (int) $line->id
                                ), true);

                if (!BimpObject::objectLoaded($fac_line)) {
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
                        'type'               => Bimp_FactureLine::LINE_PRODUCT,
                        'remisable'          => 1,
                        'linked_id_object'   => (int) $line->id,
                        'linked_object_name' => 'contrat_line'
                    ));

                    $date_from = null;
                    $date_to = null;
                    $new_date_next_facture = null;

                    $periodicity = (int) $line->getData('fac_periodicity');
                    if ((int) $line_data['nb_periods'] && $periodicity) {
                        $date_next_facture = $line->getDateNextFacture(true, $line_errors);

                        if ($date_next_facture) {
                            $dt = new DateTime($date_next_facture);
                            $date_from = $dt->format('Y-m-d 00:00:00');
                            $dt->add(new DateInterval('P' . ((int) $line_data['nb_periods'] * $periodicity) . 'M'));
                            $new_date_next_facture = $dt->format('Y-m-d');
                            $dt->sub(new DateInterval('P1D'));
                            $date_to = $dt->format('Y-m-d 23:59:59');
                        }
                    }

                    $fac_line->qty = $line_qty;
                    $fac_line->desc = $line->getData('description');
                    $fac_line->id_product = (int) $line->getData('fk_product');
                    $fac_line->pu_ht = $line->getData('price_ht');
                    $fac_line->tva_tx = $line->getData('tva_tx');
                    $fac_line->pa_ht = $line->getData('buy_price_ht');
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

                        $fac_line->updateField('deletable', 0);
                    }
                } else {
                    if ($new_qties) {
                        $line_qty += (float) $fac_line->qty;
                    }

                    if (!$line_qty) {
                        // Suppression de la ligne de facture : 
                        $line_errors = $fac_line->delete($line_warnings, true);
                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, $line_label . ' : échec de la suppression de la ligne de facture correspondante');
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

                    $fac_line->qty = $line_qty;
                    $fac_line_warnings = array();
                    $fac_line_errors = $fac_line->update($fac_line_warnings, true);

                    if (count($fac_line_errors)) {
                        $line_errors[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la mise à jour de la ligne de facture');
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
            addElementElement('bimp_contrat', 'facture', $id_contrat, $facture->id);
        }

        return $errors;
    }

    // Actions : 

    public function actionValidate($data, &$success)
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
}

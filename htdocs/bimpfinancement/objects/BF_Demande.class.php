<?php

class BF_Demande extends BimpObject
{

    const BF_DEMANDE_BROUILLON = 0;
    const BF_DEMANDE_ATT_RETOUR = 1;
    const BF_DEMANDE_SIGNE = 2;
    const BF_DEMANDE_SIGNE_ATT_CESSION = 3;
    const BF_DEMANDE_CEDE = 4;
    const BF_DEMANDE_SANS_SUITE = 5;
    const BF_DEMANDE_RECONDUIT = 6;
    const BF_DEMANDE_REMPLACE = 7;
    const BF_DEMANDE_SIGNE_ANOM = 888;
    const BF_DEMANDE_TERMINE = 999;

    public static $status_list = array(
        self::BF_DEMANDE_BROUILLON         => array('label' => 'Brouillon', 'classes' => array('warning')),
        self::BF_DEMANDE_ATT_RETOUR        => array('label' => 'Signé - en attente de retour', 'classes' => array('important')),
        self::BF_DEMANDE_SIGNE_ATT_CESSION => array('label' => 'Signé - en attente de cession', 'classes' => array('important')),
        self::BF_DEMANDE_SIGNE_ANOM        => array('label' => 'Signé mais anomalie', 'classes' => array('danger')),
        self::BF_DEMANDE_CEDE              => array('label' => 'Cédé', 'classes' => array('danger')),
        self::BF_DEMANDE_SANS_SUITE        => array('label' => 'Sans suite', 'classes' => array('danger')),
        self::BF_DEMANDE_RECONDUIT         => array('label' => 'Reconduit', 'classes' => array('danger')),
        self::BF_DEMANDE_REMPLACE          => array('label' => 'Remplacé', 'classes' => array('danger')),
        self::BF_DEMANDE_TERMINE           => array('label' => 'Terminé', 'classes' => array('success')),
    );
    public static $durations = array(
        24 => '24 mois',
        36 => '36 mois',
        48 => '48 mois',
        60 => '60 mois',
        72 => '72 mois',
        84 => '84 mois'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $annexes = array(
        0 => '-',
        1 => 'OFC',
        2 => 'OA'
    );
    public static $calc_modes = array(
        0 => '-',
        1 => 'A terme échu',
        2 => 'A terme à échoir'
    );

    // Autorisations et droits :

    public function isActionAllowed($action, &$errors = array())
    {
        $status = $this->getData('status');

        if (in_array($action, array('generateFactureBanque', 'generateFactureClient', 'generateFactureFourn', 'generateContrat'))) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID de la demande de fincancement absent';
                return 0;
            }
            if (is_null($status)) {
                $errors[] = 'Statut de la demande absent';
                return 0;
            }
        }

        $status = (int) $status;

        switch ($action) {
            case 'generateFactureBanque':
                $facture = $this->getChildObject('facture_banque');
                if (BimpObject::objectLoaded($facture)) {
                    if ((int) $facture->getData('fk_statut') !== 0) {
                        $errors[] = 'La facture banque existe déjà et n\'est plus modifiable';
                        return 0;
                    }
                }
                return 1;

            case 'generateFactureClient':
                $facture = $this->getChildObject('facture_client');
                if (BimpObject::objectLoaded($facture)) {
                    if ((int) $facture->getData('fk_statut') !== 0) {
                        $errors[] = 'La facture client existe déjà et n\'est plus modifiable';
                        return 0;
                    }
                }
                if ($status !== self::BF_DEMANDE_TERMINE) {
                    $errors[] = 'Statut invalide';
                    return 0;
                }
                if (!(float) $this->getData('vr')) {
                    $errors[] = 'VR achat absente';
                    return 0;
                }
                return 1;

            case 'generateFactureFourn':
                $facture = $this->getChildObject('facture_fournisseur');
                if (BimpObject::objectLoaded($facture)) {
                    if ((int) $facture->getData('fk_statut') !== 0) {
                        $errors[] = 'La facture fournisseur existe déjà et n\'est plus modifiable';
                        return 0;
                    }
                }
                if ($status !== self::BF_DEMANDE_TERMINE) {
                    $errors[] = 'Statut invalide';
                    return 0;
                }
                if (!(float) $this->getData('vr_vente')) {
                    $errors[] = 'VR vente absente';
                    return 0;
                }
                return 1;

            case 'generateContrat':
                if (!in_array($status, array(self::BF_DEMANDE_ATT_RETOUR, self::BF_DEMANDE_CEDE))) {
                    $errors[] = 'Statut invalide';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters:

    public function getActionsButtons()
    {
        $buttons = array();

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        if ($this->isActionAllowed('generateFactureBanque')) {
            $buttons[] = array(
                'label'   => 'Générer facture banque',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => $this->getJsActionOnclick('generateFactureBanque', array(), array(
                    'success_callback' => $callback
                ))
            );
        }

        if ($this->isActionAllowed('generateFactureClient')) {
            $buttons[] = array(
                'label'   => 'Générer facture client',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => $this->getJsActionOnclick('generateFactureClient', array(), array(
                    'success_callback' => $callback
                ))
            );
        }

        if ($this->isActionAllowed('generateFactureFourn')) {
            $buttons[] = array(
                'label'   => 'Générer facture fournisseur',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => $this->getJsActionOnclick('generateFactureFourn', array(), array(
                    'success_callback' => $callback
                ))
            );
        }

        if ($this->isActionAllowed('generateContrat')) {
            $buttons[] = array(
                'label'   => 'Générer contrat',
                'icon'    => 'fas_file-contract',
                'onclick' => $this->getJsActionOnclick('generateContrat', array(), array(
                    'success_callback' => $callback
                ))
            );
        }

        return $buttons;
    }

    public function checkDemande()
    {
        if (!(int) $this->getData('accepted')) {
            $errors[] = 'Demande non validée par la banque';
        }
        if (!$this->getData('date_livraison')) {
            $errors[] = 'Date de livraison non définie';
        }
        if (!$this->getData('date_loyer')) {
            $errors[] = 'Date de mise en loyer non définie';
        }
        if (!(float) $this->getData('montant_materiels') && !(float) $this->getData('montant_services') && !(float) $this->getData('montant_logiciels')) {
            $errors[] = 'Aucun montant défini';
        }
        if (!(int) $this->getData('id_client')) {
            $errors[] = 'Client absent';
        }
        if (!(int) $this->getData('id_commercial')) {
            $errors[] = 'La demande de financement n\'a pas de commercial';
        }

        return $errors;
    }

    public function getClient_contactsArray()
    {
        $contacts = array();
        $id_client = (int) $this->getData('id_client');
        if (!is_null($id_client) && $id_client) {
            $where = '`fk_soc` = ' . $id_client;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }
        return $contacts;
    }

    public function getSupplier_contactsArray()
    {
        $contacts = array();
        $id_supplier = (int) $this->getData('id_supplier');
        if ($id_supplier) {
            $where = '`fk_soc` = ' . $id_supplier;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }
        return $contacts;
    }

    public function getRemainingElementsToOrder()
    {
        $elements = array();

        if ($this->isLoaded()) {
            $lines = $this->getChildrenObjects('lines', array(), 'position', 'asc');

            foreach ($lines as $line) {
                if ((int) $line->getData('type') === BF_Line::BL_TEXT) {
                    continue;
                }
                $id_fourn = (int) $line->getData('id_fournisseur');

                if (!$id_fourn) {
                    continue;
                }

                $qty = (float) $line->getData('qty');

                $line_commandes = $line->getData('commandes_fourn');
                if (is_array($line_commandes)) {
                    foreach ($line_commandes as $id_commande => $commande_qty) {
                        $qty -= (float) $commande_qty;
                    }
                }

                if ($qty > 0) {
                    if (!isset($elements[$id_fourn])) {
                        $elements[$id_fourn] = array();
                    }
                    $elements[$id_fourn][(int) $line->id] = array(
                        'id_line' => (int) $line->id,
                        'qty'     => $qty
                    );
                }
            }
        }

        return $elements;
    }

    public function getCommandesFournisseurData()
    {
        $commFourns = array();

        $lines = $this->getChildrenObjects('lines', array(), 'position', 'asc');

        foreach ($lines as $line) {
            $line_comm = $line->getData('commandes_fourn');
            if (is_array($line_comm)) {
                foreach ($line_comm as $id_comm => $qty) {
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_comm);
                    if (BimpObject::objectLoaded($comm)) {
                        $id_fourn = (int) $comm->getData('fk_soc');

                        if (!isset($commFourns[$id_fourn])) {
                            $commFourns[$id_fourn] = array();
                        }
                        if (!isset($commFourns[$id_fourn][(int) $id_comm])) {
                            $commFourns[$id_fourn][(int) $id_comm] = array(
                                'comm'  => $comm,
                                'lines' => array()
                            );
                        }
                        $commFourns[$id_fourn][(int) $id_comm]['lines'][(int) $line->id] = array(
                            'qty'  => (float) $qty,
                            'line' => $line
                        );
                    }
                }
            }
        }

        return $commFourns;
    }

    public function getDemandeFacturesArray()
    {
        $factures = array(
            0 => 'Nouvelle facture'
        );

        if ($this->isLoaded()) {
            $asso = new BimpAssociation($this, 'factures');

            $list = $asso->getAssociatesList();

            foreach ($list as $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                if (BimpObject::objectLoaded($facture)) {
                    if ((int) $facture->getData('fk_statut') === 0) {
                        $factures[$id_facture] = $facture->getRef();
                    }
                }
            }
        }

        return $factures;
    }

    public function getTotalEmprunt($include_commissions = true)
    {
        $total = (float) $this->getData('montant_materiels') + (float) $this->getData('montant_services') + (float) $this->getData('montant_logiciels') - $this->getData('vr_vente');

        if ($include_commissions) {
            $total_base = $total;

            $total += $total_base * ((float) $this->getData('commission_commerciale') / 100);
            $total += $total_base * ((float) $this->getData('commission_financiere') / 100);
        }

        return $total;
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';
        if ($this->isLoaded()) {
            BimpTools::loadDolClass('societe');
            $id_client = $this->getData('id_client');
            $client = new Societe($this->db->db);
            $client->fetch($id_client);
            $note = $this->db->getRow('societe_extrafields', 'fk_object = ' . $id_client, null, 'object', array('notecreditsafe'));
            if (is_object($client)) {
                $html .= '<b>Client : </b>' . $client->getNomUrl(1);
                $html .= '<div style="margin-top: 10px">';
                $html .= '<strong>Notre crédit safe du client: </strong>';
                if ($note->notecreditsafe) {
                    $html .= '<i>' . $note->notecreditsafe . '</i>';
                } else {
                    $html .= '<i>Ce client n\'à pas de note crédit safe</i>';
                }
                $html .= '</div>';
            }
        }
        return $html;
    }

    public function renderCommandesFournisseursList()
    {
        $html = '';

        $commandes_fourn = $this->getCommandesFournisseurData();
        $remains = $this->getRemainingElementsToOrder();

        $buttons = '';
        if (!is_array($commandes_fourn) || !count($commandes_fourn)) {
            $html .= BimpRender::renderAlerts('Aucune commmande fournisseur enregistée pour cette demande de financement', 'info');
        } else {
            $view_id = 'BF_Demande_fournisseurs_view_' . $this->id;

            $buttons .= '<div style="display: none; text-align: right;" class="buttonsContainer commandes_fourn_modif_buttons">';
            $buttons .= '<button type="button" class="btn btn-default" onclick="cancelCommandesFournLinesModifs($(this), \'' . $view_id . '\', ' . $this->id . ');">';
            $buttons .= '<i class="' . BimpRender::renderIconClass('fas_undo') . ' iconLeft"></i>Annuler toutes les modifications';
            $buttons .= '</button>';

            $buttons .= '<button type="button" class="btn btn-primary" onclick="saveCommandesFournLinesModifs($(this), \'' . $view_id . '\', ' . $this->id . ');">';
            $buttons .= '<i class="' . BimpRender::renderIconClass('fas_save') . ' iconLeft"></i>Enregistrer toutes les modifications';
            $buttons .= '</button>';
            $buttons .= '</div>';

            $html .= $buttons;

            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody>';

            foreach ($commandes_fourn as $id_fourn => $commandes) {
                $html .= '<tr class="fourn_row">';
                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fourn);
                if (!$fourn->isLoaded()) {
                    $html .= '<th>Fournisseur ' . $id_fourn . '</th>';
                    $html .= '<td>';
                    $html .= BimpRender::renderAlerts('Erreur: le fournisseur d\'ID ' . $id_fourn . ' n\'existe pas');
                    $html .= '</td>';
                } else {
                    if (isset($remains[$fourn->id]) && is_array($remains[$fourn->id])) {
                        foreach ($remains[$fourn->id] as $id_line => $remain_data) {
                            $html .= '<input type="hidden" id="fourn_' . $fourn->id . '_line_' . $id_line . '_remain_qty" value="' . (float) $remain_data['qty'] . '"/>';
                        }
                    }
                    $fourn_lines = $this->getChildrenObjects('lines', array('id_fournisseur' => (int) $id_fourn), 'position', 'asc');
                    $html .= '<th style="width: 25%">';
                    $html .= $fourn->getNomUrl(true, false, true, 'default');
                    $html .= '</th>';
                    $html .= '<td>';

                    if (!is_array($commandes) || !count($commandes)) {
                        $html .= BimpRender::renderAlerts('Aucune commande enregistrée pour ce fournisseur', 'info');
                    } else {
                        $html .= '<table class="objectSubList">';
                        $html .= '<thead>';
                        $html .= '<tr>';
                        $html .= '<th>Commande</th>';
                        $html .= '<th>Statut</th>';
                        $html .= '<th>PDF Commande</th>';
                        $html .= '<th>Facture</th>';
                        $html .= '<th>PDF Facture</th>';
                        $html .= '<th></th>';
                        $html .= '</tr>';
                        $html .= '</thead>';
                        foreach ($commandes as $commande_data) {
                            $commande = $commande_data['comm'];
                            $html .= '<tr>';
                            $html .= '<td>' . $commande->getNomUrl(true, true, true, 'full') . '</td>';
                            $html .= '<td>' . $commande->displayData('fk_statut') . '</td>';
                            $html .= '<td>';
                            $html .= $commande->displayPDFButton(false);
                            $html .= '</td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td class="buttons">';
                            $html .= '<span class="displayDetailButton btn btn-light-default" onclick="toggleBfCommandeFournDetailDisplay($(this));">';
                            $html .= '<i class="' . BimpRender::renderIconClass('fas_list') . ' iconLeft"></i>Détail';
                            $html .= '<i class="' . BimpRender::renderIconClass('fas_caret-up') . ' iconRight"></i>';
                            $html .= '</span>';
                            $html .= '</td>';
                            $html .= '</tr>';

                            $html .= '<tr class="commande_fourn_elements_rows">';
                            $html .= '<td colspan="4" style="padding: 10px 30px; border-top-color: #fff">';

                            $html .= '<table class="objectSubList">';
                            $html .= '<tbody>';
                            foreach ($fourn_lines as $fourn_line) {
                                $line = $fourn_line;
                                $qty = 0;
                                if (array_key_exists($fourn_line->id, $commande_data['lines'])) {
                                    $line = $commande_data['lines'][$fourn_line->id]['line'];
                                    $qty = $commande_data['lines'][$fourn_line->id]['qty'];
                                }

                                if (BimpObject::objectLoaded($line)) {
                                    $html .= '<tr class="commande_fourn_element_row fourn_' . $fourn->id . '_line_' . $line->id . (!$qty ? ' deactivated' : '') . '"';
                                    $html .= ' data-id_fourn="' . $fourn->id . '"';
                                    $html .= ' data-id_commande="' . $commande->id . '"';
                                    $html .= ' data-id_bf_line="' . $line->id . '">';
                                    $html .= '<td>' . $line->displayDescription() . '</td>';
                                    $html .= '<td>Qté: ';
                                    if ((int) $commande->getData('fk_statut') === 0) {
                                        $max = $qty;
                                        if (isset($remains[(int) $fourn->id][(int) $line->id])) {
                                            $max += (float) $remains[(int) $fourn->id][(int) $line->id]['qty'];
                                        }
                                        $html .= BimpInput::renderInput('qty', 'fourn_' . $fourn->id . '_comm_' . $commande->id . '_line_' . $line->id . '_qty', $qty, array(
                                                    'extra_class' => 'line_qty_input',
                                                    'step'        => $line->getQtyStep(),
                                                    'data'        => array(
                                                        'initial_qty' => $qty,
                                                        'data_type'   => 'number',
                                                        'decimals'    => $line->getQtyDecimals(),
                                                        'min'         => 0,
                                                        'max'         => $max,
                                                        'unsigned'    => 1
                                                    )
                                        ));
                                        $html .= '<p class="inputHelp" style="display: inline-block">Max: <span class="qty_max_value">' . $max . '</span></p>';
                                    } else {
                                        $html .= $qty;
                                    }
                                    $html .= '</td>';
                                    $html .= '<td class="buttons">';
//                                    if ($commande->isEditable()) {
//                                        $html .= BimpRender::renderRowButton('Annuler les modifications', 'fas_undo', 'cancelCommandesFournLinesModifs($(this), \'' . $view_id . '\')', 'cancel_line hidden');
//                                        $html .= BimpRender::renderRowButton('Enregistrer', 'fas_save', 'saveCommandesFournLinesModifs($(this), \'' . $view_id . '\', ' . $this->id . ')', 'save_line hidden');
//                                    }
                                    $html .= '</td>';
                                    $html .= '</tr>';
                                }
                            }
                            $html .= '</tbody>';
                            $html .= '</table>';

                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                        $html .= '</table>';
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return BimpRender::renderPanel('Commandes fournisseurs', $html, $buttons, array(
                    'type'           => 'secondary',
                    'icon'           => 'fas_cart-arrow-down',
                    'header_buttons' => array(
                        array(
                            'label'       => 'Nouvelle(s) commande(s) fournisseur',
                            'classes'     => array('btn', 'btn-default'),
                            'icon_before' => 'fas_plus-circle',
                            'attr'        => array(
                                'onclick' => $this->getJsActionOnclick('generateCommandesFourn', array(), array(
                                    'form_name'      => 'new_commandes_fourn',
                                    'on_form_submit' => 'function($form, extra_data) {return onCommandesFournFormSubmit($form, extra_data);}'
                                ))
                            )
                        )
                    )
        ));
    }

    public function renderNewCommandesFournInputs()
    {
        $html = '';

        $elements = $this->getRemainingElementsToOrder();

        if (!count($elements)) {
            $html .= BimpRender::renderAlerts('Il n\'y a aucun élément à ajouter à une commande fournisseur', 'warning');
        } else {
            foreach ($elements as $id_fourn => $lines) {
                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fourn);
                if (BimpObject::objectLoaded($fourn)) {
                    $html .= '<div class="fournisseur_container" style="margin-bottom: 15px">';
                    $label = $fourn->getData('code_fournisseur');
                    $label .= ($label ? ' - ' : '') . $fourn->getData('nom');
                    $html .= BimpInput::renderInput('check_list', 'fournisseurs', $id_fourn, array(
                                'items' => array($id_fourn => $label)
                    ));
                    $html .= '<div class="commande_fourn_lines">';
                    $html .= '<table class="bimp_list_table" style="margin-left: 30px">';
                    $html .= '<tbody>';
                    foreach ($lines as $line) {
                        $bf_line = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Line', (int) $line['id_line'], $this);
                        if (BimpObject::objectLoaded($bf_line)) {
                            $html .= '<tr>';
                            $html .= '<td><input type="checkbox" class="fourn_line_check" name="fourn_' . $fourn->id . '_lines[]" value="' . $bf_line->id . '" checked/></td>';
                            $html .= '<td>';
                            $html .= $bf_line->displayDescription();
                            $html .= '</td>';
                            $html .= '<td>Qté: ';
                            $html .= BimpInput::renderInput('qty', 'line_' . $bf_line->id . '_qty', $line['qty'], array(
                                        'step' => $bf_line->getQtyStep(),
                                        'data' => array(
                                            'data_type' => 'number',
                                            'decimals'  => $bf_line->getQtyDecimals(),
                                            'min'       => 0,
                                            'max'       => $line['qty'],
                                            'unsigned'  => 1
                                        )
                            ));
                            $html .= '<p class="inputHelp" style="display: inline-block">Max: ' . $line['qty'] . '</p>';
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                    $html .= '</tbody>';
                    $html .= '</table>';
                    $html .= '</div>';
                    $html .= '</div>';
                } else {
                    $html .= BimpRender::renderAlerts('Le fournisseur d\'ID ' . $id_fourn . ' n\'existe pas');
                }
            }
        }

        return $html;
    }

    public function renderFacturesFraisList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $asso = new BimpAssociation($this, 'factures');
            $list = $asso->getAssociatesList();

            if (count($list)) {
                $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                $bc_list = new BC_ListTable($facture, 'default', 1, null, 'Factures frais divers et loyers intercalaires', 'fas_file-invoice-dollar');
                $bc_list->addObjectAssociationFilter($this, $this->id, 'factures');
                $bc_list->addObjectChangeReload('BF_FraisDivers');
                $bc_list->addObjectChangeReload('BF_RentExcept');
                $html = $bc_list->renderHtml();
            }
        }

        return $html;
    }

    public function renderAllFacturesList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $factures = array();

            if ((int) $this->getData('id_facture')) {
                $facture = $this->getChildObject('facture_banque');
                if (BimpObject::objectLoaded($facture)) {
                    $factures[] = array(
                        'type'      => 'Facture Banque',
                        'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                        'date'      => $facture->displayData('datef'),
                        'status'    => $facture->displayData('fk_statut'),
                        'amount_ht' => $facture->displayData('total'),
                        'paid'      => $facture->displayPaid(),
                        'file'      => $facture->displayPDFButton(true, true)
                    );
                }
            }

            if ((int) $this->getData('id_facture_client')) {
                $facture = $this->getChildObject('facture_client');
                if (BimpObject::objectLoaded($facture)) {
                    $factures[] = array(
                        'type'      => 'Facture Client',
                        'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                        'date'      => $facture->displayData('datef'),
                        'status'    => $facture->displayData('fk_statut'),
                        'amount_ht' => $facture->displayData('total'),
                        'paid'      => $facture->displayPaid(),
                        'file'      => $facture->displayPDFButton(true, true)
                    );
                }
            }

            if ((int) $this->getData('id_facture_fournisseur')) {
                $facture = $this->getChildObject('facture_fournisseur');
                if (BimpObject::objectLoaded($facture)) {
                    $factures[] = array(
                        'type'      => 'Facture Fournisseur',
                        'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                        'date'      => $facture->displayData('datef'),
                        'status'    => $facture->displayData('fk_statut'),
                        'amount_ht' => $facture->displayData('total'),
                        'paid'      => $facture->displayPaid(),
                        'file'      => $facture->displayPDFButton(true, true)
                    );
                }
            }

            $asso = new BimpAssociation($this, 'factures');

            foreach ($asso->getAssociatesList() as $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                if (BimpObject::objectLoaded($facture)) {
                    $factures[] = array(
                        'type'      => 'Facture Frais divers',
                        'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                        'date'      => $facture->displayData('datef'),
                        'status'    => $facture->displayData('fk_statut'),
                        'amount_ht' => $facture->displayData('total'),
                        'paid'      => $facture->displayPaid(),
                        'file'      => $facture->displayPDFButton(true, true)
                    );
                }
            }

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Type</th>';
            $html .= '<th>Facture</th>';
            $html .= '<th>Date</th>';
            $html .= '<th>Statut</th>';
            $html .= '<th>Montant HT</th>';
            $html .= '<th>Payé</th>';
            $html .= '<th>Fichier PDF</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (count($factures)) {
                foreach ($factures as $fac) {
                    $html .= '<tr>';
                    $html .= '<td><strong>' . $fac['type'] . '</strong></td>';
                    $html .= '<td>' . $fac['nom_url'] . '</td>';
                    $html .= '<td>' . $fac['date'] . '</td>';
                    $html .= '<td>' . $fac['status'] . '</td>';
                    $html .= '<td>' . $fac['amount_ht'] . '</td>';
                    $html .= '<td>' . $fac['paid'] . '</td>';
                    $html .= '<td>' . $fac['file'] . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="6" style="text-align: center">';
                $html .= BimpRender::renderAlerts('Il n\'y a aucune facture enregistrée pour cette demande de financement pour le moment', 'info');
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    // Traitements: 

    public function verif_exist_document($document_type)
    {
        $modifiable = true;

        // Modification des champs de la bese de données si les documents ont été supprimés
        // ($object => $document_type) : essayer d'être précis sur les noms de variable. Utiliser "$object" lorsque la variable contient effectivement un objet indeterminé. 

        switch ($document_type) {
            case 'contrat':
                if (!(int) $this->db->getValue('contrat', 'rowid', 'rowid = ' . $this->getData('id_facture'))) {
                    $this->updateField('id_contrat', 0);
                    $modifiable = false;
                }
                break;

            case 'factureB':
                if (!(int) $this->db->getValue('facture', 'rowid', 'rowid = ' . $this->getData('id_facture'))) {
                    $this->updateField('id_facture', 0);
                    $modifiable = false;
                }
                break;

            case 'factureC':
                if (!(int) $this->db->getValue('facture', 'rowid', 'rowid = ' . $this->getData('id_facture_client'))) {
                    $this->updateField('id_facture_client', 0);
                    $modifiable = false;
                }
                break;

            case 'factureF':
                if (!(int) $this->db->getValue('facture', 'rowid', 'rowid = ' . $this->getData('id_facture_fournisseur'))) {
                    $this->updateField('id_facture_fournisseur', 0);
                    $modifiable = false;
                }
                break;
        }

        return $modifiable;
    }

    public function createCommandeFournisseur($id_entrepot, $id_fournisseur, $lines)
    {
        $errors = array();

        $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fournisseur);

        if (!BimpObject::objectLoaded($fourn)) {
            $errors[] = 'Le fournisseur d\'ID ' . $id_fournisseur . ' n\'existe pas';
        } elseif (!is_array($lines) || !count($lines)) {
            $errors[] = 'Aucune ligne à ajouter à la commande';
        } else {
            foreach ($lines as $i => $line) {
                if ((float) $line['qty'] > 0) {
                    if (!(int) $line['id_line']) {
                        $errors[] = 'ID absent pour la ligne n° ' . ($i + 1);
                        unset($lines[$i]);
                    } else {
                        $bf_line = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Line', (int) $line['id_line']);
                        if (!$bf_line->isLoaded()) {
                            $errors[] = 'La ligne d\'élément à financer d\'ID ' . $line['id_line'] . 'n\'existe pas';
                            unset($lines[$i]);
                        } else {
                            $lines[$i]['bf_line'] = $bf_line;
                        }
                    }
                }
            }
            if (!count($lines)) {
                $errors[] = 'Aucune ligne à ajouter à la commande';
            }
        }

        if (!count($errors)) {
            $commFourn = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');
            $errors = $commFourn->validateArray(array(
                'entrepot'     => (int) $id_entrepot,
                'fk_soc'       => (int) $id_fournisseur,
                'note_private' => 'Demande de financement ' . $this->id
            ));

            if (!count($errors)) {
                $errors = $commFourn->create();

                if (!count($errors)) {
                    foreach ($lines as $i => $line) {
                        $bf_line = $line['bf_line'];
                        $line_errors = $bf_line->createCommandeFournLine((int) $commFourn->id, (float) $line['qty']);

                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout à la commande de la ligne n°' . ($i + 1));
                        }
                    }
                }
            }
        }

        return $errors;
    }
    
    public function calcMontants()
    {
        $errors = array();
        if ($this->isLoaded()) {
            $montant_produits = 0;
            $montant_services = 0;
            $montant_logiciels = 0;
            
            $lines = $this->getChildrenObjects('lines');
            foreach ($lines as $line) {
                $total_ttc = (float) $line->getTotalTTC();
                switch((int) $line->getData('product_type')) {
                    case BimpLine::BL_PRODUIT:
                        $montant_produits += $total_ttc;
                        break;
                    
                    case BimpLine::BL_SERVICE:
                        $montant_services += $total_ttc;
                        break;
                    
                    case BimpLine::BL_LOGICIEL:
                        $montant_logiciels += $total_ttc;
                        break;
                }
            }
            
            $this->set('montant_materiels', $montant_produits);
            $this->set('montant_services', $montant_services);
            $this->set('montant_logiciels', $montant_logiciels);
            $up_warnings = array();
            $up_errors = $this->update($up_warnings);
            
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Des erreurs sont survenues lors de la mise à jour des montants de la demande de financement');
            }
            
            if (count($up_warnings)) {
                $errors[] = BimpTools::getMsgFromArray($up_warnings, 'Des erreurs sont survenues suite à la mise à jour des montants de la demande de financement');
            }
        }
        
        return $errors;
    }
    
    public function onChildSave($child)
    {
        if (isset($child->object_name)) {
            if ($child->object_name === 'BF_Line') {
                return $this->calcMontants();
            }
        }
        
        return array();
    }

    // Actions:

    public function actionGenerateContrat($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $contrat = $this->getChildObject('contrat');
        $loyers = $this->getChildrenObjects('rents', array(), 'position', 'asc');

        if (!BimpObject::objectLoaded($contrat)) {
            $success = 'Contrat généré avec succès';
            $dt_end = new DateTime($this->getData('date_loyer'));

            foreach ($loyers as $loyer) {
                $dt_end->add(new DateInterval("P" . (int) $loyer->getData('qty') * (int) $loyer->getData('periodicity') . "M"));
            }

            BimpTools::loadDolClass('contrat');

            $contrat->socid = $this->getData('id_client');
            $contrat->date_contrat = $this->getData('date_create');
            $contrat->commercial_signature_id = (int) $this->getData('id_commercial');
            $contrat->commercial_suivi_id = (int) $this->getData('id_commercial');
            $contrat->mise_en_service = BimpTools::getDateForDolDate($this->getData('date_livraison'));
            $contrat->fin_validite = BimpTools::getDateForDolDate($dt_end->format('Y-m-d'));

            global $user;

            if ($contrat->create($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat), 'Echec de la création du contrat');
            } else {
                addElementElement('BF_Demande', 'contrat', $this->id, $contrat->id);
                $this->updateField('id_contrat', (int) $contrat->id);
                $contrat->validate($user);
            }
        } else {
            $success = 'Contrat mis à jour avec succès';

            foreach ((object) $contrat->fetch_lines() as $line) {
                $contrat->deleteline($line->id, $user);
            }
        }

        if (!count($errors) && BimpObject::objectLoaded($contrat)) {
            $dt = new DateTime($this->getData('date_loyer'));

            foreach ($loyers as $loyer) {
                $qty = (int) $loyer->getData('quantity');
                $periodicity = (int) $loyer->getData('periodicity');
                $amount_ht = (float) $loyer->getData('amount_ht');

                $desc = 'Paiement ' . BF_Rent::$periodicities_masc[$periodicity] . ' de ';
                $desc .= BimpTools::displayMoneyValue($amount_ht, 'EUR') . ' HT sur ' . $qty;
                if ($qty > 1) {
                    $desc .= BF_Rent::$period_label_plur[$periodicity];
                } else {
                    $desc .= BF_Rent::$period_label[$periodicity];
                }

                $start_date = $dt->format('Y-m-d');
                $dt->add(new DateInterval("P" . $qty * $periodicity . "M"));

                $contrat->addline($desc, (float) $loyer->getData('amount_ht'), (int) $loyer->getData('qty'), 0, 0, 0, 0, 0, $start_date, $dt->format('Y-m-d'));
                $contrat->activateAll($user, $start_date);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGenerateFactureBanque($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $refinanceurs = $this->getChildrenObjects('refinanceurs', array(
            'status' => 2
        ));

        $errors = $this->checkDemande();
        if (!count($refinanceurs)) {
            $errors[] = 'Un refinanceur en accord est obligatoire';
        }

        if (!count($errors)) {
            $success = 'Facture créée avec succès';
            $facture = $this->getChildObject('facture_banque');

            if (!BimpObject::objectLoaded($facture)) {
                $facture->set('fk_soc', $this->getData("id_client"));  // todo
                $facture->set('datef', date('Y-m-d'));
                $facture->set('date_lim_reglement', date('Y-m-d'));
                $facture->set('ef_type', "S");
                $facture->set('entrepot', "1");

                $fac_errors = $facture->create($warnings);
                if (count($fac_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture');
                } else {
                    $this->updateField('id_facture', (int) $facture->id);
                    addElementElement('BF_Demande', 'facture', $this->id, $facture->id);
                }
            } else {
                $success = 'Mise à jour de la facture effectuée avec succès';
                if ((int) $facture->getData('fk_satut') !== 0) {
                    $errors[] = 'La facture existe déjà et n\'est plus modifiable';
                }
            }

            if (!count($errors) && BimpObject::objectLoaded($facture)) {
                $total_emprunt = $this->getTotalEmprunt();

                $cout_banque = 10; // A MODIFIER EN FONCTION DU TAUX ET COEF
                $taux_tva = 0; // A MOFIFIER EN FONCTION DE SI ON A UN TAUX POUR LES EMPRUNS

                $emprunt_label = '';
                foreach ($refinanceurs as $refinanceur) {
                    $total_emprunt += $cout_banque;
                    $emprunt_label .= ($emprunt_label ? ', ' : '' ) . $total_emprunt;
                }

                $line = $facture->getLineInstance();

                if (!$line->find(array(
                            'id_obj'             => (int) $facture->id,
                            'linked_id_object'   => $this->id,
                            'linked_object_name' => 'df_total_emprunt'
                                ), true, true)) {
                    $line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => (int) ObjectLine::LINE_FREE,
                        'deletable'          => 0,
                        'editable'           => 0,
                        'remisable'          => 1,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'df_total_emprunt'
                    ));
                }

                $line->desc = 'Demande de financement n° ' . $this->id;
                $line->qty = 1;
                $line->pu_ht = $total_emprunt;
                $line->tva_tx = $taux_tva;

                if (!$line->isLoaded()) {
                    $line_errors = $line->create();
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne à la facture');
                    }
                } else {
                    $line_errors = $line->update();
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la ligne de facture');
                    }
                }

                $success .= ($success ? '<br/>' : '') . 'Montant emprunt : ' . $emprunt_label;
            }
        }

        return array(
            'warnings' => $warnings,
            'errors'   => $errors
        );
    }

    public function actionGenerateFactureClient($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Facture  créée avec succès';

        $errors = $this->checkDemande();

        if (!count($errors)) {
            $success = 'Facture client créée avec succès';
            $facture = $this->getChildObject('facture_client');

            if (!BimpObject::objectLoaded($facture)) {
                $facture->set('fk_soc', (int) $this->getData('id_client'));
                $facture->set('datef', date('Y-m-d'));
                $facture->set('date_lim_reglement', date('Y-m-d'));

                $fac_errors = $facture->create($warnings);
                if (count($fac_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture');
                } else {
                    $this->updateField('id_facture_client', (int) $facture->id);
                    addElementElement('BF_Demande', 'facture', $this->id, $facture->id);
                }
            } else {
                $success = 'Mise à jour de la facture effectuée avec succès';
                if ((int) $facture->getData('fk_satut') !== 0) {
                    $errors[] = 'La facture existe déjà et n\'est plus modifiable';
                }
            }

            if (!count($errors) && BimpObject::objectLoaded($facture)) {
                $line = $facture->getLineInstance();

                if (!$line->find(array(
                            'id_obj'             => (int) $facture->id,
                            'linked_id_object'   => $this->id,
                            'linked_object_name' => 'bf_demande_vr_achat'
                                ), true, true)) {
                    $line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => (int) ObjectLine::LINE_FREE,
                        'deletable'          => 0,
                        'editable'           => 0,
                        'remisable'          => 1,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'bf_demande_vr_achat'
                    ));
                }

                $line->desc = 'Total de la valeur résiduelle d\'achat pour la demande de financement' . ' DF' . $this->id;
                $line->qty = 1;
                $line->pu_ht = (float) $this->getData('vr');
                $line->tva_tx = 0;

                if (!$line->isLoaded()) {
                    $line_errors = $line->create();
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la vr à la facture');
                    }
                } else {
                    $line_errors = $line->update();
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la vr pour la facture');
                    }
                }
            }
        }

        return array(
            'warnings' => $warnings,
            'errors'   => $errors,
            'success'  => $success,
        );
    }

    public function actionGenerateFactureFourn($data, &$success)
    {

        $errors = array();
        $warnings = array();
        $success = '';

        $errors = $this->checkDemande();

        if (!count($errors)) {
            $success = 'Facture fournisseur créée avec succès';
            $facture = $this->getChildObject('facture_fournisseur');

            if (!BimpObject::objectLoaded($facture)) {
                $facture->set('fk_soc', 0); // ?
                $facture->set('datef', date('Y-m-d'));
                $facture->set('date_lim_reglement', date('Y-m-d'));

                $fac_errors = $facture->create($warnings);
                if (count($fac_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture');
                } else {
                    $this->updateField('id_facture_fournisseur', (int) $facture->id);
                    addElementElement('BF_Demande', 'facture', $this->id, $facture->id);
                }
            } else {
                $success = 'Mise à jour de la facture effectuée avec succès';
                if ((int) $facture->getData('fk_satut') !== 0) {
                    $errors[] = 'La facture existe déjà et n\'est plus modifiable';
                }
            }

            if (!count($errors) && BimpObject::objectLoaded($facture)) {
                $line = $facture->getLineInstance();

                if (!$line->find(array(
                            'id_obj'             => (int) $facture->id,
                            'linked_id_object'   => $this->id,
                            'linked_object_name' => 'bf_demande_vr_vente'
                                ), true, true)) {
                    $line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => (int) ObjectLine::LINE_FREE,
                        'deletable'          => 0,
                        'editable'           => 0,
                        'remisable'          => 1,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'bf_demande_vr_vente'
                    ));
                }

                $line->desc = 'Total de la valeur résiduelle de vente pour la demande de financement' . ' DF' . $this->id;
                $line->qty = 1;
                $line->pu_ht = (float) $this->getData('vr_vente');
                $line->tva_tx = 0;

                if (!$line->isLoaded()) {
                    $line_errors = $line->create();
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la vr à la facture');
                    }
                } else {
                    $line_errors = $line->update();
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la vr pour la facture');
                    }
                }
            }
        }

        return array(
            'warnings' => $warnings,
            'errors'   => $errors,
            'success'  => $success,
        );
    }

    public function actionGenerateCommandesFourn($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la demande de financement absent';
        } elseif (!isset($data['commandes_fourn']) || !is_array($data['commandes_fourn']) || !count($data['commandes_fourn'])) {
            $errors[] = 'Aucun élément sélectionné pour la création de nouvelle(s) commande(s) fournisseur';
        } elseif (!isset($data['id_entrepot']) || !(int) $data['id_entrepot']) {
            $errors[] = 'Aucun entrepôt séléctionné';
        } else {
            $check = false;
            foreach ($data['commandes_fourn'] as $i => $fourn_data) {
                if (!isset($fourn_data['lines']) || !is_array($fourn_data['lines']) || !count($fourn_data['lines'])) {
                    continue;
                }
                if (!isset($fourn_data['id_fourn']) || !(int) $fourn_data['id_fourn']) {
                    $warnings[] = 'ID fournisseur absent pour la commande à créer n° ' . ($i + 1);
                } else {
                    $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $fourn_data['id_fourn']);
                    if (!BimpObject::objectLoaded($fourn)) {
                        $warnings[] = $errors[] = 'ID fournisseur invalide pour la commande à créer n° ' . ($i + 1);
                    } else {
                        $has_line = false;
                        foreach ($fourn_data['lines'] as $line) {
                            if ((float) $line['qty'] > 0) {
                                $has_line = true;
                                break;
                            }
                        }

                        if (!$has_line) {
                            continue;
                        }

                        $comm_errors = $this->createCommandeFournisseur((int) $data['id_entrepot'], (int) $fourn_data['id_fourn'], $fourn_data['lines']);
                        if (count($comm_errors)) {
                            $title = 'Des erreurs sont survenues lors de la création de la commande pour le fournisseur "' . $fourn->getData('nom') . '"';
                            $warnings[] = BimpTools::getMsgFromArray($comm_errors, $title);
                        } else {
                            $check = true;
                            $success .= ($success ? '<br/>' : '') . ' Création de la commande effectuée avec succès pour le fournisseur "' . $fourn->getData('nom') . '"';
                        }
                    }
                }
            }
            if (!$check) {
                $errors[] = 'Aucun élément trouvé pour la création de nouvelle(s) commande(s) fournisseur';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetCommandesFournLinesQties($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Quantités mises à jour avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la demande de financement absent';
        } else {
            if (!isset($data['new_qties']) || !is_array($data['new_qties']) || !count($data['new_qties'])) {
                $errors[] = 'Quantités à mettre à jour invalides ou absentes';
            } else {
                foreach ($data['new_qties'] as $i => $new_qty) {
                    $bf_line = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Line', (int) $new_qty['id_bf_line']);
                    if (!$bf_line->isLoaded()) {
                        $errors[] = 'ID de la ligne absent pour les quantités à mettre à jour n°' . $i;
                    } else {
                        $line_errors = $bf_line->updateCommandeFournLine((int) $new_qty['id_commande'], (float) $new_qty['qty'], true);
                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors);
                        }
                    }
                }
            }
        }

        if (count($errors)) {
            $warnings[] = 'Les données ne sont peut-être pas à jour, veuillez actualiser la page en cours';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddElementsToFacture($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $elements = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la demande de financement absent';
        }
        if (!isset($data['object_name']) || !$data['object_name']) {
            $errors[] = 'Type d\'élément à ajouter à la facture absent';
        } elseif (!in_array($data['object_name'], array('BF_RentExcept', 'BF_FraisDivers'))) {
            $errors[] = 'Type d\'élément à ajouter invalide (' . $data['object_name'] . ')';
        }

        $instance = BimpObject::getInstance('bimpfinancement', $data['object_name']);
        if (!isset($data['elements']) || !is_array($data['elements']) || !count($data['elements'])) {
            $errors[] = 'Aucun ' . $instance->getLabel() . ' sélectionné' . ($instance->isLabelFemale() ? 'e' : '');
        } else {
            foreach ($data['elements'] as $id_element) {
                $element = BimpCache::getBimpObjectInstance('bimpfinancement', $data['object_name'], (int) $id_element);
                if (!BimpObject::objectLoaded($element)) {
                    $errors[] = BimpTools::ucfirst($instance->getLabel('the')) . ' d\'ID ' . $id_element . ' n\'existe pas';
                } else {
                    if ((int) $element->getData('id_facture')) {
                        $elem_fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $element->getData('id_facture'));
                        if ($elem_fac->isLoaded()) {
                            $warnings[] = BimpTools::ucfirst($instance->getLabel('the')) . ' d\'ID ' . $id_element . ' a déjà été ajouté à une facture';
                            continue;
                        } else {
                            $element->updateField('id_facture', 0);
                        }
                    }
                }

                $elements[] = $id_element;
            }
            if (!count($elements)) {
                $errors[] = 'Aucun ' . $instance->getLabel() . ' valide à ajouter à la facture sélectionnée';
            }
        }

        if (!count($errors)) {
            if (!isset($data['id_facture']) || !(int) $data['id_facture']) {
                if (!isset($data['id_entrepot']) || !(int) $data['id_entrepot']) {
                    $errors[] = 'Aucun entrepôt sélectionné';
                } else {
                    // Création de la facture:
                    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                    $fac_errors = $facture->validateArray(array(
                        'fk_soc'   => (int) $this->getData('id_client'),
                        'type'     => Facture::TYPE_STANDARD,
                        'libelle'  => 'Demande de financement ' . $this->id,
                        'datef'    => date('Y-m-d'),
                        'ef_type'  => 'X',
                        'entrepot' => (int) $data['id_entrepot']
                    ));
                    if (!count($fac_errors)) {
                        $fac_warnings = array();
                        $fac_errors = $facture->create($fac_warnings, true);
                        if (count($fac_warnings)) {
                            $warnings = array_merge($warnings, $fac_warnings);
                        }
                    }
                    if (count($fac_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création de la facture');
                    } else {
                        $asso = new BimpAssociation($this, 'factures');
                        $asso->addObjectAssociation((int) $facture->id);
//                    addElementElement('facture', 'BF_Demande', $facture->id, $this->id);
                        $success .= 'Nouvelle facture "' . $facture->getRef() . '" créée avec succès<br/>';
                    }
                }
            } else {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $data['id_facture']);
                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'La facture sélectionnée n\'existe plus';
                } elseif ((int) $facture->getData('fk_statut') !== 0) {
                    $errors[] = 'La facture sélectionnée n\'est plus au statut "Brouillon"';
                }
            }

            if (!count($errors) && BimpObject::objectLoaded($facture)) {
                $nSuccess = 0;
                foreach ($elements as $id_element) {
                    $element = BimpCache::getBimpObjectInstance('bimpfinancement', $data['object_name'], (int) $id_element);

                    if (BimpObject::objectLoaded($element)) {
                        $line_errors = $element->createFactureLine($facture->id, $facture);
                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, BimpTools::ucfirst($element->getLabel()) . ' ' . $element->id . ': échec de l\'ajout à la facture');
                        } else {
                            $nSuccess++;
                        }
                    } else {
                        $errors[] = BimpTools::ucfirst($element->getLabel('the')) . ' d\'ID ' . $id_element . ' n\'existe pas';
                    }
                }
                if ($nSuccess) {
                    $success .= $nSuccess . ' ' . $instance->getLabel($nSuccess > 1 ? 'name_plur' : '') . ' ajouté' . ($nSuccess > 1 ? 's' : '') . ' à la facture avec succès';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}

<?php

class BF_Demande extends BimpObject
{

    const STATUS_NEW = -1;
    const STATUS_DRAFT = 0;
    const STATUS_ATTENTE = 1;
    const STATUS_ACCEPTED = 10;
    const STATUS_REFUSED = 20;
    const STATUS_CANCELED = 21;

    public static $status_list = array(
        self::STATUS_NEW       => array('label' => 'Nouvelle demande', 'far_file', 'classes' => array('info')),
        self::STATUS_DRAFT     => array('label' => 'Brouillon', 'icon' => 'far_file', 'classes' => array('warning')),
        self:: STATUS_ATTENTE  => array('label' => 'Acceptation refinanceur en attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self:: STATUS_ACCEPTED => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        self:: STATUS_REFUSED  => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self:: STATUS_CANCELED => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );

    const DEVIS_NONE = 0;
    const DEVIS_GENERATED = 10;
    const DEVIS_SEND = 11;
    const DEVIS_ACCEPTED = 20;
    const DEVIS_REFUSED = 30;

    public static $devis_status_list = array(
        self::DEVIS_NONE      => array('label' => 'Devis non généré', 'icon' => 'fas_exclamation-circle', 'classes' => array('warning')),
        self::DEVIS_GENERATED => array('label' => 'Devis généré', 'icon' => 'fas_exclamation-circle', 'classes' => array('info')),
        self::DEVIS_SEND      => array('label' => 'Devis envoyé au client', 'icon' => 'fas_arrow-circle-right', 'classes' => array('info')),
        self::DEVIS_ACCEPTED  => array('label' => 'Devis accepté / signé', 'icon' => 'fas_exclamation-circle', 'classes' => array('success')),
        self::DEVIS_REFUSED   => array('label' => 'Devis refusé', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger'))
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
    public static $calc_modes = array(
        0 => '-',
        1 => 'A terme échu',
        2 => 'A terme à échoir'
    );

    // Getters booléens: 

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('duration', 'periodicity', 'vr_achat', 'vr_vente', 'mode_calcul'))) {
            if ((int) $this->getData('status') >= 10) {
                return 0;
            }
            return 1;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function areLinesEditable()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if ((int) $this->getData('status') > 0) {
            return 0;
        }

        return 1;
    }

    public function areDemandesRefinanceursEditable()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $status = (int) $this->getData('status');

        if ($status === self::STATUS_CANCELED) {
            return 0;
        }

        if ($status === self::STATUS_ACCEPTED) {
            $devis_status = (int) $this->getData('devis_status');
            if ($devis_status >= 10) {
                return 0;
            }
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = [])
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'cancel':
                if ((int) $this->getData('status') >= 10) {
                    $errors[] = 'Le statut actuel de cette demande de financement ne permet pas son annulation';
                    return 0;
                }
                return 1;

            case 'reopen':
                if ((int) $this->getData('status') !== self::STATUS_CANCELED) {
                    $errors[] = 'Cette demande de financement n\'est pas au statut "Annulée"';
                    return 0;
                }
                return 1;

            case 'generateDevisFinancement':
                if ((int) $this->getData('status') !== self::STATUS_ACCEPTED) {
                    $errors[] = 'Cette demande de financement n\'est pas au statut "Acceptée"';
                    return 0;
                }
                if ((int) $this->getData('id_devis_financement')) {
                    $errors[] = 'Le devis de financement a déj) été généré';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters Params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('generateDevis') && $this->canSetAction('generateDevis')) {
            $buttons[] = array(
                'label'   => 'Générer le devis de financement',
                'icon'    => 'fas_cogs',
                'onclick' => $this->getJsActionOnclick('generateDevis', array(), array())
            );
        }

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons[] = array(
                'label'   => 'Abandonner cette demande',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array())
            );
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => 'Réouvrir cette demande',
                'icon'    => 'fas_redo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array())
            );
        }

        return $buttons;
    }

    // Getters array: 

    public function getClientContactsArray($include_empty = true, $active_only = true)
    {
        $id_client = (int) $this->getData('id_client');

        if ($id_client) {
            return self::getSocieteContactsArray($id_client, $include_empty, '', $active_only);
        }

        return array();
    }

    public function getSupplierContactsAray($include_empty = true, $active_only = true)
    {
        $id_supplier = (int) $this->getData('id_supplier');

        if ($id_supplier) {
            return self::getSocieteContactsArray($id_supplier, $include_empty, '', $active_only);
        }

        return array();
    }

    public function getDemandeFacturesArray()
    {
        $factures = array();

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

        $factures[0] = 'Nouvelle facture';

        return $factures;
    }

    // Getters données:

    public function getLines($types = null)
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpfinancement', 'BF_Line');

            $filters = array();
            if (!is_null($types)) {
                if (is_string($types)) {
                    $type_code = $types;
                    $types = array();
                    switch ($type_code) {
                        case 'product':
                            $types[] = BF_Line::TYPE_PRODUCT;
                            break;

                        case 'free':
                            $types[] = BF_Line::TYPE_FREE;
                            break;

                        case 'text':
                            $types[] = BF_Line::TYPE_TEXT;
                            break;

                        case 'not_text':
                            $types[] = BF_Line::TYPE_PRODUCT;
                            $types[] = BF_Line::TYPE_FREE;
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

    public function getDefaultIdUserResp()
    {
        if (!$this->isLoaded()) {
            global $user;
            if (BimpObject::objectLoaded($user)) {
                return (int) $user->id;
            }
        }
        return 0;
    }

    public function getTotalDemande($withComm = true)
    {
        $tot = $this->getData('montant_materiels') + (float) $this->getData('montant_services') + (float) $this->getData('montant_logiciels');

        if ($withComm) {
            $tot += $this->getCommissionCommerciale() + $this->getCommissionFinanciere();
        }
        return (float) $tot;
    }

    public function getTotalEmprunt()
    {
        $total_emprunt = 0;
        $refinanceurs = $this->getChildrenObjects('demandes_refinanceurs', array(
            'status' => array('not_in' => 3)
        ));

        foreach ($refinanceurs as $refinanceur) {
            $total_emprunt += $refinanceur->getTotalEmprunt();
        }
        return $total_emprunt;
    }

    public function getTotalLoyer()
    {
        $totalEmp = 0;
        $refinanceurs = $this->getChildrenObjects('refinanceurs', array(
            'status'   => 2, 'periode2' => 0
        ));
        foreach ($refinanceurs as $refinanceur) {
            $totalEmp += $refinanceur->getTotalLoyer();
        }
        return $totalEmp;
    }

    public function getMarge($type = 0)
    {//type = 0 tous    1 = Financement  2 = Frais divers + Remb excep
        $total = 0;
        if ($type == 0 || $type == 1) {
            $total += $this->getTotalEmprunt() - $this->getTotalDemande(0) - $this->getCommissionCommerciale();
        }
        if ($type == 0 || $type == 2)
            $total += $this->getTotalRbtsExcept() + $this->getTotalFraisDivers();
        return $total;
    }

    public function getTotalRbtsExcept()
    {
        $loyerInters = $this->getChildrenObjects('rbts_except');
        $total = 0;
        foreach ($loyerInters as $loyerInter)
            $total += $loyerInter->getData("amount");
        return $total;
    }

    public function getTotalFraisDivers()
    {
        $fraisDivs = $this->getChildrenObjects('frais_divers');
        $total = 0;
        foreach ($fraisDivs as $fraisDiv)
            $total += $fraisDiv->getData("amount");
        return $total;
    }

    public function getCommissionCommerciale()
    {
        return $this->getTotalDemande(0) * $this->getData("commission_commerciale") / 100;
    }

    public function getCommissionFinanciere()
    {
        return ($this->getTotalDemande(0) + $this->getCommissionCommerciale()) * $this->getData("commission_financiere") / 100;
    }

    public function getRemainingElementsToOrder()
    {
        $elements = array();

        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpfinancement', 'BF_Line');
            $lines = $this->getLines('not_text');

            foreach ($lines as $line) {
                $qty = (float) $line->getData('qty');

                $line_commandes = $line->getData('commandes_fourn');
                if (is_array($line_commandes)) {
                    foreach ($line_commandes as $id_commande => $commande_qty) {
                        $qty -= (float) $commande_qty;
                    }
                }

                if ($qty > 0) {
                    $elements[(int) $line->id] = $qty;
                }
            }
        }

        return $elements;
    }

    public function getCommandesFournisseurData()
    {
        $commFourns = array();

        $lines = $this->getLines('not_text');

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

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';
        if ($this->isLoaded()) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $html .= '<b>Client : </b>' . $client->getLink();
            }
        }

        return $html;
    }

    public function renderCommandesInfos()
    {
        $html = '';

        $total = 0;
        $total_ordered = 0;
        $total_paid = 0;

        $lines = $this->getLines('not_text');

        foreach ($lines as $line) {
            $pa_ttc = (float) BimpTools::calculatePriceTaxIn((float) $line->getData('pa_ht'), (float) $line->getData('tva_tx'));
            $total += $pa_ttc * (float) $line->getData('qty');
            $total_ordered += $pa_ttc * (float) $line->getQtyOrdered();
        }

        $commandes = $this->getCommandesFournisseurData();

        foreach ($commandes as $id_fourn => $commandes) {
            foreach ($commandes as $comm_data) {
                foreach (BimpTools::getDolObjectLinkedObjectsList($comm_data['comm']->dol_object, $this->db) as $item) {
                    if ($item['type'] !== 'invoice_supplier') {
                        continue;
                    }

                    $facture_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                    if (BimpObject::objectLoaded($facture_fourn_instance)) {
                        $total_paid += $facture_fourn_instance->getTotalPaid();
                    }
                }
            }
        }

        $to_order = $total - $total_ordered;
        $to_pay = $total - $total_paid;

        $html .= '<table class="bimp_list_table">';
        $html .= '<tr>';
        $html .= '<th>Total Eléments financés</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<tr>';
        $html .= '<th>Total Commandé</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_ordered, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Total Commandes payées</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_paid, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Reste à commander</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($to_order, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Reste à payer</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($to_pay, 'EUR', true) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        return BimpRender::renderPanel('Infos', $html, '', array(
                    'type' => 'secondary',
                    'icon' => 'fas_info',
        ));
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

                    $html .= '<th style="max-width: 2000px;">';
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
                        $html .= '<th>Total TTC</th>';
                        $html .= '<th>Statut</th>';
                        $html .= '<th>PDF Commande</th>';
                        $html .= '<th>Facture(s)</th>';
                        $html .= '<th></th>';
                        $html .= '</tr>';
                        $html .= '</thead>';
                        foreach ($commandes as $commande_data) {
                            $commande = $commande_data['comm'];

                            $factures = array();

                            foreach (BimpTools::getDolObjectLinkedObjectsList($commande->dol_object, $this->db) as $item) {
                                if ($item['type'] !== 'invoice_supplier') {
                                    continue;
                                }

                                $facture_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                                if (BimpObject::objectLoaded($facture_fourn_instance)) {
                                    $factures[] = $facture_fourn_instance;
                                }
                            }

                            $html .= '<tr>';
                            $html .= '<td>' . $commande->getNomUrl(true, true, true, 'full') . '</td>';
                            $html .= '<td>' . BimpTools::displayMoneyValue($commande->getData('total_ttc'), 'EUR', true) . '</td>';
                            $html .= '<td>' . $commande->displayData('fk_statut') . '</td>';
                            $html .= '<td>';
                            $html .= $commande->displayPDFButton(true, false);
                            $html .= '</td>';
                            $html .= '<td>';
                            if (count($factures)) {
                                $html .= '<table>';
                                $html .= '<tbody>';
                                foreach ($factures as $fac_data) {
                                    $html .= '<tr>';
                                    $html .= '<td>' . $fac_data->getNomUrl(1, 1, 1, 'full') . '</td>';
                                    $html .= '<td>' . $fac_data->displayData('fk_statut') . '</td>';
                                    $html .= '<td>';
                                    if ((int) $fac_data->getData('paye')) {
                                        $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Payée</span>';
                                    } else {
                                        $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Non payée</span>';
                                    }
                                    $html .= '</td>';
                                    $html .= '<td>' . $fac_data->displayPDFButton(true, false) . '</td>';
                                    $html .= '</tr>';
                                }
                                $html .= '</tbody>';
                                $html .= '</table>';
                            }
                            $html .= '</td>';
                            $html .= '<td class="buttons">';
                            $html .= '<span class="displayDetailButton btn btn-light-default" onclick="toggleBfCommandeFournDetailDisplay($(this));">';
                            $html .= '<i class="' . BimpRender::renderIconClass('fas_list') . ' iconLeft"></i>Détail';
                            $html .= '<i class="' . BimpRender::renderIconClass('fas_caret-up') . ' iconRight"></i>';
                            $html .= '</span>';
                            $html .= '</td>';
                            $html .= '</tr>';

                            $html .= '<tr class="commande_fourn_elements_rows">';
                            $html .= '<td colspan="5" style="padding: 10px 30px; border-top-color: #fff">';
                            $html .= '<table class="objectSubList">';
                            $html .= '<tbody>';

                            // TODO: A refondre - id_fournisseur remplacé par commandes_fourn (id_comm_fourn => qty) 
//                            $fourn_lines = $this->getChildrenObjects('lines', array('id_fournisseur' => (int) $id_fourn), 'position', 'asc');
//                            foreach ($fourn_lines as $fourn_line) {
//                                $line = $fourn_line;
//                                $qty = 0;
//                                if (array_key_exists($fourn_line->id, $commande_data['lines'])) {
//                                    $line = $commande_data['lines'][$fourn_line->id]['line'];
//                                    $qty = $commande_data['lines'][$fourn_line->id]['qty'];
//                                }
//
//                                if (BimpObject::objectLoaded($line)) {
//                                    if ((int) $commande->getData('fk_statut') !== 0 && !(float) $qty) {
//                                        continue;
//                                    }
//                                    $html .= '<tr class="commande_fourn_element_row fourn_' . $fourn->id . '_line_' . $line->id . (!$qty ? ' deactivated' : '') . '"';
//                                    $html .= ' data-id_fourn="' . $fourn->id . '"';
//                                    $html .= ' data-id_commande="' . $commande->id . '"';
//                                    $html .= ' data-id_bf_line="' . $line->id . '">';
//                                    $html .= '<td>' . $line->displayDescription() . '</td>';
//                                    $html .= '<td>Qté: ';
//                                    if ((int) $commande->getData('fk_statut') === 0) {
//                                        $max = $qty;
//                                        if (isset($remains[(int) $fourn->id][(int) $line->id])) {
//                                            $max += (float) $remains[(int) $fourn->id][(int) $line->id]['qty'];
//                                        }
//                                        $html .= BimpInput::renderInput('qty', 'fourn_' . $fourn->id . '_comm_' . $commande->id . '_line_' . $line->id . '_qty', $qty, array(
//                                                    'extra_class' => 'line_qty_input',
//                                                    'step'        => $line->getQtyStep(),
//                                                    'data'        => array(
//                                                        'initial_qty' => $qty,
//                                                        'data_type'   => 'number',
//                                                        'decimals'    => $line->getQtyDecimals(),
//                                                        'min'         => 0,
//                                                        'max'         => $max,
//                                                        'unsigned'    => 1
//                                                    )
//                                        ));
//                                        $html .= '<p class="inputHelp" style="display: inline-block">Max: <span class="qty_max_value">' . $max . '</span></p>';
//                                    } else {
//                                        $html .= $qty;
//                                    }
//                                    $html .= '</td>';
//                                    $html .= '</tr>';
//                                }
//                            }
                            $html .= '</tbody>';
                            $html .= '</table>';
                            $html .= '</td>';
                            $html .= '<td></td>';
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
                $bc_list->addObjectChangeReload('BF_RbtExcept');
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

            $content .= '<table class="bimp_list_table">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th>Type</th>';
            $content .= '<th>Facture</th>';
            $content .= '<th>Date</th>';
            $content .= '<th>Statut</th>';
            $content .= '<th>Montant HT</th>';
            $content .= '<th>Payé</th>';
            $content .= '<th>Fichier PDF</th>';
            $content .= '</tr>';
            $content .= '</thead>';

            $content .= '<tbody>';

            if (count($factures)) {
                foreach ($factures as $fac) {
                    $content .= '<tr>';
                    $content .= '<td><strong>' . $fac['type'] . '</strong></td>';
                    $content .= '<td>' . $fac['nom_url'] . '</td>';
                    $content .= '<td>' . $fac['date'] . '</td>';
                    $content .= '<td>' . $fac['status'] . '</td>';
                    $content .= '<td>' . $fac['amount_ht'] . '</td>';
                    $content .= '<td>' . $fac['paid'] . '</td>';
                    $content .= '<td>' . $fac['file'] . '</td>';
                    $content .= '</tr>';
                }
            } else {
                $content .= '<tr>';
                $content .= '<td colspan="7" style="text-align: center">';
                $content .= BimpRender::renderAlerts('Il n\'y a aucune facture client enregistrée pour cette demande de financement pour le moment', 'info');
                $content .= '</td>';
                $content .= '</tr>';
            }

            $content .= '</tbody>';
            $content .= '</table>';

            $html .= BimpRender::renderPanel('Factures clients', $content, '', array(
                        'type' => 'secondary',
                        'icon' => 'fas_file-invoice-dollar'
            ));

            // Factures fournisseurs: 
            $factures = array();
            $facture = null;
            $content = '';

            $commandes_fourn = $this->getCommandesFournisseurData();

            foreach ($commandes_fourn as $id_fourn => $commandes) {
                if (is_array($commandes) && count($commandes)) {
                    foreach ($commandes as $commande_data) {
                        $commande = $commande_data['comm'];
                        foreach (BimpTools::getDolObjectLinkedObjectsList($commande->dol_object, $this->db) as $item) {
                            if ($item['type'] !== 'invoice_supplier') {
                                continue;
                            }

                            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($facture)) {
                                $factures[] = array(
                                    'nom_url'   => $facture->getNomUrl(0, true, true, 'full'),
                                    'date'      => $facture->displayData('datef'),
                                    'status'    => $facture->displayData('fk_statut'),
                                    'amount_ht' => $facture->displayData('total_ttc'),
                                    'paid'      => $facture->displayPaid(),
                                    'file'      => $facture->displayPDFButton(true, true)
                                );
                            }
                        }
                    }
                }
            }

            $content .= '<table class="bimp_list_table">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th>Type</th>';
            $content .= '<th>Facture</th>';
            $content .= '<th>Date</th>';
            $content .= '<th>Statut</th>';
            $content .= '<th>Montant HT</th>';
            $content .= '<th>Payé</th>';
            $content .= '<th>Fichier PDF</th>';
            $content .= '</tr>';
            $content .= '</thead>';

            if (count($factures)) {
                foreach ($factures as $fac) {
                    $content .= '<tr>';
                    $content .= '<td><strong>Facture fournisseur</strong></td>';
                    $content .= '<td>' . $fac['nom_url'] . '</td>';
                    $content .= '<td>' . $fac['date'] . '</td>';
                    $content .= '<td>' . $fac['status'] . '</td>';
                    $content .= '<td>' . $fac['amount_ht'] . '</td>';
                    $content .= '<td>' . $fac['paid'] . '</td>';
                    $content .= '<td>' . $fac['file'] . '</td>';
                    $content .= '</tr>';
                }
            } else {
                $content .= '<tr>';
                $content .= '<td colspan="7" style="text-align: center">';
                $content .= BimpRender::renderAlerts('Il n\'y a aucune facture fournisseur enregistrée pour cette demande de financement pour le moment', 'info');
                $content .= '</td>';
                $content .= '</tr>';
            }

            $content .= '</tbody>';
            $content .= '</table>';

            $html .= BimpRender::renderPanel('Factures fournisseurs', $content, '', array(
                        'type' => 'secondary',
                        'icon' => 'fas_file-invoice-dollar'
            ));
        }

        return $html;
    }

    public function renderInfoFin()
    {
        $this->checkObject();
        $html .= '<table class="bimp_list_table">';
        $html .= '<tr>';
        $html .= '<th>Total emprunt</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($this->getTotalEmprunt()) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Marge sur le financement</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($this->marges['marge1']) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Marge loyers inter + frais divers</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($this->marges['marge2']) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Marge totale</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($this->marges['total_marge']) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        return BimpRender::renderPanel('Totaux', $html, '', array(
                    'type' => 'secondary',
                    'icon' => 'fas_euro-sign'
        ));
    }

    public function renderCommissionInputs($field_name)
    {
        if ($this->field_exists($field_name) && $this->isFieldEditable($field_name)) {
            $html = '';

            $bc_field = new BC_Field($this, $field_name, 1);
            $html .= $bc_field->renderHtml();

            $html .= '<div style="margin-top: 5px;padding-left: 4px">';
            $html .= BimpInput::renderInput('text', $field_name . '_amount', 0, array(
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 2,
                            'min'       => 'none',
                            'max'       => 'none'
                        ),
                        'addon_right' => BimpRender::renderIcon('fas_euro-sign')
            ));
            $html .= '</div>';

            return $html;
        }

        return $this->displayData($field_name);
    }

    // Traitements: 

    public function calcMontants()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $montant_produits = 0;
            $montant_services = 0;
            $montant_logiciels = 0;

            $lines = $this->getLines('not_text');
            foreach ($lines as $line) {
                $total_ttc = (float) $line->getTotalTTC();
                switch ((int) $line->getData('product_type')) {
                    case BF_Line::PRODUIT:
                        $montant_produits += $total_ttc;
                        break;

                    case BF_Line::SERVICE:
                        $montant_services += $total_ttc;
                        break;

                    case BF_Line::LOGICIEL:
                        $montant_logiciels += $total_ttc;
                        break;
                }
            }

            $up = false;
            if ((float) $montant_produits !== $this->getInitData('montant_materiels')) {
                $this->set('montant_materiels', $montant_produits);
                $up = true;
            }
            if ((float) $montant_services !== $this->getInitData('montant_services')) {
                $this->set('montant_services', $montant_services);
                $up = true;
            }
            if ((float) $montant_logiciels !== $this->getInitData('montant_logiciels')) {
                $this->set('montant_logiciels', $montant_logiciels);
                $up = true;
            }

            if ($up) {
                $up_warnings = array();
                $up_errors = $this->update($up_warnings);

                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Des erreurs sont survenues lors de la mise à jour des montants de la demande de financement');
                }

                if (count($up_warnings)) {
                    $errors[] = BimpTools::getMsgFromArray($up_warnings, 'Des erreurs sont survenues suite à la mise à jour des montants de la demande de financement');
                }
            }
        }

        return $errors;
    }

    public function checkStatus()
    {
        if ($this->isLoaded()) {
            $cur_status = (int) $this->getData('status');
            if ($cur_status === self::STATUS_CANCELED) {
                return;
            }

            $new_status = self::STATUS_DRAFT;
            if ($cur_status < 0) {
                $new_status = self::STATUS_NEW;
            }

            $drs = $this->getChildrenObjects('demandes_refinanceurs');

            if (count($drs)) {
                $new_status = self::STATUS_DRAFT;

                $has_attente = false;
                $has_accepted = false;
                $has_refused = false;

                foreach ($drs as $dr) {
                    $dr_status = (int) $dr->getData('status');
                    if ($dr_status == BF_DemandeRefinanceur::STATUS_SELECTIONNEE) {
                        $has_accepted = true;
                    } elseif ($dr_status < 20) {
                        $has_attente = true;
                    } elseif ($dr_status == BF_DemandeRefinanceur::STATUS_REFUSEE) {
                        $has_refused = true;
                    }
                }

                if ($has_accepted) {
                    $new_status = self::STATUS_ACCEPTED;
                } elseif ($has_attente) {
                    $new_status = self::STATUS_ATTENTE;
                } elseif ($has_refused) {
                    $new_status = self::STATUS_REFUSED;
                }

                if ($new_status !== $cur_status) {
                    $this->setNewStatus($new_status);
                }
            }
        }
    }

    public function onChildSave($child)
    {
        if (is_a($child, 'BF_DemandeRefinanceur')) {
            $this->checkStatus();
        } elseif (is_a($child, 'BF_Line')) {
            $this->calcMontants();
        }

        return array();
    }

    public function onChildDelete($child)
    {
        if (is_a($child, 'BF_DemandeRefinanceur')) {
            $this->checkStatus();
        } elseif (is_a($child, 'BF_Line')) {
            $this->calcMontants();
        }

        return array();
    }

    public function generateDevis($data)
    {
        $errors = array();

        return $errors;
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

    public function traiteLinesFacture($facture, $totalFact)
    {
        $lines = $this->getChildrenObjects('lines', array(), 'position', 'asc');
        $totNonCache = $totCache = 0;

        foreach ($lines as $line) {
            if ($line->getData("in_contrat")) {
                $totNonCache += $line->getTotalLine();
            } else
                $totCache += $line->getTotalLine();
        }
//                if(($totNonCache + $totCache) != $total_emprunt)
//                    $errors[] = "Problémes dans les totaux !!! " . ($totNonCache + $totCache)." ".$total_emprunt;

        $coef = $totalFact / $totNonCache;

        $lines = $this->getChildrenObjects('lines', array(), 'position', 'asc');
        foreach ($lines as $lineT) {
            $line = $facture->getLineInstance();
            $line->reset();
            if ($lineT->getData("in_contrat")) {
                if (!$line->find(array(
                            'id_obj'             => (int) $facture->id,
                            'linked_id_object'   => $lineT->id,
                            'linked_object_name' => 'df_line'
                                ), true, true)) {
                    $line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => (int) ObjectLine::LINE_FREE,
                        'deletable'          => 1,
                        'editable'           => 1,
                        'remisable'          => 1,
                        'linked_id_object'   => (int) $lineT->id,
                        'linked_object_name' => 'df_line'
                    ));
                }

                $getDescSerials = $lineT->getSerialDesc();
                // Verif
                $line->desc = $getDescSerials->label . " " . $getDescSerials->serials;
                $line->qty = $lineT->getData("qty");
                $line->pu_ht = $lineT->getData("pu_ht") * $coef;
                $line->tva_tx = $lineT->getData("tva_tx");

                if (!$line->isLoaded()) {
                    $w = array();
                    $line_errors = $line->create($w, true);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de la ligne à la facture');
                    }
                } else {
                    $line_errors = $line->update();
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la ligne de facture');
                    }
                }
            } else {
                if ($line->find(array(
                            'id_obj'             => (int) $facture->id,
                            'linked_id_object'   => $lineT->id,
                            'linked_object_name' => 'df_line'
                                ), true, true))
                    $line->delete();
            }
        }
    }

    // Actions: 

    public function actionGenerateDevis($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $errors[] = 'En cours de dev.';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}

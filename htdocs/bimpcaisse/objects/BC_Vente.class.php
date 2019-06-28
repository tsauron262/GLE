<?php

class BC_Vente extends BimpObject
{

    const BC_VENTE_ABANDON = 0;
    const BC_VENTE_BROUILLON = 1;
    const BC_VENTE_VALIDEE = 2;

    public static $facture_model = 'bimpfact';
    public static $facture_default_bank_account_id = 1;
    public static $states = array(
        0 => array('label' => 'Abandonnée', 'icon' => 'times', 'classes' => array('danger')),
        1 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        2 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('success'))
    );

    // Getters: 

    public function isDeletable($force_delete = false)
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') < 2) {
                return 1;
            }
        }

        return 0;
    }

    public function getClient_contactsArray()
    {
        $contacts = array(
            0 => ''
        );
        $id_client = $this->getData('id_client');
        if (!is_null($id_client) && $id_client) {
            $where = '`fk_soc` = ' . (int) $id_client;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        return $contacts;
    }

    public function getArticlesArray()
    {
        if ($this->isLoaded()) {
            $articles = array();
            foreach ($this->getChildrenObjects('articles') as $article) {
                $product = $article->getChildObject('product');
                if (BimpObject::objectLoaded($product)) {
                    $articles[$article->id] = $product->getRef() . ' - ' . $product->getName();
                }
            }
            return $articles;
        }

        return array();
    }

    public function getCond_reglementsArray()
    {
        return self::getCondReglementsArray();
    }

    public function getAjaxData()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $vente_ht = (int) $this->getData('vente_ht');
        $id_client = (int) $this->getData('id_client');

        $nb_articles = 0;
        $total_ttc = 0;
        $articles = array();
        $remises = array();
        $returns = array();
        $total_remises_articles = 0;
        $total_remises_vente = 0;
        $total_remises = 0;
        $total_discounts = 0;
        $total_returns = 0;
        $nb_returns = 0;

        $toPay = 0;
        $toReturn = 0;
        $avoir = 0;

        foreach ($this->getChildrenObjects('articles') as $article) {
            $qty = (int) $article->getData('qty');

            if ($vente_ht) {
                $unit_price = (float) $article->getData('unit_price_tax_ex');
                $tva = 0;
            } else {
                $unit_price = (float) $article->getData('unit_price_tax_in');
                $tva = (float) $article->getData('tva_tx');
            }

            $article_total_ttc = (float) ($unit_price * $qty);
            $nb_articles += $qty;
            $total_ttc += $article_total_ttc;
            $articles[(int) $article->id] = array(
                'id_article'    => (int) $article->id,
                'qty'           => (int) $qty,
                'unit_price'    => (float) $unit_price,
                'unit_price_ht' => (float) $article->getData('unit_price_tax_ex'),
                'total_ttc'     => $article_total_ttc,
                'tva'           => $tva,
                'total_remises' => 0
            );
        }

        foreach ($this->getChildrenObjects('remises') as $remise) {
            $id_article = (int) $remise->getData('id_article');
            $type = (int) $remise->getData('type');
            $montant = 0;
            $label = $remise->getData('label');

            if ($id_article) {
                $per_unit = (int) $remise->getData('per_unit');
                if (isset($articles[$id_article])) {
                    switch ($type) {
                        case 1:
                            $percent = (float) $remise->getData('percent');
                            $label .= ' (' . str_replace('.', ',', '' . $percent) . ' % ';
                            if ($percent) {
                                if ($per_unit) {
                                    $montant = (float) ((float) $articles[$id_article]['total_ttc'] * ($percent / 100));
                                    $label .= ' sur chaque unité';
                                } else {
                                    $montant = (float) ((float) $articles[$id_article]['unit_price'] * ($percent / 100));
                                    $label .= ' sur 1 unité';
                                }
                            }
                            $label .= ')';
                            break;

                        case 2:
                            $montant = (float) $remise->getData('montant');
                            if ($per_unit) {
                                $label .= ' (' . BimpTools::displayMoneyValue($montant, 'EUR') . ' par unité)';
                                $montant *= (int) $articles[$id_article]['qty'];
                            } else {
                                $label .= ' (sur 1 unité)';
                            }
                            break;
                    }
                    $articles[$id_article]['total_remises'] += $montant;
                    $total_remises_articles += $montant;
                } else {
                    $remise->delete();
                }
            } else {
                switch ($type) {
                    case 1:
                        $percent = (float) $remise->getData('percent');
                        if ($percent) {
                            $montant = (float) ($total_ttc * ($percent / 100));
                        }
                        $label .= ' (' . str_replace('.', ',', '' . $percent) . ' %)';
                        break;

                    case 2:
                        $montant = (float) $remise->getData('montant');
                        break;
                }
                $total_remises_vente += $montant;
            }
            $remises[] = array(
                'id_remise'  => (int) $remise->id,
                'label'      => $label,
                'montant'    => BimpTools::displayMoneyValue($montant, 'EUR'),
                'id_article' => $id_article
            );
            $total_remises += $montant;
        }

        foreach ($this->getChildrenObjects('returns') as $return) {
            $label = '';
            $qty = (int) $return->getData('qty');
            $price_ttc = (float) $return->getData('unit_price_tax_in');
            $return_total_ttc = $qty * $price_ttc;

            $total_returns += $return_total_ttc;
            $nb_returns += $qty;

            $ref = '';
            $serial = '';
            
            $eq_warnings = array();

            if ((int) $return->getData('id_equipment')) {
                $equipment = $return->getChildObject('equipment');
                if (BimpObject::ObjectLoaded($equipment)) {
                    $serial = $equipment->getData('serial');
                    $product = $equipment->getChildObject('product');
                    if (BimpObject::ObjectLoaded($product)) {
                        $ref = $product->ref;
                    }
                }
                $eq_warnings = $equipment->checkPlaceForReturn($id_client);
            } else {
                $product = $return->getChildObject('product');
                if (BimpObject::ObjectLoaded($product)) {
                    $ref = $product->getRef();
                }
            }


            $returns[] = array(
                'id_return'     => (int) $return->id,
                'label'         => $return->getLabel(),
                'qty'           => $qty,
                'unit_price'    => BimpTools::displayMoneyValue($price_ttc, 'EUR'),
                'unit_price_ht' => BimpTools::displayMoneyValue((float) $return->getData('unit_price_tax_ex'), 'EUR'),
                'total_ttc'     => BimpTools::displayMoneyValue($return_total_ttc, 'EUR'),
                'tva'           => (float) $return->getData('tva_tx') . ' %',
                'defective'     => (int) $return->getData('defective'),
                'infos'         => htmlentities((string) $return->getData('infos')),
                'ref'           => $ref,
                'serial'        => $serial,
                'warnings'      => $eq_warnings
            );
        }

        $total_discounts = (float) $this->getTotalDiscounts();

        $toPay = $total_ttc - $total_remises - $total_returns - $total_discounts;

        if ($toPay < 0) {
            $avoir = -$toPay;
            $avoir = round($avoir, 2, PHP_ROUND_HALF_DOWN);
            $toPay = 0;
        } else {
            foreach ($this->getChildrenObjects('paiements') as $paiement) {
                $montant = (float) $paiement->getData('montant');
                $toPay -= $montant;
            }

            $toPay = round($toPay, 2, PHP_ROUND_HALF_DOWN);
            if ($toPay < 0) {
                $toReturn = -$toPay;
                $toPay = 0;
            }
        }

        $paiement_differe = 0;
        $id_cond_default = (int) BimpCore::getConf('bimpcaisse_id_cond_reglement_default');

        if ((int) $this->getData('id_cond_reglement') !== $id_cond_default) {
            $paiement_differe = 1;
        }

        return array(
            'id_vente'               => (int) $this->id,
            'nb_articles'            => $nb_articles,
            'nb_returns'             => $nb_returns,
            'total_ttc'              => $total_ttc,
            'total_remises_vente'    => $total_remises_vente,
            'total_remises_articles' => $total_remises_articles,
            'total_remises'          => $total_remises,
            'total_returns'          => $total_returns,
            'total_discounts'        => $total_discounts,
            'toPay'                  => $toPay,
            'toReturn'               => $toReturn,
            'avoir'                  => $avoir,
            'articles'               => $articles,
            'remises'                => $remises,
            'returns'                => $returns,
            'paiement_differe'       => $paiement_differe
        );
    }

    public function getCurrentEquipments()
    {
        $where = '`id_vente` = ' . (int) $this->id . ' AND `id_equipment` > 0';
        $rows = $this->db->getRows('bc_vente_article', $where, null, 'array', array(
            'id', 'id_equipment'
        ));

        $list = array();
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $list[(int) $r['id_equipment']] = (int) $r['id'];
            }
        }

        return $list;
    }

    public function getCurrentProducts()
    {
        $where = '`id_vente` = ' . (int) $this->id . ' AND `id_product` > 0 AND `id_equipment` = 0';
        $rows = $this->db->getRows('bc_vente_article', $where, null, 'array', array(
            'id', 'id_product'
        ));

        $list = array();
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $list[(int) $r['id_product']] = (int) $r['id'];
            }
        }

        return $list;
    }

    public function getCurrentReturnedEquipments()
    {
        $currentReturnedEquipments = array();

        $returns = $this->getChildrenObjects('returns');

        foreach ($returns as $return) {
            $id_equipment = (int) $return->getData('id_equipment');
            if ($id_equipment) {
                $currentReturnedEquipments[] = $id_equipment;
            }
        }

        return $currentReturnedEquipments;
    }

    public function getDocumentExtraButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') === 2) {
                if ((int) $this->getData('id_facture')) {
                    $facture = $this->getChildObject('facture');
                    if (!is_null($facture) && isset($facture->id) && $facture->id) {
                        $file = dol_sanitizeFileName($facture->ref);
                        $url = DOL_URL_ROOT . '/document.php?modulepart=facture&attachment=0';
                        $url .= '&file=' . htmlentities($file . '/' . $file) . '.pdf';
                        $buttons[] = array(
                            'label'   => 'Fichier PDF de la facture',
                            'icon'    => 'fas_file-pdf',
                            'onclick' => htmlentities('window.open(\'' . $url . '\', \'_blank\', "menubar=no, status=no, width=370, height=600");')
                        );

                        if ((int) $this->getData('id_avoir')) {
                            $avoir = $this->getChildObject('avoir');
                            if (!is_null($avoir) && isset($avoir->id) && $avoir->id) {
                                $file = dol_sanitizeFileName($avoir->ref);
                                $url = DOL_URL_ROOT . '/document.php?modulepart=facture&attachment=0';
                                $url .= '&file=' . htmlentities($file . '/' . $file) . '.pdf';
                                $buttons[] = array(
                                    'label'   => 'Fichier PDF de l\'avoir',
                                    'icon'    => 'far_file-pdf',
                                    'onclick' => htmlentities('window.open(\'' . $url . '\', \'_blank\', "menubar=no, status=no, width=370, height=600");')
                                );
                            }
                        }

                        $url = DOL_URL_ROOT . '/bimpcaisse/ticket.php?id_vente=' . $this->id;
                        $buttons[] = array(
                            'label'   => 'Ticket de caisse',
                            'icon'    => 'fas_receipt',
                            'onclick' => htmlentities('window.open(\'' . $url . '\', \'_blank\', "menubar=no, status=no, width=370, height=600");')
                        );
                    }
                }
            }
        }
        return $buttons;
    }

    public function getDefaultListExtraButtons()
    {
        $buttons = $this->getDocumentExtraButtons();
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') !== 2) {
                $buttons[] = array(
                    'label'   => 'Abandonner la vente',
                    'icon'    => 'fas_times',
                    'onclick' => 'setVenteStatus($(this), ' . $this->id . ', 0)'
                );
                $buttons[] = array(
                    'label'   => 'Editer',
                    'icon'    => 'fas_edit',
                    'onclick' => 'loadVente($(this), ' . $this->id . ');'
                );
            }
        }

        return $buttons;
    }

    public function getAvailableCustomerDiscountsArray()
    {
        $discounts = array();

        $id_client = (int) $this->getData('id_client');
        if ($id_client) {
            global $conf;

            $asso = new BimpAssociation($this, 'discounts');

            $where = '`fk_soc` = ' . (int) $id_client . ' AND `entity` = ' . $conf->entity;
            $where .= ' AND `fk_facture` IS NULL AND `fk_facture_line` IS NULL';
            $rows = $this->db->getRows('societe_remise_except', $where, null, 'array', array(
                'rowid', 'amount_ttc', 'description'
            ));
            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $r) {
                    $discounts[(int) $r['rowid']] = $r['description'] . ' : ' . BimpTools::displayMoneyValue((float) $r['amount_ttc'], 'EUR') . ' TTC';
                }
            }
        }

        return $discounts;
    }

    public function getNbArticles()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT SUM(`qty`) as qty FROM ' . MAIN_DB_PREFIX . 'bc_vente_article WHERE `id_vente` = ' . (int) $this->id;
            $results = $this->db->executeS($sql);
            if (isset($results[0]->qty)) {
                return (int) $results[0]->qty;
            }
        }

        return 0;
    }

    public function getTotalDiscounts()
    {
        if ($this->isLoaded()) {
            $asso = new BimpAssociation($this, 'discounts');

            $discounts_list = $asso->getAssociatesList();
            if (count($discounts_list)) {
                $rows = $this->db->getRows('societe_remise_except', '`rowid` IN (' . implode(',', $discounts_list) . ')', null, 'array', array('amount_ttc'));
                $total_discounts = 0;
                foreach ($rows as $r) {
                    $total_discounts += (float) $r['amount_ttc'];
                }
                return $total_discounts;
            }
        }

        return 0;
    }

    // Afficages: 

    public function displayDate()
    {
        if ($this->isLoaded()) {
            $date = $this->getData('date_create');
            $DT = new DateTime($date);
            return $DT->format('d / m / Y');
        }

        return '';
    }

    public function displayTime()
    {
        if ($this->isLoaded()) {
            $date = $this->getData('date_create');
            $DT = new DateTime($date);
            return $DT->format('H \H i');
        }

        return '';
    }

    public function displayNbArticles()
    {
        $nbArticles = (int) $this->getNbArticles();
        if ($nbArticles) {
            return $nbArticles;
        }

        return '';
    }

    public function displayTotalRemises()
    {
        if ($this->isLoaded()) {
            $data = $this->getAjaxData();
            return BimpTools::displayMoneyValue($data['total_remises'], 'EUR');
        }
        return '';
    }

    public function displayTotalWithoutRemises()
    {
        $total_ttc = 0;
        if ($this->isLoaded()) {
            $articles = $this->getChildrenObjects('articles');
            foreach ($articles as $article) {
                if ((int) $this->getData('vente_ht')) {
                    $total_ttc += (float) ((float) $article->getData('unit_price_tax_ex') * (int) $article->getData('qty'));
                } else {
                    $total_ttc += (float) ((float) $article->getData('unit_price_tax_in') * (int) $article->getData('qty'));
                }
            }
        }
        return BimpTools::displayMoneyValue($total_ttc, 'EUR');
    }

    public function displayTotalDiscounts()
    {
        if ($this->isLoaded()) {
            $total = (int) $this->getTotalDiscounts();
            return BimpTools::displayMoneyValue($total, 'EUR');
        }
        return '';
    }

    public function displayTotalRetours()
    {
        if ($this->isLoaded()) {
            $data = $this->getAjaxData();
            return BimpTools::displayMoneyValue($data['total_returns'], 'EUR');
        }
        return '';
    }

    public function defaultDisplayDiscountsItem($id_discount)
    {
        $item = $this->db->getRow('societe_remise_except', '`rowid` = ' . (int) $id_discount, array('amount_ttc', 'description'));
        if (!is_null($item)) {
            return $item->description . ' : ' . BimpTools::displayMoneyValue((float) $item->amount_ttc, 'EUR') . ' TTC';
        }

        return BimpRender::renderAlerts('Remise d\'ID ' . $id_discount . ' non trouvée');
    }

    // Rendus HTML: 

    public function renderCreationViewHtml()
    {
        $html = '';

        $html .= '<div id="curVenteGlobal" class="row">';

        $html .= '<div id="currentVenteErrors" class="col-lg-12"></div>';

        // Choix Commercial: 
        $id_user_resp = (int) $this->getData('id_user_resp');
        if (!$id_user_resp) {
            global $user;
            $id_user_resp = $user->id;
        }
        $html .= '<div class="col-lg-12">';
        $html .= '<div id="curVenteCommercial" class="venteSection">';
        $html .= '<span style="font-weight: bold; font-size: 14px;">';
        $html .= BimpRender::renderIcon('fas_user-circle', 'iconLeft');
        $html .= 'Commercial: ';
        $html .= '</span>';
        $html .= BimpInput::renderInput('search_user', 'id_user_resp', $id_user_resp);
        
        $html .= '<br/><span style="font-weight: bold; font-size: 14px;">';
        $html .= BimpRender::renderIcon('pencil', 'iconLeft');
        $html .= 'Note: ';
        $html .= '</span>';
        $html .= BimpInput::renderInput('text', 'note_plus', $this->getData('note_plus'));

        // Bouton "Actualiser": 
        $html .= '<span class="btn btn-default" style="float: right; display: inline-block; margin-top: -2px" onclick="Vente.refresh();">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser la vente';
        $html .= '</span>';

        $html .= '</div>';
        $html .= '</div>';

        // Partie de gauche (Choix Client / Ajout produit / paiements)         
        $html .= '<div id="curVenteLeft" class="col-sm-12 col-md-7 col-lg-8">';
        $html .= '<div class="row">';

        // Client: 
        $html .= '<div class="col-sm-12 col-md-6">';
        $html .= '<div id="curVentePanier" class="venteSection">';
        $html .= '<div class="venteSectionHeader">';
        $html .= '<i class="fa fa-user iconLeft"></i>';
        $html .= '<span class="venteSectionTitle">Client</span>';
        $html .= '</div>';
        $html .= '<div class="venteSectionBody">';
        $html .= $this->renderClientContent();
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Ajout produit: 
        $html .= '<div class="col-sm-12 col-md-6">';
        $html .= '<div id="curVenteAddProduct" class="venteSection">';
        $html .= '<div class="venteSectionHeader">';
        $html .= '<i class="fa fa-plus-circle iconLeft"></i>';
        $html .= '<span class="venteSectionTitle">Ajouter un article</span>';
        $html .= '</div>';
        $html .= '<div class="venteSectionBody">';
        $html .= $this->renderAddProductContent();
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
//        
        $html .= '</div>';

        // Paiement: 
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';
        $html .= '<div id="curVentePanier" class="venteSection">';
        $html .= '<div class="venteSectionHeader">';
        $html .= '<i class="fa fa-euro iconLeft"></i>';
        $html .= '<span class="venteSectionTitle">Paiement</span>';
        $html .= '</div>';
        $html .= '<div class="venteSectionBody">';
        $html .= $this->renderPaiementContent();
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        // Partie de droite (Panier produits) 
        $html .= '<div id="curVenteRight" class="col-sm-12 col-md-5 col-lg-4">';

        // Panier:
        $html .= '<div id="curVentePanier" class="venteSection">';
        $html .= '<div class="venteSectionHeader">';
        $html .= '<i class="fa fa-shopping-basket iconLeft"></i>';
        $html .= '<span class="venteSectionTitle">Panier</span>';
        $html .= '</div>';
        $html .= '<div class="venteSectionBody">';
        $html .= $this->renderCartContent();
        $html .= '</div>';
        $html .= '</div>';

        // Retours produits: 
        $html .= '<div id="curVenteRetours" class="venteSection">';
        $html .= '<div class="venteSectionHeader">';
        $html .= '<i class="fa fa-reply iconLeft"></i>';
        $html .= '<span class="venteSectionTitle">Retours produits</span>';
        $html .= '</div>';
        $html .= '<div class="venteSectionBody">';
        $html .= '<span class="nbReturns">0 article</span>';

        $html .= '<div class="totalReturnsTitle">Total TTC&nbsp;:&nbsp;<span class="totalReturns">0,00&nbsp;&euro;</span></div>';
        $html .= '<div id="returnsLines">';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';


        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public function renderClientContent()
    {
        $html = '';
        $client = $this->getChildObject('client');
        $contact = $this->getChildObject('client_contact');

        $html .= '<div id="venteClientFormContainer"';
        if ($client->isLoaded()) {
            $html .= ' style="display: none;"';
        }
        $html .= '>';
        $form = new BC_Form($this, null, 'client', 1, true);
        $html .= $form->renderHtml();

        $html .= '<div style="text-align: right">';
        $html .= '<button id="cancelChangeClientButton" type="button" class="btn btn-danger"';
        $html .= ' onclick="$(\'#venteClientFormContainer\').slideUp(250);" style="display: none; float: left">';
        $html .= '<i class="fa fa-times iconLeft"></i>Annuler';
        $html .= '</button>';
//        $html .= '<button id="saveClientButton" type="button" class="btn btn-primary"';
//        $html .= ' onclick="saveClient();">';
//        $html .= '<i class="fa fa-save iconLeft"></i>Enregistrer';
//        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div style="text-align: center; margin-top: 15px">';
        $html .= '<button id="newClientButton" type="button" class="btn btn-default btn-large"';
        $html .= ' onclick="loadNewClientForm($(this))"';
        $html .= '>';
        $html .= '<i class="fa fa-user-plus iconLeft"></i>Nouveau Client';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div id="saveClientResult" style="display: none"></div>';

        $html .= '</div>';

        $html .= '<div id="venteClientViewContainer"';
        if (!$client->isLoaded()) {
            $html .= ' style="display: none"';
        }
        $html .= '>';
        if ($client->isLoaded()) {
            $html .= $this->renderClientView();
        }
        $html .= '</div>';
        return $html;
    }

    public function renderClientView()
    {
        $html = '';

        $client = $this->getChildObject('client');
        if ($client->isLoaded()) {
            $html .= '<h3>Client: </h3>';
            $html .= '<div class="media object_card">';
            $html .= '<div class="media-body">';
            $html .= '<h4 class="media-heading"><span>' . $client->getData('code_client') . ' -</span> ' . $client->getData('nom') . '</h4>';
            $html .= '<div class="client_infos">';
            $address = $client->getData('address');
            $zip = $client->getData('zip');
            $town = $client->getData('town');
            $country = $client->getData('fk_country');
            $phone = $client->getData('phone');
            $email = $client->getData('email');

            if (!is_null($address) && $address && !is_null($zip) && $zip && !is_null($town) && $town) {
                $html .= '<div style="display: inline-block; width: 48%; padding-right: 20px;">';
                $html .= '<i class="fa fa-map-marker"></i>';
                $html .= $address . '<br/>';
                $html .= $zip . ' ' . $town . '<br/>';
                if (!is_null($country) && $country !== 1) {
                    $html .= $client->displayCountry() . '<br/>';
                }
                $html .= '</div>';
            }
            $html .= '<div style="display: inline-block; width: 48%">';
            if (!is_null($phone) && $phone) {
                $html .= '<i class="fa fa-phone"></i>';
                $html .= $phone . '<br/>';
            }
            if (!is_null($email) && $email) {
                $html .= '<i class="fa fa-at"></i>';
                $html .= $email . '<br/>';
            }
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div style="text-align: right; margin-top: 10px">';
            $html .= '<button id="changeClientButton" type="button" class="btn btn-default" style="float: left"';
            $html .= ' onclick="$(\'#venteClientFormContainer\').slideDown(250); $(\'#cancelChangeClientButton\').show();"';
            $html .= '>';
            $html .= '<i class="fa fa-exchange iconLeft"></i>Changer le client';
            $html .= '</button>';

            $html .= '<div class="btn-group">';

            $html .= '<button type="button" class="btn btn-default"';
            $html .= ' onclick="editClient($(this), ' . $client->id . ', \'' . htmlentities(addslashes($client->getData('nom'))) . '\');"';
            $html .= '><i class="fa fa-edit"></i></button>';
            $html .= '<button type="button" class="btn btn-default"';
            $title = 'Client "' . addslashes($client->getData('nom')) . '"';
            $html .= ' onclick="loadModalView(\'bimpcore\', \'Bimp_Client\', ' . $client->id . ', \'default\', $(this), \'' . htmlentities($title) . '\');"';
            $html .= '><i class="fa fa-eye"></i></button>';

            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';
            $html .= '</div>';

            $html .= '<h3 style="margin-top: 15px">Contact: </h3>';

            $html .= '<div id="contactViewContainer">';
            $html .= $this->renderContactView();
            $html .= '</div>';

            $html .= '<div style="text-align: center; margin-top: 10px">';
            $html .= '<button id="newContactButton" type="button" class="btn btn-default"';
            $html .= ' onclick="loadNewContactForm($(this), ' . $client->id . ')"';
            $html .= '>';
            $html .= '<i class="fa fa-user-plus iconLeft"></i>Nouveau contact';
            $html .= '</button>';
            $html .= '</div>';
        }
        return $html;
    }

    public function renderContactView()
    {
        $html = '';

        $contacts = $this->getClient_contactsArray();
        $contact = $this->getChildObject('client_contact');

        $id_contact = 0;
        if (!is_null($contact)) {
            if (!$contact->isLoaded()) {
                unset($contact);
                $contact = null;
            } else {
                $id_contact = $contact->id;
            }
        }
        if (count($contacts) > 1) {
            $html .= '<div' . (!is_null($contact) ? ' style="padding-right: 90px"' : '') . '>';
            $html .= '<select id="contactSelect" onchange="changeContact($(this));">';
            foreach ($contacts as $id_contact => $label) {
                $html .= '<option value="' . $id_contact . '"';
                if (!is_null($contact) && (int) $id_contact === (int) $contact->id) {
                    $html .= ' selected';
                }
                $html .= '>' . $label . '</option>';
            }
            $html .= '</select>';

            if (!is_null($contact)) {
                $html .= '<div class="contactActions">';

                $html .= '<button type="button" class="btn btn-default"';
                $html .= ' onclick="editContact($(this), ' . $contact->id . ', \'' . htmlentities(addslashes($contact->getName())) . '\');"';
                $html .= '><i class="fa fa-edit"></i></button>';

                $html .= '<button type="button" class="btn btn-default"';
                $title = 'Contact "' . addslashes($contact->getName()) . '"';
                $html .= ' onclick="loadModalView(\'bimpcore\', \'Bimp_Contact\', ' . $contact->id . ', \'default\', $(this), \'' . htmlentities($title) . '\');"';
                $html .= '><i class="fa fa-eye"></i></button>';

                $html .= '</div>';
            }
            $html .= '</div>';
        }

        if (!is_null($contact)) {
            $address = $contact->getData('address');
            $zip = $contact->getData('zip');
            $town = $contact->getData('town');
            $country = $contact->getData('fk_country');
            $phone = $contact->getData('phone');
            $phone_perso = $contact->getData('phone_perso');
            $phone_mobile = $contact->getData('phone_mobile');
            $email = $contact->getData('email');

            $html .= '<div class="media object_card">';
            $html .= '<div class="media-body">';
            $html .= '<div class="client_infos">';

            if (!is_null($address) && $address && !is_null($zip) && $zip && !is_null($town) && $town) {
                $html .= '<div style="display: inline-block; width: 48%; padding-right: 20px;">';
                $html .= '<i class="fa fa-map-marker"></i>';
                $html .= $address . '<br/>';
                $html .= $zip . ' ' . $town . '<br/>';
                if (!is_null($country) && $country !== 1) {
                    $html .= $contact->displayCountry() . '<br/>';
                }
                $html .= '</div>';
            }
            $html .= '<div style="display: inline-block; width: 48%">';
            if (!is_null($phone) && $phone) {
                $html .= '<i class="fa fa-phone"></i><strong>pro.</strong> ';
                $html .= $phone . '<br/>';
            }
            if (!is_null($phone_perso) && $phone_perso) {
                $html .= '<i class="fa fa-phone"></i><strong>perso.</strong> ';
                $html .= $phone_perso . '<br/>';
            }
            if (!is_null($phone_mobile) && $phone_mobile) {
                $html .= '<i class="fa fa-mobile"></i>';
                $html .= $phone_mobile . '<br/>';
            }
            if (!is_null($email) && $email) {
                $html .= '<i class="fa fa-at"></i>';
                $html .= $email . '<br/>';
            }
            $html .= '</div>';

            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }



        return $html;
    }

    public function renderAddProductContent()
    {
        $html = '';

        $html .= '<div id="venteAddProductFormContainer" class="formRow" style="display: block">';

        $html .= '<p style="text-align: center; color: #787878">';
        $html .= 'Référence, Code-barre ou Numéro de série';
        $html .= '</p>';

        $html .= '<div class="formRowInput" style="display: block">';
        $html .= '<input type="text" id="venteSearchProduct" name="venteSearchProduct" value=""/>';
        $html .= '</div>';

        $html .= '<div style="text-align: right; margin: 15px 0">';
        $html .= '<button id="findProductButton" type="button" class="btn btn-primary"';
        $html .= ' onclick="findProduct($(this))"';
        $html .= '>';
        $html .= '<i class="fa fa-search iconLeft"></i>Rechercher';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div id="findProductResult">';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public function renderCartContent()
    {
        $html = '';

        $total = 0;
        $nbArticles = 0;

        $html .= '<div style="text-align: right">';
        $html .= '<button type="button" id="loadRemiseFormButton" class="btn btn-default"';
        $html .= ' onclick="loadRemiseForm($(this));"';
        $html .= '>';
        $html .= '<i class="fa fa-percent iconLeft"></i>Ajouter une remise';
        $html .= '</button>';
        $html .= '<button type="button" id="loadReturnFormButton" class="btn btn-default"';
        $html .= ' onclick="loadReturnForm($(this));"';
        $html .= '>';
        $html .= '<i class="fa fa-reply iconLeft"></i>Retour Produit';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div id="venteNbArticles">';
        $html .= $nbArticles . ' article' . ($nbArticles > 1 ? 's' : '');
        $html .= '</div>';

        $html .= '<div id="venteRemises">';
        $html .= '<div class="title">Remises:</div>';
        $html .= '<div class="remises_lines"></div>';
//        $html .= '<div class="total_remises_vente">Total remises globales: <span></span></div>';
        $html .= '<div class="total_remises_articles">Total remises articles: <span></span></div>';
        $html .= '<div class="total_remises">Total remises: <span></span></div>';
        $html .= '</div>';

        $html .= '<div id="ventePanierTotal">';
        $html .= '<span class="cart_total_label">';
        if ((int) $this->getData('vente_ht')) {
            $html .= 'Total HT';
        } else {
            $html .= 'Total TTC';
        }

        $html .= '</span>: <span class="cart_total">' . BimpTools::displayMoneyValue($total, 'EUR') . '</span>';
        $html .= '</div>';

        $articles = $this->getChildrenObjects('articles');

        $html .= '<div id="ventePanierLines">';
        if (count($articles)) {
            foreach ($articles as $article) {
                $html .= $this->renderCartArticleLine($article);
            }
        }
        $html .= '</div>';

        return $html;
    }

    public function renderPaiementContent()
    {
        $html = '';

        $toPay = 0;

        // Reste à payer: 
        $html .= '<div id="venteToPay">';
        $html .= 'Reste à payer: <span>' . BimpTools::displayMoneyValue($toPay, 'EUR') . '</span>';
        $html .= '</div>';

        // A rendre:
        $html .= '<div id="venteToReturn" style="display: none">A rendre: <span>0,00 &euro;</span></div>';

        // Avoir client: 
        $html .= '<div id="venteAvoir" style="display: none">Avoir client: <span>0,00 &euro;</span></div>';

        // Boutons paiement:
        $html .= '<div id="ventePaiementButtons" class="row">';

        $html .= '<div class="col-lg-4">';
        $html .= '<button id="ventePaiementLiquideButton"type="button" class="ventePaiementButton btn btn-default btn-large"';
        $html .= ' onclick="displayNewPaiementForm($(this));" data-code="LIQ">';
        $html .= '<i class="fa fa-money iconLeft"></i>Paiement Liquide';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="col-lg-4">';
        $html .= '<button id="ventePaiementCBButton" type="button" class="ventePaiementButton btn btn-default btn-large"';
        $html .= ' onclick="displayNewPaiementForm($(this));" data-code="CB">';
        $html .= '<i class="fa fa-credit-card iconLeft"></i>Paiement Carte Bancaire';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="col-lg-4">';
        $html .= '<button id="ventePaiementCBButton" type="button" class="ventePaiementButton btn btn-default btn-large"';
        $html .= ' onclick="displayNewPaiementForm($(this));" data-code="CHQ">';
        $html .= '<i class="fa fa-pencil iconLeft"></i>Paiement Chèque';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="col-lg-4">';
        $html .= '<button id="ventePaiementCBButton" type="button" class="ventePaiementButton btn btn-default btn-large"';
        $html .= ' onclick="displayNewPaiementForm($(this));" data-code="AE">';
        $html .= '<i class="fa fa-cc-amex iconLeft"></i>Paiement American Express';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="col-lg-4">';
        $html .= '<button id="ventePaiementCBButton" type="button" class="ventePaiementButton btn btn-default btn-large"';
        $html .= ' onclick="displayNewPaiementForm($(this));" data-code="CG">';
        $html .= '<i class="fa fas fa-envelope iconLeft"></i>Chéque Gallerie';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="col-lg-4">';
        $html .= '<button id="ventePaiementCBButton" type="button" class="ventePaiementButton btn btn-default btn-large"';
        $html .= ' onclick="displayNewPaiementForm($(this));" data-code="no">';
        $html .= '<i class="fa fa-times-circle iconLeft"></i>Financement';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '</div>';

        // Formulaire d'ajout de paiement: 
        $html .= '<div id="venteAddPaiementFormContainer">';
        $html .= '<input type="hidden" id="ventePaiementCode" name="ventePaiementCode" value=""/>';

        $html .= '<div style="display: inline-block">';
        $html .= '<span>Montant: </span>';
        $html .= '<input type="text" id="ventePaiementMontant" name="ventePaiementMontant" value="' . $toPay . '"';
        $html .= ' data-data_type="number"';
        $html .= ' data-decimals="2"';
        $html .= ' data-min="0"';
        $html .= ' data-unsigned="1"';
        $html .= '/>';
        $html .= '<span class="inputAddon"><i class="fa fa-euro"></i></span>';
        $html .= '<button type="button" id="venteAddPaiementButton" class="btn btn-primary"';
        $html .= ' onclick="addVentePaiement($(this))"';
        $html .= '>';
        $html .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter';
        $html .= '</button>';
        $html .= '<button type="button" id="venteAddPaiementCancelButton" class="btn btn-danger"';
        $html .= ' onclick="$(\'#venteAddPaiementFormContainer\').slideUp(250);$(\'.ventePaiementButton.selected\').attr(\'class\', \'ventePaiementButton btn btn-large btn-default\');"';
        $html .= '>';
        $html .= '<i class="fa fa-times iconLeft"></i>Annuler';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        // Paiements enregistrés: 
        $html .= '<div id="ventePaimentsLines">';
        $html .= $this->renderPaiementsLines();
        $html .= '</div>';

        // Conditions de réglement: 
        $id_cond = (int) $this->getData('id_cond_reglement');
        $html .= '<div id="condReglement" style="font-size: 14px">';
        $html .= '<span style="font-weight: bold">Condition de réglement : </span>';
        $html .= '<select id="condReglementSelect" name="condReglementSelect"  disabled>';
        foreach ($this->getCond_reglementsArray() as $id => $label) {
            $html .= '<option value="' . $id . '"' . ((int) $id === $id_cond ? ' selected=""' : '') . '>' . $label . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        // Vente au prix HT: 
        $vente_ht = (int) $this->getData('vente_ht');
        $html .= '<div id="venteHt" style="font-size: 14px">';
        $html .= '<span style="font-weight: bold">Vendre au prix hors-taxes : </span>';
        $html .= BimpInput::renderInput('toggle', 'vente_ht', $vente_ht);
        $html .= '</div>';

        // Remboursement avoir Client:
        $html .= '<div id="avoirRbtForm">';

        $html .= '<p class="alert alert-warning" style="margin: 30px 0">';
        $html .= 'Cette opération donne lieu à un avoir client. Aucun paiement de la part du client ne sera pris en compte.';
        $html .= '</p>';

        $html .= '<div id="avoirRbtMode">';
        $html .= '<span style="font-weight: bold; font-size: 14px">Avoir client : </span>';
        $html .= BimpInput::renderInput('select', 'avoir_rbt_mode', 'remise', array(
                    'options' => array(
                        'remise' => 'Convertir en remise future',
                        'rbt'    => 'Rembourser'
                    )
        ));
        $html .= '</div>';

        $html .= '<div id="avoirRbtModePaiement">';
        $html .= '<span style="font-weight: bold; font-size: 14px">Mode de remboursement de l\'avoir client : </span>';
        $html .= BimpInput::renderInput('select', 'avoir_rbt_paiement', 'LIQ', array(
                    'options' => array(
                        'LIQ' => 'Espèce',
                        'CHQ' => 'Chèque',
                        'CB'  => 'Carte bancaire',
                        'VIR' => 'Virement bancaire'
                    )
        ));
        $html .= '</div>';
        $html .= '</div>';

        // Utilisation remises client: 
        $id_client = (int) $this->getData('id_client');
        $html .= '<div id="customerDiscountsContainer"' . (!$id_client ? 'style="display: none"' : '') . '>';
        $html .= '<div class="title"><i class="fas fa5-money-check-alt iconLeft"></i>Remises client disponibles à utiliser :</div>';
        $html .= '<div id="customerDiscounts">';
        if ($id_client) {
            $html .= $this->renderDiscountsAssociation();
        }
        $html .= '<div id="totalDiscounts">Total remises client utilisées: <span></span></div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderPaiementsLines()
    {
        $html = '';

        $paiements = $this->getChildrenObjects('paiements');
        $total = 0;

        foreach ($paiements as $paiement) {
            $html .= '<div class="paiement_line">';
            $code = $paiement->getData('code');
            $html .= '<div class="paiement_label">';
            if (!array_key_exists($code, BC_VentePaiement::$codes)) {
                $html .= BimpRender::renderAlerts('Type de paiement invalide');
            } else {
                $html .= '<i class="fa fa-' . BC_VentePaiement::$codes[$code]['icon'] . '"></i>';
                $html .= BC_VentePaiement::$codes[$code]['label'];
            }
            $html .= '</div>';

            $montant = (float) $paiement->getData('montant');
            $total += $montant;

            $html .= '<div class="paiement_montant">';
            $html .= BimpTools::displayMoneyValue($montant, 'EUR');
            $html .= '</div>';

            $html .= '<span class="deletePaiementButton" onclick="deletePaiement($(this), ' . $paiement->id . ')">';
            $html .= '<i class="fa fa-trash"></i>';
            $html .= '</span>';

            $html .= '</div>';
        }

        if (count($paiements) > 1) {
            $html .= '<div class="paiements_total">';
            $html .= '<strong>Total paiements: </strong>' . BimpTools::displayMoneyValue($total, 'EUR');
            $html .= '</div>';
        }

        return $html;
    }

    public function renderDiscountsAssociation()
    {
        $html = '';

        if ((int) $this->getData('id_client')) {
            $asso = new BimpAssociation($this, 'discounts');
            $html .= $asso->renderAddAssociateInput('default', true);
        }

        return $html;
    }

    public function renderSelectEquipmentLine($id_equipment, $current_equipments = array())
    {
        $html = '';

        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
        if (!$equipment->isLoaded()) {
            $html .= BimpRender::renderAlerts('Erreur: aucun enregistrement trouvé pour l\'équipement d\'ID ' . $id_equipment, 'danger');
        } else {
            $id_product = (int) $equipment->getData('id_product');

            if (!$id_product) {
                $html .= BimpRender::renderAlerts('Erreur: aucun produit associé à l\'équipement ' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '")', 'danger');
            } else {
                $product = $equipment->getChildObject('product');
                if (!BimpObject::objectLoaded($product)) {
                    $html .= BimpRender::renderAlerts('Erreur: produit d\'ID ' . $equipment->getData('id_product') . ' non trouvé pour l\'équipement d\'ID' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '")', 'danger');
                } else {
                    $price_ttc = 0;
                    if ((float) $equipment->getData('prix_vente_except') > 0) {
                        $price_ttc = (float) $equipment->getData('prix_vente_except');
                        $price_ht = (float) BimpTools::calculatePriceTaxEx($price_ttc, $product->tva_tx);
                    } else {
                        $price_ttc = (float) $product->price_ttc;
                        $price_ht = (float) $product->price;
                    }
                    $html .= '<div class="selectArticleLine">';
                    $html .= '<div class="equipment_title"><strong>Equipement ' . $id_equipment . '</strong> - n° de série: <strong>' . $equipment->getData('serial') . '</strong></div>';
                    $html .= '<div class="product_title"><strong>Produit:</strong> "' . $product->label . '"</div>';
                    $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
                    $html .= '<div class="product_price">';
                    if ((int) $this->getData('vente_ht')) {
                        BimpTools::displayMoneyValue($price_ht, 'EUR');
                    } else {
                        BimpTools::displayMoneyValue($price_ttc, 'EUR');
                    }

                    $html .= '</div>';

                    if (array_key_exists($id_equipment, $current_equipments)) {
                        $html .= BimpRender::renderAlerts('cet équipement a déjà été ajouté au panier', 'warning');
                    } else {
                        $eq_errors = array();
                        if (!$equipment->isAvailable(0, $eq_errors)) {
                            $html .= BimpRender::renderAlerts($eq_errors, 'warning');
                        } else {
                            $html .= '<div style="margin-top: 10px; text-align: right">';
                            $html .= '<button type="button" class="btn btn-primary"';
                            $html .= ' onclick="selectArticle($(this), ' . $id_equipment . ', \'Equipment\')"';
                            $html .= '>';
                            $html .= 'Sélectionner<i class="fa fa-chevron-right iconRight"></i>';
                            $html .= '</button>';
                            $html .= '</div>';
                        }
                    }

                    $html .= '</div>';
                }
            }
        }
        return $html;
    }

    public function renderSelectProductLine($id_product)
    {
        $html = '';

        if (!class_exists('Product')) {
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        }

        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);

        if (!BimpObject::objectLoaded($product)) {
            $html .= BimpRender::renderAlerts('Erreur: aucun enregistrement trouvé pour le produit d\'id ' . $id_product, 'danger');
        } else {
            $html .= '<div class="selectArticleLine">';
            $html .= '<div class="product_title"><strong>Produit:</strong> "' . $product->getName() . '"</div>';
            $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->getRef() . '</div>';
            $html .= '<div class="product_info"><strong>Code-barres: </strong>' . $product->getData('barcode') . '</div>';
            $html .= '<div class="product_price">';

            if ((int) $this->getData('vente_ht')) {
                $html .= BimpTools::displayMoneyValue($product->dol_object->price, 'EUR');
            } else {
                $html .= BimpTools::displayMoneyValue($product->dol_object->price_ttc, 'EUR');
            }

            $html .= '</div>';
            $html .= '<div style="margin-top: 10px; text-align: right">';
            $html .= '<button type="button" class="btn btn-primary"';
            $html .= ' onclick="selectArticle($(this), ' . $id_product . ', \'Product\')"';
            $html .= '>';
            $html .= 'Sélectionner<i class="fa fa-chevron-right iconRight"></i>';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderCartArticleLine(BC_VenteArticle $article)
    {
        $errors = array();
        $html = '';
        if ($article->isLoaded()) {
            $id_equipment = (int) $article->getData('id_equipment');
            $id_product = (int) $article->getData('id_product');
            $product = $article->getChildObject('product');

            if (!$id_product) {
                $errors[] = 'Aucun produit enregistré pour cet article';
            } elseif (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Le produit associé à cet article n\'existe plus (ID ' . $id_product . ')';
            }

            if (!count($errors)) {
                if ($id_equipment) {
                    $equipment = $this->checkEquipment($id_equipment, $errors);
                    if (!is_null($equipment)) {
                        $html .= $this->renderCartEquipmentline($article, $product, $equipment);
                    } else {
                        $delete_errors = $article->delete();
                        if (!count(($delete_errors))) {
                            $errors[] = 'Cet article a été supprimé de la vente';
                        } else {
                            $errors[] = 'Attention, cet article n\'a pas pu être supprimé de la vente.';
                        }
                    }
                } else {
                    $html .= $this->renderCartProductLine($article, $product);
                }
            }
        }

        if (count($errors)) {
            return '<br/><br/>' . BimpRender::renderAlerts($errors);
        }
        return $html;
    }

    public function renderCartEquipmentline(BC_VenteArticle $article, Bimp_Product $product, Equipment $equipment)
    {
        $html = '';
        $html .= '<div id="cart_article_' . $article->id . '" class="cartArticleLine" data-id_article="' . $article->id . '">';
        $html .= '<div class="product_title">' . $product->getName();
        $html .= '<span class="removeArticle" onclick="removeArticle($(this), ' . $article->id . ');">';
        $html .= '<i class="fa fa-trash"></i>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<div class="product_info"><strong>Equipement ' . $equipment->id . ' - n° de série: ' . $equipment->getData('serial') . '</strong></div>';
        $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->getRef() . '</div>';

        // Options article: 
        $html .= '<div class="article_options">';
        $html .= '<div class="article_qty">&nbsp;</div>';

        // Champ remise CRT: 
        $remise_crt = (float) $product->getRemiseCrt();
        if ($remise_crt) {
            $html .= '<div class="article_remise_crt" style="margin: 10px 0;">';
            $html .= '<span style="display: inline-block; font-weight: bold; margin-top: -11px; vertical-align: middle; margin-right: 10px;">Remise CRT: </span>';
            $html .= BimpInput::renderInput('toggle', 'article_remise_crt', (int) $article->getData('remise_crt'));
            $html .= '</div>';
        }

        // Remises: 
        $html .= '<div class="article_remises">';
        $html .= '<div class="title">Remises: </div>';
        $html .= '<div class="content"></div>';
        $html .= '</div>';

        $html .= '<div class="product_total_price">';
        $html .= '<span class="base_price"></span>';
        $html .= '<span class="final_price">';

        if ((int) $this->getData('vente_ht')) {
            BimpTools::displayMoneyValue($article->getData('unit_price_tax_ex'), 'EUR');
        } else {
            BimpTools::displayMoneyValue($article->getData('unit_price_tax_in'), 'EUR');
        }

        $html .= '</span>';
        $html .= '</div>';

        $html .= '</div>';

        if (!$article->checkPlace((int) $this->getData('id_entrepot'))) {
            $html .= '<div class="placeAlert">';
            $html .= BimpRender::renderAlerts('Attention, L\'équipement ' . $equipment->getNomUrl(0, 1, 1, 'default') . ' n\'est pas enregistré comme étant situé dans votre centre', 'warning');
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function renderCartProductLine(BC_VenteArticle $article, Bimp_Product $product)
    {
        $html = '';
        $qty = (int) $article->getData('qty');

        $html .= '<div id="cart_article_' . $article->id . '" class="cartArticleLine" data-id_article="' . $article->id . '">';
        $html .= '<div class="product_title">' . $product->getName();
        $html .= '<span class="removeArticle" onclick="removeArticle($(this), ' . $article->id . ');">';
        $html .= '<i class="fa fa-trash"></i>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->getRef() . '</div>';

        if ((int) $this->getData('vente_ht')) {
            $html .= '<div class="product_info"><strong>Prix unitaire HT: </strong>' . BimpTools::displayMoneyValue($product->dol_object->price, 'EUR') . '</div>';
        } else {
            $html .= '<div class="product_info"><strong>Prix unitaire TTC: </strong>' . BimpTools::displayMoneyValue($product->dol_object->price_ttc, 'EUR') . '</div>';
        }


        // Options article: 
        $html .= '<div class="article_options">';
        $html .= '<div class="article_qty">';
        $html .= '<strong>Qté: </strong>';
        $html .= '<span class="qty_down" onclick="changeArticleQty($(this), ' . $article->id . ', \'down\');">';
        $html .= '<i class="fa fa-minus-circle iconLeft"></i></span>';
        $html .= '<input type="text" value="' . $qty . '" class="article_qty_input" name="article_qty" id="article_' . $article->id . '_qty"';
        $html .= ' data-id_article="' . $article->id . '"';
        $html .= ' data-data_type="number"';
        $html .= ' data-decimals="0"';
        $html .= ' data-min="1"';
        $html .= ' data-unsigned="1"';
        $html .= '/>';
        $html .= '<span class="qty_up" onclick="changeArticleQty($(this), ' . $article->id . ', \'up\');">';
        $html .= '<i class="fa fa-plus-circle iconRight"></i></span>';
        $html .= '</div>';

        // Champ remise CRT: 
        $remise_crt = (float) $product->getRemiseCrt();
        if ($remise_crt) {
            $html .= '<div class="article_remise_crt" style="margin: 10px 0;">';
            $html .= '<span style="display: inline-block; font-weight: bold; margin-top: -11px; vertical-align: middle; margin-right: 10px;">Remise CRT: </span>';
            $html .= BimpInput::renderInput('toggle', 'article_remise_crt', (int) $article->getData('remise_crt'));
            $html .= '</div>';
        }

        // Remises: 
        $html .= '<div class="article_remises">';
        $html .= '<div class="title">Remises: </div>';
        $html .= '<div class="content"></div>';
        $html .= '</div>';

        $html .= '<div class="product_total_price">';
        $html .= '<span class="base_price"></span>';
        $html .= '<span class="final_price">';

        if ((int) $this->getData('vente_ht')) {
            $html .= BimpTools::displayMoneyValue($product->dol_object->price, 'EUR');
        } else {
            $html .= BimpTools::displayMoneyValue($product->dol_object->price_ttc, 'EUR');
        }

        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // Check du stock dispo: 
        if ($product->isTypeProduct()) {
            $stock_dispo = (int) $article->getProductStock((int) $this->getData('id_entrepot'));
            $html .= '<div class="stockAlert"' . (($stock_dispo >= $qty) ? ' style="display: none"' : '') . '>';
            $html .= BimpRender::renderAlerts('Attention, le stock de ce produit est dépassé.<br/><strong class="stock">Stock disponible: <span>' . $stock_dispo . '</span></strong>', 'warning');
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function renderTicketHtml(&$errors)
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID de la vente absent';
        } elseif (!(int) $this->getData('status') === 2) {
            $errors[] = 'Cette vente n\'est pas validée';
        } else {
            $facture = $this->getChildObject('facture');
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpTicket.php';

            global $db;

            $ticket = new BimpTicket($db, 370, $facture, (int) $this->getData('id_entrepot'), $this->id);
            $html = $ticket->renderHtml();

            if (count($ticket->errors)) {
                $errors = $ticket->errors;
                return '';
            }

            return $html;
        }

        return '';
    }

    // Traitements : 

    public function checkEquipment($id_equipment, &$errors)
    {
        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);

        if (!$equipment->isLoaded()) {
            $errors[] = 'Erreur: aucun enregistrement trouvé pour l\'équipement d\'ID ' . $id_equipment;
            return null;
        } else {
            $id_product = (int) $equipment->getData('id_product');

            if (!$id_product) {
                $errors[] = 'Erreur: aucun produit associé à l\'équipement ' . $equipment->getNomUrl(0, 1, 1, 'default');
                return null;
            } else {
                $product = $equipment->getChildObject('product');
                if (!BimpObject::objectLoaded($product)) {
                    $errors[] = 'Le produit associé à l\'équipement ' . $equipment->getNomUrl(0, 1, 1, 'default') . ' n\'existe plus (ID ' . $id_product . ')';
                    return null;
                }

                if (!$equipment->isAvailable(0, $errors, array(// On ne vérifie pas l'emplacement de l'équipement pour éviter le blocage de la vente. 
                            'id_vente'   => (int) $this->id,
                            'id_facture' => (int) $this->getData('id_facture')
                        ))) {
                    return null;
                }
            }
        }

        return $equipment;
    }

    public function addCartEquipement($id_equipment, &$errors)
    {
        $html = '';

        $equipment = $this->checkEquipment($id_equipment, $errors);
        if (BimpObject::objectLoaded($equipment)) {
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $equipment->getData('id_product'));
            $article = BimpObject::getInstance($this->module, 'BC_VenteArticle');
            $prix_ht = 0;
            $prix_ttc = 0;

            $prix_except = (float) $equipment->getData('prix_vente_except');
            if ($prix_except > 0) {
                $prix_ttc = $prix_except;
            } else {
                $prix_ttc = $product->dol_object->price_ttc;
            }

            $prix_ht = BimpTools::calculatePriceTaxEx($prix_ttc, (float) $product->dol_object->tva_tx);

            $article_errors = $article->validateArray(array(
                'id_vente'          => $this->id,
                'id_product'        => $product->id,
                'id_equipment'      => (int) $id_equipment,
                'qty'               => 1,
                'unit_price_tax_ex' => (float) $prix_ht,
                'unit_price_tax_in' => (float) round($prix_ttc, 2),
                'tva_tx'            => (float) $product->dol_object->tva_tx
            ));

            if (!count($article_errors)) {
                $article_errors = $article->create();

                if (!count($article_errors)) {
                    if (!$article->checkPlace((int) $this->getData('id_entrepot'))) {
                        $subject = 'Erreur emplacement équipement';
                        $msg = 'Un équipement a été ajouté à une vente en caisse dont l\'entrepôt ne correspond pas à l\'emplacement actuellement enregistré pour cet équipement';
                        $msg .= "\n\n";
                        $msg .= "\t" . 'Vente n°' . $this->id . "\n";
                        $msg .= "\t" . 'Equipement: ' . $equipment->getData('serial') . ' (ID: ' . $equipment->id . ')' . "\n";

                        $entrepot = BimpCache::getDolObjectInstance((int) $this->getData('id_entrepot'), 'product/stock', 'entrepot');
                        if (BimpObject::ObjectLoaded($entrepot)) {
                            $msg .= "\t" . 'Entrepôt de la vente: ' . $entrepot->libelle . "\n";
                        }

                        $place = $equipment->getCurrentPlace();
                        if (BimpObject::ObjectLoaded($place)) {
                            $msg .= "\t" . 'Emplacement de l\'équipement: ' . $place->getPlaceName();
                        }
                        mailSyn2($subject, 'logistique@bimp.fr', '', $msg);
                    }

                    $html .= $this->renderCartEquipmentline($article, $product, $equipment);
                }
            }

            if (count($article_errors)) {
                $errors[] = BimpTools::getMsgFromArray($article_errors, 'Echec de la création de l\'article');
            }
        }

        return $html;
    }

    public function addCartProduct($id_product, &$errors, $qty = 1)
    {
        $html = '';

        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);

        if (!BimpObject::objectLoaded($product)) {
            $errors[] = 'Le produit d\'ID ' . $id_product . ' n\'existe pas';
        } else {

            $article = BimpObject::getInstance($this->module, 'BC_VenteArticle');
            $article_errors = $article->validateArray(array(
                'id_vente'          => $this->id,
                'id_product'        => $id_product,
                'id_equipment'      => 0,
                'qty'               => $qty,
                'unit_price_tax_ex' => (float) BimpTools::calculatePriceTaxEx((float) $product->dol_object->price_ttc, (float) $product->dol_object->tva_tx),
                'unit_price_tax_in' => (float) round($product->dol_object->price_ttc, 2),
                'tva_tx'            => (float) $product->dol_object->tva_tx
            ));

            if (!count($article_errors)) {
                $article_errors = $article->create();

                if (!count($article_errors)) {
                    $html .= $this->renderCartProductLine($article, $product);
                }
            }
            if (count($article_errors)) {
                $errors[] = BimpTools::getMsgFromArray($article_errors, 'Echec de la création de l\'article');
            }
        }

        return $html;
    }

    public function findArticleToAdd($search, &$errors = array())
    {
        $cart_html = '';
        $result_html = '';

        $equipements = array();

        // Recherche d'équipement via n° de série: 
        $rows = $this->db->getValues('be_equipment', 'id', 'serial = "' . $search . '" || concat("S", serial) = "' . $search . '"');
        if (!is_null($rows)) {
            foreach ($rows as $id_eq) {
                if (!in_array((int) $id_eq, $equipements)) {
                    $equipements[] = (int) $id_eq;
                }
            }
        }

        $products = array();
        $products_warnings = array();

        // Recherche Produits: 
        $sql = 'SELECT p.rowid as id, p.label, p.ref, pe.serialisable FROM ' . MAIN_DB_PREFIX . 'product p ';
        $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'product_extrafields pe ';
        $sql .= ' ON p.rowid = pe.fk_object';
        $sql .= ' WHERE (p.barcode = "' . $search . '" OR p.ref LIKE "%' . $search . '%") AND tosell = 1';

//        echo $sql;
//        exit;

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows) && count($rows)) {
            $current_products = $this->getCurrentProducts();

            foreach ($rows as $r) {
                if (!in_array((int) $r['id'], $products)) {
                    if ((int) $r['serialisable']) {
                        $products_warnings[] = 'Vous devez obligatoirement saisir le numéro de série pour enregistrer un produit "' . $r['ref'] . ' - ' . $r['label'] . '"';
                        continue;
                    }
                    $products[] = (int) $r['id'];
                }
            }
        }

        // Affichage des résultats: 

        if (!count($equipements) && !count($products)) {
            $result_html .= BimpRender::renderAlerts('Aucun produit ni équipement trouvé pour la recherche "' . $search . '"');
        } else {
            if (count($equipements)) {
                $current_equipments = $this->getCurrentEquipments();

                if (count($equipements === 1) && !count($products) && !count($products_warnings)) {
                    // un seul équipement trouvé, ajout direct au panier:
                    if (array_key_exists($equipements[0], $current_equipments)) {
                        $result_html .= BimpRender::renderAlerts('L\'équipement #' . $equipements[0] . ' "' . $search . '" a déjà été ajouté au panier', 'warning');
                    } else {
                        $cart_html = $this->addCartEquipement($equipements[0], $errors);
                    }
                } else {
                    // Liste des équipements à sélectionner: 
                    $result_html .= '<h3>Equipements: </h3>';
                    $msg = count($equipements) . ' équipements trouvés pour le numéro de série "' . $search . '"';
                    $result_html .= BimpRender::renderAlerts($msg, 'info');

                    foreach ($equipements as $id_equipment) {
                        if (array_key_exists($equipements[0], $current_equipments)) {
                            $result_html .= BimpRender::renderAlerts('L\'équipement #' . $id_equipment . ' a déjà été ajouté au panier', 'warning');
                        } else {
                            $result_html .= $this->renderSelectEquipmentLine($id_equipment, $current_equipments);
                        }
                    }
                }
            }

            if (count($products) || count($products_warnings)) {
                if (count($products) === 1 && !count($equipements) && !count($products_warnings)) {
                    // Un seul produit trouvé, ajout direct au panier: 
                    $check = false;
                    if (array_key_exists((int) $products[0], $current_products)) {
                        $article = BimpCache::getBimpObjectInstance($this->module, 'BC_VenteArticle', (int) $current_products[(int) $products[0]]);
                        if ($article->isLoaded()) {
                            $check = true;
                            $qty = (int) $article->getData('qty');
                            $article->set('qty', ($qty + 1));
                            $up_errors = $article->update();
                            if (count($up_errors)) {
                                $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $products[0]);
                                if (BimpObject::objectLoaded($product)) {
                                    $product_label = '"' . $product->getRef() . '"';
                                } else {
                                    $product_label = '#' . $products[0];
                                }
                                $result_html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour des quantités pour le produit ' . $product_label), 'danger');
                            }
                        }
                    }

                    if (!$check) {
                        $cart_html = $this->addCartProduct((int) $products[0], $errors);
                    }
                } else {
                    $result_html .= '<h3>Produit non sérialisés: </h3>';
                    if (count($products_warnings)) {
                        foreach ($products_warnings as $msg) {
                            $result_html .= BimpRender::renderAlerts($msg, 'warning');
                        }
                    }
                    if (count($products) > 15) {
                        $msg = 'Un trop grand nombre de produits ont été trouvés.<br/>Veuillez utiliser un terme de recherche plus précis.';
                        $result_html .= BimpRender::renderAlerts($msg, 'warning');
                    } elseif (count($products)) {
                        $msg = count($products) . ' produit(s) trouvé(s) pour la recheche "' . $search . '"';
                        $result_html .= BimpRender::renderAlerts($msg, 'info');

                        foreach ($products as $id_product) {
                            $result_html .= $this->renderSelectProductLine($id_product);
                        }
                    }
                }
            }
        }

        return array(
            'cart_html'   => $cart_html,
            'result_html' => $result_html
        );
    }

    public function selectArticle($id_object, $object_name, &$errors)
    {
        $html = '';

        switch (strtolower($object_name)) {
            case 'equipment':
                $current_equipments = $this->getCurrentEquipments();
                if (array_key_exists($id_object, $current_equipments)) {
                    $errors[] = 'Cet équipement a déjà été ajouté au panier';
                } else {
                    $html = $this->addCartEquipement($id_object, $errors);
                }
                break;

            case 'product':
                $current_products = $this->getCurrentProducts();
                if (array_key_exists((int) $id_object, $current_products)) {
                    $article = BimpCache::getBimpObjectInstance($this->module, 'BC_VenteArticle', (int) $current_products[$id_object]);
                    if ($article->isLoaded()) {
                        $qty = (int) $article->getData('qty');
                        $article->set('qty', ($qty + 1));
                        $errors = array_merge($errors, $article->update());
                    } else {
                        $errors[] = 'Un article a déjà été ajouté au panier pour ce code-barres mais n\'a pas pu être mis à jour';
                    }
                } else {
                    $html = $this->addCartProduct($id_object, $errors);
                }
                break;
        }

        return $html;
    }

    public function addPaiement($code, $montant, &$errors)
    {
        if ($this->isLoaded()) {
            $paiement = BimpObject::getInstance($this->module, 'BC_VentePaiement');
            if (!array_key_exists($code, BC_VentePaiement::$codes)) {
                $errors[] = 'Type de paiement invalide';
            } else {
                $errors = $paiement->validateArray(array(
                    'id_vente' => $this->id,
                    'code'     => $code,
                    'montant'  => $montant
                ));

                if (!count($errors)) {
                    $errors = $paiement->create();
                }
            }
        }

        return $this->renderPaiementsLines();
    }

    public function checkVente(&$errors)
    {
        $caisse = $this->getChildObject('caisse');
        $articles = $this->getChildrenObjects('articles');
        $returns = $this->getChildrenObjects('returns');
        $total_ttc = (float) $this->getData('total_ttc');

        // Check de la caisse: 

        if (is_null($caisse) || !$caisse->isLoaded()) {
            $errors[] = 'Caisse absente ou invalide';
        }

        // Check du client: 
        $id_client = (int) $this->getData('id_client');
        $client = $this->getChildObject('client');

        if ((int) $this->getData('vente_ht')) {
            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Vente au prix HT: compte client obligatoire (avec n° de TVA intracommunautaire renseigné)';
            } elseif (!(string) $client->getData('tva_intra')) {
                $errors[] = 'Vente au prix HT: le n° de TVA intracommunautaire du client doit être renseigné';
            }
        }

        // Check des articles: 

        $has_equipment = false;
        foreach ($articles as $article) {
            $product = $article->getChildObject('product');
            $id_equipment = (int) $article->getData('id_equipment');
            if (is_null($product) || !isset($product->id) || !$product->id) {
                $errors[] = 'Produit invalide pour l\'article ' . $article->id;
            } elseif ($product->isSerialisable()) {
                if (!$id_equipment) {
                    $errors[] = 'Un numéro de série est obligatoire pour le produit "' . $product->ref . ' - ' . $product->label . '"';
                }
                $has_equipment = true;
            }

            if ($id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);

                $equipment->isAvailable(0, $errors, array(
                    'id_vente' => (int) $this->id
                ));
            }
        }

        // Checks des retours: 
        $i = 1;
        $has_returns = false;
        foreach ($returns as $return) {
            $has_returns = true;
            $id_equipment = (int) $return->getData('id_equipment');
            if ($id_equipment) {
                $has_equipment = true;
                $equipment = $return->getChildObject('equipment');
                if (!BimpObject::objectLoaded($equipment)) {
                    $errors[] = 'Retour n°' . $i . ': l\'équipement d\'ID ' . $id_equipment . ' n\'existe pas';
                } else {
                    $equipment->isAvailable(0, $errors, array(
                        'id_vente_return' => (int) $this->id
                    ));

                    if ($id_client) {
                        $place_errors = $equipment->checkPlaceForReturn($id_client);
                        if (count($place_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($place_errors, 'Retourn n°' . $i);
                        }
                    }
                }
            } else {
                $id_product = (int) $return->getData('id_product');
                if (!$id_product) {
                    $errors[] = 'Produit absent pour le retour n°' . $i;
                } else {
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);
                    if (!BimpObject::objectLoaded($product)) {
                        $errors[] = 'Retour n°' . $i . ': le produit d\'ID ' . $id_product . ' n\'existe pas';
                    } elseif ($product->isSerialisable() && !$id_equipment) {
                        $errors[] = 'Retour n°' . $i . ': produit sérialisable. Sélection d\'un équipement obligatoire';
                    }
                }
            }
            $i++;
        }

        // Checks des avoirs client utilisés:
        $asso = new BimpAssociation($this, 'discounts');
        $discounts = $asso->getAssociatesList();

        if (count($discounts)) {
            if (!BimpObject::objectLoaded($client)) {
                $this->setAssociatesList('discounts', array());
                $this->updateAssociations();
            } else {
                foreach ($discounts as $id_discount) {
                    $soc_id = (int) $this->db->getValue('societe_remise_except', 'fk_soc', '`rowid` = ' . (int) $id_discount);
                    if (!$soc_id || ($soc_id !== (int) $client->id)) {
                        $asso->deleteAssociation($this->id, (int) $id_discount);
                    }
                }
            }
        }

        // Autres checks: 
        $data = $this->getAjaxData();

        if (($has_equipment || $has_returns || (int) $data['paiement_differe']) && !BimpObject::objectLoaded($client)) {
            $errors[] = 'Compte client obligatoire pour cette vente';
        }

        if (!$data['paiement_differe'] && (float) $data['toPay'] > 0) {
            $errors[] = 'Paiements insuffisants';
        }

        if ((float) $this->getData('total_ttc') < 0) {
            $rbt_mode = BimpTools::getValue('avoir_rbt_mode', '');
            if (!$rbt_mode) {
                $errors[] = 'Mode de remboursement de l\'avoir client absent';
            } elseif ($rbt_mode === 'rbt') {
                if (!BimpTools::isSubmit('avoir_rbt_paiement')) {
                    $errors[] = 'Mode de paiement du remboursement de l\'avoir client absent';
                }
            }
        }

        if (count($errors)) {
            return false;
        }

        return true;
    }

    public function validateVente(&$errors)
    {
        global $user;

        $errors = array();
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') === 2) {
                $errors[] = 'Cette vente a déjà été validée';
                return false;
            }

            // Vérification de la validité de la vente: 

            if (!$this->checkVente($errors)) {
                return false;
            }

            // Mise à jour du statut de la vente: 
            $this->set('status', 2);
            $update_errors = $this->update();

            if (count($update_errors)) {
                $errors[] = 'Echec de la mise à jour du statut de la vente. Vente non validée';
                $errors = array_merge($errors, $update_errors);
                return false;
            }

            $articles = $this->getChildrenObjects('articles');
            $returns = $this->getChildrenObjects('returns');

            $codemove = 'VENTE' . $this->id;
            $id_client = (int) $this->getData('id_client');
            $id_entrepot = (int) $this->getData('id_entrepot');

            
            
            
            // Création de la facture et de l'avoir éventuel:
            $facture_errors = array();
            $facture_warnings = array();

            $id_facture = (int) $this->createFacture($facture_errors, $facture_warnings, true);

            if (count($facture_errors)) {
                $errors[] = BimpTools::getMsgFromArray($facture_errors, 'Echec de la création de la facture');
            } elseif ($id_facture) {
                $up_errors = $this->updateField('id_facture', $id_facture);
                if (count($up_errors)) {
                    $msg = 'Facture créée avec succès mais échec de l\'enregistrement du numéro de facture (' . $id_facture . ')<br/>Une correction manuelle est nécessaire.';
                    $errors[] = BimpTools::getMsgFromArray($up_errors, $msg);
                }
            }

            if (count($facture_warnings)) {
                $errors[] = BimpTools::getMsgFromArray($facture_warnings);
            }
            
            
            
            
            
            
            
            
            if(count($errors) == 0){
                // Gestion des stocks et emplacements des articles vendus: 
                foreach ($articles as $article) {
                    $equipment = $article->getChildObject('equipment');
                    if (BimpObject::objectLoaded($equipment)) {
                        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                        if (!$article->checkPlace($id_entrepot)) {
                            // Correction de l'emplacement initial en cas d'erreur: 
                            $place_errors = $place->validateArray(array(
                                'id_equipment' => (int) $equipment->id,
                                'type'         => BE_Place::BE_PLACE_ENTREPOT,
                                'id_entrepot'  => (int) $id_entrepot,
                                'infos'        => 'Correction automatique suite à la vente de l\'équipement (vente #' . $this->id . ')',
                                'date'         => date('Y-m-d H:i:s'),
                            ));
                            if (!count($place_errors)) {
                                $place_errors = $place->create();
                            }

                            if (count($place_errors)) {
                                $msg = 'Echec de la correction de l\'emplacement pour le n° de série "' . $equipment->getData('serial') . '"';
                                $errors[] = BimpTools::getMsgFromArray($place_errors, $msg);
                                dol_syslog('[ERREUR STOCK] ' . $msg . ' Vente #' . $this->id . ' - Article #' . $article->id . ' - Erreurs: ' . BimpTools::getMsgFromArray($place_errors), LOG_ERR);
                            }
                        }

                        // Création du nouvel emplacement: 
                        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                        if ($id_client) {
                            $place_errors = $place->validateArray(array(
                                'id_equipment' => (int) $equipment->id,
                                'type'         => BE_Place::BE_PLACE_CLIENT,
                                'id_client'    => (int) $id_client,
                                'infos'        => 'Vente #' . $this->id,
                                'date'         => date('Y-m-d H:i:s'),
                                'code_mvt'     => $codemove . '_ART' . (int) $article->id
                            ));
                        } else {
                            $place_errors = $place->validateArray(array(
                                'id_equipment' => (int) $equipment->id,
                                'type'         => BE_Place::BE_PLACE_FREE,
                                'place_name'   => 'Equipement vendu (client non renseigné)',
                                'infos'        => 'Vente #' . $this->id,
                                'date'         => date('Y-m-d H:i:s'),
                                'code_mvt'     => $codemove . '_ART' . (int) $article->id
                            ));
                        }

                        if (!count($place_errors)) {
                            $place_errors = $place->create();
                        }

                        if (count($place_errors)) {
                            $msg = 'Echec de l\'enregistrement du nouvel emplacement pour le n° de série "' . $equipment->getData('serial') . '"';
                            $errors[] = BimpTools::getMsgFromArray($place_errors, $msg);
                            dol_syslog('[ERREUR STOCK] ' . $msg . ' Vente #' . $this->id . ' - Article #' . $article->id . ' - Erreurs: ' . BimpTools::getMsgFromArray($place_errors), LOG_ERR);
                        }

                        $equipment->updateField('return_available', 1, null, true);
                    } else {
                        $product = $article->getChildObject('product');
                        $result = $product->dol_object->correct_stock($user, $id_entrepot, (int) $article->getData('qty'), 1, 'Vente #' . $this->id, 0, $codemove . '_ART' . (int) $article->id,'facture',$id_facture);
                        if ($result < 0) {
                            $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getRef() . ' - ' . $product->getName() . '" (ID: ' . $product->id . ')';
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), $msg);
                            dol_syslog('[ERREUR STOCK] ' . $msg . ' - Vente #' . $this->id . ' - Article #' . $article->id . ' - Qté: ' . (int) $article->getData('qty'), LOG_ERR);
                        }
                    }
                }

                // Gestion des stocks et emplacement des produits retournés: 
                if (count($returns)) {
                    $id_defective_entrepot = BimpCore::getConf('defective_id_entrepot');
                    $i = 1;

                    foreach ($returns as $return) {
                        $equipment = $return->getChildObject('equipment');
                        if (BimpObject::objectLoaded($equipment)) {
                            $place = BimpObject::getInstance('bimpequipment', 'BE_Place');

                            // Création du nouvel emplacement: 
                            if ((int) $return->getData('defective')) {
                                $place_errors = $place->validateArray(array(
                                    'id_equipment' => (int) $equipment->id,
                                    'type'         => BE_Place::BE_PLACE_ENTREPOT,
                                    'id_entrepot'  => (int) $id_defective_entrepot,
                                    'infos'        => 'Retour produit défectueux (Vente #' . $this->id . ')',
                                    'date'         => date('Y-m-d H:i:s'),
                                    'code_mvt'     => $codemove . '_RET' . (int) $return->id
                                ));
                            } else {
                                $place_errors = $place->validateArray(array(
                                    'id_equipment' => (int) $equipment->id,
                                    'type'         => BE_Place::BE_PLACE_ENTREPOT,
                                    'id_entrepot'  => (int) $id_entrepot,
                                    'infos'        => 'Retour produit (Vente #' . $this->id . ')',
                                    'date'         => date('Y-m-d H:i:s'),
                                    'code_mvt'     => $codemove . '_RET' . (int) $return->id
                                ));
                            }
                            if (!count($place_errors)) {
                                $place_errors = $place->create();
                            }

                            if (count($place_errors)) {
                                $msg = 'Echec de l\'enregistrement du nouvel emplacement du retour produit n° ' . $i . ' (N° série: ' . $equipment->getData('serial') . ')';
                                $errors[] = BimpTools::getMsgFromArray($place_errors, $msg);

                                dol_syslog('[ERREUR STOCK] ' . $msg . - ' Vente #' . $this->id . ' - Article #' . $article->id . ' - Erreurs: ' . BimpTools::getMsgFromArray($place_errors), LOG_ERR);
                            }
                        } else {
                            $product = $return->getChildObject('product');

                            if ((int) $return->getData('defective')) {
                                $result = $product->dol_object->correct_stock($user, $id_defective_entrepot, (int) $return->getData('qty'), 0, 'Retour produit Vente #' . $this->id, 0, $codemove . '_RET' . (int) $return->id,'facture',$id_facture);
                            } else {
                                $result = $product->dol_object->correct_stock($user, $id_entrepot, (int) $return->getData('qty'), 0, 'Retour produit Vente #' . $this->id, 0, $codemove . '_RET' . (int) $return->id,'facture',$id_facture);
                            }

                            if ($result < 0) {
                                $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getRef() . ' - ' . $product->getName() . '" (ID: ' . $product->id . ')';
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), $msg);
                                dol_syslog('[ERREUR STOCK] ' . $msg . - ' Vente #' . $this->id . ' - Article #' . $article->id . ' - Qté: ' . (int) $article->getData('qty'), LOG_ERR);
                            }
                        }
                        $i++;
                    }
                }
            }
        } else {
            $errors[] = 'Cette vente n\'existe pas';
            return false;
        }

        return true;
    }

    protected function createFacture(&$errors, &$warnings = array(), $is_validated = false)
    {
        if (!$is_validated) {
            if (!$this->checkVente($errors)) {
                return 0;
            }

            if ((int) $this->getData('status') !== 2) {
                $errors[] = 'Cette vente n\'a pas le statut "validée". Création de la facture impossible';
                return false;
            }
        }

        global $db, $user, $langs, $conf;

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

        $entrepot = $this->getChildObject('entrepot');
        $caisse = $this->getChildObject('caisse');
        $id_account = (int) $caisse->getData('id_account');
        $account = $caisse->getChildObject('account');
        $account_label = '';
        if (BimpObject::objectLoaded($account)) {
            $account_label = '"' . $account->bank . '"';
        } else {
            $account_label = ' d\'ID ' . $id_account;
        }

        $note = 'Vente en caisse. Vente n°' . $this->id;
        $note .= ' - Centre: "' . $entrepot->description . ' (' . $entrepot->libelle . ')"';
        $note .= ' - Caisse: "' . $caisse->getData('name') . '"';

        $note = 'Vente en caisse ' . $this->getNomUrl(1, 1, 0, 'default') . "\n";
        $note .= ' - Centre: "' . $entrepot->description . ' (' . $entrepot->libelle . ')"' . "\n";
        $note .= ' - Caisse: "' . $caisse->getData('name') . '"';
        
        if($this->getData('note_plus') != "")
            $note .= "\n\n".$this->getData('note_plus');

        // Création de la facture
        $is_avoir = ((float) $this->getData('total_ttc') < 0);

        if ((int) $this->getData('id_client')) {
            $id_client = (int) $this->getData('id_client');
            $id_contact = (int) $this->getData('id_client_contact');
        } else {
            $id_client = (int) BimpCore::getConf('default_id_client');
            $id_contact = 0;
        }

        $facture->validateArray(array(
            'type'              => Facture::TYPE_STANDARD,
            'ef_type'           => BimpCore::getConf('bimpcaisse_secteur_code'),
            'entrepot'          => (int) $this->getData('id_entrepot'),
            'datef'             => date('Y-m-d'),
            'fk_soc'            => $id_client,
            'fk_account'        => $id_account,
            'fk_cond_reglement' => (int) $this->getData('id_cond_reglement'),
            'note_private'      => $note
        ));

        $errors = $facture->create($warnings);

        if (count($errors)) {
            return $errors;
        }

        // Ajout contact client: 
        if ($id_contact) {
            $facture->dol_object->add_contact($id_contact, 'BILLING', 'external');
        }

        // Ajout Commercial: 
        $id_user_resp = (int) $this->getData('id_user_resp');
        if (!$id_user_resp) {
            global $user;
            $id_user_resp = $user->id;
        }
        $facture->dol_object->add_contact($id_user_resp, 'SALESREPFOLL', 'internal');

        // Ajout des avoirs client utilisés: 
        BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
        $asso = new BimpAssociation($this, 'discounts');
        $discounts = $asso->getAssociatesList();
        if (count($discounts)) {
            foreach ($discounts as $id_discount) {
                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch((int) $id_discount);

                if (!BimpObject::ObjectLoaded($discount)) {
                    $warnings[] = 'L\'avoir client d\'ID ' . $id_discount . ' n\'existe plus';
                    continue;
                }

                if ($facture->dol_object->insert_discount((int) $id_discount) <= 0) {
                    $title = 'Echec de l\'insertion de l\'avoir client #' . $id_discount . ' (' . BimpTools::displayMoneyValue($discount->amount_ttc) . ')';
                    $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), $title);
                }
            }
        }

        // Ajout des retours produits: 
        $returns = $this->getChildrenObjects('returns');
        $i = 0;
        foreach ($returns as $return) {
            $i++;
            $equipment = null;
            $product = null;
            $serial = '';

            if ((int) $return->getData('id_equipment')) {
                $equipment = $return->getChildObject('equipment');
                if (BimpObject::objectLoaded($equipment)) {
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $equipment->getData('id_product'));
                    $serial = $equipment->getData('serial');
                }
            } else {
                $product = $return->getChildObject('product');
            }

            if (!BimpObject::objectLoaded($product)) {
                $warnings[] = 'Produit invalide pour le retour n° ' . $i;
                continue;
            }

            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');

            $line->validateArray(array(
                'id_obj'             => (int) $facture->id,
                'type'               => ObjectLine::LINE_PRODUCT,
                'linked_object_name' => 'bc_vente_article',
                'linked_id_object'   => (int) $return->id
            ));

            $line->id_product = (int) $product->id;
//            $line->desc = ' (RETOUR) ' . $product->getName() . ' - Réf. ' . $product->getRef() . ($serial ? ' - N° de série: ' . $serial : '');
            $line->qty = ((int) $return->getData('qty') * -1);
            $line->tva_tx = (float) $return->getData('tva_tx');
            $line->pu_ht = (float) BimpTools::calculatePriceTaxEx((float) $return->getData('unit_price_tax_in'), $line->tva_tx);


            $line_errors = $line->create();

            if (count($line_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout du retour #' . $return->id . ' à la facture');
            }
        }

        // Ajout des lignes articles: 
        $articles = $this->getChildrenObjects('articles');
        $total_articles_ttc = 0;

        foreach ($articles as $article) {
            $total_articles_ttc += (int) $article->getData('qty') * (float) $article->getData('unit_price_tax_in');
        }

        // Calcul du total des remise globale en pourcentage: 
        $globale_remise_percent = 0;
        $remises = BimpObject::getInstance($this->module, 'BC_VenteRemise');

        foreach ($remises->getList(array(
            'id_vente'   => (int) $this->id,
            'id_article' => 0
        )) as $remise) {
            switch ((int) $remise['type']) {
                case 1:
                    $globale_remise_percent += (float) $remise['percent'];
                    break;

                case 2:
                    if ($total_articles_ttc) {
                        $montant = (float) $remise['montant'];
                        $globale_remise_percent += (float) ($montant / $total_articles_ttc) * 100;
                    }
                    break;
            }
        }

        // Ajout des produits vendus: 
        foreach ($articles as $article) {
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $article->getData('id_product'));
            $serial = '';
            $equipment = $article->getChildObject('equipment');
            if (!is_null($equipment) && $equipment->isLoaded()) {
                $serial = $equipment->getData('serial');
            }

            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');

            $line->validateArray(array(
                'id_obj'             => (int) $facture->id,
                'type'               => ObjectLine::LINE_PRODUCT,
                'linked_object_name' => 'bc_vente_article',
                'linked_id_object'   => (int) $article->id,
                'remise_crt'         => (int) $article->getData('remise_crt')
            ));

            $line->id_product = (int) $product->id;
//            $line->desc = $product->getName() . ' - Réf. ' . $product->getRef() . ($serial ? ' - N° de série: ' . $serial : '');
            $line->qty = (int) $article->getData('qty');
            $line->pu_ht = (float) $article->getData('unit_price_tax_ex');
            if ((int) $this->getData('vente_ht')) {
                $line->tva_tx = 0;
            } else {
                $line->tva_tx = (float) $article->getData('tva_tx');
            }

            $pfp = $product->getCurrentFournPriceObject();
            if (BimpObject::objectLoaded($pfp)) {
                $line->id_fourn_price = $pfp->id;
                $line->pa_ht = (float) $pfp->getData('price');
            }

            $line_warnings = array();
            $line_errors = $line->create($line_warnings);

            if (count($line_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Article #' . $article->id . ': erreurs suite à la création de la ligne de facture');
            }
            if (count($line_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout de l\'article #' . $article->id . ' à la facture');
            } else {
                // Ajout de la remise 
                $remise_percent = (float) $article->getTotalRemisesPercent($globale_remise_percent);

                if ($remise_percent) {
                    $rem_errors = $line->addRemise($remise_percent);
                    if (count($rem_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($rem_errors, 'Article #' . $article->id . ': échec de l\'ajout de la remise à la facture');
                    }
                }

                // Ajout de l\'équipement: 
                if (BimpObject::ObjectLoaded($equipment)) {
                    $eq_errors = $line->attributeEquipment((int) $equipment->id, $line->pu_ht, $line->tva_tx, $line->id_fourn_price);
                    if (count($eq_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($eq_errors, 'Article #' . $article->id . ': échec de l\'attribution de l\'équipement "' . $equipment->getData('serial') . '" à la facture');
                    }
                }
            }
        }

        // Validation de la facture: 
        if ($facture->dol_object->validate($user) <= 0) {
            $msg = 'Echec de la validation de la facture';
            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), $msg);
        }

        $facture->fetch((int) $facture->id);

        if (!class_exists('Paiement')) {
            require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
        }

        $total_paid = 0;
        $total_facture_ttc = (float) $this->getData('total_ttc');

        if (!$is_avoir) {
            // Ajout des paiements: 
            $paiements = $this->getChildrenObjects('paiements');

            $n = 0;
            foreach ($paiements as $paiement) {
                $n++;
                $montant = $paiement->getData('montant');
                $code = $paiement->getData('code');
                
                if($code != "no"){
                    $total_paid += $montant;

                    $p = new Paiement($db);
                    $p->datepaye = dol_now();
                    $p->amounts = array(
                        $facture->id => $montant
                    );
                    $p->paiementid = (int) dol_getIdFromCode($db, $code, 'c_paiement');
                    $p->facid = (int) $facture->id;

                    if ($p->create($user) < 0) {
                        $msg = 'Echec de l\'ajout à la facture du paiement n°' . $n;
                        $msg .= ' (' . BC_VentePaiement::$codes[$code]['label'] . ': ' . BimpTools::displayMoneyValue($montant, 'EUR') . ')';
                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), $msg);
                    } else {
                        if (!empty($conf->banque->enabled)) {
                            if ($p->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), 'Echec de l\'ajout du paiement n°' . $p->id . ' au compte bancaire ' . $account_label);
                            }
                        }

                        // Enregistrement du paiement en caisse
                        $paiement_errors = $caisse->addPaiement($p, $facture->id, $this->id);
                        if (count($paiement_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($paiement_errors, 'Des erreurs sont survenues lors de l\'enregistrement du paiement n°' . $n);
                        }
                    }
                }
            }

            // Ajout du rendu monnaie:
            if ($total_paid > $total_facture_ttc) {
                $returned = round($total_facture_ttc - $total_paid, 2);

                if ($returned < 0) {
                    $p = new Paiement($db);
                    $p->datepaye = dol_now();
                    $p->amounts = array(
                        $facture->id => $returned
                    );
                    $p->paiementid = (int) dol_getIdFromCode($db, 'LIQ', 'c_paiement');
                    $p->facid = (int) $facture->id;
                    $p->note = 'Rendu monnaie';

                    if ($p->create($user) < 0) {
                        $msg = 'Echec de l\'ajout à la facture du rendu monnaie de ' . BimpTools::displayMoneyValue($returned, 'EUR');
                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), $msg);
                    } else {
                        if (!empty($conf->banque->enabled)) {
                            if ($p->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                $warnings[] = 'Echec de l\'ajout du rendu monnaire de ' . BimpTools::displayMoneyValue($returned, 'EUR') . ' au compte bancaire ' . $account_label;
                                BimpTools::getErrorsFromDolObject($p, $errors, $langs);
                            }
                        }

                        $paiement_errors = $caisse->addPaiement($p, $facture->id, $this->id);

                        if (count($paiement_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($paiement_errors, 'Des erreurs sont survenues lors de l\'enregistrement du rendu monnaie de ' . BimpTools::displayMoneyValue($montant, 'EUR'));
                        }
                    }
                }
                if ($facture->dol_object->set_paid($user) < 0) {
                    $warnings[] = 'Echec de l\'enregistrement du statut "payé" pour cette facture';
                }
            } else {
                $diff = $total_facture_ttc - $total_paid;
                if ($diff < 0.01 && $diff > -0.01) {
                    if ($facture->dol_object->set_paid($user) <= 0) {
                        $warnings[] = 'Echec de l\'enregistrement du statut "payé" pour cette facture';
                    }
                }
            }
        } else {
//            // On est dans le cas d'un avoir, suppression des paiements éventuels: 
            $paiement = BimpObject::getInstance('bimpcaisse', 'BC_VentePaiement');
            $paiement->deleteByParent($this->id);

            $avoir_rbt_mode = BimpTools::getValue('avoir_rbt_mode', 'remise');

            switch ($avoir_rbt_mode) {
                case 'remise':
                    $remise_errors = $facture->convertToRemise();
                    if (count($remise_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la conversion du montant de la facture en remise client');
                    }
                    break;

                case 'rbt':
                    $avoir_rbt_paiement = BimpTools::getValue('avoir_rbt_paiement', '');
                    $rbt_errors = array();

                    if (!$avoir_rbt_mode) {
                        $rbt_errors[] = 'Mode de réglement du remboursement absent';
                    } else {
                        $p = new Paiement($db);
                        $p->datepaye = dol_now();
                        $p->amounts = array(
                            $facture->id => $total_facture_ttc
                        );
                        $p->paiementid = (int) dol_getIdFromCode($db, $avoir_rbt_paiement, 'c_paiement');
                        $p->facid = (int) $facture->id;
                        $p->note = 'Remboursement avoir client (facture négative)';

                        if ($p->create($user) < 0) {
                            $rbt_errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), 'Echec de la création du remboursement');
                        } else {
                            if (!empty($conf->banque->enabled)) {
                                if ($p->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                    $rbt_errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), 'Echec de l\'ajout du remboursement au compte bancaire ' . $account_label);
                                }
                            }

                            $paiement_errors = $caisse->addPaiement($p, $facture->id, $this->id);

                            if (count($paiement_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($paiement_errors, 'Des erreurs sont survenues lors de l\'enregistrement du remboursement de ' . BimpTools::displayMoneyValue($montant, 'EUR'));
                            }
                        }
                    }
                    if (count($rbt_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($rbt_errors, 'Des erreurs sont survenues lors de l\'ajout à la facture du remboursement de l\'avoir');
                    }
                    break;
            }
        }

        $facture->dol_object->generateDocument(self::$facture_model, $langs);

        return $facture->id;
    }

    // Overrides

    public function validate()
    {
        if (!(int) $this->getData('id_user_resp')) {
            global $user;
            if (BimpObject::ObjectLoaded($user)) {
                $this->set('id_user_resp', (int) $user->id);
            }
        }

        return parent::validate();
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $current_id_client = (int) $this->getSavedData('id_client');
        if ($current_id_client !== (int) $this->getData('id_client')) {
            $this->setAssociatesList('discounts', array());
        }

        $data = $this->getAjaxData();
        $this->set('total_ttc', (float) $data['total_ttc'] - (float) $data['total_remises'] - (float) $data['total_returns'] - (float) $data['total_discounts']);

        $errors = parent::update($warnings, $force_update);

        return $errors;
    }
}

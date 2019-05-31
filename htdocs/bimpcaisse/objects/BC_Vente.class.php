<?php

class BC_Vente extends BimpObject
{

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
                if (isset($product->id) && $product->id) {
                    $articles[$article->id] = $product->label . ' (Ref: "' . $product->ref . '")';
                }
            }
            return $articles;
        }

        return array();
    }

    public function getCond_reglementsArray()
    {
        $rows = $this->db->getRows('c_payment_term', '`active` > 0', null, 'array', array('rowid', 'libelle'), 'sortorder');

        $conds = array();
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $conds[(int) $r['rowid']] = $r['libelle'];
            }
        }

        return $conds;
    }

    public function getAjaxData()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $vente_ht = (int) $this->getData('vente_ht');

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

            $returns[] = array(
                'id_return'     => (int) $return->id,
                'label'         => $return->getLabel(),
                'qty'           => $qty,
                'unit_price'    => BimpTools::displayMoneyValue($price_ttc, 'EUR'),
                'unit_price_ht' => BimpTools::displayMoneyValue((float) $return->getData('unit_price_tax_ex'), 'EUR'),
                'total_ttc'     => BimpTools::displayMoneyValue($return_total_ttc, 'EUR'),
                'tva'           => (float) $return->getData('tva_tx') . ' %',
                'defective'     => (int) $return->getData('defective')
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
        $html .= '<select id="condReglementSelect" name="condReglementSelect">';
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

    public function renderSelectEquipmentLine($id_equipment, &$errors, $current_equipments = array())
    {
        $html = '';

        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
        if (!$equipment->isLoaded()) {
            $errors[] = 'Erreur: aucun enregistrement trouvé pour l\'équipement d\'ID ' . $id_equipment;
        } else {
            $id_product = $equipment->getData('id_product');

            if (is_null($id_product) || !$id_product) {
                $errors[] = 'Erreur: aucun produit associé à l\'équipement ' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '")';
            } else {
                $product = $equipment->getChildObject('product');
                if (is_null($product) || !isset($product->id) || !$product->id) {
                    $errors[] = 'Erreur: produit d\'ID ' . $equipment->getData('id_product') . ' non trouvé pour l\'équipement d\'ID' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '")';
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
                        $html .= BimpRender::renderAlerts('cet équipement a déjà été ajouté au panier');
                    } else {
                        $html .= '<div style="margin-top: 10px; text-align: right">';
                        $html .= '<button type="button" class="btn btn-primary"';
                        $html .= ' onclick="selectArticle($(this), ' . $id_equipment . ', \'Equipment\')"';
                        $html .= '>';
                        $html .= 'Sélectionner<i class="fa fa-chevron-right iconRight"></i>';
                        $html .= '</button>';
                        $html .= '</div>';
                    }

                    $html .= '</div>';
                }
            }
        }
        return $html;
    }

    public function renderSelectProductLine($id_product, &$errors)
    {
        $html = '';

        if (!class_exists('Product')) {
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        }

        global $db;
        $product = new Product($db);

        if ($product->fetch($id_product) <= 0) {
            $msg = 'Erreur: aucun enregistrement trouvé pour le produit d\'id ' . $id_product;
            if ($product->error) {
                $msg .= ' - ' . $product->error;
            }
            $errors[] = $msg;
        } else {
            $html .= '<div class="selectArticleLine">';
            $html .= '<div class="product_title"><strong>Produit:</strong> "' . $product->label . '"</div>';
            $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
            $html .= '<div class="product_price">';

            if ((int) $this->getData('vente_ht')) {
                $html .= BimpTools::displayMoneyValue($product->price, 'EUR');
            } else {
                $html .= BimpTools::displayMoneyValue($product->price_ttc, 'EUR');
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
            } elseif (is_null($product) || !isset($product->id) || !$product->id) {
                $errors[] = 'Produit associé à cet article non enregistré';
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

    public function renderCartEquipmentline(BC_VenteArticle $article, Product $product, Equipment $equipment)
    {
        $html = '';
        $html .= '<div id="cart_article_' . $article->id . '" class="cartArticleLine" data-id_article="' . $article->id . '">';
        $html .= '<div class="product_title">' . $product->label;
        $html .= '<span class="removeArticle" onclick="removeArticle($(this), ' . $article->id . ');">';
        $html .= '<i class="fa fa-trash"></i>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<div class="product_info"><strong>Equipement ' . $equipment->id . ' - n° de série: ' . $equipment->getData('serial') . '</strong></div>';
        $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
        $html .= '<div class="article_remises">';
        $html .= '<div class="title">Remises: </div>';
        $html .= '<div class="content"></div>';
        $html .= '</div>';
        $html .= '<div class="article_options">';
        $html .= '<div class="article_qty">&nbsp;</div>';
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
            $html .= BimpRender::renderAlerts('Attention, cet équipement n\'est pas enregistré comme étant situé dans votre centre', 'warning');
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function renderCartProductLine(BC_VenteArticle $article, Product $product)
    {
        $html = '';
        $qty = (int) $article->getData('qty');
        $stock = (int) $article->getProductStock((int) $this->getData('id_entrepot'));

        $html .= '<div id="cart_article_' . $article->id . '" class="cartArticleLine" data-id_article="' . $article->id . '">';
        $html .= '<div class="product_title">' . $product->label;
        $html .= '<span class="removeArticle" onclick="removeArticle($(this), ' . $article->id . ');">';
        $html .= '<i class="fa fa-trash"></i>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
        if ((int) $this->getData('vente_ht')) {
            $html .= '<div class="product_info"><strong>Prix unitaire HT: </strong>' . BimpTools::displayMoneyValue($product->price, 'EUR') . '</div>';
        } else {
            $html .= '<div class="product_info"><strong>Prix unitaire TTC: </strong>' . BimpTools::displayMoneyValue($product->price_ttc, 'EUR') . '</div>';
        }
        $html .= '<div class="article_remises">';
        $html .= '<div class="title">Remises: </div>';
        $html .= '<div class="content"></div>';
        $html .= '</div>';
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
        $html .= '<div class="product_total_price">';
        $html .= '<span class="base_price"></span>';
        $html .= '<span class="final_price">';

        if ((int) $this->getData('vente_ht')) {
            $html .= BimpTools::displayMoneyValue($product->price, 'EUR');
        } else {
            $html .= BimpTools::displayMoneyValue($product->price_ttc, 'EUR');
        }

        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="stockAlert"' . (($stock >= $qty) ? ' style="display: none"' : '') . '>';
        $html .= BimpRender::renderAlerts('Attention, le stock de ce produit est dépassé.<br/><strong class="stock">Stock: <span>' . $stock . '</span></strong>', 'warning');
        $html .= '</div>';
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

            if (is_null($id_product) || !$id_product) {
                $errors[] = 'Erreur: aucun produit associé à l\'équipement ' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '")';
                return null;
            } else {
                $place = $equipment->getCurrentPlace();
                if (!is_null($place) && $place->isLoaded()) {
                    if (in_array((int) $place->getData('type'), array(1, 4))) {
                        $errors[] = 'L\'équipement ' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '") est enregistré comme déjà vendu';
                        return null;
                    }
                }

                if (count($equipment->getReservationsList())) {
                    $errors[] = 'L\'équipement ' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '") est réservé';
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
        if (!is_null($equipment)) {
            $product = $equipment->getChildObject('product');
            if (is_null($product) || !isset($product->id) || !$product->id) {
                $errors[] = 'Erreur: produit d\'ID ' . $equipment->getData('id_product') . ' non trouvé pour l\'équipement d\'ID' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '")';
            } else {
                $article = BimpObject::getInstance($this->module, 'BC_VenteArticle');
                $prix_ht = 0;
                $prix_ttc = 0;

                $prix_except = (float) $equipment->getData('prix_vente_except');
                if ($prix_except > 0) {
                    $prix_ttc = $prix_except;
                } else {
                    $prix_ttc = $product->price_ttc;
                }

                $prix_ht = BimpTools::calculatePriceTaxEx($prix_ttc, (float) $product->tva_tx);

                $article_errors = $article->validateArray(array(
                    'id_vente'          => $this->id,
                    'id_product'        => $product->id,
                    'id_equipment'      => (int) $id_equipment,
                    'qty'               => 1,
                    'unit_price_tax_ex' => (float) $prix_ht,
                    'unit_price_tax_in' => (float) round($prix_ttc, 2),
                    'tva_tx'            => (float) $product->tva_tx
                ));

                if (count($article_errors)) {
                    $errors = array_merge($errors, $article_errors);
                } else {
                    $article_errors = $article->create();
                    if (count($article_errors)) {
                        $errors = array_merge($errors, $article_errors);
                    } else {
                        $html .= $this->renderCartEquipmentline($article, $product, $equipment);
                    }
                }
            }
        }

        return $html;
    }

    public function addCartProduct($id_product, &$errors, $qty = 1)
    {
        $html = '';
        if (!class_exists('Product')) {
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        }

        global $db;
        $product = new Product($db);

        if ($product->fetch($id_product) <= 0) {
            $msg = 'Erreur: aucun enregistrement trouvé pour le produit d\'id ' . $id_product;
            if ($product->error) {
                $msg .= ' - ' . $product->error;
            }
            $errors[] = $msg;
        } else {
            $article = BimpObject::getInstance($this->module, 'BC_VenteArticle');
            $article_errors = $article->validateArray(array(
                'id_vente'          => $this->id,
                'id_product'        => $id_product,
                'id_equipment'      => 0,
                'qty'               => 1,
                'unit_price_tax_ex' => (float) BimpTools::calculatePriceTaxEx((float) $product->price_ttc, (float) $product->tva_tx),
                'unit_price_tax_in' => (float) round($product->price_ttc, 2),
                'tva_tx'            => (float) $product->tva_tx
            ));

            if (count($article_errors)) {
                $errors = array_merge($errors, $article_errors);
            } else {
                $article_errors = $article->create();
                if (count($article_errors)) {
                    $errors = array_merge($errors, $article_errors);
                } else {
                    $html .= $this->renderCartProductLine($article, $product);
                }
            }
        }
        return $html;
    }

    public function findArticleToAdd($search, &$errors = array())
    {
        $cart_html = '';
        $result_html = '';

        // Recherche d'équipement via n° de série: 

        $current_equipments = $this->getCurrentEquipments();

        $rows = $this->db->getValues('be_equipment', 'id', 'serial = "' . $search . '" || concat("S", serial) = "' . $search . '"');

        if (!is_null($rows)) {
            $equipements = array();

            foreach ($rows as $id_eq) {
                if (!in_array((int) $id_eq, $equipements)) {
                    $equipements[] = (int) $id_eq;
                }
            }

            if (count($equipements) > 1) {
                $msg = count($equipements) . ' équipements trouvés pour le numéro de série "' . $search . '"';
                $result_html = BimpRender::renderAlerts($msg, 'info');

                foreach ($equipements as $id_equipment) {
                    $result_html .= $this->renderSelectEquipmentLine($id_equipment, $errors, $current_equipments);
                }
            } elseif (count($equipements)) {
                if (array_key_exists($equipements[0], $current_equipments)) {
                    $errors[] = 'L\'équipement correspondant au numéro de série "' . $search . '" a déjà été ajouté au panier';
                } else {
                    $cart_html = $this->addCartEquipement($equipements[0], $errors);
                }
            }
        } else {
            $sql = 'SELECT p.rowid as id, p.label, pe.serialisable FROM ' . MAIN_DB_PREFIX . 'product p ';
            $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'product_extrafields pe ';
            $sql .= ' ON p.rowid = pe.fk_object';
            $sql .= ' WHERE p.barcode = "' . $search . '" OR p.ref LIKE "%' . $search . '%"';

            $rows = $this->db->executeS($sql, 'array');

            if (!is_null($rows) && count($rows)) {
                $products = array();

                $current_products = $this->getCurrentProducts();

                foreach ($rows as $r) {
                    if (!in_array((int) $r['id'], $products)) {
                        if ((int) $r['serialisable']) {
                            $msg = 'Vous devez obligatoirement saisir le numéro de série pour enregistrer un produit "' . $r['label'] . '"';
                            $result_html .= BimpRender::renderAlerts($msg, 'warning');
                            continue;
                        }
                        $products[] = (int) $r['id'];
                    }
                }

                if (count($rows) > 1) {
                    if (count($products) > 6) {
                        $msg = 'Un trop grand nombre de produits ont été trouvés.<br/>Veuillez utiliser un terme de recherche plus précis.';
                        $result_html .= BimpRender::renderAlerts($msg, 'warning');
                    } elseif (count($products)) {
                        $msg = count($products) . ' produit(s) trouvé(s) pour la recheche "' . $search . '"';
                        $result_html .= BimpRender::renderAlerts($msg, 'info');

                        foreach ($products as $id_product) {
                            $result_html .= $this->renderSelectProductLine($id_product, $errors);
                        }
                    }
                } elseif (count($products)) {
                    if (array_key_exists((int) $products[0], $current_products)) {
                        $article = BimpCache::getBimpObjectInstance($this->module, 'BC_VenteArticle', (int) $current_products[(int) $products[0]]);
                        if ($article->isLoaded()) {
                            $qty = (int) $article->getData('qty');
                            $article->set('qty', ($qty + 1));
                            $errors = array_merge($errors, $article->update());
                        } else {
                            $errors[] = 'Un article a déjà été ajouté au panier pour ce code-barres mais n\'a pas pu être mis à jour';
                        }
                    } else {
                        $cart_html = $this->addCartProduct((int) $products[0], $errors);
                    }
                }
            } else {
                $result_html .= BimpRender::renderAlerts('Aucun produit trouvé', 'warning');
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

        // Vérification de la validité de la vente:

        if (is_null($caisse) || !$caisse->isLoaded()) {
            $errors[] = 'Caisse absente ou invalide';
        }

        $client = $this->getChildObject('client');

        if ((int) $this->getData('vente_ht')) {
            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Vente au prix HT: compte client obligatoire (avec n° de TVA intracommunautaire renseigné)';
            } elseif (!(string) $client->getData('tva_intra')) {
                $errors[] = 'Vente au prix HT: le n° de TVA intracommunautaire du client doit être renseigné';
            }
        }

        $has_equipment = false;
        foreach ($articles as $article) {
            $product = $article->getChildObject('product');
            $id_equipment = (int) $article->getData('id_equipment');
            if (is_null($product) || !isset($product->id) || !$product->id) {
                $errors[] = 'Produit invalide pour l\'article ' . $article->id;
            } elseif (isset($product->array_options['options_serialisable']) && $product->array_options['options_serialisable']) {
                if (!$id_equipment) {
                    $errors[] = 'Un numéro de série est obligatoire pour le produit "' . $product->ref . ' - ' . $product->label . '"';
                }
                $has_equipment = true;
            }
        }

        $i = 1;
        $has_returns = false;
        foreach ($returns as $return) {
            $has_returns = true;
            $product = null;
            $id_equipment = (int) $return->getData('id_equipment');
            if ((int) $id_equipment) {
                $has_equipment = true;
                $equipment = $return->getChildObject('equipment');
                if (!BimpObject::objectLoaded($equipment)) {
                    $errors[] = 'Equipement inéxistant pour le retour produit n°' . $i;
                } else {
                    $place = $equipment->getCurrentPlace();
                    if (!BimpObject::objectLoaded($place)) {
                        $errors[] = 'Aucun emplacement enregistré pour l\'équipement retourné "' . $equipment->displayProduct('nom', true) . ' - ' . $equipment->getData('serial') . '"';
                    } elseif ((int) $place->getData('type') !== BE_Place::BE_PLACE_CLIENT ||
                            (int) $place->getData('id_client') !== (int) $this->getData('id_client')) {
                        $errors[] = 'L\'emplacement de l\'équipement retourné "' . $equipment->displayProduct('nom', true) . ' - ' . $equipment->getData('serial') . '" ne correspond pas au client sélectionné';
                    }
                }
            } else {
                $product = $return->getChildObject('product');
                if (!BimpObject::objectLoaded($product)) {
                    $errors[] = 'Aucun produit pour le retour n°' . $i;
                } elseif ($product->isSerialisable()) {
                    $errors[] = 'Sélection d\'un équipement obligatoire pour le retour produit n°' . $i;
                }
            }
            $i++;
        }

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

        $data = $this->getAjaxData();

        if (($has_equipment || ($has_returns && $total_ttc < 0) || (int) $data['paiement_differe']) && (is_null($client) || !$client->isLoaded())) {
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

            $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');
            $id_client = (int) $this->getData('id_client');
            $id_entrepot = (int) $this->getData('id_entrepot');

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
                            'infos'        => 'Correction automatique suite à la vente de l\'équipement',
                            'date'         => date('Y-m-d H:i:s')
                        ));
                        if (!count($place_errors)) {
                            $place_errors = $place->create();
                        }

                        if (count($place_errors)) {
                            $errors[] = 'Echec de la correction de l\'emplacement pour le n° de série "' . $equipment->getData('serial') . '"';
                        }
                    }

                    // Création du nouvel emplacement: 
                    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                    if ($id_client) {
                        $place_errors = $place->validateArray(array(
                            'id_equipment' => (int) $equipment->id,
                            'type'         => BE_Place::BE_PLACE_CLIENT,
                            'id_client'    => (int) $id_client,
                            'infos'        => 'Vente',
                            'date'         => date('Y-m-d H:i:s')
                        ));
                    } else {
                        $place_errors = $place->validateArray(array(
                            'id_equipment' => (int) $equipment->id,
                            'type'         => BE_Place::BE_PLACE_FREE,
                            'place_name'   => 'Equipement vendu (client non renseigné)',
                            'infos'        => 'Vente',
                            'date'         => date('Y-m-d H:i:s')
                        ));
                    }
                    if (!count($place_errors)) {
                        $place_errors = $place->create();
                    }

                    if (count($place_errors)) {
                        $errors[] = 'Echec de l\'enregistrement du nouvel emplacement pour le n° de série "' . $equipment->getData('serial') . '"';
                        $errors = array_merge($errors, $place_errors);
                    }
                } else {
                    $product = $article->getChildObject('product');
                    $result = $product->correct_stock($user, $id_entrepot, (int) $article->getData('qty'), 1, 'Vente - ID: ' . $this->id, 0, $codemove);
                    if ($result < 0) {
                        $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->label . '" (Ref: "' . $product->ref . '")';
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product), $msg);
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
                                'infos'        => 'Retour produit défectueux (Vente ' . $this->id . ')',
                                'date'         => date('Y-m-d H:i:s')
                            ));
                        } else {
                            $place_errors = $place->validateArray(array(
                                'id_equipment' => (int) $equipment->id,
                                'type'         => BE_Place::BE_PLACE_ENTREPOT,
                                'id_entrepot'  => (int) $id_entrepot,
                                'infos'        => 'Retour produit (Vente ' . $this->id . ')',
                                'date'         => date('Y-m-d H:i:s')
                            ));
                        }
                        if (!count($place_errors)) {
                            $place_errors = $place->create();
                        }

                        if (count($place_errors)) {
                            $errors[] = 'Echec de l\'enregistrement du nouvel emplacement le retourn produit n° ' . $i . ' (N° série: ' . $equipment->getData('serial') . ')';
                            $errors = array_merge($errors, $place_errors);
                        }
                    } else {
                        $product = $return->getChildObject('product');

                        if ((int) $return->getData('defective')) {
                            $result = $product->dol_object->correct_stock($user, $id_defective_entrepot, (int) $return->getData('qty'), 0, 'Retour produit Vente ' . $this->id, 0, $codemove);
                        } else {
                            $result = $product->dol_object->correct_stock($user, $id_entrepot, (int) $return->getData('qty'), 0, 'Retour produit Vente ' . $this->id, 0, $codemove);
                        }

                        if ($result < 0) {
                            $msg = 'Echec de la mise à jour du stock pour le produit retourné "' . $product->dol_object->label . '" (Ref: "' . $product->dol_object->ref . '")';
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), $msg);
                        }
                    }
                    $i++;
                }
            }

            // Création de la facture et de l'avoir éventuel:
            $facture_errors = array();
            $id_facture = (int) $this->createFacture($facture_errors, true);
            if (!$id_facture) {
                $errors[] = 'Echec de la création de la facture';
            } elseif (count($facture_errors)) {
                $errors[] = 'Des erreurs sont survenues lors de la création de la facture';
            }
            if (count($facture_errors)) {
                $errors = array_merge($errors, $facture_errors);
            }
            if ($id_facture) {
                $this->set('id_facture', $id_facture);
                $update_errors = $this->update();
                if (count($update_errors)) {
                    $errors[] = 'Facture créée avec succès mais échec de l\'enregistrement du numéro de facture (' . $id_facture . ')<br/>Une correction manuelle est nécessaire.';
                }
            }
        } else {
            $errors[] = 'Cette vente n\'existe pas';
            return false;
        }

        return true;
    }

    protected function createFacture(&$errors, $is_validated = false)
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

        $langs->load('errors');
        $langs->load('bills');
        $langs->load('companies');
        $langs->load('compta');
        $langs->load('products');
        $langs->load('banks');
        $langs->load('main');

        if (!class_exists('Facture')) {
            require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        }

        $centre = $this->getChildObject('entrepot');
        $caisse = $this->getChildObject('caisse');
        $id_account = (int) $caisse->getData('id_account');
        $account = $caisse->getChildObject('account');
        $account_label = '';
        if (BimpObject::objectLoaded($account)) {
            $account_label = '"' . $account->bank . '"';
        } else {
            $account_label = ' d\'ID ' . $id_account;
        }

        // Création de la facture
        $is_avoir = ((float) $this->getData('total_ttc') < 0);

        $facture = new Facture($db);

        $facture->date = dol_now();

        $id_client = (int) $this->getData('id_client');
        if (!$id_client) {
            $facture->socid = (int) BimpCore::getConf('default_id_client');
        } else {
            $facture->socid = $id_client;
        }

        $note = 'Vente en caisse. Vente n°' . $this->id;
        $note .= ' - Centre: "' . $centre->description . ' (' . $centre->libelle . ')"';
        $note .= ' - Caisse: "' . $caisse->getData('name') . '"';

        $facture->note_private = $note;
        $facture->fk_user_author = $user->id;

        $facture->array_options['options_type'] = BimpCore::getConf('bimpcaisse_secteur_code');
        $facture->array_options['options_entrepot'] = (int) $this->getData('id_entrepot');
        $facture->type = Facture::TYPE_STANDARD;

        if (!$is_avoir) {
            $facture->cond_reglement_id = (int) $this->getData('id_cond_reglement');
        }

        if ($facture->create($user) <= 0) {
            if ($facture->error) {
                $errors[] = '"' . $langs->trans($facture->error) . '"';
            }
            return 0;
        }

        // Ajout des avoirs client utilisés: 
        $asso = new BimpAssociation($this, 'discounts');
        $discounts = $asso->getAssociatesList();
        if (count($discounts)) {
            foreach ($discounts as $id_discounts) {
                $facture->error = '';
                $facture->errors = array();

                if ($facture->insert_discount((int) $id_discounts) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture), 'Echec de l\'insertion de l\'avoir client d\'ID ' . $id_discounts);
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
                    $product = $equipment->getChildObject('product');
                    $serial = $equipment->getData('serial');
                }
            } else {
                $product = $return->getChildObject('product');
            }

            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Produit invalide pour le retour n° ' . $i;
                continue;
            }

            $fk_product = $product->id;
            $desc = ' (RETOUR) ' . $product->label . ' - Réf. ' . $product->ref . ($serial ? ' - N° de série: ' . $serial : '');
            $qty = (int) $return->getData('qty');
            $pu_ttc = (float) $return->getData('unit_price_tax_in');
            $txtva = (float) $return->getData('tva_tx');
            $pu_ht = (float) BimpTools::calculatePriceTaxEx($pu_ttc, $txtva);

            $pu_ht *= -1;
            $pu_ttc *= -1;

            $remise_percent = 0;
            $txlocaltax1 = 0;
            $txlocaltax2 = 0;
            $price_base_type = 'TTC';
            $date_start = '';
            $date_end = '';
            $ventil = 0;
            $info_bits = 0;
            $fk_remise_except = '';

            $facture->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $ventil, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc);

            // Mise à jour des données de la vente dans le cas d'un équipement:
            if (BimpObject::objectLoaded($equipment)) {
                $equipment->set('date_vente', '');
                $equipment->set('prix_vente', 0);
                $equipment->set('id_facture', 0);
                $equipment->update();
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

            $fk_product = $product->id;
            $desc = $product->getData('label') . ' - Réf. ' . $product->getData('ref') . ($serial ? ' - N° de série: ' . $serial : '');
            $qty = (int) $article->getData('qty');

            if ((int) $this->getData('vente_ht')) {
                $pu_ttc = (float) $article->getData('unit_price_tax_ex');
                $txtva = 0;
                $pu_ht = $pu_ttc;
            } else {
                $pu_ttc = (float) $article->getData('unit_price_tax_in');
                $txtva = (float) $article->getData('tva_tx');
                $pu_ht = (float) BimpTools::calculatePriceTaxEx($pu_ttc, $txtva);
            }

            $remise_percent = (float) $article->getTotalRemisesPercent($globale_remise_percent);

            $fk_fournprice = 0;
            $pa_ht = 0;

            $pfp = $product->getCurrentFournPriceObject();
            if (BimpObject::objectLoaded($pfp)) {
                $fk_fournprice = $pfp->id;
                $pa_ht = (float) $pfp->getData('price');
            }

            $txlocaltax1 = 0;
            $txlocaltax2 = 0;
            $price_base_type = 'TTC';
            $date_start = '';
            $date_end = '';
            $ventil = 0;
            $info_bits = 0;
            $fk_remise_except = '';
            $type = Facture::TYPE_STANDARD;
            $rang = -1;
            $special_code = 0;
            $origin = '';
            $origin_id = 0;
            $fk_parent_line = 0;

//            addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=self::TYPE_STANDARD, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='', $array_options=0, $situation_percent=100, $fk_prev_id=0, $fk_unit = null, $pu_ht_devise = 0)
            $facture->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $ventil, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $type, $rang, $special_code, $origin, $origin_id, $fk_parent_line, $fk_fournprice, $pa_ht);

            // Enregistrement des données de la vente dans le cas d'un équipement:
            if (!is_null($equipment) && $equipment->isLoaded()) {
                $final_price = $pu_ttc;
                if ($remise_percent > 0) {
                    $final_price -= (float) ($pu_ttc * ($remise_percent / 100));
                }
                $equipment->set('date_vente', date('Y-m-d H:i:s'));
                $equipment->set('prix_vente', $final_price);
                $equipment->set('id_facture', $facture->id);
                $equipment->update();
            }
        }

        // Validation de la facture: 
        if ($facture->validate($user) <= 0) {
            $msg = 'Echec de la validation de la facture';
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture), $msg);
        }

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
                $total_paid += $montant;

                $p = new Paiement($db);
                $p->datepaye = dol_now();
                $p->amounts = array(
                    $facture->id => $montant
                );
                $p->paiementid = (int) dol_getIdFromCode($db, $code, 'c_paiement');
                $p->facid = (int) $facture->id;

                if ($p->create($user) < 0) {
                    $msg = 'Echec de l\'ajout à la facture du paiement n°' . $paiement->id;
                    $msg .= ' (' . BC_VentePaiement::$codes[$code]['label'] . ': ' . BimpTools::displayMoneyValue($montant, 'EUR') . ')';
                    $errors[] = $msg;
                    BimpTools::getErrorsFromDolObject($p, $errors, $langs);
                } else {
                    if (!empty($conf->banque->enabled)) {
                        if ($p->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), 'Echec de l\'ajout du paiement n°' . $p->id . ' au compte bancaire ' . $account_label);
                        }
                    }

                    $paiement_errors = $caisse->addPaiement($p, $facture->id, $this->id);
                    if (count($paiement_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($paiement_errors, 'Des erreurs sont survenues lors de l\'enregistrement du paiement n°' . $n);
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
                        $errors[] = $msg;
                        BimpTools::getErrorsFromDolObject($p, $errors, $langs);
                    } else {
                        if (!empty($conf->banque->enabled)) {
                            if ($p->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                $errors[] = 'Echec de l\'ajout du rendu monnaire de ' . BimpTools::displayMoneyValue($returned, 'EUR') . ' au compte bancaire ' . $account_label;
                                BimpTools::getErrorsFromDolObject($p, $errors, $langs);
                            }
                        }

                        $paiement_errors = $caisse->addPaiement($p, $facture->id, $this->id);

                        if (count($paiement_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($paiement_errors, 'Des erreurs sont survenues lors de l\'enregistrement du rendu monnaie de ' . BimpTools::displayMoneyValue($montant, 'EUR'));
                        }
                    }
                }
                if ($facture->set_paid($user) < 0) {
                    $errors[] = 'Echec de l\'enregistrement du statut "payé" pour cette facture';
                }
            } else {
                $diff = $total_facture_ttc - $total_paid;
                if ($diff < 0.01 && $diff > -0.01) {
                    if ($facture->set_paid($user) <= 0) {
                        $errors[] = 'Echec de l\'enregistrement du statut "payé" pour cette facture';
                    }
                }
            }
        } else {
//            // On est dans le cas d'un avoir, suppression des paiements éventuels: 
            $paiement = BimpObject::getInstance('bimpcaisse', 'BC_VentePaiement');
            $paiement->deleteByParent($this->id);

            $avoir_rbt_mode = BimpTools::getValue('avoir_rbt_mode', '');

            switch ($avoir_rbt_mode) {
                case 'remise':
                    $remise_errors = array();
                    $bimpFacture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $facture->id);
                    if (BimpObject::objectLoaded($bimpFacture)) {
                        $remise_errors = $bimpFacture->convertToRemise();
                    } else {
                        $remise_errors[] = 'Fature invalide';
                    }

                    if (count($remise_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la conversion du montant de la facture en remise client');
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
                            $msg = 'Echec de la création du remboursement';
                            $rbt_errors[] = $msg;
                            BimpTools::getErrorsFromDolObject($p, $errors, $langs);
                        } else {
                            if (!empty($conf->banque->enabled)) {
                                if ($p->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                    $rbt_errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), 'Echec de l\'ajout du remboursement au compte bancaire ' . $account_label);
                                }
                            }

                            $paiement_errors = $caisse->addPaiement($p, $facture->id, $this->id);

                            if (count($paiement_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($paiement_errors, 'Des erreurs sont survnues lors de l\'enregistrement du remboursement de ' . BimpTools::displayMoneyValue($montant, 'EUR'));
                            }
                        }
                    }
                    if (count($rbt_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($rbt_errors, 'Des erreurs sont survenues lors de l\'ajout à la facture du remboursement de l\'avoir');
                    }
                    break;
            }
        }

        $facture->generateDocument(self::$facture_model, $langs);

        return $facture->id;
    }

    // Overrides

    public function update()
    {
        $current_id_client = (int) $this->getSavedData('id_client');
        if ($current_id_client !== (int) $this->getData('id_client')) {
            $this->setAssociatesList('discounts', array());
        }

        $data = $this->getAjaxData();
        $this->set('total_ttc', (float) $data['total_ttc'] - (float) $data['total_remises'] - (float) $data['total_returns'] - (float) $data['total_discounts']);

        parent::update();
    }
}

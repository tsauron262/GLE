<?php

class BC_Vente extends BimpObject
{

    public static $states = array(
        0 => array('label' => 'Abandonnée', 'icon' => 'times', 'classes' => array('danger')),
        1 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        2 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('success'))
    );

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

    public function getAjaxData()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $nb_articles = 0;
        $total_ttc = 0;
        $articles = array();

        foreach ($this->getChildrenObjects('articles') as $article) {
            $qty = (int) $article->getData('qty');
            $article_total_ttc = (float) ($article->getData('unit_price_tax_in') * $qty);
            $nb_articles += $qty;
            $total_ttc += $article_total_ttc;
            $articles[] = array(
                'id_article' => (int) $article->id,
                'qty'        => (int) $qty,
                'total_ttc'  => $article_total_ttc
            );
        }

        $toPay = $total_ttc;

        foreach ($this->getChildrenObjects('paiements') as $paiement) {
            $montant = (float) $paiement->getData('montant');
            $toPay -= $montant;
        }

        $toReturn = 0;
        if ($toPay < 0) {
            $toReturn = -$toPay;
            $toPay = 0;
        }

        return array(
            'id_vente'    => (int) $this->id,
            'nb_articles' => $nb_articles,
            'total_ttc'   => $total_ttc,
            'total_ht'    => 0,
            'toPay'       => $toPay,
            'toReturn'    => $toReturn,
            'articles'    => $articles
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

    public function getDefaultListExtraButtons()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') !== 2) {
                return array(
                    array(
                        'label'   => 'Editer',
                        'icon'    => 'edit',
                        'onclick' => 'loadVente($(this), ' . $this->id . ');'
                    )
                );
            }
        }

        return array();
    }

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
        if ($this->isLoaded()) {
            $nb_articles = 0;
            foreach ($this->getChildrenObjects('articles') as $article) {
                $qty = (int) $article->getData('qty');
                $nb_articles += $qty;
            }
            return $nb_articles;
        }

        return '';
    }

    public function renderCreationViewHtml()
    {
        $html = '';

        $html .= '<div id="curVenteGlobal" class="row">';

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
        $html .= '<button id="saveClientButton" type="button" class="btn btn-primary"';
        $html .= ' onclick="saveClient();">';
        $html .= '<i class="fa fa-save iconLeft"></i>Enregistrer';
        $html .= '</button>';
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
            $html .= ' onclick="loadModalView(\'bimpcore\', \'Bimp_Societe\', ' . $client->id . ', \'client\', $(this), \'' . htmlentities($title) . '\');"';
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
        $html .= '<div id="venteNbArticles">';
        $html .= $nbArticles . ' article' . ($nbArticles > 1 ? 's' : '');
        $html .= '</div>';

        $html .= '<div id="ventePanierTotal">';
        $html .= 'Total TTC: <span>' . BimpTools::displayMoneyValue($total, 'EUR') . '</span>';
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

        $html .= '<div id="venteToPay">';
        $html .= 'Reste à payer: <span>' . BimpTools::displayMoneyValue($toPay, 'EUR') . '</span>';
        $html .= '</div>';

        $html .= '<div id="venteToReturn" style="display: none">A rendre: <span>0,00 &euro;</span></div>';

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

        $html .= '</div>';

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
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div id="ventePaimentsLines">';
        $html .= $this->renderPaiementsLines();
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

    public function renderSelectEquipmentLine($id_equipment, &$errors, $current_equipments = array())
    {
        $html = '';

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment', $id_equipment);
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
                    $html .= '<div class="selectArticleLine">';
                    $html .= '<div class="equipment_title"><strong>Equipement ' . $id_equipment . '</strong> - n° de série: <strong>' . $equipment->getData('serial') . '</strong></div>';
                    $html .= '<div class="product_title"><strong>Produit:</strong> "' . $product->label . '"</div>';
                    $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
                    $html .= '<div class="product_price">' . BimpTools::displayMoneyValue($product->price_ttc, 'EUR') . '</div>';

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
            $html .= '<div class="product_price">' . BimpTools::displayMoneyValue($product->price_ttc, 'EUR') . '</div>';
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
                    $equipment = $article->getChildObject('equipment');
                    if (is_null($equipment) || !$equipment->isLoaded()) {
                        $errors[] = 'L\'équipement associé à cet article n\'existe pas (ID ' . $id_equipment . ')';
                    } else {
                        $html .= '<div id="cart_article_' . $article->id . '" class="cartArticleLine" data-id_article="' . $article->id . '">';
                        $html .= '<div class="product_title">' . $product->label;
                        $html .= '<span class="removeArticle" onclick="removeArticle($(this), ' . $article->id . ');">';
                        $html .= '<i class="fa fa-trash"></i>';
                        $html .= '</span>';
                        $html .= '</div>';
                        $html .= '<div class="product_info"><strong>Equipement ' . $id_equipment . ' - n° de série: ' . $equipment->getData('serial') . '</strong></div>';
                        $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
                        $html .= '<div class="article_options">';
                        $html .= '<div class="article_qty">&nbsp;';
                        $html .= '</div>';
                        $html .= '<div class="product_total_price">' . BimpTools::displayMoneyValue($product->price_ttc, 'EUR') . '</div>';
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                } else {
                    $qty = (int) $article->getData('qty');
                    $html .= '<div id="cart_article_' . $article->id . '" class="cartArticleLine" data-id_article="' . $article->id . '">';
                    $html .= '<div class="product_title">' . $product->label;
                    $html .= '<span class="removeArticle" onclick="removeArticle($(this), ' . $article->id . ');">';
                    $html .= '<i class="fa fa-trash"></i>';
                    $html .= '</span>';
                    $html .= '</div>';
                    $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
                    $html .= '<div class="product_info"><strong>Prix unitaire TTC: </strong>' . BimpTools::displayMoneyValue($product->price_ttc, 'EUR') . '</div>';
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
                    $html .= '<div class="product_total_price">' . BimpTools::displayMoneyValue($product->price_ttc * $qty, 'EUR') . '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }
        }

        if (count($errors)) {
            return BimpRender::renderAlerts($errors);
        }
        return $html;
    }

    public function renderCartEquipementLine($id_equipment, &$errors)
    {
        $html = '';

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment', $id_equipment);
        if (!$equipment->isLoaded()) {
            $errors[] = 'Erreur: aucun enregistrement trouvé pour l\'équipement d\'ID ' . $id_equipment;
        } else {
            $id_product = (int) $equipment->getData('id_product');

            if (is_null($id_product) || !$id_product) {
                $errors[] = 'Erreur: aucun produit associé à l\'équipement ' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '")';
            } else {
                // todo: checker l'entrepot.
                // todo: checker les stocks. 

                $product = $equipment->getChildObject('product');
                if (is_null($product) || !isset($product->id) || !$product->id) {
                    $errors[] = 'Erreur: produit d\'ID ' . $equipment->getData('id_product') . ' non trouvé pour l\'équipement d\'ID' . $id_equipment . ' (n° série "' . $equipment->getData('serial') . '")';
                } else {
                    $article = BimpObject::getInstance($this->module, 'BC_VenteArticle');
                    $article_errors = $article->validateArray(array(
                        'id_vente'          => $this->id,
                        'id_product'        => $id_product,
                        'id_equipment'      => (int) $id_equipment,
                        'qty'               => 1,
                        'unit_price_tax_in' => (float) $product->price_ttc
                    ));

                    if (count($article_errors)) {
                        $errors = array_merge($errors, $article_errors);
                    } else {
                        $article_errors = $article->create();
                        if (count($article_errors)) {
                            $errors = array_merge($errors, $article_errors);
                        } else {
                            $html .= '<div id="cart_article_' . $article->id . '" class="cartArticleLine" data-id_article="' . $article->id . '">';
                            $html .= '<div class="product_title">' . $product->label;
                            $html .= '<span class="removeArticle" onclick="removeArticle($(this), ' . $article->id . ');">';
                            $html .= '<i class="fa fa-trash"></i>';
                            $html .= '</span>';
                            $html .= '</div>';
                            $html .= '<div class="product_info"><strong>Equipement ' . $id_equipment . ' - n° de série: ' . $equipment->getData('serial') . '</strong></div>';
                            $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
                            $html .= '<div class="article_options">';
                            $html .= '<div class="article_qty">&nbsp;';
                            $html .= '</div>';
                            $html .= '<div class="product_total_price">' . BimpTools::displayMoneyValue($product->price_ttc, 'EUR') . '</div>';
                            $html .= '</div>';
                            $html .= '</div>';
                        }
                    }
                }
            }
        }

        return $html;
    }

    public function renderCartProductLine($id_product, &$errors, $qty = 1)
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
                'unit_price_tax_in' => (float) $product->price_ttc
            ));

            if (count($article_errors)) {
                $errors = array_merge($errors, $article_errors);
            } else {
                $article_errors = $article->create();
                if (count($article_errors)) {
                    $errors = array_merge($errors, $article_errors);
                } else {
                    $html .= '<div id="cart_article_' . $article->id . '" class="cartArticleLine" data-id_article="' . $article->id . '">';
                    $html .= '<div class="product_title">' . $product->label;
                    $html .= '<span class="removeArticle" onclick="removeArticle($(this), ' . $article->id . ');">';
                    $html .= '<i class="fa fa-trash"></i>';
                    $html .= '</span>';
                    $html .= '</div>';
                    $html .= '<div class="product_info"><strong>Réf: </strong>' . $product->ref . '</div>';
                    $html .= '<div class="product_info"><strong>Prix unitaire TTC: </strong>' . BimpTools::displayMoneyValue($product->price_ttc, 'EUR') . '</div>';
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
                    $html .= '<div class="product_total_price">' . BimpTools::displayMoneyValue($product->price_ttc * $qty, 'EUR') . '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
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
                    $cart_html = $this->renderCartEquipementLine($equipements[0], $errors);
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

                if (count($products) > 1) {
                    if (count($products) > 6) {
                        $msg = 'Un trop grand nombre de produits ont été trouvés.<br/>Veuillez utiliser un terme de recherche plus précis.';
                        $result_html = BimpRender::renderAlerts($msg, 'warning');
                    } else {
                        $msg = count($products) . ' produits trouvés pour la recheche "' . $search . '"';
                        $result_html = BimpRender::renderAlerts($msg, 'info');

                        foreach ($products as $id_product) {
                            $result_html .= $this->renderSelectProductLine($id_product, $errors);
                        }
                    }
                } elseif (count($products)) {
                    if (array_key_exists((int) $products[0], $current_products)) {
                        $article = BimpObject::getInstance($this->module, 'BC_VenteArticle', (int) $current_products[(int) $products[0]]);
                        if ($article->isLoaded()) {
                            $qty = (int) $article->getData('qty');
                            $article->set('qty', ($qty + 1));
                            $errors = array_merge($errors, $article->update());
                        } else {
                            $errors[] = 'Un article a déjà été ajouté au panier pour ce code-barres mais n\'a pas pu être mis à jour';
                        }
                    }

                    $cart_html = $this->renderCartProductLine((int) $products[0], $errors);
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
                    $html = $this->renderCartEquipementLine($id_object, $errors);
                }
                break;

            case 'product':
                $current_products = $this->getCurrentProducts();

                if (array_key_exists((int) $id_object, $current_products)) {
                    $article = BimpObject::getInstance($this->module, 'BC_VenteArticle', (int) $current_products[$id_object]);
                    if ($article->isLoaded()) {
                        $qty = (int) $article->getData('qty');
                        $article->set('qty', ($qty + 1));
                        $errors = array_merge($errors, $article->update());
                    } else {
                        $errors[] = 'Un article a déjà été ajouté au panier pour ce code-barres mais n\'a pas pu être mis à jour';
                    }
                } else {
                    $html = $this->renderCartProductLine($id_object, $errors);
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

    public function validateVente()
    {
        $errors = array();
        if ($this->isLoaded()) {
            $this->set('status', 2);

            $caisse = $this->getChildObject('caisse');
            if (!is_null($caisse) && $caisse->isLoaded()) {
                $total_paid = 0;
                $total_paid_liq = 0;
                $total_ttc = 0;

                foreach ($this->getChildrenObjects('articles') as $article) {
                    $total_ttc += (float) $article->getTotal();
                }

                foreach ($this->getChildrenObjects('paiements') as $paiement) {
                    $montant = (float) $paiement->getData('montant');
                    $total_paid += $montant;
                    if ($paiement->getData('code') === 'LIQ') {
                        $total_paid_liq += $montant;
                    }
                }

                $fonds_diff = $total_paid_liq;
                $fonds_diff -= (float) ($total_paid - $total_ttc);

                if ($fonds_diff != 0) {
                    $fonds = (float) $caisse->getData('fonds');
                    $fonds += $fonds_diff;
                    $caisse->set('fonds', $fonds);
                    $errors = $caisse->update();
                }
            } else {
                $errors[] = 'Caisse assignée à cette vente invalide. Mise à jour du fonds de caisse impossible';
            }

            $errors = array_merge($errors, $this->update());
        }

        return $errors;
    }

    // Overrides

    public function update()
    {
        $total_ttc = 0;
        $articles = $this->getChildrenObjects('articles');
        foreach ($articles as $article) {
            $total_ttc += (float) $article->getTotal();
        }

        $this->set('total_ttc', $total_ttc);

        return parent::update();
    }
}

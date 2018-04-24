<?php
require_once DOL_DOCUMENT_ROOT."/bimpcore/Bimp_Lib.php";

class BS_SAV extends BimpObject
{

    public static $ref_model = 'SAV{CENTRE}{00000}';
    public static $propal_model_pdf = 'azurSAV';
    public static $facture_model_pdf = 'crabeSav';
    public static $idProdPrio = 3422;

    const BS_SAV_NEW = 0;
    const BS_SAV_ATT_PIECE = 1;
    const BS_SAV_ATT_CLIENT = 2;
    const BS_SAV_DEVIS_ACCEPTE = 3;
    const BS_SAV_REP_EN_COURS = 4;
    const BS_SAV_EXAM_EN_COURS = 5;
    const BS_SAV_DEVIS_REFUSE = 6;
    const BS_SAV_A_RESTITUER = 9;
    const BS_SAV_FERME = 999;
    
    
    public function __construct($db){
        parent::__construct("bimpsupport", get_class($this));
    }
    public function getNomUrl($withpicto = true){
        $statut = self::$status_list[$this->getData("status")];
        return "<a href='".$this->getInstanceUrl($this)."'>".'<span class="'.implode(" ", $statut['classes']).'"><i class="fa fa-'.$statut['icon'].' iconLeft"></i>'.$this->ref.'</span></a>';
    }

    public static $status_list = array(
        self::BS_SAV_NEW           => array('label' => 'Nouveau', 'icon' => 'file-o', 'classes' => array('info')),
        self::BS_SAV_ATT_PIECE     => array('label' => 'Attente pièce', 'icon' => 'hourglass-start', 'classes' => array('important')),
        self::BS_SAV_ATT_CLIENT    => array('label' => 'Attente client', 'icon' => 'hourglass-start', 'classes' => array('important')),
        self::BS_SAV_DEVIS_ACCEPTE => array('label' => 'Devis Accepté', 'icon' => 'check', 'classes' => array('success')),
        self::BS_SAV_REP_EN_COURS  => array('label' => 'Réparation en cours', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        self::BS_SAV_EXAM_EN_COURS => array('label' => 'Examen en cours', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        self::BS_SAV_DEVIS_REFUSE  => array('label' => 'Devis refusé', 'icon' => 'exclamation-circle', 'classes' => array('danger')),
        self::BS_SAV_A_RESTITUER   => array('label' => 'A restituer', 'icon' => 'arrow-right', 'classes' => array('success')),
        self::BS_SAV_FERME         => array('label' => 'Fermé', 'icon' => 'times', 'classes' => array('danger'))
    );
    public static $need_propal_status = array(2, 3, 4, 5, 6, 9);
    public static $cover_types = array(
        1 => 'Couvert',
        2 => 'Payant',
        3 => 'Non couvert'
    );
    public static $save_options = array(
        1 => 'Dispose d\'une sauvegarde',
        2 => 'Désire une sauvegarde si nécessaire',
        0 => 'Non applicable',
        3 => 'Dispose d\'une sauvegarde Time machine',
        4 => 'Ne dispose pas de sauvegarde et n\'en désire pas'
    );
    public static $contact_prefs = array(
        1 => 'E-mail',
        2 => 'Téléphone',
        3 => 'SMS'
    );
    public static $etats_materiel = array(
        1 => array('label' => 'Neuf', 'classes' => array('success')),
        2 => array('label' => 'Bon état général', 'classes' => array('info')),
        3 => array('label' => 'Usagé', 'classes' => array('warning'))
    );

    public function getClient_contactsArray()
    {
        $contacts = array();

        $id_client = (int) $this->getData('id_client');
        if ($id_client) {
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

    public function getContratsArray()
    {
        $contrats = array(
            0 => ''
        );

        $id_client = (int) $this->getData('id_client');
        if ($id_client) {
            $where = '`fk_soc` = ' . $id_client;
            $rows = $this->db->getRows('contrat', $where, null, 'array', array(
                'rowid', 'ref'
            ));

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contrats[(int) $r['rowid']] = $r['ref'];
                }
            }
        }

        return $contrats;
    }

    public function getPropalsArray()
    {
        $propals = array(
            0 => ''
        );
        $id_client = (int) $this->getData('id_client');

        if ($id_client) {
            $where = '`fk_soc` = ' . $id_client;
            $rows = $this->db->getRows('propal', $where, null, 'array', array(
                'rowid', 'ref'
            ));

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $propals[(int) $r['rowid']] = $r['ref'];
                }
            }
        }

        return $propals;
    }

    public function getViewExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $status = (int) $this->getData('status');
            $propal = null;
            $propal_status = null;

            if ((int) $this->getData('id_propal')) {
                $propal = $this->getChildObject('propal');
                if (!$propal->isLoaded()) {
                    unset($propal);
                    $propal = null;
                } else {
                    $propal_status = (int) $propal->getData('fk_statut');
                }
            }

            if (is_null($propal) && $status < 999) {
                $buttons[] = array(
                    'label'   => 'Créer Devis',
                    'icon'    => 'plus-circle',
                    'onclick' => 'createNewPropal($(this), ' . $this->id . ');'
                );
            }

            if (!is_null($propal) && $propal_status === 1 && !in_array($status, array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_DEVIS_REFUSE))) {
                $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_DEVIS_ACCEPTE . ', 0)';
                $buttons[] = array(
                    'label'   => 'Devis accepté',
                    'icon'    => 'check',
                    'onclick' => $onclick
                );

                $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_DEVIS_REFUSE . ', 0)';
                $buttons[] = array(
                    'label'   => 'Devis refusé',
                    'icon'    => 'times',
                    'onclick' => $onclick
                );
            }

            switch ($status) {
                case self::BS_SAV_NEW:
                    $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_EXAM_EN_COURS . ', 1)';
                    $buttons[] = array(
                        'label'   => 'Commencer diagnostic',
                        'icon'    => 'arrow-circle-right',
                        'onclick' => $onclick
                    );
                    break;

                case self::BS_SAV_ATT_PIECE:
                    $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_REP_EN_COURS . ', 1)';
                    $buttons[] = array(
                        'label'   => 'Pièce reçue',
                        'icon'    => 'check',
                        'onclick' => $onclick
                    );
                    break;

                case self::BS_SAV_ATT_CLIENT:
                    break;

                case self::BS_SAV_DEVIS_ACCEPTE:
                    if (!is_null($propal) && $propal_status > 0) {
                        $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_REP_EN_COURS . ', 0)';
                        $buttons[] = array(
                            'label'   => 'Réparation en cours',
                            'icon'    => 'wrench',
                            'onclick' => $onclick
                        );
                    }
                    break;

                case self::BS_SAV_REP_EN_COURS:
                    $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_A_RESTITUER . ', 1)';
                    if (!is_null($propal) && $propal_status > 1) {
                        $buttons[] = array(
                            'label'   => 'Réparation terminée',
                            'icon'    => 'check',
                            'onclick' => $onclick
                        );
                    }
                    break;

                case self::BS_SAV_EXAM_EN_COURS:
                    break;

                case self::BS_SAV_DEVIS_REFUSE:
                    if (!is_null($propal)) {
                        $frais = 0;
                        foreach ($propal->dol_object->lines as $line) {
                            if ($line->desc === 'Acompte') {
                                $frais = -$line->total_ttc;
                            }
                        }

                        $data = '{module: \'' . $this->module . '\', object_name: \'' . $this->object_name . '\', id_object: ' . $this->id . ', form_name: \'close\', frais: ' . $frais . '}';
                        $onclick = 'loadModalForm($(this), ' . $data . ', \'Fermeture\');';
                        $buttons[] = array(
                            'label'   => 'Fermer le SAV',
                            'icon'    => 'times-circle',
                            'onclick' => $onclick
                        );
                    }

                    $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_EXAM_EN_COURS . ', 0, {revision: 1})';
                    $buttons[] = array(
                        'label'   => 'Réviser le devis',
                        'icon'    => 'edit',
                        'onclick' => $onclick
                    );
                    break;

                case self::BS_SAV_A_RESTITUER:
                    if (!is_null($propal)) {
                        $data = '{module: \'' . $this->module . '\', object_name: \'' . $this->object_name . '\', id_object: ' . $this->id . ', form_name: \'restitute\'}';
                        $onclick = 'loadModalForm($(this), ' . $data . ', \'Restituer\')';
                        $buttons[] = array(
                            'label'   => 'Restituer (Payer)',
                            'icon'    => 'times-circle',
                            'onclick' => $onclick
                        );
                    }
                    break;

                case self::BS_SAV_FERME:
                    break;
            }

            if (is_null($propal) && $status !== self::BS_SAV_FERME) {
                $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_FERME . ', 1, {restitute: 1})';
                $buttons[] = array(
                    'label'   => 'Restituer',
                    'icon'    => 'times-circle',
                    'onclick' => ' '
                );
            }

            if (!is_null($propal) && $propal_status === 0) {
                $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_ATT_CLIENT . ', 1)';
                $buttons[] = array(
                    'label'   => 'Envoyer devis',
                    'icon'    => 'arrow-circle-right',
                    'onclick' => $onclick
                );
            }

            if (!is_null($propal) && in_array($propal_status, array(0, 1)) && $status !== self::BS_SAV_ATT_CLIENT) {
                $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_ATT_CLIENT . ', 1, {devis_garantie: 1})';
                $buttons[] = array(
                    'label'   => 'Devis garantie',
                    'icon'    => 'file-text',
                    'onclick' => $onclick
                );
            }
        }

        $object_data = '{module: \'' . $this->module . '\', object_name: \'' . $this->object_name . '\', id_object: \'' . $this->id . '\'}';
        $onclick = 'setObjectAction($(this), '.$object_data.', \'testAction\', {test: 1}, \'restitute\')';
        $buttons[] = array(
            'label'   => 'Test action',
            'icon'    => 'file-text',
            'onclick' => $onclick
        );

        return $buttons;
    }

    public function getClientExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $data = '{module: \'' . $this->module . '\', object_name: \'' . $this->object_name . '\', id_object: ' . $this->id . ', form_name: \'contact\'}';
            $onclick = 'loadModalForm($(this), ' . $data . ', \'Recontacter\');';
            $buttons[] = array(
                'label'   => 'Recontacter',
                'icon'    => 'envelope',
                'onclick' => $onclick
            );
        }

        return $buttons;
    }

    public function getInfosExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $onclick = 'generatePDFFile($(this), ' . $this->id . ', \'pc\');';
            $buttons[] = array(
                'label'   => 'Générer Bon de prise en charge',
                'icon'    => 'fas_file-pdf',
                'onclick' => $onclick
            );

            $onclick = 'generatePDFFile($(this), ' . $this->id . ', \'pret\');';
            $buttons[] = array(
                'label'   => 'Générer Bon de prêt',
                'icon'    => 'fas_file-pdf',
                'onclick' => $onclick
            );

            $onclick = 'generatePDFFile($(this), ' . $this->id . ', \'destruction\');';
            $buttons[] = array(
                'label'   => 'Générer Bon de destruction client',
                'icon'    => 'fas_file-pdf',
                'onclick' => $onclick
            );

            $onclick = 'generatePDFFile($(this), ' . $this->id . ', \'destruction2\');';
            $buttons[] = array(
                'label'   => 'Générer Bon de destruction tribunal',
                'icon'    => 'fas_file-pdf',
                'onclick' => $onclick
            );
        }

        return $buttons;
    }

    public function getCodeEntrepot()
    {
        $id_entrepot = (int) $this->getData('id_entrepot');
        if (!$id_entrepot) {
            $id_entrepot = (int) BimpTools::getValue('id_entrepot', 0);
        }

        $code_entrepot = '';
        if ($id_entrepot) {
            $code_entrepot = $this->db->getValue('entrepot', 'label', '`rowid` = ' . (int) $id_entrepot);
            if ($code_entrepot) {
                $code_entrepot = preg_replace('/^SAV(.+)$/', '$1', $code_entrepot);

                if ($code_entrepot === 'CF') {
                    $code_entrepot = 'CFC';
                }
            }
        }

        return $code_entrepot;
    }

    public function getNomMachine()
    {
        if ($this->isLoaded()) {
            $equipment = $this->getChildObject('equipment');
            if (!is_null($equipment) && $equipment->isLoaded()) {
                return $equipment->displayProduct('default', true);
            }
        }

        return '';
    }

    public function getFactureAmountToPay()
    {
        if ((int) $this->getData('id_facture')) {
            $facture = $this->getChildObject('facture');
            if (!is_null($facture) && isset($facture->id) && $facture->id) {
                return (float) ($facture->total_ttc - $facture->getSommePaiement());
            }
        }

        if ((int) $this->getData('id_propal')) {
            $propal = $this->getChildObject('propal');
            if (!is_null($propal) && $propal->isLoaded()) {
                return (float) $propal->dol_object->total_ttc;
            }
        }

        return 0;
    }

    public function getListFilters()
    {
        $filters = array();
        if (BimpTools::isSubmit('id_entrepot')) {
            $entrepots = explode('-', BimpTools::getValue('id_entrepot'));

            $filters[] = array('name'   => 'id_entrepot', 'filter' => array(
                    'IN' => implode(',', $entrepots)
            ));
        }

        if (BimpTools::isSubmit('status')) {
            $filters[] = array('name' => 'status', 'filter' => (int) BimpTools::getValue('status'));
        }

        return $filters;
    }

    public function displayStatusWithActions()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $html .= '<div style="font-size: 15px">';
        $html .= $this->displayData('status');
        $html .= '</div>';

        $buttons = $this->getViewExtraBtn();

        if (count($buttons)) {
            $html .= '<div style="text-align: right; margin-top: 5px">';
            foreach ($buttons as $button) {
                $html .= '<div style="display: inline-block; margin: 2px;">';
                $html .= '</div>';
                $html .= BimpRender::renderButton(array(
                            'classes'     => array('btn', 'btn-default'),
                            'label'       => $button['label'],
                            'icon_before' => $button['icon'],
                            'attr'        => array(
                                'type'    => 'button',
                                'onclick' => $button['onclick']
                            )
                                ), 'button');
            }
            $html .= '</div>';
        }

        return $html;
    }

    public function displayPropal()
    {
        if (!$this->isLoaded()) {
            return '';
        }


        $id_propal = (int) $this->getData('id_propal');
        if ($id_propal) {
            $field = new BC_Field($this, 'id_propal');
            $field->display_name = 'card';
            return $field->renderHtml();
        }

        if ((int) $this->getData('status') !== 999) {
            $onclick = 'createNewPropal($(this), ' . $this->id . ');';
            return '<button type="button" class="btn btn-default" onclick="' . $onclick . '"><i class="fa fa-plus-circle iconLeft"></i>Créer une nouvelle proposition comm.</button>';
        }

        return '';
    }

    public function defaultDisplayEquipmentsItem($id_equipment)
    {
        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        if ($equipment->fetch($id_equipment)) {
            $label = '';
            if ((int) $equipment->getData('id_product')) {
                $product = $equipment->config->getObject('', 'product');
                if (!is_null($product) && isset($product->id) && $product->id) {
                    $label = $product->label;
                } else {
                    return BimpRender::renderAlerts('Equipement ' . $id_equipment . ': Produit associé non trouvé');
                }
            } else {
                $label = $equipment->getData('product_label');
            }

            $label .= ' - N° série: ' . $equipment->getData('serial');

            return $label;
        }
        return BimpRender::renderAlerts('Equipement non trouvé (ID ' . $id_equipment . ')', 'warning');
    }

    public function createPropal()
    {
        if (!$this->isLoaded()) {
            return array(
                'ID du ticket absent ou invalide'
            );
        }
        $errors = array();

        $id_client = (int) $this->getData('id_client');
        if (!$id_client) {
            $errors[] = 'Aucun client sélectionné pour ce ticket';
        }

        $id_contact = (int) $this->getData('id_contact');

        if (!count($errors)) {
            global $user, $langs;

            $repDest = DOL_DATA_ROOT . "/bimpcore/sav/" . $this->id . "/";

            if (!is_dir($repDest)) {
                mkdir($repDest);
            }

            BimpTools::loadDolClass('comm/propal', 'propal');
            $prop = new Propal($this->db->db);
            $prop->modelpdf = "azurSAV";
            $prop->socid = $id_client;
            $prop->date = dol_now();
            $prop->cond_reglement_id = 0;
            $prop->mode_reglement_id = 0;

            if ($prop->create($user) <= 0) {
                $errors[] = 'Echec de la création de la propale';
                BimpTools::getErrorsFromDolObject($prop, $errors, $langs);
            } else {
                if ($id_contact) {
                    $prop->add_contact($id_contact, 40);
                    $prop->add_contact($id_contact, 41);
                }

                $ref = $this->getData('ref');
                $equipment = $this->getChildObject('equipment');
                $serial = 'N/C';
                if (!is_null($equipment) && $equipment->isLoaded()) {
                    $serial = $equipment->getData('serial');
                }

                $client = $this->getChildObject('client');
                if (!is_null($client) && !$client->isLoaded()) {
                    $client = null;
                }

                $prop->addline("Prise en charge :  : " . $ref .
                        "\n" . "S/N : " . $serial .
                        "\n" . "Garantie :
Pour du matériel couvert par Apple, la garantie initiale s'applique.
Pour du matériel non couvert par Apple, la garantie est de 3 mois pour les pièces et la main d'oeuvre.
Les pannes logicielles ne sont pas couvertes par la garantie du fabricant.
Une garantie de 30 jours est appliquée pour les réparations logicielles.
", 0, 1, 0, 0, 0, 0, (!is_null($client) ? $client->dol_object->remise_percent : 0), 'HT', 0, 0, 3);

                $this->set('id_propal', $prop->id);

                // Prise en compte de l'acompte
                $acompte = (int) BimpTools::getValue('acompte', 0);
                if ($acompte > 0) {
                    BimpTools::loadDolClass('compta/facture', 'facture');
                    $factureA = new Facture($this->db->db);
                    $factureA->type = 3;
                    $factureA->date = dol_now();
                    $factureA->socid = $this->getData('id_client');
                    $factureA->modelpdf = self::$facture_model_pdf;
                    $factureA->array_options['options_type'] = "S";
                    $factureA->create($user);
                    $factureA->addline("Acompte", $acompte / 1.2, 1, 20, null, null, null, 0, null, null, null, null, null, 'HT', null, 1, null, null, null, null, null, null, $acompte / 1.2);
                    $factureA->validate($user);
                    addElementElement("propal", "facture", $prop->id, $factureA->id);

                    BimpTools::loadDolClass('compta/paiement', 'paiement');
                    $payement = new Paiement($this->db->db);
                    $payement->amounts = array($factureA->id => $acompte);
                    $payement->datepaye = dol_now();
                    $payement->paiementid = (int) BimpTools::getValue('mode_paiement_acompte', 0);
                    $payement->create($user);

                    $factureA->set_paid($user);

                    include_once(DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php');
                    $factureA->generateDocument(self::$facture_model_pdf, $langs);
                    $this->set('id_facture_acompte', $factureA->id);

//                    link(DOL_DATA_ROOT . "/facture/" . $factureA->ref . "/" . $factureA->ref . ".pdf", $repDest . $factureA->ref . ".pdf");

                    BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                    $discount = new DiscountAbsolute($this->db->db);
                    $discount->description = "Acompte";
                    $discount->fk_soc = $factureA->socid;
                    $discount->fk_facture_source = $factureA->id;
                    $discount->amount_ht = $acompte / 1.2;
                    $discount->amount_ttc = $acompte;
                    $discount->amount_tva = $acompte - ($acompte / 1.2);
                    $discount->tva_tx = 20;
                    $discount->create($user);

                    $prop->addline("Acompte", -$discount->amount_ht, 1, 20, 0, 0, 0, 0, 'HT', -$acompte, 0, 1, 0, 0, 0, 0, -$discount->amount_ht, null, null, null, null, null, null, null, null, $discount->id);
                }

                // Ajout du service prioritaire:
                if ((int) $this->getData('prioritaire')) {
                    require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
                    $prodF = new ProductFournisseur($this->db->db);
                    $prodF->fetch(self::$idProdPrio);
                    $prodF->tva_tx = ($prodF->tva_tx > 0) ? $prodF->tva_tx : 0;
                    $prodF->find_min_price_product_fournisseur($prodF->id, 1);

                    $prop->addline($prodF->description, $prodF->price, 1, $prodF->tva_tx, 0, 0, self::$idProdPrio, 0, 'HT', null, null, null, null, null, null, $prodF->product_fourn_price_id, $prodF->fourn_price);
                }

                $prop->valid($user);
                $prop->set_draft($user);
                $prop->fetch($prop->id);

                require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
                $prop->generateDocument(self::$propal_model_pdf, $langs);
//                link(DOL_DATA_ROOT . "/propale/" . $prop->ref . "/" . $prop->ref . ".pdf", $repDest . $prop->ref . ".pdf");
//                require_once DOL_DOCUMENT_ROOT . "/bimpsupport/core/modules/bimpsupport/modules_bimpsupport.php";
//                bimpsupport_pdf_create($db, $this, "pc");
            }
            $this->update();
        }

        return $errors;
    }

    protected function onNewStatus(&$new_status, $current_status, $extra_data)
    {

        $errors = array();

        $propal = $this->getChildObject('propal');
        $propal_status = null;

        if (!$propal->isLoaded()) {
            unset($propal);
            $propal = null;
        } else {
            $propal_status = (int) $propal->getData('fk_statut');
        }

        if (!isset($extra_data['send_msg'])) {
            $extra_data['send_msg'] = 0;
        }

        $error_msg = 'Ce SAV ne peut pas être mis au statut "' . self::$status_list[$new_status]['label'] . '"';

        $client = $this->getChildObject('client');
        if (is_null($client) || !$client->isLoaded()) {
            return array($error_msg . ' (Client absent ou invalide)');
        }

        if (is_null($propal) && in_array($new_status, self::$need_propal_status)) {
            return array($error_msg . ' (Proposition commerciale absente)');
        }

//        const BS_SAV_NEW = 0;
//        const BS_SAV_ATT_PIECE = 1;
//        const BS_SAV_ATT_CLIENT = 2;
//        const BS_SAV_DEVIS_ACCEPTE = 3;
//        const BS_SAV_REP_EN_COURS = 4;
//        const BS_SAV_EXAM_EN_COURS = 5;
//        const BS_SAV_DEVIS_REFUSE = 6;
//        const BS_SAV_A_RESTITUER = 9;
//        const BS_SAV_FERME = 999;

        global $user, $langs;

        $msg_type = '';

        switch ($new_status) {
            case self::BS_SAV_ATT_CLIENT:
                if (is_null($propal)) {
                    $errors[] = $error_msg . ' (Proposition commerciale absente)';
                } elseif ($propal_status !== 0) {
                    $errors[] = $error_msg . ' (statut de la proposition commerciale invalide)';
                } elseif (!(string) $this->getData('diagnostic')) {
                    $errors[] = $error_msg . '. Le champ "Diagnostic" doit être complété';
                } elseif (in_array($current_status, array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_FERME))) {
                    $errors[] = $errors[] = $error_msg . ' (statut actuel invalide)';
                } else {
                    $this->addNote('Devis validé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    $propal->dol_object->addline("Diagnostic : " . $this->getData('diagnostic'), 0, 1, 0, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 3);

                    if (isset($extra_data['devis_garantie']) && $extra_data['devis_garantie']) {
                        $totPa = 0;
                        $totHt = 0;
                        $totTtc = 0;
                        foreach ($propal->dol_object->lines as $ligne) {
                            if ($ligne->desc != "Acompte" && $ligne->ref != "SAV-PCU") {
                                $totHt += $ligne->total_ht;
                                $totTtc += $ligne->total_ttc;
                                $totPa += $ligne->pa_ht;
                            }
                        }

                        if ($propal->dol_object->statut === 1) {
                            $propal->dol_object->statut = 0;
                        }

                        $propal->dol_object->addline("Garantie", -($totHt) / (100 - $client->dol_object->remise_percent) * 100, 1, (($totTtc / ($totHt != 0 ? $totHt : 1) - 1) * 100), 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 1, -1, 0, 0, 0, -$totPa);

                        // Si on vient de commander les pieces sous garentie (On ne change pas le statut)
                        if ($current_status === self::BS_SAV_ATT_PIECE) {
                            $new_status = self::BS_SAV_ATT_PIECE;
                        } else {
                            $new_status = self::BS_SAV_DEVIS_ACCEPTE;
                        }

                        $propal->dol_object->valid($user);
                        $propal->dol_object->cloture($user, 2, "Auto via SAV sous garentie");
                        $propal->fetch($propal->id);
                        $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
                    } else {
                        $propal->dol_object->valid($user);
                        $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
                        $msg_type = 'Devis';
                    }
                }
                break;

            case self::BS_SAV_DEVIS_ACCEPTE:
                if ($propal_status !== 1) {
                    $errors[] = $error_msg . ' (statut de la proposition commerciale invalide)';
                } elseif (!in_array($current_status, array(0, 1, 2, 5))) {
                    $errors[] = $error_msg . ' (statut actuel invalide)';
                } else {
                    $this->addNote('Devis accepté le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    $propal->dol_object->cloture($user, 2, "Auto via SAV");
                }
                break;

            case self::BS_SAV_DEVIS_REFUSE:
                if ($propal_status !== 1) {
                    $errors[] = $error_msg . ' (statut de la proposition commerciale invalide)';
                } elseif (!in_array($current_status, array(0, 1, 2, 5))) {
                    $errors[] = $error_msg . ' (statut actuel invalide)';
                } else {
                    $this->addNote('Devis refusé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    $propal->dol_object->cloture($user, 3, "Auto via SAV");
                    $msg_type = 'commercialRefuse';
                }

                // todo: 
//                if ($chrono->extraValue[$chrono->id]['Technicien']['value'] > 0) {
//                    $req = "SELECT `nom` FROM `" . MAIN_DB_PREFIX . "usergroup` WHERE rowid IN (SELECT `fk_usergroup` FROM `" . MAIN_DB_PREFIX . "usergroup_user` WHERE `fk_user` = " . $chrono->extraValue[$chrono->id]['Technicien']['value'] . ") ANd `nom` REGEXP 'Sav([0-9])'";
//                    $sql = $db->query($req);
//                    while ($ln = $db->fetch_object($sql)) {
//                        $toMail = str_ireplace("Sav", "Boutique", $ln->nom) . "@bimp.fr";
//                        envoieMail("commercialRefuse", $chrono, null, $toMail, $fromMail, $tel, $nomMachine, $nomCentre);
//                    }
//                }
                break;

            case self::BS_SAV_EXAM_EN_COURS:
                if (!in_array($current_status, array(0, 1, 2, 3, 6))) {
                    $errors[] = $error_msg . ' (statut actuel invalide)';
                } else {
                    if (isset($extra_data['revision']) && (int) $extra_data['revision']) {
                        if ($current_status !== self::BS_SAV_DEVIS_REFUSE) {
                            $errors[] = $error_msg . ' (statut actuel invalide)';
                        } elseif (is_null($propal)) {
                            $errors[] = $error_msg . ' (Proposition commerciale absente)';
                        } else {
                            require_once(DOL_DOCUMENT_ROOT . "/bimpcore/classes/BimpRevision.php");

                            $old_id_propal = $propal->id;
                            $revision = new BimpRevisionPropal($propal->dol_object);
                            $new_id_propal = $revision->reviserPropal(array(array('Diagnostic'), null), true, self::$propal_model_pdf, $errors);

                            $this->addNote('Proposition commerciale mise en révision le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                            if ($new_id_propal && !count($errors)) {
                                //Anulation du montant de la propal
                                $totHt = 0;
                                if ($totHt == 0)
                                    $tTva = 0;
                                else {
                                    $tTva = (($totTtc / ($totHt != 0 ? $totHt : 1) - 1) * 100);
                                }
                                $propal->fetch($old_id_propal);
                                $propal->dol_object->statut = 0;
                                $propal->dol_object->addline("Devis refusé", -($totHt) / (100 - $client->dol_object->remise_percent) * 100, 1, $tTva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 1, -1, 0, 0, 0, -$totPa);

                                $this->set('id_propal', $new_id_propal);
                                $propal->fetch($new_id_propal);
                            } else {
                                $errors[] = 'Echec de la révision de la proposition commerciale';
                            }
                        }
                    } else {
                        $this->addNote('Diagnostic commencé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                        $this->set('id_user_tech', (int) $user->id);
                        $msg_type = 'debDiago';
                    }
                }
                break;

            case self::BS_SAV_REP_EN_COURS:
                if (!in_array($current_status, array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_ATT_PIECE))) {
                    $errors[] = $error_msg . ' (Statut actuel invalide)';
                } else {
                    if ($current_status === self::BS_SAV_ATT_PIECE) {
                        $this->addNote('Pièce reçue le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                        $msg_type = 'pieceOk';
                    } else {
                        $this->addNote('Réparation en cours depuis le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    }
                }
                break;

            case self::BS_SAV_A_RESTITUER:
                if (isset($extra_data['close']) && (int) $extra_data['close']) {
                    if (is_null($propal)) {
                        $errors[] = $error_msg . ' (Proposition commerciale absente)';
                    } elseif ($current_status !== self::BS_SAV_DEVIS_REFUSE) {
                        $errors[] = $error_msg . ' (statut actuel invalide)';
                    } else {
                        require_once(DOL_DOCUMENT_ROOT . "/bimpcore/classes/BimpRevision.php");

                        $old_id_propal = $propal->id;
                        $revision = new BimpRevisionPropal($propal->dol_object);
                        $new_id_propal = $revision->reviserPropal(array(null, null), true, self::$propal_model_pdf, $errors);

                        $this->addNote('Devis refusé après fermeture le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));

                        if ($new_id_propal && !count($errors)) {
                            //Anulation du montant de la propal
                            $totHt = 0;
                            if ($totHt == 0)
                                $tTva = 0;
                            else {
                                $tTva = (($totTtc / ($totHt != 0 ? $totHt : 1) - 1) * 100);
                            }
                            $propal->fetch($old_id_propal);

                            $propal->dol_object->statut = 0;
                            $propal->dol_object->addline("Devis refusé", -($totHt) / (100 - $client->dol_object->remise_percent) * 100, 1, $tTva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 1, -1, 0, 0, 0, -$totPa);

                            $this->set('id_propal', $new_id_propal);
                            $propal->fetch($new_id_propal);

                            $frais = (float) BimpTools::getValue('frais', 0);
                            $propal->dol_object->addline(/* "Prise en charge :  : " . $chrono->ref . */
                                    "Machine(s) : " . $this->getNomMachine() .
                                    "\n" . "Frais de gestion devis refusé.", $frais / 1.20, 1, 20, 0, 0, 3470, $client->dol_object->remise_percent, 'HT', null, null, 1);

                            $propal->fetch($propal->id);
                            $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
                            $propal->dol_object->cloture($user, 2, "Auto via SAV");
                            $msg_type = 'revPropRefu';
                        } else {
                            $errors[] = 'Echec de la fermeture de la proposition commerciale';
                        }
                    }
                } else {
                    if ($propal_status <= 1) {
                        $errors[] = $error_msg . ' (statut de la proposition commerciale invalide)';
                    } elseif ($current_status !== self::BS_SAV_REP_EN_COURS) {
                        $errors[] = $error_msg . ' (statut actuel invalide)';
                    } elseif (!(string) $this->getData('resolution')) {
                        $errors[] = $error_msg . '. Le champ "résolution" doit être complété';
                    } else {
                        $this->addNote('Réparation terminée le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                        $propal->dol_object->cloture($user, 2, "Auto via SAV");
                        $msg_type = 'repOk';

                        // todo: 
//                    $sql = $db->query("SELECT rowid FROM `" . MAIN_DB_PREFIX . "synopsis_apple_repair` WHERE `chronoId` = " . $chrono->id . " AND ready_for_pick_up = 0 ORDER BY `rowid` ASC");
//                    if ($db->num_rows($sql) > 0) {
//                        $data = $db->fetch_object($sql);
//                        $gsxData = new gsxDatas("");
//                        $gsxData->endRepair($data->rowid);
//                    }
                        // Désactivé:
                        //        $propal->cloture($user, 3, '');
                    }
                }
                break;

            case self::BS_SAV_FERME:
                if (!is_null($propal)) {
                    if (!isset($extra_data['restitute']) || !$extra_data['restitute']) {
                        $errors[] = 'Vous devez passer par la fonction "Restituer" pour fermer ce SAV';
                    }
                    if ($propal->dol_object->total_ttc > 0) {
                        if (!isset($extra_data['mode_paiement'])) {
                            $errors[] = 'Attention, ' . price($propal->dol_object->total_ttc) . ' &euro; à payer, merci de sélectionner le moyen de paiement';
                        }
                    }

                    if (!count($errors)) {
                        BimpTools::loadDolClass('compta/facture', 'facture');
                        $facture = new Facture($this->db->db);
                        $facture->modelpdf = self::$facture_model_pdf;
                        $facture->array_options['options_type'] = "S";
                        $facture->createFromOrder($propal->dol_object);
                        $facture->addline("Résolution : " . $this->getData('resolution'), 0, 1, 0, 0, 0, 0, 0, null, null, null, null, null, 'HT', 0, 3);
                        $facture->validate($user, '', (int) $this->getData('id_entrepot'));
                        $facture->fetch($facture->id);

                        if (isset($extra_data['paid']) && (float) $extra_data['paid'] && (isset($extra_data['mode_paiement']) && (int) $extra_data['mode_paiement'] > 0 && (int) $extra_data['mode_paiement'] != 56)) {
                            require_once(DOL_DOCUMENT_ROOT . "/compta/paiement/class/paiement.class.php");
                            $payement = new Paiement($this->db->db);
                            $payement->amounts = array($facture->id => (float) $extra_data['paid']);
                            $payement->datepaye = dol_now();
                            $payement->paiementid = (int) $extra_data['mode_paiement'];
                            $payement->create($user);
                        }

                        if ((float) $facture->getSommePaiement() >= (float) $facture->total_ttc) {
                            $facture->set_paid($user);
                        }

                        $propal->dol_object->cloture($user, 4, "Auto via SAV");

                        //Generation
                        $facture->fetch($facture->id);
                        $facture->generateDocument(self::$facture_model_pdf, $langs);
                        $this->set('id_facture', $facture->id);
                        $msg_type = 'Facture';

                        link(DOL_DATA_ROOT . "/facture/" . $facture->ref . "/" . $facture->ref . ".pdf", DOL_DATA_ROOT . "/bimpcore/sav/" . $this->id . "/" . $facture->ref . ".pdf");
                    }
                }

                if (!count($errors)) {
                    if (isset($extra_data['restitute']) && (int) $extra_data['restitute']) {
                        $this->addNote('Restitué le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    } else {
                        $this->addNote('Fermé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    }
                }

                // Todo: 
//                $sql = $db->query("SELECT rowid FROM `" . MAIN_DB_PREFIX . "synopsis_apple_repair` WHERE `chronoId` = " . $chrono->id . " AND ready_for_pick_up = 1 ORDER BY `rowid` ASC");
//                if ($db->num_rows($sql) > 0) {
//                    $data = $db->fetch_object($sql);
//                    $gsxData = new gsxDatas("");
//                    $gsxData->closeRepair($data->rowid);
//                }
                break;
        }

        if (!count($errors)) {
            if ($extra_data['send_msg'] || $msg_type === 'commercialRefuse') {
                // todo: à retourner via $this->warnings. 
                $warnings = $this->sendMsg($msg_type);
            }
        }

        return $errors;
    }

    // Action: 
    
    // Overrides: 
    
    public function create()
    {
        if($this->getData('ref') == '')
            $this->data['ref'] = $this->getNextNumRef();
        $errors = parent::create();

        if (!count($errors) && $this->getData("id_propal") < 1) {
            $this->createPropal();
        }
    }

    public function update()
    {
        $errors = array();

        if ((int) BimpTools::getValue('restitute', 0)) {
            $_POST['restitute'] = 0;
            $_GET['restitute'] = 0;
            $_REQUEST['restitute'] = 0;

            $extra_data = array(
                'restitute'     => 1,
                'paid'          => BimpTools::getValue('paid', 0),
                'mode_paiement' => BimpTools::getValue('mode_paiement', 0)
            );

            return $this->setNewStatus(self::BS_SAV_FERME, $extra_data);
        }

        if ((int) BimpTools::getValue('close', 0)) {
            $_POST['close'] = 0;
            $_GET['close'] = 0;
            $_REQUEST['close'] = 0;

            $extra_data = array(
                'close'    => 1,
                'send_msg' => BimpTools::getValue('send_msg', 0)
            );

            return $this->setNewStatus(self::BS_SAV_A_RESTITUER, $extra_data);
        }

        if ((int) BimpTools::getValue('recontact', 0)) {
            $msg_type = BimpTools::getValue('msg_type', '');
            if ($msg_type) {
                $errors = $this->sendMsg($msg_type);
            } else {
                $errors[] = 'Aucun type de notification sélectionné';
            }
            return $errors;
        }
        if (!count($errors)) {
            $errors = parent::update();
        }

        return $errors;
    }

    public function sendMsg($msg_type = '')
    {
        global $langs, $tabCentre;

        $errors = array();
        $error_msg = 'Echec de l\'envoi de la notification au client';

        $nbJours = BimpTools::getValue('nbjours', 0);
        $delai = ($nbJours > 0 ? "dans " . $nbJours . " jours" : "dès maintenant");

        $client = $this->getChildObject('client');
        if (is_null($client) || !$client->isLoaded()) {
            return array($error_msg . ' (ID du client absent)');
        }

//    $signature = '<div id="signature_Bimp"><div style="font-family: Arial, sans-serif; font-size: 13px;"><div style="margin: 0 0 8px 0;"><table border="0"><tbody><tr valign="middle"><td><a href="http://www.bimp.fr/" target="_blank"><img alt="" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/bimpcomputer.png"></a></td><td style="text-align: left;"><span style="font-size: large;"><span style="color: #181818;"><strong class="text-color theme-font">BIMP SAV</strong><span style="color: #181818;"> </span></span></span><br><div style="margin-bottom: 0px; margin-top: 8px;"><span style="font-size: medium;"><span style="color: #cb6c09;"></span></span></div><div style="margin-bottom: 0px; margin-top: 0px;"><span style="font-size: medium;"><span style="color: #808080;">Centre de Services Agrées Apple</span></span></div><div style="color: #828282; font: 13px Arial; margin-top: 10px; text-transform: none;"><a href="https://plus.google.com/+BimpFr/posts" style="text-decoration: underline;"><img alt="Google Plus Page" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/googlepluspage.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://twitter.com/BimpComputer" style="text-decoration: underline;"><img alt="Twitter" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/twitter.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://www.linkedin.com/company/bimp" style="text-decoration: underline;"><img alt="LinkedIn" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/linkedin.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="http://www.viadeo.com/fr/company/bimp" style="text-decoration: underline;"><img alt="Viadeo" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/viadeo.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://www.facebook.com/bimpcomputer" style="text-decoration: underline;"><img alt="Facebook" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/facebook.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a><br></div></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td><a href="http://bit.ly/1MsmSB8"><img moz-do-not-send="true" src="http://www.bimp.fr/emailing/signatures/evenementiel2.png" alt=""></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td><img alt="" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/pictoarbre.png"></td><td style="text-align: left;"><span style="font-size: small;"><span style="color: #009933;"> Merci de n\'imprimer cet e-mail que si nécessaire</span></span></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td style="text-align: justify;"><span style="font-size: small;"><span style="color: #888888;"><small>Ce message et éventuellement les pièces jointes, sont exclusivement transmis à l\'usage de leur destinataire et leur contenu est strictement confidentiel. Une quelconque copie, retransmission, diffusion ou autre usage, ainsi que toute utilisation par des personnes physiques ou morales ou entités autres que le destinataire sont formellement interdits. Si vous recevez ce message par erreur, merci de le détruire et d\'en avertir immédiatement l\'expéditeur. L\'Internet ne permettant pas d\'assurer l\'intégrité de ce message, l\'expéditeur décline toute responsabilité au cas où il aurait été intercepté ou modifié par quiconque.<br> This electronic message and possibly any attachment are transmitted for the exclusive use of their addressee; their content is strictly confidential. Any copy, forward, release or any other use, is prohibited, as well as any use by any unauthorized individual or legal entity. Should you receive this message by mistake, please delete it and notify the sender at once. Because of the nature of the Internet the sender is not in a position to ensure the integrity of this message, therefore the sender disclaims any liability whatsoever, in the event of this message having been intercepted and/or altered.</small></span> </span></td></tr></tbody></table></div></div></div>';
        $signature = file_get_contents("http://bimp.fr/emailing/signatures/signevenementiel2.php?prenomnom=BIMP%20SAV&adresse=Centre%20de%20Services%20Agr%C3%A9%C3%A9%20Apple");

        $propal = $this->getChildObject('propal');

        $tabFile = $tabFile2 = $tabFile3 = array();

        if (!is_null($propal)) {
            if ($propal->isLoaded()) {
                $fileProp = DOL_DATA_ROOT . "/bimpcore/sav/" . $this->id . "/PC-" . $this->getData('ref') . ".pdf";
                if (is_file($fileProp)) {
                    $tabFile[] = $fileProp;
                    $tabFile2[] = ".pdf";
                    $tabFile3[] = "PC-" . $this->getData('ref') . ".pdf";
                }

                $fileProp = DOL_DATA_ROOT . "/propale/" . $propal->dol_object->ref . "/" . $propal->dol_object->ref . ".pdf";
                if (is_file($fileProp)) {
                    $tabFile[] = $fileProp;
                    $tabFile2[] = ".pdf";
                    $tabFile3[] = $propal->dol_object->ref . ".pdf";

//                    echo '<pre>';
//                    print_r($tabFileProp);
//                    print_r($tabFileProp2);
//                    print_r($tabFileProp3);
//                    exit;
                }
            } else {
                unset($propal);
                $propal = null;
            }
        }

        $tech = '';
        $user_tech = $this->getChildObject('user_tech');
        if (!is_null($user_tech) && $user_tech->isLoaded()) {
            $tech = $user_tech->dol_object->getFullName($langs);
        }

        // todo: page à refaire: 
//        $textSuivie = "\n <a href='" . DOL_MAIN_URL_ROOT . "/synopsis_chrono_public/page.php?back_serial=" . $chrono->id . "&user_name=" . substr($chrono->societe->name, 0, 3) . "'>Vous pouvez suivre l'intervention ici.</a>";

        $textSuivie = '';

        if (!$msg_type) {
            if (BimpTools::isSubmit('msg_type')) {
                $msg_type = BimpTools::getValue('msg_type');
            } else {
                return array($error_msg . ' (Type de message absent)');
            }
        }

        $subject = '';
        $mail_msg = '';
        $sms = '';
        $nomMachine = $this->getNomMachine();
        $tabFileFact = $tabFileFact2 = $tabFileFact3 = array();
        $nomCentre = 'N/C';
        $tel = 'N/C';
        $fromMail = "SAV BIMP<no-replay@bimp.fr>";

        $code_entrepot = $this->getCodeEntrepot();
        if ($code_entrepot) {
            if (isset($tabCentre[$code_entrepot])) {
                $nomCentre = $tabCentre[$code_entrepot][2];
                $tel = $tabCentre[$code_entrepot][0];
                $fromMail = "SAV BIMP<" . $tabCentre[$code_entrepot][1] . ">";
            }
        }

        switch ($msg_type) {
            case 'Facture':
                $facture = null;
                $tabFile = $tabFile2 = $tabFile3 = array();
                if ((int) $this->getData('id_facture')) {
                    $facture = (int) $this->getChildObject('facture');
                } elseif (!is_null($propal)) {
                    $tabT = getElementElement("propal", "facture", $propal->id);
                    if (count($tabT) > 0) {
                        $facture = new Facture($this->db->db);
                        $facture->fetch($tabT[count($tabT) - 1]['d']);
                        $this->set('id_facture', $facture->id);
                        $this->update();
                    }
                }
                if (!is_null($facture)) {
                    $fileProp = DOL_DATA_ROOT . "/facture/" . $facture->ref . "/" . $facture->ref . ".pdf";
                    if (is_file($fileProp)) {
                        $tabFile[] = $fileProp;
                        $tabFile2[] = ".pdf";
                        $tabFile3[] = $facture->ref . ".pdf";
                    }
                } else {
                    $errors[] = $error_msg . ' - Fichier PDF de la facture absent';
                }
                $subject = "Fermeture du dossier " . $this->getData('ref');
                $mail_msg = 'Nous vous remercions d\'avoir choisi Bimp pour votre ' . $nomMachine . "\n";
                $mail_msg .= 'Dans les prochains jours, vous allez peut-être recevoir une enquête satisfaction de la part d\'APPLE, votre retour est important afin d\'améliorer la qualité de notre Centre de Services.' . "\n";
                break;

            case 'Devis':
                if (!is_null($propal)) {
                    $subject = 'Devis ' . $this->getData('ref');
                    $mail_msg = "Voici le devis pour la réparation de votre '" . $nomMachine . "'.\n";
                    $mail_msg .= "Veuillez nous communiquer votre accord ou votre refus par retour de ce Mail.\n";
                    $mail_msg .= "Si vous voulez des informations complémentaires, contactez le centre de service par téléphone au " . $tel . " (Appel non surtaxé).";
                }
                break;

            case 'debut':
                $subject = 'Prise en charge ' . $this->getData('ref');
                $mail_msg = "Merci d'avoir choisi BIMP en tant que Centre de Services Agréé Apple.\n";
                $mail_msg .= 'La référence de votre dossier de réparation est : ' . $this->getData('ref') . ", ";
                $mail_msg .= "si vous souhaitez communiquer d'autres informations merci de répondre à ce mail ou de contacter le " . $tel . ".\n";
                $sms = "Bonjour, nous avons le plaisir de vous annoncer que le diagnostic de votre produit commence, nous vous recontacterons quand celui-ci sera fini. L'équipe BIMP";
                break;

            case 'debDiago':
                $subject = "Prise en charge " . $this->getData('ref');
                $mail_msg = "Nous avons commencé le diagnostic de votre produit, vous aurez rapidement des nouvelles de notre part. ";
                $sms = "Nous avons commencé le diagnostic de votre produit, vous aurez rapidement des nouvelles de notre part. Votre centre de services Apple.";
                break;

            case 'commOk':
                $subject = 'Commande piece(s) ' . $this->getData('ref');
                $mail_msg = "Nous venons de commander la/les pièce(s) pour votre '" . $nomMachine . "' ou l'échange de votre iPod,iPad,iPhone. Nous restons à votre disposition pour toutes questions au " . $tel;
                $sms = "Bonjour, la pièce/le produit nécessaire à votre réparation vient d'être commandé(e), nous vous contacterons dès réception de celle-ci. L'équipe BIMP.";
                break;

            case 'repOk':
                $subject = $this->getData('ref') . " Reparation  terminee";
                $mail_msg = "Nous avons le plaisir de vous annoncer que la réparation de votre produit est finie.\n";
                $mail_msg .= "Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . ", si vous souhaitez plus de renseignements, contactez le " . $tel;
                $sms = "Bonjour, la réparation de votre produit est finie. Vous pouvez le récupérer à " . $nomCentre . " " . $delai . ". L'Equipe BIMP.";
                break;

            case 'revPropRefu':
                $subject = "Prise en charge " . $this->getData('ref') . " terminée";
                $mail_msg = "la réparation de votre produit est refusée. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . "\n";
                $mail_msg .= "Si vous souhaitez plus de renseignements, contactez le " . $tel;
                $sms = "Bonjour, la réparation de votre produit  est refusée. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . ". L'Equipe BIMP.";
                break;

            case 'pieceOk':
                $subject = "Pieces recues " . $this->getData('ref');
                $mail_msg = "La pièce/le produit que nous avions commandé pour votre Machine est arrivé aujourd'hui. Nous allons commencer la réparation de votre appareil.\n";
                $mail_msg .= "Vous serez prévenu dès que l'appareil sera prêt.";
                $sms = "Bonjour, nous venons de recevoir la pièce ou le produit pour votre réparation, nous vous contacterons quand votre matériel sera prêt. L'Equipe BIMP.";
                break;

            case "commercialRefuse":
                $subject = "Devis sav refusé par « " . $client->dol_object->getFullName($langs) . " »";
                $text = "Notre client « " . $client->dol_object->getNomUrl(1) . " » a refusé le devis de réparation sur son « " . $nomMachine . " » pour un montant de «  " . price($propal->dol_object->total) . "€ »";
                $id_user_tech = (int) $this->getData('id_user_tech');
                if ($id_user_tech) {
                    $where = "rowid IN (SELECT `fk_usergroup` FROM `" . MAIN_DB_PREFIX . "usergroup_user` WHERE `fk_user` = " . $id_user_tech . ") AND `nom` REGEXP 'Sav([0-9])'";
                    $rows = $this->db->getRows('usergroup', $where, null, 'object', array('nom'));
                    if (!is_null($rows)) {
                        foreach ($rows as $r) {
                            $toMail = str_ireplace("Sav", "Boutique", $r->nom) . "@bimp.fr";
                            mailSyn2($subject, $toMail, $fromMail, $text);
                        }
                    }
                }
                break;
        }

        $contact = $this->getChildObject('contact');

        $contact_pref = (int) $this->getData('contact_pref');

        if ($contact_pref === 1 && $mail_msg) {
            if (!is_null($contact) && $contact->isLoaded()) {
                if (isset($contact->dol_object->email) && $contact->dol_object->email) {
                    $toMail = $contact->dol_object->email;
                }
            }

            if (!$toMail) {
                $toMail = $client->dol_object->email;
            }

            if (!$toMail) {
                $errors[] = $error_msg . ' (E-mail du client absent)';
            }

            if ($tech) {
                $mail_msg .= "\n" . "Technicien en charge de la réparation : " . $tech;
            }

            $mail_msg .= "\n" . $textSuivie . "\n Cordialement.\n\nL'équipe BIMP\n\n" . $signature;

            if (!mailSyn2($subject, $toMail, $fromMail, $mail_msg, $tabFile, $tabFile2, $tabFile3)) {
                $errors[] = 'Echec envoi du mail';
            }
        } else {
            $errors[] = 'pas de mail';
        }

        if ($contact_pref === 3 && $sms) {
            require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");
            if (!is_null($contact) && $contact->isLoaded()) {
                if (testNumSms($contact->dol_object->phone_mobile))
                    $to = $contact->dol_object->phone_mobile;
                elseif (testNumSms($contact->dol_object->phone_pro))
                    $to = $contact->dol_object->phone_pro;
                elseif (testNumSms($contact->dol_object->phone_perso))
                    $to = $contact->dol_object->phone_perso;
            } elseif (testNumSms($client->dol_object->phone))
                $to = $client->dol_object->phone;

            $sms .= " " . $this->getData('ref');
            $to = "0686691814";
            $fromsms = urlencode('SAV BIMP');

            $to = traiteNumMobile($to);
            if ($to == "" || (stripos($to, "+336") === false && stripos($to, "+337") === false)) {
                $errors[] = 'Numéro invalide pour l\'envoi du sms';
            } else {
                $smsfile = new CSMSFile($to, $fromsms, $text);
                if (!$smsfile->sendfile()) {
                    $errors[] = 'Echec de l\'envoi du sms';
                }
            }
        }

        return $errors;
    }

    protected function getNextNumRef()
    {
        require_once(DOL_DOCUMENT_ROOT . "/bimpsupport/classes/SAV_ModelNumRef.php");
        $tmp = new SAV_ModelNumRef($this->db->db);
        $objsoc = false;
        $id_soc = (int) $this->getData('id_client');
        if (!$id_soc) {
            $id_soc = (int) BimpTools::getValue('id_client', 0);
        }
        if ($id_soc > 0) {
            $objsoc = new Societe($this->db->db);
            $objsoc->fetch($id_soc);
        }

        $mask = self::$ref_model;



        $mask = str_replace('{CENTRE}', $this->getCodeEntrepot(), $mask);

        return($tmp->getNextValue($objsoc, $this, $mask));
    }

    function generatePDF($file_type, &$errors)
    {
        $url = '';

        if (!in_array($file_type, array('pc', 'destruction', 'destruction2', 'pret'))) {
            $errors[] = 'Type de fichier PDF invalide';
            return '';
        }

        require_once DOL_DOCUMENT_ROOT . "/bimpsupport/core/modules/bimpsupport/modules_bimpsupport.php";

        $errors = bimpsupport_pdf_create($this->db->db, $this, 'sav', $file_type);

        if (!count($errors)) {
            $ref = '';
            switch ($file_type) {
                case 'pc':
                    $ref = 'PC-' . $this->getData('ref');
                    break;
                case 'destruction':
                    $ref = 'Destruction-' . $this->getData('ref');
                    break;
                case 'destruction2':
                    $ref = 'Destruction2-' . $this->getData('ref');
                    break;
                case 'pret':
                    $ref = 'Pret-' . $this->getData('ref');
                    break;
            }

            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('sav/' . $this->id . '/' . $ref . '.pdf');
        }

        return $url;
    }
}

function testNumSms($to)
{
    $to = str_replace(" ", "", $to);
    if ($to == "")
        return 0;
    if ((stripos($to, "06") === 0 || stripos($to, "07") === 0) && strlen($to) == 10)
        return 1;
    if ((stripos($to, "+336") === 0 || stripos($to, "+337") === 0) && strlen($to) == 12)
        return 1;
    return 0;
}

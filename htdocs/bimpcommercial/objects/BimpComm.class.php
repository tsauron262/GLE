<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BimpComm extends BimpDolObject
{

    const BC_ZONE_FR = 1;
    const BC_ZONE_UE = 2;
    const BC_ZONE_HORS_UE = 3;
    const BC_ZONE_UE_SANS_TVA = 4;

    public static $achat = 0;
    public static $dont_check_parent_on_update = false;
    public static $discount_lines_allowed = true;
    public static $use_zone_vente_for_tva = true;
    public static $cant_edit_zone_vente_secteurs = array('M');
    public static $remise_globale_allowed = true;
    public $acomptes_allowed = false;
    public $remise_globale_line_rate = null;
    public $lines_locked = 0;
    public $erreurFatal = 0;
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
        0   => ['label' => 'Non traitée en comptabilité', 'classes' => ['danger'], 'icon' => 'times'],
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
    public $onChildSaveProcessed = false;

    public function __construct($module, $object_name)
    {
        $this->useCaisseForPayments = (int) BimpCore::getConf('use_caisse_for_payments');
        parent::__construct($module, $object_name);
    }

    // Gestion des droits: 

    public function canView()
    {
        global $user;

        if (isset($user->rights->bimpcommercial->read) && (int) $user->rights->bimpcommercial->read) {
            return 1;
        }

        return 0;
    }

    public function canEditField($field_name)
    {
        global $user;

        switch ($field_name) {
            case 'logs':
                return (BimpObject::objectLoaded($user) && $user->admin ? 1 : 0);

            case 'zone_vente':
                if (static::$use_zone_vente_for_tva) {
                    if (!(int) $user->rights->bimpcommercial->priceVente && in_array($this->getData('ef_type'), static::$cant_edit_zone_vente_secteurs)) {
                        return 0;
                    }

                    if (!$user->rights->bimpcommercial->edit_zone_vente) {
                        return 0;
                    }
                }
                return 1;
        }

        return (int) parent::canEditField($field_name);
    }

    public function canClientView()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            if ($userClient->isLogged()) {
                if ($this->isLoaded() && (int) $this->getData('fk_soc') !== (int) $userClient->getData('id_client')) {
                    return 0;
                }
                return 1;
            }
        }

        return 0;
    }

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'checkMarge':
                return ($user->admin ? 1 : 0);
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ((int) $this->getData('fk_statut') === 0) {
            return 1;
        }

        if ($force_delete) {
            global $rgpd_delete;

            if ($rgpd_delete) {
                return 1;
            }
        }
        return 0;
    }

    public function isEditable($force_edit = false, &$errors = Array())
    {
        return parent::isEditable($force_edit, $errors);
    }

    public function hasDemandsValidations($exclude_user_affected = true)
    {
        global $user;

        if (BimpCore::isModuleActive('bimpvalidation')) {
            $demandes = BimpValidation::getObjectDemandes($this, 0);
            if (count($demandes)) {
                if ($exclude_user_affected) {
                    foreach ($demandes as $demande) {
                        $users = $demande->getData('validation_users');
                        if (in_array($user->id, $users)) {
                            return 0;
                        }
                    }
                }

                return 1;
            }
        } else {
            $valid_comm = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
            $type_de_piece = ValidComm::getObjectClass($this);

            $demands = $valid_comm->demandeExists($type_de_piece, $this->id, null, 0, true);

            if ($demands) {
                if ($exclude_user_affected) {
                    foreach ($demands as $d) {
                        if ((int) $d->getData('id_user_affected') == (int) $user->id) {
                            return 0;
                        }
                    }
                }

                // Soumis à des validations et possède des demandes de validation en brouillon
                if ($type_de_piece != -2 and $demands) {
                    return 1;
                }
            }
        }


        return 0;
    }

    public function isFieldActivated($field_name, &$infos = '')
    {
        if ($field_name == "marge" && !(int) BimpCore::getConf('use_marge_in_parent_bimpcomm', 0, 'bimpcommercial')) {
            $infos = 'Marges désactivées pour les pièces commerciales';
            return 0;
        }

        if (in_array($field_name, array('statut_export', 'douane_number')) && !(int) BimpCore::getConf('use_statut_export', 0, 'bimpcommercial')) {
            $infos = 'Exports désactivés pour les pièces commerciales';
            return 0;
        }

        if (in_array($field_name, array('statut_relance', 'nb_relance')) && !(int) BimpCore::getConf('use_relances_paiements_clients', 0, 'bimpcommercial')) {
            $infos = 'Relance de paiement désactivées';
            return 0;
        }

        return parent::isFieldActivated($field_name, $infos);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        global $user;

        if (!$force_edit && !$user->admin && !in_array($field, array('note_public', 'note_private'))) {
            if ($this->hasDemandsValidations(true)) {
                return 0;
            }
        }

        switch ($field) {
            case 'replaced_ref':
                return 1;

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
                    if (!(int) $this->areLinesEditable()) {
                        return 0;
                    }
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
        global $conf;

        if (!$this->isLoaded($errors)) {
            return 0;
        }

        if (in_array($this->object_name, array('Bimp_Propal', 'Bimp_Commande', 'Bimp_Facture')) && BimpCore::isModuleActive('bimpvalidation')) {
            BimpObject::loadClass('bimpvalidation', 'BV_Demande');
            $nb_refused = 0;

            if (BV_Demande::objectHasDemandesRefused($this, $nb_refused)) {
                $errors[] = $nb_refused . ' demande(s) de validation refusée(s)';
                return 0;
            }
        }

        // Vérif des lignes: 
        $lines = $this->getLines('not_text');
        if (!count($lines) && !is_a($this, 'BS_SavPropal')) {
            $errors[] = 'Aucune ligne ajoutée  ' . $this->getLabel('to') . ' (Hors text)';
            return 0;
        }

        if ($this->useEntrepot() && !(int) $this->getData('entrepot')) {
            $errors[] = 'Aucun entrepôt associé';
        } else {
            $entrepot = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Entrepot', $this->getData('entrepot'));
            if ($entrepot->getData('statut') == 0)
                $errors[] = 'L\'entrepot ' . $entrepot->getRef() . ' n\'est plus actif';
        }

        if (!count($errors)) {
            if (in_array($this->object_name, array('Bimp_Propal', 'Bimp_Commande', 'Bimp_Facture'))) {
                global $user;
                if ($this->object_name === 'Bimp_Commande' && (int) $this->getData('id_client_facture')) {
                    $client = $this->getChildObject('client_facture');
                } else {
                    $client = $this->getChildObject('client');
                }

                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Client absent';
                } else {
                    if ((int) BimpCore::getConf('typent_required', 0, 'bimpcommercial') && $client->getData('fk_typent') == 0) {
                        $errors[] = 'Type de tiers obligatoire';
                    }

                    $errors = BimpTools::merge_array($errors, $this->checkContacts());

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

        if (in_array((int) $this->getData('fk_mode_reglement'), explode(',', BimpCore::getConf('rib_client_required_modes_paiement', null, 'bimpcommercial'))) && $this->extrafieldsIsConfig('rib_client')) {
            if ($this->getData('rib_client') < 1)
                $errors[] = 'Pour les prélèvements SEPA, le RIB est obligatoire';
            else {
                $rib = $this->getChildObject('rib_client');
                $rib->isValid($errors);
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

                $client = $this->getChildObject('client');

                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Client absent';
                    return 0;
                }
                return 1;

            case 'useRemise':

                if (!$this->isEditable())
                    return 0;

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

            case 'checkTotal':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function areLinesEditable()
    {
        if ($this->field_exists('fk_statut')) {
            if ((int) $this->getData('fk_statut') > 0) {
                return 0;
            }
        }

        if (!$this->isEditable()) {
            return 0;
        }

        global $user;

        if (!$user->admin && $this->hasDemandsValidations()) {
            return 0;
        }

        return 1;
    }

    public function areLinesValid(&$errors = array(), $mail = true)
    {
        $result = 1;
        foreach ($this->getLines() as $line) {
            $line_errors = array();

            if (!$line->isValid($line_errors, $mail)) {
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

    public function hasRemisesGlobales()
    {
        if ($this->hasRemiseGlobale()) {
            $rgs = $this->getRemisesGlobales();

            if (!empty($rgs)) {
                return 1;
            }
        }

        return 0;
    }

    public function hasRemiseCRT()
    {
        $line_instance = $this->getLineInstance();

        if ($line_instance->field_exists('remise_crt')) {
            return (int) $this->db->getCount($line_instance->getTable(), 'id_obj = ' . $this->id . ' AND remise_crt > 0') > 0;
        }

        return 0;
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

    public function showForceCreateBySoc()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client) && is_a($client, 'Bimp_Societe')) {
            if (!$client->isSolvable($this->object_name)) {
                global $user;
//                echo '<pre>';print_r($user->rights);
                if ($user->rights->bimpcommercial->admin_financier)
                    return 1;
            }
        }

        return 0;
    }

    // Getters array: 

    public function getRibArray($include_empty = true)
    {
        $client = $this->getClientFacture();
        if (BimpObject::objectLoaded($client)) {
            return BimpCache::getSocieteRibsArray($client->id, $include_empty);
        }

        return ($include_empty ? array(0 => '') : array());
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
            $id_rib = (int) $this->getData('rib_client');
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $id_mode_paiement = $client->dol_object->mode_reglement_id;

                if (!$id_rib) {
                    $id_rib = (int) $client->getDefaultRibId();
                }
            }

            $buttons[] = array(
                'label'   => 'Ajouter un acompte',
                'icon'    => 'fas_hand-holding-usd',
                'onclick' => $this->getJsActionOnclick('addAcompte', array(
                    'id_mode_paiement' => $id_mode_paiement,
                    'id_rib'           => $id_rib
                        ), array(
                    'form_name' => 'acompte'
                ))
            );
        }

        // Message Achat:
        $id_group = BimpCore::getUserGroupId('achat');
        $note = BimpObject::getInstance("bimpcore", "BimpNote");
        
        if ($id_group) {
            $buttons[] = array(
                'label'   => 'Message achat',
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

        // Message facturation: 
        $id_group = BimpCore::getUserGroupId('facturation');
        if ($id_group) {
            // SERV19-FPR
            $msg = "Bonjour, merci de bien vouloir facturer cette commande*\\n\\n*si vous souhaitez une facturation partielle, veuillez modifier ce texte et indiquer précisément vos besoins\\n\\nIMPORTANT : toute facturation anticipée de produits ou services non livrés doit rester exceptionnelle, doit être justifiée par une demande écrite du client (déposer ce justificatif en pièce jointe) et doit être systématiquement signalée à notre comptabilité @Compta Fournisseurs Olys et à @David TEIXEIRA RODRIGUES";
            foreach ($this->getLines() as $line) {
                $prod = $line->getChildObject('product');
                if (stripos($prod->getData('ref'), 'SERV19-FPR') !== false) {
                    $msg .= '\n' . $prod->getData('ref') . ' en quantités ' . $line->qty;
                }
            }
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
                    "content"       => $msg
                        ), array(
                    'form_name' => 'rep'
                ))
            );
        }

        // Relevé facturation: 
        if ((int) $this->getData('fk_soc')) {
            $sql = 'SELECT datef FROM ' . MAIN_DB_PREFIX . 'facture WHERE fk_soc = ' . (int) $this->getData('fk_soc') . ' AND fk_statut IN (1,2,3)';
            $sql .= ' ORDER BY datef ASC LIMIT 1';

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['datef'])) {
                $debut = $result[0]['datef'];
            } else {
                $debut = date('Y-m-d');
            }

            $buttons[] = array(
                'label'   => 'Relevé facturation client',
                'icon'    => 'fas_clipboard-list',
                'onclick' => $this->getJsActionOnclick('releverFacturation', array(
                    'date_debut' => $debut,
                    'date_fin'   => date('Y-m-d')
                        ), array(
                    'form_name' => 'releverFacturation'
                ))
            );
        }

        // Edition historique: 
        if ($this->canEditField('logs')) {
            $buttons[] = array(
                'label'   => 'Editer logs',
                'icon'    => 'fas_history',
                'onclick' => $this->getJsLoadModalForm('logs', 'Editer les logs')
            );
        }

        if ($this->canSetAction('checkTotal') && $this->isActionAllowed('checkTotal')) {
            $buttons[] = array(
                'label'   => 'Vérifier le total',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('checkTotal')
            );
        }

        if ($this->canSetAction('checkMarge') && $this->isActionAllowed('checkMarge')) {
            $buttons[] = array(
                'label'   => 'Vérifier la marge',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('checkMarge')
            );
        }

        return $buttons;
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
                $productFourn = BimpObject::getInstance('bimpcore', 'Bimp_Product_Ldlc');
                $buttons[] = array(
                    'label'       => 'Catalogue fournisseur',
                    'icon_before' => 'fas_box',
                    'classes'     => array('btn', 'btn-default'),
                    'attr'        => array(
                        'onclick' => $productFourn->getJsLoadModalList()
                    )
                );

                global $user;

                if ($user->admin || $user->id == 956) {
                    $buttons[] = array(
                        'label'   => 'Importer des lignes',
                        'icon'    => 'fas_download',
                        'onclick' => $this->getJsLoadModalForm('import_lines_csv', 'Importer des lignes depuis un fichier CSV')
                    );
                }
            }

            if ($this->isActionAllowed('useRemise') && $this->canSetAction('useRemise')) {
                if ($this->object_name === 'Bimp_Commande' || $this->object_name === 'Bimp_Propal' || (int) $this->getData('fk_statut') === 0) {
                    $buttons[] = array(
                        'label'       => 'Déduire un crédit disponible',
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

    public function getCardFields($card_name)
    {
        $fields = parent::getCardFields($card_name);

        switch ($card_name) {
            case 'default':
                $fields[] = 'model_pdf';
                break;
        }

        return $fields;
    }

    public function getPdfNamePrincipal()
    {
        return $this->getRef() . '.pdf';
    }

    public function getEmailClientFromType()
    {
        $secteur = $this->getData('ef_type');

        if ($secteur) {
            $secteurs = BimpCache::getSecteursData();

            if (isset($secteurs[$secteur]['email_from'])) {
                return $secteurs[$secteur]['email_from'];
            }
        }

        return '';
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

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'id_product':
                if (!empty($values)) {
                    $line = $this->getLineInstance();

                    $alias = $main_alias . '___' . $line::$parent_comm_type . '_det';

                    if (!$excluded) {
                        $joins[$alias] = array(
                            'alias' => $alias,
                            'table' => $line::$dol_line_table,
                            'on'    => $alias . '.' . $line::$dol_line_parent_field . ' = ' . $main_alias . '.' . $this->getPrimary()
                        );
                        $key = 'in';
                        if ($excluded) {
                            $key = 'not_in';
                        }
                        $filters[$alias . '.fk_product'] = array(
                            $key => $values
                        );
                    } else {
                        $alias .= '_not';
                        $filters[$main_alias . '.' . $this->getPrimary()] = array(
                            'not_in' => '(SELECT ' . $alias . '.' . $line::$dol_line_parent_field . ' FROM ' . MAIN_DB_PREFIX . $line::$dol_line_table . ' ' . $alias . ' WHERE ' . $alias . '.fk_product' . ' IN (' . implode(',', $values) . '))'
                        );
                    }
                }
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

                $elem_alias = $main_alias . '___elemcont';
                $joins[$elem_alias] = array(
                    'table' => 'element_contact',
                    'on'    => $elem_alias . '.element_id = ' . $main_alias . '.rowid',
                    'alias' => $elem_alias
                );

                $type_alias = $main_alias . '___typecont';
                $joins[$type_alias] = array(
                    'table' => 'c_type_contact',
                    'on'    => $elem_alias . '.fk_c_type_contact = ' . $type_alias . '.rowid',
                    'alias' => $type_alias
                );

                $sql = '';

                if (!empty($ids)) {
                    $sql .= '(';
                    $sql .= $type_alias . '.element = \'' . static::$dol_module . '\' AND ' . $type_alias . '.source = \'internal\'';
                    $sql .= ' AND ' . $type_alias . '.code = \'SALESREPFOLL\' AND ' . $elem_alias . '.fk_socpeople ' . ($excluded ? 'NOT ' : '') . 'IN (' . implode(',', $ids) . ')';
                    $sql .= ')';

                    if (!$empty && $excluded) {
                        $sql .= ' OR (SELECT COUNT(ec2.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc2 ON tc2.rowid = ec2.fk_c_type_contact';
                        $sql .= ' WHERE tc2.element = \'' . static::$dol_module . '\'';
                        $sql .= ' AND tc2.source = \'internal\'';
                        $sql .= ' AND tc2.code = \'SALESREPFOLL\'';
                        $sql .= ' AND ec2.element_id = ' . $main_alias . '.rowid) = 0';
                    }
                }

                if ($empty) {
                    $sql .= ($sql ? ($excluded ? ' AND ' : ' OR ') : '');
                    $sql .= '(SELECT COUNT(ec2.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec2';
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc2 ON tc2.rowid = ec2.fk_c_type_contact';
                    $sql .= ' WHERE tc2.element = \'' . static::$dol_module . '\'';
                    $sql .= ' AND tc2.source = \'internal\'';
                    $sql .= ' AND tc2.code = \'SALESREPFOLL\'';
                    $sql .= ' AND ec2.element_id = ' . $main_alias . '.rowid) ' . ($excluded ? '>' : '=') . ' 0';
                }

                if ($sql) {
                    $filters[$main_alias . '___commercial_custom'] = array(
                        'custom' => '(' . $sql . ')'
                    );
                }
                break;
            case 'categorie':
            case 'collection':
            case 'nature':
            case 'famille':
            case 'gamme':
                $prod_ef_alias = $main_alias . '___product_ef';
                $line = $this->getLineInstance();
                $line_alias = $main_alias . '___' . $line::$parent_comm_type . '_det';
                if (!$excluded) {
                    $joins[$line_alias] = array(
                        'alias' => $line_alias,
                        'table' => $line::$dol_line_table,
                        'on'    => $line_alias . '.' . $line::$dol_line_parent_field . ' = ' . $main_alias . '.' . $this->getPrimary()
                    );
                    $joins[$prod_ef_alias] = array(
                        'alias' => $prod_ef_alias,
                        'table' => 'product_extrafields',
                        'on'    => $prod_ef_alias . '.fk_object = ' . $line_alias . '.fk_product'
                    );

                    $filters[$prod_ef_alias . '.' . $field_name] = array(
                        ($excluded ? 'not_' : '') . 'in' => $values
                    );
                } else {
                    $prod_ef_alias .= '_not';

                    $select_sql = 'SELECT ' . $line_alias . '.' . $line::$dol_line_parent_field;
                    $select_sql .= ' FROM ' . MAIN_DB_PREFIX . $line::$dol_line_table . ' ' . $line_alias . ', ';
                    $select_sql .= MAIN_DB_PREFIX . 'product_extrafields ' . $prod_ef_alias;
                    $select_sql .= ' WHERE ' . $prod_ef_alias . '.fk_object = ' . $line_alias . '.fk_product';
                    $select_sql .= ' AND ' . $prod_ef_alias . '.' . $field_name . ' IN (' . implode(',', $values) . ')';

                    $filters[$main_alias . '.' . $this->getPrimary()] = array(
                        'not_in' => '(' . $select_sql . ')'
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
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

    public function getTotalTtcWithoutRemises($exclude_discounts = false, $full_qty = false)
    {
        $total = 0;

        if ($this->isLoaded()) {
            $lines = $this->getLines();
            foreach ($lines as $line) {
                if ($exclude_discounts && (int) $line->id_remise_except) {
                    continue;
                }

                $total += $line->getTotalTtcWithoutRemises($full_qty);
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

    public function getRemisesInfos($force_qty_mode = -1)
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

            $lines = $this->getLines('not_text');

            foreach ($lines as $line) {
                if ($line->getData('linked_object_name') != 'discount' && $line->desc != 'Acompte') {
                    $line_infos = $line->getRemiseTotalInfos(false, $force_qty_mode);
                    $infos['remises_lines_amount_ttc'] += (float) $line_infos['line_amount_ttc'];
                    $infos['remises_lines_amount_ht'] += (float) $line_infos['line_amount_ht'];
                    $infos['remises_globales_amount_ht'] += (float) $line_infos['global_amount_ht'];
                    $infos['remises_globales_amount_ttc'] += (float) $line_infos['global_amount_ttc'];
                    $infos['ext_remises_globales_amount_ht'] += (float) $line_infos['ext_global_amount_ht'];
                    $infos['ext_remises_globales_amount_ttc'] += (float) $line_infos['ext_global_amount_ttc'];
                    $total_ttc_without_remises += $line_infos['total_ttc_without_remises'];
                }
            }

            if ($total_ttc_without_remises && $infos['remises_lines_amount_ttc']) {
                $infos['remises_lines_percent'] = ($infos['remises_lines_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            if ($total_ttc_without_remises && $infos['remises_globales_amount_ttc']) {
                $infos['remises_globales_percent'] = ($infos['remises_globales_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            if ($total_ttc_without_remises && $infos['ext_remises_globales_amount_ttc']) {
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
                if ($line->desc === 'Acompte')
                    continue;
                if (empty($line->pa_ht) && isset($line->fk_fournprice) && !$force_price) {
                    require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
                    $product = new ProductFournisseur($db);
                    if ($product->fetch_product_fournisseur_price($line->fk_fournprice))
                        $line->pa_ht = $product->fourn_unitprice * (1 - $product->fourn_remise_percent / 100);
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
                    if (!$bimp_line->isService()) {  // product
                        $marginInfos['pa_products'] += $pa;
                        $marginInfos['pv_products'] += $pv;
                        $marginInfos['pa_total'] += $pa;
                        $marginInfos['pv_total'] += $pv;
                        $marginInfos['margin_on_products'] += $pv - $pa;
                    } else {  // service
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

    public function getProvLink()
    {
        return str_replace($this->getRef(), '(PROV' . $this->id . ')', $this->getLink());
    }

    public function getCondReglementBySociete()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0)) {
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
                    if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn'))) {
                        return (int) $soc->getData('cond_reglement_supplier');
                    } else {
                        return (int) $soc->getData('cond_reglement');
                    }
                }
            }
        }

        if (isset($this->data['fk_cond_reglement']) && (int) $this->data['fk_cond_reglement']) {
            return (int) $this->data['fk_cond_reglement']; // pas getData() sinon boucle infinie (getCondReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_cond_reglement', 0);
    }

    public function getModeReglementBySociete()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0)) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc', 0);
            if (!$id_soc && $this->getData('fk_soc') > 0) {
                $id_soc = $this->getData('fk_soc');
            }
            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    if (in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn'))) {
                        return (int) $soc->getData('mode_reglement_supplier');
                    } else {
                        return (int) $soc->getData('mode_reglement');
                    }
                }
            }
        }

        if (isset($this->data['fk_mode_reglement']) && (int) $this->data['fk_mode_reglement']) {
            return (int) $this->data['fk_mode_reglement']; // pas getData() sinon boucle infinie (getModeReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_mode_reglement', 0);
    }

    public static function getZoneByCountry(Bimp_Societe $client)
    {
        $zone = self::BC_ZONE_FR;
        $id_country = $client->getData('fk_pays');

        if (!(int) $id_country) {
            $id_country = (int) BimpCore::getConf('default_id_country');
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

    public function getCommercial($params = array())
    {
        $id_comm = (int) $this->getCommercialId($params);
        if ($id_comm) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_comm);
            if (BimpObject::objectLoaded($user)) {
                return $user;
            }
        }

        return null;
    }

    public function getIdCommercial()
    {
        return $this->getIdContact($type = 'internal', $code = 'SALESREPFOLL');
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

    public function getTotal_paListTotal($filters = array(), $joins = array(), $main_alias = 'a')
    {
        $return = array(
            'data_type' => 'money',
            'value'     => 0
        );

        $line = $this->getLineInstance();

        if (is_a($line, 'ObjectLine')) {
            return 'n/c';
//            TODO fait bloqué le serveur 
//            $line_alias = $main_alias . '___det';
//            $joins[$line_alias] = array(
//                'table' => $line::$dol_line_table,
//                'alias' => $line_alias,
//                'on'    => $main_alias . '.rowid = ' . $line_alias . '.' . $line::$dol_line_parent_field
//            );
//
//            $sql = 'SELECT SUM(' . $line_alias . '.qty * ' . $line_alias . '.buy_price_ht) as total';
//            $sql .= BimpTools::getSqlFrom($this->getTable(), $joins, $main_alias);
//            $sql .= BimpTools::getSqlWhere($filters);
//
//            $result = $this->db->executeS($sql, 'array');
//
//            if (isset($result[0]['total'])) {
//                $return['value'] = (float) $result[0]['total'];
//            }
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
            $cache_key = $this->object_name . '_' . $this->id . '_remises_globales';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = BimpCache::getBimpObjectObjects('bimpcommercial', 'RemiseGlobale', array(
                            'obj_type' => static::$element_name,
                            'id_obj'   => (int) $this->id
                ));
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getTotalRemisesGlobalesAmount()
    {
        $total_rg = 0;
        if ($this->isLoaded()) {
            $rgs = $this->getRemisesGlobales();

            if (!empty($rgs)) {
                $total_ttc = (float) $this->getTotalTtcWithoutRemises(true);

                foreach ($rgs as $rg) {
                    $remise_amount = 0;
                    switch ($rg->getData('type')) {
                        case 'percent':
                            $remise_rate = (float) $rg->getData('percent');
                            $remise_amount = $total_ttc * ($remise_rate / 100);
                            break;

                        case 'amount':
                            $remise_amount = (float) $rg->getData('amount');
                            break;
                    }

                    if ($remise_amount) {
                        $total_rg += $remise_amount;
                    }
                }
            }
        }

        return $total_rg;
    }

    // Getters - Overrides BimpObject

    public function getName($with_generic = true)
    {
        // Ne jamais inclure la ref dans le nom (getRef() et getName() peuvent être appelés conjointement)
        if ($this->isLoaded()) {
            $name = (string) $this->getData('libelle');
            if ($name) {
//                return $this->getData('ref') . ' : ' . $name;
                return $name;
            }

            if ($with_generic) {
//                return BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id;
                return $this->getData('ref');
            }
        }

        return '';
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                if (isset(static::$files_module_part)) {
                    $module_part = static::$files_module_part;
                } else {
                    $module_part = static::$dol_module;
                }
                return DOL_URL_ROOT . '/' . $page . '.php?modulepart=' . $module_part . '&file=' . urlencode($this->getRef()) . '/' . urlencode($file_name);
            }
        }

        return '';
    }

    // Setters:

    public function setRef($ref)
    {
        $ref_prop = $this->getRefProperty();

        if ($this->field_exists($ref_prop)) {
            $this->set($ref_prop, $ref);
            $dol_prop = $this->getConf('fields/' . $ref_prop . '/dol_prop', $ref_prop);
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
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayMoneyValue((float) $discounts, 'EUR', 0, 0, 0, 2, 1) . '</td>';
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
                            $label = 'Déduire un crédit disponible';
                        } elseif ($this->object_name === 'Bimp_Facture' && in_array((int) $this->getData('fk_statut'), array(1, 2))) {
                            $label = 'Appliquer un avoir ou un trop perçu disponible';
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
            $infos = $this->getRemisesInfos(0);
            $infos_fq = null;

            if (in_array($this->object_name, array('Bimp_Commande'))) {
                $infos_fq = $this->getRemisesInfos(1);
            }

            if ($infos['remise_total_amount_ttc'] || (!is_null($infos_fq) && $infos_fq['remise_total_amount_ttc'])) {
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

                if ($infos['remises_lines_amount_ttc'] || (!is_null($infos_fq) && $infos_fq['remises_lines_amount_ttc'])) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Remises lignes: </td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($infos['remises_lines_amount_ht'], 'EUR', 0, 0, 0, 2, 1);
                    if (!is_null($infos_fq) && $infos['remises_lines_amount_ht'] != $infos_fq['remises_lines_amount_ht']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayMoneyValue($infos_fq['remises_lines_amount_ht'], 'EUR', 0, 0, 0, 2, 1);
                        $html .= '</span>';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($infos['remises_lines_amount_ttc'], 'EUR', 0, 0, 0, 2, 1);
                    if (!is_null($infos_fq) && $infos['remises_lines_amount_ttc'] != $infos_fq['remises_lines_amount_ttc']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayMoneyValue($infos_fq['remises_lines_amount_ttc'], 'EUR', 0, 0, 0, 2, 1);
                        $html .= '</span>';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayFloatValue($infos['remises_lines_percent'], 4) . ' %';
                    if (!is_null($infos_fq) && $infos['remises_lines_percent'] != $infos_fq['remises_lines_percent']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayFloatValue($infos_fq['remises_lines_percent'], 4) . ' %';
                        $html .= '</span>';
                    }
                    $html .= '</td>';

                    $html .= '</tr>';
                }

                if ($infos['remises_globales_amount_ttc'] || (!is_null($infos_fq) && $infos_fq['remises_globales_amount_ttc'])) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Remises globales: </td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($infos['remises_globales_amount_ht'], 'EUR', 0, 0, 0, 2, 1);
                    if (!is_null($infos_fq) && $infos['remises_globales_amount_ht'] != $infos_fq['remises_globales_amount_ht']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayMoneyValue($infos_fq['remises_globales_amount_ht'], 'EUR', 0, 0, 0, 2, 1);
                        $html .= '</span>';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($infos['remises_globales_amount_ttc'], 'EUR', 0, 0, 0, 2, 1);
                    if (!is_null($infos_fq) && $infos['remises_globales_amount_ttc'] != $infos_fq['remises_globales_amount_ttc']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayMoneyValue($infos_fq['remises_globales_amount_ttc'], 'EUR', 0, 0, 0, 2, 1);
                        $html .= '</span>';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayFloatValue($infos['remises_globales_percent'], 4) . ' %';
                    if (!is_null($infos_fq) && $infos['remises_globales_percent'] != $infos_fq['remises_globales_percent']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayFloatValue($infos_fq['remises_globales_percent'], 4) . ' %';
                        $html .= '</span>';
                    }
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                if ($infos['ext_remises_globales_amount_ttc'] || (!is_null($infos_fq) && $infos_fq['ext_remises_globales_amount_ttc'])) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Parts de remises globales externes: </td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($infos['ext_remises_globales_amount_ht'], 'EUR', 0, 0, 0, 2, 1);
                    if (!is_null($infos_fq) && $infos['ext_remises_globales_amount_ht'] != $infos_fq['ext_remises_globales_amount_ht']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayMoneyValue($infos_fq['ext_remises_globales_amount_ht'], 'EUR', 0, 0, 0, 2, 1);
                        $html .= '</span>';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($infos['ext_remises_globales_amount_ttc'], 'EUR', 0, 0, 0, 2, 1);
                    if (!is_null($infos_fq) && $infos['ext_remises_globales_amount_ttc'] != $infos_fq['ext_remises_globales_amount_ttc']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayMoneyValue($infos_fq['ext_remises_globales_amount_ttc'], 'EUR', 0, 0, 0, 2, 1);
                        $html .= '</span>';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayFloatValue($infos['ext_remises_globales_percent'], 4) . ' %';
                    if (!is_null($infos_fq) && $infos['ext_remises_globales_percent'] != $infos_fq['ext_remises_globales_percent']) {
                        $html .= '<br/><span class="important">';
                        $html .= BimpTools::displayFloatValue($infos_fq['ext_remises_globales_percent'], 4) . ' %';
                        $html .= '</span>';
                    }
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';

                $html .= '<tfoot>';
                $html .= '<td style="font-weight: bold;width: 160px;">Total Remises: </td>';

                $html .= '<td>';
                $html .= BimpTools::displayMoneyValue($infos['remise_total_amount_ht'], 'EUR', 0, 0, 0, 2, 1);
                if (!is_null($infos_fq) && $infos['remise_total_amount_ht'] != $infos_fq['remise_total_amount_ht']) {
                    $html .= '<br/><span class="important">';
                    $html .= BimpTools::displayMoneyValue($infos_fq['remise_total_amount_ht'], 'EUR', 0, 0, 0, 2, 1);
                    $html .= '</span>';
                }
                $html .= '</td>';

                $html .= '<td>';
                $html .= BimpTools::displayMoneyValue($infos['remise_total_amount_ttc'], 'EUR', 0, 0, 0, 2, 1);
                if (!is_null($infos_fq) && $infos['remise_total_amount_ttc'] != $infos_fq['remise_total_amount_ttc']) {
                    $html .= '<br/><span class="important">';
                    $html .= BimpTools::displayMoneyValue($infos_fq['remise_total_amount_ttc'], 'EUR', 0, 0, 0, 2, 1);
                    $html .= '</span>';
                }
                $html .= '</td>';

                $html .= '<td>';
                $html .= BimpTools::displayFloatValue($infos['remise_total_percent'], 4) . ' %';
                if (!is_null($infos_fq) && $infos['remise_total_percent'] != $infos_fq['remise_total_percent']) {
                    $html .= '<br/><span class="important">';
                    $html .= BimpTools::displayFloatValue($infos_fq['remise_total_percent'], 4) . ' %';
                    $html .= '</span>';
                }
                $html .= '</td>';
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

        return BimpTools::displayMoneyValue($total, '', 0, 0, 0, 2, 1);
    }

    public function displayCommercial()
    {
        $id = $this->getIdCommercial();
        if ($id > 0) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id);
            if (BimpObject::objectLoaded($user)) {
                global $modeCSV;
                if ($modeCSV) {
                    return $user->getName();
                } else {
                    return $user->getLink();
                }
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
                        $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $contact['socid']);
                        if (BimpObject::objectLoaded($soc)) {
                            $html .= $soc->getLink();
                        } else {
                            $html .= 'Client #' . $contact['socid'];
                        }
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

    public function displayCountNotes($hideIfNotNotes = false)
    {
        $notes = $this->getNotes(false);
        $nb = count($notes);
        if ($nb > 0 || $hideIfNotNotes == false)
            return '<br/><span class="warning"><span class="badge badge-warning">' . $nb . '</span> Note' . ($nb > 1 ? 's' : '') . '</span>';
        return '';
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->field_exists('replaced_ref') && $this->getData('replaced_ref')) {
            $html .= '<div style="margin-bottom: 8px">';
            $html .= '<span class="warning" style="font-size: 15px">Annule et remplace ' . $this->getLabel('the') . ' "' . $this->getData('replaced_ref') . '" (données perdues)</span>';
            $html .= '</div>';
        }

        global $user;
        if ($user->admin || $user->id == 226) {
            $html .= BimpDocumentation::renderBtn('gle', 'Test pour les admin, doc compléte');
            $html .= BimpDocumentation::renderBtn('liste', 'Test pour les admin, doc liste');
        }

        if (BimpCore::isEntity('bimp')) {
            if ($this->hasRemiseCRT()) {
                $client = null;
                if (is_a($this, 'Bimp_Facture') && $this->field_exists('id_client_final') && (int) $this->getData('id_client_final')) {
                    $client = $this->getChildObject('client_final');
                }
                if (!BimpObject::objectLoaded($client)) {
                    $client = $this->getChildObject('client');
                }

                if (BimpObject::objectLoaded($client)) {
                    if (!$client->getData('type_educ')) {
                        $onclick = $client->getJsLoadModalForm('edit_type_educ', 'Saisie du type éducation pour le client "' . addslashes($client->getName()) . '"', array(), '', '', 1);

                        $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                        $msg .= '<b>ATTENTION : ' . $this->getLabel('this') . ' contient une remise CRT, or le type éducation du client ';
                        $msg .= 'n\'est pas renseigné. En l\'absence de cette information, la validation des factures sera bloquée.</b>';
                        $msg .= '<div style="text-align: center">';
                        $msg .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $msg .= BimpRender::renderIcon('fas_edit', 'iconLeft') . 'Sélectionner le type éducation du client "' . $client->getName() . '"';
                        $msg .= '</span>';

                        $msg .= '</div>';

                        $html .= BimpRender::renderAlerts($msg, 'warning');
                    }
                }
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '';

        if (BimpCore::isModuleActive('bimpvalidation')) {
            if (!(int) $this->getData('fk_statut')) {
                $demandes = BimpValidation::getObjectDemandes($this, array(
                            'operator' => '!=',
                            'value'    => -2
                ));
                if (count($demandes)) {
                    $has_refused = false;
                    foreach ($demandes as $demande) {
                        if ((int) $demande->getData('status') === BV_Demande::BV_REFUSED) {
                            $has_refused = true;
                            break;
                        }
                    }

                    $html .= '<span class="warning">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . count($demandes) . ' demande(s) de validation :</span><br/>';
                    if ($has_refused) {
                        $html .= '<span class="danger">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Il y a au moins une demand de validation refusée. ' . $this->getLabel('this') . ' ne peut pas être validée</span><br/>';
                    }
                }

                foreach ($demandes as $demande) {
                    $html .= $demande->renderQuickView();
                }
            }
        } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
            $valid_comm = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
            $type_de_piece = ValidComm::getObjectClass($this);

            // Soumis à des validations et possède des demandes de validation en brouillon
            if ($type_de_piece != -2 and $valid_comm->demandeExists($type_de_piece, $this->id, null, 0)) {
                $html .= '<span class="warning">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'En cours de validation</span>';
            }
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        return $this->displayCountNotes(true);
    }

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
                $conf_tx_marque = (int) BimpCore::getConf('use_tx_marque', 1, 'bimpcommercial');

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
                        $html .= round((float) $marginInfo['mark_rate_products'], 4);
                    } else {
                        $html .= round((float) $marginInfo['margin_rate_products'], 4);
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
                        $html .= round((float) $marginInfo['mark_rate_services'], 4);
                    } else {
                        $html .= round((float) $marginInfo['margin_rate_services'], 4);
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
                    $html .= round((float) $marginInfo['total_mark_rate'], 4);
                } else {
                    $html .= round((float) $marginInfo['total_margin_rate'], 4);
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
        $html = '';
        if (in_array($this->object_name, array('Bimp_Propal', 'BS_SavPropal', 'Bimp_Commande'))) {
            $remises_arrieres = 0;

            $lines = $this->getLines('not_text');

            foreach ($lines as $line) {
                $remises_arrieres += $line->getTotalRemisesArrieres(false);
                ;
            }

            $total_pv = (float) $marginInfo['pv_total'];
            $total_pa = (float) $marginInfo['pa_total'];

            if ($remises_arrieres) {
                $html .= '<tr>';
                $html .= '<td>Remises arrière prévues</td>';
                $html .= '<td></td>';
                $html .= '<td><span class="danger">-' . BimpTools::displayMoneyValue($remises_arrieres, '', 0, 0, 0, 2, 1) . '</span></td>';
                $html .= '<td></td>';
                $html .= '</tr>';

                $total_pa -= $remises_arrieres;
            }

            if ((float) $total_pa !== (float) $marginInfo['pa_total']) {
                $total_marge = $total_pv - $total_pa;
                $tx = 0;

                if ((int) BimpCore::getConf('use_tx_marque', 1, 'bimpcommercial')) {
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
                $html .= '<td>' . BimpTools::displayMoneyValue($total_pv, '', 0, 0, 0, 2, 1) . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_pa, '', 0, 0, 0, 2, 1) . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_marge, '', 0, 0, 0, 2, 1) . ' (' . BimpTools::displayFloatValue($tx, 4) . ' %)</td>';
                $html .= '</tr>';
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
            $ActionComm = new ActionComm($this->db->db);
            $list = $ActionComm->getActions($fk_soc, $this->id, $type_element);

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
                        $html .= dol_print_date(strtotime($action->datep), 'dayhour');
                        if ($action->datef) {
                            $tmpa = dol_getdate($action->datep);
                            $tmpb = dol_getdate($action->datef);
                            if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
                                if ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes'] && $tmpa['seconds'] != $tmpb['seconds']) {
                                    $html .= '-' . dol_print_date(strtotime($action->datef), 'hour');
                                }
                            } else {
                                $html .= '-' . dol_print_date(strtotime($action->datef), 'dayhour');
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

    public function renderEcritureTra()
    {
        $html = '';

        switch ($this->object_name) {
            case 'Bimp_Facture':
            case 'Bimp_FactureFourn':
                $statutField = 'fk_statut';
                break;
            case 'Bimp_Paiement':
                $statutField = 'statut';
                break;
        }

        if ($this->getData($statutField) > 0) {
            viewEcriture::setCurrentObject($this);
            $html .= viewEcriture::display();
        } else {
            $html .= BimpRender::renderAlerts($this->getRef() . ' n\'est pas validé' . (($this->isLabelFemale()) ? 'e' : ''), 'info', false);
        }

        return $html;
    }

    public function renderExportedField()
    {
        $html = $this->displayData('exported');
        $html .= ' <i class="far fa5-eye rowButton bs-popover" onClick="' . $this->getJsLoadModalCustomContent('renderEcritureTra', 'Ecriture TRA de ' . $this->getRef()) . '" ></i>';
        return $html;
    }

    public function renderContacts($type = 0, $code = '', $input_name = '')
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
        if ($type == 0)
            $html .= '<th>Nature</th>';
        $html .= '<th>Tiers</th>';
        $html .= '<th>Utilisateur / Contact</th>';
        if ($code == '')
            $html .= '<th>Type de contact</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list' . $type . '_' . $code;
        $html .= '<tbody id="' . $list_id . '">';
        $html .= $this->renderContactsList($type, $code);

        $html .= '</tbody>';

        $html .= '</table>';

        $filtre = array('id_client' => (int) $this->getData('fk_soc'));
        if ($type && $code != '') {
            if ($type == 'internal') {
                $filtre['user_type_contact'] = $this->getIdTypeContact($type, $code);
            } elseif ($type == 'external') {
                $filtre['tiers_type_contact'] = $this->getIdTypeContact($type, $code);
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

    public function renderContentExtraLeft()
    {
        return '';
    }

    public function renderContentExtraRight()
    {
        return '';
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

    public function renderForceCreateBySoc()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $html = 'Ce client est au statut ' . $client->displayData('solvabilite_status') . '<br/>';
            $html .= BimpInput::renderInput('toggle', 'force_create_by_soc', 0);
        } else {
            $html = '<input type="hidden" value="0" input_name="force_create_by_soc"/><span class="danger">NON</span>';
        }

        return $html;
    }

    public function renderDemandesList()
    {
        if ($this->isLoaded()) {
            if (BimpCore::isModuleActive('bimpvalidation')) {
                return BimpValidation::renderObjectDemandesList($this);
            } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
                BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
                $objectName = ValidComm::getObjectClass($this);
                if ($objectName != -2) {
                    BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
                    $demande = BimpObject::getInstance('bimpvalidateorder', 'DemandeValidComm');
                    $list = new BC_ListTable($demande);
                    $list->addFieldFilterValue('type_de_piece', $objectName);
                    $list->addFieldFilterValue('id_piece', (int) $this->id);

                    return $list->renderHtml();
                }
            }
            return '';
        }

        return BimpRender::renderAlerts('Impossible d\'afficher la liste des demande de validation (ID ' . $this->getLabel('of_the') . ' absent)');
    }

    // Traitements:

    public function startLineTransaction()
    {
        static::$dont_check_parent_on_update = true;
    }

    public function stopLineTransaction()
    {
        static::$dont_check_parent_on_update = false;
        $lines = $this->getLines();
        if (count($lines)) {
            $lines[count($lines) - 1]->resetPositions();
            $lines[count($lines) - 1]->update();
        }
    }

    public function checkLines()
    {
        $errors = array();

        if ($this->lines_locked) {
            return array();
        }

        if (($this->isLoaded())) {
            $dol_lines = array();
            $bimp_lines = array();

            if (method_exists($this->dol_object, 'fetch_lines'))
                $this->dol_object->fetch_lines();

            foreach ($this->dol_object->lines as $line) {
                $dol_lines[(int) $line->id] = $line;
            }

            $bimp_line = $this->getChildObject('lines');
            $rows = $this->db->getRows($bimp_line->getTable(), '`id_obj` = ' . (int) $this->id, null, 'array', array('id', 'id_line', 'position', 'remise', 'type'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if (!isset($bimp_lines[(int) $r['id_line']]))
                        $bimp_lines[(int) $r['id_line']] = array(
                            'id'       => (int) $r['id'],
                            'position' => (int) $r['position'],
                            'remise'   => (float) $r['remise'],
                            'type'     => (float) $r['type']
                        );
                    else {
                        // Ligne en double: on la supprime
                        if ((int) $r['id_line']) {
                            if ($this->db->delete($bimp_line->getTable(), 'id = ' . (int) $r['id']) <= 0) {
                                $errors[] = 'Echec de la suppression de la ligne en double (ligne n° ' . $r['position'] . ') - ' . $this->db->err();
                                $this->erreurFatal++;
                            } else {
                                $this->addLog('Suppression automatique d\'une ligne en double (ID: #' . $r['id'] . ' - DOL: ' . $r['id_line'] . ')');
                            }
                        }
                    }
                }
            }

            $totalHt = 0;
            // Suppression des lignes absentes de l'objet dolibarr:
            foreach ($bimp_lines as $id_dol_line => $data) {
                if (!(int) $id_dol_line) {
                    continue;
                }
                if (!array_key_exists((int) $id_dol_line, $dol_lines)) {
                    if ($bimp_line->fetch((int) $data['id'], $this, true)) {
                        $line_warnings = array();
                        $line_errors = $bimp_line->delete($line_warnings, true);
                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la suppression d\'une ligne supprimée depuis l\'ancienne version');
                        }
                        unset($bimp_lines[$id_dol_line]);
                    }
                } elseif ($data['type'] == $bimp_line::LINE_TEXT && $dol_lines[$id_dol_line]->total_ht > 0) {
                    $this->erreurFatal++;
                    $errors[] = 'Ligne ' . $dol_lines[$id_dol_line]->desc . ' de type texte avec un montant  de ' . $dol_lines[$id_dol_line]->total_ht;
                }
                $totalHt += $dol_lines[$id_dol_line]->total_ht;
            }

            $tot = $this->getData('total_ht');

            if (round((float) $tot, 2) != round($totalHt, 2)) {
                $this->erreurFatal++;
                $msg = 'Ecart entre le total des lignes et le total ' . $this->getLabel('of_the') . '. Total lignes : ' . round($totalHt, 3) . ', total ' . $this->getLabel() . ': ' . round($tot, 3);
                $msg .= '<div style="margin-top: 10px">';
                $msg .= '<span class="btn btn-default" onclick="' . $this->getJsActionOnclick('checkTotal') . '">';
                $msg .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Vérifier le total';
                $msg .= '</span>';
                $msg .= '</div>';

                $errors[] = $msg;
            }

            // Création des lignes absentes de l'objet bimp: 
            $bimp_line->reset();
            $i = 0;
            foreach ($dol_lines as $id_dol_line => $dol_line) {
                $i++;
                if (!array_key_exists($id_dol_line, $bimp_lines) && method_exists($bimp_line, 'createFromDolLine')) {
                    $objectLine = BimpObject::getInstance($bimp_line->module, $bimp_line->object_name);
                    $objectLine->parent = $this;

                    $line_errors = $objectLine->createFromDolLine((int) $this->id, $dol_line);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la récupération des données pour la ligne n° ' . $i);
                    }
                } else {
                    if ((int) $bimp_lines[(int) $id_dol_line]['position'] !== (int) $dol_line->rang) {
                        $bimp_line->updateField('position', (int) $dol_line->rang, $bimp_lines[(int) $id_dol_line]['id']);
                    }
                    if(!is_a($this, 'Bimp_Facture') || $this->getData('fk_statut') < 1){//ne surtout pas modifier une facture validé
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
        }

        return $errors;
    }

    public function resetLines()
    {
        if ($this->isLoaded()) {
            $cache_key = $this->module . '_' . $this->object_name . '_' . $this->id . '_lines_list';
            if (self::cacheExists($cache_key)) {
                unset(self::$cache[$cache_key]);
            }
        }
    }

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $errors = array();

        if (!$force_create && !$this->can("create")) {
            return array('Vous n\'avez pas la permission de créer ' . $this->getLabel('a'));
        }

        if (!$this->isLoaded()) {
            return array('(101) ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!method_exists($this->dol_object, 'createFromClone')) {
            return array('Cette fonction n\'est pas disponible pour ' . $this->getLabel('the_plur'));
        }

        $lines_errors = $this->checkLines();

        if (count($lines_errors)) {
            return array(BimpTools::getMsgFromArray($lines_errors, 'Copie impossible'));
        }

        if ($this->field_exists('replaced_ref')) {
            $new_data['replaced_ref'] = '';
        }

//        $validate_errors = $this->validate();
//        if (count($validate_errors)) {
//            return array(BimpTools::getMsgFromArray($validate_errors), BimpTools::ucfirst($this->getLabel('this')) . ' comporte des erreurs. Copie impossible');
//        }

        global $user, $conf, $hookmanager;

        $new_object = clone $this;
        $new_object->id = null;
        $new_object->id = 0;

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

        // Pour commandes fourn. (temporaire, todo: trouver pourquoi c'est pas ajusté en auto)
        if (isset($new_object->dol_object->date_creation)) {
            $new_object->dol_object->date_creation = date('Y-m-d H:i:s');
        }

        $new_object->set('id', 0);
        $new_object->set('ref', '');
        $new_object->set('fk_statut', 0);
        $new_object->set('logs', '');

        $new_object->dol_object->user_author = $user->id;
        $new_object->dol_object->user_valid = '';

        $copy_errors = $new_object->create($warnings, $force_create);

        if (count($copy_errors)) {
            $errors[] = BimpTools::getMsgFromArray($copy_errors, 'Echec de la copie ' . $this->getLabel('of_the'));
        } else {
            $new_object->addObjectLog('Créé' . $new_object->e() . ' par clonage ' . $this->getLabel('of_the') . ' ' . $this->getRef());
            // Copie des contacts: 
            $new_object->copyContactsFromOrigin($this, $errors);

            // Copie des lignes: 
            $params = array(
                'is_clone' => true
            );

            if (isset($new_data['inverse_qty']))
                $params['inverse_qty'] = $new_data['inverse_qty'];

            $lines_errors = $new_object->createLinesFromOrigin($this, $params, $warnings);

            if (count($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la copie des lignes ' . $this->getLabel('of_the'));
            }

            // Copie des remises globales: 
            if (static::$remise_globale_allowed) {
                $new_object->copyRemisesGlobalesFromOrigin($this, $errors, $params['inverse_qty']);
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

    public function createLinesFromOrigin($origin, $params = array(), &$warnings = array())
    {
        $errors = array();

        $params = BimpTools::overrideArray(array(
                    'inverse_prices'            => false,
                    'inverse_qty'               => false,
                    'pa_editable'               => true,
                    'is_clone'                  => false,
                    'is_review'                 => false,
                    'copy_remises_globales'     => false,
                    'qty_to_zero_sauf_acomptes' => false
                        ), $params);

        if (!BimpObject::objectLoaded($origin) || !is_a($origin, 'BimpComm')) {
            return array('Element d\'origine absent ou invalide');
        }

        $lines = $origin->getChildrenObjects('lines', array(), 'position', 'asc');

        $i = 0;

        // Création des lignes: 
        $lines_new = array();

        foreach ($lines as $line) {
            if (is_a($this, 'Bimp_Commande') && is_a($line, 'Bimp_PropalLine')) {
                if ($line->isAbonnement()) {
                    continue;
                }
            }
            $i++;

            // Lignes à ne pas copier en cas de clonage: 
            if ($params['is_clone'] && (in_array($line->getData('linked_object_name'), array(
                        'discount'
                    )) || (int) $line->id_remise_except)) {
                continue;
            }

            // Lignes à ne pas copier si produit plus à la vente :
            if ($params['is_clone'] || $params['is_review']) {
                $product = $line->getProduct();
                if (BimpObject::objectLoaded($product)) {
                    if (in_array($this->object_name, array('Bimp_Propal', 'BS_SavPropal', 'Bimp_Commande', 'Bimp_Facture'))) {
                        if (!(int) $product->getData('tosell')) {
                            $warnings[] = 'Ligne n°' . $line->getData('position') . ' non incluse car le produit ' . $product->getLink() . ' n\'est plus disponible à la vente';
                            continue;
                        }
                    }
                }
            }

            $new_line = BimpObject::getInstance($this->module, $this->object_name . 'Line');

            $data = $line->getDataArray();
            $data['id_obj'] = $this->id;
            unset($data['id_line']);
            unset($data['id_parent_line']);

            if (!$params['is_review'] && !in_array($line->getData('linked_object_name'), array('bundle', 'bundleCorrect'))) {//si c'est des lignes liée mais pas a un bundle
                unset($data['linked_object_name']);
                unset($data['linked_id_object']);

                if ($line->getData('linked_object_name')) {
                    $data['deletable'] = 1;
                    $data['editable'] = 1;
                }
            }

            if ($params['is_clone']) {
                switch ($origin->object_name) {
                    case 'Bimp_Propal': 
                        unset($data['id_linked_contrat_line']);
                        break;
                    
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
                        unset($data['exp_periods_start']);
                        unset($data['next_date_exp']);
                        unset($data['fac_periods_start']);
                        unset($data['next_date_fac']);
                        unset($data['achat_periods_start']);
                        unset($data['next_date_achat']);
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

            $qty = (float) $line->getFullQty();

            if ($params['inverse_qty']) {
                $qty *= -1;
            }

            if ($params['qty_to_zero_sauf_acomptes']) {
                $ref_prod = '';
                if ((int) $line->id_product) {
                    $ref_prod = $this->db->getValue('product', 'ref', 'rowid = ' . $line->id_product);
                }
                if (!$line->id_remise_except && $ref_prod !== 'SAV-PCU' && stripos($line->desc, "Urgence") === false) {
                    $qty = 0;
                }
            }

            if ($line->id_product) {
                $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->id_product);
                if (!static::$achat && !$prod->getData('tosell'))
                    $errors[] = 'Le produit ' . $prod->getRef() . ' n\'est plus en vente';
                elseif (static::$achat && !$prod->getData('tobuy'))
                    $errors[] = 'Le produit ' . $prod->getRef() . ' n\'est plus en achat';
            }

            $new_line->validateArray($data);

            $new_line->desc = $line->desc;
            $new_line->tva_tx = $line->tva_tx;
            $new_line->id_product = $line->id_product;
            $new_line->qty = $qty;
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

            $new_line->no_remises_arrieres_auto_create = true;
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
            } else {
                if ($params['is_review'] && ((int) $line->getData('linked_id_object') || (string) $line->getData('linked_object_name'))) {
                    // On  désassocie l'objet lié de l'ancienne ligne dans le cas d'une révision: 
                    $this->db->update($line->getTable(), array(
                        'linked_id_object'   => 0,
                        'linked_object_name' => ''
                            ), '`' . $line->getPrimary() . '` = ' . (int) $line->id);
                }
            }

            $lines_new[(int) $line->id] = (int) $new_line->id;

            // Attribution des équipements si nécessaire: 
            if ($line->isProductSerialisable()) {
                if (!$params['is_clone'] && $line->equipment_required && $new_line->equipment_required) {
                    $equipmentlines = $line->getEquipmentLines();

                    foreach ($equipmentlines as $equipmentLine) {
                        $data = $equipmentLine->getDataArray();

                        if ($params['inverse_prices']) {
                            $data['pu_ht'] *= -1;
                            $data['id_fourn_price'] = 0;
                            $data['pa_ht'] *= -1;
                        }

                        if ($params['is_review']) {
                            $err = $equipmentLine->delete($warnings, true);
                        }

                        if (empty($err)) {
                            $err = $new_line->attributeEquipment($data['id_equipment'], 0, true, false);
                        }
                    }
                }
            }

            // Création des remises pour la ligne en cours:
            $errors = BimpTools::merge_array($errors, $new_line->copyRemisesFromOrigin($line, ((int) $params['inverse_prices'] || (int) $params['inverse_qty']), $params['copy_remises_globales']));
            $errors = BimpTools::merge_array($errors, $new_line->copyRemisesArrieresFromOrigine($line));
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

    public function copyRemisesGlobalesFromOrigin($origin, &$errors = array(), $inverse_price = false)
    {
        if ($this->isLoaded($errors)) {
            if (BimpObject::objectLoaded($origin) && is_a($origin, 'BimpComm')) {
                $cache_key = $this->object_name . '_' . $this->id . '_remises_globales';
                BimpCache::unsetCacheKey($cache_key);

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
                $rgs = array();
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
                    }
                }

                if (!empty($rgs) || !empty($ext_rgs)) {
                    $process_errors = $this->processRemisesGlobales();

                    if (count($process_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($process_errors, 'Erreurs lors du calcul de la répartition des remises globales');
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

    public function createAcompte($amount, $tva_tx, $id_mode_paiement, $id_bank_account = 0, $paye = 1, $date_paiement = null, $use_caisse = false, $num_paiement = '', $nom_emetteur = '', $banque_emetteur = '', &$warnings = array(), $id_rib = 0, $refPaiement = '', &$idFacture = 0)
    {
        global $user, $langs;
        $errors = array();

        $caisse = null;
        $id_caisse = 0;

        if (!$id_mode_paiement) {
            $id_mode_paiement = (int) $this->getData('fk_mode_reglement');
        }

        $type_paiement = '';
        if (preg_match('/^[0-9]+$/', $id_mode_paiement)) {
            $id_mode_paiement = (int) $id_mode_paiement;
            $type_paiement = $this->db->getValue('c_paiement', 'code', '`id` = ' . $id_mode_paiement);
        } else {
            $type_paiement = $id_mode_paiement;
            $id_mode_paiement = (int) $this->db->getValue('c_paiement', 'id', '`code` = \'' . $id_mode_paiement . '\'');
        }

        if (!$this->useCaisseForPayments) {
            $use_caisse = false;
        } elseif (!$use_caisse && in_array($type_paiement, array('LIQ'))) {
            $errors[] = 'Paiement en caisse obligatoire pour les réglements en espèces';
            return $errors;
        }

        if ($paye && $use_caisse) {
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
                $id_bank_account = (int) BimpCore::getConf('id_default_bank_account', 0);
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

        $client = $this->getClientFacture();

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
            $factureA->mode_reglement_id = $id_mode_paiement;
            $factureA->ref_client = $this->getData('ref_client');
            $factureA->modelpdf = 'bimpfact';
            $factureA->fk_account = $id_bank_account;

            if ($id_rib && $this->field_exists('rib_client') && $this->dol_field_exists('rib_client')) {
                $factureA->array_options['options_rib_client'] = $id_rib;
            }
            if ($this->field_exists('ef_type') && $this->dol_field_exists('ef_type')) {
                $factureA->array_options['options_type'] = $this->getData('ef_type');
            }
            if ($this->field_exists('entrepot') && $this->dol_field_exists('entrepot')) {
                $factureA->array_options['options_entrepot'] = $this->getData('entrepot');
            }
            if ($factureA->create($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($factureA), 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
            } else {
                $tva = (float) $tva_tx;
                $ht = $amount / (100 + $tva) * 100;
                $factureA->addline("Acompte", $ht, 1, $tva, null, null, null, 0, null, null, null, null, null, 'HT', null, 1, null, null, null, null, null, null, $ht);
                $user->rights->facture->creer = 1;
                $factureA->validate($user);
                $idFacture = $factureA->id;

                // Création du paiement:
                if ($paye) {
                    BimpTools::loadDolClass('compta/paiement', 'paiement');
                    $payement = new Paiement($this->db->db);
                    $payement->amounts = array($factureA->id => $amount);
                    $payement->ref = $refPaiement;
                    $payement->datepaye = ($date_paiement ? BimpTools::getDateTms($date_paiement) : dol_now());
                    $payement->paiementid = (int) $id_mode_paiement;
                    $payement->num_payment = $num_paiement;
                    if ($payement->create($user) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Des erreurs sont survenues lors de la création du paiement de la facture d\'acompte');
                    } else {
                        // Ajout du paiement au compte bancaire: 
                        if ($payement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_bank_account, $nom_emetteur, $banque_emetteur) < 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Echec de l\'ajout de l\'acompte au compte bancaire ' . $bank_account->bank);
                        }

                        // Enregistrement du paiement caisse: 
                        if ($use_caisse) {
                            $errors = BimpTools::merge_array($errors, $caisse->addPaiement($payement, $factureA->id));
                        }

                        $factureA->set_paid($user);
                    }
                }

                // Création de la remise client: 
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discount = new DiscountAbsolute($this->db->db);
                $discount->description = "Acompte";
                $discount->fk_soc = $factureA->socid;
                $discount->fk_facture_source = $factureA->id;
                $discount->amount_ht = $ht;
                $discount->amount_ttc = $amount;
                $discount->amount_tva = $amount - ($ht);
                $discount->tva_tx = $tva;
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

    public function getClientFacture()
    {
        if ($this->field_exists('id_client_facture') && (int) $this->getData('id_client_facture')) {
            $client = $this->getChildObject('client_facture');
            if (BimpObject::objectLoaded($client)) {
                return $client;
            }
        }

        if ((int) $this->getData('fk_soc')) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client;
            }
        }

        return null;
    }

    public function getClientFactureContactsArray()
    {
        $id_client_facture = BimpTools::getValue('id_client_facture');

        if (is_null($id_client_facture)) {
            $client = $this->getClientFacture();
            if (BimpObject::objectLoaded($client)) {
                $id_client_facture = $client->id;
            }
        }

        if (!(int) $id_client_facture) {
            return array();
        }

        return self::getSocieteContactsArray($id_client_facture);
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

    public function processOperationMasseLine($debut = true)
    {
        $this->procededOperationMasseLine = $debut;
        if (!$debut) {
            $this->dol_object->fetch_lines();
            $this->dol_object->update_price();
            $lines = $this->getLines();
            foreach ($lines as $line) {
                $line->hydrateFromDolObject();
            }
        }
    }

    public function processRemisesGlobales()
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->areLinesEditable() && !$this->processRemisesGlobalesProcessed) {
            $this->processRemisesGlobalesProcessed = true;
            $this->processOperationMasseLine();
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
                        $errors = BimpTools::merge_array($errors, $line->setRemiseGlobalePart($rg, $lines_rate));
                    }
                }
            }

            foreach ($lines as $line) {
                $line->checkRemisesGlobales();
            }
            $this->processOperationMasseLine(false);
            $this->processRemisesGlobalesProcessed = false;
        }

        return $errors;
    }

    public function checkContacts()
    {
        $errors = array();

        if (in_array($this->object_name, array('Bimp_Propal', 'Bimp_Commande', 'Bimp_Facture'))) {
            global $user;
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                // Vérif commercial suivi: 
                $tabConatact = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
                if (count($tabConatact) < 1) {
                    $ok = false;
                    $tabComm = $client->dol_object->getSalesRepresentatives($user);

                    // Il y a un commercial pour ce client
                    if (count($tabComm) > 0) {
                        $this->dol_object->add_contact($tabComm[0]['id'], 'SALESREPFOLL', 'internal');
                        $ok = true;
                        // Il y a un commercial définit par défaut (bimpcore)
                    } elseif ((int) BimpCore::getConf('user_as_default_commercial', null, 'bimpcommercial')) {
                        $this->dol_object->add_contact($user->id, 'SALESREPFOLL', 'internal');
                        $ok = true;
                        // L'objet est une facture et elle a une facture d'origine
                    } elseif ($this->object_name === 'Bimp_Facture' && (int) $this->getData('fk_facture_source')) {
                        $fac_src = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $this->getData('fk_facture_source'));
                        if (BimpObject::objectLoaded($fac_src)) {
                            $contacts = $fac_src->dol_object->getIdContact('internal', 'SALESREPFOLL');
                            if (count($contacts) > 0) {
                                $this->dol_object->add_contact($contacts[0]['id'], 'SALESREPFOLL', 'internal');
                                $ok = true;
                            }
                        }
                    }

                    if (!$ok) {
                        $errors[] = 'Pas de Commercial Suivi';
                    }
                }

                // Vérif contact signataire: 
                $tabConatact = $this->dol_object->getIdContact('internal', 'SALESREPSIGN');
                if (count($tabConatact) < 1) {
                    $this->dol_object->add_contact($user->id, 'SALESREPSIGN', 'internal');
                }
            }
        }
        return $errors;
    }

    public function addLog($text)
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->field_exists('logs')) {
            $logs = (string) $this->getData('logs');
            if ($logs) {
                $logs .= '<br/>';
            }
            global $user, $langs;
            $logs .= ' - <strong>Le ' . date('d / m / Y à H:i') . '</strong> par ' . $user->getFullName($langs) . ': ' . $text;
            $errors = $this->updateField('logs', $logs, null, true);
        }

        return $errors;
    }

    public function addNoteToCommercial($note)
    {
        return $this->addNote($note, null, 0, 0, '', 1, 1, 0, $this->getIdCommercial());
    }

    public function checkRemisesGlobales($echo = false, $create_avoir = false)
    {
        if ($this->isLoaded()) {
            if ($echo) {
                echo BimpTools::ucfirst($this->getLabel()) . ' #' . $this->id . ' - ' . $this->getRef() . ': ';
            }
            $total_rg = round($this->getTotalRemisesGlobalesAmount(), 2);
            $remises_infos = $this->getRemisesInfos();
            $total_rg_lines = round($remises_infos['remises_globales_amount_ttc'], 2);

            if ($total_rg != $total_rg_lines) {
                if ($echo) {
                    echo '<span class="danger">DIFF: Total RG: ' . $total_rg . ' - RG lignes: ' . $total_rg_lines . '</span>';

                    $rtp = 0;

                    if ($this->object_name === 'Bimp_Facture') {
                        $rtp = $this->getRemainToPay();

                        if (($total_rg - $total_rg_lines) > $rtp) {
                            echo '<span class="warning">ATTENTION: différence &gt; reste à payer</span>';
                        }
                    }
                    // Création de l\'avoir correctif: 
                    if ($create_avoir && $this->object_name == 'Bimp_Facture') {
                        $diff = $total_rg - $total_rg_lines;

                        if ($diff > 0 && $diff <= $rtp) {
                            global $user;
                            echo '<br/>Création de l\'avoir: ';

                            // Calcul du montant des lignes de l'avoir:
                            $total_lines = 0;
                            $total_lines_product = 0;
                            $total_lines_service = 0;
                            $lines = $this->getLines('not_text');

                            foreach ($lines as $line) {
                                if ($line->isRemisable()) {
                                    $line_total = (float) $line->getTotalTtcWithoutRemises();
                                    $total_lines += $line_total;
                                    $prod = $line->getProduct();
                                    if (BimpObject::objectLoaded($prod)) {
                                        if ($prod->isTypeProduct()) {
                                            $total_lines_product += $line_total;
                                        } else {
                                            $total_lines_service += $line_total;
                                        }
                                    } else {
                                        if (!(int) $this->db->getValue('facturedet', 'product_type', 'rowid = ' . (int) $line->getData('id_line'))) {
                                            $total_lines_product += $line_total;
                                        } else {
                                            $total_lines_service += $line_total;
                                        }
                                    }
                                }
                            }

                            $lines_rate = ($diff / $total_lines);
                            $product_amount = $total_lines_product * $lines_rate;
                            $service_amount = $total_lines_service * $lines_rate;
                            $total_amount = ($product_amount + $service_amount);

                            if ($total_amount > ($diff + 0.01) || $total_amount < ($diff - 0.01)) {
                                echo BimpRender::renderAlerts('Montants des lignes incorrect - produits: ' . $product_amount . ' - services: ' . $service_amount . ' - total: ' . $total_amount . ' - Attendu: ' . $diff);
                            } else {
                                $errors = array();
                                $warnings = array();

                                // Création avoir: 
                                $avoir = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                                            'fk_facture_source' => $this->id,
                                            'type'              => 0,
                                            'fk_soc'            => (int) $this->getData('fk_soc'),
                                            'entrepot'          => (int) $this->getData('entrepot'),
                                            'contact_id'        => (int) $this->getData('contact_id'),
                                            'fk_account'        => (int) $this->getData('fk_account'),
                                            'ef_type'           => ($this->getData('ef_type') ? $this->getData('ef_type') : 'S'),
                                            'datef'             => date('Y-m-d'),
                                            'libelle'           => 'Régularisation facture ' . $this->getRef(),
                                            'relance_active'    => 0,
                                            'fk_cond_reglement' => 1,
                                            'fk_mode_reglement' => 4
                                                ), true, $errors, $warnings);

                                if (!BimpObject::objectLoaded($avoir)) {
                                    echo '<span class="danger">ECHEC</span>';
                                    if (count($errors)) {
                                        echo BimpRender::renderAlerts($errors);
                                    }
                                    if (count($warnings)) {
                                        echo BimpRender::renderAlerts($warnings, 'warning');
                                    }
                                } else {
                                    echo '<span class="success">Création avoir OK</span>';
                                    // Création des lignes: 
                                    $lines_errors = array();
                                    if ($product_amount) {
                                        $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                                        $new_line->validateArray(array(
                                            'id_obj'      => (int) $avoir->id,
                                            'type'        => ObjectLine::LINE_FREE,
                                            'remisable'   => 0,
                                            'pa_editable' => 0
                                        ));

                                        $new_line->qty = 1;
                                        $new_line->desc = 'Correction remise(s) globale(s) sur les produits';
                                        $new_line->pu_ht = $product_amount * -1;
                                        $new_line->tva_tx = 0;
                                        $new_line->pa_ht = $product_amount * -1;

                                        $line_warnings = array();
                                        $line_errors = $new_line->create($line_warnings, true);

                                        if (!empty($line_errors)) {
                                            $lines_errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne produits');
                                        } elseif ((int) $new_line->getData('id_line')) {
                                            $this->db->update('facturedet', array(
                                                'product_type' => 0
                                                    ), 'rowid = ' . (int) $new_line->getData('id_line'));
                                        }
                                    }

                                    if (empty($lines_errors) && $service_amount) {
                                        $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                                        $new_line->validateArray(array(
                                            'id_obj'      => (int) $avoir->id,
                                            'type'        => ObjectLine::LINE_FREE,
                                            'remisable'   => 0,
                                            'pa_editable' => 0
                                        ));

                                        $new_line->qty = 1;
                                        $new_line->desc = 'Correction remise(s) globale(s) sur les services';
                                        $new_line->pu_ht = $service_amount * -1;
                                        $new_line->tva_tx = 0;
                                        $new_line->pa_ht = $service_amount * -1;

                                        $line_warnings = array();
                                        $line_errors = $new_line->create($line_warnings, true);

                                        if (!empty($line_errors)) {
                                            $lines_errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne services');
                                        } elseif ((int) $new_line->getData('id_line')) {
                                            $this->db->update('facturedet', array(
                                                'product_type' => 1
                                                    ), 'rowid = ' . (int) $new_line->getData('id_line'));
                                        }
                                    }

                                    if (count($lines_errors)) {
                                        echo BimpRender::renderAlerts($lines_errors);
                                        echo '<span class="info">Avoir supprimé</span>';
                                        $avoir->delete($warnings, true);
                                    } else {
                                        echo ' - ';
                                        echo '<span class="success">Création des lignes OK</span>';
                                        setElementElement('facture', 'facture', $avoir->id, $this->id);
                                        if ($avoir->dol_object->validate($user, '', 0, 0) <= 0) {
                                            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir->dol_object), 'Echec de la validation de l\'avoir'), 'danger');
                                        } else {
                                            $avoir->fetch($avoir->id);
                                            // Conversion en remise: 
                                            $conv_errors = $avoir->convertToRemise();
                                            if ($conv_errors) {
                                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($conv_errors, 'ECHEC CONVERSION EN REMISE'));
                                            } else {
                                                echo ' - <span class="success">CONV REM OK</span>';

                                                // Application de la remise à la facture: 
                                                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                                                $discount = new DiscountAbsolute($this->db->db);
                                                $discount->fetch(0, $avoir->id);

                                                if (BimpObject::objectLoaded($discount)) {
                                                    if ($discount->link_to_invoice(0, $this->id) <= 0) {
                                                        echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'ECHEC UTILISATION REMISE'));
                                                    } else {
                                                        echo ' - <span class="success">UTILISATION REM OK</span>';
                                                    }
                                                }

                                                $this->checkIsPaid();
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            echo BimpRender::renderAlerts('PAS DE CREATION D\'AVOIR CAR DIFFERENCE NEGATIVE');
                        }
                    }
                } else {
                    BimpCore::addlog('Erreur Remises globales', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', $this, array(
                        'Total RG'        => $total_rg,
                        'Total RG lignes' => $total_rg_lines
                    ));
                }
            } else {
                if ($echo) {
                    echo '<span class="success">OK</span>';
                }
            }

            if ($echo) {
                echo '<br/>';
            }
        }
    }

    public function checkValidationSolvabilite($client, &$errors = array())
    {
        if ($this->isLoaded()) {
            $emails = BimpCore::getConf('solvabilite_validation_emails', '', 'bimpcommercial');

            if ($emails) {
                if (BimpObject::objectLoaded($client) && is_a($client, 'Bimp_Societe')) {
                    if (!$client->isSolvable($this->object_name)) {
                        global $user;
                        if (!$user->rights->bimpcommercial->admin_recouvrement) {
                            $solv_label = Bimp_Societe::$solvabilites[(int) $client->getData('solvabilite_status')]['label'];
                            $errors[] = 'Vous n\'avez pas la possiblité de valider ' . $this->getLabel('this') . ' car le client est au statut "' . $solv_label . '"<br/>Un e-mail a été envoyé à un responsable pour validation de la commande';

                            $msg = 'Demande de validation d\'une commande dont le client est au statut "' . $solv_label . '"' . "\n\n";
                            $url = $this->getUrl();
                            $msg .= '<a href="' . $url . '">Commande ' . $this->getRef() . '</a>';
                            mailSyn2('Demande de validation de commande Client ' . $client->getData('code_client') . ' - ' . $client->getName(), $emails, '', $msg);
                            return 0;
                        }
                    }
                }
            }
        }

        return 1;
    }

    public function checkMarge(&$success = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $infos = '';
            if ($this->field_exists('marge', $infos)) {
                $margins = $this->getMarginInfosArray();

                $marge = $margins['total_margin'];

                if ((float) $marge !== (float) $this->getData('marge')) {
                    $old_marge = (float) $this->getData('marge');
                    $errors = $this->updateField('marge', $marge);

                    if (!count($errors)) {
                        $success = 'Marge mise à jour. (Ancienne : ' . $old_marge . ' - Nouvelle : ' . $marge . ')';
                    }
                }
            }
        }

        return $errors;
    }

    public function importLinesFromFile(&$warnings = array(), &$success = '')
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if ((int) $this->getData('fk_statut') !== 0) {
            $errors[] = $this->getLabel('this') . ' n\'est plus au statut brouillon';
            return $errors;
        }

        if (isset($_FILES['csv_file'])) {
            $rows = file($_FILES['csv_file']['tmp_name'], FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

            if (empty($rows)) {
                $errors[] = 'Aucune ligne trouvée dans le fichier';
                return $errors;
            }

            $line_instance = $this->getChildObject('lines');
            $i = 0;
            $position = (int) $this->db->getMax($line_instance->getTable(), 'position', 'id_obj = ' . (int) $this->id);
            $position += 1;

            $this->startLineTransaction();

            foreach ($rows as $r) {
                $i++;
                $data = explode(';', $r);

                $ref = $data[0];
                $qty = (float) str_replace(',', '.', str_replace(' ', '', str_replace(' ', '', $data[1])));
                $pu_ht = (float) str_replace(',', '.', str_replace(' ', '', str_replace(' ', '', $data[2])));

                if ($ref) {
                    $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                'ref' => $ref
                    ));

                    if (BimpObject::objectLoaded($product)) {
                        $line = clone $line_instance;

                        $line->id_product = $product->id;
                        $line->qty = $qty;
                        $line->pu_ht = $pu_ht;

                        $line->validateArray(array(
                            'id_obj'   => (int) $this->id,
                            'type'     => ObjectLine::LINE_PRODUCT,
                            'position' => $position
                        ));

                        $line_warnings = array();
                        $line_errors = $line->create($line_warnings, true);

                        if (count($line_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec création de la ligne n° ' . $i);
                        } else {
                            $position++;
                        }
                    } else {
                        $warnings[] = 'Ligne n° ' . $i . ' : aucun produit trouvé pour la référence "' . $ref . '"';
                    }
                } else {
                    $warnings[] = 'Ligne n° ' . $i . ' : référence produit absente';
                }
            }

            $this->stopLineTransaction();
        } else {
            $errors[] = 'Fichier CSV absent';
        }

        return $errors;
    }

    public static function getReportData($date_min, $date_max, $options = array(), &$errors = array())
    {
        if (!$date_min) {
            $errors[] = 'Date de début absente';
        }

        if (!$date_max) {
            $errors[] = 'Date de fin absente';
        }

        if (!count($errors) && $date_max < $date_min) {
            $errors[] = 'La date de fin ne peut être inférieure à la date de début';
        }

        $options = BimpTools::overrideArray(array(
                    'include_ca_details_by_users' => false
                        ), $options);
        $data = array(
            'total'    => array(
                'nb_new_clients'                   => 0,
                'nb_new_clients_by_commerciaux'    => 0,
                'nb_new_propales'                  => 0,
                'nb_new_commandes'                 => 0,
                'nb_new_commandes_for_new_clients' => 0,
                'ca_ttc'                           => 0,
                'ca_ht'                            => 0,
                'marges'                           => 0,
                'achats'                           => 0,
                'tx_marque'                        => ''
            ),
            'users'    => array(),
            'metiers'  => array(),
            'regions'  => array(),
            'secteurs' => array()
        );

        if (count($errors)) {
            return $data;
        }

        BimpObject::loadClass('bimpcore', 'Bimp_Societe');
        $bdb = BimpCache::getBdb();

        // Nb new clients: 
        $where = 'datec >= \'' . $date_min . '\' AND datec <= \'' . $date_max . '\'';
        $where .= ' AND client > 0';
        $data['total']['nb_new_clients'] = (int) $bdb->getCount('societe', $where, 'rowid');

        // Nb new clients créés par les commerciaux
        $sql = 'SELECT COUNT(DISTINCT s.rowid) as nb_rows';
        $sql .= ' FROM llx_societe s, llx_usergroup_user u';
        $sql .= ' WHERE u.fk_usergroup IN ("2")';
        $sql .= ' AND s.client > 0 AND (s.datec >= \'' . $date_min . '\' AND s.datec <= \'' . $date_max . '\') ';
        $sql .= ' AND u.fk_user = s.fk_user_modif';
        $result = $bdb->executeS($sql, 'array');
        $data['total']['nb_new_clients_by_commerciaux'] = (int) (isset($result[0]['nb_rows']) ? $result[0]['nb_rows'] : 0);

        // Nb new clients / users
        $sql = 'SELECT sc.fk_user as id_user, COUNT(DISTINCT s.rowid) as nb_clients';
        $sql .= BimpTools::getSqlFrom('societe', array(
                    'sc' => array(
                        'table' => 'societe_commerciaux',
                        'on'    => 'sc.fk_soc = s.rowid'
                    )
                        ), 's');
        $sql .= ' WHERE';
        $sql .= ' s.datec >= \'' . $date_min . '\' AND s.datec <= \'' . $date_max . '\'';
        $sql .= ' AND s.client > 0';
        $sql .= ' GROUP BY sc.fk_user';
        $rows = $bdb->executeS($sql, 'array');

        foreach ($rows as $r) {
            if (!isset($data['users'][(int) $r['id_user']])) {
                $data['users'][(int) $r['id_user']] = array();
            }

            $data['users'][(int) $r['id_user']]['nb_new_clients'] = (int) $r['nb_clients'];
        }
        // Nb new Devis: 
        $where = 'datec >= \'' . $date_min . '\' AND datec <= \'' . $date_max . '\'';
        $where .= ' AND fk_statut IN (1,2,4)';
        $data['total']['nb_new_propales'] = (int) $bdb->getCount('propal', $where, 'rowid');

        // Nb new Devis / users
        $sql = 'SELECT ec.fk_socpeople as id_user, COUNT(DISTINCT p.rowid) as nb_propales';
        $sql .= BimpTools::getSqlFrom('propal', array(
                    'ec' => array(
                        'table' => 'element_contact',
                        'on'    => 'ec.element_id = p.rowid'
                    )
                        ), 'p');
        $sql .= ' WHERE';
        $sql .= ' ec.fk_c_type_contact IN (SELECT tc.rowid';
        $sql .= BimpTools::getSqlFrom('c_type_contact', null, 'tc');
        $sql .= BimpTools::getSqlWhere(array(
                    'element' => 'propal',
                    'source'  => 'internal',
                    'code'    => 'SALESREPFOLL'
                        ), 'tc');
        $sql .= ')';
        $sql .= ' AND p.datec >= \'' . $date_min . '\' AND p.datec <= \'' . $date_max . '\'';
        $sql .= ' AND p.fk_statut IN (1,2,4)';
        $sql .= ' GROUP BY ec.fk_socpeople';
        $rows = $bdb->executeS($sql, 'array');

        foreach ($rows as $r) {
            if (!isset($data['users'][(int) $r['id_user']])) {
                $data['users'][(int) $r['id_user']] = array();
            }

            $data['users'][(int) $r['id_user']]['nb_new_propales'] = (int) $r['nb_propales'];
        }

        // Nb new Commandes: 
        $where = 'date_creation >= \'' . $date_min . '\' AND date_creation <= \'' . $date_max . '\'';
        $where .= ' AND fk_statut > 0';
        $data['total']['nb_new_commandes'] = (int) $bdb->getCount('commande', $where, 'rowid');

        // Nb new Commandes / users
        $sql = 'SELECT ec.fk_socpeople as id_user, COUNT(DISTINCT c.rowid) as nb_commandes';
        $sql .= BimpTools::getSqlFrom('commande', array(
                    'ec' => array(
                        'table' => 'element_contact',
                        'on'    => 'ec.element_id = c.rowid'
                    )
                        ), 'c');
        $sql .= ' WHERE';
        $sql .= ' ec.fk_c_type_contact IN (SELECT tc.rowid';
        $sql .= BimpTools::getSqlFrom('c_type_contact', null, 'tc');
        $sql .= BimpTools::getSqlWhere(array(
                    'element' => 'commande',
                    'source'  => 'internal',
                    'code'    => 'SALESREPFOLL'
                        ), 'tc');
        $sql .= ')';
        $sql .= ' AND c.date_creation >= \'' . $date_min . '\' AND c.date_creation <= \'' . $date_max . '\'';
        $sql .= ' AND c.fk_statut > 0';
        $sql .= ' GROUP BY ec.fk_socpeople';
        $rows = $bdb->executeS($sql, 'array');

        foreach ($rows as $r) {
            if (!isset($data['users'][(int) $r['id_user']])) {
                $data['users'][(int) $r['id_user']] = array();
            }

            $data['users'][(int) $r['id_user']]['nb_new_commandes'] = (int) $r['nb_commandes'];
        }

        // Nb new Commandes / new clients: 
        $where = 'date_creation >= \'' . $date_min . '\' AND date_creation <= \'' . $date_max . '\'';
        $where .= ' AND fk_statut > 0';
        $where .= ' AND fk_soc IN (';
        $where .= 'SELECT DISTINCT s.rowid FROM ' . MAIN_DB_PREFIX . 'societe s WHERE ';
        $where .= 's.datec >= \'' . $date_min . '\' AND s.datec <= \'' . $date_max . '\'';
        $where .= ' AND s.client > 0';
        $where .= ')';
        $data['total']['nb_new_commandes_for_new_clients'] = (int) $bdb->getCount('commande', $where, 'rowid');

        // Nb new Commandes / new clients / users
        $sql = 'SELECT ec.fk_socpeople as id_user, COUNT(DISTINCT c.rowid) as nb_commandes';
        $sql .= BimpTools::getSqlFrom('commande', array(
                    'ec' => array(
                        'table' => 'element_contact',
                        'on'    => 'ec.element_id = c.rowid'
                    )
                        ), 'c');
        $sql .= ' WHERE';
        $sql .= ' ec.fk_c_type_contact IN (SELECT tc.rowid';
        $sql .= BimpTools::getSqlFrom('c_type_contact', null, 'tc');
        $sql .= BimpTools::getSqlWhere(array(
                    'element' => 'commande',
                    'source'  => 'internal',
                    'code'    => 'SALESREPFOLL'
                        ), 'tc');
        $sql .= ')';
        $sql .= ' AND c.date_creation >= \'' . $date_min . '\' AND c.date_creation <= \'' . $date_max . '\'';
        $sql .= ' AND c.fk_statut > 0';
        $sql .= ' AND c.fk_soc IN (';
        $sql .= 'SELECT DISTINCT s.rowid FROM ' . MAIN_DB_PREFIX . 'societe s WHERE ';
        $sql .= 's.datec >= \'' . $date_min . '\' AND s.datec <= \'' . $date_max . '\'';
        $sql .= ' AND s.client > 0';
        $sql .= ')';
        $sql .= ' GROUP BY ec.fk_socpeople';
        $rows = $bdb->executeS($sql, 'array');

        foreach ($rows as $r) {
            if (!isset($data['users'][(int) $r['id_user']])) {
                $data['users'][(int) $r['id_user']] = array();
            }

            $data['users'][(int) $r['id_user']]['nb_new_commandes_for_new_clients'] = (int) $r['nb_commandes'];
        }
        // CA / marges: 
        $fields = array(
            'f.rowid as id_fac',
            'f.total_ttc',
            'f.total_ht',
            'f.marge_finale_ok',
            'f.total_achat_reval_ok',
            'fef.expertise',
            'ec.fk_socpeople as id_user',
            's.zip',
            's.fk_pays'
        );

        $filters = array(
            'f.datef'              => array(
                'and' => array(
                    array(
                        'operator' => '>=',
                        'value'    => $date_min
                    ),
                    array(
                        'operator' => '<=',
                        'value'    => $date_max
                    )
                )
            ),
            'f.fk_statut'          => array(
                'in' => array(1, 2)
            ),
            'f.type'               => array(
                'in' => array(0, 1, 2)
            ),
            'ec.fk_c_type_contact' => array(
                'in' => BimpTools::getSqlFullSelectQuery('c_type_contact', array('rowid'), array(
                    'element' => 'facture',
                    'source'  => 'internal',
                    'code'    => 'SALESREPFOLL'
                        ), array(
                    'default_alias' => 'tc'
                ))
            )
        );
        $joins = array(
            'fef' => array(
                'table' => 'facture_extrafields',
                'on'    => 'fef.fk_object = f.rowid'
            ),
            'ec'  => array(
                'table' => 'element_contact',
                'on'    => 'ec.element_id = f.rowid'
            ),
            's'   => array(
                'table' => 'societe',
                'on'    => 's.rowid = f.fk_soc'
            )
        );

        $sql = BimpTools::getSqlFullSelectQuery('facture', $fields, $filters, $joins, array(
                    'default_alias' => 'f'
        ));

        $rows = $bdb->executeS($sql, 'array');

        foreach ($rows as $r) {
            $data['total']['ca_ttc'] += (float) $r['total_ttc'];
            $data['total']['ca_ht'] += (float) $r['total_ht'];
            $data['total']['marges'] += (float) $r['marge_finale_ok'];
            $data['total']['achats'] += (float) $r['total_achat_reval_ok'];

            // CA par user: 
            if ((int) $r['id_user']) {
                $id_user = (int) $r['id_user'];

                if (!isset($data['users'][$id_user])) {
                    $data['users'][$id_user] = array();
                }

                if (!isset($data['users'][$id_user]['ca_ttc'])) {
                    $data['users'][$id_user]['ca_ttc'] = 0;
                }
                $data['users'][$id_user]['ca_ttc'] += (float) $r['total_ttc'];

                if (!isset($data['users'][$id_user]['ca_ht'])) {
                    $data['users'][$id_user]['ca_ht'] = 0;
                }
                $data['users'][$id_user]['ca_ht'] += (float) $r['total_ht'];

                if (!isset($data['users'][$id_user]['marges'])) {
                    $data['users'][$id_user]['marges'] = 0;
                }
                $data['users'][$id_user]['marges'] += (float) $r['marge_finale_ok'];

                if (!isset($data['users'][$id_user]['achats'])) {
                    $data['users'][$id_user]['achats'] = 0;
                }
                $data['users'][$id_user]['achats'] += (float) $r['total_achat_reval_ok'];

                // Répartition produits / services: 
                if ($options['include_ca_details_by_users']) {
                    $fields = array(
                        'fdet.subprice as pu_ht',
                        'fdet.qty',
                        'fdet.remise_percent',
                        'fdet.buy_price_ht as pa_ht',
                        'fdet.product_type as prod_type_line',
                        'fl.id as id_bimp_line',
                        'p.fk_product_type as prod_type'
                    );

                    $filters = array(
                        'fdet.fk_facture' => (int) $r['id_fac']
                    );

                    $joins = array(
                        'fl' => array(
                            'table' => 'bimp_facture_line',
                            'on'    => 'fl.id_line = fdet.rowid'
                        ),
                        'p'  => array(
                            'table' => 'product',
                            'on'    => 'p.rowid = fdet.fk_product'
                        )
                    );

                    $sql = BimpTools::getSqlFullSelectQuery('facturedet', $fields, $filters, $joins, array(
                                'default_alias' => 'fdet'
                    ));

                    $lines = $bdb->executeS($sql, 'array');

                    $pv_products = 0;
                    $pv_services = 0;
                    $pa_products = 0;
                    $pa_services = 0;

                    if (is_array($lines)) {
                        foreach ($lines as $l) {
                            $pv = (float) $l['qty'] * (float) $l['pu_ht'] * (1 - (float) $l['remise_percent'] / 100);
                            $pa = (float) $l['qty'] * (float) $l['pa_ht'];

                            $revals = $bdb->executeS(BimpTools::getSqlFullSelectQuery('bimp_revalorisation', array(
                                        'SUM(a.qty * a.amount) as revals'
                                            ), array(
                                        'a.status'          => array('in' => array(1, 10)),
                                        'a.id_facture_line' => (int) $l['id_bimp_line']
                                    )), 'array');

                            if ((float) $revals[0]['revals']) {
                                $pa -= (float) $revals[0]['revals'];
                            }

                            $prod_type = (isset($l['prod_type']) && !is_null($l['prod_type']) ? (int) $l['prod_type'] : $l['prod_type_line']);
                            if ($prod_type == 1) {
                                $pv_services += $pv;
                                $pa_services += $pa;
                            } else {
                                $pv_products += $pv;
                                $pa_products += $pa;
                            }
                        }
                    }

                    if (!isset($data['users'][$id_user]['ca_ht_products'])) {
                        $data['users'][$id_user]['ca_ht_products'] = 0;
                    }
                    $data['users'][$id_user]['ca_ht_products'] += $pv_products;

                    if (!isset($data['users'][$id_user]['ca_ht_services'])) {
                        $data['users'][$id_user]['ca_ht_services'] = 0;
                    }
                    $data['users'][$id_user]['ca_ht_services'] += $pv_services;

                    if (!isset($data['users'][$id_user]['achats_products'])) {
                        $data['users'][$id_user]['achats_products'] = 0;
                    }
                    $data['users'][$id_user]['achats_products'] += $pa_products;

                    if (!isset($data['users'][$id_user]['achats_services'])) {
                        $data['users'][$id_user]['achats_services'] = 0;
                    }
                    $data['users'][$id_user]['achats_services'] += $pa_services;

                    if (!isset($data['users'][$id_user]['marges_products'])) {
                        $data['users'][$id_user]['marges_products'] = 0;
                    }
                    $data['users'][$id_user]['marges_products'] += $pv_products;
                    $data['users'][$id_user]['marges_products'] -= $pa_products;

                    if (!isset($data['users'][$id_user]['marges_services'])) {
                        $data['users'][$id_user]['marges_services'] = 0;
                    }
                    $data['users'][$id_user]['marges_services'] += $pv_services;
                    $data['users'][$id_user]['marges_services'] -= $pa_services;
                }

                // Ca par métier par user: 
                if (!isset($data['users'][$id_user]['metiers'])) {
                    $data['users'][$id_user]['metiers'] = array();
                }

                $exp = (int) $r['expertise'];
                if (!isset($data['users'][$id_user]['metiers'])) {
                    $data['users'][$id_user]['metiers'][$exp] = array(
                        'ca_ttc'    => 0,
                        'ca_ht'     => 0,
                        'marges'    => 0,
                        'achats'    => 0,
                        'tx_marque' => ''
                    );
                }

                $data['users'][$id_user]['metiers'][$exp]['ca_ttc'] += (float) $r['total_ttc'];
                $data['users'][$id_user]['metiers'][$exp]['ca_ht'] += (float) $r['total_ht'];
                $data['users'][$id_user]['metiers'][$exp]['marges'] += (float) $r['marge_finale_ok'];
                $data['users'][$id_user]['metiers'][$exp]['achats'] += (float) $r['total_achat_reval_ok'];
            }

            // CA par région: 
            $region = Bimp_Societe::getRegionCsvValue($r);
            if (!isset($data['regions'][$region])) {
                $data['regions'][$region] = array(
                    'ca_ttc'    => 0,
                    'ca_ht'     => 0,
                    'marges'    => 0,
                    'achats'    => 0,
                    'tx_marque' => ''
                );
            }

            $data['regions'][$region]['ca_ttc'] += (float) $r['total_ttc'];
            $data['regions'][$region]['ca_ht'] += (float) $r['total_ht'];
            $data['regions'][$region]['marges'] += (float) $r['marge_finale_ok'];
            $data['regions'][$region]['achats'] += (float) $r['total_achat_reval_ok'];

            // CA par secteur:
            $secteur = Bimp_Societe::getSecteurCsvValue($r);
            if (!isset($data['secteurs'][$secteur])) {
                $data['secteurs'][$secteur] = array(
                    'ca_ttc'    => 0,
                    'ca_ht'     => 0,
                    'marges'    => 0,
                    'achats'    => 0,
                    'tx_marque' => ''
                );
            }

            $data['secteurs'][$secteur]['ca_ttc'] += (float) $r['total_ttc'];
            $data['secteurs'][$secteur]['ca_ht'] += (float) $r['total_ht'];
            $data['secteurs'][$secteur]['marges'] += (float) $r['marge_finale_ok'];
            $data['secteurs'][$secteur]['achats'] += (float) $r['total_achat_reval_ok'];

            // CA par métier: 
            $exp = (int) $r['expertise'];
            if (!isset($data['metiers'][$exp])) {
                $data['metiers'][$exp] = array(
                    'ca_ttc'    => 0,
                    'ca_ht'     => 0,
                    'marges'    => 0,
                    'achats'    => 0,
                    'tx_marque' => ''
                );
            }

            $data['metiers'][$exp]['ca_ttc'] += (float) $r['total_ttc'];
            $data['metiers'][$exp]['ca_ht'] += (float) $r['total_ht'];
            $data['metiers'][$exp]['marges'] += (float) $r['marge_finale_ok'];
            $data['metiers'][$exp]['achats'] += (float) $r['total_achat_reval_ok'];
        }

        // Calcul Tx Marque: 
        if ((float) $data['total']['ca_ht']) {
            $data['total']['tx_marque'] = ($data['total']['marges'] / $data['total']['ca_ht']) * 100;
        }

        foreach ($data['users'] as $id_user => &$user_data) {
            if (isset($user_data['ca_ht']) && (float) $user_data['ca_ht']) {
                $user_data['tx_marque'] = ($user_data['marges'] / $user_data['ca_ht']) * 100;
            } else {
                $user_data['tx_marque'] = 'Inf.';
            }

            if ($options['include_ca_details_by_users']) {
                if (isset($user_data['ca_ht_products']) && (float) $user_data['ca_ht_products']) {
                    $user_data['tx_marque_products'] = ($user_data['marges_products'] / $user_data['ca_ht_products']) * 100;
                } else {
                    $user_data['tx_marque_products'] = 'Inf.';
                }
                if (isset($user_data['ca_ht_services']) && (float) $user_data['ca_ht_services']) {
                    $user_data['tx_marque_services'] = ($user_data['marges_services'] / $user_data['ca_ht_services']) * 100;
                } else {
                    $user_data['tx_marque_servcies'] = 'Inf.';
                }
            }

            if (isset($user_data['metiers']) && !empty($user_data['metiers'])) {
                foreach ($user_data['metiers'] as $metier => &$metier_data) {
                    if (isset($metier_data['ca_ht']) && (float) $metier_data['ca_ht']) {
                        $metier_data['tx_marque'] = ($metier_data['marges'] / $metier_data['ca_ht']) * 100;
                    } else {
                        $metier_data['tx_marque'] = 'Inf.';
                    }
                }
            }
        }

        foreach ($data['regions'] as $region => &$region_data) {
            if (isset($region_data['ca_ht']) && (float) $region_data['ca_ht']) {
                $region_data['tx_marque'] = ($region_data['marges'] / $region_data['ca_ht']) * 100;
            } else {
                $region_data['tx_marque'] = 'Inf.';
            }
        }

        foreach ($data['secteurs'] as $secteur => &$secteur_data) {
            if (isset($secteur_data['ca_ht']) && (float) $secteur_data['ca_ht']) {
                $secteur_data['tx_marque'] = ($secteur_data['marges'] / $secteur_data['ca_ht']) * 100;
            } else {
                $secteur_data['tx_marque'] = 'Inf.';
            }
        }

        foreach ($data['metiers'] as $metier => &$metier_data) {
            if (isset($metier_data['ca_ht']) && (float) $metier_data['ca_ht']) {
                $metier_data['tx_marque'] = ($metier_data['marges'] / $metier_data['ca_ht']) * 100;
            } else {
                $metier_data['tx_marque'] = 'Inf.';
            }
        }

        return $data;
    }

    //graph

    public function getInfoGraph($graphName = '')
    {
        $data = parent::getInfoGraph($graphName);
        $arrondirEnMinuteGraph = 60 * 12;
        $data["data1"] = array("name" => 'Nb', "type" => "column");
        $data["data2"] = array("name" => 'Total HT', "type" => "column");
        $data["axeX"] = array("title" => "Date", "valueFormatString" => 'DD MMM YYYY');
//        $data["axeY"] = array("title" => 'Nb');
        $data["params"] = array('minutes' => $arrondirEnMinuteGraph);
        $data["title"] = ucfirst($this->getLabel('name_plur')) . ' par jour';

        return $data;
    }

    public function getGraphDatasPoints($params)
    {
        $result = array(1 => array(), 2 => array());

        $fieldTotal = 'total_ht';
        if ($this->object_name == 'Bimp_Propal')
            $dateStr = "UNIX_TIMESTAMP(datep)";
        elseif ($this->object_name == 'Bimp_Facture') {
            $dateStr = "UNIX_TIMESTAMP(datef)";
        } else
            $dateStr = "UNIX_TIMESTAMP(date_commande)";


        $req = 'SELECT count(*) as nb, SUM(' . $fieldTotal . ') as total_ht, ' . $dateStr . ' as timestamp FROM ' . MAIN_DB_PREFIX . $this->params['table'] . ' a ';
        $filter = array();
        $filter['entity'] = getEntity('bimp_conf', 0);
        foreach (json_decode(BimpTools::getPostFieldValue('param_list_filters'), true) as $filterT) {
            if (isset($filterT['filter']) && is_array($filterT['filter']))
                $filter[] = $filterT['filter'];
            elseif (isset($filterT['filter']) && isset($filterT['name']))
                $filter[$filterT['name']] = $filterT['filter'];
        }
        $req .= BimpTools::getSqlWhere($filter);
        $req .= ' GROUP BY ' . $dateStr;
        $sql = $this->db->db->query($req);
        while ($ln = $this->db->db->fetch_object($sql)) {
            $tabDate = array($ln->annee, $ln->month, $ln->day, $ln->hour, $ln->minute);
            $result[1][] = array("x" => "new Date(" . $ln->timestamp * 1000 . ")", "y" => (int) $ln->nb);
            $result[2][] = array("x" => "new Date(" . $ln->timestamp * 1000 . ")", "y" => (int) $ln->total_ht);
        }

        return $result;
    }

    // post process: 

    public function onCreate(&$warnings = array())
    {
        return array();
    }

    public function onDelete(&$warnings = array())
    {
        $errors = array();
        $prev_deleting = $this->isDeleting;
        $this->isDeleting = true;

        $this->startLineTransaction();
        if ($this->isLoaded($warnings)) {
            $lines = $this->getLines();

            // Suppression des objectLines: 
            foreach ($lines as $line) {
                $line_pos = $line->getData('position');
                $line_warnings = array();
                $line->bimp_line_only = true;
                $line_errors = $line->delete($line_warnings, true);

                $line_errors = BimpTools::merge_array($line_warnings, $line_errors);
                if (count($line_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs lors de la suppression de la ligne n°' . $line_pos);
                }
            }

            // Suppression des remises globales: 
            $remisesGlobales = $this->getRemisesGlobales();
            foreach ($remisesGlobales as $rg) {
                $rg_warnings = array();
                $rg_errors = $rg->delete($rg_warnings, true);

                if (count($rg_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($rg_errors, 'Echec de la suppression de la remise globale #' . $rg->id);
                }
            }

            // Suppression des demandes de validation liées à cet objet
            $demandes_a_suppr = array();
            if (BimpCore::isModuleActive('bimpvalidation')) {
                $demandes_a_suppr = BimpValidation::getObjectDemandes($this);
            } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
                $valid_comm = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
                $type_de_piece = $valid_comm::getObjectClass($this);
                $filters = array(
                    'type_de_piece' => (int) $type_de_piece,
                    'id_piece'      => (int) $this->id
                );
                $demandes_a_suppr = BimpCache::getBimpObjectObjects('bimpvalidateorder', 'DemandeValidComm', $filters);
            }

            foreach ($demandes_a_suppr as $d) {
                $dem_warnings = array();
                $dem_errors = $d->delete($dem_warnings, true);

                if (count($dem_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($dem_errors, 'Echec de la suppression de la demande de validation #' . $d->id);
                }
            }
        }

        $this->stopLineTransaction();

        $this->isDeleting = $prev_deleting;
        return $errors;
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
        if ($this->isLoaded() && !$this->isDeleting && !static::$dont_check_parent_on_update && !$this->onChildSaveProcessed) {
            $this->onChildSaveProcessed = true;
            if (is_a($child, 'objectLine')) {
                $this->processRemisesGlobales();
            }
            $this->onChildSaveProcessed = false;
        }
        return array();
    }

    public function onChildDelete($child, $id_child_deleted)
    {
        if ($this->isLoaded() && !$this->isDeleting) {
            if (is_a($child, 'objectLine')) {
                $this->processRemisesGlobales();
            }
        }
        return array();
    }

    // Actions:

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $infos = array();

        $success = BimpTools::ucfirst($this->getLabel('')) . ' validé' . $this->e();

        $success .= ' avec succès';
        $success_callback = 'bimp_reloadPage();';

        global $conf, $langs, $user;

        $result = $this->dol_object->valid($user);

        $obj_warnings = BimpTools::getDolEventsMsgs(array('warnings'));
        if (!empty($obj_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($obj_warnings);
        }

        $obj_infos = BimpTools::getDolEventsMsgs(array('mesgs'));
        if (!empty($obj_infos)) {
            $infos[] = BimpTools::getMsgFromArray($obj_infos);
        }

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

            if (count($obj_errors)) {
                $errors[] = BimpTools::getMsgFromArray($obj_errors);
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'infos'            => $infos,
            'success_callback' => $success_callback
        );
    }

    public function actionModify($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise au statut "Brouillon" effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = '(102) ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!$this->can("edit")) {
            $errors[] = 'Vous n\'avez pas la permission d\'effectuer cette action';
        } elseif (!method_exists($this->dol_object, 'setDraft')) {
            $errors[] = 'Erreur: cette action n\'est pas possible';
        } else {
            global $user;
            BimpTools::cleanDolEventsMsgs();
            if ($this->dol_object->setDraft($user) <= 0) {
                $obj_errors = BimpTools::getErrorsFromDolObject($this->dol_object);
                $obj_errors = BimpTools::merge_array($obj_errors, BimpTools::getDolEventsMsgs(array('errors')));
                $errors[] = BimpTools::getMsgFromArray($obj_errors, 'Echec de la remise au statut "Brouillon"');
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

    public function actionRemoveContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Suppression du contact effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = '(104) ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['id_contact']) || !(int) $data['id_contact']) {
                $errors[] = 'Contact à supprimer non spécifié';
            } else {
                $id_type_contact = (int) $this->db->getValue('element_contact', 'fk_c_type_contact', 'rowid = ' . $data['id_contact']);
                $id_type_commercial = (int) $this->db->getValue('c_type_contact', 'rowid', 'source = \'internal\' AND element = \'' . $this->dol_object->element . '\' AND code = \'SALESREPFOLL\'');
                if ($id_type_contact == $id_type_commercial && !$this->canEditCommercial()) {
                    $errors[] = 'Vous n\'avez pas la permission de changer le commercial ' . $this->getLabel('of_a');
                } else {
                    if ($this->dol_object->delete_contact((int) $data['id_contact']) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression du contact');
                    }
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

        if(!count($warnings))
            $success_callback = 'window.location = \'' . $url . '\'';
        else{
            $success = '<a href="'.$url.'">'.$success.'</a>';
        }

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

    public function actionAddAcompte($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Acompte créé avec succès';

        $paye = isset($data['payee']) ? $data['payee'] : 0;
        $id_mode_paiement = isset($data['id_mode_paiement']) ? $data['id_mode_paiement'] : '';
        $type_paiement = '';
        if (preg_match('/^[0-9]+$/', $id_mode_paiement)) {
            $id_mode_paiement = (int) $id_mode_paiement;
            $type_paiement = $this->db->getValue('c_paiement', 'code', '`id` = ' . $id_mode_paiement);
        } else {
            $type_paiement = $id_mode_paiement;
            $id_mode_paiement = (int) $this->db->getValue('c_paiement', 'id', '`code` = \'' . $id_mode_paiement . '\'');
        }
        $id_bank_account = isset($data['bank_account']) ? (int) $data['bank_account'] : 0;
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $id_rib = 0;

        if (!$data['date']) {
            $errors[] = 'Date de paiement absent';
        }

        if ($type_paiement == 'VIR') {
            BimpObject::loadClass('bimpcommercial', 'Bimp_Paiement');
            if (!Bimp_Paiement::canCreateVirement()) {
                $errors[] = 'Vous n\'avez pas la permission d\'enregistrer des paiements par virement';
            } elseif (!$id_bank_account) {
                $errors[] = "Le compte banqaire est obligatoire pour un virement bancaire";
            }
        } elseif ($type_paiement == 'PRE') {
            $id_rib = (int) BimpTools::getArrayValueFromPath($data, 'id_rib', 0);

            if (!$id_rib) {
                $errors[] = 'Le RIB Client est obligatoire pour le mode de paiement par prélèvement';
            }
        }

        if ($paye && !$id_mode_paiement) {
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

        if (in_array($type_paiement, array('CHQ', 'VIR'))) {
            $num_paiement = isset($data['num_paiement']) ? $data['num_paiement'] : '';
            $nom_emetteur = isset($data['nom_emetteur']) ? $data['nom_emetteur'] : '';
        }

        if ($type_paiement === 'CHQ') {
            $banque_emetteur = isset($data['banque_emetteur']) ? $data['banque_emetteur'] : '';
        }

        if (!count($errors)) {
            $errors = $this->createAcompte($amount, $data['tva_tx'], $id_mode_paiement, $id_bank_account, $paye, $data['date'], $use_caisse, $num_paiement, $nom_emetteur, $banque_emetteur, $warnings, $id_rib);
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
                    $this->fetch($this->id);
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

        if (!BimpObject::objectLoaded($societe)) {
            $errors[] = 'Le client #' . $this->getData('fk_soc') . ' n\'existe plus';
        } else {
            $societe->borne_debut = $data['date_debut'];
            $societe->borne_fin = $data['date_fin'];

            $files = BimpCache::getBimpObjectObjects('bimpcore', 'BimpFile', array(
                        'parent_module'      => 'bimpcore',
                        'parent_object_name' => array(
                            'in' => array('Bimp_Societe', 'Bimp_Client')
                        ),
                        'id_parent'          => $societe->id,
                        'file_name'          => 'Releve_facturation',
                        'deleted'            => 0
            ));

            if ($societe->generateDocument('invoiceStatement', $langs) > 0) {
                if (!empty($files)) {
                    foreach ($files as $file) {
                        $file->updateField('date_create', date('Y-m-d h:i:s'));
                        $file->updateField('date_update', date('Y-m-d h:i:s'));
                    }
                }

                $success = "Relevé de facturation généré avec succès";
            } else {
                $errors[] = "Echec de la génération du relevé de facturation";
            }
            $callback = "window.open('" . DOL_URL_ROOT . "/document.php?modulepart=company&file=" . $societe->id . "%2FReleve_facturation.pdf&entity=1', '_blank');";
        }

        return [
            'success_callback' => $callback,
            'errors'           => $errors,
            'warnings'         => array()
        ];
    }

    public function actionCheckTotal($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Vérification du total effectuée';

        if (method_exists($this->dol_object, 'update_price')) {
            $initial_brouillon = null;

            if ($this->object_name !== 'Bimp_Facture') {
                $initial_brouillon = isset($this->dol_object->brouillon) ? $this->dol_object->brouillon : null;
                $this->dol_object->brouillon = 1;
            }

            $this->dol_object->update_price();

            if ($this->object_name !== 'Bimp_Facture') {
                if (is_null($initial_brouillon)) {
                    unset($this->dol_object->brouillon);
                } else {
                    $this->dol_object->brouillon = $initial_brouillon;
                }
            }
        } else {
            $errors[] = 'Vérification du total non disponible pour ce type de pièce commerciale';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionCheckMarge($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        if ($this->isLoaded()) {
            $errors = $this->checkMarge($success);
            if (!$success) {
                $success = 'Marge déjà à jour';
            } else {
                $sc = 'bimp_reloadPage();';
            }
        } else {
            $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

            if (empty($ids)) {
                $errors[] = 'Aucun' . $this->e() . ' ' . $this->getLabel() . ' sélectionné' . $this->e();
            } else {
                foreach ($ids as $id_obj) {
                    $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_obj);

                    if (BimpObject::objectLoaded($obj)) {
                        $obj_success = '';
                        $obj_errors = $obj->checkMarge($obj_success);

                        if (count($obj_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($obj_errors, ucfirst($obj->getLabel()) . ' ' . $obj->getRef());
                        } elseif ($obj_success) {
                            $success .= ($success ? '<br/>' : '') . ucfirst($obj->getLabel()) . ' ' . $obj->getRef() . ': ' . $obj_success;
                        }
                    } else {
                        $warnings[] = ucfirst($this->getLabel('the')) . ' #' . $id_obj . ' n\'existe plus';
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    // Overrides BimpObject:

    public function reset()
    {
        $this->resetLines();
        parent::reset();
    }

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

        $cur_zone = '';
        $new_zone = '';
        if (static::$use_zone_vente_for_tva && $this->field_exists('zone_vente') && !(int) $this->getData('fk_statut')) {
            $cur_zone = $this->getData('zone_vente');
            // Check zone vente : 
            if ((in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn')) || (int) $this->getData('entrepot') == 164)) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $this->getData('fk_soc'));
                if (BimpObject::objectLoaded($soc)) {
                    $new_zone = $this->getZoneByCountry($soc);
                    if ($new_zone && $new_zone != $cur_zone) {
                        $this->set('zone_vente', $new_zone);
                    }
                }
            }
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ($new_zone && $new_zone != $cur_zone) {
                $this->addObjectLog('Zone de vente changée en auto ' . $this->displayData('zone_vente', 'default', false, true));
            }
            switch ($this->object_name) {
                case 'Bimp_Propal':
                case 'Bimp_Facture':
                case 'Bimp_Commande':
                    // Ajout des contacts clients: 
                    $id_client = $this->getAddContactIdClient();
                    if ($id_client > 0) {
                        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                        if (BimpObject::objectLoaded($client)) {
                            // BILLING2: 
                            $contacts_suivi = $this->dol_object->liste_contact(-1, 'external', 0, 'BILLING2');
                            if (!count($contacts_suivi)) {
                                $id_contact = (int) $client->getData('contact_default');
                                if ($id_contact) {
                                    if ($this->dol_object->add_contact($id_contact, 'BILLING2', 'external') <= 0) {
                                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                                    }
                                }
                            }

                            // SHIPPING: 
                            $contacts_livraison = $this->dol_object->liste_contact(-1, 'external', 1, 'SHIPPING');
                            if (!count($contacts_livraison)) {
                                $id_contact = (int) $client->getData('id_contact_shipment');
                                if ($id_contact) {
                                    if ($this->dol_object->add_contact($id_contact, 'SHIPPING', 'external') <= 0) {
                                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                                    }
                                }
                            }
                        }
                    }
                    break;
            }

            if (method_exists($this->dol_object, 'fetch_lines')) {
                $this->dol_object->fetch_lines();
            }
            $this->checkLines(); // Des lignes ont pu être créées via un trigger.

            if ($origin && $origin_id) {
                $errors = BimpTools::merge_array($errors, $this->createLinesFromOrigin($origin_object, array(), $warnings));
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

            $this->hydrateFromDolObject();
        }

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
        if (BimpTools::isSubmit('import_lines_csv')) {
            // On doit passer par un update à cause de l'envoi de fichier (non possible via une action). 
            return $this->importLinesFromFile($warnings);
        }

        $init_id_entrepot = (int) $this->getInitData('entrepot');
        $init_fk_soc = (int) $this->getInitData('fk_soc');
        $init_zone = '';
        $cur_zone = '';
        $new_zone = '';

        if ($this->getInitData('id_client_facture') != $this->getData('id_client_facture')) {
            $this->addObjectLog('Client facturation modifié, de ' . $this->getInitData('id_client_facture') . ' a ' . $this->getData('id_client_facture'));
        }

        if (static::$use_zone_vente_for_tva && $this->field_exists('zone_vente')) {
            $init_zone = $this->getInitData('zone_vente');
            $cur_zone = $this->getData('zone_vente');

            if (!(int) $this->getData('fk_statut')) {
                if ((in_array($this->object_name, array('Bimp_CommandeFourn', 'Bimp_FactureFourn')) ||
                        $this->getData('entrepot') == 164 || $init_id_entrepot == 164) &&
                        (((int) $this->getData('fk_soc') !== $init_fk_soc) || (int) $this->getData('entrepot') !== $init_id_entrepot)) {
                    $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $this->getData('fk_soc'));
                    if (BimpObject::objectLoaded($soc)) {
                        $new_zone = $this->getZoneByCountry($soc);

                        if ($new_zone && $new_zone != $cur_zone) {
                            $this->set('zone_vente', $new_zone);
                        }
                    }
                }
            }
        }

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($new_zone && $cur_zone != $new_zone) {
                $this->addObjectLog('Zone de vente changée en auto ' . $this->displayData('zone_vente', 'default', 0, 1));
            }

            if ($init_zone && $this->areLinesEditable()) {
                $cur_zone = (int) $this->getData('zone_vente');

                if ($cur_zone != $init_zone && in_array($cur_zone, array(self::BC_ZONE_HORS_UE, self::BC_ZONE_UE))) {
                    $lines_errors = $this->removeLinesTvaTx();
                    if (count($lines_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la suppression des taux de TVA');
                    }
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

    // Méthodes statiques: 

    public static function checkAllObjectLine($id_product, &$sortie = '', $nbMax = 10)
    {
        global $db;
        $tabInfo = $errors = array();
        $sortie = '';

        $tabInfo[] = array('fk_propal', 'llx_propaldet', array('llx_bimp_propal_line', 'llx_bs_sav_propal_line'), 'Bimp_Propal');
        $tabInfo[] = array('fk_commande', 'llx_commandedet', 'llx_bimp_commande_line', 'Bimp_Commande');
        $tabInfo[] = array('fk_facture', 'llx_facturedet', 'llx_bimp_facture_line', 'Bimp_Facture');
        $tabInfo[] = array('fk_commande', 'llx_commande_fournisseurdet', 'llx_bimp_commande_fourn_line', 'Bimp_CommandeFourn');
        $tabInfo[] = array('fk_facture_fourn', 'llx_facture_fourn_det', 'llx_bimp_facture_fourn_line', 'Bimp_FactureFourn');

        foreach ($tabInfo as $info) {
            $i = 0;
            if (!is_array($info[2]))
                $info[2] = array($info[2]);
            $where = array();
            foreach ($info[2] as $table)
                $where[] = 'rowid NOT IN (SELECT id_line FROM ' . $table . ')';
            $req = 'SELECT DISTINCT(`' . $info[0] . '`) as id FROM `' . $info[1] . '` WHERE ' . implode(' AND ', $where) . ' ';

            if ($id_product > 0)
                $req .= ' AND fk_product = ' . $id_product;

            $sql = $db->query($req);
            $tot = $db->num_rows($sql);
            $sql = $db->query($req . ' LIMIT 0,' . $nbMax);
            while ($ln = $db->fetch_object($sql)) {
                $comm = BimpCache::getBimpObjectInstance('bimpcommercial', $info[3], $ln->id);
                if ($comm->isLoaded()) {
                    $errors = BimpTools::merge_array($errors, $comm->checkLines());
                    $i++;
                }

                BimpCache::$cache = array();
            }
            $sortie .= '<br/>fin ' . $i . ' / ' . $tot . ' corrections de ' . $info[1];
        }
        return $errors;
    }
}

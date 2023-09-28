<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

if (defined('BIMP_EXTENDS_VERSION') && BIMP_EXTENDS_VERSION) {
    if (file_exists(DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/versions/' . BIMP_EXTENDS_VERSION . '/objects/BimpComm.class.php')) {
        require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/versions/' . BIMP_EXTENDS_VERSION . '/objects/BimpComm.class.php';
    }
}

if (defined('BIMP_EXTENDS_ENTITY') && BIMP_EXTENDS_ENTITY) {
    if (file_exists(DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/entities/' . BIMP_EXTENDS_ENTITY . '/objects/BimpComm.class.php')) {
        require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/entities/' . BIMP_EXTENDS_ENTITY . '/objects/BimpComm.class.php';
    }
}

if (class_exists('BimpComm_ExtEntity')) {

    class Bimp_CommandeTemp extends BimpComm_ExtEntity
    {
        
    }

} elseif (class_exists('BimpComm_ExtVersion')) {

    class Bimp_CommandeTemp extends BimpComm_ExtVersion
    {
        
    }

} else {

    class Bimp_CommandeTemp extends BimpComm
    {
        
    }

}

class Bimp_Commande extends Bimp_CommandeTemp
{

    public static $no_check_reservations = false;
    public $acomptes_allowed = true;
    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public static $dol_module = 'commande';
    public static $email_type = 'order_send';
    public static $mail_event_code = 'ORDER_SENTBYMAIL';
    public static $element_name = 'order';
    public static $status_list = array(
//        -3 => array('label' => 'Stock insuffisant', 'icon' => 'fas_exclamation-triangle', 'classes' => array('warning')),
        -1 => array('label' => 'Abandonnée', 'icon' => 'fas_times-circle', 'classes' => array('danger')),
        0  => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1  => array('label' => 'Validée', 'icon' => 'fas_check', 'classes' => array('info')),
//        2  => array('label' => 'Acceptée', 'icon' => 'fa_check-circle', 'classes' => array('success')),
        3  => array('label' => 'Fermée', 'icon' => 'fas_times', 'classes' => array('danger')),
    );
    public static $logistique_status = array(
        0 => array('label' => 'A traiter', 'icon' => 'fas_exclamation-circle', 'classes' => array('important')),
        1 => array('label' => 'En cours de traitement', 'icon' => 'fas_cogs', 'classes' => array('info')),
        2 => array('label' => 'Traitée', 'icon' => 'fas_check', 'classes' => array('success')),
        3 => array('label' => 'Compléte', 'icon' => 'fas_crown', 'classes' => array('success')),
        4 => array('label' => 'En attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        5 => array('label' => 'A supprimer', 'icon' => 'fas_exclamation-triangle', 'classes' => array('danger')),
        6 => array('label' => 'Clôturée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
    public static $shipment_status = array(
        0 => array('label' => 'Non expédiée', 'icon' => 'fas_shipping-fast', 'classes' => array('danger')),
        1 => array('label' => 'Expédiée partiellement', 'icon' => 'fas_shipping-fast', 'classes' => array('warning')),
        2 => array('label' => 'Expédiée', 'icon' => 'fas_shipping-fast', 'classes' => array('success')),
        3 => array('label' => 'Livraisons périodiques en cours', 'icon' => 'fas_shipping-fast', 'classes' => array('info')),
    );
    public static $invoice_status = array(
        0 => array('label' => 'Non facturée', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('danger')),
        1 => array('label' => 'Facturée partiellement', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('warning')),
        2 => array('label' => 'Facturée', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('success')),
        3 => array('label' => 'Facturation périodique en cours', 'icon' => 'fas_file-invoice-dollar', 'classes' => array('info'))
    );
    public static $revalorisations = array(
        0 => array('label' => 'NON', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'OUI', 'icon' => 'fas_exclamation', 'classes' => array('warning')),
        2 => array('label' => 'Traité', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $extra_satus = array(
        0 => array('label' => 'Aucune', 'classes' => array('info')),
        1 => array('label' => 'A supprimer', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger')),
        2 => array('label' => 'Non facturable', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger'))
    );
    public static $logistique_active_status = array(1, 2, 3);

    // Gestion des droits et autorisations:

    public function canCreate()
    {
        if (defined('NOLOGIN')) {
            return 1;
        }

        global $user;
        if (isset($user->rights->commande->creer)) {
            return (int) $user->rights->commande->creer;
        }

        return 0;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canSetAction($action)
    {
        global $conf, $user;

        switch ($action) {
            case 'modify':
            case 'validate':
            case 'forceFacturee':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->commande->creer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->commande->order_advance->validate))) {
                    return 1;
                }
                return 0;

            case 'cancel':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->commande->cloturer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->commande->order_advance->annuler))) {
                    return 1;
                }
                return 0;

            case 'sendMail':
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->commande->order_advance->send) {
                    return 1;
                }
                return 0;

            case 'reopen':
            case 'duplicate':
                return (int) $this->can("create");

            case 'processLogitique':
                return 1;

            case 'forceStatus':
                if ((int) $user->admin || $user->rights->bimpcommercial->forcerStatus) {
                    return 1;
                }
                return 0;

            case 'linesFactureQties':
                return $user->rights->bimpcommercial->factureAnticipe;

            case 'sendMailLatePayment':
                if (BimpCore::isModuleActive('bimpvalidation')) {
                    $demande = BimpCache::findBimpObjectInstance('bimpvalidation', 'BV_Demande', array(
                                'status'          => 0,
                                'type_validation' => 'rtp',
                                'type_object'     => 'commande',
                                'id_object'       => $this->id
                                    ), true);

                    if (BimpObject::objectLoaded($demande)) {
                        return $demande->canProcess();
                    }
                } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
                    $vc = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
                    $demande = $vc->demandeExists(ValidComm::OBJ_COMMANDE, (int) $this->id, ValidComm::TYPE_ENCOURS);

                    if ($demande === 0)
                        $demande = $vc->demandeExists(ValidComm::OBJ_COMMANDE, (int) $this->id, ValidComm::TYPE_IMPAYE);

                    // Encours
                    if (is_a($demande, 'DemandeValidComm')) {
                        list($secteur, $class,, $val_euros) = $vc->getObjectParams($this, $errors);
                        return $vc->userCanValidate((int) $user->id, $secteur, ValidComm::TYPE_ENCOURS, $class, $val_euros, $this)
                                or $vc->userCanValidate((int) $user->id, $secteur, ValidComm::TYPE_IMPAYE, $class, $val_euros, $this);
                    }
                }
                return 0;
        }
        return parent::canSetAction($action);
    }

    public function canEditField($field_name)
    {
        global $user;

        switch ($field_name) {
            case 'date_prevue_facturation':
                if ($user->rights->bimpcommercial->admin_recouvrement) {
                    return 1;
                }
                return 0;

            case 'entrepot':
                if ($this->isLoaded() && !$user->rights->bimpcommercial->changeEntrepot) {
                    return 0;
                }
                return 1;
        }
        return parent::canEditField($field_name);
    }

    // Getters booléens:

    public function isActionAllowed($action, &$errors = array())
    {
        global $conf;
        $status = (int) $this->getInitData('fk_statut');
        $invalide_error = 'Le statut actuel de la commande ne permet pas cette opération';

        switch ($action) {
            case 'sendMail':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }
                if ($status <= Commande::STATUS_DRAFT) {
                    $errors[] = $invalide_error;
                    return 0;
                }
                return 1;

            case 'validate':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }

                $soc = $this->getClientFacture();

                if (!BimpObject::objectLoaded($soc)) {
                    $errors[] = 'Client facturation absent ou invalide';
                } elseif ($this->getData('ef_type') != 'M') {
                    $soc->canBuy($errors);
                }

                if ($status !== Commande::STATUS_DRAFT) {
                    $errors[] = $invalide_error;
                } else {
                    $lines = $this->getChildrenObjects('lines');
                    if (!count($lines)) {
                        $errors[] = 'Aucune ligne enregistrée pour cette commande';
                    }
                }
                return (count($errors) ? 0 : 1);

            case 'modify':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }

                if (!in_array($status, array(1, 2, 3))) {
                    $errors[] = $invalide_error;
                    return 0;
                }
                if ($this->isLogistiqueActive()) {
                    $errors[] = 'La logistique est en cours de traitement';
                    return 0;
                }
                return 1;

            case 'reopen':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }
                if (!in_array($status, array(Commande::STATUS_CLOSED, Commande::STATUS_CANCELED))) {
                    $errors[] = $invalide_error;
                    return 0;
                }
                if ((int) $this->getData('logistique_status') === 6) {
                    $errors[] = 'Cette commande a été définitivement clôturée (Commande réimportée depuis 8Sens)';
                    return 0;
                }
                return 1;

            case 'cancel':
                if ($this->isLoaded()) {
                    if ($status !== Commande::STATUS_VALIDATED) {
                        $errors[] = $invalide_error;
                        return 0;
                    }
                    if (!$this->isCancellable($errors)) {
                        return 0;
                    }
                }
                return 1;

            case 'processLogitique':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }
                if (!in_array($status, self::$logistique_active_status)) {
                    $errors[] = 'La logistique n\'est pas active pour cette commande';
                    return 0;
                }
                if ((int) $this->getData('logistique_status') > 0) {
                    $errors[] = 'La logistique est déjà prise en charge pour cette commande';
                    return 0;
                }
                return 1;

            case 'forceStatus':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }
                if (!$this->isLogistiqueActive()) {
                    $errors[] = 'La logistique n\'est pas active';
                    return 0;
                }
                return 1;

            case 'forceFacturee':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }
                if (!$this->isLogistiqueActive()) {
                    $errors[] = 'La logistique n\'est pas active';
                    return 0;
                }
                if ((int) $this->getData('invoice_status') === 2) {
                    $errors[] = 'Cette commande est déjà entièrement facturée';
                    return 0;
                }
                $lines = $this->getLines('not_text');
                $remain_amount = 0;
                foreach ($lines as $line) {
                    $remain_amount += ((float) $line->getFullQty() - (float) $line->getBilledQty()) * (float) $line->getUnitPriceHTWithRemises();
                }
                if ($remain_amount) {
                    $errors[] = 'Le montant restant à facturer n\'est pas égal à 0';
                    return 0;
                }
                return 1;

            case 'sendMailLatePayment':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID de la commande absent';
                    return 0;
                }
                if ($status != Commande::STATUS_DRAFT) {
                    $errors[] = $invalide_error;
                    return 0;
                }

                // A une demande de validation de retard de paiement
                if (BimpCore::isModuleActive('bimpvalidation')) {
                    if (!(int) $this->db->getCount('bv_demande', "type_validation = 'rtp' AND type_object = 'commande' AND id_object = $this->id AND status = 0")) {
                        $errors[] = "Aucune demande de validation pour retard de paiement pour cette commande";
                        return 0;
                    }
                } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
                    $vc = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
                    if ($vc->demandeExists(ValidComm::OBJ_COMMANDE, $this->id, ValidComm::TYPE_IMPAYE) === 0) {
                        $errors[] = "Aucune demande de validation pour cette commande";
                        return 0;
                    }
                }

                if ((int) $this->getData('fk_soc')) {
                    $client = $this->getClientFacture();

                    if ($client->isLoaded()) {
                        if (empty($client->getUnpaidFactures('2019-06-30'))) {
                            $errors[] = "Ce client n'a pas de facture impayée";
                            return 0;
                        }
                    } else {
                        $errors[] = "Client absent";
                        return 0;
                    }
                } else {
                    $errors[] = "Client #" . (int) $this->getData('fk_soc') . " inexistant";
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (!(int) parent::isFieldEditable($field, $force_edit)) {
            return 0;
        }

        switch ($field) {
            case 'entrepot':
//                if (!$force_edit) {
//                    if ($this->isLogistiqueActive()) {
//                        return 0;
//                    }
//                }
                return 1;
        }

        return 1;
    }

    public function isValidatable(&$errors = array())
    {
        parent::isValidatable($errors);
        if (!count($errors)) {
            $this->areLinesValid($errors);

            $client = $this->getChildObject('client');
            $client_facture = $this->getClientFacture();
            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Client absent';
            }

            $this->checkValidationSolvabilite($client, $errors);

            if (!BimpObject::objectLoaded($client_facture)) {
                $errors[] = 'Client facturation absent';
            } elseif ($this->getData('ef_type') != 'M') {
                $client_facture->canBuy($errors);
            }

            if (!count($errors)/* && !defined('NOT_VERIF') */) {
                if ($this->getData('ef_type') !== 'M' && (int) BimpCore::getConf('contact_facturation_required_for_commandes', null, 'bimpcommercial')) {
                    // Vérif du contact facturation: 
                    $tabConatact = $this->dol_object->getIdContact('external', 'BILLING2');
                    if (count($tabConatact) < 1) {
                        $errors[] = 'Contact destinataire email facture absent';
                    } else {
                        global $langs;
                        foreach ($tabConatact as $contactId) {
                            BimpTools::loadDolClass('contact');
                            $contactObj = new Contact($this->db->db);
                            $contactObj->fetch($contactId);
                            if (stripos($contactObj->email, "@") < 1) {
                                $errors[] = 'Le contact facturation "' . $contactObj->getFullName($langs) . '" n\'a pas d\'email';
                            }
                            if (stripos($contactObj->email, "chorusolys@bimp.fr") !== false) {
                                $errors[] = 'Le contact facturation "chorusolys@bimp.fr" ne doit plus être utilisé.
\nLe remplacer par le contact client facturation qui deviendra également par défaut le contact relance de paiement
\nNB : si ces interlocuteurs sont différents, utiliser les champs prévus à cet effet';
                            }
                        }
                    }
                }
                global $user;
                if ($client_facture->getData('outstanding_limit') < 1 /* and (int) $id_cond_a_la_commande != (int) $this->getData('fk_cond_reglement') */ && !$this->asPreuvePaiment()) {
                    if (!in_array($user->id, array(232, 97, 1566, 512, 40))) {
                        $available_discounts = (float) $client_facture->getAvailableDiscountsAmounts();
                        if ($available_discounts < $this->getData('total_ttc') && $this->getData('total_ttc') > 2)
                            $errors[] = "Les clients sans encours autorisé doivent régler à la commande";
                    }
                }
            }


            //ref externe si consigne
            if ($client->getData('consigne_ref_ext') != '' && $this->getData('ref_client') == '') {
                $errors[] = 'Attention la réf client ne peut pas être vide : <br/>' . nl2br($client->getData('consigne_ref_ext'));
            }

            // Check contact LD: 
            if (in_array($this->getData('entrepot'), json_decode(BimpCore::getConf('entrepots_ld', '[]', 'bimpcommercial')))) {
                if (!$this->dol_object->getIdContact('external', 'SHIPPING')) {
                    $errors[] = 'Pour les livraisons directes le contact client est obligatoire';
                }
            }
        }


        return (count($errors) ? 0 : 1);
    }

    public function isUnvalidatable(&$errors = array())
    {
        // Laissé déco (Si appellée via trigger, la commande est déjà remise au statut 0) 
//        $this->isActionAllowed('modify', $errors);

        if (!$this->canSetAction('modify')) {
            $errors[] = 'Vous n\'avez pas la permission';
        }

        return parent::isUnvalidatable($errors);
    }

    public function isLogistiqueActive()
    {
        $forced = $this->getData('status_forced');
        if (in_array((int) $this->getData('fk_statut'), self::$logistique_active_status) &&
                (!in_array((int) $this->getData('logistique_status'), array(0, 6)) || (isset($forced['logistique']) && (int) $forced['logistique']))) {
            return 1;
        }

        return 0;
    }

    public function isCancellable(&$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        if ($this->isLogistiqueActive()) {
            $hasShipped = false;
            $hasInvoiced = false;

            $lines = $this->getLines('not_text');

            if (!empty($lines)) {
                foreach ($lines as $line) {
                    if ((float) $line->getShippedQty(null, false) > 0) {
                        $hasShipped = true;
                        break;
                    }
                }
                foreach ($lines as $line) {
                    if ((float) $line->getBilledQty(null, false) > 0) {
                        $hasInvoiced = true;
                        break;
                    }
                }
            }

            if ($hasShipped) {
                $errors[] = 'Cette commande a été partiellement ou entièrement expédiée.';
            }
            if ($hasInvoiced) {
                $errors[] = 'Cette commande a été partiellement ou entièrement facturée.';
            }
        }

        return (count($errors) ? 0 : 1);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($force_delete) {
            global $rgpd_delete;

            if ($rgpd_delete) {
                return 1;
            }
        }

        if ((int) $this->getData('fk_statut') > 0) {
            return 0;
        }

        return (int) parent::isDeletable($force_delete);
    }

    public function isCommercialOrSup()
    {
        global $user;

        if ($this->isLoaded()) {
            $id_commercial = $this->getCommercialId();

            if ((int) $id_commercial == 0)
                $id_commercial = $this->dol_object->user_author_id;
        }

        // Check si il est admin ou le commercial de cette commande
        if ($user->admin or (int) $id_commercial == (int) $user->id)
            return 1;

        $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_commercial);
        // Check si il est le n+1 du commercial en charge de cette commande
        if ((int) $commercial->getData('fk_user') == (int) $user->id)
            return 1;

        return 0;
    }

    public function isPaiementComptant()
    {
        $cond_paiement_comptant = array('LIVRAISON', 'TIERFAC', 'TIERAV', 'RECEPCOM', 'HALFFAC', 'HALFAV');
        $code_cond_paiement = self::getBdb()->getValue('c_payment_term', 'code', '`active` > 0 and rowid = ' . $this->getData('fk_cond_reglement'));
        if ((int) in_array($code_cond_paiement, $cond_paiement_comptant) == 1)
            return 1;

        // Prélèvement SEPA
        $code_mode_paiement = self::getBdb()->getValue('c_paiement', 'code', '`active` > 0 and id = ' . $this->getData('fk_mode_reglement'));
        if ($code_cond_paiement == '30D' and $code_mode_paiement == 'PRE')
            return 1;

        return 0;
    }

    public function asPreuvePaiment()
    {
        $files = $this->getFilesArray();
        foreach ($files as $file) {
            if (stripos($file, 'Paiement') !== false)
                return 1;
        }
        return 0;
    }

    // Getters:

    public function getData($field, $withDefault = true)
    {
        // Pour mettre à jour mode et cond réglement dans le formulaire en cas de sélection d'un nouveau client ou client facturation.
        if (in_array($field, array('fk_cond_reglement', 'fk_mode_reglement'))) {
            if (BimpTools::getValue('action', '') === 'loadObjectInput' && in_array(BimpTools::getValue('field_name', ''), array('fk_cond_reglement', 'fk_mode_reglement'))) {
                switch ($field) {
                    case 'fk_cond_reglement':
                        return $this->getCondReglementBySociete();

                    case 'fk_mode_reglement':
                        return $this->getModeReglementBySociete();
                }
            }
        }

        return parent::getData($field, $withDefault);
    }

    public function getDefaultListExtraButtons()
    {
        $buttons = parent::getDefaultListExtraButtons();

        if ($this->isLoaded() && $this->isLogistiqueActive()) {
            $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $this->id;
            $buttons[] = array(
                'label'   => 'Page logistique',
                'icon'    => 'fas_truck-loading',
                'onclick' => 'window.open(\'' . $url . '\')'
            );
        }

        return $buttons;
    }

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFCommandes')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
        }

        return ModelePDFCommandes::liste_modeles($this->db->db);
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->commande->dir_output;
    }

    public function getActionsButtons()
    {
        global $conf, $langs, $user;

        $buttons = parent::getActionsButtons();

        if ($this->isLoaded()) {
            $status = (int) $this->getData('fk_statut');
            $ref = $this->getRef();
            $client = $this->getChildObject('client');

            // Envoyer par e-mail
            if ($this->isActionAllowed('sendMail')) {
                if ($this->canSetAction('sendMail')) {
                    $buttons[] = array(
                        'label'   => 'Envoyer par e-mail',
                        'icon'    => 'envelope',
                        'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
                            'form_name' => 'email'
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Envoyer par e-mail',
                        'icon'     => 'envelope',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission'
                    );
                }
            }
            if ($status === 0 && !$this->asPreuvePaiment())
                $buttons[] = array(
                    'label'   => 'Télécharger la preuve de paiement',
                    'icon'    => 'dollar',
                    'onclick' => $this->getJsActionOnclick('preuvePaiment', array(), array(
                        'form_name' => 'preuve_paiement'
                    ))
                );

            // Valider
            if ($status === 0) {
                $errors = array();
                if ($this->isActionAllowed('validate', $errors)) {
                    if ($this->canSetAction('validate')) {

                        if (in_array($this->getData('entrepot'), json_decode(BimpCore::getConf('entrepots_ld', '[]', 'bimpcommercial'))) && !$this->hasDemandsValidations()) {
                            $data['form_name'] = 'livraison';
                        } else {
                            $data['confirm_msg'] = 'Veuillez confirmer la validation de cette commande';
                        }
                        $buttons[] = array(
                            'label'   => 'Valider',
                            'icon'    => 'fas_check',
                            'onclick' => $this->getJsActionOnclick('validate', array(), $data)
                        );
                    } else {
                        $errors[] = 'Vous n\'avez pas la permission';
                    }

                    if (in_array($user->login, array('admin', 'f.martinez', 't.sauron'))) {
                        $buttons[] = array(
                            'label'   => 'Forcer Validation (no triggers)',
                            'icon'    => 'fas_check',
                            'onclick' => $this->getJsActionOnclick('validate', array(
                                'forced_by_dev' => 1
                                    ), array(
                                'confirm_msg' => 'Veuillez confirmer la validation de cette commande - Mode forcé pour dev uniquement (pas de triggers)'
                            ))
                        );
                    }
                }
                if (count($errors)) {
                    $buttons[] = array(
                        'label'    => 'Valider',
                        'icon'     => 'fas_check',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => BimpTools::getMsgFromArray($errors)
                    );
                }
            }

            // Edit
            if ($status == Commande::STATUS_VALIDATED && $this->can("create") && $user->admin) {
                $buttons[] = array(
                    'label'   => 'Modifier',
                    'icon'    => 'undo',
                    'onclick' => $this->getJsActionOnclick('modify', array(), array(
                        'confirm_msg' => strip_tags($langs->trans('ConfirmUnvalidateOrder', $ref))
                    ))
                );
            }


            // Créer intervention
            if ($conf->ficheinter->enabled) {
                $instance = BimpObject::getInstance('bimptechnique', 'BT_ficheInter');
                if ($instance->canCreate()) {
                    $buttons[] = array(
                        'label'   => 'Nouvelle fiche intervention',
                        'icon'    => $instance->params['icon'],
                        'onclick' => $instance->getJsLoadModalForm('default', 'Nouvelle fiche intervention', array(
                            'fields' => array(
                                'fk_soc'    => (int) $client->id,
                                'commandes' => $this->id
                            )
                                ), null, 'open')
                    );
                }
            }


//
//            // Créer contrat
//            if ($conf->contrat->enabled && ($status == Commande::STATUS_VALIDATED || $status == Commande::STATUS_ACCEPTED || $status == Commande::STATUS_CLOSED)) {
//                $langs->load("contracts");
//
//                if ($user->rights->contrat->creer) {
//                    print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/contrat/card.php?action=create&amp;origin=' . $this->dol_object->element . '&amp;originid=' . $this->dol_object->id . '&amp;socid=' . $this->dol_object->socid . '">' . $langs->trans('AddContract') . '</a></div>';
//                }
//            }
//
//            // Expédier
//            $numshipping = 0;
//            if (!empty($conf->expedition->enabled)) {
//                $numshipping = $this->dol_object->nb_expedition();
//
//                if ($status > Commande::STATUS_DRAFT && $status < Commande::STATUS_CLOSED && ($this->dol_object->getNbOfProductsLines() > 0 || !empty($conf->global->STOCK_SUPPORTS_SERVICES))) {
//                    if (($conf->expedition_bon->enabled && $user->rights->expedition->creer) || ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer)) {
//                        if ($user->rights->expedition->creer) {
//                            print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/expedition/shipment.php?id=' . $this->dol_object->id . '">' . $langs->trans('CreateShipment') . '</a></div>';
//                        } else {
//                            print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('CreateShipment') . '</a></div>';
//                        }
//                    } else {
//                        $langs->load("errors");
//                        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")) . '">' . $langs->trans('CreateShipment') . '</a></div>';
//                    }
//                }
//            }
//
            // Réouvrir
            if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
                $buttons[] = array(
                    'label'   => 'Réouvrir',
                    'icon'    => 'undo',
                    'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                        'confirm_msg' => 'Veuillez confirmer la réouverture de ' . $this->getLabel('this')
                    ))
                );
            }
//
//            // Marquer comme expédier
//            if (($status == Commande::STATUS_VALIDATED || $status == Commande::STATUS_ACCEPTED) && $user->rights->commande->cloturer) {
//                print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->dol_object->id . '&amp;action=shipped">' . $langs->trans('ClassifyShipped') . '</a></div>';
//            }
//
            // Prendre en charge logistique:
            if ($this->isActionAllowed('processLogitique')) {
                if ($this->canSetAction('processLogitique')) {
                    $buttons[] = array(
                        'label'   => 'Prendre en charge logistique',
                        'icon'    => 'fas_truck-loading',
                        'onclick' => $this->getJsActionOnclick('processLogitique', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la prise en charge de la logistique pour cette commande'
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Prendre en charge logistique',
                        'icon'     => 'plus-circle',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission'
                    );
                }
            }

            // Cloner
            if ($this->canSetAction('duplicate')) {
                $buttons[] = array(
                    'label'   => 'Cloner',
                    'icon'    => 'copy',
                    'onclick' => $this->getJsActionOnclick('duplicate', array(
                        'date_commande' => date('Y-m-d')
                            ), array(
                        'form_name' => 'duplicate'
                    ))
                );
            }

            // Annuler
            if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
                if ($this->isLogistiqueActive()) {
                    $label = 'Abandonner';
                    $confirm_label = 'abandon';
                } else {
                    $label = 'Annuler';
                    $confirm_label = 'annulation';
                }
                $buttons[] = array(
                    'label'   => $label,
                    'icon'    => 'times',
                    'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                        'confirm_msg' => 'Veuillez confirmer l\\\'' . $confirm_label . ' de la commande ' . $this->getRef()
                    ))
                );
            }

            // Forcer statut: 
            if ($this->isActionAllowed('forceStatus')) {
                if ($this->canSetAction('forceStatus')) {
                    $data = array(
                        'logistique_status' => -1,
                        'shipment_status'   => -1,
                        'invoice_status'    => -1
                    );
                    $forced = $this->getData('status_forced');
                    foreach (array(
                'logistique',
                'shipment',
                'invoice'
                    ) as $status_type) {
                        if (isset($forced[$status_type]) && (int) $forced[$status_type]) {
                            $data[$status_type . '_status'] = (int) $this->getData($status_type . '_status');
                        }
                    }

                    $buttons[] = array(
                        'label'   => 'Forcer un statut',
                        'icon'    => 'far_check-square',
                        'onclick' => $this->getJsActionOnclick('forceStatus', $data, array(
                            'form_name' => 'force_status'
                        )),
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Forcer un statut',
                        'icon'     => 'far_check-square',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission'
                    );
                }
            }

            // Marquer entièrement facturée: 
            if ($this->isActionAllowed('forceFacturee') && $this->canSetAction('forceFacturee')) {
                $buttons[] = array(
                    'label'   => 'Marquer "Entièrement facturée"',
                    'icon'    => 'far_check-square',
                    'onclick' => $this->getJsActionOnclick('forceFacturee', array(), array(
                        'confirm_msg' => 'Veuillez confirmer'
                    ))
                );
            }

            if ($user->admin) {
                $buttons[] = array(
                    'label'   => 'Ancienne version',
                    'icon'    => 'fas_file',
                    'onclick' => 'window.open(\'' . BimpObject::getInstanceUrl($this->dol_object) . '\')'
                );
            }

            // Envoyer mail à l'utilisateur qui a fait une demande de validation
            // pour relancer le client si il y a des impayé
            if ($this->isActionAllowed('sendMailLatePayment') && $this->canSetAction('sendMailLatePayment')) {
                if (BimpCore::isModuleActive('bimpvalidation')) {
                    $demande = BimpCache::findBimpObjectInstance('bimpvalidation', 'BV_Demande', array(
                                'status'          => 0,
                                'type_validation' => 'rtp',
                                'type_object'     => 'commande',
                                'id_object'       => $this->id
                                    ), true);

                    if (BimpObject::objectLoaded($demande)) {
                        $user_ask = $demande->getChildObject('user_demande');
                        if (BimpObject::objectLoaded($user_ask)) {
                            $buttons[] = array(
                                'label'   => 'Signaler retard paiement',
                                'icon'    => 'envelope',
                                'type'    => 'danger',
                                'onclick' => $this->getJsActionOnclick('sendMailLatePayment', array(
                                    'user_ask_firstname' => $user_ask->getData('firstname'),
                                    'user_ask_email'     => $user_ask->getData('email')
                                        ), array(
                                    'confirm_msg' => "Confirmer l\'envoie de mail à " . $user_ask->getName()
                                ))
                            );
                        }
                    }
                } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
                    BimpObject::loadClass('bimpvalidateorder', 'ValidComm');
                    BimpObject::loadClass('bimpvalidateorder', 'DemandeValidComm');
                    $vc = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
                    $demande = $vc->demandeExists(DemandeValidComm::OBJ_COMMANDE, $this->id, DemandeValidComm::TYPE_ENCOURS);
                    if (!is_a($demande, 'DemandeValidComm') || $demande->getData('status') != DemandeValidComm::STATUS_PROCESSING) {
                        $demande = $vc->demandeExists(DemandeValidComm::OBJ_COMMANDE, $this->id, DemandeValidComm::TYPE_IMPAYE);
                    }
                    if (is_a($demande, 'DemandeValidComm') and $demande->getData('status') == DemandeValidComm::STATUS_PROCESSING) {
                        $user_ask = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $demande->getData('id_user_ask'));
                        $confirm_msg = "Confirmer l\'envoie de mail à ";
                        $confirm_msg .= $user_ask->getData('firstname') . ' ' . $user_ask->getData('lastname');
                        if ($user_ask->isLoaded()) {
                            $buttons[] = array(
                                'label'   => 'Signaler retard paiement',
                                'icon'    => 'envelope',
                                'type'    => 'danger',
                                'onclick' => $this->getJsActionOnclick('sendMailLatePayment', array(
                                    'user_ask_firstname' => $user_ask->getData('firstname'),
                                    'user_ask_email'     => $user_ask->getData('email')
                                        ), array(
                                    'confirm_msg' => $confirm_msg
                                ))
                            );
                        }
                    }
                }
            }
        }


        return $buttons;
    }

    public function getListExtraBulkActions()
    {
        $actions = array();

        if ($this->canSetAction('sendEmail')) {
            $actions[] = array(
                'label'   => 'Fichiers PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsBulkActionOnclick('generateBulkPdf', array(), array('single_action' => true))
            );
            $actions[] = array(
                'label'   => 'Fichiers Zip des PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsBulkActionOnclick('generateZipPdf', array(), array('single_action' => true))
            );
        }


        return $actions;
    }

    public function getFilteredListActions()
    {
        $actions = array();

        if ($this->canSetAction('bulkEditField')) {
            $actions[] = array(
                'label'  => 'Annuler',
                'icon'   => 'fas_times',
                'action' => 'cancel'
            );
        }
        if ($this->canSetAction('sendEmail')) {
            $actions[] = array(
                'label'  => 'Fichiers PDF',
                'icon'   => 'fas_file-pdf',
                'action' => 'generateBulkPdf'
            );
            $actions[] = array(
                'label'  => 'Fichiers Zip des PDF',
                'icon'   => 'fas_file-pdf',
                'action' => 'generateZipPdf'
            );
        }

        return $actions;
    }

    public function getProductFournisseursPricesArray()
    {
        if (BimpTools::isSubmit('id_product')) {
            $id_product = (int) BimpTools::getValue('id_product', 0);
        } elseif (BimpTools::isSubmit('fields')) {
            $fields = BimpTools::getValue('fields', array());
            if (isset($fields['id_product'])) {
                $id_product = (int) $fields['id_product'];
            }
        }
        if ($id_product) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            return Bimp_Product::getFournisseursPriceArray($id_product);
        }

        return array(
            0 => ''
        );
    }

    public function getShipmentsArray()
    {
        $shipments = array();

        if ($this->isLoaded()) {
            $cs = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
            foreach ($cs->getList(array(
                'id_commande_client' => (int) $this->id,
                'status'             => 1
            )) as $row) {
                $shipments[(int) $row['id']] = 'Expédition n°' . $row['num_livraison'];
            }
        }

        return $shipments;
    }

    public function getShipmentContactsArray()
    {
        $commande = $this->dol_object;

        $contacts = array(
            0 => 'Addresse de livraison de la commande'
        );

        if (!is_null($commande->socid) && $commande->socid) {
            $where = '`fk_soc` = ' . (int) $commande->socid;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        BimpTools::loadDolClass('contact');

        $bill_contacts = $commande->getIdContact('external', 'BILLING');
        if (!is_null($bill_contacts) && count($bill_contacts)) {
            foreach ($bill_contacts as $id_contact) {
                if (!array_key_exists((int) $id_contact, $contacts)) {
                    $contact = new Contact($this->db->db);
                    if ($contact->fetch((int) $id_contact) > 0) {
                        $contacts[(int) $id_contact] = $contact->firstname . ' ' . $contact->lastname;
                    }
                    unset($contact);
                }
            }
        }

        $ship_contacts = $commande->getIdContact('external', 'SHIPPING');
        if (!is_null($ship_contacts) && count($ship_contacts)) {
            foreach ($ship_contacts as $id_contact) {
                if (!array_key_exists((int) $id_contact, $contacts)) {
                    $contact = new Contact($this->db->db);
                    if ($contact->fetch((int) $id_contact) > 0) {
                        $contacts[(int) $id_contact] = $contact->firstname . ' ' . $contact->lastname;
                    }
                    unset($contact);
                }
            }
        }

        return $contacts;
    }

    public function getInvoicesArray($editable_only = false, $include_empty = false, $empty_label = '')
    {
        if ($this->isLoaded()) {
            $cache_key = 'commande_' . $this->id . '_factures';

            if ($editable_only) {
                $cache_key .= '_editable';
            }

            if (!isset(self::$cache[$cache_key])) {
                $asso = new BimpAssociation($this, 'factures');

                foreach ($asso->getAssociatesList() as $id_facture) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                    if ($facture->isLoaded()) {
                        if ($editable_only) {
                            if ((int) $facture->getData('fk_statut') !== Facture::STATUS_DRAFT) {
                                continue;
                            }

                            $libelle = $facture->getName();
                            self::$cache[$cache_key][(int) $id_facture] = $facture->getRef() . ($libelle ? ' - ' . $libelle : '');
                        }
                    }
                }
            }

            return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
        }

        return array();
    }

    public function getPropalesOriginList()
    {
        if ($this->isLoaded()) {
            $items = BimpTools::getDolObjectLinkedObjectsListByTypes($this->dol_object, $this->db, array('propal'));

            if (isset($items['propal'])) {
                return $items['propal'];
            }
        }

        return array();
    }

    public function getCondReglementBySociete()
    {
        $origin = BimpTools::getPostFieldValue('origin', '');
        $origin_id = (int) BimpTools::getPostFieldValue('origin_id', 0);

        $id_soc_propal = 0;
        if ($origin == 'propal' && $origin_id) {
            $id_soc_propal = (int) $this->db->getValue('propal', 'fk_soc', 'rowid = ' . $origin_id);
        }

        $id_soc = (int) BimpTools::getPostFieldValue('id_client_facture', 0);
        if (!$id_soc) {
            if ((int) $this->getData('id_client_facture')) {
                $id_soc = (int) $this->getData('id_client_facture');
            } else {
                $id_soc = (int) BimpTools::getPostFieldValue('fk_soc', 0);
                if (!$id_soc) {
                    $id_soc = (int) BimpTools::getPostFieldValue('id_client', 0);
                }
                if (!$id_soc && $this->getData('fk_soc') > 0) {
                    $id_soc = $this->getData('fk_soc');
                }
            }
        }

        if ($id_soc_propal && $id_soc_propal == $id_soc) {
            return (int) $this->db->getValue('propal', 'fk_cond_reglement', 'rowid = ' . $origin_id);
        }

        if ($id_soc) {
            $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
            if (BimpObject::objectLoaded($soc)) {
                return (int) $soc->getData('cond_reglement');
            }
        }

        if (isset($this->data['fk_cond_reglement']) && (int) $this->data['fk_cond_reglement']) {
            return (int) $this->data['fk_cond_reglement']; // pas getData() sinon boucle infinie (getCondReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_cond_reglement', 0);
    }

    public function getModeReglementBySociete()
    {
        $origin = BimpTools::getPostFieldValue('origin', '');
        $origin_id = (int) BimpTools::getPostFieldValue('origin_id', 0);

        $id_soc_propal = 0;
        if ($origin == 'propal' && $origin_id) {
            $id_soc_propal = (int) $this->db->getValue('propal', 'fk_soc', 'rowid = ' . $origin_id);
        }

        $id_soc = (int) BimpTools::getPostFieldValue('id_client_facture', 0);
        if (!$id_soc) {
            if ((int) $this->getData('id_client_facture')) {
                $id_soc = (int) $this->getData('id_client_facture');
            } else {
                $id_soc = (int) BimpTools::getPostFieldValue('fk_soc', 0);
                if (!$id_soc) {
                    $id_soc = (int) BimpTools::getPostFieldValue('id_client', 0);
                }
                if (!$id_soc && $this->getData('fk_soc') > 0) {
                    $id_soc = $this->getData('fk_soc');
                }
            }
        }

        if ($id_soc_propal && $id_soc_propal == $id_soc) {
            return (int) $this->db->getValue('propal', 'fk_mode_reglement', 'rowid = ' . $origin_id);
        }

        if ($id_soc) {
            $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
            if (BimpObject::objectLoaded($soc)) {
                return (int) $soc->getData('mode_reglement');
            }
        }

        if (isset($this->data['fk_mode_reglement']) && (int) $this->data['fk_mode_reglement']) {
            return (int) $this->data['fk_mode_reglement']; // pas getData() sinon boucle infinie (getModeReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_mode_reglement', 0);
    }

    public function getShippingIdContact()
    {
        $id_contact = 0;
        if ($this->isLoaded()) {
            $contacts = $this->dol_object->getIdContact('external', 'SHIPPING');
            if (isset($contacts[0]) && $contacts[0]) {
                $id_contact = $contacts[0];
            } else {
                $contacts = $this->dol_object->getIdContact('external', 'CUSTOMER');
                if (isset($contacts[0]) && $contacts[0]) {
                    $id_contact = $contacts[0];
                }
            }
        }

        return $id_contact;
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = parent::renderHeaderExtraLeft();

        if ($this->isLoaded()) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->dol_object->user_author_id);

            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le ' . BimpTools::printDate($this->getData('date_creation'), 'strong');
            $html .= ' par ' . $user->getLink();
            $html .= '</div>';

            $status = (int) $this->getData('fk_statut');
            if ($status >= 1 && (int) $this->getData('fk_user_valid')) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée le ' . BimpTools::printDate($this->getData('date_valid'), 'strong');
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_user_valid'));
                $html .= ' par ' . $user->getLink();
                $html .= '</div>';
            }

            if ($status >= 3) {
                $id_user_cloture = (int) $this->db->getValue($this->getTable(), 'fk_user_cloture', '`rowid` = ' . (int) $this->id);

                if ($id_user_cloture) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user_cloture);
                    if (BimpObject::objectLoaded($user)) {
                        $html .= '<div class="object_header_infos">';
                        $html .= 'Fermée le ' . $this->displayData('date_cloture');
                        $html .= ' par ' . $user->getLink();
                        $html .= '</div>';
                    }
                }
            }

            if ((int) $this->getData('id_user_resp')) {
                $user_resp = $this->getChildObject('user_resp');
                if (BimpObject::objectLoaded($user_resp)) {
                    $html .= '<div class="object_header_infos">';
                    $html .= 'Responsable logistique: ';
                    $html .= $user_resp->getLink();
                    $html .= '</div>';
                }
            }

            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $html .= '<div style="margin-top: 10px">';
                $html .= '<strong>Client: </strong>';
                $html .= $client->getLink();
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ($this->isLoaded()) {
            $forced = $this->getData('status_forced');
            if ((int) $this->getData('extra_status') > 0) {
                $html .= '<br/>';
                $html .= $this->displayData('extra_status');
            }
            if (in_array((int) $this->getData('fk_statut'), self::$logistique_active_status)) {
                $html .= '<br/>Logistique:';
                $html .= $this->displayData('logistique_status');
                if (isset($forced['logistique']) && (int) $forced['logistique']) {
                    $html .= ' (forcé)';
                }
            }
            if ((int) $this->getData('shipment_status') > 0) {
                $html .= '<br/>';
                $html .= $this->displayData('shipment_status');
                if (isset($forced['shipment']) && (int) $forced['shipment']) {
                    $html .= ' (forcé)';
                }
            }
            if ((int) $this->getData('invoice_status') > 0) {
                $html .= '<br/>';
                $html .= $this->displayData('invoice_status');
                if (isset($forced['invoice']) && (int) $forced['invoice']) {
                    $html .= ' (forcé)';
                }
            }
        }

        $html .= parent::renderHeaderStatusExtra();

        return $html;
    }

    public function renderShipmentsInput()
    {
        $shipments = $this->getShipmentsArray();

        $id_shipment = (int) BimpTools::getPostFieldValue('id_shipment', 0);

        if (!$id_shipment) {
            foreach ($shipments as $id_s => $shipment) {
                $id_shipment = $id_s;
                break;
            }
        }

        return BimpInput::renderInput('select', 'id_shipment', $id_shipment, array(
                    'options' => $shipments
        ));
    }

    public function renderShipmentLinesListInput()
    {
        $lines = BimpTools::getPostFieldValue('shipment_lines_list', array());

        if (empty($lines)) {
            $lines = array();
            foreach ($this->getLines('not_text') as $line) {
                if (!$line->isShippable()) {
                    continue;
                }
                $lines[] = $line->id;
            }
        }

        if (is_array($lines)) {
            $lines = implode(',', $lines);
        }

        return '<input type="hidden" value="' . $lines . '" name="shipment_lines_list"/>';
    }

    public function renderShipmentLinesInputs()
    {
        $html = '';
        $id_shipment = (int) BimpTools::getPostFieldValue('id_shipment', 0);

        $lines = BimpTools::getPostFieldValue('shipment_lines_list', array());

        if (is_string($lines)) {
            $lines = explode(',', $lines);
        }

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>N° ligne</th>';
        $html .= '<th>Libellé</th>';
        $html .= '<th>Qté expédition</th>';
        $html .= '<th>Options</th>';
        $html .= '</tr>';

        $html .= '<tbody>';

        foreach ($lines as $id_line) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);
            $full_qty = (float) $line->getFullQty();

            if (!$line->isShippable()) {
                continue;
            }
            if ($line->isLoaded()) {
                $available_qty = (float) $line->getShipmentsQty() - (float) $line->getShippedQty();

                if ($id_shipment) {
                    $shipment_data = $line->getShipmentData($id_shipment);
                    if (isset($shipment_data['qty'])) {
                        $available_qty += (float) $shipment_data['qty'];
                    }
                }

                if ($full_qty >= 0) {
                    if ($available_qty <= 0) {
                        continue;
                    }
                } else {
                    if ($available_qty >= 0) {
                        continue;
                    }
                }

                $product = null;

                if ((int) $line->getData('type') === ObjectLine::LINE_PRODUCT) {
                    $product = $line->getProduct();
                }

                $html .= '<tr>';
                $html .= '<td>';
                $html .= $line->getData('position');
                $html .= '</td>';
                $html .= '<td>';
                $html .= $line->displayLineData('desc');
                $html .= '</td>';
                $html .= '<td>';
                $html .= $line->renderShipmentQtyInput($id_shipment);
                $html .= '</td>';
                $html .= '<td>';

                if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === 0) {
                    if (!$product->isSerialisable()) {
                        if ($full_qty > 0) {
                            $shipment_data = $line->getShipmentData($id_shipment);
                            if (isset($shipment_data['group'])) {
                                $value = (int) $shipment_data['group'];
                            } else {
                                $value = 0;
                            }
                            $html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_group_articles', $value);
                        }
                    } else {
                        $html .= '<div id="shipment_line_' . $line->id . '_equipments" class="shipment_line_equipments">';
                        $html .= $line->renderShipmentEquipmentsInput($id_shipment, null, 'line_' . $line->id . '_shipment_' . $id_shipment . '_qty');
                        $html .= '</div>';
                    }
                }

                $html .= '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</thead>';
        $html .= '</table>';

        return $html;
    }

    public function renderFacturesInput()
    {
        $factures = array(
            array('value' => 0, 'label' => 'Nouvelle facture')
        );

        if ((int) BimpTools::getPostFieldValue('new_facture', 0)) {
            $id_facture = 0;
        } else {
            $comm_factures = $this->getInvoicesArray(true);
            $client_fac_factures = array();
            $client_comm_factures = array();

            $cur_facs = array();
            foreach ($comm_factures as $id_fac => $fac_label) {
                $cur_facs[] = $id_fac;
            }

            $filters = array(
                'fk_soc'    => (int) $this->getData('fk_soc'),
                'type'      => array('in' => array(0, 2)),
                'fk_statut' => 0
            );

            if (!empty($cur_facs)) {
                $filters['rowid'] = array('not_in' => $cur_facs);
            }

            foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', $filters, 'datec', 'DESC') as $fac) {
                $label = $fac->getData('libelle');
                $client_comm_factures[(int) $fac->id] = $fac->getRef() . ($label ? ' - ' . $label : '');
            }

            if ((int) $this->getData('id_client_facture') && ((int) $this->getData('id_client_facture') !== (int) $this->getData('fk_soc'))) {
                $filters['fk_soc'] = (int) $this->getData('id_client_facture');

                foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', $filters, 'datec', 'DESC') as $fac) {
                    $label = $fac->getData('libelle');
                    $client_fac_factures[(int) $fac->id] = $fac->getRef() . ($label ? ' - ' . $label : '');
                }
            }

            if (!empty($comm_factures)) {
                $factures[] = array(
                    'group' => array(
                        'label'   => 'Factures de la commande',
                        'options' => $comm_factures
                    ),
                );
            }

            if (!empty($client_fac_factures)) {
                $client = $this->getChildObject('client_facture');
                $factures[] = array(
                    'group' => array(
                        'label'   => 'Autres factures du client facturation' . (BimpObject::objectLoaded($client) ? ' (' . $client->getName() . ')' : ''),
                        'options' => $client_fac_factures
                    ),
                );
            }

            if (!empty($client_comm_factures)) {
                if (!empty($client_fac_factures)) {
                    $client = $this->getChildObject('client');
                } else {
                    $client = null;
                }

                $factures[] = array(
                    'group' => array(
                        'label'   => 'Autres factures du client' . (!empty($client_fac_factures) ? ' commande' . (BimpObject::objectLoaded($client) ? ' (' . $client->getName() . ')' : '') : ''),
                        'options' => $client_comm_factures
                    ),
                );
            }

            $id_facture = (int) BimpTools::getPostFieldValue('id_facture', 0);
        }

        return BimpInput::renderInput('select', 'id_facture', $id_facture, array(
                    'options' => $factures
        ));
    }

    public function renderFactureLinesListInput()
    {
        BimpObject::loadClass('bimpcommercial', 'ObjectLine');

        $lines = BimpTools::getPostFieldValue('facture_lines_list', array());

        if (empty($lines)) {
            $lines = array();
            foreach ($this->getLines() as $line) {
                $lines[] = $line->id;
            }
        }

        if (is_array($lines)) {
            $lines = implode(',', $lines);
        }

        return '<input type="hidden" value="' . $lines . '" name="facture_lines_list"/>';
    }

    public function renderFactureLinesInputs()
    {
        $html = '';
        $id_facture = (int) BimpTools::getPostFieldValue('id_facture', 0);

        $lines = BimpTools::getPostFieldValue('facture_lines_list', array());

        if (is_string($lines)) {
            $lines = explode(',', $lines);
        }

        $colspan = 6;

        $html .= '<div class="align-right" style="margin-bottom: 5px">';
        $html .= '<span class="btn btn-default" onclick="FactureLinesInputAddAll($(this));">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Tout Ajouter';
        $html .= '</span>';
        $html .= '<span class="btn btn-default" onclick="FactureLinesInputRemoveAll($(this));">';
        $html .= BimpRender::renderIcon('fas_minus-circle', 'iconLeft') . 'Tout retirer';
        $html .= '</span>';
        $html .= '<span class="btn btn-default" onclick="reloadParentInput($(this), \'facture_lines\', [\'id_facture\',\'facture_lines_list\']);">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>N° ligne</th>';
        $html .= '<th>Libellé</th>';
        $html .= '<th>PU HT</th>';
        $html .= '<th>Tx TVA</th>';
        $html .= '<th>Qté</th>';
        $html .= '<th>Correction auto PA</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $has_lines = false;

        $body_html = '';

        foreach ($lines as $id_line) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);

            if (BimpObject::objectLoaded($line)) {
                if ($line->getData('type') === ObjectLine::LINE_TEXT) {
                    $body_html .= '<tr class="facture_line text_line" data-id_line="' . $line->id . '">';
                    $body_html .= '<td>';
                    $body_html .= $line->getData('position');
                    $body_html .= '</td>';
                    $body_html .= '<td colspan="3">';
                    $body_html .= $line->displayLineData('desc');
                    $body_html .= '</td>';
                    $body_html .= '<td>';
                    $body_html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_facture_' . $id_facture . '_include', 1, array(
                                'extra_class' => 'include_line'
                    ));
                    $body_html .= '</td>';
                    $body_html .= '<td>';
                    $body_html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_facture_' . $id_facture . '_not_use', 1, array(
                                'extra_class' => 'include_line',
                                'disabled'    => 'true'
                    ));
                    $body_html .= '</td>';
                    $body_html .= '</tr>';
                    continue;
                }

                $max_qty = (float) $line->getFullQty() - (float) $line->getBilledQty();
                if ($id_facture) {
                    $facture_data = $line->getFactureData($id_facture);
                    if (isset($facture_data['qty'])) {
                        $max_qty += (float) $facture_data['qty'];
                    }
                }

                if (!$max_qty) {
                    continue;
                }

                $product = null;

                if ((int) $line->getData('type') === ObjectLine::LINE_PRODUCT) {
                    $product = $line->getProduct();
                }

                $has_lines = true;

                $body_html .= '<tr class="facture_line product_line" data-id_line="' . $line->id . '">';
                $body_html .= '<td>';
                $body_html .= $line->getData('position');
                $body_html .= '</td>';
                $body_html .= '<td>';
                $body_html .= $line->displayLineData('desc_light');
                $body_html .= '</td>';
                $body_html .= '<td>';
                $body_html .= $line->displayLineData('pu_ht');
                $body_html .= '</td>';
                $body_html .= '<td>';
                $body_html .= $line->displayLineData('tva_tx');
                $body_html .= '</td>';
                $body_html .= '<td' . ($line->getData('fac_periodicity') ? ' style="min-width: 300px"' : '') . '>';
                $body_html .= $line->renderFactureQtyInput($id_facture);
                $body_html .= '</td>';
                $body_html .= '<td>';
                $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                            'id_obj'             => $id_facture,
                            'linked_object_name' => 'commande_line',
                            'linked_id_object'   => (int) $line->id
                ));
                $pa_editable = 1;
                if (BimpObject::objectLoaded($fac_line)) {
                    $pa_editable = (int) $fac_line->getData('pa_editable');
                }
                $body_html .= BimpInput::renderInput('toggle', 'line_' . $line->id . '_facture_' . $id_facture . '_pa_editable', $pa_editable, array(
                            'extra_class' => 'line_facture_pa_editable'
                ));
                $body_html .= '</td>';
                $body_html .= '</tr>';

                if (BimpObject::objectLoaded($product)) {
                    if ($product->isSerialisable()) {
                        $body_html .= '<tr id="facture_line_' . $line->id . '_equipments" class="facture_line_equipments">';
                        $body_html .= '<td colspan="' . $colspan . '">';
                        $body_html .= '<div style="padding-left: 45px;">';
                        $body_html .= $line->renderFactureEquipmentsInput($id_facture, null, 'line_' . $line->id . '_facture_' . $id_facture . '_qty');
                        $body_html .= '</div>';
                        $body_html .= '</td>';
                        $body_html .= '</tr>';
                    }
                }
            }
        }

        if (!$has_lines) {
            $html .= '<tr>';
            $html .= '<td colspan="' . $colspan . '">';
            $html .= BimpRender::renderAlerts('Aucune ligne de commande disponible pour l\'ajout à une facture', 'warning');
            $html .= '</td>';
            $html .= '</tr>';
        } else {
            $html .= $body_html;
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderCommandeFournisseursList()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la commande client absent');
        }

        $html = '';

        $line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
        $lines_list = $line_instance->getList(array(
            'id_obj' => (int) $this->id
                ), null, null, 'id', 'asc', 'array', array('id'));
        $lines = array();

        foreach ($lines_list as $item) {
            $lines[] = (int) $item['id'];
        }

        $fourn_line_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
        $fourn_lines_list = $fourn_line_instance->getList(array(
            'linked_object_name' => 'commande_line',
            'linked_id_object'   => array(
                'in' => $lines
            )
                ), null, null, 'id', 'asc', 'array', array('id'));

        $fourn_lines = array();

        if (!is_null($fourn_lines_list)) {
            foreach ($fourn_lines_list as $item) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $item['id']);
                if (BimpObject::objectLoaded($line)) {
                    $commande_fourn = $line->getParentInstance();
                    if (BimpObject::objectLoaded($commande_fourn)) {
                        $id_fourn = (int) $commande_fourn->getData('fk_soc');
                        if ($id_fourn) {
                            if (!isset($fourn_lines[$id_fourn])) {
                                $fourn_lines[$id_fourn] = array();
                            }

                            if (!isset($fourn_lines[$id_fourn][$commande_fourn->id])) {
                                $fourn_lines[$id_fourn][$commande_fourn->id] = array();
                            }

                            $fourn_lines[$id_fourn][$commande_fourn->id][$line->id] = $line;
                        }
                    }
                }
            }
        }

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Commande fournisseur</th>';
        $html .= '<th>Désignation</th>';
        $html .= '<th>Prix d\'achat HT</th>';
        $html .= '<th>Tx TVA</th>';
        $html .= '<th>Qté</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        if (count($fourn_lines)) {
            foreach ($fourn_lines as $id_fourn => $commandes) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $id_fourn);
                $html .= '<tr>';
                $html .= '<td colspan="6" style="padding: 20px 8px 8px 8px; border-bottom: 1px solid #787878">Fournisseur: ' . $soc->getLink() . '</td>';
                $html .= '</tr>';

                foreach ($commandes as $id_commande_fourn => $comm_lines) {
                    $fl = true;
                    $comm_status = 0;
                    foreach ($comm_lines as $id_line => $line) {
                        $html .= '<tr>';

                        if ($fl) {
                            $commande = $line->getParentInstance();
                            $comm_status = (int) $commande->getData('fk_statut');

                            $html .= '<td rowspan="' . count($comm_lines) . '">';
                            $html .= $commande->getLink() . '&nbsp;&nbsp;&nbsp;';
                            if ((int) $commande->isLogistiqueActive()) {
                                $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commandeFourn&id=' . $commande->id;
                                $html .= '<a href="' . $url . '" target="_blank">';
                                $html .= 'Logistique' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                                $html .= '</a>';
                            }

                            $html .= '<br/>' . $commande->displayData('fk_statut') . '  -  ' . $commande->displayData('invoice_status');
                            $html .= '</td>';
                            $fl = false;
                        }

                        $html .= '<td>' . $line->displayLineData('desc_light') . '</td>';
                        $html .= '<td>' . $line->displayLineData('pu_ht') . '</td>';
                        $html .= '<td>' . $line->displayLineData('tva_tx') . '</td>';
                        $html .= '<td>' . $line->displayQties() . '</td>';

                        $html .= '<td style="text-align: right">';

                        if ($comm_status > 0) {
                            $html .= BimpRender::renderRowButton('Réceptionner', 'fas_arrow-circle-down', $line->getJsLoadModalView('reception', 'Réceptionner'));
                        } else {
                            $comm_cli_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));
                            if (BimpObject::objectLoaded($comm_cli_line)) {
                                $html .= BimpRender::renderRowButton('Retirer de la commande fournisseur', 'fas_times-circle', $comm_cli_line->getJsActionOnclick('cancelCommandeFourn', array(
                                                    'id_commande_fourn_line' => $line->id
                                                        ), array(
                                                    'confirm_msg' => 'Veuillez confirmer le retrait de cet élément de la commande fournisseur'
                                )));
//                            $html .= BimpRender::renderRowButton('Editer', 'fas_times-circle', $comm_cli_line->getJsActionOnclick('editCommandeFourn', array(
//                                'id_commande_fourn' => $id_commande_fourn,
//                                'id_commande_fourn_line' => $line->id
//                            ), array(
//                                'form_name' => 'commande_fourn'
//                            )));
                            }
                        }

                        $html .= '</td>';

                        $html .= '</tr>';
                    }
                }
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="6" style="text-align: center">';
            $html .= BimpRender::renderAlerts('Aucune commande fournisseur associée à cette commande client', 'info');
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderEquipmentsToAddToShipmentCheckList()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la commande client absent');
        }

        $id_shipment = (int) BimpTools::getPostFieldValue('id_shipment', 0);

        if (!$id_shipment) {
            return BimpRender::renderAlerts('Aucune expédition sélectionnée', 'warning');
        }

        $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
        if (!BimpObject::objectLoaded($shipment)) {
            return BimpRender::renderAlerts('L\'expédition d\'ID ' . $id_shipment . ' n\'existe pas');
        }

        self::loadClass('bimpcommercial', 'ObjectLine');

        $selected_reservations = explode(',', BimpTools::getPostFieldValue('reservations', ''));

        $lines = $this->getChildrenObjects('lines', array(
            'type' => ObjectLine::LINE_PRODUCT
        ));

        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

        $lines_equipments = array();

        foreach ($lines as $line) {
            $product = $line->getProduct();
            $full_qty = (float) $line->getFullQty();
            if (BimpObject::objectLoaded($product)) {
                if ($product->isSerialisable()) {
                    $line_equipments = array(
                        'id_line'        => (int) $line->id,
                        'label'          => 'Ligne n°' . $line->getData('position') . ' - ' . $line->displayLineData('desc'),
                        'qty'            => 0,
                        'min'            => 0,
                        'max'            => 0,
                        'equipments_max' => 0,
                        'equipments_min' => 0,
                        'equipments'     => array(),
                        'selected'       => array()
                    );
                    $line_shipments = $line->getData('shipments');
                    $line_total_qty = (int) $line->getShipmentsQty();
                    $remain_qty = $line_total_qty;
                    foreach ($line_shipments as $id_s => $shipment_data) {
                        if ((int) $id_s === $id_shipment) {
                            $line_equipments['qty'] = (int) $shipment_data['qty'];
                            if (isset($shipment_data['equipments'])) {
                                if ($full_qty >= 0) {
                                    $line_equipments['min'] = count($shipment_data['equipments']);
                                } else {
                                    $line_equipments['max'] = (count($shipment_data['equipments']) * -1);
                                }
                            }
                        }
                        $remain_qty -= (int) $shipment_data['qty'];
                    }

                    if ($full_qty >= 0) {
                        $line_equipments['max'] = $line_equipments['qty'] + $remain_qty;
                    } else {
                        $line_equipments['min'] = $line_equipments['qty'] + $remain_qty;
                    }
                    $line_equipments['equipments_max'] = $line_equipments['qty'] - $line_equipments['min'];

                    if ($full_qty >= 0) {
                        // Equipements à expédier: 
                        $list = $reservation->getList(array(
                            'id_commande_client'      => (int) $this->id,
                            'id_commande_client_line' => (int) $line->id,
                            'status'                  => 200,
                            'id_equipment'            => array(
                                'operator' => '>',
                                'value'    => 0
                            )
                                ), null, null, 'id', 'asc', 'array', array('id', 'id_equipment'));
                        if (!is_null($list)) {
                            foreach ($list as $item) {
                                $id_shipment = (int) $line->getEquipmentIdShipment((int) $item['id_equipment']);
                                if (!$id_shipment) {
                                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $item['id_equipment']);
                                    if (BimpObject::objectLoaded($equipment)) {
                                        $line_equipments['equipments'][(int) $equipment->id] = $equipment->getData('serial');
                                        if (in_array($item['id'], $selected_reservations)) {
                                            $line_equipments['selected'][] = (int) $equipment->id;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Equipements retournés: 
                        $equipments_returned = $line->getData('equipments_returned');
                        foreach ($equipments_returned as $id_equipment => $id_entrepot) {
                            $id_shipment = (int) $line->getEquipmentIdShipment((int) $id_equipment);
                            if (!$id_shipment) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (BimpObject::objectLoaded($equipment)) {
                                    $line_equipments['equipments'][(int) $equipment->id] = $equipment->getData('serial');
                                }
                            }
                        }
                    }

                    if (count($line_equipments['equipments'])) {
                        $lines_equipments[] = $line_equipments;
                    }
                }
            }
        }

        $html = '';

        if (empty($lines_equipments)) {
            $html .= BimpRender::renderAlerts('Il n\'y a aucun équipement à attribuer à une expédition pour cette commande', 'warning');
        } else {
            foreach ($lines_equipments as $line_data) {
                $html .= '<div class="line_equipments_container" style="margin-bottom: 30px;" data-id_line="' . $line_data['id_line'] . '">';
                $html .= '<div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #282828; color: #282828">';
                $html .= $line_data['label'];
                $html .= '</div>';

                $html .= '<div style="margin: 15px 0"><span>Qté: assignées à l\'expédition:&nbsp;&nbsp;&nbsp;</span>';
                $html .= BimpInput::renderInput('qty', 'line_' . $line_data['id_line'] . '_qty', $line_data['qty'], array(
                            'data'      => array(
                                'data_type' => 'number',
                                'min'       => $line_data['min'],
                                'max'       => $line_data['max'],
                                'decimals'  => 0,
                                'unsigned'  => 1
                            ),
                            'min_label' => ((float) $line_data['min'] < 0 ? 1 : 0),
                            'max_label' => ((float) $line_data['max'] > 0 ? 1 : 0)
                ));
                $html .= '</div>';

                $html .= BimpInput::renderInput('check_list', 'equipments', $line_data['selected'], array(
                            'items'          => $line_data['equipments'],
                            'max'            => $line_data['equipments_max'],
                            'max_input_name' => 'line_' . $line_data['id_line'] . '_qty',
                            'max_input_abs'  => 1
                ));

                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderLogistiqueEquipmentsView()
    {
        $html = '';

        $html .= BimpRender::renderAlerts('En développement', 'warning');

        return $html;
    }

    public function renderLogistiqueButtons()
    {
        $html = '';

        // Etiquettes: 
        $onclick = $this->getJsActionOnclick('generateVignettes', array(), array(
            'form_name' => 'vignettes'
        ));

        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_sticky-note', 'iconLeft') . 'Etiquettes';
        $html .= '</button>';

        // Nouvelle expédition:
        $expedition = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');

        $onclick = $expedition->getJsLoadModalForm('default', 'Nouvelle expédition', array(
            'fields' => array(
                'id_commande_client' => (int) $this->id,
                'id_entrepot'        => (int) $this->getData('entrepot')
            )
        ));

        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Nouvelle expédition';
        $html .= '</button>';

        // Nouvelle facture: 

        if ($this->isActionAllowed('linesFactureQties') && $this->canSetAction('linesFactureQties')) {
            $client_facture = $this->getClientFacture();
            $onclick = $this->getJsActionOnclick('linesFactureQties', array(
                'new_facture'       => 1,
                'id_client_facture' => (int) (!is_null($client_facture) ? $client_facture->id : 0),
                'id_contact'        => (int) ($client_facture->id === (int) $this->getData('fk_soc') ? $this->dol_object->contact_id : 0),
                'id_cond_reglement' => (int) $this->getData('fk_cond_reglement'),
                'note_public'       => htmlentities(addslashes($this->getData('note_public'))),
                'note_private'      => htmlentities(addslashes($this->getData('note_private'))),
                    ), array(
                'form_name'      => 'invoice',
                'on_form_submit' => 'function ($form, extra_data) { return onFactureFormSubmit($form, extra_data); }',
                'modal_format'   => 'large'
            ));

            $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Nouvelle facture anticipée';
            $html .= '</button>';
        }

        // Ajout ligne: 
        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');

        $onclick = $line->getJsLoadModalForm('line_forced', 'Ajout d\\\'une ligne de commande supplémentaire', array(
            'fields' => array(
                'id_obj' => (int) $this->id
            )
        ));
        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter une ligne de commande';
        $html .= '</button>';

        // Attribuer équipements:   
//        $onclick = $this->getJsLoadModalView('logistique_equipments', 'Attribuer des équipements');
//
//        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
//        $html .= BimpRender::renderIcon('fas_desktop', 'iconLeft') . 'Attribuer des équipements';
//        $html .= '</button>';
        // Statuts sélectionnés: 
        $items = array();

        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $this->id . ', 2);">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'A réserver</button>';
        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $this->id . ', 200);">' . BimpRender::renderIcon('fas_lock', 'iconLeft') . 'Réserver</button>';
        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $this->id . ', 4);">' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconLeft') . 'En cours d\'appro. interne</button>';
        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsStatus($(this), ' . $this->id . ', 0);">' . BimpRender::renderIcon('fas_undo', 'iconLeft') . 'Réinitialiser</button>';
        $items[] = '<button class="btn btn-light-default" onclick="setSelectedCommandeLinesReservationsEquipmentsToShipment($(this), ' . $this->id . ');">' . BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Attribuer les équipements à une expédition</button>';

        $html .= BimpRender::renderDropDownButton('Status sélectionnés', $items, array(
                    'icon'       => 'far_check-square',
                    'menu_right' => true
        ));

        $html .= '<div>';
        $html .= '<span class="btn btn-default btn-small" onclick="selectAllCommandeLinesReservationsStatus()">';
        $html .= BimpRender::renderIcon('fas_check-square', 'iconLeft') . 'Séctionner tous les statuts';
        $html .= '</span>';
        $html .= '<span class="btn btn-default btn-small" onclick="unselectAllCommandeLinesReservationsStatus()">';
        $html .= BimpRender::renderIcon('far_square', 'iconLeft') . 'Désélectionner tous les statuts';
        $html .= '</span>';
        $html .= '</div>';

        return $html;
    }

    public function renderLogistiqueLink()
    {
        $html = '';
        if ($this->isLogistiqueActive()) {
            $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $this->id;
            $html .= '<a href="' . $url . '" target="_blank">' . BimpRender::renderIcon('fas_truck-loading', 'iconLeft') . 'Logistique</a>';
        }
        return $html;
    }

    public function renderLinkedObjectsTable($htmlP = '')
    {
        $htmlP = "";
        $db = $this->db->db;

        if ($this->isLoaded()) {
            $sql = $db->query("SELECT rowid FROM `llx_synopsisdemandeinterv` WHERE `fk_commande` = " . $this->id);
            if ($sql) {
                while ($ln = $db->fetch_object($sql)) {
                    $inter = BimpCache::getBimpObjectInstance("bimpfichinter", 'Bimp_Demandinter', $ln->rowid);
                    $icon = $inter->params['icon'];
                    $htmlP .= '<tr>';
                    $htmlP .= '<td><strong>' . BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($inter->getLabel()) . '</strong></td>';
                    $htmlP .= '<td>' . $inter->getNomUrl(0) . '</td>';
                    $htmlP .= '<td>' . $inter->displayData("date_valid") . '</td>';
                    $htmlP .= '<td>' . $inter->displayData("total_ht") . '</td>';
                    $htmlP .= '<td>' . $inter->displayData("fk_statut") . '</td>';
                    $htmlP .= '</tr>';
                }
            } else
                $htmlP .= BimpRender::renderAlerts('Probléme avec les DI');

            $sql = $db->query("SELECT rowid FROM `llx_synopsis_fichinter` WHERE `fk_commande` = " . $this->id);
            if ($sql) {
                while ($ln = $db->fetch_object($sql)) {
                    $inter = BimpCache::getBimpObjectInstance("bimpfichinter", 'Bimp_Fichinter', $ln->rowid);
                    $icon = $inter->params['icon'];
                    $htmlP .= '<tr>';
                    $htmlP .= '<td><strong>' . BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($inter->getLabel()) . '</strong></td>';
                    $htmlP .= '<td>' . $inter->getNomUrl(0) . '</td>';
                    $htmlP .= '<td>' . $inter->displayData("date_valid") . '</td>';
                    $htmlP .= '<td>' . $inter->displayData("total_ht") . '</td>';
                    $htmlP .= '<td>' . $inter->displayData("fk_statut") . '</td>';
                    $htmlP .= '</tr>';
                }
            } else
                $htmlP .= BimpRender::renderAlerts('Probléme avec les FI');
        }

        $html = parent::renderLinkedObjectsTable($htmlP);

        return $html;
    }

    public function renderExtraFile($withThisObject = true)
    {
        $html = parent::renderExtraFile($withThisObject);

        if ($this->isLoaded()) {
            $sql = $this->db->db->query("SELECT rowid FROM `llx_synopsisdemandeinterv` WHERE `fk_commande` = " . $this->id);
            if ($sql) {
                while ($ln = $this->db->db->fetch_object($sql)) {
                    $objT = BimpCache::getBimpObjectInstance("bimpfichinter", 'Bimp_Demandinter', $ln->rowid);
                    if ($objT->isLoaded()) {
                        $html .= $this->renderListFileForObject($objT);
                    }
                }
            } else
                $html .= BimpRender::renderAlerts('Probléme avec les DI');

            $sql = $this->db->db->query("SELECT rowid FROM `llx_synopsis_fichinter` WHERE `fk_commande` = " . $this->id);
            if ($sql) {
                while ($ln = $this->db->db->fetch_object($sql)) {
                    $objT = BimpCache::getBimpObjectInstance("bimpfichinter", 'Bimp_Fichinter', $ln->rowid);
                    if ($objT->isLoaded()) {
                        $html .= $this->renderListFileForObject($objT);
                    }
                }
            } else
                $html .= BimpRender::renderAlerts('Probléme avec les FI');
        }


        return $html;
    }

    public function renderTotalsPanel()
    {
        $html = '';

        $total_ht_fq = 0;
        $total_tva_fq = 0;
        $total_ttc_fq = 0;

        $check_qty_modif = ($this->getData('fk_statut') > 0 && $this->getData('logistique_status') > 0);

        if ($check_qty_modif) {
            $lines = $this->getLines('not_text');

            foreach ($lines as $line) {
                $line_total_ht = $line->getTotalHTWithRemises(true);
                $line_total_ttc = $line->getTotalTTC(true);

                $total_ht_fq += $line_total_ht;
                $total_ttc_fq += $line_total_ttc;
                $total_tva_fq += ($line_total_ttc - $line_total_ht);
            }
        }

        $html .= '<table class="bimp_list_table">';
        $html .= '<tbody class="headers_col">';
        $html .= '<tr>';
        $html .= '<th>Remises</th>';
        $html .= '<td>';
        $html .= $this->displayTotalRemises();
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Total HT</th>';
        $html .= '<td>';
        $html .= $this->displayData('total_ht', 'default', false);

        if ($check_qty_modif && (float) $this->getData('total_ht') !== $total_ht_fq) {
            $html .= '<br/><span class="important">';
            $html .= BimpTools::displayMoneyValue($total_ht_fq, 'EUR', 0, 0, 0, 2, 1);
            $html .= '</span>';
        }

        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Total TVA</th>';
        $html .= '<td>';
        $html .= $this->displayData('total_tva', 'default', false);

        if ($check_qty_modif && (float) $this->getData('total_tva') !== $total_tva_fq) {
            $html .= '<br/><span class="important">';
            $html .= BimpTools::displayMoneyValue($total_tva_fq, 'EUR', 0, 0, 0, 2, 1);
            $html .= '</span>';
        }

        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Total TTC</th>';
        $html .= '<td>';
        $html .= $this->displayData('total_ttc', 'default', false);

        if ($check_qty_modif && (float) $this->getData('total_ttc') !== $total_ttc_fq) {
            $html .= '<br/><span class="important">';
            $html .= BimpTools::displayMoneyValue($total_ttc_fq, 'EUR', 0, 0, 0, 2, 1);
            $html .= '</span>';
        }

        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $title = BimpRender::renderIcon('fas_euro-sign', 'iconLeft') . 'Montants totaux';

        return BimpRender::renderPanel($title, $html, '', array(
                    'type'     => 'secondary',
                    'foldable' => true
        ));
    }

    // Traitements divers:

    public function createReservations()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $lines = $this->getChildrenObjects('lines');

            foreach ($lines as $line) {
                $errors = BimpTools::merge_array($errors, $line->checkReservations());
            }
        } else {
            $errors[] = 'ID de la commande absent';
        }

        return $errors;
    }

    public function deleteReservations()
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $reservations = BimpCache::getBimpObjectObjects('bimpreservation', 'BR_Reservation', array(
                    'type'               => 1,
                    'id_commande_client' => (int) $this->id,
                    'status'             => array(
                        'operator' => '<',
                        'value'    => 300
                    )
        ));

        foreach ($reservations as $id_reservation => $reservation) {
            $res_warnings = array();
            $res_errors = $reservation->delete($res_warnings, true);

            if (count($res_warnings)) {
                $errors[] = BimpTools::getMsgFromArray($res_warnings, 'Erreurs suite à la suppression ' . $reservation->getLabel('of_the') . ' #' . $id_reservation);
            }

            if (count($res_errors)) {
                $errors[] = BimpTools::getMsgFromArray($res_warnings, 'Echec de la suppression ' . $reservation->getLabel('of_the') . ' #' . $id_reservation);
            }
        }

        return $errors;
    }

    public function addReturnFromLine(&$warnings, $id_line, $data)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande absent';
            return $errors;
        }

        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
        if (!BimpObject::ObjectLoaded($line)) {
            $errors[] = 'La ligne de commande client d\'ID ' . $id_line . ' n\'existe pas';
            return $errors;
        }

        if ((float) $line->getFullQty() < 0) {
            $errors[] = 'Cette ligne est déjà un retour produit';
            return $errors;
        }

        $isSerialisable = $line->isProductSerialisable();
        $equipments = array();
        $qty = 0;

        $new_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                    'linked_object_name' => 'return_of_commande_line',
                    'linked_id_object'   => (int) $line->id
                        ), true);

        if ($isSerialisable) {
            if (!isset($data['equipments']) || empty($data['equipments'])) {
                $errors[] = 'Aucun équipement retourné sélectionné';
                return $errors;
            }

            foreach ($data['equipments'] as $equipment_data) {
                if (!isset($equipment_data['id_equipment']) || !(int) $equipment_data['id_equipment']) {
                    continue;
                }
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $equipment_data['id_equipment']);
                if (!BimpObject::ObjectLoaded($equipment)) {
                    $errors[] = 'L\'équipement d\'ID ' . $equipment_data['id_equipment'] . 'n\'existe pas';
                } else {
                    $id_shipment = (int) $line->getEquipmentIdShipment($equipment->id);
                    if (!$id_shipment) {
                        $errors[] = 'L\'équipement ' . $equipment->getData('serial') . ' n\'a pas encore été expédié';
                    } else {
                        $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
                        if (!BimpObject::objectLoaded($shipment)) {
                            $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" est attributé à une expédition qui n\'existe plus';
                        } else {
                            if ((int) $shipment->getData('status') !== BL_CommandeShipment::BLCS_EXPEDIEE) {
                                $errors[] = 'L\'équipement "' . $equipment->getData('serial') . '" n\'a pas encore été expédié (Expédition n°' . $shipment->getData('num_livraison') . ' non validée)';
                            } else {
                                $eq_errors = $line->checkReturnedEquipment((int) $equipment->id);
                                if (count($eq_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($eq_errors);
                                } else {
                                    $equipments[] = array(
                                        'id_equipment' => (int) $equipment->id,
                                        'id_entrepot'  => isset($equipment_data['id_entrepot']) ? (int) $equipment_data['id_entrepot'] : 0
                                    );
                                }
                            }
                        }
                    }
                }
            }

            $qty = count($equipments);
        } else {
            if (!isset($data['qty']) || !(float) $data['qty']) {
                $errors[] = 'Quantités absentes ou nulles';
            } else {
                $qty = (float) $data['qty'];
            }
        }

        if (!count($errors) && $qty) {
            if (BimpObject::ObjectLoaded($new_line)) {
                $new_line->qty += ($qty * -1);
                $line_warnings = array();
                $line_errors = $new_line->update($line_warnings, true);
                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs suite à la mise à jour des quantités de la ligne de retour');
                }
                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour des quantités de la ligne de retour');
                }
            } else {
                $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
                $new_line->id_product = $line->id_product;
                $new_line->qty = ($qty * -1);
                $new_line->pu_ht = $line->getUnitPriceHTWithRemises();
                $new_line->tva_tx = $line->tva_tx;
                $new_line->pa_ht = $line->pa_ht;
                $new_line->id_fourn_price = $line->id_fourn_price;

                $new_line->validateArray(array(
                    'id_obj'             => (int) $this->id,
                    'type'               => $line->getData('type'),
                    'remisable'          => 0,
                    'hide_product_label' => $line->getData('hide_product_label'),
                    'linked_object_name' => 'return_of_commande_line',
                    'linked_id_object'   => (int) $line->id
                ));

                $line_warnings = array();
                $line_errors = $new_line->create($line_warnings, true);
                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs suite à la création de la ligne de retour');
                }
                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne de retour');
                }
            }

            if (!count($errors) && $isSerialisable && count($equipments)) {
                $line_warnings = array();
                $line_errors = $new_line->addReturnedEquipments($line_warnings, $equipments);
                $line_errors = BimpTools::merge_array($line_errors, $line_warnings);
                if (count($line_errors)) {
                    $msg = 'Erreur lors de l\'attribution ';
                    if (count($equipments > 1)) {
                        $msg .= 'des équipements';
                    } else {
                        $msg .= 'de l\'équipement';
                    }
                    $warnings[] = BimpTools::getMsgFromArray($line_errors, $msg);
                }
            }
        }

        return $errors;
    }

    public function cancel(&$warnings = array())
    {
        $errors = array();

        if ($this->isCancellable($errors)) {
            if ($this->getData('fk_statut') === Commande::STATUS_VALIDATED) {
                if ($this->dol_object->cancel() < 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Echec de l\'annulation de la commande');
                }
            }

            if (!count($errors)) {
                $warnings = BimpTools::merge_array($warnings, $this->deleteReservations());

                $lines = $this->getLines('not_text');
                foreach ($lines as $line) {
                    $line_warnings = $line->updateField('qty_modif', (float) ($line->qty * -1), null, true);
                    if (count($line_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n°' . $line->getData('position'));
                    }
                    $line->checkQties();
                }
            }

            $this->updateField('status_forced', array(), null, true);
            $this->updateField('logistique_status', 1, null, true);

            $this->checkLogistiqueStatus();
            $this->checkShipmentStatus();
            $this->checkInvoiceStatus();
        }

        return $errors;
    }

    public function sendLivraisonDirecteNotificationEmail($to_email = '', $log_errors = true)
    {
        $errors = array();

        if (!$to_email) {
            $id_contact = $this->getIdContact('external', 'SHIPPING');

            if ($id_contact) {
                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                if (BimpObject::objectLoaded($contact)) {
                    $to_email = BimpTools::cleanEmailsStr($contact->getData('email'));
                }
            }

            if (!$to_email) {
                $client = $this->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $to_email = BimpTools::cleanEmailsStr($client->getData('email'));
                }
            }

            if (!$to_email) {
                $errors[] = 'Aucune adresse e-mail trouvée pour la livraison';
            }
        }

        if (!count($errors)) {
            $ref = $this->getRef();
            $subject = 'Votre commande N° ' . $ref . ' chez BIMP.PRO va bientôt être expédiée';
            $msg = 'Bonjour, <br/><br/>';
            $msg .= 'Votre commande N° ' . $ref . ' chez BIMP.PRO va bientôt être expédiée.<br/><br/>';
            $msg .= '<b>IMPORTANT - PROCEDURE DE RECEPTION :</b><br/><br/>';
            $msg .= "Nos envois font appel à des canaux multiples fiabilisés mais la responsabilité du transporteur prend fin dès lors qu'il vous a remis la marchandise.<br/><br/>";
            $msg .= "Il est IMPERATIF de suivre scrupuleusement  chez vous la procédure de réception ci dessous: <br/><br/>";
            $msg .= "A réception de votre commande, nous vous demandons de vérifier le nombre de colis, leur état extérieur mais aussi intérieur  en présence du transporteur, avant la validation de la réception.<br/><br/>";
            $msg .= "N'hésitez donc pas à ouvrir l'emballage en présence du livreur pour vérifier le matériel et réaliser un inventaire comparatif avec le bon de livraison présent dans le colis. Faites le systématiquement s'il s'agit d'un colis volumineux (en particulier: écran ou matériel fragile).<br/><br/>";
            $msg .= "Si vous avez le moindre doute, notez les réserves sur le bon de livraison avant signature en indiquant le nombre et l'état précis des colis endommagés. Gardez une copie de vos réserves contresignées par le transporteur.<br/><br/>";
            $msg .= "Des réserves non motivées telles que l'état du colis satisfaisant sous réserve de déballage ne peuvent être prises en compte. Sans des indications précises de votre part à réception, toute réclamation ultérieure ne pourra être prise en compte.<br/><br/>";
            $msg .= "En cas de doute sérieux, carton déchiré, perforé nous vous invitons à refuser la marchandise en motivant votre refus.<br/><br/>";
            $msg .= "Pour tout problème de livraison vous pouvez contacter nos services par mail à  Groupe-LDLC-Assistantes_Olys@ldlc.com <br/><br/>";
            $msg .= "Pour les produits lourds et / ou volumineux : Sauf mention particulière présente dans notre devis, la livraison des produits s'effectue au rez-de-chaussée et sans manutention à partir du camion de livraison.<br/>";
            $msg .= "Pour une livraison dans les étages, la demande doit être faite avant la commande et reprise sur le devis (avec un surcoût éventuel).";

            $bimpMail = new BimpMail($this, $subject, $to_email, '', $msg);
            $bimpMail->send($errors);

            if (!count($errors)) {
                $this->addObjectLog('Instructions pour la réception des livraisons directes envoyées avec succès à "' . $to_email . '"', 'LD_RECEPTION_INSTRUCTIONS_SENT');
            } elseif ($log_errors) {
                $log_msg = 'Echec de l\'envoi des instructions pour la réception des livraisons directes.<br/><b>Erreurs : </b><br/>';
                $log_msg .= BimpTools::getMsgFromArray($errors);
                $this->addObjectLog($log_msg, 'LD_RECEPTION_INSTRUCTIONS_FAIL');
            }
        }

        return $errors;
    }

    // Traitements factures: 

    public function createFacture(&$errors = array(), $id_client = null, $id_contact = null, $cond_reglement = null, $id_account = null, $public_note = '', $private_note = '', $remises = array(), $other_commandes = array(), $libelle = null, $id_entrepot = null, $ef_type = null, $force_create = false, $replaced_ref = '')
    {
        // Todo: réécrire en  utilisant Bimp_Facture.

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande client absent ou invalide';
            return 0;
        }

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

        if (!$force_create && !$facture->can('create')) {
            $errors[] = 'Vous n\'avez pas la permission de créer des factures';
        }

        if (is_null($id_client)) {
            if ((int) $this->getData('id_client_facture')) {
                $id_client = (int) $this->getData('id_client_facture');
            } else {
                $id_client = (int) $this->getData('fk_soc');
            }
        }

        if (!$id_client) {
            $errors[] = 'Aucun client enregistré pour cette commande';
            return 0;
        }

        if (is_null($cond_reglement) || !$cond_reglement) {
            $cond_reglement = (int) $this->dol_object->cond_reglement_id;
        }

        if (is_null($id_contact) || !(int) $id_contact) {
            $id_contact = $this->dol_object->contact_id;
        }

        if (is_null($id_entrepot) || !(int) $id_entrepot) {
            $id_entrepot = $this->getData('entrepot');
        }

        if (is_null($ef_type) || !$ef_type) {
            $ef_type = $this->getData('ef_type');
        }

        // Création de la facture: 

        $facture->dol_object->date = dol_now();
        $facture->dol_object->source = 0;
        $facture->dol_object->socid = $id_client;
        $facture->dol_object->fk_project = $this->dol_object->fk_project;
        $facture->dol_object->cond_reglement_id = $cond_reglement;
        $facture->dol_object->mode_reglement_id = $this->dol_object->mode_reglement_id;
        $facture->dol_object->availability_id = $this->dol_object->availability_id;
        $facture->dol_object->demand_reason_id = $this->dol_object->demand_reason_id;
        $facture->dol_object->date_livraison = $this->dol_object->date_livraison;
        $facture->dol_object->fk_delivery_address = $this->dol_object->fk_delivery_address;
        $facture->dol_object->contact_id = $id_contact;
        $facture->dol_object->ref_client = $this->dol_object->ref_client;
        $facture->dol_object->note_private = $private_note;
        $facture->dol_object->note_public = $public_note;
        $facture->dol_object->modelpdf = 'bimpfact';

        $facture->dol_object->origin = $this->dol_object->element;
        $facture->dol_object->origin_id = $this->dol_object->id;

        $facture->dol_object->fk_account = (int) $id_account;

        // get extrafields from original line
//        $this->dol_object->fetch_optionals($this->id);

        foreach ($this->dol_object->array_options as $options_key => $value)
            $facture->dol_object->array_options[$options_key] = $value;

        if (!is_null($libelle)) {
            $facture->dol_object->array_options['options_libelle'] = $libelle;
        }

        $facture->dol_object->array_options['options_entrepot'] = $id_entrepot;
        $facture->dol_object->array_options['options_type'] = $ef_type;

        if (empty($other_commandes)) {
            $facture->dol_object->array_options['options_pdf_hide_pu'] = $this->getData('pdf_hide_pu');
//            $facture->dol_object->array_options['options_pdf_hide_reduc'] = $this->getData('pdf_hide_reduc');
//            $facture->dol_object->array_options['options_pdf_hide_total'] = $this->getData('pdf_hide_total');
//            $facture->dol_object->array_options['options_pdf_hide_ttc'] = $this->getData('pdf_hide_ttc');
        }

        // Possibility to add external linked objects with hooks
        $facture->dol_object->linked_objects[$facture->dol_object->origin] = array($facture->dol_object->origin_id);

        if (!empty($other_commandes)) {
            foreach ($other_commandes as $id_commande) {
                $facture->dol_object->linked_objects[$facture->dol_object->origin][] = $id_commande;
            }
        }
        if (!empty($this->dol_object->other_linked_objects) && is_array($this->dol_object->other_linked_objects)) {
            $facture->dol_object->linked_objects = BimpTools::merge_array($facture->dol_object->linked_objects, $this->dol_object->other_linked_objects);
        }

        $facture->dol_object->source = 0;

        global $user;

        $id_facture = $facture->dol_object->create($user);
        if ($id_facture <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Echec de la création de la facture');
            return 0;
        }

        // Associations de la facture: 
        $asso = new BimpAssociation($this, 'factures');
        $asso->addObjectAssociation($id_facture);
        unset($asso);

        if ($other_commandes) {
            foreach ($other_commandes as $id_commande) {
                $commande = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_commande);
                if (BimpObject::ObjectLoaded($commande)) {
                    $asso = new BimpAssociation($commande, 'factures');
                    $asso->addObjectAssociation($id_facture);
                    unset($asso);
                }
            }
        }

        // Insertion des acomptes:
        if (is_array($remises) && count($remises)) {
            $facture->fetch((int) $id_facture);

            foreach ($remises as $id_remise) {
                $rem_errors = $facture->insertDiscount((int) $id_remise);

                if (count($rem_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($rem_errors, 'Echec de l\'insertion de la remise client #' . $id_remise);
                }
            }
        }

        if ($id_facture) {
            $data = array();

            if ($replaced_ref) {
                $data['replaced_ref'] = $replaced_ref;
            }

            if ((int) $this->getData('fk_soc') !== (int) $id_client) {
                $data['id_client_final'] = (int) $this->getData('fk_soc');
            }

            if (!empty($data)) {
                $this->db->update('facture', $data, 'rowid = ' . (int) $id_facture);
            }
        }

        return $id_facture;
    }

    public function checkFactureLinesData(&$lines_data, $id_facture = null)
    {
        $errors = array();

        foreach ($lines_data as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);
            if (!BimpObject::objectLoaded($line)) {
                $errors[] = 'La ligne d\'ID ' . $id_line . ' n\'existe pas';
            } else {
                if ((int) $line->getData('type') === ObjectLine::LINE_TEXT) {
                    continue;
                }

                $line_equipments = BimpTools::getArrayValueFromPath($line_data, 'equipments', array());
                $line_qty = BimpTools::getArrayValueFromPath($line_data, 'qty', 0);

                if ((int) $line->getData('fac_periodicity')) {
                    // Conversion du nombre de périodes à facturer en qté décimale:     
                    if (!(float) $line_qty) {
                        $periods = BimpTools::getArrayValueFromPath($line_data, 'periods', null);
                        if ((int) $periods && (int) $line->getData('fac_nb_periods')) {
                            $unit = 1 / (int) $line->getData('fac_nb_periods');
                            $line_qty = $periods * $unit * (float) $line->getFullQty();
                        } else {
                            $line_qty = 0;
                        }
                        $lines_data[$id_line]['qty'] = $line_qty;
                    }
                }

                $line_errors = $line->checkFactureData($line_qty, $line_equipments, $id_facture);
                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                }
            }
        }

        return $errors;
    }

    public function addLinesToFacture($id_facture, $lines_data = null, $check_data = true, $new_qties = false, $commit_each_line = false, &$nOk = 0)
    {
        // $commit_each_line : nécessaire pour le traitement des facturation périodiques. 

        BimpCore::setMaxExecutionTime(2400);
        ignore_user_abort(0);

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

        if ($check_data) {
            $errors = $this->checkFactureLinesData($lines_data, $id_facture);
            if (count($errors)) {
                return $errors;
            }
        }

        // Trie des lignes par commandes:
        $orderedLines = array();

        foreach ($lines_data as $id_line => $line_data) {
            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);
            if (BimpObject::objectLoaded($line)) {
                $id_commande = (int) $line->getData('id_obj');
                if (!array_key_exists($id_commande, $orderedLines)) {
                    $orderedLines[$id_commande] = array();
                }
                $orderedLines[$id_commande][(int) $id_line] = $line_data;
            } else {
                $errors[] = 'La ligne de commande client d\'ID ' . $id_line . ' n\'existe pas';
            }
        }

        $lines_data = array();

        // Trie des lignes par positions dans la commande: 
        foreach ($orderedLines as $id_commande => $lines) {
            $lines_data[$id_commande] = array();

            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_commande);

            if (BimpObject::objectLoaded($commande)) {
                $commande_lines = $commande->getLines();
                foreach ($commande_lines as $comm_line) {
                    if (array_key_exists((int) $comm_line->id, $lines)) {
                        $lines_data[$id_commande][(int) $comm_line->id] = $lines[(int) $comm_line->id];
                    }
                }
            } else {
                foreach ($lines as $id_line => $line_data) {
                    $lines_data[$id_commande][$id_line] = $line_data;
                }
            }
        }

        $commandes_assos = array();

        foreach ($lines_data as $id_commande => $commande_lines_data) {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_commande);

            if (BimpObject::objectLoaded($commande)) {
                $commande->hold_process_factures_remises_globales = true;

                // Création de la ligne de l'intitulé de la commande d'origine si nécessaire: 
                $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                            'id_obj'             => (int) $facture->id,
                            'linked_object_name' => 'commande_origin_label',
                            'linked_id_object'   => (int) $id_commande
                ));

                if (!BimpObject::objectLoaded($fac_line)) {
                    $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                    $fac_line->validateArray(array(
                        'id_obj'             => (int) $facture->id,
                        'type'               => ObjectLine::LINE_TEXT,
                        'linked_id_object'   => (int) $id_commande,
                        'linked_object_name' => 'commande_origin_label',
                    ));
                    $fac_line->qty = 1;
                    $fac_line->desc = 'Selon notre commande ' . $commande->getRef();
                    $libelle = $commande->getData('libelle');
                    if ($libelle) {
                        $fac_line->desc .= ' - ' . $libelle;
                    }
                    $fac_line_warnings = array();
                    $fac_line->create($fac_line_warnings, true);
                }
            }

            $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');
            $has_line_ok = false;
            foreach ($commande_lines_data as $id_line => $line_data) {
                if ($use_db_transactions && $commit_each_line) {
                    $this->db->db->begin();
                }

                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $id_line);
                $line_label = 'Ligne n° ' . $line->getData('position') . (BimpObject::objectLoaded($commande) ? ' de la commande ' . $commande->getRef() : '');

                $product = $line->getProduct();
                $line_errors = array();
                $line_warnings = array();
                $line_qty = (float) $line_data['qty'];
                $line_equipments = array();

                $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                            'id_obj'             => (int) $facture->id,
                            'linked_object_name' => 'commande_line',
                            'linked_id_object'   => (int) $line->id
                                ), true);

                if (!BimpObject::objectLoaded($fac_line)) {
                    if (!$line_qty) {
                        continue;
                    }

                    $fac_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                    if ((int) $line->getData('type') === ObjectLine::LINE_TEXT) {
                        // Création d'une ligne de texte: 
                        $fac_line->validateArray(array(
                            'id_obj'             => (int) $facture->id,
                            'type'               => $line->getData('type'),
                            'linked_id_object'   => (int) $line->id,
                            'linked_object_name' => 'commande_line',
                        ));
                        $fac_line->qty = 1;
                        $fac_line->desc = $line->desc;

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
                        'type'               => $line->getData('type'),
                        'remisable'          => $line->getData('remisable'),
                        'hide_product_label' => $line->getData('hide_product_label'),
                        'force_qty_1'        => $line->getData('force_qty_1'),
                        'linked_id_object'   => (int) $line->id,
                        'linked_object_name' => 'commande_line',
                        'remise_crt'         => (int) $line->getData('remise_crt'),
                        'remise_pa'          => (float) $line->getData('remise_pa'),
                        'pa_editable'        => (isset($line_data['pa_editable']) ? (int) $line_data['pa_editable'] : 1)
                    ));

                    $fac_line->qty = $line_qty;
                    $fac_line->desc = $line->desc;
                    $fac_line->id_product = $line->id_product;
                    $fac_line->pu_ht = $line->pu_ht;
                    $fac_line->tva_tx = $line->tva_tx;
                    $fac_line->pa_ht = $line->pa_ht;
                    $fac_line->id_fourn_price = $line->id_fourn_price;
                    $fac_line->date_from = $line->date_from;
                    $fac_line->date_to = $line->date_to;
                    $fac_line->id_remise_except = $line->id_remise_except;
                    $fac_line->no_remises_arrieres_auto_create = true;

                    $line_errors = $fac_line->create($line_warnings, true);

                    if (!count($line_errors)) {
                        // Copie des remises: 
                        $remises_errors = $fac_line->copyRemisesFromOrigin($line, false, false);

                        if (count($remises_errors)) {
                            $line_errors[] = BimpTools::getMsgFromArray($remises_errors, 'Erreurs lors de la copie des remises');
                        }

                        $remises_arr_errors = $fac_line->copyRemisesArrieresFromOrigine($line);

                        if (count($remises_arr_errors)) {
                            $line_errors[] = BimpTools::getMsgFromArray($remises_arr_errors, 'Erreurs lors de la copie des remises arrières');
                        }

                        $fac_line->updateField('deletable', 0);
                    }
                } else {
                    if ($new_qties) {
                        $line_qty += (float) $fac_line->qty;

                        foreach ($fac_line->getEquipmentLines() as $eq_line) {
                            $line_equipments[] = array(
                                'id_equipment' => (int) $eq_line->getData('id_equipment')
                            );
                        }
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

                    if (isset($line_data['pa_editable'])) {
                        $fac_line->set('pa_editable', (int) $line_data['pa_editable']);
                    }

                    $fac_line_errors = array();
                    if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                        $fac_line_errors = $fac_line->setEquipments(array());
                    }

                    if (count($fac_line_errors)) {
                        $line_errors[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la mise à jour de la liste des équipements');
                    } else {
                        $fac_line_warnings = array();
                        $fac_line_errors = $fac_line->update($fac_line_warnings, true);

                        if (count($fac_line_errors)) {
                            $line_errors[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la mise à jour de la ligne de facture');
                        }
                    }
                }

                if (!count($line_errors) && BimpObject::objectLoaded($fac_line)) {
                    // Assignation des équipements à la ligne de facture: 
                    $equipments_set = array();
                    if (BimpObject::objectLoaded($product) && $product->isSerialisable()) {
                        if (isset($line_data['equipments']) && is_array($line_data['equipments'])) {

                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment');
                            $equipment->updateFieldsMasse($line_data['equipments'], array('prix_achat' => $line->pa_ht, 'achat_tva_tx' => $line->tva_tx), array('prix_achat' => 0));

                            foreach ($line_data['equipments'] as $id_equipment) {//
                                $line_equipments[] = array(
                                    'id_equipment' => (int) $id_equipment
                                );
                            }
                        }

                        $eq_errors = $fac_line->setEquipments($line_equipments, $equipments_set, false);
                        if (count($eq_errors)) {
                            $line_errors[] = BimpTools::getMsgFromArray($eq_errors, 'Echec de l\'ajout des équipements à la ligne de facture');
                        }
                    }
                }

                if (!count($line_errors)) {
                    // Enregistrement des quantités facturées pour la ligne de commande:
                    $line_errors = $line->setFactureData((int) $facture->id, $line_qty, $equipments_set, $line_warnings, false);
                }

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, ucfirst($line_label));
                    if ($use_db_transactions && $commit_each_line) {
                        $this->db->db->rollback();
                    }
                } else {
                    $has_line_ok = true;
                    if ($use_db_transactions && $commit_each_line) {
                        $nOk++;
                        $this->db->db->commit();
                    }
                }
            }

            if ($has_line_ok && !in_array($id_commande, $commandes_assos)) {
                $commandes_assos[] = $id_commande;
            }

            if (BimpObject::objectLoaded($commande)) {
                unset($commande->hold_process_factures_remises_globales);

                if ($has_line_ok) {
                    $commande->processFacturesRemisesGlobales();
                }
            }
        }

        // Assos commandes / factures : 
        if (count($commandes_assos) && (!count($errors) || ($use_db_transactions && $commit_each_line))) {
            $asso = new BimpAssociation($this, 'factures');
            foreach ($commandes_assos as $id_commande) {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_commande);

                if (BimpObject::objectLoaded($commande)) {
                    $asso->addObjectAssociation($facture->id, $commande->id);
                }
            }
        }

        return $errors;
    }

    public function processFacturesRemisesGlobales()
    {
        $errors = $w = array();

        if ($this->isLoaded($errors)) {
            if (isset($this->hold_process_factures_remises_globales) && $this->hold_process_factures_remises_globales) {
                return array();
            }

            if (!(int) $this->getData('fk_statut')) {
                return array();
            }

            $rgs = $this->getRemisesGlobales();
            $lines = $this->getLines('not_text');
            $total_ttc = (float) $this->getTotalTtcWithoutRemises(true, true);

            if (!empty($rgs)) {
                foreach ($rgs as $rg) {
                    // Détermination du montant TTC de la rg: 
                    $rg_amount_ttc = 0;

                    switch ($rg->getData('type')) {
                        case 'amount':
                            $rg_amount_ttc = (float) $rg->getData('amount');
                            break;

                        case 'percent':
                            $remise_rate = (float) $rg->getData('percent');
                            $rg_amount_ttc = $total_ttc * ($remise_rate / 100);
                            break;
                    }

                    // Déduction des parts de la rg pour les qtés présentes dans les factures validées
                    $lines_remaining_qties = array();

                    foreach ($lines as $line) {
                        $lines_remaining_qties[(int) $line->id] = (float) $line->getFullQty();
                        $factures = $line->getData('factures');

                        if (is_array($factures)) {
                            foreach ($factures as $id_fac => $fac_data) {
                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);

                                if (BimpObject::objectLoaded($facture) && (int) $facture->getData('fk_statut') > 0) {
                                    $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                                                'id_obj'             => (int) $id_fac,
                                                'linked_object_name' => 'commande_line',
                                                'linked_id_object'   => (int) $line->id
                                                    ), true);

                                    if (BimpObject::objectLoaded($fac_line)) {
                                        $fac_line_remise = BimpCache::findBimpObjectInstance('bimpcommercial', 'ObjectLineRemise', array(
                                                    'id_object_line'           => (int) $fac_line->id,
                                                    'object_type'              => $fac_line::$parent_comm_type,
                                                    'linked_id_remise_globale' => (int) $rg->id
                                                        ), true);

                                        if (BimpObject::objectLoaded($fac_line_remise)) {
                                            $rg_amount_ttc -= (float) $fac_line->getTotalTtcWithoutRemises(true) * ((float) $fac_line_remise->getData('percent') / 100);
                                            $lines_remaining_qties[(int) $line->id] -= (float) $fac_line->getFullQty();
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Calcul de la nouvelle part pour chaque ligne: 
                    $total_lines_ttc = 0;
                    foreach ($lines as $line) {
                        if ($line->isRemisable() && isset($lines_remaining_qties[(int) $line->id]) && (float) $lines_remaining_qties[(int) $line->id]) {
                            $total_lines_ttc += ((float) $line->pu_ht * (1 + ((float) $line->tva_tx / 100))) * (float) $lines_remaining_qties[(int) $line->id];
                        }
                    }

                    $lines_rate = 0;
                    if ($total_lines_ttc) {
                        $lines_rate = ($rg_amount_ttc / $total_lines_ttc) * 100;
                    }

//                    if($rg->getData('type') == 'percent'){
//                        $lines_rate = $rg->getData('percent');
//                    }
                    // Assignation du nouveau taux pour chaque ligne de facture brouillon: 

                    foreach ($lines as $line) {
                        if (!$line->isRemisable()) {
                            continue;
                        }
                        $factures = $line->getData('factures');
                        if (is_array($factures)) {
                            foreach ($factures as $id_fac => $fac_data) {
                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);

                                if (BimpObject::objectLoaded($facture) && (int) $facture->getData('fk_statut') === 0) {
                                    $fac_line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                                                'id_obj'             => (int) $id_fac,
                                                'linked_object_name' => 'commande_line',
                                                'linked_id_object'   => (int) $line->id
                                                    ), true);

                                    if (BimpObject::objectLoaded($fac_line)) {
                                        $fac_line_rate = $lines_rate;

                                        if ((float) $fac_line->pu_ht) {
                                            if ((float) $fac_line->pu_ht !== (float) $line->pu_ht) {
                                                $fac_line_rate = (((float) $line->pu_ht * ($lines_rate / 100)) / (float) $fac_line->pu_ht) * 100;
                                            }
                                        } else {
                                            $fac_line_rate = 0;
                                        }

                                        $fac_line_remise = BimpCache::findBimpObjectInstance('bimpcommercial', 'ObjectLineRemise', array(
                                                    'id_object_line'           => (int) $fac_line->id,
                                                    'object_type'              => $fac_line::$parent_comm_type,
                                                    'linked_id_remise_globale' => (int) $rg->id
                                                        ), true);

                                        if (BimpObject::objectLoaded($fac_line_remise)) {
                                            if ((float) $fac_line_remise->getData('percent') === (float) $fac_line_rate) {
                                                continue;
                                            }
                                            $fac_line_remise->set('percent', $fac_line_rate);
                                            $fac_line_errors = $fac_line_remise->update($w, true);

                                            if (count($fac_line_errors)) {
                                                $errors[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la mise à jour de la part de remise globale pour la ligne n°' . $fac_line->getData('position') . ' (Facture ' . $facture->getRef() . ')');
                                            }
                                        } else {
                                            $fac_line_remise = BimpObject::createBimpObject('bimpcommercial', 'ObjectLineRemise', array(
                                                        'id_object_line'           => (int) $fac_line->id,
                                                        'object_type'              => $fac_line::$parent_comm_type,
                                                        'linked_id_remise_globale' => (int) $rg->id,
                                                        'type'                     => ObjectLineRemise::OL_REMISE_PERCENT,
                                                        'percent'                  => (float) $fac_line_rate,
                                                        'label'                    => 'Part de la remise "' . $rg->getData('label') . '" (Commande ' . $this->getRef() . ')'
                                                            ), $errors, $errors);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function checkClientsFinauxFactures(&$nbDone = 0)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $id_client_facture = (int) $this->getData('id_client_facture');
            $id_client = (int) $this->getData('fk_soc');

            if ($id_client_facture && $id_client_facture !== $id_client) {
                $asso = new BimpAssociation($this, 'factures');

                $factures_ids = $asso->getAssociatesList();
                foreach ($factures_ids as $id_facture) {
                    if (!(int) $this->db->getValue('facture', 'id_client_final', 'rowid = ' . $id_facture)) {
                        if ($this->db->update('facture', array(
                                    'id_client_final' => $id_client
                                        ), 'rowid = ' . (int) $id_facture) <= 0) {
                            $errors[] = 'Facture #' . $id_facture . ' : échec màj - ' . $this->db->err();
                        } else {
                            $nbDone++;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    // Checks status: 

    public function checkStatus()
    {
        if ($this->isLoaded() && (int) $this->getData('fk_statut') >= 0) {
            if (in_array((int) $this->getData('logistique_status'), array(3, 5, 6)) &&
                    (int) $this->getData('shipment_status') === 2 &&
                    (int) $this->getData('invoice_status') === 2) {
                if (in_array((int) $this->getData('fk_statut'), array(Commande::STATUS_VALIDATED, Commande::STATUS_ACCEPTED))) {
                    $this->updateField('fk_statut', Commande::STATUS_CLOSED);
                }
            } elseif ((int) $this->getData('fk_statut') === Commande::STATUS_CLOSED) {
                $this->updateField('fk_statut', Commande::STATUS_VALIDATED);
            }
        }
    }

    public function checkLogistiqueStatus($log_change = false)
    {
        if ($this->isLoaded() && (int) $this->getData('fk_statut') >= 0) {
            $status_forced = $this->getData('status_forced');

            if (isset($status_forced['logistique']) && (int) $status_forced['logistique']) {
                $this->checkStatus();
                return;
            }

            if (!in_array((int) $this->getData('logistique_status'), array(0, 4, 5))) {
                $lines = $this->getLines('not_text');

                $hasToProcess = false;
                $isCompleted = true;
                foreach ($lines as $line) {
                    $qties = $line->getReservedQties();

                    if (isset($qties['status'][0]) && (float) $qties['status'][0] > 0) {
                        $isCompleted = false;
                        $hasToProcess = true;
                        break;
                    }

                    if (isset($qties['not_reserved']) && (float) $qties['not_reserved'] > 0) {
                        $isCompleted = false;
                    }
                }

                if ($hasToProcess) {
                    $new_status = 1;
                } elseif (!$isCompleted) {
                    $new_status = 2;
                } else {
                    $new_status = 3;
                }

                if ($new_status !== (int) $this->getInitData('logistique_status')) {
                    if ($log_change) {
                        BimpCore::addlog('Correction auto du statut Logistique', Bimp_Log::BIMP_LOG_NOTIF, 'bimpcore', $this, array(
                            'Ancien statut'  => (int) $this->getInitData('logistique_status'),
                            'Nouveau statut' => $new_status
                        ));
                    }

                    $this->updateField('logistique_status', $new_status);
                    if (BimpCore::isEntity('bimp')) {
                        if (in_array($new_status, array(2, 3))) {
                            $entrepot = $this->getChildObject('entrepot');
                            if (BimpObject::objectLoaded($entrepot) && $entrepot->ref == 'LD') {
                                $where = 'obj_module = \'bimpcommercial\' AND obj_name = \'Bimp_Commande\' AND id_object = ' . $this->id . ' AND code = \'LD_RECEPTION_INSTRUCTIONS_SENT\'';
                                if (!(int) $this->db->getCount('bimpcore_object_log', $where)) {
                                    $this->sendLivraisonDirecteNotificationEmail('', true);
                                }
                            }
                        }
                    }

                    if ($new_status == 3) {
                        $idComm = $this->getIdCommercial();
                        $email = BimpTools::getUserEmailOrSuperiorEmail($idComm);

                        $infoClient = "";
                        $client = $this->getChildObject('client');
                        if (is_object($client) && $client->isLoaded()) {
                            $infoClient = " du client " . $client->getLink();
                        }

                        if (!empty($email)) {
                            mailSyn2("Logistique commande OK", $email, null, 'Bonjour la logistique de votre commande ' . $this->getLink() . $infoClient . ' est compléte ');
                        }
                    }
                }
            }
            $this->checkStatus();
        }
    }

    public function checkShipmentStatus($log_change = false)
    {
        if ($this->isLoaded() && (int) $this->getData('fk_statut') >= 0) {
            $status_forced = $this->getData('status_forced');

            if (isset($status_forced['shipment']) && (int) $status_forced['shipment']) {
                $this->checkStatus();
                return;
            }

            $lines = $this->getLines('not_text');

            $hasShipment = 0;
            $isFullyShipped = 0;
            $hasOnlyPeriodicity = 0;

            $current_status = (int) $this->getInitData('shipment_status');

            if (!empty($lines)) {
                $isFullyShipped = 1;
                $hasOnlyPeriodicity = 1;
                foreach ($lines as $line) {
                    $shipped_qty = round((float) $line->getShippedQty(null, true), 6);
                    if ($shipped_qty) {
                        $hasShipment = 1;
                    } else {
                        $hasOnlyPeriodicity = 0;
                    }

                    if (abs($shipped_qty) < abs(round((float) $line->getShipmentsQty(), 6))) {
                        $isFullyShipped = 0;

                        if ($hasOnlyPeriodicity && !(int) $line->getData('exp_periodicity')) {
                            $hasOnlyPeriodicity = 0;
                        }
                    }
                }
            }

            if ($isFullyShipped) {
                $new_status = 2;
            } elseif ($hasOnlyPeriodicity) {
                $new_status = 3;
            } elseif ($hasShipment) {
                $new_status = 1;
            } else {
                $new_status = 0;
            }

            if ($new_status !== $current_status) {
                if ($log_change) {
                    BimpCore::addlog('Correction auto du statut Expédition', Bimp_Log::BIMP_LOG_NOTIF, 'bimpcore', $this, array(
                        'Ancien statut'  => $current_status,
                        'Nouveau statut' => $new_status
                    ));
                }
                $this->updateField('shipment_status', $new_status);
            }

            $this->checkStatus();
        }
    }

    public function checkInvoiceStatus($log_change = false)
    {
        if ($this->isLoaded() && (int) $this->getData('fk_statut') >= 0) {
            $status_forced = $this->getData('status_forced');
            $isFullyInvoiced = 0;

            if (isset($status_forced['invoice']) && (int) $status_forced['invoice']) {
                if ((int) $this->getData('invoice_status') === 2) {
                    $isFullyInvoiced = 1;
                }
            } else {
                $lines = $this->getLines('not_text');
                $hasInvoice = 0;
                $isFullyAddedToInvoice = 0;
                $hasOnlyPeriodicity = 0;

                if (!empty($lines)) {
                    $isFullyInvoiced = 1;
                    $isFullyAddedToInvoice = 1;
                    $hasOnlyPeriodicity = 1;

                    foreach ($lines as $line) {
//                        $line->fetch($line->id);//TODO trés bourin mais necessaire pour que quand on passe une ligne a zero $line soit a jour

                        $billed_qty = abs(round((float) $line->getBilledQty(null, false), 6));
                        $full_qty = abs(round((float) $line->getFullQty(), 6));
                        if ($billed_qty) {
                            $hasInvoice = 1;
                        } else {
                            $hasOnlyPeriodicity = 0;
                        }

                        if ($billed_qty < $full_qty) {
                            $isFullyAddedToInvoice = 0;

                            if ($hasOnlyPeriodicity && !(int) $line->getData('fac_periodicity')) {
                                $hasOnlyPeriodicity = 0;
                            }
                        }

                        if ($isFullyInvoiced) {
                            if (abs(round((float) $line->getBilledQty(null, true), 6)) < $full_qty) {
                                $isFullyInvoiced = 0;
                            }
                        }
                    }
                }

                if ($isFullyAddedToInvoice) {
                    $new_status = 2;
                } elseif ($hasOnlyPeriodicity) {
                    $new_status = 3;
                } elseif ($hasInvoice) {
                    $new_status = 1;
                } else {
                    $new_status = 0;
                }

                $current_status = (int) $this->getInitData('invoice_status');
                if ($new_status !== $current_status) {
                    if ($log_change) {
                        BimpCore::addlog('Correction auto du statut Facturation', Bimp_Log::BIMP_LOG_NOTIF, 'bimpcore', $this, array(
                            'Ancien statut'  => $current_status,
                            'Nouveau statut' => $new_status
                        ));
                    }
                    $this->updateField('invoice_status', $new_status);

                    $idComm = $this->getIdCommercial();
                    $mail = BimpTools::getUserEmailOrSuperiorEmail($idComm);

                    $infoClient = "";
                    $client = $this->getChildObject('client');
                    if (is_object($client) && $client->isLoaded()) {
                        $infoClient = " du client " . $client->getLink();
                    }


                    if (isset($mail) && $mail != "")
                        mailSyn2("Statut facturation", $mail, null, 'Bonjour le statut facturation de votre commande ' . $this->getLink() . $infoClient . ' est  ' . $this->displayData('invoice_status'));
                }
            }

            // Traiement du classement "facturée": 
            $billed = (int) $this->db->getValue('commande', 'facture', 'rowid = ' . (int) $this->id);

            if ($isFullyInvoiced && !$billed) {
                global $user;
                $this->dol_object->classifybilled($user);
                $this->dol_object->fetchObjectLinked();
                if (isset($this->dol_object->linkedObjects['propal'])) {
                    foreach ($this->dol_object->linkedObjects['propal'] as $prop) {
                        $prop->classifybilled($user);
                    }
                }
            }
            if (!$isFullyInvoiced && $billed) {
                global $user;
                $this->dol_object->classifyUnBilled($user);
            }

            $this->checkStatus();
        }
    }

    // Gestion des lignes:

    public function setRevalorisation()
    {
        if (!(int) $this->getData('revalorisation')) {
            $this->updateField('revalorisation', 1);
        }
    }

    // Actions:

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        /* pour enregistré les valeur du form */
        $errors = $this->updateFields($data);
        $this->db->db->commit();
        $this->db->db->begin();

        $infos = array();

        $forced_by_dev = (int) BimpTools::getArrayValueFromPath($data, 'forced_by_dev', 0);

        $success = BimpTools::ucfirst($this->getLabel('')) . ' validé' . $this->e();
        $success .= ' avec succès';
        $success_callback = 'bimp_reloadPage();';

        global $conf, $langs, $user;

        $comm_errors = array();
        $comm_warnings = array();
        $comm_infos = array();

        if (!$forced_by_dev) {
            $result = $this->dol_object->valid($user, (int) $this->getData('entrepot'));

            $comm_errors = BimpTools::getDolEventsMsgs(array('errors'));
            $comm_warnings = BimpTools::getDolEventsMsgs(array('warnings'));
            $comm_infos = BimpTools::getDolEventsMsgs(array('mesgs'));
        } else {
            $result = 0;
            $client = $this->getChildObject('client');

            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Client absent';
            } else {
                $old_ref = $this->getRef();
                if (preg_match('/^[\(]?PROV/i', $old_ref)) {
                    $new_ref = $this->dol_object->getNextNumRef($client->dol_object);
                    $this->set('ref', $new_ref);
                } else {
                    $new_ref = $old_ref;
                }
                $comm_errors = $this->onValidate($comm_warnings);

                if (!count($comm_errors)) {
                    $result = 1;
                    $this->updateField('ref', $new_ref);
                    $this->updateField('fk_statut', 1);
                    $this->updateField('date_valid', date('Y-m-d H:i:s'));
                    $this->updateField('fk_user_valid', $user->id);

                    if (preg_match('/^[\(]?PROV/i', $old_ref)) {
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                        $dirsource = $conf->commande->dir_output . '/' . $old_ref;
                        $dirdest = $conf->commande->dir_output . '/' . $new_ref;
                        if (file_exists($dirsource)) {
                            if (@rename($dirsource, $dirdest)) {
                                // Rename docs starting with $oldref with $newref
                                $listoffiles = dol_dir_list($conf->commande->dir_output . '/' . $new_ref, 'files', 1, '^' . preg_quote($old_ref, '/'));
                                foreach ($listoffiles as $fileentry) {
                                    $dirsource = $fileentry['name'];
                                    $dirdest = preg_replace('/^' . preg_quote($old_ref, '/') . '/', $new_ref, $dirsource);
                                    $dirsource = $fileentry['path'] . '/' . $dirsource;
                                    $dirdest = $fileentry['path'] . '/' . $dirdest;
                                    @rename($dirsource, $dirdest);
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($result > 0) {
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $this->fetch($this->id);
                $this->dol_object->generateDocument($this->getModelPdf(), $langs);
            }
        } else {
            if (!count($comm_errors)) {
                $errors[] = 'Echec de la validation pour une raison inconnue';
            } else {
                $errors[] = BimpTools::getMsgFromArray($comm_errors);
            }
        }

        if (count($comm_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($comm_warnings);
        }
        if (count($comm_infos)) {
            $infos[] = BimpTools::getMsgFromArray($comm_infos);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'infos'            => $infos,
            'success_callback' => $success_callback
        );
    }

    public function actionCancel($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success_callback = '';
        $success = '';

        if ($this->isLoaded()) {
            $errors = $this->cancel($warnings);
            $success = 'Commande annulée avec succès';
            $success_callback = 'bimp_reloadPage();';
        } else {
            $id_objects = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

            if (empty($id_objects)) {
                $errors[] = 'Aucune commande sélectionnée';
            } else {
                $nOk = 0;

                $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');
                if ($use_db_transactions) {
                    $this->db->db->commit();
                }

                foreach ($id_objects as $id_object) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_object);

                    if (!BimpObject::objectLoaded($commande)) {
                        $warnings[] = 'La commande #' . $id_object . ' n\'existe pas';
                    } else {
                        $comm_errors = array();
                        if ($commande->isActionAllowed('cancel', $comm_errors)) {
                            if ($use_db_transactions) {
                                $this->db->db->begin();
                            }

                            $comm_errors = $commande->cancel();

                            if ($use_db_transactions) {
                                if (count($comm_errors)) {
                                    $this->db->db->rollback();
                                } else {
                                    $this->db->db->commit();
                                }
                            }
                        }

                        if (count($comm_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Commande ' . $commande->getRef());
                        } else {
                            $nOk++;
                        }
                    }
                }

                if ($use_db_transactions) {
                    $this->db->db->begin();
                }

                if ($nOk) {
                    $success = $nOk . ' commande(s) annulée(s) avec succès';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture ' . $this->getLabel('of_the') . ' effectuée avec succès';

        global $user;

        if ($this->dol_object->set_reopen($user) < 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la réouverture ' . $this->getLabel('of_the'));
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionLinesShipmentQties($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_shipment = (isset($data['id_shipment']) ? (int) $data['id_shipment'] : 0);
        $lines = (isset($data['lines']) ? $data['lines'] : array());

        if (!$id_shipment) {
            $errors[] = 'ID de l\'expédition absent';
        }

        if (!is_array($lines) || empty($lines)) {
            $errors[] = 'Aucune ligne de commande spécifiée';
        }

        if (!count($errors)) {
            $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
            if (!BimpObject::objectLoaded($shipment)) {
                $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' n\'existe pas';
            } else {
                if ((int) $shipment->getData('status') !== BL_CommandeShipment::BLCS_BROUILLON) {
                    $errors[] = 'L\'expédition sélectionnée ne peut pas être modifiée car elle n\'a plus le statut "brouillon"';
                } elseif ((int) $shipment->getData('id_commande_client') !== (int) $this->id) {
                    $errors[] = 'L\'expédition sélectionnée n\'appartient pas à cette commande';
                } else {
                    $success = 'Ajouts à l\'expédition n°' . $shipment->getData('num_livraison') . ' effectués avec succès';
                    foreach ($lines as $line_data) {
                        $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line_data['id_line']);
                        if (!BimpObject::objectLoaded($line)) {
                            $errors[] = 'La ligne de commande d\'ID ' . $line_data['id_line'] . ' n\'existe pas';
                        } elseif ($line->isShippable()) {
                            $line_warnings = array();
                            $line_errors = $line->setShipmentData($shipment, $line_data, $line_warnings, true);

                            if (count($line_warnings)) {
                                $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Ligne n° ' . $line->getData('position') . ' (ID ' . $line->id . ')');
                            }

                            if (count($line_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n° ' . $line->getData('position') . ' (ID ' . $line->id . ')');
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionLinesFactureQties($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        if (!isset($data['id_facture'])) {
            $errors[] = 'Aucune facture spécifiée';
        } elseif (!isset($data['lines']) || empty($data['lines'])) {
            $errors[] = 'Aucune quantité spécifiée';
        } else {
            $lines_data = array();

            foreach ($data['lines'] as $line_data) {
                $lines_data[(int) $line_data['id_line']] = $line_data;
            }


            // Vérification des quantités: 
            $id_facture = (int) $data['id_facture'] ? (int) $data['id_facture'] : null;
            $errors = $this->checkFactureLinesData($lines_data, $id_facture);

            if (!count($errors)) {
                if ($id_facture) {
                    $success = 'Ajout des unités à la facture effectué avec succès';
                    $errors = $this->addLinesToFacture($id_facture, $lines_data, false);
                } else {
                    $success = 'Création de la facture effectuée avec succès';
                    $id_client = isset($data['id_client_facture']) ? $data['id_client_facture'] : null;
                    $id_contact = isset($data['id_contact']) ? $data['id_contact'] : null;
                    $id_cond_reglement = isset($data['id_cond_reglement']) ? $data['id_cond_reglement'] : null;
                    $id_account = isset($data['id_account']) ? (int) $data['id_account'] : null;
                    $remises = isset($data['id_remises_list']) ? $data['id_remises_list'] : array();
                    $note_public = isset($data['note_public']) ? $data['note_public'] : '';
                    $note_private = isset($data['note_private']) ? $data['note_private'] : '';
                    $replaced_ref = isset($data['replaced_ref']) ? $data['replaced_ref'] : '';

                    $id_facture = $this->createFacture($errors, $id_client, $id_contact, $id_cond_reglement, $id_account, $note_public, $note_private, $remises, array(), null, null, null, false, $replaced_ref);

                    // Ajout des lignes à la facture: 
                    if ($id_facture && !count($errors)) {
                        $lines_errors = $this->addLinesToFacture($id_facture, $lines_data, false);

                        if (count($lines_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($lines_errors, 'Erreurs lors de l\'ajout des lignes à la facture');
                        }
                    }
                }

                if ($id_facture) {
                    $success_callback = 'window.open(\'' . DOL_URL_ROOT . '/bimpcommercial/index.php?fc=facture&id=' . $id_facture . '\');';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSetLinesReservationsStatus($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $reservations = isset($data['reservations']) ? $data['reservations'] : array();
        $status = isset($data['status']) ? (int) $data['status'] : null;

        if (!is_array($reservations) || empty($reservations)) {
            $errors[] = 'Aucun élément sélectionné';
        } elseif (is_null($status)) {
            $errors[] = 'Nouveau statut non spécifié';
        } else {
            $n_success = 0;
            foreach ($reservations as $id_reservation) {
                $reservation = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', (int) $id_reservation);
                if (!BimpObject::objectLoaded($reservation)) {
                    $warnings[] = 'La réservation d\'ID ' . $id_reservation . ' n\'existe pas';
                } else {
                    $line = $reservation->getChildObject('commande_client_line');

                    if (!BimpObject::objectLoaded($line)) {
                        $warnings[] = 'La réservation d\'ID ' . $id_reservation . ' n\'est pas associée à une ligne de commande valide';
                    } else {
                        if ((int) $reservation->getData('status') >= 300) {
                            $title = 'Ligne n° ' . $line->getData('position') . ': ';
                            $title .= 'statut "' . BR_Reservation::$status_list[(int) $reservation->getData('status')]['label'] . '"';
                            $warnings[] = $title . ': ce statut n\'est plus modifiable';
                        } else {
                            if (in_array((int) $status, array(4)) && (int) $reservation->getData('status') >= 200) {
                                $title = 'Ligne n° ' . $line->getData('position') . ': ';
                                $title .= 'statut "' . BR_Reservation::$status_list[(int) $reservation->getData('status')]['label'] . '"';
                                $warnings[] = $title . ': il n\'est pas possible de passer ce statut à "' . BR_Reservation::$status_list[4]['label'] . '"';
                            } else {
                                $res_errors = array();

                                if (!count($res_errors)) {
                                    $res_errors = $reservation->setNewStatus($status);
                                }

                                if (count($res_errors)) {
                                    $title = 'Ligne n° ' . $line->getData('position') . ': ';
                                    $title .= 'statut "' . BR_Reservation::$status_list[(int) $reservation->getData('status')]['label'] . '"';
                                    $warnings[] = BimpTools::getMsgFromArray($res_errors, $title);
                                } else {
                                    $n_success++;
                                }
                            }
                        }
                    }
                }
            }
            if ($n_success > 0) {
                if ($n_success === count($reservations)) {
                    $success = 'Tous les nouveaux statuts ont été enregistrés avec succès';
                } else {
                    if ($n_success > 1) {
                        $success = $n_success . ' statuts ont été mis à jour avec succès';
                    } else {
                        $success = '1 statut a été mis à jour avec succès';
                    }
                }
            } else {
                $errors = $warnings;
                $warnings = array();
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddEquipmentsToShipment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_shipment = isset($data['id_shipment']) ? (int) $data['id_shipment'] : 0;

        if (!$id_shipment) {
            $errors[] = 'Aucune expédition sélectionnée';
        } else {
            $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id_shipment);
            if (!$shipment->isLoaded()) {
                $errors[] = 'L\'expédition d\'ID ' . $id_shipment . ' n\'existe pas';
            } else {
                if ((int) $shipment->getData('status') !== BL_CommandeShipment::BLCS_BROUILLON) {
                    $errors[] = 'L\'expédition n°' . $shipment->getDatat('num_livraison') . ' n\'a pas le statut "brouillon"';
                } elseif (!isset($data['lines']) || !is_array($data['lines']) || empty($data['lines'])) {
                    $errors[] = 'Aucun équipement sélectionné';
                } else {
                    $check = false;
                    foreach ($data['lines'] as $line_data) {
                        if (isset($line_data['equipments']) && is_array($line_data['equipments']) && !empty($line_data['equipments'])) {
                            $check = true;
                            $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line_data['id_line']);
                            if (!$line->isLoaded()) {
                                $warnings[] = 'La ligne de commande d\'ID ' . $line_data['id_line'] . ' n\'existe pas';
                            } else {
                                $line_errors = $line->addEquipmentsToShipment($id_shipment, $line_data['equipments'], (int) $line_data['qty']);
                                if (count($line_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Erreurs pour la ligne n°' . $line->getData('position'));
                                } else {
                                    $success .= ($success ? '<br/>' : '') . 'Ligne n°' . $line->getData('position') . ': équipements assignés avec succès';
                                }
                            }
                        }
                    }
                    if (!$check) {
                        $errors[] = 'Aucun équipement sélectionné';
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionProcessLogitique($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Prise en charge de la logistique effectuée avec succès';

        global $user;

        if (!BimpObject::objectLoaded($user)) {
            $errors[] = 'Aucun utilisateur connecté';
        } else {
            $this->set('id_user_resp', (int) $user->id);
            $this->set('logistique_status', 1);

            $errors = $this->update($warnings, true);

            if (!count($errors)) {
                $this->addLog('Logistique prise en charge');
            }
        }

        $url = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commande&id=' . $this->id;

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'window.location = \'' . $url . '\';'
        );
    }

    public function actionForceStatusMultiple($data, &$success)
    {
        $errors = $warnings = array();
        $nbOk = 0;
        if ($this->canSetAction('forceStatus')) {
            if ($data['status'] == 2) {
                foreach ($data['id_objects'] as $nb => $idT) {
                    $instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $idT);
                    $statutActu = $instance->getData($data['type']);
                    if (isset($statutActu)) {
                        if ($statutActu != $data['status']) {
                            $nbOk++;
                            $instance->actionForceStatus(array($data['type'] => $data['status']), $inut);
                        } else
                            $warnings[] = $instance->getLink() . ' à déja ce statut';
                    } else {
                        $errors[] = 'Type de statut inconnue ' . $data['type'];
                    }
                }
            } else
                $errors[] = 'Statut non valide' . print_r($data, 1);
        } else
            $errors[] = 'Vous n\'avez pas la permission';
        $success = 'Maj status OK (' . $nbOk . ')';
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionForceStatus($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Statut forcé enregistré avec succès';

        $log = 'Changement du forçage des statuts: ';

        $status_forced = $this->getData('status_forced');

        if (isset($data['logistique_status'])) {
            if (!in_array((int) $data['logistique_status'], array(-1, 0, 1, 2, 3, 4, 5))) {
                $errors[] = 'Statut logistique invalide';
            } else {
                if ((int) $data['logistique_status'] === -1) {
                    if (isset($status_forced['logistique'])) {
                        unset($status_forced['logistique']);
                    }
                    if (!(int) $this->getData('logistique_status')) {
                        $this->updateField('logistique_status', 1);
                    }
                    $log .= ' - logistique: aucun';
                } else {
                    $status_forced['logistique'] = 1;
                    $sub_errors = $this->updateField('logistique_status', (int) $data['logistique_status']);
                    if (count($sub_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut logistique de la commande');
                    } else {
                        $log .= ' - logistique: ' . self::$logistique_status[(int) $data['logistique_status']];
                    }
                }
            }
        }

        if (isset($data['shipment_status'])) {
            if (!in_array((int) $data['shipment_status'], array(-1, 0, 1, 2))) {
                $errors[] = 'Statut expédition invalide';
            } else {
                if ((int) $data['shipment_status'] === -1) {
                    if (isset($status_forced['shipment'])) {
                        unset($status_forced['shipment']);
                    }
                    $log .= ' - expédition: aucun';
                } else {
                    $status_forced['shipment'] = 1;
                    $sub_errors = $this->updateField('shipment_status', (int) $data['shipment_status']);
                    if (count($sub_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut expédition de la commande');
                    } else {
                        $log .= ' - expédition: ' . self::$shipment_status[(int) $data['shipment_status']];
                    }
                }
            }
        }

        if (isset($data['invoice_status'])) {
            if (!in_array((int) $data['invoice_status'], array(-1, 0, 1, 2))) {
                $errors[] = 'Statut facturation invalide';
            } else {
                if ((int) $data['invoice_status'] === -1) {
                    if (isset($status_forced['invoice'])) {
                        unset($status_forced['invoice']);
                    }
                    $log .= ' - facturation: aucun';
                } else {
                    $status_forced['invoice'] = 1;
                    $sub_errors = $this->updateField('invoice_status', (int) $data['invoice_status']);
                    if (count($sub_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($sub_errors, 'Echec de la mise à jour du statut facturation de la commande');
                    } else {
                        $log .= ' - facturation: ' . self::$shipment_status[(int) $data['shipment_status']];
                    }
                }
            }
        }

        $errors = $this->updateField('status_forced', $status_forced);
        $this->checkLogistiqueStatus();
        $this->checkShipmentStatus();
        $this->checkInvoiceStatus();

        $this->addLog($log);

        $lines = $this->getLines('not_text');
        foreach ($lines as $line) {
            $line->checkQties();
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionPreuvePaiment($data, &$success)
    {
        $errors = $warnings = array();

        if (isset($data['file']) && $data['file'] != '')
            BimpTools::moveAjaxFile($errors, 'file', $this->getFilesDir(), 'Paiement');
        else
            $errors[] = 'Aucun fichier uploadé';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionForceFacturee($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $forced = $this->getData('status_forced');
        $forced['invoice'] = 1;
        $this->updateField('status_forced', $forced);
        $this->updateField('invoice_status', 2);

        $this->checkInvoiceStatus();

        $lines = $this->getLines('not_text');
        foreach ($lines as $line) {
            $line->checkQties();
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGenerateVignettes($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        if (!$this->isLoaded()) {
            $errors[] = 'ID de la commande absent';
        } else {
            $qty = isset($data['qty']) ? (int) $data['qty'] : 1;

            $url = DOL_URL_ROOT . '/bimplogistique/etiquettes_commande.php?id_commande=' . $this->id . '&qty=' . $qty;

            $success_callback = 'window.open(\'' . $url . '\')';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSendMailLatePayment($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if (!$this->isLoaded())
            $errors[] = '(321) ID ' . $this->getLabel('of_the') . ' absent';
        else {

            if ((int) $this->getData('fk_soc')) {

                if (method_exists($this, 'getClientFacture'))
                    $client = $this->getClientFacture();
                else
                    $client = $this->getChildObject('client');

                if ($client->isLoaded()) {
                    $total_rtp = 0;
                    $subject .= 'Retard de paiement - ' . $client->getData('code_client') . ' - ' . $client->getData('nom');

                    $msg = 'Bonjour ' . $data['user_ask_firstname'] . ', <br/>';

                    $unpaid_factures = $client->getUnpaidFactures('2019-06-30');

                    $detail = '';

                    foreach ($unpaid_factures as $f) {
                        $dates = $f->getRelanceDates();
                        $rtp = $f->getRemainToPay(true);

                        $detail .= $f->getNomUrl() . ' - date limite de règlement au ';
                        $detail .= date('d / m / Y', strtotime($dates['lim']));
                        $detail .= ' - reste à payer de ' . BimpTools::displayMoneyValue($rtp) . '<br/>';
                        $total_rtp += $rtp;
                    }

                    $msg .= "Ce compte client présente un retard de paiement de ";
                    $msg .= BimpTools::displayMoneyValue($total_rtp) . ", dont détail ci-après :<br/>";
                    $msg .= $detail;
                    $msg .= '<br/><br/>Vos commandes en cours ne peuvent donc pas recevoir la validation financière.';

                    $success = 'Mail envoyé à l\'adresse ' . $data['user_ask_email'] . ' pour un total de ';
                    $success .= BimpTools::displayMoneyValue($total_rtp) . ' impayé.';
                    $this->addNote($success . '<br/>' . $msg);

                    mailSyn2($subject, $data['user_ask_email'], null, $msg);
                } else
                    $errors[] = 'Client ' . $this->getLabel('of_the') . 'inconnu';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        );
    }

    // Overrides BimpComm:

    public function onCreate(&$warnings = array())
    {
        // Attention: Alimenter $errors annulera la création. 
        $errors = array();

        if (!$this->isLoaded($warnings)) {
            return $errors;
        }

        $items = BimpTools::getDolObjectLinkedObjectsListByTypes($this->dol_object, $this->db, array('propal'));

        if (isset($items['propal'])) {
            foreach ($items['propal'] as $id_propal) {
                $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $id_propal);
                if (BimpObject::objectLoaded($propal)) {
                    // Création de la ligne de l'intitulé de la propale d'origine si nécessaire: 
                    if (BimpObject::objectLoaded($propal)) {
                        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                        $line->validateArray(array(
                            'id_obj'             => (int) $this->id,
                            'type'               => ObjectLine::LINE_TEXT,
                            'linked_id_object'   => (int) $propal->id,
                            'linked_object_name' => 'propal_origin_label',
                        ));
                        $line->qty = 1;
                        $line->desc = 'Selon notre devis ' . $propal->getRef();
                        $w = array();
                        $line->create($w, true);
                    }
                }
            }
        }

        return $errors;
    }

    public function onValidate(&$warnings = array())
    {
        global $user;

        // Attention: Alimenter $errors annulera la validation. 
        $errors = array();

        $infoClient = "";
        $client = $this->getChildObject('client');
        if (BimpObject::objectLoaded($client)) {
            $infoClient = " du client " . $client->getNomUrl(1, false);
        }

        $res_errors = $this->createReservations();
        if (count($res_errors)) {
            $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la création des réservations');
        }

        if (empty($errors)) {
            if (BimpCore::isModuleActive('bimpvalidation')) {
                // Déplacé dans BimpValidation
            } elseif (BimpCore::isModuleActive('bimpvalidateorder')) {
                // Validation encours
                if (empty($errors)) {
                    if ($this->field_exists('paiement_comptant') and $this->getData('paiement_comptant')) {

                        $vc = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
                        $demande = $vc->demandeExists(ValidComm::OBJ_COMMANDE, $this->id, ValidComm::TYPE_ENCOURS);
                        if ($demande)
                            $demande->delete($warnings, 1);
                        $warnings[] = ucfirst($this->getLabel('the')) . ' ' . $this->getNomUrl(1, true) . " a été validée.";
                        $msg_mail = "Bonjour, <br/><br/>La commande " . $this->getNomUrl(1, true);
                        $msg_mail .= " a été validée financièrement par paiement comptant ou mandat SEPA par ";
                        $msg_mail .= ucfirst($user->firstname) . ' ' . strtoupper($user->lastname);
                        $msg_mail .= "<br/>Merci de vérifier le paiement ultérieurement.";
                        mailSyn2("Validation par paiement comptant ou mandat SEPA", 's.reynaud@bimp.fr', "gle@bimp.fr", $msg_mail);
                    } else {
                        $client_facture = $this->getClientFacture();
                        if (!$client_facture->getData('validation_financiere')) {
                            $vc = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');
                            $demande = $vc->demandeExists(ValidComm::OBJ_COMMANDE, $this->id, ValidComm::TYPE_ENCOURS);
                            if ($demande)
                                $demande->delete($warnings, 1);
                            $warnings[] = ucfirst($this->getLabel('the')) . ' ' . $this->getNomUrl(1, true) . " a été validée (validation financière automatique, voir configuration client)";
//                    $msg_mail = "Bonjour, <br/><br/>La commande " . $this->getNomUrl(1, true);
//                    $msg_mail .= " a été validée financièrement par la configuration du client ";
//                    $msg_mail .= "(utilisateur: " . ucfirst($user->firstname) . ' ' . strtoupper($user->lastname) . ")";
//                    mailSyn2("Validation financière forcée " . $client_facture->getData('code_client') . ' - ' . $client_facture->getData('nom'), 'a.delauzun@bimp.fr', "gle@bimp.fr", $msg_mail);
                        }
                    }
                }

                // Validation retards de paiements
                if (empty($errors)) {
                    if (!$client_facture)
                        $client_facture = $this->getClientFacture();

                    if (!$client_facture->getData('validation_impaye')) {
                        if (!BimpObject::objectLoaded($vc))
                            $vc = BimpCache::getBimpObjectInstance('bimpvalidateorder', 'ValidComm');

                        $demande = $vc->demandeExists(ValidComm::OBJ_COMMANDE, $this->id, ValidComm::TYPE_IMPAYE);
                        if ($demande)
                            $demande->delete($warnings, 1);
                        $warnings[] = "La commande " . $this->getNomUrl(1, true) . " a été validée (validation de retard de paiement automatique, voir configuration client)";
                        $this->addObjectLog("Les retard de paiement ont été validée financièrement par la configuration du client.");
                    }
                }
            }
        }

        if (empty($errors)) {
            $contacts = $this->dol_object->liste_contact(-1, 'internal', 0, 'SALESREPFOLL');
            foreach ($contacts as $contact) {
                $subject = 'Validation commande ' . $this->getRef() . ' client ' . $client->getData('code_client');
                $msg = 'Bonjour<br/>Votre commande ' . $this->getNomUrl(1, true);
                $msg .= ' pour le client ' . $client->getData('code_client') . ' ' . $client->getData('nom') . ' a été validée.';
                mailSyn2($subject, $contact['email'], "gle@bimp.fr", $msg);
            }
        }

        return $errors;
    }

    public function onDelete(&$warnings = array())
    {
        $errors = array();
        $prevDeleteting = $this->isDeleting;
        $this->isDeleting = true;

        if ($this->isLoaded($warnings)) {
            // Suppression des réservations: 
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

            $reservations = $reservation->getListObjects(array(
                'id_commande_client' => $this->isLoaded()
            ));

            foreach ($reservations as $res) {
                $res_warnings = array();
                $res_errors = $res->delete($res_warnings, true);
                $res_errors = BimpTools::merge_array($res_errors, $res_warnings);

                if (count($res_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Erreur lors de la suppression d\'une réservation');
                }
            }
        }

        $errors = BimpTools::merge_array($errors, parent::onDelete($warnings));

        $this->isDeleting = $prevDeleteting;
        return $errors;
    }

    public function checkObject($context = '', $field = '')
    {
        if ($context === 'fetch') {
            global $current_bc, $modeCSV;
            if (is_null($current_bc) || !is_a($current_bc, 'BC_List') &&
                    (is_null($modeCSV) || !$modeCSV)) {
                $this->checkLogistiqueStatus(false);
                $this->checkShipmentStatus(false);
                $this->checkInvoiceStatus(false);
            }
        }
    }

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $new_data['id_facture'] = 0;
        $new_data['date_creation'] = date('Y-m-d H:i:s');
        $new_data['date_valid'] = null;
        $new_data['date_cloture'] = null;
        $new_data['fk_user_author'] = 0;
        $new_data['fk_user_modif'] = 0;
        $new_data['fk_user_valid'] = 0;
        $new_data['fk_user_cloture'] = 0;
        $new_data['shipment_status'] = 0;
        $new_data['invoice_status'] = 0;
        $new_data['logistique_status'] = 0;
        $new_data['extra_status'] = 0;
        $new_data['status_forced'] = array();
        $new_data['id_user_resp'] = 0;
        $new_data['revalorisation'] = 0;

        return parent::duplicate($new_data, $warnings, $force_create);
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();
        $propal = null;

        $origin = BimpTools::getValue('origin', '');
        $origin_id = (int) BimpTools::getValue('origin_id', 0);

        // Fermeture de la propale si nécessaire
        if ((int) BimpTools::getValue('close_propal', 0)) {
            if ($origin === 'propal') {
                if (!$origin_id) {
                    $errors[] = 'ID de la proposition commerciale d\'origine absent';
                } else {
                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $origin_id);

                    if (!BimpObject::objectLoaded($propal)) {
                        $errors[] = 'La proposition commeciale d\'origine d\'ID ' . $origin_id . ' n\'existe pas';
                    } else {
                        $close_errors = array();
                        if (!$propal->isActionAllowed('close', $close_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($close_errors, 'La proposition commerciale ne peut pas être signée');
                        } elseif (!$propal->canSetAction('close')) {
                            $errors[] = 'Vous n\'avez pas la permission de signer la proposition commerciale';
                        } else {
                            $success = '';
                            $result = $propal->actionClose(array(
                                'new_status' => 2
                                    ), $success);
                            if (count($result['errors'])) {
                                $errors = $result['errors'];
                            }
                        }
                    }
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $this->set('date_creation', date('Y-m-d H:i:s'));

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ($origin === 'propal' && (int) $origin_id) {
                if (!BimpObject::objectLoaded($propal)) {
                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $origin_id);
                }
                if (BimpObject::objectLoaded($propal)) {
                    // Copie des notes: 
                    BimpObject::loadClass('bimpcore', 'BimpNote');
                    $note_errors = BimpNote::copyObjectNotes($propal, $this);

                    if (count($note_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($note_errors, 'Erreurs lors de la copie des notes de la propales');
                    }

                    // Copie des remises globales: 
                    $this->copyRemisesGlobalesFromOrigin($propal, $warnings);

                    if ($this->field_exists('id_demande_fin') && $propal->field_exists('id_demande_fin')) {
                        if ((int) $propal->getData('id_demande_fin')) {
                            $demande_fin = $propal->getChildObject('demande_fin');

                            if (BimpObject::objectLoaded($demande_fin)) {
                                $id_client = (int) $demande_fin->getTargetIdClient();
                                if ($id_client) {
                                    $this->updateField('id_client_facture', $id_client);
                                }
                                $this->updateField('id_demande_fin', $demande_fin->id);
                            }
                        }
                    }
                }
            }

            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                $client->setActivity('Création ' . $this->getLabel('of_the') . ' {{Commande:' . $this->id . '}}');
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_entrepot = (int) $this->getInitData('entrepot');
//        $this->setPaiementComptant(); // Eviter de créer des fonctions avec juste 1 ligne, les classes sont déjà bien assez surchargées en fonctions. 
        $this->set('paiement_comptant', $this->isPaiementComptant());

        $this->dol_object->delivery_date = $this->getData('date_livraison');
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($init_entrepot !== (int) $this->getData('entrepot')) {
                $sql = 'UPDATE `' . MAIN_DB_PREFIX . 'br_reservation` SET `id_entrepot` = ' . (int) $this->getData('entrepot');
                $sql .= ' WHERE `id_commande_client` = ' . (int) $this->id . ' AND `id_entrepot` = ' . $init_entrepot . ' AND `status` < 200';
                $this->db->db->query($sql);
            }
        }

        return $errors;
    }

    // Méthodes statiques: 

    public static function checkStatusAll()
    {
        $rows = self::getBdb()->getRows('commande', 'fk_statut IN (1,3)', null, 'array', array('rowid'));

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $comm = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande', (int) $r['rowid']);

                if (BimpObject::objectLoaded($comm)) {
                    $comm->checkLogistiqueStatus(true);
                    $comm->checkShipmentStatus(true);
                    $comm->checkInvoiceStatus(true);
                }
            }
        }
    }

    public static function sendRappels()
    {
        $out = '';

        // Rappels quotidiens: 
        $result = static::sendRappelCommandesBrouillons();
        if ($result) {
            $out .= ($out ? '<br/><br/>' : '') . '----------- Rappels commandes brouillons -----------<br/><br/>' . $result;
        }

        $result = static::checkLinesEcheances();
        if ($result) {
            $out .= ($out ? '<br/><br/>' : '') . '----------- Rappels échéances commandes -----------<br/><br/>' . $result;
        }

        // Rappels Hebdomadaires: 
        if ((int) date('N') == 7) {
            $result = static::sendRappelsNotBilled();
            if ($result) {
                $out .= ($out ? '<br/><br/>' : '') . '------- Rappels commandes non facturées------<br/><br/>' . $result;
            }
        }

        return $out;
    }

    public static function sendRappelCommandesBrouillons()
    {
        $delay = (int) BimpCore::getConf('rappels_commandes_brouillons_delay', null, 'bimpcommercial');

        if (!$delay) {
            return '';
        }

        $return = '';
        $date = new DateTime();
        $date->sub(new DateInterval('P' . $delay . 'D'));

        $bdb = BimpCache::getBdb();
        $where = 'date_creation < \'' . $date->format('Y-m-d') . '\' AND fk_statut = 0';
        $rows = $bdb->getRows('commande', $where, null, 'array', array('rowid'));

        if (!empty($rows)) {
            $commandes = array();

            $id_default_user = (int) BimpCore::getConf('default_id_commercial', null);
            foreach ($rows as $r) {
                $commande = BimpCache::getBimpObjectInstance('bimpcommmercial', 'Bimp_Commande', (int) $r['rowid']);

                if (BimpObject::objectLoaded($commande)) {
                    $id_user = $commande->getIdContact('internal', 'SALESREPSIGN');
                    if (!$id_user) {
                        $id_user = (int) $commande->getData('fk_user_author');
                    }

                    if (!$id_user) {
                        $id_user = $id_default_user;
                    }

                    if (!isset($commandes[$id_user])) {
                        $commandes[$id_user] = array();
                    }

                    $commandes[$id_user][] = $commande->getLink();
                }
            }
        }

        $i = 0;

        if (!empty($commandes)) {
            require_once(DOL_DOCUMENT_ROOT . "/synopsistools/SynDiversFunction.php");

            foreach ($commandes as $id_user => $user_commandes) {
                $msg = 'Bonjour, vous avez laissé ';
                if (count($user_commandes) > 1) {
                    $msg .= count($user_commandes) . ' factures';
                } else {
                    $msg .= 'une facture';
                }

                $msg .= ' à l\'état de brouillon depuis plus de ' . $delay . ' jours.<br/>';
                $msg .= 'Merci de bien vouloir ' . (count($user_commandes) > 1 ? 'les' : 'la') . ' régulariser au plus vite.<br/>';

                foreach ($user_commandes as $link) {
                    $msg .= '<br/>' . $link;
                }

                $mail = BimpTools::getUserEmailOrSuperiorEmail($id_user, true);

                $return .= ' - Mail to ' . $mail . ' : ';
                if (mailSyn2('Commande(s) brouillon à régulariser', BimpTools::cleanEmailsStr($mail), null, $msg)) {
                    $return .= ' [OK]';
                    $i++;
                } else {
                    $return .= ' [ECHEC]';
                }
                $return .= '<br/>';
            }
        }

        return "OK " . $i . ' mail(s)<br/><br/>' . $return;
    }

    public static function sendRappelsNotBilled()
    {
        $out = '';
        $nFails = $nOk = 0;

        $rows = self::getBdb()->getRows('commande', 'fk_statut IN ("1") AND total_ht != 0 AND (date_prevue_facturation < now() OR date_prevue_facturation IS NULL) AND (date_valid <= "' . date("Y-m-d", strtotime("-1 month")) . '") AND invoice_status IN ("0","1")', null, 'array', array('rowid'));

        if (!is_null($rows)) {
            $commandes = array();
            foreach ($rows as $r) {
                $comm = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande', (int) $r['rowid']);

                if (!BimpObject::objectLoaded($comm)) {
                    continue;
                }

                $id_user = (int) $comm->getIdCommercial();

                if (!$id_user) {
                    $id_user = $comm->getIdContact('internal', 'SALESREPSIGN');
                }

                if (!isset($commandes[$id_user])) {
                    $commandes[$id_user] = array();
                }

                $commandes[$id_user][] = $comm;
            }

            if (!empty($commandes)) {
                foreach ($commandes as $id_user => $user_commandes) {
                    foreach ($user_commandes as $commande) {
                        $to = BimpTools::getUserEmailOrSuperiorEmail($id_user, true);

//                        $to .= ($to ? ', ' : '') . 'f.martinez@bimp.fr';

                        if ($to) {
                            $soc = $commande->getChildObject('client');

                            $msg = 'Bonjour,<br/>';
                            $msg .= 'La commande ' . $commande->getLink() . ' du client ' . $soc->getLink() . ', créée le ' . $commande->displayData('date_creation', 'default', 0, 1) . ' n\'est pas facturée.<br/>';
                            $msg .= 'Merci de la régulariser au plus vite.';

                            $out .= ' - Mail to ' . $to . ' : ';
                            if (mailSyn2("Commande " . $commande->getRef() . ' non facturée', $to, '', $msg)) {
                                $out .= '[OK]';
                                $nOk++;
                            } else {
                                $out .= '[ECHEC]';
                                $nFails++;
                            }
                            $out .= '<br/>';

                            if ($nFails > 20 || $nOk > 2100) {
                                break;
                            }
                        }
                    }
                }
            } else {
                return 'Aucune commande à régulariser';
            }
        }

        return $nOk . ' envoi(s) OK - ' . ($nFails ? ' - ' . $nFails . ' échecs' : '') . '<br/><br/>' . $out;
    }

    public static function checkLinesEcheances()
    {
        $delay = (int) BimpCore::getConf('rappels_commandes_echeances_delay', null, 'bimpcommercial');

        if (!$delay) {
            return '';
        }

        $bdb = self::getBdb(true);

        $dt = new DateTime();
        $dt->add(new DateInterval('P' . $delay . 'D'));
        $dt_str = $dt->format('Y-m-d');

        $fields = array('bl.id as id_line', 'c.rowid as id_commande', 'a.date_end');
        $filters = array(
            'a.date_end'             => array(
                'and' => array(
                    'IS_NOT_NULL',
                    array(
                        'operator' => '>=',
                        'value'    => date('Y-m-d')
                    ),
                    array(
                        'operator' => '<=',
                        'value'    => $dt_str
                    )
                )
            ),
            'bl.echeance_notif_send' => 0,
            'c.fk_statut'            => array(
                'operator' => '>',
                'value'    => 0
            )
        );
        $joins = array(
            'c'  => array(
                'table' => 'commande',
                'on'    => 'c.rowid = a.fk_commande'
            ),
            'bl' => array(
                'table' => 'bimp_commande_line',
                'on'    => 'bl.id_line = a.rowid'
            )
        );
        $sql = BimpTools::getSqlFullSelectQuery('commandedet', $fields, $filters, $joins);

        $rows = $bdb->executeS($sql, 'array');

        if (is_array($rows)) {
            // Trie par commerciaux et commandes: 
            $data = array();
            $commercial_params = array(
                'check_active'    => true,
                'allow_superior'  => true,
                'allow_default'   => true,
                'id_default_user' => (int) BimpCore::getConf('id_user_mail_comm_line_expire', (int) BimpCore::getConf('default_id_commercial', null), 'bimpcommercial')
            );
            foreach ($rows as $r) {
                if ((int) $r['id_commande']) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $r['id_commande']);

                    if (BimpObject::objectLoaded($commande)) {
                        $id_commercial = $commande->getCommercialId($commercial_params);

                        if ($id_commercial) {
                            if (!isset($data[$id_commercial])) {
                                $data[$id_commercial] = array();
                            }

                            if (!isset($data[$id_commercial][(int) $r['id_commande']])) {
                                $data[$id_commercial][(int) $r['id_commande']] = array();
                            }

                            $data[$id_commercial][(int) $r['id_commande']][] = (int) $r['id_line'];
                        }
                    }
                }
            }

            if (empty($data)) {
                return 'Aucune échéance à notifier';
            }

            $return = 'Données : <pre>';
            $return .= print_r($data, 1);
            $return .= '</pre><br/><br/>';

            // Envoi des e-mails:
            foreach ($data as $id_commercial => $commandes) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_commercial);

                if (BimpObject::objectLoaded($user)) {
                    $email = $user->getData('email');

                    if ($email) {
                        $subject = 'Produits à durée limitée arrivant bientôt à échéance';
                        $html = '';
                        $nProds = 0;
                        $lines_done = array();

                        foreach ($commandes as $id_commande => $lines) {
                            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

                            if (BimpObject::objectLoaded($commande)) {
                                $body = '';
                                $nLines = 0;
                                foreach ($lines as $id_line) {
                                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);

                                    if (BimpObject::objectLoaded($line)) {
                                        $nLines++;
                                        $nProds++;
                                        $lines_done[] = $line->id;

                                        $body .= '<tr>';
                                        $body .= '<td style="padding: 5px;
                                            width: 300px">';
                                        $body .= $line->displayLineData('desc_light');
                                        $body .= '</td>';

                                        $body .= '<td style="padding: 5px">';
                                        $body .= $line->getFullQty();
                                        $body .= '</td>';

                                        $body .= '<td style="padding: 5px">';
                                        $body .= date('d / m / Y', strtotime($line->date_to));
                                        $body .= '</td>';
                                        $body .= '</tr>';
                                    }
                                }

                                $html .= '<br/><br/><h3>Commande ' . $commande->getLink() . ' (' . $nLines . ' ligne(s) de commande)</h3><br/><br/>';
                                $cli = $commande->getChildObject('client');
                                if (BimpObject::objectLoaded($cli)) {
                                    $html .= '<h3>Commande ' . $cli->getLink() . '</h4><br/><br/>';
                                }

                                $html .= '<table>';
                                $html .= '<thead>';
                                $html .= '<tr>';
                                $html .= '<th style="padding: 5px;
                                            font-weight: bold;
                                            border-bottom: 1px solid #000; background-color: #DCDCDC">Description</th>';
                                $html .= '<th style="padding: 5px; font-weight: bold; border-bottom: 1px solid #000; background-color: #DCDCDC">Qté</th>';
                                $html .= '<th style="padding: 5px; font-weight: bold; border-bottom: 1px solid #000; background-color: #DCDCDC">Echéance</th>';
                                $html .= '</tr>';
                                $html .= '</thead>';

                                $html .= '<tbody>';
                                $html .= $body;
                                $html .= '</tbody>';
                                $html .= '</table>';
                            }
                        }

                        $msg = 'Bonjour,<br/><br/>';
                        $msg .= 'Il y a <b>' . $nProds . '</b> produit(s) vendu(s) à durée limitée qui arrivent à échéance dans ' . $delay . ' jours ou moins.<br/>';
                        $msg .= 'Note: vous ne recevrez pas d\'autre alerte pour les produits listés ci-dessous.<br/><br/>';
                        $msg .= $html;

                        $return .= 'Mail to ' . ($email) . ' (' . $nProds . ' produit(s)) => ';
                        if (mailSyn2($subject, $email, '', $msg)) {
                            $return .= '[OK]';
                            $bdb->update('bimp_commande_line', array(
                                'echeance_notif_send' => 1
                                    ), 'id IN (' . implode(',', $lines_done) . ')');
                        } else {
                            $return .= '[ECHEC]';
                        }
                        $return .= '<br/>';
                    }
                }
            }
        }

        return $return;
    }
}

<?php

require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";
BimpCore::requireFileForEntity('bimpsupport', 'centre.inc.php');

class BS_SAV extends BimpObject
{

    public static $ref_model = 'SAV2{CENTRE}{00000}';
    public static $propal_model_pdf = 'bimpdevissav';
    public static $facture_model_pdf = 'bimpinvoicesav';
    public static $idProdPrio = 3422;
    private $allGarantie = true;
    public $useCaisseForPayments = false;
    public $id_cond_reglement_def = 1; // Obsolète, ne plus utiliser
    public $id_mode_reglement_def = 6; // Idem
    public $allow_force_unlock = true;

    const BS_SAV_RESERVED = -1;
    const BS_SAV_CANCELED_BY_CUST = -2;
    const BS_SAV_CANCELED_BY_USER = -3;
    const BS_SAV_RDV_EXPIRED = -4;
    const BS_SAV_NEW = 0;
    const BS_SAV_ATT_PIECE = 1;
    const BS_SAV_ATT_CLIENT = 2;
    const BS_SAV_DEVIS_ACCEPTE = 3;
    const BS_SAV_REP_EN_COURS = 4;
    const BS_SAV_EXAM_EN_COURS = 5;
    const BS_SAV_DEVIS_REFUSE = 6;
    const BS_SAV_ATT_CLIENT_ACTION = 7;
    const BS_SAV_A_RESTITUER = 9;
    const BS_SAV_FERME = 999;

    //reparation en magasin avec retour avant remplacement
    //pas de doublons Réf centre
    //923-00173

    public static $status_list = array(
        self::BS_SAV_RESERVED          => array('label' => 'Réservé par le client', 'icon' => 'fas_calendar-day', 'classes' => array('important')),
        self::BS_SAV_CANCELED_BY_CUST  => array('label' => 'Annulé par le client', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::BS_SAV_CANCELED_BY_USER  => array('label' => 'Annulé par utilisateur', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::BS_SAV_RDV_EXPIRED       => array('label' => 'Date RDV Dépassée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::BS_SAV_NEW               => array('label' => 'Nouveau', 'icon' => 'far_file', 'classes' => array('info')),
        self::BS_SAV_EXAM_EN_COURS     => array('label' => 'Examen en cours', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        self::BS_SAV_ATT_CLIENT_ACTION => array('label' => 'Attente client', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        self::BS_SAV_ATT_CLIENT        => array('label' => 'Attente acceptation client', 'icon' => 'hourglass-start', 'classes' => array('important')),
        self::BS_SAV_DEVIS_ACCEPTE     => array('label' => 'Devis Accepté', 'icon' => 'check', 'classes' => array('success')),
        self::BS_SAV_DEVIS_REFUSE      => array('label' => 'Devis refusé', 'icon' => 'exclamation-circle', 'classes' => array('danger')),
        self::BS_SAV_ATT_PIECE         => array('label' => 'Attente pièce', 'icon' => 'hourglass-start', 'classes' => array('important')),
        self::BS_SAV_REP_EN_COURS      => array('label' => 'Réparation en cours', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        self::BS_SAV_A_RESTITUER       => array('label' => 'A restituer', 'icon' => 'arrow-right', 'classes' => array('success')),
        self::BS_SAV_FERME             => array('label' => 'Fermé', 'icon' => 'times', 'classes' => array('danger'))
    );
    public static $bon_restit_raison = array(
        0  => "",
        1  => "Refus de devis offert",
        2  => "Produit obsolète",
        3  => "Pas de problème constaté",
        4  => "Réparation interne",
        5  => "Rendu en l’état sans réparation",
        6  => "Intervention sous garantie sans pièce",
        7  => "Contrat",
        8  => "Apple Care non enregistré",
        9  => "Litige client",
        10 => "Contre façon - réparation impossible",
        11 => "Mail-In Apple",
        99 => "Autre",
    );
    public static $status_opened = array(self::BS_SAV_RESERVED, self::BS_SAV_NEW, self::BS_SAV_ATT_PIECE, self::BS_SAV_ATT_CLIENT, self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_REP_EN_COURS, self::BS_SAV_EXAM_EN_COURS, self::BS_SAV_DEVIS_REFUSE, self::BS_SAV_ATT_CLIENT_ACTION, self::BS_SAV_A_RESTITUER);
    public static $need_propal_status = array(2, 3, 4, 5, 6, 9);
    public static $propal_reviewable_status = array(0, 1, 2, 3, 4, 6, 7, 9);
    public static $save_options = array(
        1 => 'Ne désire pas de sauvegarde',
        2 => 'Désire une sauvegarde si celle-ci est possible',
        0 => 'Produit non concerné par une sauvegarde',
//        3 => 'Ne désire pas de sauvegarde (Dispose d\'une sauvegarde Time machine)',
//        4 => 'Ne désire pas de sauvegarde (Ne dispose pas de sauvegarde et n\'en désire pas)'
    );
    public static $save_options_desc = array(
        1 => 'Le client donne son accord pour que toutes ses données soient effacées. Son produit lui sera restitué en configuration usine, aucune de ses données personnelles ne sera présentes.',
        2 => 'Le client autorise ENTITY_NAME à essayer de sauvegarder ses données personnelles. Si cela s’avère impossible aucune intervention ne sera réalisée sur le produit sans accord préalable du client. Le délai d’intervention pourra en être augmenté.'
    );
    public static $contact_prefs = array(
        3 => 'SMS + E-mail',
        1 => 'E-mail',
        2 => 'Téléphone'
    );
    public static $etats_materiel = array(
        1 => array('label' => 'Neuf', 'classes' => array('success')),
        2 => array('label' => 'Bon état général', 'classes' => array('info')),
        3 => array('label' => 'Usagé', 'classes' => array('warning'))
    );
    public static $list_etats_materiel = array('Rayure', 'Écran cassé', 'Liquide');
    public static $list_accessoires = array('Housse', 'Alim', 'Carton', 'Clavier', 'Souris', 'Dvd', 'Batterie', 'Boite complète');
    public static $list_symptomes = array(
        'Ecran cassé',
        'Dégât liquide',
        'Problème batterie',
        'Ne démarre pas électriquement',
        'Machine lente',
        'Démarre électriquement mais ne boot pas',
        'Extinction inopinée',
        'Renouvellement anti virus et maintenance annuelle',
        'Anti virus expiré',
        'Virus ? Eradication? Nettoyage?',
        'Formatage',
        'Réinstallation système'
    );
    public static $list_wait_infos = array(
        'Attente désactivation de la localisation'
    );
    public static $rdv_cancel_reasons = array(
        'CUSTOMER_CANCELLED'    => 'RDV Annulé par le client',
        'CUSTOMER_RESCHEDULED'  => 'RDV reprogrammé ultérieurement',
        'STORE_CLOSURE'         => 'Fermeture boutique',
        'STAFF_UNAVAILABILITY'  => 'Equipe technique non disponible',
        'IMPROPER_RESERVATION'  => 'Réservation invalide',
        'DUPLICATE_RESERVATION' => 'Réservation en doublon'
    );
    public static $default_signature_pc_params = array(
        'page'             => 1,
        'x_pos'            => 155,
        'y_pos'            => 215,
        'width'            => 45,
        'date_x_offset'    => 0,
        'date_y_offset'    => -5,
        'display_date'     => 0,
        'display_nom'      => 0,
        'display_fonction' => 0
    );
    public static $default_signature_resti_params = array(
        'page'          => 1,
        'x_pos'         => 146,
        'width'         => 40,
        'nom_x_offset'  => -32,
        'nom_y_offset'  => 2,
        'date_x_offset' => -32,
        'date_y_offset' => 12,
        'display_date'  => 1,
        'display_nom'   => 1,
    );
    public static $default_signature_destruct_params = array(
        'page'             => 1,
        'x_pos'            => 130,
        'y_pos'            => 240,
        'width'            => 50,
        'date_x_offset'    => -112,
        'date_y_offset'    => -6,
        'ville_x_offset'   => -105,
        'ville_y_offset'   => -15,
        'display_date'     => 1,
        'display_ville'    => 1,
        'display_nom'      => 0,
        'display_fonction' => 0
    );
    public static $check_on_create = 0;
    public static $check_on_update = 0;
    public static $check_on_update_field = 0;
    public static $systems_cache = null;
    public $check_version = true;

    public static function getSaveOptionDesc($choice)
    {
        if (isset(self::$save_options_desc[(int) $choice]))
            return str_replace('ENTITY_NAME', BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport'), self::$save_options_desc[(int) $choice]);
        return null;
    }

    public function __construct($module, $class)
    {
//        parent::__construct("bimpsupport", get_class($this));
        parent::__construct($module, $class);

        $this->useCaisseForPayments = (int) BimpCore::getConf('use_caisse_for_payments');

        BimpMail::$defaultType = 'ldlc';
    }

    // Gestion des droits et autorisations: 

    public function canView()
    {
        global $user;
        return (int) ($user->admin || $user->rights->BimpSupport->read);
    }

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $userClient->getData('id_client') == (int) $this->getData('id_client')) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canCreate()
    {
        return $this->canView();
    }

    public function canEdit()
    {
        return $this->canView();
    }

    public function canClientEdit()
    {
        global $userClient;

        if (!$this->isLoaded()) {
            return 1;
        }

        if (!(int) $this->getData('id_user_client') || (int) $this->getData('status') > 0 || (int) $this->getData('status') == -2) {
            return 0;
        }

        if (BimpObject::objectLoaded($userClient)) {
            if ($userClient->isAdmin()) {
                $sav_userClient = $this->getChildObject('user_client');

                if (BimpObject::objectLoaded($sav_userClient) &&
                        ($sav_userClient->id == $userClient->id || $sav_userClient->getData('id_client') == $userClient->getData('id_client'))) {
                    return 1;
                }
            } elseif ($userClient->id == $this->getData('id_user_client')) {
                return 1;
            }
        }

        return 0;
    }

    public function canDelete()
    {
        global $user;
        return (int) $user->rights->BimpSupport->delete;
    }

    // Getters booléens:

    public function isCreatable($force_create = false, &$errors = [])
    {
        if (!(int) BimpCore::getConf('use_sav', null, 'bimpsupport')) {
            $errors[] = 'Les SAV sont désactivés';
            return 0;
        }
        return 1;
    }

    public function isPropalEditable()
    {
        $propal = $this->getChildObject('propal');

        if (!is_null($propal) && $propal->isLoaded()) {
            if ((int) $propal->getData('fk_statut') !== 0) {
                return 0;
            }
        }
        return 1;
    }

    public function isGratuit()
    {
        $montant = 0;
        $factureAc = $this->getChildObject('facture_acompte');
        if ($factureAc->isLoaded()) {
            $montant += $factureAc->getData('total_ht');
        }
        $propal = $this->getChildObject('propal');
        if ($propal->isLoaded()) {
            $montant += $propal->getData('total_ht');
            $lines = $this->getPropalLines();
            foreach ($lines as $lineS) {
                if ($lineS->getData('linked_object_name') == 'sav_garantie') {
                    $montant -= $lineS->getTotalHT(true);
                }
                if ($lineS->getData('linked_object_name') == 'discount') {
                    $montant -= $lineS->getTotalHT(true);
                }
            }
        }
        if ($montant < 1 && $montant > -1)
            return 1;
        return 0;
    }

    public function isActionAllowed($action, &$errors = array())
    {
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

        $status_error = 'Le statut actuel du SAV n\'est pas valide pour cette action';

        switch ($action) {
            case 'setNew':
                if (!in_array($status, array(self::BS_SAV_RESERVED, self::BS_SAV_CANCELED_BY_CUST, self::BS_SAV_CANCELED_BY_USER, self::BS_SAV_RDV_EXPIRED))) {
                    $errors[] = $status_error;
                    return 0;
                }
                $client = $this->getChildObject('client');
                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Aucun client';
                    return 0;
                }
                if (!$client->isSolvable($this->object_name)) {
                    $errors[] = 'Client non solvable';
                    return 0;
                }
                return 1;

            case 'cancelRdv':
                if ($status == self::BS_SAV_RESERVED) {
                    return 1;
                }
                if ((string) $this->getData('date_rdv') && $status == self::BS_SAV_NEW) {
                    return 1;
                }
                $errors[] = $status_error;
                return 0;

            case 'start':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (!in_array($status, array(self::BS_SAV_NEW, self::BS_SAV_ATT_CLIENT_ACTION))) {
                    $errors[] = $status_error;
                    return 0;
                }
                return 1;

            case 'propalAccepted':
            case 'propalRefused':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (is_null($propal)) {
                    $errors[] = 'Devis absent ou invalide';
                    return 0;
                }
                if ($propal_status !== 1) {
                    $errors[] = 'Le devis n\'a pas le statut "validé"';
                    return 0;
                }

                if (in_array($status, array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_DEVIS_REFUSE, self::BS_SAV_FERME))) {
                    $errors[] = $status_error;
                    return 0;
                }
                return 1;

            case 'validate_propal':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (is_null($propal)) {
                    $errors[] = 'Devis absent';
                    return 0;
                }
                if ($propal_status !== 0) {
                    $errors[] = 'Le devis n\'est pas au statut "Brouillon"';
                    return 0;
                }
                if (in_array($status, array(self::BS_SAV_ATT_CLIENT_ACTION, self::BS_SAV_FERME))) {
                    $errors[] = $status_error;
                    return 0;
                }
                return 1;

            case 'reviewPropal':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (is_null($propal)) {
                    $errors[] = 'Devis absent';
                    return 0;
                }
                if ($propal_status === 0) {
                    $errors[] = 'Devis déjà au statut "Brouillon"';
                    return 0;
                }
                if (!in_array($status, self::$propal_reviewable_status)) {
                    $errors[] = $status_error;
                    return 0;
                }
                return 1;

            case 'waitClient':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (!is_null($propal) && $propal_status > 0) {
                    $errors[] = 'Le devis est validé';
                    return 0;
                }

                if (in_array($status, array(self::BS_SAV_ATT_CLIENT_ACTION, self::BS_SAV_FERME))) {
                    $errors[] = $status_error;
                    return 0;
                }
                return 1;

            case 'toRestitute':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (!in_array($status, array(self::BS_SAV_REP_EN_COURS, self::BS_SAV_DEVIS_REFUSE))) {
                    $errors[] = $status_error;
                    return 0;
                }

                if ($status === self::BS_SAV_REP_EN_COURS && (is_null($propal) || $propal_status === 0)) {
                    $errors[] = 'Devis absent ou non validé';
                    return 0;
                }

                if ($status === self::BS_SAV_DEVIS_REFUSE && (is_null($propal))) {
                    $errors[] = 'Devis absent';
                    return 0;
                }
                return 1;

            case 'close':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (is_null($propal) && $status === self::BS_SAV_FERME) {
                    $errors[] = 'Ce SAV est déjà fermé';
                    return 0;
                }
                if (!is_null($propal) && $status !== self::BS_SAV_A_RESTITUER) {
                    $errors[] = $status_error;
                    return 0;
                }
                return 1;

            case 'createSignaturePC':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ($status < 0 || $status === 999) {
                    $errors[] = 'Ce SAV n\'est plus actif';
                    return 0;
                }

                if ((int) $this->getData('id_signature_pc')) {
                    $signature = $this->getChildObject('signature_pc');

                    if (BimpObject::objectLoaded($signature)) {
                        $errors[] = 'Signature déjà créée';
                        return 0;
                    } else {
                        $this->updateField('id_signature_pc', 0);
                    }
                }

                return 1;

            case 'createSignatureRestitution':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ($status !== 999) {
                    $errors[] = 'Ce SAV n\'est pas fermé';
                    return 0;
                }

                if ((int) $this->getData('id_signature_resti')) {
                    $signature = $this->getChildObject('signature_resti');

                    if (BimpObject::objectLoaded($signature)) {
                        $errors[] = 'Signature déjà créée';
                        return 0;
                    } else {
                        $this->updateField('id_signature_resti', 0);
                    }
                }

                return 1;

            case 'generateRestiPdf':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ($status !== 999) {
                    $errors[] = 'Ce SAV n\'est pas fermé';
                    return 0;
                }

                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function needEquipmentAttribution()
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpsupport', 'BS_SavPropalLine');
            $lines = $this->getPropalLines(array(
                'type'               => BS_SavPropalLine::LINE_PRODUCT,
                'linked_object_name' => ''
            ));
            foreach ($lines as $line) {
                if ($line->hasEquipmentToAttribute()) {
                    return 1;
                }
            }
        }

        return 0;
    }

    public function hasParts()
    {
        if ($this->isLoaded()) {
            return ((int) $this->db->getCount('bs_apple_part', '`id_sav` = ' . (int) $this->id) ? 1 : 0);
        }

        return 0;
    }

    public function hasTierParts()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT COUNT(p.id) as number FROM ' . MAIN_DB_PREFIX . 'bs_apple_part p';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bs_sav_issue i ON p.id_issue = i.id';
            $sql .= ' WHERE p.id_sav = ' . (int) $this->id;
            $sql .= ' AND i.category_code = \'\'';

            $res = $this->db->executeS($sql, 'array');

            if (isset($res[0]['number']) && (int) $res[0]['number'] > 0) {
                return 1;
            }
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (!$force_edit) {
            switch ($field) {
                case 'code_centre_repa':
                    if ((int) $this->getData('status') < 0) {
                        return 1;
                    }

                    if (!in_array((int) $this->getData('status'), array(0, 1, 2, 3, 5, 6, 7))) {
                        return 0;
                    }
                    break;
            }
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function showPropalSignature()
    {
        $propal = $this->getChildObject('propal');

        if (BimpObject::objectLoaded($propal)) {
            return (int) $propal->getData('id_signature');
        }

        return 0;
    }

    public function needSignaturePropal()
    {
        $propal = $this->getChildObject('propal');

        if (BimpObject::objectLoaded($propal)) {
            if ((float) $propal->dol_object->total_ttc != 0) {
                return 1;
            }
        }

        return 0;
    }

    public function isRibClientRequired()
    {
        $propal = $this->getChildObject('propal');
        if (BimpObject::objectLoaded($propal)) {
            if (in_array((int) $propal->getData('fk_mode_reglement'), explode(',', BimpCore::getConf('rib_client_required_modes_paiement', null, 'bimpcommercial')))) {
                return 1;
            }
        }

        return 0;
    }

    // Getters params: 

    public function getCreateJsCallback()
    {
        $js = '';

        if ((int) $this->getData('status') === self::BS_SAV_NEW) {
            $ref = 'PC-' . $this->getData('ref');
//            if (file_exists(DOL_DATA_ROOT . '/bimpcore/sav/' . $this->id . '/' . $ref . '.pdf')) {
//                $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('sav/' . $this->id . '/' . $ref . '.pdf');
//                $js .= 'window.open("' . $url . '");';
//            }
//
            $id_facture_account = (int) $this->getData('id_facture_acompte');
            if ($id_facture_account) {
                $facture = $this->getChildObject('facture_acompte');
                if (BimpObject::objectLoaded($facture)) {
                    $ref = $facture->getData('ref');
                    if (file_exists(DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf')) {
                        $url = DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . htmlentities('/' . $ref . '/' . $ref . '.pdf');
                        $js .= 'window.open("' . $url . '");';
                    }
                }
            }

            if ((int) $this->getData('id_signature_pc')) {
                $signataire = BimpCache::findBimpObjectInstance('bimpcore', 'BimpSignataire', array(
                            'id_signature' => $this->getData('id_signature_pc'),
                            'code'         => 'default'
                                ), true);

                if (BimpObject::objectLoaded($signataire) && $signataire->isActionAllowed('signElec')) {
                    $js .= 'setTimeout(function() {' . $signataire->getJsActionOnclick('signElec', array(), array(
                                'form_name'   => 'sign_elec',
                                'no_button'   => true,
                                'modal_title' => 'Signature électronique du bon de prise en charge "' . $ref . '"'
                            )) . '}, 500);';
                }
            }
        }
        return $js;
    }

    public function getClientExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
//            $data = '{module: \'' . $this->module . '\', object_name: \'' . $this->object_name . '\', id_object: ' . $this->id . ', form_name: \'contact\'}';
//            $onclick = 'loadModalForm($(this), ' . $data . ', \'Recontacter\');';
            $buttons[] = array(
                'label'   => 'Recontacter',
                'icon'    => 'envelope',
                'onclick' => $this->getJsActionOnclick('recontact', array(), array(
                    'form_name' => 'contact'
                ))
            );
        }

        return $buttons;
    }

    public function getInfosExtraBtn()
    {
        $buttons = array();

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Générer Bon de prise en charge',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('generatePDF', array(
                    'file_type' => 'pc'
                        ), array(
                    'success_callback' => $callback
                ))
            );

            $onclick = 'generatePDFFile($(this), ' . $this->id . ', \'destruction\');';
            $buttons[] = array(
                'label'   => 'Générer Bon de destruction client',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('generatePDF', array(
                    'file_type' => 'destruction'
                        ), array(
                    'success_callback' => $callback
                ))
            );

            $onclick = 'generatePDFFile($(this), ' . $this->id . ', \'destruction2\');';
            $buttons[] = array(
                'label'   => 'Générer Bon de destruction tribunal',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('generatePDF', array(
                    'file_type' => 'destruction2'
                        ), array(
                    'success_callback' => $callback
                ))
            );

            $onclick = 'generatePDFFile($(this), ' . $this->id . ', \'europe\');';
            $buttons[] = array(
                'label'   => 'Générer Doc Loi Européenne',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('generatePDF', array(
                    'file_type' => 'europe',
                        ), array(
                    'success_callback' => $callback,
                    'form_name'        => 'europe'
                ))
            );

            $onclick = 'generatePDFFile($(this), ' . $this->id . ', \'irreparable\');';
            $buttons[] = array(
                'label'   => 'Générer Doc Irreparable',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('generatePDF', array(
                    'file_type' => 'irreparable'
                        ), array(
                    'success_callback' => $callback
                ))
            );
        }

        return $buttons;
    }

    public function getListFilters()
    {
        $filters = array();
        if (BimpTools::isSubmit('id_entrepot') && BimpTools::getValue('id_entrepot') != '') {
            $entrepots = explode('-', BimpTools::getValue('id_entrepot'));

            $filters[] = array('name'   => 'id_entrepot', 'filter' => array(
                    'IN' => implode(',', $entrepots)
            ));
        }

        if (BimpTools::isSubmit('code_centre')) {
            $codes = explode('-', BimpTools::getValue('code_centre'));
            foreach ($codes as &$code) {
                $code = "'" . $code . "'";
            }
            $filters[] = array('name'   => 'code_centre', 'filter' => array(
                    'IN' => implode(',', $codes)
            ));
        }

        if (BimpTools::isSubmit('status')) {
            $filters[] = array('name' => 'status', 'filter' => (int) BimpTools::getValue('status'));
        }

        return $filters;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->isActionAllowed('cancelRdv') && $this->canSetAction('cancelRdv')) {
                $buttons[] = array(
                    'label'   => 'RDV annulé',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('cancelRdv', array(), array(
                        'form_name' => 'cancel_rdv'
                    ))
                );
            }

            if ((int) $this->getData('status') < 0) {
                if ($this->isActionAllowed('setNew') && $this->canSetAction('setNew')) {
                    $url = $this->getUrl();
                    $buttons[] = array(
                        'label'   => 'Prendre en charge',
                        'icon'    => 'fas_cogs',
                        'onclick' => $this->getJsActionOnclick('setNew', array(), array(
                            'form_name' => 'prise_en_charge',
//                            'success_callback' => 'function() {window.open(\'' . $url . '\');}'
                        ))
                    );
                }
            } else {
                $ref = 'PC-' . $this->getData('ref');
                if (file_exists(DOL_DATA_ROOT . '/bimpcore/sav/' . $this->id . '/' . $ref . '.pdf')) {
                    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('sav/' . $this->id . '/' . $ref . '.pdf');
                    $buttons[] = array(
                        'label'   => 'Bon de prise en charge',
                        'icon'    => 'fas_file-pdf',
                        'onclick' => 'window.open(\'' . $url . '\')'
                    );
                }
            }
        }

        return $buttons;
    }

    public function getPublicListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->can('view')) {
                $url = $this->getPublicUrl();

                if ($url) {
                    $buttons[] = array(
                        'label'   => 'Voir le détail',
                        'icon'    => 'fas_eye',
                        'onclick' => 'window.location = \'' . $url . '\''
                    );
                }
            }

            if ($this->canClientEdit() && $this->getData('resgsx') && $this->getData('status') == -1) {

                $url = self::getPublicBaseUrl() . 'fc=savForm&cancel_rdv=1&sav=' . $this->id . '&r=' . $this->getRef() . '&res=' . $this->getData('resgsx');
                $buttons[] = array(
                    'label'   => 'Annuler le RDV',
                    'icon'    => 'fas_times',
                    'onclick' => 'window.location = \'' . $url . '\''
                );
            }
        }

        return $buttons;
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

            if ($this->isActionAllowed('cancelRdv') && $this->canSetAction('cancelRdv')) {
                $buttons[] = array(
                    'label'   => 'RDV annulé',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('cancelRdv', array(), array(
                        'form_name' => 'cancel_rdv'
                    ))
                );
            }

            if ($status < 0) {
                $errors = array();
                if ($this->isActionAllowed('setNew', $errors)) {
                    if ($this->canSetAction('setNew')) {
                        $buttons[] = array(
                            'label'   => 'Prendre en charge',
                            'icon'    => 'fas_cogs',
                            'onclick' => $this->getJsActionOnclick('setNew', array(), array(
                                'form_name' => 'prise_en_charge'
                            ))
                        );
                    }
                } else {
                    $buttons[] = array(
                        'label'    => 'Prendre en charge',
                        'icon'     => 'fas_cogs',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => BimpTools::getMsgFromArray($errors)
                    );
                }
            } else {
                // Devis accepté / refusé: 
                if ($this->isActionAllowed('propalAccepted')) {
                    $buttons[] = array(
                        'label'   => 'Devis accepté',
                        'icon'    => 'check',
                        'onclick' => $this->getJsActionOnclick('propalAccepted')
                    );
                }

                if (BimpObject::objectLoaded($propal) && $propal->isActionAllowed('createSignature')) {
                    $buttons[] = array(
                        'label'   => 'Créer signature devis',
                        'icon'    => 'fas_signature',
                        'onclick' => $propal->getJsActionOnclick('createSignature', array(), array(
                            'form_name' => 'create_signature'
                        ))
                    );
                }

                if ($this->isActionAllowed('propalRefused')) {
                    $buttons[] = array(
                        'label'   => 'Devis refusé',
                        'icon'    => 'times',
                        'onclick' => $this->getJsActionOnclick('propalRefused')
                    );
                }

                // Mettre en attente client: 
                if ($this->isActionAllowed('waitClient')) {
                    $buttons[] = array(
                        'label'   => 'Mettre en attente client',
                        'icon'    => 'hourglass-start',
                        'onclick' => $this->getJsActionOnclick('waitClient', array(), array(
                            'form_name' => 'wait_client'
                        ))
                    );
                }

                // Commencer diagnostic: 
                if ($this->isActionAllowed('start')) {
                    $buttons[] = array(
                        'label'   => 'Commencer diagnostic',
                        'icon'    => 'arrow-circle-right',
                        'onclick' => $this->getJsActionOnclick('start', array(), array(
                            'form_name' => 'send_msg'
                        ))
                    );
                }

                // Pièce reçue: 
                if (in_array($status, array(self::BS_SAV_ATT_PIECE))) {
                    $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_REP_EN_COURS . ', 1)';
                    $buttons[] = array(
                        'label'   => 'Pièce reçue',
                        'icon'    => 'check',
                        'onclick' => $onclick
                    );
                }

                // Commande piece: 
                if (in_array($status, array(self::BS_SAV_REP_EN_COURS, self::BS_SAV_DEVIS_ACCEPTE))) {
                    $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_ATT_PIECE . ', 1)';
                    $buttons[] = array(
                        'label'   => 'Attente pièce',
                        'icon'    => 'check',
                        'onclick' => $onclick
                    );
                }

                // Réparation en cours: 
                if (!in_array($status, array(self::BS_SAV_REP_EN_COURS, self::BS_SAV_A_RESTITUER, self::BS_SAV_FERME))) {
                    if (!is_null($propal) && $propal_status > 0) {
                        if ($propal->isSigned()) {
                            $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_REP_EN_COURS . ', 0)';
                            $buttons[] = array(
                                'label'   => 'Réparation en cours',
                                'icon'    => 'wrench',
                                'onclick' => $this->getJsActionOnclick('startRepair')
                            );
                        } else {
                            $buttons[] = array(
                                'label'    => 'Réparation en cours',
                                'icon'     => 'wrench',
                                'onclick'  => '',
                                'disabled' => 1,
                                'popover'  => 'Devis non signé'
                            );
                        }
                    }
                }

                // Réparation terminée: 
                if ($this->isActionAllowed('toRestitute')) {
                    if (in_array($status, array(self::BS_SAV_REP_EN_COURS))) {
                        if (!is_null($propal) && $propal_status > 0) {
                            $buttons[] = array(
                                'label'   => 'Réparation terminée',
                                'icon'    => 'check',
                                'onclick' => $this->getJsActionOnclick('toRestitute', array(), array('form_name' => 'resolution'))
                            );
                        }
                    }

                    // Fermer SAV (devis refusé) : 
                    if (in_array($status, array(self::BS_SAV_DEVIS_REFUSE))) {
                        if (!is_null($propal)) {
                            $frais = 0;
                            foreach ($propal->dol_object->lines as $line) {
                                if ($line->desc === 'Acompte') {
                                    $frais = -$line->total_ttc;
                                }
                            }

                            $buttons[] = array(
                                'label'   => 'Fermer le SAV',
                                'icon'    => 'times-circle',
                                'onclick' => $this->getJsActionOnclick('toRestitute', array(
                                    'frais' => $frais
                                        ), array(
                                    'form_name' => 'close_refused'
                                ))
                            );
                        }
                    }
                }

                // Restituer (payer) 
                if ($this->isActionAllowed('close')) {
                    if (!is_null($propal)) {
//                    $cond_reglement = 0;
//
//                    if (BimpObject::objectLoaded($propal)) {
//                        $cond_reglement = (int) $propal->getData('fk_cond_reglement');
//                    }
//
//                    if (!$cond_reglement) {
//                        $client = $this->getChildObject('client');
//
//                        if (BimpObject::objectLoaded($client)) {
//                            $cond_reglement = (int) $client->getData('cond_reglement');
//                        }
//                    }

                        $buttons[] = array(
                            'label'   => 'Restituer (Payer)',
                            'icon'    => 'times-circle',
                            'onclick' => $this->getJsActionOnclick('close', array(
                                'restitute' => 1,
//                            'cond_reglement' => $cond_reglement
                                    ), array(
                                'form_name' => 'restitute'
                            ))
                        );
                    } else {
                        $buttons[] = array(
                            'label'   => 'Restituer',
                            'icon'    => 'times-circle',
                            'onclick' => $this->getJsActionOnclick('close', array('restitute' => 1), array())
                        );
                    }
                }

                //Générer devis 
//            if (!is_null($propal) && $propal_status === 0 && $status !== self::BS_SAV_FERME) {
//                $buttons[] = array(
//                    'label'   => 'Générer devis',
//                    'icon'    => 'cogs',
//                    'onclick' => $this->getJsActionOnclick('generatePropal', array(), array(
//                        'confirm_msg' => "Attention, la proposition commerciale va être entièrement générée à partir des données du SAV.\\nTous les enregistrements faits depuis la fiche propale ne seront pas pris en compte"
//                    ))
//                );
//            }
                // Attribuer un équipement
//            if ($this->needEquipmentAttribution()) {
//                $buttons[] = array(
//                    'label'   => 'Attribuer un équipement',
//                    'icon'    => 'arrow-circle-right',
//                    'onclick' => $this->getJsActionOnclick('attibuteEquipment', array(), array('form_name' => 'equipment'))
//                );
//            }
                // Créer Devis 
                if (is_null($propal) && $status < 999) {
                    $buttons[] = array(
                        'label'   => 'Créer Devis',
                        'icon'    => 'plus-circle',
                        'onclick' => 'createNewPropal($(this), ' . $this->id . ');'
                    );
                }

                // Réviser devis:  
                if ($this->isActionAllowed('reviewPropal')) {
//                $callback = 'function() {bimp_reloadPage();}';
                    $buttons[] = array(
                        'label'   => 'Réviser Devis',
                        'icon'    => 'edit',
                        'onclick' => $this->getJsActionOnclick('reviewPropal', array(), array(
//                        'success_callback' => $callback,
                            'confirm_msg' => 'Veuillez confirmer la révision du devis'
                        ))
                    );
                }

                // Envoyer devis: 
                if ($this->isActionAllowed('validate_propal')) {
//                $callback = 'function() {bimp_reloadPage();}';
                    $buttons[] = array(
                        'label'   => 'Envoyer devis',
                        'icon'    => 'arrow-circle-right',
                        'onclick' => $this->getJsActionOnclick('validatePropal', array(), array(
                            'form_name' => 'validate_propal',
//                        'success_callback' => $callback
                        ))
                    );
                }

                // Ajouter acompte: 
                $onclick = '';

                $err = array();

                if ($this->isActionAllowed('validate_propal') && !(int) $this->getData('id_facture_acompte')) {
                    $onclick = $this->getJsActionOnclick('addAcompte', array(), array(
                        'form_name' => 'add_acompte'
                    ));
                } elseif (BimpObject::objectLoaded($propal) && $propal->isActionAllowed('addAcompte', $err)) {
                    $id_mode_paiement = 0;
                    $client = $propal->getChildObject('client');
                    if (BimpObject::objectLoaded($client)) {
                        $id_mode_paiement = $client->dol_object->mode_reglement_id;
                    }

                    $onclick = $this->getJsActionOnclick('addAcompte', array(
                        'id_mode_paiement' => $id_mode_paiement
                            ), array(
                        'form_name' => 'acompte'
                    ));
                }

                if ($onclick) {
                    $buttons[] = array(
                        'label'   => 'Ajouter un acompte',
                        'icon'    => 'fas_hand-holding-usd',
                        'onclick' => $onclick
                    );
                }

                // Payer facture: 
                if ((int) $this->getData('id_facture')) {
                    $facture = $this->getChildObject('facture');
                    if (!(int) $facture->dol_object->paye) {
                        $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_Paiement');
                        $values = array(
                            'fields' => array(
                                'id_client'  => (int) $this->getData('id_client'),
                                'id_facture' => (int) $this->getData('id_facture')
                            )
                        );
                        $buttons[] = array(
                            'label'   => 'Payer facture',
                            'icon'    => 'euro',
                            'onclick' => $paiement->getJsLoadModalForm('default', 'Paiement de la facture ' . $facture->dol_object->ref, $values)
                        );
                    }
                }
            }
        }

        global $user;
        if (($user->admin || $user->id == 60 || $user->id == 282 || $user->id == 78) && BimpObject::objectLoaded($propal)) {
            $propal->module = 'bimpcommercial';
            $buttons[] = array(
                'label'   => 'Fiche Propale ' . $propal->id,
                'icon'    => 'fas_file',
                'onclick' => 'window.open(\'' . BimpObject::getInstanceUrl($propal->dol_object) . "&redirectForce_oldVersion=1" . '\')'
            );
            $propal->module = 'bimpsupport';
        }

        return $buttons;
    }

    public function getEquipmentSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((string) $value) {
            $joins['equip'] = array(
                'table' => 'be_equipment',
                'alias' => 'equip',
                'on'    => $main_alias . '.id_equipment = equip.id'
            );
            $filters['or_equipment'] = array(
                'or' => array(
                    'equip.serial'        => array(
                        'part_type' => 'middle', // ou middle ou end
                        'part'      => $value
                    ),
                    'equip.product_label' => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'equip.warranty_type' => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    )
                )
            );
        }
    }

    public function getEquipementSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $filters['or_equipment'] = array(
            'or' => array(
                'e.serial'        => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                'e.product_label' => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                'e.warranty_type' => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
            )
        );
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'date_facturation':
                $values_filters = array();

                foreach ($values as $value) {
                    $filter = BC_Filter::getRangeSqlFilter($value, $errors, true, $excluded);
                    if (!empty($filter)) {
                        $values_filters[] = $filter;
                    }
                }

                if (!empty($values_filters)) {
                    $fac_alias = $main_alias . '___facture';
                    $joins[$fac_alias] = array(
                        'alias' => $fac_alias,
                        'table' => 'facture',
                        'on'    => $fac_alias . '.rowid = ' . $main_alias . '.id_facture'
                    );

                    $avoir_alias = $main_alias . '___facture_avoir';
                    $joins[$avoir_alias] = array(
                        'alias' => $avoir_alias,
                        'table' => 'facture',
                        'on'    => $avoir_alias . '.rowid = ' . $main_alias . '.id_facture_avoir'
                    );

                    $filters[$main_alias . 'date_facturation' . ($excluded ? '_excluded' : '')] = array(
                        ($excluded ? 'and_fields' : 'or') => array(
                            $fac_alias . '.datef'   => array(
                                ($excluded ? 'and' : 'or_field') => $values_filters
                            ),
                            $avoir_alias . '.datef' => array(
                                ($excluded ? 'and' : 'or_field') => $values_filters
                            )
                        )
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function getPublicUrlParams($internal = true)
    {
        return 'tab=sav&content=card&id_sav=' . $this->id;
    }

    public function getPublicListPageUrlParams()
    {
        return 'tab=sav';
    }

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            return DOL_DATA_ROOT . '/bimpcore/sav/' . $this->id . '/';
        }

        return '';
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        if (!$file_name) {
            return '';
        }

        if (!$this->isLoaded()) {
            return '';
        }

        $file = 'sav/' . $this->id . '/' . $file_name;

        return DOL_URL_ROOT . '/' . $page . '.php?modulepart=bimpcore&file=' . urlencode($file);
    }

    public function getEmailClientFromType()
    {
        return 'ldlc';
    }

    // Getters array: 

    public function getClient_contactsArray()
    {
        return $this->getSocieteContactsArray((int) $this->getData('id_client'));
    }

    public function getContratsArray()
    {
        return $this->getSocieteContratsArray((int) $this->getData('id_client'));
    }

    public function getPropalsArray()
    {
        return $this->getSocietePropalsArray((int) $this->getData('id_client'));
    }

    public function getIssuesArray($include_empty = false)
    {
        if ($this->isLoaded()) {
            $cache_key = 'sav_' . $this->id . '_issues_array';

            if (!isset(self::$cache[$cache_key])) {
                $issues = $this->getChildrenObjects('issues');

                foreach ($issues as $issue) {
                    $label = '';
                    if ((string) $issue->getData('category_label')) {
                        $label .= $issue->getData('category_label');
                    }

                    if ((string) $issue->getData('issue_label')) {
                        $label .= ($label ? ' - ' : '') . $issue->getData('issue_label');
                    }

                    $repro = (string) $issue->displayData('reproducibility', 'default', false, true);

                    if ($repro) {
                        $label .= ($label ? ' - ' : '') . $repro;
                    }

                    self::$cache[$cache_key][(int) $issue->id] = $label;
                }
            }

            return self::getCacheArray($cache_key, $include_empty);
        }

        return array();
    }

    // Getters données: 

    public function getPropalLines($filters = array())
    {
        $propal = $this->getChildObject('propal');

        if (BimpObject::objectLoaded($propal)) {
            return $propal->getChildrenObjects('lines', $filters);
        }

        return array();
    }

    public function getNomUrl($withpicto = true, $ref_only = true, $page_link = false, $modal_view = '', $card = '')
    {
        if (!$this->isLoaded()) {
            return '';
        }

        if (!$modal_view) {
            $statut = self::$status_list[$this->data["status"]];
            return "<a href='" . $this->getUrl() . "'>" . '<span class="' . ($statut['classes'] && is_array($statut['classes']) ? implode(" ", $statut['classes']) : '') . '"><i class="' . BimpRender::renderIconClass($statut['icon']) . ' iconLeft"></i>' . $this->getRef() . '</span></a>';
        }

        return parent::getNomUrl($withpicto, $ref_only, $page_link, $modal_view, $card);
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

        $mask = str_replace('{CENTRE}', (string) $this->getData('code_centre'), $mask);

        return($tmp->getNextValue($objsoc, $this, $mask));
    }

    public function getDefaultCodeCentreRepa()
    {
        global $tabCentre;
        if (isset($tabCentre[$this->getData('code_centre')]) && isset($tabCentre[$this->getData('code_centre')][10]))
            return $tabCentre[$this->getData('code_centre')][10];
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '', &$filters = array())
    {
        $fields = array('date_create', 'date_pc', 'date_close');

        $fieldPrinc = str_replace('j_', '', $field);
        if (in_array($fieldPrinc, $fields)) {
            return 'if(' . $main_alias . '.' . $fieldPrinc . ', DayOfWeek(' . $main_alias . '.' . $fieldPrinc . ')-1, 10)';
        }


        $fieldPrinc = str_replace('h_', '', $field);
        if (in_array($fieldPrinc, $fields)) {
            return 'if(' . $main_alias . '.' . $fieldPrinc . ', DATE_FORMAT(' . $main_alias . '.' . $fieldPrinc . ', "%H"), 10)';
        }

        return '';
    }

    public function fetchExtraFields()
    {
        $fields = array('date_create', 'date_pc', 'date_close');
        $extra = array();

        foreach ($fields as $field) {
            if ($this->getData($field)) {
                $date = strtotime($this->getData($field));
                $extra['j_' . $field] = date('w', $date);
                $extra['h_' . $field] = date('H', $date);
            } else {
                $extra['j_' . $field] = 10;
                $extra['h_' . $field] = 0;
            }
        }
        return $extra;
    }

    public function getCentreData($centre_repa = false)
    {
        $code_centre = '';

        if ($centre_repa) {
            $code_centre = (string) $this->getData('code_centre_repa');
        }

        if (!$code_centre) {
            $code_centre = (string) $this->getData('code_centre');
        }

        if ($code_centre) {
            global $tabCentre;

            if (isset($tabCentre[$code_centre])) {
                return array(
                    'tel'         => $tabCentre[$code_centre][0],
                    'mail'        => $tabCentre[$code_centre][1],
                    'label'       => $tabCentre[$code_centre][2],
                    'shipTo'      => $tabCentre[$code_centre][4],
                    'zip'         => $tabCentre[$code_centre][5],
                    'town'        => $tabCentre[$code_centre][6],
                    'address'     => $tabCentre[$code_centre][7],
                    'id_entrepot' => $tabCentre[$code_centre][8],
                    'ship_to'     => $tabCentre[$code_centre][4]
                );
            }
        }

        return null;
    }

    public function getNomMachine()
    {
        if ($this->isLoaded()) {
            $equipment = $this->getChildObject('equipment');
            if (!is_null($equipment) && $equipment->isLoaded()) {
                return $equipment->displayProduct('nom', true);
            }
        }

        return '';
    }

    public function getFactureAmountToPay()
    {
        if ((int) $this->getData('id_facture')) {
            $facture = $this->getChildObject('facture');
            if (BimpObject::objectLoaded($facture)) {
                return $facture->getRemainToPay();
            }
        }

        if ((int) $this->getData('id_propal')) {
            $propal = $this->getChildObject('propal');
            if (BimpObject::objectLoaded($propal)) {
                return (float) round($propal->dol_object->total_ttc, 2);
            }
        }

        return 0;
    }

    public function getSerial($first = false)
    {
        if ($first) {
            $equip = $this->getChildObject("equipment");
            if ($equip->getData('old_serial') != '') {
                $tabSerial = explode('<br/>', $equip->getData('old_serial'));
                foreach ($tabSerial as $part) {
                    if (stripos($part, 'Serial : ') !== false)
                        return str_replace('Serial : ', '', $part);
                }
            }
        }

        $equipment = $this->getChildObject('equipment');
        if (BimpObject::objectLoaded($equipment)) {
            return (string) $equipment->getData('serial');
        }

        return '';
    }

    public function getShipTo()
    {
        $centre = $this->getCentreData(true);

        if (isset($centre['shipTo'])) {
            return $centre['shipTo'];
        }

        return '';
    }

    public function getListCentre($field, $include_empty = false)
    {
        if ($this->isLoaded())
            $value = $this->getData($field);
        else
            $value = '';

        return static::getUserCentresArray($value, $include_empty);
    }

    public function getDefaultSignDistEmailContent()
    {
        $message = 'Bonjour, <br/><br/>';

        $message = "Vous trouvez ci-joint le devis pour la réparation de votre '" . $this->getNomMachine() . ".<br/><br/>";
        $message .= 'Vous pouvez effectuer la signature électronique de ce document directement depuis votre {LIEN_ESPACE_CLIENT} ou nous retourner le document ci-joint signé.<br/><br/>';
        $message .= "Si vous voulez des informations complémentaires, contactez le centre de service par téléphone au " . $tel . " (Appel non surtaxé).<br/><br/>";
        $message .= 'Cordialement, <br/><br/>';
        $message .= 'L\'équipe ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');

        return $message;
    }

    public function getPublicLink()
    {
        $id_user_client = (int) $this->getData('id_user_client');
        if (!$id_user_client) {
            $id_client = (int) $this->getData('id_client');

            if ($id_client) {
                $id_user_client = (int) $this->db->getValue('bic_user', 'id', 'id_client = ' . $id_client);
            }
        }

        if ($id_user_client) {
            $url = $this->getPublicUrl(false);

            if ($url) {
                return $url;
            }
        }
        return BimpObject::getPublicBaseUrl(false, BimpPublicController::getPublicEntityForSecteur('S')) . "a=ss&serial=" . urlencode($this->getChildObject("equipment")->getData("serial")) . "&id_sav=" . $this->id . "&user_name=" . urlencode(str_replace(" ", "", substr($this->getChildObject("client")->dol_object->name, 0, 3))) . "#suivi-sav";
//        return DOL_MAIN_URL_ROOT . "/bimpsupport/public/page.php?serial=" . $this->getChildObject("equipment")->getData("serial") . "&id_sav=" . $this->id . "&user_name=" . substr($this->getChildObject("client")->dol_object->name, 0, 3);
//        return "https://www.bimp.fr/nos-services/?serial=" . urlencode($this->getChildObject("equipment")->getData("serial")) . "&id_sav=" . $this->id . "&user_name=" . urlencode(str_replace(" ", "", substr($this->getChildObject("client")->dol_object->name, 0, 3))) . "#suivi-sav";
    }

    public function getIdContactSignataire()
    {
        if ((int) $this->getData('id_contact')) {
            return (int) $this->getData('id_contact');
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $contacts = $client->getContactsArray(false);

            if (count($contacts) == 1) {
                foreach ($contacts as $id => $label) {
                    return $id;
                }
            }
        }

        return 0;
    }

    public function getPreselectedIdContact()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client', (int) $this->getData('id_client'));

        if ($id_client) {
            if ($this->isLoaded() && (int) $this->getData('id_client') === $id_client) {
                return (int) $this->getData('id_contact');
            }

            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);

            if (BimpObject::objectLoaded($client)) {
                $contacts = $client->getContactsArray(false);

                if (count($contacts) === 1) {
                    foreach ($contacts as $id_contact => $contat_label) {
                        if ((int) $id_contact) {
                            return (int) $id_contact;
                        }
                    }
                }
            }
        }

        return 0;
    }

    public static function getCodeApple($idMax = 0, &$newIdMax = 0)
    {
        $code = '';
        $db = BimpCache::getBdb();
        $result = $db->executeS("SELECT *
FROM " . MAIN_DB_PREFIX . "bimpcore_note a
WHERE a.obj_type = 'bimp_object' AND a.obj_module = 'bimptask' AND a.obj_name = 'BIMP_Task' AND a.id_obj = '25350' ORDER by id DESC");
        if (isset($result[0])) {
            $ln = $result[0];
            $newIdMax = $ln->id;
            if ($idMax != 0 && $newIdMax > $idMax) {
                $code = $ln->content;
                $tabCode = explode('votre identifiant Apple est :', $code);
                if (isset($tabCode[1]))
                    $code = $tabCode[1];
                else {
                    $tabCode = explode('Your Apple ID Code is: ', $code);
                    if (isset($tabCode[1]))
                        $code = $tabCode[1];
                }
                $tabCode = explode('. Ne le', $code);
                if (isset($tabCode[1]))
                    $code = $tabCode[0];
                else {
                    $tabCode = explode('. Don', $code);
                    if (isset($tabCode[1]))
                        $code = $tabCode[0];
                }
                $code = str_replace(" ", "", $code);
            }
        }
        return $code;
    }

    public function getEquipmentData($data)
    {
        $equipment = $this->getChildObject('equipment');
        if (!BimpObject::objectLoaded($equipement))
            return $equipment->getData($data);

        return '';
    }

    // Affichage:

    public function displayFactureAmountToPay()
    {
        return $this->getFactureAmountToPay() . " €";
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
                $html .= BimpRender::renderButton(array(
                            'classes'     => array('btn', 'btn-default'),
                            'label'       => $button['label'],
                            'icon_before' => $button['icon'],
                            'attr'        => array(
                                'type'    => 'button',
                                'onclick' => $button['onclick']
                            )
                                ), 'button');
                $html .= '</div>';
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

    public function displayEquipment()
    {
        if ((int) $this->getData('id_equipment')) {
            $equipement = $this->getChildObject('equipment');
//            $equipement = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $this->getData('id_equipment'));

            if (!BimpObject::objectLoaded($equipement)) {
                return $this->renderChildUnfoundMsg('id_equipment', $equipement);
            }

            $return = "";

            if ((int) $equipement->getData('id_product')) {
                $return .= $equipement->displayProduct('nom') . '<br/>';
            }
            if ($equipement->getData("product_label") != "") {
                $return .= $equipement->getData("product_label") . '<br/>';
            }
            $return .= BimpObject::getInstanceNomUrlWithIcons($equipement);

            if ((string) $equipement->getData('warranty_type') && (string) $equipement->getData('warranty_type') !== '0') {
                $return .= '<br/>Type garantie: ' . $equipement->getData("warranty_type");
            }

            return $return;
        }
        return BimpRender::renderAlerts('Aucun équipement', 'warning');
    }

    public function displayExtraSav()
    {
        $equip = $this->getChildObject("equipment");

        if (BimpObject::objectLoaded($equip)) {
            $savS = BimpObject::getInstance('bimpsupport', 'BS_SAV');
            $list = $savS->getList(array('id_equipment' => $equip->id));
            foreach ($list as $arr) {
                if ($arr['id'] != $this->id) {
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $arr['id']);
                    $sav->isLoaded();
                    $return .= $sav->getNomUrl() . "<br/>";
                }
            }
        }

        $repairS = BimpObject::getInstance('bimpapple', 'GSX_Repair');
        $list = $repairS->getList(array('id_sav' => $this->id));
        foreach ($list as $arr) {
            $return .= "<a href='#gsx'>" . $arr['repair_number'] . "</a><br/>";
        }

        if ($equip->getData('old_serial') != '')
            $return .= 'Ancien(s) serial :<br/>' . $equip->getData('old_serial') . '<br/>';

        return $return;
    }

    public function defaultDisplayEquipmentsItem($id_equipment)
    {
        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
        if ($equipment->isLoaded()) {
            $label = '';
            if ((int) $equipment->getData('id_product')) {
                $product = $equipment->config->getObject('', 'product');
                if (BimpObject::objectLoaded($product)) {
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

    public function displayMoySav($ios = true)
    {
        $time = 31;
        $centres = BimpCache::getCentres();
        $html = '';
        $table = BimpCache::getDureeMoySav($time, $ios);

        $i = 0;
        $result = $result2 = array();
        foreach ($centres as $centre) {
            if (isset($table[$centre['code']])) {
                $i++;
                $tmp = array('centre' => $centre['label'], 'time' => $table[$centre['code']]);
                if ($i < 8) {
                    $result[] = $tmp;
                } else {
                    $result2[] = $tmp;
                }
            }
        }

        $sup = '';
        if (isset($_GET['code_centre']) && count(explode('-', $_GET['code_centre'])) == 1 && isset($table[$_GET['code_centre']]))
            $sup .= '<br/>(' . $centres[$_GET['code_centre']]['label'] . ' ' . $table[$_GET['code_centre']] . ' jours)';

        $html = '';
        $html .= '<div style="max-width:700px; float: left; padding:5px">' . BimpRender::renderBimpListTable($result, array('centre' => 'Centre', 'time' => 'Temps moyen en J')) . '</div>';
        if (count($result2))
            $html .= '<div style="max-width:700px; float: left; padding:5px">' . BimpRender::renderBimpListTable($result2, array('centre' => 'Centre', 'time' => 'Temps moyen en  J')) . '</div>';

        $html = BimpRender::renderPanel('Temps moyen réparation sur ' . $time . ' jours ' . ($ios ? '(iOs)' : '(hors iOs)') . $sup, $html, '', array('open' => 0));

        return $html;
    }

    public function displayMaxDiago($ios = true)
    {
        $time = 31;
        $centres = BimpCache::getCentres();
        $html = '';
        $table = BimpCache::getDureeDiago($ios);

        $i = 0;
        $result = $result2 = array();
        foreach ($centres as $centre) {
            if (isset($table[$centre['code']])) {
                $i++;
                $tmp = array('centre' => $centre['label'], 'time' => $table[$centre['code']]);
                if ($i < 8) {
                    $result[] = $tmp;
                } else {
                    $result2[] = $tmp;
                }
            }
        }
        $html = '';
        $html .= '<div style="float: left; padding:5px">' . BimpRender::renderBimpListTable($result, array('centre' => 'Centre', 'time' => 'Temps moyen en J')) . '</div>';
        if (count($result2))
            $html .= '<div style="float: left; padding:5px">' . BimpRender::renderBimpListTable($result2, array('centre' => 'Centre', 'time' => 'Temps moyen en  J')) . '</div>';


        $sup = '';
        if (isset($_GET['code_centre']) && count(explode('-', $_GET['code_centre'])) == 1 && isset($table[$_GET['code_centre']]))
            $sup .= '<br/>(' . $centres[$_GET['code_centre']]['label'] . ' ' . $table[$_GET['code_centre']] . ' jours)';

        $html = BimpRender::renderPanel('Temps max diagnostic ' . ($ios ? '(iOs)' : '(hors iOs)') . $sup, $html, '', array('open' => 0));

        return $html;
    }

    public function displayHeaderListInfo()
    {
        $html = '<div class="row">';
        $html .= '<div class="col_xs-6 col-sm-6 col-md-3">' . $this->displayMaxDiago(true) . '</div>';
        $html .= '<div class="col_xs-6 col-sm-6 col-md-3">' . $this->displayMaxDiago(false) . '</div>';
        $html .= '<div class="col_xs-6 col-sm-6 col-md-3">' . $this->displayMoySav(true) . '</div>';
        $html .= '<div class="col_xs-6 col-sm-6 col-md-3">' . $this->displayMoySav(false) . '</div>';
        $html .= '<div style="clear:both;"></div>';
        $html .= '</div>';
        return $html;
    }

    public function displayPublicLink()
    {
        return "<a target='_blank' href='" . $this->getPublicLink() . "'><i class='fas fa5-external-link-alt'></i></a>";
    }

    public function dispayRepairsNumbers()
    {
        $html = '';

        $rows = $this->db->getRows('bimp_gsx_repair', 'id_sav = ' . (int) $this->id, null, 'array', array('repair_number'));

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $html .= ($html ? '<br/>' : '') . $r['repair_number'];
            }
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->getData('date_pc'))
            $html .= '<div class="object_header_infos">Prise en charge le <strong>' . $this->displayData('date_pc') . '</strong></div><br/>';

        if ($this->getData('replaced_ref')) {
            $html .= '<div style="margin-bottom: 8px">';
            $html .= '<span class="warning" style="font-size: 15px">Annule et remplace ' . $this->getLabel('the') . ' "' . $this->getData('replaced_ref') . '" (données perdues)</span>';
            $html .= '</div>';
        }

        $html .= $this->displayData('sacs');

        $soc = $this->getChildObject("client");
        if (BimpObject::objectLoaded($soc)) {
            $html .= '<div>';
            $html .= $soc->getLink();
            $html .= '</div>';
        }

        if ((int) $this->getData('status') === self::BS_SAV_RESERVED) {
            $html .= '<div style="font-size: 15px; margin-top: 10px;">';
            $date = $this->getData('date_rdv');
            if ($date) {
                $html .= '<span class="success">';
                $html .= 'Rendez-vous le ' . date('d / m / Y à H:i', strtotime($date));
                $html .= '</span>';
            } else {
                $html .= '<span class="danger">';
                $html .= 'Pas de rendez-vous fixé';
                $html .= '</span>';
            }
            $html .= '</div>';
        }

        if (!$soc->isSolvable($this->object_name)) {
            $html .= '<div style="font-size: 15px; margin-top: 10px">';
            $html .= '<span class="danger">';
            $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Attention ce client est au statut "' . Bimp_Societe::$solvabilites[(int) $soc->getData('solvabilite_status')]['label'] . '"';
            $html .= '</span>';
            $html .= '</div>';
        }

        // Messages signature prise en charge: 
        if ((int) $this->getData('id_signature_pc') >= 0) {
            $signature_pc = $this->getChildObject('signature_pc');

            if (BimpObject::objectLoaded($signature_pc)) {
                if (!$signature_pc->isSigned()) {
                    $html .= '<div style="margin-top: 10px">';
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= '<a href="' . $signature_pc->getUrl() . '" target="_blank">Signature du bon de prise en charge en attente' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

                    $btn_html = $signature_pc->renderSignButtonsGroup();
                    if ($btn_html) {
                        $msg .= '<div style="margin-top: 8px; text-align: right">';
                        $msg .= $btn_html;
                        $msg .= '</div>';
                    }

                    $html .= BimpRender::renderAlerts($msg, 'warning');
                    $html .= '</div>';
                }
            } elseif ($this->isActionAllowed('createSignaturePC')) {
                $html .= '<div style="margin-top: 10px">';
                $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                $msg .= 'Signature du bon de prise en charge non créée.<br/>';

                if ($this->canSetAction('createSignaturePC')) {
                    $msg .= '<div style="margin-top: 8px; text-align: right">';
                    $msg .= '<span class="btn btn-default btn-small" onclick="' . $this->getJsActionOnclick('createSignaturePC', array(), array(
                                'form_name' => 'signature'
                            )) . '">';
                    $msg .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Créer';
                    $msg .= '</span>';
                    $msg .= '</div>';
                }
                $html .= BimpRender::renderAlerts($msg, 'danger');
                $html .= '</div>';
            }
        }

        // Messages signature propale: 
        $propal = $this->getChildObject('propal');
        if (BimpObject::objectLoaded($propal)) {
            $signature_propal = $propal->getChildObject('signature');

            if (BimpObject::objectLoaded($signature_propal)) {
                if (!$signature_propal->isSigned()) {
                    $html .= '<div style="margin-top: 10px">';
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= '<a href="' . $signature_propal->getUrl() . '" target="_blank">Signature du devis en attente' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

                    $btn_html = $signature_propal->renderSignButtonsGroup();
                    if ($btn_html) {
                        $msg .= '<div style="margin-top: 8px; text-align: right">';
                        $msg .= $btn_html;
                        $msg .= '</div>';
                    }

                    $html .= BimpRender::renderAlerts($msg, 'warning');
                    $html .= '</div>';
                }
            }
        }

        // Messages signature Bon restit / facture:
        if ((int) $this->getData('id_signature_resti') >= 0) {
            $signature_resti = $this->getChildObject('signature_resti');

            if (BimpObject::objectLoaded($signature_resti)) {
                if (!$signature_resti->isSigned()) {
                    $html .= '<div style="margin-top: 10px">';
                    $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $msg .= '<a href="' . $signature_resti->getUrl() . '" target="_blank">Signature du bon de restitution en attente' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';

                    $btn_html = $signature_resti->renderSignButtonsGroup();
                    if ($btn_html) {
                        $msg .= '<div style="margin-top: 8px; text-align: right">';
                        $msg .= $btn_html;
                        $msg .= '</div>';
                    }

                    $html .= BimpRender::renderAlerts($msg, 'warning');
                    $html .= '</div>';
                }
            } elseif ($this->isActionAllowed('createSignatureRestitution')) {
                $html .= '<div style="margin-top: 10px">';
                $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                $msg .= 'Signature du bon de restitution non créée.<br/>';

                if ($this->canSetAction('createSignatureRestitution')) {
                    $msg .= '<div style="margin-top: 8px; text-align: right">';
                    $msg .= '<span class="btn btn-default btn-small" onclick="' . $this->getJsActionOnclick('createSignatureRestitution', array(), array(
                                'form_name' => 'signature'
                            )) . '">';
                    $msg .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Créer';
                    $msg .= '</span>';
                    $msg .= '</div>';
                }
                $html .= BimpRender::renderAlerts($msg, 'danger');
                $html .= '</div>';
            }
        }


        $margeMini = 15;
        foreach ($propal->getLines('product') as $line) {
            $dol_line = $line->getChildObject('dol_line');
            if ($dol_line->getData('buy_price_ht') > 0 && $dol_line->getData('qty') > 0) {
                $pu = $dol_line->getData('total_ht') / $dol_line->getData('qty');

                if ((float) $pu) {
                    $marge = ($pu - $dol_line->getData('buy_price_ht')) / $pu * 100;
                    if ($marge < $margeMini) {
                        $pro = $line->getChildObject('product');
                        $html .= BimpRender::renderAlerts('Attention la ligne avec le produit ' . $pro->getLink() . ' a une marge de ' . price($marge) . ' %');
                    }
                }
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '';
        if ((int) $this->getData('status') === self::BS_SAV_FERME) {
            $file = $this->getFilesDir() . 'Restitution_' . dol_sanitizeFileName($this->getRef()) . '.pdf';

            if (file_exists($file)) {
                $url = $this->getFileUrl('Restitution_' . dol_sanitizeFileName($this->getRef()) . '.pdf');
                if ($url) {
                    $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
                    $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Bon de restitution';
                    $html .= '</span>';
                }
//            } elseif ((int) $this->getData('id_facture')) {
//                $url = DOL_URL_ROOT . '/bimpsupport/bon_restitution.php?id_sav= ' . $this->id;
//                $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
//                $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Bon de restitution';
//                $html .= '</span>';
            } else {
                $onclick = $this->getJsActionOnclick('generateRestiPdf');
                $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Générer le bon de restitution';
                $html .= '</span>';
            }


            $facture = $this->getChildObject('facture');
            if (BimpObject::objectLoaded($facture) && (int) $facture->getData('fk_statut')) {
                $html .= $facture->displayPDFButton(0, 0, 'Facture');
                $html .= $facture->displayPaiementsFacturesPdfButtons(0, 1);
            }
        }

        if (BimpCore::isModuleActive('bimpvalidation')) {
            $propal = $this->getChildObject('propal');
            if (BimpObject::objectLoaded($propal) && (int) $propal->getData('fk_statut') === 0) {
                $demandes = BimpValidation::getObjectDemandes($propal, array(
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

                    $html .= '<span class="warning">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . count($demandes) . ' demande(s) de validation du devis:</span><br/>';
                    if ($has_refused) {
                        $html .= '<span class="danger">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Il y a au moins une demand de validation refusée. ' . $this->getLabel('this') . ' ne peut pas être validée</span><br/>';
                    }
                }

                foreach ($demandes as $demande) {
                    $html .= $demande->renderQuickView();
                }
            }
        }

        return $html;
    }

    public function renderPublicHeaderExtraRight()
    {
        $html = '';

        if ($this->isLoaded() && BimpCore::isContextPublic()) {
            $propal = $this->getChildObject('propal');

            if ((int) $this->getData('id_signature_pc')) {
                $signature = $this->getChildObject('signature_pc');

                if (BimpObject::objectLoaded($signature)) {
                    $html .= $signature->displayPublicDocument('Bon de prise en charge');
                }
            }

//            if (BimpObject::objectLoaded($propal)) {
//                $signature = $propal->getChildObject('signature');
//
//                if (BimpObject::objectLoaded($signature)) {
//                    $html .= $signature->displayPublicDocument('Devis');
//                }
//            }

            if ((int) $this->getData('id_signature_resti')) {
                $signature = $this->getChildObject('signature_resti');

                if (BimpObject::objectLoaded($signature)) {
                    $html .= $signature->displayPublicDocument('Bon de restitution');
                }
            }

            $url_base = BimpCore::getConf('public_base_url', '');
            if ($url_base) {
                $url_base .= 'a=df&';
            } elseif (BimpCore::isModeDev()) {
                $url_base = DOL_URL_ROOT . '/bimpcommercial/duplicata.php?';
            }

            if ($url_base) {
                $status = (int) $this->getData('status');
                if (in_array($status, array(1, 2, 3, 4, 6, 7, 9))) {
                    if (BimpObject::objectLoaded($propal)) {
                        $ref = $propal->getRef();
                        $fileName = dol_sanitizeFileName($ref) . '.pdf';
                        $fileDir = $propal->getFilesDir();

                        if (file_exists($fileDir . $fileName)) {
                            $url = $url_base . 'r=' . urlencode($ref) . '&i=' . $propal->id . '&t=propale';
                            $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\');">';
                            $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Devis';
                            $html .= '</span>';
                        }
                    }
                }

                foreach (array(
            'facture_acompte' => 'Facture d\'acompte',
            'facture'         => 'Facture',
            'facture_avoir'   => 'Avoir'
                ) as $fac_type => $fac_label) {
                    $fac = $this->getChildObject($fac_type);

                    if (BimpObject::objectLoaded($fac)) {
                        $ref = dol_sanitizeFileName($fac->getRef());

                        if (file_exists($fac->getFilesDir() . $ref . '.pdf')) {
                            $url = $url_base . 'r=' . urlencode($ref) . '&i=' . $fac->id . '&t=facture';
                            $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\');">';
                            $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . $fac_label;
                            $html .= '</span>';
                        }
                    }
                }
            }
        }

        return $html;
    }

    public function renderSavCheckup()
    {
        $html = '';
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_facture_acompte')) {
                $sql = 'SELECT p.`rowid` FROM ' . MAIN_DB_PREFIX . 'paiement p';
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement_facture pf ON p.rowid = pf.fk_paiement';
                $sql .= ' WHERE pf.fk_facture = ' . (int) $this->getData('id_facture_acompte');
                $sql .= ' AND p.fk_paiement = 0';

                $rows = $this->db->executeS($sql, 'array');

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        $onclick = $this->getJsActionOnclick('correctAcompteModePaiement', array('id_paiement' => (int) $r['rowid']), array(
                            'form_name' => 'acompte_mode_paiement',
//                            'success_callback' => 'function() {bimp_reloadPage();}'
                        ));

                        $html .= '<div style="margin: 15px 0">';
                        $html .= BimpRender::renderAlerts('ATTENTION: aucun mode de paiement n\'a été indiqué pour le paiement de l\'acompte.');
                        $html .= '<button class="btn btn-default" onclick="' . $onclick . '"><i class="fa fa-pencil iconLeft"></i>Corriger le mode de paiement de l\'acompte</button>';
                        $html .= '</div>';
                    }
                }
            }
        }

        return $html;
    }

    public function renderPropalFilesView()
    {
        $html = '';
        if ((int) $this->isLoaded()) {
            $list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_SavFile'), 'default', 1, null, 'Fichiers joint SAV');
            $list->addFieldFilterValue('parent_module', 'bimpsupport');
            $list->addFieldFilterValue('parent_object_name', 'BS_SAV');
            $list->addFieldFilterValue('id_parent', $this->id);
            $html .= $list->renderHtml();

            if ((int) $this->getData('id_propal')) {
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'BimpFile'), 'default', 1, null, 'Fichiers joint Devis');
                $list->addFieldFilterValue('parent_module', 'bimpcommercial');
                $list->addFieldFilterValue('parent_object_name', 'Bimp_Propal');
                $list->addFieldFilterValue('id_parent', $this->getData('id_propal'));
                $html .= $list->renderHtml();
            }
        }
        return $html;
    }

    public function renderPropalView()
    {
        $html = '';
        if ((int) $this->isLoaded()) {
            if ((int) $this->getData('id_propal')) {
                $propal = $this->getChildObject('propal');
                if (BimpObject::objectLoaded($propal)) {
                    $view = new BC_View($propal, 'sav', 0, 1, 'Devis ' . $propal->getRef(), 'fas_file-invoice');
                    $html .= $view->renderHtml();
                }

                $list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine'), 'default', 1, (int) $this->getData('id_propal'), 'Lignes du devis');
                $html .= $list->renderHtml();
            } else {
                $html .= BimpRender::renderAlerts('Aucun devis enregistré pour ce SAV');
            }
        } else {
            $html .= BimpRender::renderAlerts('ID du SAV absent');
        }

        return $html;
    }

    public function renderPropalesList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $asso = new BimpAssociation($this, 'propales');
            $list = $asso->getAssociatesList();

            if (count($list)) {
                krsort($list);
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Réf.</th>';
                $html .= '<th>Statut</th>';
                $html .= '<th>Montant TTC</th>';
                $html .= '<th>Fichier</th>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                foreach ($list as $id_propal) {
                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $id_propal);
                    if ($propal->isLoaded()) {
                        $html .= '<tr>';
                        $html .= '<td>' . $propal->getRef() . '</td>';
                        $html .= '<td>' . $propal->displayData('fk_statut') . '</td>';
                        $html .= '<td>' . $propal->displayData('total_ttc') . '</td>';
                        $html .= '<td>' . $propal->displayPDFButton(false) . '</td>';
                        $html .= '</tr>';
                    }
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }

            $html = BimpRender::renderPanel('Propositions commerciales (devis)', $html, '', array(
                        'type'     => 'secondary',
                        'foldable' => true,
                        'icon'     => 'fas_file-invoice'
            ));
        }

        return $html;
    }

    public function renderPretsList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $pret = BimpObject::getInstance('bimpsupport', 'BS_Pret');

            $list = new BC_ListTable($pret, 'sav');
            $list->addFieldFilterValue('id_sav', $this->id);
            $list->addFieldFilterValue('id_entrepot', $this->getData('id_entrepot'));

            $html = $list->renderHtml();
        }

        return $html;
    }

    public function renderGsxTokenInputExtraContent()
    {
        $html = '';

        if (!class_exists('GSX_v2')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
        }

        $html .= '<div style="margin: 15px 0">';

//        $onclick = '$(this).findParentByClass(\'inputContainer\').find(\'[name=token]\').val(navigator.clipboard.readText());';
////        $onclick .= 'document.execCommand(\'paste\');';
//        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
//        $html .= BimpRender::renderIcon('fas_paste', 'iconLeft') . 'Coller token';
//        $html .= '</span>';

        if (GSX_Const::$mode === 'test') {
            $html .= '<p>';
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Mode TEST activé</span>';
            $html .= '</p>';
        }

        $onclick = 'window.open(\'' . GSX_v2::$urls['login'][GSX_v2::$mode] . '\', \'Authentification GSX\', \'menubar=no, status=no, width=800, height=600\')';
        $html .= '<script>' . $onclick . '</script>';
        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
        $html .= 'Réouvrir fenêtre d\'authentification' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
        $html .= '</span>';

        $gsx = GSX_v2::getInstance();
        $html .= '<h4>Rappel de votre identifiant GSX: </h4>';
        $html .= '<strong>AppleId</strong>: ' . $gsx->appleId . '<br/>';
        if ($gsx->appleId === GSX_v2::$default_ids['apple_id']) {
            $html .= '<strong>Mot de passe</strong>: ' . GSX_v2::$default_ids['apple_pword'];
            $html .= '<br/>Utiliser le numéro terminant par <strong>37</strong>';

            $html .= '<script>'
                    . 'var idMaxMesg = 0;'
                    . 'var boucle = true;'
                    . 'function checkCode(){'
                    . ' if(boucle){'
                    . '     setObjectAction(null, {"module":"bimpsupport", "object_name":"BS_SAV"}, "getCodeApple", {"idMax":idMaxMesg});'
                    . ' }'
                    . '}'
                    . 'checkCode();'
                    . 'bimpModal.$modal.on("hidden.bs.modal", function (e) {'
                    . ' boucle = false;'
                    . '});'
                    . '</script>';
        }

        $html .= '<p class="small" style="text-align: center; margin-top: 15px">';
        $html .= 'Si la fenêtre d\'authentification ne s\'ouvre pas, veuillez vérifier que votre navigateur ne bloque pas l\'ouverture des fenêtres pop-up';
        $html .= '</p>';

        $html .= '</div>';

        return $html;
    }

    public function renderApplePartsList($suffixe = '')
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $html = '';

        if ((int) BimpCore::getConf('use_gsx_v2', null, 'bimpapple')) {
            $issue = BimpObject::getInstance('bimpsupport', 'BS_Issue');
            $list = new BC_ListTable($issue, 'default', 1, $this->id);
            if ($suffixe) {
                $list->addIdentifierSuffix($suffixe);
            }
            $html .= $list->renderHtml();

            $nParts = (int) $this->db->getCount('bs_apple_part', '`id_sav` = ' . (int) $this->id . ' AND (`id_issue` = 0 OR `id_issue` IS NULL)');

            if ($nParts > 0) {
                if ($nParts > 1) {
                    $msg = $nParts . ' composants ont été ajoutés au panier via l\'ancienne version.<br/>Veuillez attribuer chancun de ces composants à un problème composant';
                } else {
                    $msg = '1 composant a été ajouté au panier via l\'ancienne version.<br/>Veuillez attribuer ce composant à un problème composant';
                }

                $html .= BimpRender::renderAlerts($msg, 'warning');

                $part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
                $list = new BC_ListTable($part, 'no_issue', 1, $this->id);
                $list->addFieldFilterValue('id_issue', 0);
                if ($suffixe) {
                    $list->addIdentifierSuffix($suffixe);
                }
                $html .= $list->renderHtml();
            }
        } else {
            $part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
            $list = new BC_ListTable($part, 'default', 1, $this->id);
            if ($suffixe) {
                $list->addIdentifierSuffix($suffixe);
            }
            $html .= $list->renderHtml();
        }

        return $html;
    }

    public function renderLoadPartsButton($serial = null, $suffixe = "")
    {
        if ((int) BimpCore::getConf('use_gsx_v2', null, 'bimpapple')) {
            return '';
        }

        if (!BimpObject::objectLoaded($sav)) {
            $html = BimpRender::renderAlerts('ID du SAV absent ou invalide');
        } else {
            if (is_null($serial)) {
                $equipment = $sav->getChildObject('equipment');
                if (BimpObject::objectLoaded($equipment)) {
                    $serial = $equipment->getData('serial');
                }
            }

            if (is_null($serial)) {
                $html = BimpRender::renderAlerts('Numéro de série de l\'équipement absent');
            } elseif (preg_match('/^S?[A-Z0-9]{11,12}$/', $serial) || preg_match('/^S?[0-9]{15}$/', $serial)) {
                $html = '<div id="loadPartsButtonContainer' . $suffixe . '" class="buttonsContainer">';
                $html .= BimpRender::renderButton(array(
                            'label'       => 'Charger la liste des composants compatibles',
                            'icon_before' => 'download',
                            'classes'     => array('btn btn-default'),
                            'attr'        => array(
                                'onclick' => 'loadPartsList(\'' . $serial . '\', ' . $sav->id . ', \'' . $suffixe . '\')'
                            )
                ));
                $html .= '</div>';
                $html .= '<div id="partsListContainer' . $suffixe . '" class="partsListContainer" style="display: none"></div>';
            } else {
                $html = BimpRender::renderAlerts('Le numéro de série de l\'équipement sélectionné ne correspond pas à un produit Apple: ' . $serial, 'warning');
            }
        }

        return BimpRender::renderPanel('Liste des composants Apple comptatibles', $html, '', array(
                    'type'     => 'secondary',
                    'icon'     => 'bars',
                    'foldable' => true
        ));
    }

    public function renderEquipmentPlaceOptionInput()
    {
        $html = '';

        if ($this->isLoaded()) {
            $input_name = 'put_equipment_on_prev_place';
            $id_equipment = (int) $this->getData('id_equipment');

            if ($id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);

                if (BimpObject::objectLoaded($equipment)) {
                    $cur_place = $equipment->getCurrentPlace();
                    if (BimpObject::objectLoaded($cur_place) && (int) $cur_place->getData('type') === BE_Place::BE_PLACE_SAV && (int) $cur_place->getData('id_entrepot') === (int) $this->getData('id_entrepot')) {
                        $prev_place = BimpCache::findBimpObjectInstance('bimpequipment', 'BE_Place', array(
                                    'id_equipment' => $id_equipment,
                                    'position'     => 2
                        ));

                        if (BimpObject::objectLoaded($prev_place)) {
                            $html .= '(' . $prev_place->displayPlace(true) . ')<br/><br/>';
                            $html .= BimpInput::renderInput('toggle', $input_name, 1);
                            return $html;
                        }
                    }
                }
            }
        } else {
            $input_name = 'keep_equipment_current_place';
            $id_equipment = (int) BimpTools::getPostFieldValue('id_equipment', 0);

            if ($id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                if (BimpObject::objectLoaded($equipment)) {
                    $place = $equipment->getCurrentPlace();
                    if (BimpObject::objectLoaded($place)) {
                        $html .= '(' . $place->displayPlace(true) . ')<br/><br/>';
                        $html .= BimpInput::renderInput('toggle', $input_name, 0);
                        return $html;
                    }
                }
            }
        }

        $html .= '<span class="danger">NON</span>';
        $html .= '<input type="hidden" value="0" name="' . $input_name . '"/>';

        return $html;
    }

    public function renderContactsSelect()
    {
        return BimpInput::renderInput('select', 'id_contact', (int) $this->getPreselectedIdContact(), array(
                    'options' => $this->getClient_contactsArray()
        ));
    }

    public function renderPartsConsignedStockDataForm()
    {
        $html = '';

        $errors = array();

        if ($this->isLoaded($errors)) {
            $parts = $this->getChildrenObjects('apple_parts');

            if (!count($parts)) {
                $html .= BimpRender::renderAlerts('Il n\'y a aucun composant à traiter. Vous pouvez cliquer sur "Valider" pour passer à l\'étape suivante', 'info');
            } else {

                $code_centre = (string) $this->getData('code_centre_repa');
                if (!$code_centre) {
                    $code_centre = (string) $this->getData('code_centre');
                }

                $centre = $this->getCentreData(true);

                $html .= '<h3>Stocks internes / consignés du centre: "' . $centre['label'] . '" (' . $code_centre . ')</h3>';

                $msg = 'Attention, si le centre est incorrect, veuillez fermer ce formulaire et corriger le champ "Centre de réparation" de ce SAV';

                $html .= BimpRender::renderAlerts($msg, 'warning');

                $html .= '<table class="bimp_list_table parts_consigned_stocks_data">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Ref.</th>';
                $html .= '<th>Désignation</th>';
                $html .= '<th>Stock interne / consigné</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                foreach ($parts as $part) {
                    $has_consigned_stock = false;
                    $has_internal_stock = false;
                    $part_number = $part->getData('part_number');

                    $html .= '<tr class="part_row" data-id_part="' . $part->id . '" data-part_number="' . $part_number . '">';
                    $html .= '<td>' . $part_number . '</td>';
                    $html .= '<td>' . $part->getData('label') . '</td>';

                    $html .= '<td>';

                    // Stock consigné: 
                    $html .= '<b style="font-size: 13px">Stock consigné : </b><br/>';
                    if (!$part->isConsignedStockAllowed()) {
                        $html .= '<span class="warning">Pas de stock consigné (composant tiers)</span>';
                    } else {
                        BimpObject::loadClass('bimpapple', 'ConsignedStock');
                        $consigned_stock = ConsignedStock::getStockInstance($code_centre, $part_number);

                        $input = '';

                        if (BimpObject::objectLoaded($consigned_stock)) {
                            $has_consigned_stock = true;

                            if ((int) $consigned_stock->getData('serialized')) {
                                $serials = $consigned_stock->getData('serials');

                                if (count($serials)) {
                                    $options = array(
                                        'none' => array('label' => 'NON', 'classes' => array('danger'))
                                    );

                                    foreach ($serials as $serial) {
                                        $options[$serial] = $serial;
                                    }

                                    $input = 'Qté disponible: <span class="success">' . count($serials) . '</span><br/>';
                                    $input .= '<span class="small">Numéro de série : </span><br/>';
                                    $input .= BimpInput::renderInput('select', 'consigned_stock_serial_' . $part->id, 'none', array(
                                                'extra_class' => 'from_consigned_stock_serial',
                                                'options'     => $options
                                    ));
                                }
                            } elseif ((int) $consigned_stock->getData('qty') > 0) {
                                $input = 'Qté disponible: <span class="success">' . $consigned_stock->getData('qty') . '</span><br/>';
                                $input .= 'Prendre dans le stock consigné : ';
                                $input .= BimpInput::renderInput('toggle', 'from_consigned_stock_' . $part->id, 1, array(
                                            'extra_class' => 'from_consigned_stock_check'
                                ));
                            }
                        }

                        if ($input) {
                            $html .= $input;
                        } else {
                            $html .= '<span class="warning">Aucun stock consigné disponible</span>';
                        }
                    }

                    $html .= '<br/><br/>';

                    // Stock interne: 
                    $html .= '<b style="font-size: 13px">Stock interne : </b><br/>';
                    if (!$part->isInternalStockAllowed()) {
                        $html .= '<span class="warning">Pas de stock interne</span>';
                    } else {
                        BimpObject::loadClass('bimpapple', 'InternalStock');
                        $internal_stock = InternalStock::getStockInstance($code_centre, $part_number);

                        $input = '';

                        if (BimpObject::objectLoaded($internal_stock)) {
                            if ((int) $internal_stock->getData('serialized')) {
                                $has_internal_stock = true;
                                $serials = $internal_stock->getData('serials');

                                if (count($serials)) {
                                    $options = array(
                                        '' => array('label' => 'Sélection du n° de série obligatoire', 'icon' => 'fas_exclamation-triangle', 'classes' => array('danger'))
                                    );
                                    foreach ($serials as $serial) {
                                        $options[$serial] = $serial;
                                    }

                                    $input = 'Qté disponible: <span class="success">' . count($serials) . '</span><br/>';
                                    $input .= '<span class="small">Numéro de série : </span><br/>';
                                    $input .= BimpInput::renderInput('select', 'internal_stock_serial_' . $part->id, 'none', array(
                                                'extra_class' => 'from_internal_stock_serial',
                                                'options'     => $options
                                    ));
                                }
                            } elseif ((int) $internal_stock->getData('qty') > 0) {
                                $has_internal_stock = true;
                                $input = 'Qté disponible: <span class="success">' . $internal_stock->getData('qty') . '</span><br/>';
                                $input .= BimpRender::renderAlerts('Veuillez obligatoirement prendre le composant dans le stock interne', 'info');
                                $input .= '<input type="hidden" value="1" name="from_internal_stock_' . $part->id . '" class="from_internal_stock_input"/>';
                            }
                        }

                        if ($input) {
                            $html .= $input;
                        } else {
                            $html .= '<span class="warning">Aucun stock interne disponible</span>';
                        }
                    }

                    if (!$has_internal_stock && !$has_consigned_stock) {
                        $stocks = BimpCache::getBimpObjectObjects('bimpapple', 'InternalStock', array(
                                    'part_number' => $part_number,
                                    'code_centre' => array(
                                        'operator' => '!=',
                                        'value'    => $code_centre
                                    )
                        ));

                        if (!empty($stocks)) {
                            $total_qty = 0;
                            $nb_centres = 0;

                            $centres = array();
                            foreach ($stocks as $stock) {
                                $stock_qty = 0;
                                if ((int) $stock->getData('serialized')) {
                                    $serials = $stock->getData('serials');
                                    $stock_qty = count($serials);
                                } else {
                                    $stock_qty = (int) $stock->getData('qty');
                                }

                                if ($stock_qty > 0) {
                                    $nb_centres++;
                                    $total_qty += $stock_qty;
                                    $centres[] = $stock->displayDataDefault('code_centre') . ' : ' . $stock_qty . ' unité(s) dispo';
                                }
                            }

                            if ($total_qty > 0) {
                                $msg = '<b>' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . $total_qty . ' unité(s) disponible(s) dans ' . $nb_centres . ' autre(s) centre(s)</b>';
                                $html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($centres, $msg), 'warning');
                            }
                        }
                    }

                    if ($has_consigned_stock && $has_internal_stock) {
                        $html .= '<br/><br/>';
                        $html .= '<span class="warning">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Veuillez utiliser en priorité le stock consigné</span>';
                    }

                    $html .= '</td>';

                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';

                foreach ($parts as $part) {
                    
                }
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    public static function renderMenuQuickAccess()
    {
        $html = '';

        global $conf, $user, $db;
        $mode_eco = (int) BimpCore::getConf('mode_eco');

        if (isset($conf->global->MAIN_MODULE_BIMPSUPPORT) && (userInGroupe("XX Sav", $user->id)) || userInGroupe("XX Sav MyMu", $user->id)) {
            $hrefFin = "";

            global $tabCentre;
            if ($user->array_options['options_apple_centre'] == "") {//Ajout de tous les centre
                $centreUser = array();
                foreach ($tabCentre as $idT2 => $tabCT)
                    $centreUser[] = $idT2;
            } else {
                $centreUser = explode(" ", trim($user->array_options['options_apple_centre'])); //Transforme lettre centre en id centre
                foreach ($centreUser as $idT => $CT) {//Va devenir inutille
                    foreach ($tabCentre as $idT2 => $tabCT)
                        if ($tabCT[8] == $CT)
                            $centreUser[$idT] = $idT2;
                }
            }

            $tabGroupe = array();

            $urlAcces = DOL_URL_ROOT . '/bimpsupport/?tab=sav';
            if (count($centreUser) > 1) {
                $tabGroupe = array(array('label' => "Tous", 'valeur' => 'Tous', 'forUrl' => implode($centreUser, "-")));
                $urlAcces = DOL_URL_ROOT . "/bimpsupport/?fc=index&tab=sav&code_centre=" . implode($centreUser, "-");
            }

            foreach ($tabCentre as $idGr => $tabOneCentr) {
                if (count($centreUser) == 0 || in_array($idGr, $centreUser))
                    $tabGroupe[] = array("label" => $tabOneCentr[2], "valeur" => $idGr, "forUrl" => $idGr);
            }
            $tabResult = array();

            if (!$mode_eco) {
                $result2 = $db->query("SELECT COUNT(id) as nb, code_centre as CentreVal, status as EtatVal FROM `" . MAIN_DB_PREFIX . "bs_sav` WHERE status >= -1 " . (count($centreUser) > 0 ? "AND code_centre IN ('" . implode($centreUser, "','") . "')" : "") . " GROUP BY code_centre, status");
                while ($ligne2 = $db->fetch_object($result2)) {
                    $tabResult[$ligne2->CentreVal][$ligne2->EtatVal] = $ligne2->nb;
                    if (!isset($tabResult['Tous'][$ligne2->EtatVal]))
                        $tabResult['Tous'][$ligne2->EtatVal] = 0;
                    $tabResult['Tous'][$ligne2->EtatVal] += $ligne2->nb;
                }
            }

            require_once DOL_DOCUMENT_ROOT . "/bimpsupport/objects/BS_SAV.class.php";
            $tabStatutSav = BS_SAV::$status_list;

            if (!empty($tabGroupe)) {
                $html .= '<div class="bimptheme_menu_extra_sections_title">';
                $html .= 'Accès rapides SAV';
                $html .= '</div>';

                foreach ($tabGroupe as $ligne3) {
                    $html .= '<div class="bimptheme_menu_extra_section' . ($ligne3['valeur'] != "Tous" ? ' menu_contenueCache2' : '') . '">';

                    $centre = $ligne3['valeur'];
                    $href = DOL_URL_ROOT . '/bimpsupport/?fc=index&tab=sav' . ($ligne3['valeur'] ? '&code_centre=' . $ligne3['forUrl'] : "");

                    $html .= '<div class="title">';
                    $html .= '<a href="' . $href . $hrefFin . '">' . BimpRender::renderIcon('fas_flag', 'iconLeft') . $ligne3['label'] . '</a>';
                    $html .= '</div>';

                    foreach ($tabStatutSav as $idStat => $tabStat) {
                        if ($idStat >= -1) {
                            if ($mode_eco) {
                                $nb = '';
                            } else {
                                $nb = (isset($tabResult[$centre]) && isset($tabResult[$centre][$idStat]) ? $tabResult[$centre][$idStat] : 0);
                                if ($nb == "")
                                    $nb = "0";
                            }

                            $html .= '<span href="#" class="item" style="font-size: 10px; margin-left:12px">';
                            if ($mode_eco) {
                                $nbStr = '';
                            } else {
                                $nbStr = "<span style='width: 33px; display: inline-block; text-align:right'>" . $nb . "</span> : ";
                            }
                            $html .= "<a href='" . $href . "&status=" . urlencode($idStat) . $hrefFin . "'>" . $nbStr . $tabStat['label'] . "</a>";
                            $html .= "</span><br/>";
                        }
                    }
                    $html .= '</div>';
                }

                if (count($tabGroupe) > 2) {
                    $html .= "<div style='width:100%;text-align:center;'><span id='showDetailChrono2'>(...)</span></div>";

                    $html .= "<script type='text/javascript'>$(document).ready(function(){"
                            . "$('.menu_contenueCache2').hide();"
                            . "$('#showDetailChrono2').click(function(){"
                            . "$('.menu_contenueCache2').show();"
                            . "$(this).hide();"
                            . "});"
                            . "});</script>";
                }
            }
        }

        return $html;
    }

    // Traitements:

    public function checkObject($context = '', $field = '')
    {
        if ($context === 'fetch') {
            if ($this->isLoaded()) {
                $this->resetMsgs();

                // Vérif de l'existance de la propale: 
                if ((int) $this->getData("sav_pro") < 1 && (int) $this->getData('status') >= 0) {
                    $propal = $this->getChildObject('propal');
                    if (!BimpObject::objectLoaded($propal)) {
                        if ($this->getData("id_propal") < 1) {
                            $prop_errors = $this->createPropal();
                            if (count($prop_errors)) {
                                $msg = BimpTools::getMsgFromArray($prop_errors, 'Devis absent du SAV "' . $this->getRef() . '". Echec de la tentative de création');
                                $this->msgs['errors'][] = $msg;
                                dol_syslog($msg, LOG_ERR);
                            } else {
                                dol_syslog('Devis absent du SAV "' . $this->getRef() . '". Création effectuée avec succès', LOG_NOTICE);
                                $propal = $this->getChildObject('propal');
                            }
                        }
                    }

                    // Vérif de la propale: 
                    if (BimpObject::objectLoaded($propal)) {
                        $infos = '';
                        $update = false;
                        if (!(int) $propal->dol_object->array_options['options_entrepot']) {
                            if (!(int) $this->getData('id_entrepot')) {
                                $this->msgs['errors'][] = 'Aucun entrepôt défini pour ce SAV';
                                dol_syslog('Aucun entrepôt défini pour le SAV "' . $this->getRef() . '"', LOG_ERR);
                            } else {
                                $infos .= 'Correction entrepot<br/>';
                                $propal->set('entrepot', (int) $this->getData('id_entrepot'));
                                $update = true;
                            }
                        }

                        if ((string) $propal->getData('libelle') !== $this->getRef()) {
                            $infos .= 'Correction libelle<br/>';
                            $propal->set('libelle', $this->getRef());
                            $update = true;
                        }

                        if ((string) $propal->getData('ef_type') !== 'S') {
                            $infos .= 'Correction secteur<br/>';
                            $propal->set('ef_type', 'S');
                            $update = true;
                        }

                        if ($update) {
                            $warnings = array();
                            $prop_errors = $propal->update($warnings, true);
                            if (count($prop_errors)) {
//                                dol_syslog(BimpTools::getMsgFromArray($prop_errors, 'Echec de la réparation automatique de la propale pour le SAV "' . $this->getRef() . '"'), LOG_ERR);
                                BimpCore::addlog('Echec de la réparation automatique de la propale pour le SAV "' . $this->getRef() . '"<br/>' . $infos, Bimp_Log::BIMP_LOG_ERREUR, 'sav', $this, array(
                                    'Erreurs' => $prop_errors
                                ));
                            } else {
//                                dol_syslog('Correction automatique de la propale pour le SAV "' . $this->getRef() . '" effectuée avec succès', LOG_NOTICE);
                                BimpCore::addlog('Correction automatique de la propale pour le SAV "' . $this->getRef() . '" effectuée avec succès<br/>' . $infos, Bimp_Log::BIMP_LOG_NOTIF, 'sav', $this);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function onNewStatus(&$new_status, $current_status, $extra_data, &$warnings = array())
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

        $error_msg = 'Ce SAV ne peut pas être mis au statut "' . self::$status_list[$new_status]['label'] . '"';

        $client = $this->getChildObject('client');
        if (is_null($client) || !$client->isLoaded()) {
            return array($error_msg . ' (Client absent ou invalide)');
        }

        if (is_null($propal) && in_array($new_status, self::$need_propal_status) && $this->getData("sav_pro") < 1) {
            return array($error_msg . ' (Proposition commerciale absente)');
        }

        global $user, $langs;

        $msg_type = '';

        switch ($new_status) {
            case self::BS_SAV_EXAM_EN_COURS:
                if (!in_array($current_status, self::$propal_reviewable_status)) {
                    $errors[] = $error_msg . ' (statut actuel invalide : ' . $current_status . ')';
                }
                break;

            case self::BS_SAV_ATT_CLIENT:
                if (is_null($propal)) {
                    $errors[] = $error_msg . ' (Proposition commerciale absente)';
                } elseif ($propal_status !== 1) {
                    $errors[] = $error_msg . ' (statut de la proposition commerciale invalide ' . $propal_status . ')';
                } elseif (!(string) $this->getData('diagnostic')) {
                    $errors[] = $error_msg . '. Le champ "Diagnostic" doit être complété';
                } elseif (in_array($current_status, array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_FERME))) {
                    $errors[] = $errors[] = $error_msg . ' (statut actuel invalide)';
                }
                break;

            case self::BS_SAV_ATT_PIECE:
                if (in_array($current_status, array(self::BS_SAV_FERME))) {
                    $errors[] = $errors[] = $error_msg . ' (statut actuel invalide)';
                }
                break;

            case self::BS_SAV_DEVIS_ACCEPTE:
                if ($propal_status > 2) {
                    $errors[] = $error_msg . ' (statut de la proposition commerciale invalide)';
                } elseif (!in_array($current_status, array(0, 1, 2, 4, 5))) {
                    $errors[] = $error_msg . ' (statut actuel invalide)';
                }
                break;

            case self::BS_SAV_DEVIS_REFUSE:
                if ($propal_status !== 1) {
                    $errors[] = $error_msg . ' (statut de la proposition commerciale invalide)';
                } elseif (!in_array($current_status, array(0, 1, 2, 5))) {
                    $errors[] = $error_msg . ' (statut actuel invalide)';
                }
                break;

            case self::BS_SAV_REP_EN_COURS:
                if (!in_array($current_status, array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_ATT_PIECE, self::BS_SAV_EXAM_EN_COURS))) {
                    $errors[] = $error_msg . ' (Statut actuel invalide)';
                } else {
                    if ($current_status === self::BS_SAV_ATT_PIECE) {
                        $this->addNote('Pièce reçue le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
                        $msg_type = 'pieceOk';
                    }
                }
                break;
        }

        if (!count($errors)) {
            if ($msg_type && $extra_data['send_msg']) {
                $warnings = BimpTools::merge_array($warnings, $this->sendMsg($msg_type));
            }
        }

        return $errors;
    }

    public function createAccompte($acompte, $update = true, $id_mode_paiement = null, $id_account = null)
    {
        global $user, $langs;

        $errors = array();

        $caisse = null;
        $id_caisse = 0;

        if ($this->useCaisseForPayments) {
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
            if (!$id_caisse) {
                $errors[] = 'Utilisateur non connecté à aucune caisse. Enregistrement de l\'acompte abandonné';
            } else {
                $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                if (!$caisse->isLoaded()) {
                    $errors[] = 'La caisse à laquelle vous êtes connecté est invalide. Enregistrement de l\'acompte abandonné';
                } else {
                    $caisse->isValid($errors);
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }


        $id_client = (int) $this->getData('id_client');
        if (!$id_client) {
            $errors[] = 'Aucun client sélectionné pour ce SAV';
        }
        if ($acompte > 0 && !count($errors)) {
            if (!(int) $id_mode_paiement) {
                $id_mode_paiement = (int) BimpTools::getValue('mode_paiement_acompte', null);
            }

            if (!(int) $id_mode_paiement) {
                $id_mode_paiement = (int) BimpCore::getConf('sav_mode_reglement', null, 'bimpsupport');
            }

            // Création de la facture: 
            BimpTools::loadDolClass('compta/facture', 'facture');
            $factureA = new Facture($this->db->db);
            $factureA->type = 3;
            $factureA->date = dol_now();
            $factureA->socid = $this->getData('id_client');
            $factureA->cond_reglement_id = (int) BimpCore::getConf('sav_cond_reglement', null, 'bimpsupport');
            $factureA->modelpdf = self::$facture_model_pdf;
            $factureA->array_options['options_type'] = "S";
            $factureA->array_options['options_entrepot'] = $this->getData('id_entrepot');
            $factureA->array_options['options_centre'] = $this->getData('code_centre');
            $factureA->array_options['options_expertise'] = 90;

            $user->rights->facture->creer = 1;
            if ($factureA->create($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($factureA), 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
            } else {
                $factureA->addline("Acompte", $acompte / 1.2, 1, 20, null, null, null, 0, null, null, null, null, null, 'HT', null, 1, null, null, null, null, null, null, $acompte / 1.2);
                if ($factureA->validate($user) > 0) {
                    // Création du paiement: 
                    BimpTools::loadDolClass('compta/paiement', 'paiement');
                    $payement = new Paiement($this->db->db);
                    $payement->amounts = array($factureA->id => $acompte);
                    $payement->datepaye = dol_now();
                    $payement->paiementid = (int) $id_mode_paiement;
                    if ($payement->create($user) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Des erreurs sont survenues lors de la création du paiement de la facture d\'acompte');
                    } else {
                        if (!(int) $id_account) {
                            if ($this->useCaisseForPayments) {
                                $id_account = (int) $caisse->getData('id_account');
                            } else {
                                $id_account = (int) BimpCore::getConf('id_default_bank_account');
                            }
                        }

                        // Ajout du paiement au compte bancaire: 
                        if ($payement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                            $account_label = '';

                            if ($this->useCaisseForPayments) {
                                $account = $caisse->getChildObject('account');

                                if (BimpObject::objectLoaded($account)) {
                                    $account_label = '"' . $account->bank . '"';
                                }
                            }

                            if (!$account_label) {
                                $account_label = ' d\'ID ' . $id_account;
                            }
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Echec de l\'ajout de l\'acompte au compte bancaire ' . $account_label);
                        }

                        // Enregistrement du paiement caisse: 
                        if ($this->useCaisseForPayments) {
                            $errors = BimpTools::merge_array($errors, $caisse->addPaiement($payement, $factureA->id));
                        }

                        $factureA->set_paid($user);
                    }

                    // Création de la remise client: 
                    BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                    $discount = new DiscountAbsolute($this->db->db);
                    $discount->description = "Acompte";
                    $discount->fk_soc = $factureA->socid;
                    $discount->fk_facture_source = $factureA->id;
                    $discount->amount_ht = $acompte / 1.2;
                    $discount->amount_ttc = $acompte;
                    $discount->amount_tva = $acompte - ($acompte / 1.2);
                    $discount->tva_tx = 20;
                    if ($discount->create($user) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Des erreurs sont survenues lors de la création de la remise sur acompte');
                    } else {
                        $this->set('id_discount', $discount->id);
                    }

                    $this->set('id_facture_acompte', $factureA->id);

                    $w = array();
                    $this->update($w, true);

                    include_once(DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php');
                    if ($factureA->generateDocument(self::$facture_model_pdf, $langs) <= 0) {
                        $fac_errors = BimpTools::getErrorsFromDolObject($factureA, $error = null, $langs);
                        $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la création du fichier PDF de la facture d\'acompte');
                    }
                } else {
                    $fac_errors = BimpTools::getErrorsFromDolObject($factureA, $error = null, $langs);
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la validation de la facture');
                }
            }
        }

        return $errors;
    }

    public function createPropal($update = true)
    {
        if (!$this->isLoaded()) {
            return array(
                'ID du SAV absent ou invalide'
            );
        }
        $errors = array();

        $client = $this->getChildObject('client');
        $id_contact = (int) $this->getData('id_contact');

        if (!BimpObject::objectLoaded($client)) {
            if (!(int) $this->getData('id_client')) {
                $errors[] = 'Aucun client sélectionné pour ce SAV';
            } else {
                $errors[] = 'Le client #' . $this->getData('id_client') . ' n\'existe pas';
            }
        }

        if (!count($errors)) {
            global $user, $langs;

            $id_cond_reglement = $client->getData('cond_reglement');
            if (!$id_cond_reglement)
                $id_cond_reglement = (int) BimpCore::getConf('sav_cond_reglement', $client->getData('cond_reglement'), 'bimpsupport');
            $id_mode_reglement = $client->getData('mode_reglement');
            if (!$id_mode_reglement)
                $id_mode_reglement = (int) BimpCore::getConf('sav_mode_reglement', $client->getData('mode_reglement'), 'bimpsupport');

            if ($id_cond_reglement == 20 && $id_mode_reglement != 2) {
                $id_cond_reglement = 1;
            }

            BimpTools::loadDolClass('comm/propal', 'propal');
            $prop = new Propal($this->db->db);
            $prop->modelpdf = self::$propal_model_pdf;
            $prop->socid = $client->id;
            $prop->date = dol_now();
            $prop->cond_reglement_id = $id_cond_reglement;
            $prop->mode_reglement_id = $id_mode_reglement;
            $prop->fk_account = (int) BimpCore::getConf('id_default_bank_account');
            $prop->model_pdf = 'bimpdevissav';

            if ($prop->create($user) <= 0) {
                $errors[] = 'Echec de la création de la propale';
                BimpTools::getErrorsFromDolObject($prop, $errors, $langs);
            } else {
                $prop->set_ref_client($user, $this->getData('prestataire_number'));
                $prop->array_options['options_type'] = "S";
                $prop->array_options['options_entrepot'] = (int) $this->getData("id_entrepot");
                $prop->array_options['options_libelle'] = $this->getRef();
                $prop->insertExtraFields();
                if ($id_contact) {
                    $prop->add_contact($id_contact, 40);
                    $prop->add_contact($id_contact, 41);
                }

                $this->updateField('id_propal', (int) $prop->id, null, true);
                $asso = new BimpAssociation($this, 'propales');
                $asso->addObjectAssociation((int) $prop->id);

                if ($this->getData("id_facture_acompte"))
                    addElementElement("propal", "facture", $prop->id, $this->getData("id_facture_acompte"));


                // Création des lignes propal:
                if ((int) $this->getData('id_propal')) {
                    $prop_errors = $this->generatePropalLines();
                    if (count($prop_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($prop_errors, 'Des erreurs sont survenues lors de la création des lignes du devis');
                    }
                }
            }
        }

        return $errors;
    }

    public function reviewPropal_old(&$warnings = array())
    {
        $errors = array();

        $propal = $this->getChildObject('propal');
        $client = $this->getChildObject('client');

        if (!in_array((int) $this->getData('status'), self::$propal_reviewable_status)) {
            $errors[] = 'Le devis ne peux pas être révisé selon le statut actuel du SAV';
        } elseif (!(int) $this->getData('id_propal')) {
            $errors[] = 'Proposition commerciale absente';
        } elseif (is_null($client) || !$client->isLoaded()) {
            $errors[] = 'Client absent';
        } else {
            if ($propal->dol_object->statut > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/bimpcore/classes/BimpRevision.php");

                $old_id_propal = $propal->id;

                $revision = new BimpRevisionPropal($propal->dol_object);
                $new_id_propal = $revision->reviserPropal(false, true, self::$propal_model_pdf, $errors, $this->getData("id_client"));

                $new_propal = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SavPropal', (int) $new_id_propal);
                if (!BimpObject::objectLoaded($new_propal)) {
                    $errors[] = 'Le nouveau devis d\'ID ' . $new_id_propal . ' n\'existe pas';
                }

                if ($new_id_propal && !count($errors)) {
                    $signature = $propal->getChildObject('signature');

                    if (BimpObject::objectLoaded($signature)) {
                        $signature->cancelAllSignatures();
                    }

                    //Anulation du montant de la propal
                    $totHt = (float) $propal->dol_object->total_ht;
                    if ($totHt == 0)
                        $tTva = 0;
                    else {
                        $tTva = (($propal->dol_object->total_ttc / ($totHt != 0 ? $totHt : 1) - 1) * 100);
                    }

                    $propal->fetch($old_id_propal);
                    $propal->dol_object->statut = 0;
                    $propal->dol_object->addline("Devis révisé", -($totHt) / (100 - $client->dol_object->remise_percent) * 100, 1, $tTva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 1, -1, 0, 0, 0, 0); //-$totPa);

                    $errors = BimpTools::merge_array($errors, $this->setNewStatus(self::BS_SAV_EXAM_EN_COURS));
                    global $user, $langs;
                    $this->addNote('Devis mis en révision le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    $warnings = BimpTools::merge_array($warnings, $this->removeReservations());

                    $this->updateField('id_propal', (int) $new_id_propal, null, true);

                    $asso = new BimpAssociation($this, 'propales');
                    $asso->addObjectAssociation((int) $new_id_propal);

                    // Copie des lignes: 
                    // Maintenant géré dans propal.class.php (maj Dol16) 
                    $warnings = BimpTools::merge_array($warnings, $new_propal->createLinesFromOrigin($propal, array(
                                        'is_review' => true
                    )));

                    // Check des AppleParts: 
                    $new_apple_parts_lines = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_SavPropalLine', array(
                                'id_obj'             => (int) $new_id_propal,
                                'linked_object_name' => 'sav_apple_part'
                    ));

                    if (!empty($new_apple_parts_lines)) {
                        foreach ($new_apple_parts_lines as $line) {
                            $apple_part = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_ApplePart', (int) $line->getData('linked_id_object'));
                            if (!BimpObject::objectLoaded($apple_part)) {
                                $line->set('deletable', 1);
                                $line->set('editable', 1);
                                $line->set('remisable', 1);
                                $line->set('linked_id_object', 0);
                                $line->set('linked_object_name', '');

                                $w = array();
                                $line->update($w, true);
                            }
                        }
                    }

                    // Copie des contacts: 
                    $new_propal->copyContactsFromOrigin($propal, $warnings);

                    // Copie des remises globales: 
                    $new_propal->copyRemisesGlobalesFromOrigin($propal, $warnings);

                    // Traitement de la garantie: 
                    $this->processPropalGarantie();
                } else {
                    $errors[] = 'Echec de la mise en révision du devis';
                }
            } else {
                $errors[] = 'Le devis n\'a pas besoin d\'être révisé car il est toujours au statut "Brouillon"';
            }
        }

        return $errors;
    }

    public function reviewPropal(&$warnings = array())
    {
        $errors = array();

        $old_propal = $this->getChildObject('propal');
        $client = $this->getChildObject('client');

        if (!in_array((int) $this->getData('status'), self::$propal_reviewable_status)) {
            $errors[] = 'Le devis ne peux pas être révisé selon le statut actuel du SAV';
        } elseif (!(int) $this->getData('id_propal')) {
            $errors[] = 'Proposition commerciale absente';
        } elseif (is_null($client) || !$client->isLoaded()) {
            $errors[] = 'Client absent';
        } else {
            if ($old_propal->dol_object->statut > 0) {

                // Création de la révision: 
                $new_id_propal = $old_propal->review(false, $errors, $warnings);

                $new_propal = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SavPropal', (int) $new_id_propal);
                if (!BimpObject::objectLoaded($new_propal)) {
                    $errors[] = 'Le nouveau devis d\'ID ' . $new_id_propal . ' n\'existe pas';
                }

                if (!count($errors) && BimpObject::objectLoaded($new_propal)) {
                    $errors = BimpTools::merge_array($errors, $this->setNewStatus(self::BS_SAV_EXAM_EN_COURS));
                    global $user, $langs;
                    $this->addNote('Devis mis en révision le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    $warnings = BimpTools::merge_array($warnings, $this->removeReservations());

                    $this->updateField('id_propal', (int) $new_id_propal, null, true);

                    $asso = new BimpAssociation($this, 'propales');
                    $asso->addObjectAssociation((int) $new_id_propal);

                    // Check des AppleParts: 
                    $new_apple_parts_lines = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_SavPropalLine', array(
                                'id_obj'             => (int) $new_id_propal,
                                'linked_object_name' => 'sav_apple_part'
                    ));

                    if (!empty($new_apple_parts_lines)) {
                        foreach ($new_apple_parts_lines as $line) {
                            $apple_part = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_ApplePart', (int) $line->getData('linked_id_object'));
                            if (!BimpObject::objectLoaded($apple_part)) {
                                $line->set('deletable', 1);
                                $line->set('editable', 1);
                                $line->set('remisable', 1);
                                $line->set('linked_id_object', 0);
                                $line->set('linked_object_name', '');

                                $w = array();
                                $line->update($w, true);
                            }
                        }
                    }
                    // Traitement de la garantie: 
                    $this->processPropalGarantie();
                } else {
                    $errors[] = 'Echec de la mise en révision du devis';
                }
            } else {
                $errors[] = 'Le devis n\'a pas besoin d\'être révisé car il est toujours au statut "Brouillon"';
            }
        }

        return $errors;
    }

    public function generatePropalLines(&$warnings = array())
    {
        if (!$this->isLoaded()) {
            return array('ID du SAV absent');
        }

        if (!$this->isPropalEditable()) {
            return array('Le devis ne peut pas être modifié. Veuillez mettre le devis en révision');
        }

        global $langs, $user;

        $errors = array();

        if ((int) $this->getData('id_propal') < 1) {
            $errors = $this->createPropal();
            if (count($errors)) {
                return $errors;
            }
        }

        $client = $this->getChildObject('client');
        if (!is_null($client) && !$client->isLoaded()) {
            $client = null;
        }

        if (is_null($client)) {
            return array('Client absent');
        }

        BimpTools::loadDolClass('comm/propal', 'propal');

//        $prop = new Propal($this->db->db);
//        $prop->fetch($this->getData('id_propal'));
        $prop = $this->getChildObject('propal')->dol_object;

        $prop->set_ref_client($user, $this->getData('prestataire_number'));

        // Acompte: 
        if ($this->getData('id_discount') > 0) {
            BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
            $discount = new DiscountAbsolute($this->db->db);
            $discount->fetch($this->getData('id_discount'));

            $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
            $line->no_equipment_post = true;

            $line->find(array(
                'id_obj'             => $prop->id,
                'linked_object_name' => 'sav_discount',
                'linked_id_object'   => (int) $discount->id
                    ), false, true);

            $line_errors = $line->validateArray(array(
                'id_obj'             => (int) $prop->id,
                'type'               => BS_SavPropalLine::LINE_FREE,
                'deletable'          => 0,
                'editable'           => 0,
                'remisable'          => 0,
                'linked_id_object'   => (int) $discount->id,
                'linked_object_name' => 'sav_discount'
            ));

            if (!count($line_errors)) {
                // (infobits = 1 ??) 
                $line->desc = 'Acompte';
                $line->id_product = 0;
                $line->pu_ht = -$discount->amount_ht;
                $line->pa_ht = -$discount->amount_ht;
                $line->qty = 1;
                $line->tva_tx = 20;
                $line->id_remise_except = (int) $discount->id;
                $line->remise = 0;

                $line_warnings = array();
                $error_label = '';
                if (!$line->isLoaded()) {
                    $error_label = 'création';
                    $line_errors = $line->create($line_warnings, true);
                } else {
                    $error_label = 'mise à jour';
                    $line_errors = $line->update($line_warnings, true);
                }

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la ' . $error_label . ' de la ligne d\'acompte');
                }

                if (count($line_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($line_warnings);
                }
            }
        }

        // Prise en charge: 
//        $line->find(array(
//            'id_obj'             => (int) $prop->id,
//            'linked_object_name' => 'sav_pc',
//            'linked_id_object'   => (int) $this->id
//        ));
//
//        $line->validateArray(array(
//            'id_obj'             => (int) $prop->id,
//            'type'               => BS_SavPropalLine::LINE_TEXT,
//            'deletable'          => 0,
//            'editable'           => 0,
//            'linked_id_object'   => (int) $this->id,
//            'linked_object_name' => 'sav_pc'
//        ));
//
//        $ref = $this->getData('ref');
//        $equipment = $this->getChildObject('equipment');
//        $serial = 'N/C';
//        if (!is_null($equipment) && $equipment->isLoaded()) {
//            $serial = $equipment->getData('serial');
//        }
//
//        $line->desc = 'Prise en charge : ' . $ref . '<br/>';
//        $line->desc .= 'S/N : ' . $serial . '<br/>';
//        $line->desc .= 'Garantie : pour du matériel couvert par Apple, la garantie initiale s\'applique. Pour du matériel non couvert par Apple, la garantie est de 3 mois pour les pièces et la main d\'oeuvre.';
//        $line->desc .= 'Les pannes logicielles ne sont pas couvertes par la garantie du fabricant. Une garantie de 30 jours est appliquée pour les réparations logicielles.';
//
//        $line_warnings = array();
//        $error_label = '';
//        if (!$line->isLoaded()) {
//            $error_label = 'création';
//            $line_errors = $line->create($line_warnings, true);
//        } else {
//            $error_label = 'mise à jour';
//            $line_errors = $line->update($line_warnings, true);
//        }
//        $line_errors = BimpTools::merge_array($line_errors, $line_warnings);
//        if (count($line_errors)) {
//            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la ' . $error_label . ' de la ligne de prise en charge');
//        }
        // Service prioritaire: 
        if ((int) $this->getData('prioritaire')) {
            require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
            $prodF = new ProductFournisseur($this->db->db);
            $prodF->fetch(self::$idProdPrio);
            $prodF->tva_tx = ($prodF->tva_tx > 0) ? $prodF->tva_tx : 0;
            $prodF->find_min_price_product_fournisseur($prodF->id, 1);

            $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
            $line->no_equipment_post = true;

            $line->find(array(
                'id_obj'             => $prop->id,
                'linked_object_name' => 'sav_prioritaire',
                'linked_id_object'   => (int) $this->id
                    ), false, true);

            $line->validateArray(array(
                'id_obj'             => (int) $prop->id,
                'type'               => BS_SavPropalLine::LINE_PRODUCT,
                'deletable'          => 0,
                'editable'           => 0,
                'remisable'          => 0,
                'linked_id_object'   => (int) $this->id,
                'linked_object_name' => 'sav_prioritaire',
                'out_of_warranty'    => 1
            ));

            $line->desc = '';
            $line->id_product = (int) self::$idProdPrio;
            $line->pu_ht = $prodF->price;
            $line->pa_ht = (float) $prodF->fourn_price;
            $line->id_fourn_price = (int) $prodF->product_fourn_price_id;
            $line->qty = 1;
            $line->tva_tx = $prodF->tva_tx;
            $line->remise = 0;

            $line_warnings = array();
            $error_label = '';
            if (!$line->isLoaded()) {
                $error_label = 'création';
                $line_errors = $line->create($line_warnings, true);
            } else {
                $error_label = 'mise à jour';
                $line_errors = $line->update($line_warnings, true);
            }

            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la ' . $error_label . ' de la ligne "SAV prioritaire"');
            }
            if (count($line_warnings)) {
                $warnings[] = BimpTools::getMsgFromArray($line_warnings);
            }
        }

        // Garantie: 
        $error = $this->processPropalGarantie();
        if ($error) {
            $errors[] = $error;
        }

        // Diagnostic: 
        $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
        $line->no_equipment_post = true;

        $line->find(array(
            'id_obj'             => $prop->id,
            'linked_object_name' => 'sav_diagnostic',
            'linked_id_object'   => (int) $this->id
                ), false, true);

        $line_errors = array();
        $line_warnings = array();

        if ((string) $this->getData('diagnostic')) {
            $line->validateArray(array(
                'id_obj'             => (int) $prop->id,
                'type'               => BS_SavPropalLine::LINE_TEXT,
                'deletable'          => 0,
                'editable'           => 0,
                'remisable'          => 0,
                'linked_id_object'   => (int) $this->id,
                'linked_object_name' => 'sav_diagnostic'
            ));

            $line->desc = 'Diagnostic : ' . $this->getData('diagnostic');

            $error_label = '';
            if (!$line->isLoaded()) {
                $error_label = 'création';
                $line_errors = $line->create($line_warnings, true);
            } else {
                $error_label = 'mise à jour';
                $line_errors = $line->update($line_warnings, true);
            }
        } else {
            if ($line->isLoaded()) {
                $error_label = 'suppression';
                $line_errors = $line->delete($line_warnings, true);
            }
        }

        if (count($line_errors)) {
            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la ' . $error_label . ' de la ligne "Diagnostic"');
        }
        if (count($line_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($line_warnings);
        }

        // Infos Suppl: 
        $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
        $line->no_equipment_post = true;

        $line->find(array(
            'id_obj'             => $prop->id,
            'linked_object_name' => 'sav_extra_infos',
            'linked_id_object'   => (int) $this->id
                ), false, true);

        $line_errors = array();
        if ((string) $this->getData('extra_infos')) {
            $line->validateArray(array(
                'id_obj'             => (int) $prop->id,
                'type'               => BS_SavPropalLine::LINE_TEXT,
                'deletable'          => 0,
                'editable'           => 0,
                'remisable'          => 0,
                'linked_id_object'   => (int) $this->id,
                'linked_object_name' => 'sav_extra_infos'
            ));

            $line->desc = $this->getData('extra_infos');

            $line_warnings = array();
            $error_label = '';
            if (!$line->isLoaded()) {
                $error_label = 'création';
                $line_errors = $line->create($line_warnings, true);
            } else {
                $error_label = 'mise à jour';
                $line_errors = $line->update($line_warnings, true);
            }
        } else {
            if ($line->isLoaded()) {
                $error_label = 'suppression';
                $line_errors = $line->delete($line_warnings, true);
            }
        }

        if (count($line_errors)) {
            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la ' . $error_label . ' de la ligne "Informations supplémentaires"');
        }
        if (count($line_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($line_warnings);
        }

        return $errors;
    }

    public function processPropalGarantie(Propal $propal = null)
    {
        if (!$this->isLoaded()) {
            return 'ID du SAV absent';
        }

        $line_warnings = array();

        if (!$this->isPropalEditable()) {
            return '';
        }

        $this->allGarantie = true;

        $bProp = $this->getChildObject('propal');
        if (is_null($propal)) {
            if (BimpObject::objectLoaded($bProp)) {
                $propal = $bProp->dol_object;
            }
        }

        if (!BimpObject::objectLoaded($propal)) {
            return 'Devis absent ou invalide';
        }

        $garantieHt = $garantieTtc = $garantiePa = 0;
        $garantieHtService = $garantieTtcService = $garantiePaService = 0;

        BimpObject::loadClass($this->module, 'BS_SavPropalLine');

        foreach ($this->getPropalLines(array(
            'type' => array("in" => array(BS_SavPropalLine::LINE_PRODUCT, BS_SavPropalLine::LINE_FREE)),
        )) as $line) {
            if ((int) $line->pu_ht > 0) {
                if (!(int) $line->getData('out_of_warranty')) {
                    $line->fetch($line->id);
                    $remise = (float) $line->remise;
                    $coefRemise = (100 - $remise) / 100;
                    $prod_type = $line->getData('product_type');
                    $prod = $line->getChildObject('product');
                    if ($prod->isLoaded())
                        $prod_type = $prod->getData('fk_product_type');
                    if ($prod_type != 1) {
                        $garantieHt += ((float) $line->pu_ht * (float) $line->qty * (float) $coefRemise);
                        $garantieTtc += ((float) $line->pu_ht * (float) $line->qty * ((float) $line->tva_tx / 100) * $coefRemise);
                        $garantiePa += (float) $line->pa_ht * (float) $line->qty;
                    } else {
                        $garantieHtService += ((float) $line->pu_ht * (float) $line->qty * (float) $coefRemise);
                        $garantieTtcService += ((float) $line->pu_ht * (float) $line->qty * ((float) $line->tva_tx / 100) * $coefRemise);
                        $garantiePaService += (float) $line->pa_ht * (float) $line->qty;
                    }
                } else {
                    $this->allGarantie = false;
                }
            }
        }

//        foreach ($this->getPropalLines(array(
//            'linked_object_name' => 'sav_apple_part'
//        )) as $line) {
//            if (!(int) $line->getData('out_of_warranty')) {
//                $line->fetch($line->id);
//                $remise = (float) $line->remise;
//                $coefRemise = (100 - $remise) / 100;
//                $garantieHt += ((float) $line->pu_ht * (float) $line->qty * (float) $coefRemise);
//                $garantieTtc += ((float) $line->pu_ht * (float) $line->qty * ((float) $line->tva_tx / 100) * $coefRemise);
//                $garantiePa += (float) $line->pa_ht * (float) $line->qty;
//            } else {
//                $this->allGarantie = false;
//            }
//        }


        $line = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SavPropalLine', array(
                    'id_obj'             => (int) $propal->id,
                    'linked_id_object'   => (int) $this->id,
                    'linked_object_name' => 'sav_garantie'
                        ), true, true, true);

        if (!BimpObject::objectLoaded($line)) {
            $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
        }

        $line_errors = array();

        if ((float) $garantieHt > 0) {
            $line->validateArray(array(
                'id_obj'             => (int) $propal->id,
                'type'               => BS_SavPropalLine::LINE_FREE,
                'deletable'          => 0,
                'editable'           => 0,
                'linked_id_object'   => (int) $this->id,
                'linked_object_name' => 'sav_garantie',
                'remisable'          => 0
            ));

            $line->desc = 'Garantie';
            $line->id_product = 0;
            $line->pu_ht = -$garantieHt;
            $line->pa_ht = -$garantiePa;
            $line->id_fourn_price = 0;
            $line->qty = 1;
            if ((float) $garantieHt) {
                $line->tva_tx = 100 * ($garantieTtc / $garantieHt);
            } else {
                $line->tva_tx = 0;
            }
            $line->remise = 0;

            $error_label = '';
            if (!$line->isLoaded()) {
                $error_label = 'création';
                $line_errors = $line->create($line_warnings, true);
            } else {
                $error_label = 'mise à jour';
                $line_errors = $line->update($line_warnings, true);
            }
        } else {
            if ($line->isLoaded()) {
                $error_label = 'suppression';
                $line_errors = $line->delete($line_warnings, true);
            }
        }

        $line = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SavPropalLine', array(
                    'id_obj'             => (int) $propal->id,
                    'linked_id_object'   => (int) $this->id,
                    'linked_object_name' => 'sav_garantie_service'
                        ), true, true, true);

        if (!BimpObject::objectLoaded($line)) {
            $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
        }

        $line_errors = array();

        if ((float) $garantieHtService > 0) {
            $line->validateArray(array(
                'id_obj'             => (int) $propal->id,
                'type'               => BS_SavPropalLine::LINE_FREE,
                'deletable'          => 0,
                'editable'           => 0,
                'linked_id_object'   => (int) $this->id,
                'linked_object_name' => 'sav_garantie',
                'remisable'          => 0
            ));

            $line->desc = 'Garantie main d\'oeuvre';
            $line->id_product = 0;
            $line->pu_ht = -$garantieHtService;
            $line->pa_ht = -$garantiePaService;
            $line->product_type = 1;
            $line->id_fourn_price = 0;
            $line->qty = 1;
            if ((float) $garantieHtService) {
                $line->tva_tx = 100 * ($garantieTtcService / $garantieHtService);
            } else {
                $line->tva_tx = 0;
            }
            $line->remise = 0;

            $error_label = '';
            if (!$line->isLoaded()) {
                $error_label = 'création';
                $line_errors = $line->create($line_warnings, true);
            } else {
                $error_label = 'mise à jour';
                $line_errors = $line->update($line_warnings, true);
            }
        } else {
            if ($line->isLoaded()) {
                $error_label = 'suppression';
                $line_errors = $line->delete($line_warnings, true);
            }
        }

        if (count($line_errors)) {
            return BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la ' . $error_label . ' de la ligne "Garantie"');
        }

        return '';
    }

    public function onPropalSigned($bimpSignature)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!in_array((int) $this->getData('status'), array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_DEVIS_REFUSE, self::BS_SAV_REP_EN_COURS, self::BS_SAV_FERME))) {
                $errors = $this->updateField('status', self::BS_SAV_DEVIS_ACCEPTE);
            }

            if (!count($errors)) {
                global $user;

                $this->addNote('Devis signé le "' . date('d / m / Y H:i'));
                $propal = $this->getChildObject('propal');
                $propal->dol_object->closeProposal($user, 2, "Auto via SAV");
                $this->createReservations();
            }
        }

        return $errors;
    }

    public function sendMsg($msg_type = '', $sms_only = false, $id_contact = null)
    {
        global $langs;

        $errors = array();
        $error_msg = 'Echec de l\'envoi de la notification au client';

        if (!$msg_type) {
            if (BimpTools::isSubmit('msg_type')) {
                $msg_type = BimpTools::getValue('msg_type');
            } else {
                return array($error_msg . ' (Type de message absent)');
            }
        }

        $extra_data = BimpTools::getValue('extra_data', array());
        if (isset($extra_data['nbJours'])) {
            $nbJours = (int) $extra_data['nbJours'];
        }
        $delai = ($nbJours > 0 ? "dans " . $nbJours . " jours" : "dès maintenant");
        $delaiSms = ($nbJours > 0 ? "dans " . $nbJours . " jours" : "");

        $client = $this->getChildObject('client');
        if (is_null($client) || !$client->isLoaded()) {
            return array($error_msg . ' (ID du client absent)');
        }

        $centre = $this->getCentreData();
        if (is_null($centre)) {
            return array($error_msg . ' - Centre absent');
        }
        $signature = '';
//        $signature = BimpCache::getSignature('SAV '.BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport'), "Centre de Services Agréé Apple", $centre['tel']);
//        $signature = file_get_contents("https://www.bimp.fr/signatures/v3/supports/sign.php?prenomnom=BIMP%20SAV&job=Centre%20de%20Services%20Agr%C3%A9%C3%A9%20Apple&phone=" . urlencode($centre['tel']), false, stream_context_create(array(
//            'http' => array(
//                'timeout' => 2   // Timeout in seconds
//        ))));

        $propal = $this->getChildObject('propal');

        $files = array();

        if (!is_null($propal)) {
            if ($propal->isLoaded()) {
                $ref_propal = $propal->getSavedData("ref");
                $fileProp = DOL_DATA_ROOT . "/bimpcore/sav/" . $this->id . "/PC-" . $ref_propal . ".pdf";
                if (is_file($fileProp)) {
                    $files[] = array($fileProp, 'application/pdf', 'PC-' . $ref_propal . '.pdf');
                }

                if (!in_array($msg_type, array('debDiago', 'debut', 'localise'))) {
                    $fileProp = DOL_DATA_ROOT . "/propale/" . $ref_propal . "/" . $ref_propal . ".pdf";
                    if (is_file($fileProp)) {
                        $files[] = array($fileProp, 'application/pdf', $ref_propal . '.pdf');
                    } elseif (in_array((int) $this->getData('status'), self::$need_propal_status)) {
                        $errors[] = 'Attention: PDF du devis non trouvé et donc non envoyé au client File : ' . $fileProp;
                        BimpCore::addlog('Echec envoi devis sav par e-mail au client - PDF non trouvé', Bimp_Log::BIMP_LOG_ERREUR, 'sav', $this, array(
                            'Fichier' => $fileProp
                        ));
                    }
                }
            } else {
                unset($propal);
                $propal = null;
            }
        } elseif (in_array((int) $this->getData('status'), self::$need_propal_status)) {
            $errors[] = 'Attention: devis absent';
        }

        $tech = '';
        $user_tech = $this->getChildObject('user_tech');
        if (!is_null($user_tech) && $user_tech->isLoaded()) {
            $tech = $user_tech->dol_object->firstname;
        }

        $textSuivie = "\n <a href='" . $this->getPublicLink() . "'>Vous pouvez suivre l'intervention ici.</a>";

        $subject = '';
        $mail_msg = '';
        $sms = '';
        $nomMachine = $this->getNomMachine();
        $tabT = explode('(', $nomMachine);
        $nomMachine = $tabT[0];
        $tabT = explode('"', $nomMachine);
        if (isset($tabT[1]))
            $nomMachine = $tabT[0] . '"';
        if (strlen($nomMachine) > 20) {
            if (stripos($nomMachine, 'imac'))
                $nomMachine = 'iMac';
            elseif (stripos($nomMachine, 'iphone'))
                $nomMachine = 'iPhone';
            else
                $nomMachine = 'matériel';
        }
        $nomCentre = ($centre['label'] ? $centre['label'] : 'N/C');
        $tel = ($centre['tel'] ? $centre['tel'] : 'N/C');

        global $conf;
        $fromMail = "SAV " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . "<" . ($centre['mail'] ? $centre['mail'] : 'no-reply@' . BimpCore::getConf('default_domaine', '', 'bimpsupport')) . ">";

        $contact = null;

        if (!is_null($id_contact)) {
            if ((int) $id_contact) {
                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                if (!BimpObject::objectLoaded($contact)) {
                    $errors[] = 'Contact sélectionné invalide';
                }
            }
        } else {
            $contact = $this->getChildObject('contact');
        }
        $contact_pref = (int) $this->getData('contact_pref');

        if (count($errors)) {
            return $errors;
        }

        switch ($msg_type) {
            case 'Facture':
                $facture = null;
                $files = array();
                if ((int) $this->getData('id_facture')) {
                    $facture = $this->getChildObject('facture');
                    if (BimpObject::objectLoaded($facture)) {
                        $facture = $facture->dol_object;
                    } else {
                        unset($facture);
                        $facture = null;
                        $errors[] = $error_msg . ' - Facture invalide ou absente';
                    }
                }
//                elseif (!is_null($propal)) {
//                    $tabT = getElementElement("propal", "facture", $propal->id);
//                    if (count($tabT) > 0) {
//                        include_once DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php";
//                        $facture = new Facture($this->db->db);
//                        $facture->fetch($tabT[count($tabT) - 1]['d']);
//                        $this->set('id_facture', $facture->id);
//                        $this->update();
//                    }
//                }
                if (!is_null($facture)) {
                    $fileFact = DOL_DATA_ROOT . "/facture/" . $facture->ref . "/" . $facture->ref . ".pdf";
                    if (is_file($fileFact)) {
                        $files[] = array($fileFact, 'application/pdf', $facture->ref . '.pdf');
                    } else {
                        $errors[] = 'Attention: PDF de la facture non trouvé et donc non envoyé au client';
                        dol_syslog('SAV "' . $this->getRef() . '" - ID ' . $this->id . ': échec envoi de la facture au client', LOG_ERR);
                        BimpCore::addlog('Echec envoi facture sav par e-mail au client - PDF non trouvé', Bimp_Log::BIMP_LOG_ERREUR, 'sav', $this, array(
                            'Fichier' => $fileFact
                        ));
                    }
                } else {
                    $errors[] = $error_msg . ' - Fichier PDF de la facture absent';
                }

                $extra_files = $this->getData('in_fac_emails_files');

                if (!empty($extra_files)) {
                    foreach ($extra_files as $id_file) {
                        $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', $id_file);

                        if (BimpObject::objectLoaded($file)) {
                            $file_path = $file->getFilePath();

                            if (is_file($file_path)) {
                                $file_name = $file->getData('file_name') . '.' . $file->getData('file_ext');
                                $files[] = array($file_path, dol_mimetype($file_name), $file_name);
                            } else {
                                $errors[] = 'Fichier "' . $file->getName() . '" non trouvé';
                            }
                        }
                    }
                }

                $subject = "Fermeture du dossier " . $this->getData('ref');
                $mail_msg = 'Nous vous remercions d\'avoir choisi ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . ($nomMachine ? ' pour votre ' . $nomMachine : '') . '.' . "\n\n";

                if (!empty($files)) {
                    $mail_msg .= 'Veuillez trouver ci-joint votre facture pour cette réparation' . "\n\n";
                }

                $mail_msg .= 'Dans les prochains jours, vous allez peut-être recevoir une enquête satisfaction de la part d\'APPLE, votre retour est important afin d\'améliorer la qualité de notre Centre de Services.' . "\n";
                break;

            case 'Devis':
                if (!is_null($propal)) {
                    $subject = 'Devis ' . $this->getData('ref');
                    $mail_msg = "Voici le devis pour la réparation de votre '" . $nomMachine . "'.\n";
                    $mail_msg .= "Veuillez nous communiquer votre accord ou votre refus par retour de cet e-mail.\n";
                    $mail_msg .= "Si vous voulez des informations complémentaires, contactez le centre de service par téléphone au " . $tel . " (Appel non surtaxé).";
                    $sms = "Bonjour, nous avons établi votre devis pour votre " . $nomMachine . "\n Vous l'avez reçu par e-mail.\nL'équipe " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                }
                break;

            case 'debut':
                $subject = 'Prise en charge ' . $this->getData('ref');
                $mail_msg = "Merci d'avoir choisi " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . " en tant que Centre de Services Agréé Apple.\n";
                $mail_msg .= 'La référence de votre dossier de réparation est : ' . $this->getData('ref') . ", ";
                $mail_msg .= "si vous souhaitez communiquer d'autres informations merci de répondre à ce mail ou de contacter le " . $tel . ".\n";
                $sms = "Merci d'avoir choisi " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . " " . $nomMachine . "\nLa référence de votre dossier de réparation est : " . $this->getData('ref') . "\nL'équipe " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                break;

            case 'debDiago':
                $subject = "Prise en charge " . $this->getData('ref');
                $mail_msg = "Nous avons commencé le diagnostic de votre \"$nomMachine\", vous aurez rapidement des nouvelles de notre part. ";
                $sms = "Nous avons commencé le diagnostic de votre \" $nomMachine \", vous aurez rapidement des nouvelles de notre part.\nL'équipe " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                break;

            case 'commOk':
                $subject = 'Commande piece(s) ' . $this->getData('ref');
                $mail_msg = "Nous venons de commander la/les pièce(s) pour votre '" . $nomMachine . "' ou l'échange de votre iPod,iPad,iPhone. ";
                $mail_msg .= "\n Voici notre diagnostique : " . $this->getData("diagnostic");
                $mail_msg .= "\n Nous restons à votre disposition pour toutes questions au " . $tel;
                $sms = "Bonjour, la pièce nécessaire à votre réparation vient d'être commandée, nous vous contacterons dès réception de celle-ci.\nL'équipe " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                break;

            case 'repOk':
                $subject = $this->getData('ref') . " Reparation  terminee";
                $mail_msg = "Nous avons le plaisir de vous annoncer que la réparation de votre \"$nomMachine\" est finie.\n";
                $mail_msg .= "Voici ce que nous avons fait : " . $this->getData("resolution") . "\n";
                $mail_msg .= "Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . ", si vous souhaitez plus de renseignements, contactez le " . $tel;
                $sms = "Bonjour, la réparation de votre produit est finie. Vous pouvez le récupérer à " . $nomCentre . " " . $delaiSms . ".\nL'Equipe " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . ".";
                break;

            case 'revPropRefu':
                $subject = "Prise en charge " . $this->getData('ref') . " terminée";
                $mail_msg = "la réparation de votre \"$nomMachine\" est refusée. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . "\n";
                $mail_msg .= "Si vous souhaitez plus de renseignements, contactez le " . $tel;
                $sms = "Bonjour, la réparation de votre \"$nomMachine\"  est refusée. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delaiSms . ".\nL'Equipe " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . ".";
                break;

            case 'pieceOk':
                $subject = "Pieces recues " . $this->getData('ref');
                $mail_msg = "La pièce/le produit que nous avions commandé pour votre \"$nomMachine\" est arrivé aujourd'hui. Nous allons commencer la réparation de votre appareil.\n";
                $mail_msg .= "Vous serez prévenu dès qu'il sera prêt.";
                $sms = "Bonjour, nous venons de recevoir la pièce  pour votre réparation, nous vous contacterons quand votre matériel sera prêt.\nL'Equipe " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . ".";
                break;

            case "commercialRefuse":
                $subject = "Devis sav refusé par « " . $client->dol_object->getFullName($langs) . " »";
                $text = "Notre client « " . $client->dol_object->getNomUrl(1) . " » a refusé le devis de réparation sur son « " . $nomMachine . " » pour un montant de «  " . price($propal->dol_object->total_ttc) . "€ »";
                $id_user_tech = (int) $this->getData('id_user_tech');
                if ($id_user_tech) {
                    $where = " (SELECT `fk_usergroup` FROM `" . MAIN_DB_PREFIX . "usergroup_user` WHERE `fk_user` = " . $id_user_tech . ") AND `nom` REGEXP 'Sav([0-9])'";
//                    $rows = $this->db->getRows(array('usergroup_extrafields ge', ), "fk_object IN ".$where, null, 'object', array('mail'));

                    $sql = $this->db->db->query("SELECT `mail` FROM " . MAIN_DB_PREFIX . "usergroup_extrafields ge, " . MAIN_DB_PREFIX . "usergroup g WHERE fk_object IN  (SELECT `fk_usergroup` FROM `" . MAIN_DB_PREFIX . "usergroup_user` WHERE ge.fk_object = g.rowid AND `fk_user` = " . $id_user_tech . ") AND `nom` REGEXP 'Sav([0-9])'");

                    $mailOk = false;
                    if ($this->db->db->num_rows($sql) > 0) {
                        while ($ln = $this->db->db->fetch_object($sql)) {
                            if (isset($ln->mail) && $ln->mail != "") {
                                $toMail = str_ireplace("Sav", "Boutique", $ln->mail) . "@" . BimpCore::getConf('default_domaine', '', 'bimpsupport');
                                mailSyn2($subject, $toMail, $fromMail, $text);
                                $mailOk = true;
                            }
                        }
                    }

                    if (!$mailOk) {
                        $rows2 = $this->db->getRows('usergroup', "rowid IN " . $where, null, 'object', array('nom'));
                        if (!is_null($rows2)) {
                            foreach ($rows2 as $r) {
                                $toMail = str_ireplace("Sav", "Boutique", $r->nom) . "@" . BimpCore::getConf('default_domaine', '', 'bimpsupport');
                                mailSyn2($subject, $toMail, $fromMail, $text);
                            }
                        }
                    }
                }
                break;

            case 'sav_closed':
                break;

            case 'localise':
                $eq = $this->getChildObject("equipment");
                if ($eq->getData("status_gsx") != 3)
                    $errors[] = "L'appareil " . $eq->getLink() . ' ne semble pas localisé';
                else {
                    $contact_pref = 1; // On force l'envoi par e-mail

                    $subject = " Important, à propos de la réparation de votre appareil";

                    $mail_msg = 'Bonjour,<br/><br/>Votre appareil déposé sous le dossier <b>' . $this->getRef() . '</b>, ';
                    $mail_msg .= 'n° de série <b>' . $eq->getData('serial') . '</b>';
                    $mail_msg .= ' est toujours associé à votre compte Apple iCloud.<br/><br/>';

                    $mail_msg .= 'Afin que nous puissions procéder à la réparation de votre matériel, <span style="text-decoration: underline">il est nécessaire que celui-ci soit supprimé de votre compte iCloud.</span><br/><br/>';
                    $mail_msg .= 'Pour ce faire, vous pouvez suivre la procédure suivante depuis un navigateur internet : <br/><br/>';

                    $mail_msg .= '<ul>';
                    $mail_msg .= '<li>Connectez-vous au site <a href="https://www.icloud.com">www.icloud.com</a></li>';
                    $mail_msg .= '<li>Identifiez-vous</li>';
                    $mail_msg .= '<li>Cliquez sur « Localiser »</li>';
                    $mail_msg .= '<li>Cliquez sur « Tous mes appareils » puis sur l’appareil concerné</li>';
                    $mail_msg .= '<li>Enfin cliquez sur « Supprimer de mon compte »</li>';
                    $mail_msg .= '</ul><br/>';

                    $mail_msg .= 'Vous pouvez obtenir plus d’informations sur cette procédure en suivant <a href="https://support.apple.com/fr-fr/HT205064#remove">ce lien</a><br/><br/>';

                    $mail_msg .= 'Si vous éprouvez des difficultés à effectuer cette opération, vous pouvez obtenir une assistance par téléphone en appelant ';
                    $mail_msg .= 'AppleCare <br/>au <b>0805 540 003</b> (tarif local)<br/><br/>';

                    $mail_msg .= '<p style="font-size: 15px; font-weight: bold; text-decoration: underline">';
                    $mail_msg .= '<span style="color: red">!</span> IMPORTANT: Dès que votre appareil est supprimé de votre compte Apple iCloud, merci de nous tenir informés par retour de cet e-mail afin que nous puissions poursuivre la réparation de votre matériel';
                    $mail_msg .= '</p><br/>';

                    $mail_msg .= 'Merci de votre compréhension. <br/><br/>';
                }
                break;
        }

        if (count($errors)) {
            return $errors;
        }

        global $user;
        if ($user->login == 'f.martinez') {
            $sms_only = 1;
        }

        if (!$sms_only) {
            if ($mail_msg) {
                $toMail = '';

                if ($msg_type === 'Facture' && (int) $client->getData('contact_default')) {
                    $fac_contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $client->getData('contact_default'));

                    if (BimpObject::objectLoaded($fac_contact)) {
                        $toMail = $fac_contact->getData('email');
                    }
                }

                if (!$toMail && (int) $this->getData('id_user_client')) {
                    $userClient = $this->getChildObject('user_client');

                    if (BimpObject::objectLoaded($userClient)) {
                        $toMail = $userClient->getData('email');
                    }
                }

                if (!$toMail && BimpObject::objectLoaded($contact)) {
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

                $mail_msg .= "\n" . $textSuivie . "\n\n Cordialement.\n\nL'équipe " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . "\n\n" . $signature;

                $toMail = BimpTools::cleanEmailsStr($toMail);

                if (BimpValidate::isEmail($toMail)) {
                    $bimpMail = new BimpMail($this, $subject, $toMail, $fromMail, $mail_msg);
                    $bimpMail->addFiles($files);
                    $bimpMail->setFromType('ldlc');
                    $mail_errors = array();
                    $bimpMail->send($mail_errors);

                    if (count($mail_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail');
                    }
                } else {
                    $errors[] = "Pas d'email correct " . $toMail;
                }
            } elseif (!count($errors)) {
                $errors[] = 'pas de message';
            }
        }


        if ($contact_pref === 3 && $sms) {
            if (!empty($conf->global->MAIN_DISABLE_ALL_SMS)) {
                $errors[] = 'Envoi des SMS désactivé pour le moment';
            } else {
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

                if (stripos($sms, $this->getData('ref')) === false)
                    $sms .= "\n" . $this->getData('ref');

                $sms = str_replace('ç', 'c', $sms);
                $sms = str_replace('ê', 'e', $sms);
                $sms = str_replace('ë', 'e', $sms);
                $sms = str_replace('ô', 'o', $sms);

                if (dol_strlen(str_replace('\n', '', $sms)) > 160)
                    BimpCore::addlog('Attention SMS de ' . strlen($sms) . ' caractéres : ' . $sms);

                if ($user->login == 'f.martinez') {
                    $to = "0686691814";
                }
                $fromsms = 'SAV ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');

                $to = traiteNumMobile($to);
                if ($to == "" || (stripos($to, "+336") === false && stripos($to, "+337") === false)) {
                    $errors[] = 'Numéro invalide pour l\'envoi du sms';
                } else {
                    $smsfile = new CSMSFile($to, $fromsms, $sms);
                    if (!$smsfile->sendfile()) {
                        $errors[] = 'Echec de l\'envoi du sms';
                    }
                }
            }
        }

        if ($contact_pref === 2) {
            $errors[] = 'Le client a choisi d\'être contacté de préférence par téléphone. Veuillez penser à appeller le client.';
        }
        return $errors;
    }

    public function sendClientEmail($subject, $msg, $to = '', $files = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if (!$to) {
            $contact = $this->getChildObject('contact');

            if (BimpObject::objectLoaded($contact)) {
                $to = $contact->getData('email');
            }

            if (!$to) {
                $client = $this->getChildObject('client');

                if (BimpObject::objectLoaded($client)) {
                    $to = $client->getData('email');
                }
            }
        }

        if (!$to) {
            $errors[] = 'Aucune adresse e-mail enregistrée pour le client';
        } else {
            $centre = $this->getCentreData();
            $from = "SAV " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . "<" . ($centre['mail'] ? $centre['mail'] : 'no-reply@' . BimpCore::getConf('default_domaine', '', 'bimpsupport')) . ">";

            $to = BimpTools::cleanEmailsStr($to);

            $bimpMail = new BimpMail($this, $subject, $to, $from, $msg);
            $bimpMail->setFromType('ldlc');

            if (!empty($files)) {
                $bimpMail->addFiles($files);
            }

            $bimpMail->send($errors);
        }


        return $errors;
    }

    public function generatePDF($file_type, &$errors)
    {
        $url = '';

        if (!in_array($file_type, array('pc', 'destruction', 'destruction2', 'pret', 'europe', 'irreparable'))) {
            $errors[] = 'Type de fichier PDF invalide';
            return '';
        }

        require_once DOL_DOCUMENT_ROOT . "/bimpsupport/core/modules/bimpsupport/modules_bimpsupport.php";

        if ($file_type === 'pret') {
            $prets = $this->getChildrenObjects('prets');
            if (!count($prets)) {
                $errors[] = 'Aucun pret enregistré pour ce sav';
                return '';
            }
        }

        $errors = BimpTools::merge_array($errors, bimpsupport_pdf_create($this->db->db, $this, 'sav', $file_type));

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
                case 'europe':
                    $ref = 'LoiEuropeenne-' . $this->getData('ref');
                    break;
                case 'pret':
                    $ref = 'Pret-' . $this->getData('ref');
                    break;
                case 'irreparable':
                    $ref = 'Obsolete-' . $this->getData('ref');
                    break;
            }

            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('sav/' . $this->id . '/' . $ref . '.pdf');
        }

        return $url;
    }

    public function setAllStatutWarranty($garantie = false)
    {
        foreach ($this->getPropalLines() as $line) {
            $prod = $line->getProduct();
            if ($line->getData('linked_object_name') == 'sav_apple_part' || (BimpObject::objectLoaded($prod) && stripos($prod->getData("ref"), "sav-niveau") !== false)) {
                $out_of_warranty = $garantie ? 0 : 1;
                if ((int) $line->getData("out_of_warranty") !== $out_of_warranty) {
                    $line->set("out_of_warranty", $out_of_warranty);
                    $w = array();
                    $line->update($w, true);
                }
            }
        }
    }

    public function createReservations()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $errors = $this->removeReservations();
            if (!count($errors)) {
                BimpObject::loadClass('bimpcommercial', 'ObjectLine');
                $error_msg = 'Echec de la création de la réservation';
                $lines = $this->getPropalLines(array(
                    'type'               => ObjectLine::LINE_PRODUCT,
                    'linked_object_name' => ''
                ));

                $centre_data = $this->getCentreData(true);
                $id_entrepot = (int) BimpTools::getArrayValueFromPath($centre_data, 'id_entrepot', 0);

                if (!$id_entrepot) {
                    $errors[] = 'Entrepot absent';
                } else {
                    foreach ($lines as $line) {
                        if (BimpObject::objectLoaded($line)) {
                            if (!(int) $line->id_product) {
                                continue;
                            }
                            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $line->id_product);
                            if ($product->isLoaded()) {
                                if ((int) $product->getData('fk_product_type') === Product::TYPE_PRODUCT) {
                                    if ($product->isSerialisable()) {
                                        $eq_lines = $line->getEquipmentLines();
                                        foreach ($eq_lines as $eq_line) {
                                            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                                            $res_errors = $reservation->validateArray(array(
                                                'id_sav'             => (int) $this->id,
                                                'id_sav_propal_line' => (int) $line->id,
                                                'id_entrepot'        => (int) $id_entrepot,
                                                'id_product'         => (int) $product->id,
                                                'id_equipment'       => (int) $eq_line->getData('id_equipment'),
                                                'type'               => BR_Reservation::BR_RESERVATION_SAV,
                                                'status'             => 203,
                                                'id_commercial'      => (int) $this->getData('id_user_tech'),
                                                'id_client'          => (int) $this->getData('id_client'),
                                                'qty'                => 1,
                                                'date_from'          => date('Y-m-d H:i:s')
                                            ));

                                            if (!count($res_errors)) {
                                                $res_errors = $reservation->create();
                                            }

                                            if (count($res_errors)) {
                                                $msg = $error_msg . ' le produit "' . BimpObject::getInstanceNom($product) . '"';
                                                $errors[] = BimpTools::getMsgFromArray($res_errors, $msg);
                                            }
                                        }
                                    } else {
                                        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                                        $res_errors = $reservation->validateArray(array(
                                            'id_sav'             => (int) $this->id,
                                            'id_sav_propal_line' => (int) $line->id,
                                            'id_entrepot'        => (int) $id_entrepot,
                                            'id_product'         => (int) $product->id,
                                            'id_equipment'       => 0,
                                            'type'               => BR_Reservation::BR_RESERVATION_SAV,
                                            'status'             => 203,
                                            'id_commercial'      => (int) $this->getData('id_user_tech'),
                                            'id_client'          => (int) $this->getData('id_client'),
                                            'qty'                => (int) $line->qty,
                                            'date_from'          => date('Y-m-d H:i:s')
                                        ));
                                        if (!count($res_errors)) {
                                            $res_errors = $reservation->create();
                                        }

                                        if (count($res_errors)) {
                                            $errors[] = BimpTools::getMsgFromArray($res_errors, $error_msg . ' pour le produit "' . BimpObject::getInstanceNom($product) . '"');
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

    public function removeReservations()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

            $delete_errors = array();
            if (!$reservation->deleteBy(array(
                        'id_sav' => (int) $this->id,
                        'type'   => BR_Reservation::BR_RESERVATION_SAV
                            ), $delete_errors, true)) {
                $errors[] = BimpTools::getMsgFromArray($delete_errors, 'Echec de la suppression des réservations actuelles');
            }
        } else {
            $errors[] = 'Echec de la suppression des réservations actuelles (ID du SAV absent)';
        }
        return $errors;
    }

    public function setReservationsStatus($status)
    {
        $errors = array();

        if ($this->isLoaded()) {
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
            $list = $reservation->getList(array(
                'id_sav' => (int) $this->id,
                'type'   => BR_Reservation::BR_RESERVATION_SAV
            ));
            if (!is_null($list) && count($list)) {
                foreach ($list as $item) {
                    $reservation = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', (int) $item['id']);
                    if ($reservation->isLoaded()) {
                        $qty = null;
                        $reservation->set('status', $status);
                        $res_errors = $reservation->setNewStatus($status, array('qty' => $qty, 'id_equipment' => $reservation->getData('id_equipment')));
                        if (!count($res_errors)) {
                            $w = array();
                            $res_errors = $reservation->update($w, true);
                        }
                        if (count($res_errors)) {
                            $msg = 'Echec de la mise à jour du statut pour la réservation "' . $reservation->getData('ref') . '"';
                            $errors[] = BimpTools::getMsgFromArray($res_errors, $msg);
                        }
                    } else {
                        $errors[] = 'La réservation d\'ID "' . $item['id'] . '" n\'existe plus';
                    }
                }
            }
        } else {
            BimpObject::loadClass('bimpreservation', 'BR_Reservation');
            $errors[] = 'ID du SAV absent. Impossible de passer les réservations de produit au status "' . BR_Reservation::$status_list[$status]['label'] . '"';
        }

        return $errors;
    }

    public function renderAlertDiago()
    {
        return '<div class="error">Tous les tests post réparation empêchant le passage en prêt pour enlèvement ont été effectués ?</div>';
    }

    public function convertSav(Equipment $equipment = null)
    {
        $errors = array();

        if (is_null($equipment)) {
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        }

        BimpObject::loadClass('bimpsupport', 'BS_SavPropalLine');

        if (!(int) $this->isLoaded()) {
            $errors[] = 'SAV invalide';
        }

        $id_propal = (int) $this->getData('id_propal');
        if (!(int) $id_propal) {
            $errors[] = 'ID Propale invalide';
        }

        if (!count($errors)) {
            $version = (float) $this->getData('version');
            if ($version < 1.0) {
                $asso = new BimpAssociation($this, 'propales');
                $asso->addObjectAssociation((int) $id_propal);

                $this->db->delete('bs_sav_propal_line', '`id_obj` = ' . (int) $id_propal);

                $lines = $this->db->getRows('propaldet', 'fk_propal = ' . (int) $id_propal, null, 'array');
                $sav_products = $this->db->getRows('bs_sav_product', '`id_sav` = ' . (int) $this->id, null, 'array');
                $apple_parts = $this->db->getRows('bs_apple_part', '`id_sav` = ' . (int) $this->id, null, 'array');
                $remain_lines = array();
                $id_sav_product = 0;

                if (!is_null($lines)) {
                    $i = 1;
                    foreach ($lines as $line) {
                        $data = array(
                            'id_obj'             => (int) $id_propal,
                            'id_line'            => (int) $line['rowid'],
                            'type'               => 0,
                            'deletable'          => 0,
                            'editable'           => 0,
                            'linked_id_object'   => 0,
                            'linked_object_name' => '',
                            'id_reservation'     => 0,
                            'out_of_warranty'    => 1,
                            'position'           => (int) $line['rang'],
                            'remisable'          => 0
                        );
                        $insert = false;
                        if ((string) $line['description']) {
                            if ((int) $this->getData('id_discount') && $line['description'] === 'Acompte') {
                                $data['type'] = BS_SavPropalLine::LINE_FREE;
                                $data['linked_object_name'] = 'sav_discount';
                                $data['linked_id_object'] = (int) $this->getData('id_discount');
                                $insert = true;
                            } elseif (preg_match('/^Prise en charge.*$/', $line['description'])) {
                                $data['type'] = BS_SavPropalLine::LINE_TEXT;
                                $data['linked_object_name'] = 'sav_pc';
                                $data['linked_id_object'] = (int) $this->id;
                                $insert = true;
                            } elseif (preg_match('/^Diagnostic :.*$/', $line['description'])) {
                                $data['type'] = BS_SavPropalLine::LINE_TEXT;
                                $data['linked_object_name'] = 'sav_diagnostic';
                                $data['linked_id_object'] = (int) $this->id;
                                $insert = true;
                            } elseif ($line['description'] === $this->getData('extra_infos')) {
                                $data['type'] = BS_SavPropalLine::LINE_TEXT;
                                $data['linked_object_name'] = 'sav_extra_infos';
                                $data['linked_id_object'] = (int) $this->id;
                                $insert = true;
                            } elseif (preg_match('/^Garantie.*$/', $line['description'])) {
                                $data['type'] = BS_SavPropalLine::LINE_FREE;
                                $data['linked_object_name'] = 'sav_garantie';
                                $data['linked_id_object'] = (int) $this->id;
                                $insert = true;
                            }
                        }
                        if (!$insert) {
                            if ((int) $line['fk_product']) {
                                if ((int) $line['fk_product'] === BS_SAV::$idProdPrio) {
                                    $data['type'] = BS_SavPropalLine::LINE_PRODUCT;
                                    $data['linked_object_name'] = 'sav_prioritaire';
                                    $data['linked_id_object'] = (int) $this->id;
                                    $insert = true;
                                } else {
                                    if (!is_null($sav_products)) {
                                        foreach ($sav_products as $idx => $sp) {
                                            if ((int) $sp['id_product'] === (int) $line['fk_product'] &&
                                                    (float) $sp['qty'] === (float) $line['qty'] &&
                                                    (float) $sp['remise'] === (float) $line['remise_percent']) {
                                                $data['type'] = BS_SavPropalLine::LINE_PRODUCT;
                                                $data['out_of_warranty'] = (int) $sp['out_of_warranty'];
                                                $data['deletable'] = 1;
                                                $data['editable'] = 1;
                                                $data['def_pu_ht'] = (float) $line['subprice'];
                                                $data['def_tva_tx'] = (float) $line['tva_tx'];
                                                $data['def_id_fourn_price'] = (int) $line['fk_product_fournisseur_price'];
                                                $data['remisable'] = 1;
                                                $insert = true;
                                                unset($sav_products[$idx]);
                                                $insert = true;
                                                $id_sav_product = (int) $sp['id'];
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (!$insert) {
                            if (!is_null($apple_parts)) {
                                foreach ($apple_parts as $idx => $part) {
                                    $label = $part['part_number'] . ' - ' . $part['label'];
                                    if (strpos($line['description'], $label) !== false) {
                                        $data['type'] = BS_SavPropalLine::LINE_FREE;
                                        $data['linked_object_name'] = 'sav_apple_part';
                                        $data['linked_id_object'] = (int) $part['id'];
                                        $data['out_of_warranty'] = (int) $part['out_of_warranty'];
                                        $data['remisable'] = 1;
                                        unset($apple_parts[$idx]);
                                        $insert = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($insert) {
                            $id_new_line = (int) $this->db->insert('bs_sav_propal_line', $data, true);
                            if ($id_new_line <= 0) {
                                $errors[] = 'Echec insertion ligne propale n°' . $i . ' - ' . $this->db->db->lasterror();
                            } else {
                                if ($id_sav_product) {
                                    if ($this->db->update('br_reservation', array(
                                                'id_sav_propal_line' => $id_new_line
                                                    ), '`id_sav_product` = ' . $id_sav_product) <= 0) {
                                        $errors[] = 'Echec mise à jour de la réservation pour la ligne propale n°' . $i . ' - ' . $this->db->db->lasterror();
                                    }
                                }
                                if ((float) $line['remise_percent']) {
                                    if ($this->db->insert('object_line_remise', array(
                                                'id_object_line' => (int) $id_new_line,
                                                'object_type'    => 'sav_propal',
                                                'label'          => '',
                                                'type'           => 1,
                                                'percent'        => (float) $line['remise_percent'],
                                                'montant'        => 0,
                                                'per_unit'       => 0
                                            )) <= 0) {
                                        $errors[] = 'Echec de la création de la remise pour la ligne n°' . $i . ' - ' . $this->db->db->lasterror();
                                    }
                                }
                            }
                        } else {
                            $remain_lines[$i] = $line;
                        }

                        $i++;
                    }

                    foreach ($remain_lines as $i => $line) {
                        $data = array(
                            'id_obj'             => (int) $id_propal,
                            'id_line'            => (int) $line['rowid'],
                            'type'               => BS_SavPropalLine::LINE_FREE,
                            'deletable'          => 1,
                            'editable'           => 1,
                            'linked_id_object'   => 0,
                            'linked_object_name' => '',
                            'id_reservation'     => 0,
                            'out_of_warranty'    => 1,
                            'position'           => (int) $line['rang'],
                            'remisable'          => 1
                        );
                        $id_new_line = (int) $this->db->insert('bs_sav_propal_line', $data, true);
                        if ($id_new_line <= 0) {
                            $errors[] = 'Echec insertion ligne propale n°' . $i . ' - ' . $this->db->db->lasterror();
                        } else {
                            if ((float) $line['remise_percent']) {
                                if ($this->db->insert('object_line_remise', array(
                                            'id_object_line' => (int) $id_new_line,
                                            'object_type'    => 'sav_propal',
                                            'label'          => '',
                                            'type'           => 1,
                                            'percent'        => (float) $line['remise_percent'],
                                            'montant'        => 0,
                                            'per_unit'       => 0
                                        )) <= 0) {
                                    $errors[] = 'Echec de la création de la remise pour la ligne n°' . $i . ' - ' . $this->db->db->lasterror();
                                }
                            }
                        }
                    }
                }
                if (!count($errors)) {
                    $this->updateField('version', 1, null, true);
                }
            }
        }

        return $errors;
    }

    public function onClientUpdate(&$warnings = array(), $init_id_client = 0, $init_id_contact = 0)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $id_client = (int) $this->getData('id_client');
        $id_contact = (int) $this->getData('id_contact');
        $id_propal = (int) $this->getData('id_propal');

        if ($id_client !== (int) $init_id_client) {
            // Maj propale:
            if ($id_propal) {
                if ($this->db->update('propal', array(
                            'fk_soc' => $id_client
                                ), 'rowid = ' . $id_propal) <= 0) {
                    $errors[] = 'Echec du changement de client du devis - ' . $this->db->err();
                    return $errors;
                }
            }

            // Maj prêts:
            $this->db->update('bs_pret', array(
                'id_client' => $id_client
                    ), 'id_sav = ' . (int) $this->id);
        }

        // Maj contacts propale:
        if ($id_contact !== (int) $init_id_contact) {
            $where = 'element = \'propal\' AND source = \'external\'';
            $rows = $this->db->getRows('c_type_contact', $where, null, 'array', array('rowid'));
            if (is_array($rows)) {
                $types_contacts = array();
                foreach ($rows as $r) {
                    $types_contacts[] = (int) $r['rowid'];
                }
                $where = 'element_id = ' . $id_propal;
                $where .= ' AND fk_c_type_contact IN (' . implode(',', $types_contacts) . ')';
                $this->db->update('element_contact', array(
                    'fk_socpeople' => $id_contact
                        ), $where);
            }
        }

        return $errors;
    }

    public function checkAppleParts()
    {
        if (isset($this->parts_invoiced_processing) && $this->parts_invoiced_processing) {
            return array();
        }

        $this->parts_invoiced_processing = true;
        $errors = array();

//        if (!$this->isPropalEditable()) {
//            return array('Le devis est validé. Modification des lignes du devis impossible');
//        }

        if ((int) BimpCore::getConf('use_gsx_v2', null, 'bimpapple')) {
            if ($this->isLoaded()) {
                foreach ($this->getChildrenObjects('apple_parts') as $part) {
                    $part_errors = $part->onSavPartsChange();
                    if (count($part_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($part_errors);
                    }
                }

                $this->processPropalGarantie();
            }
        }

        unset($this->parts_invoiced_processing);

        return $errors;
    }

    public function onChildSave($child)
    {
        if (is_a($child, 'BS_ApplePart')) {
            return $this->checkAppleParts();
        }

        return array();
    }

    public function onChildDelete($child, $id_child_deleted)
    {
        if (is_a($child, 'BS_ApplePart')) {
            return $this->checkAppleParts();
        }

        return array();
    }

    public function createSignature($doc_type, $id_contact = null, &$warnings = array(), $params = array())
    {
        $params = BimpTools::overrideArray(array(
                    'allow_no_scan'   => 1,
                    'allow_dist'      => 0,
                    'allow_docusign'  => 0,
                    'allow_refuse'    => 0,
                    'open_dist_acces' => 0
                        ), $params);

        $errors = array();

        $field_name = $this->getSignatureFieldName($doc_type);

        if (!$field_name) {
            $errors[] = 'Type de signature invalide "' . $doc_type . '"';
        } elseif (!$this->field_exists($field_name)) {
            $errors[] = 'Signature non disponible';
        } elseif ((int) $this->getData($field_name)) {
            $errors[] = 'La fiche signature pour ce type de document a déjà été créée';
        } else {
            if (is_null($id_contact)) {
                $id_contact = (int) $this->getData('id_contact');
            }

            $signature = BimpObject::createBimpObject('bimpcore', 'BimpSignature', array(
                        'obj_module'    => 'bimpsupport',
                        'obj_name'      => 'BS_SAV',
                        'id_obj'        => $this->id,
                        'doc_type'      => $doc_type,
                        'id_client'     => (int) $this->getData('id_client'),
                        'id_contact'    => (int) $id_contact,
                        'allow_no_scan' => (int) $params['allow_no_scan']
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($signature)) {
                $this->updateField($field_name, (int) $signature->id);

                $signataire_errors = array();
                $signataire = BimpObject::createBimpObject('bimpcore', 'BimpSignataire', array(
                            'id_signature'   => $signature->id,
                            'id_client'      => (int) $this->getData('id_client'),
                            'id_contact'     => (int) $id_contact,
                            'allow_dist'     => ((int) $params['open_dist_acces'] || (int) $params['allow_dist']),
                            'allow_docusign' => (int) $params['allow_docusign'],
                            'allow_refuse'   => (int) $params['allow_refuse'],
                                ), true, $signataire_errors, $warnings);

                if (count($signataire_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de l\'ajout du contact signataire à la fiche signature');
                } else {
                    if ((int) $params['open_dist_acces']) {
                        $open_errors = $signataire->openSignDistAccess(true, '', true);

                        if (count($open_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de l\'accès à la signature à distance');
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function generateRestitutionPdf()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $propal = $this->getChildObject('propal');

            if (!BimpObject::objectLoaded($propal)) {
                $errors[] = 'Devis absent';
            } else {
                require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/PropalSavPDF.php';

                global $db;
                $pdf = new SavRestitutePDF($db);

                $pdf->init($propal->dol_object);

                $dir = $this->getFilesDir();
                $file_name = 'Restitution_' . dol_sanitizeFileName($this->getRef()) . '.pdf';

                if (!$pdf->render($dir . $file_name, false)) {
                    $errors = $pdf->errors;
                }
            }
        }

        return $errors;
    }
    
    public function getCodeCentre(){
        $code_centre = $this->getData('code_centre_repa');

        if (!$code_centre) {
            $code_centre = $this->getData('code_centre');
        }
        return $code_centre;
    }

    public function decreasePartsStock($parts_stock_data, $code_mvt, $desc)
    {
        BimpObject::loadClass('bimpapple', 'InternalStock');
        BimpObject::loadClass('bimpapple', 'ConsignedStock');

        $code_centre = $this->getCodeCentre();

        $warnings = array();
        foreach ($parts_stock_data as $part_number => $stock_data) {
            if (isset($stock_data['internal_stock'])) {
                $stock = InternalStock::getStockInstance($code_centre, $part_number);

                if (BimpObject::objectLoaded($stock)) {
                    if ((int) $stock->getData('serialized')) {
                        $serials = BimpTools::getArrayValueFromPath($stock_data, 'internal_stock/serials', array());

                        if (count($serials)) {
                            foreach ($serials as $serial) {
                                $stock->correctStock(-1, $serial, $code_mvt, $desc, $warnings, true, true);
                            }
                        }
                    } else {
                        $qty = (int) $stock_data['internal_stock']['qty'];

                        if ($qty > 0) {
                            $stock->correctStock(-$qty, '', $code_mvt, $desc, $warnings, true, true);
                        }
                    }
                }
            }

            if (isset($stock_data['consigned_stock'])) {
                $stock = ConsignedStock::getStockInstance($code_centre, $part_number);

                if (BimpObject::objectLoaded($stock)) {
                    if ((int) $stock->getData('serialized')) {
                        $serials = BimpTools::getArrayValueFromPath($stock_data, 'consigned_stock/serials', array());

                        if (count($serials)) {
                            foreach ($serials as $serial) {
                                $stock->correctStock(-1, $serial, $code_mvt, $desc, $warnings, true, true);
                            }
                        }
                    } else {
                        $qty = (int) $stock_data['consigned_stock']['qty'];

                        if ($qty > 0) {
                            $stock->correctStock(-$qty, '', $code_mvt, $desc, $warnings, true, true);
                        }
                    }
                }
            }
        }
    }

    public function addFacEmailFile($id_file)
    {
        $errors = array();

        $files = $this->getData('in_fac_emails_files');

        if (!in_array((int) $id_file, $files)) {
            $files[] = $id_file;
            $errors = $this->updateField('in_fac_emails_files', $files);
        }

        return $errors;
    }

    public function unlinkAcompte($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $this->updateField('acompte', 0, null, true);
        $this->updateField('id_discount', 0, null, true);
        $this->updateField('id_facture_acompte', 0, null, true);

        $success = "Acompte déliée avec succés.";
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function processBonDesctruction($create_signature = true, $open_dist_acces = true, $id_contact = null, &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $dir = $this->getFilesDir();
        $file_name = $this->getSignatureDocFileName('sav_destruct');

        if (!file_exists($dir . $file_name)) {
            $this->generatePDF('destruction', $errors);
        }

        if (!count($errors)) {
            if ($create_signature && !(int) $this->getData('id_signature_destruct')) {
                $errors = $this->createSignature('sav_destruct', $id_contact, $warnings, array(
                    'allow_no_scan'   => 0,
                    'allow_dist'      => 1,
                    'open_dist_acces' => 1
                ));
            }
        }


        return $errors;
    }

    // Actions:

    public function actionWaitClient($data, &$success)
    {
        global $user, $langs;

        $errors = array();
        $warnings = array();
        $success = 'Statut du SAV mis à jour avec succès';

        $errors = $this->setNewStatus(self::BS_SAV_ATT_CLIENT_ACTION, array(), $warnings);

        if (!count($errors)) {
            $note = 'Mise en attente client le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs);
            if (isset($data['infos']) && $data['infos']) {
                $note .= "\n\n" . $data['infos'];
            }

            $this->addNote($note, BimpNote::BN_ALL);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionStart($data, &$success)
    {
        $success = 'Statut du SAV mis à jour avec succès';

        if (!in_array($this->getData('status'), array(self::BS_SAV_NEW, self::BS_SAV_ATT_CLIENT_ACTION))) {
            $errors[] = 'Statut actuel invalide';
        } else {
            $warnings = array();
            $errors = $this->setNewStatus(self::BS_SAV_EXAM_EN_COURS, array(), $warnings);

            if (!count($errors)) {
                global $user, $langs;
                $this->addNote('Diagnostic commencé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
                $this->updateField('id_user_tech', (int) $user->id, null, true);

                if (isset($data['send_msg']) && (int) $data['send_msg']) {
                    $warnings = BimpTools::merge_array($warnings, $this->sendMsg('debDiago', false, BimpTools::getArrayValueFromPath($data, 'id_contact', null)));
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionValidatePropal($data, &$success)
    {
        $success = 'Devis validé avec succès';
        $errors = array();
        $warnings = array();
        $infos = array();

        $create_signature = BimpTools::getArrayValueFromPath($data, 'create_signature', $this->needSignaturePropal());

        if ($create_signature) {
            $client = $this->getChildObject('client');

            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Client absent';
            } else {
                $id_contact = (int) BimpTools::getArrayValueFromPath($data, 'id_contact', $this->getData('id_contact'));

                if (!$id_contact && $client->isCompany()) {
                    $errors[] = 'Veuillez sélectionner le contact signataire';
                }
            }
        }

        if (isset($data['diagnostic'])) {
            $this->updateField('diagnostic', $data['diagnostic']);
        }

        $propal = $this->getChildObject('propal');

        if (!(string) $this->getData('diagnostic')) {
            $errors[] = 'Vous devez remplir le champ "Diagnostic" avant de valider le devis';
        } else {
            $propal_errors = $this->generatePropalLines();
            if (count($propal_errors)) {
                $errors[] = BimpTools::getMsgFromArray($propal_errors, 'Des erreurs sont survenues lors de la mise à jour des lignes du devis');
            }
        }

        if (count($errors)) {
            return array('errors' => $errors);
        }

//        $errors = BimpTools::merge_array($errors, $this->createReservations());

        if (!count($errors)) {
            global $user, $langs;

            $propal->updateField(('datep'), date('Y/m/d'));
            $propal->updateField('fin_validite', BimpTools::getDateTms($propal->getData('datep')) + ($propal->dol_object->duree_validite * 24 * 3600));

            $propal->lines_locked = 1;

            $new_status = null;

            if ($this->allGarantie) { // Déterminé par $this->generatePropal()
                $this->addNote('Devis garanti validé auto le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
                // Si on vient de commander les pieces sous garentie (On ne change pas le statut)
                if ((int) $this->getData('status') !== self::BS_SAV_ATT_PIECE) {
                    $new_status = self::BS_SAV_DEVIS_ACCEPTE;
                }

                if ($propal->dol_object->valid($user) < 1)
                    $errors[] = "Echec de la validation du devis " . BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($propal->dol_object));
                else {
                    $propal->dol_object->closeProposal($user, 2, "Auto via SAV sous garantie");
                    $propal->fetch($propal->id);
                    $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
                }
            } else {
                $new_status = self::BS_SAV_ATT_CLIENT;

                if ($propal->dol_object->cond_reglement_id == 20 && $propal->dol_object->mode_reglement_id != 2) {
                    $propal->dol_object->cond_reglement_id = 1;
                    global $user;
                    $propal->dol_object->update($user);
                }
                if ($propal->dol_object->cond_reglement_id != $this->id_cond_reglement_def || $propal->dol_object->mode_reglement_id != $this->id_mode_reglement_def) {
                    //exception pour les virement bencaire a la commande 
                    if ($propal->dol_object->cond_reglement_id != 20 || $propal->dol_object->mode_reglement_id != 2) {
                        //on vérifie encours
                        $client = $this->getChildObject('client');

                        $encoursActu = $client->getAllEncoursForSiret(true)['total'];
                        $authorisation = ($client->getData('outstanding_limit') + $this->getUserLimitEncours()) * 1.2;
                        $besoin = $encoursActu + $propal->dol_object->total_ht;

                        if ($besoin > ($authorisation + 1)) {
                            $errors[] = 'Le client doit payer comptant (Carte bancaire, A réception de facture), son encours autorisé (' . price($authorisation) . ' €) est inférieur au besoin (' . price($besoin) . ' €)';
                        }
                    }
                }


                if (!count($errors)) {
                    if ($propal->dol_object->valid($user) < 1) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($propal->dol_object, array(), $langs), 'Echec de la validation du devis');
                    }

                    $obj_warnings = BimpTools::getDolEventsMsgs(array('warnings'));
                    if (!empty($obj_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($obj_warnings);
                    }

                    $obj_infos = BimpTools::getDolEventsMsgs(array('mesgs'));
                    if (!empty($obj_infos)) {
                        $infos[] = BimpTools::getMsgFromArray($obj_infos);
                    }

                    if (!count($errors) && !$propal->dol_object->generateDocument(self::$propal_model_pdf, $langs)) {
                        $errors[] = "Impossible de générer le PDF validation impossible";
                        $propal->dol_object->reopen($user, 0);
                    } elseif ($create_signature) {
                        $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', $this->getDefaultSignDistEmailContent());
                        $signature_warnings = array();
                        $signature_errors = $propal->createSignature(false, true, (int) $this->getData('id_contact'), $email_content, $signature_warnings);

                        if (count($signature_warnings)) {
                            $this->addObjectLog(BimpTools::getMsgFromArray($signature_warnings, 'Erreurs lors de la création de la fiche signature'));
                        }
                        if (count($signature_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($signature_errors, 'Echec de la création de la fiche signature');
                            $this->addObjectLog(BimpTools::getMsgFromArray($signature_errors, 'Echec de la création de la fiche signature'));
                        } else {
                            $this->addNote('Devis envoyé via signature électronique le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
                        }
                    }
                }
            }
            $propal->lines_locked = 0;

            if (!count($errors)) {
                if (!is_null($new_status)) {
                    $errors = BimpTools::merge_array($errors, $this->setNewStatus($new_status));
                }

                if (!(int) $this->getData('id_user_tech')) {
                    $this->updateField('id_user_tech', (int) $user->id);
                }

                $propal->hydrateFromDolObject();

                if (isset($data['send_msg']) && (int) $data['send_msg'] && !$this->allGarantie) {
                    $sms_only = $create_signature;
                    $id_contact_notif = BimpTools::getArrayValueFromPath($data, 'id_contact_notif', null);
                    if ((int) $id_contact != (int) $id_contact_notif) {
                        $sms_only = false;
                    }
                    $warnings = BimpTools::merge_array($warnings, $this->sendMsg('Devis', $sms_only, $id_contact_notif));
                    if (!$sms_only)
                        $this->addNote('Devis envoyé via e-mail le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
                } else {
                    $this->addNote('Devis validé sans envoi le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
                }
            }
        }

        if (count($errors)) {
//            BimpCore::addlog('Echec validation propale SAV', Bimp_Log::BIMP_LOG_ERREUR, 'sav', $this, array(
//                'Erreurs' => $errors
//            ));
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'infos'    => $infos,
        );
    }

    public function getUserLimitEncours()
    {
        global $db, $user;
        $sql = $db->query("SELECT val_max
FROM llx_validate_comm a
WHERE a.user = '" . $user->id . "' AND a.secteur = 'S' AND a.type = '0'
ORDER BY a.val_max DESC");
        if ($db->num_rows($sql) > 0) {
            $ln = $db->fetch_object($sql);
            return $ln->val_max;
        }
        return 0;
    }

    public function actionPropalAccepted($data, &$success)
    {
        $warnings = array();
        $success = 'Statut du SAV Mis à jour avec succès';
        $success_callback = '';

        $errors = $this->setNewStatus(self::BS_SAV_DEVIS_ACCEPTE);

        if (!count($errors)) {
            global $user, $langs;

            $this->addNote('Devis accepté le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
            $propal = $this->getChildObject('propal');
            $propal->dol_object->closeProposal($user, 2, "Auto via SAV");

            $this->createReservations();
        }

        return array(
            'errors'           => $errors,
            'success_callback' => $success_callback
        );
    }

    public function actionPropalRefused($data, &$success)
    {
        $success = 'Statut du SAV Mis à jour avec succès';
        $warnings = array();

        $errors = $this->setNewStatus(self::BS_SAV_DEVIS_REFUSE);

        if (!count($errors)) {
            global $user, $langs;
            $this->addNote('Devis refusé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
            $propal = $this->getChildObject('propal');
            $propal->dol_object->closeProposal($user, 3, "Auto via SAV");
            $this->removeReservations();
            if (BimpTools::getValue('send_msg', 0))
                $warnings = BimpTools::merge_array($warnings, $this->sendMsg('commercialRefuse'));

            if ((int) $propal->getData('id_signature')) {
                $signature = $propal->getChildObject('signature');

                if (BimpObject::objectLoaded($signature)) {
                    $signature->cancelAllSignatures();
                }
            }
        }
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionStartRepair($data, &$success)
    {
        $success = 'Statut du SAV Mis à jour avec succès';

        $errors = $this->setNewStatus(self::BS_SAV_REP_EN_COURS);

        if (!count($errors)) {
            global $user, $langs;

            $this->addNote('Réparation en cours depuis le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
        }

        return array(
            'errors' => $errors
        );
    }

    public function actionReviewPropal($data, &$success)
    {
        $success = 'Devis mis en révision avec succès';
        $errors = array();
        $warnings = array();

        $errors = $this->reviewPropal($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRecontact($data, &$success)
    {
        $errors = array();
        $success = 'Notification envoyée avec succès';

        if (!isset($data['msg_type']) || !$data['msg_type']) {
            $errors[] = 'Aucun type de notification sélectionné';
        } else {
            $errors = $this->sendMsg($data['msg_type'], false, BimpTools::getArrayValueFromPath($data, 'id_contact', null));
        }

        return array('errors' => $errors, 'warnings' => array());
    }

    public function actionToRestitute($data, &$success)
    {
        $success = 'Statut du SAV enregistré avec succès';
        $errors = array();
        $warnings = array();
        $succesCallback = '';

        $msg_type = '';

        $propal = $this->getChildObject('propal');

        global $user, $langs;

        $frais = (float) (isset($data['frais']) ? $data['frais'] : 0);
        if (($this->getData('status') !== self::BS_SAV_DEVIS_REFUSE && $this->isGratuit()) || ($this->getData('status') === self::BS_SAV_DEVIS_REFUSE && $frais == 0)) {
            if ($data['bon_resti_raison'] == 0)
                $errors[] = 'Raison de la non facturation obligatoire';
            else {
                $this->updateField('bon_resti_raison', $data['bon_resti_raison']);
                if ($data['bon_resti_raison'] == 99) {
                    if ($data['bon_resti_raison_detail'] == '')
                        $errors[] = 'Détail de la non facturation obligatoire';
                    else
                        $this->addNote($data['bon_resti_raison_detail']);
                }
            }
        }

        // Si refus du devis: 
        if (!count($errors)) {
            if ((int) $this->getData('status') === self::BS_SAV_DEVIS_REFUSE) {
                if (is_null($propal) || !$propal->isLoaded()) {
                    $errors[] = 'Proposition commerciale absente';
                } else {
                    $new_id_propal = $propal->review(false, $errors, $warnings, true);

                    if (!$new_id_propal && !count($errors)) {
                        $errors[] = 'Echec de la fermeture de la proposition commerciale';
                    }

                    if (!count($errors)) {
                        $this->addNote('Devis fermé après refus par le client le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
                        $client = $this->getChildObject('client');

                        if (is_null($client) || !$client->isLoaded()) {
                            $errors[] = 'Client absent';
                        } else {
                            $this->updateField('id_propal', $new_id_propal);
                            $new_propal = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SavPropal', $new_id_propal);

                            $frais = (float) (isset($data['frais']) ? $data['frais'] : 0);
                            $new_propal->dol_object->addline(
                                    "Machine(s) : " . $this->getNomMachine() .
                                    "\n" . "Frais de gestion devis refusé.", $frais / 1.20, 1, 20, 0, 0, BimpCore::getConf('id_prod_refus', '', 'bimpsupport'), $client->dol_object->remise_percent, 'HT', null, null, 1);

                            $new_propal->fetch($new_propal->id);
                            $new_propal->dol_object->valid($user);

                            $new_propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
                            $new_propal->dol_object->closeProposal($user, 2, "Auto via SAV");
                            $this->removeReservations();
                            $msg_type = 'revPropRefu';
                        }
                    }
                }
            } else {
                if (isset($data['resolution'])) {
                    $this->updateField('resolution', (string) $data['resolution'], null, true);
                }
                if ((int) $this->getData('status') !== self::BS_SAV_REP_EN_COURS) {
                    $errors[] = 'Statut actuel invalide : status actuel : ' . $this->getData('status');
                } elseif ($this->needEquipmentAttribution()) {
                    $errors[] = 'Certains produits nécessitent encore l\'attribution d\'un équipement';
                } else {
                    if (!(string) $this->getData('resolution')) {
                        $errors[] = 'Le champ "résolution" doit être complété';
                    } else {
                        $this->addNote('Réparation terminée le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
                        $propal->dol_object->closeProposal($user, 2, "Auto via SAV");
                        $msg_type = 'repOk';

                        $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair');
                        $list = $repair->getList(array(
                            'id_sav'            => (int) $this->id,
                            'ready_for_pick_up' => 0
                                ), null, null, 'id', 'asc', 'array', array('id'));
                        if (!is_null($list)) {
                            foreach ($list as $item) {
                                $repair = BimpCache::getBimpObjectInstance('bimpapple', 'GSX_Repair', (int) $item['id']);
                                if ($repair->isLoaded()) {
                                    $rep_errors = $repair->updateStatus();
                                } else {
                                    $rep_errors = array('Réparation d\'id ' . $item['id'] . ' non trouvée');
                                }
                                if (count($rep_errors)) {
                                    $succesCallback .= 'setTimeout(function(){bimpModal.newContent("Erreur GSX", "' . implode('\n', $rep_errors) . '");}, 500);';
                                    $warnings[] = BimpTools::getMsgFromArray($rep_errors, 'Echec de la fermeture de la réparation (2) d\'ID ' . $item['id']);
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            $errors = $this->setNewStatus(self::BS_SAV_A_RESTITUER);

            if (!count($errors))
                $errors = $this->updateField('date_terminer', dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S'));

            if (!count($errors)) {
                if ($msg_type && isset($data['send_msg']) && $data['send_msg']) {
                    $warnings = $this->sendMsg($msg_type, false, BimpTools::getArrayValueFromPath($data, 'id_contact_notif', null));
                }
            }
            if (!count($errors))
                $errors = $this->updateField('date_close', dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S'));
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $succesCallback
        );
    }

    public function actionClose($data, &$success)
    {
        global $user, $langs;
        $errors = array();
        $warnings = array();
        $success = 'SAV Fermé avec succès';
        $success_callback = '';

        $id_client_sav = (int) $this->getData('id_client');

//        $id_client_fac = (int) BimpTools::getArrayValueFromPath($data, 'id_client', $this->getData('id_client'));
//        $id_contact_fac = (int) BimpTools::getArrayValueFromPath($data, 'id_contact', $this->getData('id_contact'));

        $id_client_fac = (int) $this->getData('id_client');
        $id_contact_fac = (int) $this->getData('id_contact');

        $client_fac = null;
        $propal = $this->getChildObject('propal');
        $impayee = $propal->dol_object->total_ttc - (float) BimpTools::getArrayValueFromPath($data, 'paid', 0) - (float) BimpTools::getArrayValueFromPath($data, 'paid2', 0);

        // Vérification du client facturé: 
        if (!$id_client_fac) {
            $errors[] = 'Aucun client de facturation sélectionné';
        } else {
            $client_fac = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client_fac);

            if ($id_client_fac !== $id_client_sav) {
                if (!BimpObject::objectLoaded($client_fac)) {
                    $errors[] = 'Le client de facturation sélectionné n\'existe plus';
                } else {
                    if (!(int) $client_fac->getData('status')) {
                        $errors[] = 'Ce client est désactivé';
                    } elseif (!$client_fac->isSolvable($this->object_name, $warnings)) {
                        $errors[] = 'Il n\'est pas possible de créer une pièce pour ce client (' . Bimp_Societe::$solvabilites[(int) $new_client->getData('solvabilite_status')]['label'] . ')';
                    }
                }
            }

            if ($impayee > 1) {
                //on vérifie encours
                $encoursActu = $client_fac->getAllEncoursForSiret(true)['total'];
                $authorisation = ($client_fac->getData('outstanding_limit') + $this->getUserLimitEncours()) * 1.2;
                $besoin = $encoursActu + $impayee;

                if ($besoin > $authorisation) {
                    $errors[] = 'Le client doit payer comptant, son encours autorisé (' . price($authorisation) . ' €) est inférieur au besoin (' . price($besoin) . ' €)';
                }
            }
        }

        if (count($errors)) {
            return array('errors' => $errors, 'warnings' => $warnings);
        }

        // Vérif contact signataire: 
        if ((int) BimpTools::getPostFieldValue('create_signature_resti', 0)) {
            if (!(int) BimpTools::getPostFieldValue('id_contact_signataire', 0)) {
                $client_sav = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client_sav);
                if (BimpObject::objectLoaded($client_sav) && $client_sav->isCompany()) {
                    $errors[] = 'Veuillez sélectionner le contact signataire (obligatoire pour les clients pros)';
                }
            }
        }

        // Vérifs paiements: 
        $caisse = null;
        $payment_1_set = (isset($data['paid']) && (float) $data['paid'] && (isset($data['mode_paiement']) && (int) $data['mode_paiement'] > 0 && (int) $data['mode_paiement'] != 56));
        $payment_2_set = (isset($data['paid2']) && (float) $data['paid2'] > 0);
        if ($payment_1_set || $payment_2_set) {
            if ($this->useCaisseForPayments) {
                global $user;

                $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
                $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
                if (!$id_caisse) {
                    $errors[] = 'Veuillez vous <a href="' . DOL_URL_ROOT . '/bimpcaisse/index.php" target="_blank">connecter à une caisse</a> pour l\'enregistrement du paiement de la facture';
                } else {
                    $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                    if (!BimpObject::objectLoaded($caisse)) {
                        $errors[] = 'La caisse à laquelle vous êtes connecté est invalide.';
                    } else {
                        $caisse->isValid($errors);
                    }
                }
            }

            $type_paiement = '';

            if ($payment_1_set) {
                $type_paiement = $this->db->getValue('c_paiement', 'code', '`id` = ' . (int) $data['mode_paiement']);
            }

            if ($payment_2_set && $type_paiement !== 'VIR') {
                $type_paiement = $this->db->getValue('c_paiement', 'code', '`id` = ' . (int) $data['mode_paiement2']);
            }

            if ($type_paiement === 'VIR') {
                BimpObject::loadClass('bimpcommercial', 'Bimp_Paiement');
                if (!Bimp_Paiement::canCreateVirement()) {
                    $errors[] = 'Vous n\'avez pas la permission d\'enregistrer des paiements par virement';
                }
            }
        }

        if (count($errors)) {
            return array('errors' => $errors, 'warnings' => $warnings);
        }

        $current_status = (int) $this->getInitData('status');

        if ((int) $this->getData('id_propal')) {
            if (!isset($data['restitute']) || !$data['restitute']) {
                $errors[] = 'Vous devez utiliser le bouton "Restituer" pour fermer ce SAV';
            }

            if (is_null($propal) || !$propal->isLoaded()) {
                $errors[] = 'La propale n\'existe plus';
            } elseif ($propal->dol_object->total_ttc > 0) {
                if (!isset($data['mode_paiement']) || !$data['mode_paiement']) {
                    $errors[] = 'Attention, ' . price($propal->dol_object->total_ttc) . ' &euro; à payer, merci de sélectionner le moyen de paiement';
                }
            }

            if ($this->needEquipmentAttribution()) {
                $errors[] = 'Certains produits nécessitent encore l\'attribution d\'un équipement';
            }

            if (!count($errors)) {
                $propal_status = (int) $propal->getData('fk_statut');

                if ($propal_status >= 2) {
                    $res_errors = $this->setReservationsStatus(304);

                    if (count($res_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour des réservations de produits');
                    }

                    if (!count($errors)) {
                        // Gestion des stocks et emplacements: 
                        $centre_data = $this->getCentreData(true);
                        $id_entrepot = (int) BimpTools::getArrayValueFromPath($centre_data, 'id_entrepot', 0);
                        $codemove = 'SAV' . $this->id . '_';

                        foreach ($this->getPropalLines() as $line) {
                            $product = $line->getProduct();

                            if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === Product::TYPE_PRODUCT) {
                                if ($product->isSerialisable()) {
                                    $eq_lines = $line->getEquipmentLines();
                                    $eq_line_errors = array();
                                    foreach ($eq_lines as $eq_line) {
                                        if (!(int) $eq_line->getData('id_equipment')) {
                                            $eq_line_errors[] = 'Equipement non attribué';
                                        } else {
                                            $equipment = $eq_line->getChildObject('equipment');
                                            if (!BimpObject::objectLoaded($equipment)) {
                                                $eq_line_errors[] = 'Erreur: cet équipment n\'existe plus';
                                            }
                                            $eq_line_errors = BimpTools::merge_array($eq_line_errors, $equipment->moveToPlace(BE_Place::BE_PLACE_CLIENT, (int) $id_client_sav, $codemove . 'LN' . $line->id . '_EQ' . (int) $eq_line->getData('id_equipment'), 'Vente ' . $this->getRef(), 1, date('Y-m-d H:i:s'), 'sav', $this->id));
                                        }
                                    }
                                    if (count($eq_line_errors)) {
                                        $error_msg = 'Echec de la mise à jour de l\'emplacement pour le produit "' . $product->getData('ref') . ' - ' . $product->getData('label') . '"';
                                        $warnings[] = BimpTools::getMsgFromArray($eq_line_errors, $error_msg);
                                        BimpCore::addlog('Erreurs emplacement équipement(s)', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', $this, array(
                                            'Erreurs' => $eq_line_errors
                                        ));
                                    }
                                } else {
                                    $stock_errors = $product->correctStocks($id_entrepot, (int) $line->qty, Bimp_Product::STOCK_OUT, $codemove . 'LN' . $line->id, 'Vente ' . $this->getRef(), 'sav', (int) $this->id);
                                    if (count($stock_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($stock_errors);
                                    }
                                }
                            } elseif ($line->getData('linked_object_name') == 'internal_stock' && (int) $line->getData('linked_id_object')) {
                                $internal_stock = BimpCache::getBimpObjectInstance('bimpapple', 'InternalStock', (int) $line->getData('linked_id_object'));

                                if (BimpObject::objectLoaded($internal_stock)) {
                                    $stock_errors = $internal_stock->correctStock(-$line->qty, '', 'FACTURATION_SAV_' . $this->id, 'Facturation ' . $this->getLink());
                                    if (count($stock_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Echec de la mise à jour du stock interne pour le composant "' . $internal_stock->getData('part_number') . '"');
                                    }
                                }
                            }
                        }

                        // Emplacement de l'équipment: 
                        if ((int) $this->getData('id_equipment')) {
                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $this->getData('id_equipment'));

                            if (BimpObject::objectLoaded($equipment)) {
                                $cur_place = $equipment->getCurrentPlace();
                                if (BimpObject::objectLoaded($cur_place) && (int) $cur_place->getData('type') === BE_Place::BE_PLACE_SAV && (int) $cur_place->getData('id_entrepot') === (int) $this->getData('id_entrepot')) {
                                    $prev_place = BimpCache::findBimpObjectInstance('bimpequipment', 'BE_Place', array(
                                                'id_equipment' => (int) $this->getData('id_equipment'),
                                                'position'     => 2
                                    ));

                                    $place = BimpObject::getInstance('bimpequipment', 'BE_Place');

                                    $w = array();
                                    if (BimpObject::objectLoaded($prev_place) && isset($data['put_equipment_on_prev_place']) && (int) $data['put_equipment_on_prev_place']) {
                                        $place_errors = $place->validateArray(array(
                                            'id_equipment' => (int) $this->getData('id_equipment'),
                                            'type'         => $prev_place->getData('type'),
                                            'id_client'    => (int) $prev_place->getData('id_client'),
                                            'id_contact'   => (int) $prev_place->getData('id_contact'),
                                            'id_entrepot'  => (int) $prev_place->getData('id_entrepot'),
                                            'id_user'      => (int) $prev_place->getData('id_user'),
                                            'code_centre'  => $prev_place->getData('code_centre'),
                                            'place_name'   => $prev_place->getData('place_name'),
                                            'infos'        => 'Restitution ' . $this->getData('ref') . ' (Remise dans l\'emplacement précédant)',
                                            'date'         => date('Y-m-d H:i:s'),
                                            'code_mvt'     => $codemove . 'CLOSE_EQ' . (int) $this->getData('id_equipment'),
                                            'origin'       => 'sav',
                                            'id_origin'    => (int) $this->id
                                        ));
                                        if (!count($place_errors)) {
                                            $place_errors = $place->create($w, true);
                                        }
                                        if (count($place_errors)) {
                                            $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de l\'enregistrement du nouvel emplacement pour l\'équipement de ce SAV');
                                        }
                                    } else {
                                        $place_errors = $place->validateArray(array(
                                            'id_equipment' => (int) $this->getData('id_equipment'),
                                            'type'         => BE_Place::BE_PLACE_CLIENT,
                                            'id_client'    => $id_client_sav,
                                            'infos'        => 'Restitution ' . $this->getData('ref') . ' (Remise au client)',
                                            'date'         => date('Y-m-d H:i:s'),
                                            'code_mvt'     => $codemove . 'CLOSE_EQ' . (int) $this->getData('id_equipment'),
                                            'origin'       => 'sav',
                                            'id_origin'    => (int) $this->id
                                        ));
                                        if (!count($place_errors)) {
                                            $place_errors = $place->create($w, true);
                                        }
                                        if (count($place_errors)) {
                                            $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de l\'enregistrement du nouvel emplacement pour l\'équipement de ce SAV');
                                        }
                                    }
                                }
                            }
                        }

                        // Création de la facture:
                        $total_ttc_wo_discounts = (float) $propal->getTotalTtcWithoutDiscountsAbsolutes();
                        $lines = $propal->getLines('not_text');

                        $has_amounts_lines = false;

                        foreach ($lines as $line) {
                            if (round((float) $line->getTotalTTC(), 2)) {
                                $has_amounts_lines = true;
                                break;
                            }
                        }

                        if (!round($total_ttc_wo_discounts, 2) && !$has_amounts_lines) {
//                            $url = DOL_URL_ROOT . '/bimpsupport/bon_restitution.php?id_sav=' . $this->id;
                        } else {
                            if ((int) $this->getData('id_facture')) {
                                $warnings[] = 'Une facture a déjà été créée pour ce SAV';
                            } else {
                                require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                                global $db;
                                $facture = new Facture($db);

                                $cond_reglement = null;

                                if ((int) $propal->dol_object->cond_reglement_id) {
                                    $cond_reglement = (int) $propal->dol_object->cond_reglement_id;
                                } else {
                                    if (BimpObject::objectLoaded($client_fac)) {
                                        $cond_reglement = (int) $client_fac->getData('cond_reglement');
                                    }
                                }

                                if (!$cond_reglement) {
                                    $cond_reglement = (int) BimpCore::getConf('sav_cond_reglement', null, 'bimpsupport');
                                }

                                $mode_reglement = null;

                                if ($payment_1_set) {
                                    $mode_reglement = (int) BimpTools::getArrayValueFromPath($data, 'mode_paiement', (int) $propal->dol_object->mode_reglement_id);
                                } else {
                                    $mode_reglement = (int) $propal->dol_object->mode_reglement_id;
                                }

                                if (!$mode_reglement) {
                                    $mode_reglement = (int) BimpCore::getConf('sav_mode_reglement', null, 'bimpsupport');
                                }

                                $facture->date = dol_now();
                                $facture->source = 0;
                                $facture->socid = $id_client_fac;
                                $facture->fk_project = $propal->dol_object->fk_project;
                                $facture->cond_reglement_id = $cond_reglement;
                                $facture->mode_reglement_id = $mode_reglement;
                                $facture->availability_id = $propal->dol_object->availability_id;
                                $facture->demand_reason_id = $propal->dol_object->demand_reason_id;
                                $facture->date_livraison = $propal->dol_object->date_livraison;
                                $facture->fk_delivery_address = $propal->dol_object->fk_delivery_address;
                                $facture->contact_id = $id_contact_fac;
                                $facture->ref_client = $propal->dol_object->ref_client;
                                $facture->note_private = '';
                                $facture->note_public = '';

                                $facture->origin = $propal->dol_object->element;
                                $facture->origin_id = $propal->id;

                                $facture->fk_account = ((int) $propal->dol_object->fk_account ? $propal->dol_object->fk_account : (int) BimpCore::getConf('id_default_bank_account'));

                                // get extrafields from original line
                                $propal->dol_object->fetch_optionals($propal->id);

                                foreach ($propal->dol_object->array_options as $options_key => $value)
                                    $facture->array_options[$options_key] = $value;

                                $facture->modelpdf = self::$facture_model_pdf;
                                $facture->array_options['options_type'] = "S";
                                $facture->array_options['options_entrepot'] = (int) $this->getData('id_entrepot');
                                $facture->array_options['options_centre'] = $this->getData('code_centre');
                                $facture->array_options['options_expertise'] = 90;

                                if (in_array($mode_reglement, explode(',', BimpCore::getConf('rib_client_required_modes_paiement', null, 'bimpcommercial')))) {
                                    $facture->array_options['options_rib_client'] = (int) BimpTools::getArrayValueFromPath($data, 'rib_client', 0);
                                }

                                $facture->linked_objects[$facture->origin] = $facture->origin_id;
                                if (!empty($propal->dol_object->other_linked_objects) && is_array($propal->dol_object->other_linked_objects)) {
                                    $facture->linked_objects = BimpTools::merge_array($facture->linked_objects, $propal->dol_object->other_linked_objects);
                                }

                                global $user;
                                $user->rights->facture->creer = 1;

                                $id_facture = $facture->create($user);
                                if ($id_facture <= 0) {
                                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture), 'Echec de la création de la facture');
                                    return array('errors' => $errors);
                                } else {
                                    $bimpFacture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $facture->id);

                                    if (!BimpObject::objectLoaded($bimpFacture)) {
                                        $errors[] = 'La facture semble ne pas avoir été créée correctement';
                                    } else {
                                        // Ajout du commercial: 
                                        $id_commercial = (int) $this->getData('id_user_tech');
                                        if (!$id_commercial) {
                                            $id_commercial = (int) $user->id;
                                        }

                                        if ($id_commercial) {
                                            $bimpFacture->dol_object->add_contact($id_commercial, 'SALESREPFOLL', 'internal');
                                        }

                                        // Création des lignes: 
                                        $lines_errors = $bimpFacture->createLinesFromOrigin($propal);

                                        if (count($lines_errors)) {
                                            $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de l\'ajout des lignes à la facture');
                                        } else {
                                            $bimpFacture->fetch($bimpFacture->id);
                                            $bimpFacture->dol_object->addline("Résolution: " . $this->getData('resolution'), 0, 1, 0, 0, 0, 0, 0, null, null, null, null, null, 'HT', 0, 3);

                                            // Copie des remises globales: 
                                            $rg_warnings = array();
                                            $rg_errors = $bimpFacture->copyRemisesGlobalesFromOrigin($propal, $rg_warnings);

                                            if (count($rg_errors)) {
                                                $warnings[] = BimpTools::getMsgFromArray($rg_errors, 'Attention: échec de la copie des remises globales');
                                                BimpCore::addlog('Echec copie des remises globales (Facture SAV)', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', $bimpFacture, array(
                                                    'Erreurs'  => $rg_errors,
                                                    'Warnings' => (!empty($rg_warnings) ? $rg_warnings : 'Aucuns')
                                                ));
                                            }

                                            if (count($rg_warnings)) {
                                                $warnings[] = BimpTools::getMsgFromArray($rg_warnings, 'Attention: errreurs lors de la copie des remises globales pour chaque lignes');
                                            }

                                            if ($bimpFacture->dol_object->validate($user, '') <= 0) { //pas d'entrepot pour pas de destock
                                                $validate_errors = BimpTools::getErrorsFromDolObject($bimpFacture->dol_object);
                                                $validate_errors = BimpTools::merge_array($validate_errors, BimpTools::getErrorsFromDolObject($bimpFacture->dol_object));

                                                if (empty($validate_errors)) {
                                                    $validate_errors[] = 'Erreur inconnue';
                                                }

                                                $msg = BimpTools::getMsgFromArray($validate_errors, 'Echec de la validation de la facture');
                                                $errors[] = $msg;

//                                                BimpCore::addlog('Erreur validation facture SAV', Bimp_Log::BIMP_LOG_ERREUR, 'sav', $this, array(
//                                                    'Erreurs' => $validate_errors
//                                                ));
                                            } else {
                                                $bimpFacture->fetch($facture->id);

                                                // Ajout du paiement: 
                                                if ($payment_1_set) {
                                                    require_once(DOL_DOCUMENT_ROOT . "/compta/paiement/class/paiement.class.php");
                                                    $payement = new Paiement($this->db->db);
                                                    $payement->amounts = array($facture->id => (float) $data['paid']);
                                                    $payement->datepaye = dol_now();
                                                    $payement->paiementid = (int) $data['mode_paiement'];
                                                    if ($payement->create($user) <= 0) {
                                                        $warnings[] = 'Echec de l\'ajout du paiement de la facture';
                                                    } else {
                                                        // Ajout du paiement au compte bancaire: 
                                                        if ($this->useCaisseForPayments) {
                                                            $id_account = (int) $caisse->getData('id_account');
                                                        } else {
                                                            $id_account = (int) BimpCore::getConf('id_default_bank_account');
                                                        }
                                                        if ($payement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Echec de l\'ajout du paiement n°' . $payement->id . ' au compte bancaire d\'ID ' . $id_account);
                                                        }

                                                        if ($this->useCaisseForPayments) {
                                                            $warnings = BimpTools::merge_array($warnings, $caisse->addPaiement($payement, $bimpFacture->id));
                                                        }
                                                    }
                                                }

                                                // Ajout deuxième paiement

                                                if ($payment_2_set) {
                                                    require_once(DOL_DOCUMENT_ROOT . "/compta/paiement/class/paiement.class.php");
                                                    $payement = new Paiement($this->db->db);
                                                    $payement->amounts = array($facture->id => (float) $data['paid2']);
                                                    $payement->datepaye = dol_now();
                                                    $payement->paiementid = (int) $data['mode_paiement2'];
                                                    if ($payement->create($user) <= 0) {
                                                        $warnings[] = 'Echec de l\'ajout du paiement de la facture';
                                                    } else {
                                                        // Ajout du paiement au compte bancaire: 
                                                        if ($this->useCaisseForPayments) {
                                                            $id_account = (int) $caisse->getData('id_account');
                                                        } else {
                                                            $id_account = (int) BimpCore::getConf('id_default_bank_account');
                                                        }
                                                        if ($payement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Echec de l\'ajout du paiement n°' . $payement->id . ' au compte bancaire d\'ID ' . $id_account);
                                                        }

                                                        if ($this->useCaisseForPayments) {
                                                            $warnings = BimpTools::merge_array($warnings, $caisse->addPaiement($payement, $bimpFacture->id));
                                                        }
                                                    }
                                                }

                                                $bimpFacture->checkIsPaid();

                                                $propal->dol_object->closeProposal($user, 4, "Auto via SAV");

                                                //Generation
                                                $up_errors = $this->updateField('id_facture', (int) $bimpFacture->id);

                                                if (count($up_errors)) {
                                                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de l\'ID de la facture (' . $bimpFacture->id . ')');
                                                }

                                                $bimpFacture->dol_object->generateDocument(self::$facture_model_pdf, $langs);

                                                $ref = $bimpFacture->getData('ref');
                                                if (file_exists(DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf')) {
                                                    $url = DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . htmlentities('/' . $ref . '/' . $ref . '.pdf');
                                                    $success_callback .= 'window.open("' . $url . '");';
                                                }

                                                global $idAvoirFact;
                                                if (isset($idAvoirFact) && (int) $idAvoirFact) {
                                                    $up_errors = $this->updateField("id_facture_avoir", $idAvoirFact);
                                                    if (count($up_errors)) {
                                                        $idAvoirFact = 0;
                                                        $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de l\'ID de l\'avoir (' . $idAvoirFact . ')');
                                                    }

                                                    if ($idAvoirFact) {
                                                        $avoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $idAvoirFact);
                                                        if (BimpObject::objectLoaded($avoir)) {
                                                            $avoir_ref = $avoir->getRef();
                                                            if ($avoir_ref && file_exists(DOL_DATA_ROOT . '/facture/' . $avoir_ref . '/' . $avoir_ref . '.pdf')) {
                                                                $url = DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . htmlentities('/' . $avoir_ref . '/' . $avoir_ref . '.pdf');
                                                                $success_callback .= 'window.open("' . $url . '");';
                                                            } else {
                                                                $warnings[] = 'Echec de la génération du PDF de l\'avoir';
                                                            }
                                                        }
                                                    }

                                                    $idAvoirFact = 0;
                                                }

                                                if (isset($data['send_msg']) && $data['send_msg']) {
                                                    $warnings = BimpTools::merge_array($warnings, $this->sendMsg('Facture', false, BimpTools::getArrayValueFromPath($data, 'id_contact_notif', null)));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $errors[] = 'Statut de la proposition commerciale invalide';
                }
            }
        }

        if (!count($errors)) {
            if (isset($data['restitute']) && (int) $data['restitute']) {
                $this->addNote('Restitué le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
            } else {
                $this->addNote('Fermé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);
            }

            $errors = $this->setNewStatus(self::BS_SAV_FERME);

            // Génération bon de restitution:
            $pdf_errors = $this->generateRestitutionPdf();

            if (count($pdf_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($pdf_errors, 'Echec création du Bon de restitution');
            } else {
                if ((int) BimpTools::getPostFieldValue('create_signature_resti', 0)) {
                    // Création signature: 
                    $signature_errors = $this->createSignature('sav_resti', BimpTools::getPostFieldValue('id_contact_signataire'));

                    if (count($signature_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($pdf_errors, 'Echec création de la signature du Bon de restitution');
                    } else {
                        $signataire = BimpCache::findBimpObjectInstance('bimpcore', 'BimpSignataire', array(
                                    'id_signature' => $this->getData('id_signature_resti'),
                                    'code'         => 'default'
                                        ), true);

                        if (BimpObject::objectLoaded($signataire) && $signataire->isActionAllowed('signElec')) {
                            $success_callback .= 'setTimeout(function() {' . $signataire->getJsActionOnclick('signElec', array(), array(
                                        'form_name'   => 'sign_elec',
                                        'no_button'   => true,
                                        'modal_title' => 'Signature électronique du bon de restitution "BR-' . $this->getRef() . '"'
                                    )) . '}, 500);';
                        }
                    }
                }
            }
        }

        $use_db_transactions = (int) BimpCore::getConf('use_db_transactions');

        if (!count($errors)) {
            if ($use_db_transactions) {
                if (count($errors)) {
                    $this->db->db->rollback();
                } else {
                    if (!$this->db->db->commit()) {
                        $errors[] = 'Une erreur inconnue est survenue - opération annulée';
                    }
                }
            }

            // Opération hors transactions: 

            if (!count($errors)) {
                // Fermeture des réparations GSX: 
                $repair = BimpObject::getInstance('bimpapple', 'GSX_Repair');
                $list = $repair->getList(array(
                    'id_sav'            => (int) $this->id,
                    'ready_for_pick_up' => 1
                        ), null, null, 'id', 'asc', 'array', array('id'));

                if (!is_null($list)) {
                    foreach ($list as $item) {
                        $repair = BimpCache::getBimpObjectInstance('bimpapple', 'GSX_Repair', (int) $item['id']);
                        if ($repair->isLoaded()) {
                            if ($repair->getData('ready_for_pick_up'))
                                $tmp = $repair->close(true, false);
                            else//on passe d'abord en RFPU
                                $rep_errors = $repair->updateStatus();
                            if (isset($tmp['errors']))
                                $rep_errors = $tmp['errors'];
                            else {
                                $rep_errors = $tmp;
                            }
                        } else {
                            $rep_errors = array('Réparation d\'id ' . $item['id'] . ' non trouvée');
                        }
                        if (count($rep_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($rep_errors, 'Echec de la fermeture de la réparation (1) d\'ID ' . $item['id']);
                        }
                    }
                }
            }

            if ($use_db_transactions) {
                $this->db->db->begin();
            }
        } elseif (!$use_db_transactions) {
            $this->setNewStatus($current_status);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionGeneratePDF($data, &$success)
    {
        $success = 'Fichier PDF généré avec succès';

        $errors = array();
        $file_url = $this->generatePDF($data['file_type'], $errors);

        return array(
            'errors'   => $errors,
            'file_url' => $file_url
        );
    }

    public function actionAttibuteEquipment($data, &$success)
    {
        $errors = array();
        $warnings = array();

        $errors[] = 'Fonction désactivée';
//        $lines = array();
//
//        $success = '';
//
//        $propal = $this->getChildObject('propal');
//
//        if (!BimpObject::objectLoaded($propal)) {
//            return array('Proposition commerciale absente ou invalide');
//        }
//
//        BimpObject::loadClass('bimpsupport', 'BS_SavPropalLine');
//
//        foreach ($this->getPropalLines(array(
//            'type'               => BS_SavPropalLine::LINE_PRODUCT,
//            'linked_object_name' => ''
//        )) as $line) {
//            if (!(int) $line->id_product || (int) $line->getData('id_equipmnet')) {
//                continue;
//            }
//
//            $product = $line->getProduct();
//            if (BimpObject::objectLoaded($product)) {
//                if ($product->getData('fk_product_type') === Product::TYPE_PRODUCT && $product->isSerialisable()) {
//                    $lines[] = $line;
//                }
//            }
//        }
//
//        if (!count($lines)) {
//            return array('Aucun produit nécessitant l\'attribution d\'un équipement trouvé pour ce SAV');
//        }
//
//        if (!isset($data['serial']) || !$data['serial']) {
//            $errors[] = 'Veillez saisir le numéro de série d\'un équipement';
//        } else {
//            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
//            $filters = array(
//                'serial' => array(
//                    'in' => array('\'' . $data['serial'] . '\'', '\'S' . $data['serial'] . '\'')
//                )
//            );
//            $list = $equipment->getList($filters, null, null, 'id', 'desc', 'array', array('id'));
//
//            if (is_null($list) || !count($list)) {
//                $errors[] = 'Aucun équipement trouvé pour ce numéro de série';
//            } else {
//                foreach ($list as $item) {
//                    if ($equipment->fetch((int) $item['id'])) {
//                        $id_product = (int) $equipment->getData('id_product');
//                        if ($id_product) {
//                            foreach ($lines as $line) {
//                                if (!(int) $line->getData('id_equipment') && (int) $line->id_product) {
//                                    if ($id_product === (int) $line->id_product) {
//                                        $product = $line->getProduct();
//                                        if (BimpObject::objectLoaded($product)) {
//                                            $line->set('id_equipment', $equipment->id);
//                                            if (count($line->checkEquipment())) {
//                                                continue;
//                                            }
//                                            if ($propal->getData('fk_statut') > 0) {
//                                                $line->updateField('id_equipment', (int) $equipment->id);
//                                            } else {
//                                                if ((float) $equipment->getData('prix_vente_except')) {
//                                                    $line->pu_ht = (float) BimpTools::calculatePriceTaxEx((float) $equipment->getData('prix_vente_except'), (float) $product->getData('tva_tx'));
//                                                }
//                                                $errors = $line->update();
//                                            }
//                                            $success = 'Equipement ' . $equipment->id . ' (N° série ' . $equipment->getData('serial') . ') attribué pour le produit "' . $product->getData('ref') . ' - ' . $product->getData('label') . '"';
//                                            if (!count($errors)) {
//                                                $line_errors = $line->onEquipmentAttributed();
//                                                if (count($line_errors)) {
//                                                    $warnings[] = BimpTools::getMsgFromArray($line_errors);
//                                                }
//                                            }
//                                            break 2;
//                                        }
//                                    }
//                                } elseif ((int) $line->getData('id_equipment') === (int) $equipment->id) {
//                                    $errors[] = 'L\'équipement ' . $equipment->id . ' (N° série ' . $equipment->getData('serial') . ') a déjà été attribué à un produit de ce SAV';
//                                    break 2;
//                                }
//                            }
//                        }
//                    } else {
//                        $errors[] = 'Echec de la récupération des données pour l\'équipement d\'ID ' . $item['id'];
//                    }
//                }
//            }
//            if (!$success && !count($errors)) {
//                $errors[] = 'Aucun produit enregistré pour ce SAV ne correspond à ce numéro de série';
//            }
//        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAttentePiece($data, &$success)
    {
        $success = 'Mise à jour du statut du SAV effectué avec succès';

        $warnings = array(); //mais de toute facon on n'en fait rien...
        $errors = $this->setNewStatus(self::BS_SAV_ATT_PIECE);

        if (!count($errors)) {
            global $user, $langs;

            $this->addNote('Attente pièce depuis le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs), BimpNote::BN_ALL);

            if (isset($data['send_msg']) && (int) $data['send_msg']) {
                $warnings = BimpTools::merge_array($warnings, $this->sendMsg('commOk'));
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGetCodeApple($data, &$success)
    {
        $idMax = $data['idMax'];
        $success = 'Code pas encore reçu';

        $newIdMax = 0;
        $code = static::getCodeApple($idMax, $newIdMax);

        $success_callback = "setTimeout(function(){checkCode();}, 2000);";
//        if ($code != '') {
        if ($idMax == 0)
            $success_callback .= 'idMaxMesg = ' . $newIdMax . ';';
        elseif ($idMax < $newIdMax)
            $success_callback = "text = '" . urlencode($code) . "'; alert(text); const notification = new Notification('Code Apple', { body: text });";
//        }

        return array(
            'errors'           => array(),
            'warnings'         => array(),
            'success_callback' => $success_callback
        );
    }
    
    public function getIdUserGroup(){
        $codeCentre = $this->getCodeCentre();
        
        BimpCore::requireFileForEntity('bimpsupport', 'centre.inc.php');
        global $tabCentre;
        if(isset($tabCentre[$codeCentre])){
            if(isset($tabCentre[$codeCentre]['idGroup'])){
                return $tabCentre[$codeCentre]['idGroup'];
            }
            else{
                BimpCore::addlog('Pas de groupe dans centre.inc pour '.$codeCentre);
            }
        }
        else{
            BimpCore::addlog('Pas de centre dans centre.inc pour '.$codeCentre);
        }
        
        return 0;
    }
    
    public function addMailMsg($dst, $src, $subj, $txt){
        $idGroup = $this->getIdUserGroup();
        $errors = $this->addNote('Message de : '.$src.'<br/>'.'Sujet : '.$subj.'<br/>'.$txt, 20, 0, 1, $src, 2, 4, $idGroup,0,0,$this->getData('id_client'));
        if(count($errors))
            BimpCore::addlog ('Erreur création mailMsg sav', 1, 'sav', $this, $errors);
        else
            return 1;
        return 0;
    }

    public function actionAddAcompte($data, &$success)
    {
        // Attention : deux formulaires différents pour cette action avec des noms de champs différents (oui c'est pas top)
        global $conf;
        $success = "Acompte ajouté avec succés";

        $errors = array();
        $warnings = array();

        // Création de la facture d'acompte:
        $amount = 0;
        if (isset($data['amount'])) {
            $amount = $data['amount'];
        } elseif (isset($data['acompte'])) {
            $amount = $data['acompte'];
        }

        if ($amount > 0) {
            $id_mode_paiement = 0;
            if (isset($data['id_mode_paiement'])) {
                $id_mode_paiement = $data['id_mode_paiement'];
            } elseif (isset($data['mode_paiement_acompte'])) {
                $id_mode_paiement = $data['mode_paiement_acompte'];
            }

            if ($id_mode_paiement && !preg_match('/^[0-9]+$/', $id_mode_paiement)) {
                $id_mode_paiement = (int) $this->db->getValue('c_paiement', 'id', 'code = \'' . $id_mode_paiement . '\'');
            }

            if ((int) $this->getData("id_facture_acompte") < 1 && $this->isActionAllowed('validate_propal')) {
                $fac_errors = $this->createAccompte($amount, false, $id_mode_paiement, BimpTools::getArrayValueFromPath($data, 'bank_account', null));
                if (count($fac_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
                } else {
                    $this->updateField('acompte', $data['amount'], null, true);
                    $client = $this->getChildObject('client');
                    $centre = $this->getCentreData();
                    $toMail = "SAV " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . "<" . ($centre['mail'] ? $centre['mail'] : 'no-reply@' . BimpCore::getConf('default_domaine', '', 'bimpsupport')) . ">";
                    mailSyn2('Acompte enregistré ' . $this->getData('ref'), $toMail, null, 'Un acompte de ' . $amount . '€ du client ' . $client->getData('code_client') . ' - ' . $client->getData('nom') . ' à été ajouté au ' . $this->getLink());
                }
            } else {
                $data['amount'] = $amount;
                $data['id_mode_paiement'] = $id_mode_paiement;
                $data['bank_account'] = (isset($data['bank_account']) ? (int) $data['bank_account'] : (int) BimpCore::getConf('id_default_bank_account'));

                $propal = $this->getChildObject('propal');
                $client = $this->getChildObject('client');
                $centre = $this->getCentreData();
                $return = $propal->actionAddAcompte($data, $success);
                if (!count($return['errors'])) {
                    $toMail = "SAV " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . "<" . ($centre['mail'] ? $centre['mail'] : 'no-reply@' . BimpCore::getConf('default_domaine', '', 'bimpsupport')) . ">";
                    mailSyn2('Acompte enregistré ' . $this->getData('ref'), $toMail, null, 'Un acompte de ' . $amount . '€ du client ' . $client->getData('code_client') . ' - ' . $client->getData('nom') . ' à été ajouté au ' . $this->getLink());
                }

                return $return;
            }
        } else {
            $errors[] = 'Impossible d\'ajouter un acompte de 0 €';
        }
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCorrectAcompteModePaiement($data, &$success)
    {
        global $user;

        $errors = array();
        $warnings = array();
        $success = 'Mode de paiement enregistré avec succès';
        $caisse = null;

        if (!isset($data['id_paiement']) || !(int) $data['id_paiement']) {
            $errors[] = 'ID du paiement absent';
        }

        if (!isset($data['mode_paiement']) || !(int) $data['mode_paiement']) {
            $errors[] = 'veuillez sélectionner un mode de paiement';
        }

        if ($this->useCaisseForPayments && $this->getData("id_facture_acompte")) {
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
            if (!$id_caisse) {
                $errors[] = 'Veuillez-vous <a href="' . DOL_URL_ROOT . '/bimpcaisse/index.php" target="_blank">connecter à une caisse</a> pour l\'enregistrement du mode de paiement de l\'acompte';
            } else {
                $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                if (!$caisse->isLoaded()) {
                    $errors[] = 'La caisse à laquelle vous êtes connecté est invalide.';
                } else {
                    $caisse->isValid($errors);
                }
            }
        }

        if (!count($errors)) {
            if ($this->useCaisseForPayments && BimpObject::objectLoaded($caisse)) {
                $id_account = (int) $caisse->getData('id_account');
            } else {
                $id_account = (int) BimpCore::getConf('id_default_bank_account');
            }

            if (!$id_account) {
                $errors[] = 'ID du compte bancaire absent pour l\'enregistrement du mode de paiement';
            } else {
                BimpTools::loadDolClass('compta/paiement', 'paiement');
                $paiement = new Paiement($this->db->db);

                if ($paiement->fetch((int) $data['id_paiement']) <= 0) {
                    $errors[] = 'ID du paiement invalide';
                } else {
                    if ((int) $paiement->fk_paiement) {
                        $errors[] = 'Un mode de paiement valide est déjà attribué à ce paiement';
                    } else {
                        // Mise à jour en base: 
                        if ($this->db->update('paiement', array(
                                    'fk_paiement' => (int) $data['mode_paiement']
                                        ), '`rowid` = ' . (int) $data['id_paiement']) <= 0) {
                            $msg = 'Echec de l\'enregistrement du mode de paiement';
                            $sqlError = $this->db->db->lasterror();
                            if ($sqlError) {
                                $msg .= ' - ' . $sqlError;
                            }
                            $errors[] = $msg;
                        } else {
                            $paiement->paiementid = (int) $data['mode_paiement'];

                            // Ajout du paiement au compte bancaire. 
                            if ($paiement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                $account_label = '';

                                if ($this->useCaisseForPayments) {
                                    $account = $caisse->getChildObject('account');

                                    if (BimpObject::objectLoaded($account)) {
                                        $account_label = '"' . $account->bank . '"';
                                    }
                                }

                                if (!$account_label) {
                                    $account_label = ' d\'ID ' . $id_account;
                                }
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($paiement), 'Echec de l\'ajout de l\'acompte au compte bancaire ' . $account_label);
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

    public function actionSetGsxActiToken($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Authentification effectuée avec succès';

        $token = (isset($data['token']) ? $data['token'] : '');

        if (!$token) {
            $errors[] = 'Token absent';
        } else {
            $errors = static::setGsxActiToken($token);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetNew($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Prise en charge effectuée';
        $success_callback = '';

        global $user, $langs;

        // Mise à jour SAV: 
        $this->set('status', self::BS_SAV_NEW);
        $this->set('date_pc', date('Y-m-d H:i:s'));

        if (isset($data['code_centre_repa'])) {
            $this->set('code_centre_repa', $data['code_centre_repa']);
        }
        if (isset($data['id_client'])) {
            $this->set('id_client', (int) $data['id_client']);
        }
        if (isset($data['id_contact'])) {
            $this->set('id_contact', (int) $data['id_contact']);
        }
        if (isset($data['id_equipment'])) {
            $this->set('id_equipment', (int) $data['id_equipment']);
        }
        if (isset($data['code_centre_repa'])) {
            $this->set('code_centre_repa', $data['code_centre_repa']);
        }
        if (isset($data['code_centre_repa'])) {
            $this->set('code_centre_repa', $data['code_centre_repa']);
        }
        if (isset($data['prioritaire'])) {
            $this->set('code_centre_repa', (int) $data['prioritaire']);
        }
        if (isset($data['system'])) {
            $this->set('system', $data['system']);
        }
        if (isset($data['login_admin'])) {
            $this->set('login_admin', $data['login_admin']);
        }
        if (isset($data['pword_admin'])) {
            $this->set('pword_admin', $data['pword_admin']);
        }
        if (isset($data['contact_pref'])) {
            $this->set('contact_pref', $data['contact_pref']);
        }
        if (isset($data['accessoires'])) {
            $this->set('accessoires', $data['accessoires']);
        }
        if (isset($data['etat_materiel'])) {
            $this->set('etat_materiel', $data['etat_materiel']);
        }
        if (isset($data['etat_materiel_desc'])) {
            $this->set('etat_materiel_desc', $data['etat_materiel_desc']);
        }
        if (isset($data['save_option'])) {
            $this->set('save_option', (int) $data['save_option']);
        }
        if (isset($data['sav_pro'])) {
            $this->set('sav_pro', (int) $data['sav_pro']);
        }
        if (isset($data['prestataire_number'])) {
            $this->set('prestataire_number', $data['prestataire_number']);
        }
        if (isset($data['symptomes'])) {
            $this->set('symptomes', $data['symptomes']);
        }
        if (isset($data['sacs'])) {
            $this->set('sacs', $data['sacs']);
        }

        $up_errors = $this->update($warnings, true);

        if (!count($up_errors)) {
            // Création de la facture d'acompte: 
            if ((float) $data['acompte'] > 0) {
                if ((int) $this->getData('id_facture_acompte')) {
                    $warnings[] = 'Attention: l\'acompte n\'a pas pu être créé car une facture d\'acompte existe déjà pour ce SAV. Veuillez utiliser le bouton "Ajouter un acompte"';
                } else {
                    $fac_errors = $this->createAccompte((float) $data['acompte'], false, (int) BimpTools::getArrayValueFromPath($data, 'mode_paiement_acompte', 6));
                    if (count($fac_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
                    } else {
                        $this->updateField('acompte', (float) $data['acompte']);

                        $client = $this->getChildObject('client');
                        $centre = $this->getCentreData();
                        $toMail = "SAV " . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . "<" . ($centre['mail'] ? $centre['mail'] : 'no-reply@' . BimpCore::getConf('default_domaine', '', 'bimpsupport')) . ">";
//                        mailSyn2('Acompte enregistré ' . $this->getData('ref'), $toMail, null, 'Un acompte de ' . $this->getData('acompte') . '€ du client ' . $client->getData('code_client') . ' - ' . $client->getData('nom') . ' à été ajouté au ' . $this->getLink());
                        $success = "Acompte créer avec succés.";
                    }
                }
            }
        } else {
            $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du SAV');
        }

        if (!count($errors)) {
            $this->addNote('Sav pris en charge par ' . $user->getFullName($langs), BimpNote::BN_ALL);

            // Création de la popale: 
            if ($this->getData("id_propal") < 1 && $this->getData("sav_pro") < 1) {
                $prop_errors = $this->createPropal();
                if (count($prop_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($prop_errors, 'Des erreurs sont survenues lors de la création de la proposition commerciale');
                }
            }

            // Emplacement de l'équipement: 
            if ((int) $this->getData('id_equipment')) {
                $equipment = $this->getChildObject('equipment');

                if (!BimpObject::objectLoaded($equipment)) {
                    $warnings[] = 'L\'équipement d\'ID ' . $this->getData('id_equipment') . ' n\'existe pas';
                } else {
                    $current_place = $equipment->getCurrentPlace();

                    if (!BimpObject::objectLoaded($current_place) || !(int) BimpTools::getArrayValueFromPath($data, 'keep_equipment_current_place', 0)) {
                        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                        $place_errors = $place->validateArray(array(
                            'id_equipment' => (int) $this->getData('id_equipment'),
                            'type'         => BE_Place::BE_PLACE_SAV,
                            'id_entrepot'  => (int) $this->getData('id_entrepot'),
                            'infos'        => 'Ouverture du SAV ' . $this->getData('ref'),
                            'date'         => date('Y-m-d H:i:s'),
                            'code_mvt'     => 'SAV' . (int) $this->id . '_CREATE_EQ' . (int) $this->getData('id_equipment'),
                            'origin'       => 'sav',
                            'id_origin'    => (int) $this->id
                        ));
                        if (!count($place_errors)) {
                            $w = array();
                            $place_errors = $place->create($w, true);
                        }

                        if (count($place_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création de l\'emplacement de l\'équipement');
                        }
                    }
                }
            }

            // Génération du bon de prise en charge: 
            $this->generatePDF('pc', $warnings);

            // Création de la signature du Bon de prise en charge:
            $signature_errors = $this->createSignature('sav_pc');

            if (count($signature_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($signature_errors);
            }

            $success_callback = $this->getCreateJsCallback();
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionCancelRdv($data, &$success)
    {
        $debug = '';
        $errors = array();
        $warnings = array();
        $success = 'Rendez-vous annulé avec succès';

        $res_id = $this->getData('resgsx');
        $date_rdv = $this->getData('date_rdv');

        if ($res_id && $date_rdv) {

            // Annulation GSX: 
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_Reservation.php';

            $gsx_errors = array();
            $centre = $this->getCentreData();

            if (isset($centre['ship_to']) && $centre['ship_to']) {
                $result = GSX_Reservation::cancelReservation(897316, $centre['ship_to'], $res_id, $gsx_errors, $debug, array(
                            'cancelReason' => BimpTools::getArrayValueFromPath($data, 'cancel_reason', 'CUSTOMER_CANCELLED')
                ));

                if ((int) BimpCore::getConf('use_gsx_v2_for_reservations', null, 'bimpapple')) {
                    if (isset($result['errors']) && !empty($result['errors'])) {
                        $request_errors = array();
                        foreach ($result['errors'] as $error) {
                            $gsx_errors[] = $error['message'] . ' (code: ' . $error['code'] . ')';
                        }
                        $gsx_errors[] = BimpTools::getMsgFromArray($request_errors, 'Echec de l\'annulation de la réservation');
                    } elseif (is_null($result) && !count($gsx_errors)) {
                        $gsx_errors[] = 'Echec de l\'annulation de la réservation pour une raison inconnue';
                    }
                } else {
                    if (isset($result['faults']) && !empty($result['faults'])) {
                        $request_errors = array();
                        foreach ($result['faults'] as $fault) {
                            $gsx_errors[] = $fault['message'] . ' (code: ' . $fault['code'] . ')';
                        }
                        $gsx_errors[] = BimpTools::getMsgFromArray($request_errors, 'Echec de l\'annulation de la réservation');
                    } elseif (is_null($result) && !count($gsx_errors)) {
                        $gsx_errors[] = 'Echec de l\'annulation de la réservation pour une raison inconnue';
                    }
                }

                if (count($gsx_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($gsx_errors, 'Echec de l\'annulation de la réservation sur GSX');
                } else {
                    $success .= 'Annulation de la réservation sur GSX effectuée avec succès';
                }
            }
        }

        $this->set('status', self::BS_SAV_CANCELED_BY_USER);
        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            global $user, $langs;
            $this->addNote('Rendez-vous annulé par ' . $user->getFullName($langs) . ' (Raison: ' . self::$rdv_cancel_reasons[BimpTools::getArrayValueFromPath($data, 'cancel_reason', 'CUSTOMER_CANCELLED')] . ')', BimpNote::BN_ALL);

            $signature = $this->getChildObject('signature_pc');

            if (BimpObject::objectLoaded($signature)) {
                $signature->cancelAllSignatures();
            }
        }
        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'debug'    => $debug
        );
    }

    public function actionCreateSignaturePC($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature créée avec succès';
        $success_callback = '';

        $id_contact = (int) BimpTools::getArrayValueFromPath($data, 'id_contact', $this->getData('id_contact'));

        $errors = $this->createSignature('sav_pc', $id_contact, $warnings);

        if (!count($errors)) {
            $signature = $this->getChildObject('signature_pc');

            if (BimpObject::objectLoaded($signature)) {
                $url = $signature->getUrl();

                if ($url) {
                    $success_callback = 'window.open(\'' . $url . '\')';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionCreateSignatureRestitution($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature créée avec succès';

        $success_callback = '';

        $id_contact = (int) BimpTools::getArrayValueFromPath($data, 'id_contact', $this->getData('id_contact'));

        $errors = $this->createSignature('sav_resti', $id_contact, $warnings);

        if (!count($errors)) {
            $signature = $this->getChildObject('signature_resti');

            if (BimpObject::objectLoaded($signature)) {
                $url = $signature->getUrl();

                if ($url) {
                    $success_callback = 'window.open(\'' . $url . '\')';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionGenerateRestiPdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $errors = $this->generateRestitutionPdf();

        if (!count($errors)) {
            $url = $this->getFileUrl('Restitution_' . dol_sanitizeFileName($this->getRef()) . '.pdf');

            if ($url) {
                $success_callback = 'window.open(\'' . $url . '\');';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides:

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            $client = $this->getChildObject('client');
            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Le client d\'ID ' . $this->getData('id_client') . ' n\'existe pas';
            } else {
                $client_errors = $client->checkValidity();
                if (count($client_errors)) {
                    $url = $client->getUrl();
                    $msg = 'Le client sélectionné n\'est pas valide. Veuillez <a href="' . $url . '" target="_blank">Corriger</a>';
                    $errors[] = BimpTools::getMsgFromArray($client_errors, $msg);
                } elseif ((int) $this->getData('status') >= 0 && $client->isCompany()) {
                    $id_contact = (int) $this->getData('id_contact');

                    if (!$id_contact) {
                        $errors[] = 'Client pro: sélection d\'un contact client obligatoire';
                    }
                }
            }

            if (!$this->isLoaded()) { // temporaire
                foreach ($this->getData('sacs') as $sacId) {
                    $sac = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Sac', $sacId);
                    $list = $sac->getSav();
                    if (count($list)) {
                        foreach ($list as $sav) {
                            if ($sav->id != $this->id)
                                $errors[] = 'Le sac ' . $sac->getLink() . ' est déja utilisé dans le ' . $sav->getLink();
                        }
                    }
                }
            }
            if (!$this->getData('code_centre_repa')) {
                $this->set('code_centre_repa', $this->getData('code_centre'));
            }
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $client = $this->getChildObject('client');

        if (!BimpObject::objectLoaded($client)) {
            $errors[] = 'Aucun client sélectionné';
        } else {
            if (!(int) $client->getData('status')) {
                $errors[] = 'Ce client est désactivé';
            } elseif (!$force_create && !$client->isSolvable($this->object_name, $warnings)) {
                $errors[] = 'Il n\'est pas possible d\'ouvrir un SAV pour ce client (' . Bimp_Societe::$solvabilites[(int) $client->getData('solvabilite_status')]['label'] . ')';
            }
        }

        if (count($errors)) {
            return $errors;
        }

        if ((float) $this->getData('acompte') > 0) {
            if (!(int) BimpTools::getValue('mode_paiement_acompte', 0)) {
                $errors[] = 'Veuillez sélectionner un mode de paiement pour l\'acompte';
            }
            if ($this->useCaisseForPayments) {
                global $user;

                $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
                $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
                if (!$id_caisse) {
                    $errors[] = 'Veuillez-vous <a href="' . DOL_URL_ROOT . '/bimpcaisse/index.php" target="_blank">connecter à une caisse</a> pour l\'enregistrement de l\'acompte';
                } else {
                    $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                    if (!$caisse->isLoaded()) {
                        $errors[] = 'La caisse à laquelle vous êtes connecté est invalide.';
                    } else {
                        $caisse->isValid($errors);
                    }
                }
            }
        }


        if (count($errors)) {
            return $errors;
        }

        if (!(string) $this->getData('ref')) {
            $this->set('ref', $this->getNextNumRef());
        }

        $centre = $this->getCentreData();
        if (!is_null($centre)) {
            $this->set('id_entrepot', (int) $centre['id_entrepot']);
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if (!defined('DONT_CHECK_SERIAL')) {
                // Création de la facture d'acompte: 
                if ($this->getData("id_facture_acompte") < 1 && (float) $this->getData('acompte') > 0) {
                    $fac_errors = $this->createAccompte((float) $this->getData('acompte'), false);
                    if (count($fac_errors)) {
                        $fac_errors = BimpTools::merge_array(array('Des erreurs sont survenues lors de la création de la facture d\'acompte'), $fac_errors);
                        if ((int) BimpCore::getConf('use_db_transactions'))
                            $errors = BimpTools::merge_array($errors, $fac_errors);
                        else
                            $warnings = BimpTools::merge_array($warnings, $fac_errors);
                    }
                }

                if (!count($errors) && BimpCore::isContextPrivate()) {
                    // Création de la popale: 
                    if ($this->getData("id_propal") < 1 && $this->getData("sav_pro") < 1) {
                        $prop_errors = $this->createPropal();
                        if (count($prop_errors)) {
                            $prop_errors = BimpTools::merge_array(array('Des erreurs sont survenues lors de la création de la proposition commerciale'), $prop_errors);
                            if ((int) BimpCore::getConf('use_db_transactions'))
                                $errors = BimpTools::merge_array($errors, $prop_errors);
                            else
                                $warnings = BimpTools::merge_array($warnings, $prop_errors);
                        }
                    }

                    // Emplacement de l'équipement: 
                    if ((int) $this->getData('id_equipment')) {
                        $equipment = $this->getChildObject('equipment');

                        if (!BimpObject::objectLoaded($equipment)) {
                            $warnings[] = 'L\'équipement d\'ID ' . $this->getData('id_equipment') . ' n\'existe pas';
                        } else {
                            $current_place = $equipment->getCurrentPlace();

                            if (!BimpObject::objectLoaded($current_place) || !(int) BimpTools::getPostFieldValue('keep_equipment_current_place', 0)) {
                                $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                                $place_errors = $place->validateArray(array(
                                    'id_equipment' => (int) $this->getData('id_equipment'),
                                    'type'         => BE_Place::BE_PLACE_SAV,
                                    'id_entrepot'  => (int) $this->getData('id_entrepot'),
                                    'infos'        => 'Ouverture du SAV ' . $this->getData('ref'),
                                    'date'         => date('Y-m-d H:i:s'),
                                    'code_mvt'     => 'SAV' . (int) $this->id . '_CREATE_EQ' . (int) $this->getData('id_equipment'),
                                    'origin'       => 'sav',
                                    'id_origin'    => (int) $this->id
                                ));
                                if (!count($place_errors)) {
                                    $w = array();
                                    $place_errors = $place->create($w, true);
                                }

                                if (count($place_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création de l\'emplacement de l\'équipement');
                                }
                            }
                        }
                    }

                    // Génération du bon de prise en charge: 
                    $this->generatePDF('pc', $warnings);

                    // Envoi du mail / sms:
                    if (BimpTools::getValue('send_msg', 0)) {
                        $warnings = BimpTools::merge_array($warnings, $this->sendMsg('debut'));
                    }

                    // Création de la signature du Bon de prise en charge:
                    if ((int) BimpTools::getValue('create_signature_pc', 0)) {
                        $signature_errors = $this->createSignature('sav_pc');
                        if (count($signature_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($signature_errors);
                        }
                    }
                }
            }

            if (BimpObject::objectLoaded($client)) {
                $client->setActivity('Création ' . $this->getLabel('of_the') . ' {{SAV:' . $this->id . '}}');
            }
        }

        // Création des lignes propal:
        if ((int) $this->getData('id_propal')) {
            $prop_errors = $this->generatePropalLines();
            if (count($prop_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($prop_errors, 'Des erreurs sont survenues lors de la création des lignes du devis');
            }
        }

        if (!count($errors)) {
            $this->checkObject('create');
        }

        if ($this->getData('status') == 0) {
            $this->updateField('date_pc', $this->getData('date_create'));
        }

//        if (!count($errors)) {
//            $this->uploadFile('file', $errors);
//            $this->uploadFile('file2', $errors);
//        }

        return $errors;
    }

    public function uploadFile($name, &$errors)
    {
        if (file_exists($_FILES[$name]["tmp_name"])) {
            $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile');
            $file->htmlName = $name;
            $values = array();
            $values['parent_module'] = $this->module;
            $values['parent_object_name'] = $this->object_name;
            $values['id_parent'] = $this->id;
            $values['file_name'] = $name . '_' . $_FILES[$name]['name'];
            $values['is_deletable'] = 1;

            $file->validateArray($values);

            $errors = $file->create();
        }

        if (count($errors)) {
            return $errors;
        }
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();

        $centre = $this->getCentreData();
        $init_id_client = (int) $this->getInitData('id_client');
        $init_id_contact = (int) $this->getInitData('id_contact');

        $id_client = (int) $this->getData('id_client');

        if ($id_client !== $init_id_client) {
            if ((int) $this->getData('id_facture') || (int) $this->getData('id_facture_avoir')) {
                $errors[] = 'Ce SAV a déjà été facturé, impossible de changer le client ou le contact';
                return $errors;
            }

            if (!$id_client) {
                $errors[] = 'ID du nouveau client absent';
                return $errors;
            }
            $new_client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);

            if (!BimpObject::objectLoaded($new_client)) {
                $errors[] = 'Le client d\'ID ' . $id_client . ' n\'existe pas';
            } else {
                if (!(int) $new_client->getData('status')) {
                    $errors[] = 'Ce client est désactivé';
                } elseif (!$new_client->isSolvable($this->object_name, $warnings)) {
                    $errors[] = 'Il n\'est pas possible de créer une pièce pour ce client (' . Bimp_Societe::$solvabilites[(int) $new_client->getData('solvabilite_status')]['label'] . ')';
                }
            }
        }

//        if (!count($errors)) {
//            $this->uploadFile('file', $errors);
//            $this->uploadFile('file2', $errors);
//        }
//        if ($this->getData("id_facture_acompte") > 0 && (int) $this->getData('id_client') !== (int) $this->getInitData('id_client')) {
//            $errors[] = 'Facture d\'acompte, impossible de changer de client';
//            return $errors;
//        }
//
//        if (!count($errors)) {
        $errors = parent::update($warnings, $force_update);
//        }

        if (!count($errors)) {
            if (((int) $this->getData('id_client') !== $init_id_client) ||
                    (int) $this->getData('id_contact') !== $init_id_contact) {
                $errors = $this->onClientUpdate($warnings, $init_id_client, $init_id_contact);
            }

            if (!is_null($centre)) {
                $this->set('id_entrepot', (int) $centre['id_entrepot']);
            }

            if ((int) $this->getData('id_propal')) {
                $propal = $this->getChildObject('propal');
                if (BimpObject::objectLoaded($propal)) {
                    if ($propal->getData('ref_client') != $this->getData('prestataire_number')) {
                        $propal->updateField('ref_client', $this->getData('prestataire_number'));
                    }
                    if ((int) $propal->getData('fk_statut') === 0) {
                        // Mise à jour des lignes propale:
                        $prop_errors = $this->generatePropalLines();
                        if (count($prop_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($prop_errors, 'Des erreurs sont survenues lors de la mise à jour des lignes du devis');
                        }
                    }
                }
            }
        }


        if (!count($errors)) {
            $this->checkObject('update');
        }

        return $errors;
    }

    public function fetch($id, $parent = null)
    {
        if (parent::fetch($id, $parent)) {
            if ($this->check_version && (float) $this->getData('version') < 1.0) {
                $this->convertSav();
            }

            return true;
        }

        return false;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = $this->id;

        $errors = parent::delete($warnings, $force_delete);

        require_once(DOL_DOCUMENT_ROOT . "/bimpreservation/objects/BR_Reservation.class.php");

        if (!count($errors)) {
            $reservations = BimpCache::getBimpObjectObjects('bimpreservation', 'BR_Reservation', array(
                        'type'   => BR_Reservation::BR_RESERVATION_SAV,
                        'id_sav' => (int) $id
            ));

            foreach ($reservations as $id_reservation => $reservation) {
                $res_warnings = array();
                $res_errors = $reservation->delete($res_warnings, true);

                if (count($res_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($res_warnings, 'Erreurs lors de la suppression ' . $reservation->getLabel('of_the') . ' #' . $id_reservation);
                }

                if (count($res_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($res_warnings, 'Echec de la suppression ' . $reservation->getLabel('of_the') . ' #' . $id_reservation);
                }
            }
        }
        return $errors;
    }

    // Méthodes statiques: 

    public static function correctDateRdvAll($echo = false)
    {
        $bdb = BimpCache::getBdb();
        $sql = 'SELECT ac.datep as date, s.id as id_sav FROM ' . MAIN_DB_PREFIX . 'bs_sav s';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'actioncomm_extrafields acef ON acef.resgsx = s.resgsx';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'actioncomm ac ON ac.id = acef.fk_object';
        $sql .= ' WHERE s.resgsx IS NOT NULL AND s.resgsx != \'\' AND s.date_rdv IS NULL';

        $rows = $bdb->executeS($sql, 'array');

        if (is_array($rows)) {
            if (count($rows)) {
                foreach ($rows as $r) {
                    if ($echo) {
                        echo 'SAV #' . $r['id_sav'] . ' - ' . $r['date'] . ': ';
                    }

                    if ($bdb->update('bs_sav', array(
                                'date_rdv' => $r['date']
                                    ), 'id = ' . (int) $r['id_sav']) <= 0) {
                        if ($echo) {
                            echo $bdb->err() . '<br/>';
                        }
                    } else {
                        if ($echo) {
                            echo ' [OK]<br/>';
                        }
                    }
                }
            } elseif ($echo) {
                echo 'Aucun SAV à traiter';
            }
        } elseif ($echo) {
            echo $bdb->err();
        }
    }

    public static function checkSavToCancel()
    {
        global $conf;
        $bdb = self::getBdb();
        $centres = BimpCache::getCentres();

        // Traitements des RDV dépassés: 
        $dt = new DateTime();
        $dt->sub(new DateInterval('P7D'));

        $sql = 'SELECT id, ref, date_rdv, date_create, code_centre, id_client, id_contact, id_user_client FROM ' . MAIN_DB_PREFIX . 'bs_sav';
        $sql .= ' WHERE status = ' . BS_SAV::BS_SAV_RESERVED;
        $sql .= ' AND (date_rdv < \'' . date('Y-m-d') . ' 00:00:00\' OR (date_rdv IS NULL AND date_create < \'' . $dt->format('Y-m-d') . ' 00:00:00\'))';

        $rows = $bdb->executeS($sql, 'array');

        if (is_array($rows)) {
            $sav_instance = BimpObject::getInstance('bimpsupport', 'BS_SAV');

            foreach ($rows as $r) {
                if ($bdb->update('bs_sav', array(
                            'status' => BS_SAV::BS_SAV_RDV_EXPIRED
                                ), 'id = ' . (int) $r['id']) > 0) {
                    $sav_instance->id = (int) $r['id'];
                    $sav_instance->addNote('Rendez-vous annulé automatiquement le ' . date('d / m / Y à H:i'), BimpNote::BN_ALL);

                    if ((string) $r['date_rdv']) {
                        $to = '';

                        if ((int) $r['id_user_client']) {
                            $to = (string) $bdb->getValue('bic_user', 'email', 'id = ' . (int) $r['id_user_client']);
                        }

                        if (!$to && (int) $r['id_contact']) {
                            $to = (string) $bdb->getValue('socpeople', 'email', 'rowid = ' . (int) $r['id_contact']);
                        }

                        if (!$to && (int) $r['id_client']) {
                            $to = (string) $bdb->getValue('societe', 'email', 'rowid = ' . (int) $r['id_client']);
                        }

                        if ($to) {
                            $subject = 'Votre rendez-vous chez ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                            $msg = 'Cher client' . "\n\n";
                            $msg .= 'Sauf erreur de notre part, vous ne vous êtes pas présenté au rendez vous que vous aviez planifié dans notre boutique ';
                            if (isset($centres[$r['code_centre']]['town'])) {
                                if (preg_match('/^[AEIOUY].+$/', $centres[$r['code_centre']]['town'])) {
                                    $msg .= ' d\'';
                                } else {
                                    $msg .= ' de ';
                                }
                                $msg .= $centres[$r['code_centre']]['town'];
                            } else {
                                $msg .= ' ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                            }
                            $msg .= ' le ' . date('d / m / Y à H:i', strtotime($r['date_rdv'])) . '. ' . "\n";
                            $msg .= 'Celui-ci à été annulé.' . "\n\n";

                            if ((string) $r['ref']) {
                                $msg .= '<b>Référence: </b>' . $r['ref'] . "\n\n";
                            }

                            $msg .= 'Si vous avez toujours besoin d’une assistance, n’hésitez pas à reprendre un rendez vous sur votre <a href="' . BimpObject::getPublicBaseUrl(false, BimpPublicController::getPublicEntityForSecteur('S')) . '">espace personnel</a> de notre site internet « www.bimp.fr »' . "\n\n";
                            $msg .= 'L’équipe technique ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');

//                            mailSyn2($subject, $to, '', $msg);
                            $from = (isset($centres[$r['code_centre']]['mail']) ? $centres[$r['code_centre']]['mail'] : '');
                            $bimpMail = new BimpMail($sav_instance, $subject, $to, $from, $msg);
                            $bimpMail->send();

//                            BimpCore::addlog('Annulation auto SAV réservé', Bimp_Log::BIMP_LOG_NOTIF, 'bic', null, array(
//                                'ID SAV' => $r['id']
//                                    ), true);
                        }
                    }
                } else {
                    BimpCore::addlog('Echec de l\'annulation automatique d\'un SAV', Bimp_Log::BIMP_LOG_ERREUR, 'bic', null, array(
                        'ID SAV'     => $r['id'],
                        'Erreyr SQL' => $bdb->err()
                    ));
                }
            }
        }

        // Traitements des RDV dépassés: 
        $dt = new DateTime();
        $dt->sub(new DateInterval('P5D'));

        $sql = 'SELECT id, ref, date_create, code_centre, id_client, id_contact, id_user_client FROM ' . MAIN_DB_PREFIX . 'bs_sav';
        $sql .= ' WHERE status = ' . BS_SAV::BS_SAV_RESERVED;
        $sql .= ' AND date_rdv IS NULL and date_create > \'' . $dt->format('Y-m-d') . ' 00:00:00\' AND date_create < \'' . $dt->format('Y-m-d') . ' 23:59:59\'';

        $rows = $bdb->executeS($sql, 'array');

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $to = '';

                if ((int) $r['id_user_client']) {
                    $to = (string) $bdb->getValue('bic_user', 'email', 'id = ' . (int) $r['id_user_client']);
                }

                if (!$to && (int) $r['id_contact']) {
                    $to = (string) $bdb->getValue('socpeople', 'email', 'rowid = ' . (int) $r['id_contact']);
                }

                if (!$to && (int) $r['id_client']) {
                    $to = (string) $bdb->getValue('societe', 'email', 'rowid = ' . (int) $r['id_client']);
                }

                if ($to) {
                    $subject = 'Votre demande d’intervention chez ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                    $msg = 'Cher client' . "\n\n";
                    $msg .= 'Vous avez ouvert une demande d’intervention dans notre boutique ';
                    if (isset($centres[$r['code_centre']]['town'])) {
                        if (preg_match('/^[AEIOUY].+$/', $centres[$r['code_centre']]['town'])) {
                            $msg .= ' d\'';
                        } else {
                            $msg .= ' de ';
                        }
                        $msg .= $centres[$r['code_centre']]['town'];
                    } else {
                        $msg .= ' ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                    }
                    $msg .= ' le ' . date('d / m / Y à H:i', strtotime($r['date_create'])) . '. ' . "\n\n";
                    $msg .= 'Sauf erreur de notre part, vous n’avez pas déposé votre produit pour réparation. Sans nouvelle de votre part d’ci deux jours, votre demande sera clôturée.' . "\n\n";

                    if ((string) $r['ref']) {
                        $msg .= '<b>Référence: </b>' . $r['ref'] . "\n\n";
                    }

                    $msg .= 'Vous pourrez néanmoins accéder à votre <a href="https://www.bimp.fr/espace-client/">espace personnel</a> sur notre site internet «  www.bimp.fr », et si besoin, faire une nouvelle demande d’intervention.' . "\n\n";
                    $msg .= 'L’équipe technique ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');

                    $from = (isset($centres[$r['code_centre']]['mail']) ? $centres[$r['code_centre']]['mail'] : '');

                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $r['id']);
                    $bimpMail = new BimpMail($sav, $subject, $to, $from, $msg);
                    $bimpMail->send();

//                    BimpCore::addlog('Annulation auto SAV réservé', Bimp_Log::BIMP_LOG_NOTIF, 'bic', null, array(
//                        'ID SAV' => $r['id']
//                            ), true);
                }
            }
        }
    }

    public static function sendAlertesClientsUnrestituteSav()
    {
        if (!BimpCore::isModeDev()) {
            return 'En développement';
        }

        $delay = (int) BimpCore::getConf('delay_alertes_clients_unrestitute_sav', null, 'bimpsupport');

        $delay = 30;
        if (!$delay) {
            return '';
        }

        $out = '';

        $dt = new DateTime();
        $dt->sub(new DateInterval('P' . $delay . 'D'));
        $date = $dt->format('Y-m-d 00:00:00');

        $savs = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_SAV', array(
                    'a.date_terminer'     => array(
                        'operator' => '<',
                        'value'    => $date
                    ),
                    'a.status'            => array(self::BS_SAV_ATT_CLIENT, self::BS_SAV_ATT_CLIENT_ACTION, self::BS_SAV_A_RESTITUER),
                    'a.alert_unrestitute' => 0
                        ), 'id', 'asc');

        if (empty($savs)) {
            return 'Aucun SAV non restitué à alerter';
        }

        foreach ($savs as $sav) {
            if (BimpCore::isModeDev()) {
                $sav->updateField('id_client', 947);
                $sav->updateField('id_contact', 228362);

                $propal = $sav->getChildObject('propal');
                if (BimpObject::objectLoaded($propal)) {
                    $propal->updateField('fk_soc', 947);
                }
            }

            $centre_email = '';
            $centre_data = $sav->getCentreData();
            $centre_email = BimpTools::getArrayValueFromPath($centre_data, 'mail', '');
            $client_email = '';
            $contact = $sav->getChildObject('contact');

            if (BimpObject::objectLoaded($contact)) {
                $client_email = $contact->getData('email');
            }

            if (!$client_email) {
                $client = $sav->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $client_email = $client->getData('email');
                }
            }

            if ($client_email) {
                // Envoi alerte au client:
                $prod_label = '';
                $eq = $sav->getChildObject('equipment');
                if (BimpObject::objectLoaded($eq)) {
                    $prod_label = $eq->getProductLabel();
                }
                $centre_address = BimpTools::getArrayValueFromPath($centre_data, 'address', '');
                $centre_address .= ($centre_address ? '<br/>' : '') . BimpTools::getArrayValueFromPath($centre_data, 'zip', '');
                $centre_address .= ($centre_address ? ' ' : '') . BimpTools::getArrayValueFromPath($centre_data, 'town', '');
//                
                $subject = 'RAPPEL IMPORTANT concernant votre dossier SAV ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');
                $msg = 'Bonjour, <br/><br/>';
                $msg .= 'Nous tenons à vous rappeller que le dossier ' . $sav->getRef() . ' concernant la réparation de votre ';
                $msg .= ($prod_label ? '"' . $prod_label . '" ' : 'matériel');
                $msg .= ' déposé dans notre centre de réparation ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . ' ';
                if ($centre_address) {
                    $msg .= ': <br/><br/><b>' . $centre_address . '</b><br/><br/>';
                } else {
                    $msg .= '.<br/><br/>';
                }
                $msg .= 'est en attente d\'une réponse de votre part depuis plus de 30 jours.<br/><br/>';
                $msg .= 'Passé ce délai et comme indiqué dans nos conditions de prise en charge, ';
                $msg .= '<b>des frais de garde de 4 € par jour</b> sont appliqués.<br/><br/>';
                $msg .= 'Vous pouvez nous confier le recyclage de votre matériel en cliquant sur le lien ci-dessous : <br/><br/>';

                $url = self::getPublicBaseUrl(false, BimpPublicController::getPublicEntityForSecteur('S')) . 'fc=generateDoc&dt=sav_destruct&ids=' . $sav->id . '&rs=' . urlencode($sav->getRef());
                $msg .= '<a href="' . $url . '">Demander la destruction de mon matériel</a>';

                $msg .= '<br/><br/>La réception de ce document signé de votre part entraînera l\'annulation des frais de garde.';
                $msg .= '<br/><br/>Cordialement,<br/>L\'équipe ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');

                $mail_errors = array();

                $out .= $sav->getLink() . ' - Mail to ' . $client_email . ' : ';

                $bimpMail = new BimpMail($sav, $subject, $client_email, $centre_email, $msg);
                $bimpMail->setFromType('ldlc');
                if ($bimpMail->send($mail_errors)) {
                    $out .= '[OK]';
                    $sav->updateField('alert_unrestitute', 1);
                    $sav->addNote('Alerte e-mail de non restitution envoyée avec succès le ' . date('d / m / Y à H:i') . ' à l\'adresse e-mail "' . $client_email . '"', BimpNote::BN_ALL);
                } else {
                    $out .= '[ECHEC]';
                    if (count($mail_errors)) {
                        $out .= BimpRender::renderAlerts($mail_errors);
                    }
                }
                $out .= '<br/>';
            } else {
                // Envoi mail au centre SAV: 
                if (!$centre_email) {
                    $centre_email = BimpCore::getConf('default_sav_email', null, 'bimpsupport');
                }

                $msg = 'Bonjour, <br/><br/>Aucune adresse e-mail valide enregistrée pour le client du SAV ' . $sav->getLink();
                $msg .= '<br/><br/>Il n\'est donc pas possible d\'alerter le client pour la non restitution de son matériel';
                mailSyn2('Adresse e-mail client absente (SAV ' . $sav->getRef() . ')', $centre_email, '', $msg);
            }

            break; // Poir tests
        }

        return $out;
    }

    public static function setGsxActiToken($token, $login = '')
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

        $gsx = new GSX_v2();

        $errors = $gsx->setActivationToken($token, $login);
        return $errors;
    }

    // Méthodes signature: 

    public function getSignatureFieldName($doc_type)
    {
        switch ($doc_type) {
            case 'sav_pc':
                return 'id_signature_pc';

            case 'sav_resti':
                return 'id_signature_resti';

            case 'sav_destruct':
                return 'id_signature_destruct';
        }

        return'';
    }

    public function getSignatureDocFileName($doc_type, $signed = false, $file_idx = 0)
    {
        $ext = $this->getSignatureDocFileExt($doc_type, $signed);

        switch ($doc_type) {
            case 'sav_pc':
                return 'PC-' . $this->getData('ref') . ($signed ? '_signe' : '') . '.' . $ext;

            case 'sav_resti':
                return 'Restitution_' . dol_sanitizeFileName($this->getRef()) . ($signed ? '_signe' : '') . '.' . $ext;

            case 'sav_destruct':
                return 'Destruction-' . dol_sanitizeFileName($this->getRef()) . ($signed ? '_signe' : '') . '.' . $ext;
        }

        return '';
    }

    public function getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false, $file_idx = 0)
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $context = BimpCore::getContext();

        if ($forced_context) {
            $context = $forced_context;
        }

        if ($context === 'public') {
            return self::getPublicBaseUrl() . 'fc=doc&doc=' . $doc_type . ($signed ? '_signed' : '') . '&docid=' . $this->id . '&docref=' . urlencode($this->getRef());
        }

        $file_name = $this->getSignatureDocFileName($doc_type, $signed);

        if ($file_name) {
            return $this->getFileUrl($file_name);
        }

        return '';
    }

    public function getSignatureDocRef($doc_type)
    {
        switch ($doc_type) {
            case 'sav_pc':
                return 'PC-' . $this->getRef();

            case 'sav_resti':
                return 'BR-' . $this->getRef();

            case 'sav_destruct':
                return 'DESTR-' . $this->getRef();
        }

        return '';
    }

    public function getSignatureParams($doc_type)
    {
        switch ($doc_type) {
            case 'sav_pc':
                return self::$default_signature_pc_params;

            case 'sav_resti':
                return BimpTools::overrideArray(self::$default_signature_resti_params, (array) $this->getData('signature_resti_params'));

            case 'sav_destruct':
                return self::$default_signature_destruct_params;
        }

        return array();
    }

    public function getSignatureCheckMentions($doc_type, $signataire = 'default')
    {
        return array('Lu et approuvé');
    }

    public function getOnSignedNotificationEmail($doc_type, &$use_as_from = false)
    {
        $centre = $this->getCentreData();

        if (isset($centre['mail'])) {
            $use_as_from = true;
            return $centre['mail'];
        }

        return '';
    }

    public function getOnSignedEmailExtraInfos($doc_type)
    {
        if ($this->isLoaded() && $doc_type == 'devis_sav') {
            $html = '<b>SAV: </b> ' . $this->getLink(array(), 'private');

            $tech = $this->getChildObject('user_tech');

            if (BimpObject::objectLoaded($tech)) {
                $html .= '<br/><b>Technicien: </b>' . $tech->getLink(array(), 'private');
            }

            $html .= '<br/><br/>';

            return $html;
        }

        return '';
    }

    public function isSignatureCancellable()
    {
        return 0;
    }

    public function isSignatureReopenable($doc_type, &$errors = array())
    {
        return 0;
    }

    public function displaySignatureDocExtraInfos($doc_type)
    {
        global $conf;

        $html = '';

        if ($doc_type === 'sav_pc') {
            $equipment = $this->getChildObject('equipment');

            if (BimpObject::objectLoaded($equipment)) {
                $html .= '<b>Numéro de série: </b>' . $equipment->getData('serial');

                $product = $equipment->displayProduct('default', true, true);

                if ($product) {
                    $html .= '<br/><b>Produit: </b>' . $product;
                }
            } else {
                $html .= '<span class="danger">non spécifié</span>';
            }
            $html .= '<br/><br/>';

            $html .= '<b>Etat du matériel: </b>' . $this->displayData('etat_materiel', 'default', false) . '<br/>';
            $html .= '<b>Symptômes: </b>' . $this->getData('symptomes') . '<br/><br/>';
            $html .= '<b>Option de sauvegarde</b>: ' . $this->displayData('save_option', 'default', false);

            if (BS_SAV::getSaveOptionDesc((int) $this->getData('save_option')) != null) {
                $html .= '<br/><span class="danger">' . BS_SAV::getSaveOptionDesc((int) $this->getData('save_option')) . '</span>';
            }

            $cgv = "";
            $cgv .= "-La société BIMP ne peut pas être tenue responsable de la perte éventuelle de données, quelque soit le support.\n\n";

            $prixRefus = "49";

            if ($conf->global->MAIN_INFO_SOCIETE_NOM == "MY-MULTIMEDIA") {
                $prixRefus = "39";
            }

            $isIphone = false;
            $prioritaire = (int) $this->getData('prioritaire');

            if (BimpObject::objectLoaded($equipment)) {
                $isIphone = $equipment->isIphone();
            }

            if ($isIphone) {
                $prixRefus = "29";
            }

            $cgv .= "- Les frais de prise en charge diagnotic de <b>" . $prixRefus . "€ TTC</b> sont à régler pour tout matériel  hors garantie. En cas d’acceptation du devis ces frais seront déduits.<br/><br/>";
            $cgv .= "- Les problèmes logiciels, la récupération de données ou la réparation matériel liés à une mauvaise utilisation (liquide, chute, etc...), ne sont pas couverts par la GARANTIE APPLE; Un devis sera alors établi et des frais de <b>" . $prixRefus . "€ TTC</b> seront facturés en cas de refus de celui-ci." . "<br/><br/>";
            $cgv .= "- Des frais de <b>" . $prixRefus . "€ TTC</b> seront automatiquement facturés, si lors de l’expertise il s’avère que  des pièces de contrefaçon ont été installées.<br/><br/>";
            $cgv .= "- Le client s’engage à venir récupérer son bien dans un délai d’un mois après mise à disposition,émission d’un devis. Après expiration de ce délai, ce dernier accepte des frais de garde de <b>4€ par jour</b>.<br/><br/>";
            $cgv .= "- Comme l’autorise la loi du 31 décembre 1903, modifiée le 22 juin 2016, les produits qui n'auront pas été retirés dans le délai de un an pourront être détruits, après accord du tribunal.<br/><br/>";
            $cgv .= "- BIMP n’accepte plus les réglements par chèques. Les modes de réglements acceptés sont: en espèces (plafond maximum de 1000 €), en carte bleue.<br/><br/>";

            if ($prioritaire && $isIphone) {
                $cgv .= '- J\'accepte les frais de 96 TTC de prise en charge urgente';
            }

            $html .= '<div style="margin: 15px; padding: 15px; border: 1px solid #737373">';
            $html .= '<div style="text-align: center; margin-bottom: 10px">';
            $html .= '<h3 style="margin-top: 0">Conditions générales de prise en charge</h3>';

            if ($prioritaire) {
                $html .= '<span class="danger">Prise en charge urgente</span>';
            }

            $html .= '</div>';

            $html .= $cgv;

            $html .= '</div>';
        }

        return $html;
    }

    public function onSigned($signature)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        switch ($signature->getData('doc_type')) {
            case 'sav_pc':
                $signataire = BimpCache::findBimpObjectInstance('bimpcore', 'BimpSignataire', array(
                            'id_signature' => $signature->id,
                            'code'         => 'default'
                                ), true);

                if (BimpObject::objectLoaded($signataire) && $signataire->getData('type_signature') == BimpSignataire::TYPE_ELEC) {
                    $fileName = $this->getSignatureDocFileName('sav_pc', true);
                    $filePath = $this->getSignatureDocFileDir('sav_pc') . $fileName;

                    if (file_exists($filePath)) {
                        $subject = 'Votre bon de prise en charge PC-' . $this->getRef();

                        $message = 'Bonjour, ' . "\n\n";
                        $message .= 'Vous trouverez ci-joint votre bon de prise en charge ' . $this->getLink(array(), 'public') . " \n\n";
                        $message .= 'Merci d\'avoir choisi ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport') . "\n\n";
                        $message .= 'Cordialement';

                        $files = array(
                            array($filePath, 'application/pdf', $fileName)
                        );
                        $mail_errors = $this->sendClientEmail($subject, $message, '', $files);

                        if (count($mail_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi du bon de prise en charge au client par e-mail');
                        }
                    } else {
                        $errors[] = 'Impossible d\'envoyer le bon de prise en charge par e-mail au client (fichier "' . $fileName . '" non trouvé)';
                    }
                }
                break;

            case 'sav_destruct':
                $fileName = $this->getSignatureDocFileName('sav_destruct', true);
                $filePath = $this->getSignatureDocFileDir('sav_destruct') . $fileName;

                $prod_label = '';
                $eq = $this->getChildObject('equipment');
                if (BimpObject::objectLoaded($eq)) {
                    $prod_label = $eq->getProductLabel();
                }

                // Mail client: 
                $subject = 'Confirmation de la destruction de votre matériel';

                $message = 'Bonjour, ' . "\n\n";
                $message .= 'Nous vous confirmons la prise en compte de votre demande de destruction de votre ';
                $message .= ($prod_label ? $prod_label : 'matériel') . ' (Dossier ' . $this->getRef() . ').<br/><br/>';
                $message .= 'Nous vous confirmons également l\'annulation des frais de garde de 4 € par jours.<br/><br/>';
                $message .= 'Cordialement,<br/><br/>';
                $message .= 'L\'équipe ' . BimpCore::getConf('default_name', $conf->global->MAIN_INFO_SOCIETE_NOM, 'bimpsupport');

                $files = array();
                if (file_exists($filePath)) {
                    $files[] = array($filePath, 'application/pdf', $fileName);
                }

                $mail_errors = $this->sendClientEmail($subject, $message, '', $files);

                if (count($mail_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de confirmation au client');
                }
                break;
        }

        return $errors;
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

<?php

require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";
require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

class BS_SAV extends BimpObject
{

    public static $ref_model = 'SAV2{CENTRE}{00000}';
    public static $propal_model_pdf = 'bimpdevissav';
    public static $facture_model_pdf = 'bimpinvoicesav';
    public static $idProdPrio = 3422;
    private $allGarantie = true;
    public $useCaisseForPayments = false;

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

    public static $status_list = array(
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
    public static $need_propal_status = array(2, 3, 4, 5, 6, 9);
    public static $propal_reviewable_status = array(0, 1, 2, 3, 4, 6, 7, 9);
    public static $save_options = array(
        1 => 'Dispose d\'une sauvegarde',
        2 => 'Désire une sauvegarde si celle-ci est possible',
        0 => 'Non applicable',
        3 => 'Dispose d\'une sauvegarde Time machine',
        4 => 'Ne dispose pas de sauvegarde et n\'en désire pas'
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
    public static $check_on_create = 0;
    public static $check_on_update = 0;
    public static $check_on_update_field = 0;
    public static $systems_cache = null;
    public $check_version = true;

    public function __construct($db)
    {
        parent::__construct("bimpsupport", get_class($this));

        define("NOT_VERIF", true);

        $this->useCaisseForPayments = BimpCore::getConf('use_caisse_for_payments');
    }

    // Gestion des droits et autorisations: 

    public function canCreate()
    {
        return $this->can("view");
    }

    protected function canEdit()
    {
        return $this->can("view");
    }

    protected function canView()
    {
        global $user;
        return (int) $user->rights->BimpSupport->read;
    }

    public function canDelete()
    {
        global $user;
        return (int) $user->rights->BimpSupport->delete;
    }

    public function canEditField($field_name)
    {
        switch ($field_name) {
//            case 'status':
//                return 0;
        }

        return parent::canEditField($field_name);
    }

    // Getters booléens:

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
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function needEquipmentAttribution()
    {
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpsupport', 'BS_SavPropalLine');
            $lines = $this->getChildrenObjects('propal_lines', array(
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

    // Getters params: 

    public function getCreateJsCallback()
    {
        $js = '';
        $ref = 'PC-' . $this->getData('ref');
        if (file_exists(DOL_DATA_ROOT . '/bimpcore/sav/' . $this->id . '/' . $ref . '.pdf')) {
            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('sav/' . $this->id . '/' . $ref . '.pdf');
            $js .= 'window.open("' . $url . '");';
        }

        $id_facture_account = (int) $this->getData('id_facture_acompte');
        if ($id_facture_account) {
            $facture = $this->getChildObject('facture_acompte');
            if (BimpObject::objectLoaded($facture)) {
                $ref = $facture->getData('facnumber');
                if (file_exists(DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf')) {
                    $url = DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . htmlentities('/' . $ref . '/' . $ref . '.pdf');
                    $js .= 'window.open("' . $url . '");';
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
                    'file_type' => 'europe'
                        ), array(
                    'success_callback' => $callback
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
        if (BimpTools::isSubmit('id_entrepot')) {
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

            // Devis accepté / refusé: 
            if ($this->isActionAllowed('propalAccepted')) {
                $buttons[] = array(
                    'label'   => 'Devis accepté',
                    'icon'    => 'check',
                    'onclick' => $this->getJsActionOnclick('propalAccepted')
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
            if (in_array($status, array(self::BS_SAV_DEVIS_ACCEPTE))) {
                if (!is_null($propal) && $propal_status > 0) {
                    $onclick = 'setNewSavStatus($(this), ' . $this->id . ', ' . self::BS_SAV_REP_EN_COURS . ', 0)';
                    $buttons[] = array(
                        'label'   => 'Réparation en cours',
                        'icon'    => 'wrench',
                        'onclick' => $this->getJsActionOnclick('startRepair')
                    );
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

                $onclick = $propal->getJsActionOnclick('addAcompte', array(
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

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
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
                    $joins['facture'] = array(
                        'alias' => 'facture',
                        'table' => 'facture',
                        'on'    => 'facture.rowid = a.id_facture'
                    );

                    $joins['facture_avoir'] = array(
                        'alias' => 'facture_avoir',
                        'table' => 'facture',
                        'on'    => 'facture_avoir.rowid = a.id_facture_avoir'
                    );

                    $filters['date_facturation' . ($excluded ? '_excluded' : '')] = array(
                        ($excluded ? 'and_fields' : 'or') => array(
                            'facture.datef'       => array(
                                ($excluded ? 'and' : 'or_field') => $values_filters
                            ),
                            'facture_avoir.datef' => array(
                                ($excluded ? 'and' : 'or_field') => $values_filters
                            )
                        )
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
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

    public function getNomUrl($withpicto = true, $ref_only = true, $page_link = false, $modal_view = '', $card = '')
    {
        if (!$this->isLoaded()) {
            return '';
        }

        if (!$modal_view) {
            $statut = self::$status_list[$this->data["status"]];
            return "<a href='" . $this->getUrl() . "'>" . '<span class="' . implode(" ", $statut['classes']) . '"><i class="' . BimpRender::renderIconClass($statut['icon']) . ' iconLeft"></i>' . $this->getRef() . '</span></a>';
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

    public function getDefaultCodeCentre()
    {
        if (BimpTools::isSubmit('code_centre')) {
            return BimpTools::getValue('code_centre');
        } else {
            global $user;
            $userCentres = explode(' ', $user->array_options['options_apple_centre']);
            foreach ($userCentres as $code) {
                if (preg_match('/^ ?([A-Z]+) ?$/', $code, $matches)) {
                    return $matches[1];
                }
            }

            $id_entrepot = (int) $this->getData('id_entrepot');
            if (!$id_entrepot) {
                $id_entrepot = BimpTools::getValue('id_entrepot', 0);
            }
            if ($id_entrepot) {
                global $tabCentre;
                foreach ($tabCentre as $code_centre => $centre) {
                    if ((int) $centre[8] === $id_entrepot) {
                        return $code_centre;
                    }
                }
            }
        }

        return '';
    }

    public function getCentreData()
    {
        if ($code_centre = (string) $this->getData('code_centre')) {
            global $tabCentre;

            if (isset($tabCentre[$code_centre])) {
                return array(
                    'tel'         => $tabCentre[$code_centre][0],
                    'mail'        => $tabCentre[$code_centre][1],
                    'label'       => $tabCentre[$code_centre][2],
                    'zip'         => $tabCentre[$code_centre][5],
                    'town'        => $tabCentre[$code_centre][6],
                    'address'     => $tabCentre[$code_centre][7],
                    'id_entrepot' => $tabCentre[$code_centre][8]
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

    public function getSerial()
    {
        $equipment = $this->getChildObject('equipment');
        if (BimpObject::objectLoaded($equipment)) {
            return (string) $equipment->getData('serial');
        }

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

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->getData('replaced_ref')) {
            $html .= '<div style="margin-bottom: 8px">';
            $html .= '<span class="warning" style="font-size: 15px">Annule et remplace ' . $this->getLabel('the') . ' "' . $this->getData('replaced_ref') . '" (données perdues)</span>';
            $html .= '</div>';
        }

        $soc = $this->getChildObject("client");
        if (BimpObject::objectLoaded($soc)) {
            $html .= '<div>';
            $html .= $soc->getLink();
            $html .= '</div>';
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
            if ((int) $this->getData('id_propal')) {
//                $list = new BC_ListTable(BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine'), 'default', 1, (int) $this->getData('id_propal'), 'Lignes du devis');
//                $html .= $list->renderHtml();
                $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'BimpFile'), 'default', 1, null, 'fichiers joint');
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
                        $html .= '<td>' . $propal->displayData('total') . '</td>';
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

    public function renderHeaderExtraRight()
    {
        $html = '';
        if ((int) $this->getData('status') === self::BS_SAV_FERME) {
            $url = DOL_URL_ROOT . '/bimpsupport/bon_restitution.php?id_sav= ' . $this->id;
            $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
            $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Bon de restitution';
            $html .= '</span>';

            $facture = $this->getChildObject('facture');
            if (BimpObject::objectLoaded($facture) && (int) $facture->getData('fk_statut')) {
                $html .= $facture->displayPDFButton(0, 0, 'Facture');
                $html .= $facture->displayPaiementsFacturesPdfButtons(0, 1);
            }
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
        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
        $html .= 'Réouvrir fenêtre d\'authentification' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
        $html .= '</span>';

        $gsx = GSX_v2::getInstance();
        $html .= '<h4>Rappel de votre identifiant GSX: </h4>';
        $html .= '<strong>AppleId</strong>: ' . $gsx->appleId . '<br/>';
        if ($gsx->appleId === GSX_v2::$default_ids['apple_id']) {
            $html .= '<strong>Mot de passe</strong>: ' . GSX_v2::$default_ids['apple_pword'];
        }

        $html .= '<p class="small" style="text-align: center; margin-top: 15px">';
        $html .= 'Si la fenêtre d\authentification ne s\'ouvre pas, veuillez vérifier que votre navigateur ne bloque pas l\'ouverture des fenêtres pop-up';
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

        if (BimpCore::getConf('use_gsx_v2')) {
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
        if ((int) BimpCore::getConf('use_gsx_v2')) {
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

    // Traitements:

    public function checkObject($context = '', $field = '')
    {
        if ($context === 'fetch') {
            if ($this->isLoaded()) {
                $this->resetMsgs();

                // Vérif de l'existance de la propale: 
                if ($this->getData("sav_pro") < 1) {
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
                        $update = false;
                        if (!(int) $propal->dol_object->array_options['options_entrepot']) {
                            if (!(int) $this->getData('id_entrepot')) {
                                $this->msgs['errors'][] = 'Aucun entrepôt défini pour ce SAV';
                                dol_syslog('Aucun entrepôt défini pour le SAV "' . $this->getRef() . '"', LOG_ERR);
                            } else {
                                $propal->set('entrepot', (int) $this->getData('id_entrepot'));
                                $update = true;
                            }
                        }

                        if ((string) $propal->getData('libelle') !== $this->getRef()) {
                            $propal->set('libelle', $this->getRef());
                            $update = true;
                        }

                        if ((string) $propal->getData('ef_type') !== 'S') {
                            $propal->set('ef_type', 'S');
                            $update = true;
                        }

                        if ($update) {
                            $warnings = array();
                            $prop_errors = $propal->update($warnings, true);
                            if (count($prop_errors)) {
                                dol_syslog(BimpTools::getMsgFromArray($prop_errors, 'Echec de la réparation automatique de la propale pour le SAV "' . $this->getRef() . '"'), LOG_ERR);
                            } else {
                                dol_syslog('Correction automatique de la propale pour le SAV "' . $this->getRef() . '" effectuée avec succès', LOG_NOTICE);
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
                if (!in_array($current_status, array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_ATT_PIECE))) {
                    $errors[] = $error_msg . ' (Statut actuel invalide)';
                } else {
                    if ($current_status === self::BS_SAV_ATT_PIECE) {
                        $this->addNote('Pièce reçue le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
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

    public function createAccompte($acompte, $update = true)
    {
        global $user, $langs;

        $errors = array();

        $caisse = null;
        $id_caisse = 0;

        if ($this->useCaisseForPayments) {
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
            if (!$id_caisse) {
                $errors[] = 'Utilisateur connecté à aucune caisse. Enregistrement de l\'acompte abandonné';
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
            // Création de la facture: 
            BimpTools::loadDolClass('compta/facture', 'facture');
            $factureA = new Facture($this->db->db);
            $factureA->type = 3;
            $factureA->date = dol_now();
            $factureA->socid = $this->getData('id_client');
            $factureA->cond_reglement_id = 1;
            $factureA->modelpdf = self::$facture_model_pdf;
            $factureA->array_options['options_type'] = "S";
            $factureA->array_options['options_entrepot'] = $this->getData('id_entrepot');

            $user->rights->facture->creer = 1;
            if ($factureA->create($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($factureA), 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
            } else {
                $factureA->addline("Acompte", $acompte / 1.2, 1, 20, null, null, null, 0, null, null, null, null, null, 'HT', null, 1, null, null, null, null, null, null, $acompte / 1.2);
                $factureA->validate($user);

                // Création du paiement: 
                BimpTools::loadDolClass('compta/paiement', 'paiement');
                $payement = new Paiement($this->db->db);
                $payement->amounts = array($factureA->id => $acompte);
                $payement->datepaye = dol_now();
                $payement->paiementid = (int) BimpTools::getValue('mode_paiement_acompte', 0);
                if ($payement->create($user) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Des erreurs sont survenues lors de la création du paiement de la facture d\'acompte');
                } else {
                    if ($this->useCaisseForPayments) {
                        $id_account = (int) $caisse->getData('id_account');
                    } else {
                        $id_account = (int) BimpCore::getConf('bimpcaisse_id_default_account');
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

            $id_cond_reglement = (int) $client->getData('cond_reglement');
            $id_mode_reglement = (int) $client->getData('mode_reglement');

            if (!$id_cond_reglement) {
                $id_cond_reglement = 1;
            }

            if (!$id_mode_reglement) {
                $id_mode_reglement = 6;
            }

            BimpTools::loadDolClass('comm/propal', 'propal');
            $prop = new Propal($this->db->db);
            $prop->modelpdf = self::$propal_model_pdf;
            $prop->socid = $client->id;
            $prop->date = dol_now();
            $prop->cond_reglement_id = $id_cond_reglement;
            $prop->mode_reglement_id = $id_mode_reglement;
            $prop->fk_account = BimpCore::getConf('bimpcaisse_id_default_account');

            if ($prop->create($user) <= 0) {
                $errors[] = 'Echec de la création de la propale';
                BimpTools::getErrorsFromDolObject($prop, $errors, $langs);
            } else {
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

    public function reviewPropal(&$warnings = array())
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

        if (is_null($propal)) {
            $bProp = $this->getChildObject('propal');
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

        foreach ($this->getChildrenObjects('propal_lines', array(
            'type' => array("in" => array(BS_SavPropalLine::LINE_PRODUCT, BS_SavPropalLine::LINE_FREE)),
        )) as $line) {
            if ((int) $line->pu_ht > 0) {
                if (!(int) $line->getData('out_of_warranty')) {
//                    echo $line->id . ' (' . $line->pu_ht . ')<br/>';
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

//        foreach ($this->getChildrenObjects('propal_lines', array(
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

    public function displayPublicLink()
    {
        return "<a target='_blank' href='" . $this->getPublicLink() . "'><i class='fas fa5-external-link-alt'></i></a>";
    }

    public function getPublicLink()
    {
//        return DOL_MAIN_URL_ROOT . "/bimpsupport/public/page.php?serial=" . $this->getChildObject("equipment")->getData("serial") . "&id_sav=" . $this->id . "&user_name=" . substr($this->getChildObject("client")->dol_object->name, 0, 3);
        return "https://www.bimp.fr/nos-services/?serial=" . urlencode($this->getChildObject("equipment")->getData("serial")) . "&id_sav=" . $this->id . "&user_name=" . urlencode(str_replace(" ", "", substr($this->getChildObject("client")->dol_object->name, 0, 3))) . "#suivi-sav";
    }

    public function sendMsg($msg_type = '')
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

        $client = $this->getChildObject('client');
        if (is_null($client) || !$client->isLoaded()) {
            return array($error_msg . ' (ID du client absent)');
        }

        $centre = $this->getCentreData();
        if (is_null($centre)) {
            return array($error_msg . ' - Centre absent');
        }

        $signature = file_get_contents("https://www.bimp.fr/signatures/v3/supports/sign.php?prenomnom=BIMP%20SAV&job=Centre%20de%20Services%20Agr%C3%A9%C3%A9%20Apple&phone=" . urlencode($centre['tel']), false, stream_context_create(array(
            'http' => array(
                'timeout' => 2   // Timeout in seconds
        ))));

        $propal = $this->getChildObject('propal');

        $tabFile = $tabFile2 = $tabFile3 = array();

        if (!is_null($propal)) {
            if ($propal->isLoaded()) {
                $ref_propal = $propal->getSavedData("ref");
                $fileProp = DOL_DATA_ROOT . "/bimpcore/sav/" . $this->id . "/PC-" . $ref_propal . ".pdf";
                if (is_file($fileProp)) {
                    $tabFile[] = $fileProp;
                    $tabFile2[] = "application/pdf";
                    $tabFile3[] = "PC-" . $ref_propal . ".pdf";
                }

                $fileProp = DOL_DATA_ROOT . "/propale/" . $ref_propal . "/" . $ref_propal . ".pdf";
                if (is_file($fileProp)) {
                    $tabFile[] = $fileProp;
                    $tabFile2[] = "application/pdf";
                    $tabFile3[] = $ref_propal . ".pdf";
                } elseif (in_array((int) $this->getData('status'), self::$need_propal_status)) {
                    $errors[] = 'Attention: PDF du devis non trouvé et donc non envoyé au client File : ' . $fileProp;
                    dol_syslog('SAV "' . $this->getRef() . '" - ID ' . $this->id . ': échec envoi du devis au client ' . print_r($errors, 1), LOG_ERR, 0, "_devissav");
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
            $tech = $user_tech->dol_object->getFullName($langs);
        }

        $textSuivie = "\n <a href='" . $this->getPublicLink() . "'>Vous pouvez suivre l'intervention ici.</a>";



        $subject = '';
        $mail_msg = '';
        $sms = '';
        $nomMachine = $this->getNomMachine();
        $nomCentre = ($centre['label'] ? $centre['label'] : 'N/C');
        $tel = ($centre['tel'] ? $centre['tel'] : 'N/C');
        $fromMail = "SAV BIMP<" . ($centre['mail'] ? $centre['mail'] : 'no-replay@bimp.fr') . ">";

        switch ($msg_type) {
            case 'Facture':
                $facture = null;
                $tabFile = $tabFile2 = $tabFile3 = array();
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
                        $tabFile[] = $fileFact;
                        $tabFile2[] = "application/pdf";
                        $tabFile3[] = $facture->ref . ".pdf";
                    } else {
                        $errors[] = 'Attention: PDF de la facture non trouvé et donc non envoyé au client';
                        dol_syslog('SAV "' . $this->getRef() . '" - ID ' . $this->id . ': échec envoi de la facture au client', LOG_ERR);
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
                    $sms = "Bonjour, nous avons établi votre devis pour votre " . $nomMachine . "\n Vous l'avez reçu par mail.\nL'équipe BIMP";
                }
                break;

            case 'debut':
                $subject = 'Prise en charge ' . $this->getData('ref');
                $mail_msg = "Merci d'avoir choisi BIMP en tant que Centre de Services Agréé Apple.\n";
                $mail_msg .= 'La référence de votre dossier de réparation est : ' . $this->getData('ref') . ", ";
                $mail_msg .= "si vous souhaitez communiquer d'autres informations merci de répondre à ce mail ou de contacter le " . $tel . ".\n";
                $sms = "Merci d'avoir choisi BIMP " . $nomMachine . "\nLa référence de votre dossier de réparation est : " . $this->getData('ref') . "\nL'équipe BIMP";
                break;

            case 'debDiago':
                $subject = "Prise en charge " . $this->getData('ref');
                $mail_msg = "Nous avons commencé le diagnostic de votre \"$nomMachine\", vous aurez rapidement des nouvelles de notre part. ";
                $sms = "Nous avons commencé le diagnostic de votre \" $nomMachine \", vous aurez rapidement des nouvelles de notre part.\nL'équipe BIMP";
                break;

            case 'commOk':
                $subject = 'Commande piece(s) ' . $this->getData('ref');
                $mail_msg = "Nous venons de commander la/les pièce(s) pour votre '" . $nomMachine . "' ou l'échange de votre iPod,iPad,iPhone. ";
                $mail_msg .= "\n Voici nottre diagnostique : " . $this->getData("diagnostic");
                $mail_msg .= "\n Nous restons à votre disposition pour toutes questions au " . $tel;
                $sms = "Bonjour, la pièce/le produit nécessaire à votre réparation vient d'être commandé(e), nous vous contacterons dès réception de celle-ci.\nL'équipe BIMP";
                break;

            case 'repOk':
                $subject = $this->getData('ref') . " Reparation  terminee";
                $mail_msg = "Nous avons le plaisir de vous annoncer que la réparation de votre \"$nomMachine\" est finie.\n";
                $mail_msg .= "Voici ce que nous avons fait : " . $this->getData("resolution") . "\n";
                $mail_msg .= "Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . ", si vous souhaitez plus de renseignements, contactez le " . $tel;
                $sms = "Bonjour, la réparation de votre produit est finie. Vous pouvez le récupérer à " . $nomCentre . " " . $delai . ".\nL'Equipe BIMP.";
                break;

            case 'revPropRefu':
                $subject = "Prise en charge " . $this->getData('ref') . " terminée";
                $mail_msg = "la réparation de votre \"$nomMachine\" est refusée. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . "\n";
                $mail_msg .= "Si vous souhaitez plus de renseignements, contactez le " . $tel;
                $sms = "Bonjour, la réparation de votre \"$nomMachine\"  est refusée. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . ".\nL'Equipe BIMP.";
                break;

            case 'pieceOk':
                $subject = "Pieces recues " . $this->getData('ref');
                $mail_msg = "La pièce/le produit que nous avions commandé pour votre \"$nomMachine\" est arrivé aujourd'hui. Nous allons commencer la réparation de votre appareil.\n";
                $mail_msg .= "Vous serez prévenu dès qu'il sera prêt.";
                $sms = "Bonjour, nous venons de recevoir la pièce ou le produit pour votre réparation, nous vous contacterons quand votre matériel sera prêt.\nL'Equipe BIMP.";
                break;

            case "commercialRefuse":
                $subject = "Devis sav refusé par « " . $client->dol_object->getFullName($langs) . " »";
                $text = "Notre client « " . $client->dol_object->getNomUrl(1) . " » a refusé le devis de réparation sur son « " . $nomMachine . " » pour un montant de «  " . price($propal->dol_object->total) . "€ »";
                $id_user_tech = (int) $this->getData('id_user_tech');
                if ($id_user_tech) {
                    $where = " (SELECT `fk_usergroup` FROM `" . MAIN_DB_PREFIX . "usergroup_user` WHERE `fk_user` = " . $id_user_tech . ") AND `nom` REGEXP 'Sav([0-9])'";
//                    $rows = $this->db->getRows(array('usergroup_extrafields ge', ), "fk_object IN ".$where, null, 'object', array('mail'));

                    $sql = $this->db->db->query("SELECT `mail` FROM " . MAIN_DB_PREFIX . "usergroup_extrafields ge, " . MAIN_DB_PREFIX . "usergroup g WHERE fk_object IN  (SELECT `fk_usergroup` FROM `" . MAIN_DB_PREFIX . "usergroup_user` WHERE ge.fk_object = g.rowid AND `fk_user` = " . $id_user_tech . ") AND `nom` REGEXP 'Sav([0-9])'");

                    $mailOk = false;
                    if ($this->db->db->num_rows($sql) > 0) {
                        while ($ln = $this->db->db->fetch_object($sql)) {
                            if (isset($ln->mail) && $ln->mail != "") {
                                $toMail = str_ireplace("Sav", "Boutique", $ln->mail) . "@bimp.fr";
                                mailSyn2($subject, $toMail, $fromMail, $text);
                                $mailOk = true;
                            }
                        }
                    }

                    if (!$mailOk) {
                        $rows2 = $this->db->getRows('usergroup', "rowid IN " . $where, null, 'object', array('nom'));
                        if (!is_null($rows2)) {
                            foreach ($rows2 as $r) {
                                $toMail = str_ireplace("Sav", "Boutique", $r->nom) . "@bimp.fr";
                                mailSyn2($subject, $toMail, $fromMail, $text);
                            }
                        }
                    }
                }
                break;

            case 'sav_closed':
                break;
        }

        $contact = $this->getChildObject('contact');

        $contact_pref = (int) $this->getData('contact_pref');

        //Perpignan demenagement
        if ($nomCentre == "Perpignan") {
            $mail_msg .= "<br/><br/>Attention le SAV est exceptionnellement fermé les matins  pour cause de travaux jusqu’au 30 septembre.<br/>";
        }

        if ($mail_msg) {
            $toMail = '';

            if ($msg_type === 'Facture' && (int) $client->getData('contact_default')) {
                $fac_contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $client->getData('contact_default'));

                if (BimpObject::objectLoaded($fac_contact)) {
                    $toMail = $fac_contact->getData('email');
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

            $mail_msg .= "\n" . $textSuivie . "\n Cordialement.\n\nL'équipe BIMP\n\n" . $signature;

            $toMail = BimpTools::cleanEmailsStr($toMail);

            if (BimpValidate::isEmail($toMail)) {
                if (!mailSyn2($subject, $toMail, $fromMail, $mail_msg, $tabFile, $tabFile2, $tabFile3)) {
                    $errors[] = 'Echec envoi du mail';
                }
            } else {
                $errors[] = "Pas d'email correct " . $toMail;
            }
        } else {
            $errors[] = 'pas de message';
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

            $sms .= "\n" . $this->getData('ref');
            //$to = "0686691814";
            $fromsms = 'SAV BIMP';

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

        if ($contact_pref === 2) {
            $errors[] = 'Le client a choisi d\'être contacté de préférence par téléphone. Veuillez penser à appeller le client.';
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
        foreach ($this->getChildrenObjects("propal_lines") as $line) {
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
                $lines = $this->getChildrenObjects('propal_lines', array(
                    'type'               => ObjectLine::LINE_PRODUCT,
                    'linked_object_name' => ''
                ));

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
                                            'id_entrepot'        => (int) $this->getData('id_entrepot'),
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
                                        'id_entrepot'        => (int) $this->getData('id_entrepot'),
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
                        $res_errors = $reservation->setNewStatus($status, $qty, $reservation->getData('id_equipment'));
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

    public function updateClient(&$warnings = array(), $id = 0)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if (!$id) {
            $errors[] = 'ID du nouveau client absent';
            return $errors;
        }

        $new_client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id);

        if (!BimpObject::objectLoaded($new_client)) {
            $errors[] = 'Le client d\'ID ' . $id . ' n\'existe pas';
        } else {
            if (!(int) $new_client->getData('status')) {
                $errors[] = 'Ce client est désactivé';
            } elseif (!$new_client->isSolvable($this->object_name, $warnings)) {
                $errors[] = 'Il n\'est pas possible de créer une pièce pour ce client (' . Bimp_Societe::$solvabilites[(int) $new_client->getData('solvabilite_status')]['label'] . ')';
            }
        }

        if (count($errors)) {
            return $errors;
        }

        if ($this->getData("id_facture_acompte") > 0) {
            $fact = $this->getChildObject("facture_acompte");
            $fact->set("fk_soc", $id);
            $errors = $fact->update($warnings, true);
        }

        if ($this->getData("id_discount") > 0 && !count($errors)) {
            $this->db->db->query("UPDATE " . MAIN_DB_PREFIX . "societe_remise_except SET `fk_soc` = " . $id . " WHERE rowid = " . $this->getData("id_discount"));
        }

        if ($this->getData("id_propal") > 0 && !count($errors)) {
            // Mise à jour du client de la propale: 
            $prop = $this->getChildObject("propal");
            $prop->set('fk_soc', (int) $id);
            $w = array();
            $prop_errors = $prop->update($w, true);
            if (count($prop_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($prop_errors, 'Des erreurs sont survenues lors du changement de client du devis');
            }
//            $prop->set("fk_soc", $id);
//            $errors = $prop->updateDolObject($warnings, true);
//            $this->db->db->query("UPDATE ".MAIN_DB_PREFIX."propal SET `fk_soc` = ".$id." WHERE rowid = ".$this->getData("id_propal"));
        }

        // Changement du client pour les prêts:
        $prets = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_Pret', array(
                    'id_sav' => (int) $this->id
        ));
        foreach ($prets as $pret) {
            $pret->set('id_client', (int) $id);
            $w = array();
            $pret_errors = $pret->update($w, true);
            if (count($pret_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($pret_errors, 'Des erreurs sont survenues lors de la mise à jour du prêt "' . $pret->getData('ref') . '"');
            }
        }

        if ($this->getData("id_facture") > 0) {
            $fact = $this->getChildObject("facture");
            $fact->set("fk_soc", $id);
            $errors = $fact->update($warnings, true);
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

        if ((int) BimpCore::getConf('use_gsx_v2')) {
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

    public function onChildDelete($child)
    {
        if (is_a($child, 'BS_ApplePart')) {
            return $this->checkAppleParts();
        }

        return array();
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

            $this->addNote($note);
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
                $this->addNote('Diagnostic commencé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                $this->updateField('id_user_tech', (int) $user->id, null, true);

                if (isset($data['send_msg']) && (int) $data['send_msg']) {
                    $warnings = BimpTools::merge_array($warnings, $this->sendMsg('debDiago'));
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

        define("NOT_VERIF", true);

//        $errors = BimpTools::merge_array($errors, $this->createReservations());

        if (!count($errors)) {
            global $user, $langs;

            $propal->lines_locked = 1;

            $new_status = null;

            if ($this->allGarantie) { // Déterminé par $this->generatePropal()
                $this->addNote('Devis garantie validé auto le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                // Si on vient de commander les pieces sous garentie (On ne change pas le statut)
                if ((int) $this->getData('status') !== self::BS_SAV_ATT_PIECE) {
                    $new_status = self::BS_SAV_DEVIS_ACCEPTE;
                }

                if ($propal->dol_object->valid($user) < 1)
                    $errors[] = "Validation de devis impossible !!!" . BimpTools::getMsgFromArray($propal->dol_object->errors);
                else {
                    $propal->dol_object->cloture($user, 2, "Auto via SAV sous garantie");
                    $propal->fetch($propal->id);
                    $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
                }
            } else {
                $this->addNote('Devis envoyé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                $new_status = self::BS_SAV_ATT_CLIENT;

                if ($propal->dol_object->valid($user) < 1) {
                    $errors[] = "Validation de devis impossible !!!" . BimpTools::getMsgFromArray($propal->dol_object->errors);
                }

                if (!count($errors) && !$propal->dol_object->generateDocument(self::$propal_model_pdf, $langs)) {
                    $errors[] = "Impossible de générer le PDF validation impossible";
                    $propal->dol_object->reopen($user, 0);
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

                if (isset($data['send_msg']) && (int) $data['send_msg']) {
                    $warnings = BimpTools::merge_array($warnings, $this->sendMsg('Devis'));
                }
            }
        }

        if (count($errors)) {
            BimpCore::addlog('Echec validation propale SAV', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcommercial', $this, array(
                'Erreurs' => $errors
            ));
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionPropalAccepted($data, &$success)
    {
        $success = 'Statut du SAV Mis à jour avec succès';

        $errors = $this->setNewStatus(self::BS_SAV_DEVIS_ACCEPTE);

        if (!count($errors)) {
            global $user, $langs;

            $this->addNote('Devis accepté le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
            $propal = $this->getChildObject('propal');
            $propal->dol_object->cloture($user, 2, "Auto via SAV");
            $this->createReservations();
        }

        return array(
            'errors' => $errors
        );
    }

    public function actionPropalRefused($data, &$success)
    {
        $success = 'Statut du SAV Mis à jour avec succès';
        $warnings = array();

        $errors = $this->setNewStatus(self::BS_SAV_DEVIS_REFUSE);

        if (!count($errors)) {
            global $user, $langs;
            $this->addNote('Devis refusé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
            $propal = $this->getChildObject('propal');
            $propal->dol_object->cloture($user, 3, "Auto via SAV");
            $this->removeReservations();
            if (BimpTools::getValue('send_msg', 0))
                $warnings = BimpTools::merge_array($warnings, $this->sendMsg('commercialRefuse'));
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

            $this->addNote('Réparation en cours depuis le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
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
            $errors = $this->sendMsg($data['msg_type']);
        }

        return array('errors' => $errors, 'warnings' => array());
    }

    public function actionToRestitute($data, &$success)
    {
        $success = 'Statut du SAV enregistré avec succès';
        $errors = array();
        $warnings = array();

        $msg_type = '';

        $propal = $this->getChildObject('propal');

        global $user, $langs;

        // Si refus du devis: 
        if ((int) $this->getData('status') === self::BS_SAV_DEVIS_REFUSE) {
            if (is_null($propal) || !$propal->isLoaded()) {
                $errors[] = 'Proposition commerciale absente';
            } else {
                require_once(DOL_DOCUMENT_ROOT . "/bimpcore/classes/BimpRevision.php");

                $old_id_propal = $propal->id;
                $revision = new BimpRevisionPropal($propal->dol_object);
                $new_id_propal = $revision->reviserPropal(array(null, null), true, self::$propal_model_pdf, $errors);

                $this->addNote('Devis fermé après refus par le client le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));

                if ($new_id_propal && !count($errors)) {
                    $client = $this->getChildObject('client');

                    if (is_null($client) || !$client->isLoaded()) {
                        $errors[] = 'Client absent';
                    } else {
                        //Anulation du montant de la propal
                        $totHt = $propal->dol_object->total_ht;
                        $totTtc = $propal->dol_object->total_ttc;
                        if ($totHt == 0)
                            $tTva = 0;
                        else {
                            $tTva = (($totTtc / ($totHt != 0 ? $totHt : 1) - 1) * 100);
                        }
                        $propal->fetch($old_id_propal);

                        $propal->dol_object->statut = 0;
                        $propal->dol_object->addline("Devis refusé", -($totHt) / (100 - $client->dol_object->remise_percent) * 100, 1, $tTva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 1, -1, 0, 0, 0, 0); //-$totPa);

                        $this->set('id_propal', $new_id_propal);
                        $propal->fetch($new_id_propal);

                        $frais = (float) (isset($data['frais']) ? $data['frais'] : 0);
                        $propal->dol_object->addline(
                                "Machine(s) : " . $this->getNomMachine() .
                                "\n" . "Frais de gestion devis refusé.", $frais / 1.20, 1, 20, 0, 0, 3470, $client->dol_object->remise_percent, 'HT', null, null, 1);

                        $propal->fetch($propal->id);
                        $propal->dol_object->valid($user);

                        $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
                        $propal->dol_object->cloture($user, 2, "Auto via SAV");
                        $this->removeReservations();
//                        $apple_part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
//                        $apple_part->deleteBy(array(
//                            'id_sav' => (int) $this->id
//                        ));
                        $msg_type = 'revPropRefu';
                    }
                } else {
                    $errors[] = 'Echec de la fermeture de la proposition commerciale';
                }
            }
        } else {
            if (isset($data['resolution'])) {
                $this->updateField('resolution', (string) $data['resolution'], null, true);
            }
            if ((int) $this->getData('status') !== self::BS_SAV_REP_EN_COURS) {
                $errors[] = 'Statut actuel invalide';
            } elseif ($this->needEquipmentAttribution()) {
                $errors[] = 'Certains produits nécessitent encore l\'attribution d\'un équipement';
            } else {
                if (!(string) $this->getData('resolution')) {
                    $errors[] = 'Le champ "résolution" doit être complété';
                } else {
                    $this->addNote('Réparation terminée le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    $propal->dol_object->cloture($user, 2, "Auto via SAV");
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
                                $warnings[] = BimpTools::getMsgFromArray($rep_errors, 'Echec de la fermeture de la réparation (2) d\'ID ' . $item['id']);
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
                    $warnings = $this->sendMsg($msg_type);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionClose($data, &$success)
    {
        global $user, $langs;
        $errors = array();
        $warnings = array();
        $success = 'SAV Fermé avec succès';
        $success_callback = '';

        $caisse = null;
        $payment_set = (isset($data['paid']) && (float) $data['paid'] && (isset($data['mode_paiement']) && (int) $data['mode_paiement'] > 0 && (int) $data['mode_paiement'] != 56));

//        $prets = $this->getChildrenObjects('prets');
//        foreach ($prets as $pret) {
//            if (!(int) $pret->getData('returned')) {
//                $errors[] = 'Le prêt "' . $pret->getData('ref') . '" n\'est pas restitué';
//            }
//        }

        if ($payment_set) {
            if ($this->useCaisseForPayments) {
                global $user;

                $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
                $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
                if (!$id_caisse) {
                    $errors[] = 'Veuillez vous <a href="' . DOL_URL_ROOT . '/bimpcaisse/index.php" target="_blank">connecter à une caisse</a> pour l\'enregistrement du paiement de la facture';
                } else {
                    $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                    if (!$caisse->isLoaded()) {
                        $errors[] = 'La caisse à laquelle vous êtes connecté est invalide.';
                    } else {
                        $caisse->isValid($errors);
                    }
                }
            }
            $type_paiement = $this->db->getValue('c_paiement', 'code', '`id` = ' . (int) $data['mode_paiement']);
            if ($type_paiement === 'VIR') {
                BimpObject::loadClass('bimpcommercial', 'Bimp_Paiement');
                if (!Bimp_Paiement::canCreateVirement()) {
                    $errors[] = 'Vous n\'avez pas la permission d\'enregistrer des paiements par virement';
                }
            }
        }

        if (count($errors)) {
            return array('errors' => $errors);
        }

        $current_status = (int) $this->getInitData('status');


        if ((int) $this->getData('id_propal')) {
            $propal = $this->getChildObject('propal');

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
                        $id_client = (int) $this->getData('id_client');
                        $id_entrepot = (int) $this->getData('id_entrepot');
//                        $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');
                        $codemove = 'SAV' . $this->id . '_';
                        foreach ($this->getChildrenObjects('propal_lines') as $line) {
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
                                            $eq_line_errors = BimpTools::merge_array($eq_line_errors, $equipment->moveToPlace(BE_Place::BE_PLACE_CLIENT, (int) $id_client, $codemove . 'LN' . $line->id . '_EQ' . (int) $eq_line->getData('id_equipment'), 'Vente ' . $this->getRef(), 1, date('Y-m-d H:i:s'), 'sav', $this->id));
                                            // Création du nouvel emplacement: 
//                                            $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
//                                            if ($id_client) {
//                                                $place_errors = $place->validateArray(array(
//                                                    'id_equipment' => (int) $eq_line->getData('id_equipment'),
//                                                    'type'         => BE_Place::BE_PLACE_CLIENT,
//                                                    'id_client'    => (int) $id_client,
//                                                    'infos'        => 'Vente ' . $this->getRef(),
//                                                    'date'         => date('Y-m-d H:i:s'),
//                                                    'code_mvt'     => $codemove . 'LN' . $line->id . '_EQ' . (int) $eq_line->getData('id_equipment'),
//                                                    'origin'       => 'sav',
//                                                    'id_origin'    => (int) $this->id
//                                                ));
//                                            } else {
//                                                $place_errors = $place->validateArray(array(
//                                                    'id_equipment' => (int) $eq_line->getData('id_equipment'),
//                                                    'type'         => BE_Place::BE_PLACE_FREE,
//                                                    'place_name'   => 'Equipement vendu (client non renseigné)',
//                                                    'infos'        => 'Vente ' . $this->getRef(),
//                                                    'date'         => date('Y-m-d H:i:s'),
//                                                    'code_mvt'     => $codemove . 'LN' . $line->id . '_EQ' . (int) $eq_line->getData('id_equipment'),
//                                                    'origin'       => 'sav',
//                                                    'id_origin'    => (int) $this->id
//                                                ));
//                                            }
//                                            if (!count($place_errors)) {
//                                                $place_warnings = array();
//                                                $place_errors = $place->create($place_warnings, true);
//                                            }
//
//                                            if (count($place_errors)) {
//                                                $equipment = $line->getChildObject('equipment');
//                                                if (BimpObject::objectLoaded($equipment)) {
//                                                    $label = $equipment->getRef();
//                                                } else {
//                                                    $label = 'Erreur: cet équipment n\'existe plus';
//                                                }
//                                                $eq_line_errors[] = BimpTools::getMsgFromArray($place_errors, 'Echec de l\'enregistrement du nouvel emplacement pour le n° de série "' . $label . '"');
//                                            }
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
                                            'id_client'    => (int) $this->getData('id_client'),
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
                            $url = DOL_URL_ROOT . '/bimpsupport/bon_restitution.php?id_sav=' . $this->id;
                        } else {
                            if ((int) $this->getData('id_facture')) {
                                $warnings[] = 'Une facture a déjà été créée pour ce SAV';
                            } else {
                                require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                                global $db;
                                $facture = new Facture($db);

                                if ((int) $propal->dol_object->cond_reglement_id) {
                                    $cond_reglement = (int) $propal->dol_object->cond_reglement_id;
                                } else {
                                    $client = $this->getChildObject('client');

                                    if (BimpObject::objectLoaded($client)) {
                                        $cond_reglement = (int) $client->getData('cond_reglement');
                                    }
                                }

                                $facture->date = dol_now();
                                $facture->source = 0;
                                $facture->socid = (int) $this->getData('id_client');
                                $facture->fk_project = $propal->dol_object->fk_project;
                                $facture->cond_reglement_id = $cond_reglement;
                                $facture->mode_reglement_id = (isset($data['mode_paiement']) ? (int) $data['mode_paiement'] : $propal->dol_object->mode_reglement_id);
                                $facture->availability_id = $propal->dol_object->availability_id;
                                $facture->demand_reason_id = $propal->dol_object->demand_reason_id;
                                $facture->date_livraison = $propal->dol_object->date_livraison;
                                $facture->fk_delivery_address = $propal->dol_object->fk_delivery_address;
                                $facture->contact_id = $propal->dol_object->contact_id;
                                $facture->ref_client = $propal->dol_object->ref_client;
                                $facture->note_private = '';
                                $facture->note_public = '';

                                $facture->origin = $propal->dol_object->element;
                                $facture->origin_id = $propal->id;

                                $facture->fk_account = ((int) $propal->dol_object->fk_account ? $propal->dol_object->fk_account : BimpCore::getConf('bimpcaisse_id_default_account', 0));

                                // get extrafields from original line
                                $propal->dol_object->fetch_optionals($propal->id);

                                foreach ($propal->dol_object->array_options as $options_key => $value)
                                    $facture->array_options[$options_key] = $value;

                                $facture->modelpdf = self::$facture_model_pdf;
                                $facture->array_options['options_type'] = "S";
                                $facture->array_options['options_entrepot'] = (int) $this->getData('id_entrepot');

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
                                                $msg = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($bimpFacture->dol_object), 'Echec de la validation de la facture');
                                                $warnings[] = $msg;
                                                dol_syslog('SAV "' . $this->getRef() . '": ' . $msg, LOG_ERR);
                                            } else {
                                                $bimpFacture->fetch($facture->id);

                                                // Ajout du paiement: 
                                                if ($payment_set) {
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
                                                            $id_account = (int) BimpCore::getConf('bimpcaisse_id_default_account');
                                                        }
                                                        if ($payement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $id_account, '', '') < 0) {
                                                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($payement), 'Echec de l\'ajout du paiement n°' . $payement->id . ' au compte bancaire d\'ID ' . $id_account);
                                                        }

                                                        if ($this->useCaisseForPayments) {
                                                            $warnings = BimpTools::merge_array($warnings, $caisse->addPaiement($payement, $bimpFacture->id));
                                                        }
                                                    }
                                                }

//                                                $to_pay = (float) $bimpFacture->dol_object->total_ttc - ((float) $bimpFacture->dol_object->getSommePaiement() + (float) $bimpFacture->dol_object->getSumCreditNotesUsed() + (float) $bimpFacture->dol_object->getSumDepositsUsed());
//                                                if ($to_pay >= -0.01 && $to_pay <= 0.1) {
//                                                    $bimpFacture->dol_object->set_paid($user);
//                                                }
                                                $bimpFacture->checkIsPaid();

                                                $propal->dol_object->cloture($user, 4, "Auto via SAV");

                                                //Generation
                                                $up_errors = $this->updateField('id_facture', (int) $bimpFacture->id);

                                                if (count($up_errors)) {
                                                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de l\'ID de la facture (' . $bimpFacture->id . ')');
                                                }

                                                $bimpFacture->dol_object->generateDocument(self::$facture_model_pdf, $langs);

                                                $ref = $bimpFacture->getData('facnumber');
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
                                                    $warnings = BimpTools::merge_array($warnings, $this->sendMsg('Facture'));
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
                $this->addNote('Restitué le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
            } else {
                $this->addNote('Fermé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
            }

            if (!count($errors)) {
                $errors = $this->setNewStatus(self::BS_SAV_FERME);
            }

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

        if (count($errors)) {
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
//        foreach ($this->getChildrenObjects('propal_lines', array(
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

            $this->addNote('Attente pièce depuis le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));

            if (isset($data['send_msg']) && (int) $data['send_msg']) {
                $warnings = BimpTools::merge_array($warnings, $this->sendMsg('commOk'));
            }
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
        // Création de la facture d'acompte: 
        $this->updateField('acompte', $data['acompte'], null, true);
        $_POST['mode_paiement_acompte'] = $data['mode_paiement_acompte'];
        if ($this->getData("id_facture_acompte") < 1 && (float) $this->getData('acompte') > 0) {
            $fac_errors = $this->createAccompte((float) $this->getData('acompte'), false);
            if (count($fac_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
            } else
                $success = "Acompte créer avec succés.";
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
                $id_account = (int) BimpCore::getConf('bimpcaisse_id_default_account');
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
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

            $gsx = new GSX_v2();

            $errors = $gsx->setActivationToken($token);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
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
                    $msg = 'Le client sélectionné n\'est pas valide. Veuillez <a href="' . $url . '" target="_blank">corriger</a>';
                    $errors[] = BimpTools::getMsgFromArray($client_errors, $msg);
                }
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
            } elseif (!$client->isSolvable($this->object_name, $warnings)) {
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

        if (!count($errors) && !defined('DONT_CHECK_SERIAL')) {

            // Création de la facture d'acompte: 
            if ($this->getData("id_facture_acompte") < 1 && (float) $this->getData('acompte') > 0) {
                $fac_errors = $this->createAccompte((float) $this->getData('acompte'), false);
                if (count($fac_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
                }
            }

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
                            $place_errors = $place->create();
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

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();

        $centre = $this->getCentreData();


        if ($this->getData("id_facture_acompte") > 0 && (int) $this->getData('id_contact') !== (int) $this->getInitData('id_contact')) {
            $errors[] = 'Facture d\'acompte, impossible de changer de client';
            return $errors;
        }


        if (!count($errors)) {
            $errors = parent::update($warnings, $force_update);
        }


        if (!count($errors)) {
            if ((int) $this->getData('id_client') !== (int) $this->getInitData('id_client')) {
                $errors = $this->updateClient($warnings, $this->getData("id_client"));
            }

            if ((int) $this->getData('id_contact') !== (int) $this->getInitData('id_contact')) {
                //todo gestion des contacts.
            }


            if (!is_null($centre)) {
                $this->set('id_entrepot', (int) $centre['id_entrepot']);
            }


            if ((int) $this->getData('id_propal')) {
                $propal = $this->getChildObject('propal');
                if (BimpObject::objectLoaded($propal)) {
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

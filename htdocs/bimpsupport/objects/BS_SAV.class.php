<?php

require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";
require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

class BS_SAV extends BimpObject
{

    public static $ref_model = 'SAV{CENTRE}{00000}';
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
        self::BS_SAV_NEW               => array('label' => 'Nouveau', 'icon' => 'file-o', 'classes' => array('info')),
        self::BS_SAV_EXAM_EN_COURS     => array('label' => 'Examen en cours', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        self::BS_SAV_ATT_CLIENT_ACTION => array('label' => 'Attente client', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        self::BS_SAV_ATT_CLIENT        => array('label' => 'Attente acceptation client', 'icon' => 'hourglass-start', 'classes' => array('important')),
        self::BS_SAV_DEVIS_ACCEPTE     => array('label' => 'Devis Accepté', 'icon' => 'check', 'classes' => array('success')),
        self::BS_SAV_DEVIS_REFUSE      => array('label' => 'Devis refusé', 'icon' => 'exclamation-circle', 'classes' => array('danger')),
        self::BS_SAV_ATT_PIECE         => array('label' => 'Attente pièce', 'icon' => 'hourglass-start', 'classes' => array('important')),
        self::BS_SAV_REP_EN_COURS      => array('label' => 'Réparation en cours', 'icon' => 'hourglass-start', 'classes' => array('warning')),
        self::BS_SAV_A_RESTITUER       => array('label' => 'A restituer', 'icon' => 'arrow-right', 'classes' => array('success')),
        self::BS_SAV_FERME             => array('label' => 'Fermée', 'icon' => 'times', 'classes' => array('danger'))
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
    public static $systems_cache = null;

    public function __construct($db)
    {
        parent::__construct("bimpsupport", get_class($this));

        $this->useCaisseForPayments = BimpCore::getConf('sav_use_caisse_for_payments');
    }

    // Getters:

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

    public function needEquipmentAttribution()
    {
        if ($this->isLoaded()) {
            $sav_products = $this->getChildrenObjects('products');
            foreach ($sav_products as $sav_product) {
                if (!(int) $sav_product->getData('id_equipment')) {
                    $product = $sav_product->getChildObject('product');
                    if (!is_null($product) && $product->isLoaded()) {
                        if ($product->getData('fk_product_type') === Product::TYPE_PRODUCT &&
                                $product->isSerialisable()) {
                            return 1;
                        }
                    }
                }
            }
        }

        return 0;
    }

    public function getNomUrl($withpicto = true)
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $statut = self::$status_list[$this->data["status"]];
        return "<a href='" . $this->getUrl() . "'>" . '<span class="' . implode(" ", $statut['classes']) . '"><i class="' . BimpRender::renderIconClass($statut['icon']) . ' iconLeft"></i>' . $this->ref . '</span></a>';
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

    public function getUserCentresArray()
    {
        global $tabCentre;

        $centres = array(
            '' => ''
        );

        global $user;
        $userCentres = explode(' ', $user->array_options['options_apple_centre']);

        if (count($userCentres)) {
            foreach ($userCentres as $code) {
                if (preg_match('/^ ?([A-Z]+) ?$/', $code, $matches)) {
                    if (isset($tabCentre[$code])) {
                        $centres[$matches[1]] = $tabCentre[$matches[1]][2];
                    }
                }
            }
        }

        if (count($centres) <= 1) {
            foreach ($tabCentre as $code => $centre) {
                $centres[$code] = $centre[2];
            }
        }

        return $centres;
    }

    public function getCentresArray()
    {
        global $tabCentre;

        $centres = array(
            '' => ''
        );

        foreach ($tabCentre as $code => $centre) {
            $centres[$code] = $centre[2];
        }

        return $centres;
    }

    public function getSystemsArray()
    {
        return array(
            300  => "iOs",
            1013 => "MAC OS 10.13",
            1012 => "MAC OS 10.12",
            1011 => "MAC OS 10.11",
            1010 => "MAC OS 10.10",
            1075 => "MAC OS 10.7.5",
            106  => "MAC OS 10.6",
            107  => "MAC OS 10.7",
            109  => "MAC OS 10.9",
            108  => "MAC OS 10.8",
            9911 => "Windows 10",
            203  => "Windows 8",
            204  => "Windows 7",
            202  => "Windows Vista",
            201  => "Windows XP",
            8801 => "Linux",
            2    => "Indéterminé",
            1    => "Autre"
        );
    }

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
        }

        return $buttons;
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
                return (float) round((float) $facture->getRemainToPay(), 2);
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
            if (!is_null($propal) && $propal_status === 1 && !in_array($status, array(self::BS_SAV_DEVIS_ACCEPTE, self::BS_SAV_DEVIS_REFUSE))) {
                $buttons[] = array(
                    'label'   => 'Devis accepté',
                    'icon'    => 'check',
                    'onclick' => $this->getJsActionOnclick('propalAccepted')
                );

                $buttons[] = array(
                    'label'   => 'Devis refusé',
                    'icon'    => 'times',
                    'onclick' => $this->getJsActionOnclick('propalRefused')
                );
            }

            // Mettre en attente client: 
            if (!in_array($status, array(self::BS_SAV_ATT_CLIENT_ACTION, self::BS_SAV_FERME)) && (is_null($propal) || $propal_status === 0)) {
                $buttons[] = array(
                    'label'   => 'Mettre en attente client',
                    'icon'    => 'hourglass-start',
                    'onclick' => $this->getJsActionOnclick('waitClient', array(), array(
                        'form_name' => 'wait_client'
                    ))
                );
            }

            // Commencer diagnostic: 
            if (in_array($status, array(self::BS_SAV_NEW, self::BS_SAV_ATT_CLIENT_ACTION))) {
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
            if (in_array($status, array(self::BS_SAV_REP_EN_COURS))) {
                $buttons[] = array(
                    'label'   => 'Réparation terminée',
                    'icon'    => 'check',
                    'onclick' => $this->getJsActionOnclick('toRestitute', array(), array('form_name' => 'resolution'))
                );
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

            // Restituer (payer) 
            if (in_array($status, array(self::BS_SAV_A_RESTITUER))) {
                if (!is_null($propal)) {
                    $buttons[] = array(
                        'label'   => 'Restituer (Payer)',
                        'icon'    => 'times-circle',
                        'onclick' => $this->getJsActionOnclick('close', array('restitute' => 1), array(
                            'form_name' => 'restitute'
                        ))
                    );
                }
            }

            // Restituer
            if (is_null($propal) && $status !== self::BS_SAV_FERME) {
                $buttons[] = array(
                    'label'   => 'Restituer',
                    'icon'    => 'times-circle',
                    'onclick' => $this->getJsActionOnclick('close', array('restitute' => 1))
                );
            }

            //Générer devis 
            if (!is_null($propal) && $propal_status === 0 && $status !== self::BS_SAV_FERME) {
                $buttons[] = array(
                    'label'   => 'Générer devis',
                    'icon'    => 'cogs',
                    'onclick' => $this->getJsActionOnclick('generatePropal', array(), array(
                        'confirm_msg' => "Attention, la proposition commerciale va être entièrement générée à partir des données du SAV.\\nTous les enregistrements faits depuis la fiche propale ne seront pas pris en compte"
                    ))
                );
            }

            // Attribuer un équipement
            if ($this->needEquipmentAttribution()) {
                $buttons[] = array(
                    'label'   => 'Attribuer un équipement',
                    'icon'    => 'arrow-circle-right',
                    'onclick' => $this->getJsActionOnclick('attibuteEquipment', array(), array('form_name' => 'equipment'))
                );
            }

            // Créer Devis 
            if (is_null($propal) && $status < 999) {
                $buttons[] = array(
                    'label'   => 'Créer Devis',
                    'icon'    => 'plus-circle',
                    'onclick' => 'createNewPropal($(this), ' . $this->id . ');'
                );
            }

            // Réviser devis:  
            if (in_array($status, self::$propal_reviewable_status)) {
                if (!is_null($propal_status) && $propal_status > 0) {
                    $callback = 'function() {window.location.reload();}';
                    $buttons[] = array(
                        'label'   => 'Réviser Devis',
                        'icon'    => 'edit',
                        'onclick' => $this->getJsActionOnclick('reviewPropal', array(), array(
                            'success_callback' => $callback,
                            'confirm_msg'      => 'Veuillez confirmer la révision du devis'
                        ))
                    );
                }
            }

            // Envoyer devis: 
            if (!is_null($propal) && $propal_status === 0 && !in_array($status, array(self::BS_SAV_ATT_CLIENT_ACTION))) {
                $callback = 'function() {window.location.reload();}';
                $buttons[] = array(
                    'label'   => 'Envoyer devis',
                    'icon'    => 'arrow-circle-right',
                    'onclick' => $this->getJsActionOnclick('validatePropal', array(), array(
                        'form_name'        => 'validate_propal',
                        'success_callback' => $callback
                    ))
                );
            }

            // Payer facture: 
            if ((int) $this->getData('id_facture')) {
                $facture = $this->getChildObject('facture');
                if (!(int) $facture->dol_object->paye) {
                    $paiement = BimpObject::getInstance('bimpcore', 'Bimp_Paiement');
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

        return $buttons;
    }

    // Affichage:

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
    
    public function displayEquipment(){
        $return = "";
        $equipement = $this->getChildObject('equipment');
        if($equipement->getData("product_label") != "")
            $return .= $equipement->getData("product_label")."<br/>";
        $return .= $equipement->getData("serial")."<br/>";
        $return .= $equipement->getData("warranty_type");
        return $return;
    }
    
    public function displayExtraSav(){
        $equip = $this->getChildObject("equipment");
        $savS = BimpObject::getInstance('bimpsupport', 'BS_SAV');
        $list = $savS->getList(array('id_equipment' => $equip->id));
        foreach($list as $arr){
            if($arr['id'] != $this->id){
                $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
                $sav->fetch($arr['id']);
                $return .= $sav->getNomUrl()."<br/>";
            }
        }
        
        
        $repairS = BimpObject::getInstance('bimpapple', 'GSX_Repair');
        $list = $repairS->getList(array('id_sav' => $this->id));
        foreach($list as $arr){
            $reapir = BimpObject::getInstance('bimpapple', 'GSX_Repair');
            $return .= "<a href='#gsx'>".$arr['repair_confirm_number']."</a><br/>";
        }
        
        return $return;
    }
    
    public function getEquipementSearchFilters(&$filters, $value)
    {
        $filters['or_equipment'] = array(
            'or' => array(
                'e.serial'         => array(
                    'part_type' => 'middle', // ou middle ou end
                    'part'      => $value
                ),
                'e.product_label'     => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                'e.warranty_type'     => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                // etc...
            )
        );
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

    // Rendus HTML: 

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
                            'success_callback' => 'function() {window.location.reload();}'
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

    // Traitements:

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
                    $errors[] = $error_msg . ' (statut actuel invalide : '.$current_status.')';
                }
                break;

            case self::BS_SAV_ATT_CLIENT:
                if (is_null($propal)) {
                    $errors[] = $error_msg . ' (Proposition commerciale absente)';
                } elseif ($propal_status !== 0) {
                    $errors[] = $error_msg . ' (statut de la proposition commerciale invalide)';
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
            if ($msg_type && $extra_data['send_msg'] || $msg_type === 'commercialRefuse') {
                $warnings = array_merge($warnings, $this->sendMsg($msg_type));
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
                if (!$caisse->fetch($id_caisse)) {
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
            $factureA->modelpdf = self::$facture_model_pdf;
            $factureA->array_options['options_type'] = "S";
            $factureA->array_options['options_entrepot'] = $this->getData('id_entrepot');
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
                        $errors = array_merge($errors, $caisse->addPaiement($payement, $factureA->id));
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

                $this->update();

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

        $id_client = (int) $this->getData('id_client');
        if (!$id_client) {
            $errors[] = 'Aucun client sélectionné pour ce SAV';
        }

        $id_contact = (int) $this->getData('id_contact');

        if (!count($errors)) {
            global $user, $langs;

            BimpTools::loadDolClass('comm/propal', 'propal');
            $prop = new Propal($this->db->db);
            $prop->modelpdf = self::$propal_model_pdf;
            $prop->socid = $id_client;
            $prop->date = dol_now();
            $prop->cond_reglement_id = 0;
            $prop->mode_reglement_id = 0;
            

            $prop->array_options['options_type'] = "S";
            $prop->array_options['options_entrepot'] = $this->getData("id_entrepot");
            $prop->array_options['options_libelle'] = $this->getData("ref");

            if ($prop->create($user) <= 0) {
                $errors[] = 'Echec de la création de la propale';
                BimpTools::getErrorsFromDolObject($prop, $errors, $langs);
            } else {
                if ($id_contact) {
                    $prop->add_contact($id_contact, 40);
                    $prop->add_contact($id_contact, 41);
                }

                $this->set('id_propal', $prop->id);

                if ($this->getData("id_facture_acompte"))
                    addElementElement("propal", "facture", $prop->id, $this->getData("id_facture_acompte"));

                if ($update)
                    $this->update();
            }
        }

        return $errors;
    }

    public function generatePropal()
    {
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

        if ($prop->statut > 0) {
            return array('Cette propale doit être révisée pour pouvoir être re-générée');
        }

        $prop->fetch_lines();
        foreach ($prop->lines as $line) {
            $line->delete();
        }

        define("NOT_VERIF", true);

        if ($this->getData('id_discount') > 0) {
            BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
            $discount = new DiscountAbsolute($this->db->db);
            $discount->fetch($this->getData('id_discount'));
            $prop->addline("Acompte", -$discount->amount_ht, 1, 20, 0, 0, 0, 0, 'HT', -($discount->amount_ttc), 0, 1, 0, 0, 0, 0, -$discount->amount_ht, null, null, null, null, null, null, null, null, $discount->id);
        }

        $ref = $this->getData('ref');
        $equipment = $this->getChildObject('equipment');
        $serial = 'N/C';
        if (!is_null($equipment) && $equipment->isLoaded()) {
            $serial = $equipment->getData('serial');
        }

//        $prop->addline("Prise en charge :  : " . $ref .
//                "\n" . "S/N : " . $serial .
//                "\n" . "Garantie :
//Pour du matériel couvert par Apple, la garantie initiale s'applique.
//Pour du matériel non couvert par Apple, la garantie est de 3 mois pour les pièces et la main d'oeuvre.
//Les pannes logicielles ne sont pas couvertes par la garantie du fabricant.
//Une garantie de 30 jours est appliquée pour les réparations logicielles.
//", 0, 1, 0, 0, 0, 0, (!is_null($client) ? $client->dol_object->remise_percent : 0), 'HT', 0, 0, 3);


        // Ajout du service prioritaire:
        if ((int) $this->getData('prioritaire')) {
            require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
            $prodF = new ProductFournisseur($this->db->db);
            $prodF->fetch(self::$idProdPrio);
            $prodF->tva_tx = ($prodF->tva_tx > 0) ? $prodF->tva_tx : 0;
            $prodF->find_min_price_product_fournisseur($prodF->id, 1);

            $prop->addline($prodF->description, $prodF->price, 1, $prodF->tva_tx, 0, 0, $prodF->id, 0, 'HT', null, null, null, null, null, null, $prodF->product_fourn_price_id, $prodF->fourn_price);
        }

        //Ajout diagnostique
        if ($this->getData('diagnostic') != "") {
            $prop->addline("Diagnostic : " . $this->getData('diagnostic'), 0, 1, 0, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 3);
        }

        $garantieHt = $garantieTtc = $garantiePa = 0;

        //Ajout des prod apple
        foreach ($this->getChildrenObjects("products") as $prod) {
            $prodG = new Product($this->db->db);
            $prodG->fetch($prod->getData("id_product"));
            $remise = $prod->getData("remise");
            $coefRemise = (100 - $remise) / 100;
            require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
            $prodF = new ProductFournisseur($this->db->db);
            $prodF->find_min_price_product_fournisseur($prodG->id, $prod->getData("qty"));
            $prop->addline($prodG->description, $prodG->price, $prod->getData("qty"), $prodG->tva_tx, 0, 0, $prodG->id, $client->dol_object->remise_percent + $remise, 'HT', null, null, null, null, null, null, $prodF->product_fourn_price_id, $prodF->fourn_price);
            if (!$prod->getData("out_of_warranty")) {
                $garantieHt += $prodG->price * $prod->getData("qty") * $coefRemise;
                $garantieTtc += $prodG->price * $prod->getData("qty") * ($prodG->tva_tx / 100) * $coefRemise;
                $garantiePa += $prodF->fourn_price * $prod->getData("qty");
            } else
                $this->allGarantie = false;
        }

        //Ajout des Prod non Apple

        foreach ($this->getChildrenObjects("apple_parts") as $prod) {
            $tva = 20;
            $price = ($prod->getData("no_order") || $prod->getData("exchange_price") < 1) ? $prod->getData("stock_price") : $prod->getData("exchange_price");
            $price2 = BS_ApplePart::convertPrix($price, $prod->getData("part_number"), $prod->getData("label"));
            $label = $prod->getData("part_number") . " - " . $prod->getData("label");
            $label .= ($prod->getData("no_order")) ? " APPRO" : "";
            $prop->addline($label, $price2, $prod->getData("qty"), $tva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', null, null, null, null, null, null, null, $price);
            if (!$prod->getData("out_of_warranty")) {
                $garantieHt += $price2 * $prod->getData("qty");
                $garantieTtc += $price2 * $prod->getData("qty") * ($tva / 100);
                $garantiePa += $price * $prod->getData("qty");
            } else
                $this->allGarantie = false;
        }

        //Ajout garantie
        if ($garantieHt > 0) {
            $tva = 100 * $garantieTtc / $garantieHt;
            $prop->addline("Garantie", -($garantieHt), 1, $tva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 1, -1, 0, 0, 0, -$garantiePa);
        }

        // Ajout infos supplémentaires:
        if ($this->getData('extra_infos') != "") {
            $prop->addline($this->getData('extra_infos'), 0, 1, 0, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 3);
        }

        require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
        $prop->fetch($prop->id);
        $prop->generateDocument(self::$propal_model_pdf, $langs);

        return $errors;
    }

    public function sendMsg($msg_type = '')
    {
        global $langs;

        $errors = array();
        $error_msg = 'Echec de l\'envoi de la notification au client';

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

//    $signature = '<div id="signature_Bimp"><div style="font-family: Arial, sans-serif; font-size: 13px;"><div style="margin: 0 0 8px 0;"><table border="0"><tbody><tr valign="middle"><td><a href="http://www.bimp.fr/" target="_blank"><img alt="" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/bimpcomputer.png"></a></td><td style="text-align: left;"><span style="font-size: large;"><span style="color: #181818;"><strong class="text-color theme-font">BIMP SAV</strong><span style="color: #181818;"> </span></span></span><br><div style="margin-bottom: 0px; margin-top: 8px;"><span style="font-size: medium;"><span style="color: #cb6c09;"></span></span></div><div style="margin-bottom: 0px; margin-top: 0px;"><span style="font-size: medium;"><span style="color: #808080;">Centre de Services Agrées Apple</span></span></div><div style="color: #828282; font: 13px Arial; margin-top: 10px; text-transform: none;"><a href="https://plus.google.com/+BimpFr/posts" style="text-decoration: underline;"><img alt="Google Plus Page" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/googlepluspage.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://twitter.com/BimpComputer" style="text-decoration: underline;"><img alt="Twitter" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/twitter.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://www.linkedin.com/company/bimp" style="text-decoration: underline;"><img alt="LinkedIn" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/linkedin.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="http://www.viadeo.com/fr/company/bimp" style="text-decoration: underline;"><img alt="Viadeo" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/viadeo.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://www.facebook.com/bimpcomputer" style="text-decoration: underline;"><img alt="Facebook" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/facebook.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a><br></div></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td><a href="http://bit.ly/1MsmSB8"><img moz-do-not-send="true" src="http://www.bimp.fr/emailing/signatures/evenementiel2.png" alt=""></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td><img alt="" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/pictoarbre.png"></td><td style="text-align: left;"><span style="font-size: small;"><span style="color: #009933;"> Merci de n\'imprimer cet e-mail que si nécessaire</span></span></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td style="text-align: justify;"><span style="font-size: small;"><span style="color: #888888;"><small>Ce message et éventuellement les pièces jointes, sont exclusivement transmis à l\'usage de leur destinataire et leur contenu est strictement confidentiel. Une quelconque copie, retransmission, diffusion ou autre usage, ainsi que toute utilisation par des personnes physiques ou morales ou entités autres que le destinataire sont formellement interdits. Si vous recevez ce message par erreur, merci de le détruire et d\'en avertir immédiatement l\'expéditeur. L\'Internet ne permettant pas d\'assurer l\'intégrité de ce message, l\'expéditeur décline toute responsabilité au cas où il aurait été intercepté ou modifié par quiconque.<br> This electronic message and possibly any attachment are transmitted for the exclusive use of their addressee; their content is strictly confidential. Any copy, forward, release or any other use, is prohibited, as well as any use by any unauthorized individual or legal entity. Should you receive this message by mistake, please delete it and notify the sender at once. Because of the nature of the Internet the sender is not in a position to ensure the integrity of this message, therefore the sender disclaims any liability whatsoever, in the event of this message having been intercepted and/or altered.</small></span> </span></td></tr></tbody></table></div></div></div>';
        $signature = file_get_contents("http://bimp.fr/emailing/signatures/signevenementiel2.php?prenomnom=BIMP%20SAV&adresse=Centre%20de%20Services%20Agr%C3%A9%C3%A9%20Apple");

        $propal = $this->getChildObject('propal');

        $tabFile = $tabFile2 = $tabFile3 = array();

        if (!is_null($propal)) {
            if ($propal->isLoaded()) {
                $fileProp = DOL_DATA_ROOT . "/bimpcore/sav/" . $this->id . "/PC-" . $this->getData('ref') . ".pdf";
                if (is_file($fileProp)) {
                    $tabFile[] = $fileProp;
                    $tabFile2[] = "application/pdf";
                    $tabFile3[] = "PC-" . $this->getData('ref') . ".pdf";
                }

                $fileProp = DOL_DATA_ROOT . "/propale/" . $propal->dol_object->ref . "/" . $propal->dol_object->ref . ".pdf";
                if (is_file($fileProp)) {
                    $tabFile[] = $fileProp;
                    $tabFile2[] = "application/pdf";
                    $tabFile3[] = $propal->dol_object->ref . ".pdf";
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

        $textSuivie = "\n <a href='" . DOL_MAIN_URL_ROOT . "/bimpsupport/public/page.php?serial=" . $this->getChildObject("equipment")->getData("serial") . "&id_sav=" . $this->id . "&user_name=" . substr($this->getChildObject("client")->dol_object->name, 0, 3) . "'>Vous pouvez suivre l'intervention ici.</a>";


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
                        $tabFile2[] = "application/pdf";
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
                $sms = "Bonjour, nous avons le plaisir de vous annoncer que le diagnostic de votre \"" . $nomMachine . "\" commence, nous vous recontacterons quand celui-ci sera fini.\nL'équipe BIMP";
                break;

            case 'debDiago':
                $subject = "Prise en charge " . $this->getData('ref');
                $mail_msg = "Nous avons commencé le diagnostic de votre \"$nomMachine\", vous aurez rapidement des nouvelles de notre part. ";
                $sms = "Nous avons commencé le diagnostic de votre \" $nomMachine \", vous aurez rapidement des nouvelles de notre part.\nVotre centre de services Apple.";
                break;

            case 'commOk':
                $subject = 'Commande piece(s) ' . $this->getData('ref');
                $mail_msg = "Nous venons de commander la/les pièce(s) pour votre '" . $nomMachine . "' ou l'échange de votre iPod,iPad,iPhone. Nous restons à votre disposition pour toutes questions au " . $tel;
                $sms = "Bonjour, la pièce/le produit nécessaire à votre réparation vient d'être commandé(e), nous vous contacterons dès réception de celle-ci.\nL'équipe BIMP.";
                break;

            case 'repOk':
                $subject = $this->getData('ref') . " Reparation  terminee";
                $mail_msg = "Nous avons le plaisir de vous annoncer que la réparation de votre \"$nomMachine\" est finie.\n";
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

        if ($mail_msg) {
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

        if (!in_array($file_type, array('pc', 'destruction', 'destruction2', 'pret'))) {
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

        $errors = array_merge($errors, bimpsupport_pdf_create($this->db->db, $this, 'sav', $file_type));

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

    public function setAllStatutWarranty($garantie = false)
    {
        foreach ($this->getChildrenObjects("products") as $prod) {
            $prod->set("out_of_warranty", $garantie ? "0" : "1");
            $prod->update();
        }
        foreach ($this->getChildrenObjects("apple_parts") as $prod) {
            $prod->set("out_of_warranty", $garantie ? "0" : "1");
            $prod->update();
        }
    }

    public function createReservations()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $errors = $this->removeReservations();
            if (!count($errors)) {
                $error_msg = 'Echec de la création de la réservation pour le produit';
                $sav_products = $this->getChildrenObjects('products');
                $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

                foreach ($sav_products as $sav_product) {
                    if (!is_null($sav_product) && $sav_product->isLoaded()) {
                        $product = $sav_product->getChildObject('product');
                        if (!is_null($product) && $product->isLoaded() && (int) $product->getData('fk_product_type') === Product::TYPE_PRODUCT) {
                            $reservation->reset();
                            $prod_errors = $reservation->validateArray(array(
                                'id_sav'         => (int) $this->id,
                                'id_sav_product' => (int) $sav_product->id,
                                'id_entrepot'    => (int) $this->getData('id_entrepot'),
                                'id_product'     => (int) $product->id,
                                'id_equipment'   => (int) $sav_product->getData('id_equipment'),
                                'type'           => BR_Reservation::BR_RESERVATION_SAV,
                                'status'         => 203,
                                'id_commercial'  => (int) $this->getData('id_user_tech'),
                                'id_client'      => (int) $this->getData('id_client'),
                                'qty'            => (int) $sav_product->getData('qty'),
                                'date_from'      => date('Y-m-d H:i:s')
                            ));
                            if (!count($prod_errors)) {
                                $prod_errors = $reservation->create();
                            }

                            if (count($prod_errors)) {
                                $errors[] = $error_msg .= ' "' . $product->id . '"';
                                $errors = array_merge($errors, $prod_errors);
                            } else {
                                $sav_product->set('id_reservation', $reservation->id);
                                $errors = array_merge($errors, $sav_product->update());
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

            $this->db->update('bs_sav_product', array(
                'id_reservation' => 0
                    ), '`id_sav` = ' . (int) $this->id);
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
                $sav_product = BimpObject::getInstance('bimpsupport', 'BS_SavProduct');

                foreach ($list as $item) {
                    if ($reservation->fetch((int) $item['id'])) {
                        $qty = null;
                        $id_equipment = null;
                        $reservation->set('status', $status);
                        if ($reservation->isProductSerialisable()) {
                            $qty = 1;
                            if (!$sav_product->find(array(
                                        'id_reservation' => (int) $reservation->id
                                    ))) {
                                $errors[] = 'Produit du sav non trouvé pour la réservation "' . $reservation->getData('ref') . '"';
                                continue;
                            }

                            $id_equipment = (int) $sav_product->getData('id_equipment');
                            if (!$id_equipment) {
                                $prod = $sav_product->getChildObject('product');
                                if (!is_null($prod) && $prod->isLoaded()) {
                                    $prod_label = $prod->getData('ref') . ' - ' . $prod->getData('label');
                                } else {
                                    $prod_label = 'inconnu';
                                }
                                $errors[] = 'Attribution d\'un équipement obligatoire pour le produit "' . $prod_label . '"';
                                continue;
                            }
                        }

                        $res_errors = $reservation->setNewStatus($status, $qty, $id_equipment);
                        if (!count($res_errors)) {
                            $res_errors = $reservation->update();
                        }
                        if (count($res_errors)) {
                            $errors[] = 'Echec de la mise à jour du statut pour la réservation "' . $reservation->getData('ref') . '"';
                            $errors = array_merge($errors, $res_errors);
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
                $this->updateField('id_user_tech', (int) $user->id);

                if (isset($data['send_msg']) && (int) $data['send_msg']) {
                    $warnings = array_merge($warnings, $this->sendMsg('debDiago'));
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGeneratePropal($data, &$success)
    {
        $errors = array();

        $success = 'Devis généré avec succès';

        $errors = $this->generatePropal();

        return $errors;
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
        } elseif (!isset($data['generate_propal']) || (int) $data['generate_propal']) {
            $errors = $this->generatePropal();
        }
        else{
            $this->allGarantie = (is_object($propal) && $propal->dol_object->total_ttc <= 0);
        }
        
        define("NOT_VERIF", true);

        $errors = array_merge($errors, $this->createReservations());

        if (!count($errors)) {
            global $user, $langs;



            $new_status = null;
            if ($this->allGarantie) { // Déterminé par $this->generatePropal()
                $this->addNote('Devis garantie validé auto le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                // Si on vient de commander les pieces sous garentie (On ne change pas le statut)
                if ((int) $this->getData('status') !== self::BS_SAV_ATT_PIECE) {
                    $new_status = self::BS_SAV_DEVIS_ACCEPTE;
                }

                $propal->dol_object->valid($user);
                $propal->dol_object->cloture($user, 2, "Auto via SAV sous garentie");
                $propal->fetch($propal->id);
                $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
            } else {
                $this->addNote('Devis envoyé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                $new_status = self::BS_SAV_ATT_CLIENT;
                $propal->dol_object->valid($user);
                $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
            }

            if (!is_null($new_status)) {
                $errors = $this->setNewStatus($new_status);
            }

            if (!(int) $this->getData('id_user_tech')) {
                $this->updateField('id_user_tech', (int) $user->id);
            }

            if (isset($data['send_msg']) && (int) $data['send_msg']) {
                $warnings = array_merge($warnings, $this->sendMsg('Devis'));
            }
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

        $errors = $this->setNewStatus(self::BS_SAV_DEVIS_REFUSE);

        if (!count($errors)) {
            global $user, $langs;
            $this->addNote('Devis refusé le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
            $propal = $this->getChildObject('propal');
            $propal->dol_object->cloture($user, 3, "Auto via SAV");
            $this->removeReservations();
            $warnings = array_merge($warnings, $this->sendMsg('commercialRefuse'));
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
                $new_id_propal = $revision->reviserPropal(array(null, null), true, self::$propal_model_pdf, $errors);

                if ($new_id_propal && !count($errors)) {
                    //Anulation du montant de la propal
                    $totHt = $propal->dol_object->total_ht;
                    if ($totHt == 0)
                        $tTva = 0;
                    else {
                        $tTva = (($propal->dol_object->total_ttc / ($totHt != 0 ? $totHt : 1) - 1) * 100);
                    }
                    $propal->fetch($old_id_propal);
                    $propal->dol_object->statut = 0;
                    $propal->dol_object->addline("Devis révisé", -($totHt) / (100 - $client->dol_object->remise_percent) * 100, 1, $tTva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 1, -1, 0, 0, 0, 0); //-$totPa);

                    $this->set('id_propal', $new_id_propal);
                    $this->update();
                    $errors = array_merge($errors, $this->setNewStatus(self::BS_SAV_EXAM_EN_COURS));
                    global $user, $langs;
                    $this->addNote('Devis mis en révision le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    $warnings = array_merge($warnings, $this->removeReservations());
                } else {
                    $errors[] = 'Echec de la mise révision du devis';
                }
            } else {
                $errors[] = 'Le devis n\'a pas besoin d\'être révisé car il est toujours au statut "Brouillon"';
            }
        }

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

        return $errors;
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
                        $propal->dol_object->generateDocument(self::$propal_model_pdf, $langs);
                        $propal->dol_object->cloture($user, 2, "Auto via SAV");
                        $this->removeReservations();
                        $sav_product = BimpObject::getInstance('bimpsupport', 'BS_SavProduct');
                        $sav_product->deleteBy(array(
                            'id_sav' => (int) $this->id
                        ));
                        $apple_part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
                        $apple_part->deleteBy(array(
                            'id_sav' => (int) $this->id
                        ));
                        $msg_type = 'revPropRefu';
                    }
                } else {
                    $errors[] = 'Echec de la fermeture de la proposition commerciale';
                }
            }
        } else {
            if (isset($data['resolution'])) {
                $this->set('resolution', (string) $data['resolution']);
                $this->update();
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
                            if ($repair->fetch((int) $item['id'])) {
                                $rep_errors = $repair->updateStatus();
                            } else {
                                $rep_errors = array('Réparation d\'id ' . $item['id'] . ' non trouvée');
                            }
                            if (count($rep_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($rep_errors, 'Echec de la fermeture de la réparation d\'ID ' . $item['id']);
                            }
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            $errors = $this->setNewStatus(self::BS_SAV_A_RESTITUER);

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
        $caisse = null;
        $payment_set = (isset($data['paid']) && (float) $data['paid'] && (isset($data['mode_paiement']) && (int) $data['mode_paiement'] > 0 && (int) $data['mode_paiement'] != 56));

        $prets = $this->getChildrenObjects('prets');
        foreach ($prets as $pret) {
            if (!(int) $pret->getData('returned')) {
                $errors[] = 'Le prêt "' . $pret->getData('ref') . '" n\'est pas restitué';
            }
        }

        if (count($errors)) {
            return array(BimpTools::getMsgFromArray($errors, 'Il n\'est pas possible de fermer ce SAV:'));
        }

        if ($this->useCaisseForPayments && $payment_set) {
            global $user;

            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
            if (!$id_caisse) {
                $errors[] = 'Veuillez-vous <a href="' . DOL_URL_ROOT . '/bimpcaisse/index.php" target="_blank">connecter à une caisse</a> pour l\'enregistrement du paiement de la facture';
            } else {
                if (!$caisse->fetch($id_caisse)) {
                    $errors[] = 'La caisse à laquelle vous êtes connecté est invalide.';
                } else {
                    $caisse->isValid($errors);
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $success = 'SAV Fermé avec succès';
        $current_status = (int) $this->getSavedData('status');
        $warnings = array();

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
                        $warnings[] = BimpTools::getMsgFromArray($res_errors, 'Des erreurs sont survenues lors de la mise à jour des réservations de produits:');
                    }

                    if (!count($errors)) {
                        // Gestion des stocks et emplacements: 
                        $id_client = (int) $this->getData('id_client');
                        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                        $id_entrepot = (int) $this->getData('id_entrepot');
                        $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');
                        foreach ($this->getChildrenObjects('products') as $sav_product) {
                            $product = $sav_product->getChildObject('product');

                            if (BimpObject::objectLoaded($product) && (int) $product->getData('fk_product_type') === Product::TYPE_PRODUCT) {
                                if ($product->isSerialisable()) {
                                    $error_msg = 'Echec de la mise à jour de l\'emplacement pour le produit "' . $product->getData('ref') . ' - ' . $product->getData('label') . '"';
                                    if (!$sav_product->getData('id_equipment')) {
                                        $warnings[] = $error_msg . ' - Equipement non attribué';
                                    } else {
                                        // Création du nouvel emplacement: 
                                        $place->reset();
                                        if ($id_client) {
                                            $place_errors = $place->validateArray(array(
                                                'id_equipment' => (int) $sav_product->getData('id_equipment'),
                                                'type'         => BE_Place::BE_PLACE_CLIENT,
                                                'id_client'    => (int) $id_client,
                                                'infos'        => 'Vente SAV',
                                                'date'         => date('Y-m-d H:i:s')
                                            ));
                                        } else {
                                            $place_errors = $place->validateArray(array(
                                                'id_equipment' => (int) $sav_product->getData('id_equipment'),
                                                'type'         => BE_Place::BE_PLACE_FREE,
                                                'place_name'   => 'Equipement vendu (client non renseigné)',
                                                'infos'        => 'Vente SAV',
                                                'date'         => date('Y-m-d H:i:s')
                                            ));
                                        }
                                        if (!count($place_errors)) {
                                            $place_errors = $place->create();
                                        }

                                        if (count($place_errors)) {
                                            $equipment = $sav_product->getChildObject('equipment');
                                            if (BimpObject::objectLoaded($equipment)) {
                                                $label = $equipment->getRef();
                                            } else {
                                                $label = 'Erreur: cet équipment n\'existe plus';
                                            }
                                            $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de l\'enregistrement du nouvel emplacement pour le n° de série "' . $label . '"');
                                        }
                                    }
                                } else {
                                    $result = $product->dol_object->correct_stock($user, $id_entrepot, (int) $sav_product->getData('qty'), 1, $this->getRef(), 0, $codemove);
                                    if ($result < 0) {
                                        $warnings[] = 'Echec de la mise à jour du stock pour le produit "' . $product->getData('label') . '" (Ref: "' . $product->etRef() . '")';
                                        if (count($product->dol_object->errors)) {
                                            $warnings = array_merge($warnings, $product->dol_object->errors);
                                        } elseif ($product->dol_object->error) {
                                            $warnings[] = $product->dol_object->error;
                                        }
                                    }
                                }
                            }
                        }

                        if ((int) $this->getData('id_equipment')) {
                            $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                            $place_errors = $place->validateArray(array(
                                'id_equipment' => (int) $this->getData('id_equipment'),
                                'type'         => BE_Place::BE_PLACE_CLIENT,
                                'id_client'    => (int) $this->getData('id_client'),
                                'infos'        => 'Restitution ' . $this->getData('ref'),
                                'date'         => date('Y-m-d H:i:s')
                            ));
                            if (!count($place_errors)) {
                                $place_errors = $place->create();
                            }

                            if (count($place_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de l\'enregistrement du nouvel emplacement pour l\'équipement de ce SAV');
                            }
                        }

                        // Création de la facture:
                        BimpTools::loadDolClass('compta/facture', 'facture');
                        $facture = new Facture($this->db->db);
                        $facture->modelpdf = self::$facture_model_pdf;
                        $facture->array_options['options_type'] = "S";
                        $facture->array_options['options_entrepot'] = $this->getData('id_entrepot');
                        $facture->createFromOrder($propal->dol_object);
                        $facture->addline("Résolution : " . $this->getData('resolution'), 0, 1, 0, 0, 0, 0, 0, null, null, null, null, null, 'HT', 0, 3);
                        $facture->validate($user, ''); //pas d'entrepot pour pas de destock
                        $facture->fetch($facture->id);

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
                                    $warnings = array_merge($warnings, $caisse->addPaiement($payement, $facture->id));
                                }
                            }
                        }

                        $to_pay = (float) $facture->total_ttc - ((float) $facture->getSommePaiement() + (float) $facture->getSumCreditNotesUsed() + (float) $facture->getSumDepositsUsed());
                        if ($to_pay >= -0.01 && $to_pay <= 0.1) {
                            $facture->set_paid($user);
                        }

                        $propal->dol_object->cloture($user, 4, "Auto via SAV");

                        //Generation
                        $facture->fetch($facture->id);

                        $this->set('id_facture', $facture->id);
                        $up_errors = $this->update();
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de l\'ID de la facture (' . $facture->id . ')');
                        } else {
                            $facture->generateDocument(self::$facture_model_pdf, $langs);
                        }

                        if (isset($data['send_msg']) && $data['send_msg']) {
                            $warnings = array_merge($warnings, $this->sendMsg('Facture'));
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
                    if ($repair->fetch((int) $item['id'])) {
                        $rep_errors = $repair->close();
                    } else {
                        $rep_errors = array('Réparation d\'id ' . $item['id'] . ' non trouvée');
                    }
                    if (count($rep_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($rep_errors, 'Echec de la fermeture de la réparation d\'ID ' . $item['id']);
                    }
                }
            }
        }

        if (count($errors)) {
            $this->setNewStatus($current_status);
        }
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
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

        $products = array();

        $success = '';

        foreach ($this->getChildrenObjects('products') as $sav_product) {
            $product = $sav_product->getChildObject('product');
            if (!is_null($product) && $product->isLoaded()) {
                if ($product->isSerialisable()) {
                    $products[] = $sav_product;
                }
            }
        }

        if (!count($products)) {
            return array('Aucun produit nécessitant l\'attribution d\'un équipement trouvé pour ce SAV');
        }

        if (!isset($data['serial']) || !$data['serial']) {
            $errors[] = 'Veillez saisir le numéro de série d\'un équipement';
        } else {
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
            $filters = array(
                'serial' => array(
                    'in' => array('\'' . $data['serial'] . '\'', '\'S' . $data['serial'] . '\'')
                )
            );
            $list = $equipment->getList($filters, null, null, 'id', 'desc', 'array', array('id'));
            if (is_null($list) || !count($list)) {
                $errors[] = 'Aucun équipement trouvé pour ce numéro de série';
            } else {
                foreach ($list as $item) {
                    if ($equipment->fetch((int) $item['id'])) {
                        $id_product = (int) $equipment->getData('id_product');
                        if ($id_product) {
                            foreach ($products as $sav_product) {
                                if (!(int) $sav_product->getData('id_equipment')) {
                                    if ($id_product === (int) $sav_product->getData('id_product')) {
                                        $product = $sav_product->getChildObject('product');
                                        $sav_product->set('id_equipment', $equipment->id);
                                        $errors = $sav_product->update();
                                        $success = 'Equipement ' . $equipment->id . ' (N° série ' . $equipment->getData('serial') . ') attribué pour le produit "' . $product->getData('ref') . ' - ' . $product->getData('label') . '"';
                                        break 2;
                                    }
                                } elseif ((int) $sav_product->getData('id_equipment') === $equipment->id) {
                                    $errors[] = 'L\'équipement ' . $equipment->id . ' (N° série ' . $equipment->getData('serial') . ') a déjà été attribué à un produit de ce SAV';
                                    break 2;
                                }
                            }
                        }
                    } else {
                        $errors[] = 'Echec de la récupération des données pour l\'équipement d\'ID ' . $item['id'];
                    }
                }
            }
            if (!$success && !count($errors)) {
                $errors[] = 'Aucun produit enregistré pour ce SAV ne correspond à ce numéro de série';
            }
        }

        return $errors;
    }

    public function actionAttentePiece($data, &$success)
    {
        $success = 'Mise à jour du statut du SAV effectué avec succès';

        $errors = $this->setNewStatus(self::BS_SAV_ATT_PIECE);

        if (!count($errors)) {
            global $user, $langs;

            $this->addNote('Attente pièce depuis le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));

            if (isset($data['send_msg']) && (int) $data['send_msg']) {
                $warnings = array_merge($warnings, $this->sendMsg('commOk'));
            }
        }

        return $errors;
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
                if (!$caisse->fetch($id_caisse)) {
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

    // Overrides:

    public function create(&$warnings = array())
    {
        $errors = array();

        if ((float) $this->getData('acompte') > 0) {
            if (!(int) BimpTools::getValue('mode_paiement_acompte', 0)) {
                $errors[] = 'Veuillez sélectionner un mode de paiement pour l\'acompte';
            }
            if ($this->useCaisseForPayments && $this->getData("id_facture_acompte")) {
                global $user;

                $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
                $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);
                if (!$id_caisse) {
                    $errors[] = 'Veuillez-vous <a href="' . DOL_URL_ROOT . '/bimpcaisse/index.php" target="_blank">connecter à une caisse</a> pour l\'enregistrement de l\'acompte';
                } else {
                    if (!$caisse->fetch($id_caisse)) {
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

        $errors = parent::create($warnings);

        if (!count($errors) && !defined('DONT_CHECK_SERIAL')) {
            if ($this->getData("id_facture_acompte") < 1 && (float) $this->getData('acompte') > 0) {
                $fac_errors = $this->createAccompte((float) $this->getData('acompte'), false);
                if (count($fac_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'Des erreurs sont survenues lors de la création de la facture d\'acompte');
                }
            }

            if ($this->getData("id_propal") < 1 && $this->getData("sav_pro") < 1) {
                $prop_errors = $this->createPropal();
                if (count($prop_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($prop_errors, 'Des erreurs sont survenues lors de la création de la proposition commerciale');
                }
            }

            if ((int) $this->getData('id_equipment')) {
                $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
                $place_errors = $place->validateArray(array(
                    'id_equipment' => (int) $this->getData('id_equipment'),
                    'type'         => BE_Place::BE_PLACE_SAV,
                    'id_entrepot'  => (int) $this->getData('id_entrepot'),
                    'infos'        => 'Ouverture du SAV ' . $this->getData('ref'),
                    'date'         => date('Y-m-d H:i:s')
                ));
                if (!count($place_errors)) {
                    $place_errors = $place->create();
                }

                if (count($place_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($place_errors, 'Echec de la création de l\'emplacement de l\'équipement');
                }
            }

            $this->generatePDF('pc', $warnings);

            if (BimpTools::getValue('send_msg', 0)) {
                $warnings = array_merge($warnings, $this->sendMsg('debut'));
            }
        }

        return $errors;
    }

    public function update(&$warnings = array())
    {
        $errors = array();

        $centre = $this->getCentreData();

        if (!is_null($centre)) {
            $this->set('id_entrepot', (int) $centre['id_entrepot']);
        }

        if (!count($errors)) {
            $errors = parent::update($warnings);
        }

        return $errors;
    }

    // Gestion des droits: 

    public function canCreate()
    {
        return $this->canView();
    }

    public function canEdit()
    {
        return $this->canView();
    }

    public function canView()
    {
        global $user;
        return (int) $user->rights->BimpSupport->read;
    }

    public function canDelete()
    {
        global $user;
        return (int) $user->rights->BimpSupport->delete;
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

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
    public $check_version = true;

    public function __construct($db)
    {
        parent::__construct("bimpsupport", get_class($this));

        define("NOT_VERIF", true);

        $this->useCaisseForPayments = BimpCore::getConf('sav_use_caisse_for_payments');
    }
    
    public function renderHeaderExtraLeft(){
        $soc = $this->getChildObject("client");
        return $soc->dol_object->getNomUrl(1);;
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

        return $buttons;
    }

    public function getEquipmentSearchFilters(&$filters, $value, &$joins = array())
    {
        if ((string) $value) {
            $joins['e'] = array(
                'table' => 'be_equipment',
                'alias' => 'e',
                'on'    => 'a.id_equipment = e.id'
            );
            $filters['or_equipment'] = array(
                'or' => array(
                    'e.serial'        => array(
                        'part_type' => 'middle', // ou middle ou end
                        'part'      => $value
                    ),
                    'e.product_label' => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'e.warranty_type' => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    )
                )
            );
        }
    }

    public function getEquipementSearchFilters(&$filters, $value, &$joins = array())
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

    public function displayEquipment()
    {
        $return = "";
        $equipement = $this->getChildObject('equipment');
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

    public function displayExtraSav()
    {
        $equip = $this->getChildObject("equipment");
        $savS = BimpObject::getInstance('bimpsupport', 'BS_SAV');
        $list = $savS->getList(array('id_equipment' => $equip->id));
        foreach ($list as $arr) {
            if ($arr['id'] != $this->id) {
                $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
                $sav->fetch($arr['id']);
                $return .= $sav->getNomUrl() . "<br/>";
            }
        }


        $repairS = BimpObject::getInstance('bimpapple', 'GSX_Repair');
        $list = $repairS->getList(array('id_sav' => $this->id));
        foreach ($list as $arr) {
            $reapir = BimpObject::getInstance('bimpapple', 'GSX_Repair');
            $return .= "<a href='#gsx'>" . $arr['repair_confirm_number'] . "</a><br/>";
        }

        return $return;
    }

    public function defaultDisplayEquipmentsItem($id_equipment)
    {
        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        if ($equipment->fetch($id_equipment)) {
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
                            'form_name'        => 'acompte_mode_paiement',
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

                $instance = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
                $list = new BC_ListTable($instance, 'default', 1, (int) $this->getData('id_propal'), 'Lignes du devis');
                $html .= $list->renderHtml();
            }
        }

        return $html;
    }

    public function renderPropalesList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $asso = new BimpAssociation($this, 'propales');
            $list = $asso->getAssociatesList();

//            echo '<pre>';
//            print_r($list);
//            exit;
            if (count($list)) {
                krsort($list);
                $propal = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');
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
                    if ($propal->fetch($id_propal)) {
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
                    $errors[] = $error_msg . ' (statut actuel invalide : ' . $current_status . ')';
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

                $this->updateField('id_propal', (int) $prop->id);
                $asso = new BimpAssociation($this, 'propales');
                $asso->addObjectAssociation((int) $prop->id);

                if ($this->getData("id_facture_acompte"))
                    addElementElement("propal", "facture", $prop->id, $this->getData("id_facture_acompte"));
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


        $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
        $line->no_equipment_post = true;

        // Acompte: 
        if ($this->getData('id_discount') > 0) {
            BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
            $discount = new DiscountAbsolute($this->db->db);
            $discount->fetch($this->getData('id_discount'));

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
//        $line_errors = array_merge($line_errors, $line_warnings);
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
        $line->find(array(
            'id_obj'             => $prop->id,
            'linked_object_name' => 'sav_diagnostic',
            'linked_id_object'   => (int) $this->id
                ), false, true);

        $line_errors = array();

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
                $line_errors = $line->delete(true);
            }
        }

        if (count($line_errors)) {
            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la ' . $error_label . ' de la ligne "Diagnostic"');
        }
        if (count($line_warnings)) {
            $warnings[] = BimpTools::getMsgFromArray($line_warnings);
        }

        // Infos Suppl: 
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
                $line_errors = $line->delete(true);
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

        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        foreach ($this->getChildrenObjects('propal_lines', array(
            'type'               => BS_SavPropalLine::LINE_PRODUCT,
            'linked_id_object'   => 0,
            'linked_object_name' => ''
        )) as $line) {
            if ((int) $line->id_product) {
                if ($product->fetch((int) $line->id_product)) {
                    if (!(int) $line->getData('out_of_warranty')) {
                        $remise = (float) $line->remise;
                        $coefRemise = (100 - $remise) / 100;
                        $garantieHt += ((float) $line->pu_ht * (float) $line->qty * (float) $coefRemise);
                        $garantieTtc += ((float) $line->pu_ht * (float) $line->qty * ((float) $line->tva_tx / 100) * $coefRemise);
                        $garantiePa += (float) $line->pa_ht * (float) $line->qty;
                    } else {
                        $this->allGarantie = false;
                    }
                }
            }
        }

        foreach ($this->getChildrenObjects('propal_lines', array(
            'linked_object_name' => 'sav_apple_part'
        )) as $line) {
            if (!(int) $line->getData('out_of_warranty')) {
                $garantieHt += ((float) $line->pu_ht * (float) $line->qty);
                $garantieTtc += ((float) $line->pu_ht * (float) $line->qty * ((float) $line->tva_tx / 100));
                $garantiePa += (float) $line->pa_ht * (float) $line->qty;
            } else {
                $this->allGarantie = false;
            }
        }

        $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');

        $rows = $line->getList(array(
            'id_obj'             => (int) $propal->id,
            'linked_id_object'   => (int) $this->id,
            'linked_object_name' => 'sav_garantie'
                ), null, null, 'position', 'asc', 'array', array('id'));

        if ($rows > 1) {
            foreach ($rows as $idx => $r) {
                if ($idx === 0) {
                    continue;
                }
                $line->fetch((int) $r['id']);
                $line->delete();
            }
        }

        if (isset($rows[0]['id']) && (int) $rows[0]['id']) {
            $line->fetch((int) $rows[0]['id']);
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

            $line_warnings = array();
            $error_label = '';
            if (!$line->isLoaded()) {
                $error_label = 'création';
                $line_errors = $line->create($line_warnings, true);
            } else {
                $error_label = 'mise à jour';
                $line_errors = $line->update($line_warnings, true);
            }
            $line_errors = array_merge($line_errors, $line_warnings);
        } else {
            if ($line->isLoaded()) {
                $error_label = 'suppression';
                $line_errors = $line->delete(true);
            }
        }

        if (count($line_errors)) {
            return BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la ' . $error_label . ' de la ligne "Garantie"');
        }

        return '';
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


        $signature = file_get_contents("http://bimp.fr/emailing/signatures/signevenementiel2.php?prenomnom=BIMP%20SAV&adresse=Centre%20de%20Services%20Agr%C3%A9%C3%A9%20Apple", stream_context_create(array(
            'http' => array(
                'timeout' => 2   // Timeout in seconds
        ))));

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
                        include_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
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
                $sms = "Bonjour, le diagnostic de votre \"" . $nomMachine . "\" commence, nous vous recontacterons quand celui-ci sera fini.\nL'équipe BIMP";
                break;

            case 'debDiago':
                $subject = "Prise en charge " . $this->getData('ref');
                $mail_msg = "Nous avons commencé le diagnostic de votre \"$nomMachine\", vous aurez rapidement des nouvelles de notre part. ";
                $sms = "Nous avons commencé le diagnostic de votre \" $nomMachine \", vous aurez rapidement des nouvelles de notre part.\nL'équipe BIMP";
                break;

            case 'commOk':
                $subject = 'Commande piece(s) ' . $this->getData('ref');
                $mail_msg = "Nous venons de commander la/les pièce(s) pour votre '" . $nomMachine . "' ou l'échange de votre iPod,iPad,iPhone. Nous restons à votre disposition pour toutes questions au " . $tel;
                $sms = "Bonjour, la pièce/le produit nécessaire à votre réparation vient d'être commandé(e), nous vous contacterons dès réception de celle-ci.\nL'équipe BIMP";
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
                    $where = " (SELECT `fk_usergroup` FROM `" . MAIN_DB_PREFIX . "usergroup_user` WHERE `fk_user` = " . $id_user_tech . ") AND `nom` REGEXP 'Sav([0-9])'";
//                    $rows = $this->db->getRows(array('usergroup_extrafields ge', ), "fk_object IN ".$where, null, 'object', array('mail'));

                    $sql = $this->db->db->query("SELECT `mail` FROM llx_usergroup_extrafields ge, llx_usergroup g WHERE fk_object IN  (SELECT `fk_usergroup` FROM `llx_usergroup_user` WHERE ge.fk_object = g.rowid AND `fk_user` = " . $id_user_tech . ") AND `nom` REGEXP 'Sav([0-9])'");

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

            if($this->testMail($toMail)){
                if (!mailSyn2($subject, $toMail, $fromMail, $mail_msg, $tabFile, $tabFile2, $tabFile3)) 
                    $errors[] = 'Echec envoi du mail';
            }
            else{
                $errors[] = "Pas d'email correct ".$toMail;
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
        foreach ($this->getChildrenObjects("propal_lines") as $line) {
            $line->set("out_of_warranty", $garantie ? "0" : "1");
            $line->update();
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

                $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');

                foreach ($lines as $line) {
                    if (BimpObject::objectLoaded($line)) {
                        if (!(int) $line->id_product) {
                            continue;
                        }
                        if ($product->fetch((int) $line->id_product)) {
                            if ((int) $product->getData('fk_product_type') === Product::TYPE_PRODUCT) {
                                if ($product->isSerialisable()) {
                                    $eq_lines = $line->getEquipmentLines();
                                    foreach ($eq_lines as $eq_line) {
                                        $reservation->reset();
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
                                    $reservation->reset();
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
                    if ($reservation->fetch((int) $item['id'])) {
                        $qty = null;
                        $reservation->set('status', $status);
                        $res_errors = $reservation->setNewStatus($status, $qty, $reservation->getData('id_equipment'));
                        if (!count($res_errors)) {
                            $res_errors = $reservation->update();
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
                            'position'           => (int) $line['rang']
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
                            'position'           => (int) $line['rang']
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
                    $this->updateField('version', 1);
                }
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
            return $errors;
        }

        define("NOT_VERIF", true);

//        $errors = array_merge($errors, $this->createReservations());

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
                $new_id_propal = $revision->reviserPropal(false, true, self::$propal_model_pdf, $errors, $this->getData("id_client"));

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

                    $errors = array_merge($errors, $this->setNewStatus(self::BS_SAV_EXAM_EN_COURS));
                    global $user, $langs;
                    $this->addNote('Devis mis en révision le "' . date('d / m / Y H:i') . '" par ' . $user->getFullName($langs));
                    $warnings = array_merge($warnings, $this->removeReservations());

                    if (isset($this->config->objects['propal'])) {
                        unset($this->config->objects['propal']);
                    }

                    $this->updateField('id_propal', (int) $new_id_propal);

                    $asso = new BimpAssociation($this, 'propales');
                    $asso->addObjectAssociation((int) $new_id_propal);

                    $propalLine = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
                    $lines_list = $propalLine->getList(array(
                        'id_obj' => (int) $old_id_propal,
                            ), null, null, 'position', 'asc', 'array', array('id'));
                    $i = 0;
                    foreach ($lines_list as $item) {
                        $i++;
                        if ($propalLine->fetch((int) $item['id'])) {
                            $remises = $propalLine->getRemises();
                            $eq_lines = $propalLine->getEquipmentLines();
                            $propalLine->id = null;
                            $propalLine->set('id', 0);
                            $propalLine->remise = 0;
                            $propalLine->setIdParent($new_id_propal);
                            $line_errors = $propalLine->create();
                            if (count($line_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la copie de la ligne du devis n°' . $i);
                            } else {
                                if (count($remises)) {
                                    $j = 0;
                                    foreach ($remises as $remise) {
                                        $j++;
                                        $remise->id = null;
                                        $remise->set('id', 0);
                                        $remise->set('id_object_line', $propalLine->id);
                                        $remise_errors = $remise->create();
                                        if (count($remise_errors)) {
                                            $warnings[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la copie de la remise n°' . $j . ' pour la ligne du devis n°' . $i);
                                        }
                                    }
                                }
                                if (count($eq_lines)) {
                                    $new_eq_lines = $propalLine->getEquipmentLines();
                                    $j = 0;
                                    foreach ($eq_lines as $eq_line) {
                                        $j++;
                                        $id_equipment = (int) $eq_line->getData('id_equipment');
                                        if ($id_equipment) {
                                            $new_eq_line = array_shift($new_eq_lines);
                                            $eq_line_errors = array();
                                            if (BimpObject::objectLoaded($new_eq_line)) {
                                                $new_eq_line->validateArray(array(
                                                    'id_equipment'   => $id_equipment,
                                                    'pu_ht'          => (float) $eq_line->getData('pu_ht'),
                                                    'tva_tx'         => (float) $eq_line->getData('tva_tx'),
                                                    'pa_ht'          => (float) $eq_line->getData('pa_ht'),
                                                    'id_fourn_price' => (int) $eq_line->getData('id_fourn_price')
                                                ));
                                                $eq_line_errors = $new_eq_line->update();
                                            } else {
                                                $eq_line_errors[] = 'Aucune ligne d\'équipement disponible';
                                            }
                                            if (count($eq_line_errors)) {
                                                $warnings[] = BimpTools::getMsgFromArray($eq_line_errors, 'Echec de la copie de la ligne d\'équipement n°' . $j . ' pour la ligne du devis n°' . $i);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $errors[] = 'Echec de la mise en révision du devis';
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
                                            // Création du nouvel emplacement: 
                                            $place->reset();
                                            if ($id_client) {
                                                $place_errors = $place->validateArray(array(
                                                    'id_equipment' => (int) $eq_line->getData('id_equipment'),
                                                    'type'         => BE_Place::BE_PLACE_CLIENT,
                                                    'id_client'    => (int) $id_client,
                                                    'infos'        => 'Vente SAV',
                                                    'date'         => date('Y-m-d H:i:s')
                                                ));
                                            } else {
                                                $place_errors = $place->validateArray(array(
                                                    'id_equipment' => (int) $eq_line->getData('id_equipment'),
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
                                                $equipment = $line->getChildObject('equipment');
                                                if (BimpObject::objectLoaded($equipment)) {
                                                    $label = $equipment->getRef();
                                                } else {
                                                    $label = 'Erreur: cet équipment n\'existe plus';
                                                }
                                                $eq_line_errors[] = BimpTools::getMsgFromArray($place_errors, 'Echec de l\'enregistrement du nouvel emplacement pour le n° de série "' . $label . '"');
                                            }
                                        }
                                    }
                                    if (count($eq_line_errors)) {
                                        $error_msg = 'Echec de la mise à jour de l\'emplacement pour le produit "' . $product->getData('ref') . ' - ' . $product->getData('label') . '"';
                                        $warnings[] = BimpTools::getMsgFromArray($eq_line_errors, $error_msg);
                                    }
                                } else {
                                    $result = $product->dol_object->correct_stock($user, $id_entrepot, (int) $line->qty, 1, $this->getRef(), 0, $codemove);
                                    if ($result < 0) {
                                        $msg = 'Echec de la mise à jour du stock pour le produit "' . $product->getData('label') . '" (Ref: "' . $product->getRef() . '")';
                                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), $msg);
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

                        if ($facture->createFromOrder($propal->dol_object, $user) <= 0) {
                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture), 'Echec de la création de la facture');
                        } else {
                            $facture->addline("Résolution : " . $this->getData('resolution'), 0, 1, 0, 0, 0, 0, 0, null, null, null, null, null, 'HT', 0, 3);
                            if ($facture->validate($user, '') <= 0) { //pas d'entrepot pour pas de destock
                                $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture), 'Echec de la validation de la facture');
                            } else {
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
                                $up_errors = $this->updateField('id_facture', (int) $facture->id);
                                if (count($up_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement de l\'ID de la facture (' . $facture->id . ')');
                                } else {
                                    $facture->generateDocument(self::$facture_model_pdf, $langs);
                                }

                                if (isset($data['send_msg']) && $data['send_msg']) {
                                    $warnings = array_merge($warnings, $this->sendMsg('Facture'));
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
            if ($this->useCaisseForPayments) {
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

        // Création des lignes propal:
        if ((int) $this->getData('id_propal')) {
            $prop_errors = $this->generatePropalLines();
            if (count($prop_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($prop_errors, 'Des erreurs sont survenues lors de la création des lignes du devis');
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();

        $centre = $this->getCentreData();

        if (!is_null($centre)) {
            $this->set('id_entrepot', (int) $centre['id_entrepot']);
        }

        if (!count($errors)) {
            $errors = parent::update($warnings, $force_update);
        }

        // Mise à jour des lignes propales:
        if ((int) $this->getData('id_propal')) {
            $propal = $this->getChildObject('propal');
            if (BimpObject::objectLoaded($propal)) {
                if ((int) $propal->getData('fk_statut') === 0) {
                    $prop_errors = $this->generatePropalLines();
                    if (count($prop_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($prop_errors, 'Des erreurs sont survenues lors de la mise à jour des lignes du devis');
                    }
                }
            }
        }

        return $errors;
    }

    public function fetch($id, $parent = null)
    {
        if (parent::fetch($id, $parent)) {
//            echo (float) $this->getData('version');
//            exit;
            if ($this->check_version && (float) $this->getData('version') < 1.0) {
                $this->convertSav();
            }

            return true;
        }

        return false;
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

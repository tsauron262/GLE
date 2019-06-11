<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BContract_contrat extends BimpDolObject {
    //public $redirectMode = 4;
    // Les status
    CONST CONTRAT_STATUS_BROUILLON = 0;
    CONST CONTRAT_STATUS_VALIDE = 1;
    CONST CONTRAT_STATUS_CLOS = 2;
    CONST CONTRAT_STATUS_SIGNED = 10;
    // Les périodicitées
    CONST CONTRAT_PERIOD_MENSUELLE = 1;
    CONST CONTRAT_PERIOD_TRIMESTRIELLE = 3;
    CONST CONTRAT_PERIOD_SEMESTRIELLE = 6;
    CONST CONTRAT_PERIOD_ANNUELLE = 12;
    // Les délais d'intervention
    CONST CONTRAT_DELAIS_4_HEURES = 4;
    CONST CONTRAT_DELAIS_8_HEURES = 8;
    CONST CONTRAT_DELAIS_16_HEURES = 16;
    // Les renouvellements
    CONST CONTRAT_RENOUVELLEMENT_NON = 0;
    CONST CONTRAT_RENOUVELLEMENT_1_FOIS = 1;
    CONST CONTRAT_RENOUVELLEMENT_2_FOIS = 3;
    CONST CONTRAT_RENOUVELLEMENT_3_FOIS = 6;
    CONST CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION = 12;
    // Contrat dénoncé
    CONST CONTRAT_DENOUNCE_NON = 0;
    CONST CONTRAT_DENOUNCE_OUI_DANS_LES_TEMPS = 1;
    CONST CONTRAT_DENOUNCE_OUI_HORS_DELAIS = 2;
    // Mode de règlements
    CONST CONTRAT_REGLEMENT_TIP = 1;
    CONST CONTRAT_REGLEMENT_VIREMENT = 2;
    CONST CONTRAT_REGLEMENT_PRELEVEMENT = 3;
    CONST CONTRAT_REGLEMENT_ESPECES = 4;
    CONST CONTRAT_REGLEMENT_CB = 6;
    CONST CONTRAT_REGLEMENT_CHEQUE = 7;
    CONST CONTRAT_REGLEMENT_BOR = 11;
    CONST CONTRAT_REGLEMENT_FINANCEMENT = 13;
    CONST CONTRAT_REGLEMENT_TRAITE = 51;
    CONST CONTRAT_REGLEMENT_MANDAT = 55;
    CONST CONTRAT_REGLEMENT_30_JOURS = 56;
    CONST CONTRAT_REGLEMENT_REMBOURCEMENT = 57;
    CONST CONTRAT_REGLEMENT_AMERICAN_EXPRESS = 58;

    public static $status_list = Array(
        self::CONTRAT_STATUS_BROUILLON => Array('label' => 'Brouillon', 'classes' => Array('warning'), 'icon' => 'fas_trash-alt'),
        self::CONTRAT_STATUS_VALIDE => Array('label' => 'Validé', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::CONTRAT_STATUS_CLOS => Array('label' => 'Clos', 'classes' => Array('danger'), 'icon' => 'fas_times'),
    );
    public static $denounce = Array(
        self::CONTRAT_DENOUNCE_NON => Array('label' => 'Non', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::CONTRAT_DENOUNCE_OUI_DANS_LES_TEMPS => Array('label' => 'OUI, DANS LES TEMPS', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::CONTRAT_DENOUNCE_OUI_HORS_DELAIS => Array('label' => 'OUI, HORS DELAIS', 'classes' => Array('danger'), 'icon' => 'fas_times'),
    );
    public static $period = Array(
        self::CONTRAT_PERIOD_MENSUELLE => 'Mensuelle',
        self::CONTRAT_PERIOD_TRIMESTRIELLE => 'Trimestrielle',
        self::CONTRAT_PERIOD_SEMESTRIELLE => 'Semestrielle',
        self::CONTRAT_PERIOD_ANNUELLE => 'Annuelle'
    );
    public static $gti = Array(
        self::CONTRAT_DELAIS_4_HEURES => '4 heures ouvrées',
        self::CONTRAT_DELAIS_8_HEURES => '8 heures ouvrées',
        self::CONTRAT_DELAIS_16_HEURES => '16 heures ouvrées'
    );
    public static $renouvellement = Array(
        self::CONTRAT_RENOUVELLEMENT_NON => 'Non',
        self::CONTRAT_RENOUVELLEMENT_1_FOIS => 'Tacite 1 fois',
        self::CONTRAT_RENOUVELLEMENT_2_FOIS => 'Tacite 2 fois',
        self::CONTRAT_RENOUVELLEMENT_3_FOIS => 'Tacite 3 fois',
        self::CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION => 'Sur proposition'
    );
    public static $mode_reglement = Array(
        self::CONTRAT_REGLEMENT_TIP => 'TIP',
        self::CONTRAT_REGLEMENT_VIREMENT => 'Virement',
        self::CONTRAT_REGLEMENT_PRELEVEMENT => 'Prélèvement',
        self::CONTRAT_REGLEMENT_ESPECES => 'Espèces',
        self::CONTRAT_REGLEMENT_CB => 'Carte banquaire',
        self::CONTRAT_REGLEMENT_CHEQUE => 'Chèque',
        self::CONTRAT_REGLEMENT_BOR => 'B.O.R',
        self::CONTRAT_REGLEMENT_FINANCEMENT => 'Financement',
        self::CONTRAT_REGLEMENT_TRAITE => 'Traité',
        self::CONTRAT_REGLEMENT_MANDAT => 'Mandat',
        self::CONTRAT_REGLEMENT_30_JOURS => '30 jours',
        self::CONTRAT_REGLEMENT_REMBOURCEMENT => 'Rembourcement',
        self::CONTRAT_REGLEMENT_AMERICAN_EXPRESS => 'American Express'
    );
    
    public static $dol_module = 'contract';

    
    function __construct($module, $object_name) {
        if(BimpTools::getContext() == 'public') {
            $this->redirectMode = 4;
        }
        return parent::__construct($module, $object_name);
    }


    /* GETTERS */  
    public function getModeReglementClient() {
        global $db;
        BimpTools::loadDolClass('societe');
        $client = new Societe($db);
        $client->fetch($this->getData('fk_soc'));
        return $client->mode_reglement_id;
    }
    
    public function getEndDate() {
        $debut = new DateTime();
        $fin = new DateTime();
        $Timestamp_debut = strtotime($this->getData('date_start'));
        if($Timestamp_debut > 0){
            $debut->setTimestamp($Timestamp_debut);
        $fin->setTimestamp($Timestamp_debut);
        if($this->getData('duree_mois') > 0)
            $fin = $fin->add(new DateInterval("P" . $this->getData('duree_mois') . "M"));
        $fin = $fin->sub(new DateInterval("P1D"));
        return $fin;
        }
        return '';
    }
    
    public function displayRef() {
        return $this->getData('ref') . ' - ' . $this->getData('objet_contrat');
    }

    public function displayEndDate() {
        $fin = $this->getEndDate();
        if($fin > 0)
            return $fin->format('d/m/Y');
    }
    
    public function getName() {
        return $this->getData('objet_contrat');
    }
    
    public function getAddContactIdClient()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return $id_client;
    }
    
    public function getClientContactsArray()
    {
        $id_client = $this->getAddContactIdClient();
        return self::getSocieteContactsArray($id_client, false);
    }
    
    public function getActionsButtons()
    {
        global $conf, $langs, $user;
        $buttons = Array();
        if ($this->isLoaded() && BimpTools::getContext() != 'public') {
            $status = $this->getData('statut');
            $buttons[] = array(
                'label'   => 'Générer le PDF du contrat',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
            );
            $buttons[] = array(
                'label'   => 'Générer le PDF du courrier',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('generatePdfCourrier', array(), array())
            );
            $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
            if ($this->getData('statut') == self::CONTRAT_STATUS_BROUILLON) {
                $buttons[] = array(
                    'label' => 'Valider le contrat',
                    'icon' => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('validation', array(), array(
                        'success_callback' => $callback
                    ))
                );
            }
            
            if($status == self::CONTRAT_STATUS_VALIDE && is_null($this->getData('date_contrat'))) {
                 $buttons[] = array(
                    'label' => 'Contrat signé',
                    'icon' => 'fas_signature',
                    'onclick' => $this->getJsActionOnclick('signed', array(), array(
                        'success_callback' => $callback
                    ))
                );
            }
            
            if (!is_null($status)) {
                $status = (int) $status;
                $soc = $this->getChildObject('client');
                // Cloner: 
                if ($this->can("create")) {
                    $buttons[] = array(
                        'label'   => 'Cloner',
                        'icon'    => 'copy',
                        'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
                            'form_name' => 'duplicate_contrat'
                        ))
                    );
                }
            }
        }

        return $buttons;
    }
    
    public function getSyntecSite() {
       return "Pour connaitre l'indice syntec en vigueur, veuillez vous rendre sur le site internet <a href='https://www.syntec.fr' target='_blank'>https://www.syntec.fr</a>";
    }
    
    /* DISPLAY */
    public function display_card() {
        $card = "";

        $card .= '<div class="col-md-4">';
        $card .= '<div class="card">';
        $card .= '<div class="header">';
        $card .= '<h4 class="title">' . $this->getRef() . '</h4>';
        $card .= '<p class="category">';
        $card .= ($this->isValide()) ? 'Contrat en cours de vadité' : 'Contrat échu';
        $card .= '</p>';
        $card .= '</div>';
        $card .= '<div class="content"><div class="footer"><div class="legend">';
        $card .= ($this->isValide()) ? '<i class="fa fa-plus text-success"></i> <a href="?fc=contrat_ticket&id=' . $this->getData('id') . '&navtab-maintabs=tickets">Créer un ticket support</a>' : '';
        $card .= '<i class="fa fa-eye text-info"></i><a href="?fc=contrat_ticket&id='.$this->getData('id').'">Voir le contrat</a></div><hr><div class="stats"></div></div></div>';
        $card .= '</div></div>';

        return $card;
    }
    
    /* RIGHTS */
    public function canEdit(){
        if($this->getData("statut") == self::CONTRAT_STATUS_CLOS || $this->getData('statut') == self::CONTRAT_STATUS_VALIDE)
            return 0;
        return 1;
    }

    public function canClientView() {
        global $userClient;
        if($userClient->it_is_admin()){
            return true;
        }
        $list = $userClient->getChildrenObjects('user_client_contrat');
        foreach ($list as $obj) {
            if($obj->getData('id_contrat') == $this->id) {
                return true;
            }
        }
        return false;
    }
    
     public function canDelete() {
        return $this->canEdit();
    }
    
    /* ACTIONS */
    
    public function actionSigned($data, &$success) {
        $success = 'Contrat signé avec succes';
        $this->updateField('date_contrat', date('Y-m-d HH:ii:ss'));
    }
    
    public function actionValidation($data, &$success) {
        global $user;
        if($this->dol_object->validate($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object));
            return 0;
        }
        $this->dol_object->activateAll($user);
        $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
        $instance->find(Array('id_contrat' => $this->id));
        if($instance->updateLine($this->id, $this->getData('date_start') )) {
            $success .= 'Contrat et échéancier créer avec succes';
        }
    }
    
    public function actionAddContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ajout du contact effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['type']) || !(int) $data['type']) {
                $errors[] = 'Nature du contact absent';
            } else {
                switch ((int) $data['type']) {
                    case 1:
                        $id_contact = isset($data['id_contact']) ? (int) $data['id_contact'] : 0;
                        $type_contact = isset($data['tiers_type_contact']) ? (int) $data['tiers_type_contact'] : 0;
                        if (!$id_contact) {
                            $errors[] = 'Contact non spécifié';
                        }
                        if (!$type_contact && static::$external_contact_type_required) {
                            $errors[] = 'Type de contact non spécifié';
                        }

                        if (!count($errors)) {
                            if ($this->dol_object->add_contact($id_contact, $type_contact, 'external') <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                            }
                        }
                        break;

                    case 2:
                        $id_user = isset($data['id_user']) ? (int) $data['id_user'] : 0;
                        $type_contact = isset($data['user_type_contact']) ? (int) $data['user_type_contact'] : 0;
                        if (!$id_user) {
                            $errors[] = 'Utilisateur non spécifié';
                        }
                        if (!$type_contact && static::$internal_contact_type_required) {
                            $errors[] = 'Type de contact non spécifié';
                        }
                        if (!count($errors)) {
                            if ($this->dol_object->add_contact($id_user, $type_contact, 'internal') <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                            }
                        }
                        break;
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'contact_list_html' => $this->renderContactsList()
        );
    }
    
    public function actionRemoveContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Suppression du contact effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['id_contact']) || !(int) $data['id_contact']) {
                $errors[] = 'Contact à supprimer non spécifié';
            } else {
                if ($this->dol_object->delete_contact((int) $data['id_contact']) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression du contact');
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'contact_list_html' => $this->renderContactsList()
        );
    }

    public function actionGeneratePdf($data, &$success)
    {   
        global $langs;
        $success = "PDF contrat généré avec Succes";
        $this->dol_object->generateDocument('contrat_BIMP_maintenance', $langs);
    }
    
    public function actionGeneratePdfCourrier($data, &$success)
    {   
        global $langs;
        $success = "PDF courrier généré avec Succes";
        $this->dol_object->generateDocument('contrat_courrier_BIMP_renvois', $langs);
    }
    
    /* OTHERS FUNCTIONS */
    
    public function create(&$warnings = array(), $force_create = false) {
        
        if(BimpTools::getValue('use_syntec') && !BimpTools::getValue('syntec')) {
            return 'Vous devez rensseigner un indice syntec';
        }
        return parent::create($warnings, $force_create);
    }
    
    public function fetch($id, $parent = null) {
        $return = parent::fetch($id, $parent);
        $this->autoClose();
        return $return;
    }
    
    public function autoClose(){//passer les contrat au statut clos quand toutes les enssiéne ligne sont close
        if($this->id > 0 && $this->getData("statut") == 1 && $this->getEndDate() < new DateTime()){
            $sql = $this->db->db->query("SELECT * FROM `llx_contratdet` WHERE statut != 5 AND `fk_contrat` = ".$this->id);
            if($this->db->db->num_rows($sql) == 0){
                $this->updateField("statut", 2);
            }
        }
        
    }

    public function isValide() {
        if ($this->getData('date_start') && $this->getData('duree_mois')) { // On est dans les nouveaux contrats
            $aujourdhui = strtotime(date('Y-m-d'));
            $fin = $this->getEndDate();
            $fin = $fin->getTimestamp();
            if ($fin - $aujourdhui > 0) {
                return true;
            }
        } else { // On est dans les anciens contrats
            $lines = $this->dol_object->lines; // Changera quand l'objet BContract_contratLine sera OP
            foreach ($lines as $line) {
                if ($line->statut == 4) {
                    return true;
                }
            }
        }
        return false;
    }

    /**********/
   /* RENDER */
  /**********/
    
    public function renderLinkedObjectsTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            $objects = array();

            if ($this->isDolObject()) {
                $propal_instance = null;
                $facture_instance = null;
                $commande_instance = null;
                $commande_fourn_instance = null;
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    switch ($item['type']) {
                        case 'propal':
                            $propal_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);
                            if ($propal_instance->isLoaded()) {
                                $icon = $propal_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($propal_instance->getLabel()),
                                    'ref'      => $propal_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $propal_instance->displayData('datep'),
                                    'total_ht' => $propal_instance->displayData('total_ht'),
                                    'status'   => $propal_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'facture':
                            $facture_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $item['id_object']);
                            if ($facture_instance->isLoaded()) {
                                $icon = $facture_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($facture_instance->getLabel()),
                                    'ref'      => $facture_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $facture_instance->displayData('datef'),
                                    'total_ht' => $facture_instance->displayData('total'),
                                    'status'   => $facture_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'commande':
                            $commande_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);
                            if ($commande_instance->isLoaded()) {
                                $icon = $commande_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_instance->getLabel()),
                                    'ref'      => $commande_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $commande_instance->displayData('date_commande'),
                                    'total_ht' => $commande_instance->displayData('total_ht'),
                                    'status'   => $commande_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'order_supplier':
                            $commande_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $item['id_object']);
                            if ($commande_fourn_instance->isLoaded()) {
                                $icon = $commande_fourn_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_fourn_instance->getLabel()),
                                    'ref'      => $commande_fourn_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $commande_fourn_instance->displayData('date_commande'),
                                    'total_ht' => $commande_fourn_instance->displayData('total_ht'),
                                    'status'   => $commande_fourn_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'invoice_supplier':
                            $facture_fourn_instance = BimpCache::getDolObjectInstance((int) $item['id_object'], 'fourn', 'fournisseur.facture', 'FactureFournisseur');
                            BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
                            if (BimpObject::objectLoaded($facture_fourn_instance)) {
                                $date_facture = new DateTime(BimpTools::getDateFromDolDate($facture_fourn_instance->date));
                                $objects[] = array(
                                    'type'     => 'Facture fournisseur',
                                    'ref'      => BimpObject::getInstanceNomUrlWithIcons($facture_fourn_instance),
                                    'date'     => $date_facture->format('d / m / Y'),
                                    'total_ht' => BimpTools::displayMoneyValue((float) $facture_fourn_instance->total_ht, 'EUR'),
                                    'status'   => Bimp_Facture::$status_list[(int) $facture_fourn_instance->statut]['label']
                                );
                            }
                            break;
                    }
                }
            }

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Type</th>';
            $html .= '<th>Réf.</th>';
            $html .= '<th>Date</th>';
            $html .= '<th>Montant HT</th>';
            $html .= '<th>Statut</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (count($objects)) {
                foreach ($objects as $data) {
                    $html .= '<tr>';
                    $html .= '<td><strong>' . $data['type'] . '</strong></td>';
                    $html .= '<td>' . $data['ref'] . '</td>';
                    $html .= '<td>' . $data['date'] . '</td>';
                    $html .= '<td>' . $data['total_ht'] . '</td>';
                    $html .= '<td>' . $data['status'] . '</td>';;
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="5">' . BimpRender::renderAlerts('Aucun objet lié', 'info') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Objets liés', $html, '', array(
                        'foldable' => true,
                        'type'     => 'secondary',
                        'icon'     => 'fas_link',
            ));
        }

        return $html;
    }
 
    public function renderFilesTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            global $conf;
            $dir = $conf->contrat->dir_output. '/' . dol_sanitizeFileName($this->getRef());

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
                $html .= BimpRender::renderAlerts('Aucun fichier', 'info', false);
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
    
    public function renderEcheancier() {
        
        // TODO a viré (voir pour objet)
        $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
        if($instance->find(Array('id_contrat' => $this->id))) {
            return $instance->display();
        } elseif($this->getData('statut') < self::CONTRAT_STATUS_VALIDE) {
            return $instance->display("Le contrat doit être valider pour générer l'échéancier");
        } elseif(!$this->getData('date_start') || !$this->getData('periodicity') || !$this->getData('duree_mois')) {
            return $instance->display("Un des champs : Durée en mois, Date de début, Périodicitée est obligatoire pour la génération de l'échéancier");
        } else {
            $instance->updateLine($this->id);
            return $instance->display();
        } 
        
    }
    
    public function renderContacts()
    {
        $html = '';

        $html .= '<table class="bimp_list_table">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Nature</th>';
        $html .= '<th>Tiers</th>';
        $html .= '<th>Utilisateur / Contact</th>';
        $html .= '<th>Type de contact</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';
        $html .= '<tbody id="' . $list_id . '">';
        $html .= $this->renderContactsList();

        $html .= '</tbody>';

        $html .= '</table>';

        return BimpRender::renderPanel('Liste des contacts', $html, '', array(
                    'type'           => 'secondary',
                    'icon'           => 'user-circle',
                    'header_buttons' => array(
                        array(
                            'label'       => 'Ajouter un contact',
                            'icon_before' => 'plus-circle',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => $this->getJsActionOnclick('addContact', array('id_client' => (int) $this->getData('fk_soc')), array(
                                    'form_name'        => 'contact',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                                ))
                            )
                        )
                    )
        ));
    }

    public function renderContactsList()
    {
        $html = '';

        $list = array();

        if ($this->isLoaded() && method_exists($this->dol_object, 'liste_contact')) {
            $list_int = $this->dol_object->liste_contact(-1, 'internal');
            $list_ext = $this->dol_object->liste_contact(-1, 'external');
            $list = array_merge($list_int, $list_ext);
        }

        if (count($list)) {
            global $conf;
            BimpTools::loadDolClass('societe');
            BimpTools::loadDolClass('contact');

            $soc = new Societe($this->db->db);
            $user = new User($this->db->db);
            $contact = new Contact($this->db->db);

            $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';

            foreach ($list as $item) {
                $html .= '<tr>';
                switch ($item['source']) {
                    case 'internal':
                        $user->id = $item['id'];
                        $user->lastname = $item['lastname'];
                        $user->firstname = $item['firstname'];
                        $user->photo = $item['photo'];
                        $user->login = $item['login'];

                        $html .= '<td>Utilisateur</td>';
                        $html .= '<td>' . $conf->global->MAIN_INFO_SOCIETE_NOM . '</td>';
                        $html .= '<td>' . $user->getNomUrl(-1) . BimpRender::renderObjectIcons($user) . '</td>';
                        break;

                    case 'external':
                        $soc->fetch((int) $item['socid']);
                        $contact->id = $item['id'];
                        $contact->lastname = $item['lastname'];
                        $contact->firstname = $item['firstname'];

                        $html .= '<td>Contact tiers</td>';
                        $html .= '<td>' . $soc->getNomUrl(1) . BimpRender::renderObjectIcons($soc) . '</td>';
                        $html .= '<td>' . $contact->getNomUrl(1) . BimpRender::renderObjectIcons($contact) . '</td>';
                        break;
                }
                $html .= '<td>' . $item['libelle'] . '</td>';
                $html .= '<td style="text-align: right">';
                $html .= BimpRender::renderRowButton('Supprimer le contact', 'trash', $this->getJsActionOnclick('removeContact', array('id_contact' => (int) $item['rowid']), array(
                                    'confirm_msg'      => 'Etes-vous sûr de vouloir supprimer ce contact?',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                )));
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="5">';
            $html .= BimpRender::renderAlerts('Aucun contact enregistré', 'info');
            $html .= '</td>';
            $html .= '</tr>';
        }

        return $html;
    }
    
     public function renderHeaderStatusExtra() {

        $extra = '';
        if (!is_null($this->getData('date_contrat'))) {
            $extra .= '<br/><span class="important">' . BimpRender::renderIcon('fas_signature', 'iconLeft') . 'Contrat signé</span>';
        }
        return $extra;
    }
    
}

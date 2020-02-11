<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
class BContract_contrat extends BimpDolObject {
    //public $redirectMode = 4;
    public static $email_type = '';
    //
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
    CONST CONTRAT_DELAIS_0_HEURES = 0;
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

    CONST CONTRAT_GLOBAL = "CT";
    CONST CONTRAT_DE_MAINTENANCE = 'CMA';
    CONST CONTRAT_SUPPORT_TELEPHONIQUE = 'CST';
    CONST CONTRAT_MONITORING = 'CMO'; 
    CONST CONTRAT_DE_SPARE = 'CSP';
    CONST CONTRAT_DE_DELEGATION_DE_PERSONEL = 'CDP';

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
        self::CONTRAT_DELAIS_0_HEURES => '',
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

    public static $objet_contrat = [
        self::CONTRAT_GLOBAL => ['label' => "Contrat global", 'classes' => [], 'icon' => 'globe'],
        self::CONTRAT_DE_MAINTENANCE => ['label' => "Contrat de maintenance", 'classes' => [], 'icon' => 'cogs'],
        self::CONTRAT_SUPPORT_TELEPHONIQUE => ['label' => "Contrat de support téléphonique", 'classes' => [], 'icon' => 'phone'],
        self::CONTRAT_MONITORING => ['label' => "Contrat de monitoring", 'classes' => [], 'icon' => 'terminal'],
        self::CONTRAT_DE_SPARE => ['label' => "Contrat de spare", 'classes' => [], 'icon' => 'share'],
        self::CONTRAT_DE_DELEGATION_DE_PERSONEL => ['label' => "Contrat de délégation du personnel", 'classes' => [], 'icon' => 'male'],
    ];
    
    public static $true_objects_for_link = [
        'commande' => 'Commande',
        'facture_fourn' => 'Facture fournisseur', 
        //'propal' => 'Proposition commercial'
    ];

    public static $dol_module = 'contract';
    
    function __construct($module, $object_name) {
        global $user, $db;

        $this->redirectMode = 4;
        return parent::__construct($module, $object_name);
    }
    
    public function cronContrat() {
        
        // Vérifier tous les contrats à clore.
        //$all = $this->getInstance('bimp')
        
        foreach($this->getList(['statut' => 1]) as $contrat) {
            
            print_r($contrat);
            
        }
        
        
        // Vérifier tous les contrats pour faire la relance aux commeciaux
        
        // Vérifier tout les contrats a facturé et envoyer aux commerciaux.
        
    }
    
    public function isClosDansCombienDeTemps() {
        
        $aujourdhui = new DateTime();
        $finContrat = $this->getEndDate();
        $diff = $aujourdhui->diff($finContrat);
        if(!$diff->invert) {
            return $diff->d;
        }
        return 0;
    }
    
    public function actionClose($data, &$success) {
        global $user;
        $success = 'Contrat clos avec succès';
        if($this->dol_object->closeAll($user) >= 1) {
            $this->updateField('statut', self::CONTRAT_STATUS_CLOS);
        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        ];
        
    }
    
    public function actionUpdateSyntec() {
        $syntec = file_get_contents("https://syntec.fr/"); 
        if(preg_match('/<div class="indice-number"[^>]*>(.*)<\/div>/isU', $syntec, $matches)){
            $indice = str_replace(' ', "", strip_tags($matches[0]));
            BimpCore::setConf('current_indice_syntec',  str_replace(' ', "", strip_tags($matches[0])));
            $success = "L'indice Syntec c'est mis à jours avec succès";
        } else {
            return "Impossible de récupérer l'indice Syntec automatiquement, merci de la rensseigner manuellement";
        }
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    public function update(&$warnings = array(), $force_update = false) {
        
        if(BimpTools::getValue('type_piece')) {
            $id = 0;
            switch(BimpTools::getValue('type_piece')) {
                case 'propal':
                    $id = BimpTools::getValue('propal_client');
                    break;
                case 'commande':
                    $id = BimpTools::getValue('commande_client');
                    break;
                case 'facture_fourn':
                    $id = BimpTools::getValue('facture_fourn_client');
                    break;
            }
            if($id == 0) {
                return "Il n'y à pas de pièce " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . ' pour ce client';
            } else {
                if(getElementElement(BimpTools::getValue('type_piece'), 'contrat', $id, $this->id)) {
                    return "La piece " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . ' que vous avez choisi est déjà liée à ce contrat';
                } else {
                    addElementElement(BimpTools::getValue('type_piece'), 'contrat', $id, $this->id);
                    $success = "La " . self::$true_objects_for_link[BimpTools::getValue('type_piece')] . " à été liée au contrat avec succès" ;
                }
            }
            return ['success' => $success, 'warnings' => $warnings, 'errors' => $errors];
        } else {
            return parent::update();
        }
    }
    
    public function getListClient($object) {
        
        $list = $this->db->getRows($object, 'fk_soc = ' . $this->getData('fk_soc'));
        $return = [];
        
        foreach($list as $l) {
            $instance = $this->getInstance('bimpcommercial', 'Bimp_' . ucfirst($object), $l->rowid);
            $return[$instance->id] = $instance->getData('ref') . " - " . $instance->getData('libelle');
        }
        //print_r($return);
        return $return;
        
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
    
    public function displayDateNextFacture() {
        if($this->isLoaded()) {
            $echeancier = $this->getInstance('bimpcontract', "BContract_echeancier");
                $fin = false;
                if($echeancier->find(['id_contrat' => $this->id])) {
                    $next_facture_date = $echeancier->getData('next_facture_date');
                    if($next_facture_date == 0) {
                        $fin = true;
                        $return = "<b class='important'>&Eacute;chéancier totalement facturé</b>";
                    } else {
                        $return = $next_facture_date;
                    }
                    
                } else {
                    $return = $this->getData('date_start');
                }
                if(!$fin) {
                    $return = new DateTime($return);
                    $return = $return->format('d / m / Y');
                }
            return $return;
        }
    }
    
    public function displayRef() {
        return $this->getData('ref') . ' - ' . $this->getName();
    }
    
    public function getTitleEcheancier() {
        return '&Eacute;ch&eacute;ancier du contrat N°' . $this->displayRef();
    }

    public function displayEndDate() {
        $fin = $this->getEndDate();
        if($fin > 0)
            return $fin->format('d/m/Y');
    }
    
    public function getName() {
        $objet = $this->getData('objet_contrat');
        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
        return "<span><i class='fas fa-".self::$objet_contrat[$objet]['icon']."' ></i> ".self::$objet_contrat[$objet]['label']."</span>";
        
        return self::$objet_contrat[$this->getData('objet_contrat')];
    }
    
    public function getIndiceSyntec() {
        return BimpCore::getConf('current_indice_syntec') ;
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
    
    public function actionReopen() {
        // Fonction temporaire pour le moment TODO a modifier
        $this->updateField('statut', 0);
        //$sql = "DELETE FROM llx_bcontract_prelevement WHERE id_contrat = " . $this->id;
        
        $this->db->delete('bcontract_prelevement', 'id_contrat = ' . $this->id);
        
    }
    
    public function getActionsButtons()
    {
        global $conf, $langs, $user;
        $buttons = Array();
        if($this->getData('statut') == self::CONTRAT_STATUS_BROUILLON) {
             // ToDo à viré lorsque le formulaire sur les commandes sera fait
           
        }
        
        if ($this->isLoaded() && BimpTools::getContext() != 'public') {
            $status = $this->getData('statut');
            
//            $buttons[] = array(
//                    'label'   => 'Envoyer par e-mail',
//                    'icon'    => 'envelope',
//                    'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
//                        'form_name' => 'email'
//                    ))
//                );
            
            if(($user->id == 232 || $user->id == 460 || $user->admin) && $this->getData('statut') == 1) {
                $buttons[] = array(
                    'label'   => 'Réouvrir le contrat',
                    'icon'    => 'fas_sync',
                    'onclick' => $this->getJsActionOnclick('reopen', array(), array())
                );
            }
            
            
            
            if((($user->id == 232 || $user->id == 460 || $user->admin) && $this->getData('statut') == 1) && (!is_null($this->getData('date_contrat')))) {
                $buttons[] = array(
                    'label'   => 'Dé-signer le contrat',
                    'icon'    => 'fas_sync',
                    'onclick' => $this->getJsActionOnclick('unSign', array(), array())
                );
            }
            
            if(($user->admin || $user->id == 375 || $user->id == 460) && $this->getData('statut') != self::CONTRAT_STATUS_CLOS && $this->getData('statut') != self::CONTRAT_STATUS_BROUILLON) {
                
                $buttons[] = array(
                    'label'   => 'Clôre le contrat',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('close', array(), array(
                        'confirm_msg'      => "Voulez vous clôre ce contrat ?",
                    ))
                );
                
            }
            
            if($user->admin || $user->id == 460){
                $buttons[] = array(
                    'label'   => 'Mettre à jours l\'indice Syntec',
                    'icon'    => 'fas_sync',
                    'onclick' => $this->getJsActionOnclick('updateSyntec', array(), array())
                );
            }
            
           
//            
//            if(!count(getElementElement('contrat', 'facture', $this->id)) && $user->rights->contrat->desactiver && $this->getData('statut') == self::CONTRAT_STATUS_VALIDE) {
//                $buttons[] = array(
//                    'label'   => 'Réouvrir le contrat',
//                    'icon'    => 'fas_sync',
//                    'onclick' => $this->getJsActionOnclick('reopen', array(), array())
//                );
//            } else {
//                $buttons[] = array(
//                    'label'    => 'Réouvrir le contrat',
//                    'icon'     => 'fas_sync',
//                    'onclick'  => '',
//                    'disabled' => 1,
//                    'popover'  => 'Action impossible'
//                );
//            }
//            
            
            if($status != self::CONTRAT_STATUS_CLOS) {
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
            }
            
            
//            $buttons[] = array(
//                'label'   => 'Générer le PDF de l\'échéancier',
//                'icon'    => 'fas_sync',
//                'onclick' => $this->getJsActionOnclick('generatePdfCourrier', array(), array())
//            );
            
            $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
            if ($this->getData('statut') == self::CONTRAT_STATUS_BROUILLON) {
                $buttons[] = array(
                    'label' => 'Valider le contrat',
                    'icon' => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('validation', array(), array(
                        'confirm_msg'      => "Voulez vous valider ce contrat ?",
                        'success_callback' => $callback
                    ))
                );
            }
            if($status == self::CONTRAT_STATUS_VALIDE && is_null($this->getData('date_contrat'))) {
                 $buttons[] = array(
                    'label' => 'Contrat signé',
                    'icon' => 'fas_signature',
                    'onclick' => $this->getJsActionOnclick('signed', array(), array(
                        'confirm_msg'      => "Voulez vous identifier ce contrat comme signé ?",
                        'success_callback' => $callback
                    ))
                );
            }
            
            if($status == self::CONTRAT_STATUS_VALIDE && $this->getData('date_contrat') && !getElementElement('contrat', 'contrat', $this->id)) {
                $buttons[] = array(
                    'label' => 'Créer un avenant',
                    'icon' => 'fas_plus',
                    'onclick' => $this->getJsActionOnclick('createAvenant', array(), array(
                        'confirm_msg'      => "Créer un avenant pour ce contrat ?",
                        'success_callback' => $callback
                    ))
                );
            }
        }

        return $buttons;
    }
    
    
    
    public function actionDuplicate($data, &$success = Array()) {
        
        $new_contrat = clone $this;
        $new_contrat->id = null;
        $new_contrat->id = 0;
        $new_contrat->set('id', 0);
        $new_contrat->set('fk_statut', 1);
        $new_contrat->set('ref', '');
        $new_contrat->set('date_contrat', null);
        
        if($new_contrat->create()) {
            $success = "Contrat cloner avec succès";
            
        }
        return $errors[] = $new_contrat->getData('ref');
        $errors = Array();
        
        if(!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }
        
        
        
        return Array(
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        );
        
    }
    
    public function actionUnSign() {
        if($this->updateField('date_contrat', null)) {
            $success = 'Contrat dé-signer';
        }
        
        return [
          'success' => $success,
          'errors' => $errors,
          'warnings' => $warnings
        ];
    }
    
    public function getSyntecSite() {
       return "Pour connaitre l'indice syntec en vigueur, veuillez vous rendre sur le site internet <a href='https://www.syntec.fr' target='_blank'>https://www.syntec.fr</a>";
    }
    
    /* DISPLAY */
    public function display_card() {
        
        $societe = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
        $card = "";

        $card .= '<div class="col-md-4">';
        
        $card .= "<div class='card_interface'>";
        //$card .= "<img src='".DOL_URL_ROOT."/viewimage.php?modulepart=societe&entity=1&file=381566%2F%2Flogos%2Fthumbs%2F".$societe->dol_object->logo."&cache=0' alt=''><br />";
        $card .= "<div class='img' ><i class='fas fa-". self::$objet_contrat[$this->getData('objet_contrat')]['icon'] ."' ></i></div>";
            
        
            $card .= "<h1>" . $this->getRef() . "</h1>";
            //$card .= "<h2>". self::$objet_contrat[$this->getData('objet_contrat')]['label'] ."</h2>";
            $card .= '<h2>Durée du contrat : '. $this->getData('duree_mois') .' mois</h2>';
            if($this->getData('periodicity')) {
                $card .= '<h2>Facturation : '. self::$period[$this->getData('periodicity')] .'</h2>';
            }
            $card .= '<a tool="Voir le contrat" flow="down" class="button" href="?fc=contrat_ticket&id=' . $this->getData('id') . '"><i class="fas fa-eye"></i></a>';
            if($this->isValide()) {
                $card .= '<a tool="Créer un ticket" flow="down" class="button" href="?fc=contrat_ticket&id=' . $this->getData('id') . '&navtab-maintabs=tickets"><i class="fas fa-plus"></i></a>';
            }
            //$card .= '<a tool="Statistiques du contrat" flow="down" class="button" href="https://instagram.com/chynodeluxe"><i class="fas fa-leaf"></i></a>';
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
        
        $id_contact_type = $this->db->getValue('c_type_contact', 'rowid', 'code = "SITE" AND element = "contrat"');
        $have_contact = ($this->db->getValue('element_contact', 'rowid', 'element_id = ' . $this->id . ' AND fk_c_type_contact = ' . $id_contact_type)) ? true : false;
        
        if(!$have_contact) {
            return "Il doit y avoir au moin un site d'intervention associé au contrat";
        }
        
        if(strpos($this->getData('ref'), "PROV")) {
            
            $ref = BimpTools::getNextRef('contrat', 'ref', $this->getData('objet_contrat') . '{AA}{MM}-00');
            
        } else {
            $ref = $this->getData('ref');
        }
        
        if($this->dol_object->validate($user, $ref) <= 0) {
            $success = 'Le contrat ' . $ref ;
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object));
            return 0;
        }
        
        if(!BimpTools::getValue('use_syntec')) {
            $this->updateField('syntec', null);
        }
        $date = new DateTime($this->getData('date_start'));
        $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
        $instance->set('id_contrat', $this->id);
        $instance->set('next_facture_date', $date->format('Y-m-d H:i:s'));
        $instance->set('next_facture_amount', $this->reste_a_payer());
        $instance->set('validate', 0);
        $instance->create();
            
        
        $this->dol_object->activateAll($user);
        $this->actionGeneratePdf([], $success);
        
    }
    
    public function actionFusion($data, &$success) {
        
        $errors = [];
        
        $ids_selected_contrats = $data['id_objects'];
        $success = "Les contrats ont bien été fusionnés";
        $last_socid = 0;
        
        if(count($ids_selected_contrats) == 1) {
            $errors[] = "Vous ne pouvez pas fusionner qu'un seul contrat";
        }
        
        foreach($ids_selected_contrats as $id) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $id);
            
            if($contrat->getData('statut') == self::CONTRAT_STATUS_BROUILLON) {
                $errors[] = 'Le contrat '. $contrat->getRef() .' ne peut être fusionné car il est en statut brouillon';
            }
            
            if($contrat->getData('fk_soc') != $last_socid && $last_socid > 0) {
                $errors[] = 'Les contrat ne peuvent êtres fusionné car ce n\'est pas le même client';
            }

            $last_socid = $contrat->getData('fk_soc');

        }
        
        if(!count($errors)) {
            
        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        ];
        
    }
    
    public function getBulkActions()
    {
        return array(
            [
                'label'     => 'Fusionner les contrats sélectionnés',
                'icon'      => 'fas_sign-in-alt',
                'onclick'   => 'setSelectedObjectsAction($(this), \'list_id\', \'fusion\', {}, null, null, true)',
                'btn_class' => 'setSelectedObjectsAction'
            ],
        );
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
    
    public function actionGeneratePdfEcheancier($data, &$success) {
        global $langs;
        $success = "PDF de l'échéancier généré avec succès";
    }
    
    /* OTHERS FUNCTIONS */
    
    public function create(&$warnings = array(), $force_create = false) {
        
        if(BimpTools::getValue('use_syntec') && !BimpTools::getValue('syntec')) {
            return 'Vous devez rensseigner un indice syntec';
        }
        
        if((BimpTools::getValue('tacite') == 1 || BimpTools::getValue('tacite') == 1 || BimpTools::getValue('tacite') == 3)) {
            if(BimpTools::getValue('duree_mois') != 12 && BimpTools::getValue('duree_mois') != 24 && BimpTools::getValue('duree_mois') != 36) {
                return 'Vous ne pouvez pas demander un renouvellement TACITE pour des périodes différentes de (12, 24 ou 36 mois)';
            }
        }
            $errors = parent::create($warnings, $force_create);

            $callback = "window.location.href = '" . DOL_URL_ROOT . "/bimpcontract/index.php?fc=contrat&id='".$this->id."';";
            return [
                'success_callback' => $callback,
                'errors'           => $errors,
                'warnings'         => $warnings
            ];
    }
    
    public function fetch($id, $parent = null) {
        $return = parent::fetch($id, $parent);
        $this->autoClose();
        return $return;
    }
    
    public function autoClose(){//passer les contrat au statut clos quand toutes les enssiéne ligne sont close
        if($this->id > 0 && $this->getData("statut") == 1 && $this->getEndDate() < new DateTime()){
            $sql = $this->db->db->query("SELECT * FROM `".MAIN_DB_PREFIX."contratdet` WHERE statut != 5 AND `fk_contrat` = ".$this->id);
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
    
 
    public function renderFilesTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            global $conf;
            $ref = $this->getRef();
            if($this->getData('statut') == self::CONTRAT_STATUS_BROUILLON) {
                $ref = str_replace('(', "_", $ref);
                $ref = str_replace(')', "_", $ref);
            }
            
            $dir = $conf->contrat->dir_output. '/' . $ref;

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
                $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . $ref . urlencode('/');
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
        
        if($this->isLoaded()) {
            $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
        
        if($this->getData('statut') < self::CONTRAT_STATUS_VALIDE) {
            return BimpRender::renderAlerts('Le contrat n\'est pas validé', 'danger', false);
        }
        if(!$this->getData('date_start') || !$this->getData('periodicity') || !$this->getData('duree_mois')) {
            return BimpRender::renderAlerts("Le contrat a été créé avec l'ancienne méthode donc il ne comporte pas d'échéancier", 'warning', false);
        }
        
        $create = false;
        
        if(!$instance->find(Array('id_contrat' => $this->id)) && $this->getData('f_statut') != self::CONTRAT_STATUS_VALIDE) {
            $create = true;
        }
        
        $data = $this->action_line_echeancier($create);
        
            return $instance->displayEcheancier($data);
        }
        
    }
    
    public function is_not_finish() {
        if($this->reste_periode() == 0) {
            return 0;
        }
        return 1;
    }
    
    public function action_line_echeancier() {
        
        $returnedArray = Array(
            'factures_send' => getElementElement('contrat', 'facture', $this->id),
            'reste_a_payer' => $this->reste_a_payer(),
            'reste_periode' => $this->reste_periode(),
            'periodicity' => $this->getData('periodicity')
        );
        
        return (object) $returnedArray;
    }
    
    public function reste_a_payer() {
        $duree_mois = $this->getData('duree_mois');
        $periodicity = $this->getData('periodicity');
        $nombre_periode = $duree_mois / $periodicity;
        $facture_delivred = getElementElement('contrat', 'facture', $this->id);
        if($facture_delivred) {
            foreach($facture_delivred as $link) {
                $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture', $link['d']);
                $montant += $instance->getData('total');
            }
            $return = $this->getTotalContrat() - $montant;
        } else {
            $return = $this->getTotalContrat();  
        }
        return $return; 
    }

    public function reste_periode() {
        
        if($this->isLoaded()) {
             $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
            $instance->find(array('id_contrat' => $this->id));
            $date_1 = new DateTime($instance->getData('next_facture_date'));
            $date_2 = $this->getEndDate();
            if($date_1->format('Y-m-d') == $this->getData('date_start')) {
                $return = $this->getData('duree_mois') / $this->getData('periodicity');
            } else {
                $date_1->sub(new DateInterval('P1D'));
                $interval = $date_1->diff($date_2);
                $return = (($interval->m + $interval->y * 12) / $this->getData('periodicity'));
            }
            return $return;
        }
    }
    
    public function getTotalContrat() {
        $montant = 0;
        foreach ($this->dol_object->lines as $line){
            $montant += $line->total_ht;
        }
        
        return $montant;
    }
    
    public function getTotalDejaPayer($paye_distinct = false) {
        $element_factures = getElementElement('contrat', 'facture', $this->id);
        if(!count($element_factures)) {
            $montant = 0;
        } else {
            foreach($element_factures as $element) {
                $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture', $element['d']);
                if($paye_distinct){
                    if($instance->getData('paye')) {
                        $montant += $instance->getData('total');
                    }
                } else {
                    $montant += $instance->getData('total');
                }
                
            }
            
        }
        return $montant;
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
    
    public function actionCreateAvenant($data, &$success) {
        
        $avLetters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O"];
        
        //$success = "L'avenant N°" . $this->getData('ref') . " à été créé avec succes";
        $contra_source = ($this->getData('contrat_source') ? $this->getData('contrat_source') : $this->id);
        $contrat_initial = $this->id;
        
        $first_contrat = $this;
        
        if($contrat_initial != $this->id) {
            $first_contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $contrat_initial);
        }
        
        $numero_next = $first_contrat->getListCount(['contrat_source' => $first_contrat->id]) + 1;
        $next_ref = $first_contrat->getData('ref') . "-" . $avLetters[$numero_next];
        
        if($clone = $this->dol_object->createFromClone($this->getData('fk_soc'))) {
            $next_contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $clone);
            addElementElement('contrat', 'contrat', $this->id, $next_contrat->id);
            $next_contrat->updateField('contrat_source', $first_contrat->id);
            $next_contrat->updateField('date_contrat', NULL);
            $next_contrat->updateField('ref', $first_contrat->getData('ref') . "-" . $avLetters[$numero_next]);
            
        }
    }
    
    public function getContratSource() {
        
        if(!is_null($this->getData('contrat_source'))) {
            $source = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('contrat_source'));
            return $source->getNomUrl();
        }
        
        return 'Ce contrat est le contrat initial';
        
    }
    
    public function createFromPropal($propal, $data) {
        //print_r($data); die();
        global $user;
        $new_contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
        $new_contrat->set('fk_soc', $data['fk_soc']);
        $new_contrat->set('date_contrat', null);
        $new_contrat->set('date_start', $data['valid_start']);
        $new_contrat->set('objet_contrat', $data['objet_contrat']);
        $new_contrat->set('fk_commercial_signature', $data['commercial_signature']);
        $new_contrat->set('fk_commercial_suivi', $data['commercial_suivi']);
        $new_contrat->set('periodicity', $data['periodicity']);
        $new_contrat->set('gti', $data['gti']);
        $new_contrat->set('duree_mois', $data['duree_mois']);
        $new_contrat->set('tacite', $data['re_new']);
        $new_contrat->set('moderegl', $data['fk_mode_reglement']);
        $new_contrat->set('note_public', $data['note_public']);
        $new_contrat->set('ref_ext', $data['ref_ext']);
        if($data['use_syntec'] == 1) {
            $new_contrat->set('syntec', BimpCore::getConf('current_indice_syntec'));
        }
//        echo '<pre>';
//        print_r($commande->dol_object->lines); die();
        if($new_contrat->create() > 0) {
            foreach($propal->dol_object->lines as $line) {                
                $produit = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                if($produit->getData('fk_product_type') == 1 && $line->total_ht != 0) {
                    $description = ($line->desc) ? $line->desc : $line->libelle;
                    $end_date = new DateTime($data['valid_start']);
                    $end_date->add(new DateInterval("P" . $data['duree_mois'] . "M"));
                    $new_contrat->dol_object->addLine($description, $line->subprice, $line->qty, $line->tva_tx, 0, 0, $line->fk_product, $line->remise_percent, $data['valid_start'], $end_date->format('Y-m-d'), 'HT', 0.0, 0, null, 0, 0, null, $line->rang);                 
                }
            }
            addElementElement('propal', 'contrat', $propal->id, $new_contrat->id);
            return $new_contrat->id;
        } else {
            return -1;
        }
    }
    
//    public function newRef($start_ref) {
//        
//        $count_contrat = count($this->db->getRows('contrat', "ref LIKE '%$start_ref%'")) + 1;
//        
//        if($count_contrat < 10) {
//            $add_zero = "000";
//        } elseif($count_contrat > 10 && $count_contrat < 100) {
//            $add_zero = "00";
//        } else {
//            $add_zero = '0';
//        }
//        return $start_ref . $add_zero . $count_contrat;
//    }
    
    public function renderHeaderExtraLeft()
    {   
        
        $html = '';
        
        if($this->isLoaded()) {
            if ($this->dol_object->element == 'contrat' && BimpTools::getContext() != 'public') {
                $userCreationContrat = new User($this->db->db);
                $userCreationContrat->fetch((int) $this->getData('fk_user_author'));

                $html .= '<div class="object_header_infos">';
                $create = new DateTime($this->getData('datec'));
                $html .= 'Créé le <strong >' . $create->format('d / m / Y') . '</strong>';
                $html .= ' par <strong >' . $userCreationContrat->getNomUrl(1) . '</strong>';
                $html .= '</div>';
            }
            if($this->getData('statut') == self::CONTRAT_STATUS_VALIDE) {
                
                $now = new DateTime();
                $interval = $now->diff($this->getEndDate());
                //print_r($interval);
                $intervale_days = $interval->days;
                //$intervale_days = 14;
                
                $renderAlert = true;
                $hold= false;
                if($intervale_days < 365) {
                    $html .= '<div class="object_header_infos">';
                    if($intervale_days <= 365 && $intervale_days > 90) {
                        $renderAlert = false;
                        $alerte_type = 'info';
                    } elseif($intervale_days <= 90 && $intervale_days > 30) {
                        $alerte_type = 'info';
                    } elseif($intervale_days <= 30 && $intervale_days > 15) {
                        $alerte_type = 'warning';
                    } else {
                        $alerte_type = 'danger';
                    }
                    
                    if(!$this->getData('duree_mois') || !$this->getData('date_start')) {
                        
                        
                        
                        $val = $this->db->getMax('contratdet', 'date_fin_validite', 'fk_contrat = ' . $this->id);
                        
                        $date_fin = new DateTime($val);
                        
                        $html .= BimpRender::renderAlerts('<h5>Ceci est un ancien contrat dont la date d\'expiration est le : <b> '.$date_fin->format('d / m / Y').' </b></h5> ', 'info', false);
                        $renderAlert = false;
                        $hold = true;
                    }

                    if($renderAlert)
                        $html .= BimpRender::renderAlerts('Ce contrat expire dans <strong>'.$intervale_days.' jours</strong>', $alerte_type, false);
                    elseif(!$hold)
                        $html .= 'Ce contrat expire dans <strong>'.$intervale_days.' jours</strong>';
                    $html .= '</div>';
                }
                
            }
        }
        return $html;
    }
    
    public function displayCommercial() {

        BimpTools::loadDolClass('user');
        $commercial = new User($this->db->db);
        $commercial->fetch($this->getData('fk_commercial_suivi'));
        
        return $commercial->getNomUrl(1);
        
    }
    
    public function isSigned($display = null) {

        if (!is_null($this->getData('date_contrat'))) {
            return (is_null($display) ? 1 : "<b class='success'>OUI</b>");
        } else {
            return (is_null($display) ? 0 : "<b class='danger'>NON</b>");
        }
        
    }
    
    public function relance_renouvellement_commercial() {
  
    }

//    public function getEmailUsersFromArray()
//    {
//        global $user, $langs, $conf;
//
//        $emails = array();
//
//        // User connecté: 
//
//        if (!empty($user->email)) {
//            $emails[$user->email] = $user->getFullName($langs) . ' (' . $user->email . ')';
//        }
//
//        if (!$user->admin)
//            return $emails;
//
//        if (!empty($user->email_aliases)) {
//            foreach (explode(',', $user->email_aliases) as $alias) {
//                $alias = trim($alias);
//                if ($alias) {
//                    $alias = str_replace('/</', '', $alias);
//                    $alias = str_replace('/>/', '', $alias);
//                    if (!isset($emails[$alias])) {
//                        $emails[$alias] = $user->getFullName($langs) . ' (' . $alias . ')';
//                    }
//                }
//            }
//        }
//
//        // Société: 
//
//        if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL)) {
//            $emails[$conf->global->MAIN_INFO_SOCIETE_MAIL] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $conf->global->MAIN_INFO_SOCIETE_MAIL . ')';
//        }
//
//        if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES)) {
//            foreach (explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES) as $alias) {
//                $alias = trim($alias);
//                if ($alias) {
//                    $alias = str_replace('/</', '', $alias);
//                    $alias = str_replace('/>/', '', $alias);
//                    if (!isset($emails[$alias])) {
//                        $emails[$alias] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $alias . ')';
//                    }
//                }
//            }
//        }
//
//        // Contacts pièce: 
//
//        if ($this->isLoaded()) {
//            $c_user = new User($this->db->db);
//            $contacts = $this->dol_object->liste_contact(-1, 'internal');
//            foreach ($contacts as $item) {
//                $c_user->fetch($item['id']);
//                if (BimpObject::objectLoaded($c_user)) {
//                    if (!empty($c_user->email) && !isset($emails[$c_user->email])) {
//                        $emails[$c_user->email] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $c_user->email . ')';
//                    }
//
//                    if (!empty($c_user->email_aliases)) {
//                        foreach (explode(',', $c_user->email_aliases) as $alias) {
//                            $alias = trim($alias);
//                            if ($alias) {
//                                $alias = str_replace('/</', '', $alias);
//                                $alias = str_replace('/>/', '', $alias);
//                                if (!isset($emails[$alias])) {
//                                    $emails[$alias] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $alias . ')';
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        return $emails;
//    }
//    
//    public function renderMailToInputs($input_name)
//    {
//        $emails = $this->getMailsToArray();
//
//        $html = '';
//
//        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array(
//                    'options'     => $emails,
//                    'extra_class' => 'emails_select principal'
//        ));
//
//
//        $html .= '<p class="inputHelp selectMailHelp">';
//        $html .= 'Sélectionnez une adresse e-mail puis cliquez sur "Ajouter"';
//        $html .= '</p>';
//
//        $html .= '<div class="mail_custom_value" style="display: none; margin-top: 10px">';
//        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
//        $html .= '<p class="inputHelp">Entrez une adresse e-mail valide puis cliquez sur "Ajouter"</p>';
//        $html .= '</div>';
//
//        return $html;
//    }
//    
//    public function getMailsToArray()
//    {
//        global $user, $langs;
//
//        $client = $this->getChildObject('client');
//
//        $emails = array(
//            ""           => "",
//            $user->email => $user->getFullName($langs) . " (" . $user->email . ")"
//        );
//
//        if ($this->isLoaded()) {
//            $contacts = $this->dol_object->liste_contact(-1, 'external');
//            foreach ($contacts as $item) {
//                if (!isset($emails[(int) $item['id']])) {
//                    $emails[(int) $item['id']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
//                }
//            }
//        }
//
//        if (BimpObject::objectLoaded($client)) {
//            $client_emails = self::getSocieteEmails($client->dol_object);
//            if (is_array($client_emails)) {
//                foreach ($client_emails as $value => $label) {
//                    if (!isset($emails[$value])) {
//                        $emails[$value] = $label;
//                    }
//                }
//            }
//        }
//
//        if ($this->isLoaded()) {
//            $contacts = $this->dol_object->liste_contact(-1, 'internal');
//            foreach ($contacts as $item) {
//                if (!isset($emails[$item['email']])) {
//                    $emails[$item['email']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
//                }
//            }
//        }
//
//        $emails['custom'] = 'Autre';
//
//        return $emails;
//    }
//    
//    public function getDefaultMailTo()
//    {
//        return array();
//    }
//    
//   public function getEmailModelsArray()
//    {
//        if (!static::$email_type) {
//            return array();
//        }
//
//        return self::getEmailTemplatesArray(static::$email_type, true);
//    }
//    
    public function isBySocId() {
        if(isset($_REQUEST['socid']) && $_REQUEST['socid'] > 0) {
            return 1;
        }
        return 0;
    }
    
}

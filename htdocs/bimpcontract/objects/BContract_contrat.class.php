<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BContract_contrat extends BimpDolObject {

    // Les status
    CONST CONTRAT_STATUS_BROUILLON = 0;
    CONST CONTRAT_STATUS_VALIDE = 1;
    CONST CONTRAT_STATUS_CLOS = 2;
    // Les périodicitées
    CONST CONTRAT_PERIOD_MENSUELLE = 1;
    CONST CONTRAT_PERIOD_TRIMESTRIELLE = 3;
    CONST CONTRAT_PERIOD_SEMESTRIELLE = 6;
    CONST CONTRAT_PERIOD_ANNUELLE = 12;
    // Les délais d'intervention
    CONST CONTRAT_DELAIS_2_HEURES = 2;
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
        self::CONTRAT_DELAIS_2_HEURES => '2 heures ouvrées',
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
    
    function __construct($module, $object_name) {
        if(BimpTools::getContext() == 'public') {
            $this->redirectMode = 4;
        }
        return parent::__construct($module, $object_name);
    }
    
    public function displayRef() {
        return $this->getData('ref') . ' - ' . $this->getData('objet_contrat');
    }

    public function displayEndDate() {
        $fin = $this->getEndDate();
        if($fin > 0)
            return $fin->format('d/m/Y');
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

    public function getActionsButtons() {
        $buttons = array();
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
        return $buttons;
    }
    
    public function actionValidation($data, &$success) {
        $instance = $this->getInstance('bimpcontract', 'BContract_echeancier');
        $instance->find(Array('id_contrat' => $this->id));
        if($instance->updateLine($this->id)) {
            $success = 'Contrat validé et échéancier créer';
            $this->updateField('statut', self::CONTRAT_STATUS_VALIDE);
        }
    }
    
    public function canEdit(){
        if($this->getData("statut") != self::CONTRAT_STATUS_CLOS)
            return true;
        return false;
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

    public function getName() {
        return $this->getData('objet_contrat');
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
    
}

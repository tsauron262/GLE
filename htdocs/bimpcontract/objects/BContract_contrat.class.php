<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

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

    public function displayRef() {
        return $this->getData('ref');
        
    }

    public function displayEndDate($date_start, $duree_mois) {
        $date_start = $date_start['value'];
        $date = strtotime($date_start);
        $dateTime = new DateTime();
        $dateTime->setTimestamp($date);
        $dateTime->add(new DateInterval("P" . $duree_mois['value'] . "M"));
        $dateTime->sub(new DateInterval("P1D"));
        return $dateTime->format('d / m / Y');
    }

    public function getActionsButtons() {
        $buttons = array();
        if ($this->getData('statut') != self::CONTRAT_STATUS_VALIDE) {
            $buttons[] = array(
                'label' => 'Valider le contrat',
                'icon' => 'fas_check',
                'onclick' => $this->getJsNewStatusOnclick(self::CONTRAT_STATUS_VALIDE)
            );
        }
        return $buttons;
    }
    
    public function canClientView() {
        return true;
    }
    
    
//    public function isValide() {
//        global $db;
//        $bimp = new BimpDb($db);
//        $in_covers = Array();
//        $liste_contrat = $bimp->getRows('contrat', 'fk_soc = ' . $this->getData('attached_societe'));
//        foreach ($liste_contrat as $contrat) {
//            $current = new Contrat($db);
//            $current->fetch($contrat->rowid);
//            $extra = (object) $current->array_options;
//
//            if ($extra->options_date_start) { // Nouveau contrat
//                $debut = new DateTime();
//                $fin = new DateTime();
//                $debut->setTimestamp($extra->options_date_start);
//                $fin->setTimestamp($extra->options_date_start);
//                $fin = $fin->add(new DateInterval("P" . $extra->options_duree_mois . "M"));
//                $fin = $fin->sub(new DateInterval("P1D"));
//
//                $fin = strtotime($fin->format('Y-m-d'));
//                $debut = strtotime($debut->format('Y-m-d'));
//                $aujourdhui = strtotime(date('Y-m-d'));
//
//                if ($fin - $aujourdhui > 0) {
//                    $in_covers[$current->id] = $current->ref;
//                }
//            } else {
//                foreach ($current->lines as $line) {
//                    if ($line->statut == 4) {
//                        $in_covers[$current->id] = $current->ref;
//                    }
//                }
//            }
//        }
//        return $in_covers;
//    }
    

}

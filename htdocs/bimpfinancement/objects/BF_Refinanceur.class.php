<?php

class BF_Refinanceur extends BimpObject
{
    public static $coefALaCon = 0.0833333333333;

    const BF_REFINANCEUR_RIEN = 0;
    const BF_REFINANCEUR_ETUDE = 1;
    const BF_REFINANCEUR_ACCORD = 2;
    const BF_REFINANCEUR_REFUS = 3;
    const BF_REFINANCEUR_SOUS_CONDITION = 4;
    
    public static $payments = array(
        0 => '-',
        1 => 'Prélévement auto',
        2 => 'Virement',
        3 => 'Mandat administratif'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1  => 'mensuel',
        3  => 'trimestriel',
        6  => 'semestriel',
        12 => 'annuel'
    );
    public static $period_label = array(
        1  => 'mois',
        3  => 'trimestre',
        6  => 'semestre',
        12 => 'an'
    );
    public static $period_label_plur = array(
        1  => 'mois',
        3  => 'trimestres',
        6  => 'semestres',
        12 => 'ans'
    );
    public static $status_list = array(
        // Oblkigatoirement une constante pour self::
        self::BF_REFINANCEUR_RIEN => array('label' => '-', 'classes' => array('important')),
        self::BF_REFINANCEUR_ACCORD => array('label' => 'Accord', 'classes' => array('success')),
        self::BF_REFINANCEUR_REFUS => array('label' => 'Refus', 'classes' => array('danger')),
        self::BF_REFINANCEUR_ETUDE => array('label' => '&Eacute;tude', 'classes' => array('warning')),
        self::BF_REFINANCEUR_SOUS_CONDITION => array('label' => 'Sous-condition', 'classes' => array('warning')),
    );

    public static $names = array(
        0 => '-',
        228225 => 'BNP',
        233883 => 'FRANFINANCE',
        231492 => 'GE - CM-CIC BAIL',
        234057 => 'GRENKE',
        5 => 'LIXXBAIL',
        230634 => 'LOCAM'
    );
    
    public function getTotalLoyer(){
        return $this->getData("quantity") * $this->getData("amount_ht");
    }
    
    public function displayLoyerSuggest($display_name = 'nom_url', $display_input_value = true, $no_html = false){
        $totalEmprunt = $this->getTotalEmprunt();
        $loyer = $this->caclulLoyer($this->getTotalEmpruntDemande());
        
        $info = "Total emprunt : ".price($totalEmprunt);
        $info .= "<br />Coût banque : ".price($this->getCoutBanqueWithCoeficient() + $this->getCoutBanqueWithTaux());
        $info .= '<br/>Total remboursement : '.price($this->getTotalLoyer());
        $verif = ($this->getCoutBanqueWithCoeficient() + $this->getCoutBanqueWithTaux()) > 0 
                && ($this->getCoutBanqueWithCoeficient() + $this->getCoutBanqueWithTaux()) < ($totalEmprunt / 2);
        $html = "";
        $html .= '<a type="button" class="'.($verif? '': 'red').' btn btn-default bs-popover"';
        $html .= ' onclick="majLoyerAuto($(this), '.$loyer.')"';
        $html .= ' data-toggle="popover"';
        $html .= ' data-trigger="hover"';
        $html .= ' data-html="true"';
        $html .= ' data-content="'.$info.'"';
        $html .= ' data-container="body"';
        $html .= ' data-placement="top">';
        $html .= '<i class="fas fa-question-circle iconLeft"></i>';
        $html .= price($loyer);
        $html .= '</a>';
        return $html;
    }
    
    public function getTotalEmprunt(){
        return $this->getTotalLoyer() - ($this->getCoutBanqueWithCoeficient() + $this->getCoutBanqueWithTaux());
    }
    
    public function getNbMois(){
        return $this->getData("quantity") * $this->getData("periodicity");
    }
    
    public function isEchouar(){//todo
        $demande = $this->getParentInstance();
        return ($demande->getData("mode_calcul") == 2);
    }
    
    public function getTotalEmpruntDemande(){
        $demande = $this->getParentInstance();
        return $demande->getTotalDemande();
    }
    
    public function getCoutBanqueWithTaux(){
        $taux = $this->getData("rate");
        if($taux > 0){
            $tauxPM = $taux / 100 / 12;
            $echoirCalc = 1;
            if ($this->isEchouar()) {
                $echoirCalc = 1 + $taux / 100 * self::$coefALaCon;
            }
            $capital = $this->getData("amount_ht") / ($tauxPM / (1 - pow((1 + $tauxPM), -($this->getNbMois())))  / $echoirCalc) / $this->getData('periodicity');
            return $this->getTotalLoyer() - $capital;
        }
        return 0;
    }
    
    public function getCoutBanqueWithCoeficient(){
        $coef = $this->getData("coef");
        if($coef > 0){
            $loyerTest = $this->getData("amount_ht");
            $nbPeriode = $this->getData("quantity");
            $nbMois = $this->getNbMois();

            $total = $loyerTest*$nbMois;

            //cherchon total
            for($i = 1; $i <= 100; $i++){
                $loyerT = $total * $coef / 100;
                $coefCorrec = $nbPeriode * (1 - $i/100);
                if($loyerT == $loyerTest){
                    break;
                }
                elseif($loyerT < $loyerTest){
                    $total += ($loyerTest - $loyerT)*$coefCorrec;
                }
                elseif($loyerT > $loyerTest){
                    $total += ($loyerTest - $loyerT)*$coefCorrec;
                }
//echo "<br/>oooo".$total;
            }

            return ($total * $nbPeriode * $coef  / 100) - $total;
        }
        return 0;
    }

    
    public function caclulLoyer($capital){
        $nbPeriode = $this->getData("quantity");
        $dureePeriode = $this->getData("periodicity");
        $nbMois = $this->getNbMois();
        $coef = $this->getData("coef");
        if ($this->getData("rate") > 0){
            $tauxPM = $this->getData("rate") / 100 / 12;
            $echoirCalc = 1;
            if ($this->isEchouar()) {
                $echoirCalc = 1 + $this->getData("rate") / 100 * self::$coefALaCon;
            }
             $loyer = $capital * ($dureePeriode * (($tauxPM / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc)));
//             $loyer = $capital * ($tauxPM / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc) * $dureePeriode;
//             $loyer = (($capital * $tauxPM) / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc) * $dureePeriode;
        }
        else{
            $loyer = $capital / $nbPeriode;
        }
        
        if($coef != 0){
            $interet = $capital - $capital * ($nbMois / $dureePeriode * $coef / 100);
            $loyer = ($capital - $interet)/$nbPeriode;
        }

        return $loyer;
    }

}

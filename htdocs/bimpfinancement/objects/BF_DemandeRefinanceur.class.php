<?php

class BF_DemandeRefinanceur extends BimpObject {

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
        1 => 'Mensuelle',
        3 => 'Trimestrielle',
        6 => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1 => 'mensuel',
        3 => 'trimestriel',
        6 => 'semestriel',
        12 => 'annuel'
    );
    public static $period_label = array(
        1 => 'mois',
        3 => 'trimestre',
        6 => 'semestre',
        12 => 'an'
    );
    public static $period_label_plur = array(
        1 => 'mois',
        3 => 'trimestres',
        6 => 'semestres',
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
    protected $calcValues = array(
        'cout_with_coef' => 0,
        'cout_with_tx' => 0,
        'nb_mois' => 0,
        'total_loyer' => 0,
    );

    public function isCreatable($force_create = false) {
        $demande = $this->getParentInstance();

        if (BimpObject::objectLoaded($demande)) {
            if (!(int) $demande->getData('accepted')) {
                return 1;
            }
        }

        return 0;
    }

    public function isEditable($force_edit = false) {
        return $this->isCreatable($force_edit);
    }

    public function isDeletable($force_delete = false) {
        return $this->isCreatable($force_delete);
    }

    public function isEchouar() {
        //todo
        $demande = $this->getParentInstance();
        return ($demande->getData("mode_calcul") == 2);
    }

    public function getCalcValues() {
        if (is_null($this->calcValues)) {
            if ($this->isLoaded() && BimpTools::isSubmit('new_values/' . $this->id)) {
                $amount_ht = BimpTools::getValue('new_values/' . $this->id . '/amount_ht', $this->getData('amount_ht'));
                $coef = BimpTools::getValue('new_values/' . $this->id . '/coef', $this->getData('coef'));
                $taux = BimpTools::getValue('new_values/' . $this->id . '/rate', $this->getData('rate'));
                $nbPeriodes = BimpTools::getValue('new_values/' . $this->id . '/quantity', $this->getData('quantity'));
                $dureePeriode = BimpTools::getValue('new_values/' . $this->id . '/periodicity', $this->getData('periodicity'));
            } else {
                $amount_ht = $this->getData("amount_ht");
                $coef = $this->getData('coef');
                $taux = $this->getData("rate");
                $nbPeriodes = $this->getData('quantity');
                $dureePeriode = $this->getData('periodicity');
            }

            $nbMois = $nbPeriodes * $dureePeriode;
            $total_loyer = $nbPeriodes * $amount_ht;
            $coutWithTx = 0;
            $coutWithCoef = 0;
            $loyer = 0;
            $totalDemande = $this->getTotalDemande();

            $isEchoir = $this->isEchouar();


            $parent = $this->getParentInstance();
            $vr = $parent->getData("vr_vente");
            $tauxPM = $taux / 100 / (12/$dureePeriode);

            $loyer = $this->vpm($tauxPM, 0, $nbPeriodes, $totalDemande, -$vr, $isEchoir);

            $totalEmprunt = $this->va($tauxPM, 0, $nbPeriodes, $amount_ht, -$vr, $isEchoir);
//            die($totalEmprunt);
            if($coef > 0){
                $coutWithCoef = $total_loyer - $totalEmprunt;
            }
            else
                $coutWithTx = $total_loyer - $totalEmprunt;

            $this->calcValues = array(
                'cout_with_coef' => $coutWithCoef,
                'cout_with_tx' => $coutWithTx,
                'cout_total' => $coutWithCoef + $coutWithTx,
                'nb_mois' => $nbMois,
                'loyer' => $loyer,
                'total_loyer' => $total_loyer,
                'total_emprunt' => $totalEmprunt
            );
        }

        return $this->calcValues;
    }

//    public function getCalcValues()
//    {
//        if (is_null($this->calcValues)) {
//            if ($this->isLoaded() && BimpTools::isSubmit('new_values/' . $this->id)) {
//                $amount_ht = BimpTools::getValue('new_values/' . $this->id . '/amount_ht', $this->getData('amount_ht'));
//                $coef = BimpTools::getValue('new_values/' . $this->id . '/coef', $this->getData('coef'));
//                $taux = BimpTools::getValue('new_values/' . $this->id . '/rate', $this->getData('rate'));
//                $nbPeriodes = BimpTools::getValue('new_values/' . $this->id . '/quantity', $this->getData('quantity'));
//                $dureePeriode = BimpTools::getValue('new_values/' . $this->id . '/periodicity', $this->getData('periodicity'));
//            } else {
//                $amount_ht = $this->getData("amount_ht");
//                $coef = $this->getData('coef');
//                $taux = $this->getData("rate");
//                $nbPeriodes = $this->getData('quantity');
//                $dureePeriode = $this->getData('periodicity');
//            }
//
//            $nbMois = $nbPeriodes * $dureePeriode;
//            $total_loyer = $nbPeriodes * $amount_ht;
//            $coutWithTx = 0;
//            $coutWithCoef = 0;
//            $loyer = 0;
//            $totalDemande = $this->getTotalDemande();
//
//            $isEchoir = $this->isEchouar();
//
//            // Cout banque avec taux: 
//            if ($taux > 0) {
//                $tauxPM = $taux / 100 / 12;
//                $echoirCalc = 1;
//                if ($isEchoir) {
//                    $echoirCalc = 1 + $taux / 100 * self::$coefALaCon;
//                }
//                $capital = $amount_ht / ($tauxPM / (1 - pow((1 + $tauxPM), -($nbMois))) / $echoirCalc) / $dureePeriode;
//                $coutWithTx = $total_loyer - $capital;
//            }
//
//            // Cout banque avec coef:
//            if ($coef > 0) {
//                $loyerTest = $amount_ht;
//                $total = $loyerTest * $nbMois;
//
//                //cherchons total
//                for ($i = 1; $i <= 100; $i++) {
//                    $loyerT = $total * $coef / 100;
//                    $coefCorrec = $nbPeriodes * (1 - $i / 100);
//                    if ($loyerT == $loyerTest) {
//                        break;
//                    } elseif ($loyerT < $loyerTest) {
//                        $total += ($loyerTest - $loyerT) * $coefCorrec;
//                    } elseif ($loyerT > $loyerTest) {
//                        $total += ($loyerTest - $loyerT) * $coefCorrec;
//                    }
//                }
//
//                $coutWithCoef = ($total * $nbPeriodes * $coef / 100) - $total;
//            }
//
//            // Calcul Loyer: 
//            if ($taux > 0) {
//                $tauxPM = $taux / 100 / 12;
//                $echoirCalc = 1;
//                if ($isEchoir) {
//                    $echoirCalc = 1 + $taux / 100 * self::$coefALaCon;
//                }
//                $loyer = $totalDemande * ($dureePeriode * (($tauxPM / (1 - pow((1 + $tauxPM), -($nbMois))) / $echoirCalc)));
////             $loyer = $totalDemande * ($tauxPM / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc) * $dureePeriode;
////             $loyer = (($totalDemande * $tauxPM) / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc) * $dureePeriode;
//            } else {
//                $loyer = $totalDemande / $nbPeriodes;
//            }
//
//            if ($coef != 0) {
//                $interet = $totalDemande - ($totalDemande * ($nbMois / $dureePeriode * $coef / 100));
//                $loyer = ($totalDemande - $interet) / $nbPeriodes;
//            }
//
//            // Emprunt total: 
//            $totalEmprunt = $total_loyer - ($coutWithCoef + $coutWithTx);
//
//            $this->calcValues = array(
//                'cout_with_coef' => $coutWithCoef,
//                'cout_with_tx'   => $coutWithTx,
//                'cout_total'     => $coutWithCoef + $coutWithTx,
//                'nb_mois'        => $nbMois,
//                'loyer'          => $loyer,
//                'total_loyer'    => $total_loyer,
//                'total_emprunt'  => $totalEmprunt
//            );
//        }
//
//        return $this->calcValues;
//    }

    public function getTotalLoyer() {
        return $this->getData("quantity") * $this->getData("amount_ht");
    }

    public function displayLoyerSuggest() {
        $calc_values = $this->getCalcValues(true);

        $info = "Total emprunt : " . price($calc_values['total_emprunt']);
        $info .= "<br />Coût banque : " . price($calc_values['cout_total']);
        $info .= '<br/>Total remboursement : ' . price($calc_values['total_loyer']);

        $verif = ($calc_values['cout_total'] > 0) && ($calc_values['cout_total'] < ($calc_values['total_emprunt'] / 2));
        $verif2 = ((float) round($calc_values['loyer'], 2) === (float) round($this->getData('amount_ht'), 2));

        $html = "";

        $html .= '<span type="button" class="loyer_calc_btn btn btn-' . ($verif ? ($verif2 ? 'success' : 'default') : 'danger') . ' bs-popover"';
        $html .= ' onclick="majLoyerAuto($(this), ' . $calc_values['loyer'] . ');"';
        $html .= BimpRender::renderPopoverData($info, 'top', 'true');
        $html .= '>';

        $html .= BimpTools::displayMoneyValue($calc_values['loyer']);

        $html .= '<i class="fas fa-question-circle iconRight"></i>';
        $html .= '</span>';

        return $html;
    }

    public function getTotalEmprunt() {
        $calcValues = $this->getCalcValues();
        if (isset($calcValues['total_emprunt'])) {
            return $calcValues['total_emprunt'];
        }
        return 0;
    }

    public function getNbMois() {
        return $this->getData("quantity") * $this->getData("periodicity");
    }

    public function getTotalDemande() {
        $demande = $this->getParentInstance();

        if (BimpObject::objectLoaded($demande)) {
            return $demande->getTotalDemande();
        }

        return 0;
    }

    public function getCoutBanqueWithTaux() {
        $calcValues = $this->getCalcValues();
        if (isset($calcValues['cout_with_tx'])) {
            return $calcValues['cout_with_tx'];
        }
        return 0;
    }

    public function getCoutBanqueWithCoeficient() {
        $calcValues = $this->getCalcValues();
        if (isset($calcValues['cout_with_coef'])) {
            return $calcValues['cout_with_coef'];
        }
        return 0;
    }

    public function getLoyer() {
        $calcValues = $this->getCalcValues();
        if (isset($calcValues['loyer'])) {
            return $calcValues['loyer'];
        }
        return 0;
    }

    public function displayRefinanceur() {
        if ($this->isLoaded()) {
            $refinanceur = BimpCache::getBimpObjectInstance($this->module, 'BF_Refinanceur', (int) $this->getData('id_refinanceur'));

            if (!$refinanceur->isLoaded()) {
                return $this->renderChildUnfoundMsg('id_refinanceur', $refinanceur);
            } else {
                return $refinanceur->getName();
            }
        }

        return '';
    }

    public static function getRefinanceursArray($include_empty = true) {
        $cache_key = 'bf_refinanceurs_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $instance = BimpObject::getInstance('bimpfinancement', 'BF_Refinanceur');

            foreach ($instance->getList(array(), null, null, 'id', 'asc', 'array', array('id', 'id_societe')) as $item) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $item['id_societe']);
                if ($soc->isLoaded()) {
                    self::$cache[$cache_key][(int) $item['id']] = $soc->getName();
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Overrides: 

    public function reset() {
        $this->calcValues = null;

        parent::reset();
    }

    public function onSave(&$errors = array(), &$warnings = array()) {
        $this->calcValues = null;
    }

    function vpm($taux, $coef, $npm, $va, $vc = 0, $type = 0) {//Calcul loyé avec taux et capital
        if ($coef > 0) {
            return $coef / 100 * ($va);
        }


        if (!is_numeric($taux) || !is_numeric($npm) || !is_numeric($va) || !is_numeric($vc)):
            return false;
        endif;

        if ($type > 1 || $type < 0):
            return false;
        endif;

        $tauxAct = pow(1 + $taux, -$npm);

        if ((1 - $tauxAct) == 0):
            return 0;
        endif;

        $vpm = ( ($va + ($vc * $tauxAct)) * $taux / (1 - $tauxAct) ) / (1 + $taux * $type);
        return $vpm;
    }

    function va($taux, $coef, $npm, $vpm, $vc = 0, $type = 0) {
        if ($coef > 0)
            return $vpm / $coef * 100;

        if (!is_numeric($taux) || !is_numeric($npm) || !is_numeric($vpm) || !is_numeric($vc)):
            return false;
        endif;

        if ($type > 1 || $type < 0):
            return false;
        endif;

        $tauxAct = pow(1 + $taux, -$npm);

        if ((1 - $tauxAct) == 0):
            return 0;
        endif;

        $va = $vpm * (1 + $taux * $type) * (1 - $tauxAct) / $taux - $vc * $tauxAct;
        return $va;
    }

}

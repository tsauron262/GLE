<?php

class BFTools
{

    public static $periodicities_masc = array(
        1  => 'mensuel',
        3  => 'trimestriel',
        6  => 'semestriel',
        12 => 'annuel'
    );

    public static function calc_vpm($taux, $npm, $va, $vc = 0, $type = 0)
    {
        // VPM = valeur de paiement => Calcul du montant d'un remboursement selon taux et capital
        // Taux: taux d'intérêt
        // npm : Nombre de remboursements (périodes). 
        // va : Montant de l'emprunt (Valeur actuelle)
        // vc : Valeur capitalisée (résiduelle)
        // type: 0 = à terme échu (fin de période) / 1 = à terme à échoir (début de période)

        if (!is_numeric($taux) || !is_numeric($npm) || !is_numeric($va) || !is_numeric($vc)) {
            return false;
        }

        if ($type > 1 || $type < 0) {
            return false;
        }

        $tauxAct = pow(1 + $taux, -$npm);

        if ((1 - $tauxAct) == 0) {
            return 0;
        }

        return (($va + ($vc * $tauxAct)) * $taux / (1 - $tauxAct) ) / (1 + $taux * $type);
    }

    public static function calc_va($taux, $coef, $npm, $vpm, $vc = 0, $type = 0)
    {
        // VA = Montant de l'emprunt (Valeur Actuelle)
        // Taux: taux d'intérêt
        // npm : Nombre de remboursements. 
        // va : Montant de l'emprunt (Valeur actuelle)
        // vc : Valeur capitalisée (résiduelle)
        // type: 0 = à terme échu (fin de période) / 1 = à terme à échoir (début de période)

        if ($coef > 0) {
            return ($vpm / $coef) * 100;
        }

        if (!is_numeric($taux) || !is_numeric($npm) || !is_numeric($vpm) || !is_numeric($vc)) {
            return false;
        }

        if ($type > 1 || $type < 0) {
            return false;
        }

        $tauxAct = pow(1 + $taux, -$npm);

        if ((1 - $tauxAct) == 0) {
            return 0;
        }

        return $vpm * (1 + $taux * $type) * (1 - $tauxAct) / $taux - $vc * $tauxAct;
    }

    public static function getCalcValues($total_materiels, $total_services, $tx_cession, $nb_mois, $marge, $vr_achat, $mode_calcul, $periodicity, &$errors = array())
    {
        if (!(int) $periodicity) {
            $errors[] = 'Périodicité non définie';
        }

        if (!(float) $tx_cession) {
            $errors[] = 'Taux de cession non défini';
        }

        if (!(int) $nb_mois) {
            $errors[] = 'Nombre de périodes non définis';
        }

        $total_demande = $total_materiels + $total_services;
        if ($total_demande < 1801) {
            $va = (100 * (1 + $marge)) + (6.5 * (1800 - $total_demande) / 1000);
        } else {
            $va = 100 * (1 + $marge);
        }
        $vc = $vr_achat * -1;
        $coef = self::calc_vpm($tx_cession / 100 / 12, $nb_mois, $va, $vc, $mode_calcul);

        $loyer_evo_mensuel = ($total_demande * $coef) / 100;
        $loyer_dyn_mensuel = ($total_materiels / $nb_mois) + ($total_services * ($coef / 100));

        $coef_loyer_suppl = BFTools::calc_vpm($tx_cession / 100 / 12, $nb_mois + 12, $va, $vc, (int) $mode_calcul);
        $loyer_dyn_suppl_mensuel = (($total_demande * ($coef_loyer_suppl / 100) * ($nb_mois + 12)) - ($loyer_dyn_mensuel * $nb_mois)) / 12;

        $first_loyer_maj = $total_demande * 0.1;

        return array(
            'total_materiels'         => $total_materiels,
            'total_services'          => $total_services,
            'total_demande'           => $total_demande,
            'marge'                   => $marge,
            'tx_cession'              => $tx_cession,
            'nb_mois'                 => $nb_mois,
            'vr_achat'                => $vr_achat,
            'periodicity'             => $periodicity,
            'va'                      => $va,
            'vr'                      => $vc,
            'coef'                    => $coef,
            'loyer_evo_mensuel'       => $loyer_evo_mensuel,
            'loyer_evo'               => $loyer_evo_mensuel * $periodicity,
            'loyer_dyn_mensuel'       => $loyer_dyn_mensuel,
            'loyer_dyn'               => $loyer_dyn_mensuel * $periodicity,
            'loyer_dyn_suppl_mensuel' => $loyer_dyn_suppl_mensuel,
            'loyer_dyn_suppl'         => $loyer_dyn_suppl_mensuel * $periodicity,
            'nb_loyers_suppl'         => 12 / $periodicity,
            'loyer_dyn_eco_percent'   => ($total_demande ? (($loyer_evo_mensuel * $nb_mois - $loyer_dyn_mensuel * $nb_mois) / $total_demande) * 100 : 0),
            'first_loyer_maj'         => $first_loyer_maj,
            'fl10_next_loyer_mensuel' => ($loyer_evo_mensuel * $nb_mois - $first_loyer_maj * ($nb_mois - 1))
        );
    }

    public static function renderValuesTable($values)
    {
        $html = '<table class="bimp_list_table">';
        $html .= '<tbody class="headers_col">';

        $html .= '<tr>';
        $html .= '<th>Total Demande HT</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($values['total_demande'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Nombre de mois</th>';
        $html .= '<td>' . $values['nb_mois'] . ' mois</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Marge</th>';
        $html .= '<td>' . $values['marge'] * 100 . ' %</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Taux de cession</th>';
        $html .= '<td>' . BimpTools::displayFloatValue($values['tx_cession'], 2) . ' %</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Coefficient multiplicateur</th>';
        $html .= '<td>' . BimpTools::displayFloatValue($values['coef'], 3) . ' %</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="2"><h4>Formule EVOLUTIVE</h4></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Loyer mensuel HT</th>';
        $html .= '<td><b>' . BimpTools::displayMoneyValue($values['loyer_evo_mensuel'], 'EUR', 0, 0, 0, 2, 1) . '</b></td>';
        $html .= '</tr>';

        if ($values['loyer_evo_mensuel'] != $values['loyer_evo']) {
            $html .= '<tr>';
            $html .= '<th>Loyer ' . self::$periodicities_masc[$values['periodicity']] . ' HT</th>';
            $html .= '<td><b>' . BimpTools::displayMoneyValue($values['loyer_evo'], 'EUR', 0, 0, 0, 2, 1) . '</b></td>';
            $html .= '</tr>';
        }

        if ($values['nb_mois'] <= 36) {
            $html .= '<tr>';
            $html .= '<td colspan="2"><h4>Formule DYNAMIQUE (Taux 0%)</h4></td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Loyer mensuel HT</th>';
            $html .= '<td><b>' . BimpTools::displayMoneyValue($values['loyer_dyn_mensuel'], 'EUR', 0, 0, 0, 2, 1) . '</b></td>';
            $html .= '</tr>';

            if ($values['loyer_evo_mensuel'] != $values['loyer_evo']) {
                $html .= '<tr>';
                $html .= '<th>Loyer ' . self::$periodicities_masc[$values['periodicity']] . ' HT</th>';
                $html .= '<td><b>' . BimpTools::displayMoneyValue($values['loyer_dyn'], 'EUR', 0, 0, 0, 2, 1) . '</b></td>';
                $html .= '</tr>';
            }

            $html .= '<tr>';
            if ((int) $values['nb_loyers_suppl'] > 1) {
                $html .= '<th>Suivi de ' . $values['nb_loyers_suppl'] . ' Loyers ' . self::$periodicities_masc[$values['periodicity']] . 's HT supplémentaires de : </th>';
            } else {
                $html .= '<th>Suivi de ' . $values['nb_loyers_suppl'] . ' Loyer ' . self::$periodicities_masc[$values['periodicity']] . ' HT supplémentaire de : </th>';
            }

            $html .= '<td><b>' . BimpTools::displayMoneyValue($values['loyer_dyn_suppl'], 'EUR', 0, 0, 0, 2, 1) . '</b></td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Economie constatée</th>';
            $html .= '<td><b>' . BimpTools::displayFloatValue($values['loyer_dyn_eco_percent'], 1) . ' %</b></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}

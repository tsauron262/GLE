<?php

class BFTools
{

    public static function calc_vpm($taux, $coef, $npm, $va, $vc = 0, $type = 0)
    {
        // VPM = valeur de paiement => Calcul du montant d'un remboursement selon taux et capital
        // Taux: taux d'intérêt
        // npm : Nombre de remboursements. 
        // va : Montant de l'emprunt (Valeur actuelle)
        // vc : Valeur capitalisée (résiduelle)
        // type: 0 = à terme échu (fin de période) / 1 = à terme à échoir (début de période)

        if ($coef > 0) {
            return ($coef / 100) * $va;
        }

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
}

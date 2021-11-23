<?php

function get_sens($amount, $element, $inverse = false, $sens_parent = 'D') {
    switch($element) {
        case 'paiement':
            if($inverse){
                return ($amount > 0) ? 'C' : 'D';
            }
            return ($amount < 0) ? 'C' : 'D';
        break;
        case 'facture_fourn':
            $sens = ($sens_parent == 'D') ? 'C' : 'D';
            if($inverse) {
                $sens = ($sens_parent == 'D') ? 'D' : 'C';
            }
        break;
        case 'facture': 
            $sens = ($sens_parent == 'D') ? 'D' : 'C';
            if($inverse) {
                $sens = ($sens_parent == 'D') ? 'C' : "D";
            } 
        break;
    }
    return $sens;
}


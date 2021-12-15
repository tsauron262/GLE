<?php

function interco_code($compte_a_convertir, $compte_interco) {
    $start_compte = substr($compte_a_convertir, 0,6);
    $end_compte =  substr($compte_interco, 6, 7);
    return $start_compte . $end_compte;
}
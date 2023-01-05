<?php

function code_journal($secteur, $AouV, $isInterco) {
    switch($secteur) {
        case 'E': 
        case 'CTE':
            $endCode = "E"; 
            break;
        case 'S': $endCode = "S"; break;
        case 'C': 
        case 'CO':
        case 'CTC':
        case 'I':
        case 'BP':
        case 'X':
            $endCode = "P"; 
            break;
        case 'M': $endCode = "B"; break;
        default:
            $endCode = "P";
            break;
    }
    if($isInterco) {
        $middleCode = "I";
    } else  {
        if($AouV == "A") {
            $middleCode  = "C";
        } else {
            $middleCode = "E";
        }
    }

    return $AouV . $middleCode . $endCode;

}
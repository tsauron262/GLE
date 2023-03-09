<?php

function code_journal($secteur, $AouV, $isInterco) {
    switch($secteur) {
        case 'E': 
        case 'CTE':
            $endCode = "E"; 
            break;
        case 'S': $endCode = "S"; break;
        case 'M': $endCode = "B"; break;
        case 'C': 
        case 'CO':
        case 'CTC':
        case 'I':
        case 'BP':
        case 'X':
        default:
            $endCode = BimpCore::getConf('default_end_code_j', 'P', 'bimptocegid'); 
            break;
    }
    if($isInterco) {
        $middleCode = BimpCore::getConf('midle_journal_interco', null, 'bimptocegid');//"I";
    } else  {
        if($AouV == "A") {
            $middleCode  = "C";
        } else {
            $middleCode = "E";
        }
    }

    return $AouV . $middleCode . $endCode;

}
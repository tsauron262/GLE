<?php

function suppr_accents($str, $encoding = 'utf-8') {
    $str = htmlentities($str, ENT_NOQUOTES, $encoding);
    $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
    $str = preg_replace("#&[^;]+;#", '', $str);
    $str = str_replace("\n", "", $str);
    $str = str_replace("\t", "", $str);
    $str = str_replace("\r", "", $str);
    return $str;
}

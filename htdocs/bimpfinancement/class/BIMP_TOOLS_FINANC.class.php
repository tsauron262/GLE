<?php

class BIMP_TOOLS_FINANC{
//    public static $coefALaCon = 0.0833333333333;
    public static $coefALaCon = 0.179497374;
    
    static function calculInteret($capital, $nbMois, $dureePeriode = 1, $taux = 0, $coef = 0, $echoir = true) {
        
        $nbPeriode = $nbMois/$dureePeriode;
        if ($taux > 0){
            $tauxPM = $taux / 100 / 12;
            $echoirCalc = 1;
            if ($echoir) {
                $echoirCalc = 1 + $taux / 100 * self::$coefALaCon;
            }
             $loyer = $capital / ($dureePeriode / (($tauxPM / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc)));
             $loyer = $capital * ($tauxPM / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc) * $dureePeriode;
//             $loyer = (($capital * $tauxPM) / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc) * $dureePeriode;
        }
        else{
            $loyer = $capital / $nbPeriode;
        }
        
        if($coef != 0){
            $interet = $capital - $capital * ($nbMois * $dureePeriode * $coef / 100);
            $loyer = ($capital - $interet)/$nbPeriode;
        }

        return $loyer;
    }

    static function calculCapital($loyer, $nbMois, $dureePeriode = 1, $taux = 0, $coef = 0, $echoir = true) {
        $nbPeriode = $nbMois/$dureePeriode;

        if($taux > 0){
            $tauxPM = $taux / 100 / 12;
            $echoirCalc = 1;
            if ($echoir) {
                $echoirCalc = 1 + $taux / 100 * self::$coefALaCon;
            }
            $capital = $loyer / ($tauxPM / (1 - pow((1 + $tauxPM), -($nbMois)))  / $echoirCalc) / $dureePeriode;
        }
        else{
            $capital = $loyer * $nbPeriode;
        }
        if($coef != 0){
            $interet = $capital - $capital / ($nbMois * $dureePeriode * $coef / 100);
            $capital = $capital - $interet;
        }
        
        return $capital;
    }
}
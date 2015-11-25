<?php

global $tabCentre;
$tabCentre = array("V" => array("04 75 81 81 54", "sav07@bimp.fr", "Saint Peray (valence)", 19), 
    "M" => array("04 50 22 15 55", "sav74@bimp.fr", "Meythet", 58, "462140", "74960", "Meythet", "3 RUE DU VIEUX MOULIN"), 
    'L' => array("04 78 17 30 28", "sav69@bimp.fr", "Lyon", 21, "1000565", "69006", "Lyon", "67 rue Vendome"),
    "S" => array("04 77 81 58 12", "sav42@bimp.fr", "Saint-Etienne", 18, "1000483", "42000", "Saint Etienne", "14 rue gambetta"),
    "GA" => array("04 76 23 05 18", "sav38@bimp.fr", "Grenoble (Arts et Métiers)", 52, "494685", "38000", "Grenoble", "5, rue des arts et métiers"),
    "B" => array("09 70 72 12 33", "sav250@bimp.fr", "Besançon", 83, "466183", "25000", "CC Chateaufarine route de Dole"),
    "MO" => array("03 81 95 19 20", "sav252@bimp.fr", "Montbeliard", 84, "484926", "25200", "37 place Denfert Rochereau"),
    "C" => array("03 44 200 200", "sav60@bimp.fr", "Compiegne", 106, "1040727", "60200", "Compiègne", "10 rue de l’étoile"),
    "MA" => array("04 96 11 29 40", "sav13@bimp.fr", "Marseille", 116, "462140", "13011", "Marseille", "Route de la Sablière"));


$tabCentre["GB"] = $tabCentre["GA"];
$tabCentre["GB"]['2'] = "Grenoble boutique";

$tabCentre["AB"] = $tabCentre["M"];
$tabCentre["AB"]['2'] = "Annecy Boutique";
$tabCentre["CB"] = $tabCentre["M"];
$tabCentre["CB"]['2'] = "Chambery boutique";


$tabCentre["VB"] = $tabCentre["V"];
$tabCentre["VB"]['2'] = "Valence Boutique";
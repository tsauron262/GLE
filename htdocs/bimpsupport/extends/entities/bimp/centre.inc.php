<?php

global $tabCentre;
$tabCentre = array(
    "V"     => array("04 75 62 73 80", "sav07@ldlc.com", "Guillerand Granges", 19, "1461030", "07500", "GUILLERAND GRANGES", "Espace Colibri \n 85 rue Conrad Kilian", 25, 1),
    "M"     => array(/*"04 50 22 15 55"*/"04 50 32 89 07", "sav74@ldlc.com", "Meythet", 58, "462140", "74960", "Meythet", "3 RUE DU VIEUX MOULIN", 21, 0),
    "CB"    => array("09 70 72 12 32", "sav73@ldlc.com", "Chambéry", 213, "1461034", "73000", "Chambéry", "150 rue croix d'or", 36, 1),
    'L'     => array("04 78 17 30 23", "sav69@ldlc.com", "Lyon 6", 21, "1461023", "69006", "Lyon", "67 rue Vendome", 23, 1),
    'L3'     => array("04 78 17 30 23", "sav69@ldlc.com", "Lyon 3", 21, "1461023", "69003", "Lyon", "20 Rue Servient", 23, 0, 'L'),
    "S"     => array("04 77 81 58 11", "sav42@ldlc.com", "Saint-Etienne", 18, "1461019", "42000", "Saint Etienne", "14 rue gambetta", 24, 1),
    "GA"    => array("04 76 54 02 54", "sav38@ldlc.com", "Grenoble boutique (old)", 52, "1461027", "38000", "Grenoble", "11 Place Victor Hugo", 26, 0),
    "B"     => array("09 70 72 12 33", "sav250@ldlc.com", "Besançon", 83, "1461035", "25000", "Besançon", "CC Châteaufarine route de Dole", 22, 1),
    "MO"    => array("03 81 95 19 20", "sav252@ldlc.com", "Montbeliard", 84, "1461025", "25200", "Montbéliard", "37 place Denfert Rochereau", 27, 0),
    "C"     => array("03 44 200 200", "sav60@ldlc.com", "Compiegne", 106, "1461028", "60200", "Compiègne", "10 rue de l’étoile", 30, 1),
    "MA"    => array("04 96 11 29 40", "sav13@ldlc.com", "Marseille", 116, "1461029", "13011", "Marseille", "117 Traverse de la Montre, Centre commercial Grand V", 32, 1),
    "AB"    => array("04 50 32 89 07", "sav74@ldlc.com", "Annecy Boutique", 60, "1461033", "74000", "Annecy", "7 rue de la poste", 34, 1),
    "CFC"   => array("04 63 46 76 37", "sav63@ldlc.com", "Chamalières", 147, "1461020", "63000", "Clermont-Ferrand", "Centre Jaude 2 (1er Etage) 7 Rue Giscard de la Tour Fondue", 35, 0),
    "CFB"   => array(/*"04 63 46 76 37"*/"04 63 46 76 36", "sav63@ldlc.com", "Clermont-Ferrand boutique", 147, "1461020", "63000", "Clermont-Ferrand", "Centre Jaude 2 (1er Etage) 7 Rue Giscard de la Tour Fondue", 35, 1),
    "CF"    => array("04 63 46 76 37", "sav63@ldlc.com", "Chamalières OLD", 147, "1461020", "63000", "Clermont-Ferrand", "Centre Jaude 2 (1er Etage) 7 Rue Giscard de la Tour Fondue", 35, 0),
    "P"     => array("09 71 002 450", "sav66@ldlc.com", "Perpignan", 215, "1461021", "66000", "Perpignan", "12 Avenue Maréchal Leclerc", 40, 1), // boutique: 9 Boulevard Georges Clemenceau
    "N"     => array("04 66 842 974", "sav30@ldlc.com", "Nîmes", 217, "1461026", "30900", "Nîmes", "Centre commercial Cap Costière, 400 Avenue Claude Baillet", 39, 0),
    "MONTP" => array("04 67 555 111", "sav34@ldlc.com", "Montpellier", 218, "1187561", "34000", "Montpellier", "36 Rue Saint-Guilhem", 38, 0),
    "MAU"   => array("04 67 222 333", "sav341@ldlc.com", "Mauguio", 221, "1461024", "34130", "Mauguio", "39 Rue René Fonck", 37, 1),
    "BB"    => array("09 53 01 39 84", "sav01@ldlc.com", "Bourg-en-Bresse", 58, "1461022", "01000", "Bourg-en-Bresse", "20 Avenue des granges barde", 45, 1)
    );

$tabCentre["GB"] = $tabCentre["GA"];
$tabCentre["GB"]['2'] = "Grenoble boutique";
$tabCentre["GB"]['9'] = 1;

//$tabCentre["AB"] = $tabCentre["M"];
//$tabCentre["AB"]['2'] = "Annecy Boutique";
//$tabCentre["CB"] = $tabCentre["M"];
//$tabCentre["CB"]['2'] = "Chambery boutique";

//$tabCentre["VB"] = $tabCentre["V"];
//$tabCentre["VB"]['2'] = "Valence Boutique";

//$tabCentre["M"] = $tabCentre["AB"];

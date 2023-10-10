<?php

global $tabCentre;

// 0: tel / 1: email / 2: label / 3: ?? / 4: shipTo / 5: zip / 6: ville / 7: adresse / 8: ID entrepôt / 9: actif / 10: Centre répa de rattachement (fac.) 

$tabCentre = array(
    "MV" => array("02 31 35 11 22", "sav_mv@actimac.fr", "Mondevillage", 0, "1134736", "14120", "Mondeville", "Centre commercial Mondevillage, Parvis Central, Rue Jacquard Mondeville", 1, 1),
    "CS" => array("02 31 36 01 01", "sav_mv@actimac.fr", "Caen Saint-Pierre", 0, "1134736", "14000", "Caen", "147 rue Saint-Pierre", 1, 0, 'MV'),
    "RR" => array("02 35 15 00 00", "sav_rr@actimac.fr", "Rouen république", 0, "608105", "76000", "Rouen", "49 bis Rue de la République", 4, 1),
    "RD" => array("02 35 15 00 00", "tom.cressent@actimac.fr", "Rouen Dock 76", 0, "608105", "76000", "Rouen", "Centre de Commerces et de Loisirs Docks 76 - Porte 5", 4, 0, 'RR'),
    "LH" => array("02 35 54 14 14", "sav_lh@actimac.fr", "Le Havre", 0, "1185605", "76600", "Le Havre", "11 rue Robert de la Villehervé", 7, 1)
);

//$tabCentre["MV"][4] = '1442050';
//$tabCentre["RR"][4] = '1442050';
//$tabCentre["LH"][4] = '1442050';
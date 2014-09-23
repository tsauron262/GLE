<?php

if (!defined('NOTOKENRENEWAL'))
    define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))
    define('NOREQUIREMENU', '1'); // If there is no menu to show
if (!defined('NOREQUIREHTML'))
    define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
if (!defined('NOREQUIREAJAX'))
    define('NOREQUIREAJAX', '1');
define("NOLOGIN", 1);  // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.

require_once('../../main.inc.php');

$sautDeLigne = "\n";
$separateur = "\t";




$result = $db->query("SELECT *, fact.rowid as factid 
FROM  `llx_facture` fact, llx_societe soc
WHERE fk_soc = soc.rowid AND `extraparams` IS NULL AND fk_statut = 2 ");

while ($ligne = $db->fetch_array($result)) {
    $return = "";
    $return .= textTable($ligne, $separateur, $sautDeLigne);
    $return .= $sautDeLigne;
    $result2 = $db->query("SELECT * FROM  `llx_facturedet` WHERE  `fk_facture` =  " . $ligne['factid']);
    
    $i = 0;
    while ($ligne2 = $db->fetch_array($result2)) {
        $i++;
        $return .= textTable($ligne2, $separateur, $sautDeLigne, "L", ($i == 1));
    }
    $return .= $sautDeLigne . $sautDeLigne . $sautDeLigne;
    echo $return . DOL_DATA_ROOT . "/extratFact/" . $ligne['facnumber'] . ".csv";
    $folder = DOL_DATA_ROOT . "/extratFact/";
    if (!is_dir($folder))
        mkdir($folder);
    file_put_contents($folder . $ligne['facnumber'] . ".csv", $return);
}

function textTable($ligne, $separateur, $sautDeLigne, $prefLigne = '', $afficheTitre = true) {
    $return = "";
    if ($afficheTitre) {
        $return .= "E".$separateur;
        foreach ($ligne as $nom => $valeur) {
            if (!is_int($nom))
                $return .= $nom . $separateur;
        }
        $return .= $sautDeLigne;
    }
        
    $return .= $prefLigne.$separateur;
    foreach ($ligne as $nom => $valeur) {
        if (!is_int($nom))
            $return .= $valeur . $separateur;
    }
    $return .= $sautDeLigne;
    return $return;
}

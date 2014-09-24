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
//$sautDeLigne = "<br/><br/>";
//$separateur = " | ";




$result = $db->query("SELECT nom, phone, address, zip, town, facnumber, fact.datec, fact.rowid as factid 
FROM  `llx_facture` fact, llx_societe soc
WHERE fk_soc = soc.rowid AND `extraparams` IS NULL AND fk_statut = 2 ");

while ($ligne = $db->fetch_array($result)) {
    $return1 = $return2 = "";
    $return1 .= textTable($ligne, $separateur, $sautDeLigne, 'E', true);
    $return2 .= textTable($ligne, $separateur, $sautDeLigne, 'E', false);
    $result2 = $db->query("SELECT ref, fd.qty, fd.subprice, fd.description FROM  `llx_facturedet` fd left join llx_product p ON p.rowid = fd.fk_product WHERE  `fk_facture` =  " . $ligne['factid']);
    
    $i = 0;
    while ($ligne2 = $db->fetch_array($result2)) {
        $i++;
        if($i==1)
            $return1 .= textTable($ligne2, $separateur, $sautDeLigne, "L", true);
        $return2 .= textTable($ligne2, $separateur, $sautDeLigne, "L", false);
    }
    $return = $return1.$return2;
echo $return;
    $folder = DOL_DATA_ROOT . "/extratFact/";
    if (!is_dir($folder))
        mkdir($folder);
    file_put_contents($folder . $ligne['facnumber'] . ".txt", $return);
}

function textTable($ligne, $separateur, $sautDeLigne, $prefLigne = '', $afficheTitre = true) {
    $return = "";
    $tabCacher = array('factid', 'rowid');
    if ($afficheTitre) {
        $return .= $prefLigne.$separateur;
        foreach ($ligne as $nom => $valeur) {
            if (!is_int($nom) && !in_array($nom,$tabCacher))
                $return .= $nom . $separateur;
        }
        $return .= $sautDeLigne;
    }
     else{   
    $return .= $prefLigne.$separateur;
    foreach ($ligne as $nom => $valeur) {
        if (!is_int($nom) && !in_array($nom,$tabCacher))
            $return .= $valeur . $separateur;
    }
    $return .= $sautDeLigne;
     }
    return $return;
}

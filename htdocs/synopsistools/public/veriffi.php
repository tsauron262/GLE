<?php

if (!defined('NOTOKENRENEWAL'))
    define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))
    define('NOREQUIREMENU', '1'); // If there is no menu to show
if (!defined('NOREQUIREHTML'))
    define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
if (!defined('NOREQUIREAJAX'))
    define('NOREQUIREAJAX', '1');
//define("NOLOGIN", 1);  // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.

require_once('../../main.inc.php');

//$sautDeLigne = "<br/><br/>";
//$separateur = " | ";


//require_once(DOL_DOCUMENT_ROOT."/synopsistools/class/synopsisexport.class.php");
//$export = new synopsisexport($db, (isset($_REQUEST['sortie'])? $_REQUEST['sortie'] : 'html'));
//$export->exportFactureSav();

$soc = new Societe($db);
$sql = $db->query("SELECT * FROM `". MAIN_DB_PREFIX ."fichinter` f LEFT JOIN ". MAIN_DB_PREFIX ."synopsisfichinter sf ON  f.rowid = sf.rowid  WHERE (f.fk_contrat < 1 || fk_contrat IS NULL) AND (fk_commande < 1 || fk_commande IS NULL) AND datec > '2018-01-01 00:00:00'");
while($ln = $db->fetch_object($sql)){
    echo "<br><br>".$ln->ref." ";
    if($ln->fk_soc){
        $soc->fetch($ln->fk_soc);
        if($soc->id > 0){
            $comms = $soc->getSalesRepresentatives($user);
            foreach($comms as $comm){
                echo " ".print_r($comm['email'],1);
            }
        }
    }
}



echo '<br/><br/>Non validée<br/><br/>';

$sql = $db->query("SELECT * FROM `". MAIN_DB_PREFIX ."fichinter` f WHERE fk_statut = 0 AND datec > '2018-01-01 00:00:00'");
while($ln = $db->fetch_object($sql)){
    echo "<br><br>".$ln->ref." ";
    if($ln->fk_soc){
        $soc->fetch($ln->fk_soc);
        if($soc->id > 0){
            $comms = $soc->getSalesRepresentatives($user);
            foreach($comms as $comm){
                echo " ".print_r($comm['email'],1);
            }
        }
    }
}

echo "FIN";


echo "<br/><br/><a href='" . $_SERVER["HTTP_REFERER"] . "'>Retour</a>";



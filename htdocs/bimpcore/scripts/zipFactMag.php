<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

llxHeader();

ini_set('display_errors', 1);

chdir(DOL_DATA_ROOT);

$zipFile = "./zip/zipFact.zip";

$zip = new ZipArchive(); 
// On crée l’archive.

$debug = false;
$ok = $bad = 0;

if($zip->open($zipFile, ZipArchive::CREATE) === TRUE)
{
  echo $zipFile.' ouvert';
  
  
    $req = "SELECT facnumber FROM llx_facture f, `llx_facture_extrafields` fe WHERE `fk_object` = f.rowid AND fe.`type` = 'M'";
    $sql = $db->query($req);

    while ($ln = $db->fetch_object($sql)){
        $dir = './facture/'.$ln->facnumber.'/'.$ln->facnumber.'.pdf';
        if(is_dir($dir) || is_file($dir)){
            $zip->addFile($dir);
            if($debug)
                echo '<br/>Aj '.$dir;
            $ok++;
        }
        else{
            if($debug)
                echo '<br/>'.$dir.' n\'existe pas';
            $bad++;
        }
    }

    $zip->close();

    echo "<br/>zip fermé";
    echo ' '.$ok.' ok . '.$bad.' ko';
    
}
else
{
  echo 'Impossible d&#039;ouvrir &quot;Zip.zip&quot;';
  // Traitement des erreurs avec un switch(), par exemple.
}


llxFooter();

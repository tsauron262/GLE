<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

echo "Script désactiver";
return;


$sql = $db->query("SELECT rowid FROM `llx_societe` WHERE `outstanding_limit_icba` = '7000' AND date_depot_icba is null");
echo 'Début du script: <br/><br/>';
while($ln = $db->fetch_object($sql)){
    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $ln->rowid);
    $files = $client->getFilesArray();
    $id_bimpfile = 0;
    foreach ($files as $id => $f) {
        if($f == "PDF - atradius.pdf")
            $id_bimpfile = (int) $id;
    }
    $msg = '';
    if($id_bimpfile) {
        $bimpfile = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', (int) $id_bimpfile);
        $date_file = filemtime($bimpfile->getFilePath());
        
        if(0 < (int) $date_file) {
            echo $client->getNomUrl() . " date depot ICBA devient " . date('Y-m-d H:i:s', $date_file) . '<br/>';
            $client->updateField('date_depot_icba', date('Y-m-d H:i:s', $date_file));
        } else {
            echo $client->getNomUrl() . ' date du fichier introuvale, on passe l\'encours ICBA à 0<br/>';
            $client->updateField('outstanding_limit_icba', 0);
        $msg = "Encours ICBA passé à 0 € le 20 janvier 2023 car le rapport n'avait pas été déposé";
        }
    } else {
        echo $client->getNomUrl() . ' pas de fichier ICBA, on passe l\'encours ICBA à 0<br/>';
        $client->updateField('outstanding_limit_icba', 0);
        $msg = "Encours ICBA passé à 0 € le 20 janvier 2023 car le rapport n'avait pas été déposé";
    }
    
    if($msg)
        $client->addNote($msg);
}


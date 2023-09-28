<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

$old_name = "atradius";

$files = BimpCache::getBimpObjectObjects('bimpcore', 'BimpFile', array(
    'parent_module'      => 'bimpcore',
    'file_name'          => $old_name,
    'file_ext'           => 'pdf'
        ), true);

$nb_fichier_trouve = $nb_fichier_introuvable = 0;

echo '<table>';

foreach($files as $f) {
    $client = BimpCache::getBimpObjectInstance($f->getData('parent_module'), $f->getData('parent_object_name'), $f->getData('id_parent'));
    if(file_exists($f->getFilePath())) {
        $date_create = date(filemtime($f->getFilePath()));
        $new_name = 'icba_' . date('Y-m-d', $date_create);
        $f->set('file_name', $new_name);
        $f->update();
        $nb_fichier_trouve++;
    } else {
        $traitement = "<b>FICHIER INEXISTANT</b>";
        $nb_fichier_introuvable++;
    }
    
    echo '<tr><td>' .  $client->getNomUrl() . '</td><td>' . $traitement . '<td/><tr/>';
}
echo '</table>';

echo 'Nb trouvé: ' . $nb_fichier_trouve . '<br/><br/>';
echo 'Nb introuvable: ' . $nb_fichier_introuvable . '<br/><br/>';
echo 'Nb fichier total: ' . count($files) . '<br/><br/>';
<?php
require_once("../../main.inc.php");

die('Désactivé'); 

$sql = $db->query("SELECT s.id as idSav, n.date_create as date  FROM `llx_bs_sav` s, `llx_bimpcore_note` n WHERE `date_terminer` IS NULL
AND `obj_name` = 'BS_SAV' AND `id_obj` = s.id AND (`content` LIKE 'Réparation terminée le \"%\" par%' || `content` LIKE 'Devis fermé après refus par le client le \"%\" par%')");

$i=0;
while ($ln = $db->fetch_object($sql)){
    $db->query("UPDATE llx_bs_sav SET date_terminer = '".$ln->date."' WHERE id = ".$ln->idSav);
    $i++;
}

echo 'fin '.$i;
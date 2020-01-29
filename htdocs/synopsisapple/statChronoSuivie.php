<?php

require_once('../main.inc.php');

llxHeader("", "Suivi des interventions SAV");


print load_fiche_titre("Suivi des interventions SAV");


$requete = "SELECT c.id, c.ref FROM `" . MAIN_DB_PREFIX . "synopsischrono` c, " . MAIN_DB_PREFIX . "synopsischrono_chrono_105 c105, " . MAIN_DB_PREFIX . "element_element e1, " . MAIN_DB_PREFIX . "propal p, " . MAIN_DB_PREFIX . "facture f"
        . " WHERE (c105.Suivie = '' || c105.Suivie is NULL) AND c105.id = c.id AND fk_source = p.rowid AND sourcetype = 'propal' AND targettype = 'facture' AND fk_target = f.rowid AND c.propalid = p.rowid AND `ref` LIKE 'FA%' ";
$centre = str_replace(" ", "','", $user->array_options['options_apple_centre']);
if($centre)
    $requete .= " AND Centre IN ('" . $centre . "') ";
$requete .= " AND DATEDIFF(now(), f.`date_valid`) > 2 AND DATEDIFF(now(), f.`date_valid`) < 7 ORDER BY f.`date_valid`, f.rowid DESC";

$sql = $db->query($requete);

require_once (DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
$chr = new Chrono($db);

echo $db->num_rows($sql)." SAV a suivre : <br/><br/>";

while ($result = $db->fetch_object($sql)) {
    if (!$chr->id > 0) {
        $chr->fetch($result->id);
    } else {
        $chr->id = $result->id;
        $chr->ref = $result->ref;
    }
    echo $chr->getNomUrl(1)."<br/>";
}
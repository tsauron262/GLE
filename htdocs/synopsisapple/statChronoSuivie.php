<?php

require_once('../main.inc.php');

llxHeader("", "Signaler un bug");


print load_fiche_titre("Suivie des interventions SAV");


$sql = $db->query("SELECT c.id, c.ref FROM `" . MAIN_DB_PREFIX . "synopsischrono` c, " . MAIN_DB_PREFIX . "synopsischrono_chrono_105 c105, " . MAIN_DB_PREFIX . "element_element e1, " . MAIN_DB_PREFIX . "propal p, " . MAIN_DB_PREFIX . "facture f"
        . " WHERE c105.Suivie = '' AND c105.id = c.id AND fk_source = p.rowid AND sourcetype = 'propal' AND targettype = 'facture' AND fk_target = f.rowid AND c.propalid = p.rowid AND `facnumber` LIKE 'FA%' "
        . "AND DATEDIFF(now(), f.`date_valid`) > 2 AND DATEDIFF(now(), f.`date_valid`) < 7");

require_once (DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
$chr = new Chrono($db);
while ($result = $db->fetch_object($sql)) {
    if (!$chr->id > 0) {
        $chr->fetch($result->id);
    } else {
        $chr->id = $result->id;
        $chr->ref = $result->ref;
    }
    echo $chr->getNomUrl(1)."<br/>";
}
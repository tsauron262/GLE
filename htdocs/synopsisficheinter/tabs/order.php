<?php
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/order.lib.php");
require_once DOL_DOCUMENT_ROOT.'/synopsisfichinter/class/synopsisfichinter.class.php';

llxHeader($js, "Intervnetion en rapport avec la commande");

$id = GETPOST("id");
$commande = new Commande($db);
$commande->fetch($id);
$head = commande_prepare_head($commande);
dol_fiche_head($head, 'inter', $langs->trans("Interventions"), 0, 'order');

$fi = new Synopsisfichinter($db);
$sql = $db->query("SELECT * FROM `llx_synopsisfichinter` WHERE `fk_commande` = ".$id);
while($ln = $db->fetch_object($sql)){
    $fi->fetch($ln->rowid);
    echo $fi->getNomUrl(1);
    echo "<br/><br/>";
}

llxFooter();
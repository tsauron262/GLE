<?php
require_once("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/synopsisfichinter/class/synopsisfichinter.class.php';

llxHeader($js, "Intervnetion en rapport avec le client");

$id = GETPOST("id");
    $object = new Societe($db);
    $object->fetch($id);
    $head = societe_prepare_head($object);

dol_fiche_head($head, 'inter', $langs->trans("Interventions"), 0, 'company');

$fi = new Synopsisfichinter($db);
$sql = $db->query("SELECT * FROM `llx_fichinter` WHERE `fk_soc` = ".$id);
while($ln = $db->fetch_object($sql)){
    $fi->fetch($ln->rowid);
    echo $fi->getNomUrl(1);
    echo "<br/><br/>";
}

llxFooter();
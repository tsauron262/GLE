<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsisficheinter/modules_synopsisfichinter.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");


if ($conf->projet->enabled) {
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php");
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
}
if (defined("FICHEINTER_ADDON") && is_readable(DOL_DOCUMENT_ROOT . "/includes/modules/synopsisficheinter/mod_" . FICHEINTER_ADDON . ".php")) {
    require_once(DOL_DOCUMENT_ROOT . "/includes/modules/synopsisficheinter/mod_" . FICHEINTER_ADDON . ".php");
}

$langs->load("companies");
$langs->load("interventions");



        global $user,$langs,$conf;
$req = "SELECT rowid FROM llx_Synopsis_fichinter";
$sqlI = $db->query($req);
while($inter = $db->fetch_object($sqlI)){
	$idInter = $inter->rowid;
        $requete = "SELECT sum(total_ht) as sht,
                           sum(total_tva) as stva,
                           sum(total_ttc) as sttc,
                           sum(duree) as sdur
                      FROM llx_Synopsis_fichinterdet
                     WHERE fk_fichinter = ".$idInter;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $requete = "UPDATE llx_Synopsis_fichinter
                       SET total_ht = '".$res->sht."',
                           total_tva = '".$res->stva."' ,
                           total_ttc = '".$res->sttc ."',
                           duree = '".$res->sdur."'
                     WHERE rowid = ".$idInter;
        $sql = $db->query($requete);
	echo ($requete);
}
?>

<?php

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/revision.class.php");

$langs->load('propal');
$langs->load('compta');

$id = isset($_GET["id"]) ? $_GET["id"] : '';

// Security check
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'propale', $id, 'propal');


/*
 * 	View
 */


$propal = new Propal($db);
$propalId = $_GET["id"];
$propal->fetch($propalId);


$revisionPropal = new SynopsisRevisionPropal($propal);
$propalPre = $revisionPropal->getPropalPrec();
$propalSui = $revisionPropal->getPropalSuiv();


if (isset($_REQUEST['action']) && $_REQUEST['action'] == "revisee") {
    $revisionPropal->reviserPropal();
}






llxHeader();








$head = propal_prepare_head($propal);
dol_fiche_head($head, 'revision', $langs->trans('Proposal'), 0, 'propal');
//$result = $db->query("SELECT import_key as pre, extraparams as sui FROM " . MAIN_DB_PREFIX . "propal WHERE rowid = " . $propalId);
//$obj = $db->fetch_object($result);
//
//$propalPre = new propal($db);
//$propalPre->fetch($obj->pre);
//
//$propalSui = new propal($db);
//$propalSui->fetch($obj->sui);



print '<table width="100%"><tr><td><br/>Révision précédente : <br/>';
if ($propalPre->id != 0)
    print $propalPre->getNomUrl(1) . "<br/>";


print '<td><br/>Révision suivante : <br/>';
if ($propalSui->id != 0)
    print $propalSui->getNomUrl(1) . "<br/>";
else
    print '<form><input type="hidden" name="id" value="' . $propalId . '"/>
                        <input type="hidden" name="action" value="revisee"/>
                        <input class = "butAction" type="submit" value="R&eacute;viser"/>
                 </form>';




if (isset($conf->global->MAIN_MODULE_SYNOPSISCHRONO)) {
    $requete = "SELECT *,b.ref as refb FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono as b, " . MAIN_DB_PREFIX . "propal as l WHERE b.propalid = l.rowid AND l.rowid = " . $propal->id;
    $resql = $db->query($requete);

    print '<td><br/>Doc. indicable : <br/>';
    if ($db->num_rows($resql) > 0) {
        $res = $db->fetch_object($resql);
        print '<a href="' . DOL_URL_ROOT . '/Synopsis_Chrono/fiche.php?id=' . $res->id . '">' . $res->refb . '</a>';
    } else {
        $res = $db->fetch_object($resql);
        print '<a href="' . DOL_URL_ROOT . '/Synopsis_Chrono/nouveau.php?id=' . $propal->socid . '&propalid=' . $propal->id . '&typeid=1">A creer</a>';
    }
}








llxFooter();
$db->close();
?>

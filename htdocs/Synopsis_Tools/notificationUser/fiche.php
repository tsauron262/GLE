<?php

require_once('../../main.inc.php');

global $tabContactPlus;



if (isset($_REQUEST['saveForm'])) {
    if (isset($_REQUEST['id']) && $_REQUEST['id'] > 0)
        $req = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Tools_notificationUser SET fk_type_contact = " . $_REQUEST['typeContact'] . ", fk_trigger = " . $_REQUEST['fk_trigger'] . ", sujet = '" . $_REQUEST['sujet'] . "', message = '" . $_REQUEST['text'] . "', mailTo = '" . $_REQUEST['mailTo'] . "' WHERE rowid = " . $_REQUEST['id'];
    else
        $req = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Tools_notificationUser (fk_type_contact, fk_trigger, sujet, message, mailTo) VALUES (" . $_REQUEST['typeContact'] . "," . $_REQUEST['fk_trigger'] . ",'" . $_REQUEST['sujet'] . "','" . $_REQUEST['text'] . "','" . $_REQUEST['mailTo']."')";
    if (!$db->query($req))
        die("Erreur : " . $req);
    header("Location: list.php");
}

if (isset($_REQUEST['delElem'])) {
    if (isset($_REQUEST['id']) && $_REQUEST['id'] > 0)
        $req = "DELETE  FROM " . MAIN_DB_PREFIX . "Synopsis_Tools_notificationUser WHERE rowid = " . $_REQUEST['id'];
    if (!$db->query($req))
        die("Erreur : " . $req);
    header("Location: list.php");
}


$js = "<script src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.jDoubleSelect.js' type='text/javascript'></script>";
$js .= "<script>jQuery(document).ready(function(){
                    jQuery('SELECT.double').each(function(){ 
                        jQuery(this).jDoubleSelect();
                    });
                });
        </script>";

llxHeader($js, "Notification User");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Notification User"));

if($_REQUEST['id'] > 0){
    $sql = $db->query("SELECT *, nu.rowid as idNU
        FROM  `" . MAIN_DB_PREFIX . "Synopsis_Tools_notificationUser` nu
        LEFT JOIN " . MAIN_DB_PREFIX . "c_type_contact tc ON tc.rowid =  `fk_type_contact` 
        LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_trigger t ON t.id =  `fk_trigger`
            WHERE nu.rowid = " . $_REQUEST['id']);

    $result = $db->fetch_object($sql);
}

echo "<form method='post' action=''>";

echo "<table cellpadding='15'>";
print "<tr><th class='ui-widget-header ui-state-default'>Type d'&eacute;l&eacute;ment / D&eacute;clancheur";
print "    <td class='ui-widget-content'>
            <table width=100%>
                <tr><td>
                    <select name='fk_trigger' id='typeElement_refid' class='required double noSelDeco'>";

$sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_type_element ORDER BY rang");
while ($res = $db->fetch_object($sql)) {
    print "<optgroup label='" . str_replace(" ", "_", $res->label) . "'>";
    echo "<option value='0'></option>";
    $sql1 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_trigger as t, " . MAIN_DB_PREFIX . "Synopsis_Process_type_element_trigger as te WHERE te.trigger_refid = t.id AND te.element_refid = " . $res->id . "  ORDER BY code");
    while ($res1 = $db->fetch_object($sql1)) {
        $selected = ($result->fk_trigger == $res1->id) ? "selected='selected'" : "";
        print "<option value='" . $res1->id . "' " . $selected . ">" . $res1->code . "</option>";
    }
    print "</optgroup>";
}
print "</select>";
echo "</td></tr></table>";
echo "</td>";
echo "<th class='ui-state-default'>Sujet</th>";
echo "<td class=' ui-widget-content'><input type='text' name='sujet' value='" . $result->sujet . "'/></th>";
echo "<tr>";
echo "<th class='ui-state-default'>Message</th>";
echo "<td class=' ui-widget-content' colspan='2'>
                <textarea name='text'>" . $result->message . "</textarea>
                <td class='ui-state-default'>[ELEM] = Lien vers fiche avec picto
                <br/>[STATUT] = Statut
                <br/>[NAME] = Nom
                <br/>[REF] = Ref</th>
</th>";
echo "<tr><th class='ui-state-default'>Destinataire fixe</th>";
echo "<td class=' ui-widget-content'><input type='text' name='mailTo' value='" . $result->mailTo . "'/></th>";
//echo "<tr><td></td><td colspan='2'>et / ou</td><td></td>";
echo "<th class='ui-state-default'>et / ou Type destinataire</th>";
echo "<td class=' ui-widget-content'><select name='typeContact''/></th>";
echo "<option value='0'></option>";
$sql2 = $db->query("SELECT rowid, libelle FROM " . MAIN_DB_PREFIX . "c_type_contact WHERE active = 1 AND source = 'internal'");
foreach ($tabContactPlus as $contactPlus) {
    $selected = ($result->fk_type_contact == $contactPlus['id']) ? "selected='selected'" : "";
    echo "<option value='" . $contactPlus['id'] . "' " . $selected . ">" . $contactPlus['nom'] . "</option>";
}
while ($result2 = $db->fetch_object($sql2)) {
    $selected = ($result->fk_type_contact == $result2->rowid) ? "selected='selected'" : "";
    echo "<option value='" . $result2->rowid . "' " . $selected . ">" . $result2->libelle . "</option>";
}

echo "</td></tr></table>";

echo "</div>";
echo "<div class='divButAction'>";
if($_REQUEST['id'] > 0)
echo "<input type='submit' class='butActionDelete' name='delElem' value='Supprimer'/>";
echo "<a href='" . DOL_URL_ROOT . "/Synopsis_Tools/notificationUser/list.php'><input type='button' class='butAction' name='cancel' value='Annuler'/></a>";
echo "<input type='submit' class='butAction' name='saveForm' value='Valider'/>";
echo "</div></form>";
?>

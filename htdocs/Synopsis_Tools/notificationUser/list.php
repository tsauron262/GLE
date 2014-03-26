<?php
require("../../main.inc.php");
llxHeader();

global $tabContactPlus;

print "<div class='titre'>Config Notification Utilisateurs</div>";

print "<table class='noborder'><tr class='liste_titre'>";
print "<th>Evenement</th>";
print "<th>Sujet</th>";
print "<th>Text</th>";
print "<th>Destinataire fixe</th>";
print "<th>et/ou Type destinataire</th>";
print "<th>Modifier</th>";
echo "</tr>";

$sql = $db->query("SELECT *, nu.rowid as idNU 
    FROM  `".MAIN_DB_PREFIX."Synopsis_Tools_notificationUser` nu
    LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact tc ON tc.rowid =  `fk_type_contact` 
    LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_trigger t ON t.id =  `fk_trigger`");

$i = 0;
while($result = $db->fetch_object($sql)){
    $i = !$i;
    if(isset($tabContactPlus[$result->fk_type_contact]))
        $result->libelle = $tabContactPlus[$result->fk_type_contact]['nom'];
    
    
    echo "<tr class='".($i? 'impair' : 'pair')."'>";
    echo "<td>".$result->code."</td>";
    echo "<td>".$result->sujet."</td>";
    echo "<td>".$result->message."</td>";
    echo "<td>".$result->mailTo."</td>";
    echo "<td>".$result->libelle."</td>";
    echo "<td><a href='".DOL_URL_ROOT."/Synopsis_Tools/notificationUser/fiche.php?id=".$result->idNU."'>".img_edit("Modifier")."</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<div class='divButAction'><a href='" . DOL_URL_ROOT . "/Synopsis_Tools/notificationUser/fiche.php'><input type='button' class='butAction' value='Nouveau'/></a></div>";
?>

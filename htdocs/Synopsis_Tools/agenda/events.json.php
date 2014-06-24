    <?php

require_once('../../main.inc.php');
require_once("libAgenda.php");



$eventsStr = array();
$sql = ("SELECT *, (`datep2` - `datep`) as duree FROM " . MAIN_DB_PREFIX . "actioncomm WHERE ((datep < '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep > '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "') || (datep2 < '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep2 > '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "')) AND fk_user_action IN (" . implode(",", $newTabUser2) . ") order by duree DESC ");
$result = $db->query($sql);
//echo $sql;
while ($ligne = $db->fetch_object($result)) {
    $userId = $newTabUser[$ligne->fk_user_action];
//        $text = "<a href='" . DOL_URL_ROOT . "/comm/action/fiche.php?id=" . $ligne->id . "'>" . $ligne->label;
    $text = "<input type='hidden' class='idAction' value='" . $ligne->id . "'/>";
    $text .= "<input type='hidden' class='percent' value='" . $ligne->percent . "'/>";
    $text .= "<a title='".$ligne->label."' href='" . DOL_URL_ROOT . "/comm/action/fiche.php?id=" . $ligne->id . "' onclick=\"dispatchePopIFrame('" . DOL_URL_ROOT . "/comm/action/fiche.php?id=" . $ligne->id . "&action=edit&optioncss=print', function(){ $('#calendar').weekCalendar('refresh');}, '" . $ligne->label . "', 1); return false;\">" . $ligne->label;
    if ($ligne->fk_soc > 0) {
        $soc = new Societe($db);
        $soc->fetch($ligne->fk_soc);
        $text .= "<br/><br/>" . $soc->getNomUrl(1);
    }
    $text = str_replace(array("\r\n", "\r", "\n"), "<br />", $text);
    $text = str_replace('"', '\"', $text);
    if (!isset($ligne->datep2))
        $ligne->datep2 = $ligne->datep;
    if (!isset($ligne->datep))
        $ligne->datep = $ligne->datep2;
    $tabColor = array(50 => "#BBCCFF", 5 => "purple", 2 => "red",
        51 => "red", 54 => "red", 55 => "red", 58 => "red", 66 => "red",
        52 => "blue", 53 => "blue", 56 => "blue", 57 => "blue", 63 => "blue",
        60 => "orange", 63 => "green",
        61 => 'purple',
        64 => "gray", 65 => "gray");
    $colorStr = '';
    if (isset($tabColor[$ligne->fk_action]))
        $colorStr = ', "color":"' . $tabColor[$ligne->fk_action] . '"';
    if (isset($ligne->datep))
        $eventsStr[] = '{"id":' . $ligne->id . ', "start":"' . date('c', $db->jdate($ligne->datep)) . '", "end":"' . date('c', $db->jdate($ligne->datep2)) . '", "title":"' . $text . '", "userId": ' . $userId . $colorStr . '}';
}
echo "[";

echo implode(",", $eventsStr);


echo "]";
?>
<?php
require_once('../../main.inc.php');
require_once("libAgenda.php");



$eventsStr = array();
$sql = ("SELECT *, (`datep2` - `datep`) as duree FROM " . MAIN_DB_PREFIX . "actioncomm WHERE ((datep < '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep > '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "') || (datep2 < '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep2 > '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "')) AND fk_user_action IN (" . implode(",", $newTabUser2) . ") order by duree DESC ");
$result = $db->query($sql);
//echo $sql;
while($ligne = $db->fetch_object($result)){
    $userId = $newTabUser[$ligne->fk_user_action];
    if(!$userId > 0)
        die($sql.$userId."-".$ligne->fk_user_action);
        $text = "<a href='" . DOL_URL_ROOT . "/comm/action/fiche.php?id=" . $ligne->id . "'>" . $ligne->label;
        $eventsStr[] = '{"id":'.$ligne->id.', "start":"'.date('c', $db->jdate($ligne->datep)).'", "end":"'.date('c', $db->jdate($ligne->datep2)).'", "title":"'.$text.'", "userId": '.$userId.'}';
}
echo "[";
    
echo implode(",", $eventsStr);


echo "]";


?>
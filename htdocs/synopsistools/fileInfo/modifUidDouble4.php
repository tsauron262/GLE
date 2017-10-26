<?php


global $db;
$tabSql = array();

for($i=0; $i < 100; $i++){
    $sql = $db->query("SELECT COUNT(`rowid`) as nb, `uri`, max(rowid) as id FROM `".MAIN_DB_PREFIX."synopsiscaldav_event` WHERE 1 GROUP BY uri HAVING nb > 1 ORDER BY `llx_synopsiscaldav_event`.`uri` DESC");
    if($db->num_rows($sql) > 0){
        while($ligne = $db->fetch_object($sql)){
            $req = "UPDATE `".MAIN_DB_PREFIX."synopsiscaldav_event` SET uri = concat('b', uri) WHERE rowid= ".$ligne->id;
            echo $req . "</br>";
            $db->query($req);
        }
    }
}

$text = "vire les uid en double";
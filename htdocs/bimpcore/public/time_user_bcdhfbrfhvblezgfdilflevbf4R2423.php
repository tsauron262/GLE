<?php

define("NOLOGIN", 1);  // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.

require_once('../../main.inc.php');
require_once __DIR__ . '/../Bimp_Lib.php';
ini_set("display_errors", 1);


$display = array();
$sql = $db->query("SELECT login, data, data_time FROM llx_bimp_php_session;");
while($session = $db->fetch_object($sql)){
    
    $displayLn = array("user"=>$session->login, "time_total" => $session->data_time);
    $data = json_decode($session->data);
    if(isset($data->time)){
        foreach($data->time as $clef => $val){
            $displayLn['time_'.$clef] = $val;
        }
    }
    $display[] = $displayLn;
}

echo json_encode($display);

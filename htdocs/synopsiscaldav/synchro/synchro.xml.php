<?php


if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != "synchro" || $_SERVER['PHP_AUTH_PW'] != "9DDrvuNcWRdKClhTe2LGh0mbKVIV33I3" ) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'NON autorisé';
    exit;
} else {

header("Content-type: text/xml");
}




define("NOLOGIN", 1);  // This means this output page does not require to be logged.
require_once("../../main.inc.php");

$tabU = array();
$tabUser = array();

$result = $db->query('SELECT login, email  FROM `'.MAIN_DB_PREFIX.'user` u, '.MAIN_DB_PREFIX.'user_extrafields ue WHERE `statut` = 1 AND email != "" AND login != "" AND fk_object = u.rowid AND synchaction = 1');
while ($ligne = $db->fetch_object($result))
        if(stripos($ligne->email, "@bimp.fr") > 0)
            $tabUser[] = array($ligne->login, $ligne->email);

foreach($tabUser as $user){
//    $filter = "VEVENT [20180101T000000Z;20250315T000000Z] : STATUS!=CANCELLED";
    
    
    $date = new DateTime();
    $interval = new DateInterval('P23M');
    $date->sub($interval);
    $date2 = new DateTime();
    $interval = new DateInterval('P100M');
    $date2->add($interval);
    $filter = "VEVENT [".$date->format('Ymd')."T000000Z;".$date2->format('Ymd')."T000000Z] : STATUS!=CANCELLED";

    if(isset($user[1]) && $user[0] != "" && $user[1] != "")
        $tabU[] = array("ID" => array("Left" => 
                                array("Host" => "erp.bimp.fr",
                                        "Port" => "443",
                                        "Protocol" => "https",
                                        "Path" => "/bimp8/synopsiscaldav/html/cal.php/calendars/".$user[0]."/Calendar/",
                                        "Login" => "gle_suivi",
                                        "Password" => "{3DES}r7ewnFt+Y0C2fAT5Ry6i+5bvMNGzlgSI",
                                        "Filter" => $filter),
                            "Right" => 
                                array("Host" => "mailhost.bimp.fr",
                                        "Port" => "443",
                                        "Protocol" => "https",
                                        "Path" => "/SOGo/dav/".$user[1]."/Calendar/personal/",
                                        "Login" => "gle_suivi@bimp.fr",
                                        "Password" => "{3DES}r7ewnFt+Y0C2fAT5Ry6i+5bvMNGzlgSI",
                                        "Filter" => $filter)
    ));
}



echo array2xml($tabU, "ConfigIDs");






function array2xml($array, $wrap='ROW0', $upper=false) {
    // set initial value for XML string
    $xml = '';
    // wrap XML with $wrap TAG
    if ($wrap != null) {
        $xml .= "<$wrap>\n";
    }
    // main loop
    foreach ($array as $key=>$value) {
        // set tags in uppercase if needed
        
        if(!is_integer($key)){
            $temp = array(0 => array($key => $value));
        }
        else{
            $temp = array($key => $value);
        }
        foreach($temp as $temp2){
            foreach($temp2 as $key => $value){
                if ($upper == true) {
                    $key = strtoupper($key);
                }

                // append to XML string
                if(!is_array($value))
                    $xml .= "<$key>" . htmlspecialchars(trim($value)) . "</$key>";
                else{
                    $xml .= array2xml($value, $key);
                }
            }
        }
        
        
    }
    // close wrap TAG if needed
    if ($wrap != null) {
        $xml .= "\n</$wrap>\n";
    }
    // return prepared XML string
    return $xml;
}


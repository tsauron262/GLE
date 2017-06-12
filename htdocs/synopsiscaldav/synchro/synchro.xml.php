<?php


if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != "synchro" || $_SERVER['PHP_AUTH_PW'] != "synchrosynchro" ) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Texte utilisé si le visiteur utilise le bouton d\'annulation';
    exit;
} else {

header("Content-type: text/xml");
}





$tabU = array();




$tabUser = array(array("test", "test@bimp.fr"), array("tsauron", "tommy@bimp.fr"));


foreach($tabUser as $user){
$tabU[] = array("ID" => array("Left" => 
                                array("Host" => "gle.synopsis-erp.com",
                                        "Port" => "443",
                                        "Protocol" => "https",
                                        "Path" => "/bimp/synopsiscaldav/html/cal.php/calendars/".$user[0]."/Calendar/",
                                        "Login" => "test",
                                        "Password" => "testtest1",
                                        "Filter" => "VEVENT [20170323T000000Z;20180315T000000Z] : STATUS!=CANCELLED"),
                            "Right" => 
                                array("Host" => "10.192.20.20",
                                        "Port" => "8080",
                                        "Protocol" => "http",
                                        "Path" => "/SOGo/dav/".$user[1]."/Calendar/personal/",
                                        "Login" => "test@bimp.fr",
                                        "Password" => "testtest1",
                                        "Filter" => "VEVENT [20170323T000000Z;20180315T000000Z] : STATUS!=CANCELLED")
    ));
}





echo array2xml($tabU, "ConfigIDs");

/*<ConfigIDs>
    <ID>
        <Left>
            <Host>gle.synopsis-erp.com</Host>
            <Port>443</Port>
            <Protocol>https</Protocol>
            <Path>/bimp/synopsiscaldav/html/cal.php/calendars/test/Calendar/</Path>
            <Login>test</Login>
            <Password>testtest1</Password>
            <Filter>VEVENT [20170323T000000Z;20180315T000000Z] : STATUS!=CANCELLED</Filter>
        </Left>
        <Right>
            <Host>10.192.20.20</Host>
            <Port>8080</Port>
            <Protocol>http</Protocol>
            <Path>/SOGo/dav/test@bimp.fr/Calendar/personal/</Path>
            <Login>test@bimp.fr</Login>
            <Password>testtest1</Password>
            <Filter>VEVENT [20170323T000000Z;20180315T000000Z] : STATUS!=CANCELLED</Filter>
        </Right>
    </ID>
</ConfigIDs>*/







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


<?php
require_once("../main.inc.php");



if(isset($_REQUEST['clef']) == "hduifgdufgfqdkjfqgvhgtyeaipeoirhdifm4554534534hfiudg"){


    $result = $db->query('SELECT login, email  FROM `'.MAIN_DB_PREFIX.'user` u, '.MAIN_DB_PREFIX.'user_extrafields ue WHERE `statut` = 1 AND email != "" AND login != "" AND fk_object = u.rowid AND synchaction = 1');


    $xml = "<root>";
    $tabResult = array();
    while ($ligne = $db->fetch_object($result)){
        $xml .= "<user>
        <left>"."gle.synopsis-erp.com:443/bimp6/synopsiscaldav/html/cal.php/calendars/".$ligne->login."/Calendar/"."</left>
        <right>"."mailhost.bimp.fr:443/SOGo/dav/".$ligne->email."/Calendar/personal/"."</right>
    </user>";
    }
    $xml .= "</root>";

    header("Content-type: text/xml");
    echo $xml;


}
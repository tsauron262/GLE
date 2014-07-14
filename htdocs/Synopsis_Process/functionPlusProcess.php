<?php

function getSNChrono($idChrono, $source) {
    global $db;
    $key = 1011;
    $dest = "productCli";
    $ordre = 1;


    $return = array();
    $result = getElementElement($source, $dest, $idChrono, null, $ordre);
    if (count($result) > 0) {
        $chronoTab = array();
        foreach ($result as $chrono)
            $chronoTab[] = $chrono['d'];
        $req = "SELECT `value` FROM `" . MAIN_DB_PREFIX . "synopsischrono_value` WHERE `chrono_refid` IN (" . implode(",", $chronoTab) . ") AND `key_id` = " . $key;
        $sql = $db->query($req);
        while ($result = $db->fetch_object($sql))
            $return[] = $result->value;
    }

    return implode(" | ", $return);
}

function pictoSMS($idChrono) {
    global $db;
    $chrono = new Chrono($db);
    $chrono->fetch($idChrono);
    if (is_object($chrono->contact) && $chrono->contact->phone_mobile != "")
        $to = $chrono->contact->phone_mobile;
    elseif (is_object($chrono->societe) && $chrono->societe->phone != "")
        $to = $chrono->societe->phone;
    $fromsms = urlencode('SAV BIMP');

    $to = str_replace(" ", "", $to);


    $tabMessage = array(array("camion", "MEss 1", "Message avec espace é à l'oin"),
        array("object_licence", "Mess 2", "Message 2"));

    if ($to == "" || stripos($to, "6") === false)
        return "Pas de numéro de mobile";
//    $to = urlencode("+33628335081");
    else {
        $return = "";
        foreach ($tabMessage as $message)
            $return .= '<a href="#" onclick="dispatchePopObject(\'&msg=' . urlencode($message[2]) . '&fromsms=' . $fromsms . '&to=' . $to . '\', \'sms\',function(){}, \'SMS\', 100);">' . (($message[0] != "") ? img_picto($message[1], $message[0] . "@Synopsis_Tools") : $message[1]) . '</a> ';
        return $return;
    }
}

<?php
$tabUser = getTabUser();

$i = 0;
$newTabUser = array();
$newTabUser2 = array();
foreach($tabUser as $idUser => $nomUser){
    $newTabUser[$idUser] = $i;
    $newTabUser2[$i] = $idUser;
    $i++;
}


function getTabUser() {
    global $user, $db;
    $tabUser = array();
    $tmpUser = new User($db);
    foreach ($_REQUEST as $key => $val) {
        if (preg_match('/^user([0-9]*)$/', $key, $arrTmp)) {
            $tmpUser->fetch($val);
            $tabUser[$val] = $tmpUser->getNomUrl();
        }
    }
    if (count($tabUser) == 0 && isset($_SESSION['AGENDA']['tabUser']))
        $tabUser = $_SESSION['AGENDA']['tabUser'];
    elseif (count($tabUser) == 0)
        $tabUser[$user->id] = $user->getNomUrl();
    else
        $_SESSION['AGENDA']['tabUser'] = $tabUser;
        
    return $tabUser;
}


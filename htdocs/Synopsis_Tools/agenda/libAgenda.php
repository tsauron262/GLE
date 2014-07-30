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

function getPara(){
    if(isset($_REQUEST['workHour']))
        $_SESSION['paraAgenda']['workHour'] = $_REQUEST['workHour'];
    if(isset($_REQUEST['timeTranche']))
        $_SESSION['paraAgenda']['timeTranche'] = $_REQUEST['timeTranche'];
//    echo $_SESSION['paraAgenda']['timeTranche'];die;
    
    if(!isset($_SESSION['paraAgenda']['workHour']))
        $_SESSION['paraAgenda']['workHour'] = 'false';
    if(!isset($_SESSION['paraAgenda']['timeTranche']))
        $_SESSION['paraAgenda']['timeTranche'] = '20';
    return $_SESSION['paraAgenda'];
}

function getTabUser() {
    global $user, $db, $langs;
    $tabUser = array();
    $tmpUser = new User($db);
    foreach ($_REQUEST as $key => $val) {
        if (preg_match('/^user([0-9]*)$/', $key, $arrTmp)) {
            $tmpUser->fetch($val);
            $tabUser[$val] = '<span title="'.$tmpUser->getFullName($langs).'">'. $tmpUser->getNomUrl() ."</span>";
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


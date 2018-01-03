<?php

$tabUser = getTabUser();

$i = 0;
$newTabUser = array();
$newTabUser2 = array();
foreach ($tabUser as $idUser => $nomUser) {
    $newTabUser[$idUser] = $i;
    $newTabUser2[$i] = $idUser;
    $i++;
}

function getPara() {
    if (isset($_REQUEST['chevauche']))
        $_SESSION['paraAgenda']['chevauche'] = $_REQUEST['chevauche'];
    if (isset($_REQUEST['workHour']))
        $_SESSION['paraAgenda']['workHour'] = $_REQUEST['workHour'];
    if (isset($_REQUEST['timeTranche']))
        $_SESSION['paraAgenda']['timeTranche'] = $_REQUEST['timeTranche'];
//    echo $_SESSION['paraAgenda']['timeTranche'];die;
//    print_r($_SESSION['paraAgenda']);die;

    /* val default */
    if (!isset($_SESSION['paraAgenda']['workHour']))
        $_SESSION['paraAgenda']['workHour'] = 'true';
    if (!isset($_SESSION['paraAgenda']['timeTranche']))
        $_SESSION['paraAgenda']['timeTranche'] = '2';
    if (!isset($_SESSION['paraAgenda']['chevauche']))
        $_SESSION['paraAgenda']['chevauche'] = 'true';
    return $_SESSION['paraAgenda'];
}

function getTabUser() {
    global $user, $db, $langs;
    $tabUser = array();
    $tmpUser = new User($db);
    foreach ($_REQUEST['customSelect'] as $key => $val) {
        $tmpUser->fetch($val);
        $tabUser[$val] = '<span title="' . $tmpUser->getFullName($langs) . '">' . $tmpUser->getNomUrl() . "</span>";
    }
    if (count($tabUser) == 0 && isset($_SESSION['AGENDA']['tabUser']))
        $tabUser = $_SESSION['AGENDA']['tabUser'];
    elseif (count($tabUser) == 0)
        $tabUser[$user->id] = $user->getNomUrl();
    else
        $_SESSION['AGENDA']['tabUser'] = $tabUser;
    return $tabUser;
}

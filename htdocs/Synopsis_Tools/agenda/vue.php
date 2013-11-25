<?php

require_once('../../main.inc.php');
llxHeader('<script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Tools/agenda/agenda.js"></script>
    <link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/Synopsis_Tools/agenda/agenda.css" />');

if (isset($_REQUEST['date'])) {
    $tabT = explode("/", $_REQUEST['date']);
    if (isset($tabT[2]))
    $date = new DateTime($tabT[2] . "/" . $tabT[1] . "/" . $tabT[0]);
}
if (!isset($date))
    $date = new DateTime();

$tabUser = getTabUser();

printMenu($tabUser, $date);

//Une semaine
if (isset($_REQUEST['vueSemaine']))
    printSemaine($date, $tabUser);

elseif (isset($_REQUEST['vueJour']))
    printPeriode($date, $tabUser, $nbJours = 1);

//Un mois
else//if (isset($_REQUEST['vueMois']))
    printMois($date, $tabUser);

function getTabUser() {
    global $user;
    $tabUser = array();
    foreach ($_REQUEST as $key => $val) {
        if (preg_match('/^user([0-9]*)$/', $key, $arrTmp)) {
            $tabUser[] = $val;
        }
    }
    if (count($tabUser) == 0)
        $tabUser[] = $user->id;
    return $tabUser;
}

function printMenu($tabUser, $date) {
    global $db;

    $js = "var tabGroup = new Array();";
    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "usergroup_user");
    while ($result = $db->fetch_object($sql)) {
        $js .= "if(!tabGroup[" . $result->fk_usergroup . "]) tabGroup[" . $result->fk_usergroup . "] = new Array();";
        $js .= "tabGroup[" . $result->fk_usergroup . "].push(" . $result->fk_user . ");";
    }
    echo "<script>" . $js . "</script>";


    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "usergroup");
    while ($result = $db->fetch_object($sql)) {
        $select .= "<option value='" . $result->rowid . "'>" . $result->nom . "</option>";
    }
    echo "<form action='' method='post'>";
    echo "<select id='group'>" . $select . "</select>";
    echo "<span class='nbGroup'></span>";


    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1");
    echo "<div class='listUser'><table><tr>";
    $i = 0;
    while ($result = $db->fetch_object($sql)) {
        $i++;
        echo "<td>";
        echo "<input " . (in_array($result->rowid, $tabUser) ? "checked='checked'" : "") . " type='checkbox' class='userCheck' id='user" . $result->rowid . "' name='user" . $result->rowid . "' value='" . $result->rowid . "'/>";
        echo $result->firstname . " " . $result->lastname;
        echo "</td>";
        if ($i > 5) {
            $i = 0;
            echo "</tr><tr>";
        }
    }
    echo "</tr></table></div>";


    echo "<input name='date' type ='date' class='dateVue' value='" . date_format($date, "d/m/Y") . "'/>";

    echo "<input type='submit' class='butAction' name='vueJour' value='Vue jour'/>";
    echo "<input type='submit' class='butAction' name='vueSemaine' value='Vue semaine'/>";
    echo "<input type='submit' class='butAction' name='vueMois' value='Vue mois'/>";
    echo "</form>";
}

function printSemaine($date, $tabUser) {
    $jSem = date_format($date, "w");
    $date = date_sub($date, date_interval_create_from_date_string(($jSem > 0 ? ($jSem - 1) : 6) . " day"));
    printPeriode($date, $tabUser, 7);
}

function printMois($date, $tabUser) {
    global $db;
    foreach ($tabUser as $user) {
        $date = new DateTime(date_format($date, "Y-M-") . "01");
        $jSem = date_format($date, "w");
        $date = date_sub($date, date_interval_create_from_date_string(($jSem > 0 ? ($jSem - 1) : 6) . " day"));
        $userO = new User($db);
        $userO->fetch($user);
        echo $userO->getNomUrl(1) . "<br/>";
        printPeriode($date, $user, 35, "taille1");
        echo "<br/>";
    }
}

function printPeriode($date, $tabUser, $nbJours = 7, $cssPlus = "") {
    $printUser = true;
    if (!is_array($tabUser)) {
        $tabUser = array($tabUser);
        $printUser = false;
    }
    $tabJSem = array("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
    for ($i = 0; $i < $nbJours; $i++) {
        $date2 = strtotime(date_format($date, "Y-m-d"));
        print "<div class='contentOneDay " . $cssPlus . "'>";
        $jSem = date_format($date, "w");
        echo $tabJSem[$jSem] . " " . dol_print_date($date2, "daytext");
        print "<div class='oneDay'>";
        foreach ($tabUser as $user) {
            printOneDayOneUser($user, $date2, $printUser, false, ($jSem == 0 || $jSem == 6)? "bgAgendaFe" : "bgAgenda");
        }
        print "</div></div>";
        if ($jSem == 0)
            echo "<div class='clear'></div>";
        $date = date_add($date, date_interval_create_from_date_string("1 day"));
    }
}

function printOneDayOneUser($userId, $date, $printUser = false, $printDate = false, $bg = "bgAgenda") {
    global $db;
    $heureDebJ = "08";
    $heureFinJ = "18";
    $i = 0;
    $coefx = 0.069444444 * 24 / ($heureFinJ - $heureDebJ);
    $constx = -60 * $heureDebJ * $coefx;
    $coefy = 0;
    $minHeight = 60;
    $sql = $db->query("SELECT *, (`datep2` - `datep`) as duree FROM " . MAIN_DB_PREFIX . "actioncomm WHERE ((datep < '" . date('Y-m-d 23:59:00', $date) . "' AND datep > '" . date('Y-m-d 00:00:00', $date) . "') || (datep < '" . date('Y-m-d 23:59:00', $date) . "' AND datep2 > '" . date('Y-m-d 00:00:00', $date) . "')) AND fk_user_action = " . $userId . " order by duree DESC ");
    print '<div class="calendarSyn">';
    print "<img src='" . DOL_URL_ROOT . "/Synopsis_Tools/agenda/img/".$bg.".jpg' class='bgAgenda'/>";
    if ($printUser) {
        $user = new User($db);
        $user->fetch($userId);
        echo $user->getNomUrl(1);
    }
    if ($printUser && $printDate)
        echo "<br/>";
    if ($printDate)
        echo dol_print_date($date);
    while ($result = $db->fetch_object($sql)) {
        $i++;
        $debuV = strtotime($result->datep);
        $finV = strtotime($result->datep2);

        $debuJ = strtotime(date('Y-M-d', $date) . " 00:00:00"); //Pour les elem sur plusieur jours
        $finJ = strtotime(date('Y-M-d', $date) . " 23:59:59");
        if ($debuJ > $debuV) {
            $debuV = $debuJ;
        }
        if ($finJ < $finV) {
            $finV = $finJ;
        }

        $debu = $debuV;
        $fin = $finV;

        $debuJ = strtotime(date('Y-M-d', $date) . " " . $heureDebJ . ":00:00"); //Pour les elem hors journéé de travail
        $finJ = strtotime(date('Y-M-d', $date) . " " . $heureFinJ . ":00:00");
        if ($debuJ > $debu) {
            $debu = $debuJ;
        }
        if ($finJ < $fin) {
            $fin = $finJ;
        }



        if (isset($result->datep2))
            $duree = $fin - $debu;
        else
            $duree = 0;


        if ($finJ <= $debu) {//Rdv aprés journé de travaile
            $debu = $finJ - 7200;
            $duree = $fin - $debu;
        }

        $minuteDeb = date('H', $debu) * 60 + date('i', $debu);
        $minuteDur = $duree / 60;
        print '<div id="event_' . $i . '" class="event eventAbss" style="top:' . (($minuteDeb * $coefx) + $constx) . '%; height:' . (($minuteDur > $minHeight ? $minuteDur : $minHeight) * $coefx) . '%; left:' . $coefy . '%;">';
        print "<a href='/gle_dev/comm/action/fiche.php?id=" . $result->id . "'>" . $result->label;
        print "<br/>" . date("H:i", $debuV) . " - " . date("H:i", $finV) . "</a>";
        print "<br/>" . $result->note;
        print '</div>';
        $coefy = $coefy + 2;
    }
    print "</div>";
}

?>

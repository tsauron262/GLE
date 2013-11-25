<?php

require_once('../../main.inc.php');
llxHeader();

$date = new DateTime("2013-10-30");

$tabUser = array(1);

//Un mois
//printMois($date, $tabUser);

//Une semaine
//    printSemaine($date, $tabUser);

$tabUser = array(1,2,34,5,6,8,9,7,4,5,6,7);
printPeriode($date, $tabUser, $nbJours = 1);

function printSemaine($date, $tabUser){
        $jSem = date_format($date, "w");
        $date = date_sub($date, date_interval_create_from_date_string(($jSem>0? ($jSem-1) : 6)." day"));
    printPeriode($date, $tabUser, 7);
}
function printMois($date, $tabUser){
    $date = new DateTime(date_format($date, "Y-M-")."01");
        $jSem = date_format($date, "w");
        $date = date_sub($date, date_interval_create_from_date_string(($jSem>0? ($jSem-1) : 6)." day"));
    printPeriode($date, $tabUser, 35, "taille1");
}

function printPeriode($date, $tabUser, $nbJours = 7, $cssPlus = "") {
    $printUser = true;
    if(!is_array($tabUser)){
        $tabUser = array($tabUser);
        $printUser = false;
    }
    $tabJSem = array("Dimanche", "Lundi", "Maedi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
    for ($i = 0; $i < $nbJours; $i++) {
        $date2 = strtotime(date_format($date, "Y-m-d"));
        print "<div class='contentOneDay ".$cssPlus."'>";
        $jSem = date_format($date, "w");
        echo $tabJSem[$jSem] . " " . dol_print_date($date2, "daytext");
        print "<div class='oneDay'>";
        foreach ($tabUser as $user) {
            printOneDayOneUser($user, $date2, $printUser);
        }
        print "</div></div>";
        if ($jSem == 0)
            echo "<div class='clear'></div>";
        $date = date_add($date, date_interval_create_from_date_string("1 day"));
    }
}

function printOneDayOneUser($userId, $date, $printUser = false, $printDate = false) {
    global $db;
    $heureDebJ = "08";
    $heureFinJ = "18";
    $i = 0;
    $coefx = 0.069444444*24/($heureFinJ-$heureDebJ);
    $constx = -60 * $heureDebJ * $coefx;
    $coefy = 0;
    $minHeight = 5;
    $sql = $db->query("SELECT *, (`datep2` - `datep`) as duree FROM " . MAIN_DB_PREFIX . "actioncomm WHERE ((datep < '" . date('Y-m-d 23:59:00', $date) . "' AND datep > '" . date('Y-m-d 00:00:00', $date) . "') || (datep < '" . date('Y-m-d 23:59:00', $date) . "' AND datep2 > '" . date('Y-m-d 00:00:00', $date) . "')) AND fk_user_action = " . $userId . " order by duree DESC ");
    print '<div class="calendarSyn">';
        print "<img src='".DOL_URL_ROOT."/Synopsis_Tools/agenda/img/bgAgenda.jpg' class='bgAgenda'/>";
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
        $debu = strtotime($result->datep);
        $fin = strtotime($result->datep2);
        
        $debuJ = strtotime(date('Y-M-d', $date)." ".$heureDebJ.":00:00");
        $finJ = strtotime(date('Y-M-d', $date)." ".$heureFinJ.":00:00");
        if($debuJ > $debu){
            $debu = $debuJ;
        }
        if($finJ < $fin){
            $fin = $finJ;
        }
        
        
        
        if (isset($result->datep2))
            $duree = $fin - $debu;
        else
            $duree = 0;
        
        $minuteDeb = date('H', $debu) * 60 + date('i', $debu);
        $minuteDur = $duree/60;
        print '<div id="event_' . $i . '" class="event eventAbss" style="top:' . (($minuteDeb * $coefx) + $constx) . '%; height:' . (($minuteDur * $coefx) > $minHeight ? ($minuteDur * $coefx) : $minHeight) . '%; left:' . $coefy . '%;">';
        print $result->label;
        print "<br/><a href='/gle_dev/comm/action/fiche.php?id=" . $result->id . "'>" .  date("d H:i", $debu) . " <br/> " . date("d H:i", $fin) .$duree. "</a>";
        print $result->note;
        print '</div>';
        $coefy = $coefy + 2;
    }
    print "</div>";
}

?>

<?php

require_once('../../main.inc.php');
require_once("libAgenda.php");



$eventsStr = array();
if ($_REQUEST['end'] != "NaN" && $_REQUEST['start'] != "NaN") {
    $_SESSION['dateDebStr'] = $_REQUEST['start'];
    
    $_SESSION['nbJour'] = ($_REQUEST['end'] - $_REQUEST['start'])/(3600*24);
    
    
    $heureOuvree = (isset($_SESSION['paraAgenda']['workHour']) && $_SESSION['paraAgenda']['workHour'] == 'true');
    $sql = ("SELECT *, (`datep2` - `datep`) as duree "
            . "FROM " . MAIN_DB_PREFIX . "actioncomm a LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields on fk_object = a.id "
            . "LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id AND ar.element_type = 'user'"
            . "WHERE ((datep < '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep >= '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "') "//Rdv dbut ds periode fin aprés
            . "|| (datep2 <= '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep2 > '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "')" //fin ds la periode
            . "|| (datep2 > '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep < '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "'))"
            . " AND (fk_user_action IN (" . implode(",", $newTabUser2) . ") || ar.fk_element IN (" . implode(",", $newTabUser2) . ")) AND (fk_action NOT IN (3,8,9,10,30,31,40)) order by datep ASC ");
    $result = $db->query($sql); //avant et aprés periode
//echo $sql;die;
    $f = 0;
    $tabTest = array();
    while ($ligne = $db->fetch_object($result)) {//print_r($ligne);
        $userIdV = $userId = -1;
        
        if($ligne->fk_element > 0 && isset($newTabUser[$ligne->fk_element])){
            $userIdV = $ligne->fk_element;
            $userId = $newTabUser[$ligne->fk_element];
        }
        elseif(isset($newTabUser[$ligne->fk_user_action])){
            $userIdV = $ligne->fk_user_action;
            $userId = $newTabUser[$ligne->fk_user_action];
        }
        
        
        if($userId < 0) 
            continue;//Pas d'utilisateur concerné.
        
        
        if(isset($tabTest[$userId][$ligne->id]))
            continue;//Evenement deja envoyer
        $tabTest[$userId][$ligne->id] = true;

        if ($ligne->label == "")
            $ligne->label = "N/C";
        
//        $ligne->label = str_replace("'", "", $ligne->label);
//        $ligne->note = str_replace("'", "", $ligne->note);
        $ligne->label = str_replace('\\', "", $ligne->label);
        $ligne->note = str_replace("\\", "", $ligne->note);


//        $text = "<a href='" . DOL_URL_ROOT . "/comm/action/card.php?id=" . $ligne->id . "'>" . $ligne->label;
        $text = "<input type='hidden' class='idAction' value='" . $ligne->id . "'/>";
        $text .= "<input type='hidden' class='percent' value='" . $ligne->percent . "'/>";
        $text .= "<a title='" . $ligne->label . "' href='" . DOL_URL_ROOT . "/comm/action/card.php?id=" . substr($ligne->id, 0, 30) . "'>" . $ligne->label . "</a>";
        if ($ligne->fk_soc > 0) {
            $soc = new Societe($db);
            $soc->fetch($ligne->fk_soc);
            $text .= "<br/><br/>" . $soc->getNomUrl(1);
        }
        $text .= "<br/><br/>" . substr($ligne->note, 0, 40);
        $text = str_replace(array("\r\n", "\r", "\n"), "<br />", $text);
        $text = str_replace('"', '\"', $text);
        $text = str_replace("'", '\'', $text);


        if (!isset($ligne->datep2))
            $ligne->datep2 = $ligne->datep;
        if (!isset($ligne->datep))
            $ligne->datep = $ligne->datep2;

        if ($ligne->conf == 1 && $userIdV != $user->id) {
            $text = "<span class='twhite'>Confidentiel</span>";
            $ligne->fk_action = 999;
        }



        $tabColor = array(50 => "#BBCCFF", 70 => "#BBCCFF", 5 => "purple", 2 => "red",
            51 => "red", 54 => "red", 55 => "red", 58 => "red", 66 => "red", 69 => "red",
//            52 => "blue", 53 => "blue", 56 => "blue", 57 => "blue", 63 => "blue", "blue" => 67, "blue" => 68,
            60 => "orange", 63 => "green",
            61 => 'purple',
            64 => "gray", 65 => "gray",
            999 => "black");
        $colorStr = '';
        if (isset($tabColor[$ligne->fk_action]))
            $colorStr = ', "color":"' . $tabColor[$ligne->fk_action] . '"';





        if (isset($ligne->datep)) {
            $date1 = new DateTime($ligne->datep);
            $date2 = new DateTime($ligne->datep2);

            $hour = $date1->format("H");
            $hour2 = $date2->format("H");
            if ($heureOuvree && intval($hour) < 8)
                $date1->setTime(8, 0);
            if ($heureOuvree && intval($hour2) >= 20)
                $date2->setTime(20, 0);



            if (!$heureOuvree || (intval($hour2) == 0 || intval($hour2) > 8))
                $eventsStr[] = '{"id":' . $ligne->id . ', "start":"' . $date1->format('c') . '", "end":"' . $date2->format('c') . '", "title":"' . $text . '", "userId": ' . $userId . $colorStr . '}';
        }
        $f = $f + 1;
    }
    /* mod Drsi(momo) */
    $tabFerie[] = array();
    $yearsF = date('Y', $_REQUEST['end']);
    $tabFerie = getHolidays($yearsF);
    $fin = $_REQUEST['end'];
    $start = $_REQUEST['start'];
    $i = 0;
    while ($i < count($tabFerie)) {

        if ($tabFerie[$i][1] >= $start && $tabFerie[$i][1] <= $fin) {
            $g = 0;
            while ($g < count($newTabUser)) {
                $date1 = new DateTime($tabFerie[$i][0]);
                $date2 = new DateTime(date('Y-m-d 23:59:59', $tabFerie[$i][1]));
                $hour = $date1->format("H");
                $hour2 = $date2->format("H");
                if ($heureOuvree && intval($hour) < 8)
                    $date1->setTime(8, 0);
                if ($heureOuvree && intval($hour2) >= 20)
                    $date2->setTime(20, 0);
                $text2 = '{"start":"' . $date1->format('c') . '", "end":"' . $date2->format('c') . '", "title":"<font color=\"white\"> Ferié </font><font size=5><font color=\"black\"><b><br/><br/>F<br/><br/>É<br/><br/>R<br/><br/>I<br/><br/>É<br/><br/></b></font></font>" ,"userId": ' . $g . ', "color":"grey" }';
//                die($text2);
                $eventsStr[] = $text2;
                $g = $g + 1;
            }
        }
        $i = $i + 1;
    }
    /* fmod Drsi(momo) */
}

echo "[";

echo implode(",", $eventsStr);


echo "]";


/* mod Drsi(momo) */

function getHolidays($year = null) {
    if ($year === null) {
        $year = intval(date('Y'));
    }

    $easterDate = easter_date($year);
    $easterDay = date('j', $easterDate);
    $easterMonth = date('n', $easterDate);
    $easterYear = date('Y', $easterDate);

    $holidays = array(
        // Dates fixes
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $year)),
            mktime(0, 0, 0, 1, 1, $year)
        ), // 1er janvier
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, 5, 1, $year)),
            mktime(0, 0, 0, 5, 1, $year)
        ), // Fête du travail
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, 5, 8, $year)),
            mktime(0, 0, 0, 5, 8, $year)
        ), // Victoire des alliés  
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, 7, 14, $year)),
            mktime(0, 0, 0, 7, 14, $year)
        ), // Fête nationale
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, 8, 15, $year)),
            mktime(0, 0, 0, 8, 15, $year)
        ), // Assomption 
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, 11, 1, $year)),
            mktime(0, 0, 0, 11, 1, $year)
        ), // Toussaint
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, 11, 11, $year)),
            mktime(0, 0, 0, 11, 11, $year)
        ), // Armistice
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, 12, 25, $year)),
            mktime(0, 0, 0, 12, 25, $year)
        ), // Noel
        // Dates variables
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, $easterMonth, $easterDay + 1, $easterYear)),
            mktime(0, 0, 0, $easterMonth, $easterDay + 1, $easterYear)
        ),
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear)),
            mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear)
        ),
        array(
            date('Y-m-d H:i:s', mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear)),
            mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear)
        )
    );

    sort($holidays);

    return $holidays; /* fin mod Drsi(momo) */
}

?>
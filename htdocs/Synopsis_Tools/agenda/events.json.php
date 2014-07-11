    <?php

require_once('../../main.inc.php');
require_once("libAgenda.php");



$eventsStr = array();
$sql = ("SELECT *, (`datep2` - `datep`) as duree FROM " . MAIN_DB_PREFIX . "actioncomm a LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields on fk_object = a.id WHERE ((datep < '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep > '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "') "
        . "|| (datep2 < '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep2 > '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "')"
        . "|| (datep2 > '" . date('Y-m-d 23:59:00', $_REQUEST['end']) . "' AND datep < '" . date('Y-m-d 00:00:00', $_REQUEST['start']) . "')) AND fk_user_action IN (" . implode(",", $newTabUser2) . ") order by duree DESC ");
$result = $db->query($sql);
//echo $sql;
$f = 0;
while ($ligne = $db->fetch_object($result)) {
    $userId = $newTabUser[$ligne->fk_user_action];
//        $text = "<a href='" . DOL_URL_ROOT . "/comm/action/fiche.php?id=" . $ligne->id . "'>" . $ligne->label;
    $text = "<input type='hidden' class='idAction' value='" . $ligne->id . "'/>";
    $text .= "<input type='hidden' class='percent' value='" . $ligne->percent . "'/>";
    $text .= "<a title='".$ligne->label."' href='" . DOL_URL_ROOT . "/comm/action/fiche.php?id=" . $ligne->id . "' onclick=\"dispatchePopIFrame('" . DOL_URL_ROOT . "/comm/action/fiche.php?id=" . $ligne->id . "&action=edit&optioncss=print', function(){ $('#calendar').weekCalendar('refresh');}, '" . $ligne->label . "', 100); return false;\">" . $ligne->label;
    if ($ligne->fk_soc > 0) {
        $soc = new Societe($db);
        $soc->fetch($ligne->fk_soc);
        $text .= "<br/><br/>" . $soc->getNomUrl(1);
    }
    $text = str_replace(array("\r\n", "\r", "\n"), "<br />", $text);
    $text = str_replace('"', '\"', $text);
    if (!isset($ligne->datep2))
        $ligne->datep2 = $ligne->datep;
    if (!isset($ligne->datep))
        $ligne->datep = $ligne->datep2;
    
    if($ligne->conf == 1 && $ligne->fk_user_action != $user->id){
        $text = "Confidentiel";
        $ligne->fk_action = 999;
    }
    
    
    
    $tabColor = array(50 => "#BBCCFF", 5 => "purple", 2 => "red",
        51 => "red", 54 => "red", 55 => "red", 58 => "red", 66 => "red",
        52 => "blue", 53 => "blue", 56 => "blue", 57 => "blue", 63 => "blue",
        60 => "orange", 63 => "green",
        61 => 'purple',
        64 => "gray", 65 => "gray",
        999 => "black");
    $colorStr = '';
    if (isset($tabColor[$ligne->fk_action]))
        $colorStr = ', "color":"' . $tabColor[$ligne->fk_action] . '"';
    
    
    if (isset($ligne->datep))
        $eventsStr[] = '{"id":' . $ligne->id . ', "start":"' . date('c', $db->jdate($ligne->datep)) . '", "end":"' . date('c', $db->jdate($ligne->datep2)) . '", "title":"' . $text . '", "userId": ' . $userId . $colorStr . '}';
    $f = $f +1;
}
/*mod Drsi(momo)*/
$tabFerie[] = array();
$yearsF = date('Y', $_REQUEST['end']);
$tabFerie = getHolidays($yearsF);
$fin = $_REQUEST['end'];
$start = $_REQUEST['start'];
$i=0;
while ($i < count($tabFerie)){
    
    if ($tabFerie[$i][1] >= $start && $tabFerie[$i][1] <= $fin){
        $g =0;
        while ($g < count($newTabUser)){
            $finjours= date('Y-m-d 23:59:59', $tabFerie[$i][1]);
            $text2 = '{"start":"' . date('c', $db->jdate($tabFerie[$i][0])) . '", "end":"' . date('c', $db->jdate($finjours)) . '", "title":"<font color=\"white\"> Ferié </font><font size=5><font color=\"black\"><b><br/><br/>F<br/><br/>É<br/><br/>R<br/><br/>I<br/><br/>É<br/><br/></b></font></font>" ,"userId": ' . $g . ', "color":"grey" }';
            $eventsStr[] = $text2;
            $g = $g +1;
        }
    } 
    $i=$i+1;
}
/*fmod Drsi(momo)*/

echo "[";

echo implode(",", $eventsStr);


echo "]";



/*mod Drsi(momo)*/function getHolidays($year = null)
{
  if ($year === null)
  {
    $year = intval(date('Y'));
  }
 
  $easterDate  = easter_date($year);
  $easterDay   = date('j', $easterDate);
  $easterMonth = date('n', $easterDate);
  $easterYear   = date('Y', $easterDate);
 
  $holidays = array(
    // Dates fixes
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, 1,  1,  $year)),
        mktime(0, 0, 0, 1,  1,  $year)
    ),// 1er janvier
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, 5,  1,  $year)),
        mktime(0, 0, 0, 5,  1,  $year)
    ),// Fête du travail
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, 5,  8,  $year)),
        mktime(0, 0, 0, 5,  8,  $year)
    ),// Victoire des alliés  
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, 7,  14, $year)),
        mktime(0, 0, 0, 7,  14, $year)
    ),// Fête nationale
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, 8,  15, $year)),
        mktime(0, 0, 0, 8,  15, $year)
    ), // Assomption 
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, 11, 1, $year)),
        mktime(0, 0, 0, 11, 1, $year)
    ),// Toussaint
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, 11, 11, $year)),
        mktime(0, 0, 0, 11, 11, $year)
    ),// Armistice
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, 12, 25, $year)),
        mktime(0, 0, 0, 12, 25, $year)
    ), // Noel
 
    // Dates variables
      
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear)),
        mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear)
    ), 
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear)),
        mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear)
    ),
    array(
        date('Y-m-d H:i:s' ,mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear)),
        mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear)
    )
  );
 
  sort($holidays);
 
  return $holidays;/*fin mod Drsi(momo)*/
}
?>
<?php

require_once('../../main.inc.php');
require_once("libAgenda.php");

$tabUserId = array();
$tabUser = getTabUser();
$i = 0;
$tabJsIdUser = 'tabUserId = Array();';
foreach ($tabUser as $userId => $nom) {
    $i++;
//    $tabUserId[] = "'".$userId.":".$nom."'";
    $tabJsIdUser .= 'tabUserId.push(' . $userId . ');';
}
$userStr = "'" . implode("','", $tabUser) . "'";

$js = <<<EOF
        <script type="text/javascript" src="../agenda/agenda.js"></script>
    <link rel="stylesheet" type="text/css" href="../agenda/agenda.css" />
 <link rel='stylesheet' type='text/css' href='../jquery/calendar/libs/css/smoothness/jquery-ui-1.8.11.custom.css' />
  <link rel="stylesheet" type="text/css" href="../jquery/calendar/jquery.weekcalendar.css" />
  <link rel="stylesheet" type="text/css" href="../jquery/calendar/skins/default.css" />
  <link rel="stylesheet" type="text/css" href="../jquery/calendar/skins/gcalendar.css" />
  <style type="text/css">
    body {
      font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
      margin: 0;
    }

    h1 {
      margin:0 0 2em;
      padding: 0.5em;
      font-size: 1.3em;
    }

    p.description {
      font-size: 0.8em;
      padding: 1em;
//      position: absolute;
      top: 1.2em;
      margin-right: 400px;
    }

    #calendar_selection {
      font-size: 0.7em;
//      position: absolute;
      top: 1em;
      right: 1em;
      padding: 1em;
      background: #ffc;
      border: 1px solid #dda;
      width: 270px;
    }

    #message {
      font-size: 0.7em;
//      position: absolute;
      top: 1em;
      right: 320px;
      padding: 1em;
      background: #ddf;
      border: 1px solid #aad;
      width: 270px;
    }
  </style>

  <script type='text/javascript' src='../jquery/calendar/libs/jquery-1.4.4.min.js'></script>
  <script type='text/javascript' src='../jquery/calendar/libs/jquery-ui-1.8.11.custom.min.js'></script>
  <script type='text/javascript' src='../jquery/calendar/libs/jquery-ui-i18n.js'></script>

  <script type="text/javascript" src="../jquery/calendar/libs/date.js"></script>
  <script type="text/javascript" src="../jquery/calendar/jquery.weekcalendar.js"></script>
  <script type="text/javascript">
EOF;
$js .= $tabJsIdUser;
$js .= <<<EOF
  (function($) {
    var d = new Date();
    d.setDate(d.getDate() - d.getDay());
    var year = d.getFullYear();
    var month = d.getMonth();
    var day = d.getDate();


    d = new Date();
    d.setDate(d.getDate() -(d.getDay() - 3));
    year = d.getFullYear();
    month = d.getMonth();
    day = d.getDate();

        
        function save(calEvent){
            jQuery.ajax({
                url: DOL_URL_ROOT + "/Synopsis_Tools/agenda/ajax.php",
                type: "POST",
                datatype: "xml",
                data: "setUser=" + calEvent.userId + "&id=" + calEvent.id + "&start=" + calEvent.start.getTime() + "&end=" + calEvent.end.getTime(),
                error: function(msg) {
                    alert("erreur");
                }
            });
        }

    $(document).ready(function() {
      var Ocalendar = $('#calendar').weekCalendar({
        timeslotsPerHour: 4,
        date: new Date(toDateUrl(new Date(), 2)+'T08:00:00.000+00:00'),
        height: function(Ocalendar){
          return $(window).height() - $('h1').outerHeight(true);
        },
        eventRender : function(calEvent, Oevent) {
          if (calEvent.end.getTime() < new Date().getTime()) {
            Oevent.css('backgroundColor', '#aaa');
            Oevent.find('.wc-time').css({
              backgroundColor: '#999',
              border:'1px solid #888'
            });
          }
        },
      eventResize: function(calEvent, Oevent) {
        save(calEvent);
      },
      eventDrop: function(calEvent, Oevent) {
        save(calEvent);
      },
        eventNew : function(calEvent, Oevent, FreeBusyManager, calendar) {
            start = calEvent.start;
            end = calEvent.end;
            end -= 60*1000;
            back = document.location.href;
            back = escape(back);
            back = back.replace(/\//g, "%2F");
            newUrl = "../../comm/action/fiche.php?action=create&datep="+toDateUrl(start)+"&datef="+toDateUrl(end)+"&affectedto="+tabUserId[parseInt(calEvent.userId)]+"&backtopage="+back;
            window.location.replace(newUrl);
        },
        
        data: 'events.json.php',
        users: [$userStr],
        showAsSeparateUser: true,
        displayOddEven: true,
        displayFreeBusys: true,
        daysToShow: 5,
        switchDisplay: {'1 journée': 1, '3 journées': 3, 'work week': 5, 'full week': 7},
        headerSeparator: ' ',
        useShortDayNames: true,
        // I18N
        firstDayOfWeek: $.datepicker.regional['fr'].firstDay,
        shortDays: $.datepicker.regional['fr'].dayNamesShort,
        longDays: $.datepicker.regional['fr'].dayNames,
        shortMonths: $.datepicker.regional['fr'].monthNamesShort,
        longMonths: $.datepicker.regional['fr'].monthNames,
        dateFormat: 'd F y'
      });
    });
  })(jQuery);
        
        
    function toDateUrl(time, option) {
        time = new Date(time);
        result = time.getFullYear();
        if(option == 2)
            result = result + "-";
        if (time.getMonth() < 9)
            result = result + "0";
        result = result + (time.getMonth() + 1);
        if(option == 2)
            result = result + "-";
        if (time.getDate() < 10)
            result = result + "0";
        result = result + time.getDate();
        if(option == 2){
            return result;
        }
        if (time.getHours() < 10)
            result = result + "0";
        result = result + time.getHours();
        if (time.getMinutes() < 10)
            result = result + "0";
        result = result + time.getMinutes();
        return result;
    }
  </script>
        
EOF;




llxHeader($js);

//if (isset($_REQUEST['date'])) {
//    $tabT = explode("/", $_REQUEST['date']);
//    if (isset($tabT[2]))
//        $date = new DateTime($tabT[2] . "/" . $tabT[1] . "/" . $tabT[0]);
//    $nbJours = $_REQUEST['nbJours'];
//    if (isset($_REQUEST['dateMoins']))
//        $date = date_add($date, date_interval_create_from_date_string("-" . $nbJours . " day"));
//    if (isset($_REQUEST['datePlus']))
//        $date = date_add($date, date_interval_create_from_date_string("+" . $nbJours . " day"));
//}
//if (!isset($date))
//    $date = new DateTime();
//
//$vue = (isset($_REQUEST['vueSemaine']) ? 7 : (isset($_REQUEST['vueJour']) ? 1 : (isset($_REQUEST['vueMois']) ? 30 : (isset($_REQUEST['oldVue']) ? $_REQUEST['oldVue'] : 30))));









printMenu($tabUser);



echo '

  <div id="calendar"></div>
</body>
</html>';

////Une semaine
//if ($vue == 7)
//    printSemaine($date, $tabUser);
//
//elseif ($vue == 1)
//    printPeriode($date, $tabUser, 1);
//
////Un mois
//else//if (isset($_REQUEST['vueMois']))
//    printMois($date, $tabUser);


//function printMoinsPlus($date, $nbJours) {
//    $dateM = date_sub($date, date_interval_create_from_date_string("-" . $nbJours . " day"));
//    $dateP = date_sub($date, date_interval_create_from_date_string("+" . $nbJours . " day"));
//    print "<button name='dateMois' value='<-'/>";
//    echo "</form>";
//}

function printMenu($tabUser) {
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
    echo "<div class='contentListUser'><span class='nbGroup'></span>";


    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1 ORDER BY firstname");
    echo "<div class='listUser'><table><tr>";
    $i = 0;
    while ($result = $db->fetch_object($sql)) {
        $i++;
        echo "<td>";
        echo "<input " . (isset($tabUser[$result->rowid]) ? "checked='checked'" : "") . " type='checkbox' class='userCheck' id='user" . $result->rowid . "' name='user" . $result->rowid . "' value='" . $result->rowid . "'/>";
        echo "<label for='user" . $result->rowid . "'>" . $result->firstname . " " . $result->lastname . "</label>";
        echo "</td>";
        if ($i > 5) {
            $i = 0;
            echo "</tr><tr>";
        }
    }
    echo "</tr></table></div></div>";

//    $form = new Form($db);
//    echo $form->select_date($date, 'date');
////    echo "<input name='date' type ='date' class='dateVue' value='" . date_format($date, "d/m/Y") . "'/>";
//
//    echo "<input type='hidden' name='oldVue' value='" . $vue . "'/>";
//    echo "<input type='hidden' name='nbJours' value='" . $vue . "'/>";
//    echo "<input type='submit' class='butAction' name='vueJour' value='Vue jour'/>";
//    echo "<input type='submit' class='butAction' name='vueSemaine' value='Vue semaine'/>";
//    echo "<input type='submit' class='butAction' name='vueMois' value='Vue mois'/>";
//    echo "<br/>";
//    echo "<br/>";
//    echo "<input type='submit' class='butAction' name='dateMoins' value='<='/>";
    echo "<input type='submit' class='butAction' name='val' value='Valider'/>";
    echo "</form>";
    echo "<br/><br/>";
}
?>

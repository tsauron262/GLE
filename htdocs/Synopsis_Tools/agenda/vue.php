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
 <link rel='stylesheet' type='text/css' href='./calendar/libs/css/smoothness/jquery-ui-1.8.11.custom.css' />
  <link rel="stylesheet" type="text/css" href="./calendar/jquery.weekcalendar.css" />
  <link rel="stylesheet" type="text/css" href="./calendar/skins/default.css" />
  <link rel="stylesheet" type="text/css" href="./calendar/skins/gcalendar.css" />
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
        
    .wc-header .wc-user-header a, .wc-header .wc-user-header{
        overflow: visible;
        height: 60px;
        width: 60px;
        -moz-transform:rotate(-45deg);
        -webkit-transform:rotate(-45deg); 
        -o-transform:rotate(-90deg);
        -ms-transform : rotate(-90deg) 
    }
    .ui-widget-content td.ui-state-active{
        background: none;
    }
  </style>


  <script type="text/javascript" src="./calendar/jquery.weekcalendar.js"></script>
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
          return $(window).height() - $('h1').outerHeight(true) - 110;
        },
        eventRender : function(calEvent, Oevent) {
          if (calEvent.color != ''){
              Oevent.css('backgroundColor', calEvent.color);
              Oevent.find('.wc-time').css({
                backgroundColor: calEvent.color,
              });
          }
          else{
            if (calEvent.end.getTime() < new Date().getTime()) {
              Oevent.css('backgroundColor', '#aaa');
              Oevent.find('.wc-time').css({
                backgroundColor: '#999',
                border:'1px solid #888'
              });
            }
          }
        },
      eventResize: function(calEvent, Oevent) {
        save(calEvent);
      },
      eventDrop: function(calEvent, Oevent) {
        save(calEvent);
      },
        eventNew : function(calEvent, Oevent, FreeBusyManager, calendar) {
            start = calEvent.start.getTime();
            end = calEvent.end.getTime();
            end -= 60*1000;
            back = document.location.href;
            back = escape(back);
            back = back.replace(/\//g, "%2F");
            newUrl = "../../comm/action/fiche.php?action=create&datep="+toDateUrl(start)+"&datef="+toDateUrl(end)+"&affectedto="+tabUserId[parseInt(calEvent.userId)]+"&optioncss=print&backtopage="+back;
            dispatchePopIFrame(newUrl, function(){ $('#calendar').weekCalendar('refresh'); }, 'New Action', 1);
//            window.location.href = newUrl;
        },
        
        data: 'events.json.php',
        users: [$userStr],
        showAsSeparateUser: false,
        displayOddEven: true,
        displayFreeBusys: true,
        daysToShow: 5,
        switchDisplay: {'1 journée': 1, '3 journées': 3, 'work week': 5, 'full week': 7},
        headerSeparator: ' ',
        useShortDayNames: true,
        // I18N
        firstDayOfWeek: $.datepicker.regional['fr_FR'].firstDay,
        shortDays: $.datepicker.regional['fr_FR'].dayNamesShort,
        longDays: $.datepicker.regional['fr_FR'].dayNames,
        shortMonths: $.datepicker.regional['fr_FR'].monthNamesShort,
        longMonths: $.datepicker.regional['fr_FR'].monthNames,
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
        result = result +""+ (time.getMonth() + 1);
        if(option == 2)
            result = result + "-";
        if (time.getDate() < 10)
            result = result + "0";
        result = result +""+ time.getDate();
        if(option == 2){
            return result;
        }
        if (time.getHours() < 10)
            result = result + "0";
        result = result +""+ time.getHours();
        if (time.getMinutes() < 10)
            result = result + "0";
        result = result +""+ time.getMinutes();
        return result;
    }
  </script>
EOF;




llxHeader($js);



printMenu($tabUser);



echo '

  <div id="calendar"></div>
</body>
</html>';

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
    echo "<select id='group'><option value='0'>Groupes</option>" . $select . "</select>";
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
    
    echo "<input type='submit' class='butAction' name='val' value='Valider'/>";
    echo "</form>";
    echo "<br/><br/>";
}
?>

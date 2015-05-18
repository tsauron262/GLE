<?php

require_once('../../main.inc.php');
require_once("libAgenda.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

global $isMobile;
if($isMobile)
    $conf->global->MAIN_HIDE_LEFT_MENU = true;

$tabUserId = array();
$tabUser = getTabUser();
if(isset($_REQUEST['timeTranche']))
    $_REQUEST['workHour'] = (isset($_REQUEST['workHour']) && $_REQUEST['workHour'] == 'on')? 'true' : 'false';
$tabPara = getPara();
$i = 0;
$tabJsIdUser = 'tabUserId = Array();';
foreach ($tabUser as $userId => $nom) {
    $i++;
//    $tabUserId[] = "'".$userId.":".$nom."'";
    $tabJsIdUser .= 'tabUserId.push(' . $userId . ');';
}
$userStr = "'" . implode("','", $tabUser) . "'";

$js = ' <script src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jstree/jquery.cookie.js" type="text/javascript"></script>';
$js .= <<<EOF
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
EOF;


  $js .= '<script type="text/javascript" src="./calendar/jquery.weekcalendar.js"></script>';
  $js .= '<script type="text/javascript">';
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
                url: DOL_URL_ROOT + "/synopsistools/agenda/ajax.php",
                type: "POST",
                datatype: "xml",
                data: "setUser=" + calEvent.userId + "&id=" + calEvent.id + "&start=" + calEvent.start.getTime() + "&end=" + calEvent.end.getTime() + "&clone="+calEvent.clone,
                error: function(msg) {
                    alert("erreur");
                },
                success: function(msg) {
                    $('#calendar').weekCalendar('refresh');
                }
            });
        }

    $(document).ready(function() {
      var Ocalendar = $('#calendar').weekCalendar({
EOF;
$js .= "
        timeslotsPerHour: ".$_SESSION['paraAgenda']['timeTranche'].",
        businessHours: {start: 8, end: 20, limitDisplay: ".$_SESSION['paraAgenda']['workHour']."},
        timeslotHeight: 25,
        ";    
$js .= <<<EOF
        defaultEventLength: 2,
        date: new Date(toDateUrl(new Date(), 2)+'T08:00:00.000+00:00'),
        height: function(Ocalendar){
          return $(window).height() - $('h1').outerHeight(true) - 110;
        },
        calendarAfterLoad: function(element) {
            initCopyColler();
            initSimult();
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
        idActionCopy = $.cookie("copyAction");
        idActionCut = $.cookie("cutAction");
        if(idActionCut > 0){
            $.cookie("cutAction", 0);
            calEvent.id = idActionCut;
            save(calEvent);
        } else if(idActionCopy > 0){
            $.cookie("copyAction", 0);
            calEvent.id = idActionCopy;
            calEvent.clone = true;
            save(calEvent);
        } else{
                start = calEvent.start.getTime();
                end = calEvent.end.getTime();
                end -= 60*1000;
                back = document.location.href;
                back = escape(back);
                back = back.replace(/\//g, "%2F");
                newUrl = "../../comm/action/card.php?action=create&datep="+toDateUrl(start)+"&datef="+toDateUrl(end)+"&assignedtouser="+tabUserId[parseInt(calEvent.userId)]+"&optioncss=print&backtopage="+back;
                dispatchePopIFrame(newUrl, function(){ $('#calendar').weekCalendar('refresh'); }, 'New Action', 100);
    //            window.location.href = newUrl;
            }
        },
        
        data: 'events.json.php',
        users: [$userStr],
        showAsSeparateUser: false,
        displayOddEven: true,
        displayFreeBusys: true,
EOF;
if(isset($_SESSION['dateDebStr'])){
    $dateDebStr = $_SESSION['dateDebStr'];
}
else{
    $dateDeb = new DateTime("");
    $dateDebStr = $dateDeb->getTimestamp();
}


if(isset($_SESSION['nbJour'])){
    $nbJour = $_SESSION['nbJour'];
}
else{
    $nbJour = 6;
}
$js .= '
        date: '.$dateDebStr.'000,
        daysToShow: ' . ((count($tabUser) > 5 && $nbJour > 3) ? '3' : $nbJour) . ',';

$js .= "switchDisplay: {'1 journée': 1, '3 journées': 3";
if (count($tabUser) < 6)
    $js .= ", 'work week': 6, 'full week': 7";
$js .= "},";

$js .= <<<EOF
        headerSeparator: ' ',
        useShortDayNames: true,
        // I18N
        firstDayOfWeek: $.datepicker.regional['fr_FR'].firstDay,
        shortDays: $.datepicker.regional['fr_FR'].dayNames,
        longDays: $.datepicker.regional['fr_FR'].dayNames,
        shortMonths: $.datepicker.regional['fr_FR'].monthNamesShort,
        longMonths: $.datepicker.regional['fr_FR'].monthNames,
        dateFormat: 'd F y (W)'
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
        
        
divActCli = initCtrlV = null;
setInterval("blink(divActCli)",500);
function initCopyColler(){
//    $(elem).addClass("actif");
        var ctrlDown = false;
        var ctrlKey = 17, pommeKey = 91, pommeKey2 = 222, vKey = 86, cKey = 67, xKey = 88, zKey = 90;
        var survol = false;
        
        $(".wc-cal-event").mouseover(function()
        {
            survol = $(this).find(".idAction").attr("value");
        }).mouseout(function() {
            survol = null;
        });
        
        $(document).keydown(function(e)
        {
            if (e.keyCode == ctrlKey || e.keyCode == pommeKey || e.keyCode == pommeKey2)
                ctrlDown = true;
        
//        alert(e.keyCode+" | "+ctrlDown);
            if (ctrlDown && (((e.keyCode == cKey || e.keyCode == xKey) && survol) || e.keyCode == zKey)){
//        alert("copiecoupe");
                divAct =  $(".idAction[value='"+survol+"']").parent().parent();
                divActs =  $(".idAction").parent().parent();
                divActs.css({ 'opacity' : 0.8 });
                divActCli = null;
                divActs.show();
                $.cookie("cutAction", "");
                $.cookie("copyAction", "");
                if (e.keyCode == cKey){
                    $.cookie("copyAction", survol);
                    divActCli =  divAct;
                }
                if (e.keyCode == xKey){
                    divAct.css({ 'opacity' : 0.4 });
                    $.cookie("cutAction", survol);
                }
            }
            if (ctrlDown && (e.keyCode == vKey) && initCtrlV == null){
                alert("Dessiner à l'endroit souhaité");
                initCtrlV = true;
            }
        }).keyup(function(e)
        {
            if (e.keyCode == ctrlKey || e.keyCode == pommeKey || e.keyCode == pommeKey2)
                ctrlDown = false;
        });
        
        
        
        $(".percent[value='-2']").each(function(){
            $(this).parent().parent().css("background-image", "url("+DOL_URL_ROOT+"/synopsistools/agenda/barrer.png)");  
        });
        $(".percent[value='0']").each(function(){
            $(this).parent().parent().css("background-image", "url("+DOL_URL_ROOT+"/synopsistools/agenda/point.png)");  
        });
}
        
function blink(ob) { 
        if(ob !== null){
    if (ob.is(':visible')) 
        ob.hide(); 
    else
        ob.show();  
        }
}  
  </script>
EOF;




llxHeader($js);



$head = calendars_prepare_head($paramnoaction);

dol_fiche_head($head, "team", $langs->trans('Agenda'), 0, 'action');


echo "<div class='floatL'>";
printMenu($tabUser);
echo "</div>";


echo "<div class='floatL'>";
echo '<form>Interval : <select name="timeTranche">';
foreach(array(5,10,15,20,30) as $time)
    echo '<option value="'.(60/$time).'" '.((60/$time) == $_SESSION['paraAgenda']['timeTranche'] ? 'selected="selected"' : "") .'>'.$time.'</option>';
echo '</select>';
echo "<label style='margin-left:11px' for='workHour'>Heures ouvrées : </label><input type='checkbox' id='workHour' name='workHour' ".("true" == $_SESSION['paraAgenda']['workHour'] ? 'checked="checked"' : "") .'/>';
echo "<input type='submit' value='Ok' class='butAction'/></form>";
echo "</div>";



echo "<div class='floatL'>";
echo '<input type="text" class="datePicker" id="dateChange"/>';
echo "<button class='butAction' onclick='"
. "dateTab = $(\"#dateChange\").attr(\"value\").split(\"/\");"
. "dateStr = dateTab[2] +\"-\"+ dateTab[1] +\"-\"+ dateTab[0];"
. "$(\"#calendar\").weekCalendar(\"gotoWeek\", new Date(dateStr));"
        . "' >Ok</button>";
echo "</div><div class='clear'></div>";


echo '

  <div id="calendar"></div>
</body>
</html>';

function printMenu($tabUser) {
    global $db,$user;

    $js = "var tabGroup = new Array();";
    $js .= "tabGroup[-1] = new Array();";
    $js .= "tabGroup[-1].push(".$user->id.");";
    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "usergroup_user");
    while ($result = $db->fetch_object($sql)) {
        $js .= "if(!tabGroup[" . $result->fk_usergroup . "]) tabGroup[" . $result->fk_usergroup . "] = new Array();";
        $js .= "tabGroup[" . $result->fk_usergroup . "].push(" . $result->fk_user . ");";
    }
    echo "<script>" . $js . "</script>";


    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "usergroup ORDER BY nom");
    while ($result = $db->fetch_object($sql)) {
        $select .= "<option value='" . $result->rowid . "'>" . $result->nom . "</option>";
    }
    echo "<form action='' method='post'>";
    echo "<select id='group'><option value='0'>Groupes</option><option value='-1'>Moi</option>" . $select . "</select>";
    echo "<div class='contentListUser'><a href='#'><span class='nbGroup'></span></a>";


    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1 ORDER BY firstname");
    echo "<div class='listUser'>"
    . "Classement alphabétique horizontal<br/><br/>"
            . "<table><tr>";
    $i = 0;
        echo "<td>";
    while ($result = $db->fetch_object($sql)) {
        $i++;
        echo "<input " . (isset($tabUser[$result->rowid]) ? "checked='checked'" : "") . " type='checkbox' class='userCheck' id='user" . $result->rowid . "' name='user" . $result->rowid . "' value='" . $result->rowid . "'/>";
        echo "<label for='user" . $result->rowid . "'>" . $result->firstname . " " . $result->lastname . "</label>";
        if ($i > 20) {
            $i = 0;
        echo "</td><td>";
//            echo "</tr><tr>";
        }
        else
            echo "<br/>";
    }
    echo "</td></tr></table><br/></div></div>";

    echo "<input type='submit' class='butAction' name='val' value='Valider'/>";
    echo "</form>";
    echo "<div class='listUser'><br/><br/></div>";
}

?>

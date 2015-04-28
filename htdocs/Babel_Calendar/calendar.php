<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 avr. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : calendar.php
  * GLE-1.2
  */

    require_once('../main.inc.php');


    $js = "";
    $js .= "<link rel='stylesheet' type='text/css' href='".DOL_URL_ROOT."/Synopsis_Common/jquery/fullcalendar/fullcalendar.css' />";
    $js .= "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/fullcalendar/fullcalendar.js'></script>";

    if($_REQUEST['type']=='Affaire' && $_REQUEST['id']> 0){
        $js.= '<script>var type="Affaire";';
        $js.= ' var id='.$_REQUEST['id'].';</script>';
    } else if($_REQUEST['type']=='Affaire'){
        $js.= '<script>var type="Affaire";</script>';
        $js.= '<script>var id="";</script>';
    } else{
        $js.= '<script>var type="";</script>';
        $js.= '<script>var id="";</script>';
    }


    $js .= <<<EOF
    <script>
    var extra = "";
    if (type == 'Affaire') extra += "&type=Affaire";
    if (id > 0) extra += "&id="+id;
    jQuery(document).ready(function() {
        jQuery('#calendar').fullCalendar({
            theme: true,
            weekends: true,
            firstDay: 1,
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            height: 650,
            axisFormat: 'HH(:mm)',
            selectable: false,
            editable: false,
            selectHelper: false,
            events: 'ajax/calDatas-json_response.php?1=1'+extra,
            buttonText: {
                prev:     '&nbsp;&#9668;&nbsp;',  // left triangle
                next:     '&nbsp;&#9658;&nbsp;',  // right triangle
                prevYear: '&nbsp;&lt;&lt;&nbsp;', // <<
                nextYear: '&nbsp;&gt;&gt;&nbsp;', // >>
                today:    'Aujourd\'hui',
                month:    'mois',
                week:     'semaine',
                day:      'jour',
            },
            titleFormat:{
                month: 'MM/yyyy',
                week: "dd/MM{ '&#8212;' dd/MM}", // Sep 7 - 13 2009
                day: 'dd/MM/yyyy'                  // Tuesday, Sep 8, 2009

            },
            columnFormat:{
                month: 'ddd',
                week: 'dd dd/MM', // Mon 9/7
                day: 'ddd dd/MM'
            },
            timeFormat: {
                agenda: 'HH:mm{ - HH:mm}', // 5:00 - 6:30
                '': 'HH(:mm)'
            },
            monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
            monthNamesShort: ['Jan','Fév','Mar',"Avr","Mai","Jun","Jui","Aou","Sep","Oct","Nov","Déc"],
            dayNames: ['Dimanche',"Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"],
            dayNamesShrot: ['Dim','Lun','Mar','Mer',"Jeu","Ven","Sam"],
            loading: function(isLoading, view){
                if (isLoading)
                {
                    jQuery('#loading').effect('slide');
                } else {
                    jQuery('#loading').effect('drop');
                }
            },
            dayClick: function(date, allDay, jsEvent, view) {
//                if (allDay) {
//                    alert('Clicked on the entire day: ' + date);
//                }else{
//                    alert('Clicked on the slot: ' + date);
//                }
//                alert('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);
//                alert('Current view: ' + view.name);
//                jQuery(this).css('background-color', 'red');
            },
            eventClick: function(calEvent, jsEvent, view) {
//                alert('Event: ' + calEvent.title);
//                alert('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);
//                alert('View: ' + view.name);
//                jQuery(this).css('border-color', 'blue');
            },
//            select: function( startDate, endDate, allDay, jsEvent, view )
//            {
//                console.log( startDate, endDate, allDay, jsEvent, view );
//            },
//            dragOpacity:{
//                agenda: 0.8,
//                '': 0.8,
//            },
//            eventDrop: function( event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view ) {
//                console.log( event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view );
//            },
//            eventResize: function( event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view ){
//                console.log( event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view );
//            }

        });
    });
    </script>

EOF;
    $arrColor = array('#D40808','#DF8713','#89DF00','#08D490','#D40890','#08D408','#9008D4','#90D408','#0808D4','#D49008', '#0890D4');


    llxHeader($js,"Calendrier");
print "<br/>";
    print "<div style='display: none; width: 20em;' id='loading' class='ui-state-highlight'>Chargement en cours ...</div>";
print "<br/>";

    print "<div id='calendar'></div>";

    $affaire = 0;
    $contrat = 1;
    $commande = 2;
    $commandeFourn = 3;
    $exped=4;
    $facture = 5;
    $factfourn = 6;
    $livraison = 7;
    $projet = 8;
    $propal = 9;


    if ($_REQUEST['type'] == "Affaire" && $_REQUEST['id'] > 0)
    {
        print "<br/><br/>";
        print "<style> .color{ border: 1px Solid #000; height: 1em; width: 1em; }  </style>";
        print "<div style='border:1px Solid #000000; width:400px;'>";
        print "<legend class='ui-widget-header ui-state-default'>L&eacute;gende</legend>";
        print "<table cellpadding=5 width=400>";
        print "<tr><td><div class='color' style='background-color: ".$arrColor[$affaire]."'></div><td>Affaire";
        print "    <td><div class='color' style='background-color: ".$arrColor[$contrat]."'></div><td>Contrat";
        print "<tr><td><div class='color' style='background-color: ".$arrColor[$commande]."'></div><td>Commande";
        print "    <td><div class='color' style='background-color: ".$arrColor[$commandefourn]."'></div><td>Commande Fournisseur";
        print "<tr><td><div class='color' style='background-color: ".$arrColor[$exped]."'></div><td>Exp&eacute;dition";
        print "    <td><div class='color' style='background-color: ".$arrColor[$facture]."'></div><td>Facture";
        print "<tr><td><div class='color' style='background-color: ".$arrColor[$factfourn]."'></div><td>Facture fournisseur";
        print "    <td><div class='color' style='background-color: ".$arrColor[$livraison]."'></div><td>Livraison";
        print "<tr><td><div class='color' style='background-color: ".$arrColor[$projet]."'></div><td>Projet";
        print "    <td><div class='color' style='background-color: ".$arrColor[$propal]."'></div><td>Propale";
        print "</table>";
        print "</div>";
    }
    llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');
?>
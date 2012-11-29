<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 24 aout 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : configJour_html-response.php
  * GLE-1.1
  */
require_once('../../main.inc.php');
  ///configure le nombre d'heure par jour
  ///configure le nombre de jour par semaine

//dolibarr_set_const($db, "FAC_FORCE_DATE_VALIDATION",$_POST["forcedate"])


$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '<link rel="stylesheet" type="text/css" href="'.$csspath.'ui.all.css" />'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery-1.3.2.js"></script>'."\n";
$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";
$header .= '<link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';

$header .= '<script language="javascript" src="'.$jqueryuipath.'jquery-ui.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.dialog.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.tabs.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'/jquery.validate.js"></script>'."\n";
print "<html><head></head><body></body>";
print $header;

  print '<form name="configProj" id="configProj" method="POST"  action="config.php?&selectedTabs=2">';
  print '<table width=700px style="border-collapse:collapse;" cellpadding=15>';
  print '<tr>';
  print '<th class="ui-widget-header ui-state-default">Nombre de jour de travail par semaine';
  print '</th>';
  print '<td><input name="DayPerWeek" id="DayPerWeek"  value="'.$conf->global->PROJECT_DAY_PER_WEEK.'" ></input>';
  print '</td>';
  print '</tr>';

  print '<tr>';
  print '<th class="ui-widget-header ui-state-default">Dur&eacute;e minimum pour une demi-journ&eacute;e  de r&eacute;servation';
  print '</th>';
  print '<td><input name="halfDay" id="halfDay"  value="'.$conf->global->HALFDAY.'" ></input>';
  print '</td>';
  print '</tr>';

  print '<tr>';
  print '<th class="ui-widget-header ui-state-default">Dur&eacute;e minimum pour une journ&eacute;e de r&eacute;servation';
  print '</th>';
  print '<td><input name="allDay" id="allDay"  value="'.$conf->global->ALLDAY.'" ></input>';
  print '</td>';
  print '</tr>';

  print '<tr>';
  print '<th class="ui-widget-header ui-state-default" colspan = 2>Journ&eacute;e type';
  print '</th></tr>';
  print '<tr><td>D&eacute;but de journ&eacute;e</td><td><input name="DayStart" id="DayStart"  value="'.$conf->global->PROJECT_DAY_START.'"></td>';
  print '<tr><td>Fin de la journ&eacute;e</td><td><input name="DayEnd" id="DayEnd"  value="'.$conf->global->PROJECT_DAY_END.'"></td>';
  print '<tr><td>Nombre d\'heures de travail par jour</td><td><input name="hourPerDay" id="hourPerDay"  value="'.$conf->global->PROJECT_HOUR_PER_DAY.'">';
  print '</td>';
  print '</tr>';



  print '<tr>';
  print '<td colspan=1 align=left><br/><input class="butAction"  type="submit" value="Valider" ></input>';
  print '</td>';
  print '</tr>';
  print '</table>';
  print "</form>";
  print "<br/><br/><br/>";
//horaire
  print <<<EOF
   <div id ='tabs1' style='width: 700px;'>
    <ul>
     <li><a href='#fragment-1'><span style='font-size: 12px; font-weight: 400;'>Semaine</span></a></li>
     <li><a href='#fragment-2'><span style='font-size: 12px; font-weight: 400;'>Samedi</span></a></li>
     <li><a href='#fragment-3'><span style='font-size: 12px; font-weight: 400;'>Dimanche</span></a></li>
     <li><a href='#fragment-4'><span style='font-size: 12px; font-weight: 400;'>F&eacute;ri&eacute;</span></a></li>
    </ul>
EOF;
  print "<div id='fragment-1'>";
  print '<table width=600px style="border-collapse:collapse;">';
  print '<tr>';
  print '<th colspan=4 class="ui-widget-header ui-state-default">';
  print 'Horaire Exceptionnel';
  print '</th>';
  print '</tr>';
  $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire WHERE day is null";
  $sql = $db->query($requete);
  $result=array();
  $firstTS=24*3600;
  $lastTS=0;
  while($res = $db->fetch_object($sql))
  {
    $debut = $res->debut;
     $fin = $res->fin;
     $facteur = $res->facteur;
     $debutTS;
     $finTS;
     if(preg_match("/([0-9]{2}):([0-9]{2})/",$debut,$arr))
     {
        $debutTS = $arr[1] * 3600 + $arr[2] * 60;
     }
     if(preg_match("/([0-9]{2}):([0-9]{2})/",$fin,$arr))
     {
        $finTS = $arr[1] * 3600 + $arr[2] * 60;
     }
     $result[$debutTS]['facteur']=$res->facteur;
     $result[$debutTS]['duree']=$finTS - $debutTS;
     $result[$debutTS]['fin']=$finTS;
     $result[$debutTS]['rowid']=$res->id;
     if($firstTS>$debutTS)
     {
        $firstTS=$debutTS;
     }
     if($lastTS<$finTS)
     {
        $lastTS=$finTS;
     }
  }
  print "<tr><td>00:00</td><td>".secToHeure($firstTS)."</td><td>100%</td></tr>";

  for($i=$firstTS;$i<$lastTS;$i+=60)
  {
      if ($result[$i]['facteur']."x"!="x")
      {
          print '<tr>';
          print '<td>'.secToHeure($i).'</td>';
          print '<td>'.secToHeure($i + $result[$i]['duree']).'</td>';
          print '<td>'.$result[$i]['facteur'].'%</td>';
          print '<td><span onClick="delete_tranche('.$result[$i]['rowid'].')">'.img_picto('Supprimer','delete').'</span></td>';
          print '</tr>';
      }

  }
  print "<tr><td>".secToHeure($lastTS)."</td><td>24:00</td><td>100%</td></tr>";

  print '</table>';

  print "<br/><span class='fg-button ui-state-default ui-corner-all' style='padding: 5pt;' onclick='jQuery(\"#dialog_horaire\").dialog(\"open\");'>Ajouter une tranche horaire</span>";

  print "</div><div id='fragment-2'>";
  print '<table width=600px style="border-collapse:collapse;">';
  print '<tr>';
  print '<th colspan=4 class="ui-widget-header ui-state-default">';
  print 'Horaire Exceptionnel Samedi';
  print '</th>';
  print '</tr>';
  $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire WHERE day = 6"; // samedi
  $sql = $db->query($requete);
  $result=array();
  $firstTS=24*3600;
  $lastTS=0;
  while($res = $db->fetch_object($sql))
  {
    $debut = $res->debut;
     $fin = $res->fin;
     $facteur = $res->facteur;
     $debutTS;
     $finTS;
     if(preg_match("/([0-9]{2}):([0-9]{2})/",$debut,$arr))
     {
        $debutTS = $arr[1] * 3600 + $arr[2] * 60;
     }
     if(preg_match("/([0-9]{2}):([0-9]{2})/",$fin,$arr))
     {
        $finTS = $arr[1] * 3600 + $arr[2] * 60;
     }
     $result[$debutTS]['facteur']=$res->facteur;
     $result[$debutTS]['duree']=$finTS - $debutTS;
     $result[$debutTS]['fin']=$finTS;
     $result[$debutTS]['rowid']=$res->id;

     if($firstTS>$debutTS)
     {
        $firstTS=$debutTS;
     }
     if($lastTS<$finTS)
     {
        $lastTS=$finTS;
     }
  }
  print "<tr><td>00:00</td><td>".secToHeure($firstTS)."</td><td>100%</td></tr>";

  for($i=$firstTS;$i<$lastTS;$i+=60)
  {
      if ($result[$i]['facteur']."x"!="x")
      {
          print '<tr>';
          print '<td>'.secToHeure($i).'</td>';
          print '<td>'.secToHeure($i + $result[$i]['duree']).'</td>';
          print '<td>'.$result[$i]['facteur'].'%</td>';
          print '<td><span onClick="delete_tranche('.$result[$i]['rowid'].')">'.img_picto('Supprimer','delete').'</span></td>';
          print '</tr>';
      }

  }
  print "<tr><td>".secToHeure($lastTS)."</td><td>24:00</td><td>100%</td></tr>";

  print '</table>';

  print "<br/><span  class='fg-button ui-state-default ui-corner-all'  style='padding: 5pt;' onclick='jQuery(\"#dialog_horaireSamedi\").dialog(\"open\");'>Ajouter une tranche horaire</span>";

  print "</div><div id='fragment-3'>";

  print '<table width=600px style="border-collapse:collapse;">';
  print '<tr>';
  print '<th colspan=4 class="ui-widget-header ui-state-default">';
  print 'Horaire Exceptionnel Dimanche';
  print '</th>';
  print '</tr>';
  $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire WHERE day = 7"; // dimanche
  $sql = $db->query($requete);
  $result=array();
  $firstTS=24*3600;
  $lastTS=0;
  while($res = $db->fetch_object($sql))
  {
    $debut = $res->debut;
     $fin = $res->fin;
     $facteur = $res->facteur;
     $debutTS;
     $finTS;
     if(preg_match("/([0-9]{2}):([0-9]{2})/",$debut,$arr))
     {
        $debutTS = $arr[1] * 3600 + $arr[2] * 60;
     }
     if(preg_match("/([0-9]{2}):([0-9]{2})/",$fin,$arr))
     {
        $finTS = $arr[1] * 3600 + $arr[2] * 60;
     }
     $result[$debutTS]['facteur']=$res->facteur;
     $result[$debutTS]['duree']=$finTS - $debutTS;
     $result[$debutTS]['fin']=$finTS;
     $result[$debutTS]['rowid']=$res->id;
     if($firstTS>$debutTS)
     {
        $firstTS=$debutTS;
     }
     if($lastTS<$finTS)
     {
        $lastTS=$finTS;
     }
  }
  print "<tr><td>00:00</td><td>".secToHeure($firstTS)."</td><td>100%</td></tr>";

  for($i=$firstTS;$i<$lastTS;$i+=60)
  {
      if ($result[$i]['facteur']."x"!="x")
      {
          print '<tr>';
          print '<td>'.secToHeure($i).'</td>';
          print '<td>'.secToHeure($i + $result[$i]['duree']).'</td>';
          print '<td>'.$result[$i]['facteur'].'%</td>';
          print '<td><span onClick="delete_tranche('.$result[$i]['rowid'].')">'.img_picto('Supprimer','delete').'</span></td>';
          print '</tr>';
      }

  }
  print "<tr><td>".secToHeure($lastTS)."</td><td>24:00</td><td>100%</td></tr>";

  print '</table>';

  print "<br/><span class='fg-button ui-state-default ui-corner-all'  style='padding: 5pt;' onclick='jQuery(\"#dialog_horaireDimanche\").dialog(\"open\");'>Ajouter une tranche horaire</span>";
  print "</div>";


  print "</div><div id='fragment-4'>";

  print '<table width=600px style="border-collapse:collapse;">';
  print '<tr>';
  print '<th colspan=4 class="ui-widget-header ui-state-default">';
  print 'Horaire Exceptionnel jours f&eacute;ri&eacute;s';
  print '</th>';
  print '</tr>';
  $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire WHERE day = 8"; // ferie
  $sql = $db->query($requete);
  $result=array();
  $firstTS=24*3600;
  $lastTS=0;
  while($res = $db->fetch_object($sql))
  {
    $debut = $res->debut;
     $fin = $res->fin;
     $facteur = $res->facteur;
     $debutTS;
     $finTS;
     if(preg_match("/([0-9]{2}):([0-9]{2})/",$debut,$arr))
     {
        $debutTS = $arr[1] * 3600 + $arr[2] * 60;
     }
     if(preg_match("/([0-9]{2}):([0-9]{2})/",$fin,$arr))
     {
        $finTS = $arr[1] * 3600 + $arr[2] * 60;
     }
     $result[$debutTS]['facteur']=$res->facteur;
     $result[$debutTS]['duree']=$finTS - $debutTS;
     $result[$debutTS]['fin']=$finTS;
     $result[$debutTS]['rowid']=$res->id;
     if($firstTS>$debutTS)
     {
        $firstTS=$debutTS;
     }
     if($lastTS<$finTS)
     {
        $lastTS=$finTS;
     }
  }
  print "<tr><td>00:00</td><td>".secToHeure($firstTS)."</td><td>100%</td></tr>";

  for($i=$firstTS;$i<$lastTS;$i+=60)
  {
      if ($result[$i]['facteur']."x"!="x")
      {
          print '<tr>';
          print '<td>'.secToHeure($i).'</td>';
          print '<td>'.secToHeure($i + $result[$i]['duree']).'</td>';
          print '<td>'.$result[$i]['facteur'].'%</td>';
          print '<td><span onClick="delete_tranche('.$result[$i]['rowid'].')">'.img_picto('Supprimer','delete').'</span></td>';
          print '</tr>';
      }

  }
  print "<tr><td>".secToHeure($lastTS)."</td><td>24:00</td><td>100%</td></tr>";

  print '</table>';

  print "<br/><span class='fg-button ui-state-default ui-corner-all'  style='padding: 5pt;' onclick='jQuery(\"#dialog_horaireFerie\").dialog(\"open\");'>Ajouter une tranche horaire</span>";
  print "</div>";

  print "</div>"; // fin de tabs

  print '</form>';

//modal
print "<div id='dialog_horaire' style='display: none;'>";
print "<form id='formSemaine'>";
print "<table>";
print "<tr><td>D&eacute;but (08:00)</td><td><input name='debSem' id='debSem' ></input></td></tr>";
print "<tr><td>Fin (18:00)</td><td><input name='finSem' id='finSem' ></input></td></tr>";
print "<tr><td>Facteur (125%)</td><td><input name='factSem' id='factSem' ></input></td></tr>";
print "</table>";
print "</form>";
print "</div>";


//ajouter les 2 jour du week end:
print "<div id='dialog_horaireSamedi' style='display: none;'>";
print "<form id='formSamedi'>";
print "<table>";
print "<tr><td>D&eacute;but (08:00)</td><td><input name='debSam' id='debSam' ></input></td></tr>";
print "<tr><td>Fin (18:00)</td><td><input name='finSam' id='finSam' ></input></td></tr>";
print "<tr><td>Facteur (125%)</td><td><input name='factSam' id='factSam' ></input></td></tr>";
print "</table>";
print "</form>";
print "</div>";

print "<div id='dialog_horaireDimanche' style='display: none;'>";
print "<form id='formDimanche'>";
print "<table>";
print "<tr><td>D&eacute;but (08:00)</td><td><input name='debDim' id='debDim' ></input></td></tr>";
print "<tr><td>Fin (18:00)</td><td><input name='finDim' id='finDim' ></input></td></tr>";
print "<tr><td>Facteur (125%)</td><td><input name='factDim' id='factDim' ></input></td></tr>";
print "</table>";
print "</form>";
print "</div>";

//ferie
print "<div id='dialog_horaireFerie' style='display: none;'>";
print "<form id='formFerie'>";
print "<table>";
print "<tr><td>D&eacute;but (08:00)</td><td><input name='debFer' id='debFer' ></input></td></tr>";
print "<tr><td>Fin (18:00)</td><td><input name='finFer' id='finFer' ></input></td></tr>";
print "<tr><td>Facteur (125%)</td><td><input name='factFer' id='factFer' ></input></td></tr>";
print "</table>";
print "</form>";
print "</div>";





print <<<EOF
<script type='text/javascript'>

jQuery.validator.addMethod(
                "FRDate",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W\d\d\:\d\d$/);
                },
                "La date doit &ecirc;tre au format dd/mm/yyyy hh:mm"
            );
jQuery.validator.addMethod(
                "HourFormat",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d\:\d\d$/);
                },
                "L'heure doit &ecirc;tre au format hh:mm"
            );

jQuery('#configProj').validate({
    rules: { DayStart: {
                HourFormat: true
            },
            DayEnd : {
                HourFormat: true
            },
          },
});

jQuery('#dialog_horaire').dialog({ hide: 'explode', modal: true, 'show': 'slide',title: "Tranche horaire de la semaine",autoOpen: false, buttons: {
    "Ok": function(){
        jQuery.validator.addMethod(
                "HourFormat",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d\:\d\d$/);
                },
                "L'heure doit &ecirc;tre au format hh:mm"
            );        //validate
           var post="type=Semaine";
           var debut = jQuery("#debSem").val();
           var fin = jQuery("#finSem").val();
           var facteur = jQuery("#factSem").val();
               post += "&debut="+debut;
               post += "&fin="+fin;
               post += "&facteur="+facteur;
               post += "&action=add";

            if (jQuery("#formSemaine").validate({
                                rules: { debSem: {
                                            required: true,
                                            minlength: 5,
                                            HourFormat: true
                                        },
                                        finSem : {
                                            required: true,
                                            minlength: 5,
                                            HourFormat: true
                                        },
                                        factSem : {
                                            required: true,
                                        },
                                      },
                            messages: {
                                        debSem: {
                                            required : "<br>Champs requis",
                                            minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                        },
                                        finSem: {
                                            required : "<br>Champs requis",
                                            minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                        },
                                        factSem: {
                                            required : "<br>Champs requis",
                                        },
                                    }
                            }).form())
                            {
                                jQuery.ajax({
                                        async: false,
                                        url: "ajax/configJour_xmlresponse.php",
                                        type: "POST",
                                        data: post,
                                        success: function(msg){
                                            if (jQuery(msg).find('ok').text()+"x" != "x")
                                            {
                                                location.href="config.php?selectedTabs=2";
                                            } else {
                                                alert (jQuery(msg).find('ko').text());
                                            }
                                        }
                                    });
                            }
                    }
            }
        }
);
jQuery('#dialog_horaireSamedi').dialog({ hide: 'explode', modal: true, 'show': 'slide',title: "Tranche horaire du Samedi",autoOpen: false, buttons: {
    "Ok": function(){
        jQuery.validator.addMethod(
                "HourFormat",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d\:\d\d$/);
                },
                "L'heure doit &ecirc;tre au format hh:mm"
            );
           var post="type=Samedi";
           var debut = jQuery("#debSam").val();
           var fin = jQuery("#finSam").val();
           var facteur = jQuery("#factSam").val();
               post += "&debut="+debut;
               post += "&fin="+fin;
               post += "&facteur="+facteur;
               post += "&action=add";
                if (jQuery("#formSamedi").validate({
                                    rules: { debSam: {
                                                required: true,
                                                minlength: 5,
                                                HourFormat: true
                                            },
                                            finSam : {
                                                required: true,
                                                minlength: 5,
                                                HourFormat: true
                                            },
                                            factSam : {
                                                required: true,
                                            },
                                          },
                                messages: {
                                            debSam: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                            finSam: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                            factSam: {
                                                required : "<br>Champs requis",
                                            },
                                        }
                                }).form())
                                {
                                    jQuery.ajax({
                                            async: false,
                                            url: "ajax/configJour_xmlresponse.php",
                                            type: "POST",
                                            data: post,
                                            success: function(msg){
                                                if (jQuery(msg).find('ok').text()+"x" != "x")
                                                {
                                                    location.href="config.php?selectedTabs=2";
                                                } else {
                                                    alert (jQuery(msg).find('ko').text());
                                                }
                                            }
                                        });
                                }
                        }
            }
        }
);
jQuery('#dialog_horaireDimanche').dialog({ hide: 'explode', modal: true, 'show': 'slide',title: "Tranche horaire du Dimanche",autoOpen: false, buttons: {
    "Ok": function(){
        jQuery.validator.addMethod(
                "HourFormat",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d\:\d\d$/);
                },
                "L'heure doit &ecirc;tre au format hh:mm"
            );        //validate
           var post="type=Dimanche";
           var debut = jQuery("#debDim").val();
           var fin = jQuery("#finDim").val();
           var facteur = jQuery("#factDim").val();
               post += "&debut="+debut;
               post += "&fin="+fin;
               post += "&facteur="+facteur;
               post += "&action=add";

                if (jQuery("#formDimanche").validate({
                                    rules: { debDim: {
                                                required: true,
                                                minlength: 5,
                                                HourFormat: true
                                            },
                                            finDim : {
                                                required: true,
                                                minlength: 5,
                                                HourFormat: true
                                            },
                                            factDim : {
                                                required: true,
                                            },
                                          },
                                messages: {
                                            debDim: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                            finDim: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                            factDim: {
                                                required : "<br>Champs requis",
                                            },
                                        }
                                }).form())
                                {
                                    jQuery.ajax({
                                            async: false,
                                            url: "ajax/configJour_xmlresponse.php",
                                            type: "POST",
                                            data: post,
                                            success: function(msg){
                                                if (jQuery(msg).find('ok').text()+"x" != "x")
                                                {
                                                    location.href="config.php?selectedTabs=2";
                                                } else {
                                                    alert (jQuery(msg).find('ko').text());
                                                }
                                            }
                                        });
                                }
                        }
            }
        }
);

jQuery('#dialog_horaireFerie').dialog({ hide: 'explode', modal: true, 'show': 'slide',title: "Tranche horaire des jours f&eacute;ri&eacute;s",autoOpen: false, buttons: {
    "Ok": function(){
        jQuery.validator.addMethod(
                "HourFormat",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d\:\d\d$/);
                },
                "L'heure doit &ecirc;tre au format hh:mm"
            );        //validate
           var post="type=Ferie";
           var debut = jQuery("#debFer").val();
           var fin = jQuery("#finFer").val();
           var facteur = jQuery("#factFer").val();
               post += "&debut="+debut;
               post += "&fin="+fin;
               post += "&facteur="+facteur;
               post += "&action=add";

                if (jQuery("#formFerie").validate({
                                    rules: { debDim: {
                                                required: true,
                                                minlength: 5,
                                                HourFormat: true
                                            },
                                            finDim : {
                                                required: true,
                                                minlength: 5,
                                                HourFormat: true
                                            },
                                            factDim : {
                                                required: true,
                                            },
                                          },
                                messages: {
                                            debDim: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                            finDim: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                            factDim: {
                                                required : "<br>Champs requis",
                                            },
                                        }
                                }).form())
                                {
                                    jQuery.ajax({
                                            async: false,
                                            url: "ajax/configJour_xmlresponse.php",
                                            type: "POST",
                                            data: post,
                                            success: function(msg){
                                                if (jQuery(msg).find('ok').text()+"x" != "x")
                                                {
                                                    location.href="config.php?selectedTabs=2";
                                                } else {
                                                    alert (jQuery(msg).find('ko').text());
                                                }
                                            }
                                        });
                                }
                        }
            }
        }
);

jQuery('#tabs1').tabs({
    spinner: "Chargement ...",
    cache: true,fx: { opacity: 'toggle' }
});
function delete_tranche(pId)
{
     var post="action=del&id="+pId;
     jQuery.ajax({
        async: false,
        url: "ajax/configJour_xmlresponse.php",
        type: "POST",
        data: post,
        success: function(msg){
            if (jQuery(msg).find('ok').text()+"x" != "x")
            {
                location.href="config.php?selectedTabs=2";
            } else {
                alert (jQuery(msg).find('ko').text());
            }
        }
    });

}
</script>
EOF;

function secToHeure($ts)
{
    $hour = floor(intval($ts)/3600);
    $min = round(intval($ts) - $hour * 3600)/60;
    if ($hour<10)$hour = "0".$hour;
    if ($min<10)$min = "0".$min;
    $ret = $hour.":".$min;
    return($ret);
}
?>

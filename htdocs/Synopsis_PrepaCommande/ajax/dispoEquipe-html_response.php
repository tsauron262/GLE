<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : dispoEquipe-html_response.php
  * GLE-1.2
  */

  //Regarde tt les di prises en charges


require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

print '<link rel="stylesheet" type="text/css" media="screen" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.contextMenu.css" />';
print "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery.contextMenu2.js'></script>";
print "<style>.context-menu-shadow { display: none !important; } .contextMenu { max-width: 100px !important; }</style>";

$html = new Form($db);
$period=10;
print "<table width=800 cellpadding=15 style='font-size: 125%;'><tr><td class='ui-widget-header ui-state-default'>";
$html->select_users(0,'userid',1);
$curdate=time();
while(date('N',$curdate) != 1){
    $curdate -= 24*3600;
}
print "<td align=center class='ui-widget-header ui-state-default' style='border-right: none;'><div class='ui-widget-header ui-state-default selectable' id='prevPeriod' style='border: none;'><span class='ui-icon ui-icon-circle-triangle-w'></span></div>";
print "<td align=center class='ui-widget-header ui-state-default' style='border-left: none; border-right: none;' ><div id='date'>du ".date('d/m/Y',$curdate)." au ". date('d/m/Y',$curdate+3600*$period*24)."</div>";
print "<td align=center class='ui-widget-header ui-state-default' style='border-left: none;'><div class='ui-widget-header ui-state-default selectable'  id='nxtPeriod' style='border: none;'><span class='ui-icon ui-icon-circle-triangle-e'></span></div>";
print "<td align=center width=15 class='ui-widget-header ui-state-default' style='border-right: none;'><div class='ui-widget-header ui-state-default selectable' id='moinsPeriod' style='border: none;'><span class='ui-icon ui-icon-circle-triangle-w'></span></div>";
print "<td align=center width=25 class='ui-widget-header ui-state-default' style='border-left: none; border-right: none;'  ><span id='period'>".$period."</span>j";
print "<td align=center width=15 class='ui-widget-header ui-state-default' style='border-left: none;'><div class='ui-widget-header ui-state-default selectable'  id='plusPeriod' style='border: none;'><span class='ui-icon ui-icon-circle-triangle-e'></span></div>";
print "</table>";
print ' <div class="contextMenu" style="display:none" id="MenuDispo">';
print '        <ul style="max-width: 100px; padding-left: 5px; ">';
print '            <li id="reserver" class="menuContent">';
print '                <img height=16 width=16 src="'.DOL_URL_ROOT.'/theme/auguria/img/object_calendar.png" />';
print '                Demand Inter.</li>';
print '            <li id="editer" class="menuContent">';
print '                <img height=16 width=16 src="'.DOL_URL_ROOT.'/theme/auguria/img/button_edit.png" />';
print '                Fiche Inter.</li>';
print '        </ul>';
print '    </div>';

print "<br/>";
print "<div id='dispo'>";
print "</div>";
print "<script>var dateDeb='".$curdate."';";



print "var period='".$period."';";
print $jqgridJs;
print "</script>";
print "<style>.selectable{ cursor: pointer; } .menuContent:hover { text-decoration: underline; cursor: pointer; }</style>";

print <<<EOF
<script>
var eventsMenu = {
    bindings: {
        'editer': function(t) {
            location.href=DOL_URL_ROOT+'/fichinter/fiche.php?action=create&socid='+socId;
        },
        'reserver': function(t) {
            location.href=DOL_URL_ROOT+'/Synopsis_DemandeInterv/fiche.php?action=create&socid='+socId;
        }
    }
};

var ArrId = new Array();
jQuery(document).ready(function(){
    jQuery('#userid').change(function(){
        //Ajoute à la liste
        var userId = jQuery(this).find(':selected').val();
        ArrId[userId]=userId;
        var idStr = "";
        for(var i in ArrId)
        {
            idStr+= ArrId[i]+"-";
        }
        idStr = idStr.replace(/-$/,'');
        jQuery.ajax({
            url: 'ajax/html/dispoUser-html_response.php',
            data: 'user='+idStr+"&dateDeb="+dateDeb+"&period="+period,
            datatype: 'xml',
            type: 'POST',
            success: function(msg){
                jQuery('#dispo').replaceWith('<div id="dispo">'+jQuery(msg).html()+'</div>');
                jQuery(msg).find('td').each(function(){
                    if ("x"+jQuery(this).attr('id') !="x")
                    {
                        jQuery("#"+jQuery(this).attr('id')).contextMenu("MenuDispo", eventsMenu);
                    }
                })

            }
        });
    });
    jQuery('#nxtPeriod').click(function(){
        var idStr = "";
        for(var i in ArrId)
        {
            idStr+= ArrId[i]+"-";
        }
        idStr = idStr.replace(/-$/,'');
        var newDate = parseInt(dateDeb) + parseInt(3600 * 24 * period);
        jQuery.ajax({
            url: 'ajax/html/dispoUser-html_response.php',
            data: 'user='+idStr+"&dateDeb="+newDate+"&period="+period,
            datatype: 'xml',
            type: 'POST',
            success: function(msg){
                jQuery('#dispo').replaceWith('<div id="dispo">'+jQuery(msg).html()+'</div>');
                jQuery(msg).find('td').each(function(){
                    if ("x"+jQuery(this).attr('id') !="x")
                    {
                        jQuery("#"+jQuery(this).attr('id')).contextMenu("MenuDispo", eventsMenu);
                    }
                });
                var newDateStr = new Date();
                    newDateStr.setTime(newDate * 1000);
                var monthTmp = newDateStr.getMonth() + 1 ;
                var Str1 = newDateStr.getDate()+"/" + monthTmp +"/"+newDateStr.getFullYear();
                newDateStr.setTime((parseInt(newDate) + parseInt(3600 * 24 * period)) * 1000);
                monthTmp = newDateStr.getMonth()+1;
                var Str2 = newDateStr.getDate()+"/" + monthTmp +"/"+newDateStr.getFullYear();
                jQuery('#date').replaceWith('<div id="date">du '+Str1+' au '+Str2+' </div>');
                dateDeb=newDate;
            }
        });
    });

    jQuery('#plusPeriod').click(function()
    {
        var idStr = "";
        for(var i in ArrId)
        {
            idStr+= ArrId[i]+"-";
        }
        idStr = idStr.replace(/-$/,'');
        period++;
        var newDate = parseInt(dateDeb);
        jQuery.ajax({
            url: 'ajax/html/dispoUser-html_response.php',
            data: 'user='+idStr+"&dateDeb="+newDate+"&period="+period,
            datatype: 'xml',
            type: 'POST',
            success: function(msg){
                jQuery('#dispo').replaceWith('<div id="dispo">'+jQuery(msg).html()+'</div>');
                jQuery(msg).find('td').each(function(){
                    if ("x"+jQuery(this).attr('id') !="x")
                    {
                        jQuery("#"+jQuery(this).attr('id')).contextMenu("MenuDispo", eventsMenu);
                    }
                });
                var newDateStr = new Date();
                    newDateStr.setTime(newDate * 1000);
                var monthTmp = newDateStr.getMonth() + 1 ;
                var Str1 = newDateStr.getDate()+"/" + monthTmp +"/"+newDateStr.getFullYear();
                newDateStr.setTime((parseInt(newDate) + parseInt(3600 * 24 * period)) * 1000);
                monthTmp = newDateStr.getMonth()+1;
                var Str2 = newDateStr.getDate()+"/" + monthTmp +"/"+newDateStr.getFullYear();
                jQuery('#date').replaceWith('<div id="date">du '+Str1+' au '+Str2+' </div>');
                dateDeb=newDate;
                jQuery('#period').replaceWith('<span id="period">'+period+'</span>');
            }
        });
    });
    jQuery('#moinsPeriod').click(function()
    {
        var idStr = "";
        for(var i in ArrId)
        {
            idStr+= ArrId[i]+"-";
        }
        idStr = idStr.replace(/-$/,'');
        period--;
        var newDate = parseInt(dateDeb);
        jQuery.ajax({
            url: 'ajax/html/dispoUser-html_response.php',
            data: 'user='+idStr+"&dateDeb="+newDate+"&period="+period,
            datatype: 'xml',
            type: 'POST',
            success: function(msg){
                jQuery('#dispo').replaceWith('<div id="dispo">'+jQuery(msg).html()+'</div>');
                jQuery(msg).find('td').each(function(){
                    if ("x"+jQuery(this).attr('id') !="x")
                    {
                        jQuery("#"+jQuery(this).attr('id')).contextMenu("MenuDispo", eventsMenu);
                    }
                });
                var newDateStr = new Date();
                    newDateStr.setTime(newDate * 1000);
                var monthTmp = newDateStr.getMonth() + 1 ;
                var Str1 = newDateStr.getDate()+"/" + monthTmp +"/"+newDateStr.getFullYear();
                newDateStr.setTime((parseInt(newDate) + parseInt(3600 * 24 * period)) * 1000);
                monthTmp = newDateStr.getMonth()+1;
                var Str2 = newDateStr.getDate()+"/" + monthTmp +"/"+newDateStr.getFullYear();
                jQuery('#date').replaceWith('<div id="date">du '+Str1+' au '+Str2+' </div>');
                dateDeb=newDate;
                jQuery('#period').replaceWith('<span id="period">'+period+'</span>');
            }
        });
    });

    jQuery('#prevPeriod').click(function(){
        var idStr = "";
        for(var i in ArrId)
        {
            idStr+= ArrId[i]+"-";
        }
        idStr = idStr.replace(/-$/,'');
        var newDate = parseInt(dateDeb) - parseInt(3600 * 24 * period);
        jQuery.ajax({
            url: 'ajax/html/dispoUser-html_response.php',
            data: 'user='+idStr+"&dateDeb="+newDate+"&period="+period,
            datatype: 'xml',
            type: 'POST',
            success: function(msg){
                jQuery('#dispo').replaceWith('<div id="dispo">'+jQuery(msg).html()+'</div>');
                jQuery(msg).find('td').each(function(){
                    if ("x"+jQuery(this).attr('id') !="x")
                    {
                        jQuery("#"+jQuery(this).attr('id')).contextMenu("MenuDispo", eventsMenu);
                    }
                });
                var newDateStr = new Date();
                    newDateStr.setTime(newDate * 1000);
                var monthTmp = newDateStr.getMonth() + 1 ;
                var Str1 = newDateStr.getDate()+"/" + monthTmp +"/"+newDateStr.getFullYear();
                newDateStr.setTime((parseInt(newDate) + parseInt(3600 * 24 * period)) * 1000);
                monthTmp = newDateStr.getMonth()+1;
                var Str2 = newDateStr.getDate()+"/" + monthTmp +"/"+newDateStr.getFullYear();
                jQuery('#date').replaceWith('<div id="date">du '+Str1+' au '+Str2+' </div>');
                dateDeb=newDate;
            }
        });
    });
});
</script>


EOF;
?>

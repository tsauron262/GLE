<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 10 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : configPrix.php
  * GLE-1.2
  */
    require_once("pre.inc.php");
    $js = "";
    $js.=<<<EOF
    <style>
    .treeview, .treeview ul {
        list-style:none outside none;
        margin:0;
        padding:0;
    }
    .treeview ul {
        background-color:white;
        margin-top:4px;
    }
    .treeview li {
        margin:0;
        padding:3px 0 3px 16px;
    }
    .treeview a.selected {
        background-color:#EEEEEE;
    }
    </style>
    <script>
    jQuery(document).ready(function(){
                jQuery.validator.addMethod(
                        "floatSup0",
                        function(value, element) {
                            // put your own logic here, this is just a (crappy) example
                            value=value.replace(/,/,'.')
                            return value>=0;
                        },
                        "  Ce champ est requis ou invalide"
                    );
        jQuery('#userForm').validate();
    });
    function showUserPrice(iD){

        jQuery.ajax({
            url:'ajax/getUserPrice-xml_response.php',
            data:"userId="+iD,
            datatype:"xml",
            type:"POST",
            success: function(msg){
                var longHtml = "<form id='userForm'><input name='userId' value='"+iD+"' type='hidden'>";
                    longHtml += "<table width='832' cellpadding=15><tr><th align=left class='ui-widget-header ui-state-hover' colspan=2>De "+jQuery(msg).find('userDesc').text()+"</table>";
                    longHtml += "<div id='dialTabs'>";
                    longHtml += "<ul><li><a href='#fragment-1'>Intervention</a></li><li><a href='#fragment-2'>Forfait d&eacute;placement</a></li></ul>";
                    longHtml += "<div id='fragment-1'>";
                    longHtml += "<table width='832' cellpadding=15>";
                jQuery(msg).find('interv').each(function(){
                    var idTypeInterv = jQuery(this).attr('id');
                    var prixIntervUser = jQuery(this).find('prix').text();
                    var label = jQuery(this).find('label').text();
                    longHtml += "<tr>";
                    longHtml +=     "<th class='ui-widget-header ui-state-default' >"+label
                    longHtml +=     "<td class='ui-widget-content' width=60%><input type='text' class='floatSup0' name='type-"+idTypeInterv+"' value='"+prixIntervUser+"'>";
                });
                longHtml += "</table></div>";
                longHtml += "<div id='fragment-2'>";
                longHtml += "<table width='832' cellpadding=15>";
                jQuery(msg).find('deplacement').each(function()
                {
                    var product = jQuery(this).find('product').text();
                    var prixIntervUser = jQuery(this).find('prix').text();
                    var idTypeInterv = jQuery(this).attr('id');
                    longHtml += "<tr>";
                    longHtml +=     "<th align=left class='ui-widget-header ui-state-default' >   "+product
                    longHtml +=     "<td class='ui-widget-content' width=60%><input type='text' class='floatSup0' name='dep-"+idTypeInterv+"' value='"+prixIntervUser+"'>";
                });


                longHtml += "</table></div>";
                longHtml += "<table width='832' cellpadding=15>";
                longHtml += "<tr><th class='ui-state-default' colspan=2><button onClick='validateForm(); return false;' class='butAction'>Modifier</button></th></tr>";
                longHtml += "</table>";
                longHtml += "</div>";

                longHtml += "</form>";
                jQuery('#toReplace').replaceWith('<div id="toReplace">'+longHtml+'</div>')
                jQuery('#dialTabs').tabs({
                    spinner:"Chargement ...",


                })
            }
        });
    }
    function validateForm(){
        if (jQuery('#userForm').validate().form()){
            var data = jQuery('#userForm').serialize();
            jQuery.ajax({
                url:"ajax/saveUserPrice-xml_response.php",
                data:data,
                datatype:"xml",
                type:"POST",
                cache: false,
                success: function(msg){
                    if(jQuery(msg).find('OK').length>0)
                    {
                        if (jQuery('#msg').length>0) jQuery('#msg').remove();
                        jQuery('#toReplace').prepend('<div id="msg" class="ui-state-highlight">&nbsp;&nbsp;&nbsp;OK</div>')
                    } else {
                        if (jQuery('#msg').length>0) jQuery('#msg').remove();
                        jQuery('#toReplace').prepend('<div id="msg" class="ui-state-error">&nbsp;&nbsp;&nbsp;KO</div>')
                    }
                }

            })

        }
        return false;
    }

    </script>
EOF;

    if (!$user->rights->synopsisficheinter->config) { accessforbidden(); exit();}

    llxHeader($js,"Config. des prix interventions");


    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE statut = 1 AND rowid != 1";
        if ($conf->global->FIRSTNAME_BEFORE_NAME){
            $requete .= " ORDER BY firstname, name ";
        } else {
            $requete .= "  ORDER BY name, firstname ";
        }

    $sql = $db->query($requete);
    print "<table><tr><td valign='top'>";
    print "<table><tr><td valign='top'>";
    print "<div style='padding: 10px 15px;width: 200px; vertical-align:middle' class='treeheader ui-state-default ui-widget ui-corner-top ui-widget-header'>Utilisateur<div>";
    print "<tr><td>";
    print "<div style='width: 232px; margin-top: -3px;'  ><ul style='padding: 10px 15px;' class='ui-widget-content  ui-widget treeview ui-widget treeview'>";
    while ($res=$db->fetch_object($sql))
    {
        if ($conf->global->FIRSTNAME_BEFORE_NAME)
        {
           print "<li class=''><a href='#' onClick='showUserPrice(".$res->rowid.");'>".$res->firstname." ".$res->name."</a></li>";
        } else {
            print "<li class=''><a href='#' onClick='showUserPrice(".$res->rowid.");'>".$res->name." ".$res->firstname."</a></li>";
        }
    }
    print "</ul>";
    print "</div>";
    print "</table>";
    print "<td width=800 valign='top'>";
    print "<table><tr><td valign='top'>";
    print "<div style='padding: 10px 15px;width: 800px; vertical-align:middle' class='treeheader ui-state-default ui-widget ui-corner-top ui-widget-header'>Prix Jour<div>";
//    print "<tr><td>";
//    print "<div style='width: 832px; margin-top: -3px;'  ><ul style='padding: 10px 15px;' class='ui-widget-content  ui-widget treeview ui-widget treeview'>";
    print "</table>";
    print "<div id='toReplace'>";

    print "</div>";
    print "</table>";




?>

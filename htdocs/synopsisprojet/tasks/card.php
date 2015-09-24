<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
  \file       htdocs/synopsisprojet/tasks/card.php
  \ingroup    projet
  \brief      Fiche taches d'un projet
  \version    $Id: card.php,v 1.20 2008/02/25 20:03:27 eldy Exp $
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisprojet/core/lib/synopsis_project.lib.php");

if (!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';

//
/*
 * Securite acces client
 */
$projetid = '';
if ($_REQUEST["id"]) {
    $projetid = $_REQUEST["id"];
    $projet = new SynopsisProject($db);
    $projet->fetch($projetid);
}

if ($projetid == '')
    accessforbidden();

// Security check
$socid = 0;
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsisprojet', $projetid, 'Synopsis_projet_view');


$jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT . "/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT . "/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT . "/Synopsis_Common/images";

$js = '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-4.5/css/ui.jqgrid.css" />';
//$js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-4.5/css/jquery.searchFilter.css" />';
//$js .= ' <script src="' . $jspath . '/jqGrid-4.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= '<script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/ui.selectmenu.js" type="text/javascript"></script>';
$js .= '<script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/ui.datetimepicker.js" type="text/javascript"></script>';


//$js .= ' <script src="'.$jqueryuipath.'/ui.slider.js" type="text/javascript"></script>';

$js .= ' <script src="' . $jspath . '/jqGrid-4.5/plugins/jquery.contextmenu.js" type="text/javascript"></script>';




$jqgridJs = <<<EOF
<script type='text/javascript'>
EOF;
$jqgridJs .= 'var gridimgpath="' . $imgPath . '/images/";';
$jqgridJs .= 'var userId="' . $user->id . '";';
$jqgridJs .= 'var socId="' . $socid . '";';
$jqgridJs .= 'var projetId="' . $projetid . '";';

$jqgridJs .= <<<EOF
var curTaskID = -1;
jQuery(document).ready(function(){
    jQuery('#userTimeForm').find('input.resetDur').each(function(){
        jQuery(this).blur(function(){
            var userTotal = 0;
            jQuery('#userTimeForm').find('input.resetDur').each(function(){
                userTotal += parseInt(jQuery(this).val());
            });
            jQuery('#userTotal').text(userTotal+" h");
        });
    });

    jQuery('.slider').each(function(){
        var id = jQuery(this).attr('id');
            id = id + "complet";
        jQuery(this).slider({
            animate: true,
            range: 'min',
            max: 100,
            min: 0,
            step: 1,
            tooltips: function(t){ return (Math.round(t*100)/100 + " %") },
            change: function(event, ui){
                jQuery('#'+id).val(ui.value);
            },
            slide: function(event, ui)
            {
            },
            values: 0
        });
    });


    var get = "&extra=showResp";
    if (socId > 0)
    {
        get = "&socid="+socId;
    }

    var eventsMenu = {
            bindings: {
                'ajouter': function(t) {
                    jQuery("#adddialog").dialog('open');
                },
                'editer': function(t) {
                    editTask(false,jQuery(t).attr('id'),false);
                },
                'supprimer': function(t) {
                    delTask(jQuery(t).attr('id'))
                },
                'attribuer': function(t) {
                    curTaskID = jQuery(t).attr('id');
                    jQuery("#userTimeDialog").dialog('open');
                }
            }
        };


    jQuery("#gridListTask").jqGrid(
    {
            datatype: "json",
            url: "ajax/listTask_json.php?lightMode=1&projId="+projetId+get,
            colNames: ['id', 'D&eacute;signation','Date de d&eacute;but pr&eacute;vue','Avt Q.', 'Dur&eacute;e pr&eacute;vue','Temps pass&eacute;','Statut'],
            colModel: [ {name:'id',index:'rowid', width:5, hidden:true,key:true,hidedlg:true},
                        {name:'label',index:'label', width:100, align: "center"},
                        {name:'task_date',index:'task_date', width:100, datefmt: "dd/mm/yyyy",sorttype: "date", align:"center"},
                        {name:'progress',index:'progress', width:75, align:"center"},
                        {name:'task_duration',index:'task_duration', width:75, align:"center"},
                        {name:'task_duration_effective',index:'task_duration_effective', width:75, align:"center"},
                        {name:'statut',index:'statut', width:75, align:"right"},
                      ],
            rowNum:25,
            rowList:[25,50,75],
            imgpath: gridimgpath,
            pager: jQuery('#gridListTaskPager'),
            sortname: 'rowid',
            mtype: "POST",
            viewrecords: true,
            autowidth: true,
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "Projets",
            afterInsertRow: function(rowid, rowdata, rowelem) {
                    curTaskID = rowid;
                    jQuery("#" + rowid).contextMenu("MenuJqGrid", eventsMenu);
            },
            loadComplete: function(){
                    jQuery(".progressbar").each(function(){
                        var val = jQuery(this).text();
                        jQuery(this).text('');
                        jQuery(this).progressbar( {
                            value: val,
                            orientation: "horizontal",
                        });
                        jQuery(this).css('height',"10px");
                    });
            },
            subGrid: true,
            subGridUrl: 'ajax/listUserTask.php?userId='+userId+"&lightMode=1&projId="+projetId+get,
            subGridRowExpanded: function(subgrid_id, row_id)
            {
                // we pass two parameters
                // subgrid_id is a id of the div tag created within a table
                // the row_id is the id of the row
                // If we want to pass additional parameters to the url we can use
                // the method getRowData(row_id) - which returns associative array in type name-value
                // here we can easy construct the following
                var subgrid_table_id;
                subgrid_table_id = subgrid_id+"_t";
                  jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                  jQuery("#"+subgrid_table_id).jqGrid({
                      url: 'ajax/listUserTask.php?userId='+userId+"&lightMode=1&taskId="+row_id+get,
                      datatype: "json",
                      colNames: ['Id',"Nom","R&ocirc;le","D&eacute;but pr&eacute;v.","Fin pr&eacute;v.","Dur&eacute;e Pr&eacute;v.","D&eacute;but","Fin","Dur&eacute;e Eff."],
                      colModel: [
                        {name:"id",index:"id",width:55,key:true,hidden:true},
                        {name:"firstname",index:"firstname",width:290},
                        {
                            name:'role',
                            index:'role',
                            align:"center",
                            width: 150,
                        },
                        {name:'dateo',index:'dateo', width:100, datefmt: "dd/mm/yyyy",sorttype: "date", align:"center"},
                        {name:'dateFin',index:'dateFin', width:100, datefmt: "dd/mm/yyyy",sorttype: "date", align:"center"},
                        {
                            name:"duration",
                            index:"duration",
                            width:80,
                            align:"center",
                        },
                        {name:'dateoEff',index:'dateoEff', width:100, datefmt: "dd/mm/yyyy",sorttype: "date", align:"center"},
                        {name:'dateFinEff',index:'dateFinEff', width:100, datefmt: "dd/mm/yyyy",sorttype: "date", align:"center"},
                        {
                            name:"duration_effective",
                            index:"duration_effective",
                            width:80,
                            align:"center",
                        },
                      ],
                      height: "100%",
                      rowNum:20,
                      width: 855,
                      imgpath: gridimgpath,
                      sortname: 'firstname',
                      sortorder: "desc",
                      afterInsertRow: function(rowid, rowdata, rowelem) {
                            jQuery('.subgrid-data .ui-jqgrid-bdiv').each(function(){
                                jQuery(this).css("width",parseInt(jQuery(this).css("width").replace(/px$/,'')) + 1 +"px");
                            });
                      },

                  });
            },
        }).navGrid('#gridListTaskPager',
                   { add:false,
                     del:false,
                     edit:false,
                     position:"left"
                   });
    });
</script>
EOF;

$js .= $jqgridJs;

if ($projet->type_id != 1) {
    $js .= <<< EOF
<script>
jQuery(document).ready(function(){
    jQuery.validator.addMethod(
                "FRDate",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                },
                "La date doit &ecirc;tre au format dd/mm/yyyy hh:mm"
            );
    jQuery.validator.addMethod(
                "FRDateNoReq",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    if (value + "x" == "x") return true;
                    return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                },
                "La date doit &ecirc;tre au format dd/mm/yyyy hh:mm"
            );


    jQuery.validator.addMethod(
                "speselect",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    var ret = false;
                    if (value > -2 ) {ret = true;}
                    return ret;
                },
                "Merci de s&eacute;lectionner un &eacute;l&eacute;ment dans la liste"
            );

    jQuery.validator.addMethod(
                "sup0",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    var ret = false;
                    if (value > 0 ) {ret = true;}
                    return ret;
                },
                "Merci de s&eacute;lectionner un &eacute;l&eacute;ment dans la liste"
            );

EOF;
    if ($user->rights->synopsisprojet->attribution) {
        $js .= <<< EOF
    jQuery('#userTimeDialog').dialog({
        autoOpen: false,
        modal: true ,
        show: 'slide',
        label: 'Ajustement d\'une t&acirc;che',
        width: 740,
        position: "center",
        bgiframe: true,
        open: function(){
            /*jQuery('.resetRole').each(function(){
                jQuery(this).selectmenu('valueById',"acto");
            });*/
            jQuery('.resetDur').each(function(){
                jQuery(this).val(0);
            });
            jQuery('.resettaskDateDeb').each(function(){
                jQuery(this).val("");
            });

            //Call ajax => getDatas Users
            jQuery.ajax({
                async: false,
                type: "post",
                url: "projectLight-xmlresponse.php",
                data: "taskId="+curTaskID+"&action=listUser",
                success: function(msg){
                    //reload
                    jQuery(msg).find('user').each(function(){
                        var role = jQuery(this).find('role').text();
                        var dur = jQuery(this).find('dur').text();
                        var taskDateDeb = jQuery(this).find('taskDateDeb').text();
                        var userId = jQuery(this).find('id').text();
                        /*jQuery('SELECT#role'+userId).selectmenu('valueById',role);*/
                        jQuery('input#dur'+userId).val(dur);
                        jQuery('input#taskDateDeb'+userId).val(taskDateDeb);
                    });
                    var userTotal = 0;
                    jQuery('#userTimeForm').find('input.resetDur').each(function(){
                        userTotal += parseInt(jQuery(this).val());
                    });
                    jQuery('#userTotal').text(userTotal+" h");

                }
             });
        },
        buttons: {
            "Ok": function()
            {
                if (jQuery("#userTimeForm").validate({}).form()) {
                    var PostStr = jQuery('#userTimeForm').serialize();
                    jQuery.ajax({
                        async: false,
                        type: "post",
                        url: "projectLight-xmlresponse.php",
                        data: "taskId="+curTaskID+"&action=ajust&"+PostStr,
                        success: function(msg){
                            //reload
                                jQuery('#gridListTask').trigger("reloadGrid");
                                jQuery("#userTimeDialog").dialog('close');
                            }
                     });
                }
            },
            "Annuler": function(){
                jQuery("#userTimeDialog").dialog('close');
            }
        } // Close button
    });
  
EOF;
    }

    $js .= <<< EOF
    step2 = false;
    jQuery("#Assocdialog").dialog({
        autoOpen: false,
        modal: true ,
        show: 'slide',
        label: 'Importation d\'une proposition',
        width: 820,
        position: "center",
        bgiframe: true,
        open: function(){
            step2=false;
            jQuery('#step2').replaceWith('<div id="step2"></div>');
        },
        buttons: {
            "Importer": function()
            {
                if(step2)
                {
                    step2 = false;

                    //validateForm => duree sup0, date au format FR+ time et tache nom vide
                    if (jQuery("#step2form").validate({}).form()) {
                            //sendDatas to Ajax
                            var datas = jQuery('#step2form').serialize();
                            jQuery.ajax({
                                async: true,
                                type: "post",
                                url: "projectLight-xmlresponse.php",
                                data: "action=step2Import&" + datas,
                                success: function(msg){
                                    jQuery('#gridListTask').trigger("reloadGrid");
                                    jQuery("#Assocdialog").dialog('close');
                                }
                            });
                    }
                } else if(jQuery('#propalId :selected').val())
                {
                    jQuery.ajax({
                        async: false,
                        type: "post",
                        url: "projectLight-xmlresponse.php",
                        data: "projId="+projetId+"&action=import&propalId="+jQuery('#propalId :selected').val(),
                        success: function(msg)
                        {
                            step2 = true;
                            //Affiche une 2eme partie dans le dialog avec
                            //Desc + serv => tache
                            //Utilisateur => Attrib
                            //Role => Attrib
                            //temps pour la tache (rempli auto)
                            var longHtml = "<form id='step2form' onSubmit='return(false);'><table width=100% cellpadding=10>";
                                longHtml += "<tr><th class='ui-widget-header ui-state-default'>T&acirc;che";
                                longHtml += "    <th class='ui-widget-header ui-state-default'>D&eacute;but";
                                longHtml += "    <th class='ui-widget-header ui-state-default'>Utilisateur";
                                longHtml += "    <th class='ui-widget-header ui-state-default'>R&ocirc;le";
                                longHtml += "    <th class='ui-widget-header ui-state-default'>Dur&eacute;e";
                            jQuery(msg).find('task').each(function(){
                                var id = jQuery(this).find('id').text();
                                var name = jQuery(this).find('name').text();
                                var desc = jQuery(this).find('desc').text();
                                var dateo = jQuery(this).find('dateo').text();
                                var userList = jQuery(this).find('userList').text();
                                var role = jQuery(this).find('role').text();
                                var duration = jQuery(this).find('duration').text();
                                longHtml += "<tr><td class='ui-widget-content' label='"+desc+"'><input class='required' name='name"+id+"' value='"+name+"'>"
                                longHtml += "    <td class='ui-widget-content' align=center><input class='required FRDate' style='text-align:center' class='datepicker' name='dateo"+id+"' value='"+dateo+"'>";
                                longHtml += "    <td class='ui-widget-content' align=center>"+userList;
                                longHtml += "    <td class='ui-widget-content' align=center>"+role;
                                longHtml += "    <td class='ui-widget-content' align=center><input class='required sup0 durationTot' style='width:4em; text-align:center' name='dur"+id+"' value='"+duration+"' >" ;
                            });
                            longHtml += "<tr><td colspan=3>&nbsp;<th class='ui-widget-header ui-state-default' style='font-size:1.3em; font-weight:900'>Total<td style='font-size:1.3em; font-weight:900' align=center class='ui-widget-content'><span id='totImportDur'>h</span>";
                            longHtml += "</table></form>";

                            jQuery('#step2').replaceWith('<div id="step2">'+longHtml+'</div>');
                            /*jQuery('#step2').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });*/
                            jQuery('#step2').find('.datepicker').datetimepicker();
                            var tot = 0;
                            jQuery('#step2').find('.durationTot').each(function(){
                                tot += parseFloat(jQuery(this).val());
                            });
                            jQuery('#totImportDur').replaceWith("<span id='totImportDur'>"+tot+"</span> / "+tot+" h ");
                            jQuery('#step2').find('.durationTot').each(function(){
                                jQuery(this).blur(function(){
                                    var tot = 0;
                                    jQuery('#step2').find('.durationTot').each(function(){
                                        tot += parseFloat(jQuery(this).val());
                                    });
                                    jQuery('#totImportDur').replaceWith("<span id='totImportDur'>"+tot+"</span>");
                                });
                            });
                        }
                     });
                } else {
                    jQuery("#Assocdialog").dialog('close');
                }
            },
            "Annuler": function(){
                jQuery("#Assocdialog").dialog('close');
            }
        } // Close button
    });
    
step2c = false;
    jQuery("#commandedialog").dialog({
        autoOpen: false,
        modal: true ,
        show: 'slide',
        label: 'Importation d\'une commande',
        width: 820,
        position: "center",
        bgiframe: true,
        open: function(){
            step2c=false;
            jQuery('#step2c').replaceWith('<div id="step2c"></div>');
        },
        buttons: {
            "Importer": function()
            {
                if(step2c)
                {
                    step2c = false;

                    //validateForm => duree sup0, date au format FR+ time et tache nom vide
                    if (jQuery("#step2cform").validate({}).form()) {
                            //sendDatas to Ajax
                            var datas = jQuery('#step2cform').serialize();
                            jQuery.ajax({
                                async: true,
                                type: "post",
                                url: "commande-xmlresponse.php",
                                data: "action=step2cImport&" + datas,
                                success: function(msg){
                                    jQuery('#gridListTask').trigger("reloadGrid");
                                    jQuery("#commandedialog").dialog('close');
                                }
                            });
                    }
                } else if(jQuery('#commandeId :selected').val())
                {
                    jQuery.ajax({
                        async: false,
                        type: "post",
                        url: "commande-xmlresponse.php",
                        data: "projId="+projetId+"&action=import&commandeId="+jQuery('#commandeId :selected').val(),
                        success: function(msg)
                        {
                            step2c = true;
                            //Affiche une 2eme partie dans le dialog avec
                            //Desc + serv => tache
                            //Utilisateur => Attrib
                            //Role => Attrib
                            //temps pour la tache (rempli auto)
                            var longHtml = "<form id='step2cform' onSubmit='return(false);'><table width=100% cellpadding=10>";
                                longHtml += "<tr><th class='ui-widget-header ui-state-default'>T&acirc;che";
                                longHtml += "    <th class='ui-widget-header ui-state-default'>D&eacute;but";
                                longHtml += "    <th class='ui-widget-header ui-state-default'>Utilisateur";
                                longHtml += "    <th class='ui-widget-header ui-state-default'>R&ocirc;le";
                                longHtml += "    <th class='ui-widget-header ui-state-default'>Dur&eacute;e";
                            jQuery(msg).find('task').each(function(){
                                var id = jQuery(this).find('id').text();
                                var name = jQuery(this).find('name').text();
                                var desc = jQuery(this).find('desc').text();
                                var dateo = jQuery(this).find('dateo').text();
                                var userList = jQuery(this).find('userList').text();
                                var role = jQuery(this).find('role').text();
                                var duration = jQuery(this).find('duration').text();
                                longHtml += "<tr><td class='ui-widget-content' label='"+desc+"'><input class='required' name='name"+id+"' value='"+name+"'>"
                                longHtml += "    <td class='ui-widget-content' align=center><input class='required FRDate' style='text-align:center' class='datepicker' name='dateo"+id+"' value='"+dateo+"'>";
                                longHtml += "    <td class='ui-widget-content' align=center>"+userList;
                                longHtml += "    <td class='ui-widget-content' align=center>"+role;
                                longHtml += "    <td class='ui-widget-content' align=center><input class='required sup0 durationTot' style='width:4em; text-align:center' name='dur"+id+"' value='"+duration+"' >" ;
                            });
                            longHtml += "<tr><td colspan=3>&nbsp;<th class='ui-widget-header ui-state-default' style='font-size:1.3em; font-weight:900'>Total<td style='font-size:1.3em; font-weight:900' align=center class='ui-widget-content'><span id='totImportDur'>h</span>";
                            longHtml += "</table></form>";

                            jQuery('#step2c').replaceWith('<div id="step2c">'+longHtml+'</div>');
                            /*jQuery('#step2c').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });*/
                            jQuery('#step2c').find('.datepicker').datetimepicker();
                            var tot = 0;
                            jQuery('#step2c').find('.durationTot').each(function(){
                                tot += parseFloat(jQuery(this).val());
                            });
                            jQuery('#totImportDur').replaceWith("<span id='totImportDur'>"+tot+"</span> / "+tot+" h ");
                            jQuery('#step2c').find('.durationTot').each(function(){
                                jQuery(this).blur(function(){
                                    var tot = 0;
                                    jQuery('#step2c').find('.durationTot').each(function(){
                                        tot += parseFloat(jQuery(this).val());
                                    });
                                    jQuery('#totImportDur').replaceWith("<span id='totImportDur'>"+tot+"</span>");
                                });
                            });
                        }
                     });
                } else {
                    jQuery("#commandedialog").dialog('close');
                }
            },
            "Annuler": function(){
                jQuery("#commandedialog").dialog('close');
            }
        } // Close button
    });


    jQuery("#deldialog").dialog({
        autoOpen: false,
        modal: true ,
        show: 'slide',
        label: 'Suppression d\'une t&acirc;che',
        width: 740,
        position: "center",
        bgiframe: true,
        buttons: {
            "Ok": function()
            {
                jQuery.ajax({
                    async: false,
                    type: "post",
                    url: "projectLight-xmlresponse.php",
                    data: "taskId="+curTaskID+"&action=delete",
                    success: function(msg){
                        //reload
                            jQuery('#gridListTask').trigger("reloadGrid");
                            jQuery("#deldialog").dialog('close');
                        }
                 });
            },
            "Annuler": function(){
                jQuery("#deldialog").dialog('close');
            }
        } // Close button
    });

    jQuery("#Moddialog").dialog({
        autoOpen: false,
        modal: true ,
        show: 'slide',
        label: 'Modification d\'une t&acirc;che',
        width: 830,
        position: "center",
        bgiframe: true,
        open: function(event, ui) {
            resetModDialog();
            jQuery.ajax({
                async: false,
                type: "post",
                url: "projectLight-xmlresponse.php",
                data: "taskId="+curTaskID+"&action=descTask",
                success: function(msg){
                    jQuery("#Modname").val(jQuery(msg).find('pName').text());
                     var dateText = jQuery(msg).find('pStart').text();
                    if (dateText+"x"!="x")
                        jQuery("#Moddatedeb").val(dateText);

                    var parentId = jQuery(msg).find('pParent').text();
                    var ModtypeId = jQuery(msg).find('pType').text();
                    jQuery("#Modparent").val(parentId);
                    jQuery("#Modtype").val(ModtypeId);
                    var statut = jQuery(msg).find('pStatut').text();
                    jQuery('#Modstatut').val(statut);
                    var comp = jQuery(msg).find('pComp').text();
                    comp = parseFloat(comp);
                    jQuery("#Modslider").slider('value',(comp > 0?comp:0));
                    jQuery("#ModshortDesc").val(jQuery(msg).find('caption').text());
                    jQuery("#ModDesc").val(jQuery(msg).find('desc').text());
                    jQuery("#ModUrl").val(jQuery(msg).find('pLink').text());

                },//fin de success
            });//fin de ajax
    },
    buttons: {
        "Annuler": function(){
            jQuery("#Moddialog").dialog('close');
        },
        "Ok": function() {
            mod="Mod";
            var action = "Mod";
            //send datas to project-xmlresponse.php?id=
            var postStr = "";
                postStr += "&datedeb="+jQuery("#Moddatedeb").val();
                postStr += "&parent="+jQuery("#Modparent :selected").val();
                postStr += "&type="+jQuery("#Modtype :selected").val();
                postStr += "&progress="+jQuery("#Modslider").slider('value');
                postStr += "&url="+jQuery("#ModUrl").val();
                postStr += "&statut="+jQuery("#Modstatut").val();
                postStr += "&description="+jQuery("#ModDesc").val();
                postStr += "&shortDescription="+jQuery("#ModshortDesc").val();
                postStr += "&name="+jQuery("#Modname").val();
                postStr += "&userid="+userId;

            if (jQuery("#ModForm").validate({
                rules: {
                        Moddatedeb: {
                            FRDate: true,
                            required: true,
                        },
                        Modparent: {
                            required: true,
                            speselect: true
                        },
                        Modname: {
                            required: true,
                            minlength: 2
                        }
                    },
                    messages: {
                        Moddatedeb: {
                          FRDate: "<br>Le format de la date est inconnu",
                          required: "<br>Champ requis"
                        },
                        Modname: {
                            required: "<br>Champ requis",
                            minlength: "<br>Le nom doit faire au moins 2 caract&egrave;res"
                        },
                        Modparent: {
                            required: "<br>Champ requis",
                            speselect: "Merci de s&eacute;l&eacute;tionner un parent"
                        },
                    }
                }).form()) {
                    jQuery.ajax({
                        async: true,
                        type: "post",
                        url: "projectLight-xmlresponse.php",
                        data: "id="+projetId+"&action=update&taskId=" + curTaskID + postStr,
                        success: function(msg){
                            //reload jqGrid
                            jQuery('#gridListTask').trigger("reloadGrid");
                            jQuery("#Moddialog").dialog('close');
                        }
                    }); //close ajax
                } // close validator
            }

        } // Close button
    });
    jQuery("#adddialog").dialog({
        autoOpen: false,
        modal: true ,
        show: 'slide',
        label: 'Ajouter une t&acirc;che',
        width: 830,
        bgiframe: true,
        open: function(){
            currentGrp = 0;
            currentUser = 0;
            currentModTask=-1;
        },
        position: "center",
        buttons: {
            "Annuler": function(){
                jQuery("#adddialog").dialog('close');
            },
            "Ok": function(){
                currentModTask=-1;
                var name=jQuery("#addname").val();
                var datedeb=jQuery("#adddatedeb").val();
                var complet = jQuery("#addslidercomplet").val();
                if ("x"+complet == "x")
                {
                    complet=0;
                }
                var desc = jQuery("#addDesc").val();
                var shortDesc = jQuery("#addshortDesc").val();
                var url = jQuery("#addUrl").val();
                var statut = jQuery("#addstatut").val();

                var parent = jQuery("#addparent").val();
                var type = jQuery("#addtype").val();

                var postStr = "action=insert&id="+projetId;
                    postStr += "&name="+name;
                    postStr += "&datedeb="+datedeb;
                    postStr += "&userid="+userId;
                    postStr += "&complet="+complet;
                    postStr += "&desc="+desc;
                    postStr += "&shortDesc="+shortDesc;
                    postStr += "&url="+url;
                    postStr += "&parent="+parent;
                    postStr += "&type="+type;
                    postStr += "&statut="+statut;



                    if (jQuery("#addForm").validate({
                        rules: {
                            adddatedeb: {
                                FRDate: true,
                                required: true,
                            },
                            adddatefin: {
                                FRDate: true,
                                required: true
                            },
                            addparent: {
                                required: true,
                                speselect: true
                            },
                            addname: {
                                required: true,
                                minlength: 2
                            }
                        },
                        messages: {
                            adddatedeb: {
                                  FRDate: "Le format de la date est inconnu",
                                  required: "<br>Champ requis"
                                },
                            adddatefin: {
                                  FRDate: "<br>Le format de la date est inconnu",
                                  required: "<br>Champ requis"
                                },
                            addname: {
                                required: "<br>Champ requis",
                                minlength: "<br>Le nom doit faire au moins 2 caract&egrave;res"
                            },
                            addparent: {
                                required: "<br>Champ requis",
                                speselect: "Merci de s&eacute;lectionner un parent"
                            },
                        }
                    }).form()) {

                        jQuery.ajax({
                            type: "POST",
                            url: "projectLight-xmlresponse.php",
                            data: postStr,
                            success: function(msg){
                                //reload jqGrid
                                jQuery('#gridListTask').trigger("reloadGrid");
                                jQuery("#adddialog").dialog('close');
                            }
                        });
                    }
                }
            }

        });   // close dialog


       //DatePicker

        jQuery('.datepicker').each(function(){
            jQuery(this).datetimepicker();
            jQuery(this).val("");
        });
        jQuery("#ui-datepicker-div").addClass("promoteZ");
        jQuery("#ui-timepicker-div").addClass("promoteZ");
        
        //Buttons

        jQuery("#ajouter").click(function(){
            mod="add";
            jQuery("#adddialog").dialog('open');
        });
        jQuery("#importer").click(function(){
            jQuery("#Assocdialog").dialog('open');
        });
	jQuery("#importerCommande").click(function(){
            jQuery("#commandedialog").dialog('open');
        });

        jQuery("#addaddUserBut").click(function(){
            //Ajoute dans le tableau + mets en place un Array global
            var role = jQuery('#addaddActo :selected').val();
            var userid = jQuery('#addadduserid :selected').val();
            var dur = jQuery('#adddurActo').val();
            jQuery('#addtargetUser').append('<tr><td>'+role+'<td>'+userid+"<td>"+dur+"<td><button class='butAction' onClick='jQuery(this).parents(\"tr\").remove();'>Supprimer</button>");
        });
        //End button
        //dialog accordion

//        jQuery("#accordionadd").tabs({cache: true,fx: { opacity: 'toggle' }, spinner:"Chargement ..."});
//        jQuery("#accordionMod").tabs({cache: true,fx: { opacity: 'toggle' }, spinner:"Chargement ..."});

    }); //end document.ready


    var currentModTask = "-1";
    function editTask(obj,taskId,rowType)
    {
        curTaskID=taskId;
        jQuery("#Moddialog").dialog('open');
    }

    function delTask(taskId)
    {
        curTaskID = taskId;
        //addDialog
        jQuery("#deldialog").dialog('open');
    }

    function resetModDialog()
    {
        jQuery("#Modname").val("");
        jQuery("#adddatedeb").val("");
        /*jQuery("#Modparent").selectmenu('value',-1);
        jQuery("#Modtype").selectmenu('value',-1);*/
        jQuery("#ModshortDesc").val("");
        jQuery("#ModDesc").val("");
        jQuery("#ModUrl").val("");
        jQuery('#Modslider').slider('value',0);
    }
</script>
EOF;
}


llxHeader($js, $langs->trans("Tasks"), "Tasks", "", '', '', array('/Synopsis_Common/jquery/jquery.validate.min.js'/*, '/includes/jquery/js/jquery-ui-latest.custom.min.js'*/));

$projet = new SynopsisProject($db);
$projet->fetch($_GET["id"]);
$projet->societe->fetch($projet->societe->id);
$head = synopsis_project_prepare_head($projet);
dol_fiche_head($head, 'tasks', $langs->trans("Project"));
//saveHistoUser($projet->id, "projet",$projet->ref);
$requete = "SELECT *
              FROM " . MAIN_DB_PREFIX . "user
             WHERE statut = 1
          ORDER BY firstname, lastname";
$sql = $db->query($requete);
$optUsrStr = "";
while ($res = $db->fetch_object($sql)) {
    $optUsrStr .= "<option value='" . $res->rowid . "'>" . $res->firstname . " " . $res->name . "</option>";
}
print "<script>";
print 'var optUsrStr = "<option value=\'-1\'>S&eacute;lectionn&eacute;-></option>' . $optUsrStr . '";';
print "</script>";




print '<table id="gridListTask" class="scroll" cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListTaskPager" class="scroll" style="text-align:center;"></div>';

print '<div class="tabsAction">';

print "<button class='butAction butAction' id='ajouter'>Ajouter une t&acirc;che</button>";
if ($user->rights->synopsisprojet->creer) {
    //if($projet->statut == 0 || $projet->statut == 5)
    //print "<button class='butAction' id='ajouter'>Ajouter une t&acirc;che</button>";
    //if($projet->statut == 0)
    print "<button class='butAction butAction' id='importer'>Importer une propale</button>";
    //if($projet->statut == 0)
    print "<button class='butAction butAction' id='importerCommande'>Importer une commande</button>";
}


print '</div>';

//Importer depuis la proposition
//Nouvelle tache
// fk_projet   fk_task_parent  label   duration_effective  fk_user_creat   statut  note    progress    description color   url priority    shortDesc   level   tms
$requete = "SELECT *
              FROM " . MAIN_DB_PREFIX . "projet_task
             WHERE fk_projet = " . $projetid;
$sql = $db->query($requete);

$optDependStr = "";
$optGrpStr = "";
while ($res = $db->fetch_object($sql)) {
    $optDependStr .= "<option value='" . $res->rowid . "'>" . $res->label . "</option>";
    //si c'est un group
    if ($res->priority == 3) {
        $optGrpStr .= "<option value='" . $res->rowid . "'>" . $res->label . "</option>";
    }
}
foreach (array("0" => array("id" => 'Moddialog', "legend" => 'Modifier une t&acirc;che', "mode" => "Mod"),
 "1" => array("id" => 'adddialog', "legend" => 'Ajouter une t&acirc;che', "mode" => "add")
)
as $key => $val) {
    print '<div style="display:none;" id="' . $val["id"] . '" label="' . $val['legend'] . '" style="background-color:#FFFFFF;   width: 870px; border: 1px Solid #CCCCCC;">';
    print ' <div><form onSubmit="return false" id="' . $val['mode'] . 'Form">';
    print '    <fieldset style="padding :10px; margin: 10px;">';
    print '        <legend>' . $val['legend'] . '</legend>';
    print displayHTMLTable_tpl($val['mode'], $optDependStr, $optGrpStr, $optUsrStr, $db);
    print '    </fieldset>';
    print ' </form></div>';
    print '</div>';
}


llxFooter('$Date: 2008/02/25 20:03:27 $ - $Revision: 1.20 $');

function displayHTMLTable_tpl($mode, $optDependStr, $optGrpStr, $optUsrStr, $db) { //keep source space, display form
    require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
    $html = new Form($db);

    $htmlTable = "";
//    $htmlTable .= '<div id="accordion'.$mode.'">';
//    $htmlTable.="<ul>";
//    $htmlTable.='   <li><a href="#fragment'.$mode.'-1"><span>T&acirc;che</span></a></li>';
////    $htmlTable.='   <li><a href="#fragment'.$mode.'-2"><span>Utilisateur</span></a></li>';
//    $htmlTable.='</ul>';
    $htmlTable .= '<div id="fragment' . $mode . '-1">';
    $htmlTable .= '<table class="' . $mode . 'table" border=0 cellpadding=10 width=100% style=" border-collapse : collapse; ">';
    $htmlTable .= '  <tbody>';
    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default" style="min-width: 120px;">';
    $htmlTable .= '        <label for="' . $mode . 'name"><em>*</em>&nbsp;&nbsp;Nom de la t&acirc;che</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '        <input style="width:100%;" type="text" id="' . $mode . 'name" name="' . $mode . 'name">';
    $htmlTable .= '      </td>';
    $htmlTable .= '    </tr>';
    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default">';
    $htmlTable .= '        <label for="' . $mode . 'type"><em>*</em>&nbsp;&nbsp;Type</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '        <SELECT id="' . $mode . 'type"  name="' . $mode . 'type">';
    $htmlTable .= '          <option value="1">Etape</option>';
    $htmlTable .= '          <option value="2">T&acirc;che</option>';
    $htmlTable .= '          <option value="3">Groupe</option>';
    $htmlTable .= '        </SELECT>';
    $htmlTable .= '      </td>';
    $htmlTable .= '    </tr>';
    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default">';
    if ($mode == "add") {
        $htmlTable .= '    <label for="adddatedeb"><em>*</em>&nbsp;&nbsp;Date de d&eacute;but</label>';
        $htmlTable .= '  </th>';
        $htmlTable .= '  <td class="ui-widget-content">';
        $htmlTable .= '    <input type="text" class="datepicker" style="width: 177px;" id="adddatedeb" name="adddatedeb">';
        $htmlTable .= '  </td>';
        $htmlTable .= '</tr>';
        $htmlTable .= '<tr>';
//        $htmlTable .= '  <th align="left" class="ui-widget-header ui-state-default">';
//        $htmlTable .= '    <label for="adddatefin"><em>*</em>&nbsp;&nbsp;Date de fin</label>';
//        $htmlTable .= '  </th>';
//        $htmlTable .= '  <td class="ui-widget-content">';
//        $htmlTable .= '    <input type="text" class="AdatePickEnd" style="width: 177px;" id="adddatefin" name="adddatefin">';
    } else {
        $htmlTable .= '    <label for="Moddatedeb"><em>*</em>&nbsp;&nbsp;Date de d&eacute;but</label>';
        $htmlTable .= '  </td>';
        $htmlTable .= '  <td class="ui-widget-content">';
        $htmlTable .= '    <input type="text" class="datepicker" style="width: 177px;"  id="Moddatedeb"  name="Moddatedeb">';
        $htmlTable .= '  </td>';
        $htmlTable .= '</tr>';
        $htmlTable .= '<tr>';
//        $htmlTable .= '  <th align="left" class="ui-widget-header ui-state-default">';
//        $htmlTable .= '    <label for="Moddatefin"><em>*</em>&nbsp;&nbsp;Date de fin</label>';
//        $htmlTable .= '  </th>';
//        $htmlTable .= '  <td class="ui-widget-content">';
//        $htmlTable .= '         <input type="text" class="UdatePickEnd" style="width: 177px;"  id="Moddatefin" name="Moddatefin">';
    }
    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default">';
    $htmlTable .= '         <label for="' . $mode . 'parent"><em>*</em>&nbsp;&nbsp;Parent</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '         <SELECT id="' . $mode . 'parent"  name="' . $mode . 'parent">';
    $htmlTable .= '             <option value="-2">S&eacute;lection-></option>';
    $htmlTable .= '             <option value="-1">Racine du projet</option>';
    $htmlTable .= $optGrpStr;
    $htmlTable .= '         </SELECT>';
    $htmlTable .= '      </td>';
    $htmlTable .= '    </tr>';

    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default">';
    $htmlTable .= '         <label for="' . $mode . 'personne"><em>*</em>&nbsp;&nbsp;Participant</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '         <SELECT id="SelUser' . $mode . '"  name="' . $mode . 'parent">';
    $htmlTable .= '             <option value="-2">S&eacute;lection-></option>';
    $htmlTable .= $optGrpStr;
    $htmlTable .= '         </SELECT>';
    $htmlTable .= '      </td>';
    $htmlTable .= '    </tr>';


    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default">';
    $htmlTable .= '         <label for="' . $mode . 'shortDesc">Description courte</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '         <input style="width:100%;" type="text" id="' . $mode . 'shortDesc">';
    $htmlTable .= '      </td>';
    $htmlTable .= '    </tr>';

    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default">';
    $htmlTable .= '         <label for="' . $mode . 'statut">Statut</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '         <SELECT name="' . $mode . 'statut" id="' . $mode . 'statut">';
    $htmlTable .= '             <OPTION value="open">Ouvert</OPTION>';
    $htmlTable .= '             <OPTION value="closed">Ferm&eacute;</OPTION>';
    $htmlTable .= '         </SELECT>';
    $htmlTable .= '      </td>';
    $htmlTable .= '    </tr>';

    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default" rowspan=1>';
    $htmlTable .= '        <label for="' . $mode . 'Desc">Description</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '        <textarea style="width:100%;" type="text" id="' . $mode . 'Desc"></textarea>';
    $htmlTable .= '      </td>';
    $htmlTable .= '      <td colspan=2>';
    $htmlTable .= '    </tr>';
    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default">';
    $htmlTable .= '         <label for="' . $mode . 'complet">% Compl&eacute;tion</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '         <div id="' . $mode . 'slider" class="slider"></div>';
    $htmlTable .= '         <input type="hidden" id="' . $mode . 'slidercomplet" name="' . $mode . 'slidercomplet" value="">';
    $htmlTable .= '      </td>';
    $htmlTable .= '    </tr>';
    $htmlTable .= '    <tr>';
    $htmlTable .= '      <th align="left" class="ui-widget-header ui-state-default">';
    $htmlTable .= '         <label for="' . $mode . 'Url">Url de la t&acirc;che</label>';
    $htmlTable .= '      </th>';
    $htmlTable .= '      <td class="ui-widget-content">';
    $htmlTable .= '         <input style="width:100%;" type="text" id="' . $mode . 'Url">';
    $htmlTable .= '      </td>';
    $htmlTable .= '    </tr>';
    $htmlTable .= '  </tbody>';
    $htmlTable .= '</table>';
    $htmlTable .= '</div>';
    $htmlTable .= '    </div>';


    return($htmlTable);
}

print ' <div class="contextMenu" id="MenuJqGrid">';
print '        <ul>';
if ($user->rights->synopsisprojet->creer) {
    print '            <li id="ajouter">';
    print '                <img height=16 width=16 src="' . DOL_URL_ROOT . '/synopsisprojet/img/fromCrystal/edit_add.png" />';
    print '                Ajouter</li>';

    if ($user->rights->synopsisprojet->attribution) {
        print '            <li id="attribuer">';
        print '                <img height=16 width=16 src="' . DOL_URL_ROOT . '/synopsisprojet/img/fromCrystal/add_user.png" />';
        print '                Attribuer</li>';
    }
    print '            <li id="editer">';
    print '                <img height=16 width=16 src="' . DOL_URL_ROOT . '/synopsisprojet/img/fromCrystal/edit.png" />';
    print '                Editer</li>';
}
if ($user->rights->synopsisprojet->supprimer) {
    print '            <li id="supprimer">';
    print '                <img src="' . DOL_URL_ROOT . '/synopsisprojet/img/fromCrystal/editdelete.png" />';
    print '                Supprimer</li>';
}
print '        </ul>';
print ' </div>';


print ' <div id="deldialog">';
print " &Ecirc;tes vous s&ucirc;r(e) de vouloir supprimer cette t&acirc;che ?";
print ' </div>';



if ($user->rights->synopsisprojet->attribution) {
    print " <div id='userTimeDialog'>";
    print "  <form id='userTimeForm'>";
//1 Liste des utilisateurs
    $requete = "SELECT firstname,
                   lastname,
                   rowid as id
              FROM " . MAIN_DB_PREFIX . "user
             WHERE statut = 1
          ORDER BY firstname, lastname";
    $sql = $db->query($requete);
    print "<table width=100% cellpadding=10>";
    print "<tr><th class='ui-widget-header ui-state-hover'>Nom";
    print "    <th class='ui-widget-header ui-state-hover'>R&ocirc;le";
    print "    <th class='ui-widget-header ui-state-hover'>D&eacute;but";
    print "    <th class='ui-widget-header ui-state-hover'>Dur&eacute;e";
    while ($res = $db->fetch_object($sql)) {
        print "<tr><th class='ui-widget-header ui-state-default'>" . $res->firstname . " " . $res->name;
        print "    <td class='ui-widget-content'><SELECT class='resetRole' id='role" . $res->id . "' name='role" . $res->id . "'><OPTION value='admin'>Admin</OPTION><OPTION value='acto'>Intervenant</OPTION><OPTION value='read'>Lecteur</OPTION><OPTION value='info'>Info</OPTION></SELECT> ";
        print "    <td align=center class='ui-widget-content'><input class='FRDateNoReq resettaskDateDeb datepicker' name='taskDateDeb" . $res->id . "' id='taskDateDeb" . $res->id . "' style='text-align:center; width:8em;'></input>";
        print "    <td align=center class='ui-widget-content'><input class='resetDur' name='dur" . $res->id . "' id='dur" . $res->id . "' style='text-align:center; width:4em;'></input>";
    }
    print "<tr><th style='font-size: 1.3em;' class='ui-widget-header ui-state-default' colspan=3>Total";
    print "    <td align=center class='ui-widget-content'><span style='min-width:2em; font-size: 1.3em; font-weight: 900;' id='userTotal'>h</span>";

//2 Temps prevu
    print "</table>";
    print "</form>";
    print ' </div>';
}

print "<div id='Assocdialog'>";
$requete = "SELECT p.* FROM " . MAIN_DB_PREFIX . "propal p, " . MAIN_DB_PREFIX . "Synopsis_projet_view p2 WHERE p.fk_soc = p2.fk_soc AND p2.rowid = " . $projetid;
print "<form id='Assocform'>";
print "<table width=100% cellpadding=10>";
print "<tr><th class='ui-widget-header ui-state-default'>Proposition du projet";
print "    <td class='ui-widget-content'>";
print "        <select name='propalId' id='propalId'>";
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql)) {
    print "<OPTION value='" . $res->rowid . "'>" . $res->ref . "</OPTION>";
}
print "        </select>";
print "</table>";
print "<div id='step2'>";
print "</div>";
print "</form>";
print "</div>";

print "<div id='commandedialog'>";
$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "commande WHERE fk_soc = " . $projet->socid;
print "<form id='commandeform'>";
print "<table width=100% cellpadding=10>";
print "<tr><th class='ui-widget-header ui-state-default'>Commande à importer";
print "    <td class='ui-widget-content'>";
print "        <select name='commandeId' id='commandeId'>";
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql)) {
    print "<OPTION value='" . $res->rowid . "'>" . $res->ref . "</OPTION>";
}
print "        </select>";
print "</table>";
print "<div id='step2c'>";
print "</div>";
print "</form>";
print "</div>";
?>

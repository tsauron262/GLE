<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : history.php
  * GLE-1.2
  */

  require_once('pre.inc.php');
    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

    // Securite acces client
    $socid=0;
    if ($user->societe_id > 0)
    {
        $socid = $user->societe_id;
    }
    if ($user->societe_id >0 && isset($_GET["id"]) && $_GET["id"]>0)
    {
        $commande = new Synopsis_Commande($db);
        $commande->fetch((int)$_GET['id']);
        if ($user->societe_id !=  $commande->socid) {
            accessforbidden();
        }
    }

  llxHeader($js);
  
  
  print "Fonction désactivé";
  
  
//  print" <a href='index.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Retour</span></a>";
//  print "<div id='msg' class='ui-state-error error' style='max-height: 1.1em; vertical-align:middle; overflow: hidden;width:0; opacity:0; border: 1px transparent;'><div id='toReplace'></div></div>";
//
////JqGrid
//print "<br/>";
//    print '<table id="list2"></table> <div id="pager2"></div>';
//    print "<div id='dialogWebContent'><div id='toReplace'></div></div>";
//    print <<<EOF
//    <script>
//    var webContent = "";
//jQuery(document).ready(function(){
//    jQuery('#dialogWebContent').dialog({
//        autoOpen:false,
//        modal: true,
//        title: "R&eacute;sultat",
//        minWidth: 740,
//        width: 740,
//        show: 'slide',
//        hide: 'slide',
//        open:function(){
//            jQuery('#dialogWebContent').find('#toReplace').replaceWith('<div id="toReplace">'+webContent+'</div>');
//        },
//        buttons: {
//            Fermer: function(){
//                jQuery(this).dialog('close');
//            }
//        },
//    });
//    jQuery('#list2').jqGrid({
//        url:'ajax/importHisto-json_response.php',
//        datatype: 'json',
//        colNames:['rowid','Fichier','Date', 'Envoie mail', 'Affiche'],
//        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
//                   {
//                       name:'filename',
//                       index:'filename',
//                       width:120,
//                       align: 'left',
//                       searchoptions:{
//                           sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]
//                       },
//                   },
//                   {
//                       name:'datec',index:'datec', width:100,
//                       align:'center',
//                       sorttype:'date',
//                       formatter:'date',
//                       formatoptions:{srcformat:'Y-m-d H:i',newformat:'d/m/Y H:i'},
//                       editable:false,
//                       searchoptions:{
//                           dataInit:function(el){
//                               jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
//                               jQuery(el).datepicker({
//                                   regional: 'fr',
//                                   changeMonth: true,
//                                   changeYear: true,
//                                   showButtonPanel: true,
//                                   constrainInput: true,
//                                   gotoCurrent: true,
//                                   dateFormat: 'dd/mm/yy',
//                               });
//                               jQuery('#ui-datepicker-div').addClass('promoteZ');
//                           },
//                           sopt:['eq','ne','le','lt','ge','gt'],
//                       },
//                   },
//                   {name:'email',index:'affiche', width:70, align: 'center', sortable: false, search: false},
//                   {name:'affiche',index:'affiche', width:70, align: 'center', sortable: false, search: false},
//                 ],
//        rowNum:10,
//        rowList:[10,30,50],
//        width: 700,
//        height: 150,
//        pager: '#pager2',
//        sortname: 'datec',
//        sortorder: 'DESC',
//        beforeRequest: function(){
//            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
//        },
//        viewrecords: true,
//        sortorder: 'desc',
//        caption:'Historique importation'
//    }).navGrid('#pager2',{edit:false, add:false, del:false, search:true, view:false});
//});
//
//
//function sendMail(rowid)
//{
//    jQuery.ajax({
//        url: "ajax/sendMail-xml_response.php",
//        data:"id="+rowid ,
//        datatype:"xml" ,
//        type:"POST" ,
//        cache: false,
//        success: function(msg){
//            if (jQuery(msg).find('OK'))
//            {
//                jQuery("#msg").find('#toReplace').replaceWith('<div id="toReplace">Le mail a &eacute;t&eacute; envoy&eacute; avec succ&egrave;s</div>');
//                //jQuery("#msg").css('display','block');
//                jQuery("#msg").animate({
//                    display: "block",opacity: 1,border: "1px Solid",
//                    width: 700
//                }, 1000 );
//                setTimeout(function(){ jQuery("#msg").animate({
//                    width: 0, opacity: 0,
//                    display: "none",border: "1px Solid transparent",
//                }, 1000 ) },5000);
//            } else {
//                jQuery("#msg").find('#toReplace').replaceWith('<div id="toReplace">Echec lors de l\'envoi du mail</div>');
//                jQuery("#msg").animate({
//                    display: "block",opacity: 1,border: "1px Solid",
//                    width: 700
//                }, 1000 );
//                setTimeout(function(){ jQuery("#msg").animate({
//                    width: 0, opacity: 0,
//                    display: "none",
//                    border: "1px Solid transparent",
//                }, 1000 ) },5000);
//            }
//        }
//    });
//}
//function displayReport(rowid)
//{
//    jQuery.ajax({
//        url: "ajax/displayReport-xml_response.php",
//        data:"id="+rowid ,
//        datatype:"xml" ,
//        type:"POST" ,
//        cache: false,
//        success: function(msg){
//            webContent = jQuery(msg).find('webContent').text();
//            jQuery('#dialogWebContent').dialog('open');
//        }
//    });
//}
//</script>
//EOF;


?>

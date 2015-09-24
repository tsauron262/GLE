<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 30 mars 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : stock_GA.php
  * GLE-1.1
  */

  //par fourn
  //par cessionnaire
  //par client

  //par produit
  //par categorie de produit


require("./pre.inc.php");

$langs->load("suppliers");
$langs->load("orders");
$langs->load("companies");


$langs->load("suppliers");
$langs->load("orders");
$langs->load("companies");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

global $needJquery;
$needJquery= true;

$js .= ' <script src="'.$jqueryuipath.'/ui.progressbar.js" type="text/javascript"></script>';
$js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';


$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';

$js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

$js .= "<style>

.ui-jqgrid-bdiv { overflow-x: hidden !important; }
.tablediv .ui-jqgrid-view { max-width: 1060px; overflow-x: scroll; }
</style>";

$showFourn = true;
$showClient = true;
$showCessionnaire = true;
$showAll = true;
if ($_REQUEST['cessionnaire']==1)
{
    $showFourn = false;
    $showClient = false;
    $showCessionnaire = true;
    $showAll = false;
} else  if ($_REQUEST['client'] == 1)
{
    $showFourn = false;
    $showClient = true;
    $showCessionnaire = false;
    $showAll = false;
} else if ($_REQUEST['fournisseur'] == 1)
{
    $showFourn = true;
    $showClient = false;
    $showCessionnaire = false;
    $showAll = false;
}


$js .= "<style type='text/css'>body { position: static; }                 .ui-datepicker select.ui-datepicker-month, .ui-datepicker select.ui-datepicker-year  {width:48%;}
</style>
        <script type='text/javascript'>";
$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."societe
             WHERE 1=1 ";
//if (!$showAll)
//{
//    if ($showFourn)
//        $requete .=" AND ".MAIN_DB_PREFIX."societe.fournisseur > 0 ";
//    if ($showClient)
//        $requete .=" AND ".MAIN_DB_PREFIX."societe.client = 1  ";
//    if ($showCessionnaire)
//        $requete .=" AND ".MAIN_DB_PREFIX."societe.cessionnaire > 0 ";
//}

$sql = $db->query($requete);
$js .= 'var socRess = "';
$js .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
$js1 = 'var cessRess = "';
$js1 .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
$js2 = 'var fournRess = "';
$js2 .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
$js3 = 'var clientRess = "';
$js3 .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";

while ($res = $db->fetch_object($sql))
{
    $js .= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";
    if ($res->cessionnaire > 0)
    {
        $js1 .= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";
    }
    if ($res->fournisseur > 0)
    {
        $js2 .= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";
    }
    if ($res->client == 1)
    {
        $js3 .= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";
    }
}
$js = preg_replace('/;$/','',$js);
$js .= '";';
$js1 = preg_replace('/;$/','',$js1);
$js1 .= '";';
$js2 = preg_replace('/;$/','',$js2);
$js2 .= '";';
$js3 = preg_replace('/;$/','',$js3);
$js3 .= '";';

$js .= $js1."\n";
$js .= $js2."\n";
$js .= $js3."\n";

$requete = "SELECT DISTINCT ".MAIN_DB_PREFIX."c_departements.rowid,
                            ".MAIN_DB_PREFIX."c_departements.nom
                       FROM ".MAIN_DB_PREFIX."c_departements,
                            ".MAIN_DB_PREFIX."societe
                      WHERE ".MAIN_DB_PREFIX."societe.fk_departement =".MAIN_DB_PREFIX."c_departements.rowid ";
if (!$showAll)
{
    if ($showFourn)
        $requete .=" AND ".MAIN_DB_PREFIX."societe.fournisseur > 0 ";
    if ($showClient)
        $requete .=" AND ".MAIN_DB_PREFIX."societe.client = 1  ";
    if ($showCessionnaire)
        $requete .=" AND ".MAIN_DB_PREFIX."societe.cessionnaire > 0 ";

}

$sql = $db->query($requete);
$js .= 'var depRess = "';
$js .=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";

while ($res = $db->fetch_object($sql))
{
    $js .= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";
}

$js = preg_replace('/;$/','',$js);
$js .= '";';


$js .= 'var gridimgpath = "'.$imgPath.'/images/";';
$js .= 'var userId = "'.$user->id .'";';

if ($_REQUEST['cat'])
{
    $js .= 'var catRequest = "'.$_REQUEST['cat'] .'";';
} else {
    $js .= 'var catRequest = false;';
}

if (!$showAll)
{
    $js .=" var showAll = false; ";
    $js .=" var titleCaption = 'Stock de location'; ";
    if ($showFourn)
    {
        $js .= "var fournisseur = 1; ";
        $js .= "var socRess = cessRess; ";
        $js .="     titleCaption = 'Stock de location - Par fournisseurs'; ";
    } else {
        $js .=" var fournisseur = false; ";
    }
    if ($showClient)
    {
        $js .=" var client = 1; ";
        $js .= "var socRess = clientRess; ";
        $js .="     titleCaption = 'Stock de location - Par client'; ";
    } else {
        $js .=" var client = false; ";
    }
    if ($showCessionnaire)
    {
        $js .=" var cessionnaire = 1; ";
        $js .= "var socRess = cessRess; ";
        $js .="     titleCaption = 'Stock de location - Par cessionnaire'; ";
    } else {
        $js .=" var cessionnaire = false; ";
    }
} else {
    $js .=" var showAll = true; ";
    $js .=" var titleCaption = 'Stock de location'; ";
}



$jqgridJs .= <<<EOF
jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);

jQuery.jgrid.edit.msg.minValue="Ce champs est requis";
jQuery(document).ready(function() {
    var param = "";
    if (!showAll)
    {
        if (cessionnaire)
            param += '&cessionnaire='+cessionnaire;
        if (client)
            param += '&client='+client;
        if (fournisseur)
            param += '&fournisseur='+fournisseur;
    }
    //if (catRequest) { param += "&cat="+catRequest; }
    var grid;
    if (showAll)
    {
         grid = jQuery("#gridListGAStock").jqGrid({
                datatype: "json",
                    url:"ajax/listStockGA_json.php?action=subAllStock&userId="+userId+"&SubRowId="+param,
                    datatype: "json",
                    colNames: ['id',"Nom","Serial","Statut","Client","Fournisseur","Cessionnaire","Adresse","Date de location","Date de retour","Date de sortie d&eacute;finitive"],
                    colModel: [ {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                                {name:'nom',index:'p.nom', width:210, align:"center"},
                                {name:'serial',index:'serial', width:180, align:"center"},
                                {name:'statut',index:'statut', width:190,editoptions:{value:"0:En stock;1:En location;2:Revendu"},
                                    stype: 'select',
                                    edittype: 'select',
                                    editable: false,
                                    searchoptions:{sopt:['eq','ne']},
                                },
                                {name:'client',index:'client', width:220, align:"center",
                                    stype: 'select',
                                    edittype: 'select',
                                    editable: false,
                                    searchoptions:{sopt:['eq','ne']},
                                    editoptions: {
                                        value: clientRess,
                                    },
                                },
                                {name:'fournisseur',index:'fournisseur', width:220, align:"center",
                                    stype: 'select',
                                    edittype: 'select',
                                    editable: false,
                                    searchoptions:{sopt:['eq','ne']},
                                    editoptions: {
                                        value: fournRess,
                                    },
                                },
                                {name:'cessionnaire',index:'cessionnaire', width:220, align:"center",
                                    stype: 'select',
                                    edittype: 'select',
                                    editable: false,
                                    searchoptions:{sopt:['eq','ne']},
                                    editoptions: {
                                        value: cessRess,
                                    },
                                },
                                {name:'adresse',index:'adresse', width:320, align:"center"},
                                {name:'dateLoc',index:'dateLoc', width:120, align:"center",
                                                            sorttype:"date",
                                                            formatter:'date',
                                                            formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                                                            editable:false,formoptions:{ elmprefix:"   " },
                                                            searchoptions:{
                                                                dataInit:function(el){
                                                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                                                    jQuery(el).datepicker({
                                                                        dateFormat: 'dd/mm/yy',
                                                                        regional: 'fr',
                                                                        changeMonth: true,
                                                                        changeYear: true,
                                                                        showButtonPanel: true,
                                                                        constrainInput: true,
                                                                        gotoCurrent: true
                                                                    });
                                                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                                    //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                                                },
                                                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                                                            },
                                },
                                {name:'dateFinLoc',index:'dateFinLoc', width:120, align:"center",
                                                            sorttype:"date",
                                                            formatter:'date',
                                                            formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                                                            editable:false,formoptions:{ elmprefix:"   " },
                                                            searchoptions:{
                                                                dataInit:function(el){
                                                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                                                    jQuery(el).datepicker({
                                                                        dateFormat: 'dd/mm/yy',
                                                                        regional: 'fr',
                                                                        changeMonth: true,
                                                                        changeYear: true,
                                                                        showButtonPanel: true,
                                                                        constrainInput: true,
                                                                        gotoCurrent: true
                                                                    });
                                                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                                    //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                                                },
                                                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                                                            },
                                },
                                {name:'dateSortie',index:'dateSortie', width:120, align:"center",
                                                            sorttype:"date",
                                                            formatter:'date',
                                                            formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                                                            editable:false,formoptions:{ elmprefix:"   " },
                                                            searchoptions:{
                                                                dataInit:function(el){
                                                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                                                    jQuery(el).datepicker({
                                                                        dateFormat: 'dd/mm/yy',
                                                                        regional: 'fr',
                                                                        changeMonth: true,
                                                                        changeYear: true,
                                                                        showButtonPanel: true,
                                                                        constrainInput: true,
                                                                        gotoCurrent: true
                                                                    });
                                                                    jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                                    //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                                                },
                                                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                                                            },
                               },
                              ],
                rowNum:30,
                rowList:[30,50,100],
                imgpath: gridimgpath,
                pager: jQuery('#gridListGAStockPager'),
                sortname: 'id',
                sortorder: "asc",
                mtype: "POST",
                viewrecords: true,
                width: "1100",
                height: 500,
                beforeRequest: function(){
                    jQuery('#gview_gridListGAStock').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
                },
                sortorder: "asc",
                //multiselect: true,
                caption: "<span style='padding:4px; font-size: 13px; '>"+titleCaption+"</span>",
                viewsortcols: true,
            }).navGrid("#gridListGAStockPager",
                           { view:false, add:false,
                             del:false,
                             edit:false,
                             search:true,
                             position:"left"
                             }
                        );
      } else { //si pas showAll

         grid = jQuery("#gridListGAStock").jqGrid({
                datatype: "json",
                url: "ajax/listStockGA_json.php?userId="+userId+param,
                colNames:['id', "Tiers",'Ville','D&eacute;partement','Premi&egrave;re location'],
                colModel:[  {name:'rowid',index:'s.rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                            {
                                name:'Tiers',
                                index:'s.nom',
                                align:"left",
                                width: 250,
                                editable:true,
                                stype: 'select',
                                edittype: 'select',
                                editable: false,
                                searchoptions:{sopt:['eq','ne']},
                                editoptions: {
                                    value: socRess,
                                },
                                formoptions:{ elmprefix:"*  " }
                            },{
                                name:'Ville',
                                index:'ville',
                                align:"center",
                                width: 150,
                                searchoptions:{sopt:['eq','ne','bw','bn','in','ni','ew','en','cn','nc']}
                            },{
                                name:'D&eacute;partement',
                                index:'departement',
                                align:"center",
                                editable:true,
                                width: 150,
                                stype: 'select',
                                edittype: 'select',
                                editable: false,
                                editoptions: { value: depRess, },
                                searchoptions:{sopt:['eq','ne']},
                            },
                            {
                                name:'firstLoc',
                                index:'firstLoc',
                                width:95,
                                align:"center",
                                sorttype:"date",
                                formatter:'date',
                                formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                                editable:false,formoptions:{ elmprefix:"   " },
                                searchoptions:{
                                    dataInit:function(el){
                                        jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                        jQuery(el).datepicker({
                                            dateFormat: 'dd/mm/yy',
                                            regional: 'fr',
                                            changeMonth: true,
                                            changeYear: true,
                                            showButtonPanel: true,
                                            constrainInput: true,
                                            gotoCurrent: true
                                        });
                                        jQuery("#ui-datepicker-div").addClass("promoteZ");
                                        //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                    },
                                    sopt:['eq','ne',"le",'lt',"ge","gt"],
                                },
                            }
                          ],
                rowNum:30,
                rowList:[30,50,100],
                imgpath: gridimgpath,
                pager: jQuery('#gridListGAStockPager'),
                sortname: 's.nom',
                mtype: "POST",
                viewrecords: true,
                width: "1100",
                height: 500,
                beforeRequest: function(){
                    jQuery('#gview_gridListGAStock').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
                },
                sortorder: "asc",
                //multiselect: true,
                caption: "<span style='padding:4px; font-size: 13px; '>"+titleCaption+"</span>",
                viewsortcols: true,
                subGrid: true,
                subGridRowExpanded: function(subgrid_id, row_id) {
                     var subgrid_table_id, pager_id;
                     subgrid_table_id = subgrid_id+"_t";
                     mtype: "POST",
                     pager_id = "p_"+subgrid_table_id;
                     jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");
                     jQuery("#"+subgrid_table_id).jqGrid({
                            url:"ajax/listStockGA_json.php?action=subCessionnaire&userId="+userId+"&SubRowId="+row_id+param,
                            datatype: "json",
                            colNames: ['id',"Nom","Serial","Statut","Client","Fournisseur","Cessionnaire","Adresse","Date de location","Date de retour","Date de sortie d&eacute;finitive"],
                            colModel: [ {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                                        {name:'nom',index:'p.nom', width:210, align:"center"},
                                        {name:'serial',index:'serial', width:180, align:"center"},
                                        {name:'statut',index:'statut', width:190,editoptions:{value:"0:En stock;1:En location;2:Revendu"},
                                            stype: 'select',
                                            edittype: 'select',
                                            editable: false,
                                            searchoptions:{sopt:['eq','ne']},
                                        },
                                        {name:'client',index:'client', width:220, align:"center",
                                            stype: 'select',
                                            edittype: 'select',
                                            editable: false,
                                            searchoptions:{sopt:['eq','ne']},
                                            editoptions: {
                                                value: clientRess,
                                            },
                                        },
                                        {name:'fournisseur',index:'fournisseur', width:220, align:"center",
                                            stype: 'select',
                                            edittype: 'select',
                                            editable: false,
                                            searchoptions:{sopt:['eq','ne']},
                                            editoptions: {
                                                value: fournRess,
                                            },
                                        },
                                        {name:'cessionnaire',index:'cessionnaire', width:220, align:"center",
                                            stype: 'select',
                                            edittype: 'select',
                                            editable: false,
                                            searchoptions:{sopt:['eq','ne']},
                                            editoptions: {
                                                value: cessRess,
                                            },
                                        },
                                        {name:'adresse',index:'adresse', width:320, align:"center"},
                                        {name:'dateLoc',index:'dateLoc', width:120, align:"center",
                                                                    sorttype:"date",
                                                                    formatter:'date',
                                                                    formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                                                                    editable:false,formoptions:{ elmprefix:"   " },
                                                                    searchoptions:{
                                                                        dataInit:function(el){
                                                                            jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                                                            jQuery(el).datepicker({
                                                                                dateFormat: 'dd/mm/yy',
                                                                                regional: 'fr',
                                                                                changeMonth: true,
                                                                                changeYear: true,
                                                                                showButtonPanel: true,
                                                                                constrainInput: true,
                                                                                gotoCurrent: true
                                                                            });
                                                                            jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                                            //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                                                        },
                                                                        sopt:['eq','ne',"le",'lt',"ge","gt"],
                                                                    },
                                        },
                                        {name:'dateFinLoc',index:'dateFinLoc', width:120, align:"center",
                                                                    sorttype:"date",
                                                                    formatter:'date',
                                                                    formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                                                                    editable:false,formoptions:{ elmprefix:"   " },
                                                                    searchoptions:{
                                                                        dataInit:function(el){
                                                                            jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                                                            jQuery(el).datepicker({
                                                                                dateFormat: 'dd/mm/yy',
                                                                                regional: 'fr',
                                                                                changeMonth: true,
                                                                                changeYear: true,
                                                                                showButtonPanel: true,
                                                                                constrainInput: true,
                                                                                gotoCurrent: true
                                                                            });
                                                                            jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                                            //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                                                        },
                                                                        sopt:['eq','ne',"le",'lt',"ge","gt"],
                                                                    },
                                        },
                                        {name:'dateSortie',index:'dateSortie', width:120, align:"center",
                                                                    sorttype:"date",
                                                                    formatter:'date',
                                                                    formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                                                                    editable:false,formoptions:{ elmprefix:"   " },
                                                                    searchoptions:{
                                                                        dataInit:function(el){
                                                                            jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                                                            jQuery(el).datepicker({
                                                                                dateFormat: 'dd/mm/yy',
                                                                                regional: 'fr',
                                                                                changeMonth: true,
                                                                                changeYear: true,
                                                                                showButtonPanel: true,
                                                                                constrainInput: true,
                                                                                gotoCurrent: true
                                                                            });
                                                                            jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                                            //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                                                        },
                                                                        sopt:['eq','ne',"le",'lt',"ge","gt"],
                                                                    },
                                       },
                                      ],
                            rowNum:20,
                            width: "1560",
                            pager: pager_id,
                            imgpath: gridimgpath,
                            sortname: 'id',
                            sortorder: "asc",
                            height: '100%',
                        }).navGrid("#"+pager_id,{edit:false,add:false,del:false})
                },
            }).navGrid("#gridListGAStockPager",
                           { view:false, add:false,
                             del:false,
                             edit:false,
                             search:true,
                             position:"left"
                           }
                       );
    } // fin du if (showall)

});
EOF;
$js .= $jqgridJs."</script>";

llxHeader($js,'Stock de location',"",1);

print "<br/>";
print "<br/>";
print "<br/>";
    $head = array(
                  0=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php",'Stock de location','stock'),
                  1=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?fournisseur=1",'Stock de location fournisseur','stockf'),
                  2=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?client=1",'Stock de location client','stockc'),
                  3=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?cessionnaire=1",'Stock de location cessionnaire','stocke'),);
if ($_REQUEST['cessionnaire'])
{
    dol_fiche_head($head, 'stocke', $langs->trans("Location de produit - vue par cessionnaire"));
} else if ($_REQUEST['fournisseur'])
{
    dol_fiche_head($head, 'stockf', $langs->trans("Location de produit - vue par fournisseur"));
} else if ($_REQUEST['client'])
{
    dol_fiche_head($head, 'stockc', $langs->trans("Location de produit - vue par client"));
} else {
    dol_fiche_head($head, 'stock', $langs->trans("Location de produit"));
}

print '<table id="gridListGAStock" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListGAStockPager" class="scroll" style="text-align:center;"></div>';



$db->close();

llxFooter('$Date: 2008/03/31 03:57:06 $ - $Revision: 1.13 $');



?>
<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ressource_html-repsonse.php
  * GLE-1.1
  */
  require_once('../../main.inc.php');
  //require_once(DOL_DOCUMENT_ROOT."/core/lib/ressource.lib.php");


$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
//
//if (! ($user->rights->SynopsisRessources->SynopsisRessources->Utilisateur || $user->rights->SynopsisRessources->SynopsisRessources->Admin || $user->rights->SynopsisRessources->SynopsisRessources->Resa))
//{
//    accessforbidden();
//}
$soc = new Societe($db);
$soc->fetch($socid);
print '<html><head></head><body>';

if ($_REQUEST['projet_id']."x" == "x")
{
    //Affiche toutes les ressources de la societe
    //reprendre Synopsis_Ressources en ro


    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/ui.core.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/ui.progressbar.js" type="text/javascript"></script>';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/flick/jquery-ui-1.7.2.custom.css" />';
$js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

    $js .= '      <link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';


    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/ajaxfileupload.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';



    $js .= "<style type='text/css'>body { position: static; }</style>";

    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.bgiframe.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.dimensions.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.tooltip.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.treeview.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.cookie.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.filestyle.js" ></script>';

    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."user
                 WHERE statut = 1
              ORDER BY firstname, lastname";
    $sql = $db->query($requete);
    $jsEditopts["userincomp"]="";
     $jsEditopts["userincomp"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
    while ($res = $db->fetch_object($sql))
    {
        $jsEditopts["userincomp"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->firstname . " ".$res->name)))  . ";";
    }

    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources
                 WHERE isGroup = 1";
    $sql = $db->query($requete);
    $jsEditopts["catRess"]="";
     $jsEditopts["catRess"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
    while ($res = $db->fetch_object($sql))
    {
        $jsEditopts["catRess"].= $res->id . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";
    }

    $jsEditopts["userincomp"] = preg_replace('/;$/','',$jsEditopts["userincomp"]);
    $jsEditopts["catRess"] = preg_replace('/;$/','',$jsEditopts["catRess"]);
    $jqgridJs ="<script type='text/javascript'>";
    $jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
    $jqgridJs .= 'var userId="'.$user->id.'";';
    $jqgridJs .= 'var socId="'.$soc->id.'"; ';
    $jqgridJs .= 'var campId="'.$campagneId.'"; ';
    $jqgridJs .= 'var DOL_URL_ROOT="'.DOL_URL_ROOT.'"; ';
    $jqgridJs .= "var userincomp='".$jsEditopts["userincomp"]."'\n";
    $jqgridJs .= "var catRess='".$jsEditopts["catRess"]."'\n";



$jqgridJs .= <<<EOF
    var grid;
    var remParent = 0;
    var remCatName = "Ressources";
    var submitPhotoID=""; //case insert
    function showRessource(pParentId)
    {

        var get = "&parent="+pParentId;
        if (pParentId == -1)
        {
            get = "";
        }
        remParent=pParentId;
        //Get cat Name
        try{
            remCatName = $("#catRes"+pParentId).text();
        } catch(e){
            alert (e);
        }
        jQuery("#gridListRessources").setGridParam({url:"ajax/ressource_json.php?userId="+userId+get}).trigger("reloadGrid")
    }
    var get = "";
    $("#tree").treeview({
                            collapsed: false,
                            animated: "medium",
                            control:"#sidetreecontrol",
                            prerendered: true,
                            persist: "location",
    });
    grid = $("#gridListRessources").jqGrid({
            datatype: "json",
            url: "ajax/ressource_json.php?userId="+userId+get,
            colNames:['id',"D&eacute;signation", "Cat&eacute;gorie",'R&eacute;f&eacute;rent','Description','Date achat','Valeur','Co&ucirc;t unitaire','Photo'],
            colModel:[  {name:'rowid',index:'rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'nom',index:'nom', width:80, align:"center",editable:false,searchoptions:{
                            sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                        },
                        {
                            name:'categorie',
                            index:'categorie',
                            display:"none",
                            align:"center",
                            editable:false,
                            hidden:true,
                            hidedlg:false,
                            edithidden: false,
                            search:false,
                            stype: 'select',
                            edittype: 'select',
                            editable: true,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: catRess,
                            }
                        },
                        {
                            name:'fk_user_resp',
                            index:'fk_user_resp',
                            width:150,
                            align: 'center',
                            stype: 'select',
                            edittype: 'select',
                            editable: true,
                            searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]},
                            editoptions: {
                                value: userincomp,
                            },
                        },
                        {name:'description',index:'description', width:180, align:"center",editable:false,edittype:"textarea",
                            searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                        },
                        {
                            name:'date_achat',
                            index:'date_achat',
                            width:90,
                            align:"center",
                            sorttype:"date",
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    $(el).datepicker();
                                    $("#ui-datepicker-div").addClass("promoteZ");
                                    $("#ui-timepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],

                            },
                            editoptions:{
                                dataInit:function(el){
                                    $(el).datepicker();
                                    $("#ui-datepicker-div").addClass("promoteZ");
                                    $("#ui-timepicker-div").addClass("promoteZ");
                                }
                            },
                        },
                        {name:'valeur',index:'valeur', width:80, align:"center",editable:false,
                                                    searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},
                        },
                        {name:'cout',index:'cout', width:80, align:"center",editable:false,
                                                    searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},
                        },
                        {name:'photo',index:'photo', width:200, align:"center", edittype:'file',editable:false, search: false,sortable: false},
                      ],
            rowNum:10,
            rowList:[10,20,30],
            imgpath: gridimgpath,
            pager: jQuery('#gridListRessourcesPager'),
            sortname: 'id',
            mtype: "POST",
            viewrecords: true,
            autowidth: true,
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "<span style='padding:4px; font-size: 13px; '>Ressources</span>",
            viewsortcols: true,
            loadComplete: function(){
            //    alert ("loadComplete");
        },
EOF;
    if (!$user->rights->SynopsisRessources->SynopsisRessources->Admin)
    {
        $jqgridJs.= '    }).navGrid("#gridListRessourcesPager",'."\n";
        $jqgridJs.= '                   { add:false,'."\n";
        $jqgridJs.= '                     del:false,'."\n";
        $jqgridJs.= '                     edit:false,'."\n";
        $jqgridJs.= '                     search:true,'."\n";
        $jqgridJs.= '                     position:"left"'."\n";
        $jqgridJs.= '                     });'."\n";
    }

    $jqgridJs.= '    });'."\n";

    //
    $jqgridJs.= '</script>'."\n";


    $js .= $jqgridJs;
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Ressources/ressource.class.php');
    $ress = new Ressource($db);
//    llxHeader($js,$langs->trans("Ressources"),"Ressources","1");
top_menu($js,$langs->trans("Ressources"), "",1,false);
    //Affiche un jqgrid de suivie


    print '<div class="tabBar">';

    print "<table width=100%>";
    print "<tr><td style='width:150px;'>";


    print '  <div class="treeheader ui-widget ui-corner-top ui-widget-header"  style="padding-bottom: 4px; padding-left: 4px; padding-top: 4px; margin-top: 2px;" >Cat&eacute;gories</div>';
    print '<div style="overflow:auto; height: 551px; width: 200px; border: 0px;" class=" ui-widget ui-widget-content ">';
    print '<div id="tree" class="ui-widget ui-widget-content ui-corner-bottom" style="height: 547px; font-size: 0.8em; ">';

    print '  <ul class="treeview ui-widget treeview" id="tree" style="overflow-x: auto; min-height: 518px; border-bottom: 0px;">';
    print '  <li nowrap="on" class="lastCollapsable"><div class="hitarea collapsable-hitarea lastCollapsable-hitarea"></div><a id="catRes-1" href="javascript:showRessource(-1)"><strong>Ressources</strong></a>';

    $ress->parseRecursiveCat();

    print "</li>";
    print '</ul>';
    print '<div  class="scroll  ui-widget ui-widget-content ui-state-default ui-jqgrid-pager ui-corner-bottom" style="text-align: center;  width: 197px; height: 24px;">';
    print '<div  class="ui-pager-control" role="group" style="font-size: 1.1em; border: 1px none,font-weight: bold; outline: none ;">';
    print '</div>';
    print '</div>';
    print '</div>';
    print '</div>';

    print "<td>";
    print '<table id="gridListRessources" class="scroll" cellpadding="0" cellspacing="0"></table>';
    print '<div id="gridListRessourcesPager" class="scroll" style="text-align:center;"></div>';
    print "</div>";

    print "</td></tr></table>";



    //Affiche resa par modal + bouton voir dispo

} else {
    //Affiche les ressources du projet
    //Date début, date fin
    //Cout sur la période
    //Cout achat
    //Affiche toutes les ressources de la societe
    //reprendre Synopsis_Ressources en ro


    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js = ' <script src="'.$jspath.'/jquery-1.3.2.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/jquery-ui.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/ui.core.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jqueryuipath.'/ui.progressbar.js" type="text/javascript"></script>';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery-ui.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/flick/jquery-ui-1.7.2.custom.css" />';
$js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

    $js .= '      <link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';


    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/ajaxfileupload.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';



    $js .= "<style type='text/css'>body { position: static; }</style>";

    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.bgiframe.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.dimensions.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.tooltip.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.treeview.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.cookie.js" ></script>';
    $js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.filestyle.js" ></script>';

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE statut = 1 ORDER BY firstname, lastname";
                $sql = $db->query($requete);
                $jsEditopts["userincomp"]="";
                 $jsEditopts["userincomp"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
                while ($res = $db->fetch_object($sql))
                {
                    $jsEditopts["userincomp"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->firstname . " ".$res->name)))  . ";";
                }

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE isGroup = 1";
                $sql = $db->query($requete);
                $jsEditopts["catRess"]="";
                 $jsEditopts["catRess"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
                while ($res = $db->fetch_object($sql))
                {
                    $jsEditopts["catRess"].= $res->id . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->nom)))  . ";";
                }

                $jsEditopts["userincomp"] = preg_replace('/;$/','',$jsEditopts["userincomp"]);
                $jsEditopts["catRess"] = preg_replace('/;$/','',$jsEditopts["catRess"]);
    $jqgridJs ="<script type='text/javascript'>";
    $jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
    $jqgridJs .= 'var userId="'.$user->id.'";';
    $jqgridJs .= 'var socId="'.$soc->id.'"; ';
    $jqgridJs .= 'var campId="'.$campagneId.'"; ';
    $jqgridJs .= 'var DOL_URL_ROOT="'.DOL_URL_ROOT.'"; ';
    $jqgridJs .= "var userincomp='".$jsEditopts["userincomp"]."'\n";
    $jqgridJs .= "var catRess='".$jsEditopts["catRess"]."'\n";
    $jqgridJs .= "var projId='".$_REQUEST['projet_id']."'\n";



$jqgridJs .= <<<EOF
    var grid;
    var remParent = 0;
    var remCatName = "Ressources";
    var submitPhotoID=""; //case insert
    function showRessource(pParentId)
    {

        var get = "&parent="+pParentId;
        if (pParentId == -1)
        {
            get = "";
        }
        get += "&projId="+projId;

        remParent=pParentId;
        //Get cat Name
        try{
            remCatName = $("#catRes"+pParentId).text();
        } catch(e){
            alert (e);
        }

        jQuery("#gridListRessources1").setGridParam({url:"ajax/ressource_json.php?userId="+userId+get}).trigger("reloadGrid")

    }




        var get = "&projId="+projId;
        $("#tree").treeview({
                                collapsed: false,
                                animated: "medium",
                                control:"#sidetreecontrol",
                                prerendered: true,
                                persist: "location",
        });
        grid = $("#gridListRessources1").jqGrid({
                datatype: "json",
                url: "ajax/ressource_json.php?userId="+userId+get,
                colNames:['id',"D&eacute;signation", "Cat&eacute;gorie",'R&eacute;f&eacute;rent','Description','Date achat','Valeur','Co&ucirc;t horaire','Photo'],
                colModel:[  {name:'rowid',index:'rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                            {name:'nom',index:'nom', width:80, align:"center",editable:false,searchoptions:{
                                sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                            },
                            {
                                name:'categorie',
                                index:'categorie',
                                display:"none",
                                align:"center",
                                editable:false,
                                hidden:true,
                                hidedlg:false,
                                edithidden: false,
                                search:false,
                                stype: 'select',
                                edittype: 'select',
                                editable: true,
                                searchoptions:{sopt:['eq','ne']},
                                editoptions: {
                                    value: catRess,
                                }
                            },
                            {
                                name:'fk_user_resp',
                                index:'fk_user_resp',
                                width:150,
                                align: 'center',
                                stype: 'select',
                                edittype: 'select',
                                editable: true,
                                searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]},
                                editoptions: {
                                    value: userincomp,
                                },
                            },
                            {name:'description',index:'description', width:180, align:"center",editable:false,edittype:"textarea",
                                searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                            },
                            {
                                name:'date_achat',
                                index:'date_achat',
                                width:90,
                                align:"center",
                                sorttype:"date",
                                editable:false,
                                searchoptions:{
                                    dataInit:function(el){
                                        $(el).datepicker();
                                        $("#ui-datepicker-div").addClass("promoteZ");
                                        $("#ui-timepicker-div").addClass("promoteZ");
                                    },
                                    sopt:['eq','ne',"le",'lt',"ge","gt"],

                                },
                                editoptions:{
                                    dataInit:function(el){
                                        $(el).datepicker();
                                        $("#ui-datepicker-div").addClass("promoteZ");
                                        $("#ui-timepicker-div").addClass("promoteZ");
                                    }
                                },
                            },
                            {name:'valeur',index:'valeur', width:80, align:"center",editable:false,
                                                        searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},
                            },
                            {name:'cout',index:'cout', width:80, align:"center",editable:false,
                                                        searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},
                            },
                            {name:'photo',index:'photo', width:200, align:"center", edittype:'file',editable:false, search: false,sortable: false},
                          ],
                rowNum:10,
                rowList:[10,20,30],
                imgpath: gridimgpath,
                pager: jQuery('#gridListRessources1Pager'),
                sortname: 'id',
                mtype: "POST",
                viewrecords: true,
            autowidth: true,
                height: 500,
                sortorder: "desc",
                //multiselect: true,
                caption: "<span style='padding:4px; font-size: 13px; '>Ressources</span>",
                viewsortcols: true,
                loadComplete: function(){
                //    alert ("loadComplete");
                },
EOF;
    if (!$user->rights->SynopsisRessources->SynopsisRessources->Admin)
    {
        $jqgridJs.= '    }).navGrid("#gridListRessources1Pager",'."\n";
        $jqgridJs.= '                   { add:false,'."\n";
        $jqgridJs.= '                     del:false,'."\n";
        $jqgridJs.= '                     edit:false,'."\n";
        $jqgridJs.= '                     search:true,'."\n";
        $jqgridJs.= '                     position:"left"'."\n";
        $jqgridJs.= '                     });'."\n";
    }

    $jqgridJs.= '    });'."\n";

    //
    $jqgridJs.= '</script>'."\n";


    $js .= $jqgridJs;
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Ressources/ressource.class.php');
    $ress = new Ressource($db);
//    llxHeader($js,$langs->trans("Ressources"),"Ressources","1");
    top_menu($js,$langs->trans("Ressources"), "",1,false);
    //Affiche un jqgrid de suivie


    print '<div class="tabBar">';

    print "<table width=100%>";
    print "<tr><td style='width:150px;'>";


    print '  <div class="treeheader ui-widget ui-corner-top ui-widget-header"  style="padding-bottom: 4px; padding-left: 4px; padding-top: 4px; margin-top: 2px;" >Cat&eacute;gories</div>';
    print '<div style="overflow:auto; height: 551px; width: 200px; border: 0px;" class=" ui-widget ui-widget-content ">';
    print '<div id="tree" class="ui-widget ui-widget-content ui-corner-bottom" style="height: 547px; font-size: 0.8em; ">';

    print '  <ul class="treeview ui-widget treeview" id="tree" style="overflow-x: auto; min-height: 518px; border-bottom: 0px;">';
    print '  <li nowrap="on" class="lastCollapsable"><div class="hitarea collapsable-hitarea lastCollapsable-hitarea"></div><a id="catRes-1" href="javascript:showRessource(-1)"><strong>Ressources</strong></a>';

    $ress->parseRecursiveCat();

    print "</li>";
    print '</ul>';
    print '<div  class="scroll  ui-widget ui-widget-content ui-state-default ui-jqgrid-pager ui-corner-bottom" style="text-align: center;  width: 197px; height: 24px;">';
    print '<div  class="ui-pager-control" role="group" style="font-size: 1.1em; border: 1px none,font-weight: bold; outline: none ;">';
    print '</div>';
    print '</div>';
    print '</div>';
    print '</div>';

    print "<td>";
    print '<table id="gridListRessources1" class="scroll" cellpadding="0" cellspacing="0"></table>';
    print '<div id="gridListRessources1Pager" class="scroll" style="text-align:center;"></div>';
    print "</div>";

    print "</td></tr></table>";

}

print "</body></html>";
?>

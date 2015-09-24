<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listList.php
  * GLE-1.2
  */


  require_once('pre.inc.php');


    if(!$user->rights->process->configurer){
        accessforbidden();
  }

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";
$js="";

$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';


$js .= "<style type='text/css'>body { position: static; }                 .ui-datepicker select.ui-datepicker-month, .ui-datepicker select.ui-datepicker-year  {width:48%;}
</style>
        <script type='text/javascript'>";

$js .= 'var gridimgpath = "'.$imgPath.'/images/";'."\n";
$js .= 'var statusForm = "';
$js .=  "-1:" . preg_replace("/'/","\\'",traiteStr(html_entity_decode("S&eacute;lection ->")))  . ";";
$js .=  "1:" . preg_replace("/'/","\\'",traiteStr(html_entity_decode("Actif")))  . ";";
$js .=  "5:" . preg_replace("/'/","\\'",traiteStr(html_entity_decode("Inactif")))  . ";";
$js .= '";';

$jqgridJs .= <<<EOF


  jQuery.jgrid.edit.msg.minValue="Ce champs est requis";
jQuery(document).ready(function() {
    var grid = jQuery("#gridlistList").jqGrid({
            datatype: "json",
            url: "ajax/listList_json.php?",
            colNames:['id', "Label",'Description','Nb &Eacute;l&eacute;ments'],
EOF;
    $jqgridJs .= <<<EOF
            colModel:[  {name:'id',index:'s.rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {
                            name:'label',
                            index:'label',
                            align:"left",
                            width: 150,
                            editable: false,
                            searchoptions:{sopt:['eq','ne','bw','bn','in','ni','ew','en','cn','nc']}
                        },{
                            name:'description',
                            index:'description',
                            align:"left",
                            width: 750
                        },
                        {
                            name:'cnt',
                            index:'cnt',
                            width:75,
                            align:"center",
                            searchoptions:{sopt:['eq','ne','gt','ge','lt','le']},
                        }
EOF;
$jqgridJs .= <<<EOF

                      ],
            rowNum:30,
            rowList:[30,50,100],
            imgpath: gridimgpath,
            pager: jQuery('#gridlistListPager'),
            sortname: 'label',
            beforeRequest: function(){
                jQuery('#gview_gridlistList').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
            },
            gridComplete: function(){
EOF;
            $jqgridJs .= "jQuery('.butAction').mouseover(function(){";
            $jqgridJs .= "    jQuery(this).removeClass('ui-state-default');";
            $jqgridJs .= "    jQuery(this).addClass('ui-state-hover');";
            $jqgridJs .= "});";
            $jqgridJs .= "jQuery('.butAction').mouseout(function(){";
            $jqgridJs .= "    jQuery(this).removeClass('ui-state-hover');";
            $jqgridJs .= "    jQuery(this).addClass('ui-state-default');";
            $jqgridJs .= "});";

            $jqgridJs .= "jQuery('.butAction-rev').mouseover(function(){";
            $jqgridJs .= "    jQuery(this).removeClass('ui-state-hover');";
            $jqgridJs .= "    jQuery(this).addClass('ui-state-default');";
            //$jqgridJs .= "console.log(jQuery(this).find('.ui-icon').css('background-image'));";
            $jqgridJs .= "    jQuery(this).find('.ui-icon').css('background-image','url(\"".$css."/images/ui-icons_0073ea_256x240.png\")');";
//            $jqgridJs .= "    jQuery(this).find('.ui-icon').addClass('ui-state-default');";
            $jqgridJs .= "});";
            $jqgridJs .= "jQuery('.butAction-rev').mouseout(function(){";
            $jqgridJs .= "    jQuery(this).removeClass('ui-state-default');";
            $jqgridJs .= "    jQuery(this).addClass('ui-state-hover');";
            $jqgridJs .= "    jQuery(this).find('.ui-icon').css('background-image','url(\"".$css."/images/ui-icons_ffffff_256x240.png\")');";
            $jqgridJs .= "});";
$jqgridJs .= <<<EOF
            },
            mtype: "POST",
            viewrecords: true,
            autowidth: true,
            height: 500,
            sortorder: "asc",
            //multiselect: true,
            caption: "<span style='padding:4px; font-size: 16px;'>Listes</span>",
            viewsortcols: true,
            ondblClickRow: function(e,u){
                location.href='listBuilder.php?id='+e;
            },
EOF;

    $jqgridJs.= '    }).navGrid("#gridlistListPager",'."\n";
    $jqgridJs.= '                   { view:false, add:false,'."\n";
    $jqgridJs.= '                     del:false,'."\n";
    $jqgridJs.= '                     edit:false,'."\n";
    $jqgridJs.= '                     search:true,'."\n";
    $jqgridJs.= '                     position:"left"'."\n";
    $jqgridJs.= '                     });'."\n";


$jqgridJs.= '   });'."\n";
$js .= $jqgridJs."</script>";

llxHeader($js,traiteStr('Liste d\'élément'),'',1);

  print '<table id="gridlistList" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
  print '<div id="gridlistListPager" class="scroll" style="text-align:center;"></div>';


$db->close();

llxFooter('$Date: 2008/09/10 22:23:38 $ - $Revision: 1.60.2.2 $');


function traiteStr($str){
    return $str;
}
?>

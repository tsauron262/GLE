<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 8-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : recapCamp.php
  * GLE-1.1
  */


  //Grid avec la liste et les resultats
  //Subgrid avec les notes d'avancement

$campagneId = $_REQUEST['campagneId'];

  require_once('pre.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
$soc = new Societe($db);
$soc->fetch($socid);



$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js = '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';


$js .= '<script language="javascript" src="js/jquery.MetaData.js"></script>'."\n";
$js .= '<script language="javascript" src="js/jquery.rating.js"></script>'."\n";
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="css/jquery.rating.css" />';


    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
$js .= "<style type='text/css'>body { position: static; }</style>";
$js .= "<style type='text/css'>.ui-progressbar{ height: 13px; background-color: #ffffff; margin: 0px; float: left; width: 100%;}</style>";
$js .= "<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000;float: left; width: 100%; }
                                SELECT { width: 150px; }
                                INPUT.hasDatepicker { width: 150px;}
                                .ui-pg-selbox { width: auto; }</style>";
$js .= "<style type='text/css'>.promoteZ { z-index: 2006; /* Dialog z-index is 1006*/}</style>";


$jqgridJs = <<<EOF
<script type='text/javascript'>
EOF;
$jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
$jqgridJs .= 'var userId="'.$user->id.'";';
$jqgridJs .= 'var socId="'.$soc->id.'"; ';
$jqgridJs .= 'var campId="'.$campagneId.'"; ';

$requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_stcomm WHERE active = 1";
$sql = $db->query($requete);
$stCommStr="-2:aucun;";
while ($res = $db->fetch_object($sql))
{
    $stCommStr .= $res->id.":".$res->libelle.";";
}


$requete =  "SELECT DISTINCT ".MAIN_DB_PREFIX."user.rowid, concat(".MAIN_DB_PREFIX."user.firstname, ' ' ,".MAIN_DB_PREFIX."user.lastname) as fullname
               FROM ".MAIN_DB_PREFIX."user, Babel_campagne_people
              WHERE ".MAIN_DB_PREFIX."user.rowid = Babel_campagne_people.user_refid
           ORDER BY ".MAIN_DB_PREFIX."user.lastname";
$sql = $db->query($requete);
$userStr="-2:aucun;";
while ($res = $db->fetch_object($sql))
{
    $userStr .= $res->rowid.":".utf8_decode($res->fullname).";";
}

$stCommStr = preg_replace('/;$/','',$stCommStr);
$userStr = preg_replace('/;$/','',$userStr);
$jqgridJs .= 'var stcommStrSearch="'.$stCommStr.'"; ';
$jqgridJs .= 'var userIdStrSearch="'.$userStr.'";';




//Affiche Source  , note , date
//Permet d'ajouter des infos

$jqgridJs .= <<<EOF
var lastsel2;


$(document).ready(function(){
    var get = "";

$.datepicker.setDefaults($.extend({showMonthAfterYear: false, changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,}, $.datepicker.regional['fr']));
    if (campId > 0)
    {
        get = "&campId="+campId;
    }
    $("#gridListHistoProspect").jqGrid({
            datatype: "json",
            url: "ajax/recapCamp_json.php?userId="+userId+get,
            colNames:['id',"Statut Fermeture", 'Statut du prospect post campagne','Date de la derni&egrave;re prise en charge', 'Statut actuel','Commercial','Nom de la soci&eacute;t&eacute;'],
            colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'closeStatut',index:'closeStatut', width:80, align:"center",editable:false,searchoptions:{sopt:['eq','ne']},stype:"select",editoptions:{
                            value: '0:aucun;1:Positif;2:Negatif'
                        }},
                        {name:'closeStComm',index:'closeStComm', width:90, align:"center",searchoptions:{sopt:['eq','ne','lt','le','gt','ge']},stype:"select",editable:false,editoptions:{
                            value: stcommStrSearch,
                        }},
                        {name:'date_prisecharge',index:'date_prisecharge', width:90, align:"center",editable:false, datefmt: "dd/mm/yyyy",sorttype: "date",
                            searchoptions:{
                                sopt:['eq','ne','lt','gt'] ,
                                dataInit:function(el){
                                    $(el).datepicker({dateFormat:'dd/mm/yy'});
                                }
                            }
                        },
                        {name:'fk_statut',index:'fk_statut', width:90, align:"center",editable:false,searchoptions:{sopt:['eq','ne']},stype:"select",editoptions:{
                            value: "1:En attente;2:En cours;3:Ferme;4:Respousser",
                        }},
                        {name:'user_id',index:'user_id', width:90, align:"center",searchoptions:{sopt:['eq','ne']},stype:"select",editoptions:{
                            value: userIdStrSearch,
                        }},
                        {name:'socname',index:'socname', width:80, searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}, align:"center"},
                      ],
            rowNum:20,
            ondblClickRow: function(id){
                //show modal with note
//                if(id && id!= lastsel2){
//                    jQuery('#gridListHistoProspect').restoreRow(lastsel2);
//                    jQuery('#gridListHistoProspect').editRow(id,true,pickdates);
//                    lastsel2=id;
//                }


            },
            editurl: "ajax/editProspection_json.php",
            rowList:[20,30,40],
            imgpath: gridimgpath,
            pager: jQuery('#gridListHistoProspectPager'),
            sortname: 'id',
            mtype: "POST",
            viewrecords: true,
            width: "900",
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "R&eacute;capitulatif",
            viewsortcols: true,
            loadComplete: function(){
            },
             subGrid: true,
            subGridUrl: 'ajax/recapCamp_json.php?action=sub&userId='+userId+get,
            subGridRowExpanded: function(subgrid_id, row_id) {
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
                  url: 'ajax/recapCamp_json.php?action=sub&userId='+userId+get+'&campsocid='+row_id,
                  //url:"subgrid.php?q=2&id="+row_id,
                  datatype: "json",
                  colNames: ['Id','Date de la note','Avis','Avancement','Note'],
                  colModel: [
                    {name:"id",index:"id",width:55,key:true,hidden:true},
                    {
                        name:"dateModif",
                        index:"dateModif",
                        width:180,
                        datefmt: "dd/mm/yyyy",
                        sorttype:"date",
                    },
                    {name:"avis",index:"avis",width:105},
                    {name:"avancement",index:"avancement",width:180},
                    {name:"note",index:"note",width:390},
                    ],
                  height: "100%",
                  rowNum:20,
                  imgpath: gridimgpath,
                  sortname: 'dateModif',
                  sortorder: "desc",
                  ondblClickRow: function( rowid) {
                      displayExtra(row_id,rowid);
                  },
                  loadComplete: function(){
                        $('#'+subgrid_table_id+ ' tr').each(function(){
                            //Load progress and starratting
                            $(".progressbar").each(function(){
                                    var val = $(this).text();
                                    if (val + "x" != "x")
                                    {
                                        $(this).text('');
                                        $(this).progressbar( {
                                            value: val,
                                            orientation: "horizontal",
                                        }
                                        );
                                        $(this).css('height',"10px");
                                    }
                                    //$(this).find('.ui-progressbar-value').css('height','8px');
                                });
                        var iter = 0;
                        var content = $('#'+subgrid_table_id).getCell(this.id,2);
                        var val = "";
                        $(content).find('input').each(function(){
                            if($(this).attr("checked") && $(this).val()+"x" != 'x')
                            {
                                val = $(this).val();
                            }
                        });
                        $(this).find("#starrating :radio.star").rating();
                        $(this).find("#starrating :radio.star").rating("select",val);
                        $($this).find("#starrating :radio.star").rating('disable');

                        });
                    },
               })
           },
        }).navGrid('#gridListHistoProspectPager',
               { add:false,
                 del:false,
                 edit:false,
                 position:"left"
    }).navButtonAdd("#gridListSocLPager",
                    {
                        gridList:"Clear",
                        title:"Reset",
                        buttonicon :'ui-icon-refresh',
                        onClickButton:function(){ mygrid[0].clearToolbar();
                                                }
                    }
                    );
    jQuery("#tr_date_prisecharge","#gridListHistoProspect").datepicker({dateFormat:"dd/mm/yyyy"});
    $("#ui-datepicker-div").addClass("promoteZ");

    $("#dialog").dialog({
        modal: true,
        autoOpen: false,
        open: function(){
            $('#dialog').find('#date').text(date);
            $('#dialog').find('#avanc').text(avanc);
            $('#dialog').find('#note').text(note);
        }

    });

    });
function pickdates(id){
    jQuery("#"+id+"_dateModif","#gridListHistoProspect").datepicker({dateFormat:"dd/mm/yyyy"});
}

var avanc;
var date;
var note;
function displayExtra(gridId, id)
{
    var arr=new Array();
    $("#gridListHistoProspect_"+gridId).find("#"+id).find("td").each(function(){
        arr.push($(this).text());
    });
    alert ($('#'+subgrid_table_id).getCell(gridId,2).html());
     avanc = $('#'+subgrid_table_id).getCell(gridId,2).html();
     date = arr[3];
     note = arr[4];
    $("#dialog").dialog('open');

    //draw modal send datas


}
</script>
EOF;

$js .= $jqgridJs;


llxHeader($js,$langs->trans("Prospection"),"Prospection","1");

//Affiche un jqgrid de suivie


print '<div class="fiche"> ';

print '<div class="tabs">';
print '<a class="tabTitle">'.$langs->trans('Campagne Prospection').'</a>
       <a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=listCamp&id='.$campagneId.'">'.$langs->trans('Fiche').'</a>';
//print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=stats&id='.$campagne_id.'">'.$langs->trans('Statistiques').'</a>';
print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/affichePropection.php?action=list&campagneId='.$campagneId.'">Prospection</a>';
print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/recapCamp.php?campagneId='.$campagneId.'">R&eacute;capitulatif</a>';
print '<a  class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/statsCamp.php?campagneId='.$campagneId.'">Statistiques</a>';
print '</div>';
print '<div class="tabBar">';
print '<table id="gridListHistoProspect" class="scroll" cellpadding="0" cellspacing="0"></table>';
print '<div id="gridListHistoProspectPager" class="scroll" style="text-align:center;"></div>';
print "</div>";

print "<div id='dialog' style='display: none;'>";
print "<table>";
print "<tbody>";
print "<tr><td>Date</td><td><span id='date'></span></td></tr>";
print "<tr><td>Avancement</td><td><span id='avanc'></span></td></tr>";
print "<tr><td>Avis</td><td><span id='avis'></span></td></tr>";
print "<tr><td>Note</td><td><span id='note'></span></td></tr>";
print "</tbody>";
print "</table>";
print "</div>";

?>

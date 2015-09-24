<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
/*
 */

/**
        \file       htdocs/contrat/info.php
        \ingroup    contrat
        \brief      Page des informations d'un contrat
        \version    $Id: info.php,v 1.16 2008/03/01 01:26:47 eldy Exp $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GMAO/SAV.class.php");

$langs->load("contracts");

// Security check
$contratid = isset($_REQUEST["id"])?$_REQUEST["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid,'');
    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';


/*
* View
*/

llxHeader($js,utf8_decode('Détails SAV'),'1');

$contrat = $contrat=getContratObj($_REQUEST["id"]);
$contrat->fetch($_GET["id"]);
$contrat->info($_GET["id"]);

$head = contract_prepare_head($contrat);
//$head = $contrat->getExtraHeadTab($head);

$hselected = 5;

dol_fiche_head($head, 'sav', $langs->trans("Contract"));


//jqGrid SAV

print '<table id="list1"></table> <div id="pager1"></div>';

$arrResByLine = array();
$arrResByProd = array();
$arrResByStatut = array();
$arrProdByLine = array();

$requete = "SELECT rowid, fk_product FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$contratid;
$sql = $db->query($requete);
while ($res = $db->fetch_object($requete))
{
    $arrResByLine[$res->rowid] = 0;
    $arrResByProd[$res->fk_product] = 0;
    $arrProdByLine[$res->rowid]=$res->fk_product;
}
$requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_GMAO_status WHERE active=1";
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql))
{
    $arrResByStatut[$res->id] = 0;
}

$requete = "SELECT Babel_GMAO_SAV_client.id,
                   Babel_GMAO_SAV_client.statut,
                   ifnull(Babel_GMAO_SAV_client.fk_product,-1) as fk_product,
                   element_id
              FROM Babel_GMAO_SAV_client
             WHERE Babel_GMAO_SAV_client.element_id IN (SELECT rowid FROM ".MAIN_DB_PREFIX."contratdet WHERE  ".MAIN_DB_PREFIX."contratdet.fk_contrat = ".$contratid.")
               AND Babel_GMAO_SAV_client.element_type LIKE 'contrat%' ";
$sql = $db->query($requete);

$totalSAV = 0;
while ($res=$db->fetch_object($sql))
{
    if ($arrResByStatut[$res->statut]."x" == "x"){
        $arrResByStatut[$res->statut] = 0;
    }
    if ($arrResByProd[$res->fk_product]."x" == "x"){
        $arrResByProd[$res->fk_product] = 0;
    }
    $arrResByStatut[$res->statut]++;
    $arrResByProd[$res->fk_product]++;
    $arrResByLine[$res->element_id]++;
    $totalSAV++;
}
print "<div class='titre'>Total</div>";
print "<h2>Fiche SAV : ".$totalSAV."</h2>";
print "<div id='tab'>";
print "<ul>";
print " <li><a href='#Statut'><span>Par statut</span></a></li>";
print " <li><a href='#Produit'><span>Par produit</span></a></li>";
print " <li><a href='#Ligne'><span>Par ligne</span></a></li>";
print "</ul>";
print " <div id='Statut'>";
print "<table cellpadding=15 width=450>";

$objSav = new SAV($db);
print "<tr><th class='ui-widget-header ui-state-hover'>Statut<th class='ui-widget-header ui-state-hover'>Total";
foreach($arrResByStatut as $key=>$val)
{
    print "<tr><th align=left class='ui-widget-header ui-state-default'>".$objSav->LibStatut($key,4)."<td align=center class='ui-widget-content'>".$val;
}
print "</table>";
print '</div>';
print " <div id='Produit'>";
print "<table cellpadding=15 width=450>";
print "<tr><th class='ui-widget-header ui-state-hover'>Produit<th class='ui-widget-header ui-state-hover'>Total";
require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
foreach($arrResByProd as $key=>$val)
{
    $tmpProd = new Product($db);
    $tmpProd->fetch($key);
    print "<tr><th align=left class='ui-widget-header ui-state-default'>".$tmpProd->getNomUrl(1)."   ".$tmpProd->libelle."<td class='ui-widget-content' align=center>".$val;
}
print "</table>";
print '</div>';
print " <div id='Ligne'>";
print "<table cellpadding=15 width=450>";
print "<tr><th class='ui-widget-header ui-state-hover'>Ligne<th class='ui-widget-header ui-state-hover'>Total";
foreach($arrResByLine as $key=>$val)
{
    $tmpProd = new Product($db);
    $tmpProd->fetch($arrProdByLine[$key]);
    print "<tr><th align=left class='ui-widget-header ui-state-default'>".$tmpProd->getNomUrl(1)."   ".$tmpProd->libelle."<td class='ui-widget-content' align=center>".$val;
}
print "</table>";
print '</div>';
print '</div>';

print '</div>';


$db->close();
print '<script>';
print 'var contratId='.$contratid.';';
print <<<EOF
jQuery(document).ready(function(){
    jQuery('#tab').tabs({
        spinner: "Chargement",
        cache: true,
        fx: { opacity: "toggle" }
    });

    jQuery('#list1').jqGrid({
        url:'ajax/savByContrat-json_response.php?id='+contratId,
        datatype: 'json',
        colNames:['rowid','Description','Date de fin', 'Num. S&eacute;rie'],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'description',index:'description', align: 'left'},
                   {name:'DateFin',index:'DateFin', width:40,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                   {name:'serial_number',index:'serial_number', width:80, align: 'center'},
                 ],
        rowNum:10,
        rowList:[10,30,50],
        width: 900,
        height: 300,
        pager: '#pager1',
        sortname: 'rowid',
        beforeRequest: function(){
            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
        },
        viewrecords: true,
        sortorder: 'desc',
        caption:'Historique contrat',
        subGrid: true,
        subGridUrl:'ajax/savByContrat-json_response.php?action=subGrid',
        subGridRowExpanded: function(subgrid_id, row_id) {
            var subgrid_table_id;
            subgrid_table_id = subgrid_id+"_t";
            jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
            jQuery("#"+subgrid_table_id).jqGrid({
                  width: 840,
                  url: 'ajax/savByContrat-json_response.php?action=subGrid&id='+row_id,
                  //url:"subgrid.php?q=2&id="+row_id,
                  datatype: "json",
                  colNames: ['Id','Ref','Probl&egrave;me','D&eacute;but','Fin','Statut',"lastMessage"],
                  colModel: [

                    {name:"id",index:"id",width:55,key:true,hidden:true},
                    {name:"ref",index:"ref",width:110,},
                    {name:"descriptif_probleme",index:"descriptif_probleme",width:195},
                    {name:'date_create',index:'date_create', width:140,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                    {name:'date_end',index:'date_end', width:140,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                    {name:"statut",index:"statut",width:100},
                    {name:"lastMessage",index:"lastMessage",width:100},
                    ],
                  height: "100%",
                  rowNum:20,
                  sortname: 'id',
                  sortorder: "desc",
            });
        }

    });
    jQuery('#list1').jqGrid('navGrid','#pager1',{edit:false,add:false,del:false,search:true});

});
</script>
EOF;
llxFooter('$Date: 2008/03/01 01:26:47 $ - $Revision: 1.16 $');

?>
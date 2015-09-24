<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * $Id: index.php,v 1.12 2007/09/15 22:42:19 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/SSLCert/index.php,v $
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
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
require("./pre.inc.php");

if (!$user->rights->SSLCert->lire) { accessforbidden(); }

$langs->load('SSLCerts');
/*
 * Affichage
 */

    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";


    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

    $js .= "<script>jQuery(document).ready(function(){ jQuery('#createSSLCert').validate({
                        rules: { nom:{ required: true},
                                 datec: { FRDate: true , required: true },
                                 datef: { FRDateNotRequired: true  },
                                 dater: { FRDateNotRequired: true  },
                               },
                        messages: { nom: { required: 'Ce champs est requis'},
                                    datec: { required: 'Ce champs est requis'} }
    });
    jQuery('.ui-icon').mouseover(function(){
        jQuery(this).parent().addClass('ui-state-default');
    });
    jQuery('.ui-icon').mouseout(function(){
        jQuery(this).parent().removeClass('ui-state-default');
    });

    jQuery('#modDialog').dialog({
        autoOpen: false,
        width: 560,
        maxWidth: 560,
        minWidth: 560,
        modal: true,
        title: 'Modification du certificat',
        buttons:{
            'OK':function() {
                if (jQuery('#modSSLCert').validate({
                        rules: { nom:{ required: true},
                                 datec: { FRDate: true , required: true },
                                 datef: { FRDateNotRequired: true  },
                                 dater: { FRDateNotRequired: true  },
                               },
                        messages: { nom: { required: 'Ce champs est requis'},
                                    datec: { required: 'Ce champs est requis'} }
                }).form()){
                    var data = jQuery('#modSSLCert').serialize();
                    jQuery.ajax({
                        url: 'index.php',
                        data: 'action=mod&id='+SSLCertid+'&data='+data,
                        datetype: 'xml',
                        type: 'post',
                        success: function(msg){
                            jQuery('#list2').trigger('reloadGrid');
                            jQuery('#modDialog').dialog('close');
                        }
                    });
                }
            },
            'Annuler': function (){
                jQuery('#modDialog').dialog('close');
            }
        },
        open: function(){
            jQuery('#modSSLCert')[0].reset();
            jQuery('#ui-datepicker-div').addClass('promoteZ');
            jQuery.ajax({
                url: 'index.php',
                data: 'action=getData&id='+SSLCertid,
                type: 'POST',
                datatype: 'xml',
                success: function(msg){
                    jQuery('#modSSLCert').find('#nom').val(jQuery(msg).find('label').text());
                    jQuery('#modSSLCert').find('#datec').val(jQuery(msg).find('datec').text());
                    jQuery('#modSSLCert').find('#datef').val(jQuery(msg).find('datef').text());
                    jQuery('#modSSLCert').find('#dater').val(jQuery(msg).find('dater').text());
                    jQuery('#modSSLCert').find('#note').val(jQuery(msg).find('note').text());
                    var active = jQuery(msg).find('active').text();
                    if (active == 1)
                    {
                        jQuery('#modSSLCert').find('#actif').attr('checked','checked');
                    } else {
                        jQuery('#modSSLCert').find('#actif').attr('checked','checked');
                    }
                }
            });
        }

    });
    jQuery('#delDialog').dialog({
        autoOpen: false,
        width: 560,
        maxWidth: 560,
        minWidth: 560,
        modal: true,
        title: 'Suppression du certificat',
        buttons:{
            'OK':function() {
                jQuery.ajax({
                    url: 'index.php',
                    data: 'action=del&id='+SSLCertid,
                    datetype: 'xml',
                    type: 'post',
                    success: function(msg){
                        if (jQuery(msg).find('OK'))
                        {
                            jQuery('#list2').trigger('reloadGrid');
                            jQuery('#delDialog').dialog('close');
                        }
                    }
                });
            },
            'Annuler': function (){
                jQuery('#delDialog').dialog('close');
            }
        }

    });

    jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                        dateFormat: 'dd/mm/yy',
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        buttonImage: 'cal.png',
                        buttonImageOnly: true,
                        showTime: true,
                        duration: '',
                        constrainInput: false,}, jQuery.datepicker.regional['fr']));

    jQuery('#datef').datepicker();
    jQuery('#datec').datepicker();
    jQuery('#dater').datepicker();
    jQuery('#list2').jqGrid({
        url:'index.php?action=list',
        datatype: 'json',

        colNames:['rowid','".$langs->trans("Description")."','".$langs->trans("DateCreate")."', '".$langs->trans("DateFin")."', '".$langs->trans("DateRenew")."', '".$langs->trans("Note")."', '".$langs->trans("Actif")."', '".$langs->trans("Action")."'],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'label',index:'label', width:90, align: 'center'},
                   {name:'datec',index:'datec', width:100,
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
                   {name:'datef',index:'datef', width:80, align:'right',
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

                   {name:'dater',index:'dater', width:80, align:'right',
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
                   {name:'note',index:'note', width:180,align:'left'},
                   {name:'active',index:'active', width:80,align:'center'},
                   {name:'action',index:'action', width:80,align:'center',hidedlg: true, search: false},
                 ],
        rowNum:30,
        rowList:[30,50,100],
        width: 900,
        height: 500,
        pager: '#pager2',
        sortname: 'rowid',
        beforeRequest: function(){
            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
        },
        viewrecords: true,
        sortorder: 'desc',
        caption:'".$langs->trans('SSLCertNames')."' });
        jQuery('#list2').jqGrid('navGrid','#pager2',{edit:true,add:true,del:true,search:true}); });


 jQuery.validator.addMethod(
                            'FRDate',
                            function(value, element) {
                                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                            },
                            'La date doit &ecirc;tre au format dd/mm/yyyy'
                           );
 jQuery.validator.addMethod(
                            'FRDateNotRequired',
                            function(value, element) {
                                if (value+'x' == 'x') return true;
                                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                            },
                            'La date doit &ecirc;tre au format dd/mm/yyyy'
                           );

function deleteSSLCert(pId)
{
    SSLCertid=pId;
    jQuery('#delDialog').dialog('open');
}
function modifySSLCert(pId)
{
    SSLCertid=pId;
    jQuery('#modDialog').dialog('open');
}
var SSLCertid = '';
</script>
<style>
.error { padding-left: 5px; }
</style>
";
if ($_REQUEST['action'] == "mod" && $user->rights->SSLCert->creer)
{
  $nom =  preg_replace('/\'/','\\\'',$_REQUEST['nom']);
  $datec =  parseDate($_REQUEST['datec']);
  $dater =  parseDate($_REQUEST['dater']);
  $datef =  parseDate($_REQUEST['datef']);
  $actif =  ($_REQUEST['actif']."x"!="x"?1:0);
  $note =  preg_replace('/\'/','\\\'',$_REQUEST['note']);


  $SSLCertid = $_REQUEST['id'];
  $requete = "UPDATE Babel_SSLCert
                 SET label='".$nom."',
                     datec=".$datec.",
                     dater=".$dater.",
                     datef=".$datef.",
                     active=".$actif.",
                     note='".$note."'
               WHERE rowid =  ".$SSLCertid;
  $sql = $db->query($requete);
  $xml = "<ajax-response>";
  if ($sql)
  {
        $xml .= "<OK>OK</OK>";
  } else {
        $xml .= "<KO>KO".$requete."</KO>";
  }
  if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
  } else {
        header("Content-type: text/xml;charset=utf-8");
  } $et = ">";
  echo "<?xml version='1.0' encoding='utf-8'?$et\n";
  echo $xml;
  echo "</ajax-response>";
  exit;
}
if ($_REQUEST['action'] == "getData" && $user->rights->SSLCert->lire)
{
    $SSLCertId = $_REQUEST['id'];
    if ($SSLCertId > 0)
    {
        $requete = "SELECT label, note, active,
                           date_format(datec,'%d/%m/%Y') as datec,
                           date_format(datef,'%d/%m/%Y') as datef,
                           date_format(dater,'%d/%m/%Y') as dater
                      FROM Babel_SSLCert WHERE rowid = ".$SSLCertId;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $xml = "<ajax-response>";
        if ($sql)
        {
            $xml .= "<label><![CDATA[".utf8_encode($res->label)."]]></label>";
            $xml .= "<datec><![CDATA[".utf8_encode($res->datec)."]]></datec>";
            $xml .= "<datef><![CDATA[".utf8_encode($res->datef)."]]></datef>";
            $xml .= "<dater><![CDATA[".utf8_encode($res->dater)."]]></dater>";
            $xml .= "<active><![CDATA[".utf8_encode($res->active)."]]></active>";
            $xml .= "<note><![CDATA[".utf8_encode($res->note)."]]></note>";
        } else {
            $xml .= "<KO>KO</KO>";
        }
        if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
             header("Content-type: application/xhtml+xml;charset=utf-8");
        } else {
            header("Content-type: text/xml;charset=utf-8");
        } $et = ">";
        echo "<?xml version='1.0' encoding='utf-8'?$et\n";
        echo $xml;
        echo "</ajax-response>";
        exit;

    }
}

if ($_REQUEST['action'] == 'list'&& $user->rights->SSLCert->lire)
{
    $page = $_GET['page'];
    $limit = $_GET['rows'];
    $sidx = $_GET['sidx'];
    $sord = $_GET['sord'];
    if(!$sidx) $sidx =1;
    $result = $db->query("SELECT COUNT(*) AS count FROM Babel_SSLCert");
    $row = $db->fetch_object($result,MYSQL_ASSOC);
    $count = $row->count;
    if( $count >0 )
    {
        $total_pages = ceil($count/$limit);
    } else {
        $total_pages = 0;
    }
    if ($page > $total_pages)
        $page=$total_pages;
    $start = $limit*$page - $limit;
    if ($start < 0) $start=0;
    // do not put $limit*($page - 1)
    $SQL = "SELECT * FROM Babel_SSLCert  ORDER BY $sidx $sord LIMIT $start , $limit";
    $result = $db->query( $SQL ) or die("Couldn t execute query.".mysql_error() . $SQL);
    $responce->page = $page;
    $responce->total = $total_pages;
    $responce->records = $count;
    $i=0;
    while($row = $db->fetch_object($result,MYSQL_ASSOC))
    {
        $responce->rows[$i]['id']=$row->rowid;
        $responce->rows[$i]['cell']=array($row->rowid,
                                           utf8_encode($row->label),
                                           $row->datec,
                                           $row->datef,
                                           $row->dater,
                                           $row->note,
                                           ($row->active==1?'<img src="'.DOL_URL_ROOT."/theme/".$conf->theme.'/img/tick.png">':'<img src="'.DOL_URL_ROOT."/theme/".$conf->theme.'/img/agt_stop.png">'),
                                           " <center>   <table style='margin-top: -20px;' cellpadding=0><tr><t><td style='background:none repeat scroll 0 0 transparent;border:0 none transparent;max-width:16px;width:16px; height: 16px;max-height:16px;' valign=center><span title='Modifier' style='width: 16px;' class='ui-icon ui-icon-arrowrefresh-1-e' onclick='modifySSLCert(".$row->rowid.")'></span><td style='background:none repeat scroll 0 0 transparent;border:0 none transparent;max-width:16px;width:16px; height: 16px;max-height:16px;' valign=center><span title='Supprimer' style='width: 16px;' class='ui-icon ui-icon-trash' onclick='deleteSSLCert(".$row->rowid.")'></span></table></center>");
        $i++;
    }
        echo json_encode($responce);
        exit();
}

if ($_REQUEST['action'] == 'del' && $user->rights->SSLCert->effacer)
{
    $xml = "<ajax-response>";
    $id = $_REQUEST['id'];
    $requete = "DELETE FROM Babel_SSLCert WHERE rowid = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<OK>OK</OK>";
    } else {
        $xml .= "<KO>KO</KO>";
    }
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo $xml;
    echo "</ajax-response>";
exit;
}

    llxHeader($js,'SSLCertes',"",1);
    if (($_REQUEST['action']=='add') && $_REQUEST['cancel']!= 1&& $user->rights->SSLCert->creer)
    {
        $datec = parseDate($_REQUEST['datec']);
        $datef = parseDate($_REQUEST['datef']);
        $dater = parseDate($_REQUEST['dater']);
        $requete = "INSERT INTO `Babel_SSLCert`
                                (`datec`,`datef`,`dater`,`label`,`note`,`active`)
                        VALUES
                                ( ".$datec.",".$datef." ,".$dater." , '".preg_replace('/\'/','\\\'',$_REQUEST['nom'])."', '".preg_replace('/\'/','\\\'',$_REQUEST['note'])."', ".($_REQUEST['actif']."x"!='x'?1:0).");";
        $sql = $db->query($requete);
    }
    if ($_REQUEST['action']=="create" || $_REQUEST['addNew']== 1 && $user->rights->SSLCert->creer)
    {
          print "<br/><div class='titre'>".$langs->trans("NewSSLCert")."</div><br/>";
          print "<form method='POST' action='index.php' id='createSSLCert'>";
          print "<input type='hidden'/ name='action' value='add'> ";
          print "<table  id='grid' width='100%' cellpadding='15'><thead>";
          print "<tr><th class='ui-widget-header ui-state-default'>Nom</th>";
          print "    <td class='ui-widget-content'><input type='text' id='nom' name='nom' /></td>";
          print "<tr><th class='ui-widget-header ui-state-default'>Date achat</th>";
          print "    <td class='ui-widget-content'><input type='text' id='datec' name='datec' /></td>";
          print "<tr><th class='ui-widget-header ui-state-default'>Dernier renouvellement</th>";
          print "    <td class='ui-widget-content'><input type='text' id='dater' name='dater' /></td>";
          print "<tr><th class='ui-widget-header ui-state-default'>Date de fin</th>";
          print "    <td class='ui-widget-content'><input type='text' id='datef' name='datef' /></td>";
          print "<tr><th class='ui-widget-header ui-state-default'>Actif ?</th>";
          print "    <td class='ui-widget-content'><input type='checkbox'/ id='actif' name='actif'></td>";
          print "<tr><th class='ui-widget-header ui-state-default'>Note</th>";
          print "    <td class='ui-widget-content'><textarea name='note' id='note'></textarea></td>";
          print "<tr><td align=center colspan=2 class='ui-widget-content'><button class='butAction ui-state-default ui-widget-header ui-corner-all' name='addStop' value='1' style='padding: 5px 10px;'>Ajouter</button>
                                                                          <button class='butAction ui-state-default ui-widget-header ui-corner-all' name='addNew' value='1' style='padding: 5px 10px;'>Ajouter et Creer nouveau</button>
                                                                          <button class='butAction ui-state-default ui-widget-header ui-corner-all' name='cancel' value='1' style='padding: 5px 10px;'>Annuler</button>";
          print "</thead></table>";
          print "</form>";
          exit;
    }

//
        print "<br>";
        print "<div class='titre'>".$langs->trans("SSLCertNames")."</div>";
        print "<br>";

    print '<table id="list2"></table> <div id="pager2"></div>';


        print "<br/>";
        if ($user->rights->SSLCert->creer)
        {
            print "<hr/>";
            print "<div id='tabBar' style='float: right;'>";
            print "<form action='index.php?action=create' method='POST'>
                        <button style='padding: 5px 10px' class='ui-widget-header ui-state-default ui-corner-all butAction' >".$langs->trans('NewSSLCert')."</button></form>";
            print "</div>";
        }


function parseDate($date)
{
    if(preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$date,$arr))
    {
        return( "'".date('Y-m-d',mktime(0, 0, 0, $arr[2],$arr[1], $arr[3]))) ."'";
    } else {
        return "NULL";
    }
}

//dialog

print "<div id='delDialog'>&Ecirc;tes vous sur de vouloir supprimer ce certificat ?</div>";

print "<div id='modDialog'>";
      print "<form method='POST' action='index.php' id='modSSLCert'>";
      print "<input type='hidden'/ name='action' value='mod'> ";
      print "<table width='100%' cellpadding='15'>";
      print "<tr><th class='ui-widget-header ui-state-default'>Nom</th>";
      print "    <td class='ui-widget-content'><input type='text' id='nom' name='nom' /></td>";
      print "<tr><th class='ui-widget-header ui-state-default'>Date achat</th>";
      print "    <td class='ui-widget-content'><input type='text' id='datec' name='datec' /></td>";
      print "<tr><th class='ui-widget-header ui-state-default'>Dernier renouvellement</th>";
      print "    <td class='ui-widget-content'><input type='text' id='dater' name='dater' /></td>";
      print "<tr><th class='ui-widget-header ui-state-default'>Date de fin</th>";
      print "    <td class='ui-widget-content'><input type='text' id='datef' name='datef' /></td>";
      print "<tr><th class='ui-widget-header ui-state-default'>Actif ?</th>";
      print "    <td class='ui-widget-content'><input type='checkbox'/ id='actif' name='actif'></td>";
      print "<tr><th class='ui-widget-header ui-state-default'>Note</th>";
      print "    <td class='ui-widget-content'><textarea name='note' id='note'></textarea></td>";
      print "</table>";

print "</div>";

$db->close();

llxFooter('$Date: 2007/09/15 22:42:19 $ - $Revision: 1.12 $');
?>

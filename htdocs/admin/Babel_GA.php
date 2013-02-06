<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 14 mar. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Babel_GA.php
  */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("synopsisGene@Synopsis_Tools");

if (!$user->admin || !$conf->global->MAIN_MODULE_BABELGA)
  accessforbidden();

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js .= ' <script src="'.$jqueryuipath.'/ui.slider.js" type="text/javascript"></script>';
$js .="<style> input { text-align: center; }
#progressTxA  { width: 100%; height: 10px;}
  </style>";

$js .= " <script>";
$js .= <<< EOF

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


jQuery(document).ready(function(){

    jQuery("#tabs").tabs();

    jQuery.validator.addMethod(
                'currency',
                function(value, element) {
                    return value.match(/^\d*?[,.]?\d*?$/);
                },
                'La montant n\'est pas au bon format'
            );
    jQuery.validator.addMethod(
                'sup1',
                function(value, element) {
                    return parseFloat(value) > 0;
                },
                'Valeur incorrecte'
            );
    jQuery.validator.addMethod(
                'MinMax',
                function(value, element) {
                    return (parseFloat(jQuery("#maxFinancement").val()) > parseFloat(jQuery('#minFinancement').val()) );
                },
                'Le montant minimum ne peut &ecirc;tre sup&egrave;rieur au montant maximum'
            );

    jQuery('#stockloc').change(function()
    {
        jQuery.ajax({
            url: "Babel_GA.php",
            data: "action=saveDataStockLoc&stockId="+jQuery(this).find(':selected').val(),
            datatype: 'html',
            success: function(msg){
                //alert(msg);
                location.reload();
            }
            });
    });


    jQuery('#addDefPerDialogBut').click(function(){
        var dateTmp=new Date();
        dateTmp1 = dateTmp.getTime();
        var html ="<tr>\
                     <td class='ui-widget-content'><input  class='required' name='defPeriodSimpleDesc-"+dateTmp1+"' id='defPeriodSimpleDesc-"+dateTmp1+"' value=''>\
                     <td class='ui-widget-content'><input  class='required' name='defPeriodSimpleDesc2-"+dateTmp1+"' id='defPeriodSimpleDesc2-"+dateTmp1+"' value=''>\
                     <td class='ui-widget-content'><input  class='required sup1' name='defPeriodSimpleCnt-"+dateTmp1+"' id='defPeriodSimpleCnt-"+dateTmp1+"' value=''>\
                     <td class='ui-widget-content'><span class='ui-icon ui-icon-trash'></span></td>\
                   </tr>";
        jQuery('#tbodyDefPerDialog').append(html);
        jQuery('#tbodyDefPerDialog .ui-icon-trash').click(function(){
            jQuery(this).parent().parent().remove();
        });
    });
    jQuery('#tbodyDefPerDialog .ui-icon-trash').click(function(){
        jQuery(this).parent().parent().remove();
    });

    jQuery('#dfltaddTxMarge').click(function(){
        jQuery('#addTxMargeDialog').dialog('open');
    });

    jQuery('#changePeriod').click(function(){
       jQuery(this).effect("highlight", {}, "fast");
       //dialog edition
       jQuery('#periodDialog').dialog('open');
        return (false);
    });
    jQuery('button.ui-widget-header').mouseover(function(){
        jQuery(this).addClass('ui-state-hover');
    });
    jQuery('button.ui-widget-header').mouseout(function(){
        jQuery(this).removeClass('ui-state-hover');
    });
    jQuery('#addPerSimpleDialogBut').click(function(){
        var dateTmp=new Date();
        dateTmp1 = dateTmp.getTime();

        var html = "<tr>\
                      <td  align=center class='ui-widget-content'><select  class='required' name='typePeriodSimple-"+dateTmp1+"' id='typePeriodSimple-"+dateTmp1+"'>";
EOF;
        $requete = "SELECT * FROM Babel_financement_period WHERE active=1";
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            $js.= "html += '<option value=\'".$res->id."\'>".$res->Description."</option>';";
        }
        $js.= <<<EOF
            html += "</select></td> \
                    <td align=center class='ui-widget-content'><input size=10 class='required sup1' name='durPeriodSimple-"+dateTmp1+"' id='durPeriodSimple-"+dateTmp1+"'></td> \
                    <td align=center class='ui-widget-content'><input type=checkbox name='echPeriodSimple-"+dateTmp1+"' id='echPeriodSimple-"+dateTmp1+"'></td>\
                   <td align=center class='ui-widget-content'><span class='ui-icon ui-icon-trash'></span></td>";
        jQuery('#tbodyPerSimpleDialog').append(html);
        jQuery('#tbodyPerSimpleDialog').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });
        jQuery('#tbodyPerSimpleDialog .ui-icon-trash').click(function(){
            jQuery(this).parent().parent().remove();
        });

    });
    jQuery('#tbodyPerSimpleDialog .ui-icon-trash').click(function(){
        jQuery(this).parent().parent().remove();
    });

    jQuery('#changePeriodFin').click(function(){
        jQuery('#periodFinDialog').dialog('open');
        return (false);
    });
    jQuery('#periodFinDialog').dialog({
        autoOpen: false,
        width: 560,
        maxWidth: 560,
        minWidth: 560,
        modal: true,
        title: "D&eacute;finition des p&eacute;riodes",
        buttons:{
            "OK":function() {
                if (jQuery('#periodFinForm').validate().form())
                {
                    var data = jQuery('#periodFinForm').serialize();


                    jQuery.ajax({
                        url: "Babel_GA.php",
                        data: "action=saveDataDefPer&"+data,
                        datatype: 'html',
                        success: function(msg){
                            //alert(msg);
                            location.reload();
                        }
                    });
                }
            },
            "Annuler": function (){
                jQuery(this).dialog('close');
            }
        }

    });
    jQuery('#periodDialog').dialog({
        autoOpen: false,
        width: 560,
        maxWidth: 560,
        minWidth: 560,
        modal: true,
        title: 'Les p&eacute;riodes simples',
        buttons:{
            "OK":function() {
//
              if (jQuery('#periodSimpleForm').validate().form())
                {
                    var data = jQuery('#periodSimpleForm').serialize();
                    jQuery.ajax({
                        url: "Babel_GA.php",
                        data: "action=saveDataPerSimple&"+data,
                        datatype: 'html',
                        success: function(msg){
//                            alert(msg);
                            location.reload();
                        }
                    });
                }
            },
            "Annuler": function (){
                jQuery(this).dialog('close');
            }
        }
    });
    jQuery('#changeTauxFinDefault').click(function(){
        jQuery('#addLigneFinDialog').dialog('open');
        return(false);
    });

    jQuery('#CONTRATVALIDATE_CREATE_FOURN_FACT').click(function(){
        var data = "";
        var id = jQuery(this).attr('id') ;
        var statut = false;
        if(jQuery(this).attr('checked')==true)
        {
            statut = 1;
        } else {
            statut = 0;
        }
        jQuery.ajax({
            url: "Babel_GA.php",
            datatype: 'html',
            data: "action=saveDataFOURN&statut="+statut+"&part="+id,
            success: function(msg){
                jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                jQuery('#showLog').css('display',"inline");
            }
        });
    });
    jQuery('#CONTRATVALIDATE_CREATE_FACT').click(function(){
        var data = "";
        var id = jQuery(this).attr('id') ;
        var statut = false;
        if(jQuery(this).attr('checked')==true)
        {
            statut = 1;
        } else {
            statut = 0;
        }
        jQuery.ajax({
            url: "Babel_GA.php",
            datatype: 'html',
            data: "action=saveDataFOURN&statut="+statut+"&part="+id,
            success: function(msg){
                jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                jQuery('#showLog').css('display',"inline");
            }
        });
    });
    jQuery('#BABELGA_LIMREG_FACTURE').change(function(){
        var data = "";
        var id = jQuery(this).attr('id') ;
        var statut = false;
            statut = jQuery(this).find(':selected').val();
        jQuery.ajax({
            url: "Babel_GA.php",
            datatype: 'html',
            data: "action=saveDataFACT&statut="+statut+"&part="+id,
            success: function(msg){
                jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                jQuery('#showLog').css('display',"inline");
            }
        });
    });


    jQuery('#CONTRATVALIDATE_CREATE_FOURN_COMM').click(function(){
        var data = "";
        var id = jQuery(this).attr('id') ;
        var statut = false;
        if(jQuery(this).attr('checked')==true)
        {
            statut = 1;
        } else {
            statut = 0;
        }
        jQuery.ajax({
            url: "Babel_GA.php",
            datatype: 'html',
            data: "action=saveDataFOURN&statut="+statut+"&part="+id,
            success: function(msg){
                jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                jQuery('#showLog').css('display',"inline");
            }
        });
    });

    jQuery('#CONTRATGAVALIDATE_ENTER_STOCK').click(function(){
        var data = "";
        var id = jQuery(this).attr('id') ;
        var statut = false;
        if(jQuery(this).attr('checked')==true)
        {
            statut = 1;
        } else {
            statut = 0;
        }
        jQuery.ajax({
            url: "Babel_GA.php",
            datatype: 'html',
            data: "action=saveDataSTOCK&statut="+statut+"&part="+id,
            success: function(msg){
                jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                jQuery('#showLog').css('display',"inline");
            }
        });
    });

    jQuery('#medium').find('input').click(function(){
        //alert (jQuery(this).attr('id'));
        var id = jQuery(this).attr('id') ;
        var statut = false;
        if(jQuery(this).attr('checked')==true)
        {
            statut = 1;
        } else {
            statut = 0;
        }
        var data = "";
        jQuery.ajax({
            url: "Babel_GA.php",
            datatype: 'html',
            data: "action=saveDataMedium&statut="+statut+"&part="+id,
            success: function(msg){
                jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                jQuery('#showLog').css('display',"inline");
            }
        });
    });
    jQuery('#maxFinancement').blur(function(){
        if (jQuery('#maxFinForm').validate().form())
        {
            jQuery.ajax({
                url: "Babel_GA.php",
                datatype: 'html',
                data: "action=saveDataMaxFin&MaxFin="+jQuery(this).val(),
                success: function(msg){
                    jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                    jQuery('#showLog').css('display',"inline");
                    location.reload();

                }//end success
            }); // end ajax
        } // end validate
    }); // end blur
    jQuery('#minFinancement').blur(function(){

        if (jQuery('#minFinForm').validate().form())
        {
            jQuery.ajax({
                url: "Babel_GA.php",
                datatype: 'html',
                data: "action=saveDataMinFin&MinFin="+jQuery(this).val(),
                success: function(msg){
                    jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                    jQuery('#showLog').css('display',"inline");
                    location.reload();
                }//end success
            }); // end ajax
        } // end validate
    });

    jQuery('#progressTxA').slider({
        animate: true,
        range: true,
        max: 30,
        min: 0,
        step: 0.01,
        tooltips: function(t){ return (Math.round(t*100)/100 + " %") },
        change: function(event, ui){
            var val1 = ui.values[0];
            var val2 = ui.values[1];
            if(val1<val2)
            {
                jQuery('#txMinTxt').html(val1);
                jQuery('#txMaxTxt').html(val2);
                jQuery('#tauxmin').val(val1);
                jQuery('#tauxmax').val(val2);

            } else {
                jQuery('#txMinTxt').html(val2);
                jQuery('#txMaxTxt').html(val1);
                jQuery('#tauxmin').val(val2);
                jQuery('#tauxmax').val(val1);
            }
            tauxmin = jQuery('#tauxmin').val();
            tauxmax = jQuery('#tauxmax').val();
            tauxVmin = jQuery('#tauxVmin').val();
            tauxVmax = jQuery('#tauxVmax').val();
            jQuery.ajax({
                url: "Babel_GA.php",
                datatype: 'html',
                data: "action=saveData&tauxmin="+tauxmin+'&tauxmax='+tauxmax+"&tauxvmin="+tauxVmin+"&tauxvmax="+tauxVmax,
                success: function(msg){
                    jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>');
                    jQuery('#showLog').css('display',"inline");
                }
            })

        },
        slide: function(event, ui)
        {
            var val1 = ui.values[0];
            var val2 = ui.values[1];
            if(val1<val2)
            {
                jQuery('#txMinTxt').html(val1);
                jQuery('#txMaxTxt').html(val2);
            } else {
                jQuery('#txMinTxt').html(val2);
                jQuery('#txMaxTxt').html(val1);
            }
        },
EOF;
    $js .= '    values: ['.($conf->global->TxAchatMin.'x'=='x'?0:$conf->global->TxAchatMin).','.($conf->global->TxAchatMax.'x'=='x'?0:$conf->global->TxAchatMax).']';
    $js .= <<< EOF
    });

    jQuery('#progressTxB').slider({
    animate: true,
    range: true,
    max: 30,
    min: 0,
    step: 0.01,
        tooltips: function(t){ return (Math.round(t*100)/100 + " %") },
    change: function(event, ui){
        var val1 = ui.values[0];
        var val2 = ui.values[1];
        if(val1<val2)
        {
            jQuery('#txVMinTxt').html(val1);
            jQuery('#txVMaxTxt').html(val2);
            jQuery('#tauxVmin').val(val1);
            jQuery('#tauxVmax').val(val2);

        } else {
            jQuery('#txVMinTxt').html(val2);
            jQuery('#txVMaxTxt').html(val1);
            jQuery('#tauxVmin').val(val2);
            jQuery('#tauxVmax').val(val1);
        }
        tauxmin = jQuery('#tauxmin').val();
        tauxmax = jQuery('#tauxmax').val();
        tauxVmin = jQuery('#tauxVmin').val();
        tauxVmax = jQuery('#tauxVmax').val();
        jQuery.ajax({
            url: "Babel_GA.php",
            datatype: 'html',
            data: "action=saveData&tauxmin="+tauxmin+'&tauxmax='+tauxmax+"&tauxvmin="+tauxVmin+"&tauxvmax="+tauxVmax,
            success: function(msg){
                jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>')
                jQuery('#showLog').css('display',"inline");
            }
        })

    },
    slide: function(event, ui)
    {
        var val1 = ui.values[0];
        var val2 = ui.values[1];
        if(val1<val2)
        {
            jQuery('#txVMinTxt').html(val1);
            jQuery('#txVMaxTxt').html(val2);
        } else {
            jQuery('#txVMinTxt').html(val2);
            jQuery('#txVMaxTxt').html(val1);
        }
    },
EOF;
    $js .= '    values: ['.($conf->global->TxVenteMin.'x'=='x'?0:$conf->global->TxVenteMin).','.($conf->global->TxVenteMax."x"=="x"?0:$conf->global->TxVenteMax).']';
    $js .= <<< EOF
    });

EOF;
if ($conf->global->MaxFin."x"!="x" && $conf->global->MinFin."x"!="x")
{
$js .= <<< EOF

    jQuery('#progressTxC').slider({
    animate: true,
    range: true,
            tooltips: function(t){ return (Math.round(t*100)/100 + " &euro;") },

EOF;
$js.= "   max: ".$conf->global->MaxFin.",";
$js.= "   min: ".$conf->global->MinFin.",";
$tmp = $conf->global->MaxFin - $conf->global->MinFin;
$tmp /= 100;
if ($tmp>10000)
{
    $tmp = round($tmp/1000)*1000;
} else {
    $tmp = round($tmp/100)*100;
}
$js.= "   step: ".$tmp.",";
$js .= '    values: ['.($conf->global->MinFinSeuil.'x'=='x'?0:$conf->global->MinFinSeuil).','.($conf->global->MaxFinSeuil."x"=="x"?0:$conf->global->MaxFinSeuil).'],';

$js .= <<< EOF
    change: function(event, ui){
        var val1 = ui.values[0];
        var val2 = ui.values[1];
        if(val1<val2)
        {
            jQuery('#MontantFinMinTxt').html(val1);
            jQuery('#MontantFinMaxTxt').html(val2);
            jQuery('#MontantFinMin').val(val1);
            jQuery('#MontantFinMax').val(val2);
        } else {
            jQuery('#MontantFinMinTxt').html(val2);
            jQuery('#MontantFinMaxTxt').html(val1);
            jQuery('#MontantFinMin').val(val2);
            jQuery('#MontantFinMax').val(val1);
        }
        var montantmin = jQuery('#MontantFinMin').val();
        var montantmax = jQuery('#MontantFinMax').val();
        jQuery.ajax({
            url: "Babel_GA.php",
            datatype: 'html',
            data: "action=saveDataMontant&montantmin="+montantmin+"&montantmax="+montantmax,
            success: function(msg){
                jQuery('#log').replaceWith('<div id="log">Modification sauvegard&eacute;e</log>')
                jQuery('#showLog').css('display',"inline");
            }
        })

    },
    slide: function(event, ui)
    {
        var val1 = ui.values[0];
        var val2 = ui.values[1];
        if(val1<val2)
        {
            jQuery('#MontantFinMinTxt').html(val1);
            jQuery('#MontantFinMaxTxt').html(val2);
        } else {
            jQuery('#MontantFinMinTxt').html(val2);
            jQuery('#MontantFinMaxTxt').html(val1);
        }
    },
    });
EOF;
}

$js .= "});";

$js .= "</script>";

if ($_REQUEST['action'] == 'saveDataSTOCK')
{
    $statut = $_REQUEST['statut'];
    dolibarr_set_const($db,$_REQUEST['part'],trim($statut),'',0);
    exit;
}
if ($_REQUEST['action'] == 'saveDataFOURN')
{
    $statut = $_REQUEST['statut'];
    dolibarr_set_const($db,$_REQUEST['part'],trim($statut),'',0);
    exit;
}

if ($_REQUEST['action'] == 'saveDataFACT')
{
    $statut = $_REQUEST['statut'];
    dolibarr_set_const($db,$_REQUEST['part'],trim($statut),'',0);
    exit;
}


if ($_REQUEST['action']=='saveDataMedium')
{
    //
    $statut = $_REQUEST['statut'];
    switch ($_REQUEST['part'])
    {
        case 'mediumMontantTot':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_MONTANTTOT',trim($statut),'',0);
        }
        break;
        case 'mediumDureeSimple':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_DURSIMPLE',trim($statut),'',0);
        }
        break;
        case 'mediumChoixTerme':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_CHOIXTERME',trim($statut),'',0);
        }
        break;
        case 'mediumTxMargeAffiche':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_MARGEAFFICHE',trim($statut),'',0);
        }
        break;
        case 'mediumTxMargeMod':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_MARGEMODIFIER',trim($statut),'',0);
        }
        break;
        case 'mediumTxBankAffiche':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_TXBANKAFFICHE',trim($statut),'',0);
        }
        break;
        case 'mediumTxBankModifie':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_TXBANKMODIFIE',trim($statut),'',0);
        }
        break;

        case 'mediumTxParLigne':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_TXPARLIGNE',trim($statut),'',0);
        }
        break;
        case 'mediumTotAFinAffiche':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_TOTAFFICHE',trim($statut),'',0);
        }
        break;
        case 'mediumTEGAffiche':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_TEG',trim($statut),'',0);
        }
        break;
        case 'mediumTEGPROPAffiche':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_TEGPROP',trim($statut),'',0);
        }
        break;
        case 'mediumTEGACTUAffiche':
        {
            dolibarr_set_const($db,'BABELGA_MEDIUM_TEGACTU',trim($statut),'',0);
        }
        break;

    }
    exit(0);

}
if ($_REQUEST['action'] == 'saveDataPerSimple')
{
    $db->begin();
    $requete = "DELETE FROM Babel_GA_period_simple";
    $sql = $db->query($requete);
    $ar=array();
    if ($sql)
    {
        foreach($_REQUEST as $key=>$val)
        {
            if(preg_match('/^typePeriodSimple-([0-9]*)$/',$key,$arrT)){
                $idVar  =$arrT[1];
                $durTot = $_REQUEST['durPeriodSimple-'.$idVar];
                $ech = ($_REQUEST['echPeriodSimple-'.$idVar]."x"=="x"?1:0);
                $typePer = $val;
                $requete = "INSERT INTO Babel_GA_period_simple (financement_period_refid, duree, echu) VALUES (".$typePer.",".$durTot.",".$ech.")";
                $sql = $db->query($requete);
                if (!$sql) { $db->rollback; print $requete ; exit(0); }
            }
        }
        $db->commit();
    } else {
        $db->rollback();
        print $requete ;
    }
exit(0);
}



if ($_POST["action"] == 'updateMask')
{
    $mask= ($_REQUEST['maskcontratGA']."x" !='x'?$_REQUEST['maskcontratGA']:$_REQUEST['maskpropal']);
    $maskConst = ($_REQUEST['maskconst']."x" != "x"?$_REQUEST['maskconst'] : $_REQUEST['maskconstpropal']);
    if ("x".$maskConst != "x" &&$mask."x" != "x") dolibarr_set_const($db,$maskConst,$mask,'',0);
}

if ($_GET["action"] == 'set')
{
    $type='contratGA';
    if ($_GET['type'].'x' !='x') { $type = $_GET['type']; }

    // On active le modele
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    $result1=$db->query($sql_del);

    $dir = DOL_DOCUMENT_ROOT."/includes/modules/propaleGA/";

    //preg_match('/^pdf_deplacement/',$file) && preg_match('/.modules.php$/',$file)
    //
    $file= 'pdf_contratGA_'.$_GET['value'].'.modules.php';
    if ($_GET['type'].'x' !='x') { $file= 'pdf_propaleGA_'.$_GET['value'].'.modules.php'; }


    require_once($dir.$file);

    $classname = 'pdf_contratGA_'.$_GET['value'];
    if ($_GET['type'].'x' !='x') { $classname = 'pdf_propaleGA_'.$_GET['value']; }

    $module = new $classname($db);
    $libelle =  $module->libelle;


    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type,libelle) VALUES ('".$_GET["value"]."','".$type."','".$libelle."')";
    $result2=$db->query($sql);
    if ($db->query($sql))
    {
        $db->commit();
    } else {
        $db->rollback();
    }


}

if ($_GET["action"] == 'del')
{
    $type='contratGA';
    if ($_GET['type'].'x' !='x') { $type = $_GET['type']; }
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    if ($db->query($sql))
    {

    }
}


if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    if ($_GET['type'].'x' !='x')
    {
        dolibarr_set_const($db, "PROPALEGA_ADDON",$_GET["value"]);
    } else {
        dolibarr_set_const($db, "CONTRATGA_ADDON",$_GET["value"]);
    }
}

  if ($_GET["action"] == 'setdoc')
{
    $db->begin();
    if ($_GET['type'].'x' !='x')
    {
        if (dolibarr_set_const($db, "PROPALEGA_ADDON_PDF",$_GET["value"]))
        {
            $conf->global->PROPALEGA_ADDON_PDF = $_GET["value"];
        }

        // On active le modele
        $type='propalGA';
        $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
        $sql_del .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
        $result1=$db->query($sql_del);

        $dir = DOL_DOCUMENT_ROOT."/includes/modules/propaleGA/";

        //preg_match('/^pdf_deplacement/',$file) && preg_match('/.modules.php$/',$file)
        //
        $file= 'pdf_propaleGA_'.$_GET['value'].'.modules.php';
        require_once($dir.$file);

        $classname = 'pdf_propaleGA_'.$_GET['value'];

        $module = new $classname($db);
        $libelle =  $module->libelle;


        $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type,libelle) VALUES ('".$_GET["value"]."','".$type."','".$libelle."')";
        $result2=$db->query($sql);
        if ($result1 && $result2)
        {
            $db->commit();
        } else {
            $db->rollback();
        }


    } else {
        if (dolibarr_set_const($db, "CONTRATGA_ADDON_PDF",$_GET["value"]))
        {
            $conf->global->CONTRATGA_ADDON_PDF = $_GET["value"];
        }

        // On active le modele
        $type='contratGA';
        $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
        $sql_del .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
        $result1=$db->query($sql_del);

        $dir = DOL_DOCUMENT_ROOT."/includes/modules/contratGA/";

        //preg_match('/^pdf_deplacement/',$file) && preg_match('/.modules.php$/',$file)
        //
        $file= 'pdf_contratGA_'.$_GET['value'].'.modules.php';
        require_once($dir.$file);

        $classname = 'pdf_contratGA_'.$_GET['value'];

        $module = new $classname($db);
        $libelle =  $module->libelle;


        $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type,libelle) VALUES ('".$_GET["value"]."','".$type."','".$libelle."')";
        $result2=$db->query($sql);
        if ($result1 && $result2)
        {
            $db->commit();
        } else {
            $db->rollback();
        }
    }
}



if ($_REQUEST['action'] == 'saveDataStockLoc')
{
    $val = $_REQUEST['stockId'];
    dolibarr_set_const($db,'BABELGA_STOCKLOC',$val,'',0);
    exit(0);
}

if ($_REQUEST['action'] == 'saveDataDefPer')
{
    $ar=array();
    foreach($_REQUEST as $key=>$val)
    {
        if(preg_match('/^([\w]*)-([0-9]*)$/',$key,$arrT)){
            $nameVar =$arrT[1];
            $idVar  =$arrT[2];
            $nbItrVar = $_REQUEST['defPeriodSimpleCnt-'.$idVar];
            $arr[$nbItrVar][$nameVar]=$val;
        }
    }
//update or insert
    $testarr = array();
    foreach($arr as $key=>$val)
    {
        $testarr[]=$key;
        $requete = "SELECT * FROM Babel_financement_period WHERE NbIterAn =".$key;
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0)
        {
            $requete = " UPDATE Babel_financement_period SET Description = '".$val["defPeriodSimpleDesc"]."',
                                                             Description2 = '".$val["defPeriodSimpleDesc2"]."',
                                                             active = 1
                          WHERE NbIterAn = ".$key;
             $sql = $db->query($requete);
        } else {
            $requete = " INSERT INTO Babel_financement_period (Description,
                                                                   Description2,
                                                                   NbIterAn)
                                                          VALUES ( '".$val["defPeriodSimpleDesc"]."' ,
                                                                   '".$val["defPeriodSimpleDesc2"]."' ,
                                                                   ".$key." )";
             $sql = $db->query($requete);
        }
    }
    //set inactive the other
    $test = join(",",$testarr);
    $requete = "SELECT * FROM Babel_financement_period WHERE NbIterAn NOT IN (".$test.")";
    print $requete;
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        $requete1 = "UPDATE Babel_financement_period SET active = 0 WHERE id = ".$res->id;
        $db->query($requete1);
    }

    exit(0);
}
if ($_REQUEST['action'] == 'saveDataMontant')
{
    dolibarr_set_const($db,'MinFinSeuil',trim($_REQUEST["montantmin"]),'',0);
    dolibarr_set_const($db,'MaxFinSeuil',trim($_REQUEST["montantmax"]),'',0);
    exit(0);
}

if ($_REQUEST['action'] == "saveData")
{
    dolibarr_set_const($db,'TxAchatMin',trim($_REQUEST["tauxmin"]),'',0);
    dolibarr_set_const($db,'TxAchatMax',trim($_REQUEST["tauxmax"]),'',0);
    dolibarr_set_const($db,'TxVenteMin',trim($_REQUEST["tauxvmin"]),'',0);
    dolibarr_set_const($db,'TxVenteMax',trim($_REQUEST["tauxvmax"]),'',0);
    exit(0);
}
if ($_REQUEST['action']=="saveDataMaxFin")
{
    dolibarr_set_const($db,'MaxFin',trim($_REQUEST["MaxFin"]),'',0);
    if ($conf->global->MaxFinSeuil."x" == "x" || $conf->global->MaxFinSeuil > $_REQUEST["MaxFin"])
    {
        dolibarr_set_const($db,'MaxFinSeuil',trim($_REQUEST["MaxFin"]),'',0);
    }
    if ($conf->global->MinFin > $conf->global->MaxFin)
    {
        dolibarr_set_const($db,'MinFin',trim($_REQUEST["MaxFin"]),'',0);
        if ($conf->global->MinFinSeuil."x" == "x" || $conf->global->MinFinSeuil < $conf->global->MinFin)
        {
            dolibarr_set_const($db,'MinFinSeuil',$conf->global->MinFin,'',0);
        }
    }
    exit(0);
}
if ($_REQUEST['action']=="saveDataMinFin")
{
    dolibarr_set_const($db,'MinFin',trim($_REQUEST["MinFin"]),'',0);
    if ($conf->global->MinFinSeuil."x" == "x" || $conf->global->MinFinSeuil < $_REQUEST["MinFin"])
    {
        dolibarr_set_const($db,'MinFinSeuil',trim($_REQUEST["MinFin"]),'',0);
    }
    if ($conf->global->MinFin > $conf->global->MaxFin)
    {
        dolibarr_set_const($db,'MaxFin',trim($_REQUEST["MinFin"]),'',0);
        if ($conf->global->MaxFinSeuil."x" == "x" || $conf->global->MaxFinSeuil > $conf->global->MaxFin)
        {
            dolibarr_set_const($db,'MaxFinSeuil',$conf->global->MaxFin,'',0);
        }
    }

    exit(0);
}
if ($conf->global->TxAchatMin > $conf->global->TxAchatMax)
{
    $tmp1= $conf->global->TxAchatMax;
    $tmp2= $conf->global->TxAchatMin;
    dolibarr_set_const($db,'TxAchatMin',$tmp1,'',0);
    dolibarr_set_const($db,'TxAchatMax',$tmp2,'',0);
}
if ($conf->global->TxVenteMin > $conf->global->TxVenteMax)
{
    $tmp1= $conf->global->TxVenteMax;
    $tmp2= $conf->global->TxVenteMin;
    dolibarr_set_const($db,'TxVenteMin',$tmp1,'',0);
    dolibarr_set_const($db,'TxVenteMax',$tmp2,'',0);
}

dolibarr_set_const($db,'PROPALEGA_USE_CUSTOMER_CONTACT_AS_RECIPIENT',1,'',0);

llxHeader($js,$langs->trans("Gestion d'actif"),"",1);
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("Gestion d'actif"),$linkback,'setup');




print "<div id='showLog' style='display:none' class='ui-state-default ui-class-highlight'><span class='ui-icon ui-icon-info' style='float: left;'></span><span style='float: left'><div id='log' style='display:none;'></div></span></div>";
print "<br/>";


//Tabs
print '<div id="tabs">';
print '    <ul>';
print '        <li><a href="#fragment-1"><span>Financement</span></a></li>';
print '        <li><a href="#fragment-2"><span>P&eacute;riode</span></a></li>';
print '        <li><a href="#fragment-3"><span>Mode medium</span></a></li>';
print '        <li><a href="#fragment-4"><span>Stock</span></a></li>';
print '        <li><a href="#fragment-5"><span>Editions</span></a></li>';
print '        <li><a href="#fragment-6"><span>Processus</span></a></li>';
print '    </ul>';
print '    <div id="fragment-1">';


//Conf de la fourchette de taux de vente disponible +seuil par user
print "   <table width=100% style='border-collapse: collapse;' cellpadding=15>";
$txMin = $conf->global->TxAchatMin."x"!="x"?$conf->global->TxAchatMin:0;
$txMax = $conf->global->TxAchatMax."x"!="x"?$conf->global->TxAchatMax:0;
$txVMin = $conf->global->TxVenteMin."x"!="x"?$conf->global->TxVenteMin:0;
$txVMax = $conf->global->TxVenteMax."x"!="x"?$conf->global->TxVenteMax:0;
$MaxFin = $conf->global->MaxFin."x"!="x"?$conf->global->MaxFin:0;
$MinFin = $conf->global->MinFin."x"!="x"?$conf->global->MinFin:0;

//print "<tr><th class='ui-widget-header ui-state-default'>Taux d'achat minimum</th><td  class='ui-widget-content'><input id='tauxmin' name='tauxmin' value='". $txMin ."'>%</tr>";
//print "<tr><th class='ui-widget-header ui-state-default'>Taux d'achat maximum</th><td class='ui-widget-content'><input id='tauxmax' name='tauxmax' value='".  $txMax ."'>%</tr>";
//
//print "<tr><th class='ui-widget-header ui-state-default'>Taux de vente minimum</th><td  class='ui-widget-content'><input id='tauxVmin' name='tauxVmin' value='".$txVMin ."'>%</tr>";
//print "<tr><th class='ui-widget-header ui-state-default'>Taux de vente maximum</th><td class='ui-widget-content'><input id='tauxVmax' name='tauxVmax' value='". $txVMax ."'>%</tr>";

print "<tr><th rowspan=2 width=200 class='ui-widget-header ui-state-default'>Taux d'achat permis</th>
           <td  class='ui-widget-content ui-state-focus'>";
print "<div id='progressTxA'></div><input type='hidden' id='tauxmin' name='tauxmin' value='". $txMin ."'><input type='hidden' id='tauxmax' name='tauxmax' value='". $txMax ."'>";
print "</tr>";
print "<tr><td  class='ui-widget-content'>Entre <span id='txMinTxt'>".$txMin."</span>% et <span id='txMaxTxt'>".$txMax."</span>&nbsp;%</tr>";
print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=2></td></tr>";
print "<tr><th rowspan=2 class='ui-widget-header ui-state-default'>Taux de vente permis</th>
           <td   class='ui-widget-content ui-state-focus'><div id='progressTxB'></div><input type='hidden' id='tauxVmin' name='tauxVmin' value='". $txVMin ."'><input type='hidden' id='tauxVmax' name='tauxVmax' value='". $txVMax ."'></tr>";
print "<tr><td  class='ui-widget-content'>Entre <span id='txVMinTxt'>".$txVMin."</span>% et <span id='txVMaxTxt'>".$txVMax."</span>&nbsp;%</tr>";
print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=2></td></tr>";

print "<tr><th class='ui-widget-header ui-state-default'>Montant maximum du financement</th><td  class='ui-widget-content'>";
print "<form id='maxFinForm' action='".$_SERVER['PHP_SELF']."?action=saveData' onsubmit='return(false);' method='POST'>";
print "<input id='maxFinancement' class='required currency MinMax' name='maxFinancement' value='".$MaxFin ."'>&nbsp;&euro;";
print "</form>";
print "</tr>";
print "<tr><th class='ui-widget-header ui-state-default'>Montant minimum du financement</th><td  class='ui-widget-content'>";
print "<form id='minFinForm'  action='".$_SERVER['PHP_SELF']."?action=saveData' onsubmit='return(false);' method='POST'>";
print "<input id='minFinancement' class='required currency MinMax'  name='minFinancement' value='".$MinFin ."'>&nbsp;&euro;";
print "</tr>";
print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=2></td></tr>";
if ($conf->global->MaxFin."x"!="x" && $conf->global->MinFin."x"!="x")
{
    $MaxFinSeuil = $conf->global->MaxFinSeuil."x"!="x"?$conf->global->MaxFinSeuil:0;
    $MinFinSeuil = $conf->global->MinFinSeuil."x"!="x"?$conf->global->MinFinSeuil:0;

    print "<tr><th rowspan=2 class='ui-widget-header ui-state-default'>Seuils de validation</th>
               <td  class='ui-widget-content ui-state-focus'><div id='progressTxC'></div><input type='hidden' id='MontantFinMin' name='MontantFinMin' value='". $MinFinSeuil ."'><input type='hidden' id='MontantFinMax' name='MontantFinMax' value='". $MaxFinSeuilx ."'></tr>";
    print "<tr><td  class='ui-widget-content ui-state-highligh'>Entre <span id='MontantFinMinTxt'>".$MinFinSeuil."</span>&nbsp;&euro; et <span id='MontantFinMaxTxt'>".$MaxFinSeuil."</span>&nbsp;&euro;</tr>";
}
print "</form>";

//Ajout des taux par défault (class) montant % taux
print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=2></td></tr>";
print "<tr>";
print '<td colspan=2 class="ui-widget-header ui-state-default">Taux de financement par d&eacute;faut<button style="float: right; margin-right: 15px;" id="changeTauxFinDefault" class="ui-state-default ui-widget-header"><span class="ui-icon ui-icon-newwin" style="float: left"></span><span class="float: left;">Editer les taux</span></button></td>';

//Babel_GA.class
require_once(DOL_DOCUMENT_ROOT."/Babel_GA/BabelGA.class.php");

print "<tr><td colspan=3 class='ui-widget-content'>";
//$form->
$bga = new BabelGA($db);
$bga->fetch_taux(-1,'dflt');
$bga->drawFinanceTable();
print "</tr>";
print "</tr>";


//Ajout des taux de marge par défault (class) montant % taux
print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=2></td></tr>";
print "<tr>";
print '<td colspan=2 class="ui-widget-header ui-state-default">Taux de marge par d&eacute;faut<button style="float: right; margin-right: 15px;" id="dfltaddTxMarge" class="ui-state-default ui-widget-header"><span class="ui-icon ui-icon-newwin" style="float: left"></span><span class="float: left;">Editer les taux</span></button></td>';

//Babel_GA.class
require_once(DOL_DOCUMENT_ROOT."/Babel_GA/BabelGA.class.php");

print "<tr><td colspan=3 class='ui-widget-content'>";
print $bga->drawMargeFinTable();

print "</tr>";
print "</tr>";


print "</table>";

print '    </div>';

print '    <div id="fragment-2">';
print "   <table width=100% style='border-collapse: collapse;' cellpadding=15>";

//Ajout suppression de durée / financement_period
$requete = "SELECT * FROM Babel_financement_period  WHERE active=1";
$sql = $db->query($requete);
$iter=0;
print "<tr>";
print '<td colspan=2 class="ui-widget-header ui-state-default">P&eacute;riode de financement<button style="float: right; margin-right: 15px;" id="changePeriodFin" class="ui-state-default ui-widget-header"><span class="ui-icon ui-icon-newwin" style="float: left"></span><span class="float: left;">Editer les p&eacute;riodes</span></button></td>';
print "<tr>";
print "<td colspan=2 class='ui-widget-content'><table  cellpadding=15 width=100%>";
    print "<tr>";
    print '<th class="ui-widget-header ui-state-default">&nbsp;</td>
           <th class="ui-widget-header ui-state-default">D&eacute;nomination p&eacute;riode
           <th class="ui-widget-header ui-state-default">D&eacute;nomination  dur&eacute;e
           <th class="ui-widget-header ui-state-default">Nb Paiement par an</td>';

while ($res = $db->fetch_object($sql))
{
    $iter++;
    print "<tr>";
    print '<td class="ui-widget-header ui-state-default" align=center>P&eacute;riode '.$iter.'</td>
           <td class="ui-widget-content" align=center>'.$res->Description.'
           <td class="ui-widget-content" align=center> '.$res->Description2.'
           <td class="ui-widget-content" align=center> '.$res->NbIterAn.'  </td>';
}
print "</table>";
//periode simple

print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=4>&nbsp;</td></tr>";
print "<tr>";
print '<td colspan=2 class="ui-widget-header ui-state-default">D&eacute;finition des p&eacute;riodes simples
            <button style="float: right; margin-right: 15px;" id="changePeriod" class="ui-state-default ui-widget-header"><span class="ui-icon ui-icon-newwin" style="float: left"></span><span class="float: left;">Editer les p&eacute;riodes</span></button></td>';

$requete = "SELECT *
              FROM Babel_financement_period
             WHERE active=1
          ORDER BY NbIterAn DESC";
$sql = $db->query($requete);
print "<tr><td colspan=2 class='ui-widget-content'>";
print "<table cellpadding=15 width=100%>";
while ($res = $db->fetch_object($sql))
{
    $requete1 = "SELECT *
                   FROM Babel_GA_period_simple
                  WHERE financement_period_refid = ".$res->id. "
               ORDER BY duree ASC";
    //Affiche la periode de financement simple dispo
    $iter++;
    print "<tr>";
    print '<td colspan=1 class="ui-widget-header ui-state-default" align=center>P&eacute;riode '.$res->Description.'  </td>';
    print '<td colspan=1  class="ui-widget-content"><table cellpadding=5 width=100% >';
    $sql1 = $db->query($requete1);
    while ($res1 = $db->fetch_object($sql1))
    {
        $nbiteran = $res->NbIterAn;
        $dureeTot = $res1->duree;
        $anTot = $dureeTot / 12;
        $count = $anTot * $nbiteran;
        $echu = 'Terme &agrave; &eacute;choir';
        if ($res1->echu == 0) $echu = 'A terme &eacute;chu';
        print '<tr><td>'.$count.' '.$res->Description2;
        print '    <td>&nbsp;&nbsp;&nbsp;&nbsp;'.$echu;
        if ($dureeTot == $count)
        {
            print "<td>&nbsp</td>";
        } else {
            print '    <td><small>&nbsp;&nbsp;&nbsp;&nbsp;(Soit : '.$dureeTot.' Mois) </small>';
        }
    }
    print '</table></td></tr>';
}
print "   </table>";
print "   </table>";

print '    </div>';
print '    <div id="fragment-3">';
print "   <table  width=100% style='border-collapse: collapse;' cellpadding=15>";
//Contenu pour medium
print "<tbody id='medium'>";
print "<tr>";
print '<td colspan=2 class="ui-widget-header ui-state-default">Mode medium</td>';
print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_MONTANTTOT."x"!="x"?"checked":"";
print '<td width=30% class="ui-widget-header ui-state-default"> Montant total / detaill&eacute;</td>
       <td class="ui-widget-content"> <input name="mediumMontantTot" '.$tmp.' id="mediumMontantTot" type="checkbox"/></td>';
print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_DURSIMPLE."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Dur&eacute;e simple / detaill&eacute;e</td>
       <td class="ui-widget-content"> <input name="mediumDureeSimple" '.$tmp.' id="mediumDureeSimple" type="checkbox"/></td>';
print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_CHOIXTERME."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Dur&eacute;e detaill&eacute;e avec choix des terme</td>
       <td class="ui-widget-content"> <input name="mediumChoixTerme" '.$tmp.' id="mediumChoixTerme" type="checkbox"/></td>';
print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_MARGEAFFICHE."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Affichage du taux de marge</td>
       <td class="ui-widget-content"> <input name="mediumTxMargeAffiche" '.$tmp.' id="mediumTxMargeAffiche" type="checkbox"/></td>';
print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_MARGEMODIFIER."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Modification du taux de marge</td>
       <td class="ui-widget-content"> <input name="mediumTxMargeMod" '.$tmp.' id="mediumTxMargeMod" type="checkbox"/></td>';
print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_TXBANKAFFICHE."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Affichage du taux bancaire</td>
       <td class="ui-widget-content"> <input name="mediumTxBankAffiche" '.$tmp.' id="mediumTxBankAffiche" type="checkbox"/></td>';
print "<tr>";


//
$tmp = $conf->global->BABELGA_MEDIUM_TXBANKMODIFIE."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Modification du taux bancaire</td>
       <td class="ui-widget-content"> <input name="mediumTxBankModifie" '.$tmp.' id="mediumTxBankModifie" type="checkbox"/></td>';
print "<tr>";

//$tmp = $conf->global->BABELGA_MEDIUM_TXPARLIGNE."x"!="x"?"checked":"";
//print '<td class="ui-widget-header ui-state-default"> Taux par ligne / taux unique </td>
//       <td class="ui-widget-content"> <input name="mediumTxParLigne" '.$tmp.' id="mediumTxParLigne" type="checkbox"/></td>';

print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_TOTAFFICHE."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Affiche le total &agrave; financer </td>
       <td class="ui-widget-content"> <input name="mediumTotAFinAffiche" '.$tmp.' id="mediumTotAFinAffiche" type="checkbox"/></td>';

print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_TEG."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Affiche le T.E.G. </td>
       <td class="ui-widget-content"> <input name="mediumTEGAffiche" '.$tmp.' id="mediumTEGAffiche" type="checkbox"/></td>';

print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_TEGPROP."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Affiche le TEG proportionnel </td>
       <td class="ui-widget-content"> <input name="mediumTEGPROPAffiche" '.$tmp.' id="mediumTEGPROPAffiche" type="checkbox"/></td>';

print "<tr>";
$tmp = $conf->global->BABELGA_MEDIUM_TEGACTU."x"!="x"?"checked":"";
print '<td class="ui-widget-header ui-state-default"> Affiche le T.E.G. actualis&eacute; </td>
       <td class="ui-widget-content"> <input name="mediumTEGACTUAffiche" '.$tmp.' id="mediumTEGACTUAffiche" type="checkbox"/></td>';

print "</table>";

print '    </div>';

print '    <div id="fragment-4">';
print "   <table width=100% style='border-collapse: collapse;' cellpadding=15>";
print "<tbody id='stock'>";
print "<tr>";
print '<td colspan=2 class="ui-widget-header ui-state-default">Stock de location</td>';
print "<tr>";
$tmp = $conf->global->BABELGA_STOCKLOC."x"!="x"?$conf->global->BABELGA_STOCKLOC:-1;
print '<td class="ui-widget-header ui-state-default"> Entrepot par d&eacute;faut pour le stock de location</td>
       <td class="ui-widget-content"> ';
$requete = "SELECT * FROM ".MAIN_DB_PREFIX."entrepot";
$sql = $db->query($requete);
if ($db->num_rows($sql) > 0)
{
    print "<SELECT id='stockloc' name='stockloc'>";
    print "<option value='-1'>S&eacute;l&eacute;tionner -></option>";
    while ($res = $db->fetch_object($sql))
    {
        if ($res->rowid == $conf->global->BABELGA_STOCKLOC)
        {
            print "<option SELECTED value='".$res->rowid."'>".$res->label . " ".$res->lieu."</option>";
        } else {
            print "<option value='".$res->rowid."'>".$res->label . " ".$res->lieu."</option>";
        }
    }
    print "</SELECT>";
} else {
    print "<div class='ui-state-error error'>Merci de cr&eacute;er un stock</div>";
}
print '</td>';
print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=2></td></tr>";

$tmp = $conf->global->CONTRATGAVALIDATE_ENTER_STOCK."x"!="x"?"checked":"";
print '<td width=30% class="ui-widget-header ui-state-default">Alimentation automatique des stocks</td>
       <td class="ui-widget-content"> <input name="CONTRATGAVALIDATE_ENTER_STOCK" '.$tmp.' id="CONTRATGAVALIDATE_ENTER_STOCK" type="checkbox"/></td>';

print "</tbody>";
print "</table>";




print '    </div>';
print '    <div id="fragment-5">';

/*
 * Modeles de documents
 */

print_titre($langs->trans("Mod&egrave;le de proposition"));

// Defini tableau def de modele deplacement
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'propalGA'";

$resql=$db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows=$db->num_rows($resql);
    while ($i < $num_rows)
    {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
} else {
    dol_print_error($db);
}


print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=2></td></tr>";
print "<tr class=\"liste_titre\">\n";
print "  <td width=\"140\">".$langs->trans("id")."</td>\n";
print "  <td width=\"140\">".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();


//Propale GA
$dir = DOL_DOCUMENT_ROOT."/includes/modules/propaleGA/";
$handle=opendir($dir);
$html=new Form($db);

//    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."document_model WHERE "

$var=true;
while (($file = readdir($handle))!==false)
{
    //preg_match('/^pdf_deplacement/',$file) && preg_match('/.modules.php$/',$file)
    //
    if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,14) == 'pdf_propaleGA_')
    {
        $name = substr($file, 14, strlen($file) - 26);//babel
        $classname = substr($file, 0, strlen($file) -12);//pdf_deplacement_babel

        $var=!$var;
        print "<tr ".$bc[$var].">\n  <td>";
        print "$name";
        print "</td>\n  <td>\n";
        require_once($dir.$file);
        $module = new $classname($db);
        print $module->libelle;
        print '</td><td>';
        print $module->description;
        print '</td>';

        // Active
        if (in_array($name, $def))
        {
            print "<td align=\"center\">\n";
            if ($conf->global->PROPALEGA_ADDON_PDF != "$name")
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;type=propalGA&amp;value='.$name.'">';
                print img_tick($langs->trans("Disable"));
                print '</a>';
            } else {
                print img_tick($langs->trans("Enabled"));
            }
            print "</td>";
        } else {
            print "<td align=\"center\">\n";
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;type=propalGA&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
            print "</td>";
        }

        // Defaut
        print "<td align=\"center\">";
        if ($conf->global->PROPALEGA_ADDON_PDF == "$name")
        {
            print img_tick($langs->trans("Default"));
        }
        else
        {
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;type=propalGA&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
        }
        print '</td>';

        // Info
        $htmltooltip ='<b align="center">Affichage standard PDF</b> ';



        print '<td align="center">';
        print $html->textwithtooltip('',$htmltooltip,1,0);
        print '</td>';

        print "</tr>\n";
    }
}
closedir($handle);

print '</table>';
print '<br>';

/*
 *  Module numerotation
 */
print "<br>";
print_titre($langs->trans("Modules de num&eacute;rotation des propositions"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td nowrap>'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
    $var=true;
    //Get Active docs
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 14) == 'mod_propaleGA_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);
            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propaleGA/".$file.".php");

            $module = new $file;

            // Show modules according to features level
            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
            print $module->info();
            print '</td>';
            // Examples
            print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

            print '<td align="center">';
            if ($conf->global->PROPALEGA_ADDON == "$file")
            {
                print img_tick($langs->trans("Activated"));
            } else {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;type=propalGA&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
            }
            print '</td>';
            require_once(DOL_DOCUMENT_ROOT."/Babel_GA/PropalGA.class.php");
            $ctr=new PropalGA($db);
            //$ctr->initAsSpecimen();

            // Info
            $htmltooltip='';
            $htmltooltip.='<b>'.$langs->trans("Version").'</b>: '.$module->getVersion().'<br>';
            $facture->type=0;
            $nextval=$module->getNextValue($mysoc,$ctr);
            if ("$nextval" != $langs->trans("NotAvailable"))    // Keep " on nextval
            {
                $htmltooltip.='<b>'.$langs->trans("NextValue").'</b>: ';
                if ($nextval)
                {
                    $htmltooltip.=$nextval.'<br>';
                } else {
                    $htmltooltip.=$langs->trans($module->error).'<br>';
                }
            }

            print '<td align="center">';
            print $html->textwithtooltip('',$htmltooltip,1,0);
            print '</td>';

            print "</tr>\n";
        }
    }
    closedir($handle);
}

print "</table>";

print '<br>';
print '<br>';

print_titre($langs->trans("Mod&egrave;le de contrat"));

// Defini tableau def de modele deplacement
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'contratGA'";

$resql=$db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows=$db->num_rows($resql);
    while ($res=$db->fetch_object($resql))
    {
        array_push($def, $res->nom);
        $i++;
    }
} else {
    dol_print_error($db);
}



print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class='ui-widget-overlay' style='position: relative;'><td colspan=2></td></tr>";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Id")."</td>\n";
print "  <td width=\"140\">".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";
//Contrat GA
$dir = DOL_DOCUMENT_ROOT."/includes/modules/contratGA/";
$handle=opendir($dir);
$html=new Form($db);


$var=true;
while (($file = readdir($handle))!==false)
{
    //preg_match('/^pdf_deplacement/',$file) && preg_match('/.modules.php$/',$file)
    //
    if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,14) == 'pdf_contratGA_')
    {
        $name = substr($file, 14, strlen($file) - 26);//babel
        $classname = substr($file, 0, strlen($file) -12);//pdf_deplacement_babel

        $var=!$var;
        print "<tr ".$bc[$var].">\n  <td>";
        print "$name";
        print "</td>\n  <td>\n";
        require_once($dir.$file);
        $module = new $classname($db);
        print $module->libelle;
        print "</td>";
        print "<td>";
        print $module->description;
        print '</td>';

        // Active
        if (in_array($name, $def))
        {
            print "<td align=\"center\">\n";
            if ($conf->global->CONTRATGA_ADDON_PDF != "$name")
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
                print img_tick($langs->trans("Disable"));
                print '</a>';
            } else {
                print img_tick($langs->trans("Enabled"));
            }
            print "</td>";
        } else {
            print "<td align=\"center\">\n";
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
            print "</td>";
        }

        // Defaut
        print "<td align=\"center\">";
        if ($conf->global->CONTRATGA_ADDON_PDF == "$name")
        {
            print img_tick($langs->trans("Default"));
        }
        else
        {
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
        }
        print '</td>';

        // Info
        $htmltooltip ='<b align="center">Affichage standard PDF</b> ';



        print '<td align="center">';
        print $html->textwithtooltip('',$htmltooltip,1,0);
        print '</td>';

        print "</tr>\n";
    }
}
closedir($handle);

print '</table>';
print '<br>';

/*
 *  Module numerotation
 */
print "<br>";
print_titre($langs->trans("Modules de num&eacute;rotation des contrats"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td nowrap>'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
    $var=true;
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 14) == 'mod_contratGA_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);
            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/contratGA/".$file.".php");

            $module = new $file;

            // Show modules according to features level
            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
            print $module->info();
            print '</td>';
            // Examples
            print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

            print '<td align="center">';
            if ($conf->global->CONTRATGA_ADDON == "$file")
            {
                print img_tick($langs->trans("Activated"));
            } else {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
            }
            print '</td>';
            require_once(DOL_DOCUMENT_ROOT."/Babel_GA/ContratGA.class.php");
            $ctr=new ContratGA($db);
            //$ctr->initAsSpecimen();

            // Info
            $htmltooltip='';
            $htmltooltip.='<b>'.$langs->trans("Version").'</b>: '.$module->getVersion().'<br>';
            $facture->type=0;
            $nextval=$module->getNextValue($mysoc,$ctr);
            if ("$nextval" != $langs->trans("NotAvailable"))    // Keep " on nextval
            {
                $htmltooltip.='<b>'.$langs->trans("NextValue").'</b>: ';
                if ($nextval)
                {
                    $htmltooltip.=$nextval.'<br>';
                } else {
                    $htmltooltip.=$langs->trans($module->error).'<br>';
                }
            }

            print '<td align="center">';
            print $html->textwithtooltip('',$htmltooltip,1,0);
            print '</td>';

            print "</tr>\n";
        }
    }
    closedir($handle);
}


//print "<tr><td>".$langs->trans('Liste des p&eacute;riodes disponibles');
//print "    <td><table>";
//$requete = "SELECT * FROM ";
//
//
//print "</table></td>";
print "</table><br>\n";

print '    </div>';

print '    <div id="fragment-6">';
print "   <table  width=100% style='border-collapse: collapse;' cellpadding=15>";
//Contenu pour medium
print "<tbody id='medium'>";
print "<tr>";
print '<td colspan=2 class="ui-widget-header ui-state-default">Processus</td>';
print "<tr>";
$tmp = $conf->global->CONTRATVALIDATE_CREATE_FOURN_COMM."x"!="x"?"checked":"";
print '<td width=30% class="ui-widget-header ui-state-default">Cr&eacute;ation automatique de la commande fournisseur </td>
       <td class="ui-widget-content"><span style="float: left;"><input name="CONTRATVALIDATE_CREATE_FOURN_COMM" '.$tmp.' id="CONTRATVALIDATE_CREATE_FOURN_COMM" type="checkbox"/></span><span class="ui-icon ui-icon-alert" style="margin-top: -1px; margin-left: 1.5em; margin-right: 4px; float: left"></span><small style="margin-top: 3px; float: left;">Attention n&eacute;c&eacute;ssite les prix fournisseurs des produits</small></td>';
print "<tr>";
$tmp = $conf->global->CONTRATVALIDATE_CREATE_FOURN_FACT."x"!="x"?"checked":"";
print '<td width=30% class="ui-widget-header ui-state-default"> Cr&eacute;ation de la facture fournisseur</td>
       <td class="ui-widget-content"> <input name="CONTRATVALIDATE_CREATE_FOURN_FACT" '.$tmp.' id="CONTRATVALIDATE_CREATE_FOURN_FACT" type="checkbox"/></td>';
print "<tr>";
$tmp = $conf->global->CONTRATVALIDATE_CREATE_FACT."x"!="x"?"checked":"";
print '<td width=30% class="ui-widget-header ui-state-default"> Cr&eacute;ation de la facture cesionnaire</td>
       <td class="ui-widget-content"> <input name="CONTRATVALIDATE_CREATE_FACT" '.$tmp.' id="CONTRATVALIDATE_CREATE_FACT" type="checkbox"/></td>';

print "<tr>";
$tmp = $conf->global->BABELGA_LIMREG_FACTURE."x"!="x"?$conf->global->BABELGA_LIMREG_FACTURE:0;
print '<td width=30% class="ui-widget-header ui-state-default"> Limite de r&egrave;glement de la facture</td>
       <td class="ui-widget-content">'.$html->select_conditions_paiements($tmp,'BABELGA_LIMREG_FACTURE',-1,0,false).'</td>';
//TODO changer en select cond de reglement

print "</table>";

print '    </div>'; //End tabs




print "<div id='periodDialog'>";
print "<form id='periodSimpleForm' action='".$_SERVER['PHP_SELF']."?action=saveData' onsubmit='return(false);' method='POST'>";
        print "<table style='border-collapse: collapse;' width=90%><thead></thead>";
        print "  <tr><th class='ui-widget-header ui-state-default'>Type de p&eacute;riode</th>";
        print "      <th class='ui-widget-header ui-state-default'>Dur&eacute;e totale en mois</th>";
        print "      <th class='ui-widget-header ui-state-default'>Terme <br/>(&eacute;chu / &agrave; &eacute;choir)</th>";
        print "      <th class='ui-widget-header ui-state-default'>&nbsp;</th>";
        print "<tbody id='tbodyPerSimpleDialog'>";
        $requete = "SELECT * FROM Babel_GA_period_simple ORDER by financement_period_refid, duree";
        $sql = $db->query($requete);
        $iter = 0;
        while ($res=$db->fetch_object($sql))
        {
            print "  <tr><td align=center class='ui-widget-content'>";
            print "    <select  class='required' name='typePeriodSimple-".$iter."' id='typePeriodSimple-".$iter."'>";
            $requete1 = "SELECT * FROM Babel_financement_period  WHERE active=1 order by NbIterAn DESC";
            $sql1 = $db->query($requete1);
            while ($res1 = $db->fetch_object($sql1))
            {
                if ($res1->id == $res->financement_period_refid)
                {
                    print "<option SELECTED value='".$res1->id."'>".$res1->Description."</option>";
                } else {
                    print "<option value='".$res1->id."'>".$res1->Description."</option>";
                }
            }
            print "</select>";
            print "     </td>";
            print "     <td align=center class='ui-widget-content'><input size=10 class='required sup1' name='durPeriodSimple-".$iter."' id='durPeriodSimple-".$iter."' value='".$res->duree."'></td>";
            $extra ="checked";
            if ($res->echu == 0) { $extra = ""; }
            print "     <td align=center class='ui-widget-content'><input type=checkbox name='echPeriodSimple-".$iter."' id='echPeriodSimple-".$iter."' ".$extra."></td>";
            print "     <td align=center class='ui-widget-content'><span class='ui-icon ui-icon-trash'></span></td>";
            $iter++;
        }

        print "</tbody>";
        print "<tfoot><tr><td class='ui-widget-header ui-state-hover' colspan=4 align=right style='padding: 2px;'>
                       <div class='ui-state-hover ui-corner-all' style='width: 16px;'>
                        <span class='ui-icon ui-icon-plus' id='addPerSimpleDialogBut'></div>
                        </span></td></tfoot>";
        print "</table>";
    print "</form>";
    print "</div>";


    print "<div id='periodFinDialog'>";
    print "<form id='periodFinForm' action='".$_SERVER['PHP_SELF']."?action=saveData' onsubmit='return(false);' method='POST'>";

        print "<table cellpadding=5 width=90%>
                  <thead>
                     <tr><th style='padding: 15px;' class='ui-widget-header ui-state-default' colspan=4>D&eacute;finition des p&eacute;riodes</th></thead>";
        print "<tbody id='tbodyDefPerDialog'>";

        $requete = "SELECT * FROM Babel_financement_period  WHERE active=1 order by NbIterAn DESC";
        $sql = $db->query($requete);
            print "<tr>";
            print "<th class='ui-widget-content'>P&eacute;riode";
            print "<th class='ui-widget-content'>Unit&eacute;";
            print "<th class='ui-widget-content'>Iteration / an";
            print "<th  class='ui-widget-content'>";
            print "</tr>";
        $iter=0;
        while ($res = $db->fetch_object($sql))
        {
            print "<tr>";
            print "<td class='ui-widget-content'><input  class='required'  name='defPeriodSimpleDesc-".$iter."' id='defPeriodSimpleDesc-".$iter."' value='".$res->Description."'>";
            print "<td class='ui-widget-content'><input  class='required' name='defPeriodSimpleDesc2-".$iter."' id='defPeriodSimpleDesc2-".$iter."' value='".$res->Description2."'>";
            print "<td class='ui-widget-content'><input  class='required sup1' name='defPeriodSimpleCnt-".$iter."' id='defPeriodSimpleCnt-".$iter."' value='".$res->NbIterAn."'>";
            print "<td class='ui-widget-content'><span class='ui-icon ui-icon-trash'></span></td>";
            print "</tr>";
            $iter++;
        }
        print "<tfoot><tr><td class='ui-widget-header ui-state-hover' colspan=4 align=right style='padding: 2px;'>
                       <div class='ui-state-hover ui-corner-all' style='width: 16px;'>
                        <span class='ui-icon ui-icon-plus' id='addDefPerDialogBut'></div>
                        </span></td></tfoot>";

print "</table>";
print "</form>";
print "</div>";


llxFooter('$Date: 2010/04/01 01:31:46 $ - $Revision: 1.00 $');
?>
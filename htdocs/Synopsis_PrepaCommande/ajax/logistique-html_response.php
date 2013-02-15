<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 28 sept. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : logistique-html_response.php
 * GLE-1.2
 */
require_once('../../main.inc.php');
$id = $_REQUEST['id'];
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");

$tabBrutEtCondense = false;

$arrProdNegQty = array();
$arrProdPosQty = array();
$com = new Synopsis_Commande($db);
$res = $com->fetch($id);
if ($res > 0) {

    $arrGrpTmp = $com->listGroupMember();
    if ($arrGrpTmp && $tabBrutEtCondense) {
//Tabs brut ou condense
        $html = new Form($db);
        print "<div id='tabFinance'>";
        print "<ul><li><a href='#fragment1'>Condens&eacute;</a></li>";
        print "    <li><a href='#fragment2'>Brut</a></li>";
        print "</ul>";
        $com->fetch_lines(0);


        print "<div id='fragment1'>";
        $arrProd = array();
        $arrProdDet = array();
        foreach ($com->lines as $key => $val) {
            if ($val->fk_product > 0 && $val->ref . "x" != "x")
                $arrProd[$val->fk_product] += $val->qty;
            $arrProdDet[$val->fk_product][$com->id] = array('line' => $val, "com" => $com);
        }

        foreach ($arrGrpTmp as $key => $val) {
            foreach ($val->lignes as $key1 => $val1) {
                if ($val1->fk_product > 0 && $val1->ref . "x" != "x")
                    $arrProd[$val1->fk_product] += $val1->qty;
                $arrProdDet[$val1->fk_product][$com->id] = array('line' => $val1, "com" => $val);
            }
        }
        print "<table cellpadding=15 width=700><tr><th class='ui-widget-header ui-state-default'>Produit<th class='ui-widget-header ui-state-default'>Qte<th class='ui-widget-header ui-state-default'>commande / dispo";
        displayLigneBouton($user, $com, '');

        foreach ($arrProd as $key => $val) {
            if ($val == 0)
                continue;
            $tmpProd = new Product($db);
            $tmpProd->fetch($key);
            if ($tmpProd->type <> 0)
                continue;
            print "<tr><td class='ui-widget-content'>" . $tmpProd->getNomUrl(1) . "<td class='ui-widget-content' align=center>" . $val;
            print "<td class='ui-widget-content'>";
            print "<table width=100%>";
            foreach ($arrProdDet[$tmpProd->id] as $key1 => $val1) {
                print "<tr><td valign=middle>" . $val1['com']->getNomUrl(6) . "<td valign=middle>";

                if ($user->rights->SynopsisPrepaCom->exped->Modifier) {
                    $htmlSelect = str_replace('class="flat"', 'class="flat logistique"', $html->selectyesno('logistiqueOK-' . $val1['line']->id, $val1['line']->logistique_ok, 0));
                    print $htmlSelect;
                } else {
                    print "" . ($val1['line']->logistique_ok > 0 ? "oui" : "non");
                }
                if ($val1['line']->logistique_ok == '1') {
                    print "   <div id='pasdispo-" . $val1['line']->id . "' style='display:none'>Dispo le :<input id='logistiqueKODate-" . $val1['line']->id . "' class='datepicker'></div>";
                } else {
                    if ($user->rights->SynopsisPrepaCom->exped->Modifier) {
                        print "   <div id='pasdispo-" . $val1['line']->id . "' style='display:block'>Dispo le :<input id='logistiqueKODate-" . $val1['line']->id . "' value='" . ($val1['line']->logistique_date_dispo . "x" != "x" ? date('d/m/Y', strtotime($val1['line']->logistique_date_dispo)) : "") . "' class='datepicker'></div>";
                    } else {
                        print "<br/>Dispo&nbsp;le:&nbsp;" . ($val1['line']->logistique_date_dispo . "x" != "x" ? date('d/m/Y', strtotime($val1['line']->logistique_date_dispo)) : "");
                    }
                }
            }
            print "</table></td></tr>";
        }
        displayLigneBouton($user, $com, 'bis');
        print "</table>";
        print "</div>";

//Mode brut

        print "<div id='fragment2'>";
        print "<table cellpadding=10 width=900>";
        displayLigneHeader();
        displayLigneBouton($user, $com, 'a');

        displayLogistique($com, false);
        $tot = count($arrGrpTmp) - 1;
        $i = 0;
//        require_once('Var_Dump.php');
//        var_dump::display($arrGrpTmp);
        foreach ($arrGrpTmp as $key => $val) {
            $val->fetch_lines(0);
            if ($i == $tot) {
                displayLogistique($val, true);
            } else {
                displayLogistique($val, false);
            }
            $i++;
        }
        displayLigneBouton($user, $com, 'abis');

        print "</table>";
        print "</div>";
        print "</div>";
    } else {
        print "<table cellpadding=10 width=900>";
        displayLigneHeader();
        displayLigneBouton($user, $com, 'b');

        displayLogistique($com, true, true);
        displayLigneBouton($user, $com, 'bbis');
        print "</table>";
    }
    print "<div id='valDialog' class='cntValDialog'>&Ecirc;tes vous sur de vouloir valider cette commande ?</div>";
    print "<div id='modDialog' class='cntModDialog'>&Ecirc;tes vous sur de vouloir modifier cette commande ?</div>";
    print "<div id='modDevalidationDialog' class='cntDeValDialog'>&Ecirc;tes vous sur de vouloir invalider cette commande ?</div>";
} else {
    print "Pas de commande trouv&eacute;e";
}
//  print "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/'>≤/script> ":
print "<style>.ui-selectmenu-menu, .ui-selectmenu-dropdown { min-width:60px;}
                #ui-datepicker-div { z-index: 9999999; }</style>";
print <<<EOF
    <script>
    var caseLogistique = false;
    jQuery(document).ready(function(){
        jQuery('#tabFinance').tabs({
          cache: true,
          fx: { opacity: 'toggle' },
          spinner: 'Chargement ...',
        });
    if(jQuery('.cntValDialog').length > 1){
//        jQuery('#valDialog').dialog( "destroy" );
        jQuery('#valDialog').remove();
    }
    if(jQuery('.cntModDialog').length > 1){
//        jQuery('#modDialog').dialog( "destroy" );
        jQuery('#modDialog').remove();
    }
    if(jQuery('.cntDeValDialog').length > 1){
//        jQuery('#modDevalidationDialog').dialog( "destroy" );
        jQuery('#modDevalidationDialog').remove();
    }

        jQuery('#modDevalidationDialog').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Invalidation de la logistique",
            buttons: {
                Ok: function(){
                    //num Commande
                    //Statut Valid aka logistique_statut
                    jQuery.ajax({
                        url: 'ajax/xml/devalLogistique-xml_response.php',
                        datatype: "xml",
                        type: "POST",
                        data: "comId="+comId,
                        cache: false,
                        success:function(msg){
                            if(jQuery(msg).find('OK').length > 0)
                            {
                                jQuery('#valDialog').dialog("close");
                                //reload
                                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                jQuery('#valDialog').dialog( "destroy" );
                                jQuery('#valDialog').remove();
                                jQuery.ajax({
                                    url: "ajax/logistique-html_response.php",
                                    data: "id="+comId,
                                    cache: false,
                                    datatype: "html",
                                    type: "POST",
                                    success: function(msg){
                                        jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                                    },
                                });
                            } else {
                                alert ('Il y a eu une erreur');
                            }
                        }
                    });

                    jQuery(this).dialog("close");
                },
                Annuler: function(){
                    jQuery(this).dialog("close");
                }
            }
        });

        jQuery('#valDialog').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Validation de la logistique",
            buttons: {
                Ok: function(){
                    //num Commande
                    //Statut Valid aka logistique_statut
                    jQuery.ajax({
                        url: 'ajax/xml/valLogistique-xml_response.php',
                        datatype: "xml",
                        type: "POST",
                        data: "comId="+comId,
                        cache: false,
                        success:function(msg){
                            if(jQuery(msg).find('OK').length > 0)
                            {
                                jQuery('#valDialog').dialog("close");
                                //reload
                                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                jQuery('#valDialog').dialog( "destroy" );
                                jQuery('#valDialog').remove();
                                jQuery.ajax({
                                    url: "ajax/logistique-html_response.php",
                                    data: "id="+comId,
                                    cache: false,
                                    datatype: "html",
                                    type: "POST",
                                    success: function(msg){
                                        jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                                    },
                                });
                            } else {
                                alert ('Il y a eu une erreur');
                            }
                        }
                    });

                    jQuery(this).dialog("close");
                },
                Annuler: function(){
                    jQuery(this).dialog("close");
                }
            }
        });
        jQuery('#modDialog').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Modification de la logistique",
            buttons: {
                Ok: function(){
                    var findJquery = caseLogistique;
                    if (!findJquery) findJquery = "";
                    else findJquery= " "+findJquery;
                    var data=jQuery('#resDisp'+findJquery).find('select').serialize();
                    var data1="";
                    jQuery('#resDisp'+findJquery).find('.datepicker').each(function(){
                        var id = jQuery(this).attr('id');
                        var val = jQuery(this).val();
                        data1+='&'+id+'='+val;
                    });

                    jQuery.ajax({
                        url: 'ajax/xml/modLogistique-xml_response.php',
                        datatype: "xml",
                        type: "POST",
                        data: data+data1+"&comId="+comId,
                        cache: false,
                        success:function(msg){
                            var res = jQuery(msg).find('result').text();
                            jQuery('#modDialog').dialog("close");
                        }
                    });
                },
                Annuler: function(){
                    jQuery(this).dialog("close");
                }
            }
        });

EOF;
getScript('b');
getScript('a', '#fragment2', '#fragment1');
getScript('', '#fragment1', '#fragment1');
getScript('bbis');
getScript('abis', '#fragment2', '#fragment1');
getScript('bis', '#fragment1', '#fragment1');


print <<<EOF

    function changeDispo(elem, val, suff, oblige){
        if($(elem).attr('value') != val || oblige){
            var tmp=jQuery(elem).attr('id').replace(/^logistiqueOK-/,'');
            jQuery(elem).attr('value', val);
            if(val == 'yes')
                jQuery(suff+'#pasdispo-'+tmp).show();
            else
                jQuery(suff+'#pasdispo-'+tmp).show();

        }
    }
   
            jQuery('.logistique').change(function(){
                changeDispo(this, jQuery(this).val(), '', true);
            });

        jQuery.datepicker.setDefaults(jQuery.extend({
            showMonthAfterYear: false,
            dateFormat: 'dd/mm/yy',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            buttonImage: 'cal.png',
            displayWeek:true,
            buttonImageOnly: true,
            showTime: false,
            duration: '',
            displayWeek:true,
            constrainInput: false,
        },
        jQuery.datepicker.regional['fr']));
        jQuery('.datepicker').datepicker({displayWeek:true});

    });
    </script>
EOF;

function displayLogistique($com) {
    global $db, $user, $lang;
    $html = new Form($db);
    $com->fetch_lines(1);

    $prod = new Product($db);
    if (count($com->lines) > 0) {
        foreach ($com->lines as $key => $val) {
            $prod->fetch($val->fk_product);
            if ($prod->type <> 0)
                continue;
            if ($val->ref . "x" != "x" && $val->qty <> 0) {
                $imgWarning = "";
                if (strtotime($val->logistique_date_dispo) < time()) {
                    $imgWarning = img_warning("Date depass&eacute;");
                }
                if ($user->rights->SynopsisPrepaCom->exped->Modifier && $com->logistique_statut < 1) {
                    $htmlSelect = str_replace('class="flat"', 'class="flat logistique"', $html->selectyesno('logistiqueOK-' . $val->rowid, $val->logistique_ok, 0));
                    print "<tr><td width=155 class='ui-widget-content'>" . $htmlSelect;
                } else {
                    print "<tr><td width=155 class='ui-widget-content' align=center>" . ($val->logistique_ok > 0 ? "oui" : "non");
                }
                if ($val->logistique_ok == '1') {
                    print "   <div id='pasdispo-" . $val->rowid . "' style='display:none'>" . $imgWarning . " Dispo le :<input id='logistiqueKODate-" . $val->rowid . "' class='datepicker'></div>";
                } else {
                    if ($user->rights->SynopsisPrepaCom->exped->Modifier && $com->logistique_statut < 1) {
                        print "   <div id='pasdispo-" . $val->rowid . "' style='display:block'>" . $imgWarning . " Dispo le :<input id='logistiqueKODate-" . $val->rowid . "' value='" . ($val->logistique_date_dispo . "x" != "x" ? date('d/m/Y', strtotime($val->logistique_date_dispo)) : "") . "' class='datepicker'></div>";
                    } else {
                        print "<br/>" . $imgWarning . "&nbsp;Dispo&nbsp;le:&nbsp;" . ($val->logistique_date_dispo . "x" != "x" ? date('d/m/Y', strtotime($val->logistique_date_dispo)) : "");
                    }
                }
                print "    <td width=100 class='ui-widget-content'>" . utf8_encodeRien($prod->getNomUrl(1));
                print "    <td width=20 class='ui-widget-content'>" . utf8_encodeRien($val->qty);
                print "    <td width=100 class='ui-widget-content'>" . utf8_encodeRien($val->libelle);
                print "    <td class='ui-widget-content'>" . utf8_encodeRien($val->desc);
            }
        }
    } else {
        print " Pas de produits dans la commande";
    }
}

function displayLigneHeader() {
    print "<tr><th class='ui-widget-header ui-state-default'>Dispo?.
                   <th class='ui-widget-header ui-state-default'>Ref.
                   <th class='ui-widget-header ui-state-default'>Qt&eacute;
                   <th class='ui-widget-header ui-state-default'>Label
                   <th class='ui-widget-header ui-state-default'>Description";
}

function getScript($id, $suff = false, $suffB = false) {
    $suff1 = ($suff) ? $suff . " " : "";
    $suff2 = ($suff) ? "'" . $suff . "'" : "false";
    $suff3 = ($suffB) ? "'" . $suffB . "'" : "false";

    echo "
       jQuery('#logistique1" . $id . "').click(function(){
            jQuery('" . $suff1 . ".logistique').each(function(){
                changeDispo(this, 'yes', '" . $suff1 . "');
            });
        });
        jQuery('#logistique2" . $id . "').click(function(){
            jQuery('" . $suff1 . ".logistique').each(function(){
                changeDispo(this, 'no', '" . $suff1 . "');
            });
        });
        jQuery('#logistique3" . $id . "').click(function(){
            caseLogistique=" . $suff2 . ";
            jQuery('#valDialog').dialog('open');
        });
        jQuery('#logistique4" . $id . "').click(function(){
            caseLogistique=" . $suff2 . ";
            jQuery('#modDialog').dialog('open');
        });
        jQuery('#logistique41" . $id . "').click(function(){
            caseLogistique=" . $suff3 . ";
            jQuery('#modDevalidationDialog').dialog('open');
        });";
}

function displayLigneBouton($user, $com, $id = '') {
    if ($user->rights->SynopsisPrepaCom->exped->Modifier) {
        if ($com->logistique_statut < 1) {
            print "<tr class='ui-widget-header'>
                        <td colspan=5 align=center>
                        <button id='logistique1" . $id . "' class='butAction ui-corner-all ui-widget-header ui-state-default'>Tout &agrave; oui</button>
                        <button id='logistique2" . $id . "' class='butAction ui-corner-all ui-widget-header ui-state-default'>Tout &agrave; non</button>
                        <button id='logistique3" . $id . "' class='butAction ui-corner-all ui-widget-header ui-state-default'>Valider</button>
                        <button id='logistique4" . $id . "' class='butAction ui-corner-all ui-widget-header ui-state-default'>Modifier</button>
                      </td></tr>";
        } else {
            print "<tr class='ui-widget-header'>
                        <td colspan=5 align=center>
                        <button id='logistique41" . $id . "' class='butAction ui-corner-all ui-widget-header ui-state-default'>Modifier</button>
                      </td></tr>";
        }
    }
}

?>
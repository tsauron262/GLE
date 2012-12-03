<?php
/*
  ** GLE by Synopsis et DRSI
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
  require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
  require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

  if ($conf->global->PREPACOMMANDE_SHOW_FINANCE_FULL_DETAIL)
  {
      $com = new Synopsis_Commande($db);
      $html = new Form($db);
      $res=$com->fetch($id);
      if ($res>0)
      {
            $com->fetch_lines(0);

            $prod = new Product($db);
            if (count($com->lines) > 0)
            {
                print "<table width=1100 cellpadding=5>";
                print "<tr><th style='padding:15px;' class='ui-widget-header ui-state-default'>Accord?.
                           <th style='padding:15px;' class='ui-widget-header ui-state-default'>Ref.
                           <th style='padding:15px;' class='ui-widget-header ui-state-default'>Label
                           <th style='padding:15px;' class='ui-widget-header ui-state-default'>PU HT
                           <th style='padding:15px;' class='ui-widget-header ui-state-default'>Qt&eacute;.
                           <th style='padding:15px;' class='ui-widget-header ui-state-default'>Total HT
                           <th style='padding:15px;' class='ui-widget-header ui-state-default'>Description";
                foreach($com->lines as $key=> $val)
                {
                    if ($val->total_ht == 0) continue;
                    if ($val->fk_product>0)
                    {
                        $prod->fetch($val->fk_product);
                        print "<tr><td class='ui-widget-content'>";
                        if ($user->rights->SynopsisPrepaCom->financier->Modifier)
                        {
                            print $html->selectyesno('financeOK-'.$val->id,$val->finance_ok,0,"finance");
                        } else {
                            print ($val->finance_ok>0?'oui':'non');
                        }
                        print "    <td class='ui-widget-content'>".$prod->getNomUrl(1);
                        print "    <td class='ui-widget-content'>".utf8_encode($val->libelle);
                        print "    <td class='ui-widget-content' nowrap align=right>".price($val->subprice)."&euro;";
                        print "    <td class='ui-widget-content' align=center>".$val->qty;
                        print "    <td class='ui-widget-content' nowrap align=right>".price($val->total_ht)."&euro;";
                        print "    <td width=60% class='ui-widget-content'>".utf8_encode($val->desc);
                        if ($prod->isservice())
                        {
                            // Duration
                            print '<br/><table cellpadding=10 width=200><tr><th class="ui-state-default ui-widget-header">'.$langs->trans("Duration").'</td><td class="ui-widget-content">'.$prod->duration_value.'&nbsp;';
                            if ($prod->duration_value > 1)
                            {
                                $dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
                            } else {
                                $dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
                            }
                            print $langs->trans($dur[$prod->duration_unit])."&nbsp;";
                            print "</table>";
                        }
                    } else {
                        print "<tr><td class='ui-widget-content'>";
                        if ($user->rights->SynopsisPrepaCom->financier->Modifier)
                        {
                            print $html->selectyesno('financeOK-'.$val->id,$val->finance_ok,0,"finance");
                        } else {
                            print ($val->finance_ok>0?'oui':'non');
                        }
                        print "    <td class='ui-widget-content'> - ";
                        print "    <td class='ui-widget-content'>".utf8_encode($val->libelle);
                        print "    <td width=60% class='ui-widget-content'>".utf8_encode($val->desc);
                    }
                }
                $arrGrpTmp = $com->listGroupMember();
                if ($arrGrpTmp)
                {
                    foreach ($arrGrpTmp as $key1=>$val1){
                        $val1->fetch_lines();
                        foreach($val1->lignes as $key=> $val)
                        {
                            if ($val->total_ht == 0) continue;
                            if ($val->fk_product>0){
                                $prod->fetch($val->fk_product);
                                print "<tr><td class='ui-widget-content'>";
                                if ($user->rights->SynopsisPrepaCom->financier->Modifier)
                                {
                                    print $html->selectyesno('financeOK-'.$val->id,$val->finance_ok,0,"finance");
                                } else {
                                    print ($val->finance_ok>0?'oui':'non');
                                }
                                print "    <td class='ui-widget-content'>".$prod->getNomUrl(1);
                                print "    <td class='ui-widget-content'>".utf8_encode($val->libelle);
                                print "    <td class='ui-widget-content' nowrap align=right>".price($val->subprice)."&euro;";
                                print "    <td class='ui-widget-content' align=center>".$val->qty;
                                print "    <td class='ui-widget-content' nowrap align=right>".price($val->total_ht)."&euro;";
                                print "    <td width=60% class='ui-widget-content'>".utf8_encode($val->desc);
                                if ($prod->isservice())
                                {
                                    // Duration
                                    print '<br/><table cellpadding=10 width=200><tr><th class="ui-state-default ui-widget-header">'.$langs->trans("Duration").'</td><td class="ui-widget-content">'.$prod->duration_value.'&nbsp;';
                                    if ($prod->duration_value > 1)
                                    {
                                        $dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
                                    } else {
                                        $dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
                                    }
                                    print $langs->trans($dur[$prod->duration_unit])."&nbsp;";
                                    print "</table>";
                                }
                            } else {
                                print "<tr><td class='ui-widget-content'>";
                                if ($user->rights->SynopsisPrepaCom->financier->Modifier)
                                {
                                    print $html->selectyesno('financeOK-'.$val->id,$val->finance_ok,0,"finance");
                                } else {
                                    print ($val->finance_ok>0?'oui':'non');
                                }
                                print "    <td class='ui-widget-content'> - ";
                                print "    <td class='ui-widget-content'>".utf8_encode($val->libelle);
                                print "    <td width=60% class='ui-widget-content'>".utf8_encode($val->desc);
                            }
                        }
                    }
                }


                if ($user->rights->SynopsisPrepaCom->financier->Modifier)
                {
                    if ($com->finance_statut == 1)
                    {
                        print "<tr class='ui-widget-header'>
                                <td colspan=8 align=center>
                                <button id='finance4a' class='butAction ui-corner-all ui-widget-header ui-state-default'>Modifier</button>
                              ";
                    } else {
                        print "<tr class='ui-widget-header'>
                                <td colspan=8 align=center>
                                <button id='finance1' class='butAction ui-corner-all ui-widget-header ui-state-default'>Tout &agrave; oui</button>
                                <button id='finance2' class='butAction ui-corner-all ui-widget-header ui-state-default'>Tout &agrave; non</button>
                                <button id='finance3' class='butAction ui-corner-all ui-widget-header ui-state-default'>Valider</button>
                                <button id='finance4' class='butAction ui-corner-all ui-widget-header ui-state-default'>Modifier</button>
                              ";
                    }
                }
                print "</table>";

                print "<div id='valdialogFin' class='cntValdialogFin'>&Ecirc;tes vous sur de vouloir valider cette commande ?</div>";
                print "<div id='moddialogFin' class='cntModdialogFin'>&Ecirc;tes vous sur de vouloir modifier cette commande ?</div>";
                print "<div id='DeValdialogFin' class='cntDeValdialogFin'>&Ecirc;tes vous sur de vouloir invalider cette commande ?</div>";

            } else {
                print " Pas de ligne dans la commande";
            }


          } else {
            print "Pas de commande trouv&eacute;e";
          }
        //  print "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/'>≤/script> ":
          print "<style>.ui-selectmenu-menu, .ui-selectmenu-dropdown { min-width:60px;}
                        #ui-datepicker-div { z-index: 9999999; }</style>";
//          print "<script > jQuery(document).ready(function(){ jQuery('select.finance').selectmenu({style: 'dropdown', maxHeight: 300, menuWidth: 60 }); });  </script>\n";
          print <<<EOF
    <script>
jQuery(document).ready(function(){

    if(jQuery('.cntValdialogFin').length > 1){
        jQuery('#valdialogFin').dialog( "destroy" );
        jQuery('#valdialogFin').remove();
    }
    if(jQuery('.cntDeValdialogFin').length > 1){
        jQuery('#DeValdialogFin').dialog( "destroy" );
        jQuery('#DeValdialogFin').remove();
    }
    if(jQuery('.cntModdialogFin').length > 1){
        jQuery('#moddialogFin').dialog( "destroy" );
        jQuery('#moddialogFin').remove();
    }

        jQuery('#valdialogFin').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Validation de la financi&egrave;re",
            buttons: {
                Ok: function(){
                    //num Commande
                    //Statut Valid aka logistique_statut
                    jQuery.ajax({
                        url: 'ajax/xml/valFinance-xml_response.php',
                        datatype: "xml",
                        type: "POST",
                        data: "comId="+comId,
                        cache: false,
                        success:function(msg){
                            if(jQuery(msg).find('OK').length > 0)
                            {
                                jQuery('#valdialogFin').dialog("close");
                                //reload
                                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                jQuery('#valdialogFin').dialog( "destroy" );
                                jQuery('#valdialogFin').remove();
                                jQuery.ajax({
                                    url: "ajax/finance-html_response.php",
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

        jQuery('#DeValdialogFin').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Invalidation de la financi&egrave;re",
            buttons: {
                Ok: function(){
                    //num Commande
                    //Statut Valid aka logistique_statut
                    jQuery.ajax({
                        url: 'ajax/xml/devalFinance-xml_response.php',
                        datatype: "xml",
                        type: "POST",
                        data: "comId="+comId,
                        cache: false,
                        success:function(msg){
                            if(jQuery(msg).find('OK').length > 0)
                            {
                                jQuery('#DeValdialogFin').dialog("close");
                                //reload
                                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                jQuery('#DeValdialogFin').dialog( "destroy" );
                                jQuery('#DeValdialogFin').remove();
                                jQuery.ajax({
                                    url: "ajax/finance-html_response.php",
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

        jQuery('#moddialogFin').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Modification de l'accord financier",
            buttons: {
                Ok: function(){
                      var data=jQuery('#resDisp').find('select').serialize();

                    jQuery.ajax({
                        url: 'ajax/xml/modFinance-xml_response.php',
                        datatype: "xml",
                        type: "POST",
                        data: data+"&comId="+comId,
                        cache: false,
                        success:function(msg){
                            var res = jQuery(msg).find('result').text();
                            jQuery('#moddialogFin').dialog("close");
                        }
                    });
                },
                Annuler: function(){
                    jQuery(this).dialog("close");
                }
            }
        });

        jQuery('#finance1').click(function(){
            jQuery('.finance').each(function(){
            });
        });
        jQuery('#finance2').click(function(){
            jQuery('.finance').each(function(){
            });
        });
        jQuery('#finance3').click(function(){
            jQuery('#valdialogFin').dialog('open');
        });
        jQuery('#finance4').click(function(){
            jQuery('#moddialogFin').dialog('open');
        });
        jQuery('#finance4a').click(function(){
            jQuery('#DeValdialogFin').dialog('open');
        });


    });
</script>
EOF;

  } else {
// finance => total HT +
// mode de paiement et condition de reglement + pas le detail

    $commande = new Synopsis_Commande($db);
    $html = new Form($db);
    $res=$commande->fetch($id);


    print '<table class="border" width="700" cellpadding=10>';

    // Ref
    $extra = "";
    $ret = $commande->isGroupMember();
    if ($ret){
        $extra = 'Membre du groupe : '.$ret->nom;
    }
    print '<tr><th width="18%" class="ui-state-default ui-widget-header">'.$langs->trans('Ref').'</th>';
    if ($extra."x" != "x")
    {
        print '<td  width="20%" colspan="1" class="ui-widget-content">'.utf8_encode($commande->getNomUrl(1)).'</td>';
        print '<th class="ui-state-default ui-widget-header">Groupe</th>';
        print '<td colspan=2 class="ui-widget-content">'.$ret->getNomUrl(1).'</td>';

    } else {
        print '<td colspan="4" class="ui-widget-content">'.utf8_encode($commande->getNomUrl(1)).'</td>';
    }
    print '</tr>';

    // Ref commande client
    print '<tr><th nowrap="nowrap" class="ui-state-default ui-widget-header">';
    print $langs->trans('RefCustomer');
    print '</th><td colspan="4" class="ui-widget-content">';
    if ($user->rights->commande->creer && $_REQUEST['action'] == 'RefCustomerOrder')
    {
        print '<form action="fiche.php?id='.$id.'" method="post">';
        print '<input type="hidden" name="action" value="set_ref_client">';
        print '<input type="text" class="flat" size="20" name="ref_client" value="'.utf8_encode($commande->ref_client).'">';
        print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
        print '</form>';
    } else {
        print utf8_encode($commande->ref_client);
    }
    print '</td>';
    print '</tr>';


    // Societe
    $societe = new Societe($db);
    $societe->fetch($commande->socid);
    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Company').'</th>';
    print '<td colspan="4" class="ui-widget-content">'.utf8_encode($societe->getNomUrl(1)).'</td>';
    print '</tr>';

    // Date
    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Date').'</th>';
    print '<td class="ui-widget-content" colspan="4">'.utf8_encode(dol_print_date($commande->date,'day')).'</td>';
    print '</tr>';

    // Mode de reglement
    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Mode de r&eacute;glement').'</th>';
    print '<td class="ui-widget-content" colspan="4">'.utf8_encode($commande->mode_reglement).'</td>';
    print '</tr>';

    // Condition de paiement
    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Conditions de paiement').'</th>';
    print '<td class="ui-widget-content" colspan="4">'.utf8_encode($commande->cond_reglement_facture).'</td>';
    print '</tr>';


    //SI groupe de commande
    $arrGrpTmp = $commande->listGroupMember();

    if ($arrGrpTmp)
    {
        print '<tr><th colspan=5 class="ui-state-hover ui-widget-header">Groupe de commande</th>';
        $total_ht = 0;
        $total_tva = 0;
        $total_ttc = 0;
        // Total HT
        print '<tr><th style="padding: 5px;" colspan=5 class="ui-state-default ui-widget-header">'.$commande->ref.'</th>';
        print '<tr><th style="padding: 5px;" colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('AmountHT').'</th>';
        $total_ht += $commande->total_ht;
        print '<td style="padding: 5px;" colspan=2 class="ui-widget-content" align="right"><b>'.price($commande->total_ht).'</b></td>';
        print '<td style="padding: 5px;" class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

//require_once('Var_Dump.php');
//var_dump::Display($arrGrpTmp);
        foreach($arrGrpTmp as $key=>$val)
        {
            // Total HT
            $total_ht += $val->total_ht;
            print '<tr><th style="padding: 5px;" colspan=5 class="ui-state-default ui-widget-header">'.$val->ref.'</th>';
            print '<tr><th style="padding: 5px;" colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('AmountHT').'</th>';
            print '<td style="padding: 5px;" colspan=2 class="ui-widget-content" align="right"><b>'.price($val->total_ht).'</b></td>';
            print '<td style="padding: 5px;" class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

        }
        //Total groupe
        print '<tr><th colspan=5 class="ui-state-default ui-widget-header">Total groupe</th>';
        print '<tr><th colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('AmountHT').'</th>';
        print '<td colspan=2  class="ui-widget-content" align="right"><b>'.price($total_ht).'</b></td>';
        print '<td class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

        // Total TVA
        print '<tr><th colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('AmountVAT').'</th>
                   <td colspan=2 class="ui-widget-content" align="right">'.price($total_tva).'</td>';
        print '<td colspan=1  class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

        // Total TTC
        print '<tr><th colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('AmountTTC').'</th>
                   <td colspan=2 class="ui-widget-content" align="right">'.price($total_ttc).'</td>';
        print '<td colspan=1  class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';


    } else {
        // Total HT
        print '<tr><th colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('AmountHT').'</th>';
        print '<td colspan=2  class="ui-widget-content" align="right"><b>'.price($commande->total_ht).'</b></td>';
        print '<td class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

        // Total TVA
        print '<tr><th colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('AmountVAT').'</th>
                   <td colspan=2 class="ui-widget-content" align="right">'.price($commande->total_tva).'</td>';
        print '<td colspan=1 class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

        // Total TTC
        print '<tr><th colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('AmountTTC').'</th>
                   <td colspan=2 class="ui-widget-content" align="right">'.price($commande->total_ttc).'</td>';
        print '<td colspan=1  class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
    }
    print '<tr><th colspan=2 class="ui-state-default ui-widget-header">'.$langs->trans('Status').' financier</th>';
    $statusFin = "-";
    if ($commande->finance_ok == 1){
        $statusFin = 'OK';
    }else if($commande->finance_ok == 0){
        $statusFin = 'Non';
    }else if($commande->finance_ok == 2){
        $statusFin = 'Partiel';
    }
    if ($commande->finance_statut == 1)
    {
        $statusFin .= "&nbsp;&nbsp;&nbsp;<b>Valid&eacute;e</b>";
    } else {
        $statusFin .= "&nbsp;&nbsp;&nbsp;<b>Temporaire</b>";
    }

    print '<td class="ui-widget-content" colspan="4">'.$statusFin.'</td>';
    print '</tr>';


        if ($user->rights->SynopsisPrepaCom->financier->Modifier)
        {
            if ($commande->finance_statut == 1)
            {
                print "<tr class='ui-widget-header'>
                        <td colspan=8 align=center>
                        <button id='finance4a' class='butAction ui-corner-all ui-widget-header ui-state-default'>Modifier</button>
                      ";
            } else {
                print "<tr class='ui-widget-header'>
                        <td colspan=8 align=center>
                        <button id='finance3' class='butAction ui-corner-all ui-widget-header ui-state-default'>OK finance</button>
                        <button id='finance4' class='butAction ui-corner-all ui-widget-header ui-state-default'>Non finance</button>
                        <button id='finance2' class='butAction ui-corner-all ui-widget-header ui-state-default'>Valider</button>
                      ";
            }
        }
        print "</table>";

        print "<div id='valdialogFin' class='cntValdialogFin'>&Ecirc;tes vous sur de vouloir valider cette commande ?</div>";
        print "<div id='moddialogFin' class='cntModdialogFin'>&Ecirc;tes vous sur de vouloir invalider cette commande ?</div>";

print <<<EOF
<script>
jQuery(document).ready(function(){

    if(jQuery('.cntValdialogFin').length > 1){
        jQuery('#valdialogFin').dialog( "destroy" );
        jQuery('#valdialogFin').remove();
    }
    if(jQuery('.cntModdialogFin').length > 1){
        jQuery('#moddialogFin').dialog( "destroy" );
        jQuery('#moddialogFin').remove();
    }

        jQuery('#valdialogFin').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Validation de la financi&egrave;re",
            buttons: {
                Ok: function(){
                    //num Commande
                    //Statut Valid aka logistique_statut
                    jQuery.ajax({
                        url: 'ajax/xml/valFinance-xml_response.php',
                        datatype: "xml",
                        type: "POST",
                        data: "comId="+comId,
                        cache: false,
                        success:function(msg){
                            if(jQuery(msg).find('OK').length > 0)
                            {
                                jQuery('#OKdialogFin').dialog("close");
                                //reload
                                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                jQuery('#OKdialogFin').dialog( "destroy" );
                                jQuery('#OKdialogFin').remove();
                                jQuery.ajax({
                                    url: "ajax/finance-html_response.php",
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


        jQuery('#moddialogFin').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Modification de l'accord financier",
            buttons: {
                Ok: function(){
                    jQuery.ajax({
                        url: 'ajax/xml/devalFinance-xml_response.php',
                        datatype: "xml",
                        type: "POST",
                        data: "comId="+comId,
                        cache: false,
                        success:function(msg){
                            var res = jQuery(msg).find('result').text();
                            jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                            jQuery.ajax({
                                url: "ajax/finance-html_response.php",
                                data: "id="+comId,
                                cache: false,
                                datatype: "html",
                                type: "POST",
                                success: function(msg){
                                    jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                                    jQuery('#moddialogFin').dialog("close");
                                },
                            });
                        }
                    });
                },
                Annuler: function(){
                    jQuery(this).dialog("close");
                }
            }
        });

        jQuery('#finance3').click(function(){
            //ajax Direct OK + reload
                jQuery.ajax({
                    url: 'ajax/xml/OKFinance-xml_response.php',
                    datatype: "xml",
                    type: "POST",
                    data: "comId="+comId,
                    cache: false,
                    success:function(msg){
                        var res = jQuery(msg).find('result').text();
                        jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                        jQuery.ajax({
                            url: "ajax/finance-html_response.php",
                            data: "id="+comId,
                            cache: false,
                            datatype: "html",
                            type: "POST",
                            success: function(msg){
                                jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                            },
                        });
                    }
                });
        });
        jQuery('#finance4a').click(function(){
            jQuery('#moddialogFin').dialog('open');
        });
        jQuery('#finance4').click(function(){
            //ajax Direct KO + reload
                jQuery.ajax({
                    url: 'ajax/xml/KOFinance-xml_response.php',
                    datatype: "xml",
                    type: "POST",
                    data: "comId="+comId,
                    cache: false,
                    success:function(msg){
                        var res = jQuery(msg).find('result').text();
                                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                jQuery.ajax({
                                    url: "ajax/finance-html_response.php",
                                    data: "id="+comId,
                                    cache: false,
                                    datatype: "html",
                                    type: "POST",
                                    success: function(msg){
                                        jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                                    },
                                });
                    }
                });
        });
        jQuery('#finance2').click(function(){
            jQuery('#valdialogFin').dialog('open');
        });


});

</script>
EOF;

  }

?>

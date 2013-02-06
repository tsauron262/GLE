<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 13 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : fiche-xml_response.php
  * GLE-1.2
  */
    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
    require_once(DOL_DOCUMENT_ROOT ."/core/modules/commande/modules_commande.php");
    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
    require_once(DOL_DOCUMENT_ROOT."/core/lib/order.lib.php");
    if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
    if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
    if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');

    $langs->load('orders');
    $langs->load('sendings');
    $langs->load('companies');
    $langs->load('bills');
    $langs->load('propal');
    $langs->load("synopsisGene@Synopsis_Tools");
    $langs->load('deliveries');
    $langs->load('products');

    if (!$user->rights->commande->lire) accessforbidden();


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


    $html = new Form($db);
    $formfile = new FormFile($db);

    $id = $_REQUEST['id'];
    if ($id > 0)
    {
        $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
        print ' <script src="'.$jspath.'/jquery.jeditable.js" type="text/javascript"></script>';
        print <<<EOF
        <style>
        #notePublicEdit button{
            -moz-border-radius: 8px 8px 8px 8px;
            background-color: #0073EA;
EOF;
        print ' background-image: url("'.$conf->global->GLE_FULL_ROOT.'/Synopsis_Common/css/flick/images/ui-bg_highlight-soft_100_f6f6f6_1x100.png");';
        print <<<EOF
            background-repeat: repeat-x;
            border: 1px solid #0073EA;
            color: #fff;
            cursor: pointer;
            display: inline-block;
            font-family: 'Lucida Grande';
            font-size: 11px;
            font-style: normal;
            font-variant: normal;
            font-weight: bold;
            height: 30px;
            margin: 2px;
            min-width: 95px;
            padding: 5px 10px;
            text-align: center;
            text-transform: none;
        }

        #notePublicEdit button:hover{
            -moz-border-radius: 8px 8px 8px 8px;
            background-color: #F6F6F6;
EOF;
        print ' background-image: url("'.$conf->global->GLE_FULL_ROOT.'/Synopsis_Common/css/flick/images/ui-bg_highlight-soft_25_0073ea_1x100.png");';
        print <<<EOF
            background-repeat: repeat-x;
            border: 1px solid #DDDDDD;
            color: #0073EA;
            cursor: pointer;
            display: inline-block;
            font-family: 'Lucida Grande';
            font-size: 11px;
            font-style: normal;
            font-variant: normal;
            font-weight: bold;
            height: 30px;
            margin: 2px;
        }
        </style>
            <script>
                 jQuery('#notePublicEdit').editable('ajax/xml/notePublic-xmlresponse.php', {
                     type      : 'textarea',
                     cancel    : 'Annuler',
                     submit    : 'OK',
                     indicator : '<img src="img/ajax-loader.gif">',
                     tooltip   : 'Editer',
                     placeholder : 'Cliquer pour &eacute;diter',
                     onblur : 'cancel',
                     width: '95%',
                     height:"18em",
                     submitdata : {id: comId},
                     data : function(value, settings) {
                          var retval = value; //Global var
                          return retval;
                     },
                 });


            </script>
EOF;

        if ($mesg) print $mesg.'<br>';

        $commande = new Synopsis_Commande($db);
        if ( $commande->fetch($id) > 0)
        {
            $soc = new Societe($db);
            $soc->fetch($commande->socid);

            $author = new User($db);
            $author->fetch($commande->user_author_id);
            $nbrow=6;

            print '<table cellpadding=10 class="border" width="700">';

            // Ref
//            print '<tr><th width="18%" class="ui-state-default ui-widget-header">'.$langs->trans('Ref').'</th>';
//            print '<td colspan="4" class="ui-widget-content">'.utf8_encodeRien($commande->getNomUrl(1)).'</td>';
//            print '</tr>';

            // Ref
            $extra = "";
            $ret = $commande->isGroupMember();
            if ($ret){
                $extra = 'Membre du groupe : '.$ret->nom;
            }
            print '<tr><th width="18%" class="ui-state-default ui-widget-header">'.$langs->trans('Ref').'</th>';
            if ($extra."x" != "x")
            {
                print '<td  width="20%" colspan="1" class="ui-widget-content">'.utf8_encodeRien($commande->getNomUrl(1)).'</td>';
                print '<th class="ui-state-default ui-widget-header">Groupe</th>';
                print '<td colspan=2 class="ui-widget-content">'.$ret->getNomUrl(1).'</td>';

            } else {
                print '<td colspan="4" class="ui-widget-content">'.utf8_encodeRien($commande->getNomUrl(1)).'</td>';
            }
            print '</tr>';


            // Ref commande client
            print '<tr><th nowrap="nowrap" class="ui-state-default ui-widget-header">';
            print $langs->trans('RefCustomer');
            print '</th><td colspan="4" class="ui-widget-content">';
            print utf8_encodeRien($commande->ref_client);
            print '</td>';
            print '</tr>';


            // Societe
            print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Company').'</th>';
            print '<td colspan="4" class="ui-widget-content">'.utf8_encodeRien($soc->getNomUrl(1)).'</td>';
            print '</tr>';

            // Ligne info remises tiers
            print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Discounts').'</th>
                       <td colspan="4" class="ui-widget-content">';
            if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
            else print $langs->trans("CompanyHasNoRelativeDiscount");
            $absolute_discount=$soc->getAvailableDiscounts('','fk_facture_source IS NULL');
            $absolute_creditnote=$soc->getAvailableDiscounts('','fk_facture_source IS NOT NULL');
            print '. ';
            if ($absolute_discount)
            {
                if ($commande->statut > 0)
                {
                    print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->transnoentities("Currency".$conf->monnaie));
                }  else {
                    // Remise dispo de type non avoir
                    $filter='fk_facture_source IS NULL';
                    print '<br>';
                    $html->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$commande->id,0,'remise_id',$soc->id,$absolute_discount,$filter);
                }
            }
            if ($absolute_creditnote)
            {
                print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie)).'. ';
            }
            if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
            print '</td></tr>';

            // Date
            print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Date').'</th>';
            print '<td class="ui-widget-content" colspan="2">'.utf8_encodeRien(dol_print_date($commande->date,'day')).'</td>';
            print '<td class="ui-widget-content" colspan=2 width="50%">'.$langs->trans('Source').' : '.$commande->getLabelSource();
            if ($commande->source == 0 && $conf->propal->enabled && $commande->propale_id)
            {
                // Si source = propal
                $propal = new Propal($db);
                $propal->fetch($commande->propale_id);
                print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
            }
            print '</td>';
            print '</tr>';

            print '<tr><th height="10" class="ui-state-default ui-widget-header">';
            print $langs->trans('DeliveryDate');
            print '</th><td colspan="3" class="ui-widget-content">';
            print $commande->date_livraison ? htmlentities(utf8_decode(dol_print_date($commande->date_livraison,'day'))) : '&nbsp;';
            print '</td>';

                $arrGrpTmp = $commande->listGroupMember();
                if (! $user->rights->SynopsisPrepaCom->all->AfficherPrix)
                {
                    $nbrow-=3;
                } else {
                    if ($arrGrpTmp){ $nbrow += ( 2 * count($arrGrpTmp) + 4); }
                }


            //Note public
            print '<td class="ui-widget-content" rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br><div id="notePublicEdit" style="width: 290px; min-height: 19em; padding: 5px; overflow-y: hidden; color: rgb(0, 0, 0); background: none repeat scroll 0% 0% rgb(250, 229, 128); margin: 0px 3% 0px 1%;">';
            print utf8_encodeRien(nl2br($commande->note_public));
            print '</div></td>';

            print '</tr>';
print <<<EOF
<style>
#notePublicEdit { cursor: pointer; }
</style>
EOF;


            // Adresse de livraison
            print '<tr><th height="10" class="ui-state-default ui-widget-header">';
            print utf8_encodeRien($langs->trans('DeliveryAddress'));

            print '</th><td colspan="3" class="ui-widget-content">';

            print getAdresseLivraisonComm($commande->id);
//            print utf8_encodeRien( $html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_REQUEST['socid'],'none','commande',$commande->id,false));
            print '</td></tr>';



            if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
            {

                //SI groupe de commande
                if ($arrGrpTmp)
                {
                    print '<tr><th colspan=3 class="ui-state-hover ui-widget-header">Groupe de commande</th>';
                    $total_ht = 0;
                    $total_tva = 0;
                    $total_ttc = 0;
                    // Total HT
                    print '<tr><th style="padding: 5px;" colspan=3 class="ui-state-default ui-widget-header">'.$commande->ref.'</th>';
                    print '<tr><th style="padding: 5px;" class="ui-state-default ui-widget-header">'.$langs->trans('AmountHT').'</th>';
                    $total_ht += $commande->total_ht;
                    print '<td style="padding: 5px;" colspan=1 class="ui-widget-content" align="right"><b>'.price($commande->total_ht).'</b></td>';
                    print '<td  colspan=2 style="padding: 5px;" class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';


                    foreach($arrGrpTmp as $key=>$val)
                    {
                        // Total HT
                        $total_ht += $val->total_ht;
                        print '<tr><th style="padding: 5px;" colspan=3 class="ui-state-default ui-widget-header">'.$val->ref.'</th>';
                        print '<tr><th style="padding: 5px;" class="ui-state-default ui-widget-header">'.$langs->trans('AmountHT').'</th>';
                        print '<td style="padding: 5px;" colspan=1 class="ui-widget-content" align="right"><b>'.price($val->total_ht).'</b></td>';
                        print '<td  colspan=2 style="padding: 5px;" class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

                    }
                    //Total groupe
                    print '<tr><th colspan=3 class="ui-state-default ui-widget-header">Total groupe</th>';
                    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('AmountHT').'</th>';
                    print '<td colspan=1 class="ui-widget-content" align="right"><b>'.price($total_ht).'</b></td>';
                    print '<td  colspan=2 class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

                    // Total TVA
                    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('AmountVAT').'</th>
                               <td class="ui-widget-content" align="right">'.price($total_tva).'</td>';
                    print '<td colspan=2  class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

                    // Total TTC
                    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('AmountTTC').'</th>
                               <td class="ui-widget-content" align="right">'.price($total_ttc).'</td>';
                    print '<td colspan=2  class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';


                } else {
                    // Total HT
                    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('AmountHT').'</th>';
                    print '<td colspan=1 class="ui-widget-content" align="right"><b>'.price($commande->total_ht).'</b></td>';
                    print '<td colspan=2 class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

                    // Total TVA
                    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('AmountVAT').'</th><td class="ui-widget-content" align="right">'.price($commande->total_tva).'</td>';
                    print '<td colspan=2  class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

                    // Total TTC
                    print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('AmountTTC').'</th><td class="ui-widget-content" align="right">'.price($commande->total_ttc).'</td>';
                    print '<td colspan=2  class="ui-widget-content">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
                }
            }


            // Statut
            print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Status').'</th>';
            print '<td class="ui-widget-content" colspan="3">'.utf8_encodeRien($commande->getLibStatut(4)).'</td>';
            print '</tr>';

            print '</table><br>';
            print "\n";

            print '<table cellpadding=10 class="border" width="700">';
            $colspan = 4;
            if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
            {
                $colspan = 5;
            }
            if ($arrGrpTmp)
            {
                print '<tr><th colspan='.$colspan.' class="ui-state-hover ui-widget-header">Produits du groupe de commandes</th>';
            } else {
                print '<tr><th colspan='.$colspan.' class="ui-state-hover ui-widget-header">Produits de la commande</th>';
            }
            print "<tr><th class='ui-state-default ui-widget-header'>Libell&eacute;<th class='ui-state-default ui-widget-header'>Description";
            if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
            {
                print "     <th class='ui-state-default ui-widget-header'>PU HT";
            }
            print "     <th class='ui-state-default ui-widget-header'>Qt&eacute;";
            if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
            {
                print "    <th class='ui-state-default ui-widget-header'>Total HT";
            }
            if ($arrGrpTmp)
            {
                //ligne de la commande
                print '<tr><th style="padding: 5px;" colspan='.$colspan.' class="ui-state-default ui-widget-header">'.$commande->ref.'</th>';
                foreach($commande->liges as $key=>$val)
                {
                    $libelle = $val->libelle;
                    if ($val->fk_product > 0)
                    {
                        $tmpProd = new Product($db);
                        $tmpProd->fetch($val->fk_product);
                        $libelle = utf8_encodeRien($tmpProd->getNomUrl(1));
                    }
                    print "<tr><td class='ui-widget-content' nowrap>".$libelle."<td class='ui-widget-content'>".utf8_encodeRien($val->desc);
                    if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
                    {
                        print "    <td class='ui-widget-content' align=right nowrap>".price($val->subprice);
                    }
                    print "    <td class='ui-widget-content' nowrap>".$val->qty;
                    if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
                    {
                        print "    <td align=right class='ui-widget-content' nowrap>".price($val->total_ht);
                    }

                }
                //ligne du groupe
                foreach($arrGrpTmp as $key1=>$val1)
                {
                    print '<tr><th style="padding: 5px;" colspan='.$colspan.' class="ui-state-default ui-widget-header">'.$val1->ref.'</th>';
                    //Lignes de commande
                    foreach($val1->lines as $key=>$val)
                    {
                        $libelle = $val->libelle;
                        if ($val->fk_product > 0)
                        {
                            $tmpProd = new Product($db);
                            $tmpProd->fetch($val->fk_product);
                            $libelle = utf8_encodeRien($tmpProd->getNomUrl(1));
                        }
                        print "<tr><td class='ui-widget-content' nowrap>".$libelle."<td class='ui-widget-content'>".utf8_encodeRien($val->desc);
                        if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
                        {
                            print "    <td class='ui-widget-content' align=right nowrap>".price($val->subprice);
                        }
                        print "    <td class='ui-widget-content' nowrap>".$val->qty;
                        if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
                        {
                            print "    <td align=right class='ui-widget-content' nowrap>".price($val->total_ht);
                        }
                    }
               }

            } else {

                //Lignes de commande
                foreach($commande->lines as $key=>$val)
                {
                    $libelle = $val->libelle;
                    if (!$val->fk_product>0 && $val->qty == 0 && $val->subprice == 0)
                    {
                        $colspan=3;
                        if (!$user->rights->SynopsisPrepaCom->all->AfficherPrix) $colspan=1;

                        print "<tr><td class='ui-widget-content' nowrap>&nbsp;<td class='ui-widget-content' colspan=1>".utf8_encodeRien($val->desc)."<td colspan='".$colspan."' class='ui-widget-content'>";
                    } else {
                        if ($val->fk_product > 0)
                        {
                            $tmpProd = new Product($db);
                            $tmpProd->fetch($val->fk_product);
                            $libelle = utf8_encodeRien($tmpProd->getNomUrl(1));
                        }
                        print "<tr><td class='ui-widget-content' nowrap>".$libelle."<td class='ui-widget-content'>".utf8_encodeRien($val->desc);
                        if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
                        {
                            print "    <td class='ui-widget-content' align=right nowrap>".price($val->subprice);
                        }
                        print "    <td class='ui-widget-content' align=center nowrap>".$val->qty;
                        if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
                        {
                            print "    <td align=right class='ui-widget-content' nowrap>".price($val->total_ht);
                        }

                    }

                }

            }
          print '</table>';
            print "<br/>";
            print '<table cellpadding=10 class="border" width="700">';
            print '<tr><td class="ui-widget-header" align="right"><button onClick="location.href=\''.DOL_URL_ROOT.'/commande/fiche.php?id='.$id.'\'" class="butAction">Modifier</button>';
            print '</table>';
        }
    }
?>
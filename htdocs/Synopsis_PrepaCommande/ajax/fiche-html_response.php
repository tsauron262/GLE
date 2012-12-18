<?php

/*
 * * GLE by Synopsis et DRSI
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
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT . '/Synopsis_Tools/commandeGroup/commandeGroup.class.php');
require_once(DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/order.lib.php");
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php');
if ($conf->propal->enabled)
    require_once(DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load("synopsisGene@Synopsis_Tools");
$langs->load('deliveries');
$langs->load('products');

if (!$user->rights->commande->lire)
    accessforbidden();


// Securite acces client
$socid = 0;
if ($user->societe_id > 0) {
    $socid = $user->societe_id;
}
if ($user->societe_id > 0 && isset($_GET["id"]) && $_GET["id"] > 0) {
    $commande = new Synopsis_Commande($db);
    $commande->fetch((int) $_GET['id']);
    if ($user->societe_id != $commande->socid) {
        accessforbidden();
    }
}


$html = new Form($db);
$formfile = new FormFile($db);

$id = $_REQUEST['id'];
if ($id > 0) {
    $jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery";
    print ' <script src="' . $jspath . '/jquery.jeditable.js" type="text/javascript"></script>';
    print <<<EOF
        <style>
        #notePublicEdit button{
            -moz-border-radius: 8px 8px 8px 8px;
            background-color: #0073EA;
EOF;
    print ' background-image: url("' . $conf->global->GLE_FULL_ROOT . '/Synopsis_Common/css/flick/images/ui-bg_highlight-soft_100_f6f6f6_1x100.png");';
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
    print ' background-image: url("' . $conf->global->GLE_FULL_ROOT . '/Synopsis_Common/css/flick/images/ui-bg_highlight-soft_25_0073ea_1x100.png");';
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

    if ($mesg)
        print $mesg . '<br>';

    $commande = new Synopsis_Commande($db);
    if ($commande->fetch($id) > 0) {
        $soc = new Societe($db);
        $soc->fetch($commande->socid);

        $author = new User($db);
        $author->fetch($commande->user_author_id);
        $nbrow = 8;
        if ($conf->projet->enabled)
            $nbrow++;

        print '<table class="border" width="700" cellpadding=10>';

        // Ref
        $extra = "";
        $ret = $commande->isGroupMember();
        if ($ret) {
            $extra = 'Membre du groupe : ' . $ret->nom;
        }
        print '<tr><th width="18%" class="ui-state-default ui-widget-header">' . $langs->trans('Ref') . '</th>';
        if ($extra . "x" != "x") {
            print '<td  width="20%" colspan="1" class="ui-widget-content">' . traiteStr($commande->getNomUrl(1)) . '</td>';
            print '<th class="ui-state-default ui-widget-header">Groupe</th>';
            print '<td colspan=2 class="ui-widget-content">' . $ret->getNomUrl(1) . '</td>';
        } else {
            print '<td colspan="4" class="ui-widget-content">' . traiteStr($commande->getNomUrl(1)) . '</td>';
        }
        print '</tr>';

        print '<tr><th nowrap="nowrap" class="ui-state-default ui-widget-header">';
        print $langs->trans('Commercial cde');
        print '</th><td colspan="2" class="ui-widget-content">';
        $tmpUser = new User($db);
        $tmpUser->fetch($commande->user_author_id);

        print traiteStr($tmpUser->getNomUrl(1));
        print '</td>';

        // Ref commande client
        print '<th nowrap="nowrap" class="ui-state-default ui-widget-header">';
        print $langs->trans('RefCustomer');
        print '</th><td colspan="1" class="ui-widget-content">';
        if ($user->rights->commande->creer && $_REQUEST['action'] == 'RefCustomerOrder') {
            print '<form action="fiche.php?id=' . $id . '" method="post">';
            print '<input type="hidden" name="action" value="set_ref_client">';
            print '<input type="text" class="flat" size="20" name="ref_client" value="' . traiteStr($commande->ref_client) . '">';
            print ' <input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print traiteStr($commande->ref_client);
        }
        print '</td>';
        print '</tr>';


        // Societe
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Company') . '</th>';
        print '<td colspan="4" class="ui-widget-content">' . traiteStr($soc->getNomUrl(1)) . '</td>';
        print '</tr>';

        // Date
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Date') . '</th>';
        print '<td class="ui-widget-content" colspan="2">' . traiteStr(dol_print_date($commande->date, 'day')) . '</td>';
        print '<td class="ui-widget-content" colspan=2 width="50%">' . $langs->trans('Source') . ' : ' . $commande->getLabelSource();
        if ($commande->source == 0 && $conf->propal->enabled && $commande->propale_id) {
            // Si source = propal
            $propal = new Propal($db);
            $propal->fetch($commande->propale_id);
            print ' -> <a href="' . DOL_URL_ROOT . '/comm/propal.php?propalid=' . $propal->id . '">' . traiteStr($propal->ref) . '</a>';
        }
        print '</td>';
        print '</tr>';

        // Date de livraison
        if ($conf->expedition->enabled) {
            print '<tr><th height="10" class="ui-state-default ui-widget-header">';
            print $langs->trans('DeliveryDate');
            print '</th><td colspan="3" class="ui-widget-content">';
            if ($_REQUEST['action'] == 'editdate_livraison') {
                print '<form name="setdate_livraison" action="' . $_SERVER["PHP_SELF"] . '?id=' . $commande->id . '" method="post">';
                print '<input type="hidden" name="action" value="setdate_livraison">';
                $html->select_date($commande->date_livraison, 'liv_', '', '', '', "setdate_livraison");
                print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
                print '</form>';
            } else {
                print $commande->date_livraison ? htmlentities(utf8_decode(dol_print_date($commande->date_livraison, 'day'))) : '&nbsp;';
            }
            print '</td>';

            $arrGrpTmp = $commande->listGroupMember();
            if (!$user->rights->SynopsisPrepaCom->all->AfficherPrix) {
                $nbrow-=3;
            } else {
                if ($arrGrpTmp) {
                    $nbrow += ( 2 * count($arrGrpTmp) + 4);
                }
            }

            print '<td class="ui-widget-content" rowspan="' . $nbrow . '" valign="top">' . $langs->trans('NotePublic') . ' :<br/>
                           <div style="width: 95%; min-height: 11em; height: 28em; padding: 5px; overflow-y: hidden; color: rgb(0, 0, 0); background: none repeat scroll 0% 0% rgb(250, 229, 128); margin: 0px 3% 0px 1%;" id="notePublicEdit">';
            print traiteStr(nl2br($commande->note_public));
            print '</div></td>';
            print '</tr>';
            print <<<EOF
<style>
#notePublicEdit { cursor: pointer; }
</style>
EOF;
            // Adresse de livraison
            print '<tr><th height="10" class="ui-state-default ui-widget-header">';
            print $langs->trans('DeliveryAddress');
            print '</td>';

            print '</th><td colspan="3" class="ui-widget-content">';
            if ($_REQUEST['action'] == 'editdelivery_adress') {
//                    print traiteStr($html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_REQUEST['socid'],'adresse_livraison_id','commande',$commande->id,false));
                print getAdresseLivraisonComm($commande->id);
            } else {
//                    print traiteStr($html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_REQUEST['socid'],'none','commande',$commande->id,false));
                print getAdresseLivraisonComm($commande->id);
            }
            print '</td></tr>';
        }

        print "<tr>";

        print '</tr>';
        // Lignes de 3 colonnes

        if ($user->rights->SynopsisPrepaCom->all->AfficherPrix) {

            //SI groupe de commande
            if ($arrGrpTmp) {
                print '<tr><th colspan=4 class="ui-state-hover ui-widget-header">Groupe de commande</th>';
                $total_ht = 0;
                $total_tva = 0;
                $total_ttc = 0;
                // Total HT
                print '<tr><th style="padding: 5px;" colspan=4 class="ui-state-default ui-widget-header">' . $commande->ref . '</th>';
                print '<tr><th style="padding: 5px;" class="ui-state-default ui-widget-header">' . $langs->trans('AmountHT') . '</th>';
                $total_ht += $commande->total_ht;
                print '<td style="padding: 5px;" colspan=1 class="ui-widget-content" align="right"><b>' . price($commande->total_ht) . '</b></td>';
                print '<td colspan=2 style="padding: 5px;" class="ui-widget-content">' . $langs->trans('Currency' . $conf->monnaie) . '</td></tr>';


                foreach ($arrGrpTmp as $key => $val) {
                    // Total HT
                    $total_ht += $val->total_ht;
                    print '<tr><th style="padding: 5px;" colspan=4 class="ui-state-default ui-widget-header">' . $val->ref . '</th>';
                    print '<tr><th style="padding: 5px;" class="ui-state-default ui-widget-header">' . $langs->trans('AmountHT') . '</th>';
                    print '<td style="padding: 5px;" colspan=1 class="ui-widget-content" align="right"><b>' . price($val->total_ht) . '</b></td>';
                    print '<td colspan=2  style="padding: 5px;" class="ui-widget-content">' . $langs->trans('Currency' . $conf->monnaie) . '</td></tr>';
                }
                //Total groupe
                print '<tr><th colspan=4 class="ui-state-default ui-widget-header">Total groupe</th>';
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('AmountHT') . '</th>';
                print '<td colspan=1 class="ui-widget-content" align="right"><b>' . price($total_ht) . '</b></td>';
                print '<td colspan=2  class="ui-widget-content">' . $langs->trans('Currency' . $conf->monnaie) . '</td></tr>';

                // Total TVA
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('AmountVAT') . '</th>
                               <td class="ui-widget-content" align="right">' . price($total_tva) . '</td>';
                print '<td colspan=2 class="ui-widget-content">' . $langs->trans('Currency' . $conf->monnaie) . '</td></tr>';

                // Total TTC
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('AmountTTC') . '</th>
                               <td class="ui-widget-content" align="right">' . price($total_ttc) . '</td>';
                print '<td colspan=2  class="ui-widget-content">' . $langs->trans('Currency' . $conf->monnaie) . '</td></tr>';
            } else {
                // Total HT
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('AmountHT') . '</th>';
                print '<td colspan=1 class="ui-widget-content" align="right"><b>' . price($commande->total_ht) . '</b></td>';
                print '<td colspan=2 class="ui-widget-content">' . $langs->trans('Currency' . $conf->monnaie) . '</td></tr>';

                // Total TVA
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('AmountVAT') . '</th><td class="ui-widget-content" align="right">' . price($commande->total_tva) . '</td>';
                print '<td colspan=2  class="ui-widget-content">' . $langs->trans('Currency' . $conf->monnaie) . '</td></tr>';

                // Total TTC
                print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('AmountTTC') . '</th><td class="ui-widget-content" align="right">' . price($commande->total_ttc) . '</td>';
                print '<td colspan=2  class="ui-widget-content">' . $langs->trans('Currency' . $conf->monnaie) . '</td></tr>';
            }
        }

        // Statut
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Status') . '</th>';
        print '<td class="ui-widget-content" colspan="3">' . $commande->getLibStatut(4) . '</td>';
        print '</tr>';

        // Statut logistique
        $statusLog = "-";
        if ($commande->logistique_ok == 1) {
            $statusLog = 'OK';
        } else if ($commande->logistique_ok == 0) {
            $statusLog = 'KO';
        } else if ($commande->logistique_ok == 2) {
            $statusLog = 'Partiel';
        }

        if ($commande->logistique_statut == 1) {
            $statusLog .= "&nbsp;&nbsp;&nbsp;<b>Valid&eacute;</b>";
        } else {
            $statusLog .= "&nbsp;&nbsp;&nbsp;<b>Temporaire</b>";
        }

        $dateDispo = "";
        $weekDispo = "";
        if ($commande->logistique_ok != 1) {
            $dateDispo = ($commande->logistique_date_dispo . 'x' == 'x' ? '' : date('d/m/Y', strtotime($commande->logistique_date_dispo)));
            $weekDispo = ($commande->logistique_date_dispo . 'x' == 'x' ? '' : date('W', strtotime($commande->logistique_date_dispo)));
        }

        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Status') . ' logistique</th>';
        if ($conf->global->PREPACOMMANDE_SHOW_WEEK_WHEN_TEMPORARY) {
            print '<td class="ui-widget-content" colspan="3">' . $statusLog . '&nbsp;' . ($dateDispo . "x" == "x" ? "" : "Dispo semaine " . $weekDispo) . '</td>';
        } else {
            print '<td class="ui-widget-content" colspan="3">' . $statusLog . '&nbsp;' . ($dateDispo . "x" == "x" ? "" : "Dispo le " . $dateDispo) . '</td>';
        }
        print '</tr>';
        // Statut financier
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Status') . ' financier</th>';
        $statusFin = "-";
        if ($commande->finance_ok == 1) {
            $statusFin = 'OK';
        } else if ($commande->finance_ok == 0) {
            $statusFin = 'KO';
        } else if ($commande->finance_ok == 2) {
            $statusFin = 'Partiel';
        }
        if ($commande->finance_statut == 1) {
            $statusFin .= "&nbsp;&nbsp;&nbsp;<b>Valid&eacute;</b>";
        } else {
            $statusFin .= "&nbsp;&nbsp;&nbsp;<b>Temporaire</b>";
        }

        print '<td class="ui-widget-content" colspan="3">' . $statusFin . '</td>';
        print '</tr>';
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Status') . ' Exp&eacute;dition</th>';
//            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_exp WHERE fk_commande = ".$id;
//            $sql = $db->query($requete);
        require_once(DOL_DOCUMENT_ROOT . "/expedition/class/expedition.class.php");
        $tabExpe = getElementElement("commande", "shipping", $id);

//            if ($db->num_rows($sql) == 1)
        if (count($tabExpe) == 1) {
//                $res = $db->fetch_object($sql);
            $exp = new expedition($db);
            $exp->fetch($tabExpe[0]['d']);
            print '<td class="ui-widget-content" colspan="2">';
            print $exp->getNomUrl(1);
            print '<td class="ui-widget-content" colspan="2">';
            print $exp->getLibStatut(4);
//            } else if ($db->num_rows($sql) > 0)
        } else if (count($tabExpe) > 0) {
            print '<td class="ui-widget-content" colspan="4" style="padding: 0px; border:0px Solid;">';
            print "<table width=100% cellpadding=5>";
//                while($res = $db->fetch_object($sql))
            for ($iter = 0; $iter < count($tabExpe) - 1; $iter++) {
                $exp = new expedition($db);
                $exp->fetch($tabExpe[$iter]['d']);
                if ($iter % 2 != 1)
                    print "<tr>";
                print "<td class='ui-widget-content'>" . $exp->getNomUrl(1);
                print "<td class='ui-widget-content'>" . $exp->getLibStatut(4);
            }
            if ($iter % 2 == 1)
                print "<td colspan=2 class='ui-widget-content'>&nbsp;";
            print "</table>";
        } else {
            print '<td class="ui-widget-content" colspan="4">';
            print "Pas d'exp&eacute;dition planifi&eacute;e";
        }

        // Statut BIMP
//            $requete = "SELECT * FROM BIMP_commande_status WHERE commande_refid = ".$id;
//            $sql = $db->query($requete);
        $tabStat = getElementElement("commande", "statutS", $id);
        if (!isset($tabStat[0])) {
            setElementElement("commande", "statutS", $id, 1);
            $statusBIMP = 1;
        }
        else
            $statusBIMP = $tabStat[0]['d'];


//            $res=$db->fetch_object($sql);
//            $statusBIMP = $res->statut_refid;
//        if ($res->statut_refid . "x" == "x") {
//            $requete = "INSERT INTO BIMP_commande_status (commande_refid, statut_refid) VALUES (" . $id . ",1)";
//            $sql = $db->query($requete);
//            $statusBIMP = 1;
//        }
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_c_commande_status WHERE id =  " . $statusBIMP;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Status') . ' BIMP</th>';
        print '<td class="ui-widget-content" colspan="4">' . traiteStr($res->label);
        if ($user->rights->SynopsisPrepaCom->all->ModifierEtat)
            print '  <span onClick="openBIMPStatusDial();">' . img_edit() . '</span>';
        print '</td>';
        print '</tr>';


        print '</table><br>';
        print "\n";
    }
}
print '<div id="bimpStatusDial">';
$requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_c_commande_status";
$sql = $db->query($requete);
print "<select id='newStatus'>";
while ($res = $db->fetch_object($sql)) {
    print "<option value='" . $res->id . "'>" . traiteStr($res->label) . "</option>";
}
print "</select>";
print "";
print "</div>";
print <<<EOF
<script>
    function openBIMPStatusDial()
    {
        jQuery('#bimpStatusDial').dialog('open');
    }
    jQuery(document).ready(function(){
        jQuery('#bimpStatusDial').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            show: 'slide',
            title: "Modification du statut global",
            buttons: {
                Ok: function(){
                    var statut = jQuery('#bimpStatusDial').find(':selected').val();
                    var self=this;
                    jQuery.ajax({
                        url: "ajax/xml/BIMPStatus-xml_response.php",
                        datatype:"xml",
                        data:"statut="+statut+"&id="+comId,
                        type:"POST",
                        success: function(msg){
                            location.href='prepacommande.php?id='+comId;
                        }
                    });
                    jQuery(this).dialog("close");
                },
                Annuler: function(){
                    jQuery(this).dialog("close");
                }
            }
        });
    });
</script>
EOF;


function traiteStr($str){return $str;}
?>
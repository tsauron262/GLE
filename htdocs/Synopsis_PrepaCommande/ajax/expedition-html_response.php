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
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/sendings.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php");
$msg = "";
$GROUP_COMMANDE = true;

if (isset($_REQUEST['nd']) && $_REQUEST['nd'] . "x" != "x" && isset($_REQUEST['action']) && $_REQUEST['action'] == "setDateLiv") {
    if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/', $_REQUEST['nd'], $arrRegEx)) {
        $dateLiv = $arrRegEx[3] . "-" . $arrRegEx[2] . "-" . $arrRegEx[1];
        //date_livraison
        $requete = "UPDATE " . MAIN_DB_PREFIX . "commande SET date_livraison ='" . $dateLiv . "' WHERE rowid = " . $_REQUEST['id'];
        $db->query($requete);
    }
}
if (isset($_REQUEST['action']) && $_REQUEST['action'] == "setDepot" && isset($_REQUEST['nd']) && $_REQUEST['nd'] . 'x' != "x") {
    if ($_REQUEST["id"] > 0) {
        $commande = new Synopsis_Commande($db);
        if ($commande->fetch($_REQUEST["id"]) > 0) {
            setElementElement('commande', 'entrepot' . $_REQUEST["numDep"], $_REQUEST["id"], $_REQUEST["nd"]);
        }
    }
}
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'notifyExped' && $_REQUEST["id"] > 0) {
    //sendmail
    require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
    $commande = new Synopsis_Commande($db);
    $commande->fetch($_REQUEST['id']);

    $to = '';
//    $requete = "SELECT email FROM BIMP_site as s , BIMP_site_commande as sc WHERE sc.site_id = s.id AND  fk_commande = " . $_REQUEST['id'];
    $tabEntrep = getElementElement('commande', 'entrepot', $commande->id);
    $tabEntrep = array_merge($tabEntrep, getElementElement('commande', 'entrepot0', $commande->id));
    $tabEntrep = array_merge($tabEntrep, getElementElement('commande', 'entrepot1', $commande->id));
    $tabEntrep = array_merge($tabEntrep, getElementElement('commande', 'entrepot2', $commande->id));
    $tabEntrep = array_merge($tabEntrep, getElementElement('commande', 'entrepot3', $commande->id));
    $tabEntrep = array_merge($tabEntrep, getElementElement('commande', 'entrepot4', $commande->id));
    if (isset($tabEntrep[0])) {
        $idEntr = $tabEntrep[0]['d'];
        $requete = "SELECT description as email FROM " . MAIN_DB_PREFIX . "entrepot WHERE rowid = " . $idEntr;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);

//              $tmpUser = new User($db);
//              $tmpUser->fetch($res->email);
        //Notification
        //TO service depositaire
        //CC Resp Tech
        $to = $res->email;
    }

    // Appel des triggers
    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
    $interface = new Interfaces($db);
    $result = $interface->run_triggers('PREPACOM_INTERNAL_EXPEDITION_SENDMAIL', $this, $user, $langs, $conf);
//    if ($result < 0) {
//        $error++;
//        $this->errors = $interface->errors;
//    }
    // Fin appel triggers

    $subject = "[Preparation Matériel] Nouveau message concernant le matériel de la commande " . $commande->ref;

    $msg = "Bonjour,<br/><br/>";
    $msg .= "Le mat&eacute;riel de la commande " . $commande->getNomUrl(1) . " vous a &eacute;t&eacute; exp&eacute;di&eacute;.";

    $msg .= "<br/><br/>Cordialement,<br/>\nGLE\n";
    $from = $conf->global->BIMP_MAIL_FROM;
    $addr_cc = $conf->global->BIMP_MAIL_GESTPROD;

//    require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
//    $result = sendMail($subject, $to, $from, $msg, array(), array(), array(), $addr_cc, '', 0, 1, $from);

    $result = mailSyn($to, $subject, $msg);
    if ($result)
        $msg = "Le mail a &eacute;t&eacute; envoy&eacute;" . $to;
    $tabExpe = getElementElement("commande", "shipping", $commande->id);
    $tabExp = array();
    foreach ($tabExpe as $elem)
        $tabExp[] = $elem['d'];
    $requete = "UPDATE " . MAIN_DB_PREFIX . "expedition SET fk_statut = 2 WHERE fk_statut = 1 AND  rowid in (" . implode(", ", $tabExp) . ")";
    $sql = $db->query($requete);
}

$requete = "SELECT fk_soc FROM " . MAIN_DB_PREFIX . "commande WHERE rowid = " . $_REQUEST['id'];
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$socid = $res->fk_soc;

$langs->load("companies");
$langs->load("sendings");
$langs->load("deliveries");
$langs->load("commercial");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("banks");

// Security check
//$socid = isset($_REQUEST["socid"])?$_REQUEST["socid"]:'';
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'societe', '', '');


$html = new Form($db);
$formfile = new FormFile($db);

if ($msg . "x" != "x")
    print "<div class='ui-state-highlight' style='padding:3px;'><span style='float:left' class='ui-icon ui-icon-info'></span>" . $msg . "</div>";

/*
 *    View
 */
if ($_REQUEST["id"] > 0) {
    $commande = new Synopsis_Commande($db);
    if ($commande->fetch($_REQUEST["id"]) > 0) {
        $commande->loadExpeditions(1);

        $soc = new Societe($db);
        $soc->fetch($commande->socid);

        $author = new User($db);
        $author->fetch($commande->user_author_id);


        // Onglet commande
        $nbrow = 2;

        print '<table cellpadding=15 class="border" width="100%">';

//TODO checker si pas d'entrepot
        // Date de prepa
        print '<tr><th height="10"  class="ui-widget-header ui-state-default">';
        print $langs->trans('DeliveryDate');
        print '<a href="#pppart7a" onClick="changeDateLivraison();">' . img_edit($langs->trans('Site BIMP'), 1) . "</a>";
        print '</th><td colspan="2"  class="ui-widget-content">';
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'editdate_livraison') {
            //print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'" method="post">';
            print "<input type='text' class='datePicker' id='livDate' name='livDate'>";
            print '<button onClick="validateDateLiv();" class="butAction" >OK</button>';
            //print '</form>';
        } else {
            print htmlentities(utf8_decode(dol_print_date($commande->date_livraison, 'day')));
        }
        print '</td>';


        // Deposer a
        for ($i = 0; $i < 3; $i++) {
            print '<tr><th height="10"  class="ui-widget-header ui-state-default">';
            print $langs->trans('D&eacute;poser &agrave;');
            print '<a href="#pppart7a" onClick="changeSiteDepot(' . $i . ');">' . img_edit($langs->trans('Site BIMP'), 1) . "</a>";
            print '</th><td align=center colspan="1" width=40% class="ui-widget-content">';
            $tabEntrep = getElementElement('commande', 'entrepot' . $i, $commande->id);
            $idEntr = 0;
            if (isset($tabEntrep[0]))
                $idEntr = $tabEntrep[0]['d'];
            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'editDepot' . $i) {
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "entrepot ORDER BY lieu";
                print "<select name='newDepot' id='newDepot'>";
                $sql6 = $db->query($requete);
                while ($res6 = $db->fetch_object($sql6)) {
                    print "<option value='" . $res6->rowid . "'" . (($res6->rowid == $idEntr) ? " selected='selected'" : '') . ">" . traite_str($res6->lieu) . "</option>";
                }
                print "</select>";
                print "<button onClick='validateDepot(" . $i . ");' class='butAction'>OK</button>";
            } else {
//            $tabEntrep = getElementElement('comm', 'entrepot', $commande->id);
                if ($idEntr) {
                    $requete = "SELECT lieu FROM " . MAIN_DB_PREFIX . "entrepot WHERE rowid = " . $idEntr;
                    $sql6 = $db->query($requete);
                    $res6 = $db->fetch_object($sql6);
                    if ($res6->lieu . "x" != 'x')
                        print traite_str($res6->lieu);
                }
            }
        }
        print '</td>';
        print '<td align=center colspan="1"  class="ui-widget-content">';
        print "<button id='notifyExped' class='butAction'>Notifier de l'exp&eacute;dition</button>";
        print <<<EOF
<script>
jQuery(document).ready(function(){

        jQuery('#notifyExped').click(function(){
            jQuery.ajax({
                url: "ajax/expedition-html_response.php?action=notifyExped",
                data: "id="+comId,
                cache: false,
                datatype: "html",
                type: "POST",
                success: function(msg){
                    jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                },

            });
        });

        jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                        dateFormat: 'dd/mm/yy',
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        buttonImage: 'cal.png',
                        buttonImageOnly: true,
                        showTime: false,
                        duration: '',
                        constrainInput: false,}, jQuery.datepicker.regional['fr']));
        jQuery('.datePicker').datepicker();
});

function validateDepot(numDep){
    var nd = jQuery('#newDepot').find(':selected').val();
    jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
    jQuery.ajax({
        url: "ajax/expedition-html_response.php?action=setDepot&nd="+nd+"&id="+comId+"&numDep="+numDep,
        data: "id="+comId,
        cache: false,
        datatype: "html",
        type: "POST",
        success: function(msg){
            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
        },
    });
}
function validateDateLiv(){
    var nd = jQuery('#livDate').val();
    jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
    jQuery.ajax({
        url: "ajax/expedition-html_response.php?action=setDateLiv&nd="+nd+"&id="+comId,
        data: "id="+comId,
        cache: false,
        datatype: "html",
        type: "POST",
        success: function(msg){
            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
        },
    });
}
function changeDateLivraison(){
    jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
    jQuery.ajax({
        url: "ajax/expedition-html_response.php?action=editdate_livraison",
        data: "id="+comId,
        cache: false,
        datatype: "html",
        type: "POST",
        success: function(msg){
            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
        },
    });

}

function changeSiteDepot(nb){
    jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
    jQuery.ajax({
        url: "ajax/expedition-html_response.php?action=editDepot"+nb,
        data: "id="+comId,
        cache: false,
        datatype: "html",
        type: "POST",
        success: function(msg){
            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
        },
    });

}
</script>
EOF;

        // Adresse de livraison
        print '<tr><th width=20% height="10" class="ui-widget-header ui-state-default">';
        print $langs->trans('DeliveryAddress');

        print '</th><td colspan="2" class="ui-widget-content">';

//        if ($_REQUEST['action'] == 'editdelivery_adress') {
//            print traite_str($html->form_adresse_livraison($_SERVER['PHP_SELF'] . '?id=' . $commande->id, $commande->adresse_livraison_id, $_REQUEST['socid'], 'adresse_livraison_id', 'commande', $commande->id, false));
//        } else {
//            print traite_str($html->form_adresse_livraison($_SERVER['PHP_SELF'] . '?id=' . $commande->id, $commande->adresse_livraison_id, $_REQUEST['socid'], 'none', 'commande', $commande->id, false));
//        }
        print getAdresseLivraisonComm($commande->id);
        print '</td>';
        print '</table><br>';


        /**
         *  Lignes   de commandes avec quantite livrees et reste a livrer  Les
         * quantites livrees sont stockees dans $commande->expeditions[fk_product]
         */
        print '<table class="liste" width="100%">';
        if ($GROUP_COMMANDE)
            $arrGrpTmp = $commande->listGroupMember(false);
        if(!$GROUP_COMMANDE || count($arrGrpTmp) == 0)
            $arrGrpTmp = array($commande->id => $commande);


        $sql = "SELECT cd.fk_product, cd.description, cd.price, cd.qty as qty, cd.rowid, cd.tva_tx, cd.subprice";
        $sql.= " FROM " . MAIN_DB_PREFIX . "commandedet as cd ";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON cd.fk_product = p.rowid";
        $arrTmp = array();
        foreach ($arrGrpTmp as $key => $val)
            $arrTmp[] = $val->id;
        $sql.= " WHERE cd.fk_commande IN (" . join(",", $arrTmp) . ")";
        $sql.= " AND p.fk_product_type = 0 ";
//        $sql.= " GROUP BY cd.fk_product";
        $sql.= " ORDER BY cd.rowid";

        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Description") . '</td>';
            print '<td align="center">' . $langs->trans("QtyOrdered") . '</td>';
            print '<td align="center">' . $langs->trans("QtyShipped") . '</td>';
            print '<td align="center">' . $langs->trans("KeepToShip") . '</td>';
            if ($conf->stock->enabled) {
                print '<td align="center">' . $langs->trans("Stock") . '</td>';
            } else {
                print '<td>&nbsp;</td>';
            }
            print "</tr>\n";

            $var = true;
            $reste_a_livrer = array();
            $reste_a_livrer_total = 0;
            while ($i < $num) {
                $objp = $db->fetch_object($resql);
                if ($objp->qty == 0) {
                    $i++;
                    continue;
                }

                $var = !$var;
                print "<tr $bc[$var]>";
                if ($objp->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($objp->fk_product);
                    print '<td>';
                    print '<a href="' . DOL_URL_ROOT . '/product/card.php?id=' . $objp->fk_product . '">';
                    print img_object($langs->trans("Product"), "product") . ' ' . $product->ref . '</a>';
                    print traite_str($product->libelle ? ' - ' . $product->libelle : '');
                    print '</td>';
                } else {
                    print "<td>" . traite_str(nl2br($objp->description)) . "</td>\n";
                }

                print '<td align="center">' . $objp->qty . '</td>';

                print '<td align="center">';
                $quantite_livree = 0;
                foreach ($arrGrpTmp as $commT) {
                    $commT->loadExpeditions(1);
                    $quantite_livree += $commT->expeditions[$objp->rowid];
                }
                print $quantite_livree;
                print '</td>';

                $reste_a_livrer[$objp->fk_product] = $objp->qty - $quantite_livree;
                $reste_a_livrer_total = $reste_a_livrer_total + $reste_a_livrer[$objp->fk_product];
                print '<td align="center">';
                print $reste_a_livrer[$objp->fk_product];
                print '</td>';

                if ($conf->stock->enabled) {
                    print '<td align="center">';
                    print $product->stock_reel;
                    if ($product->stock_reel < $reste_a_livrer[$objp->fk_product]) {
                        print ' ' . img_warning($langs->trans("StockTooLow"));
                    }
                    print '</td>';
                } else {
                    print '<td>&nbsp;</td>';
                }
                print "</tr>";

                $i++;
            }
            $db->free($resql);

            if (!$num) {
                print '<tr ' . $bc[false] . '><td colspan="5">' . $langs->trans("NoArticleOfTypeProduct") . '<br>';
            }

            print "</table>";
        } else {
            dol_print_error($db);
        }

//        print '</div>';


        foreach ($arrGrpTmp as $key => $commande) {

            if ($user->rights->SynopsisPrepaCom->exped->Modifier) {

                // Bouton expedier avec gestion des stocks
                print "<br/>";
                print '<table width="100%">';

                if ($conf->stock->enabled && isset($reste_a_livrer_total) && $reste_a_livrer_total > 0 && $commande->statut > 0 && $commande->statut < 3 && $user->rights->expedition->creer) {
                    print '<tr><td width="50%" colspan="2" valign="top">';
                    $langs->load("orders");
                    print_titre($langs->trans("NewSending") . " " . $langs->trans("Order") . " " . $commande->getNomUrl(1));

                    print '<form method="GET" action="' . DOL_URL_ROOT . '/expedition/card.php">';
                    print '<input type="hidden" name="action" value="create">';
//                    print '<input type="hidden" name="id" value="' . $commande->id . '">';
                    print '<input type="hidden" name="origin" value="commande">';
                    print '<input type="hidden" name="object_id" value="' . $commande->id . '">';
                    print '<table class="border" width="100%">';

                    $entrepot = new Entrepot($db);
                    $langs->load("stocks");

                    print '<tr>';
                    print '<th class="ui-widget-header ui-state-default">' . $langs->trans("Warehouse") . '</td>';
                    print '<td class="ui-widget-content">';

                    if (sizeof($entrepot->list_array()) === 1) {
                        $uentrepot = array();
                        $uentrepot[$user->entrepots[0]['id']] = $user->entrepots[0]['label'];
                        print $html->selectarray("entrepot_id", $uentrepot);
                    } else {
                        print $html->selectarray("entrepot_id", $entrepot->list_array());
                    }

                    if (sizeof($entrepot->list_array()) <= 0) {
                        print ' &nbsp; Aucun entrep&ocirc;t d&eacute;finit, <a href="' . DOL_URL_ROOT . '/product/stock/card.php?action=create">definissez en un</a>';
                    }
                    print '</td></tr>';
                    /*
                      print '<tr><td width="20%">Mode d\'expedition</td>';
                      print '<td>';
                      print $html->selectarray("entrepot_id",$entrepot->list_array());
                      print '</td></tr>';
                     */

                    print '<tr><td align="center" colspan="2"  class="ui-wiget-header ui-state-default">';
                    print '<button style="padding: 5px 10px; width: 200px;"  class="butAction ui-corner-all ui-state-default ui-widget-header" named="save" value="' . $langs->trans("NewSending") . '">' . $langs->trans("NewSending") . '</button>';
                    print '</td></tr>';

                    print "</table>";
                    print "</form>\n";

                    $somethingshown = 1;
                    print "</td></tr></table>";
                }
            }
            global $form;
            $form = $html;
            show_list_sending_receive('commande', $commande->id, '', false);
//$origin='commande',$origin_id,$filter='',$display=true
            if ($user->rights->SynopsisPrepaCom->exped->Modifier) {
                print '<table cellpadding=10 class="border" width="100%">';
                print '<tr><td class="ui-widget-header" align="right"><button onClick="location.href=\'' . DOL_URL_ROOT . '/expedition/card.php?action=create&origin=commande&object_id=' . $_REQUEST["id"] . '\'" class="butAction">Expedier</button><button onClick="location.href=\'' . DOL_URL_ROOT . '/expedition/commande.php?id=' . $commande->id . '\'" class="butAction">Modifier</button>';
                print '</table>';
            }
        }
    } else {
        /* Commande non trouvee */
        print "Commande inexistante";
    }
}

function sendMail($subject, $to, $from, $msg, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = '', $addr_bcc = '', $deliveryreceipt = 0, $msgishtml = 1, $errors_to = '') {
    global $mysoc;
    global $langs;
    mailSyn2($subject, $to, $from, $msg, $filename_list, $mimetype_list, $mimefilename_list, $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, $errors_to);
    if ($res) {
        return (1);
    } else {
        return -1;
    }
}

function traite_str($str) {
    return $str;
}

$db->close();
?>
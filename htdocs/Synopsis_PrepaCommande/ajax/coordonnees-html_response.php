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
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
$langs->load("companies");

$errmesg = '';

// Security check
$socid = isset($_GET["socid"]) ? $_GET["socid"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '');

$userstatic = new User($db);

$form = new Form($db);

$requete = "SELECT fk_soc FROM " . MAIN_DB_PREFIX . "commande WHERE rowid = " . $_REQUEST['id'];
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$socid = $res->fk_soc;

if ($socid > 0) {
    // On recupere les donnees societes par l'objet
    $objsoc = new Societe($db);
    $objsoc->id = $socid;
    $objsoc->fetch($socid);//, $to);

    $dac = utf8_decode(strftime("%Y-%m-%d %H:%M", time()));
    if ($errmesg) {
        print "<b>$errmesg</b><br>";
    }

    /*
     *
     */
    print '<table width="100%" class="notopnoleftnoright" >';
    print '<tr><td valign="top" class="notopnoleft">';

    print '<table class="border" width="100%" cellpadding=10>';

    print '<tr><th width="30%" class="ui-state-default ui-widget-header">' . $langs->trans("Name") . '</td><td width="70%" colspan="3" class="ui-widget-content">';
    print utf8_encodeRien($objsoc->getNomUrl(1));
    print '</td></tr>';

    print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Prefix') . '</td><td colspan="3" class="ui-widget-content">' . utf8_encodeRien($objsoc->prefix_comm) . '</td></tr>';

    if ($objsoc->client) {
        print '<tr><th nowrap class="ui-state-default ui-widget-header">';
        print $langs->trans('CustomerCode') . '</td><td colspan="3"  class="ui-widget-content">';
        print utf8_encodeRien($objsoc->code_client);
        if ($objsoc->check_codeclient() <> 0)
            print '  <font class="error">(' . $langs->trans("WrongCustomerCode") . ')</font>';
        print '</td></tr>';
    }

    print "<tr><th class='ui-state-default ui-widget-header' valign=\"top\">" . $langs->trans('Address') . "</td><td  class='ui-widget-content' colspan=\"3\">" . utf8_encodeRien(nl2br($objsoc->address)) . "</td></tr>";

    print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Zip') . '</td><td class="ui-widget-content">' . $objsoc->zip . "</td>";
    print '<th class="ui-state-default ui-widget-header" >' . $langs->trans('Town') . '</td><td class="ui-widget-content">' . utf8_encodeRien($objsoc->town) . "</td></tr>";

    // Country
    print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Country") . '</td><td colspan="3"  class="ui-widget-content">';
    if ($objsoc->isInEEC())
        print $form->textwithtooltip($objsoc->pays, $langs->trans("CountryIsInEEC"), 1, 0);
    else
        print $objsoc->pays;
    print '</td></tr>';

    // Phone
    print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans('Phone') . '</td><td class="ui-widget-content">' . dol_print_phone($objsoc->phone, $objsoc->pays_code) . '</td>';

    // Fax
    print '<th class="ui-state-default ui-widget-header">' . $langs->trans('Fax') . '</td><td class="ui-widget-content">' . dol_print_phone($objsoc->fax, $objsoc->pays_code) . '</td></tr>';

    print '<tr><th class="ui-state-default ui-widget-header">' . $langs->trans("Web") . "</td><td colspan=\"3\"  class=\"ui-widget-content\"><a href=\"http://$objsoc->url\" target=\"_blank\">" . $objsoc->url . "</a>&nbsp;</td></tr>";

    // Assujeti TVA ou pas
    print '<tr>';
    print '<th class="ui-state-default ui-widget-header" nowrap="nowrap">' . $langs->trans('VATIsUsed') . '</td><td colspan="3"  class="ui-widget-content">';
    print yn($objsoc->tva_assuj);
    print '</td>';
    print '</tr>';


    // Multiprix
    if (isset($conf->global->PRODUIT_MULTIPRICES) && $conf->global->PRODUIT_MULTIPRICES) {
        print '<tr><th nowrap class="ui-state-default ui-widget-header">';
        print '<table width="100%" class="nobordernopadding"><tr><th nowrap>';
        print $langs->trans("PriceLevel");
        print '<td><td align="right" >';
        if ($user->rights->societe->creer) {
            print '<a href="' . DOL_URL_ROOT . '/comm/multiprix.php?id=' . $objsoc->id . '">' . img_edit($langs->trans("Modify")) . '</a>';
        }
        print '</td></tr></table>';
        print '</td><td colspan="3">' . $objsoc->price_level . "</td>";
        print '</tr>';
    }

    // Adresse de livraison
    if ($conf->expedition->enabled) {
        print '<tr><th nowrap class="ui-state-default ui-widget-header">';
        print $langs->trans("DeliveriesAddress");
        print '<td colspan="3"  class="ui-widget-content">';

//        $sql = "SELECT count(rowid) as nb";
//        $sql.= " FROM ".MAIN_DB_PREFIX."societe_adresse_livraison";
//        $sql.= " WHERE fk_soc =".$objsoc->id;

        print getAdresseLivraisonComm($_REQUEST['id']);

        print '</td>';
        print '</tr>';
    }
    print "</table>";
    print "<br/>";
    show_contacts($conf, $langs, $db, $objsoc);
}
?>
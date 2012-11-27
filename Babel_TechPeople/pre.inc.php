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
  *//**
      \file           htdocs/compta/pre.inc.php
      \ingroup      compta
      \brief          Fichier gestionnaire du menu compta
*/

require("../main.inc.php");


function llxHeader($head = "", $title="", $help_url='')
{
    global $user, $conf, $langs;

    top_menu($head, $title);

    $menu = new Menu();

    // Les recettes
    if ($conf->societe->enabled)
    {
        $langs->load("commercial");
        $menu->add(DOL_URL_ROOT."/compta/clients.php", $langs->trans("Customers"));
    }

    if ($conf->propal->enabled)
    {
        $langs->load("propal");
        $menu->add(DOL_URL_ROOT."/compta/propal.php",$langs->trans("Prop"));
    }

    if ($conf->contrat->enabled)
    {
        $langs->load("contracts");
        $menu->add(DOL_URL_ROOT."/contrat/",$langs->trans("Contracts"));
    }

    if ($conf->don->enabled)
    {
        $langs->load("donations");
        $menu->add(DOL_URL_ROOT."/compta/dons/",$langs->trans("Donations"));
    }

    if ($conf->facture->enabled)
    {
        $langs->load("bills");
        $menu->add(DOL_URL_ROOT."/compta/facture.php",$langs->trans("Bills"));
        $menu->add_submenu(DOL_URL_ROOT."/compta/facture/impayees.php",$langs->trans("Unpayed"));
        $menu->add_submenu(DOL_URL_ROOT."/compta/paiement/liste.php",$langs->trans("Payments"));

    if (! $conf->global->FACTURE_DISABLE_RECUR)
        {
            $menu->add_submenu(DOL_URL_ROOT."/compta/facture/fiche-rec.php", $langs->trans("Repeatable"));
        }

        $menu->add_submenu(DOL_URL_ROOT."/compta/facture/stats/", $langs->trans("Statistics"));
    }


    if ($conf->commande->enabled && $conf->facture->enabled)
    {
        $langs->load("orders");
        $menu->add(DOL_URL_ROOT."/compta/commande/liste.php?leftmenu=orders&amp;afacturer=1", $langs->trans("MenuOrdersToBill"));
    }

    // Les depenses
    if ($conf->fournisseur->enabled)
    {
        $langs->load("suppliers");
        $menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
    }

    if ($conf->deplacement->enabled && $user->societe_id == 0)
    {
        $langs->load("trips");
        $menu->add(DOL_URL_ROOT."/compta/deplacement/", $langs->trans("Trips"));
    }

    if ($conf->tax->enabled && $conf->compta->tva && $user->societe_id == 0)
    {
        $menu->add(DOL_URL_ROOT."/compta/tva/index.php",$langs->trans("VAT"));
    }

    if ($conf->tax->enabled)
    {
        $menu->add(DOL_URL_ROOT."/compta/charges/index.php",$langs->trans("Charges"));
    }


    // Vision des recettes-depenses
    if ($conf->banque->enabled && $user->rights->banque->lire)
    {
        $langs->load("banks");
        $menu->add(DOL_URL_ROOT."/compta/bank/",$langs->trans("Bank"));
    }

    $menu->add(DOL_URL_ROOT."/compta/stats/",$langs->trans("Reportings"));

    if ($conf->prelevement->enabled)
    {
        $menu->add(DOL_URL_ROOT."/compta/prelevement/",$langs->trans("StandingOrders"));
    }
//var_dump($conf);
    if ($conf->compta->enabled || $conf->global->MAIN_MODULE_COMPTABILITEEXPERT == 1)
    {
        if ($user->rights->compta->ventilation->creer)
        {
            $menu->add(DOL_URL_ROOT."/compta/ventilation/",$langs->trans("Ventilation"));
        }

        if ($user->rights->compta->ventilation->parametrer)
        {
            $menu->add(DOL_URL_ROOT."/compta/param/",$langs->trans("Param"));
        }
    }

    left_menu($menu->liste, $help_url);
}

?>

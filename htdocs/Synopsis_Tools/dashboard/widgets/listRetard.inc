<?php

/*
 * GLE by Synopsis
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 17 aout 2010
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/**
 *
 * Name : listRetard.php
 * GLE-1.2
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_listRetard() {
    // A widget object as per jquery.dashboard.js.
    global $db, $user, $langs, $bc, $conf;
    $var = true;
    if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }

    $table = '<p>';

    function showWeather($totallate, $text, $options) {
        global $conf;

        $out = '';
        $offset = 0;
        $cursor = 10; // By default
        //if (! empty($conf->global->MAIN_METEO_OFFSET)) $offset=$conf->global->MAIN_METEO_OFFSET;
        //if (! empty($conf->global->MAIN_METEO_GAP)) $cursor=$conf->global->MAIN_METEO_GAP;
        $level0 = $offset;
        if (!empty($conf->global->MAIN_METEO_LEVEL0))
            $level0 = $conf->global->MAIN_METEO_LEVEL0;
        $level1 = $offset + 1 * $cursor;
        if (!empty($conf->global->MAIN_METEO_LEVEL1))
            $level1 = $conf->global->MAIN_METEO_LEVEL1;
        $level2 = $offset + 2 * $cursor;
        if (!empty($conf->global->MAIN_METEO_LEVEL2))
            $level2 = $conf->global->MAIN_METEO_LEVEL2;
        $level3 = $offset + 3 * $cursor;
        if (!empty($conf->global->MAIN_METEO_LEVEL3))
            $level3 = $conf->global->MAIN_METEO_LEVEL3;

        if ($totallate <= $level0)
            $out.=img_picto_common($text, 'weather/weather-clear.png', $options);
        if ($totallate > $level0 && $totallate <= $level1)
            $out.=img_picto_common($text, 'weather/weather-few-clouds.png', $options);
        if ($totallate > $level1 && $totallate <= $level2)
            $out.=img_picto_common($text, 'weather/weather-clouds.png', $options);
        if ($totallate > $level2 && $totallate <= $level3)
            $out.=img_picto_common($text, 'weather/weather-many-clouds.png', $options);
        if ($totallate > $level3)
            $out.=img_picto_common($text, 'weather/weather-storm.png', $options);
        return $out;
    }

    /*
     * Dolibarr Working Board with weather
     */
    $showweather = empty($conf->global->MAIN_DISABLE_METEO) ? 1 : 0;
    $rowspan = 0;
    $dashboardlines = array();

    $table .= '<table class="noborder" width="100%">';
    $table .= '<tr class="liste_titre">';
    $table .= '<th class="liste_titre"colspan="2">' . $langs->trans("DolibarrWorkBoard") . '</th>';
    $table .= '<th class="liste_titre"align="right">' . $langs->trans("Number") . '</th>';
    $table .= '<th class="liste_titre"align="right">' . $langs->trans("Late") . '</th>';
    $table .= '<th class="liste_titre">&nbsp;</th>';
    $table .= '<th class="liste_titre"width="20">&nbsp;</th>';
    if ($showweather)
        $table .= '<th class="liste_titre" width="80">&nbsp;</th>';
    $table .= '</tr>';


//
// Do not include sections without management permission
//
// Number of actions to do (late)
    if (!empty($conf->agenda->enabled) && $user->rights->agenda->myactions->read) {
        include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $board = new ActionComm($db);
        $board->load_board($user);
        $board->warning_delay = $conf->actions->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("ActionsToDo");
        $board->url = DOL_URL_ROOT . '/comm/action/listactions.php?status=todo&mainmenu=agenda';
        $board->img = img_object($langs->trans("Actions"), "action");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of customer orders a deal
    if (!empty($conf->commande->enabled) && $user->rights->commande->lire) {
        include_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        $board = new Commande($db);
        $board->load_board($user);
        $board->warning_delay = $conf->commande->client->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("OrdersToProcess");
        $board->url = DOL_URL_ROOT . '/commande/liste.php?viewstatut=-2';
        $board->img = img_object($langs->trans("Orders"), "order");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of suppliers orders a deal
    if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire) {
        include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
        $board = new CommandeFournisseur($db);
        $board->load_board($user);
        $board->warning_delay = $conf->commande->fournisseur->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("SuppliersOrdersToProcess");
        $board->url = DOL_URL_ROOT . '/fourn/commande/index.php';
        $board->img = img_object($langs->trans("Orders"), "order");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of commercial proposals opened (expired)
    if (!empty($conf->propal->enabled) && $user->rights->propale->lire) {
        $langs->load("propal");

        include_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
        $board = new Propal($db);
        $board->load_board($user, "opened");
        $board->warning_delay = $conf->propal->cloture->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("PropalsToClose");
        $board->url = DOL_URL_ROOT . '/comm/propal/list.php?viewstatut=1';
        $board->img = img_object($langs->trans("Propals"), "propal");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of commercial proposals CLOSED signed (billed)
    if (!empty($conf->propal->enabled) && $user->rights->propale->lire) {
        $langs->load("propal");

        include_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
        $board = new Propal($db);
        $board->load_board($user, "signed");
        $board->warning_delay = $conf->propal->facturation->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("PropalsToBill");
        $board->url = DOL_URL_ROOT . '/comm/propal/list.php?viewstatut=2';
        $board->img = img_object($langs->trans("Propals"), "propal");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of services enabled (delayed)
    if (!empty($conf->contrat->enabled) && $user->rights->contrat->lire) {
        $langs->load("contracts");

        include_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $board = new Contrat($db);
        $board->load_board($user, "inactives");
        $board->warning_delay = $conf->contrat->services->inactifs->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("BoardNotActivatedServices");
        $board->url = DOL_URL_ROOT . '/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=0';
        $board->img = img_object($langs->trans("Contract"), "contract");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of active services (expired)
    if (!empty($conf->contrat->enabled) && $user->rights->contrat->lire) {
        $langs->load("contracts");

        include_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $board = new Contrat($db);
        $board->load_board($user, "expired");
        $board->warning_delay = $conf->contrat->services->expires->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("BoardRunningServices");
        $board->url = DOL_URL_ROOT . '/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=4&filter=expired';
        $board->img = img_object($langs->trans("Contract"), "contract");
        $rowspan++;
        $dashboardlines[] = $board;
    }
// Number of invoices customers (has paid)
    if (!empty($conf->facture->enabled) && $user->rights->facture->lire) {
        $langs->load("bills");

        include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        $board = new Facture($db);
        $board->load_board($user);
        $board->warning_delay = $conf->facture->client->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("CustomerBillsUnpaid");
        $board->url = DOL_URL_ROOT . '/compta/facture/impayees.php';
        $board->img = img_object($langs->trans("Bills"), "bill");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of supplier invoices (has paid)
    if (!empty($conf->fournisseur->enabled) && !empty($conf->facture->enabled) && $user->rights->facture->lire) {
        $langs->load("bills");

        include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
        $board = new FactureFournisseur($db);
        $board->load_board($user);
        $board->warning_delay = $conf->facture->fournisseur->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("SupplierBillsToPay");
        $board->url = DOL_URL_ROOT . '/fourn/facture/index.php?filtre=paye:0';
        $board->img = img_object($langs->trans("Bills"), "bill");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of transactions to conciliate
    if (!empty($conf->banque->enabled) && $user->rights->banque->lire && !$user->societe_id) {
        $langs->load("banks");

        include_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
        $board = new Account($db);
        $found = $board->load_board($user);
        if ($found > 0) {
            $board->warning_delay = $conf->bank->rappro->warning_delay / 60 / 60 / 24;
            $board->label = $langs->trans("TransactionsToConciliate");
            $board->url = DOL_URL_ROOT . '/compta/bank/index.php?leftmenu=bank&mainmenu=bank';
            $board->img = img_object($langs->trans("TransactionsToConciliate"), "payment");
            $rowspan++;
            $dashboardlines[] = $board;
        }
    }

// Number of cheque to send
    if (!empty($conf->banque->enabled) && $user->rights->banque->lire && !$user->societe_id) {
        $langs->load("banks");

        include_once DOL_DOCUMENT_ROOT . '/compta/paiement/cheque/class/remisecheque.class.php';
        $board = new RemiseCheque($db);
        $board->load_board($user);
        $board->warning_delay = $conf->bank->cheque->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("BankChecksToReceipt");
        $board->url = DOL_URL_ROOT . '/compta/paiement/cheque/index.php?leftmenu=checks&mainmenu=accountancy';
        $board->img = img_object($langs->trans("BankChecksToReceipt"), "payment");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Number of foundation members
    if (!empty($conf->adherent->enabled) && $user->rights->adherent->lire && !$user->societe_id) {
        $langs->load("members");

        include_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
        $board = new Adherent($db);
        $board->load_board($user);
        $board->warning_delay = $conf->adherent->cotisation->warning_delay / 60 / 60 / 24;
        $board->label = $langs->trans("MembersWithSubscriptionToReceive");
        $board->url = DOL_URL_ROOT . '/adherents/liste.php?mainmenu=members&statut=1';
        $board->img = img_object($langs->trans("Members"), "user");
        $rowspan++;
        $dashboardlines[] = $board;
    }

// Calculate total nb of late
    $totallate = 0;
    foreach ($dashboardlines as $key => $board) {
        if ($board->nbtodolate > 0)
            $totallate+=$board->nbtodolate;
    }

// Show dashboard
    $var = true;
    foreach ($dashboardlines as $key => $board) {
        $var = !$var;
        $table .= '<tr ' . $bc[$var] . '><td width="16">' . $board->img . '</td><td>' . $board->label . '</td>';
        $table .= '<td align="right"><a href="' . $board->url . '">' . $board->nbtodo . '</a></td>';
        $table .= '<td align="right">';
        $table .= '<a href="' . $board->url . '">';
        $table .= $board->nbtodolate;
        $table .= '</a></td>';
        $table .= '<td align="left">';
        if ($board->nbtodolate > 0)
            $table .= img_picto($langs->trans("NActionsLate", $board->nbtodolate), "warning");
        else
            $table .= '&nbsp;';
        $table .= '</td>';
        $table .= '<td nowrap="nowrap" align="right">';
        $table .= ' (>' . ceil($board->warning_delay) . ' ' . $langs->trans("days") . ')';
        $table .= '</td>';
        if ($showweather) {
            $table .= '<td class="nohover" rowspan="' . $rowspan . '" width="80" style="border-left: 1px solid #DDDDDD" align="center">';
            $text = '';
            if ($totallate > 0)
                $text = $langs->transnoentitiesnoconv("WarningYouHaveAtLeastOneTaskLate") . ' (' . $langs->transnoentitiesnoconv("NActionsLate", $totallate) . ')';
            $options = 'height="64px"';
            if ($rowspan <= 2)
                $options = 'height="24"';  // Weather logo is smaller if dashboard has few elements
            else if ($rowspan <= 3)
                $options = 'height="48"';  // Weather logo is smaller if dashboard has few elements
            $table .= showWeather($totallate, $text, $options);
            //$table .= showWeather(0,'');
            //$table .= showWeather(40,$text);
            $table .= '</td>';
            $showweather = 0;
        }
        $table .= '</tr>';
        $table .= "\n";
    }


    $table .= '</table>';   // End table array






































    $table .= '</p>';

    return array(
        'title' => 'Retards - Liste des retards',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
    );
}

// The widget settings handler.
function widget_listRetard_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

?>

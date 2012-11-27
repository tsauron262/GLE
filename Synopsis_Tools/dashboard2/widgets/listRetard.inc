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
  * Infos on http://www.babel-services.com
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
    global $db,$user,$langs,$bc,$conf;
    $var = true;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }

    $table = '<p>';

    $table .= '<table class="noborder" width="100%">';
    $table .= '<tr class="liste_titre">';
    $table .= '<td colspan="2">'.$langs->trans("DolibarrWorkBoard").'</td>';
    $table .= '<td align="right">'.$langs->trans("Number").'</td>';
    $table .= '<td align="right">'.$langs->trans("Late").'</td>';
    $table .= '<td>&nbsp;</td>';
    $table .= '<td width="20">&nbsp;</td>';
    $table .= '</tr>';
      // Nbre actions e faire (en retard)
    if ($conf->agenda->enabled && $user->rights->agenda->myactions->read)
    {
      include_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
      $board=new ActionComm($db);
      $board->load_board($user);
      $board->warning_delay=$conf->actions->warning_delay/60/60/24;
      $board->label=$langs->trans("ActionsToDo");

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Actions"),"task").'</td><td>'.$board->label.'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?status=todo">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?status=todo">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($board->warning_delay).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td  align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-widget-content ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
      $table .= "\n";
    }

    // Nbre commandes clients e traiter
    if ($conf->commande->enabled && $user->rights->commande->lire)
    {
      include_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
      $board=new Commande($db);
      $board->load_board($user);

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Orders"),"order").'</td><td>'.$langs->trans("OrdersToProcess").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/commande/liste.php?viewstatut=-2">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/commande/liste.php?viewstatut=-2">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->commande->traitement->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    $table .= "\n";
    }

    // Nbre propales ouvertes (expirees)
    if ($conf->propal->enabled && $user->rights->propale->lire)
    {
      $langs->load("propal");

      include_once(DOL_DOCUMENT_ROOT."/propal.class.php");
      $board=new Propal($db);
      $board->load_board($user,"opened");

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Propals"),"propal").'</td><td>'.$langs->trans("PropalsToClose").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=1">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=1">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right" >';
      $table .= ' (>'.ceil($conf->propal->cloture->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td  align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    }

    // Nbre propales fermees signees (a facturer)
    if ($conf->propal->enabled && $user->rights->propale->lire)
    {
      $langs->load("propal");

      include_once(DOL_DOCUMENT_ROOT."/propal.class.php");
      $board=new Propal($db);
      $board->load_board($user,"signed");

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Propals"),"propal").'</td><td>'.$langs->trans("PropalsToBill").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=2">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=2">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->propal->facturation->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= " class='ui-state-highlight' > ". img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    $table .= "\n";
    }

    // Nbre services a activer (en retard)
    if ($conf->contrat->enabled && $user->rights->contrat->lire)
    {
      $langs->load("contracts");

      include_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
      $board=new Contrat($db);
      $board->load_board($user,"inactives");

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Contract"),"contract").'</td><td>'.$langs->trans("BoardNotActivatedServices").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=0">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=0">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->contrat->services->inactifs->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    $table .= "\n";
    }

    // Nbre services actifs (a renouveler)
    if ($conf->contrat->enabled && $user->rights->contrat->lire)
    {
      $langs->load("contracts");

      include_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
      $board=new Contrat($db);
      $board->load_board($user,"expired");

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Contract"),"contract").'</td><td>'.$langs->trans("BoardRunningServices").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=4">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=4">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->contrat->services->expires->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    $table .= "\n";
    }

    // Nbre factures fournisseurs (e payer)
    if ($conf->fournisseur->enabled && $conf->facture->enabled && $user->rights->facture->lire)
    {
      $langs->load("bills");

      include_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.facture.class.php");
      $board=new FactureFournisseur($db);
      $board->load_board($user);

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Bills"),"bill").'</td><td>'.$langs->trans("SupplierBillsToPay").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?filtre=paye:0">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?filtre=paye:0">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->facture->fournisseur->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    $table .= "\n";
    }

    // Nbre factures clients (a payer)
    if ($conf->facture->enabled && $user->rights->facture->lire)
    {
      $langs->load("bills");

      include_once(DOL_DOCUMENT_ROOT."/facture.class.php");
      $board=new Facture($db);
      $board->load_board($user);

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Bills"),"bill").'</td><td>'.$langs->trans("CustomerBillsUnpayed").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->facture->client->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    $table .= "\n";
    }

    // Nbre ecritures a rapprocher
    if ($conf->banque->enabled && $user->rights->banque->lire && ! $user->societe_id)
    {
      $langs->load("banks");

      include_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");
      $board=new Account($db);
      $board->load_board($user);

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("TransactionsToConciliate"),"payment").'</td><td>'.$langs->trans("TransactionsToConciliate").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/bank/index.php?leftmenu=bank&mainmenu=bank">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php?leftmenu=bank&mainmenu=bank">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->bank->rappro->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    $table .= "\n";
    }

      // Nbre ecritures a rapprocher
    if ($conf->banque->enabled && $user->rights->banque->lire && ! $user->societe_id)
    {
      $langs->load("banks");

      include_once(DOL_DOCUMENT_ROOT."/compta/paiement/cheque/remisecheque.class.php");
      $board=new RemiseCheque($db);
      $board->load_board($user);

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("BankChecksToReceipt"),"payment").'</td><td>'.$langs->trans("BankChecksToReceipt").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/index.php?leftmenu=checks&mainmenu=accountancy">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/index.php?leftmenu=checks&mainmenu=accountancy">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->bank->cheque->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
      $table .= "\n";
    }


    // Nbre adherent valides (attente cotisation)
    if ($conf->adherent->enabled && $user->rights->adherent->lire && ! $user->societe_id)
    {
      $langs->load("members");

      include_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
      $board=new Adherent($db);
      $board->load_board($user);

      $var=!$var;
      $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Members"),"user").'</td><td>'.$langs->trans("Members").'</td>';
      $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/adherents/liste.php?mainmenu=members&statut=1">'.$board->nbtodo.'</a></td>';
      $table .= '<td align="right">';
      $table .= '<a href="'.DOL_URL_ROOT.'/adherents/liste.php?mainmenu=members&statut=1">';
      $table .= $board->nbtodolate;
      $table .= '</a></td><td nowrap align="right">';
      $table .= ' (>'.ceil($conf->adherent->cotisation->warning_delay/60/60/24).' '.$langs->trans("days").')';
      $table .= '</td>';
      $table .= '<td align="center" ';
      if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
      else $table .= '>&nbsp;';
      $table .= '</td>';
      $table .= '</tr>';
    $table .= "\n";
    }
    //Nbe de produit avec stock faible

    //Nbe de contrat inactif

    //Nbe de contrat expirer

    //Tax / Tva a payer

    //Ndf a valider

    //Propal a valider

    //Commande a valider

    //Facture a valider

    //Nbe de contrat a traiter

    //Nbe expedition a traiter

    //Nbe domain a echéance
    if ($conf->global->MAIN_MODULE_DOMAIN == 1 && $user->rights->domain->lire && ! $user->societe_id)
    {

          include_once(DOL_DOCUMENT_ROOT."/domain/domain.class.php");
          $board=new Domain($db);
          $board->load_board($user);

          $var=!$var;
          $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Domaine &agrave; expiration"),'domain').'</td><td>'.$langs->trans("Domaine &agrave; expiration").'</td>';
          $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/domain/index.php?leftmenu=domain&mainmenu=tools">'.$board->nbtodo.'</a></td>';
          $table .= '<td align="right">';
          $table .= '<a href="'.DOL_URL_ROOT.'/domain/index.php?leftmenu=domain&mainmenu=tools">';
          $table .= $board->nbtodolate;
          $table .= '</a></td><td nowrap align="right">';
          $table .= ' (<'.ceil($conf->domain->warning_delay/60/60/24).' '.$langs->trans("days").')';
          $table .= '</td>';
          $table .= '<td align="center" ';
          if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
          else $table .= '>&nbsp;';
          $table .= '</td>';
          $table .= '</tr>';
          $table .= "\n";
    }

    //Nbe SSLCerts a echéance
    if ($conf->global->MAIN_MODULE_SSLCERT == 1 && $user->rights->SSLCert->lire && ! $user->societe_id)
    {

          include_once(DOL_DOCUMENT_ROOT."/Babel_SSLCert/SSLCert.class.php");
          $board=new SSLCert($db);
          $board->load_board($user);

          $var=!$var;
          $table .= '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Certificat &agrave; expiration"),'SSLCERT').'</td><td>'.$langs->trans("Certificat &agrave; expiration").'</td>';
          $table .= '<td align="right"><a href="'.DOL_URL_ROOT.'/Babel_SSLCert/index.php?leftmenu=domain&mainmenu=tools">'.$board->nbtodo.'</a></td>';
          $table .= '<td align="right">';
          $table .= '<a href="'.DOL_URL_ROOT.'/Babel_SSLCert/index.php?leftmenu=SSLCert&mainmenu=tools">';
          $table .= $board->nbtodolate;
          $table .= '</a></td><td nowrap align="right">';
          $table .= ' (<'.ceil($conf->Babel_SSLCert->warning_delay/60/60/24).' '.$langs->trans("days").')';
          $table .= '</td>';
          $table .= '<td align="center" ';
          if ($board->nbtodolate > 0) { $table .= "class='ui-state-highlight'>".img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
          else $table .= '>&nbsp;';
          $table .= '</td>';
          $table .= '</tr>';
          $table .= "\n";

    }

    $table .= '</table>';

    $table .= '</p>';

      return array(
        'title' => 'Retards - Liste des retards',
        'content' => utf8_encode($table),
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

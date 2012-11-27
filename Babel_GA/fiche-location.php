<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 1 avr. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : fiche-location.php
  * GLE-1.1
  */
//Affiche le cartouche avec


require_once("./pre.inc.php");
$langs->load("synopsisGene@Synopsis_Tools");
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$header .= '<script language="javascript" src="'.$jspath.'jquery.validate.min.js"></script>'."\n";

if ($conf->global->MAIN_MODULE_BABELGA) require_once(DOL_DOCUMENT_ROOT."/Babel_GA/LocationGA.class.php");


$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');


llxHeader($header,$langs->trans('LocationCard'));

if ($_REQUEST['id']>0)
{

    /*
     *
     */
    $loc = new LocationGA($db);
    $loc->fetch($_REQUEST['id']);



    $head = array(0=>array(DOL_URL_ROOT."/Babel_GA/fiche-location.php",'Fiche','location'),
                  1=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php",'Stock de location','stock'),
                  2=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?fournisseur=1",'Stock de location fournisseur','stock'),
                  3=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?client=1",'Stock de location client','stock'),
                  4=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?cessionnaire=1",'Stock de location cessionnaire','stock'),);

    if ($loc->serial."x" != "x")
    {
        $head = array(0=>array(DOL_URL_ROOT."/Babel_GA/fiche-location.php",'Fiche','location'),
                      1=>array(DOL_URL_ROOT."/Babel_GA/glpi.php?id=".$loc->id,'GLPI','glpi'),
                      2=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php",'Stock de location','stock'),
                      3=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?fournisseur=1",'Stock de location fournisseur','stock'),
                      4=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?client=1",'Stock de location client','stock'),
                      5=>array(DOL_URL_ROOT."/Babel_GA/stock_GA.php?cessionnaire=1",'Stock de location cessionnaire','stock'),);
    }

    dol_fiche_head($head, 'location', $langs->trans("Location de produit"));



    print '<table cellpadding=15 class="border" width="100%">';

//Nom du produit
    print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("Name").'</th>
               <td width="70%" colspan="3" class="ui-widget-content">';
    print $loc->product->getNomUrl(1)."&nbsp;&nbsp;&nbsp;";
    print $loc->product->libelle;
    print '</td></tr>';
//Référence
    print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("Serial").'</th>
               <td width="70%" colspan="3" class="ui-widget-content">';
    print $loc->getNomUrl(1);
    //print $loc->serial;
    print '</td></tr>';
//Description
    print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("Description").'</th>
               <td width="70%" colspan="3" class="ui-widget-content">';
    print $loc->product->description;
    print '</td></tr>';
//statut
    print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("Statut").'</th>
               <td width="70%" colspan="3" class="ui-widget-content">';
    print $loc->getLibStatut(4);
    print '</td></tr>';
//addresse courante
    print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("Adresse").'</th>
               <td width="70%" colspan="3" class="ui-widget-content">';
    print $loc->getAddress();
    print '</td></tr>';
    print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("Responsable").'</th>
               <td width="70%" colspan="3" class="ui-widget-content">';
    print $loc->user_resp->getNomUrl(1);
    print '</td></tr>';
    switch ($loc->statut)
    {
        case 0: {
            print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("En stock :").'</th>
                       <td width="70%" colspan="3" class="ui-widget-content">';
            print "<table width=100% class='nobordernopadding'>";
            print "<tr><td width=100>Cessionnaire<td>".$loc->cession->getNomUrl(1);
            print "</table>";
            print '</td></tr>';
            print "</table>";
        };
        break;
        case 1: {
            $deb = date('d/m/Y',strtotime($loc->dateDeb));
            $fin = date('d/m/Y',strtotime($loc->dateFin));

            print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("En Location :").'du '.$deb.' au '.$fin.'</th>
                       <td width="70%" colspan="3" class="ui-widget-content">';
            print "<table width=100% class='nobordernopadding'>";
            print "<tr><td width=100>Cessionnaire<td>".$loc->cession->getNomUrl(1);
            print "<tr><td width=100>Fournisseur<td>".$loc->fourn->getNomUrl(1);
            print "<tr><td width=100>Client<td>".$loc->client->getNomUrl(1);
            print "</table>";
            print '</td></tr>';
            print "</table>";

        }
        break;
        case 2: {
            $fin = date('d/m/Y',strtotime($loc->dateDeSortieDefinitive));
            print '<tr><th class="ui-widget-header ui-state-default" width="30%">'.$langs->trans("En Location :").'du '.$deb.' au '.$fin.'</th>
                       <td width="70%" colspan="3" class="ui-widget-content">';
            print "<table width=100% class='nobordernopadding'>";
            print "<tr><td width=100>Date de sortie<td>".$fin;
            print "<tr><td width=100>Prix de cession<td>".price(round($loc->PrixCessionALaSortie*100)/100);
        }
        break;
    }


//Historique de location
    $requete = "SELECT * FROM ";

} else {
    print "<div class='ui-state-error error'>Erreur de navidation</div>";
}


?>



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
 */
    include_once ("../master.inc.php");
    include_once ("./pre.inc.php");

    //Limit

require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");


require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

$langs->load("contracts");
$langs->load("products");
$langs->load("companies");

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_contract=isset($_GET["search_contract"])?$_GET["search_contract"]:$_POST["search_contract"];
$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];
$statut=isset($_GET["statut"])?$_GET["statut"]:1;
$socid=isset($_GET['socid'])?$_GET['socid']:$_POST['socid'];

if (! $sortfield) $sortfield="c.rowid";
if (! $sortorder) $sortorder="DESC";

if ($user->rights->BabelGSM->BabelGSM_com->AfficheContrat !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}

// Security check
$contratid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $contratid,'');


$langs->load("sendings");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Expedition", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);

$sql = 'SELECT';
$sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND cd.date_fin_validite > sysdate()",1,0).') as nb_running,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite <= sysdate())",1,0).') as nb_late,';
$sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
$sql.= " c.rowid as cid, c.ref, c.datec, c.date_contrat, c.statut, s.nom, s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."contrat as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql.= " WHERE c.fk_soc = s.rowid ";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_nom)      $sql.= " AND s.nom like '%".addslashes($search_nom)."%'";
if ($search_contract) $sql.= " AND c.rowid = '".addslashes($search_contract)."'";
if ($sall)            $sql.= " AND (s.nom like '%".addslashes($sall)."%' OR cd.label like '%".addslashes($sall)."%' OR cd.description like '%".addslashes($sall)."%')";
if ($socid > 0)       $sql.= " AND s.rowid = ".$socid;
$sql.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.rowid";
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($limit + 1 ,$offset);

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print_barre_liste($langs->trans("ListOfContracts"), $page, $_SERVER["PHP_SELF"], "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

    print '<table class="liste" width="100%">';

    print '<tr class="liste_titre">';
    $param='&amp;search_contract='.$search_contract;
    $param.='&amp;search_nom='.$search_nom;
    print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "c.rowid","","$param",'width="50"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"), $_SERVER["PHP_SELF"], "s.nom","","$param","",$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("DateCreation"), $_SERVER["PHP_SELF"], "c.datec","","$param",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateContract"), $_SERVER["PHP_SELF"], "c.date_contrat","","$param",'align="center"',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "c.statut","","$param",'align="center"',$sortfield,$sortorder);
    print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(0,3).'</td>';
    print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(4,3).'</td>';
    print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(5,3).'</td>';
    print "</tr>\n";

    //print '<form method="POST" action="contrat.php">';
    //print '<tr class="liste_titre">';
    //print '<td class="liste_titre">';
    //print '<input type="text" class="flat" size="3" name="search_contract" value="'.$search_contract.'">';
    //print '</td>';
    //print '<td class="liste_titre" valign="right">';
    //print '<input type="text" class="flat" size="24" name="search_nom" value="'.$search_nom.'">';
    //print '</td>';
    //print '<td class="liste_titre">&nbsp;</td>';
    //print '<td class="liste_titre">&nbsp;</td>';
    //print '<td colspan="3" class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    //print "</td>";
    //print "</tr>\n";
    //print '</form>';

    $now=time();
    $var=True;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;
        print "<tr $bc[$var]>";
        print "<td nowrap><a href=\"contrat_detail.php?contrat_id=$obj->cid\">";
        print img_object($langs->trans("ShowContract"),"contract").' '.(isset($obj->ref) ? $obj->ref : $obj->cid) .'</a>';
        if ($obj->nb_late) print img_warning($langs->trans("Late"));
        print '</td>';
        print '<td><a href="client_detail.php?client_id='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
        //print '<td align="center">'.dol_print_date($obj->datec).'</td>';
        print '<td align="center">'.dol_print_date($obj->date_contrat).'</td>';
        //print '<td align="center">'.$staticcontrat->LibStatut($obj->statut,3).'</td>';
        print '<td align="center">'.($obj->nb_initial>0?$obj->nb_initial:'').'</td>';
        print '<td align="center">'.($obj->nb_running+$obj->nb_late>0?$obj->nb_running+$obj->nb_late:'').'</td>';
        print '<td align="center">'.($obj->nb_closed>0?$obj->nb_closed:'').'</td>';
        print "</tr>\n";
        $i++;
    }
    $db->free($resql);

    print "</table>";
}
$gsm->jsCorrectSize(true);

?>
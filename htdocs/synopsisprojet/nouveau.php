<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 16 aout 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : nouveau.php
  * GLE-1.2
  */

    require_once('pre.inc.php');
$langs->load("synopsisproject@synopsisprojet");
    llxHeader("","Nouveau projet","",1);

    $search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
    $search_ville=isset($_GET["search_ville"])?$_GET["search_ville"]:$_POST["search_ville"];
    $socname=isset($_GET["socname"])?$_GET["socname"]:$_POST["socname"];
    $sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
    $sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
    $page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];

    if (! $sortorder) $sortorder="ASC";
    if (! $sortfield) $sortfield="nom";

    if ($page == -1) { $page = 0 ; }

    $offset = $conf->liste_limit * $page ;
    $pageprev = $page - 1;
    $pagenext = $page + 1;
    $sql = "SELECT s.rowid, s.nom, s.town, s.datec as datec";
    $sql.= ", st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur,";
    if ($conf->global->MAIN_MODULE_BABELGA)
    {
        $sql.=" s.cessionnaire, ";
    }
    $sql.= " s.siren as idprof1, s.siret as idprof2, ape as idprof3, idprof4 as idprof4";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE s.fk_stcomm = st.id AND client > 0";
    if ($socid)
    {
        $sql .= " AND s.rowid = ".$socid;
    }
    if (strlen($stcomm))
    {
        $sql .= " AND s.fk_stcomm=".$stcomm;
    }

    if (! $user->rights->societe->client->voir && ! $socid) //restriction
    {
        $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    }
    if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
    {
        if (! $user->rights->fournisseur->lire) $sql.=" AND s.fournisseur != 1";
    }

    if ($search_nom)
    {
        $sql.= " AND (";
        $sql.= "s.nom LIKE '%".addslashes($search_nom)."%'";
        $sql.= " OR s.code_client LIKE '%".addslashes($search_nom)."%'";
        $sql.= " OR s.email like '%".addslashes($search_nom)."%'";
        $sql.= " OR s.url like '%".addslashes($search_nom)."%'";
        $sql.= ")";
    }

    $sql .= " ORDER BY $sortfield $sortorder ";
    // Count total nb of records
    $nbtotalofrecords = 0;
    $result;
    if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
    {
        $result = $db->query($sql);
        $nbtotalofrecords = $db->num_rows($result);
    } else {
        $result = $db->query($sql);
        $nbtotalofrecords = $db->num_rows($result);
        $sql .= " LIMIT ".$conf->global->MAIN_DISABLE_FULL_SCANLIST;
        $result = $db->query($sql);
    }

    $result = $db->query($sql);
    if ($result)
    {

        print "<br/>";
        print "<div style='padding-left: 25px;'>";
        print "<form action='card.php?action=create' method='GET'>";
        print "<input type='hidden' name='action' value='create' >";
        print "<table cellpadding=15 width=700>";
        print "<tr><th style='font-size: 12pt' class='ui-widget-header ui-state-default' colspan=2>Choix du destinataire";
        if ($db->num_rows($result) > 0)
        {
            print "<tr><td width=50% class='ui-widget-content' align=center><select name='socid'>";
            while ($res=$db->fetch_object($result))
            {
                print "<option value='".$res->rowid."' >".htmlentities($res->nom)."</option>";
            }
            print"</select>";
        } else {
            print "<tr><td width=50% class='ui-widget-content' align=center>Pas de tiers trouv&eacute;";
        }
        print "</td><td class='ui-widget-content' align=center>";
        print '<button style="padding: 5px 10px;" class="ui-widget-header ui-corner-all ui-state-default butAction" ><span style="padding: 1px 10px;float: left;">'.$langs->trans("Etape suivante").'</span><span style="float: left;" class="ui-icon ui-icon-arrowreturnthick-1-e"></button>';
        print "</table>";
        print "</form>";
        if ($nbtotalofrecords > $db->num_rows($result))
        {
            print "<em>".$db->num_rows($result)."/".$nbtotalofrecords." r&eacute;sultats affich&eacute;s</em>";
        }
        print "<form action='nouveau.php' method='GET'>";
        print "<table width=700 cellpadding=15>";
        print "<tr><th width=50%  style='padding: 5px 10px' class='ui-widget-header ui-state-default' colspan=2>Filtrer par nom :</th><td align=center class='ui-widget-content'>";
        print "<table width=100%><tr><td align=center>";
        print "<input style='text-align:center;' type='text' name='search_nom' value='".$_REQUEST['search_nom']."'><td>";
        print "<input type='hidden' name='action' value='create'>";
        print '<button style="padding: 5px 10px;" class="ui-widget-header ui-corner-all ui-state-default butAction" ><span style="padding: 1px 10px;float: left;">'.$langs->trans("Filtrer").'</span><span style="float: left;" class="ui-icon ui-icon-circle-zoomout"></button>';
        print "</td>";
        print "</table";
        print "</form>";
    }
llxFooter('$Date: 2008/02/25 20:03:27 $ - $Revision: 1.20 $');
?>

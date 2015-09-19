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
    require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");

$requete = "SELECT fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE rowid = ".$_REQUEST['id'];
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$socid=$res->fk_soc;

    $langs->load("companies");
    $langs->load("commercial");
    $langs->load("customers");
    $langs->load("suppliers");
    $langs->load("banks");

    // Security check
    //$socid = isset($_GET["socid"])?$_GET["socid"]:'';
    if ($user->societe_id) $socid=$user->societe_id;
    $result = restrictedArea($user, 'societe','','');


/*
*    View
*/

if ($socid>0)
{
    $soc = new Societe($db);
    $soc->id = $socid;
    $result=$soc->fetch($socid);

    /*
    * Fiche societe en mode visu
    */

    print '<table cellpadding=15 class="border" width="100%">';
    print '<tr><th class="ui-widget-header ui-state-default" width="20%">'.$langs->trans('Name').'</td><td class="ui-widget-content" colspan="3">'.utf8_encodeRien($soc->getNomUrl(1)).'</td></tr>';

  print '<tr><th class="ui-widget-header ui-state-default">';
  print $langs->trans('CustomerCode').'</td><td  class="ui-widget-content" width="20%">';
  print utf8_encodeRien($soc->code_client);
  if ($soc->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
  print '</td><th  class="ui-widget-header ui-state-default">'.$langs->trans('Prefix').'</td><td class="ui-widget-content">'.utf8_encodeRien($soc->prefix_comm).'</td></tr>';

    print "<tr><th class='ui-widget-header ui-state-default' valign=\"top\">".$langs->trans('Address')."</td><td class='ui-widget-content' colspan=\"3\">".utf8_encodeRien(nl2br($soc->address))."</td></tr>";

    print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans('Zip').'</td><td class="ui-widget-content">'.$soc->zip."</td>";
    print '<th class="ui-widget-header ui-state-default">'.$langs->trans('Town').'</td><td class="ui-widget-content">'.utf8_encodeRien($soc->town)."</td></tr>";

    print '<tr><th  class="ui-widget-header ui-state-default">'.$langs->trans('Country').'</td><td colspan="3" class="ui-widget-content">'.utf8_encodeRien($soc->pays).'</td>';

    print '<tr><th  class="ui-widget-header ui-state-default">'.$langs->trans('Phone').'</td><td class="ui-widget-content">'.dol_print_phone($soc->phone).'</td>';
    print '<th  class="ui-widget-header ui-state-default">'.$langs->trans('Fax').'</td><td class="ui-widget-content">'.dol_print_phone($soc->fax).'</td></tr>';

    print '<tr><th  class="ui-widget-header ui-state-default">'.$langs->trans('Web').'</td><td colspan="3" class="ui-widget-content">';
    if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
    print '</td></tr>';

    // Liste les commerciaux
    print '<tr><th  class="ui-widget-header ui-state-default" valign="top">'.$langs->trans("SalesRepresentatives").'</td>';
    print '<td colspan="3"  class="ui-widget-content">';

    $sql = "SELECT u.rowid, u.lastname, u.firstname";
    $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE sc.fk_soc =".$soc->id;
    $sql .= " AND sc.fk_user = u.rowid";
    $sql .= " ORDER BY u.firstname ASC ";

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);

          if (!$user->rights->societe->client->voir)
          {
            print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
            print img_object($langs->trans("ShowUser"),"user").' ';
            print stripslashes($obj->firstname)." " .stripslashes($obj->lastname)."\n";
            print '</a><br>';
            $i++;
          }
          else
          {
            print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
            print img_object($langs->trans("ShowUser"),"user").' ';
            print utf8_encodeRien($obj->firstname." " .$obj->lastname."\n");
            print '</a>&nbsp;';
            if ($user->rights->SynopsisPrepaCom->all->AssocierCom)
            {
                print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$socid.'&amp;delcommid='.$obj->rowid.'">';
                print img_delete();
                print '</a><br>';
            } else {
                print "<br/>";
            }
            $i++;
          }
        }

        $db->free($resql);
    }
    else
    {
        dol_print_error($db);
    }
    if($i == 0) { print $langs->trans("NoSalesRepresentativeAffected"); }

    print "</td></tr>";

    print '</table>';


    if ($user->rights->SynopsisPrepaCom->all->AssocierCom)
    {
        print "<br/>";
        /*
        * Liste
        *
        */

        $langs->load("users");
        $title=$langs->trans("ListOfUsers");

        $sql = "SELECT u.rowid, u.lastname, u.firstname, u.login";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql .= " , ".MAIN_DB_PREFIX."usergroup as ug";
        $sql .= " , ".MAIN_DB_PREFIX."usergroup_user as ugu";
        $sql .= " WHERE u.rowid = ugu.fk_user";
        $sql .= " AND ug.rowid = ugu.fk_usergroup";
        $sql .= " AND ug.nom = 'Commerciaux (tous)'";
        $sql .= " ORDER BY u.firstname ASC ";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;

            load_fiche_titre($title);

            // Lignes des titres
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Name").'</td>';
            print '<td>'.$langs->trans("Login").'</td>';
            print '<td>&nbsp;</td>';
            print "</tr>\n";

            $var=true;

            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]><td>";
                print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
                print img_object($langs->trans("ShowUser"),"user").' ';
                print stripslashes($obj->firstname)." " .stripslashes($obj->lastname)."\n";
                print '</a>';
                print '</td><td>'.utf8_encodeRien($obj->login).'</td>';
                print '<td><a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$socid.'&amp;commid='.$obj->rowid.'">'.$langs->trans("Add").'</a></td>';

                print '</tr>'."\n";
                $i++;
            }

            print "</table>";
            $db->free($resql);
        }
        else
        {
            dol_print_error($db);
        }
    }

}


$db->close();


?>
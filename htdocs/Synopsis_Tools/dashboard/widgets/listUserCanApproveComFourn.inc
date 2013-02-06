<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');

// The get widget handler.
function widget_listUserCanApproveComFourn() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("orders");

    $commandestatic=new CommandeFournisseur($db);


      $table =  '<p>';

$table .= '<table class="notopnoleftnoright" width="100%">';
$table .= '<tr valign="top"><td class="notopnoleft" width="30%">';

$sql = "SELECT count(cf.rowid), fk_statut";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cf.fk_soc = s.rowid ";
if (!$user->rights->societe->client->voir && !$socid) //restriction
{
  $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
}
$sql.= " GROUP BY cf.fk_statut";
$sql .= " LIMIT 50";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

      $table .= '<table class="liste" width="100%">';
      $table .= '<tr class="liste_titre"><td>'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Nb").'</td><td>&nbsp;</td>';
      $table .= "</tr>\n";
      $sql = "SELECT u.name, u.firstname
          FROM ".MAIN_DB_PREFIX."user as u,
               ".MAIN_DB_PREFIX."user_rights as ur
         WHERE u.rowid = ur.fk_user
           AND ur.fk_id = (SELECT id FROM ".MAIN_DB_PREFIX."rights_def WHERE module='fournisseur' AND perms='commande' AND subperms='approuver')";

        $resql = $db->query($sql);
        if ($resql)
        {
          $num = $db->num_rows($resql);
          $i = 0;

          $table .=  '<table class="liste" width="100%">';
          $table .=  '<tr class="liste_titre"><td>Personnes habilit&eacute;es &agrave; approuver les commandes</td>';
          $table .=  "</tr>\n";
          $var=True;

          while ($i < $num)
            {
              $row = $db->fetch_row($resql);
              $var=!$var;

              $table .=  "<tr $bc[$var]>";
              $table .=  '<td>'.$row[1].' '.$row[0].'</td>';
              $table .=  "</tr>\n";
              $i++;
            }
          $table .=  "</table>";
          $db->free($resql);
        }


        $table .="</p>";
      return array(
        'title' => 'Commande fournisseur - Personnes habilit&eacute;es &agrave; approuver les commandes',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    } else {
      return array(
        'title' => 'Commande fournisseur - Personnes habilit&eacute;es &agrave; approuver les commandes',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listUserCanApproveComFourn_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

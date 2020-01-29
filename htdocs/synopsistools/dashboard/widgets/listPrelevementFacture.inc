<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

// The get widget handler.
function widget_listPrelevementFacture() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("withdrawals");

    $staticcontrat=new Contrat($db);
    $staticcompany = new Societe($db);
    $staticcontratligne=new ContratLigne($db);

      $table =  '<p>';
      $table2 = '</p>';


    if ($user->rights->prelevement->bons->lire)
    {

        $sql = "SELECT f.ref, f.rowid, s.nom, s.rowid as socid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
        $sql .= " WHERE s.rowid = f.fk_soc";
        $sql .= " AND pfd.traite = 0 AND pfd.fk_facture = f.rowid LIMIT 50";

        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

        if ($socid)
        {
          $sql .= " AND f.fk_soc = $socid";
        }

        if ( $result = $db->query($sql) )
        {
          $num = $db->num_rows($result);
          $i = 0;

          $table .= '<table class="noborder" width="100%">';
          $table .= '<tr class="liste_titre">';
          $table .= '<td colspan="2">Factures en attente de pr&eacute;l&egrave;vement ('.$num.')</td></tr>';

          $table2 .= '<table class="noborder" width="100%">';
          $table2 .= '<tr class="liste_titre">';
          $table2 .= '<td colspan="2">Factures en attente de pr&eacute;l&egrave;vement ('.$num.')</td></tr>';
          $var = true;
          while ($i < $num)
          {
              $obj = $db->fetch_object();
              $var=!$var;
              if ($i < 10)
              {
                  $table .=  '<tr '.$bc[$var].'><td>';
                  $table .=  '<a href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$obj->rowid.'">'.img_file().' '.$obj->ref.'</a></td>';
                  $table .=  '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$obj->nom.'</a></td>';
                  $table .=  '</tr>';
              }

              $table2 .=  '<tr '.$bc[$var].'><td>';
              $table2 .=  '<a href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$obj->rowid.'">'.img_file().' '.$obj->ref.'</a></td>';
              $table2 .=  '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$obj->nom.'</a></td>';
              $table2 .=  '</tr>';


              $i++;
          }
          $table .= "</table><br>";
          $table2 .= "</table><br>";
        }


        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Pr&eacute;l&egrave;vement - 10 derni&egrave;re factures en attente',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/initFullscreen.js',
      );

    } else {
      return array(
        'title' => 'Pr&eacute;l&egrave;vement - 10 derni&egrave;re factures en attente',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listPrelevementFacture_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

// A silly little helper function for the form.
//function selected($data, $color) {
//  if ($data['color'] == $color) {
//    return ' value="' . $color . '" selected="selected"';
//  }
//  else {
//    return ' value="' . $color . '"';
//  }
//}

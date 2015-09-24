<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

// The get widget handler.
function widget_listProjet() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("projects");

    $projet=new Project($db);

    $table =  '<p>';
    $table2 = '</p>';



    if ($conf->projet->enabled && $user->rights->projet->lire)
    {
        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre">';
        $table .= '<td align="right">'.$langs->trans("Project").'</td>';
        $table .= '<td align="right">'.$langs->trans("NbOpenTasks").'</td>';
        $table .= '<td align="right">'.$langs->trans("Avancement").'</td>';
        $table .= "</tr>\n";

        $sql = "SELECT p.title, p.rowid, count(t.rowid), avg(t.progress) as prg";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
        $sql.= " ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
        $sql.= " WHERE 1 = 1";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid)
        {
          $sql .= " AND p.fk_soc = ".$socid;
        }
        $sql.= " GROUP BY p.rowid";




        $var=true;
        $resql = $db->query($sql);
        if ( $resql )
        {
          $num = $db->num_rows($resql);
          $i = 0;

          while ($i < $num)
            {
              $row = $db->fetch_row( $resql);
              $var=!$var;
              $table .= "<tr $bc[$var]>";
              $table .= '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$row[1].'">'.img_object($langs->trans("ShowProject"),"project")." ".$row[0].'</a></td>';
              $table .= '<td align="right">'.$row[2].'</td>';
              $table .= '<td align="right"><div style="height: 16px; border:1px solid #000000;" id="progBar'.$row[1].'"></div></td>';
              $table .= "</tr>\n";
              $table .= '<script type="text/javascript">$("#progBar'.$row[1].'").progressbar({ value:"'.$row[3].'"})</script>';

              $i++;
            }

          $db->free($resql);
        }
        $table .=  "</table>";

        $table .="</p>";
        return array(
        'title' => 'Projets - Liste & avancement',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );

    } else {
      return array(
        'title' => 'Projets - Liste & avancement',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listProjet_settings() {
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

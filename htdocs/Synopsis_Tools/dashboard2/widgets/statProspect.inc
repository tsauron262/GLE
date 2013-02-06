<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.

function widget_statProspect() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    $langs->load("companies");
    $langs->load("commercial");

    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $table = '<p>';

    $prodser = array();
    $prodser[0][0]=$prodser[0][1]=$prodser[1][0]=$prodser[1][1]=0;

    $sql = "SELECT count(*) as cc, st.libelle, st.id";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st ";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE s.fk_stcomm = st.id AND s.client=2";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    $sql .= " GROUP BY st.id";
    $sql .= " ORDER BY st.id";

    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num > 0 )
        {
            $var=true;

            $table .= '<table class="noborder" width="100%">';
            $table .= '<tr class="liste_titre">';
            $table .= '<td colspan="2">'.$langs->trans("ProspectsByStatus").'</td></tr>';
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $var=!$var;
                $table .= "<tr $bc[$var]><td><a href=\"prospects.php?page=0&amp;stcomm=".$obj->id."\">";
                $table .= img_action($langs->trans("Show"),$obj->id).' ';
                $table .= $langs->trans("StatusProspect".$obj->id);
                $table .= '</a></td><td align="right">'.$obj->cc.'</td></tr>';
                $i++;
            }
            $table .= "</table><br>";
        }
    }


    $table .="</p>";

  return array(
    'title' => 'Prospects - Statistiques',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_statProspect_settings() {
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

<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Tools/commandeGroup/commandeGroup.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

// The get widget handler.
function widget_listPrepaGroupCommande() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
        $socid = $user->societe_id;
    }
    $langs->load("orders");
    $formfile = new FormFile($db);

    $commandeGroup=new commandeGroup($db);


      $table =  '<p>';
      $table2 = '</p>';

    if ($conf->commande->enabled && $user->rights->commande->commande->group)
    {
        $sql = "SELECT count(".MAIN_DB_PREFIX."Synopsis_commande_grpdet.commande_group_refid) as cnt,
                       ".MAIN_DB_PREFIX."Synopsis_commande_grp.id
                  FROM ".MAIN_DB_PREFIX."Synopsis_commande_grp
             LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_commande_grpdet ON ".MAIN_DB_PREFIX."Synopsis_commande_grp.id = ".MAIN_DB_PREFIX."Synopsis_commande_grpdet.commande_group_refid
              GROUP BY ".MAIN_DB_PREFIX."Synopsis_commande_grp.id
              ORDER BY ".MAIN_DB_PREFIX."Synopsis_commande_grp.tms DESC LIMIT 50";

//$table .= $sql;

        $resql=$db->query($sql);
        if ($resql)
        {
            $total = 0;
            $total2 = 0;
            $ftotal = 0;
            $ftotal2 = 0;
            $num = $db->num_rows($resql);
            if ($num > 0)
            {

                $cssClass[true]='pair';
                $cssClass[false]='impair';
                $bool = false;
                $iter = 0;
                $table .= '<table class="noborder" width="100%">';
                $table .= "<tr class=\"liste_titre\">";
                $table .= "<td colspan=\"1\">".$langs->trans("Groupe de commandes")."</td>";
                $table .= "<td align='center' colspan=\"1\">Nb com.</td></tr>";

                $table2 .= '<table class="noborder" width="100%">';
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"1\">".$langs->trans("Groupe de commandes")."</td>";
                $table2 .= "<td colspan=\"1\">&nbsp;</td></tr>";

                $bool = false;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                while ($res =$db->fetch_object($resql))
                {
                    $commandeGroup->fetch($res->id);
                    $bool = !$bool;
                    if ($iter < 10)
                    {
                            $var=!$var;
                            $table .= '<tr class='.$arrCss[$bool].'>
                                    <td  nowrap="nowrap">';
                            $table .= $commandeGroup->getNomUrl(1);
                            $table .=  '<td align="center">'.$res->cnt.'</td>';
                    }
                    $var=!$var;
                    $table2 .= '<tr class='.$arrCss[$bool].'>
                            <td  nowrap="nowrap">';
                    $table2 .= $commandeGroup->getNomUrl(1);
                    $table2 .=  '<td align="center">'.$res->cnt.'</td>';
                    $iter++;
                }
                $table2 .=  "</table><br>";
            }
        }
        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Pr&eacute;paration commande - 10 derniers groupes modifi&eacute;s',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    }else {
      return array(
        'title' => 'Pr&eacute;paration commande - 10 derniers groupes modifi&eacute;s',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listPrepaGroupCommande_settings() {
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
